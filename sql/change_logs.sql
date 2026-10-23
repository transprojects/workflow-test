-- Change log table (also auto-created by includes/change_log_mail.php on first use)
CREATE TABLE IF NOT EXISTS `change_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `module` VARCHAR(60) NOT NULL,
  `action` VARCHAR(10) NOT NULL DEFAULT 'EDIT',
  `record_id` VARCHAR(100) NOT NULL,
  `changed_by` INT NOT NULL DEFAULT 0,
  `changed_by_name` VARCHAR(150) NOT NULL DEFAULT '',
  `ip_address` VARCHAR(45) NOT NULL DEFAULT '',
  `changes_count` INT NOT NULL DEFAULT 0,
  `details` MEDIUMTEXT NULL,
  `mail_sent` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_module` (`module`),
  KEY `idx_action` (`action`),
  KEY `idx_changed_by` (`changed_by`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_record` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- If the table already exists (created before add/delete logging), add the action column:
-- ALTER TABLE `change_logs` ADD `action` VARCHAR(10) NOT NULL DEFAULT 'EDIT' AFTER `module`, ADD KEY `idx_action` (`action`);
