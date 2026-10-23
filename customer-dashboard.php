<?php include "includes/includes.php";
session_start();
function differenceInHours($startdate, $enddate) {

    $difference = abs(strtotime($startdate) - strtotime($enddate));
    $hours = floor(($difference) / (60 * 60));
    $minuts = floor(($difference - $hours * 60 * 60) / 60);
    $seconds = floor(($difference - $hours * 60 * 60 - $minuts * 60));
    echo $hours . ":" . $minuts . ":" . $seconds;
}


// Date Range (Replace with POST input or default values)
// Get POST data

$cust_id = isset($_POST['cust_id']) ? intval($_POST['cust_id']) : 33;
$from_date = isset($_POST['from_date']) && !empty($_POST['from_date'])
    ? date('Y-m-d', strtotime($_POST['from_date']))
    : date('Y-m-01'); // First day of current month

$to_date = isset($_POST['to_date']) && !empty($_POST['to_date'])
    ? date('Y-m-d', strtotime($_POST['to_date']))
    : date('Y-m-d'); // Today's date



$cust_name = '';
if ($cust_id > 0) {
    $custQuery = "SELECT cust_name FROM adm_customer_master WHERE id = $cust_id";
    $custResult = $db->query($custQuery);
    if ($custResult && $custResult->num_rows > 0) {
        $custRow = $custResult->fetch_assoc();
        $cust_name = $custRow['cust_name'];
    }
}

// First Proof Query
$firstProofQuery = "SELECT 
    i.id AS project_id,
    i.pub_id AS ARTICLEID,
    t.current_stage,
    d.dept_name,
    t.current_status,
    i.created_dt,
    t.end_time,
    ROUND(TIMESTAMPDIFF(HOUR, i.created_dt, t.end_time), 2) AS delivery_hours,
    CASE 
        WHEN TIMESTAMPDIFF(HOUR, i.created_dt, t.end_time) < 48 THEN '<48 hrs to deliver'
        WHEN TIMESTAMPDIFF(HOUR, i.created_dt, t.end_time) BETWEEN 48 AND 72 THEN '48 to 72 hrs to deliver'
        ELSE '>72 hrs to deliver'
    END AS delivery_category
FROM inw_inward_dtl i
JOIN adm_workflow_master w ON w.wf_id = i.wf_id
JOIN inw_transactions t ON t.project_id = i.id
-- Join to dept_master using resolved dept_id
JOIN adm_dept_master d ON d.dept_code = (
    SELECT dept_id FROM (
        SELECT 
            SUBSTRING_INDEX(SUBSTRING_INDEX(w.wf_code, ',', n.n), ',', -1) AS dept_id
        FROM (
            SELECT a.N + b.N * 10 + 1 AS n
            FROM (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 
                  UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 
                  UNION ALL SELECT 8 UNION ALL SELECT 9) a,
                 (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 
                  UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 
                  UNION ALL SELECT 8 UNION ALL SELECT 9) b
        ) n
        WHERE n.n <= 1 + LENGTH(w.wf_code) - LENGTH(REPLACE(w.wf_code, ',', ''))
    ) AS depts
    WHERE dept_id != '15'
    ORDER BY FIND_IN_SET(dept_id, w.wf_code) DESC
    LIMIT 1
)
WHERE t.current_status = 'Completed'
  AND t.current_stage = 'FP'
  AND t.dept = (
      SELECT dept_id FROM (
          SELECT 
              SUBSTRING_INDEX(SUBSTRING_INDEX(w.wf_code, ',', n.n), ',', -1) AS dept_id
          FROM (
              SELECT a.N + b.N * 10 + 1 AS n
              FROM (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 
                    UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 
                    UNION ALL SELECT 8 UNION ALL SELECT 9) a,
                   (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 
                    UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 
                    UNION ALL SELECT 8 UNION ALL SELECT 9) b
          ) n
          WHERE n.n <= 1 + LENGTH(w.wf_code) - LENGTH(REPLACE(w.wf_code, ',', ''))
      ) AS depts
      WHERE dept_id != '15'
      ORDER BY FIND_IN_SET(dept_id, w.wf_code) DESC
      LIMIT 1
  )
  AND i.created_dt BETWEEN '".$from_date."' AND '".$to_date."'
  AND i.cust_id = '".$cust_id."'";

