<?php
/**
 * Attendance Model
 */
require_once __DIR__ . '/../core/Model.php';

class Attendance extends Model {
    protected $table = 'attendances';

    public function calculateStatus($attendanceTime, $lateThreshold = null) {
        $lateThreshold = $lateThreshold ?? $this->getLateThresholdTime();
        if (empty($attendanceTime) || empty($lateThreshold)) {
            return 'hadir';
        }

        $attendanceTs = strtotime(date('Y-m-d') . ' ' . $attendanceTime);
        $lateTs = strtotime(date('Y-m-d') . ' ' . $lateThreshold);

        if ($attendanceTs === false || $lateTs === false) {
            return 'hadir';
        }

        return ($attendanceTs > $lateTs) ? 'terlambat' : 'hadir';
    }

    public function getLateThresholdTime() {
        $settingModel = new Setting();
        $lateTime = $settingModel->get('attendance_late_time', '07:15:00');
        return !empty($lateTime) ? $lateTime : '07:15:00';
    }

    public function validateFaceAttendancePayload($payload, $minConfidencePercent) {
        if (!is_array($payload)) {
            return false;
        }

        $recognizedUserId = $payload['recognized_user_id'] ?? null;
        $sessionUserId = $payload['user_id'] ?? null;
        $identifier = trim((string)($payload['identifier'] ?? ''));
        $confidenceScore = (float)($payload['confidence_score'] ?? 0);
        $threshold = (float)$minConfidencePercent;

        if (empty($identifier) || $sessionUserId === null || $recognizedUserId === null) {
            return false;
        }

        if ((int)$sessionUserId !== (int)$recognizedUserId) {
            return false;
        }

        if ($confidenceScore < $threshold) {
            return false;
        }

        return true;
    }

    public function hasAttendanceForDate($userId, $date = null) {
        $date = $date ?? date('Y-m-d');
        $sql = "SELECT id FROM {$this->table} WHERE user_id = :user_id AND attendance_date = :attendance_date LIMIT 1";
        $row = $this->db->query($sql)
            ->bind(':user_id', $userId)
            ->bind(':attendance_date', $date)
            ->single();
        return $row !== null;
    }

    public function deleteDuplicateForTest($userId, $date = null) {
        $date = $date ?? date('Y-m-d');
        $sql = "DELETE FROM {$this->table} WHERE user_id = :user_id AND attendance_date = :attendance_date";
        return $this->db->query($sql)
            ->bind(':user_id', $userId)
            ->bind(':attendance_date', $date)
            ->execute();
    }

    public function createAttendanceIfNotExists($data) {
        $userId = (int)($data['user_id'] ?? 0);
        $date = $data['attendance_date'] ?? date('Y-m-d');
        $time = $data['attendance_time'] ?? date('H:i:s');

        if ($userId <= 0) {
            return ['success' => false, 'message' => 'User tidak valid.'];
        }

        if ($this->hasAttendanceForDate($userId, $date)) {
            return ['success' => false, 'duplicate' => true, 'message' => 'Anda sudah melakukan absensi hari ini.'];
        }

        $status = $data['status'] ?? $this->calculateStatus($time, $data['late_threshold'] ?? $this->getLateThresholdTime());
        $method = $data['method'] ?? 'face_recognition';
        $notes = $data['notes'] ?? 'Absensi otomatis via Face Recognition';
        $confidenceScore = isset($data['confidence_score']) ? number_format((float)$data['confidence_score'], 2, '.', '') : null;

        $sql = "INSERT INTO {$this->table}
                (user_id, attendance_date, attendance_time, status, method, confidence_score, notes)
                VALUES (:user_id, :attendance_date, :attendance_time, :status, :method, :confidence_score, :notes)";

        try {
            $this->db->beginTransaction();
            $exists = $this->db->query("SELECT id FROM {$this->table} WHERE user_id = :user_id AND attendance_date = :attendance_date FOR UPDATE")
                ->bind(':user_id', $userId)
                ->bind(':attendance_date', $date)
                ->single();

            if ($exists) {
                $this->db->rollBack();
                return ['success' => false, 'duplicate' => true, 'message' => 'Anda sudah melakukan absensi hari ini.'];
            }

            $this->db->query($sql);
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':attendance_date', $date);
            $this->db->bind(':attendance_time', $time);
            $this->db->bind(':status', $status);
            $this->db->bind(':method', $method);
            $this->db->bind(':confidence_score', $confidenceScore);
            $this->db->bind(':notes', $notes);

            $inserted = $this->db->execute();
            if (!$inserted) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Gagal menyimpan absensi.'];
            }

            $attendanceId = $this->db->lastInsertId();
            $this->db->commit();

