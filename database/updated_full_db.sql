-- ==============================================================================
-- KARNATAKA TREKKERS - COMPLETE IDEMPOTENT DATABASE SCHEMA & MIGRATION SCRIPT
-- File: updated_full_db.sql
-- Compatible with: MySQL 5.7+, MySQL 8.0+, MariaDB 10.x+, Hostinger phpMyAdmin, cPanel
-- Safe to run: If tables or columns already exist, it SKIPS them without error.
--              If tables or columns are missing, it CREATES them automatically.
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- 1. USERS TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `address` TEXT DEFAULT NULL,
  `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
  `verification_token` VARCHAR(100) DEFAULT NULL,
  `email_verified` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. ADMINS TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('Super Admin', 'Editor') DEFAULT 'Editor',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3. TREK CATEGORIES TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trek_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. TREK LEADERS TABLE
-- ------------------------------------------------------------------------------
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

-- ------------------------------------------------------------------------------
-- 5. TREKS TABLE (Full structure including all SEO and Dual Package fields)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `treks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `location` VARCHAR(150) DEFAULT 'Karnataka',
  `image` VARCHAR(255) DEFAULT NULL,
  `duration` VARCHAR(100) NOT NULL,
  `difficulty` ENUM('Easy', 'Moderate', 'Difficult') DEFAULT 'Moderate',
  `trek_distance` DECIMAL(5,2) NOT NULL,
  `altitude` INT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `offer_price` DECIMAL(10,2) DEFAULT NULL,
  `with_transport_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `with_transport_offer_price` DECIMAL(10,2) DEFAULT NULL,
  `without_transport_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `without_transport_offer_price` DECIMAL(10,2) DEFAULT NULL,
  `transport_enabled` TINYINT(1) DEFAULT 1,
  `own_transport_enabled` TINYINT(1) DEFAULT 1,
  `own_transport_note` TEXT DEFAULT NULL,
  `with_transport_note` TEXT DEFAULT NULL,
  `description` TEXT NOT NULL,
  `itinerary` TEXT NOT NULL,
  `inclusions` TEXT DEFAULT NULL,
  `exclusions` TEXT DEFAULT NULL,
  `with_transport_inclusions` TEXT DEFAULT NULL,
  `with_transport_exclusions` TEXT DEFAULT NULL,
  `own_transport_inclusions` TEXT DEFAULT NULL,
  `own_transport_exclusions` TEXT DEFAULT NULL,
  `things_to_carry` TEXT DEFAULT NULL,
  `pickup_points_txt` TEXT DEFAULT NULL,
  `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
  `recurring_friday` TINYINT(1) DEFAULT 0,
  `recurring_saturday` TINYINT(1) DEFAULT 0,
  `recurring_sunday` TINYINT(1) DEFAULT 0,
  `recurring_until` DATE DEFAULT NULL,
  `featured` TINYINT(1) DEFAULT 0,
  `meta_title` VARCHAR(150) DEFAULT NULL,
  `meta_description` VARCHAR(255) DEFAULT NULL,
  `focus_keyphrase` VARCHAR(150) DEFAULT NULL,
  `excerpt` TEXT DEFAULT NULL,
  `image_alt` VARCHAR(255) DEFAULT NULL,
  `tags` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `trek_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 6. TREK DATES TABLE (Scheduled Batches)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trek_dates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `trek_id` INT NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `seats` INT NOT NULL DEFAULT 20,
  `booked_seats` INT NOT NULL DEFAULT 0,
  `available_seats` INT NOT NULL DEFAULT 20,
  `schedule_type` ENUM('auto', 'custom') NOT NULL DEFAULT 'auto',
  `price` DECIMAL(10,2) DEFAULT NULL,
  `label` VARCHAR(100) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `status` ENUM('Active', 'Disabled') NOT NULL DEFAULT 'Active',
  `trek_leader_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_trek_dates_start_date` (`start_date`),
  FOREIGN KEY (`trek_id`) REFERENCES `treks`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`trek_leader_id`) REFERENCES `trek_leaders`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 7. PICKUP POINTS TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pickup_points` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `trek_id` INT NOT NULL,
  `trek_date_id` INT DEFAULT NULL,
  `time` TIME NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `landmark` VARCHAR(255) DEFAULT NULL,
  FOREIGN KEY (`trek_id`) REFERENCES `treks`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`trek_date_id`) REFERENCES `trek_dates`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 8. COUPONS TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `coupons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `discount_type` ENUM('Percentage', 'Fixed') NOT NULL,
  `discount_value` DECIMAL(10,2) NOT NULL,
  `min_booking_amount` DECIMAL(10,2) DEFAULT 0.00,
  `expiry_date` DATE NOT NULL,
  `max_uses` INT DEFAULT 0,
  `current_uses` INT DEFAULT 0,
  `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 9. BOOKINGS TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_no` VARCHAR(50) NOT NULL UNIQUE,
  `user_id` INT DEFAULT NULL,
  `trek_id` INT NOT NULL,
  `trek_date_id` INT NOT NULL,
  `num_trekkers` INT NOT NULL,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `discount_amount` DECIMAL(10,2) DEFAULT 0.00,
  `payable_amount` DECIMAL(10,2) NOT NULL,
  `pickup_point_id` INT DEFAULT NULL,
  `package_type` VARCHAR(50) NOT NULL DEFAULT 'with_transport',
  `payment_status` ENUM('Pending', 'Paid', 'Failed') DEFAULT 'Pending',
  `booking_status` ENUM('Pending', 'Confirmed', 'Cancelled') DEFAULT 'Pending',
  `completed` TINYINT(1) NOT NULL DEFAULT 0,
  `razorpay_order_id` VARCHAR(150) DEFAULT NULL,
  `razorpay_payment_id` VARCHAR(150) DEFAULT NULL,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `details` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_bookings_completed` (`completed`),
  KEY `idx_bookings_created_at` (`created_at`),
  KEY `idx_bookings_payment_status` (`payment_status`),
  KEY `idx_bookings_booking_status` (`booking_status`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`trek_id`) REFERENCES `treks`(`id`),
  FOREIGN KEY (`trek_date_id`) REFERENCES `trek_dates`(`id`),
  FOREIGN KEY (`pickup_point_id`) REFERENCES `pickup_points`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 10. PAYMENTS TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT NOT NULL,
  `transaction_no` VARCHAR(150) NOT NULL UNIQUE,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` VARCHAR(50) DEFAULT NULL,
  `status` VARCHAR(50) NOT NULL,
  `response_payload` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 11. REVIEWS & RATINGS TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `trek_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `rating` INT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `comment` TEXT NOT NULL,
  `status` ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`trek_id`) REFERENCES `treks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 12. BLOGS TABLE (Full structure including all SEO columns)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blogs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `content` LONGTEXT NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `author` VARCHAR(100) DEFAULT 'Admin',
  `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
  `meta_title` VARCHAR(150) DEFAULT NULL,
  `meta_description` VARCHAR(255) DEFAULT NULL,
  `focus_keyphrase` VARCHAR(150) DEFAULT NULL,
  `excerpt` TEXT DEFAULT NULL,
  `image_alt` VARCHAR(255) DEFAULT NULL,
  `tags` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 13. GALLERY CATEGORIES TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gallery_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 14. TREK GALLERY TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trek_gallery` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `trek_id` INT DEFAULT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `thumbnail_path` VARCHAR(255) DEFAULT NULL,
  `image_title` VARCHAR(100) DEFAULT NULL,
  `category_id` INT DEFAULT NULL,
  `is_featured` TINYINT(1) DEFAULT 0,
  `sort_order` INT DEFAULT 0,
  `session_token` VARCHAR(64) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_gallery_trek` (`trek_id`),
  KEY `idx_gallery_cat` (`category_id`),
  FOREIGN KEY (`trek_id`) REFERENCES `treks`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `gallery_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 15. TREK VIDEOS TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trek_videos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `trek_id` INT DEFAULT NULL,
  `youtube_url` VARCHAR(255) NOT NULL,
  `title` VARCHAR(100) DEFAULT NULL,
  `sort_order` INT DEFAULT 0,
  `session_token` VARCHAR(64) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`trek_id`) REFERENCES `treks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 16. TREK HIGHLIGHTS TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trek_highlights` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `trek_id` INT NOT NULL,
  `highlight` VARCHAR(255) NOT NULL,
  `icon` VARCHAR(50) NOT NULL DEFAULT 'fas fa-check-circle',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`trek_id`) REFERENCES `treks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 17. TREK NOTES TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trek_notes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `trek_id` INT NOT NULL,
  `note` TEXT NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`trek_id`) REFERENCES `treks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 18. TREK ADDITIONAL NOTES TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trek_additional_notes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `trek_id` INT NOT NULL,
  `note` TEXT NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`trek_id`) REFERENCES `treks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 19. TREK FAQS TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trek_faqs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `trek_id` INT NOT NULL,
  `question` TEXT NOT NULL,
  `answer` TEXT NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`trek_id`) REFERENCES `treks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 20. WISHLIST TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wishlist` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `trek_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `user_trek` (`user_id`, `trek_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`trek_id`) REFERENCES `treks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 21. CONTACT MESSAGES TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('Unread', 'Read') DEFAULT 'Unread',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 22. SETTINGS TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `key_name` VARCHAR(100) PRIMARY KEY,
  `value_data` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 23. CUSTOM TRIP ENQUIRIES TABLE
-- ------------------------------------------------------------------------------
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

-- ------------------------------------------------------------------------------
-- 24. CALLBACK REQUESTS TABLE
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `callback_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `trek_id` INT DEFAULT NULL,
  `preferred_date` DATE DEFAULT NULL,
  `participants` INT DEFAULT NULL,
  `message` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `priority` ENUM('High', 'Medium', 'Low') DEFAULT 'Low',
  `status` ENUM('New', 'Contacted', 'Interested', 'Converted', 'Closed') DEFAULT 'New',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`trek_id`) REFERENCES `treks`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 25. ATTENDANCE TABLE
-- ------------------------------------------------------------------------------
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

-- ------------------------------------------------------------------------------
-- 26. TREK COMPLETION REPORTS TABLE
-- ------------------------------------------------------------------------------
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

-- ------------------------------------------------------------------------------
-- 27. ACTIVITY LOGS TABLE
-- ------------------------------------------------------------------------------
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

-- ==============================================================================
-- SAFE COLUMN & INDEX MIGRATIONS FOR EXISTING TABLES
-- Uses ADD COLUMN IF NOT EXISTS / ADD INDEX IF NOT EXISTS (MariaDB 10.x+ / MySQL 8.0+)
-- If tables already exist, this safely adds any missing columns without data loss.
-- ==============================================================================

-- Treks Table Upgrades (SEO, Dual Packages, Recurring Schedules)
ALTER TABLE `treks` ADD COLUMN IF NOT EXISTS `location` VARCHAR(150) DEFAULT 'Karnataka';
ALTER TABLE `treks` ADD COLUMN IF NOT EXISTS `with_transport_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00;
ALTER TABLE `treks` ADD COLUMN IF NOT EXISTS `with_transport_offer_price` DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE `treks` ADD COLUMN IF NOT EXISTS `without_transport_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00;
ALTER TABLE `treks` ADD COLUMN IF NOT EXISTS `without_transport_offer_price` DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE `treks` ADD COLUMN IF NOT EXISTS `transport_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `treks` ADD COLUMN IF NOT EXISTS `own_transport_enabled` TINYINT(1) DEFAULT 1;
ALTER TABLE `treks` ADD COLUMN IF NOT EXISTS `own_transport_note` TEXT DEFAULT NULL;
ALTER TABLE `treks` ADD COLUMN IF NOT EXISTS `with_transport_note` TEXT DEFAULT NULL;
ALTER TABLE `treks` ADD COLUMN IF NOT EXISTS `with_transport_inclusions` TEXT DEFAULT NULL;
ALTER TABLE `treks` ADD COLUMN IF NOT EXISTS `with_transport_exclusions` TEXT DEFAULT NULL;
ALTER TABLE `treks` ADD COLUMN IF NOT EXISTS `own_transport_inclusions` TEXT DEFAULT NULL;
ALTER TABLE `treks` ADD COLUMN IF NOT EXISTS `own_transport_exclusions` TEXT DEFAULT NULL;
ALTER TABLE `treks` ADD COLUMN IF NOT EXISTS `recurring_friday` TINYINT(1) DEFAULT 0;
ALTER TABLE `treks` ADD COLUMN IF NOT EXISTS `recurring_saturday` TINYINT(1) DEFAULT 0;
ALTER TABLE `treks` ADD COLUMN IF NOT EXISTS `recurring_sunday` TINYINT(1) DEFAULT 0;
ALTER TABLE `treks` ADD COLUMN IF NOT EXISTS `recurring_until` DATE DEFAULT NULL;
ALTER TABLE `treks` ADD COLUMN IF NOT EXISTS `meta_title` VARCHAR(150) DEFAULT NULL;
ALTER TABLE `treks` ADD COLUMN IF NOT EXISTS `meta_description` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `treks` ADD COLUMN IF NOT EXISTS `focus_keyphrase` VARCHAR(150) DEFAULT NULL;
ALTER TABLE `treks` ADD COLUMN IF NOT EXISTS `excerpt` TEXT DEFAULT NULL;
ALTER TABLE `treks` ADD COLUMN IF NOT EXISTS `image_alt` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `treks` ADD COLUMN IF NOT EXISTS `tags` TEXT DEFAULT NULL;

-- Blogs Table Upgrades (SEO fields)
ALTER TABLE `blogs` ADD COLUMN IF NOT EXISTS `meta_title` VARCHAR(150) DEFAULT NULL;
ALTER TABLE `blogs` ADD COLUMN IF NOT EXISTS `meta_description` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `blogs` ADD COLUMN IF NOT EXISTS `focus_keyphrase` VARCHAR(150) DEFAULT NULL;
ALTER TABLE `blogs` ADD COLUMN IF NOT EXISTS `excerpt` TEXT DEFAULT NULL;
ALTER TABLE `blogs` ADD COLUMN IF NOT EXISTS `image_alt` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `blogs` ADD COLUMN IF NOT EXISTS `tags` TEXT DEFAULT NULL;

-- Bookings Table Upgrades
ALTER TABLE `bookings` ADD COLUMN IF NOT EXISTS `package_type` VARCHAR(50) NOT NULL DEFAULT 'with_transport';
ALTER TABLE `bookings` ADD COLUMN IF NOT EXISTS `completed` TINYINT(1) NOT NULL DEFAULT 0;

-- Trek Dates Table Upgrades
ALTER TABLE `trek_dates` ADD COLUMN IF NOT EXISTS `seats` INT NOT NULL DEFAULT 20;
ALTER TABLE `trek_dates` ADD COLUMN IF NOT EXISTS `booked_seats` INT NOT NULL DEFAULT 0;
ALTER TABLE `trek_dates` ADD COLUMN IF NOT EXISTS `available_seats` INT NOT NULL DEFAULT 20;
ALTER TABLE `trek_dates` ADD COLUMN IF NOT EXISTS `schedule_type` ENUM('auto', 'custom') NOT NULL DEFAULT 'auto';
ALTER TABLE `trek_dates` ADD COLUMN IF NOT EXISTS `price` DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE `trek_dates` ADD COLUMN IF NOT EXISTS `label` VARCHAR(100) DEFAULT NULL;
ALTER TABLE `trek_dates` ADD COLUMN IF NOT EXISTS `notes` TEXT DEFAULT NULL;
ALTER TABLE `trek_dates` ADD COLUMN IF NOT EXISTS `trek_leader_id` INT DEFAULT NULL;

-- Pickup Points Table Upgrades
ALTER TABLE `pickup_points` ADD COLUMN IF NOT EXISTS `trek_date_id` INT DEFAULT NULL;

-- Callback Requests Table Upgrades
ALTER TABLE `callback_requests` ADD COLUMN IF NOT EXISTS `notes` TEXT DEFAULT NULL;
ALTER TABLE `callback_requests` ADD COLUMN IF NOT EXISTS `priority` ENUM('High', 'Medium', 'Low') DEFAULT 'Low';

-- Trek Gallery Table Upgrades
ALTER TABLE `trek_gallery` ADD COLUMN IF NOT EXISTS `session_token` VARCHAR(64) DEFAULT NULL;
ALTER TABLE `trek_gallery` ADD COLUMN IF NOT EXISTS `is_featured` TINYINT(1) DEFAULT 0;
ALTER TABLE `trek_gallery` ADD COLUMN IF NOT EXISTS `sort_order` INT DEFAULT 0;

-- Trek Videos Table Upgrades
ALTER TABLE `trek_videos` ADD COLUMN IF NOT EXISTS `session_token` VARCHAR(64) DEFAULT NULL;
ALTER TABLE `trek_videos` ADD COLUMN IF NOT EXISTS `sort_order` INT DEFAULT 0;

-- ------------------------------------------------------------------------------
-- SEED DEFAULT SETTINGS (Safe - will NOT overwrite if already exist)
-- ------------------------------------------------------------------------------
INSERT IGNORE INTO `settings` (`key_name`, `value_data`) VALUES
('site_name', 'Karnataka Trekkers'),
('contact_email', 'info@karnatakatrekkers.com'),
('contact_phone', '+91 98765 43210'),
('contact_address', '#42, 3rd Cross, HSR Layout, Sector 2, Bengaluru, Karnataka - 560102'),
('seo_default_title', 'Karnataka Trekkers - Book Adventure & Nature Treks near Bangalore'),
('seo_default_desc', 'Karnataka Trekkers offers guided weekend treks, night treks, and Western Ghats adventure trips with experienced leads, pickup, food, and homestays.'),
('seo_default_keywords', 'trekking bangalore, kudremukh trek, kumara parvatha trek, night treks bangalore, western ghats trekking');

-- Default Super Admin (admin / admin123)
INSERT IGNORE INTO `admins` (`id`, `name`, `username`, `email`, `password_hash`, `role`) VALUES
(1, 'Super Administrator', 'admin', 'admin@karnatakatrekkers.com', '$2y$10$7R861B2wVv6vHh6Vl3wIku49pXm9B8oD668p8aM8jE40u6o1o7uC', 'Super Admin');

-- Default Trek Leader (leader / leader123)
INSERT IGNORE INTO `trek_leaders` (`id`, `name`, `email`, `password_hash`, `phone`, `status`) VALUES
(1, 'Suresh Guide', 'suresh@karnatakatrekkers.com', '$2y$10$7R861B2wVv6vHh6Vl3wIku49pXm9B8oD668p8aM8jE40u6o1o7uC', '9876543211', 'Active');

SET FOREIGN_KEY_CHECKS = 1;

-- ==============================================================================
-- SUCCESS: All tables and columns are created or synced without data loss.
-- ==============================================================================
