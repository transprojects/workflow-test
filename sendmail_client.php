<?php 
include "includes/includes.php";
include_once 'includes/issue_drafts.php';
include_once("smtpmail/phpmailer/class.phpmailer.php");


	
	 
	 
	//echo $_SERVER['HTTP_CLIENT_IP'];
if(isset($_POST['action']) && $_POST['action']=='update')
{
	
	//// MAILER SETTINGS ///	
	 $query_es = "SELECT * FROM `email_settings` WHERE id= '2'";
	 $steps_es = $db->query($query_es) or ($db->error);
	 $email_st = $steps_es->fetch_assoc();
	 
	 
		foreach($_POST as $key => $value) {
		$post[$key] = filter($value);
	}
	
if (trim((string)$post['subject']) == '' || trim(strip_tags(html_entity_decode((string)$post['message']))) == '') { idr_fail('sendmail_client.php', 'id='.(int)$post['id'], 'Please enter the subject and write the comments before sending.'); }
$insert_query = "INSERT INTO `query` (`jid`,`cust_id`,`aid`, `from_userid`, `to_userid`,`subject`, `message`,`created_date`, `query_raised_ip`) VALUES ('". $post['jid']."','". $post['cust_id']."','". $post['id']."', '". $_COOKIE['user_id']."','". $post['j_hid']."', '". mysqli_real_escape_string($db,$post['subject'])."', '". mysqli_real_escape_string($db,$post['message'])."', CURRENT_TIMESTAMP,'". $_SERVER['REMOTE_ADDR']."');";	
	$db->query($insert_query) or die($db->error);
	
	

		   $to="uma@transforma.in";
		   $cc="viji@transforma.in";	
		   $bcc="inward@transforma.in,marimuthu@transforma.in"; 
		   $subject_s=mysqli_real_escape_string($db,$post['subject']);
		   $message_s=mysqli_real_escape_string($db,$post['message']);
		
		 $subject="Query on ".$post['pub_id']." from ".$_COOKIE['full_name']."(".$post['dept_name'].") ".$subject_s;
		 $message='Dear Uma,<br/><br/> 
			'.$message_s.';
			<br/><br/>
			If you have any questions or require further assistance, please feel free to contact us.
			<br/><br/>
			<br/><br/>
			Best regards,<br/><br/>
			'.$_COOKIE['full_name'].'<br/>
			<br/>Note: This is a Auto generated mail, please do not reply to this<br/>';
			
	
		//echo $subject;
		//echo $message;exit;
		$mail_from=$email_st['from_email'];
		$mail_pass=$email_st['from_password'];
			
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
        $mail->Body = $message;

		$mail->AltBody    = ""; // optional, comment out and test

			
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
	

				
		
				$maxRetries = 3;
				$retryInterval = 5; // Retry interval in seconds
				
				
				for ($retryCount = 1; $retryCount <= $maxRetries; $retryCount++) {
    
    // Send the email
    if (!$mail->send()) {
		
		header("location:".$_COOKIE['set_url']."/articles?msg=Error sending email"); 

        // Retry after a delay
        sleep($retryInterval);
    } else {
		


		header("location:".$_COOKIE['set_url']."/articles?msg=Email sent successfully"); 
        break; // Exit the loop if email is sent successfully
		
    }
}
	   if ($retryCount > $maxRetries) {
    echo 'Email sending failed after maximum retries.';
}
		
	
	
		header("location:".$_COOKIE['set_url']."/articles?msg=Successfully added"); 


}
/////////////// Update completed ///////////////


	$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$_REQUEST['id']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$app_res = $result->fetch_assoc();
	}

/// Get the Client Name ////
$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = ".$app_res['cust_id'].";";
	$customers = $db->query($query) or ($db->error);
	$customer_info = $customers->fetch_assoc();
/// Get the journal name ///
	$query = "SELECT `j_id`,`j_name`,j_code,j_hid FROM `adm_journals` WHERE j_id = ".$app_res['j_id'].";";
	$journal = $db->query($query) or ($db->error);
	$journal_info = $journal->fetch_assoc();
	
	//echo "SELECT `id`,`full_name`,`user_email` FROM `users` WHERE id = ".$journal_info['j_hid']."";
	$journal_hinfo = mysqli_fetch_array(mysqli_query($db,"SELECT `id`,`full_name`,`user_email` FROM `users` WHERE id = ".$journal_info['j_hid'].""));
	$dep_res = mysqli_fetch_array(mysqli_query($db,"SELECT dept_name FROM `adm_dept_master` WHERE dept_code = ".$app_res['department_id'].""));
	


