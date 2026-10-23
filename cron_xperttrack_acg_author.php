<?php
require('includes/conn.php');
require('linux_db_acg.php');
ini_set('max_execution_time', '6000');

$logDir = "logs";

// Create folder if it doesn't exist
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

$logFile = $logDir . "/article_tracker_log_" . date("Y-m-d") . ".txt";

function writeLog($logFile, $message) {
    file_put_contents($logFile, date("Y-m-d H:i:s") . " - " . $message . "\n", FILE_APPEND);
}

$cdate = date('Y-m-d');


$date = new DateTime($cdate);
$date->modify('-120 days');
$newDate = $date->format('Y-m-d');

$sn = 1;

$sql_inward = mysqli_query($xpertrack_db, "
    SELECT id, workflow_id, pub_id 
    FROM inw_inward_dtl 
    WHERE workflow_id IS NOT NULL 
      AND DATE_FORMAT(created_dt, '%Y-%m-%d') >= '$newDate'
");

writeLog($logFile, "---------------- SCRIPT STARTED ----------------");

while ($res = mysqli_fetch_array($sql_inward)) {

    $inw_id = $res['id'];
    $workflow_id = $res['workflow_id'];
    $pub_id = $res['pub_id'];


 // Fetch dates
    $getFP = mysqli_fetch_array(mysqli_query($db, 
        "SELECT sent_date FROM inw_dispatch_history 
         WHERE a_id='$workflow_id' AND  stage LIKE '%FP%'
         ORDER BY idh_id DESC LIMIT 1"));
		 
		 
    // Fetch dates
    $getRev = mysqli_fetch_array(mysqli_query($db, 
        "SELECT sent_date FROM inw_dispatch_history 
         WHERE a_id='$workflow_id' AND  stage LIKE '%REV%'
         ORDER BY idh_id DESC LIMIT 1"));

    /* $getRev2 = mysqli_fetch_array(mysqli_query($db, 
        "SELECT sent_date FROM inw_dispatch_history 
         WHERE a_id='$workflow_id' AND stage='REV2' 
         ORDER BY idh_id DESC LIMIT 1"));

    $getRev3 = mysqli_fetch_array(mysqli_query($db, 
        "SELECT sent_date FROM inw_dispatch_history 
         WHERE a_id='$workflow_id' 
         AND stage NOT IN ('REV1','REV2','FP') 
         AND stage NOT LIKE '%FIN%'
         ORDER BY idh_id DESC LIMIT 1")); */

    $getFinal = mysqli_fetch_array(mysqli_query($db, 
        "SELECT sent_date FROM inw_dispatch_history 
         WHERE a_id='$workflow_id' 
         AND stage LIKE '%FIN%'
         ORDER BY idh_id DESC LIMIT 1"));

    // Sanitized values
    $fp  = $getFP['sent_date'] != "" ? "'".$getFP['sent_date']."'" : "NULL";
    $rev  = $getRev['sent_date'] != "" ? "'".$getRev['sent_date']."'" : "NULL";
    //$rev2   = $getRev2['sent_date'] != "" ? "'".$getRev2['sent_date']."'" : "NULL";
   // $rev3   = $getRev3['sent_date'] != "" ? "'".$getRev3['sent_date']."'" : "NULL";
    $finals = $getFinal['sent_date'] != "" ? "'".$getFinal['sent_date']."'" : "NULL";

    // CHECK IF EXISTS
    $check = mysqli_query($xpertrack_db, 
        "SELECT at_id FROM article_tracker 
         WHERE inw_id='$inw_id' OR pub_id='$pub_id'
         LIMIT 1");

    if (!$check) {
        writeLog($logFile, "ERROR checking existing record (inw_id: $inw_id): " . mysqli_error($xpertrack_db));
        echo "Error checking record for inw_id $inw_id<br>";
        continue;
    }

    if (mysqli_num_rows($check) > 0) {
        // UPDATE
        $query = "
            UPDATE article_tracker SET 
                first_proof_sent_to_author = $fp,
                final_proof_to_acg_from_transforma = $rev,
                finals_to_acg_from_transforma = $finals
            WHERE inw_id='$inw_id' OR pub_id='$pub_id'
        ";

        $taskMsg = "UPDATED article_tracker for inw_id=$inw_id";

    } else {
        // INSERT
        $query = "
            INSERT INTO article_tracker (
                inw_id,
                pub_id,
                final_proof_to_acg_from_transforma,
                final_proof_to_acg_from_transforma,
                final_proof_to_acg_from_transforma,
                finals_to_acg_from_transforma
            ) VALUES (
                '$inw_id',
                '$pub_id',
                $rev1,
                $rev2,
                $rev3,
                $finals
            )
        ";

        $taskMsg = "INSERTED article_tracker for inw_id=$inw_id";
    }

    // Execute query
    if (!mysqli_query($xpertrack_db, $query)) {
        $err = mysqli_error($xpertrack_db);
        writeLog($logFile, "DB ERROR for inw_id=$inw_id → $err");
        echo "<span style='color:red'>ERROR:</span> $err <br>";
    } else {
        writeLog($logFile, $taskMsg);
        echo $sn . ") " . $taskMsg . "<br>";
    }

    $sn++;
}

writeLog($logFile, "---------------- SCRIPT COMPLETED ----------------");

?>
