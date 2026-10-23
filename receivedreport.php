<?php include "includes/includes.php";
include ('includes/paginate.php'); 
?>
<?php
if(isset($_REQUEST['wgetaction']))
{
$search = "";
if ($_REQUEST['wcust_id'] != "" && $_REQUEST['wcust_id'] != 'all') {
    $search .= "and cust_id='" . $_REQUEST['wcust_id'] . "' ";
}

if ($_REQUEST['wj_id'] != "" && $_REQUEST['wj_id'] != 'all') {
    $search .= "and j_id='" . $_REQUEST['wj_id'] . "' ";
}

}
$fds=date('Y-m-d', strtotime('-5 days'));
$tds=date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>	

<style>
/* Pagination styles */
.pagination {
    display: inline-block;
    padding-left: 0;
    margin: 20px 0;
    border-radius: 4px;
    float: right; /* Align pagination to the right */
}

.pagination > li {
    display: inline;
}

.pagination > li > a,
.pagination > li > span {
    position: relative;
    float: left;
    padding: 6px 12px;
    margin-left: -1px;
    line-height: 1.42857143;
    color: #F26722; /* Orange color */
    text-decoration: none;
    background-color: #fff;
    border: 1px solid #ddd;
}

.pagination > li:first-child > a,
.pagination > li:first-child > span {
    margin-left: 0;
    border-bottom-left-radius: 4px;
    border-top-left-radius: 4px;
}

.pagination > li:last-child > a,
.pagination > li:last-child > span {
    border-bottom-right-radius: 4px;
    border-top-right-radius: 4px;
}

.pagination > li > a:hover,
.pagination > li > span:hover,
.pagination > li > a:focus,
.pagination > li > span:focus {
    background-color: #eee;
}

.pagination > .active > a,
.pagination > .active > span,
.pagination > .active > a:hover,
.pagination > .active > span:hover,
.pagination > .active > a:focus,
.pagination > .active > span:focus {
    z-index: 2;
    color: #fff;
    background-color: #F26722; /* Orange color */
    border-color: #F26722; /* Orange color */
    cursor: default;
}

/* Disabled styles */
.pagination > .disabled > span,
.pagination > .disabled > a,
.pagination > .disabled > a:hover,
.pagination > .disabled > a:focus,

/* 
.datepicker-month-year-only .ui-datepicker-calendar {
    display: none;
}
 */
    #loader {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            text-align: center;
            z-index: 9999;
        }

        #loader img {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
