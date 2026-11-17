# FaceAbsen - Sistem Absensi Sekolah Berbasis AI Wajah & Asisten Cerdas Multi-Provider
---
##Laporan PDF (AHMAD FIROSY FIJRI)

https://drive.google.com/file/d/1ZXkCUYtBu9zuDzbYSli46N6AGjoEb78X/view?usp=sharing

---
## Identitas Pengembang
<p> Nama  : AHMAD FIROSY FIJRI </p>
<p> KELAS : TI 2025 1D </p>

---

Aplikasi web presensi sekolah modern yang dirancang untuk mendukung pencatatan kehadiran menggunakan kamera smartphone/laptop dengan integrasi model kecerdasan buatan Google Teachable Machine di **SMK BINAWARGA LEMAHABANG**. Dibangun menggunakan arsitektur MVC PHP Native berkinerja tinggi, aman, dan tanpa dependensi framework yang berlebihan, serta dilengkapi dengan microservice Asisten AI Chatbot dan WhatsApp Gateway.

---

## 🚀 Fitur Unggulan Sistem

### 1. 🎯 Presensi Wajah AI Real-time (Biometrik Wajah)
* **Live Camera Detection**: Mendukung kamera depan smartphone, webcam laptop, maupun kamera eksternal dengan resolusi adaptif via `navigator.mediaDevices.getUserMedia`.
* **Model Google Teachable Machine**: Klasifikasi citra wajah secara real-time frame-demi-frame menggunakan pustaka TensorFlow.js (`@teachablemachine/image`).
* **Visual & Audio Feedback**: Bounding status interaktif, progress bar confidence score real-time, hitung mundur otomatis (*countdown*), dan efek suara sambutan saat presensi berhasil.
* **Ambang Batas Dinamis**: Akurasi minimal (*confidence score*) dapat diatur fleksibel melalui database (default: `80%`).
* **Aturan Bisnis Presensi**:
  * Otomatis menentukan status **Hadir** atau **Terlambat** berdasarkan batas jam masuk sekolah (default batas keterlambatan: `07:15:00`).
  * **Pencegahan Absensi Ganda**: Memanfaatkan *unique database constraint* (`user_id` + `attendance_date`) sehingga siswa/guru tidak dapat melakukan presensi lebih dari satu kali pada hari yang sama.

### 2. 🤖 Asisten AI Chatbot Sekolah (Multi-Provider AI)
* **Floating Chat Widget**: Asisten virtual interaktif yang selalu dapat diakses di pojok kanan bawah dashboard seluruh civitas sekolah.
* **Akses Data Sekolah Real-time (Read-Only)**: Chatbot dapat menjawab pertanyaan seputar rekapitulasi kehadiran harian, persentase kehadiran kelas, informasi rombel, data kontak civitas, dan jadwal piket guru secara instan.
* **Dukungan Multi-Provider AI**:
  * **Google Gemini API**: Model `gemini-2.5-flash` dan `gemini-3.5-flash` untuk pemrosesan konteks tinggi dan penalaran cepat.
  * **NVIDIA NIM API**: Model `deepseek-ai/deepseek-v4.1-flash`, `meta/llama-3.2-11b-vision-instruct`, dan ragam model open-source terkemuka.
* **Proteksi Anti-Koding (Strict Non-Coding Guard)**: Asisten diproteksi secara ketat dari *prompt injection* terkait pembuatan atau pembahasan kode pemrograman, menjaga fokus asisten murni pada kebutuhan informasi edukasi dan manajerial sekolah.
* **Dual-Engine Architecture**: Berjalan pada microservice background Node.js (port `3001`) berlatensi rendah dengan *graceful fallback* ke engine PHP Native jika microservice tidak aktif.

