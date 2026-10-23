<?php
/**
 * Daily Timesheet Report
 * ----------------------
 * Builds the daily timesheet (one row per work session) and e-mails it
 * as an HTML table that mirrors the supplied Excel format:
 *
 *   Sn | Date | Emp name | Emp ID | Activity | Art ID | Journal |
 *   Client | MS pages | Start time | End time   (+ a Duration column)
 *
 * Table / column mapping (from the spec):
 *   users               -> full_name AS "Emp name", id AS "Emp ID"
 *   inw_inward_dtl       -> job_id AS "Art ID"            (Article Details)
 *                           cust_id -> adm_customer_master.id
 *                           j_id    -> adm_journals.j_id
 *   adm_customer_master  -> cust_name AS "Client"
 *   adm_journals         -> j_code   AS "Journal"
 *   inw_transactions     -> start_time, end_time, completion_status,
 *                           pages_completed AS "MS pages"
 *                           project_id   -> inw_inward_dtl.id
 *                           process_user -> users.id
 *                           stage = 'FP', dept IN (2, 32, 1, 5)
 *
 * Usage:
 *   php daily_timesheet_report.php                # today
 *   php daily_timesheet_report.php 2026-06-02     # a specific date
 *
 * Cron (run every day at 19:00):
 *   0 19 * * * /usr/bin/php /path/to/daily_timesheet_report.php >> /var/log/timesheet.log 2>&1
 */
set_time_limit(0);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '1024M');

date_default_timezone_set('Asia/Kolkata'); // adjust to your timezone

$config = require __DIR__ . '/config.php';

// --- Resolve the report date (CLI arg or today) -----------------------------
$reportDate = $argv[1] ?? date('Y-m-d', strtotime('-1 day'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $reportDate)) {
    fwrite(STDERR, "Invalid date '{$reportDate}'. Use YYYY-MM-DD.\n");
    exit(1);
}

// --- Connect (PDO + prepared statements) ------------------------------------
$db  = $config['db'];
$dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}";
try {
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, 'DB connection failed: ' . $e->getMessage() . "\n");
    exit(1);
}

// --- Fetch the rows ---------------------------------------------------------
$rows = fetchTimesheetRows($pdo, $reportDate, $config['filters']);

// --- Build the e-mail body --------------------------------------------------
$html = renderReportHtml($rows, $reportDate);

// --- Send -------------------------------------------------------------------
$subject = sprintf('%s - %s', $config['mail']['subject_prefix'], $reportDate);
$xlsName = 'daily_timesheet_' . $reportDate . '.xls';
$sent    = sendReport($config['mail'], $subject, $html, $html, $xlsName);

echo $sent
    ? "Report for {$reportDate} sent (" . count($rows) . " rows).\n"
    : "Report for {$reportDate} generated but sending FAILED.\n";

exit($sent ? 0 : 1);


/* =========================================================================
 * Data access
 * ======================================================================= */

/**
 * One row per work session for the given day.
 *
 * Two sources are merged with UNION ALL:
 *   1. inw_transactions  — production / FP-stage work (timed via start/end).
 *   2. non_workflow_user_percentage_article — non-production work logged as a
 *      percentage of the working day. Its work hours are derived as
 *      (nwa_percentage / 100) * workday_hours.
 *
 * A LEFT JOIN on inw_inward_dtl keeps inw_transactions sessions with no linked
 * project/article in the report too.
 */
