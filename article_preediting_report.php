<?php
/**
 * Article Pre-editing Report (Client-wise, completion-gated)
 * ---------------------------------------------------------
 * Lists, per client, every article and the CE/PE/XML work done on it at the
 * FP stage. An article is reported ONLY when the LAST department in that
 * client's configured pipeline is Completed (e.g. IAP => XML must be
 * completed, not just CE). Otherwise the article is not shown.
 *
 * Columns:
 *   Sn | Client | Journal | Article ID | Recv Date | Input Type |
 *   Proof page count | MSS pages | Date received into pre-editing |
 *   Time of receipt into pre-editing | Date CE/PE completed and moved to
 *   pagination | Time of CE/PE completion | Pre-editor name |
 *   Actual pre-editing time spent on the file
 *
 * NOTE: No non-production logic. Only FP-stage transactions tied to an article.
 *
 * Usage:
 *   php article_preediting_report.php                # yesterday (default)
 *   php article_preediting_report.php 2026-06-02     # a specific date
 */

date_default_timezone_set('Asia/Kolkata'); // adjust to your timezone

$config = require __DIR__ . '/config.php';

/* -------------------------------------------------------------------------
 * CLIENT-WISE COMPLETION CONFIG
 * -------------------------------------------------------------------------
 * 1) STAGE_DEPT maps each stage label to its inw_transactions.dept id.
 *    Known: CE = 1, Pre-editing (PE) = 2.
 *    >>> CONFIRM the three ids below (Preclean / XML / CE-ELX). Only XML and
 *        CE-ELX affect gating; Preclean is never an end department.
 *
 * 2) CLIENT_STAGES lists each client's pipeline in order. The LAST stage is
 *    the "end department": the article is only reported once that department
 *    has a Completed FP transaction.
 *
 *    The array KEY must match adm_customer_master.cust_name. If your client
 *    codes (ACG, AKA, ...) live in a different column, change the cfg JOIN
 *    below to that column (e.g. c.cust_code).
 * ---------------------------------------------------------------------- */
const STAGE_DEPT = [
    'Pre-Clean'    => 22,   // <-- CONFIRM dept id (never an end dept)
    'Pre-editing' => 2,    // PE  (known)
    'CE'          => 1,    // CE  (known)
    'XML'         => 5,   // <-- CONFIRM dept id (end dept for AKA/GleamPub/IAP)
    'CE-ELX'      => 32,    // <-- CONFIRM dept id (end dept for EDP-Sci)
];

const CLIENT_STAGES = [
    'ACG'      => ['Pre-Clean', 'CE'],
    'AKA'      => ['Pre-editing', 'CE', 'XML'],
    'ASP'      => ['Pre-Clean', 'CE'],
    'EDP-Sci'  => ['CE-ELX'],
    'EOS'      => ['Pre-Clean', 'CE'],
    'GleamPub' => ['Pre-editing', 'CE', 'XML'],
    'IAP'      => ['Pre-editing', 'CE', 'XML'],
    'PEERJ'    => ['Pre-Clean', 'CE'],
    'SIF'      => ['Pre-editing'],
    'TSP'      => ['Pre-Clean', 'CE'],
];

// --- Resolve the report date (CLI arg or yesterday) -------------------------
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

// --- Fetch, render, send ----------------------------------------------------
$rows = fetchArticleRows($pdo, $reportDate, $config['filters']['stage']);
$html = renderReportHtml($rows, $reportDate);

$subject = sprintf('Editorial Dispatch Report - %s', $reportDate);
$sent    = sendReport($config['mail'], $subject, $html);

echo $sent
    ? "Report for {$reportDate} sent (" . count($rows) . " articles).\n"
    : "Report for {$reportDate} generated but sending FAILED.\n";

exit($sent ? 0 : 1);


/* =========================================================================
 * Data access
 * ======================================================================= */

/**
 * One aggregated row per article for the given day, gated so that only
 * articles whose client's END department is Completed are returned.
 */
