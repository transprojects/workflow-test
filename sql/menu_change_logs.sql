-- Adds "Change Logs" to the admin menu (Settings group, menu_id = 5).
-- The page itself is routed by .htaccess:  RewriteRule ^changelogs$ view-change-logs.php
-- Run once on the `workflow` database.

-- 1. Create the menu entry (skipped if it already exists)
INSERT INTO `wf_smenu`
  (`menu_id`, `smenu_type`, `smenu_order`, `smenu_item`, `smenu_title`, `smenu_class`, `smenu_link`, `smenu_link_demo`, `smenu_status`, `smenu_show`)
SELECT 5, 'ADMIN', 3, 'Change Logs', 'Change Logs', '', 'changelogs', 'changelogs', 'Active', 1
WHERE NOT EXISTS (SELECT 1 FROM `wf_smenu` WHERE `smenu_link` = 'changelogs');

-- 2. Give it to a role (the menu only shows for roles that have this smenu_id).
--    Replace 1 with the user_role_id that should see Change Logs, then run:
--
-- UPDATE `user_role_master`
--    SET `smenu_id` = CONCAT(`smenu_id`, ',', (SELECT `smenu_id` FROM `wf_smenu` WHERE `smenu_link` = 'changelogs' LIMIT 1))
--  WHERE `user_role_id` = 1
--    AND FIND_IN_SET((SELECT `smenu_id` FROM `wf_smenu` WHERE `smenu_link` = 'changelogs' LIMIT 1), `smenu_id`) = 0;
--
--    (Alternatively tick "Change Logs" on Roles > Edit Role in the app.)
--
-- Note: menu access is stored in the user's cookie at login, so users must log out
-- and back in to see the new entry.
