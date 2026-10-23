<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
ini_set("display_errors", "0");
include "../includes/Auth.php";
include "../includes/function.php";
$auth = new Auth();
$db_handle = new DBController();
$start 	= $_REQUEST['iDisplayStart'];
$length = $_REQUEST['iDisplayLength'];
$sSearch = $_REQUEST['sSearch'];
$col = $_REQUEST['iSortCol_0'];
$gSearch="";

$arr = array(0 => 'j_code', 1 => 'full_name', 2 => 'stage', 3 => 'article', 4 => 'recv_dt', 5 => 'dm.created_on', 6 => 'f_id');

$sort_by = $arr[$col];
$sort_type = $_REQUEST['sSortDir_0'];


// if($_COOKIE['staff_type']!='1')
// 	$gSearch .=' and staff_id='.$_COOKIE['staff_id'];
// else	
// 	$gSearch .=' and (staff_id not in (1,9) )';
if(($_REQUEST['cust_id']!='' || $_REQUEST['j_id']!='' || $_REQUEST['stage']!='' || $_REQUEST['article_id']!='' || $_REQUEST['from_dt']!='' || $_REQUEST['to_dt']!=''))
{
	

	if(isset($_REQUEST['cust_id']) && $_REQUEST['cust_id']!='')

         //$j_id = implode(",",$_REQUEST['j_id']);

		$gSearch .= ' and FIND_IN_SET(dm.cust_id,"'.$_REQUEST['cust_id'].'")';
        

	if(isset($_REQUEST['j_id']) && $_REQUEST['j_id']!='')

         //$j_id = implode(",",$_REQUEST['j_id']);

		$gSearch .= ' and FIND_IN_SET(dm.j_id,"'.$_REQUEST['j_id'].'")';
		
        

	if(isset($_REQUEST['stage']) && $_REQUEST['stage']!='')
	{

		 $ex_stage=explode(",",$_REQUEST['stage']);
		 if(count($ex_stage)==1)
		 {
			$and=' and';
			$or='';
		 }
		 else
		 {
			$and=' and (';
			$orf=' or ';  
		}
		$or='';
		 for($i=0;$i<=count($ex_stage)-1;$i++)
		 {

			if($i==count($ex_stage)-1)
			{
				$or = ')';

			}
			
			if($i==0)
			{
				$gSearch.=''.$and.' stage LIKE "%'.$ex_stage[$i].'%" ';  
			}
			else
			{
				$gSearch.=''.$orf.' stage LIKE "%'.$ex_stage[$i].'%" '.$or.' ';  

			}
			
		}

	}

		
	if(isset($_REQUEST['from_dt']) && $_REQUEST['to_dt']!='')

         //$j_id = implode(",",$_REQUEST['j_id']);

		$gSearch .= ' and (DATE_FORMAT(recv_dt,"%Y-%m-%d")>="'.date('Y-m-d', strtotime($_REQUEST['from_dt'])).'" and DATE_FORMAT(recv_dt,"%Y-%m-%d")<="'.date('Y-m-d',strtotime($_REQUEST['to_dt'])).'")';


	if(isset($_REQUEST['article_id']) && $_REQUEST['article_id']!='')

            $gSearch.='and article LIKE "%'.$_REQUEST['article_id'].'%" ';  


	

}
else
{
	$gSearch .= 'and 1=1';
}


 $res_prof = $auth->SelectClientFeedback($sSearch, $sort_by, $sort_type, $start, $length,$gSearch);
 //print_r($res_prof);
//echo count($res_prof);
//$rec='';
$rec = array(
	'iTotalRecords' => 0,
	'iTotalDisplayRecords' =>0,
	'aaData' => array()
);
if($res_prof!='')
{

	if(count($res_prof)>0 && count($res_prof)!='NULL')
	{
		foreach($res_prof as $row)
		{
			$data[] = $row;
		}
	}
	else {
		$data[] = '';
	}

$qry = "";	
$qry = $auth->CountClientFeedback($sSearch,$gSearch);
foreach($qry as $row)
{	
	$iTotal = $row['count'];
}
//echo $iTotal;

$rec = array(
	'iTotalRecords' => $iTotal,
	'iTotalDisplayRecords' => $iTotal,
	'aaData' => array()
);

$k=0;
if (isset($data) && is_array($data)) {
    $k = 0; // initialize index
    foreach ($data as $clientfeedback) {

        if (!empty($clientfeedback['rca']) || !empty($clientfeedback['ca']) || !empty($clientfeedback['pa'])) {
            $messageContent = '
                <div class="message-content">
                    <strong>RCA:</strong> ' . nl2br($clientfeedback['rca']) . '<br><br>
                    <strong>CA:</strong> ' . nl2br($clientfeedback['ca']) . '<br><br>
                    <strong>PA:</strong> ' . nl2br($clientfeedback['pa']) . '
                </div>';
        } else {
            $messageContent = '
                <div class="message-content">' . nl2br($clientfeedback['ap_message']) . '</div>';
        }

        $rec['aaData'][$k] = array(
            0 => $clientfeedback['j_code'],
            1 => (!empty($clientfeedback['u_id']) && !empty($clientfeedback['full_name']))
                ? $clientfeedback['full_name']
                : 'Not Available',
            2 => $clientfeedback['stage'],
            3 => $clientfeedback['article'],
            4 => '<div class="message-content">' . $clientfeedback['message'] . '</div>',
            5 => $messageContent,
            6 => date('d-m-Y H:i:s', strtotime($clientfeedback['recv_dt'])),
         7 => '<div class="hidden-sm hidden-xs action-buttons">
        <a title="Action Plan" class="btn btn-xs btn-warning" 
           href="' . $_COOKIE['set_url'] . '/edit-client-feedback/' . $clientfeedback['f_id'] . '"><i class="fa fa-pencil-square-o"></i></a> 

        <a title="View" class="btn btn-xs btn-info" 
           href="' . $_COOKIE['set_url'] . '/client-feedback-detail/' . $clientfeedback['f_id'] . '"><i class="fa fa-eye"></i></a> 

        <button data-delete="' . $clientfeedback['f_id'] . '" 
                class="btn btn-danger demo4 delete-article" 
                type="button">
            <i class="ace-icon fa fa-trash-o bigger-120"></i>
        </button>
    </div>'

        );
        $k++;
    }
}

}

echo json_encode($rec);