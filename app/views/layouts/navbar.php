<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= BASE_URL ?>">
            <img src="<?= BASE_URL ?>img/logo.png" alt="Logo SMK Bina Warga Lemahabang" width="34" height="34" class="rounded-circle bg-white p-1 shadow-sm">
            <span><?= APP_NAME ?></span>
            <span class="badge bg-warning text-dark fw-bold ms-1 d-none d-sm-inline-block small" style="font-size: 0.7rem;">SMK BW</span>
        </a>
        
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>"><i class="bi bi-house-door me-1"></i> Beranda</a>
                </li>
                <?php if (Auth::check()): ?>
                    <li class="nav-item">
                        <a class="nav-link fw-medium text-white" href="<?= BASE_URL . Auth::getDashboardUrl() ?>">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium text-warning" href="<?= BASE_URL ?>scan">
                            <i class="bi bi-camera-fill me-1"></i> Presensi Wajah
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium text-white" href="<?= BASE_URL ?>attendance">
                            <i class="bi bi-clipboard-data me-1"></i> Rekap Absensi
                        </a>
                    </li>
                    <?php if (Auth::role() === 'super_user'): ?>
                        <?php 
                            $currUrl = trim($_GET['url'] ?? '', '/');
                            $isApiKeyActive = strpos($currUrl, 'admin/api_key') === 0 || strpos($currUrl, 'admin/api-key') === 0;
                            $isMasterActive = strpos($currUrl, 'admin/student') === 0 || strpos($currUrl, 'admin/teacher') === 0 || strpos($currUrl, 'admin/class') === 0;
                            $isMessagesActive = strpos($currUrl, 'admin/message') === 0 || strpos($currUrl, 'admin/broadcast') === 0;
                        ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-white fw-medium <?= $isMasterActive ? 'active fw-bold' : '' ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-database me-1"></i> Master Data
                            </a>
                            <ul class="dropdown-menu shadow border-0">
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>admin/students"><i class="bi bi-mortarboard-fill me-2 text-primary"></i> Data Siswa</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>admin/teachers"><i class="bi bi-person-video3 me-2 text-info"></i> Data Guru</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>admin/classes"><i class="bi bi-door-open-fill me-2 text-warning"></i> Data Kelas</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-medium <?= $isMessagesActive ? 'active fw-bold text-success' : 'text-white' ?>" href="<?= BASE_URL ?>admin/messages">
                                <i class="bi bi-whatsapp text-success me-1"></i> Kirim Pesan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-medium <?= $isApiKeyActive ? 'active fw-bold text-warning' : 'text-white' ?>" href="<?= BASE_URL ?>admin/api_keys">
                                <i class="bi bi-key-fill text-warning me-1"></i> Konfigurasi API Key
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav ms-auto align-items-lg-center gap-2">
                <?php if (Auth::check()): ?>
                    <?php 
                        $user = Auth::user(); 
                        $badgeClass = 'bg-light text-dark';
                        $roleLabel = 'User';
                        if ($user['role'] === 'super_user') {
                            $badgeClass = 'bg-danger text-white';
                            $roleLabel = 'Super User';
                        } elseif ($user['role'] === 'guru') {
                            $badgeClass = 'bg-info text-dark';
                            $roleLabel = 'Guru';
                        } elseif ($user['role'] === 'peserta_didik') {
                            $badgeClass = 'bg-success text-white';
                            $roleLabel = 'Siswa';
                        }
                    ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white d-flex align-items-center gap-2 py-1 px-2 rounded-3 bg-white bg-opacity-10" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle fs-5"></i>
                            <span class="small fw-semibold"><?= htmlspecialchars($user['name']) ?></span>
                            <span class="badge <?= $badgeClass ?> rounded-pill small"><?= $roleLabel ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                            <li>
                                <div class="px-3 py-2 border-bottom">
                                    <small class="text-muted d-block">Login sebagai:</small>
                                    <strong class="text-dark small"><?= htmlspecialchars($user['username']) ?></strong>
                                </div>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="<?= BASE_URL . Auth::getDashboardUrl() ?>">
                                    <i class="bi bi-speedometer2 me-2 text-primary"></i> Dashboard
                                </a>
                            </li>
                            <?php if (Auth::role() === 'super_user'): ?>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>admin/students"><i class="bi bi-mortarboard me-2 text-primary"></i> Data Siswa</a></li>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>admin/teachers"><i class="bi bi-person-video3 me-2 text-info"></i> Data Guru</a></li>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>admin/classes"><i class="bi bi-door-open me-2 text-warning"></i> Data Kelas</a></li>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>admin/messages"><i class="bi bi-whatsapp me-2 text-success"></i> Kirim Pesan WhatsApp</a></li>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>admin/api_keys"><i class="bi bi-key-fill me-2 text-warning"></i> Konfigurasi API Key</a></li>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>scan/settings"><i class="bi bi-sliders me-2 text-secondary"></i> Model AI & Jam Presensi</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item py-2 text-danger" href="<?= BASE_URL ?>auth/logout">
                                    <i class="bi bi-box-arrow-right me-2"></i> Keluar (Logout)
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="btn btn-outline-light rounded-pill px-4 btn-sm fw-semibold" href="<?= BASE_URL ?>auth/login">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
