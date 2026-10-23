<?php 
include ('includes/includes.php'); 

include_once 'includes/issue_drafts.php';
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
        } else if(!$f->isDot() && $f->isDir()) 
		{
            if($f !='Delivery' && $f !='CE' && $f !='PRQC')
			{
				rmove($f->getRealPath(), "$dest/$f");
				unlink($f->getRealPath());
				if(empty($f))
				{
					unlink($f);
				}
			}
        }
    }
    //unlink($src);
}

/// Add New record
if(isset($_POST['action']) && $_POST['action'] == 'insert')
{ 
	foreach($_POST as $key => $value) {
		$post[$key] = filter($value);
	}

	if (trim($post['recv_dt']) == '' || trim($post['due_dt']) == '' || (int)$post['wf_id'] <= 0) { idr_fail('rev_issue.php', 'id='.(int)$post['vi_id'], 'Please choose the received date, the due date and a workflow.'); }
	$post['recv_dt'] = convert_mdy_ymd($post['recv_dt']);	
	$post['due_dt'] = convert_mdy_ymd($post['due_dt']);	
	
	//// Get the worflow Steps ////
	$query = "SELECT `wf_id`,`wf_code`,wf_name FROM `adm_workflow_master` WHERE wf_id = '".$post['wf_id']."';";
	$steps = $db->query($query) or ($db->error);
	$deptinfo = $steps->fetch_assoc();
	$dept_info = explode(',',$deptinfo['wf_code']);
	
	/////// Get the department name /////
	$result = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$dept_info[0].";"); 	
	$toatl_count=$result->num_rows;
	$dept_res = array();
	if($toatl_count>0)
	{
		$dept_res = $result->fetch_assoc();
	}
	
	$xmlFilePath   = '';
	if(isset($_FILES['attachment']))
	{
		//// Check for zip file /////
	
		$errors= array();
		$file_name = $_FILES['attachment']['name'];
		$file_size =$_FILES['attachment']['size'];
		$file_tmp =$_FILES['attachment']['tmp_name'];
		$file_type=$_FILES['attachment']['type'];
	
		$down_destination_dir = WF_DIR."/downloads/JOURNALS/".$post['cust_name']."/".$post['j_code']."/".$post['volume_folder']."/".$post['stage']."/misc/";
		if (!(is_dir($down_destination_dir))) 
		{
			if (!mkdir($down_destination_dir, 0777, true)) 
			{
				idr_fail('rev_issue.php', 'id='.(int)$post['vi_id'], 'The output folders could not be created. Please check that the work drive is available and writable, then try again.');
			}
		}
			
		if (strrpos($file_name, '.zip') === strlen($file_name)-strlen('.zip'))
		{
			$zip = new ZipArchive;
			$res = $zip->open($file_tmp);	
			//var_dump($res);		
			if ($res === TRUE)
			{
				$zip->extractTo($down_destination_dir);
				$zip->close();  
			}
		}
		else
		{
			$Path     = $down_destination_dir;
			move_uploaded_file($file_tmp, $Path.$file_name);	
			//$xmlFilePath   = $Path.$file_name;
		}		
	}

	$insert_query = "INSERT INTO `inw_issue_corrections` (`vi_id`,correction_count,`received_date`, `due_date`, `correction_pages`, `comments`, `file_name`, `created_by`, `created_on`,type) VALUES ('".$post['vi_id']."','".$post['correction_count']."','".$post['recv_dt']."', '".$post['due_dt']."', '".$post['correction_pages']."', '".addslashes($post['instructions'])."', '".$destination_dir."','".$_COOKIE['user_id']."', NOW(),'ISSCOR');";
	//echo $insert_query; exit;
	$db->query($insert_query) or idr_fail('rev_issue.php', 'id='.(int)$post['vi_id'], 'The correction could not be saved: '.$db->error);
	
	
		/////////////////////////////////////////////////
		//// Here get the Articles Under this Issue /////
		////////////////// Loop Of Articles Start Here ///
		//////////////////////////////////////////////////
		/////////////////////////////////////////////////
		
		$Cn= $post['cust_name'];
		$jn= $post['j_code'];	
		$journal_folder = 'JOURNALS';
		$production = 'production';
		
		$work_dir = WF_DIR."/$production/$journal_folder/$Cn/$jn/".$post['volume_folder'];
		//// Delivery DIR /////
		mkdir($work_dir."/Delivery/".$post['stage']."/", 0777, true);
		
		/////// Update Valoume and Issue Table ////
		$inw_volume_issues_update_query="UPDATE inw_volume_issues SET `work_dir` ='".$work_dir."',`dispatch_dir` ='".$work_dir."/Delivery/".$post['stage']."/"."',`status` ='',`stage` ='".$post['stage']."' WHERE vi_id = ".$post['vi_id'].";";		
		$db->query($inw_volume_issues_update_query);			
		
		
		$query = "SELECT * FROM `inw_inward_dtl` WHERE `v_issue_id` = ".$post['vi_id']." ORDER BY `status` ASC;";
		$articles_res = $db->query($query) or ($db->error);				
		while($app_res = $articles_res->fetch_assoc())
		{
 			
				
		
		$destination_dir = $work_dir."/".$app_res['pub_id']."/".$post['stage']."/";
		
		if (!(is_dir($destination_dir))) {
		if (!mkdir($destination_dir, 0777, true)) {
		idr_fail('rev_issue.php', 'id='.(int)$post['vi_id'], 'The output folders could not be created. Please check that the work drive is available and writable, then try again.');
		}
		}
	
	
	/// Move the Previos stage production Files current stage production ///
		$production_dir = $work_dir."/".$app_res['pub_id']."/".$app_res['stage']."/";		
		$production_dest_dir = $destination_dir;
		
		rmove($production_dir, $production_dest_dir);
		
		//// Crete the production Next Dept folder ///
		if (!(is_dir($production_dest_dir.$dept_res['dept_name']))) 
		{
			if (!mkdir($production_dest_dir.$dept_res['dept_name'], 0777, true)) 		{
				idr_fail('rev_issue.php', 'id='.(int)$post['vi_id'], 'The output folders could not be created. Please check that the work drive is available and writable, then try again.');
			}
		}	
		$prj_update_query="UPDATE inw_inward_dtl SET `prev_stage` ='".$app_res['stage']."',`prev_workflow` ='".$app_res['wf_id']."',`prev_dept_id` ='".$app_res['department_id']."',`prev_status` ='".$app_res['status']."',`stage` ='".$post['stage']."',wf_id = '".$post['wf_id']."', `status` ='".$dept_res['dept_name']."',`department_id` ='". $dept_info[0]."' WHERE id = ".$app_res['id'].";";
		//echo $prj_update_query; exit;
		$db->query($prj_update_query);
			
			
		}	
			

		header("location:clientreview_issues.php?msg=Successfully added"); 
		die();	
}

