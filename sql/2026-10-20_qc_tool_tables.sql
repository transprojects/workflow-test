-- QC tools (PeerJ, IAP, TSP ...): first-report archive + bypass log.
-- Run once on the live database. The PHP (journal_style_validator.php) also creates these
-- automatically if missing, so this file is only needed to create them ahead of time.
-- Safe to re-run (IF NOT EXISTS).

CREATE TABLE IF NOT EXISTS `qc_tool_first_report` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `a_id` int(11) NOT NULL,
  `pub_id` varchar(100) DEFAULT NULL,
  `cust_id` int(11) DEFAULT NULL,
  `tool` varchar(20) NOT NULL,
  `stage` varchar(25) DEFAULT NULL,
  `dept` varchar(50) DEFAULT NULL,
  `xml_file` varchar(255) DEFAULT NULL,
  `status` varchar(10) NOT NULL,
  `error_count` int(11) DEFAULT NULL,
  `warning_count` int(11) DEFAULT NULL,
  `report_file` varchar(255) DEFAULT NULL,
  `report_size` int(11) DEFAULT NULL,
  `run_by` varchar(100) DEFAULT NULL,
  `run_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_article_tool` (`a_id`, `tool`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `qc_tool_bypass_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `a_id` int(11) NOT NULL,
  `pub_id` varchar(100) DEFAULT NULL,
  `cust_id` int(11) DEFAULT NULL,
  `tool` varchar(20) NOT NULL,
  `stage` varchar(25) DEFAULT NULL,
  `dept` varchar(50) DEFAULT NULL,
  `xml_file` varchar(255) DEFAULT NULL,
  `had_failing_report` tinyint(1) NOT NULL DEFAULT 0,
  `report_file` varchar(255) DEFAULT NULL,
  `reason` text,
  `bypassed_by` varchar(100) DEFAULT NULL,
  `user_id` varchar(20) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `bypassed_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `a_id` (`a_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
