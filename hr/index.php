
<!DOCTYPE html>
<html lang="en" class="h-100">

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
	<link href="vendor/jquery-nice-select/css/nice-select.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">

</head>
<?php
$error_message='';

if(isset($_POST['admin_login'])) {
        
    if(empty($_POST['admin_uname']) || empty($_POST['admin_password'])) {
        $error_message = 'Username and/or Password can not be empty<br>';
    } else {
		
		$email = strip_tags($_POST['admin_uname']);
		$password = strip_tags($_POST['admin_password']);

    	$statement = $pdo->prepare("SELECT * FROM users WHERE user_name=? AND id=?");
    	$statement->execute(array($email,'387'));
    	$total = $statement->rowCount();    
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);    
        if($total==0) {
            $error_message .= 'Username does not match<br>';
        } else {       
            foreach($result as $row) { 
                $row_password = $row['pwd'];
            }
        
			if ($row_password!=PwdHash($password,substr($row_password,0,9))) { 

                $error_message .= 'Password does not match<br>';
            } else {       
            
				$_SESSION['user'] = $row;
                header("location: view_user_logs.php");
            }
        }
    }

    
}
?>
<body class="vh-100">
    <div class="authincation h-100">
        <div class="container h-100">
            <div class="row justify-content-center h-100 align-items-center">
                <div class="col-md-6">
                    <div class="authincation-content">
                        <div class="row no-gutters">
                            <div class="col-xl-12">
                                <div class="auth-form">
									<div class="text-center mb-3">
										<a href="index.php" class="brand-logo">
											<img src="images/logo.png" style="width:300px;">
										</a>
									</div>
                                    <h4 class="text-center mb-4"></h4>
                                    <?php 
	    if( (isset($error_message)) && ($error_message!='') ):
	        echo '<div class="error">'.$error_message.'</div>';
	    endif;
	    ?>

                                    <form action="" name="admin_login_form" id="admin_login_form" method="post">
                                        <div class="mb-3">
                                            <label class="mb-1"><strong>Username</strong></label>
                                            <input type="text" name="admin_uname" class="form-control" value="" placeholder="Username">
                                        </div>
                                        <div class="mb-3">
                                            <label class="mb-1"><strong>Password</strong></label>
                                            <input type="password" name="admin_password" class="form-control" value="" placeholder="Password">
                                        </div>
                                        <div class="text-center">
                                            <button type="submit" name="admin_login" class="btn btn-primary btn-block">Sign In</button>
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


    <!--**********************************
        Scripts
    ***********************************-->
    <!-- Required vendors -->
	
    <script src="vendor/global/global.min.js"></script>
	<script src="vendor/jquery-nice-select/js/jquery.nice-select.min.js"></script>
    <script src="js/custom.min.js"></script>
    <script src="js/dlabnav-init.js"></script>
	<script src="js/styleSwitcher.js"></script>
</body>
</html>