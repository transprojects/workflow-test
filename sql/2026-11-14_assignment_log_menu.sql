-- Menu entry (Articles > Assignment Log) for /assignmentlog
INSERT INTO wf_smenu (menu_id,smenu_type,smenu_order,smenu_item,smenu_title,smenu_class,smenu_link,smenu_link_demo,smenu_status,smenu_show)
SELECT 9,'ADMIN',(SELECT COALESCE(MAX(smenu_order),0)+1 FROM wf_smenu WHERE menu_id=9),'Assignment Log','Assignment Log','','assignmentlog','assignmentlog','Active',1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM wf_smenu WHERE smenu_link='assignmentlog');