function fetchTimesheetRows(PDO $pdo, string $reportDate, array $filters): array
{
    // Standard working day expressed in seconds, used to turn a percentage
    // into a duration for the non-production rows.
    $workdaySeconds = (int) round(((float) ($filters['workday_hours'] ?? 8)) * 3600);

    $sql = "
        /* ---- 1. Production work (inw_transactions, stage = FP) ---------- */
        SELECT
            u.id                          AS emp_id,
            u.full_name                   AS emp_name,
            DATE_FORMAT(d.created_dt, '%d-%b-%Y %H:%i:%s')               AS work_date,
            DATE(t.start_time)            AS start_date,
            t.dept                        AS dept,

            /*
             * 'Activity' (e.g. 'Pre-edit'). This is a per-row value in
             * inw_transactions, so it cannot come from the constant stage
             * filter. Rename t.activity below to your real column if needed.
             * Sessions with no project are reported as 'Non-production'.
             */
            CASE
                WHEN t.project_id IS NULL OR t.project_id = 0
                    THEN 'Non-production'
                ELSE COALESCE(NULLIF(t.current_status, ''), 'Production')
            END                           AS activity,

            d.job_id                      AS art_id,
            j.j_code                      AS journal,
            c.cust_name                   AS client,
            d.manuscript_count            AS ms_pages,
            t.start_time                  AS start_time,
            t.end_time                    AS end_time,
            t.completion_status           AS completion_status,

            /* Per-session duration in seconds (NULL if still running). */
            TIMESTAMPDIFF(SECOND, t.start_time, t.end_time) AS duration_seconds

        FROM inw_transactions      t
        JOIN users                 u ON u.id  = t.process_user
        LEFT JOIN inw_inward_dtl   d ON d.id  = t.project_id
        LEFT JOIN adm_customer_master c ON c.id  = d.cust_id
        LEFT JOIN adm_journals     j ON j.j_id = d.j_id

        WHERE t.stage = :stage
          AND t.dept IN (2, 32, 1, 5, 22)
          AND u.user_name IN (              'TT163','TT241','TT244','TT248','TT267','TT263','TT275','TR1156','TT111','TR1176','TR1157','TR1163','TR1166'
          )
          AND DATE(t.start_time) = :report_date

        UNION ALL

        /* ---- 2. Non-production work (percentage of the working day) ----- */
        SELECT
            nw.user_id                              AS emp_id,
            COALESCE(u2.full_name, nw.user_name)    AS emp_name,
            nw.nwa_date                             AS work_date,
            nw.nwa_date                             AS start_date,
            NULL                                    AS dept,
            'Non-production'                        AS activity,
            nw.nwa_pub_id                           AS art_id,
            NULL                                    AS journal,
            nw.nwa_cust_name                        AS client,
            nw.nwa_page_count                       AS ms_pages,			
            NULL                                    AS start_time,
            NULL                                    AS end_time,
            nw.nwa_pt                         		AS completion_status,

            /* hours = (nwa_percentage / 100) * workday  ->  seconds */
            ROUND((COALESCE(nw.nwa_percentage, 0) / 100) * :workday_seconds)
                                                    AS duration_seconds

        FROM non_workflow_user_percentage_article nw
        LEFT JOIN users u2 ON u2.id = nw.user_id
        WHERE nw.nwa_date = :report_date_nw
          AND nw.user_name IN (              'TT163','TT241','TT244','TT248','TT267','TT263','TT275','TR1156','TT111','TR1176','TR1157','TR1163','TR1166'
          )

        /* Group by employee; timed rows first, non-production rows after. */
        ORDER BY emp_name, (start_time IS NULL), start_time
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':stage'           => $filters['stage'],
        ':report_date'     => $reportDate,
        ':report_date_nw'  => $reportDate,
        ':workday_seconds' => $workdaySeconds,
    ]);

    return $stmt->fetchAll();
}


/* =========================================================================
 * Rendering
 * ======================================================================= */

