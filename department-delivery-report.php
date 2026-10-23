<?php include "includes/includes.php";
include "includes/modern-ui.php";
include ('includes/paginate.php'); 
?>
<?php
//echo $_SESSION['cust_id'];
if(isset($_REQUEST['id']) && $_REQUEST['id']!='' && $_REQUEST['action']=='delete')
{	
	$db->query("delete from inw_inward_dtl where id='".$_REQUEST['id']."'");
	header("location:added_articles.php?msg=Successfully deleted");
	die();	
	
}
function differenceInHours($startdate,$enddate){
	/*$starttimestamp = strtotime($startdate);
	$endtimestamp = strtotime($enddate);
	$difference = abs($endtimestamp - $starttimestamp)/3600;
	return round($difference,2);*/
	
	$datetime1 = new DateTime($startdate);
	$datetime2 = new DateTime($enddate);
	$interval = $datetime1->diff($datetime2);
	echo $interval->format('%h')." Hours ".$interval->format('%i')." Minutes ".$interval->format('%s')." Seconds";
}

if(isset($_REQUEST['getaction']) && $_REQUEST['getaction']!='')
{
	
$_SESSION['cuser_id'] = $_POST['user_id'];
$_SESSION['fcust_id'] = $_POST['cust_id'];
$_SESSION['stage_id'] = $_POST['stage_id'];
$_SESSION['j_id'] = $_POST['j_id'];
$_SESSION['pub_id'] = $_POST['pub_id'];
$_SESSION['from_dt'] = $_POST['from_dt'];
$_SESSION['to_dt'] = $_POST['to_dt'];
$_SESSION['dep_id']=$_REQUEST['dep_id'];

$var = '';
if($_SESSION['fcust_id']!=''){
	
     $var.= "and c.id = '".$_SESSION['fcust_id']."' ";
}
if($_SESSION['stage_id']!=''){
	
      $var.= "and wd.stage LIKE '%".$_SESSION['stage_id']."%' ";
}
if($_SESSION['j_id']!=''){
	
     $var.= "and j.j_id = '".$_SESSION['j_id']."' ";
}
if($_SESSION['pub_id']!=''){
	
     $var.= "and pub_id LIKE '%".$_SESSION['pub_id']."%' ";
}
if($_SESSION['cuser_id']!=''){
	
     $var.= "and inw.process_user= '".$_SESSION['cuser_id']."' ";
}

	$_SESSION['varname'] = $var;


	
	}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php mu_head(); ?>
    <style>.mu-table td,.mu-table th{white-space:nowrap;}.mu-table{min-width:0}
