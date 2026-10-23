<?php include "includes/includes.php";
include "includes/modern-ui.php";

/* ============================================================================
 * Consolidated target report - LIVE.
 *
 * Same columns as consolidatedtargetreport, but nothing is read from the cron tables
 * (user_target_analytics / user_target_analytics_details).  Every number is worked out
 * from the transactions the moment the page is opened, with the SAME rules the user
 * dashboard uses:
 *   - an article counts once per stage: the first matching transaction of the range
 *   - pages = pages_completed, else the article's page_count, else manuscript_count
 *   - achievement of a customer / stage = pages / target; a stage with no target adds no % (as the existing report does)
 *   - production / working time come from the transactions' start and end times
 * All employees of the department are read with a handful of queries (not one per
 * employee, day, customer and stage like the cron).
 * Working days / adjusted working days / non-workflow / complexity / feedback are the
 * report's own rules, read from the live tables.
 * ========================================================================== */

$cr_self = !empty($cr_self);
$cr_url  = $cr_self ? 'myconsolidatedtargetreport' : (stripos(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '', 'consolidatedtargetreportlive') !== false ? 'consolidatedtargetreportlive' : 'consolidatedtargetreport');

function cr_e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function cr_hms($s) { $s = max(0, (int)$s); return sprintf('%02d:%02d:%02d', floor($s / 3600), floor(($s % 3600) / 60), $s % 60); }
function cr_has($hay, $needle) { return stripos((string)$hay, $needle) !== false; }

/* does a transaction count towards this stage?  (identical to user-dashboard.php) */
function cr_counts($row, $stage, $ce_group, $dept_req)
{
	$st = strtolower($row['current_status']);
	if ($ce_group) {
		if ($stage === 'PRECLEAN') {
			return (string)$row['dept'] === '22' && cr_has($row['current_stage'], 'FP')
			    && in_array($st, array('completed', 'to cross check', 'correction done', 'break'), true);
		}
		return in_array((string)$row['dept'], array('1', '2'), true)
		    && in_array($st, array('completed', 'to cross check', 'correction done'), true);
	}
	if ($stage === 'ISS')  { return $st === 'completed' && cr_has($row['current_stage'], 'ISS'); }
	if ($stage === 'CORR') { return $st === 'correction done'; }
	$ok = in_array((string)$dept_req, array('14', '9'), true)
	    ? array('completed', 'assign', 'to cross check', 'break')
	    : array('completed', 'to cross check');
	if (!in_array($st, $ok, true)) { return false; }
	/* FIN counts FIN rounds only; PMC is its own stage (it used to be counted inside Final) */
	return cr_has($row['current_stage'], $stage);
}

/* employees of a department that have a target assigned (user_target_assign) - the only ones this report lists */
define('CR_TARGET_USER', "EXISTS (SELECT 1 FROM user_target_assign a WHERE a.user_id = users.id)");

