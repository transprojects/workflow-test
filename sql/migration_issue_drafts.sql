-- Issue article draft rounds (DRAFT1, DRAFT2 ...)
-- Articles of an issue stay in stage ISSCOR<n>; a client correction round for selected
-- articles is recorded here only (database link, no new stage / folder).
-- Safe to run more than once. No existing table is altered.

CREATE TABLE IF NOT EXISTS `inw_issue_article_drafts` (
  `draft_id`         int(11)      NOT NULL AUTO_INCREMENT,
  `vi_id`            int(11)      NOT NULL,
  `article_id`       bigint(20)   NOT NULL,
  `draft_no`         smallint(6)  NOT NULL,
  `sub_stage`        varchar(10)  NOT NULL,
  `stage`            varchar(15)  DEFAULT NULL COMMENT 'article stage when booked (e.g. ISSCOR1)',
  `received_date`    date         DEFAULT NULL,
  `due_date`         date         DEFAULT NULL,
  `correction_pages` int(11)      NOT NULL DEFAULT 0,
  `wf_id`            int(11)      DEFAULT NULL,
  `comments`         text,
  `attachment`       varchar(255) DEFAULT NULL,
  `created_by`       varchar(50)  DEFAULT NULL,
  `created_on`       datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`draft_id`),
  UNIQUE KEY `uq_article_draft` (`article_id`,`draft_no`),
  KEY `idx_vi` (`vi_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Dispatch date of each round (set when the round's articles go back to the client). Run once (MySQL has no IF NOT EXISTS for columns; a second run only says "Duplicate column name").
ALTER TABLE `inw_issue_article_drafts`
  ADD COLUMN `dispatched_on` datetime    DEFAULT NULL,
  ADD COLUMN `dispatched_by` varchar(50) DEFAULT NULL;
