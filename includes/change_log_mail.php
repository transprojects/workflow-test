<?php
/**
 * Shared helper: record a change in the `change_logs` table. Emails are sent in batches by
 * cron-send-change-logs.php (not during the page save).
 * SMTP settings are read from the email_settings table (id 1).
 *
 * @param string $module    Short module name, e.g. "Move Files", "Workflow"
 * @param mixed  $record_id Record id being edited
 * @param string $details   Pre-built change lines (old -> new), plain text
 * @param int    $changes   Total number of changes
 */
function ensure_change_logs_table($db)
{
    $db->query("CREATE TABLE IF NOT EXISTS `change_logs` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `module` VARCHAR(60) NOT NULL,
        `action` VARCHAR(10) NOT NULL DEFAULT 'EDIT',
        `record_id` VARCHAR(100) NOT NULL,
        `changed_by` INT NOT NULL DEFAULT 0,
        `changed_by_name` VARCHAR(150) NOT NULL DEFAULT '',
        `ip_address` VARCHAR(45) NOT NULL DEFAULT '',
        `changes_count` INT NOT NULL DEFAULT 0,
        `details` MEDIUMTEXT NULL,
        `mail_sent` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_module` (`module`),
        KEY `idx_action` (`action`),
        KEY `idx_changed_by` (`changed_by`),
        KEY `idx_created_at` (`created_at`),
        KEY `idx_record` (`record_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Upgrade an older table that was created before the action column existed
    $col = $db->query("SHOW COLUMNS FROM `change_logs` LIKE 'action'");
    if ($col && $col->num_rows == 0) {
        $db->query("ALTER TABLE `change_logs` ADD `action` VARCHAR(10) NOT NULL DEFAULT 'EDIT' AFTER `module`, ADD KEY `idx_action` (`action`)");
    }
}

/**
 * Fetch the current row (before an update) so it can be diffed afterwards.
 */
function change_log_snapshot($table, $pk_col, $id)
{
    global $db;
    $res = $db->query("SELECT * FROM `" . $table . "` WHERE `" . $pk_col . "` = '" . $db->real_escape_string((string) $id) . "' LIMIT 1");
    return ($res && $res->num_rows > 0) ? $res->fetch_assoc() : array();
}

/**
 * Diff old row vs new values (only keys that are columns of the old row), then
 * log + email. Never throws; a failure here must not block the save.
 *
 * @param array $new            column => new value
 * @param array $secret_fields  columns whose values must not be logged
 */
function log_row_change($module, $record_ref, $old, $new, $secret_fields = array())
{
    try {
        $details = "[$module changes]\r\n";
        $changes = 0;
        foreach ($new as $f => $val) {
            if (!array_key_exists($f, $old)) {
                continue; // not a column of the table
            }
            if (is_array($val)) {
                $val = implode(',', $val);
            }
            $o = (string) $old[$f];
            if ($o !== (string) $val) {
                $details .= in_array($f, $secret_fields)
                    ? "  $f: (changed - value hidden)\r\n"
                    : "  $f: '$o' -> '$val'\r\n";
                $changes++;
            }
        }
        if ($changes == 0) {
            $details .= "  (no change)\r\n";
        }
        send_change_log_mail($module, $record_ref, $details, $changes);
    } catch (Exception $e) {
        error_log("log_row_change ($module) failed: " . $e->getMessage());
    }
}

/**
 * For pages that save by delete + re-insert (user target assign): snapshot the
 * target row and its per-customer rows (keyed by cust_id).
 */
function change_log_target_snapshot($uta_id)
{
    global $db;
    $uta_id = $db->real_escape_string((string) $uta_id);
    $snap = array('header' => array(), 'children' => array());
    $res = $db->query("SELECT * FROM user_target_assign WHERE uta_id = '$uta_id' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $snap['header'] = $res->fetch_assoc();
    }
    $res = $db->query("SELECT * FROM user_target_customer_assign WHERE uta_id = '$uta_id'");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $snap['children'][$r['cust_id']] = $r;
        }
    }
    return $snap;
}

/**
 * Flatten a change_log_target_snapshot() into column => value pairs (for ADD / DELETE logs).
 */
