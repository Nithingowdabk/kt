-- Remove or comment out USE statement for Hostinger / shared hosting compatibility
-- USE `karnataka_trekkers`;

-- 1. Add package-specific inclusions, exclusions, and booking notes columns
ALTER TABLE `treks` 
  ADD COLUMN `with_transport_inclusions` TEXT DEFAULT NULL AFTER `exclusions`,
  ADD COLUMN `with_transport_exclusions` TEXT DEFAULT NULL AFTER `with_transport_inclusions`,
  ADD COLUMN `own_transport_inclusions` TEXT DEFAULT NULL AFTER `with_transport_exclusions`,
  ADD COLUMN `own_transport_exclusions` TEXT DEFAULT NULL AFTER `own_transport_inclusions`,
  ADD COLUMN `with_transport_note` TEXT DEFAULT NULL AFTER `own_transport_note`;

-- 2. Migrate existing inclusions & exclusions to the new fields
UPDATE `treks` SET 
  `with_transport_inclusions` = `inclusions`,
  `with_transport_exclusions` = `exclusions`,
  `own_transport_inclusions` = `inclusions`,
  `own_transport_exclusions` = `exclusions`;

-- 3. Create trek_additional_notes table
CREATE TABLE IF NOT EXISTS `trek_additional_notes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `trek_id` INT NOT NULL,
  `note` TEXT NOT NULL,
  `sort_order` INT NOT NULL,
  FOREIGN KEY (`trek_id`) REFERENCES `treks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
