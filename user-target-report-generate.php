<?php
include "includes/includes.php";
session_start();

// Initialize variables
//$user_condition = "and dept='" . $_REQUEST['dept_code'] . "' and inw.process_user= '" . $_REQUEST['user_id'] . "' "; 

if($_REQUEST['dept_code']==1)
{
$user_condition= "and dept in ('1','2','22') and inw.process_user= '" . $_REQUEST['user_id'] . "' ";	
$user_condition_depart= "department_id in ('1','2','22')";	
}
elseif($_REQUEST['dept_code']==10)
{
$user_condition= "and dept in ('10','14','24') and inw.process_user= '" . $_REQUEST['user_id'] . "'";
$user_condition_depart= "department_id in ('10','14','24')";	
	
}
else
{
$user_condition= "and dept='".$_REQUEST['dept_code']."' and inw.process_user= '" . $_REQUEST['user_id'] . "'";
$user_condition_depart= "department_id='".$_REQUEST['dept_code']."'";	
	
}

// Get the start date and end date from the request and convert them to DateTime objects
if (isset($_REQUEST['fdate'])) {
    $start_date = date('Y-m-d', strtotime($_REQUEST['fdate']));
    $end_date = date('Y-m-d', strtotime($_REQUEST['tdate']));
} else {
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d');
}

// Convert dates to timestamps
$start_timestamp = strtotime($start_date);
$end_timestamp = strtotime($end_date);

// If end date is before start date, swap them
if ($end_timestamp < $start_timestamp) {
    $temp = $start_timestamp;
    $start_timestamp = $end_timestamp;
    $end_timestamp = $temp;
}


// Calculate the difference in seconds and convert to days
$days_difference = round(($end_timestamp - $start_timestamp) / (60 * 60 * 24)) + 1;

$user_department = mysqli_fetch_array(mysqli_query($db, "SELECT * FROM users INNER JOIN adm_dept_master ON users.department_id = adm_dept_master.dept_code WHERE (department_id = '" . $_REQUEST['dept_code'] . "' OR FIND_IN_SET('" . $_REQUEST['dept_code'] . "', access_other_depts)) AND users.id='" . $_REQUEST['user_id'] . "' "));

// Get all the departments
$dept_query = "SELECT dept_name, dept_code FROM adm_dept_master WHERE dept_code='" . $_REQUEST['dept_code'] . "' ORDER BY dept_name ASC";
$dept_query_res = $db->query($dept_query) or die($db->error);

