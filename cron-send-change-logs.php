<?php
/**
 * Cron job: email all pending change-log entries (change_logs.mail_sent = 0) as one digest.
 *
 * Run from the command line / Task Scheduler, e.g. every 10 minutes:
 *     php C:/LAMP/Apache/htdocs/workflow-new/cron-send-change-logs.php
 * (see cron-send-change-logs.bat)
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script can only be run from the command line.\n");
}

set_time_limit(0);
chdir(__DIR__);
date_default_timezone_set('Asia/Kolkata');

// Prevent two runs from overlapping
$lock = fopen(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'change_log_cron.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    exit("Another run is still in progress.\n");
}

include "includes/conn.php";
include_once "includes/change_log_mail.php";

$sent = change_log_send_pending();
echo date('Y-m-d H:i:s') . " - " . ($sent > 0 ? "digest sent with $sent change(s)" : "nothing to send") . "\n";

flock($lock, LOCK_UN);
fclose($lock);
