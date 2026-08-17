#include "WifiManagerCustom.h"
#include <esp_wifi.h>

WifiManagerCustom::WifiManagerCustom() 
    : _server(80), _inPortalMode(false), _buttonPressStart(0), _lastReconnectAttempt(0), _isConfigured(false) {
    _ssid = "";
    _password = "";
    _apiUrl = "";
    _deviceToken = "";
}

void WifiManagerCustom::loadFromPreferences() {
    _prefs.begin("sms_gw", true); // Open in read-only mode
    _ssid = _prefs.getString("ssid", DEFAULT_WIFI_SSID);
    _password = _prefs.getString("pass", DEFAULT_WIFI_PASSWORD);
    _apiUrl = _prefs.getString("api_url", DEFAULT_API_URL);
    _deviceToken = _prefs.getString("token", DEFAULT_DEVICE_TOKEN);
    _isConfigured = _prefs.getBool("configured", false);
    _prefs.end();

    // Sanitasi URL jika berakhiran slash '/'
    if (_apiUrl.endsWith("/")) {
        _apiUrl = _apiUrl.substring(0, _apiUrl.length() - 1);
    }
}

void WifiManagerCustom::saveToPreferences(String ssid, String pass, String apiUrl, String token) {
    if (apiUrl.endsWith("/")) {
        apiUrl = apiUrl.substring(0, apiUrl.length() - 1);
    }

    _prefs.begin("sms_gw", false); // Open in read-write mode
    _prefs.putString("ssid", ssid);
    _prefs.putString("pass", pass);
    _prefs.putString("api_url", apiUrl);
    _prefs.putString("token", token);
    _prefs.putBool("configured", true);
    _prefs.end();

    _ssid = ssid;
    _password = pass;
    _apiUrl = apiUrl;
    _deviceToken = token;
    _isConfigured = true;
}

void WifiManagerCustom::resetConfig() {
    _prefs.begin("sms_gw", false);
    _prefs.clear();
    _prefs.end();
    Serial.println("[NVS] Konfigurasi berhasil dihapus (Factory Reset).");
}

void WifiManagerCustom::begin() {
    pinMode(SETUP_TRIGGER_PIN, INPUT_PULLUP);

    loadFromPreferences();

    Serial.println("\n[WiFi Manager] Membaca konfigurasi dari memori Flash (NVS)...");
    Serial.printf(" - SSID Target : %s\n", _ssid.c_str());
    Serial.printf(" - Server API  : %s\n", _apiUrl.c_str());
    Serial.printf(" - Device Token: %s\n", _deviceToken.c_str());

    // Cek jika SSID kosong atau belum pernah dikonfigurasi sama sekali
    if (_ssid.length() == 0) {
        Serial.println("[WiFi Manager] Belum ada konfigurasi WiFi. Membuka Portal Konfigurasi...");
        startConfigPortal(true);
        return;
    }

    // Coba koneksi ke WiFi dalam mode STA
    WiFi.mode(WIFI_STA);
    WiFi.begin(_ssid.c_str(), _password.c_str());

    Serial.print("[WiFi Manager] Menghubungkan ke ");
    Serial.print(_ssid);

    unsigned long startAttempt = millis();
    while (WiFi.status() != WL_CONNECTED && (millis() - startAttempt < WIFI_CONNECT_TIMEOUT)) {
        delay(500);
        Serial.print(".");
        
        // Cek jika tombol BOOT ditekan saat proses koneksi
        if (digitalRead(SETUP_TRIGGER_PIN) == LOW) {
            Serial.println("\n[WiFi Manager] Tombol BOOT ditekan. Beralih ke Web Portal...");
            startConfigPortal(false);
            return;
        }
    }
    Serial.println();

    if (WiFi.status() == WL_CONNECTED) {
        Serial.println("[WiFi Manager] ✅ Terhubung ke WiFi Modem!");
        Serial.print(" - IP Address : ");
        Serial.println(WiFi.localIP());
        Serial.print(" - Gateway IP  : ");
        Serial.println(WiFi.gatewayIP());
        Serial.print(" - RSSI Sinyal : ");
        Serial.printf("%d dBm\n", WiFi.RSSI());
    } else {
        Serial.println("\n[WiFi Manager] ⚠️ Gagal terhubung ke WiFi dalam batas waktu (15 detik)!");
        Serial.println("[WiFi Manager] Membuka Web Portal secara otomatis agar dapat dikonfigurasi ulang...");
        startConfigPortal(true);
    }
}

