-- ==============================================================================
-- Migrasi: Tabel broadcast_messages & Pengaturan fonnte_api_key
-- ==============================================================================

USE `faceabsen_db`;

-- 1. Tabel Riwayat Pesan WhatsApp (Broadcast Messages / Outbox)
CREATE TABLE IF NOT EXISTS `broadcast_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sender_id` INT NOT NULL,
    `recipient_type` ENUM('all_teachers', 'all_students', 'class', 'specific') NOT NULL,
    `target_class_id` INT NULL,
    `target_phone` TEXT NOT NULL,
    `target_names` TEXT NULL,
    `total_recipients` INT NOT NULL DEFAULT 1,
    `message` TEXT NOT NULL,
    `status` ENUM('sent', 'failed', 'partial') NOT NULL DEFAULT 'sent',
    `fonnte_response` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_broadcast_sender` FOREIGN KEY (`sender_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_broadcast_class` FOREIGN KEY (`target_class_id`)
        REFERENCES `classes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX `idx_broadcast_sender` (`sender_id`),
    INDEX `idx_broadcast_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Konfigurasi Fonnte API Key pada tabel settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `created_at`, `updated_at`)
VALUES ('fonnte_api_key', 'bdvECGY1WDwuYNDFUg8H', NOW(), NOW())
ON DUPLICATE KEY UPDATE `setting_value` = 'bdvECGY1WDwuYNDFUg8H', `updated_at` = NOW();
