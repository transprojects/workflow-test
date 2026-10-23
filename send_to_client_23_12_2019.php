<?php 
include ('dbc.php'); 
page_protect();



 function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
}


function randomPassword($len) {
    $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < $len; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
}

$dispatch_method = array(1=>'FTP upload &amp; mail',2=>'Only FTP &amp; manual mail',3=>'Attach file &amp; mail',4=>'xProof 
upload &amp; mail');

if(isset($_POST['action']) && $_POST['action']=='update')
{
	foreach($_POST as $key => $value) {
		$post[$key] = $value;
	}
	
	$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$post['id']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$app_res = $result->fetch_assoc();		
	}
	
	$set_status = "`status` ='Client Review',";
	if($_POST['stage']=='FIN')
	{
		$set_status = "`status` ='Completed',";
	}
		
	$prj_update_query="UPDATE inw_inward_dtl SET ".$set_status." `department_id` ='0',`assign_department_id` ='0' WHERE id = ".$post['id']." ";
		
	//echo $prj_update_query; exit;
	$db->query($prj_update_query);
	
///////////////////////////////////////////////////
/////// Upload the files to Server START HERE /////
///////////////////////////////////////////////////

// get FTP access parameters

if($_POST['optionRad']==1 || $_POST['optionRad']==2)
{

$current_stage = preg_replace('/[0-9]+/', '', $_POST['stage']);



$host = $_POST['FtpHost'];

$user = $_POST['FtpUser'];

$pass = $_POST['FtpPass'];

$destDir = $_POST['FtpPath'];

$workDir = "uploads"; // define this as per local system

// get temporary file name for the uploaded file

$tmpName = basename($_FILES['fileAttachment']['name']);

// copy uploaded file into current directory
if($_FILES['fileAttachment']['name'] !="")
{
move_uploaded_file($_FILES['fileAttachment']['tmp_name'], $workDir."/".$tmpName) or die("Cannot move uploaded file to working directory");
}

// open connection

$conn = ftp_connect($host) or die ("Cannot initiate connection to host");

// send access parameters

ftp_login($conn, $user, $pass) or die("Cannot login");


///// Create DIR ///
if($current_stage =='FP')
{
	$destDir .= "/Firstproofs/";
}
else if($current_stage =='REV')
{
	$destDir .= "/Revises/";	
}
else
{
	$destDir .= "/".$current_stage."/";	
}

// try to create directory $dir
if (ftp_mkdir($conn, $destDir))
{
	echo "Successfully created $destDir";
}
else
{
	echo "Error while creating $destDir";
}

///// Create Month and Year DIR ///

$destDir .= date('Y')."/";		

// try to create directory $dir
if (ftp_mkdir($conn, $destDir))
{
	echo "Successfully created $destDir";
}
else
{
	echo "Error while creating $destDir";
}

///// Create Month and DATE DIR ///

$destDir .= date('md');	

// try to create directory $dir
if (ftp_mkdir($conn, $destDir))
{
	echo "Successfully created $destDir";
}
else
{
	echo "Error while creating $destDir";
}
////// END Create DIR ////
$email_ftp_path ='';

// perform file upload
if($_FILES['fileAttachment']['name'] !="")
{
	$upload = ftp_put($conn, $destDir."/".$_FILES['fileAttachment']['name'], $workDir."/".$tmpName, FTP_BINARY);
	
	$email_ftp_path .= "ftp://".$user."@".$host.$destDir."/".$_FILES['fileAttachment']['name']."<br>";	
	// delete local copy of uploaded file
	unlink($workDir."/".$tmpName) or die("Cannot delete uploaded file from working directory — manual deletion recommended");
}

foreach($_POST['attach_files'] as $file)
{
	$upload = ftp_put($conn, $destDir."/".$file, $_POST['work_dir']."/".$file, FTP_BINARY);
	
	$email_ftp_path .= "ftp://".$user."@".$host.$destDir."/".$file."<br>";
	
}

//var_dump($upload);

// check upload status

// display message

if (!$upload) {

    echo "Cannot upload to FTP";

} else {

    echo "Upload complete";

}


// close the FTP stream

ftp_close($conn);




}

//exit;
////////////////////////////////////////
////// End files upload to server //////
////////////////////////////////////////			
			
			
////// send mail to clinet with attachment ////
/* $smtp_host 		= "ssl://smtp.gmail.com";
$smtp_user 		= "trans.robatic.msg@gmail.com";
$smtp_password 	= "Technet16";
$smtp_port 		= "465";
$smtp_ssl 		= "true"; 

$smtp_host 		= "smtp.office365.com";
$smtp_port 		= "587";
$smtp_ssl 		= "false";
$smtp_user 		= $_POST['txtFrom'];
$smtp_password 	= $_POST['txtMpass']; */

if($_POST['optionRad']==1 || $_POST['optionRad']==3 )
{
	
$mail_from 		= $_POST['txtFrom'] ;
$mail_from_name	= "";


$mail_to_name 	= "";

date_default_timezone_set('Asia/Kolkata');

$body = str_replace('ARTICLEID',$_POST['article_id'],$_POST['txtBody']);
$body = str_replace('CLIENTNAME',$_POST['cust_name'],$body);
//echo $app_res['cust_id'];exit;
if($app_res['cust_id']==39)
{
$body = str_replace('FTPFILEPATH',$destDir."/".$_FILES['fileAttachment']['name'],$body);
}
else
{
$body = str_replace('FTPFILEPATH',$email_ftp_path,$body);
}

	$querysrc = $_POST['work_dir'];
	$querystr = "";
	$i = new DirectoryIterator($querysrc);
    foreach($i as $f) {
        if($f->isFile()) {
           
			$filename = $f->getFilename();
			if (strpos($filename, '_Query.txt') !== false) 
			{
					$file = fopen($querysrc.$filename,"r");
					while(! feof($file))
					{
						$querystr .= str_replace("\n","<br/>", fgets($file));
						
					}
					$querystr = "---------- Query ----------<br/>".$querystr."<br/><br/>---------------------------<br/>";
				break;
			}

		}
	}
	
	$body = str_replace('AUTHORQUERY',$querystr,$body);
//exit;




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
$mail->Subject = str_replace('ARTICLEID',$_POST['article_id'],$_POST['txtSub']);
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

		if($_POST['optionRad']==3 || $_POST['optionRad']==4)
		{
			// $mail->AddAttachment($_POST['work_dir']."Rouzies Onyemah final draft (2).pdf");      // attachment
			if($_FILES['fileAttachment']['name'] !="")
			{
				$mail->AddAttachment($_FILES['fileAttachment']['tmp_name'],$_FILES['fileAttachment']['name']);
			}
			//$mail->AddAttachment("images/phpmailer_mini.gif"); // attachment
			foreach($_POST['attach_files'] as $file)
			{
				$mail->AddAttachment($_POST['work_dir'].$file);
			}
		}

if(!$mail->Send()) {
  echo "Mailer Error: " . $mail->ErrorInfo;
} else {
   echo "Message sent!";
}
	//exit;
}




if($_POST['optionRad']==4)
{
	
	/////// Insert 	file details in inw_proof_dtl table /////////
	
	$project_id  = $_POST['id'];
	
	$password = randomPassword(12);	
	$spassword = randomPassword(4);
	$status = "1";
	$sent_date = date('Y-m-d H:i:s');
	$stage = $_POST['stage'];
	$sent_by = $_SESSION['user_id'];
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
}

///// Add records in Artile Dispatch history ///

$dispat_qry = "INSERT INTO `inw_dispatch_history` (`cust_id`, `j_id`, `a_id`, `from_mail`, `to_mail`, `mail_text`, `sent_by`, `stage`, `sent_date`,dispatch_type) VALUES ('".$app_res['cust_id']."', '".$app_res['j_id']."', '".$app_res['id']."', '".$_POST['txtFrom']."', '".$_POST['txtTo']."', '".$body."', '".$_SESSION['user_id']."', '".$app_res['stage']."', NOW(),'".$dispatch_method[$_POST['optionRad']]."')";
$db->query($dispat_qry);
	

	header("location:projects.php?msg=Successfully completed"); 
}
/////////////// Update completed ///////////////

