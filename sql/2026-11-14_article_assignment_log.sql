-- Assignment history: one row per (re)assignment of an article to a user, with the previous holder and the stage at that time.
CREATE TABLE IF NOT EXISTS `article_assignment_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inw_id` int(11) NOT NULL,
  `pub_id` varchar(255) DEFAULT NULL,
  `stage` varchar(50) DEFAULT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `assigned_user_id` int(11) NOT NULL,
  `prev_user_id` int(11) DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `action` varchar(20) NOT NULL DEFAULT 'assign',
  `assigned_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_inw` (`inw_id`),
  KEY `idx_user` (`assigned_user_id`),
  KEY `idx_prev` (`prev_user_id`),
  KEY `idx_at` (`assigned_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
