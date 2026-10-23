<?php include "includes/includes.php";
include "includes/modern-ui.php";

/* ============================================================================
 * My report - daily / monthly summary for the logged-in user.
 *
 * The old page ran two queries per calendar day (department lookup + user_report
 * row).  Now: one query for the department, one for the whole date range.
 * ========================================================================== */

function mr_e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$user_id = (int)(isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : 0);
$dep_id  = (int)(isset($_COOKIE['department_id']) ? $_COOKIE['department_id'] : 0);
$mode    = (isset($_REQUEST['date_wise']) && $_REQUEST['date_wise'] === 'm') ? 'm' : 'd';
$submitted = isset($_REQUEST['mfrom_dt']) || isset($_REQUEST['from_dt']);

$from_val  = !empty($_REQUEST['from_dt'])  ? $_REQUEST['from_dt']  : date('d-m-Y', strtotime('-5 days'));
$to_val    = !empty($_REQUEST['to_dt'])    ? $_REQUEST['to_dt']    : date('d-m-Y', strtotime('+2 days'));
$month_val = !empty($_REQUEST['mfrom_dt']) ? $_REQUEST['mfrom_dt'] : date('m-Y');

$me = $db->query("SELECT full_name FROM users WHERE id = ".$user_id." LIMIT 1");
$me = $me ? $me->fetch_assoc() : null;
$my_name = $me ? $me['full_name'] : '';

$dep = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$dep_id." LIMIT 1");
$dep = $dep ? $dep->fetch_assoc() : null;
$dep_name = $dep ? $dep['dept_name'] : '';

$days = array(); $tot = array('assigned' => 0, 'completed_total' => 0, 'completed_fp' => 0, 'completed_rev' => 0,
                              'completed_issu' => 0, 'completed_final' => 0, 'pending' => 0, 'wip' => 0);