.mu-chart{position:relative;min-height:260px}
.mu-two{display:grid;grid-template-columns:repeat(auto-fit,minmax(380px,1fr));gap:16px}
.modal-content{border-radius:14px !important;box-shadow:0 20px 50px rgba(20,48,74,.35) !important;border:1px solid var(--line)}
.modal-content h3{color:var(--navy);font-size:15px;text-transform:uppercase;letter-spacing:.6px;margin-bottom:12px}
.modal-content th{background:#eef3f8 !important;color:var(--navy);font-size:12px;text-transform:uppercase;letter-spacing:.5px;border:0 !important;border-bottom:2px solid #d5e1ed !important}
.modal-content td{border:0 !important;border-bottom:1px solid #eef1f5 !important}
.modal-content button{background:linear-gradient(135deg,var(--accent),#f08a45) !important;border-radius:9px !important;padding:9px 22px !important;font-weight:600}</style>	

<style>
  .monthlytable th,
    .monthlytable td {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
.modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
}

/* Center the modal */
.modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    width: 40%;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    animation: fadeIn 0.3s ease-in-out;
}

/* Table inside modal */
.modal-content table {
    width: 100%;
    border-collapse: collapse;
}

.modal-content th,
.modal-content td {
    padding: 10px;
    border: 1px solid #ccc;
    text-align: left;
}

.modal-content th {
    background-color: #f2f2f2;
}

/* Close button */
.modal-content button {
    margin-top: 15px;
    padding: 8px 15px;
    background-color: #007BFF;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.modal-content button:hover {
    background-color: #0056b3;
}

/* Fade animation */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}


</style>

  </head>

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
	 
      <!-- Header logo section -->
      
      <!-- report section body starts -->
      <div class="mu-root">
        <div class="mu-hero">
          <div><small>Reports</small><h1>Department Delivery Performance</h1>
            <p>Expected vs delivered pages per day, with absentee impact</p></div>
        </div>
        <form action="#" id="validation-form" method="post" autocomplete="off">
          <section class="mu-panel mu-filter-panel mu-reveal">
            <div class="mu-filter">
              <div class="mu-field"><label>View by</label>
                <div class="mu-seg">
                  <label><input type="radio" name="date_wise" id="daywise" value="d" <?php if (($_REQUEST['date_wise'] ?? '') != 'm') { ?> checked <?php } ?>><span>Day</span></label>
                  <label><input type="radio" name="date_wise" id="monthwise" value="m" <?php if (($_REQUEST['date_wise'] ?? '') == 'm') { ?> checked <?php } ?>><span>Month</span></label>
                </div></div>
              <div class="mu-field" style="min-width:240px;"><label for="dep_id">Department</label>
                <select name="dep_id" id="dep_id" required>
<?php
    $query = "SELECT dept_name,dept_code FROM `adm_dept_master`  ORDER BY `dept_name` ASC ";
    $customers = $db->query($query) or ($db->error);
?>
                  <option value="">-Select-</option>
<?php while($row = $customers->fetch_assoc()){ ?>
                  <option value="<?php echo $row['dept_code']; ?>" <?php if(($_REQUEST['dep_id'] ?? '') ==$row['dept_code']){?> selected <?php } ?>><?php echo $row['dept_name']; ?></option>
<?php } ?>
                </select></div>
              <div id="day_div" class="mu-field" style="display:<?php echo (($_REQUEST['date_wise'] ?? '') != 'm') ? 'block' : 'none'; ?>;min-width:250px;"><label>Date range</label>
                <div class="dvr" data-from-name="from_dt" data-to-name="to_dt" data-from="<?php echo ($_REQUEST['from_dt'] ?? '') != '' ? $_REQUEST['from_dt'] : date('d-m-Y', strtotime('-5 days')); ?>" data-to="<?php echo ($_REQUEST['to_dt'] ?? '') != '' ? $_REQUEST['to_dt'] : date('d-m-Y', strtotime('+2 days')); ?>"></div></div>
              <div id="month_div" class="mu-field" style="display:<?php echo (($_REQUEST['date_wise'] ?? '') == 'm') ? 'flex' : 'none'; ?>;">
                <label for="mfrom_dt">Month</label>
                <input type="text" id="mfrom_dt" name="mfrom_dt" placeholder="Select Month" class="monthlyinputdate2" value="<?php echo ($_REQUEST['mfrom_dt'] ?? '') != '' ? $_REQUEST['mfrom_dt'] : date('m-Y'); ?>" /></div>
              <div class="mu-field" style="flex-direction:row;gap:10px;">
                <input type="hidden" name="filter" id="filter" value="1">
                <button class="mu-btn primary" type="submit" name="getaction"><i class="fa fa-bolt"></i> Get Result</button>
                <a class="mu-btn" href="ddcapacityreport" title="Reset"><i class="fa fa-refresh"></i></a>
              </div>
            </div>
          </section>
        </form>


        <!-- Table section -->
		<?php 
				//echo $var;
if($_POST['dep_id']!="")
{
$fds=date('d-m-Y', strtotime('-3 days'));
$tds=date('d-m-Y');
	if($_REQUEST['from_dt']!="")
{
$start_date =date('Y-m-d',strtotime($_REQUEST['from_dt']));
$end_date =date('Y-m-d',strtotime($_REQUEST['to_dt']));
}
if($_REQUEST['mfrom_dt']!="" && $_REQUEST['date_wise'] == 'm')
{
	//echo $_REQUEST['mfrom_dt'];
 $mstart_date =date('Y-m',strtotime("01-".$_REQUEST['mfrom_dt']));
$mend_date =date('Y-m',strtotime("30-".$_REQUEST['mfrom_dt']));	

$start_date =date('Y-m-01',strtotime($mstart_date));
 $end_date =date('Y-m-t',strtotime($mend_date));	
}

$dept_id = $_REQUEST['dep_id'];
$dept_res = mysqli_fetch_assoc(mysqli_query($db, "SELECT dept_name FROM adm_dept_master WHERE dept_code = '$dept_id'"));
$dept_name = $dept_res['dept_name'] ?? 'Unknown';
?>		
	
    <section class="mu-panel mu-reveal">
<h3><i class="fa fa-table"></i> Daily delivery <span class="mu-right"><?php echo htmlspecialchars($dept_name); ?></span></h3>
<div class="mu-table-wrap" style="border:0;">
<table class="mu-table">
    <thead>
        <tr>
            <th>S.no</th>
            <th>Expected Delivery</th>
            <th>Delivered Pages</th>
            <th>Absent</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $sno = 1;

        // Default date range
        $fds = date('Y-m-d', strtotime('-3 days'));
        $tds = date('Y-m-d');

        $start_date = $fds;
        $end_date = $tds;

        // Get from request
        if (!empty($_REQUEST['from_dt'])) {
            $start_date = date('Y-m-d', strtotime($_REQUEST['from_dt']));
            $end_date = date('Y-m-d', strtotime($_REQUEST['to_dt']));
        }

        if (!empty($_REQUEST['mfrom_dt']) && $_REQUEST['date_wise'] === 'm') {
            $mstart_date = date('Y-m', strtotime("01-" . $_REQUEST['mfrom_dt']));
            $mend_date = date('Y-m', strtotime("30-" . $_REQUEST['mfrom_dt']));    
            $start_date = date('Y-m-01', strtotime($mstart_date));
            $end_date = date('Y-m-t', strtotime($mend_date));    
        }

        // Department filter
        $dep_id = isset($_REQUEST['dep_id']) ? mysqli_real_escape_string($db, $_REQUEST['dep_id']) : '';

        // Calculate working days (excluding Sundays)
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $end->modify('+1 day');
        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($start, $interval, $end);

        $workingDays = 0;
        foreach ($dateRange as $date) {
            if ($date->format('w') != 0) { // Not Sunday
                $workingDays++;
            }
        }

        // Main Query
        $cnt_query = "
            SELECT 
                uta.dept_id,
                dm.dept_name AS `Department`,
                CASE 
                    WHEN uta.dept_id = 1 THEN 
                        CASE 
                            WHEN (COALESCE(SUM(uta.average_pr_pc), 0) + COALESCE(SUM(uta.average_pr_ppc), 0)) = 0 THEN NULL
                            ELSE ROUND(
                                (
                                    IF(SUM(uta.average_pr_pc) > 0, SUM(uta.average_pr_pc), 0) + 
                                    IF(SUM(uta.average_pr_ppc) > 0, SUM(uta.average_pr_ppc), 0)
                                ) / (
                                    IF(SUM(uta.average_pr_pc) > 0, 1, 0) + 
                                    IF(SUM(uta.average_pr_ppc) > 0, 1, 0)
                                ), 2
                            )
                        END
                    ELSE
                        CASE 
                            WHEN (
                                COALESCE(SUM(uta.average_pr_pc), 0) + COALESCE(SUM(uta.average_pr_cpc), 0) +
                                COALESCE(SUM(uta.average_pr_rpc), 0) + COALESCE(SUM(uta.average_pr_fpc), 0) +
                                COALESCE(SUM(uta.average_pr_ipc), 0)
                            ) = 0 THEN NULL
                            ELSE ROUND(
                                (
                                    IF(SUM(uta.average_pr_pc) > 0, SUM(uta.average_pr_pc), 0) +
                                    IF(SUM(uta.average_pr_cpc) > 0, SUM(uta.average_pr_cpc), 0) +
                                    IF(SUM(uta.average_pr_rpc) > 0, SUM(uta.average_pr_rpc), 0) +
                                    IF(SUM(uta.average_pr_fpc) > 0, SUM(uta.average_pr_fpc), 0) +
                                    IF(SUM(uta.average_pr_ipc) > 0, SUM(uta.average_pr_ipc), 0)
                                ) / (
                                    IF(SUM(uta.average_pr_pc) > 0, 1, 0) +
                                    IF(SUM(uta.average_pr_cpc) > 0, 1, 0) +
                                    IF(SUM(uta.average_pr_rpc) > 0, 1, 0) +
                                    IF(SUM(uta.average_pr_fpc) > 0, 1, 0) +
                                    IF(SUM(uta.average_pr_ipc) > 0, 1, 0)
                                ), 2
                            )
                        END
                END AS `Preferred Collaboration Avg`,
                COALESCE(SUM(uta_total.total_worked_pages), 0) AS `Total Worked Pages`
            FROM user_target_assign AS uta
            LEFT JOIN adm_dept_master AS dm ON dm.dept_code = uta.dept_id
			LEFT JOIN users AS u ON u.id = uta.user_id AND u.approved = 1
            LEFT JOIN (
                SELECT user_id, dept_id, SUM(total_worked_pages) AS total_worked_pages
                FROM user_target_analytics
                WHERE from_date BETWEEN '$start_date' AND '$end_date'
                GROUP BY user_id, dept_id
            ) AS uta_total ON uta_total.user_id = uta.user_id AND uta_total.dept_id = uta.dept_id
            WHERE u.id IS NOT NULL";

        if ($dep_id != '') {
            $cnt_query .= " AND uta.dept_id = '$dep_id'";
        }

        $cnt_query .= " GROUP BY uta.dept_id";

        $result = mysqli_query($db, $cnt_query);

        while ($row = mysqli_fetch_assoc($result)) {
			
			$labels = [];
			$expectedData = [];
			$workedData = [];
			$absentData = [];


            $expected_delivery = is_numeric($row['Preferred Collaboration Avg']) 
                ? round($row['Preferred Collaboration Avg'] * $workingDays, 2) 
                : 0;
				
				
				  // Repeat Absent Query block (or reuse $absent_sum if still in scope)
				 // Assuming $absent_sum is already calculated

    $labels[] = $row['Department'];
    $expectedData[] = $expected_delivery;
    $workedData[] = (float) $row['Total Worked Pages'];

            // Absent Users Query

$dept_id = (int)$row['dept_id']; // Make sure this is passed or looped dynamically

             $absent_users_query = "
WITH RECURSIVE date_range AS (
    SELECT DATE('$start_date') AS absent_date
    UNION ALL
    SELECT DATE_ADD(absent_date, INTERVAL 1 DAY)
    FROM date_range
    WHERE absent_date < DATE('$end_date')
),
filtered_dates AS (
    SELECT absent_date
    FROM date_range
    WHERE DAYOFWEEK(absent_date) != 1 -- Exclude Sundays
),
user_dates AS (
    SELECT 
        u.id AS user_id,
        u.full_name,
        uta.dept_id,
        d.absent_date
    FROM users u
    JOIN user_target_assign uta ON uta.user_id = u.id AND uta.dept_id = $dept_id
    JOIN filtered_dates d
    WHERE u.approved = 1
),
worked_users AS (
    SELECT user_id, dept_id, from_date
    FROM user_target_analytics
    WHERE dept_id = $dept_id AND total_worked_pages > 0
)
SELECT 
    ud.full_name,
    CASE 
        WHEN uta.dept_id = 1 THEN ROUND((uta.average_pr_pc + uta.average_pr_ppc)/2, 2)
        ELSE ROUND(( 
            IFNULL(uta.average_pr_pc, 0) + 
            IFNULL(uta.average_pr_cpc, 0) + 
            IFNULL(uta.average_pr_rpc, 0) + 
            IFNULL(uta.average_pr_fpc, 0) + 
            IFNULL(uta.average_pr_ipc, 0)
        ) / NULLIF((
            IF(uta.average_pr_pc > 0, 1, 0) +
            IF(uta.average_pr_cpc > 0, 1, 0) +
            IF(uta.average_pr_rpc > 0, 1, 0) +
            IF(uta.average_pr_fpc > 0, 1, 0) +
            IF(uta.average_pr_ipc > 0, 1, 0)
        ), 0), 2)
    END AS avg_per_day,
    GROUP_CONCAT(DATE_FORMAT(ud.absent_date, '%d-%m-%Y') ORDER BY ud.absent_date) AS leave_dates
FROM user_dates ud
JOIN user_target_assign uta ON uta.user_id = ud.user_id AND uta.dept_id = ud.dept_id
LEFT JOIN worked_users wu 
    ON wu.user_id = ud.user_id AND wu.dept_id = ud.dept_id AND wu.from_date = ud.absent_date
WHERE wu.user_id IS NULL
GROUP BY ud.user_id
ORDER BY ud.full_name";



            $absent_result = mysqli_query($db, $absent_users_query);
            $absent_sum = 0;
            $absent_names = [];
			$absent_user_rows = [];
			while ($abs = mysqli_fetch_assoc($absent_result)) {
				
				
				   

					$leave_dates = isset($abs['leave_dates']) ? explode(',', $abs['leave_dates']) : [];
					$leave_dates = array_filter($leave_dates); // Remove empty values
					$absent_days = count($leave_dates); // Count actual leave dates

					$avg = is_numeric($abs['avg_per_day']) ? floatval($abs['avg_per_day']) : 0;
					$absent_sum += $avg * $absent_days;

					$absent_names[] = $abs['full_name'];
					
						$absent_user_rows[] = [
						'name' => $abs['full_name'],
						'avg' => $avg,
						'days' => $absent_days ,// 👈 Add leave count separately
						'dates' => implode(',', $leave_dates)
						];
			}
					$absentData[] = round($absent_sum, 2); // Or use cached value




			$absent_display = $absent_sum > 0 ? "<a href='#' class='show-absent mu-num wip' data-users='" . json_encode($absent_user_rows) . "'>" . round($absent_sum, 2) . " </a>": "<span class='mu-zero'>-</span>";
            echo "<tr>
                <td>{$sno}</td>
                <td><b>{$expected_delivery}</b></td>
                <td>{$row['Total Worked Pages']}</td>
                <td>{$absent_display}</td>
            </tr>";

            $sno++;
        }
		
		echo "<script>
    const chartLabels = " . json_encode($labels) . ";
    const expectedPages = " . json_encode($expectedData) . ";
    const deliveredPages = " . json_encode($workedData) . ";
    const absentPages = " . json_encode($absentData) . ";
</script>";
        ?>
    </tbody>
</table>
</div>
</section>
<div class="mu-two">
  <section class="mu-panel mu-reveal"><h3><i class="fa fa-bar-chart"></i> Expected vs delivered</h3>
    <div class="mu-chart"><canvas id="deliveryChart"></canvas></div></section>
  <section class="mu-panel mu-reveal"><h3><i class="fa fa-line-chart"></i> Trend</h3>
    <div class="mu-chart"><canvas id="trendChart"></canvas></div></section>
</div>



<!-- Modal HTML -->
<div id="absentModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3 style="text-align:center;">Absent Users Detail</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 30%; text-align: left;">Name</th>
                    <th style="width: 5%; text-align: left;">Avg</th>
                    <th style="width: 5%; text-align: left;">No of Days</th>
                    <!-- <th style="width: 5%; text-align: left;">Leave Dates</th> -->
                </tr>
            </thead>
            <tbody id="absentDetailBody">
                <!-- Filled by JavaScript -->
            </tbody>
        </table>
        <div style="text-align: center; margin-top: 10px;">
            <button onclick="document.getElementById('absentModal').style.display='none';">Close</button>
        </div>
    </div>
</div>

<?php } else { ?>
<div class="mu-empty mu-reveal"><i class="fa fa-bar-chart"></i>Choose the period and department, then press <b>Get Result</b>.</div>
<?php } ?>
<?php
$department_name = $dept_name; // or fetch from result set
$dateWiseData = [];

$start = new DateTime($start_date);
$end = new DateTime($end_date);
$end->modify('+1 day');

$period = new DatePeriod($start, new DateInterval('P1D'), $end);

// Loop through each date and fetch data
foreach ($period as $date) {
    if ($date->format('w') == 0) continue; // skip Sundays
    $d = $date->format('Y-m-d');

    // Total Worked Pages
    $worked_result = mysqli_fetch_assoc(mysqli_query($db, "
        SELECT SUM(total_worked_pages) AS total_worked
        FROM user_target_analytics
        WHERE from_date = '$d' AND dept_id = '$dept_id'
    "));
    $worked_pages = $worked_result['total_worked'] ?? 0;

    // Expected delivery per day
    $avg_query = mysqli_fetch_assoc(mysqli_query($db, "
        SELECT 
                          CASE    WHEN dept_id = 1 THEN 
                        CASE 
                            WHEN (COALESCE(SUM(uta.average_pr_pc), 0) + COALESCE(SUM(uta.average_pr_ppc), 0)) = 0 THEN NULL
                            ELSE ROUND(
                                (
                                    IF(SUM(uta.average_pr_pc) > 0, SUM(uta.average_pr_pc), 0) + 
                                    IF(SUM(uta.average_pr_ppc) > 0, SUM(uta.average_pr_ppc), 0)
                                ) / (
                                    IF(SUM(uta.average_pr_pc) > 0, 1, 0) + 
                                    IF(SUM(uta.average_pr_ppc) > 0, 1, 0)
                                ), 2
                            )
                        END
                    ELSE
                        CASE 
                            WHEN (
                                COALESCE(SUM(uta.average_pr_pc), 0) + COALESCE(SUM(uta.average_pr_cpc), 0) +
                                COALESCE(SUM(uta.average_pr_rpc), 0) + COALESCE(SUM(uta.average_pr_fpc), 0) +
                                COALESCE(SUM(uta.average_pr_ipc), 0)
                            ) = 0 THEN NULL
                            ELSE ROUND(
                                (
                                    IF(SUM(uta.average_pr_pc) > 0, SUM(uta.average_pr_pc), 0) +
                                    IF(SUM(uta.average_pr_cpc) > 0, SUM(uta.average_pr_cpc), 0) +
                                    IF(SUM(uta.average_pr_rpc) > 0, SUM(uta.average_pr_rpc), 0) +
                                    IF(SUM(uta.average_pr_fpc) > 0, SUM(uta.average_pr_fpc), 0) +
                                    IF(SUM(uta.average_pr_ipc) > 0, SUM(uta.average_pr_ipc), 0)
                                ) / (
                                    IF(SUM(uta.average_pr_pc) > 0, 1, 0) +
                                    IF(SUM(uta.average_pr_cpc) > 0, 1, 0) +
                                    IF(SUM(uta.average_pr_rpc) > 0, 1, 0) +
                                    IF(SUM(uta.average_pr_fpc) > 0, 1, 0) +
                                    IF(SUM(uta.average_pr_ipc) > 0, 1, 0)
                                ), 2
                            )
                        END
                END AS avg_day
        FROM user_target_assign uta
		LEFT JOIN users AS u ON u.id = uta.user_id AND u.approved = 1
        WHERE dept_id = '$dept_id' AND u.id IS NOT NULL LIMIT 1
    "));
    $expected = $avg_query['avg_day'] ?? 0;

    // Absent loss
    $absent_result = mysqli_query($db, "
        SELECT 
            u.id AS user_id,
            u.full_name,
            CASE 
                WHEN uta.dept_id = 1 THEN ROUND((uta.average_pr_pc + uta.average_pr_ppc)/2, 2)
                ELSE ROUND(( 
                    IFNULL(uta.average_pr_pc, 0) + 
                    IFNULL(uta.average_pr_cpc, 0) + 
                    IFNULL(uta.average_pr_rpc, 0) + 
                    IFNULL(uta.average_pr_fpc, 0) + 
                    IFNULL(uta.average_pr_ipc, 0)
                ) / NULLIF(( 
                    IF(uta.average_pr_pc > 0, 1, 0) +
                    IF(uta.average_pr_cpc > 0, 1, 0) +
                    IF(uta.average_pr_rpc > 0, 1, 0) +
                    IF(uta.average_pr_fpc > 0, 1, 0) +
                    IF(uta.average_pr_ipc > 0, 1, 0)
                ), 0), 2)
            END AS avg_per_day
        FROM users u
        JOIN user_target_assign uta ON uta.user_id = u.id AND uta.dept_id = '$dept_id'
        WHERE u.approved = 1 AND NOT EXISTS (
            SELECT 1 FROM user_target_analytics ua
            WHERE ua.user_id = u.id AND ua.dept_id = '$dept_id' AND ua.from_date = '$d' AND ua.total_worked_pages > 0
        )
    ");
    $absent_loss = 0;
    while ($abs = mysqli_fetch_assoc($absent_result)) {
        $absent_loss += floatval($abs['avg_per_day']);
    }

    // Save per-day entry
    $dateWiseData[] = [
        'date' => $d,
        'expected' => round($expected, 2),
        'worked' => round($worked_pages, 2),
        'absent' => round($absent_loss, 2)
    ];
}
?>

        
      </div>
      <!-- Footer Section -->
     
  
	
	 <!-- Footer Section -->
     <?php echo footer(); ?>

    </div>
 



 
    



<!-- Footer Section -->
<?php echo scripts(); ?>	
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>



<script>
const trendData = <?php echo json_encode($dateWiseData); ?>;

const labels = trendData.map(row => row.date);
const expectedData = trendData.map(row => row.expected);
const workedData = trendData.map(row => row.worked);
const absentData = trendData.map(row => row.absent);

// Use a unique name
const trendCtx = document.getElementById('trendChart').getContext('2d');
const trendChart = new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Expected Delivery',
                data: expectedData,
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.4,
                fill: true
            },
            {
                label: 'Delivered Pages',
                data: workedData,
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.4,
                fill: true
            },
            {
                label: 'Absent Loss',
                data: absentData,
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                tension: 0.4,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Department-Wise Daily Performance Trend'
            },
            tooltip: {
                mode: 'index',
                intersect: false
            },
            legend: {
                position: 'bottom'
            }
        },
        interaction: {
            mode: 'nearest',
            axis: 'x',
            intersect: false
        },
        scales: {
            x: {
                title: {
                    display: true,
                    text: 'Date'
                }
            },
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Pages'
                }
            }
        }
    }
});
</script>