if(isset($_REQUEST['id']))
{
	$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$_REQUEST['id']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$app_res = $result->fetch_assoc();
		$app_res['volume_folder'] = $app_res['volume_folder'].$app_res['issueorgroup_folder'];
	}

/// Get the Client Name ////
	$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = ".$app_res['cust_id'].";";
	$customers = $db->query($query) or ($db->error);
	$customer_info = $customers->fetch_assoc();
	
/// Get the journal name ///
	$query = "SELECT * FROM `adm_journals` WHERE j_id = ".$app_res['j_id'].";";
	$journal = $db->query($query) or ($db->error);
	$journal_info = $journal->fetch_assoc();
	
	/// Get Email Subject and Body ///
	$query = "SELECT * FROM `inw_dispatch_mail_master` WHERE j_id = ".$app_res['j_id']." AND cust_id=".$app_res['cust_id']." AND stage ='".preg_replace('/[0-9]+/', '', $app_res['stage'])."';";
	$d_mail = $db->query($query) or ($db->error);
	$d_mail_info = $d_mail->fetch_assoc();
	
	
}

//echo $transaction_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
<meta charset="utf-8" />
<title>Production - Workflow</title>
<meta name="description" content="3 styles with inline editable feature" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

<!-- bootstrap & fontawesome -->
<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

