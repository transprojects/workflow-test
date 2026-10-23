<?php
// ======================= CONFIG =======================

	include ('dbc.php'); 
	
define ("DB_HOST_LOCAL", "localhost");   // workflow DB
define ("DB_USER_LOCAL", "root");
define ("DB_PASS_LOCAL","root@123");
define ("DB_NAME_LOCAL","workflow");

define ("DB_HOST_REMOTE", "68.178.166.2");  // xperttrack DB
define ("DB_USER_REMOTE","xperttrackadmin_acg");
define ("DB_PASS_REMOTE","2g5x(NKEl7tE");
define ("DB_NAME_REMOTE","xperttrackadmin_acg");

$MAX_RETRIES = 3;
$RETRY_WAIT  = 5; // seconds
$LOG_DIR     = __DIR__ . "/logs";
$LOG_FILE    = $LOG_DIR . "/" . date("Y-m-d") . "_ftp.log";
if (!is_dir($LOG_DIR)) { @mkdir($LOG_DIR, 0777, true); }

function writeLog($msg) {
    global $LOG_FILE;
    $timestamp = "[" . date("Y-m-d H:i:s") . "] ";
    $line = $timestamp . $msg . PHP_EOL;

    // Write to log file
    @file_put_contents($LOG_FILE, $line, FILE_APPEND);

    // Print to console
    echo $line;
    flush(); // Ensure output is sent immediately
}


function ensureDir($dir) {
    if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
}
function logTransaction($db, $article_id, $action, $status, $assigned_to = null, $notes = null) {
    $department = "Inward"; // fixed as per your requirement
    $stmt = $db->prepare("
        INSERT INTO article_transactions (article_id, action, department, status, assigned_to, notes)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssss", $article_id, $action, $department, $status, $assigned_to, $notes);
    $stmt->execute();
    $stmt->close();
}

// ======================= EMAIL FUNCTION =======================
function sendMail($email_st, $cpemail, $jninfo, $app_res_staff, $fileName, $article_type, $isSuccess, $extraNote = "") {
    include_once("../../smtpmail/phpmailer/class.phpmailer.php");
    $mail = new PHPMailer();
    $mail->IsSMTP();
    $mail->CharSet = 'UTF-8';
    $mail->Host = "smtp.gmail.com";
    $mail->SMTPAuth = true;
    $mail->Port = 465;
    $mail->Username = $email_st['from_email'];
    $mail->Password = $email_st['from_password'];
    $mail->SMTPSecure = 'ssl';
    $mail->From = $email_st['from_email'];
    $mail->FromName = $email_st['from_email'];
    $mail->isHTML(true);



    $statusText = $isSuccess ? "Downloaded Successfully" : "Download Failed";
    $mail->Subject = "ACG File : $fileName - First Proof - " . $statusText;

    if ($isSuccess) {
        $body .= 'Dear Transforma,<br/><br/> 
			We would like to inform you that the first proof article has been successfully posted for processing, and the relevant details are as follows:<br/><br/>
			<table style="border-collapse: collapse;">
			<tr bgcolor="#05c2ff" style="border: 1px solid #F4B083;">
			<th style="word-wrap: break-word;height:25px;width:100px;text-align:center;color:white;font-family:Arial;font-size: 14px;border: 1px solid #F4B083;">Information</th>
			<th style="word-wrap: break-word;text-align:center;color:white;font-family:Arial;font-size: 14px;border: 1px solid #F4B083;">Details</th>
			</tr>
			<tr bgcolor="#e1e1e1" style="border: 1px solid #F4B083;">
			<td style="font-weight: bold;font-size: 14px;border: 1px solid #F4B083;"> &#160;&#160; Journal Name</td>
			<td style="border: 1px solid #F4B083;">'.$jninfo.'</td>
			</tr>	         	
			<tr bgcolor="#e1e1e1" style="border: 1px solid #F4B083;">
			<td style="font-weight: bold;font-size: 14px;border: 1px solid #F4B083;"> &#160;&#160; File Name</td>
			<td style="color:#0080ff; border: 1px solid #F4B083;">' . htmlspecialchars($fileName) .'</td>
			</tr>
			</table>
			<br/><br/>
			If you have any questions or require further assistance, please feel free to contact us.
			<br/><br/>
			Thank you for your cooperation.
			<br/><br/>
			Best regards,<br/><br/>
				ACG Team.<br/><br/>
			<br/>Note: This is a Auto generated mail, please do not reply to this<br/>';
    } else {
    $body = 'Dear Transforma,<br/><br/>';
        $body .= 'The first proof article file <strong>'.htmlspecialchars($fileName).'</strong> <span style="color:red;font-weight:bold;">failed</span> to download after multiple attempts.<br/><br/>';
    $body .= '<br/>Best regards,<br/>ACG Team.<br/><br/>Note: This is an auto-generated mail.';
    }

    $mail->Body = $body;

    foreach (explode(',', $cpemail['mail_to'] ?? '') as $e) { if (trim($e)) $mail->addAddress(trim($e)); }
    foreach (explode(',', $cpemail['mail_cc'] ?? '') as $e) { if (trim($e)) $mail->AddCC(trim($e)); }
    foreach (explode(',', $cpemail['mail_bcc'] ?? '') as $e) { if (trim($e)) $mail->AddBCC(trim($e)); }

    $ok = $mail->send();
    writeLog("MAIL ".($ok ? "SENT" : "FAILED")." | $statusText | File: $fileName");
}

// ======================= FTP RETRY FUNCTION =======================
function ftpDownloadWithRetry($ftp, $remote, $local, $maxRetries, $waitSec) {
    for ($i=1; $i <= $maxRetries; $i++) {
        if (@ftp_get($ftp, $local, $remote, FTP_BINARY)) {
            writeLog("FTP: Downloaded on attempt $i | $remote");
            return true;
        }
        writeLog("FTP: Attempt $i failed for $remote. Retrying in {$waitSec}s...");
        sleep($waitSec);
    }
    writeLog("FTP: Failed after $maxRetries attempts | $remote");
    return false;
}

// ======================= DB CONNECTIONS =======================
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db_local  = new mysqli(DB_HOST_LOCAL,  DB_USER_LOCAL,  DB_PASS_LOCAL,  DB_NAME_LOCAL);
    $db_remote = new mysqli(DB_HOST_REMOTE, DB_USER_REMOTE, DB_PASS_REMOTE, DB_NAME_REMOTE);
    $db_local->set_charset("utf8mb4");
    $db_remote->set_charset("utf8mb4");
    writeLog("DB: Connected to LOCAL(workflow) and REMOTE(xperttrack) DBs");
} catch (Throwable $e) {
    writeLog("DB: Connection error - ".$e->getMessage());
    die("DB connection error");
}

