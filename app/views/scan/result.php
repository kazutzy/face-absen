<div class="container py-5">
    <?php Flash::display(); ?>

    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-4 p-md-5 text-center">
                    <?php if ($status === 'success'): ?>
                        <div class="mb-4">
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-4 py-2 fs-6 fw-bold">
                                <i class="bi bi-check-circle-fill me-2"></i> ABSENSI BERHASIL
                            </span>
                        </div>

                        <div class="card bg-light border-0 rounded-4 p-4 text-start mb-4">
                            <div class="row g-3">
                                <div class="col-12 border-bottom pb-2">
                                    <span class="text-muted small d-block">Nama</span>
                                    <span class="fw-bold text-dark fs-5"><?= htmlspecialchars($name) ?></span>
                                </div>
                                <div class="col-6 border-bottom pb-2">
                                    <span class="text-muted small d-block">Tanggal</span>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($date) ?></span>
                                </div>
                                <div class="col-6 border-bottom pb-2">
                                    <span class="text-muted small d-block">Jam</span>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($time) ?></span>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted small d-block">Status</span>
                                    <?php if (strtolower($attendance_status) === 'terlambat'): ?>
                                        <span class="badge bg-warning text-dark px-3 py-1 fs-6">Terlambat</span>
                                    <?php else: ?>
                                        <span class="badge bg-success px-3 py-1 fs-6">Hadir</span>
                                    <?php endif; ?>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted small d-block">Confidence</span>
                                    <span class="fw-bold text-primary fs-6"><?= htmlspecialchars($confidence) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($status === 'duplicate'): ?>
                        <div class="mb-4">
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-4 py-2 fs-6 fw-bold">
                                <i class="bi bi-info-circle-fill me-2"></i> SUDAH MELAKUKAN ABSENSI
                            </span>
                        </div>

                        <h4 class="fw-bold text-dark mb-3">Anda sudah melakukan absensi hari ini.</h4>
                        <p class="text-muted mb-4"><?= htmlspecialchars($message) ?></p>
                    <?php else: ?>
                        <div class="mb-4">
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-4 py-2 fs-6 fw-bold">
                                <i class="bi bi-x-circle-fill me-2"></i> VERIFIKASI GAGAL
                            </span>
                        </div>

                        <h4 class="fw-bold text-dark mb-3">Identitas wajah tidak dapat diverifikasi.</h4>
                        <p class="text-muted mb-4"><?= htmlspecialchars($message) ?></p>
                    <?php endif; ?>

                    <div class="mt-4 d-flex justify-content-center gap-2 flex-wrap">
                        <a href="<?= BASE_URL ?>scan" class="btn btn-primary rounded-pill px-4">
                            <i class="bi bi-camera-fill me-1"></i> Kembali ke Scanner
                        </a>
                        <a href="<?= BASE_URL . Auth::getDashboardUrl() ?>" class="btn btn-outline-secondary rounded-pill px-4">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
