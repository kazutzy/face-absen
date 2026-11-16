<div class="container py-4 flex-grow-1">
    
    <!-- Flash Notification -->
    <?php Flash::display(); ?>

    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">
            
            <!-- Hero Card -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-body p-4 p-md-5 text-center bg-white">
                    <img src="<?= BASE_URL ?>img/logo.png" alt="Logo SMK Bina Warga Lemahabang" class="mb-3 p-2 bg-white rounded-circle shadow-sm" style="width: 105px; height: 105px; object-fit: contain;">
                    <h1 class="h2 fw-bold text-dark mb-1"><?= APP_NAME ?></h1>
                    <div class="h5 fw-bold text-success text-uppercase mb-3">SMK BINA WARGA LEMAHABANG</div>
                    <p class="text-muted lead mb-4">
                        Sistem Presensi Sekolah Modern Berbasis Pengenalan Wajah (Face Recognition) & AI Teachable Machine.
                    </p>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                            <i class="bi bi-check-circle-fill me-1"></i> Arsitektur MVC Siap
                        </span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill">
                            <i class="bi bi-database-check me-1"></i> MySQL Database Ready
                        </span>
                        <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-2 rounded-pill">
                            <i class="bi bi-phone-fill me-1"></i> Mobile Responsive
                        </span>
                    </div>
                </div>
            </div>

            <!-- System Architecture & Setup Overview -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="card-title fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle text-primary"></i> Status Sistem & Entity Database
                    </h5>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3 h-100">
                                <div class="text-muted small">Sekolah</div>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($school_name) ?></div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3 h-100">
                                <div class="text-muted small">Status Arsitektur</div>
                                <div class="fw-bold text-success"><?= htmlspecialchars($stats['status']) ?></div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3 h-100">
                                <div class="text-muted small">Total Akun Terdaftar</div>
                                <div class="fw-bold fs-5 text-primary"><?= (int)$stats['total_users'] ?> Pengguna</div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 bg-light rounded-3 h-100">
                                <div class="text-muted small">Total Kelas / Rombel</div>
                                <div class="fw-bold fs-5 text-primary"><?= (int)$stats['total_classes'] ?> Kelas</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Role Access Card -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="card-title fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-people text-primary"></i> Peran Pengguna (Roles)
                    </h5>
                </div>
                <div class="card-body pt-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                            <div>
                                <span class="fw-semibold text-dark d-block">Super User (Administrator)</span>
                                <small class="text-muted">Mengelola data master, kelas, user, model Teachable Machine, dan rekapitulasi.</small>
                            </div>
                            <span class="badge bg-danger rounded-pill">Admin</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                            <div>
                                <span class="fw-semibold text-dark d-block">Guru</span>
                                <small class="text-muted">Memantau absensi kelas, absensi manual, dan laporan kehadiran.</small>
                            </div>
                            <span class="badge bg-primary rounded-pill">Guru</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                            <div>
                                <span class="fw-semibold text-dark d-block">Peserta Didik</span>
                                <small class="text-muted">Melakukan presensi wajah menggunakan kamera smartphone dan melihat riwayat.</small>
                            </div>
                            <span class="badge bg-success rounded-pill">Siswa</span>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>