// Revised Proof Query
$revisedProofQuery = " SELECT 
    r.inw_id AS project_id,
    i.pub_id AS ARTICLEID,
	t.current_stage,
    d.dept_name,   
	r.revision_count,
    r.received_date,
    t.end_time,
    d.dept_name,
    ROUND(TIMESTAMPDIFF(HOUR, r.received_date, t.end_time), 2) AS delivery_hours,
    CASE 
        WHEN TIMESTAMPDIFF(HOUR, r.received_date, t.end_time) < 24 THEN '<24 hrs to deliver'
        WHEN TIMESTAMPDIFF(HOUR, r.received_date, t.end_time) BETWEEN 24 AND 48 THEN '24 to 48 hrs to deliver'
        ELSE '>48 hrs to deliver'
    END AS delivery_category
FROM inw_revisions_dtl r
JOIN inw_inward_dtl i ON i.id = r.inw_id
JOIN adm_workflow_master w ON w.wf_id = i.wf_id
JOIN inw_transactions t ON t.project_id = r.inw_id
JOIN adm_dept_master d ON d.dept_code = (
    SELECT dept_id FROM (
        SELECT 
            SUBSTRING_INDEX(SUBSTRING_INDEX(w.wf_code, ',', n.n), ',', -1) AS dept_id
        FROM (
            SELECT a.N + b.N * 10 + 1 AS n
            FROM (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 
                  UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 
                  UNION ALL SELECT 8 UNION ALL SELECT 9) a,
                 (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 
                  UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 
                  UNION ALL SELECT 8 UNION ALL SELECT 9) b
        ) n
        WHERE n.n <= 1 + LENGTH(w.wf_code) - LENGTH(REPLACE(w.wf_code, ',', ''))
    ) AS depts
    WHERE dept_id != '15'
    ORDER BY FIND_IN_SET(dept_id, w.wf_code) DESC
    LIMIT 1
)
WHERE t.current_status = 'Completed'
  AND t.current_stage = CONCAT('REV', r.revision_count)
  AND t.dept = (
      SELECT dept_id FROM (
          SELECT 
              SUBSTRING_INDEX(SUBSTRING_INDEX(w.wf_code, ',', n.n), ',', -1) AS dept_id
          FROM (
              SELECT a.N + b.N * 10 + 1 AS n
              FROM (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 
                    UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 
                    UNION ALL SELECT 8 UNION ALL SELECT 9) a,
                   (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 
                    UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 
                    UNION ALL SELECT 8 UNION ALL SELECT 9) b
          ) n
          WHERE n.n <= 1 + LENGTH(w.wf_code) - LENGTH(REPLACE(w.wf_code, ',', ''))
      ) AS depts
      WHERE dept_id != '15'
      ORDER BY FIND_IN_SET(dept_id, w.wf_code) DESC
      LIMIT 1
  )
  AND r.received_date BETWEEN '".$from_date."' AND '".$to_date."'
  AND i.cust_id = '".$cust_id."'";

// Fetch data helper
function getDeliveryStats($result, $isRevised = false) {
    $counts = [
        'total' => 0,
        'lt_48' => 0,
        'btw_48_72' => 0,
        'gt_72' => 0
    ];
    while ($row = $result->fetch_assoc()) {
        $counts['total']++;
        $hours = floatval($row['delivery_hours']);
        if ($isRevised) {
            if ($hours < 24) $counts['lt_48']++;
            elseif ($hours < 48) $counts['btw_48_72']++;
            else $counts['gt_72']++;
        } else {
            if ($hours < 48) $counts['lt_48']++;
            elseif ($hours <= 72) $counts['btw_48_72']++;
            else $counts['gt_72']++;
        }
    }
    return $counts;
}

