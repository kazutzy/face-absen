<div class="container py-4 flex-grow-1">
    
    <!-- Flash Notification -->
    <?php Flash::display(); ?>

    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/dashboard" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Guru</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-dark mb-0">
                <i class="bi bi-person-video3 text-primary me-2"></i>Data Tenaga Pendidik (Guru)
            </h3>
        </div>
        <div>
            <a href="<?= BASE_URL ?>admin/teacher_create" class="btn btn-primary rounded-pill px-3 shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Tambah Guru Baru
            </a>
        </div>
    </div>

    <!-- Search Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3">
            <form action="<?= BASE_URL ?>admin/teachers" method="GET" class="row g-2 align-items-center">
                <div class="col-12 col-md-9">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 bg-light" placeholder="Cari nama guru, NIP, email, atau username..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <button type="submit" class="btn btn-dark w-100 rounded-3">
                        <i class="bi bi-search me-1"></i> Cari
                    </button>
                </div>
                <div class="col-6 col-md-1">
                    <a href="<?= BASE_URL ?>admin/teachers" class="btn btn-outline-secondary w-100 rounded-3" title="Reset">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-body p-0">
            <?php if (!empty($teachers)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="width: 60px;">No</th>
                                <th>NIP</th>
                                <th>Nama Lengkap</th>
                                <th>Kontak (Email & HP)</th>
                                <th>Username</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($teachers as $t): ?>
                                <tr>
                                    <td class="ps-4 text-muted small"><?= $no++ ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($t['nip']) ?></span></td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($t['name']) ?></td>
                                    <td>
                                        <?php if (!empty($t['email'])): ?>
                                            <div class="text-dark small"><i class="bi bi-envelope text-secondary me-1"></i><?= htmlspecialchars($t['email']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($t['phone'])): ?>
                                            <div class="text-muted small"><i class="bi bi-telephone text-primary me-1"></i><?= htmlspecialchars($t['phone']) ?></div>
                                        <?php elseif (empty($t['email'])): ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?= htmlspecialchars($t['username']) ?></code></td>
                                    <td>
                                        <?php if ($t['status'] === 'active'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= BASE_URL ?>admin/teacher_detail/<?= $t['id'] ?>" class="btn btn-outline-info" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="<?= BASE_URL ?>admin/teacher_edit/<?= $t['id'] ?>" class="btn btn-outline-warning" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger" title="Hapus" 
                                                    data-bs-toggle="modal" data-bs-target="#deleteTeacherModal" 
                                                    data-id="<?= $t['id'] ?>" data-name="<?= htmlspecialchars($t['name']) ?>">
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
                    <h5 class="fw-bold text-dark mb-1">Tidak Ada Data Guru</h5>
                    <p class="text-muted small mb-3">
                        <?= !empty($search) ? 'Tidak ada guru yang sesuai dengan kata kunci pencarian.' : 'Belum ada data guru yang ditambahkan ke sistem.' ?>
                    </p>
                    <a href="<?= BASE_URL ?>admin/teacher_create" class="btn btn-sm btn-primary rounded-pill px-3">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Guru Baru
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Guru -->
<div class="modal fade" id="deleteTeacherModal" tabindex="-1" aria-labelledby="deleteTeacherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-body p-4 text-center">
                <div class="text-danger mb-3"><i class="bi bi-exclamation-triangle-fill display-4"></i></div>
                <h5 class="fw-bold mb-2">Hapus Guru?</h5>
                <p class="text-muted small mb-4">Apakah Anda yakin ingin menghapus data guru <strong id="deleteTeacherName"></strong>? Tindakan ini tidak dapat dibatalkan.</p>
                
                <form id="deleteTeacherForm" method="POST" action="">
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
    const deleteModal = document.getElementById('deleteTeacherModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const teacherId = button.getAttribute('data-id');
            const teacherName = button.getAttribute('data-name');
            
            document.getElementById('deleteTeacherName').textContent = teacherName;
            document.getElementById('deleteTeacherForm').action = '<?= BASE_URL ?>admin/teacher_delete/' + teacherId;
        });
    }
});
</script>