<script>
// Use a different variable name
const deliveryCtx = document.getElementById('deliveryChart').getContext('2d');
const deliveryChart = new Chart(deliveryCtx, {
    type: 'bar',
    data: {
        labels: chartLabels,
        datasets: [
            {
                label: 'Expected Delivery',
                data: expectedPages,
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            },
            {
                label: 'Delivered Pages',
                data: deliveredPages,
                backgroundColor: 'rgba(75, 192, 192, 0.7)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            },
            {
                label: 'Absent Loss',
                data: absentPages,
                backgroundColor: 'rgba(255, 99, 132, 0.7)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Department-wise Delivery Report'
            },
            tooltip: {
                mode: 'index',
                intersect: false
            },
            legend: {
                position: 'bottom'
            }
        },
        scales: {
            x: {
                title: {
                    display: true,
                    text: 'Department'
                }
            },
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Pages'
                }
            }
        }
    }
});
</script>


<!-- Date picker functionality -->
    <script>
        $(function () {
        // var datesForDisable = ["2017-10-14", "2017-10-15", "2017-10-16"];
        var date = new Date();
        date.setDate(date.getDate() - 1);
        var fromdate;
        $(".monthlyinputdate").datepicker({
          uiLibrary: "bootstrap5",
          dateFormat: "dd-mm-yy",
          autoclose: true,
         //  minDate: date,
              onSelect: function (dateText, inst) {
             fromdate = dateText;

                    $(".monthlyinputdate1").datepicker("option", "minDate", fromdate);
                }
        });

        $(".monthlyinputdate1").datepicker({
          uiLibrary: "bootstrap5",
          dateFormat: "dd-mm-yy",
          autoclose: true,
          todayHighlight: true,
          // minDate: date, //For disabling date from today
           onSelect: function (dateText, inst) {
             todate = dateText;
                    $(".monthlyinputdate").datepicker("option", "maxDate", todate);
                }
        });
		
   $(".monthlyinputdate2").datepicker({
        uiLibrary: "bootstrap5",
        dateFormat: "mm-yy", // Format to display
        changeMonth: true,
        changeYear: true,
        showButtonPanel: true, // Add this to show the "Done" button
        beforeShow: function(input, inst) {
            // Remove the days
            var datepicker = $(this).datepicker('widget');
            datepicker.find('.ui-datepicker-calendar').remove();
        },
        onClose: function(dateText, inst) {
            var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
            var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
            $(this).datepicker('setDate', new Date(year, month, 1));
        }
    });
      });
