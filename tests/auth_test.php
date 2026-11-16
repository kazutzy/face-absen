<?php
/**
 * Automated Test Suite for Authentication, Role-Based Authorization, and CSRF Protection
 * Run: php tests/auth_test.php
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

function assertTest($condition, $message) {
    if ($condition) {
        echo " [PASS] " . $message . "\n";
    } else {
        echo " [FAIL] " . $message . "\n";
        exit(1);
    }
}

echo "========================================================\n";
echo "    FaceAbsen Test Suite: Auth, RBAC & Security        \n";
echo "========================================================\n\n";

// --- TEST 1: CSRF Token Generator & Validator ---
echo "--- 1. Testing CSRF Protection ---\n";
$token = Csrf::getToken();
assertTest(!empty($token) && strlen($token) === 64, "CSRF token generated with 64 hex characters.");
assertTest(Csrf::validate($token) === true, "Valid CSRF token passes validation.");
assertTest(Csrf::validate('invalid_token_123') === false, "Invalid CSRF token is rejected.");
assertTest(Csrf::validate('') === false, "Empty CSRF token is rejected.");
echo "\n";

// --- TEST 2: Password Verification Logic ---
echo "--- 2. Testing Password Hashing & Verification ---\n";
$userModel = new User();
$plainAdminPass = 'admin123';
$adminUser = [
    'id' => 1,
    'username' => 'admin',
    'password' => password_hash($plainAdminPass, PASSWORD_BCRYPT),
    'name' => 'Administrator FaceAbsen',
    'role' => 'super_user',
    'status' => 'active'
];
assertTest(password_verify($plainAdminPass, $adminUser['password']), "BCRYPT password verification works for Super User.");
assertTest(password_verify('wrongpass', $adminUser['password']) === false, "Wrong password verification fails correctly.");
echo "\n";

// --- TEST 3: Auth Session & Role Checking for Super User ---
echo "--- 3. Testing Super User Auth & Role Guard ---\n";
Auth::login($adminUser);
assertTest(Auth::check() === true, "Auth::check() returns true after login.");
assertTest(Auth::role() === 'super_user', "Auth::role() returns 'super_user'.");
assertTest(Auth::hasRole('super_user') === true, "Auth::hasRole('super_user') is true.");
assertTest(Auth::hasRole('guru') === false, "Super User accessing guru role is false.");
assertTest(Auth::hasRole('peserta_didik') === false, "Super User accessing siswa role is false.");
assertTest(Auth::getDashboardUrl() === 'admin/dashboard', "Super User dashboard URL is 'admin/dashboard'.");
echo "\n";

// --- TEST 4: Auth Session & Role Checking for Guru ---
echo "--- 4. Testing Guru Auth & Role Guard ---\n";
$guruUser = [
    'id' => 2,
    'username' => 'guru1',
    'password' => password_hash('guru123', PASSWORD_BCRYPT),
    'name' => 'Budi Santoso, S.Kom',
    'role' => 'guru',
    'status' => 'active'
];
Auth::login($guruUser);
assertTest(Auth::role() === 'guru', "Auth::role() returns 'guru'.");
assertTest(Auth::hasRole('guru') === true, "Auth::hasRole('guru') is true.");
assertTest(Auth::hasRole('super_user') === false, "Guru accessing super_user role is false (Protected).");
assertTest(Auth::hasRole('peserta_didik') === false, "Guru accessing peserta_didik role is false (Protected).");
assertTest(Auth::getDashboardUrl() === 'guru/dashboard', "Guru dashboard URL is 'guru/dashboard'.");
echo "\n";

// --- TEST 5: Auth Session & Role Checking for Peserta Didik ---
echo "--- 5. Testing Peserta Didik Auth & Role Guard ---\n";
$siswaUser = [
    'id' => 3,
    'username' => 'siswa1',
    'password' => password_hash('siswa123', PASSWORD_BCRYPT),
    'name' => 'Ahmad Fauzi',
    'role' => 'peserta_didik',
    'status' => 'active'
];
Auth::login($siswaUser);
assertTest(Auth::role() === 'peserta_didik', "Auth::role() returns 'peserta_didik'.");
assertTest(Auth::hasRole('peserta_didik') === true, "Auth::hasRole('peserta_didik') is true.");
assertTest(Auth::hasRole('super_user') === false, "Siswa accessing super_user role is false (Protected).");
assertTest(Auth::hasRole('guru') === false, "Siswa accessing guru role is false (Protected).");
assertTest(Auth::getDashboardUrl() === 'siswa/dashboard', "Siswa dashboard URL is 'siswa/dashboard'.");
echo "\n";

// --- TEST 6: Logout & Unauthenticated State ---
echo "--- 6. Testing Logout & Unauthenticated State ---\n";
Auth::logout();
assertTest(Auth::check() === false, "Auth::check() returns false after logout.");
assertTest(Auth::user() === null, "Auth::user() returns null after logout.");
assertTest(Auth::role() === null, "Auth::role() returns null after logout.");
echo "\n";

// --- TEST 7: Flash Notification Helper ---
echo "--- 7. Testing Flash Notification Helper ---\n";
Flash::set('success', 'Operasi berhasil.');
$flash = Flash::get();
assertTest($flash !== null && $flash['type'] === 'success' && $flash['message'] === 'Operasi berhasil.', "Flash message correctly set and retrieved.");
assertTest(Flash::get() === null, "Flash message is cleared after single retrieval.");
echo "\n";

echo "========================================================\n";
echo " ALL AUTHENTICATION & AUTHORIZATION TESTS PASSED 100%!  \n";
echo "========================================================\n";
