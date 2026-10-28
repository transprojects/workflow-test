-- Move Files: per-extension file-naming rules, 2026-10-26.
-- JSON object keyed by extension, e.g. {"xml":"{pubid}","docx":"{pubid}_*"}.  NULL / missing extension = no naming
-- rule, i.e. every file with that extension moves exactly as before, so this change is safe to run ahead of the code.
-- Back up first.  To undo:  ALTER TABLE inw_move_files_dept_wise DROP COLUMN move_name_rules;
ALTER TABLE inw_move_files_dept_wise
  ADD COLUMN IF NOT EXISTS move_name_rules TEXT NULL DEFAULT NULL AFTER move_files;