<!-- page specific plugin styles -->
<link rel="stylesheet" href="assets/css/richtext.min.css" />
<!-- text fonts -->
<link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />

<!-- ace styles -->
<link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />

<!--[if lte IE 9]>
			<link rel="stylesheet" href="assets/css/ace-part2.min.css" class="ace-main-stylesheet" />
		<![endif]-->
<link rel="stylesheet" href="assets/css/ace-skins.min.css" />

<!--[if lte IE 9]>
		  <link rel="stylesheet" href="assets/css/ace-ie.min.css" />
		<![endif]-->

<!-- inline styles related to this page -->

<!-- HTML5shiv and Respond.js for IE8 to support HTML5 elements and media queries -->

<!--[if lte IE 8]>
		<script src="assets/js/html5shiv.min.js"></script>
		<script src="assets/js/respond.min.js"></script>
		<![endif]-->

</head>

<body class="no-skin">
<!-- .navbar-container -->
<?php include("includes/header.php"); ?>
<!-- /.navbar-container -->

<div class="main-container " id="main-container"> 
  <!-- .sidebar-shortcuts -->
  <?php include("includes/left_sidebar.php"); ?>
  <!-- /.sidebar-shortcuts -->
  
  <div class="main-content">
    <div class="main-content-inner">
      <div class="breadcrumbs " id="breadcrumbs">
        <ul class="breadcrumb">
          <li> <i class="ace-icon fa fa-home home-icon"></i> <a href="#">Home</a> </li>
          <li> <a href="#">Project</a> </li>
          <li class="active">Working on <?php echo $app_res['article_title']; ?></li>
        </ul>
        <!-- /.breadcrumb -->
        
        <div class="nav-search" id="nav-search">
          <form class="form-search">
            <span class="input-icon">
            <input type="text" placeholder="Search ..." class="nav-search-input" id="nav-search-input" autocomplete="off" />
            <i class="ace-icon fa fa-search nav-search-icon"></i> </span>
          </form>
        </div>
        <!-- /.nav-search --> 
      </div>
      <div class="page-content">
        <div class="page-header">
          <h1> <?php echo $customer_info['cust_name']; ?> : <?php echo $journal_info['j_name']; ?> </h1>
        </div>
        <!-- /.page-header -->
        
        <div class="row">
          <div class="col-xs-12"> 
            <!-- PAGE CONTENT BEGINS -->
            <div class="widget-box">
              <div class="widget-header widget-header-blue widget-header-flat">
                <h4 class="widget-title lighter">Project Dispatch</h4>
              </div>
              <div class="widget-body">
                <div class="widget-main">
                  <div id="fuelux-wizard-container">
                    <div class="step-content pos-rel">
                      <div class="step-pane active" data-step="1">
                        <h3 class="lighter block green">Article information</h3>
                        <div class="profile-user-info profile-user-info-striped">
                          <div class="profile-info-row">
                            <div class="profile-info-name"> Customer </div>
                            <div class="profile-info-value"> <span class="editable" id="age"><?php echo $customer_info['cust_name']; ?></span> </div>
                          </div>
                          <div class="profile-info-row">
                            <div class="profile-info-name"> Journal </div>
                            <div class="profile-info-value"> <span class="editable" id="age"><?php echo $journal_info['j_code']; ?></span> </div>
                          </div>
                          <div class="profile-info-row">
                            <div class="profile-info-name"> Article </div>
                            <div class="profile-info-value"> <span class="editable" id="age"><?php echo $app_res['pub_id']; ?></span> </div>
                          </div>
                          <div class="profile-info-row">
                            <div class="profile-info-name"> Received Date </div>
                            <div class="profile-info-value"> <span class="editable" id="city"><?php echo (date('d-m-Y',strtotime($app_res['recv_dt']))); ?></span> </div>
                          </div>
                          <div class="profile-info-row">
                            <div class="profile-info-name"> Due Date </div>
                            <div class="profile-info-value"> <span class="editable" id="login"><?php echo (date('d-m-Y',strtotime($app_res['due_dt']))); ?></span> </div>
                          </div>
                          <div class="profile-info-row">
                            <div class="profile-info-name"> Insructions </div>
                            <div class="profile-info-value"> <span class="editable" id="login"> <a href="#" class="green bigger-140 show-details-btn" title="Show Details"> <i class="ace-icon fa fa-angle-double-down"></i> <span class="">View Instructions</span> </a> </span><br>
                              <div id="message-content" style="display:none;" class="message-body ace-scroll" style="position: relative;">
                              <div class="hr hr-double"></div>
                              <div class="scroll-content" style="max-height: 150px; overflow:scroll;"> <?php echo $app_res['instructions']; ?> </div>
                              <div class="hr hr-double"></div>
                            </div>
                          </div>
                        </div>
                        <div class="profile-info-row">
                          <div class="profile-info-name"> Department Comments </div>
                          <div class="profile-info-value"> <span class="editable" id="login">
                            <?php
	$di_query="SELECT comments FROM `inw_dept_comments`  WHERE dept_ids IN (".$app_res['department_id'].") ";
    $di_result = $db->query($di_query); 	
   
    $di_count=$di_result->num_rows;
    if($di_count>0)
    {
   	 	$di_res = $di_result->fetch_assoc();
		echo $di_res['comments'];
    }
    ?>
                            </span> </div>
                        </div>
                      </div>
                      <div class="space-20"></div>
                      <form class="form-horizontal" id="validation-form" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                          <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Choose Dispatch Method :</label>
                          <div class="col-xs-12 col-sm-9">
                            <input id="optionRad_0" name="optionRad" value="1" onclick="javascript:setTimeout('__doPostBack(\'optionRad$0\',\'\')', 0)" type="radio" checked="checked">
                            <label for="optionRad_0">FTP upload &amp; mail</label>
                            </td>
                            <td><input id="optionRad_1" name="optionRad" value="4" onclick="javascript:setTimeout('__doPostBack(\'optionRad$1\',\'\')', 0)" type="radio">
                              <label for="optionRad_1">xProof upload &amp; mail</label></td>
                            <td><input id="optionRad_2" name="optionRad" value="2" onclick="javascript:setTimeout('__doPostBack(\'optionRad$2\',\'\')', 0)" type="radio">
                              <label for="optionRad_2">Only FTP &amp; manual mail</label></td>
                            <td><input id="optionRad_3" name="optionRad" value="3" onclick="javascript:setTimeout('__doPostBack(\'optionRad$3\',\'\')', 0)" type="radio">
                              <label for="optionRad_3">Attach file &amp; mail</label>
                          </div>
                        </div>
                        <div id="div1">
                          <div class="page-header">
                            <h1> FTP </h1>
                          </div>
                          <div class="row">
                            <div class="col-xs-12">
                              <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right">Site URL</label>
                                <div class="col-sm-9">
                                  <input name="FtpHost" type="text"  class="col-sm-6 required" value="<?php echo $journal_info['ftp_send_host']; ?>" />
                                </div>
                              </div>
                              <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right">Target DIR</label>
                                <div class="col-sm-9">
                                  <input name="FtpPath" type="text"  class="col-sm-6 required" value="<?php echo $journal_info['ftp_send_dir']; ?>"/>
                                  Note: No slash end of the path </div>
                              </div>
                              <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right">User</label>
                                <div class="col-sm-9">
                                  <input name="FtpUser" type="text"  class="col-sm-6 required" value="<?php echo $journal_info['ftp_send_user']; ?>" />
                                </div>
                              </div>
                              <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right">Password</label>
                                <div class="col-sm-9">
                                  <input name="FtpPass" type="password"  class="col-sm-6 required" value="<?php echo $journal_info['ftp_send_password']; ?>"/>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div  id="emaildiv">
                          <div class="page-header">
                            <h1> E-Mail </h1>
                          </div>
                          <div class="row">
                            <div class="col-xs-12">
                              <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right">From</label>
                                <div class="col-sm-9">
                                  <input name="txtFrom" type="email"  class="col-sm-6 required" value="<?php echo $journal_info['j_from_email']; ?>"/>
                                </div>
                              </div>
                              <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right">Password</label>
                                <div class="col-sm-9">
                                  <input name="txtMpass" type="password"  class="col-sm-6 required" value="<?php echo $journal_info['j_from_email_pwd']; ?>" />
                                </div>
                              </div>
                              <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right">To</label>
                                <div class="col-sm-9">
									
									<?php if($app_res['cust_id'] == 42 || $app_res['cust_id'] == 43)
									{
									?>
										<input name="txtTo" type="text"  class="col-sm-6 required" value="<?php echo $app_res['corresponding_mail']; ?>"/>
									<?php 
									}
									else
									{
										?>
										<input name="txtTo" type="text"  class="col-sm-6 required" value="<?php echo $journal_info['j_to_email']; ?>"/>
									<?php	
									}
									?>
                                  
                                  Note- Add the Email ids with comma seperated. </div>
                              </div>
                              <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right">CC</label>
                                <div class="col-sm-9">
                                  <input name="txtCc" type="text"  class="col-sm-6" value="<?php echo $journal_info['j_cc_email']; ?>"/>
                                  Note:- Add the Email ids with comma seperated. </div>
                              </div>
                              <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right">BCC</label>
                                <div class="col-sm-9">
                                  <input name="txtBcc" type="text"  class="col-sm-6" value="<?php echo $journal_info['j_bcc_email']; ?>" />
                                  Note:- Add the Email ids with comma seperated. </div>
                              </div>							  
                              <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right">Author Name</label>
                                <div class="col-sm-9">
                                 <input name="txtToName" type="text"  class="col-sm-6" value="<?php echo $app_res['corresponding_author']; ?>"/>
                                  Note:- Corresponding Author Name fetched from article.</div>
                              </div>
                              <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right">Subject</label>
                                <div class="col-sm-9">
                                  <input name="txtSub" type="text"  class="col-sm-6 required" value="<?php echo $d_mail_info['subject']?>"/>
                                </div>
                              </div>
                              <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right">Message</label>
                                <div class="col-sm-9">
                                  <textarea name="txtBody" id="txtBody" type="text"  class="col-sm-8 required" ><?php echo $d_mail_info['message']?></textarea>
                                </div>
                              </div>
                              <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right">Attachment</label>
                                <div class="col-sm-9">
                                  <input name="fileAttachment" type="file" id="image-file" class="col-sm-6 " />
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="space-2"></div>
                        <?php
	//// Get Prev dept name /////
	$presult = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$app_res['department_id'].";"); 	
	$ptoatl_count=$presult->num_rows;
	$pdept_res = array();
	if($ptoatl_count>0)
	{
		$pdept_res = $presult->fetch_assoc();
	}
						?>
                        <div class="form-group">
                          <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Work DIR:</label>
                          <div class="col-xs-12 col-sm-9">
								<?php
							$work_dir = $app_res['work_dir'];
							$work_dir = str_replace(WF_DIR,TOOL_DIR,$work_dir);
						
							?>
                            <div class="clearfix"> <a class="btn btn-primary" href="file:///<?php echo $work_dir.$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/".$pdept_res['dept_name']."/"; ?>" target="_blank"><i class="ace-icon fa fa-folder align-top bigger-125"></i> View</a> </div>
                          </div>
                        </div>
                        <div class="space-2"></div>
                        <div class="form-group">
                          <label class="control-label bolder blue control-label col-xs-12 col-sm-3 no-padding-right">Check the files to send</label>
                          <div class="checkbox col-xs-12 col-sm-9">
                            <?php
								// Open the source directory to read in files
								$src = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/".$pdept_res['dept_name']."/";
								if(is_dir($src))
								{
    $i = new DirectoryIterator($src);
    foreach($i as $f) {
        if($f->isFile()) 
		{
            $filename = $f->getFilename();
			// $filesize = filesize($src."/".$f);
			$fsize = filesize($src."/".$f);
			$filesize = formatSizeUnits($fsize);
			?>
                            <div class="row">
                              <label>
                                <?php
														if (strpos($filename, '.zip') !== false) 
														{
													?>
                                <input name="attach_files[]" class="ace ace-checkbox-2" data-size="<?php echo  $fsize; ?>" value="<?php echo  $filename; ?>" type="checkbox" checked="checked"/>
                                <?php
														}
														else	
														{
													?>
                                <input name="attach_files[]" class="ace ace-checkbox-2" data-size="<?php echo  $fsize; ?>" value="<?php echo  $filename; ?>" type="checkbox" />
                                <?php
														}
														
													?>
                                <span class="lbl"> <?php echo  $filename; ?> </span> <span class="lbl"> <b>(<?php echo  $filesize; ?>)</b> </span> </label>
                            </div>
                            <?php
			
			
        } 
		
    }
	}
								?>
                          </div>
                        </div>
                        <div class="space-2"></div>
                        <div class="form-group" id="total_file_size_div">
                          <label class="control-label bolder blue control-label col-xs-12 col-sm-3 no-padding-right">Total selected files size</label>
                          <div class="checkbox col-xs-12 col-sm-9">
                            <label> <b><span class="lbl" id="total_file_size"></span></b> </label>
                          </div>
                        </div>
						
												                            <div class="hr hr-dotted"></div>

						  <div class="space-2"></div>

						 <div class="form-group" id="reminder" style="display:none;">
                                <label class="col-sm-3 control-label no-padding-right">Need Reminder Mail ?</label>
                                <div class="col-sm-9">
                                <label>  <input name="reminder_mail" type="checkbox"  id="reminder_mail" class="ace ace-checkbox-2" value="1" /><span class="lbl"></span></label>
                                </div>
                          </div>
                        <div class="form-group">
                          <div class="col-xs-12 col-sm-9 center">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?php echo $_REQUEST['id']; ?>" />
                            <input type="hidden" name="fileattach" id="fileattach" value="0" />
                            <input type="hidden" name="article_id" value="<?php echo $app_res['pub_id']; ?>" />
							<input type="hidden" name="cust_id" value="<?php echo $customer_info['id']; ?>" />
							<input type="hidden" name="cust_name" value="<?php echo $customer_info['cust_name']; ?>" />
							<input type="hidden" name="j_code" value="<?php echo $journal_info['j_code']; ?>" />
							<input type="hidden" name="j_id" value="<?php echo $journal_info['j_id']; ?>" />
                            <input type="hidden" name="stage" value="<?php echo $app_res['stage']; ?>" />
                            <input type="hidden" name="cust_name" value="<?php echo $customer_info['cust_name']; ?>" />
                            <input type="hidden" name="work_dir" value="<?php echo $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/".$pdept_res['dept_name']."/"; ?>">
                            <!--<a href="projects.php" class="btn btn-grey">Cancel</a>-->
							<input type="button" value="Cancel" onClick="window.history.go(-1);" class="btn btn-grey"/>
                            <button type="submit" value="Save" id="saver" class="btn btn-info" name="submit">Send</button>
                          </div>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
              <!-- /.widget-main --> 
            </div>
            <!-- /.widget-body --> 
          </div>
          <!-- PAGE CONTENT ENDS --> 
        </div>
        <!-- /.col --> 
      </div>
      <!-- /.row --> 
    </div>
    <!-- /.page-content --> 
  </div>