function change_log_target_values($snap)
{
    $skip = '/^(uta_id|id|uca_id)$|created|modified|updated/i';
    $out = array();
    foreach ($snap['header'] as $f => $v) {
        if (!preg_match($skip, $f)) {
            $out[$f] = $v;
        }
    }
    foreach ($snap['children'] as $cid => $row) {
        $parts = array();
        foreach ($row as $f => $v) {
            if (!preg_match($skip, $f) && $f !== 'cust_id' && $f !== 'user_id' && (string) $v !== '') {
                $parts[] = "$f=$v";
            }
        }
        $out['customer ' . $cid] = implode(', ', $parts);
    }
    return $out;
}

/**
 * Diff two change_log_target_snapshot() results and log + email the result.
 */
function log_target_assign_change($module, $record_ref, $old, $new)
{
    try {
        $skip = '/^(uta_id|id|uca_id)$|created|modified|updated/i';
        $details = "[$module changes]\r\n";
        $changes = 0;

        $details .= "Target totals / averages:\r\n";
        foreach ($new['header'] as $f => $val) {
            if (preg_match($skip, $f) || !array_key_exists($f, $old['header'])) {
                continue;
            }
            if ((string) $old['header'][$f] !== (string) $val) {
                $details .= "  $f: '" . $old['header'][$f] . "' -> '$val'\r\n";
                $changes++;
            }
        }

        $details .= "\r\nCustomer-wise targets:\r\n";
        $cust_names = array();
        $all_ids = array_unique(array_merge(array_keys($old['children']), array_keys($new['children'])));
        foreach ($all_ids as $cid) {
            $o = isset($old['children'][$cid]) ? $old['children'][$cid] : null;
            $n = isset($new['children'][$cid]) ? $new['children'][$cid] : null;
            if ($o === null) {
                $details .= "  Customer $cid: added\r\n";
                $changes++;
                continue;
            }
            if ($n === null) {
                $details .= "  Customer $cid: removed\r\n";
                $changes++;
                continue;
            }
            foreach ($n as $f => $val) {
                if (preg_match($skip, $f) || $f === 'user_id' || $f === 'cust_id' || !array_key_exists($f, $o)) {
                    continue;
                }
                if ((string) $o[$f] !== (string) $val) {
                    $details .= "  Customer $cid | $f: '" . $o[$f] . "' -> '$val'\r\n";
                    $changes++;
                }
            }
        }
        if ($changes == 0) {
            $details .= "  (no change)\r\n";
        }
        send_change_log_mail($module, $record_ref, $details, $changes);
    } catch (Exception $e) {
        error_log("log_target_assign_change ($module) failed: " . $e->getMessage());
    }
}

/**
 * Render a value for the log (arrays -> comma list / JSON when nested).
 */
function change_log_value($val)
{
    if (!is_array($val)) {
        return (string) $val;
    }
    foreach ($val as $v) {
        if (is_array($v)) {
            return json_encode($val);
        }
    }
    return implode(',', $val);
}

/**
 * Best record reference for an ADD: the first non-empty POST field from $keys, else the new insert id.
 */
function change_log_ref($keys = array())
{
    global $db;
    foreach ((array) $keys as $k) {
        if (isset($_POST[$k]) && !is_array($_POST[$k]) && trim($_POST[$k]) !== '') {
            return trim($_POST[$k]);
        }
    }
    $id = (int) $db->insert_id;
    return $id > 0 ? (string) $id : 'new';
}

/**
 * Log + email a newly ADDED record. $values: column => value.
 */
function log_row_added($module, $record_ref, $values, $secret_fields = array())
{
    try {
        $details = "[$module added]\r\n";
        $n = 0;
        foreach ($values as $f => $val) {
            $val = change_log_value($val);
            if ($val === '' || $val === '[]') {
                continue;
            }
            $details .= in_array($f, $secret_fields) ? "  $f: (value hidden)\r\n" : "  $f: '$val'\r\n";
            $n++;
        }
        send_change_log_mail($module, $record_ref, $details, $n, 'ADD');
    } catch (Exception $e) {
        error_log("log_row_added ($module) failed: " . $e->getMessage());
    }
}

/**
 * Log + email a DELETED record. $old_row: the row fetched with change_log_snapshot() before the delete.
 */
