<?php 
include "includes/includes.php";
session_start();
include_once("includes/assign-modern.php");
$rqs = '';
if(!empty($_POST['return_qs'])) {
	parse_str($_POST['return_qs'], $rq_);
	$rq_ = array_intersect_key($rq_, array_flip(array('cust_id','stage_id','j_id','pub_id','from_dt','to_dt','user_id','dept_id','dep_id','r_user_id')));
	if($rq_) { $rqs = '&'.http_build_query($rq_); }
}
include_once("smtpmail/phpmailer/class.phpmailer.php");
include_once("assign_mail_template.php");
//$mail_from = "inward@transforma.in";
//$mail_pass = "Zot60242";

$mail_from = $email_setting['from_email'];
$mail_pass = $email_setting['from_password'];


?>
<?php
if(isset($_POST['assign']))
{
	// An article cannot be re-assigned to the freelancer who already holds it: check before changing anything.
	$same_owner = array();
	foreach((array)$_POST['aids'] as $chk_id) {
		if($chk_id === '') { continue; }
		$chk = mysqli_fetch_array(mysqli_query($db, "select allocate_to from inw_inward_dtl where pub_id='".mysqli_real_escape_string($db, $chk_id)."'"));
		if($chk && (int)$chk['allocate_to'] === (int)$_POST['ruser_id']) { $same_owner[] = $chk_id; }
	}
	if($same_owner) {
		header("location:".$_COOKIE['set_url']."/rassigntovendor?msg=Already assigned to the selected freelancer: ".implode(', ', $same_owner).". Nothing was changed.".$rqs);
		exit;
	}
	
	
	//echo $_POST['pub_id'];exit;
	//print_r($_POST);
	//print_r($_POST['aids']);
	foreach($_POST['aids'] as $key => $a_id) {
		//echo $a_id; 
		if($a_id!="")
		{
			$article_cust_id=mysqli_fetch_array(mysqli_query($db,"select cust_id from inw_inward_dtl where pub_id='".$a_id."'"));

				if($article_cust_id['cust_id']=='57')
				{
				$wf_id='128';	
				}
				elseif($article_cust_id['cust_id']=='68' || $article_cust_id['cust_id']=='69')
				{
				$wf_id='176';	
				}
				elseif($article_cust_id['cust_id']=='58')
				{
				$wf_id='126';
				}
				elseif($article_cust_id['cust_id']=='82')
				{
				$wf_id='189';
				}
				elseif($article_cust_id['cust_id']=='33')
				{
				$wf_id='189';
				}
				else
				{
				$wf_id='128';
				}
				$userdetails=mysqli_fetch_array(mysqli_query($db,"select * from users where id='".$_POST['ruser_id']."'"));

				$user_working_status = '<span class="label label-sm label-success">CE</span>|<span class="label label-sm label-info">'.$userdetails['user_name'].'</span>|<span class="label label-sm label-warning">Assigned</span>';

				$assigned="`allocate_to` ='".$_POST['ruser_id']."',vendor_id='".$_POST['ruser_id']."',vendor_assign_date='".date('Y-m-d H:i:s')."',`allocate_time` ='".date('Y-m-d H:i:s')."',vendor_due_date='".date('Y-m-d',strtotime($_REQUEST['ce_pe_due_date']))."',wf_id='".$wf_id."',user_working_status = '".$user_working_status."'";
			
			$prj_update_query="UPDATE inw_inward_dtl SET $assigned WHERE pub_id = '".$a_id."' "; 
			//echo $prj_update_query; exit;
			$db->query($prj_update_query);
			
		}

	}//exit;
			
			$insert_reason=mysqli_query($db,"insert into assigned_article_user(`ruser_id`,`dept_id`,`total`,`a_date`,`cdate`,`instructions`,`article_ids`) values ('".$_REQUEST['ruser_id']."','1','".$_REQUEST['aa_count']."','".date('Y-m-d',strtotime($_REQUEST['ce_pe_due_date']))."','".date('Y-m-d H:i:s')."','".$_REQUEST['message']."','".$_POST['pub_id']."')");
			
			$userdetails_password=mysqli_fetch_array(mysqli_query($db,"select * from tbl_user_password where user_id='".$_POST['ruser_id']."' order by id desc limit 1"));
		
			
			
$mail_aids = array_filter(array_map('trim', (array)$_POST['aids']));
@$body = assign_mail_body(array(
	'full_name'  => $userdetails['full_name'],
	'user_name'  => $userdetails['user_name'],
	'password'   => $userdetails_password['password'],
	'message'    => $_REQUEST['message'],
	'due_date'   => $_REQUEST['ce_pe_due_date'],
	'portal_url' => 'http://103.66.12.3:8010/workflow-new/vportal-new/index.php',
	'reassign'   => true,
	'articles'   => assign_mail_articles($db, $mail_aids),
	'logo_cid'   => file_exists(dirname(__FILE__).'/assets/images/translogo.png') ? 'transforma_logo' : ''
));
$mail_ids = implode(', ', $mail_aids);
@$subject = (count($mail_aids) > 1 ? 'Articles ' : 'Article ') . (strlen($mail_ids) > 70 ? substr($mail_ids, 0, 67) . '...' : $mail_ids) . ' Assign Notification';	

		$mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host = "outlook.office365.com";
		$mail->Port = 587;
		$mail->SMTPSecure = 'tls';
		$mail->SMTPAuth = true;		 
        $mail->Username = $mail_from;
        $mail->Password = $mail_pass;
        $mail->From = $mail_from;
        $mail->FromName = $mail_from;
        $mail->isHTML(true);
        $mail->Subject =$subject;
        $mail->Body = $body;
        if(file_exists(dirname(__FILE__).'/assets/images/translogo.png')) { $mail->AddEmbeddedImage(dirname(__FILE__).'/assets/images/translogo.png', 'transforma_logo', 'translogo.png', 'base64', 'image/png'); }
		$to=$userdetails['user_email'];
		$cc="uma@transforma.in,inward@transforma.in,viji@transforma.in,trans2@transforma.in,indesign@transforma.in,journalqc@transforma.in,art@transforma.in,ananthi@transforma.in,jeyapriya@transforma.in";
		$bcc="marimuthu@transforma.in";
		
//// Send mail to more than one user ///
        $mail_to = explode(',', $to);
        foreach ($mail_to as $email) {
            if ($email != '') {
                $mail->addAddress($email);
            }
        }
/////////// End here send mail ////////


        $mail->AltBody = ""; // optional, comment out and test

        $mail_tocc = explode(',', $cc);
        foreach ($mail_tocc as $email) {
            if ($email != '') {
                $mail->AddCC($email);
            }
        }

        $mail_tobcc = explode(',', $bcc);
        foreach ($mail_tobcc as $email) {
            if ($email != '') {
                $mail->AddBCC($email);
            }
        }


		 if(!$mail->Send()) {

			header("location:".$_COOKIE['set_url']."/rassigntovendor?msg=Successfully Allocated But Mail not Sent".$rqs);
    } else {
			header("location:".$_COOKIE['set_url']."/rassigntovendor?msg=Successfully Allocated and Mail sent".$rqs);
    }
		
}

