-- Department e-mail + managers, copied from the local workflow_test DB. manager_ids are users.id values (same ids as local).
-- Run the ALTER only if the columns do not exist yet (the app also adds them automatically).
ALTER TABLE adm_dept_master ADD COLUMN dept_email VARCHAR(255) NULL, ADD COLUMN manager_ids VARCHAR(255) NULL;

UPDATE adm_dept_master SET dept_email = 'ananthi@transforma.in', manager_ids = '309' WHERE dept_code = 1;  -- CE
UPDATE adm_dept_master SET dept_email = 'ananthi@transforma.in', manager_ids = '309' WHERE dept_code = 2;  -- Pre-Editing
UPDATE adm_dept_master SET dept_email = 'manikandan@transforma.in', manager_ids = '416' WHERE dept_code = 5;  -- XML
UPDATE adm_dept_master SET dept_email = 'art@transforma.in', manager_ids = '278' WHERE dept_code = 9;  -- Graphics
UPDATE adm_dept_master SET dept_email = 'gani@transforma.in', manager_ids = '126' WHERE dept_code = 10;  -- PAG
UPDATE adm_dept_master SET dept_email = 'journalqc@transforma.in', manager_ids = '167' WHERE dept_code = 14;  -- PRQC
UPDATE adm_dept_master SET dept_email = 'inward@transforma.in', manager_ids = '199,424,414' WHERE dept_code = 15;  -- Delivery
UPDATE adm_dept_master SET dept_email = 'inward@transforma.in', manager_ids = '424,199,414' WHERE dept_code = 16;  -- Inward
UPDATE adm_dept_master SET dept_email = 'manikandan@transforma.in', manager_ids = '416' WHERE dept_code = 17;  -- ePub
UPDATE adm_dept_master SET dept_email = 'journalqc@transforma.in', manager_ids = '167' WHERE dept_code = 18;  -- Proofing
UPDATE adm_dept_master SET dept_email = 'ananthi@transforma.in', manager_ids = '309' WHERE dept_code = 22;  -- Pre-Clean
UPDATE adm_dept_master SET dept_email = 'manikandan@transforma.in', manager_ids = '416' WHERE dept_code = 23;  -- FXML
UPDATE adm_dept_master SET dept_email = 'jeyapriya@transforma.in, ananthi@transforma.in', manager_ids = '309,175' WHERE dept_code = 24;  -- Tex-pre-clean
UPDATE adm_dept_master SET dept_email = 'ananthi@transforma.in', manager_ids = '309' WHERE dept_code = 32;  -- CE-ELX
UPDATE adm_dept_master SET dept_email = 'ananthi@transforma.in', manager_ids = '309' WHERE dept_code = 33;  -- PRE-CLEAN-ELX
UPDATE adm_dept_master SET dept_email = 'journalqc@transforma.in', manager_ids = '351,167' WHERE dept_code = 34;  -- PRQC-ELX
UPDATE adm_dept_master SET dept_email = 'art@transforma.in', manager_ids = '278' WHERE dept_code = 35;  -- GRAPHICS-ELX
UPDATE adm_dept_master SET dept_email = 'manikandan@transforma.in', manager_ids = '416' WHERE dept_code = 36;  -- XML-ELX
UPDATE adm_dept_master SET dept_email = 'inward@transforma.in', manager_ids = '424,414' WHERE dept_code = 37;  -- DELIVERY-ELX
UPDATE adm_dept_master SET dept_email = 'gani@transforma.in', manager_ids = '126' WHERE dept_code = 38;  -- PAG-ELX
