<div class="container py-4 flex-grow-1">
    <?php Flash::display(); ?>

    <!-- Welcome & Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-shield-lock-fill text-danger me-2"></i>Dashboard Administrator
            </h2>
            <p class="text-muted mb-0">Selamat datang, <strong><?= htmlspecialchars($user['name']) ?></strong>. Ringkasan operasional sistem FaceAbsen hari ini.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-6 px-3 py-2 rounded-pill">
                <i class="bi bi-person-badge me-1"></i> Super User
            </span>
        </div>
    </div>

    <!-- 5 Utama Metric Cards (Responsive Grid) -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Peserta Didik -->
        <div class="col-12 col-sm-6 col-lg">
            <a href="<?= BASE_URL ?>admin/students" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white hover-shadow transition">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small fw-semibold">Peserta Didik</span>
                            <span class="badge bg-success-subtle text-success p-2 rounded-3">
                                <i class="bi bi-mortarboard-fill fs-5"></i>
                            </span>
                        </div>
                        <h3 class="fw-bold text-dark mb-0"><?= (int)$stats['total_students'] ?></h3>
                        <small class="text-success d-block mt-1">Total siswa aktif</small>
                    </div>
                </div>
            </a>
        </div>

        <!-- 2. Total Guru -->
        <div class="col-12 col-sm-6 col-lg">
            <a href="<?= BASE_URL ?>admin/teachers" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white hover-shadow transition">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small fw-semibold">Total Guru</span>
                            <span class="badge bg-info-subtle text-info p-2 rounded-3">
                                <i class="bi bi-person-video3 fs-5"></i>
                            </span>
                        </div>
                        <h3 class="fw-bold text-dark mb-0"><?= (int)$stats['total_teachers'] ?></h3>
                        <small class="text-info d-block mt-1">Total guru terdaftar</small>
                    </div>
                </div>
            </a>
        </div>

        <!-- 3. Hadir Hari Ini -->
        <div class="col-12 col-sm-6 col-lg">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small fw-semibold">Hadir Hari Ini</span>
                        <span class="badge bg-primary-subtle text-primary p-2 rounded-3">
                            <i class="bi bi-check-circle-fill fs-5"></i>
                        </span>
                    </div>
                    <h3 class="fw-bold text-dark mb-0"><?= (int)$stats['today_hadir'] ?></h3>
                    <small class="text-primary d-block mt-1">Status hadir tepat waktu</small>
                </div>
            </div>
        </div>

        <!-- 4. Terlambat Hari Ini -->
        <div class="col-12 col-sm-6 col-lg">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small fw-semibold">Terlambat</span>
                        <span class="badge bg-warning-subtle text-warning p-2 rounded-3">
                            <i class="bi bi-clock-history fs-5"></i>
                        </span>
                    </div>
                    <h3 class="fw-bold text-dark mb-0"><?= (int)$stats['today_terlambat'] ?></h3>
                    <small class="text-warning d-block mt-1">Status terlambat</small>
                </div>
            </div>
        </div>

        <!-- 5. Belum Absen -->
        <div class="col-12 col-sm-6 col-lg">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small fw-semibold">Belum Absen</span>
                        <span class="badge bg-danger-subtle text-danger p-2 rounded-3">
                            <i class="bi bi-exclamation-circle-fill fs-5"></i>
                        </span>
                    </div>
                    <h3 class="fw-bold text-dark mb-0"><?= (int)$stats['today_belum_absen'] ?></h3>
                    <small class="text-danger d-block mt-1">Siswa belum presensi</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Section: Statistik + Quick Action -->
    <div class="row g-4 mb-4">
        <!-- Statistik Absensi Hari Ini -->
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-clipboard-data-fill text-primary me-2"></i>Statistik Absensi Hari Ini
                    </h5>
                    <span class="badge bg-light text-muted border small">
                        <i class="bi bi-calendar3 me-1"></i><?= date('d M Y') ?>
                    </span>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-3 text-center mb-3">
                        <div class="col-4">
                            <div class="border rounded-3 bg-light p-3">
                                <div class="text-muted small mb-1">Total Absensi</div>
                                <h4 class="fw-bold text-dark mb-0"><?= (int)($today_summary['total_records'] ?? 0) ?></h4>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded-3 bg-light p-3">
                                <div class="text-muted small mb-1">Hadir Tepat Waktu</div>
                                <h4 class="fw-bold text-success mb-0"><?= (int)($today_summary['hadir'] ?? 0) ?></h4>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded-3 bg-light p-3">
                                <div class="text-muted small mb-1">Terlambat</div>
                                <h4 class="fw-bold text-warning mb-0"><?= (int)($today_summary['terlambat'] ?? 0) ?></h4>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar Kehadiran -->
                    <?php 
                        $totalEligible = max(1, (int)$stats['total_students']);
                        $hadirPercent = round(((int)$stats['today_hadir'] / $totalEligible) * 100);
                        $latePercent = round(((int)$stats['today_terlambat'] / $totalEligible) * 100);
                    ?>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Tingkat Kehadiran Siswa:</span>
                            <span class="fw-bold text-dark"><?= ($hadirPercent + $latePercent) ?>%</span>
                        </div>
                        <div class="progress rounded-pill" style="height: 8px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $hadirPercent ?>%" title="Hadir: <?= $hadirPercent ?>%"></div>
                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $latePercent ?>%" title="Terlambat: <?= $latePercent ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Action & Shortcut -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-lightning-charge-fill text-primary me-2"></i>Quick Action
                    </h5>
                </div>
                <div class="card-body pt-0">
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>admin/students" class="btn btn-outline-success rounded-pill btn-sm py-2 text-start px-3 d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-mortarboard-fill me-2"></i> Data Peserta Didik</span>
                            <i class="bi bi-chevron-right small"></i>
                        </a>
                        <a href="<?= BASE_URL ?>admin/teachers" class="btn btn-outline-info rounded-pill btn-sm py-2 text-start px-3 d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-person-video3 me-2"></i> Data Guru</span>
                            <i class="bi bi-chevron-right small"></i>
                        </a>
                        <a href="<?= BASE_URL ?>admin/classes" class="btn btn-outline-warning rounded-pill btn-sm py-2 text-start px-3 d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-door-open-fill me-2"></i> Data Kelas</span>
                            <i class="bi bi-chevron-right small"></i>
                        </a>
                        <a href="<?= BASE_URL ?>admin/messages" class="btn btn-outline-success rounded-pill btn-sm py-2 text-start px-3 d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-whatsapp text-success me-2"></i> Kirim Pesan WhatsApp</span>
                            <i class="bi bi-chevron-right small"></i>
                        </a>
                        <a href="<?= BASE_URL ?>scan" class="btn btn-outline-primary rounded-pill btn-sm py-2 text-start px-3 d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-camera-fill me-2"></i> Rekap / Pemindai FaceAbsen</span>
                            <i class="bi bi-chevron-right small"></i>
                        </a>
                        <a href="<?= BASE_URL ?>scan/settings" class="btn btn-light rounded-pill btn-sm py-2 text-start px-3 d-flex align-items-center justify-content-between text-muted">
                            <span><i class="bi bi-gear-fill me-2"></i> Konfigurasi Model AI & Jam Masuk</span>
                            <i class="bi bi-chevron-right small"></i>
                        </a>
                        <a href="<?= BASE_URL ?>admin/api_keys" class="btn btn-outline-dark rounded-pill btn-sm py-2 text-start px-3 d-flex align-items-center justify-content-between">
                            <span><i class="bi bi-key-fill text-warning me-2"></i> Konfigurasi API Key</span>
                            <i class="bi bi-chevron-right small"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Absensi Terbaru -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-clock-history text-primary me-2"></i>Tabel Absensi Terbaru
            </h5>
            <span class="badge bg-light text-muted border small"><?= count($latest_attendances) ?> Data</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-4">Nama / Pengguna</th>
                            <th>NIS / Identitas</th>
                            <th>Tanggal</th>
                            <th>Jam</th>
                            <th>Status</th>
                            <th>Metode</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($latest_attendances)): ?>
                            <?php foreach ($latest_attendances as $row): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($row['name'] ?? '-') ?></div>
                                        <small class="text-muted">@<?= htmlspecialchars($row['username'] ?? '-') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= htmlspecialchars($row['nis'] ?? ($row['class_name'] ?? 'User')) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row['attendance_date'] ?? '-') ?></td>
                                    <td>
                                        <i class="bi bi-clock text-muted me-1 small"></i>
                                        <?= htmlspecialchars($row['attendance_time'] ?? '-') ?>
                                    </td>
                                    <td>
                                        <?php if (strtolower($row['status'] ?? 'hadir') === 'terlambat'): ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1">
                                                <i class="bi bi-exclamation-circle me-1"></i>Terlambat
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">
                                                <i class="bi bi-check-circle me-1"></i>Hadir
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary border">
                                            <i class="bi bi-cpu me-1"></i><?= htmlspecialchars($row['method'] ?? 'face_recognition') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    <p class="mb-0 fw-medium">Belum ada catatan absensi terbaru pada sistem.</p>
                                    <small class="text-muted">Presensi yang masuk via kamera AI akan otomatis tampil di tabel ini.</small>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Chatbot AI Assistant untuk Super User / Admin (Rekap & Analitik Cepat)
$chatbot_role = 'super_user';
require __DIR__ . '/../components/chatbot.php';
?>

