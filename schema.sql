-- این فایل را در phpMyAdmin (بخش Import یا SQL) اجرا کنید
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `teachers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `submissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `academic_year` SMALLINT UNSIGNED NOT NULL,
  `class_code` VARCHAR(20) NOT NULL,
  `exercise_title` VARCHAR(200) NULL,
  `code` MEDIUMTEXT NOT NULL,
  `notes` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_created` (`created_at`),
  KEY `idx_class` (`academic_year`,`class_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- حساب معلم (رمز عبور به صورت هش bcrypt ذخیره شده است)
INSERT INTO `teachers` (`username`, `password_hash`) VALUES
('SECAD', '$2y$12$RP3lS3hW7X9Ioa7iDEmH3ee4Xlk9NjbkwwW3oG6aBmjdLkMZFPwjS')
ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`);

-- تنظیمات سایت (مثل عنوان تمرین که معلم از داشبورد تغییر می‌دهد)
CREATE TABLE IF NOT EXISTS `settings` (
  `name` VARCHAR(50) NOT NULL,
  `value` TEXT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `settings` (`name`, `value`) VALUES ('exercise_title', 'تمرین ۱');
