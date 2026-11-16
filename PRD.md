# Product Requirements Document (PRD)
## FaceAbsen - Sistem Presensi Sekolah Berbasis AI Wajah & Asisten Cerdas
**Instansi:** SMK BINAWARGA LEMAHABANG  
**Versi Dokumen:** 1.0 (MVP & Ekstensi Fitur Terpadu)  
**Status:** Implementasi Aktif / Produksi  
**Target Platform:** Web (Desktop, Laptop, Tablet, & Smartphone)

---

## 1. Ringkasan Eksekutif (Executive Summary)

### 1.1 Latar Belakang Masalah
Pencatatan kehadiran siswa dan guru konvensional (berbasis kertas, tanda tangan manual, atau pemindai sidik jari) memiliki sejumlah kelemahan:
* **Risiko Kecurangan:** Praktik "titip absen" atau absensi palsu masih sering terjadi.
* **Inefisiensi Waktu:** Antrean panjang di gerbang sekolah dan rekapitulasi data manual memakan waktu tim Tata Usaha dan Wali Kelas.
* **Keterlambatan Informasi:** Orang tua/wali siswa tidak menerima kabar real-time mengenai keterlambatan atau ketidakhadiran siswa.
* **Keterbatasan Akses Informasi:** Civitas sekolah kesulitan mendapatkan informasi cepat seputar rekap kehadiran atau status rombel tanpa membuka banyak laporan manual.

### 1.2 Solusi Produk: FaceAbsen
FaceAbsen adalah aplikasi web presensi sekolah berbasis AI (*Artificial Intelligence*) yang mengombinasikan:
1. **Pengenalan Wajah Real-time:** Memanfaatkan Google Teachable Machine & TensorFlow.js untuk presensi tanpa kontak fisik (*contactless biometric*).
2. **Asisten AI Chatbot Multi-Provider:** Didukung Google Gemini dan NVIDIA NIM untuk melayani tanya-jawab berbasis data sekolah real-time (*read-only*).
3. **WhatsApp Broadcast Gateway:** Terintegrasi Fonnte API untuk pengiriman notifikasi instan ke nomor civitas sekolah.
4. **Master Data & Rekapitulasi Terpadu:** Manajemen data siswa, guru, kelas, email terverifikasi, dan laporan cetak berstandar kop surat resmi sekolah.

---

## 2. Sasaran & Metrik Keberhasilan (Goals & Success Metrics)

| Sasaran Produk | Metrik / Indikator Kunci (KPI) |
| :--- | :--- |
| **Kecepatan Presensi** | Proses deteksi dan validasi presensi wajah selesai dalam waktu < 2 detik per pengguna. |
| **Akurasi Deteksi** | Tingkat akurasi pengenalan wajah (*confidence score*) ≥ 80%. |
| **Integritas Data Kehadiran** | 0% kecurangan ganda harian berkat proteksi *unique database constraint*. |
| **Otomasi Rekapitulasi** | Laporan kehadiran harian dan bulanan siap cetak (*print-ready*) secara instan tanpa rekap manual. |
| **Efisiensi Komunikasi** | Pengiriman broadcast WhatsApp ke ratusan penerima dalam hitungan detik. |

---

## 3. Persona Pengguna & Hak Akses (User Personas & RBAC)

Sistem membagi pengguna ke dalam 3 tingkatan peran (*Role-Based Access Control*):

```
                     ┌──────────────────────────┐
                     │       SUPER USER         │
                     │  (Administrator / TU)    │
                     └─────────────┬────────────┘
                                   │
              ┌────────────────────┴────────────────────┐
              ▼                                         ▼
┌──────────────────────────┐               ┌──────────────────────────┐
│          GURU            │               │      PESERTA DIDIK       │
│ (Tenaga Pengajar / Wali) │               │         (Siswa)          │
└──────────────────────────┘               └──────────────────────────┘
```

### 3.1 Super User (Administrator / Bagian Tata Usaha)
* **Kebutuhan:** Kendali penuh terhadap master data, konfigurasi integrasi AI/WhatsApp, pemantauan log audit, dan ekspor seluruh laporan presensi sekolah.
* **Fitur Utama:**
  * CRUD Master Data: Siswa, Guru, dan Kelas (Rombel).
  * Konfigurasi API Key (NVIDIA NIM & Google Gemini) dengan fitur uji latensi langsung.
  * WhatsApp Broadcast Gateway (Kirim pesan ke seluruh guru, siswa, per kelas, atau nomor khusus).
  * Pemantauan Jejak Audit Keamanan (`audit_logs`).
  * Pengaturan Jam Sekolah (Jam Masuk, Toleransi Keterlambatan, Jam Pulang, Ambang Akurasi Wajah).

