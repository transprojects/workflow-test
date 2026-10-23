<?php
/* Edit Move Files: history of the changes made to this record (read from the change_logs table written on every save). */
/* $mfh_all = true (Add page): the latest activity on every Move Files record; otherwise only this record's history */
$mfh_all = !empty($mfh_all);
$mfh_id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
$mfh_rows = array();
if ($mfh_all || $mfh_id > 0) {
	$mfh_rs = @$db->query("SELECT id, action, record_id, changed_by_name, ip_address, changes_count, details, created_at FROM change_logs WHERE module = 'Move Files'" . ($mfh_all ? "" : " AND record_id = '" . $mfh_id . "'") . " ORDER BY id DESC LIMIT " . ($mfh_all ? 30 : 100));
	if ($mfh_rs) { while ($r = $mfh_rs->fetch_assoc()) { $mfh_rows[] = $r; } }
}
/* names for the record each entry belongs to (customer / journal / department) */
$mfh_label = array();
if ($mfh_all && $mfh_rows) {
	$ids = array(); foreach ($mfh_rows as $r) { $ids[(int)$r['record_id']] = 1; }
	$q = @$db->query("SELECT m.mf_id, c.cust_name, j.j_code, d.dept_name FROM inw_move_files m LEFT JOIN adm_customer_master c ON c.id = m.cust_id LEFT JOIN adm_journals j ON j.j_id = m.j_id LEFT JOIN adm_dept_master d ON d.dept_code = m.dept_id WHERE m.mf_id IN (" . implode(',', array_keys($ids)) . ")");
	if ($q) { while ($r = $q->fetch_assoc()) { $mfh_label[(int)$r['mf_id']] = $r; } }
}
$mfh_cust = array(); $mfh_j = array();
$q = @$db->query("SELECT id, cust_name FROM adm_customer_master"); if ($q) { while ($r = $q->fetch_assoc()) { $mfh_cust[(int)$r['id']] = $r['cust_name']; } }
$q = @$db->query("SELECT j_id, j_code FROM adm_journals"); if ($q) { while ($r = $q->fetch_assoc()) { $mfh_j[(int)$r['j_id']] = $r['j_code']; } }
$mfh_dept = array();
$mfh_d = $db->query("SELECT dept_code, dept_name FROM adm_dept_master");
if ($mfh_d) { while ($r = $mfh_d->fetch_assoc()) { $mfh_dept[(int)$r['dept_code']] = $r['dept_name']; } }

