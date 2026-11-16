<?php
/**
 * AttendanceController - Modul Rekap Absensi & Laporan Kehadiran Multi-Role
 */
require_once __DIR__ . '/../core/Controller.php';

class AttendanceController extends Controller {

    public function __construct() {
        Auth::requireLogin();
    }

    /**
     * Halaman Utama Rekap Absensi (Log Harian / Range Tanggal / Filter)
     * GET /attendance atau GET /admin/attendance
     */
    public function index() {
        $user = Auth::user();
        $role = $user['role'];

        $attendanceModel = $this->model('Attendance');
        $classesModel = $this->model('Classes');
        $studentModel = $this->model('Student');
        $teacherModel = $this->model('Teacher');

        // Parameter Filter dari Query String
        $filters = [
            'date' => trim($_GET['date'] ?? ''),
            'start_date' => trim($_GET['start_date'] ?? ''),
            'end_date' => trim($_GET['end_date'] ?? ''),
            'class_id' => trim($_GET['class_id'] ?? ''),
            'student_id' => trim($_GET['student_id'] ?? ''),
            'role' => trim($_GET['role'] ?? ''),
            'status' => trim($_GET['status'] ?? ''),
            'search' => trim($_GET['search'] ?? ''),
            'sort' => trim($_GET['sort'] ?? 'attendance_date'),
            'order' => trim($_GET['order'] ?? 'DESC')
        ];

        // Enforce RBAC / Data Isolation
        if ($role === 'peserta_didik') {
            // Siswa hanya boleh melihat datanya sendiri
            $filters['user_id'] = $user['id'];
            $filters['role'] = 'peserta_didik';
        } elseif ($role === 'guru') {
            // Guru dapat melihat data absensi sekolah & kelas
            // Jika guru memilih filter diri sendiri
            if (isset($_GET['scope']) && $_GET['scope'] === 'pribadi') {
                $filters['user_id'] = $user['id'];
            }
        }
        // Super User memiliki akses ke seluruh data tanpa pembatasan

        // Pagination setup
        $perPage = 15;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $totalRows = $attendanceModel->countFilteredReport($filters);
        $totalPages = max(1, ceil($totalRows / $perPage));
        $offset = ($page - 1) * $perPage;

        $attendances = $attendanceModel->getFilteredReport($filters, $perPage, $offset);

        $data = [
            'title' => 'Rekap Absensi - ' . APP_NAME,
            'user' => $user,
            'role' => $role,
            'attendances' => $attendances,
            'classes' => $classesModel->all(),
            'filters' => $filters,
            'total_rows' => $totalRows,
            'page' => $page,
            'total_pages' => $totalPages,
            'per_page' => $perPage
        ];

        $this->view('attendance/index', $data);
    }

    /**
     * Halaman Rekap Absensi Bulanan (Nama | Hadir | Terlambat | Izin | Sakit | Tidak Hadir)
     * GET /attendance/monthly
     */
    public function monthly() {
        $user = Auth::user();
        $role = $user['role'];

        $attendanceModel = $this->model('Attendance');
        $classesModel = $this->model('Classes');

        $month = (int)($_GET['month'] ?? date('n'));
        $year = (int)($_GET['year'] ?? date('Y'));
        $classId = trim($_GET['class_id'] ?? '');
        $filterRole = trim($_GET['role'] ?? '');

        $filters = [];
        if (!empty($classId)) {
            $filters['class_id'] = $classId;
        }
        if (!empty($filterRole)) {
            $filters['role'] = $filterRole;
        }

        // Enforce RBAC
        if ($role === 'peserta_didik') {
            $filters['user_id'] = $user['id'];
        }

        $recapData = $attendanceModel->getMonthlyRecap($month, $year, $filters);

        $data = [
            'title' => 'Rekap Absensi Bulanan - ' . APP_NAME,
            'user' => $user,
            'role' => $role,
            'month' => $month,
            'year' => $year,
            'class_id' => $classId,
            'filter_role' => $filterRole,
            'classes' => $classesModel->all(),
            'recap' => $recapData
        ];

        $this->view('attendance/monthly', $data);
    }

