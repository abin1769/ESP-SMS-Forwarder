# SMS Gateway ESP32 + SIM800L

## Overview

Sistem ini berfungsi sebagai SMS Gateway berbasis ESP32 + SIM800L.

ESP32 menerima SMS dari SIM800L kemudian mengirimkannya ke Laravel melalui REST API.

Laravel menyimpan SMS ke database dan menampilkan dashboard.

---

# Architecture

```
HP
        │
        ▼
SIM800L
        │ UART
        ▼
ESP32-S3
        │ HTTP
        ▼
Laravel API
        │
        ▼
MySQL
        │
        ▼
Dashboard
```

---

# Goals V1

✅ Receive SMS

✅ Save Database

✅ Dashboard

❌ Reply SMS (Next)

❌ Send SMS (Next)

❌ SD Card Backup (Next)

---

# Database

## devices

| field |
|------|
id

name

token

status

last_seen

created_at

updated_at

---

## sms

| field |
|------|
id

device_id

phone

message

received_at

processed

created_at

updated_at

---

# API

## POST

/api/device/heartbeat

ESP32 mengirim heartbeat setiap 30 detik.

Body

```json
{
"token":"DEVICE_TOKEN",
"signal":27,
"operator":"TELKOMSEL"
}
```

Response

```json
{
"success":true
}
```

---

## POST

/api/sms

Body

```json
{
"token":"DEVICE_TOKEN",
"phone":"+628123456789",
"message":"Halo Dunia",
"received_at":"2026-08-06 22:51:00"
}
```

Response

```json
{
"success":true
}
```

---

## GET

/api/sms

Return seluruh SMS.

---

## GET

/api/device

Status device.

---

# Dashboard

Sidebar

Dashboard

SMS

Devices

Settings

---

Dashboard

Device Status

Operator

Signal

Last Seen

Jumlah SMS Hari Ini

Grafik SMS

---

SMS Page

Table

No

Phone

Message

Received At

Action

Search

Pagination

---

# Device Authentication

Setiap ESP memiliki token unik.

Header atau JSON

```
DEVICE_TOKEN
```

Laravel akan mengecek token sebelum menerima data.

---

# Future Features

Reply SMS

Send SMS

Multiple Devices

Export CSV

Webhook

Realtime Notification

Telegram Notification

WhatsApp Notification

SD Card Sync

MQTT

OTA Update

GPS Tracking

---

# ESP32 Workflow

```
Boot

↓

Connect WiFi

↓

SIM800L Init

↓

Wait SMS

↓

+CMTI

↓

AT+CMGR=index

↓

Parse

↓

POST API

↓

Success

↓

Delete SMS

↓

Wait Again
```

---

# Error Handling

Jika API gagal

↓

Jangan delete SMS

↓

Retry nanti

---

# Coding Standard

Laravel 12

PHP 8.3

Service Pattern

Repository tidak digunakan dulu

API Resource

Validation Request

RESTful

PSR-12

---

# Project Tree

```
app/

Controllers/
Api/
SmsController.php
DeviceController.php

Models/
Sms.php
Device.php

Services/
SmsService.php

database/

migrations/

routes/

api.php

resources/views/
dashboard.blade.php
sms/index.blade.php

```
