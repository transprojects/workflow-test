<?php
$account="techsupport@transforma.in";
$password="Tr@n$!t2018";
$to="swamy@transforma.in";
$from="techsupport@transforma.in";
$from_name="techsupport";
$msg="Hi Swamy,
			<br/>
			Now emails are working fine.please check it. 
			"; 
$subject="HTML message";


include("phpmailer/class.phpmailer.php");
$mail = new PHPMailer();
$mail->IsSMTP();
$mail->CharSet = 'UTF-8';
$mail->Host = "outlook.office365.com";
$mail->SMTPAuth= true;
$mail->Port = 587;
$mail->Username= $account;
$mail->Password= $password;
$mail->SMTPSecure = 'tls';
$mail->From = $from;
$mail->FromName= $from_name;
$mail->isHTML(true);
$mail->Subject = $subject;
$mail->Body = $msg;
$mail->addAddress($to);
if(!$mail->send()){
 echo "Mailer Error: " . $mail->ErrorInfo;
}else{
 echo "E-Mail has been sent";
}
?>
