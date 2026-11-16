<?php
/**
 * Classes Model (Rombongan Belajar)
 */
require_once __DIR__ . '/../core/Model.php';

class Classes extends Model {
    protected $table = 'classes';

    public function createClass($name, $level) {
        $sql = "INSERT INTO {$this->table} (name, level) VALUES (:name, :level)";
        $this->db->query($sql);
        $this->db->bind(':name', $name);
        $this->db->bind(':level', $level);
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function updateClass($id, $name, $level) {
        $sql = "UPDATE {$this->table} SET name = :name, level = :level WHERE id = :id";
        $this->db->query($sql);
        $this->db->bind(':name', $name);
        $this->db->bind(':level', $level);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function deleteClass($id) {
        // Cek apakah ada siswa di kelas ini
        if ($this->hasStudents($id)) {
            return false;
        }
        return $this->delete($id);
    }

    public function hasStudents($id) {
        $sql = "SELECT COUNT(*) as total FROM students WHERE class_id = :id";
        $row = $this->db->query($sql)->bind(':id', $id)->single();
        return $row && (int)$row['total'] > 0;
    }

    public function getWithStudentCount($search = '') {
        $sql = "SELECT c.*, COUNT(s.id) as total_students 
                FROM {$this->table} c
                LEFT JOIN students s ON c.id = s.class_id";
        
        if (!empty($search)) {
            $sql .= " WHERE c.name LIKE :search_name OR c.level LIKE :search_lvl";
        }

        $sql .= " GROUP BY c.id ORDER BY c.level ASC, c.name ASC";
        
        $this->db->query($sql);
        if (!empty($search)) {
            $searchTerm = '%' . $search . '%';
            $this->db->bind(':search_name', $searchTerm);
            $this->db->bind(':search_lvl', $searchTerm);
        }
        return $this->db->resultSet();
    }
}