$(document).ready(function () {
            $("#yearwise").click(function () {
                $("#year_div").show();
                $("#yfrom_dt").val(<?php echo date('Y');?>);
                $("#month_div").hide();
                $("#mfrom_dt").removeAttr('value');
                $("#day_div").hide();
                $("#from_dt").removeAttr('value');
                $("#to_dt").removeAttr('value');				
            });
            $("#monthwise").click(function () {
				$("#month_div").show();
                $("#year_div").hide();
				$("#day_div").hide();
                $("#yfrom_dt").removeAttr('value');
                $("#from_dt").removeAttr('value');				
                $("#to_dt").removeAttr('value');				
				var vals=(date.getMonth()+1)+"-"+date.getFullYear();
                $("#mfrom_dt").val(vals);
        
            });
            $("#daywise").click(function () {
                $("#year_div").hide();
                $("#yfrom_dt").removeAttr('value');
                $("#month_div").hide();
                $("#mfrom_dt").removeAttr('value');
                $("#day_div").show();
				var vals=date.getDate()+"-"+(date.getMonth()+1)+"-"+date.getFullYear();
				$("#from_dt").attr("value");
				$("#to_dt").attr("value");
                $("#from_dt").val(vals);
                $("#to_dt").val(vals);
            });
			
			 $(".fydatepicker").datepicker({
            format: "yyyy",
            viewMode: "years",
            minViewMode: "years"
        });
        $(".mydatepicker").datepicker({
            format: "mm-yyyy",
            viewMode: "months",
            minViewMode: "months"
        });
        });
    </script>
	
<script>
document.querySelectorAll('.show-absent').forEach(link => {
    link.addEventListener('click', function (e) {
        e.preventDefault();
        const data = JSON.parse(this.getAttribute('data-users'));
        const tbody = document.getElementById('absentDetailBody');
        tbody.innerHTML = '';
        data.forEach(user => {
            const row = `<tr>
                <td style='text-align:left;'>${user.name}</td>
                <td style='text-align:left;'>${user.avg}</td>
                <td style='text-align:left;'>${user.days}</td> <!-- ✅ Show number of absent days -->
                 <!--<td style='text-align:left;'>${user.dates}</td>-->
            </tr>`;
            tbody.innerHTML += row;
        });
        document.getElementById('absentModal').style.display = 'block';
    });
});
</script>
<script src="dv-rangepicker.js?v=1"></script>
<?php mu_foot(); ?>
  </body>
</html>
