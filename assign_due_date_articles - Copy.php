<?php include "includes/includes.php";
session_start();

if(isset($_POST['assign']))
{
	//print_r($_POST);
	$post['target_due_dt'] = date('Y-m-d',strtotime($_POST['target_due_dt']));
	foreach($_POST['aids'] as $key => $a_id) {
		if($a_id>0)
		{
			$prj_update_query="UPDATE inw_inward_dtl SET `target_due_dt` ='".$post['target_due_dt']."' WHERE id = ".$a_id." "; 
			//echo $prj_update_query; exit;
			$db->query($prj_update_query);
			
		}		
	}
	
	header("location:".$_COOKIE['set_url']."/assignduedate?msg=Successfully added");
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
	
     $var.= "and c.id = '".$_SESSION['cust_id']."' ";
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
            <p class="articleheading ms-4">Assign Due Date to Articles</p>
          </div>
        </div>
      </div>

      <div class="divouter mt-4">
        <form action="" name="articles" class="formouter" method="post">
					<div class="selectdiv row p-4 d-flex gap-2">
					

					
			
			
			  
			  
			  <h5 class="widget-title">Customer wise FP Articles count</h5>
							   <table class="table table-striped custtable table-primary wiptable">
               <tbody>
                  <tr>
                     <td class="pb-3" style="display: flex; flex-wrap: wrap; gap: 10px">
                        <?php
                           $sql_query =
                               "SELECT * FROM `adm_customer_master`";
                           ($run_row = $db->query($sql_query)) or die($db->error);
                           while ($row = $run_row->fetch_assoc()) {
                           
                              $fp_query = "SELECT count(id)as stage_count FROM `inw_inward_dtl` WHERE cust_id =".$row['id']." AND  stage = 'FP' AND `target_due_dt` IS NULL AND status NOT IN ('Delivery','Client Review','Completed','Query','Hold')  $searchfp $search_cy  $search_cm $search_cd ";
            $fp_run_query = $db->query($fp_query) or die($db->error);
            $fp_row = $fp_run_query->fetch_assoc();
            $fp_id_count = $fp_row['stage_count'];
                               if ($fp_id_count == 0) { ?>
                        <p class="col-md-2" style="color:<?php echo $row[
                           "font_color"
                           ]; ?> !important;flex-grow: 1;text-align: left;border-bottom: 1px solid #d3cdcd59;"> <?php echo $row['cust_name']; ?> - <?php echo  $fp_id_count; ?></p>	</p>
                        <?php }
                           ?>
                        <?php
                           }
                           ?>
                     </td>
                  </tr>
               </tbody>
            </table>
			<h5 class="widget-title">Get Customer wise FP Articles</h5>
			 <div class="col-12 col-sm-6 col-md-3 col-lg-2">
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
			   <div class="col-6 col-sm-6 col-md-3 col-lg-2">
			  <label class="control-label">Choose Status</label>
					<label class="inline">
					<input type="checkbox" id="inpt_status" name="inpt_status[]" class="ace" value="Query" <?php if (in_array("Query", $_SESSION['inpt_status'])) { echo 'checked'; } ?>  />
					<span class="lbl"> Query</span>
					</label>
					<label class="inline">
					<input type="checkbox" id="inpt_status" name="inpt_status[]" class="ace" value="Hold" <?php if (in_array("Hold", $_SESSION['inpt_status'])) { echo 'checked'; } ?> />
					<span class="lbl"> Hold</span>
					</label>
				  
				  
                  <input type="submit" value="Get Articles"  name="getaction" class="btn btn-info btn-sm">
				
					</div>
					
       </form>
              </div>

      <!-- Table section -->

       <div class="container-fluid  custom-container p-4" style="box-shadow: 0px 0px 16px 0px rgba(164, 164, 171, 0.25);border-radius: 24px;">
	   <div class="container-fluid table-responsive" style="overflow: auto">
					<div class="pull-right">
				  <label class="control-label"><b> Selected Articles count</b> : </label>
				  <div class="btn btn-danger" id="a_count">0</div>
				  </div>

		<form action="assign_due_date_articles.php" id="assign_due_date_form" method="post" autocomplete="off">
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

		
			$cnt_query = "SELECT COUNT(wd.id) as num FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND wd.stage = 'FP' AND wd.`target_due_dt` IS NULL  ".$_SESSION['inpt_status_qry']." ".$_SESSION['varname']." ";
			   $query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage, wd.notes FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND wd.stage = 'FP' AND wd.`target_due_dt` IS NULL ".$_SESSION['inpt_status_qry']."  ".$_SESSION['varname']."";
	
		$customers = $db->query($query) or ($db->error);				
		while($row = $customers->fetch_assoc()){ 
		?>
                  <tr <?php if($row['status'] == 'Query' || $row['status'] == 'Hold'){ ?> class="alert-danger" <?php } ?>>
                    <td class="center"><label class="pos-rel">
                        <input type="checkbox" class="ace aids" name="aids[]" value="<?php echo $row['id']; ?>" />
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
                
				
                </tbody>
              </table>
			  
			  <table>
			    <tr><td colspan="6"> <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-4 no-padding-right" for="name">Date:</label>
                            <div class="col-xs-12 col-sm-4">
                              <div class="input-group">
                                <input type="text" id="target_due_dt" name="target_due_dt" class="required col-xs-12 col-sm-12 date-picker" value="" />
                                <span class="input-group-addon"> <i class="ace-icon fa fa-calendar bigger-110"></i> </span> </div>
                            </div>
                          </div></td>
						  <td colspan="4">
						  
                            <button type="submit" id="assign" value="Assign" name="assign" class="btn btn-sm btn-warning">Assign <i class="ace-icon fa fa-plus icon-on-right"></i></button>
                          
					</td>
				</tr>
			  </table>
			  </form>
		
		</div>
      	</div>


     <!-- Footer Section -->
     <?php echo footer(); ?>




<!-- Footer Section -->
<?php echo scripts(); ?>	

   
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
    $('#cust_id').on('change',function(){
        var j_cust_id = $(this).val();
        if(j_cust_id){
            $.ajax({
                type:'POST',
                url:'get_journals.php',
                data:'j_cust_id='+j_cust_id,
                success:function(html){
                    $('#j_id').html(html);                    
                }
            }); 
        }else{
            $('#j_id').html('<option value="">Select customer first</option>');
            
        }
    });

	
	});
	
			
		</script>
  </body>
</html>
