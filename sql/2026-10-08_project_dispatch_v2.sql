-- Project Dispatch (Send to Client) v2 - database changes
-- Date: 2026-10-08
--
-- Safe to run more than once: every statement checks first.
-- The pages (send_to_client_v2.php, dispatch_log.php) create the same tables / columns themselves on first use,
-- so this file is for doing it up front (e.g. when the web user has no ALTER / CREATE rights).
-- Works on MySQL 5.7 / 8 and MariaDB (no "ADD COLUMN IF NOT EXISTS", which MySQL lacks).
-- Run it in the workflow database.

-- 1. Dispatch log: one row per Send from send_to_client_v2.php
CREATE TABLE IF NOT EXISTS `inw_dispatch_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `a_id` int(11) DEFAULT NULL,
  `pub_id` varchar(100) DEFAULT NULL,
  `cust_id` int(11) DEFAULT NULL,
  `j_id` int(11) DEFAULT NULL,
  `stage` varchar(25) DEFAULT NULL,
  `method` varchar(60) DEFAULT NULL,
  `user_id` varchar(20) DEFAULT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `mail_from` varchar(255) DEFAULT NULL,
  `mail_to` text,
  `mail_cc` text,
  `mail_bcc` text,
  `subject` text,
  `files` mediumtext,
  `confirm_info` mediumtext,
  `steps` mediumtext,
  `status` varchar(15) DEFAULT NULL,
  `result_message` text,
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `duration_ms` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `a_id` (`a_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Snapshot of the whole Delivery folder at dispatch time (files, sizes, modified times, zip contents)
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inw_dispatch_log' AND COLUMN_NAME = 'folder') = 0,
              'ALTER TABLE `inw_dispatch_log` ADD COLUMN `folder` MEDIUMTEXT NULL',
              'SELECT ''inw_dispatch_log.folder already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2b. Did the dispatch report e-mail go out (Sent / Not sent - no recipients configured / Failed ...)
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inw_dispatch_log' AND COLUMN_NAME = 'report_mail') = 0,
              'ALTER TABLE `inw_dispatch_log` ADD COLUMN `report_mail` VARCHAR(255) NULL',
              'SELECT ''inw_dispatch_log.report_mail already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2c. Recipients of the dispatch report e-mail (set them on the Email Alerts and Logs screen, card "Project Dispatch: dispatch report").
--     Until at least one address is saved there, no report is sent (the log records "Not sent - no recipients configured").
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wf_settings' AND COLUMN_NAME = 'sw_disp_to') = 0,
              'ALTER TABLE `wf_settings` ADD COLUMN `sw_disp_to` TEXT NULL, ADD COLUMN `sw_disp_cc` TEXT NULL, ADD COLUMN `sw_disp_bcc` TEXT NULL',
              'SELECT ''wf_settings.sw_disp_* already exist''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. "Use old version" log is shared with Start Work: remember which page the switch came from