</style>


  </head>

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
	 
      <!-- Header logo section -->
      
      <!-- report section body starts -->
      <div class="containter-fluid">
        <div class="container-fluid mt-4">
          <p class="reportsectionheading ms-4">Received Report</p>
        </div>
		<form action="#" id="validation-form" method="post" autocomplete="off">
        <div class="container d-flex justify-content-center">
          <div
            class="col-12 col-sm-8 d-flex justify-content-center flex-column mt-4 monthly1outer"
          >
            <div class="d-flex justify-content-center">
              <div class="radio d-flex col-2 mt-4">
                <input type="radio"   name="date_wise" <?php if ($_REQUEST['date_wise'] == 'd' || $_REQUEST['date_wise'] != 'm') { ?> checked <?php } ?> id="daywise" value="d" />
                <label class="ms-2">Day</label>
              </div>
              <div class="radio d-flex col-2 mt-4">
                <input type="radio" name="date_wise" <?php if ($_REQUEST['date_wise'] == 'm') { ?> checked <?php } ?> id="monthwise" value="m" />
                <label class="ms-2">Month</label>
              </div>
            </div>
            <!-- <div class="container"> -->
            <div class="row mt-4 m-5">
              <div class="col-12 col-lg-4">
                <label class="mb-2">Choose Customer</label>
                <select class="form-select customer_drop" data-placeholder="Select Customer" name="wcust_id" id="wcust_id">
												    <option value="all" <?php if ($_REQUEST['wcust_id'] == 'all') { ?> selected <?php } ?> >Select All</option>

										  <?php
										  if($_COOKIE['user_level']=='3')
										  {
											$sql_customers = $auth->showCustomerlistbyuser($_COOKIE['cust_id'],$_COOKIE['ocust_id']);
										  }
										  else
										  {
											$sql_customers = $auth->showCustomerlist();
										  }
											foreach($sql_customers as $res_customers)
											{
												
									      	if(isset($_REQUEST['wcust_id']))
													{
										    	?>
										       <option <?php if ($_REQUEST['wcust_id'] == $res_customers['id']) { ?> selected <?php } ?> value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
													<?php
													}
													else
													{
													?>
													 <option value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
													<?php
													}
											}
											?>
                </select>
              </div>
			    <div class="col-12 col-lg-8" id="day_div" style="display:<?php
                                                                    if ($_REQUEST['date_wise'] == 'd' || $_REQUEST['date_wise'] != 'm') {
                                                                        echo 'block';
                                                                    } else {
                                                                        echo 'none';
                                                                    }
                                                                    ?>;">
            <div class="row">
              <div class="col-12 col-lg-6">
                <label class="mb-2">From Date</label>
                <div class="d-flex" >
                  <input type="text" class="form-control date-picker monthlyinputdate" id="from_dt" name="wfrom_dt" style="border-color: #dee2e6 !important" value="<?php
                                                                                    if ($_REQUEST['wfrom_dt'] != '') {
                                                                                        echo $_REQUEST['wfrom_dt'];
                                                                                    } else {
                                                                                        echo date('d-m-Y', strtotime('-5 days'));
                                                                                    }
                                                                                    ?>" readonly />
                </div>
              </div>
              <div class="col-12 col-lg-6">
                <label class="mb-2">To Date</label>
                <div class="d-flex">
                  <input
                    type="text" id="wto_dt" name="wto_dt" class="form-control monthlyinputdate1" style="border-color: #dee2e6 !important" value="<?php
                                                                                    if ($_REQUEST['wto_dt'] != '') {
                                                                                        echo $_REQUEST['wto_dt'];
                                                                                    } else {
                                                                                        echo date('d-m-Y', strtotime('+2 days'));
                                                                                    }
                                                                                    ?>" 
                  />
                </div>
              </div>
              </div>
			    </div>

			   <div class="col-12 col-lg-8" id="month_div" style="display:<?php
                                                                    if ($_REQUEST['date_wise'] == 'm') {
                                                                        echo 'block';
                                                                    } else {
                                                                        echo 'none';
                                                                    }
                                                                    ?>;">
                                                                       
				<div class="col-12 col-lg-6">
                <label class="mb-2">Month</label>
                <div class="d-flex">
                  <input type="text" id="mfrom_dt" name="mfrom_dt"  placeholder="Select Month"  class="form-control monthlyinputdate2" style="border-color: #dee2e6 !important" value="<?php
                                                                                    if ($_REQUEST['mfrom_dt'] != '') {
                                                                                        echo $_REQUEST['mfrom_dt'];
                                                                                    } else {
                                                                                        echo date('m-Y');
                                                                                    }
                                                                                    ?>" />
                </div>
              </div>
                 </div>
            </div>
            <!-- </div> -->
            <div class="col-12 mt-1 mb-4 d-flex justify-content-center">
			<input type="hidden" name="filter" id="filter" value="1">
              <button class="resultbtn" type="submit"  name="wgetaction" >Get Result</button>
              <button class="refreshbtn ms-4">
               <a href="receivedreport"> <img src="./assets/images/refresh.svg" /></a>
              </button>
            </div>
          </div>
        </div>
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
        <div class="container-fluid mt-4 table-responsive">
		
		<table class="table table-bordered monthlytable">
														
													    
															
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

<?php 


$rev_id_count=0;

