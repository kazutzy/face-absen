<?php
/**
 * Automated Test Suite for Attendance Report & Filters (Tahap 8)
 * Run: php tests/report_test.php
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
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Student.php';
require_once __DIR__ . '/../app/models/Teacher.php';
require_once __DIR__ . '/../app/models/Classes.php';
require_once __DIR__ . '/../app/models/Setting.php';
require_once __DIR__ . '/../app/models/Attendance.php';
require_once __DIR__ . '/../app/controllers/AttendanceController.php';

function assertReportTest($condition, $message) {
    if ($condition) {
        echo " [PASS] " . $message . "\n";
    } else {
        echo " [FAIL] " . $message . "\n";
        exit(1);
    }
}

function captureControllerOutput($controller, $method, $params = []) {
    ob_start();
    call_user_func_array([$controller, $method], $params);
    return ob_get_clean();
}

$db = Database::getInstance();
$attendanceModel = new Attendance();

echo "========================================================\n";
echo " FaceAbsen Test Suite: Tahap 8 - Attendance Report & RBAC\n";
echo "========================================================\n\n";

// Siapkan data attendance sampel untuk testing komprehensif
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$lastWeek = date('Y-m-d', strtotime('-7 days'));

// User 3 (siswa1 - Ahmad Fauzi), User 6 (fajar - Fajar), User 2 (guru1 - Budi)
$testRecords = [
    [
        'user_id' => 3,
        'attendance_date' => $today,
        'attendance_time' => '07:05:00',
        'status' => 'hadir',
        'method' => 'face_recognition',
        'confidence_score' => 95.50,
        'notes' => 'Presensi hadir via AI'
    ],
    [
        'user_id' => 3,
        'attendance_date' => $yesterday,
        'attendance_time' => '07:20:00',
        'status' => 'terlambat',
        'method' => 'face_recognition',
        'confidence_score' => 92.00,
        'notes' => 'Presensi terlambat'
    ],
    [
        'user_id' => 6,
        'attendance_date' => $today,
        'attendance_time' => '07:10:00',
        'status' => 'hadir',
        'method' => 'face_recognition',
        'confidence_score' => 88.00,
        'notes' => 'Presensi siswa 6'
    ],
    [
        'user_id' => 2, // guru1
        'attendance_date' => $today,
        'attendance_time' => '06:55:00',
        'status' => 'hadir',
        'method' => 'face_recognition',
        'confidence_score' => 96.00,
        'notes' => 'Presensi guru'
    ]
];

foreach ($testRecords as $rec) {
    $attendanceModel->deleteDuplicateForTest($rec['user_id'], $rec['attendance_date']);
    $attendanceModel->createAttendanceIfNotExists($rec);
}

// --------------------------------------------------------------------------
// 1. FILTER TANGGAL & RANGE TANGGAL
// --------------------------------------------------------------------------
echo "--- 1. Testing Filter Tanggal & Range Tanggal ---\n";
$filterSingleDate = $attendanceModel->getFilteredReport(['date' => $today]);
assertReportTest(!empty($filterSingleDate), 'Filter single date berhasil mengambil catatan hari ini.');

$filterDateRange = $attendanceModel->getFilteredReport([
    'start_date' => $yesterday,
    'end_date' => $today
]);
assertReportTest(count($filterDateRange) >= 3, 'Filter date range (yesterday - today) mengambil rentang tanggal dengan benar.');

echo "\n";

// --------------------------------------------------------------------------
// 2. FILTER KELAS & SEARCH KEYWORD
// --------------------------------------------------------------------------
echo "--- 2. Testing Filter Kelas & Search Keyword ---\n";
$filterClass = $attendanceModel->getFilteredReport(['class_id' => 1]);
assertReportTest(count($filterClass) >= 2, 'Filter kelas 1 berhasil mengambil presensi siswa kelas tersebut.');

$searchResult = $attendanceModel->getFilteredReport(['search' => 'Ahmad']);
assertReportTest(!empty($searchResult) && strpos($searchResult[0]['name'], 'Ahmad') !== false, 'Pencarian kata kunci nama siswa berhasil.');

echo "\n";

// --------------------------------------------------------------------------
// 3. FILTER ROLE & STATUS
// --------------------------------------------------------------------------
echo "--- 3. Testing Filter Role & Status ---\n";
$filterGuru = $attendanceModel->getFilteredReport(['role' => 'guru']);
assertReportTest(!empty($filterGuru) && $filterGuru[0]['role'] === 'guru', 'Filter kategori role Guru berhasil.');

$filterLate = $attendanceModel->getFilteredReport(['status' => 'terlambat']);
assertReportTest(!empty($filterLate) && $filterLate[0]['status'] === 'terlambat', 'Filter status Terlambat berhasil.');

echo "\n";

// --------------------------------------------------------------------------
// 4. PAGINATION & TOTAL COUNT
// --------------------------------------------------------------------------
echo "--- 4. Testing Pagination & Count ---\n";
$totalRows = $attendanceModel->countFilteredReport([]);
$paginated = $attendanceModel->getFilteredReport([], 2, 0);
assertReportTest($totalRows >= 4, 'Total data terhitung akurat untuk pagination.');
assertReportTest(count($paginated) <= 2, 'Limit pagination per halaman bekerja dengan tepat.');

echo "\n";

// --------------------------------------------------------------------------
// 5. REKAP BULANAN (MONTHLY RECAP AGGREGATION)
// --------------------------------------------------------------------------
echo "--- 5. Testing Rekap Bulanan Teragregasi ---\n";
$curMonth = (int)date('n');
$curYear = (int)date('Y');
$monthlyRecap = $attendanceModel->getMonthlyRecap($curMonth, $curYear);
assertReportTest(!empty($monthlyRecap), 'Rekap bulanan berhasil diagregasi per pengguna.');

$ahmadRecap = null;
foreach ($monthlyRecap as $mr) {
    if ($mr['user_id'] == 3) {
        $ahmadRecap = $mr;
        break;
    }
}
assertReportTest($ahmadRecap !== null, 'Data siswa Ahmad Fauzi ditemukan pada rekap bulanan.');
assertReportTest((int)$ahmadRecap['total_hadir'] >= 1, 'Agregasi Hadir pada rekap bulanan tercatat.');
assertReportTest((int)$ahmadRecap['total_terlambat'] >= 1, 'Agregasi Terlambat pada rekap bulanan tercatat.');

echo "\n";

// --------------------------------------------------------------------------
// 6. PERMISSION & DATA ISOLATION (RBAC)
// --------------------------------------------------------------------------
echo "--- 6. Testing Hak Akses (Permissions & RBAC) ---\n";

// A. Super User: Melihat semua data
$adminUser = $db->query("SELECT * FROM users WHERE role = 'super_user' LIMIT 1")->single();
Auth::login($adminUser);
$_GET = [];
$controller = new AttendanceController();
$adminHtml = captureControllerOutput($controller, 'index');
assertReportTest(strpos($adminHtml, 'Data Log Kehadiran') !== false, 'Super User dapat mengakses halaman rekap seluruh data.');

// B. Peserta Didik: Hanya melihat data sendiri
$siswaUser = $db->query("SELECT * FROM users WHERE role = 'peserta_didik' AND id = 3 LIMIT 1")->single();
Auth::login($siswaUser);
$_GET = [];
$siswaController = new AttendanceController();
$siswaHtml = captureControllerOutput($siswaController, 'index');
assertReportTest(strpos($siswaHtml, 'Ahmad Fauzi') !== false, 'Peserta Didik dapat melihat data presensi miliknya.');
// Pastikan peserta didik tidak melihat data guru pada tabelnya jika user_id difilter
$siswaFiltered = $attendanceModel->getFilteredReport(['user_id' => $siswaUser['id']]);
$hasOtherUser = false;
foreach ($siswaFiltered as $sf) {
    if ($sf['user_id'] != $siswaUser['id']) {
        $hasOtherUser = true;
        break;
    }
}
assertReportTest($hasOtherUser === false, 'Data presensi siswa terisolasi secara ketat (hanya milik sendiri).');

echo "\n";

// --------------------------------------------------------------------------
// 7. PRINT VIEW & EXCEL EXPORT INTEGRITY
// --------------------------------------------------------------------------
echo "--- 7. Testing Print View & Format Export ---\n";
Auth::login($adminUser);
$_GET = ['type' => 'daily'];
$printHtml = captureControllerOutput($controller, 'print');
assertReportTest(strpos($printHtml, 'LAPORAN LOG PRESENSI HARIAN') !== false, 'Tampilan cetak harian ter-render dengan format print-friendly.');
assertReportTest(strpos($printHtml, 'window.print()') !== false, 'Tombol fungsi cetak PDF terpasang.');

$_GET = ['type' => 'monthly', 'month' => $curMonth, 'year' => $curYear];
$printMonthlyHtml = captureControllerOutput($controller, 'print');
assertReportTest(strpos($printMonthlyHtml, 'REKAPITULASI ABSENSI BULANAN') !== false, 'Tampilan cetak bulanan ter-render dengan format print-friendly.');

echo "\n========================================================\n";
echo " ALL ATTENDANCE REPORT & PERMISSION TESTS PASSED (100%) \n";
echo "========================================================\n";
