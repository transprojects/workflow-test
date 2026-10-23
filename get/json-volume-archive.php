<?php 
//error_reporting(0);
$sno=1;
$response = array();
//echo $_COOKIE['department_id'];exit;

if(isset($_REQUEST['proceed']) && $_REQUEST['proceed']!='')
{
	
$var = '';
if($_POST['cust_id']!=''){
	
     $var.= "and c.id = '".$_POST['cust_id']."' ";
}
if($_POST['stage_id']!=''){
	
      $var.= "and stage LIKE '".$_POST['stage_id']."%' ";
}
if($_POST['j_id']!=''){
	
     $var.= "and j.j_id = '".$_POST['j_id']."' ";
}
if($_POST['pub_id']!=''){
	
     $var.= "and pub_id LIKE '%".$_POST['pub_id']."%' ";
}
if($_POST['from_dt']!='' && $_POST['to_dt']){
	
   $var.="and recv_dt BETWEEN '".date('Y-m-d',strtotime($_POST['from_dt']))."' and '".date('Y-m-d',strtotime($_POST['to_dt']))."'";
}

}

 $v_folder1 = explode(" ",$_POST['v_folder']);
  $v_id = $v_folder1[0];
$v_folder = $v_folder1[1];
$v_cust_id=$_POST['v_cust_id'];
$v_j_id=$_POST['v_j_id'];

		if($_COOKIE['user_level'] != 1) 
		{		  
			if($_COOKIE['department_id'] == 16)
			{
				$query = "SELECT c.cust_name,journal_title,pub_id,issueorgroup_folder,recv_dt,due_dt,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,j.j_id,manuscript_count,stage,folder,volume_folder,notes FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.approved_by > 0 and wd.cust_id = c.id and wd.j_id = j.j_id and wd.status IN ('Delivery','Client Review') and wd.volume_folder ='".$v_folder."' and wd.cust_id='".$v_cust_id."' and wd.j_id= '".$v_j_id."'";
			}
			else
			{
				$query = "SELECT c.cust_name,journal_title,pub_id,issueorgroup_folder,recv_dt,due_dt,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,j.j_id,manuscript_count,stage,folder,volume_folder,notes FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id and wd.status IN ('Delivery','Client Review') AND department_id = '".$_COOKIE['department_id']."' and wd.volume_folder ='".$v_folder."' and wd.cust_id='".$v_cust_id."' and wd.j_id= '".$v_j_id."'";
			}
		}
		else
		{
			$query = "SELECT c.cust_name,journal_title,pub_id,issueorgroup_folder,recv_dt,due_dt,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,j.j_id,manuscript_count,stage,folder,volume_folder,notes FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id and wd.status IN ('Delivery','Client Review') and wd.volume_folder ='".$v_folder."' and wd.cust_id='".$v_cust_id."' and wd.j_id= '".$v_j_id."'";
		}
		
$order_by = "ORDER BY `id` DESC";	
$query = $query.$var.$order_by;
$customers = $db->query($query) or ($db->error);

$num_query = $customers->num_rows;
if($num_query!=0)
{
while($row = $customers->fetch_assoc()){
 $action='<span class="label label-sm label-success">'.$row['status'].'</span> ';


 
if($_COOKIE['department_id'] == 16 || $_COOKIE['user_level'] == 1)
{
	$pub_id=$row['pub_id'];
	$j_id=$row['j_id'];
   	$folder=$row['volume_folder'];

	   
	  
		   $client_review='<a class="btn btn-xs btn-primary btn-sm" id="folder2" href="volume_archive_imp.php?id='.$pub_id.'&j_id='.$j_id.'&vol='.$folder.'"> <i class="ace-icon fa fa-folder bigger-120"></i> Archive</a>';
	   
        
    
                 
} 

$posts[] = array($row['cust_name'], $row['j_code'], $row['pub_id'].(!empty($row['notes']) ? ' <span class="label label-sm label-info">'.htmlspecialchars(substr($row['notes'],0,35)).'...</span>' : ''), ltrim($row['issueorgroup_folder'],"/"), (date('d-m-Y',strtotime($row['recv_dt']))),(date('d-m-Y',strtotime($row['due_dt']))),$row['manuscript_count'],$row['fig'],$row['tab'],$row['stage'],$action,$folder,'<div class="hidden-sm hidden-xs action-buttons">'.$client_review.'</div>',$row['id']);
		$response['data'] = $posts;
		$sno++;


	} 
}	
else
{
	$response['data'] = '';
}
 
//print_r($posts);
$fp= fopen('json-download/volume-articles.json', 'w');
fwrite($fp, json_encode($response));
fclose($fp);
?>