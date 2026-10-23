<?php
include "includes/includes.php";
include_once "includes/change_log_mail.php";

if (!isset($_POST['pub_id'])) {
    exit;
}

/* ===============================
   CLEAR OLD COOKIES
=============================== */
setcookie("j_id", "", time() - 3600, "/");
setcookie("pub_id", "", time() - 3600, "/");
setcookie("group_folder_name", "", time() - 3600, "/");
setcookie("stage", "", time() - 3600, "/");

/* ===============================
   BASIC INPUTS
=============================== */
$j_id              = (int)$_POST['j_id'];
$article_id        = trim($_POST['pub_id']);
$group_folder_name = $_POST['group_folder_name'] ?? '0';
$stage             = $_POST['stage'];

setcookie("j_id", $j_id, $cookie_expiration_time, "/");
setcookie("pub_id", $article_id, $cookie_expiration_time, "/");
setcookie("group_folder_name", $group_folder_name, $cookie_expiration_time, "/");
setcookie("stage", $stage, $cookie_expiration_time, "/");

/* ===============================
   DATE HANDLING (WORKING DAY)
=============================== */
$recv_dt  = date('Y-m-d', strtotime($_POST['recv_dt']));
$pub_dt   = date('Y-m-d', strtotime($_POST['pub_dt']));
$accpt_dt = date('Y-m-d', strtotime($_POST['accpt_dt']));

$currentDate   = new DateTime();
$excludedDates = [date('Y-m-d'), '2023-10-23', '2023-11-13'];

while (
    $currentDate->format('N') >= 6 ||
    in_array($currentDate->format('Y-m-d'), $excludedDates)
) {
    $currentDate->add(new DateInterval('P1D'));
}
$due_date = $currentDate->format('Y-m-d');

/* ===============================
   JOURNAL DETAILS
=============================== */
$journaldetails = $auth->getJournalCustomer($j_id);
$Cn      = $journaldetails[0]['cust_name'];
$cust_id = $journaldetails[0]['j_cust_id'];
$jn      = $journaldetails[0]['j_code'];

/* ===============================
   WORKFLOW & DEPARTMENT
=============================== */
$wf_id = (int)$_POST['wf_id'];

$workflow  = $auth->getArticleWorkflow($wf_id);
$dept_code = (int)explode(',', $workflow[0]['wf_code'])[0];

$dept   = $auth->getArticleDepartment($dept_code);
$status = $dept[0]['dept_name'];

/* ===============================
   DIRECTORY STRUCTURE
=============================== */
$download_folder = 'downloads';
$journal_folder  = 'JOURNALS';
$production      = 'production';

$work_dir = WF_DIR."/$production/$journal_folder/$Cn/$jn/";
$work_dir_esc = mysqli_real_escape_string($db, $work_dir);

$target_dir = WF_DIR."/$download_folder/$journal_folder/$Cn/$jn/"
            .VOLUME_FOLDER_NAME.$group_folder_name."/$article_id/$stage/";

if (!is_dir($target_dir)) {
    mkdir($target_dir, 0755, true);
}

