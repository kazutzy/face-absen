# Panduan Keamanan & Anti-Fraud: FaceAbsen

Dokumen ini berisi hasil **Security Review**, evaluasi **Anti-Fraud**, mekanisme perlindungan **Face Recognition**, analisis **Anti-Spoofing**, serta **Daftar Risiko yang Masih Tersisa** pada aplikasi sistem presensi **FaceAbsen**.

---

## 1. Hasil Evaluasi Keamanan (14 Komponen Utama)

| No | Komponen Keamanan | Status Implementasi & Pengujian | Keterangan Teknis |
|---|---|:---:|---|
| 1 | **Authentication** | **AMAN (Secured)** | Autentikasi berbasis username dan password dengan status akun aktif (`users.status = 'active'`). Validasi kegagalan login informatif tanpa membocorkan eksistensi akun (*user enumeration*). |
| 2 | **Authorization (RBAC)** | **AMAN (Secured)** | Hak akses dikontrol ketat via middleware guard `Auth::requireRole()` dan `Auth::requireLogin()`. Tiga role terisolasi (*Super User*, *Guru*, *Peserta Didik*). Siswa tidak dapat mengakses master data atau data presensi siswa lain. |
| 3 | **Session Security** | **AMAN (Secured)** | Menggunakan `session_regenerate_id(true)` saat proses login untuk mencegah serangan *Session Fixation*. Pembersihan sesi total saat logout (`session_destroy` & penghapusan cookie sesi). |
| 4 | **Password Hashing** | **AMAN (Secured)** | Menggunakan algoritma **BCRYPT** (`PASSWORD_BCRYPT`) standar industri dengan salt otomatis. Tidak ada password yang disimpan dalam format *plain text*. |
| 5 | **CSRF Protection** | **AMAN (Secured)** | Setiap form POST dan endpoint AJAX dilindungi token acak 256-bit (`bin2hex(random_bytes(32))`) yang divalidasi menggunakan fungsi *constant-time comparison* `hash_equals()`. |
| 6 | **SQL Injection** | **AMAN (Secured)** | Seluruh query database pada seluruh Model menggunakan **PDO Prepared Statements** dengan *parameter binding* eksplisit. Bebas dari manipulasi string SQL mentah. |
| 7 | **XSS (Cross-Site Scripting)** | **AMAN (Secured)** | Seluruh output variabel dinamis di-escape menggunakan `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` pada layer Views. |
| 8 | **Input Validation** | **AMAN (Secured)** | Validasi server-side menyeluruh terhadap NIS, NIP, Username (keunikan di database), URL Model AI, Jam Batas Masuk, dan batas confidence score. |
| 9 | **File Upload** | **AMAN (N/A)** | Sistem tidak mengekspos endpoint upload file bebas yang rentan diinjeksi skrip berbahaya (model AI dimuat via URL terkonfigurasi). |
| 10 | **Camera Permission** | **AMAN (Secured)** | Akses kamera menggunakan API standar `navigator.mediaDevices.getUserMedia` yang secara otomatis meminta izin eksplisit dari pengguna serta mewajibkan konteks aman (*HTTPS / localhost*). |
| 11 | **API Endpoint Security** | **AMAN (Secured)** | Endpoint `/scan/record` dilindungi autentikasi sesi, token CSRF, validasi integritas payload, dan **Rate Limiter**. |
| 12 | **Duplicate Attendance** | **AMAN (Secured)** | Presensi ganda pada hari yang sama dicegah pada 3 lapis: *Database Unique Constraint* `uq_user_attendance_date`, *Transaction Lock* `FOR UPDATE`, dan *Frontend State Debouncing*. |
| 13 | **Direct URL Access** | **AMAN (Secured)** | Seluruh route controller memeriksa autentikasi sebelum merender view. Tamu (*Guest*) yang mencoba mengakses URL internal otomatis dialihkan ke halaman login. |
| 14 | **Data Privacy** | **AMAN (Secured)** | Pemrosesan inferensi AI Teachable Machine berjalan **100% lokal di browser pengguna (Client-side TensorFlow.js)**. Tidak ada foto atau video wajah mentah yang dikirimkan atau disimpan ke database server. |

---

## 2. Mekanisme Keamanan Face Recognition & Anti-Fraud

