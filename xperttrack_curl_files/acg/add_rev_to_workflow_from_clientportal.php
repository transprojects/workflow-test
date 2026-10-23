<?php
// --- 0. Basic error reporting (log-only) ---
error_reporting(E_ALL);
ini_set('display_errors', 1);             // don't show errors to users
$php_error_log_path = 'workflow_script_errors.log'; // <-- adjust path writable by PHP
ini_set('log_errors', 1);
ini_set('error_log', $php_error_log_path);


include('conn.php');
include('dbc.php');
include('../../vendor/autoload.php');
include('../../email-functions.php');

// --- INPUT VARIABLES ---
$inw_id       = $_REQUEST['inw_id'] ?? '';
$workflow_id  = $_REQUEST['workflow_id'] ?? '';
$stage        = $_REQUEST['stage'] ?? '';
$stage_prev   = $_REQUEST['stage_prev'] ?? '';
$author       = $_REQUEST['author'] ?? '';
$volume_no    = "Vol00000";
$wf_id        = 67;

// --- LOG HELPER ---
function log_event($file_name, $stage, $cl_id, $remarks, $status = 1, $db_conn = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $cdatetime = date('Y-m-d H:i:s');
    $safe_file = $db_conn ? $db_conn->real_escape_string($file_name) : addslashes($file_name);
    $safe_remarks = $db_conn ? $db_conn->real_escape_string($remarks) : addslashes($remarks);
    $query = "INSERT INTO logs(file_name, stage, cl_id, remarks, servers, ip, status, cdatetime)
              VALUES ('$safe_file','$stage','$cl_id','$safe_remarks','WORKFLOW','$ip','$status','$cdatetime')";
    if ($db_conn) @$db_conn->query($query);
    error_log("LOG_EVENT | file:$file_name | stage:$stage | cl_id:$cl_id | remarks:$remarks | status:$status");
}

// --- MOVE FUNCTION ---
function rmove($src, $dest) {
    if (!is_dir($src)) return false;
    if (!is_dir($dest) && !mkdir($dest, 0777, true)) return false;

    foreach (new DirectoryIterator($src) as $f) {
        if ($f->isFile()) {
            if (!rename($f->getRealPath(), "$dest/" . $f->getFilename())) {
                error_log("Failed to move file: " . $f->getRealPath());
            }
        } elseif (!$f->isDot() && $f->isDir()) {
            if (!in_array($f->getFilename(), ['Delivery','CE','PRQC'])) {
                rmove($f->getRealPath(), "$dest/" . $f->getFilename());
            }
        }
    }
    return true;
}

