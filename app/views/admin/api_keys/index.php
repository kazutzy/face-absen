<div class="container py-4 flex-grow-1">

    <!-- Flash Notification -->
    <?php Flash::display(); ?>

    <!-- Breadcrumb & Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/dashboard" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Konfigurasi API Key</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-dark mb-0">
                <i class="bi bi-key-fill text-warning me-2"></i>Konfigurasi API Key
            </h3>
            <p class="text-muted small mb-0 mt-1">Kelola dan ganti API Key layanan AI (NVIDIA NIM Chatbot) yang digunakan oleh sistem SMK Bina Warga Lemahabang.</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#addApiKeyModal">
                <i class="bi bi-plus-circle me-1"></i> Tambah API Key Baru
            </button>
        </div>
    </div>

    <!-- Active Key Highlight Card -->
    <?php if ($active_key): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-gradient-success text-white overflow-hidden" style="background: linear-gradient(135deg, #0b8043 0%, #065f32 100%);">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-warning text-dark fw-bold px-3 py-1 rounded-pill">
                                <i class="bi bi-check-circle-fill me-1"></i> API KEY AKTIF
                            </span>
                            <span class="badge bg-white bg-opacity-25 text-white fw-semibold px-2 py-1 rounded-pill">
                                <?= htmlspecialchars($active_key['provider'] ?? 'NVIDIA NIM') ?>
                            </span>
                        </div>
                        <h4 class="fw-bold mb-1"><?= htmlspecialchars($active_key['name']) ?></h4>
                        <p class="mb-0 text-white-50 small">Kunci ini sedang aktif digunakan oleh asisten FaceAbsen AI dan seluruh modul analitik.</p>
                    </div>
                    <div class="bg-white bg-opacity-10 p-3 rounded-4 border border-white border-opacity-25">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-white-50 fw-semibold">Nilai Kunci (Aktif):</small>
                            <span class="badge bg-success bg-opacity-75 text-white" id="activeKeyIntegrationBadge">
                                <i class="bi bi-cpu-fill me-1"></i> Terhubung ke Chatbot
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <code class="text-warning fw-bold fs-6 font-monospace" id="activeKeyText" data-masked="<?= htmlspecialchars(ApiKey::maskKey($active_key['api_key'])) ?>" data-full="<?= htmlspecialchars($active_key['api_key']) ?>" data-state="masked"><?= htmlspecialchars(ApiKey::maskKey($active_key['api_key'])) ?></code>
                            <button type="button" class="btn btn-sm btn-outline-light rounded-pill px-2 py-1" onclick="toggleActiveKeyVisibility()" title="Tampilkan / Sembunyikan Kunci">
                                <i class="bi bi-eye" id="activeKeyEyeIcon"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-light rounded-pill px-2 py-1 text-nowrap" onclick="copyToClipboard('<?= htmlspecialchars($active_key['api_key']) ?>', this)" title="Salin API Key">
                                <i class="bi bi-clipboard me-1"></i> Salin
                            </button>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-warning rounded-pill px-3 py-1 fw-bold shadow-sm" id="btnTestActiveKey" onclick="testApiKey(<?= $active_key['id'] ?>, '<?= htmlspecialchars($active_key['name'], ENT_QUOTES) ?>', this)">
                                <i class="bi bi-broadcast me-1"></i> Uji Koneksi API
                            </button>
                            <small class="text-white-50">Tes langsung ke server NVIDIA NIM</small>
                        </div>
                        <div id="active-test-result" class="mt-2 small d-none"></div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning border-0 rounded-4 shadow-sm mb-4 d-flex align-items-center gap-3">
            <i class="bi bi-exclamation-triangle-fill fs-3 text-warning"></i>
            <div>
                <strong class="d-block">Belum ada API Key aktif!</strong>
                <span>Silakan pilih salah satu API Key di bawah ini untuk dijadikan kunci aktif agar layanan AI dapat berfungsi.</span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Table of All API Keys -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="bi bi-list-stars text-primary me-2"></i>Daftar API Key Terdaftar
            </h5>
            <span class="badge bg-light text-muted border small"><?= count($api_keys) ?> Kunci</span>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($api_keys)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th class="ps-4" style="width: 50px;">No</th>
                                <th>Nama Label</th>
                                <th>Provider</th>
                                <th>Kunci API</th>
                                <th>Status</th>
                                <th>Terakhir Diperbarui</th>
                                <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($api_keys as $k): ?>
                                <?php $isActive = !empty($k['is_active']); ?>
                                <tr class="<?= $isActive ? 'table-success bg-opacity-25' : '' ?>">
                                    <td class="ps-4 text-muted small"><?= $no++ ?></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($k['name']) ?></div>
                                        <?php if ($isActive): ?>
                                            <small class="text-success fw-bold"><i class="bi bi-check-circle me-1"></i>Sedang Digunakan</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-cpu text-primary me-1"></i><?= htmlspecialchars($k['provider'] ?? 'NVIDIA NIM') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="font-monospace small text-muted" id="key-display-<?= $k['id'] ?>" data-masked="<?= htmlspecialchars(ApiKey::maskKey($k['api_key'])) ?>" data-full="<?= htmlspecialchars($k['api_key']) ?>">
                                                <?= htmlspecialchars(ApiKey::maskKey($k['api_key'])) ?>
                                            </span>
                                            <button type="button" class="btn btn-sm btn-link text-muted p-0" onclick="toggleKeyVisibility(<?= $k['id'] ?>)" title="Tampilkan / Sembunyikan">
                                                <i class="bi bi-eye" id="eye-icon-<?= $k['id'] ?>"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-link text-muted p-0" onclick="copyToClipboard('<?= htmlspecialchars($k['api_key']) ?>', this)" title="Salin Kunci">
                                                <i class="bi bi-clipboard"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($isActive): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">
                                                <i class="bi bi-check-circle-fill me-1"></i>Aktif
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-3 py-1">
                                                Cadangan
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted">
                                        <?= htmlspecialchars(date('d M Y, H:i', strtotime($k['updated_at'] ?? $k['created_at']))) ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex align-items-center justify-content-end gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-2 py-1" onclick="testApiKey(<?= $k['id'] ?>, '<?= htmlspecialchars($k['name'], ENT_QUOTES) ?>', this)" title="Uji validitas koneksi API Key">
                                                <i class="bi bi-broadcast me-1"></i> Uji
                                            </button>

                                            <?php if (!$isActive): ?>
                                                <form action="<?= BASE_URL ?>admin/api_key_activate/<?= $k['id'] ?>" method="POST" class="d-inline" onsubmit="return confirm('Aktifkan API Key \'<?= htmlspecialchars($k['name'], ENT_QUOTES) ?>\' untuk digunakan sistem?');">
                                                    <?= Csrf::field() ?>
                                                    <button type="submit" class="btn btn-sm btn-success rounded-pill px-3" title="Gunakan kunci ini sebagai default">
                                                        <i class="bi bi-play-fill me-1"></i> Gunakan
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 disabled" disabled>
                                                    <i class="bi bi-check-all me-1"></i> Utama
                                                </button>
                                            <?php endif; ?>

                                            <button type="button" class="btn btn-sm btn-outline-warning rounded-circle" title="Edit"
                                                    data-bs-toggle="modal" data-bs-target="#editApiKeyModal"
                                                    data-id="<?= $k['id'] ?>"
                                                    data-name="<?= htmlspecialchars($k['name']) ?>"
                                                    data-provider="<?= htmlspecialchars($k['provider']) ?>"
                                                    data-masked="<?= htmlspecialchars(ApiKey::maskKey($k['api_key'])) ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>

                                            <?php if (!$isActive): ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle" title="Hapus"
                                                        data-bs-toggle="modal" data-bs-target="#deleteApiKeyModal"
                                                        data-id="<?= $k['id'] ?>"
                                                        data-name="<?= htmlspecialchars($k['name']) ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <div class="text-muted mb-2"><i class="bi bi-key display-4 opacity-50"></i></div>
                    <h5 class="fw-bold text-dark mb-1">Belum Ada API Key</h5>
                    <p class="text-muted small mb-3">Tambahkan API Key pertama Anda untuk mengaktifkan fitur AI FaceAbsen.</p>
                    <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addApiKeyModal">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Kunci
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Tambah API Key -->
<div class="modal fade" id="addApiKeyModal" tabindex="-1" aria-labelledby="addApiKeyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="<?= BASE_URL ?>admin/api_key_store" method="POST">
                <?= Csrf::field() ?>
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="addApiKeyModalLabel">
                        <i class="bi bi-key-fill text-warning me-2"></i>Tambah API Key Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama / Label Kunci <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="add_name" class="form-control rounded-3" placeholder="Contoh: Akun NVIDIA NIM Cadangan 2" required>
                        <small class="text-muted">Beri nama pengenal agar mudah dibedakan.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Provider / Layanan <span class="text-danger">*</span></label>
                        <select name="provider" id="add_provider" class="form-select rounded-3">
                            <option value="Google Gemini">Google Gemini (Gemini 3.5 / 2.5 Flash)</option>
                            <option value="NVIDIA NIM" selected>NVIDIA NIM (DeepSeek / Llama)</option>
                            <option value="OpenAI">OpenAI</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-semibold mb-0">Nilai Kunci API (API Key Secret) <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" id="btnTestAddKey" onclick="testModalInputKey('add')">
                                <i class="bi bi-broadcast me-1"></i> Uji Kunci
                            </button>
                        </div>
                        <textarea name="api_key" id="add_api_key" rows="2" class="form-control font-monospace rounded-3" placeholder="nvapi-..." required></textarea>
                        <div id="add_test_result" class="mt-2 small d-none"></div>
                        <small class="text-muted">Kunci akan disimpan dengan aman di database.</small>
                    </div>
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActiveCheck">
                        <label class="form-check-label small fw-semibold" for="isActiveCheck">
                            Langsung jadikan sebagai Kunci Aktif
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Kunci
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit API Key -->
<div class="modal fade" id="editApiKeyModal" tabindex="-1" aria-labelledby="editApiKeyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="editApiKeyForm" action="" method="POST">
                <?= Csrf::field() ?>
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="editApiKeyModalLabel">
                        <i class="bi bi-pencil-square text-warning me-2"></i>Edit API Key
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama / Label Kunci <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_name" class="form-control rounded-3" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Provider / Layanan <span class="text-danger">*</span></label>
                        <select name="provider" id="edit_provider" class="form-select rounded-3">
                            <option value="Google Gemini">Google Gemini (Gemini 3.5 / 2.5 Flash)</option>
                            <option value="NVIDIA NIM">NVIDIA NIM (DeepSeek / Llama)</option>
                            <option value="OpenAI">OpenAI</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-semibold mb-0">Nilai Kunci API Baru (Opsional)</label>
                            <button type="button" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" id="btnTestEditKey" onclick="testModalInputKey('edit')">
                                <i class="bi bi-broadcast me-1"></i> Uji Kunci
                            </button>
                        </div>
                        <input type="hidden" id="edit_id" value="">
                        <textarea name="api_key" id="edit_api_key" rows="2" class="form-control font-monospace rounded-3" placeholder="Kosongkan jika tidak ingin mengubah kunci rahasia"></textarea>
                        <div id="edit_test_result" class="mt-2 small d-none"></div>
                        <small class="text-muted d-block mt-1">
                            Kunci saat ini: <code class="text-secondary" id="edit_current_masked">-</code>. Biarkan kolom ini kosong jika hanya ingin mengubah nama atau provider.
                        </small>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> Perbarui
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus API Key -->
<div class="modal fade" id="deleteApiKeyModal" tabindex="-1" aria-labelledby="deleteApiKeyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="deleteApiKeyForm" action="" method="POST">
                <?= Csrf::field() ?>
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-danger" id="deleteApiKeyModalLabel">
                        <i class="bi bi-trash-fill me-2"></i>Konfirmasi Hapus API Key
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <p class="mb-2">Apakah Anda yakin ingin menghapus API Key berikut?</p>
                    <div class="p-3 bg-light rounded-3 border">
                        <strong class="d-block text-dark" id="delete_name">-</strong>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Tindakan ini tidak dapat dibatalkan. Kunci yang sedang aktif tidak dapat dihapus.</p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-semibold">
                        <i class="bi bi-trash me-1"></i> Hapus Permanen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Toggle mask / unmask active key in highlight card
function toggleActiveKeyVisibility() {
    const el = document.getElementById('activeKeyText');
    const icon = document.getElementById('activeKeyEyeIcon');
    if (!el || !icon) return;

    if (el.dataset.state === 'revealed') {
        el.textContent = el.dataset.masked;
        el.dataset.state = 'masked';
        icon.className = 'bi bi-eye';
    } else {
        el.textContent = el.dataset.full;
        el.dataset.state = 'revealed';
        icon.className = 'bi bi-eye-slash';
    }
}

// Toggle mask / unmask API key in table
function toggleKeyVisibility(id) {
    const el = document.getElementById('key-display-' + id);
    const icon = document.getElementById('eye-icon-' + id);
    if (!el || !icon) return;

    if (el.dataset.state === 'revealed') {
        el.textContent = el.dataset.masked;
        el.dataset.state = 'masked';
        icon.className = 'bi bi-eye';
    } else {
        el.textContent = el.dataset.full;
        el.dataset.state = 'revealed';
        icon.className = 'bi bi-eye-slash';
    }
}

// Copy to clipboard helper with fallback for non-HTTPS localhost
function copyToClipboard(text, btn) {
    function showFeedback() {
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check2 text-success"></i> Tersalin!';
        setTimeout(() => {
            btn.innerHTML = originalHtml;
        }, 1500);
    }

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(showFeedback).catch(() => {
            fallbackCopy(text, showFeedback);
        });
    } else {
        fallbackCopy(text, showFeedback);
    }
}

