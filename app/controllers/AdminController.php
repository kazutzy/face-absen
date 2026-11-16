<?php
/**
 * AdminController - Area Khusus Super User (Master Data Management)
 */
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../models/ApiKey.php';
require_once __DIR__ . '/../models/Setting.php';
require_once __DIR__ . '/../models/BroadcastMessage.php';
require_once __DIR__ . '/../services/FonnteService.php';

class AdminController extends Controller {

    public function __construct() {
        // Guard: Hanya Super User yang diizinkan mengakses
        Auth::requireRole('super_user');
    }

    public function index() {
        $this->dashboard();
    }

    public function dashboard() {
        $userModel = $this->model('User');
        $classesModel = $this->model('Classes');
        $studentModel = $this->model('Student');
        $teacherModel = $this->model('Teacher');
        $attendanceModel = $this->model('Attendance');

        $today = date('Y-m-d');
        $todaySummary = $attendanceModel->getDailySummary($today);
        $totalStudents = $studentModel->count();
        $totalTeachers = $teacherModel->count();
        $todayPresent = (int)($todaySummary['hadir'] ?? 0);
        $todayLate = (int)($todaySummary['terlambat'] ?? 0);
        $todayAttended = (int)($todaySummary['attended'] ?? 0);

        $stats = [
            'total_users' => $userModel->count(),
            'total_teachers' => $totalTeachers,
            'total_students' => $totalStudents,
            'total_classes' => $classesModel->count(),
            'today_hadir' => $todayPresent,
            'today_terlambat' => $todayLate,
            'today_absen' => max(0, $totalStudents - $todayAttended),
            'today_belum_absen' => max(0, $totalStudents - $todayAttended),
            'today_attendances' => $todayAttended,
            'today_total_records' => (int)($todaySummary['total_records'] ?? 0)
        ];

        $data = [
            'title' => 'Dashboard Super User - ' . APP_NAME,
            'user' => Auth::user(),
            'stats' => $stats,
            'today_summary' => $todaySummary,
            'latest_attendances' => $attendanceModel->getLatestAttendances(10),
            'attendance_date' => $today
        ];

        $this->view('admin/dashboard', $data);
    }

    // =========================================================================
    // MODUL REKAP ABSENSI (DELEGATED TO ATTENDANCE CONTROLLER)
    // =========================================================================

    public function attendance() {
        require_once __DIR__ . '/AttendanceController.php';
        $controller = new AttendanceController();
        $controller->index();
    }

    public function attendance_monthly() {
        require_once __DIR__ . '/AttendanceController.php';
        $controller = new AttendanceController();
        $controller->monthly();
    }

    public function attendance_export_excel() {
        require_once __DIR__ . '/AttendanceController.php';
        $controller = new AttendanceController();
        $controller->export_excel();
    }

    public function attendance_print() {
        require_once __DIR__ . '/AttendanceController.php';
        $controller = new AttendanceController();
        $controller->print();
    }

    // =========================================================================
    // 1. MANAJEMEN DATA PESERTA DIDIK (STUDENTS)
    // =========================================================================

    public function students() {
        $search = trim($_GET['search'] ?? '');
        $classId = !empty($_GET['class_id']) ? (int)$_GET['class_id'] : null;

        $studentModel = $this->model('Student');
        $classesModel = $this->model('Classes');

        $students = $studentModel->getFilteredList($search, $classId);
        $classes = $classesModel->all();

        $data = [
            'title' => 'Data Peserta Didik - ' . APP_NAME,
            'students' => $students,
            'classes' => $classes,
            'search' => $search,
            'selected_class' => $classId
        ];

        $this->view('admin/students/index', $data);
    }

    public function student_create() {
        $classesModel = $this->model('Classes');
        $classes = $classesModel->all();

        $data = [
            'title' => 'Tambah Peserta Didik - ' . APP_NAME,
            'classes' => $classes,
            'error' => null,
            'old' => []
        ];

        $this->view('admin/students/create', $data);
    }

    public function student_store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/students');
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            Flash::set('danger', 'Sesi form tidak valid. Silakan coba lagi.');
            $this->redirect('admin/students');
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $nis = trim($_POST['nis'] ?? '');
        $classId = (int)($_POST['class_id'] ?? 0);
        $phone = trim($_POST['phone'] ?? '');
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

        $userModel = $this->model('User');
        $studentModel = $this->model('Student');
        $classesModel = $this->model('Classes');

        // Validasi input
        $error = null;
        if (empty($name) || empty($username) || empty($password) || empty($nis) || empty($classId) || empty($email)) {
            $error = 'Semua kolom bertanda bintang (*) wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format alamat email "' . htmlspecialchars($email) . '" tidak valid.';
        } elseif (!$userModel->isEmailUnique($email)) {
            $error = 'Alamat email "' . htmlspecialchars($email) . '" sudah digunakan oleh akun lain.';
        } elseif (!$userModel->isUsernameUnique($username)) {
            $error = 'Username "' . htmlspecialchars($username) . '" sudah digunakan oleh akun lain.';
        } elseif (!$studentModel->isNisUnique($nis)) {
            $error = 'NIS "' . htmlspecialchars($nis) . '" sudah terdaftar untuk siswa lain.';
        }

        if ($error) {
            $data = [
                'title' => 'Tambah Peserta Didik - ' . APP_NAME,
                'classes' => $classesModel->all(),
                'error' => $error,
                'old' => $_POST
            ];
            $this->view('admin/students/create', $data);
            return;
        }

