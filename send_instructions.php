<?php include "includes/includes.php"; include_once 'includes/issue_drafts.php';?>
<?php 
error_reporting(0);

if(isset($_POST['action']) && $_POST['action']=='update')
{

	foreach($_POST as $key => $value) {
		$post[$key] = $value;
	}
	
  //echo "SELECT * FROM inw_inward_dtl WHERE id = ".$post['id']." ";exit;
	if (empty($_POST['assign_department']) || trim(strip_tags((string)$post['comments'])) == '') { idr_fail('send_instructions.php', 'id='.(int)$post['id'], 'Please choose at least one department and write the comments.'); }
	$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$post['id']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	
		$pinfo = $result->fetch_assoc();
		
		/// Get the Customer Details /////
		
		$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = '".$pinfo['cust_id']."' ";
		$customers = $db->query($query) or ($db->error);
		$crow = $customers->fetch_assoc();
		
		
		//// Journal name ///	
		$query = "SELECT j_name,j_code FROM `adm_journals` WHERE j_id = '".$pinfo['j_id']."';";
		$steps = $db->query($query) or ($db->error);
		$jninfo = $steps->fetch_assoc();

	
	$Cn= $crow['cust_name'];
	$jn= $jninfo['j_code'];	
	$artile_name= $pinfo['pub_id'];		
	$download_folder = 'downloads';
	$journal_folder = 'JOURNALS';
	$volumefolder =$pinfo['volume_folder'];
	$group_folder_name =$pinfo['issueorgroup_folder'];
	$stage =$pinfo['stage'];
	$date =date('d-m-Y-his');
		
	// current directory
	//echo getcwd() . "\n";
	
	
	 $destination_dir = WF_DIR."/$download_folder/$journal_folder/$Cn/$jn/$volumefolder/$group_folder_name/$artile_name/$stage/instructions/";
	if (!(is_dir($destination_dir))) {
	if (!mkdir($destination_dir, 0777, true)) {
    //die('Failed to create folders...$jn');
	}
	}
	
$inst_doc=$_FILES['inst_doc']['name'];
if($inst_doc!="")
{
move_uploaded_file($_FILES['inst_doc']['tmp_name'],$destination_dir.$inst_doc);
}
else
{
$inst_doc="";
}


	 $insert_query = "INSERT INTO `inw_dept_comments` (`inw_id`, `dept_ids`, `comments`, `created_by`, `created_on`, `created_dept`,stage) VALUES ('". $post['id']."', '". implode(",",$_POST['assign_department'])."', '". mysqli_real_escape_string($db,$post['comments'])."', '". $_COOKIE['user_id']."', CURRENT_TIMESTAMP, '". $_COOKIE['department_id']."', '". $pinfo['stage'] ."');";	

		$db->query($insert_query) or die($db->error);

		header("location:".$_COOKIE['set_url']."/articles?msg=Successfully added"); 
		die();	
			
	print_r($_POST); exit;
}
/////////////// Update completed ///////////////

