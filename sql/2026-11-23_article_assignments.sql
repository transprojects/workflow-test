-- Per-department article owners. Replaces the single assigned_user_id / assigned_od_user_id pair as the source of truth:
-- one row per (article, department) owner. The current owner is the active row (active_key = 1); when the owner is replaced or removed the row is
-- ended (active_key NULL) and kept, so "who handled this article in PRQC before" is simply the latest row for that department.
-- inw_inward_dtl.assigned_user_id / assigned_od_user_id (+ ce_pe_due_date / od_due_date) are still written, as a cache for the existing pages.
-- Safe to run more than once. The application works without this table (it just skips the writes), so it can be applied before or after the code.

CREATE TABLE IF NOT EXISTS `article_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inw_id` int(11) NOT NULL,
  `dept_id` int(11) NOT NULL,
  `stage` varchar(50) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `due_date` date DEFAULT NULL,
  `source` varchar(20) NOT NULL DEFAULT 'assign',      -- assign / reassign / auto / handoff / return / backfill / history
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_at` datetime NOT NULL,
  `ended_at` datetime DEFAULT NULL,
  `end_reason` varchar(20) DEFAULT NULL,               -- replaced / cleared
  `active_key` tinyint(1) DEFAULT NULL,                -- 1 while this is the current owner, NULL once ended (lets the unique key allow one active owner per article+department)
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_active` (`inw_id`,`dept_id`,`active_key`),
  KEY `idx_user_active` (`user_id`,`active_key`),
  KEY `idx_inw_dept` (`inw_id`,`dept_id`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1) Current owners, from the columns the pages use today (only when the article is in the department that column belongs to).
INSERT INTO article_assignments (inw_id, dept_id, stage, user_id, due_date, source, assigned_at, active_key)
SELECT i.id, i.department_id, i.stage,
       IF(i.department_id IN (10,14,34,38), i.assigned_od_user_id, i.assigned_user_id),
       IF(i.department_id IN (10,14,34,38), i.od_due_date, i.ce_pe_due_date),
       'backfill', NOW(), 1
FROM inw_inward_dtl i
WHERE i.department_id IN (1,2,5,9,16,22,24,32,10,14,34,38)
  AND IF(i.department_id IN (10,14,34,38), i.assigned_od_user_id, i.assigned_user_id) > 0
  AND NOT EXISTS (SELECT 1 FROM article_assignments a WHERE a.inw_id = i.id AND a.dept_id = i.department_id AND a.active_key = 1);

-- 2) History: for every other department the article went through, the user who last worked it there (from inw_transactions). Ended rows - they only feed "return to previous user".
INSERT INTO article_assignments (inw_id, dept_id, stage, user_id, due_date, source, assigned_at, ended_at, end_reason, active_key)
SELECT t.project_id, t.dept, t.current_stage, t.process_user, NULL, 'history', COALESCE(t.start_time, NOW()), COALESCE(t.end_time, t.start_time), 'replaced', NULL
FROM inw_transactions t
JOIN (SELECT project_id, dept, MAX(id) AS mid FROM inw_transactions
      WHERE dept IN (1,2,5,9,16,22,24,32,10,14,34,38,18) AND process_user > 0 GROUP BY project_id, dept) m ON m.mid = t.id
WHERE NOT EXISTS (SELECT 1 FROM article_assignments a WHERE a.inw_id = t.project_id AND a.dept_id = t.dept);