while (strtotime($start_date) <= strtotime($end_date))
{
?>
<th style="width:45px;color:#F26722;font-size:12px;"> <?php echo date('d',strtotime($start_date));?></th>
<?php 

 $start_date = date ("Y-m-d", strtotime("+1 days", strtotime($start_date)));
}
?>
																	 <th style="color:#0000FF;">TOTAL</th>

																	
                                                                </tr>
                                                            </thead>
                                                                 <tbody>
		  <tr>
           <th style="color: #4648b8">FP </th>
		                                                         
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

               $fp_query_total = mysqli_num_rows(mysqli_query($db,"SELECT * FROM `inw_inward_dtl` WHERE DATE_FORMAT(recv_dt,'%Y-%m-%d')>='".date('Y-m-d', strtotime($sstart_date))."' and DATE_FORMAT(recv_dt,'%Y-%m-%d')<='".date('Y-m-d', strtotime($send_date))."' and stage='FP' and status != 'Client Review' $search  "));

					 	while (strtotime($sstart_date) <= strtotime($send_date))
						{
	
		
               $fp_query = "SELECT count(id) as stage_count FROM `inw_inward_dtl` WHERE DATE_FORMAT(recv_dt,'%Y-%m-%d')='".date('Y-m-d', strtotime($sstart_date))."' and stage='FP' and status != 'Client Review' $search  ";
			  
			  
            $fp_run_query = $db->query($fp_query) or die($db->error);
            $fp_row = $fp_run_query->fetch_assoc();
						?>
				<td><a onclick="loaddata('<?php echo date('Y-m-d', strtotime($sstart_date));?>','<?php echo $_REQUEST['wcust_id'];?>','FP');" style="cursor:pointer;"><?php if($sstart_date<date('Y-m-d') && $fp_row['stage_count']>0){?><b style="color:red;"><?php echo $fp_row['stage_count'];?></b><?php }else{?><span style="color:#000;"><?php echo $fp_row['stage_count'];?></span><?php }?></a></td>
						<?php 
						 //$fp_id_count++;
						$sstart_date = date ("Y-m-d", strtotime("+1 days", strtotime($sstart_date)));
						} 
						?>
          
			
                       <td><a onclick="loaddata1('<?php echo date('Y-m-d', strtotime($tsstart_date));?>','<?php echo date('Y-m-d', strtotime($tsend_date));?>','<?php echo $_REQUEST['wcust_id'];?>','FPALL');" style="cursor:pointer;"><b style="color:#0000FF;"><?php echo $fp_query_total;?></b></a></td>
                                    
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

               $rev_query_total = mysqli_num_rows(mysqli_query($db,"SELECT wd.id,wd.cust_id, wd.j_id, wd.status, r.due_date, r.received_date, wd.pub_id, wd.stage, wd.department_id,aq_status FROM inw_inward_dtl as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)),adm_journals as j  WHERE  wd.j_id = j.j_id and wd.status != 'Client Review'  AND  DATE_FORMAT(r.received_date,'%Y-%m-%d')>='" .date('Y-m-d', strtotime($sstart_date))."' AND  DATE_FORMAT(r.received_date,'%Y-%m-%d')<='" .date('Y-m-d', strtotime($send_date))."'  AND r.created_on > '2020-03-01' AND wd.stage LIKE 'REV%' AND wd.status NOT IN ('Client Review','Completed') AND wd.id = r.inw_id $search GROUP BY r.inw_id ORDER BY wd.cust_id ASC"));

					 	while (strtotime($sstart_date) <= strtotime($send_date))
						{
	
		
                   $rev_query = "SELECT wd.id,wd.cust_id, wd.j_id, wd.status, r.due_date, r.received_date, wd.pub_id, wd.stage, wd.department_id,aq_status FROM inw_inward_dtl as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)),adm_journals as j  WHERE  wd.j_id = j.j_id and wd.status != 'Client Review'  AND  DATE_FORMAT(r.received_date,'%Y-%m-%d')='" .date('Y-m-d', strtotime($sstart_date))."' AND r.created_on > '2020-03-01' AND wd.stage LIKE 'REV%' AND wd.status NOT IN ('Client Review','Completed') AND wd.id = r.inw_id $search GROUP BY r.inw_id ORDER BY wd.cust_id ASC ";
			  
			  
            $rev_run_query = $db->query($rev_query) or die($db->error);
            $rev_row = $rev_run_query->fetch_assoc();
            $rev_id_count = $rev_id_coun+$rev_run_query->num_rows;
			
						?>
				<td> <a onclick="loaddata('<?php echo date('Y-m-d', strtotime($sstart_date));?>','<?php echo $_REQUEST['wcust_id'];?>','REV');" style="cursor:pointer;"><?php if($sstart_date<date('Y-m-d') && $rev_run_query->num_rows>0){?><b style="color:red;"><?php echo $rev_run_query->num_rows;?></b><?php }else{?><span style="color:#000;"><?php echo $rev_run_query->num_rows;?></span><?php }?></a></td>
						<?php 
						
						$sstart_date = date ("Y-m-d", strtotime("+1 days", strtotime($sstart_date)));


						} 
						?>
                                <td><a onclick="loaddata1('<?php echo date('Y-m-d', strtotime($tsstart_date));?>','<?php echo date('Y-m-d', strtotime($tsend_date));?>','<?php echo $_REQUEST['wcust_id'];?>','REVALL');" style="cursor:pointer;"><b style="color:#0000FF;"><?php echo $rev_query_total;?></b></a></td>

			
                                                                                   
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

               $issue_query_total = mysqli_num_rows(mysqli_query($db,"SELECT vi.starting_page_number, vi.stage,vi.vi_id,r.received_date,r.due_date,vi.received_date as rcv_dt,vi.due_date as due_dt ,volume_number,issue_number,j.j_code,(SELECT COUNT(id) FROM inw_inward_dtl WHERE v_issue_id = vi.vi_id AND status='Delivery') as ccount,(SELECT COUNT(id) FROM inw_inward_dtl WHERE v_issue_id = vi.vi_id AND status !='Delivery') as pcount  FROM inw_volume_issues as vi LEFT JOIN inw_issue_corrections as r ON (vi.`vi_id` =r.`vi_id` and r.ic_id = (select ic_id from inw_issue_corrections where vi_id = r.`vi_id` order by ic_id DESC limit 1))  , adm_journals as j WHERE vi.cust_id = j.j_cust_id and vi.j_id = j.j_id and vi.status NOT IN ('Delivery','Client Review','Completed')  AND   DATE_FORMAT(vi.received_date,'%Y-%m-%d')>='" .date('Y-m-d', strtotime($sstart_date))."' AND  DATE_FORMAT(vi.received_date,'%Y-%m-%d')<='" .date('Y-m-d', strtotime($send_date))."'   $search ORDER BY vi.vi_id ASC"));

					 	while (strtotime($sstart_date) <= strtotime($send_date))
						{
	
		
                   $issue_query = "SELECT vi.starting_page_number, vi.stage,vi.vi_id,r.received_date,r.due_date,vi.received_date as rcv_dt,vi.due_date as due_dt ,volume_number,issue_number,j.j_code,(SELECT COUNT(id) FROM inw_inward_dtl WHERE v_issue_id = vi.vi_id AND status='Delivery') as ccount,(SELECT COUNT(id) FROM inw_inward_dtl WHERE v_issue_id = vi.vi_id AND status !='Delivery') as pcount  FROM inw_volume_issues as vi LEFT JOIN inw_issue_corrections as r ON (vi.`vi_id` =r.`vi_id` and r.ic_id = (select ic_id from inw_issue_corrections where vi_id = r.`vi_id` order by ic_id DESC limit 1))  , adm_journals as j WHERE vi.cust_id = j.j_cust_id and vi.j_id = j.j_id and vi.status NOT IN ('Delivery','Client Review','Completed')  AND  DATE_FORMAT(vi.received_date,'%Y-%m-%d')='" .date('Y-m-d', strtotime($sstart_date))."' $search ORDER BY vi.vi_id ASC ";
			  
			  
            $issue_run_query = $db->query($issue_query) or die($db->error);
            $rev_row = $issue_run_query->fetch_assoc();
            $issue_id_count = $issue_id_count+$issue_run_query->num_rows;
			
						?>
				<td> <a onclick="loaddata('<?php echo date('Y-m-d', strtotime($sstart_date));?>','<?php echo $_REQUEST['wcust_id'];?>','ISSUE');" style="cursor:pointer;">
                <?php if($sstart_date<date('Y-m-d') && $issue_run_query->num_rows>0){?><b style="color:red;"><?php echo $issue_run_query->num_rows;?></b><?php }else{?><span style="color:#000;"><?php echo $issue_run_query->num_rows;?></span><?php }?>
            </a></td>
						<?php 
						
						$sstart_date = date ("Y-m-d", strtotime("+1 days", strtotime($sstart_date)));


						} 
						?>
                                <td><a onclick="loaddata1('<?php echo date('Y-m-d', strtotime($tsstart_date));?>','<?php echo date('Y-m-d', strtotime($tsend_date));?>','<?php echo $_REQUEST['wcust_id'];?>','ISSUEALL');" style="cursor:pointer;"><b style="color:#0000FF;"><?php echo $issue_query_total;?></b></a></td>

			
                                                                                   
                 </tr>
				 
				    <tr class="totalrow">
				  
				<th style="color: #4648b8"><b style="color:#0000FF;">TOTAL</b></th>
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



						while (strtotime($sstart_date) <= strtotime($send_date))
						{
							
							
							 $fp_query = "SELECT count(id) as stage_count FROM `inw_inward_dtl` WHERE DATE_FORMAT(recv_dt,'%Y-%m-%d')='".date('Y-m-d', strtotime($sstart_date))."' and stage='FP' and status != 'Client Review' $search  ";
			  
			  
            $fp_run_query = $db->query($fp_query) or die($db->error);
            $fp_row = $fp_run_query->fetch_assoc();
            $fp_id_count =$fp_row['stage_count'];
			
							
							 $rev_query = "SELECT wd.id,wd.cust_id, wd.j_id, wd.status, r.due_date, r.received_date, wd.pub_id, wd.stage, wd.department_id,aq_status FROM inw_inward_dtl as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)),adm_journals as j  WHERE  wd.j_id = j.j_id and wd.status != 'Client Review'  AND  DATE_FORMAT(r.received_date,'%Y-%m-%d')='" .date('Y-m-d', strtotime($sstart_date))."' AND r.created_on > '2020-03-01' AND wd.stage LIKE 'REV%' AND wd.status NOT IN ('Client Review','Completed') AND wd.id = r.inw_id $search GROUP BY r.inw_id ORDER BY wd.cust_id ASC ";
			  
			  
            $rev_run_query = $db->query($rev_query) or die($db->error);
            $rev_row = $rev_run_query->fetch_assoc();
            $rev_id_count =$rev_run_query->num_rows;

            $issue_query = "SELECT vi.starting_page_number, vi.stage,vi.vi_id,r.received_date,r.due_date,vi.received_date as rcv_dt,vi.due_date as due_dt ,volume_number,issue_number,j.j_code,(SELECT COUNT(id) FROM inw_inward_dtl WHERE v_issue_id = vi.vi_id AND status='Delivery') as ccount,(SELECT COUNT(id) FROM inw_inward_dtl WHERE v_issue_id = vi.vi_id AND status !='Delivery') as pcount  FROM inw_volume_issues as vi LEFT JOIN inw_issue_corrections as r ON (vi.`vi_id` =r.`vi_id` and r.ic_id = (select ic_id from inw_issue_corrections where vi_id = r.`vi_id` order by ic_id DESC limit 1))  , adm_journals as j WHERE vi.cust_id = j.j_cust_id and vi.j_id = j.j_id and vi.status NOT IN ('Delivery','Client Review','Completed')  AND  DATE_FORMAT(vi.received_date,'%Y-%m-%d')='" .date('Y-m-d', strtotime($sstart_date))."' $search ORDER BY vi.vi_id ASC ";
			  
			  
            $issue_run_query = $db->query($issue_query) or die($db->error);
            $issue_row = $issue_run_query->fetch_assoc();
            $issue_id_count =$issue_run_query->num_rows;
			
			
						?>
				<td><a onclick="loaddata2('<?php echo date('Y-m-d', strtotime($sstart_date));?>','<?php echo $_REQUEST['wcust_id'];?>','DALL');" style="cursor:pointer;"><b style="color:#0000FF;"><?php echo $fp_id_count+$rev_id_count+$issue_id_count;?></b></a></td>
						<?php 
												$sstart_date = date ("Y-m-d", strtotime("+1 days", strtotime($sstart_date)));

						}
						?>
				<td> <a onclick="loaddata1('<?php echo date('Y-m-d', strtotime($tsstart_date));?>','<?php echo date('Y-m-d', strtotime($tsend_date));?>','<?php echo $_REQUEST['wcust_id'];?>','TALL');" style="cursor:pointer;"><b style="color:#0000FF;"><?php echo $fp_query_total+$rev_query_total+$issue_query_total;?></b></a></td>

                 </tr>
                <?php
              
           // } 
			  $r++;
			  }
		
		?>
		
		   </tbody>
                                                        </table>
														
		<div id="article_list"></div>			
