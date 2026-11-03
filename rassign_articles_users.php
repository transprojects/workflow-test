<?php include "includes/includes.php";
session_start();
include_once("includes/assign-modern.php");
$rqs = '';
if(!empty($_POST['return_qs'])) {
	parse_str($_POST['return_qs'], $rq_);
	$rq_ = array_intersect_key($rq_, array_flip(array('cust_id','stage_id','j_id','pub_id','from_dt','to_dt','user_id','dept_id','dep_id','r_user_id')));
	if($rq_) { $rqs = '&'.http_build_query($rq_); }
}

if(isset($_POST['reassign']))
{
	// An article cannot be re-assigned to the user who already holds it: check before changing anything.
	$od_dept = !($_REQUEST['dept_id']=='1' || $_REQUEST['dept_id']=='22' || $_REQUEST['dep_id']=='24' || $_REQUEST['dep_id']=='32');
	$owner_col = $od_dept ? 'assigned_od_user_id' : 'assigned_user_id';
	$same_owner = array();
	foreach((array)$_POST['aids'] as $chk_id) {
		if((int)$chk_id <= 0) { continue; }
		$chk = mysqli_fetch_array(mysqli_query($db, "select pub_id, $owner_col as owner_id from inw_inward_dtl where id=".(int)$chk_id));
		if($chk && (int)$chk['owner_id'] === (int)$_POST['re_user_id']) { $same_owner[] = $chk['pub_id']; }
	}
	if($same_owner) {
		header("location:re-assigntouser?msg=Already assigned to the selected user: ".implode(', ', $same_owner).". Nothing was changed.".$rqs);
		exit;
	}
	//print_r($_POST);
	//print_r($_POST['aids']);exit;
	foreach($_POST['aids'] as $key => $a_id) {
		//echo $a_id; 
		if($a_id>0)
		{
			
			$assigned_user=mysqli_fetch_array(mysqli_query($db,"select full_name,department_id from users where id='".$_POST['re_user_id']."'"));
			$current_user_deptname=mysqli_fetch_array(mysqli_query($db,"select * from adm_dept_master where dept_code='".$assigned_user['department_id']."'"));
			
			$user_working_status = '<span class="label label-sm label-success">'.$current_user_deptname['dept_name'].'</span>|<span class="label label-sm label-info">'.$assigned_user['full_name'].'</span>|<span class="label label-sm label-warning">Assigned</span>';
			
			if($_REQUEST['dept_id']=='1' || $_REQUEST['dept_id']=='22' || $_REQUEST['dep_id']=='24' || $_REQUEST['dep_id']=='32')
			{
				$reassigned="`assigned_user_id` ='".$_POST['re_user_id']."',ce_pe_due_date='".date('Y-m-d',strtotime(isset($_REQUEST['target_due_dt']) ? $_REQUEST['target_due_dt'] : $_REQUEST['ce_pe_due_date']))."',user_working_status='".$user_working_status."'";
			}
			else
			{
				$reassigned="`assigned_od_user_id` ='".$_POST['re_user_id']."',od_due_date='".date('Y-m-d',strtotime(isset($_REQUEST['target_due_dt']) ? $_REQUEST['target_due_dt'] : $_REQUEST['ce_pe_due_date']))."',user_working_status='".$user_working_status."'";
			}
			
			
			
			$prj_update_query="UPDATE inw_inward_dtl SET $reassigned WHERE id = ".$a_id." "; 
			//echo $prj_update_query; exit;
			$db->query($prj_update_query);
			
		}
		
	}//exit;
	header("location:re-assigntouser?msg=Successfully Re-Allocated".$rqs);
}

