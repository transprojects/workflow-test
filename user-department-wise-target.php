<?php
include "includes/includes.php";
session_start();


// Get department code and user ID from request
$dept_code = $_REQUEST['dept_id'];
if ($_REQUEST['user_id'] != "" && $_REQUEST['user_id'] != "all") {
    $user_condition_tr = "and inw.process_user= '" . $_REQUEST['user_id'] . "'";
    $user_condition_user = "and users.id= '" . $_REQUEST['user_id'] . "'";
} else {
    $user_condition_tr = "";
    $user_condition_user = "";
}

// Get the start date and end date from the request and convert them to DateTime objects
$start_date = isset($_REQUEST['fdate']) ? date('Y-m-d', strtotime($_REQUEST['fdate'])) : date('Y-m-d');
$end_date = isset($_REQUEST['tdate']) ? date('Y-m-d', strtotime($_REQUEST['tdate'])) : date('Y-m-d');

// Create DateTime objects
$start = new DateTime($start_date);
$end = new DateTime($end_date);
$interval = $start->diff($end);
$daysDifference = $interval->days + 1;

// Get department details
$dept_query = "SELECT dept_name, dept_code FROM adm_dept_master WHERE dept_code='$dept_code' ORDER BY dept_name ASC";
$dept_query_res = $db->query($dept_query) or die($db->error);
$dept_row = $dept_query_res->fetch_assoc();

if ($_REQUEST['dept_id'] == 1) {
    $user_condition = "and dept in ('1','2','22')";
    $user_condition_depart = "and department_id in ('1','2','22')";
} elseif ($_REQUEST['dept_id'] == 10) {
    $user_condition = "and dept in ('10','14','24')";
    $user_condition_depart = "and department_id in ('10','14','24')";
} else {
    $user_condition = "and dept='" . $_REQUEST['dept_id'] . "'";
    $user_condition_depart = "and department_id='" . $_REQUEST['dept_id'] . "'";
}

// Get all users for the department
$user_query = "SELECT full_name, id, user_name FROM users INNER JOIN user_target_customer_assign ON users.id = user_target_customer_assign.user_id WHERE 1=1 $user_condition_depart AND approved='1' $user_condition_user GROUP BY user_target_customer_assign.user_id ORDER BY full_name ASC";
$users = $db->query($user_query) or die($db->error);
$num_user_target = mysqli_num_rows($users);
$user_targets = [];

