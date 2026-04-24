-- Migration: Add system_alerts and weekly_reports columns to user_settings table
-- Run this SQL to update your database schema

ALTER TABLE `user_settings`
ADD COLUMN IF NOT EXISTS `system_alerts` tinyint(1) DEFAULT 1,
ADD COLUMN IF NOT EXISTS `weekly_reports` tinyint(1) DEFAULT 1;

-- For MySQL versions that don't support IF NOT EXISTS, use:
-- ALTER TABLE `user_settings` ADD COLUMN `system_alerts` tinyint(1) DEFAULT 1;
-- ALTER TABLE `user_settings` ADD COLUMN `weekly_reports` tinyint(1) DEFAULT 1;