/** Build the HTML report, grouped by employee with a per-employee total. */
function renderReportHtml(array $rows, string $reportDate): string
{
    $displayDate = date('d-M-Y', strtotime($reportDate));

    $thStyle = 'style="border:1px solid #444;padding:6px 8px;background:#1f4e78;color:#fff;font-weight:bold;text-align:left;"';
    $tdStyle = 'style="border:1px solid #ccc;padding:5px 8px;"';

    $headers = ['Sn', 'Date', 'Start date', 'Emp name', 'Emp ID', 'Department', 'Activity/Status', 'Art ID',
                'Journal', 'Client', 'MS pages', 'Start time', 'End time', 'Duration'];

    $html  = '<div style="font-family:Calibri,Arial,sans-serif;font-size:13px;color:#222;">';
    $html .= '<h2 style="margin:0 0 4px;">Daily Timesheet Report</h2>';
    $html .= '<p style="margin:0 0 12px;color:#555;">Date: <strong>' . h($displayDate) . '</strong></p>';

    if (!$rows) {
        $html .= '<p>No timesheet activity recorded for this date.</p></div>';
        return $html;
    }

    $html .= '<table style="border-collapse:collapse;width:100%;">';
    $html .= '<thead><tr>';
    foreach ($headers as $head) {
        $html .= "<th {$thStyle}>" . h($head) . '</th>';
    }
    $html .= '</tr></thead><tbody>';

    $sn            = 0;
    $currentEmp    = null;
    $empSeconds    = 0;
    $grandSeconds  = 0;

    $appendEmpTotal = function (?int $seconds) use (&$html, $tdStyle): string {
        if ($seconds === null) {
            return '';
        }
        return '<tr><td colspan="11" style="border:1px solid #ccc;padding:5px 8px;'
             . 'text-align:right;background:#eef3f8;font-weight:bold;">Total work time</td>'
             . '<td style="border:1px solid #ccc;padding:5px 8px;background:#eef3f8;font-weight:bold;">'
             . h(secondsToHms($seconds)) . '</td></tr>';
    };

    foreach ($rows as $row) {
        // Emit the running total when the employee changes.
        if ($currentEmp !== null && $row['emp_id'] !== $currentEmp) {
            //$html .= $appendEmpTotal($empSeconds);
            //$empSeconds = 0;
        }
        $currentEmp = $row['emp_id'];

        $sn++;
        $dur          = $row['duration_seconds'] !== null ? (int) $row['duration_seconds'] : null;
        $empSeconds  += $dur ?? 0;
        $grandSeconds += $dur ?? 0;

        $html .= '<tr>';
        $html .= "<td {$tdStyle}>" . $sn . '</td>';
        $html .= "<td {$tdStyle}>" . h(date('d-M-Y H:i:s', strtotime($row['work_date']))) . '</td>';
        $html .= "<td {$tdStyle}>" . h(!empty($row['start_date']) ? date('d-M-Y', strtotime($row['start_date'])) : '-') . '</td>';
        $html .= "<td {$tdStyle}>" . h($row['emp_name']) . '</td>';
        $html .= "<td {$tdStyle}>" . h($row['emp_id']) . '</td>';
        $html .= "<td {$tdStyle}>" . h(deptName($row['dept'] ?? null)) . '</td>';
        $html .= "<td {$tdStyle}>" . h($row['activity']) . '</td>';
        $html .= "<td {$tdStyle}>" . h($row['art_id'] ?? '-') . '</td>';
        $html .= "<td {$tdStyle}>" . h($row['journal'] ?? '-') . '</td>';
        $html .= "<td {$tdStyle}>" . h($row['client'] ?? '-') . '</td>';
        $html .= "<td {$tdStyle}>" . h($row['ms_pages'] ?? '-') . '</td>';
        $html .= "<td {$tdStyle}>" . h(fmtTime($row['start_time'])) . '</td>';
        $html .= "<td {$tdStyle}>" . h(fmtTime($row['end_time'])) . '</td>';
        if($row['activity'] == 'Non-production'){
			$html .= "<td {$tdStyle}>" . $row['completion_status'] . '</td>';			
		} else {
			$html .= "<td {$tdStyle}>" . h($dur !== null ? secondsToHms($dur) : 'In progress') . '</td>';			
		}		
		
        $html .= '</tr>';
    }

    // Total for the last employee in the list.
   // $html .= $appendEmpTotal($empSeconds);

    $html .= '</tbody>';
    /*$html .= '<tfoot><tr><td colspan="11" style="border:1px solid #444;padding:6px 8px;text-align:right;'
           . 'background:#1f4e78;color:#fff;font-weight:bold;">Grand total work time</td>';
    $html .= '<td style="border:1px solid #444;padding:6px 8px;background:#1f4e78;color:#fff;font-weight:bold;">'
           . h(secondsToHms($grandSeconds)) . '</td>';
    $html .= '</tr></tfoot>';*/
	$html .= '</table>';

    $html .= '<p style="margin-top:10px;color:#888;font-size:11px;">'
           . 'Auto-generated report. Stage = FP, Dept IN (2, 32, 1, 5).</p>';
    $html .= '</div>';

    return $html;
}


/* =========================================================================
 * Mail
 * ======================================================================= */

function sendReport(array $mail, string $subject, string $html, string $attachment = '', string $attachmentName = ''): bool
{
    if (!empty($mail['use_smtp'])) {
        return sendViaPhpMailer($mail, $subject, $html, $attachment, $attachmentName);
    }
    return sendViaMail($mail, $subject, $html, $attachment, $attachmentName);
}

