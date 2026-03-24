-- Align `users` with sohoj_sheba.sql (commit): photo paths on user row
USE `sohoj_sheba`;

-- MariaDB 10.5.2+ supports IF NOT EXISTS on ADD COLUMN
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `profile_photo_path` VARCHAR(255) NULL AFTER `terms_accepted_at`,
  ADD COLUMN IF NOT EXISTS `nid_photo_path` VARCHAR(255) NULL AFTER `profile_photo_path`;
