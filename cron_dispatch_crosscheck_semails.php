<?php
include "includes/conn.php";
include "email-functions.php";

date_default_timezone_set('Asia/Kolkata');

$logoUrl = 'http://103.66.12.3:8010/workflow-new/assets/images/xpertworkflow.svg';

// Fetch email configuration
$email_settings = mysqli_fetch_array(mysqli_query($db, "SELECT * FROM email_settings WHERE id = 5"));
if (!$email_settings) {
    die("Email settings not found!\n");
}

$replyTo = $email_settings['from_email'] ?? $email_settings['from_email']; // fallback

// --------------------------------------------------------
// 💌 Function to build enhanced email body
// --------------------------------------------------------

function buildHtmlEmail($rows, $logoUrl, $replyTo, $dispatchedCount, $notDispatchedCount)
{
    $today = date('d-M-Y', strtotime('-1 day'));

    // Group data by customer
    $customers = [];
    foreach ($rows as $r) {
        $cust = $r['cust_name'];
        if (!isset($customers[$cust])) $customers[$cust] = [];
        $customers[$cust][] = $r;
    }

    // -------------------------------------------------------
    // 🧮 Prepare dynamic summary counts for each customer
    // -------------------------------------------------------
    $summaryRows = [];
    $grandTotal = 0;
    $grandAchieved = 0;
    $grandNotAchieved = 0;

    foreach ($customers as $custName => $custRows) {
        $total = count($custRows);
        $achieved = 0;
        foreach ($custRows as $r) {
            if (!empty($r['sent_date'])) $achieved++;
        }
        $notAchieved = $total - $achieved;

        $summaryRows[] = [
            'customer' => $custName,
            'total' => $total,
            'achieved' => $achieved,
            'notAchieved' => $notAchieved
        ];

        $grandTotal += $total;
        $grandAchieved += $achieved;
        $grandNotAchieved += $notAchieved;
    }

    // ---------------------------------------
    // 🧾 Begin Email HTML
    // ---------------------------------------
    $html = '<!doctype html><html><head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    </head>
    <body style="margin:0;padding:0;background-color:#ffffff;font-family:Arial,Helvetica,sans-serif;color:#333;">

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" 
        style="border-collapse:collapse;background-color:#ffffff;width:100%;">
      <tr><td>

      <!-- Header -->
      <table width="100%" style="border-bottom:3px solid #0056b3;">
        <tr>
          <td style="padding:20px 0;background-color:#f8f9fa;text-align:center;">
            <img src="' . htmlspecialchars($logoUrl) . '" alt="Workflow Logo" style="height:60px;display:inline-block;">
          </td>
        </tr>
      </table>

      <!-- Title -->
      <table width="100%">
        <tr>
          <td style="padding:20px 25px 10px;text-align:center;">
            <h1 style="margin:0;font-size:20px;color:#0056b3;">Schedule - Article Dispatch Details — ' . $today . '</h1>
            <p style="margin:8px 0 15px 0;color:#555;font-size:14px;">
              Below are the approved dispatch entries for <strong>' . $today . '</strong>.
            </p>
          </td>
        </tr>
      </table>

      <!-- ✅ Dynamic Summary Table -->
      <table width="80%" align="center" border="1" cellspacing="0" cellpadding="8"
        style="border-collapse:collapse;font-size:14px;margin:10px auto 25px auto;border:1px solid #ccc;">
        <thead style="background-color:#e9f3ff;color:#003d80;font-weight:bold;text-align:center;">
          <tr>
            <th style="padding:8px;border:1px solid #ccc;">Planned Articles</th>
            <th style="padding:8px;border:1px solid #ccc;">No. of Articles</th>
            <th style="padding:8px;border:1px solid #ccc;">Achieved</th>
            <th style="padding:8px;border:1px solid #ccc;">Not Achieved</th>
          </tr>
        </thead>
        <tbody>';

    foreach ($summaryRows as $row) {
        $html .= '<tr style="text-align:center;background-color:#fff;">
            <td style="border:1px solid #ccc;">' . htmlspecialchars($row['customer']) . '</td>
            <td style="border:1px solid #ccc;">' . (int)$row['total'] . '</td>
            <td style="border:1px solid #ccc;color:green;font-weight:bold;">' . (int)$row['achieved'] . '</td>
            <td style="border:1px solid #ccc;color:red;font-weight:bold;">' . (int)$row['notAchieved'] . '</td>
          </tr>';
    }

    // 🔹 Add total summary row
    $html .= '<tr style="text-align:center;background-color:#f3f3f3;font-weight:bold;">
        <td style="border:1px solid #ccc;">Total</td>
        <td style="border:1px solid #ccc;">' . (int)$grandTotal . '</td>
        <td style="border:1px solid #ccc;color:green;">' . (int)$grandAchieved . '</td>
        <td style="border:1px solid #ccc;color:red;">' . (int)$grandNotAchieved . '</td>
      </tr>';

    $html .= '</tbody></table>';

    // -------------------------------------------------------
    // 🔹 Section Separator Before Detailed Tables
    // -------------------------------------------------------
    $html .= '<div style="margin:30px 0 15px;text-align:center;">
        <h3 style="color:#0056b3;font-size:18px;font-weight:bold;margin:0;">
            Detailed Dispatch Breakdown by Customer
        </h3>
        <p style="color:#666;font-size:13px;margin-top:5px;">
            The following section lists article-level dispatch details for each customer.
        </p>
      </div>';

    // -------------------------------------------------------
    // 🧾 Detailed Customer Tables
    // -------------------------------------------------------
    $colorsTable = ['#e9f3ff', '#fff4e5', '#eafaf1', '#fbeaff', '#f0f9ff', '#fff6f6', '#f4fff0'];
    $colorIndex = 0;

    foreach ($customers as $custName => $custRows) {
        $custColor = $colorsTable[$colorIndex % count($colorsTable)];
        $colorIndex++;

        $html .= '<table width="100%" border="1" cellspacing="0" cellpadding="8" 
                    style="border-collapse:collapse;width:100%;border:1px solid #dcdcdc;font-size:14px;margin-bottom:25px;">
            <thead>
              <tr style="background-color:' . $custColor . ';color:#003d80;font-weight:600;text-align:left;">
                <th colspan="10" style="padding:10px;border:1px solid #dcdcdc;font-size:15px;">
                  Customer: ' . htmlspecialchars($custName) . ' (' . count($custRows) . ' Articles)
                </th>
              </tr>
              <tr style="background-color:#e9f3ff;color:#003d80;font-weight:600;text-align:left;">
                <th style="padding:10px;border:1px solid #dcdcdc;">S.No</th>
                <th style="padding:10px;border:1px solid #dcdcdc;">Journal</th>
                <th style="padding:10px;border:1px solid #dcdcdc;">Article ID</th>
                <th style="padding:10px;border:1px solid #dcdcdc;">Stage</th>
                <th style="padding:10px;border:1px solid #dcdcdc;">Status</th>
                <th style="padding:10px;border:1px solid #dcdcdc;">Page Count</th>
                <th style="padding:10px;border:1px solid #dcdcdc;">Received Date</th>
                <th style="padding:10px;border:1px solid #dcdcdc;">Due Date</th>
                <th style="padding:10px;border:1px solid #dcdcdc;">Created</th>
                <th style="padding:10px;border:1px solid #dcdcdc;">Sent Date</th>
              </tr>
            </thead>
            <tbody>';

        $sno = 1;
        foreach ($custRows as $r) {
            $sentDate = $r['sent_date']
                ? htmlspecialchars($r['sent_date'])
                : '<span style="color:red;font-weight:bold;">Not Dispatched</span>';
            $bg = $r['sent_date'] ? ($sno % 2 === 0 ? '#ffffff' : '#f9f9f9') : '#ffe5e5';

            $html .= '<tr style="background-color:' . $bg . ';text-align:left;">
                <td style="padding:10px;border:1px solid #dcdcdc;text-align:center;">' . $sno . '</td>
                <td style="padding:10px;border:1px solid #dcdcdc;">' . htmlspecialchars($r['j_code']) . '</td>
                <td style="padding:10px;border:1px solid #dcdcdc;">' . htmlspecialchars($r['pub_id']) . '</td>
                <td style="padding:10px;border:1px solid #dcdcdc;">' . htmlspecialchars($r['stage']) . '</td>
                <td style="padding:10px;border:1px solid #dcdcdc;">' . htmlspecialchars($r['article_status']) . '</td>
                <td style="padding:10px;border:1px solid #dcdcdc;text-align:center;">' . (int)$r['page_count'] . '</td>
                <td style="padding:10px;border:1px solid #dcdcdc;">' . (!empty($r['received_date']) ? htmlspecialchars($r['received_date']) : '-') . '</td>
                <td style="padding:10px;border:1px solid #dcdcdc;">' . (!empty($r['due_date']) ? htmlspecialchars($r['due_date']) : '-') . '</td>
                <td style="padding:10px;border:1px solid #dcdcdc;">' . htmlspecialchars($r['created_dt']) . '</td>
                <td style="padding:10px;border:1px solid #dcdcdc;">' . $sentDate . '</td>
            </tr>';
            $sno++;
        }

        $html .= '</tbody></table>';
    }

    // Footer message only
    $html .= '<table width="100%" style="margin-top:20px;border-top:1px solid #e6e6e6;">
        <tr>
          <td style="background-color:#f8f9fa;padding:20px 25px;font-size:13px;color:#777;text-align:center;">
            <p style="margin:0;">Regards,<br><strong>Workflow Team</strong></p>
            <p style="margin-top:8px;font-size:12px;color:#999;line-height:1.6;">
              This is an automated message. Please do not reply.<br>
              For assistance, contact 
              <a href="mailto:' . $replyTo . '" style="color:#0056b3;text-decoration:none;">' . $replyTo . '</a>.
            </p>
          </td>
        </tr>
      </table>

      </td></tr></table>
    </body></html>';

    return $html;
}




