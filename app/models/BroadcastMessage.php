<?php
/**
 * BroadcastMessage Model - Manajemen Riwayat Pesan WhatsApp Gateway
 */
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Model.php';

class BroadcastMessage extends Model {
    protected $table = 'broadcast_messages';

    public function __construct() {
        parent::__construct();
        $this->ensureTableExists();
    }

    /**
     * Pastikan tabel broadcast_messages tersedia di database
     */
    public function ensureTableExists() {
        try {
            $pdo = $this->db->getPdo();
            $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `sender_id` INT NOT NULL,
                `recipient_type` ENUM('all_teachers', 'all_students', 'class', 'specific') NOT NULL,
                `target_class_id` INT NULL,
                `target_phone` TEXT NOT NULL,
                `target_names` TEXT NULL,
                `total_recipients` INT NOT NULL DEFAULT 1,
                `message` TEXT NOT NULL,
                `status` ENUM('sent', 'failed', 'partial') NOT NULL DEFAULT 'sent',
                `fonnte_response` TEXT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_broadcast_sender` (`sender_id`),
                INDEX `idx_broadcast_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $pdo->exec($sql);
        } catch (Throwable $e) {
            error_log("BroadcastMessage::ensureTableExists error: " . $e->getMessage());
        }
    }

    /**
     * Ambil riwayat pesan lengkap beserta relasi pengirim dan kelas
     */
    public function getAllWithDetails($limit = 50, $offset = 0) {
        $this->ensureTableExists();
        $sql = "SELECT b.*, u.name as sender_name, u.role as sender_role, c.name as class_name, c.level as class_level
                FROM {$this->table} b
                JOIN users u ON b.sender_id = u.id
                LEFT JOIN classes c ON b.target_class_id = c.id
                ORDER BY b.id DESC
                LIMIT :limit OFFSET :offset";
        $this->db->query($sql);
        $this->db->bind(':limit', (int)$limit, PDO::PARAM_INT);
        $this->db->bind(':offset', (int)$offset, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    /**
     * Hitung total catatan pesan
     */
    public function countAll() {
        $this->ensureTableExists();
        $this->db->query("SELECT COUNT(*) as total FROM {$this->table}");
        $row = $this->db->single();
        return (int)($row['total'] ?? 0);
    }

    /**
     * Ambil satu pesan berdasarkan ID
     */
    public function getById($id) {
        $this->ensureTableExists();
        $sql = "SELECT b.*, u.name as sender_name, c.name as class_name
                FROM {$this->table} b
                JOIN users u ON b.sender_id = u.id
                LEFT JOIN classes c ON b.target_class_id = c.id
                WHERE b.id = :id LIMIT 1";
        $this->db->query($sql);
        $this->db->bind(':id', (int)$id);
        return $this->db->single();
    }

    /**
     * Simpan catatan pesan baru
     */
    public function createMessage($data) {
        $this->ensureTableExists();
        $sql = "INSERT INTO {$this->table} 
                (sender_id, recipient_type, target_class_id, target_phone, target_names, total_recipients, message, status, fonnte_response, created_at)
                VALUES (:sender_id, :recipient_type, :target_class_id, :target_phone, :target_names, :total_recipients, :message, :status, :fonnte_response, NOW())";
        
        $this->db->query($sql);
        $this->db->bind(':sender_id', (int)$data['sender_id']);
        $this->db->bind(':recipient_type', $data['recipient_type']);
        $this->db->bind(':target_class_id', !empty($data['target_class_id']) ? (int)$data['target_class_id'] : null);
        $this->db->bind(':target_phone', $data['target_phone']);
        $this->db->bind(':target_names', $data['target_names'] ?? null);
        $this->db->bind(':total_recipients', (int)($data['total_recipients'] ?? 1));
        $this->db->bind(':message', $data['message']);
        $this->db->bind(':status', $data['status'] ?? 'sent');
        $this->db->bind(':fonnte_response', $data['fonnte_response'] ?? null);

        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Hapus riwayat pesan
     */
    public function deleteMessage($id) {
        $this->ensureTableExists();
        $this->db->query("DELETE FROM {$this->table} WHERE id = :id");
        $this->db->bind(':id', (int)$id);
        return $this->db->execute();
    }

    /**
     * Dapatkan ringkasan statistik pesan
     */
    public function getSummaryStats() {
        $this->ensureTableExists();
        $sql = "SELECT 
                    COUNT(*) as total_broadcasts,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as total_sent,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as total_failed,
                    COALESCE(SUM(total_recipients), 0) as total_recipients
                FROM {$this->table}";
        $this->db->query($sql);
        return $this->db->single();
    }
}