### 3.2 Guru (Tenaga Pendidik / Wali Kelas)
* **Kebutuhan:** Memantau ketertiban kehadiran siswa di kelas yang diampu, melakukan presensi wajah mandiri, dan melihat riwayat kehadiran pribadi.
* **Fitur Utama:**
  * Presensi wajah masuk/pulang harian.
  * Dashboard monitoring kehadiran rombel dan status siswa (Hadir, Terlambat, Izin, Sakit, Alpa).
  * Akses asisten chatbot AI untuk data absensi dan jadwal piket.

### 3.3 Peserta Didik (Siswa)
* **Kebutuhan:** Melakukan presensi pagi dengan kamera pemindai wajah secara cepat serta memantau riwayat presensi sendiri.
* **Fitur Utama:**
  * Presensi wajah mandiri via kamera perangkat (desktop/smartphone).
  * Riwayat log presensi personal harian dan bulanan.
  * Feedback visual status kehadiran (Hadir Tepat Waktu vs Terlambat).

---

## 4. Ruang Lingkup Fitur (Feature Requirements)

### 4.1 Modul 1: Presensi Wajah AI Real-time (Face Recognition Engine)
* **FR-1.1 (Deteksi Kamera):** Membuka stream kamera pengguna via WebRTC `getUserMedia` dengan resolusi responsif di perangkat desktop maupun seluler.
* **FR-1.2 (Inferensi Model AI):** Memproses frame gambar secara real-time menggunakan model TensorFlow.js dari Google Teachable Machine (`public/my_model/`).
* **FR-1.3 (Ambang Akurasi):** Hanya memvalidasi presensi jika confidence score mencapai nilai minimal yang ditentukan (default `80.00%`).
* **FR-1.4 (Pencegahan Duplikasi):** Mencegah pengguna melakukan presensi lebih dari 1 kali dalam tanggal yang sama (`UNIQUE(user_id, attendance_date)`).
* **FR-1.5 (Penentuan Status Otomatis):**
  * Waktu masuk < Batas Jam Terlambat (misal 07:15) ➔ Status **Hadir**.
  * Waktu masuk ≥ Batas Jam Terlambat ➔ Status **Terlambat**.
* **FR-1.6 (Feedback Interaktif):** Menyediakan audio feedback suara sambutan, status box warna dinamis, dan hitung mundur otomatis (*countdown*).

### 4.2 Modul 2: Manajemen Master Data & Atribut Email
* **FR-2.1 (Data Siswa):** Pengelolaan data siswa mencakup Nama, NIS, Kelas, No. HP, Face Model Identifier, dan **Alamat Email**.
* **FR-2.2 (Data Guru):** Pengelolaan data guru mencakup Nama, NIP, No. HP, dan **Alamat Email**.
* **FR-2.3 (Atribut Email Terpusat):** Kolom `email` disimpan terpusat di tabel induk `users` dengan validasi keunikan global di seluruh sistem dan format email valid (`FILTER_VALIDATE_EMAIL`).
* **FR-2.4 (Data Kelas / Rombel):** Pengelolaan kelas (tingkat 10, 11, 12) dengan penghitungan otomatis jumlah siswa aktif.
* **FR-2.5 (Proteksi Penghapusan Kelas):** Sistem menolak penghapusan kelas jika masih ada siswa yang terdaftar (*Safe Deletion Protection*).
* **FR-2.6 (Transaksi Atomik):** Seluruh penyimpanan multi-tabel (`users` + `students` / `teachers`) dibungkus dalam transaksi database atomik (*rollback on error*).

### 4.3 Modul 3: Asisten AI Chatbot Sekolah (Multi-Provider)
* **FR-3.1 (Akses Terintegrasi):** Widget floating chatbot di pojok kanan bawah yang dapat diakses oleh seluruh pengguna terotentikasi.
* **FR-3.2 (Akses Data Real-time):** Chatbot memiliki akses *read-only* ke data absensi hari ini, daftar rombel, profil kontak civitas, dan aturan sekolah untuk menjawab pertanyaan faktual.
* **FR-3.3 (Dukungan Multi-Provider):**
  * Google Gemini API (`gemini-2.5-flash`, `gemini-3.5-flash`).
  * NVIDIA NIM API (`deepseek-ai/deepseek-v4.1-flash`, `meta/llama-3.2-11b-vision-instruct`).
* **FR-3.4 (Proteksi Anti-Koding):** Sistem secara otomatis menolak instruksi pengguna yang meminta pembuatan atau pembahasan kode pemrograman (*Strict Coding Refusal*), menjaga peran asisten tetap pada ranah sekolah.
* **FR-3.5 (Arsitektur Dual-Engine):** Berjalan di microservice Node.js daemon (port 3001) dengan mekanisme *fallback* otomatis ke backend PHP jika microservice tidak aktif.

