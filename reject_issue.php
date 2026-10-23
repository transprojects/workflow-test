<?php include "includes/includes.php"; include_once 'includes/issue_drafts.php'; $idr_art = idr_article_map(idr_runner_mysqli($db));?>
<?php
/**
 * Recursively move files from one directory to another
 * 
 * @param String $src - Source of files being moved
 * @param String $dest - Destination of files being moved
 */
function rmove($src, $dest){

    // If source is not a directory stop processing
    if(!is_dir($src)) return false;

    // If the destination directory does not exist create it
    if(!is_dir($dest)) { 
        if(!mkdir($dest)) {
            // If the destination directory could not be created stop processing
            return false;
        }    
    }

    // Open the source directory to read in files
    $i = new DirectoryIterator($src);
    foreach($i as $f) {
        if($f->isFile()) {
            rename($f->getRealPath(), "$dest/" . $f->getFilename());
        } else if(!$f->isDot() && $f->isDir()) {
            rmove($f->getRealPath(), "$dest/$f");
            //rmdir($f->getRealPath());
        }
    }
    //rmdir($src);
}

if(isset($_REQUEST['delete_issue']) &&  $_REQUEST['delete_issue'] == 'Delete Issue')
{
	$delete_query = "DELETE FROM `inw_volume_issues` WHERE vi_id = '".$_REQUEST['vi_id']."'";
	$db->query($delete_query) or idr_fail('reject_issue.php', '', 'The change could not be saved: '.$db->error);
	header("location:reject_issue.php?msg=Successfully deleted"); 
	 exit();
}	
if(isset($_REQUEST['re_assign']))
{
	
	//echo "1";exit;
	if(!empty($_REQUEST['article_ids'])) 
	{
		foreach($_REQUEST as $key => $value) {
		$post[$key] = $value;
		}
		
		$to_id_array = explode('|',$post['new_volume_number']);
		$to_vid = $to_id_array[0];
		$to_volume_number = $to_id_array[1];
		$to_issue_number = $to_id_array[2];

		
		 $insert_query = "INSERT INTO `inw_vlume_issue_tracker` (`from_vid`, `to_vid`, `article_ids`,type,`created_by`, `created_on`) VALUES ('".$post['vi_id']."', '".$to_vid."', '".implode(',',$post['article_ids'])."', 'Re-Assigned','".$_COOKIE['user_id']."', NOW());";
		$db->query($insert_query) or idr_fail('reject_issue.php', '', 'The change could not be saved: '.$db->error);
		
		foreach ($_REQUEST['article_ids'] as $aid) 
		{
			$id = $aid;
			$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$id." "); 	
			$row_cnt=1;
			$toatl_count=$result->num_rows;
			if($toatl_count>0)
			{
				$app_res = $result->fetch_assoc();
				$newvolume_folder = VOLUME_PREFIX.$to_volume_number.$to_issue_number;
				
				//// Articles are in Delivery stage will move it to the Issue and volume folder //////
				if($app_res['status'] == 'Client Review')
				{
				/// Productoin articles moving to new vloume and issue folder
				$fsrc = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/"; 		
				$fdest = $app_res['work_dir'].$newvolume_folder."/".$app_res['pub_id']."/"; 
				mkdir($fdest, 0777, true);
				rmove($fsrc,$fdest);
				
				/// Downloads articles moving to new vloume and issue folder
				$app_res['work_dir'] = str_replace('production','downloads',$app_res['work_dir']);
				$fsrc = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/"; 		
				$fdest = $app_res['work_dir'].$newvolume_folder."/".$app_res['pub_id']."/"; 
				mkdir($fdest, 0777, true);
				rmove($fsrc,$fdest);
				
				$prj_update_query="UPDATE inw_inward_dtl SET `volume_folder` ='".$newvolume_folder."',`v_issue_id` ='".$to_vid."' WHERE id = ".$id." ";
			//echo $prj_update_query; exit;
				$db->query($prj_update_query);
				}
				else
				{
					//// Here we updated the Temp stages to move the article automatically issue once complete all the stages //////
					$prj_update_query="UPDATE inw_inward_dtl SET `temp_v_issue_id` ='". $to_vid."', `v_issue_id` ='". $to_vid."' WHERE id = ".$id." ";
					//echo $prj_update_query; exit;
					$db->query($prj_update_query);
			
				}
			
				
			}

		}
	 }
	 
	header("location:manage_issues.php?msg=Successfully added"); 
	 exit();
}
if(isset($_REQUEST['reject_issue']) &&  $_REQUEST['reject_issue'] == 'Reject') 
{
	if(!empty($_REQUEST['article_ids'])) 
	{
		foreach($_REQUEST as $key => $value) {
		$post[$key] = $value;
		}	
	
		$insert_query = "INSERT INTO `inw_vlume_issue_tracker` (`from_vid`, `to_vid`, `article_ids`,type,`created_by`, `created_on`) VALUES ('".$post['vi_id']."', '0', '".implode(',',$_REQUEST['article_ids'])."', 'Rejected','".$_COOKIE['user_id']."', NOW());";	

		$db->query($insert_query) or idr_fail('reject_issue.php', '', 'The change could not be saved: '.$db->error);
		
		foreach ($_REQUEST['article_ids'] as $aid) 
		{
			$id = $aid;
			$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$id." "); 	
			$row_cnt=1;
			$toatl_count=$result->num_rows;
			if($toatl_count>0)
			{
				$app_res = $result->fetch_assoc();
				
				/// This is added for group folder moving to issue				
				$newvolume_folder = VOLUME_FOLDER_NAME.$app_res['issueorgroup_folder'];
				
				/// Productoin articles moving to new vloume and issue folder
				$fsrc = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/"; 		
				$fdest = $app_res['work_dir'].$newvolume_folder."/".$app_res['pub_id']."/"; 
				
				mkdir($fdest, 0777, true);
				rmove($fsrc,$fdest);
				
				/// Move the current stage production Files previous stage production ///
				$production_dir = $fdest.$app_res['stage']."/";		
				$production_dest_dir = $fdest.$app_res['prev_stage']."/";
				/// We are moving the latest stage file to isscor1 folder//////			
				//frcopy($production_dir, $production_dest_dir);
				rmove($production_dir, $production_dest_dir);
				
				
				/// Downloads articles moving to new vloume and issue folder
				$app_res['work_dir'] = str_replace('production','downloads',$app_res['work_dir']);
				$fsrc = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/"; 		
				$fdest = $app_res['work_dir'].$newvolume_folder."/".$app_res['pub_id']."/"; 
				
				mkdir($fdest, 0777, true);
				rmove($fsrc,$fdest);
				
				
			
				
				$prj_update_query="UPDATE inw_inward_dtl SET `volume_folder` ='".VOLUME_FOLDER_NAME."',`v_issue_id` ='0' ,`stage` ='".$app_res['prev_stage']."',wf_id = '".$app_res['prev_workflow']."', `status` ='".$app_res['prev_status']."',`department_id` ='". $app_res['prev_dept_id']."' WHERE id = ".$id." ";
			//echo $prj_update_query; exit;
			$db->query($prj_update_query);
			
				
			}

		}
	 }
	 
	header("location:reject_issue.php?msg=Successfully added"); 
	 exit();
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
    <style>
      .rj-bar .af-field{min-width:220px;}
      .rj-bar .form-select{width:100% !important;height:38px;border-radius:9px;border:1px solid #d5dde6;font-size:13.5px;box-shadow:none;}
      .rj-h{display:flex;align-items:center;gap:8px;margin:0 0 4px;font-size:13px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#14304a;}
      .rj-h i{color:#e8742a;}
      .rj-note{color:#6b7785;margin:0 0 12px;}
      .rj-grid{display:grid;grid-template-columns:minmax(240px,420px) auto;gap:14px 18px;align-items:end;margin-top:12px;}
      .rj-grid .af-field{min-width:0;}
      .rj-actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap;}
      .af-btn.warn{background:#fff6e5;border-color:#f1d9a6;color:#b87800 !important;}
      .af-btn.warn:hover{background:#d98a06;border-color:#d98a06;color:#fff !important;}
      .af-btn.danger{background:#fdeaea;border-color:#f3c4c4;color:#c0392b !important;}
      .af-btn.danger:hover{background:#d64545;border-color:#d64545;color:#fff !important;}
      #articles td:first-child,#articles th:first-child{width:34px;}
      .af-card .label{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:700;line-height:1.3;color:#fff;background:#6b7785;}
    </style>

    <!-- Data Table CSS- -->
    
    </head>
    
    <body>
    <div class="wholeouter">
    <!-- Header logo section -->
    
    <?php include("includes/top-header.php");?>
    <div class="container-fluid" style="justify-content: space-between;">
      <div class="af-page" style="padding-bottom:0;">
        <div class="af-hero">
          <div><small>Manage issues</small><h1>Reject / Assign Issue</h1></div>
          <a class="af-add" href="manage_issues.php" style="background:rgba(255,255,255,.16);box-shadow:none;border:1px solid rgba(255,255,255,.35);"><i class="fa fa-arrow-left"></i> Back to Manage Issues</a>
        </div>
      </div>
    </div>

    <?php
    if(!empty($_REQUEST['msg']))  {
      echo '<div class="alert alert-success"><button type="button" class="close" aria-label="Close">&times;</button>'. htmlspecialchars($_REQUEST['msg']) .'</div>';
    } ?>

    <div class="af-page"><div class="af-wrap">
      <form class="form-horizontal formouter" id="get_records" method="post">
        <div class="af-bar rj-bar">
          <div class="af-field"><label>Customer</label>
            <?php
									$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` ORDER BY `cust_name` ASC ";
									$customers = $db->query($query) or ($db->error);		
								 ?>
                    			<select id="cust_id" name="cust_id" class="col-xs-12 col-sm-4 required form-select" data-placeholder="Choose">
                     				<option value="">-Select-</option>
                    					 <?php while($row = $customers->fetch_assoc()){ ?>
                   				    		<option value="<?php echo $row['id']; ?>" <?php if($_REQUEST['cust_id'] ==$row['id'] ){?> selected <?php } ?>><?php echo $row['cust_name']; ?></option>
                   					  	 <?php } ?>
                   				 </select>
          </div>
          <div class="af-field"><label>Journal</label>
            <?php
					 	$query = "SELECT `j_id`,`j_name`,j_code FROM `adm_journals` where j_cust_id ='".$_REQUEST['cust_id']."' ORDER BY `j_name` ASC ";
					 	$customers = $db->query($query) or ($db->error);		
					 	?>
                     	<select id="j_id" name="j_id" class="col-xs-12 col-sm-4 required form-select" data-placeholder="Choose">
                     	 <option value="">-Select-</option>
                     	 <?php while($row = $customers->fetch_assoc()){ ?>
                     	 <option value="<?php echo $row['j_id']; ?>" <?php if($_REQUEST['j_id'] == $row['j_id'] ){?> selected <?php } ?>><?php echo $row['j_code']; ?></option>
                     	 <?php } ?>
                     	</select>
          </div>
          <div class="af-field"><label>Volume / Issue</label>
            <?php
					$query = "SELECT `vi_id`,`volume_number`,issue_number FROM `inw_volume_issues` ORDER BY `volume_number` ASC ";
					$volume_issue = $db->query($query) or ($db->error);		
					?>
                    <select id="volume_number" name="volume_number" class="col-xs-12 col-sm-4 required form-select" data-placeholder="Choose">
                    <option value="">-Select-</option>
                    <?php while($row = $volume_issue->fetch_assoc()){ ?>
                    <option value="<?php echo $row['vi_id']; ?>" <?php if($_REQUEST['volume_number'] ==$row['vi_id'] ){?> selected <?php } ?>><?php echo VOLUME_PREFIX.$row['volume_number'].$row['issue_number']; ?></option>
                    <?php } ?>
                    </select>
          </div>
          <div class="af-actions" style="margin-left:0;">
            <button type="submit" value="Get Records" name="get_records_btn" id="get_records_btn" class="af-btn primary"><i class="fa fa-search"></i> Get Records</button>
            <a class="af-btn" href="reject_issue.php" title="Reset"><i class="fa fa-refresh"></i></a>
          </div>
        </div>
      </form>
    </div></div>

    <?php
    if($_REQUEST['j_id'] >0)
    {
    ?>
    <form class="form-horizontal" id="validation-form" method="post" action="">
      <input type="hidden" name="cust_id" value="<?php echo (int)$_REQUEST['cust_id'];?>">
      <input type="hidden" name="j_id" value="<?php echo (int)$_REQUEST['j_id'];?>">
      <input type="hidden" name="vi_id" value="<?php echo (int)$_REQUEST['volume_number'];?>">

      <div class="af-page"><div class="af-card">
        <div class="table-responsive">
          <table id="articles" class="table custtable table-primary idr-compact" style="width: 100% !important; text-wrap: nowrap !important">
            <thead>
              <tr>
                <th class="center"><input type="checkbox" id="chk_all" title="Select all on this page" /></th>
                <th data-af-role="client">Customer</th>
                <th data-af-role="journal">Journal</th>
                <th data-af-role="file">Article</th>
                <th data-af-role="recv">Due Date</th>
                <th data-af-role="recv">Created On</th>
                <th data-af-role="num">Manuscript</th>
                <th data-af-role="num">Fig</th>
                <th data-af-role="num">Table</th>
                <th data-af-role="stage">Stage</th>
                <th data-af-role="none">Status</th>
              </tr>
            </thead>
            <tbody>
                  <?php
		if($_COOKIE['user_level'] != 1) 
		{		  
			if($_COOKIE['department_id'] == '16')
			{
				$query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.approved_by > 0 and wd.cust_id = c.id and wd.j_id = j.j_id and wd.cust_id = ".$_REQUEST['cust_id']." and wd.j_id = ".$_REQUEST['j_id']." and wd.v_issue_id ='".$_REQUEST['volume_number']."' ORDER BY `id` DESC ";
			}
			else if($_COOKIE['department_id'] == 9)
			{
				$query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.approved_by > 0 and wd.cust_id = c.id and wd.j_id = j.j_id and wd.cust_id = ".$_REQUEST['cust_id']." and wd.j_id = ".$_REQUEST['j_id']." AND wd.fig_complete_status = 0 AND fig > 0  and wd.v_issue_id ='".$_REQUEST['volume_number']."' ORDER BY `id` DESC ";
			}
			else
			{
				$query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE  wd.cust_id = c.id and wd.j_id = j.j_id and wd.cust_id = ".$_REQUEST['cust_id']." and wd.j_id = ".$_REQUEST['j_id']." AND department_id = '".$_COOKIE['department_id']."' and wd.v_issue_id ='".$_REQUEST['volume_number']."' BY `id` DESC ";
			}
		}
		else
		{
			$query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id and wd.cust_id = ".$_REQUEST['cust_id']." and wd.j_id = ".$_REQUEST['j_id']." and wd.v_issue_id ='".$_REQUEST['volume_number']."' ORDER BY `id` DESC ";
		}
		//echo $query;
		$customers = $db->query($query) or ($db->error);				
		$toatl_article_count=$customers->num_rows;
		while($row = $customers->fetch_assoc()){ 
		$tr_class = '';
		// no due-date / priority highlight
		?>
                  <tr>
                    <td class="center"><input type="checkbox" class="chk_row" name="article_ids[]" value="<?php echo $row['id']; ?>" /></td>
                    <td><?php echo $row['cust_name']; ?></td>
					<td><?php echo $row['j_code']; ?></td>
                    <td class="hidden-480"><?php echo $row['pub_id'].idr_article_badge($idr_art, $row['id']); ?></td>                   
					<td><?php echo (date('d-m-Y',strtotime($row['due_dt']))); ?></td>
                    <td><?php echo (date('d-m-Y',strtotime($row['created_dt']))); ?></td>
					 <td><?php echo $row['manuscript_count']; ?></td>
                     <td><?php echo $row['fig']; ?></td>
                    <td class="hidden-480"><?php echo $row['tab']; ?></td>
                    <td class="hidden-480"><?php echo $row['stage']; ?></td>
                    <td class="hidden-480"><span class="af-status" style="--c:#1e9e61"><?php echo $row['status']; ?></span></td>
                    
                  </tr>
                  <?php } ?>
                </tbody>
          </table>
        </div>
      </div></div>

      <?php
		 $query = "SELECT `vi_id`,`volume_number`,issue_number FROM `inw_volume_issues` WHERE cust_id = '".$_REQUEST['cust_id']."' AND j_id = '".$_REQUEST['j_id']."' AND vi_id != '".$_REQUEST['volume_number']."' ORDER BY `volume_number` ASC ";
		$volume_issue = $db->query($query) or ($db->error);		
		
		?>
      <div class="af-page"><div class="af-card">
        <?php if($toatl_article_count == 0) { ?>
          <h3 class="rj-h"><i class="fa fa-trash-o"></i> Empty issue</h3>
          <p class="rj-note">No articles found in this volume or issue. If you want to delete it, click Delete Issue.</p>
          <button type="submit" id="delete_issue" value="Delete Issue" name="delete_issue" class="af-btn danger"><i class="fa fa-trash-o"></i> Delete Issue</button>
        <?php } else { ?>
          <h3 class="rj-h"><i class="fa fa-exchange"></i> Assign or reject the ticked articles</h3>
          <p class="rj-note">Tick the articles above, then assign them to another volume / issue of this journal, or reject them from this issue.</p>
          <div class="rj-grid">
            <div class="af-field rj-bar"><label>Existing volume and issue to assign to</label>
              <select id="new_volume_number" name="new_volume_number" class="col-xs-4 col-sm-4 form-select required" data-placeholder="Choose">
                                  <option value="">-Select-</option>
                                  <?php while($row = $volume_issue->fetch_assoc()){ ?>
                                  <option value="<?php echo $row['vi_id']; ?>|<?php echo $row['volume_number']; ?>|<?php echo $row['issue_number']; ?>" <?php if($_REQUEST['volume_number'] ==$row['vi_id'] ){?> selected <?php } ?>><?php echo VOLUME_PREFIX.$row['volume_number'].$row['issue_number']; ?></option>
                                  <?php } ?>
								                                 </select>
            </div>
            <div class="rj-actions">
              <button type="submit" id="re_assign" value="Assign" name="re_assign" class="af-btn primary"><i class="fa fa-exchange"></i> Assign</button>
              <button type="submit" id="reject_issue" value="Reject" name="reject_issue" class="af-btn warn"><i class="fa fa-ban"></i> Reject</button>
            </div>
          </div>
        <?php } ?>
      </div></div>
    </form>
    <?php } ?>

    <?php echo scripts(); ?> 
    <?php af_foot(); ?>
    <?php idr_error_script(); idr_error_popup('Cannot continue'); ?>
    <script type="text/javascript">$(function () { $('#chk_all').on('change', function () { $('.chk_row').prop('checked', this.checked); }); });</script>
    
    <!-- Footer Section --> 
    <?php echo footer(); ?> 
 
 
<script type="text/javascript">
jQuery(function($) {

$('#reject_issue').on('click', function(e) {
	
	        var cnt = $("input[name='article_ids[]']:checked").length;
	        if (cnt < 1)
	        {
	            idrError('Please tick at least one article first.', 'No article selected');
	            e.preventDefault();
	        }			
	        
	    });
		$('#re_assign').on('click', function(e) {
	
	        var cnt = $("input[name='article_ids[]']:checked").length;
	        if (cnt < 1)
	        {
	            idrError('Please tick at least one article first.', 'No article selected');
	            e.preventDefault();
	        }
			$('#validation-form').validate();		
	        
	    });
		
		
		$('#get_records_btn').on('click', function(e) {$('#get_records').validate({errorClass: 'help-block'});	});
		
		
				
							
			});
			jQuery(function($) {
				    var table = $('#articles').DataTable( {
		dom: "<'row'<'col-sm-4'l><'col-sm-4 text-center'B><'col-sm-4'f>>tp",
	    pageLength: 10,
		order: [],
		columnDefs: [{ orderable: false, targets: [0] }],

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

    

        
    } );
			
				
				
						
			})
			
function confirmDelete(delUrl) 
{
	if (confirm("Are you sure you want to delete")) 
	{
		document.location = delUrl;
	}
}	

$(document).ready(function(){

$("#cust_id").on("change", function() {
         var cust_id=$.trim($(this).val());

         // Fetch states based on the selected client name and populate the "name" select element
         $.ajax({
             url: "<?php echo $_COOKIE['set_url'];?>/get_journal.php",
             dataType: "json",
             data: { cust_id:cust_id },
             success: function(data) {
                 populateSelecte("j_id",data);

             }
         });
     });
function populateSelecte(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option data-id="+item.j_code+"_0000  data-previous="+item.j_code+"_0000>").text(item.j_code).val(item.j_id));
            });
        }
	
	///////////// Get workflow from Journal //////////
	
	$('#j_id').on('change',function(){
        var j_id = $(this).val();
		var j_cust_id = $('#cust_id').val();
        if(j_id){
            $.ajax({
                type:'POST',
                url:'get_vol_issue.php',
                data:'j_id='+j_id+'&j_cust_id='+j_cust_id,
                success:function(html){
                    $('#volume_number').html(html);                    
                }
            }); 
        }else{
            $('#volume_number').html('<option value="">Select Customer and Journal first</option>');
            
        }
    });
	
	
	});
		
		</script>
</body>
</html>
