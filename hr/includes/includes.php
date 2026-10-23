<?php
include 'include/check.php';
$uri = basename($_SERVER['PHP_SELF']);
$sel_menu_url = $auth->getMenuURL($uri);
$sel_menu = $auth->getMenuid($_COOKIE["emp_id"], $uri);
$link_id='';
$permission='';

if(is_array($sel_menu) && count($sel_menu)==1)
{
	if(isset($sel_menu[0]['menu_id']))
	{
		$link_id = $sel_menu[0]['menu_id'];
	}
}
else
{
	$sel_smenu = $auth->getSubMenuid($_COOKIE["emp_id"], $uri);
	if(isset($sel_smenu[0]['smenu_id']))
	{
		$link_id = $sel_smenu[0]['smenu_id'];
	}
}
if($link_id=='' && $sel_menu_url!='' && $_COOKIE["emp_ltype"]!='Super Admin')
	echo '<script>alert("You are unauthorized");window.location.replace("dashboard.php");</script>';

$permission = valoperator($link_id);
$permission1 = explode(",",$permission);
function pageLength() {
?>
	pageLength : 10,
	lengthMenu : [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
<?php  }
function styles() {
?>
        <!-- App Icons -->
        <link rel="shortcut icon" href="./assets/images/favicon.ico" />
        <!--Morris Chart CSS -->
        <link rel="stylesheet" href="./assets/plugins/morris/morris.css" />

        <!-- App css -->
        <link href="./assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="./assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="./assets/css/style.css" rel="stylesheet" type="text/css" />
<?php  } 
function scripts() {
?>	
	<script src="./assets/js/jquery.min.js"></script>
	<script src="./assets/js/popper.min.js"></script>
	<script src="./assets/js/bootstrap.min.js"></script>
	<script src="./assets/js/modernizr.min.js"></script>
	<script src="./assets/js/waves.js"></script>
	<script src="./assets/js/jquery.slimscroll.js"></script>
	<script src="./assets/js/jquery.nicescroll.js"></script>
	<script src="./assets/js/jquery.scrollTo.min.js"></script>
	<!-- App js -->
	<script src="./assets/js/app.js"></script>
<?php } 
function navigation() {
include 'include/check.php';	
?>
<div class="navbar-custom">
	<div class="container-fluid">
		<div id="navigation">
			<!-- Navigation Menu-->
			<ul class="navigation-menu">
				<li class="has-submenu<?php if(basename($_SERVER['PHP_SELF'])=="dashboard.php"){ ?> active <?php } ?>">
					<a href="dashboard.php"><i class="ti-home"></i>Dashboard</a>
				</li>
				<?php 
				$menu_records = $auth->showStaffMenu($_COOKIE['emp_id']);
				foreach($menu_records as $menu){
					$smenu_records = $auth->showStaffSubMenu($_COOKIE['emp_id'],$menu['menu_id']);
					if($smenu_records!='') $num_smenu=count($smenu_records);
				?>
				<li class="has-submenu <?php if(basename($_SERVER['PHP_SELF'])==$menu['menu_url']){ ?> active <?php } ?>">
					<a href="<?php echo $menu['menu_url']; ?>"><i class="ti-light-bulb"></i><?php echo $menu['menu_name']; ?></a>
					<?php if($smenu_records!=''){ ?>
					<ul class="submenu">
						<li>
							<ul>
							<?php 
							if (is_array($smenu_records) || is_object($smenu_records))
							{
							foreach($smenu_records as $smenu){ ?>
								<li><a href="<?php echo $smenu['smenu_url']; ?>"><?php echo $smenu['smenu_name']; ?></a></li>
							<?php }
							} ?>	
							</ul>
						</li>
					</ul>
					<?php } ?>						
				</li>
			<?php } ?>                                
			</ul>
		</div>
	</div>
</div>
<?php
} 
function footer() {
?>
<footer class="footer">
	<div class="container-fluid">
		<div class="row">
			<div class="col-12">
				© 2018 Eden Matrimony - Crafted with <i class="mdi mdi-heart text-danger"></i> by <a href="https://www.itacumens.com/" target="_blank" style="text-decoration: none;	">IT Acumens</a>.
			</div>
		</div>
	</div>
</footer>
<?php
}
function cheader() {
?>
<div class="topbar-main">
	<div class="container-fluid">
		<div class="logo">
			<a href="./dashboard.php" class="logo">
				<img src="./assets/images/logo-sm.png" alt="" height="22" class="logo-small" />
				<img src="./assets/images/logo.png" alt="" height="54" class="logo-large" />
			</a>
		</div>
		<div class="menu-extras topbar-custom">
			<ul class="list-inline float-right mb-0">
				<!-- User-->
				<li class="list-inline-item dropdown notification-list">
					<a class="nav-link dropdown-toggle arrow-none waves-effect nav-user" data-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
						<img src="./assets/images/users/avatar-1.jpg" alt="user" class="rounded-circle" />
					</a>
					<div class="dropdown-menu dropdown-menu-right profile-dropdown ">
						<a class="dropdown-item" href="#"><i class="dripicons-user text-muted"></i> Hi, <?php echo $_COOKIE['emp_name']; ?></a>
						<a class="dropdown-item" href="single-report.php"><i class="dripicons-view-list-large  text-muted"></i> Profiles Count </a>
						<a class="dropdown-item" href="expenses.php"><i class="dripicons-card  text-muted"></i> Expenses </a>
						<div class="dropdown-divider"></div>
						<a class="dropdown-item" href="logout.php"><i class="dripicons-exit text-muted"></i> Logout</a>
					</div>
				</li>
				<li class="menu-item list-inline-item">
					<a class="navbar-toggle nav-link">
						<div class="lines">
							<span></span>
							<span></span>
							<span></span>
						</div>
					</a>
				</li>
			</ul>
		</div>
		<div class="clearfix"></div>
	</div>
</div>
<?php	
}
?>