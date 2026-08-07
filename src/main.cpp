#include <Arduino.h>
#include "config.h"
#include "WifiManagerCustom.h"
#include "GSMManager.h"
#include "ApiClient.h"

// Global Manager Instances
WifiManagerCustom wifi;
GSMManager        gsm;
ApiClient         api;

unsigned long lastHeartbeatTime = 0;

void setup() {
    Serial.begin(115200);
    delay(1000);

    Serial.println("\n==================================================");
    Serial.println("  ESP32-S3 SIM800L SMS Gateway Starting...");
    Serial.println("==================================================");

    wifi.begin(WIFI_SSID, WIFI_PASSWORD);
    gsm.begin(&Serial2, SIM800_RX, SIM800_TX, SIM800_BAUD);
    api.begin(API_URL, DEVICE_TOKEN);
}

void loop() {
    // 1. Maintain WiFi Connection & Non-blocking GSM State Machine
    wifi.update();
    gsm.update();

    unsigned long currentMillis = millis();

    // 2. Heartbeat Timer (every HEARTBEAT_INTERVAL)
    if (wifi.isConnected() && (currentMillis - lastHeartbeatTime >= HEARTBEAT_INTERVAL)) {
        lastHeartbeatTime = currentMillis;

        int signal = gsm.getSignal();
        String opName = gsm.getOperator();
        String simStatus = gsm.getSIMStatus();
        String regStatus = gsm.getRegistrationStatus();
        String pendingCmd = "";

        // Send heartbeat & receive any pending remote AT command from Web Console
        bool hbSuccess = api.heartbeat(signal, opName, simStatus, regStatus, pendingCmd);

        if (hbSuccess && pendingCmd.length() > 0) {
            Serial.println("\n[Remote Console] Received AT Command from Web:");
            Serial.println("> " + pendingCmd);

            String result = gsm.executeCustomAT(pendingCmd, 3000);

            Serial.println("[Remote Console] SIM800L Execution Result:");
            Serial.println(result);

            // Report execution result back to Laravel Web Console
            api.sendATResponse(pendingCmd, result);
        }
    }

    // 3. SMS Detection & Forwarding Flow
    if (gsm.hasNewSMS()) {
        SMSMessage sms = gsm.readSMS();

        if (wifi.isConnected() && api.sendSMS(sms)) {
            gsm.deleteSMS(sms.index);
        } else {
            Serial.println("[System]\nAPI error or WiFi disconnected. SMS retained on SIM for retry.");
        }
    }

    delay(10); // Yield to system tasks
}