/* one log line -> html */
function mfh_line($line, $mfh_dept)
{
	$line = trim($line);
	if ($line === '' || $line === '(no change)' || $line[0] === '[') { return ''; }
	if (preg_match("/^Stage: (.+?) \| Dept: (\d+) \| (Files|Folder)\s*: '(.*)' -> '(.*)'$/s", $line, $m)) {
		$dn = isset($mfh_dept[(int)$m[2]]) ? $mfh_dept[(int)$m[2]] : ('Dept ' . $m[2]);
		return '<div class="mfh-line"><span class="mfh-stage">' . htmlspecialchars($m[1]) . '</span><b>' . htmlspecialchars($dn) . '</b><span class="mfh-field">' . $m[3] . '</span>'
			. '<span class="mfh-old">' . ($m[4] === '' ? '(empty)' : htmlspecialchars($m[4])) . '</span><span class="mfh-arrow">&rarr;</span>'
			. '<span class="mfh-new">' . ($m[5] === '' ? '(empty)' : htmlspecialchars($m[5])) . '</span></div>';
	}
	if (preg_match("/^(.+?) \((\w+)\): '(.*)' -> '(.*)'$/s", $line, $m)) {
		return '<div class="mfh-line"><span class="mfh-stage mfh-head">Header</span><b>' . htmlspecialchars($m[1]) . '</b><span class="mfh-old">' . htmlspecialchars($m[3]) . '</span><span class="mfh-arrow">&rarr;</span><span class="mfh-new">' . htmlspecialchars($m[4]) . '</span></div>';
	}
	return '<div class="mfh-line mfh-raw">' . htmlspecialchars($line) . '</div>';
}
?>
<?php
/* an ADD entry: header ids -> names, and only the files / folders that were actually filled in */
function mfh_added($details, $mfh_dept, $mfh_cust, $mfh_j)
{
	$v = array();
	foreach (preg_split('/\r\n|\r|\n/', (string)$details) as $ln) { if (preg_match("/^\s*(\w+): '(.*)'$/s", $ln, $m)) { $v[$m[1]] = $m[2]; } }
	$out = '';
	$head = array();
	if (isset($v['cust_id'])) { $head[] = isset($mfh_cust[(int)$v['cust_id']]) ? $mfh_cust[(int)$v['cust_id']] : ('Customer ' . $v['cust_id']); }
	if (isset($v['j_id'])) { $head[] = isset($mfh_j[(int)$v['j_id']]) ? $mfh_j[(int)$v['j_id']] : ('Journal ' . $v['j_id']); }
	if (isset($v['dept_id']) && $v['dept_id'] !== '') { $head[] = isset($mfh_dept[(int)$v['dept_id']]) ? $mfh_dept[(int)$v['dept_id']] : ('Dept ' . $v['dept_id']); }
	if ($head) { $out .= '<div class="mfh-line"><span class="mfh-stage mfh-head">New record</span><b>' . htmlspecialchars(implode(' / ', $head)) . '</b></div>'; }
	foreach (array('files' => 'Files', 'folders' => 'Folder') as $k => $label) {
		$a = isset($v[$k]) ? json_decode(stripslashes($v[$k]), true) : null;
		if (!is_array($a)) { $a = isset($v[$k]) ? json_decode($v[$k], true) : null; }
		if (!is_array($a)) { continue; }
		foreach ($a as $stage => $depts) {
			if (!is_array($depts)) { continue; }
			foreach ($depts as $dc => $val) {
				if ($val === '' || $val === null) { continue; }
				$dn = isset($mfh_dept[(int)$dc]) ? $mfh_dept[(int)$dc] : ('Dept ' . $dc);
				$out .= '<div class="mfh-line"><span class="mfh-stage">' . htmlspecialchars($stage) . '</span><b>' . htmlspecialchars($dn) . '</b><span class="mfh-field">' . $label . '</span><span class="mfh-new">' . htmlspecialchars($val) . '</span></div>';
			}
		}
	}
	return $out;
}
?>
<style>
.mfh-card{margin:26px auto 10px;max-width:100%;background:#fff;border:1px solid #e3e8ef;border-radius:14px;box-shadow:0 4px 18px rgba(20,48,74,.06);padding:18px 20px;}
.mfh-title{display:flex;align-items:center;gap:10px;font-size:16px;font-weight:700;color:#14304a;margin:0 0 4px;}
.mfh-title .mfh-n{background:#e8f0fa;color:#1b6ca8;font-size:12px;border-radius:10px;padding:2px 9px;font-weight:700;}
.mfh-sub{font-size:12px;color:#6b7785;margin-bottom:14px;}
.mfh-item{display:grid;grid-template-columns:170px 1fr;gap:6px 18px;padding:12px 0;border-top:1px solid #eef1f5;}
.mfh-when{font-size:13px;color:#14304a;font-weight:600;}
.mfh-rec{font-size:12px;margin-top:2px;}.mfh-rec a{color:#1b6ca8;font-weight:600;text-decoration:none;}.mfh-rec a:hover{text-decoration:underline;}
.mfh-who{font-size:12px;color:#6b7785;margin-top:2px;}
.mfh-badge{display:inline-block;font-size:11px;font-weight:700;border-radius:8px;padding:1px 8px;margin-top:6px;background:#fff1e6;color:#c8601a;}
.mfh-badge.add{background:#e1f6ea;color:#157a4a;}
.mfh-line{display:flex;flex-wrap:wrap;align-items:center;gap:8px;font-size:13px;margin-bottom:6px;}
.mfh-stage{background:#e8f0fa;color:#1b6ca8;border-radius:8px;padding:1px 9px;font-size:12px;font-weight:600;}
.mfh-stage.mfh-head{background:#f1e8fa;color:#6b3aa8;}
.mfh-field{color:#6b7785;font-size:12px;text-transform:uppercase;letter-spacing:.04em;}
.mfh-old{background:#fdecec;color:#b42318;border-radius:6px;padding:1px 8px;text-decoration:line-through;word-break:break-all;}
.mfh-new{background:#e6f6ec;color:#157a4a;border-radius:6px;padding:1px 8px;font-weight:600;word-break:break-all;}
.mfh-arrow{color:#8a97a6;}
.mfh-raw{color:#4a5b70;white-space:pre-wrap;}
.mfh-none{font-size:13px;color:#6b7785;padding:10px 0;}
.mfh-more summary{cursor:pointer;color:#1b6ca8;font-weight:600;font-size:13px;padding:8px 0;}
@media (max-width:767px){.mfh-item{grid-template-columns:1fr;}}
</style>
<div class="mfh-card" id="mf-history">
	<div class="mfh-title"><?php echo $mfh_all ? 'Recent Move Files activity' : 'Change history'; ?> <span class="mfh-n"><?php echo count($mfh_rows); ?></span></div>
	<div class="mfh-sub"><?php echo $mfh_all ? 'The latest additions and changes on all Move Files records, so you can see what is already set up before adding a new one.' : 'Who changed this Move Files record, when, and what changed (old &rarr; new).'; ?></div>
	<?php if (!$mfh_rows): ?>
		<div class="mfh-none"><?php echo $mfh_all ? 'No Move Files activity has been recorded yet.' : 'No changes have been recorded for this record yet.'; ?></div>
	<?php else: foreach ($mfh_rows as $i => $h):
		$lines = '';
		if (strtoupper($h['action']) === 'ADD') { $lines = mfh_added($h['details'], $mfh_dept, $mfh_cust, $mfh_j); }
		else { foreach (preg_split('/\r\n|\r|\n/', (string)$h['details']) as $ln) { $lines .= mfh_line($ln, $mfh_dept); } }
		if ($lines === '') { $lines = '<div class="mfh-none" style="padding:0">' . ((int)$h['changes_count'] === 0 ? 'Saved with no changes.' : 'Details not available.') . '</div>'; }
		if ($i === 10) { echo '<details class="mfh-more"><summary>Show older changes</summary>'; }
	?>
		<div class="mfh-item">
			<div>
				<div class="mfh-when"><?php echo date('d-m-Y H:i', strtotime($h['created_at'])); ?></div>
				<?php if ($mfh_all): $lb = isset($mfh_label[(int)$h['record_id']]) ? $mfh_label[(int)$h['record_id']] : null; ?>
				<div class="mfh-rec"><a href="<?php echo htmlspecialchars((isset($_COOKIE['set_url']) ? $_COOKIE['set_url'] : '') . '/edit-move-files/' . (int)$h['record_id']); ?>"><?php echo $lb ? htmlspecialchars(trim($lb['cust_name'] . ' / ' . $lb['j_code'] . ' / ' . $lb['dept_name'], ' /')) : 'Record #' . (int)$h['record_id']; ?></a></div>
				<?php endif; ?>
				<div class="mfh-who"><?php echo htmlspecialchars($h['changed_by_name'] !== '' ? $h['changed_by_name'] : 'Unknown'); ?></div>
				<span class="mfh-badge <?php echo strtoupper($h['action']) === 'ADD' ? 'add' : ''; ?>"><?php echo htmlspecialchars(strtoupper($h['action'])); ?> &middot; <?php echo (int)$h['changes_count']; ?> change<?php echo (int)$h['changes_count'] === 1 ? '' : 's'; ?></span>
			</div>
			<div><?php echo $lines; ?></div>
		</div>
	<?php endforeach; if (count($mfh_rows) > 10) { echo '</details>'; } endif; ?>
</div>
