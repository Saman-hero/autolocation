-- Add image column for vehicles
-- Run this in phpMyAdmin if the column doesn't exist
ALTER TABLE `vehicles` ADD COLUMN `image` VARCHAR(255) DEFAULT NULL AFTER `couleur`;
