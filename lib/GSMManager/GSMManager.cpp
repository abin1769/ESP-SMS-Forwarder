#include "GSMManager.h"

GSMManager::GSMManager() {
    _serial = nullptr;
    _rxPin = 16;
    _txPin = 17;
    _baudRate = 9600;
    _state = GSM_STATE_UNINITIALIZED;
    _lastStateTimer = 0;
    _lastHealthCheck = 0;
}

bool GSMManager::begin(HardwareSerial* serialPort, int rxPin, int txPin, long baudRate) {
    _serial = serialPort;
    _rxPin = rxPin;
    _txPin = txPin;
    _baudRate = baudRate;

    _serial->begin(_baudRate, SERIAL_8N1, _rxPin, _txPin);
    
    Serial.println("[GSM]\nInitializing...");
    _state = GSM_STATE_CHECK_AT;
    _lastStateTimer = millis();

    return true;
}

String GSMManager::sendCommand(String command, uint32_t timeout) {
    if (!_serial) return "";

    // Clear input buffer
    while (_serial->available()) {
        _serial->read();
    }

    _serial->println(command);
    String response = "";
    uint32_t start = millis();

    while (millis() - start < timeout) {
        while (_serial->available()) {
            char c = _serial->read();
            response += c;
        }
        
        // Early break if response completes
        if (response.indexOf("OK\r") != -1 || 
            response.indexOf("ERROR\r") != -1 || 
            response.indexOf("+CMS ERROR") != -1 || 
            response.indexOf("+CME ERROR") != -1) {
            break;
        }
        delay(10);
    }

    return response;
}

bool GSMManager::waitForResponse(String target, uint32_t timeout) {
    uint32_t start = millis();
    String buffer = "";
    while (millis() - start < timeout) {
        while (_serial && _serial->available()) {
            char c = _serial->read();
            buffer += c;
            if (buffer.indexOf(target) != -1) {
                return true;
            }
        }
        delay(10);
    }
    return false;
}

void GSMManager::update() {
    if (!_serial) return;

    unsigned long currentMillis = millis();

    // --- Non-blocking State Machine ---
    switch (_state) {
        case GSM_STATE_UNINITIALIZED:
            break;

        case GSM_STATE_CHECK_AT: {
            String resp = sendCommand("AT", 1000);
            if (resp.indexOf("OK") != -1) {
                _state = GSM_STATE_CONFIGURING;
                _lastStateTimer = currentMillis;
            } else if (currentMillis - _lastStateTimer > 10000) {
                Serial.println("[GSM]\nError: SIM800L not responding. Retrying...");
                _lastStateTimer = currentMillis;
            }
            break;
        }

        case GSM_STATE_CONFIGURING: {
            sendCommand("ATE0", 1000);                // Disable Echo
            sendCommand("AT+CMGF=1", 1000);            // Text Mode
            sendCommand("AT+CNMI=2,2,0,0,0", 1000);    // Immediate SMS notification

            Serial.println("[GSM]\nSIM800L Ready");
            _state = GSM_STATE_READY;
            _lastHealthCheck = currentMillis;
            break;
        }

        case GSM_STATE_READY: {
            // Periodic health check every 45s
            if (currentMillis - _lastHealthCheck >= 45000) {
                _lastHealthCheck = currentMillis;
                String resp = sendCommand("AT", 1000);
                if (resp.indexOf("OK") == -1) {
                    Serial.println("[GSM]\nModule unresponsive! Re-initializing...");
                    _state = GSM_STATE_CHECK_AT;
                    _lastStateTimer = currentMillis;
                    return;
                }
            }
            break;
        }

        case GSM_STATE_ERROR:
            if (currentMillis - _lastStateTimer >= 5000) {
                Serial.println("[GSM]\nAttempting recovery from error state...");
                _state = GSM_STATE_CHECK_AT;
                _lastStateTimer = currentMillis;
            }
            break;
    }

    // --- Stream Incoming SMS Detector (+CMTI: "SM",3) ---
    while (_serial->available()) {
        String line = _serial->readStringUntil('\n');
        line.trim();

        int cmtiIdx = line.indexOf("+CMTI:");
        if (cmtiIdx != -1) {
            int commaIdx = line.indexOf(",", cmtiIdx);
            if (commaIdx != -1) {
                int smsIndex = line.substring(commaIdx + 1).toInt();
                Serial.println("[GSM]\nSMS detected");
                _pendingSMSIndexes.push(smsIndex);
            }
        }
    }

    // Read queued SMS indexes
    if (!_pendingSMSIndexes.empty() && _state == GSM_STATE_READY) {
        int targetIdx = _pendingSMSIndexes.front();
        _pendingSMSIndexes.pop();

        // Give SIM800L 200ms to settle memory index write
        delay(200);

        String raw = sendCommand("AT+CMGR=" + String(targetIdx), 3000);
        
        Serial.println("[GSM] Debug CMGR Raw Response:");
        Serial.println(raw);

        if (raw.indexOf("+CMGR:") != -1) {
            SMSMessage msg = parseCMGRResponse(targetIdx, raw);
            if (msg.phone.length() > 0) {
                Serial.println("[GSM]\nPhone:");
                Serial.println(msg.phone);
                Serial.println("[GSM]\nMessage:");
                Serial.println(msg.message);

                _smsQueue.push(msg);
            } else {
                Serial.println("[GSM]\nParsing failed. Skipping SMS.");
            }
        } else {
            Serial.println("[GSM]\nFailed to read SMS index. Skipping.");
        }
    }
}

