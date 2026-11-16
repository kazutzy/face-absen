<div class="container py-4 flex-grow-1">
    <?php Flash::display(); ?>

    <!-- Welcome Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-mortarboard-fill text-success me-2"></i>Dashboard Peserta Didik
            </h2>
            <p class="text-muted mb-0">Selamat datang, <strong><?= htmlspecialchars($user['name']) ?></strong>. Cek status presensi harianmu di sini.</p>
        </div>
        <div>
            <span class="badge bg-success-subtle text-success border border-success-subtle fs-6 px-3 py-2 rounded-pill">
                <i class="bi bi-person-check me-1"></i> Peserta Didik
            </span>
        </div>
    </div>

    <!-- Row 1: Profil & Status Presensi Hari Ini -->
    <div class="row g-4 mb-4">
        <!-- Profil Siswa -->
        <div class="col-12 col-md-5">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-person-vcard text-primary me-2"></i>Profil Siswa
                    </h5>
                </div>
                <div class="card-body pt-0">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle">
                            <i class="bi bi-person-fill fs-2"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-0"><?= htmlspecialchars($user['name']) ?></h5>
                            <span class="badge bg-primary-subtle text-primary mt-1">
                                <?= htmlspecialchars($student['class_name'] ?? 'Kelas belum ditentukan') ?>
                            </span>
                        </div>
                    </div>

                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                            <span class="text-muted">NIS:</span>
                            <span class="fw-semibold text-dark"><?= htmlspecialchars($student['nis'] ?? '-') ?></span>
                        </li>
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                            <span class="text-muted">Username:</span>
                            <span class="fw-semibold text-dark"><?= htmlspecialchars($user['username']) ?></span>
                        </li>
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                            <span class="text-muted">Status Model Wajah:</span>
                            <?php if (!empty($student['face_model_identifier'])): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="bi bi-check-circle me-1"></i>Terdaftar AI
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                    <i class="bi bi-exclamation-triangle me-1"></i>Belum Didaftarkan
                                </span>
                            <?php endif; ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Status Presensi Hari Ini & Action FaceAbsen -->
        <div class="col-12 col-md-7">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-calendar-check text-primary me-2"></i>Status Presensi Hari Ini
                    </h5>
                    <span class="badge bg-light text-muted border small"><?= date('d M Y') ?></span>
                </div>
                <div class="card-body pt-0">
                    <?php if (!empty($today_attendance)): ?>
                        <!-- Card Sudah Absen -->
                        <div class="p-3 bg-success-subtle border border-success-subtle rounded-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-success fw-bold">
                                    <i class="bi bi-check-circle-fill me-1"></i> ABSENSI TERCATAT
                                </span>
                                <?php if (strtolower($today_attendance['status'] ?? 'hadir') === 'terlambat'): ?>
                                    <span class="badge bg-warning text-dark px-3 py-1 fs-6">Terlambat</span>
                                <?php else: ?>
                                    <span class="badge bg-success px-3 py-1 fs-6">Hadir</span>
                                <?php endif; ?>
                            </div>
                            <div class="row g-2 text-success-emphasis small">
                                <div class="col-6">
                                    <span>Jam Masuk:</span>
                                    <strong class="d-block fs-6"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($today_attendance['attendance_time'] ?? '-') ?></strong>
                                </div>
                                <div class="col-6">
                                    <span>Metode:</span>
                                    <strong class="d-block"><i class="bi bi-cpu me-1"></i><?= htmlspecialchars($today_attendance['method'] ?? 'face_recognition') ?></strong>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Card Belum Absen -->
                        <div class="p-3 bg-warning-subtle border border-warning-subtle rounded-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-warning-emphasis fw-bold">
                                    <i class="bi bi-clock-history me-1"></i> BELUM MELAKUKAN PRESENSI
                                </span>
                                <span class="badge bg-warning text-dark px-2 py-1">Belum Absen</span>
                            </div>
                            <p class="small text-muted mb-0">Silakan lakukan presensi wajah mandiri melalui kamera perangkat Anda.</p>
                        </div>
                    <?php endif; ?>

                    <!-- Jadwal Waktu Presensi -->
                    <div class="p-3 bg-light rounded-3 mb-3">
                        <div class="row text-center g-2 small">
                            <div class="col-4">
                                <span class="text-muted d-block">Jam Masuk</span>
                                <strong class="text-dark"><?= htmlspecialchars(substr($settings['attendance_start_time'] ?? '06:30:00', 0, 5)) ?></strong>
                            </div>
                            <div class="col-4 border-start border-end">
                                <span class="text-muted d-block">Batas Hadir</span>
                                <strong class="text-warning"><?= htmlspecialchars(substr($settings['attendance_late_time'] ?? '07:15:00', 0, 5)) ?></strong>
                            </div>
                            <div class="col-4">
                                <span class="text-muted d-block">Jam Pulang</span>
                                <strong class="text-dark"><?= htmlspecialchars(substr($settings['attendance_end_time'] ?? '16:00:00', 0, 5)) ?></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Link FaceAbsen -->
                    <div class="d-grid">
                        <a href="<?= BASE_URL ?>scan" class="btn btn-success rounded-pill py-2 fw-semibold shadow-sm">
                            <i class="bi bi-camera-fill me-1"></i> Buka FaceAbsen
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: Riwayat Absensi Terbaru -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-clock-history text-primary me-2"></i>Riwayat Absensi Terbaru
            </h5>
            <span class="badge bg-light text-muted border small"><?= count($recent_attendances) ?> Catatan</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-4">Tanggal</th>
                            <th>Jam Masuk</th>
                            <th>Status Kehadiran</th>
                            <th>Metode Presensi</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_attendances)): ?>
                            <?php foreach ($recent_attendances as $row): ?>
                                <tr>
                                    <td class="ps-4 fw-medium text-dark">
                                        <i class="bi bi-calendar-event text-muted me-1 small"></i>
                                        <?= htmlspecialchars($row['attendance_date'] ?? '-') ?>
                                    </td>
                                    <td>
                                        <i class="bi bi-clock text-muted me-1 small"></i>
                                        <?= htmlspecialchars($row['attendance_time'] ?? '-') ?>
                                    </td>
                                    <td>
                                        <?php if (strtolower($row['status'] ?? 'hadir') === 'terlambat'): ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1">
                                                <i class="bi bi-clock-history me-1"></i>Terlambat
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
                                    <td class="small text-muted">
                                        <?= htmlspecialchars($row['notes'] ?? 'Presensi via AI Teachable Machine') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="bi bi-calendar-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    <p class="mb-0 fw-medium">Belum ada riwayat absensi.</p>
                                    <small class="text-muted">Riwayat kehadiran Anda akan tercatat secara otomatis setelah melakukan presensi.</small>
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
// Chatbot AI Assistant untuk Peserta Didik
$chatbot_role = 'peserta_didik';
require __DIR__ . '/../components/chatbot.php';
?>

