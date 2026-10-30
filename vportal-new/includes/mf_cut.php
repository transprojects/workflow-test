<?php
/* "Cut" option of the Move Files configuration (inw_move_files_dept_wise.cut_move): when ticked for a movement (e.g. CE -> PAG), the files and
   folders that movement transfers are MOVED - each source file is deleted once its copy in the destination is verified - instead of leaving a copy. */

function mf_cut_ensure($db)
{
	static $done = false;
	if ($done) { return; }
	$done = true;
	$t = '`inw_move_files_dept_wise`';
	$r = $db->query("SHOW COLUMNS FROM $t LIKE 'cut_move'");
	if (!$r || $r->num_rows > 0) { return; }
	foreach (array('cut_files', 'cut_zip') as $old) {   // earlier test versions of this option
		$o = $db->query("SHOW COLUMNS FROM $t LIKE '$old'");
		if ($o && $o->num_rows > 0) {
			$db->query("ALTER TABLE $t CHANGE `$old` `cut_move` TINYINT(1) NOT NULL DEFAULT 0");
			$db->query("UPDATE $t SET `cut_move` = IF(`cut_move` IN (0), 0, 1)");
			return;
		}
	}
	$db->query("ALTER TABLE $t ADD COLUMN `cut_move` TINYINT(1) NOT NULL DEFAULT 0");
}

/* Start Work switches this on for the movement row it is processing, and off again when the row is done */
function mf_cut_set($on = null)
{
	static $v = false;
	if ($on !== null) { $v = (bool)$on; }
	return $v;
}

/* called after a file was copied: when cutting, delete the source once the destination copy is the same size */
function mf_cut_source($sfn, $dfn)
{
	if (!mf_cut_set()) { return; }
	clearstatcache(true, $sfn); clearstatcache(true, $dfn);
	if (!is_file($sfn) || !is_file($dfn)) { return; }
	$rs = @realpath($sfn); $rd = @realpath($dfn);
	if ($rs !== false && $rd !== false && strcasecmp(str_replace(chr(92), '/', $rs), str_replace(chr(92), '/', $rd)) === 0) { return; }
	$name = basename($sfn);
	if (filesize($sfn) !== filesize($dfn)) { fml_add('FILE', 'FAILED', $name, $sfn, $dfn, 'Cut: the copy in the destination is not the same size, so the source file was kept.', @filesize($sfn)); return; }
	if (@unlink($sfn)) { fml_add('FILE', 'MOVED', $name, $sfn, $dfn, 'Cut: removed from the source folder.', @filesize($dfn)); }
	else { fml_add('FILE', 'FAILED', $name, $sfn, $dfn, 'Copied, but the source file could not be deleted (it may be open in another program).', @filesize($dfn)); }
}
