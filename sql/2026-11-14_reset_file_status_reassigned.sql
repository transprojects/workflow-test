-- ONE-TIME FIX: articles that were re-assigned before the "reset file status on re-assign" change.
-- Targets only articles whose LATEST assignment-log row is a re-assign, whose file status is not 'Inprogress',
-- nobody has it open, and the new assignee has not started work since (no transaction by them after the re-assign).
-- Only re-assigns recorded in article_assignment_log can be found; older ones are not in the log.

-- 1) PREVIEW - check this list first
SELECT i.id, i.pub_id, i.stage, i.status, i.file_status AS current_file_status, l.assigned_at AS reassigned_at,
       pu.full_name AS previous_user, cu.full_name AS assigned_to
FROM article_assignment_log l
JOIN (SELECT inw_id, MAX(id) AS last_id FROM article_assignment_log GROUP BY inw_id) m ON m.last_id = l.id
JOIN inw_inward_dtl i ON i.id = l.inw_id
LEFT JOIN users pu ON pu.id = l.prev_user_id
LEFT JOIN users cu ON cu.id = l.assigned_user_id
WHERE l.action = 'reassign'
  AND i.file_status <> 'Inprogress' AND i.file_status <> 'Assign'
  AND (i.present_user_id = 0 OR i.present_user_id IS NULL)
  AND NOT EXISTS (SELECT 1 FROM inw_transactions t WHERE t.project_id = i.id AND t.process_user = l.assigned_user_id AND t.created_dt > l.assigned_at);

-- 2) FIX - run only after checking the preview (same conditions)
UPDATE inw_inward_dtl i
JOIN (SELECT inw_id, MAX(id) AS last_id FROM article_assignment_log GROUP BY inw_id) m ON m.inw_id = i.id
JOIN article_assignment_log l ON l.id = m.last_id
SET i.file_status = 'Assign'
WHERE l.action = 'reassign'
  AND i.file_status <> 'Inprogress' AND i.file_status <> 'Assign'
  AND (i.present_user_id = 0 OR i.present_user_id IS NULL)
  AND NOT EXISTS (SELECT 1 FROM inw_transactions t WHERE t.project_id = i.id AND t.process_user = l.assigned_user_id AND t.created_dt > l.assigned_at);