</div>
<!-- /.main-content -->

<?php include("includes/footer.php"); ?>
<a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse"> <i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i> </a>
</div>
<!-- /.main-container --> 

<!-- basic scripts --> 

<!--[if !IE]> -->
		<script src="assets/js/jquery-2.1.4.min.js"></script>

		<!-- <![endif]--> 

<!--[if IE]>
<script src="assets/js/jquery-1.11.3.min.js"></script>
<![endif]--> 
<script type="text/javascript">
			if('ontouchstart' in document.documentElement) document.write("<script src='assets/js/jquery.mobile.custom.min.js'>"+"<"+"/script>");
		</script> 
<script src="assets/js/bootstrap.min.js"></script> 

<!-- page specific plugin scripts --> 
<script src="assets/js/jquery.richtext.min.js"></script> 
<script src="assets/js/jquery.validate.min.js"></script> 

<!-- ace scripts --> 
<script src="assets/js/ace-elements.min.js"></script> 
<script src="assets/js/ace.min.js"></script> 
<script src="assets/js/bootbox.js"></script> 


<!-- inline scripts related to this page --> 
<script type="text/javascript">
			jQuery(function($) {				
				$('#validation-form').validate({errorClass: 'help-block'});		
				$('#txtBody').richText();//this will create the default editor will all buttons	
			})
			
	
			
   $('#validation-form').submit(function() {
	  
	   var article_id = $("input[name='article_id']").val();
	   var cust_name = $("input[name='cust_name']").val();
	   var j_code = $("input[name='j_code']").val();
	   var j_id = $("input[name='j_id']").val();
	   var cust_id = $("input[name='cust_id']").val();
	   var stage = $("input[name='stage']").val();
	  

	var returnval=true;
    $.ajax({
        url: 'main_sample.php',
	    async : false,
        type: 'POST',
        data:{article_id: article_id, cust_name:cust_name, j_code:j_code,j_id:j_id, cust_id:cust_id, stage:stage},
        context: this,
        success: function(result) {
			// alert(result);
                if (result == 1)
				{
				returnval=true;
                this.submit();
                } 
			    else if(result == 0)
				{
                returnval=false;
                alert("No Proper File Name | Text Name  Or Modify Configuration");
                } 
				else if(result == 2)
				{
                returnval=false;
                alert("Sub Folder Must Contain Files Or Modify Configuration.");
                }
				else if(result == 3)
				{
                returnval=false;
                alert("Folder Must Not Contain Zip Or Modify Configuration.");
                }
				else if(result == 7)
				{
                returnval=true;;
				}
				else
				{
				returnval=true;
				}
			
        }
    });
   return returnval;	
	
});		
	
		</script> 
		