1. **Authoritative Server Identity Validation (Anti-Impersonation)**:
   * Sistem **tidak mempercayai** `user_id` yang dikirim dari browser sebagai satu-satunya bukti identitas.
   * Server memverifikasi identitas pengguna berdasarkan sesi login aktif `Auth::id()` dan mencocokkan `face_model_identifier` pada tabel `students`.
   * Jika wajah yang terdeteksi adalah milik siswa B namun yang login adalah siswa A, request **ditolak** dengan kode `400 / 403`.
2. **Configurable Confidence Threshold**:
   * Ambang batas akurasi dapat diatur secara dinamis oleh Super User (default: `80.00%`).
   * Prediksi dengan akurasi di bawah batas ambang atau label kosong/background otomatis ditolak server.
3. **Rate Limiting Engine (`RateLimiter.php`)**:
   * Endpoint presensi `/scan/record` dibatasi maksimal **15 request per menit** per user/IP untuk mencegah serangan *brute-force* atau flooding request inferensi.
   * Request yang melebihi batas akan diblokir dengan respons `HTTP 429 Too Many Requests`.
4. **Audit Logging System (`audit_logs`)**:
   * Setiap perubahan konfigurasi model AI, jam batas keterlambatan, atau modifikasi master data oleh administrator dicatat dalam tabel `audit_logs` lengkap dengan `user_id`, `action`, `old_values`, `new_values`, `ip_address`, dan `timestamp`.

---

## 3. Analisis Anti-Spoofing & Kejujuran Teknis Biometrik

> [!IMPORTANT]
> **Pernyataan Transparansi & Kejujuran Teknis**:
> Model pengenalan wajah bawaan Google Teachable Machine (Image Project) berbasis klasifikasi citra 2D (MobileNet/TensorFlow.js). Model ini **belum memiliki sensor depth 3D fisik maupun algoritma Liveness Detection aktif/pasif bawaan**.

### Rekomendasi Anti-Spoofing untuk Pengembangan Lanjutan:
1. **Active Challenge-Response Liveness Detection**:
   * Mengintegrasikan pustaka pendeteksi landmark wajah (seperti *MediaPipe Face Mesh*) untuk meminta pengguna melakukan aksi acak saat presensi (misal: *Berkedip 2 kali*, *Tersenyum*, atau *Menoleh ke kanan/kiri*).
2. **Passive Liveness / Texture Analysis**:
   * Menganalisis refleksi cahaya layar smartphone, pola moiré layar digital, atau distorsi cetakan foto kertas pada frame video.
3. **Geofencing & Network Restriction**:
   * Membatasi pengiriman absensi hanya jika perangkat terhubung ke Wi-Fi sekolah (verifikasi IP publik atau BSSID jaringan sekolah) atau koordinat GPS berada dalam radius sekolah.
4. **Supervised Attendance Kiosk**:
   * Menggunakan tablet/perangkat pemindai yang diletakkan di gerbang sekolah dengan pengawasan petugas piket / guru untuk mencegah siswa memindai foto cetak.

---

## 4. Daftar Risiko yang Masih Tersisa (Residual Risks) & Mitigasinya

| No | Risiko yang Masih Tersisa | Tingkat Risiko | Dampak Potensial | Rekomendasi Mitigasi |
|---|---|:---:|---|---|
| 1 | **Spoofing Foto / Video 2D** | Sedang | Siswa menampilkan foto cetak atau video wajah teman di depan kamera smartphone. | Terapkan *Active Liveness Challenge* (kedip/senyum) atau gunakan perangkat presensi terpasang di sekolah (*kiosk mode*). |
| 2 | **Presensi di Luar Lokasi Sekolah** | Sedang | Siswa melakukan presensi dari rumah menggunakan HP pribadi sebelum berangkat sekolah. | Tambahkan validasi *Geofencing GPS Radius* atau pembatasan *IP Whitelist / Local Subnet Wi-Fi Sekolah*. |
| 3 | **Kemiripan Wajah Ekstrem (Kembar Identik)** | Rendah | Siswa kembar identik berpotensi salah teridentifikasi pada model klasifikasi umum. | Tingkatkan *minimum confidence threshold* ke 90%+ dan daftarkan dataset foto latih dengan sudut pencahayaan yang variatif. |
| 4 | **Manipulasi Jam Sistem Klien** | Sangat Rendah (Sudah Termitigasi) | Pengguna mengubah jam pada smartphone miliknya. | Waktu presensi dihitung **100% menggunakan jam server backend** (`date('H:i:s')`), sehingga manipulasi jam HP tidak berpengaruh. |

---

*Dokumen ini diperbarui secara berkala seiring pembaruan arsitektur keamanan sistem FaceAbsen.*