// --------------------------------------------------------
// 🧾 Fetch rows and dispatch status
// --------------------------------------------------------
$today = date('Y-m-d', strtotime('-1 day'));

$sql = "SELECT 
          h.history_id, h.article_id, h.pub_id, h.cust_name, h.j_code,
          h.stage, h.status AS article_status, 
          DATE_FORMAT(h.received_date, '%d-%m-%Y') AS received_date,
          DATE_FORMAT(h.due_date, '%d-%m-%Y') AS due_date,
          h.page_count, 
          DATE_FORMAT(h.created_dt, '%d-%m-%Y %H:%i') AS created_dt,
          d.id AS daily_id, d.schedule_date, d.status, h.inw_id
        FROM plan_article_to_dispatch_mail_history h
        JOIN plan_article_to_dispatch_mail_daily d ON h.schedule_id = d.id
        WHERE d.schedule_date = ? 
          AND d.dispatch_status IN ('Pending')";

$stmt = $db->prepare($sql);
$stmt->bind_param('s', $today);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;

if (empty($rows)) {
    echo "✅ No approved records to mail for $today\n";
    exit;
}

// 🚚 Match with inw_dispatch_history
$dispatchedCount = 0;
$notDispatchedCount = 0;

foreach ($rows as &$row) {
    $check = $db->prepare("SELECT DATE_FORMAT(sent_date, '%d-%m-%Y %H:%i') AS sent_date 
                           FROM inw_dispatch_history 
                           WHERE a_id = ? AND stage = ? 
                           ORDER BY sent_date DESC LIMIT 1");
    $check->bind_param('is', $row['inw_id'], $row['stage']);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $row['sent_date'] = $data['sent_date'];
        $dispatchedCount++;
    } else {
        $row['sent_date'] = null;
        $notDispatchedCount++;
    }
}
unset($row);

