-- Database Schema for Karnataka Trekkers
-- Compatible with MySQL 5.7+ / 8.0+ / Shared Hosting (Hostinger, cPanel, etc.)

-- NOTE: On shared hosting (like Hostinger phpMyAdmin), do NOT run CREATE DATABASE or USE statements.
-- Select your database (e.g. u994480942_KT) in phpMyAdmin and run the queries below:

-- 1. Users Table
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

-- 2. Admins Table
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

-- 3. Trek Categories
CREATE TABLE IF NOT EXISTS `trek_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Treks Table
CREATE TABLE IF NOT EXISTS `treks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `duration` VARCHAR(100) NOT NULL, -- e.g. "2 Days / 1 Night"
  `difficulty` ENUM('Easy', 'Moderate', 'Difficult') DEFAULT 'Moderate',
  `trek_distance` DECIMAL(5,2) NOT NULL, -- in km
  `altitude` INT NOT NULL, -- in ft
  `price` DECIMAL(10,2) NOT NULL,
  `offer_price` DECIMAL(10,2) DEFAULT NULL,
  `description` TEXT NOT NULL,
  `itinerary` TEXT NOT NULL, -- Can store JSON or text structure
  `inclusions` TEXT DEFAULT NULL,
  `exclusions` TEXT DEFAULT NULL,
  `things_to_carry` TEXT DEFAULT NULL,
  `pickup_points_txt` TEXT DEFAULT NULL, -- general description of pickup points
  `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
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

-- 5. Trek Dates Table (Scheduled Batches)
CREATE TABLE IF NOT EXISTS `trek_dates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `trek_id` INT NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `available_slots` INT NOT NULL,
  `status` ENUM('Active', 'Full', 'Cancelled') DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`trek_id`) REFERENCES `treks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Pickup Points Table
CREATE TABLE IF NOT EXISTS `pickup_points` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `trek_id` INT NOT NULL,
  `time` TIME NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `landmark` VARCHAR(255) DEFAULT NULL,
  FOREIGN KEY (`trek_id`) REFERENCES `treks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Coupons Table
CREATE TABLE IF NOT EXISTS `coupons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `discount_type` ENUM('Percentage', 'Fixed') NOT NULL,
  `discount_value` DECIMAL(10,2) NOT NULL,
  `min_booking_amount` DECIMAL(10,2) DEFAULT 0.00,
  `expiry_date` DATE NOT NULL,
  `max_uses` INT DEFAULT 0, -- 0 for unlimited
  `current_uses` INT DEFAULT 0,
  `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Bookings Table
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
  `payment_status` ENUM('Pending', 'Paid', 'Failed') DEFAULT 'Pending',
  `booking_status` ENUM('Pending', 'Confirmed', 'Cancelled') DEFAULT 'Pending',
  `razorpay_order_id` VARCHAR(150) DEFAULT NULL,
  `razorpay_payment_id` VARCHAR(150) DEFAULT NULL,
  `name` VARCHAR(100) NOT NULL, -- Primary traveler details
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `details` TEXT NOT NULL, -- JSON array of other travelers' details (name, age, gender)
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`trek_id`) REFERENCES `treks`(`id`),
  FOREIGN KEY (`trek_date_id`) REFERENCES `trek_dates`(`id`),
  FOREIGN KEY (`pickup_point_id`) REFERENCES `pickup_points`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Payments Table
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

-- 10. Reviews & Ratings Table
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

-- 11. Blogs Table
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

-- 12. Gallery Table
CREATE TABLE IF NOT EXISTS `gallery` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `trek_id` INT DEFAULT NULL, -- Can associate with a specific trek
  `image_path` VARCHAR(255) NOT NULL,
  `caption` VARCHAR(255) DEFAULT NULL,
  `type` ENUM('Image', 'Video') DEFAULT 'Image',
  `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`trek_id`) REFERENCES `treks`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Wishlist Table
CREATE TABLE IF NOT EXISTS `wishlist` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `trek_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `user_trek` (`user_id`, `trek_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`trek_id`) REFERENCES `treks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Contact Messages Table
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

-- 15. Activity Logs Table
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