/** Preferred: PHPMailer over SMTP. Requires composer require phpmailer/phpmailer. */
function sendViaPhpMailer(array $mail, string $subject, string $html, string $attachment = '', string $attachmentName = ''): bool
{
	//echo $html; exit;
    $autoload = __DIR__ . '/smtpmail/phpmailer/class.phpmailer.php';
    if (!is_file($autoload)) {
        fwrite(STDERR, "PHPMailer not installed; falling back to mail().\n");
        return sendViaMail($mail, $subject, $html);
    }
    require_once $autoload;

    $m = new PHPMailer();
    try {
        $m->isSMTP();
        $m->Host       = $mail['smtp_host'];
        $m->Port       = (int) $mail['smtp_port'];
        $m->SMTPAuth   = true;
        $m->Username   = $mail['smtp_user'];
        $m->Password   = $mail['smtp_pass'];
        $m->SMTPSecure = $mail['smtp_secure'];

        $m->setFrom($mail['from_email'], $mail['from_name']);
        foreach ($mail['to'] as $addr => $name) {
            $m->addAddress($addr, $name);
        }
        foreach (($mail['cc'] ?? []) as $addr => $name) {
            $m->addCC($addr, $name);
        }

        $m->isHTML(true);
        $m->Subject = $subject;
        $m->Body    = $html;
        $m->AltBody = strip_tags($html);

        if ($attachment !== '') {
            $m->addStringAttachment(
                $attachment,
                $attachmentName !== '' ? $attachmentName : 'report.xls',
                'base64',
                'application/vnd.ms-excel'
            );
        }

        $m->send();
        return true;
    } catch (Throwable $e) {
        fwrite(STDERR, 'PHPMailer error: ' . $e->getMessage() . "\n");
        return false;
    }
}

/** Fallback: PHP built-in mail(). */
function sendViaMail(array $mail, string $subject, string $html, string $attachment = '', string $attachmentName = ''): bool
{
    $to = implode(', ', array_keys($mail['to']));

    $headers   = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = sprintf('From: %s <%s>', $mail['from_name'], $mail['from_email']);
    if (!empty($mail['cc'])) {
        $headers[] = 'Cc: ' . implode(', ', array_keys($mail['cc']));
    }

    // No attachment: keep the simple HTML message.
    if ($attachment === '') {
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        return mail($to, $subject, $html, implode("\r\n", $headers));
    }

    // With attachment: build a multipart/mixed MIME message.
    $boundary = 'np_' . md5(uniqid('', true));
    $fileName = $attachmentName !== '' ? $attachmentName : 'report.xls';

    $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';

    $body  = '--' . $boundary . "\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $html . "\r\n\r\n";

    $body .= '--' . $boundary . "\r\n";
    $body .= 'Content-Type: application/vnd.ms-excel; name="' . $fileName . "\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n";
    $body .= 'Content-Disposition: attachment; filename="' . $fileName . "\"\r\n\r\n";
    $body .= chunk_split(base64_encode($attachment)) . "\r\n";

    $body .= '--' . $boundary . "--\r\n";

    return mail($to, $subject, $body, implode("\r\n", $headers));
}


/* =========================================================================
 * Helpers
 * ======================================================================= */

/** HTML-escape. */
function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Map a department id to its display name. Returns '-' when empty. */
function deptName($dept): string
{
    if ($dept === null || $dept === '') {
        return '-';
    }
    $departments = ['2' => 'Pre-editing', '1' => 'CE', '5' => 'XML', '32' => 'CE-ELX'];
    return $departments[(string) $dept] ?? (string) $dept;
}

/** Format a datetime string as HH:MM (12h). Returns '-' when empty. */
function fmtTime(?string $dt): string
{
    if (!$dt) {
        return '-';
    }
    $ts = strtotime($dt);
    return $ts ? date('h:i A', $ts) : $dt;
}

/** Convert seconds to H:MM:SS. */
function secondsToHms(int $seconds): string
{
    $seconds = max(0, $seconds);
    $h = intdiv($seconds, 3600);
    $m = intdiv($seconds % 3600, 60);
    $s = $seconds % 60;
    return sprintf('%d:%02d:%02d', $h, $m, $s);
}