// ✉️ Build Email & Send
$htmlBody = buildHtmlEmail($rows, $logoUrl, $replyTo, $dispatchedCount, $notDispatchedCount);
$subject  = "Schedule - Article Dispatch Details — " . date('d-m-Y', strtotime('-1 day'));

$maxRetries = 3;
$attempt = 0;
$mailSent = false;

while ($attempt < $maxRetries && !$mailSent) {
    $attempt++;
    echo "Attempt $attempt to send email...\n";

    try {
        $mailSent = sendMail(
            $email_settings['host'],
            $email_settings['port'],
            $email_settings['secure'],
            $email_settings['from_email'],
            $email_settings['from_password'],
            $email_settings['from_email'],
            $email_settings['from_name'],
            $email_settings['mail_sto'],
            $email_settings['mail_scc'],
            $email_settings['mail_sbcc'],
            $subject,
            $htmlBody
        );

        if ($mailSent) {
            echo "✅ Mail sent successfully on attempt $attempt\n";
        } else {
            echo "⚠️ Send failed (attempt $attempt)\n";
            sleep(5);
        }

    } catch (Exception $e) {
        error_log("Mailer Exception: " . $e->getMessage());
        sleep(5);
    }
}

// 🗃 Update DB based on result
$dailyIds = array_unique(array_column($rows, 'daily_id'));
$in = implode(',', array_map('intval', $dailyIds));

if ($mailSent) {
    $upd = "UPDATE plan_article_to_dispatch_mail_daily 
            SET dispatch_status = 'Dispatch Mail Sent', dmailsent_datetime = NOW() 
            WHERE id IN ($in)";
    $db->query($upd);
    echo "✅ Updated dispatch_status to 'Dispatch Mail Sent'.\n";
} else {
    $updFail = "UPDATE plan_article_to_dispatch_mail_daily 
                SET dispatch_status = 'Dispatch Mail Failed', dmailsent_datetime = NOW() 
                WHERE id IN ($in)";
    $db->query($updFail);
    error_log("❌ Mail failed after $maxRetries attempts. Updated to 'Dispatch Mail Failed'.");
}
?>
