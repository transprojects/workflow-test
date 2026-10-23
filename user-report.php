<?php include "includes/includes.php";
include ('includes/paginate.php'); 
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
          <p class="reportsectionheading ms-4">User Report</p>
        </div>
		<form action="#" id="validation-form" method="post" autocomplete="off">
        <div class="container d-flex justify-content-center">
          <div
            class="col-12 col-sm-12 d-flex justify-content-center flex-column mt-4 monthly1outer"
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
              <div class="col-12 col-lg-3">
                <label class="mb-2">Choose Department</label>
                 <?php
    $query = "SELECT dept_name,dept_code FROM `adm_dept_master`  ORDER BY `dept_name` ASC ";
    $customers = $db->query($query) or ($db->error);		
    
    ?><select id="dep_id" name="dep_id" class="form-control"  data-placeholder="Choose" required>
    <option value="">-Select-</option>
    <?php while($row = $customers->fetch_assoc()){ ?>
    <option value="<?php echo $row['dept_code']; ?>" <?php if($_REQUEST['dep_id'] ==$row['dept_code']){?> selected <?php } ?>><?php echo $row['dept_name']; ?></option>
    <?php } ?>
    </select>
              </div>
			   <div class="col-12 col-lg-3">
                <label class="mb-2">Choose Users</label>
                 <?php
//read data
 $query_user = "SELECT `id`,`user_name`,`full_name` FROM `users` WHERE department_id = '".$_REQUEST['dep_id']."' and  approved='1' ORDER BY `full_name` ASC ";
$users_r = $db->query($query_user) or ($db->error);
?><select id="user_id" name="user_id" class="form-control"  data-placeholder="Choose">
   <option value="">-Select-</option>
<?php		
while($row_u = $users_r->fetch_assoc())
{
	?>   
<option value="<?php echo $row_u['id'];?>" <?php if($_REQUEST['user_id'] ==$row_u['id']){?> selected <?php } ?>><?php echo $row_u['full_name']; ?></option>
    <?php
}
?>
    </select>
              </div>
			    <div class="col-12 col-lg-6" id="day_div" style="display:<?php
                                                                    if ($_REQUEST['date_wise'] == 'd' || $_REQUEST['date_wise'] != 'm') {
                                                                        echo 'block';
                                                                    } else {
                                                                        echo 'none';
                                                                    }
                                                                    ?>;">
            <div class="row">
              <div class="col-12 col-lg-4">
                <label class="mb-2">From Date</label>
                <div class="d-flex">
                  <input type="text" class="form-control date-picker monthlyinputdate" id="from_dt" name="from_dt" style="border-color: #dee2e6 !important" value="<?php
                                                                                    if ($_REQUEST['from_dt'] != '') {
                                                                                        echo $_REQUEST['from_dt'];
                                                                                    } else {
                                                                                        echo date('d-m-Y', strtotime('-5 days'));
                                                                                    }
                                                                                    ?>" readonly />
                </div>
              </div>
              <div class="col-12 col-lg-4">
                <label class="mb-2">To Date</label>
                <div class="d-flex">
                  <input
                    type="text" id="to_dt" name="to_dt" class="form-control monthlyinputdate1" style="border-color: #dee2e6 !important" value="<?php
                                                                                    if ($_REQUEST['to_dt'] != '') {
                                                                                        echo $_REQUEST['to_dt'];
                                                                                    } else {
                                                                                        echo date('d-m-Y', strtotime('+2 days'));
                                                                                    }
                                                                                    ?>"/>
                </div>
              </div>
              </div>
			    </div>

			   <div class="col-12 col-lg-6" id="month_div" style="display:<?php
                                                                    if ($_REQUEST['date_wise'] == 'm') {
                                                                        echo 'block';
                                                                    } else {
                                                                        echo 'none';
                                                                    }
                                                                    ?>;">
                                                                       
				<div class="col-12 col-lg-4">
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
              <button class="resultbtn" type="submit"  name="getaction" >Get Result</button>
              <button class="refreshbtn ms-4">
               <a href="wipreport"> <img src="./assets/images/refresh.svg" /></a>
              </button>
            </div>
          </div>
        </div>
	</form>


        <!-- Table section -->
		<?php 
				//echo $var;
