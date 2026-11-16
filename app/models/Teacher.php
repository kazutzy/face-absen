<?php
/**
 * Teacher Model
 */
require_once __DIR__ . '/../core/Model.php';

class Teacher extends Model {
    protected $table = 'teachers';

    public function getFilteredList($search = '') {
        $sql = "SELECT t.*, u.name, u.username, u.email, u.status 
                FROM {$this->table} t
                JOIN users u ON t.user_id = u.id";

        if (!empty($search)) {
            $sql .= " WHERE u.name LIKE :search_name OR t.nip LIKE :search_nip OR u.username LIKE :search_user OR u.email LIKE :search_email OR t.phone LIKE :search_phone";
        }

        $sql .= " ORDER BY u.name ASC";

        $this->db->query($sql);
        if (!empty($search)) {
            $searchTerm = '%' . $search . '%';
            $this->db->bind(':search_name', $searchTerm);
            $this->db->bind(':search_nip', $searchTerm);
            $this->db->bind(':search_user', $searchTerm);
            $this->db->bind(':search_email', $searchTerm);
            $this->db->bind(':search_phone', $searchTerm);
        }
        return $this->db->resultSet();
    }

    public function findTeacherDetailById($id) {
        $sql = "SELECT t.*, u.name, u.username, u.email, u.status, u.created_at as user_created_at
                FROM {$this->table} t
                JOIN users u ON t.user_id = u.id
                WHERE t.id = :id LIMIT 1";
        return $this->db->query($sql)->bind(':id', $id)->single();
    }

    public function findByUserId($userId) {
        $sql = "SELECT t.*, u.name, u.username, u.email, u.status 
                FROM {$this->table} t
                JOIN users u ON t.user_id = u.id
                WHERE t.user_id = :user_id LIMIT 1";
        return $this->db->query($sql)->bind(':user_id', $userId)->single();
    }

    public function findByNip($nip) {
        $sql = "SELECT t.*, u.name, u.username, u.email, u.status 
                FROM {$this->table} t
                JOIN users u ON t.user_id = u.id
                WHERE t.nip = :nip LIMIT 1";
        return $this->db->query($sql)->bind(':nip', $nip)->single();
    }

    public function isNipUnique($nip, $excludeId = null) {
        $sql = "SELECT id FROM {$this->table} WHERE nip = :nip";
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
        }
        $sql .= " LIMIT 1";

        $this->db->query($sql)->bind(':nip', $nip);
        if ($excludeId !== null) {
            $this->db->bind(':exclude_id', $excludeId);
        }
        $row = $this->db->single();
        return $row === null;
    }

    // Atomic Creation (User + Teacher)
    public function createTeacher($userData, $teacherData) {
        $pdo = $this->db->getPdo();
        try {
            $pdo->beginTransaction();

            // 1. Insert ke users (termasuk email)
            $stmtUser = $pdo->prepare("INSERT INTO users (username, password, name, email, role, status) VALUES (?, ?, ?, ?, 'guru', ?)");
            $stmtUser->execute([
                $userData['username'],
                password_hash($userData['password'], PASSWORD_BCRYPT),
                $userData['name'],
                !empty($userData['email']) ? $userData['email'] : null,
                $userData['status'] ?? 'active'
            ]);
            $userId = $pdo->lastInsertId();

            // 2. Insert ke teachers
            $stmtTeacher = $pdo->prepare("INSERT INTO teachers (user_id, nip, phone) VALUES (?, ?, ?)");
            $stmtTeacher->execute([
                $userId,
                $teacherData['nip'],
                $teacherData['phone'] ?? null
            ]);
            $teacherId = $pdo->lastInsertId();

            $pdo->commit();
            return $teacherId;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    // Atomic Update (User + Teacher)
    public function updateTeacher($teacherId, $userData, $teacherData) {
        $teacher = $this->findTeacherDetailById($teacherId);
        if (!$teacher) {
            return false;
        }

        $pdo = $this->db->getPdo();
        try {
            $pdo->beginTransaction();

            // 1. Update users (termasuk email)
            $emailVal = !empty($userData['email']) ? $userData['email'] : null;
            if (!empty($userData['password'])) {
                $stmtUser = $pdo->prepare("UPDATE users SET name = ?, email = ?, username = ?, password = ?, status = ? WHERE id = ?");
                $stmtUser->execute([
                    $userData['name'],
                    $emailVal,
                    $userData['username'],
                    password_hash($userData['password'], PASSWORD_BCRYPT),
                    $userData['status'],
                    $teacher['user_id']
                ]);
            } else {
                $stmtUser = $pdo->prepare("UPDATE users SET name = ?, email = ?, username = ?, status = ? WHERE id = ?");
                $stmtUser->execute([
                    $userData['name'],
                    $emailVal,
                    $userData['username'],
                    $userData['status'],
                    $teacher['user_id']
                ]);
            }

            // 2. Update teachers
            $stmtTeacher = $pdo->prepare("UPDATE teachers SET nip = ?, phone = ? WHERE id = ?");
            $stmtTeacher->execute([
                $teacherData['nip'],
                $teacherData['phone'] ?? null,
                $teacherId
            ]);

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    // Atomic Delete
    public function deleteTeacher($teacherId) {
        $teacher = $this->findTeacherDetailById($teacherId);
        if (!$teacher) {
            return false;
        }

        $userModel = new User();
        return $userModel->delete($teacher['user_id']);
    }
}
