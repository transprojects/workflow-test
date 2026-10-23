<?php include "includes/includes.php";

/* ============================================================================
 * User report details - one user, one day.
 *
 * status = A  -> Completed + In Progress + Pending lists
 *          C  -> Completed list
 *          W  -> WIP list
 *          P  -> Pending list
 *
 * Every list is built with a fixed number of queries (one aggregate over
 * inw_transactions, one lookup for the article rows, one for the current owner),
 * instead of several queries per row.  Date filters are range scans on
 * created_dt so an index on it can be used, and all request values are cast or
 * escaped before they reach SQL.
 * ========================================================================== */

function ur_e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function ur_dur($seconds)
{
	$s = max(0, (int)$seconds);
	return sprintf('%02dh %02dm %02ds', floor($s / 3600), floor(($s % 3600) / 60), $s % 60);
}
function ur_dt($v)
{
	return ($v === null || $v === '' || strpos($v, '0000-00-00') === 0) ? '' : date('d-m-Y h:i:s a', strtotime($v));
}
function ur_rows($db, $sql, $key = null)
{
	$out = array();
	$r = $db->query($sql);
	if (!$r) { return $out; }
	while ($row = $r->fetch_assoc()) {
		if ($key === null) { $out[] = $row; } else { $out[$row[$key]] = $row; }
	}
	return $out;
}
function ur_status_class($text)
{
	$t = strtolower($text);
	if (strpos($t, 'complet') !== false) { return 'ok'; }
	if (strpos($t, 'progress') !== false || strpos($t, 'assign') !== false) { return 'info'; }
	if (strpos($t, 'query') !== false || strpos($t, 'hold') !== false) { return 'bad'; }
	if (strpos($t, 'pending') !== false) { return 'warn'; }
	return 'muted';
}
function ur_initials($name)
{
	$i = '';
	foreach (array_slice(preg_split('/\s+/', trim((string)$name)), 0, 2) as $w) { $i .= strtoupper(substr($w, 0, 1)); }
	return $i !== '' ? $i : '?';
}

/* ---- request ---- */
$user_id = (int)(isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : 0);
$status  = isset($_REQUEST['status']) ? $_REQUEST['status'] : '';
$status  = in_array($status, array('A', 'P', 'C', 'W'), true) ? $status : 'A';
$ts      = isset($_REQUEST['sdate']) ? strtotime($_REQUEST['sdate']) : false;
$ts      = $ts ? $ts : time();
$day     = date('Y-m-d', $ts);
$day_end = date('Y-m-d', strtotime('+1 day', $ts));
$dep_id  = $db->real_escape_string(isset($_REQUEST['dep_id']) ? $_REQUEST['dep_id'] : '');
$depcode = $db->real_escape_string(isset($_REQUEST['depcode']) ? $_REQUEST['depcode'] : '');

$user_row  = ur_rows($db, "SELECT full_name FROM users WHERE id = ".$user_id." LIMIT 1");
$user_name = $user_row ? $user_row[0]['full_name'] : '';

$range = "t.process_user = '".$user_id."' AND t.created_dt >= '".$day."' AND t.created_dt < '".$day_end."'";
$secs  = "SUM(IF(t.end_time IS NULL OR t.start_time IS NULL, 0, TIMESTAMPDIFF(SECOND, t.start_time, t.end_time)))";

/* ---------------------------------------------------------------------------
 * Lists. Each returns array of project_id => aggregate row (newest article first).
 * ------------------------------------------------------------------------- */
