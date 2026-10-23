<?php  
session_start();
if(isset($_SESSION['staff_id']) && $_SESSION['staff_id']!="")
{
	include("include/conn.php");
}
else
{
	echo "<script>window.location='index.php';</script>";
}

$uri = basename($_SERVER['PHP_SELF']);
$menu_query_id = mysqli_fetch_array(mysqli_query($con,"select *,count(staff_menu.sm_menuid) as count_menu from menu,staff_menu where staff_menu.sm_menuid=menu.menu_id and menu_status='Active' and staff_menu.sm_staffid='".$_SESSION['staff_id']."' and menu_link='".$uri."'"));
if($menu_query_id['count_menu']==1)
{
$link=$menu_query_id['menu_id'];
}
else
{
	$sel_smenu=mysqli_fetch_array(mysqli_query($con,"SELECT * FROM staff_sub_menu INNER JOIN sub_menu ON sub_menu.smenu_id=staff_sub_menu.sbm_menuid where staff_sub_menu.sbm_staffid= '".$_SESSION['staff_id']."' and sub_menu.smenu_link='".$uri."'"));

$link=$sel_smenu['smenu_id'];
}
?>