// Filters come from the query string only (nothing is kept in the session). No filter = everything.
$flt = am_request_filters();
$f_status = am_status_sql($flt['inpt_status']);
$f_var = am_where($db, $flt, 'stage');
if($flt['user_id']!=='') { $f_var .= " and wd.allocate_to = ".intval($flt['user_id'])." "; }



?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php include_once 'includes/modern-ui.php'; include_once 'includes/article-filter-ui.php'; mu_head(); af_head(); am_head(); ?>

    <!-- Data Table CSS- -->

  </head>

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
   
      <!-- Page body starts -->
   <style>
  .alert {
    max-width: 300px; /* Adjust the maximum width as needed */
    padding: 10px; /* Add some padding for better spacing */
    margin: 10px auto; /* Center the div horizontally */
  }
  .hide {
  display: none;
}
.close {
    position: absolute;
    top: 5px;
    right: 5px;
    padding: 5px;
    background-color: transparent;
    border: none;
    cursor: pointer;
  }

  .close:hover {
    color: #000;
  }
  
  .dataTables_length{
	  margin-bottom: 15px !important;
  }
</style>   
      <div class="container-fluid" style="justify-content: space-between;">
	  
	   <?php
  if(!empty($_REQUEST['msg']))  {
    echo '<div id="successMessage" class="alert alert-success">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      '. $_REQUEST['msg'] .'</div>';   
  }
  ?> 
        <div class="af-page" style="padding-bottom:0;">
          <div class="af-hero">
            <div><small>Freelancer</small><h1>Jobs to Reallocate (Freelancer)</h1></div>
          </div>
        </div>
      </div>

      <div class="af-page"><div class="af-wrap">
        <form action="" name="articles" class="formouter" method="get">
					<div class="af-bar">
	
			<h5 class="widget-title" style="flex:1 1 100%;margin:0;font-size:13px;font-weight:700;color:#14304a;">Get Freelancer wise FP Articles</h5>
      <div class="af-field">

      <label class="mb-2">Select Freelancer:</label>
      <?php
      $query = "SELECT full_name,id FROM `users` where `user_level` = 4  ORDER BY `full_name` ASC ";
      $users = $db->query($query) or ($db->error);		

      ?>
      <select id="user_id" name="user_id" class="form-select"  data-placeholder="Choose">
      <option value="">All freelancers</option>
      <?php while($row = $users->fetch_assoc()){ ?>
      <option value="<?php echo $row['id']; ?>" <?php if($_REQUEST['user_id'] ==$row['id']){?> selected <?php } ?>><?php echo $row['full_name']; ?></option>
      <?php } ?>
      </select>
      </div>
			  
			 
			
