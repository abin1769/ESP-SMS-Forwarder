#ifndef CONFIG_H
#define CONFIG_H

// ============================================================================
// WiFi Configuration
// ============================================================================
#define WIFI_SSID         "OPPO A58"
#define WIFI_PASSWORD     "25252525"

// ============================================================================
// Backend Laravel API Configuration
// ============================================================================
#define API_URL           "http://10.22.226.31:8000"
#define DEVICE_TOKEN      "ESP32_DEFAULT_SECRET_TOKEN_12345"

// ============================================================================
// SIM800L Hardware Pin & Serial Configuration (ESP32-S3 default)
// ============================================================================
#define SIM800_RX         16  // ESP32 RX2 connected to SIM800L TX
#define SIM800_TX         17  // ESP32 TX2 connected to SIM800L RX
#define SIM800_BAUD       9600

// ============================================================================
// Timing Constants (in milliseconds)
// ============================================================================
#define HEARTBEAT_INTERVAL 30000 // Send heartbeat every 30 seconds

#endif // CONFIG_H
