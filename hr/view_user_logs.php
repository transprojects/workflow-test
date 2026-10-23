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
                                        <label  class="form-label font-w600" for="validationCustom01">Work Location<span class="text-danger scale5 ms-2">*</span></label>
										
								<select id="location_id" name="location_id" class="form-control" data-placeholder="Choose">
									<option value="">-Select-</option>
								   	<option value="CHN">-Chennai-</option>
									<option value="TVL" selected="selected">-Tirunelveli-</option>
								</select>


                                    </div>
								  <div class="col-xl-3  col-md-3 mb-4">
                                        <label  class="form-label font-w600" for="validationCustom01">Choose Department<span class="text-danger scale5 ms-2">*</span></label>	
	 <?php
                    $statement = $pdo->prepare("SELECT dept_name,dept_code FROM `adm_dept_master` WHERE parent_id=0 ORDER BY `dept_name` ASC ");
					$statement->execute();
					$result = $statement->fetchAll();							
					?>
					<select id="dep_id" name="dep_id" class="form-control" data-placeholder="Choose">
						<option value="">-Select-</option>
						<?php
						foreach ($result as $row) { ?>
						<option value="<?php echo $row['dept_code']; ?>" <?php if($_REQUEST['dep_id'] ==$row['dept_code']){?> selected <?php } ?>><?php echo $row['dept_name']; ?></option>
						<?php } ?>
					</select>
	   </div>
									
                                   <div class="col-xl-3  col-md-3 mb-4">
                                        <label  class="form-label font-w600" for="validationCustom01">Employees<span class="text-danger scale5 ms-2">*</span></label>
                              <select class="multi-select select2-hidden-accessible" name="user_id[]" id="user_id" multiple="" data-select2-id="1" tabindex="-1" aria-hidden="true">
                                                    </select>
													


                                    </div>
									<div class="col-xl-2  col-md-2 mb-4" style="margin-top:30px;">
											<input type="checkbox" class="form-check-input" id="chkall"> <span style="margin-top:15px;">Select All</span>
									</div>
                                    <div class="col-xl-2  col-md-2 mb-4">
                                        <label  class="form-label font-w600" for="validationCustom01">From Date <span class="text-danger scale5 ms-2">*</span></label>
                                          <div class="input-group">
											 <div class="input-group-text"><i class="far fa-clock"></i></div>
											<input size="16" type="text" value="<?php echo date('d-m-Y',strtotime($fdate));?> " class="form-control form_datetime " name="fdate" id="validationCustom017" placeholder="From Date">
											
										</div>

                                    </div>
                                     <div class="col-xl-2  col-md-2 mb-4">
                                        <label  class="form-label font-w600" for="validationCustom01">To Date <span class="text-danger scale5 ms-2">*</span></label>
                                          <div class="input-group">
											 <div class="input-group-text"><i class="far fa-clock"></i></div>
											<input size="16" type="text" value="<?php echo date('d-m-Y',strtotime($tdate));?> " class="form-control form_datetime " name="tdate" id="validationCustom017" placeholder="To Date">
											
										</div>

                                    </div>

                                    <div class="col-xl-3  col-md-3 mb-4">
									<button type="submit" name="btn_download" class="btn btn-secondary" style="margin-top: 25px;">Download</button>
                                    <a href="view_user_logs.php" class="btn btn-primary me-3" style="margin-top: 25px;">Reset</a>
                                    </div>


                                   

                                            
											
                                          


                                        </div>
                                           
                                </div>
                            </div>
              
                    </div>

                <!-- row -->


              
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
//$mstaff=mysqli_fetch_array(mysqli_query($db,"select * from users where id='".$_REQUEST['user_id']."'"));
//$mstaff_name=$mstaff['full_name'];
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
		  title: 'User Log Details'
       } 
     ] 

  });
  
  
 $("#chkall").click(function(){
      if($("#chkall").is(':checked') ){ //select all
        $("#user_id").find('option').prop("selected",true);
        $("#user_id").trigger('change');
      } else { //deselect all
        $("#user_id").find('option').prop("selected",false);
        $("#user_id").trigger('change');
      }
  });

		function populateSelect(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.full_name).val(item.id));
            });
        }


		// Handle changes in the "countries" select element
            $("#dep_id").on("change", function() {
                var location = $('#location_id').val();
                var department_id = $(this).val();

                // Fetch connectedsource based on the selected connectedsource and populate the "name" select element
                $.ajax({
                    url: "get_users.php",
                    dataType: "json",
                    data: { dep_id: department_id,location_id: location },
                    success: function(data) {
                        populateSelect("user_id", data);

                        // Reset the "cities" select element when the country changes
                        //$("#user_id").empty();
                    }
                });
            });
			
	});
	</script>
	
   <?php 
if(isset($_REQUEST['btn_download']))
{
$user=implode(",",$_REQUEST['user_id']);
$location_id=$_REQUEST['location_id'];
header("location:download-user-logs.php?user=".$user."&startdate=".$_REQUEST['fdate']."&enddate=".$_REQUEST['tdate']."&location_id=".$_REQUEST['location_id']."");
}
?>
</body>
</html>