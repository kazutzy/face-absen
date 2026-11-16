-- ==============================================================================
-- FaceAbsen Database Seed Data
-- ==============================================================================

USE `faceabsen_db`;

-- 1. Seed Classes
INSERT INTO `classes` (`id`, `name`, `level`) VALUES
(1, 'X RPL 1', '10'),
(2, 'XI RPL 1', '11'),
(3, 'XII RPL 1', '12')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 2. Seed Users
-- Password hash untuk 'admin123', 'guru123', 'siswa123' (BCRYPT)
-- admin: $2y$10$eA8m910aZ8gYtD8N3zFk4eO25d7Hj8.O.Y1a/7f08bK6i1a8Z29eG (atau via migrate.php)
INSERT INTO `users` (`id`, `username`, `password`, `name`, `role`, `status`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator FaceAbsen', 'super_user', 'active'),
(2, 'guru1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Budi Santoso, S.Kom', 'guru', 'active'),
(3, 'siswa1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ahmad Fauzi', 'peserta_didik', 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Tambahan users sesuai permintaan: `firosy` (super_user), `danish` (guru), `fajar` (peserta_didik)
INSERT INTO `users` (`id`, `username`, `password`, `name`, `role`, `status`) VALUES
(4, 'firosy', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Firosy', 'super_user', 'active'),
(5, 'danish', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Danish', 'guru', 'active'),
(6, 'fajar',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Fajar',  'peserta_didik', 'active'),
(7, 'siswa_111111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Muhammad Rizky', 'peserta_didik', 'active'),
(8, 'siswa_222222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Siti Rahma', 'peserta_didik', 'active'),
(9, 'siswa_333333', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Budi Pratama', 'peserta_didik', 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Note: Hash di atas adalah bcrypt untuk 'admin123' / 'password', migrate.php akan memastikan hash admin123 secara native PHP.

-- 3. Seed Teacher
INSERT INTO `teachers` (`id`, `user_id`, `nip`) VALUES
(1, 2, '198507122010011002')
ON DUPLICATE KEY UPDATE `nip` = VALUES(`nip`);

-- Tambahan teacher untuk `danish` (user_id = 5)
INSERT INTO `teachers` (`id`, `user_id`, `nip`) VALUES
(2, 5, '199001011234567890')
ON DUPLICATE KEY UPDATE `nip` = VALUES(`nip`);

-- 4. Seed Student
INSERT INTO `students` (`id`, `user_id`, `nis`, `class_id`, `face_model_identifier`) VALUES
(1, 3, '20241001', 1, 'siswa_ahmad_fauzi')
ON DUPLICATE KEY UPDATE `nis` = VALUES(`nis`);

-- Tambahan student untuk `fajar` (user_id = 6)
INSERT INTO `students` (`id`, `user_id`, `nis`, `class_id`, `face_model_identifier`) VALUES
(2, 6, '20241002', 1, 'siswa_fajar')
ON DUPLICATE KEY UPDATE `nis` = VALUES(`nis`);

-- Siswa dengan NIS 111111, 222222, 333333 (face_model_identifier diset ke NIS)
INSERT INTO `students` (`id`, `user_id`, `nis`, `class_id`, `face_model_identifier`) VALUES
(3, 7, '111111', 1, '111111'),
(4, 8, '222222', 1, '222222'),
(5, 9, '333333', 1, '333333')
ON DUPLICATE KEY UPDATE `nis` = VALUES(`nis`), `face_model_identifier` = VALUES(`face_model_identifier`);

-- 5. Seed Settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('school_name', 'SMK BINAWARGA LEMAHABANG'),
('attendance_start_time', '06:30:00'),
('attendance_late_time', '07:15:00'),
('attendance_end_time', '16:00:00'),
('min_confidence_score', '80.00'),
('teachable_machine_model_url', 'http://localhost/projectrpl/public/my_model/'),
('nvidia_api_key', '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')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- 6. Seed API Keys
INSERT INTO `api_keys` (`id`, `name`, `provider`, `api_key`, `is_active`) VALUES
(1, 'Kunci Utama (Default NVIDIA NIM)', 'NVIDIA NIM', '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', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `api_key` = VALUES(`api_key`), `is_active` = VALUES(`is_active`);