// Filters come from the query string only (nothing is kept in the session). No filter = everything.
$flt = am_request_filters();
$f_status = am_status_sql($flt['inpt_status']);
$f_var = am_where($db, $flt, 'stage');
$_REQUEST['dep_id'] = preg_replace('/\D/', '', (string)$flt['dep_id']);
if($_REQUEST['dep_id']==='') { $_REQUEST['dep_id'] = '1'; }
$flt['dep_id'] = $_REQUEST['dep_id'];
if($_COOKIE['user_level']=='3') {
	$am_cust = array();
	foreach($auth->showCustomerlistbyuser($_COOKIE['cust_id'],$_COOKIE['ocust_id']) as $am_r) { $am_cust[] = (int)$am_r['id']; }
	$f_var .= $am_cust ? " and c.id in (".implode(',', $am_cust).") " : " and 1=0 ";
}
if($flt['r_user_id']!=='') {
	$f_var .= ($flt['dep_id']==10 || $flt['dep_id']==14) ? " and assigned_od_user_id='".intval($flt['r_user_id'])."' " : " and assigned_user_id='".intval($flt['r_user_id'])."' ";
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
  
    .alert-info {
    background-color: rgb(83, 237, 247) !important;
    border-color: #bce8f1 !important;
    color: #a94442 !important;
}

.alert-danger {
    background-color: rgba(263, 0, 0, 36.5)!important;
    border-color: #ebccd1 !important;
    color: #a94442 !important;
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
            <div><small>Re-allocation</small><h1>Re-Allocate Articles to PE/CE/PAG/PRQC Departments</h1></div>
          </div>
        </div>
      </div>

      <div class="af-page"><div class="af-wrap">
        <form action="" name="articles" class="formouter" method="get">
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
											$sql_customers = $auth->showCustomerlist();
										  }
											foreach($sql_customers as $res_customers)
											{
												
									      	if(isset($_REQUEST['cust_id']))
													{
										    	?>
										       <option <?php if ($flt['cust_id'] == $res_customers['id']) { echo ' selected="selected"'; } ?> value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
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
			  
			   <div class="af-field">
                <label class="mb-2">Choose Department</label>
               <?php
    $query = "SELECT dept_name,dept_code FROM `adm_dept_master` WHERE dept_code in('1','2','10','14','24','32')  ORDER BY `dept_name` ASC ";
    $customers = $db->query($query) or ($db->error);		
    
    ?><select id="dep_id" name="dep_id" class="form-control"  data-placeholder="Choose" required>
    <option value="">-Select-</option>
    <?php while($row = $customers->fetch_assoc()){ ?>
    <option value="<?php echo $row['dept_code']; ?>" <?php if($_REQUEST['dep_id'] ==$row['dept_code']){?> selected <?php } ?>><?php echo $row['dept_name']; ?></option>
    <?php } ?>
    </select>
              </div>
			  
			  <div class="af-field">
                <label class="mb-2">Choose Allocated User</label>
               <?php
//read data
 $query_user = "SELECT `id`,`user_name`,`full_name` FROM `users` WHERE department_id = '".$_REQUEST['dep_id']."' and  approved='1' ORDER BY `full_name` ASC ";
$users_r = $db->query($query_user) or ($db->error);
?><select id="r_user_id" name="r_user_id" class="form-control"  data-placeholder="Choose">
   <option value="">-Select-</option>
<?php		
while($row_u = $users_r->fetch_assoc())
{
$logged_users=mysqli_num_rows(mysqli_query($db,"select * from wf_token_auth where DATE_FORMAT(start_date,'%Y-%m-%d')='".date('Y-m-d')."' and username='".$row_u['user_name']."'"));

	?>   
<option value="<?php echo $row_u['id'];?>" data-online="<?php echo $logged_users>0 ? 1 : 0; ?>" <?php if($flt['r_user_id'] ==$row_u['id']){?> selected <?php } ?>><?php echo ($logged_users>0 ? '● ' : '○ ').$row_u['full_name']; ?></option>
    <?php
}
?>
    </select>
              </div>
			  <div class="af-field">
			  <label class="control-label" style="color: #222">Choose Status</label>
			  <div class="d-flex gap-3 mt-3">
					<div class="inline">
					<input type="checkbox" id="inpt_status" name="inpt_status[]" class="ace" value="Query" <?php if (in_array("Query", (array)($flt['inpt_status'] ?? []))) { echo 'checked'; } ?>  />
					<span class="lbl"> Query</span>
					</div>
					<div class="inline">
					<input type="checkbox" id="inpt_status" name="inpt_status[]" class="ace" value="Hold" <?php if (in_array("Hold", (array)($flt['inpt_status'] ?? []))) { echo 'checked'; } ?> />
					<span class="lbl"> Hold</span>
					</div>
				  </div>
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

		<form action="rassign_articles_users.php" id="assign_due_date_form" method="post" autocomplete="off">
		<input type="hidden" name="return_qs" value="<?php echo htmlspecialchars(http_build_query(array_filter(array_diff_key($flt, array('inpt_status'=>1)), 'strlen'))); ?>">
		
		
											
			<?php 
if(true) {
?>						
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


if($_REQUEST['dep_id']==1 || $_REQUEST['dep_id']==22)
{
		
			$cnt_query = "SELECT COUNT(wd.id) as num FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  and department_id in('1','2','10','14','24')  and assigned_user_id IS NOT NULL  ".$f_status." ".$f_var." ";
			
			
				 $query = "SELECT wd.department_id,wd.assign_department_id,c.cust_name,journal_title,fig_complete_status,pub_id,recv_dt,due_dt,target_due_dt,ce_pe_due_date,highpriority,wd.created_dt,wd.issueorgroup_folder,fig,tab,status,approved_by,wd.id,j.j_code,j.jt_id,j.j_division,manuscript_count,stage,work_dir,r.received_date,r.due_date,r.correction_pages,wd.notes,aq_status ,wd.user_working_status,wd.assigned_user_id FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND department_id='".$_REQUEST['dep_id']."' and assigned_user_id IS NOT NULL ".$f_status."  ".$f_var."";
   
}
else
{
			$cnt_query = "SELECT COUNT(wd.id) as num FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id and department_id in('1','2','32','10','14','24')  and assigned_od_user_id IS NOT NULL  ".$f_status." ".$f_var." ";
			
		     $query = "SELECT wd.department_id,wd.assign_department_id,c.cust_name,journal_title,fig_complete_status,pub_id,recv_dt,due_dt,target_due_dt,ce_pe_due_date,highpriority,wd.created_dt,wd.issueorgroup_folder,fig,tab,status,approved_by,wd.id,j.j_code,j.jt_id,j.j_division,manuscript_count,stage,work_dir,r.received_date,r.due_date,r.correction_pages,wd.notes,aq_status ,wd.user_working_status,wd.assigned_od_user_id FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id and department_id='".$_REQUEST['dep_id']."' and assigned_od_user_id IS NOT NULL ".$f_status."  ".$f_var." ";
}

//echo  $query;
	
		$customers = $db->query($query) or ($db->error);				
		while($row = $customers->fetch_assoc()){ 
		
		//echo "select * from users where id='".$row['assigned_user_id']."'";
		if($_REQUEST['dep_id']==1 || $_REQUEST['dep_id']==22 || $_REQUEST['dep_id']==32)
		{
		$assigned_user=mysqli_fetch_array(mysqli_query($db,"select * from users where id='".$row['assigned_user_id']."'"));
		}
		else
		{
		$assigned_user=mysqli_fetch_array(mysqli_query($db,"select * from users where id='".$row['assigned_od_user_id']."'"));
		}
		$stage_r=preg_replace("/REV([0-9]+)/","REV",$row['stage']);
			$stage_i=preg_replace("/ISSCOR([0-9]+)/","ISSCOR",$row['stage']);
			
			
						 if($row['stage'] == "FP")
		{
			if($_REQUEST['department_id'] == '1' || $_REQUEST['department_id'] == '2' || $_REQUEST['department_id'] == '32')
			{
			$due_dt = (date('d-m-Y',strtotime($row['ce_pe_due_date'])));
			}
			else
			{
			$due_dt = (date('d-m-Y',strtotime($row['target_due_dt'])));
			}
			$recv_dt = (date('d-m-Y',strtotime($row['recv_dt'])));
			$manuscript_count = $row['manuscript_count'];
		}
		elseif($stage_r== "REV")
		{
			$due_dt = (date('d-m-Y',strtotime($row['due_date'])));
			$recv_dt = (date('d-m-Y',strtotime($row['received_date'])));
			$manuscript_count = $row['correction_pages'];
		}
		elseif($stage_i=='ISSCOR')
		{
			$due_dt = (date('d-m-Y',strtotime($row['due_dt'])));
			$recv_dt = (date('d-m-Y',strtotime($row['created_dt'])));
			$manuscript_count = $row['correction_pages'];
		}
		else
		{
			$due_dt = (date('d-m-Y',strtotime($row['due_date'])));
			$recv_dt = (date('d-m-Y',strtotime($row['received_date'])));
			$manuscript_count = $row['correction_pages'];
		}
		$tr_class = '';
		
		$current_dateTimestamp1 = strtotime(date('d-m-Y')); 
		$Due_dateTimestamp2 = strtotime($due_dt); 
		if($row['highpriority'] == 1)
		{
			$tr_class = 'alert alert-info';
		}
		elseif(($row['status'] == 'Query') || ($row['status'] == 'Hold'))
		{
			$tr_class = 'alert alert-dark';
		}
		
		elseif($current_dateTimestamp1 > $Due_dateTimestamp2)
		{
			$tr_class = 'alert alert-danger';
		}
		if($row['user_working_status']=='<span class="label label-sm label-success">Client Review</span>' && $row['department_id']=='0')
{
		
				$current_status='<label class="label label-success ams_label">Client Review</label>';	
			
}
elseif($row['user_working_status']!='<span class="label label-sm label-success">Client Review</span>' && $row['user_working_status']!='')
{
		
				$current_status=$row['user_working_status']."".$aq_status;
			
}
else
			{
				$current_status='<label class="label label-success ams_label">'.$row['status'].'</label>';	
			}
		
		?>
                  <tr class="<?php echo  $tr_class;?>">
                    <td class="center"><label class="pos-rel">
                        <input type="checkbox" class="ace aids" name="aids[]" value="<?php echo $row['id']; ?>" data-owner="<?php echo (int)(isset($row['assigned_user_id']) ? $row['assigned_user_id'] : (isset($row['assigned_od_user_id']) ? $row['assigned_od_user_id'] : 0)); ?>" data-owner-name="<?php echo htmlspecialchars(isset($assigned_user['full_name']) ? $assigned_user['full_name'] : ''); ?>" />
                        <span class="lbl"></span> </label></td>                   
                    <td><?php echo $row['j_code']; ?></td>
                    <td class="hidden-480"><?php echo $row['pub_id']; echo ($row['notes']!='') ? ' <span class="label label-sm label-info">'.substr($row['notes'],0,10).'</span>' : '';?></td>
                    <td><?php echo $recv_dt; ?></td>
                    <td><?php echo $due_dt; ?></td>
                    <td><?php echo $manuscript_count; ?></td>
                    <td><?php echo $row['fig']; ?></td>
                    <td class="hidden-480"><?php echo $row['tab']; ?></td>
                    <td class="hidden-480"><?php echo $row['stage']; ?></td>
                    <td class="hidden-480"><?php echo $current_status; ?></td>
                   
                  </tr>
                  <?php } ?>
                

                </tbody>
              </table>
			    <div class="assign-panel ap-3">
	<div class="ap-head">
		<span class="ap-title">Re-assign to Another User</span>
		<span class="ap-count zero" id="ap_count"><b id="ap_count_n">0</b> article(s) selected</span>
	</div>
	<div class="ap-body">
		<div>
			<label class="ap-label" for="re_user_id">New user <i>*</i></label>
			<?php
//read data
 $query_user = "SELECT `id`,`user_name`,`full_name` FROM `users` WHERE department_id = '".$_REQUEST['dep_id']."' and id!='".$_REQUEST['r_user_id']."' and  approved='1' ORDER BY `full_name` ASC ";
$users_r = $db->query($query_user) or ($db->error);
?>
	<select id="re_user_id" name="re_user_id" required>
    <option value="">Select new user</option>
<?php		
while($row_u = $users_r->fetch_assoc())
{
		$logged_users=mysqli_num_rows(mysqli_query($db,"select * from wf_token_auth where DATE_FORMAT(start_date,'%Y-%m-%d')='".date('Y-m-d')."' and username='".$row_u['user_name']."'"));
	
	?>   
<option value="<?php echo $row_u['id'];?>" data-online="<?php echo $logged_users>0 ? 1 : 0; ?>" <?php if($flt['r_user_id'] ==$row_u['id']){?> selected <?php } ?>><?php echo ($logged_users>0 ? '● ' : '○ ').$row_u['full_name']; ?></option>
    <?php
}
?>
    </select>
		</div>
		<div>
			<label class="ap-label" for="target_due_dt">Due date <i>*</i></label>
			<div class="ap-datewrap">
				<input type="text" id="target_due_dt" name="target_due_dt" class="ap-date required date-picker" value="" placeholder="dd-mm-yyyy" autocomplete="off">
				<i class="ace-icon fa fa-calendar"></i>
			</div>
			<div class="ap-quick">
				<button type="button" data-days="1">Tomorrow</button>
				<button type="button" data-days="3">+3 days</button>
				<button type="button" data-days="7">+7 days</button>
			</div>
		</div>
		<div class="ap-go">
			<button type="submit" id="reassign" value="Re assign" name="reassign" disabled>Re-Assign <i class="ace-icon fa fa-plus icon-on-right"></i></button>
			<div class="ap-hint" id="ap_hint">Tick articles above to enable</div>
		</div>
	</div>
	
</div>
			  <input type="hidden" name="aa_count" id="aa_count" value="">
			  <input type="hidden" name="dept_id" id="dept_id" value="<?php echo $_REQUEST['dep_id'];?>">
			  </form>
			<?php } ?>

		
		</div>
      	</div>
              </div>


     <!-- Footer Section -->
     <?php echo footer(); ?>




<!-- Footer Section -->
<?php echo scripts(); ?>	
				
<!-- inline scripts related to this page --> 
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
        
    })
	
});


$(function () {
    $("#checkAll").click(function () {
        if ($("#checkAll").is(':checked')) {
            $(".aids").prop("checked", true);
			var checkboxes = $('input[name="aids[]"]:checked').length;	
			$("#a_count").html(checkboxes - 1);			
		$("#a_count").html(checkboxes);
        } else {
            $(".aids").prop("checked", false);
			$("#a_count").html(0);
        }
    });
});
			
function confirmDelete(delUrl) 
{
	if (confirm("Are you sure you want to delete")) 
	{
		document.location = delUrl;
	}
}		

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
		</script> 
<script type="text/javascript">
			$(document).ready(function(){
    $('#dep_id').on('change',function(){
        var dep_id = $(this).val();
        if(dep_id){
            $.ajax({
                type:'POST',
                url:'get_ruser.php',
                data:'dept_id='+dep_id,
                success:function(html){
                    $('#r_user_id').html(html);                    
                }
            }); 
        }else{
            $('#r_user_id').html('<option value="">Select User first</option>');
            
        }
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