$range_label = '';
if ($submitted) {
	if ($mode === 'm') {
		$first = strtotime('01-'.$month_val);
		$first = $first ? $first : strtotime(date('Y-m-01'));
		$s_ts  = strtotime(date('Y-m-01', $first));
		$e_ts  = strtotime(date('Y-m-t', $first));
	} else {
		$s_ts = strtotime($from_val); $e_ts = strtotime($to_val);
		if (!$s_ts) { $s_ts = strtotime('-5 days'); }
		if (!$e_ts) { $e_ts = $s_ts; }
		if ($e_ts < $s_ts) { $e_ts = $s_ts; }
		if ($e_ts - $s_ts > 366 * 86400) { $e_ts = $s_ts + 366 * 86400; }   // keep a runaway range cheap
	}
	$s = date('Y-m-d', $s_ts); $e = date('Y-m-d', $e_ts);
	$range_label = date('d M Y', $s_ts).' &ndash; '.date('d M Y', $e_ts);

	$by_date = array();
	$q = $db->query("SELECT `date`, assigned, completed_total, completed_fp, completed_rev, completed_issu, completed_final, pending, wip
	                 FROM user_report
	                 WHERE user_id = ".$user_id." AND dept_code = ".$dep_id." AND `date` BETWEEN '".$s."' AND '".$e."'
	                 ORDER BY id ASC");
	while ($q && ($r = $q->fetch_assoc())) {
		if (!isset($by_date[$r['date']])) { $by_date[$r['date']] = $r; }   // first row per day, as before
	}
	for ($ts = $s_ts; $ts <= $e_ts; $ts = strtotime('+1 day', $ts)) {
		$d = date('Y-m-d', $ts);
		$row = isset($by_date[$d]) ? $by_date[$d] : array_fill_keys(array_keys($tot), 0);
		foreach ($tot as $k => $_) { $tot[$k] += (int)$row[$k]; }
		$days[] = array('d' => $d, 'ts' => $ts, 'r' => $row);
	}
}
$link = "user-report-details.php?user_id=".$user_id."&dep_id=".$dep_id."&depcode=".urlencode($dep_name);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php mu_head(); ?>
  </head>

  <body>
    <div class="wholeouter">
    <?php include("includes/top-header.php"); ?>

      <div class="mu-root">

        <div class="mu-hero">
          <div>
            <small>My report</small>
            <h1><?php echo mr_e($my_name !== '' ? $my_name : 'My report'); ?></h1>
            <p><?php echo mr_e($dep_name); ?><?php echo $range_label !== '' ? ' &nbsp;&middot;&nbsp; '.$range_label : ''; ?></p>
          </div>
          <div class="mu-hero-right">
            <div class="mu-chip"><small>Today</small><b><?php echo date('d M Y'); ?></b></div>
          </div>
        </div>

        <form action="#" id="validation-form" method="post" autocomplete="off">
          <input type="hidden" name="filter" id="filter" value="1">
          <section class="mu-panel mu-filter-panel mu-reveal">
            <div class="mu-filter">
              <div class="mu-field">
                <label>View by</label>
                <div class="mu-seg">
                  <label><input type="radio" name="date_wise" id="daywise" value="d" <?php echo $mode === 'd' ? 'checked' : ''; ?>><span>Day</span></label>
                  <label><input type="radio" name="date_wise" id="monthwise" value="m" <?php echo $mode === 'm' ? 'checked' : ''; ?>><span>Month</span></label>
                </div>
              </div>

              <div class="mu-field day-f"><label for="from_dt">From date</label>
                <input type="text" id="from_dt" name="from_dt" class="monthlyinputdate" value="<?php echo mr_e($from_val); ?>" readonly></div>
              <div class="mu-field day-f"><label for="to_dt">To date</label>
                <input type="text" id="to_dt" name="to_dt" class="monthlyinputdate1" value="<?php echo mr_e($to_val); ?>"></div>
              <div class="mu-field month-f"><label for="mfrom_dt">Month</label>
                <input type="text" id="mfrom_dt" name="mfrom_dt" class="monthlyinputdate2" placeholder="Select month" value="<?php echo mr_e($month_val); ?>"></div>

              <div class="mu-field" style="flex-direction:row;gap:10px;">
                <button class="mu-btn primary" type="submit" name="getaction"><i class="fa fa-search"></i> Get result</button>
                <a class="mu-btn" href="my-report.php" title="Reset"><i class="fa fa-refresh"></i></a>
              </div>
            </div>
          </section>
        </form>

<?php if ($submitted) { ?>
        <div class="mu-stats">
          <div class="mu-stat info mu-reveal"><i class="fa fa-inbox"></i><div><span>Assigned</span><b data-count="<?php echo $tot['assigned']; ?>">0</b></div></div>
          <div class="mu-stat ok mu-reveal"><i class="fa fa-check-circle"></i><div><span>Completed</span><b data-count="<?php echo $tot['completed_total']; ?>">0</b></div></div>
          <div class="mu-stat accent mu-reveal"><i class="fa fa-cogs"></i><div><span>WIP</span><b data-count="<?php echo $tot['wip']; ?>">0</b></div></div>
          <div class="mu-stat warn mu-reveal"><i class="fa fa-hourglass-half"></i><div><span>Pending</span><b data-count="<?php echo $tot['pending']; ?>">0</b></div></div>
        </div>

        <section class="mu-panel mu-reveal">
          <h3><i class="fa fa-calendar"></i> Daily summary <span class="mu-right"><?php echo count($days); ?> days</span></h3>
          <div class="mu-table-wrap">
            <table class="mu-table">
              <thead><tr><th class="c">#</th><th>Date</th><th class="c">Assigned</th><th>Completed</th><th class="c">Pending</th><th class="c">WIP</th></tr></thead>
              <tbody>
<?php foreach ($days as $i => $day) {
	$r = $day['r']; $dow = date('D', $day['ts']);
	$cls = ($dow === 'Sat' || $dow === 'Sun') ? 'mu-off' : '';
	if ($day['d'] === date('Y-m-d')) { $cls = 'mu-today'; }
	$qs = "&sdate=".$day['d'];
	$parts = array('FP' => array('completed_fp', '#1e9e61'), 'REV' => array('completed_rev', '#d64545'),
	               'ISSUE' => array('completed_issu', '#d98a06'), 'Final' => array('completed_final', '#2a7fc1'));
	$ct = max(1, (int)$r['completed_total']);
?>
                <tr class="<?php echo $cls; ?>">
                  <td class="c"><?php echo $i + 1; ?></td>
                  <td class="nw"><b><?php echo date('d M', $day['ts']); ?></b> <small>(<?php echo $dow; ?>)</small></td>
                  <td class="c"><?php echo $r['assigned'] > 0 ? '<a class="mu-num" target="_blank" href="'.$link.$qs.'&status=A">'.(int)$r['assigned'].'</a>' : '<span class="mu-zero">0</span>'; ?></td>
                  <td>
<?php if ($r['completed_total'] > 0) { ?>
                    <a class="mu-num ok" target="_blank" href="<?php echo $link.$qs; ?>&status=C"><?php echo (int)$r['completed_total']; ?></a>
                    <div class="mu-split"><?php foreach ($parts as $lbl => $p) { if ($r[$p[0]] > 0) { echo '<i title="'.$lbl.': '.(int)$r[$p[0]].'" style="width:'.round($r[$p[0]] / $ct * 100, 1).'%;background:'.$p[1].'"></i>'; } } ?></div>
                    <div class="mu-legend"><?php foreach ($parts as $lbl => $p) { if ($r[$p[0]] > 0) { echo '<span><b style="color:'.$p[1].'">'.$lbl.' '.(int)$r[$p[0]].'</b></span>'; } } ?></div>
<?php } else { echo '<span class="mu-zero">0</span>'; } ?>
                  </td>
                  <td class="c"><?php echo $r['pending'] > 0 ? '<span class="mu-pill warn">'.(int)$r['pending'].'</span>' : '<span class="mu-zero">0</span>'; ?></td>
                  <td class="c"><?php echo $r['wip'] > 0 ? '<a class="mu-num wip" target="_blank" href="'.$link.$qs.'&status=W">'.(int)$r['wip'].'</a>' : '<span class="mu-zero">0</span>'; ?></td>
                </tr>
<?php } ?>
              </tbody>
              <tfoot><tr><td></td><td>Total</td><td class="c"><?php echo $tot['assigned']; ?></td>
                <td><?php echo $tot['completed_total']; ?> <small>(FP <?php echo $tot['completed_fp']; ?> &middot; REV <?php echo $tot['completed_rev']; ?> &middot; ISSUE <?php echo $tot['completed_issu']; ?> &middot; Final <?php echo $tot['completed_final']; ?>)</small></td>
                <td class="c"><?php echo $tot['pending']; ?></td><td class="c"><?php echo $tot['wip']; ?></td></tr></tfoot>
            </table>
          </div>
        </section>

        <div id="article_list"></div>
        <div id="loader"></div>
<?php } else { ?>
        <div class="mu-empty mu-reveal"><i class="fa fa-calendar-check-o"></i>Pick a date range or month and press <b>Get result</b>.</div>
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

      /* show only the inputs that belong to the chosen mode */
      function sync() {
        var m = $('input[name=date_wise]:checked').val() === 'm';
        $('.month-f').toggle(m); $('.day-f').toggle(!m);
      }
      $('input[name=date_wise]').on('change', function () {
        if ($(this).val() === 'm') { $('#mfrom_dt').val(('0' + (today.getMonth() + 1)).slice(-2) + '-' + today.getFullYear()); }
        sync();
      });
      sync();

      $(".monthlyinputdate").datepicker({ dateFormat: "dd-mm-yy", onSelect: function (t) { $(".monthlyinputdate1").datepicker("option", "minDate", t); } });
      $(".monthlyinputdate1").datepicker({ dateFormat: "dd-mm-yy", onSelect: function (t) { $(".monthlyinputdate").datepicker("option", "maxDate", t); } });
      $(".monthlyinputdate2").datepicker({
        dateFormat: "mm-yy", changeMonth: true, changeYear: true, showButtonPanel: true,
        beforeShow: function () { $(this).datepicker('widget').addClass('mu-month-only'); },
        onClose: function () {
          var m = $("#ui-datepicker-div .ui-datepicker-month :selected").val(), y = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
          $(this).datepicker('setDate', new Date(y, m, 1));
        }
      });
    });
    </script>
    <style>.mu-month-only .ui-datepicker-calendar{display:none;}</style>
    <?php mu_foot(); ?>
  </body>
</html>
