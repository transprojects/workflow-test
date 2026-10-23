-- Index additions, 2026-10-02.  Plain non-unique indexes: no application change, no effect on INSERT/UPDATE
-- correctness.  Most of these tables are MyISAM, so each ALTER locks writes while it builds - run when quiet.
-- Back up first.  To undo any of them:  ALTER TABLE <t> DROP INDEX <name>;
-- NOTE: inw_transactions.process_user and dept are varchar - always compare them to a QUOTED value
--       (process_user = '352'); an unquoted number makes MySQL ignore the index.

-- ---- reports / dashboards ----
ALTER TABLE inw_transactions            ADD INDEX ix_user_created   (process_user, created_dt);
ALTER TABLE inw_transactions            ADD INDEX ix_user_start     (process_user, start_time);
ALTER TABLE user_report                 ADD INDEX ix_user_dept_date (user_id, dept_code, date);
ALTER TABLE non_workflow_user_percentage ADD INDEX ix_user_date     (user_id, nw_date);
ALTER TABLE user_target_analytics       ADD INDEX ix_user_dept_from (user_id, dept_id, from_date);
ALTER TABLE user_target_analytics_details ADD INDEX ix_master_lookup (master_id, user_id, cust_id, stage);
ALTER TABLE user_target_assign          ADD INDEX ix_user_dept      (user_id, dept_id);
ALTER TABLE user_target_customer_assign ADD INDEX ix_user_uta       (user_id, uta_id);

-- ---- article table (inw_inward_dtl) ----
ALTER TABLE inw_inward_dtl ADD INDEX ix_assigned_target (assigned_user_id, target_due_dt);
ALTER TABLE inw_inward_dtl ADD INDEX ix_status_stage    (status, stage);
ALTER TABLE inw_inward_dtl ADD INDEX ix_created_dt      (created_dt);
ALTER TABLE inw_inward_dtl ADD INDEX ix_due_dt          (due_dt);
ALTER TABLE inw_inward_dtl ADD INDEX ix_v_issue         (v_issue_id);
ALTER TABLE inw_inward_dtl ADD INDEX ix_present_user    (present_user_id);
ALTER TABLE inw_inward_dtl ADD INDEX ix_last_worked     (last_worked_user_id);
ALTER TABLE inw_inward_dtl ADD INDEX ix_od_user_due     (assigned_od_user_id, od_due_date);
ALTER TABLE inw_inward_dtl ADD INDEX ix_j_id            (j_id);

-- ---- lookups that were full scans (only the primary key existed) ----
ALTER TABLE inw_dispatch_history   ADD INDEX ix_sent_date   (sent_date);          -- a_id,stage,sent_date already exists
ALTER TABLE inw_dept_comments      ADD INDEX ix_inw         (inw_id);
ALTER TABLE inw_issue_corrections  ADD INDEX ix_vi          (vi_id, ic_id);
ALTER TABLE inw_client_feedback    ADD INDEX ix_cust_created (cust_id, created_on);
ALTER TABLE inw_client_feedback    ADD INDEX ix_cust_apdt   (cust_id, ap_dt);
ALTER TABLE inw_move_files_dept_wise ADD INDEX ix_mf        (mf_id);
ALTER TABLE inw_to_checklist       ADD INDEX ix_inw_stage   (inw_id, stage, dept_id);   -- 675k rows
ALTER TABLE article_transactions   ADD INDEX ix_article     (article_id);
ALTER TABLE login_history          ADD INDEX ix_user_dt     (lh_userid, lh_current_dt);
ALTER TABLE wf_token_auth       ADD INDEX ix_start_user  (start_date, username);   -- wf-users/user.php: today's logins
