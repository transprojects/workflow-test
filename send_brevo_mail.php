<?php
$apiKey = "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";  // Replace with your API key
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.brevo.com/v3/account");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "accept: application/json",
    "api-key: $apiKey"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<b>HTTP Code:</b> $httpcode<br>";
echo "<pre>$response</pre>";
?>


<?php
// ==========================
// Brevo API Email Trigger Script
// ==========================

// Your Brevo (Sendinblue) API key
$apiKey = "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";  // Replace with your API key

// Email details
$toEmail    = "marimuthu@transforma.in";
$toName     = "Marimuthu  Pandian M";

$ccEmails   = ["techsupport@transforma.in"];   // optional
$bccEmails  = ["marimuthu@transforma.in"];                     // optional

$subject    = "Test Email from Brevo API (HTML)";
$fromEmail  = "editor@eu-opensci.org";
$fromName   = "European Open Science";

// HTML content
$htmlContent = "
<html>
  <head><title>Brevo Email Test</title></head>
  <body style='font-family:Arial,sans-serif;'>
    <h2 style='color:#2a7ae2;'>Hello from Brevo!</h2>
    <p>This is a <b>test email</b> sent using <b>Brevo API</b> via PHP.</p>
    <p>Regards,<br><b>Your Company</b></p>
  </body>
</html>
";

// Build payload base
$data = [
    "sender" => [
        "name"  => $fromName,
        "email" => $fromEmail
    ],
    "to" => [
        ["email" => $toEmail, "name" => $toName]
    ],
    "subject" => $subject,
    "htmlContent" => $htmlContent
];

// Build cc array (compatible with older PHP versions)
if (!empty($ccEmails) && is_array($ccEmails)) {
    $ccArr = [];
    foreach ($ccEmails as $cc) {
        // basic validation: skip empty values
        $cc = trim($cc);
        if ($cc !== '') {
            $ccArr[] = ["email" => $cc];
        }
    }
    if (!empty($ccArr)) {
        $data['cc'] = $ccArr;
    }
}

// Build bcc array
if (!empty($bccEmails) && is_array($bccEmails)) {
    $bccArr = [];
    foreach ($bccEmails as $bcc) {
        $bcc = trim($bcc);
        if ($bcc !== '') {
            $bccArr[] = ["email" => $bcc];
        }
    }
    if (!empty($bccArr)) {
        $data['bcc'] = $bccArr;
    }
}

// Optionally: add multiple "to" recipients example
// $data['to'][] = ["email" => "second@example.com", "name" => "Second Recipient"];

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.brevo.com/v3/smtp/email");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "accept: application/json",
    "api-key: $apiKey",
    "content-type: application/json"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute and handle response
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($response === false) {
    $curlErr = curl_error($ch);
}
curl_close($ch);

if (isset($curlErr)) {
    echo "<b>❌ cURL error:</b> " . htmlspecialchars($curlErr);
    exit;
}

if ($httpcode == 201) {
    echo "<b>✅ Email sent successfully!</b>";
} else {
    echo "<b>❌ Failed to send email. HTTP code: " . intval($httpcode) . "</b><br>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}
?>
