<?php 
ob_start();
include "includes/includes.php";
require_once __DIR__ . '/includes/path_map.php';   // J:/ <-> E:/New_Workflow_Files/ so the folder is found on whichever drive holds it
ini_set('max_execution_time', 0);
function formatSizeUnits($bytes) {
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }

    return $bytes;
}

function randomPassword($len) {
    $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < $len; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
}

/* ---------- helpers shared by the package check, the "To be delivered" section and the dispatch log ---------- */
function sc_article_dir($db, $a) {
	$dr = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = " . (int)$a['department_id']);
	$dd = $dr ? $dr->fetch_assoc() : array('dept_name' => '');
	return wf_existing_dir($a['work_dir'] . $a['volume_folder'] . "/" . $a['pub_id'] . "/" . $a['stage'] . "/" . $dd['dept_name'] . "/");
}
/* one file: exists, size, modified date, zip entries / pdf pages, problems */
function sc_file_info_one($dir, $n, $hash = false) {
	$n = basename((string)$n);
	$p = $dir . $n;
	$info = array('name' => $n, 'exists' => is_file($p));
	if (!$info['exists']) { return $info; }
	$info['size'] = filesize($p);
	$info['size_text'] = formatSizeUnits($info['size']);
	$info['modified'] = date('d-M-Y H:i', filemtime($p));
	$info['age_hours'] = round((time() - filemtime($p)) / 3600, 1);
	if ($hash && $info['size'] > 0 && $info['size'] < 200 * 1048576) { $info['md5'] = md5_file($p); }
	$ext = strtolower(pathinfo($n, PATHINFO_EXTENSION));
	if ($ext == 'zip') {
		$info['type'] = 'zip';
		$z = new ZipArchive();
		if ($info['size'] > 0 && $z->open($p) === true) {
			$entries = array();
			for ($i = 0; $i < $z->numFiles; $i++) {
				$s = $z->statIndex($i);
				if (substr($s['name'], -1) == '/') { continue; }
				$entries[] = array('name' => $s['name'], 'size' => formatSizeUnits($s['size']));
			}
			$z->close();
			$info['entries'] = $entries;
			$info['count'] = count($entries);
		} else { $info['error'] = 'The zip cannot be opened (empty or corrupt).'; }
	} elseif ($ext == 'pdf') {
		$info['type'] = 'pdf';
		if ($info['size'] == 0) { $info['error'] = 'The PDF is empty (0 KB).'; }
		else {
			$c = file_get_contents($p, false, null, 0, 60 * 1048576);
			$pages = preg_match_all('/\/Type\s*\/Page(?![s\w])/', $c);
			if (preg_match_all('/\/Count\s+(\d+)/', $c, $m) && max($m[1]) > $pages) { $pages = (int)max($m[1]); }
			$info['pages'] = $pages > 0 ? $pages : null;
			if (substr(ltrim(substr($c, 0, 1024)), 0, 4) !== '%PDF') { $info['error'] = 'This file does not look like a valid PDF.'; }
		}
	} else { $info['type'] = 'other'; if ($info['size'] == 0) { $info['error'] = 'The file is empty (0 KB).'; } }
	return $info;
}
/* Move Files configuration into the department the article is in now: ext => [source depts], plus what the folder holds */
function sc_move_config($db, $a, $dir) {
	$exts = array();
	$stage = preg_replace('/[0-9]+/', '', $a['stage']);
	$wf = null;
	if ((int)$a['wf_id'] > 0) {
		$r = $db->query("SELECT wf_code FROM adm_workflow_master WHERE wf_id = " . (int)$a['wf_id']);
		if ($r && ($w = $r->fetch_assoc())) { $wf = array_map('intval', explode(',', $w['wf_code'])); }
	}
	$r = $db->query("SELECT f.dept_id AS src_id, m.dept_name AS src_name, d.move_files FROM inw_move_files f
		JOIN inw_move_files_dept_wise d ON d.mf_id = f.mf_id
		LEFT JOIN adm_dept_master m ON m.dept_code = f.dept_id
		WHERE f.cust_id = " . (int)$a['cust_id'] . " AND f.j_id = " . (int)$a['j_id'] . "
		AND d.dept_id = " . (int)$a['department_id'] . " AND d.stage = '" . $db->real_escape_string($stage) . "' AND d.move_files <> ''");
	$configured = false;
	while ($r && ($row = $r->fetch_assoc())) {
		if ((int)$row['src_id'] === (int)$a['department_id']) { continue; }
		if ($wf !== null && !in_array((int)$row['src_id'], $wf, true)) { continue; }
		$configured = true;
		foreach (explode('|', $row['move_files']) as $e) {
			$e = strtolower(trim($e, " .\t"));
			if ($e !== '') { $exts[$e][$row['src_name']] = true; }
		}
	}
	$have = array(); $other = array();
	if (is_dir($dir)) {
		foreach (new DirectoryIterator($dir) as $f) {
			if (!$f->isFile()) { continue; }
			$x = strtolower($f->getExtension());
			if (isset($exts[$x])) { $have[$x][] = $f->getFilename(); } else { $other[] = $f->getFilename(); }
		}
	}
	return array('configured' => $configured, 'exts' => $exts, 'have' => $have, 'other' => $other);
}

/* ---------- dispatch report e-mail: one mail per dispatch from the new page (who, what, to whom, which files and what is inside them, how it ended) ---------- */
function sc_h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
/* returns array(subject, body) built from the log row, the article and the folder snapshot */
function sc_dispatch_report($db, $row, $a, $dir) {
	$fmt = function ($d) { return ($d && $d !== '0000-00-00' && strtotime($d)) ? date('d-M-Y', strtotime($d)) : '-'; };
	$tr = function ($k, $v) { return '<tr><td style="padding:5px 14px 5px 0;color:#5b6b7b;vertical-align:top;white-space:nowrap;"><strong>' . $k . '</strong></td><td style="padding:5px 0;">' . $v . '</td></tr>'; };
	$cn = ''; $jn = '';
	$r = $db->query("SELECT cust_name FROM adm_customer_master WHERE id = " . (int)$a['cust_id']); if ($r && ($x = $r->fetch_assoc())) { $cn = $x['cust_name']; }
	$r = $db->query("SELECT j_code FROM adm_journals WHERE j_id = " . (int)$a['j_id']); if ($r && ($x = $r->fetch_assoc())) { $jn = $x['j_code']; }
	$status = $row['status'];
	$ok = $status === 'completed';
	$word = $ok ? 'DISPATCHED' : ($status === 'failed' ? 'DISPATCH FAILED' : 'DISPATCH - NO CLEAR RESULT');
	$col = $ok ? '#2e8b57' : ($status === 'failed' ? '#c0392b' : '#b87800');
	$files = json_decode($row['files'], true); if (!is_array($files)) { $files = array(); }
	$snap = json_decode($row['folder'], true); if (!is_array($snap)) { $snap = array(); }
	$steps = json_decode($row['steps'], true); if (!is_array($steps)) { $steps = array(); }
	$cf = json_decode($row['confirm_info'], true);
	$size = function ($b) { return formatSizeUnits((int)$b); };

	/* files sent: from the snapshot (it carries the zip contents and the "sent" mark); the log's own list adds md5 */
	$md5 = array(); foreach ($files as $f) { if (isset($f['md5'])) { $md5[$f['name']] = $f['md5']; } }
	$sent = array(); foreach ($snap as $f) { if (!empty($f['sent'])) { $sent[] = $f; } }
	$listzip = function ($f, $cap) {
		$h = '';
		if (!empty($f['entries'])) {
			$tot = isset($f['entries_total']) ? (int)$f['entries_total'] : count($f['entries']);
			$h .= '<div style="margin:2px 0 6px 14px;font-size:12px;line-height:1.55;color:#33414f;"><em>' . $tot . ' file(s) inside:</em><br>';
			foreach (array_slice($f['entries'], 0, $cap) as $e) { $h .= '&bull; ' . sc_h($e['name']) . ' <span style="color:#8a95a1;">(' . sc_h($e['size']) . ')</span><br>'; }
			if ($tot > $cap) { $h .= '<em>and ' . ($tot - $cap) . ' more</em><br>'; }
			$h .= '</div>';
		}
		return $h;
	};
	$sent_html = '';
	foreach ($sent as $f) {
		$sent_html .= '<div style="margin:6px 0 2px;"><strong>' . sc_h($f['name']) . '</strong> <span style="color:#5b6b7b;">(' . sc_h(isset($f['size_text']) ? $f['size_text'] : '') . ', modified ' . sc_h(isset($f['modified']) ? $f['modified'] : '-') . ')</span>';
		if (!empty($f['pages'])) { $sent_html .= ' &middot; ' . (int)$f['pages'] . ' page(s)'; }
		if (isset($md5[$f['name']])) { $sent_html .= '<br><span style="color:#8a95a1;font-size:11.5px;">md5 ' . sc_h($md5[$f['name']]) . '</span>'; }
		$sent_html .= '</div>' . $listzip($f, 200);
	}
	if (!$sent_html) { $sent_html = '<p style="color:#b87800;margin:6px 0;">No file from the Delivery folder was sent with this dispatch.</p>'; }
	foreach ($files as $f) { if (isset($f['note'])) { $sent_html .= '<div style="margin:6px 0 2px;"><strong>' . sc_h($f['name']) . '</strong> <span style="color:#5b6b7b;">(' . sc_h($f['note']) . (isset($f['size']) ? ', ' . $size($f['size']) : '') . ')</span></div>'; } }

	/* the folder as it stood */
	$folder_html = '';
	if ($snap) {
		$folder_html = '<table cellpadding="0" cellspacing="0" border="0" style="font-size:12.5px;width:100%;">';
		foreach ($snap as $f) {
			$folder_html .= '<tr style="border-top:1px solid #eef1f4;"><td style="padding:4px 10px 4px 0;">' . sc_h($f['name']) . (!empty($f['sent']) ? ' <strong style="color:#2e8b57;">&#10003; sent</strong>' : '')
				. (!empty($f['error']) ? '<div style="color:#c0392b;">' . sc_h($f['error']) . '</div>' : '') . '</td>'
				. '<td style="padding:4px 10px;white-space:nowrap;text-align:right;color:#5b6b7b;">' . sc_h(isset($f['size_text']) ? $f['size_text'] : '') . '</td>'
				. '<td style="padding:4px 0 4px 10px;white-space:nowrap;text-align:right;color:#5b6b7b;">' . sc_h(isset($f['modified']) ? $f['modified'] : '') . '</td></tr>';
			if (empty($f['sent']) && !empty($f['entries'])) { $folder_html .= '<tr><td colspan="3">' . $listzip($f, 60) . '</td></tr>'; }
		}
		$folder_html .= '</table>';
	} else {
		$folder_html = '<p style="color:#b87800;margin:6px 0;">The Delivery folder was not found or was empty.</p>';
	}

	/* Move Files configuration: what should be delivered, and whether it was there */
	$cfg_html = '';
	$mf = sc_move_config($db, $a, $dir);
	if ($mf['configured']) {
		foreach ($mf['exts'] as $x => $from) {
			$found = !empty($mf['have'][$x]);
			$cfg_html .= '<div>' . ($found ? '<strong style="color:#2e8b57;">&#10003;</strong>' : '<strong style="color:#c0392b;">&#10007;</strong>') . ' <strong>.' . sc_h($x) . '</strong> <span style="color:#5b6b7b;">(from ' . sc_h(implode(', ', array_keys($from))) . ')</span> &mdash; '
				. ($found ? sc_h(implode(', ', $mf['have'][$x])) : '<span style="color:#c0392b;">not in the folder</span>') . '</div>';
		}
	}

	$warn_html = '';
	if (is_array($cf) && !empty($cf['warnings'])) { foreach ($cf['warnings'] as $w) { $warn_html .= '<div style="color:#b87800;">&#9888; ' . sc_h($w) . '</div>'; } }

	$body = '<div style="font-family:Segoe UI,Arial,sans-serif;font-size:14px;color:#22303f;">'
		. '<p>Hi Team,</p>'
		. '<p><strong>' . sc_h($row['user_name']) . '</strong> ' . ($ok ? 'dispatched' : 'tried to dispatch') . ' the article <strong>' . sc_h($row['pub_id']) . '</strong> (' . sc_h($row['stage']) . ') using <strong>' . sc_h($row['method']) . '</strong> on ' . date('d-M-Y h:i A', strtotime($row['started_at'])) . '.'
		. ' Result: <strong style="color:' . $col . ';">' . ($ok ? 'Completed' : ($status === 'failed' ? 'Failed' : 'No clear result')) . '</strong>' . (!$ok && $row['result_message'] !== '' ? ' &mdash; ' . sc_h($row['result_message']) : '') . '.</p>'
		. '<table cellpadding="0" cellspacing="0" border="0" style="font-size:14px;">'
		. $tr('Dispatched by', sc_h($row['user_name']) . ($row['user_id'] !== '' ? ' (ID ' . sc_h($row['user_id']) . ')' : ''))
		. $tr('Dispatched on', date('d-M-Y h:i A', strtotime($row['started_at'])))
		. $tr('Article', sc_h($row['pub_id']) . ' (ID ' . (int)$row['a_id'] . ')')
		. $tr('Customer / Journal', sc_h($cn) . ' / ' . sc_h($jn))
		. $tr('Stage', sc_h($row['stage']))
		. $tr('Method', sc_h($row['method']))
		. $tr('To', sc_h($row['mail_to']))
		. ($row['mail_cc'] !== '' ? $tr('Cc', sc_h($row['mail_cc'])) : '')
		. ($row['mail_bcc'] !== '' ? $tr('Bcc', sc_h($row['mail_bcc'])) : '')
		. $tr('Subject', sc_h($row['subject']))
		. $tr('Received / Due', $fmt(isset($a['recv_dt']) ? $a['recv_dt'] : '') . ' / ' . $fmt(isset($a['due_dt']) ? $a['due_dt'] : ''))
		. $tr('Pages', (int)(isset($a['page_count']) ? $a['page_count'] : 0))
		. $tr('Time taken', round((int)$row['duration_ms'] / 1000, 1) . ' s')
		. '</table>'
		. '<h3 style="margin:18px 0 6px;font-size:15px;color:#14304a;">Files sent (' . count($sent) . ')</h3>' . $sent_html
		. '<h3 style="margin:18px 0 6px;font-size:15px;color:#14304a;">Delivery folder at dispatch time (' . count($snap) . ' file' . (count($snap) == 1 ? '' : 's') . ')</h3>' . $folder_html
		. ($cfg_html !== '' ? '<h3 style="margin:18px 0 6px;font-size:15px;color:#14304a;">Move Files configuration</h3>' . $cfg_html : '')
		. ($warn_html !== '' ? '<h3 style="margin:18px 0 6px;font-size:15px;color:#14304a;">Warnings shown to the user</h3>' . $warn_html : '')
		. '<h3 style="margin:18px 0 6px;font-size:15px;color:#14304a;">What happened</h3>'
		. '<div style="font-size:12.5px;line-height:1.6;color:#33414f;">' . ($steps ? implode('<br>', array_map('sc_h', $steps)) : '-') . '</div>'
		. '<p style="color:#888;font-size:12px;margin-top:16px;">System-generated notification from the Workflow application. Recipients can be changed in Email Alerts and Logs. The full record is in Reports &gt; Dispatch Log.</p>'
		. '</div>';
	return array('subject' => $word . ': ' . $row['pub_id'] . ' (' . $cn . ' / ' . $jn . ') by ' . $row['user_name'], 'body' => $body);
}
/* send the report (to the "dispatch" recipients of Email Alerts and Logs); never throws; returns the status text that goes into the log */
function sc_dispatch_notify($db, $log_id, $a, $dir) {
	try {
		$cfg = sw_mail_get($db, 'dispatch');
		$to = sw_switch_parse_list($cfg['to']); $cc = sw_switch_parse_list($cfg['cc']); $bcc = sw_switch_parse_list($cfg['bcc']);
		if (!$to['valid'] && !$cc['valid'] && !$bcc['valid']) { return 'Not sent - no recipients configured'; }
		require_once __DIR__ . '/email-functions.php';
		$es = $db->query("SELECT * FROM email_settings WHERE id = 1"); $es = $es ? $es->fetch_assoc() : null;
		if (!$es) { return 'Not sent - email_settings missing'; }
		$r = $db->query("SELECT * FROM inw_dispatch_log WHERE id = " . (int)$log_id); $row = $r ? $r->fetch_assoc() : null;
		if (!$row) { return 'Not sent - log row missing'; }
		$m = sc_dispatch_report($db, $row, $a, $dir);
		$ok = sendMail($es['host'], $es['port'], $es['secure'], $es['from_email'], $es['from_password'], $es['from_email'], $es['from_name'],
			implode(',', $to['valid']), implode(',', $cc['valid']), implode(',', $bcc['valid']), $m['subject'], $m['body']);
		return $ok ? 'Sent' : 'Failed - see smtp_mail.log';
	} catch (Exception $e) { return 'Failed - ' . substr($e->getMessage(), 0, 150); }
}

/* ---------- dispatch log (inw_dispatch_log): what was asked, what was sent, what happened ---------- */
function sc_log_table($db) {
	$db->query("CREATE TABLE IF NOT EXISTS `inw_dispatch_log` (
		`id` int(11) NOT NULL AUTO_INCREMENT, `a_id` int(11) DEFAULT NULL, `pub_id` varchar(100) DEFAULT NULL, `cust_id` int(11) DEFAULT NULL, `j_id` int(11) DEFAULT NULL,
		`stage` varchar(25) DEFAULT NULL, `method` varchar(60) DEFAULT NULL, `user_id` varchar(20) DEFAULT NULL, `user_name` varchar(100) DEFAULT NULL, `ip` varchar(45) DEFAULT NULL,
		`mail_from` varchar(255) DEFAULT NULL, `mail_to` text, `mail_cc` text, `mail_bcc` text, `subject` text,
		`files` mediumtext, `confirm_info` mediumtext, `steps` mediumtext, `status` varchar(15) DEFAULT NULL, `result_message` text,
		`started_at` datetime DEFAULT NULL, `finished_at` datetime DEFAULT NULL, `duration_ms` int(11) DEFAULT NULL,
		PRIMARY KEY (`id`), KEY `a_id` (`a_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	$r = $db->query("SHOW COLUMNS FROM inw_dispatch_log LIKE 'folder'");   /* snapshot of the whole Delivery folder at dispatch time */
	if ($r && $r->num_rows == 0) { $db->query("ALTER TABLE inw_dispatch_log ADD COLUMN folder MEDIUMTEXT NULL"); }
	$r = $db->query("SHOW COLUMNS FROM inw_dispatch_log LIKE 'report_mail'");   /* did the dispatch report e-mail go out */
	if ($r && $r->num_rows == 0) { $db->query("ALTER TABLE inw_dispatch_log ADD COLUMN report_mail VARCHAR(255) NULL"); }
}
/* every file in the folder (top level) with size, modified time and, for a zip, the files inside; $sent = names that this dispatch sends */
function sc_folder_snapshot($dir, $sent) {
	$out = array();
	if (!is_dir($dir)) { return $out; }
	$names = array();
	foreach (new DirectoryIterator($dir) as $f) { if ($f->isFile()) { $names[] = $f->getFilename(); } }
	sort($names, SORT_NATURAL | SORT_FLAG_CASE);
	foreach (array_slice($names, 0, 300) as $n) {
		$i = sc_file_info_one($dir, $n);
		if (isset($i['entries']) && count($i['entries']) > 1000) { $i['entries_total'] = count($i['entries']); $i['entries'] = array_slice($i['entries'], 0, 1000); }
		$i['sent'] = in_array($n, $sent, true);
		$out[] = $i;
	}
	return $out;
}
$sc_steps = array(); $sc_log_id = 0; $sc_log_t0 = 0;
function sc_step($t) { global $sc_steps; $sc_steps[] = date('H:i:s') . '  ' . $t; }
/* xProof / Xperttrack / client-portal scripts upload ONE fixed file from the Delivery folder (<article id>.zip, or .pdf for some EOS reviews);
   the ticked files are not used by them. Write down which file that is, whether it exists and its size / md5, before the script runs. */
function sc_portal_target($a, $post) {
	$dir = wf_existing_dir($a['work_dir'] . $a['volume_folder'] . "/" . $a['issueorgroup_folder'] . "/" . $a['pub_id'] . "/" . $a['stage'] . "/Delivery/");
	$found = false;
	foreach (array('zip', 'pdf') as $x) {
		$f = $dir . $a['pub_id'] . '.' . $x;
		if (is_file($f)) { $found = true; sc_step('This method uploads one fixed file, not the ticked ones: ' . $a['pub_id'] . '.' . $x . ' from Delivery - ' . filesize($f) . ' bytes, modified ' . date('d-M-Y H:i', filemtime($f)) . ', md5 ' . md5_file($f)); }
	}
	if (!$found) { sc_step('This method uploads <article id>.zip from Delivery - NOT FOUND in ' . $dir); }
	if (!empty($post['attach_files'])) { sc_step('Ticked files (' . implode(', ', $post['attach_files']) . ') are NOT uploaded by this method'); }
}
function sc_log_start($db, $post, $a) {
	global $sc_log_id, $sc_log_t0, $dispatch_method;
	$sc_log_t0 = microtime(true);
	sc_log_table($db);
	$dir = wf_existing_dir(isset($post['work_dir']) ? $post['work_dir'] : '');
	$files = array();
	if (!empty($post['attach_files']) && is_array($post['attach_files'])) {
		foreach ($post['attach_files'] as $n) { $files[] = sc_file_info_one($dir, $n, true); }
	}
	foreach (array('attach_filess', 'attach_filesss') as $k) {
		if (!empty($post[$k]) && is_array($post[$k])) { foreach ($post[$k] as $n) { $files[] = array('name' => basename($n), 'note' => 'misc file (ASP)'); } }
	}
	if (!empty($_FILES['fileAttachment']['name'])) { $files[] = array('name' => $_FILES['fileAttachment']['name'], 'size' => (int)$_FILES['fileAttachment']['size'], 'note' => 'uploaded from this PC'); }
	$m = isset($dispatch_method[$post['optionRad']]) ? html_entity_decode($dispatch_method[$post['optionRad']]) : 'option ' . $post['optionRad'];
	$e = function ($v) use ($db) { return $db->real_escape_string((string)$v); };
	$db->query("INSERT INTO inw_dispatch_log (a_id,pub_id,cust_id,j_id,stage,method,user_id,user_name,ip,mail_from,mail_to,mail_cc,mail_bcc,subject,files,confirm_info,status,started_at)
		VALUES (" . (int)$a['id'] . ",'" . $e($a['pub_id']) . "'," . (int)$a['cust_id'] . "," . (int)$a['j_id'] . ",'" . $e($a['stage']) . "','" . $e($m) . "','" . $e(@$_COOKIE['user_id']) . "','" . $e(@$_COOKIE['full_name']) . "','" . $e($_SERVER['REMOTE_ADDR']) . "',
		'" . $e(@$post['txtFrom']) . "','" . $e(@$post['txtTo']) . "','" . $e(@$post['txtCc']) . "','" . $e(@$post['txtBcc']) . "','" . $e(@$post['txtSub']) . "',
		'" . $e(json_encode($files)) . "','" . $e(isset($post['sc_confirm']) ? $post['sc_confirm'] : '') . "','started',NOW())");
	$sc_log_id = (int)$db->insert_id;
	/* what the Delivery folder held at this moment, for the record (and which of it this dispatch sends) */
	$sent_names = array();
	if (!empty($post['attach_files']) && is_array($post['attach_files'])) { foreach ($post['attach_files'] as $n) { $sent_names[] = basename($n); } }
	if (in_array((int)$post['optionRad'], array(4, 5, 6), true)) { $sent_names[] = is_file($dir . $a['pub_id'] . '.zip') ? $a['pub_id'] . '.zip' : $a['pub_id'] . '.pdf'; }   /* these methods upload the article package (<id>.zip, or .pdf when there is no zip) */
	$snap = sc_folder_snapshot($dir, $sent_names);
	$db->query("UPDATE inw_dispatch_log SET folder = '" . $db->real_escape_string(json_encode($snap)) . "' WHERE id = " . (int)$sc_log_id);
	sc_step('Delivery folder at this moment: ' . count($snap) . ' file(s) - ' . implode(', ', array_map(function ($x) { return $x['name'] . (isset($x['size_text']) ? ' (' . $x['size_text'] . ', ' . $x['modified'] . ')' : ''); }, $snap)));
	sc_step('Dispatch started by ' . (isset($_COOKIE['full_name']) ? $_COOKIE['full_name'] : 'user') . ' - ' . $m . ' - ' . count($files) . ' file(s)');
	register_shutdown_function(function () use ($db, $a, $dir) {
		global $sc_log_id, $sc_log_t0, $sc_steps;
		if (!$sc_log_id) { return; }
		$loc = '';
		foreach (headers_list() as $h) { if (stripos($h, 'location:') === 0) { $loc = $h; } }
		$buf = ob_get_level() ? trim(strip_tags((string)ob_get_contents())) : '';
		$err = error_get_last();
		$status = 'finished'; $msg = '';
		if ($loc !== '' && stripos($loc, 'Successfully completed') !== false) { $status = 'completed'; $msg = 'Successfully completed'; }
		elseif ($loc !== '' && preg_match('/[?&]msg=([^&]*)/', $loc, $mm)) { $status = 'failed'; $msg = urldecode($mm[1]); }
		elseif ($err && in_array($err['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR), true)) { $status = 'failed'; $msg = $err['message']; }
		elseif (preg_match('/Mailer Error|Cannot upload|Cannot initiate|Error while|Cannot delete/i', $buf, $mm)) { $status = 'failed'; $msg = substr($buf, 0, 300); }
		if ($status === 'failed') { sc_step('FAILED: ' . $msg); } elseif ($status === 'completed') { sc_step('Completed: status updated and dispatch history written'); }
		elseif ($buf !== '') { sc_step('Server output: ' . substr($buf, 0, 200)); }
		$q = "UPDATE inw_dispatch_log SET status='" . $db->real_escape_string($status) . "', result_message='" . $db->real_escape_string($msg) . "', steps='" . $db->real_escape_string(json_encode($sc_steps)) . "',
			finished_at=NOW(), duration_ms=" . (int)round((microtime(true) - $sc_log_t0) * 1000) . " WHERE id=" . (int)$sc_log_id;
		$db->query($q);
		/* the dispatch report e-mail (who, what, files and their contents, outcome) - after the outcome is saved so it reports it */
		$rm = sc_dispatch_notify($db, $sc_log_id, $a, $dir);
		$db->query("UPDATE inw_dispatch_log SET report_mail='" . $db->real_escape_string($rm) . "' WHERE id=" . (int)$sc_log_id);
	});
}

/* Package check used by the page before Send: POST sc_file_info=1, id, files[] -> JSON (files with date / zip contents / pdf pages, Move Files configuration) */
if (isset($_POST['sc_file_info'])) {
	while (ob_get_level()) { ob_end_clean(); }
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-store');
	$r = $db->query("SELECT * FROM inw_inward_dtl WHERE id = " . (int)$_POST['id']);
	$a = $r ? $r->fetch_assoc() : null;
	if (!$a) { echo json_encode(array('files' => array(), 'error' => 'Article not found.')); exit; }
	$dir = sc_article_dir($db, $a);
	$out = array();
	$names = isset($_POST['files']) && is_array($_POST['files']) ? $_POST['files'] : array();
	if (!empty($_POST['fixed'])) {   /* methods that upload <article id>.zip / .pdf themselves */
		$names = array();
		foreach (array('zip', 'pdf') as $x) { if (is_file($dir . $a['pub_id'] . '.' . $x)) { $names[] = $a['pub_id'] . '.' . $x; } }
		if (!$names) { $names[] = $a['pub_id'] . '.zip'; }
	}
	foreach ($names as $n) { $out[] = sc_file_info_one($dir, $n); }
	$cfg = sc_move_config($db, $a, $dir);
	$rows = array();
	foreach ($cfg['exts'] as $x => $from) { $rows[] = array('ext' => $x, 'from' => array_keys($from), 'found' => isset($cfg['have'][$x]) ? $cfg['have'][$x] : array()); }
	echo json_encode(array('files' => $out, 'config' => array('configured' => $cfg['configured'], 'rows' => $rows, 'other' => $cfg['other'])));
	exit;
}

require_once __DIR__ . '/sw_switch_lib.php';   // same helpers / table / recipients as the Start Work "Use old version"

/* "Use old version": the user goes back to send_to_client.php. A reason is mandatory (screenshots / files may be attached); it is logged in the same
   table as the Start Work switch (sw_switch_log, page = 'send_to_client') and e-mailed to the same To / CC / BCC (wf_settings, screen: sw_switch_settings.php).
   POST (multipart) sc_switch_legacy=1, aid, reason, att[] */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sc_switch_legacy'])) {
	while (ob_get_level()) { ob_end_clean(); }
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-store');
	$reason = trim((string)(isset($_POST['reason']) ? $_POST['reason'] : ''));
	if (function_exists('mb_strlen') ? mb_strlen($reason, 'UTF-8') < 10 : strlen($reason) < 10) {
		echo json_encode(array('ok' => false, 'message' => 'Please tell us why you are switching (at least 10 characters).')); exit;
	}
	if (strlen($reason) > 2000) { $reason = substr($reason, 0, 2000); }
	$files = sw_switch_collect_files();
	if (isset($files['error'])) { echo json_encode(array('ok' => false, 'message' => $files['error'])); exit; }
	sw_switch_ensure($db);
	$r = $db->query("SHOW COLUMNS FROM sw_switch_log LIKE 'page'");   /* which page the switch came from (older rows are Start Work) */
	if ($r && $r->num_rows == 0) { $db->query("ALTER TABLE sw_switch_log ADD COLUMN page VARCHAR(30) NOT NULL DEFAULT 'start_work'"); }
	$a = array('id' => (int)$_REQUEST['aid'], 'pub_id' => '', 'stage' => '', 'cust_id' => 0, 'j_id' => 0, 'department_id' => 0);
	$r = $db->query("SELECT id, pub_id, stage, cust_id, j_id, department_id, status FROM inw_inward_dtl WHERE id = " . (int)$_REQUEST['aid']);
	if ($r && ($row = $r->fetch_assoc())) { $a = $row; }
	$cn = ''; $jn = ''; $dn = '';
	$r = $db->query("SELECT cust_name FROM adm_customer_master WHERE id = " . (int)$a['cust_id']); if ($r && ($x = $r->fetch_assoc())) { $cn = $x['cust_name']; }
	$r = $db->query("SELECT j_name, j_code FROM adm_journals WHERE j_id = " . (int)$a['j_id']);   if ($r && ($x = $r->fetch_assoc())) { $jn = $x['j_name']; }
	$r = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = " . (int)$a['department_id']); if ($r && ($x = $r->fetch_assoc())) { $dn = $x['dept_name']; }
	if ($dn === '' && isset($a['status'])) { $dn = (string)$a['status']; }
	$uname = isset($_COOKIE['full_name']) ? $_COOKIE['full_name'] : ''; $uid = isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : '';

	/* log first (the attachments are filed under its id), then fill in the files and the e-mail result */
	$db->query("INSERT INTO sw_switch_log (article_id, pub_id, stage, dept, user_id, user_name, reason, mail_status, created_at, page) VALUES ("
		. (int)$a['id'] . ", '" . $db->real_escape_string($a['pub_id']) . "', '" . $db->real_escape_string($a['stage']) . "', '" . $db->real_escape_string($dn) . "', '"
		. $db->real_escape_string($uid) . "', '" . $db->real_escape_string($uname) . "', '" . $db->real_escape_string($reason) . "', 'Pending', NOW(), 'send_to_client')");
	$log_id = (int)$db->insert_id;
	$meta = sw_switch_store_files($log_id, $files);
	if ($meta) { $db->query("UPDATE sw_switch_log SET files = '" . $db->real_escape_string(json_encode($meta)) . "' WHERE id = " . $log_id); }

	$cfg = sw_switch_get($db);
	$to = sw_switch_parse_list($cfg['to']); $cc = sw_switch_parse_list($cfg['cc']); $bcc = sw_switch_parse_list($cfg['bcc']);
	$mail_status = '';
	if (!$to['valid'] && !$cc['valid'] && !$bcc['valid']) {
		$mail_status = 'Not sent - no recipients configured';
	} else {
		try {
			require_once __DIR__ . '/email-functions.php';
			$es = $db->query("SELECT * FROM email_settings WHERE id = 1"); $es = $es ? $es->fetch_assoc() : null;
			if (!$es) { $mail_status = 'Not sent - email_settings missing'; }
			else {
				$h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
				$att_paths = array(); $att_names = array();
				foreach ($meta as $m) { $att_paths[] = sw_switch_dir() . '/' . $m['s']; $att_names[] = $h($m['n']); }
				$body = '<p>Hi Team,</p><p>A user has switched back from the <strong>new Project Dispatch (v2)</strong> to the <strong>old Project Dispatch</strong> page and gave the reason below.</p>'
					. '<table cellpadding="4" cellspacing="0" border="0">'
					. '<tr><td><strong>User</strong></td><td>' . $h($uname) . ($uid !== '' ? ' (ID ' . $h($uid) . ')' : '') . '</td></tr>'
					. '<tr><td><strong>Article</strong></td><td>' . $h($a['pub_id']) . ' (ID ' . (int)$a['id'] . ')</td></tr>'
					. '<tr><td><strong>Customer</strong></td><td>' . $h($cn) . '</td></tr>'
					. '<tr><td><strong>Journal</strong></td><td>' . $h($jn) . '</td></tr>'
					. '<tr><td><strong>Stage / Department</strong></td><td>' . $h($a['stage']) . ' / ' . $h($dn) . '</td></tr>'
					. '<tr><td><strong>Switched on</strong></td><td>' . date('d-M-Y h:i A') . '</td></tr>'
					. '<tr><td style="vertical-align:top;"><strong>Reason</strong></td><td>' . nl2br($h($reason)) . '</td></tr>'
					. ($att_names ? '<tr><td style="vertical-align:top;"><strong>Attachments</strong></td><td>' . implode('<br>', $att_names) . '</td></tr>' : '')
					. '</table>'
					. '<p>Please look into the issue so the new page can be used for this case.</p>'
					. '<p style="color:#888;font-size:12px;">System-generated notification from the Workflow application. Recipients can be changed in Email Alerts and Logs.</p>';
				$ok = sendMail($es['host'], $es['port'], $es['secure'], $es['from_email'], $es['from_password'], $es['from_email'], $es['from_name'],
					implode(',', $to['valid']), implode(',', $cc['valid']), implode(',', $bcc['valid']),
					'PROJECT DISPATCH: ' . $uname . ' switched to the old page - ' . $a['pub_id'], $body, $att_paths);
				$mail_status = $ok ? 'Sent' : 'Failed - see smtp_mail.log';
			}
		} catch (Exception $e) { $mail_status = 'Failed - ' . substr($e->getMessage(), 0, 150); }
	}
	$db->query("UPDATE sw_switch_log SET mail_status = '" . $db->real_escape_string($mail_status) . "' WHERE id = " . $log_id);
	if ((int)$a['id'] > 0) {   /* send_to_client.php lets this user open the old page for this article */
		if (session_status() === PHP_SESSION_NONE) { @session_start(); }
		$_SESSION['sc_legacy_ok'][(int)$a['id']] = time();
	}
	echo json_encode(array('ok' => true, 'mail' => $mail_status)); exit;
}

$dispatch_method = array(1 => 'FTP upload &amp; mail', 2 => 'Only FTP &amp; manual mail', 3 => 'Attach file &amp; mail', 4 => 'xProof upload &amp; mail', 5 => 'EProof', 6 => 'Xperttrack');

if (isset($_POST['action']) && $_POST['action'] == 'update') {
    foreach ($_POST as $key => $value) {
        $post[$key] = $value;
    }
	
	$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = " . $_REQUEST['id'] . " ");
    $row_cnt = 1;
    $toatl_count = $result->num_rows;
    if ($toatl_count > 0) {
        $app_res = $result->fetch_assoc();
    }
	
		/// Get the Client Name ////
    $query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = " . $app_res['cust_id'] . ";";
    $customers = $db->query($query) or ( $db->error);
    $customer_info = $customers->fetch_assoc();
	sc_log_start($db, $post, $app_res);   // dispatch log: who sent what to whom, and how it ended
	
	if($customer_info['cust_name'] == 'PeerJ')
	{
		
		include ("PeerJ_sent_to_client.php");
	}
	else if($customer_info['cust_name'] == 'Sunrise-NOW')
	{
		
		include ("Sunrise_NOW_sent_to_client.php");
	}
	else
	{
			if ($_POST['optionRad'] == 1 || $_POST['optionRad'] == 2 || $_POST['optionRad'] == 3) 
	{
		  if ($_FILES['fileAttachment']['name'] == "" && sizeof($_POST['attach_files']) == 0) 
		  {
			
			 header("location:send_to_client_v2.php?id=" . $post['id'] . "&msg=File missed, Please check it.");
			 exit;
		  
		  }
	}
	
		 
	if ($_POST['optionRad'] == 4)
	{

        $stage_r=preg_replace("/REV([0-9]+)/","REV",$post['stage']);
        $stage_drft=preg_replace("/DRAFT([0-9]+)/","DRAFT",$post['stage']);

		
		 // To check proof folder exist or not
		if(!is_dir($_POST['work_dir'])) { 
			 header("location:send_to_client_v2.php?id=" . $post['id'] . "&msg=Delivery folder missed, Please check it.");
			 exit;
		}
		
		
		if($post['cust_id']!=66)
		{
			if (!file_exists($_POST['work_dir']."/".$_POST['article_id'].".zip"))  
			{ 
			 header("location:send_to_client_v2.php?id=" . $post['id'] . "&msg=Package missed in Delivery folder, Please check it.");
			 exit;
			}
		}
		elseif($post['cust_id']==66 && $post['stage']=='FP')
		{
			if (!file_exists($_POST['work_dir']."/".$_POST['article_id'].".zip"))  
			{ 
			 header("location:send_to_client_v2.php?id=" . $post['id'] . "&msg=Package missed in Delivery folder, Please check it.");
			 exit;
			}
		}
		elseif($post['cust_id']==66 && $stage_drft=='DRAFT')
		{
			if (!file_exists($_POST['work_dir']."/".$_POST['article_id'].".zip"))  
			{ 
			 header("location:send_to_client_v2.php?id=" . $post['id'] . "&msg=Package missed in Delivery folder, Please check it.");
			 exit;
			}
		}
		elseif($post['cust_id']==66 && $post['stage']!='FP' && $stage_drft!='DRAFT')
		{
			
			if($post['corrections']=='yes' || $post['corrections']=='')
			{
				if (!file_exists($_POST['work_dir']."/".$_POST['article_id'].".pdf"))  
				{ 
				 header("location:send_to_client_v2.php?id=" . $post['id'] . "&msg=PDF File missed in Delivery folder, Please check it.");
				 exit;
				}
			}
            elseif($app_res['wf_id']=='147' && $stage_r=='REV' &&  $post['stage']!='REV1')
            {
                if (!file_exists($_POST['work_dir']."/".$_POST['article_id'].".pdf"))  
				{ 
				 header("location:send_to_client_v2.php?id=" . $post['id'] . "&msg=PDF File missed in Delivery folder, Please check it.");
				 exit;
				}
            }
             elseif($app_res['wf_id']=='148' && $stage_r=='REV')
            {
                if (!file_exists($_POST['work_dir']."/".$_POST['article_id'].".zip"))  
				{ 
				 header("location:send_to_client_v2.php?id=" . $post['id'] . "&msg=Package missed in Delivery folder, Please check it.");
				 exit;
				}
            }
			else
			{
				if (!file_exists($_POST['work_dir']."/".$_POST['article_id'].".zip"))  
				{ 
				 header("location:send_to_client_v2.php?id=" . $post['id'] . "&msg=Package missed in Delivery folder, Please check it.");
				 exit;
				}	
			}
		}
		
		
		if($post['cust_id']!=66)
		{
			if($post['txtTo'] != $app_res['corresponding_mail'] && $post['txtToName'] != $app_res['corresponding_author'])
			{
				 header("location:send_to_client_v2.php?id=" . $post['id'] . "&msg=Please check author name and email id!.");
				exit;
			}
		}
		elseif($post['cust_id']==66 && $post['stage']=='FP')
		{
			if($post['txtTo'] != $app_res['corresponding_mail'] && $post['txtToName'] != $app_res['corresponding_author'])
			{
				 header("location:send_to_client_v2.php?id=" . $post['id'] . "&msg=Please check author name and email id!.");
				exit;
			}
		}
		

		
	}
	
	
    $result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = " . $post['id'] . " ");
    $row_cnt = 1;
    $toatl_count = $result->num_rows;
    if ($toatl_count > 0) {
        $app_res = $result->fetch_assoc();
    }
	$query = "SELECT * FROM `adm_journals` WHERE j_id = " . $app_res['j_id'] . ";";
    $journal = $db->query($query) or ( $db->error);
    $journal_info = $journal->fetch_assoc();
    /* $set_status = "`status` ='Client Review',";
    if ($_POST['stage'] == 'FIN') {
        $set_status = "`status` ='Completed',";
    }

    $prj_update_query = "UPDATE inw_inward_dtl SET " . $set_status . " `department_id` ='0',`assign_department_id` ='0' WHERE id = " . $post['id'] . "";

    //echo $prj_update_query; exit;
    $db->query($prj_update_query); */

///////////////////////////////////////////////////
/////// Upload the files to Server START HERE /////
///////////////////////////////////////////////////
// get FTP access parameters

    if ($_POST['optionRad'] == 1 || $_POST['optionRad'] == 2) {

        $current_stage = preg_replace('/[0-9]+/', '', $_POST['stage']);



        $host = $_POST['FtpHost'];

        $user = $_POST['FtpUser'];

        $pass = $_POST['FtpPass'];

        $destDir = $_POST['FtpPath'];

        $workDir = "uploads"; // define this as per local system
// get temporary file name for the uploaded file

        $tmpName = basename($_FILES['fileAttachment']['name']);

// copy uploaded file into current directory
        if ($_FILES['fileAttachment']['name'] != "") {
            move_uploaded_file($_FILES['fileAttachment']['tmp_name'], $workDir . "/" . $tmpName) or die("Cannot move uploaded file to working directory");
        }

// open connection

        $conn = ftp_connect($host) or die("Cannot initiate connection to host");

// send access parameters

        ftp_login($conn, $user, $pass) or die("Cannot login");

		// Switched to passive mode after logging in
			ftp_pasv($conn, true);

///// Create DIR ///
        if ($current_stage == 'FP') {
            $destDir .= "/Firstproofs/";
        } else if ($current_stage == 'REV') {
            $destDir .= "/Revises/";
        } else {
            $destDir .= "/" . $current_stage . "/";
        }

// try to create directory $dir
        if (ftp_mkdir($conn, $destDir)) {
            echo "Successfully created $destDir";
        } else {
            echo "Error while creating $destDir";
        }

///// Create Month and Year DIR ///

        $destDir .= date('Y') . "/";

// try to create directory $dir
        if (ftp_mkdir($conn, $destDir)) {
            echo "Successfully created $destDir";
        } else {
            echo "Error while creating $destDir";
        }

///// Create Month and DATE DIR ///

        $destDir .= date('md');

// try to create directory $dir
        if (ftp_mkdir($conn, $destDir)) {
            echo "Successfully created $destDir";
        } else {
            echo "Error while creating $destDir";
        }
////// END Create DIR ////
        $email_ftp_path = '';

// perform file upload
        $sc_ftp_bad = array();   /* files that did not arrive intact (checked by asking the FTP server for the size) */
        if ($_FILES['fileAttachment']['name'] != "") {
            $upload = ftp_put($conn, $destDir . "/" . $_FILES['fileAttachment']['name'], $workDir . "/" . $tmpName, FTP_BINARY);
            $sc_rs = @ftp_size($conn, $destDir . "/" . $_FILES['fileAttachment']['name']); $sc_ls = @filesize($workDir . "/" . $tmpName);
            sc_step('FTP upload (picked from this PC) ' . $_FILES['fileAttachment']['name'] . ': ' . ($upload ? 'sent' : 'FAILED') . ' - server size ' . $sc_rs . ' / local size ' . $sc_ls . ' - ' . (($upload && $sc_rs == $sc_ls) ? 'VERIFIED' : 'NOT VERIFIED'));
            if (!$upload || $sc_rs != $sc_ls) { $sc_ftp_bad[] = $_FILES['fileAttachment']['name']; }

            $email_ftp_path .= "ftp://" . $user . "@" . $host . $destDir . "/" . $_FILES['fileAttachment']['name'] . "<br>";
            // delete local copy of uploaded file
            unlink($workDir . "/" . $tmpName) or die("Cannot delete uploaded file from working directory — manual deletion recommended");
        }

        foreach ($_POST['attach_files'] as $file) {
            $upload = ftp_put($conn, $destDir . "/" . $file, $_POST['work_dir'] . "/" . $file, FTP_BINARY);
            $sc_rs = @ftp_size($conn, $destDir . "/" . $file); $sc_ls = @filesize($_POST['work_dir'] . "/" . $file);
            sc_step('FTP upload ' . $file . ' to ' . $host . $destDir . ': ' . ($upload ? 'sent' : 'FAILED') . ' - server size ' . $sc_rs . ' / local size ' . $sc_ls . ' - ' . (($upload && $sc_rs == $sc_ls) ? 'VERIFIED' : 'NOT VERIFIED'));
            if (!$upload || $sc_rs != $sc_ls) { $sc_ftp_bad[] = $file; }

            $email_ftp_path .= "ftp://" . $user . "@" . $host . $destDir . "/" . $file . "<br>";
        }

//var_dump($upload);
// check upload status
// display message

        if ($sc_ftp_bad) {
            /* v2: do not carry on to the mail / status change when a file did not arrive intact */
            ftp_close($conn);
            sc_step('Stopped: ' . count($sc_ftp_bad) . ' file(s) not verified on the FTP server');
            header("location:send_to_client_v2.php?id=" . $post['id'] . "&msg=FTP upload could not be verified for: " . implode(', ', $sc_ftp_bad) . ". Please try again.");
            exit;
        }
        if (!$upload) {

            echo "Cannot upload to FTP";
        } else {

            echo "Upload complete";
        }


// close the FTP stream

        ftp_close($conn);
    }

//exit;
////////////////////////////////////////
////// End files upload to server //////
////////////////////////////////////////			
////// send mail to clinet with attachment ////
    /* $smtp_host 		= "ssl://smtp.gmail.com";
      $smtp_user 		= "trans.robatic.msg@gmail.com";
      $smtp_password 	= "Technet16";
      $smtp_port 		= "465";
      $smtp_ssl 		= "true";

      $smtp_host 		= "smtp.office365.com";
      $smtp_port 		= "587";
      $smtp_ssl 		= "false";
      $smtp_user 		= $_POST['txtFrom'];
      $smtp_password 	= $_POST['txtMpass']; */

    if ($_POST['optionRad'] == 1 || $_POST['optionRad'] == 3) {

        $mail_from = $_POST['txtFrom'];
        $mail_from_name = "";


        $mail_to_name = "";

        date_default_timezone_set('Asia/Kolkata');

        $body = str_replace('ARTICLEID', $_POST['article_id'], $_POST['txtBody']);
        $body = str_replace('CLIENTNAME', $_POST['cust_name'], $body);
        $body = str_replace('AUTHORNAME', $_POST['txtToName'], $body);
		$body= str_replace("##AuthorNames##",$_POST['txtToName'],$body);

		
//echo $app_res['cust_id'];exit;
        if ($app_res['cust_id'] == 39 || $app_res['cust_id'] == 37) {
            $body = str_replace('FTPFILEPATH', $destDir . "/" . $_FILES['fileAttachment']['name'], $body);
        } else {
            $body = str_replace('FTPFILEPATH', $email_ftp_path, $body);
        }

        $querysrc = $_POST['work_dir'];
        $querystr = "";
        $i = new DirectoryIterator($querysrc);
        foreach ($i as $f) {
            if ($f->isFile()) {

                $filename = $f->getFilename();
                if (strpos($filename, '_Query.txt') !== false) {
                    $file = fopen($querysrc . $filename, "r");
                    while (!feof($file)) {
                        $querystr .= str_replace("\n", "<br/>", fgets($file));
                    }
                    $querystr = "---------- Query ----------<br/>" . $querystr . "<br/><br/>---------------------------<br/>";
                    break;
                }
            }
        }

        $body = str_replace('AUTHORQUERY', $querystr, $body);
//exit;


		$sub_mail= $journal_info['j_code']."--".substr($app_res['article_title'],0,40)."... [".$app_res['pub_id']."]";

        include_once("smtpmail/phpmailer/class.phpmailer.php");

        $mail = new PHPMailer();
        $mail->IsSMTP();		
		 if($app_res['cust_id']!=37)
		{
        $mail->CharSet = 'UTF-8';
        $mail->Host = "outlook.office365.com";
		$mail->Port = 587;
		$mail->SMTPSecure = 'tls';

		}
		else
		{
		$mail->CharSet = 'UTF-8';
        $mail->Host = "smtp.gmail.com";
		$mail->Port = 465;
		$mail->SMTPSecure = "ssl";	
        } 
		/* $mail->CharSet = 'UTF-8';
        $mail->Host = "outlook.office365.com";
		$mail->Port = 587; */
		$mail->SMTPAuth = true;		 
        $mail->Username = $mail_from;
        $mail->Password = $_POST['txtMpass'];
        $mail->From = $mail_from;
        $mail->FromName = $mail_from;
        $mail->isHTML(true);
		 if($app_res['cust_id']!=37)
		{
        $mail->Subject = str_replace('ARTICLEID', $_POST['article_id'], $_POST['txtSub']);
		}
		else
		{
		//$mail->Subject = str_replace('##FileName##',$sub_mail, $_POST['txtSub']);
		//$mail->Subject =$_POST['txtSub'];
		$mail->Subject ='=?UTF-8?B?'.base64_encode($_POST['txtSub']).'?=';

		}
        $mail->Body = $body;


//// Send mail to more than one user ///
        $mail_to = explode(',', $_POST['txtTo']);
        foreach ($mail_to as $email) {
            if ($email != '') {
                $mail->addAddress($email);
            }
        }
/////////// End here send mail ////////


        $mail->AltBody = ""; // optional, comment out and test

        $mail_tocc = explode(',', $_POST['txtCc']);
        foreach ($mail_tocc as $email) {
            if ($email != '') {
                $mail->AddCC($email);
            }
        }

        $mail_tobcc = explode(',', $_POST['txtBcc']);
        foreach ($mail_tobcc as $email) {
            if ($email != '') {
                $mail->AddBCC($email);
            }
        }

         if ($_POST['optionRad'] == 3 || $_POST['optionRad'] == 4) {
            // $mail->AddAttachment($_POST['work_dir']."Rouzies Onyemah final draft (2).pdf");      // attachment
            if ($_FILES['fileAttachment']['name'] != "") {
                $mail->AddAttachment($_FILES['fileAttachment']['tmp_name'], $_FILES['fileAttachment']['name']);
            }
            //$mail->AddAttachment("images/phpmailer_mini.gif"); // attachment
            foreach ($_POST['attach_files'] as $file) {
                if (!is_file($_POST['work_dir'] . $file)) {
                    sc_step('Attachment ' . $file . ' is missing in the folder - stopped');
                    header("location:send_to_client_v2.php?id=" . $post['id'] . "&msg=Attachment missing in the folder: " . $file . ". Please check it.");
                    exit;
                }
                $mail->AddAttachment($_POST['work_dir'] . $file);
                sc_step('Attached to the mail: ' . $file . ' (' . filesize($_POST['work_dir'] . $file) . ' bytes)');
            }
			 foreach ($_POST['attach_filess'] as $file) {
                $mail->AddAttachment("E:/New_Workflow_Files/JOURNAL/downloads/JOURNALS/ASP/misc/" . $file);
            }
			foreach ($_POST['attach_filesss'] as $file) {
                $mail->AddAttachment("E:/New_Workflow_Files/JOURNAL/downloads/JOURNALS/ASP/misc/" . $file);
            }
        } 

        if (!$mail->Send()) {
            sc_step('Mail to ' . $_POST['txtTo'] . ': FAILED - ' . $mail->ErrorInfo);
            echo "Mailer Error: " . $mail->ErrorInfo;
			header("location:".$_COOKIE['set_url']."/send_to_client_v2.php?id=" . $post['id'] . "&msg=Mailer Sending Error. Please try again");
			 exit;
        } else {	
            sc_step('Mail sent to ' . $_POST['txtTo'] . (trim($_POST['txtCc']) !== '' ? ' (cc ' . $_POST['txtCc'] . ')' : '') . ' with subject "' . $_POST['txtSub'] . '"');
            echo "Message sent!";
        }
        //exit;
    }

    //// FTP UPLOAD TO LINUX SERVER FOR CLIENT PORTAL AND LATEX EDITOR -- START
    if($_POST['optionRad'] == 1 && $app_res['cust_id']==7)
    {
        if($post['stage']=='FP' && $app_res['pub_id']=='EPL_6767')
        {
     
         //// latex editor ftp upload
        include('linux_sif_latex_ftp_upload.php');

         //// client portal  ftp upload
        include('linux_sif_clientportal_ftp_upload.php');
        }
    }
	
	
	
	
	//// XPERTTRACK SYSTEM UPLOAD FOR IAP CUSTOMERS
	
	if ($_POST['optionRad'] == 6) 
	{
		sc_portal_target($app_res, $post);
		if($app_res['cust_id'] == 57 || $app_res['cust_id'] == 58)
		{
			
					
            $stage_check=preg_replace("/FIN([0-9]+)/","FIN",$app_res['stage']);
            $stage_pmc=preg_replace("/PMC([0-9]+)/","PMC",$app_res['stage']);
			
			if($app_res['stage']=='FP')
			{
                include ('linux_iap_client_portal_fp.php');
			}
			elseif($stage_check=='FIN')
            {
                include ('linux_iap_client_portal_fin.php');
            }
			elseif($stage_pmc=='PMC')
            {
                include ('linux_iap_client_portal_pmc.php');
            }
            else
            {
                include ('linux_iap_client_portal_rev.php');
            }
		}	
		
		if($app_res['cust_id'] == 53)
		{
			
					
            $stage_check=preg_replace("/REV([0-9]+)/","REV",$app_res['stage']);
           // $stage_pmc=preg_replace("/PMC([0-9]+)/","PMC",$app_res['stage']);
			
			if($app_res['stage']=='FP')
			{
                include ('linux_now_client_portal_fp.php');
			}
			elseif($stage_check=='REV')
            {
                include ('linux_now_client_portal_rev.php');
            }
            else
            {
                include ('linux_now_client_portal_fin.php');
            }
		}
		
	}
	
	//// XPERTTRACK SYSTEM UPLOAD FOR IAP CUSTOMERS


    //// FTP UPLOAD TO LINUX SERVER FOR CLIENT PORTAL AND LATEX EDITOR -- END


    if ($_POST['optionRad'] == 4) 
	{
		sc_portal_target($app_res, $post);

		if($app_res['cust_id'] == 43)
		{
			include ('jmir_xpertproofing.php');
			// include ('jmir_upload_file.php');
		}
		else if($app_res['cust_id'] == 42 && $journal_info['j_proofing_system_server']=='AWS1')
		{
			include ('tsp_xpertproofing.php');
		}
		else if($app_res['cust_id'] == 84)
		{
			include ('aws_xpertproofing_xpro.php');
		}
		else if($app_res['cust_id'] == 42  && $journal_info['j_proofing_system_server']=='AWS2' && $app_res['xperttrack_sync']=='0')
		{
			include ('aws_xpertproofing_xpro.php');
		}
		else if($app_res['cust_id'] == 42  && $journal_info['j_proofing_system_server']=='AWS2' && $app_res['xperttrack_sync']=='1')
		{
			include ('aws_xpertproofing_xpro_xperttrack.php');
		}
		else if($app_res['cust_id'] == 72  && $journal_info['j_proofing_system_server']=='AWS2' && $app_res['xperttrack_sync']=='1')
		{
			include ('aws_xpertproofing_xpro_xperttrack_ksu.php');
		}
        else if($app_res['cust_id'] == 66  && $journal_info['j_proofing_system_server']=='AWS1' && $app_res['stage']== 'FP')
		{
			include ('aws_eos_xpertproofing_xpro.php');
		}
        else if($app_res['cust_id'] == 66  && $journal_info['j_proofing_system_server']=='AWS2' && $app_res['stage']== 'FP')
		{
			include ('aws_eos_xpertproofing_xpro.php');
		}
		
		else if($app_res['cust_id'] == 42 && $journal_info['j_proofing_system_server']=='GODADDY' && $app_res['stage']== 'FP')
		{
			include ('godaddy_xpertproofing.php');
		}
        else if($app_res['cust_id'] == 76 && $app_res['stage']== 'FP')
		{
			//echo "test dev jrnl";exit;
			include('linux_devjrnl_xpertproofing_xpro.php');
		}
		 else if($app_res['cust_id'] == 77 && $app_res['stage']== 'FP')
		{
			//echo "acg jrnl";exit;
			include('aws_xpertproofing_xpro_xperttrack_acg.php');
		}
		 else if($app_res['cust_id'] == 79 && $app_res['stage']== 'FP')
		{
			//echo "csiro jrnl";exit;
			//include('aws_xpertproofing_xpro_xperttrack_csiro.php');
			include('xpertproofing_xpro_xperttrack_csiro.php');
		}
		else if ($app_res['cust_id'] == 66 && $app_res['stage']!= 'FP') {
			
            $stage_check=preg_replace("/FIN([0-9]+)/","FIN",$app_res['stage']);
            $stage_check_draft=preg_replace("/DRAFT([0-9]+)/","DRAFT",$app_res['stage']);
			
			 if($stage_check_draft=='DRAFT')
            {
                include ('linux_eos_client_portal_draft.php');

            }
            elseif($stage_check=='FIN')
            {
                include ('linux_eos_client_portal_fin.php');

            }
            else
            {
                include ('linux_eos_client_portal_rev.php');

            }
			//include ('aws_eos_client_portal_rev.php');
            
        }
		else if ($app_res['cust_id'] == 79 && $app_res['stage']!= 'FP') {
			
            $stage_check=preg_replace("/FIN([0-9]+)/","FIN",$app_res['stage']);
            $stage_check_draft=preg_replace("/DRAFT([0-9]+)/","DRAFT",$app_res['stage']);
			
			 if($stage_check_draft=='DRAFT')
            {
                include ('linux_csiro_client_portal_draft.php');

            }
            elseif($stage_check=='FIN')
            {
                include ('linux_csiro_client_portal_fin.php');

            }
            else
            {
                include ('linux_csiro_client_portal_rev.php');

            }
			//include ('aws_eos_client_portal_rev.php');
            
        }
		else if($app_res['cust_id'] == 42 && ($journal_info['j_proofing_system_server']=='' || $journal_info['j_proofing_system_server']=='NULL'))
		{
			include ('tsp_xpertproofing.php');
			// include ('tsp_proofing.php');
		}
		else if($app_res['cust_id'] == 44)
		{
			include ('plos_xpertproofing.php');
			// include ('tsp_proofing.php');
		}
	}
	
	if ($_POST['optionRad'] == 5) {
		
		
		if($app_res['cust_id'] == 34 && $_POST['stage'] == "FP")
		{
			include ('now_eproofing.php');
		
		}
		
	}


/// Here Update Article status to Client Review

	$set_status = "`status` ='Client Review',";
    $user_working_status = '<span class="label label-sm label-success">Client Review</span>';

    if ($_POST['stage'] == 'FIN') {
        $set_status = "`status` ='Completed',";

    }

    $prj_update_query = "UPDATE inw_inward_dtl SET " . $set_status . " `department_id` ='0',`assign_department_id` ='0',`user_working_status`='".$user_working_status."' WHERE id = " . $post['id'] . "";

    //echo $prj_update_query; exit;
    $db->query($prj_update_query);


///// Add records in Artile Dispatch history ///

    $dispat_qry = "INSERT INTO `inw_dispatch_history` (`cust_id`, `j_id`, `a_id`, `from_mail`, `to_mail`, `mail_text`, `sent_by`, `stage`, `sent_date`,dispatch_type) VALUES ('" . $app_res['cust_id'] . "', '" . $app_res['j_id'] . "', '" . $app_res['id'] . "', '" . $_POST['txtFrom'] . "', '" . $_POST['txtTo'] . "', '" . $body . "', '" . $_COOKIE['user_id'] . "', '" . $app_res['stage'] . "', NOW(),'" . $dispatch_method[$_POST['optionRad']] . "')";
    $db->query($dispat_qry);


    header("location:".$_COOKIE['set_url']."/underdispatcharticles?msg=Successfully completed");
}
}
/////////////// Update completed ///////////////

if (isset($_REQUEST['id'])) {
	
	
    $result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = " . $_REQUEST['id'] . " ");
    $row_cnt = 1;
    $toatl_count = $result->num_rows;
    if ($toatl_count > 0) {
        $app_res = $result->fetch_assoc();
        $app_res['volume_folder'] = $app_res['volume_folder'] . $app_res['issueorgroup_folder'];
    }

/// Get the Client Name ////
    $query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = " . $app_res['cust_id'] . ";";
    $customers = $db->query($query) or ( $db->error);
    $customer_info = $customers->fetch_assoc();

/// Get the journal name ///
    $query = "SELECT * FROM `adm_journals` WHERE j_id = " . $app_res['j_id'] . ";";
    $journal = $db->query($query) or ( $db->error);
    $journal_info = $journal->fetch_assoc();

    /// Get Email Subject and Body ///
    $query = "SELECT * FROM `inw_dispatch_mail_master` WHERE j_id = " . $app_res['j_id'] . " AND cust_id=" . $app_res['cust_id'] . " AND stage ='" . preg_replace('/[0-9]+/', '', $app_res['stage']) . "';";
    $d_mail = $db->query($query) or ( $db->error);
    $d_mail_info = $d_mail->fetch_assoc();
	
	/// Changes the Subject and Body Text for NOW Publisher ///
	if($app_res['cust_id'] == 42)
	{
		$d_mail_info['subject'] = str_replace("##FileName##",$app_res['pub_id'],$d_mail_info['subject']);
		$d_mail_info['message'] = str_replace("##FileName##",$app_res['pub_id'],$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##EPROOFING_LINK##",EPROOFING_URL,$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##EPROOFING_KEY##",base64_encode($app_res['id']),$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##AuthorNames##",$app_res['corresponding_author'],$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##ARTICLETITLE##",$app_res['article_title'],$d_mail_info['message']);
		
		//##AuthorNames##
		
	}
		/// Changes the Subject and Body Text for NOW Publisher ///
if($customer_info['cust_name'] == "Sunrise-NOW" || $customer_info['cust_name'] == "TSP")
	{
		$d_mail_info['subject'] = str_replace("##FileName##",$app_res['pub_id'],$d_mail_info['subject']);
		$d_mail_info['message'] = str_replace("##FileName##",$app_res['pub_id'],$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##EPROOFING_LINK##",EPROOFING_URL,$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##EPROOFING_KEY##",base64_encode($customer_info['cust_name'].":". $journal_info['j_code'].":".$app_res['pub_id'].":".$app_res['page_count']),$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##AuthorNames##",$app_res['corresponding_author'],$d_mail_info['message']);
		
			//##AuthorNames##
		
	}
	
	$sub_mail= $journal_info['j_code']."--".substr($app_res['article_title'],0,40)."... [".$app_res['pub_id']."]";

	
}
//echo $journal_info['j_proofing_system_server'];
//echo $transaction_id;

if($app_res['cust_id'] == 66  && $journal_info['j_proofing_system_server']=='AWS2')
{
include ('linux_db.php');
	
	    /// Checking the EOS XPERTTRACK DB for existing article values ////
$inwerd_detl = "SELECT * FROM inw_inward_dtl where workflow_id='".$_REQUEST['id']."'";
$inwerd_query = $xpertrack_db->query($inwerd_detl) or die($xpertrack_db->error);
$inwed_run = $inwerd_query->fetch_assoc();

$staffs_to_email=mysqli_fetch_array(mysqli_query($xpertrack_db,"select * from eos_staffs where staff_id='".$inwed_run['staff_id']."'"));
$staffs_cc_jemail=mysqli_fetch_array(mysqli_query($db,"select * from adm_journals` where j_id='".$app_res['j_id']."'"));
$staff_cc_email=mysqli_fetch_array(mysqli_query($xpertrack_db,"select * from eos_staffs where staff_id='".$inwed_run['staff_id']."'"));
$staff_cc_email_superadmin=mysqli_fetch_array(mysqli_query($xpertrack_db,"select * from eos_staffs where staff_id='1'"));

}
if(($app_res['cust_id'] == 57 || $app_res['cust_id'] == 58))
{
include ('linux_db_iap.php');
	
/// Checking the IAP XPERTTRACK DB for existing article values ////

$inwerd_detl = "SELECT * FROM inw_inward_dtl where workflow_id='".$_REQUEST['id']."'";
$inwerd_query = $xpertrack_db->query($inwerd_detl) or die($xpertrack_db->error);
$inwed_run = $inwerd_query->fetch_assoc();

$staffs_to_email=mysqli_fetch_array(mysqli_query($xpertrack_db,"select * from staffs where staff_id='".$inwed_run['staff_id']."'"));
$staffs_cc_jemail=mysqli_fetch_array(mysqli_query($db,"select * from adm_journals` where j_id='".$app_res['j_id']."'"));
$staff_cc_email=mysqli_fetch_array(mysqli_query($xpertrack_db,"select * from staffs where staff_id='".$inwed_run['staff_id']."'"));
$staff_cc_email_superadmin=mysqli_fetch_array(mysqli_query($xpertrack_db,"select * from staffs where staff_id='1'"));

}
elseif($app_res['cust_id'] == 53)
{
include ('linux_db_now.php');
	
/// Checking the NOW XPERTTRACK DB for existing article values ////

$inwerd_detl = "SELECT * FROM inw_inward_dtl where workflow_id='".$_REQUEST['id']."'";
$inwerd_query = $xpertrack_db->query($inwerd_detl) or die($xpertrack_db->error);
$inwed_run = $inwerd_query->fetch_assoc();

$staffs_to_email=mysqli_fetch_array(mysqli_query($xpertrack_db,"select * from staffs where staff_id='".$inwed_run['staff_id']."'"));
$staffs_cc_jemail=mysqli_fetch_array(mysqli_query($db,"select * from adm_journals` where j_id='".$app_res['j_id']."'"));
$staff_cc_email=mysqli_fetch_array(mysqli_query($xpertrack_db,"select * from staffs where staff_id='".$inwed_run['staff_id']."'"));
$staff_cc_email_superadmin=mysqli_fetch_array(mysqli_query($xpertrack_db,"select * from staffs where staff_id='1'"));

}
else if($app_res['cust_id'] == 66 && $journal_info['j_proofing_system_server']=='GODADDY')
{
include ('godaddy-client-portal/godaddydb_new.php');
}
if($app_res['cust_id'] == 7 && $app_res['stage'] == 'FP' && $app_res['pub_id']=='EPL_6767')
{
include ('linux_sif_db.php');
	
/// Checking the LINUX SIF DB for existing article values ////
$inwerd_detl = "SELECT * FROM inw_inward_dtl where workflow_id='".$_REQUEST['id']."'";
$inwerd_query = $linuxsif_db->query($inwerd_detl) or die($linuxsif_db->error);
$inwed_run = $inwerd_query->fetch_assoc();

$linux_inw_id=$inwed_run['id'];

}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
     <meta charset="UTF-8" />
     <meta name="viewport" content="width=device-width, initial-scale=1.0" />   <!-- without this phones show the desktop layout shrunk -->
     <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>	

    <!-- Data Table CSS --->
     <style>
/* ===== Project Dispatch (send to client) - responsive layout =====
   Only the look changes: field names, ids, the show / hide of #div1 / #emaildiv / #reminder and the page scripts are untouched. */
.wholeouter{--navy:#14304a;--navy2:#1f4b73;--accent:#e8742a;--blue:#1b6ca8;--ink:#22303f;--mute:#6b7785;--line:#e4e9ef;--ok:#1e9e61;--bad:#d64545;}
body{background:#f3f6fa;}
.wholeouter *{box-sizing:border-box;}
@keyframes stcUp{from{opacity:0;transform:translateY(14px);}to{opacity:1;transform:none;}}
@keyframes stcShine{0%{transform:translateX(-120%) skewX(-20deg);}100%{transform:translateX(320%) skewX(-20deg);}}

/* ---- title bar ---- */
.stc-hero{position:relative;overflow:hidden;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;width:calc(100% - 2 * clamp(10px,2.5vw,24px));max-width:1180px;margin:14px auto 14px;padding:16px 22px;border-radius:16px;color:#fff;
  background:linear-gradient(120deg,#14304a 0%,#1f4b73 60%,#1b6ca8 100%);box-shadow:0 8px 22px rgba(20,48,74,.2);animation:stcUp .45s ease both;}
.stc-hero:before{content:"";position:absolute;top:0;bottom:0;width:26%;left:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,.13),transparent);animation:stcShine 6s ease-in-out infinite;}
.stc-hero>*{position:relative;}
.stc-hero small{display:block;font-size:10.5px;letter-spacing:1px;text-transform:uppercase;opacity:.75;}
.stc-hero h1{margin:0;font-size:22px;font-weight:700;color:#fff;line-height:1.2;}
.stc-back{display:inline-flex;align-items:center;gap:8px;padding:9px 16px;border-radius:10px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.25);color:#fff !important;font-weight:600;text-decoration:none !important;transition:transform .2s,background .2s;}
.stc-back:hover{background:rgba(255,255,255,.26);transform:translateX(-3px);}
.stc-wrap{max-width:1180px;margin:0 auto;padding:0 clamp(10px,2.5vw,0px);}
.stc-msg{display:flex;flex-direction:column;align-items:center;gap:8px;max-width:1180px;margin:0 auto 14px;}
.stc-msg span{padding:8px 16px !important;height:auto !important;text-align:center;}

/* ---- layout shell: cards instead of the old nested boxes ---- */
.divouter{display:block !important;padding:0 clamp(10px,2.5vw,24px) 40px !important;margin-top:0 !important;}
.form_outer{width:100% !important;max-width:1180px;margin:0 auto;padding:0 !important;display:block !important;background:transparent !important;}
.form_inner{width:100% !important;padding:0 !important;display:block !important;background:transparent !important;}
.form_inner>.container,.form_inner form>.container,.form_inner form>#div1,.form_inner form>#emaildiv{
  width:100%;max-width:100% !important;margin:0 0 16px !important;padding:20px 22px !important;background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:0 2px 12px rgba(20,48,74,.06);animation:stcUp .45s ease both;}
.form_inner .container.mt-4,.form_inner .container.mt-5,.form_inner .container.mb-4,.form_inner .container.mb-5{margin-top:0 !important;margin-bottom:16px !important;}
#div1 .container,#emaildiv .container{max-width:100% !important;padding:0 !important;margin:0 !important;}
#div1 .row,#emaildiv .row{margin:0 !important;--bs-gutter-x:0;padding-top:0 !important;}
#div1 .row>*,#emaildiv .row>*{padding:0 !important;width:100% !important;max-width:100%;}
.form_inner form{margin:0;}
.form_inner>.container.stc-formwrap{background:transparent !important;border:0 !important;box-shadow:none !important;padding:0 !important;margin:0 !important;animation:none;}

/* section headings (were absolutely positioned) */
.formcont1heading{position:static !important;display:block !important;width:100% !important;height:auto !important;margin:0 0 14px !important;padding:0 0 10px !important;border-bottom:1px solid var(--line);background:transparent !important;color:var(--navy) !important;z-index:auto !important;}
/* the theme draws this heading as a black arrow tab - switch that off */
.formcont1heading:before,.formcont1heading:after{display:none !important;content:none !important;}
.formcont1heading h3,.widget-title{display:flex;align-items:center;gap:8px;margin:0 !important;font-size:13px !important;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--navy);}
.formcont1heading h3:before{content:"";width:4px;height:16px;border-radius:3px;background:var(--accent);}
.widget-title i{color:var(--accent);}
#widget-box-2 .widget-body{padding:0 !important;} #widget-box-2 .widget-main{padding:0 !important;}

/* ---- article information: table -> tiles ---- */
#widget-box-2 table{display:block;width:100%;margin:0;border:0 !important;background:transparent;}
#widget-box-2 tbody{display:grid !important;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:12px;border:0 !important;}
#widget-box-2 tr{display:flex;flex-direction:column;gap:3px;padding:12px 14px;border:1px solid var(--line);border-radius:12px;background:#f9fbfd;transition:box-shadow .2s,transform .2s;}
#widget-box-2 tr:hover{box-shadow:0 8px 18px rgba(20,48,74,.1);transform:translateY(-2px);}
.table-hover>tbody>tr:hover>*{--bs-table-accent-bg:transparent;}
/* Bootstrap's table-hover paints every cell with an inset box-shadow (the grey bars on hover) - switch it off for the tiles */
#widget-box-2 table,#widget-box-2 tbody,#widget-box-2 tr,#widget-box-2 td{--bs-table-bg:transparent;--bs-table-accent-bg:transparent;--bs-table-hover-bg:transparent;--bs-table-striped-bg:transparent;}
#widget-box-2 tr:hover>*,#widget-box-2 td{box-shadow:none !important;color:inherit;}
#widget-box-2 td{display:block !important;width:auto !important;padding:0 !important;border:0 !important;background:transparent !important;word-break:break-word;}
#widget-box-2 tr:hover td{background:transparent !important;}
#widget-box-2 td.tdstyle{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--mute);}
#widget-box-2 td+td{font-size:14px;font-weight:600;color:var(--navy);}
#widget-box-2 tr:nth-last-child(-n+2){grid-column:1/-1;}
#widget-box-2 tr:nth-last-child(-n+2) td+td{font-weight:500;color:var(--ink);}

/* ---- dispatch method: radio chips ---- */
.radio_btn{margin:0 0 16px !important;height:auto !important;}
.form_inner .radio_btn .form-group:not(#reminder){display:block !important;width:100% !important;margin:0 !important;}
.radio_btn .control-label{display:block;width:auto !important;float:none !important;padding:0 0 10px !important;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--navy) !important;}
.radio_btn .d-flex{display:flex !important;flex-wrap:wrap;gap:10px !important;width:100% !important;}
.radio_btn input[type=radio]{position:absolute;opacity:0;width:1px;height:1px;pointer-events:none;}
.radio_btn input[type=radio]+label{display:inline-flex;align-items:center;gap:10px;margin:0;padding:11px 16px;border:1px solid #d5dde6;border-radius:12px;background:#fff;color:var(--ink);font-weight:600;font-size:13.5px;cursor:pointer;transition:all .2s;}
.radio_btn input[type=radio]+label:before{content:"";flex:0 0 auto;width:16px;height:16px;border-radius:50%;border:2px solid #b3bcc7;background:#fff;transition:all .2s;}
.radio_btn input[type=radio]+label:hover{border-color:var(--blue);transform:translateY(-2px);box-shadow:0 8px 16px rgba(20,48,74,.1);}
.radio_btn input[type=radio]:checked+label{background:#eaf2fa;border-color:var(--blue);color:var(--blue);box-shadow:0 6px 16px rgba(27,108,168,.18);}
.radio_btn input[type=radio]:checked+label:before{border-color:var(--blue);background:radial-gradient(circle,var(--blue) 0 4px,#fff 5px);}
.radio_btn input[type=radio]:focus-visible+label{outline:3px solid rgba(27,108,168,.35);outline-offset:2px;}

/* ---- form rows: label | field on desktop, stacked on phones ---- */
.form_inner .form-group{display:grid;grid-template-columns:190px minmax(0,1fr);gap:6px 18px;align-items:start;width:100% !important;margin:0 0 14px !important;}
/* Bootstrap's .d-flex is !important - beat it, except for #reminder whose inline show()/hide() must keep working */
.form_inner .form-group:not(#reminder){display:grid !important;}
.form_inner .form-group>div.d-flex,.form_inner .form-group>div{display:flex;}
.form_inner .form-group>.d-flex>input.form-control,.form_inner .form-group>div>input.form-control{flex:1 1 220px;min-width:0;}
.form_inner .form-group>label.control-label,.form_inner .form-group>label{width:auto !important;max-width:none !important;float:none !important;text-align:left !important;margin:0;padding:11px 0 0 !important;font-size:13px;font-weight:600;color:var(--navy) !important;}
.form_inner .form-group>div{width:100% !important;max-width:100% !important;padding:0 !important;flex-wrap:wrap;gap:8px !important;}
.form_inner .form-control,.form_inner input[type=text],.form_inner input[type=email],.form_inner input[type=password],.form_inner input[type=file]{
  width:100% !important;max-width:100% !important;height:42px;padding:0 12px;border:1px solid #d5dde6 !important;border-radius:10px !important;background:#fff;color:var(--ink);font-size:14px;box-shadow:none !important;transition:border-color .2s,box-shadow .2s;}
.form_inner input[type=file]{padding:8px 12px;height:auto;background:#f9fbfd;}
.form_inner .form-control:focus,.form_inner input:focus{outline:0;border-color:var(--blue) !important;box-shadow:0 0 0 3px rgba(27,108,168,.15) !important;}
.form_inner input[readonly]{background:#f3f6fa;color:var(--mute);}
.form_inner .form-group label.error,.form_inner label.error{color:var(--bad);font-size:12px;margin:2px 0 0;font-weight:500;}
.form_inner input.error{border-color:var(--bad) !important;}
.form_inner .form-group>div>*{max-width:100%;}

/* mail body editor */
.richText{width:100% !important;max-width:100% !important;border-radius:12px;overflow:hidden;border:1px solid #d5dde6 !important;}
.richText .richText-toolbar{background:#f6f9fc;border-bottom:1px solid var(--line);}
.richText .richText-toolbar ul{display:flex;flex-wrap:wrap;}
.richText .richText-editor{min-height:200px;max-width:100%;overflow:auto;border:0 !important;padding:12px 14px;}
#txtBody{width:100% !important;min-height:200px;}

/* work dir links, AWS button, file pick-list */
.form_inner .btn.btn-primary{display:inline-flex !important;align-items:center;gap:8px;margin:0 8px 8px 0;padding:9px 18px !important;border:0 !important;border-radius:20px !important;background:linear-gradient(135deg,#14304a,#1f4b73) !important;color:#fff !important;font-weight:700;font-size:13px;cursor:pointer;box-shadow:0 3px 8px rgba(20,48,74,.25);transition:transform .15s,box-shadow .2s;}
.form_inner .btn.btn-primary:hover{transform:translateY(-2px);box-shadow:0 10px 20px rgba(20,48,74,.32);}
.form_inner .checkbox{padding:0 !important;}
.form_inner .checkbox .row{display:block;margin:0 0 8px !important;}
.form_inner .checkbox .row>*{padding:0 !important;width:100%;}
.form_inner .checkbox label{display:flex;align-items:center;flex-wrap:wrap;gap:8px;width:100%;margin:0;padding:10px 14px;border:1px solid #edf1f5;border-radius:10px;background:#f9fbfd;cursor:pointer;word-break:break-all;transition:background .2s,border-color .2s;}
.form_inner .checkbox label:hover{background:#eef5fb;border-color:#c5daef;}
#total_file_size_div{display:inline-block;margin-top:8px;padding:6px 14px;border-radius:20px;background:#fdeee3;color:var(--accent);font-weight:700;}

.form_inner .form-group .btn.btn-danger{display:inline-flex;align-items:center;justify-content:center;height:42px;padding:0 16px !important;border-radius:10px !important;border:1px solid #f3c4c4 !important;background:#fdeaea !important;color:#c0392b !important;font-weight:700;font-size:13.5px;transition:all .2s;}
.form_inner .form-group .btn.btn-danger:hover{background:var(--bad) !important;border-color:var(--bad) !important;color:#fff !important;}


/* ---- who is uploading + priority ---- */
.stc-sub{margin-top:4px;font-size:13px;opacity:.9;} .stc-sub i{margin-right:6px;} .stc-sub b{font-weight:700;}
.stc-hero-r{display:flex;flex-wrap:wrap;align-items:center;justify-content:flex-end;gap:8px;}
.stc-chip{display:inline-flex;align-items:center;gap:7px;padding:7px 13px;border-radius:20px;font-weight:700;font-size:12.5px;white-space:nowrap;background:rgba(255,255,255,.16);color:#fff;border:1px solid rgba(255,255,255,.28);}
.stc-hero .stc-chip.high{background:#d64545;border-color:#d64545;box-shadow:0 4px 12px rgba(214,69,69,.45);}
.stc-hero .stc-chip.late{background:#e8742a;border-color:#e8742a;box-shadow:0 4px 12px rgba(232,116,42,.45);animation:stcPulse 1.8s ease-in-out infinite;}
.stc-hero .stc-chip.today{background:#1b9cc2;border-color:#1b9cc2;}
.stc-hero .stc-chip.ok{background:rgba(30,158,97,.9);border-color:transparent;}
@keyframes stcPulse{0%,100%{box-shadow:0 0 0 0 rgba(232,116,42,.5);}50%{box-shadow:0 0 0 8px rgba(232,116,42,0);}}
/* the same chips on the light tiles */
#widget-box-2 .stc-chip{margin:0 6px 4px 0;background:#eef1f5;color:var(--mute);border-color:transparent;font-size:12px;}
#widget-box-2 .stc-chip.high{background:#fde8e8;color:#c0392b;} #widget-box-2 .stc-chip.late{background:#fff0e3;color:#c4590f;}
#widget-box-2 .stc-chip.today{background:#e1effa;color:#1b6ca8;} #widget-box-2 .stc-chip.ok{background:#e3f6ec;color:#1e9e61;}
.stc-who{display:inline-flex;align-items:center;gap:9px;font-weight:700;}
.stc-av{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--blue),var(--navy));color:#fff;font-size:12px;}
.stc-note{display:block;margin-top:3px;color:var(--mute);font-weight:500;font-size:11.5px;}
#widget-box-2 tr.stc-tile-by,#widget-box-2 tr.stc-tile-pri{background:#f3f8ff;border-color:#cfe1f3;}
@media (max-width:640px){.stc-hero-r{justify-content:flex-start;width:100%;} .stc-chip{font-size:12px;padding:6px 11px;}}
/* ---- actions ---- */
.stc-actions{background:#fff;}
.form_inner .stc-actions .form-group:not(#reminder){display:block !important;margin:0 !important;}
.stc-actions .form-group>div{display:flex !important;flex-wrap:wrap;justify-content:flex-end;gap:10px !important;}
.stc-actions .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:44px;padding:0 24px !important;border-radius:12px !important;font-weight:700;font-size:14px;text-decoration:none !important;cursor:pointer;transition:transform .15s,box-shadow .2s,background .2s,color .2s;}
.stc-actions .btn-info{border:0 !important;background:linear-gradient(135deg,var(--accent),#f08a45) !important;color:#fff !important;box-shadow:0 6px 16px rgba(232,116,42,.35);min-width:150px;}
.stc-actions .btn-info:hover{transform:translateY(-2px);box-shadow:0 12px 24px rgba(232,116,42,.45);}
.stc-actions .btn-grey{border:1px solid #d5dde6 !important;background:#fff !important;color:var(--navy) !important;}
.stc-actions .btn-grey:hover{background:#eef3f8 !important;}
.stc-actions .btn-danger{border:1px solid #f3c4c4 !important;background:#fdeaea !important;color:#c0392b !important;}
.stc-actions .btn-danger:hover{background:var(--bad) !important;border-color:var(--bad) !important;color:#fff !important;}

/* ---- tablet ---- */
@media (max-width:900px){
  .form_inner .form-group:not(#reminder){grid-template-columns:150px minmax(0,1fr);gap:6px 14px;}
  .stc-hero h1{font-size:19px;}
}
/* ---- phone ---- */
@media (max-width:640px){
  .form_inner>.container,.form_inner form>.container,.form_inner form>#div1,.form_inner form>#emaildiv{padding:16px 14px !important;border-radius:14px;}
  .form_inner .form-group:not(#reminder){grid-template-columns:1fr;gap:4px;margin-bottom:12px !important;}
  #reminder{grid-template-columns:1fr;}
  .form_inner .form-group>label.control-label,.form_inner .form-group>label{padding:0 !important;}
  #widget-box-2 tbody{grid-template-columns:1fr 1fr;}
  .radio_btn input[type=radio]+label{width:100%;}
  .stc-hero{padding:14px 16px;} .stc-back span{display:none;} .stc-back{padding:10px 12px;}
  /* Send / Cancel stay within thumb reach while the long form scrolls */
  .stc-actions{position:sticky;bottom:0;z-index:30;margin-left:calc(-1 * clamp(10px,2.5vw,24px)) !important;margin-right:calc(-1 * clamp(10px,2.5vw,24px)) !important;width:auto !important;border-radius:16px 16px 0 0 !important;box-shadow:0 -8px 24px rgba(20,48,74,.14) !important;padding:12px 14px !important;}
  .stc-actions .form-group>div{justify-content:stretch;}
  .stc-actions .btn{flex:1 1 30%;padding:0 12px !important;min-width:0;}
  .stc-actions .btn-info{flex:1 1 100%;order:-1;}
}
@media (max-width:420px){ #widget-box-2 tbody{grid-template-columns:1fr;} }
@media (prefers-reduced-motion:reduce){.wholeouter *,.stc-hero:before{animation:none !important;transition:none !important;}}
img,iframe,table{max-width:100%;}
</style>
  </head>

   <body>
  <div id="loader" class="loader-container">
        <div class="loader"></div>
    </div>
    <div id="overlay" class="overlay"></div>
    <div class="wholeouter">
    <?php include("includes/top-header.php");?>


    <?php
/* who is dispatching, and how urgent is this article?  (same due-date rule as the article lists) */
$stc_user_name = isset($_COOKIE['full_name']) ? trim($_COOKIE['full_name']) : '';
if ($stc_user_name === '' && isset($_COOKIE['user_id'])) {
	$stc_u = $db->query("SELECT full_name FROM users WHERE id = ".(int)$_COOKIE['user_id']." LIMIT 1");
	if ($stc_u && ($stc_r = $stc_u->fetch_assoc())) { $stc_user_name = trim($stc_r['full_name']); }
}
$stc_last_by = '';
if (!empty($app_res['last_worked_user_id'])) {
	$stc_u = $db->query("SELECT full_name FROM users WHERE id = ".(int)$app_res['last_worked_user_id']." LIMIT 1");
	if ($stc_u && ($stc_r = $stc_u->fetch_assoc())) { $stc_last_by = trim($stc_r['full_name']); }
}
$stc_stage = (string)$app_res['stage'];
$stc_due_raw = '';
if ($stc_stage == 'FP')                              { $stc_due_raw = !empty($app_res['target_due_dt']) ? $app_res['target_due_dt'] : $app_res['due_dt']; }
elseif (preg_match('/^ISSCOR[0-9]+$/', $stc_stage))  { $stc_due_raw = $app_res['due_dt']; }
else {
	$stc_rv = $db->query("SELECT due_date FROM inw_revisions_dtl WHERE inw_id = ".(int)$app_res['id']." ORDER BY r_id DESC LIMIT 1");
	if ($stc_rv && ($stc_r = $stc_rv->fetch_assoc())) { $stc_due_raw = $stc_r['due_date']; }
	if (empty($stc_due_raw) || strpos($stc_due_raw, '0000-00-00') === 0) { $stc_due_raw = $app_res['due_dt']; }
}
$stc_ts   = (!empty($stc_due_raw) && strpos($stc_due_raw, '0000-00-00') !== 0) ? strtotime($stc_due_raw) : false;
$stc_days = $stc_ts ? (int)round((strtotime(date('Y-m-d', $stc_ts)) - strtotime(date('Y-m-d'))) / 86400) : null;   // <0 late, 0 today
$stc_high = ((int)$app_res['highpriority'] === 1);
$stc_chips = '';
if ($stc_high) { $stc_chips .= '<span class="stc-chip high"><i class="fa fa-flag"></i> High priority</span>'; }
if ($stc_days !== null && $stc_days < 0)       { $stc_chips .= '<span class="stc-chip late"><i class="fa fa-exclamation-triangle"></i> Overdue &middot; '.(-$stc_days).' day'.((-$stc_days) === 1 ? '' : 's').'</span>'; }
elseif ($stc_days === 0)                        { $stc_chips .= '<span class="stc-chip today"><i class="fa fa-clock-o"></i> Due today</span>'; }
elseif ($stc_days !== null)                     { $stc_chips .= '<span class="stc-chip ok"><i class="fa fa-calendar-check-o"></i> Due in '.$stc_days.' day'.($stc_days === 1 ? '' : 's').'</span>'; }
else                                            { $stc_chips .= '<span class="stc-chip muted"><i class="fa fa-calendar-o"></i> No due date</span>'; }
?>
<div class="stc-hero">
      <div><small>Dispatch</small><h1>Project Dispatch<span title="Page version" style="display:inline-block;margin-left:10px;padding:2px 11px;border-radius:12px;background:#e8863a;color:#fff;font-size:12px;font-weight:700;vertical-align:middle;letter-spacing:.3px;">v2</span></h1>
        <div class="stc-sub"><i class="fa fa-user-circle-o"></i> Uploading by <b><?php echo htmlspecialchars($stc_user_name !== '' ? $stc_user_name : 'you'); ?></b></div></div>
      <div class="stc-hero-r">
        <?php echo $stc_chips; ?>
        <a class="stc-back sc-legacy" title="Go back to the original Project Dispatch page" href="#"><i class="fa fa-undo"></i><span>Use old version</span></a>
        <a class="stc-back" href="javascript:history.back();"><i class="fa fa-arrow-left"></i><span>Back</span></a>
      </div>
    </div>
<?php 
	  if(isset($_REQUEST['msg']))
	  {
	  ?>
	  
			<div style="width: 100%; display: flex; justify-content: center; ">
        <span style="width:fit-content; height: 30px; color: #fff; background:#ed424a; border-radius: 15px; display: flex; justify-content: center; align-items: center; padding: 5px 10px;"><?php echo htmlspecialchars($_REQUEST['msg']);?></span>
      </div>
	  </br>
	   <div style="width: 100%; display: flex; justify-content: center;">
	  		<a href="https://xpertproof.transforma.in/xpertproofing/Transforma_XPro/xpertproof/delete_article" target="_blank">Click here to Reset</a>
	   </div>
	  <?php 
	  }
	  ?>
	  
	
	  
      <div class="divouter mt-4">
        <div class="form_outer">
          <div class="form_inner d-flex flex-column align-items-center">
         <div class="container" id="widget-box-2">
          <div class="formcont1heading mb-3" style="position: absolute; left: 12px">
            <h3 class="widget-title bigger lighter" style="font-size: 1.275em">
              <i class="ace-icon fa fa-comments"></i>
              Article Information
            </h3>            
          </div>

          <div class="widget-body pt-5">
            <div class="widget-main no-padding pt-4">
              <table class="table table-bordered table-hover">
                <tbody style="border: 1px solid #dedef3;">
                  <tr class="stc-tile-by">
                    <td class="tdstyle">Uploading by</td>
                    <td class="hidden-480"><span class="stc-who"><span class="stc-av"><?php echo htmlspecialchars(strtoupper(substr($stc_user_name !== '' ? $stc_user_name : '?', 0, 1))); ?></span><?php echo htmlspecialchars($stc_user_name !== '' ? $stc_user_name : '-'); ?></span><?php echo $stc_last_by !== '' ? '<small class="stc-note">Last worked on by '.htmlspecialchars($stc_last_by).'</small>' : ''; ?></td>
                  </tr>
                  <tr class="stc-tile-pri">
                    <td class="tdstyle">Priority</td>
                    <td class="hidden-480"><?php echo $stc_chips; ?></td>
                  </tr>
                  <tr>
                    <td class="tdstyle">Customer Name</td>
                    <td class="hidden-480"><?php echo $customer_info['cust_name']; ?></td>
                  </tr>
                  <tr>
                    <td class="tdstyle">Journal</td>
                    <td class="hidden-480"><?php echo $journal_info['j_code']; ?></td>
                  </tr>
                  <tr>
                    <td class="tdstyle">Stage</td>
                    <td class="hidden-480"><?php echo $app_res['stage']; ?> | <?php echo $app_res['status']; ?></td>
                  </tr>
                  <tr>
                    <td class="tdstyle">Article Title</td>
                    <td class="hidden-480"><?php echo $app_res['pub_id']; ?></td>
                  </tr>
                  <tr>
                    <td class="tdstyle">Received Date</td>
                    <td class="hidden-480"><?php echo (date('d-m-Y',strtotime($app_res['recv_dt']))); ?></td>
                  </tr>
                  <tr>
                    <td class="tdstyle">Due Date</td>
                    <td class="hidden-480"> <?php
if($app_res['stage']=='FP')
{
echo (date('d-m-Y',strtotime($app_res['target_due_dt'])));
}
else
{
echo (date('d-m-Y',strtotime($app_res['due_dt'])));
}
?></td>
                  </tr>
				   <tr>
                    <td class="tdstyle">Insructions</td>
                    <td class="hidden-480"><?php echo $app_res['instructions']; ?></td>
                  </tr>
				   <tr>
                    <td class="tdstyle">Department Comments </td>
                    <td class="hidden-480">  <?php
                                                                        $di_query = "SELECT comments FROM `inw_dept_comments`  WHERE dept_ids IN (" . $app_res['department_id'] . ") ";
                                                                        $di_result = $db->query($di_query);

                                                                        $di_count = $di_result->num_rows;
                                                                        if ($di_count > 0) {
                                                                            $di_res = $di_result->fetch_assoc();
                                                                            echo $di_res['comments'];
                                                                        }
                                                                        ?></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div
      </div>

  <div class="container stc-formwrap">
           <form method="post" enctype="multipart/form-data">

      <div class="container radio_btn">
        <div class="form-group d-flex">
          <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Choose Dispatch Method :</label>
          <a href="#" class="sc-legacy sc-legacy-link" title="Go back to the original Project Dispatch page"><i class="fa fa-undo"></i> Facing an issue? Use old version</a>
          <div class="col-xs-12 col-sm-12 d-flex gap-3">
              <input id="optionRad_0" name="optionRad" value="1"  type="radio" checked="checked">
              <label for="optionRad_0">FTP upload and mail</label>
              </td>
              <td><input id="optionRad_1" name="optionRad" value="4"  type="radio">
                  <label for="optionRad_1">xProof upload &amp; mail</label></td>
              <td><input id="optionRad_2" name="optionRad" value="2" type="radio">
                  <label for="optionRad_2">Only FTP &amp; manual mail</label></td>
              <td><input id="optionRad_3" name="optionRad" value="3" type="radio">
                  <label for="optionRad_3">Attach file &amp; mail</label></td>
              <td><input id="optionRad_4" name="optionRad" value="5" type="radio">
                  <label for="optionRad_4">EProofing</label></td>
			 <td><input id="optionRad_5" name="optionRad" value="6" type="radio">
                  <label for="optionRad_5">Xperttrack</label></td>
          </div>
      </div>
    </div>
         <div id="div1"> 
          <div class="formcont1heading mb-3" style="position: absolute; left: 12px">
              <h3 style="font-size: 1.275em"> FTP </h3>
          </div>
          <div class="container mt-4 mb-4 pt-5">
          <div class="row pt-5">
              <div class="col-xs-12 d-flex flex-column gap-3 ftpstyle">
                  <div class="form-group d-flex">
                      <label class="col-sm-3 control-label no-padding-right">Site URL</label>
                      <div class="col-sm-9 d-flex gap-2">
                          <input name="FtpHost" type="text"  class="col-sm-6 form-control required form-control" value="<?php echo $journal_info['ftp_send_host']; ?>" />
                      </div>
                  </div>
                  <div class="form-group d-flex ">
                      <label class="col-sm-3 control-label no-padding-right">Target DIR</label>
                      <div class="col-sm-9 d-flex gap-2">
                          <input name="FtpPath" type="text"  class="col-sm-6 form-control required form-control" value="<?php echo $journal_info['ftp_send_dir']; ?>"/>
                          Note: No slash end of the path </div>
                  </div>
                  <div class="form-group d-flex">
                      <label class="col-sm-3 control-label no-padding-right">User</label>
                      <div class="col-sm-9 d-flex gap-2">
                          <input name="FtpUser" type="text"  class="col-sm-6 form-control required form-control" value="<?php echo $journal_info['ftp_send_user']; ?>" />
                      </div>
                  </div>
                  <div class="form-group d-flex">
                      <label class="col-sm-3 control-label no-padding-right">Password</label>
                      <div class="col-sm-9 d-flex gap-2">
                          <input name="FtpPass" type="password"  class="col-sm-6 form-control required form-control" value="<?php echo $journal_info['ftp_send_password']; ?>"/>
                      </div>
                  </div>
              </div>
          </div>
        </div>
      </div>
      <!-- Email div  -->
      <div  id="emaildiv">
        <div class="formcont1heading mb-3" style="position: absolute; left: 12px">
            <h3 style="font-size: 1.275em"> E-Mail </h3>
        </div>
        <div class="container mt-4 mb-4 pt-5">
        <div class="row pt-5">
            <div class="col-xs-12 d-flex flex-column gap-3 ftpstyle">
                <div class="form-group d-flex">
                    <label class="col-sm-3 control-label no-padding-right">From</label>
                    <div class="col-sm-9 d-flex gap-2">
                        <input name="txtFrom" type="email"  class="col-sm-6 form-control required" value="<?php echo $journal_info['j_from_email']; ?>"/>
                    </div>
                </div>
                <div class="form-group d-flex">
                    <label class="col-sm-3 control-label no-padding-right">Password</label>
                    <div class="col-sm-9 d-flex gap-2">
                        <input name="txtMpass" type="password"  class="col-sm-6 form-control required" value="<?php echo $journal_info['j_from_email_pwd']; ?>" />
                    </div>
                </div>
                <div class="form-group d-flex">
                    <label class="col-sm-3 control-label no-padding-right">To</label>
                    <div class="col-sm-9 d-flex gap-2">
					
					   <?php
                                                                                if ($app_res['cust_id'] == 42 || $app_res['cust_id'] == 43  || $app_res['cust_id'] == 34 || $app_res['cust_id'] == 37 || $app_res['cust_id'] == 77) {
                                                                                    ?>
                                                                                    <input name="txtTo" type="text" id="txtTo"  class="col-sm-6 form-control required" value="<?php echo $app_res['corresponding_mail']; ?>" readonly />
                                                                                    <?php
                                                                                }elseif($app_res['cust_id'] == 66 && $app_res['stage']=='FP' ) {
                                                                                    ?>
                                                                                    <input name="txtTo" type="text" id="txtTo"  class="col-sm-6 form-control required" value="<?php echo $app_res['corresponding_mail']; ?>" readonly />
                                                                                    <?php
                                                                                }
																				elseif($app_res['cust_id'] == 76   && $app_res['stage']=='FP') {
                                                                                    ?>
                                                                                    <input name="txtTo" type="text" id="txtTo"  class="col-sm-6 form-control required" value="<?php echo $journal_info['j_to_email']; ?>" readonly />
                                                                                    <?php
                                                                                }
																				
																				elseif($app_res['cust_id'] == 66 && $app_res['stage']!='FP' ) {
                                                                                    ?>
                                                                                    <input name="txtTo" type="text" id="txtTo"  class="col-sm-6 form-control required" value="<?php echo $staffs_to_email['staff_email']; ?>" readonly />
                                                                                    <?php
                                                                                }
                                                                                elseif($app_res['cust_id'] == 7 && $app_res['stage']=='FP' && $app_res['pub_id']=='EPL_6767' ) {
                                                                                    ?>
                                                                                    <input name="txtTo" type="text" id="txtTo"  class="col-sm-6 form-control required" value="marimuthu@transforma.co"/>
                                                                                    <?php
                                                                                }
																				else {
                                                                                    ?>
                                                                                    <input name="txtTo" type="text" id="txtTo"  class="col-sm-6 form-control required" value="<?php echo $journal_info['j_to_email']; ?>" readonly />
                                                                                    <?php
                                                                                }
                                                                                ?>
						
						
						
                          <a href="<?php echo $_COOKIE['set_url'];?>/edit-article/<?php echo $app_res['id'];?>" class="btn btn-danger">Edit</a>
                        <span class="validemail"></span>
                        Note- Add the Email ids with comma seperated. </div>
              </div> 
			  				<?php 
                                                                            $stage_check_pmc=preg_replace("/PMC([0-9]+)/","PMC",$app_res['stage']);
                                                                            $stage_check_fin=preg_replace("/FIN([0-9]+)/","FIN",$app_res['stage']);
                                                                            $stage_check_draft=preg_replace("/DRAFT([0-9]+)/","DRAFT",$app_res['stage']);
                                                                            $stage_check_rev=preg_replace("/REV([0-9]+)/","REV",$app_res['stage']);
																		if($app_res['cust_id'] == 66) {
                                                                                    ?>
                                                                                     <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">CC</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <input name="txtCc" type="text"  class="col-sm-6 form-control" value="<?php echo $staff_cc_email['staff_email']; ?>,<?php echo $journal_info['j_to_email']; ?>,<?php echo $staff_cc_email_superadmin['staff_email']; ?>"/>
                                                                                Note:- Add the Email ids with comma seperated. </div>
                                                                        </div>
                                                                        <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">BCC</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <input name="txtBcc" type="text"  class="col-sm-6 form-control" value="<?php echo $journal_info['j_bcc_email']; ?>"  />
                                                                                Note:- Add the Email ids with comma seperated. </div>
                                                                        </div>	
                                                                                    <?php
                                                                                }
																				else
																				{
																				?>
                                                                        <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">CC</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                               <input name="txtCc" type="text" class="col-sm-6 form-control" 
       value="<?php echo $journal_info['j_cc_email'] . (!empty($app_res['corresponding_other_author']) ? ',' . $app_res['corresponding_other_author'] : ''); ?>"/>
Note:- Add the Email IDs with comma separated. </div>
                                                                        </div>
                                                                        <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">BCC</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <input name="txtBcc" type="text"  class="col-sm-6 form-control" value="<?php echo $journal_info['j_bcc_email']; ?>" />
                                                                                Note:- Add the Email ids with comma seperated. </div>
                                                                        </div>	
																			<?php 
																				}
																				?>
              
               <?php
                                                                                if($app_res['cust_id']== 66 && $app_res['stage']=='FP' ) {
                                                                                    ?>
                                                                                   
																					
                                                                        <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Author Name</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <input name="txtToName" type="text"  class="col-sm-6 form-control" value="<?php echo $app_res['corresponding_author']; ?>" readonly />&nbsp;&nbsp; <a href="<?php echo $_COOKIE['set_url'];?>/edit-article/<?php echo $app_res['id'];?>" class="btn btn-danger">Edit</a>
                                                                                Note:- Corresponding Author Name fetched from article.</div>
                                                                        </div>  
																		<?php 
																				}
																				 if(($app_res['cust_id']== 76 || $app_res['cust_id']== 77 || $app_res['cust_id']== 79) && $app_res['stage']=='FP' ) {
                                                                                    ?>
                                                                                   
																					
                                                                        <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Author Name</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <input name="txtToName" type="text"  class="col-sm-6 form-control" value="<?php echo $app_res['corresponding_author']; ?>" readonly />&nbsp;&nbsp; <a href="<?php echo $_COOKIE['set_url'];?>/edit-article/<?php echo $app_res['id'];?>" class="btn btn-danger">Edit</a>
                                                                                Note:- Corresponding Author Name fetched from article.</div>
                                                                        </div>  
																		<?php 
																				}
																				elseif($app_res['cust_id']!= 66 && $app_res['stage']=='FP' ) {
                                                                                    ?>
																					
																					  <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Author Name</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <input name="txtToName" type="text"  class="col-sm-6 form-control" value="<?php echo $app_res['corresponding_author']; ?>" readonly />&nbsp;&nbsp; <a href="<?php echo $_COOKIE['set_url'];?>/edit-article/<?php echo $app_res['id'];?>" class="btn btn-danger">Edit</a>
                                                                                Note:- Corresponding Author Name fetched from article.</div>
                                                                        </div>  
																				<?php 
																				}
																				
																				elseif($app_res['cust_id']!= 66 && $app_res['stage']!='FP' ) {
                                                                                    ?>
																					
																					  <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Author Name</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <input name="txtToName" type="text"  class="col-sm-6 form-control" value="<?php echo $app_res['corresponding_author']; ?>" readonly />&nbsp;&nbsp; <a href="<?php echo $_COOKIE['set_url'];?>/edit-article/<?php echo $app_res['id'];?>" class="btn btn-danger">Edit</a>
                                                                                Note:- Corresponding Author Name fetched from article.</div>
                                                                        </div>  
																				<?php 
																				}
																				?>
                    
                <div class="form-group d-flex">
                    <label class="col-sm-3 control-label no-padding-right">Reply To</label>
                    <div class="col-sm-9 d-flex gap-2">
                        <input name="txtreplyto" type="text"  class="col-sm-6 form-control" value="<?php echo $journal_info['reply_to']; ?>"/>
                        Note:- Corresponding ReplyTo fetched from journal details.</div>
                </div>
				
				     <?php 
																		if($app_res['cust_id']==37)
																		{
																		?>




                                                                        <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Subject</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <input name="txtSub" type="text"  class="col-sm-6 form-control required" value="<?php echo $sub_mail; ?> - Proof"/>
                                                                            </div>
                                                                        </div>
																		<?php 
																		}
																		elseif($app_res['cust_id']==57 || $app_res['cust_id']==58)
																		{
																			if($app_res['stage']=='FP')
																			{
																			$subject_iap='[##ARTICLEID##] - Firstproof Uploaded';
																			}
																			elseif($stage_check_fin=='FIN')
																			{
																			$subject_iap='[##ARTICLEID##] - Final Delivery';
																			}
																			elseif($stage_check_pmc=='PMC')
																			{
																			$subject_iap='[##ARTICLEID##] - PMC Data Files Uploaded';
																			}
																			elseif($stage_check_rev=='REV' && ((is_null($inwed_run['pap'])) || $inwed_run['pap']=='no' && ($journal_info['j_code']=='JSIAML') ))
																			{
																			$subject_iap='[##ARTICLEID##] - Revised Proof Uploaded';
																			}
																			elseif($stage_check_rev=='REV' && ((is_null($inwed_run['pap'])) || $inwed_run['pap']=='no' ))
																			{
																			$subject_iap='[##ARTICLEID##] - Revised Proof Uploaded';
																			}
																			elseif($stage_check_rev=='REV' && $inwed_run['pap']=='yes')
																			{
																			$subject_iap='[##ARTICLEID##] – Jstage Data Files Uploaded';
																			}
																			else
																			{
																			$subject_iap='[##ARTICLEID##] – Jstage Data Files Uploaded';
																			}
																		?>
																		 <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Subject</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <input name="txtSub" type="text"  class="col-sm-6 form-control required" value="<?php echo $subject_iap; ?>"/>
                                                                            </div>
                                                                        </div>
																	  <?php 																		
																		}
																		elseif(($app_res['cust_id']==66 || $app_res['cust_id']==79) && $app_res['stage']!='FP' && $stage_check_fin=='FIN' && $inwed_run['corrections']=='no')
																		{
																		$subject_eos='[##ARTICLEID##] - Final Delivery';
																		?>
																		 <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Subject</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <input name="txtSub" type="text"  class="col-sm-6 form-control required" value="<?php echo $subject_eos; ?>"/>
                                                                            </div>
                                                                        </div>
																	  <?php 																		
																		}
                                                                        elseif(($app_res['cust_id']==66 || $app_res['cust_id']== 77 || $app_res['cust_id']==79) && $app_res['stage']!='FP' && $app_res['stage']!='REV1' && $stage_check_rev=='REV' && $inwed_run['corrections']=='no')
																		{
																		$subject_eos='[##ARTICLEID##] - Final Proof PDF';
																		?>
																		 <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Subject</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <input name="txtSub" type="text"  class="col-sm-6 form-control required" value="<?php echo $subject_eos; ?>"/>
                                                                            </div>
                                                                        </div>
																	  <?php 																		
																		}
																		  elseif(($app_res['cust_id']==66 || $app_res['cust_id']== 77 || $app_res['cust_id']==79) && $app_res['stage']!='FP'  && $stage_check_rev!='REV' && $stage_check_fin!='FIN' && $stage_check_draft=='DRAFT')
																		{
																		$subject_eos='[##ARTICLEID##] - Updated HTML,PDF for Online Editor ';
																		?>
																		 <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Subject</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <input name="txtSub" type="text"  class="col-sm-6 form-control required" value="<?php echo $subject_eos; ?>"/>
                                                                            </div>
                                                                        </div>
																	  <?php 																		
																		}
																		else
																		{
																		?>
																		 <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Subject</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <input name="txtSub" type="text"  class="col-sm-6 form-control required" value="<?php echo $d_mail_info['subject']; ?>"/>
                                                                            </div>
                                                                        </div>
																		<?php 
																		}
																		?>

				
                 <?php 
                                                                    

																		if(($app_res['cust_id']==66 || $app_res['cust_id']== 77 || $app_res['cust_id']==79) && $app_res['stage']!='FP' && $stage_check_fin=='FIN' && $inwed_run['corrections']=='no')
																		{
																		$message_eos='Dear Editor,<br/><br/>Final Package for the above mentioned article has been uploaded to the portal with xml,pdf and Epub File.<br/><br/>Best regards,<br/>Transforma';
																		?>
                                                                        <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Message</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <textarea name="txtBody" id="txtBody" type="text"  class="col-sm-8 required" ><?php echo $message_eos;?></textarea>
                                                                            </div>
                                                                        </div>
																		<?php 
																		}
                                                                        elseif(($app_res['cust_id']==66 || $app_res['cust_id']== 77 || $app_res['cust_id']==79) && $app_res['stage']!='FP'  && $app_res['stage']!='REV1' && $stage_check_rev=='REV' && $inwed_run['corrections']=='no')
																		{
																		$message_eos='Dear Editor,<br/><br/>Final Proof PDF for the above mentioned article has been uploaded to the portal.<br/><br/>Best regards,<br/>Transforma';
																		?>
                                                                        <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Message</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <textarea name="txtBody" id="txtBody" type="text"  class="col-sm-8 required" ><?php echo $message_eos;?></textarea>
                                                                            </div>
                                                                        </div>
																		<?php 
																		}
																		 elseif(($app_res['cust_id']==66 || $app_res['cust_id']== 77 || $app_res['cust_id']==79) && $app_res['stage']!='FP'  && $stage_check_rev!='REV' && $stage_check_fin!='FIN' && $stage_check_draft=='DRAFT')
																		{
																		$message_eos='Dear Editor,<br/><br/>Corrected HTML,PDF,XML for the above mentioned article has been uploaded to the portal and you can click the link again and edit the article if required or you can directly appprove if no correction required.<br/><br/>Best regards,<br/>Transforma';
																		?>
																		 <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Message</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <textarea name="txtBody" id="txtBody" type="text"  class="col-sm-8 required" ><?php echo $message_eos;?></textarea>
                                                                            </div>
                                                                        </div>
																	  <?php 																		
																		}
																		  elseif($app_res['cust_id']==57 || $app_res['cust_id']==58)
																		{
																			
																			if($app_res['stage']=='FP')
																			{
																			$message_iap='Dear Editor,<br/><br/>We have uploaded the firstproof for '.$app_res['pub_id'].' to the portal.<br/><br/>Best regards,<br/>Transforma';
																			}
																			elseif($stage_check_fin=='FIN')
																			{
																			$message_iap='Dear Editor,<br/><br/>Final Package for the above mentioned article has been uploaded to the portal with xml,pdf and Epub File.<br/><br/>Best regards,<br/>Transforma';
																			}
																			elseif($stage_check_pmc=='PMC')
																			{
																			$message_iap='Dear Editor,<br/><br/>We have uploaded the PMC Data Package of '.$app_res['pub_id'].' to the portal. If you encounter any difficulties in reviewing the package or need further revisions, please let us know.<br/><br/>Best regards,<br/>Transforma';
																			}
																		elseif($stage_check_rev=='REV' && ((is_null($inwed_run['pap'])) ||  $inwed_run['pap']=='no' && ($journal_info['j_code']=='JSIAML') ))
																			{
																			$message_iap='Dear Editors,<br/><br/>We have uploaded the Revised Proof of '.$app_res['pub_id'].' to the portal. If you encounter any difficulties in downloading or reviewing the PDF, please feel free to reach out to us<br/><br/>Best regards,<br/>Transforma';
																			}
																			elseif($stage_check_rev=='REV' && ((is_null($inwed_run['pap'])) ||  $inwed_run['pap']=='no' ))
																			{
																			$message_iap='Dear Editors,<br/><br/>We have uploaded the revised proof of '.$app_res['pub_id'].' to the portal. If you encounter any difficulties in downloading or reviewing the PDF, please feel free to reach out to us<br/><br/>Best regards,<br/>Transforma';
																			}
																			elseif($stage_check_rev=='REV' && $inwed_run['pap']=='yes')
																			{
																			$message_iap='Dear Editor,<br/><br/>We have uploaded the J-stage Data Files of '.$app_res['pub_id'].' to the portal. If you encounter any difficulties in reviewing the package or need further revisions, please let us know.<br/><br/>Best regards,<br/>Transforma';
																			}
																			
																		?>
                                                                        <div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Message</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <textarea name="txtBody" id="txtBody" type="text"  class="col-sm-8 required" ><?php echo $message_iap;?></textarea>
                                                                            </div>
                                                                        </div>
																		<?php 
																		}
																		else
																		{
																		?>
																		<div class="form-group d-flex">
                                                                            <label class="col-sm-3 control-label no-padding-right">Message</label>
                                                                            <div class="col-sm-9 d-flex gap-2">
                                                                                <textarea name="txtBody" id="txtBody" type="text"  class="col-sm-8 required" ><?php echo $d_mail_info['message'] ?></textarea>
                                                                            </div>
                                                                        </div>
																		<?php 
																		}
																		?>
              
                  <div class="form-group d-flex">
                    <label class="col-sm-3 control-label no-padding-right">Attachment</label>
                    <div class="col-sm-9 d-flex gap-2">
                        <input name="fileAttachment" type="file" id="image-file" class="col-sm-6 form-control " />
                    </div>
                  </div>
                </div>
        </div>
        </div>
    </div>
    <!-- <div class="space-2"></div> -->
	
	  <?php
//// Get Prev dept name /////
                                                            $presult = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = " . $app_res['department_id'] . ";");
                                                            $ptoatl_count = $presult->num_rows;
                                                            $pdept_res = array();
                                                            if ($ptoatl_count > 0) {
                                                                $pdept_res = $presult->fetch_assoc();
                                                            }
                                                            ?>
    <div class="container mt-4 mb-4">
         <div class="form-group d-flex">
           <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Work DIR:</label>
               <div class="col-xs-12 col-sm-9">
                  <?php
                                                                    $work_dir = $app_res['work_dir'];
                                                                    $work_dir = str_replace(WF_DIR, TOOL_DIR, $work_dir);
                                                                    ?>
                                                                    <div class="clearfix" style="justify-content:flex-start;gap:8px"> <a class="btn btn-primary" href="file:///<?php echo $work_dir . $app_res['volume_folder'] . "/" . $app_res['pub_id'] . "/" . $app_res['stage'] . "/" . $pdept_res['dept_name'] . "/"; ?>" target="_blank"><i class="ace-icon fa fa-folder align-top bigger-125 pt-1"></i>&nbspView</a> 
																	<?php
																		if($customer_info['cust_name'] == "Sunrise-NOW")
																		{
																	?>
																		<span class="btn btn-primary" id="Sunrise_Upload_Files_to_AWS_Server"> <i class="ace-icon fa fa-cloud-upload align-top bigger-125"></i> Upload Files to AWS Server </span>
																	<?php
																		}
																	?>
                </div>
          </div>
        </div>
        <?php
		/* what the Move Files configuration delivers decides the files to send; with a configuration there are no tick-boxes */
		$src = wf_existing_dir($app_res['work_dir'] . $app_res['volume_folder'] . "/" . $app_res['pub_id'] . "/" . $app_res['stage'] . "/" . $pdept_res['dept_name'] . "/");
		$mf_pre = sc_move_config($db, $app_res, $src);
		$mf_auto = $mf_pre['configured'] ? array_keys($mf_pre['exts']) : null;
		$mf_shown = 0;
		?>
		<div class="container mt-4 mb-4 sc-flush" id="sc-ticklist">
           <div class="form-group d-flex">
                                                                <label class="control-label bolder blue control-label col-xs-12 col-sm-3 no-padding-right"><?php echo $mf_auto !== null ? 'Files to be sent' : 'Check the files to send'; ?></label>
                                                                <div class="checkbox col-xs-12 col-sm-9">
                                                                    <?php
// Open the source directory to read in files
                                                                     $src = $app_res['work_dir'] . $app_res['volume_folder'] . "/" . $app_res['pub_id'] . "/" . $app_res['stage'] . "/" . $pdept_res['dept_name'] . "/";
                                                                    $src = wf_existing_dir($src);
                                                                    /* tick what the Move Files configuration delivers (same rule the move step uses on submit); no configuration = the old rules below */
                                                                    $mf_pre = sc_move_config($db, $app_res, $src);
                                                                    $mf_auto = $mf_pre['configured'] ? array_keys($mf_pre['exts']) : null;
                                                                    if (is_dir($src)) {
                                                                        $i = new DirectoryIterator($src);
                                                                        foreach ($i as $f) {
                                                                            if ($f->isFile()) {
                                                                                 $filename = $f->getFilename();
                                                                                // $filesize = filesize($src."/".$f);
                                                                                $fsize = filesize($src . "/" . $f);
                                                                                $filesize = formatSizeUnits($fsize);
                                                                                if ($mf_auto !== null) {
                                                                                    $mf_x = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                                                                                    $mf_tick = in_array($mf_x, $mf_auto, true) && !preg_match('/-ori\.pdf$/i', $filename)
                                                                                        && !($app_res['cust_id'] == 82 && strtolower($filename) !== strtolower($app_res['pub_id'] . '.' . $mf_x));   /* customer 82: only <article id>.<ext> moves */
                                                                                    if (!$mf_tick) { continue; }   /* not delivered: not listed, not sent */
                                                                                    $mf_shown++;
                                                                                }
                                                                                ?>
                                                                                <div class="row">
                                                                                    <label>
                                                                                        <?php
                                                                                        if ($mf_auto !== null) {
                                                                                            $mf_x = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                                                                                            $mf_tick = in_array($mf_x, $mf_auto, true) && !preg_match('/-ori\.pdf$/i', $filename)
                                                                                                && !($app_res['cust_id'] == 82 && strtolower($filename) !== strtolower($app_res['pub_id'] . '.' . $mf_x));   /* customer 82: only <article id>.<ext> moves */
                                                                                            ?>
                                                                                            <input type="hidden" name="attach_files[]" class="sc-sendfile" data-size="<?php echo $fsize; ?>" value="<?php echo htmlspecialchars($filename, ENT_QUOTES); ?>" /><i class="fa fa-check-circle" style="color:#2e8b57;margin-right:4px"></i>
                                                                                            <?php
                                                                                        } elseif (strpos($filename, '.zip') !== false) {
                                                                                            ?>
                                                                                            <input name="attach_files[]" class="ace ace-checkbox-2" data-size="<?php echo $fsize; ?>" value="<?php echo $filename; ?>" type="checkbox" checked="checked"/>
                                                                                            <?php
                                                                                        } elseif (strpos($filename, '-ori.pdf') == true) {
                                                                                            ?>
                                                                                           <input name="attach_files[]" class="ace ace-checkbox-2" data-size="<?php echo $fsize; ?>" value="<?php echo $filename; ?>" type="checkbox" />
                                                                                            <?php
                                                                                        }else if (strpos($filename, '.pdf') !== false && ($app_res['cust_id'] == 42 && $app_res['stage']== "REV1")) {
                                                                                            ?>
                                                                                            <input name="attach_files[]" class="ace ace-checkbox-2" data-size="<?php echo $fsize; ?>" value="<?php echo $filename; ?>" type="checkbox" checked="checked"/>
                                                                                            <?php if ($mf_auto !== null && !$mf_shown) { ?>
		<div style="color:#c0392b"><i class="fa fa-times-circle"></i> No file in the <?php echo htmlspecialchars($pdept_res['dept_name']); ?> folder matches the Move Files configuration (.<?php echo htmlspecialchars(implode(', .', $mf_auto)); ?>).</div>
		<?php } ?>
		<?php
                                                                                        }
																						elseif($app_res['cust_id']==37) {
                                                                                            ?>
                                                                                            <input name="attach_files[]" class="ace ace-checkbox-2" data-size="<?php echo $fsize; ?>" value="<?php echo $filename; ?>" type="checkbox" checked="checked" />
                                                                                            <?php
                                                                                        }elseif($app_res['cust_id']!=37) {
                                                                                            ?>
                                                                                            <input name="attach_files[]" class="ace ace-checkbox-2" data-size="<?php echo $fsize; ?>" value="<?php echo $filename; ?>" type="checkbox" />
                                                                                            <?php
                                                                                        }
																						
                                                                                            ?>
                                                                                                                                                                                  <span class="lbl"> <?php echo $filename; ?> </span> <span class="lbl"> <b>(<?php echo $filesize; ?>)</b> </span> <span class="lbl sc-mod" data-hours="<?php echo round((time() - $f->getMTime()) / 3600, 1); ?>"><i class="fa fa-clock-o"></i> Modified <?php echo date('d-M-Y H:i', $f->getMTime()); ?></span><?php if (preg_match('/\.(zip|pdf)$/i', $filename)) { ?> <a href="#" class="sc-view" data-file="<?php echo htmlspecialchars($filename, ENT_QUOTES); ?>">View contents</a><?php } ?> </label>

                                                                                            <?php
                                                                                        ?>
                                                                                </div>
                                                                                <?php
                                                                            }
                                                                        }
                                                                    }
                                                                    ?>
																	
																	   <?php
																	    if($app_res['cust_id']==37)
																	   { 
																	    $src_fs=$app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/Delivery/";
							    $fsizes = filesize($src_fs . "/" .$app_res['pub_id']."-ori.pdf");
                              // $filesize = formatSizeUnits($fsize);
							     if($fsizes >2500000)
								{
									//echo "1";
// Open the source directory to read in files
                                                                     $src_misc = "E:/New_Workflow_Files/JOURNAL/downloads/JOURNALS/ASP/misc/";
                                                                    if (is_dir($src_misc)) {
                                                                        $i = new DirectoryIterator($src_misc);
                                                                        foreach ($i as $f) {
                                                                            if ($f->isFile()) {
                                                                                $filename = $f->getFilename();
                                                                                // $filesize = filesize($src_misc."/".$f);
                                                                                $fsize = filesize($src_misc . "/" . $f);
                                                                                $filesize = formatSizeUnits($fsize);
                                                                                ?>
																				<?php
                                                                                        if (strpos($filename, '.txt') == true) {
                                                                                            ?>
                                                                                <div class="row">
                                                                                    <label>
                                                                                        
                                                                                            <input name="attach_filess[]" class="ace ace-checkbox-2" data-size="<?php echo $fsize; ?>" value="<?php echo $filename; ?>" type="checkbox" checked="checked"/>
																							                                                                                        <span class="lbl"> <?php echo $filename; ?> </span> <span class="lbl"> <b>(<?php echo $filesize; ?>)</b> </span> </label>

                                                                                           
                                                                                </div>
																				 <?php
                                                                                        } 
                                                                                        ?>
                                                                                <?php
                                                                            }
                                                                        }
                                                                    }
								}
                                                                    ?>
																	
																	   <?php
																	 
																	  
																	    $src_fs=$app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/Delivery/";
							    //$fsizes = filesize($src_fs . "/" .$app_res['pub_id']."-ori.pdf");
                              // $filesize = formatSizeUnits($fsize);
							    // if($fsizes >2500000)
								//{
									//echo "1";
// Open the source directory to read in files
                                                                     $src_misc = "E:/New_Workflow_Files/JOURNAL/downloads/JOURNALS/ASP/misc/";
                                                                    if (is_dir($src_misc)) {
                                                                        $i = new DirectoryIterator($src_misc);
                                                                        foreach ($i as $f) {
                                                                            if ($f->isFile()) {
                                                                                $filename = $f->getFilename();
                                                                                // $filesize = filesize($src_misc."/".$f);
                                                                                $fsize = filesize($src_misc . "/" . $f);
                                                                                $filesize = formatSizeUnits($fsize);
                                                                                ?>
																				<?php
                                                                                        if (strpos($filename, '.doc') == true) {
                                                                                            ?>
                                                                                <div class="row">
                                                                                    <label>
                                                                                        
                                                                                            <input name="attach_filesss[]" class="ace ace-checkbox-2" data-size="<?php echo $fsize; ?>" value="<?php echo $filename; ?>" type="checkbox" checked="checked"/>
																							                                                                                        <span class="lbl"> <?php echo $filename; ?> </span> <span class="lbl"> <b>(<?php echo $filesize; ?>)</b> </span> </label>
	
                                                                                </div>
																				
                                                                                            <?php
                                                                                        }
                                                                                        ?>
                                                                                <?php
                                                                            }
                                                                        }
                                                                    }
																	   }
                                                                    ?>
                                                                </div>
                                                            </div>
          </div>
		  
		<?php
		/* xProof / EProof / Xperttrack upload ONE fixed file from the Delivery folder (<article id>.zip, or .pdf); the tick-list is not used by them */
		$sc_fixed = array();
		foreach (array('zip', 'pdf') as $sc_x) { $sc_fi = sc_file_info_one($src, $app_res['pub_id'] . '.' . $sc_x); if ($sc_fi['exists']) { $sc_fixed[] = $sc_fi; } }
		?>
		<div class="container mt-4 mb-4 sc-flush" id="sc-fixed" style="display:none">
			<div class="form-group d-flex">
				<label class="control-label bolder blue control-label col-xs-12 col-sm-3 no-padding-right">File uploaded by this method</label>
				<div class="col-xs-12 col-sm-9">
					<?php if (!$sc_fixed) { ?>
						<div style="color:#c0392b"><i class="fa fa-times-circle"></i> <?php echo htmlspecialchars($app_res['pub_id']); ?>.zip was not found in the <?php echo htmlspecialchars($pdept_res['dept_name']); ?> folder.</div>
					<?php } foreach ($sc_fixed as $sc_fi) { ?>
						<div style="margin-bottom:3px"><b><?php echo htmlspecialchars($sc_fi['name']); ?></b> <b>(<?php echo htmlspecialchars($sc_fi['size_text']); ?>)</b>
							<span class="lbl"><i class="fa fa-clock-o"></i> Modified <?php echo htmlspecialchars($sc_fi['modified']); ?></span>
							<?php if (isset($sc_fi['count'])) { echo ' - ' . (int)$sc_fi['count'] . ' file' . ((int)$sc_fi['count'] == 1 ? '' : 's') . ' inside'; } if (!empty($sc_fi['pages'])) { echo ' - ' . (int)$sc_fi['pages'] . ' page' . ((int)$sc_fi['pages'] == 1 ? '' : 's'); } ?>
							<a href="#" class="sc-view" data-file="<?php echo htmlspecialchars($sc_fi['name'], ENT_QUOTES); ?>">View contents</a>
							<?php if (!empty($sc_fi['error'])) { ?><span style="color:#c0392b"> - <?php echo htmlspecialchars($sc_fi['error']); ?></span><?php } ?>
						</div>
					<?php } ?>
					<div class="text-muted" style="margin-top:4px"><i class="fa fa-info-circle"></i> This method uploads this one package from the Delivery folder; individual files cannot be chosen.</div>
				</div>
			</div>
		</div>
		<?php
		/* ---- files to be delivered, according to the Move Files configuration (inw_move_files + inw_move_files_dept_wise) ----
		   The destination department is the one the article is in now (Delivery); the accepted extensions are the union of what the
		   configured source departments send to it for this customer / journal / stage. */
		$mf = sc_move_config($db, $app_res, $src);
		$mf_exts = $mf['exts']; $mf_have = $mf['have']; $mf_other = $mf['other']; $mf_configured = $mf['configured'];
		?>
		<style>.container.sc-flush{width:auto;max-width:none;margin-left:0;margin-right:0;padding-left:0;padding-right:0} .sc-log td,.sc-log th{padding:4px 8px;font-size:12px;vertical-align:top} .sc-badge{display:inline-block;padding:1px 8px;border-radius:10px;color:#fff;font-size:11px} .sc-log details{margin-top:2px}</style>
		<style>
/* package dialogs (confirm + file contents): same look as the Comments/Instructions popup */
.sc-modal .modal-dialog{max-width:760px;width:94%}
.sc-modal .modal-content{border:0;border-radius:18px;overflow:hidden;box-shadow:0 20px 60px rgba(20,35,60,.35)}
.sc-modal .modal-header{background:linear-gradient(90deg,#e8863a,#de7a2a);color:#fff;border:0;padding:16px 24px}
.sc-modal .modal-title{font-size:17px;font-weight:700;color:#fff}
.sc-modal .modal-title .fa{margin-right:6px}
.sc-modal .modal-header .close,.sc-modal .modal-header .bootbox-close-button{color:#fff !important;opacity:.9 !important;text-shadow:none !important;font-size:28px;font-weight:300;margin-top:-4px}.sc-modal .modal-header .close:hover{opacity:1 !important}
.sc-modal .modal-body{background:#f3f6fb;padding:20px 24px;max-height:68vh;overflow:auto}
.sc-modal .modal-footer{background:#fff;border:0;padding:14px 24px}
.sc-modal .sc-navy{background:#1d3557;color:#fff;border:0;border-radius:10px;padding:9px 26px;font-weight:700}
.sc-modal .sc-navy:hover{background:#274a78;color:#fff}
.sc-modal .sc-ghost{background:#fff;color:#1d3557;border:1px solid #cfd8e6;border-radius:10px;padding:9px 18px;font-weight:600}
.sc-modal .sc-go{border-radius:10px;padding:9px 26px;font-weight:700}
.sc-modal .sc-go[disabled]{opacity:.45;cursor:not-allowed}
.sc-hero{background:linear-gradient(120deg,#1b3050,#2c5f9e);color:#fff;border-radius:16px;padding:16px 20px;margin-bottom:6px;text-align:left}
.sc-hero-k{font-size:11px;letter-spacing:.08em;opacity:.8}
.sc-hero-t{font-size:22px;font-weight:700;line-height:1.25;word-break:break-all}
.sc-hero-s{font-size:13px;opacity:.9}
.sc-pills{margin-top:10px}
.sc-pill{display:inline-block;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.28);border-radius:12px;padding:5px 12px;margin:0 8px 4px 0;font-size:12px}
.sc-pill.sc-p-ok{background:rgba(46,139,87,.55)} .sc-pill.sc-p-warn{background:rgba(214,137,16,.6)} .sc-pill.sc-p-err{background:rgba(192,57,43,.7)}
.sc-sec{margin:18px 0 8px;font-size:12px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:#1d3557;text-align:left}
.sc-sec .fa{margin-right:4px;color:#2c5f9e}
.sc-count{display:inline-block;min-width:20px;text-align:center;background:#dfe8f5;color:#2c5f9e;border-radius:10px;padding:0 7px;font-size:11px;margin-left:4px}
.sc-card{background:#fff;border-left:4px solid #2c5f9e;border-radius:12px;padding:11px 14px;margin-bottom:9px;box-shadow:0 1px 4px rgba(20,35,60,.1);text-align:left;font-size:13px;color:#223}
.sc-card.sc-ok{border-left-color:#2e8b57} .sc-card.sc-warn{border-left-color:#e0a21b} .sc-card.sc-err{border-left-color:#c0392b}
.sc-card.sc-slim{padding:8px 14px}
.sc-card.sc-slim .fa{margin-right:6px}
.sc-card.sc-ok .fa-check-circle{color:#2e8b57} .sc-card.sc-warn .fa{color:#e0a21b} .sc-card.sc-err .fa{color:#c0392b}
.sc-card-h{display:flex;gap:12px;align-items:flex-start}
.sc-ico{flex:0 0 36px;height:36px;border-radius:10px;background:#eaf0f9;color:#2c5f9e;display:flex;align-items:center;justify-content:center;font-size:17px}
.sc-card.sc-err .sc-ico{background:#fbe9e7;color:#c0392b} .sc-card.sc-warn .sc-ico{background:#fdf3dc;color:#c98a0a}
.sc-card-t{flex:1;min-width:0} .sc-card-t b{font-size:14px;word-break:break-all}
.sc-meta{margin-top:2px;color:#667} .sc-meta span{display:inline-block;margin:0 14px 2px 0} .sc-meta .fa{margin-right:4px;color:#8a97ab}
.sc-amber{color:#c98a0a} .sc-bad{color:#c0392b;margin-top:2px}
.sc-list{margin:-4px 0 9px 4px;background:#fff;border-radius:0 0 12px 12px;border:1px solid #e3e9f3;border-top:0;overflow:auto;font-size:12px}
.sc-list div{display:flex;justify-content:space-between;gap:10px;padding:4px 12px;border-top:1px solid #f0f3f8} .sc-list div:first-child{border-top:0}
.sc-list span{word-break:break-all} .sc-list em{font-style:normal;color:#8a97ab;white-space:nowrap}
.sc-kv{width:100%} .sc-kv td{padding:3px 0;vertical-align:top;word-break:break-word} .sc-kv td:first-child{width:84px;color:#7a869a;font-weight:600;padding-right:12px;white-space:nowrap}
.sc-dash{color:#9aa6b8} .sc-right{float:right;color:#667}
.sc-review{display:block;margin:14px 0 4px;font-weight:700;color:#1d3557;background:#fff;border-radius:12px;padding:11px 14px;box-shadow:0 1px 4px rgba(20,35,60,.1);cursor:pointer}
.sc-review input{margin-right:8px}
.sc-foot{margin-top:10px;color:#8a97ab;font-size:12px;text-align:left}
@keyframes sc-pulse{0%,100%{box-shadow:0 0 0 0 rgba(232,134,58,.7)}50%{box-shadow:0 0 0 8px rgba(232,134,58,0)}}.sc-legacy.sc-hl{animation:sc-pulse 1.1s ease-in-out infinite;outline:2px solid #e8863a;outline-offset:2px;border-radius:10px;color:#e8863a !important;background:#fff7ee}
.radio_btn .form-group{position:relative}.sc-legacy-link{position:absolute;right:4px;top:4px;font-size:12.5px;color:#8a97ab !important;text-decoration:none !important;white-space:nowrap}.sc-legacy-link:hover{color:#e8863a !important;text-decoration:underline !important}
@media (max-width:520px){.sc-legacy-link{position:static;display:block;margin:2px 0 6px}}
.sc-modal .sc-ta{width:100%;min-height:96px;border:1px solid #cfd8e6;border-radius:12px;padding:10px 12px;font:inherit;font-size:14px;resize:vertical;margin:2px 0 10px;background:#fff}
.sc-modal .sc-ta:focus{outline:0;border-color:#e8863a;box-shadow:0 0 0 3px rgba(232,134,58,.18)}
.sc-att{background:#fff;border:1px dashed #b9c6da;border-radius:12px;padding:10px 12px;margin-bottom:10px;text-align:left;font-size:13px}
.sc-att.over{background:#fff7ee;border-color:#e8863a}
.sc-att-btn{display:inline-block;background:#eaf0f9;color:#1d3557;border-radius:8px;padding:5px 12px;font-weight:600;cursor:pointer;margin:0 8px 0 0}
.sc-att-hint,.sc-att-lim,.sc-att-count{color:#8a97ab;font-size:12px}
.sc-att-list{list-style:none;margin:6px 0 0;padding:0}
.sc-att-list li{padding:3px 0;border-top:1px solid #f0f3f8} .sc-att-list em{font-style:normal;color:#8a97ab;margin-left:6px}
.sc-att-list .x{border:0;background:none;color:#c0392b;font-size:18px;line-height:1;float:right;cursor:pointer}
</style>
		<div class="container mt-4 mb-4 sc-flush">
			<div class="form-group d-flex">
				<label class="control-label bolder blue control-label col-xs-12 col-sm-3 no-padding-right">To be delivered (Move Files configuration)</label>
				<div class="col-xs-12 col-sm-9">
				<?php if (!$mf_configured) { ?>
					<div class="text-muted"><i class="fa fa-info-circle"></i> No Move Files configuration found for this customer / journal / stage into <?php echo htmlspecialchars($pdept_res['dept_name']); ?>.</div>
				<?php } else { ?>
					<?php foreach ($mf_exts as $mf_x => $mf_from) { $mf_ok = !empty($mf_have[$mf_x]); ?>
					<div style="margin-bottom:4px">
						<?php if ($mf_ok) { ?><i class="fa fa-check-circle" style="color:#2e8b57"></i><?php } else { ?><i class="fa fa-times-circle" style="color:#c0392b"></i><?php } ?>
						<b>.<?php echo htmlspecialchars($mf_x); ?></b>
						<span class="text-muted">(from <?php echo htmlspecialchars(implode(', ', array_keys($mf_from))); ?>)</span>
						<?php if ($mf_ok) { echo ' - ' . htmlspecialchars(implode(', ', $mf_have[$mf_x])); } else { ?>
							<span style="color:#c0392b"> - not found in the <?php echo htmlspecialchars($pdept_res['dept_name']); ?> folder</span>
						<?php } ?>
					</div>
					<?php } ?>
					<?php if ($mf_other) { ?>
					<div class="text-muted" style="margin-top:6px"><i class="fa fa-question-circle"></i> In the folder but not in the configuration: <?php echo htmlspecialchars(implode(', ', $mf_other)); ?></div>
					<?php } ?>
				<?php } ?>
				</div>
			</div>
		</div>
		<?php
		/* ---- dispatch log for this article: last 10 attempts, newest first ---- */
		sc_log_table($db);
		$sc_rows = array();
		$sc_lr = $db->query("SELECT * FROM inw_dispatch_log WHERE a_id = " . (int)$app_res['id'] . " ORDER BY id DESC LIMIT 10");
		while ($sc_lr && ($sc_row = $sc_lr->fetch_assoc())) { $sc_rows[] = $sc_row; }
		?>
		<div class="container mt-4 mb-4 sc-flush">
			<div class="form-group d-flex">
				<label class="control-label bolder blue control-label col-xs-12 col-sm-3 no-padding-right">Dispatch log</label>
				<div class="col-xs-12 col-sm-9">
				<?php if (!$sc_rows) { ?>
					<div class="text-muted">Nothing has been dispatched from this page for this article yet.</div>
				<?php } else { ?>
					<table class="sc-log" style="width:100%">
						<?php foreach ($sc_rows as $sc_row) {
							$sc_col = $sc_row['status'] == 'completed' ? '#2e8b57' : ($sc_row['status'] == 'failed' ? '#c0392b' : '#d68910');
							$sc_files = json_decode($sc_row['files'], true); if (!is_array($sc_files)) { $sc_files = array(); }
							$sc_steps_l = json_decode($sc_row['steps'], true); if (!is_array($sc_steps_l)) { $sc_steps_l = array(); }
							$sc_cf = json_decode($sc_row['confirm_info'], true);
						?>
						<tr style="border-top:1px solid #eee">
							<td style="white-space:nowrap"><?php echo htmlspecialchars($sc_row['started_at']); ?></td>
							<td><span class="sc-badge" style="background:<?php echo $sc_col; ?>"><?php echo htmlspecialchars($sc_row['status']); ?></span></td>
							<td>
								<b><?php echo htmlspecialchars($sc_row['user_name'] ?: ('user ' . $sc_row['user_id'])); ?></b> &middot; <?php echo htmlspecialchars($sc_row['method']); ?>
								&middot; to <?php echo htmlspecialchars($sc_row['mail_to']); ?>
								<?php if ($sc_row['result_message'] && $sc_row['status'] == 'failed') { ?><div style="color:#c0392b"><?php echo htmlspecialchars($sc_row['result_message']); ?></div><?php } ?>
								<details>
									<summary class="text-muted">Details</summary>
									<div>Subject: <?php echo htmlspecialchars($sc_row['subject']); ?></div>
									<?php if (!empty($sc_row['report_mail'])) { ?><div>Dispatch report e-mail: <b><?php echo htmlspecialchars($sc_row['report_mail']); ?></b></div><?php } ?>
									<div>From: <?php echo htmlspecialchars($sc_row['mail_from']); ?> &middot; Cc: <?php echo htmlspecialchars($sc_row['mail_cc']); ?> &middot; Bcc: <?php echo htmlspecialchars($sc_row['mail_bcc']); ?></div>
									<div>Stage <?php echo htmlspecialchars($sc_row['stage']); ?> &middot; <?php echo (int)$sc_row['duration_ms']; ?> ms &middot; IP <?php echo htmlspecialchars($sc_row['ip']); ?></div>
									<?php if ($sc_files) { ?><div style="margin-top:3px"><b>Files</b></div><ul style="margin:0 0 0 16px">
										<?php foreach ($sc_files as $sc_f) { ?><li><?php echo htmlspecialchars($sc_f['name']);
											if (isset($sc_f['size_text'])) { echo ' (' . htmlspecialchars($sc_f['size_text']) . ', modified ' . htmlspecialchars($sc_f['modified']) . ')'; }
											if (isset($sc_f['count'])) { echo ' - ' . (int)$sc_f['count'] . ' files inside'; }
											if (isset($sc_f['pages'])) { echo ' - ' . (int)$sc_f['pages'] . ' pages'; }
											if (isset($sc_f['md5'])) { echo ' - md5 ' . htmlspecialchars($sc_f['md5']); }
											if (isset($sc_f['note'])) { echo ' - ' . htmlspecialchars($sc_f['note']); } ?></li><?php } ?></ul><?php } ?>
									<?php if (is_array($sc_cf) && !empty($sc_cf['warnings'])) { ?><div style="color:#d68910;margin-top:3px"><b>Warnings shown to the user:</b> <?php echo htmlspecialchars(implode(' | ', $sc_cf['warnings'])); ?></div><?php } ?>
									<?php if (is_array($sc_cf) && !empty($sc_cf['confirmed_at'])) { ?><div class="text-muted">Confirmed in the package dialog at <?php echo htmlspecialchars($sc_cf['confirmed_at']); ?></div><?php } ?>
									<?php $sc_snap = !empty($sc_row['folder']) ? json_decode($sc_row['folder'], true) : array(); if (is_array($sc_snap) && $sc_snap) { ?>
									<div style="margin-top:3px"><b>Delivery folder at dispatch time (<?php echo count($sc_snap); ?> file<?php echo count($sc_snap) == 1 ? '' : 's'; ?>)</b></div>
									<table style="width:100%;font-size:12px">
										<?php foreach ($sc_snap as $sc_sf) { ?>
										<tr style="border-top:1px solid #f0f0f0"><td><?php echo htmlspecialchars($sc_sf['name']); ?><?php if (!empty($sc_sf['sent'])) { ?> <span class="sc-badge" style="background:#2e8b57">sent</span><?php } ?>
											<?php if (!empty($sc_sf['entries'])) { ?><details><summary class="text-muted"><?php echo (int)(isset($sc_sf['entries_total']) ? $sc_sf['entries_total'] : count($sc_sf['entries'])); ?> files inside the zip</summary>
												<div style="max-height:150px;overflow:auto;font-size:11px"><?php foreach ($sc_sf['entries'] as $sc_en) { echo htmlspecialchars($sc_en['name']) . ' (' . htmlspecialchars($sc_en['size']) . ')<br>'; } ?></div></details><?php } ?>
											<?php if (!empty($sc_sf['error'])) { ?><div style="color:#c0392b"><?php echo htmlspecialchars($sc_sf['error']); ?></div><?php } ?></td>
											<td style="white-space:nowrap;text-align:right"><?php echo isset($sc_sf['size_text']) ? htmlspecialchars($sc_sf['size_text']) : ''; ?></td>
											<td style="white-space:nowrap;text-align:right"><?php echo isset($sc_sf['modified']) ? htmlspecialchars($sc_sf['modified']) : ''; ?></td></tr>
										<?php } ?>
									</table><?php } ?>
									<?php if ($sc_steps_l) { ?><div style="margin-top:3px"><b>What happened</b></div><pre style="margin:0;font-size:11px;white-space:pre-wrap"><?php echo htmlspecialchars(implode("\n", $sc_steps_l)); ?></pre><?php } ?>
								</details>
							</td>
						</tr>
						<?php } ?>
					</table>
					<div style="margin-top:4px"><a href="dispatch_log.php?aid=<?php echo (int)$app_res['id']; ?>" target="_blank">Open full dispatch log</a></div>
				<?php } ?>
				</div>
			</div>
		</div>
										<div class="form-group" id="reminder" style="display:none;">
                                                                <label class="col-sm-3 control-label no-padding-right">Need Reminder Mail ?</label>
                                                                <div class="col-sm-9">
                                                                    <label>  <input name="reminder_mail" type="checkbox"  id="reminder_mail" class="ace ace-checkbox-2" value="1" /><span class="lbl"></span></label>
                                                                </div>
                                                            </div>
          <div class="container mt-5 mb-5 stc-actions">
          <div class="form-group d-flex justify-content-center">
            <div class="col-xs-12 d-flex gap-3">
                <input type="hidden" name="action" value="update">
                                                                    <input type="hidden" name="id" value="<?php echo $_REQUEST['id']; ?>" />
																	<?php 
																	if($app_res['cust_id']=='66' && $app_res['stage']!='FP')
																	{
																	?>
                                                                    <input type="hidden" name="corrections" value="<?php echo $inwed_run['corrections']; ?>" />
																	<?php 
																	}
																	?>
                                                                    <input type="hidden" name="fileattach" id="fileattach" value="0" />
                                                                    <input type="hidden" name="article_id" value="<?php echo $app_res['pub_id']; ?>" />
                                                                    <input type="hidden" name="cust_id" value="<?php echo $customer_info['id']; ?>" />
                                                                    <input type="hidden" name="cust_name" value="<?php echo $customer_info['cust_name']; ?>" />
                                                                    <input type="hidden" name="j_code" value="<?php echo $journal_info['j_code']; ?>" />
                                                                    <input type="hidden" name="j_id" value="<?php echo $journal_info['j_id']; ?>" />
                                                                    <input type="hidden" name="stage" value="<?php echo $app_res['stage']; ?>" />
                                                                    <input type="hidden" name="cust_name" value="<?php echo $customer_info['cust_name']; ?>" />
                                                                    <input type="hidden" name="work_dir" value="<?php echo $app_res['work_dir'] . $app_res['volume_folder'] . "/" . $app_res['pub_id'] . "/" . $app_res['stage'] . "/" . $pdept_res['dept_name'] . "/"; ?>">

                                                                    <input type="hidden" name="proof_dir" value="<?php echo $app_res['work_dir'] . $app_res['volume_folder'] . "/" . $app_res['pub_id'] . "/" . $app_res['stage'] . "/Proofing/"; ?>">
                                                                    <?php 
                                                                    if($app_res['cust_id'] == 7 && $app_res['stage'] == 'FP' && $app_res['pub_id']=='EPL_6767')
                                                                    {
                                                                    ?>
                                                                    <input type="hidden" name="linux_inw_id" value="<?php echo $linux_inw_id; ?>" />
                                                                    <?php 
                                                                    }
                                                                    ?>
               <a href="<?php echo $_COOKIE['set_url'];?>/edit-article/<?php echo $app_res['id'];?>" class="btn btn-danger">Edit Article </a>
                <input type="button" value="Cancel" onClick="window.history.go(-1);" class="btn btn-grey"/>
                <button type="submit" value="Save" id="saver" class="btn btn-info" name="submit">Send</button>
            </div>
        </div>
		</form>
        </div>
      </div>
      </div>
    </div>
</div>                                       
  <?php echo scripts(); ?>	

       <!-- Footer Section -->
       <?php echo footer(); ?>
</div>

     

        <!-- /.main-container --> 

        <!-- basic scripts --> 

        <!--[if !IE]> -->
        <script>
        /* the Send / Cancel bar is nested inside the Work DIR block by the old markup; make it a direct child of the form
           so it lays out (and sticks on phones) like every other card - it stays inside the form, so the submit still works */
        document.addEventListener('DOMContentLoaded', function () {
          var a = document.querySelector('.stc-actions'), f = a && a.closest('form');
          if (f && a.parentNode !== f) { f.appendChild(a); }
        });
        </script>
        <script src="<?php echo $_COOKIE['set_url'];?>/assets/js/jquery-2.1.4.min.js"></script>

        <!-- <![endif]--> 

        <!--[if IE]>
        <script src="assets/js/jquery-1.11.3.min.js"></script>
        <![endif]--> 
        <script type="text/javascript">
                                                                        if ('ontouchstart' in document.documentElement)
                                                                            document.write("<script src='<?php echo $_COOKIE['set_url'];?>/assets/js/jquery.mobile.custom.min.js'>" + "<" + "/script>");
        </script> 

        <!-- page specific plugin scripts --> 
        <script src="<?php echo $_COOKIE['set_url'];?>/assets/js/jquery.richtext.min.js"></script> 
        <script src="<?php echo $_COOKIE['set_url'];?>/assets/js/jquery.validate.min.js"></script> 

        <!-- ace scripts --> 
        <script src="<?php echo $_COOKIE['set_url'];?>/assets/js/ace-elements.min.js"></script> 
        <script src="<?php echo $_COOKIE['set_url'];?>/assets/js/ace.min.js"></script> 
        <script src="<?php echo $_COOKIE['set_url'];?>/assets/js/bootbox.js"></script> 


       <!-- inline scripts related to this page --> 
        <script type="text/javascript">
                                                                      jQuery(function ($) {
                                                                            $('#validation-form').validate({errorClass: 'help-block'});
                                                                            $('#txtBody').richText();//this will create the default editor will all buttons	
                                                                        })


																		$(window).load(function() 
																		{
																			$(".loader").fadeOut("slow");
																		});
                                                                        $('#Sunrise_Upload_Files_to_AWS_Server').click(function () {
																		
																			$(".loader").fadeIn("slow");
		var a_id = $("input[name='id']").val();
		 //alert(a_id);
		$.ajax({
					type:'POST',
					url:'<?php echo $_COOKIE['set_url'];?>/Sunrise_Upload_Files_to_AWS_Server.php',
					data:'a_id='+a_id+'&type=1',
					success:function(result){
						$(".loader").fadeOut("slow");
						 response = JSON.parse(result);
					
						 if(response.status == 1)
						 {
							bootbox.dialog(
							{
								message: '<br/><div class="alert alert-success">'+ response.message +'</div>',
								buttons:
								{
									"button" :
									{
										"label" : "Close",
										"className" : "btn-sm",
										"callback": function() {
											
										}
									}							
								} 						
							});
						}
						else
						{
							bootbox.dialog(
							{
								message: '<br/><div class="alert alert-warning">'+ response.message +'</div>',
								buttons:
								{
									"button" :
									{
										"label" : "Close",
										"className" : "btn-sm",
										"callback": function() {
											
										}
									}							
								} 						
							});
						}
						       
					}
				});  
			});                                                              // $('#txtBody').richText();//this will create the default editor will all buttons	
                                                                       // })



                                                                        $('#validation-form').submit(function () {

                                                                            var article_id = $("input[name='article_id']").val();
                                                                            var cust_name = $("input[name='cust_name']").val();
                                                                            var j_code = $("input[name='j_code']").val();
                                                                            var j_id = $("input[name='j_id']").val();
                                                                            var cust_id = $("input[name='cust_id']").val();
                                                                            var stage = $("input[name='stage']").val();


                                                                            var returnval = true;
                                                                            $.ajax({
                                                                                url: 'main_sample.php',
                                                                                async: false,
                                                                                type: 'POST',
                                                                                data: {article_id: article_id, cust_name: cust_name, j_code: j_code, j_id: j_id, cust_id: cust_id, stage: stage},
                                                                                context: this,
                                                                                success: function (result) {
                                                                                    // alert(result);
                                                                                    if (result == 1)
                                                                                    {
                                                                                        returnval = true;
                                                                                        this.submit();
                                                                                    } else if (result == 0)
                                                                                    {
                                                                                        returnval = false;
                                                                                        alert("No Proper File Name | Text Name  Or Modify Configuration");
                                                                                    } else if (result == 2)
                                                                                    {
                                                                                        returnval = false;
                                                                                        alert("Sub Folder Must Contain Files Or Modify Configuration.");
                                                                                    } else if (result == 3)
                                                                                    {
                                                                                        returnval = false;
                                                                                        alert("Folder Must Not Contain Zip Or Modify Configuration.");
                                                                                    } else if (result == 7)
                                                                                    {
                                                                                        returnval = true;
                                                                                        ;
                                                                                    } else
                                                                                    {
                                                                                        returnval = true;
                                                                                    }

                                                                                }
                                                                            });
                                                                            return returnval;

                                                                        });

        </script> 

        <script type="text/javascript">
            $(function () {


                $("input[id$='optionRad_0']").click(function () {
                    var re = $(this).val();
                    $("#fileattach").val(0);
                    $("#div1,#emaildiv").show();
                    $("#reminder").hide();
                    $('#image-file').val('');

                });


                $("input[id$='optionRad_1']").click(function () {
                    var tog = $(this).val();
                    $("#div1").hide();
                    $("#reminder").show();
                    $("#fileattach").val(0);
                    $("#emaildiv").show();
                    $('#image-file').val('');

                });



                $("input[id$='optionRad_2']").click(function () {
                    var mail = $(this).val();
                    $("#emaildiv").hide();
                    $("#fileattach").val(0);
                    $("#div1").show();
                    $("#reminder").hide();
                });


                $("input[id$='optionRad_3']").click(function () {
                    var mail = $(this).val();
                    $("#fileattach").val(3);
                    $("#div1").hide();
                    $("#emaildiv").show();
                    $('#image-file').val('');
                    $("#reminder").hide();

                    $("#image-file").bind('change', function () {
                        var fat = $("#fileattach").val();
                        if (fat == 3)
                        {
                            var res = this.files[0].size / 1024 / 1024;
                            if (res > 20) {
                                alert('File size exceeds 20 MB Cannot Accept');
                                $('#image-file').val(''); //for clearing with Jquery
                            }
                        }
                    });
                });
				
				
				$("input[id$='optionRad_4']").click(function () {
					var tog = $(this).val();
                    $("#div1").hide();
                    $("#reminder").show();
                    $("#fileattach").val(0);
                    $("#emaildiv").show();
                    $('#image-file').val('');
                });
				
				$("input[id$='optionRad_5']").click(function () {
					var tog = $(this).val();
                    $("#div1").hide();
                    $("#reminder").show();
                    $("#fileattach").val(0);
                    $("#emaildiv").show();
                    $('#image-file').val('');
                });
				
                $("#saver").click(function ()
                {
                    var fat = $("#fileattach").val();
                    if (fat == 3)
                    {
                        var x;
                        x = document.getElementById("image-file").value;
                      /*   if (x == "") {
                            alert("Attachment is Empty Cannot Send");
                            return false;
                        }
                        ;
 */
                    }
                    ;

                });

<?php
if (($customer_info['id'] == 42 && $app_res['stage']== "FP") || ($customer_info['id'] == 72  && $app_res['stage']== "FP")  || $customer_info['id'] == 43 || $customer_info['id'] == 44 || $customer_info['id'] == 66 || $customer_info['id']== 77 || $customer_info['id'] == 79 || $customer_info['id'] == 76  || $customer_info['id'] == 84) {
    ?>
                    $("input[id$='optionRad_1']").click();
    <?php
}
else if ($customer_info['id'] == 57  || $customer_info['id'] == 58 || $customer_info['id'] == 53) {
    ?>
                    $("input[id$='optionRad_5']").click();
    <?php
}else if ($journal_info['ftp_send'] == 1 && $journal_info['j_email_send'] == 1) {
    ?>
                    $("input[id$='optionRad_0']").click();
    <?php
}  else if ($journal_info['ftp_send'] == 1) {
    ?>
                    $("input[id$='optionRad_2']").click();
    <?php
} else if ($journal_info['j_email_send'] == 1 || ($customer_info['id'] == 42 && $app_res['stage']== "REV1")) {
    ?>
                    $("input[id$='optionRad_3']").click();
    <?php
}else if ($customer_info['id'] == 34) {
    ?>  
                    $("input[id$='optionRad_4']").click();
    <?php
}

?>
                $("#total_file_size_div").hide();
                /* total size of the ticked files (no size limit) */
                function scShowTotal() {
                    var t = 0;
                    $(".ace-checkbox-2:checked").each(function () { t += parseInt($(this).attr("data-size")) || 0; });
                    if (t != 0) { $("#total_file_size_div").show(); $("#total_file_size").html(formatSizeUnits(t)); } else { $("#total_file_size_div").hide(); }
                }
                window.scShowTotal = scShowTotal;
                $('.ace-checkbox-2').click(scShowTotal);
            });
            $(window).load(function () { if (window.scShowTotal) { window.scShowTotal(); } });

            /* ---- package check: modified dates, zip contents, PDF pages, Move Files configuration; reviewed + confirmed before Send (and logged) ---- */
            (function ($) {
                var STALE_HOURS = 24, aid = $("input[name='id']").val(), pubId = $("input[name='article_id']").val(), $form = $('#saver').closest('form');
                function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
                $('.sc-mod').each(function () { if (parseFloat($(this).data('hours')) > STALE_HOURS) { $(this).css('color', '#d68910').attr('title', 'Older than ' + STALE_HOURS + ' hours - check this is the latest file'); } });
                function fetchInfo(names, cb, fixed) {
                    $.post(location.href, {sc_file_info: 1, id: aid, files: names, fixed: fixed ? 1 : 0}, function (r) { cb(r && r.files ? r : null); }, 'json')
                     .fail(function () { cb(null); });
                }
                /* the tick-list only matters for FTP / mail-attach (1, 2, 3); xProof, EProof and Xperttrack (4, 5, 6) upload one fixed package */
                function usesTicks() { return ['4', '5', '6'].indexOf($("input[name='optionRad']:checked").val()) < 0; }
                function applyMethod() {
                    var t = usesTicks();
                    $('#sc-ticklist').toggle(t).find("input").prop('disabled', !t);
                    $('#sc-fixed').toggle(!t);
                }
                $(document).on('change click', "input[name='optionRad']", function () { setTimeout(applyMethod, 0); });
                setTimeout(applyMethod, 0); $(window).on('load', function () { setTimeout(applyMethod, 300); });
                /* the value the server will see: PHP keeps the last field of a repeated name */
                function fv(name) { var v = ''; $.each($form.serializeArray(), function (i, f) { if (f.name === name) { v = f.value; } }); return $.trim(v); }
                function title(icon, t) { return '<i class="fa ' + icon + '"></i> ' + t; }
                function sec(t, n, icon) { return '<div class="sc-sec"><i class="fa ' + icon + '"></i> ' + t + (n != null ? ' <span class="sc-count">' + n + '</span>' : '') + '</div>'; }
                function pill(t, cls) { return '<span class="sc-pill ' + (cls || '') + '">' + t + '</span>'; }
                function card(f, open) {
                    var state = !f.exists || f.error ? 'sc-err' : (f.age_hours > STALE_HOURS ? 'sc-warn' : 'sc-ok');
                    var icon = f.type == 'zip' ? 'fa-file-archive-o' : (f.type == 'pdf' ? 'fa-file-pdf-o' : 'fa-file-o');
                    var h = '<div class="sc-card ' + state + '"><div class="sc-card-h"><span class="sc-ico"><i class="fa ' + icon + '"></i></span><div class="sc-card-t"><b>' + esc(f.name) + '</b>';
                    if (!f.exists) { return h + '<div class="sc-bad">File not found in the folder.</div></div></div></div>'; }
                    var stale = f.age_hours > STALE_HOURS;
                    h += '<div class="sc-meta"><span><i class="fa fa-hdd-o"></i> ' + esc(f.size_text) + '</span><span class="' + (stale ? 'sc-amber' : '') + '"><i class="fa fa-clock-o"></i> Modified ' + esc(f.modified) + (stale ? ' &middot; older than ' + STALE_HOURS + ' hours' : '') + '</span>';
                    if (f.type == 'zip' && f.entries) { h += '<span><i class="fa fa-folder-open-o"></i> ' + f.count + ' file' + (f.count == 1 ? '' : 's') + ' inside</span>'; }
                    if (f.type == 'pdf' && !f.error) { h += '<span><i class="fa fa-file-text-o"></i> ' + (f.pages ? f.pages + ' page' + (f.pages == 1 ? '' : 's') : 'page count not available') + '</span>'; }
                    h += '</div>';
                    if (f.error) { h += '<div class="sc-bad"><i class="fa fa-exclamation-triangle"></i> ' + esc(f.error) + '</div>'; }
                    h += '</div></div>';
                    if (f.type == 'zip' && f.entries && f.entries.length) {
                        h += '<div class="sc-list" style="max-height:' + (open ? '240' : '120') + 'px">';
                        $.each(f.entries, function (i, e) { h += '<div><span>' + esc(e.name) + '</span><em>' + esc(e.size) + '</em></div>'; });
                        h += '</div>';
                    }
                    return h + '</div>';
                }
                function row(k, v) { return '<tr><td>' + k + '</td><td>' + (v === '' ? '<span class="sc-dash">-</span>' : v) + '</td></tr>'; }

                $(document).on('click', '.sc-view', function (e) {
                    e.preventDefault();
                    var n = $(this).data('file');
                    fetchInfo([n], function (r) {
                        var f = r && r.files.length ? r.files[0] : null;
                        bootbox.dialog({ className: 'sc-modal', title: title('fa-folder-open', 'File contents'),
                            message: f ? '<div class="sc-hero"><div class="sc-hero-k">ARTICLE</div><div class="sc-hero-t">' + esc(pubId) + '</div><div class="sc-hero-s">' + esc($("input[name='cust_name']").val()) + ' &middot; ' + esc($("input[name='j_code']").val()) + ' &middot; stage ' + esc($("input[name='stage']").val()) + '</div></div>' + sec('File', null, 'fa-file-o') + card(f, true)
                                       : '<div class="sc-card sc-err"><div class="sc-bad">Could not read the file.</div></div>',
                            buttons: { close: { label: 'Close', className: 'sc-navy' } } });
                    });
                });

                var confirmed = false;
                $form.on('submit', function (e) {
                    if (confirmed) { return true; }
                    var fixed = !usesTicks();
                    var names = fixed ? [] : $("input[name='attach_files[]']:checked, input[type='hidden'][name='attach_files[]']").not(':disabled').map(function () { return this.value; }).get();
                    var up = fixed ? null : ($('#image-file')[0] && $('#image-file')[0].files && $('#image-file')[0].files[0]);
                    if (!names.length && !fixed) { return true; }          /* nothing from the folder is ticked: behave as before */
                    e.preventDefault();
                    $('#saver').prop('disabled', true);
                    fetchInfo(names, function (r) {
                        $('#saver').prop('disabled', false);
                        var fs = r ? r.files : [], cfg = r ? r.config : null, errors = [], warnings = [], h = '';
                        if (!r) { warnings.push('The files could not be checked on the server.'); }

                        var $rad = $("input[name='optionRad']:checked"), method = $.trim($("label[for='" + $rad.attr('id') + "']").text());
                        var sendsMail = String($rad.val()) !== '2';   /* option 2 = Only FTP & manual mail: the system sends no e-mail */
                        if (sendsMail && !fv('txtTo')) { warnings.push('No recipient (To) address.'); }
                        if (sendsMail && !fv('txtSub')) { warnings.push('The subject is empty.'); }

                        /* files first: they feed the checks and the summary pills */
                        var total = 0, files_h = '';
                        $.each(fs, function (i, f) {
                            files_h += card(f, false);
                            if (!f.exists) { errors.push(f.name + ' is not in the folder.'); return; }
                            total += f.size || 0;
                            if (f.error) { errors.push(f.name + ': ' + f.error); }
                            if (f.type == 'zip' && f.entries && !f.count) { errors.push(f.name + ' contains no files.'); }
                            if (f.age_hours > STALE_HOURS) { warnings.push(f.name + ' was last modified ' + f.modified + ' (older than ' + STALE_HOURS + ' hours).'); }
                            if ((f.type == 'zip' || f.type == 'pdf') && pubId && f.name.toLowerCase().indexOf(String(pubId).toLowerCase()) < 0) { warnings.push(f.name + ' does not contain the article id ' + pubId + ' in its name.'); }
                        });
                        if (up) { total += up.size; files_h += '<div class="sc-card sc-ok"><div class="sc-card-h"><span class="sc-ico"><i class="fa fa-upload"></i></span><div class="sc-card-t"><b>' + esc(up.name) + '</b><div class="sc-meta"><span><i class="fa fa-hdd-o"></i> ' + esc(formatSizeUnits(up.size)) + '</span><span>uploaded from this PC</span></div></div></div></div>'; }

                        var cfg_h = '';
                        if (cfg) {
                            if (!cfg.configured) { cfg_h += '<div class="sc-card"><div class="sc-dash">No configuration found for this customer / journal / stage.</div></div>'; }
                            $.each(cfg.rows, function (i, c) {
                                var picked = $.grep(fs, function (f) { return f.name.toLowerCase().slice(-(c.ext.length + 1)) === '.' + c.ext; }).length > 0;
                                var inFolder = c.found.length > 0;
                                var st = inFolder && picked ? 'sc-ok' : (inFolder ? 'sc-warn' : 'sc-err');
                                var note = !inFolder ? 'not in the folder' : (picked ? 'selected' : 'in the folder but NOT selected');
                                cfg_h += '<div class="sc-card ' + st + ' sc-slim"><b>.' + esc(c.ext) + '</b> <span class="sc-dash">from ' + esc(c.from.join(', ')) + '</span><span class="sc-right">' + note + '</span></div>';
                                if (!inFolder) { warnings.push('.' + c.ext + ' is required by the Move Files configuration but is not in the folder.'); }
                                else if (!picked) { warnings.push('.' + c.ext + ' is required by the Move Files configuration but is not selected (' + c.found.join(', ') + ').'); }
                            });
                        }

                        /* hero */
                        h += '<div class="sc-hero"><div class="sc-hero-k">ARTICLE</div><div class="sc-hero-t">' + esc(pubId) + '</div><div class="sc-hero-s">' + esc($("input[name='cust_name']").val()) + ' &middot; ' + esc($("input[name='j_code']").val()) + ' &middot; stage ' + esc($("input[name='stage']").val()) + '</div>'
                           + '<div class="sc-pills">' + pill('<b>' + (fs.length + (up ? 1 : 0)) + '</b> file' + (fs.length + (up ? 1 : 0) == 1 ? '' : 's')) + pill('<b>' + esc(formatSizeUnits(total)) + '</b> total')
                           + (errors.length ? pill('<b>' + errors.length + '</b> problem' + (errors.length == 1 ? '' : 's'), 'sc-p-err') : '') + (warnings.length ? pill('<b>' + warnings.length + '</b> warning' + (warnings.length == 1 ? '' : 's'), 'sc-p-warn') : '')
                           + (!errors.length && !warnings.length ? pill('<i class="fa fa-check"></i> all checks passed', 'sc-p-ok') : '') + '</div></div>';

                        /* what will be sent */
                        h += sec('Dispatch', null, 'fa-paper-plane-o') + '<div class="sc-card"><table class="sc-kv">' + row('Method', esc(method)) + (sendsMail ? row('From', esc(fv('txtFrom'))) + row('To', esc(fv('txtTo'))) + row('Cc', esc(fv('txtCc'))) + row('Bcc', esc(fv('txtBcc'))) : row('E-mail', '<span class="sc-dash">Not sent by the system - the mail is sent manually.</span>')) + '</table></div>';
                        h += sec('Files to be sent', fs.length + (up ? 1 : 0), 'fa-paperclip') + files_h;
                        if (cfg) { h += sec('Move Files configuration', cfg.rows.length, 'fa-exchange') + cfg_h; }

                        /* verdict */
                        h += sec('Checks', errors.length + warnings.length, 'fa-check-square-o');
                        if (!errors.length && !warnings.length) { h += '<div class="sc-card sc-ok sc-slim"><i class="fa fa-check-circle"></i> All checks passed.</div>'; }
                        $.each(errors, function (i, t) { h += '<div class="sc-card sc-err sc-slim"><i class="fa fa-times-circle"></i> ' + esc(t) + '</div>'; });
                        $.each(warnings, function (i, t) { h += '<div class="sc-card sc-warn sc-slim"><i class="fa fa-exclamation-triangle"></i> ' + esc(t) + '</div>'; });
                        if (!errors.length) { h += '<label class="sc-review"><input type="checkbox" id="sc-reviewed"> I have reviewed the details above and want to send.</label>'; }
                        else { h += '<div class="sc-card sc-err sc-slim" style="font-weight:600"><i class="fa fa-ban"></i> Please fix the problems marked in red before sending.</div>'; }
                        h += '<div class="sc-foot"><i class="fa fa-history"></i> This dispatch is recorded in the dispatch log.</div>';

                        var buttons = { cancel: { label: 'Cancel, let me check', className: 'sc-ghost' } };
                        if (!errors.length) {
                            buttons.ok = { label: 'Yes, send', className: 'btn-success sc-go', callback: function () {
                                var info = { confirmed_at: new Date().toLocaleString(), reviewed: true, warnings: warnings, files: $.map(fs, function (f) { return f.name; }) };
                                if (!$form.find("input[name='sc_confirm']").length) { $form.append('<input type="hidden" name="sc_confirm">'); }
                                $form.find("input[name='sc_confirm']").val(JSON.stringify(info));
                                confirmed = true; HTMLFormElement.prototype.submit.call($form[0]);
                            } };
                        }
                        var dlg = bootbox.dialog({ className: 'sc-modal', title: title('fa-paper-plane', 'Confirm package before sending'), message: h, buttons: buttons });
                        var $ok = dlg.find('.sc-go').prop('disabled', true);
                        dlg.on('change', '#sc-reviewed', function () { $ok.prop('disabled', !this.checked); });
                    }, fixed);
                    return false;
                });
            })(jQuery);
            /* ---- opened the old Project Dispatch page directly? say so, and point at "Use old version" ---- */
            (function ($) {
                var why = <?php echo json_encode(isset($_GET['sc_direct']) ? (string)$_GET['sc_direct'] : '', JSON_HEX_TAG | JSON_HEX_AMP); ?>;
                if (why !== 'url' && why !== 'noreason') { return; }
                try { history.replaceState(null, '', location.pathname + location.search.replace(/([?&])sc_direct=[^&]*&?/, '$1').replace(/[?&]$/, '')); } catch (e) {}   /* a refresh must not show it again */
                setTimeout(function () {
                    bootbox.dialog({ className: 'sc-modal', closeButton: false,
                        title: '<i class="fa fa-info-circle"></i> ' + (why === 'noreason' ? 'Please give a reason first' : 'You opened the old Project Dispatch page directly'),
                        message: '<div class="sc-card" style="border-left-color:#e8863a">' + (why === 'noreason'
                            ? 'The old Project Dispatch page can only be opened after you tell us why you need it. We have brought you to the new page.<br><br>Use the highlighted <b>Use old version</b> link and enter your reason.'
                            : 'The new Project Dispatch page is now the standard one, so we have brought you here.<br><br>If you really need the old page, use the highlighted <b>Use old version</b> link and tell us why.') + '</div>',
                        buttons: { ok: { label: 'Show me', className: 'sc-navy', callback: function () {
                            var $l = $('.sc-legacy').addClass('sc-hl');
                            $('html,body').animate({ scrollTop: 0 }, 250);
                            setTimeout(function () { $l.removeClass('sc-hl'); }, 9000);
                        } } } });
                }, 350);
            })(jQuery);
            /* ---- Use old version: reason is mandatory (screenshots / files optional); it is e-mailed to the configured recipients and logged, then the old page opens ---- */
            (function ($) {
                var LIM = <?php echo json_encode(sw_switch_limits()); ?>, OK_EXT = <?php echo json_encode(sw_switch_allowed_ext()); ?>,
                    OLD_URL = <?php echo json_encode($_COOKIE['set_url'] . '/send_to_client.php?id=' . (int)$app_res['id'] . '&legacy=1'); ?>, busy = false;
                function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
                function mb(n) { return n < 1048576 ? Math.max(1, Math.round(n / 1024)) + ' KB' : (n / 1048576).toFixed(1) + ' MB'; }
                $(document).on('click', '.sc-legacy', function (e) {
                    e.preventDefault();
                    if (busy) { return; }
                    var picked = [], shot = 0, dlg;
                    function err(t) { dlg.find('#sc-sw-err').text(t || '').toggle(!!t); }
                    function render() {
                        var $ul = dlg.find('.sc-att-list').empty();
                        $.each(picked, function (i, f) { $ul.append('<li><i class="fa ' + (/^image\//.test(f.type) ? 'fa-file-image-o' : 'fa-file-o') + '"></i> <span class="n">' + esc(f.name) + '</span> <em>' + mb(f.size) + '</em> <button type="button" class="x" data-i="' + i + '" title="Remove">&times;</button></li>'); });
                        dlg.find('.sc-att-count').text(picked.length ? picked.length + ' of ' + LIM.files + ' attached' : '');
                    }
                    function addFiles(list) {
                        var msg = '', total = 0; $.each(picked, function (i, f) { total += f.size; });
                        $.each(list, function (i, f) {
                            var name = f.name && f.name !== 'image.png' ? f.name : 'screenshot-' + (++shot) + '.png', ext = (name.split('.').pop() || '').toLowerCase();
                            if (OK_EXT.indexOf(ext) < 0) { msg = '"' + name + '" is not an allowed type (images, PDF, Word, Excel, CSV, text, log, ZIP).'; return; }
                            if (f.size > LIM.each) { msg = '"' + name + '" is larger than ' + mb(LIM.each) + '.'; return; }
                            if (picked.length >= LIM.files) { msg = 'You can attach up to ' + LIM.files + ' files.'; return; }
                            if (total + f.size > LIM.total) { msg = 'The attachments are too large together (limit ' + mb(LIM.total) + ').'; return; }
                            try { if (f.name !== name) { f = new File([f], name, { type: f.type }); } } catch (er) {}
                            picked.push(f); total += f.size;
                        });
                        render(); err(msg);
                    }
                    var msgHtml = '<div class="sc-card" style="border-left-color:#e8863a">Please tell us what went wrong with the new Project Dispatch page. Your note is sent to the support team so it can be fixed.</div>'
                        + '<textarea id="sc-reason" rows="4" class="sc-ta" placeholder="Reason for switching (minimum 10 characters)..."></textarea>'
                        + '<div class="sc-att"><label class="sc-att-btn"><i class="fa fa-paperclip"></i> Choose file<input type="file" id="sc-att-in" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.log,.zip" style="display:none"></label>'
                        + '<span class="sc-att-hint">or drop / paste a screenshot (Ctrl+V)</span> <span class="sc-att-count"></span>'
                        + '<div class="sc-att-lim">Optional &middot; up to ' + LIM.files + ' files &middot; ' + mb(LIM.each) + ' each</div><ul class="sc-att-list"></ul></div>'
                        + '<div id="sc-sw-err" class="sc-card sc-err sc-slim" style="display:none"></div>';
                    dlg = bootbox.dialog({ className: 'sc-modal', title: '<i class="fa fa-undo"></i> Switch to the old Project Dispatch page?', message: msgHtml, buttons: {
                        cancel: { label: 'Stay here', className: 'sc-ghost' },
                        ok: { label: 'Send & switch', className: 'btn-success sc-go', callback: function () {
                            var reason = $.trim(dlg.find('#sc-reason').val());
                            if (reason.length < 10) { err('Please enter at least 10 characters.'); return false; }
                            var fd = new FormData();
                            fd.append('sc_switch_legacy', 1); fd.append('aid', $("input[name='id']").val()); fd.append('reason', reason);
                            $.each(picked, function (i, f) { fd.append('att[]', f, f.name); });
                            busy = true; err('');
                            dlg.find('.sc-go').prop('disabled', true).text('Sending...'); dlg.find('.sc-ghost').prop('disabled', true);
                            $.ajax({ url: location.pathname + location.search, type: 'POST', dataType: 'json', cache: false, data: fd, processData: false, contentType: false })
                             .done(function (r) {
                                 if (r && r.ok) { dlg.find('.sc-go').text('Opening the old page...'); location.href = OLD_URL; return; }
                                 busy = false; dlg.find('.sc-go').prop('disabled', false).text('Send & switch'); dlg.find('.sc-ghost').prop('disabled', false); err((r && r.message) || 'Please try again.');
                             })
                             .fail(function () { busy = false; dlg.find('.sc-go').prop('disabled', false).text('Send & switch'); dlg.find('.sc-ghost').prop('disabled', false); err('The request failed (the attachments may be too large for the server). Please try again with smaller files.'); });
                            return false;
                        } }
                    } });
                    dlg.on('shown.bs.modal', function () { dlg.find('#sc-reason').focus(); });
                    dlg.on('change', '#sc-att-in', function () { addFiles(this.files); this.value = ''; });
                    dlg.on('click', '.sc-att-list .x', function () { picked.splice($(this).data('i'), 1); render(); err(''); });
                    dlg.on('paste', function (ev) {
                        var items = (ev.originalEvent.clipboardData || {}).items || [], got = [];
                        for (var k = 0; k < items.length; k++) { if (items[k].kind === 'file') { var f = items[k].getAsFile(); if (f) { got.push(f); } } }
                        if (got.length) { ev.preventDefault(); addFiles(got); }
                    });
                    dlg.on('dragover', '.sc-att', function (ev) { ev.preventDefault(); $(this).addClass('over'); })
                       .on('dragleave drop', '.sc-att', function () { $(this).removeClass('over'); })
                       .on('drop', '.sc-att', function (ev) { ev.preventDefault(); addFiles(ev.originalEvent.dataTransfer.files); });
                });
            })(jQuery);


            function formatSizeUnits(bytes) {
                if (bytes >= 1073741824) {
                    bytes = (bytes / 1073741824).toFixed(2) + ' GB';
                } else if (bytes >= 1048576) {
                    bytes = (bytes / 1048576).toFixed(2) + ' MB';
                } else if (bytes >= 1024) {
                    bytes = (bytes / 1024).toFixed(2) + ' KB';
                } else if (bytes > 1) {
                    bytes = bytes + ' bytes';
                } else if (bytes == 1) {
                    bytes = bytes + ' byte';
                } else {
                    bytes = '0 byte';
                }
                return bytes;
            }

$(document).ready(function() {
	
	validateMultipleEmails($('#txtTo').val());
    $('#txtTo').blur(function() {
        validateMultipleEmails($('#txtTo').val());
    });
});

function validateMultipleEmails(emailInput) {
    // Get value on emails input as a string
    var emails = emailInput;
    
    // Split string by comma into an array
    emails = emails.split(",");

    var valid = true;
    var regex = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    
    var invalidEmails = [];
    
    for (var i = 0; i < emails.length; i++) {
        // Trim whitespaces from email address
        emails[i] = emails[i].trim();
        
        // Check email against our regex to determine if email is valid
        if( emails[i] == "" || ! regex.test(emails[i])){
            invalidEmails.push(emails[i]);
        }
    }
    
    // Output invalid emails
    $('.validemail .text-danger').remove();
    if(invalidEmails != 0) {
        $('.validemail').append('<p class="text-danger">Invalid emails: ' + invalidEmails.join(', ') + '</p>');
		$('#saver').hide();
    }
	else
	{
		$('#saver').show();
	}
}

        </script>
    </body>
</html>
