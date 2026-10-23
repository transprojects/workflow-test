<?php include "includes/includes.php";?>
    <?php 
/**
 * Recursively copy files from one directory to another
 * 
 * @param String $src - Source of files being moved
 * @param String $dest - Destination of files being moved
 */ 
if(isset($_REQUEST['id']))
{
	$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$_REQUEST['id']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$app_res = $result->fetch_assoc();
	}
/// Get the Client Name ////
	$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = ".$app_res['cust_id'].";";
	$customers = $db->query($query) or ($db->error);
	$customer_info = $customers->fetch_assoc();
/// Get the journal name ///
	$query = "SELECT `j_id`,`j_name`,j_code FROM `adm_journals` WHERE j_id = ".$app_res['j_id'].";";
	$journal = $db->query($query) or ($db->error);
	$journal_info = $journal->fetch_assoc();	
}
	$transsql = $db->query("select * from inw_dept_comments where inw_id = ".$app_res['id']) or ($db->error);				
	$transsql_sql = $db->query("select * from inw_transactions where project_id = ".$app_res['id']." and comments!='' ") or ($db->error);				
//echo $transaction_id;
?>
    <?php 
	$cdate =explode(" ",$app_res['created_dt']);	
	$startdate = date("d-m-Y", strtotime($cdate[0]));	
	$recvicedate = date("d-m-Y", strtotime($app_res['recv_dt']));
	$duedate = date("d-m-Y", strtotime($app_res['due_dt'])); ?>
    <div class="page-content">
    <div class="page-header"> 
   <h5> <?php echo $customer_info['cust_name']; ?> : <?php echo $journal_info['j_name']; ?> : <?php echo $app_res['pub_id']; ?></h5> 
  <!--<h5 style="text-align:center;color:red;"> <?php echo $app_res['pub_id']; ?></h5>-->
</div>
    <!-- /.page-header -->
    <div class="row">
    <div class="col-xs-12">
    <!-- PAGE CONTENT BEGINS -->

    <div class="row">
  <div class="col-xs-12" > 
        <!-- PAGE CONTENT BEGINS -->
        <div id="timeline-1">
      <div class="row">
            <?php
		$sql = "SELECT * FROM article_transactions WHERE article_id = ".$_REQUEST['id']."  ORDER BY timestamp ASC";
$result = $db->query($sql);


?>
            <!-- Transaction Table Grid -->
            <div class="mt-5">
          <table class="table table-bordered">
                <thead>
              <tr>                   
                    <th>Time</th>
                    <th>Activity</th>
                    <th>Status</th>
                    <th>Department</th>
                    <th>Notes</th>
                  </tr>
            </thead>
                <tbody>
              <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
						
	$timestamp = $row['timestamp'];
	$date = new DateTime($timestamp); // No timezone conversion
	$formatted_timestamp = $date->format('Y-m-d h:i A');  // Format: YYYY-MM-DD hh:mm AM/PM
	
                        echo "<tr>
                            <td>{$formatted_timestamp}</td>
                            <td>{$row['action']}</td>
							<td>{$row['status']}</td>                            <td>{$row['department']}</td>
							<td>{$row['notes']}</td>
                        </tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No transactions found</td></tr>";
                }
                ?>
            </tbody>
              </table>
        </div>
          </div>
    </div>
      </div>
</div>