void WifiManagerCustom::update() {
    // 1. Jika sedang dalam portal mode, proses request web & DNS
    if (_inPortalMode) {
        _dnsServer.processNextRequest();
        _server.handleClient();
        return;
    }

    // 2. Deteksi tombol BOOT (Setup Trigger) ditekan selama 3 detik
    if (digitalRead(SETUP_TRIGGER_PIN) == LOW) {
        if (_buttonPressStart == 0) {
            _buttonPressStart = millis();
        } else if (millis() - _buttonPressStart >= 3000) {
            Serial.println("\n[WiFi Manager] 🔘 Tombol BOOT ditahan 3 detik! Membuka Portal Konfigurasi...");
            _buttonPressStart = 0;
            startConfigPortal(false);
            return;
        }
    } else {
        _buttonPressStart = 0;
    }

    // 3. Auto-reconnect non-blocking jika koneksi WiFi terputus
    if (WiFi.status() != WL_CONNECTED) {
        unsigned long currentMillis = millis();
        if (currentMillis - _lastReconnectAttempt >= RECONNECT_INTERVAL) {
            _lastReconnectAttempt = currentMillis;
            Serial.println("[WiFi Manager] Koneksi WiFi terputus. Mencoba menghubungkan kembali...");
            WiFi.disconnect();
            WiFi.begin(_ssid.c_str(), _password.c_str());
        }
    }
}

bool WifiManagerCustom::isConnected() {
    return (WiFi.status() == WL_CONNECTED);
}

void WifiManagerCustom::startConfigPortal(bool autoTriggered) {
    _inPortalMode = true;

    WiFi.disconnect(true);
    delay(100);
    WiFi.mode(WIFI_AP_STA); // Mode AP_STA agar tetap bisa memindai jaringan WiFi sekitar

    // Pindai jaringan WiFi sekitar
    Serial.println("[Portal] Memindai jaringan WiFi sekitar...");
    int networkCount = WiFi.scanNetworks();
    Serial.printf("[Portal] Ditemukan %d jaringan WiFi.\n", networkCount);

    // Buat nama AP unik berdasarkan 4 digit terakhir MAC Address
    String mac = WiFi.macAddress();
    mac.replace(":", "");
    String apSSID = "ESP-SMS-" + mac.substring(mac.length() - 4);

    IPAddress apIP(192, 168, 4, 1);
    IPAddress netMsk(255, 255, 255, 0);
    WiFi.softAPConfig(apIP, apIP, netMsk);
    WiFi.softAP(apSSID.c_str());

    Serial.println("\n=======================================================");
    Serial.println("         🔥 WEB CONFIGURATION PORTAL AKTIF 🔥         ");
    Serial.println("=======================================================");
    Serial.printf(" 1. Hubungkan HP / Laptop ke WiFi : %s\n", apSSID.c_str());
    Serial.println(" 2. Buka browser dan ketik alamat : http://192.168.4.1");
    Serial.println(" 3. Isi form WiFi, Device Token, dan URL Server");
    Serial.println("=======================================================\n");

    // Start DNS Server (Captive Portal Redirect)
    _dnsServer.start(53, "*", apIP);

    // Setup HTTP Web Routes
    setupWebRoutes(networkCount);

    _server.begin();
    Serial.println("[Portal] HTTP Server & Captive Portal DNS siap menerima koneksi.");

    // Configuration Portal Main Loop (berjalan sampai user menyimpan config dan ESP me-restart)
    while (_inPortalMode) {
        _dnsServer.processNextRequest();
        _server.handleClient();
        delay(10);
    }
}