// Execute and fetch
$fpResult = $db->query($firstProofQuery);
$revResult = $db->query($revisedProofQuery);

$firstStats = getDeliveryStats($fpResult);
$revStats = getDeliveryStats($revResult, true);

// QMS (Feedback)
$qmsQuery = "SELECT f.f_id, j.j_code, f.article, f.stage, f.message, f.created_on
    FROM inw_client_feedback f
    JOIN adm_journals j ON j.j_id = f.j_id
    WHERE f.message IS NOT NULL 
      AND f.cust_id = $cust_id 
      AND DATE(f.created_on) BETWEEN '$from_date' AND '$to_date'
    ORDER BY f.created_on DESC
";
$qmsResult = $db->query($qmsQuery);

// CAPA (Action Taken)
$capaQuery = "SELECT f.f_id, j.j_code, f.article, f.stage, f.ap_message, f.created_on
    FROM inw_client_feedback f
    JOIN adm_journals j ON j.j_id = f.j_id
    WHERE f.ap_message IS NOT NULL 
      AND f.cust_id = $cust_id 
      AND DATE(f.created_on) BETWEEN '$from_date' AND '$to_date'
    ORDER BY f.created_on DESC
";
$capaResult = $db->query($capaQuery);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>	
    <link href="styles.css" rel="stylesheet" />
    <link href="customer-dashboard.css" rel="stylesheet" />

    <!-- Data Table CSS- -->

   
   
  </head>
  <body>
      <div class="container-fluid">
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
	 


        <div class="container-fluid peerj">
            <div class="peerj-section1">
                <div class="peerj-section1-nav justify-content-between d-flex">
                    <div class="d-flex peerj-nav-right">
						<h4>Delivery Status <?php if (!empty($cust_name)) echo "- " . htmlspecialchars($cust_name); ?></h4>
                        <div class="d-flex delivery-items gap-3">
                            <p id="first-proof" class=" peerj1-clicked" onclick="handleClick(this)">First Proof</p>
                            <p id="revised" onclick="handleClick(this)">Revised</p>
                        </div>
                    </div>
<?php if (!empty($from_date) && !empty($to_date)): ?>
  <div style="text-align: center; font-weight: bold;margin-top:26px;margin-right: 20%;">
    Showing Data: 
    <span style="color: #2a9d8f;">
      <?php echo date('d-m-Y', strtotime($from_date)); ?>
    </span> 
    to 
    <span style="color: #2a9d8f;">
      <?php echo date('d-m-Y', strtotime($to_date)); ?>
    </span>
  </div>
<?php endif; ?>
<!-- Download Button -->
<div class="col-auto d-flex align-items-center">
  <button type="button" class="btn btn-success" id="downloadBtn">Download Excel</button>
</div>
<div class="col-auto d-flex align-items-center">
  <button type="button" class="btn btn-danger" id="downloadBtnall">Download All Data Excel</button>
