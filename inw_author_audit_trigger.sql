-- Audit every change to author columns of inw_inward_dtl, from ANY source (app, phpMyAdmin, scripts).
-- DB-level USER() / CONNECTION_ID() show who connected; compare with the app's DB login to spot direct edits.
CREATE TABLE IF NOT EXISTS inw_author_audit (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  inw_id INT NOT NULL,
  old_author TEXT NULL, new_author TEXT NULL,
  old_mail TEXT NULL,   new_mail TEXT NULL,
  old_other TEXT NULL,  new_other TEXT NULL,
  db_user VARCHAR(100) NOT NULL,      -- e.g. workflow_app@10.10.10.5 vs root@laptop
  conn_id BIGINT NOT NULL,
  changed_at DATETIME NOT NULL,
  KEY (inw_id), KEY (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TRIGGER IF EXISTS trg_inw_author_audit;
DELIMITER $$
CREATE TRIGGER trg_inw_author_audit AFTER UPDATE ON inw_inward_dtl
FOR EACH ROW
BEGIN
  IF NOT (OLD.corresponding_author <=> NEW.corresponding_author
      AND OLD.corresponding_mail <=> NEW.corresponding_mail
      AND OLD.corresponding_other_author <=> NEW.corresponding_other_author) THEN
    INSERT INTO inw_author_audit
      (inw_id, old_author, new_author, old_mail, new_mail, old_other, new_other, db_user, conn_id, changed_at)
    VALUES
      (NEW.id, OLD.corresponding_author, NEW.corresponding_author, OLD.corresponding_mail, NEW.corresponding_mail,
       OLD.corresponding_other_author, NEW.corresponding_other_author, USER(), CONNECTION_ID(), NOW());
  END IF;
END$$
DELIMITER ;
