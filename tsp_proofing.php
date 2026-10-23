<?php
	
	/////// Insert 	file details in inw_proof_dtl table /////////
	
	$project_id  = $_POST['id'];
	
	$password = randomPassword(12);	
	$spassword = randomPassword(4);
	$status = "1";
	$sent_date = date('Y-m-d H:i:s');
	$stage = $_POST['stage'];
	$sent_by = $_COOKIE['user_id'];
	$sent_on = date('Y-m-d H:i:s');
	
	
	
	$dispat_qry = "INSERT INTO `inw_proof_dtl` (`project_id`, `password`, `spassword`, `status`, `sent_date`, `stage`, `sent_by`, `sent_on`) VALUES ('".$project_id."', '".$password."', '".$spassword."', '".$status."', '".$sent_date."', '".$stage."', '".$sent_by."', '".$sent_on."')";
	$db->query($dispat_qry);
	$pd_id=mysqli_insert_id($db);
	
	$update_rmeinderm=mysqli_query($db,"update inw_inward_dtl set reminder_mail='".$_POST['reminder_mail']."'  where id='".$project_id."'");
	
	$journal_result=mysqli_fetch_array(mysqli_query($db,"select * from adm_journals where j_id='".$_POST['j_id']."' "));
	$Date =date('Y-m-d');
	$r1=$journal_result['j_reminder_1'];
	$r2=$journal_result['j_reminder_2'];
	$r3=$journal_result['j_reminder_3'];
	
	if($journal_result['reminder_mail_to']=='A')
	{
		$name=$app_res['corresponding_author'];
		//$email=$app_res['corresponding_mail'];
		$email='tec0304@transforma.co';
	}
	else
	{
		$name='Publisher';
		//$email=$app_res['j_to_email'];	
		$email='techsupport@transforma.in';
	}
	
	
		for($i=1;$i<=3;$i++)
		{
			if($i==1)
			{
		$reminder_date=date('Y-m-d', strtotime($Date. ' + '.$r1.' days'));
			}
		elseif($i==2)
			{
		$reminder_date=date('Y-m-d', strtotime($Date. ' + '.$r2.' days'));
			}
		elseif($i==3)
			{
		$reminder_date=date('Y-m-d', strtotime($Date. ' + '.$r3.' days'));
			}
		$insert_remindermail=mysqli_query($db,"insert into article_client_reminder(`inw_id`,`pd_id`,`reminder_date`,`status`,`name`,`email`,`created_on`) values ('".$project_id."','".$pd_id."','".$reminder_date."','1','".$name."','".$email."','".date('Y-m-d H:i:s')."')");
		}

/////// Send proofing link to author ///////
	
$mail_from 		= $_POST['txtFrom'] ;
$mail_from_name	= "";


$mail_to_name 	= "";

date_default_timezone_set('Asia/Kolkata');
$body = $_POST['txtBody'];
$Subject = $_POST['txtSub'];

$body = str_replace('##AuthorNames##',$_POST['txtToName'],$body);
$body = str_replace('##FileName##',$_POST['article_id'],$body);

/// Get the Client Name ////
	$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = ".$app_res['cust_id'].";";
	$customers = $db->query($query) or ($db->error);
	$customer_info = $customers->fetch_assoc();
	
	
	/// cust_id 42 is TSP ///
if($app_res['cust_id'] != 42)
{
	$body = str_replace('##HOSTNAME##',Proofing_URL."/".$customer_info['cust_name']."/fsp?fs",$body);
}
else
{
	$body = str_replace('##HOSTNAME##',Proofing_URL."/fsp?fs",$body);
	
}

$body = str_replace('##URLPASSWORD##',$spassword."1".$password,$body);
$Subject = str_replace('##FileName##',$_POST['article_id'],$Subject);




include("smtpmail/phpmailer/class.phpmailer.php");

$mail = new PHPMailer();
$mail->IsSMTP();
$mail->CharSet = 'UTF-8';
$mail->Host = "outlook.office365.com";
$mail->SMTPAuth= true;
$mail->Port = 587;
$mail->Username= $mail_from;
$mail->Password= $_POST['txtMpass'];
$mail->SMTPSecure = 'tls';
$mail->From = $mail_from;
$mail->FromName= $mail_from;
$mail->isHTML(true);
$mail->Subject = $Subject;
$mail->Body = $body;

//// Send mail to more than one user ///
$mail_to 		= explode(',',$_POST['txtTo']);
foreach($mail_to as $email)
{
	if($email!='')
	{
		$mail->addAddress($email);
	}
}
/////////// End here send mail ////////


$mail->AltBody    = ""; // optional, comment out and test

$mail_tocc 		= explode(',',$_POST['txtCc']);
foreach($mail_tocc as $email)
{
	if($email!='')
	{
		$mail->AddCC($email);
	}
}

$mail_tobcc 		= explode(',',$_POST['txtBcc']);
foreach($mail_tobcc as $email)
{
	if($email!='')
	{
		$mail->AddBCC($email);
	}
}

		
if(!$mail->Send()) {
  echo "Mailer Error: " . $mail->ErrorInfo;
} else {
   echo "Message sent!";
}

?>