function fallbackCopy(text, callback) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.left = '-999999px';
    textarea.style.top = '-999999px';
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();
    try {
        document.execCommand('copy');
        if (callback) callback();
    } catch (err) {
        console.error('Gagal menyalin:', err);
    }
    document.body.removeChild(textarea);
}

// Modal event binding
document.addEventListener('DOMContentLoaded', function () {
    const editModal = document.getElementById('editApiKeyModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const provider = button.getAttribute('data-provider');
            const masked = button.getAttribute('data-masked');

            const form = document.getElementById('editApiKeyForm');
            form.action = '<?= BASE_URL ?>admin/api_key_update/' + id;

            document.getElementById('edit_id').value = id || '';
            document.getElementById('edit_name').value = name || '';
            document.getElementById('edit_provider').value = provider || 'NVIDIA NIM';
            document.getElementById('edit_api_key').value = '';
            document.getElementById('edit_current_masked').textContent = masked || '-';
            const editResult = document.getElementById('edit_test_result');
            if (editResult) editResult.className = 'mt-2 small d-none';
        });
    }

    const addModal = document.getElementById('addApiKeyModal');
    if (addModal) {
        addModal.addEventListener('show.bs.modal', function () {
            const addResult = document.getElementById('add_test_result');
            if (addResult) addResult.className = 'mt-2 small d-none';
        });
    }

    const deleteModal = document.getElementById('deleteApiKeyModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');

            const form = document.getElementById('deleteApiKeyForm');
            form.action = '<?= BASE_URL ?>admin/api_key_delete/' + id;

            document.getElementById('delete_name').textContent = name || '-';
        });
    }
});

