<?php 
include "includes/includes.php";
// Using a switch statement to return the month name


// Define the months array
$moths_array = array('2024-11', '2024-12', '2025-01', '2025-02');

?>
    <table class="table table-bordered monthlytable">
<?php
// Loop through each month
foreach ($moths_array as $mfrom_dt) {

    $start_date = $mfrom_dt . '-01'; 
    $end_date = $mfrom_dt . '-31';
	$explode_month=explode("-",$mfrom_dt);
$year = $explode_month[0]; // Replace with the desired year
$month =$explode_month[1];   // Replace with the desired month (1-12)

switch ((int)$month) {
    case 1:
        $month_name = 'January';
        break;
    case 2:
        $month_name = 'February';
        break;
    case 3:
        $month_name = 'March';
        break;
    case 4:
        $month_name = 'April';
        break;
    case 5:
        $month_name = 'May';
        break;
    case 6:
        $month_name = 'June';
        break;
    case 7:
        $month_name = 'July';
        break;
    case 8:
        $month_name = 'August';
        break;
    case 9:
        $month_name = 'September';
        break;
    case 10:
        $month_name = 'October';
        break;
    case 11:
        $month_name = 'November';
        break;
    case 12:
        $month_name = 'December';
        break;
    default:
        $month_name = 'Invalid month number'; 
        break;
}
	?>

                <thead>
                <tr><td colspan="2"><?php echo $month_name; ?></td></tr>
                    <tr>
                        <th>Customer</th>
                        <th>Stage</th>
                        <th>TOTAL</th>
                    </tr>
                    
                </thead>
                                <tbody>
    <?php

    // Query to fetch customer data
	//$customers_array = array('Peerj','TSP','SIF','EOS','ASP','IAP','IAP Japanese journal','NOW-Publisher','Gleampub','AKA','Sunrise','RSSDI','Charlsworth');
	
	//$customers_str = implode(',', $customers_array);
	
    $query = "SELECT cust_name, cust_id, id FROM `adm_customer_master` where cust_name IN ('Peerj','TSP','SIF','EOS','ASP','IAP','IAP Japanese journal','NOW-Publisher','Gleampub','AKA','Sunrise','RSSDI','Charlsworth') order by cust_name ASC;";
    $customers = $db->query($query) or die($db->error);
	
    
	

    // Loop through each customer
    while ($crow = $customers->fetch_assoc()) {
        $cust_name = $crow['cust_name'];
        $cust_id = $crow['cust_id'];
        $id = $crow['id'];
        
        $search = "AND cust_id='" . $id . "'";

        // Displaying the start and end dates for the month
        //echo "Start Date: " . $start_date . "<br>";
        //echo "End Date: " . $end_date;
        ?>

        
            

                    <tr>
                        <th style="color: #4648b8"><?php echo $cust_name; ?> </th>
                        <th style="color: #4648b8">FP</th>

                        <?php
                        // Query for the "FP" stage count
						$fp_query = "
    SELECT SUM(it.pages_completed) as total_pages 
    FROM inw_dispatch_history AS idh
    INNER JOIN inw_transactions AS it 
        ON it.project_id = idh.a_id
    WHERE DATE_FORMAT(idh.sent_date, '%Y-%m-%d') >= '" . date('Y-m-d', strtotime($start_date)) . "' 
      AND DATE_FORMAT(idh.sent_date, '%Y-%m-%d') <= '" . date('Y-m-d', strtotime($end_date)) . "' 
      AND idh.stage = 'FP'
	  AND it.stage = 'FP'
	  AND it.dept = '14'
      $search
";
                        $fp_query_rs = mysqli_query($db, $fp_query);

						$fp_query_total_arr = $fp_query_rs->fetch_assoc();
						$fp_query_total = $fp_query_total_arr['total_pages'];
						
                        ?>
                        <td><?php echo $fp_query_total; ?></td>
                    </tr>

                    <tr>
                        <th style="color: #4648b8"> </th>
                        <th style="color: #4648b8">REV</th>

                        <?php
						
						$fp_query = "
    SELECT SUM(it.pages_completed) as total_pages 
    FROM inw_dispatch_history AS idh
    INNER JOIN inw_transactions AS it 
        ON it.project_id = idh.a_id
    WHERE DATE_FORMAT(idh.sent_date, '%Y-%m-%d') >= '" . date('Y-m-d', strtotime($start_date)) . "' 
      AND DATE_FORMAT(idh.sent_date, '%Y-%m-%d') <= '" . date('Y-m-d', strtotime($end_date)) . "' 
      AND idh.stage LIKE '%REV%'
	  AND it.stage LIKE '%REV%'
	  AND it.dept = '14'
      $search
";
                        $fp_query_rs = mysqli_query($db, $fp_query);

						$fp_query_total_arr = $fp_query_rs->fetch_assoc();
						$rev_query_total = $fp_query_total_arr['total_pages'];
						
                        
                        ?>
                        <td><?php echo $rev_query_total; ?></td>
                    </tr>

                    <tr>
                        <th style="color: #4648b8"> </th>
                        <th style="color: #4648b8">ISS</th>

                        <?php
                        // Query for the "ISS" stage count
                        $fp_query = "
    SELECT SUM(it.pages_completed) as total_pages 
    FROM inw_issue_dispatch_history AS idh
    INNER JOIN inw_transactions AS it 
        ON it.project_id = idh.a_id
    WHERE DATE_FORMAT(idh.sent_date, '%Y-%m-%d') >= '" . date('Y-m-d', strtotime($start_date)) . "' 
      AND DATE_FORMAT(idh.sent_date, '%Y-%m-%d') <= '" . date('Y-m-d', strtotime($end_date)) . "' 
      AND idh.stage LIKE '%ISSCOR%'
	  AND it.stage LIKE '%ISSCOR%'
	  AND it.dept = '14'
      $search
";
                        $fp_query_rs = mysqli_query($db, $fp_query);

						$fp_query_total_arr = $fp_query_rs->fetch_assoc();
						$issue_query_total = $fp_query_total_arr['total_pages'];
                        ?>
                        <td><?php echo $issue_query_total; ?></td>
                    </tr>

                    <tr class="totalrow">
                    <th></th>
                        <th>TOTAL</th>
                        <td><?php echo $fp_query_total + $rev_query_total + $issue_query_total; ?></td>
                    </tr>
                
            
        

        <?php
    } // End of customer loop
} // End of month loop
?>
</tbody>
</table>
