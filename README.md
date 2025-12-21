<div align="center">

# 🌍 SIAPKAK

<img src="https://res.cloudinary.com/drgwsncdn/image/upload/v1764506518/logo_kuck1i.png" alt="SIAPKAK Logo" width="200"/>

### Sistem Informasi Air Pollution Kampus Area Karawang

*Real-time Air Quality Monitoring System for Campus Environment*

[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=flat&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=flat&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-CSS-38B2AC?style=flat&logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

**SIAPKAK** adalah aplikasi web monitoring kualitas udara real-time yang dirancang khusus untuk kampus area Karawang. Sistem ini mengintegrasikan data dari **World Air Quality Index (AQICN)** untuk memberikan informasi terkini mengenai kondisi udara, dilengkapi dengan notifikasi otomatis via email ketika kualitas udara mencapai tingkat berbahaya.

[🚀 Demo](#-instalasi) • [📖 Dokumentasi](#-daftar-isi) • [🎯 Fitur](#-fitur-unggulan) • [📸 Screenshots](#-mockup-aplikasi)

</div>

---

## 📋 Daftar Isi

1. [Pengenalan Aplikasi](#-pengenalan-aplikasi)
2. [Fitur Unggulan](#-fitur-unggulan)
3. [Spesifikasi Teknis](#-spesifikasi-teknis)
4. [Screenshots](#-screenshots)
5. [Tutorial Instalasi](#-tutorial-instalasi)
6. [Struktur Aplikasi](#-struktur-aplikasi)
7. [Mockup Aplikasi](#-mockup-aplikasi)
8. [API Documentation](#-api-documentation)
9. [Troubleshooting](#-troubleshooting)
10. [Lisensi & Kontribusi](#-lisensi--kontribusi)

---

## 🌟 Pengenalan Aplikasi

### Tentang SIAPKAK

**SIAPKAK (Sistem Informasi Air Pollution Kampus Area Karawang)** adalah solusi monitoring kualitas udara berbasis web yang dikembangkan untuk membantu kampus dan institusi pendidikan di Karawang memantau kondisi udara secara real-time. 

### Latar Belakang

Dengan meningkatnya polusi udara di area industri Karawang, penting bagi institusi pendidikan untuk:
- 📊 Memantau kualitas udara secara berkelanjutan
- ⚠️ Memberikan peringatan dini kepada civitas akademika
- 📈 Menganalisis tren polusi untuk pengambilan keputusan
- 🏥 Melindungi kesehatan mahasiswa dan staf

### Mengapa SIAPKAK?

- ✅ **Real-time Monitoring**: Data kualitas udara ter-update setiap jam
- ✅ **Integrasi AQICN**: Koneksi langsung dengan World Air Quality Index
- ✅ **Email Notification**: Alert otomatis saat AQI berbahaya (≥150)
- ✅ **User-Friendly**: Interface intuitif dan responsif
- ✅ **Unified Dashboard**: Overview & Analytics dalam satu halaman
- ✅ **Multi-Platform**: Akses dari desktop, tablet, dan mobile

---

## 📸 Screenshots

### Desktop View
<div align="center">
  <img src="https://res.cloudinary.com/drgwsncdn/image/upload/v1764513871/desktop-final_qqsiss.png" alt="SIAPKAK Desktop Dashboard" width="100%"/>
  <p><em>Dashboard Overview & Analytics - Desktop View (1920×1080)</em></p>
</div>

### Mobile View
<div align="center">
  <img src="https://res.cloudinary.com/drgwsncdn/image/upload/v1764513868/mobile-final_bhnep9.png" alt="SIAPKAK Mobile Dashboard" width="400"/>
  <p><em>Dashboard Overview & Analytics - Mobile View (390×844)</em></p>
</div>

---

## 🎯 Fitur Unggulan

### 🔐 Autentikasi & Keamanan
- Login/Register dengan enkripsi password (bcrypt)
- Session-based authentication
- Role-based access control (Admin/User)
- **Granular Menu Permissions**: Admin dapat mengatur akses menu per user
- Change password functionality

### 📍 Manajemen Stasiun Monitoring
- CRUD stasiun monitoring dengan AJAX
- GPS coordinate mapping
- Status monitoring real-time
- Integration dengan AQICN API

### 📊 Dashboard Overview & Analytics
- **Unified Dashboard**: Overview dan Analytics dalam satu halaman
- **Statistics Cards**: Total Stasiun, Udara Baik, Udara Sedang, Tidak Sehat
- **Time Series Chart**: Tren AQI 7 hari terakhir (Database + AQICN API)
- **Pollutants Bar Chart**: Konsentrasi PM2.5, PM10, O₃, NO₂, SO₂, CO
- **AQI Status Pie Chart**: Distribusi kategori kualitas udara
- **Interactive Map**: Leaflet.js dengan heatmap visualization
- **Station Markers**: Color-coded berdasarkan level AQI
- **Sync Button**: Sinkronisasi real-time dengan AQICN API

### ⭐ My Favorite Stations (User-only)
- Toggle favorit via ikon ⭐ di halaman Stasiun
- Widget "My Favorite Stations" di Dashboard (hanya tampil untuk role `user`)
- Detail stasiun favorit via modal, termasuk AQI & PM2.5 terbaru
- API endpoints:
  - `GET /api/favorites` → daftar favorit user
  - `GET /api/favorites/with-readings?limit=5` → favorit + data AQI terbaru
  - `POST /api/favorites/toggle` → tambah/hapus favorit
- Role-based access: fitur ini ditolak untuk `admin` (403), hanya `user` yang bisa akses

### 📧 Sistem Notifikasi
- **Email Alerts**: Otomatis terkirim saat AQI ≥ 150
- **Professional Templates**: HTML email dengan branding SIAPKAK
- **Gmail SMTP**: Reliable email delivery
- **Multi-User**: Notifikasi ke semua user aktif

### 📈 Data Kualitas Udara
Tracking parameter lengkap:
- **AQI** (Air Quality Index)
- **PM2.5** & **PM10** (Particulate Matter)
- **O₃** (Ozone)
- **NO₂** (Nitrogen Dioxide)
- **SO₂** (Sulfur Dioxide)
- **CO** (Carbon Monoxide)

### 🗺️ Peta Interaktif
- Leaflet.js mapping dengan custom markers
- Color-coded berdasarkan AQI level
- Popup info dengan station details
- Heatmap overlay untuk visualisasi polusi

### 📱 Reports & Export
- Generate PDF reports
- Excel/CSV export
- Custom date range filtering
- Station-specific reports

---

## 💻 Spesifikasi Teknis

### Technology Stack

| Kategori | Teknologi | Versi | Deskripsi |
|----------|-----------|-------|-----------|
| **Backend** | PHP Native | 7.4+ | Object-Oriented Programming |
| **Database** | MySQL | 8.0+ | Relational Database |
| **Frontend** | HTML5 + CSS3 | - | Semantic markup |
| **CSS Framework** | Tailwind CSS | 2.2+ | Utility-first styling |
| **JavaScript** | jQuery | 3.6+ | DOM manipulation & AJAX |
| **Charts** | Chart.js | 3.9.1 | Data visualization |
| **Maps** | Leaflet.js | 1.9.4 | Interactive maps |
| **Heatmap** | Leaflet.heat | 0.2.0 | Heatmap layer |
| **Icons** | Font Awesome | 6.4.0 | Icon library |
| **Authentication** | Session + JWT | - | Hybrid auth system |
| **Email** | PHPMailer | 6.8+ | SMTP email client |
| **Environment** | PHP Dotenv | 5.5+ | Config management |
| **API Integration** | AQICN API | v2 | Air quality data |

### System Requirements

#### Server Requirements
- **Web Server**: Apache 2.4+ / Nginx
- **PHP**: 7.4 or higher
- **MySQL**: 8.0 or higher
- **Composer**: Latest version
- **Extensions**: 
  - `pdo_mysql`
  - `mbstring`
  - `openssl`
  - `curl`

#### Browser Requirements
- Chrome 90+ / Firefox 88+ / Safari 14+ / Edge 90+
- JavaScript enabled
- Cookies enabled

### Database Schema

**Tables:**
1. `users` - User accounts & authentication (role: admin/user)
2. `stations` - Air quality monitoring stations
3. `air_quality_readings` - Historical AQI and pollutants data
4. `user_favorite_stations` - Mapping user ↔ favorite station
5. `notifications` - User notifications
6. `push_subscriptions` - Web push subscriptions
7. `notification_logs` - Delivery tracking

### Architecture Pattern

```
MVC (Model-View-Controller) Architecture
├── Models: Database entities & business logic
├── Views: HTML templates & UI components
├── Controllers: Request handling & response
└── Config: Database, Auth, Router, API clients
```

---

## 🇮🇩 Sync Data Indonesia (AQICN)

SIAPKAK menyediakan 2 script otomatis untuk sinkronisasi data AQICN:

### 📂 File Scripts

| File | Fungsi | Frekuensi |
|------|--------|-----------|
| **`sync_indonesia_stations.php`** | Auto-discover & register stasiun monitoring di 100+ kota Indonesia | Manual / 1x seminggu |
| **`cron_sync_data.php`** | Sync data kualitas udara real-time dari AQICN API | Otomatis / Setiap jam |
| `database/clean_duplicates.php` | Maintenance - bersihkan data duplikat | Manual / Bulanan |

---

### 🔧 Script 1: Discovery Stasiun (`sync_indonesia_stations.php`)

Script ini akan mencari dan mendaftarkan stasiun monitoring AQICN di seluruh Indonesia.

#### Fitur:
- ✅ Coverage **100+ kota** Indonesia (Jakarta, Surabaya, Bandung, Karawang, Bekasi, dll)
- ✅ Auto-search via AQICN API `/search/?keyword=` endpoint
- ✅ Extract data: nama stasiun, koordinat GPS, aqicn_station_id
- ✅ Insert ke database dengan **duplicate prevention** (UNIQUE constraint)
- ✅ Rate limiting (1 detik per request)
- ✅ JSON summary output

#### Usage:

**Manual Run (Windows):**
```powershell
# PowerShell
C:\xampp\php\php.exe C:\xampp\htdocs\siapkak\sync_indonesia_stations.php

# CMD
C:\xampp\php\php.exe C:\xampp\htdocs\siapkak\sync_indonesia_stations.php
```

**Manual Run (Linux/Mac):**
```bash
php sync_indonesia_stations.php
```

**Expected Output:**
```json
{
    "success": true,
    "message": "Station discovery completed",
    "data": {
        "cities_searched": 107,
        "stations_found": 156,
        "stations_registered": 145,
        "stations_skipped": 11
    }
}
```

#### Waktu Eksekusi:
- **~3-5 menit** untuk 100+ kota
- Rate limiting: 1 detik/request = ~2 menit untuk API calls
- Database insert: ~1-2 menit

#### Kapan Dijalankan:
- **Setup awal**: 1x saat instalasi
- **Update berkala**: 1x per minggu (untuk stasiun baru)
- **Manual**: Setelah AQICN menambah stasiun baru

---

### ⏰ Script 2: Sync Data (`cron_sync_data.php`)

Script ini akan mengambil data kualitas udara real-time dari semua stasiun yang sudah terdaftar.

#### Fitur:
- ✅ Sync data AQI, PM2.5, PM10, O3, NO2, SO2, CO
- ✅ Calculate status (Baik, Sedang, Tidak Sehat, dll)
- ✅ Update `air_quality_readings` table
- ✅ Update `latest_aqi` & `latest_pm25` di tabel stations
- ✅ **Email alerts** otomatis saat AQI ≥ 150 (Tidak Sehat)
- ✅ Prevent duplicate readings (check by hour)
- ✅ Rate limiting (1 detik per stasiun)
- ✅ Comprehensive logging ke file & console

#### Usage:

**Manual Run (Windows):**
```powershell
# PowerShell
C:\xampp\php\php.exe C:\xampp\htdocs\siapkak\cron_sync_data.php

# CMD
C:\xampp\php\php.exe C:\xampp\htdocs\siapkak\cron_sync_data.php
```

**Manual Run (Linux/Mac):**
```bash
php cron_sync_data.php
```

**Expected Output:**
```
════════════════════════════════════════════════════════════════════════════════
🔄 AQICN DATA SYNC - Started at 2025-12-16 14:00:00
════════════════════════════════════════════════════════════════════════════════
📍 Found 10 active stations to sync

[1/10] Universitas Indonesia (UI) - Depok, Jawa Barat
   ✅ Synced: AQI 45 (Baik) - PM2.5: 28
[2/10] Universitas Gadjah Mada (UGM) - Yogyakarta
   ✅ Synced: AQI 52 (Sedang) - PM2.5: 33
[3/10] Institut Teknologi Bandung (ITB) - Bandung
   ✅ Synced: AQI 58 (Sedang) - PM2.5: 37
...

────────────────────────────────────────────────────────────────────────────────
📊 SYNC SUMMARY
────────────────────────────────────────────────────────────────────────────────
Total Stations:       10
✅ Successfully Synced: 9
❌ Failed:              1
⏭️  Skipped:             0
📧 Notifications Sent:  2

Success Rate: 90.00%
════════════════════════════════════════════════════════════════════════════════
✅ Sync completed at 2025-12-16 14:01:15
════════════════════════════════════════════════════════════════════════════════
```

#### Waktu Eksekusi:
- **~2-4 menit** untuk 145 stasiun
- Rate limiting: 1 detik/stasiun = ~2.5 menit
- Database operations: ~30-60 detik

---

### 🤖 Setup Cron Job (Otomatis)

#### Windows Task Scheduler (XAMPP):

1. **Buka Task Scheduler:**
   - Windows Key + R → `taskschd.msc` → Enter

2. **Create Basic Task:**
   - Name: `SIAPKAK Sync AQICN`
   - Description: `Hourly sync of air quality data from AQICN API`
   - Trigger: **Daily**
   - Start: **Today 00:00**
   - Recur every: **1 day**

3. **Action Settings:**
   - Action: **Start a program**
   - Program/script: `C:\xampp\php\php.exe`
   - Add arguments: `C:\xampp\htdocs\siapkak\cron_sync_data.php`
   - Start in: `C:\xampp\htdocs\siapkak`

4. **Advanced Settings:**
   - Right-click task → **Properties**
   - **Triggers** tab → **Edit**
   - ✅ **Repeat task every:** `1 hour`
   - ✅ **For a duration of:** `Indefinitely`
   - ✅ **Enabled**
   - **OK**

5. **Run Settings:**
   - **General** tab:
     - ✅ **Run whether user is logged on or not**
     - ✅ **Run with highest privileges**
   - **OK**

#### Linux/Mac Crontab:

```bash
# Edit crontab
crontab -e

# Add this line (sync setiap jam)
0 * * * * /usr/bin/php /path/to/siapkak/cron_sync_data.php

# Atau dengan logging
0 * * * * /usr/bin/php /path/to/siapkak/cron_sync_data.php >> /path/to/siapkak/storage/logs/cron.log 2>&1
```

**Schedule Options:**
```bash
# Setiap jam (jam 00 menit 00)
0 * * * * php cron_sync_data.php

# Setiap 30 menit
*/30 * * * * php cron_sync_data.php

# Setiap 15 menit
*/15 * * * * php cron_sync_data.php

# Setiap hari jam 6 pagi & 6 sore
0 6,18 * * * php cron_sync_data.php
```

---

### 📊 Monitoring & Maintenance

#### Cek Log File:

**Windows PowerShell:**
```powershell
# Lihat 50 baris terakhir
Get-Content C:\xampp\htdocs\siapkak\storage\logs\cron_sync.log -Tail 50

# Monitor real-time
Get-Content C:\xampp\htdocs\siapkak\storage\logs\cron_sync.log -Wait

# Cari error
Select-String -Path C:\xampp\htdocs\siapkak\storage\logs\cron_sync.log -Pattern "Error|Failed"
```

**Linux/Mac:**
```bash
# Lihat 50 baris terakhir
tail -f storage/logs/cron_sync.log

# Monitor real-time
tail -f storage/logs/cron_sync.log

# Cari error
grep -i "error\|failed" storage/logs/cron_sync.log
```

#### Cek Database:

```sql
-- Total stasiun terdaftar
SELECT COUNT(*) as total_stations 
FROM monitoring_stations;

-- Stasiun dengan external_id (siap sync)
SELECT COUNT(*) as ready_stations 
FROM monitoring_stations 
WHERE external_id IS NOT NULL;

-- Readings hari ini
SELECT COUNT(*) as today_readings 
FROM air_quality_readings 
WHERE DATE(measured_at) = CURDATE();

-- 10 Reading terakhir
SELECT 
    s.name, 
    s.location, 
    r.aqi_index, 
    r.status, 
    r.measured_at 
FROM air_quality_readings r
JOIN monitoring_stations s ON r.station_id = s.id
ORDER BY r.measured_at DESC 
LIMIT 10;

-- Stasiun dengan AQI tertinggi
SELECT 
    name, 
    location, 
    latest_aqi, 
    latest_pm25 
FROM monitoring_stations 
WHERE latest_aqi IS NOT NULL 
ORDER BY latest_aqi DESC 
LIMIT 10;
```

#### Clean Duplicate Data (Maintenance):

```powershell
# Jalankan manual sebulan sekali
C:\xampp\php\php.exe C:\xampp\htdocs\siapkak\database\clean_duplicates.php
```

---

### 🔍 Troubleshooting Sync

#### Error: "API Key not configured"
```bash
# Solusi: Pastikan .env sudah ada AQICN_API_KEY
echo %AQICN_API_KEY%  # Windows CMD
```

#### Error: "No stations to sync"
```bash
# Solusi: Jalankan discovery script dulu
php sync_indonesia_stations.php
```

#### Error: HTTP 429 (Too Many Requests)
```bash
# Solusi: Rate limit dari AQICN
# 1. Tunggu 5-10 menit
# 2. Jalankan ulang
# 3. Increase sleep delay di script (saat ini 1 detik)
```

#### Success Rate < 80%
```bash
# Possible causes:
# 1. Network timeout
# 2. AQICN API down/maintenance
# 3. Stasiun offline/tidak ada data
# 4. Database connection issue

# Cek log untuk detail error
Get-Content storage/logs/cron_sync.log -Tail 100
```

#### Email Alerts Tidak Terkirim
```env
# Solusi: Pastikan email settings benar di .env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password  # Bukan password Gmail biasa!

# Enable Gmail App Password:
# 1. Google Account → Security
# 2. 2-Step Verification (enable)
# 3. App passwords → Generate
# 4. Copy 16-digit code → paste ke .env
```

---

## 🚀 Tutorial Instalasi

### Langkah 1: Persiapan Environment

#### A. Install XAMPP
1. Download XAMPP dari [https://www.apachefriends.org](https://www.apachefriends.org)
2. Install dengan default settings
3. Start **Apache** dan **MySQL** dari XAMPP Control Panel

#### B. Install Composer
1. Download dari [https://getcomposer.org/download/](https://getcomposer.org/download/)
2. Jalankan installer
3. Verify installation:
   ```bash
   composer --version
   ```

### Langkah 2: Setup Project

#### Clone atau Extract Project
```bash
# Navigate ke htdocs
cd C:\xampp\htdocs

# Pastikan folder siapkak sudah ada
# Struktur: C:\xampp\htdocs\siapkak\
```

#### Install Dependencies
```bash
cd siapkak
composer install
```

### Langkah 3: Konfigurasi Environment

#### Copy Environment File
```bash
# Windows PowerShell
Copy-Item .env.example .env

# Windows CMD
copy .env.example .env
```

#### Edit File `.env`
Buka file `.env` dengan text editor dan sesuaikan:

```env
# ===================================
# DATABASE CONFIGURATION
# ===================================
DB_HOST=localhost
DB_PORT=3306
DB_NAME=siapkak
DB_USER=root
DB_PASSWORD=

# ===================================
# APPLICATION CONFIGURATION
# ===================================
APP_NAME=SIAPKAK
APP_URL=http://localhost/siapkak
APP_ENV=development

# ===================================
# JWT AUTHENTICATION
# ===================================
JWT_SECRET=your_secret_key_here_change_this
JWT_EXPIRY=86400

# ===================================
# AQICN API INTEGRATION
# ===================================
AQICN_API_KEY=e39d43e33231723538a7d4bf8cfa650433223bc3
AQICN_CACHE_TTL=1800
AQICN_DEFAULT_LOCATION=jakarta

# ===================================
# EMAIL NOTIFICATION (Gmail SMTP)
# ===================================
MAIL_PROVIDER=gmail
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=laporsiapkak@gmail.com
MAIL_PASSWORD=your_gmail_app_password
MAIL_FROM=laporsiapkak@gmail.com
MAIL_FROM_NAME="SIAPKAK Alert System"

# ===================================
# TIMEZONE
# ===================================
TIMEZONE=Asia/Jakarta
```

> **⚠️ Security Note**: Ubah `JWT_SECRET` untuk production! Generate dengan:
> ```powershell
> [Convert]::ToBase64String((1..32|%{Get-Random -Max 256}))
> ```

### Langkah 4: Setup Database

#### Opsi A: Import Database Dump ⭐⭐⭐ (Recommended - Paling Cepat!)

**Untuk setup instan dengan data demo lengkap!**

1. Buka `http://localhost/phpmyadmin`
2. Klik **"New"** → Database name: `siapkak`
3. Collation: `utf8mb4_unicode_ci`
4. Klik **"Create"**
5. Select database `siapkak`
6. Tab **"Import"**
7. Choose file: `siapkak.sql` (ada di root folder project)
8. Klik **"Go"**
9. **Selesai!** Database siap dengan tabel + data demo (users, stations, menus, permissions, dll.)

**Keuntungan**: Tidak perlu migration manual atau seeding terpisah.

> Catatan: Gunakan dump terbaru [siapkak.sql](siapkak.sql) yang sudah termasuk tabel `stations`, `air_quality_readings`, dan `user_favorite_stations` agar fitur Favorites berfungsi tanpa setup tambahan.

#### Opsi B: Web Migration Tool

**Jika tidak ingin menggunakan dump atau ingin setup incremental.**

1. Buka browser
2. Akses: `http://localhost/siapkak/public/migrate.php`
3. Klik tombol **"▶️ Run Database Migration"**
4. Tunggu hingga selesai (✅ 4/4 statements successful)
5. Database siap digunakan!

**Seed Data (Optional - Tambahkan data demo):**
```bash
# PowerShell
C:\xampp\php\php.exe database\seed.php

# CMD
C:\xampp\php\php.exe database/seed.php
```

#### Opsi C: Command Line

```bash
# 1. Buat database
mysql -u root -p
CREATE DATABASE siapkak CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# 2. Jalankan migration
php database/migrate.php

# 3. Seed data demo (optional)
php database/seed.php
```

#### Opsi D: PHPMyAdmin (Migration Only)

1. Buka `http://localhost/phpmyadmin`
2. Klik **"New"** → Database name: `siapkak`
3. Collation: `utf8mb4_unicode_ci`
4. Klik **"Create"**
5. Select database `siapkak`
6. Tab **"Import"**
7. Choose file: `database/migration.sql`
8. Klik **"Go"**

### Langkah 5: Sinkronisasi Data AQICN

#### Setup Awal - Daftar Stasiun Indonesia
```bash
# Discover & register semua stasiun Indonesia dari AQICN
php sync_indonesia_stations.php
# Output: ~100+ stasiun dari berbagai kota (Jakarta, Surabaya, Bandung, dll)
# Durasi: 3-5 menit

# Sync data kualitas udara untuk semua stasiun
php cron_sync_data.php
```

#### Setup Cron Job (Auto-sync setiap jam)

**Windows Task Scheduler:**
1. Buka **Task Scheduler**
2. **Create Basic Task**
   - Name: `SIAPKAK Sync AQICN`
   - Trigger: **Daily**
   - Recur every: **1 day**
   - Action: **Start a program**
3. Program/script: `C:\xampp\php\php.exe`
4. Add arguments: `C:\xampp\htdocs\siapkak\cron_sync_data.php`
5. **Finish** → Right-click task → **Properties**
6. Triggers tab → **Edit** → **Repeat task every**: `1 hour`
7. Duration: `Indefinitely`

### Langkah 6: Jalankan Aplikasi

#### Akses via Browser
- **URL**: `http://localhost/siapkak`
- **Login Page**: Langsung redirect ke login

#### Demo Credentials
```
📧 Email: admin@siapkak.local
🔑 Password: password123
👤 Role: Administrator

📧 Email: john@siapkak.local
🔑 Password: password123
👤 Role: User

📧 Email: jane@siapkak.local
🔑 Password: password123
👤 Role: User

#### Role-Based Feature Visibility
- Widget "My Favorite Stations" dan tombol ⭐ hanya tampil untuk role `user`
- API `/api/favorites/*` akan menolak akses dari `admin` (403 Forbidden)
- Admin tetap memiliki akses penuh ke manajemen data & menu permissions
```

### Langkah 7: Konfigurasi Virtual Host (Optional)

Untuk akses via domain custom (e.g., `siapkak.local`):

#### Edit Apache Configuration
File: `C:\xampp\apache\conf\extra\httpd-vhosts.conf`
```apache
<VirtualHost *:80>
    ServerName siapkak.local
    DocumentRoot "C:/xampp/htdocs/siapkak/public"
    
    <Directory "C:/xampp/htdocs/siapkak/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog "logs/siapkak-error.log"
    CustomLog "logs/siapkak-access.log" common
</VirtualHost>
```

#### Edit Hosts File
File: `C:\Windows\System32\drivers\etc\hosts` (Run as Administrator)
```
127.0.0.1 siapkak.local
```

#### Restart Apache
```bash
# Via XAMPP Control Panel: Stop → Start Apache
```

Akses: `http://siapkak.local`

### ✅ Checklist Instalasi Berhasil

- [ ] XAMPP Apache & MySQL running
- [ ] Composer dependencies installed
- [ ] File `.env` sudah dikonfigurasi
- [ ] Database `siapkak` created
- [ ] Tables migrated (4/4 successful)
- [ ] Seed data loaded (optional)
- [ ] AQICN stasiun Indonesia synced
- [ ] Login berhasil dengan demo credentials
- [ ] Dashboard tampil dengan data

---

## 📁 Struktur Aplikasi

### Overview Folder Structure

```
siapkak/
│
├── 📂 public/                      # Public-facing files (Document Root)
│   ├── index.php                   # Entry point & router
│   ├── dashboard.php               # Main dashboard
│   ├── login.php                   # Login page
│   ├── stations.php                # Station management
│   ├── readings.php                # Readings management
│   ├── analytics.php               # Analytics & charts
│   ├── reports.php                 # Report generation
│   ├── settings.php                # User settings
│   ├── users.php                   # User management (Admin)
│   ├── menus.php                   # Menu management (Admin)
│   ├── .htaccess                   # Apache URL rewrite
│   │
│   ├── 📂 auth/                    # Authentication endpoints
│   │   ├── login.php               # Login handler
│   │   ├── logout.php              # Logout handler
│   │   └── check.php               # Session validator
│   │
│   ├── 📂 css/                     # Stylesheets
│   │   └── styles.css              # Custom CSS
│   │
│   ├── 📂 js/                      # JavaScript files
│   │   ├── main.js                 # Core functionality
│   │   ├── ajax.js                 # AJAX handlers
│   │   ├── charts.js               # Chart configurations
│   │   └── push.js                 # Push notifications
│   │
│   └── 📂 img/                     # Images & assets
│       └── logo.png                # SIAPKAK logo
│
├── 📂 src/                         # Application source code
│   │
│   ├── 📂 config/                  # Configuration classes
│   │   ├── Database.php            # PDO database connection
│   │   ├── Auth.php                # JWT authentication
│   │   ├── Env.php                 # Environment loader
│   │   ├── Response.php            # JSON response helper
│   │   ├── Router.php              # Request router
│   │   ├── ApiClientAqicn.php      # AQICN API client
│   │   ├── EmailNotification.php   # Email service (PHPMailer)
│   │   └── PushNotification.php    # Web Push service
│   │
│   ├── 📂 models/                  # Database models (OOP)
│   │   ├── User.php                # User CRUD operations
│   │   ├── Station.php             # Station CRUD
│   │   ├── AirQualityReading.php   # Readings CRUD
│   │   ├── FavoriteStation.php     # User favorites CRUD
│   │   └── Notification.php        # Notifications CRUD
│   │
│   ├── 📂 controllers/             # Business logic controllers
│   │   ├── AuthController.php      # Authentication logic
│   │   ├── StationController.php   # Station management
│   │   ├── ReadingController.php   # Readings management
│   │   ├── AnalyticsController.php # Analytics & charts
│   │   ├── FavoriteStationController.php # Favorites API (user-only)
│   │   ├── ReportController.php    # Report generation
│   │   ├── NotificationController.php # Notification system
│   │   └── UserManagementController.php # User admin
│   │
│   ├── 📂 middlewares/             # Request middlewares
│   │   └── AuthMiddleware.php      # Authentication guard
│   │
│   └── 📂 views/                   # Reusable view components
│       ├── header.php              # Page header
│       ├── footer.php              # Page footer
│       ├── sidebar.php             # Navigation sidebar
│       ├── breadcrumb.php          # Breadcrumb navigation
│       └── modals.php              # Modal components
│
├── 📂 database/                    # Database management
│   ├── migrate.php                 # Migration runner
│   ├── seed.php                    # Data seeding
│   ├── migration.sql               # SQL schema
│   ├── add_external_id_column.php  # Add AQICN ID column
│   └── clean_duplicates.php        # Remove duplicate data
│
├── 📂 storage/                     # Storage directory
│   ├── 📂 cache/                   # API cache files
│   │   └── aqicn_*.json            # AQICN cached responses
│   └── 📂 logs/                    # Application logs
│       ├── cron_sync.log           # Cron sync logs
│       └── error.log               # Error logs
│
├── 📂 vendor/                      # Composer dependencies
│   ├── autoload.php                # Composer autoloader
│   ├── firebase/php-jwt/           # JWT library
│   ├── phpmailer/phpmailer/        # Email library
│   ├── phpoffice/phpspreadsheet/   # Excel/PDF library
│   └── vlucas/phpdotenv/           # Environment loader
│
├── 📂 docs/                        # Documentation
│   └── (empty - cleaned)
│
├── 📄 composer.json                # Composer dependencies
├── 📄 composer.lock                # Locked versions
├── 📄 .env.example                 # Environment template
├── 📄 .env                         # Environment config (git-ignored)
├── 📄 .gitignore                   # Git ignore rules
├── 📄 README.md                    # This file
├── 📄 cron_sync_data.php           # Cron job: sync AQICN data
└── 📄 sync_indonesia_stations.php  # Discover Indonesia stations
```

### Key Directories Explained

#### 📂 `/public` - Web Root
Entry point untuk semua HTTP requests. Berisi:
- **Pages**: Dashboard, Analytics, Reports, Settings
- **Auth**: Login/Logout handlers
- **Assets**: CSS, JS, Images

#### 📂 `/src` - Application Core
Core business logic dengan arsitektur MVC:
- **config/**: Singleton classes untuk database, auth, API
- **models/**: Database entities dengan CRUD methods
- **controllers/**: Request handling & response generation
- **middlewares/**: Authentication guards
- **views/**: Reusable PHP templates

#### 📂 `/database` - Schema & Migration
Database management tools:
- **migrate.php**: Create tables dari migration.sql
- **seed.php**: Insert demo data
- **Maintenance scripts**: Clean duplicates, add columns

#### 📂 `/storage` - Runtime Data
Runtime storage untuk:
- **cache/**: AQICN API responses (TTL: 30 minutes)
- **logs/**: Cron sync logs & error logs

#### 📂 `/vendor` - Dependencies
Composer-managed third-party libraries

### File Naming Conventions

| Type | Pattern | Example |
|------|---------|---------|
| **Controllers** | `{Name}Controller.php` | `AuthController.php` |
| **Models** | `{Entity}.php` | `User.php` |
| **Pages** | `{name}.php` | `dashboard.php` |
| **Config** | `{Name}.php` | `Database.php` |
| **Middleware** | `{Name}Middleware.php` | `AuthMiddleware.php` |

### Data Flow Architecture

```
┌─────────────┐
│   Browser   │
└──────┬──────┘
       │ HTTP Request
       ↓
┌─────────────┐
│ public/     │ ← Entry Point (.htaccess → index.php)
│ index.php   │
└──────┬──────┘
       │ Route to Controller
       ↓
┌─────────────┐
│ Middleware  │ ← Authentication Check
│ Auth Guard  │
└──────┬──────┘
       │ Authorized
       ↓
┌─────────────┐
│ Controller  │ ← Business Logic
│ (src/...)   │
└──────┬──────┘
       │ Query Database
       ↓
┌─────────────┐
│   Model     │ ← Database Operations (CRUD)
│ (src/...)   │
└──────┬──────┘
       │ Return Data
       ↓
┌─────────────┐
│   View      │ ← Render Response (JSON/HTML)
│ or JSON     │
└──────┬──────┘
       │ HTTP Response
       ↓
┌─────────────┐
│   Browser   │ ← Display to User
└─────────────┘
```

---

## 📸 Mockup Aplikasi

### 🖥️ Desktop View

<img src="https://res.cloudinary.com/drgwsncdn/image/upload/v1764513871/desktop-final_qqsiss.png" alt="SIAPKAK Desktop" width="1500"/>
```



### 📱 Mobile View

<img src="https://res.cloudinary.com/drgwsncdn/image/upload/v1764513868/mobile-final_bhnep9.png" alt="SIAPKAK Mobile" width="500"/>
```

### 🎨 Color Scheme

**AQI Status Colors:**
- 🟢 **Baik** (0-50): `#10b981` (Green)
- 🟡 **Sedang** (51-100): `#f59e0b` (Yellow)
- 🟠 **Tidak Sehat Sensitif** (101-150): `#f97316` (Orange)
- 🔴 **Tidak Sehat** (151-200): `#ef4444` (Red)
- 🟣 **Sangat Tidak Sehat** (201-300): `#a855f7` (Purple)
- ⚫ **Berbahaya** (300+): `#7c2d12` (Maroon)

**Brand Colors:**
- **Primary**: `#2563eb` (Blue)
- **Secondary**: `#1e40af` (Dark Blue)
- **Success**: `#10b981` (Green)
- **Warning**: `#f59e0b` (Orange)
- **Danger**: `#ef4444` (Red)

---

## 📡 API Documentation

### Authentication Endpoints

#### Register
```
POST /api/auth/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123"
}

Response: 201 Created
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "user": { "id": 1, "name": "John Doe", "email": "john@example.com", "role": "user" },
    "token": "eyJhbGc..."
  }
}
```

#### Login
```
POST /api/auth/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}

Response: 200 OK
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": { ... },
    "token": "eyJhbGc..."
  }
}
```

#### Get Current User
```
GET /api/auth/me
Authorization: Bearer <token>

Response: 200 OK
{
  "success": true,
  "message": "Success",
  "data": { ... }
}
```

### Stations Endpoints (CRUD)

#### Get All Stations
```
GET /api/stations?limit=10&page=1

Response: 200 OK
{
  "success": true,
  "data": {
    "stations": [
      {
        "id": 1,
        "name": "Stasiun Area Gedung A",
        "location": "Karawang",
        "latitude": -6.3088,
        "longitude": 107.2865,
        "latest_aqi": 75,
        "latest_pm25": 22.1,
        ...
      }
    ],
    "pagination": { "total": 3, "page": 1, "limit": 10, "pages": 1 }
  }
}
```

#### Get Single Station
```
GET /api/stations/show?id=1

Response: 200 OK
{
  "success": true,
  "data": {
    "station": { ... },
    "latest_reading": { ... }
  }
}
```

#### Create Station (Auth Required)
```
POST /api/stations
Authorization: Bearer <token>
Content-Type: application/json

{
  "name": "Stasiun Baru",
  "location": "Karawang",
  "latitude": -6.3100,
  "longitude": 107.2880,
  "description": "Deskripsi stasiun"
}

Response: 201 Created
```

#### Update Station (Auth Required)
```
PUT /api/stations/update?id=1
Authorization: Bearer <token>
Content-Type: application/json

{
  "name": "Stasiun Update",
  "location": "Karawang Updated",
  "latitude": -6.3100,
  "longitude": 107.2880
}

Response: 200 OK
```

#### Delete Station (Auth Required)
```
DELETE /api/stations/delete?id=1
Authorization: Bearer <token>

Response: 200 OK
```

### Readings Endpoints (CRUD)

#### Get All Readings
```
GET /api/readings?limit=50&page=1

Response: 200 OK
{
  "success": true,
  "data": {
    "readings": [
      {
        "id": 1,
        "station_id": 1,
        "aqi_index": 75,
        "pm25": 22.1,
        "pm10": 35.8,
        "o3": 30,
        "no2": 20,
        "so2": 10,
        "co": 0.8,
        "status": "Sedang",
        "source_api": "aqicn",
        "measured_at": "2024-01-15 10:00:00"
      }
    ],
    "pagination": { ... }
  }
}
```

#### Get Readings by Station
```
GET /api/readings/by-station?station_id=1&limit=24&page=1

Response: 200 OK
```

#### Get Trend Data
```
GET /api/readings/trend?station_id=1&hours=24

Response: 200 OK
{
  "success": true,
  "data": {
    "station": { ... },
    "trend": [
      {
        "hour": "2024-01-15 10:00:00",
        "avg_aqi": 75,
        "avg_pm25": 22.1,
        "avg_pm10": 35.8,
        "max_aqi": 85,
        "min_aqi": 65
      }
    ],
    "hours": 24
  }
}
```

#### Create Reading (Auth Required)
```
POST /api/readings
Authorization: Bearer <token>
Content-Type: application/json

{
  "station_id": 1,
  "aqi_index": 75,
  "pm25": 22.1,
  "pm10": 35.8,
  "o3": 30,
  "no2": 20,
  "so2": 10,
  "co": 0.8,
  "status": "Sedang",
  "source_api": "manual"
}

Response: 201 Created
```

#### Sync Data from AQICN API (Auth Required)
```
POST /api/readings/sync-aqicn
Authorization: Bearer <token>
Content-Type: application/json

{
  "station_id": 1,
  "latitude": -6.3088,
  "longitude": 107.2865
}

Response: 201 Created
{
  "success": true,
  "data": {
    "aqi": 75,
    "status": "Sedang",
    "pm25": 22.1,
    "pm10": 35.8,
    "source": "aqicn"
  }
}
```

## 📊 Seeding Data

Project ini sudah include sample data:

### Users (Demo)
| Email | Password | Role |
|-------|----------|------|
| admin@siapkak.local | password123 | admin |
| john@siapkak.local | password123 | user |
| jane@siapkak.local | password123 | user |

### Stations
- 3 stasiun monitoring di area kampus Karawang

### Readings
- 8+ sample readings dengan berbagai nilai AQI (Baik, Sedang, Tidak Sehat)

## 🔐 Security Notes

- ✅ Password di-hash dengan `password_hash()` dan diverifikasi dengan `password_verify()`
- ✅ JWT token dengan secret key yang aman
- ✅ API key disimpan di `.env` (tidak di-commit ke git)
- ✅ CORS headers untuk API requests
- ✅ Input validation di setiap endpoint
- ✅ Prepared statements untuk mencegah SQL injection
- ✅ Granular menu permissions untuk kontrol akses per user

### User Menu Permission System

SIAPKAK dilengkapi dengan sistem permission granular yang memungkinkan admin mengatur akses menu untuk setiap user. Fitur ini memberikan kontrol akses yang lebih detail dibanding hanya role admin/user.

#### Fitur Permission System:
- **Admin**: Selalu memiliki akses ke semua menu
- **User Biasa**: Akses menu dikontrol oleh admin melalui User Management
- **Default Permission**: User baru hanya dapat akses Dashboard
- **Real-time Update**: Perubahan permission langsung berlaku tanpa logout

#### Cara Menggunakan:
1. Login sebagai admin
2. Buka menu **User Management**
3. Klik icon **key (🔑)** pada user yang ingin dikelola
4. Centang/uncheck menu yang ingin diberikan/dicabut aksesnya
5. Klik **Save Permissions**

#### API Endpoints:
```
GET /api/users/menu-access?user_id={id}    # Get user permissions
PUT /api/users/menu-access                  # Update permissions
```

Dokumentasi lengkap: [docs/USER_PERMISSIONS.md](docs/USER_PERMISSIONS.md)

---

## 🐛 Troubleshooting

### Database Connection Error
**Symptoms**: "Database connection error" / "SQLSTATE[HY000]"

**Solutions**:
1. Pastikan MySQL running di XAMPP Control Panel
2. Check credentials di `.env`:
   ```env
   DB_HOST=localhost
   DB_PORT=3306
   DB_USER=root
   DB_PASSWORD=
   ```
3. Verify database exists:
   ```bash
   mysql -u root -e "SHOW DATABASES LIKE 'siapkak';"
   ```
4. Test connection:
   ```bash
   mysql -u root -e "USE siapkak; SHOW TABLES;"
   ```

### Class Not Found Error
**Symptoms**: "Class 'App\Config\Database' not found"

**Solutions**:
1. Regenerate Composer autoload:
   ```bash
   composer dump-autoload
   ```
2. Clear cache:
   ```bash
   rm -rf storage/cache/*
   ```
3. Verify `vendor/autoload.php` exists

### API Request Failed
**Symptoms**: "No valid AQI data available" / "API request failed"

**Solutions**:
1. Check AQICN API key di `.env`:
   ```env
   AQICN_API_KEY=your_key_here
   ```
2. Test API manually:
   ```bash
   curl "https://api.waqi.info/feed/jakarta/?token=YOUR_API_KEY"
   ```
3. Verify internet connection
4. Check coordinate format (latitude, longitude)
5. Clear API cache:
   ```bash
   rm storage/cache/aqicn_*.json
   ```

### .env File Not Found
**Symptoms**: "File not found: .env"

**Solutions**:
1. Copy template:
   ```bash
   copy .env.example .env
   ```
2. Verify file exists:
   ```bash
   dir .env
   ```
3. Check file permissions (read access)

### Migration Failed
**Symptoms**: "Migration failed" / "Table already exists"

**Solutions**:
1. **Drop & recreate database**:
   ```sql
   DROP DATABASE siapkak;
   CREATE DATABASE siapkak CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
2. Run migration again:
   ```bash
   php database/migrate.php
   ```
3. Or use web tool: `http://localhost/siapkak/public/migrate.php`

### Email Not Sending
**Symptoms**: Email alerts tidak terkirim

**Solutions**:
1. Check SMTP credentials di `.env`:
   ```env
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=465
   MAIL_USERNAME=your_email@gmail.com
   MAIL_PASSWORD=your_app_password
   ```
2. Generate Gmail App Password:
   - Go to: https://myaccount.google.com/apppasswords
   - Create new app password
   - Update `.env` with generated password
3. Test email manually:
   ```bash
   php -r "echo mail('test@example.com', 'Test', 'Body') ? 'OK' : 'FAIL';"
   ```
4. Check logs:
   ```bash
   Get-Content storage/logs/email.log -Tail 50
   ```

### Session/Login Issues
**Symptoms**: "Unauthorized" / Cannot login / Session expired

**Solutions**:
1. Clear browser cookies for `localhost`
2. Check session directory writable:
   ```bash
   php -r "echo session_save_path();"
   ```
3. Verify JWT secret in `.env`:
   ```env
   JWT_SECRET=your_secret_key
   ```
4. Check session timeout (default 24h):
   ```env
   JWT_EXPIRY=86400
   ```

### CORS / API Access Denied
**Symptoms**: "Access-Control-Allow-Origin" error

**Solutions**:
1. Enable CORS headers di `public/index.php`:
   ```php
   header('Access-Control-Allow-Origin: *');
   header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
   ```
2. Check `.htaccess` configuration
3. Verify Apache `mod_headers` enabled

### Apache Rewrite Not Working
**Symptoms**: "404 Not Found" on clean URLs

**Solutions**:
1. Enable `mod_rewrite`:
   - Edit: `c:\xampp\apache\conf\httpd.conf`
   - Uncomment: `LoadModule rewrite_module modules/mod_rewrite.so`
   - Restart Apache
2. Verify `.htaccess` exists in `public/`
3. Check `AllowOverride All` in Apache config

### High Memory Usage
**Symptoms**: Slow performance / Out of memory

**Solutions**:
1. Increase PHP memory limit:
   ```ini
   ; php.ini
   memory_limit = 256M
   ```
2. Clear cache:
   ```bash
   rm storage/cache/*.json
   ```
3. Optimize queries (add indexes)
4. Reduce AQICN sync frequency

### Duplicate Data in Database
**Symptoms**: Same station/reading appears multiple times

**Solutions**:
1. Run cleanup script:
   ```bash
   php database/clean_duplicates.php
   ```
2. Apply unique constraints:
   ```bash
   php database/apply_unique_constraint.php
   ```
3. Check cron job not running too frequently

---

## 📚 API Documentation

### Authentication Endpoints

#### Register
```http
POST /api/auth/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123"
}
```

**Response**: 201 Created
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "user"
    },
    "token": "eyJhbGc..."
  }
}
```

#### Login
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response**: 200 OK
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": { ... },
    "token": "eyJhbGc..."
  }
}
```

### Stations Endpoints

#### Get All Stations
```http
GET /api/stations?limit=10&page=1
```

**Response**: 200 OK
```json
{
  "success": true,
  "data": {
    "stations": [
      {
        "id": 1,
        "name": "Stasiun Gedung A",
        "location": "Karawang",
        "latitude": -6.3088,
        "longitude": 107.2865,
        "latest_aqi": 75,
        "latest_pm25": 22.1
      }
    ],
    "pagination": {
      "total": 3,
      "page": 1,
      "limit": 10,
      "pages": 1
    }
  }
}
```

#### Create Station
```http
POST /api/stations
Authorization: Bearer <token>
Content-Type: application/json

{
  "name": "Stasiun Baru",
  "location": "Karawang",
  "latitude": -6.3100,
  "longitude": 107.2880,
  "description": "Deskripsi stasiun"
}
```

### Readings Endpoints

#### Get All Readings
```http
GET /api/readings?limit=50&page=1
```

#### Sync from AQICN
```http
POST /api/readings/sync-aqicn
Authorization: Bearer <token>
Content-Type: application/json

{
  "station_id": 1,
  "latitude": -6.3088,
  "longitude": 107.2865
}
```

**Response**: 201 Created
```json
{
  "success": true,
  "data": {
    "aqi": 75,
    "status": "Sedang",
    "pm25": 22.1,
    "pm10": 35.8,
    "source": "aqicn",
    "notification_sent": true
  },
  "message": "Data synced from AQICN API and notification sent"
}
```

---

## 🤝 Lisensi & Kontribusi

### 📄 License

MIT License © 2024 SIAPKAK Team

```
Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
```

### 👨‍🎓 Academic Purpose

Project ini dibuat untuk memenuhi tugas mata kuliah:
- **Basis Data** - Database design & SQL
- **Pemrograman Web** - Full-stack web development
- **Analisis & Desain Berorientasi Objek** - OOP analysis & design
- **Pemrograman Berorientasi Objek** - OOP implementation

### 🤝 Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

### 📧 Contact & Support

- **Email**: laporsiapkak@gmail.com
- **GitHub**: [github.com/siapkak](https://github.com/siapkak)
- **Issues**: [Report Bug](https://github.com/siapkak/siapkak/issues)

### 🙏 Acknowledgments

- **AQICN** - Air Quality Index API
- **Chart.js** - Data visualization
- **Leaflet.js** - Interactive maps
- **Tailwind CSS** - UI framework
- **PHPMailer** - Email delivery
- **Composer** - Dependency management

---

## 📝 Changelog

### Version 2.0 - Dashboard Unification (November 2025)
- ✨ **Merged Overview & Analytics**: Dashboard sekarang menampilkan Overview dan Analytics dalam satu halaman
- 🗑️ **Removed**: Halaman Analytics terpisah dihapus
- 📊 **Enhanced Dashboard**: 
  - Statistics cards (Total Stasiun, Udara Baik, Sedang, Tidak Sehat)
  - Bar Chart untuk Pollutants (PM2.5, PM10, O₃, NO₂, SO₂, CO)
  - Pie Chart untuk distribusi status AQI
  - Interactive Map dengan Heatmap layer
  - Toggle Heatmap dan Refresh buttons
- 🎯 **Improved UX**: Menu lebih sederhana, Dashboard sebagai single item (bukan dropdown)
- 📱 **Mobile Responsive**: Semua charts dan maps dioptimasi untuk mobile view
- 🖼️ **Updated Screenshots**: Desktop dan mobile mockups terbaru

---

<div align="center">

### 🌍 Made with ❤️ for Clean Air

**SIAPKAK** - Monitoring Kualitas Udara untuk Indonesia Lebih Sehat

[⬆ Back to Top](#-siapkak)

---

**Happy Monitoring! 📊🌱**

*Last Updated: November 30, 2025*

</div>
