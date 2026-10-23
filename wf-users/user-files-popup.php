<?php
/* ============================================================================
 * Today's file list of one employee - the pop-up content of wf-users/user.php.
 *
 *   project_user_time.php?id=<user>    -> every file the employee touched today
 *   project_user_ctime.php?id=<user>   -> only the files completed today
 * (both are thin wrappers that set $mode and include this file)
 *
 * One query for the whole list (it used to run four per file) and no dependence on the
 * old Ace template, sidebar or Bootstrap - the page is a small self-contained card list.
 * ========================================================================== */
include_once 'dbc.php';

if (!isset($mode) || $mode !== 'completed') { $mode = 'worked'; }
$uid      = (int)(isset($_REQUEST['id']) ? $_REQUEST['id'] : 0);
$today    = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$now      = time();
function uf_e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function uf_dur($s) { $s = max(0, (int)$s); $h = floor($s / 3600); $m = floor(($s % 3600) / 60); return $h > 0 ? sprintf('%dh %02dm', $h, $m) : sprintf('%dm %02ds', $m, $s % 60); }

$emp = null;
$r = $db->query("SELECT u.full_name, u.user_email, d.dept_name FROM users u LEFT JOIN adm_dept_master d ON d.dept_code = u.department_id WHERE u.id = ".$uid." LIMIT 1");
if ($r) { $emp = $r->fetch_assoc(); }

