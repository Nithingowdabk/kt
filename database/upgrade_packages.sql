-- Upgrade database schema for Dual Package Booking System
-- Compatible with MySQL 5.7+ / 8.0+

USE `karnataka_trekkers`;

-- Add columns to treks table
ALTER TABLE `treks`
  ADD COLUMN `with_transport_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `offer_price`,
  ADD COLUMN `with_transport_offer_price` DECIMAL(10,2) DEFAULT NULL AFTER `with_transport_price`,
  ADD COLUMN `without_transport_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `with_transport_offer_price`,
  ADD COLUMN `without_transport_offer_price` DECIMAL(10,2) DEFAULT NULL AFTER `without_transport_price`,
  ADD COLUMN `transport_enabled` TINYINT(1) DEFAULT 1 AFTER `without_transport_offer_price`,
  ADD COLUMN `own_transport_enabled` TINYINT(1) DEFAULT 1 AFTER `transport_enabled`,
  ADD COLUMN `own_transport_note` TEXT DEFAULT NULL AFTER `own_transport_enabled`;

-- Migrate existing prices to with_transport fields
UPDATE `treks` SET 
  `with_transport_price` = `price`, 
  `with_transport_offer_price` = `offer_price`,
  `without_transport_price` = `price` * 0.6, -- Default fallback: own transport is 40% cheaper
  `without_transport_offer_price` = `offer_price` * 0.6;

-- Add package_type to bookings table
ALTER TABLE `bookings`
  ADD COLUMN `package_type` VARCHAR(50) NOT NULL DEFAULT 'with_transport' AFTER `pickup_point_id`;
