<?php 
$error_message = '';
$success_message = '';
$error_message1 = '';
$success_message1 = '';

require 'assets/mail/PHPMailer.php';
require 'assets/mail/Exception.php';
$mail = new PHPMailer\PHPMailer\PHPMailer();

// Check if the user is logged in or not
if(!isset($_SESSION['user'])) {
	header('location: index.php');
	exit;
}
if($_SESSION['user']['staff_type']==2)
{
$pagename=basename($_SERVER['PHP_SELF']);	
	if($pagename=='view_users.php')
	{
	header('location: logout.php');
	}
}
 $cur_page = substr($_SERVER["SCRIPT_NAME"],strrpos($_SERVER["SCRIPT_NAME"],"/")+1);


?>
<!--**********************************
            Nav header start
        ***********************************-->
        <div class="nav-header">
           <a href="view_articles.php" class="brand-logo" style="padding-left:0 !important;">
				<img src="images/logo.png" style="width:275px;">


            </a>
            <div class="nav-control">
                <div class="hamburger">
                    <span class="line"></span><span class="line"></span><span class="line"></span>
                </div>
            </div>
        </div>
        <!--**********************************
            Nav header end
        ***********************************-->
        
        <div class="header">
            <div class="header-content">
                <nav class="navbar navbar-expand">
                    <div class="collapse navbar-collapse justify-content-between">
                       <div class="header-left">
							<div class="dashboard_bar">
                               Employee Log Details   
							</div>
							
                        </div>
                        <ul class="navbar-nav header-right">

					

							<li class="nav-item dropdown header-profile">
                                <a class="nav-link " href="javascript:void(0);"  role="button" data-bs-toggle="dropdown">
						<div class="header-info2 d-flex align-items-center">
							<img src="images/admin.png" alt=""/>
							<div class="d-flex align-items-center sidebar-info">
								<div>
									<span class="font-w400 d-block"><?php echo $_SESSION['user']['full_name']; ?></span>&nbsp;&nbsp;
									<!--<small class="text-end font-w400">Superadmin</small>-->
								</div>	
								<i class="fas fa-chevron-down"></i>
							</div>
							
						</div>
					</a>
                                <div class="dropdown-menu dropdown-menu-end">

                                

                                  
                                    <a href="change-password.php" class="dropdown-item ai-icon">
                                        <svg id="icon-inbox1" xmlns="http://www.w3.org/2000/svg" class="text-success" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                                        <span class="ms-2">Change Password </span>
                                    </a>
                                    <a href="logout.php" class="dropdown-item ai-icon">
                                        <svg  xmlns="http://www.w3.org/2000/svg" class="text-danger" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                                        <span class="ms-2">Logout</span>
                                    </a>
                                </div>
                            </li>
							<li class="nav-item">
								
							</li>
                        </ul>
                    </div>
				</nav>
			</div>
		</div>