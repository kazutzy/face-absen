<?php
/**
 * AuditLog Model - Pencatatan Jejak Audit Keamanan & Perubahan Data Sistem
 */
require_once __DIR__ . '/../core/Model.php';

class AuditLog extends Model {
    protected $table = 'audit_logs';

    /**
     * Mencatat aksi audit ke database
     * @param string $action Nama aksi (misal: 'UPDATE_SETTING', 'MODIFY_ATTENDANCE', 'DELETE_STUDENT')
     * @param string $tableName Nama tabel yang dimodifikasi
     * @param int|null $recordId ID record terkait
     * @param mixed $oldValues Nilai lama (opsional)
     * @param mixed $newValues Nilai baru (opsional)
     * @param int|null $userId ID user pelaku (default dari Auth::id())
     * @return bool
     */
    public static function record($action, $tableName, $recordId = null, $oldValues = null, $newValues = null, $userId = null) {
        try {
            $db = Database::getInstance();
            $actorId = $userId ?? (class_exists('Auth') ? Auth::id() : null);
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'CLI/Unknown', 0, 255);

            $oldStr = is_array($oldValues) || is_object($oldValues) ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : (string)$oldValues;
            $newStr = is_array($newValues) || is_object($newValues) ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : (string)$newValues;

            $sql = "INSERT INTO audit_logs 
                    (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
                    VALUES (:user_id, :action, :table_name, :record_id, :old_values, :new_values, :ip_address, :user_agent)";

            $db->query($sql);
            $db->bind(':user_id', $actorId);
            $db->bind(':action', $action);
            $db->bind(':table_name', $tableName);
            $db->bind(':record_id', $recordId);
            $db->bind(':old_values', !empty($oldStr) ? $oldStr : null);
            $db->bind(':new_values', !empty($newStr) ? $newStr : null);
            $db->bind(':ip_address', $ipAddress);
            $db->bind(':user_agent', $userAgent);

            return $db->execute();
        } catch (Throwable $e) {
            // Audit logging tidak boleh menyebabkan blocking aplikasi jika terjadi exception
            error_log("AuditLog Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mengambil daftar log audit terbaru
     */
    public function getRecent($limit = 50) {
        $limit = (int)$limit;
        $sql = "SELECT al.*, u.name as user_name, u.username
                FROM {$this->table} al
                LEFT JOIN users u ON al.user_id = u.id
                ORDER BY al.created_at DESC
                LIMIT :limit";
        $this->db->query($sql);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        return $this->db->resultSet();
    }
}
