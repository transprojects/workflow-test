<?php 
define ("DB_HOST_CE", "10.10.10.109"); // set database host
define ("DB_USER_CE", "root"); // set database user
define ("DB_PASS_CE","root@123"); // set database password
define ("DB_NAME_CE","trans_demo"); // set database name

//10.10.10.109 File Folder Structure //
define ("WF_CE_DIR","//10.10.10.109/109-Files/"); // set 10.10.10.109 DIR Path

//Connect with the database
$db_ce = new mysqli(DB_HOST_CE, DB_USER_CE, DB_PASS_CE, DB_NAME_CE);

if ($db_ce->connect_errno) {
    printf("Connect failed: %s\n", $db_ce->connect_error);
    exit();
}
  $src = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/".$post['pub_id'].".html";   // source folder or file
 $src_metaxml = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/".$post['pub_id']."-metadata.xml";   // source folder or file
 $src_d = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/".$post['pub_id'].".doc";   // source folder or file

  $dest =WF_CE_DIR.$post['pub_id']."/";   // destination folder or file
 $dest_file=WF_CE_DIR.$post['pub_id']."/".$post['pub_id'].".html";      // destination folder or file
 $dest_xml=WF_CE_DIR.$post['pub_id']."/".$post['pub_id']."-metadata.xml";      // destination folder or file
//exit;
if (!is_dir($dest))
{
mkdir($dest,0777);
}

if(file_exists($src)  && file_exists($src_metaxml))
{

copy($src,$dest_file);
copy($src_metaxml,$dest_xml);

$update_ce_editor=mysqli_query($db_ce,"update tbl_td_file_movement set td_fm_status=0 where workflow_file_id='".$post['id']."'");

}
elseif(file_exists($src_d)  && file_exists($src_metaxml))
{
copy($src_d,$dest_file);
copy($src_metaxml,$dest_xml);
$update_ce_editor=mysqli_query($db_ce,"update tbl_td_file_movement set td_fm_status=0 where workflow_file_id='".$post['id']."'");

}
else
{
	header("location:".$_COOKIE['set_url']."/startwork/".$post['id']."/".$post['tr_id']."&msg=Document or Meta file missing in the  ".$post['dept_name']." folder");exit;
}

?>