//echo $transaction_id;
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>	
    <?php include_once 'includes/modern-ui.php'; include_once 'includes/article-filter-ui.php'; include_once 'includes/issue_drafts.php'; mu_head(); af_head(); idr_head(); ?>

    <!-- Data Table CSS -->
    <style>
    th  {
  color: #336199 !important;
  background-color: #EDF3F4 !important;
  border-top: 1px solid #F7FBFF !important;
}
 td {
  color: #336199 !important;
  background-color: #EDF3F4 !important;
  border-top: 1px solid #F7FBFF !important;
}
.wholeouter {
    height: 70% !important;
    /* width: 100%; */
}
  </style>
      <style>
      .mp-info{display:flex;flex-wrap:wrap;gap:10px;align-items:stretch;}
      .mp-info>div{padding:8px 14px;border:1px solid #e4e9ef;border-radius:10px;background:#f9fbfd;min-width:140px;}
      .mp-info small{display:block;font-size:10.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#6b7785;}
      .mp-info b{color:#14304a;font-size:14px;}
      .mp-h{display:flex;align-items:center;gap:8px;margin:0 0 4px;font-size:13px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#14304a;}
      .mp-h i{color:#e8742a;}
      .mp-note{color:#6b7785;margin:0 0 12px;}
      .mp-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px 18px;margin-top:12px;}
      .mp-grid .af-field{min-width:0;}
      .mp-grid .af-field.wide{grid-column:1/-1;}
      .mp-grid .form-control,.mp-grid .form-select{width:100%;height:38px;border:1px solid #d5dde6;border-radius:9px;font-size:13.5px;box-shadow:none;}
      .mp-grid .form-control[readonly]{background:#f3f6f9;color:#51606f;}
      .mp-grid textarea.form-control{height:auto;min-height:100px;}
      .mp-grid input[type=file].form-control{height:auto;padding:6px 10px;}
      .mp-grid .select2-container{width:100% !important;}
      .mp-grid .select2-container--default .select2-selection--multiple{min-height:38px;border:1px solid #d5dde6;border-radius:9px;}
      .mp-all{display:inline-flex;align-items:center;gap:6px;margin-left:10px;font-size:12px;font-weight:600;text-transform:none;letter-spacing:0;color:#14304a;cursor:pointer;}
      .mp-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:18px;}
      .mp-req{color:#d64545;}
      .mp-tbl{width:100%;border-collapse:separate;border-spacing:0 6px;}
      .mp-tbl th{padding:6px 12px 2px;font-size:10.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#7b8a9a;text-align:left;white-space:nowrap;border:0;background:transparent;}
      .mp-tbl td{padding:10px 12px;background:#fff;border-top:1px solid #e4e9ef;border-bottom:1px solid #e4e9ef;vertical-align:top;color:#22303f;}
      .mp-tbl td:first-child{border-left:1px solid #e4e9ef;border-radius:10px 0 0 10px;}
      .mp-tbl td:last-child{border-right:1px solid #e4e9ef;border-radius:0 10px 10px 0;}
      .mp-pill{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:700;}
      .mp-pill.open{background:#fdf1d9;color:#b87800;} .mp-pill.done{background:#e3f6ec;color:#1e9e61;}
      /* the rich-text editor sits inside the card */
      .mp-grid .richText{border:1px solid #d5dde6;border-radius:9px;overflow:hidden;}
    </style>
  </head>

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
     <?php
if(isset($_REQUEST['cust_id']))
{
$cust_id=implode(",",$_REQUEST['cust_id']);
$ex_cid = explode(",",$cust_id);
}
else
{
$ex_cid='';
}
if(isset($_REQUEST['j_id']))
{
	 $jid=implode(",",$_REQUEST['j_id']);
 	 $ex_jid = explode(",",$jid);
}
else
{
	$ex_jid='';
}
if(isset($_REQUEST['stage']))
{
	  $stage=implode(",",$_REQUEST['stage']);
 	  $ex_stage = explode(",",$stage);
}
else
{
	$ex_stage='';
}
if(isset($_REQUEST['article_id']))
{
	  $article_id=$_REQUEST['article_id'];
}
else
{
	$article_id='';
}
if(isset($_REQUEST['from_dt']))
{
	  $from_dt=$_REQUEST['from_dt'];
}
else
{
	$from_dt='';
}
if(isset($_REQUEST['to_dt']))
{
	  $to_dt=$_REQUEST['to_dt'];
}
else
{
	$to_dt='';
}

	
?>
      <div class="container-fluid" style="justify-content: space-between;">
      <div class="af-page" style="padding-bottom:0;">
        <div class="af-hero">
          <div><small>Query to the project manager &middot; <?php echo htmlspecialchars($customer_info['cust_name']); ?> &middot; <?php echo htmlspecialchars($journal_info['j_code']); ?></small><h1>Send Query</h1></div>
          <a class="af-add" href="<?php echo $_COOKIE['set_url'];?>/articles" style="background:rgba(255,255,255,.16);box-shadow:none;border:1px solid rgba(255,255,255,.35);"><i class="fa fa-arrow-left"></i> Back to Articles</a>
        </div>
      </div>
    </div>

    <?php
    if(!empty($_REQUEST['msg']))  {
      echo '<div class="alert alert-success"><button type="button" class="close" aria-label="Close">&times;</button>'. htmlspecialchars($_REQUEST['msg']) .'</div>';
    } ?>

    <div class="af-page"><div class="af-wrap"><div class="formouter">
      <div class="mp-info">
        <div><small>Article</small><b><?php echo htmlspecialchars($app_res['pub_id']); ?></b></div>
        <div><small>Customer</small><b><?php echo htmlspecialchars($customer_info['cust_name']); ?></b></div>
        <div><small>Journal</small><b><?php echo htmlspecialchars($journal_info['j_code']); ?></b></div>
        <div><small>Stage</small><b><?php echo htmlspecialchars($app_res['stage']); ?></b></div>
        <div><small>Received</small><b><?php echo $app_res['recv_dt'] ? date('d-m-Y', strtotime($app_res['recv_dt'])) : '&ndash;'; ?></b></div>
        <div><small>Due</small><b><?php echo $app_res['due_dt'] ? date('d-m-Y', strtotime($app_res['due_dt'])) : '&ndash;'; ?></b></div>
      </div>
    </div></div></div>

    <?php 
				 $query_num=mysqli_num_rows(mysqli_query($db,"select * from  query where aid='".$_REQUEST['id']."'"));
?>
    <?php if($query_num>0) { ?>
    <div class="af-page"><div class="af-card">
      <h3 class="mp-h"><i class="fa fa-history"></i> Earlier queries on this article</h3>
      <div class="table-responsive">
        <table id="dynamic-table" class="mp-tbl">
          <thead><tr><th>#</th><th>Article title</th><th>From</th><th>Subject</th><th>Created on</th><th>Reply</th><th>Replied on</th><th>Status</th></tr></thead>
          <tbody>
            <?php
$i=1;
				 $notificaton_threads=mysqli_query($db,"select * from  query where aid='".$_REQUEST['id']."'");
				  while($row_pc =mysqli_fetch_array($notificaton_threads)){
	$manuscriptno=mysqli_fetch_array(mysqli_query($db,"select * from inw_inward_dtl where id='".$row_pc['aid']."' "));
	$fromuser=mysqli_fetch_array(mysqli_query($db,"select * from users where id='".$row_pc['from_userid']."' "));
	$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = ".$row_pc['cust_id'].";";
	$customers = $db->query($query) or ($db->error);
	$q_customer = $customers->fetch_assoc();
	$query = "SELECT `j_id`,`j_name`,j_code,j_hid FROM `adm_journals` WHERE j_id = ".$row_pc['jid'].";";
	$journal = $db->query($query) or ($db->error);
	$q_journal = $journal->fetch_assoc();
		?>
				   <tr>
					<td><?php echo $i; ?></td>
					<td><?php echo htmlspecialchars($manuscriptno['article_title']); ?></td>
					<td><?php echo htmlspecialchars($fromuser['full_name']); ?></td>
					<td><?php echo htmlspecialchars($row_pc['subject']); ?></td>
					<td><?php echo date('d-m-Y H:i a',strtotime($row_pc['created_date'])); ?></td>
					<td><?php echo $row_pc['reply_message']; ?></td>
					<td><?php if($row_pc['reply_message']!=''){ echo date('d-m-Y H:i a',strtotime($row_pc['reply_date'])); } ?></td>
					<td><?php if($row_pc['reply_message']==''){ ?><span class="mp-pill open">Open</span><?php }else{ ?><span class="mp-pill done">Replied</span><?php } ?></td>
				  </tr>
				  <?php
				  $i++;
		}
		?>
          </tbody>
        </table>
      </div>
    </div></div>
    <?php } ?>

    <div class="af-page"><div class="af-card">
      <h3 class="mp-h"><i class="fa fa-envelope-o"></i> Send a query to the project manager</h3>
      <p class="mp-note">This sends an email to the project manager and saves the query against the article.</p>
      <form class="form-horizontal" id="validation-form" method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?php echo $_REQUEST['id']; ?>" />
        <input type="hidden" name="pub_id" value="<?php echo $app_res['pub_id']; ?>" />
        <input type="hidden" name="dept_name" value="<?php echo $dep_res['dept_name']; ?>" />
        <input type="hidden" name="j_hid" value="<?php echo $journal_info['j_hid']; ?>" />
        <input type="hidden" name="cust_id" value="<?php echo $customer_info['id']; ?>" />
        <input type="hidden" name="jid" value="<?php echo $journal_info['j_id']; ?>" />
        <div class="mp-grid">
          <div class="af-field">
            <label>From</label>
            <input type="text" name="mail_from" class="form-control" value="<?php echo htmlspecialchars($_COOKIE['user_name']); ?>" placeholder="From" readonly>
          </div>
          <div class="af-field">
            <label>Recipient</label>
            <input type="text" name="mail_to" class="form-control" value="uma@transforma.in" placeholder="Recipient(s)" readonly>
          </div>
          <div class="af-field">
            <label>Subject <span class="mp-req">*</span></label>
            <input type="text" name="subject" id="mp_subject" class="form-control" value="" placeholder="Subject">
          </div>
          <div class="af-field wide">
            <label>Comments <span class="mp-req">*</span></label>
            <textarea name="message" id="message" class="form-control" rows="10" placeholder="Comments"></textarea>
          </div>
        </div>
        <div class="mp-actions">
          <a class="af-btn" href="<?php echo $_COOKIE['set_url'];?>/articles">Cancel</a>
          <button type="submit" class="af-btn primary" id="mp_save"><i class="fa fa-paper-plane"></i> Send</button>
        </div>
      </form>
    </div></div>

     <?php echo footer(); ?>




<!-- Footer Section -->
<?php echo scripts(); ?>	
    <?php idr_error_script(); idr_error_popup('Cannot send the query'); ?>

   
       <script src="<?php echo $_COOKIE['set_url'];?>/assets/js/select2/select2.min.js"></script>
	  <!-- page specific plugin scripts --> 
        <script src="<?php echo $_COOKIE['set_url'];?>/assets/js/jquery.richtext.min.js"></script> 
        <script src="<?php echo $_COOKIE['set_url'];?>/assets/js/jquery.validate.min.js"></script> 

   
    <script>
	                   jQuery(function ($) {
                                                                            $('#validation-form').on('submit', function (e) {
                                                                              if (!$.trim($('#mp_subject').val())) { idrError('Please enter the subject.', 'Subject required'); e.preventDefault(); return; }
                                                                              var txt = ($('#message').val() || '').replace(/<[^>]*>|&nbsp;/g, '').trim();
                                                                              if (!txt) { idrError('Please write the comments.', 'Comments required'); e.preventDefault(); }
                                                                            });
                                                                            $('#message').richText();//this will create the default editor will all buttons	
                                                                        })
      $(".customer_drop").select2({
        placeholder: "Select Department",
      });
     

      $("#chkall").click(function(){
      if($("#chkall").is(':checked') ){ //select all
        $("#assign_department").find('option').prop("selected",true);
        $("#assign_department").trigger('change');
      } else { //deselect all
        $("#assign_department").find('option').prop("selected",false);
        $("#assign_department").trigger('change');
      }
  });

    </script>


  </body>
</html>
