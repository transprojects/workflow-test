<?php include "includes/includes.php";
include "includes/modern-ui.php";
include ('includes/paginate.php'); 
?>
<?php
if(isset($_REQUEST['wgetaction']))
{   
$search = "";
if ($_REQUEST['wcust_id'] != "" && $_REQUEST['wcust_id'] != 'all') {
    $search .= "and cust_id='" . $_REQUEST['wcust_id'] . "' ";
}

if ($_REQUEST['dept'] != "" && $_REQUEST['dept'] != 'all') {
    $dept .= "and wd.department_id='" . $_REQUEST['dept'] . "' ";
}

}
$fds=date('Y-m-d', strtotime('-2 days'));
$tds=date('Y-m-d', strtotime('+1 days'));
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
    <style>.mu-table td,.mu-table th{white-space:nowrap;}.mu-matrix{min-width:0}.mu-matrix th,.mu-matrix td{text-align:center}
.mu-matrix tbody th{background:#f4f7fb;text-align:left;text-transform:none;letter-spacing:0;font-size:13px}
.mu-matrix td a{display:inline-block;min-width:26px;padding:2px 6px;border-radius:14px;color:var(--info);text-decoration:none;transition:all .2s}
.mu-matrix td a:hover{background:var(--info);color:#fff}
.mu-field .select2-container{min-width:200px;max-width:320px}</style>	

</head>

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
	 
      <!-- Header logo section -->
      
      <!-- report section body starts -->
      <div class="mu-root">
        <div class="mu-hero">
          <div><small>Reports</small><h1>WIP Report</h1>
            <p>Work in progress by stage and due date, per customer and department &nbsp;&middot;&nbsp; <a href="wipreport" style="color:#fff;text-decoration:underline;">Open the new version</a></p></div>
        </div>
        <form action="#" id="validation-form" method="post" autocomplete="off">
          <section class="mu-panel mu-filter-panel mu-reveal">
            <div class="mu-filter">
              <div class="mu-field"><label>View by</label>
                <div class="mu-seg">
                  <label><input type="radio" name="date_wise" id="daywise" value="d" <?php if (($_REQUEST['date_wise'] ?? '') != 'm') { ?> checked <?php } ?>><span>Day</span></label>
                  <label><input type="radio" name="date_wise" id="monthwise" value="m" <?php if (($_REQUEST['date_wise'] ?? '') == 'm') { ?> checked <?php } ?>><span>Month</span></label>
                </div></div>
              <div class="mu-field" style="min-width:240px;"><label for="wcust_id">Customer</label>
                <select class="customer_drop" name="wcust_id" id="wcust_id" >
                  <option value="all" <?php if (($_REQUEST['wcust_id'] ?? '') == 'all') { ?> selected <?php } ?>>Select All</option>
<?php
if($_COOKIE['user_level']=='1') { $sql_customers = $auth->showCustomerlist(); }
else { $sql_customers = $auth->showCustomerlistbyuser($_COOKIE['cust_id'],$_COOKIE['ocust_id']); }
foreach($sql_customers as $res_customers) { ?>
                  <option <?php if (($_REQUEST['wcust_id'] ?? '') == $res_customers['id']) { ?> selected <?php } ?> value="<?php echo $res_customers['id'] ?>"><?php echo $res_customers['cust_name'] ?></option>
<?php } ?>
                </select></div>
              <div class="mu-field" style="min-width:220px;"><label for="dept">Department</label>
                <select class="department_drop" name="dept" id="dept" >
                  <option value="all">Select All</option>
<?php
$sql_department = $auth->getAlldepartment('0', 2);
foreach ($sql_department as $res_department) { ?>
                  <option <?php if (($_REQUEST['dept'] ?? '') == $res_department['dept_code']) { ?> selected <?php } ?> value="<?php echo htmlspecialchars($res_department['dept_code']); ?>"><?php echo htmlspecialchars($res_department['dept_name']); ?></option>
<?php } ?>
                </select></div>
              <div id="day_div" class="mu-field" style="display:<?php echo (($_REQUEST['date_wise'] ?? '') != 'm') ? 'block' : 'none'; ?>;min-width:250px;"><label>Date range</label>
                <div class="dvr" data-from-name="wfrom_dt" data-to-name="wto_dt" data-from="<?php echo ($_REQUEST['wfrom_dt'] ?? '') != '' ? $_REQUEST['wfrom_dt'] : date('d-m-Y', strtotime('-5 days')); ?>" data-to="<?php echo ($_REQUEST['wto_dt'] ?? '') != '' ? $_REQUEST['wto_dt'] : date('d-m-Y', strtotime('+2 days')); ?>"></div></div>
              <div id="month_div" class="mu-field" style="display:<?php echo (($_REQUEST['date_wise'] ?? '') == 'm') ? 'flex' : 'none'; ?>;">
                <label for="mfrom_dt">Month</label>
                <input type="text" id="mfrom_dt" name="mfrom_dt" placeholder="Select Month" class="monthlyinputdate2" value="<?php echo ($_REQUEST['mfrom_dt'] ?? '') != '' ? $_REQUEST['mfrom_dt'] : date('m-Y'); ?>" /></div>
              <div class="mu-field" style="flex-direction:row;gap:10px;">
                <input type="hidden" name="filter" id="filter" value="1">
                <button class="mu-btn primary" type="submit" name="wgetaction"><i class="fa fa-bolt"></i> Get Result</button>
                <a class="mu-btn" href="wipreport-old" title="Reset"><i class="fa fa-refresh"></i></a>
              </div>
            </div>
          </section>
        </form>


        <!-- Table section -->
		
		<?php

if(isset($_REQUEST['filter']))
{
$day = date('d');        // Get the current day	

        $r = 1;
if ($_REQUEST['date_wise'] == "d")
{

    if($_REQUEST['wfrom_dt']!="")
    {
    $start_date =date('Y-m-d',strtotime($_REQUEST['wfrom_dt']));
    $start_date =date('Y-m-d',strtotime($_REQUEST['wfrom_dt']));

    $end_date =date('Y-m-d',strtotime($_REQUEST['wto_dt']));
    }
}
else if ($_REQUEST['date_wise'] == "m") {

$explode_month=explode("-",$_REQUEST['mfrom_dt']);
$year = $explode_month[1]; // Replace with the desired year
$month =$explode_month[0];   // Replace with the desired month (1-12)

// Create a date string in the format "YYYY-MM-01" for the first day of the specified month and year
$start_date = date("Y-m-01", strtotime("$year-$month-01"));

// Create a date string in the format "YYYY-MM-t" for the last day of the specified month and year
    if($month==date('m'))
    {
        $end_date = date("Y-m-d", strtotime("$year-$month-$day"));
    }
    else
    {
        $end_date = date("Y-m-t", strtotime("$year-$month-01"));
    }

//echo "Start Date: " . $start_date . "<br>";
//echo "End Date: " . $end_date;

}

else
{
$start_date =$fds;
$end_date =$tds;	
}
		?>
        <section class="mu-panel mu-reveal">
<h3><i class="fa fa-table"></i> Work in progress <span class="mu-right">Click a number to list the articles</span></h3>
<div class="mu-table-wrap" style="border:0;">
<table class="mu-table mu-matrix">
														
													    
															
                                                            <thead>
															
                                                              
																
																 <tr>
                <th rowspan="2" style="vertical-align: middle">Stage</th>
                <th colspan="32" style="color: #f26722; font-weight: 600; text-align: left"                >
                  <?php echo date('M',strtotime($start_date));?>
                  <span style="color: #222; margin-left: 40%"
                    ><?php echo date('d-M-Y',strtotime($start_date));?> TO <?php echo date('d-M-Y',strtotime($end_date));?></span
                  >
                </th>
              </tr>
															
                                                             <tr class="violetclr">

<th >Previous Due's</th>
<?php 


$rev_id_count=0;
$start_date_prev=date('Y-m-d');
$end_prev_date=date('Y-m-d', strtotime('-1 days'));

while (strtotime($start_date_prev) <= strtotime($end_date))
{
?>
<th > <?php echo date('d',strtotime($start_date_prev));?></th>
<?php 

 $start_date_prev = date ("Y-m-d", strtotime("+1 days", strtotime($start_date_prev)));
}
?>
																	 <th style="color:#0000FF;">TOTAL</th>

																	
                                                                </tr>
                                                            </thead>
                                                            <tbody>
		  <tr>
		    <th style="color: #4648b8">FP</th>                                                   
																		<?php 
																		
if ($_REQUEST['date_wise'] == "d")
{

    if($_REQUEST['wfrom_dt']!="")
    {
    $sstart_date =date('Y-m-d',strtotime($_REQUEST['wfrom_dt']));
    $send_date =date('Y-m-d',strtotime($_REQUEST['wto_dt']));

    $tsstart_date =date('Y-m-d',strtotime($_REQUEST['wfrom_dt']));
    $tsend_date =date('Y-m-d',strtotime($_REQUEST['wto_dt']));
    }
}
else if ($_REQUEST['date_wise'] == "m") {
    
$explode_month=explode("-",$_REQUEST['mfrom_dt']);
$year = $explode_month[1]; // Replace with the desired year
$month =$explode_month[0];   // Replace with the desired month (1-12)

// Create a date string in the format "YYYY-MM-01" for the first day of the specified month and year
$sstart_date = date("Y-m-01", strtotime("$year-$month-01"));

// Create a date string in the format "YYYY-MM-t" for the last day of the specified month and year
//$send_date = date("Y-m-t", strtotime("$year-$month-01"));
if($month==date('m'))
{
    $send_date = date("Y-m-d", strtotime("$year-$month-$day"));
}
else
{
    $send_date = date("Y-m-t", strtotime("$year-$month-01"));
}
//echo "Start Date: " . $start_date . "<br>";
//echo "End Date: " . $end_date;
$tsstart_date =date('Y-m-d',strtotime($sstart_date));
$tsend_date =date('Y-m-d',strtotime($send_date));
}
else
{
$sstart_date =$fds;
$send_date =$tds;	


$tsstart_date =date('Y-m-d',strtotime($sstart_date));
$tsend_date =date('Y-m-d',strtotime($send_date));
}

$current_date_fp=date('Y-m-d');

               $fp_query_total = mysqli_num_rows(mysqli_query($db,"SELECT * FROM `inw_inward_dtl` as wd WHERE DATE_FORMAT(due_dt,'%Y-%m-%d')>='".date('Y-m-d', strtotime($sstart_date))."' and DATE_FORMAT(due_dt,'%Y-%m-%d')<='".date('Y-m-d', strtotime($send_date))."' and stage='FP' and status != 'Client Review' $search  $dept "));

               $fp_query_total_previous = mysqli_num_rows(mysqli_query($db,"SELECT * FROM `inw_inward_dtl` as wd WHERE DATE_FORMAT(due_dt,'%Y-%m-%d')>='".date('Y-m-d', strtotime($sstart_date))."' and DATE_FORMAT(due_dt,'%Y-%m-%d')<'".date('Y-m-d')."' and stage='FP' and status != 'Client Review' $search $dept  "));


               ?>
            <td><a onclick="loaddata1('<?php echo date('Y-m-d', strtotime($tsstart_date));?>','<?php echo date('Y-m-d', strtotime($end_prev_date));?>','<?php echo $_REQUEST['wcust_id'];?>','<?php echo $_REQUEST['dept'];?>','FPALL');" style="cursor:pointer;"><b style="color:red;"><?php echo $fp_query_total_previous;?></b></a></td>

               <?php 

					 	while (strtotime($current_date_fp) <= strtotime($send_date))
						{
	
		
               $fp_query = "SELECT count(id) as stage_count FROM `inw_inward_dtl` as wd WHERE DATE_FORMAT(due_dt,'%Y-%m-%d')='".date('Y-m-d', strtotime($current_date_fp))."' and stage='FP' and status != 'Client Review' $search $dept  ";
			  
			  
            $fp_run_query = $db->query($fp_query) or die($db->error);
            $fp_row = $fp_run_query->fetch_assoc();
						?>
				<td><a onclick="loaddata('<?php echo date('Y-m-d', strtotime($current_date_fp));?>','<?php echo $_REQUEST['wcust_id'];?>','<?php echo $_REQUEST['dept'];?>','FP');" style="cursor:pointer;"><?php if($current_date_fp<date('Y-m-d') && $fp_row['stage_count']>0){?><b style="color:red;"><?php echo $fp_row['stage_count'];?></b><?php }else{?><span style="color:#000;"><?php echo $fp_row['stage_count'];?></span><?php }?></a></td>
						<?php 
						 //$fp_id_count++;
						$current_date_fp = date ("Y-m-d", strtotime("+1 days", strtotime($current_date_fp)));
						} 
						?>
          
			
                       <td><a onclick="loaddata1('<?php echo date('Y-m-d', strtotime($tsstart_date));?>','<?php echo date('Y-m-d', strtotime($tsend_date));?>','<?php echo $_REQUEST['wcust_id'];?>','<?php echo $_REQUEST['dept'];?>','FPALL');" style="cursor:pointer;"><b style="color:#0000FF;"><?php echo $fp_query_total;?></b></a></td>
                                    
                 </tr>
				 
				   <tr>
		    <th style="color: #4648b8">REV</th>                                                   
                                     
																		<?php 
																		
if ($_REQUEST['date_wise'] == "d")
{

    if($_REQUEST['wfrom_dt']!="")
    {
    $sstart_date =date('Y-m-d',strtotime($_REQUEST['wfrom_dt']));
    $send_date =date('Y-m-d',strtotime($_REQUEST['wto_dt']));

    $tsstart_date =date('Y-m-d',strtotime($_REQUEST['wfrom_dt']));
    $tsend_date =date('Y-m-d',strtotime($_REQUEST['wto_dt']));
    }
}
else if ($_REQUEST['date_wise'] == "m") {

$explode_month=explode("-",$_REQUEST['mfrom_dt']);
$year = $explode_month[1]; // Replace with the desired year
$month =$explode_month[0];   // Replace with the desired month (1-12)

// Create a date string in the format "YYYY-MM-01" for the first day of the specified month and year
$sstart_date = date("Y-m-01", strtotime("$year-$month-01"));

// Create a date string in the format "YYYY-MM-t" for the last day of the specified month and year
//$send_date = date("Y-m-t", strtotime("$year-$month-01"));
if($month==date('m'))
{
    $send_date = date("Y-m-d", strtotime("$year-$month-$day"));
}
else
{
    $send_date = date("Y-m-t", strtotime("$year-$month-01"));
}
//echo "Start Date: " . $start_date . "<br>";
//echo "End Date: " . $end_date;
$tsstart_date =date('Y-m-d',strtotime($sstart_date));
$tsend_date =date('Y-m-d',strtotime($send_date));

}
else
{
$sstart_date =$fds;
$send_date =$tds;	


$tsstart_date =date('Y-m-d',strtotime($sstart_date));
$tsend_date =date('Y-m-d',strtotime($send_date));
}
$current_date_rev=date('Y-m-d');

               $rev_query_total = mysqli_num_rows(mysqli_query($db,"SELECT wd.id,wd.cust_id, wd.j_id, wd.status, r.due_date, r.received_date, wd.pub_id, wd.stage, wd.department_id,aq_status FROM inw_inward_dtl as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)),adm_journals as j  WHERE  wd.j_id = j.j_id and wd.status != 'Client Review'  AND  DATE_FORMAT(r.due_date,'%Y-%m-%d')>='" .date('Y-m-d', strtotime($sstart_date))."' AND  DATE_FORMAT(r.due_date,'%Y-%m-%d')<='" .date('Y-m-d', strtotime($send_date))."'  AND r.created_on > '2023-01-01' AND wd.stage LIKE 'REV%' AND wd.status NOT IN ('Client Review','Completed') AND wd.id = r.inw_id $search $dept GROUP BY r.inw_id ORDER BY wd.cust_id ASC"));

               $rev_query_total_previous = mysqli_num_rows(mysqli_query($db,"SELECT wd.id,wd.cust_id, wd.j_id, wd.status, r.due_date, r.received_date, wd.pub_id, wd.stage, wd.department_id,aq_status FROM inw_inward_dtl as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)),adm_journals as j  WHERE  wd.j_id = j.j_id and wd.status != 'Client Review'  AND  DATE_FORMAT(r.due_date,'%Y-%m-%d')>='" .date('Y-m-d', strtotime($sstart_date))."' AND  DATE_FORMAT(r.due_date,'%Y-%m-%d')<'" .date('Y-m-d')."'  AND r.created_on > '2023-01-01' AND wd.stage LIKE 'REV%' AND wd.status NOT IN ('Client Review','Completed') AND wd.id = r.inw_id $search $dept GROUP BY r.inw_id ORDER BY wd.cust_id ASC"));


               ?>
         <td><a onclick="loaddata1('<?php echo date('Y-m-d', strtotime($tsstart_date));?>','<?php echo date('Y-m-d', strtotime($end_prev_date));?>','<?php echo $_REQUEST['wcust_id'];?>','<?php echo $_REQUEST['dept'];?>','REVALL');" style="cursor:pointer;"><b style="color:red;"><?php echo $rev_query_total_previous;?></b></a></td>

               <?php 

					 	while (strtotime($current_date_rev) <= strtotime($send_date))
						{
	
		
                   $rev_query = "SELECT wd.id,wd.cust_id, wd.j_id, wd.status, r.due_date, r.received_date, wd.pub_id, wd.stage, wd.department_id,aq_status FROM inw_inward_dtl as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)),adm_journals as j  WHERE  wd.j_id = j.j_id and wd.status != 'Client Review'  AND  DATE_FORMAT(r.due_date,'%Y-%m-%d')='" .date('Y-m-d', strtotime($current_date_rev))."' AND r.created_on > '2023-01-01' AND wd.stage LIKE 'REV%' AND wd.status NOT IN ('Client Review','Completed') AND wd.id = r.inw_id $search $dept GROUP BY r.inw_id ORDER BY wd.cust_id ASC ";
			  
			  
            $rev_run_query = $db->query($rev_query) or die($db->error);
            $rev_row = $rev_run_query->fetch_assoc();
            $rev_id_count = $rev_id_coun+$rev_run_query->num_rows;
			
						?>
				<td> <a onclick="loaddata('<?php echo date('Y-m-d', strtotime($current_date_rev));?>','<?php echo $_REQUEST['wcust_id'];?>','<?php echo $_REQUEST['dept'];?>','REV');" style="cursor:pointer;"><?php if($current_date_rev<date('Y-m-d') && $rev_run_query->num_rows>0){?><b style="color:red;"><?php echo $rev_run_query->num_rows;?></b><?php }else{?><span style="color:#000;"><?php echo $rev_run_query->num_rows;?></span><?php }?></a></td>
						<?php 
						
						$current_date_rev = date ("Y-m-d", strtotime("+1 days", strtotime($current_date_rev)));


						} 
						?>
                                <td><a onclick="loaddata1('<?php echo date('Y-m-d', strtotime($tsstart_date));?>','<?php echo date('Y-m-d', strtotime($tsend_date));?>','<?php echo $_REQUEST['wcust_id'];?>','<?php echo $_REQUEST['dept'];?>','REVALL');" style="cursor:pointer;"><b style="color:#0000FF;"><?php echo $rev_query_total;?></b></a></td>

			
                                                                                   
                 </tr>
				 
				    <tr>
		             <th style="color: #4648b8">FIN</th>                                                   
                                       
																		<?php 
																		
if ($_REQUEST['date_wise'] == "d")
{

    if($_REQUEST['wfrom_dt']!="")
    {
    $sstart_date =date('Y-m-d',strtotime($_REQUEST['wfrom_dt']));
    $send_date =date('Y-m-d',strtotime($_REQUEST['wto_dt']));

    $tsstart_date =date('Y-m-d',strtotime($_REQUEST['wfrom_dt']));
    $tsend_date =date('Y-m-d',strtotime($_REQUEST['wto_dt']));
    }
}
else if ($_REQUEST['date_wise'] == "m") {

$explode_month=explode("-",$_REQUEST['mfrom_dt']);
$year = $explode_month[1]; // Replace with the desired year
$month =$explode_month[0];   // Replace with the desired month (1-12)

// Create a date string in the format "YYYY-MM-01" for the first day of the specified month and year
$sstart_date = date("Y-m-01", strtotime("$year-$month-01"));

// Create a date string in the format "YYYY-MM-t" for the last day of the specified month and year
//$send_date = date("Y-m-t", strtotime("$year-$month-01"));
if($month==date('m'))
{
    $send_date = date("Y-m-d", strtotime("$year-$month-$day"));
}
else
{
    $send_date = date("Y-m-t", strtotime("$year-$month-01"));
}
//echo "Start Date: " . $start_date . "<br>";
//echo "End Date: " . $end_date;
$tsstart_date =date('Y-m-d',strtotime($sstart_date));
$tsend_date =date('Y-m-d',strtotime($send_date));

}
else
{
$sstart_date =$fds;
$send_date =$tds;	


$tsstart_date =date('Y-m-d',strtotime($sstart_date));
$tsend_date =date('Y-m-d',strtotime($send_date));
}
$current_date_fin=date('Y-m-d');

               $fin_query_total = mysqli_num_rows(mysqli_query($db,"SELECT wd.id,wd.cust_id, wd.j_id, wd.status, r.due_date, r.received_date, wd.pub_id, wd.stage, wd.department_id,aq_status FROM inw_inward_dtl as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)),adm_journals as j  WHERE  wd.j_id = j.j_id and wd.status != 'Client Review'  AND  DATE_FORMAT(r.due_date,'%Y-%m-%d')>='" .date('Y-m-d', strtotime($sstart_date))."' AND  DATE_FORMAT(r.due_date,'%Y-%m-%d')<='" .date('Y-m-d', strtotime($send_date))."'  AND r.created_on > '2023-01-01' AND wd.stage LIKE 'FIN%' AND wd.status NOT IN ('Client Review','Completed') AND wd.id = r.inw_id $search $dept GROUP BY r.inw_id ORDER BY wd.cust_id ASC"));

               $fin_query_total_previous = mysqli_num_rows(mysqli_query($db,"SELECT wd.id,wd.cust_id, wd.j_id, wd.status, r.due_date, r.received_date, wd.pub_id, wd.stage, wd.department_id,aq_status FROM inw_inward_dtl as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)),adm_journals as j  WHERE  wd.j_id = j.j_id and wd.status != 'Client Review'  AND  DATE_FORMAT(r.due_date,'%Y-%m-%d')>='" .date('Y-m-d', strtotime($sstart_date))."' AND  DATE_FORMAT(r.due_date,'%Y-%m-%d')<'" .date('Y-m-d')."'  AND r.created_on > '2023-01-01' AND wd.stage LIKE 'FIN%' AND wd.status NOT IN ('Client Review','Completed') AND wd.id = r.inw_id $search $dept GROUP BY r.inw_id ORDER BY wd.cust_id ASC"));


               ?>
         <td><a onclick="loaddata1('<?php echo date('Y-m-d', strtotime($tsstart_date));?>','<?php echo date('Y-m-d', strtotime($end_prev_date));?>','<?php echo $_REQUEST['wcust_id'];?>','<?php echo $_REQUEST['dept'];?>','FINALL');" style="cursor:pointer;"><b style="color:red;"><?php echo $fin_query_total_previous;?></b></a></td>

               <?php 

					 	while (strtotime($current_date_fin) <= strtotime($send_date))
						{
	
		
                   $fin_query = "SELECT wd.id,wd.cust_id, wd.j_id, wd.status, r.due_date, r.received_date, wd.pub_id, wd.stage, wd.department_id,aq_status FROM inw_inward_dtl as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)),adm_journals as j  WHERE  wd.j_id = j.j_id and wd.status != 'Client Review'  AND  DATE_FORMAT(r.due_date,'%Y-%m-%d')='" .date('Y-m-d', strtotime($current_date_fin))."' AND r.created_on > '2023-01-01' AND wd.stage LIKE 'FIN%' AND wd.status NOT IN ('Client Review','Completed') AND wd.id = r.inw_id $search $dept GROUP BY r.inw_id ORDER BY wd.cust_id ASC ";
			  
			  
            $fin_run_query = $db->query($fin_query) or die($db->error);
            $fin_row = $fin_run_query->fetch_assoc();
            $fin_id_count = $fin_id_coun+$fin_run_query->num_rows;
			
						?>
				<td> <a onclick="loaddata('<?php echo date('Y-m-d', strtotime($current_date_fin));?>','<?php echo $_REQUEST['wcust_id'];?>','<?php echo $_REQUEST['dept'];?>','FIN');" style="cursor:pointer;"><?php if($current_date_fin<date('Y-m-d') && $fin_run_query->num_rows>0){?><b style="color:red;"><?php echo $fin_run_query->num_rows;?></b><?php }else{?><span style="color:#000;"><?php echo $fin_run_query->num_rows;?></span><?php }?></a></td>
						<?php 
						
						$current_date_fin = date ("Y-m-d", strtotime("+1 days", strtotime($current_date_fin)));


						} 
						?>
                                <td><a onclick="loaddata1('<?php echo date('Y-m-d', strtotime($tsstart_date));?>','<?php echo date('Y-m-d', strtotime($tsend_date));?>','<?php echo $_REQUEST['wcust_id'];?>','<?php echo $_REQUEST['dept'];?>','FINALL');" style="cursor:pointer;"><b style="color:#0000FF;"><?php echo $fin_query_total;?></b></a></td>

			
                                                                                   
                 </tr>


                 <tr>
		      <th style="color: #4648b8">ISS</th>                                                   
																		<?php 
																		
if ($_REQUEST['date_wise'] == "d")
{

    if($_REQUEST['wfrom_dt']!="")
    {
    $sstart_date =date('Y-m-d',strtotime($_REQUEST['wfrom_dt']));
    $send_date =date('Y-m-d',strtotime($_REQUEST['wto_dt']));

  
    $tsstart_date =date('Y-m-d',strtotime($_REQUEST['wfrom_dt']));
    $tsend_date =date('Y-m-d',strtotime($_REQUEST['wto_dt']));
    }
}
else if ($_REQUEST['date_wise'] == "m") {

$explode_month=explode("-",$_REQUEST['mfrom_dt']);
$year = $explode_month[1]; // Replace with the desired year
$month =$explode_month[0];   // Replace with the desired month (1-12)

// Create a date string in the format "YYYY-MM-01" for the first day of the specified month and year
$sstart_date = date("Y-m-01", strtotime("$year-$month-01"));

// Create a date string in the format "YYYY-MM-t" for the last day of the specified month and year
//$send_date = date("Y-m-t", strtotime("$year-$month-01"));
if($month==date('m'))
{
    $send_date = date("Y-m-d", strtotime("$year-$month-$day"));
}
else
{
    $send_date = date("Y-m-t", strtotime("$year-$month-01"));
}
//echo "Start Date: " . $start_date . "<br>";
//echo "End Date: " . $end_date;

$tsstart_date =date('Y-m-d',strtotime($sstart_date));
$tsend_date =date('Y-m-d',strtotime($send_date));

}
else
{
$sstart_date =$fds;
$send_date =$tds;	

$tsstart_date =date('Y-m-d',strtotime($sstart_date));
$tsend_date =date('Y-m-d',strtotime($send_date));
}

$current_date_iss=date('Y-m-d');


               $issue_query_total = mysqli_num_rows(mysqli_query($db,"SELECT vi.starting_page_number, vi.stage,vi.vi_id,r.received_date,r.due_date,vi.received_date as rcv_dt,vi.due_date as due_dt ,volume_number,issue_number,j.j_code,(SELECT COUNT(id) FROM inw_inward_dtl as wd WHERE v_issue_id = vi.vi_id AND status='Delivery' $dept) as ccount,(SELECT COUNT(id) FROM inw_inward_dtl as wd WHERE v_issue_id = vi.vi_id AND status !='Delivery' $dept) as pcount  FROM inw_volume_issues as vi LEFT JOIN inw_issue_corrections as r ON (vi.`vi_id` =r.`vi_id` and r.ic_id = (select ic_id from inw_issue_corrections where vi_id = r.`vi_id` order by ic_id DESC limit 1))  , adm_journals as j WHERE vi.cust_id = j.j_cust_id and vi.j_id = j.j_id and vi.status NOT IN ('Delivery','Client Review','Completed')  AND   DATE_FORMAT(vi.due_date,'%Y-%m-%d')>='" .date('Y-m-d', strtotime($sstart_date))."' AND  DATE_FORMAT(vi.due_date,'%Y-%m-%d')<='" .date('Y-m-d', strtotime($send_date))."'   $search ORDER BY vi.vi_id ASC"));

               $issue_query_total_previous =mysqli_num_rows(mysqli_query($db,"SELECT vi.starting_page_number, vi.stage,vi.vi_id,r.received_date,r.due_date,vi.received_date as rcv_dt,vi.due_date as due_dt ,volume_number,issue_number,j.j_code,(SELECT COUNT(id) FROM inw_inward_dtl as wd WHERE v_issue_id = vi.vi_id AND status='Delivery' $dept) as ccount,(SELECT COUNT(id) FROM inw_inward_dtl as wd WHERE v_issue_id = vi.vi_id AND status !='Delivery' $dept) as pcount  FROM inw_volume_issues as vi LEFT JOIN inw_issue_corrections as r ON (vi.`vi_id` =r.`vi_id` and r.ic_id = (select ic_id from inw_issue_corrections where vi_id = r.`vi_id` order by ic_id DESC limit 1))  , adm_journals as j WHERE vi.cust_id = j.j_cust_id and vi.j_id = j.j_id and vi.status NOT IN ('Delivery','Client Review','Completed')  AND   DATE_FORMAT(vi.due_date,'%Y-%m-%d')>='" .date('Y-m-d', strtotime($sstart_date))."' AND  DATE_FORMAT(vi.due_date,'%Y-%m-%d')<'" .date('Y-m-d')."'   $search ORDER BY vi.vi_id ASC"));


               ?>
         <td><a onclick="loaddata1('<?php echo date('Y-m-d', strtotime($tsstart_date));?>','<?php echo date('Y-m-d', strtotime($end_prev_date));?>','<?php echo $_REQUEST['wcust_id'];?>','<?php echo $_REQUEST['dept'];?>','ISSUEALL');" style="cursor:pointer;"><b style="color:red;"><?php echo $issue_query_total_previous;?></b></a></td>

               <?php 
					 	while (strtotime($current_date_iss) <= strtotime($send_date))
						{
	
		
                    $issue_query = "SELECT vi.starting_page_number, vi.stage,vi.vi_id,r.received_date,r.due_date,vi.received_date as rcv_dt,vi.due_date as due_dt ,volume_number,issue_number,j.j_code,(SELECT COUNT(id) FROM inw_inward_dtl as wd WHERE v_issue_id = vi.vi_id AND status='Delivery' $dept) as ccount,(SELECT COUNT(id) FROM inw_inward_dtl as wd WHERE v_issue_id = vi.vi_id AND status !='Delivery' $dept) as pcount  FROM inw_volume_issues as vi LEFT JOIN inw_issue_corrections as r ON (vi.`vi_id` =r.`vi_id` and r.ic_id = (select ic_id from inw_issue_corrections where vi_id = r.`vi_id` order by ic_id DESC limit 1))  , adm_journals as j WHERE vi.cust_id = j.j_cust_id and vi.j_id = j.j_id and vi.status NOT IN ('Delivery','Client Review','Completed')  AND  DATE_FORMAT(vi.due_date,'%Y-%m-%d')='" .date('Y-m-d', strtotime($current_date_iss))."' $search ORDER BY vi.vi_id ASC ";
			  
			  
            $issue_run_query = $db->query($issue_query) or die($db->error);
            $rev_row = $issue_run_query->fetch_assoc();
            $issue_id_count = $issue_id_count+$issue_run_query->num_rows;
			
						?>
				<td> <a onclick="loaddata('<?php echo date('Y-m-d', strtotime($current_date_iss));?>','<?php echo $_REQUEST['wcust_id'];?>','<?php echo $_REQUEST['dept'];?>','ISSUE');" style="cursor:pointer;">
                <?php if($current_date_iss<date('Y-m-d') && $issue_run_query->num_rows>0){?><b style="color:red;"><?php echo $issue_run_query->num_rows;?></b><?php }else{?><span style="color:#000;"><?php echo $issue_run_query->num_rows;?></span><?php }?>
            </a></td>
						<?php 
						
						$current_date_iss = date ("Y-m-d", strtotime("+1 days", strtotime($current_date_iss)));


						} 
						?>
                                <td><a onclick="loaddata1('<?php echo date('Y-m-d', strtotime($tsstart_date));?>','<?php echo date('Y-m-d', strtotime($tsend_date));?>','<?php echo $_REQUEST['wcust_id'];?>','<?php echo $_REQUEST['dept'];?>','ISSUEALL');" style="cursor:pointer;"><b style="color:#0000FF;"><?php echo $issue_query_total;?></b></a></td>

			
                                                                                   
                 </tr>
				 
				  <tr class="totalrow">
				  
				<th><b style="color:#0000FF;">TOTAL</b></th>
							<?php 
									
if ($_REQUEST['date_wise'] == "d")
{

    if($_REQUEST['wfrom_dt']!="")
    {
    $sstart_date =date('Y-m-d',strtotime($_REQUEST['wfrom_dt']));
    $send_date =date('Y-m-d',strtotime($_REQUEST['wto_dt']));
    }
}
else if ($_REQUEST['date_wise'] == "m") {
    
$explode_month=explode("-",$_REQUEST['mfrom_dt']);
$year = $explode_month[1]; // Replace with the desired year
$month =$explode_month[0];   // Replace with the desired month (1-12)

// Create a date string in the format "YYYY-MM-01" for the first day of the specified month and year
$sstart_date = date("Y-m-01", strtotime("$year-$month-01"));

// Create a date string in the format "YYYY-MM-t" for the last day of the specified month and year
//$send_date = date("Y-m-t", strtotime("$year-$month-01"));
if($month==date('m'))
{
    $send_date = date("Y-m-d", strtotime("$year-$month-$day"));
}
else
{
    $send_date = date("Y-m-t", strtotime("$year-$month-01"));
}
//echo "Start Date: " . $start_date . "<br>";
//echo "End Date: " . $end_date;

}
else
{
$sstart_date =$fds;
$send_date =$tds;	

$tsstart_date =date('Y-m-d',strtotime($sstart_date));
$tsend_date =date('Y-m-d',strtotime($send_date));
}

$current_date_total=date('Y-m-d');
//
 $fp_query_total_prev = "SELECT count(id) as stage_count FROM `inw_inward_dtl` as wd WHERE DATE_FORMAT(due_dt,'%Y-%m-%d')>='".date('Y-m-d', strtotime($sstart_date))."'  and  DATE_FORMAT(due_dt,'%Y-%m-%d')<'".date('Y-m-d')."' and stage='FP' and status != 'Client Review' $search $dept  ";
			  
			  
$fp_run_query_total_prev = $db->query($fp_query_total_prev) or die($db->error);
$fp_row_total_prev = $fp_run_query_total_prev->fetch_assoc();
$fp_id_count_total_prev =$fp_row_total_prev['stage_count'];

                
  $rev_query_total_prev = "SELECT wd.id,wd.cust_id, wd.j_id, wd.status, r.due_date, r.received_date, wd.pub_id, wd.stage, wd.department_id,aq_status FROM inw_inward_dtl as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)),adm_journals as j  WHERE  wd.j_id = j.j_id and wd.status != 'Client Review'  AND  DATE_FORMAT(r.due_date,'%Y-%m-%d')>='".date('Y-m-d', strtotime($sstart_date))."'  and  DATE_FORMAT(r.due_date,'%Y-%m-%d')<'".date('Y-m-d')."'  AND r.created_on > '2023-01-01' AND wd.stage LIKE 'REV%' AND wd.status NOT IN ('Client Review','Completed') AND wd.id = r.inw_id $search $dept GROUP BY r.inw_id ORDER BY wd.cust_id ASC ";
  
  
$rev_run_query_total_prev = $db->query($rev_query_total_prev) or die($db->error);
$rev_row_total_prev = $rev_run_query_total_prev->fetch_assoc();
$rev_id_count_total_prev =$rev_run_query_total_prev->num_rows;


  $fin_query_total_prev = "SELECT wd.id,wd.cust_id, wd.j_id, wd.status, r.due_date, r.received_date, wd.pub_id, wd.stage, wd.department_id,aq_status FROM inw_inward_dtl as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)),adm_journals as j  WHERE  wd.j_id = j.j_id and wd.status != 'Client Review'  AND  DATE_FORMAT(r.due_date,'%Y-%m-%d')>='".date('Y-m-d', strtotime($sstart_date))."'  and  DATE_FORMAT(r.due_date,'%Y-%m-%d')<'".date('Y-m-d')."'  AND r.created_on > '2023-01-01' AND wd.stage LIKE 'FIN%' AND wd.status NOT IN ('Client Review','Completed') AND wd.id = r.inw_id $search $dept GROUP BY r.inw_id ORDER BY wd.cust_id ASC ";
  
  
$fin_run_query_total_prev = $db->query($fin_query_total_prev) or die($db->error);
$fin_row_total_prev = $fin_run_query_total_prev->fetch_assoc();
$fin_id_count_total_prev =$fin_run_query_total_prev->num_rows;

 $issue_query_total_prev = "SELECT vi.starting_page_number, vi.stage,vi.vi_id,r.received_date,r.due_date,vi.received_date as rcv_dt,vi.due_date as due_dt ,volume_number,issue_number,j.j_code,(SELECT COUNT(id) FROM inw_inward_dtl as wd WHERE v_issue_id = vi.vi_id AND status='Delivery' $dept) as ccount,(SELECT COUNT(id) FROM inw_inward_dtl as wd WHERE v_issue_id = vi.vi_id AND status !='Delivery'$dept) as pcount  FROM inw_volume_issues as vi LEFT JOIN inw_issue_corrections as r ON (vi.`vi_id` =r.`vi_id` and r.ic_id = (select ic_id from inw_issue_corrections where vi_id = r.`vi_id` order by ic_id DESC limit 1))  , adm_journals as j WHERE vi.cust_id = j.j_cust_id and vi.j_id = j.j_id and vi.status NOT IN ('Delivery','Client Review','Completed')  AND   DATE_FORMAT(vi.due_date,'%Y-%m-%d')>='".date('Y-m-d', strtotime($sstart_date))."'  and  DATE_FORMAT(vi.due_date,'%Y-%m-%d')<'".date('Y-m-d')."'  $search ORDER BY vi.vi_id ASC ";
  
  
$issue_run_query_total_prev = $db->query($issue_query_total_prev) or die($db->error);
$issue_row_total_prev = $issue_run_query_total_prev->fetch_assoc();
$issue_id_count_total_prev =$issue_run_query_total_prev->num_rows;

?>
<td><a onclick="loaddata1('<?php echo date('Y-m-d', strtotime($tsstart_date));?>','<?php echo date('Y-m-d', strtotime($end_prev_date));?>','<?php echo $_REQUEST['wcust_id'];?>','<?php echo $_REQUEST['dept'];?>','TALL');" style="cursor:pointer;"><b style="color:#0000FF;"><?php echo $fp_id_count_total_prev+$rev_id_count_total_prev+$fin_id_count_total_prev+$issue_id_count_total_prev;?></b></a></td>


<?php 

						while (strtotime($current_date_total) <= strtotime($send_date))
						{
							
							
							 $fp_query = "SELECT count(id) as stage_count FROM `inw_inward_dtl` as wd WHERE DATE_FORMAT(due_dt,'%Y-%m-%d')='".date('Y-m-d', strtotime($current_date_total))."' and stage='FP' and status != 'Client Review' $search $dept  ";
			  
			  
            $fp_run_query = $db->query($fp_query) or die($db->error);
            $fp_row = $fp_run_query->fetch_assoc();
            $fp_id_count =$fp_row['stage_count'];
			
							
							 $rev_query = "SELECT wd.id,wd.cust_id, wd.j_id, wd.status, r.due_date, r.received_date, wd.pub_id, wd.stage, wd.department_id,aq_status FROM inw_inward_dtl as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)),adm_journals as j  WHERE  wd.j_id = j.j_id and wd.status != 'Client Review'  AND  DATE_FORMAT(r.due_date,'%Y-%m-%d')='" .date('Y-m-d', strtotime($current_date_total))."' AND r.created_on > '2023-01-01' AND wd.stage LIKE 'REV%' AND wd.status NOT IN ('Client Review','Completed') AND wd.id = r.inw_id $search  $dept GROUP BY r.inw_id ORDER BY wd.cust_id ASC ";
			  
			  
            $rev_run_query = $db->query($rev_query) or die($db->error);
            $rev_row = $rev_run_query->fetch_assoc();
            $rev_id_count =$rev_run_query->num_rows;
			
			
			 $fin_query = "SELECT wd.id,wd.cust_id, wd.j_id, wd.status, r.due_date, r.received_date, wd.pub_id, wd.stage, wd.department_id,aq_status FROM inw_inward_dtl as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)),adm_journals as j  WHERE  wd.j_id = j.j_id and wd.status != 'Client Review'  AND  DATE_FORMAT(r.due_date,'%Y-%m-%d')='" .date('Y-m-d', strtotime($current_date_total))."' AND r.created_on > '2023-01-01' AND wd.stage LIKE 'FIN%' AND wd.status NOT IN ('Client Review','Completed') AND wd.id = r.inw_id $search $dept GROUP BY r.inw_id ORDER BY wd.cust_id ASC ";
			  
			  
            $fin_run_query = $db->query($fin_query) or die($db->error);
            $fin_row = $fin_run_query->fetch_assoc();
            $fin_id_count =$fin_run_query->num_rows;

             $issue_query = "SELECT vi.starting_page_number, vi.stage,vi.vi_id,r.received_date,r.due_date,vi.received_date as rcv_dt,vi.due_date as due_dt ,volume_number,issue_number,j.j_code,(SELECT COUNT(id) FROM inw_inward_dtl as wd WHERE v_issue_id = vi.vi_id AND status='Delivery' $dept) as ccount,(SELECT COUNT(id) FROM inw_inward_dtl as wd WHERE v_issue_id = vi.vi_id AND status !='Delivery' $dept) as pcount  FROM inw_volume_issues as vi LEFT JOIN inw_issue_corrections as r ON (vi.`vi_id` =r.`vi_id` and r.ic_id = (select ic_id from inw_issue_corrections where vi_id = r.`vi_id` order by ic_id DESC limit 1))  , adm_journals as j WHERE vi.cust_id = j.j_cust_id and vi.j_id = j.j_id and vi.status NOT IN ('Delivery','Client Review','Completed')  AND  DATE_FORMAT(vi.due_date,'%Y-%m-%d')='" .date('Y-m-d', strtotime($current_date_total))."' $search ORDER BY vi.vi_id ASC ";
			  
			  
            $issue_run_query = $db->query($issue_query) or die($db->error);
            $issue_row = $issue_run_query->fetch_assoc();
            $issue_id_count =$issue_run_query->num_rows;
			
			
						?>
				<td><a onclick="loaddata2('<?php echo date('Y-m-d', strtotime($current_date_total));?>','<?php echo $_REQUEST['wcust_id'];?>','<?php echo $_REQUEST['dept'];?>','DALL');" style="cursor:pointer;"><b style="color:#0000FF;"><?php echo $fp_id_count+$rev_id_count+$fin_id_count+$issue_id_count;?></b></a></td>
						<?php 
												$current_date_total = date ("Y-m-d", strtotime("+1 days", strtotime($current_date_total)));

						}
						?>
				<td> <a onclick="loaddata1('<?php echo date('Y-m-d', strtotime($tsstart_date));?>','<?php echo date('Y-m-d', strtotime($tsend_date));?>','<?php echo $_REQUEST['wcust_id'];?>','<?php echo $_REQUEST['dept'];?>','TALL');" style="cursor:pointer;"><b style="color:#0000FF;"><?php echo $fp_query_total+$rev_query_total+$fin_query_total+$issue_query_total;?></b></a></td>

                 </tr>
                <?php
              
           // } 
			  $r++;
			  }
		
		?>
		
		   <?php if(isset($_REQUEST['filter'])) { ?></tbody></table></div>
														
		<div id="article_list"></div>			
<div id="loader">
        <img src="loader.gif" alt="Loading...">
    </div>
</section><?php } else { ?>
<div class="mu-empty mu-reveal"><i class="fa fa-table"></i>Choose the period and filters, then press <b>Get Result</b>.</div>
<?php } ?>
      </div>
      <!-- Footer Section -->
     
  
	
	 <!-- Footer Section -->
     <?php echo footer(); ?>

    </div>
 




 
    



<!--- Footer Section -->
<?php echo scripts(); ?>	

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- jQuery UI (Datepicker) -->
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

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

    </script>

 <script>
 
           function loaddata(due_date,cust,dept,st)
{
				$("#loader").show(); // Show the loader
	
				//alert(cust);
				//alert(st);

                $.ajax({
                    url: "artilce_list_on_date.php", // Replace with your PHP script URL
                    method: "POST", // You can also use "POST" if needed
                    dataType: "html",
					data: { cust_id: cust,dept_id: dept, stage: st, dt: due_date }, // Pass the arguments as an object

                    success: function(response) {
                        $("#article_list").html(response);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("Error: " + textStatus, errorThrown);
                    },
					complete: function() {
                       $("#loader").hide(); // Hide the loader after AJAX call is complete
                    }
                });
            }

            function loaddata1(start_date,due_date,cust,dept,st)
{
				$("#loader").show(); // Show the loader
	
				//alert(cust);
				//alert(st);

                $.ajax({
                    url: "artilce_list_on_date_all.php", // Replace with your PHP script URL
                    method: "POST", // You can also use "POST" if needed
                    dataType: "html",
					data: { cust_id: cust,dept_id: dept, stage: st, sdt: start_date, dt: due_date }, // Pass the arguments as an object

                    success: function(response) {
                        $("#article_list").html(response);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("Error: " + textStatus, errorThrown);
                    },
					complete: function() {
                       $("#loader").hide(); // Hide the loader after AJAX call is complete
                    }
                });
            }

            function loaddata2(due_date,cust,dept,st)
{
				$("#loader").show(); // Show the loader
	
				//alert(cust);
				//alert(st);

                $.ajax({
                    url: "artilce_list_on_date_all.php", // Replace with your PHP script URL
                    method: "POST", // You can also use "POST" if needed
                    dataType: "html",
					data: { cust_id: cust,dept_id: dept, stage: st, dt: due_date }, // Pass the arguments as an object

                    success: function(response) {
                        $("#article_list").html(response);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("Error: " + textStatus, errorThrown);
                    },
					complete: function() {
                       $("#loader").hide(); // Hide the loader after AJAX call is complete
                    }
                });
            }
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
	 <script src="assets/js/select2/select2.min.js"></script>
   
    <script>
      $(".customer_drop").select2({
        placeholder: "Select Customer",
      });
     
      $(".department_drop").select2({
        placeholder: "Select Department",
      });
    </script>
  <script src="dv-rangepicker.js?v=1"></script>
<?php mu_foot(); ?>
</body>
</html>
