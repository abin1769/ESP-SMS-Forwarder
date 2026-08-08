# SMS Gateway Backend (ESP32 + SIM800L) - Laravel 12

Sistem Backend SMS Gateway berbasis **Laravel 12 (PHP 8.3)** & **Bootstrap 5** yang dirancang untuk menerima, menyimpan, dan mengelola SMS dari modul **ESP32 + SIM800L** melalui REST API yang aman dan terstruktur.

---

## 📌 Fitur Utama

1. **Device Authentication**: Keamanan API berbasis **Device Token** (HTTP Header `X-Device-Token` / Bearer / JSON Body).
2. **REST API Gateway**: Endpoint standar untuk Heartbeat status perangkat (`POST /api/device/heartbeat`), Respon AT Command (`POST /api/device/command-response`), dan Pengiriman SMS Masuk (`POST /api/sms`).
3. **Clean Service Architecture**: Pemisahan logika bisnis dari Controller menggunakan **Service Layer** (`DeviceService`, `SmsService`).
4. **Form Request Validation**: Validasi request ketat untuk API & Web.
5. **Pemantauan Status SIM Card & Jaringan Realtime**:
   - Status Kartu SIM: `READY` (Terpasang & Siap) / `UNKNOWN` / `SIM PIN`
   - Registrasi Sinyal Jaringan Seluler (`AT+CREG?`): Deteksi otomatis saat terhubung ke provider `+CREG: 0,1` (Registered Home) / `+CREG: 0,5` (Registered Roaming) / `+CREG: 0,2` (Searching)
   - Kuat Sinyal SIM800L (CSQ 0-31, estimasi dBm & Kualitas Sinyal: Sangat Baik / Baik / Cukup / Lemah)
   - Operator Seluler (AT+COPS?)
   - Last Seen Activity Tracker
6. **Penarikan Pesan dari Memori SIM (SIM Storage Pull / Sync)**:
   - Tombol **"Tarik Pesan dari SIM"** pada Web Dashboard & Device Console
   - Fitur otomatis penarikan seluruh SMS tersimpan di memori SIM (`AT+CMGL="ALL"`) saat modul pertama kali terhubung ke jaringan (`+CREG: 0,1`) maupun melalui trigger jarak jauh dari Web
7. **Interactive GSM Live Console & AT Terminal**:
   - Eksekusi AT Command secara remote dari Web ke ESP32 + SIM800L
   - Preset AT Command instan: `SYNC_SIM_SMS`, `AT+CMGL="ALL"`, `AT+CPMS?`, `AT+CREG?`, `AT+CSQ`, `AT+CPIN?`, `AT+COPS?`, `AT+CBC`
8. **Manajemen Data SMS**: Table SMS dengan fitur pencarian live, filter per device, filter status diproses, dan pagination Bootstrap 5.
9. **CRUD Devices**: Manajemen perangkat ESP32, penambahan device, update status, hapus, dan tombol **Regenerate Token**.

---

## 📂 Folder & Architecture Structure

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── DeviceController.php     # Endpoint GET /api/device, POST /api/device/heartbeat & /api/device/command-response
│   │   │   └── SmsController.php        # Endpoint GET /api/sms & POST /api/sms
│   │   └── Web/
│   │       ├── DashboardController.php  # Controller halaman utama Dashboard
│   │       ├── DeviceController.php     # CRUD Perangkat ESP32, syncSimSms & Live AT Console
│   │       └── SmsController.php        # List, Detail, Toggle, Delete SMS & syncFromSim
│   ├── Middleware/
│   │   └── EnsureValidDeviceToken.php   # Otorisasi token device pada API
│   ├── Requests/
│   │   ├── Api/
│   │   │   ├── DeviceHeartbeatRequest.php
│   │   │   └── StoreSmsRequest.php
│   │   └── Web/
│   │       ├── StoreDeviceRequest.php
│   │       └── UpdateDeviceRequest.php
│   └── Resources/
│       ├── DeviceResource.php           # API Transformer untuk Device
│       └── SmsResource.php              # API Transformer untuk SMS
├── Models/
│   ├── Device.php                       # Model Device & Relasi
│   └── Sms.php                          # Model SMS, Scope Search & Filter
└── Services/
    ├── DeviceService.php                # Service Logic Device, Heartbeat & AT Queue
    └── SmsService.php                   # Service Logic SMS & Statistics

src/
└── main.cpp                             # Firmware ESP32-S3: State Machine, SMS Detection, SIM SMS Sync & AT Console