while ($dept_row = $dept_query_res->fetch_assoc()) {
    // Get all the active users for a particular department
    $user_query = "SELECT full_name, id, user_name FROM users WHERE (department_id = '" . $dept_row['dept_code'] . "' OR FIND_IN_SET('" . $dept_row['dept_code'] . "', access_other_depts)) AND approved='1'  AND id='" . $_REQUEST['user_id'] . "' ORDER BY full_name ASC";
    $users = $db->query($user_query) or die($db->error);

    while ($user_row = $users->fetch_assoc()) {
        // Initialize total pages completed and target pages
        $total_pages_completed = 0;
        $total_target_pages = 0;

        // Initialize stage-wise and customer-wise data
        $stage_totals = [
            'FP' => ['completed' => 0, 'target' => 0],
            'REV' => ['completed' => 0, 'target' => 0],
            'CORR' => ['completed' => 0, 'target' => 0],
            'FIN' => ['completed' => 0, 'target' => 0],
            'PMC' => ['completed' => 0, 'target' => 0],
			'ISS' => ['completed' => 0, 'target' => 0],
                        'JSTAGE' => ['completed' => 0, 'target' => 0]
        ];
        $customer_data = [];

        // Loop through user target customers and calculate pages for different stages
        $user_target_customer = $db->query("SELECT * FROM user_target_customer_assign INNER JOIN adm_customer_master ON user_target_customer_assign.cust_id = adm_customer_master.id WHERE user_id='" . $user_row['id'] . "'");
        while ($res_user_target = $user_target_customer->fetch_assoc()) {
            $customer_id = $res_user_target['id'];
            $customer_name = $res_user_target['cust_name'];

            if (!isset($customer_data[$customer_id])) {
                $customer_data[$customer_id] = [
                    'name' => $customer_name,
                    'stages' => [
                        'FP' => ['completed' => 0, 'target' => 0],
                        'REV' => ['completed' => 0, 'target' => 0],
                        'CORR' => ['completed' => 0, 'target' => 0],
                        'FIN' => ['completed' => 0, 'target' => 0],
            'PMC' => ['completed' => 0, 'target' => 0],
						'ISS' => ['completed' => 0, 'target' => 0],
                        'JSTAGE' => ['completed' => 0, 'target' => 0]
                    ]
                ];
            }

            $stages = ['FP' => 'page_count', 'REV' => 'rpc', 'CORR' => 'cpc', 'FIN' => 'fpc', 'PMC' => 'mpc', 'ISS' => 'ipc', 'JSTAGE' => 'jpc'];
            $stages = rs_filter_stages($stages);   // only the stages in $report_stage_types

            foreach ($stages as $stage => $target_field) {
                // Specific condition for the CORR stage
                $status_condition = ($stage == 'CORR') ? "inw.current_status = 'Correction Done'" : "current_status = 'Completed'";
                $stage_condition = ($stage != 'CORR') ? "AND current_stage LIKE '%$stage%'" : "";

                $query = "
                    WITH FirstOccurrences AS (
                        SELECT 
                            inw.project_id,
                            inw.pages_completed,
                            ROW_NUMBER() OVER (PARTITION BY inw.project_id ORDER BY inw.created_dt) AS rn
                        FROM 
                            inw_transactions AS inw
                        INNER JOIN 
                            inw_inward_dtl AS wd ON inw.project_id = wd.id
                        INNER JOIN 
                            adm_customer_master AS c ON wd.cust_id = c.id
                        INNER JOIN 
                            adm_journals AS j ON wd.j_id = j.j_id
                        WHERE
                            DATE_FORMAT(inw.created_dt, '%Y-%m-%d') >= '$start_date'
                            AND DATE_FORMAT(inw.created_dt, '%Y-%m-%d') <= '$end_date'
                            AND wd.cust_id = '$customer_id'
                            AND $status_condition
                            $stage_condition
                            $user_condition
                            AND (inw.assign_department IS NULL OR inw.assign_department = 0)
                    )
                    SELECT 
                        SUM(pages_completed) AS pages_completed
                    FROM 
                        FirstOccurrences
                    WHERE 
                        rn = 1
                ";

                // Log the query for debugging
                // echo "DEBUG: SQL Query - $query<br>";

                $result = $db->query($query) or die($db->error);
                $pages_completed = ($result->fetch_assoc()['pages_completed']) ?? 0;
                $target_pages = ($res_user_target[$target_field] != "0") ? $res_user_target[$target_field] : 1;

                $total_pages_completed += $pages_completed;
                $total_target_pages += $target_pages;

                $stage_totals[$stage]['completed'] += $pages_completed;
                $stage_totals[$stage]['target'] += $target_pages;

                $customer_data[$customer_id]['stages'][$stage]['completed'] = $pages_completed;
                $customer_data[$customer_id]['stages'][$stage]['target'] = $target_pages;
            }
        }

        // Calculate the total percentage by summing the stage percentages for all customers
        $total_percentage = 0;
        foreach ($customer_data as $customer_id => $data) {
            $customer_total_percentage = 0;
            foreach ($data['stages'] as $stage => $totals) {
                $customer_total_percentage += ($totals['target'] > 0) ? round(($totals['completed'] / $totals['target']) * 100, 2) : 0;
            }
            $total_percentage += $customer_total_percentage;
        }
    }
}

