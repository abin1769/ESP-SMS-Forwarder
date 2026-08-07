#ifndef API_CLIENT_H
#define API_CLIENT_H

#include <Arduino.h>
#include <HTTPClient.h>
#include <WiFi.h>
#include "../GSMManager/GSMManager.h"

class ApiClient {
private:
    String _baseUrl;
    String _token;

public:
    ApiClient();

    void begin(String baseUrl, String token);
    bool sendSMS(SMSMessage sms);
    bool heartbeat(int signal, String operatorName);
};

#endif // API_CLIENT_H
