<?php
/**
 * ApiKey Model - Manajemen Kunci API (AI Providers & Integrasi)
 */
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/Setting.php';

class ApiKey extends Model {
    protected $table = 'api_keys';

    public function __construct() {
        parent::__construct();
        $this->ensureTableExists();
    }

    /**
     * Pastikan tabel api_keys tersedia di database (auto-migration self-healing)
     */
    public function ensureTableExists() {
        try {
            $pdo = $this->db->getPdo();
            $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL,
                `provider` VARCHAR(50) NOT NULL DEFAULT 'NVIDIA NIM',
                `api_key` TEXT NOT NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_api_keys_active` (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $pdo->exec($sql);

            // Cek apakah tabel kosong, jika ya buat default key dari tabel settings
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM `{$this->table}`");
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($res && (int)$res['total'] === 0) {
                $savedKey = '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';
                try {
                    $settingModel = new Setting();
                    $val = $settingModel->get('nvidia_api_key');
                    if (!empty($val)) {
                        $savedKey = $val;
                    }
                } catch (Throwable $ignore) {}

                $insert = $pdo->prepare("INSERT INTO `{$this->table}` (`name`, `provider`, `api_key`, `is_active`) VALUES (?, ?, ?, 1)");
                $insert->execute(['Kunci Utama (Default NVIDIA NIM)', 'NVIDIA NIM', $savedKey]);
            }
        } catch (Throwable $e) {
            error_log("ApiKey::ensureTableExists notice: " . $e->getMessage());
        }
    }

    /**
     * Ambil seluruh daftar API Key
     */
    public function getAll() {
        $this->ensureTableExists();
        $sql = "SELECT * FROM {$this->table} ORDER BY is_active DESC, id DESC";
        return $this->db->query($sql)->resultSet();
    }

    /**
     * Ambil API Key yang sedang aktif digunakan (dapat dipanggil statis maupun via instance objek)
     */
    public static function getActiveKey() {
        try {
            $db = Database::getInstance();
            $pdo = $db->getPdo();

            // Cek dan pastikan tabel api_keys ada
            $check = $pdo->query("SHOW TABLES LIKE 'api_keys'")->fetch();
            if (!$check) {
                $instance = new self();
                $instance->ensureTableExists();
            }

            $sql = "SELECT * FROM api_keys WHERE is_active = 1 LIMIT 1";
            $active = $db->query($sql)->single();

            if (!$active) {
                // Fallback ke key pertama jika belum ada yang diset aktif
                $sqlFirst = "SELECT * FROM api_keys ORDER BY id ASC LIMIT 1";
                $active = $db->query($sqlFirst)->single();
            }

            return $active;
        } catch (Throwable $e) {
            error_log("ApiKey::getActiveKey error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Cari API key berdasarkan ID
     */
    public function findById($id) {
        $this->ensureTableExists();
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        return $this->db->query($sql)->bind(':id', (int)$id)->single();
    }

    /**
     * Tambah API key baru
     */
    public function createKey($data) {
        $name = trim($data['name'] ?? '');
        $provider = trim($data['provider'] ?? 'NVIDIA NIM');
        $apiKey = trim($data['api_key'] ?? '');
        $isActive = !empty($data['is_active']) ? 1 : 0;

        // Jika tabel masih kosong, otomatis jadikan aktif
        $totalKeys = $this->count();
        if ($totalKeys === 0) {
            $isActive = 1;
        }

        $pdo = $this->db->getPdo();
        try {
            $pdo->beginTransaction();

            if ($isActive === 1) {
                // Nonaktifkan semua key lainnya
                $pdo->exec("UPDATE {$this->table} SET is_active = 0");
            }

            $stmt = $pdo->prepare("INSERT INTO {$this->table} (name, provider, api_key, is_active) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $provider, $apiKey, $isActive]);
            $insertedId = $pdo->lastInsertId();

            if ($isActive === 1) {
                // Sinkronisasi ke tabel settings
                try {
                    $settingModel = new Setting();
                    $settingModel->set('nvidia_api_key', $apiKey);
                } catch (Throwable $ignoreSetting) {
                    error_log("Setting sync notice: " . $ignoreSetting->getMessage());
                }
            }

            $pdo->commit();
            return $insertedId;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Perbarui data API Key
     */
    public function updateKey($id, $data) {
        $existing = $this->findById($id);
        if (!$existing) {
            return false;
        }

        $name = trim($data['name'] ?? $existing['name']);
        $provider = trim($data['provider'] ?? $existing['provider']);
        $apiKey = !empty($data['api_key']) ? trim($data['api_key']) : $existing['api_key'];
        $isActive = isset($data['is_active']) ? ((int)$data['is_active'] === 1 ? 1 : 0) : (int)$existing['is_active'];

        $pdo = $this->db->getPdo();
        try {
            $pdo->beginTransaction();

            if ($isActive === 1) {
                $pdo->exec("UPDATE {$this->table} SET is_active = 0");
            }

            $stmt = $pdo->prepare("UPDATE {$this->table} SET name = ?, provider = ?, api_key = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$name, $provider, $apiKey, $isActive, (int)$id]);

            if ($isActive === 1) {
                try {
                    $settingModel = new Setting();
                    $settingModel->set('nvidia_api_key', $apiKey);
                } catch (Throwable $ignoreSetting) {
                    error_log("Setting sync notice: " . $ignoreSetting->getMessage());
                }
            }

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Ganti / Aktifkan API key yang digunakan sistem
     */
    public function setActiveKey($id) {
        $target = $this->findById($id);
        if (!$target) {
            return false;
        }

        $pdo = $this->db->getPdo();
        try {
            $pdo->beginTransaction();

            // Set semua jadi nonaktif
            $pdo->exec("UPDATE {$this->table} SET is_active = 0");

            // Set target jadi aktif
            $stmt = $pdo->prepare("UPDATE {$this->table} SET is_active = 1 WHERE id = ?");
            $stmt->execute([(int)$id]);

            // Sinkronkan ke tabel settings agar Node.js / Chatbot langsung membaca key aktif
            try {
                $settingModel = new Setting();
                $settingModel->set('nvidia_api_key', $target['api_key']);
            } catch (Throwable $ignoreSetting) {
                error_log("Setting sync notice: " . $ignoreSetting->getMessage());
            }

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Hapus API Key
     */
    public function deleteKey($id) {
        $target = $this->findById($id);
        if (!$target) {
            return ['success' => false, 'message' => 'API Key tidak ditemukan.'];
        }

        $totalKeys = $this->count();
        if ($totalKeys <= 1) {
            return ['success' => false, 'message' => 'Tidak dapat menghapus satu-satunya API Key yang terdaftar.'];
        }
        if ((int)$target['is_active'] === 1) {
            return ['success' => false, 'message' => 'API Key yang sedang aktif tidak dapat dihapus. Silakan aktifkan kunci lain terlebih dahulu.'];
        }

        $pdo = $this->db->getPdo();
        try {
            $pdo->beginTransaction();

            $stmtDel = $pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
            $stmtDel->execute([(int)$id]);

            $pdo->commit();
            return ['success' => true, 'message' => 'API Key berhasil dihapus.'];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Helper sensor API Key untuk keamanan tampilan
     */
    public static function maskKey($key) {
        if (empty($key)) return '-';
        $len = strlen($key);
        if ($len <= 12) {
            return substr($key, 0, 3) . '••••' . substr($key, -3);
        }
        $prefix = substr($key, 0, 8);
        $suffix = substr($key, -6);
        return $prefix . '••••••••••••••••' . $suffix;
    }
}
