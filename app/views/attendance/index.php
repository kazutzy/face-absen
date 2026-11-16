<div class="container py-4 flex-grow-1">
    <?php Flash::display(); ?>

    <!-- Header & Navigation -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL . Auth::getDashboardUrl() ?>" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Rekap Absensi</li>
                </ol>
            </nav>
            <h2 class="fw-bold text-dark mb-0">
                <i class="bi bi-clipboard-data-fill text-primary me-2"></i>Rekap Data Absensi
            </h2>
            <p class="text-muted small mb-0">Laporan log kehadiran presensi dengan multi-filter dan ekspor data.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= BASE_URL ?>attendance/monthly" class="btn btn-outline-primary rounded-pill btn-sm px-3 shadow-sm">
                <i class="bi bi-calendar-month me-1"></i> Rekap Bulanan
            </a>
            <?php 
                $queryParams = http_build_query($filters);
            ?>
            <a href="<?= BASE_URL ?>attendance/export_excel?<?= $queryParams ?>" class="btn btn-outline-success rounded-pill btn-sm px-3 shadow-sm" target="_blank">
                <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
            </a>
            <a href="<?= BASE_URL ?>attendance/print?<?= $queryParams ?>" class="btn btn-outline-secondary rounded-pill btn-sm px-3 shadow-sm" target="_blank">
                <i class="bi bi-printer me-1"></i> Cetak / PDF
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-body p-4">
            <form action="<?= BASE_URL ?>attendance" method="GET" class="row g-3 align-items-end">
                <!-- Search Keyword -->
                <div class="col-12 col-md-4 col-lg-3">
                    <label for="search" class="form-label small fw-semibold text-secondary">Pencarian</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control bg-light" id="search" name="search" placeholder="Nama, NIS, NIP..." value="<?= htmlspecialchars($filters['search']) ?>">
                    </div>
                </div>

                <!-- Single Date -->
                <div class="col-6 col-md-4 col-lg-2">
                    <label for="date" class="form-label small fw-semibold text-secondary">Tanggal Tertentu</label>
                    <input type="date" class="form-control form-control-sm bg-light" id="date" name="date" value="<?= htmlspecialchars($filters['date']) ?>">
                </div>

                <!-- Range: Start Date -->
                <div class="col-6 col-md-4 col-lg-2">
                    <label for="start_date" class="form-label small fw-semibold text-secondary">Dari Tanggal</label>
                    <input type="date" class="form-control form-control-sm bg-light" id="start_date" name="start_date" value="<?= htmlspecialchars($filters['start_date']) ?>">
                </div>

                <!-- Range: End Date -->
                <div class="col-6 col-md-4 col-lg-2">
                    <label for="end_date" class="form-label small fw-semibold text-secondary">Sampai Tanggal</label>
                    <input type="date" class="form-control form-control-sm bg-light" id="end_date" name="end_date" value="<?= htmlspecialchars($filters['end_date']) ?>">
                </div>

                <!-- Filter Kelas -->
                <div class="col-6 col-md-4 col-lg-3">
                    <label for="class_id" class="form-label small fw-semibold text-secondary">Kelas / Rombel</label>
                    <select class="form-select form-select-sm bg-light" id="class_id" name="class_id">
                        <option value="">-- Semua Kelas --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($filters['class_id'] == $c['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?> (Tingkat <?= htmlspecialchars($c['level']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($role === 'super_user'): ?>
                    <!-- Filter Role (Khusus Admin) -->
                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="role" class="form-label small fw-semibold text-secondary">Kategori Role</label>
                        <select class="form-select form-select-sm bg-light" id="role" name="role">
                            <option value="">-- Semua --</option>
                            <option value="peserta_didik" <?= ($filters['role'] === 'peserta_didik') ? 'selected' : '' ?>>Peserta Didik</option>
                            <option value="guru" <?= ($filters['role'] === 'guru') ? 'selected' : '' ?>>Guru</option>
                        </select>
                    </div>
                <?php endif; ?>

                <!-- Filter Status -->
                <div class="col-6 col-md-4 col-lg-2">
                    <label for="status" class="form-label small fw-semibold text-secondary">Status Kehadiran</label>
                    <select class="form-select form-select-sm bg-light" id="status" name="status">
                        <option value="">-- Semua Status --</option>
                        <option value="hadir" <?= ($filters['status'] === 'hadir') ? 'selected' : '' ?>>Hadir</option>
                        <option value="terlambat" <?= ($filters['status'] === 'terlambat') ? 'selected' : '' ?>>Terlambat</option>
                        <option value="izin" <?= ($filters['status'] === 'izin') ? 'selected' : '' ?>>Izin</option>
                        <option value="sakit" <?= ($filters['status'] === 'sakit') ? 'selected' : '' ?>>Sakit</option>
                        <option value="alpa" <?= ($filters['status'] === 'alpa' || $filters['status'] === 'tidak_hadir') ? 'selected' : '' ?>>Tidak Hadir (Alpa)</option>
                    </select>
                </div>

                <!-- Sorting Option -->
                <div class="col-6 col-md-4 col-lg-2">
                    <label for="sort" class="form-label small fw-semibold text-secondary">Urutkan</label>
                    <select class="form-select form-select-sm bg-light" id="sort" name="sort">
                        <option value="attendance_date" <?= ($filters['sort'] === 'attendance_date') ? 'selected' : '' ?>>Tanggal</option>
                        <option value="attendance_time" <?= ($filters['sort'] === 'attendance_time') ? 'selected' : '' ?>>Jam</option>
                        <option value="name" <?= ($filters['sort'] === 'name') ? 'selected' : '' ?>>Nama</option>
                        <option value="status" <?= ($filters['sort'] === 'status') ? 'selected' : '' ?>>Status</option>
                    </select>
                </div>

                <!-- Filter Buttons -->
                <div class="col-12 col-md-4 col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold flex-grow-1 shadow-sm">
                        <i class="bi bi-funnel-fill me-1"></i> Terapkan Filter
                    </button>
                    <a href="<?= BASE_URL ?>attendance" class="btn btn-light btn-sm rounded-pill px-3 border" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Data Rekap Absensi -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-table text-primary me-2"></i>Data Log Kehadiran
            </h5>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1">
                Total: <?= number_format($total_rows) ?> Data
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-4" style="width: 50px;">No</th>
                            <th>Nama</th>
                            <th>Kelas</th>
                            <th>Tanggal</th>
                            <th>Jam</th>
                            <th>Status</th>
                            <th>Metode</th>
                            <th>Confidence</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($attendances)): ?>
                            <?php 
                                $no = ($page - 1) * $per_page + 1;
                                foreach ($attendances as $row): 
                            ?>
                                <tr>
                                    <td class="ps-4 text-muted small"><?= $no++ ?></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($row['name'] ?? '-') ?></div>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($row['nis'] ?? ($row['nip'] ?? $row['username'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['class_name'])): ?>
                                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($row['class_name']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border"><?= ucfirst(htmlspecialchars($row['role'] ?? '-')) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['attendance_date'] ?? '-') ?></td>
                                    <td>
                                        <i class="bi bi-clock text-muted me-1 small"></i>
                                        <?= htmlspecialchars($row['attendance_time'] ?? '-') ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $st = strtolower($row['status'] ?? 'hadir');
                                            if ($st === 'terlambat'):
                                        ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1">
                                                <i class="bi bi-clock-history me-1"></i>Terlambat
                                            </span>
                                        <?php elseif ($st === 'izin'): ?>
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill px-3 py-1">
                                                <i class="bi bi-envelope me-1"></i>Izin
                                            </span>
                                        <?php elseif ($st === 'sakit'): ?>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3 py-1">
                                                <i class="bi bi-heart-pulse me-1"></i>Sakit
                                            </span>
                                        <?php elseif ($st === 'alpa' || $st === 'tidak_hadir'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1">
                                                <i class="bi bi-x-circle me-1"></i>Tidak Hadir
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">
                                                <i class="bi bi-check-circle me-1"></i>Hadir
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary border">
                                            <?= htmlspecialchars($row['method'] ?? 'face_recognition') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['confidence_score'])): ?>
                                            <span class="fw-bold text-primary small"><?= htmlspecialchars($row['confidence_score']) ?>%</span>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    <p class="mb-0 fw-medium">Tidak ada data absensi yang sesuai dengan kriteria filter.</p>
                                    <small class="text-muted">Coba ubah filter tanggal, kelas, atau kata kunci pencarian Anda.</small>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="card-footer bg-white border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted">
                    Menampilkan halaman <?= $page ?> dari <?= $total_pages ?> (Total <?= $total_rows ?> entri)
                </small>
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm mb-0">
                        <!-- Tombol Sebelumnya -->
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <?php 
                                $prevFilters = $filters;
                                $prevFilters['page'] = $page - 1;
                            ?>
                            <a class="page-link rounded-pill me-1" href="<?= BASE_URL ?>attendance?<?= http_build_query($prevFilters) ?>" aria-label="Previous">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>

                        <!-- Nomor Halaman -->
                        <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($total_pages, $page + 2);
                            for ($p = $startPage; $p <= $endPage; $p++): 
                                $pageFilters = $filters;
                                $pageFilters['page'] = $p;
                        ?>
                            <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                                <a class="page-link rounded-circle mx-1" href="<?= BASE_URL ?>attendance?<?= http_build_query($pageFilters) ?>">
                                    <?= $p ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <!-- Tombol Berikutnya -->
                        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                            <?php 
                                $nextFilters = $filters;
                                $nextFilters['page'] = $page + 1;
                            ?>
                            <a class="page-link rounded-pill ms-1" href="<?= BASE_URL ?>attendance?<?= http_build_query($nextFilters) ?>" aria-label="Next">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>