// Test connection for a registered key by ID
function testApiKey(id, name, btn) {
    const originalHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Menguji...';
    }

    const activeResultBox = document.getElementById('active-test-result');
    const isActiveCard = btn && btn.id === 'btnTestActiveKey';

    if (isActiveCard && activeResultBox) {
        activeResultBox.className = 'mt-2 small alert alert-info py-2 px-3 border-0 rounded-3 text-dark';
        activeResultBox.innerHTML = '<div class="spinner-border spinner-border-sm text-info me-2" role="status"></div>Sedang menghubungi server NVIDIA NIM...';
    }

    fetch('<?= BASE_URL ?>admin/api_key_test/' + id)
        .then(res => res.json())
        .then(data => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }

            if (data.success) {
                const html = `<div class="alert alert-success alert-dismissible fade show py-2 px-3 small mb-0 rounded-3" role="alert">
                    <i class="bi bi-check-circle-fill me-1"></i> <strong>Koneksi Berhasil!</strong> ${data.message} <span class="badge bg-success ms-1">${data.latency_ms}ms</span>
                    <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>`;
                if (isActiveCard && activeResultBox) {
                    activeResultBox.className = 'mt-2 small';
                    activeResultBox.innerHTML = html;
                } else {
                    showToastAlert('success', `[${name}] Berhasil terhubung ke server NVIDIA! (${data.latency_ms}ms, ${data.models_count || 0} model siap digunakan)`);
                }
            } else {
                const html = `<div class="alert alert-danger alert-dismissible fade show py-2 px-3 small mb-0 rounded-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Gagal Terhubung:</strong> ${data.message} ${data.http_code ? '(HTTP ' + data.http_code + ')' : ''}
                    <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>`;
                if (isActiveCard && activeResultBox) {
                    activeResultBox.className = 'mt-2 small';
                    activeResultBox.innerHTML = html;
                } else {
                    showToastAlert('danger', `[${name}] Gagal: ${data.message}`);
                }
            }
        })
        .catch(err => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
            if (isActiveCard && activeResultBox) {
                activeResultBox.className = 'mt-2 small alert alert-danger py-2 px-3 border-0 rounded-3';
                activeResultBox.innerHTML = '<i class="bi bi-x-circle me-1"></i> Gagal menghubungi endpoint server lokal: ' + err.message;
            } else {
                showToastAlert('danger', 'Gagal memanggil endpoint tes: ' + err.message);
            }
        });
}

