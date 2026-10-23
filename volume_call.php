<?php
set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("dbc.php");
include("includes/global_variables.php");
include("scan_db.php");
include("volume_archive_imp.php");
//page_protect();
?>
<?php


$s_cust_id=$_COOKIE['v_cust_id'];
$s_j_id=$_COOKIE['v_j_id'];
$s_v_folder=$_COOKIE['v_folder'];
$vi_id = $_COOKIE['vi_id'];
// $newvol_folder = VOLUME_PREFIX.$volume_number111.$issue_number111;        

//echo "SELECT * FROM `inw_inward_dtl` WHERE cust_id = '".$s_cust_id."' AND j_id = '".$s_j_id."' AND volume_folder = '".$s_v_folder."'";EXIT;
$query1=mysqli_query($db, "SELECT * FROM `inw_inward_dtl` WHERE cust_id = '".$s_cust_id."' AND j_id = '".$s_j_id."' AND volume_folder = '".$s_v_folder."' ");
$path = "";
$value = "";
 if (mysqli_num_rows($query1)>0)
 {
	 while($post=mysqli_fetch_array($query1))
	 {
	 $work_dest=$post['work_dir'];
	 $work_vol=$post['volume_folder'];
	 global $path ;
	 $path = "$work_dest/$work_vol";
        volume_archive($db,$conn,$post['cust_id'],$post['j_id'],$post['volume_folder'],$vi_id);
	 }
 }
 else
 {	$_COOKIE = "empty";
	header("location:volume_archive.php?msg=No Data Is Available For This Module");
	exit;
 } 

 
  $query2=mysqli_query($conn, "SELECT * FROM `inw_inward_dtl` WHERE cust_id = '".$s_cust_id."' AND j_id = '".$s_j_id."' AND volume_folder = '".$s_v_folder."' ");

 if (mysqli_num_rows($query2)>0)
 {
    if ($files = glob($path . "/*"))
    { 
	    header("location:volume_archive.php?msg=Successfull Folder Movements|| DB Movements Modified");
	}
	else
	{
		deleteAll($path);
		header("location:volume_archive.php?msg=Successfully Archived"); 
	}
 }
 
 
 
?>