SMSMessage GSMManager::parseCMGRResponse(int index, String raw) {
    SMSMessage sms;
    sms.index = index;
    sms.phone = "";
    sms.message = "";
    sms.datetime = "";

    int cmgrPos = raw.indexOf("+CMGR:");
    if (cmgrPos == -1) {
        return sms;
    }

    // Locate quotes relative to +CMGR:
    int firstQuote  = raw.indexOf("\"", cmgrPos);
    int secondQuote = raw.indexOf("\"", firstQuote + 1);
    int thirdQuote  = raw.indexOf("\"", secondQuote + 1);
    int fourthQuote = raw.indexOf("\"", thirdQuote + 1);

    if (thirdQuote != -1 && fourthQuote != -1) {
        sms.phone = raw.substring(thirdQuote + 1, fourthQuote);
        sms.phone.trim();
    }

    // Locate timestamp quotes (5th & 6th or 7th & 8th)
    int fifthQuote = raw.indexOf("\"", fourthQuote + 1);
    int sixthQuote = raw.indexOf("\"", fifthQuote + 1);
    int seventhQuote = raw.indexOf("\"", sixthQuote + 1);
    int eighthQuote = raw.indexOf("\"", seventhQuote + 1);

    String rawTime = "";
    if (seventhQuote != -1 && eighthQuote != -1) {
        rawTime = raw.substring(seventhQuote + 1, eighthQuote);
    } else if (fifthQuote != -1 && sixthQuote != -1) {
        rawTime = raw.substring(fifthQuote + 1, sixthQuote);
    }

    if (rawTime.length() >= 17) {
        // Convert "YY/MM/DD,HH:MM:SS+TZ" to "20YY-MM-DD HH:MM:SS"
        String year = "20" + rawTime.substring(0, 2);
        String month = rawTime.substring(3, 5);
        String day = rawTime.substring(6, 8);
        String time = rawTime.substring(9, 17);
        sms.datetime = year + "-" + month + "-" + day + " " + time;
    }

    // Parse message body text (line after header)
    int headerEnd = raw.indexOf("\n", cmgrPos);
    if (headerEnd != -1) {
        String body = raw.substring(headerEnd + 1);
        int okIdx = body.indexOf("OK");
        if (okIdx != -1) {
            body = body.substring(0, okIdx);
        }
        body.trim();
        sms.message = body;
    }

    return sms;
}

bool GSMManager::hasNewSMS() {
    return !_smsQueue.empty();
}

SMSMessage GSMManager::readSMS() {
    if (_smsQueue.empty()) {
        SMSMessage emptySMS = { -1, "", "", "" };
        return emptySMS;
    }

    SMSMessage sms = _smsQueue.front();
    _smsQueue.pop();
    return sms;
}

bool GSMManager::deleteSMS(int index) {
    String cmd = "AT+CMGD=" + String(index);
    String resp = sendCommand(cmd, 2000);
    bool success = (resp.indexOf("OK") != -1);

    if (success) {
        Serial.println("[GSM]\nDelete success");
    } else {
        Serial.printf("[GSM]\nDelete failed for SMS #%d\n", index);
    }
    return success;
}

bool GSMManager::sendSMS(String number, String message) {
    String cmdNumber = "AT+CMGS=\"" + number + "\"";
    _serial->println(cmdNumber);
    delay(300);

    _serial->print(message);
    _serial->write(26);

    return waitForResponse("OK", 5000);
}

int GSMManager::getSignal() {
    String resp = sendCommand("AT+CSQ", 1500);
    int csq = 0;
    int idx = resp.indexOf("+CSQ:");
    if (idx != -1) {
        String sub = resp.substring(idx + 5);
        sub.trim();
        int commaIdx = sub.indexOf(",");
        if (commaIdx != -1) {
            csq = sub.substring(0, commaIdx).toInt();
        }
    }
    return csq;
}

String GSMManager::getOperator() {
    String resp = sendCommand("AT+COPS?", 2000);
    int idx = resp.indexOf("\"");
    if (idx != -1) {
        int endIdx = resp.indexOf("\"", idx + 1);
        if (endIdx != -1) {
            return resp.substring(idx + 1, endIdx);
        }
    }
    return "UNKNOWN";
}