// Test input key before saving inside Add or Edit modal
function testModalInputKey(type) {
    let keyInput, nameInput, resultBox, btn;
    if (type === 'add') {
        keyInput = document.getElementById('add_api_key');
        nameInput = document.getElementById('add_name');
        resultBox = document.getElementById('add_test_result');
        btn = document.getElementById('btnTestAddKey');
    } else {
        keyInput = document.getElementById('edit_api_key');
        nameInput = document.getElementById('edit_name');
        resultBox = document.getElementById('edit_test_result');
        btn = document.getElementById('btnTestEditKey');
    }

    const keyVal = keyInput ? keyInput.value.trim() : '';
    const nameVal = nameInput ? nameInput.value.trim() : 'Kunci Uji';

    if (!keyVal) {
        if (type === 'edit') {
            const editId = document.getElementById('edit_id').value;
            if (editId) {
                testApiKey(editId, nameVal, btn);
                return;
            }
        }
        resultBox.className = 'mt-2 small alert alert-warning py-2 px-3 mb-0 rounded-3';
        resultBox.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i> Masukkan nilai kunci API terlebih dahulu.';
        return;
    }

    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Menguji...';

    resultBox.className = 'mt-2 small alert alert-info py-2 px-3 mb-0 rounded-3';
    resultBox.innerHTML = '<div class="spinner-border spinner-border-sm text-info me-2" role="status"></div>Sedang memeriksa validitas kunci ke NVIDIA NIM...';

    fetch('<?= BASE_URL ?>admin/api_key_test', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            api_key: keyVal,
            name: nameVal
        })
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;

        if (data.success) {
            resultBox.className = 'mt-2 small alert alert-success py-2 px-3 mb-0 rounded-3';
            resultBox.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i> <strong>Kunci Valid!</strong> ${data.message} (${data.latency_ms}ms)`;
        } else {
            resultBox.className = 'mt-2 small alert alert-danger py-2 px-3 mb-0 rounded-3';
            resultBox.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Gagal:</strong> ${data.message}`;
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        resultBox.className = 'mt-2 small alert alert-danger py-2 px-3 mb-0 rounded-3';
        resultBox.innerHTML = '<i class="bi bi-x-circle me-1"></i> Terjadi kesalahan jaringan: ' + err.message;
    });
}

// Floating Toast Notification
function showToastAlert(type, message) {
    let toastContainer = document.getElementById('toastFloatingContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastFloatingContainer';
        toastContainer.className = 'position-fixed bottom-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }

    const toastEl = document.createElement('div');
    toastEl.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0 show shadow-lg mb-2`;
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body small fw-semibold">
                <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill'} me-1"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close" onclick="this.closest('.toast').remove()"></button>
        </div>
    `;
    toastContainer.appendChild(toastEl);
    setTimeout(() => {
        if (toastEl.parentNode) toastEl.remove();
    }, 4500);
}
</script>
