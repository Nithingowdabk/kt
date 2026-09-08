-- Migration script for Simplified Hybrid Trek Scheduling System

USE `karnataka_trekkers`;

-- 1. Add recurring settings to treks table
ALTER TABLE `treks` 
  ADD COLUMN `recurring_friday` TINYINT(1) DEFAULT 0 AFTER `status`,
  ADD COLUMN `recurring_until` DATE DEFAULT NULL AFTER `recurring_friday`;

-- 2. Clean up status values and migrate them in trek_dates
UPDATE `trek_dates` SET `status` = 'Active' WHERE `status` IN ('Active', 'Full');
UPDATE `trek_dates` SET `status` = 'Disabled' WHERE `status` = 'Cancelled';

-- 3. Modify status column in trek_dates
ALTER TABLE `trek_dates` MODIFY COLUMN `status` ENUM('Active', 'Disabled') NOT NULL DEFAULT 'Active';

-- 4. Clean up scheduling_type values and migrate them
UPDATE `trek_dates` SET `scheduling_type` = 'Weekend' WHERE `scheduling_type` IS NULL;
UPDATE `trek_dates` SET `scheduling_type` = 'Weekend' WHERE `scheduling_type` NOT IN ('Weekend', 'Special');

-- 5. Rename scheduling_type to schedule_type and modify enum values to auto/custom
ALTER TABLE `trek_dates` CHANGE COLUMN `scheduling_type` `schedule_type` ENUM('auto', 'custom') NOT NULL DEFAULT 'auto';

-- 6. Map scheduling_type values to auto and custom
UPDATE `trek_dates` SET `schedule_type` = 'auto' WHERE `schedule_type` = 'Weekend';
UPDATE `trek_dates` SET `schedule_type` = 'custom' WHERE `schedule_type` = 'Special';

-- 7. Rename special_tag to label in trek_dates
ALTER TABLE `trek_dates` CHANGE COLUMN `special_tag` `label` VARCHAR(100) DEFAULT NULL;
