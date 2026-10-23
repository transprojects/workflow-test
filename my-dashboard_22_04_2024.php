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

    <!-- Data Table CSS- -->

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
        height: 12px;
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
      
    </style>
  </head>
  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
	 
<?php 

$user_target_dashboard=mysqli_fetch_array(mysqli_query($db,"select *,count(*) as tc from user_target_assign where user_id='".$_COOKIE['user_id']."' "));

if($user_target_dashboard['tc']!=0)
{

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
	}


$user_condition= "and dept='".$_COOKIE['department_id']."' and inw.process_user= '".$_COOKIE['user_id']."'  ";	

if($_COOKIE['department_id']==1 || $_COOKIE['department_id']==22 )
{
	if(isset($_REQUEST['fdate']))
	{
	$article_count=$user_target_dashboard['average_ac'];
	$page_count=$user_target_dashboard['average_pc'];
	
	$average_fp_ac=round($user_target_dashboard['average_ac'])*$days_difference;
	$average_cor_ac=0;
	$average_rev_ac=0;
	$average_fin_ac=0;
	}
	else
	{
	$average_fp_ac=round($user_target_dashboard['average_ac']);	
	$average_cor_ac=0;
	$average_rev_ac=0;
	$average_fin_ac=0;
	}
$cnt_query_fp = mysqli_fetch_array(mysqli_query($db,"SELECT  COUNT(DISTINCT(inw.project_id)) as num_fp,SUM(manuscript_count) AS pg from inw_transactions as inw,inw_inward_dtl as wd ,adm_customer_master as c, adm_journals as j where wd.cust_id = c.id and wd.j_id = j.j_id ".$user_condition." and DATE_FORMAT(inw.created_dt,'%Y-%m-%d')>='".date('Y-m-d', strtotime($start_date))."' and  DATE_FORMAT(inw.created_dt,'%Y-%m-%d')<='".date('Y-m-d', strtotime($end_date))."' and (current_status='Completed')"));

$fp_page_count=mysqli_fetch_array(mysqli_query($db,"SELECT 
    COUNT(DISTINCT inw.project_id) AS num_fp,
    SUM(wd.manuscript_count) AS manuscript_count_sum
FROM 
    inw_transactions AS inw
INNER JOIN 
    inw_inward_dtl AS wd ON inw.project_id = wd.id
INNER JOIN 
    adm_customer_master AS c ON wd.cust_id = c.id
INNER JOIN 
    adm_journals AS j ON wd.j_id = j.j_id
where 1=1 ".$user_condition." and DATE_FORMAT(inw.created_dt,'%Y-%m-%d')>='".date('Y-m-d', strtotime($start_date))."' and  DATE_FORMAT(inw.created_dt,'%Y-%m-%d')<='".date('Y-m-d', strtotime($end_date))."' and (current_status='Completed');"));

$completed_count_fp=$cnt_query_fp['num_fp'];

$target_percentage_fp=($completed_count_fp/$average_fp_ac)*100;

}
else
{
	
	if(isset($_REQUEST['fdate']))
	{
	$average_fp_ac=round($user_target_dashboard['average_ac'])*$days_difference;
	$average_fp_pc=round($user_target_dashboard['average_pc'])*$days_difference;
	
	$average_cor_ac=round($user_target_dashboard['average_cac'])*$days_difference;
	$average_cor_pc=round($user_target_dashboard['average_cpc'])*$days_difference;
	
	$average_rev_ac=round($user_target_dashboard['average_rac'])*$days_difference;
	$average_rev_pc=round($user_target_dashboard['average_rpc'])*$days_difference;
	
	$average_fin_ac=round($user_target_dashboard['average_fac'])*$days_difference;	
	$average_fin_pc=round($user_target_dashboard['average_fpc'])*$days_difference;	
	}
	else
	{
	$average_fp_ac=round($user_target_dashboard['average_ac']);
	$average_fp_pc=round($user_target_dashboard['average_pc']);
	
	$average_cor_ac=round($user_target_dashboard['average_cac']);
	$average_cor_pc=round($user_target_dashboard['average_cpc']);
	
	$average_rev_ac=round($user_target_dashboard['average_rac']);
	$average_rev_pc=round($user_target_dashboard['average_rpc']);
	
	$average_fin_ac=round($user_target_dashboard['average_fac']);
	$average_fin_pc=round($user_target_dashboard['average_fpc']);
	}



$cnt_query_fp = mysqli_fetch_array(mysqli_query($db,"SELECT  COUNT(DISTINCT(inw.project_id)) as num_fp ,
    SUM(inw.pages_completed) AS manuscript_count_sum
FROM 
    inw_transactions AS inw
INNER JOIN 
    inw_inward_dtl AS wd ON inw.project_id = wd.id
INNER JOIN 
    adm_customer_master AS c ON wd.cust_id = c.id
INNER JOIN 
    adm_journals AS j ON wd.j_id = j.j_id
where 1=1 ".$user_condition." and DATE_FORMAT(inw.created_dt,'%Y-%m-%d')>='".date('Y-m-d', strtotime($start_date))."' and  DATE_FORMAT(inw.created_dt,'%Y-%m-%d')<='".date('Y-m-d', strtotime($end_date))."' and (current_status='Completed') and current_stage LIKE '%FP%'"));


$cnt_query_corr = mysqli_fetch_array(mysqli_query($db,"SELECT  COUNT(DISTINCT(inw.project_id)) as num_fp ,
    SUM(inw.pages_completed) AS manuscript_count_sum
FROM 
    inw_transactions AS inw
INNER JOIN 
    inw_inward_dtl AS wd ON inw.project_id = wd.id
INNER JOIN 
    adm_customer_master AS c ON wd.cust_id = c.id
INNER JOIN 
    adm_journals AS j ON wd.j_id = j.j_id
where 1=1 ".$user_condition." and DATE_FORMAT(inw.created_dt,'%Y-%m-%d')>='".date('Y-m-d', strtotime($start_date))."' and  DATE_FORMAT(inw.created_dt,'%Y-%m-%d')<='".date('Y-m-d', strtotime($end_date))."' and (current_status='Correction Done')"));




$cnt_query_rev = mysqli_fetch_array(mysqli_query($db,"SELECT  COUNT(DISTINCT(inw.project_id)) as num_rev ,
    SUM(inw.pages_completed) AS manuscript_count_sum
FROM 
    inw_transactions AS inw
INNER JOIN 
    inw_inward_dtl AS wd ON inw.project_id = wd.id
INNER JOIN 
    adm_customer_master AS c ON wd.cust_id = c.id
INNER JOIN 
    adm_journals AS j ON wd.j_id = j.j_id
where 1=1  ".$user_condition." and DATE_FORMAT(inw.created_dt,'%Y-%m-%d')>='".date('Y-m-d', strtotime($start_date))."' and  DATE_FORMAT(inw.created_dt,'%Y-%m-%d')<='".date('Y-m-d', strtotime($end_date))."' and (current_status='Completed') and current_stage LIKE '%REV%'  "));
			
$cnt_query_fin = mysqli_fetch_array(mysqli_query($db,"SELECT  COUNT(DISTINCT(inw.project_id)) as num_finals ,
    SUM(inw.pages_completed) AS manuscript_count_sum
FROM 
    inw_transactions AS inw
INNER JOIN 
    inw_inward_dtl AS wd ON inw.project_id = wd.id
INNER JOIN 
    adm_customer_master AS c ON wd.cust_id = c.id
INNER JOIN 
    adm_journals AS j ON wd.j_id = j.j_id
where 1=1  ".$user_condition." and DATE_FORMAT(inw.created_dt,'%Y-%m-%d')>='".date('Y-m-d', strtotime($start_date))."' and  DATE_FORMAT(inw.created_dt,'%Y-%m-%d')<='".date('Y-m-d', strtotime($end_date))."' and (current_status='Completed') and (current_stage LIKE '%ISSFIN%' OR current_stage LIKE '%FIN%') "));
			
			

$completed_count_fp=$cnt_query_fp['num_fp'];
$completed_count_fp_pc=$cnt_query_fp['manuscript_count_sum'];

$completed_count_corr=$cnt_query_corr['num_fp'];
$completed_count_corr_pc=$cnt_query_corr['manuscript_count_sum'];

$completed_count_rev=$cnt_query_rev['num_rev'];
$completed_count_rev_pc=$cnt_query_rev['manuscript_count_sum'];

$completed_count_fin=$cnt_query_fin['num_finals'];
$completed_count_fin_pc=$cnt_query_fin['manuscript_count_sum'];

$target_percentage_fp=($completed_count_fp/$average_fp_ac)*100;
$target_percentage_fp_pc=($completed_count_fp_pc/$average_fp_pc)*100;


$target_percentage_corr=($completed_count_corr/$average_cor_ac)*100;
$target_percentage_corr_pc=($completed_count_corr_pc/$average_cor_pc)*100;


$target_percentage_rev=($completed_count_rev/$average_rev_ac)*100;
$target_percentage_rev_pc=($completed_count_rev_pc/$average_rev_pc)*100;


$target_percentage_fin=($completed_count_fin/$average_fin_ac)*100;
$target_percentage_fin_pc=($completed_count_fin_pc/$average_fin_pc)*100;


}


$total_allocated= ($average_fp_pc+$average_cor_pc+$average_rev_pc+$average_fin_pc)/4;

$total_completed=$completed_count_fp_pc+$completed_count_corr_pc+$completed_count_rev_pc+$completed_count_fin_pc;

$total_ach_percentage=($total_completed/$total_allocated)*100;
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
                  Over all Target -
                  <span
                    style="font-size: 1.2em; color: #74d84c; font-weight: 600"
                  >
                    <?php echo round($total_ach_percentage);?>%
                  </span>
                </p>
                
              </div>
            </div>
			<form method="post" name="search_target" action="mydashboard">
			<div class="container-fluid d-flex gap-2 ps-md-4 pe-md-4 gap-4"
              style="margin-top: 32px">
              <input class="dateselection" type="date" name="fdate" value="<?php echo $start_date;?>" />
              <input class="dateselection" type="date" name="tdate" value="<?php echo $end_date;?>" />
              <button class="searchbtn" type="submit">Search</button>
            </div>
			</form>
           
			
          <div class="justify-content-center flex-column mt-4 monthly1outer p-4" style=" border-radius: 16px; padding: 24px; margin: 20px;height: fit-content;">
		  
		              <div class="d-flex align-items-center" style="justify-content: space-between">
              <p style="font-size: 20px; font-weight: 600">Over All Target </p>
              <!--<button class="showmorebtn p-2">Show More</button>-->
            </div>
            <div class="row d-flex align-items-center">
              <div class="col-12 col-md-4">
                <p style="font-size: 64px; font-weight: 600"><?php echo $completed_count_fp_pc+$completed_count_corr_pc+$completed_count_rev_pc+$completed_count_fin_pc;?> / <?php echo ($average_fp_pc+$average_cor_pc+$average_rev_pc+$average_fin_pc)/4; ?></p>
              </div>

              <div class="col-12 col-md-8 mt-3 mb-3 m-md-0" style="position: relative">
			  <div class="bar">
			  <input type="hidden" class="inputprogress" value="<?php echo round($total_ach_percentage);?>" min="0" max="100" style="position:absolute;top:40px;">
               <div class="progress">
                    <div class="percentage"><?php echo round($total_ach_percentage);?>%</div>
               </div>

                <div class="d-flex pt-2 ps-2 pe-3" style="position: absolute;justify-content: space-between;width: 100%;top: 15px;left:0;font-size: 14px;color: #9c9dad;">
                  <p>0%</p>
                  <p>50%</p>
                  <p>100%</p>
                </div>
              </div>
			 </div>
           </div>
			
			
            
			
			
             <!-- <div class="report mt-3 hide">
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
		  if($_COOKIE['department_id']=='10' || $_COOKIE['department_id']=='14')
		  {
		  ?>
          <div class="analyticsouter monthly1outer" style=" border-radius: 16px;padding: 24px;margin: 20px;height: fit-content;">
            <div class="d-flex" style="justify-content: space-between; align-items: center;">
              <h5>Analytics</h5>
            <!-- <select class="d-flex gap-3 ps-3 pe-3" style="height: 38px; width: fit-content;border-radius: 8px;color: #666;">
                <option>April 2024</option>
              </select>-->
            </div>
             <div
                class="d-flex justify-content-center"
                style="flex-wrap: wrap; gap: 16px; margin-top: 32px"
              >
                <div
                  class="analyticdiv d-flex"
                  style="justify-content: space-evenly"
                >
                  <div
                    class="d-flex flex-column gap-3"
                    style="min-width: 50px; width: 45%"
                  >
                    <p class="analytictitle">First Proof</p>
                   
					<div class="d-flex gap-3 gap-md-5">
                      <div>
                        <p style="color: #b3b3b3; font-size: 1em">
                          <span
                            ><img
                              src="./assets/images/articleicon.png"
                              style="
                                width: 20px;
                                height: auto;
                                margin-right: 10px;
                              " /></span
                          >Article
                        </p>
                        <p class="percestyl" style="font-weight: 600; margin-left: 30px"><?php echo $completed_count_fp;?></p>
                      </div>
                      <div>
                        <p style="color: #b3b3b3; font-size: 1 em">
                          <span
                            ><img
                              src="./assets/images/pageicon.png"
                              style="
                                width: 20px;
                                height: auto;
                                margin-right: 10px;
                              " /></span
                          >Page
                        </p>
                        <p class="percestyl" style="font-weight: 600; margin-left: 30px"><?php if($completed_count_fp_pc!=""){ echo round($completed_count_fp_pc); }else{ echo "0"; }?></p>
                      </div>
                    </div>
                  </div>
                  <div>
                    <svg
                      width="110px"
                      height="130px"
                      viewBox="0 0 42 42"
                      class="donut"
                    >
                      <circle
                        class="donut-hole"
                        cx="24"
                        cy="21"
                        r="15.91549430918954"
                        fill="#fff"
                      ></circle>
                      <circle
                        class="donut-ring"
                        cx="24"
                        cy="21"
                        r="15.91549430918954"
                        fill="transparent"
                        stroke="#eaeaea"
                        stroke-width="2"
                      ></circle>

                      <circle
                        class="donut-segment"
                        cx="24"
                        cy="21"
                        r="15.91549430918954"
                        fill="transparent"
                        stroke="#3c6ed1"
                        stroke-width="2"
                        stroke-dasharray="<?php echo $target_percentage_fp_pc;?> <?php echo 100-$target_percentage_fp_pc;?>"
                        stroke-dashoffset="25"
                      ></circle>
                      <g class="chart-text">
                        <text x="40%" y="50%" class="chart-number"><?php echo round($target_percentage_fp_pc);?>%</text>
                      </g>
                    </svg>
                  </div>
                </div>
                <div
                  class="analyticdiv d-flex"
                  style="justify-content: space-evenly"
                >
                  <div
                    class="d-flex flex-column gap-3"
                    style="min-width: 50px; width: 45%"
                  >
                    <p class="analytictitle">Corrections</p>
                   <div class="d-flex gap-3 gap-md-5">
                      <div>
                        <p style="color: #b3b3b3; font-size: 1em">
                          <span
                            ><img
                              src="./assets/images/articleicon.png"
                              style="
                                width: 20px;
                                height: auto;
                                margin-right: 10px;
                              " /></span
                          >Article
                        </p>
                        <p class="percestyl" style="font-weight: 600; margin-left: 30px"><?php echo $completed_count_corr;?></p>
                      </div>
                      <div>
                        <p style="color: #b3b3b3; font-size: 1 em">
                          <span
                            ><img
                              src="./assets/images/pageicon.png"
                              style="
                                width: 20px;
                                height: auto;
                                margin-right: 10px;
                              " /></span
                          >Page
                        </p>
                        <p  class="percestyl" style="font-weight: 600; margin-left: 30px"><?php if($completed_count_corr_pc!=""){ echo round($completed_count_corr_pc); }else{ echo "0"; }?></p>
                      </div>
                    </div>
                  </div>
                  <div>
                    <svg
                      width="110px"
                      height="130px"
                      viewBox="0 0 42 42"
                      class="donut"
                    >
                      <circle
                        class="donut-hole"
                        cx="28"
                        cy="21"
                        r="15.91549430918954"
                        fill="#fff"
                      ></circle>
                      <circle
                        class="donut-ring"
                        cx="24"
                        cy="21"
                        r="15.91549430918954"
                        fill="transparent"
                        stroke="#eaeaea"
                        stroke-width="2"
                      ></circle>

                      <circle
                        class="donut-segment"
                        cx="24"
                        cy="21"
                        r="15.91549430918954"
                        fill="transparent"
                        stroke="#ff0000"
                        stroke-width="2"
                        stroke-dasharray="<?php echo $target_percentage_corr_pc;?> <?php echo 100-$target_percentage_corr_pc;?>"
                        stroke-dashoffset="25"
                      ></circle>
                      <g class="chart-text">
                        <text x="40%" y="50%" class="chart-number"><?php echo round($target_percentage_corr_pc);?>%</text>
                      </g>
                    </svg>
                  </div>
                </div>

                <div
                  class="analyticdiv d-flex"
                  style="justify-content: space-evenly"
                >
                  <div
                    class="d-flex flex-column gap-3"
                    style="min-width: 50px; width: 45%"
                  >
                    <p class="analytictitle">Revises</p>
                   <div class="d-flex gap-3 gap-md-5">
                      <div>
                        <p style="color: #b3b3b3; font-size: 1em">
                          <span
                            ><img
                              src="./assets/images/articleicon.png"
                              style="
                                width: 20px;
                                height: auto;
                                margin-right: 10px;
                              " /></span
                          >Article
                        </p>
                        <p class="percestyl" style="font-weight: 600; margin-left: 30px"><?php echo $completed_count_rev;?></p>
                      </div>
                      <div>
                        <p style="color: #b3b3b3; font-size: 1 em">
                          <span
                            ><img
                              src="./assets/images/pageicon.png"
                              style="
                                width: 20px;
                                height: auto;
                                margin-right: 10px;
                              " /></span
                          >Page
                        </p>
                        <p class="percestyl" style="font-weight: 600; margin-left: 30px"><?php if($completed_count_rev_pc!=""){ echo round($completed_count_rev_pc); }else{ echo "0"; }?></p>
                      </div>
                    </div>
                  </div>
                  <div>
                    <svg
                      width="110px"
                      height="130px"
                      viewBox="0 0 42 42"
                      class="donut"
                    >
                      <circle
                        class="donut-hole"
                        cx="28"
                        cy="21"
                        r="15.91549430918954"
                        fill="#fff"
                      ></circle>
                      <circle
                        class="donut-ring"
                        cx="24"
                        cy="21"
                        r="15.91549430918954"
                        fill="transparent"
                        stroke="#eaeaea"
                        stroke-width="2"
                      ></circle>

                      <circle
                        class="donut-segment"
                        cx="24"
                        cy="21"
                        r="15.91549430918954"
                        fill="transparent"
                        stroke="#ffab26"
                        stroke-width="2"
                        stroke-dasharray="<?php echo $target_percentage_rev_pc;?> <?php echo 100-$target_percentage_rev_pc;?>"
                        stroke-dashoffset="25"
                      ></circle>
                      <g class="chart-text">
                        <text x="40%" y="50%" class="chart-number"><?php echo round($target_percentage_rev_pc);?>%</text>
                      </g>
                    </svg>
                  </div>
                </div>
                <div
                  class="analyticdiv d-flex"
                  style="justify-content: space-evenly"
                >
                  <div
                    class="d-flex flex-column gap-3"
                    style="min-width: 50px; width: 50%"
                  >
                    <p class="analytictitle">Final</p>
                    <div class="d-flex gap-3 gap-md-5">
                      <div>
                        <p style="color: #b3b3b3; font-size: 1em; width: 100%">
                          <span
                            ><img
                              src="./assets/images/articleicon.png"
                              style="
                                width: 20px;
                                height: auto;
                                margin-right: 10px;
                              " /></span
                          >Article
                        </p>
                        <p  class="percestyl" style="font-weight: 600; margin-left: 30px"><?php echo $completed_count_fin;?></p>
                      </div>
                      <div>
                        <p style="color: #b3b3b3; font-size: 1 em">
                          <span
                            ><img
                              src="./assets/images/pageicon.png"
                              style="
                                width: 20px;
                                height: auto;
                                margin-right: 10px;
                              " /></span
                          >Page
                        </p>
                        <p class="percestyl" style="font-weight: 600; margin-left: 30px"><?php if($completed_count_fin_pc!=""){ echo round($completed_count_fin_pc); }else{ echo "0"; }?></p>
                      </div>
                    </div>
                  </div>
                  <div>
                    <svg
                      width="110px"
                      height="130px"
                      viewBox="0 0 42 42"
                      class="donut"
                    >
                      <circle
                        class="donut-hole"
                        cx="24"
                        cy="21"
                        r="15.91549430918954"
                        fill="#fff"
                      ></circle>
                      <circle
                        class="donut-ring"
                        cx="24"
                        cy="21"
                        r="15.91549430918954"
                        fill="transparent"
                        stroke="#eaeaea"
                        stroke-width="2"
                      ></circle>

                      <circle
                        class="donut-segment"
                        cx="24"
                        cy="21"
                        r="15.91549430918954"
                        fill="transparent"
                        stroke="#0bb70b"
                        stroke-width="2"
                        stroke-dasharray="<?php echo $target_percentage_fin_pc;?> <?php echo 100-$target_percentage_fin_pc;?>"
                        stroke-dashoffset="25"
                      ></circle>
                      <g class="chart-text">
                        <text x="40%" y="50%" class="chart-number"><?php echo round($target_percentage_fin_pc); ?>%</text>
                      </g>
                    </svg>
                  </div>
                </div>
              </div>

            </div>
			 <?php 
		  }
		  else
		  {
		  ?>
		   <div class="analyticsouter monthly1outer" style=" border-radius: 16px;padding: 24px;margin: 20px;height: fit-content;">
            <div class="d-flex" style="justify-content: space-between; align-items: center;">
              <h5>Analytics</h5>
             <!-- <select class="d-flex gap-3 ps-3 pe-3" style="height: 38px; width: fit-content;border-radius: 8px;color: #666;">
                <option>April 2024</option>
              </select>-->
            </div>
             <div
                class="d-flex justify-content-center"
                style="flex-wrap: wrap; gap: 16px; margin-top: 32px"
              >
                <div
                  class="analyticdiv d-flex"
                  style="justify-content: space-evenly"
                >
                  <div
                    class="d-flex flex-column gap-3"
                    style="min-width: 50px; width: 40%"
                  >
                    <p class="analytictitle">Artilce Count</p>
                    <p class="analyticpara">
                      <span style="color: rgb(88, 241, 88); font-weight: 600"
                        ><?php echo round($completed_count_fp);?></span
                      >
                      till date
                    </p>
                  </div>
                  <div>
                    <svg
                      width="110px"
                      height="130px"
                      viewBox="0 0 42 42"
                      class="donut"
                    >
                      <circle
                        class="donut-hole"
                        cx="24"
                        cy="21"
                        r="15.91549430918954"
                        fill="#fff"
                      ></circle>
                      <circle
                        class="donut-ring"
                        cx="24"
                        cy="21"
                        r="15.91549430918954"
                        fill="transparent"
                        stroke="#eaeaea"
                        stroke-width="2"
                      ></circle>

                      <circle
                        class="donut-segment"
                        cx="24"
                        cy="21"
                        r="15.91549430918954"
                        fill="transparent"
                        stroke="#3c6ed1"
                        stroke-width="2"
                        stroke-dasharray=""
                        stroke-dashoffset="25"
                      ></circle>
                      <g class="chart-text">
                        <text x="40%" y="50%" class="chart-number"><?php echo round($completed_count_fp);?></text>
                      </g>
                    </svg>
                  </div>
                </div>
                <div
                  class="analyticdiv d-flex"
                  style="justify-content: space-evenly"
                >
                  <div
                    class="d-flex flex-column gap-3"
                    style="min-width: 50px; width: 40%"
                  >
                    <p class="analytictitle">Page Count</p>
                    <p class="analyticpara">
                      <span style="color: green; font-weight: 600"><?php if($fp_page_count['manuscript_count_sum']!=""){ echo round($fp_page_count['manuscript_count_sum']); }else{ echo "0"; }?></span>
                      till date
                    </p>
                  </div>
                  <div>
                    <svg
                      width="110px"
                      height="130px"
                      viewBox="0 0 42 42"
                      class="donut"
                    >
                      <circle
                        class="donut-hole"
                        cx="24"
                        cy="21"
                        r="15.91549430918954"
                        fill="#fff"
                      ></circle>
                      <circle
                        class="donut-ring"
                        cx="24"
                        cy="21"
                        r="15.91549430918954"
                        fill="transparent"
                        stroke="#eaeaea"
                        stroke-width="2"
                      ></circle>

                      <circle
                        class="donut-segment"
                        cx="24"
                        cy="21"
                        r="15.91549430918954"
                        fill="transparent"
                        stroke="#ff0000"
                        stroke-width="2"
                        stroke-dasharray=""
                        stroke-dashoffset="25"
                      ></circle>
                      <g class="chart-text">
                        <text x="40%" y="50%" class="chart-number"><?php echo round($fp_page_count['manuscript_count_sum']);?></text>
                      </g>
                    </svg>
                  </div>
                </div>
				</div>
				</div>
		  <?php 
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
			if($_COOKIE['department_id']==1 || $_COOKIE['department_id']==22)
			{
			$assigned_user="and wd.status='CE' and assigned_user_id='".$_COOKIE['user_id']."'";
			}
			elseif($_COOKIE['department_id']==10)
			{
			$assigned_user="and wd.department_id=10 and assigned_od_user_id='".$_COOKIE['user_id']."'";
			}
			elseif($_COOKIE['department_id']==14)
			{
			$assigned_user="and wd.department_id=14  and assigned_od_user_id='".$_COOKIE['user_id']."'";
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

			$progress_status=mysqli_fetch_array(mysqli_query($db,"select current_status from inw_transactions where project_id='".$res_allocated['inw_id']."' and  process_user='".$_COOKIE['user_id']."' "));
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
        </div>
      </div>
     
<?php 
}
else
{
?>
<div style="height:77vh;display:flex;justify-content:center;align-items:center;">
<p style="text-align:center;color:red;">No Target Assigned to You</p>
</div>
<?php 
}
?>

  
  
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
      });
    </script>
  </body>
</html>