### 3. 🔑 Manajemen & Konfigurasi API Key (Super User)
* **Pusat Pengelolaan Multi-Key**: Administrator dapat mendaftarkan, menyimpan, dan melabeli beberapa API Key cadangan/alternatif dari berbagai penyedia (Google Gemini, NVIDIA NIM).
* **1-Click Active Key Switch**: Pergantian kunci aktif secara instan tanpa perlu memodifikasi kode atau me-restart server. Sistem secara otomatis menyinkronkan konfigurasi ke tabel `settings` dan mencatat jejak audit.
* **Uji Koneksi Langsung (Smart Live Test)**: Tombol pengujian koneksi real-time yang memvalidasi kunci ke server penyedia terkait, mengukur waktu latensi respons (ms), dan menampilkan daftar model AI yang aktif (tersedia hingga 50-85 model siap pakai).
* **Keamanan Nilai Kunci**: Nilai API Key otomatis disensor (*masked*) pada tampilan tabel (misal `nvapi-••••4hkehE` atau `AQ.••••Vlg`), dilengkapi tombol *Reveal/Hide* dan tombol *Copy to Clipboard* dengan fallback lingkungan localhost.
* **Self-Healing Auto-Migration**: Model otomatis memeriksa dan membuat tabel `api_keys` beserta data seed default jika tabel belum tersedia di database.

### 4. 👥 Manajemen Master Data Terpadu (CRUD & Email)
* **Peserta Didik (Siswa)**:
  * Manajemen data lengkap: Nama, NIS, Rombel/Kelas, No. HP, Face Model Identifier, dan **Alamat Email**.
  * Filter daftar berdasarkan kelas dan pencarian instan (nama, NIS, atau email).
  * Tampilan profil detail siswa lengkap dengan tautan kontak langsung (`mailto:` dan `tel:`).
* **Tenaga Pendidik (Guru)**:
  * Manajemen data lengkap: Nama, NIP, No. HP, dan **Alamat Email**.
  * Tampilan kartu profil guru dengan ringkasan tugas dan kontak resmi.
* **Atribut Email Terpusat & Unik**:
  * Email disimpan terpusat di tabel `users` (`users.email VARCHAR(100) UNIQUE`), menjamin keunikan global di seluruh sistem dan mencegah bentrokan alamat email antarpengguna.
  * Validasi server-side ketat: Wajib diisi (`required`), format standar (`FILTER_VALIDATE_EMAIL`), dan cek keunikan otomatis.
* **Rombongan Belajar (Kelas)**:
  * Manajemen kelas dan tingkatan (Kelas 10, 11, 12).
  * Penghitungan otomatis jumlah siswa terdaftar per rombel.
  * **Safe Deletion Protection**: Proteksi integritas relasi yang secara otomatis menolak penghapusan kelas jika masih terdapat siswa aktif di dalamnya.
* **Integritas Relasi Atomik**: Seluruh proses penambahan dan pembaruan akun menggunakan transaksi database atomik (`beginTransaction`, `commit`, `rollBack`).

### 5. 📲 WhatsApp Gateway & Broadcast Pengumuman (Fonnte API)
* **Kirim Pengumuman Resmi**: Terintegrasi langsung dengan WhatsApp Gateway melalui Fonnte API untuk menyampaikan pesan penting sekolah secara cepat.
* **Segmentasi Penerima Pesan**:
  * Seluruh Guru / Tenaga Pendidik.
  * Seluruh Siswa / Peserta Didik.
  * Rombel / Kelas Tertentu (misal khusus kelas XII).
  * Nomor WhatsApp Tertentu (Pesan Khusus).
* **Template Pesan Cerdas**: Mendukung placeholder otomatis seperti `{nama}`, `{sekolah}`, dan `{tanggal}` yang akan digantikan secara dinamis sesuai data penerima.
* **Riwayat Pesan (Outbox Log)**: Tabel riwayat pesan terkirim lengkap dengan detail target, jumlah penerima, cuplikan pesan, respons gateway, dan status pengiriman (`sent`, `partial`, `failed`).

