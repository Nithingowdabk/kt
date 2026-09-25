-- Karnataka Trekkers - Dynamic Trek System Architecture & SEO Migration
-- Compatible with MySQL 5.7+ / 8.0+ / MariaDB / Hostinger phpMyAdmin

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Create table for automatic 301 redirects on slug changes
CREATE TABLE IF NOT EXISTS `trek_slug_redirects` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `trek_id` INT NULL,
  `old_slug` VARCHAR(255) NOT NULL,
  `new_slug` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_old_slug` (`old_slug`),
  KEY `idx_trek_id` (`trek_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Add starting_point and ensure best_season columns exist in treks
ALTER TABLE `treks` ADD COLUMN IF NOT EXISTS `starting_point` VARCHAR(150) DEFAULT NULL AFTER `location`;
ALTER TABLE `treks` ADD COLUMN IF NOT EXISTS `best_season` VARCHAR(100) DEFAULT NULL AFTER `altitude`;
ALTER TABLE `treks` ADD COLUMN IF NOT EXISTS `is_indexed` TINYINT(1) NOT NULL DEFAULT 1 AFTER `status`;

-- 2b. Expand SEO column capacities to support extended lengths
ALTER TABLE `treks` MODIFY COLUMN `meta_title` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `treks` MODIFY COLUMN `meta_description` TEXT DEFAULT NULL;
ALTER TABLE `treks` MODIFY COLUMN `focus_keyphrase` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `blogs` MODIFY COLUMN `meta_title` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `blogs` MODIFY COLUMN `meta_description` TEXT DEFAULT NULL;
ALTER TABLE `blogs` MODIFY COLUMN `focus_keyphrase` VARCHAR(255) DEFAULT NULL;

-- 2c. Clear orphaned or expired batch associations on pickup points so they apply across all trek dates
UPDATE `pickup_points` pp 
LEFT JOIN `trek_dates` td ON pp.trek_date_id = td.id 
SET pp.trek_date_id = NULL 
WHERE pp.trek_date_id IS NOT NULL AND (td.id IS NULL OR td.start_date < CURDATE());

-- 3. Seed initial 301 slug redirects for known typos
INSERT IGNORE INTO `trek_slug_redirects` (`old_slug`, `new_slug`) VALUES
('shivagange-sunrise-trek-from-baangalore', 'shivagange-sunrise-trek-from-bangalore'),
('netravathi-trek-from-banaglore', 'netravathi-trek-from-bangalore');

-- 4. Correct known typo records in treks table if they exist
UPDATE `treks` 
SET 
  `title` = 'Shivagange Sunrise Trek From Bangalore',
  `slug` = 'shivagange-sunrise-trek-from-bangalore'
WHERE `slug` = 'shivagange-sunrise-trek-from-baangalore' OR `title` LIKE '%Shivagange%Baangalore%';

UPDATE `treks` 
SET 
  `title` = 'Netravathi Trek From Bangalore',
  `slug` = 'netravathi-trek-from-bangalore'
WHERE `slug` = 'netravathi-trek-from-banaglore' OR `title` LIKE '%Netravathi%Banaglore%';

UPDATE `treks` 
SET 
  `pickup_points_txt` = 'Opposite Gopalan Arcade'
WHERE `pickup_points_txt` LIKE '%Oppsite%';

SET FOREIGN_KEY_CHECKS = 1;