void WifiManagerCustom::setupWebRoutes(int networkCount) {
    // Route Halaman Utama Portal
    _server.on("/", HTTP_GET, [this, networkCount]() {
        _server.send(200, "text/html", generatePortalHtml(networkCount));
    });

    // Handler Simpan Konfigurasi (POST /save)
    _server.on("/save", HTTP_POST, [this]() {
        String newSsid = _server.arg("ssid");
        String newPass = _server.arg("password");
        String newUrl  = _server.arg("api_url");
        String newToken = _server.arg("token");

        newSsid.trim();
        newPass.trim();
        newUrl.trim();
        newToken.trim();

        if (newSsid.length() == 0 || newUrl.length() == 0 || newToken.length() == 0) {
            String errHtml = generateSuccessHtml("Gagal! SSID WiFi, URL Server, dan Device Token tidak boleh kosong.", false);
            _server.send(400, "text/html", errHtml);
            return;
        }

        saveToPreferences(newSsid, newPass, newUrl, newToken);

        Serial.println("\n[Portal] Konfigurasi Baru Berhasil Disimpan:");
        Serial.printf(" - SSID        : %s\n", newSsid.c_str());
        Serial.printf(" - URL Server  : %s\n", newUrl.c_str());
        Serial.printf(" - Device Token: %s\n", newToken.c_str());
        Serial.println("[Portal] Memulai restart ESP32 dalam 2 detik...");

        String succHtml = generateSuccessHtml("Konfigurasi Berhasil Disimpan! ESP32 akan restart dan terhubung ke modem...", true);
        _server.send(200, "text/html", succHtml);

        delay(1800);
        ESP.restart();
    });

    // Handler Reset ke Pengaturan Awal (POST /reset)
    _server.on("/reset", HTTP_POST, [this]() {
        resetConfig();
        String resp = generateSuccessHtml("Perangkat berhasil di-reset ke pengaturan default. Restarting...", true);
        _server.send(200, "text/html", resp);
        delay(1800);
        ESP.restart();
    });

    // Captive Portal Redirection Routes untuk Android, iOS, Windows, macOS
    _server.on("/generate_204", HTTP_GET, [this, networkCount]() { _server.send(200, "text/html", generatePortalHtml(networkCount)); });
    _server.on("/gen_204", HTTP_GET, [this, networkCount]() { _server.send(200, "text/html", generatePortalHtml(networkCount)); });
    _server.on("/hotspot-detect.html", HTTP_GET, [this, networkCount]() { _server.send(200, "text/html", generatePortalHtml(networkCount)); });
    _server.on("/canonical.html", HTTP_GET, [this, networkCount]() { _server.send(200, "text/html", generatePortalHtml(networkCount)); });
    _server.on("/ncsi.txt", HTTP_GET, [this]() { _server.send(200, "text/plain", "Microsoft NCSI"); });
    _server.on("/connecttest.txt", HTTP_GET, [this, networkCount]() { _server.send(200, "text/html", generatePortalHtml(networkCount)); });
    _server.on("/redirect", HTTP_GET, [this, networkCount]() { _server.send(200, "text/html", generatePortalHtml(networkCount)); });

    // Wildcard 404 handler -> Redirect ke halaman root portal
    _server.onNotFound([this]() {
        _server.sendHeader("Location", "http://192.168.4.1/", true);
        _server.send(302, "text/plain", "");
    });
}

