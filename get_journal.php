<?php 
include("includes/check.php");

if (isset($_GET['cust_id'])) {
    $cust_id = $_GET['cust_id'];
    $cid = str_replace(",","-", $cust_id);

//echo $cid;exit;
$showjournalscustomerwise = $auth->showjournalscustomerwise($cid);
// print_r($res_COOKIE_article);
    $jsource = array();
    if($showjournalscustomerwise!='')
{
    foreach($showjournalscustomerwise as $row)
    {
        $jsource[] = $row;
    }
    echo json_encode($jsource);
}
}
?>