function ur_list_completed($db)
{
	global $range, $secs, $dep_id;
	return ur_rows($db, "SELECT t.project_id, MIN(t.start_time) AS st, MAX(t.end_time) AS et, ".$secs." AS secs,
	                            0 AS running, MAX(t.id) AS lid, MAX(t.current_status = 'Completed') AS done
	                     FROM inw_transactions t
	                     WHERE ".$range." AND t.dept = '".$dep_id."'
	                     GROUP BY t.project_id HAVING done = 1
	                     ORDER BY t.project_id DESC", 'project_id');
}
function ur_list_progress($db)
{
	global $range, $secs;
	return ur_rows($db, "SELECT t.project_id, MIN(t.start_time) AS st, MAX(t.end_time) AS et, ".$secs." AS secs,
	                            SUM(t.end_time IS NULL) AS running, MAX(t.id) AS lid
	                     FROM inw_transactions t
	                     WHERE ".$range." AND t.current_status = 'Inprogress'
	                     GROUP BY t.project_id
	                     ORDER BY t.project_id DESC", 'project_id');
}
function ur_list_wip($db)
{
	global $range, $dep_id;
	return ur_rows($db, "SELECT t.project_id,
	                            MIN(IF(t.dept = '".$dep_id."', t.start_time, NULL)) AS st,
	                            MAX(IF(t.dept = '".$dep_id."', t.end_time, NULL)) AS et,
	                            SUM(IF(t.dept = '".$dep_id."' AND t.end_time IS NOT NULL AND t.start_time IS NOT NULL,
	                                   TIMESTAMPDIFF(SECOND, t.start_time, t.end_time), 0)) AS secs,
	                            0 AS running, MAX(t.id) AS lid
	                     FROM inw_transactions t
	                     WHERE ".$range."
	                     GROUP BY t.project_id
	                     ORDER BY t.project_id DESC", 'project_id');
}
function ur_list_pending($db)
{
	global $user_id, $day, $day_end, $depcode;
	$rows = ur_rows($db, "SELECT wd.id AS project_id, NULL AS st, NULL AS et, 0 AS secs, 0 AS running, 0 AS lid
	                      FROM inw_inward_dtl wd
	                      WHERE wd.assigned_user_id = ".$user_id."
	                        AND wd.target_due_dt >= '".$day."' AND wd.target_due_dt < '".$day_end."'
	                        AND wd.status = '".$depcode."'
	                      ORDER BY wd.id DESC", 'project_id');
	return $rows;
}

/* Article, customer, journal, last stage and current owner for a set of lists. */
function ur_details($db, $ids)
{
	$info = array('art' => array(), 'last' => array(), 'owner' => array());
	if (!$ids) { return $info; }
	$in = implode(',', array_map('intval', $ids));

	$info['art'] = ur_rows($db, "SELECT wd.id, wd.pub_id, wd.notes, wd.stage, wd.status, c.cust_name, j.j_code
	                             FROM inw_inward_dtl wd
	                             JOIN adm_customer_master c ON c.id = wd.cust_id
	                             JOIN adm_journals j ON j.j_id = wd.j_id
	                             WHERE wd.id IN (".$in.")", 'id');

	/* the person holding the article now: latest non-dept-9 transaction, if it is still open */
	$info['owner'] = ur_rows($db, "SELECT t.project_id, t.stage, t.current_status, u.full_name
	                               FROM inw_transactions t
	                               JOIN (SELECT project_id, MAX(id) AS mid FROM inw_transactions
	                                     WHERE dept <> 9 AND project_id IN (".$in.") GROUP BY project_id) m ON m.mid = t.id
	                               JOIN users u ON u.id = t.process_user
	                               WHERE t.current_status NOT IN ('Completed','Take Over','Query','Hold')", 'project_id');
	return $info;
}
function ur_last_stage($db, $lids)
{
	$lids = array_filter(array_map('intval', $lids));
	if (!$lids) { return array(); }
	$out = array();
	foreach (ur_rows($db, "SELECT id, current_stage FROM inw_transactions WHERE id IN (".implode(',', $lids).")") as $r) {
		$out[$r['id']] = $r['current_stage'];
	}
	return $out;
}

/* ---------------------------------------------------------------------------
 * Rendering
 * ------------------------------------------------------------------------- */
function ur_section($title, $icon, $tone, $list, $info, $stages, $with_time, $empty)
{
	$n = 0; $total = 0; $body = '';
	foreach ($list as $pid => $r) {
		if (!isset($info['art'][$pid])) { continue; }
		$a = $info['art'][$pid];
		$n++; $total += (int)$r['secs'];

		$stage = isset($stages[$r['lid']]) && $stages[$r['lid']] !== '' ? $stages[$r['lid']] : $a['stage'];
		$note  = $a['notes'] !== '' && $a['notes'] !== null ? ' <span class="ur-note" title="'.ur_e($a['notes']).'">'.ur_e(substr($a['notes'], 0, 10)).'</span>' : '';

		$owner = '';
		if (isset($info['owner'][$pid]) && $info['owner'][$pid]['stage'] === $a['stage']) {
			$o = $info['owner'][$pid];
			$owner = '<span class="ur-person"><span class="ur-avatar">'.ur_initials($o['full_name']).'</span>'.ur_e(ucwords($o['full_name'])).'</span> '
			       . '<span class="ur-pill '.ur_status_class($o['current_status']).'">'.ur_e($o['current_status']).'</span>';
		}

		$tr_class = in_array($a['status'], array('Query', 'Hold'), true) ? ' class="ur-hl"' : '';
		$body .= '<tr'.$tr_class.'><td class="c">'.$n.'</td>'
		      .  '<td>'.ur_e($a['cust_name']).'</td>'
		      .  '<td><span class="ur-tag">'.ur_e($a['j_code']).'</span></td>'
		      .  '<td><b>'.ur_e($a['pub_id']).'</b>'.$note.'</td>'
		      .  '<td>'.($stage !== '' ? '<span class="ur-tag alt">'.ur_e($stage).'</span>' : '<span class="ur-na">&ndash;</span>').'</td>';
		if ($with_time) {
			$st = ur_dt($r['st']);
			$et = ur_dt($r['et']);
			if ($et === '' || $r['running'] > 0) { $et = '<span class="ur-pill info">Running</span>'; }
			$body .= '<td class="nw">'.($st !== '' ? $st : '<span class="ur-na">&ndash;</span>').'</td>'
			      .  '<td class="nw">'.$et.'</td>'
			      .  '<td class="nw">'.((int)$r['secs'] > 0 ? '<span class="ur-dur"><i class="fa fa-clock-o"></i> '.ur_dur($r['secs']).'</span>' : '<span class="ur-na">&ndash;</span>').'</td>';
		}
		$body .= '<td><span class="ur-pill '.ur_status_class($a['status']).'">'.ur_e($a['status']).'</span> '.$owner.'</td></tr>';
	}

	$o  = '<section class="ur-panel '.$tone.'"><h3><i class="fa '.$icon.'"></i> '.$title.' <span class="ur-count">'.$n.'</span>';
	if ($with_time && $total > 0) { $o .= '<span class="ur-total"><i class="fa fa-hourglass-half"></i> Total '.ur_dur($total).'</span>'; }
	$o .= '</h3>';
	if ($n === 0) { return $o.'<div class="ur-empty">'.$empty.'</div></section>'; }

	$o .= '<div class="ur-table-wrap"><table class="ur-table"><thead><tr><th class="c">#</th><th>Customer</th><th>Journal</th><th>Article / Book</th><th>Stage</th>'
	   .  ($with_time ? '<th>Start</th><th>End</th><th>Duration</th>' : '')
	   .  '<th>Status</th></tr></thead><tbody>'.$body.'</tbody></table></div></section>';
	return $o;
}

/* ---- build the requested lists ---- */
$sections = array();   // [title, icon, tone, list, with_time, empty message]
if ($status === 'A' || $status === 'C') { $sections[] = array('Completed articles', 'fa-check-circle', 'ok', ur_list_completed($db), true, 'No completed articles'); }
if ($status === 'A')                    { $sections[] = array('In progress articles', 'fa-spinner', 'info', ur_list_progress($db), true, 'No in-progress articles'); }
if ($status === 'W')                    { $sections[] = array('WIP articles', 'fa-cogs', 'wip', ur_list_wip($db), true, 'No WIP articles'); }
if ($status === 'A' || $status === 'P') { $sections[] = array('Pending articles', 'fa-hourglass-half', 'warn', ur_list_pending($db), false, 'No pending articles'); }

$ids = array(); $lids = array();
foreach ($sections as $s) { foreach ($s[3] as $pid => $r) { $ids[$pid] = $pid; $lids[] = $r['lid']; } }
$info   = ur_details($db, $ids);
$stages = ur_last_stage($db, $lids);

$titles = array('A' => 'Assigned articles', 'P' => 'Pending articles', 'C' => 'Completed articles', 'W' => 'WIP articles');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>

    <style>
      .addarticleformouter{background:transparent !important;padding:0 !important;box-shadow:none !important;border:0 !important;}
      .addarticleforminner{background:transparent !important;box-shadow:none !important;border:0 !important;
        width:100% !important;max-width:1360px;margin:0 auto !important;padding:0 18px !important;}
      #main-container{margin-left:0 !important;}
      .ur-root{--navy:#14304a;--navy2:#1f4b73;--accent:#e8742a;--blue:#1b6ca8;--ink:#22303f;--mute:#6b7785;
        --line:#e4e9ef;--ok:#1e9e61;--warn:#d98a06;--bad:#d64545;--info:#2a7fc1;color:var(--ink);font-size:13.5px;}
      .ur-root *{box-sizing:border-box;}
      .ur-root h2,.ur-root h3,.ur-root p{margin:0;}
      @keyframes urUp{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:none;}}

      .ur-banner{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:14px 24px;
        padding:18px 24px;margin:6px 0 16px;border-radius:12px;color:var(--navy);
        background:linear-gradient(120deg,#f4f9fe 0%,#e9f2fb 100%);border:1px solid #d6e4f1;
        box-shadow:0 2px 10px rgba(20,48,74,.06);animation:urUp .45s ease both;}
      .ur-banner small{display:block;font-size:11px;letter-spacing:.8px;text-transform:uppercase;color:var(--mute);}
      .ur-banner .ur-name{font-size:22px;font-weight:700;line-height:1.25;}
      .ur-banner .ur-sub{color:var(--mute);margin-top:2px;}
      .ur-facts{display:flex;flex-wrap:wrap;gap:10px;}
      .ur-fact{min-width:130px;padding:9px 14px;border-radius:8px;background:#fff;border:1px solid #dbe6f1;}
      .ur-fact b{display:block;font-size:14px;font-weight:600;color:var(--navy);}
      .ur-print{border:1px solid var(--line);background:#fff;color:var(--navy);border-radius:8px;padding:8px 14px;
        font-weight:600;cursor:pointer;}
      .ur-print:hover{background:var(--navy);color:#fff;border-color:var(--navy);}

      .ur-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;margin-bottom:14px;}
      .ur-stat{display:flex;align-items:center;gap:14px;padding:14px 16px;background:#fff;border:1px solid var(--line);
        border-radius:12px;box-shadow:0 2px 8px rgba(20,48,74,.04);}
      .ur-stat>i{width:42px;height:42px;line-height:42px;text-align:center;border-radius:10px;font-size:18px;background:#eaf2fa;color:var(--blue);}
      .ur-stat span{display:block;color:var(--mute);font-size:11.5px;text-transform:uppercase;letter-spacing:.5px;}
      .ur-stat b{font-size:17px;color:var(--navy);font-variant-numeric:tabular-nums;}
      .ur-stat.ok>i{background:#e3f6ec;color:var(--ok);} .ur-stat.warn>i{background:#fdf1d9;color:var(--warn);}
      .ur-stat.info>i{background:#e1effa;color:var(--info);} .ur-stat.wip>i{background:#fdeee3;color:var(--accent);}

      .ur-panel{background:#fff;border:1px solid var(--line);border-radius:12px;padding:16px 18px;margin-bottom:14px;
        border-left:5px solid var(--blue);box-shadow:0 2px 8px rgba(20,48,74,.04);animation:urUp .35s ease both;}
      .ur-panel.ok{border-left-color:var(--ok);} .ur-panel.warn{border-left-color:var(--warn);}
      .ur-panel.info{border-left-color:var(--info);} .ur-panel.wip{border-left-color:var(--accent);}
      .ur-panel h3{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700;text-transform:uppercase;
        letter-spacing:.7px;color:var(--navy);padding-bottom:10px;margin-bottom:8px;border-bottom:1px solid var(--line);}
      .ur-panel h3>i{color:var(--accent);}
      .ur-count{display:inline-block;min-width:24px;padding:1px 8px;border-radius:20px;text-align:center;
        background:#eaf2fa;color:var(--blue);font-size:12px;}
      .ur-total{margin-left:auto;color:var(--accent);font-size:14px;letter-spacing:0;text-transform:none;}
      .ur-total i{margin-right:5px;}

      .ur-table-wrap{overflow-x:auto;border:1px solid var(--line);border-radius:10px;}
      .ur-table{width:100%;border-collapse:collapse;min-width:820px;margin:0;border:0;}
      .ur-table th{background:#eef3f8;color:var(--navy);border:0;border-bottom:2px solid #d5e1ed;padding:11px 12px;font-size:12px;
        font-weight:600;text-transform:uppercase;letter-spacing:.5px;text-align:left;}
      .ur-table td{padding:11px 12px;border:0;border-bottom:1px solid #eef1f5;vertical-align:middle;background:#fff;}
      .ur-table tbody tr:nth-child(even) td{background:#f9fbfd;}
      .ur-table tbody tr:hover td{background:#eef5fb;}
      .ur-table tr.ur-hl td{background:#fff9d6 !important;}
      .ur-table .c{text-align:center;width:46px;color:var(--mute);} .ur-table .nw{white-space:nowrap;}
      .ur-tag{display:inline-block;padding:3px 10px;border-radius:6px;background:#eaf2fa;color:var(--blue);font-weight:600;font-size:12px;}
      .ur-tag.alt{background:#fdeee3;color:var(--accent);}
      .ur-note{display:inline-block;margin-left:6px;padding:2px 8px;border-radius:6px;background:#e1effa;color:var(--info);font-size:11px;}
      .ur-dur{font-weight:600;color:var(--navy);font-variant-numeric:tabular-nums;}
      .ur-dur i{color:var(--accent);margin-right:4px;}
      .ur-na{color:#b3bcc7;}
      .ur-person{display:inline-flex;align-items:center;gap:6px;}
      .ur-avatar{display:inline-block;width:22px;height:22px;line-height:22px;border-radius:50%;text-align:center;
        font-size:9.5px;font-weight:700;color:#fff;background:linear-gradient(135deg,var(--blue),var(--navy2));}
      .ur-pill{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:700;}
      .ur-pill.ok{background:#e3f6ec;color:var(--ok);} .ur-pill.warn{background:#fdf1d9;color:var(--warn);}
      .ur-pill.bad{background:#fbe5e5;color:var(--bad);} .ur-pill.info{background:#e1effa;color:var(--info);}
      .ur-pill.muted{background:#eef1f5;color:var(--mute);}
      .ur-empty{padding:26px;text-align:center;color:var(--mute);background:#f9fbfd;border:1px dashed var(--line);border-radius:10px;}

      @media print{ .ur-print{display:none !important;} .ur-panel,.ur-banner{animation:none;} }
    </style>
  </head>

  <body>
    <div class="wholeouter">
    <?php include("includes/top-header.php");?>

      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
<div class="main-container" id="main-container" style="margin-left:-15px;">
  <div class="main-content"><div class="main-content-inner"><div class="page-content">
    <div class="row"><div class="col-xs-12 ur-root">

      <div class="ur-banner">
        <div>
          <small>User report</small>
          <div class="ur-name"><?php echo ur_e($user_name !== '' ? $user_name : 'Unknown user'); ?></div>
          <div class="ur-sub"><?php echo ur_e($titles[$status]); ?></div>
        </div>
        <div class="ur-facts">
          <div class="ur-fact"><small>Date</small><b><?php echo date('d M Y', $ts); ?></b></div>
          <button type="button" class="ur-print" onclick="window.print()"><i class="fa fa-print"></i> Print</button>
        </div>
      </div>

      <div class="ur-stats">
<?php foreach ($sections as $s) {
	$n = 0; $t = 0;
	foreach ($s[3] as $pid => $r) { if (isset($info['art'][$pid])) { $n++; $t += (int)$r['secs']; } }
?>
        <div class="ur-stat <?php echo $s[2]; ?>"><i class="fa <?php echo $s[1]; ?>"></i>
          <div><span><?php echo ur_e($s[0]); ?></span><b><?php echo $n; ?><?php echo ($s[4] && $t > 0) ? ' &middot; '.ur_dur($t) : ''; ?></b></div></div>
<?php } ?>
      </div>

<?php foreach ($sections as $s) { echo ur_section($s[0], $s[1], $s[2], $s[3], $info, $stages, $s[4], $s[5]); } ?>

    </div></div>
  </div></div></div>
</div>
        </div>
      </div>

      <?php echo scripts(); ?>
      <?php echo footer(); ?>
    </div>
  </body>
</html>