if ($num_user_target > 0) {
    while ($user_row = $users->fetch_assoc()) {
        $user_id = $user_row['id'];
        $total_pages_completed = 0;
        $total_target_pages = 0;

        $stage_totals = ['FP'=>['completed'=>0,'target'=>0],'REV'=>['completed'=>0,'target'=>0],'CORR'=>['completed'=>0,'target'=>0],'FIN'=>['completed'=>0,'target'=>0],'PMC'=>['completed'=>0,'target'=>0],'ISS'=>['completed'=>0,'target'=>0],'JSTAGE'=>['completed'=>0,'target'=>0]];
        $stage_customer_counts = ['FP'=>0,'REV'=>0,'CORR'=>0,'FIN'=>0,'PMC'=>0,'ISS'=>0,'JSTAGE'=>0];
        $customer_data = [];

        $user_target_customer = $db->query("SELECT * FROM user_target_customer_assign INNER JOIN adm_customer_master ON user_target_customer_assign.cust_id = adm_customer_master.id WHERE user_id='$user_id'");
        while ($res_user_target = $user_target_customer->fetch_assoc()) {
            $customer_id = $res_user_target['id'];
            $customer_name = $res_user_target['cust_name'];

            if (!isset($customer_data[$customer_id])) {
                $customer_data[$customer_id] = ['name'=>$customer_name, 'stages'=>['FP'=>['completed'=>0,'target'=>0],'REV'=>['completed'=>0,'target'=>0],'CORR'=>['completed'=>0,'target'=>0],'FIN'=>['completed'=>0,'target'=>0],'PMC'=>['completed'=>0,'target'=>0],'ISS'=>['completed'=>0,'target'=>0],'JSTAGE'=>['completed'=>0,'target'=>0]]];
            }

            $stages = ($dept_code == 1) ? ['FP'=>'page_count'] : ['FP'=>'page_count','REV'=>'rpc','CORR'=>'cpc','FIN'=>'fpc','PMC'=>'mpc','ISS'=>'ipc','JSTAGE'=>'jpc'];
            $stages = rs_filter_stages($stages);   // only the stages in $report_stage_types

            foreach ($stages as $stage => $target_field) {
                $status_condition = ($stage == 'CORR') ? "inw.current_status = 'Correction Done'" : "current_status = 'Completed'";
                $stage_condition = ($stage != 'CORR') ? "AND current_stage LIKE '%$stage%'" : "";

                $query = "
                    WITH FirstOccurrences AS (
                        SELECT inw.project_id, inw.pages_completed,
                            ROW_NUMBER() OVER (PARTITION BY inw.project_id ORDER BY inw.created_dt) AS rn
                        FROM inw_transactions AS inw
                        INNER JOIN inw_inward_dtl AS wd ON inw.project_id = wd.id
                        INNER JOIN adm_customer_master AS c ON wd.cust_id = c.id
                        INNER JOIN adm_journals AS j ON wd.j_id = j.j_id
                        WHERE DATE_FORMAT(inw.created_dt, '%Y-%m-%d') BETWEEN '$start_date' AND '$end_date'
                            AND wd.cust_id = '$customer_id'
                            AND $status_condition
                            $stage_condition
                            AND inw.process_user = '$user_id'
                            AND (inw.assign_department IS NULL OR inw.assign_department = 0)
                    )
                    SELECT SUM(pages_completed) AS pages_completed FROM FirstOccurrences WHERE rn = 1
                ";

                $result = $db->query($query) or die($db->error);
                $pages_completed = ($result->fetch_assoc()['pages_completed']) ?? 0;
                $target_pages = (float)$res_user_target[$target_field]; // Don't default to 1 if 0

                $total_pages_completed += $pages_completed;
                $total_target_pages += $target_pages;

                $stage_totals[$stage]['completed'] += $pages_completed;
                $stage_totals[$stage]['target'] += $target_pages;

                $customer_data[$customer_id]['stages'][$stage]['completed'] = $pages_completed;
                $customer_data[$customer_id]['stages'][$stage]['target'] = $target_pages;

                // Only count this customer if the target for this stage is > 0
                if ($target_pages > 0) {
                    $stage_customer_counts[$stage]++;
                }
            }
        }

        $total_percentage = 0;
        $total_completed = 0;
        foreach ($customer_data as $data) {
            foreach ($data['stages'] as $stage => $totals) {
                if ($totals['target'] > 0) {
                    $total_percentage += round(($totals['completed'] / $totals['target']) * 100, 2);
                }
                $total_completed += $totals['completed'];
            }
        }
        $total_percentage = $total_percentage / $daysDifference;

        $user_targets[] = [
            'user_id' => $user_row['id'],
            'full_name' => $user_row['full_name'],
            'user_name' => $user_row['user_name'],
            'target_pages' => $total_target_pages,
            'completed_pages' => $total_completed,
            'percentage' => $total_percentage,
            'stage_totals' => $stage_totals,
            'customer_data' => $customer_data,
            'stage_customer_counts' => $stage_customer_counts
        ];
    }
}

