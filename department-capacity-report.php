<?php include "includes/includes.php";
include ('includes/paginate.php');
include "includes/modern-ui.php";
?>
<?php


session_start();
if($_COOKIE['user_level']==1 || $_COOKIE['user_level']==5)
{
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
<style>
  .mu-table td,.mu-table th{white-space:nowrap;}
  .mu-table{min-width:0;}
  .dc-name{font-weight:700;color:var(--navy);}
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
          <div>
            <small>Reports</small>
            <h1>Department Capacity Report</h1>
            <p><?php echo (!empty($_POST['dep_id'])) ? htmlspecialchars(mysqli_fetch_assoc(mysqli_query($db, "SELECT dept_name FROM adm_dept_master WHERE dept_code = '".mysqli_real_escape_string($db, $_POST['dep_id'])."'"))['dept_name'] ?? '') : 'Average capacity per employee, by department'; ?></p>
          </div>
        </div>

        <form action="#" id="validation-form" method="post" autocomplete="off">
          <section class="mu-panel mu-filter-panel mu-reveal">
            <div class="mu-filter">
              <div class="mu-field" style="min-width:220px;"><label for="dep_id">Department</label>
<?php
    $query = "SELECT dept_name,dept_code FROM `adm_dept_master`  ORDER BY `dept_name` ASC ";
    $customers = $db->query($query) or ($db->error);
?>
                <select id="dep_id" name="dep_id" required>
                  <option value="">-Select-</option>
<?php while($row = $customers->fetch_assoc()){ ?>
                  <option value="<?php echo $row['dept_code']; ?>" <?php if(isset($_REQUEST['dep_id']) && $_REQUEST['dep_id'] ==$row['dept_code']){?> selected <?php } ?>><?php echo $row['dept_name']; ?></option>
<?php } ?>
                </select></div>
              <div class="mu-field" style="min-width:240px;"><label for="user_id">Employee</label>
                <select class="employee_drop" name="user_id" id="user_id">
                  <option value="">Select Employee</option>
                  <option value="all" <?php if (isset($_REQUEST['user_id']) && $_REQUEST['user_id']=="all"){?>selected<?php }?>>-All-</option>
<?php
	$sql_users = $auth->showdashboardUserlist(0);
	foreach($sql_users as $res_users) {
?>
                  <option value="<?php echo $res_users['id'] ?>" <?php if (isset($_REQUEST['user_id']) && $_REQUEST['user_id']==$res_users['id']){?>selected<?php }?>><?php echo $res_users['full_name'] ?></option>
<?php } ?>
                </select></div>
              <div class="mu-field" style="flex-direction:row;gap:10px;">
                <input type="hidden" name="filter" id="filter" value="1">
                <button class="mu-btn primary" type="submit" name="getaction"><i class="fa fa-bolt"></i> Get Result</button>
                <a class="mu-btn" href="dcapacityreport" title="Reset"><i class="fa fa-refresh"></i></a>
              </div>
            </div>
          </section>
        </form>


        <!-- Table section -->
		<?php 
				//echo $var;
if($_POST['dep_id']!="")
{
	
$dept_id = $_REQUEST['dep_id'];
$dept_res = mysqli_fetch_assoc(mysqli_query($db, "SELECT dept_name FROM adm_dept_master WHERE dept_code = '$dept_id'"));
$dept_name = $dept_res['dept_name'] ?? 'Unknown';
?>		
	
    <section class="mu-panel mu-reveal">
          <h3><i class="fa fa-table"></i> Capacity <span class="mu-right"><?php echo htmlspecialchars($dept_name); ?></span></h3>
<?php
$dep_id = isset($_REQUEST['dep_id']) ? mysqli_real_escape_string($db, $_REQUEST['dep_id']) : '';
$user_id = isset($_REQUEST['user_id']) ? mysqli_real_escape_string($db, $_REQUEST['user_id']) : '';
$filter_by_user = ($user_id !== '' && strtolower($user_id) !== 'all');
$cnt_query = "
    SELECT 
        uta.uta_id,
        uta.dept_id,
        dm.dept_name AS `Department`,
        u.full_name AS `Employee Name`,
        CASE 
            WHEN uta.dept_id = 1 THEN 
                CASE 
                    WHEN (COALESCE(uta.average_pr_pc, 0) + COALESCE(uta.average_pr_ppc, 0)) = 0 THEN NULL
                    ELSE ROUND(
                        (
                            IF(uta.average_pr_pc > 0, uta.average_pr_pc, 0) + 
                            IF(uta.average_pr_ppc > 0, uta.average_pr_ppc, 0)
                        ) / (
                            IF(uta.average_pr_pc > 0, 1, 0) + 
                            IF(uta.average_pr_ppc > 0, 1, 0)
                        ), 2
                    )
                END
            ELSE
                CASE 
                    WHEN (
                        COALESCE(uta.average_pr_pc, 0) + COALESCE(uta.average_pr_cpc, 0) +
                        COALESCE(uta.average_pr_rpc, 0) + COALESCE(uta.average_pr_fpc, 0) +
                        COALESCE(uta.average_pr_ipc, 0)
                    ) = 0 THEN NULL
                    ELSE ROUND(
                        (
                            IF(uta.average_pr_pc > 0, uta.average_pr_pc, 0) +
                            IF(uta.average_pr_cpc > 0, uta.average_pr_cpc, 0) +
                            IF(uta.average_pr_rpc > 0, uta.average_pr_rpc, 0) +
                            IF(uta.average_pr_fpc > 0, uta.average_pr_fpc, 0) +
                            IF(uta.average_pr_ipc > 0, uta.average_pr_ipc, 0)
                        ) / (
                            IF(uta.average_pr_pc > 0, 1, 0) +
                            IF(uta.average_pr_cpc > 0, 1, 0) +
                            IF(uta.average_pr_rpc > 0, 1, 0) +
                            IF(uta.average_pr_fpc > 0, 1, 0) +
                            IF(uta.average_pr_ipc > 0, 1, 0)
                        ), 2
                    )
                END
        END AS `Preferred Collaboration Avg`
    FROM user_target_assign AS uta
    LEFT JOIN users AS u ON u.id = uta.user_id
    LEFT JOIN adm_dept_master AS dm ON dm.dept_code = uta.dept_id
    WHERE dm.dept_code = '$dep_id' and u.approved=1
";
// Add user filter only if it's not "All"
if ($filter_by_user) {
    $cnt_query .= " AND uta.user_id = '$user_id'";
}
$cnt_result = mysqli_query($db, $cnt_query);
$sno = 1; $total_avg = 0; $valid_count = 0;
?>
          <div class="mu-table-wrap" style="border:0;">
            <table class="mu-table" style="max-width:560px;">
              <thead><tr><th>#</th><th>Name</th><th class="c">#Average</th></tr></thead>
              <tbody>
<?php while ($row = mysqli_fetch_assoc($cnt_result)) {
	$avg = $row['Preferred Collaboration Avg'];
	if (is_numeric($avg)) { $total_avg += $avg; $valid_count++; }
?>
                <tr>
                  <td><?php echo $sno++; ?></td>
                  <td class="dc-name"><?php echo htmlspecialchars($row['Employee Name']); ?></td>
                  <td class="c"><?php echo is_numeric($avg) ? '<span class="mu-pill info">'.number_format($avg, 2).'</span>' : '<span class="mu-zero">-</span>'; ?></td>
                </tr>
<?php }
$final_avg = $valid_count > 0 ? round($total_avg, 2) : 0;
?>
              </tbody>
              <tfoot><tr><td colspan="2">Total</td><td class="c"><?php echo number_format($final_avg, 2); ?></td></tr></tfoot>
            </table>
          </div>
        </section>
<?php }?>
      </div>
      <!-- Footer Section -->
     
  
	
	 <!-- Footer Section -->
     <?php echo footer(); ?>

    </div>
 



 
    



<!-- Footer Section -->
<?php echo scripts(); ?>	


<?php 
}
else
{
	header("location:404.php");
}
?>

	<script type="text/javascript">
			$(document).ready(function(){
    $('#dep_id').on('change',function(){
        var dep_id = $(this).val();
        if(dep_id){
            $.ajax({
                type:'POST',
                url:'get_consolidated_users.php',
                data:'dept_id='+dep_id,
                success:function(html){
                    $('#user_id').html(html);                    
                }
            }); 
        }else{
            $('#user_id').html('<option value="">Select User first</option>');
            
        }
    });

	
	});
		</script>

<?php mu_foot(); ?>
  </body>
</html>
