<?php
include("includes/check.php");
error_reporting(1);
//read data
$stage='';
$jid = $_REQUEST['j_id'];
$stage = isset($_REQUEST['stage']) ? htmlspecialchars(strip_tags((string)$_REQUEST['stage'])) : '';   /* INPUT_REQUEST is not a PHP constant: PHP 8 stopped here with a fatal error */

$showjournal= $auth->showjournals($jid);

$Date =date('d-m-Y');

if($stage=='FP')
{
$due_date=date('d-m-Y', strtotime($Date. ' + '.$showjournal[0]['j_due_fduration'].' days'));
}
elseif($stage=='REV')
{
$due_date=date('d-m-Y', strtotime($Date. ' + '.$showjournal[0]['j_due_rduration'].' days'));
}
elseif($stage=='FIN')
{
$due_date=date('d-m-Y', strtotime($Date. ' + '.$showjournal[0]['j_due_fiduration'].' days'));
}
elseif($stage=='ISSCOR')
{
$due_date=date('d-m-Y', strtotime($Date. ' + '.$showjournal[0]['j_due_isduration'].' days'));
}
elseif($stage=='ISSFIN')
{
$due_date=date('d-m-Y', strtotime($Date. ' + '.$showjournal[0]['j_due_isfduration'].' days'));
}
elseif(strpos($stage,'PMC')===0)
{
$due_date=date('d-m-Y', strtotime($Date. ' + '.(int)($showjournal[0]['j_due_pmcduration'] ?? 0).' days'));
}
elseif(strpos($stage,'JSTAGE')===0)
{
$due_date=date('d-m-Y', strtotime($Date. ' + '.(int)$showjournal[0]['j_due_jsduration'].' days'));
}
elseif($stage=='Sample')
{
$due_date=date('d-m-Y', strtotime($Date. ' + '.$showjournal[0]['j_due_isduration'].' days'));
}
else
{
$due_date=date('d-m-Y', strtotime($Date. ' + 0 days'));
}
//echo $due_date;
?>

<input type="text" class="form-control monthlyinputdate" name="due_dt" value="<?php echo $due_date; ?>" id="due_dt"/>