</div>
 <input type="hidden" name="sfrom_date" id="sfrom_date" value="<?php echo date('Y-m-d', strtotime($from_date)); ?>">
    <input type="hidden" name="sto_date" id="sto_date" value="<?php echo date('Y-m-d', strtotime($to_date)); ?>">
    <input type="hidden" name="scust_id" id="scust_id" value="<?php echo $cust_id;?>"> <!-- Update dynamically -->
	
	
	
                  <div class="peerj-nav-date">
  <p id="dateDisplay">Filter</p>
  <button id="openDatepicker" class="peerj-datebtn">
    <img src="assets/images/date-piceker-icon.svg" alt="">
  </button>

  <div id="dateRangeInputs" class="mt-3">
    <form method="POST" id="filterForm" action="customer-dashboard.php">
      <div class="row g-3 align-items-center">
        
        <!-- Customer Dropdown -->
     <div class="row g-3 perrj-date">
		<div class="col-auto">
          <label for="cust_id" class="col-form-label">Customer:</label>
        </div>
        <div class="col-auto">
          <select class="form-select" name="cust_id" id="cust_id" required>
            <option value="">Select Customer</option>
            <?php
            $sql_customers = $auth->showCustomerlist();
             foreach($sql_customers as $res_customers) {
        $selected = ($res_customers['id'] == $cust_id) ? 'selected' : '';
        echo "<option value='{$res_customers['id']}' $selected>{$res_customers['cust_name']}</option>";
      }
            ?>
          </select>
        </div>
	
  <!-- From Date -->
  <div class="col-auto d-flex align-items-center">
    <label for="fromDate" class="col-form-label me-2">From:</label>
    <input type="text" name="from_date" id="fromDate" class="form-control" placeholder="Start Date" autocomplete="off" value="<?php echo htmlspecialchars($from_date); ?>" required style="min-width: 150px;" />
  </div>

  <!-- To Date -->
  <div class="col-auto d-flex align-items-center">
    <label for="toDate" class="col-form-label me-2">To:</label>
    <input type="text" name="to_date" id="toDate" class="form-control" placeholder="End Date" autocomplete="off" value="<?php echo htmlspecialchars($to_date); ?>" required style="min-width: 150px;" />
  </div>

  <!-- Submit Button -->
  <div class="col-auto d-flex align-items-center">
    <button type="submit" class="btn btn-primary">Submit</button>
  </div>
</div>

      </div>
    </form>
  </div>
</div>

                </div>
<!-- HTML Rendering -->
<div class="peerj-section1-cards no-wrap" id="firstProofSection">
    <div class="peerj-dash-card1 peerj-dash-card">
        <div class="d-flex gap-4 peerj-card-head">
            <img src="assets/images/peerj/icon1.svg" alt="icon">
            <p><?php echo  $firstStats['total'] ?></p>
        </div>
        <p>Total Firstproof</p>
    </div>
    <div class="peerj-dash-card2 peerj-dash-card">
        <div class="d-flex gap-4 peerj-card-head">
            <img src="assets/images/peerj/icon2.svg" alt="icon">
            <p><?php echo  $firstStats['lt_48'] ?></p>
        </div>
        <p>Within 48 hrs</p>
    </div>
    <div class="peerj-dash-card3 peerj-dash-card">
        <div class="d-flex gap-4 peerj-card-head">
            <img src="assets/images/peerj/icon3.svg" alt="icon">
            <p><?php echo  $firstStats['btw_48_72'] ?></p>
        </div>
        <p>48 - 72 hrs</p>
    </div>
    <div class="peerj-dash-card4 peerj-dash-card">
        <div class="d-flex gap-4 peerj-card-head">
            <img src="assets/images/peerj/icon4.svg" alt="icon">
            <p><?php echo  $firstStats['gt_72'] ?></p>
        </div>
        <p>Above 72 hrs</p>
    </div>
</div>

<div class="peerj-section1-cards no-wrap" id="revisedSection" style="display:none">
    <div class="peerj-dash-card1 peerj-dash-card">
        <div class="d-flex gap-4 peerj-card-head">
            <img src="assets/images/peerj/icon1.svg" alt="icon">
            <p><?php echo  $revStats['total'] ?></p>
        </div>
        <p>Total Revised</p>
    </div>
    <div class="peerj-dash-card2 peerj-dash-card">
        <div class="d-flex gap-4 peerj-card-head">
            <img src="assets/images/peerj/icon2.svg" alt="icon">
            <p><?php echo  $revStats['lt_48'] ?></p>
        </div>
        <p>Within 24 hrs</p>
    </div>
    <div class="peerj-dash-card3 peerj-dash-card">
        <div class="d-flex gap-4 peerj-card-head">
            <img src="assets/images/peerj/icon3.svg" alt="icon">
            <p><?php echo  $revStats['btw_48_72'] ?></p>
        </div>
        <p>24 - 48 hrs</p>
    </div>
    <div class="peerj-dash-card4 peerj-dash-card">
        <div class="d-flex gap-4 peerj-card-head">
            <img src="assets/images/peerj/icon4.svg" alt="icon">
            <p><?php echo  $revStats['gt_72'] ?></p>
        </div>
        <p>Above 48 hrs</p>
    </div>