String WifiManagerCustom::generatePortalHtml(int networkCount) {
    String mac = WiFi.macAddress();
    
    String html = "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'>";
    html += "<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'>";
    html += "<title>SMS Gateway Setup</title>";
    html += "<style>";
    html += "*{box-sizing:border-box;margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;}";
    html += "body{background:#0b1120;color:#f1f5f9;display:flex;justify-content:center;align-items:center;min-height:100vh;padding:16px;}";
    html += ".card{background:#1e293b;border:1px solid #334155;border-radius:16px;max-width:440px;width:100%;padding:24px;box-shadow:0 20px 25px -5px rgba(0,0,0,0.5);}";
    html += ".header{text-align:center;margin-bottom:20px;}";
    html += ".header h1{font-size:20px;font-weight:700;color:#38bdf8;margin-bottom:4px;}";
    html += ".header p{font-size:13px;color:#94a3b8;}";
    html += ".badge-box{display:flex;justify-content:center;gap:8px;margin-top:10px;flex-wrap:wrap;}";
    html += ".badge{background:#0f172a;border:1px solid #334155;color:#38bdf8;font-size:11px;font-weight:600;padding:4px 10px;border-radius:999px;}";
    html += ".form-group{margin-bottom:16px;}";
    html += "label{display:block;font-size:12px;font-weight:600;color:#cbd5e1;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;}";
    html += "input,select{width:100%;padding:10px 14px;background:#0f172a;border:1px solid #334155;border-radius:8px;color:#fff;font-size:14px;outline:none;transition:border-color .2s;}";
    html += "input:focus,select:focus{border-color:#38bdf8;}";
    html += ".input-desc{font-size:11px;color:#64748b;margin-top:4px;line-height:1.4;}";
    html += ".pw-wrapper{position:relative;}";
    html += ".pw-toggle{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;font-size:12px;padding:4px;}";
    html += ".btn{width:100%;padding:12px;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;transition:background .2s;display:flex;justify-content:center;align-items:center;gap:8px;}";
    html += ".btn-primary{background:#0284c7;color:#fff;margin-top:8px;}";
    html += ".btn-primary:hover{background:#0369a1;}";
    html += ".btn-danger{background:transparent;border:1px solid #ef4444;color:#ef4444;margin-top:12px;font-size:12px;padding:8px;}";
    html += ".btn-danger:hover{background:rgba(239,68,68,0.1);}";
    html += ".divider{height:1px;background:#334155;margin:20px 0;}";
    html += ".hint{background:rgba(56,189,248,0.08);border-left:3px solid #38bdf8;padding:10px 12px;border-radius:4px;font-size:12px;color:#bae6fd;margin-bottom:16px;line-height:1.4;}";
    html += "</style></head><body>";

    html += "<div class='card'>";
    html += "<div class='header'>";
    html += "<h1>📡 SMS Gateway Setup</h1>";
    html += "<p>Tautkan Modem WiFi & Hubungkan ke Web Dashboard</p>";
    html += "<div class='badge-box'>";
    html += "<span class='badge'>ESP32-S3</span>";
    html += "<span class='badge'>MAC: " + mac + "</span>";
    html += "</div>";
    html += "</div>";

    html += "<div class='hint'>💡 Masukkan kredensial WiFi modem dan Token dari Web Dashboard Laravel. ESP32 akan menyimpannya secara permanen.</div>";

    html += "<form method='POST' action='/save'>";
    
    // Pilihan WiFi Scanned
    html += "<div class='form-group'>";
    html += "<label for='wifi_select'>Pilih Jaringan WiFi / Modem</label>";
    html += "<select id='wifi_select' onchange='selectSSID(this.value)'>";
    html += "<option value=''>-- Pilih SSID dari daftar pemindaian --</option>";
    for (int i = 0; i < networkCount; ++i) {
        String ssidItem = WiFi.SSID(i);
        int rssi = WiFi.RSSI(i);
        int quality = (rssi <= -100) ? 0 : (rssi >= -50 ? 100 : 2 * (rssi + 100));
        String lock = (WiFi.encryptionType(i) == WIFI_AUTH_OPEN) ? "🔓" : "🔒";
        
        String selected = (ssidItem == _ssid) ? " selected" : "";
        html += "<option value='" + ssidItem + "'" + selected + ">" + lock + " " + ssidItem + " (" + String(quality) + "%)</option>";
    }
    html += "<option value='__MANUAL__'>[+ Masukkan SSID Manual / Tersembunyi]</option>";
    html += "</select>";
    html += "</div>";

    // Input SSID Manual / Text
    html += "<div class='form-group' id='manual_ssid_group'>";
    html += "<label for='ssid'>Nama SSID WiFi</label>";
    html += "<input type='text' id='ssid' name='ssid' value='" + _ssid + "' placeholder='Contoh: MyModem_WiFi' required>";
    html += "</div>";

    // Input Password WiFi
    html += "<div class='form-group'>";
    html += "<label for='password'>Password WiFi</label>";
    html += "<div class='pw-wrapper'>";
    html += "<input type='password' id='password' name='password' value='" + _password + "' placeholder='Kosongkan jika Open WiFi'>";
    html += "<button type='button' class='pw-toggle' onclick='togglePw()'>👁️</button>";
    html += "</div>";
    html += "</div>";

    html += "<div class='divider'></div>";

    // Input API URL Server
    html += "<div class='form-group'>";
    html += "<label for='api_url'>URL Server API Laravel</label>";
    html += "<input type='text' id='api_url' name='api_url' value='" + _apiUrl + "' placeholder='http://192.168.1.50:8000' required>";
    html += "<div class='input-desc'>Alamat IP / Domain Backend (Contoh: http://192.168.1.100:8000 atau https://sms.domain.com)</div>";
    html += "</div>";

    // Input Device Token
    html += "<div class='form-group'>";
    html += "<label for='token'>Device Token</label>";
    html += "<input type='text' id='token' name='token' value='" + _deviceToken + "' placeholder='ESP32_SECRET_TOKEN...' required>";
    html += "<div class='input-desc'>Token autentikasi yang dibuat dari halaman <strong>Devices</strong> pada Web Dashboard.</div>";
    html += "</div>";

    html += "<button type='submit' class='btn btn-primary' id='btnSubmit'>💾 Simpan & Hubungkan</button>";
    html += "</form>";

    // Form Factory Reset
    html += "<form method='POST' action='/reset' onsubmit='return confirm(\"Apakah Anda yakin ingin menghapus semua pengaturan di memori ESP32?\");'>";
    html += "<button type='submit' class='btn btn-danger'>⚠️ Reset ke Pengaturan Awal</button>";
    html += "</form>";

    html += "</div>";

    // JavaScript Frontend
    html += "<script>";
    html += "function selectSSID(val){";
    html += "  var ssidInput = document.getElementById('ssid');";
    html += "  if(val && val !== '__MANUAL__'){ ssidInput.value = val; document.getElementById('password').focus(); }";
    html += "  else if(val === '__MANUAL__'){ ssidInput.value = ''; ssidInput.focus(); }";
    html += "}";
    html += "function togglePw(){";
    html += "  var pw = document.getElementById('password');";
    html += "  pw.type = (pw.type === 'password') ? 'text' : 'password';";
    html += "}";
    html += "document.querySelector('form').addEventListener('submit', function(){";
    html += "  var btn = document.getElementById('btnSubmit');";
    html += "  btn.innerHTML = '⏳ Menyimpan...';";
    html += "  btn.style.opacity = '0.7';";
    html += "});";
    html += "</script>";

    html += "</body></html>";

    return html;
}