### 6. 📊 Laporan, Rekapitulasi Presensi & Cetak Resmi
* **Filter Rekapitulasi Fleksibel**: Menyaring rekaman presensi berdasarkan rentang tanggal harian, rekapitulasi bulanan, rombongan belajar, dan status kehadiran (**Hadir**, **Terlambat**, **Izin**, **Sakit**, **Alpa**).
* **Kop Surat Standar Sekolah**: Halaman cetak laporan (`print.php`) dirancang khusus dengan kop surat resmi **YAYASAN PENDIDIKAN BINA WARGA - SMK BINAWARGA LEMAHABANG**, tabel data tertata rapi, dan kolom pengesahan Kepala Sekolah / Tata Usaha.
* **Format Cetak Responsif**: Optimal untuk langsung dicetak menggunakan printer fisik atau disimpan sebagai file PDF melalui dialog print browser (`Ctrl+P`).

### 7. 🛡️ Keamanan, Hak Akses (RBAC) & Audit Logs
* **Role-Based Access Control (RBAC)**: Pemisahan hak akses ketat antara **Super User (Administrator)**, **Guru**, dan **Peserta Didik (Siswa)**.
* **Jejak Audit Keamanan (`audit_logs`)**: Setiap perubahan data administratif krusial (manajemen master data, pergantian API Key, modifikasi sistem) dicatat secara terpusat mencakup ID pengguna, aksi, tabel target, nilai sebelum/sesudah, alamat IP, dan user agent.
* **Perlindungan Terhadap Kerentanan Web**:
  * **CSRF Protection**: Token anti pemalsuan permintaan di setiap form POST (`Csrf::field()` / `Csrf::tokenField()`).
  * **SQL Injection**: Seluruh query database menggunakan PDO Prepared Statements dengan parameter binding.
  * **XSS Prevention**: Sanitasi output menyeluruh menggunakan `htmlspecialchars`.
  * **Rate Limiting**: Mekanisme pembatas laju request untuk melindungi endpoint autentikasi dan API dari serangan brute force / flooding.
  * **Password Security**: Penyimpanan kata sandi menggunakan hashing Bcrypt standar industri.

---

## 💻 Teknologi & Tumpukan Perangkat Lunak

* **Backend Utama**: PHP 8.0+ Native OOP MVC (Lightweight custom framework tanpa bloatware).
* **Microservice AI**: Node.js v18+ (Express/HTTP, `mysql2/promise`, HTTPS clients).
* **Database**: MariaDB 10.4+ / MySQL 8.0+ (Driver PDO dengan InnoDB Engine & UTF8mb4).
* **Frontend**: HTML5, CSS3, Bootstrap 5.3 (via CDN), Bootstrap Icons, JavaScript ES6+ (Native Async/Await & Fetch API).
* **AI & Machine Learning**:
  * Google Teachable Machine (`@teachablemachine/image` + TensorFlow.js 1.3.1).
  * Google Gemini API (`gemini-2.5-flash`, `gemini-3.5-flash`).
  * NVIDIA NIM Cloud API (`deepseek-ai/deepseek-v4.1-flash`, `meta/llama-3.2-11b-vision-instruct`).
* **Komunikasi Eksternal**: Fonnte WhatsApp API Gateway.

---

## 📁 Struktur Direktori Project