<div class="af-actions"><input type="submit" value="Get Articles" name="getaction" class="af-btn primary"></div>
</div>
       </form></div>
					
      <!-- Table section -->

       <div class="af-card">
	   <div class="table-responsive">
					<div class="pull-right mb-2">
				  <label class="control-label" style="color: #222"><b> Selected Articles count</b> : </label>
				  <div class="btn btn-danger" id="a_count">0</div>
				  </div>

		<form action="<?php echo $_COOKIE['set_url'];?>/rassigntovendor" id="assign_due_date_form" method="post" autocomplete="off">
		<input type="hidden" name="return_qs" value="<?php echo htmlspecialchars(http_build_query(array_filter(array_diff_key($flt, array('inpt_status'=>1)), 'strlen'))); ?>">
		
	
											
											
              <table id="dynamic-table" class="table custtable table-primary" style="width: 100% !important; overflow:auto !important;text-wrap: nowrap !important;white-space: nowrap !important">
                <thead>
                  <tr>
                    <th class="center"> <label class="pos-rel">
                        <input type="checkbox" id="checkAll" name="checkAll" class="ace" />
                        <span class="lbl"></span> </label>
                    </th>                    
                    <th data-sortable="true" scope="col" class="thcolor">Journal</th>
                    <th data-sortable="true" scope="col" class="thcolor">Article</th>
                    <th data-sortable="true" scope="col" class="thcolor">Freelancer</th>
                    <th data-sortable="true" scope="col" class="thcolor">Allocated On</th>
                    <th data-sortable="true" scope="col" class="thcolor"> Received Date </th>
                    <th data-sortable="true" scope="col" class="thcolor">Due Date </th>
                    <th data-sortable="true" scope="col" class="thcolor">Manuscript</th>
                    <th data-sortable="true" scope="col" class="thcolor">Fig</th>
                    <th data-sortable="true" scope="col" class="thcolor">Table</th>
                    <th data-sortable="true" scope="col" class="thcolor">Stage</th>
                    <th data-sortable="true" scope="col" class="thcolor">Status</th>
                    
                  </tr>
                </thead>
                <tbody>
                  <?php


	
				$cnt_query = "SELECT COUNT(wd.id) as num FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND wd.stage = 'FP' and department_id in('1','22','32')  and wd.allocate_to IN (SELECT id FROM users WHERE user_level = 4)  ".$f_status." ".$f_var." ";
		    $query = "SELECT (SELECT full_name FROM users WHERE id = wd.allocate_to) AS fl_name, wd.allocate_to AS owner_id, COALESCE(NULLIF(wd.vendor_assign_date,'0000-00-00 00:00:00'), wd.allocate_time) AS alloc_at,c.cust_name,journal_title,pub_id,recv_dt,due_dt,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage, wd.notes FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND wd.stage = 'FP' and department_id in ('1','32') and wd.allocate_to IN (SELECT id FROM users WHERE user_level = 4) ".$f_status."  ".$f_var."";

		$customers = $db->query($query) or ($db->error);				
		while($row = $customers->fetch_assoc()){ 
		?>
                  <tr <?php if($row['status'] == 'Query' || $row['status'] == 'Hold'){ ?> class="alert-danger" <?php } ?>>
                    <td class="center"><label class="pos-rel">
                        <input type="checkbox" class="ace aids" name="aids[]" value="<?php echo $row['pub_id']; ?>" data-owner="<?php echo (int)$row['owner_id']; ?>" data-owner-name="<?php echo htmlspecialchars($row['fl_name']); ?>" />
                        <span class="lbl"></span> </label></td>                   
                    <td><?php echo $row['j_code']; ?></td>
                    <td class="hidden-480"><?php echo $row['pub_id']; echo ($row['notes']!='') ? ' <span class="label label-sm label-info">'.substr($row['notes'],0,10).'</span>' : '';?></td>
                    <td><span class="label label-sm label-info"><?php echo htmlspecialchars($row['fl_name']); ?></span></td>
                    <?php $alloc_ts = !empty($row['alloc_at']) ? strtotime($row['alloc_at']) : 0; ?>
                    <td data-order="<?php echo (int)$alloc_ts; ?>"><?php echo $alloc_ts ? '<b>'.date('d-m-Y', $alloc_ts).'</b> <span style="color:#64748b">'.date('h:i A', $alloc_ts).'</span>' : '-'; ?></td>
                    <td><?php echo (date('d-m-Y',strtotime($row['recv_dt']))); ?></td>
                    <td><?php echo (date('d-m-Y',strtotime($row['due_dt']))); ?></td>
                    <td><?php echo $row['manuscript_count']; ?></td>
                    <td><?php echo $row['fig']; ?></td>
                    <td class="hidden-480"><?php echo $row['tab']; ?></td>
                    <td class="hidden-480"><?php echo $row['stage']; ?></td>
                    <td class="hidden-480"><span class="label label-sm label-success"><?php echo $row['status']; ?></span></td>
                   
                  </tr>
                  <?php } ?>
				  
				
                </tbody>
              </table>
			  
			   <div class="assign-panel">
	<div class="ap-head">
		<span class="ap-title">Reassign to Another Freelancer</span>
		<span class="ap-count zero" id="ap_count"><b id="ap_count_n">0</b> article(s) selected</span>
	</div>
	<div class="ap-body">
		<div>
			<label class="ap-label" for="ruser_id">New freelancer <i>*</i></label>
			<?php
    $query = "SELECT full_name,id FROM `users` where `user_level` = 4 and id!='".$_REQUEST['user_id']."'  ORDER BY `full_name` ASC ";
    $users = $db->query($query) or ($db->error);		
    
    ?>
	<select id="ruser_id" name="ruser_id" required>
    <option value="">Select new freelancer</option>
    <?php while($row = $users->fetch_assoc()){ ?>
    <option value="<?php echo $row['id']; ?>" <?php if($_REQUEST['user_id'] ==$row['id']){?> selected <?php } ?>><?php echo $row['full_name']; ?></option>
    <?php } ?>
    </select>
		</div>
		<div>
			<label class="ap-label" for="message">Instructions <span style="text-transform:none;font-weight:400">(optional)</span></label>
			<textarea name="message" id="message" placeholder="Any notes for the freelancer"></textarea>
		</div>
		<div>
			<label class="ap-label" for="ce_pe_due_date">Due date <i>*</i></label>
			<div class="ap-datewrap">
				<input type="text" id="ce_pe_due_date" name="ce_pe_due_date" class="ap-date required date-picker" value="" placeholder="dd-mm-yyyy" autocomplete="off">
				<i class="ace-icon fa fa-calendar"></i>
			</div>
			<div class="ap-quick">
				<button type="button" data-days="1">Tomorrow</button>
				<button type="button" data-days="3">+3 days</button>
				<button type="button" data-days="7">+7 days</button>
			</div>
		</div>
		<div class="ap-go">
			<button type="submit" id="assign" value="Assign" name="assign" disabled>Reassign Freelancer <i class="ace-icon fa fa-plus icon-on-right"></i></button>
			<div class="ap-hint" id="ap_hint">Tick articles above to enable</div>
		</div>
	</div>
	<input type="hidden" name="dep_id" id="" value="1">
	<input type="hidden" name="cust_id" id="" value="<?php echo $_REQUEST['cust_id'];?>">