String WifiManagerCustom::generateSuccessHtml(String message, bool restart) {
    String html = "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'>";
    html += "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
    html += "<title>Status Pengaturan</title>";
    html += "<style>";
    html += "*{box-sizing:border-box;margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;}";
    html += "body{background:#0b1120;color:#f1f5f9;display:flex;justify-content:center;align-items:center;min-height:100vh;padding:16px;}";
    html += ".card{background:#1e293b;border:1px solid #334155;border-radius:16px;max-width:400px;width:100%;padding:32px;text-align:center;box-shadow:0 20px 25px -5px rgba(0,0,0,0.5);}";
    html += ".icon{font-size:48px;margin-bottom:16px;}";
    html += "h1{font-size:18px;font-weight:700;color:" + String(restart ? "#38bdf8" : "#ef4444") + ";margin-bottom:8px;}";
    html += "p{font-size:14px;color:#94a3b8;line-height:1.5;margin-bottom:20px;}";
    html += ".spinner{width:36px;height:36px;border:3px solid #334155;border-top-color:#38bdf8;border-radius:50%;animation:spin 1s linear infinite;margin:0 auto 16px;}";
    html += "@keyframes spin{to{transform:rotate(360deg);}}";
    html += "</style></head><body>";
    html += "<div class='card'>";
    html += "<div class='icon'>" + String(restart ? "✅" : "⚠️") + "</div>";
    html += "<h1>" + String(restart ? "Berhasil Disimpan!" : "Peringatan") + "</h1>";
    html += "<p>" + message + "</p>";
    if (restart) {
        html += "<div class='spinner'></div>";
        html += "<p style='font-size:12px;color:#64748b;'>ESP32 sedang me-restart dan menghubungkan ke jaringan...</p>";
    } else {
        html += "<a href='/' style='color:#38bdf8;text-decoration:none;font-size:14px;font-weight:600;'>← Kembali ke Form Setup</a>";
    }
    html += "</div></body></html>";
    return html;
}
