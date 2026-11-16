<?php
/**
 * User Model
 */
require_once __DIR__ . '/../core/Model.php';

class User extends Model {
    protected $table = 'users';

    public function findByUsername($username) {
        $sql = "SELECT * FROM {$this->table} WHERE username = :username LIMIT 1";
        return $this->db->query($sql)->bind(':username', $username)->single();
    }

    public function findByRole($role) {
        $sql = "SELECT * FROM {$this->table} WHERE role = :role ORDER BY name ASC";
        return $this->db->query($sql)->bind(':role', $role)->resultSet();
    }

    public function create($data) {
        $sql = "INSERT INTO {$this->table} (username, password, name, email, role, status) 
                VALUES (:username, :password, :name, :email, :role, :status)";
        
        $this->db->query($sql);
        $this->db->bind(':username', $data['username']);
        $this->db->bind(':password', password_hash($data['password'], PASSWORD_BCRYPT));
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':email', !empty($data['email']) ? $data['email'] : null);
        $this->db->bind(':role', $data['role'] ?? 'peserta_didik');
        $this->db->bind(':status', $data['status'] ?? 'active');
        
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function updateProfile($id, $data) {
        $sql = "UPDATE {$this->table} SET name = :name, email = :email, status = :status WHERE id = :id";
        $this->db->query($sql);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':email', !empty($data['email']) ? $data['email'] : null);
        $this->db->bind(':status', $data['status']);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function updatePassword($id, $newPassword) {
        $sql = "UPDATE {$this->table} SET password = :password WHERE id = :id";
        $this->db->query($sql);
        $this->db->bind(':password', password_hash($newPassword, PASSWORD_BCRYPT));
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function isUsernameUnique($username, $excludeId = null) {
        $sql = "SELECT id FROM {$this->table} WHERE username = :username";
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
        }
        $sql .= " LIMIT 1";
        
        $this->db->query($sql)->bind(':username', $username);
        if ($excludeId !== null) {
            $this->db->bind(':exclude_id', $excludeId);
        }
        $row = $this->db->single();
        return $row === null;
    }

    public function isEmailUnique($email, $excludeId = null) {
        if (empty($email)) {
            return true;
        }
        $sql = "SELECT id FROM {$this->table} WHERE email = :email";
        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
        }
        $sql .= " LIMIT 1";
        
        $this->db->query($sql)->bind(':email', $email);
        if ($excludeId !== null) {
            $this->db->bind(':exclude_id', $excludeId);
        }
        $row = $this->db->single();
        return $row === null;
    }

    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        return $this->db->query($sql)->bind(':email', $email)->single();
    }

    public function verifyCredentials($username, $plainPassword) {
        $user = $this->findByUsername($username);
        if ($user && $user['status'] === 'active') {
            if (password_verify($plainPassword, $user['password'])) {
                return $user;
            }
        }
        return false;
    }
}
