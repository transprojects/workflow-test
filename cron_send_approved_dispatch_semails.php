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

function buildHtmlEmail($rows, $logoUrl, $replyTo)
{
    $today = date('d-m-Y');

    // --- Group by customer ---
    $customers = [];
    foreach ($rows as $r) {
        $cust = $r['cust_name'];
        if (!isset($customers[$cust])) $customers[$cust] = [];
        $customers[$cust][] = $r;
    }

    // --- Predefine color palette for customer sections ---
    $colors = ['#e9f3ff', '#fff4e5', '#eafaf1', '#fbeaff', '#f0f9ff', '#fff6f6', '#f4fff0'];
    $colorIndex = 0;

    // --- Customer Count Summary ---
    $custSummary = '<table width="100%" border="0" cellspacing="0" cellpadding="8" 
                    style="border-collapse:collapse;margin-bottom:20px;font-size:14px;width:100%;">
        <tr><td colspan="3" style="padding:10px 0;font-weight:600;font-size:16px;color:#003d80;text-align:center;">
            <u>Customer-wise Article Count Summary</u>
        </td></tr>
        <tr style="background-color:#0056b3;color:#fff;font-weight:600;text-align:center;">
            <th style="padding:8px;border:1px solid #dcdcdc;">S.No</th>
            <th style="padding:8px;border:1px solid #dcdcdc;">Customer</th>
            <th style="padding:8px;border:1px solid #dcdcdc;">Article Count</th>
        </tr>';

    $i = 1;
    foreach ($customers as $custName => $custRows) {
        $custSummary .= '<tr style="text-align:center;background-color:' . ($i % 2 == 0 ? '#f9f9f9' : '#ffffff') . ';">
            <td style="padding:8px;border:1px solid #dcdcdc;">' . $i . '</td>
            <td style="padding:8px;border:1px solid #dcdcdc;text-align:left;">' . htmlspecialchars($custName) . '</td>
            <td style="padding:8px;border:1px solid #dcdcdc;">' . count($custRows) . '</td>
        </tr>';
        $i++;
    }
    $custSummary .= '</table>';

    // --- Email Header ---
    $html = '<!doctype html><html><head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    </head>
    <body style="margin:0;padding:0;background-color:#ffffff;font-family:Arial,Helvetica,sans-serif;color:#333;">

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" 
        style="border-collapse:collapse;background-color:#ffffff;width:100%;">
      <tr>
        <td style="padding:0;margin:0;">

          <!-- Header -->
          <table width="100%" border="0" cellspacing="0" cellpadding="0" 
              style="border-collapse:collapse;width:100%;border-bottom:3px solid #0056b3;">
            <tr>
              <td style="padding:20px 0;background-color:#f8f9fa;text-align:center;">
                <img src="' . htmlspecialchars($logoUrl) . '" alt="Workflow Logo" style="height:60px;display:inline-block;">
              </td>
            </tr>
          </table>

          <!-- Title Section -->
          <table width="100%" border="0" cellspacing="0" cellpadding="0" 
              style="border-collapse:collapse;width:100%;">
            <tr>
              <td style="padding:20px 25px 10px;text-align:center;">
                <h1 style="margin:0;font-size:20px;color:#0056b3;">Planned Dispatch Article Details — ' . $today . '</h1>
                <p style="margin:8px 0 15px 0;color:#555;font-size:14px;">
                  Below are the approved dispatch entries for <strong>' . $today . '</strong>.
                </p>
              </td>
            </tr>
          </table>

          ' . $custSummary . '  <!-- ✅ Customer Summary Table Added -->

          <!-- Detailed Data Table -->';

    // --- Customer-wise section tables ---
    foreach ($customers as $custName => $custRows) {
        $custColor = $colors[$colorIndex % count($colors)];
        $colorIndex++;

        $html .= '<table width="100%" border="1" cellspacing="0" cellpadding="8" 
                    style="border-collapse:collapse;width:100%;border:1px solid #dcdcdc;font-size:14px;margin-bottom:25px;">
            <thead>
              <tr style="background-color:' . $custColor . ';color:#003d80;font-weight:600;text-align:left;">
                <th colspan="9" style="padding:10px;border:1px solid #dcdcdc;font-size:15px;">
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
              </tr>
            </thead>
            <tbody>';

        $sno = 1;
        foreach ($custRows as $r) {
            $bg = $sno % 2 === 0 ? '#ffffff' : '#f9f9f9';
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
            </tr>';
            $sno++;
        }

        $html .= '</tbody></table>';
    }

    // --- Footer ---
    $html .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" 
                style="border-collapse:collapse;width:100%;">
        <tr>
          <td style="background-color:#f8f9fa;border-top:1px solid #e6e6e6;
                     padding:20px 25px;font-size:13px;color:#777;text-align:center;">
            <p style="margin:0;">Regards,<br><strong>Workflow Team</strong></p>
            <p style="margin-top:8px;font-size:12px;color:#999;line-height:1.6;">
              This is an automated message. Please do not reply.<br>
              For assistance, contact 
              <a href="mailto:' . $replyTo . '" style="color:#0056b3;text-decoration:none;">' . $replyTo . '</a>.
            </p>
          </td>
        </tr>
      </table>

    </td></tr></table></body></html>';

    return $html;
}





