<?php
include("includes/check.php");
//read data
$jid = $_REQUEST['j_id'];

$showjournalfolder= $auth->getJournalfolder($jid);


    $jsource_jf = array();
    if($showjournalfolder!='')
{
    foreach($showjournalfolder as $row)
    {
        $jsource_jf[] = $row;
    }
    echo json_encode($jsource_jf);
}
?>