$files = array();
$q = $db->query("SELECT t.id, t.project_id, t.stage, t.current_status, t.start_time, t.end_time,
                        CASE WHEN t.pages_completed IS NOT NULL AND t.pages_completed != 0 THEN t.pages_completed
                             WHEN wd.page_count IS NOT NULL AND wd.page_count != 0 THEN wd.page_count
                             ELSE wd.manuscript_count END AS pg,
                        wd.pub_id, c.cust_name, j.j_code, dm.dept_name
                 FROM inw_transactions t
                 JOIN inw_inward_dtl wd ON wd.id = t.project_id
                 LEFT JOIN adm_customer_master c ON c.id = wd.cust_id
                 LEFT JOIN adm_journals j ON j.j_id = wd.j_id
                 LEFT JOIN adm_dept_master dm ON dm.dept_code = t.dept
                 WHERE t.process_user = '".$uid."' AND t.created_dt >= '".$today."' AND t.created_dt < '".$tomorrow."'
                 ORDER BY t.id ASC");
while ($q && ($x = $q->fetch_assoc())) {
	$p = (int)$x['project_id'];
	if (!isset($files[$p])) {
		$files[$p] = array('pub' => $x['pub_id'], 'cust' => $x['cust_name'], 'journal' => $x['j_code'], 'stages' => array(), 'dept' => '', 'status' => '',
		                   'first' => 0, 'last_end' => 0, 'secs' => 0, 'done' => false, 'pages' => 0, 'n' => 0, 'running' => false);
	}
	$f = &$files[$p];
	$st = strtotime($x['start_time']); $en = $x['end_time'] ? strtotime($x['end_time']) : 0;
	$f['n']++;
	$f['stages'][$x['stage']] = true;
	$f['status'] = $x['current_status']; $f['dept'] = $x['dept_name'];
	if ($st && (!$f['first'] || $st < $f['first'])) { $f['first'] = $st; }
	if ($st && $en && $en >= $st) { $f['secs'] += $en - $st; $f['last_end'] = max($f['last_end'], $en); }
	elseif ($st && !$en && $x['current_status'] === 'Inprogress') { $f['running'] = true; $f['secs'] += min(16 * 3600, max(0, $now - $st)); }
	if ($x['current_status'] === 'Completed' && !$f['done']) { $f['done'] = true; $f['pages'] = (float)$x['pg']; }
	unset($f);
}
if ($mode === 'completed') { $files = array_filter($files, function ($f) { return $f['done']; }); }

$tot_secs = 0; $tot_pages = 0; $n_done = 0;
foreach ($files as $f) { $tot_secs += $f['secs']; $tot_pages += $f['pages']; $n_done += $f['done'] ? 1 : 0; }
$title = $mode === 'completed' ? 'Completed files today' : 'Files worked today';
$name  = $emp ? $emp['full_name'] : 'Unknown employee';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo uf_e($title.' - '.$name); ?></title>
<style>
:root{--navy:#14304a;--accent:#e8742a;--blue:#1b6ca8;--ink:#22303f;--mute:#6b7785;--line:#e4e9ef;--ok:#1e9e61;--warn:#d98a06;--info:#2a7fc1;}
*,*:before,*:after{box-sizing:border-box;}
html,body{margin:0;}
body{font-family:"Open Sans","Segoe UI",system-ui,-apple-system,Roboto,Arial,sans-serif;font-size:13.5px;color:var(--ink);background:#f3f6fa;padding:16px;}
@keyframes up{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:none;}}
@keyframes pulse{0%{box-shadow:0 0 0 0 rgba(42,127,193,.5);}100%{box-shadow:0 0 0 8px rgba(42,127,193,0);}}
.top{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;margin-bottom:14px;border-radius:14px;color:#fff;background:linear-gradient(120deg,#14304a,#1f4b73 60%,#1b6ca8);box-shadow:0 6px 16px rgba(20,48,74,.18);animation:up .35s ease both;}
.who{display:flex;align-items:center;gap:12px;min-width:0;}
.av{flex:0 0 auto;width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);font-weight:700;}
.who b{display:block;font-size:16px;} .who small{display:block;opacity:.8;font-size:12px;}
.sums{display:flex;flex-wrap:wrap;gap:8px;}
.sums div{min-width:78px;padding:6px 12px;border-radius:10px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.24);text-align:center;}
.sums span{display:block;font-size:10px;letter-spacing:.5px;text-transform:uppercase;opacity:.8;} .sums b{font-size:17px;font-variant-numeric:tabular-nums;}
.tools{display:flex;align-items:center;gap:10px;margin-bottom:10px;}
.tools input{flex:1;height:38px;padding:0 12px 0 34px;border:1px solid #d5dde6;border-radius:10px;outline:0;font:inherit;background:#fff url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' stroke='%239aa6b2' stroke-width='2'><circle cx='7' cy='7' r='5'/><path d='M11 11l4 4'/></svg>") no-repeat 11px center;}
.tools input:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(27,108,168,.15);}
.tools small{color:var(--mute);white-space:nowrap;}
.list{display:flex;flex-direction:column;gap:10px;}
.item{display:grid;grid-template-columns:34px minmax(0,1.6fr) minmax(0,1fr) minmax(0,1fr) auto;gap:10px 16px;align-items:center;padding:12px 14px;background:#fff;border:1px solid var(--line);border-left:4px solid var(--c,#8795a5);border-radius:12px;box-shadow:0 2px 8px rgba(20,48,74,.05);animation:up .35s ease both;transition:transform .2s,box-shadow .2s;}
.item:hover{transform:translateY(-2px);box-shadow:0 10px 22px rgba(20,48,74,.1);}
.item.done{--c:#1e9e61;} .item.run{--c:#2a7fc1;} .item.other{--c:#d98a06;}
.no{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#eef3f8;color:var(--navy);font-weight:700;font-size:12px;}
.pub b{display:block;font-size:14.5px;color:var(--navy);word-break:break-all;} .pub small{display:block;color:var(--mute);font-size:12px;margin-top:2px;}
.tag{display:inline-block;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;background:#eaf2fa;color:var(--blue);margin:0 4px 3px 0;}
.tag.dept{background:#fdeee3;color:#c4590f;}
.col span{display:block;color:var(--mute);font-size:10.5px;text-transform:uppercase;letter-spacing:.4px;font-weight:700;} .col b{font-variant-numeric:tabular-nums;color:var(--ink);}
.pill{display:inline-flex;align-items:center;gap:7px;padding:5px 11px;border-radius:20px;font-weight:700;font-size:12px;white-space:nowrap;background:color-mix(in srgb,var(--c,#8795a5) 14%,#fff);color:var(--c,#8795a5);}
.pill:before{content:"";width:8px;height:8px;border-radius:50%;background:var(--c,#8795a5);}
.run .pill:before{animation:pulse 1.6s infinite;}
.pg{display:block;margin-top:5px;text-align:right;color:var(--mute);font-size:12px;} .pg b{color:var(--navy);}
.empty{padding:40px 16px;text-align:center;color:var(--mute);background:#fff;border:1px dashed var(--line);border-radius:12px;}
.empty b{display:block;color:var(--navy);font-size:15px;margin-bottom:3px;}
@media (max-width:640px){
  body{padding:10px;} .item{grid-template-columns:30px 1fr;} .item .col,.item .st{grid-column:2;} .pg{text-align:left;}
  .sums div{flex:1 1 70px;}
}
@media (prefers-reduced-motion:reduce){*{animation:none !important;transition:none !important;}}
</style>
</head>
<body>
  <div class="top">
    <div class="who"><span class="av"><?php echo uf_e(strtoupper(substr(trim($name), 0, 1))); ?></span>
      <div><b><?php echo uf_e($name); ?></b><small><?php echo uf_e($emp ? $emp['dept_name'] : ''); ?> &middot; <?php echo date('d M Y'); ?> &middot; <?php echo uf_e($title); ?></small></div></div>
    <div class="sums">
      <div><span>Files</span><b><?php echo count($files); ?></b></div>
<?php if ($mode !== 'completed') { ?>      <div><span>Completed</span><b><?php echo $n_done; ?></b></div>
<?php } ?>      <div><span>Pages</span><b><?php echo (int)round($tot_pages); ?></b></div>
      <div><span>Worked</span><b><?php echo uf_dur($tot_secs); ?></b></div>
    </div>
  </div>

<?php if (!$files) { ?>
  <div class="empty"><b><?php echo $mode === 'completed' ? 'No file completed yet today' : 'No file worked on yet today'; ?></b>Files appear here as soon as <?php echo uf_e($name); ?> starts working.</div>
<?php } else { ?>
  <div class="tools"><input type="search" id="q" placeholder="Search article, customer, journal..."><small id="cnt"><?php echo count($files); ?> files</small></div>
  <div class="list" id="list">
<?php $i = 0; foreach ($files as $f) { $i++;
	$cls = $f['done'] ? 'done' : ($f['running'] ? 'run' : 'other');
	$lbl = $f['done'] ? 'Completed' : ($f['running'] ? 'In progress' : $f['status']);
	$hay = strtolower($f['pub'].' '.$f['cust'].' '.$f['journal'].' '.implode(' ', array_keys($f['stages']))); ?>
    <div class="item <?php echo $cls; ?>" data-h="<?php echo uf_e($hay); ?>" style="animation-delay:<?php echo min($i, 12) * 25; ?>ms">
      <span class="no"><?php echo $i; ?></span>
      <div class="pub"><b><?php echo uf_e($f['pub']); ?></b><small><?php echo uf_e($f['cust']); ?><?php echo $f['journal'] ? ' &middot; '.uf_e($f['journal']) : ''; ?></small>
        <div style="margin-top:5px;"><?php foreach (array_keys($f['stages']) as $s) { echo '<span class="tag">'.uf_e($s).'</span>'; } echo $f['dept'] ? '<span class="tag dept">'.uf_e($f['dept']).'</span>' : ''; ?></div></div>
      <div class="col"><span>Started</span><b><?php echo $f['first'] ? date('H:i', $f['first']) : '-'; ?></b>
        <span style="margin-top:6px;">Last finished</span><b><?php echo $f['running'] ? 'running' : ($f['last_end'] ? date('H:i', $f['last_end']) : '-'); ?></b></div>
      <div class="col"><span>Time spent</span><b><?php echo uf_dur($f['secs']); ?></b>
        <span style="margin-top:6px;">Entries</span><b><?php echo (int)$f['n']; ?></b></div>
      <div class="st"><span class="pill" style="--c:var(--<?php echo $cls === 'done' ? 'ok' : ($cls === 'run' ? 'info' : 'warn'); ?>)"><?php echo uf_e($lbl); ?></span>
        <?php echo $f['done'] ? '<span class="pg"><b>'.(int)round($f['pages']).'</b> pages</span>' : ''; ?></div>
    </div>
<?php } ?>
  </div>
  <div class="empty" id="none" style="display:none;"><b>Nothing matches</b>Try another word.</div>
  <script>
  (function () {
    var q = document.getElementById('q'), items = document.querySelectorAll('.item'), cnt = document.getElementById('cnt'), none = document.getElementById('none');
    q.addEventListener('input', function () {
      var v = this.value.toLowerCase().trim(), n = 0;
      for (var i = 0; i < items.length; i++) { var ok = !v || items[i].getAttribute('data-h').indexOf(v) !== -1; items[i].style.display = ok ? '' : 'none'; if (ok) { n++; } }
      cnt.textContent = n + ' file' + (n === 1 ? '' : 's'); none.style.display = n ? 'none' : 'block';
    });
  })();
  </script>
<?php } ?>
</body>
</html>
