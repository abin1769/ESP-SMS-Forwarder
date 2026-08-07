#ifndef WIFI_MANAGER_CUSTOM_H
#define WIFI_MANAGER_CUSTOM_H

#include <Arduino.h>
#include <WiFi.h>

class WifiManagerCustom {
private:
    const char* _ssid;
    const char* _password;
    unsigned long _lastReconnectAttempt;
    const unsigned long RECONNECT_INTERVAL = 10000; // Try reconnecting every 10s

public:
    WifiManagerCustom();
    void begin(const char* ssid, const char* password);
    void update();
    bool isConnected();
    String getIP();
};

#endif // WIFI_MANAGER_CUSTOM_H
