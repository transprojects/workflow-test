<?php
include ('dbc.php');
ini_set('max_execution_time', 0);
page_protect();

function formatSizeUnits($bytes) {
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
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

$dispatch_method = array(1 => 'FTP upload &amp; mail', 2 => 'Only FTP &amp; manual mail', 3 => 'Attach file &amp; mail', 4 => 'xProof upload &amp; mail', 5 => 'EProof');


if (isset($_POST['action']) && $_POST['action'] == 'update') {
    foreach ($_POST as $key => $value) {
        $post[$key] = $value;
    }
	
	$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = " . $_REQUEST['id'] . " ");
    $row_cnt = 1;
    $toatl_count = $result->num_rows;
    if ($toatl_count > 0) {
        $app_res = $result->fetch_assoc();
    }
	
		/// Get the Client Name ////
    $query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = " . $app_res['cust_id'] . ";";
    $customers = $db->query($query) or ( $db->error);
    $customer_info = $customers->fetch_assoc();
	
	
			
	
		 

	
	
    $result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = " . $post['id'] . " ");
    $row_cnt = 1;
    $toatl_count = $result->num_rows;
    if ($toatl_count > 0) {
        $app_res = $result->fetch_assoc();
    }
	$query = "SELECT * FROM `adm_journals` WHERE j_id = " . $app_res['j_id'] . ";";
    $journal = $db->query($query) or ( $db->error);
    $journal_info = $journal->fetch_assoc();
    $set_status = "`status` ='Client Review',";
    if ($_POST['stage'] == 'FIN') {
        $set_status = "`status` ='Completed',";
    }

    $prj_update_query = "UPDATE inw_inward_dtl SET " . $set_status . " `department_id` ='0',`assign_department_id` ='0' WHERE id = " . $post['id'] . "";

    //echo $prj_update_query; exit;
    $db->query($prj_update_query);







    if ($_POST['optionRad'] == 4) 
	{

		if($app_res['cust_id'] == 46 || $app_res['j_id'] == 120 )
		{
			include ('aws_test_xpertproofing.php');
		}

        if($app_res['cust_id'] == 66 )
		{
			include ('aws_test_xpertproofing.php');
		}
	}
	
	




///// Add records in Artile Dispatch history ///

    $dispat_qry = "INSERT INTO `inw_dispatch_history` (`cust_id`, `j_id`, `a_id`, `from_mail`, `to_mail`, `mail_text`, `sent_by`, `stage`, `sent_date`,dispatch_type) VALUES ('" . $app_res['cust_id'] . "', '" . $app_res['j_id'] . "', '" . $app_res['id'] . "', '" . $_POST['txtFrom'] . "', '" . $_POST['txtTo'] . "', '" . $body . "', '" . $_COOKIE['user_id'] . "', '" . $app_res['stage'] . "', NOW(),'" . $dispatch_method[$_POST['optionRad']] . "')";
    $db->query($dispat_qry);


    header("location:projects.php?msg=Successfully completed");
}
/////////////// Update completed ///////////////

if (isset($_REQUEST['id'])) {
    $result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = " . $_REQUEST['id'] . " ");
    $row_cnt = 1;
    $toatl_count = $result->num_rows;
    if ($toatl_count > 0) {
        $app_res = $result->fetch_assoc();
        $app_res['volume_folder'] = $app_res['volume_folder'] . $app_res['issueorgroup_folder'];
    }

/// Get the Client Name ////
    $query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = " . $app_res['cust_id'] . ";";
    $customers = $db->query($query) or ( $db->error);
    $customer_info = $customers->fetch_assoc();

/// Get the journal name ///
    $query = "SELECT * FROM `adm_journals` WHERE j_id = " . $app_res['j_id'] . ";";
    $journal = $db->query($query) or ( $db->error);
    $journal_info = $journal->fetch_assoc();

    /// Get Email Subject and Body ///
    $query = "SELECT * FROM `inw_dispatch_mail_master` WHERE j_id = " . $app_res['j_id'] . " AND cust_id=" . $app_res['cust_id'] . " AND stage ='" . preg_replace('/[0-9]+/', '', $app_res['stage']) . "';";
    $d_mail = $db->query($query) or ( $db->error);
    $d_mail_info = $d_mail->fetch_assoc();
	
	/// Changes the Subject and Body Text for NOW Publisher ///
	if($app_res['cust_id'] == 42)
	{
		$d_mail_info['subject'] = str_replace("##FileName##",$app_res['pub_id'],$d_mail_info['subject']);
		$d_mail_info['message'] = str_replace("##FileName##",$app_res['pub_id'],$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##EPROOFING_LINK##",EPROOFING_URL,$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##EPROOFING_KEY##",base64_encode($app_res['id']),$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##AuthorNames##",$app_res['corresponding_author'],$d_mail_info['message']);
		
		//##AuthorNames##
		
	}
		/// Changes the Subject and Body Text for NOW Publisher ///
if($customer_info['cust_name'] == "Sunrise-NOW" || $customer_info['cust_name'] == "TSP")
	{
		$d_mail_info['subject'] = str_replace("##FileName##",$app_res['pub_id'],$d_mail_info['subject']);
		$d_mail_info['message'] = str_replace("##FileName##",$app_res['pub_id'],$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##EPROOFING_LINK##",EPROOFING_URL,$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##EPROOFING_KEY##",base64_encode($customer_info['cust_name'].":". $journal_info['j_code'].":".$app_res['pub_id'].":".$app_res['page_count']),$d_mail_info['message']);
		$d_mail_info['message'] = str_replace("##AuthorNames##",$app_res['corresponding_author'],$d_mail_info['message']);
		
			//##AuthorNames##
		
	}
	
	$sub_mail= $journal_info['j_code']."--".substr($app_res['article_title'],0,40)."... [".$app_res['pub_id']."]";

	
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
						
						<style>
		.loader 
		{
			position: fixed;
			left: 0px;
			top: 0px;
			width: 100%;
			height: 100%;
			z-index: 9999;
			background: url('css/loader.gif') 50% 50% no-repeat rgb(249,249,249);
			opacity: .8;
			z-index: 99999;
		}
	</style>

    </head>

    <body class="no-skin">
        <!-- .navbar-container -->
        <?php include("includes/header.php"); ?>
        <!-- /.navbar-container -->
<div class="loader"></div>

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

        <div class="span12">
        <?php
        if(!empty($_REQUEST['msg']))  {
        echo '<div class="alert alert-danger">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        '. $_REQUEST['msg'] .'</div>';   

        } ?> 
        </div>
      </div>
	  
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
                                                                <div class="profile-info-value"> <span class="editable" id="city"><?php echo (date('d-m-Y', strtotime($app_res['recv_dt']))); ?></span> </div>
                                                            </div>
                                                            <div class="profile-info-row">
                                                                <div class="profile-info-name"> Due Date </div>
                                                                <div class="profile-info-value"> <span class="editable" id="login"><?php echo (date('d-m-Y', strtotime($app_res['due_dt']))); ?></span> </div>
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
                                                                        $di_query = "SELECT comments FROM `inw_dept_comments`  WHERE dept_ids IN (" . $app_res['department_id'] . ") ";
                                                                        $di_result = $db->query($di_query);

                                                                        $di_count = $di_result->num_rows;
                                                                        if ($di_count > 0) {
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
                                                                        <label for="optionRad_3">Attach file &amp; mail</label></td>
																	<td><input id="optionRad_4" name="optionRad" value="5" onclick="javascript:setTimeout('__doPostBack(\'optionRad$4\',\'\')', 0)" type="radio">
                                                                        <label for="optionRad_4">EProofing</label></td>
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

                                                                                <?php
                                                                                if ($app_res['cust_id'] == 42 || $app_res['cust_id'] == 43  || $app_res['cust_id'] == 34 || $app_res['cust_id'] == 37) {
                                                                                    ?>
                                                                                    <input name="txtTo" type="text" id="txtTo"  class="col-sm-6 required" value="<?php echo $app_res['corresponding_mail']; ?>"/>
                                                                                    <?php
                                                                                } else {
                                                                                    ?>
                                                                                    <input name="txtTo" type="text" id="txtTo"  class="col-sm-6 required" value="<?php echo $journal_info['j_to_email']; ?>"/>
                                                                                    <?php
                                                                                }
                                                                                ?>

																				<span class="validemail"></span>

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
                                                                            <label class="col-sm-3 control-label no-padding-right">Reply To</label>
                                                                            <div class="col-sm-9">
                                                                                <input name="txtreplyto" type="text"  class="col-sm-6" value="<?php echo $journal_info['reply_to']; ?>"/>
                                                                                Note:- Corresponding ReplyTo fetched from journal details.</div>
                                                                        </div>






                                                                       <?php 
																		if($app_res['cust_id']==37)
																		{
																		?>




                                                                        <div class="form-group">
                                                                            <label class="col-sm-3 control-label no-padding-right">Subject</label>
                                                                            <div class="col-sm-9">
                                                                                <input name="txtSub" type="text"  class="col-sm-6 required" value="<?php echo $sub_mail; ?> - Proof"/>
                                                                            </div>
                                                                        </div>
																		<?php 
																		}
																		else
																		{
																		?>
																		 <div class="form-group">
                                                                            <label class="col-sm-3 control-label no-padding-right">Subject</label>
                                                                            <div class="col-sm-9">
                                                                                <input name="txtSub" type="text"  class="col-sm-6 required" value="<?php echo $d_mail_info['subject']; ?>"/>











                                                                            </div>
                                                                        </div>
																		<?php 
																		}
																		?>



                                                                        <div class="form-group">
                                                                            <label class="col-sm-3 control-label no-padding-right">Message</label>
                                                                            <div class="col-sm-9">
                                                                                <textarea name="txtBody" id="txtBody" type="text"  class="col-sm-8 required" ><?php echo $d_mail_info['message'] ?></textarea>
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
                                                            $presult = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = " . $app_res['department_id'] . ";");
                                                            $ptoatl_count = $presult->num_rows;
                                                            $pdept_res = array();
                                                            if ($ptoatl_count > 0) {
                                                                $pdept_res = $presult->fetch_assoc();
                                                            }
                                                            ?>
                                                            <div class="form-group">
                                                                <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Work DIR:</label>
                                                                <div class="col-xs-12 col-sm-9">
                                                                    <?php
                                                                    $work_dir = $app_res['work_dir'];
                                                                    $work_dir = str_replace(WF_DIR, TOOL_DIR, $work_dir);
                                                                    ?>
                                                                    <div class="clearfix"> <a class="btn btn-primary" href="file:///<?php echo $work_dir . $app_res['volume_folder'] . "/" . $app_res['pub_id'] . "/" . $app_res['stage'] . "/" . $pdept_res['dept_name'] . "/"; ?>" target="_blank"><i class="ace-icon fa fa-folder align-top bigger-125"></i> View</a> 
																	<?php
																		if($customer_info['cust_name'] == "Sunrise-NOW")
																		{
																	?>
																		<span class="btn btn-primary" id="Sunrise_Upload_Files_to_AWS_Server"> <i class="ace-icon fa fa-cloud-upload align-top bigger-125"></i> Upload Files to AWS Server </span>
																	<?php
																		}
																	?>
																	</div>
                                                                </div>
                                                            </div>
                                                            <div class="space-2"></div>
                                                            <div class="form-group">
                                                                <label class="control-label bolder blue control-label col-xs-12 col-sm-3 no-padding-right">Check the files to send</label>
                                                                <div class="checkbox col-xs-12 col-sm-9">
                                                                    <?php
// Open the source directory to read in files
                                                                     $src = $app_res['work_dir'] . $app_res['volume_folder'] . "/" . $app_res['pub_id'] . "/" . $app_res['stage'] . "/" . $pdept_res['dept_name'] . "/";
                                                                    if (is_dir($src)) {
                                                                        $i = new DirectoryIterator($src);
                                                                        foreach ($i as $f) {
                                                                            if ($f->isFile()) {
                                                                                 $filename = $f->getFilename();
                                                                                // $filesize = filesize($src."/".$f);
                                                                                $fsize = filesize($src . "/" . $f);
                                                                                $filesize = formatSizeUnits($fsize);
                                                                                ?>
                                                                                <div class="row">
                                                                                    <label>
                                                                                        <?php
                                                                                        if (strpos($filename, '.zip') !== false) {
                                                                                            ?>
                                                                                            <input name="attach_files[]" class="ace ace-checkbox-2" data-size="<?php echo $fsize; ?>" value="<?php echo $filename; ?>" type="checkbox" checked="checked"/>
                                                                                            <?php
                                                                                        } elseif (strpos($filename, '-ori.pdf') == true) {
                                                                                            ?>
                                                                                           <input name="attach_files[]" class="ace ace-checkbox-2" data-size="<?php echo $fsize; ?>" value="<?php echo $filename; ?>" type="checkbox" />
                                                                                            <?php
                                                                                        }elseif($app_res['cust_id']==37) {
                                                                                            ?>
                                                                                            <input name="attach_files[]" class="ace ace-checkbox-2" data-size="<?php echo $fsize; ?>" value="<?php echo $filename; ?>" type="checkbox" checked="checked" />
                                                                                            <?php
                                                                                        }elseif($app_res['cust_id']!=37) {
                                                                                            ?>
                                                                                            <input name="attach_files[]" class="ace ace-checkbox-2" data-size="<?php echo $fsize; ?>" value="<?php echo $filename; ?>" type="checkbox" />
                                                                                            <?php
                                                                                        }
																						
                                                                                            ?>
                                                                                                                                                                                  <span class="lbl"> <?php echo $filename; ?> </span> <span class="lbl"> <b>(<?php echo $filesize; ?>)</b> </span> </label>

                                                                                            <?php
                                                                                        ?>
                                                                                </div>
                                                                                <?php
                                                                            }
                                                                        }
                                                                    }
                                                                    ?>
																	
																	   <?php
																	    if($app_res['cust_id']==37)
																	   { 
																	    $src_fs=$app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/Delivery/";
							    $fsizes = filesize($src_fs . "/" .$app_res['pub_id']."-ori.pdf");
                              // $filesize = formatSizeUnits($fsize);
							     if($fsizes >2500000)
								{
									//echo "1";
// Open the source directory to read in files
                                                                     $src_misc = "E:/New_Workflow_Files/JOURNAL/downloads/JOURNALS/ASP/misc/";
                                                                    if (is_dir($src_misc)) {
                                                                        $i = new DirectoryIterator($src_misc);
                                                                        foreach ($i as $f) {
                                                                            if ($f->isFile()) {
                                                                                $filename = $f->getFilename();
                                                                                // $filesize = filesize($src_misc."/".$f);
                                                                                $fsize = filesize($src_misc . "/" . $f);
                                                                                $filesize = formatSizeUnits($fsize);
                                                                                ?>
																				<?php
                                                                                        if (strpos($filename, '.txt') == true) {
                                                                                            ?>
                                                                                <div class="row">
                                                                                    <label>
                                                                                        
                                                                                            <input name="attach_filess[]" class="ace ace-checkbox-2" data-size="<?php echo $fsize; ?>" value="<?php echo $filename; ?>" type="checkbox" checked="checked"/>
																							                                                                                        <span class="lbl"> <?php echo $filename; ?> </span> <span class="lbl"> <b>(<?php echo $filesize; ?>)</b> </span> </label>

                                                                                           
                                                                                </div>
																				 <?php
                                                                                        } 
                                                                                        ?>
                                                                                <?php
                                                                            }
                                                                        }
                                                                    }
								}
                                                                    ?>
																	
																	   <?php
																	 
																	  
																	    $src_fs=$app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/Delivery/";
							    //$fsizes = filesize($src_fs . "/" .$app_res['pub_id']."-ori.pdf");
                              // $filesize = formatSizeUnits($fsize);
							    // if($fsizes >2500000)
								//{
									//echo "1";
// Open the source directory to read in files
                                                                     $src_misc = "E:/New_Workflow_Files/JOURNAL/downloads/JOURNALS/ASP/misc/";
                                                                    if (is_dir($src_misc)) {
                                                                        $i = new DirectoryIterator($src_misc);
                                                                        foreach ($i as $f) {
                                                                            if ($f->isFile()) {
                                                                                $filename = $f->getFilename();
                                                                                // $filesize = filesize($src_misc."/".$f);
                                                                                $fsize = filesize($src_misc . "/" . $f);
                                                                                $filesize = formatSizeUnits($fsize);
                                                                                ?>
																				<?php
                                                                                        if (strpos($filename, '.doc') == true) {
                                                                                            ?>
                                                                                <div class="row">
                                                                                    <label>
                                                                                        
                                                                                            <input name="attach_filesss[]" class="ace ace-checkbox-2" data-size="<?php echo $fsize; ?>" value="<?php echo $filename; ?>" type="checkbox" checked="checked"/>
																							                                                                                        <span class="lbl"> <?php echo $filename; ?> </span> <span class="lbl"> <b>(<?php echo $filesize; ?>)</b> </span> </label>
	
                                                                                </div>
																				
                                                                                            <?php
                                                                                        }
                                                                                        ?>
                                                                                <?php
                                                                            }
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
                                                                    <input type="hidden" name="work_dir" value="<?php echo $app_res['work_dir'] . $app_res['volume_folder'] . "/" . $app_res['pub_id'] . "/" . $app_res['stage'] . "/" . $pdept_res['dept_name'] . "/"; ?>">
                                                                    <input type="hidden" name="proof_dir" value="<?php echo $app_res['work_dir'] . $app_res['volume_folder'] . "/" . $app_res['pub_id'] . "/" . $app_res['stage'] . "/Proofing/"; ?>">
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
                                                                        if ('ontouchstart' in document.documentElement)
                                                                            document.write("<script src='assets/js/jquery.mobile.custom.min.js'>" + "<" + "/script>");
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
                                                                      jQuery(function ($) {
                                                                            $('#validation-form').validate({errorClass: 'help-block'});
                                                                            $('#txtBody').richText();//this will create the default editor will all buttons	
                                                                        })


																		$(window).load(function() 
																		{
																			$(".loader").fadeOut("slow");
																		});
                                                                        $('#Sunrise_Upload_Files_to_AWS_Server').click(function () {
																		
																			$(".loader").fadeIn("slow");
		var a_id = $("input[name='id']").val();
		 alert(a_id);
		$.ajax({
					type:'POST',
					url:'Sunrise_Upload_Files_to_AWS_Server.php',
					data:'a_id='+a_id+'&type=1',
					success:function(result){
						$(".loader").fadeOut("slow");
						 response = JSON.parse(result);
					
						 if(response.status == 1)
						 {
							bootbox.dialog(
							{
								message: '<br/><div class="alert alert-success">'+ response.message +'</div>',
								buttons:
								{
									"button" :
									{
										"label" : "Close",
										"className" : "btn-sm",
										"callback": function() {
											
										}
									}							
								} 						
							});
						}
						else
						{
							bootbox.dialog(
							{
								message: '<br/><div class="alert alert-warning">'+ response.message +'</div>',
								buttons:
								{
									"button" :
									{
										"label" : "Close",
										"className" : "btn-sm",
										"callback": function() {
											
										}
									}							
								} 						
							});
						}
						       
					}
				});  
			});                                                              // $('#txtBody').richText();//this will create the default editor will all buttons	
                                                                       // })



                                                                        $('#validation-form').submit(function () {

                                                                            var article_id = $("input[name='article_id']").val();
                                                                            var cust_name = $("input[name='cust_name']").val();
                                                                            var j_code = $("input[name='j_code']").val();
                                                                            var j_id = $("input[name='j_id']").val();
                                                                            var cust_id = $("input[name='cust_id']").val();
                                                                            var stage = $("input[name='stage']").val();


                                                                            var returnval = true;
                                                                            $.ajax({
                                                                                url: 'main_sample.php',
                                                                                async: false,
                                                                                type: 'POST',
                                                                                data: {article_id: article_id, cust_name: cust_name, j_code: j_code, j_id: j_id, cust_id: cust_id, stage: stage},
                                                                                context: this,
                                                                                success: function (result) {
                                                                                    // alert(result);
                                                                                    if (result == 1)
                                                                                    {
                                                                                        returnval = true;
                                                                                        this.submit();
                                                                                    } else if (result == 0)
                                                                                    {
                                                                                        returnval = false;
                                                                                        alert("No Proper File Name | Text Name  Or Modify Configuration");
                                                                                    } else if (result == 2)
                                                                                    {
                                                                                        returnval = false;
                                                                                        alert("Sub Folder Must Contain Files Or Modify Configuration.");
                                                                                    } else if (result == 3)
                                                                                    {
                                                                                        returnval = false;
                                                                                        alert("Folder Must Not Contain Zip Or Modify Configuration.");
                                                                                    } else if (result == 7)
                                                                                    {
                                                                                        returnval = true;
                                                                                        ;
                                                                                    } else
                                                                                    {
                                                                                        returnval = true;
                                                                                    }

                                                                                }
                                                                            });
                                                                            return returnval;

                                                                        });

        </script> 

        <script type="text/javascript">
            $(function () {


                $("input[id$='optionRad_0']").click(function () {
                    var re = $(this).val();
                    $("#fileattach").val(0);
                    $("#div1,#emaildiv").show();
                    $("#reminder").hide();
                    $('#image-file').val('');

                });


                $("input[id$='optionRad_1']").click(function () {
                    var tog = $(this).val();
                    $("#div1").hide();
                    $("#reminder").show();
                    $("#fileattach").val(0);
                    $("#emaildiv").show();
                    $('#image-file').val('');

                });



                $("input[id$='optionRad_2']").click(function () {
                    var mail = $(this).val();
                    $("#emaildiv").hide();
                    $("#fileattach").val(0);
                    $("#div1").show();
                    $("#reminder").hide();
                });


                $("input[id$='optionRad_3']").click(function () {
                    var mail = $(this).val();
                    $("#fileattach").val(3);
                    $("#div1").hide();
                    $("#emaildiv").show();
                    $('#image-file').val('');
                    $("#reminder").hide();

                    $("#image-file").bind('change', function () {
                        var fat = $("#fileattach").val();
                        if (fat == 3)
                        {
                            var res = this.files[0].size / 1024 / 1024;
                            if (res > 20) {
                                alert('File size exceeds 20 MB Cannot Accept');
                                $('#image-file').val(''); //for clearing with Jquery
                            }
                        }
                    });
                });
				
				
				$("input[id$='optionRad_4']").click(function () {
					var tog = $(this).val();
                    $("#div1").hide();
                    $("#reminder").show();
                    $("#fileattach").val(0);
                    $("#emaildiv").show();
                    $('#image-file').val('');
                });
				
				
                $("#saver").click(function ()
                {
                    var fat = $("#fileattach").val();
                    if (fat == 3)
                    {
                        var x;
                        x = document.getElementById("image-file").value;
                      /*   if (x == "") {
                            alert("Attachment is Empty Cannot Send");
                            return false;
                        }
                        ;
 */
                    }
                    ;

                });

<?php
if ($journal_info['ftp_send'] == 1 && $journal_info['j_email_send'] == 1) {
    ?>
                    $("input[id$='optionRad_0']").click();
    <?php
} else if ($customer_info['id'] == 42 || $customer_info['id'] == 43 || $customer_info['id'] == 44 || $customer_info['id'] == 46) {
    ?>
                    $("input[id$='optionRad_1']").click();
    <?php
} else if ($journal_info['ftp_send'] == 1) {
    ?>
                    $("input[id$='optionRad_2']").click();
    <?php
} else if ($journal_info['j_email_send'] == 1) {
    ?>
                    $("input[id$='optionRad_3']").click();
    <?php
}else if ($customer_info['id'] == 34) {
    ?>  
                    $("input[id$='optionRad_4']").click();
    <?php
}
?>
                $("#total_file_size_div").hide();
				<?php 
				if($app_res['cust_id']==37)
				{
				?>
                $('.ace-checkbox-2').click(function () {
                    $total_checked_file_size = 0;
                    $(".ace-checkbox-2:checked").each(function () {
                        $total_checked_file_size += parseInt($(this).attr("data-size"));
                    });
                    if ($total_checked_file_size != 0)
                    {
                        $("#total_file_size_div").show();
                        $fsize = formatSizeUnits($total_checked_file_size);
                        if ($total_checked_file_size > 1048576 * 2.5)
                        {
                            bootbox.dialog(
                                    {
                                        message: '<br/><div class="alert alert-danger"> <i class="ace-icon fa fa-exclamation-triangle"></i> Total file size is more than <b>2.5MB</b>. Please check and send mail.</div> '
                                    });
                            $(this).prop('checked', false);
                            return false;
                        }
                        $("#total_file_size").html($fsize);
                    } else
                    {
                        $("#total_file_size_div").hide();
                    }
                });
				
				<?php 
				}
				else
				{
				?>
				 $('.ace-checkbox-2').click(function () {
                    $total_checked_file_size = 0;
                    $(".ace-checkbox-2:checked").each(function () {
                        $total_checked_file_size += parseInt($(this).attr("data-size"));
                    });
                    if ($total_checked_file_size != 0)
                    {
                        $("#total_file_size_div").show();
                        $fsize = formatSizeUnits($total_checked_file_size);
                        if ($total_checked_file_size > 1048576 * 20)
                        {
                            bootbox.dialog(
                                    {
                                        message: '<br/><div class="alert alert-danger"> <i class="ace-icon fa fa-exclamation-triangle"></i> Total file size is more than <b>20MB</b>. Please check and send mail.</div> '
                                    });
                            $(this).prop('checked', false);
                            return false;
                        }
                        $("#total_file_size").html($fsize);
                    } else
                    {
                        $("#total_file_size_div").hide();
                    }
                });
				<?php 
				}
				?>
            });
            $(window).load(function () {
				
				<?php 
				if($app_res['cust_id']==37)
				{
				?>
                $total_checked_file_size = 0;
                $temp_file_size = 0;
                $(".ace-checkbox-2:checked").each(function () {
                    $temp_file_size += parseInt($(this).attr("data-size"));
                    if ($temp_file_size > 1048576 * 2.5)
                    {
                        // alert('Hi');
                        //$(this).prop('checked', false);
                    } else
                    {
                        $total_checked_file_size += parseInt($(this).attr("data-size"));
                    }
                });
                if ($total_checked_file_size != 0)
                {
                    $("#total_file_size_div").show();
                    $fsize = formatSizeUnits($total_checked_file_size);

                    if ($temp_file_size > 1048576 * 2.5)
                    {
                        bootbox.dialog(
                                {
                                    message: '<br/><div class="alert alert-danger"> <i class="ace-icon fa fa-exclamation-triangle"></i> Total file size is more than <b>2.5MB</b>. Please check and send mail.</div> '
                                });
                        $("#total_file_size").html($fsize);
                    }
                } else
                {
                    $("#total_file_size_div").hide();
                }
				
				<?php 
				}
				else
				{
				?>
				
				 $total_checked_file_size = 0;
                $temp_file_size = 0;
                $(".ace-checkbox-2:checked").each(function () {
                    $temp_file_size += parseInt($(this).attr("data-size"));
                    if ($temp_file_size > 1048576 * 20)
                    {
                        // alert('Hi');
                        //$(this).prop('checked', false);
                    } else
                    {
                        $total_checked_file_size += parseInt($(this).attr("data-size"));
                    }
                });
                if ($total_checked_file_size != 0)
                {
                    $("#total_file_size_div").show();
                    $fsize = formatSizeUnits($total_checked_file_size);

                    if ($temp_file_size > 1048576 * 20)
                    {
                        bootbox.dialog(
                                {
                                    message: '<br/><div class="alert alert-danger"> <i class="ace-icon fa fa-exclamation-triangle"></i> Total file size is more than <b>20MB</b>. Please check and send mail.</div> '
                                });
                        $("#total_file_size").html($fsize);
                    }
                } else
                {
                    $("#total_file_size_div").hide();
                }
				<?php 
				}
				?>
            });

            function formatSizeUnits(bytes) {
                if (bytes >= 1073741824) {
                    bytes = (bytes / 1073741824).toFixed(2) + ' GB';
                } else if (bytes >= 1048576) {
                    bytes = (bytes / 1048576).toFixed(2) + ' MB';
                } else if (bytes >= 1024) {
                    bytes = (bytes / 1024).toFixed(2) + ' KB';
                } else if (bytes > 1) {
                    bytes = bytes + ' bytes';
                } else if (bytes == 1) {
                    bytes = bytes + ' byte';
                } else {
                    bytes = '0 byte';
                }
                return bytes;
            }

$(document).ready(function() {
	
	validateMultipleEmails($('#txtTo').val());
    $('#txtTo').blur(function() {
        validateMultipleEmails($('#txtTo').val());
    });
});

function validateMultipleEmails(emailInput) {
    // Get value on emails input as a string
    var emails = emailInput;
    
    // Split string by comma into an array
    emails = emails.split(",");

    var valid = true;
    var regex = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    
    var invalidEmails = [];
    
    for (var i = 0; i < emails.length; i++) {
        // Trim whitespaces from email address
        emails[i] = emails[i].trim();
        
        // Check email against our regex to determine if email is valid
        if( emails[i] == "" || ! regex.test(emails[i])){
            invalidEmails.push(emails[i]);
        }
    }
    
    // Output invalid emails
    $('.validemail .text-danger').remove();
    if(invalidEmails != 0) {
        $('.validemail').append('<p class="text-danger">Invalid emails: ' + invalidEmails.join(', ') + '</p>');
		$('#saver').hide();
    }
	else
	{
		$('#saver').show();
	}
}














































































        </script>
    </body>
</html>
