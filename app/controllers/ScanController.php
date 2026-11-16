<?php
/**
 * ScanController - FaceAbsen Face Recognition Scanner & Configuration API
 */
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/RateLimiter.php';
require_once __DIR__ . '/../models/AuditLog.php';

class ScanController extends Controller {

    public function __construct() {
        // Wajib login untuk mengakses area pemindai
        Auth::requireLogin();
    }

    public function index() {
        $settingModel = $this->model('Setting');
        
        $modelUrl = $settingModel->get('teachable_machine_model_url', 'https://teachablemachine.withgoogle.com/models/sample/');
        $minConfidence = (float)$settingModel->get('min_confidence_score', '80.00');

        $data = [
            'title' => 'FaceAbsen - Pemindai Wajah AI',
            'user' => Auth::user(),
            'model_url' => $modelUrl,
            'min_confidence' => $minConfidence
        ];

        $this->view('scan/index', $data);
    }

    /**
     * API JSON Endpoint: Mengambil konfigurasi model & mapping identifier siswa
     * GET /scan/config
     */
    public function config() {
        $settingModel = $this->model('Setting');
        $studentModel = $this->model('Student');

        $modelUrl = $settingModel->get('teachable_machine_model_url', 'https://teachablemachine.withgoogle.com/models/sample/');
        $minConfidencePercent = (float)$settingModel->get('min_confidence_score', '80.00');
        $minConfidenceRatio = $minConfidencePercent / 100.0;

        // Ambil data siswa dan mapping dengan prioritas utama berdasarkan NIS
        $students = $studentModel->getAllWithDetails();
        $studentMappings = [];

        foreach ($students as $s) {
            $studentData = [
                'id' => (int)$s['id'],
                'user_id' => (int)$s['user_id'],
                'name' => $s['name'],
                'nis' => (string)$s['nis'],
                'class_name' => $s['class_name']
            ];

            // 1. Prioritas Utama: Mapping via NIS (kelas training model Teachable Machine menggunakan NIS)
            if (!empty($s['nis'])) {
                $studentMappings[(string)$s['nis']] = $studentData;
            }

            // 2. Sekunder: Mapping via face_model_identifier jika diset dan berbeda
            if (!empty($s['face_model_identifier'])) {
                $studentMappings[(string)$s['face_model_identifier']] = $studentData;
            }
        }

        $this->json([
            'success' => true,
            'model_name' => 'FaceAbsen',
            'model_url' => $modelUrl,
            'min_confidence_percent' => $minConfidencePercent,
            'min_confidence_ratio' => $minConfidenceRatio,
            'student_mappings' => $studentMappings
        ]);
    }

    /**
     * POST /scan/record
     * Menerima hasil face recognition lalu memvalidasi & menyimpan attendance
     */
    public function result() {
        $user = Auth::user();
        $status = $_GET['status'] ?? 'failed';
        $name = $_GET['name'] ?? ($user['name'] ?? 'Pengguna');
        $date = $_GET['date'] ?? date('d-m-Y');
        $time = $_GET['time'] ?? date('H:i:s');
        $attendanceStatus = $_GET['attendance_status'] ?? 'Hadir';
        $confidence = $_GET['confidence'] ?? '0.00';
        $message = $_GET['message'] ?? 'Identitas wajah tidak dapat diverifikasi.';

        $data = [
            'title' => 'Hasil Absensi - ' . APP_NAME,
            'user' => $user,
            'status' => $status,
            'name' => $name,
            'date' => $date,
            'time' => $time,
            'attendance_status' => $attendanceStatus,
            'confidence' => $confidence,
            'message' => $message
        ];

        $this->view('scan/result', $data);
    }

    public function record() {
        Auth::requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Metode request tidak valid.'], 405);
        }

