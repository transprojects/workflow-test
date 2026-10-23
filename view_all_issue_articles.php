<?php 
include ('includes/includes.php'); 
include_once 'includes/issue_drafts.php';
$idr_art = idr_article_map(idr_runner_mysqli($db));
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
    .ii-info .ii-grow{flex:1 1 auto;display:flex;align-items:center;justify-content:flex-end;gap:10px;background:transparent;border:0;}
    .ii-misc{display:inline-flex;align-items:center;gap:7px;padding:8px 16px;border-radius:10px;background:#e8f1fb;border:1px solid #c5daef;color:#1b6ca8 !important;font-weight:700;text-decoration:none !important;}
    .ii-misc:hover{background:#1b6ca8;color:#fff !important;}
    #articles td:first-child,#articles th:first-child{width:34px;}
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
          <div><small>Issue details</small><h1>Vol<?php echo htmlspecialchars($voliss_info['volume_number'].$voliss_info['issue_number']); ?></h1></div>
          <a class="af-add" href="manage_issues.php" style="background:rgba(255,255,255,.16);box-shadow:none;border:1px solid rgba(255,255,255,.35);"><i class="fa fa-arrow-left"></i> Back to Manage Issues</a>
        </div>
      </div>
    </div>

    <?php if(!empty($_REQUEST['msg']))  {
      echo '<div class="alert alert-success" style="max-width:420px;margin:10px auto;">'. htmlspecialchars($_REQUEST['msg']) .'</div>';
    } ?>

    <div class="af-page"><div class="af-wrap"><div class="formouter">
      <div class="ii-info">
        <div><small>Issue</small><b>Vol<?php echo htmlspecialchars($voliss_info['volume_number'].$voliss_info['issue_number']); ?></b></div>
        <div><small>Received date</small><b><?php echo $voliss_info['received_date'] ? date('d-m-Y', strtotime($voliss_info['received_date'])) : '&ndash;'; ?></b></div>
        <div><small>Due date</small><b><?php echo $voliss_info['due_date'] ? date('d-m-Y', strtotime($voliss_info['due_date'])) : '&ndash;'; ?></b></div>
        <div><small>Draft status</small><b><?php echo idr_issue_cell($idr_issue, $voliss_info['vi_id']); ?></b></div>
        <?php if($voliss_info['vi_id'] > 0) { ?>
        <div class="ii-grow"><span style="color:#6b7785;font-weight:600;">Upload files to</span>
          <a class="ii-misc" href="file:///<?php echo str_replace('production','downloads',$work_dir)."/".$voliss_info['stage']."/misc"; ?>" target="_blank"><i class="fa fa-folder-open"></i> MISC</a></div>
        <?php } ?>
      </div>
    </div></div></div>

    <div class="af-page"><div class="af-card">
      	<div class="table-responsive">
              <table id="articles" class="table custtable table-primary idr-compact" style="width: 100% !important; text-wrap: nowrap !important">
                <thead>
                  <tr>
                    <th class="center"><input type="checkbox" id="chk_all" title="Select all on this page" /></th>
                    <th data-af-role="client">Customer</th>
                    <th data-af-role="journal">Journal</th>
                    <th data-af-role="file">Article</th>
                    <th data-af-role="num">Sequence No</th>
                    <th data-af-role="recv">Due Date</th>
                    <th data-af-role="recv">Created On</th>
                    <th data-af-role="num">Manuscript</th>
                    <th data-af-role="num">Fig</th>
                    <th data-af-role="num">Table</th>
                    <th data-af-role="stage">Stage</th>
                    <th>Status</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php
		if($_COOKIE['user_level'] != 1) 
		{		  
			if($_COOKIE['department_id'] == '16')
			{
				$query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,work_dir,wd.v_issue_id,v_issue_sequence_id FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  AND wd.v_issue_id ='".$_REQUEST['id']."'  ORDER BY `id` DESC ";
			}
			else if($_COOKIE['department_id'] == 9)
			{
				$query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,work_dir,wd.v_issue_id,v_issue_sequence_id FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.approved_by > 0 and wd.cust_id = c.id and wd.j_id = j.j_id AND (wd.fig_complete_status = 0 OR department_id = '".$_COOKIE['department_id']."') AND fig > 0  AND wd.v_issue_id ='".$_REQUEST['id']."'  AND wd.status NOT IN ('Delivery','Client Review','Completed') ORDER BY `id` DESC ";
			}
			else
			{
				$query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,work_dir,wd.v_issue_id,v_issue_sequence_id FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND department_id = '".$_COOKIE['department_id']."'  AND wd.v_issue_id ='".$_REQUEST['id']."'  AND wd.status NOT IN ('Delivery','Client Review','Completed') ORDER BY `id` DESC ";
			}
		}
		else
		{
			$query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,work_dir,wd.v_issue_id,v_issue_sequence_id FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  AND wd.v_issue_id ='".$_REQUEST['id']."'  ORDER BY `id` DESC ";
		}
		//echo $query;
		$customers = $db->query($query) or ($db->error);				
		while($row = $customers->fetch_assoc()){ 
		$tr_class = '';   // no due-date / priority highlight
		$trans_sql = "SELECT u.full_name,t.current_status,t.id FROM `inw_transactions` as t,users as u WHERE t.process_user = u.id AND t.`project_id` = '".$row['id']."'  AND t.process_user !='". $_COOKIE['user_id']."' AND t.current_status NOT IN ('Completed','Take Over') AND t.stage = '".$row['stage']."' AND ('Completed' != (select current_status from `inw_transactions` where `project_id` = '".$row['id']."' ORDER BY `id` DESC limit 1) )  ORDER BY t.`id` DESC limit 1;";
		//echo $trans_sql.'<br>';		
		$chk_transaction = $db->query($trans_sql); 
		$transaction_toatl_count=$chk_transaction->num_rows;
		$trans_res = $chk_transaction->fetch_assoc();		
		?>
                  <tr>
                    <td class="center"><input type="checkbox" class="chk_row" /></td>
                    <td><?php echo $row['cust_name']; ?></td>
					<td><?php echo $row['j_code']; ?></td>
                    <td class="hidden-480"><?php echo $row['pub_id'].idr_article_badge($idr_art, $row['id']); ?></td>                   
                    <td class="hidden-480"><?php echo $row['v_issue_sequence_id']; ?></td>                   
					<td><?php echo (date('d-m-Y',strtotime($row['due_dt']))); ?></td>
                    <td><?php echo (date('d-m-Y',strtotime($row['created_dt']))); ?></td>
					 <td><?php echo $row['manuscript_count']; ?></td>
                     <td><?php echo $row['fig']; ?></td>
                    <td><?php echo $row['tab']; ?></td>
                    <td><?php echo $row['stage']; ?></td>
                    <td><span class="af-stat-wrap"><span class="af-status" style="--c:#1e9e61"><?php echo $row['status']; ?></span><?php if($transaction_toatl_count > 0){ echo '<span class="af-sub">'.$trans_res['full_name'].'</span><span class="af-sub" style="color:#b87800">'.$trans_res['current_status'].'</span>'; } ?></span></td>
                    <td><?php if($_COOKIE['department_id'] == 16 || $_COOKIE['user_level'] == 1){ ?><a title="Upload multiple files" class="btn btn-xs btn-pink" href="upload_files.php?id=<?php echo $row['id']; ?>&issue_id=<?php echo $row['v_issue_id']; ?>&back=viewissues"><i class="fa fa-cloud-upload"></i> Upload</a><?php } ?></td>
                    
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
        <?php echo scripts(); ?> 
    
    <!-- Footer Section --> 
    <?php echo footer(); ?>   

<?php af_foot(); ?>
<!-- inline scripts related to this page --> 
<script type="text/javascript">
			jQuery(function($) {
							$('#articles').DataTable( {
					dom: "<'row'<'col-sm-4'l><'col-sm-4 text-center'B><'col-sm-4'f>>tp",
					order: [],
					columnDefs: [{ orderable: false, targets: [0, 12] }],
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
				
				
						
			})
			
$(function () { $('#chk_all').on('change', function () { $('.chk_row').prop('checked', this.checked); }); });

function confirmDelete(delUrl) 
{
	if (confirm("Are you sure you want to delete")) 
	{
		document.location = delUrl;
	}
}
	
function check_work_status(id,tid)
			{	
			
				$.ajax(
				{
					type:'POST',
					url:'get_article_work_status.php',
					data:{id:id},
					success:function(result)
					{
						
						if(result == 0)
						{
							window.location = 'project_work.php?id='+id;					
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
									window.location = 'project_work.php?id='+id+'&tid='+tid;				
									return true;
								}
							},
							"danger" :
							{
								"label" : "Override",
								"className" : "btn-sm btn-danger",
								"callback": function() {
									window.location = 'project_work.php?id='+id+'&tid='+tid;					
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
