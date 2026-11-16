<?php
/**
 * Komponen Chatbot AI Widget (Node.js & NVIDIA NIM)
 * Variabel pendukung yang diharapkan:
 * - $user (array user yang login)
 * - $chatbot_role (string: 'peserta_didik' | 'guru' | 'super_user')
 */
$chatRole = $chatbot_role ?? ($user['role'] ?? 'peserta_didik');
$chatUserName = $user['name'] ?? 'Sahabat FaceAbsen';
$isSuperUser = (stripos($chatRole, 'super_user') !== false || stripos($chatRole, 'admin') !== false);
$isGuru = !$isSuperUser && (stripos($chatRole, 'guru') !== false);
?>

<!-- Chatbot Floating Stylesheet -->
<link rel="stylesheet" href="<?= BASE_URL ?>css/chatbot.css?v=1.0">

<!-- Chatbot Container Wrapper -->
<div id="faceabsen-chatbot" class="faceabsen-chatbot-root" 
     data-user-name="<?= htmlspecialchars($chatUserName, ENT_QUOTES, 'UTF-8') ?>"
     data-user-role="<?= htmlspecialchars($chatRole, ENT_QUOTES, 'UTF-8') ?>"
     data-base-url="<?= BASE_URL ?>"
     data-node-url="http://localhost:3001">

    <!-- Floating Action Button (Ikon Chat) -->
    <button id="chatbot-fab" class="chatbot-fab-btn shadow-lg" type="button" aria-label="Buka Chatbot AI" title="Tanya FaceAbsen AI">
        <span class="chatbot-fab-badge pulse"></span>
        <i class="bi bi-chat-dots-fill chatbot-icon-open"></i>
        <i class="bi bi-x-lg chatbot-icon-close d-none"></i>
    </button>

    <!-- Floating Tooltip Greeting Preview -->
    <div id="chatbot-tooltip-bubble" class="chatbot-tooltip-bubble shadow-sm">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success rounded-circle p-1"><i class="bi bi-robot text-white"></i></span>
            <span class="small fw-semibold text-dark">
                <?= $isSuperUser ? 'Akses seluruh database & analitik FaceAbsen di sini!' : 'Butuh bantuan presensi? Tanya AI di sini!' ?>
            </span>
            <button type="button" class="btn-close btn-close-xs ms-1" id="chatbot-close-tooltip" aria-label="Tutup"></button>
        </div>
    </div>

    <!-- Chatbot Window Modal -->
    <div id="chatbot-window" class="chatbot-window shadow-lg d-none">
        <!-- Header -->
        <div class="chatbot-header d-flex justify-content-between align-items-center p-3 text-white">
            <div class="d-flex align-items-center gap-2">
                <div class="chatbot-avatar-circle">
                    <i class="bi bi-robot fs-5"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold d-flex align-items-center gap-2">
                        FaceAbsen AI
                        <span class="badge bg-light text-primary rounded-pill px-2 py-0" style="font-size: 0.65rem;">
                            <?= $isSuperUser ? 'Super Admin' : 'Node.js' ?>
                        </span>
                    </h6>
                    <small class="chatbot-status d-flex align-items-center gap-1 opacity-75">
                        <span class="status-dot"></span> NVIDIA NIM • Online
                    </small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-1">
                <button type="button" class="btn btn-sm btn-icon-header" id="chatbot-clear-btn" title="Bersihkan Obrolan">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
                <button type="button" class="btn btn-sm btn-icon-header" id="chatbot-minimize-btn" title="Tutup Chat">
                    <i class="bi bi-dash-lg"></i>
                </button>
            </div>
        </div>

        <!-- Body / Chat History -->
        <div id="chatbot-messages" class="chatbot-messages p-3">
            <!-- Pesan Selamat Datang Otomatis -->
            <div class="chat-message bot-message d-flex gap-2 mb-3">
                <div class="bot-avatar-mini">
                    <i class="bi bi-robot"></i>
                </div>
                <div class="message-bubble bot-bubble">
                    <p class="mb-1">
                        Halo <strong><?= htmlspecialchars($chatUserName) ?></strong>! 👋
                    </p>
                    <p class="mb-2">
                        <?php if ($isSuperUser): ?>
                            Saya asisten AI FaceAbsen untuk Super User / Administrator. Anda memiliki akses penuh ke seluruh database: rekapitulasi kehadiran, analitik rombel, data master siswa &amp; guru, data kelas, akun pengguna, hingga konfigurasi sistem presensi.
                        <?php elseif ($isGuru): ?>
                            Saya asisten AI FaceAbsen untuk Bapak/Ibu Guru. Ada yang ingin ditanyakan seputar presensi pribadi, rekap rombel, atau panduan sistem?
                        <?php else: ?>
                            Saya asisten pintar FaceAbsen. Mau tahu cara presensi wajah, aturan jam masuk/terlambat, atau ada kendala kamera? Tanyakan saja ya!
                        <?php endif; ?>
                    </p>
                    <small class="message-time text-muted d-block text-end"><?= date('H:i') ?></small>
                </div>
            </div>

            <!-- Quick Action Suggestion Chips -->
            <div class="quick-chips-container mb-3" id="quick-chips">
                <small class="text-muted d-block mb-1 fw-semibold" style="font-size: 0.75rem;">Pertanyaan Populer:</small>
                <div class="d-flex flex-wrap gap-1">
                    <?php if ($isSuperUser): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary quick-chip" data-query="Bagaimana rekapitulasi kehadiran sekolah hari ini?">
                            <i class="bi bi-bar-chart-line me-1"></i>Rekap Hari Ini
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary quick-chip" data-query="Tampilkan analitik kehadiran per kelas atau rombel hari ini">
                            <i class="bi bi-pie-chart me-1"></i>Analitik Rombel
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary quick-chip" data-query="Tampilkan seluruh data peserta didik beserta status wajah AI">
                            <i class="bi bi-mortarboard me-1"></i>Data Siswa
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary quick-chip" data-query="Tampilkan daftar seluruh guru dan NIP">
                            <i class="bi bi-person-badge me-1"></i>Data Guru
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary quick-chip" data-query="Apa saja pengaturan sistem presensi (jadwal & model AI)?">
                            <i class="bi bi-sliders me-1"></i>Pengaturan Sistem
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary quick-chip" data-query="Tampilkan daftar akun pengguna (users) dan perannya">
                            <i class="bi bi-people me-1"></i>Daftar Pengguna
                        </button>
                    <?php elseif ($isGuru): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary quick-chip" data-query="Bagaimana cara presensi wajah untuk guru?">
                            <i class="bi bi-camera me-1"></i>Cara Presensi Guru
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary quick-chip" data-query="Berapa jam masuk dan batas terlambat presensi?">
                            <i class="bi bi-clock me-1"></i>Aturan Jam Presensi
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary quick-chip" data-query="Siapa saja daftar peserta didik yang terdaftar di database?">
                            <i class="bi bi-mortarboard me-1"></i>Data Peserta Didik
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary quick-chip" data-query="Siapa saja tenaga pengajar/guru yang terdaftar?">
                            <i class="bi bi-person-badge me-1"></i>Data Tenaga Pengajar
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary quick-chip" data-query="Apa yang harus dilakukan jika kamera wajah siswa tidak terdeteksi?">
                            <i class="bi bi-wrench me-1"></i>Kendala Kamera Wajah
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-sm btn-outline-success quick-chip" data-query="Bagaimana langkah-langkah presensi wajah yang benar?">
                            <i class="bi bi-camera me-1"></i>Cara Absen Wajah
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success quick-chip" data-query="Pukul berapa batas waktu presensi agar tidak terlambat?">
                            <i class="bi bi-alarm me-1"></i>Batas Jam Masuk
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success quick-chip" data-query="Siapa saja nama guru/tenaga pengajar yang terdaftar?">
                            <i class="bi bi-person-badge me-1"></i>Daftar Guru
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success quick-chip" data-query="Siapa saja peserta didik yang terdaftar di database?">
                            <i class="bi bi-mortarboard me-1"></i>Data Siswa
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success quick-chip" data-query="Kenapa kamera tidak bisa mengenali wajah saya?">
                            <i class="bi bi-question-circle me-1"></i>Kendala Kamera
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Typing Indicator (Hidden by default) -->
        <div id="chatbot-typing-indicator" class="chatbot-typing-indicator px-3 py-2 d-none">
            <div class="typing-bubble d-inline-flex align-items-center gap-1">
                <i class="bi bi-robot text-primary me-1"></i>
                <span class="text-muted small me-2">AI sedang mengetik</span>
                <span class="dot"></span>
                <span class="dot"></span>
                <span class="dot"></span>
            </div>
        </div>

        <!-- Footer / Input Form -->
        <div class="chatbot-footer p-2 border-top bg-white">
            <form id="chatbot-form" class="d-flex align-items-center gap-2 mb-0">
                <input type="text" id="chatbot-input" class="form-control form-control-sm rounded-pill chatbot-input-field" 
                       placeholder="Ketik pertanyaan seputar presensi..." autocomplete="off" required>
                <button type="submit" id="chatbot-send-btn" class="btn btn-primary btn-sm rounded-circle chatbot-send-btn" 
                        aria-label="Kirim Pesan" title="Kirim Pesan">
                    <i class="bi bi-send-fill"></i>
                </button>
            </form>
            <div class="text-center mt-1">
                <small class="text-muted" style="font-size: 0.68rem;">
                    Didukung oleh <strong class="text-dark">Node.js</strong> &amp; <strong class="text-dark">NVIDIA NIM AI</strong>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Chatbot Client Script -->
<script src="<?= BASE_URL ?>js/chatbot.js?v=1.0"></script>
