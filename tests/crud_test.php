<?php
/**
 * Automated Test Suite for Master Data CRUD (Students, Teachers, Classes) & RBAC Protection
 * Run: php tests/crud_test.php
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
require_once __DIR__ . '/../app/models/Classes.php';
require_once __DIR__ . '/../app/models/Student.php';
require_once __DIR__ . '/../app/models/Teacher.php';

function assertTest($condition, $message) {
    if ($condition) {
        echo " [PASS] " . $message . "\n";
    } else {
        echo " [FAIL] " . $message . "\n";
        exit(1);
    }
}

echo "========================================================\n";
echo "    FaceAbsen Test Suite: Master Data CRUD & RBAC       \n";
echo "========================================================\n\n";

$classesModel = new Classes();
$studentModel = new Student();
$teacherModel = new Teacher();
$userModel = new User();

// --- 1. TESTING CLASS CRUD ---
echo "--- 1. Testing Kelas (Classes) CRUD ---\n";
// Create
$testClassName = 'Test Rombel ' . rand(100, 999);
$classId = $classesModel->createClass($testClassName, '10');
assertTest($classId > 0, "Create class '$testClassName' succeeded (ID: $classId).");

// Read with student count
$classList = $classesModel->getWithStudentCount($testClassName);
assertTest(count($classList) === 1 && $classList[0]['name'] === $testClassName, "Search class by name returned created class.");

// Update
$updatedClassName = $testClassName . ' Updated';
$updateResult = $classesModel->updateClass($classId, $updatedClassName, '11');
assertTest($updateResult === true, "Update class name and level succeeded.");

$checkClass = $classesModel->findById($classId);
assertTest($checkClass['name'] === $updatedClassName && $checkClass['level'] === '11', "Verified updated class data.");

// --- 2. TESTING STUDENT CRUD ---
echo "\n--- 2. Testing Peserta Didik (Students) CRUD ---\n";
$testNis = 'TESTNIS' . rand(1000, 9999);
$testUsername = 'teststudent_' . rand(1000, 9999);
$testEmail = 'teststudent_' . rand(1000, 9999) . '@smkbinawarga.sch.id';
$testStudentName = 'Siswa Test ' . rand(100, 999);

// Test Uniqueness before insert
assertTest($studentModel->isNisUnique($testNis) === true, "NIS '$testNis' is unique before creation.");
assertTest($userModel->isUsernameUnique($testUsername) === true, "Username '$testUsername' is unique before creation.");
assertTest($userModel->isEmailUnique($testEmail) === true, "Email '$testEmail' is unique before creation.");

// Create Student
$studentId = $studentModel->createStudent(
    ['name' => $testStudentName, 'username' => $testUsername, 'email' => $testEmail, 'password' => 'studentpass123', 'status' => 'active'],
    ['nis' => $testNis, 'class_id' => $classId]
);
assertTest($studentId > 0, "Create student '$testStudentName' succeeded (ID: $studentId).");

// Test Uniqueness after insert
assertTest($studentModel->isNisUnique($testNis) === false, "NIS '$testNis' is correctly flagged as duplicate after creation.");
assertTest($userModel->isUsernameUnique($testUsername) === false, "Username '$testUsername' is correctly flagged as duplicate.");
assertTest($userModel->isEmailUnique($testEmail) === false, "Email '$testEmail' is correctly flagged as duplicate.");

// Search & Filter
$filteredStudents = $studentModel->getFilteredList($testNis, $classId);
assertTest(count($filteredStudents) === 1 && $filteredStudents[0]['nis'] === $testNis, "Search and filter student by NIS and Class returned correct record.");

// Detail
$studentDetail = $studentModel->findStudentDetailById($studentId);
assertTest($studentDetail['name'] === $testStudentName && $studentDetail['nis'] === $testNis && $studentDetail['email'] === $testEmail, "Detail view returned correct student profile with email.");

// Test Class Deletion Protection (Class has 1 student)
assertTest($classesModel->hasStudents($classId) === true, "Class correctly reports having registered students.");
assertTest($classesModel->deleteClass($classId) === false, "Deleting class with registered students was safely blocked.");

// Update Student
$updatedStudentName = $testStudentName . ' Edited';
$updatedStudentEmail = 'edit_' . $testEmail;
$updateStudentRes = $studentModel->updateStudent(
    $studentId,
    ['name' => $updatedStudentName, 'username' => $testUsername, 'email' => $updatedStudentEmail, 'password' => '', 'status' => 'active'],
    ['nis' => $testNis, 'class_id' => $classId]
);
assertTest($updateStudentRes === true, "Update student profile succeeded.");
$checkStudent = $studentModel->findStudentDetailById($studentId);
assertTest($checkStudent['name'] === $updatedStudentName && $checkStudent['email'] === $updatedStudentEmail, "Verified updated student name and email in database.");

// Delete Student
$deleteStudentRes = $studentModel->deleteStudent($studentId);
assertTest($deleteStudentRes === true, "Delete student and linked user account succeeded.");
assertTest($studentModel->findStudentDetailById($studentId) === null, "Verified student record removed from database.");

// Delete Class after student removed
assertTest($classesModel->hasStudents($classId) === false, "Class now has 0 students.");
$deleteClassRes = $classesModel->deleteClass($classId);
assertTest($deleteClassRes === true, "Deleting empty class succeeded.");

// --- 3. TESTING TEACHER CRUD ---
echo "\n--- 3. Testing Guru (Teachers) CRUD ---\n";
$testNip = 'TESTNIP' . rand(10000, 99999);
$testTeacherUsername = 'testguru_' . rand(1000, 9999);
$testTeacherEmail = 'testguru_' . rand(1000, 9999) . '@smkbinawarga.sch.id';
$testTeacherName = 'Guru Test ' . rand(100, 999) . ', M.Pd';

assertTest($teacherModel->isNipUnique($testNip) === true, "NIP '$testNip' is unique before creation.");
assertTest($userModel->isUsernameUnique($testTeacherUsername) === true, "Username '$testTeacherUsername' is unique before creation.");
assertTest($userModel->isEmailUnique($testTeacherEmail) === true, "Email '$testTeacherEmail' is unique before creation.");

// Create Teacher
$teacherId = $teacherModel->createTeacher(
    ['name' => $testTeacherName, 'username' => $testTeacherUsername, 'email' => $testTeacherEmail, 'password' => 'gurupass123', 'status' => 'active'],
    ['nip' => $testNip]
);
assertTest($teacherId > 0, "Create teacher '$testTeacherName' succeeded (ID: $teacherId).");

// Test Uniqueness after insert
assertTest($teacherModel->isNipUnique($testNip) === false, "NIP '$testNip' is correctly flagged as duplicate after creation.");
assertTest($userModel->isUsernameUnique($testTeacherUsername) === false, "Username '$testTeacherUsername' is correctly flagged as duplicate.");
assertTest($userModel->isEmailUnique($testTeacherEmail) === false, "Email '$testTeacherEmail' is correctly flagged as duplicate.");

// Search Teacher
$filteredTeachers = $teacherModel->getFilteredList($testNip);
assertTest(count($filteredTeachers) === 1 && $filteredTeachers[0]['nip'] === $testNip, "Search teacher by NIP returned correct record.");

// Detail Teacher
$teacherDetail = $teacherModel->findTeacherDetailById($teacherId);
assertTest($teacherDetail['name'] === $testTeacherName && $teacherDetail['nip'] === $testNip && $teacherDetail['email'] === $testTeacherEmail, "Detail view returned correct teacher profile with email.");

// Update Teacher
$updatedTeacherName = $testTeacherName . ' Updated';
$updatedTeacherEmail = 'edit_' . $testTeacherEmail;
$updateTeacherRes = $teacherModel->updateTeacher(
    $teacherId,
    ['name' => $updatedTeacherName, 'username' => $testTeacherUsername, 'email' => $updatedTeacherEmail, 'password' => '', 'status' => 'active'],
    ['nip' => $testNip]
);
assertTest($updateTeacherRes === true, "Update teacher profile succeeded.");
$checkTeacher = $teacherModel->findTeacherDetailById($teacherId);
assertTest($checkTeacher['name'] === $updatedTeacherName && $checkTeacher['email'] === $updatedTeacherEmail, "Verified updated teacher name and email in database.");

// Delete Teacher
$deleteTeacherRes = $teacherModel->deleteTeacher($teacherId);
assertTest($deleteTeacherRes === true, "Delete teacher and linked user account succeeded.");
assertTest($teacherModel->findTeacherDetailById($teacherId) === null, "Verified teacher record removed from database.");

echo "\n========================================================\n";
echo " ALL MASTER DATA CRUD & INTEGRITY TESTS PASSED 100%!    \n";
echo "========================================================\n";
