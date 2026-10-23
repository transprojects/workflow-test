<?php
/* Article edit history: what an "Edit Article" save changed, in readable form (labels, names instead of ids).
 * Written by edit_article.php, shown in the Job Card ("View changes" pop-up and the "Article edits" panel).
 * Table article_edit_log is created on first use:
 *
 *   CREATE TABLE IF NOT EXISTS article_edit_log (
 *     id INT(11) NOT NULL AUTO_INCREMENT, article_id INT(11) NOT NULL DEFAULT 0, transaction_id INT(11) NOT NULL DEFAULT 0,
 *     stage VARCHAR(30) NOT NULL DEFAULT '', user_id VARCHAR(30) NOT NULL DEFAULT '', user_name VARCHAR(150) NOT NULL DEFAULT '',
 *     change_count INT(11) NOT NULL DEFAULT 0, changes MEDIUMTEXT NULL, created_at DATETIME NOT NULL,
 *     PRIMARY KEY (id), KEY ix_article (article_id), KEY ix_tx (transaction_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 */

/* the comment written on the transaction row when the department is reassigned from the Edit Article page */
define('EL_DEPT_LABEL', 'Edit Article');
define('EL_DEPT_LABEL_OLD', 'Department changed using the Edit Article page.');   /* rows saved before the rename */
define('EL_DEPT_LABEL_OLD2', 'Department reassigned (Edit Article)');

function el_ensure($db)
{
	static $done = false;
	if ($done) { return; }
	$done = true;
	try {
		$db->query("CREATE TABLE IF NOT EXISTS article_edit_log (
			id INT(11) NOT NULL AUTO_INCREMENT,
			article_id INT(11) NOT NULL DEFAULT 0,
			transaction_id INT(11) NOT NULL DEFAULT 0,
			stage VARCHAR(30) NOT NULL DEFAULT '',
			user_id VARCHAR(30) NOT NULL DEFAULT '',
			user_name VARCHAR(150) NOT NULL DEFAULT '',
			change_count INT(11) NOT NULL DEFAULT 0,
			changes MEDIUMTEXT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id), KEY ix_article (article_id), KEY ix_tx (transaction_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8");
	} catch (Exception $e) { }
}

function el_labels()
{
	return array(
		'cust_id' => 'Customer', 'at_id' => 'Article type', 'j_id' => 'Journal', 'journal_title' => 'Journal title', 'article_title' => 'Article title',
		'accpt_dt' => 'Accepted date', 'pub_id' => 'Article ID', 'doi' => 'DOI', 'pub_dt' => 'Publication date', 'fig' => 'Fig count', 'tab' => 'Table count',
		'location_id' => 'E location ID', 'stage' => 'Stage', 'manuscript_count' => 'Manuscript count', 'corresponding_author' => 'Corresponding author',
		'notes' => 'Notes', 'instructions' => 'Instructions', 'graphics' => 'Graphics', 'worklocation' => 'Work location', 'highpriority' => 'High priority',
		'is_conversion' => 'Conversion article', 'late_dispatch' => 'Client approved late dispatch', 'test_article' => 'Test article',
		'img_color_figures' => 'Color figures count', 'img_relabel_count' => 'Relabel fig count', 'img_redraw_count' => 'Redraw fig count', 'img_disk_count' => 'Disk fig count',
		'anxillary_fig' => 'Anxillary fig count', 'page_count' => 'Proof page count', 'complexity' => 'Complexity', 'inw_type' => 'Input type',
		'corresponding_other_author' => 'Co-author mails', 'target_due_dt' => 'Target due date (CE)', 'corresponding_mail' => 'Corresponding mail',
		'wf_id' => 'Workflow', 'department_id' => 'Department', 'status' => 'Current department', 'issueorgroup_folder' => 'Group folder',
		'recv_dt' => 'Received date', 'due_dt' => 'Due date', 'at_status' => 'Article status', 'message_query' => 'Query message'
	);
}

/* a stored raw value -> what a person reads */
function el_value($db, $field, $v)
{
	$v = (string)$v;
	if ($v === '' || $v === '0000-00-00' || $v === '0000-00-00 00:00:00' || $v === '1970-01-01') { return ''; }
	$one = function ($sql, $col) use ($db) { $r = $db->query($sql); $x = $r ? $r->fetch_assoc() : null; return $x ? (string)$x[$col] : ''; };
	switch ($field) {
		case 'cust_id':       $n = $one("SELECT cust_name FROM adm_customer_master WHERE id = ".(int)$v, 'cust_name'); return $n !== '' ? $n : $v;
		case 'j_id':          $n = $one("SELECT j_code FROM adm_journals WHERE j_id = ".(int)$v, 'j_code'); return $n !== '' ? $n : $v;
		case 'at_id':         $n = $one("SELECT at_title FROM inw_article_type WHERE at_id = ".(int)$v, 'at_title'); return $n !== '' ? $n : $v;
		case 'wf_id':         $n = $one("SELECT wf_name FROM adm_workflow_master WHERE wf_id = ".(int)$v, 'wf_name'); return $n !== '' ? $n : $v;
		case 'department_id': $n = $one("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".(int)$v, 'dept_name'); return $n !== '' ? $n : $v;
		case 'highpriority': case 'test_article': return ($v === '1' || strtolower($v) === 'yes') ? 'Yes' : 'No';
		case 'is_conversion': case 'late_dispatch': return strtolower($v) === 'yes' ? 'Yes' : 'No';
		case 'accpt_dt': case 'pub_dt': case 'target_due_dt': case 'recv_dt': case 'due_dt':
			return strtotime($v) ? date('d-m-Y', strtotime($v)) : $v;
	}
	return $v;
}

/* $old / $new: raw column => value (as edit_article.php compares them). Returns array of array(label, from, to) */
function el_diff($db, $old, $new)
{
	$labels = el_labels(); $out = array();
	foreach ($new as $f => $n) {
		if ($f === 'status' && isset($new['department_id'])) { continue; }   /* the department name is already shown by "Department" */
		$o = isset($old[$f]) ? (string)$old[$f] : '';
		if ($o === (string)$n) { continue; }
		$from = el_value($db, $f, $o); $to = el_value($db, $f, $n);
		if ($from === $to) { continue; }          /* e.g. 0000-00-00 -> empty, 0 -> No */
		$out[] = array('label' => isset($labels[$f]) ? $labels[$f] : $f, 'from' => $from, 'to' => $to);
	}
	return $out;
}

function el_record($db, $article_id, $tx_id, $stage, $changes)
{
	if (!$changes) { return 0; }
	el_ensure($db);
	$db->query("INSERT INTO article_edit_log (article_id, transaction_id, stage, user_id, user_name, change_count, changes, created_at) VALUES ("
		.(int)$article_id.", ".(int)$tx_id.", '".$db->real_escape_string((string)$stage)."', '"
		.$db->real_escape_string(isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : '')."', '".$db->real_escape_string(isset($_COOKIE['full_name']) ? $_COOKIE['full_name'] : '')."', "
		.count($changes).", '".$db->real_escape_string(json_encode($changes))."', NOW())");
	return (int)$db->insert_id;
}
