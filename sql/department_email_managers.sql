-- Department master: department e-mail + department managers (users.id list, comma separated)
-- Used by the "file movement not configured" report (Start Work v2 / v3). The app also adds these columns by itself
-- (sw_dept_ensure in sw_switch_lib.php) the first time a department page or the report is used; run this only if you prefer to do it by hand.
ALTER TABLE adm_dept_master
  ADD COLUMN dept_email VARCHAR(255) NULL,
  ADD COLUMN manager_ids VARCHAR(255) NULL;

UPDATE adm_dept_master SET dept_email = 'inward@transforma.in'    WHERE dept_code = 16 AND (dept_email IS NULL OR dept_email = '');
UPDATE adm_dept_master SET dept_email = 'ananthi@transforma.in'   WHERE dept_code = 1  AND (dept_email IS NULL OR dept_email = '');
UPDATE adm_dept_master SET dept_email = 'manikandan@transforma.in' WHERE dept_code = 5 AND (dept_email IS NULL OR dept_email = '');
UPDATE adm_dept_master SET dept_email = 'gani@transforma.in'      WHERE dept_code = 10 AND (dept_email IS NULL OR dept_email = '');
UPDATE adm_dept_master SET dept_email = 'journalqc@transforma.in' WHERE dept_code = 14 AND (dept_email IS NULL OR dept_email = '');
UPDATE adm_dept_master SET dept_email = 'art@transforma.in'       WHERE dept_code = 9  AND (dept_email IS NULL OR dept_email = '');