// 🧾 Fetch rows: Approved but not yet emailed
$today = date('Y-m-d');

$sql = "SELECT 
          h.history_id, h.article_id, h.pub_id, h.cust_name, h.j_code,
          h.stage, h.status as article_status,  DATE_FORMAT(h.received_date, '%d-%m-%Y') as received_date ,DATE_FORMAT(h.due_date, '%d-%m-%Y') as due_date, h.page_count, 
          DATE_FORMAT(h.created_dt, '%d-%m-%Y %H:%i') AS created_dt,
          d.id AS daily_id, d.schedule_date, d.status
        FROM plan_article_to_dispatch_mail_history h
        JOIN plan_article_to_dispatch_mail_daily d ON h.schedule_id = d.id
        WHERE d.schedule_date = ? 
          AND d.status in ('Approved','Mail Failed')";

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

// ✉️ Build Email Body
$htmlBody = buildHtmlEmail($rows, $logoUrl, $replyTo);
$subject  = "Planned Dispatch Article Details — " . date('d-m-Y');

// 🧠 Retry logic: Try up to 3 times if sending fails
$maxRetries = 3;
$attempt = 0;
$mailSent = false;
$errorMsg = '';

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
            $errorMsg = "Send failed (attempt $attempt)";
            echo "⚠️ $errorMsg\n";
            sleep(5); // wait before retry
        }

    } catch (Exception $e) {
        $errorMsg = "Mailer Exception (attempt $attempt): " . $e->getMessage();
        error_log($errorMsg);
        sleep(5);
    }
}

// ✅ Update DB if sent
if ($mailSent) {
    $dailyIds = array_unique(array_column($rows, 'daily_id'));
    $in = implode(',', array_map('intval', $dailyIds));

    $upd = "UPDATE plan_article_to_dispatch_mail_daily 
            SET status = 'Mail Sent', mailsent_datetime = NOW() 
            WHERE id IN ($in)";

    if ($db->query($upd)) {
        echo "✅ Updated status to 'Mail Sent' for related entries.\n";
    } else {
        error_log("DB update failed: " . $db->error);
    }

} else {
    // ❌ Mark as 'Mail Failed' if all attempts failed
    $dailyIds = array_unique(array_column($rows, 'daily_id'));
    $in = implode(',', array_map('intval', $dailyIds));

    $updFail = "UPDATE plan_article_to_dispatch_mail_daily 
                SET status = 'Mail Failed', mailsent_datetime = NOW() 
                WHERE id IN ($in)";
    $db->query($updFail);

    error_log("❌ Mail failed after $maxRetries attempts. DB updated to 'Mail Failed'.");
}
?>
