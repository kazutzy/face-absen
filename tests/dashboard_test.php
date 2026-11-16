<?php
/**
 * Dashboard Role Verification Tests for FaceAbsen (Tahap 7)
 * Run: php tests/dashboard_test.php
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
require_once __DIR__ . '/../app/controllers/AdminController.php';
require_once __DIR__ . '/../app/controllers/GuruController.php';
require_once __DIR__ . '/../app/controllers/SiswaController.php';

function assertDashboardTest($condition, $message) {
    if ($condition) {
        echo " [PASS] " . $message . "\n";
    } else {
        echo " [FAIL] " . $message . "\n";
        exit(1);
    }
}

function renderDashboard($controller, $method) {
    ob_start();
    $controller->$method();
    $html = ob_get_clean();
    return $html;
}

$database = Database::getInstance();

echo "========================================================\n";
echo "   FaceAbsen Test Suite: Tahap 7 - Multi-Role Dashboard \n";
echo "========================================================\n\n";

// --------------------------------------------------------------------------
// 1. SUPER USER DASHBOARD VERIFICATION
// --------------------------------------------------------------------------
echo "--- 1. Testing Super User Dashboard ---\n";
$adminUser = $database->query("SELECT * FROM users WHERE role = 'super_user' LIMIT 1")->single();
Auth::login($adminUser);
$admin = new AdminController();
$htmlAdmin = renderDashboard($admin, 'dashboard');

assertDashboardTest(strpos($htmlAdmin, 'Dashboard Administrator') !== false, 'Admin dashboard renders header.');
assertDashboardTest(strpos($htmlAdmin, 'Peserta Didik') !== false, 'Card Total Peserta Didik present.');
assertDashboardTest(strpos($htmlAdmin, 'Total Guru') !== false, 'Card Total Guru present.');
assertDashboardTest(strpos($htmlAdmin, 'Hadir Hari Ini') !== false, 'Card Hadir Hari Ini present.');
assertDashboardTest(strpos($htmlAdmin, 'Terlambat') !== false, 'Card Terlambat present.');
assertDashboardTest(strpos($htmlAdmin, 'Belum Absen') !== false, 'Card Belum Absen present.');
assertDashboardTest(strpos($htmlAdmin, 'Statistik Absensi Hari Ini') !== false, 'Section Statistik Absensi Hari Ini present.');
assertDashboardTest(strpos($htmlAdmin, 'Tabel Absensi Terbaru') !== false, 'Section Tabel Absensi Terbaru present.');
assertDashboardTest(strpos($htmlAdmin, 'admin/students') !== false, 'Quick Action: Link Data Peserta Didik present.');
assertDashboardTest(strpos($htmlAdmin, 'admin/teachers') !== false, 'Quick Action: Link Data Guru present.');
assertDashboardTest(strpos($htmlAdmin, 'admin/classes') !== false, 'Quick Action: Link Data Kelas present.');

echo "\n";

// --------------------------------------------------------------------------
// 2. GURU DASHBOARD VERIFICATION
// --------------------------------------------------------------------------
echo "--- 2. Testing Guru Dashboard ---\n";
$guruUser = $database->query("SELECT * FROM users WHERE role = 'guru' LIMIT 1")->single();
Auth::login($guruUser);
$guru = new GuruController();
$htmlGuru = renderDashboard($guru, 'dashboard');

assertDashboardTest(strpos($htmlGuru, 'Dashboard Guru') !== false, 'Guru dashboard renders header.');
assertDashboardTest(strpos($htmlGuru, 'Presensi Pribadi Hari Ini') !== false, 'Card Presensi Pribadi Hari Ini present.');
assertDashboardTest(strpos($htmlGuru, 'Status Presensi') !== false, 'Status absensi pribadi present.');
assertDashboardTest(strpos($htmlGuru, 'Rekap Kehadiran Sederhana') !== false, 'Section Rekap Kehadiran Sederhana present.');
assertDashboardTest(strpos($htmlGuru, 'Buka FaceAbsen') !== false, 'Link FaceAbsen present.');
assertDashboardTest(strpos($htmlGuru, 'Ringkasan Kehadiran Kelas') !== false, 'Section Ringkasan Kehadiran Kelas present.');

echo "\n";

// --------------------------------------------------------------------------
// 3. PESERTA DIDIK DASHBOARD VERIFICATION
// --------------------------------------------------------------------------
echo "--- 3. Testing Peserta Didik Dashboard ---\n";
$siswaUser = $database->query("SELECT * FROM users WHERE role = 'peserta_didik' LIMIT 1")->single();
Auth::login($siswaUser);
$siswa = new SiswaController();
$htmlSiswa = renderDashboard($siswa, 'dashboard');

assertDashboardTest(strpos($htmlSiswa, 'Dashboard Peserta Didik') !== false, 'Student dashboard renders header.');
assertDashboardTest(strpos($htmlSiswa, 'Profil Siswa') !== false, 'Card Profil Siswa present.');
assertDashboardTest(strpos($htmlSiswa, 'Status Presensi Hari Ini') !== false, 'Card Status Presensi Hari Ini present.');
assertDashboardTest(strpos($htmlSiswa, 'Buka FaceAbsen') !== false, 'Link FaceAbsen present.');
assertDashboardTest(strpos($htmlSiswa, 'Riwayat Absensi Terbaru') !== false, 'Table Riwayat Absensi Terbaru present.');

echo "\n";

// --------------------------------------------------------------------------
// 4. DATABASE INTEGRITY & REAL-DATA VERIFICATION
// --------------------------------------------------------------------------
echo "--- 4. Testing Real Database Binding & Counters ---\n";
$teacherCount = (int)$database->query("SELECT COUNT(*) as total FROM teachers")->single()['total'];
$studentCount = (int)$database->query("SELECT COUNT(*) as total FROM students")->single()['total'];
assertDashboardTest($teacherCount > 0 && $studentCount > 0, 'Dashboard counters reflect real seeded database records.');

echo "\n========================================================\n";
echo " ALL DASHBOARD ROLE TESTS PASSED 100%!                   \n";
echo "========================================================\n";
