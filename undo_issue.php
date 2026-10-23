<?php 
include ('includes/includes.php'); 

/**
 * Recursively move files from one directory to another
 * 
 * @param String $src - Source of files being moved
 * @param String $dest - Destination of files being moved
 */

/// Add New record
if(isset($_POST['action']) && $_POST['action'] == 'insert')
{ 
	foreach($_POST as $key => $value) {
		$post[$key] = filter($value);
	}

	if ((int)$post['wf_id'] <= 0 || (int)$post['dept_id'] <= 0) { idr_fail('undo_issue.php', 'id='.(int)$post['vi_id'], 'Please choose a workflow and the department to assign in the same stage.'); }
	$post['recv_dt'] = convert_mdy_ymd($post['recv_dt']);	
	$post['due_dt'] = convert_mdy_ymd($post['due_dt']);	
	
	//// Get the worflow Steps ////
	$query = "SELECT `wf_id`,`wf_code`,wf_name FROM `adm_workflow_master` WHERE wf_id = '".$post['wf_id']."';";
	$steps = $db->query($query) or ($db->error);
	$deptinfo = $steps->fetch_assoc();
	$dept_info = explode(',',$deptinfo['wf_code']);
	
	/////// Get the department name /////
	$result = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$post['dept_id'].";"); 	
	$toatl_count=$result->num_rows;
	$dept_res = array();
	if($toatl_count>0)
	{
		$dept_res = $result->fetch_assoc();
	}
	
	
	
	
		
		
		$query = "SELECT * FROM `inw_inward_dtl` WHERE `v_issue_id` = ".$post['vi_id']." ORDER BY `status` ASC;";
		$articles_res = $db->query($query) or ($db->error);				
		while($app_res = $articles_res->fetch_assoc())
		{
 			
				
		
		
			
		
		$prj_update_query="UPDATE inw_inward_dtl SET  `status` ='".$dept_res['dept_name']."',`department_id` ='". $post['dept_id']."' WHERE id = ".$app_res['id'].";";
		//echo $prj_update_query; exit;
		$db->query($prj_update_query);
			
			
		}	
		
		$vi_update_query="UPDATE inw_volume_issues SET  `status` ='' WHERE vi_id = ".$post['vi_id'].";";
		$db->query($vi_update_query);
			

		header("location:clientreview_issues.php?msg=Successfully updated"); 
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
		$correction_count = $correction_count +2;
	}
	//echo $correction_count;
	
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
  <!-- Data Table CSS -->
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>	
    <?php include_once 'includes/modern-ui.php'; include_once 'includes/article-filter-ui.php'; include_once 'includes/issue_drafts.php'; mu_head(); af_head(); idr_head(); ?>

    <!-- Data Table CSS -->
  
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
<!-- <div class="container-fluid" style="justify-content: space-between;">
  <div div class="row pt-4 d-flex align-items-center flex-wrap">
    <div class="col-8 col-lg-10">
      <p class="articleheading ms-4"><?php echo $customer_info['cust_name']; ?> : <?php echo $journal_info['j_name']; ?></p>
    </div>
  </div>
