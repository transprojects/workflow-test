<?php
include("dbc.php");

$userId = intval($_COOKIE['user_id']);
$stage  = mysqli_real_escape_string($db, $_POST['stage']);
$projId = intval($_POST['id']);

$error = "";


/* ----------------------------------------------------------
   1️⃣ CHECK HIGH PRIORITY INPROGRESS ARTICLES IN SAME STAGE
   ---------------------------------------------------------- */
$sql = "
    SELECT t.present_user_id
    FROM inw_transactions t
    JOIN inw_inward_dtl d ON d.id = t.project_id
    WHERE t.stage = '$stage'
      AND t.current_status = 'Inprogress'
      AND d.highpriority = 1
      AND t.present_user_id > 0
    LIMIT 1
";

$res = $db->query($sql);

if ($res && $res->num_rows) {
    echo "Kindly Process the High Priority Articles First";
    exit;
}


/* ----------------------------------------------------------
   2️⃣ CHECK IF USER HAS OTHER INPROGRESS ARTICLES
   ---------------------------------------------------------- */
$sql = "
    SELECT id 
    FROM inw_transactions
    WHERE project_id != $projId
      AND dept = '$stage'
      AND process_user = $userId
      AND end_time IS NULL
      AND current_status = 'Inprogress'
      AND created_dt > '2024-01-01'
    LIMIT 1
";

$res = $db->query($sql);

if ($res && $res->num_rows) {
    echo "Please complete all the Inprogress Articles before you start this work";
    exit;
}


/* ----------------------------------------------------------
   3️⃣ CHECK IF THIS ARTICLE IS ALREADY PICKED BY ANOTHER USER
   ---------------------------------------------------------- */
$sql = "
    SELECT present_user_id
    FROM inw_inward_dtl
    WHERE id = $projId
      AND present_user_id != $userId
      AND present_user_id > 0
    LIMIT 1
";

$res = $db->query($sql);

if ($res && $res->num_rows) {

    $row = $res->fetch_assoc();
    $uid = intval($row['present_user_id']);

    // Fetch user name in 1 query
    $usr = $db->query("SELECT full_name FROM users WHERE id=$uid LIMIT 1")->fetch_assoc();

    echo "This Article currently in <strong>Progress</strong>. Work already started by <strong>{$usr['full_name']}</strong>.";
    exit;
}


/* ----------------------------------------------------------
   4️⃣ IF ALL GOOD
   ---------------------------------------------------------- */
echo "0";
exit;

?>
