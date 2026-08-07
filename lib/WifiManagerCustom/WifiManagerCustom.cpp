#include "WifiManagerCustom.h"

WifiManagerCustom::WifiManagerCustom() {
    _ssid = nullptr;
    _password = nullptr;
    _lastReconnectAttempt = 0;
}

void WifiManagerCustom::begin(const char* ssid, const char* password) {
    _ssid = ssid;
    _password = password;

    WiFi.mode(WIFI_STA);
    WiFi.begin(_ssid, _password);

    Serial.print("[WiFi]\nConnecting to ");
    Serial.println(_ssid);

    int attempts = 0;
    while (WiFi.status() != WL_CONNECTED && attempts < 20) {
        delay(500);
        Serial.print(".");
        attempts++;
    }
    Serial.println();

    if (WiFi.status() == WL_CONNECTED) {
        Serial.println("[WiFi]\nConnected");
        Serial.print("IP: ");
        Serial.println(WiFi.localIP());
    } else {
        Serial.println("[WiFi]\nInitial connection failed. Non-blocking auto-reconnect active.");
    }
}

void WifiManagerCustom::update() {
    if (WiFi.status() != WL_CONNECTED) {
        unsigned long currentMillis = millis();
        if (currentMillis - _lastReconnectAttempt >= RECONNECT_INTERVAL) {
            _lastReconnectAttempt = currentMillis;
            Serial.println("[WiFi]\nConnection lost. Attempting reconnect...");
            WiFi.disconnect();
            WiFi.begin(_ssid, _password);
        }
    }
}

bool WifiManagerCustom::isConnected() {
    return WiFi.status() == WL_CONNECTED;
}

String WifiManagerCustom::getIP() {
    return WiFi.localIP().toString();
}
