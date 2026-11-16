<?php
/**
 * Student Model
 */
require_once __DIR__ . '/../core/Model.php';

class Student extends Model {
    protected $table = 'students';

    public function getFilteredList($search = '', $classId = null) {
        $sql = "SELECT s.*, u.name, u.username, u.email, u.status, c.name as class_name, c.level
                FROM {$this->table} s
                JOIN users u ON s.user_id = u.id
                JOIN classes c ON s.class_id = c.id
                WHERE 1=1";
        
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (u.name LIKE :search_name OR s.nis LIKE :search_nis OR u.username LIKE :search_user OR u.email LIKE :search_email OR s.phone LIKE :search_phone)";
            $searchTerm = '%' . $search . '%';
            $params[':search_name'] = $searchTerm;
            $params[':search_nis'] = $searchTerm;
            $params[':search_user'] = $searchTerm;
            $params[':search_email'] = $searchTerm;
            $params[':search_phone'] = $searchTerm;
        }

        if (!empty($classId)) {
            $sql .= " AND s.class_id = :class_id";
            $params[':class_id'] = $classId;
        }

        $sql .= " ORDER BY c.level ASC, c.name ASC, u.name ASC";

        $this->db->query($sql);
        foreach ($params as $param => $val) {
            $this->db->bind($param, $val);
        }
        return $this->db->resultSet();
    }

    public function findStudentDetailById($id) {
        $sql = "SELECT s.*, u.name, u.username, u.email, u.status, u.created_at as user_created_at, 
                       c.name as class_name, c.level
                FROM {$this->table} s
                JOIN users u ON s.user_id = u.id
                JOIN classes c ON s.class_id = c.id
                WHERE s.id = :id LIMIT 1";
        return $this->db->query($sql)->bind(':id', $id)->single();
    }

    public function findByUserId($userId) {
        $sql = "SELECT s.*, u.name, u.username, u.email, u.status, c.name as class_name, c.level
                FROM {$this->table} s
                JOIN users u ON s.user_id = u.id
                JOIN classes c ON s.class_id = c.id
                WHERE s.user_id = :user_id LIMIT 1";
        return $this->db->query($sql)->bind(':user_id', $userId)->single();
    }

    public function findByNis($nis) {
        $sql = "SELECT s.*, u.name, u.username, u.email, u.status, c.name as class_name, c.level
                FROM {$this->table} s
                JOIN users u ON s.user_id = u.id
                JOIN classes c ON s.class_id = c.id
                WHERE s.nis = :nis LIMIT 1";
        return $this->db->query($sql)->bind(':nis', $nis)->single();
    }

    public function getAllWithDetails() {
        $sql = "SELECT s.*, u.name, u.username, u.email, u.status, c.name as class_name, c.level
                FROM {$this->table} s
                JOIN users u ON s.user_id = u.id
                JOIN classes c ON s.class_id = c.id
                ORDER BY u.name ASC";
        return $this->db->query($sql)->resultSet();
    }

    public function findByFaceModelIdentifier($identifier) {
        $sql = "SELECT s.*, u.name, u.username, u.email, u.status, c.name as class_name, c.level
                FROM {$this->table} s
                JOIN users u ON s.user_id = u.id
                JOIN classes c ON s.class_id = c.id
                WHERE s.face_model_identifier = :identifier LIMIT 1";
        return $this->db->query($sql)->bind(':identifier', $identifier)->single();
    }

    /**
     * Cari siswa berdasarkan NIS utama (label Teachable Machine) atau face_model_identifier
     */
    public function findByIdentifierOrNis($identifier) {
        $student = $this->findByNis($identifier);
        if ($student) {
            return $student;
        }
        return $this->findByFaceModelIdentifier($identifier);
    }

    public function isNisUnique($nis, $excludeId = null) {
        $sql = "SELECT id FROM {$this->table} WHERE nis = :nis";
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
        }
        $sql .= " LIMIT 1";

        $this->db->query($sql)->bind(':nis', $nis);
        if ($excludeId !== null) {
            $this->db->bind(':exclude_id', $excludeId);
        }
        $row = $this->db->single();
        return $row === null;
    }

    // Atomic Creation (User + Student)
    public function createStudent($userData, $studentData) {
        $pdo = $this->db->getPdo();
        try {
            $pdo->beginTransaction();

            // 1. Insert ke users (termasuk email)
            $stmtUser = $pdo->prepare("INSERT INTO users (username, password, name, email, role, status) VALUES (?, ?, ?, ?, 'peserta_didik', ?)");
            $stmtUser->execute([
                $userData['username'],
                password_hash($userData['password'], PASSWORD_BCRYPT),
                $userData['name'],
                !empty($userData['email']) ? $userData['email'] : null,
                $userData['status'] ?? 'active'
            ]);
            $userId = $pdo->lastInsertId();

            // 2. Insert ke students (face_model_identifier default ke NIS)
            $faceIdentifier = !empty($studentData['face_model_identifier']) ? $studentData['face_model_identifier'] : $studentData['nis'];
            $stmtStudent = $pdo->prepare("INSERT INTO students (user_id, nis, class_id, face_model_identifier, phone) VALUES (?, ?, ?, ?, ?)");
            $stmtStudent->execute([
                $userId,
                $studentData['nis'],
                $studentData['class_id'],
                $faceIdentifier,
                $studentData['phone'] ?? null
            ]);
            $studentId = $pdo->lastInsertId();

            $pdo->commit();
            return $studentId;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    // Atomic Update (User + Student)
    public function updateStudent($studentId, $userData, $studentData) {
        $student = $this->findStudentDetailById($studentId);
        if (!$student) {
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
                    $student['user_id']
                ]);
            } else {
                $stmtUser = $pdo->prepare("UPDATE users SET name = ?, email = ?, username = ?, status = ? WHERE id = ?");
                $stmtUser->execute([
                    $userData['name'],
                    $emailVal,
                    $userData['username'],
                    $userData['status'],
                    $student['user_id']
                ]);
            }

            // 2. Update students (face_model_identifier default ke NIS jika tidak diset khusus)
            $faceIdentifier = !empty($studentData['face_model_identifier']) ? $studentData['face_model_identifier'] : $studentData['nis'];
            $stmtStudent = $pdo->prepare("UPDATE students SET nis = ?, class_id = ?, face_model_identifier = ?, phone = ? WHERE id = ?");
            $stmtStudent->execute([
                $studentData['nis'],
                $studentData['class_id'],
                $faceIdentifier,
                $studentData['phone'] ?? null,
                $studentId
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
    public function deleteStudent($studentId) {
        $student = $this->findStudentDetailById($studentId);
        if (!$student) {
            return false;
        }

        // Hapus akun di users (foreign key cascade akan menghapus student & attendance terkait)
        $userModel = new User();
        return $userModel->delete($student['user_id']);
    }
}
