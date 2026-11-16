<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Laporan Presensi') ?></title>
    <!-- Bootstrap CSS for layout -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            color: #212529;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .print-container {
            max-width: 900px;
            margin: 20px auto;
            background: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .kop-surat {
            border-bottom: 3px double #212529;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .table-print th, .table-print td {
            border: 1px solid #333 !important;
            padding: 6px 10px;
            font-size: 13px;
        }
        .table-print th {
            background-color: #f0f0f0 !important;
            font-weight: bold;
        }
        @media print {
            body {
                background-color: #ffffff;
                margin: 0;
                padding: 0;
            }
            .print-container {
                max-width: 100%;
                margin: 0;
                padding: 10px;
                box-shadow: none;
                border-radius: 0;
            }
            .no-print {
                display: none !important;
            }
            @page {
                size: A4 portrait;
                margin: 15mm;
            }
        }
    </style>
</head>
<body>

    <!-- Floating Print Control Bar (Hidden on Print) -->
    <div class="no-print bg-dark text-white py-2 sticky-top shadow">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="small">
                <i class="bi bi-printer-fill text-warning me-1"></i> Mode Cetak / Ekspor PDF - <strong><?= APP_NAME ?></strong>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-primary btn-sm px-3 rounded-pill fw-semibold">
                    <i class="bi bi-printer me-1"></i> Cetak Dokumen (PDF)
                </button>
                <button onclick="window.close()" class="btn btn-outline-light btn-sm px-3 rounded-pill">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <div class="print-container">
        <!-- Kop Surat Resmi -->
        <div class="kop-surat text-center position-relative d-flex align-items-center justify-content-center gap-3">
            <img src="<?= BASE_URL ?>img/logo.png" alt="Logo SMK Bina Warga" style="width: 75px; height: 75px; object-fit: contain;">
            <div>
                <h4 class="fw-bold mb-0 text-uppercase fs-6">YAYASAN PENDIDIKAN BINA WARGA</h4>
                <h3 class="fw-bold mb-0 text-uppercase fs-5">SMK BINA WARGA LEMAHABANG</h3>
                <h2 class="fw-bolder mb-1 text-uppercase text-primary fs-4"><?= APP_NAME ?> - PRESENSI SISWA</h2>
                <p class="small text-muted mb-0">Jl. Lemahabang No. 1, Cirebon, Jawa Barat | Website: smkbinawarga.sch.id</p>
            </div>
        </div>

        <!-- Judul Laporan -->
        <div class="text-center mb-4">
            <h5 class="fw-bold mb-1 text-uppercase text-decoration-underline">
                <?= ($type === 'monthly') ? 'REKAPITULASI ABSENSI BULANAN' : 'LAPORAN LOG PRESENSI HARIAN' ?>
            </h5>
            <small class="text-muted">
                <?php if ($type === 'monthly'): ?>
                    Periode: Bulan <?= $month ?> Tahun <?= $year ?>
                <?php else: ?>
                    Dicetak pada: <?= date('d-m-Y H:i:s') ?> | Oleh: <?= htmlspecialchars($user['name']) ?>
                <?php endif; ?>
            </small>
        </div>

        <?php if ($type === 'monthly'): ?>
            <!-- Tabel Rekap Bulanan -->
            <table class="table table-bordered table-print w-100 text-center mb-4">
                <thead>
                    <tr>
                        <th style="width: 40px;">No</th>
                        <th class="text-start">Nama Lengkap</th>
                        <th>NIS / Role</th>
                        <th>Kelas</th>
                        <th>Hadir</th>
                        <th>Terlambat</th>
                        <th>Izin</th>
                        <th>Sakit</th>
                        <th>Tidak Hadir</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recap)): ?>
                        <?php 
                            $no = 1;
                            $totH = 0; $totT = 0; $totI = 0; $totS = 0; $totA = 0; $totAll = 0;
                            foreach ($recap as $r): 
                                $h = (int)($r['total_hadir'] ?? 0);
                                $t = (int)($r['total_terlambat'] ?? 0);
                                $i = (int)($r['total_izin'] ?? 0);
                                $s = (int)($r['total_sakit'] ?? 0);
                                $a = (int)($r['total_tidak_hadir'] ?? 0);
                                $all = (int)($r['total_kehadiran'] ?? 0);

                                $totH += $h; $totT += $t; $totI += $i; $totS += $s; $totA += $a; $totAll += $all;
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="text-start"><?= htmlspecialchars($r['name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($r['nis'] ?? ($r['nip'] ?? $r['role'])) ?></td>
                                <td><?= htmlspecialchars($r['class_name'] ?? '-') ?></td>
                                <td><?= $h ?></td>
                                <td><?= $t ?></td>
                                <td><?= $i ?></td>
                                <td><?= $s ?></td>
                                <td><?= $a ?></td>
                                <td class="fw-bold"><?= $all ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="fw-bold" style="background-color: #f9f9f9;">
                            <td colspan="4" class="text-end pe-2">TOTAL KESELURUHAN:</td>
                            <td><?= $totH ?></td>
                            <td><?= $totT ?></td>
                            <td><?= $totI ?></td>
                            <td><?= $totS ?></td>
                            <td><?= $totA ?></td>
                            <td><?= $totAll ?></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="py-3 text-muted">Tidak ada data rekapitulasi pada periode ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php else: ?>
            <!-- Tabel Log Harian -->
            <table class="table table-bordered table-print w-100 mb-4">
                <thead class="text-center">
                    <tr>
                        <th style="width: 40px;">No</th>
                        <th>Nama</th>
                        <th>NIS / Role</th>
                        <th>Kelas</th>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th>Status</th>
                        <th>Metode</th>
                        <th>Akurasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($attendances)): ?>
                        <?php $no = 1; foreach ($attendances as $row): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= htmlspecialchars($row['name'] ?? '-') ?></td>
                                <td class="text-center"><?= htmlspecialchars($row['nis'] ?? ($row['nip'] ?? $row['role'])) ?></td>
                                <td class="text-center"><?= htmlspecialchars($row['class_name'] ?? '-') ?></td>
                                <td class="text-center"><?= htmlspecialchars($row['attendance_date'] ?? '-') ?></td>
                                <td class="text-center"><?= htmlspecialchars($row['attendance_time'] ?? '-') ?></td>
                                <td class="text-center fw-bold"><?= strtoupper(htmlspecialchars($row['status'] ?? '-')) ?></td>
                                <td class="text-center"><?= htmlspecialchars($row['method'] ?? '-') ?></td>
                                <td class="text-center"><?= !empty($row['confidence_score']) ? htmlspecialchars($row['confidence_score']) . '%' : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-3 text-muted">Tidak ada data presensi yang ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Lembar Pengesahan / Tanda Tangan -->
        <div class="row mt-5 pt-3">
            <div class="col-6 text-center">
                <p class="mb-1 small">Mengetahui,</p>
                <p class="fw-bold mb-5 small">Kepala Sekolah</p>
                <p class="fw-bold mb-0 text-decoration-underline">Drs. H. Mulyadi, M.Pd</p>
                <small class="text-muted">NIP. 196805141993031004</small>
            </div>
            <div class="col-6 text-center">
                <p class="mb-1 small">Kota, <?= date('d F Y') ?></p>
                <p class="fw-bold mb-5 small">Petugas / Operator Presensi</p>
                <p class="fw-bold mb-0 text-decoration-underline"><?= htmlspecialchars($user['name']) ?></p>
                <small class="text-muted">ID: <?= htmlspecialchars($user['username']) ?></small>
            </div>
        </div>
    </div>

</body>
</html>
