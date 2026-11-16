<?php
/**
 * Base Model Class
 */
abstract class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function all() {
        $sql = "SELECT * FROM {$this->table} ORDER BY {$this->primaryKey} DESC";
        return $this->db->query($sql)->resultSet();
    }

    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        return $this->db->query($sql)->bind(':id', $id)->single();
    }

    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        return $this->db->query($sql)->bind(':id', $id)->execute();
    }

    public function count() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $row = $this->db->query($sql)->single();
        return $row ? (int)$row['total'] : 0;
    }
}