</div>
            </div>
            <div class="peerj-section2">
                <div class="peerj-section2-left ">
				<div class="peerj-graph">
				<h4>Review Statistics</h4>
				<canvas id="chart-container"></canvas>
					<div class="peerj-graph-foot">
					<p><span style="background-color: rgba(54, 162, 235, 0.7);"></span>First Proof</p>
					<p><span style="background-color: rgba(255, 99, 132, 0.7);"></span>Revised</p>
					</div>
				</div>

                   <div class="peerj-happenings">
    <h4 class="peerj-common-head">Happenings</h4>
    <div class="peerj-happenings-content">
        <?php
        // Fetch happenings from the database
        $stmt = $db->prepare("SELECT h_title, h_cdate FROM wf_happenings ORDER BY h_cdate DESC LIMIT 10");
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
          //  while ($row = $result->fetch_assoc()) {
               // $formattedDate = date("d-m-Y", strtotime($row['h_cdate']));
               // echo '<p><span></span>' . htmlspecialchars($row['h_title']) . ' ON ' . $formattedDate . '</p>';
             //   echo '<p><span></span>' . htmlspecialchars($row['h_title']) . '</p>';
          //  }
			$colors = ['#3771F2', '#F25C37', '#37F29E', '#F2D237', '#8E37F2']; // define some colors

while ($row = $result->fetch_assoc()) {
    $randomColor = $colors[array_rand($colors)];
    echo '<p><span style="background-color:' . $randomColor . '; display:inline-block; width:14px; height:14px; border-radius:3px; margin-right:8px; vertical-align:middle;"></span>' . htmlspecialchars($row['h_title']) . '</p>';
}
        } else {
            echo '<p><span></span>No recent happenings.</p>';
        }

        $stmt->close();
        ?>
    </div>
