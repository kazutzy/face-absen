<?php
/**
 * Setting Model (Key-Value Configuration Storage)
 */
require_once __DIR__ . '/../core/Model.php';

class Setting extends Model {
    protected $table = 'settings';

    public function get($key, $default = null) {
        $sql = "SELECT setting_value FROM {$this->table} WHERE setting_key = :key LIMIT 1";
        $result = $this->db->query($sql)->bind(':key', $key)->single();
        return $result ? $result['setting_value'] : $default;
    }

    public function set($key, $value) {
        $sql = "INSERT INTO {$this->table} (setting_key, setting_value) 
                VALUES (:key, :value)
                ON DUPLICATE KEY UPDATE setting_value = :value_update";
        $this->db->query($sql);
        $this->db->bind(':key', $key);
        $this->db->bind(':value', $value);
        $this->db->bind(':value_update', $value);
        return $this->db->execute();
    }

    public function getAllAsMap() {
        $sql = "SELECT setting_key, setting_value FROM {$this->table}";
        $rows = $this->db->query($sql)->resultSet();
        $map = [];
        foreach ($rows as $row) {
            $map[$row['setting_key']] = $row['setting_value'];
        }
        return $map;
    }
}
