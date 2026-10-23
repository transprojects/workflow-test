<?php 
include "includes/includes.php";
session_start();
include_once("smtpmail/phpmailer/class.phpmailer.php");
//$mail_from = "inward@transforma.in";
//$mail_pass = "Zot60242";

$mail_from = $email_setting['from_email'];
$mail_pass = $email_setting['from_password'];


?>
<?php
if(isset($_POST['assign']))
{
	
	
	//echo $_POST['pub_id'];exit;
	//print_r($_POST);
	//print_r($_POST['aids']);
	foreach($_POST['aids'] as $key => $a_id) {
		//echo $a_id; 
		if($a_id!="")
		{
		   $article_cust_id=mysqli_fetch_array(mysqli_query($db,"select cust_id,j_id from inw_inward_dtl where pub_id='".$a_id."'"));
		   
		        $j_id    = $article_data['j_id'];

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
				elseif($article_cust_id['cust_id']=='80')
				{
				$wf_id='188';
				}
				elseif($article_cust_id['cust_id']=='82')
				{
				$wf_id='189';
				}
				elseif($article_cust_id['cust_id']=='33')
				{
				 // Get workflow code from journal table
				$journal_res = mysqli_fetch_array(mysqli_query($db,"
					SELECT j_fp_wf 
					FROM adm_journals 
					WHERE j_id='".$j_id."'
				"));

				$wf_code = trim($journal_res['j_fp_wf']);

				// Get wf_id from workflow master using wf_name
				$wf_res = mysqli_fetch_array(mysqli_query($db,"
					SELECT wf_id 
					FROM adm_workflow_master 
					WHERE wf_name='".$wf_code."'
					LIMIT 1
				"));

				$wf_id = $wf_res['wf_id'];
				}
				else
				{
				$wf_id='128';
				}
				$userdetails=mysqli_fetch_array(mysqli_query($db,"select * from users where id='".$_POST['user_id']."'"));

				$user_working_status = '<span class="label label-sm label-success">CE</span>|<span class="label label-sm label-info">'.$userdetails['user_name'].'</span>|<span class="label label-sm label-warning">Assigned</span>';

				$assigned="`allocate_to` ='".$_POST['user_id']."',vendor_id='".$_POST['user_id']."',vendor_assign_date='".date('Y-m-d H:i:s')."',`allocate_time` ='".date('Y-m-d H:i:s')."',vendor_due_date='".date('Y-m-d',strtotime($_REQUEST['ce_pe_due_date']))."',wf_id='".$wf_id."',user_working_status = '".$user_working_status."'";
			
			$prj_update_query="UPDATE inw_inward_dtl SET $assigned WHERE pub_id = '".$a_id."' "; 
			//echo $prj_update_query; exit;
			$db->query($prj_update_query);
			
		}

	}//exit;
			
			$insert_reason=mysqli_query($db,"insert into assigned_article_user(`user_id`,`dept_id`,`total`,`a_date`,`cdate`,`instructions`,`article_ids`) values ('".$_REQUEST['user_id']."','1','".$_REQUEST['aa_count']."','".date('Y-m-d',strtotime($_REQUEST['ce_pe_due_date']))."','".date('Y-m-d H:i:s')."','".$_REQUEST['message']."','".$_POST['pub_id']."')");
			
			$userdetails_password=mysqli_fetch_array(mysqli_query($db,"select * from tbl_user_password where user_id='".$_POST['user_id']."' order by id desc limit 1"));
		
			
			
@$body ="Dear ".$userdetails['full_name'].",
<br/>
<br/>
Please note that you have received the article ".$_REQUEST['pub_id'].". Kindly process and complete it promptly.
<br/><br/>
Instructions : ".$_REQUEST['message']."
<br/><br/>
Login Credentials Below :
<br/><br/>
Freelancer Portal URL :<a href='http://103.66.12.3:8010/workflow-new/vportal-new/index.php'>Click here</a><br/>
Freelancer Username: ".$userdetails['user_name']."<br/>
Freelancer Password : ".$userdetails_password['password']."
<br/><br/>
Thank you.
<br/><br/>
Best regards,
<br/>
Inward Team";
@$subject = "Article ".$_REQUEST['pub_id']." Assign Notification";	

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
		$to=$userdetails['user_email'];
		$cc="uma@transforma.in,inward@transforma.in,viji@transforma.in,trans2@transforma.in,indesign@transforma.in,journalqc@transforma.in,art@transforma.in,ananthi@transforma.in,jeyapriya@transforma.in ";
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

			header("location:".$_COOKIE['set_url']."/assigntovendor?msg=Successfully Allocated But Mail not Sent");
    } else {
			header("location:".$_COOKIE['set_url']."/assigntovendor?msg=Successfully Allocated and Mail sent");
    }
		
}

if(isset($_SESSION['inpt_status_qry']) || $_SESSION['inpt_status_qry'] =='' )
{
	$_SESSION['inpt_status_qry'] = " AND wd.status NOT IN ('Delivery','Client Review','Completed','Query','Hold') ";
}

if(isset($_REQUEST['getaction']) && $_REQUEST['getaction']!='')
{
	
$_SESSION['cust_id'] = $_POST['cust_id'];
$_SESSION['stage_id'] = $_POST['stage_id'];
$_SESSION['j_id'] = $_POST['j_id'];
$_SESSION['pub_id'] = $_POST['pub_id'];
$_SESSION['from_dt'] = $_POST['from_dt'];
$_SESSION['to_dt'] = $_POST['to_dt'];
$_SESSION['inpt_status'] = $_POST['inpt_status'];
$_SESSION['inpt_status_qry'] = " AND wd.status NOT IN ('Delivery','Client Review','Completed','Query','Hold') ";

if($_SESSION['inpt_status']!=''){
	if (in_array("Query", (array)($_SESSION['inpt_status'] ?? [])) && in_array("Hold", (array)($_SESSION['inpt_status'] ?? [])))
	{
		$_SESSION['inpt_status_qry'] = " AND wd.status NOT IN ('Delivery','Client Review','Completed') ";
	}
	else if(in_array("Query", (array)($_SESSION['inpt_status'] ?? [])))
	{
		$_SESSION['inpt_status_qry'] = " AND wd.status NOT IN ('Delivery','Client Review','Completed','Hold') ";
	}
	else if(in_array("Hold", (array)($_SESSION['inpt_status'] ?? [])))
	{
		$_SESSION['inpt_status_qry'] = " AND wd.status NOT IN ('Delivery','Client Review','Completed','Query') ";
	}
     
}
$var = '';
if($_SESSION['cust_id']!='' && $_SESSION['cust_id']!='all' ){
	
     $var.= "and c.id = '".$_SESSION['cust_id']."' ";
}
if($_SESSION['cust_id']!='' && $_SESSION['cust_id']=='all' ){
	
     $var.= "and c.id in('69','68','57','58','82')";
}
if($_SESSION['stage_id']!=''){
	
      $var.= "and stage LIKE '".$_SESSION['stage_id']."%' ";
}
if($_SESSION['j_id']!=''){
	
     $var.= "and j.j_id = '".$_SESSION['j_id']."' ";
}
if($_SESSION['pub_id']!=''){
	
     $var.= "and pub_id LIKE '%".$_SESSION['pub_id']."%' ";
}
if($_SESSION['from_dt']!='' && $_SESSION['to_dt']){
	
   $var.="and recv_dt BETWEEN '".date('Y-m-d',strtotime($_SESSION['from_dt']))."' and '".date('Y-m-d',strtotime($_SESSION['to_dt']))."'";
}

	$_SESSION['varname'] = $var;
}


?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php include_once 'includes/modern-ui.php'; include_once 'includes/article-filter-ui.php'; mu_head(); af_head(); ?>

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
            <div><small>Freelancer</small><h1>Jobs to Allocate (Freelancer)</h1></div>
          </div>
        </div>
      </div>

      <div class="af-page"><div class="af-wrap">
        <form action="" name="articles" class="formouter" method="post">
					<div class="af-bar">
	
			<h5 class="widget-title" style="flex:1 1 100%;margin:0;font-size:13px;font-weight:700;color:#14304a;">Get Customer wise FP Articles</h5>
			 <div class="af-field">
                <label class="mb-2">Choose Customer</label>
                <select class="form-select customer_drop" data-placeholder="Select Customer" name="cust_id" id="cust_id">
												    <option value="all">Select All</option>

										  <?php
										  if($_COOKIE['user_level']=='3')
										  {
											$sql_customers = $auth->showCustomerlistbyuser($_COOKIE['cust_id'],$_COOKIE['ocust_id']);
										  }
										  else
										  {
											  $cust_id='57';
											  $ocust_id='58,68,69,80,82,33';
											$sql_customers = $auth->showCustomerlistbyuser($cust_id,$ocust_id);
										  }
											foreach($sql_customers as $res_customers)
											{
												
									      	if(isset($_REQUEST['cust_id']))
													{
										    	?>
										       <option <?php if ($_SESSION['cust_id'] == $res_customers['id']) { echo ' selected="selected"'; } ?> value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
													<?php
													}
													else
													{
													?>
													 <option value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
													<?php
													}
											}
											?>
                </select>
              </div>
			  
			 
			
						</div>
						<div class="af-actions" style="margin:12px 0 4px;justify-content:flex-end;">
       <input type="submit" value="Get Articles" name="getaction" class="af-btn primary">
                 </div>
       </form></div>
					
      <!-- Table section -->

       <div class="af-card">
	   <div class="table-responsive">
					<div class="pull-right mb-2">
				  <label class="control-label" style="color: #222"><b> Selected Articles count</b> : </label>
				  <div class="btn btn-danger" id="a_count">0</div>
				  </div>

		<form action="<?php echo $_COOKIE['set_url'];?>/assigntovendor" id="assign_due_date_form" method="post" autocomplete="off">
		
	
											
											
              <table id="dynamic-table" class="table custtable table-primary" style="width: 100% !important; overflow:auto !important;text-wrap: nowrap !important;white-space: nowrap !important">
                <thead>
                  <tr>
                    <th class="center"> <label class="pos-rel">
                        <input type="checkbox" id="checkAll" name="checkAll" class="ace" />
                        <span class="lbl"></span> </label>
                    </th>                    
                    <th data-sortable="true" scope="col" class="thcolor">Journal</th>
                    <th data-sortable="true" scope="col" class="thcolor">Article</th>
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


		if($_REQUEST['cust_id']=='68')
		{
			$cnt_query = "SELECT COUNT(wd.id) as num FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND wd.stage = 'FP' and department_id in('2')  and (wd.allocate_to =0 OR allocate_to IS NULL)  ".$_SESSION['inpt_status_qry']." ".$_SESSION['varname']." ";
		     $query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage, wd.notes FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND wd.stage = 'FP' and department_id='2' and (wd.allocate_to =0 OR allocate_to IS NULL) ".$_SESSION['inpt_status_qry']."  ".$_SESSION['varname']."";

		}
		else
		{
				$cnt_query = "SELECT COUNT(wd.id) as num FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND wd.stage = 'FP' and department_id in('1','22','32')  and (wd.allocate_to =0 OR allocate_to IS NULL)  ".$_SESSION['inpt_status_qry']." ".$_SESSION['varname']." ";
		    $query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage, wd.notes FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND wd.stage = 'FP' and department_id in ('1','32') and (wd.allocate_to =0 OR allocate_to IS NULL) ".$_SESSION['inpt_status_qry']."  ".$_SESSION['varname']."";
		}

		$customers = $db->query($query) or ($db->error);				
		while($row = $customers->fetch_assoc()){ 
		?>
                  <tr <?php if($row['status'] == 'Query' || $row['status'] == 'Hold'){ ?> class="alert-danger" <?php } ?>>
                    <td class="center"><label class="pos-rel">
                        <input type="checkbox" class="ace aids" name="aids[]" value="<?php echo $row['pub_id']; ?>" />
                        <span class="lbl"></span> </label></td>                   
                    <td><?php echo $row['j_code']; ?></td>
                    <td class="hidden-480"><?php echo $row['pub_id']; echo ($row['notes']!='') ? ' <span class="label label-sm label-info">'.substr($row['notes'],0,10).'</span>' : '';?></td>
                    <td><?php echo (date('d-m-Y',strtotime($row['recv_dt']))); ?></td>
                    <td><?php echo (date('d-m-Y',strtotime($row['due_dt']))); ?></td>
                    <td><?php echo $row['manuscript_count']; ?></td>
                    <td><?php echo $row['fig']; ?></td>
                    <td class="hidden-480"><?php echo $row['tab']; ?></td>
                    <td class="hidden-480"><?php echo $row['stage']; ?></td>
                    <td class="hidden-480"><span class="label label-sm label-success"><?php echo $row['status']; ?></span></td>
                   
                  </tr>
                  <?php } ?>
				  
                <input type="text" name="pub_id" id="pub_id" value="">
				
                </tbody>
              </table>
			  
			   <div style="display: flex; gap:50px; align-items: end">
			   
									
		<div class="col-12 col-sm-6 col-md-3 col-lg-2">

                            <label class="control-label col-xs-12 col-sm-4 no-padding-right" for="name" style="color: #000">Select Freelancer:</label>
	               <?php
    $query = "SELECT full_name,id FROM `users` where `user_level` = 4  ORDER BY `full_name` ASC ";
    $users = $db->query($query) or ($db->error);		
    
    ?>
	<select id="user_id" name="user_id" class="form-select"  data-placeholder="Choose"   required>
    <option value="">-Select-</option>
    <?php while($row = $users->fetch_assoc()){ ?>
    <option value="<?php echo $row['id']; ?>" <?php if($_REQUEST['user_id'] ==$row['id']){?> selected <?php } ?>><?php echo $row['full_name']; ?></option>
    <?php } ?>
    </select>
	</div>
                              
								<input type="hidden" name="dep_id" id="" value="1">
								<input type="hidden" name="cust_id" id="" value="<?php echo $_REQUEST['cust_id'];?>">
								
       
							<div class="form-group">
                            <label class="control-label col-xs-12 col-sm-4 no-padding-right" for="name" style="color: #000">Instructions:</label>
                            <div class="col-xs-12">
                              <div class="input-group">
                                <textarea name="message" id="message" class="col-xs-12 col-sm-12"></textarea>
							</div>
                            </div>
                          </div>

					    <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-4 no-padding-right" for="name" style="color: #000">Due Date:</label>
                            <div class="col-xs-12">
                              <div class="input-group">
                                <input type="text" id="ce_pe_due_date" name="ce_pe_due_date" class="required col-xs-12 col-sm-12 date-picker" value="" style="width: 70%">
                                <span class="input-group-addon"> <i class="ace-icon fa fa-calendar bigger-110"></i> </span> </div>
                            </div>
                          </div>
						  
                            <button type="submit" id="assign" value="Assign" name="assign" class="btn btn-sm btn-warning" style="height:40px">Assign Freelancer <i class="ace-icon fa fa-plus icon-on-right"></i></button>
                                    
			</div>
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
  <?php af_foot(); ?>
  </body>

</html>
