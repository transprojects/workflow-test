<?php 
 include "includes/includes.php";
 include_once 'includes/issue_drafts.php';
 $idr_art = idr_article_map(idr_runner_mysqli($db));
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
    <?php include_once 'includes/modern-ui.php'; include_once 'includes/article-filter-ui.php'; mu_head(); af_head(); idr_head(); ?>

    <!-- Data Table CSS- -->

	<style>
    .ii-info{display:flex;flex-wrap:wrap;gap:10px;align-items:stretch;}
    .ii-info>div{padding:8px 14px;border:1px solid #e4e9ef;border-radius:10px;background:#f9fbfd;min-width:150px;}
    .ii-info small{display:block;font-size:10.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#6b7785;}
    .ii-info b{color:#14304a;font-size:14px;}
    .ii-info .ii-auto{flex:1 1 320px;display:flex;align-items:center;justify-content:space-between;gap:14px;}
    .ii-info .ii-auto b{font-weight:600;font-size:13px;}
    .ii-info .ii-grow{flex:0 0 auto;display:flex;align-items:center;gap:10px;background:transparent;border:0;margin-left:auto;padding-right:0;}
    .ii-misc{display:inline-flex;align-items:center;gap:7px;padding:8px 16px;border-radius:10px;background:#e8f1fb;border:1px solid #c5daef;color:#1b6ca8 !important;font-weight:700;text-decoration:none !important;}
    .ii-misc:hover{background:#1b6ca8;color:#fff !important;}
    #articles td:first-child,#articles th:first-child{width:34px;}
    /* legacy label-* pills in the status cell (user working status) */
    .af-card .label{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:700;line-height:1.3;color:#fff;background:#6b7785;}
    .af-card .label-success{background:#1e9e61;} .af-card .label-info{background:#d98a06;} .af-card .label-warning{background:#e8932a;} .af-card .label-danger{background:#d64545;}
    </style>
  </head>

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>


	 <?php
		//// Get volume issues Details///
		$query = "SELECT * FROM `inw_volume_issues` WHERE vi_id = '".(int)$_REQUEST['id']."';";
		$steps = $db->query($query) or ($db->error);
		$voliss_info = $steps->fetch_assoc();
		$idr_issue = idr_issue_map(idr_runner_mysqli($db));
		$work_dir = str_replace(WF_DIR, TOOL_DIR, $voliss_info['work_dir']);
	?>
    <div class="container-fluid" style="justify-content: space-between;">
      <div class="af-page" style="padding-bottom:0;">
        <div class="af-hero">
          <div><small>Issue details &middot; start work</small><h1>Vol<?php echo htmlspecialchars($voliss_info['volume_number'].$voliss_info['issue_number']); ?></h1></div>
          <a class="af-add" href="manage_issues.php" style="background:rgba(255,255,255,.16);box-shadow:none;border:1px solid rgba(255,255,255,.35);"><i class="fa fa-arrow-left"></i> Back to Manage Issues</a>
        </div>
      </div>
    </div>

    <?php if(!empty($_REQUEST['msg']))  {
      echo '<div class="alert alert-success"><button type="button" class="close" aria-label="Close">&times;</button>'. htmlspecialchars($_REQUEST['msg']) .'</div>';
    } ?>

    <div class="af-page"><div class="af-wrap"><div class="formouter">
      <div class="ii-info">
        <div><small>Issue</small><b>Vol<?php echo htmlspecialchars($voliss_info['volume_number'].$voliss_info['issue_number']); ?></b></div>
        <div><small>Received date</small><b><?php echo $voliss_info['received_date'] ? date('d-m-Y', strtotime($voliss_info['received_date'])) : '&ndash;'; ?></b></div>
        <div><small>Due date</small><b><?php echo $voliss_info['due_date'] ? date('d-m-Y', strtotime($voliss_info['due_date'])) : '&ndash;'; ?></b></div>
        <div><small>Draft status</small><b><?php echo idr_issue_cell($idr_issue, $voliss_info['vi_id']); ?></b></div>
        <?php
        $idr_wc = idr_withclient_map(idr_runner_mysqli($db));
        $with_client = isset($idr_wc[$voliss_info['vi_id']]) ? $idr_wc[$voliss_info['vi_id']] : 0;
        if($with_client > 0 && ($_COOKIE['department_id'] == 16 || $_COOKIE['user_level'] == 1)) { ?>
        <div class="ii-grow" style="margin-left:0;"><a class="ii-misc" style="background:#e3f6ec;border-color:#bfe8d2;color:#1e9e61 !important;" href="issue_draft_rebook.php?id=<?php echo (int)$voliss_info['vi_id']; ?>" title="Book articles that are with the client for corrections (DRAFT1, DRAFT2 ...)"><i class="fa fa-pencil"></i> Draft Correction <span style="opacity:.75;font-weight:600;">(<?php echo $with_client; ?> with client)</span></a></div>
        <?php } ?>
        <?php if($voliss_info['vi_id'] > 0) { ?>
        <div class="ii-grow"><span style="color:#6b7785;font-weight:600;">Upload files to</span>
          <a class="ii-misc" href="file:///<?php echo str_replace('production','downloads',$work_dir)."/".$voliss_info['stage']."/misc"; ?>" target="_blank"><i class="fa fa-folder-open"></i> MISC</a></div>
        <?php } ?>
        <div class="ii-auto" style="width:100%;flex-basis:100%;">
          <div><small>Automation status</small><b><?php echo htmlspecialchars($voliss_info['automation_status']); ?></b></div>
          <form class="form-horizontal" id="validation-form" method="post" style="margin:0;">
            <button type="submit" id="Start_to_Auto_Pagination" value="Start_to_Auto_Pagination" class="af-btn primary"><i class="fa fa-cogs"></i> Start to Auto Pagination</button>
            <input type="hidden" name="action" value="Start_to_Auto_Pagination">
            <input type="hidden" name="vi_id" value="<?php echo $voliss_info['vi_id'];?>">
            <input type="hidden" id="automation_run_from" name="automation_run_from" value="<?php echo $voliss_info['automation_run_from'];?>">
          </form>
        </div>
      </div>
    </div></div></div>

    <div class="af-page"><div class="af-card">
      	<div class="table-responsive">
       <table id="articles" class="table custtable table-primary idr-compact" style="width: 100% !important; text-wrap: nowrap !important">
                <thead>
                  <tr>
                    <th class="center"><input type="checkbox" id="chk_all" title="Select all on this page" /></th>
                    <th data-af-role="num">Seq.no</th>
                    <th data-af-role="client">Customer</th>
                    <th data-af-role="journal">Journal</th>
                    <th data-af-role="file">Article</th>
                    <th data-af-role="num">Manuscript</th>
                    <th data-af-role="num">Fig</th>
                    <th data-af-role="num">Table</th>
                    <th data-af-role="stage">Stage</th>
                    <th data-af-role="none">Status</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php
		if($_COOKIE['user_level'] != 1) 
		{		  
			if($_COOKIE['department_id'] == '16')
			{
				$query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,work_dir,wd.v_issue_sequence_id,wd.automation_status,wd.user_working_status FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.approved_by <= 0 and wd.cust_id = c.id and wd.j_id = j.j_id AND wd.v_issue_id ='".$_REQUEST['id']."' AND wd.status NOT IN ('Delivery','Client Review','Completed') ORDER BY `v_issue_sequence_id` ASC ";
			}
			else if($_COOKIE['department_id'] == 9)
			{
				$query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,work_dir,wd.v_issue_sequence_id,wd.automation_status,wd.user_working_status FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.approved_by > 0 and wd.cust_id = c.id and wd.j_id = j.j_id AND (wd.fig_complete_status = 0 OR ( FIND_IN_SET(department_id,'".$_COOKIE['department_id']."'))) AND fig > 0  AND wd.v_issue_id ='".$_REQUEST['id']."'  AND wd.status NOT IN ('Delivery','Client Review','Completed') ORDER BY `v_issue_sequence_id` ASC ";
			}
			else
			{
				$query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,work_dir,wd.v_issue_sequence_id,wd.automation_status,wd.user_working_status FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND  FIND_IN_SET(department_id,'".$_COOKIE['department_id'].",".$_COOKIE['odepartment_id']."')  AND wd.v_issue_id ='".$_REQUEST['id']."'  AND wd.status NOT IN ('Delivery','Client Review','Completed') ORDER BY `v_issue_sequence_id` ASC ";
			}
		}
		else
		{
			 $query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,work_dir,wd.v_issue_id,wd.v_issue_sequence_id,wd.automation_status,wd.user_working_status FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  AND wd.v_issue_id ='".$_REQUEST['id']."' AND wd.status NOT IN ('Delivery','Client Review','Completed') ORDER BY `v_issue_sequence_id` ASC ";
		}
		//echo $query;
		$customers = $db->query($query) or ($db->error);				
		while($row = $customers->fetch_assoc()){ 
		$tr_class = '';   // no due-date / priority highlight
		/* issue articles open the OLD start work page (start_work.php): give this user the one-time ticket start_work.php asks for, so it is not redirected to the new page */
		if (session_status() === PHP_SESSION_ACTIVE) { $_SESSION['sw_legacy_ok'][(int)$row['id']] = time(); }
		/*$trans_sql = "SELECT u.full_name,t.current_status,t.id FROM `inw_transactions` as t,users as u WHERE t.process_user = u.id AND t.`project_id` = '".$row['id']."'   AND t.current_status NOT IN ('Completed','Take Over') AND t.stage = '".$row['stage']."' AND ('Completed' != (select current_status from `inw_transactions` where `project_id` = '".$row['id']."' ORDER BY `id` DESC limit 1) )  ORDER BY t.`id` DESC limit 1;";
		//echo $trans_sql.'<br>';		
		$chk_transaction = $db->query($trans_sql); 
		$transaction_toatl_count=$chk_transaction->num_rows;
		$trans_res = $chk_transaction->fetch_assoc(); 
		
		<?php if($transaction_toatl_count > 0){ echo '<span class="label label-sm label-info">'.$trans_res['full_name'].'</span>|<span class="label label-sm label-warning">'.$trans_res['current_status'].'</span>'; } ?>
		*/		
		?>
                  <tr>
                    <td class="center"><input type="checkbox" class="chk_row" /></td>
                    <td><?php echo $row['v_issue_sequence_id']; ?></td>
                    <td><?php echo $row['cust_name']; ?></td>
					<td><?php echo $row['j_code']; ?></td>
                    <td class="hidden-480"><?php echo $row['pub_id'].idr_article_badge($idr_art, $row['id']); ?></td>                   
					 <td><?php echo $row['manuscript_count']; ?></td>
                     <td><?php echo $row['fig']; ?></td>
                    <td><?php echo $row['tab']; ?></td>
                    <td><?php echo $row['stage']; ?></td>
                    <td><span class="af-stat-wrap"><span class="af-status" style="--c:#1e9e61"><?php echo $row['status']; ?></span><?php echo str_replace('</span>|<span', '</span><span', $row['user_working_status']); ?></span></td>
                    <td><div class="hidden-sm hidden-xs action-buttons">
                    <?php
					if($row['approved_by']>0){
						if($_COOKIE['department_id'] == 9){
					?>
					<?php 
					if($row['last_transaction_id']!='')
			{
				
				echo $start='<a title="Start work" style="font-size: 0.945em" class="btn btn-xs btn-primary" href="'.$_COOKIE['set_url'].'/graphicstartwork/'.$row['id'].'/'.$row['last_transaction_id'].' target="_blank" ">
			<i class="ace-icon fa fa-tasks bigger-120"></i>&nbspStart</a>';
			
			}
			else
			{
				echo $start='<a title="Start work" style="font-size: 0.945em" class="btn btn-xs btn-primary" href="'.$_COOKIE['set_url'].'/graphicstartwork/'.$row['id'].'/new"  target="_blank">
				<i class="ace-icon fa fa-tasks bigger-120"></i>&nbspStart</a>';	
			}
				?>
                                                            <?php } else { ?>
															
															<?php if($row['status'] != "Delivery"){?>
 <a onClick="return check_work_status('<?php echo $row['id']; ?>','<?php echo $trans_res['id']; ?>');" class="btn btn-xs btn-primary" href="javascript:void(0);" id="startBtn_<?php echo $row['id']; ?>">
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
					if($_COOKIE['user_level'] == 1){ ?>          
					<button class="btn btn-xs btn-info">
						<a class="btn btn-danger btn-xs" href="jobcard.php?id=<?php echo $row['id']; ?>"><i class="ace-icon fa fa-clock-o bigger-120"></i></a>
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
            </div></div></div>
            
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


<?php af_foot(); ?>
<!-- inline scripts related to this page --> 
<script type="text/javascript">
$(function () { $('#chk_all').on('change', function () { $('.chk_row').prop('checked', this.checked); }); });
			jQuery(function($) {
				//initiate dataTables plugin
				var myTable = 
				$('#articles').DataTable( {
					dom: "<'row'<'col-sm-4'l><'col-sm-4 text-center'B><'col-sm-4'f>>tp",
					order: [],
					columnDefs: [{ orderable: false, targets: [0, 10] }],
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
			
			let btn = $("#startBtn_" + id);

    // Disable and show spinner immediately
    btn.prop("disabled", true).addClass("disabled");
    showSpinner(btn);
	
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
							 openNewTab('<?php echo $_COOKIE['set_url'];?>/start_work.php?aid='+id+'&tid='+tid+'&legacy=1');  

						// Reset button
                        btn.prop("disabled", false).removeClass("disabled");
                        hideSpinner(btn);
						
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
								 openNewTab('<?php echo $_COOKIE['set_url'];?>/start_work.php?aid='+id+'&tid='+tid+'&legacy=1');   
						// Reset button
                        btn.prop("disabled", false).removeClass("disabled");
                        hideSpinner(btn);										
									return true;
								}
							},
							"danger" :
							{
								"label" : "Override",
								"className" : "btn-sm btn-danger",
								"callback": function() {
								openNewTab('<?php echo $_COOKIE['set_url'];?>/start_work.php?aid='+id+'&tid='+tid+'&legacy=1');				
						// Reset button
                        btn.prop("disabled", false).removeClass("disabled");
                        hideSpinner(btn);										
									return true;
								}
							}, 
							"button" :
							{
								"label" : "Close",
								"className" : "btn-sm",
								"callback": function() {
									//window.location.reload();
						// Reset button
                        btn.prop("disabled", false).removeClass("disabled");
                        hideSpinner(btn);									
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
// ========================================================
// Spinner Functions
// ========================================================
function showSpinner(btn) {
    btn.data("original-html", btn.html());
    btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');
}

function hideSpinner(btn) {
    let oldHtml = btn.data("original-html");
    if (oldHtml) btn.html(oldHtml);
}	

/* function openNewTab(url) {
    let newTab = window.open(url, '_blank');
    if (newTab) newTab.focus();
} */

function openNewTab(url) {
    let newTab = window.open(url, '_blank');

    if (!newTab || newTab.closed || typeof newTab.closed === "undefined") {

        swal({
            title: "Popup Blocked",
            text: "Your browser has blocked the popup.\n\nPlease allow popups for this website and try again.\n\nChrome: Click the popup blocked icon in the address bar and select 'Always allow popups from this site'.",
            type: "warning",
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "OK"
        });

        return false;
    }

    newTab.focus();
    return true;
}		
		</script>
</body>
</html>
