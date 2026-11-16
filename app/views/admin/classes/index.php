<div class="container py-4 flex-grow-1">
    
    <!-- Flash Notification -->
    <?php Flash::display(); ?>

    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/dashboard" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Kelas</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-dark mb-0">
                <i class="bi bi-door-open-fill text-primary me-2"></i>Manajemen Kelas
            </h3>
        </div>
        <div>
            <button type="button" class="btn btn-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addClassModal">
                <i class="bi bi-plus-lg me-1"></i> Tambah Kelas Baru
            </button>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3">
            <form action="<?= BASE_URL ?>admin/classes" method="GET" class="row g-2 align-items-center">
                <div class="col-12 col-md-9">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 bg-light" placeholder="Cari nama kelas atau tingkat..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <button type="submit" class="btn btn-dark w-100 rounded-3">
                        <i class="bi bi-search me-1"></i> Cari
                    </button>
                </div>
                <div class="col-6 col-md-1">
                    <a href="<?= BASE_URL ?>admin/classes" class="btn btn-outline-secondary w-100 rounded-3" title="Reset">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-body p-0">
            <?php if (!empty($classes)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="width: 60px;">No</th>
                                <th>Tingkat</th>
                                <th>Nama Kelas</th>
                                <th>Jumlah Siswa</th>
                                <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($classes as $c): ?>
                                <tr>
                                    <td class="ps-4 text-muted small"><?= $no++ ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($c['level']) ?></span></td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($c['name']) ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>admin/students?class_id=<?= $c['id'] ?>" class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none">
                                            <i class="bi bi-people me-1"></i> <?= (int)$c['total_students'] ?> Siswa
                                        </a>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-warning" title="Edit"
                                                    data-bs-toggle="modal" data-bs-target="#editClassModal"
                                                    data-id="<?= $c['id'] ?>" data-name="<?= htmlspecialchars($c['name']) ?>" data-level="<?= htmlspecialchars($c['level']) ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" title="Hapus" 
                                                    data-bs-toggle="modal" data-bs-target="#deleteClassModal" 
                                                    data-id="<?= $c['id'] ?>" data-name="<?= htmlspecialchars($c['name']) ?>"
                                                    data-students="<?= (int)$c['total_students'] ?>">
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
                    <h5 class="fw-bold text-dark mb-1">Tidak Ada Data Kelas</h5>
                    <p class="text-muted small mb-3">
                        <?= !empty($search) ? 'Tidak ada kelas yang sesuai dengan kata kunci pencarian.' : 'Belum ada data kelas yang dibuat.' ?>
                    </p>
                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addClassModal">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Kelas Baru
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Tambah Kelas -->
<div class="modal fade" id="addClassModal" tabindex="-1" aria-labelledby="addClassModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="addClassModalLabel"><i class="bi bi-plus-circle text-primary me-2"></i>Tambah Kelas Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>admin/class_store" method="POST" autocomplete="off">
                <div class="modal-body p-4">
                    <?= Csrf::field() ?>
                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold text-secondary">Nama Kelas <span class="text-danger">*</span></label>
                        <input type="text" class="form-control bg-light" id="name" name="name" placeholder="Contoh: X RPL 2 atau XII TKJ 1" required>
                    </div>
                    <div class="mb-3">
                        <label for="level" class="form-label small fw-semibold text-secondary">Tingkat / Jenjang <span class="text-danger">*</span></label>
                        <select class="form-select bg-light" id="level" name="level" required>
                            <option value="10">Kelas 10</option>
                            <option value="11">Kelas 11</option>
                            <option value="12">Kelas 12</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Kelas -->
<div class="modal fade" id="editClassModal" tabindex="-1" aria-labelledby="editClassModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="editClassModalLabel"><i class="bi bi-pencil-square text-warning me-2"></i>Edit Data Kelas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editClassForm" method="POST" action="" autocomplete="off">
                <div class="modal-body p-4">
                    <?= Csrf::field() ?>
                    <div class="mb-3">
                        <label for="edit_name" class="form-label small fw-semibold text-secondary">Nama Kelas <span class="text-danger">*</span></label>
                        <input type="text" class="form-control bg-light" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_level" class="form-label small fw-semibold text-secondary">Tingkat / Jenjang <span class="text-danger">*</span></label>
                        <select class="form-select bg-light" id="edit_level" name="level" required>
                            <option value="10">Kelas 10</option>
                            <option value="11">Kelas 11</option>
                            <option value="12">Kelas 12</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-semibold">Perbarui</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus Kelas -->
<div class="modal fade" id="deleteClassModal" tabindex="-1" aria-labelledby="deleteClassModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-body p-4 text-center">
                <div class="text-danger mb-3"><i class="bi bi-exclamation-triangle-fill display-4"></i></div>
                <h5 class="fw-bold mb-2">Hapus Kelas?</h5>
                <p class="text-muted small mb-3">Apakah Anda yakin ingin menghapus kelas <strong id="deleteClassName"></strong>?</p>
                <div id="deleteWarningStudents" class="alert alert-warning py-2 small d-none mb-3">
                    <i class="bi bi-exclamation-circle me-1"></i> Kelas memiliki <span id="studentCountWarning"></span> siswa terdaftar! Hapus/pindahkan siswa terlebih dahulu.
                </div>
                
                <form id="deleteClassForm" method="POST" action="">
                    <?= Csrf::field() ?>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light w-100 rounded-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" id="deleteSubmitBtn" class="btn btn-danger w-100 rounded-3">Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit Modal Hook
    const editClassModal = document.getElementById('editClassModal');
    if (editClassModal) {
        editClassModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const classId = button.getAttribute('data-id');
            const className = button.getAttribute('data-name');
            const classLevel = button.getAttribute('data-level');

            document.getElementById('edit_name').value = className;
            document.getElementById('edit_level').value = classLevel;
            document.getElementById('editClassForm').action = '<?= BASE_URL ?>admin/class_update/' + classId;
        });
    }

    // Delete Modal Hook
    const deleteClassModal = document.getElementById('deleteClassModal');
    if (deleteClassModal) {
        deleteClassModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const classId = button.getAttribute('data-id');
            const className = button.getAttribute('data-name');
            const studentCount = parseInt(button.getAttribute('data-students') || '0', 10);

            document.getElementById('deleteClassName').textContent = className;
            document.getElementById('deleteClassForm').action = '<?= BASE_URL ?>admin/class_delete/' + classId;

            const warningDiv = document.getElementById('deleteWarningStudents');
            const deleteSubmitBtn = document.getElementById('deleteSubmitBtn');

            if (studentCount > 0) {
                document.getElementById('studentCountWarning').textContent = studentCount;
                warningDiv.classList.remove('d-none');
                deleteSubmitBtn.disabled = true;
                deleteSubmitBtn.classList.add('opacity-50');
            } else {
                warningDiv.classList.add('d-none');
                deleteSubmitBtn.disabled = false;
                deleteSubmitBtn.classList.remove('opacity-50');
            }
        });
    }
});
</script>
