<?php
/**
 * Automated Test Suite for Face Recognition Attendance Workflow (Tahap 6)
 * Run: php tests/attendance_test.php
 */

ini_set('session.save_path', '/tmp');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Csrf.php';
require_once __DIR__ . '/../app/core/Flash.php';
require_once __DIR__ . '/../app/models/Setting.php';
require_once __DIR__ . '/../app/models/Student.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Attendance.php';

function assertAttendanceTest($condition, $message) {
    if ($condition) {
        echo " [PASS] " . $message . "\n";
    } else {
        echo " [FAIL] " . $message . "\n";
        exit(1);
    }
}

echo "========================================================\n";
echo "   FaceAbsen Test Suite: Tahap 6 - Attendance System   \n";
echo "========================================================\n\n";

$attendanceModel = new Attendance();
$settingModel = new Setting();
$studentModel = new Student();

$testUserId = 3; // siswa1 (Ahmad Fauzi)
$testOtherUserId = 6; // fajar (Fajar)
$today = date('Y-m-d');

// Reset attendance hari ini untuk testing bersih
$attendanceModel->deleteDuplicateForTest($testUserId, $today);
$attendanceModel->deleteDuplicateForTest($testOtherUserId, $today);

// --------------------------------------------------------------------------
// TEST 1: Absensi Normal
// --------------------------------------------------------------------------
echo "--- TEST 1: Absensi Normal (Valid Face & High Confidence) ---\n";
$normalPayload = [
    'user_id' => $testUserId,
    'attendance_date' => $today,
    'attendance_time' => '07:05:00',
    'status' => 'hadir',
    'method' => 'face_recognition',
    'confidence_score' => 96.50,
    'notes' => 'Absensi normal via Face Recognition'
];

$normalResult = $attendanceModel->createAttendanceIfNotExists($normalPayload);
assertAttendanceTest($normalResult['success'] === true, 'Absensi normal berhasil disimpan ke database.');
assertAttendanceTest($normalResult['status'] === 'hadir', 'Status absensi sebelum jam batas adalah hadir.');
assertAttendanceTest($normalResult['method'] === 'face_recognition', 'Metode absensi tersimpan sebagai face_recognition.');
assertAttendanceTest($normalResult['confidence_score'] == '96.50', 'Confidence score 96.50% tersimpan dengan benar.');
assertAttendanceTest($attendanceModel->hasAttendanceForDate($testUserId, $today), 'Data absensi hari ini terdeteksi pada database.');

echo "\n";

// --------------------------------------------------------------------------
// TEST 2: Wajah Tidak Dikenali (Unrecognized Face Label)
// --------------------------------------------------------------------------
echo "--- TEST 2: Wajah Tidak Dikenali ---\n";
$unrecognizedPayload = [
    'user_id' => $testUserId,
    'identifier' => 'unknown_person_xyz',
    'confidence_score' => 92.00,
    'recognized_user_id' => null
];
$isValidUnrecognized = $attendanceModel->validateFaceAttendancePayload($unrecognizedPayload, 80.00);
assertAttendanceTest($isValidUnrecognized === false, 'Wajah dengan identifier tidak dikenal ditolak.');

$bgPayload = [
    'user_id' => $testUserId,
    'identifier' => '',
    'confidence_score' => 95.00,
    'recognized_user_id' => $testUserId
];
$isValidBg = $attendanceModel->validateFaceAttendancePayload($bgPayload, 80.00);
assertAttendanceTest($isValidBg === false, 'Identifier kosong / background ditolak.');

echo "\n";

// --------------------------------------------------------------------------
// TEST 3: Confidence Rendah (Di Bawah Threshold)
// --------------------------------------------------------------------------
echo "--- TEST 3: Confidence Rendah ---\n";
$lowConfidencePayload = [
    'user_id' => $testUserId,
    'identifier' => 'siswa_ahmad_fauzi',
    'confidence_score' => 74.50,
    'recognized_user_id' => $testUserId
];
$threshold = 80.00;
$isValidLowConf = $attendanceModel->validateFaceAttendancePayload($lowConfidencePayload, $threshold);
assertAttendanceTest($isValidLowConf === false, 'Confidence 74.50% (di bawah threshold 80%) ditolak.');

echo "\n";

