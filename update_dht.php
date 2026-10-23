<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "root@123";
$dbname = "workflow";

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL to fetch required data from inw_inward_dtl and inw_revisions_dtl tables
$sql = "
    SELECT 
        d.pub_id,        
        d.id,
        d.due_dt,
        r.due_date,
        r.r_id,
        r.type,        
        r.revision_count
    FROM 
        inw_inward_dtl d
    JOIN 
        inw_revisions_dtl r ON d.id = r.inw_id
    WHERE         
        d.cust_id = 33
        AND YEAR(d.created_dt) = 2024
		ORDER BY `d`.`id` ASC
";

// Execute the query and check for errors
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Loop through the results
    while($row = $result->fetch_assoc()) {
       
	   echo $stage = $row['type'].$row['revision_count'];
	   echo '<br>';

        ////////// FP ////  Update QUERY
        echo $dht_qry = "SELECT * FROM `inw_dispatch_history` WHERE `cust_id` = 33 AND a_id = '".$row['id']."' AND stage = 'FP'";
		echo '<br>';
        $dht_result = $conn->query($dht_qry);
		if($dht_result->num_rows > 0)
		{			
			$dht_row = $dht_result->fetch_assoc();

			$sent_date = $dht_row['sent_date'];
			// Extract the time from the current sent_date
			$sent_time = date('H:i:s', strtotime($sent_date));  // Get time in HH:MM:SS format

			// Randomly select 0, 1, or 2 days before due_date
			$random_offset = rand(0, 2); // 0 means same as due_date, 1 means 1 day before, 2 means 2 days before
			$new_due_date = date('Y-m-d', strtotime($row['due_dt'] . " -$random_offset days"));

			// Update the sent_date with due_date's new date and keep the original time part
			$new_sent_date = $new_due_date . ' ' . $sent_time;

			// SQL to update the sent_date in inw_dispatch_history table
			echo $update_sql = "
			UPDATE inw_dispatch_history 
			SET sent_date = '".$new_sent_date."'
			WHERE a_id = '".$row['id']."'
			AND idh_id = '".$dht_row['idh_id']."' ";
			$conn->query($update_sql);
			echo '<br>';
		}

        ////////// REV and FIN ////  Update QUERY
        echo $dht_qry = "SELECT * FROM `inw_dispatch_history` WHERE `cust_id` = 33 AND a_id = '".$row['id']."' AND stage = '".$stage."'";
		echo '<br>';
        $dht_result = $conn->query($dht_qry);
		if($dht_result->num_rows > 0)
		{
			$dht_row = $dht_result->fetch_assoc();

			$sent_date = $dht_row['sent_date'];
			// Extract the time from the current sent_date
			$sent_time = date('H:i:s', strtotime($sent_date));  // Get time in HH:MM:SS format

			// Randomly select 0, 1, or 2 days before due_date
			$random_offset = rand(0, 2); // 0 means same as due_date, 1 means 1 day before, 2 means 2 days before
			$new_due_date = date('Y-m-d', strtotime($row['due_date'] . " -$random_offset days"));

			// Update the sent_date with due_date's new date and keep the original time part
			$new_sent_date = $new_due_date . ' ' . $sent_time;

			// SQL to update the sent_date in inw_dispatch_history table
			echo $update_sql = "
			UPDATE inw_dispatch_history 
			SET sent_date = '".$new_sent_date."' 		
			WHERE a_id = '".$row['id']."'
			AND idh_id = '".$dht_row['idh_id']."' ";
			echo '<br>';
			$conn->query($update_sql);
		}
    }
} else {
    echo "No records found to update.\n";
}

// Close the database connection
$conn->close();
?>
