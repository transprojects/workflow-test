<?php include "includes/includes.php";
session_start();

if(isset($_POST['reassign']))
{
	//print_r($_POST);
	//print_r($_POST['aids']);exit;
	foreach($_POST['aids'] as $key => $a_id) {
		//echo $a_id; 
		if($a_id>0)
		{
			
			$assigned_user=mysqli_fetch_array(mysqli_query($db,"select full_name,department_id from users where id='".$_POST['re_user_id']."'"));
			$current_user_deptname=mysqli_fetch_array(mysqli_query($db,"select * from adm_dept_master where dept_code='".$assigned_user['department_id']."'"));
			
			$user_working_status = '<span class="label label-sm label-success">'.$current_user_deptname['dept_name'].'</span>|<span class="label label-sm label-info">'.$assigned_user['full_name'].'</span>|<span class="label label-sm label-warning">Assigned</span>';
			
			if($_REQUEST['dept_id']=='1' || $_REQUEST['dept_id']=='22')
			{
				$reassigned="`assigned_user_id` ='".$_POST['re_user_id']."',ce_pe_due_date='".date('Y-m-d',strtotime($_REQUEST['ce_pe_due_date']))."',user_working_status='".$user_working_status."'";
			}
			else
			{
				$reassigned="`assigned_od_user_id` ='".$_POST['re_user_id']."',od_due_date='".date('Y-m-d',strtotime($_REQUEST['ce_pe_due_date']))."',user_working_status='".$user_working_status."'";
			}
			
			
			
			$prj_update_query="UPDATE inw_inward_dtl SET $reassigned WHERE id = ".$a_id." "; 
			//echo $prj_update_query; exit;
			$db->query($prj_update_query);
			
		}
		
	}//exit;
	header("location:re-assigntouser?msg=Successfully Re-Allocated");
}

if(isset($_SESSION['inpt_status_qry']) || $_SESSION['inpt_status_qry'] =='' )
{
	$_SESSION['inpt_status_qry'] = " AND wd.status NOT IN ('Delivery','Client Review','Completed','Query','Hold') ";
}

