<?php
/* PDF compare tool: "which article is this file?"
   A compared PDF is named after its article's Pub ID (TSP_CMC_88018.pdf).  This looks that Pub ID up in the live table
   and, if it is not there, in the archive, and tells the user what it found so they can see what went wrong:
   not found / archived / delivered / withdrawn / on hold / in progress, the current stage and status, and the PRQC folder
   the report would be filed in (and whether the server can reach and write to it).
   Used by compare-pdf-check.php (shown on the page) and download-compare-pdf-report.php (does the filing). */

if (!defined('TOOL_DIR')) {
	include_once __DIR__ . '/global_variables.php';
}

/* Windows Explorer link, as on the Start Work page: the server drive (WF_DIR) is the user's mapped drive (TOOL_DIR) */
function cmp_explorer_link($serverPath)
{
	if ($serverPath === '') {
		return '';
	}
	return 'file:///' . str_replace(WF_DIR, TOOL_DIR, $serverPath);
}

/* "Source" folder of the article stage: the same path with production -> downloads (as on Start Work) */
function cmp_source_link($workDir, $volume, $pubId, $stage)
{
	if ($workDir === '' || $volume === '' || $stage === '') {
		return '';
	}
	return 'file:///' . str_replace('production', 'downloads', str_replace(WF_DIR, TOOL_DIR, $workDir)) . $volume . '/' . $pubId . '/' . $stage;
}