        $rawBody = file_get_contents('php://input');
        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }

        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($payload['csrf_token'] ?? '');
        if (!Csrf::validate($csrfToken)) {
            $this->json(['success' => false, 'message' => 'Sesi form kedaluwarsa.'], 400);
        }

        $user = Auth::user();
        $sessionUserId = (int)($user['id'] ?? 0);
        $inputUserId = (int)($payload['user_id'] ?? 0);
        $identifier = trim((string)($payload['identifier'] ?? ''));
        $confidenceScore = (float)($payload['confidence_score'] ?? 0);

        // 1. Rate Limiting Protection (Max 15 requests per 60 seconds per user)
        if (!RateLimiter::check('scan_record_' . $sessionUserId . '_' . ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'), 15, 60)) {
            $this->json([
                'success' => false,
                'message' => 'Terlalu banyak permintaan absensi. Silakan tunggu beberapa saat lagi.'
            ], 429);
        }

        // 2. Authoritative Session Validation (Jangan percaya user_id dari payload sebagai satu-satunya sumber)
        if ($sessionUserId <= 0 || ($inputUserId > 0 && $inputUserId !== $sessionUserId)) {
            $this->json(['success' => false, 'message' => 'User tidak valid untuk absensi ini.'], 403);
        }

        $settingModel = $this->model('Setting');
        $studentModel = $this->model('Student');
        $attendanceModel = $this->model('Attendance');
        $minConfidenceScore = (float)$settingModel->get('min_confidence_score', '80.00');

        // Prioritas pencarian: NIS utama siswa, fallback ke face_model_identifier
        $student = $studentModel->findByIdentifierOrNis($identifier);
        $recognizedUserId = $student ? (int)$student['user_id'] : null;

        if (!$student || !$attendanceModel->validateFaceAttendancePayload([
            'user_id' => $sessionUserId,
            'recognized_user_id' => $recognizedUserId,
            'identifier' => $identifier,
            'confidence_score' => $confidenceScore
        ], $minConfidenceScore)) {
            $this->json([
                'success' => false,
                'message' => 'Identitas wajah tidak dapat diverifikasi.'
            ], 400);
        }

        if ($attendanceModel->hasAttendanceForDate($sessionUserId, date('Y-m-d'))) {
            $this->json([
                'success' => false,
                'duplicate' => true,
                'message' => 'Anda sudah melakukan absensi hari ini.'
            ], 409);
        }

        $attendanceTime = date('H:i:s');
        $attendanceStatus = $attendanceModel->calculateStatus($attendanceTime, $settingModel->get('attendance_late_time', '07:15:00'));
        $result = $attendanceModel->createAttendanceIfNotExists([
            'user_id' => $sessionUserId,
            'attendance_date' => date('Y-m-d'),
            'attendance_time' => $attendanceTime,
            'status' => $attendanceStatus,
            'method' => 'face_recognition',
            'confidence_score' => $confidenceScore,
            'notes' => 'Absensi otomatis via face recognition',
            'late_threshold' => $settingModel->get('attendance_late_time', '07:15:00')
        ]);

        if (!($result['success'] ?? false)) {
            $this->json([
                'success' => false,
                'duplicate' => (bool)($result['duplicate'] ?? false),
                'message' => $result['message'] ?? 'Identitas wajah tidak dapat diverifikasi.'
            ], (isset($result['duplicate']) && $result['duplicate']) ? 409 : 400);
        }

        $this->json([
            'success' => true,
            'message' => 'ABSENSI BERHASIL',
            'attendance' => [
                'name' => $user['name'],
                'date' => date('d-m-Y'),
                'time' => $attendanceTime,
                'status' => $result['status'],
                'confidence_score' => number_format((float)$confidenceScore, 2, '.', '') . '%',
                'method' => 'face_recognition'
            ]
        ]);
    }

    /**
     * Halaman Pengaturan Model (Khusus Super User)
     * GET /scan/settings
     */
    public function settings() {
        Auth::requireRole('super_user');

        $settingModel = $this->model('Setting');
        $modelUrl = $settingModel->get('teachable_machine_model_url', 'https://teachablemachine.withgoogle.com/models/sample/');
        $minConfidence = $settingModel->get('min_confidence_score', '80.00');
        $lateTime = $settingModel->get('attendance_late_time', '07:15:00');

        $data = [
            'title' => 'Pengaturan Model & Jam Presensi - ' . APP_NAME,
            'model_url' => $modelUrl,
            'min_confidence' => $minConfidence,
            'late_time' => $lateTime
        ];

        $this->view('scan/settings', $data);
    }

    /**
     * Simpan Pengaturan Model & Jam Batas Presensi (Khusus Super User)
     * POST /scan/settings_update
     */
    public function settings_update() {
        Auth::requireRole('super_user');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('scan/settings');
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            Flash::set('danger', 'Sesi form kedaluwarsa.');
            $this->redirect('scan/settings');
        }

        $modelUrl = trim($_POST['model_url'] ?? '');
        $minConfidence = trim($_POST['min_confidence'] ?? '80.00');
        $lateTime = trim($_POST['late_time'] ?? '07:15:00');

        if (empty($modelUrl)) {
            Flash::set('danger', 'URL Model Teachable Machine tidak boleh kosong.');
            $this->redirect('scan/settings');
        }

        // Format validasi URL
        if (!filter_var($modelUrl, FILTER_VALIDATE_URL)) {
            Flash::set('danger', 'Format URL model tidak valid.');
            $this->redirect('scan/settings');
        }

        $confidenceVal = (float)$minConfidence;
        if ($confidenceVal < 10 || $confidenceVal > 100) {
            Flash::set('danger', 'Minimum confidence harus berada di antara 10% hingga 100%.');
            $this->redirect('scan/settings');
        }

        if (!empty($lateTime)) {
            if (strlen($lateTime) === 5) {
                $lateTime .= ':00';
            }
        } else {
            $lateTime = '07:15:00';
        }

        $settingModel = $this->model('Setting');
        $oldSettings = [
            'teachable_machine_model_url' => $settingModel->get('teachable_machine_model_url'),
            'min_confidence_score' => $settingModel->get('min_confidence_score'),
            'attendance_late_time' => $settingModel->get('attendance_late_time')
        ];

        $settingModel->set('teachable_machine_model_url', rtrim($modelUrl, '/') . '/');
        $settingModel->set('min_confidence_score', number_format($confidenceVal, 2, '.', ''));
        $settingModel->set('attendance_late_time', $lateTime);

        $newSettings = [
            'teachable_machine_model_url' => rtrim($modelUrl, '/') . '/',
            'min_confidence_score' => number_format($confidenceVal, 2, '.', ''),
            'attendance_late_time' => $lateTime
        ];

        AuditLog::record('UPDATE_AI_MODEL_SETTINGS', 'settings', null, $oldSettings, $newSettings);

        Flash::set('success', 'Konfigurasi model AI & jam batas presensi berhasil disimpan.');
        $this->redirect('scan/settings');
    }
}