function getStageTooltip($stage_key, $stage_totals, $stage_customer_counts, $customer_data, $daysDifference) {
    $total_target = $stage_totals[$stage_key]['target'];
    $completed = $stage_totals[$stage_key]['completed'];
    $customer_count = $stage_customer_counts[$stage_key];

    if ($customer_count > 0 && $daysDifference > 0) {
        // Adjusted total target = per customer daily target × days × customer count
        $daily_per_customer_target = $total_target / $customer_count;
        $adjusted_total_target = $daily_per_customer_target * $daysDifference * $customer_count;

        $completion_percentage = ($adjusted_total_target > 0) 
            ? round(($completed / $adjusted_total_target) * 100, 2)
            : 0;

        $average_target = round($daily_per_customer_target * $daysDifference, 2);

        return "Total : $total_target pages, Completed : $completed pages, "
             . "Target : $average_target pages (for $customer_count customers over $daysDifference days), "
             . "Completion Percentage : $completion_percentage%";
    } else {
        return "No valid targets for this stage.";
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Wise Report</title>

    <link rel="shortcut icon" href="<?php echo $_COOKIE['set_url'];?>/assets/images/favicon.svg">
    <link href="<?php echo $_COOKIE['set_url'];?>/css/main.min.css" rel="stylesheet" />
    <link href="<?php echo $_COOKIE['set_url'];?>/styles.css" rel="stylesheet" />
    <link type="text/css" href="<?php echo $_COOKIE['set_url'];?>/assets/css/ui.multiselect.css" rel="stylesheet" />
	<!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.1.0/css/buttons.dataTables.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .form-container {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
        }
        .form-container .row {
            margin-bottom: 15px;
        }
        .form-container .col-12 {
            padding: 0 10px;
        }
        .form-container .col-sm-3 {
            width: 25%;
        }
        .form-container .col-lg-3 {
            width: 25%;
        }
        .form-select, .dateselection {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .searchbtn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .searchbtn:hover {
            background-color: #0056b3;
        }
        .form-actions {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
        }
        .report-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .report-header h1 {
            margin-bottom: 10px;
            color: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #0056b3;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #ddd;
        }
	.tooltip-container {
		position: relative;
		display: inline-block;
		cursor: pointer;
		max-width: 100%;
	}

	.tooltip-text {
		visibility: hidden;
		opacity: 0;
		background-color: rgba(0, 0, 0, 0.85);
		color: #fff;
		text-align: center;
		padding: 8px 12px;
		border-radius: 6px;
		position: absolute;
		z-index: 9999;
		bottom: 125%;
		left: 50%;
		transform: translateX(-50%);
		transition: opacity 0.3s ease, transform 0.3s ease;
		font-size: 13px;

		/* FORCE horizontal tooltip */
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
		max-width: 1000px; /* increase or decrease as per your layout */
	}

	/* Tooltip arrow */
	.tooltip-text::after {
		content: "";
		position: absolute;
		top: 100%;
		left: 50%;
		margin-left: -6px;
		border-width: 6px;
		border-style: solid;
		border-color: rgba(0, 0, 0, 0.85) transparent transparent transparent;
	}

	.tooltip-container:hover .tooltip-text {
		visibility: visible;
		opacity: 1;
		transform: translateX(-50%) translateY(-5px);
	}
</style>

	<!-- jQuery and Bootstrap JS -->

</head>
<body>
<div class="container mt-5">
 <form method="post" name="search_target" action="user-department-wise-target.php">
            <div class="form-container">
<div class="row g-3">
        
       				  <div class="col-12 col-sm-3 col-lg-3">
                  <label class="mb-2">Choose Department <span style="color: red"></span></label>
                  <select class="form-select department_drop" name="dept_id" id="dept_id" required>
      <option value="">Select Department</option>
											<?php
											$sql_department = $auth->getAlldepartmentTarget();
											foreach($sql_department as $res_department)
											{
											?>
											<option value="<?php echo $res_department['dept_code'] ?>"style="font-weight:500;" <?php if ($_REQUEST['dept_id']==$res_department['dept_code']){?>selected<?php }?>><?php echo $res_department['dept_name'] ?> </option>
											<?php
											}
											?>
    </select>
                </div>
				 <div class="col-12 col-sm-3 col-lg-3">
                  <label class="mb-2">Choose Employee <span style="color: red"></span></label>
                  <select class="form-select department_drop" data-placeholder="Select Employee" name="user_id" id="user_id" required>
                    <option value="">Select Employee</option>
					

											 <?php
      $sql_users = $auth->showUserlist();
      foreach($sql_users as $res_users) {
      ?>
        <option value="<?php echo $res_users['id'] ?>" <?php if ($_REQUEST['user_id'] == $res_users['id']) echo 'selected'; ?>>
          <?php echo $res_users['full_name'] ?>
        </option>
      <?php } ?>
                </select>
                </div>
       <div class="col-12 col-sm-3 col-lg-3">
									<label class="mb-2">From Date <span style="color: red"></span></label>
									<input class="dateselection" type="date" name="fdate" value="<?php echo $start_date;?>" />
								</div>
							<div class="col-12 col-sm-3 col-lg-3">
						<label class="mb-2">To Date<span style="color: red"></span></label>
						<input class="dateselection" type="date" name="tdate" value="<?php echo $end_date;?>" />
						</div>
    </div>


                <div class="form-actions">
                    <button class="searchbtn" type="submit">Search</button>
                </div>
            </div>
        </form>
    <div class="report-header">
        <h4>Department Wise Report</h4>
        <p><strong>Department:</strong> <?php echo htmlspecialchars($dept_row['dept_name']); ?></p>
        <p><strong>Date :</strong> <?php echo htmlspecialchars(date('d-m-Y',strtotime($start_date))); ?></p>
    </div>

    <!-- User Target Report Table -->
    <h5>User Target and Completion Summary</h5>
 <table id="example">
    <thead>
        <tr>
            <th>Employee Name</th>
            <?php foreach (array('FP' => 'FP', 'REV' => 'REV', 'FIN' => 'FIN', 'PMC' => 'PMC', 'ISS' => 'ISSUE', 'JSTAGE' => 'JSTAGE', 'CORR' => 'CORRECTIONS') as $k => $h) { if (rs_target_enabled($k)) { echo '<th>' . $h . '</th>'; } } ?>
            <th>NON-WF</th>
            <?php if ($dept_code == 1 || $dept_code == 22): ?>
                <th>Target Count</th>
                <th>Completed Count</th>
            <?php else: ?>
                <th>Completed Pages</th>
            <?php endif; ?>
            <th>Completion Percentage</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($user_targets as $user):
            $stage_totals = $user['stage_totals'];
            $stage_customer_counts = $user['stage_customer_counts'];
            $dept = ($_REQUEST['dept_id'] == 1) ? "1" : (($_REQUEST['dept_id'] == 10) ? "10" : $_REQUEST['dept_id']);
        ?>
        <tr>
            <td>
                <a href="user-target-report-generate.php?dept_code=<?php echo $dept; ?>&fdate=<?php echo $_REQUEST['fdate']; ?>&user_id=<?php echo $user['user_id']; ?>&tdate=<?php echo $_REQUEST['fdate']; ?>">
                    <?php echo htmlspecialchars($user['full_name']); ?>
                </a>
            </td>

            <?php foreach (array_filter(['FP', 'REV', 'FIN', 'PMC', 'ISS', 'JSTAGE', 'CORR'], 'rs_target_enabled') as $stage): ?>
            <td>
                <div class="tooltip-container">
                    <?php echo $stage_totals[$stage]['completed']; ?>
                    <div class="tooltip-text">
                        <?php echo getStageTooltip($stage, $stage_totals, $stage_customer_counts, $user['customer_data'], $daysDifference); ?>
                    </div>
                </div>
            </td>
            <?php endforeach; ?>

            <td>N/A</td>

            <?php if ($dept_code == 1 || $dept_code == 22): ?>
                <td><?php echo $user['target_pages']; ?></td>
                <td><?php echo $user['completed_pages']; ?></td>
            <?php else: ?>
                <td><?php echo $user['completed_pages']; ?></td>
            <?php endif; ?>

            <td><?php echo $user['percentage']; ?>%</td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>


    <!-- User Stage-wise Report Table -->
   <?php /* <h2>Stage-wise Breakdown</h2>
    <?php foreach ($user_targets as $user): ?>
        <h3><?php echo htmlspecialchars($user['full_name']); ?> (<?php echo htmlspecialchars($user['user_name']); ?>)</h3>
        <table class="stage-table">
            <thead>
                <tr>
                    <th>Stage</th>
                    <th>Pages Completed</th>
                    <th>Target Pages</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($user['stage_totals'] as $stage => $totals): ?>
                    <?php
                    $percentage = ($totals['target'] > 0) ? round(($totals['completed'] / $totals['target']) * 100, 2) : 0;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stage); ?></td>
                        <td><?php echo htmlspecialchars($totals['completed']); ?></td>
                        <td><?php echo htmlspecialchars($totals['target']); ?></td>
                        <td><?php echo htmlspecialchars($percentage); ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>

    <!-- User Customer-wise Report Table -->
    <h2>Customer-wise Breakdown</h2>
    <?php foreach ($user_targets as $user): ?>
        <h3><?php echo htmlspecialchars($user['full_name']); ?> (<?php echo htmlspecialchars($user['user_name']); ?>)</h3>
        <?php foreach ($user['customer_data'] as $customer_id => $data): ?>
            <div class="customer-header">
                <h4>Customer: <?php echo htmlspecialchars($data['name']); ?></h4>
            </div>
            <table class="customer-table">
                <thead>
                    <tr>
                        <th>Stage</th>
                        <th>Pages Completed</th>
                        <th>Target Pages</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['stages'] as $stage => $totals): ?>
                        <?php
                        $percentage = ($totals['target'] > 0) ? round(($totals['completed'] / $totals['target']) * 100, 2) : 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stage); ?></td>
                            <td><?php echo htmlspecialchars($totals['completed']); ?></td>
                            <td><?php echo htmlspecialchars($totals['target']); ?></td>
                            <td><?php echo htmlspecialchars($percentage); ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>
    <?php endforeach; ?>
	<?php */
	?>
</div>
<!-- jQuery and Bootstrap JS -->
<script src="<?php echo $_COOKIE['set_url'];?>/assets/js/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.flash.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>


	<script>
	 $(document).ready(function () {
		 
		  // Initialize DataTable with export buttons
    $('#example').DataTable({
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
	
	
	// Handle changes in the "countries" select element
         $("#dept_id").on("change", function() {
                var department_id = $(this).val();

                // Fetch connectedsource based on the selected connectedsource and populate the "name" select element
                $.ajax({
                    url: "get_dashboard_users.php",
                    dataType: "json",
                    data: { dep_id: department_id },
                    success: function(data) {
                        populateSelect("user_id", data);

                        // Reset the "cities" select element when the country changes
                        //$("#user_id").empty();
                    }
                });
            });
			
					function populateSelect(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();
                selectElement.append($("<option>").text('All').val('all'));

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.full_name).val(item.id));
            });
        } 
		});
	</script>
	
				<script>
/* $(document).ready(function() {
  $('#user_id').on('change', function() {
    var user_id = $(this).val();

    if (user_id != '') {
      $.ajax({
        url: 'get_user_departments.php',
        type: 'POST',
        data: { user_id: user_id },
        success: function(response) {
          $('#dept_id').html(response);
        }
      });
    } else {
      $('#dept_id').html('<option value="">Select Department</option>');
    }
  });
}); */
</script>
</body>
</html>
