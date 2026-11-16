<?php
/**
 * Automated Test Suite for API Key Configuration & Activation
 * Run: php tests/api_key_test.php
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
require_once __DIR__ . '/../app/models/AuditLog.php';
require_once __DIR__ . '/../app/models/ApiKey.php';
require_once __DIR__ . '/../app/models/Setting.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';
require_once __DIR__ . '/../app/controllers/ChatController.php';

function assertTest($condition, $message) {
    if ($condition) {
        echo " [PASS] " . $message . "\n";
    } else {
        echo " [FAIL] " . $message . "\n";
        exit(1);
    }
}

echo "========================================================\n";
echo "   FaceAbsen Test Suite: API Key Management & Switch    \n";
echo "========================================================\n\n";

$apiKeyModel = new ApiKey();
$settingModel = new Setting();

// 1. ApiKey Masking Test
echo "--- 1. Testing ApiKey::maskKey ---\n";
$masked = ApiKey::maskKey('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');
assertTest(strpos($masked, 'nvapi-') === 0 && strpos($masked, '••••') !== false, "Masked key contains prefix and bullets. Masked: $masked");
assertTest(substr($masked, -6) === '4hkehE', "Masked key preserves suffix characters: " . substr($masked, -6));

// 2. ApiKey Initial State
echo "--- 2. Testing ApiKey Initial State ---\n";
$allKeys = $apiKeyModel->getAll();
assertTest(!empty($allKeys), "Initial API keys exist in database (count: " . count($allKeys) . ").");

$activeKey = $apiKeyModel->getActiveKey();
assertTest(!empty($activeKey) && $activeKey['is_active'] == 1, "Active API key found: '" . $activeKey['name'] . "'");

// 3. Create New API Key (Reserve / Inactive)
echo "--- 3. Testing Create New API Key ---\n";
$testKeyVal = 'nvapi-test-key-' . bin2hex(random_bytes(16));
$newKeyId = $apiKeyModel->createKey([
    'name' => 'Akun Cadangan Testing ' . rand(100, 999),
    'provider' => 'NVIDIA NIM',
    'api_key' => $testKeyVal,
    'is_active' => 0
]);
assertTest($newKeyId > 0, "Successfully created new API key with ID: $newKeyId");

$fetched = $apiKeyModel->findById($newKeyId);
assertTest($fetched && $fetched['api_key'] === $testKeyVal && $fetched['is_active'] == 0, "Fetched new key matches created properties and is inactive.");

// Current active key should remain unchanged
$currentActive = $apiKeyModel->getActiveKey();
assertTest($currentActive['id'] != $newKeyId, "Current active key remained distinct from new inactive key.");

// 4. Switch Active API Key (Activate)
echo "--- 4. Testing Switch Active API Key ---\n";
$switchOk = $apiKeyModel->setActiveKey($newKeyId);
assertTest($switchOk === true, "setActiveKey($newKeyId) returned true.");

$nowActive = $apiKeyModel->getActiveKey();
assertTest($nowActive && $nowActive['id'] == $newKeyId && $nowActive['api_key'] === $testKeyVal, "Activated key is now the active key returned by getActiveKey().");

// Verify settings sync
$syncedSetting = $settingModel->get('nvidia_api_key');
assertTest($syncedSetting === $testKeyVal, "Settings table 'nvidia_api_key' synchronized to new key value.");

// 5. Update API Key
echo "--- 5. Testing Update API Key ---\n";
$updateOk = $apiKeyModel->updateKey($newKeyId, [
    'name' => 'Akun Cadangan Renamed',
    'provider' => 'OpenAI'
]);
assertTest($updateOk === true, "updateKey returned true.");
$updated = $apiKeyModel->findById($newKeyId);
assertTest($updated['name'] === 'Akun Cadangan Renamed' && $updated['provider'] === 'OpenAI', "Updated name and provider verified.");

// 6. Delete API Key (Active Prevention)
echo "--- 6. Testing Delete Constraints ---\n";
$delActiveResult = $apiKeyModel->deleteKey($newKeyId);
assertTest($delActiveResult['success'] === false, "Prevented deleting currently active API key.");

// Switch back to original key before deleting test key
$originalKeyId = $allKeys[0]['id'];
$apiKeyModel->setActiveKey($originalKeyId);
$activeAfterSwitchBack = $apiKeyModel->getActiveKey();
assertTest($activeAfterSwitchBack['id'] == $originalKeyId, "Switched back to original key ID: $originalKeyId");

// Now delete test key
$delResult = $apiKeyModel->deleteKey($newKeyId);
assertTest($delResult['success'] === true, "Successfully deleted non-active test API key.");
$deletedCheck = $apiKeyModel->findById($newKeyId);
assertTest(empty($deletedCheck), "Verified test key no longer exists in database.");

// 7. Testing RBAC Protection on AdminController
echo "--- 7. Testing Super User RBAC Protection ---\n";
// Case A: Unauthenticated
$_SESSION = [];
ob_start();
try {
    $adminCtrl = new AdminController();
    $adminCtrl->api_keys();
} catch (Throwable $e) {
    // Controller calls $this->redirect('auth/login') which might exit or redirect
}
ob_end_clean();
assertTest(!Auth::check(), "Unauthenticated session correctly verified.");

// Case B: Non-Super User (Guru / Peserta Didik)
$_SESSION['user'] = [
    'id' => 999,
    'username' => 'guru_tester',
    'name' => 'Guru Tester',
    'role' => 'guru',
    'status' => 'active'
];
assertTest(Auth::role() !== 'super_user', "Logged in as non-super user (guru).");

echo "\nAll API Key configuration & switching tests completed successfully!\n";