--    (existing rows count as Start Work). Needs the sw_switch_log table that Start Work already created.
SET @sql = IF((SELECT COUNT(*) FROM information_schema.TABLES
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sw_switch_log') = 0,
              'SELECT ''sw_switch_log does not exist yet - it is created the first time Start Work "Use old version" is used; run this step again after that''',
              IF((SELECT COUNT(*) FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sw_switch_log' AND COLUMN_NAME = 'page') = 0,
                 'ALTER TABLE `sw_switch_log` ADD COLUMN `page` VARCHAR(30) NOT NULL DEFAULT ''start_work''',
                 'SELECT ''sw_switch_log.page already exists'''));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. Menu item: Reports > Dispatch Log  (page: /dispatchlog)
INSERT INTO `wf_smenu` (`menu_id`, `smenu_type`, `smenu_order`, `smenu_item`, `smenu_title`, `smenu_class`, `smenu_link`, `smenu_link_demo`, `smenu_status`, `smenu_show`)
SELECT 7, 'ADMIN', 9, 'Dispatch Log', 'Dispatch Log', '', 'dispatchlog', 'dispatchlog', 'Active', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `wf_smenu` WHERE `smenu_link` = 'dispatchlog');

-- 5. Who sees the menu item: a role sees only the items listed in user_role_master.smenu_id, and users get that list when they log in.
--    Add the new item to the roles that should see it (here: Portal Admin = 1). Other roles (Manager = 2, Team Leader = 7, ...): change the id,
--    or tick "Dispatch Log" under Reports in the Edit Role screen. Users must log out and in again afterwards.
UPDATE `user_role_master`
SET `smenu_id` = CONCAT(`smenu_id`, ',', (SELECT `smenu_id` FROM `wf_smenu` WHERE `smenu_link` = 'dispatchlog' LIMIT 1))
WHERE `user_role_id` = 1
  AND `smenu_id` <> ''
  AND FIND_IN_SET((SELECT `smenu_id` FROM `wf_smenu` WHERE `smenu_link` = 'dispatchlog' LIMIT 1), `smenu_id`) = 0;

-- 6. Move "Change Logs" from Settings to Reports (Reports > Change Logs). Only the menu group changes: access is by smenu_id,
--    so nobody gains or loses it. Users see the new place after they log in again.
UPDATE `wf_smenu` SET `menu_id` = 7, `smenu_order` = 10 WHERE `smenu_link` = 'changelogs';

-- 7. Start Work v3: the pre-delivery confirmation log (what the popup showed, the user's decision, how the submit ended)
CREATE TABLE IF NOT EXISTS `inw_delivery_confirm_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `a_id` int(11) DEFAULT NULL,
  `pub_id` varchar(100) DEFAULT NULL,
  `cust_id` int(11) DEFAULT NULL,
  `j_id` int(11) DEFAULT NULL,
  `stage` varchar(25) DEFAULT NULL,
  `from_dept` varchar(60) DEFAULT NULL,
  `to_label` varchar(120) DEFAULT NULL,
  `kind` varchar(20) DEFAULT NULL,
  `user_id` varchar(20) DEFAULT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `src_dir` text,
  `dest_dir` text,
  `folder` mediumtext,
  `config` mediumtext,
  `auto_info` mediumtext,
  `warnings` mediumtext,
  `errors` mediumtext,
  `decision` varchar(15) DEFAULT NULL,
  `shown_at` datetime DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  `outcome` varchar(15) DEFAULT NULL,
  `outcome_message` text,
  `after_folder` mediumtext,
  `dest_before` mediumtext,
  `report_mail` varchar(255) DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `a_id` (`a_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7b. Start Work v3: what was already in the Delivery folder before the move (same-name files are flagged). Needed when the table above
--     was created before this column existed (the page also adds it itself on first use).
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inw_delivery_confirm_log' AND COLUMN_NAME = 'dest_before') = 0,
              'ALTER TABLE `inw_delivery_confirm_log` ADD COLUMN `dest_before` MEDIUMTEXT NULL',
              'SELECT ''inw_delivery_confirm_log.dest_before already exists''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 8. Menu item: Reports > Pre-delivery Checks  (page: /deliverychecklog), and Portal Admin gets it (other roles: Edit Role screen, then log in again)
INSERT INTO `wf_smenu` (`menu_id`, `smenu_type`, `smenu_order`, `smenu_item`, `smenu_title`, `smenu_class`, `smenu_link`, `smenu_link_demo`, `smenu_status`, `smenu_show`)
SELECT 7, 'ADMIN', 11, 'Pre-delivery Checks', 'Pre-delivery Checks', '', 'deliverychecklog', 'deliverychecklog', 'Active', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `wf_smenu` WHERE `smenu_link` = 'deliverychecklog');

UPDATE `user_role_master`
SET `smenu_id` = CONCAT(`smenu_id`, ',', (SELECT `smenu_id` FROM `wf_smenu` WHERE `smenu_link` = 'deliverychecklog' LIMIT 1))
WHERE `user_role_id` = 1
  AND `smenu_id` <> ''
  AND FIND_IN_SET((SELECT `smenu_id` FROM `wf_smenu` WHERE `smenu_link` = 'deliverychecklog' LIMIT 1), `smenu_id`) = 0;

-- Check:
-- SELECT smenu_id, menu_id, smenu_title, smenu_link, smenu_status FROM wf_smenu WHERE smenu_link = 'dispatchlog';
-- SELECT user_role_id, user_role_name FROM user_role_master WHERE FIND_IN_SET((SELECT smenu_id FROM wf_smenu WHERE smenu_link = 'dispatchlog'), smenu_id);
