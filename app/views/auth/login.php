<div class="container d-flex flex-column justify-content-center align-items-center min-vh-100 py-4">
    <div class="w-100" style="max-width: 420px;">
        
        <!-- Brand Header with Logo -->
        <div class="text-center mb-4">
            <img src="<?= BASE_URL ?>img/logo.png" alt="Logo SMK Bina Warga Lemahabang" class="mb-2 shadow-sm rounded-circle p-1 bg-white" style="width: 85px; height: 85px; object-fit: contain;">
            <h3 class="fw-bold text-dark mb-0"><?= APP_NAME ?></h3>
            <div class="fw-bold text-success text-uppercase small tracking-wide">SMK BINA WARGA LEMAHABANG</div>
            <p class="text-muted small mt-1">Silakan masuk dengan akun terdaftar Anda</p>
        </div>

        <!-- Flash Messages -->
        <?php Flash::display(); ?>

        <!-- Login Card -->
        <div class="card border-0 shadow rounded-4 overflow-hidden">
            <div style="height: 5px; background: linear-gradient(90deg, #0b8043 0%, #f59e0b 100%);"></div>
            <div class="card-body p-4">
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 py-2" role="alert">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                        <small><?= htmlspecialchars($error) ?></small>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="<?= BASE_URL ?>auth/login" method="POST" autocomplete="off">
                    <!-- CSRF Protection Field -->
                    <?= Csrf::field() ?>

                    <div class="mb-3">
                        <label for="username" class="form-label small fw-semibold text-secondary">Username / NIS / NIP</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0 bg-light" id="username" name="username" placeholder="Masukkan username / NIS" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label small fw-semibold text-secondary">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control border-start-0 ps-0 bg-light" id="password" name="password" placeholder="Masukkan password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 rounded-3 fw-semibold shadow-sm">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Sistem
                    </button>
                </form>
            </div>
        </div>

        <div class="text-center mt-3">
            <a href="<?= BASE_URL ?>" class="text-decoration-none small text-muted">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Beranda
            </a>
        </div>

    </div>
</div>