### 4.4 Modul 4: Manajemen Kunci API (API Key Management)
* **FR-4.1 (Penyimpanan Multi-Key):** Super User dapat mendaftarkan beberapa API Key cadangan dengan nama label dan provider yang jelas.
* **FR-4.2 (1-Click Switch):** Mengubah kunci aktif dengan satu klik tombol "Gunakan", otomatis memperbarui tabel `settings` dan mencatat audit log.
* **FR-4.3 (Uji Koneksi Langsung):** Pengujian koneksi ke API provider secara live, mengukur latensi (ms), dan memverifikasi status model aktif.
* **FR-4.4 (Masking Sensor Kunci):** Kunci disensor di antarmuka tabel (`nvapi-••••4hkehE` atau `AQ.••••Vlg`) dengan tombol *Reveal/Hide* dan salin clipboard.
* **FR-4.5 (Auto-Migration Self-Healing):** Tabel `api_keys` otomatis dibuat jika belum tersedia saat controller diakses.

### 4.5 Modul 5: WhatsApp Broadcast Gateway (Fonnte API)
* **FR-5.1 (Kategori Penerima):** Pilihan broadcast ke Semua Guru, Semua Siswa, Per Rombel, atau Nomor Tertentu.
* **FR-5.2 (Template Variabel Dinamis):** Mendukung placeholder otomatis `{nama}`, `{sekolah}`, dan `{tanggal}`.
* **FR-5.3 (Outbox & Riwayat Pesan):** Pencatatan riwayat pesan terkirim di tabel `broadcast_messages` beserta status respon gateway.

### 4.6 Modul 6: Laporan, Rekapitulasi & Cetak
* **FR-6.1 (Filter Rekapitulasi):** Pemfilteran data berdasarkan tanggal harian, periode bulan & tahun, rombel/kelas, dan status kehadiran.
* **FR-6.2 (Kop Surat Resmi):** Tampilan cetak (`print.php`) memuat kop surat resmi **YAYASAN PENDIDIKAN BINA WARGA - SMK BINAWARGA LEMAHABANG**.
* **FR-6.3 (Export PDF / Print):** Tata letak halaman cetak responsif dan rapi untuk printer fisik maupun fitur "Save as PDF" di browser.

### 4.7 Modul 7: Keamanan & Audit Trail
* **FR-7.1 (Audit Logs):** Mencatat setiap aktivitas penting Super User di tabel `audit_logs` (user ID, action, table, old value, new value, IP, user agent).
* **FR-7.2 (CSRF Guard):** Perlindungan CSRF token di setiap form input dan mutasi data POST.
* **FR-7.3 (Rate Limiter):** Pembatasan frekuensi request ke endpoint sensitif (login dan scanning).
* **FR-7.4 (Password Hashing):** Enkripsi kata sandi menggunakan algoritma Bcrypt standar industri.

---

## 5. Kebutuhan Non-Fungsional (Non-Functional Requirements)

* **Performa (Performance):**
  * Waktu respon scanning wajah: < 2 detik pada koneksi internet normal / lokal.
  * Respon Asisten AI Chatbot: < 3 detik untuk query data sekolah.
* **Keamanan (Security):**
  * Pencegahan injeksi SQL via PDO Prepared Statements & Parameter Binding.
  * Sanitasi seluruh output ke antarmuka dengan `htmlspecialchars` (Anti-XSS).
  * Proteksi sesi dari *session hijacking* dan *fixation*.
* **Ketersediaan (Availability & Fallback):**
  * Sistem presensi inti tetap berfungsi 100% meskipun service Chatbot AI eksternal atau WhatsApp gateway mengalami gangguan jaringan.
* **Kompatibilitas Perangkat (Usability & Compatibility):**
  * Responsif di berbagai resolusi layar (Mobile 360px hingga Desktop 1920px) berbasis Bootstrap 5.3.
  * Kompatibel dengan browser modern: Google Chrome, Mozilla Firefox, Microsoft Edge, dan Safari.

---

## 6. Tumpukan Teknologi & Arsitektur Sistem (Tech Stack)