function log_row_deleted($module, $record_ref, $old_row, $secret_fields = array())
{
    try {
        $details = "[$module deleted - values before deletion]\r\n";
        $n = 0;
        foreach ($old_row as $f => $val) {
            if ($val === null || (string) $val === '') {
                continue;
            }
            $details .= in_array($f, $secret_fields) ? "  $f: (value hidden)\r\n" : "  $f: '$val'\r\n";
            $n++;
        }
        if ($n == 0) {
            $details .= "  (record not found / already deleted)\r\n";
        }
        send_change_log_mail($module, $record_ref, $details, 1, 'DELETE');
    } catch (Exception $e) {
        error_log("log_row_deleted ($module) failed: " . $e->getMessage());
    }
}

/**
 * Record a change in `change_logs`. The email is NOT sent here (it would slow down the
 * page that is being saved); cron-send-change-logs.php emails all pending entries as one
 * digest. Define CHANGE_LOG_INSTANT_MAIL as true before including this file to send
 * an email per change again.
 */
function send_change_log_mail($module, $record_id, $details, $changes, $action = 'EDIT')
{
    global $db;

    $now     = date('Y-m-d H:i:s');
    $user_id = isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : 'unknown';
    $ip      = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';

    // Look up the editor's name
    $user_name = '';
    $res_u = $db->query("SELECT full_name, user_name FROM users WHERE id = " . (int) $user_id);
    if ($res_u && $res_u->num_rows > 0) {
        $u = $res_u->fetch_assoc();
        $user_name = $u['full_name'] ?: $u['user_name'];
    }

    $log_row_id = 0;
    try {
        ensure_change_logs_table($db);
        $db->query(sprintf(
            "INSERT INTO `change_logs` (module, action, record_id, changed_by, changed_by_name, ip_address, changes_count, details, created_at)
             VALUES ('%s', '%s', '%s', %d, '%s', '%s', %d, '%s', '%s')",
            $db->real_escape_string($module),
            $db->real_escape_string($action),
            $db->real_escape_string((string) $record_id),
            (int) $user_id,
            $db->real_escape_string($user_name),
            $db->real_escape_string($ip),
            (int) $changes,
            $db->real_escape_string($details),
            $now
        ));
        $log_row_id = (int) $db->insert_id;
    } catch (Exception $e) {
        error_log("change_log_mail: could not save change_logs row: " . $e->getMessage());
    }

    if (defined('CHANGE_LOG_INSTANT_MAIL') && CHANGE_LOG_INSTANT_MAIL && $log_row_id > 0) {
        change_log_send_pending(array($log_row_id));
    }
}

/**
 * Email pending (mail_sent = 0) change_logs rows as ONE digest with a .txt attachment,
 * then mark them as sent. Returns the number of entries emailed.
 *
 * @param array|null $only_ids  limit to these ids (used by instant mode); null = all pending
 * @param int        $limit     max entries per digest
 */
