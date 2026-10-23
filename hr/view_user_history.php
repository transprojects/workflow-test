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
                                                if(isset($_REQUEST['ldate']))
												{
                                                    $ldate=$_REQUEST['ldate'];
                                                }
                                                else
                                                {
                                                    $ldate=date('Y-m-d');
                                                }
												?>

			<div class="card">
					<div class="card-body">
                        <form name="ldate_search" action="" method="post">
							<div class="form-validation">
								<div class="row">
                                   
                                  
                                    <div class="col-xl-3  col-md-3 mb-4">
                                        <label  class="form-label font-w600" for="validationCustom01">Date <span class="text-danger scale5 ms-2">*</span></label>
                                          <div class="input-group">
											 <div class="input-group-text"><i class="far fa-clock"></i></div>
											<input size="16" type="text" value="<?php echo date('d-m-Y',strtotime($ldate));?> " class="form-control form_datetime " name="ldate" id="validationCustom017" placeholder="Login Date">
											
										</div>

                                    </div>
                                   

                                    <div class="col-xl-3  col-md-3 mb-4">
									<button type="submit" name="btn_search" class="btn btn-secondary" style="margin-top: 25px;">Search</button>
                                    <a href="view_user_history.php?id=<?php echo $_REQUEST['id'];?>" class="btn btn-primary me-3" style="margin-top: 25px;">Reset</a>
                                    </div>


                                   

                                            
											
                                          


                                        </div>
                                           
                                </div>
                            </div>
                </form>
                    </div>

                <!-- row -->
<?php 
$mstaff_name=mysqli_fetch_array(mysqli_query($db,"select * from users where id='".$_REQUEST['id']."'"));

?>

                <div class="row">

					<div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title"><?php echo $mstaff_name['full_name'];?> Login  Overview (<?php echo date('d-m-Y',strtotime($ldate));?> )</h4>                               
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="empTable" class="display" style="min-width: 845px">
                                        <thead>
                                            <tr>
                                                <th>S.no</th>
                                                <th>Login In time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
					$statement = $pdo->prepare("SELECT * from login_history where lh_userid='".$_REQUEST['id']."' and DATE_FORMAT(lh_current_dt,'%Y-%m-%d')='".date('Y-m-d',strtotime($ldate))."'");
					
					//$statement = $pdo->prepare("SELECT * from login_history where lh_userid='".$_REQUEST['id']."' and DATE_FORMAT(lh_current_dt,'%Y-%m-%d')='2023-07-10'");	
					
					$statement->execute();
					$result = $statement->fetchAll();	
					$i=0;					
					foreach ($result as $row) {
						$i++;
						
							
						?>
                                            <tr>
                                                <td><?php echo  $i;?></td>
                                                <td><?php echo  date('H:i:s',strtotime($row['lh_current_dt']));?></td>
                                            </tr>
                                            <?php 
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
		<?php include('includes/footer.php'); ?>

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
		  title: 'User Login Details'
       } 
     ] 

  });

});
	</script>
   
</body>
</html>