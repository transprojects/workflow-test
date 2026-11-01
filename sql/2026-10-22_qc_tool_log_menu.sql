-- Reports > "QC Tool Log" menu item (page: qc_tool_log.php) - first validator reports + bypass log. Safe to run more than once.
-- It is given to the Portal Admin role (user_role_id = 1); add it to other roles from Users > Roles > Menu Access Permission.
INSERT INTO wf_smenu (menu_id, smenu_type, smenu_order, smenu_item, smenu_title, smenu_class, smenu_link, smenu_link_demo, smenu_status, smenu_show)
SELECT 7, 'ADMIN', 17, 'QC Tool Log', 'QC Tool Log', '', 'qc_tool_log.php', 'qc_tool_log.php', 'Active', 1
 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM wf_smenu WHERE smenu_link = 'qc_tool_log.php');

UPDATE user_role_master r
   SET r.smenu_id = CONCAT(r.smenu_id, ',', (SELECT smenu_id FROM wf_smenu WHERE smenu_link = 'qc_tool_log.php' LIMIT 1))
 WHERE r.user_role_id = 1
   AND NOT FIND_IN_SET((SELECT smenu_id FROM wf_smenu WHERE smenu_link = 'qc_tool_log.php' LIMIT 1), r.smenu_id);
