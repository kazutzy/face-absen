<div class="container py-4 flex-grow-1">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-7">
            
            <!-- Breadcrumb & Header -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/dashboard" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Pengaturan Model AI</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-dark mb-4">
                <i class="bi bi-cpu-fill text-primary me-2"></i>Konfigurasi Model Teachable Machine
            </h3>

            <!-- Flash Message -->
            <?php Flash::display(); ?>

            <!-- Form Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                <div class="card-body p-4">
                    <form action="<?= BASE_URL ?>scan/settings_update" method="POST" autocomplete="off">
                        <?= Csrf::field() ?>

                        <div class="mb-3">
                            <label for="model_url" class="form-label small fw-semibold text-secondary">URL Model Teachable Machine <span class="text-danger">*</span></label>
                            <input type="url" class="form-control bg-light" id="model_url" name="model_url" placeholder="https://teachablemachine.withgoogle.com/models/..." value="<?= htmlspecialchars($model_url) ?>" required>
                            <div class="form-text small">
                                Masukkan tautan ekspor model (shareable link) yang dihasilkan oleh Google Teachable Machine (Image Project).
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="min_confidence" class="form-label small fw-semibold text-secondary">Minimum Confidence Threshold (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control bg-light" id="min_confidence" name="min_confidence" min="10" max="100" step="1" value="<?= htmlspecialchars($min_confidence) ?>" required>
                                <span class="input-group-text bg-light text-muted">%</span>
                            </div>
                            <div class="form-text small">
                                Persentase akurasi minimum agar wajah dinyatakan valid (Rekomendasi: <strong>80% - 90%</strong>).
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="late_time" class="form-label small fw-semibold text-secondary">Jam Batas Keterlambatan <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-clock"></i></span>
                                <input type="time" step="1" class="form-control bg-light" id="late_time" name="late_time" value="<?= htmlspecialchars(substr($late_time ?? '07:15:00', 0, 8)) ?>" required>
                            </div>
                            <div class="form-text small">
                                Batas jam masuk tepat waktu. Presensi setelah jam ini akan otomatis berstatus <strong>Terlambat</strong>.
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded-3 mb-4">
                            <h6 class="fw-bold text-dark small mb-1"><i class="bi bi-lightbulb text-warning me-1"></i> Petunjuk Model Teachable Machine:</h6>
                            <ol class="small text-muted mb-0 ps-3">
                                <li>Buka <a href="https://teachablemachine.withgoogle.com/train/image" target="_blank" class="text-decoration-none">Teachable Machine Image Project</a>.</li>
                                <li>Buat kelas label sesuai dengan NIS / Identifier siswa (misal: <code>siswa_ahmad_fauzi</code>).</li>
                                <li>Tambahkan kelas <code>Background</code> / <code>Kosong</code> untuk melatih kondisi tanpa wajah.</li>
                                <li>Klik <strong>Train Model</strong> lalu <strong>Export Model &rarr; Upload my model</strong>.</li>
                                <li>Salin link yang dihasilkan (contoh: <code>https://teachablemachine.withgoogle.com/models/XYZ123/</code>) dan tempel di form atas.</li>
                            </ol>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="<?= BASE_URL ?>admin/dashboard" class="btn btn-light rounded-pill px-4">Kembali</a>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                                <i class="bi bi-save me-1"></i> Simpan Konfigurasi
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Test Link Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-primary bg-opacity-10 border-primary border-opacity-25">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-primary mb-0">Uji Coba Pemindai Kamera</h6>
                        <small class="text-muted">Buka halaman pemindai untuk menguji live camera & model AI.</small>
                    </div>
                    <a href="<?= BASE_URL ?>scan" class="btn btn-primary rounded-pill btn-sm px-3">
                        <i class="bi bi-camera-fill me-1"></i> Buka Scanner
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>