```text
projectrpl/
├── app/
│   ├── controllers/
│   │   ├── AdminController.php         # Controller Master Data (Siswa, Guru, Kelas), API Keys, & Broadcast
│   │   ├── AttendanceController.php    # Controller Laporan Rekapitulasi, Filter, & Export Cetak
│   │   ├── AuthController.php          # Controller Login, Session Guard, CSRF, & Logout
│   │   ├── ChatController.php          # Controller Dual-Engine AI Assistant & Fallback API
│   │   ├── GuruController.php          # Controller Dashboard & Riwayat Kehadiran Guru
│   │   ├── HomeController.php          # Controller Landing Page & Status Arsitektur
│   │   ├── ScanController.php          # Controller AI Face Recognition & Attendance Engine
│   │   └── SiswaController.php         # Controller Dashboard & Riwayat Siswa
│   ├── core/
│   │   ├── App.php                     # Core Router & URL Request Dispatcher
│   │   ├── Auth.php                    # Role-Based Access Control (RBAC) Guard
│   │   ├── Controller.php              # Base Controller (view, model, json, redirect)
│   │   ├── Csrf.php                    # CSRF Token Generator & Validator
│   │   ├── Database.php                # PDO Singleton Connection Wrapper
│   │   ├── Flash.php                   # Flash Notification Session Helper
│   │   ├── Model.php                   # Base Active Record ORM Helper
│   │   └── RateLimiter.php             # Token-Bucket API Rate Limiter
│   ├── models/
│   │   ├── ApiKey.php                  # Model Manajemen Multi-Key AI & Active Key Switching
│   │   ├── Attendance.php              # Model Presensi & Statistik Kehadiran
│   │   ├── AuditLog.php                # Model Jejak Audit Aktivitas & Perubahan Data
│   │   ├── BroadcastMessage.php        # Model Riwayat Pesan WhatsApp Gateway
│   │   ├── Classes.php                 # Model Rombongan Belajar / Kelas
│   │   ├── Setting.php                 # Model Konfigurasi Sistem Key-Value
│   │   ├── Student.php                 # Model Profil Peserta Didik & Face Label
│   │   ├── Teacher.php                 # Model Profil Guru & NIP
│   │   └── User.php                    # Model Akun Pengguna, Email Unik, & Bcrypt Auth
│   └── views/
│       ├── admin/                      # Views Master Data, API Keys, Pesan WhatsApp, & Dashboard
│       │   ├── api_keys/               # Views Konfigurasi API Key & Live Test
│       │   ├── classes/                # Views Manajemen Kelas
│       │   ├── messages/               # Views WhatsApp Broadcast & Outbox Log
│       │   ├── students/               # Views Master Data Peserta Didik (Create/Edit/Detail/List)
│       │   ├── teachers/               # Views Master Data Guru (Create/Edit/Detail/List)
│       │   └── dashboard.php           # Dashboard Super User
│       ├── attendance/                 # Views Rekapitulasi Presensi & Cetak (print.php)
│       ├── auth/                       # Views Form Login
│       ├── components/                 # Komponen Reusable (Floating Chatbot Widget)
│       │   └── chatbot.php
│       ├── guru/                       # Views Dashboard Guru & Monitoring Kelas
│       ├── home/                       # Views Landing Page Resmi
│       ├── layouts/                    # Header, Footer, Navbar Responsif
│       ├── scan/                       # Views Live Camera & AI Face Scanner
│       └── siswa/                      # Views Dashboard Siswa & Riwayat Mandiri
├── chatbot/                            # Microservice Node.js Chatbot AI
│   ├── node_modules/                   # Dependensi Node.js (mysql2, dll)
│   ├── package.json                    # Konfigurasi package Node.js
│   ├── server.js                       # HTTP Server Daemon Multi-Provider AI (Port 3001)
│   └── start.sh                        # Bash Script Runner untuk background daemon
├── config/
│   └── config.php                      # Konfigurasi Global (BASE_URL, Database, Session, Autoloader)
├── database/
│   ├── migrations/                     # Script Migrasi Database Terpisah
│   ├── migrate.php                     # CLI Automated Migration & Seeder Runner
│   ├── schema.sql                      # Skema DDL Database MySQL (Lengkap dengan Tabel Audit & API Keys)
│   └── seed.sql                        # Data Awal Standar (User, Class, Settings, API Key)
├── node-runtime/                       # Portable Node.js Binary Runtime
├── public/
│   ├── css/
│   │   └── style.css                   # Theme Palette Hijau Zamrud & Kuning Emas SMK BINAWARGA
│   ├── js/
│   │   ├── face-recognizer.js          # Engine Real-time TensorFlow.js & Teachable Machine
│   │   └── main.js                     # Interaksi UI, Modal Confirmation, & Helper
│   ├── my_model/                       # Model Klasifikasi Wajah Teachable Machine Lokal
│   └── index.php                       # Front Controller Entry Point
├── tests/                              # Automated Test Suites (9 Test Suites Lengkap)
├── README.md                           # Dokumentasi Resmi Proyek
└── SECURITY.md                         # Dokumen Analisis Keamanan & Anti-Fraud
```