try {
    // === FETCH JOURNAL & CUSTOMER DETAILS ===
    $app_res = $db->query("SELECT * FROM inw_inward_dtl WHERE id='$workflow_id'")->fetch_assoc();
    if (!$app_res) throw new Exception("No inward record found: $workflow_id");

    $jninfo = $db->query("SELECT * FROM adm_journals WHERE j_id='{$app_res['j_id']}'")->fetch_assoc();
    $crow   = $db->query("SELECT cust_name, cust_id, id FROM adm_customer_master WHERE id='{$jninfo['j_cust_id']}'")->fetch_assoc();

    $Cn          = $crow['cust_name'];
    $jn          = $jninfo['j_code'];
    $artile_name = $app_res['pub_id'];

    // === DIRECTORY STRUCTURE ===
    $work_dir        = WF_DIR . "/production/JOURNALS/$Cn/$jn/";
    $destination_dir = WF_DIR . "/downloads/JOURNALS/$Cn/$jn/" . VOLUME_FOLDER_NAME . "/$artile_name/$stage/";
    if (!is_dir($destination_dir) && !mkdir($destination_dir, 0777, true)) {
        throw new Exception("Failed to create directory: $destination_dir");
    }

    // === FTP CONNECTION ===
    $conn_id = @ftp_connect($ftp_server);
    if (!$conn_id) throw new Exception("Could not connect to FTP server $ftp_server");
    log_event($artile_name,'FP','19',"Connected to FTP server",1,$xperttrack_db);

    if (!@ftp_login($conn_id,$ftp_user_name,$ftp_user_pass)) {
        throw new Exception("FTP login failed for $ftp_user_name");
    }
    log_event($artile_name,'FP','20',"FTP login success",1,$xperttrack_db);
    ftp_set_option($conn_id, FTP_USEPASVADDRESS, false);
    ftp_pasv($conn_id, false);

    // === FILE DOWNLOAD ===
    $remote_file = $author == '1'
        ? "$ftp_path/REV/$Cn/$jn/$artile_name/$stage/$artile_name.zip"
        : "$ftp_path/REV/$Cn/$jn/$artile_name/$stage/editor/$artile_name.zip";

    $local_file = $destination_dir . "$artile_name.zip";
    $maxRetries = 3;
    $download_ok = false;

    for ($i=1;$i<=$maxRetries;$i++) {
        $download_ok = ftp_get($conn_id,$local_file,$remote_file,FTP_BINARY);
        $remote_size = ftp_size($conn_id,$remote_file);
        $local_size = file_exists($local_file) ? filesize($local_file) : 0;

        if ($download_ok && ($remote_size <= 0 || $remote_size == $local_size)) break;
        log_event($artile_name,'FP','21',"REV ZIP download failed (retry $i)",0,$xperttrack_db);
        sleep(2);
    }

    if (!$download_ok) throw new Exception("REV ZIP download failed after $maxRetries retries");

    log_event($artile_name,'FP','21',"REV ZIP downloaded successfully",1,$xperttrack_db);

    // === UPDATE WORKFLOW TABLE ===
    $currentDate = date('Y-m-d');
    $currentDateTime = date('Y-m-d H:i:s');
    $afternoon = date('Y-m-d 12:00:00');
    $due_date = (strtotime($currentDateTime) > strtotime($afternoon)) ? date('Y-m-d',strtotime('+1 day')) : $currentDate;

    $stage_rcount = preg_replace('/[^0-9]/','',$stage);
    $rev_source = $work_dir . VOLUME_FOLDER_NAME . "/$artile_name/$stage_prev/";
    $rev_destination = $work_dir . VOLUME_FOLDER_NAME . "/$artile_name/$stage/";

    $db->query("UPDATE inw_inward_dtl SET stage='$stage', prev_stage='$stage_prev', status='PAG', department_id=10, wf_id='$wf_id' WHERE id='$workflow_id'");

   $rev_check = $db->query("SELECT 1 FROM inw_revisions_dtl WHERE inw_id='$workflow_id' AND revision_count='$stage_rcount'");
    if ($rev_check->num_rows==0) {
        $db->query("INSERT INTO inw_revisions_dtl (inw_id, revision_count, received_date, due_date, file_name, created_by, created_on, type)
                    VALUES ('$workflow_id','$stage_rcount',CURDATE(),'$due_date','$destination_dir','54',NOW(),'REV')");
    }

    log_event($artile_name,'AUTHOR SUBMISSION REV1','22',"Workflow record updated & revision entry created",1,$xperttrack_db);

    rmove($rev_source,$rev_destination);

    // === EMAIL NOTIFICATION ===
    $email_st = $db->query("SELECT * FROM email_settings WHERE id='2'")->fetch_assoc();
    $cpemail  = $db->query("SELECT * FROM client_portal_mail_settings WHERE id='12'")->fetch_assoc();

     $to   = $cpemail['mail_to'];
     $cc   = $cpemail['mail_cc'];
     $bcc  = $_REQUEST['mail_cc1'] ?? '';

    $subject = "ACG Revised PDF & Attachments Submitted Successfully – $artile_name";
    $message = "
    Dear Team,<br><br>
    The revised PDF and other attachments for the article <strong>$artile_name</strong> have been successfully uploaded to the portal.<br><br>
    Please check and proceed with the next steps.<br><br>
    Note: This is an auto-generated email, please do not reply.<br><br>
    Regards,<br>ACG Team<br>";

    $email_sent = false;
    for($i=1;$i<=3;$i++){
        $result = sendMail($email_st['host'],$email_st['port'],$email_st['secure'],
            $email_st['from_email'],$email_st['from_password'],
            $email_st['from_email'],$email_st['from_name'],
            $to,$cc,$bcc,$subject,$message);
        if($result){
            log_event($artile_name,'AUTHOR SUBMISSION REV1','23','Email sent successfully',1,$xperttrack_db);
            $email_sent = true;
            break;
        } else {
            log_event($artile_name,'AUTHOR SUBMISSION REV1','23',"Email sending failed (attempt $i)",0,$xperttrack_db);
            sleep(5);
        }
    }
    if(!$email_sent) echo "Email sending failed after multiple retries.";

    ftp_close($conn_id);
    echo "Process completed successfully for $artile_name";

} catch(Exception $e){
    log_event($workflow_id ?? 'NA','ERROR','0',"Fatal error: ".$e->getMessage(),0,$xperttrack_db);
    error_log("Fatal Error: ".$e->getMessage());
    ftp_close($conn_id ?? null);
    echo "Error: ".$e->getMessage();
}
?>
