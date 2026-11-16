<div class="container py-4 flex-grow-1">
    
    <!-- Flash Notification -->
    <?php Flash::display(); ?>

    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/dashboard" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Peserta Didik</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-dark mb-0">
                <i class="bi bi-mortarboard-fill text-primary me-2"></i>Data Peserta Didik
            </h3>
        </div>
        <div>
            <a href="<?= BASE_URL ?>admin/student_create" class="btn btn-primary rounded-pill px-3 shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Tambah Siswa Baru
            </a>
        </div>
    </div>

    <!-- Filter & Search Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3">
            <form action="<?= BASE_URL ?>admin/students" method="GET" class="row g-2 align-items-center">
                <div class="col-12 col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 bg-light" placeholder="Cari nama, NIS, email, atau username..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <select name="class_id" class="form-select bg-light">
                        <option value="">-- Semua Kelas --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $selected_class == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?> (Tingkat <?= htmlspecialchars($c['level']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <button type="submit" class="btn btn-dark w-100 rounded-3">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                </div>
                <div class="col-6 col-md-1">
                    <a href="<?= BASE_URL ?>admin/students" class="btn btn-outline-secondary w-100 rounded-3" title="Reset">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-body p-0">
            <?php if (!empty($students)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="width: 60px;">No</th>
                                <th>NIS</th>
                                <th>Nama Lengkap</th>
                                <th>Kelas</th>
                                <th>Kontak (Email & HP)</th>
                                <th>Username</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($students as $s): ?>
                                <tr>
                                    <td class="ps-4 text-muted small"><?= $no++ ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($s['nis']) ?></span></td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($s['name']) ?></td>
                                    <td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($s['class_name']) ?></span></td>
                                    <td>
                                        <?php if (!empty($s['email'])): ?>
                                            <div class="text-dark small"><i class="bi bi-envelope text-secondary me-1"></i><?= htmlspecialchars($s['email']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($s['phone'])): ?>
                                            <div class="text-muted small"><i class="bi bi-telephone text-primary me-1"></i><?= htmlspecialchars($s['phone']) ?></div>
                                        <?php elseif (empty($s['email'])): ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?= htmlspecialchars($s['username']) ?></code></td>
                                    <td>
                                        <?php if ($s['status'] === 'active'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= BASE_URL ?>admin/student_detail/<?= $s['id'] ?>" class="btn btn-outline-info" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="<?= BASE_URL ?>admin/student_edit/<?= $s['id'] ?>" class="btn btn-outline-warning" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger" title="Hapus" 
                                                    data-bs-toggle="modal" data-bs-target="#deleteModal" 
                                                    data-id="<?= $s['id'] ?>" data-name="<?= htmlspecialchars($s['name']) ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <div class="text-muted mb-2"><i class="bi bi-inbox display-4"></i></div>
                    <h5 class="fw-bold text-dark mb-1">Tidak Ada Data Peserta Didik</h5>
                    <p class="text-muted small mb-3">
                        <?= !empty($search) || !empty($selected_class) ? 'Tidak ada hasil yang sesuai dengan kriteria pencarian/filter.' : 'Belum ada data siswa yang didaftarkan ke sistem.' ?>
                    </p>
                    <a href="<?= BASE_URL ?>admin/student_create" class="btn btn-sm btn-primary rounded-pill px-3">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Siswa Baru
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-body p-4 text-center">
                <div class="text-danger mb-3"><i class="bi bi-exclamation-triangle-fill display-4"></i></div>
                <h5 class="fw-bold mb-2">Hapus Siswa?</h5>
                <p class="text-muted small mb-4">Apakah Anda yakin ingin menghapus peserta didik <strong id="deleteStudentName"></strong>? Tindakan ini tidak dapat dibatalkan.</p>
                
                <form id="deleteForm" method="POST" action="">
                    <?= Csrf::field() ?>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light w-100 rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger w-100 rounded-3">Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const studentId = button.getAttribute('data-id');
            const studentName = button.getAttribute('data-name');
            
            document.getElementById('deleteStudentName').textContent = studentName;
            document.getElementById('deleteForm').action = '<?= BASE_URL ?>admin/student_delete/' + studentId;
        });
    }
});
</script>
