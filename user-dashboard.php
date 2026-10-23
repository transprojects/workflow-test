<?php include "includes/includes.php";
include "includes/modern-ui.php";

/* ============================================================================
 * Admin dashboard - target achievement for one employee / department / date range.
 *
 * Data rules
 *   - Targets are DAILY (pages per day, per customer and stage).  Achievement for a range is
 *       completed pages / (daily target x days in range)
 *     so a customer / stage card, the stage ring and the overall figure all use the same scale
 *     (the old cards divided by a single day's target, which showed e.g. 432% for a month).
 *   - A stage with no target (blank / 0) is not scored (the old code silently used a target of 1,
 *     which inflated the overall figure whenever pages were done for an un-targeted stage).
 *   - overall = sum of every customer x stage achievement (of every department when "All departments"
 *     is chosen) + average non-workflow % per day.
 *   - Non-workflow % / time are summed over the whole range (the old query read only the first row).
 *     Rejected entries are ignored.
 *
 * Queries
 *   - per department, the user's transactions for the range are read ONCE (index user+created_dt),
 *     limited to the customers that have a target and to the statuses the report can count; the
 *     stage / status rules are then applied in PHP (earliest matching transaction of each article).
 *   - targets, time summary and non-workflow are one query each; allocated-article statuses are
 *     fetched together instead of one query per article.
 * ========================================================================== */

/* my-dashboard.php sets $td_self = true and includes this file: same report, but for the logged-in user. */
$td_self = !empty($td_self);

function ud_e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function ud_has($hay, $needle) { return stripos((string)$hay, $needle) !== false; }
function ud_n($v, $dec = 2) { $s = number_format((float)$v, $dec, '.', ','); return strpos($s, '.') !== false ? rtrim(rtrim($s, '0'), '.') : $s; }
function ud_tone($pct) { return $pct < 50 ? 'bad' : ($pct < 80 ? 'warn' : 'good'); }
/* tone of an article's latest status for this employee: ok = worked / handed on, info = being worked, bad = not started */
function ud_alloc_tone($st)
{
	$st = strtolower(trim((string)$st));
	if ($st === '') { return 'bad'; }
	if (in_array($st, array('completed', 'correction done', 'to cross check', 'cross checking done'), true)) { return 'ok'; }
	return 'info';
}
function ud_hms($sec) { $sec = (int)$sec; return sprintf('%02d:%02d:%02d', floor($sec / 3600), floor($sec % 3600 / 60), $sec % 60); }

/* does a transaction count towards this stage?  (same rules the old per-stage SQL had) */
function ud_counts($row, $stage, $is_ce_group, $dept)
{
	$st = strtolower($row['current_status']);
	if ($is_ce_group) {
		if ($stage === 'PRECLEAN') {
			return (string)$row['dept'] === '22' && ud_has($row['current_stage'], 'FP')
			    && in_array($st, array('completed', 'to cross check', 'correction done', 'break'), true);
		}
		return in_array((string)$row['dept'], array('1', '2'), true)
		    && in_array($st, array('completed', 'to cross check', 'correction done'), true);
	}
	if ($stage === 'ISS')  { return $st === 'completed' && ud_has($row['current_stage'], 'ISS'); }
	if ($stage === 'CORR') { return $st === 'correction done'; }
	$ok = in_array($dept, array('14', '9'), true)
	    ? array('completed', 'assign', 'to cross check', 'break')
	    : array('completed', 'to cross check');
	if (!in_array($st, $ok, true)) { return false; }
	/* FIN counts FIN rounds only; PMC is its own stage with its own target (it used to be counted inside Final) */
	return ud_has($row['current_stage'], $stage);
}

/* departments the employee has a target in (CE / PE / PC share one report, so they collapse into one) */
function ud_target_depts($db, $user_id)
{
	$out = array();
	$q = $db->query("SELECT DISTINCT dept_id FROM user_target_assign WHERE user_id = ".(int)$user_id." AND dept_id IS NOT NULL ORDER BY dept_id");
	while ($q && ($r = $q->fetch_assoc())) { $out[] = (string)$r['dept_id']; }
	$ce = array_values(array_intersect($out, array('1', '2', '22')));
	if (count($ce) > 1) { $out = array_values(array_diff($out, $ce)); array_unshift($out, in_array('1', $ce, true) ? '1' : $ce[0]); }
	return $out;
}

