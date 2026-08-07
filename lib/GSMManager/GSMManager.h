#ifndef GSM_MANAGER_H
#define GSM_MANAGER_H

#include <Arduino.h>
#include <HardwareSerial.h>
#include <queue>

struct SMSMessage {
    int index;
    String phone;
    String message;
    String datetime;
};

enum GSMState {
    GSM_STATE_UNINITIALIZED,
    GSM_STATE_CHECK_AT,
    GSM_STATE_CONFIGURING,
    GSM_STATE_READY,
    GSM_STATE_ERROR
};

class GSMManager {
private:
    HardwareSerial* _serial;
    int _rxPin;
    int _txPin;
    long _baudRate;

    GSMState _state;
    unsigned long _lastStateTimer;
    unsigned long _lastHealthCheck;
    
    std::queue<int> _pendingSMSIndexes;
    std::queue<SMSMessage> _smsQueue;

    // Internal AT Command helper
    String sendCommand(String command, uint32_t timeout = 2000);
    bool waitForResponse(String target, uint32_t timeout = 2000);

    // Parsing helpers
    SMSMessage parseCMGRResponse(int index, String rawResponse);

public:
    GSMManager();

    bool begin(HardwareSerial* serialPort = &Serial2, int rxPin = 16, int txPin = 17, long baudRate = 9600);
    void update();
    bool hasNewSMS();
    SMSMessage readSMS();
    bool deleteSMS(int index);
    bool sendSMS(String number, String message);
    int getSignal();
    String getOperator();
    String getSIMStatus();
    String getRegistrationStatus();
    String executeCustomAT(String command, uint32_t timeout = 3000);
};

#endif // GSM_MANAGER_H