// ======================= FTP CONNECTION =======================
$settings = mysqli_fetch_array(mysqli_query($db_remote,"SELECT * FROM settings WHERE set_id=1"));

$query_es = "SELECT * FROM `email_settings` WHERE id= '4'";
$steps_es = $db_local->query($query_es) or ($db_local->error);
$email_st = $steps_es->fetch_assoc();
	 
$ftp_server = $settings['set_demo_ftp_server'];
$ftp_port   = $settings['set_demo_ftp_port'];
$ftp_user   = $settings['set_demo_ftp_user_name'];
$ftp_pass   = $settings['set_demo_ftp_user_pass'];
$ftp_path   = $settings['set_demo_ftp_path'];


	//// CLIENT PORTAL MAIL SETTINGS ///	
	 $query_cp = "SELECT * FROM `client_portal_mail_settings` WHERE id= '12'";
	 $steps_cp = $db_local->query($query_cp) or ($db_local->error);
	 $cpemail = $steps_cp->fetch_assoc();
	 
	 
$ftp = @ftp_connect($ftp_server, (int)$ftp_port);
if (!$ftp) { writeLog("FTP: Cannot connect to $ftp_server:$ftp_port"); die(); }
if (!@ftp_login($ftp, $ftp_user, $ftp_pass)) { writeLog("FTP: Login failed for user $ftp_user"); ftp_close($ftp); die(); }
ftp_pasv($ftp, true);
writeLog("FTP: Connected and logged in. Path: $ftp_path");

// ======================= PROCESS FILES =======================
$files = @ftp_nlist($ftp, $ftp_path);
if (!$files) { writeLog("FTP: No files found."); exit; }
$today = date('Y-m-d');
$target_dir_auto = "E:/New_Workflow_Files/AUTOMATION-SYSTEM/ACG/";