if($_POST['from_dt']!="" || $_POST['mfrom_dt']!="" || $_POST['user_id']!="")
{
?>		
	
        <div class="container-fluid mt-4 table-responsive">
		
		<table class="table table-bordered monthlytable">
														
					<thead>
					
                    <tr>
				 
                    <th class="center"> S.no</th>
					<th >Date</th>
					<th >#Assigned</th>
					<th >#Completed</th>
					<th >#Pending</th>
					<th >#WIP</th>
                    <th >#Page Count</th>
                  </tr>
                </thead>
                <tbody>								    
	<b style="color:red;"><?php echo $user_name['full_name'];?></b>
				
				
	<?php
	
$fds=date('d-m-Y', strtotime('-3 days'));
$tds=date('d-m-Y');
	if($_REQUEST['from_dt']!="")
{
$start_date =date('Y-m-d',strtotime($_REQUEST['from_dt']));
$end_date =date('Y-m-d',strtotime($_REQUEST['to_dt']));
}
if($_REQUEST['mfrom_dt']!="" && $_REQUEST['date_wise'] == 'm')
{
	//echo $_REQUEST['mfrom_dt'];
 $mstart_date =date('Y-m',strtotime("01-".$_REQUEST['mfrom_dt']));
$mend_date =date('Y-m',strtotime("30-".$_REQUEST['mfrom_dt']));	

$start_date =date('Y-m-01',strtotime($mstart_date));
 $end_date =date('Y-m-t',strtotime($mend_date));	
}
	$sno=1;	
	
	
	while (strtotime($start_date) <= strtotime($end_date))
{
	
	
				
			      $cnt_query = mysqli_fetch_array(mysqli_query($db,"SELECT * from user_report where dept_code='".$_REQUEST['dep_id']."' and user_id='".$_REQUEST['user_id']."' and DATE_FORMAT(date,'%Y-%m-%d')='".date('Y-m-d', strtotime($start_date))."'"));
				  
				
			$query = "SELECT dept_name,dept_code FROM `adm_dept_master` where dept_code='".$_REQUEST['dep_id']."'   ORDER BY `dept_name` ASC ";
			$customers = $db->query($query) or ($db->error);
			$row = $customers->fetch_assoc();			
		    $dep_name=$row['dept_name'];
		    $dep_id=$_REQUEST['dep_id'];
				
			
	

		//echo $assign_qry;
		$givendate=date('Y-m-d', strtotime($start_date));
        $MyGivenDateIn = strtotime($givendate);
        $ConverDate = date("l", $MyGivenDateIn);
        $ConverDateTomatch = strtolower($ConverDate);
       // echo $ConverDateTomatch;
   $total_assigned=$total_assigned+$cnt_query['assigned'];
   $total_page=$total_page+$cnt_query['pages_completed'];
		?> 
		
                  <tr>
                    <td><?php echo $sno; ?></td>
					<?php 
					  if(($ConverDateTomatch == "saturday" ))
					  {
					  ?>
					<td style="color:red !important;"><?php echo date('d M',strtotime($start_date));?> (Sat) </td>
					<?php
					  }
					   elseif(($ConverDateTomatch == "sunday"))
					  {
					  ?>
					<td style="color:red !important;"><?php echo date('d M',strtotime($start_date));?> (Sun) </td>
					<?php
					  }
					  else
					  {
					  ?>
					<td><?php echo date('d M',strtotime($start_date));?></td>
					<?php 
					  }
					?>

					<td><a href="user-report-details.php?user_id=<?php echo $_REQUEST['user_id'];?>&sdate=<?php echo $start_date;?>&status=A&dep_id=<?php echo $dep_id;?>&depcode=<?php echo $dep_name;?>" target="_blank"><?php echo $cnt_query['assigned']; ?></a></td>
					
                    <td>
					<a href="user-report-details.php?user_id=<?php echo $_REQUEST['user_id'];?>&sdate=<?php echo $start_date;?>&status=C&dep_id=<?php echo $dep_id;?>&depcode=<?php echo $dep_name;?>" target="_blank">
					Total :<?php echo $cnt_query['completed_total']; ?></a><br/>(<b style="color:green;">FP:<?php echo $cnt_query['completed_fp']; ?> </b> | <b style="color:red;">REV:<?php echo $cnt_query['completed_rev']; ?>  </b> |  <b style="color:orange;">ISSUE :<?php echo $cnt_query['completed_issu']; ?> </b> | <b style="color:blue;"> Final:<?php echo $cnt_query['completed_final']; ?> </b>)
					</td>
					<td><?php echo $cnt_query['pending']; ?></td>
					<td><a href="user-report-details.php?user_id=<?php echo $_REQUEST['user_id'];?>&sdate=<?php echo $start_date;?>&status=W&dep_id=<?php echo $dep_id;?>&depcode=<?php echo $dep_name;?>" target="_blank"><?php echo $cnt_query['wip']; ?></a></td>
					<td><?php echo $cnt_query['pages_completed']; ?></td>	
					
		  </td>
					
  
                  </tr>
                  <?php 
				  $sno++;
				 $start_date = date ("Y-m-d", strtotime("+1 days", strtotime($start_date)));

				  }
				  ?>
                  
              
	
                </tbody>
				<br/><b style="color:red;">Total File Count : <?php echo $total_assigned;?> / Total Page Count : <?php echo $total_page;?></b>					
				 
		
                                                        </table>
														
		<div id="article_list"></div>			
<div id="loader">
        <img src="loader.gif" alt="Loading...">
    </div>
          
        </div>
<?php }?>
        <!-- Table section starts-->

        
      </div>
      <!-- Table section ends -->
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
                    url: "artilce_list_on_date.php", // Replace with your PHP script URL
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
                    url: "artilce_list_on_date_all.php", // Replace with your PHP script URL
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
                    url: "artilce_list_on_date_all.php", // Replace with your PHP script URL
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
	
	<script type="text/javascript">
			$(document).ready(function(){
    $('#dep_id').on('change',function(){
        var dep_id = $(this).val();
        if(dep_id){
            $.ajax({
                type:'POST',
                url:'get_report_user.php',
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