function change_log_send_pending($only_ids = null, $limit = 500)
{
    global $db;
    $to_list = array('marimuthu@transforma.in', 'swamy@transforma.in', 'dhamodaran@transforma.in');

    ensure_change_logs_table($db);
    $where = "mail_sent = 0";
    if (is_array($only_ids)) {
        $where .= " AND id IN (" . implode(',', array_map('intval', $only_ids ?: array(0))) . ")";
    }
    $res = $db->query("SELECT * FROM change_logs WHERE $where ORDER BY id ASC LIMIT " . (int) $limit);
    $rows = array();
    while ($res && ($r = $res->fetch_assoc())) {
        $rows[] = $r;
    }
    if (!$rows) {
        return 0;
    }

    $res_es = $db->query("SELECT * FROM email_settings WHERE id = 1");
    $es = $res_es ? $res_es->fetch_assoc() : null;
    if (!$es || empty($es['from_password'])) {
        error_log("change_log_mail: email_settings id=1 not found, digest not sent");
        return 0;
    }

    $action_word = array('ADD' => 'ADDED', 'DELETE' => 'DELETED', 'EDIT' => 'UPDATED');
    $first = $rows[0]['created_at'];
    $last  = $rows[count($rows) - 1]['created_at'];

    // Full text attachment + HTML summary table
    $text  = "WORKFLOW CHANGE LOG DIGEST\r\n=========================\r\n";
    $text .= "Entries : " . count($rows) . "\r\nFrom    : $first\r\nTo      : $last\r\n\r\n";
    $html_rows = '';
    $ids = array();
    foreach ($rows as $r) {
        $ids[] = (int) $r['id'];
        $who = $r['changed_by_name'] !== '' ? $r['changed_by_name'] . " (user_id " . $r['changed_by'] . ")" : "user_id " . $r['changed_by'];
        $act = isset($action_word[$r['action']]) ? $action_word[$r['action']] : $r['action'];
        $text .= str_repeat('-', 70) . "\r\n";
        $text .= "#" . $r['id'] . "  " . strtoupper($r['module']) . " - $act\r\n";
        $text .= "Record ID      : " . $r['record_id'] . "\r\n";
        $text .= "Changed by     : $who\r\n";
        $text .= "IP address     : " . $r['ip_address'] . "\r\n";
        $text .= "Date/Time      : " . $r['created_at'] . "\r\n\r\n";
        $text .= $r['details'] . "\r\nTotal changes: " . $r['changes_count'] . "\r\n\r\n";
        $html_rows .= "<tr><td style='border:1px solid #ccc;padding:4px'>" . (int) $r['id'] . "</td>"
            . "<td style='border:1px solid #ccc;padding:4px'>" . htmlspecialchars($r['created_at']) . "</td>"
            . "<td style='border:1px solid #ccc;padding:4px'>" . htmlspecialchars($act) . "</td>"
            . "<td style='border:1px solid #ccc;padding:4px'>" . htmlspecialchars($r['module']) . "</td>"
            . "<td style='border:1px solid #ccc;padding:4px'>" . htmlspecialchars($r['record_id']) . "</td>"
            . "<td style='border:1px solid #ccc;padding:4px'>" . htmlspecialchars($who) . "</td>"
            . "<td style='border:1px solid #ccc;padding:4px'>" . (int) $r['changes_count'] . "</td></tr>";
    }

    $tmp = tempnam(sys_get_temp_dir(), 'chglog');
    file_put_contents($tmp, $text);

    include_once("smtpmail/phpmailer/class.phpmailer.php");
    $mail = new PHPMailer();
    $mail->IsSMTP();
    $mail->CharSet = 'UTF-8';
    $mail->Host = $es['host'];
    $mail->SMTPAuth = true;
    $mail->Port = $es['port'];
    $mail->Username = $es['from_email'];
    $mail->Password = $es['from_password'];
    $mail->SMTPSecure = $es['secure'];
    $mail->From = $es['from_email'];
    $mail->FromName = $es['from_name'] ?: $es['from_email'];
    $mail->isHTML(true);
    $mail->Subject = "Workflow Change Log - " . count($rows) . " change(s) (" . $first . " to " . $last . ")";
    $mail->Body = "Dear Team,<br/><br/>" . count($rows) . " change(s) were recorded between <b>" . htmlspecialchars($first) . "</b> and <b>" . htmlspecialchars($last) . "</b>. "
        . "Full old &rarr; new values are in the attached text file and on the Change Logs page.<br/><br/>"
        . "<table style='border-collapse:collapse;font-family:Arial;font-size:13px'>"
        . "<tr style='background:#f2f2f2'><th style='border:1px solid #ccc;padding:4px'>#</th><th style='border:1px solid #ccc;padding:4px'>Time</th><th style='border:1px solid #ccc;padding:4px'>Action</th><th style='border:1px solid #ccc;padding:4px'>Module</th><th style='border:1px solid #ccc;padding:4px'>Record</th><th style='border:1px solid #ccc;padding:4px'>Changed by</th><th style='border:1px solid #ccc;padding:4px'>Changes</th></tr>"
        . $html_rows . "</table><br/>Thanks &amp; Regards,<br/>Workflow System<br/>Transforma Pvt. Ltd.";
    $mail->AltBody = count($rows) . " change(s) recorded between $first and $last. See attached log.";
    foreach ($to_list as $email) {
        $mail->addAddress($email);
    }
    $mail->AddAttachment($tmp, "change_log_" . date('Ymd_His') . ".txt");

    $sent = $mail->Send();
    @unlink($tmp);
    if (!$sent) {
        error_log("change_log_mail digest error: " . $mail->ErrorInfo);
        return 0;
    }
    $db->query("UPDATE change_logs SET mail_sent = 1 WHERE id IN (" . implode(',', $ids) . ")");
    return count($rows);
}
