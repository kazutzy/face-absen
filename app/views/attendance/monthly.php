<div class="container py-4 flex-grow-1">
    <?php Flash::display(); ?>

    <!-- Header & Navigation -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL . Auth::getDashboardUrl() ?>" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>attendance" class="text-decoration-none">Rekap Absensi</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Rekap Bulanan</li>
                </ol>
            </nav>
            <h2 class="fw-bold text-dark mb-0">
                <i class="bi bi-calendar-month-fill text-primary me-2"></i>Rekap Absensi Bulanan
            </h2>
            <p class="text-muted small mb-0">Akumulasi rekapitulasi kehadiran siswa dan guru per bulan.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= BASE_URL ?>attendance" class="btn btn-outline-primary rounded-pill btn-sm px-3 shadow-sm">
                <i class="bi bi-list-ul me-1"></i> Log Harian
            </a>
            <?php 
                $monthQueryParams = http_build_query([
                    'type' => 'monthly',
                    'month' => $month,
                    'year' => $year,
                    'class_id' => $class_id,
                    'role' => $filter_role
                ]);
            ?>
            <a href="<?= BASE_URL ?>attendance/print?<?= $monthQueryParams ?>" class="btn btn-outline-secondary rounded-pill btn-sm px-3 shadow-sm" target="_blank">
                <i class="bi bi-printer me-1"></i> Cetak / PDF
            </a>
        </div>
    </div>

    <!-- Filter Card Bulanan -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-body p-4">
            <form action="<?= BASE_URL ?>attendance/monthly" method="GET" class="row g-3 align-items-end">
                <!-- Filter Bulan -->
                <div class="col-6 col-md-3 col-lg-3">
                    <label for="month" class="form-label small fw-semibold text-secondary">Bulan</label>
                    <select class="form-select form-select-sm bg-light" id="month" name="month">
                        <?php 
                            $indonesianMonths = [
                                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                            ];
                            foreach ($indonesianMonths as $mNum => $mName):
                        ?>
                            <option value="<?= $mNum ?>" <?= ($month == $mNum) ? 'selected' : '' ?>>
                                <?= $mName ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filter Tahun -->
                <div class="col-6 col-md-3 col-lg-2">
                    <label for="year" class="form-label small fw-semibold text-secondary">Tahun</label>
                    <select class="form-select form-select-sm bg-light" id="year" name="year">
                        <?php 
                            $curYear = (int)date('Y');
                            for ($y = $curYear - 2; $y <= $curYear + 1; $y++):
                        ?>
                            <option value="<?= $y ?>" <?= ($year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <!-- Filter Kelas -->
                <div class="col-6 col-md-3 col-lg-3">
                    <label for="class_id" class="form-label small fw-semibold text-secondary">Kelas / Rombel</label>
                    <select class="form-select form-select-sm bg-light" id="class_id" name="class_id">
                        <option value="">-- Semua Kelas --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($class_id == $c['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?> (Tingkat <?= htmlspecialchars($c['level']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($role === 'super_user'): ?>
                    <!-- Filter Role -->
                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="role" class="form-label small fw-semibold text-secondary">Role</label>
                        <select class="form-select form-select-sm bg-light" id="role" name="role">
                            <option value="">-- Semua --</option>
                            <option value="peserta_didik" <?= ($filter_role === 'peserta_didik') ? 'selected' : '' ?>>Peserta Didik</option>
                            <option value="guru" <?= ($filter_role === 'guru') ? 'selected' : '' ?>>Guru</option>
                        </select>
                    </div>
                <?php endif; ?>

                <!-- Tombol Submit -->
                <div class="col-12 col-md-3 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold flex-grow-1 shadow-sm">
                        <i class="bi bi-funnel-fill me-1"></i> Tampilkan
                    </button>
                    <a href="<?= BASE_URL ?>attendance/monthly" class="btn btn-light btn-sm rounded-pill px-3 border" title="Reset">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Rekapitulasi Bulanan -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-calendar2-check-fill text-primary me-2"></i>
                Rekapitulasi Periode <?= $indonesianMonths[$month] ?? $month ?> <?= $year ?>
            </h5>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1">
                Total: <?= count($recap) ?> Pengguna
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle mb-0">
                    <thead class="table-light text-center small">
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th class="text-start">Nama Lengkap</th>
                            <th>Kelas / Role</th>
                            <th class="table-success" style="width: 90px;">Hadir</th>
                            <th class="table-warning" style="width: 90px;">Terlambat</th>
                            <th class="table-info" style="width: 90px;">Izin</th>
                            <th class="table-secondary" style="width: 90px;">Sakit</th>
                            <th class="table-danger" style="width: 100px;">Tidak Hadir</th>
                            <th class="table-primary" style="width: 90px;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recap)): ?>
                            <?php 
                                $no = 1;
                                $grandHadir = 0;
                                $grandLate = 0;
                                $grandIzin = 0;
                                $grandSakit = 0;
                                $grandAlpa = 0;
                                $grandTotal = 0;

                                foreach ($recap as $row): 
                                    $h = (int)($row['total_hadir'] ?? 0);
                                    $t = (int)($row['total_terlambat'] ?? 0);
                                    $i = (int)($row['total_izin'] ?? 0);
                                    $s = (int)($row['total_sakit'] ?? 0);
                                    $a = (int)($row['total_tidak_hadir'] ?? 0);
                                    $tot = (int)($row['total_kehadiran'] ?? 0);

                                    $grandHadir += $h;
                                    $grandLate += $t;
                                    $grandIzin += $i;
                                    $grandSakit += $s;
                                    $grandAlpa += $a;
                                    $grandTotal += $tot;
                            ?>
                                <tr>
                                    <td class="text-center text-muted small"><?= $no++ ?></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($row['name'] ?? '-') ?></div>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($row['nis'] ?? ($row['nip'] ?? $row['username'])) ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($row['class_name'])): ?>
                                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($row['class_name']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border"><?= ucfirst(htmlspecialchars($row['role'] ?? '-')) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center fw-bold text-success"><?= $h ?></td>
                                    <td class="text-center fw-bold text-warning"><?= $t ?></td>
                                    <td class="text-center fw-bold text-info"><?= $i ?></td>
                                    <td class="text-center fw-bold text-secondary"><?= $s ?></td>
                                    <td class="text-center fw-bold text-danger"><?= $a ?></td>
                                    <td class="text-center fw-bold text-primary"><?= $tot ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">
                                    <i class="bi bi-calendar-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    <p class="mb-0 fw-medium">Belum ada rekapitulasi data absensi pada periode ini.</p>
                                    <small class="text-muted">Presensi yang masuk otomatis direkapitulasi secara berkala.</small>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($recap)): ?>
                        <tfoot class="table-light text-center fw-bold">
                            <tr>
                                <td colspan="3" class="text-end pe-3">TOTAL KESELURUHAN:</td>
                                <td class="text-success"><?= $grandHadir ?></td>
                                <td class="text-warning"><?= $grandLate ?></td>
                                <td class="text-info"><?= $grandIzin ?></td>
                                <td class="text-secondary"><?= $grandSakit ?></td>
                                <td class="text-danger"><?= $grandAlpa ?></td>
                                <td class="text-primary"><?= $grandTotal ?></td>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>
