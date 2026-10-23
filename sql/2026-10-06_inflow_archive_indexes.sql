-- Phase 0 - Inflow report optimisation (2026-10-06)
-- Run once on every server that has the workflow_archive schema.
-- The live table (workflow.inw_revisions_dtl) already has both indexes; the archive only had its primary key,
-- so every "latest revision" lookup scanned all ~53k rows.  With these indexes the plan goes from two full
-- table scans to an index range (~200 rows).  Safe to run while the site is up: the tables are small (a few seconds).
-- Check first:  SHOW INDEX FROM workflow_archive.inw_revisions_dtl;
ALTER TABLE workflow_archive.inw_revisions_dtl
  ADD INDEX idx_inw_id_rid (inw_id, r_id),
  ADD INDEX idx_received_date (received_date);
-- Rollback:
-- ALTER TABLE workflow_archive.inw_revisions_dtl DROP INDEX idx_inw_id_rid, DROP INDEX idx_received_date;