</div>

                </div>
                <div class="peerj-section2-right">
    <div class="peerj-table-nav">
        <div class="d-flex peerj-table-items gap-3">
            <p id="qms-tab" class="peerj1-clicked" onclick="showTab('qms')">QMS</p>
            <p id="capa-tab" onclick="showTab('capa')">CAPA</p>
        </div>

        <div class="peerj-table-wrapper">
            <!-- QMS TABLE -->
            <div class="peerj-table" id="qms-table">
                <table>
                    <colgroup>
                        <col width="10%" /><col width="20%" /><col width="20%" /><col width="20%" /><col width="30%" />
                    </colgroup>
                    <tr class="peerj-table-head">
                        <th>F.No</th>
                        <th>Journal Name</th>
                        <th>Article ID</th>
                        <th>Stage</th>
                        <th>Feedback</th>
                    </tr>
                    <?php $i = 1; while($row = $qmsResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo  $row['f_id'] ?></td>
                            <td><?php echo  htmlspecialchars($row['j_code']) ?></td>
                            <td><?php echo  htmlspecialchars($row['article']) ?></td>
                            <td><?php echo  htmlspecialchars($row['stage']) ?></td>
							<td><div class="feedback-content"><?php echo $row['message']; ?></div></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>

            <!-- CAPA TABLE -->
            <div class="peerj-table" id="capa-table" style="display:none;">
                <table>
                    <colgroup>
                        <col width="10%" /><col width="20%" /><col width="20%" /><col width="20%" /><col width="30%" />
                    </colgroup>
                    <tr class="peerj-table-head">
                        <th>F.No</th>
                        <th>Journal Name</th>
                        <th>Article ID</th>
                        <th>Stage</th>
                        <th>Action Taken</th>
                    </tr>
                    <?php $i = 1; while($row = $capaResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo  $row['f_id'] ?></td>
                            <td><?php echo  htmlspecialchars($row['j_code']) ?></td>
                            <td><?php echo  htmlspecialchars($row['article']) ?></td>
                            <td><?php echo  htmlspecialchars($row['stage']) ?></td>
							<td><div class="feedback-content"><?php echo $row['ap_message']; ?></div></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>
    </div>
</div>

            </div>
        </div>




    </div>
	
		 <!-- Footer Section -->
     <?php echo footer(); ?>




<!-- Footer Section -->
<?php echo scripts(); ?>	

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  document.querySelectorAll('.peerj-happenings-content p span').forEach(span => {
    // Generate random color in hex
    const randomColor = '#' + Math.floor(Math.random()*16777215).toString(16).padStart(6, '0');
    span.style.backgroundColor = randomColor;
    span.style.display = 'inline-block';
    span.style.width = '14px';
    span.style.height = '14px';
    span.style.borderRadius = '3px';
    span.style.marginRight = '8px';
    span.style.verticalAlign = 'middle';
  });
</script>

<script>
function handleClick(element) {
    document.querySelectorAll('.delivery-items p').forEach(p => p.classList.remove('peerj1-clicked'));
    element.classList.add('peerj1-clicked');

    const id = element.id;
    document.getElementById('firstProofSection').style.display = id === 'first-proof' ? 'grid' : 'none';
    document.getElementById('revisedSection').style.display = id === 'revised' ? 'grid' : 'none';
}
</script>

<script>
function showTab(tab) {
    document.getElementById('qms-tab').classList.remove('peerj1-clicked');
    document.getElementById('capa-tab').classList.remove('peerj1-clicked');

    document.getElementById('qms-table').style.display = 'none';
    document.getElementById('capa-table').style.display = 'none';

    document.getElementById(tab + '-tab').classList.add('peerj1-clicked');
    document.getElementById(tab + '-table').style.display = 'block';
}
</script>


    <script>
        $(document).ready(function () {
    const today = new Date();
    const formattedToday = today.toISOString().split('T')[0];  // Format: yyyy-mm-dd

    // Set the default date on page load
  //  $('#dateDisplay').text(`${formattedToday}`).show();
    $('#dateDisplay').text(`Filter`).show();

    // Show the date inputs when button clicked
    $('#openDatepicker').click(function () {
        $('#dateRangeInputs').toggle();
    });

    // Initialize From Date picker
    $('#fromDate').datepicker({
        format: "yyyy-mm-dd",
        autoclose: true,
        todayHighlight: true,
        endDate: today // Cannot select future dates
    }).on('changeDate', function (e) {
        let selectedFromDate = e.date;

        // The minimum date for To Date can now be the same as From Date
        $('#toDate').datepicker('setStartDate', selectedFromDate);
        $('#toDate').datepicker('setEndDate', today); // Cannot select beyond today

        // Reset To Date if it's outside the new range
        if ($('#toDate').val()) {
            let toDate = new Date($('#toDate').val());
            if (toDate < selectedFromDate || toDate > today) {
                $('#toDate').val('');
            }
        }

        checkDateSelection();
    });

    // Initialize To Date picker
    $('#toDate').datepicker({
        format: "yyyy-mm-dd",
        autoclose: true,
        todayHighlight: true,
        startDate: today, // Initially set to today
        endDate: today     // Cannot select beyond today
    }).on('changeDate', function () {
        checkDateSelection();
    });

    function checkDateSelection() {
        var fromDate = $('#fromDate').val();
        var toDate = $('#toDate').val();

        if (fromDate && toDate) {
            $('#dateRangeInputs').hide();
            if (fromDate === toDate) {
                $('#dateDisplay').text(`${toDate}`).show();
            } else {
                $('#dateDisplay').text(`${fromDate} to ${toDate}`).show();
            }
        }
    }
});
    </script>
    <script>
        const handleClick = (element) => {
            const paragraphs = document.querySelectorAll(".delivery-items p");

            // If clicked element already has the class, do nothing
            if (element.classList.contains('peerj1-clicked')) {
                return; // keep the class, no change
            }

            // Remove 'peerj1-clicked' class from all <p> tags
            paragraphs.forEach((p) => {
                p.classList.remove("peerj1-clicked");
            });

            // Add the class to the clicked element
            element.classList.add("peerj1-clicked");
        };
        const handleTableClick = (element) => {
            const paragraphs = document.querySelectorAll(".peerj-table-items p");

            // If clicked element already has the class, do nothing
            if (element.classList.contains('peerj1-clicked')) {
                return; // keep the class, no change
            }

            // Remove 'peerj1-clicked' class from all <p> tags
            paragraphs.forEach((p) => {
                p.classList.remove("peerj1-clicked");
            });

            // Add the class to the clicked element
            element.classList.add("peerj1-clicked");
        };
    </script>

    <!-- Review Statics graph -->

<script>
const chartData = {
    labels: ['Ahead', 'On Time', 'Delay'],
    datasets: [
        {
            label: 'First Proof',
            backgroundColor: 'rgba(54, 162, 235, 0.7)',
            data: [
                <?php echo  $firstStats['lt_48'] ?>,
                <?php echo  $firstStats['btw_48_72'] ?>,
                <?php echo  $firstStats['gt_72'] ?>
            ]
        },
        {
            label: 'Revised',
            backgroundColor: 'rgba(255, 99, 132, 0.7)',
            data: [
                <?php echo  $revStats['lt_48'] ?>,
                <?php echo  $revStats['btw_48_72'] ?>,
                <?php echo  $revStats['gt_72'] ?>
            ]
        }
    ]
};
</script>

<script>
const ctx = document.getElementById('chart-container').getContext('2d');

new Chart(ctx, {
    type: 'bar',
    data: chartData,
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top'
            },
            title: {
                display: false
            }
        },
        scales: {
            x: {
                title: {
                    display: true,
                    text: 'Delivery Time'
                }
            },
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Proofs'
                }
            }
        }
    }
});
</script>



   
     <!-- Toogle show hide functionality -->
    <script>
      const showbtn = $(".showmorebtn");
      const report = $(".report");
      var state = "false";

      showbtn.on("click", function () {
        report[0].classList.toggle("hide");
        state = !state;
        if (state) {
          showbtn[0].innerHTML = "Show More";
        } else {
          showbtn[0].innerHTML = "Show Less";
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

          format: "dd-mm-yyyy",
          autoclose: true,
          weekStart: 1,
          todayHighlight: true,
          // minDate: date,
          onSelect: function (dateText, inst) {
            fromdate = dateText;
            console.log(fromdate);
          },
        });

        $(".monthlyinputdate1").datepicker({
          uiLibrary: "bootstrap5",
          iconsLibrary: "materialicons",
          format: "dd-mm-yyyy",
          autoclose: true,
          weekStart: 1,
          todayHighlight: true,
          // minDate: new Date(), //For disabling date from today
        });
      });
	  
	   // DataTable
                var table = $('#myTable1').DataTable({
                    searching: true,
                    pageLength:5,
					lengthMenu : [[5,10, 25, 50, 100, 100000], [5,10, 25, 50, 100, "All"]],  
				});
    </script>

  <script>
$("#downloadBtn").on("click", function () {
    const formData = $("#filterForm").serialize();
    const downloadUrl = "download-report.php?" + formData;
    window.location.href = downloadUrl; // triggers download
});
$("#downloadBtnall").on("click", function () {
    const formData = $("#filterForm").serialize();
    const downloadUrl = "download-report-all.php?" + formData;
    window.location.href = downloadUrl; // triggers download
});

</script>


  </body>
</html>


</body>

</html>