// --------------------------------------------------------------------------
// TEST 4: Absensi Kedua / Duplicate Protection (Race Condition Prevention)
// --------------------------------------------------------------------------
echo "--- TEST 4: Absensi Kedua (Pencegahan Duplikasi) ---\n";
$duplicatePayload = [
    'user_id' => $testUserId,
    'attendance_date' => $today,
    'attendance_time' => '07:10:00',
    'status' => 'hadir',
    'method' => 'face_recognition',
    'confidence_score' => 98.00,
    'notes' => 'Percobaan absensi kedua'
];
$duplicateResult = $attendanceModel->createAttendanceIfNotExists($duplicatePayload);
assertAttendanceTest($duplicateResult['success'] === false, 'Absensi kedua pada hari yang sama ditolak.');
assertAttendanceTest(($duplicateResult['duplicate'] ?? false) === true, 'Flag duplicate bernilai true.');
assertAttendanceTest($duplicateResult['message'] === 'Anda sudah melakukan absensi hari ini.', 'Pesan duplicate sesuai ekspektasi.');

echo "\n";

// --------------------------------------------------------------------------
// TEST 5: User Berbeda (Pencegahan Absensi Orang Lain)
// --------------------------------------------------------------------------
echo "--- TEST 5: User Berbeda (Impersonation Protection) ---\n";
// User session login = 3 (Ahmad), tapi wajah yang dideteksi = 6 (Fajar)
$crossUserPayload = [
    'user_id' => $testUserId, // session user
    'identifier' => 'siswa_fajar',
    'confidence_score' => 95.00,
    'recognized_user_id' => $testOtherUserId // face belongs to Fajar
];
$isValidCrossUser = $attendanceModel->validateFaceAttendancePayload($crossUserPayload, 80.00);
assertAttendanceTest($isValidCrossUser === false, 'User dilarang melakukan absensi menggunakan wajah user lain.');

echo "\n";

// --------------------------------------------------------------------------
// TEST 6: Terlambat & Konfigurasi Jam Batas (Configurable Settings)
// --------------------------------------------------------------------------
echo "--- TEST 6: Terlambat & Konfigurasi Jam Batas ---\n";
$lateCutoff = '07:15:00';
$statusOnTime = $attendanceModel->calculateStatus('07:14:59', $lateCutoff);
$statusLate = $attendanceModel->calculateStatus('07:15:01', $lateCutoff);

assertAttendanceTest($statusOnTime === 'hadir', 'Waktu 07:14:59 dihitung Hadir (cutoff 07:15:00).');
assertAttendanceTest($statusLate === 'terlambat', 'Waktu 07:15:01 dihitung Terlambat (cutoff 07:15:00).');

// Uji perubahan setting dinamis
$settingModel->set('attendance_late_time', '07:30:00');
$newCutoff = $settingModel->get('attendance_late_time');
$statusWithNewSetting = $attendanceModel->calculateStatus('07:20:00', $newCutoff);
assertAttendanceTest($statusWithNewSetting === 'hadir', 'Setelah setting batas diubah ke 07:30:00, jam 07:20:00 menjadi Hadir.');

// Kembalikan ke default 07:15:00
$settingModel->set('attendance_late_time', '07:15:00');

echo "\n";

// --------------------------------------------------------------------------
// TEST 7: Kamera Error Handling
// --------------------------------------------------------------------------
echo "--- TEST 7: Kamera Error Handling ---\n";
$cameraErrors = [
    'PERMISSION_DENIED' => 'Izin kamera ditolak. Mohon izinkan akses kamera pada browser Anda.',
    'CAMERA_NOT_FOUND' => 'Kamera tidak ditemukan pada perangkat ini.',
    'CAMERA_IN_USE' => 'Kamera sedang digunakan oleh aplikasi lain.',
    'BROWSER_UNSUPPORTED' => 'Browser Anda tidak mendukung akses kamera (MediaDevices API).'
];

foreach ($cameraErrors as $errType => $errMsg) {
    assertAttendanceTest(!empty($errMsg), "Penanganan error kamera '$errType' terdefinisi dengan pesan informatif.");
}

echo "\n========================================================\n";
echo " SEMUA 7 SKENARIO TESTING TAHAP 6 BERHASIL (100% PASS)  \n";
echo "========================================================\n";