---

## ⚙️ Panduan Instalasi & Konfigurasi

### 1. Prasyarat Lingkungan
* Web Server (Apache via XAMPP / LAMPP / Standalone Apache).
* PHP versi 8.0 atau yang lebih baru dengan ekstensi `pdo_mysql`, `curl`, dan `json` aktif.
* MySQL Server / MariaDB Server (Port default `3306`).
* Node.js versi 18+ (Opsional: telah disediakan runtime portabel di folder `node-runtime/`).

### 2. Konfigurasi Koneksi Basis Data
Buka berkas `config/config.php` dan sesuaikan pengaturan database dengan lingkungan lokal Anda:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'faceabsen_db');
```

### 3. Eksekusi Migrasi & Seeding Otomatis
Jalankan migrasi skema tabel dan pengisian data bawaan melalui antarmuka terminal:

```bash
php database/migrate.php
```

*Skrip ini akan otomatis membuat basis data `faceabsen_db`, membentuk 9 tabel berelasi (users, classes, students, teachers, attendances, settings, audit_logs, api_keys, broadcast_messages), serta mengisi akun bawaan ber-hash Bcrypt yang aman.*

---

## 🔐 Akun Bawaan Pengujian (Seeded Accounts)

Sistem telah dilengkapi dengan akun pengujian untuk seluruh tingkatan hak akses:

| Role | Username | Password | Email Terdaftar | Keterangan / Face Label |
| :--- | :--- | :--- | :--- | :--- |
| **Super User** | `admin` | `admin123` | `admin@smkbinawarga.sch.id` | Administrator Sistem & Master Data |
| **Guru** | `guru1` | `guru123` | `guru1@smkbinawarga.sch.id` | Budi Santoso, S.Kom (NIP: 198507122010011002) |
| **Guru** | `danish` | `danish123` | `danish@smkbinawarga.sch.id` | Danish (NIP: 199001011234567890) |
| **Peserta Didik** | `siswa_111111` | `siswa123` | `siswa_111111@smkbinawarga.sch.id` | Muhammad Rizky (**NIS: 111111** / Face Label: `111111`) |
| **Peserta Didik** | `siswa_222222` | `siswa123` | `siswa_222222@smkbinawarga.sch.id` | Siti Rahma (**NIS: 222222** / Face Label: `222222`) |
| **Peserta Didik** | `siswa_333333` | `siswa123` | `siswa_333333@smkbinawarga.sch.id` | Budi Pratama (**NIS: 333333** / Face Label: `333333`) |
| **Peserta Didik** | `siswa1` | `siswa123` | `siswa1@smkbinawarga.sch.id` | Ahmad Fauzi (NIS: 20241001 / Face Label: `siswa_ahmad_fauzi`) |
| **Peserta Didik** | `fajar` | `siswa123` | `fajar@smkbinawarga.sch.id` | Fajar (NIS: 20241002 / Face Label: `siswa_fajar`) |
| **Super User** | `firosy` | `firosy123` | `firosy@smkbinawarga.sch.id` | Super User Tambahan Firosy |

---

## 🏃 Menjalankan Aplikasi & Layanan Pendukung

### 1. Menjalankan Web Server PHP
#### Menggunakan Web Server Apache (XAMPP / LAMPP)
1. Pindahkan folder proyek ke web root (misal `/opt/lampp/htdocs/projectrpl` atau `C:/xampp/htdocs/projectrpl`).
2. Pastikan service Apache dan MySQL berjalan normal.
3. Akses aplikasi melalui browser:
   ```
   http://localhost/projectrpl/public/
   ```

#### Menggunakan PHP Built-in Server
Jika ingin menjalankan tanpa Apache:
```bash
php -S localhost:8000 -t public/
```
Akses melalui browser: `http://localhost:8000`

