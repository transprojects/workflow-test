<?php 
include "includes/includes.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ================== CONFIG ==================
$target_dir = "E:/New_Workflow_Files/AUTOMATION-SYSTEM/CSIRO/"; // ✅ removed backtick

if (!is_dir($target_dir)) {
    if (!mkdir($target_dir, 0755, true)) {
        die("Failed to create folders at $target_dir");
    }
}

// DB connections
define("DB_HOST_LOCAL", "localhost");   // workflow DB
define("DB_USER_LOCAL", "root");
define("DB_PASS_LOCAL", "root@123");
define("DB_NAME_LOCAL", "workflow");

define("DB_HOST_REMOTE", "68.178.166.2");  // xperttrack DB
define("DB_USER_REMOTE","xperttrackadmin_csiro");
define("DB_PASS_REMOTE","&5Cq5h}f7SIb[2b)");
define("DB_NAME_REMOTE","xperttrackadmin_csiro");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db_local  = new mysqli(DB_HOST_LOCAL, DB_USER_LOCAL, DB_PASS_LOCAL, DB_NAME_LOCAL);
    $db_remote = new mysqli(DB_HOST_REMOTE, DB_USER_REMOTE, DB_PASS_REMOTE, DB_NAME_REMOTE);
    $db_local->set_charset("utf8mb4");
    $db_remote->set_charset("utf8mb4");
} catch (Throwable $e) {
    die("DB connection error: " . $e->getMessage());
}

// Logging
$LOG_DIR  = __DIR__ . "/logs";
$LOG_FILE = $LOG_DIR . "/" . date("Y-m-d") . "_upload.log";
if (!is_dir($LOG_DIR)) { @mkdir($LOG_DIR, 0777, true); }

function writeLog($msg) {
    global $LOG_FILE;
    $line = "[" . date("Y-m-d H:i:s") . "] " . $msg . PHP_EOL;
    @file_put_contents($LOG_FILE, $line, FILE_APPEND);
    echo $line;
    flush();
}