if(isset($_REQUEST['id']))
{
	$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$_REQUEST['id']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$app_res = $result->fetch_assoc();
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

//echo $transaction_id;
?>

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
  background-color: #EDF3F4 !important;
  border-top: 1px solid #F7FBFF !important;
}
 td {
  color: #336199 !important;
  background-color: #EDF3F4 !important;
  border-top: 1px solid #F7FBFF !important;
}
.wholeouter {
    height: 70% !important;
    /* width: 100%; */
}
  </style>
      <style>
      .mp-info{display:flex;flex-wrap:wrap;gap:10px;align-items:stretch;}
      .mp-info>div{padding:8px 14px;border:1px solid #e4e9ef;border-radius:10px;background:#f9fbfd;min-width:140px;}
      .mp-info small{display:block;font-size:10.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#6b7785;}
      .mp-info b{color:#14304a;font-size:14px;}
      .mp-h{display:flex;align-items:center;gap:8px;margin:0 0 4px;font-size:13px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#14304a;}
      .mp-h i{color:#e8742a;}
      .mp-note{color:#6b7785;margin:0 0 12px;}
      .mp-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px 18px;margin-top:12px;}
      .mp-grid .af-field{min-width:0;}
      .mp-grid .af-field.wide{grid-column:1/-1;}
      .mp-grid .form-control,.mp-grid .form-select{width:100%;height:38px;border:1px solid #d5dde6;border-radius:9px;font-size:13.5px;box-shadow:none;}
      .mp-grid .form-control[readonly]{background:#f3f6f9;color:#51606f;}
      .mp-grid textarea.form-control{height:auto;min-height:100px;}
      .mp-grid input[type=file].form-control{height:auto;padding:6px 10px;}
      .mp-grid .select2-container{width:100% !important;}
      .mp-grid .select2-container--default .select2-selection--multiple{min-height:38px;border:1px solid #d5dde6;border-radius:9px;}
      .mp-all{display:inline-flex;align-items:center;gap:6px;margin-left:10px;font-size:12px;font-weight:600;text-transform:none;letter-spacing:0;color:#14304a;cursor:pointer;}
      .mp-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:18px;}
      .mp-req{color:#d64545;}
      .mp-tbl{width:100%;border-collapse:separate;border-spacing:0 6px;}
      .mp-tbl th{padding:6px 12px 2px;font-size:10.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#7b8a9a;text-align:left;white-space:nowrap;border:0;background:transparent;}
      .mp-tbl td{padding:10px 12px;background:#fff;border-top:1px solid #e4e9ef;border-bottom:1px solid #e4e9ef;vertical-align:top;color:#22303f;}
      .mp-tbl td:first-child{border-left:1px solid #e4e9ef;border-radius:10px 0 0 10px;}
      .mp-tbl td:last-child{border-right:1px solid #e4e9ef;border-radius:0 10px 10px 0;}
      .mp-pill{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:700;}
      .mp-pill.open{background:#fdf1d9;color:#b87800;} .mp-pill.done{background:#e3f6ec;color:#1e9e61;}
      /* the rich-text editor sits inside the card */
      .mp-grid .richText{border:1px solid #d5dde6;border-radius:9px;overflow:hidden;}
    </style>
  </head>

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
     <?php
if(isset($_REQUEST['cust_id']))
{
$cust_id=implode(",",$_REQUEST['cust_id']);
$ex_cid = explode(",",$cust_id);
}
else
{
$ex_cid='';
}
if(isset($_REQUEST['j_id']))
{
	 $jid=implode(",",$_REQUEST['j_id']);
 	 $ex_jid = explode(",",$jid);
}
else
{
	$ex_jid='';
}
if(isset($_REQUEST['stage']))
{
	  $stage=implode(",",$_REQUEST['stage']);
 	  $ex_stage = explode(",",$stage);
}
else
{
	$ex_stage='';
}
if(isset($_REQUEST['article_id']))
{
	  $article_id=$_REQUEST['article_id'];
}
else
{
	$article_id='';
}
if(isset($_REQUEST['from_dt']))
{
	  $from_dt=$_REQUEST['from_dt'];
}
else
{
	$from_dt='';
}
if(isset($_REQUEST['to_dt']))
{
	  $to_dt=$_REQUEST['to_dt'];
}
else
{
	$to_dt='';
}

	
?>
      <div class="container-fluid" style="justify-content: space-between;">
      <div class="af-page" style="padding-bottom:0;">
        <div class="af-hero">
          <div><small>Internal comments &middot; <?php echo htmlspecialchars($customer_info['cust_name']); ?> &middot; <?php echo htmlspecialchars($journal_info['j_code']); ?></small><h1>Send Instructions</h1></div>
          <a class="af-add" href="<?php echo $_COOKIE['set_url'];?>/articles" style="background:rgba(255,255,255,.16);box-shadow:none;border:1px solid rgba(255,255,255,.35);"><i class="fa fa-arrow-left"></i> Back to Articles</a>
        </div>
      </div>
    </div>

    <?php
    if(!empty($_REQUEST['msg']))  {
      echo '<div class="alert alert-success"><button type="button" class="close" aria-label="Close">&times;</button>'. htmlspecialchars($_REQUEST['msg']) .'</div>';
    } ?>

    <div class="af-page"><div class="af-wrap"><div class="formouter">
      <div class="mp-info">
        <div><small>Article</small><b><?php echo htmlspecialchars($app_res['pub_id']); ?></b></div>
        <div><small>Customer</small><b><?php echo htmlspecialchars($customer_info['cust_name']); ?></b></div>
        <div><small>Journal</small><b><?php echo htmlspecialchars($journal_info['j_code']); ?></b></div>
        <div><small>Stage</small><b><?php echo htmlspecialchars($app_res['stage']); ?></b></div>
        <div><small>Received</small><b><?php echo $app_res['recv_dt'] ? date('d-m-Y', strtotime($app_res['recv_dt'])) : '&ndash;'; ?></b></div>
        <div><small>Due</small><b><?php echo $app_res['due_dt'] ? date('d-m-Y', strtotime($app_res['due_dt'])) : '&ndash;'; ?></b></div>
      </div>
    </div></div></div>

    <div class="af-page"><div class="af-card">
      <h3 class="mp-h"><i class="fa fa-comments"></i> Send comments to internal departments</h3>
      <p class="mp-note">Choose the departments that should see this note, write the comments, and attach a document if needed.</p>
      <form class="form-horizontal" id="validation-form" method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?php echo $_REQUEST['id']; ?>" />
        <div class="mp-grid">
          <div class="af-field wide">
            <label>Departments <span class="mp-req">*</span> <label class="mp-all"><input type="checkbox" class="form-check-input" id="chkall"> Select all</label></label>
            <select class="form-select customer_drop" data-placeholder="Select Assign Department" name="assign_department[]" id="assign_department"  multiple="multiple" style="height:100px;">
                                <?php
											$sql_departments = $auth->getAlldepartment();
											foreach($sql_departments as $res_departments)
											{
									   ?>
													 <option value="<?php echo $res_departments['dept_code'] ?>"style="font-weight:500;"><?php echo $res_departments['dept_name'] ?> </option>
													<?php
											}
											?>
                                                              </select>
          </div>
          <div class="af-field wide">
            <label>Comments <span class="mp-req">*</span></label>
            <textarea name="comments" id="comments" class="form-control" rows="10" placeholder="Comments"></textarea>
          </div>
          <div class="af-field wide">
            <label>Upload file</label>
            <input type="file" name="inst_doc" class="form-control">
          </div>
        </div>
        <div class="mp-actions">
          <a class="af-btn" href="<?php echo $_COOKIE['set_url'];?>/articles">Cancel</a>
          <button type="submit" class="af-btn primary" id="mp_save"><i class="fa fa-check"></i> Save</button>
        </div>
      </form>
    </div></div>

     <?php echo footer(); ?>




<!-- Footer Section -->
<?php echo scripts(); ?>	
    <?php idr_error_script(); idr_error_popup('Cannot send the instructions'); ?>

   
       <script src="<?php echo $_COOKIE['set_url'];?>/assets/js/select2/select2.min.js"></script>
   
    <script>
      $(".customer_drop").select2({
        placeholder: "Select Department",
      });
     

      $("#chkall").click(function(){
      if($("#chkall").is(':checked') ){ //select all
        $("#assign_department").find('option').prop("selected",true);
        $("#assign_department").trigger('change');
      } else { //deselect all
        $("#assign_department").find('option').prop("selected",false);
        $("#assign_department").trigger('change');
      }
  });
        $('#comments').richText();//this will create the default editor will all buttons
      $('#validation-form').on('submit', function (e) {
        if (!($('#assign_department').val() || []).length) { idrError('Please choose at least one department.', 'Department required'); e.preventDefault(); return; }
        var txt = ($('#comments').val() || '').replace(/<[^>]*>|&nbsp;/g, '').trim();
        if (!txt) { idrError('Please write the comments.', 'Comments required'); e.preventDefault(); }
      });			

    </script>


  </body>
</html>