<div id="loader">
        <img src="loader.gif" alt="Loading...">
    </div>
          
        </div>

        <!-- Table section starts-->

        
      </div>
      <!-- Table section ends -->
      <!-- Footer Section -->
     
  
	
	 <!-- Footer Section -->
     <?php echo footer(); ?>

    </div>
 




 
    



<!-- Footer Section -->
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
 
           function loaddata(due_date,cust,st)
{
				$("#loader").show(); // Show the loader
	
				//alert(cust);
				//alert(st);

                $.ajax({
                    url: "artilce_list_on_rdate.php", // Replace with your PHP script URL
                    method: "POST", // You can also use "POST" if needed
                    dataType: "html",
					data: { cust_id: cust, stage: st, dt: due_date }, // Pass the arguments as an object

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

            function loaddata1(start_date,due_date,cust,st)
{
				$("#loader").show(); // Show the loader
	
				//alert(cust);
				//alert(st);

                $.ajax({
                    url: "artilce_list_on_date_rall.php", // Replace with your PHP script URL
                    method: "POST", // You can also use "POST" if needed
                    dataType: "html",
					data: { cust_id: cust, stage: st, sdt: start_date, dt: due_date }, // Pass the arguments as an object

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

            function loaddata2(due_date,cust,st)
{
				$("#loader").show(); // Show the loader
	
				//alert(cust);
				//alert(st);

                $.ajax({
                    url: "artilce_list_on_date_rall.php", // Replace with your PHP script URL
                    method: "POST", // You can also use "POST" if needed
                    dataType: "html",
					data: { cust_id: cust, stage: st, dt: due_date }, // Pass the arguments as an object

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