/* articles allocated to the employee in this department (right-hand list) + their latest status */
function ud_alloc($db, $user_id, $dept, $start_date, $end_next, $td_self)
{
	$out = array(array(), array());
	$uid = (string)(int)$user_id;
	$alloc_where = '';
	if ($dept === '1' || $dept === '22') { $alloc_where = "wd.status = 'CE' AND wd.assigned_user_id = ".(int)$user_id; }
	elseif ($dept === '10')              { $alloc_where = ($td_self ? "wd.department_id = 10" : "wd.status = 'PAG'")." AND wd.assigned_od_user_id = ".(int)$user_id; }
	elseif ($dept === '14')              { $alloc_where = ($td_self ? "wd.department_id = 14" : "wd.status = 'PRQC'")." AND wd.assigned_od_user_id = ".(int)$user_id; }
	if ($alloc_where === '') { return $out; }
	$q = $db->query("SELECT wd.pub_id, wd.status, wd.od_due_date, wd.id AS inw_id
	                 FROM inw_inward_dtl wd
	                 WHERE ".$alloc_where."
	                   AND wd.od_due_date >= '".$start_date."' AND wd.od_due_date < '".$end_next."'
	                 ORDER BY wd.od_due_date, wd.id");
	while ($q && ($r = $q->fetch_assoc())) { $out[0][$r['inw_id']] = $r; }
	if ($out[0]) {
		$q = $db->query("SELECT t.project_id, t.current_status FROM inw_transactions t
		                 JOIN (SELECT project_id, MAX(id) AS mid FROM inw_transactions
		                       WHERE process_user = '".$uid."' AND project_id IN (".implode(',', array_map('intval', array_keys($out[0]))).")
		                       GROUP BY project_id) m ON m.mid = t.id");
		while ($q && ($r = $q->fetch_assoc())) { $out[1][$r['project_id']] = $r['current_status']; }
	}
	return $out;
}

/* the full target report of ONE department (live: calculated from the transactions) */
function ud_report($db, $user_id, $dept, $start_date, $end_next, $days, $td_self)
{
	$is_ce = in_array($dept, array('1', '2', '22'), true);
	$titles = $is_ce
		? array('FP' => array('First Proof - CE/PE', '#1b6ca8'), 'PRECLEAN' => array('First Proof - PC', '#2a7fc1'))
		: array('FP' => array('First Proof', '#1b6ca8'), 'REV' => array('Revises', '#F56500'), 'CORR' => array('Corrections', '#d64545'),
		        'FIN' => array('Final', '#1e9e61'), 'PMC' => array('PMC', '#c28a00'), 'ISS' => array('Issue Correction', '#6f42c1'), 'JSTAGE' => array('JSTAGE', '#d6881d'));
	$fields = $is_ce
		? array('FP' => 'page_count', 'PRECLEAN' => 'ppage_count')
		: array('FP' => 'page_count', 'REV' => 'rpc', 'CORR' => 'cpc', 'FIN' => 'fpc', 'PMC' => 'mpc', 'ISS' => 'ipc', 'JSTAGE' => 'jpc');
	$titles = rs_filter_stages($titles); $fields = rs_filter_stages($fields);   /* only the stages in $report_stage_types */

	$rep = array('dept' => $dept, 'is_ce' => $is_ce, 'titles' => $titles, 'customers' => array(),
	             'stage_pct' => array(), 'stage_raw' => array(), 'target_pct' => 0, 'raw_sum' => 0, 'alloc' => array(), 'alloc_status' => array());
	foreach ($titles as $s => $_) { $rep['stage_pct'][$s] = 0; $rep['stage_raw'][$s] = 0; }

	$uid = (string)(int)$user_id;
	$dept_sql = $db->real_escape_string($dept);

	/* targets per customer (latest assignment wins when a user/department has more than one) */
	$q = $db->query("SELECT cust.cust_id, c.cust_name, cust.page_count, cust.ppage_count, cust.rpc, cust.cpc, cust.fpc, cust.ipc, cust.jpc, cust.mpc
	                 FROM user_target_customer_assign cust
	                 JOIN adm_customer_master c ON c.id = cust.cust_id
	                 JOIN user_target_assign uta ON uta.uta_id = cust.uta_id
	                 WHERE cust.user_id = '".$uid."' AND uta.dept_id = '".$dept_sql."'
	                 ORDER BY uta.uta_id, cust.utca_id");
	$targets = array();
	while ($q && ($r = $q->fetch_assoc())) { $targets[$r['cust_id']] = $r; }

	if ($targets) {
		/* the user's transactions for the range, read once */
		$dept_cond = $is_ce ? "AND t.dept IN ('1','2','22')" : "AND t.dept = '".$dept_sql."'";
		$cust_in   = implode(',', array_map('intval', array_keys($targets)));
		$by_cust = array();
		$q = $db->query("SELECT t.id, t.project_id, t.dept, t.current_stage, t.current_status, t.created_dt, wd.cust_id,
		                        CASE WHEN t.pages_completed IS NOT NULL AND t.pages_completed != 0 THEN t.pages_completed
		                             WHEN wd.page_count IS NOT NULL AND wd.page_count != 0 THEN wd.page_count
		                             ELSE wd.manuscript_count END AS pg
		                 FROM inw_transactions t
		                 JOIN inw_inward_dtl wd ON wd.id = t.project_id
		                 WHERE t.process_user = '".$uid."'
		                   AND t.created_dt >= '".$start_date."' AND t.created_dt < '".$end_next."'
		                   AND t.current_status IN ('Completed','To Cross Check','Correction Done','Break','Assign')
		                   AND wd.cust_id IN (".$cust_in.")
		                   ".$dept_cond);
		while ($q && ($r = $q->fetch_assoc())) { $by_cust[(string)$r['cust_id']][] = $r; }

		foreach ($targets as $cid => $tg) {
			$rep['customers'][$cid] = array('name' => $tg['cust_name'], 'stages' => array());
			$rows = isset($by_cust[(string)$cid]) ? $by_cust[(string)$cid] : array();
			foreach ($fields as $stage => $field) {
				$first = array();   // earliest matching transaction per article
				foreach ($rows as $r) {
					if (!ud_counts($r, $stage, $is_ce, $dept)) { continue; }
					$p = $r['project_id'];
					if (!isset($first[$p]) || $r['created_dt'] < $first[$p]['created_dt']
					    || ($r['created_dt'] === $first[$p]['created_dt'] && $r['id'] < $first[$p]['id'])) { $first[$p] = $r; }
				}
				$done = 0;
				foreach ($first as $r) { $done += (float)$r['pg']; }
				$daily  = isset($tg[$field]) ? (float)$tg[$field] : 0;
				$period = $daily * $days;
				$raw    = $daily > 0 ? round($done / $daily * 100, 2) : null;     // % of ONE day's target, as the consolidated report adds it up
				$pct    = $raw !== null ? round($raw / $days, 2) : null;           // null = no target set
				if ($raw !== null) { $rep['stage_raw'][$stage] += $raw; $rep['raw_sum'] += $raw; $rep['stage_pct'][$stage] = round($rep['stage_raw'][$stage] / $days, 2); $rep['target_pct'] = round($rep['raw_sum'] / $days, 2); }
				$rep['customers'][$cid]['stages'][$stage] = array('done' => $done, 'daily' => $daily, 'period' => $period, 'pct' => $pct);
			}
		}
	}

	$al = ud_alloc($db, $user_id, $dept, $start_date, $end_next, $td_self);
	$rep['alloc'] = $al[0]; $rep['alloc_status'] = $al[1];
	return $rep;
}

/* ---- request ---- */
$user_id  = (int)($td_self ? (isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : 0) : (isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : 0));
$form_url = $td_self ? 'targetdashboard' : 'admindashboard';
$dept_req = isset($_REQUEST['dept_id']) ? (string)$_REQUEST['dept_id'] : null;
$is_all   = ($dept_req === 'all');
$have     = ($dept_req !== null && $dept_req !== '' && $user_id > 0);
$no_target = false;
if ($td_self && $user_id > 0) {                       // "my" page only shows for users that have a target
	$tc = $db->query("SELECT COUNT(*) AS n FROM user_target_assign WHERE user_id = '".$user_id."'");
	$tc = $tc ? $tc->fetch_assoc() : array('n' => 0);
	if ((int)$tc['n'] === 0) { $no_target = true; $have = false; }
}

$fd = isset($_REQUEST['fdate']) ? strtotime($_REQUEST['fdate']) : false;
$td = isset($_REQUEST['tdate']) ? strtotime($_REQUEST['tdate']) : false;
$fd = $fd ? $fd : strtotime('today');
$td = $td ? $td : $fd;
if ($td < $fd) { $t = $fd; $fd = $td; $td = $t; }
$start_date = date('Y-m-d', $fd);
$end_date   = date('Y-m-d', $td);
$end_next   = date('Y-m-d', strtotime('+1 day', $td));
$days       = (int)round(($td - $fd) / 86400) + 1;

$reports = array(); $dept_names = array(); $target_pct = 0; $overall = 0; $eff_days = $days;
$alloc = array(); $alloc_status = array(); $my_user = null;
$time = array('work' => '00:00:00', 'prod' => '00:00:00', 'prod_pct' => 0);
$nw = array('pct' => 0, 'time' => '00:00:00', 'raw' => 0);

if ($have) {
	$uid = (string)$user_id;
	$mu = $db->query("SELECT full_name FROM users WHERE id = ".$user_id." LIMIT 1");
	$my_user = $mu ? $mu->fetch_assoc() : null;

	$eff_days = $days;

	{
		/* ---- LIVE: same rules as the live Consolidated Target Report (consolidatedtargetreportlive) ----
		 * pages / % per customer & stage from the transactions; working time per day; working days =
		 * days with transaction time or a non-workflow entry; achieved = (sum of % + non-workflow %) / working days */
		$dept_list = $is_all ? ud_target_depts($db, $user_id) : array($dept_req);
		$scope = array();                                    // departments whose transaction time counts
		foreach ($dept_list as $d) {
			foreach ((in_array($d, array('1', '2', '22'), true) ? array('1', '2', '22') : array($d)) as $x) { $scope[$x] = true; }
		}
		$scope_in = $scope ? "'".implode("','", array_map(array($db, 'real_escape_string'), array_keys($scope)))."'" : "''";

		$prod_status = array('completed', 'assign', 'to cross check', 'need clarification', 'query', 'pending', 'correction done', 'break', 'cross checking');
		$work_s = 0; $prod_s = 0; $day_set = array();
		$q = $db->query("SELECT LOWER(current_status) AS st, DATE(start_time) AS d, SUM(TIMESTAMPDIFF(SECOND, start_time, end_time)) AS s
		                 FROM inw_transactions
		                 WHERE process_user = '".$uid."' AND dept IN (".$scope_in.")
		                   AND start_time >= '".$start_date."' AND end_time < '".$end_next."' AND end_time >= start_time
		                 GROUP BY LOWER(current_status), DATE(start_time)");
		while ($q && ($r = $q->fetch_assoc())) {
			$sec = (int)$r['s']; $work_s += $sec;
			if (in_array($r['st'], $prod_status, true)) { $prod_s += $sec; }
			if ($sec > 0) { $day_set[$r['d']] = true; }
		}

		/* ---- non workflow: one entry per day, summed over the range ---- */
		$nw_sec = 0;
		$q = $db->query("SELECT nw_date, CAST(nw_percentage AS DECIMAL(8,2)) AS pct, TIME_TO_SEC(nw_pt) AS sec
		                 FROM non_workflow_user_percentage
		                 WHERE user_id = '".$uid."' AND nw_date >= '".$start_date."' AND nw_date <= '".$end_date."'");
		while ($q && ($r = $q->fetch_assoc())) {
			$nw['raw'] += (float)$r['pct']; $nw_sec += (int)$r['sec'];
			$day_set[$r['nw_date']] = true;
		}
		$wd = count($day_set);
		$eff_days = $wd > 0 ? $wd : 1;
		$nw['time'] = ud_hms($nw_sec);
		$nw['pct']  = round($nw['raw'] / $eff_days, 2);      // average per working day
		$time['work'] = ud_hms($work_s);
		$time['prod'] = ud_hms($prod_s + $nw_sec);           // production + non-workflow, as the report shows it
		$time['prod_pct'] = $work_s > 0 ? min(100, round(($prod_s + $nw_sec) / $work_s * 100)) : 0;

		/* ---- one report per department (pages / (daily target x working days)) ---- */
		$raw_total = 0;
		foreach ($dept_list as $d) {
			$rep = ud_report($db, $user_id, $d, $start_date, $end_next, $eff_days, $td_self);
			if (!$rep['customers'] && $is_all) { continue; }
			$reports[$d] = $rep;
			$raw_total += $rep['raw_sum'];
			foreach ($rep['alloc'] as $k => $v) { $alloc[$k] = $v; }
			foreach ($rep['alloc_status'] as $k => $v) { $alloc_status[$k] = $v; }
		}
		$target_pct = round($raw_total / $eff_days, 2);
		$overall    = round(($raw_total + $nw['raw']) / $eff_days, 2);
	}
	if ($is_all && $reports) {
		foreach ($auth->getDepartmentsByCodes(array_keys($reports)) as $dd) { $dept_names[(string)$dd['dept_code']] = $dd['dept_name']; }
	}
	foreach (array('1' => 'CE / PE / PC') as $k => $v) { if (isset($reports[$k])) { $dept_names[$k] = $v; } }
}
$customers_total = 0;
foreach ($reports as $rep) { $customers_total += count($rep['customers']); }

/* employees that have a target (the selected one is always kept so the page never loses its context) */
$with_target = array(); $employees = array();
if (!$td_self) {
	$qt = $db->query("SELECT DISTINCT user_id FROM user_target_assign");
	while ($qt && ($rt = $qt->fetch_assoc())) { $with_target[(int)$rt['user_id']] = true; }
	foreach ($auth->showUserlist() as $u) {
		if (isset($with_target[(int)$u['id']]) || (int)$u['id'] === $user_id) { $employees[] = $u; }
	}
}

/* department options for the selected employee */
$dept_options = '<option value="">Select Department</option>';
if ($user_id > 0) {
	$user = $auth->getUserById($user_id);
	if ($user) {
		$dept_options .= "<option value='all' ".($is_all ? 'selected' : '').">All departments (overall)</option>";
		$departments = array();
		if (!empty($user['department_id'])) { $departments[] = $user['department_id']; }
		if (!empty($user['access_other_depts'])) { $departments = array_merge($departments, explode(',', $user['access_other_depts'])); }
		$dept_details = $auth->getDepartmentsByCodes(array_unique($departments));
		if ($user['department_id'] == 1) {
			$combined = array(1, 2, 22); $names = array();
			foreach ($dept_details as $d) { if (in_array($d['dept_code'], $combined)) { $names[] = $d['dept_name']; } }
			if ($names) { $dept_options .= "<option value='1' ".($dept_req === '1' ? 'selected' : '').">".ud_e(implode(' / ', $names))." (Primary)</option>"; }
			foreach ($dept_details as $d) {
				if (!in_array($d['dept_code'], $combined)) {
					$dept_options .= "<option value='".ud_e($d['dept_code'])."' ".($d['dept_code'] == $dept_req ? 'selected' : '').">".ud_e($d['dept_name'])."</option>";
				}
			}
		} else {
			foreach ($dept_details as $d) {
				$primary = ($d['dept_code'] == $user['department_id']);
				$dept_options .= "<option value='".ud_e($d['dept_code'])."' ".($d['dept_code'] == $dept_req ? 'selected' : '').">".ud_e($d['dept_name']).($primary ? ' (Primary)' : '')."</option>";
			}
		}
	} else { $dept_options = '<option value="">No Department Found</option>'; }
}

$range_label = date('d M Y', $fd).($start_date !== $end_date ? ' &ndash; '.date('d M Y', $td) : '');
$short_label = $start_date === $end_date ? date('j M Y', $fd) : date('j M', $fd).' – '.date('j M Y', $td);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php mu_head(); ?>
    <link href="delivery-dashboard-v2.css?v=20261003g" rel="stylesheet" />
    <link href="user-dashboard-v2.css?v=20261003h" rel="stylesheet" />
  </head>

  <body>
    <div class="wholeouter">
    <?php include("includes/top-header.php"); ?>

      <div class="dv ud">

        <header class="dv-bar">
          <div class="dv-title">
            <span class="dv-eyebrow"><?php echo $td_self ? 'My target dashboard' : 'Admin dashboard'; ?><?php echo $have && $is_all ? ' &middot; all departments' : ''; ?></span>
            <h1><?php echo $have && $my_user ? ud_e($my_user['full_name']) : ($td_self ? 'My performance' : 'Employee performance'); ?></h1>
            <span class="dv-range"><i class="fa fa-calendar-o"></i> <?php echo $have ? $range_label.' &middot; '.$days.' day'.($days > 1 ? 's' : '').' &middot; '.$eff_days.' working day'.($eff_days > 1 ? 's' : '') : 'Choose an employee, department and dates'; ?></span>
          </div>
        </header>

        <form method="get" name="search_target" action="<?php echo $form_url; ?>" class="dv-card ud-filter">
<?php if (!$td_self) { ?>
          <div class="ud-field ud-grow">
            <label for="user_id">Employee <em><?php echo count($with_target); ?> with targets</em></label>
            <select class="form-select" name="user_id" id="user_id" required>
              <option value="">Select Employee</option>
<?php foreach ($employees as $u) { ?>
              <option value="<?php echo ud_e($u['id']); ?>" <?php echo ($user_id == $u['id']) ? 'selected' : ''; ?>><?php echo ud_e($u['full_name']); ?></option>
<?php } ?>
            </select>
          </div>
<?php } ?>
          <div class="ud-field ud-grow">
            <label for="dept_id">Department</label>
            <select class="form-select" name="dept_id" id="dept_id" required><?php echo $dept_options; ?></select>
          </div>

          <div class="ud-field">
            <label>Date range</label>
            <div class="dv-filter">
              <button type="button" id="openDatepicker" class="dv-btn ghost"><i class="fa fa-calendar"></i><span id="dateDisplay"><?php echo ud_e($short_label); ?></span><i class="fa fa-angle-down ud-caret"></i></button>
              <div id="dateRangeInputs">
                <div class="dv-pop">
                  <h6>Select dates</h6>
                  <div class="dv-presets">
                    <button type="button" data-p="today">Today</button>
                    <button type="button" data-p="yesterday">Yesterday</button>
                    <button type="button" data-p="7">Last 7 days</button>
                    <button type="button" data-p="30">Last 30 days</button>
                    <button type="button" data-p="month">This month</button>
                    <button type="button" data-p="lastmonth">Last month</button>
                  </div>
                  <div class="dv-sel" id="dvSelTxt"></div>
                  <div class="dvc" id="dvCal"></div>
                  <input type="hidden" name="fdate" id="fromDate" value="<?php echo $start_date; ?>" />
                  <input type="hidden" name="tdate" id="toDate" value="<?php echo $end_date; ?>" />
                  <button type="button" id="dvApply" class="dv-btn go">Use this range</button>
                </div>
              </div>
            </div>
          </div>

          <div class="ud-field ud-btns">
            <button class="dv-btn go ud-search" type="submit"><i class="fa fa-search"></i><span>Search</span></button>
            <a class="dv-btn ghost" href="<?php echo $form_url; ?>" title="Reset"><i class="fa fa-refresh"></i></a>
          </div>
        </form>

<?php if ($have) { $tone = ud_tone($overall); ?>
        <p class="ud-src live"><i class="fa fa-bolt"></i>
          Live figures from the transactions: (sum of achievement % + non-workflow %) &divide; <b><?php echo $eff_days; ?> working day<?php echo $eff_days > 1 ? 's' : ''; ?></b>.
          A working day is a day on which the employee has transaction time (in the departments shown) or a non-workflow entry.
        </p>
        <section class="dv-kpis ud-kpis">
          <article class="dv-card dv-k dv-k-total">
            <span class="dv-k-label">Overall target achieved</span>
            <b class="dv-k-num"><span data-count="<?php echo $overall; ?>" data-decimals="2" data-suffix="%"><?php echo ud_n($overall); ?>%</span></b>
            <div class="dv-split"><i style="flex:<?php echo max(0.0001, min(100, $overall)); ?>"></i><u style="flex:<?php echo max(0.0001, 100 - min(100, $overall)); ?>;background:transparent"></u></div>
            <div class="dv-split-lbl"><span>Targets <strong><?php echo ud_n($target_pct); ?>%</strong></span><span>Non-workflow <strong><?php echo ud_n($nw['pct']); ?>%</strong></span></div>
          </article>
          <article class="dv-card dv-k ud-k ud-accent"><i class="fa fa-calendar"></i><div><span class="dv-k-label">Working days</span><b class="dv-k-num sm" data-count="<?php echo $eff_days; ?>"><?php echo $eff_days; ?></b><span class="dv-k-sub">of <?php echo $days; ?> calendar days</span></div></article>
          <article class="dv-card dv-k ud-k ud-green"><i class="fa fa-cogs"></i><div><span class="dv-k-label">Production time</span><b class="dv-k-num sm"><?php echo ud_e($time['prod']); ?></b><span class="dv-k-sub"><?php echo $time['prod_pct']; ?>% of working time</span></div></article>
          <article class="dv-card dv-k ud-k ud-blue"><i class="fa fa-clock-o"></i><div><span class="dv-k-label">Working time</span><b class="dv-k-num sm"><?php echo ud_e($time['work']); ?></b></div></article>
          <article class="dv-card dv-k ud-k ud-red"><i class="fa fa-coffee"></i><div><span class="dv-k-label">Non-workflow time</span><b class="dv-k-num sm"><?php echo ud_e($nw['time']); ?></b><span class="dv-k-sub"><?php echo ud_n($nw['pct']); ?>% avg / day</span></div></article>
        </section>

        <div class="ud-grid">
          <div class="ud-left">
            <section class="dv-card ud-panel">
              <div class="dv-card-head"><h2>Target achieved</h2><span class="dv-hint">Daily targets &times; <?php echo $eff_days; ?> working day<?php echo $eff_days > 1 ? 's' : ''; ?></span></div>
              <div class="ud-gauge">
                <span class="dv-ring big ud-t-<?php echo $tone; ?>" style="--p:<?php echo max(0, min(100, $overall)); ?>"><b><?php echo ud_n($overall, 0); ?>%</b></span>
                <div class="ud-gauge-body">
                  <div class="ud-gauge-txt"><b><span data-count="<?php echo $overall; ?>" data-decimals="2" data-suffix="%"><?php echo ud_n($overall); ?>%</span></b><span>of target for the selected period</span></div>
                  <div class="ud-track"><i class="ud-t-<?php echo $tone; ?>" style="width:<?php echo max(0, min(100, $overall)); ?>%"></i></div>
                  <div class="ud-scale"><span>0%</span><span>50%</span><span>100%</span></div>
<?php if ($reports) { ?>
                  <div class="ud-chips">
<?php if ($is_all) { foreach ($reports as $d => $rep) { ?>
                    <span class="ud-chip" style="--c:#1b6ca8"><i></i><?php echo ud_e(isset($dept_names[$d]) ? $dept_names[$d] : 'Dept '.$d); ?> <b><?php echo ud_n($rep['target_pct']); ?>%</b></span>
<?php } } else { $rep = reset($reports); foreach ($rep['titles'] as $stage => $meta) { ?>
                    <span class="ud-chip" style="--c:<?php echo $meta[1]; ?>"><i></i><?php echo ud_e($meta[0]); ?> <b><?php echo ud_n($rep['stage_pct'][$stage]); ?>%</b></span>
<?php } } ?>
                    <span class="ud-chip" style="--c:#8896a5"><i></i>Non-workflow <b><?php echo ud_n($nw['pct']); ?>%</b></span>
                  </div>
<?php } ?>
                </div>
              </div>
            </section>

            <section class="dv-card ud-panel">
              <div class="dv-card-head"><h2>Analytics</h2><span class="dv-hint"><?php echo $is_all ? count($reports).' department'.(count($reports) === 1 ? '' : 's').' &middot; ' : ''; ?><?php echo $customers_total; ?> customer<?php echo $customers_total === 1 ? '' : 's'; ?></span></div>
<?php if (!$customers_total) { ?>
              <div class="dv-empty"><i class="fa fa-bullseye"></i><b>No targets assigned</b><span>This employee has no targets <?php echo $is_all ? 'in any department.' : 'for the selected department.'; ?></span></div>
<?php } else { foreach ($reports as $d => $rep) { ?>
<?php if ($is_all) { $dp = $rep['target_pct']; ?>
              <div class="ud-dept"><h3><i class="fa fa-sitemap"></i> <?php echo ud_e(isset($dept_names[$d]) ? $dept_names[$d] : 'Department '.$d); ?></h3><span class="ud-pillpct ud-t-<?php echo ud_tone($dp); ?>"><?php echo ud_n($dp); ?>%</span></div>
<?php } ?>
              <div class="ud-stages">
<?php foreach ($rep['titles'] as $stage => $meta) { $sum = $rep['stage_pct'][$stage]; ?>
                <article class="ud-stage" style="--c:<?php echo $meta[1]; ?>">
                  <header>
                    <h4><?php echo ud_e($meta[0]); ?></h4>
                    <span class="dv-ring sm ud-t-<?php echo ud_tone($sum); ?>" style="--p:<?php echo max(0, min(100, $sum)); ?>"><b><?php echo ud_n($sum, 0); ?>%</b></span>
                  </header>
<?php foreach ($rep['customers'] as $c) { $s = $c['stages'][$stage]; $has = $s['pct'] !== null; ?>
                  <div class="ud-cust<?php echo $has ? '' : ' none'; ?>">
                    <div class="ud-cust-top"><b><?php echo ud_e($c['name']); ?></b>
                      <span><?php echo $has ? ud_n($s['pct']).'%' : 'No target'; ?></span></div>
                    <div class="ud-mini"><i class="ud-t-<?php echo $has ? ud_tone($s['pct']) : 'none'; ?>" style="width:<?php echo $has ? max(0, min(100, $s['pct'])) : 0; ?>%"></i></div>
                    <small><?php echo ud_n($s['done']); ?> pages<?php echo $has ? ' of '.ud_n($s['period']).' <em>('.ud_n($s['daily']).'/day)</em>' : ''; ?></small>
                  </div>
<?php } ?>
                </article>
<?php } ?>
              </div>
<?php } } ?>
            </section>
          </div>

          <aside class="dv-card ud-panel ud-alloc">
<?php
$al = array('ok' => 0, 'info' => 0, 'bad' => 0);
foreach ($alloc as $id => $a) { $al[ud_alloc_tone(isset($alloc_status[$id]) ? $alloc_status[$id] : '')]++; }
$al_total = count($alloc);
$al_pct = function ($n) use ($al_total) { return $al_total > 0 ? round($n / $al_total * 100) : 0; };
?>
            <div class="dv-card-head"><h2>Allocated articles</h2><span class="dv-hint"><?php echo $al_total; ?> article<?php echo $al_total === 1 ? '' : 's'; ?></span></div>
<?php if (!$alloc) { ?>
            <div class="dv-empty"><i class="fa fa-inbox"></i><b>No allocated articles</b><span>Nothing due in the selected dates.</span></div>
<?php } else { ?>
            <div class="ud-al-sum">
              <div class="ud-al-bar"><i class="ok" style="flex:<?php echo max(0.0001, $al['ok']); ?>"></i><i class="info" style="flex:<?php echo max(0.0001, $al['info']); ?>"></i><i class="bad" style="flex:<?php echo max(0.0001, $al['bad']); ?>"></i></div>
              <div class="ud-al-key">
                <span><em class="ok"></em>Done <b><?php echo $al['ok']; ?></b></span>
                <span><em class="info"></em>In progress <b><?php echo $al['info']; ?></b></span>
                <span><em class="bad"></em>Not started <b><?php echo $al['bad']; ?></b></span>
              </div>
            </div>
            <ul class="ud-list">
<?php foreach ($alloc as $id => $a) {
	$cs = isset($alloc_status[$id]) ? trim($alloc_status[$id]) : '';
	$tone = ud_alloc_tone($cs);
	$label = $cs === '' ? 'Not started' : ($cs === 'Inprogress' ? 'In progress' : $cs);
	$due = !empty($a['od_due_date']) && $a['od_due_date'] !== '0000-00-00' ? date('d M Y', strtotime($a['od_due_date'])) : '';
	$late = $due && $tone !== 'ok' && strtotime($a['od_due_date']) < strtotime('today');
?>
              <li>
                <div class="ud-al-main"><b><?php echo ud_e($a['pub_id']); ?></b><small><?php echo $due ? 'Due '.$due : 'No due date'; ?><?php echo $late ? ' <em class="late">overdue</em>' : ''; ?></small></div>
                <span class="ud-pill <?php echo $tone; ?>"><?php echo ud_e($label); ?></span>
              </li>
<?php } ?>
            </ul>
<?php } ?>
            <div class="ud-more"><a class="dv-btn ghost" href="currentwork" target="_blank">Show more <i class="fa fa-arrow-right"></i></a></div>
          </aside>
        </div>
<?php } else { ?>
<?php if ($no_target) { ?>
        <div class="dv-card dv-empty ud-wide"><i class="fa fa-bullseye"></i><b>No target assigned to you</b></div>
<?php } else { ?>
        <div class="dv-card dv-empty ud-wide"><i class="fa fa-user-circle-o"></i><b>Pick <?php echo $td_self ? 'a department' : 'an employee and department'; ?></b><span>Then press <b>Search</b> to see the target report. Choose <b>All departments</b> for the overall performance.</span></div>
<?php } ?>
<?php } ?>

      </div>

      <?php echo footer(); ?>
    </div>

    <?php echo scripts(); ?>
    <script src="dv-datepicker.js?v=1"></script>
    <script>
    $(function () {
      dvInitPicker();
      $('#user_id').on('change', function () {
        var u = $(this).val();
        if (u !== '') {
          $.post('get_user_departments.php', { user_id: u }, function (h) {
            $('#dept_id').html(h).find('option:first').after('<option value="all">All departments (overall)</option>');
          });
        } else { $('#dept_id').html('<option value="">Select Department</option>'); }
      });
    });
    </script>
    <?php mu_foot(); ?>
  </body>
</html>