</div> -->

 <div class="container-fluid" style="justify-content: space-between;">
      <div class="af-page" style="padding-bottom:0;">
        <div class="af-hero">
          <div><small>Undo &middot; <?php echo htmlspecialchars($customer_info['cust_name']); ?> &middot; <?php echo htmlspecialchars($journal_info['j_code']); ?></small><h1>Undo Issue</h1></div>
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
        <div><small>Journal</small><b><?php echo htmlspecialchars($journal_info['j_code']); ?></b></div>
        <div><small>Volume</small><b><?php echo htmlspecialchars($app_res['volume_folder']); ?></b></div>
        <div><small>Stage | status</small><b><?php echo htmlspecialchars($app_res['stage']); ?> | <?php echo htmlspecialchars($app_res['status']); ?></b></div>
        <div><small>Received</small><b><?php echo $app_res['received_date'] ? date('d-m-Y', strtotime($app_res['received_date'])) : '&ndash;'; ?></b></div>
        <div><small>Due</small><b><?php echo $app_res['due_date'] ? date('d-m-Y', strtotime($app_res['due_date'])) : '&ndash;'; ?></b></div>
      </div>
    </div></div></div>

    <div class="af-page"><div class="af-card">
      <h3 class="rv-h"><i class="fa fa-undo"></i> Enter the following information</h3>
      <p class="rv-note">The issue goes back into production in its current stage: choose the workflow and the department the articles should be assigned to.</p>
      <form class="form-horizontal" id="validation-form" method="post">
        <input type="hidden" name="correction_count" value="<?php echo $correction_count; ?>" />
        <input type="hidden" name="vi_id" value="<?php echo $_REQUEST['id']; ?>" />
        <input type="hidden" name="cust_name" value="<?php echo $customer_info['cust_name']; ?>" />
        <input type="hidden" name="j_code" value="<?php echo $journal_info['j_code']; ?>" />
        <input type="hidden" name="volume_folder" value="<?php echo $app_res['volume_folder']; ?>" />
        <input type="hidden" name="work_dir" value="<?php echo $app_res['work_dir']; ?>">
        <input type="hidden" name="prev_stage" value="<?php echo $app_res['stage']; ?>" />
        <input type="hidden" name="action" value="insert">
        <?php 
						  	$query = "SELECT vi.vi_id,vi.received_date,vi.due_date,volume_number,issue_number,cus.cust_name,ju.j_code,(SELECT COUNT(id) FROM inw_inward_dtl WHERE v_issue_id = vi.vi_id AND status='Delivery') as ccount FROM inw_volume_issues as vi LEFT JOIN adm_customer_master as cus ON vi.cust_id = cus.id LEFT JOIN adm_journals as ju ON vi.j_id = ju.j_id WHERE vi.status = 'Client Review' and vi.vi_id='".$_REQUEST['id']."' ORDER BY vi.vi_id ASC;";
						$customersrow_corr = $db->query($query) or ($db->error);				
						$row_corr = $customersrow_corr->fetch_assoc();
						  ?>
        <div class="rv-grid">
          <div class="af-field">
            <label>Correction Pages</label>
            <input type="number" min="0" id="correction_pages" name="correction_pages" class="form-control" value="<?php echo (int)$app_res['page_count']; ?>" />
          </div>
          <div class="af-field">
            <label>Stage</label>
            <select name="stage" class="form-select required">
                          <option value="ISSFIN<?php echo $correction_count; ?>" selected>Issue Final</option>
                              </select>
          </div>
          <div class="af-field">
            <label>Workflow <span class="rv-req">*</span></label>
            <?php
		$j_cust_id = $app_res['j_id'];
		 $query = "SELECT j_fin_issue_wf FROM `adm_journals` WHERE j_id = '".$j_cust_id."'";
		$customers = $db->query($query) or ($db->error);
		$jrow = $customers->fetch_assoc();
						
		$query = "SELECT `wf_id`,`wf_code`,wf_name FROM `adm_workflow_master` WHERE wf_name IN ('".$jrow['j_fin_issue_wf']."') ORDER BY `wf_code` ASC ";
		$customers = $db->query($query) or ($db->error);		
		
		?>
                                <select id="wf_id" name="wf_id" class="form-select required" data-placeholder="Choose">
                                  <option value="">-Select-</option>
                                  <?php while($row = $customers->fetch_assoc()){ ?>
                          <option value="<?php echo $row['wf_id']; ?>"><?php echo $row['wf_name']; ?></option>
                          <?php } ?>
                                </select>
          </div>
          <div class="af-field">
            <label>Department to assign in the same stage <span class="rv-req">*</span></label>
            <?php
	//// Get the worflow Steps ////
	 $query = "SELECT `wf_id`,`wf_code`,wf_name FROM `adm_workflow_master` WHERE wf_name = '".$jrow['j_fin_issue_wf']."';";
	$steps = $db->query($query) or ($db->error);
	$deptinfo = $steps->fetch_assoc();	
	$query = "SELECT * FROM `adm_dept_master` WHERE `dept_code` IN (".$deptinfo['wf_code'].") ORDER BY `dept_name` ASC";
	$customers = $db->query($query) or ($db->error);		

	?>
                              <select id="dept_id" name="dept_id" class="form-select required" data-placeholder="Choose">
                                <option value="">-Select-</option>                                
                                <?php while($row = $customers->fetch_assoc()){ ?>
                                <option value="<?php echo $row['dept_code']; ?>" ><?php echo $row['dept_name']; ?></option>
                                <?php } ?>
                              </select>
          </div>
        </div>
        <div class="rv-actions">
          <a class="af-btn" href="clientreview_issues.php">Cancel</a>
          <button type="submit" value="Save" class="af-btn primary" id="rv_save"><i class="fa fa-check"></i> Next</button>
        </div>
      </form>
    </div></div>

    <?php echo footer(); ?>

<!-- Footer Section -->
<?php echo scripts(); ?>	

   
      <?php idr_error_script(); idr_error_popup('Cannot undo the issue'); ?>
<script type="text/javascript">
$(function () {
  $('#validation-form').on('submit', function (e) {
    if (!$('#wf_id').val()) { idrError('Please choose a workflow.', 'Workflow required'); e.preventDefault(); return; }
    if (!$('#dept_id').val()) { idrError('Please choose the department to assign in the same stage.', 'Department required'); e.preventDefault(); }
  });
});
</script>
</body>
</html>
