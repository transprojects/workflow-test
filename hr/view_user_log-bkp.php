<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="keywords" content="" />
	<meta name="author" content="" />
	<meta name="robots" content="" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="format-detection" content="telephone=no">
	
	<!-- PAGE TITLE HERE -->
    <?php include('includes/title.php'); ?>
	
	<!-- FAVICONS ICON -->
	<link rel="shortcut icon" type="image/png" href="images/favicon.png" />
    <!-- Datatable -->
    <link href="vendor/datatables/css/jquery.dataTables.min.css" rel="stylesheet"> 
	
	<!--<link href='https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css' rel='stylesheet' type='text/css'> -->
   <link href='https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css' rel='stylesheet' type='text/css'>
   
    <link rel="stylesheet" href="vendor/select2/css/select2.min.css">
    <!-- Custom Stylesheet -->
	<link href="vendor/jquery-nice-select/css/nice-select.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
<style>
.dataTables_wrapper .dt-buttons {
  float:none;  
  text-align:center;
}
</style>
</head>
<body>

    <!--*******************
        Preloader start
    ********************-->
   <div id="preloader">
		<div class="lds-ripple">
			<div></div>
			<div></div>
		</div>
    </div>
    <!--*******************
        Preloader end
    ********************-->


    <!--**********************************
        Main wrapper start
    ***********************************-->
    <div id="main-wrapper">

       
		
		


		
		
       <!--**********************************
            Header start
        ***********************************-->
        <?php include('includes/header.php'); ?>
        <!--**********************************
            Header end ti-comment-alt
        ***********************************-->

        <!--**********************************
            Sidebar start
        ***********************************-->
        <?php include('includes/nav.php'); ?>
       
        <!--**********************************
            Sidebar end
        ***********************************-->

        <!--**********************************
            Content body start
        ***********************************-->
        <div class="content-body">
            <div class="container-fluid">
												<?php 
												//$user_id='';
												//$fdate='';
												//$tdate='';
												if(isset($_REQUEST['user_id']))
												{
                                                    $user_id=$_REQUEST['user_id'];
                                                }
                                                if(isset($_REQUEST['fdate']))
												{
                                                    $fdate=$_REQUEST['fdate'];
                                                }
												if(isset($_REQUEST['tdate']))
												{
                                                    $tdate=$_REQUEST['tdate'];
                                                }
                                                else
                                                {
                                                    $fdate=date('Y-m-d');
                                                    $tdate=date('Y-m-d');
													$user_id='';
                                                }
												?>

			<div class="card">
					<div class="card-body">
                        <form name="ldate_search" action="" method="post">
							<div class="form-validation">
								<div class="row">
                                   
                                   <div class="col-xl-3  col-md-3 mb-4">
                                        <label  class="form-label font-w600" for="validationCustom01">Employees<span class="text-danger scale5 ms-2">*</span></label>
                                            
                                        <select id="single-select" class="form-control" name="user_id" required>
									<option data-display="Select">Choose Employees...</option>
                                            <?php
                    $statement = $pdo->prepare("SELECT * FROM  users where  user_level in ('2','3','4','5','6','7') and approved=1 ");
					$statement->execute();
					$result = $statement->fetchAll();							
					foreach ($result as $row) {
						
						?>
                        <option  <?php if($user_id==$row['id']){?>selected<?php }?> value="<?php echo $row['id'] ?>"><?php echo $row['full_name'] ?></option>
                     <?php 
					}
					?>
                                   
                                                    </select>

                                    </div>
                                    <div class="col-xl-3  col-md-3 mb-4">
                                        <label  class="form-label font-w600" for="validationCustom01">From Date <span class="text-danger scale5 ms-2">*</span></label>
                                          <div class="input-group">
											 <div class="input-group-text"><i class="far fa-clock"></i></div>
											<input size="16" type="text" value="<?php echo date('d-m-Y',strtotime($fdate));?> " class="form-control form_datetime " name="fdate" id="validationCustom017" placeholder="From Date">
											
										</div>

                                    </div>
                                     <div class="col-xl-3  col-md-3 mb-4">
                                        <label  class="form-label font-w600" for="validationCustom01">To Date <span class="text-danger scale5 ms-2">*</span></label>
                                          <div class="input-group">
											 <div class="input-group-text"><i class="far fa-clock"></i></div>
											<input size="16" type="text" value="<?php echo date('d-m-Y',strtotime($tdate));?> " class="form-control form_datetime " name="tdate" id="validationCustom017" placeholder="To Date">
											
										</div>

                                    </div>

                                    <div class="col-xl-3  col-md-3 mb-4">
									<button type="submit" name="btn_search" class="btn btn-secondary" style="margin-top: 25px;">Search</button>
                                    <a href="view_user_logs.php" class="btn btn-primary me-3" style="margin-top: 25px;">Reset</a>
                                    </div>


                                   

                                            
											
                                          


                                        </div>
                                           
                                </div>
                            </div>
                </form>
                    </div>

                <!-- row -->


                <div class="row">

					<div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">User Logs  Overview</h4>                               
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="empTable" class="display" style="min-width: 845px">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Employee ID</th>
                                                <th>Employee Name</th>
                                                <th>Login In</th>
                                                <th>Login Out</th>
                                                <th>Count of Log Ins</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
				if(isset($_REQUEST['fdate']))
				{
				$start_date = date('Y-m-d',strtotime($fdate));
                $end_date =date('Y-m-d',strtotime($tdate));
				//$start_date_m = date ("Y-m-d", strtotime("-1 days", strtotime($start_date)));

					$statement = $pdo->prepare("SELECT * from users  where worklocation='TVL' and id='".$user_id."'  and approved=1 ");
					
					
					$statement->execute();
					$result = $statement->fetchAll();	
					$i=0;					
					foreach ($result as $row) {
						$i++;
						
						while (strtotime($start_date) <= strtotime($end_date)) {
							

		$sql_day_count=mysqli_query($db,"select * from login_history where lh_userid='".$row['id']."' and DATE_FORMAT(lh_current_dt,'%Y-%m-%d')='".$start_date."' order by lh_id desc ");
		$res_day_login=mysqli_fetch_array($sql_day_count);
		$num_day_login=mysqli_num_rows($sql_day_count);
		
		$res_login=mysqli_fetch_array(mysqli_query($db,"SELECT MIN(lh_current_dt) as intime, MAX(lh_logout_time) as outtime FROM login_history where DATE_FORMAT(lh_current_dt,'%Y-%m-%d')='".$start_date."' and lh_userid='".$row['id']."'"));
		
		//echo $assign_qry;
		$givendate=date('Y-m-d', strtotime($start_date));
        $MyGivenDateIn = strtotime($givendate);
        $ConverDate = date("l", $MyGivenDateIn);
        $ConverDateTomatch = strtolower($ConverDate);
       // echo $ConverDateTomatch;
		
		
		
		
						?>
						
					
					
                                            <tr <?php if($num_day_login==0 && $ConverDateTomatch!= "sunday" ){?> style="background-color:yellow !important;"<?php  }?>>
											<?php 
												if($ConverDateTomatch == "saturday")
												{
												?>
												<td style="color:orange !important;"><?php echo date('d-m-Y',strtotime($start_date));?> (Sat)</td>
												<?php
												}
												elseif($ConverDateTomatch == "sunday")
												{
												?>
												<td style="color:red !important;"><?php echo date('d-m-Y',strtotime($start_date));?>(Sun)</td>
												<?php
												}
												else
												{
												?>
												<td><?php echo date('d-m-Y',strtotime($start_date));?></td>
												<?php 
												}
											?>					  
                                                <td><?php echo $row['user_name']  ;?></td>
                                                <td><?php echo $row['full_name']  ;?></td>
												<?php 
												if($num_day_login>0)
												{
												?>
                                                <td><?php echo  date('H:i:s',strtotime($res_login['intime']));?></td>
                                                <td><?php echo  date('H:i:s',strtotime($res_login['outtime']));?></td>
												<?php 
												}
												else
												{
												?>
												<td style="color:red !important;">No logs</td>
                                                <td style="color:red !important;">No logs</td>
												<?php 
												}
												?>
												<?php 
												if($num_day_login>0)
												{
												?>
                                                <td><?php echo $num_day_login; ?></td>
												<?php 
												}
												elseif($num_day_login==0 && $ConverDateTomatch!= "sunday" )
												{
												?>
                                                <td style="color:red !important;"><?php echo $num_day_login; ?> (Leave) </td>
												<?php 
												}
												else
												{
												?>
                                                <td style="color:red !important;"><?php echo $num_day_login; ?></td>
												<?php 
												}
												?>
                                            </tr>
											
					
                                            <?php 
											
											$start_date = date ("Y-m-d", strtotime("+1 days", strtotime($start_date)));

						}
                    }
										}
                    ?>
                           
                                                                                                                                                                                                                        
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

				</div>
            </div>
        </div>
        <!--**********************************
            Content body end
        ***********************************-->


         <!--**********************************
            Footer start
        ***********************************-->
		 <!-- Datatable JS -->

		<?php include('includes/footer.php'); ?>
<?php 
$mstaff=mysqli_fetch_array(mysqli_query($db,"select * from users where id='".$_REQUEST['user_id']."'"));
$mstaff_name=$mstaff['full_name'];
?>
<!--**********************************
	Footer end
***********************************-->
	<script>
	$(document).ready(function(){
  var empDataTable = $('#empTable').DataTable({
     dom: 'Blfrtip',
	 button: {
               className: 'btn btn-primary'
          },
     buttons: [
       {
          extend: 'excelHtml5', 
		  text:'Download Excel',"className": 'btn btn-default btn-xs',
		  title: '<?php echo $mstaff_name;?> User Log Details'
       } 
     ] 

  });

});
	</script>
   
</body>
</html>