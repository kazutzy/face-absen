<div class="container py-4">
    <!-- Breadcrumb & Header -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-1">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Kirim Pesan WhatsApp</li>
        </ol>
    </nav>

    <!-- Flash Alert -->
    <?php Flash::display(); ?>

    <!-- Banner & Status Gateway Fonnte -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-body p-4 bg-gradient" style="background: linear-gradient(135deg, #128c7e 0%, #075e54 100%); color: white;">
            <div class="row align-items-center g-3">
                <div class="col-lg-7">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-white p-3 text-success shadow-sm d-flex align-items-center justify-content-center" style="width: 58px; height: 58px;">
                            <i class="bi bi-whatsapp fs-2"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-1">WhatsApp Gateway Fonnte</h4>
                            <p class="mb-0 text-white-50 small">Kirim pengumuman, pemberitahuan presensi, dan pesan langsung ke Guru maupun Siswa secara otomatis.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 text-lg-end">
                    <div class="d-inline-flex flex-wrap gap-2 justify-content-lg-end">
                        <button type="button" class="btn btn-light btn-sm fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalSettingsKey">
                            <i class="bi bi-key-fill text-warning me-1"></i> Pengaturan Token API
                        </button>
                        <button type="button" class="btn btn-outline-light btn-sm fw-semibold" id="btnRefreshStatus" onclick="refreshDeviceStatus()">
                            <i class="bi bi-arrow-clockwise me-1"></i> Segarkan Status
                        </button>
                    </div>
                </div>
            </div>

            <hr class="my-3 opacity-25">

            <!-- Detail Status Perangkat -->
            <div class="row g-2 text-dark">
                <?php 
                    $devStatus = $device_status['device_status'] ?? ($device_status['status'] === true ? 'connect' : 'disconnect');
                    $isConnect = strtolower($devStatus) === 'connect';
                    $quota = $device_status['quota'] ?? '-';
                    $deviceNumber = $device_status['device'] ?? '-';
                    $deviceName = $device_status['name'] ?? '-';
                    $package = $device_status['package'] ?? 'Free';
                    $expired = $device_status['expired'] ?? '-';
                ?>
                <div class="col-6 col-md-3">
                    <div class="bg-white rounded-3 p-2 px-3 shadow-sm h-100">
                        <small class="text-muted d-block" style="font-size: 0.75rem;">Status Perangkat</small>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span class="badge <?= $isConnect ? 'bg-success' : 'bg-danger' ?> rounded-pill px-2 py-1">
                                <i class="bi <?= $isConnect ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?> me-1"></i>
                                <span id="valDeviceStatus"><?= $isConnect ? 'Terhubung (Connect)' : 'Terputus (Disconnect)' ?></span>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="bg-white rounded-3 p-2 px-3 shadow-sm h-100">
                        <small class="text-muted d-block" style="font-size: 0.75rem;">Nomor WhatsApp Pengirim</small>
                        <strong class="text-dark small d-block mt-1 font-monospace" id="valDeviceNumber">
                            <i class="bi bi-phone me-1 text-success"></i><?= htmlspecialchars($deviceNumber) ?>
                        </strong>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="bg-white rounded-3 p-2 px-3 shadow-sm h-100">
                        <small class="text-muted d-block" style="font-size: 0.75rem;">Sisa Kuota Pesan</small>
                        <strong class="text-primary small d-block mt-1" id="valDeviceQuota">
                            <i class="bi bi-chat-dots-fill me-1"></i><?= htmlspecialchars($quota) ?> Pesan
                        </strong>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="bg-white rounded-3 p-2 px-3 shadow-sm h-100">
                        <small class="text-muted d-block" style="font-size: 0.75rem;">Paket & Masa Aktif</small>
                        <strong class="text-dark small d-block mt-1" style="font-size: 0.8rem;" id="valDevicePackage">
                            <?= htmlspecialchars($package) ?> (s/d <?= htmlspecialchars($expired) ?>)
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Konten Utama: 2 Kolom (Form Kirim & Riwayat) -->
    <div class="row g-4">
        <!-- Kolom Kiri: Form Buat Pesan -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                    <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-send-fill text-success"></i> Buat & Kirim Pesan
                    </h5>
                    <p class="text-muted small mb-0">Pilih target penerima dan buat pesan yang ingin dikirimkan.</p>
                </div>
                <div class="card-body p-4">
                    <form action="<?= BASE_URL ?>admin/message_send" method="POST" id="formSendMessage">
                        <?= Csrf::field() ?>

                        <!-- 1. Kategori Penerima -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted text-uppercase">1. Target Penerima <span class="text-danger">*</span></label>
                            
                            <div class="d-flex flex-column gap-2">
                                <div class="form-check p-3 border rounded-3 bg-light bg-opacity-50">
                                    <input class="form-check-input" type="radio" name="recipient_type" id="rcptTeachers" value="all_teachers" onchange="toggleRecipientType()" <?= ($prefill['target'] === 'all_teachers') ? 'checked' : '' ?>>
                                    <label class="form-check-label w-100 cursor-pointer" for="rcptTeachers">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong class="d-block text-dark"><i class="bi bi-person-video3 text-info me-1"></i> Semua Guru</strong>
                                                <small class="text-muted">Kirimkan broadcast ke seluruh tenaga pengajar</small>
                                            </div>
                                            <span class="badge bg-info text-dark rounded-pill"><?= count($teachers) ?> guru bertelpon</span>
                                        </div>
                                    </label>
                                </div>

                                <div class="form-check p-3 border rounded-3 bg-light bg-opacity-50">
                                    <input class="form-check-input" type="radio" name="recipient_type" id="rcptStudents" value="all_students" onchange="toggleRecipientType()" <?= ($prefill['target'] === 'all_students') ? 'checked' : '' ?>>
                                    <label class="form-check-label w-100 cursor-pointer" for="rcptStudents">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong class="d-block text-dark"><i class="bi bi-mortarboard-fill text-primary me-1"></i> Semua Siswa</strong>
                                                <small class="text-muted">Kirimkan broadcast ke seluruh peserta didik aktif</small>
                                            </div>
                                            <span class="badge bg-primary rounded-pill"><?= count($students) ?> siswa bertelpon</span>
                                        </div>
                                    </label>
                                </div>

                                <div class="form-check p-3 border rounded-3 bg-light bg-opacity-50">
                                    <input class="form-check-input" type="radio" name="recipient_type" id="rcptClass" value="class" onchange="toggleRecipientType()" <?= ($prefill['target'] === 'class') ? 'checked' : '' ?>>
                                    <label class="form-check-label w-100 cursor-pointer" for="rcptClass">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong class="d-block text-dark"><i class="bi bi-door-open-fill text-warning me-1"></i> Per Rombel / Kelas Tertentu</strong>
                                                <small class="text-muted">Pilih satu kelas tujuan broadcast</small>
                                            </div>
                                            <span class="badge bg-warning text-dark rounded-pill"><?= count($classes) ?> kelas</span>
                                        </div>
                                    </label>
                                </div>

                                <div class="form-check p-3 border rounded-3 bg-light bg-opacity-50">
                                    <input class="form-check-input" type="radio" name="recipient_type" id="rcptSpecific" value="specific" onchange="toggleRecipientType()" <?= ($prefill['target'] === 'specific' || !empty($prefill['phone']) || empty($prefill['target'])) ? 'checked' : '' ?>>
                                    <label class="form-check-label w-100 cursor-pointer" for="rcptSpecific">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong class="d-block text-dark"><i class="bi bi-person-fill text-secondary me-1"></i> Perorangan / Nomor Spesifik</strong>
                                                <small class="text-muted">Pilih satu guru/siswa atau ketik nomor WhatsApp bebas</small>
                                            </div>
                                            <span class="badge bg-secondary rounded-pill">1 Penerima</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Opsi Dinamis: Pilihan Rombel / Kelas -->
                        <div class="mb-3 d-none" id="containerClassSelect">
                            <label class="form-label fw-semibold small text-muted text-uppercase">Pilih Kelas / Rombel <span class="text-danger">*</span></label>
                            <select name="class_id" id="selectClass" class="form-select rounded-3">
                                <option value="">-- Pilih Kelas Tujuan --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>">
                                        Kelas <?= htmlspecialchars($c['class_name']) ?> (Tingkat <?= htmlspecialchars($c['level']) ?>) - <?= (int)$c['total_with_phone'] ?> siswa bertelpon
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Opsi Dinamis: Pilihan Perorangan / Kontak Spesifik -->
                        <div class="mb-3 d-none" id="containerSpecificSelect">
                            <label class="form-label fw-semibold small text-muted text-uppercase">Pilih Kontak atau Masukkan Nomor <span class="text-danger">*</span></label>
                            
                            <select name="specific_target" id="selectSpecificContact" class="form-select rounded-3 mb-2" onchange="handleSpecificContactChange()">
                                <option value="">-- Pilih dari Daftar Kontak Guru / Siswa --</option>
                                <optgroup label="Daftar Guru (Tenaga Pengajar)">
                                    <?php foreach ($teachers as $t): ?>
                                        <option value="guru|<?= $t['id'] ?>|<?= htmlspecialchars($t['phone']) ?>|<?= htmlspecialchars($t['name']) ?>" <?= ($prefill['phone'] === $t['phone']) ? 'selected' : '' ?>>
                                            [Guru] <?= htmlspecialchars($t['name']) ?> - Telp: <?= htmlspecialchars($t['phone']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Daftar Siswa (Peserta Didik)">
                                    <?php foreach ($students as $s): ?>
                                        <option value="siswa|<?= $s['id'] ?>|<?= htmlspecialchars($s['phone']) ?>|<?= htmlspecialchars($s['name']) ?>" <?= ($prefill['phone'] === $s['phone']) ? 'selected' : '' ?>>
                                            [Siswa] <?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['class_name']) ?>) - Telp: <?= htmlspecialchars($s['phone']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>

                            <div class="p-2 border rounded-3 bg-light">
                                <small class="text-muted d-block mb-1">Atau masukkan nomor WhatsApp langsung (jika di luar database):</small>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class="bi bi-telephone-plus"></i></span>
                                    <input type="tel" name="custom_phone" id="inputCustomPhone" class="form-control" placeholder="Contoh: 081234567890" value="<?= htmlspecialchars($prefill['phone']) ?>">
                                </div>
                                <input type="hidden" name="custom_name" id="inputCustomName" value="<?= htmlspecialchars($prefill['name']) ?>">
                            </div>
                        </div>

                        <!-- 2. Isi Pesan & Pilihan Template -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold small text-muted text-uppercase mb-0">2. Isi Pesan WhatsApp <span class="text-danger">*</span></label>
                                <span class="badge bg-light text-secondary border small" id="charCounter">0 Karakter</span>
                            </div>

                            <!-- Pilihan Template Pesan Siap Pakai -->
                            <div class="mb-2 p-2 rounded-3 bg-light border">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-semibold small text-primary mb-0" style="font-size: 0.78rem;">
                                        <i class="bi bi-file-earmark-text-fill me-1"></i> Template Pesan Cepat:
                                    </label>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-danger" style="font-size: 0.72rem;" onclick="resetMessageText()">
                                        <i class="bi bi-x-circle me-1"></i>Hapus/Reset
                                    </button>
                                </div>
                                <select id="selectMessageTemplate" class="form-select form-select-sm rounded-3" onchange="applyMessageTemplate()">
                                    <option value="">-- Pilih Template Pesan (Otomatis Mengisi Teks) --</option>
                                    <optgroup label="Pemberitahuan Siswa & Wali Murid">
                                        <option value="keterlambatan_siswa">⚠️ Pemberitahuan Siswa Terlambat Hadir</option>
                                        <option value="belum_absen_siswa">⏰ Pengingat Siswa Belum Absen Hari Ini</option>
                                        <option value="panggilan_wali">📝 Undangan Pertemuan / Konseling Wali Murid</option>
                                        <option value="prestasi_siswa">⭐ Apresiasi Kedisiplinan / Kehadiran Siswa</option>
                                    </optgroup>
                                    <optgroup label="Pemberitahuan Guru & Tenaga Pendidik">
                                        <option value="rapat_guru">👨‍🏫 Undangan Rapat Koordinasi Dewan Guru</option>
                                        <option value="rekap_guru">📊 Pengingat Validasi Jurnal & Presensi Kelas</option>
                                        <option value="jadwal_piket">📋 Informasi Tugas Piket Guru</option>
                                    </optgroup>
                                    <optgroup label="Pengumuman Sekolah Umum">
                                        <option value="pengumuman_umum">📢 Pengumuman Kegiatan Resmi Sekolah</option>
                                        <option value="libur_sekolah">🏖️ Pemberitahuan Hari Libur / Cuti Bersama</option>
                                        <option value="ujian_sekolah">📑 Pengumuman Jadwal Asesmen / Ujian</option>
                                    </optgroup>
                                </select>
                            </div>

                            <!-- Tag Variabel Cepat -->
                            <div class="d-flex flex-wrap gap-1 mb-2">
                                <small class="text-muted me-1 align-self-center" style="font-size: 0.75rem;">Sisipkan Tag:</small>
                                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 rounded-pill" style="font-size: 0.72rem;" onclick="insertTag('{nama}')">+ {nama}</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 rounded-pill" style="font-size: 0.72rem;" onclick="insertTag('{sekolah}')">+ {sekolah}</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 rounded-pill" style="font-size: 0.72rem;" onclick="insertTag('{tanggal}')">+ {tanggal}</button>
                            </div>

                            <textarea name="message" id="inputMessage" class="form-control font-monospace rounded-3" rows="7" placeholder="Pilih template di atas atau ketik pesan WhatsApp di sini..." required oninput="updateCharCount()"></textarea>
                            
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <small class="text-muted" style="font-size: 0.75rem;">
                                    Format WA: *tebal*, _miring_, ~coret~
                                </small>
                                <div class="d-flex align-items-center gap-1">
                                    <label class="small text-muted mb-0" style="font-size: 0.75rem;">Jeda blast:</label>
                                    <select name="delay" class="form-select form-select-sm py-0 px-2" style="font-size: 0.75rem; width: auto;">
                                        <option value="1">1 Detik</option>
                                        <option value="2" selected>2 Detik (Aman)</option>
                                        <option value="3">3 Detik</option>
                                        <option value="5">5 Detik</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Submit Kirim -->
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-success py-2 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" id="btnSubmitSend">
                                <i class="bi bi-whatsapp fs-5"></i> Kirim Pesan Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Riwayat Pengiriman (Outbox) -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-clock-history text-primary"></i> Riwayat Pengiriman Pesan
                        </h5>
                        <p class="text-muted small mb-0">Daftar log pesan yang telah diproses oleh gateway Fonnte.</p>
                    </div>
                    <div>
                        <span class="badge bg-light text-dark border px-3 py-2 rounded-pill small">
                            Total: <?= (int)($stats['total_broadcasts'] ?? 0) ?> Pengiriman
                        </span>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-muted text-uppercase">
                                <tr>
                                    <th class="ps-4" style="width: 150px;">Waktu</th>
                                    <th>Kategori & Target</th>
                                    <th>Isi Pesan</th>
                                    <th class="text-center" style="width: 100px;">Status</th>
                                    <th class="text-end pe-4" style="width: 90px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($messages)): ?>
                                    <?php foreach ($messages as $m): ?>
                                        <?php 
                                            $badgeClass = 'bg-success';
                                            $statusText = 'Terkirim';
                                            if ($m['status'] === 'failed') {
                                                $badgeClass = 'bg-danger';
                                                $statusText = 'Gagal';
                                            } elseif ($m['status'] === 'partial') {
                                                $badgeClass = 'bg-warning text-dark';
                                                $statusText = 'Sebagian';
                                            }

                                            $rcptLabel = 'Perorangan';
                                            $rcptIcon = 'bi-person';
                                            if ($m['recipient_type'] === 'all_teachers') {
                                                $rcptLabel = 'Semua Guru';
                                                $rcptIcon = 'bi-person-video3';
                                            } elseif ($m['recipient_type'] === 'all_students') {
                                                $rcptLabel = 'Semua Siswa';
                                                $rcptIcon = 'bi-mortarboard';
                                            } elseif ($m['recipient_type'] === 'class') {
                                                $rcptLabel = 'Kelas ' . ($m['class_name'] ?? '');
                                                $rcptIcon = 'bi-door-open';
                                            }
                                        ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="small fw-semibold text-dark"><?= date('d/m/Y', strtotime($m['created_at'])) ?></div>
                                                <small class="text-muted font-monospace" style="font-size: 0.75rem;"><?= date('H:i:s', strtotime($m['created_at'])) ?> WIB</small>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-1 mb-1">
                                                    <i class="bi <?= $rcptIcon ?> text-primary small"></i>
                                                    <strong class="small text-dark"><?= $rcptLabel ?></strong>
                                                    <span class="badge bg-light text-secondary border rounded-pill ms-1" style="font-size: 0.68rem;">
                                                        <?= $m['total_recipients'] ?> nomor
                                                    </span>
                                                </div>
                                                <small class="text-muted d-block text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($m['target_names'] ?: $m['target_phone']) ?>">
                                                    <?= htmlspecialchars($m['target_names'] ?: $m['target_phone']) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="small text-dark text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($m['message']) ?>">
                                                    <?= htmlspecialchars($m['message']) ?>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?= $badgeClass ?> rounded-pill small px-2 py-1">
                                                    <?= $statusText ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="d-flex justify-content-end gap-1">
                                                    <button type="button" class="btn btn-sm btn-outline-info p-1 px-2 rounded-3" title="Detail Pesan" onclick='showDetailModal(<?= json_encode($m) ?>)'>
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <form action="<?= BASE_URL ?>admin/message_delete/<?= $m['id'] ?>" method="POST" class="d-inline" onsubmit="return confirm('Hapus riwayat pesan ini?')">
                                                        <?= Csrf::field() ?>
                                                        <button type="submit" class="btn btn-sm btn-outline-danger p-1 px-2 rounded-3" title="Hapus Log">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="bi bi-chat-square-dots fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                            <p class="mb-0">Belum ada riwayat pengiriman pesan WhatsApp.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Pesan -->
<div class="modal fade" id="modalDetailMessage" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle-fill text-primary"></i> Detail Riwayat Pesan WhatsApp
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <div class="mb-3">
                    <small class="text-muted d-block">Target Penerima:</small>
                    <strong class="text-dark small d-block" id="modalDetailTarget">-</strong>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Nomor Tujuan:</small>
                    <div class="p-2 bg-light rounded-3 font-monospace small text-break" id="modalDetailPhone">-</div>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Isi Pesan WhatsApp:</small>
                    <div class="p-3 bg-light rounded-3 small font-monospace text-dark whitespace-pre-wrap" id="modalDetailBody" style="white-space: pre-wrap;">-</div>
                </div>
                <div class="mb-2">
                    <small class="text-muted d-block">Respon Gateway Fonnte:</small>
                    <div class="p-2 bg-dark text-success rounded-3 small font-monospace text-break" id="modalDetailResponse" style="font-size: 0.75rem;">-</div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4 btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pengaturan API Key Fonnte -->
<div class="modal fade" id="modalSettingsKey" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-key-fill text-warning"></i> Pengaturan API Key Fonnte
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>admin/fonnte_save_key" method="POST">
                <?= Csrf::field() ?>
                <div class="modal-body py-3">
                    <p class="text-muted small mb-3">
                        Kunci API diperoleh dari dasbor akun Fonnte (<a href="https://fonnte.com" target="_blank" rel="noopener">fonnte.com</a>) setelah memindai QR code WhatsApp gateway.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted text-uppercase">Token API Fonnte <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-shield-lock-fill text-secondary"></i></span>
                            <input type="text" name="fonnte_api_key" id="inputFonnteApiKey" class="form-control font-monospace" value="<?= htmlspecialchars($current_api_key) ?>" required>
                        </div>
                        <small class="text-muted" style="font-size: 0.75rem;">Kunci bawaan saat ini: <code>bdvECGY1WDwuYNDFUg8H</code></small>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3 btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 btn-sm fw-semibold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleRecipientType() {
    const isClass = document.getElementById('rcptClass').checked;
    const isSpecific = document.getElementById('rcptSpecific').checked;

    const classContainer = document.getElementById('containerClassSelect');
    const specificContainer = document.getElementById('containerSpecificSelect');

    if (isClass) {
        classContainer.classList.remove('d-none');
        document.getElementById('selectClass').setAttribute('required', 'required');
    } else {
        classContainer.classList.add('d-none');
        document.getElementById('selectClass').removeAttribute('required');
    }

    if (isSpecific) {
        specificContainer.classList.remove('d-none');
    } else {
        specificContainer.classList.add('d-none');
    }
}

function handleSpecificContactChange() {
    const select = document.getElementById('selectSpecificContact');
    const val = select.value;
    if (val) {
        const parts = val.split('|');
        if (parts.length >= 4) {
            document.getElementById('inputCustomPhone').value = parts[2];
            document.getElementById('inputCustomName').value = parts[3];
        }
    }
}

// Koleksi Template Pesan WhatsApp Siap Pakai
const messageTemplates = {
    keterlambatan_siswa: `*Pemberitahuan Keterlambatan Presensi Siswa*

Kepada Yth. Orang Tua/Wali dari *{nama}*,
Assalamu'alaikum Wr. Wb.

Kami dari *{sekolah}* menginformasikan bahwa ananda terdata *terlambat hadir* pada hari ini, {tanggal}.

Mohon perhatian dan kerja sama Bapak/Ibu agar ananda dapat berangkat lebih awal sehingga dapat mengikuti pembelajaran tepat waktu.

Terima kasih atas kerja samanya.
_Wassalamu'alaikum Wr. Wb._`,

    belum_absen_siswa: `*Pengingat Presensi Wajah (FaceAbsen)*

Yth. *{nama}*,
Sistem presensi *{sekolah}* mencatat bahwa Anda *belum melakukan presensi* per hari ini, {tanggal}.

Silakan segera menuju perangkat kamera pemindai wajah di sekolah sebelum batas waktu presensi berakhir.

Terima kasih.`,

    panggilan_wali: `*Undangan Pertemuan Wali Murid*

Kepada Yth. Orang Tua/Wali dari *{nama}*,
Salam sejahtera.

Sehubungan dengan perkembangan belajar dan kehadiran siswa di *{sekolah}*, kami mengundang Bapak/Ibu untuk hadir ke sekolah pada:
📅 Tanggal: {tanggal}
🏢 Tempat: Ruang Pertemuan / BP {sekolah}

Kehadiran Bapak/Ibu sangat berarti bagi perkembangan ananda. Terima kasih atas perhatiannya.`,

    prestasi_siswa: `*Apresiasi Kedisiplinan & Kehadiran Siswa*

Kepada Yth. *{nama}* & Orang Tua/Wali,
Selamat!

Pihak *{sekolah}* memberikan apresiasi atas kedisiplinan dan tingkat kehadiran ananda yang sangat baik per tanggal {tanggal}.

Pertahankan terus semangat belajar dan ketertiban ini demi masa depan yang gemilang!`,

    rapat_guru: `*Undangan Rapat Koordinasi Dewan Guru*

Kepada Yth. Bapak/Ibu *{nama}*,
Assalamu'alaikum Wr. Wb.

Mengharap kehadiran Bapak/Ibu Guru pada Rapat Evaluasi Pembelajaran & Presensi Sekolah:
📅 Hari/Tanggal: {tanggal}
🏫 Lokasi: Ruang Guru *{sekolah}*

Mengingat pentingnya agenda ini, dimohon hadir tepat waktu. Terima kasih atas dedikasi dan kerja samanya.`,

    rekap_guru: `*Pengingat Pengisian Rekap Kehadiran & Jurnal*

Yth. Bapak/Ibu *{nama}*,
Salam hormat.

Diberitahukan kepada seluruh dewan guru *{sekolah}* untuk memeriksa dan melengkapi rekapan jurnal mengajar serta absensi kelas per tanggal *{tanggal}*.

Terima kasih atas kerja samanya.`,

    jadwal_piket: `*Pemberitahuan Tugas Piket Guru*

Yth. Bapak/Ibu *{nama}*,
Kami mengingatkan jadwal tugas piket harian Anda di *{sekolah}* pada tanggal *{tanggal}*.

Mohon hadir lebih awal untuk menyambut siswa dan memantau ketertiban absensi pagi. Terima kasih.`,

    pengumuman_umum: `*Pemberitahuan Resmi Sekolah*

Kepada Yth. *{nama}*,
Disampaikan pengumuman penting dari *{sekolah}* per tanggal *{tanggal}*:

[Silakan tuliskan rincian pengumuman di sini...]

Demikian pemberitahuan ini disampaikan. Atas perhatian dan kerja sama seluruh warga sekolah, kami ucapkan terima kasih.`,

    libur_sekolah: `*Informasi Libur Kegiatan Belajar Mengajar*

Kepada Yth. *{nama}*,
Diberitahukan bahwa seluruh kegiatan pembelajaran di *{sekolah}* *diliburkan* mulai tanggal {tanggal}.

Kegiatan KBM akan kembali aktif normal sesuai jadwal kalender akademik. Selamat beristirahat dan tetap jaga kesehatan.`,

    ujian_sekolah: `*Pemberitahuan Pelaksanaan Asesmen / Ujian Sekolah*

Kepada Yth. *{nama}*,
Kami menginformasikan bahwa pelaksanaan ujian di *{sekolah}* akan dimulai pada tanggal {tanggal}.

Diharapkan seluruh siswa hadir tepat waktu, membawa perlengkapan yang diperlukan, dan mempersiapkan diri dengan baik.

Semoga sukses!`
};

function applyMessageTemplate() {
    const select = document.getElementById('selectMessageTemplate');
    const key = select.value;
    if (key && messageTemplates[key]) {
        document.getElementById('inputMessage').value = messageTemplates[key];
        updateCharCount();
    }
}

function resetMessageText() {
    document.getElementById('inputMessage').value = '';
    document.getElementById('selectMessageTemplate').value = '';
    updateCharCount();
    document.getElementById('inputMessage').focus();
}

function insertTag(tag) {
    const textarea = document.getElementById('inputMessage');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    textarea.value = text.substring(0, start) + tag + text.substring(end);
    textarea.selectionStart = textarea.selectionEnd = start + tag.length;
    textarea.focus();
    updateCharCount();
}

function updateCharCount() {
    const len = document.getElementById('inputMessage').value.length;
    document.getElementById('charCounter').textContent = len + ' Karakter';
}

function refreshDeviceStatus() {
    const btn = document.getElementById('btnRefreshStatus');
    const origHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memuat...';
    btn.disabled = true;

    fetch('<?= BASE_URL ?>admin/fonnte_device_status')
        .then(res => res.json())
        .then(data => {
            const isConnect = data.device_status === 'connect' || data.status === true;
            document.getElementById('valDeviceStatus').textContent = isConnect ? 'Terhubung (Connect)' : 'Terputus (Disconnect)';
            document.getElementById('valDeviceNumber').innerHTML = '<i class="bi bi-phone me-1 text-success"></i>' + (data.device || '-');
            document.getElementById('valDeviceQuota').innerHTML = '<i class="bi bi-chat-dots-fill me-1"></i>' + (data.quota || '-') + ' Pesan';
            document.getElementById('valDevicePackage').textContent = (data.package || 'Free') + ' (s/d ' + (data.expired || '-') + ')';
        })
        .catch(err => {
            alert('Gagal memperbarui status gateway: ' + err);
        })
        .finally(() => {
            btn.innerHTML = origHtml;
            btn.disabled = false;
        });
}

function showDetailModal(item) {
    document.getElementById('modalDetailTarget').textContent = item.target_names || item.recipient_type;
    document.getElementById('modalDetailPhone').textContent = item.target_phone;
    document.getElementById('modalDetailBody').textContent = item.message;
    document.getElementById('modalDetailResponse').textContent = item.fonnte_response || '-';
    
    const modal = new bootstrap.Modal(document.getElementById('modalDetailMessage'));
    modal.show();
}

// Inisialisasi saat halaman pertama kali dibuka
document.addEventListener('DOMContentLoaded', function() {
    toggleRecipientType();
    updateCharCount();
});
</script>