    /**
     * Export Rekap Absensi ke format Excel / CSV (.xls)
     * GET /attendance/export_excel
     */
    public function export_excel() {
        $user = Auth::user();
        $role = $user['role'];
        $attendanceModel = $this->model('Attendance');

        $filters = [
            'date' => trim($_GET['date'] ?? ''),
            'start_date' => trim($_GET['start_date'] ?? ''),
            'end_date' => trim($_GET['end_date'] ?? ''),
            'class_id' => trim($_GET['class_id'] ?? ''),
            'role' => trim($_GET['role'] ?? ''),
            'status' => trim($_GET['status'] ?? ''),
            'search' => trim($_GET['search'] ?? '')
        ];

        if ($role === 'peserta_didik') {
            $filters['user_id'] = $user['id'];
        }

        $attendances = $attendanceModel->getFilteredReport($filters);

        $filename = 'rekap_absensi_' . date('Ymd_His') . '.xls';

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Rekap Absensi</title></head>';
        echo '<body>';
        echo '<table border="1">';
        echo '<thead>';
        echo '<tr style="background-color: #0d6efd; color: #ffffff; font-weight: bold;">';
        echo '<th>No</th><th>Nama</th><th>Role / NIS</th><th>Kelas</th><th>Tanggal</th><th>Jam</th><th>Status</th><th>Metode</th><th>Confidence</th><th>Keterangan</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        $no = 1;
        foreach ($attendances as $row) {
            $conf = !empty($row['confidence_score']) ? $row['confidence_score'] . '%' : '-';
            echo '<tr>';
            echo '<td>' . $no++ . '</td>';
            echo '<td>' . htmlspecialchars($row['name'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['nis'] ?? ($row['nip'] ?? $row['role'])) . '</td>';
            echo '<td>' . htmlspecialchars($row['class_name'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['attendance_date'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['attendance_time'] ?? '-') . '</td>';
            echo '<td>' . strtoupper(htmlspecialchars($row['status'] ?? '-')) . '</td>';
            echo '<td>' . htmlspecialchars($row['method'] ?? '-') . '</td>';
            echo '<td>' . $conf . '</td>';
            echo '<td>' . htmlspecialchars($row['notes'] ?? '-') . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</body></html>';
        exit;
    }

    /**
     * Tampilan Print-Friendly untuk Cetak / Save as PDF
     * GET /attendance/print
     */
    public function print() {
        $user = Auth::user();
        $role = $user['role'];
        $attendanceModel = $this->model('Attendance');

        $type = $_GET['type'] ?? 'daily'; // 'daily' or 'monthly'

        if ($type === 'monthly') {
            $month = (int)($_GET['month'] ?? date('n'));
            $year = (int)($_GET['year'] ?? date('Y'));
            $classId = trim($_GET['class_id'] ?? '');

            $filters = [];
            if (!empty($classId)) {
                $filters['class_id'] = $classId;
            }
            if ($role === 'peserta_didik') {
                $filters['user_id'] = $user['id'];
            }

            $recap = $attendanceModel->getMonthlyRecap($month, $year, $filters);

            $data = [
                'title' => 'Cetak Rekap Absensi Bulanan (' . $month . '/' . $year . ')',
                'type' => 'monthly',
                'month' => $month,
                'year' => $year,
                'recap' => $recap,
                'user' => $user
            ];
        } else {
            $filters = [
                'date' => trim($_GET['date'] ?? ''),
                'start_date' => trim($_GET['start_date'] ?? ''),
                'end_date' => trim($_GET['end_date'] ?? ''),
                'class_id' => trim($_GET['class_id'] ?? ''),
                'role' => trim($_GET['role'] ?? ''),
                'status' => trim($_GET['status'] ?? ''),
                'search' => trim($_GET['search'] ?? '')
            ];

            if ($role === 'peserta_didik') {
                $filters['user_id'] = $user['id'];
            }

            $attendances = $attendanceModel->getFilteredReport($filters);

            $data = [
                'title' => 'Cetak Laporan Absensi - ' . APP_NAME,
                'type' => 'daily',
                'attendances' => $attendances,
                'filters' => $filters,
                'user' => $user
            ];
        }

        $this->view('attendance/print', $data);
    }
}
