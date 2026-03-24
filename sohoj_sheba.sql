
SET NAMES utf8mb4;
SET time_zone = "+00:00";

-- 1) Create database
CREATE DATABASE `sohoj_sheba`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `sohoj_sheba`;

-- 2) Core tables

-- Users table (both user + worker accounts)
CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role` ENUM('user','worker') NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(32) NULL,
  `whatsapp` VARCHAR(32) NULL,
  `alternative_phone` VARCHAR(32) NULL,
  `country` VARCHAR(64) NULL,
  `city` VARCHAR(64) NULL,
  `area` VARCHAR(120) NULL,
  `postal_code` VARCHAR(24) NULL,
  `address` TEXT NULL,
  `date_of_birth` DATE NULL,
  `gender` ENUM('male','female','other','prefer-not-to-say') NULL,
  `preferred_language` VARCHAR(32) NULL,
  `referral_source` VARCHAR(64) NULL,
  `preferences_text` TEXT NULL,
  `newsletter_opt_in` TINYINT(1) NOT NULL DEFAULT 0,
  `terms_accepted_at` DATETIME NULL,
  `profile_photo_path` VARCHAR(255) NULL,
  `nid_photo_path` VARCHAR(255) NULL,
  `status` ENUM('active','blocked') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Extra worker-only fields (1:1 with users where role=worker)
CREATE TABLE IF NOT EXISTS `worker_profiles` (
  `user_id` BIGINT UNSIGNED NOT NULL,
  `experience` ENUM('less-than-1','1-2','3-5','5-10','more-than-10') NULL,
  `skills` TEXT NULL,
  `nid_number` VARCHAR(64) NULL,
  `trade_license` VARCHAR(64) NULL,
  `profile_photo_path` VARCHAR(255) NULL,
  `nid_photo_path` VARCHAR(255) NULL,
  `rating_avg` DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  `jobs_completed` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_worker_profiles_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Service catalog (matches your services.js slugs)
CREATE TABLE IF NOT EXISTS `services` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(64) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `base_price` INT UNSIGNED NOT NULL DEFAULT 0,
  `icon` VARCHAR(64) NULL,
  `color` VARCHAR(32) NULL,
  `short_desc` VARCHAR(255) NULL,
  `about` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_services_slug` (`slug`),
  KEY `idx_services_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Many-to-many: worker offers services
CREATE TABLE IF NOT EXISTS `worker_services` (
  `worker_user_id` BIGINT UNSIGNED NOT NULL,
  `service_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`worker_user_id`,`service_id`),
  KEY `idx_worker_services_service` (`service_id`),
  CONSTRAINT `fk_worker_services_worker`
    FOREIGN KEY (`worker_user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_worker_services_service`
    FOREIGN KEY (`service_id`) REFERENCES `services` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bookings (user creates; worker accepts; status changes over time)
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_code` VARCHAR(32) NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `service_id` BIGINT UNSIGNED NOT NULL,
  `worker_user_id` BIGINT UNSIGNED NULL,
  `status` ENUM('pending','accepted','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  `scheduled_at` DATETIME NULL,
  `address_text` TEXT NULL,
  `notes` TEXT NULL,
  `price` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_bookings_code` (`booking_code`),
  KEY `idx_bookings_user` (`user_id`),
  KEY `idx_bookings_worker` (`worker_user_id`),
  KEY `idx_bookings_service` (`service_id`),
  KEY `idx_bookings_status` (`status`),
  CONSTRAINT `fk_bookings_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_service`
    FOREIGN KEY (`service_id`) REFERENCES `services` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_worker`
    FOREIGN KEY (`worker_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: status history for audit trail
CREATE TABLE IF NOT EXISTS `booking_status_history` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` BIGINT UNSIGNED NOT NULL,
  `from_status` ENUM('pending','accepted','in_progress','completed','cancelled') NULL,
  `to_status` ENUM('pending','accepted','in_progress','completed','cancelled') NOT NULL,
  `changed_by_user_id` BIGINT UNSIGNED NULL,
  `note` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bsh_booking` (`booking_id`),
  KEY `idx_bsh_changed_by` (`changed_by_user_id`),
  CONSTRAINT `fk_bsh_booking`
    FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bsh_changed_by`
    FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: reviews (user reviews worker per booking)
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `worker_user_id` BIGINT UNSIGNED NOT NULL,
  `rating` TINYINT UNSIGNED NOT NULL,
  `comment` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reviews_booking` (`booking_id`),
  KEY `idx_reviews_worker` (`worker_user_id`),
  KEY `idx_reviews_user` (`user_id`),
  CONSTRAINT `chk_reviews_rating` CHECK (`rating` BETWEEN 1 AND 5),
  CONSTRAINT `fk_reviews_booking`
    FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reviews_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reviews_worker`
    FOREIGN KEY (`worker_user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: transactions/earnings ledger for workers
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `worker_user_id` BIGINT UNSIGNED NOT NULL,
  `booking_id` BIGINT UNSIGNED NULL,
  `type` ENUM('earning','withdrawal') NOT NULL,
  `amount` INT UNSIGNED NOT NULL,
  `status` ENUM('pending','completed','rejected') NOT NULL DEFAULT 'pending',
  `note` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_transactions_worker` (`worker_user_id`),
  KEY `idx_transactions_booking` (`booking_id`),
  KEY `idx_transactions_type` (`type`),
  KEY `idx_transactions_status` (`status`),
  CONSTRAINT `fk_transactions_worker`
    FOREIGN KEY (`worker_user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_transactions_booking`
    FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Seed services (aligned with services.js keys)
INSERT INTO `services` (`slug`, `name`, `base_price`, `icon`, `color`, `short_desc`, `about`, `is_active`)
VALUES
  ('carpenter',   'Carpenter',   500, 'fa-hammer',            '#2e7d32', 'Expert furniture repair, assembly & custom woodwork at your doorstep.', 'Our certified carpenters bring 5+ years of experience to every job.', 1),
  ('plumber',     'Plumber',     400, 'fa-faucet-drip',       '#1565c0', 'Leak fixing, pipe installation & complete bathroom solutions.',           'Professional plumbers who solve every water-related problem quickly.', 1),
  ('electrician', 'Electrician', 450, 'fa-bolt',              '#f59e0b', 'Wiring, fan/light installation & full electrical safety checks.',        'Certified electricians with safety-first approach.',                   1),
  ('mason',       'Mason',       600, 'fa-trowel-bricks',     '#8d5524', 'Brickwork, tiling, plastering & structural repairs.',                   'Skilled masons who build and repair with precision.',                 1),
  ('gardener',    'Gardener',    350, 'fa-seedling',          '#059669', 'Lawn care, plant maintenance & beautiful garden landscaping.',          'Passionate gardeners who turn your outdoor space into a green paradise.', 1),
  ('home-repair', 'Home Repair', 450, 'fa-house-circle-check','#7c3aed', 'All-in-one home maintenance & quick repair solutions.',                 'Your one-stop handyman service for home maintenance.',                1)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `base_price` = VALUES(`base_price`),
  `icon` = VALUES(`icon`),
  `color` = VALUES(`color`),
  `short_desc` = VALUES(`short_desc`),
  `about` = VALUES(`about`),
  `is_active` = VALUES(`is_active`),
  `updated_at` = CURRENT_TIMESTAMP;



ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `preferred_language` VARCHAR(32) NULL AFTER `gender`,
  ADD COLUMN IF NOT EXISTS `referral_source` VARCHAR(64) NULL AFTER `preferred_language`,
  ADD COLUMN IF NOT EXISTS `preferences_text` TEXT NULL AFTER `referral_source`,
  ADD COLUMN IF NOT EXISTS `newsletter_opt_in` TINYINT(1) NOT NULL DEFAULT 0 AFTER `preferences_text`,
  ADD COLUMN IF NOT EXISTS `terms_accepted_at` DATETIME NULL AFTER `newsletter_opt_in`,
  ADD COLUMN IF NOT EXISTS `profile_photo_path` VARCHAR(255) NULL AFTER `terms_accepted_at`,
  ADD COLUMN IF NOT EXISTS `nid_photo_path` VARCHAR(255) NULL AFTER `profile_photo_path`;