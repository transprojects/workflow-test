<?php include "includes/includes.php";
session_start();
?>
<?php
if(isset($_POST['assign']))
{
	
	//print_r($_POST);
	//print_r($_POST['aids']);exit;
	foreach($_POST['aids'] as $key => $a_id) {
		//echo $a_id; 
		if($a_id>0)
		{
			

			$assigned_user=mysqli_fetch_array(mysqli_query($db,"select full_name,department_id from users where id='".$_POST['user_id']."'"));
			$current_user_deptname=mysqli_fetch_array(mysqli_query($db,"select * from adm_dept_master where dept_code='".$assigned_user['department_id']."'"));
			
			$user_working_status = '<span class="label label-sm label-success">'.$current_user_deptname['dept_name'].'</span>|<span class="label label-sm label-info">'.$assigned_user['full_name'].'</span>|<span class="label label-sm label-warning">Assigned</span>';
			
			if($_REQUEST['dep_id']=='1' || $_REQUEST['dep_id']=='22' || $_REQUEST['dep_id']=='24' || $_REQUEST['dep_id']=='2' || $_REQUEST['dep_id']=='5' || $_REQUEST['dep_id']=='32')
			{
				$assigned="`assigned_user_id` ='".$_POST['user_id']."',ce_pe_due_date='".date('Y-m-d',strtotime($_REQUEST['ce_pe_due_date']))."',user_working_status='".$user_working_status."'";
			}
			else
			{
				
				$assigned="`assigned_od_user_id` ='".$_POST['user_id']."',od_due_date='".date('Y-m-d',strtotime($_REQUEST['ce_pe_due_date']))."',user_working_status='".$user_working_status."'";
			}
			
			$prj_update_query="UPDATE inw_inward_dtl SET $assigned WHERE id = ".$a_id." "; 
			//echo $prj_update_query; exit;
			$db->query($prj_update_query);
			
		}

	}//exit;
			
			$insert_reason=mysqli_query($db,"insert into assigned_article_user(`user_id`,`dept_id`,`total`,`a_date`,`cdate`) values ('".$_REQUEST['user_id']."','".$_REQUEST['dep_id']."','".$_REQUEST['aa_count']."','".date('Y-m-d',strtotime($_REQUEST['ce_pe_due_date']))."','".date('Y-m-d H:i:s')."')");

	header("location:".$_COOKIE['set_url']."/assigntouser?msg=Successfully Allocated");
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
if($_SESSION['cust_id']!='' && $_SESSION['cust_id']!='all'){
	
	
     $var.= "and c.id = '".$_SESSION['cust_id']."' ";
}
if($_SESSION['stage_id']!=''){
	
      $var.= "and wd.stage LIKE '".$_SESSION['stage_id']."%' ";
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
            <div><small>Allocation</small><h1>Allocate Articles to PE/CE/PAG/PRQC Departments</h1></div>
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
											$sql_customers = $auth->showCustomerlist();
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
			  
			   <div class="af-field">
                <label class="mb-2">Choose Department</label>
               <?php
    $query = "SELECT dept_name,dept_code FROM `adm_dept_master` WHERE dept_code in('1','2','5','10','14','24','32')  ORDER BY `dept_name` ASC ";
    $customers = $db->query($query) or ($db->error);		
    
    ?><select id="dept_id" name="dept_id" class="form-control"  data-placeholder="Choose" required>
    <option value="">-Select-</option>
    <?php while($row = $customers->fetch_assoc()){ ?>
    <option value="<?php echo $row['dept_code']; ?>" <?php if($_REQUEST['dept_id'] ==$row['dept_code']){?> selected <?php } ?>><?php echo $row['dept_name']; ?></option>
    <?php } ?>
    </select>
              </div>
			  
			   <div class="af-field">
                <label class="mb-2">Choose Stage</label>
            <select class="form-control" data-placeholder="Select Stage" name="stage_id" id="stage_id">
								    <option value="all">Select All</option>

                  <?php foreach($project_status_types as $id=>$val){?>
                          <?php 
                          if(isset($_REQUEST['stage_id']))
													{
                          ?>
                         <option value="<?php echo $id; ?>" <?php if($id==$_REQUEST['stage_id']){?>selected<?php }?> ><?php echo $val; ?></option>
                         <?php 
                          }
                          else
                          {
                          ?>
                         <option value="<?php echo $id; ?>" <?php if($val=="FirstProof"){?>selected<?php }?> ><?php echo $val; ?></option>

                          <?php 
                          }
                          ?>
                   <?php } ?>
                </select>
              </div>
			  
			  
			  
			  <div class="af-field">
			  <label class="control-label" style="color: #222">Choose Status</label>
			  <div class="d-flex gap-3 mt-3">
					<div class="inline">
					<input type="checkbox" id="inpt_status" name="inpt_status[]" class="ace" value="Query" <?php if (in_array("Query", (array)($_SESSION['inpt_status'] ?? []))) { echo 'checked'; } ?>  />
					<span class="lbl"> Query</span>
					</div>
					<div class="inline">
					<input type="checkbox" id="inpt_status" name="inpt_status[]" class="ace" value="Hold" <?php if (in_array("Hold", (array)($_SESSION['inpt_status'] ?? []))) { echo 'checked'; } ?> />
					<span class="lbl"> Hold</span>
					</div>
				  </div>
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

		<form action="assign_articles_users.php" id="assign_due_date_form" method="post" autocomplete="off">
		
	
											
											
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

if(isset($_REQUEST['getaction']))
{
	$var="and department_id='".$_REQUEST['dept_id']."' ".$_SESSION['inpt_status_qry']." ".$_SESSION['varname']."";
}
else
{
$var="and 1=0";

}
if($_REQUEST['dept_id']==1 || $_REQUEST['dept_id']==22 || $_REQUEST['dept_id']==32 || $_REQUEST['dept_id']==5)
{
		
		
			$cnt_query = "SELECT COUNT(wd.id) as num FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND wd.stage = 'FP' and department_id in('1','2','32','10','14')  and assigned_user_id IS NULL  and is_conversion='no' $var";
			
		     $query = "SELECT wd.department_id,wd.assign_department_id,c.cust_name,journal_title,fig_complete_status,pub_id,recv_dt,due_dt,target_due_dt,ce_pe_due_date,highpriority,wd.created_dt,wd.issueorgroup_folder,fig,tab,status,approved_by,wd.id,j.j_code,j.jt_id,j.j_division,manuscript_count,stage,work_dir,r.received_date,r.due_date,r.correction_pages,wd.notes,aq_status ,wd.user_working_status FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND wd.stage = 'FP' and assigned_user_id IS NULL and is_conversion='no' $var ";
}
else
{
			 
		   // $query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage, wd.notes FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id and assigned_od_user_id IS NULL AND (wd.stage ='FP' or wd.stage  LIKE '%REV%'   or wd.stage  LIKE '%FIN%') AND wd.stage  NOT LIKE '%ISSFIN1%' $var";
		   
		     $query = "SELECT wd.department_id,wd.assign_department_id,c.cust_name,journal_title,fig_complete_status,pub_id,recv_dt,due_dt,target_due_dt,ce_pe_due_date,highpriority,wd.created_dt,wd.issueorgroup_folder,fig,tab,status,approved_by,wd.id,j.j_code,j.jt_id,j.j_division,manuscript_count,stage,work_dir,r.received_date,r.due_date,r.correction_pages,wd.notes,aq_status,wd.user_working_status  FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id and assigned_od_user_id IS NULL AND (wd.stage ='FP' or wd.stage  LIKE '%REV%'   or wd.stage  LIKE '%FIN%') AND wd.stage  NOT LIKE '%ISSFIN1%' and (present_user_id=0 or present_user_id is NULL) and is_conversion='no' $var";
}
	
		$customers = $db->query($query) or ($db->error);				
		while($row = $customers->fetch_assoc()){ 
		
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
                        <input type="checkbox" class="ace aids" name="aids[]" value="<?php echo $row['id']; ?>" />
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
			  
			   <div style="display: flex; gap:50px; align-items: end">
			   
									
		<div class="col-12 col-sm-6 col-md-3 col-lg-2">

                            <label class="control-label col-xs-12 col-sm-4 no-padding-right" for="name" style="color: #000">Select Users:</label>
	               <?php
				   if($_REQUEST['dept_id']!="")
				   {
$dept_id = intval($_REQUEST['dept_id']); // Ensure it's an integer to prevent SQL injection

$query = "SELECT full_name, id, user_name 
          FROM `users` 
          WHERE (department_id = '$dept_id' OR FIND_IN_SET('$dept_id', access_other_depts)) 
          AND approved = '1' 
          ORDER BY full_name ASC";				  
		  }
				   else
				   {
    $query = "SELECT full_name,id,user_name FROM `users` where user_level=3 and  approved='1'  ORDER BY `full_name` ASC ";
				   }
    $users = $db->query($query) or ($db->error);		
    
    ?>
	<select id="user_id" name="user_id" class="form-select"  data-placeholder="Choose"   required>
    <option value="">-Select-</option>
    <?php while($row = $users->fetch_assoc()){
		$logged_users=mysqli_num_rows(mysqli_query($db,"select * from wf_token_auth where DATE_FORMAT(start_date,'%Y-%m-%d')='".date('Y-m-d')."' and username='".$row['user_name']."'"));
		?>
    <option value="<?php echo $row['id']; ?>" <?php if($logged_users==0){?> style="color:red;" <?php }else{?>style="color:green;"<?php } ?>><?php echo $row['full_name']; ?></option>
    <?php } ?>
    </select>
	</div>
                              
								<input type="hidden" name="dep_id" id="" value="<?php echo $_REQUEST['dept_id'];?>">
								
       

			<div class="form-group">
                            <label class="control-label col-xs-12 col-sm-4 no-padding-right" for="name" style="color: #000">Date:</label>
                            <div class="col-xs-12">
                              <div class="input-group">
                                <input type="text" id="ce_pe_due_date" name="ce_pe_due_date" class="required col-xs-12 col-sm-12 date-picker" value="" style="width: 70%">
                                <span class="input-group-addon"> <i class="ace-icon fa fa-calendar bigger-110"></i> </span> </div>
                            </div>
                          </div>
						  
                            <button type="submit" id="assign" value="Assign" name="assign" class="btn btn-sm btn-warning" style="height:40px">Assign <i class="ace-icon fa fa-plus icon-on-right"></i></button>
                                    
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
                url:'get_assigned_user.php',
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
		</script> 
  <?php af_foot(); ?>
  </body>

</html>