// Display the total percentage
//echo "<p>Total Achieved Percentage: " . htmlspecialchars($total_percentage) . "%</p>";
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Performance Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
            color: #333;
        }
        h1, h2, h3 {
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
        .container {
            max-width: 1200px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .report-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .report-header h1 {
            margin: 0;
        }
        .report-header p {
            font-size: 1.2em;
        }
		 @media print {
            body, html {
                width: 210mm;
                height: 297mm;
                margin: 0;
                padding: 0;
            }
            .container {
                margin: 0;
                width: 100%;
            }
            table, th, td {
                page-break-inside: avoid;
            }
            .report-header h1 {
                font-size: 24px;
            }
            h2 {
                font-size: 20px;
            }
            h3 {
                font-size: 18px;
            }
			  .print-button {
                display: none;
            }
        }
		 .hidden {
            display: none;
        }
    </style>
	 <script>
        function printReport() {
            var printButton = document.getElementById('print-button');
            printButton.classList.add('hidden');
            window.print();
            printButton.classList.remove('hidden');
        }
    </script>
</head>
<body>

<div class="container">
    <div class="report-header">
        <h1>Employee Performance Report</h1>
        <p>Department: <?php echo htmlspecialchars($user_department['dept_name']); ?></p>
        <p>Employee: <?php echo htmlspecialchars($user_department['full_name']); ?> (<?php echo htmlspecialchars($user_department['user_name']); ?>)</p>
        <p>Date: <?php echo htmlspecialchars(date('d-m-Y', strtotime($_REQUEST['fdate']))); ?></p>
    </div>

    <!-- Overall Results Table -->
    <h2>Overall Results</h2>
    <table>
        <thead>
            <tr>
                <th>Department</th>
                <th>Employee Name</th>
                <th>Employee Code</th>
                <th>Date</th>
                <th>Target Percentage</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php echo htmlspecialchars($user_department['dept_name']); ?></td>
                <td><?php echo htmlspecialchars($user_department['full_name']); ?></td>
                <td><?php echo htmlspecialchars($user_department['user_name']); ?></td>
                <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($_REQUEST['fdate']))); ?></td>
                <td><?php echo htmlspecialchars($total_percentage); ?>%</td>
            </tr>
        </tbody>
    </table>

 <?php
// Initialize an array to keep track of stage percentages
$stage_percentages = [];
$total_completed = [];

// Loop through each customer to aggregate the stage percentages
foreach ($customer_data as $customer_id => $data) {
    foreach ($data['stages'] as $stage => $totals) {
        $percentage = $totals['target'] > 0 ? round(($totals['completed'] / $totals['target']) * 100, 2) : 0;

        // Aggregate the percentage for each stage
        if (!isset($stage_percentages[$stage])) {
            $stage_percentages[$stage] = 0;
        }
        $stage_percentages[$stage] += $percentage;
        $total_completed[$stage] += $totals['completed'];
    }
}
?>

<!-- Stage-Wise Breakdown Table -->
<h2>Stage-Wise Breakdown</h2>
<table>
    <thead>
        <tr>
            <th>Stage</th>
            <th>Pages Completed</th>
            <!--<th>Target Pages</th>-->
            <th>Percentage</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach (rs_filter_stages($stage_totals) as $stage => $totals): ?>
            <tr>
                <td><?php echo htmlspecialchars($stage); ?></td>
                <td><?php echo htmlspecialchars($total_completed[$stage]); ?></td>
                <!--<td><?php echo htmlspecialchars($totals['target']); ?></td>-->
                <td>
                    <?php
                    // Calculate the combined percentage for the stage
                    $combined_percentage = isset($stage_percentages[$stage]) ? $stage_percentages[$stage] : 0;
                    echo htmlspecialchars($combined_percentage) . '%';
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>


    <!-- Customer-Wise Breakdown -->
    <?php 
    $total_percentage = 0;
    foreach ($customer_data as $customer_id => $data): 
        $customer_total_percentage = 0;
        foreach ($data['stages'] as $stage => $totals) {
            $stage_percentage = $totals['target'] > 0 ? round(($totals['completed'] / $totals['target']) * 100, 2) : 0;
            $customer_total_percentage += $stage_percentage;
        }
        $total_percentage += $customer_total_percentage;
    ?>
        <h3>Customer: <?php echo htmlspecialchars($data['name']); ?></h3>
        <table>
            <thead>
                <tr>
                    <th>Stage</th>
                    <th>Pages Completed</th>
                    <th>Target Pages</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (rs_filter_stages($data['stages']) as $stage => $totals): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stage); ?></td>
                        <td><?php echo htmlspecialchars($totals['completed']); ?></td>
                        <td><?php echo htmlspecialchars($totals['target']); ?></td>
                        <td><?php echo htmlspecialchars($totals['target'] > 0 ? round(($totals['completed'] / $totals['target']) * 100, 2) : 0); ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p><strong>Total Achieved Percentage for <?php echo htmlspecialchars($data['name']); ?>:</strong> <?php echo htmlspecialchars($customer_total_percentage); ?>%</p>
    <?php endforeach; ?>
    
    <!-- Display overall percentage for all customers -->
    <h2>Total Achieved Percentage for All Customers: <?php echo htmlspecialchars($total_percentage); ?>%</h2>
</div>


</body>
</html>
