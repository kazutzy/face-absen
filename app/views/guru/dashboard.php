<div class="container py-4 flex-grow-1">
    <?php Flash::display(); ?>

    <!-- Welcome Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">
                <i class="bi bi-person-video3 text-primary me-2"></i>Dashboard Guru
            </h2>
            <p class="text-muted mb-0">Selamat datang, <strong><?= htmlspecialchars($user['name']) ?></strong>. Pantau kehadiran pribadi dan rombongan belajar.</p>
        </div>
        <div>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3 py-2 rounded-pill">
                <i class="bi bi-person-badge me-1"></i> Guru / Tenaga Pendidik
            </span>
        </div>
    </div>

    <!-- Row 1: Status Absensi Pribadi & Rekap Kehadiran Sederhana -->
    <div class="row g-4 mb-4">
        <!-- Status Absensi Pribadi Hari Ini -->
        <div class="col-12 col-md-5">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-person-check text-primary me-2"></i>Presensi Pribadi Hari Ini
                    </h5>
                </div>
                <div class="card-body pt-0">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle">
                            <i class="bi bi-person-circle fs-2"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($user['name']) ?></h6>
                            <small class="text-muted">NIP: <?= htmlspecialchars($teacher['nip'] ?? '-') ?></small>
                        </div>
                    </div>

                    <?php if (!empty($today_attendance)): ?>
                        <div class="p-3 bg-success-subtle border border-success-subtle rounded-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-success small fw-semibold">Status Presensi</span>
                                <?php if (strtolower($today_attendance['status'] ?? 'hadir') === 'terlambat'): ?>
                                    <span class="badge bg-warning text-dark px-2 py-1">Terlambat</span>
                                <?php else: ?>
                                    <span class="badge bg-success px-2 py-1">Hadir Tepat Waktu</span>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex justify-content-between align-items-center small text-success-emphasis">
                                <span>Jam Masuk:</span>
                                <span class="fw-bold"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($today_attendance['attendance_time'] ?? '-') ?></span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="p-3 bg-warning-subtle border border-warning-subtle rounded-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-warning-emphasis small fw-semibold">Status Presensi</span>
                                <span class="badge bg-warning text-dark px-2 py-1">Belum Absen</span>
                            </div>
                            <p class="small text-muted mb-0">Anda belum melakukan presensi hari ini.</p>
                        </div>
                    <?php endif; ?>

                    <div class="d-grid">
                        <a href="<?= BASE_URL ?>scan" class="btn btn-primary rounded-pill py-2 fw-semibold shadow-sm">
                            <i class="bi bi-camera-fill me-1"></i> Buka FaceAbsen
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rekap Kehadiran Sederhana Pribadi & Sekolah -->
        <div class="col-12 col-md-7">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-bar-chart-fill text-primary me-2"></i>Rekap Kehadiran Sederhana
                    </h5>
                    <span class="badge bg-light text-muted border small"><?= date('M Y') ?></span>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-3 text-center mb-3">
                        <div class="col-4">
                            <div class="border rounded-3 bg-light p-3">
                                <div class="small text-muted mb-1">Total Absensi</div>
                                <h4 class="fw-bold text-dark mb-0"><?= (int)($personal_summary['total_attendance'] ?? 0) ?></h4>
                                <small class="text-muted d-block mt-1">Akumulasi</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded-3 bg-light p-3">
                                <div class="small text-muted mb-1">Hadir Tepat Waktu</div>
                                <h4 class="fw-bold text-success mb-0"><?= (int)($personal_summary['hadir'] ?? 0) ?></h4>
                                <small class="text-success d-block mt-1">Tepat Waktu</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded-3 bg-light p-3">
                                <div class="small text-muted mb-1">Terlambat</div>
                                <h4 class="fw-bold text-warning mb-0"><?= (int)($personal_summary['terlambat'] ?? 0) ?></h4>
                                <small class="text-warning d-block mt-1">Terlambat</small>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded-3">
                        <div class="d-flex justify-content-between align-items-center small">
                            <span class="text-muted"><i class="bi bi-buildings text-primary me-1"></i> Kehadiran Sekolah Hari Ini:</span>
                            <span class="fw-bold text-dark">
                                <?= (int)($today_summary['hadir'] ?? 0) ?> Hadir, <?= (int)($today_summary['terlambat'] ?? 0) ?> Terlambat
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: Ringkasan Kehadiran Kelas (Jika Memiliki / Ada Kelas) -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-door-open-fill text-primary me-2"></i>Ringkasan Kehadiran Kelas
            </h5>
            <span class="badge bg-light text-muted border small">Hari Ini</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-4">Nama Rombel / Kelas</th>
                            <th>Tingkat</th>
                            <th class="text-center">Total Siswa</th>
                            <th class="text-center">Hadir</th>
                            <th class="text-center">Terlambat</th>
                            <th class="text-center">Belum Absen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($class_summary)): ?>
                            <?php foreach ($class_summary as $row): ?>
                                <?php 
                                    $totSiswa = (int)($row['total_students'] ?? 0);
                                    $hadirSiswa = (int)($row['hadir'] ?? 0);
                                    $terlambatSiswa = (int)($row['terlambat'] ?? 0);
                                    $belumSiswa = max(0, $totSiswa - ($hadirSiswa + $terlambatSiswa));
                                ?>
                                <tr>
                                    <td class="ps-4 fw-semibold text-dark"><?= htmlspecialchars($row['name'] ?? '-') ?></td>
                                    <td><span class="badge bg-light text-dark border">Kelas <?= htmlspecialchars($row['level'] ?? '-') ?></span></td>
                                    <td class="text-center fw-bold"><?= $totSiswa ?></td>
                                    <td class="text-center text-success fw-bold"><?= $hadirSiswa ?></td>
                                    <td class="text-center text-warning fw-bold"><?= $terlambatSiswa ?></td>
                                    <td class="text-center text-danger fw-bold"><?= $belumSiswa ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-door-closed fs-2 d-block mb-1 text-secondary opacity-50"></i>
                                    Belum ada data rombongan belajar / kelas yang terdaftar.
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
// Chatbot AI Assistant untuk Guru
$chatbot_role = 'guru';
require __DIR__ . '/../components/chatbot.php';
?>

