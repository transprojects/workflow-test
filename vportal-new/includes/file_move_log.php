<?php
/* ============================================================================
 * File movement log (Start Work v2)
 *
 * Every time an article's work is completed and its files are copied to the next department,
 * each step is written to `inw_file_move_log`: which file, from which folder to which folder,
 * and whether it was MOVED, FAILED or SKIPPED - with the reason.  "NOTE" rows record things
 * that are not a single file (no movement configured, source folder missing, nothing matched ...).
 *
 * Logging never stops the work: every function swallows its own errors.
 *   fml_ctx($db, array(...))      - start / update the context (article, stage, from / to department ...)
 *   fml_add($kind, $result, ...)  - one log row
 *   fml_summary(...)              - counts for the current batch + destination
 * ========================================================================== */

function fml_ensure($db)
{
	static $done = false;
	if ($done) { return true; }
	$done = true;
	try {
		$db->query("CREATE TABLE IF NOT EXISTS `inw_file_move_log` (
			`id` BIGINT(20) NOT NULL AUTO_INCREMENT,
			`batch_id` VARCHAR(40) NOT NULL,
			`inw_id` INT(11) NOT NULL DEFAULT 0,
			`pub_id` VARCHAR(120) NULL,
			`cust_id` INT(11) NULL,
			`stage` VARCHAR(30) NULL,
			`from_dept` VARCHAR(120) NULL,
			`to_dept` VARCHAR(120) NULL,
			`kind` VARCHAR(10) NOT NULL DEFAULT 'FILE',
			`result` VARCHAR(10) NOT NULL DEFAULT 'MOVED',
			`file_name` VARCHAR(500) NULL,
			`size_bytes` BIGINT(20) NULL,
			`src_path` TEXT NULL,
			`dest_path` TEXT NULL,
			`reason` TEXT NULL,
			`move_types` VARCHAR(400) NULL,
			`user_id` INT(11) NULL,
			`user_name` VARCHAR(120) NULL,
			`created_on` DATETIME NOT NULL,
			PRIMARY KEY (`id`),
			KEY `idx_inw` (`inw_id`, `created_on`),
			KEY `idx_batch` (`batch_id`),
			KEY `idx_pub` (`pub_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
		$c = $db->query("SHOW COLUMNS FROM `inw_file_move_log` LIKE 'move_types'");
		if ($c && $c->num_rows == 0) { $db->query("ALTER TABLE `inw_file_move_log` ADD COLUMN `move_types` VARCHAR(400) NULL AFTER `reason`"); }
	} catch (Throwable $e) { return false; }
	return true;
}

/* set / update the context shared by every log row of this Start Work submit */
function fml_ctx($db, $set = array())
{
	if (!isset($GLOBALS['FML'])) {
		$GLOBALS['FML'] = array('db' => $db, 'batch' => date('YmdHis') . '-' . substr(md5(uniqid('', true)), 0, 6), 'inw_id' => 0, 'pub_id' => '', 'cust_id' => 0,
		                        'stage' => '', 'from_dept' => '', 'to_dept' => '', 'src' => '', 'dest' => '', 'counts' => array(), 'fold_id' => 0, 'types' => '');
	}
	foreach ($set as $k => $v) { $GLOBALS['FML'][$k] = $v; }
	return $GLOBALS['FML'];
}

function fml_add($kind, $result, $file = '', $src = '', $dest = '', $reason = '', $size = null)
{
	try {
		if (empty($GLOBALS['FML']['db'])) { return; }
		$c = $GLOBALS['FML'];
		$db = $c['db'];
		if (!fml_ensure($db)) { return; }
		$e = function ($s) use ($db) { return $db->real_escape_string((string)$s); };
		$uid = isset($_COOKIE['user_id']) ? (int)$_COOKIE['user_id'] : 0;
		$uname = isset($_COOKIE['full_name']) ? $_COOKIE['full_name'] : '';
		$norm = function ($p) { return str_replace('\\', '/', (string)$p); };
		/* the many "nothing selected for this destination" notes of one submit are folded into ONE row listing the departments */
		$fold = ($kind === 'NOTE' && strpos((string)$reason, 'This destination has no file types or folders') === 0);
		/* files that are not copied only because their type is not selected for that destination: ONE row per destination listing the file names */
		if ($kind === 'FILE' && $result === 'SKIPPED' && strpos((string)$reason, 'Not copied: the file type') === 0) {
			$key = 'skip|' . $c['to_dept'];
			if (!empty($GLOBALS['FML']['skiprows'][$key])) {
				$db->query("UPDATE `inw_file_move_log` SET file_name = LEFT(CONCAT(file_name, ', ', '" . $e($file) . "'), 490) WHERE id = " . (int)$GLOBALS['FML']['skiprows'][$key]);
				return;
			}
			$reason = preg_replace('/the file type \S+ is not selected/', 'these file types are not selected', $reason);
			$skipkey = $key;
		}
		if ($fold && !empty($GLOBALS['FML']['fold_id'])) {
			$db->query("UPDATE `inw_file_move_log` SET to_dept = CONCAT(to_dept, ', ', '" . $e($c['to_dept']) . "') WHERE id = " . (int)$GLOBALS['FML']['fold_id']);
			return;
		}
		$db->query("INSERT INTO `inw_file_move_log` (batch_id, inw_id, pub_id, cust_id, stage, from_dept, to_dept, kind, result, file_name, size_bytes, src_path, dest_path, reason, move_types, user_id, user_name, created_on)
			VALUES ('" . $e($c['batch']) . "', " . (int)$c['inw_id'] . ", '" . $e($c['pub_id']) . "', " . (int)$c['cust_id'] . ", '" . $e($c['stage']) . "', '" . $e($c['from_dept']) . "', '" . $e($c['to_dept']) . "',
			'" . $e($kind) . "', '" . $e($result) . "', '" . $e($file) . "', " . ($size === null ? 'NULL' : (int)$size) . ", '" . $e($norm($src)) . "', '" . $e($norm($dest)) . "', '" . $e($reason) . "', '" . $e(isset($c['types']) ? $c['types'] : '') . "', " . $uid . ", '" . $e($uname) . "', NOW())");
		if ($fold) { $GLOBALS['FML']['fold_id'] = (int)$db->insert_id; }
		if (isset($skipkey)) { $GLOBALS['FML']['skiprows'][$skipkey] = (int)$db->insert_id; }
		$k = $c['to_dept'] . '|' . $result;
		$GLOBALS['FML']['counts'][$k] = (isset($GLOBALS['FML']['counts'][$k]) ? $GLOBALS['FML']['counts'][$k] : 0) + ($kind === 'NOTE' ? 0 : 1);
	} catch (Throwable $ex) { /* logging must never break the move */ }
}

/* how many rows with this result were written for the current destination department */
function fml_count($result)
{
	$c = isset($GLOBALS['FML']) ? $GLOBALS['FML'] : null;
	if (!$c) { return 0; }
	$k = $c['to_dept'] . '|' . $result;
	return isset($c['counts'][$k]) ? (int)$c['counts'][$k] : 0;
}

/* a readable reason for a failed copy / mkdir, taken from PHP's own last error */
function fml_why($default = 'the file could not be written - it may be open in another program or locked')
{
	$w = error_get_last();
	if ($w && isset($w['message'])) {
		$m = preg_replace('/^[a-z_]+\(.*?\): /i', '', $w['message']);
		if ($m !== '') { return $m; }
	}
	return $default;
}

/* how many times rcopy() actually ran (a destination can be skipped by the page's own customer rules before rcopy is called) */
function fml_rc_hit() { $GLOBALS['FML_RC'] = (isset($GLOBALS['FML_RC']) ? $GLOBALS['FML_RC'] : 0) + 1; }
function fml_rc_count() { return isset($GLOBALS['FML_RC']) ? (int)$GLOBALS['FML_RC'] : 0; }

/* ".doc .docx .html" (+ "folders: Eqns") from the movement's "doc|docx|html" settings */
function fml_types($files, $folders = '')
{
	$out = array();
	foreach (explode('|', (string)$files) as $x) { $x = trim($x, " .\t"); if ($x !== '') { $out[] = '.' . strtolower($x); } }
	$s = implode(' ', $out);
	$f = array_filter(array_map('trim', explode('|', (string)$folders)), 'strlen');
	if ($f) { $s .= ($s !== '' ? '  ' : '') . 'folders: ' . implode(', ', $f); }
	return $s;
}