lib/
├── GSMManager/                          # Driver SIM800L: Registrasi Jaringan (+CREG), Status SIM (+CPIN), Sinyal (CSQ), Sync SIM (CMGL)
├── ApiClient/                           # HTTP Client ESP32 untuk REST API Laravel
└── WifiManagerCustom/                   # Pengelola koneksi & auto-reconnect WiFi
```


database/
├── factories/
│   ├── DeviceFactory.php
│   └── SmsFactory.php
├── migrations/
│   ├── 2026_08_07_000001_create_devices_table.php
│   └── 2026_08_07_000002_create_sms_table.php
└── seeders/
    ├── DatabaseSeeder.php
    ├── DeviceSeeder.php
    └── SmsSeeder.php

resources/views/
├── layouts/
│   └── app.blade.php                    # Master Layout Bootstrap 5 + Sidebar
├── dashboard.blade.php                  # Dashboard Statistik & Chart
├── devices/
│   └── index.blade.php                  # CRUD Devices & Modal Token
└── sms/
    └── index.blade.php                  # Inbox SMS, Search, Filter & Modal Detail

routes/
├── api.php                              # REST API Routes
└── web.php                              # Web Dashboard Routes
```

---

## 🛠️ Instalasi & Konfigurasi

### 1. Requirements
- **PHP 8.3** atau lebih baru
- **Composer 2.x**
- **MySQL** / MariaDB / SQLite

### 2. Langkah Instalasi

```bash
# Clone repository & masuk ke direktori
git clone <repository_url>
cd SMS_Forwarder

# Install Dependensi Composer
composer install

# Salin environment file & setting Database
cp .env.example .env

# Generate Application Key
php artisan key:generate
```

### 3. Konfigurasi Database `.env` (MySQL)

Ubah file `.env` sesuai kredensial MySQL Anda:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sms_gateway
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Jalankan Migration & Seeder

```bash
# Jalankan migration database beserta data seeder awal
php artisan migrate:fresh --seed
```

### 5. Jalankan Local Server

```bash
php artisan serve
```

Buka browser dan akses: `http://127.0.0.1:8000`

---

## 🔌 Dokumentasi REST API (ESP32 Gateway)

### 1. Heartbeat Device (`POST /api/device/heartbeat`)

Dikirimkan oleh ESP32 secara berkala (misal setiap 30-60 detik) untuk mengabarkan status sinyal & operator.

- **Headers**:
  - `Content-Type: application/json`
  - `X-Device-Token: <TOKEN_DEVICE>` *(atau kirim token dalam body JSON)*

- **Request Body**:
```json
{
  "token": "ESP32_DEFAULT_SECRET_TOKEN_12345",
  "signal": 27,
  "operator": "TELKOMSEL"
}
```

- **Response Success (200 OK)**:
```json
{
  "success": true,
  "message": "Heartbeat received successfully.",
  "data": {
    "id": 1,
    "name": "ESP32-Gateway-Node01",
    "token": "ESP32_DEFAULT_SECRET_TOKEN_12345",
    "status": "online",
    "is_online": true,
    "signal": 27,
    "operator": "TELKOMSEL",
    "last_seen": "2026-08-07 09:00:00",
    "last_seen_human": "1 second ago"
  }
}
```

---

### 2. Forward Incoming SMS (`POST /api/sms`)

Dikirimkan oleh ESP32 saat menerima SMS baru dari modul SIM800L.

- **Request Body**:
```json
{
  "token": "ESP32_DEFAULT_SECRET_TOKEN_12345",
  "phone": "+628123456789",
  "message": "Pesan SMS uji coba dari SIM800L",
  "received_at": "2026-08-07 09:15:00"
}
```

- **Response Success (201 Created)**:
```json
{
  "success": true,
  "message": "SMS saved successfully.",
  "data": {
    "id": 101,
    "device_id": 1,
    "device_name": "ESP32-Gateway-Node01",
    "phone": "+628123456789",
    "message": "Pesan SMS uji coba dari SIM800L",
    "received_at": "2026-08-07 09:15:00",
    "processed": false
  }
}
```

---

### 3. Get All Devices (`GET /api/device`)

- **Response (200 OK)**:
```json
{
  "data": [
    {
      "id": 1,
      "name": "ESP32-Gateway-Node01",
      "token": "ESP32_DEFAULT_SECRET_TOKEN_12345",
      "status": "online",
      "is_online": true,
      "signal": 27,
      "operator": "TELKOMSEL",
      "last_seen": "2026-08-07 09:15:00"
    }
  ]
}
```

---

### 4. Get All SMS (`GET /api/sms`)

- **Query Parameters**:
  - `search`: Kata kunci nomor HP / isi pesan / nama device.
  - `device_id`: Filter ID device.
  - `processed`: `1` (Sudah diproses) atau `0` (Belum diproses).
  - `per_page`: Jumlah record (default 15).

---

## 🧪 Testing

Jalankan pengujian otomatis PHPUnit:

```bash
php artisan test
```

---

## 📝 License
Proyek ini dikembangkan dengan lisensi MIT.