### 2. Menjalankan Service AI Chatbot (Node.js Daemon)
Untuk mengaktifkan microservice Chatbot AI di port `3001`:

```bash
# Opsi 1: Menggunakan bash script pembungkus
bash chatbot/start.sh

# Opsi 2: Menggunakan Node runtime portabel
./node-runtime/bin/node chatbot/server.js

# Opsi 3: Menggunakan Node.js global di sistem
node chatbot/server.js
```
*Status layanan dapat diperiksa via `http://localhost:3001/` atau `http://localhost:3001/health`.*

---

## 🤖 Konfigurasi Model Teachable Machine

Sistem FaceAbsen menggunakan model **Google Teachable Machine** yang dapat dikonfigurasi secara dinamis melalui database tanpa perlu memodifikasi kode:

1. Tabel `settings`:
   * `teachable_machine_model_url`: URL direktori model Teachable Machine (contoh: `http://localhost/projectrpl/public/my_model/` atau URL cloud `https://teachablemachine.withgoogle.com/models/XYZ/`).
   * `min_confidence_score`: Ambang batas akurasi minimal (default: `80.00`%).
   * `attendance_start_time`: Jam mulai presensi pagi (default: `06:30:00`).
   * `attendance_late_time`: Batas jam keterlambatan (default: `07:15:00`).
   * `attendance_end_time`: Jam batas akhir presensi (default: `16:00:00`).
2. Kamera secara otomatis meminta izin (*permission*) pengguna via `navigator.mediaDevices.getUserMedia`.
3. Pengenalan wajah dieksekusi real-time dengan feedback visual interaktif, persentase akurasi, dan audio sambutan.

---

## 🧪 Rangkaian Uji Otomasi (Automated Test Suites)

FaceAbsen dilengkapi dengan 9 test suite otomatis berstandar industri untuk memvalidasi integritas data, aturan bisnis, autentikasi, dan keamanan:

```bash
# 1. Uji Autentikasi, Bcrypt Hashing & Pembatas Hak Akses (RBAC)
php tests/auth_test.php

# 2. Uji Penanganan Rute HTTP, URL Guard & Middleware Filter
php tests/http_route_test.php

# 3. Uji Operasi Master Data CRUD (Siswa, Guru, Kelas, Email Unik & Transaksi Atomik)
php tests/crud_test.php

# 4. Uji API Pengenalan Wajah, Validasi Payload & Anti-Absensi Ganda
php tests/scan_test.php

# 5. Uji Aturan Bisnis Presensi (Tepat Waktu vs Terlambat, Jam Server)
php tests/attendance_test.php

# 6. Uji Dashboard Multi-Role & Agregasi Statistik Real-time
php tests/dashboard_test.php

# 7. Uji Modul Laporan, Filter Multi-Kriteria, Rekapitulasi & Ekspor Cetak
php tests/report_test.php

# 8. Uji Keamanan Aplikasi (Rate Limiter, CSRF Protection, XSS Sanitization & Audit Log)
php tests/security_test.php

# 9. Uji Manajemen Kunci API (CRUD Multi-Key, 1-Click Switch, Masking & Integrasi Provider)
php tests/api_key_test.php
```

---

## 🛡️ Standar Keamanan & Panduan Anti-Fraud

Detail mengenai pertahanan keamanan web, analisis kerentanan (SQL Injection, Cross-Site Scripting, CSRF, Session Hijacking), dan rekomendasi anti-spoofing biometrik dapat dipelajari pada dokumen:
👉 [`SECURITY.md`](SECURITY.md)

---

## 📄 Lisensi & Hak Cipta
&copy; **SMK BINAWARGA LEMAHABANG** — Sistem Presensi Sekolah Modern Berbasis AI Wajah & Teachable Machine.
Dikembangkan untuk keandalan, integritas, dan transparansi presensi civitas sekolah.
