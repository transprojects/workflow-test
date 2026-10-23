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
				

                <!-- row -->


                <div class="row">

					<div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Employee </h4>                               
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="empTable" class="display" style="min-width: 845px">
                                        <thead>
                                            <tr>
								 
												
                                                <th>Name</th>
                                                <th>Employee ID</th>
                                                <th>Action</th>   
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
					$i=0;
					
					
                    $statement = $pdo->prepare("SELECT * from users where worklocation='TVL' and user_level=3 and approved=1 ");
					$statement->execute();
					$result = $statement->fetchAll();							
					foreach ($result as $row) {
						$i++;
						
							
						?>
                                            <tr>
                                            
                                                
                                                <td><?php echo $row['full_name'];?></td>
                                                <td><?php echo $row['user_name'];?></td>
												<td><a href="view_user_history.php?id=<?php echo $row['id'];?>" style="color:red;text-decoration:underline;">Login Details</a></td>  

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
		  title: 'User Details'
       } 
     ] 

  });

});
	</script>
   
</body>
</html>