-- Database Schema Upgrade for Production
-- Compatible with MySQL 5.7+ / 8.0+ / Shared Hosting (Hostinger, cPanel, etc.)

-- 1. Create Settings Table for System Configurations
CREATE TABLE IF NOT EXISTS `settings` (
  `key_name` VARCHAR(100) PRIMARY KEY,
  `value_data` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings if they don't exist
INSERT IGNORE INTO `settings` (`key_name`, `value_data`) VALUES
('site_name', 'Karnataka Trekkers'),
('contact_email', 'info@karnatakatrekkers.com'),
('contact_phone', '+91 98765 43210'),
('contact_address', '#42, 3rd Cross, HSR Layout, Sector 2, Bengaluru, Karnataka - 560102'),
('razorpay_key', 'rzp_test_mockKeyId12345'),
('razorpay_secret', 'mockKeySecret67890abcdef'),
('razorpay_mode', 'TEST'),
('razorpay_webhook_secret', 'mockWebhookSecret12345'),
('whatsapp_url', 'https://api.chatprovider.com/send'),
('whatsapp_token', 'your_whatsapp_token_here'),
('whatsapp_enable', '1'),
('whatsapp_phone_number_id', '1234567890'),
('whatsapp_access_token', 'mock_meta_whatsapp_token'),
('whatsapp_template_booking_confirm', 'booking_confirmation'),
('whatsapp_template_payment_success', 'payment_success'),
('whatsapp_template_trek_reminder', 'trek_reminder'),
('seo_default_title', 'Karnataka Trekkers - Book Adventure & Nature Treks near Bangalore'),
('seo_default_desc', 'Karnataka Trekkers offers guided weekend treks, night treks, and Western Ghats adventure trips with experienced leads, pickup, food, and homestays.'),
('seo_default_keywords', 'trekking bangalore, kudremukh trek, kumara parvatha trek, night treks bangalore, western ghats trekking');

-- 2. Create Trek Leaders Table
CREATE TABLE IF NOT EXISTS `trek_leaders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed a default trek leader (password: leader123)
INSERT IGNORE INTO `trek_leaders` (`id`, `name`, `email`, `password_hash`, `phone`, `status`) VALUES
(1, 'Suresh Guide', 'suresh@karnatakatrekkers.com', '$2y$10$7R861B2wVv6vHh6Vl3wIku49pXm9B8oD668p8aM8jE40u6o1o7uC', '9876543211', 'Active');

-- 3. Upgrade Trek Dates Table (Adding total, booked, and available seats, and linking trek leader)
-- Check if available_slots exists, rename/upgrade it
SET @dbname = DATABASE();
SET @tablename = 'trek_dates';
SET @columnname = 'available_slots';
SET @preparedStatement = (SELECT IF(
  EXISTS(
    SELECT 1 FROM information_schema.columns 
    WHERE table_schema = @dbname AND table_name = @tablename AND column_name = @columnname
  ),
  'ALTER TABLE `trek_dates` 
    ADD COLUMN `total_seats` INT NOT NULL DEFAULT 20 AFTER `end_date`,
    ADD COLUMN `booked_seats` INT NOT NULL DEFAULT 0 AFTER `total_seats`,
    ADD COLUMN `available_seats` INT NOT NULL DEFAULT 20 AFTER `booked_seats`,
    ADD COLUMN `trek_leader_id` INT DEFAULT NULL AFTER `status`,
    ADD CONSTRAINT `fk_dates_trek_leader` FOREIGN KEY (`trek_leader_id`) REFERENCES `trek_leaders`(`id`) ON DELETE SET NULL',
  'SELECT "trek_dates columns already upgraded"'
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Migrate data from available_slots if they exist
SET @preparedStatementData = (SELECT IF(
  EXISTS(
    SELECT 1 FROM information_schema.columns 
    WHERE table_schema = @dbname AND table_name = @tablename AND column_name = @columnname
  ),
  'UPDATE `trek_dates` SET `total_seats` = `available_slots`, `available_seats` = `available_slots`',
  'SELECT "trek_dates data already migrated"'
));
PREPARE stmt FROM @preparedStatementData;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Drop available_slots if it exists
SET @preparedStatementDrop = (SELECT IF(
  EXISTS(
    SELECT 1 FROM information_schema.columns 
    WHERE table_schema = @dbname AND table_name = @tablename AND column_name = @columnname
  ),
  'ALTER TABLE `trek_dates` DROP COLUMN `available_slots`',
  'SELECT "available_slots already dropped"'
));
PREPARE stmt FROM @preparedStatementDrop;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Assign trek leader 1 to some trek dates
UPDATE `trek_dates` SET `trek_leader_id` = 1 WHERE `trek_leader_id` IS NULL;

-- 4. Upgrade Pickup Points Table to link specific Trek Dates
SET @columnname_pp = 'trek_date_id';
SET @preparedStatementPP = (SELECT IF(
  EXISTS(
    SELECT 1 FROM information_schema.columns 
    WHERE table_schema = @dbname AND table_name = 'pickup_points' AND column_name = @columnname_pp
  ),
  'SELECT "pickup_points already has trek_date_id"',
  'ALTER TABLE `pickup_points` 
    ADD COLUMN `trek_date_id` INT DEFAULT NULL AFTER `trek_id`,
    ADD CONSTRAINT `fk_pickups_trek_date` FOREIGN KEY (`trek_date_id`) REFERENCES `trek_dates`(`id`) ON DELETE CASCADE'
));
PREPARE stmt FROM @preparedStatementPP;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Assign pickup points to matching trek dates for data consistency
UPDATE `pickup_points` pp 
JOIN `trek_dates` td ON pp.trek_id = td.trek_id
SET pp.trek_date_id = td.id
WHERE pp.trek_date_id IS NULL;

-- 5. Create Attendance Table
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `trek_date_id` INT NOT NULL,
  `booking_id` INT NOT NULL,
  `traveler_name` VARCHAR(100) NOT NULL,
  `status` ENUM('Present', 'Absent') DEFAULT 'Present',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`trek_date_id`) REFERENCES `trek_dates`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Create Trek Completion Reports Table
CREATE TABLE IF NOT EXISTS `trek_completion_reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `trek_date_id` INT NOT NULL UNIQUE,
  `trek_leader_id` INT NOT NULL,
  `summary` TEXT NOT NULL,
  `weather_conditions` VARCHAR(100) DEFAULT NULL,
  `challenges_faced` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`trek_date_id`) REFERENCES `trek_dates`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`trek_leader_id`) REFERENCES `trek_leaders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Create Activity Logs Table
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `user_type` ENUM('Admin', 'Trek Leader', 'User', 'System') NOT NULL DEFAULT 'System',
  `username` VARCHAR(150) DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Redesign Trek Scheduling: Add type and notes, create custom_trip_enquiries table
ALTER TABLE `trek_dates` ADD COLUMN `scheduling_type` ENUM('Weekend', 'Special') NOT NULL DEFAULT 'Weekend';
ALTER TABLE `trek_dates` ADD COLUMN `special_tag` VARCHAR(100) DEFAULT NULL;
ALTER TABLE `trek_dates` ADD COLUMN `notes` TEXT DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `custom_trip_enquiries` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `trek_id` INT NOT NULL,
  `preferred_date` DATE NOT NULL,
  `num_participants` INT NOT NULL,
  `pickup_location` VARCHAR(255) NOT NULL,
  `special_requirements` TEXT DEFAULT NULL,
  `status` ENUM('Pending', 'Reviewed', 'Cancelled') DEFAULT 'Pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`trek_id`) REFERENCES `treks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Create callback_requests table
CREATE TABLE IF NOT EXISTS `callback_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `trek_id` INT DEFAULT NULL,
  `preferred_date` DATE DEFAULT NULL,
  `participants` INT DEFAULT NULL,
  `message` TEXT DEFAULT NULL,
  `status` ENUM('New', 'Contacted', 'Interested', 'Converted', 'Closed') DEFAULT 'New',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`trek_id`) REFERENCES `treks`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Upgrade callback_requests for CRM (Add notes and priority columns)
ALTER TABLE `callback_requests` ADD COLUMN `notes` TEXT DEFAULT NULL;
ALTER TABLE `callback_requests` ADD COLUMN `priority` ENUM('High', 'Medium', 'Low') DEFAULT 'Low';

-- 11. Audit Cleanup: Drop redundant empty gallery table if exists
DROP TABLE IF EXISTS `gallery`;