            return [
                'success' => true,
                'attendance_id' => $attendanceId,
                'user_id' => $userId,
                'attendance_date' => $date,
                'attendance_time' => $time,
                'status' => $status,
                'method' => $method,
                'confidence_score' => $confidenceScore,
                'notes' => $notes,
                'message' => 'ABSENSI BERHASIL'
            ];
        } catch (PDOException $e) {
            if ($this->db->getPdo()->inTransaction()) {
                $this->db->rollBack();
            }
            if ($e->errorInfo[1] == 1062) {
                return ['success' => false, 'duplicate' => true, 'message' => 'Anda sudah melakukan absensi hari ini.'];
            }
            throw $e;
        }
    }

    public function recordAttendance($data) {
        return $this->createAttendanceIfNotExists($data);
    }

    public function getTodayAttendanceByUser($userId) {
        $today = date('Y-m-d');
        $sql = "SELECT a.*, u.name, u.role
                FROM {$this->table} a
                JOIN users u ON a.user_id = u.id
                WHERE a.user_id = :user_id AND a.attendance_date = :today LIMIT 1";
        return $this->db->query($sql)
            ->bind(':user_id', $userId)
            ->bind(':today', $today)
            ->single();
    }

    public function getDailySummary($date = null) {
        $date = $date ?? date('Y-m-d');
        $sql = "SELECT
                    COUNT(*) as total_records,
                    SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                    SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as terlambat,
                    SUM(CASE WHEN status IN ('hadir', 'terlambat') THEN 1 ELSE 0 END) as attended
                FROM {$this->table}
                WHERE attendance_date = :date";
        return $this->db->query($sql)->bind(':date', $date)->single();
    }

    public function getLatestAttendances($limit = 8) {
        $limit = (int)$limit;
        $sql = "SELECT a.*, u.name, u.username, s.nis, c.name as class_name
                FROM {$this->table} a
                JOIN users u ON a.user_id = u.id
                LEFT JOIN students s ON s.user_id = u.id
                LEFT JOIN classes c ON c.id = s.class_id
                ORDER BY a.created_at DESC, a.attendance_date DESC, a.attendance_time DESC
                LIMIT :limit";
        $this->db->query($sql);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    public function getRecentByUser($userId, $limit = 5) {
        $limit = (int)$limit;
        $sql = "SELECT a.*, u.name
                FROM {$this->table} a
                JOIN users u ON a.user_id = u.id
                WHERE a.user_id = :user_id
                ORDER BY a.attendance_date DESC, a.attendance_time DESC
                LIMIT :limit";
        $this->db->query($sql);
        $this->db->bind(':user_id', $userId, PDO::PARAM_INT);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    public function getClassSummary($date = null) {
        $date = $date ?? date('Y-m-d');
        $sql = "SELECT c.id, c.name, c.level,
                    COUNT(s.id) as total_students,
                    SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                    SUM(CASE WHEN a.status = 'terlambat' THEN 1 ELSE 0 END) as terlambat
                FROM classes c
                LEFT JOIN students s ON s.class_id = c.id
                LEFT JOIN attendances a ON a.user_id = s.user_id AND a.attendance_date = :date
                GROUP BY c.id, c.name, c.level
                ORDER BY c.level ASC, c.name ASC";
        return $this->db->query($sql)->bind(':date', $date)->resultSet();
    }

    public function getDailyAttendanceList($date = null) {
        $date = $date ?? date('Y-m-d');
        $sql = "SELECT a.*, u.name, u.username, u.role, s.nis, c.name as class_name
                FROM {$this->table} a
                JOIN users u ON a.user_id = u.id
                LEFT JOIN students s ON u.id = s.user_id
                LEFT JOIN classes c ON s.class_id = c.id
                WHERE a.attendance_date = :date
                ORDER BY a.attendance_time DESC";
        return $this->db->query($sql)->bind(':date', $date)->resultSet();
    }

    public function getSummaryByDate($date = null) {
        $date = $date ?? date('Y-m-d');
        $sql = "SELECT status, COUNT(*) as count 
                FROM {$this->table} 
                WHERE attendance_date = :date 
                GROUP BY status";
        return $this->db->query($sql)->bind(':date', $date)->resultSet();
    }

    public function getUserSummary($userId) {
        $sql = "SELECT 
                    COUNT(*) as total_attendance,
                    SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                    SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as terlambat,
                    SUM(CASE WHEN MONTH(attendance_date) = MONTH(CURRENT_DATE()) AND YEAR(attendance_date) = YEAR(CURRENT_DATE()) THEN 1 ELSE 0 END) as this_month
                FROM {$this->table}
                WHERE user_id = :user_id";
        return $this->db->query($sql)->bind(':user_id', $userId)->single();
    }

    /**
     * Mengambil log absensi dengan multi-filter, search, pagination, dan sorting
     */
    public function getFilteredReport($filters = [], $limit = null, $offset = null) {
        $conditions = [];
        $bindings = [];

        // 1. Filter Tanggal Spesifik
        if (!empty($filters['date'])) {
            $conditions[] = "a.attendance_date = :f_date";
            $bindings[':f_date'] = $filters['date'];
        }

        // 2. Filter Range Tanggal (Start & End Date)
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $conditions[] = "a.attendance_date BETWEEN :f_start_date AND :f_end_date";
            $bindings[':f_start_date'] = $filters['start_date'];
            $bindings[':f_end_date'] = $filters['end_date'];
        } elseif (!empty($filters['start_date'])) {
            $conditions[] = "a.attendance_date >= :f_start_date";
            $bindings[':f_start_date'] = $filters['start_date'];
        } elseif (!empty($filters['end_date'])) {
            $conditions[] = "a.attendance_date <= :f_end_date";
            $bindings[':f_end_date'] = $filters['end_date'];
        }

        // 3. Filter Kelas
        if (!empty($filters['class_id'])) {
            $conditions[] = "s.class_id = :f_class_id";
            $bindings[':f_class_id'] = (int)$filters['class_id'];
        }

        // 4. Filter User ID / Peserta Didik Sendiri
        if (!empty($filters['user_id'])) {
            $conditions[] = "a.user_id = :f_user_id";
            $bindings[':f_user_id'] = (int)$filters['user_id'];
        }

        // 5. Filter Student ID
        if (!empty($filters['student_id'])) {
            $conditions[] = "s.id = :f_student_id";
            $bindings[':f_student_id'] = (int)$filters['student_id'];
        }

        // 6. Filter Role (peserta_didik / guru)
        if (!empty($filters['role'])) {
            $conditions[] = "u.role = :f_role";
            $bindings[':f_role'] = $filters['role'];
        }

        // 7. Filter Status Kehadiran
        if (!empty($filters['status'])) {
            $statusVal = strtolower($filters['status']);
            if ($statusVal === 'tidak_hadir' || $statusVal === 'tidak hadir') {
                $statusVal = 'alpa';
            }
            $conditions[] = "a.status = :f_status";
            $bindings[':f_status'] = $statusVal;
        }

        // 8. Search Keyword (Nama, NIS, NIP, Username)
        if (!empty($filters['search'])) {
            $conditions[] = "(u.name LIKE :search_name OR u.username LIKE :search_uname OR s.nis LIKE :search_nis OR t.nip LIKE :search_nip)";
            $searchPattern = '%' . trim($filters['search']) . '%';
            $bindings[':search_name'] = $searchPattern;
            $bindings[':search_uname'] = $searchPattern;
            $bindings[':search_nis'] = $searchPattern;
            $bindings[':search_nip'] = $searchPattern;
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        // Sorting
        $allowedSorts = [
            'attendance_date' => 'a.attendance_date',
            'attendance_time' => 'a.attendance_time',
            'name' => 'u.name',
            'status' => 'a.status',
            'class_name' => 'c.name'
        ];
        $sortCol = $allowedSorts[$filters['sort'] ?? ''] ?? 'a.attendance_date';
        $sortDir = strtoupper($filters['order'] ?? '') === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT a.*, u.name, u.username, u.role, s.nis, c.name as class_name, c.level as class_level, t.nip
                FROM {$this->table} a
                JOIN users u ON a.user_id = u.id
                LEFT JOIN students s ON s.user_id = u.id
                LEFT JOIN classes c ON c.id = s.class_id
                LEFT JOIN teachers t ON t.user_id = u.id
                {$whereClause}
                ORDER BY {$sortCol} {$sortDir}, a.attendance_time {$sortDir}";

        if ($limit !== null) {
            $limit = (int)$limit;
            $offset = (int)($offset ?? 0);
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        $query = $this->db->query($sql);
        foreach ($bindings as $key => $val) {
            $query->bind($key, $val);
        }

        return $query->resultSet();
    }

    /**
     * Menghitung total data absensi untuk pagination
     */
    public function countFilteredReport($filters = []) {
        $conditions = [];
        $bindings = [];

        if (!empty($filters['date'])) {
            $conditions[] = "a.attendance_date = :f_date";
            $bindings[':f_date'] = $filters['date'];
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $conditions[] = "a.attendance_date BETWEEN :f_start_date AND :f_end_date";
            $bindings[':f_start_date'] = $filters['start_date'];
            $bindings[':f_end_date'] = $filters['end_date'];
        } elseif (!empty($filters['start_date'])) {
            $conditions[] = "a.attendance_date >= :f_start_date";
            $bindings[':f_start_date'] = $filters['start_date'];
        } elseif (!empty($filters['end_date'])) {
            $conditions[] = "a.attendance_date <= :f_end_date";
            $bindings[':f_end_date'] = $filters['end_date'];
        }

        if (!empty($filters['class_id'])) {
            $conditions[] = "s.class_id = :f_class_id";
            $bindings[':f_class_id'] = (int)$filters['class_id'];
        }

        if (!empty($filters['user_id'])) {
            $conditions[] = "a.user_id = :f_user_id";
            $bindings[':f_user_id'] = (int)$filters['user_id'];
        }

        if (!empty($filters['student_id'])) {
            $conditions[] = "s.id = :f_student_id";
            $bindings[':f_student_id'] = (int)$filters['student_id'];
        }

        if (!empty($filters['role'])) {
            $conditions[] = "u.role = :f_role";
            $bindings[':f_role'] = $filters['role'];
        }

        if (!empty($filters['status'])) {
            $statusVal = strtolower($filters['status']);
            if ($statusVal === 'tidak_hadir' || $statusVal === 'tidak hadir') {
                $statusVal = 'alpa';
            }
            $conditions[] = "a.status = :f_status";
            $bindings[':f_status'] = $statusVal;
        }

        if (!empty($filters['search'])) {
            $conditions[] = "(u.name LIKE :search_name OR u.username LIKE :search_uname OR s.nis LIKE :search_nis OR t.nip LIKE :search_nip)";
            $searchPattern = '%' . trim($filters['search']) . '%';
            $bindings[':search_name'] = $searchPattern;
            $bindings[':search_uname'] = $searchPattern;
            $bindings[':search_nis'] = $searchPattern;
            $bindings[':search_nip'] = $searchPattern;
        }

        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "SELECT COUNT(*) as total
                FROM {$this->table} a
                JOIN users u ON a.user_id = u.id
                LEFT JOIN students s ON s.user_id = u.id
                LEFT JOIN classes c ON c.id = s.class_id
                LEFT JOIN teachers t ON t.user_id = u.id
                {$whereClause}";

        $query = $this->db->query($sql);
        foreach ($bindings as $key => $val) {
            $query->bind($key, $val);
        }

        $res = $query->single();
        return (int)($res['total'] ?? 0);
    }

    /**
     * Rekap Absensi Bulanan teragregasi per user
     */
    public function getMonthlyRecap($month, $year, $filters = []) {
        $conditions = ["MONTH(a.attendance_date) = :month", "YEAR(a.attendance_date) = :year"];
        $bindings = [
            ':month' => (int)$month,
            ':year' => (int)$year
        ];

        if (!empty($filters['class_id'])) {
            $conditions[] = "s.class_id = :class_id";
            $bindings[':class_id'] = (int)$filters['class_id'];
        }

        if (!empty($filters['role'])) {
            $conditions[] = "u.role = :role";
            $bindings[':role'] = $filters['role'];
        }

        if (!empty($filters['user_id'])) {
            $conditions[] = "a.user_id = :user_id";
            $bindings[':user_id'] = (int)$filters['user_id'];
        }

        $whereClause = 'WHERE ' . implode(' AND ', $conditions);

        $sql = "SELECT u.id as user_id, u.name, u.username, u.role, s.nis, c.name as class_name, t.nip,
                    SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) as total_hadir,
                    SUM(CASE WHEN a.status = 'terlambat' THEN 1 ELSE 0 END) as total_terlambat,
                    SUM(CASE WHEN a.status = 'izin' THEN 1 ELSE 0 END) as total_izin,
                    SUM(CASE WHEN a.status = 'sakit' THEN 1 ELSE 0 END) as total_sakit,
                    SUM(CASE WHEN a.status IN ('alpa', 'tidak_hadir') THEN 1 ELSE 0 END) as total_tidak_hadir,
                    COUNT(a.id) as total_kehadiran
                FROM {$this->table} a
                JOIN users u ON a.user_id = u.id
                LEFT JOIN students s ON s.user_id = u.id
                LEFT JOIN classes c ON c.id = s.class_id
                LEFT JOIN teachers t ON t.user_id = u.id
                {$whereClause}
                GROUP BY u.id, u.name, u.username, u.role, s.nis, c.name, t.nip
                ORDER BY c.name ASC, u.name ASC";

        $query = $this->db->query($sql);
        foreach ($bindings as $key => $val) {
            $query->bind($key, $val);
        }

        return $query->resultSet();
    }
}
