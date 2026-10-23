<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="keywords" content="" />
	<meta name="author" content="" />
	<meta name="robots" content="" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Jobick : Job Admin Bootstrap 5 Template" />
	<meta property="og:title" content="Jobick : Job Admin Bootstrap 5 Template" />
	<meta property="og:description" content="Jobick : Job Admin Bootstrap 5 Template" />
	<meta property="og:image" content="https://jobick.dexignlab.com/xhtml/social-image.png" />
	<meta name="format-detection" content="telephone=no">
	
	<!-- PAGE TITLE HERE -->
    <?php include('includes/title.php'); ?>
	<!-- FAVICONS ICON -->
	<link rel="shortcut icon" type="image/png" href="images/favicon.png" />
	<link href="vendor/jquery-nice-select/css/nice-select.css" rel="stylesheet">
	<link href="vendor/datatables/css/jquery.dataTables.min.css" rel="stylesheet">
	<link href="vendor/bootstrap-datepicker-master/css/bootstrap-datepicker.min.css" rel="stylesheet">
	
	<!-- Style css -->
    <link href="css/style.css" rel="stylesheet">
	
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

        <?php 
$error_message='';
if(isset($_POST['admin_change_password'])) {
	$valid = 1;

	if( empty($_POST['password']) || empty($_POST['re_password']) ) {
        $valid = 0;
        $error_message .= "Password can not be empty<br>";
    }

    if( !empty($_POST['password']) && !empty($_POST['re_password']) ) {
    	if($_POST['password'] != $_POST['re_password']) {
	    	$valid = 0;
	        $error_message .= "Passwords do not match<br>";	
    	}        
    }

    if($valid == 1) {


    	// updating the database
		$statement = $pdo->prepare("UPDATE users SET pwd=? WHERE id=?");
		$statement->execute(array(PwdHash($_POST['password']),$_SESSION['user']['id']));

    	$success_message = 'User Password is updated successfully.';
    }
}
?>
<div class="content-body">
    <div class="authincation h-100">
        <div class="container h-100">
            <div class="row justify-content-center h-100 align-items-center">
                <div class="col-md-6">
                    <div class="authincation-content">
                        <div class="row no-gutters">
                            <div class="col-xl-12">
                                <div class="auth-form">
									<div class="text-center mb-3">
                                    <a href="admin-dashboard.php"><img src="images/logo.png" style="width:250px;" alt=""></a>
									</div>
                                    <h4 class="text-center mb-4">Change Admin Password</h4>
                                        <?php if($error_message): ?>
                                            <div class="error">
                                            <?php echo $error_message; ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if($success_message): ?>
                                            <div class="success">
                                            <?php echo $success_message; ?>
                                            </div>
                                        <?php endif; ?>
                                    <form action="" method="post" name="admin_change_password_form">

										<div class="mb-3">
                                            <label class="mb-1"><strong>New Password</strong></label>
                                            <input type="password" name="password" class="form-control" value="" placeholder=
                                            "New Pasword">
                                        </div>
										<div class="mb-3">
                                            <label class="mb-1"><strong>Retype New Password</strong></label>
                                            <input type="password" name="re_password" class="form-control" value="" placeholder=
                                            "Confirm New Pasword">
                                        </div>                                        
                                        <div class="text-center">
                                            <button type="submit" name="admin_change_password" class="btn btn-primary btn-block">SUBMIT</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
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

		
</body>
</html>