```
┌────────────────────────────────────────────────────────┐
│             KLIEN BROWSER (UI & CAMERA)                │
│  HTML5, CSS3, Bootstrap 5.3, JavaScript ES6+, WebRTC   │
│         TensorFlow.js (Teachable Machine Model)        │
└──────────────────────────┬─────────────────────────────┘
                           │ HTTP / HTTPS
                           ▼
┌────────────────────────────────────────────────────────┐
│             BACKEND WEB SERVER (PHP 8+ MVC)            │
│         Core Routing, Controllers, Models, RBAC        │
└────────────┬─────────────────────────────┬─────────────┘
             │                             │
             ▼                             ▼
┌────────────────────────┐    ┌────────────────────────┐
│  DATABASE (MariaDB)    │    │  AI CHATBOT (Node.js)  │
│  Tabel Relasional PDO  │◄───┤  Port 3001 Microservice│
│  Integritas & Audit    │    │  Gemini & NVIDIA NIM   │
└────────────────────────┘    └────────────────────────┘
```

* **Bahasa Backend Utama:** PHP 8.0+ Native OOP MVC (Tanpa Framework Berat).
* **Microservice AI:** Node.js v18+ dengan runtime portabel (`node-runtime/`).
* **Basis Data:** MariaDB / MySQL dengan InnoDB Engine, Foreign Keys, & UTF8mb4.
* **Frontend:** HTML5, CSS3 (Palette Zamrud & Emas SMK BINAWARGA), Bootstrap 5.3, Bootstrap Icons.
* **Model AI:** Google Teachable Machine Image Classification Model.
* **Cloud AI Providers:** Google Gemini API & NVIDIA NIM API.
* **SMS/WA Gateway:** Fonnte API Gateway.

---

## 7. Struktur Data Inti (Database Schema Overview)

Basis data `faceabsen_db` terdiri atas 9 tabel utama:

1. `users` — Identitas akun, peran (super_user, guru, peserta_didik), hash kata sandi, dan alamat email unik.
2. `classes` — Rombongan belajar (nama kelas dan tingkatan).
3. `students` — Profil peserta didik, relasi ke `users` & `classes`, NIS, no. HP, dan label model wajah.
4. `teachers` — Profil tenaga pendidik, relasi ke `users`, NIP, dan no. HP.
5. `attendances` — Rekam presensi harian dengan constraint unik `(user_id, attendance_date)`.
6. `settings` — Pengaturan sistem berbasis key-value (jam presensi, model URL, toleransi waktu).
7. `audit_logs` — Jejak audit perubahan administratif dan keamanan.
8. `api_keys` — Penyimpanan multi-key AI provider, label, status aktif, dan masking.
9. `broadcast_messages` — Riwayat pengiriman pesan pengumuman WhatsApp via gateway Fonnte.

---

## 8. Rencana Rilis & Roadmap Pengembangan

* **Fase 1 (MVP - Selesai):**
  * [x] Arsitektur inti MVC PHP Native & Autentikasi RBAC.
  * [x] Integrasi kamera WebRTC & deteksi wajah Google Teachable Machine.
  * [x] Aturan jam sekolah dan logika status Hadir / Terlambat.
* **Fase 2 (Manajemen Data & Laporan - Selesai):**
  * [x] Master Data Siswa, Guru, dan Rombel (CRUD).
  * [x] Laporan presensi harian/bulanan dengan format kop surat resmi SMK BINAWARGA LEMAHABANG.
  * [x] Jejak audit aktivitas administratif (`audit_logs`).
* **Fase 3 (Integrasi AI & WhatsApp - Selesai):**
  * [x] Microservice Node.js Chatbot AI dengan akses data sekolah read-only.
  * [x] Integrasi multi-provider (Google Gemini & NVIDIA NIM).
  * [x] Panel konfigurasi API Key dengan uji latensi langsung dan masking.
  * [x] WhatsApp Gateway (Fonnte API) untuk pengumuman broadcast.
* **Fase 4 (Penyempurnaan Identitas & Atribut Email - Selesai):**
  * [x] Penambahan atribut Email unik terverifikasi pada akun Siswa dan Guru.
  * [x] Standardisasi nama sekolah resmi menjadi **SMK BINAWARGA LEMAHABANG**.
  * [x] Pengujian otomatis komprehensif (9 Test Suites, 100% Passed).
* **Fase 5 (Pengembangan Mendatang / Future Roadmap):**
  * [ ] *Liveness Detection* lanjutan (deteksi kedipan mata atau gestur wajah untuk anti-foto/spoofing).
  * [ ] Progressive Web App (PWA) agar dapat diinstal langsung di layar utama smartphone.
  * [ ] Notifikasi otomatis pesan WhatsApp ke wali murid saat siswa terlambat atau tidak hadir (*automated triggered notifications*).

---

## 9. Penutup & Pengesahan

Dokumen PRD ini menjadi acuan resmi standar fungsionalitas, arsitektur, dan keamanan untuk pengembangan serta operasional aplikasi **FaceAbsen** di **SMK BINAWARGA LEMAHABANG**.
