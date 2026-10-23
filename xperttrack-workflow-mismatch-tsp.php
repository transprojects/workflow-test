<?php

set_time_limit(0);
// Database connections
$godaddyDb = new mysqli('68.178.166.2', 'xperttrackadmin_tsp', '2g5x(NKEl7tE', 'xperttrackadmin_tsp');
$windowsDb = new mysqli('localhost', 'root', 'root@123', 'workflow');

// Check connections
if ($godaddyDb->connect_error) {
    die("Connection failed: " . $godaddyDb->connect_error);
}
if ($windowsDb->connect_error) {
    die("Connection failed: " . $windowsDb->connect_error);
}

// Query to get records with mismatch
$query = "SELECT pub_id FROM inw_inward_dtl WHERE stage_with = 'TRANSFORMA'";
$godaddyResult = $godaddyDb->query($query);

// Check for query errors
if (!$godaddyResult) {
    die("Query failed: " . $godaddyDb->error);
}

$mismatchedArticles = [];
while ($godaddyRow = $godaddyResult->fetch_assoc()) {
    $pub_id = $godaddyRow['pub_id'];
    
    // Avoid multiple queries for the same article
    $windowsResult = $windowsDb->query("SELECT pub_id FROM inw_inward_dtl WHERE pub_id = '$pub_id' AND status= 'Client Review' and stage NOT LIKE '%ISSC%'");
    
    // Check for query errors
    if (!$windowsResult) {
        die("Query failed: " . $windowsDb->error);
    }
    
    $wind_num = $windowsResult->num_rows;
    if ($wind_num > 0) {
        // Collect article IDs with mismatches
        $mismatchedArticles[] = $pub_id;
    }
}

// If there are mismatches, print them
if (!empty($mismatchedArticles)) {
    echo "Mismatched Articles: " . implode(', ', $mismatchedArticles);
} else {
    echo "No mismatched articles found.";
}


		include("smtpmail/phpmailer/class.phpmailer.php");


		$mail_from = "pm@transforma.in";
		$mail_pass = "Fay90556";

		// If there are mismatches, prepare and send the email
if (!empty($mismatchedArticles)) {
    $subject = "TSP Mismatch Detected Between Xperttrack and Internal Workflow - (Date: " . date('d-m-Y H:i:s') . ")";
    
    $body = 'Dear Team,<br/><br/>
    We have identified mismatched records between the Xperttrack Platform and the Internal Workflow system for processing articles.<br/><br/>
    Below is the list of articles with mismatched statuses between both systems:<br/><br/>
    <table style="border-collapse: collapse;">
    <tr bgcolor="#05c2ff" style="border: 1px solid #F4B083;">
    <th style="word-wrap: break-word;height:25px;width:100px;text-align:center;color:white;font-family:Arial;font-size: 14px;border: 1px solid #F4B083;">Articles:</th>
    </tr>';

    foreach ($mismatchedArticles as $pub_id) {
        $body .= '<tr bgcolor="#e1e1e1" style="border: 1px solid #F4B083;">
        <td style="color:#0080ff; border: 1px solid #F4B083;">' . $pub_id . '</td>
        </tr>';
    }

    $body .= '</table><br/><br/>
    If you have any questions or need further details, please don’t hesitate to contact us.<br/><br/><br/>
    Kind regards,<br/><br/>
   Transforma Tech Team<br/><br/>';

//echo $body;

		$to = "marimuthu@transforma.in";
		$cc="";
		$bcc="";
		
		//$cc='pm@transforma.in,viji@transforma.in';
		//$bcc="murali@transforma.in,marimuthu@transforma.in,inward@transforma.in,dhamodaran@transforma.in";
		
        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host = "outlook.office365.com";
        $mail->SMTPAuth = true;
        $mail->Port = 587;
        $mail->Username = $mail_from;
        $mail->Password = $mail_pass;
        $mail->SMTPSecure = 'tls';
        $mail->From = $mail_from;
        $mail->FromName = $mail_from;
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = ""; // optional, comment out and test

		//// Send mail to more than one user ///
		$mail_to 		= explode(',',$to);
		foreach($mail_to as $email)
		{
			if($email!='')
			{
				$mail->addAddress($email);
			}
		}
		
		
		
		$mail_tocc 		= explode(',',$cc);
		foreach($mail_tocc as $email)
		{
			if($email!='')
			{
				$mail->AddCC($email);
			}
		}
		$mail_tobcc 		= explode(',',$bcc);
		foreach($mail_tobcc as $email)
		{
			if($email!='')
			{
				$mail->AddCC($email);
			}
		}
		
        if (!$mail->Send()) {

            echo "Mailer Error: " . $mail->ErrorInfo;
        } else {
            echo "Message sent!";


        }
		} else {
    echo "No mismatched articles found.";
}
// Close database connections
$godaddyDb->close();
$windowsDb->close();

?>