/* all numbers for a list of users and a date range - returns array(user_id => metrics) */
function cr_compute($db, $users, $start_d, $end_d)
{
	$out = array();
	if (!$users) { return $out; }
	$ids   = array_map('intval', array_keys($users));
	$in    = implode(',', $ids);
	$inq   = "'" . implode("','", $ids) . "'";            // process_user / user_id are varchar in places: always compare quoted
	$end_n = date('Y-m-d', strtotime($end_d . ' +1 day'));

	foreach ($ids as $uid) {
		$out[$uid] = array('FP' => 0, 'REV' => 0, 'CORR' => 0, 'FIN' => 0, 'PMC' => 0, 'ISS' => 0, 'JSTAGE' => 0, 'PRECLEAN' => 0,
		                   'pct' => 0.0, 'work_s' => 0, 'prod_s' => 0, 'nw_pct' => 0.0, 'nw_s' => 0,
		                   'days' => 0, 'adj' => 0.0, 'complex' => 0, 'feedback' => 0);
	}

	/* ---- targets: user -> target department -> customers ---- */
	$targets = array(); $depts_of = array();
	$q = $db->query("SELECT c.user_id, a.dept_id AS tdept, c.cust_id, c.page_count, c.rpc, c.cpc, c.fpc, c.ipc, c.jpc, c.mpc, c.ppage_count
	                 FROM user_target_customer_assign c
	                 JOIN user_target_assign a ON a.uta_id = c.uta_id
	                 JOIN adm_customer_master cm ON cm.id = c.cust_id
	                 WHERE c.user_id IN (".$in.")");
	while ($q && ($r = $q->fetch_assoc())) {
		$targets[(int)$r['user_id']][(string)$r['tdept']][] = $r;
		$set = in_array((string)$r['tdept'], array('1', '2', '22'), true) ? array('1', '2', '22') : array((string)$r['tdept']);
		foreach ($set as $d) { $depts_of[(int)$r['user_id']][$d] = true; }
	}

	/* ---- the users' transactions of the range, read once ---- */
	$tx = array();
	$q = $db->query("SELECT t.id, t.process_user, t.project_id, t.dept, t.current_stage, t.current_status, t.created_dt, wd.cust_id,
	                        CASE WHEN t.pages_completed IS NOT NULL AND t.pages_completed != 0 THEN t.pages_completed
	                             WHEN wd.page_count IS NOT NULL AND wd.page_count != 0 THEN wd.page_count
	                             ELSE wd.manuscript_count END AS pg
	                 FROM inw_transactions t
	                 JOIN inw_inward_dtl wd ON wd.id = t.project_id
	                 WHERE t.process_user IN (".$inq.") AND t.created_dt >= '".$start_d."' AND t.created_dt < '".$end_n."'");
	while ($q && ($r = $q->fetch_assoc())) { $tx[(int)$r['process_user']][] = $r; }

	/* ---- pages and % per customer / stage (range level, first occurrence of every article) ---- */
	foreach ($targets as $uid => $by_dept) {
		foreach ($by_dept as $td => $custs) {
			$ce = in_array((string)$td, array('1', '2', '22'), true);
			$dset = $ce ? array('1', '2', '22') : array((string)$td);
			$stages = $ce ? array('FP' => 'page_count', 'PRECLEAN' => 'ppage_count')
			              : array('FP' => 'page_count', 'REV' => 'rpc', 'CORR' => 'cpc', 'FIN' => 'fpc', 'PMC' => 'mpc', 'ISS' => 'ipc', 'JSTAGE' => 'jpc');
			$stages = rs_filter_stages($stages);                      // only the stages in $report_stage_types
			$rows_by_cust = array();
			if (isset($tx[$uid])) {
				foreach ($tx[$uid] as $r) { if (in_array((string)$r['dept'], $dset, true)) { $rows_by_cust[(string)$r['cust_id']][] = $r; } }
			}
			$seen = array();
			foreach ($custs as $tg) {
				if (isset($seen[$tg['cust_id']])) { continue; }          // a customer listed twice under the same department counts once
				$seen[$tg['cust_id']] = true;
				$rows = isset($rows_by_cust[(string)$tg['cust_id']]) ? $rows_by_cust[(string)$tg['cust_id']] : array();
				foreach ($stages as $stage => $field) {
					$first = array();
					foreach ($rows as $r) {
						if (!cr_counts($r, $stage, $ce, $td)) { continue; }
						$p = $r['project_id'];
						if (!isset($first[$p]) || $r['created_dt'] < $first[$p]['created_dt']
						    || ($r['created_dt'] === $first[$p]['created_dt'] && $r['id'] < $first[$p]['id'])) { $first[$p] = $r; }
					}
					$done = 0.0;
					foreach ($first as $r) { $done += (float)$r['pg']; }
					$target = isset($tg[$field]) ? (float)$tg[$field] : 0.0;
					$out[$uid][$stage] += $done;
					$out[$uid]['pct']  += ($target > 0) ? round(($done / $target) * 100, 2) : 0;   // no target for that stage = no % (as the report always did)
				}
			}
		}
	}

	/* ---- working / production time, per user and day ---- */
	$prod_status = array('completed', 'assign', 'to cross check', 'need clarification', 'query', 'pending', 'correction done', 'break', 'cross checking');
	$work_day = array(); $prod_day = array();
	$q = $db->query("SELECT process_user, dept, LOWER(current_status) AS st, DATE(start_time) AS d,
	                        SUM(TIMESTAMPDIFF(SECOND, start_time, end_time)) AS s
	                 FROM inw_transactions
	                 WHERE process_user IN (".$inq.") AND start_time >= '".$start_d."' AND end_time < '".$end_n."' AND end_time >= start_time
	                 GROUP BY process_user, dept, LOWER(current_status), DATE(start_time)");
	while ($q && ($r = $q->fetch_assoc())) {
		$uid = (int)$r['process_user'];
		if (empty($depts_of[$uid]) || !isset($depts_of[$uid][(string)$r['dept']])) { continue; }
		$s = (int)$r['s'];
		$out[$uid]['work_s'] += $s;
		$work_day[$uid][$r['d']] = (isset($work_day[$uid][$r['d']]) ? $work_day[$uid][$r['d']] : 0) + $s;
		if (in_array($r['st'], $prod_status, true)) {
			$out[$uid]['prod_s'] += $s;
			$prod_day[$uid][$r['d']] = (isset($prod_day[$uid][$r['d']]) ? $prod_day[$uid][$r['d']] : 0) + $s;
		}
	}

	/* ---- non-workflow ---- */
	$nw_day = array(); $nw_dates = array();
	$q = $db->query("SELECT user_id, nw_date, nw_percentage, TIME_TO_SEC(nw_pt) AS s FROM non_workflow_user_percentage
	                 WHERE user_id IN (".$in.") AND nw_date >= '".$start_d."' AND nw_date <= '".$end_d."'");
	while ($q && ($r = $q->fetch_assoc())) {
		$uid = (int)$r['user_id'];
		$out[$uid]['nw_pct'] += (float)$r['nw_percentage'];
		$out[$uid]['nw_s']   += (int)$r['s'];
		$nw_day[$uid][$r['nw_date']] = (isset($nw_day[$uid][$r['nw_date']]) ? $nw_day[$uid][$r['nw_date']] : 0) + (int)$r['s'];
		$nw_dates[$uid][$r['nw_date']] = true;
	}

	/* ---- working days: a day with transaction time, or a non-workflow entry; 7h+ = 1 day, some work = half a day ---- */
	foreach ($ids as $uid) {
		$days = array();
		if (!empty($work_day[$uid])) { foreach ($work_day[$uid] as $d => $s) { if ($s > 0) { $days[$d] = true; } } }
		if (!empty($nw_dates[$uid])) { foreach ($nw_dates[$uid] as $d => $_) { $days[$d] = true; } }
		$adj = 0.0;
		foreach ($days as $d => $_) {
			$tot = (isset($prod_day[$uid][$d]) ? $prod_day[$uid][$d] : 0) + (isset($nw_day[$uid][$d]) ? $nw_day[$uid][$d] : 0);
			$adj += $tot >= 25200 ? 1 : ($tot > 0 ? 0.5 : 0);
		}
		$out[$uid]['days'] = count($days);
		$out[$uid]['adj']  = $adj;
	}

	/* ---- complexity and internal feedback ---- */
	$q = $db->query("SELECT user_id, COUNT(*) AS n FROM wf_file_complexity_users
	                 WHERE user_id IN (".$in.") AND fcu_date >= '".$start_d."' AND fcu_date <= '".$end_d."'
	                   AND approval_status = 'Approved' AND file_status = 'complex' GROUP BY user_id");
	while ($q && ($r = $q->fetch_assoc())) { if (isset($out[(int)$r['user_id']])) { $out[(int)$r['user_id']]['complex'] = (int)$r['n']; } }
	$q = $db->query("SELECT u_id, COUNT(*) AS n FROM inw_internal_feedback
	                 WHERE u_id IN (".$in.") AND created_on >= '".$start_d."' AND created_on < '".$end_n."' GROUP BY u_id");
	while ($q && ($r = $q->fetch_assoc())) { if (isset($out[(int)$r['u_id']])) { $out[(int)$r['u_id']]['feedback'] = (int)$r['n']; } }
	return $out;
}

/* ---- employee drop-down for a department (called when the department changes) ---- */
if (isset($_REQUEST['ajax_users'])) {
	while (ob_get_level() > 0) { ob_end_clean(); }
	$d = (int)(isset($_REQUEST['dept_id']) ? $_REQUEST['dept_id'] : 0);
	echo '<option value="all">-All-</option>';
	$q = $d ? $db->query("SELECT id, full_name FROM users WHERE department_id = '".$d."' AND approved = '1' AND ".CR_TARGET_USER." ORDER BY full_name ASC") : false;
	while ($q && ($r = $q->fetch_assoc())) { echo '<option value="'.(int)$r['id'].'">'.cr_e($r['full_name']).'</option>'; }
	exit;
}

/* ---- request ---- */
$mode      = (isset($_REQUEST['date_wise']) && $_REQUEST['date_wise'] === 'm') ? 'm' : 'd';
$submitted = isset($_REQUEST['getaction']);
$dep_id    = isset($_REQUEST['dep_id']) ? (int)$_REQUEST['dep_id'] : 0;
$sel_user  = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : 'all';
if ($cr_self) {                                          // my own figures only: own department, own user
	$dep_id   = (int)(isset($_COOKIE['department_id']) ? $_COOKIE['department_id'] : 0);
	$sel_user = (int)(isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : 0);
}
$sel_user  = ($sel_user === 'all' || (int)$sel_user > 0) ? $sel_user : 'all';
$from_val  = !empty($_REQUEST['from_dt'])  ? $_REQUEST['from_dt']  : date('d-m-Y', strtotime('-7 days'));
$to_val    = !empty($_REQUEST['to_dt'])    ? $_REQUEST['to_dt']    : date('d-m-Y');
$month_val = !empty($_REQUEST['mfrom_dt']) ? $_REQUEST['mfrom_dt'] : date('m-Y');

$start_d = $end_d = ''; $label = ''; $rows = array(); $users = array(); $dept_name = '';
$t0 = microtime(true);
if ($submitted && $dep_id > 0) {
	if ($mode === 'm') {
		$first = strtotime('01-'.$month_val); if (!$first) { $first = strtotime(date('Y-m-01')); }
		$s_ts = strtotime(date('Y-m-01', $first)); $e_ts = strtotime(date('Y-m-t', $first));
	} else {
		$s_ts = strtotime($from_val); $e_ts = strtotime($to_val);
		if (!$s_ts) { $s_ts = strtotime('-7 days'); } if (!$e_ts || $e_ts < $s_ts) { $e_ts = $s_ts; }
		if ($e_ts - $s_ts > 366 * 86400) { $e_ts = $s_ts + 366 * 86400; }
	}
	$start_d = date('Y-m-d', $s_ts); $end_d = date('Y-m-d', $e_ts);
	$label = date('d M Y', $s_ts).($start_d !== $end_d ? ' &ndash; '.date('d M Y', $e_ts) : '');

	$dq = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$dep_id." LIMIT 1");
	if ($dq && ($dr = $dq->fetch_assoc())) { $dept_name = $dr['dept_name']; }
	$uc = ($sel_user === 'all') ? '' : " AND id = ".(int)$sel_user;
	$uq = $db->query("SELECT id, full_name FROM users WHERE department_id = '".$dep_id."' AND approved != 0 AND ".CR_TARGET_USER.$uc." ORDER BY full_name ASC");
	while ($uq && ($ur = $uq->fetch_assoc())) { $users[(int)$ur['id']] = $ur['full_name']; }

	$m = cr_compute($db, $users, $start_d, $end_d);
	foreach ($users as $uid => $name) {
		$x = $m[$uid];
		$days = (int)$x['days'];
		$x['name']      = $name;
		$x['achieved']  = $days > 0 ? round(($x['pct'] + $x['nw_pct']) / $days, 2) : 0;
		$x['nw_avg']    = $days > 0 ? round($x['nw_pct'] / $days, 2) : 0;
		$x['pages']     = $x['FP'] + $x['REV'] + $x['CORR'] + $x['FIN'] + $x['PMC'] + $x['ISS'] + $x['JSTAGE'] + $x['PRECLEAN'];
		$x['prod_total'] = $x['prod_s'] + $x['nw_s'];
		$rows[] = $x;
	}
	usort($rows, function ($a, $b) { return $b['achieved'] <=> $a['achieved']; });
}
$elapsed = round(microtime(true) - $t0, 2);

$active = array_filter($rows, function ($r) { return $r['days'] > 0; });
$avg_ach = $active ? round(array_sum(array_column($active, 'achieved')) / count($active), 2) : 0;
$tot_pages = array_sum(array_column($rows, 'pages'));
$low = count(array_filter($active, function ($r) { return $r['achieved'] < 74; }));
$top = $active ? reset($rows) : null;

$depts = array();
$dq = $db->query("SELECT dept_name, dept_code FROM adm_dept_master ORDER BY dept_name ASC");
while ($dq && ($dr = $dq->fetch_assoc())) { $depts[] = $dr; }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php mu_head(); ?>
    <style>
      .cr-live{display:inline-flex;align-items:center;gap:7px;padding:5px 12px;border-radius:20px;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.28);font-size:12px;font-weight:700;}
      .cr-live:before{content:"";width:8px;height:8px;border-radius:50%;background:#4ade80;animation:muPulse 1.6s infinite;}
      .cr-pct{display:inline-block;min-width:62px;padding:4px 10px;border-radius:20px;text-align:center;font-weight:700;font-variant-numeric:tabular-nums;}
      .cr-pct.low{background:#fde8e8;color:#c0392b;} .cr-pct.mid{background:#fdf1d9;color:#b87800;} .cr-pct.high{background:#e3f6ec;color:#1e9e61;}
      .cr-flag{display:inline-block;min-width:30px;padding:3px 9px;border-radius:20px;text-align:center;font-weight:700;background:#fde8e8;color:#c0392b;}
      .cr-zero{color:#b3bcc7;}
      .cr-name{font-weight:700;color:var(--navy);white-space:nowrap;}
      .cr-table td,.cr-table th{white-space:nowrap;}
      .cr-wrap .dataTables_wrapper .dt-buttons .dt-button{border-radius:10px;}
      .cr-note{margin:10px 2px 0;color:var(--mute);font-size:12px;}
      .cr-dayrow.mu-field{min-width:150px;}
    </style>
  </head>

  <body>
    <div class="wholeouter">
    <?php include("includes/top-header.php"); ?>

      <div class="mu-root">
        <div class="mu-hero">
          <div>
            <small>Reports</small>
            <h1><?php echo $cr_self ? 'My Consolidated Target Report' : 'Consolidated Target Report'; ?></h1>
            <p><?php echo $submitted && $dep_id ? cr_e($dept_name).' &nbsp;&middot;&nbsp; '.$label : 'Live from the transactions - not from the cron tables'; ?></p>
          </div>
          <div class="mu-hero-right"><span class="cr-live">LIVE DATA</span>
            <?php if ($submitted && $dep_id) { ?><div class="mu-chip"><small>Calculated</small><b><?php echo date('h:i A'); ?> &middot; <?php echo $elapsed; ?>s</b></div><?php } ?>
          </div>
        </div>

        <form method="post" autocomplete="off" id="validation-form">
          <section class="mu-panel mu-filter-panel mu-reveal">
            <div class="mu-filter">
              <div class="mu-field"><label>View by</label>
                <div class="mu-seg">
                  <label><input type="radio" name="date_wise" id="daywise" value="d" <?php echo $mode === 'd' ? 'checked' : ''; ?>><span>Day</span></label>
                  <label><input type="radio" name="date_wise" id="monthwise" value="m" <?php echo $mode === 'm' ? 'checked' : ''; ?>><span>Month</span></label>
                </div></div>
              <div class="mu-field day-f"><label>Date range</label>
                <div class="dvr" data-from-name="from_dt" data-to-name="to_dt" data-from="<?php echo cr_e($from_val); ?>" data-to="<?php echo cr_e($to_val); ?>"></div></div>
              <link rel="stylesheet" href="dv-rangepicker.css?v=1"><script src="dv-rangepicker.js?v=1"></script>
              <div class="mu-field month-f"><label for="mfrom_dt">Month</label><input type="text" id="mfrom_dt" name="mfrom_dt" class="monthlyinputdate2" placeholder="Select month" value="<?php echo cr_e($month_val); ?>"></div>
<?php if ($cr_self) { ?>
              <input type="hidden" name="dep_id" value="<?php echo $dep_id; ?>"><input type="hidden" name="user_id" value="<?php echo (int)$sel_user; ?>">
<?php } else { ?>
              <div class="mu-field" style="min-width:200px;"><label for="dep_id">Department</label>
                <select id="dep_id" name="dep_id" required>
                  <option value="">-Select-</option>
<?php foreach ($depts as $d) { ?>
                  <option value="<?php echo (int)$d['dept_code']; ?>" <?php echo $dep_id == $d['dept_code'] ? 'selected' : ''; ?>><?php echo cr_e($d['dept_name']); ?></option>
<?php } ?>
                </select></div>
              <div class="mu-field" style="min-width:220px;"><label for="user_id">Employee</label>
                <select id="user_id" name="user_id">
                  <option value="all">-All-</option>
<?php
if ($dep_id > 0) {
	$eq = $db->query("SELECT id, full_name FROM users WHERE department_id = '".$dep_id."' AND approved = '1' AND ".CR_TARGET_USER." ORDER BY full_name ASC");
	while ($eq && ($er = $eq->fetch_assoc())) { echo '<option value="'.(int)$er['id'].'" '.((string)$sel_user === (string)$er['id'] ? 'selected' : '').'>'.cr_e($er['full_name']).'</option>'; }
}
?>
                </select></div>
<?php } ?>
              <div class="mu-field" style="flex-direction:row;gap:10px;">
                <button class="mu-btn primary" type="submit" name="getaction" value="1"><i class="fa fa-bolt"></i> Get live result</button>
                <a class="mu-btn" href="<?php echo $cr_url; ?>" title="Reset"><i class="fa fa-refresh"></i></a>
              </div>
            </div>
            <p class="cr-note" style="margin-top:12px;"><i class="fa fa-info-circle"></i> <?php echo $cr_self ? 'Your own figures, calculated live from the transactions.' : 'Only employees with a <b>target assigned</b> are listed.'; ?></p>
          </section>
        </form>

<?php if ($submitted && $dep_id > 0) { ?>
        <div class="mu-stats">
          <div class="mu-stat info mu-reveal"><i class="fa fa-users"></i><div><span>Employees</span><b data-count="<?php echo count($rows); ?>">0</b></div></div>
          <div class="mu-stat <?php echo $avg_ach < 74 ? 'bad' : ($avg_ach < 94 ? 'warn' : 'ok'); ?> mu-reveal"><i class="fa fa-bullseye"></i><div><span>Average achieved</span><b data-count="<?php echo $avg_ach; ?>" data-decimals="2" data-suffix="%">0%</b></div></div>
          <div class="mu-stat accent mu-reveal"><i class="fa fa-file-text-o"></i><div><span>Total pages</span><b data-count="<?php echo (int)$tot_pages; ?>">0</b></div></div>
          <div class="mu-stat ok mu-reveal"><i class="fa fa-trophy"></i><div><span>Top performer</span><b style="font-size:16px;"><?php echo $top ? cr_e($top['name']).' &middot; '.$top['achieved'].'%' : '&ndash;'; ?></b></div></div>
          <div class="mu-stat bad mu-reveal"><i class="fa fa-arrow-down"></i><div><span>Below 74%</span><b data-count="<?php echo $low; ?>">0</b></div></div>
        </div>

        <section class="mu-panel cr-wrap mu-reveal">
          <h3><i class="fa fa-table"></i> Employees with a target <span class="mu-right"><?php echo count($rows); ?> in <?php echo cr_e($dept_name); ?></span></h3>
<?php if (!$rows) { ?>
          <div class="mu-empty"><i class="fa fa-bullseye"></i>No employee of this department has a target assigned.</div>
<?php } else { ?>
          <div class="mu-table-wrap" style="border:0;">
            <table class="mu-table cr-table" id="crTable" style="width:100%">
              <thead><tr>
                <th>#</th><th>Employee</th><?php foreach (array('FP' => '#FP', 'REV' => '#REV', 'CORR' => '#CORRECTIONS', 'FIN' => '#FINALS', 'PMC' => '#PMC', 'ISS' => '#ISSUE', 'JSTAGE' => '#JSTAGE') as $k => $h) { if (rs_target_enabled($k)) { echo '<th>' . $h . '</th>'; } } ?>
                <th>Non-workflow %</th><th>Non-workflow time</th><th>Complexity</th><th>Total pages</th><th>Production time</th><th>Working time</th>
                <th>Working days</th><th>Adjusted days</th><th>Feedback</th><th>Achieved target %</th>
              </tr></thead>
              <tbody>
<?php foreach ($rows as $i => $r) {
	$cls = $r['achieved'] < 74 ? 'low' : ($r['achieved'] < 94 ? 'mid' : 'high');
	$z = function ($v) { return ((float)$v == 0) ? '<span class="cr-zero">0</span>' : (0 + $v); };
?>
                <tr>
                  <td><?php echo $i + 1; ?></td>
                  <td class="cr-name"><?php echo cr_e($r['name']); ?></td>
                  <?php foreach (array('FP', 'REV', 'CORR', 'FIN', 'PMC', 'ISS', 'JSTAGE') as $k) { if (rs_target_enabled($k)) { echo '<td>' . $z($r[$k]) . '</td>'; } } ?>
                  <td><?php echo $r['nw_avg']; ?>%</td>
                  <td><?php echo cr_hms($r['nw_s']); ?></td>
                  <td><?php echo $r['complex'] > 0 ? '<span class="cr-flag">'.(int)$r['complex'].'</span>' : '<span class="cr-zero">0</span>'; ?></td>
                  <td><b><?php echo 0 + $r['pages']; ?></b></td>
                  <td><?php echo cr_hms($r['prod_total']); ?></td>
                  <td><?php echo cr_hms($r['work_s']); ?></td>
                  <td><?php echo (int)$r['days']; ?></td>
                  <td><?php echo $r['adj']; ?></td>
                  <td><?php echo $r['feedback'] > 0 ? '<span class="cr-flag">'.(int)$r['feedback'].'</span>' : '<span class="cr-zero">0</span>'; ?></td>
                  <td data-order="<?php echo $r['achieved']; ?>"><span class="cr-pct <?php echo $cls; ?>"><?php echo $r['achieved']; ?>%</span></td>
                </tr>
<?php } ?>
              </tbody>
            </table>
          </div>
<?php } ?>
          <p class="cr-note">Worked out from the transactions when this page was opened (<?php echo date('d-m-Y h:i:s A'); ?>). Achieved % = (workflow % + non-workflow %) &divide; working days, using the same rules as the user dashboard.</p>
        </section>
<?php } elseif ($submitted) { ?>
        <div class="mu-empty mu-reveal"><i class="fa fa-building-o"></i>Choose a department.</div>
<?php } else { ?>
        <div class="mu-empty mu-reveal"><i class="fa fa-bolt"></i>Choose the period and department, then press <b>Get live result</b>.</div>
<?php } ?>
      </div>

      <?php echo footer(); ?>
    </div>

    <?php echo scripts(); ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script>
    $(function () {
      var today = new Date();
      function sync() { var m = $('input[name=date_wise]:checked').val() === 'm'; $('.month-f').toggle(m); $('.day-f').toggle(!m); }
      $('input[name=date_wise]').on('change', function () { if ($(this).val() === 'm') { $('#mfrom_dt').val(('0' + (today.getMonth() + 1)).slice(-2) + '-' + today.getFullYear()); } sync(); });
      sync();
      $(".monthlyinputdate").datepicker({ dateFormat: "dd-mm-yy", onSelect: function (t) { $(".monthlyinputdate1").datepicker("option", "minDate", t); } });
      $(".monthlyinputdate1").datepicker({ dateFormat: "dd-mm-yy", onSelect: function (t) { $(".monthlyinputdate").datepicker("option", "maxDate", t); } });
      $(".monthlyinputdate2").datepicker({ dateFormat: "mm-yy", changeMonth: true, changeYear: true, showButtonPanel: true,
        beforeShow: function () { $(this).datepicker('widget').addClass('mu-month-only'); },
        onClose: function () { var m = $("#ui-datepicker-div .ui-datepicker-month :selected").val(), y = $("#ui-datepicker-div .ui-datepicker-year :selected").val(); $(this).datepicker('setDate', new Date(y, m, 1)); } });

      /* employees of the chosen department */
      $('#dep_id').on('change', function () {
        var d = $(this).val();
        if (d) { $.post('<?php echo $cr_url; ?>', { ajax_users: 1, dept_id: d }, function (h) { $('#user_id').html(h); }); }
        else { $('#user_id').html('<option value="all">-All-</option>'); }
      });

      if ($('#crTable').length && $.fn.DataTable) {
        $('#crTable').DataTable({
          pageLength: 50, lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']], order: [[16, 'desc']], scrollX: true,
          dom: 'Bfrtip',
          buttons: [{ extend: 'collection', text: 'Export', buttons: ['copyHtml5', 'excelHtml5', 'csvHtml5'] }]
        });
      }
    });
    </script>
    <style>.mu-month-only .ui-datepicker-calendar{display:none;}</style>
    <?php mu_foot(); ?>
  </body>
</html>
