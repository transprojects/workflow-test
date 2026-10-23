<?php include "includes/includes.php";
session_start();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>	

    <!-- Data Table CSS check- -->

    <!-- Page body starts -->
    <style>
      html {
        scrollbar-width: thin;
      }

      ::-webkit-scrollbar-thumb {
        width: 5px !important;
      }

      ::-webkit-scrollbar-track {
        color: #666;
      }

      body {
        font-family: "Open Sans";
        height: fit-content;
        scrollbar-width: 5px;
      }
      .wholeouter {
        height: fit-content;
      }

      .bar {
        width: 100%;
        height: 15px;
        background-color: #f0edeb;
        border-radius: 100px;
        display: flex;
        align-items: center;
      }

       .progress {
        width: 100%;
        width: 15px;
        height: 15px;
        background-color: rgb(250, 247, 247);
        border-radius: 100px;
        position: relative;
        overflow: visible;
		padding-left:15px;
      }
	  .cprogress {
         width: 100%;
        width: 15px;
        height: 15px;
        background-color: rgb(250, 247, 247);
        border-radius: 100px;
        position: relative;
        overflow: visible;
		padding-left:15px;
      }
	   .rprogress {
        width: 100%;
        width: 15px;
        height: 15px;
        background-color: rgb(250, 247, 247);
        border-radius: 100px;
        position: relative;
        overflow: visible;
		padding-left:15px;
      }
		.fprogress {
        width: 100%;
        width: 15px;
        height: 15px;
        background-color: rgb(250, 247, 247);
        border-radius: 100px;
        position: relative;
        overflow: visible;
		padding-left:15px;
      }

      .footer {
        margin-top: 0;
      }

      input {
        margin-bottom: 30px;
      }

      .showmorebtn {
        height: fit-content;
        width: clamp(60px, 8.333vw, 120px);
        border: 1px solid #ff8801;
        background: transparent;
        border-radius: 8px;
        font-weight: 700;
        font-size: 14px;
        color: #ff8801;
      }

      .searchbtn {
        height: 46px;
        width: clamp(60px, 8.333vw, 120px);
        border: 1px solid #ff8801;
        background: transparent;
        border-radius: 8px;
        font-weight: 700;
        font-size: 14px;
        color: #ff8801;
      }
      .statusnot {
        height: 30px;
        width: 98px;
        background-color: #ffdbdb;
        color: #f22626;
        font-size: 12px;
        font-weight: 600;
        border-radius: 24px;
      }

      .complete {
        height: 30px;
        width: 98px;
        background-color: rgba(87, 246, 103, 0.32);
        color: #058212;
        font-size: 12px;
        font-weight: 600;
        border-radius: 24px;
      }

      .Inprogress {
        height: 30px;
        width: 98px;
        background-color: rgba(255, 187, 86, 0.29);
        color: #ff9900;
        font-size: 12px;
        font-weight: 600;
        border-radius: 24px;
      }

      th,
      td {
        border: 1px solid #cbcbd8;
        padding: 14px;
      }
      .tabclr {
        color: #a8a9bb;
      }

      .analyticdiv {
        height: 132px;
        width: 48%;
        display: flex;
        justify-content: center;
        align-items: center;
        border-radius: 16px;
        border: 1px solid #cbcbd8;
        justify-content: space-around;
      }

	      .percestyl {
        margin-left: 30px;
      }

      @media (max-width: 768px) {
        .analyticdiv {
          width: 100%;
        }

        .percestyl {
          margin-left: 0px;
        }
      }					  
      .dashboardselect {
        padding: 14px 8px;
        border-radius: 8px;
        width: clamp(80px, 10vw, 146px);
        border-color: #cbcbd8;
        background: #fff;
        color: #666;
        font-weight: 600;
        accent-color: #f26722;
      }

      option {
        width: 100px !important;
      }

      .show {
        display: block;
      }

      .hide {
        display: none;
      }

      .dateselection {
        height: 46px;
        width: clamp(130px, 10vw, 162px);
        border-radius: 8px;
        color: #666;
        padding: 5px;
		margin-bottom: 0px;
      }

      .analytictitle {
        font-size: 1.5em;
        font-weight: 600;
      }

      .analyticpara {
        font-size: 0.875em;
        color: #a8a9bb;
      }
      .group1 {
        scrollbar-width: thin;
        scrollbar-color: #666;
      }
      .group1::-webkit-scrollbar {
        width: 4px !important;
      }
      .group1::-webkit-scrollbar-thumb {
        background: #666;
      }
      .group2 {
        scrollbar-width: thin;
        scrollbar-color: #666;
      }
      .group2::-webkit-scrollbar {
        width: 4px !important;
      }
      .group2::-webkit-scrollbar-thumb {
        background: #666;
      }
	    

      .percentage {
        height: 28px;
        width: 28px;
        position: absolute;
        font-weight: bold;
		font-size:11px;
        color: aliceblue;
        right: 0px;
        -webkit-box-shadow: 0px 0px 2px 4px rgba(168, 168, 168, 1);
        -moz-box-shadow: 0px 0px 2px 4px rgba(168, 168, 168, 1);
        box-shadow: 0px 0px 2px 4px rgba(168, 168, 168, 1);
        background: #ffffffd0;
        color: #181818;
        z-index: 500;
        border-radius: 50%;
        top: -7px;
        display: flex;
        justify-content: center;
        align-items: center;
      }
	  .cpercentage {
        height: 28px;
        width: 28px;
        position: absolute;
        font-weight: bold;
		font-size:11px;
        color: aliceblue;
        right: 0px;
        -webkit-box-shadow: 0px 0px 2px 4px rgba(168, 168, 168, 1);
        -moz-box-shadow: 0px 0px 2px 4px rgba(168, 168, 168, 1);
        box-shadow: 0px 0px 2px 4px rgba(168, 168, 168, 1);
        background: #ffffffd0;
        color: #181818;
        z-index: 500;
        border-radius: 50%;
        top: -7px;
        display: flex;
        justify-content: center;
        align-items: center;
      }
	   .rpercentage {
        height: 28px;
        width: 28px;
        position: absolute;
        font-weight: bold;
		font-size:11px;
        color: aliceblue;
        right: 0px;
        -webkit-box-shadow: 0px 0px 2px 4px rgba(168, 168, 168, 1);
        -moz-box-shadow: 0px 0px 2px 4px rgba(168, 168, 168, 1);
        box-shadow: 0px 0px 2px 4px rgba(168, 168, 168, 1);
        background: #ffffffd0;
        color: #181818;
        z-index: 500;
        border-radius: 50%;
        top: -7px;
        display: flex;
        justify-content: center;
        align-items: center;
      }
	  .fpercentage {
        height: 28px;
        width: 28px;
        position: absolute;
        font-weight: bold;
		font-size:11px;
        color: aliceblue;
        right: 0px;
        -webkit-box-shadow: 0px 0px 2px 4px rgba(168, 168, 168, 1);
        -moz-box-shadow: 0px 0px 2px 4px rgba(168, 168, 168, 1);
        box-shadow: 0px 0px 2px 4px rgba(168, 168, 168, 1);
        background: #ffffffd0;
        color: #181818;
        z-index: 500;
        border-radius: 50%;
        top: -7px;
        display: flex;
        justify-content: center;
        align-items: center;
      }
	  
	        .chart-text {
        font-size: 0.5em;
        font-weight: 600;
        color: #222;
        -moz-transform: translateY(0.4em);
        -ms-transform: translateY(0.4em);
        -webkit-transform: translateY(0.4em);
        transform: translateY(0.4em);
      }

      .chart-number {
        -moz-transform: translateX(0em);
        -ms-transform: translateX(0em);
        -webkit-transform: translateX(0em);
        transform: translateX(0em);
      }
	  
      .info-container {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
}


        @keyframes slideIn {
            from {
                transform: translateX(50px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
		.production-time {
		background-color: #28a745; /* green */
		color: white;
		}
		.working-time {
		background-color: #007bff; /* blue */
		color: white;
		}
.info-box {
    padding: 15px;
    border-radius: 10px;
    font-size: 16px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 120px;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

/* Color classes */
.bg-yellow { background-color: #ffc107; color: #000; }
.bg-green  { background-color: #28a745; color: #fff; }
.bg-blue   { background-color: #007bff; color: #fff; }
.bg-red    { background-color: #dc3545; color: #fff; }
.bg-purple { background-color: #6f42c1; color: #fff; }

.info-days {
    background-color: #ffc107; /* Amber */
    color: #000;
}

.production-time {
    background-color: #28a745; /* Green */
    color: #fff;
}

.working-time {
    background-color: #007bff; /* Blue */
    color: #fff;
}

.nonworkflow-time {
    background-color: #dc3545; /* Red */
    color: #fff;
}
.percentage_all-container {
    background-color: #e9f8e4; /* Light green */
    padding: 8px 12px;
    border-radius: 10px;
    display: inline-block;
}

    </style>
  </head>
  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
	 
<?php 

$user_target_dashboard=mysqli_fetch_array(mysqli_query($db,"select *,count(*) as tc from user_target_assign where user_id='".$_REQUEST['user_id']."' "));



			 //$start_date = date('Y-m-d', strtotime('-5 day'));
			// $end_date =date('Y-m-d', strtotime('-5 day'));
			
			 //$start_date = date('Y-m-d');
			 //$end_date = date('Y-m-d');
			 
	// Get the start date and end date from the request and convert them to DateTime objects
	
	if(isset($_REQUEST['fdate']))
	{
$start_date =  date('Y-m-d', strtotime($_REQUEST['fdate']));
$end_date = date('Y-m-d', strtotime($_REQUEST['tdate']));


// Convert dates to timestamps
$start_timestamp = strtotime($_REQUEST['fdate']);
$end_timestamp = strtotime($_REQUEST['tdate']);



// If end date is before start date, swap them
if ($end_timestamp < $start_timestamp) {
    $temp = $start_timestamp;
    $start_timestamp = $end_timestamp;
    $end_timestamp = $temp;
}

// Calculate the difference in seconds
$time_difference = $end_timestamp - $start_timestamp;

// Convert the difference to days
$days_difference = $time_difference / (60 * 60 * 24);

// Round the result to ensure it's an integer
$days_difference = round($days_difference)+1;
//echo "Number of days between the two dates: $days_difference";


	}
	else
	{
	 $start_date = date('Y-m-d');
	 $end_date = date('Y-m-d');	
	 
	  // Convert dates to timestamps
$start_timestamp = strtotime($start_date);
$end_timestamp = strtotime($end_date);



// If end date is before start date, swap them
if ($end_timestamp < $start_timestamp) {
    $temp = $start_timestamp;
    $start_timestamp = $end_timestamp;
    $end_timestamp = $temp;
}

// Calculate the difference in seconds
$time_difference = $end_timestamp - $start_timestamp;

// Convert the difference to days
$days_difference = $time_difference / (60 * 60 * 24);

// Round the result to ensure it's an integer
$days_difference = round($days_difference)+1;
//echo "Number of days between the two dates: $days_difference";
	}

if ($_REQUEST['dept_id'] == 'all') {
    // No department filter
    $user_condition = "and inw.process_user = '" . $_REQUEST['userr_id'] . "'";
    $user_condition_target = "and user_id = '" . $_REQUEST['user_id'] . "'";
} else
{
    // Filter by exact dept_id
    $user_condition = "and inw.dept = '" . $_REQUEST['dept_id'] . "' and inw.process_user = '" . $_REQUEST['user_id'] . "'";
    $user_condition_target = "and  user_id = '" . $_REQUEST['user_id'] . "' and dept_id='".$_REQUEST['dept_id']."'";
	
}

if ($_REQUEST['department_platform']!= '') {
    // No department filter
    $user_platform = "and j.j_platform='" . $_REQUEST["department_platform"] . "' ";
} else
{
    // Filter by exact dept_id
    $user_platform = "";
}
echo $ccc .= "";


?>	 
   
<!-- body section -->
      <div class="container-fluid">
        <div class="row justify-content-left">
          <div
            class="col-12 col-md-9 group1"
            style="height: 80vh; overflow-y: scroll"
          >
		   <div
              class="container-fluid d-flex align-items-center ps-4 pe-4"
              style="
                margin-top: 32px;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 10px;
              "
            >
              <h2>Dashboard</h2>
              <div class="mt-2 mt-md-0"					
                style="
                  position: relative;
                  width: fit-content;
                  height: 45px;
                  padding: 12px 16px;
                  display: flex;
                  align-items: center;
                  right: -36px;
                  box-shadow: 0 0 5px 1px rgb(177, 177, 177);
                  background: #fff;
                  color: #222;
                  border-radius: 12px; justify-content: center;
                "
              >
			  
			           <p class="m-0" style="font-weight: 600">
				    Overall Target -  <span class="percentage_all-container">
					<span class="percentage_all" style="font-size: 1.2em; color: #74d84c; font-weight: 600;">0</span>%</span>
				</p>

                
              
                
              </div>
            </div>
	<form method="post" name="search_target" action="admindashboard">
  <div class="container-fluid ps-md-4 pe-md-4" style="margin-top: 32px;">
    <div class="d-flex flex-nowrap gap-3 align-items-center"> <!-- Ensuring vertical center alignment -->

      <!-- Employee -->
      <div class="flex-fill" style="min-width: 200px;">
        <label class="mb-0">Choose Employee</label>
        <select class="form-select department_drop" name="user_id" id="user_id" required>
          <option value="">Select Employee</option>
          <?php
          $sql_users = $auth->showUserlist();
          foreach ($sql_users as $res_users) {
          ?>
            <option value="<?php echo $res_users['id'] ?>" <?php if ($_REQUEST['user_id'] == $res_users['id']) echo 'selected'; ?>>
              <?php echo $res_users['full_name'] ?>
            </option>
          <?php } ?>
        </select>
      </div>

      <!-- Department -->
      <div class="flex-fill" style="min-width: 180px;">
        <label class="mb-0">Choose Department</label>
      <select class="form-select department_drop" name="dept_id" id="dept_id" required>
  <?php
 if (isset($_REQUEST['user_id'])) {
  $user_id = $_REQUEST['user_id'];
  $selected_dept = isset($_REQUEST['dept_id']) ? $_REQUEST['dept_id'] : '';
  $user = $auth->getUserById($user_id);

  if ($user) {
    $departments = [];

    if (!empty($user['department_id'])) $departments[] = $user['department_id'];
    if (!empty($user['access_other_depts'])) {
      $departments = array_merge($departments, explode(',', $user['access_other_depts']));
    }

    $departments = array_unique($departments);
    $dept_details = $auth->getDepartmentsByCodes($departments);

    echo '<option value="">Select Department</option>';

    if ($user['department_id'] == 1) {
      $combined_depts = [1, 2, 22];
      $combined_names = [];

      foreach ($dept_details as $dept) {
        if (in_array($dept['dept_code'], $combined_depts)) {
          $combined_names[] = $dept['dept_name'];
        }
      }

      if (!empty($combined_names)) {
        $combined_value = '1';
        $selected = ($selected_dept == $combined_value) ? 'selected' : '';
        $label = implode(' / ', $combined_names) . ' (Primary Department)';
        echo "<option value='{$combined_value}' style='color: black; background-color: yellow;' {$selected}>{$label}</option>";
      }

      foreach ($dept_details as $dept) {
        if (!in_array($dept['dept_code'], $combined_depts)) {
          $selected = ($dept['dept_code'] == $selected_dept) ? 'selected' : '';
          echo "<option value='{$dept['dept_code']}' {$selected}>{$dept['dept_name']}</option>";
        }
      }
    } else {
      foreach ($dept_details as $dept) {
        $is_primary = ($dept['dept_code'] == $user['department_id']);
        $style = $is_primary ? "style='color: black; background-color: yellow;'" : '';
        $label = $dept['dept_name'] . ($is_primary ? ' (Primary Department)' : '');
        $selected = ($dept['dept_code'] == $selected_dept) ? 'selected' : '';
        echo "<option value='{$dept['dept_code']}' {$style} {$selected}>{$label}</option>";
      }
    }
  } else {
    echo '<option value="">No Department Found</option>';
  }
} else {
  echo '<option value="">Select Department</option>';
}

  ?>
</select>

      </div>

      <!-- Platform (Only for dept 10) -->
      <div id="extra-dropdown" class="flex-fill" style="min-width: 150px; display: none;">
        <label class="mb-0">Platform</label>
        <select class="form-select" name="department_platform">
            <option value="">Select Platform</option>
			<option value="3B2" <?php if($_REQUEST['department_platform'] =="3B2"){?> selected <?php } ?>>3B2</option>
			<option value="INDESIGN" <?php if($_REQUEST['department_platform'] =="INDESIGN"){?> selected <?php } ?>>INDESIGN</option>
			<option value="LATEX" <?php if($_REQUEST['department_platform'] =="LATEX"){?> selected <?php } ?>>LATEX</option>
		</select>
      </div>

      <!-- From Date -->
      <div class="flex-fill" style="min-width: 160px;">
        <label class="mb-0">From Date</label>
        <input class="form-control dateselection" type="date" name="fdate" value="<?php echo $start_date; ?>" />
      </div>

      <!-- To Date -->
      <div class="flex-fill" style="min-width: 160px;">
        <label class="mb-0">To Date</label>
        <input class="form-control dateselection" type="date" name="tdate" value="<?php echo $end_date; ?>" />
      </div>

      <!-- Search Button -->
      <div style="min-width: 120px;">
			<button class="searchbtn" type="submit" style="margin-top: 30px;">Search</button>
			<button class="refreshbtn ms-4"><a href="admindashboard"> <img src="./assets/images/refresh.svg"></a></button>
	</div>

    </div>
  </div>
</form>


           
			<?php 
			if(isset($_REQUEST['dept_id']))
			{
			?>
          <div class="justify-content-center flex-column mt-4 monthly1outer p-4" style=" border-radius: 16px; padding: 24px; margin: 20px;height: fit-content;">
		  
		              <div class="d-flex align-items-center" style="justify-content: space-between">
              <p style="font-size: 20px; font-weight: 600">Target Acheived </p>
              <!--<button class="showmorebtn p-2">Show More</button>-->
            </div>
            <div class="row d-flex align-items-center">
              <div class="col-12 col-md-4">
                 <!--<p style="font-size: 64px; font-weight: 600"><?php echo $completed_count_fp_pc+$completed_count_corr_pc+$completed_count_rev_pc+$completed_count_fin_pc;?> / <?php echo ($average_fp_pc+$average_cor_pc+$average_rev_pc+$average_fin_pc)/4; ?></p>-->
              </div>

              <div class="col-12 col-md-8 mt-3 mb-3 m-md-0" style="position: relative">
			  <div class="bar">
               <div class="progress">
			   <?php 
			   if($_REQUEST['dept_id']==0 || $_REQUEST['dept_id']==1 || $_REQUEST['dept_id']==22)
			   {
				?>
				                    <div class="percentage"><?php echo $target_percentage_fp;?>  </div>

				<?php 
			   }
			   else
			   {
			   ?>
			                       <div class="percentage"></div>

			   <?php 
			   }
			   ?>
               </div>

                <div class="d-flex pt-2 ps-2 pe-3" style="position: absolute;justify-content: space-between;width: 100%;top: 8px;left:0;font-size: 14px;color: #9c9dad;">
                  <p>0%</p>
                  <p>50%</p>
                  <p>100%</p>
                </div>
              </div>
			 </div>
           </div>
			
			
            
			
			
          <!-- <div class="report mt-3">
              <h5 style="font-weight: 600">Target Details</h5>

              <table style="width: 100%; text-align: center; margin-top: 24px">
                <thead>
                  <tr>
                    <th rowspan="2" style="color: #a8a9bb">Customers</th>
                    <th colspan="2">First Proof</th>
                    <th colspan="2">corrections</th>
                    <th colspan="2">Revises</th>
                    <th colspan="2">Final</th>
                  </tr>
                  <tr>
                    <th rowspan="2" class="tabclr">Article</th>
                    <th rowspan="2" class="tabclr">Page</th>
                    <th rowspan="2" class="tabclr">Article</th>
                    <th rowspan="2" class="tabclr">Page</th>
                    <th rowspan="2" class="tabclr">Article</th>
                    <th rowspan="2" class="tabclr">Page</th>
                    <th rowspan="2" class="tabclr">Article</th>
                    <th rowspan="2" class="tabclr">Page</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>TSP</td>
                    <td>4</td>
                    <td>80</td>
                    <td>4</td>
                    <td>80</td>
                    <td>4</td>
                    <td>80</td>
                    <td>4</td>
                    <td>80</td>
                  </tr>
                  <tr>
                    <td class="tabclr">Total</td>
                    <td class="tabclr">0</td>
                    <td class="tabclr">0</td>
                    <td class="tabclr">0</td>
                    <td class="tabclr">0</td>
                    <td class="tabclr">0</td>
                    <td class="tabclr">0</td>
                    <td class="tabclr">0</td>
                    <td class="tabclr">0</td>
                  </tr>
                </tbody>
              </table>
            </div>-->
          </div>
		  
		  

		  <?php 
		 // if(isset($_REQUEST['dept_id']))
		 // {
		  
		  
		    // Initialize total pages completed and target pages
        $total_pages_completed = 0;
        $total_target_pages = 0;
		

// Assume $start_date and $end_date are set or dynamically calculated


// Create DateTime objects from the start and end dates
$start = new DateTime($start_date);
$end = new DateTime($end_date);
 
// Calculate the difference between the two dates
$interval = $start->diff($end);
$daysDifference = $interval->days + 1;

// Connect DB here
// $db = mysqli_connect(...);

$user_id = $_REQUEST['user_id'];
			if($_REQUEST['dept_id']!='1' && $_REQUEST['dept_id']!='2' && $_REQUEST['dept_id']!='22')
			{
			$dept_code_s="AND inw_transactions.dept IN ('".$_REQUEST['dept_id']."')";
			}
			else
			{
			$dept_code_s="AND inw_transactions.dept IN ('1','2','22')";
			}
$sql_time = "SELECT 
        SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND, start_time, end_time))) AS total_working_time,
        SEC_TO_TIME(SUM(CASE 
            WHEN current_status IN ('Completed','Assign','To Cross Check','Need Clarification','Query','Pending','Correction Done','Break','Cross Checking' ) 
            THEN TIMESTAMPDIFF(SECOND, start_time, end_time) 
            ELSE 0 
        END)) AS total_production_time
    FROM inw_transactions 
    WHERE process_user = '$user_id'
    AND DATE_FORMAT(start_time, '%Y-%m-%d') >= '$start_date'
    AND DATE_FORMAT(end_time, '%Y-%m-%d') <= '$end_date'
	$dept_code_s";

$result_time = mysqli_query($db, $sql_time);
$row_time = mysqli_fetch_assoc($result_time);

$total_working_time = $row_time['total_working_time'];
$total_production_time = $row_time['total_production_time'];
		  $non_workflow_percentage=mysqli_fetch_array(mysqli_query($db,"select nw_percentage, nw_pt AS total_nw_pt_seconds from non_workflow_user_percentage where user_id= '".$_REQUEST['user_id']."' and nw_date>='".date('Y-m-d', strtotime($_REQUEST['fdate']))."' and nw_date<='".date('Y-m-d', strtotime($_REQUEST['tdate']))."'"));
		
		// Default to 0 if no data found
$raw_nw_percentage = isset($non_workflow_percentage['nw_percentage']) ? floatval($non_workflow_percentage['nw_percentage']) : 0;

// Divide by days if more than one day
if ($days_difference > 1) {
    $nw_percentage = round($raw_nw_percentage / $days_difference, 2);
} else {
    $nw_percentage = $raw_nw_percentage;
}
?>
<div class="info-container">
    <!-- Smaller width -->
    <div class="info-box bg-yellow" style="flex: 1 1 10%;">
        📅 Days: <?php echo $daysDifference; ?>
    </div>
    <div class="info-box bg-green" style="flex: 1 1 20%;">
        ⏱ Production Time: <?php echo $total_production_time ? $total_production_time : '00:00:00'; ?>
    </div>
    <div class="info-box bg-blue" style="flex: 1 1 20%;">
        ⏱ Working Time: <?php echo $total_working_time ? $total_working_time : '00:00:00'; ?>
    </div>
    <!-- Bigger width -->
    <div class="info-box bg-red" style="flex: 2 1 25%;">
        ⏱ Non Workflow Time: <?php echo $non_workflow_percentage['total_nw_pt_seconds'] ? $non_workflow_percentage['total_nw_pt_seconds'] : '00:00:00'; ?>
    </div>
    <div class="info-box bg-purple" style="flex: 1 1 15%;">
        🚫 Non Workflow %: <?php echo $nw_percentage ?? '0%'; ?>
    </div>
</div>



<?php 
$department_id=$_REQUEST['dept_id'];

  if($_REQUEST['dept_id']!='1' && $_REQUEST['dept_id']!='2' && $_REQUEST['dept_id']!='22')
		  {
		  
		  
		    // Initialize total pages completed and target pages
        $total_pages_completed = 0;
        $total_target_pages = 0;

        // Initialize stage-wise and customer-wise data
        $stage_totals = [
            'FP' => ['completed' => 0, 'target' => 0],
            'PRECLEAN' => ['completed' => 0, 'target' => 0],
            'REV' => ['completed' => 0, 'target' => 0],
            'CORR' => ['completed' => 0, 'target' => 0],
            'FIN' => ['completed' => 0, 'target' => 0],
            'ISS' => ['completed' => 0, 'target' => 0]
        ];
        $customer_data = [];
        // Loop through user target customers and calculate pages for different stages
        $user_target_customer = $db->query("SELECT * FROM user_target_customer_assign INNER JOIN adm_customer_master ON user_target_customer_assign.cust_id = adm_customer_master.id LEFT JOIN user_target_assign ON user_target_customer_assign.uta_id = user_target_assign.uta_id WHERE user_target_customer_assign.user_id='" . $_REQUEST['user_id'] . "' AND dept_id='".$_REQUEST['dept_id']."'");
        while ($res_user_target = $user_target_customer->fetch_assoc()) {
            $customer_id = $res_user_target['id'];
            $customer_name = $res_user_target['cust_name'];

            if (!isset($customer_data[$customer_id])) {
                $customer_data[$customer_id] = [
                    'name' => $customer_name,
                    'stages' => [
                        'FP' => ['completed' => 0, 'target' => 0],
                        'PRECLEAN' => ['completed' => 0, 'target' => 0],
                        'REV' => ['completed' => 0, 'target' => 0],
                        'CORR' => ['completed' => 0, 'target' => 0],
                        'FIN' => ['completed' => 0, 'target' => 0],
						'ISS' => ['completed' => 0, 'target' => 0]
                    ]
                ];
            }
	
            $stages = ['FP' => 'page_count', 'REV' => 'rpc', 'CORR' => 'cpc', 'FIN' => 'fpc', 'ISS' => 'ipc'];

            foreach ($stages as $stage => $target_field) {
             // Special logic for the ISS stage
    if ($stage == 'ISS') {
        // For ISS, get pages_completed for the first occurrence of the ISS stage
        $query = "
            WITH FirstOccurrences AS (
                SELECT 
                    inw.project_id,
					 CASE 
                WHEN inw.pages_completed IS NOT NULL AND inw.pages_completed != 0 THEN inw.pages_completed
                WHEN wd.page_count IS NOT NULL AND wd.page_count != 0 THEN wd.page_count
                ELSE wd.manuscript_count
            END AS pages_completed,
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
                    AND current_stage LIKE '%ISS%'
                    AND current_status = 'Completed'
                    $user_condition
            )
            SELECT 
                SUM(pages_completed) AS pages_completed
            FROM 
                FirstOccurrences
            WHERE 
                rn = 1
        ";
    }elseif ($stage == 'PRECLEAN') {
        // For PRECLEAN, get pages_completed for the first occurrence of the PRECLEAN stage
        $query = "
            WITH FirstOccurrences AS (
                SELECT 
                    inw.project_id,
					 CASE 
                WHEN inw.pages_completed IS NOT NULL AND inw.pages_completed != 0 THEN inw.pages_completed
                WHEN wd.page_count IS NOT NULL AND wd.page_count != 0 THEN wd.page_count
                ELSE wd.manuscript_count
            END AS pages_completed,
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
                    AND current_stage LIKE '%FP%'
					AND inw.dept='22'
                    AND current_status = 'Completed'
                    AND inw.process_user= '".$_REQUEST['user_id']."'  
            )
            SELECT 
                SUM(pages_completed) AS pages_completed
            FROM 
                FirstOccurrences
            WHERE 
                rn = 1
        ";
    } else {
        // Normal stage query (FP, REV, CORR, FIN)
       // $status_condition = ($stage == 'CORR') ? "inw.current_status = 'Correction Done'" : "current_status = 'Completed'";
      //  $status_condition = ($stage == 'CORR') ? "inw.current_status = 'Correction Done'" : "current_status in ('Completed','Assign','To Cross Check')";
     //   $stage_condition = ($stage != 'CORR') ? "AND current_stage LIKE '%$stage%'" : "";
	 
	 $allowed_departments = ['14', '9'];

$status_condition = ($stage == 'CORR')
    ? "inw.current_status = 'Correction Done'"
    : "inw.current_status IN (" . (
        in_array($department_id, $allowed_departments)
            ? "'Completed', 'Assign', 'To Cross Check','Break'"
            : "'Completed', 'To Cross Check'"
    ) . ")";

// Updated stage condition with PMC for FIN stage
if ($stage == 'CORR') {
    $stage_condition = "";
} elseif ($stage == 'FIN') {
    $stage_condition = "AND (current_stage LIKE '%FIN%' OR current_stage LIKE '%PMC%')";
} else {
    $stage_condition = "AND current_stage LIKE '%$stage%'";
}

        $query = "
            WITH FirstOccurrences AS (
                SELECT 
                    inw.project_id,
                    CASE 
                WHEN inw.pages_completed IS NOT NULL AND inw.pages_completed != 0 THEN inw.pages_completed
                WHEN wd.page_count IS NOT NULL AND wd.page_count != 0 THEN wd.page_count
                ELSE wd.manuscript_count
            END AS pages_completed,
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
            )
            SELECT 
                SUM(pages_completed) AS pages_completed
            FROM 
                FirstOccurrences
            WHERE 
                rn = 1
        ";
    }

    // Execute the query and fetch results
    $result = $db->query($query) or die($db->error);
    $pages_completed = ($result->fetch_assoc()['pages_completed']) ?? 0;
    $target_pages = ($res_user_target[$target_field] != "0") ? $res_user_target[$target_field] : 1;

    // Accumulate stage-wise totals
    $total_pages_completed += $pages_completed;
    $total_target_pages += $target_pages;

    $stage_totals[$stage]['completed'] += $pages_completed;
    $stage_totals[$stage]['target'] += $target_pages;

    $customer_data[$customer_id]['stages'][$stage]['completed'] = $pages_completed;
    $customer_data[$customer_id]['stages'][$stage]['target'] = $target_pages;
	
	
            }
        }

        // Calculate the total percentage by summing the stage percentages
      /*  $total_percentage = 0;
        foreach ($stage_totals as $stage => $totals) {
            $total_percentage += ($totals['target'] > 0) ? round(($totals['completed'] / $totals['target']) * 100, 2) : 0;
        }
		*/
		
		  // Calculate the total percentage by summing the stage percentages for all customers
        $total_percentage = 0;
        foreach ($customer_data as $customer_id => $data) {
            $customer_total_percentage = 0;
            foreach ($data['stages'] as $stage => $totals) {
                $customer_total_percentage += ($totals['target'] > 0) ? round(($totals['completed'] / $totals['target']) * 100, 2) : 0;
            }
            $total_percentage += $customer_total_percentage;
        }

        // Output the overall results
        echo "<div class='analyticsouter monthly1outer' style='border-radius: 16px;padding: 24px;margin: 20px;height: fit-content;'>
            <div class='d-flex' style='justify-content: space-between; align-items: center;'>
              <h5>Analytics</h5>
            </div>
            <div class='d-flex justify-content-center' style='flex-wrap: wrap; gap: 16px; margin-top: 32px'>";



		$stages = [
            'FP' => ['title' => 'First Proof', 'color' => '#3c6ed1'],
            'REV' => ['title' => 'Revises', 'color' => '#ffab26'],
            'CORR' => ['title' => 'Corrections', 'color' => '#ff0000'],
            'FIN' => ['title' => 'Final/PMC', 'color' => '#0bb70b'],
            'ISS' => ['title' => 'Issue Correction', 'color' => '#0bb722']
        ];	
       

       foreach ($stages as $stage => $data) {
    $completed = $stage_totals[$stage]['completed'];
    $target = $stage_totals[$stage]['target'];
    //$percentage = ($target > 0) ? round(($completed / $target) * 100, 2) : 0;
    $percentage = ($target > 0) ? round(($completed / $target) * 100) : 0;

    // Initialize total percentage accumulator
    $total_cpercentage = 0;

    echo "<div class='analyticdiv d-flex' style='justify-content: space-evenly;height: fit-content;'>
            <div class='d-flex flex-column gap-3 p-3' style='min-width: 50px; width: 45%'>
              <p class='analytictitle'>{$data['title']}</p>
              <div style='display: flex;width: 100%;gap: 20px;flex-wrap: wrap;'>";

    foreach ($customer_data as $customer_id => $customer) {
        $customer_percentage = ($customer['stages'][$stage]['target'] > 0) ? round(($customer['stages'][$stage]['completed'] / $customer['stages'][$stage]['target']) * 100, 2) : 0;
        // Accumulate the customer percentages
        $total_cpercentage += $customer_percentage;

        echo "<div>{$customer['name']}<div>{$customer['stages'][$stage]['completed']} | {$customer_percentage}%</div></div>";
    }

    echo "    </div>
            </div>
            <div class='p-2'>
              <svg width='110px' height='130px' viewBox='0 0 42 42' class='donut'>
                <circle class='donut-hole' cx='24' cy='21' r='15.91549430918954' fill='#fff'></circle>
                <circle class='donut-ring' cx='24' cy='21' r='15.91549430918954' fill='transparent' stroke='#eaeaea' stroke-width='2'></circle>
                <circle class='donut-segment' cx='24' cy='21' r='15.91549430918954' fill='transparent' stroke='{$data['color']}' stroke-width='2' stroke-dasharray='{$total_cpercentage} " . (100 - $total_cpercentage) . "' stroke-dashoffset='25'></circle>
                <g class='chart-text'>
                  <text x='40%' y='50%' class='chart-number'>{$total_cpercentage}%</text>
                </g>
              </svg>
            </div>
          </div>";
}

$non_workflow_percentage=mysqli_fetch_array(mysqli_query($db,"select nw_percentage from non_workflow_user_percentage where user_id= '".$_REQUEST['user_id']."' and nw_date>='".date('Y-m-d', strtotime($_REQUEST['fdate']))."' and nw_date<='".date('Y-m-d', strtotime($_REQUEST['tdate']))."'"));
		
		$overall_percentage = round(($total_percentage + $non_workflow_percentage['nw_percentage']) / $days_difference, 2);
        echo "</div></div>			  <input type='hidden' class='inputprogress' value='$overall_percentage' min='0' max='100' style='position:absolute;top:40px;'>";
      //  echo "</div></div>			  <input type='hidden' class='inputprogress' value='$total_percentage' min='0' max='100' style='position:absolute;top:40px;'>";
   
		  }
		  else
		  {
		 	    // Initialize total pages completed and target pages
        $total_pages_completed = 0;
        $total_target_pages = 0;

        // Initialize stage-wise and customer-wise data
        $stage_totals = [
            'FP' => ['completed' => 0, 'target' => 0],
            'PRECLEAN' => ['completed' => 0, 'target' => 0],
            'REV' => ['completed' => 0, 'target' => 0],
            'REV' => ['completed' => 0, 'target' => 0],
            'CORR' => ['completed' => 0, 'target' => 0],
            'FIN' => ['completed' => 0, 'target' => 0],
            'ISS' => ['completed' => 0, 'target' => 0]
        ];
        $customer_data = [];

        // Loop through user target customers and calculate pages for different stages
        $user_target_customer = $db->query("SELECT * FROM user_target_customer_assign INNER JOIN adm_customer_master ON user_target_customer_assign.cust_id = adm_customer_master.id  LEFT JOIN user_target_assign ON user_target_customer_assign.uta_id = user_target_assign.uta_id WHERE user_target_customer_assign.user_id='" . $_REQUEST['user_id'] . "' AND dept_id='".$_REQUEST['dept_id']."'");
        while ($res_user_target = $user_target_customer->fetch_assoc()) {
            $customer_id = $res_user_target['id'];
            $customer_name = $res_user_target['cust_name'];

            if (!isset($customer_data[$customer_id])) {
                $customer_data[$customer_id] = [
                    'name' => $customer_name,
                    'stages' => [
                        'FP' => ['completed' => 0, 'target' => 0],
                        'PRECLEAN' => ['completed' => 0, 'target' => 0],
                        'REV' => ['completed' => 0, 'target' => 0],
                        'CORR' => ['completed' => 0, 'target' => 0],
                        'FIN' => ['completed' => 0, 'target' => 0],
						'ISS' => ['completed' => 0, 'target' => 0]
                    ]
                ];
            }
	
            $stages = ['FP' => 'page_count','PRECLEAN' => 'ppage_count'];
	
            foreach ($stages as $stage => $target_field) {
             // Special logic for the ISS stage
  if ($stage == 'PRECLEAN') {
        // For PRECLEAN, get pages_completed for the first occurrence of the PRECLEAN stage
        $query = "
            WITH FirstOccurrences AS (
                SELECT 
                    inw.project_id,
					 CASE 
                WHEN inw.pages_completed IS NOT NULL AND inw.pages_completed != 0 THEN inw.pages_completed
                WHEN wd.page_count IS NOT NULL AND wd.page_count != 0 THEN wd.page_count
                ELSE wd.manuscript_count
            END AS pages_completed,
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
                    AND current_stage LIKE '%FP%'
					AND inw.dept='22'
					AND current_status in ('Completed','To Cross Check','Correction Done', 'Break')
                    AND inw.process_user='".$_REQUEST['user_id']."'
            )
            SELECT 
                SUM(pages_completed) AS pages_completed
            FROM 
                FirstOccurrences
            WHERE 
                rn = 1
        ";
    } else {
        // Normal stage query (FP, REV, CORR, FIN)
       // $status_condition = ($stage == 'CORR') ? "inw.current_status = 'Correction Done'" : "current_status = 'Completed'";
       // $status_condition = ($stage == 'CORR') ? "inw.current_status = 'Correction Done'" : "current_status in ('Completed','Assign','To Cross Check')";
      //  $stage_condition = ($stage != 'CORR') ? "AND current_stage LIKE '%$stage%'" : "";
	 $allowed_departments = ['14', '9'];

$status_condition = ($stage == 'CORR')
    ? "inw.current_status = 'Correction Done'"
    : "inw.current_status IN (" . (
        in_array($department_id, $allowed_departments)
            ? "'Completed', 'To Cross Check','Break'"
            : "'Completed', 'To Cross Check','Break'"
    ) . ")";

// Updated stage condition with PMC for FIN stage
if ($stage == 'CORR') {
    $stage_condition = "";
} elseif ($stage == 'FIN') {
    $stage_condition = "AND (current_stage LIKE '%FIN%' OR current_stage LIKE '%PMC%')";
} else {
    $stage_condition = "AND current_stage LIKE '%$stage%'";
}



        $query = "
            WITH FirstOccurrences AS (
                SELECT 
                    inw.project_id,
                    CASE 
                WHEN inw.pages_completed IS NOT NULL AND inw.pages_completed != 0 THEN inw.pages_completed
                WHEN wd.page_count IS NOT NULL AND wd.page_count != 0 THEN wd.page_count
                ELSE wd.manuscript_count
            END AS pages_completed,
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
					AND inw.dept in ('1','2')
					AND current_status in ('Completed','To Cross Check','Correction Done')
                    AND inw.process_user='".$_REQUEST['user_id']."'
                   
            )
            SELECT 
                SUM(pages_completed) AS pages_completed
            FROM 
                FirstOccurrences
            WHERE 
                rn = 1
        ";
    }

    // Execute the query and fetch results
    $result = $db->query($query) or die($db->error);
    $pages_completed = ($result->fetch_assoc()['pages_completed']) ?? 0;
    $target_pages = ($res_user_target[$target_field] != "0") ? $res_user_target[$target_field] : 1;

    // Accumulate stage-wise totals
    $total_pages_completed += $pages_completed;
    $total_target_pages += $target_pages;

    $stage_totals[$stage]['completed'] += $pages_completed;
    $stage_totals[$stage]['target'] += $target_pages;

    $customer_data[$customer_id]['stages'][$stage]['completed'] = $pages_completed;
    $customer_data[$customer_id]['stages'][$stage]['target'] = $target_pages;
	
	
            }
        }

        // Calculate the total percentage by summing the stage percentages
      /*  $total_percentage = 0;
        foreach ($stage_totals as $stage => $totals) {
            $total_percentage += ($totals['target'] > 0) ? round(($totals['completed'] / $totals['target']) * 100, 2) : 0;
        }
		*/
		
		  // Calculate the total percentage by summing the stage percentages for all customers
        $total_percentage = 0;
        foreach ($customer_data as $customer_id => $data) {
            $customer_total_percentage = 0;
            foreach ($data['stages'] as $stage => $totals) {
                $customer_total_percentage += ($totals['target'] > 0) ? round(($totals['completed'] / $totals['target']) * 100, 2) : 0;
            }
            $total_percentage += $customer_total_percentage;
        }

        // Output the overall results
        echo "<div class='analyticsouter monthly1outer' style='border-radius: 16px;padding: 24px;margin: 20px;height: fit-content;'>
            <div class='d-flex' style='justify-content: space-between; align-items: center;'>
              <h5>Analytics</h5>
            </div>
            <div class='d-flex justify-content-center' style='flex-wrap: wrap; gap: 16px; margin-top: 32px'>";


	
            $stages = [
            'FP' => ['title' => 'First Proof - CE/PE', 'color' => '#3c6ed1'],
            'PRECLEAN' => ['title' => 'First Proof - PC', 'color' => '#3c6ed1']
        ];


       foreach ($stages as $stage => $data) {
    $completed = $stage_totals[$stage]['completed'];
    $target = $stage_totals[$stage]['target'];
    //$percentage = ($target > 0) ? round(($completed / $target) * 100, 2) : 0;
    $percentage = ($target > 0) ? round(($completed / $target) * 100) : 0;

    // Initialize total percentage accumulator
    $total_cpercentage = 0;

    echo "<div class='analyticdiv d-flex' style='justify-content: space-evenly;height: fit-content;'>
            <div class='d-flex flex-column gap-3 p-3' style='min-width: 50px; width: 45%'>
              <p class='analytictitle'>{$data['title']}</p>
              <div style='display: flex;width: 100%;gap: 20px;flex-wrap: wrap;'>";

    foreach ($customer_data as $customer_id => $customer) {
        $customer_percentage = ($customer['stages'][$stage]['target'] > 0) ? round(($customer['stages'][$stage]['completed'] / $customer['stages'][$stage]['target']) * 100, 2) : 0;
        // Accumulate the customer percentages
        $total_cpercentage += $customer_percentage;

        echo "<div>{$customer['name']}<div>{$customer['stages'][$stage]['completed']} | {$customer_percentage}%</div></div>";
    }

    echo "    </div>
            </div>
            <div class='p-2'>
              <svg width='110px' height='130px' viewBox='0 0 42 42' class='donut'>
                <circle class='donut-hole' cx='24' cy='21' r='15.91549430918954' fill='#fff'></circle>
                <circle class='donut-ring' cx='24' cy='21' r='15.91549430918954' fill='transparent' stroke='#eaeaea' stroke-width='2'></circle>
                <circle class='donut-segment' cx='24' cy='21' r='15.91549430918954' fill='transparent' stroke='{$data['color']}' stroke-width='2' stroke-dasharray='{$total_cpercentage} " . (100 - $total_cpercentage) . "' stroke-dashoffset='25'></circle>
                <g class='chart-text'>
                  <text x='40%' y='50%' class='chart-number'>{$total_cpercentage}%</text>
                </g>
              </svg>
            </div>
          </div>";
}

		$non_workflow_percentage=mysqli_fetch_array(mysqli_query($db,"select nw_percentage from non_workflow_user_percentage where user_id= '".$_REQUEST['user_id']."' and  nw_date>='".date('Y-m-d', strtotime($_REQUEST['fdate']))."' and nw_date<='".date('Y-m-d', strtotime($_REQUEST['tdate']))."'"));
		
		$overall_percentage = round(($total_percentage + $non_workflow_percentage['nw_percentage']) / $days_difference, 2);
		
        echo "</div></div>			  <input type='hidden' class='inputprogress' value='$overall_percentage' min='0' max='100' style='position:absolute;top:40px;'>";
		  }
		  ?>
          </div>
		 
         <div
            class="col-3 d-none d-md-flex p-4 group2"
            style="
              flex-direction: column;
              background-color: rgba(255, 245, 235, 0.8);
              height: 80vh;
              overflow-y: auto;
            "
          >
            <h5>Allocated Article status</h5>
           <div
                class="row d-flex mt-4"
                style="border-bottom: 1px solid #d8d2cb"
              >
              <p class="col-8 pt-2 pb-2">Pub ID</p>
              <p class="col-4 pt-2 pb-2">Status</p>
            </div>
			<?php 
			if($_REQUEST['dept_id']==1 || $_REQUEST['dept_id']==22)
			{
			$assigned_user="and wd.status='CE' and assigned_user_id='".$_REQUEST['user_id']."'";
			}
			elseif($_REQUEST['dept_id']==10)
			{
			$assigned_user="and wd.status='PAG' and assigned_od_user_id='".$_REQUEST['user_id']."'";
			}
			elseif($_REQUEST['dept_id']==14)
			{
			$assigned_user="and wd.status='PRQC'  and assigned_od_user_id='".$_REQUEST['user_id']."'";
			}
			
			
			$sql_allocated=mysqli_query($db,"SELECT 
     wd.pub_id,wd.status,wd.id as inw_id
FROM 
   
    inw_inward_dtl AS wd
INNER JOIN 
    adm_customer_master AS c ON wd.cust_id = c.id
INNER JOIN 
    adm_journals AS j ON wd.j_id = j.j_id
where 1=1 $assigned_user and DATE_FORMAT(wd.od_due_date,'%Y-%m-%d')>='".date('Y-m-d', strtotime($start_date))."' and  DATE_FORMAT(wd.od_due_date,'%Y-%m-%d')<='".date('Y-m-d', strtotime($end_date))."';");
			while($res_allocated=mysqli_fetch_array($sql_allocated))
			{

			$progress_status=mysqli_fetch_array(mysqli_query($db,"select current_status from inw_transactions where project_id='".$res_allocated['inw_id']."' and  process_user='".$_REQUEST['user_id']."' "));
			?>
            <div
                class="row d-flex pt-2 pb-2 align-items-center"
                style="border-bottom: 1px solid #d8d2cb"
              >
              <p class="col-8 pt-2 pb-2"><?php echo $res_allocated['pub_id'];?></p>
              <p class="col-4 pt-2 pb-2 <?php if($progress_status['current_status']=="Inprogress"){?>Inprogress<?php }elseif($progress_status['current_status']=="Completed"){?>complete<?php }else{?>statusnot<?php }?> d-flex align-items-center justify-content-center">
                <?php if($progress_status['current_status']=="Inprogress"){?>In Progress<?php }elseif($progress_status['current_status']=="Completed"){?>Completed<?php }else{?>Not Started<?php }?>
              </p>
            </div>
          
			<?php
			}
			?>
			
			 <div style="display: flex; margin-top: 15px; justify-content: end">
                <a href="currentwork" target="_blank">Show More</a>
              </div>
          </div>
		  <?php
			}
			?>
        </div>
      </div>
     


  
  
    <!-- Footer Section -->
     <?php echo footer(); ?>




<!-- Footer Section -->
<?php echo scripts(); ?>	



   
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
    </script>

    <!-- Progress Bar script -->
    <script>
      const input = document.querySelector(".inputprogress");
      const progress = document.querySelector(".progress");
      const percentage = document.querySelector(".percentage");
      console.log(progress);
	    <?php 
			   if($_REQUEST['dept_id']!=1 || $_REQUEST['dept_id']!=22)
			   {
				?>
	  var perc= $(".inputprogress").val();
	  $(".percentage").text(perc);
	  $(".percentage_all").text(perc);
			   <?php 
			   }
			   ?>
      let percentComplete = 0;
	  
	  let maxwidth;
        if (window.innerWidth > 763) {
          maxwidth = 44;
        } else {
          maxwidth = 80;
        }
		
      let maxwidthinpixel = (maxwidth / 100) * window.innerWidth;
 

      $(document).ready(function () {
        percentComplete = $('.inputprogress').val();
        console.log(percentComplete);
        if (percentComplete < 50) {
          progress.style.backgroundColor = "red";
        } else if (percentComplete >= 50 && percentComplete < 80) {
          progress.style.backgroundColor = "Orange";
        } else {
          progress.style.backgroundColor = "green";
        }
		
		

        progress.style.width = `${(percentComplete / 100) * maxwidthinpixel}px`;

      //  percentage.innerHTML = `${percentComplete}%`;
      });
    </script>
	<!-- CORRECTIONS BAR -->
	 <script>
      const cinput = document.querySelector(".cinputprogress");
      const cprogress = document.querySelector(".cprogress");
      const cpercentage = document.querySelector(".cpercentage");
	 
	   let cmaxwidth;
        if (window.innerWidth > 763) {
          cmaxwidth = 44;
        } else {
          cmaxwidth = 80;
        }
		
      let cmaxwidthinpixel = (cmaxwidth / 100) * window.innerWidth;
      console.log(cprogress);
      let cpercentComplete = 0;

      $(document).ready(function () {
        cpercentComplete = $('.cinputprogress').val();
        console.log(cpercentComplete);
        if (cpercentComplete < 50) {
          cprogress.style.backgroundColor = "red";
        } else if (cpercentComplete >= 50 && cpercentComplete < 80) {
          cprogress.style.backgroundColor = "Orange";
        } else {
          cprogress.style.backgroundColor = "green";
        }

		cprogress.style.width = `${(cpercentComplete / 100) * cmaxwidthinpixel}px`;


       // cpercentage.innerHTML = `${cpercentComplete}%`;
      });
    </script>

	<!-- REVISES BAR -->
	 <script>
      const rinput = document.querySelector(".rinputprogress");
      const rprogress = document.querySelector(".rprogress");
      const rpercentage = document.querySelector(".rpercentage");
	  
      console.log(rprogress);
      let rpercentComplete = 0;
	   let rmaxwidth;
        if (window.innerWidth > 763) {
          rmaxwidth = 44;
        } else {
          rmaxwidth = 80;
        }
      let rmaxwidthinpixel = (rmaxwidth / 100) * window.innerWidth;
	  
      $(document).ready(function () {
        rpercentComplete = $('.rinputprogress').val();
        console.log(rpercentComplete);
        if (rpercentComplete < 50) {
          rprogress.style.backgroundColor = "red";
        } else if (rpercentComplete >= 50 && rpercentComplete < 80) {
          rprogress.style.backgroundColor = "Orange";
        } else {
          rprogress.style.backgroundColor = "green";
        }

		rprogress.style.width = `${(rpercentComplete / 100) * rmaxwidthinpixel}px`;

        //rpercentage.innerHTML = `${rpercentComplete}%`;
      });
    </script>
	
	
		<!-- FINAL BAR -->
	 <script>
      const finput = document.querySelector(".finputprogress")
      const fprogress = document.querySelector(".fprogress");
      const fpercentage = document.querySelector(".fpercentage");
      console.log(fprogress);
      let fpercentComplete = 0;
	 let fmaxwidth;
        if (window.innerWidth > 763) {
          fmaxwidth = 44;
        } else {
          fmaxwidth = 80;
        }
      let fmaxwidthinpixel = (fmaxwidth / 100) * window.innerWidth;
	  

      $(document).ready(function () {
        fpercentComplete = $('.finputprogress').val();
        console.log(fpercentComplete);
        if (fpercentComplete < 50) {
          fprogress.style.backgroundColor = "red";
        } else if (fpercentComplete >= 50 && fpercentComplete < 80) {
          fprogress.style.backgroundColor = "Orange";
        } else {
          fprogress.style.backgroundColor = "green";
        }

		fprogress.style.width = `${(fpercentComplete / 100) * fmaxwidthinpixel}px`;
       // fpercentage.innerHTML = `${fpercentComplete}%`;
      });
    </script>

    <!-- Initialize DataTables -->
    <script>
      $(document).ready(function () {
        var table = $("#table").DataTable({
          dom: "Bfrtip",
          buttons: [
            {
              extend: "collection", // Use a collection button for the dropdown
              text: "Export", // The text for the main button
              buttons: [
                "copyHtml5",
                "excelHtml5",
                "csvHtml5",
                {
                  extend: "pdfHtml5",
                  customize: function (doc) {
                    // Customize the PDF content here (optional)
                  },
                },
                {
                  extend: "print",
                  autoPrint: false, // Disable automatic printing
                  customize: function (win) {
                    // Customize the printed content here (optional)
                  },
                },
              ],
            },
          ],
          lengthMenu: [10, 25, 50, 100],
        });

        // Add a click event to the main button to trigger the dropdown
        $(".dt-buttons").on("click", function () {
          $(".dt-button-collection").toggleClass("show");
        });
		
		
		function populateSelect(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.full_name).val(item.id));
            });
        }
		
		
		
// Handle changes in the "countries" select element
/*             $("#dept_id").on("change", function() {
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
            }); */
			
			
      });
    </script>
	
			<script>
$(document).ready(function() {
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
});
</script>
<script>
 /*  document.addEventListener('DOMContentLoaded', function () {
    const deptSelect = document.getElementById('dept_id');
    const platformDiv = document.getElementById('extra-dropdown');

    function togglePlatform() {
      platformDiv.style.display = (deptSelect.value === '10') ? 'block' : 'none';
    }

    deptSelect.addEventListener('change', togglePlatform);
    togglePlatform(); // run on page load
  }); */
</script>

  </body>
</html>

