<?php 
 include "includes/includes.php";
?>
<?php
if(isset($_POST['action']) && $_POST['action']=='Start_to_Auto_Pagination')
{
	
	$update_vi="UPDATE inw_volume_issues SET `automation_process` ='0', automation_run_from='".$_REQUEST['automation_run_from']."', `automation_status` ='The Automation Process in Queue' WHERE vi_id = '".$_REQUEST['vi_id']."'";
	$db->query($update_vi);
	header("location:show_issues.php?id=".$_REQUEST['vi_id']."&msg=Successfully updated");
			
}
if(isset($_REQUEST['id']) && $_REQUEST['id']!='' && $_REQUEST['action']=='delete')
{	
	$db->query("delete from inw_inward_dtl where id='".$_REQUEST['id']."'");
	header("location:projects.php?msg=Successfully completed");
	die();	
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

	<style>
	.dataTables_wrapper .dataTables_filter{
        margin-bottom : 10px !important;
      }
    a{
        font-size : 0.925em !important;
    }
	</style>
  </head>

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>


	 <div class="container-fluid" style="justify-content: space-between;">
        <div div class="row pt-4 d-flex align-items-center flex-wrap">
          <div class="col-8 col-lg-10">
            <p class="articleheading ms-4">Issue Details</p>
          </div>
         
        </div>
      </div>

      <div class="divouter mt-4">
        <div class="span12">
        <?php
        if(!empty($_REQUEST['msg']))  {
        echo '<div class="alert alert-success">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        '. $_REQUEST['msg'] .'</div>';   

        } ?> 
        </div>
      </div>
        
        <div class="row">
          <div class="col-xs-12 formouter"> 
		  <div class="p-4" style="display: flex; justify-content: center; align-items: center;text-align: center">
		  <?php
					//// Get volume issues Details///	
			$query = "SELECT * FROM `inw_volume_issues` WHERE vi_id = '".$_REQUEST['id']."';";
			$steps = $db->query($query) or ($db->error);
			$voliss_info = $steps->fetch_assoc();


				  ?>
										<label class="col-sm-3 control-label no-padding-right">Issue: Vol<?php echo $voliss_info['volume_number'].$voliss_info['issue_number'];?></label>
										<label class="col-sm-3 control-label no-padding-right">Received Date: <?php echo $voliss_info['received_date']; ?></label>
										<label class="col-sm-3 control-label no-padding-right">Due Date: <?php echo $voliss_info['due_date']; ?></label><?php
							$work_dir = $voliss_info['work_dir'];
							$work_dir = str_replace(WF_DIR,TOOL_DIR,$work_dir);
							if($voliss_info['vi_id'] > 0)
							{
							?>
                            <div class="col-sm-3">
                            Upload Files to :
                             <a class="btn btn-primary" href="file:///<?php echo str_replace('production','downloads',$work_dir)."/".$voliss_info['stage']."/misc"; ?>" target="_blank">
												<i class="ace-icon fa fa-folder align-top bigger-125 pt-1"></i>
												MISC
											</a>
                                            </div>
                                            
                            <?php } ?>
							</div>
									 
                          <div class="hr hr-dotted"></div>
						   <div class="space-2"></div>
							<div class="space-2"></div>
						    <div class="p-4" style="display: flex; justify-content: center; align-items: center;">		
							<label class="pe-4 control-label"><b>Automation Status:</b>&#160;&#160; <?php echo $voliss_info['automation_status'];?></label>
							<form class="form-horizontal" id="validation-form" method="post">
								<button type="submit" id="Start_to_Auto_Pagination" value="Start_to_Auto_Pagination" class="btn btn-success"><i class="ace-icon fa  fa-cogs fa-w-20 fa-spin fa-lg"></i> Start to Auto Pagination</button>
								<input type="hidden" name="action" value="Start_to_Auto_Pagination">
								<input type="hidden" name="vi_id" value="<?php echo $voliss_info['vi_id'];?>">
								<input type="hidden" id="automation_run_from" name="automation_run_from" value="<?php echo $voliss_info['automation_run_from'];?>">
							</form>
							</div>
									 <div class="space-2"></div>
                          <div class="hr hr-dotted"></div>
						   <div class="space-2"></div>
		  </div>
		  

		<div class="container-fluid  custom-container p-4 mt-5" style="box-shadow: 0px 0px 16px 0px rgba(164, 164, 171, 0.25);border-radius: 24px;">
      	<div class="container-fluid table-responsive" style="overflow: auto">	
       <table id="dynamic-table" class="table custtable table-primary" style="width: 100% !important; overflow:auto !important;    text-wrap: nowrap !important">
                <thead>
                  <tr>
                    <th class="center"> <label class="pos-rel">
                        <input type="checkbox" class="ace" />
                        <span class="lbl"></span> </label>
                    </th>
                    <th>Seq.no</th>
                    <th>Customer</th>
					<th>Journal</th>
                    <th>Article</th>                   
                   
					<th class="hidden-480">Manuscript</th>
                    <th class="hidden-480">Fig</th>
					<th class="hidden-480">Table</th>
                    <th class="hidden-480">Stage</th>
                    <th class="hidden-480">Status</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php
		if($_COOKIE['user_level'] != 1) 
		{		  
			if($_COOKIE['department_id'] == '16')
			{
				$query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,work_dir,wd.v_issue_sequence_id,wd.automation_status FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.approved_by <= 0 and wd.cust_id = c.id and wd.j_id = j.j_id AND wd.v_issue_id ='".$_REQUEST['id']."' AND wd.status NOT IN ('Delivery','Client Review','Completed') ORDER BY `v_issue_sequence_id` ASC ";
			}
			else if($_COOKIE['department_id'] == 9)
			{
				$query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,work_dir,wd.v_issue_sequence_id,wd.automation_status FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.approved_by > 0 and wd.cust_id = c.id and wd.j_id = j.j_id AND (wd.fig_complete_status = 0 OR ( FIND_IN_SET(department_id,'".$_COOKIE['department_id'].",".$_COOKIE['odepartment_id']."'))) AND fig > 0  AND wd.v_issue_id ='".$_REQUEST['id']."'  AND wd.status NOT IN ('Delivery','Client Review','Completed') ORDER BY `v_issue_sequence_id` ASC ";
			}
			else
			{
				$query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,work_dir,wd.v_issue_sequence_id,wd.automation_status FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND  FIND_IN_SET(department_id,'".$_COOKIE['department_id'].",".$_COOKIE['odepartment_id']."')  AND wd.v_issue_id ='".$_REQUEST['id']."'  AND wd.status NOT IN ('Delivery','Client Review','Completed') ORDER BY `v_issue_sequence_id` ASC ";
			}
		}
		else
		{
			 $query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,work_dir,wd.v_issue_id,wd.v_issue_sequence_id,wd.automation_status FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  AND wd.v_issue_id ='".$_REQUEST['id']."' AND wd.status NOT IN ('Delivery','Client Review','Completed') ORDER BY `v_issue_sequence_id` ASC ";
		}
		//echo $query;
		$customers = $db->query($query) or ($db->error);				
		while($row = $customers->fetch_assoc()){ 
		$tr_class = '';
		if(date('d-m-Y') > (date('d-m-Y',strtotime($row['due_dt']))) && $row['highpriority'] == 1)
		{
			$tr_class = 'alert alert-warning';
		}
		else if(date('d-m-Y') > (date('d-m-Y',strtotime($row['due_dt']))))
		{
			$tr_class = 'alert alert-danger';
		}
		else if($row['highpriority'] == 1)
		{
			$tr_class = 'alert alert-info';
		}
		$trans_sql = "SELECT u.full_name,t.current_status,t.id FROM `inw_transactions` as t,users as u WHERE t.process_user = u.id AND t.`project_id` = '".$row['id']."'   AND t.current_status NOT IN ('Completed','Take Over') AND t.stage = '".$row['stage']."' AND ('Completed' != (select current_status from `inw_transactions` where `project_id` = '".$row['id']."' ORDER BY `id` DESC limit 1) )  ORDER BY t.`id` DESC limit 1;";
		//echo $trans_sql.'<br>';		
		$chk_transaction = $db->query($trans_sql); 
		$transaction_toatl_count=$chk_transaction->num_rows;
		$trans_res = $chk_transaction->fetch_assoc();		
		?>
                  <tr class="<?php echo $tr_class; ?>">
                    <td class="center"><label class="pos-rel">
                        <input type="checkbox" class="ace" />
                        <span class="lbl"></span> </label></td>
                    <td><?php echo $row['v_issue_sequence_id']; ?></td>
                    <td><?php echo $row['cust_name']; ?></td>
					<td><?php echo $row['j_code']; ?></td>
                    <td class="hidden-480"><?php echo $row['pub_id']; ?></td>                   
					 <td><?php echo $row['manuscript_count']; ?></td>
                     <td><?php echo $row['fig']; ?></td>
                    <td><?php echo $row['tab']; ?></td>
                    <td><?php echo $row['stage']; ?></td>
                    <td><span class="label label-sm label-success"><?php echo $row['status']; ?></span> <?php if($transaction_toatl_count > 0){ echo '<span class="label label-sm label-info">'.$trans_res['full_name'].'</span>|<span class="label label-sm label-warning">'.$trans_res['current_status'].'</span>'; } ?></td>
                    <td><div class="hidden-sm hidden-xs action-buttons">
                    <?php
					if($row['approved_by']>0){
						if($_COOKIE['department_id'] == 9){
					?>
					<?php 
					if($row['last_transaction_id']!='')
			{
				
				echo $start='<a title="Start work" style="font-size: 0.945em" class="btn btn-xs btn-primary" href="'.$_COOKIE['set_url'].'/graphicstartwork/'.$row['id'].'/'.$row['last_transaction_id'].'">
			<i class="ace-icon fa fa-tasks bigger-120"></i>&nbspStart</a>';
			
			}
			else
			{
				echo $start='<a title="Start work" style="font-size: 0.945em" class="btn btn-xs btn-primary" href="'.$_COOKIE['set_url'].'/graphicstartwork/'.$row['id'].'/new">
				<i class="ace-icon fa fa-tasks bigger-120"></i>&nbspStart</a>';	
			}
				?>
                                                            <?php } else { ?>
															
															<?php if($row['status'] != "Delivery"){?>
															 <a onClick="return check_work_status('<?php echo $row['id']; ?>','<?php echo $trans_res['id']; ?>');" class="btn btn-xs btn-primary" href="javascript:void(0);">
																<i class="ace-icon fa fa-tasks bigger-120"></i>&nbsp;Start
															</a> 
					<?php } ?>
					
                                                           
                                                            <?php } ?>
                    <?php } ?>
                    <a class="btn btn-danger btn-xs" href="send_instructions.php?id=<?php echo $row['id']; ?>">
												<i class="ace-icon fa fa-reply icon-only"></i>
											</a>
                                            <a class="btn btn-xs btn-warning" href="sendmail_client.php?id=<?php echo $row['id']; ?>">
												<i class="ace-icon fa fa-envelope"></i>
											</a>
                   
                       
                     <?php
					if($_COOKIE['user_level'] == 1){ ?>                                     <button class="btn btn-xs btn-info">
																<i class="ace-icon fa fa-clock-o bigger-120"></i>
															</button>  
                                                            <?php } ?>
                                                             <?php
															 $num = preg_replace("/([a-zA-Z])/","",$row['stage']);
					//if(($_COOKIE['department_id'] == 16 || $_COOKIE['user_level'] == 1) && $num > 1 ){ 
					if($num > 1 ){ ?>
                  
                   <a class="btn btn-xs btn-success" href="skip_article.php?id=<?php echo $row['id'];  ?>&sid=<?php echo $_REQUEST['id']; ?>"> <i class="ace-icon fa fa-fast-forward bigger-120"></i></a>
                   
                     <?php }?>
                     
                     <?php
															
					if($_COOKIE['department_id'] == 16 || $_COOKIE['user_level'] == 1){ ?>
                  
                   <a title="Upload multiple files" class="btn btn-xs btn-pink" href="<?php echo $_COOKIE['set_url'];?>/uploadfilesissue/<?php echo $row['id']; ?>/<?php echo $row['v_issue_id']; ?>" > <i class="ace-icon fa  fa-cloud-upload bigger-130"></i> </a>
                   
                     <?php }?>
                                                            </div></td>
                  </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
            
            <!-- PAGE CONTENT ENDS --> 
          </div>
          <!-- /.col --> 
        </div>
        <!-- /.row --> 
      
      <!-- /.page-content --> 
  
  <!-- /.main-content -->
  
 <!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Comments/Instructions</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
      <p id="ppopup"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <!-- <button type="button" class="btn btn-primary">Save changes</button> -->
      </div>
    </div>
  </div>
</div>
     <!-- Footer Section -->
	 
<?php echo footer(); ?>
<!-- Footer Section -->
<?php echo scripts(); ?>	
<!-- /.main-container --> 
</div>


<!-- inline scripts related to this page --> 
<script type="text/javascript">
			jQuery(function($) {
				//initiate dataTables plugin
				var myTable = 
				$('#dynamic-table').DataTable( {
					dom: "<'row'<'col-sm-4'l><'col-sm-4 text-center'B><'col-sm-4'f>>tp",
					//bAutoWidth: false,
					pageLength : 10,

					buttons: [
           	 {
            	    extend: 'collection', // Use a collection button for the dropdown
                text: 'Export', // The text for the main button
                buttons: [
                    'copyHtml5',
                    'excelHtml5',
                    'csvHtml5',
                    {
                        extend: 'pdfHtml5',
                        customize: function (doc) {
                            // Customize the PDF content here (optional)
                        }
                    },
                    {
                        extend: 'print',
                        autoPrint: false, // Disable automatic printing
                        customize: function (win) {
                            // Customize the printed content here (optional)
                        }
                    }
                ]
            }
         ],
	
					// dom: 'Bfrtip',
         	

			    });
			
				$("#Start_to_Auto_Pagination").click(function(e){
					 e.preventDefault();
					$automation_run_from = $("#automation_run_from").val();
					var form = $(this).parents('form');
					if($automation_run_from == 0 || $automation_run_from ==1)
					{
							form.submit();
					}
					else
					{
						
						
						swal({
							title: "Are you sure?",
							text: "PDF Compilation Run?",
							type: "warning",
							showCancelButton: true,
							confirmButtonClass: "btn-danger",
							confirmButtonText: "From Error",
							cancelButtonText: "Fromm Beginning",
							closeOnConfirm: false,
							closeOnCancel: false
						},
						function(isConfirm) 
						{
							if (isConfirm) 
							{
								swal("Thanks for your conformation");
								form.submit();
							}
							else 
							{
								$("#automation_run_from").val("1");
								swal("Thanks for your conformation");
								form.submit();
							}
						});

					}
					
		
				});
				
						
			})
			
function confirmDelete(delUrl) 
{
	if (confirm("Are you sure you want to delete")) 
	{
		document.location = delUrl;
	}
}
	
function check_work_status(id,tid)
			{	
			
			if(tid=='')
			{
				tid = 0;
			}
		
				$.ajax(
				{
					type:'POST',
					url:'<?php echo $_COOKIE['set_url'];?>/get_article_work_status.php',
					data:{id:id},
					success:function(result)
					{
						
						if(result == 0)
						{
							 var wid='issue';
							//window.location = 'project_work.php?id='+id;	
              window.location ='<?php echo $_COOKIE['set_url'];?>/startwork/'+id+'/'+tid;					
							return true;
						}
						else
						{
							bootbox.dialog(
							{
								message: '<br/><div class="alert alert-danger"> <strong> In Progress! </strong>	This Article currently in <strong>Progress.</strong> It is already started the work BY <strong>'+ result +'</strong></div>',
								buttons:
						{
							"success" :
							 {
								"label" : "<i class='ace-icon fa fa-check'></i> Take Over!",
								"className" : "btn-sm btn-success",
								"callback": function() {
									window.location = '<?php echo $_COOKIE['set_url'];?>/startwork/'+id+'/'+tid;					
									return true;
								}
							},
							"danger" :
							{
								"label" : "Override",
								"className" : "btn-sm btn-danger",
								"callback": function() {
									window.location = '<?php echo $_COOKIE['set_url'];?>/startwork/'+id+'/'+tid;					
									return true;
								}
							}, 
							"button" :
							{
								"label" : "Close",
								"className" : "btn-sm",
								"callback": function() {
									window.location.reload();				
									return true;
								}
							}							
						} 						
							});					
							return false;
						}
					}
				});
			
			}	
		</script>
</body>
</html>