        $faceIdentifier = trim($_POST['face_model_identifier'] ?? '');
        if (empty($faceIdentifier)) {
            $faceIdentifier = $nis; // Default otomatis gunakan NIS sebagai kelas label model AI
        }

        try {
            $studentModel->createStudent(
                ['name' => $name, 'email' => $email, 'username' => $username, 'password' => $password, 'status' => $status],
                ['nis' => $nis, 'class_id' => $classId, 'face_model_identifier' => $faceIdentifier, 'phone' => $phone]
            );

            Flash::set('success', 'Data peserta didik "' . htmlspecialchars($name) . '" berhasil ditambahkan.');
            $this->redirect('admin/students');
        } catch (Throwable $e) {
            Flash::set('danger', 'Gagal menambahkan peserta didik: ' . $e->getMessage());
            $this->redirect('admin/students');
        }
    }

    public function student_detail($id = null) {
        if (!$id) {
            $this->redirect('admin/students');
        }

        $studentModel = $this->model('Student');
        $student = $studentModel->findStudentDetailById((int)$id);

        if (!$student) {
            Flash::set('danger', 'Data peserta didik tidak ditemukan.');
            $this->redirect('admin/students');
        }

        $data = [
            'title' => 'Detail Peserta Didik - ' . APP_NAME,
            'student' => $student
        ];

        $this->view('admin/students/detail', $data);
    }

    public function student_edit($id = null) {
        if (!$id) {
            $this->redirect('admin/students');
        }

        $studentModel = $this->model('Student');
        $classesModel = $this->model('Classes');

        $student = $studentModel->findStudentDetailById((int)$id);
        if (!$student) {
            Flash::set('danger', 'Data peserta didik tidak ditemukan.');
            $this->redirect('admin/students');
        }

        $data = [
            'title' => 'Edit Peserta Didik - ' . APP_NAME,
            'student' => $student,
            'classes' => $classesModel->all(),
            'error' => null
        ];

        $this->view('admin/students/edit', $data);
    }

    public function student_update($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$id) {
            $this->redirect('admin/students');
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            Flash::set('danger', 'Sesi form tidak valid. Silakan coba lagi.');
            $this->redirect('admin/students');
        }

        $studentModel = $this->model('Student');
        $userModel = $this->model('User');
        $classesModel = $this->model('Classes');

        $student = $studentModel->findStudentDetailById((int)$id);
        if (!$student) {
            Flash::set('danger', 'Data peserta didik tidak ditemukan.');
            $this->redirect('admin/students');
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $nis = trim($_POST['nis'] ?? '');
        $classId = (int)($_POST['class_id'] ?? 0);
        $phone = trim($_POST['phone'] ?? '');
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

        // Validasi
        $error = null;
        if (empty($name) || empty($username) || empty($nis) || empty($classId) || empty($email)) {
            $error = 'Nama, Email, Username, NIS, dan Kelas wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format alamat email "' . htmlspecialchars($email) . '" tidak valid.';
        } elseif (!$userModel->isEmailUnique($email, (int)$student['user_id'])) {
            $error = 'Alamat email "' . htmlspecialchars($email) . '" sudah digunakan oleh akun lain.';
        } elseif (!$userModel->isUsernameUnique($username, (int)$student['user_id'])) {
            $error = 'Username "' . htmlspecialchars($username) . '" sudah digunakan.';
        } elseif (!$studentModel->isNisUnique($nis, (int)$id)) {
            $error = 'NIS "' . htmlspecialchars($nis) . '" sudah terdaftar.';
        }

        if ($error) {
            $data = [
                'title' => 'Edit Peserta Didik - ' . APP_NAME,
                'student' => array_merge($student, $_POST),
                'classes' => $classesModel->all(),
                'error' => $error
            ];
            $this->view('admin/students/edit', $data);
            return;
        }

        $faceIdentifier = trim($_POST['face_model_identifier'] ?? '');
        if (empty($faceIdentifier)) {
            $faceIdentifier = $nis; // Default otomatis gunakan NIS sebagai kelas label model AI
        }

        try {
            $studentModel->updateStudent(
                (int)$id,
                ['name' => $name, 'email' => $email, 'username' => $username, 'password' => $password, 'status' => $status],
                ['nis' => $nis, 'class_id' => $classId, 'face_model_identifier' => $faceIdentifier, 'phone' => $phone]
            );

            Flash::set('success', 'Data peserta didik "' . htmlspecialchars($name) . '" berhasil diperbarui.');
            $this->redirect('admin/students');
        } catch (Throwable $e) {
            Flash::set('danger', 'Gagal memperbarui data: ' . $e->getMessage());
            $this->redirect('admin/students');
        }
    }

    public function student_delete($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$id) {
            $this->redirect('admin/students');
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            Flash::set('danger', 'Sesi form tidak valid.');
            $this->redirect('admin/students');
        }

        $studentModel = $this->model('Student');
        try {
            $student = $studentModel->findStudentDetailById((int)$id);
            if ($student) {
                $studentModel->deleteStudent((int)$id);
                Flash::set('success', 'Data peserta didik "' . htmlspecialchars($student['name']) . '" berhasil dihapus.');
            } else {
                Flash::set('warning', 'Data tidak ditemukan.');
            }
        } catch (Throwable $e) {
            Flash::set('danger', 'Gagal menghapus siswa: ' . $e->getMessage());
        }

        $this->redirect('admin/students');
    }

    // =========================================================================
    // 2. MANAJEMEN DATA GURU (TEACHERS)
    // =========================================================================

    public function teachers() {
        $search = trim($_GET['search'] ?? '');
        $teacherModel = $this->model('Teacher');
        $teachers = $teacherModel->getFilteredList($search);

        $data = [
            'title' => 'Data Guru - ' . APP_NAME,
            'teachers' => $teachers,
            'search' => $search
        ];

        $this->view('admin/teachers/index', $data);
    }

    public function teacher_create() {
        $data = [
            'title' => 'Tambah Guru - ' . APP_NAME,
            'error' => null,
            'old' => []
        ];

        $this->view('admin/teachers/create', $data);
    }

    public function teacher_store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/teachers');
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            Flash::set('danger', 'Sesi form tidak valid.');
            $this->redirect('admin/teachers');
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $nip = trim($_POST['nip'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

        $userModel = $this->model('User');
        $teacherModel = $this->model('Teacher');

        // Validasi
        $error = null;
        if (empty($name) || empty($username) || empty($password) || empty($nip) || empty($email)) {
            $error = 'Semua kolom bertanda bintang (*) wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format alamat email "' . htmlspecialchars($email) . '" tidak valid.';
        } elseif (!$userModel->isEmailUnique($email)) {
            $error = 'Alamat email "' . htmlspecialchars($email) . '" sudah digunakan oleh akun lain.';
        } elseif (!$userModel->isUsernameUnique($username)) {
            $error = 'Username "' . htmlspecialchars($username) . '" sudah digunakan.';
        } elseif (!$teacherModel->isNipUnique($nip)) {
            $error = 'NIP "' . htmlspecialchars($nip) . '" sudah terdaftar untuk guru lain.';
        }

        if ($error) {
            $data = [
                'title' => 'Tambah Guru - ' . APP_NAME,
                'error' => $error,
                'old' => $_POST
            ];
            $this->view('admin/teachers/create', $data);
            return;
        }

        try {
            $teacherModel->createTeacher(
                ['name' => $name, 'email' => $email, 'username' => $username, 'password' => $password, 'status' => $status],
                ['nip' => $nip, 'phone' => $phone]
            );

            Flash::set('success', 'Data guru "' . htmlspecialchars($name) . '" berhasil ditambahkan.');
            $this->redirect('admin/teachers');
        } catch (Throwable $e) {
            Flash::set('danger', 'Gagal menambahkan data guru: ' . $e->getMessage());
            $this->redirect('admin/teachers');
        }
    }

    public function teacher_detail($id = null) {
        if (!$id) {
            $this->redirect('admin/teachers');
        }

        $teacherModel = $this->model('Teacher');
        $teacher = $teacherModel->findTeacherDetailById((int)$id);

        if (!$teacher) {
            Flash::set('danger', 'Data guru tidak ditemukan.');
            $this->redirect('admin/teachers');
        }

        $data = [
            'title' => 'Detail Guru - ' . APP_NAME,
            'teacher' => $teacher
        ];

        $this->view('admin/teachers/detail', $data);
    }

    public function teacher_edit($id = null) {
        if (!$id) {
            $this->redirect('admin/teachers');
        }

        $teacherModel = $this->model('Teacher');
        $teacher = $teacherModel->findTeacherDetailById((int)$id);

        if (!$teacher) {
            Flash::set('danger', 'Data guru tidak ditemukan.');
            $this->redirect('admin/teachers');
        }

        $data = [
            'title' => 'Edit Guru - ' . APP_NAME,
            'teacher' => $teacher,
            'error' => null
        ];

        $this->view('admin/teachers/edit', $data);
    }

    public function teacher_update($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$id) {
            $this->redirect('admin/teachers');
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            Flash::set('danger', 'Sesi form tidak valid.');
            $this->redirect('admin/teachers');
        }

        $teacherModel = $this->model('Teacher');
        $userModel = $this->model('User');

        $teacher = $teacherModel->findTeacherDetailById((int)$id);
        if (!$teacher) {
            Flash::set('danger', 'Data guru tidak ditemukan.');
            $this->redirect('admin/teachers');
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $nip = trim($_POST['nip'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';

        // Validasi
        $error = null;
        if (empty($name) || empty($username) || empty($nip) || empty($email)) {
            $error = 'Nama, Email, Username, dan NIP wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format alamat email "' . htmlspecialchars($email) . '" tidak valid.';
        } elseif (!$userModel->isEmailUnique($email, (int)$teacher['user_id'])) {
            $error = 'Alamat email "' . htmlspecialchars($email) . '" sudah digunakan oleh akun lain.';
        } elseif (!$userModel->isUsernameUnique($username, (int)$teacher['user_id'])) {
            $error = 'Username "' . htmlspecialchars($username) . '" sudah digunakan.';
        } elseif (!$teacherModel->isNipUnique($nip, (int)$id)) {
            $error = 'NIP "' . htmlspecialchars($nip) . '" sudah terdaftar.';
        }

        if ($error) {
            $data = [
                'title' => 'Edit Guru - ' . APP_NAME,
                'teacher' => array_merge($teacher, $_POST),
                'error' => $error
            ];
            $this->view('admin/teachers/edit', $data);
            return;
        }

        try {
            $teacherModel->updateTeacher(
                (int)$id,
                ['name' => $name, 'email' => $email, 'username' => $username, 'password' => $password, 'status' => $status],
                ['nip' => $nip, 'phone' => $phone]
            );

            Flash::set('success', 'Data guru "' . htmlspecialchars($name) . '" berhasil diperbarui.');
            $this->redirect('admin/teachers');
        } catch (Throwable $e) {
            Flash::set('danger', 'Gagal memperbarui data: ' . $e->getMessage());
            $this->redirect('admin/teachers');
        }
    }

    public function teacher_delete($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$id) {
            $this->redirect('admin/teachers');
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            Flash::set('danger', 'Sesi form tidak valid.');
            $this->redirect('admin/teachers');
        }

        $teacherModel = $this->model('Teacher');
        try {
            $teacher = $teacherModel->findTeacherDetailById((int)$id);
            if ($teacher) {
                $teacherModel->deleteTeacher((int)$id);
                Flash::set('success', 'Data guru "' . htmlspecialchars($teacher['name']) . '" berhasil dihapus.');
            } else {
                Flash::set('warning', 'Data tidak ditemukan.');
            }
        } catch (Throwable $e) {
            Flash::set('danger', 'Gagal menghapus data guru: ' . $e->getMessage());
        }

        $this->redirect('admin/teachers');
    }

    // =========================================================================
    // 3. MANAJEMEN DATA KELAS (CLASSES)
    // =========================================================================

    public function classes() {
        $search = trim($_GET['search'] ?? '');
        $classesModel = $this->model('Classes');
        $classes = $classesModel->getWithStudentCount($search);

        $data = [
            'title' => 'Manajemen Kelas - ' . APP_NAME,
            'classes' => $classes,
            'search' => $search
        ];

        $this->view('admin/classes/index', $data);
    }

    public function class_store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/classes');
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            Flash::set('danger', 'Sesi form tidak valid.');
            $this->redirect('admin/classes');
        }

        $name = trim($_POST['name'] ?? '');
        $level = trim($_POST['level'] ?? '');

        if (empty($name) || empty($level)) {
            Flash::set('danger', 'Nama Kelas dan Tingkat wajib diisi.');
            $this->redirect('admin/classes');
        }

        $classesModel = $this->model('Classes');
        if ($classesModel->createClass($name, $level)) {
            Flash::set('success', 'Kelas "' . htmlspecialchars($name) . '" berhasil ditambahkan.');
        } else {
            Flash::set('danger', 'Gagal menambahkan kelas.');
        }

        $this->redirect('admin/classes');
    }

    public function class_update($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$id) {
            $this->redirect('admin/classes');
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            Flash::set('danger', 'Sesi form tidak valid.');
            $this->redirect('admin/classes');
        }

        $name = trim($_POST['name'] ?? '');
        $level = trim($_POST['level'] ?? '');

        if (empty($name) || empty($level)) {
            Flash::set('danger', 'Nama Kelas dan Tingkat wajib diisi.');
            $this->redirect('admin/classes');
        }

        $classesModel = $this->model('Classes');
        if ($classesModel->updateClass((int)$id, $name, $level)) {
            Flash::set('success', 'Data kelas berhasil diperbarui.');
        } else {
            Flash::set('danger', 'Gagal memperbarui kelas.');
        }

        $this->redirect('admin/classes');
    }

    public function class_delete($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$id) {
            $this->redirect('admin/classes');
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            Flash::set('danger', 'Sesi form tidak valid.');
            $this->redirect('admin/classes');
        }

        $classesModel = $this->model('Classes');

        if ($classesModel->hasStudents((int)$id)) {
            Flash::set('warning', 'Kelas tidak dapat dihapus karena masih memiliki data siswa yang terdaftar.');
            $this->redirect('admin/classes');
        }

        if ($classesModel->deleteClass((int)$id)) {
            Flash::set('success', 'Kelas berhasil dihapus.');
        } else {
            Flash::set('danger', 'Gagal menghapus kelas.');
        }

        $this->redirect('admin/classes');
    }

    // =========================================================================
    // API KEY CONFIGURATION MANAGEMENT (SUPER USER)
    // =========================================================================

    public function api_keys() {
        try {
            $apiKeyModel = $this->model('ApiKey');
            $apiKeys = $apiKeyModel->getAll();
            $activeKey = $apiKeyModel->getActiveKey();
        } catch (Throwable $e) {
            Flash::set('danger', 'Terjadi kendala saat memuat konfigurasi API Key: ' . $e->getMessage());
            $apiKeys = [];
            $activeKey = null;
        }

        $data = [
            'title' => 'Konfigurasi API Key - ' . APP_NAME,
            'api_keys' => $apiKeys,
            'active_key' => $activeKey
        ];

        $this->view('admin/api_keys/index', $data);
    }

    // Alias URL agar fleksibel (/admin/api_key, /admin/api-key, /admin/apikey, /admin/apikeys)
    public function api_key() {
        $this->api_keys();
    }

    public function apikey() {
        $this->api_keys();
    }

    public function apikeys() {
        $this->api_keys();
    }

    public function api_key_store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/api_keys');
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            Flash::set('danger', 'Sesi form tidak valid. Silakan coba lagi.');
            $this->redirect('admin/api_keys');
        }

        $name = trim($_POST['name'] ?? '');
        $provider = trim($_POST['provider'] ?? 'NVIDIA NIM');
        $apiKey = trim($_POST['api_key'] ?? '');
        $isActive = !empty($_POST['is_active']) ? 1 : 0;

        if (empty($name) || empty($apiKey)) {
            Flash::set('danger', 'Nama label dan nilai API Key wajib diisi.');
            $this->redirect('admin/api_keys');
        }

        $apiKeyModel = $this->model('ApiKey');
        try {
            $apiKeyModel->createKey([
                'name' => $name,
                'provider' => $provider,
                'api_key' => $apiKey,
                'is_active' => $isActive
            ]);

            try {
                AuditLog::record('CREATE_API_KEY', 'api_keys', null, null, ['name' => $name, 'provider' => $provider, 'is_active' => $isActive]);
            } catch (Throwable $eLog) {
                error_log("AuditLog record notice: " . $eLog->getMessage());
            }

            Flash::set('success', 'API Key "' . htmlspecialchars($name) . '" berhasil ditambahkan.');
        } catch (Throwable $e) {
            Flash::set('danger', 'Gagal menambahkan API Key: ' . $e->getMessage());
        }

        $this->redirect('admin/api_keys');
    }

    public function api_key_activate($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$id) {
            $this->redirect('admin/api_keys');
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            Flash::set('danger', 'Sesi form tidak valid.');
            $this->redirect('admin/api_keys');
        }

        $apiKeyModel = $this->model('ApiKey');
        $target = $apiKeyModel->findById((int)$id);

        if (!$target) {
            Flash::set('danger', 'API Key tidak ditemukan.');
            $this->redirect('admin/api_keys');
        }

        if ($apiKeyModel->setActiveKey((int)$id)) {
            try {
                AuditLog::record('ACTIVATE_API_KEY', 'api_keys', (int)$id, null, ['activated_name' => $target['name'], 'provider' => $target['provider']]);
            } catch (Throwable $eLog) {
                error_log("AuditLog record notice: " . $eLog->getMessage());
            }
            Flash::set('success', 'API Key aktif berhasil diubah ke: "' . htmlspecialchars($target['name']) . '". Sistem dan asisten AI sekarang menggunakan kunci ini.');
        } else {
            Flash::set('danger', 'Gagal mengubah API Key aktif.');
        }

        $this->redirect('admin/api_keys');
    }

    public function api_key_update($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$id) {
            $this->redirect('admin/api_keys');
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            Flash::set('danger', 'Sesi form tidak valid.');
            $this->redirect('admin/api_keys');
        }

        $name = trim($_POST['name'] ?? '');
        $provider = trim($_POST['provider'] ?? 'NVIDIA NIM');
        $apiKey = trim($_POST['api_key'] ?? '');

        if (empty($name)) {
            Flash::set('danger', 'Nama label API Key tidak boleh kosong.');
            $this->redirect('admin/api_keys');
        }

        $apiKeyModel = $this->model('ApiKey');
        $old = $apiKeyModel->findById((int)$id);

        if (!$old) {
            Flash::set('danger', 'API Key tidak ditemukan.');
            $this->redirect('admin/api_keys');
        }

        $updateData = [
            'name' => $name,
            'provider' => $provider
        ];
        if (!empty($apiKey)) {
            $updateData['api_key'] = $apiKey;
        }

        if ($apiKeyModel->updateKey((int)$id, $updateData)) {
            try {
                AuditLog::record('UPDATE_API_KEY', 'api_keys', (int)$id, ['name' => $old['name']], ['name' => $name, 'provider' => $provider]);
            } catch (Throwable $eLog) {
                error_log("AuditLog record notice: " . $eLog->getMessage());
            }
            Flash::set('success', 'Data API Key "' . htmlspecialchars($name) . '" berhasil diperbarui.');
        } else {
            Flash::set('danger', 'Gagal memperbarui API Key.');
        }

        $this->redirect('admin/api_keys');
    }

    public function api_key_delete($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$id) {
            $this->redirect('admin/api_keys');
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            Flash::set('danger', 'Sesi form tidak valid.');
            $this->redirect('admin/api_keys');
        }

        $apiKeyModel = $this->model('ApiKey');
        $old = $apiKeyModel->findById((int)$id);

        if (!$old) {
            Flash::set('danger', 'API Key tidak ditemukan.');
            $this->redirect('admin/api_keys');
        }

        $result = $apiKeyModel->deleteKey((int)$id);
        if ($result['success']) {
            try {
                AuditLog::record('DELETE_API_KEY', 'api_keys', (int)$id, ['name' => $old['name']], null);
            } catch (Throwable $eLog) {
                error_log("AuditLog record notice: " . $eLog->getMessage());
            }
            Flash::set('success', $result['message']);
        } else {
            Flash::set('warning', $result['message']);
        }

        $this->redirect('admin/api_keys');
    }

    /**
     * Endpoint untuk Menguji Koneksi dan Validitas API Key ke NVIDIA NIM
     * GET/POST /admin/api_key_test/{id}
     */
    public function api_key_test($id = null) {
        $keyToTest = null;
        $keyName = '';
        $provider = 'NVIDIA NIM';

        // 1. Ambil key dari POST jika dikirim langsung (misal uji input modal)
        $rawInput = file_get_contents('php://input');
        $jsonBody = json_decode($rawInput, true);
        if (!empty($jsonBody['api_key'])) {
            $keyToTest = trim($jsonBody['api_key']);
            $keyName = trim($jsonBody['name'] ?? 'Kunci Uji');
            $provider = trim($jsonBody['provider'] ?? 'NVIDIA NIM');
        } elseif (!empty($_POST['api_key'])) {
            $keyToTest = trim($_POST['api_key']);
            $keyName = trim($_POST['name'] ?? 'Kunci Uji');
            $provider = trim($_POST['provider'] ?? 'NVIDIA NIM');
        }

        $apiKeyModel = $this->model('ApiKey');

        // 2. Ambil dari ID jika ada
        if (!$keyToTest && $id) {
            $target = $apiKeyModel->findById((int)$id);
            if ($target) {
                $keyToTest = $target['api_key'];
                $keyName = $target['name'];
                $provider = $target['provider'];
            }
        }

        // 3. Fallback ke kunci aktif saat ini jika ID tidak diberikan
        if (!$keyToTest) {
            $active = $apiKeyModel->getActiveKey();
            if ($active) {
                $keyToTest = $active['api_key'];
                $keyName = $active['name'];
                $provider = $active['provider'];
            }
        }

        if (empty($keyToTest)) {
            return $this->json([
                'success' => false,
                'message' => 'Nilai API Key tidak ditemukan untuk diuji.'
            ], 400);
        }

        // 4. Deteksi Provider (Google Gemini vs NVIDIA NIM)
        $isGemini = (stripos($provider, 'gemini') !== false || stripos($provider, 'google') !== false || strpos($keyToTest, 'AQ.') === 0 || strpos($keyToTest, 'AIza') === 0);

        $startTime = microtime(true);

        if ($isGemini) {
            // Pengujian Google Gemini API
            $testUrl = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . urlencode($keyToTest);
            $ch = curl_init($testUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 12);
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            $latencyMs = round((microtime(true) - $startTime) * 1000);
            curl_close($ch);

            if ($curlErr) {
                return $this->json([
                    'success' => false,
                    'message' => 'Gagal menghubungi server Google Gemini: ' . $curlErr,
                    'latency_ms' => $latencyMs,
                    'http_code' => $httpCode,
                    'key_name' => $keyName,
                    'provider' => 'Google Gemini'
                ]);
            }

            $data = json_decode($response, true);

            if ($httpCode === 200 && isset($data['models'])) {
                $count = count($data['models']);
                return $this->json([
                    'success' => true,
                    'message' => "Terhubung dengan sukses! Kunci Google Gemini valid dan aktif ({$count} model AI Gemini siap digunakan).",
                    'latency_ms' => $latencyMs,
                    'http_code' => $httpCode,
                    'key_name' => $keyName,
                    'provider' => 'Google Gemini',
                    'models_count' => $count
                ]);
            }

            $errMsg = $data['error']['message'] ?? "Respon dari Google (HTTP {$httpCode})";
            return $this->json([
                'success' => false,
                'message' => 'Autentikasi Gemini Gagal: ' . $errMsg,
                'latency_ms' => $latencyMs,
                'http_code' => $httpCode,
                'key_name' => $keyName,
                'provider' => 'Google Gemini'
            ]);
        }

        // Pengujian NVIDIA NIM API
        $ch = curl_init('https://integrate.api.nvidia.com/v1/models');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $keyToTest,
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        $latencyMs = round((microtime(true) - $startTime) * 1000);
        curl_close($ch);

        if ($curlErr) {
            return $this->json([
                'success' => false,
                'message' => 'Gagal menghubungi server NVIDIA: ' . $curlErr,
                'latency_ms' => $latencyMs,
                'http_code' => $httpCode,
                'key_name' => $keyName,
                'provider' => 'NVIDIA NIM'
            ]);
        }

        if ($httpCode === 200 && !empty($response)) {
            $data = json_decode($response, true);
            $count = isset($data['data']) && is_array($data['data']) ? count($data['data']) : 0;
            return $this->json([
                'success' => true,
                'message' => "Terhubung dengan sukses! Kunci API valid dan aktif ({$count} model AI NVIDIA NIM siap digunakan).",
                'latency_ms' => $latencyMs,
                'http_code' => $httpCode,
                'key_name' => $keyName,
                'provider' => 'NVIDIA NIM',
                'models_count' => $count
            ]);
        }

        if ($httpCode === 401) {
            return $this->json([
                'success' => false,
                'message' => 'Autentikasi Ditolak (HTTP 401 Unauthorized): API Key tidak valid atau telah kedaluwarsa.',
                'latency_ms' => $latencyMs,
                'http_code' => $httpCode,
                'key_name' => $keyName,
                'provider' => 'NVIDIA NIM'
            ]);
        }

        if ($httpCode === 403) {
            return $this->json([
                'success' => false,
                'message' => 'Akses Dilarang (HTTP 403 Forbidden): Kuota habis atau akun belum memiliki izin akses.',
                'latency_ms' => $latencyMs,
                'http_code' => $httpCode,
                'key_name' => $keyName,
                'provider' => 'NVIDIA NIM'
            ]);
        }

        return $this->json([
            'success' => false,
            'message' => "Respon dari NVIDIA (HTTP {$httpCode}): " . substr(strip_tags((string)$response), 0, 150),
            'latency_ms' => $latencyMs,
            'http_code' => $httpCode,
            'key_name' => $keyName,
            'provider' => 'NVIDIA NIM'
        ]);
    }

    // =========================================================================
    // FITUR PENGIRIMAN PESAN WHATSAPP (FONNTE GATEWAY)
    // =========================================================================

    /**
     * Halaman Utama Pengiriman Pesan WhatsApp
     */
    public function messages() {
        $db = new Database();

        // 1. Ambil data guru yang memiliki nomor telepon
        $db->query("
            SELECT t.id, t.nip, t.phone, u.name
            FROM teachers t
            JOIN users u ON t.user_id = u.id
            WHERE u.status = 'active' AND t.phone IS NOT NULL AND t.phone != ''
            ORDER BY u.name ASC
        ");
        $teachersWithPhone = $db->resultSet();

        // 2. Ambil data siswa yang memiliki nomor telepon
        $db->query("
            SELECT s.id, s.nis, s.phone, u.name, c.name as class_name, c.level
            FROM students s
            JOIN users u ON s.user_id = u.id
            JOIN classes c ON s.class_id = c.id
            WHERE u.status = 'active' AND s.phone IS NOT NULL AND s.phone != ''
            ORDER BY c.level ASC, c.name ASC, u.name ASC
        ");
        $studentsWithPhone = $db->resultSet();

        // 3. Ambil daftar kelas beserta jumlah siswa bertelpon
        $db->query("
            SELECT c.id, c.name as class_name, c.level,
                   COUNT(s.id) as total_students,
                   SUM(CASE WHEN s.phone IS NOT NULL AND s.phone != '' THEN 1 ELSE 0 END) as total_with_phone
            FROM classes c
            LEFT JOIN students s ON s.class_id = c.id
            GROUP BY c.id, c.name, c.level
            ORDER BY c.level ASC, c.name ASC
        ");
        $classes = $db->resultSet();

        // 4. Riwayat pesan yang pernah dikirim
        $broadcastModel = new BroadcastMessage();
        $messages = $broadcastModel->getAllWithDetails(50);
        $summaryStats = $broadcastModel->getSummaryStats();

        // 5. Cek status perangkat Fonnte secara live
        $deviceStatus = FonnteService::getDeviceStatus();
        $currentApiKey = FonnteService::getApiKey();

        // Tangkap prefill jika dikirim dari link shortcut halaman detail
        $prefillTarget = $_GET['to'] ?? '';
        $prefillPhone = $_GET['phone'] ?? '';
        $prefillName = $_GET['name'] ?? '';

        $data = [
            'title' => 'Pengiriman Pesan WhatsApp - ' . APP_NAME,
            'user' => Auth::user(),
            'teachers' => $teachersWithPhone,
            'students' => $studentsWithPhone,
            'classes' => $classes,
            'messages' => $messages,
            'stats' => $summaryStats,
            'device_status' => $deviceStatus,
            'current_api_key' => $currentApiKey,
            'prefill' => [
                'target' => $prefillTarget,
                'phone' => $prefillPhone,
                'name' => $prefillName
            ]
        ];

        $this->view('admin/messages/index', $data);
    }

    /**
     * Alias URL untuk kemudahan akses: /admin/broadcast
     */
    public function broadcast() {
        $this->messages();
    }

    /**
     * Action POST: Kirim Pesan WhatsApp ke Guru / Siswa / Kelas / Nomor Tertentu
     */
    public function message_send() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/messages');
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            Flash::set('danger', 'Sesi formulir kedaluwarsa. Silakan ulangi pengiriman pesan.');
            $this->redirect('admin/messages');
        }

        $recipientType = trim($_POST['recipient_type'] ?? '');
        $classId = !empty($_POST['class_id']) ? (int)$_POST['class_id'] : null;
        $specificTarget = trim($_POST['specific_target'] ?? '');
        $customPhone = trim($_POST['custom_phone'] ?? '');
        $messageTemplate = trim($_POST['message'] ?? '');
        $delay = isset($_POST['delay']) ? max(1, min(10, (int)$_POST['delay'])) : 2;

        if (empty($messageTemplate)) {
            Flash::set('danger', 'Isi pesan WhatsApp tidak boleh kosong.');
            $this->redirect('admin/messages');
        }

        $db = new Database();
        $targetPhones = [];
        $targetNames = [];

        // 1. Resolusi Target Penerima
        if ($recipientType === 'all_teachers') {
            $db->query("
                SELECT t.phone, u.name
                FROM teachers t
                JOIN users u ON t.user_id = u.id
                WHERE u.status = 'active' AND t.phone IS NOT NULL AND t.phone != ''
            ");
            $rows = $db->resultSet();
            foreach ($rows as $r) {
                $targetPhones[] = $r['phone'];
                $targetNames[] = $r['name'];
            }
        } elseif ($recipientType === 'all_students') {
            $db->query("
                SELECT s.phone, u.name
                FROM students s
                JOIN users u ON s.user_id = u.id
                WHERE u.status = 'active' AND s.phone IS NOT NULL AND s.phone != ''
            ");
            $rows = $db->resultSet();
            foreach ($rows as $r) {
                $targetPhones[] = $r['phone'];
                $targetNames[] = $r['name'];
            }
        } elseif ($recipientType === 'class') {
            if (!$classId) {
                Flash::set('danger', 'Silakan pilih rombel / kelas tujuan.');
                $this->redirect('admin/messages');
            }
            $db->query("
                SELECT s.phone, u.name
                FROM students s
                JOIN users u ON s.user_id = u.id
                WHERE s.class_id = :cid AND u.status = 'active' AND s.phone IS NOT NULL AND s.phone != ''
            ");
            $db->bind(':cid', $classId);
            $rows = $db->resultSet();
            foreach ($rows as $r) {
                $targetPhones[] = $r['phone'];
                $targetNames[] = $r['name'];
            }
        } elseif ($recipientType === 'specific') {
            if (!empty($customPhone)) {
                $targetPhones[] = $customPhone;
                $targetNames[] = !empty($_POST['custom_name']) ? trim($_POST['custom_name']) : 'Nomor Kustom';
            } elseif (!empty($specificTarget)) {
                // Format: role|id|phone|name
                $parts = explode('|', $specificTarget);
                if (count($parts) >= 4) {
                    $targetPhones[] = $parts[2];
                    $targetNames[] = $parts[3];
                }
            }
        } else {
            Flash::set('danger', 'Kategori penerima pesan tidak valid.');
            $this->redirect('admin/messages');
        }

        if (empty($targetPhones)) {
            Flash::set('warning', 'Tidak ditemukan nomor WhatsApp aktif pada kategori penerima yang dipilih.');
            $this->redirect('admin/messages');
        }

        // 2. Ambil data nama sekolah untuk template {sekolah}
        $schoolName = 'SMK BINAWARGA LEMAHABANG';
        try {
            $settingModel = $this->model('Setting');
            $val = $settingModel->get('school_name');
            if (!empty($val)) $schoolName = $val;
        } catch (Throwable $e) {}

        // Ganti variabel umum {sekolah} dan {tanggal}
        $finalMessage = str_replace(
            ['{sekolah}', '{tanggal}'],
            [$schoolName, date('d/m/Y')],
            $messageTemplate
        );

        // Ganti variabel {nama} secara dinamis
        if (count($targetNames) === 1) {
            $finalMessage = str_replace('{nama}', $targetNames[0], $finalMessage);
        } else {
            // Untuk penerima massal, ganti {nama} dengan sebutan umum sesuai kategori
            if ($recipientType === 'all_teachers') {
                $finalMessage = str_replace('{nama}', 'Bapak/Ibu Guru', $finalMessage);
            } elseif ($recipientType === 'all_students' || $recipientType === 'class') {
                $finalMessage = str_replace('{nama}', 'Siswa/Siswi', $finalMessage);
            } else {
                $finalMessage = str_replace('{nama}', 'Bapak/Ibu/Saudara', $finalMessage);
            }
        }

        // 3. Kirim via Fonnte Service
        $res = FonnteService::sendMessage($targetPhones, $finalMessage, $delay);

        $status = $res['success'] ? 'sent' : 'failed';
        $totalRecipients = count($targetPhones);
        $targetNamesStr = implode(', ', array_slice($targetNames, 0, 10));
        if (count($targetNames) > 10) {
            $targetNamesStr .= ' dan ' . (count($targetNames) - 10) . ' lainnya';
        }

        // 4. Simpan ke database riwayat broadcast_messages
        $broadcastModel = new BroadcastMessage();
        $recordId = $broadcastModel->createMessage([
            'sender_id' => Auth::id(),
            'recipient_type' => $recipientType,
            'target_class_id' => $classId,
            'target_phone' => implode(', ', $targetPhones),
            'target_names' => $targetNamesStr,
            'total_recipients' => $totalRecipients,
            'message' => $finalMessage,
            'status' => $status,
            'fonnte_response' => json_encode($res['data'] ?? ['error' => $res['error'] ?? ''])
        ]);

        // 5. Catat log audit sistem
        AuditLog::record('SEND_WHATSAPP_MESSAGE', 'broadcast_messages', $recordId ?: null, null, [
            'recipient_type' => $recipientType,
            'total_recipients' => $totalRecipients,
            'status' => $status,
            'sample_targets' => $targetNamesStr
        ]);

        if ($res['success']) {
            Flash::set('success', "Pesan WhatsApp berhasil dikirim ke {$totalRecipients} penerima.");
        } else {
            $errMsg = $res['error'] ?? 'Terjadi kendala pada gateway Fonnte.';
            Flash::set('danger', "Gagal mengirim pesan WhatsApp: {$errMsg}");
        }

        $this->redirect('admin/messages');
    }

    /**
     * Action POST: Hapus Catatan Riwayat Pesan
     */
    public function message_delete($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/messages');
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            Flash::set('danger', 'Sesi tidak valid.');
            $this->redirect('admin/messages');
        }

        if (!$id) {
            Flash::set('danger', 'ID pesan tidak valid.');
            $this->redirect('admin/messages');
        }

        $broadcastModel = new BroadcastMessage();
        $msg = $broadcastModel->getById($id);
        if ($msg) {
            $broadcastModel->deleteMessage($id);
            AuditLog::record('DELETE_WHATSAPP_LOG', 'broadcast_messages', (int)$id, ['recipient_type' => $msg['recipient_type']], null);
            Flash::set('success', 'Riwayat pesan berhasil dihapus.');
        } else {
            Flash::set('warning', 'Data pesan tidak ditemukan.');
        }

        $this->redirect('admin/messages');
    }

    /**
     * Action POST: Perbarui API Key Fonnte
     */
    public function fonnte_save_key() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/messages');
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            Flash::set('danger', 'Sesi tidak valid.');
            $this->redirect('admin/messages');
        }

        $newKey = trim($_POST['fonnte_api_key'] ?? '');
        if (empty($newKey)) {
            Flash::set('danger', 'API Key Fonnte tidak boleh kosong.');
            $this->redirect('admin/messages');
        }

        $oldKey = FonnteService::getApiKey();
        FonnteService::saveApiKey($newKey);

        AuditLog::record('UPDATE_FONNTE_KEY', 'settings', null, ['key' => '***'], ['key' => '***']);
        Flash::set('success', 'Konfigurasi API Key Fonnte berhasil diperbarui.');
        $this->redirect('admin/messages');
    }

    /**
     * AJAX Endpoint: Cek Status Perangkat Fonnte Secara Real-time
     */
    public function fonnte_device_status() {
        $status = FonnteService::getDeviceStatus();
        return $this->json($status);
    }
}