function fetchArticleRows(PDO $pdo, string $reportDate, string $stage): array
{
    // Every department that appears in any pipeline (for the dept IN filter).
    $deptIds = array_values(array_unique(array_map('intval', STAGE_DEPT)));
    $deptInSql = implode(',', $deptIds) ?: '0';

    // Client -> end (last) department id, built from the pipeline config.
    $endDeptByClient = [];
    foreach (CLIENT_STAGES as $client => $stages) {
        $endStage = end($stages);
        if (isset(STAGE_DEPT[$endStage])) {
            $endDeptByClient[$client] = (int) STAGE_DEPT[$endStage];
        }
    }

    // Bound params. (:stage is used twice, so a second name :stage_end.)
    $params = [
        ':stage'       => $stage,
        ':stage_end'   => $stage,
        ':report_date' => $reportDate,
    ];

    // Inline derived table:  cfg(cust_name, end_dept)
    $cfgParts = [];
    $k = 0;
    foreach ($endDeptByClient as $client => $endDept) {
        $cfgParts[]            = "SELECT :cn{$k} AS cust_name, " . (int) $endDept . " AS end_dept";
        $params[":cn{$k}"]     = $client;
        $k++;
    }
    $cfgSql = $cfgParts ? implode(' UNION ALL ', $cfgParts)
                        : "SELECT NULL AS cust_name, 0 AS end_dept";

    $sql = "
        SELECT
            c.cust_name                        AS client,
            j.j_code                           AS journal,
            d.job_id                           AS article_id,
            DATE_FORMAT(d.recv_dt, '%d-%b-%Y') AS recv_dt,
            d.inw_type                         AS inw_type,
            d.page_count                       AS page_count,
            d.manuscript_count                 AS mss_pages,

            /* Received into pre-editing = first CE/PE start on the file. */
            MIN(t.start_time)                  AS received_dt,

            /* Completed and moved to pagination = last CE/PE/XML end. */
            MAX(t.end_time)                    AS completed_dt,

            /* Editor(s) who worked the file. */
            GROUP_CONCAT(DISTINCT u.full_name ORDER BY u.full_name SEPARATOR ', ')
                                               AS preeditor_name,

            /* Actual time spent = sum of every completed session duration. */
            SUM(TIMESTAMPDIFF(SECOND, t.start_time, t.end_time)) AS spent_seconds

        FROM inw_transactions      t
        JOIN inw_inward_dtl        d ON d.id   = t.project_id
        JOIN users                 u ON u.id   = t.process_user
        LEFT JOIN adm_customer_master c ON c.id  = d.cust_id
        LEFT JOIN adm_journals     j ON j.j_id = d.j_id

        /* Client-wise pipeline config; also limits output to configured clients. */
        JOIN ( {$cfgSql} ) cfg ON cfg.cust_name = c.cust_name

        WHERE t.stage = :stage
          AND t.dept IN ({$deptInSql})
          AND t.current_status = 'Completed'
          AND DATE(t.start_time) = :report_date

          /* Gate: the client's END department must itself be Completed. */
          AND EXISTS (
                SELECT 1
                FROM inw_transactions et
                WHERE et.project_id = d.id
                  AND et.stage = :stage_end
                  AND et.dept  = cfg.end_dept
                  AND et.current_status = 'Completed'
          )

        GROUP BY d.id, c.cust_name, j.j_code, d.job_id,
                 d.recv_dt, d.inw_type, d.page_count, d.manuscript_count
        ORDER BY c.cust_name, j.j_code, d.job_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}


/* =========================================================================
 * Rendering
 * ======================================================================= */