<script type="text/javascript">
		$(function(){
			
			
			$("input[id$='optionRad_0']").click(function(){
			var re=$(this).val();
			$("#fileattach").val(0);
			$("#div1,#emaildiv").show();
			$("#reminder").hide();
			$('#image-file').val('');
			
		});
		
		
		$("input[id$='optionRad_1']").click(function(){
			var tog=$(this).val();
			$("#div1").hide();
			$("#reminder").show();
		    $("#fileattach").val(0);
			$("#emaildiv").show();
			$('#image-file').val('');
			
		});
	
		
		
		$("input[id$='optionRad_2']").click(function(){
			var mail=$(this).val();
			$("#emaildiv").hide();
			$("#fileattach").val(0);
			$("#div1").show();
			$("#reminder").hide();
		});
		
		
		$("input[id$='optionRad_3']").click(function(){
			var mail=$(this).val();
			$("#fileattach").val(3);
			$("#div1").hide();
			$("#emaildiv").show();
			$('#image-file').val('');
			$("#reminder").hide();
						
	 	$("#image-file").bind('change', function() {
				var fat=$("#fileattach").val();
				if(fat==3)
				{
                 var res= this.files[0].size/1024/1024;
			     if (res > 20) {
                 alert('File size exceeds 20 MB Cannot Accept');
                 $('#image-file').val(''); //for clearing with Jquery
                   }     
				}
	        });
        });
		$("#saver").click(function()
		{
			var fat=$("#fileattach").val();
		   	if(fat==3)
				{
		  var x;
    x = document.getElementById("image-file").value;
    if (x == "") {
        alert("Attachment is Empty Cannot Send");
        return false;
    };
		
	    };
			
		});
		
		<?php
			if($journal_info['ftp_send'] == 1 && $journal_info['j_email_send'] == 1)
			{
		?>
				$("input[id$='optionRad_0']").click();
		<?php
			}
			else if($customer_info['id'] == 42 || $customer_info['id'] == 43)
			{
		?>
				$("input[id$='optionRad_1']").click();
		<?php
			}
		
			else if($journal_info['ftp_send'] == 1)
			{
		?>
				$("input[id$='optionRad_2']").click();
		<?php		
			}
			else if($journal_info['j_email_send'] == 1)
			{
		?>
				$("input[id$='optionRad_3']").click();
		<?php		
			}
			?>
		$("#total_file_size_div").hide();
		$('.ace-checkbox-2').click(function(){
			$total_checked_file_size =0;
			$(".ace-checkbox-2:checked").each(function() {
				$total_checked_file_size += parseInt($(this).attr("data-size"));
			});
			if($total_checked_file_size != 0 )
			{
				$("#total_file_size_div").show();
				$fsize = formatSizeUnits($total_checked_file_size);				
				if( $total_checked_file_size > 1048576*20)
				{
					bootbox.dialog(
					{
						message: '<br/><div class="alert alert-danger"> <i class="ace-icon fa fa-exclamation-triangle"></i> Total file size is more than <b>20MB</b>. Please check and send mail.</div> ' 
					});
					$(this).prop('checked', false);
					return false;
				}				
				$("#total_file_size").html($fsize);
			}
			else
			{
				$("#total_file_size_div").hide();
			}
		});
	
		});
		$(window).load(function() {
			$total_checked_file_size  = 0;
			$temp_file_size = 0;
    	  $(".ace-checkbox-2:checked").each(function() {
			  $temp_file_size += parseInt($(this).attr("data-size"));				
				if( $temp_file_size > 1048576*20)
				{					
					// alert('Hi');
					//$(this).prop('checked', false);
				}
				else
				{
					$total_checked_file_size += parseInt($(this).attr("data-size"));
				}
			});
			if($total_checked_file_size != 0 )
			{
				$("#total_file_size_div").show();
				$fsize = formatSizeUnits($total_checked_file_size);		
		
				if( $temp_file_size > 1048576*20)
				{
					bootbox.dialog(
					{
						message: '<br/><div class="alert alert-danger"> <i class="ace-icon fa fa-exclamation-triangle"></i> Total file size is more than <b>20MB</b>. Please check and send mail.</div> ' 
					});
					$("#total_file_size").html($fsize);				
				}				
			}
			else
			{
				$("#total_file_size_div").hide();
			}
		});	
		
		function formatSizeUnits(bytes){
      if      (bytes>=1073741824) {bytes=(bytes/1073741824).toFixed(2)+' GB';}
      else if (bytes>=1048576)    {bytes=(bytes/1048576).toFixed(2)+' MB';}
      else if (bytes>=1024)       {bytes=(bytes/1024).toFixed(2)+' KB';}
      else if (bytes>1)           {bytes=bytes+' bytes';}
      else if (bytes==1)          {bytes=bytes+' byte';}
      else                        {bytes='0 byte';}
      return bytes;
}

		
		
		</script>
</body>
</html>
