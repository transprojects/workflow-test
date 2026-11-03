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

/* Where the compare jobs (uploads, status, report) live.  Like the other QC tools this is E:/Tool_Reports/ (outside the web root,
   only reachable through PHP); on a machine without that folder it falls back to pdf_compare_jobs/ in the application folder. */
function cmp_jobs_dir()
{
	$base = 'E:/Tool_Reports/';
	if (is_dir($base) && is_writable($base)) {
		$dir = $base . 'pdf_compare/jobs/';
		if (is_dir($dir) || @mkdir($dir, 0777, true)) {
			return $dir;
		}
	}
	$dir = dirname(__DIR__) . '/pdf_compare_jobs/';
	if (!is_dir($dir)) {
		@mkdir($dir, 0777, true);
	}
	return $dir;
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

/* The generated report points at Images/comparison_page_N.png beside it, so a copy taken out of the job folder (the download,
   the copy filed in the PRQC folder) loses its pictures.  Build a self-contained copy: the page images are embedded (shrunk to
   JPEG when GD is available, to keep the file a sensible size) and the original file names replace file1.pdf / file2.pdf. */
function cmp_standalone_report($jobDir, $reportFile, $meta)
{
	$out = $jobDir . 'compare_report_standalone.html';
	if (is_file($out) && filemtime($out) >= filemtime($reportFile)) {
		return $out;
	}

	$html = file_get_contents($reportFile);
	if ($html === false) {
		return $reportFile;
	}

	$memRaw = trim((string)ini_get('memory_limit'));
	$memBytes = (int)$memRaw;
	switch (strtolower(substr($memRaw, -1))) {
		case 'g': $memBytes *= 1024;
		case 'm': $memBytes *= 1024;
		case 'k': $memBytes *= 1024;
	}

	$imgDir = realpath($jobDir . 'Images');

	$html = preg_replace_callback('/src="Images\/([^"]+)"/', function ($m) use ($imgDir, $memBytes) {
		$path = $imgDir ? realpath($imgDir . DIRECTORY_SEPARATOR . basename(rawurldecode($m[1]))) : false;
		if (!$path || strpos($path, $imgDir) !== 0 || !is_file($path)) {
			return $m[0];
		}
		$data = null;
		$mime = 'image/png';
		if (function_exists('imagecreatefrompng') && function_exists('imagejpeg')) {
			$info = @getimagesize($path);
			$need = $info ? $info[0] * $info[1] * 5 : 0;
			if ($info && ($memBytes <= 0 || $need < $memBytes * 0.5)) {
				$im = @imagecreatefrompng($path);
				if ($im) {
					if (imagesx($im) > 2200 && function_exists('imagescale')) {
						$small = imagescale($im, 2200);
						if ($small) {
							imagedestroy($im);
							$im = $small;
						}
					}
					ob_start();
					imagejpeg($im, null, 80);
					$data = ob_get_clean();
					imagedestroy($im);
					$mime = 'image/jpeg';
				}
			}
		}
		if ($data === null || $data === '') {
			$data = file_get_contents($path);
			$mime = 'image/png';
		}
		return 'src="data:' . $mime . ';base64,' . base64_encode($data) . '"';
	}, $html);

	if ($html === null) {
		return $reportFile;
	}

	foreach (array('file1_name' => 'file1.pdf', 'file2_name' => 'file2.pdf') as $key => $placeholder) {
		if (is_array($meta) && !empty($meta[$key])) {
			$html = str_replace('<strong>' . $placeholder . '</strong>', '<strong>' . htmlspecialchars($meta[$key], ENT_QUOTES, 'UTF-8') . '</strong>', $html);
		}
	}

	return @file_put_contents($out, $html) !== false ? $out : $reportFile;
}

/* File the finished report into the article's PRQC folder, ONCE per job, and log the outcome.
   Called as soon as a comparison completes (compare-pdf-status.php), so the report is already in the work folder if the
   pdf_compare_jobs folder is cleaned later.  filing.json remembers the outcome; Download and the viewer reuse it. */
function cmp_file_report($db, $jobDir, $jobId)
{
	$marker = $jobDir . 'filing.json';
	$read = function () use ($marker) {
		if (!is_file($marker)) { return null; }
		$r = json_decode((string)file_get_contents($marker), true);
		return is_array($r) ? $r : null;
	};

	if (($r = $read()) !== null) { return $r; }

	$reportFile = $jobDir . 'compare_report.html';
	if (!is_file($reportFile)) { return null; }

	$lock = @fopen($jobDir . 'filing.lock', 'x');
	if (!$lock) {                                   // another request is filing it right now: wait for its result
		for ($i = 0; $i < 40; $i++) {
			usleep(250000);
			if (($r = $read()) !== null) { return $r; }
		}
		return null;
	}

	$meta = is_file($jobDir . 'meta.json') ? json_decode((string)file_get_contents($jobDir . 'meta.json'), true) : null;
	$names = array();
	foreach (array('file1_name', 'file2_name') as $k) {
		if (is_array($meta) && !empty($meta[$k])) { $names[] = pathinfo($meta[$k], PATHINFO_FILENAME); }
	}

	$standalone = cmp_standalone_report($jobDir, $reportFile, $meta);

	$article = null;
	foreach ($names as $n) {
		$info = cmp_article_lookup($db, $n);
		if ($info['found_in'] === 'Live' && ($article === null || $article['found_in'] !== 'Live')) {
			$article = $info;
		} elseif ($article === null && $info['found_in'] !== '') {
			$article = $info;
		}
	}

	$res = array('saved' => false, 'message' => 'Could not determine the source article automatically.', 'prqc_path' => '',
	             'saved_file' => '', 'work_link' => '', 'source_link' => '', 'pub_id' => '');

	if ($names) {
		if ($article === null) {
			$res['message'] = 'No matching article (pub_id) found for the compared files (' . implode(', ', $names) . '). Name the PDFs exactly as the article Pub ID.';
			$sug = cmp_article_lookup($db, $names[0]);
			if (!empty($sug['suggestions'])) { $res['message'] .= ' Did you mean: ' . implode(', ', $sug['suggestions']) . '?'; }
		} else {
			$res['prqc_path'] = $article['prqc_path'];
			$res['work_link'] = $article['work_link'];
			$res['source_link'] = $article['source_link'];
			$res['pub_id'] = $article['pub_id'];
			$facts = 'Article ' . $article['pub_id'] . ' - ' . $article['state'] . ' (found in ' . $article['found_in'] .
			         ', stage ' . $article['stage'] . ', status ' . $article['status'] . '). ';
			$dest = $article['prqc_path'] . $article['pub_id'] . '_Compare_Report_' . date('Ymd_His') . '.html';

			if ($article['found_in'] === 'Archive') {
				$res['message'] = $facts . 'Archived articles are not filed into a PRQC folder.';
			} elseif ($article['state'] === 'Withdrawn') {
				$res['message'] = $facts . 'Withdrawn articles are not filed into a PRQC folder.';
			} elseif ($article['prqc_path'] === '') {
				$res['message'] = $facts . implode(' ', $article['notes']);
			} elseif (is_dir($article['prqc_path']) || @mkdir($article['prqc_path'], 0777, true)) {
				if (@copy($standalone, $dest)) {
					$res['saved'] = true;
					$res['saved_file'] = $dest;
					$res['message'] = $facts . 'Report saved to PRQC folder: ' . $dest;
				} else {
					$e = error_get_last();
					$res['message'] = $facts . 'Could not write the report to its PRQC folder (check server path/permissions). Server said: ' . ($e ? $e['message'] : 'no details');
				}
			} else {
				$e = error_get_last();
				$res['message'] = $facts . 'Its PRQC folder could not be created: ' . $article['prqc_path'] . ' Server said: ' . ($e ? $e['message'] : 'no details');
			}
		}
	}

	cmp_log_filing($db, array(
		'user_id' => $_COOKIE['user_id'] ?? '', 'job_id' => $jobId,
		'file1_name' => $meta['file1_name'] ?? '', 'file2_name' => $meta['file2_name'] ?? '',
		'file1_size' => $meta['file1_size'] ?? 0, 'file2_size' => $meta['file2_size'] ?? 0,
		'pub_id' => $article['pub_id'] ?? '', 'found_in' => $article['found_in'] ?? '',
		'article_state' => $article['state'] ?? 'Not found', 'stage' => $article['stage'] ?? '', 'article_status' => $article['status'] ?? '',
		'prqc_path' => $res['prqc_path'], 'saved_file' => $res['saved_file'], 'saved' => $res['saved'], 'message' => $res['message'],
		'work_link' => $res['work_link'], 'source_link' => $res['source_link']
	));

	file_put_contents($marker, json_encode($res));

	/* the comparison is done and the report is self-contained: the two uploaded PDFs (up to hundreds of MB) and the loose
	   page images are no longer needed.  Only when the self-contained copy exists, so the report can always be shown. */
	if ($standalone !== $reportFile && is_file($standalone) && filesize($standalone) > 0) {
		@unlink($jobDir . 'file1.pdf');
		@unlink($jobDir . 'file2.pdf');
		foreach ((array)glob($jobDir . 'Images/*') as $img) {
			@unlink($img);
		}
		@rmdir($jobDir . 'Images');
	}
	fclose($lock);
	@unlink($jobDir . 'filing.lock');
	return $res;
}

/* a job folder that was cleaned away: say where the report was filed (from the log) */
function cmp_filed_note($db, $jobId)
{
	cmp_ensure_log_table($db);
	$e = $db->real_escape_string($jobId);
	$r = $db->query("SELECT saved, saved_file, message FROM compare_pdf_filing_log WHERE job_id = '$e' ORDER BY id DESC LIMIT 1");
	$row = $r ? $r->fetch_assoc() : null;
	if (!$row) {
		return 'This comparison was removed from the server and there is no filing record for it. Please run the comparison again.';
	}
	return 'This comparison was already cleaned from the server. ' . ($row['saved'] ? 'The report was filed at: ' . $row['saved_file'] : $row['message']);
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
