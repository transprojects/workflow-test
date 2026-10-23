<?php include "includes/includes.php";
include "includes/modern-ui.php";

/* ============================================================================
 * My dashboard - my worked details for a date range + team article status.
 *
 * Before: one `users` lookup per worked row and two lookups per team article.
 * Now:    one query for the worked rows (user name read once), one for the team
 *         list and one for the status of all of those articles together.
 * ========================================================================== */

function db_e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function db_dur($s)
{
	$s = max(0, (int)$s);
	return sprintf('%02d:%02d:%02d', floor($s / 3600), floor(($s % 3600) / 60), $s % 60);
}
/* tone of an article's latest status: ok = worked / handed on, info = being worked, bad = not started */
function db_tone($st)
{
	$st = strtolower(trim((string)$st));
	if ($st === '') { return 'bad'; }
	if (in_array($st, array('completed', 'correction done', 'to cross check', 'cross checking done'), true)) { return 'ok'; }
	return 'info';
}
function db_esc($db, $v) { return $db->real_escape_string((string)$v); }

$user_id = (int)(isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : 0);
$dept    = (int)(isset($_COOKIE['department_id']) ? $_COOKIE['department_id'] : 0);
$is_fig  = ($dept === 9);

/* ---- date range (defaults to today) ---- */
$from = !empty($_REQUEST['fdate']) ? strtotime($_REQUEST['fdate']) : (!empty($_POST['userfromDate']) ? strtotime($_POST['userfromDate']) : false);
$to   = !empty($_REQUEST['tdate']) ? strtotime($_REQUEST['tdate']) : (!empty($_POST['userendDate'])  ? strtotime($_POST['userendDate'])  : false);
$from = $from ? $from : strtotime('today');
$to   = $to   ? $to   : $from;
if ($to < $from) { $t = $from; $from = $to; $to = $t; }
$from_d = date('Y-m-d', $from);
$to_d   = date('Y-m-d', $to);
$days   = (int)round(($to - $from) / 86400) + 1;
$to_next = date('Y-m-d', strtotime('+1 day', $to));

$me = $db->query("SELECT full_name FROM users WHERE id = ".$user_id." LIMIT 1");
$me = $me ? $me->fetch_assoc() : null;
$my_name = $me ? $me['full_name'] : '';

