<div class="container py-4 flex-grow-1">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-7">
            
            <!-- Breadcrumb & Header -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/dashboard" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/students" class="text-decoration-none">Peserta Didik</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-dark mb-4">
                <i class="bi bi-pencil-square text-warning me-2"></i>Edit Data Peserta Didik
            </h3>

            <!-- Alert Error -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 py-2 mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                    <small><?= htmlspecialchars($error) ?></small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Form Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-body p-4">
                    <form action="<?= BASE_URL ?>admin/student_update/<?= $student['id'] ?>" method="POST" autocomplete="off">
                        <?= Csrf::field() ?>

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="name" class="form-label small fw-semibold text-secondary">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control bg-light" id="name" name="name" value="<?= htmlspecialchars($student['name']) ?>" required autofocus>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="nis" class="form-label small fw-semibold text-secondary">NIS <span class="text-danger">*</span></label>
                                <input type="text" class="form-control bg-light" id="nis" name="nis" value="<?= htmlspecialchars($student['nis']) ?>" required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="class_id" class="form-label small fw-semibold text-secondary">Kelas <span class="text-danger">*</span></label>
                                <select class="form-select bg-light" id="class_id" name="class_id" required>
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php foreach ($classes as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= $student['class_id'] == $c['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['name']) ?> (Tingkat <?= htmlspecialchars($c['level']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="email" class="form-label small fw-semibold text-secondary">Alamat Email <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control bg-light" id="email" name="email" value="<?= htmlspecialchars($student['email'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="phone" class="form-label small fw-semibold text-secondary">Nomor Telepon / No. HP</label>
                                <input type="text" class="form-control bg-light" id="phone" name="phone" placeholder="Contoh: 081234567890" value="<?= htmlspecialchars($student['phone'] ?? '') ?>">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="username" class="form-label small fw-semibold text-secondary">Username Login <span class="text-danger">*</span></label>
                                <input type="text" class="form-control bg-light" id="username" name="username" value="<?= htmlspecialchars($student['username']) ?>" required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="password" class="form-label small fw-semibold text-secondary">Password Baru (Opsional)</label>
                                <input type="password" class="form-control bg-light" id="password" name="password" placeholder="Kosongkan jika tidak diubah">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="status" class="form-label small fw-semibold text-secondary">Status Akun</label>
                                <select class="form-select bg-light" id="status" name="status">
                                    <option value="active" <?= $student['status'] === 'active' ? 'selected' : '' ?>>Aktif (Bisa Presensi & Login)</option>
                                    <option value="inactive" <?= $student['status'] === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="face_model_identifier" class="form-label small fw-semibold text-secondary">
                                    Label Model AI (Kelas Teachable Machine)
                                </label>
                                <input type="text" class="form-control bg-light" id="face_model_identifier" name="face_model_identifier" placeholder="Default: Otomatis sesuai NIS" value="<?= htmlspecialchars($student['face_model_identifier'] ?? $student['nis']) ?>">
                                <div class="form-text small text-muted">
                                    <i class="bi bi-info-circle me-1"></i>Otomatis menggunakan <strong>NIS</strong> jika dikosongkan.
                                </div>
                            </div>
                        </div>

                        <hr class="my-4 text-muted opacity-25">

                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?= BASE_URL ?>admin/students" class="btn btn-light rounded-pill px-4">Batal</a>
                            <button type="submit" class="btn btn-warning rounded-pill px-4 fw-semibold shadow-sm">
                                <i class="bi bi-check-lg me-1"></i> Perbarui Siswa
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