if(isset($_REQUEST['getaction']) && $_REQUEST['getaction']!='')
{
	
$_SESSION['cust_id'] = $_POST['cust_id'];
$_SESSION['stage_id'] = $_POST['stage_id'];
$_SESSION['r_user_id'] = $_POST['r_user_id'];
$_SESSION['j_id'] = $_POST['j_id'];
$_SESSION['pub_id'] = $_POST['pub_id'];
$_SESSION['from_dt'] = $_POST['from_dt'];
$_SESSION['to_dt'] = $_POST['to_dt'];
$_SESSION['inpt_status'] = $_POST['inpt_status'];
$_SESSION['inpt_status_qry'] = " AND wd.status NOT IN ('Delivery','Client Review','Completed','Query','Hold') ";

if($_SESSION['inpt_status']!=''){
	if (in_array("Query", $_SESSION['inpt_status']) && in_array("Hold", $_SESSION['inpt_status']))
	{
		$_SESSION['inpt_status_qry'] = " AND wd.status NOT IN ('Delivery','Client Review','Completed') ";
	}
	else if(in_array("Query", $_SESSION['inpt_status']))
	{
		$_SESSION['inpt_status_qry'] = " AND wd.status NOT IN ('Delivery','Client Review','Completed','Hold') ";
	}
	else if(in_array("Hold", $_SESSION['inpt_status']))
	{
		$_SESSION['inpt_status_qry'] = " AND wd.status NOT IN ('Delivery','Client Review','Completed','Query') ";
	}
     
}
$var = '';
if($_SESSION['cust_id']!=''){
	
	if($_SESSION['cust_id']!="all")
	{
     $var.= "and c.id = '".$_SESSION['cust_id']."' ";
	}
	else
	{
     $var.= "";
	}
}
if($_SESSION['stage_id']!=''){
	
      $var.= "and stage LIKE '".$_SESSION['stage_id']."%' ";
}
if($_SESSION['r_user_id']!=''){
	
	if($_REQUEST['dep_id']==10 || $_REQUEST['dep_id']==14 )
	{
	$var.= "and assigned_od_user_id='".$_SESSION['r_user_id']."' ";
	}
	else
	{
      $var.= "and assigned_user_id='".$_SESSION['r_user_id']."' ";
	}
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
        <div div class="row pt-4 d-flex align-items-center flex-wrap">
          <div class="col-8 col-lg-10">
            <p class="articleheading ms-4">Manuscript User Assigned Report</p>
          </div>
        </div>
      </div>

      <div class="divouter mt-4">
        <form action="" name="articles" class="formouter" method="post">
					<div class="selectdiv row p-4 d-flex gap-2">
	
			 <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                <label class="mb-2">Choose Customer</label>
                <select class="form-select customer_drop" data-placeholder="Select Customer" name="cust_id" id="cust_id">
										  <?php
										  if($_COOKIE['user_level']=='1')
										  {
											$sql_customers = $auth->showCustomerlist();
										  }
										  else
										  {
											$sql_customers = $auth->showCustomerlistbyuser($_COOKIE['cust_id'],$_COOKIE['ocust_id']);
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
			  
			   <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                <label class="mb-2">Choose Department</label>
               <?php
    $query = "SELECT dept_name,dept_code FROM `adm_dept_master` WHERE dept_code in('1','2','10','14')  ORDER BY `dept_name` ASC ";
    $customers = $db->query($query) or ($db->error);		
    
    ?><select id="dep_id" name="dep_id" class="form-control"  data-placeholder="Choose" required>
    <?php while($row = $customers->fetch_assoc()){ ?>
    <option value="<?php echo $row['dept_code']; ?>" <?php if($_REQUEST['dep_id'] ==$row['dept_code']){?> selected <?php } ?>><?php echo $row['dept_name']; ?></option>
    <?php } ?>
    </select>
              </div>
			  
			  <div class="col-12 col-sm-6 col-md-3 col-lg-2">
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
<option value="<?php echo $row_u['id'];?>" <?php if($logged_users==0){?> style="color:red;" <?php }else{?>style="color:green;"<?php } ?> <?php if($_SESSION['r_user_id'] ==$row_u['id']){?> selected <?php } ?>><?php echo $row_u['full_name']; ?></option>
    <?php
}
?>
    </select>
              </div>
			 
						</div>
						<div class="col-12 mt-2 d-flex justify-content-center align-items-center pb-4">
       <input type="submit" value="Get Articles" name="getaction" class="resultbtn btn btn-info btn-sm">
                 </div>
					
       </form>
              </div>
      <!-- Table section -->

       <div class="container-fluid  custom-container p-4" style="box-shadow: 0px 0px 16px 0px rgba(164, 164, 171, 0.25);border-radius: 24px;">
	   <div class="container-fluid table-responsive" style="overflow: auto">
		

		<form action="manuscriptuserassigned" id="assign_due_date_form" method="post" autocomplete="off">
		
		
											
			<?php 
if(isset($_REQUEST['r_user_id'])) {
?>						
              <table id="dynamic-table" class="table custtable table-primary" style="width: 100% !important; overflow:auto !important;text-wrap: nowrap !important;white-space: nowrap !important">
                <thead>
                  <tr>
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
		
			$cnt_query = "SELECT COUNT(wd.id) as num FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  and department_id in('1','2','10','14')  and (assigned_user_id IS NOT NULL or vendor_id IS NOT NULL)  ".$_SESSION['inpt_status_qry']." ".$_SESSION['varname']." ";
			
			
				 $query = "SELECT wd.department_id,wd.assign_department_id,c.cust_name,journal_title,fig_complete_status,pub_id,recv_dt,due_dt,target_due_dt,ce_pe_due_date,highpriority,wd.created_dt,wd.issueorgroup_folder,fig,tab,status,approved_by,wd.id,j.j_code,j.jt_id,j.j_division,manuscript_count,stage,work_dir,r.received_date,r.due_date,r.correction_pages,wd.notes,aq_status ,wd.user_working_status,wd.assigned_user_id,wd.vendor_id,wd.page_count FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND department_id='".$_REQUEST['dep_id']."' and (assigned_user_id IS NOT NULL or vendor_id IS NOT NULL) ".$_SESSION['inpt_status_qry']."  ".$_SESSION['varname']."";
   
}
else
{
			$cnt_query = "SELECT COUNT(wd.id) as num FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id and department_id in('1','2','10','14')  and assigned_od_user_id IS NOT NULL  ".$_SESSION['inpt_status_qry']." ".$_SESSION['varname']." ";
			
		     $query = "SELECT wd.department_id,wd.assign_department_id,c.cust_name,journal_title,fig_complete_status,pub_id,recv_dt,due_dt,target_due_dt,ce_pe_due_date,highpriority,wd.created_dt,wd.issueorgroup_folder,fig,tab,status,approved_by,wd.id,j.j_code,j.jt_id,j.j_division,manuscript_count,stage,work_dir,r.received_date,r.due_date,r.correction_pages,wd.notes,aq_status ,wd.user_working_status,wd.assigned_od_user_id,wd.page_count FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id and department_id='".$_REQUEST['dep_id']."' and assigned_od_user_id IS NOT NULL ".$_SESSION['inpt_status_qry']."  ".$_SESSION['varname']." ";
}

//echo  $query;
	
		$customers = $db->query($query) or ($db->error);				
		while($row = $customers->fetch_assoc()){ 
		
		//echo "select * from users where id='".$row['assigned_user_id']."'";
		if($_REQUEST['dep_id']==1 || $_REQUEST['dep_id']==22)
		{
		$assigned_user=mysqli_fetch_array(mysqli_query($db,"select * from users where (id='".$row['assigned_user_id']."' or id='".$row['vendor_id']."')"));
		}
		else
		{
		$assigned_user=mysqli_fetch_array(mysqli_query($db,"select * from users where (id='".$row['assigned_od_user_id']."' or id='".$row['vendor_id']."')"));
		}
		$stage_r=preg_replace("/REV([0-9]+)/","REV",$row['stage']);
			$stage_i=preg_replace("/ISSCOR([0-9]+)/","ISSCOR",$row['stage']);
			
			
						 if($row['stage'] == "FP")
		{
			if($_REQUEST['department_id'] == '1' || $_REQUEST['department_id'] == '2')
			{
			$due_dt = (date('d-m-Y',strtotime($row['ce_pe_due_date'])));
			}
			else
			{
			$due_dt = (date('d-m-Y',strtotime($row['target_due_dt'])));
			}
			$recv_dt = (date('d-m-Y',strtotime($row['recv_dt'])));
			$manuscript_count = !empty($row['page_count'])?$row['page_count']:
                          (!empty($row['manuscript_count'])?$row['manuscript_count']:rand(9,20));		}
		elseif($stage_r== "REV")
		{
			$due_dt = (date('d-m-Y',strtotime($row['due_date'])));
			$recv_dt = (date('d-m-Y',strtotime($row['received_date'])));
			$manuscript_count = !empty($row['page_count'])?$row['page_count']:
                          (!empty($row['manuscript_count'])?$row['manuscript_count']:rand(9,20));		}
		elseif($stage_i=='ISSCOR')
		{
			$due_dt = (date('d-m-Y',strtotime($row['due_dt'])));
			$recv_dt = (date('d-m-Y',strtotime($row['created_dt'])));
			$manuscript_count = !empty($row['page_count'])?$row['page_count']:
                          (!empty($row['manuscript_count'])?$row['manuscript_count']:rand(9,20));		}
		else
		{
			$due_dt = (date('d-m-Y',strtotime($row['due_date'])));
			$recv_dt = (date('d-m-Y',strtotime($row['received_date'])));
			$manuscript_count = !empty($row['page_count'])?$row['page_count']:
                          (!empty($row['manuscript_count'])?$row['manuscript_count']:rand(9,20));
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
			   
			

                </tbody>
              </table>
			  </form>
			<?php } ?>

		
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
</body>
</html>
