-- v2: richer audit. Run once on top of v1 (table already exists). Safe to re-run except the ALTER (skip if columns exist).
ALTER TABLE inw_author_audit
  ADD COLUMN pub_id        VARCHAR(50)  NULL,   -- article pub id at the time of change
  ADD COLUMN stage         VARCHAR(50)  NULL,   -- current stage (FP, ...)
  ADD COLUMN dept_status   VARCHAR(50)  NULL,   -- current status / department
  ADD COLUMN client_host   VARCHAR(255) NULL,   -- IP:port of the connection, from PROCESSLIST
  ADD COLUMN db_name       VARCHAR(64)  NULL,
  ADD COLUMN sql_text      MEDIUMTEXT   NULL,   -- the exact UPDATE statement that fired the trigger
  ADD COLUMN client_prog   VARCHAR(100) NULL,   -- program_name attr (mysql, HeidiSQL, PHP mysqlnd ...)
  ADD COLUMN app_user_id   VARCHAR(50)  NULL,   -- set by the app (NULL = not from the app = direct edit)
  ADD COLUMN app_script    VARCHAR(255) NULL,   -- start_work_v3.php etc, set by the app
  ADD COLUMN app_ip        VARCHAR(45)  NULL;   -- end user's browser IP, set by the app

DROP TRIGGER IF EXISTS trg_inw_author_audit;
DELIMITER $$
CREATE TRIGGER trg_inw_author_audit AFTER UPDATE ON inw_inward_dtl
FOR EACH ROW
BEGIN
  DECLARE v_host VARCHAR(255); DECLARE v_db VARCHAR(64); DECLARE v_sql MEDIUMTEXT; DECLARE v_prog VARCHAR(100);
  IF NOT (OLD.corresponding_author <=> NEW.corresponding_author
      AND OLD.corresponding_mail <=> NEW.corresponding_mail
      AND OLD.corresponding_other_author <=> NEW.corresponding_other_author) THEN
    SELECT HOST, DB, INFO INTO v_host, v_db, v_sql
      FROM information_schema.PROCESSLIST WHERE ID = CONNECTION_ID() LIMIT 1;
    -- client_prog stays NULL; to fill it, enable performance_schema and SELECT program_name from performance_schema.session_connect_attrs
    INSERT INTO inw_author_audit
      (inw_id, pub_id, stage, dept_status, old_author, new_author, old_mail, new_mail, old_other, new_other, db_user, conn_id, changed_at,
       client_host, db_name, sql_text, client_prog, app_user_id, app_script, app_ip)
    VALUES
      (NEW.id, NEW.pub_id, NEW.stage, NEW.status, OLD.corresponding_author, NEW.corresponding_author, OLD.corresponding_mail, NEW.corresponding_mail,
       OLD.corresponding_other_author, NEW.corresponding_other_author, USER(), CONNECTION_ID(), NOW(),
       v_host, v_db, v_sql, v_prog, @app_user_id, @app_script, @app_ip);
  END IF;
END$$
DELIMITER ;
