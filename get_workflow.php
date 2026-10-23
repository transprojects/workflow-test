<?php
include("includes/check.php");
//read data
$jid = $_REQUEST['j_id'];

$showjournal= $auth->showjournals($jid);

$j_wp_fp=$showjournal[0]['j_fp_wf'];
$j_rev_wf=$showjournal[0]['j_rev_wf'];
$j_fin_wf=$showjournal[0]['j_fin_wf'];
$j_jstage_wf=isset($showjournal[0]['j_jstage_wf']) ? $showjournal[0]['j_jstage_wf'] : '';

$showjournalworkflow= $auth->getJournalWorkflow($j_wp_fp,$j_rev_wf,$j_fin_wf,$j_jstage_wf);


    $jsource_wf = array();
    if($showjournalworkflow!='')
{
    foreach($showjournalworkflow as $row)
    {
        $jsource_wf[] = $row;
    }
    echo json_encode($jsource_wf);
}
?>
