<?php
/**
 * GuruController - Area Khusus Pendidik / Guru
 */
require_once __DIR__ . '/../core/Controller.php';

class GuruController extends Controller {

    public function __construct() {
        // Guard Middleware: Hanya role guru yang diizinkan
        Auth::requireRole('guru');
    }

    public function index() {
        $this->dashboard();
    }

    public function dashboard() {
        $teacherModel = $this->model('Teacher');
        $attendanceModel = $this->model('Attendance');
        $classesModel = $this->model('Classes');

        $user = Auth::user();
        $teacherProfile = null;
        $classList = [];
        $today = date('Y-m-d');
        $todayAttendance = $attendanceModel->getTodayAttendanceByUser($user['id']);
        $personalSummary = $attendanceModel->getUserSummary($user['id']);
        $todaySummary = $attendanceModel->getDailySummary($today);
        $classSummary = $attendanceModel->getClassSummary($today);

        try {
            $teacherProfile = $teacherModel->findByUserId($user['id']);
            $classList = $classesModel->getWithStudentCount();
        } catch (Throwable $e) {
            // DB fallback
        }

        $data = [
            'title' => 'Dashboard Guru - ' . APP_NAME,
            'user' => $user,
            'teacher' => $teacherProfile,
            'classes' => $classList,
            'personal_summary' => $personalSummary,
            'class_summary' => $classSummary,
            'today_attendance' => $todayAttendance,
            'today_summary' => $todaySummary,
            'today_attendances' => $attendanceModel->getDailyAttendanceList($today),
            'attendance_date' => $today
        ];

        $this->view('guru/dashboard', $data);
    }
}