/* ===============================
   DEPARTMENT FOLDERS
=============================== */
$deptRes = $db->query("
    SELECT dept_name 
    FROM adm_dept_master 
    WHERE dept_code = $dept_code
")->fetch_assoc();

$baseDeptPath = $work_dir.VOLUME_FOLDER_NAME.$group_folder_name."/$article_id/$stage/";

if (!is_dir($baseDeptPath.$deptRes['dept_name'])) {
    mkdir($baseDeptPath.$deptRes['dept_name'], 0777, true);
}

/* Graphics folders */
if ((int)$_POST['fig'] > 0) {
    $gfx = $db->query("
        SELECT dept_name 
        FROM adm_dept_master 
        WHERE dept_code = 9
    ")->fetch_assoc();

    foreach (['', 'Print', 'Web'] as $sub) {
        $path = $baseDeptPath.$gfx['dept_name'].($sub ? "/$sub" : "");
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }
}

/* ===============================
   DUPLICATE CHECK
=============================== */
$article_count = $auth->countArticle($article_id);
$db_article_id = 0;

if ($article_count[0]['count'] == 0) {

    /* ===============================
       FLAGS
    =============================== */
    $test_article  = isset($_POST['test_article']) ? 1 : 0;
    $highpriority  = isset($_POST['highpriority']) ? 1 : 0;
    $is_conversion = isset($_POST['is_conversion']) ? 'yes' : 'no';

    /* ===============================
       ESCAPE INPUTS
    =============================== */
    $title = mysqli_real_escape_string($db, $_POST['article_title']);
    $inst  = mysqli_real_escape_string($db, $_POST['instructions']);
    $notes = mysqli_real_escape_string($db, $_POST['notes']);

    $db->begin_transaction();

    try {

        /* ===============================
           MAIN INSERT
        =============================== */
        $db->query("
            INSERT INTO inw_inward_dtl (
                cust_id, j_id, issueorgroup_folder, pub_id, article_title,
                recv_dt, due_dt, test_article, highpriority, is_conversion,
                manuscript_count, fig, tab, stage, wf_id, inw_type, complexity,
                corresponding_author, corresponding_mail, location_id, doi,
                pub_dt, accpt_dt, instructions, notes, status
            ) VALUES (
                '$cust_id', '$j_id', '$group_folder_name', '$article_id', '$title',
                '$recv_dt', '$due_date', '$test_article', '$highpriority', '$is_conversion',
                '{$_POST['manuscript_count']}', '{$_POST['fig']}', '{$_POST['tab']}',
                '$stage', '$wf_id', '{$_POST['inw_type']}', '{$_POST['complexity']}',
                '{$_POST['corresponding_author']}', '{$_POST['corresponding_mail']}',
                '{$_POST['location_id']}', '{$_POST['doi']}',
                '$pub_dt', '$accpt_dt', '$inst', '$notes', '$status'
            )
        ");

        $db_article_id = $db->insert_id;

        /* ===============================
           UPDATE META
        =============================== */
        $db->query("
            UPDATE inw_inward_dtl SET
                volume_folder = 'Vol00000',
                approved_by = 1,
                department_id = 1,
                work_dir = '$work_dir_esc',
                at_id = '".(int)$_POST['at_id']."'
            WHERE id = '$db_article_id'
        ");

        /* ===============================
           INSTRUCTIONS HISTORY
        =============================== */
        $db->query("
            INSERT INTO inw_instructions_dtl
            (inw_id, stage, instructions, created_by, created_dt)
            VALUES
            ('$db_article_id', '$stage', '$inst', '{$_SESSION['user_id']}', NOW())
        ");

        /* ===============================
           REVISIONS
        =============================== */
        if ($stage === 'REV' || $stage === 'FIN') {
            $db->query("
                INSERT INTO inw_revisions_dtl
                (inw_id, revision_count, received_date, due_date,
                 correction_pages, comments, file_name, created_by, created_on, type)
                VALUES
                ('$db_article_id', '1', '$recv_dt', '$due_date',
                 '{$_POST['manuscript_count']}', '$inst',
                 '$target_dir', '{$_SESSION['user_id']}', NOW(), '$stage')
            ");
        }

        $db->commit();

        // Change-log entry + email for the new article
        log_row_added('Article', $db_article_id . ' / ' . $_POST['pub_id'], array_diff_key($_POST, array('action'=>1)));

    } catch (Exception $e) {
        $db->rollback();
        die("Insert failed: ".$e->getMessage());
    }

} else {
    $db_article_id = $article_count[0]['id'];
}

/* ===============================
   LOG ENTRY
=============================== */
$auth->insertLog(
    $db_article_id,
    $article_id,
    '1',
    'LINUX',
    'FP',
    'Basic information updated',
    '1'
);
?>
