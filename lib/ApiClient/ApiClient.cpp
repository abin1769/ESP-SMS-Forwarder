#include "ApiClient.h"

ApiClient::ApiClient() {
    _baseUrl = "";
    _token = "";
}

void ApiClient::begin(String baseUrl, String token) {
    _baseUrl = baseUrl;
    if (_baseUrl.endsWith("/")) {
        _baseUrl = _baseUrl.substring(0, _baseUrl.length() - 1);
    }
    _token = token;
}

bool ApiClient::sendSMS(SMSMessage sms) {
    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("[API]\nSend SMS failed: WiFi Disconnected");
        return false;
    }

    HTTPClient http;
    String endpoint = _baseUrl + "/api/sms";

    Serial.println("[API]\nPOST...");
    http.begin(endpoint);

    http.addHeader("Content-Type", "application/json");
    http.addHeader("X-Device-Token", _token);

    // Escape special characters in message for valid JSON
    String escapedMsg = sms.message;
    escapedMsg.replace("\\", "\\\\");
    escapedMsg.replace("\"", "\\\"");
    escapedMsg.replace("\n", "\\n");
    escapedMsg.replace("\r", "\\r");
    escapedMsg.replace("\t", "\\t");

    // Construct JSON body
    String payload = "{";
    payload += "\"token\":\"" + _token + "\",";
    payload += "\"phone\":\"" + sms.phone + "\",";
    payload += "\"message\":\"" + escapedMsg + "\"";
    if (sms.datetime.length() > 0) {
        payload += ",\"received_at\":\"" + sms.datetime + "\"";
    }
    payload += "}";

    int httpCode = http.POST(payload);
    bool success = false;

    if (httpCode == HTTP_CODE_OK || httpCode == HTTP_CODE_CREATED) {
        Serial.printf("[API]\nHTTP %d\n", httpCode);
        success = true;
    } else {
        if (httpCode > 0) {
            Serial.printf("[API]\nHTTP Error %d: %s\n", httpCode, http.getString().c_str());
        } else {
            Serial.printf("[API]\nHTTP POST Connection Failed: %s\n", http.errorToString(httpCode).c_str());
        }
        success = false;
    }

    http.end();
    return success;
}

bool ApiClient::heartbeat(int signal, String operatorName) {
    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("[API]\nHeartbeat failed: WiFi Disconnected");
        return false;
    }

    HTTPClient http;
    String endpoint = _baseUrl + "/api/device/heartbeat";

    Serial.println("[API]\nPOST Heartbeat...");
    http.begin(endpoint);

    http.addHeader("Content-Type", "application/json");
    http.addHeader("X-Device-Token", _token);

    String escapedOp = operatorName;
    escapedOp.replace("\"", "\\\"");

    String payload = "{";
    payload += "\"token\":\"" + _token + "\",";
    payload += "\"signal\":" + String(signal) + ",";
    payload += "\"operator\":\"" + escapedOp + "\"";
    payload += "}";

    int httpCode = http.POST(payload);
    bool success = false;

    if (httpCode == HTTP_CODE_OK || httpCode == HTTP_CODE_CREATED) {
        Serial.printf("[API]\nHTTP %d\n", httpCode);
        success = true;
    } else {
        if (httpCode > 0) {
            Serial.printf("[API]\nHTTP Error %d\n", httpCode);
        } else {
            Serial.printf("[API]\nHeartbeat Failed: %s\n", http.errorToString(httpCode).c_str());
        }
        success = false;
    }

    http.end();
    return success;
}