/////////////// Read the data FROM XML File ///////////
///////////////////////////////////////////////////////

if(isset($_REQUEST['id']))
{
	$result = $db->query("SELECT ic_id FROM inw_issue_corrections WHERE type ='ISSCOR' and vi_id = ".$_REQUEST['id']." "); 		
	$correction_count=$result->num_rows;
	if($correction_count <= 0 )
	{
		$correction_count = 2;
	}
	else
	{
		$correction_count = $correction_count +1;
	}
	
	$result = $db->query("SELECT * FROM inw_volume_issues WHERE vi_id = ".$_REQUEST['id']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$app_res = $result->fetch_assoc();
		$app_res['volume_folder'] = VOLUME_PREFIX.$app_res['volume_number'].$app_res['issue_number'];
	}
	
	/// Get the Client Name ////
	$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = ".$app_res['cust_id'].";";
	$customers = $db->query($query) or ($db->error);
	$customer_info = $customers->fetch_assoc();
	/// Get the journal name ///
	$query = "SELECT `j_id`,`j_name`,j_code FROM `adm_journals` WHERE j_id = ".$app_res['j_id'].";";
	$journal = $db->query($query) or ($db->error);
	$journal_info = $journal->fetch_assoc();
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
    
	<style>
   
   th  {
  color: #336199 !important;
  background-color: #fff1f1 !important;
  border-top: 1px solid #dedef3 !important;
  border-left: 1px solid #dedef3 !important;
  border-right: 1px solid #dedef3 !important;
}
 td {
  color: #336199 !important;
  background-color: #fff !important;
  border-top: 1px solid #dedef3 !important;
  border-right: 1px solid #dedef3 !important;
  border-left: 1px solid #dedef3 !important;
}
      .tdstyle{
        width: 30%;
      }
      .ftpstyle{
        width: 100%;
      }
      .form-group{
        display: flex;
        width: 90%;
      }
      .form-control{
        width: 50%;
      }
      .form_outer{
        width: 95%;
        padding: 50px 20px;
        display: flex;
        justify-content: center;
        background-color: #fff3ed;

      }
      .form_inner{
        width: 95%;
        background-color: #fff;
        padding: 20px;
      }
     .divouter{
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 40px;
     }
     .radio_btn{
      margin: 30px 0;
      height: 30px;
     }
    </style>

        <style>
      .rv-info{display:flex;flex-wrap:wrap;gap:10px;align-items:stretch;}
      .rv-info>div{padding:8px 14px;border:1px solid #e4e9ef;border-radius:10px;background:#f9fbfd;min-width:140px;}
      .rv-info small{display:block;font-size:10.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#6b7785;}
      .rv-info b{color:#14304a;font-size:14px;}
      .rv-h{display:flex;align-items:center;gap:8px;margin:0 0 4px;font-size:13px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#14304a;}
      .rv-h i{color:#e8742a;}
      .rv-note{color:#6b7785;margin:0 0 12px;}
      .rv-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px 18px;margin-top:12px;}
      .rv-grid .af-field{min-width:0;}
      .rv-grid .af-field.wide{grid-column:1/-1;}
      .rv-grid .form-select,.rv-grid .form-control,.rv-grid textarea{width:100%;height:38px;border:1px solid #d5dde6;border-radius:9px;font-size:13.5px;box-shadow:none;}
      .rv-grid textarea{height:auto;min-height:90px;padding:8px 12px;}
      .rv-grid input[type=file].form-control{height:auto;padding:6px 10px;}
      .rv-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:18px;}
      .rv-req{color:#d64545;}
    </style>
  </head>
    
    <body>
    <div class="wholeouter">
    <!-- Header logo section -->
    
    <?php include("includes/top-header.php");?>
    <div class="container-fluid" style="justify-content: space-between;">
      <div class="af-page" style="padding-bottom:0;">
        <div class="af-hero">
          <div><small>Revise &middot; <?php echo htmlspecialchars($customer_info['cust_name']); ?> &middot; <?php echo htmlspecialchars($journal_info['j_code']); ?></small><h1>Revise Issue</h1></div>
          <a class="af-add" href="clientreview_issues.php" style="background:rgba(255,255,255,.16);box-shadow:none;border:1px solid rgba(255,255,255,.35);"><i class="fa fa-arrow-left"></i> Back to Under Client Issues</a>
        </div>
      </div>
    </div>

    <?php
    if(!empty($_REQUEST['msg']))  {
      echo '<div class="alert alert-success"><button type="button" class="close" aria-label="Close">&times;</button>'. htmlspecialchars($_REQUEST['msg']) .'</div>';
    } ?>

    <div class="af-page"><div class="af-wrap"><div class="formouter">
      <div class="rv-info">
        <div><small>Customer</small><b><?php echo htmlspecialchars($customer_info['cust_name']); ?></b></div>
        <div><small>Journal</small><b><?php echo htmlspecialchars($journal_info['j_name']); ?></b></div>
        <div><small>Volume</small><b><?php echo htmlspecialchars($app_res['volume_folder']); ?></b></div>
        <div><small>Current stage</small><b><?php echo htmlspecialchars($app_res['stage']); ?></b></div>
        <div><small>Received</small><b><?php echo $app_res['received_date'] ? date('d-m-Y', strtotime($app_res['received_date'])) : '&ndash;'; ?></b></div>
        <div><small>Due</small><b><?php echo $app_res['due_date'] ? date('d-m-Y', strtotime($app_res['due_date'])) : '&ndash;'; ?></b></div>
      </div>
    </div></div></div>

    <div class="af-page"><div class="af-card">
      <h3 class="rv-h"><i class="fa fa-pencil-square-o"></i> Enter the following information</h3>
      <p class="rv-note">The whole issue goes back into production under a new correction stage. Choose the dates, the workflow and the number of correction pages.</p>
      <form class="form-horizontal" id="validation-form" method="post" action="" enctype="multipart/form-data">
        <input type="hidden" name="correction_count" value="<?php echo $correction_count; ?>" />
        <input type="hidden" name="vi_id" value="<?php echo $_REQUEST['id']; ?>" />
        <input type="hidden" name="cust_name" value="<?php echo $customer_info['cust_name']; ?>" />
        <input type="hidden" name="j_code" value="<?php echo $journal_info['j_code']; ?>" />
        <input type="hidden" name="volume_folder" value="<?php echo $app_res['volume_folder']; ?>" />
        <input type="hidden" name="work_dir" value="<?php echo $app_res['work_dir']; ?>">
        <input type="hidden" name="prev_stage" value="<?php echo $app_res['stage']; ?>" />
        <input type="hidden" name="action" value="insert">
        <div class="rv-grid">
          <div class="af-field dvr-field">
            <label>Received Date &rarr; Due Date <span class="rv-req">*</span></label>
            <div class="dvr" data-placeholder="Select received and due dates" data-from-name="recv_dt" data-to-name="due_dt"></div>
          </div>
          <link rel="stylesheet" href="dv-rangepicker.css?v=1"><script src="dv-rangepicker.js?v=1"></script>
          <div class="af-field">
            <label>Correction Pages <span class="rv-req">*</span></label>
            <input type="number" min="0" id="correction_pages" name="correction_pages" class="form-control" value="" />
          </div>
          <div class="af-field">
            <label>Stage</label>
            <select name="stage" class="required form-select">
                               <option value="ISSCOR<?php echo $correction_count; ?>" selected>Corrections</option>                           
                              </select>
          </div>
          <div class="af-field">
            <label>Workflow <span class="rv-req">*</span></label>
            <?php
		$j_cust_id = filter($app_res['j_id']);
		$query = "SELECT j_issue_wf FROM `adm_journals` WHERE j_id = '".$j_cust_id."'";
		$customers = $db->query($query) or ($db->error);
		$jrow = $customers->fetch_assoc();
						
		$query = "SELECT `wf_id`,`wf_code`,wf_name FROM `adm_workflow_master` WHERE wf_name IN ('".$jrow['j_issue_wf']."') ORDER BY `wf_code` ASC ";
		$customers = $db->query($query) or ($db->error);		
		
		?>
                                <select id="wf_id" name="wf_id" class="col-xs-12 col-sm-4 required form-select" data-placeholder="Choose">
                                  <option value="">-Select-</option>
                                  <?php while($row = $customers->fetch_assoc()){ ?>
                                  <option value="<?php echo $row['wf_id']; ?>"><?php echo $row['wf_name']; ?></option>
                                  <?php } ?>
                                </select>
          </div>
          <div class="af-field wide">
            <label>Instructions</label>
            <textarea name="instructions" id="instructions" rows="4"></textarea>
          </div>
          <div class="af-field wide">
            <label>Attachment</label>
            <input class="form-control" type="file" name="attachment" accept=".doc,.docx,.pdf,.zip" />
          </div>
        </div>
        <div class="rv-actions">
          <a class="af-btn" href="clientreview_issues.php">Cancel</a>
          <button type="submit" value="Save" class="af-btn primary" id="rv_save"><i class="fa fa-check"></i> Save</button>
        </div>
      </form>
    </div></div>

    <?php echo scripts(); ?>
<?php idr_error_script(); idr_error_popup('Cannot save'); ?>
<script type="text/javascript">
$(function () {
  $('#validation-form').on('submit', function (e) {
    if (!$('input[name=recv_dt]').val() || !$('input[name=due_dt]').val()) { idrError('Please choose the received date and the due date.', 'Dates required'); e.preventDefault(); return; }
    if (!$('#correction_pages').val()) { idrError('Please enter the number of correction pages.', 'Correction pages required'); e.preventDefault(); return; }
    if (!$('#wf_id').val()) { idrError('Please choose a workflow.', 'Workflow required'); e.preventDefault(); }
  });
});
</script> 
    
    <!-- Footer Section --> 
    <?php echo footer(); ?> 
	</div>
<!-- inline scripts related to this page --> 
<script type="text/javascript">

 $(function () {
        // var datesForDisable = ["2017-10-14", "2017-10-15", "2017-10-16"];
        var date = new Date();
        date.setDate(date.getDate() - 1);
        var fromdate;
        $(".date-picker").datepicker({
          uiLibrary: "bootstrap5",
           dateFormat: "dd-mm-yy",
          autoclose: true,

        });

       
      });
	  
			
			$(document).ready(function()
			{
				/***************/
				$('.show-details-btn').on('click', function(e) {
					e.preventDefault();
					$( "#message-content" ).toggle( "slow");
					//add scrollbars to .message-body
						$('.message-content .message-body').ace_scroll({
							size: 150,
							mouseWheelLock: true,
							styleClass: 'scroll-visible'
						});
					$(this).find(ace.vars['.icon']).toggleClass('fa-angle-double-down').toggleClass('fa-angle-double-up');
				});
				/***************/	
			});
		</script>
</body>
</html>