/* every report filing attempt is logged (compare-pdf-log.php shows it) */
function cmp_ensure_log_table($db)
{
	$db->query("CREATE TABLE IF NOT EXISTS compare_pdf_filing_log (
		id INT AUTO_INCREMENT PRIMARY KEY,
		logged_at DATETIME NOT NULL,
		user_id VARCHAR(50) NOT NULL DEFAULT '',
		job_id VARCHAR(60) NOT NULL DEFAULT '',
		file1_name VARCHAR(255) NOT NULL DEFAULT '',
		file2_name VARCHAR(255) NOT NULL DEFAULT '',
		file1_size BIGINT NOT NULL DEFAULT 0,
		file2_size BIGINT NOT NULL DEFAULT 0,
		pub_id VARCHAR(100) NOT NULL DEFAULT '',
		found_in VARCHAR(10) NOT NULL DEFAULT '',
		article_state VARCHAR(20) NOT NULL DEFAULT '',
		stage VARCHAR(50) NOT NULL DEFAULT '',
		article_status VARCHAR(50) NOT NULL DEFAULT '',
		prqc_path VARCHAR(500) NOT NULL DEFAULT '',
		saved_file VARCHAR(500) NOT NULL DEFAULT '',
		saved TINYINT NOT NULL DEFAULT 0,
		message TEXT,
		work_link VARCHAR(500) NOT NULL DEFAULT '',
		source_link VARCHAR(500) NOT NULL DEFAULT '',
		KEY idx_logged (logged_at),
		KEY idx_pub (pub_id)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4");
}

function cmp_log_filing($db, $row)
{
	cmp_ensure_log_table($db);
	$cols = array('user_id', 'job_id', 'file1_name', 'file2_name', 'pub_id', 'found_in', 'article_state', 'stage', 'article_status', 'prqc_path', 'saved_file', 'message', 'work_link', 'source_link');
	$sql = "INSERT INTO compare_pdf_filing_log (logged_at, file1_size, file2_size, saved";
	$val = " VALUES (NOW(), " . (int)($row['file1_size'] ?? 0) . ", " . (int)($row['file2_size'] ?? 0) . ", " . (empty($row['saved']) ? 0 : 1);
	foreach ($cols as $c) {
		$sql .= ", `$c`";
		$val .= ", '" . $db->real_escape_string(substr((string)($row[$c] ?? ''), 0, $c === 'message' ? 2000 : 490)) . "'";
	}
	$db->query($sql . ")" . $val . ")");
}

function cmp_article_lookup($db, $name)
{
	$out = array(
		'input' => $name, 'pub_id' => '', 'found_in' => '', 'stage' => '', 'status' => '', 'recv_dt' => '',
		'state' => 'Not found', 'active' => false,
		'prqc_path' => '', 'folder_exists' => false, 'writable' => false, 'can_save' => false,
		'work_link' => '', 'source_link' => '',
		'notes' => array(), 'suggestions' => array()
	);

	$cand = trim($name);
	$tries = array($cand);
	$stripped = trim(preg_replace('/\s*\(\d+\)$/', '', $cand));          // "name (1)" from a duplicate download
	if ($stripped !== '' && $stripped !== $cand) {
		$tries[] = $stripped;
	}

	/* last resort: drop a trailing "-old" / "_rev" / " final" style suffix (up to two) - exact names are always tried first */
	$trim = $stripped;
	for ($i = 0; $i < 2; $i++) {
		$next = preg_replace('/[-_ ][^-_ ]+$/', '', $trim);
		if ($next === $trim || strlen($next) < 4) { break; }
		$tries[] = $trim = $next;
	}

	$row = null;
	foreach ($tries as $t) {
		if ($t === '') { continue; }
		$e = $db->real_escape_string($t);
		foreach (array('Live' => 'inw_inward_dtl', 'Archive' => 'workflow_archive.inw_inward_dtl') as $src => $tbl) {
			try {
				$r = $db->query("SELECT pub_id, stage, status, work_dir, volume_folder, recv_dt FROM $tbl WHERE pub_id = '$e' LIMIT 1");
			} catch (Throwable $x) {
				$r = false;
			}
			if ($r && $r->num_rows > 0) {
				$row = $r->fetch_assoc();
				$out['found_in'] = $src;
				if ($t !== $cand && $t !== $stripped) {
					$out['notes'][] = 'The file name "' . $cand . '" was matched to article ' . $t . ' by ignoring the extra text at the end.';
				}
				break 2;
			}
		}
	}

	/* not found: suggest close Pub IDs (the name with a suffix cut off, or names that start with it) */
	if (!$row) {
		$sug = array();
		$parts = explode('_', $cand);
		for ($i = count($parts) - 1; $i >= 2; $i--) {
			$p = $db->real_escape_string(implode('_', array_slice($parts, 0, $i)));
			$r = $db->query("SELECT pub_id FROM inw_inward_dtl WHERE pub_id = '$p' LIMIT 1");
			if ($r && $r->num_rows > 0) { $sug[] = $r->fetch_assoc()['pub_id']; }
		}
		if ($cand !== '' && strlen($cand) >= 4) {
			$like = $db->real_escape_string(str_replace(array('\\', '%', '_'), array('\\\\', '\\%', '\\_'), $cand));
			$r = $db->query("SELECT pub_id FROM inw_inward_dtl WHERE pub_id LIKE '$like%' ORDER BY pub_id LIMIT 3");
			while ($r && ($x = $r->fetch_assoc())) { $sug[] = $x['pub_id']; }
		}
		$out['suggestions'] = array_values(array_unique($sug));
		$out['notes'][] = 'No article with the Pub ID "' . $cand . '" exists in the workflow (live or archive). Name the PDF exactly as the article\'s Pub ID, e.g. TSP_CMC_88018.pdf.';
		return $out;
	}

	$out['pub_id'] = $row['pub_id'];
	$out['stage'] = trim((string)$row['stage']);
	$out['status'] = trim((string)$row['status']);
	$out['recv_dt'] = (string)$row['recv_dt'];

	$st = strtolower($out['status']);
	if ($out['found_in'] === 'Archive') {
		$out['state'] = 'Archived';
	} elseif ($st === 'delivery' || $st === 'delivery-elx') {
		$out['state'] = 'Delivered';
	} elseif ($st === 'with drawn') {
		$out['state'] = 'Withdrawn';
	} elseif ($st === 'hold') {
		$out['state'] = 'On hold';
		$out['active'] = true;
	} else {
		$out['state'] = 'In progress';
		$out['active'] = true;
	}

	/* where the report would be filed: {work_dir}{volume_folder}/{pub_id}/{stage}/PRQC/ - the article's CURRENT stage */
	if ($row['work_dir'] !== '' && $row['volume_folder'] !== '' && $out['stage'] !== '') {
		$path = $row['work_dir'] . $row['volume_folder'] . '/' . $row['pub_id'] . '/' . $out['stage'] . '/PRQC/';
		$out['prqc_path'] = $path;
		$out['work_link'] = cmp_explorer_link($path);
		$out['source_link'] = cmp_source_link($row['work_dir'], $row['volume_folder'], $row['pub_id'], $out['stage']);
		$out['folder_exists'] = is_dir($path);
		$probe = $path;
		while ($probe !== '' && !is_dir($probe)) {
			$next = dirname(rtrim($probe, '/\\'));
			if ($next === $probe || $next === '.') { $probe = ''; break; }
			$probe = $next;
		}
		$out['writable'] = $probe !== '' && is_writable($probe);
		if ($probe === '') {
			$out['notes'][] = 'The server cannot reach ' . $row['work_dir'] . ' (drive or share not available to the web server).';
		} elseif (!$out['writable']) {
			$out['notes'][] = 'The server can see the folder but has no write permission on ' . $probe . '.';
		}
	} else {
		$out['notes'][] = 'The article has no work folder / volume / stage saved, so its PRQC folder cannot be worked out.';
	}

	if ($out['found_in'] === 'Archive') {
		$out['notes'][] = 'The article is archived, so the report is not filed into a PRQC folder. Download it and attach it manually if needed.';
	} elseif ($out['state'] === 'Delivered') {
		$out['notes'][] = 'The article is already delivered (status ' . $out['status'] . '). It will still be filed under its last stage ' . $out['stage'] . '.';
	} elseif ($out['state'] === 'Withdrawn') {
		$out['notes'][] = 'The article is marked With Drawn, so the report is not filed into a PRQC folder.';
	}
	if ($out['found_in'] === 'Live' && $out['stage'] !== '') {
		$out['notes'][] = 'The report is filed under the article\'s current stage (' . $out['stage'] . '), which may differ from the stage the compared files belong to.';
	}

	$out['can_save'] = $out['found_in'] === 'Live' && $out['state'] !== 'Withdrawn' && $out['prqc_path'] !== '' && $out['writable'];
	return $out;
}
