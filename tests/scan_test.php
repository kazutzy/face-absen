<?php
/**
 * Automated Test Suite for FaceAbsen Face Recognition Configuration & Permissions
 * Run: php tests/scan_test.php
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

function assertTest($condition, $message) {
    if ($condition) {
        echo " [PASS] " . $message . "\n";
    } else {
        echo " [FAIL] " . $message . "\n";
        exit(1);
    }
}

echo "========================================================\n";
echo "    FaceAbsen Test Suite: Face Recognition & Config    \n";
echo "========================================================\n\n";

$settingModel = new Setting();
$studentModel = new Student();

// --- 1. Testing Teachable Machine Settings in Database ---
echo "--- 1. Testing Dynamic AI Model Settings Storage ---\n";
$currentUrl = $settingModel->get('teachable_machine_model_url');
assertTest(!empty($currentUrl), "Retrieved model URL from settings: $currentUrl");

$testModelUrl = 'https://teachablemachine.withgoogle.com/models/test-model-123/';
$settingModel->set('teachable_machine_model_url', $testModelUrl);
assertTest($settingModel->get('teachable_machine_model_url') === $testModelUrl, "Updated model URL dynamically without code changes.");

$settingModel->set('min_confidence_score', '85.00');
assertTest($settingModel->get('min_confidence_score') === '85.00', "Updated minimum confidence threshold to 85.00%.");
echo "\n";

// --- 2. Testing Student Face Identifier Mappings ---
echo "--- 2. Testing Student Face Identifier Mapping ---\n";
$students = $studentModel->getAllWithDetails();
$mappedCount = 0;
foreach ($students as $s) {
    if (!empty($s['face_model_identifier'])) {
        $mappedCount++;
        assertTest(!empty($s['name']) && !empty($s['nis']), "Student '{$s['name']}' has valid identifier '{$s['face_model_identifier']}'.");
    }
}
assertTest($mappedCount >= 5, "Found $mappedCount registered student face identifier(s).");

// Test finding student directly by NIS (training class in Teachable Machine)
$nis1 = $studentModel->findByNis('111111');
assertTest($nis1 && $nis1['nis'] === '111111', "Student with NIS '111111' resolved successfully.");
$nis2 = $studentModel->findByIdentifierOrNis('222222');
assertTest($nis2 && $nis2['nis'] === '222222', "Student with NIS '222222' resolved via findByIdentifierOrNis.");
$nis3 = $studentModel->findByIdentifierOrNis('333333');
assertTest($nis3 && $nis3['nis'] === '333333', "Student with NIS '333333' resolved via findByIdentifierOrNis.");
echo "\n";

// --- 3. Testing JavaScript Engine Structure ---
echo "--- 3. Testing Face Recognition Modular JS File ---\n";
$jsFile = __DIR__ . '/../public/js/face-recognizer.js';
assertTest(file_exists($jsFile), "public/js/face-recognizer.js file exists.");
$jsContent = file_get_contents($jsFile);
assertTest(strpos($jsContent, 'class FaceAbsenRecognizer') !== false, "JS contains 'class FaceAbsenRecognizer'.");
assertTest(strpos($jsContent, 'async loadModel') !== false, "JS contains 'loadModel' method.");
assertTest(strpos($jsContent, 'async startCamera') !== false, "JS contains 'startCamera' method.");
assertTest(strpos($jsContent, 'onFaceRecognized') !== false, "JS contains 'onFaceRecognized' event hook.");
assertTest(strpos($jsContent, 'onFaceTracking') !== false, "JS contains 'onFaceTracking' real-time tracking hook.");
assertTest(strpos($jsContent, 'facingMode') !== false, "JS supports mobile front camera facingMode.");
echo "\n";

// Restore original test url
$settingModel->set('teachable_machine_model_url', 'https://teachablemachine.withgoogle.com/models/sample/');
$settingModel->set('min_confidence_score', '80.00');

echo "========================================================\n";
echo " ALL FACE RECOGNITION & CONFIG TESTS PASSED 100%!       \n";
echo "========================================================\n";