/** Build the HTML report, grouped visually by client. */
function renderReportHtml(array $rows, string $reportDate): string
{
    $displayDate = date('d-M-Y', strtotime($reportDate));

    $thStyle = 'style="border:1px solid #444;padding:6px 8px;background:#1f4e78;color:#fff;font-weight:bold;text-align:left;vertical-align:top;"';
    $tdStyle = 'style="border:1px solid #ccc;padding:5px 8px;vertical-align:top;"';

    $headers = [
        'Sn',
        'Client',
        'Journal',
        'Article ID',
        'Recv Date',
        'Input Type',
        'Proof page count',
        'MSS pages',
        'Date received into pre-editing',
        'Time of receipt into pre-editing',
        'Date CE / PE completed and moved to pagination',
        'Time of CE / PE completion and moved to pagination',
        'Pre-editor name',
        'Actual pre-editing time spent on the file',
    ];

    $html  = '<div style="font-family:Calibri,Arial,sans-serif;font-size:13px;color:#222;">';
    $html .= '<h2 style="margin:0 0 4px;">Article Pre-editing Report (CE / PE / XML)</h2>';
    $html .= '<p style="margin:0 0 12px;color:#555;">Stage: <strong>FP</strong> &nbsp;|&nbsp; '
           . 'Date: <strong>' . h($displayDate) . '</strong></p>';

    if (!$rows) {
        $html .= '<p>No completed CE/PE (FP-stage) articles for this date.</p></div>';
        return $html;
    }

    $html .= '<table style="border-collapse:collapse;width:100%;">';
    $html .= '<thead><tr>';
    foreach ($headers as $head) {
        $html .= "<th {$thStyle}>" . h($head) . '</th>';
    }
    $html .= '</tr></thead><tbody>';

    $sn           = 0;
    $grandSeconds = 0;

    foreach ($rows as $row) {
        $sn++;
        $spent         = $row['spent_seconds'] !== null ? (int) $row['spent_seconds'] : 0;
        $grandSeconds += $spent;

        $html .= '<tr>';
        $html .= "<td {$tdStyle}>" . $sn . '</td>';
        $html .= "<td {$tdStyle}>" . h($row['client'] ?? '-') . '</td>';
        $html .= "<td {$tdStyle}>" . h($row['journal'] ?? '-') . '</td>';
        $html .= "<td {$tdStyle}>" . h($row['article_id'] ?? '-') . '</td>';
        $html .= "<td {$tdStyle}>" . h($row['recv_dt'] ?? '-') . '</td>';
        $html .= "<td {$tdStyle}>" . h($row['inw_type'] ?? '-') . '</td>';
        $html .= "<td {$tdStyle}>" . h($row['page_count'] ?? '-') . '</td>';
        $html .= "<td {$tdStyle}>" . h($row['mss_pages'] ?? '-') . '</td>';
        $html .= "<td {$tdStyle}>" . h(fmtDate($row['received_dt'])) . '</td>';
        $html .= "<td {$tdStyle}>" . h(fmtTime($row['received_dt'])) . '</td>';
        $html .= "<td {$tdStyle}>" . h(fmtDate($row['completed_dt'])) . '</td>';
        $html .= "<td {$tdStyle}>" . h(fmtTime($row['completed_dt'])) . '</td>';
        $html .= "<td {$tdStyle}>" . h($row['preeditor_name'] ?? '-') . '</td>';
        $html .= "<td {$tdStyle}>" . h(secondsToHms($spent)) . '</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody><tfoot><tr>';
    $html .= '<td colspan="13" style="border:1px solid #444;padding:6px 8px;text-align:right;'
           . 'background:#1f4e78;color:#fff;font-weight:bold;">Total pre-editing time spent</td>';
    $html .= '<td style="border:1px solid #444;padding:6px 8px;background:#1f4e78;color:#fff;font-weight:bold;">'
           . h(secondsToHms($grandSeconds)) . '</td>';
    $html .= '</tr></tfoot></table>';

    $html .= '<p style="margin-top:10px;color:#888;font-size:11px;">'
           . 'Auto-generated report. Stage = FP. Article shown only when the '
           . 'client&#39;s final department is Completed.</p>';
    $html .= '</div>';

    return $html;
}


/* =========================================================================
 * Mail
 * ======================================================================= */

function sendReport(array $mail, string $subject, string $html): bool
{
    if (!empty($mail['use_smtp'])) {
        return sendViaPhpMailer($mail, $subject, $html);
    }
    return sendViaMail($mail, $subject, $html);
}

/** Preferred: PHPMailer over SMTP. */
function sendViaPhpMailer(array $mail, string $subject, string $html): bool
{
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

        $m->send();
        return true;
    } catch (Throwable $e) {
        fwrite(STDERR, 'PHPMailer error: ' . $e->getMessage() . "\n");
        return false;
    }
}

/** Fallback: PHP built-in mail(). */
function sendViaMail(array $mail, string $subject, string $html): bool
{
    $to = implode(', ', array_keys($mail['to']));

    $headers   = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = sprintf('From: %s <%s>', $mail['from_name'], $mail['from_email']);
    if (!empty($mail['cc'])) {
        $headers[] = 'Cc: ' . implode(', ', array_keys($mail['cc']));
    }

    return mail($to, $subject, $html, implode("\r\n", $headers));
}


/* =========================================================================
 * Helpers
 * ======================================================================= */

/** HTML-escape. */
function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Format a datetime string as a date (dd-Mon-yyyy). Returns '-' when empty. */
function fmtDate(?string $dt): string
{
    if (!$dt) {
        return '-';
    }
    $ts = strtotime($dt);
    return $ts ? date('d-M-Y', $ts) : $dt;
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
