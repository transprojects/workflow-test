<?php require("db.php");
// $status_check=mysqli_fetch_array(mysqli_query($db,"select * from tbl_td_file_status where td_fs_id='".$_REQUEST['fsid']."'"));
// $update=mysqli_query($db,"update tbl_td_file_status set td_fs_status=13 where td_fs_id='".$_REQUEST['fsid']."'");
// $update=mysqli_query($db,"update tbl_td_file_movement set td_fm_status=0 where td_fm_id='".$status_check['td_fs_fm_id']."'");

$conn->query("update tbl_td_file_status set td_fs_status=13 where td_fs_id='".$_REQUEST['fsid']."'");
$conn->query("update tbl_td_file_movement set td_fm_status=0 where td_fm_id=(select td_fs_fm_id from tbl_td_file_status where td_fs_id='".$_REQUEST['fsid']."' limit 1)");
echo "0";
?>