// Insert transaction log in local DB
function logTransaction($db, $article_id, $action, $status, $assigned_to = "System", $notes = null) {
    $department = "Inward";
    $stmt = $db->prepare("
        INSERT INTO article_transactions (article_id, action, department, status, assigned_to, notes)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssss", $article_id, $action, $department, $status, $assigned_to, $notes);
    $stmt->execute();
    $stmt->close();
}

// ======================= FTP CONNECTION =======================
$settings = mysqli_fetch_array(mysqli_query($db_remote,"SELECT * FROM eos_settings WHERE set_id=1"));
$query_es = "SELECT * FROM `email_settings` WHERE id= '2'";
$steps_es = $db_local->query($query_es) or ($db_local->error);
$email_st = $steps_es->fetch_assoc();


// ================== EMAIL SETTINGS ==================
$query_cp = "SELECT * FROM client_portal_mail_settings WHERE id=11";
$steps_cp = $db_local->query($query_cp);
$cpemail  = $steps_cp->fetch_assoc();
// Send email
function sendMail($email_st, $cpemail, $jninfo, $fileName, $article_type, $isSuccess, $extraNote = "") {
    include_once("smtpmail/phpmailer/class.phpmailer.php");
    $mail = new PHPMailer();
    $mail->IsSMTP();
    $mail->CharSet = "UTF-8";
    $mail->Host = "outlook.office365.com";
    $mail->SMTPAuth = true;
    $mail->Port = 587;
    $mail->Username = $email_st['from_email'];
    $mail->Password = $email_st['from_password'];
    $mail->SMTPSecure = 'tls';
    $mail->From = $email_st['from_email'];
    $mail->FromName = "CSIRO Workflow"; // ✅ better name
    $mail->isHTML(true);

    $statusText = $isSuccess ? "Uploaded Successfully" : "Upload Failed";
    $mail->Subject = "CSIRO File : $fileName - First Proof - " . $statusText;

    if ($isSuccess) {
        $body = "
            Dear Transforma,<br/><br/>
            First proof article uploaded successfully.<br/><br/>
            <table style='border-collapse: collapse;'>
            <tr bgcolor='#05c2ff'>
                <th style='color:white;border:1px solid #F4B083;'>Information</th>
                <th style='color:white;border:1px solid #F4B083;'>Details</th>
            </tr>
            <tr bgcolor='#e1e1e1'>
                <td style='font-weight:bold;border:1px solid #F4B083;'>Journal Name</td>
                <td style='border:1px solid #F4B083;'>$jninfo</td>
            </tr>
            <tr bgcolor='#e1e1e1'>
                <td style='font-weight:bold;border:1px solid #F4B083;'>File Name</td>
                <td style='color:#0080ff;border:1px solid #F4B083;'>$fileName</td>
            </tr>
            </table>
            <br/>Regards,<br/>CSIRO Team.<br/><br/>
            Note: Auto-generated mail.
        ";
    } else {
        $body = "
            Dear Transforma,<br/><br/>
            File <b>$fileName</b> failed to upload.<br/><br/>
            Regards,<br/>CSIRO Team.<br/><br/>
            Note: Auto-generated mail.
        ";
    }

    $mail->Body = $body;
    foreach (explode(",", $cpemail['mail_to'] ?? '') as $e) { if (trim($e)) $mail->addAddress(trim($e)); }
    foreach (explode(",", $cpemail['mail_cc'] ?? '') as $e) { if (trim($e)) $mail->addCC(trim($e)); }
    foreach (explode(",", $cpemail['mail_bcc'] ?? '') as $e) { if (trim($e)) $mail->addBCC(trim($e)); }

    $ok = $mail->send();
    writeLog("MAIL ".($ok ? "SENT" : "FAILED")." | File: $fileName");
}

// ================== FILE PROCESS ==================
function processUploadedFile($file_name, $target_dir) {
    global $db_local, $db_remote, $cpemail, $email_st;

    writeLog("Processing uploaded file $file_name");

    // --- split file name ---
    $parts = explode("_", $file_name, 3);
    if (count($parts) < 3) {
        writeLog("Invalid filename: $file_name");
        return;
    }
    list($custName, $jCode, $pub_id) = $parts;

    // --- customer lookup ---
    $stmt = $db_remote->prepare("SELECT id FROM adm_customer_master WHERE cust_name=? LIMIT 1");
    $stmt->bind_param("s", $custName);
    $stmt->execute();
    $custRow = $stmt->get_result()->fetch_assoc();
    $cust_id = $custRow['id'] ?? 0;
    $stmt->close();
    writeLog("Customer [$custName] → ID=$cust_id");

    // --- journal lookup ---
    $stmt = $db_remote->prepare("SELECT j_id, j_name FROM adm_journals WHERE j_code=? LIMIT 1");
    $stmt->bind_param("s", $jCode);
    $stmt->execute();
    $jourRow = $stmt->get_result()->fetch_assoc();
    $j_id = $jourRow['j_id'] ?? 0;
    $jn   = $jourRow['j_name'] ?? '';
    $stmt->close();
    writeLog("Journal [$jCode] → j_id=$j_id, j_name=$jn");

    // --- setup vars ---
    $article_id   = pathinfo($file_name, PATHINFO_FILENAME); // remove .zip
    $pub_id       = $article_id;
    $zipSize      = filesize($target_dir . $file_name);
    $due_dt       = date("Y-m-d");
    $wf_id        = 145;
    $article_type = "Word";
    $workflow_id  = 0;

    // --- check if exists in local ---
    $stmtChk = $db_local->prepare("SELECT id FROM inw_inward_dtl WHERE pub_id=? LIMIT 1");
    $stmtChk->bind_param("s", $article_id);
    $stmtChk->execute();
    $res = $stmtChk->get_result();
    if ($res->num_rows > 0) {
        $workflow_id = $res->fetch_assoc()['id'];
        writeLog("Local entry already exists for pub_id=$article_id (workflow_id=$workflow_id)");
    }
    $stmtChk->close();

    // --- insert local if not exists ---
    if ($workflow_id == 0) {
        $stmt = $db_local->prepare("
            INSERT INTO inw_inward_dtl 
            (cust_id, j_id, job_id, journal_title, article_title, recv_dt, accpt_dt, due_dt,
             pub_id, created_dt, stage, wf_id, department_id, status, inw_type, approved_by, zip_size, zip_status,xperttrack_sync)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW(), ?, ?, NOW(), 'FP', ?, '22', 'Pre-Clean', ?, '1', ?, '1', '1')
        ");
        $stmt->bind_param(
            "iisssssiss",
            $cust_id, $j_id, $article_id, $jn, $article_id,
            $due_dt, $article_id, $wf_id, $article_type, $zipSize
        );
        $stmt->execute();
        $workflow_id = $stmt->insert_id;
        $stmt->close();

        writeLog("Local insert OK, workflow_id=$workflow_id");
        logTransaction($db_local, $workflow_id, "Dropzone Upload", "Inserted");
    }

    // --- insert remote ---
    $stmt = $db_remote->prepare("
        INSERT INTO inw_inward_dtl 
        (pub_id,cust_id, j_id, job_id, journal_title, article_title, recv_dt, accpt_dt, due_dt,
          created_dt, stage, wf_id, department_id, status, inw_type, approved_by, zip_size, workflow_id)
        VALUES (? , ?, ?, ?, ?, ?, NOW(), NOW(), ?, NOW(), 'FP', ?, '22', 'Pre-Clean', ?, '1', ?, ?)
    ");
    $stmt->bind_param(
        "siisssssiii",$article_id,$cust_id, $j_id, $article_id, $jn, $article_id,$due_dt, $wf_id, $article_type, $zipSize, $workflow_id);
    $stmt->execute();
    $stmt->close();
    writeLog("Remote insert OK (linked workflow_id=$workflow_id)");

    // --- send mail ---
	sendMail($email_st, $cpemail, $jn, $article_id, $article_type, true);
    writeLog("Mail triggered for $file_name");
}





// ================== MAIN UPLOAD ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $target_file = $target_dir . "/" . basename($_FILES['file']['name']);
    if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
        writeLog("File uploaded: $target_file");
        processUploadedFile($_FILES['file']['name'], $target_dir);
        echo "Successfully uploaded";
    } else {
        writeLog("Upload failed: " . $_FILES['file']['name']);
	    sendMail($email_st, $cpemail, $jn,$_FILES['file']['name'], $article_type, false);
        echo "Error while uploading";
    }
}
?>