</div>
<input type="hidden" name="pub_id" id="pub_id" value="">
			  <input type="hidden" name="aa_count" id="aa_count" value="">
			  </form>
		
		</div>
      	</div>
              </div>


     <!-- Footer Section -->
     <?php echo footer(); ?>




<!-- Footer Section -->
<?php echo scripts(); ?>	

   <script type="text/javascript">
			$(document).ready(function(){
    $('#dep_id').on('change',function(){
        var dep_id = $(this).val();
        if(dep_id){
            $.ajax({
                type:'POST',
                url:'get_user.php',
                data:'dept_id='+dep_id,
                success:function(html){
                    $('#user_id').html(html);                    
                }
            }); 
        }else{
            $('#user_id').html('<option value="">Select User first</option>');
            
        }
    });

	
	});
	
			
		</script>
     <script type="text/javascript">
	 
	 
	  $(document).ready(function() {
			table = $('#dynamic-table').DataTable();
	 });
	 
	 
	 
$(document).ready(function() {
    $('#assign_due_date_form').submit(function() {
        if ($('input:checked', this).length  > 0) {
            return true;
        } else {
            alert('Please tick at least one Article to assign the due date!');
            return false;
        }
    });
	
	$('.aids').click(function() {
        var checkboxes = $('input[name="aids[]"]:checked').length;
		//alert(checkboxes);
		$("#a_count").html(checkboxes);
		$("#aa_count").val(checkboxes);
        
    })
	
});


