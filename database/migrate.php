<?php
/**
 * CLI Database Migration & Seeder Runner
 * Cara menjalankan: php database/migrate.php
 */

require_once __DIR__ . '/../config/config.php';

echo "========================================================\n";
echo "       FaceAbsen Database Migration & Seeder CLI       \n";
echo "========================================================\n";

try {
    // 1. Koneksi ke MySQL Server tanpa memilih database terlebih dahulu
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];

    $pdoServer = null;
    $pdo = null;

    $possibleSockets = [
        '/tmp/mariadb_run/mysql.sock',
        '/opt/lampp/var/mysql/mysql.sock',
        '/var/run/mysqld/mysqld.sock',
        '/tmp/mysql.sock'
    ];

    try {
        $pdoServer = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4", DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        $connected = false;
        foreach ($possibleSockets as $sock) {
            if (file_exists($sock)) {
                try {
                    $pdoServer = new PDO("mysql:unix_socket=" . $sock . ";charset=utf8mb4", DB_USER, DB_PASS, $options);
                    $connected = true;
                    break;
                } catch (PDOException $ex) {}
            }
        }
        if (!$connected) {
            throw $e;
        }
    }

    echo "[1/4] Membuat database '" . DB_NAME . "' jika belum ada...\n";
    $pdoServer->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "      Database siap.\n\n";

    // 2. Koneksi langsung ke database FaceAbsen
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        $connected = false;
        foreach ($possibleSockets as $sock) {
            if (file_exists($sock)) {
                try {
                    $pdo = new PDO("mysql:unix_socket=" . $sock . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, $options);
                    $connected = true;
                    break;
                } catch (PDOException $ex) {}
            }
        }
        if (!$connected) {
            throw $e;
        }
    }

    // 3. Menjalankan DDL Schema
    echo "[2/4] Menjalankan migrasi skema tabel (schema.sql)...\n";
    $schemaSql = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($schemaSql);
    echo "      Tabel berhasil dibuat: users, classes, students, teachers, attendances, settings.\n\n";

    // 4. Seeding Data
    echo "[3/4] Menjalankan seeding data awal (seed.sql & hash password)...\n";
    
    // Seed Classes
    $stmtClass = $pdo->prepare("INSERT INTO `classes` (`id`, `name`, `level`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `name` = VALUES(`name`)");
    $stmtClass->execute([1, 'X RPL 1', '10']);
    $stmtClass->execute([2, 'XI RPL 1', '11']);
    $stmtClass->execute([3, 'XII RPL 1', '12']);

    // Seed Users dengan BCRYPT Hash yang valid
    $passAdmin = password_hash('admin123', PASSWORD_BCRYPT);
    $passGuru = password_hash('guru123', PASSWORD_BCRYPT);
    $passSiswa = password_hash('siswa123', PASSWORD_BCRYPT);

    // Passwords for additional seeded accounts
    $passFirosy = password_hash('firosy123', PASSWORD_BCRYPT);
    $passDanish = password_hash('danish123', PASSWORD_BCRYPT);
    $passFajar  = password_hash('fajar123',  PASSWORD_BCRYPT);

    $stmtUser = $pdo->prepare("INSERT INTO `users` (`id`, `username`, `password`, `name`, `role`, `status`) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `password` = VALUES(`password`), `name` = VALUES(`name`)");
    $stmtUser->execute([1, 'admin', $passAdmin, 'Administrator FaceAbsen', 'super_user', 'active']);
    $stmtUser->execute([2, 'guru1', $passGuru, 'Budi Santoso, S.Kom', 'guru', 'active']);
    $stmtUser->execute([3, 'siswa1', $passSiswa, 'Ahmad Fauzi', 'peserta_didik', 'active']);

    // Additional requested users
    $stmtUser->execute([4, 'firosy', $passFirosy, 'Firosy', 'super_user', 'active']);
    $stmtUser->execute([5, 'danish', $passDanish, 'Danish', 'guru', 'active']);
    $stmtUser->execute([6, 'fajar',  $passFajar,  'Fajar',  'peserta_didik', 'active']);
    $stmtUser->execute([7, 'siswa_111111', $passSiswa, 'Muhammad Rizky', 'peserta_didik', 'active']);
    $stmtUser->execute([8, 'siswa_222222', $passSiswa, 'Siti Rahma', 'peserta_didik', 'active']);
    $stmtUser->execute([9, 'siswa_333333', $passSiswa, 'Budi Pratama', 'peserta_didik', 'active']);

    // Seed Teacher & Student
    $stmtTeacher = $pdo->prepare("INSERT INTO `teachers` (`id`, `user_id`, `nip`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `nip` = VALUES(`nip`)");
    $stmtTeacher->execute([1, 2, '198507122010011002']);

    // Teacher record for `danish` (user_id = 5)
    $stmtTeacher->execute([2, 5, '199001011234567890']);

    $stmtStudent = $pdo->prepare("INSERT INTO `students` (`id`, `user_id`, `nis`, `class_id`, `face_model_identifier`) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `nis` = VALUES(`nis`), `face_model_identifier` = VALUES(`face_model_identifier`)");
    $stmtStudent->execute([1, 3, '20241001', 1, 'siswa_ahmad_fauzi']);

    // Student record for `fajar` (user_id = 6)
    $stmtStudent->execute([2, 6, '20241002', 1, 'siswa_fajar']);

    // Students with NIS 111111, 222222, 333333
    $stmtStudent->execute([3, 7, '111111', 1, '111111']);
    $stmtStudent->execute([4, 8, '222222', 1, '222222']);
    $stmtStudent->execute([5, 9, '333333', 1, '333333']);

    // Seed Settings
    $settings = [
        'school_name' => 'SMK BINAWARGA LEMAHABANG',
        'attendance_start_time' => '06:30:00',
        'attendance_late_time' => '07:15:00',
        'attendance_end_time' => '16:00:00',
        'min_confidence_score' => '80.00',
        'teachable_machine_model_url' => 'http://localhost/projectrpl/public/my_model/',
        'nvidia_api_key' => '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'
    ];

    $stmtSetting = $pdo->prepare("INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");
    foreach ($settings as $k => $v) {
        $stmtSetting->execute([$k, $v]);
    }

    // Seed API Keys
    $stmtApiKey = $pdo->prepare("INSERT INTO `api_keys` (`id`, `name`, `provider`, `api_key`, `is_active`) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `api_key` = VALUES(`api_key`), `is_active` = VALUES(`is_active`)");
    $stmtApiKey->execute([1, 'Kunci Utama (Default NVIDIA NIM)', 'NVIDIA NIM', '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', 1]);

    echo "      Data awal & API Keys berhasil di-seed.\n\n";

    echo "[4/4] Verifikasi Tabel dan Akun Default:\n";
    echo "--------------------------------------------------------\n";
    echo " Akun Super User : username = admin    | password = admin123\n";
    echo " Akun Guru       : username = guru1    | password = guru123\n";
    echo " Akun Siswa      : username = siswa1   | password = siswa123\n";
    echo "--------------------------------------------------------\n";
    echo " Migrasi Database Berhasil 100% Selesai!\n";
    echo "========================================================\n";

} catch (Exception $e) {
    echo "\n[ERROR] Migrasi gagal: " . $e->getMessage() . "\n";
    exit(1);
}