foreach ($files as $remote_file_full) {
    $remote_file = (strpos($remote_file_full, $ftp_path) === false) 
        ? rtrim($ftp_path, "/")."/".ltrim($remote_file_full, "/")
        : $remote_file_full;
    $file_name = basename($remote_file);
	$article_id = pathinfo(basename($remote_file), PATHINFO_FILENAME);


    // Only today’s files
    $mt = @ftp_mdtm($ftp, $remote_file);
    if ($mt === -1 || date('Y-m-d', $mt) !== $today) continue;

// ✅ Get ZIP size from FTP
    $zipSize = @ftp_size($ftp, $remote_file);
    if ($zipSize === -1) $zipSize = 0; // fallback if size not available

    writeLog("---- Processing $file_name ----");
    writeLog("📏 File size: $zipSize bytes");


    // Parse file name
   // Hardcode customer name
$custName = "ACG";

// Split filename into parts
$parts = explode('-', $file_name);
if (count($parts) < 2) { 
    writeLog("Invalid filename format: $file_name"); 
    continue; 
}

// First part is job code
$jCode = array_shift($parts);

// Remaining parts combined as pub_id
$pub_id = implode('-', $parts);

// Get customer
writeLog("Looking up customer '$custName'...");
$stmtCust = $db_remote->prepare("SELECT id FROM adm_customer_master WHERE cust_name = ? LIMIT 1");
$stmtCust->bind_param("s", $custName);
$stmtCust->execute();
$resCust = $stmtCust->get_result();
if (!$resCust->num_rows) { 
    writeLog("❌ No customer found for '$custName'");
    continue; 
}
$custRow = $resCust->fetch_assoc();
$cust_id = $custRow['id'];
$stmtCust->close();
writeLog("✅ Found customer_id=$cust_id for '$custName'");

// Log job code and pub ID
writeLog("Job Code: $jCode, Pub ID: $pub_id");

$stmtJour = $db_remote->prepare("SELECT j_id, j_name FROM adm_journals WHERE j_code = ? LIMIT 1");
$stmtJour->bind_param("s", $jCode);
$stmtJour->execute();
$resJour = $stmtJour->get_result();
if (!$resJour->num_rows) { 
    writeLog("❌ No journal found for '$jCode'");
    continue; 
}
$jourRow = $resJour->fetch_assoc();
$j_id = $jourRow['j_id'];
$jn   = $jourRow['j_name'];
$stmtJour->close();
writeLog("✅ Found j_id=$j_id, journal_title='$jn'");

// Check if file exists in LOCAL
writeLog("Checking if '$file_name' already exists in LOCAL DB...");
$stmtChk = $db_local->prepare("SELECT id, zip_status FROM inw_inward_dtl WHERE pub_id = ? LIMIT 1");
$stmtChk->bind_param("s", $file_name);
$stmtChk->execute();
$res = $stmtChk->get_result();
$exists = $res->num_rows > 0;
$row = $exists ? $res->fetch_assoc() : null;
$stmtChk->close();
$article_type='Research';
//$zipSize = round($zipSize / 1024, 2); // KB with 2 decimal places
$due_dt=date('Y-m-d');
$wf_id=145;
$local_path = $target_dir_auto . $file_name;

if (!$exists) {
    writeLog("📥 Inserting new record into LOCAL DB...");

     $insertSQL = "
        INSERT INTO `inw_inward_dtl` 
        (`cust_id`, `j_id`, `job_id`, `journal_title`, `article_title`, `recv_dt`, `accpt_dt`, `due_dt`,
         `pub_id`, `created_dt`, `stage`, `wf_id`, `department_id`, `status`, `work_dir`, `volume_folder`,
         `inw_type`, `approved_by`, `zip_size`, `zip_status`)
        VALUES (
            '".$cust_id."',
            '".$j_id."',
            '".$article_id."',
            '".$jn."',
            '".$article_id."',
            '".date('Y-m-d')."',
            '".date('Y-m-d')."',
            '".$due_dt."',
            '".$article_id."',
            NOW(),
            'FP',
            '".$wf_id."',
            '22',
            'Pre-Clean',
            '".$work_dir."',
            '".VOLUME_FOLDER_NAME."',
            '".$article_type."',
            '1',
            '".$zipSize."',
            '1'
        )
    ";

    writeLog("🛠 DEBUG SQL (LOCAL): $insertSQL");
    $db_local->query($insertSQL);
    $workflow_id = $db_local->insert_id;
    logTransaction($db_local, $workflow_id, "Inserted into Journal Workflow", "Success", "System", "File: $file_name, Journal: $jn");
	
    writeLog("✅ LOCAL: Inserted id=$workflow_id");
} else {
    $workflow_id = (int)$row['id'];
    writeLog("ℹ Found existing LOCAL id=$workflow_id");
	    logTransaction($db_local, $workflow_id, "Checked Journal Workflow for file", "Exists", "System", "File already present: $file_name");

    if ((int)$row['zip_status'] === 1) { 
        writeLog("⏭ Already downloaded — skipping");
        continue; 
    }
}

// FTP download
writeLog("Attempting FTP download: $remote_file → $local_path");
ensureDir(dirname($local_path));
$downloadOK = ftpDownloadWithRetry($ftp, $remote_file, $local_path, $MAX_RETRIES, $RETRY_WAIT);

if ($downloadOK) {
    writeLog("✅ Download complete: $file_name");
    logTransaction($db_local, $workflow_id, "FTP Download", "Success", "System", "Downloaded file: $file_name");

	 // Move file to backup folder in FTP
    $backup_dir = rtrim($ftp_path, "/") . "/BACKUP/";
    if (!@ftp_chdir($ftp, $backup_dir)) {
        // If backup directory doesn't exist, create it
        if (@ftp_mkdir($ftp, $backup_dir)) {
            writeLog("📂 Created backup folder: $backup_dir");
        } else {
            writeLog("⚠ Failed to create backup folder: $backup_dir");
        }
    }

    $backup_path = $backup_dir . "/" . $file_name;
    if (@ftp_rename($ftp, $remote_file, $backup_path)) {
        writeLog("📦 Moved file to FTP backup folder: $backup_path");
    } else {
        writeLog("⚠ Failed to move file to FTP backup folder: $backup_path");
    }

    // Insert into REMOTE
    writeLog("📤 Inserting into REMOTE DB...");
	 logTransaction($db_local, $workflow_id, "Inserted into Xperttrack System", "Success", "System", "File: $file_name, Journal: $jn");


     $insertRemoteSQL = "
        INSERT INTO `inw_inward_dtl`
        (`cust_id`, `j_id`, `job_id`, `journal_title`, `article_title`, `recv_dt`, `accpt_dt`, `due_dt`,
         `pub_id`, `created_dt`, `stage`, `wf_id`, `department_id`, `status`, `work_dir`, `volume_folder`,
         `inw_type`, `approved_by`, `zip_size`, `workflow_id`)
        VALUES (
            '".$cust_id."',
            '".$j_id."',
            '".$article_id."',
            '".$jn."',
            '".$article_id."',
            '".date('Y-m-d')."',
            '".date('Y-m-d')."',
            '".$due_dt."',
            '".$article_id."',
            NOW(),
            'FP',
            '".$wf_id."',
            '22',
            'Pre-Clean',
            '".$work_dir."',
            '".VOLUME_FOLDER_NAME."',
            '".$article_type."',
            '1',
            '".$zipSize."',
            '".$workflow_id."'
        )
    ";

    writeLog("🛠 DEBUG SQL (REMOTE): $insertRemoteSQL");
    $db_remote->query($insertRemoteSQL);

    writeLog("✅ REMOTE: Insert complete for workflow_id=$workflow_id");

    // Send success email
    writeLog("📧 Sending SUCCESS email...");
    sendMail($email_st, $cpemail, $jn, $app_res_staff, $file_name, $article_type, true);
	logTransaction($db_local, $workflow_id, "Email Notification", "Sent", "System", "Success email sent for $file_name");

} else {
    writeLog("❌ FTP download failed for $file_name");
	    logTransaction($db_local, $workflow_id, "FTP Download", "Failed", "System", "Failed to download file after retries: $file_name");

    writeLog("📧 Sending FAILURE email...");
    sendMail($email_st, $cpemail, $jn, $app_res_staff, $file_name, $article_type, false, "FTP download failed.");
	logTransaction($db_local, $workflow_id, "Email Notification", "Sent", "System", "Failure email sent for $file_name");

}

}


ftp_close($ftp);
$db_local->close();
$db_remote->close();
writeLog("=== Run complete ===");
?>
