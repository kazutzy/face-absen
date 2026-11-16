<?php
/**
 * SiswaController - Area Khusus Peserta Didik
 */
require_once __DIR__ . '/../core/Controller.php';

class SiswaController extends Controller {

    public function __construct() {
        // Guard Middleware: Hanya role peserta_didik yang diizinkan
        Auth::requireRole('peserta_didik');
    }

    public function index() {
        $this->dashboard();
    }

    public function dashboard() {
        $studentModel = $this->model('Student');
        $attendanceModel = $this->model('Attendance');
        $settingModel = $this->model('Setting');

        $user = Auth::user();
        $studentProfile = null;
        $todayAttendance = null;
        $settings = [];
        $today = date('Y-m-d');

        try {
            $studentProfile = $studentModel->findByUserId($user['id']);
            $todayAttendance = $attendanceModel->getTodayAttendanceByUser($user['id']);
            $settings = $settingModel->getAllAsMap();
        } catch (Throwable $e) {
            // DB fallback
        }

        $personalSummary = $attendanceModel->getUserSummary($user['id']);

        $data = [
            'title' => 'Dashboard Peserta Didik - ' . APP_NAME,
            'user' => $user,
            'student' => $studentProfile,
            'today_attendance' => $todayAttendance,
            'personal_summary' => $personalSummary,
            'settings' => $settings,
            'recent_attendances' => $attendanceModel->getRecentByUser($user['id'], 10),
            'attendance_date' => $today
        ];

        $this->view('siswa/dashboard', $data);
    }
}
