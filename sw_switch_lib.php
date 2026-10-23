<?php
/* Start Work v2 -> old Start Work switch: settings + log helpers.
 * Used by start_work_v2.php (the "Use old version" button) and sw_switch_settings.php (the admin screen).
 *
 * Recipients live in the existing wf_settings table (columns sw_switch_to / sw_switch_cc / sw_switch_bcc, comma separated).
 * wf_settings has a single row, so adding the columns is instant. They are added automatically the first time they are needed
 * (sw_switch_ensure); the same SQL is below if you prefer to run it yourself:
 *
 *   ALTER TABLE wf_settings ADD COLUMN sw_switch_to TEXT NULL, ADD COLUMN sw_switch_cc TEXT NULL, ADD COLUMN sw_switch_bcc TEXT NULL;
 *
 * Every switch is also written to sw_switch_log (created on first use).
 */

function sw_switch_ensure($db)
{
	static $done = false;
	if ($done) { return; }
	$done = true;
	try {
		$r = $db->query("SHOW COLUMNS FROM wf_settings LIKE 'sw_switch_to'");
		if ($r && $r->num_rows == 0) {
			$db->query("ALTER TABLE wf_settings ADD COLUMN sw_switch_to TEXT NULL, ADD COLUMN sw_switch_cc TEXT NULL, ADD COLUMN sw_switch_bcc TEXT NULL");
		}
		$r = $db->query("SHOW COLUMNS FROM wf_settings LIKE 'sw_book_to'");   /* booking (Revise / Final) alert recipients */
		if ($r && $r->num_rows == 0) {
			$db->query("ALTER TABLE wf_settings ADD COLUMN sw_book_to TEXT NULL, ADD COLUMN sw_book_cc TEXT NULL, ADD COLUMN sw_book_bcc TEXT NULL");
		}
		$r = $db->query("SHOW COLUMNS FROM wf_settings LIKE 'sw_disp_to'");   /* Project Dispatch report recipients */
		if ($r && $r->num_rows == 0) {
			$db->query("ALTER TABLE wf_settings ADD COLUMN sw_disp_to TEXT NULL, ADD COLUMN sw_disp_cc TEXT NULL, ADD COLUMN sw_disp_bcc TEXT NULL");
		}
		$r = $db->query("SHOW COLUMNS FROM wf_settings LIKE 'sw_fm_to'");   /* "file movement not configured" reports from Start Work */
		if ($r && $r->num_rows == 0) {
			$db->query("ALTER TABLE wf_settings ADD COLUMN sw_fm_to TEXT NULL, ADD COLUMN sw_fm_cc TEXT NULL, ADD COLUMN sw_fm_bcc TEXT NULL");
		}
		$db->query("CREATE TABLE IF NOT EXISTS sw_book_log (
			id INT(11) NOT NULL AUTO_INCREMENT,
			article_id INT(11) NOT NULL DEFAULT 0,
			pub_id VARCHAR(150) NOT NULL DEFAULT '',
			kind VARCHAR(10) NOT NULL DEFAULT '',
			booked_stage VARCHAR(30) NOT NULL DEFAULT '',
			prev_stage VARCHAR(30) NOT NULL DEFAULT '',
			workflow VARCHAR(150) NOT NULL DEFAULT '',
			first_dept VARCHAR(100) NOT NULL DEFAULT '',
			user_id VARCHAR(30) NOT NULL DEFAULT '',
			user_name VARCHAR(150) NOT NULL DEFAULT '',
			mail_status VARCHAR(255) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY ix_created (created_at)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8");
		$db->query("CREATE TABLE IF NOT EXISTS sw_switch_log (
			id INT(11) NOT NULL AUTO_INCREMENT,
			article_id INT(11) NOT NULL DEFAULT 0,
			pub_id VARCHAR(150) NOT NULL DEFAULT '',
			stage VARCHAR(30) NOT NULL DEFAULT '',
			dept VARCHAR(100) NOT NULL DEFAULT '',
			user_id VARCHAR(30) NOT NULL DEFAULT '',
			user_name VARCHAR(150) NOT NULL DEFAULT '',
			reason TEXT,
			mail_status VARCHAR(255) NOT NULL DEFAULT '',
			files TEXT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY ix_created (created_at)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8");
		$r = $db->query("SHOW COLUMNS FROM sw_switch_log LIKE 'files'");   /* tables created before attachments existed */
		if ($r && $r->num_rows == 0) { $db->query("ALTER TABLE sw_switch_log ADD COLUMN files TEXT NULL"); }
	} catch (Exception $e) { /* the caller copes with missing columns (empty recipients) */ }
}

/* array('to' => 'a@x.com, b@x.com', 'cc' => '', 'bcc' => '') - empty strings when nothing is set */
function sw_switch_get($db)
{
	$out = array('to' => '', 'cc' => '', 'bcc' => '');
	$r = @$db->query("SELECT sw_switch_to, sw_switch_cc, sw_switch_bcc FROM wf_settings ORDER BY set_id ASC LIMIT 1");
	if ($r && ($row = $r->fetch_assoc())) {
		$out['to']  = trim((string)$row['sw_switch_to']);
		$out['cc']  = trim((string)$row['sw_switch_cc']);
		$out['bcc'] = trim((string)$row['sw_switch_bcc']);
	}
	return $out;
}

/* "a@x.com; b@x.com\n c@x.com" -> array('valid' => [...unique lower-case], 'bad' => [...]) */
function sw_switch_parse_list($text)
{
	$valid = array(); $bad = array();
	foreach (preg_split('/[\s,;]+/', (string)$text, -1, PREG_SPLIT_NO_EMPTY) as $e) {
		$e = trim($e);
		if (filter_var($e, FILTER_VALIDATE_EMAIL)) { $valid[strtolower($e)] = strtolower($e); } else { $bad[] = $e; }
	}
	return array('valid' => array_values($valid), 'bad' => $bad);
}

/* kind 'switch' = "Use old version" alerts, 'book' = Revise / Final booking alerts */
function sw_mail_cols($kind) { return $kind === 'filemove' ? array('sw_fm_to', 'sw_fm_cc', 'sw_fm_bcc') : ($kind === 'book' ? array('sw_book_to', 'sw_book_cc', 'sw_book_bcc') : ($kind === 'dispatch' ? array('sw_disp_to', 'sw_disp_cc', 'sw_disp_bcc') : array('sw_switch_to', 'sw_switch_cc', 'sw_switch_bcc'))); }
function sw_mail_get($db, $kind)
{
	$c = sw_mail_cols($kind);
	$out = array('to' => '', 'cc' => '', 'bcc' => '');
	$r = @$db->query("SELECT ".$c[0].", ".$c[1].", ".$c[2]." FROM wf_settings ORDER BY set_id ASC LIMIT 1");
	if ($r && ($row = $r->fetch_assoc())) { $out['to'] = trim((string)$row[$c[0]]); $out['cc'] = trim((string)$row[$c[1]]); $out['bcc'] = trim((string)$row[$c[2]]); }
	return $out;
}
function sw_mail_save($db, $kind, $to, $cc, $bcc)
{
	sw_switch_ensure($db);
	$c = sw_mail_cols($kind);
	$to = $db->real_escape_string($to); $cc = $db->real_escape_string($cc); $bcc = $db->real_escape_string($bcc);
	return (bool)$db->query("UPDATE wf_settings SET ".$c[0]." = '".$to."', ".$c[1]." = '".$cc."', ".$c[2]." = '".$bcc."' ORDER BY set_id ASC LIMIT 1");
}

function sw_switch_save($db, $to, $cc, $bcc)
{
	sw_switch_ensure($db);
	$to = $db->real_escape_string($to); $cc = $db->real_escape_string($cc); $bcc = $db->real_escape_string($bcc);
	return (bool)$db->query("UPDATE wf_settings SET sw_switch_to = '".$to."', sw_switch_cc = '".$cc."', sw_switch_bcc = '".$bcc."' ORDER BY set_id ASC LIMIT 1");
}

/* ---- attachments (screenshots etc.) sent along with the reason ---- */
define('SW_SWITCH_MAX_FILES', 5);
define('SW_SWITCH_MAX_BYTES', 5 * 1024 * 1024);   /* per file */
function sw_switch_allowed_ext() { return array('png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'log', 'zip'); }
function sw_switch_dir() { return __DIR__.'/sw_switch_files'; }
function sw_switch_ini_bytes($v)
{
	$v = trim((string)$v); if ($v === '') { return 0; }
	$n = (float)$v; $u = strtolower(substr($v, -1));
	if ($u === 'g') { $n *= 1073741824; } elseif ($u === 'm') { $n *= 1048576; } elseif ($u === 'k') { $n *= 1024; }
	return (int)$n;
}
/* limits the browser must respect: array('files' => n, 'each' => bytes, 'total' => bytes) */
function sw_switch_limits()
{
	$up = sw_switch_ini_bytes(ini_get('upload_max_filesize')); $post = sw_switch_ini_bytes(ini_get('post_max_size'));
	$each = SW_SWITCH_MAX_BYTES; if ($up > 0 && $up < $each) { $each = $up; }
	$total = $each * SW_SWITCH_MAX_FILES; if ($post > 0 && $post - 262144 < $total) { $total = max(0, $post - 262144); }
	return array('files' => SW_SWITCH_MAX_FILES, 'each' => $each, 'total' => $total);
}
/* $_FILES['att'] -> list of array(name, tmp, size, ext); or array('error' => message) */
function sw_switch_collect_files()
{
	$out = array();
	if (empty($_FILES['att']) || !isset($_FILES['att']['name'])) { return $out; }
	$f = $_FILES['att']; $names = (array)$f['name'];
	$lim = sw_switch_limits();
	if (count($names) > SW_SWITCH_MAX_FILES) { return array('error' => 'Please attach at most '.SW_SWITCH_MAX_FILES.' files.'); }
	foreach ($names as $i => $name) {
		$err = is_array($f['error']) ? $f['error'][$i] : $f['error'];
		if ($err === UPLOAD_ERR_NO_FILE) { continue; }
		$name = (string)$name;
		if ($err !== UPLOAD_ERR_OK) { return array('error' => '"'.$name.'" could not be uploaded'.(($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) ? ' (too large for the server).' : '.')); }
		$size = (int)$f['size'][$i]; $tmp = $f['tmp_name'][$i];
		$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
		if (!in_array($ext, sw_switch_allowed_ext(), true)) { return array('error' => '"'.$name.'" is not an allowed file type (images, PDF, Word, Excel, CSV, text, log or ZIP).'); }
		if ($size > $lim['each']) { return array('error' => '"'.$name.'" is larger than '.round($lim['each'] / 1048576, 1).' MB.'); }
		if (!is_uploaded_file($tmp)) { return array('error' => '"'.$name.'" was not uploaded properly.'); }
		$out[] = array('name' => $name, 'tmp' => $tmp, 'size' => $size, 'ext' => $ext);
	}
	return $out;
}
/* move the collected files into sw_switch_files/<yyyymm>/<log id>/ ; returns array of array(n => name, s => relative path, z => size) */
function sw_switch_store_files($log_id, $files)
{
	$meta = array(); if (!$files) { return $meta; }
	$base = sw_switch_dir();
	if (!is_dir($base)) {
		@mkdir($base, 0755, true);
		@file_put_contents($base.'/.htaccess', "# attachments are served only through sw_switch_settings.php (Portal Admin)\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nOrder allow,deny\nDeny from all\n</IfModule>\n");
		@file_put_contents($base.'/index.html', '');
	}
	$rel = date('Ym').'/'.(int)$log_id;
	if (!is_dir($base.'/'.$rel)) { @mkdir($base.'/'.$rel, 0755, true); }
	foreach ($files as $i => $f) {
		$safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', pathinfo($f['name'], PATHINFO_FILENAME)); $safe = substr($safe !== '' ? $safe : 'file', 0, 60);
		$stored = $rel.'/'.($i + 1).'_'.$safe.'.'.$f['ext'];
		if (@move_uploaded_file($f['tmp'], $base.'/'.$stored)) { $meta[] = array('n' => $f['name'], 's' => $stored, 'z' => $f['size']); }
	}
	return $meta;
}
