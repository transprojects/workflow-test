-- QC tool log: emailed ZIP download links. Run once on the live `workflow` database.
-- (The pages also create these tables automatically on first use; this is for doing it up front.)

CREATE TABLE IF NOT EXISTS `qc_zip_jobs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `token` CHAR(40) NOT NULL,
  `created_by` VARCHAR(100) NOT NULL DEFAULT '',
  `created_by_name` VARCHAR(150) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `mode` VARCHAR(10) NOT NULL DEFAULT 'full',
  `recipients` TEXT NULL,
  `note` TEXT NULL,
  `items_json` MEDIUMTEXT NULL,
  `stats_json` TEXT NULL,
  `total` INT NOT NULL DEFAULT 0,
  `done` INT NOT NULL DEFAULT 0,
  `files` INT NOT NULL DEFAULT 0,
  `missing` INT NOT NULL DEFAULT 0,
  `status` VARCHAR(12) NOT NULL DEFAULT 'building',
  `zip_file` VARCHAR(255) NULL,
  `zip_size` BIGINT NULL,
  `mail_status` VARCHAR(10) NULL,
  `mail_error` TEXT NULL,
  `downloads` INT NOT NULL DEFAULT 0,
  `last_download` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `qc_zip_otp` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_id` INT UNSIGNED NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `code_hash` CHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `attempts` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `k_job` (`job_id`, `email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