$(function () {
    $("#checkAll").click(function () {
        if ($("#checkAll").is(':checked')) {
            $(".aids").prop("checked", true);
			var checkboxes = $('input[name="aids[]"]:checked').length;	
			$("#a_count").html(checkboxes - 1);			
		$("#a_count").html(checkboxes);
		$("#aa_count").val(checkboxes);
        } else {
            $(".aids").prop("checked", false);
			$("#a_count").html(0);
			$("#aa_count").val(0);
        }
    });
});
	

 
jQuery(function($) {			
			
				//documentation : http://docs.jquery.com/Plugins/Validation/validate
			
				//datepicker plugin
				//link
				$('.date-picker').datepicker({
					autoclose: true,
					todayHighlight: true,
					format: 'dd-mm-yyyy'
				})
	
				$('#assign_due_date_form').validate({errorClass: 'help-block'});			

			})
			
			  $(document).ready(function() {
      $('input[type="checkbox"]').change(function() {
        var selectedValues = $('input[name="aids[]"]:checked').map(function() {
          return this.value;
        }).get().join(',');

        $('#pub_id').val(selectedValues);
      });
    });
		</script> 
<div class="cm-back" id="cm_back"><div class="cm-box" role="dialog" aria-modal="true">
	<div class="cm-head"><span id="cm_title">Confirm assignment</span><small id="cm_sub"></small></div>
	<div class="cm-body">
		<div class="cm-row"><span id="cm_who_l">Assign to</span><span id="cm_who"></span></div>
		<div class="cm-row"><span>Due date</span><span id="cm_due"></span></div>
		<div class="cm-row" id="cm_msg_row"><span>Instructions</span><span id="cm_msg"></span></div>
		<div class="cm-list">Articles (<span id="cm_n">0</span>)</div>
		<div class="cm-chips" id="cm_chips"></div>
	</div>
	<div class="cm-foot"><button type="button" class="cm-cancel" id="cm_cancel">Cancel</button><button type="button" class="cm-ok" id="cm_ok">Confirm &amp; Assign</button></div>
</div></div>
  <?php am_foot(); ?>
  <?php af_foot(); ?>
  </body>

</html>
