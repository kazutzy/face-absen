<div class="container py-4 flex-grow-1">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            
            <!-- Breadcrumb & Header -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/dashboard" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/students" class="text-decoration-none">Peserta Didik</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Detail</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-dark mb-4">
                <i class="bi bi-person-vcard-fill text-info me-2"></i>Detail Profil Siswa
            </h3>

            <!-- Profile Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-3">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle">
                            <i class="bi bi-mortarboard fs-2"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold text-dark mb-0"><?= htmlspecialchars($student['name']) ?></h4>
                            <span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($student['class_name']) ?> (Tingkat <?= htmlspecialchars($student['level']) ?>)</span>
                        </div>
                    </div>

                    <ul class="list-group list-group-flush">
                        <li class="list-group-item px-0 py-3 d-flex justify-content-between">
                            <span class="text-muted">NIS / NISN</span>
                            <span class="fw-semibold text-dark"><?= htmlspecialchars($student['nis']) ?></span>
                        </li>
                        <li class="list-group-item px-0 py-3 d-flex justify-content-between">
                            <span class="text-muted">Alamat Email</span>
                            <?php if (!empty($student['email'])): ?>
                                <a href="mailto:<?= htmlspecialchars($student['email']) ?>" class="fw-semibold text-decoration-none text-primary">
                                    <i class="bi bi-envelope text-primary me-1"></i><?= htmlspecialchars($student['email']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted small">Belum diisi</span>
                            <?php endif; ?>
                        </li>
                        <li class="list-group-item px-0 py-3 d-flex justify-content-between">
                            <span class="text-muted">Nomor Telepon / No. HP</span>
                            <?php if (!empty($student['phone'])): ?>
                                <span class="fw-semibold text-dark"><i class="bi bi-telephone text-primary me-1"></i><?= htmlspecialchars($student['phone']) ?></span>
                            <?php else: ?>
                                <span class="text-muted small">Belum diisi</span>
                            <?php endif; ?>
                        </li>
                        <li class="list-group-item px-0 py-3 d-flex justify-content-between">
                            <span class="text-muted">Username Login</span>
                            <code><?= htmlspecialchars($student['username']) ?></code>
                        </li>
                        <li class="list-group-item px-0 py-3 d-flex justify-content-between">
                            <span class="text-muted">Status Akun</span>
                            <?php if ($student['status'] === 'active'): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Nonaktif</span>
                            <?php endif; ?>
                        </li>
                        <li class="list-group-item px-0 py-3 d-flex justify-content-between">
                            <span class="text-muted">Label Model Wajah AI</span>
                            <?php if (!empty($student['face_model_identifier'])): ?>
                                <span class="badge bg-success-subtle text-success">
                                    <i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($student['face_model_identifier']) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning-subtle text-warning">Belum Terdaftar</span>
                            <?php endif; ?>
                        </li>
                        <li class="list-group-item px-0 py-3 d-flex justify-content-between">
                            <span class="text-muted">Tanggal Terdaftar</span>
                            <span class="text-secondary small"><?= htmlspecialchars($student['created_at']) ?></span>
                        </li>
                    </ul>

                    <div class="d-flex justify-content-between gap-2 mt-4">
                        <a href="<?= BASE_URL ?>admin/students" class="btn btn-outline-secondary rounded-pill px-4">
                            <i class="bi bi-arrow-left me-1"></i> Kembali
                        </a>
                        <div class="d-flex gap-2">
                            <?php if (!empty($student['phone'])): ?>
                                <a href="<?= BASE_URL ?>admin/messages?to=specific&phone=<?= urlencode($student['phone']) ?>&name=<?= urlencode($student['name']) ?>" class="btn btn-success rounded-pill px-3 fw-semibold">
                                    <i class="bi bi-whatsapp me-1"></i> Kirim WA
                                </a>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>admin/student_edit/<?= $student['id'] ?>" class="btn btn-warning rounded-pill px-4 fw-semibold">
                                <i class="bi bi-pencil me-1"></i> Edit Data
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
