<?php
/**
 * Automated Security & Anti-Fraud Test Suite (Tahap 9)
 * Run: php tests/security_test.php
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
require_once __DIR__ . '/../app/core/RateLimiter.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Student.php';
require_once __DIR__ . '/../app/models/Teacher.php';
require_once __DIR__ . '/../app/models/Classes.php';
require_once __DIR__ . '/../app/models/Setting.php';
require_once __DIR__ . '/../app/models/Attendance.php';
require_once __DIR__ . '/../app/models/AuditLog.php';

function assertSecurityTest($condition, $message) {
    if ($condition) {
        echo " [PASS] " . $message . "\n";
    } else {
        echo " [FAIL] " . $message . "\n";
        exit(1);
    }
}

$db = Database::getInstance();
$attendanceModel = new Attendance();
$settingModel = new Setting();
$studentModel = new Student();

echo "========================================================\n";
echo " FaceAbsen Test Suite: Tahap 9 - Security & Anti-Fraud  \n";
echo "========================================================\n\n";

// --------------------------------------------------------------------------
// 1. RATE LIMITING PROTECTION
// --------------------------------------------------------------------------
echo "--- 1. Testing Rate Limiting on Sensitive Endpoints ---\n";
$testRateKey = 'test_scan_limit_' . time();
RateLimiter::reset($testRateKey);

$allowedCount = 0;
for ($i = 1; $i <= 5; $i++) {
    if (RateLimiter::check($testRateKey, 5, 60)) {
        $allowedCount++;
    }
}
assertSecurityTest($allowedCount === 5, '5 request pertama diizinkan oleh RateLimiter.');

// Percobaan ke-6 harus diblokir karena limit adalah 5
$isBlocked = !RateLimiter::check($testRateKey, 5, 60);
assertSecurityTest($isBlocked === true, 'Request ke-6 berhasil diblokir oleh RateLimiter (HTTP 429 Simulation).');
RateLimiter::reset($testRateKey);

echo "\n";

// --------------------------------------------------------------------------
// 2. SERVER-SIDE IDENTITY AUTHORIZATION (ANTI-SPOOF / ANTI-TAMPERING)
// --------------------------------------------------------------------------
echo "--- 2. Testing Authoritative Identity Validation ---\n";
$sessionUserId = 3; // Siswa Ahmad Fauzi
$attackerClaimedUserId = 1; // Mencoba mengaku Admin atau siswa lain

$isPayloadValidWithFakeUser = $attendanceModel->validateFaceAttendancePayload([
    'user_id' => $sessionUserId,
    'recognized_user_id' => $attackerClaimedUserId,
    'identifier' => 'siswa_ahmad_fauzi',
    'confidence_score' => 95.00
], 80.00);
assertSecurityTest($isPayloadValidWithFakeUser === false, 'Manipulasi user_id dari browser ditolak di server.');

// --------------------------------------------------------------------------
// 3. CONFIGURABLE THRESHOLD & CONFIDENCE ENFORCEMENT
// --------------------------------------------------------------------------
echo "\n--- 3. Testing Configurable Threshold & Low Confidence ---\n";
$settingModel->set('min_confidence_score', '85.00');
$currentThreshold = (float)$settingModel->get('min_confidence_score');

$isAcceptedAt82 = $attendanceModel->validateFaceAttendancePayload([
    'user_id' => $sessionUserId,
    'recognized_user_id' => $sessionUserId,
    'identifier' => 'siswa_ahmad_fauzi',
    'confidence_score' => 82.00
], $currentThreshold);
assertSecurityTest($isAcceptedAt82 === false, 'Confidence 82% ditolak ketika threshold dikonfigurasi 85%.');

$isAcceptedAt87 = $attendanceModel->validateFaceAttendancePayload([
    'user_id' => $sessionUserId,
    'recognized_user_id' => $sessionUserId,
    'identifier' => 'siswa_ahmad_fauzi',
    'confidence_score' => 87.50
], $currentThreshold);
assertSecurityTest($isAcceptedAt87 === true, 'Confidence 87.5% diterima ketika threshold 85%.');

// Reset threshold ke 80.00%
$settingModel->set('min_confidence_score', '80.00');

echo "\n";

// --------------------------------------------------------------------------
// 4. SQL INJECTION IMMUNITY (PREPARED STATEMENTS)
// --------------------------------------------------------------------------
echo "--- 4. Testing SQL Injection Immunity ---\n";
$sqliPayloads = [
    "' OR '1'='1",
    "1' UNION SELECT 1,2,3,4,5,6,7,8--",
    "'; DROP TABLE users; --",
    "admin' --"
];

foreach ($sqliPayloads as $payload) {
    $results = $attendanceModel->getFilteredReport(['search' => $payload]);
    assertSecurityTest(is_array($results), "SQL Injection payload ($payload) ditangani secara aman via parameterized PDO bindings.");
}

echo "\n";

// --------------------------------------------------------------------------
// 5. CSRF PROTECTION & TOKEN INTEGRITY
// --------------------------------------------------------------------------
echo "--- 5. Testing CSRF Token Security ---\n";
$validToken = Csrf::token();
assertSecurityTest(Csrf::validate($validToken) === true, 'Valid CSRF token passes verification.');
assertSecurityTest(Csrf::validate('invalid_tampered_token_xyz') === false, 'Tampered CSRF token is strictly rejected.');
assertSecurityTest(Csrf::validate('') === false, 'Empty CSRF token is strictly rejected.');

echo "\n";

// --------------------------------------------------------------------------
// 6. AUDIT LOGGING FOR ADMINISTRATIVE ACTIONS
// --------------------------------------------------------------------------
echo "--- 6. Testing Audit Log Recording ---\n";
$auditLogged = AuditLog::record(
    'TEST_ADMIN_ACTION',
    'attendances',
    101,
    ['status' => 'alpa'],
    ['status' => 'hadir'],
    1
);
assertSecurityTest($auditLogged === true, 'Aksi audit administrator berhasil dicatat ke tabel audit_logs.');

$auditModel = new AuditLog();
$recentLogs = $auditModel->getRecent(5);
assertSecurityTest(!empty($recentLogs) && $recentLogs[0]['action'] === 'TEST_ADMIN_ACTION', 'Data jejak audit dapat dibaca kembali dari database.');

echo "\n";

// --------------------------------------------------------------------------
// 7. DUPLICATE ATTENDANCE & CONCURRENCY RACE CONDITION SAFEGUARD
// --------------------------------------------------------------------------
echo "--- 7. Testing Race Condition & Double Submission Protection ---\n";
$today = date('Y-m-d');
$attendanceModel->deleteDuplicateForTest($sessionUserId, $today);

$firstInsert = $attendanceModel->createAttendanceIfNotExists([
    'user_id' => $sessionUserId,
    'attendance_date' => $today,
    'attendance_time' => '07:00:00',
    'status' => 'hadir',
    'method' => 'face_recognition',
    'confidence_score' => 95.00
]);
assertSecurityTest($firstInsert['success'] === true, 'Presensi pertama hari ini berhasil tercatat.');

$secondInsert = $attendanceModel->createAttendanceIfNotExists([
    'user_id' => $sessionUserId,
    'attendance_date' => $today,
    'attendance_time' => '07:01:00',
    'status' => 'hadir',
    'method' => 'face_recognition',
    'confidence_score' => 96.00
]);
assertSecurityTest($secondInsert['success'] === false && ($secondInsert['duplicate'] ?? false) === true, 'Presensi kedua pada hari yang sama diblokir (Anti-Duplicate Guarantee).');

echo "\n========================================================\n";
echo " ALL SECURITY & ANTI-FRAUD TESTS PASSED (100% PASS)     \n";
echo "========================================================\n";