/* ---- worked details ---- */
$work = array(); $n_done = 0; $n_prog = 0; $secs_total = 0; $articles = array();
$q = $db->query("SELECT t.stage, t.current_status, t.start_time, t.end_time,
                        IF(t.end_time IS NULL, NULL, TIMESTAMPDIFF(SECOND, t.start_time, t.end_time)) AS secs,
                        d.pub_id, d.page_count, d.fig
                 FROM inw_transactions t
                 JOIN inw_inward_dtl d ON d.id = t.project_id
                 WHERE t.process_user = '".$user_id."'
                   AND t.current_status NOT IN ('Take Over')
                   AND t.start_time >= '".$from_d."' AND t.start_time < '".$to_next."'
                 ORDER BY t.start_time DESC");
while ($q && ($r = $q->fetch_assoc())) {
	$work[] = $r; $articles[$r['pub_id']] = true;
	$st = strtolower($r['current_status']);
	if ($st === 'completed') { $n_done++; } elseif ($st === 'inprogress') { $n_prog++; }
	if ($r['secs'] !== null) { $secs_total += (int)$r['secs']; }
}

/* ---- team article status ---- */
$cust  = db_esc($db, isset($_COOKIE['cust_id']) ? $_COOKIE['cust_id'] : '');
$ocust = db_esc($db, isset($_COOKIE['ocust_id']) ? $_COOKIE['ocust_id'] : '');
$extra = $is_fig ? "AND (wd.assign_department_id = '9' OR wd.fig > 0)" : '';
$team = array();
$q = $db->query("SELECT wd.id, wd.pub_id, wd.due_dt
                 FROM inw_inward_dtl wd
                 JOIN adm_customer_master c ON c.id = wd.cust_id
                 JOIN adm_journals j ON j.j_id = wd.j_id
                 WHERE wd.approved_by > 0
                   AND (wd.cust_id = '".$cust."' OR FIND_IN_SET(wd.cust_id, '".$ocust."'))
                   AND (wd.fig_complete_status = 0 OR wd.department_id = '".$dept."')
                   ".$extra."
                   AND wd.status NOT IN ('Delivery','Client Review','Completed','Query','Hold')
                   AND wd.v_issue_id <= 0 AND wd.test_article != 1
                 ORDER BY wd.due_dt ASC LIMIT 10");
while ($q && ($r = $q->fetch_assoc())) { $team[$r['id']] = $r; $team[$r['id']]['cs'] = ''; }
if ($team) {
	/* latest transaction of each article (own department only for the figure team) */
	$scope = $is_fig ? "AND dept = '".$dept."'" : '';
	$q = $db->query("SELECT t.project_id, t.current_status
	                 FROM inw_transactions t
	                 JOIN (SELECT project_id, MAX(id) AS mid FROM inw_transactions
	                       WHERE project_id IN (".implode(',', array_keys($team)).") ".$scope."
	                       GROUP BY project_id) m ON m.mid = t.id");
	while ($q && ($r = $q->fetch_assoc())) { $team[$r['project_id']]['cs'] = $r['current_status']; }
}
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
    <link href="user-dashboard-v2.css?v=20261003e" rel="stylesheet" />
  </head>

  <body>
    <div class="wholeouter">
    <?php include("includes/top-header.php"); ?>

      <div class="dv ud">

        <header class="dv-bar">
          <div class="dv-title">
            <span class="dv-eyebrow">Dashboard</span>
            <h1>Hello, <?php echo db_e($my_name !== '' ? $my_name : 'there'); ?></h1>
            <span class="dv-range"><i class="fa fa-calendar-o"></i> Your work from <?php echo date('d M Y', $from); ?><?php echo $from_d !== $to_d ? ' &ndash; '.date('d M Y', $to).' &middot; '.$days.' days' : ''; ?></span>
          </div>

          <form method="get" name="search_target" action="mydashboard" class="dv-actions">
            <div class="dv-filter">
              <button type="button" id="openDatepicker" class="dv-btn ghost"><i class="fa fa-calendar"></i><span id="dateDisplay"><?php echo $from_d === $to_d ? date('j M Y', $from) : date('j M', $from).' – '.date('j M Y', $to); ?></span><i class="fa fa-angle-down ud-caret"></i></button>
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
                  <input type="hidden" name="fdate" id="fromDate" value="<?php echo $from_d; ?>" />
                  <input type="hidden" name="tdate" id="toDate" value="<?php echo $to_d; ?>" />
                  <button type="button" id="dvApply" class="dv-btn go">Use this range</button>
                </div>
              </div>
            </div>
            <button class="dv-btn go ud-search" type="submit"><i class="fa fa-search"></i><span>Search</span></button>
          </form>
        </header>

        <section class="dv-kpis ud-kpis db-kpis">
          <article class="dv-card dv-k dv-k-total">
            <span class="dv-k-label">Entries worked</span>
            <b class="dv-k-num" data-count="<?php echo count($work); ?>"><?php echo count($work); ?></b>
            <div class="dv-split"><i style="flex:<?php echo max(0.0001, $n_done); ?>"></i><u style="flex:<?php echo max(0.0001, count($work) - $n_done); ?>;background:rgba(255,255,255,.28)"></u></div>
            <div class="dv-split-lbl"><span>Completed <strong><?php echo $n_done; ?></strong></span><span>Other <strong><?php echo count($work) - $n_done; ?></strong></span></div>
          </article>
          <article class="dv-card dv-k ud-k ud-blue"><i class="fa fa-file-text-o"></i><div><span class="dv-k-label">Articles</span><b class="dv-k-num sm" data-count="<?php echo count($articles); ?>"><?php echo count($articles); ?></b></div></article>
          <article class="dv-card dv-k ud-k ud-green"><i class="fa fa-check-circle"></i><div><span class="dv-k-label">Completed</span><b class="dv-k-num sm" data-count="<?php echo $n_done; ?>"><?php echo $n_done; ?></b></div></article>
          <article class="dv-card dv-k ud-k ud-accent"><i class="fa fa-spinner"></i><div><span class="dv-k-label">In progress</span><b class="dv-k-num sm" data-count="<?php echo $n_prog; ?>"><?php echo $n_prog; ?></b></div></article>
          <article class="dv-card dv-k ud-k ud-red"><i class="fa fa-hourglass-half"></i><div><span class="dv-k-label">Time spent</span><b class="dv-k-num sm"><?php echo db_dur($secs_total); ?></b></div></article>
        </section>

        <div class="ud-grid">
          <section class="dv-card ud-panel">
            <div class="dv-card-head"><h2>Worked details</h2><span class="dv-hint"><?php echo count($work); ?> entr<?php echo count($work) === 1 ? 'y' : 'ies'; ?></span></div>
            <div class="dv-scroll dv-tablewrap">
              <table id="myTable1" class="table custtable dv-table" style="width:100%">
                <thead><tr>
                  <th>Article</th><th>Stage</th><th>Start</th><th>End</th>
                  <th><?php echo $is_fig ? 'Fig count' : 'Pages'; ?></th><th>Time spent</th><th>Status</th>
                </tr></thead>
                <tbody>
<?php foreach ($work as $r) {
	$tone = db_tone($r['current_status']);
	$label = strtolower($r['current_status']) === 'inprogress' ? 'In progress' : $r['current_status'];
?>
                  <tr>
                    <td><b><?php echo db_e($r['pub_id']); ?></b></td>
                    <td><span class="dv-chip soft"><?php echo db_e($r['stage']); ?></span></td>
                    <td data-order="<?php echo strtotime($r['start_time']); ?>"><?php echo date('d-m-Y', strtotime($r['start_time'])); ?> <small class="db-time"><?php echo date('h:i a', strtotime($r['start_time'])); ?></small></td>
                    <td data-order="<?php echo $r['end_time'] ? strtotime($r['end_time']) : 0; ?>"><?php echo $r['end_time'] ? date('d-m-Y', strtotime($r['end_time'])).' <small class="db-time">'.date('h:i a', strtotime($r['end_time'])).'</small>' : '<span class="db-none">&ndash;</span>'; ?></td>
                    <td><?php echo (int)($is_fig ? $r['fig'] : $r['page_count']); ?></td>
                    <td data-order="<?php echo (int)$r['secs']; ?>"><?php echo $r['secs'] !== null ? '<i class="fa fa-clock-o db-clock"></i> '.db_dur($r['secs']) : '<span class="db-none">&ndash;</span>'; ?></td>
                    <td><span class="ud-pill <?php echo $tone; ?>"><?php echo db_e($label); ?></span></td>
                  </tr>
<?php } ?>
                </tbody>
              </table>
            </div>
          </section>

          <aside class="dv-card ud-panel ud-alloc">
<?php
$tl = array('ok' => 0, 'info' => 0, 'bad' => 0);
foreach ($team as $a) { $tl[db_tone($a['cs'])]++; }
$tl_total = count($team);
?>
            <div class="dv-card-head"><h2>Team article status</h2><span class="dv-hint"><?php echo $tl_total; ?> open</span></div>
<?php if (!$team) { ?>
            <div class="dv-empty"><i class="fa fa-inbox"></i><b>No open articles</b><span>Nothing is waiting for your team right now.</span></div>
<?php } else { ?>
            <div class="ud-al-sum">
              <div class="ud-al-bar"><i class="ok" style="flex:<?php echo max(0.0001, $tl['ok']); ?>"></i><i class="info" style="flex:<?php echo max(0.0001, $tl['info']); ?>"></i><i class="bad" style="flex:<?php echo max(0.0001, $tl['bad']); ?>"></i></div>
              <div class="ud-al-key">
                <span><em class="ok"></em>Done <b><?php echo $tl['ok']; ?></b></span>
                <span><em class="info"></em>In progress <b><?php echo $tl['info']; ?></b></span>
                <span><em class="bad"></em>Not started <b><?php echo $tl['bad']; ?></b></span>
              </div>
            </div>
            <ul class="ud-list">
<?php foreach ($team as $a) {
	$cs = trim($a['cs']);
	$tone = db_tone($cs);
	$label = $cs === '' ? 'Not started' : ($cs === 'Inprogress' ? 'In progress' : $cs);
	$due = !empty($a['due_dt']) && $a['due_dt'] !== '0000-00-00' && $a['due_dt'] !== '0000-00-00 00:00:00' ? date('d M Y', strtotime($a['due_dt'])) : '';
	$late = $due && $tone !== 'ok' && strtotime($a['due_dt']) < strtotime('today');
?>
              <li>
                <div class="ud-al-main"><b><?php echo db_e($a['pub_id']); ?></b><small><?php echo $due ? 'Due '.$due : 'No due date'; ?><?php echo $late ? ' <em class="late">overdue</em>' : ''; ?></small></div>
                <span class="ud-pill <?php echo $tone; ?>"><?php echo db_e($label); ?></span>
              </li>
<?php } ?>
            </ul>
<?php } ?>
            <div class="ud-more"><a class="dv-btn ghost" href="currentwork" target="_blank">Show more <i class="fa fa-arrow-right"></i></a></div>
          </aside>
        </div>

      </div>

      <?php echo footer(); ?>
    </div>

    <?php echo scripts(); ?>
    <script src="dv-datepicker.js?v=1"></script>
    <script>
      $(function () {
        dvInitPicker();
        $('#myTable1').DataTable({
          searching: true, pageLength: 10, order: [[2, 'desc']],
          lengthMenu: [[5, 10, 25, 50, 100, -1], [5, 10, 25, 50, 100, "All"]],
          dom: 'Bfltip',
          buttons: [{ extend: 'collection', text: 'Export', buttons: ['copy', 'csv', 'excel', 'pdf', 'print'] }],
          stripeClasses: [],
          language: { search: '', searchPlaceholder: 'Search articles…', lengthMenu: 'Show _MENU_' }
        });
        if (window.muBoot) { window.muBoot(document); }
      });
    </script>
    <?php mu_foot(); ?>
  </body>
</html>
