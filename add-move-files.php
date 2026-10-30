<?php include "includes/includes.php";?>
<?php require_once __DIR__.'/includes/form_ui.php'; ?>
<?php include_once "includes/change_log_mail.php";?>
<?php require_once __DIR__.'/includes/mf_cut.php'; mf_cut_ensure($db); ?>
<?php require_once __DIR__.'/includes/move_naming.php'; ?>
<?php
$bs = array('BS' => 'BookServices');
/*$project_status_types = $bs + $project_status_types;
$project_status_types_bs = $bs + $project_status_types_bs;*/
$project_status_types_bs = $project_status_types + $bs;
/// Add New record
if (isset($_POST['action']) && $_POST['action'] == 'insert') {
    foreach ($_POST as $key => $value) {
        $post[$key] = $value;
    }
	// print_r($_POST['files']);
	// echo "</br>";
/* print_r($_POST['files']);
exit; */
    $bad_rules = mfn_validate_post(isset($_POST['rules']) ? $_POST['rules'] : array());
    if ($bad_rules) {
        echo "<script>alert(" . json_encode("Some file naming rules are not valid:\n" . implode("\n", $bad_rules)) . "); history.back();</script>";
        die();
    }
    /* one record per customer + journal + department */
    $dup_dept = (int)$post['dept_id'];
    $dup = $db->query("SELECT mf_id FROM inw_move_files WHERE cust_id = '" . $db->real_escape_string($post['cust_id']) . "' AND j_id = '" . $db->real_escape_string($post['j_id']) . "' AND (dept_id = " . $dup_dept . ($dup_dept == 0 ? " OR dept_id IS NULL" : "") . ") LIMIT 1");
    if ($dup && $dup->num_rows > 0) {
        $dup_row = $dup->fetch_assoc();
        echo "<script>alert('A Move Files record already exists for this customer, journal and department (record #" . (int)$dup_row['mf_id'] . "). Please edit that record instead of adding a new one.'); history.back();</script>";
        die();
    }
     $insert_query = "INSERT INTO `inw_move_files` (`cust_id`, `j_id`,b_id, dept_id,`created_by`, `created_on`) VALUES ('" . $post['cust_id'] . "', '" . $post['j_id'] . "','" . $post['b_id'] . "', '" . $post['dept_id'] . "','" . $_COOKIE['user_id'] . "', NOW());";

    $db->query($insert_query) or die($db->error);

    $mf_id = $db->insert_id;
	
    foreach ($_POST['files'] as $stagekey => $sub_files) {

        foreach ($sub_files as $deptid_key => $files_val) {
			// echo $files_val;

             $rules_json = mfn_rules_from_post($files_val, isset($_POST['rules'][$stagekey][$deptid_key]) ? $_POST['rules'][$stagekey][$deptid_key] : array());
             $rules_sql  = $rules_json === null ? 'NULL' : "'" . $db->real_escape_string($rules_json) . "'";
             $insert_query_mov_files = "INSERT INTO `inw_move_files_dept_wise` (`mf_id`, `stage`, `dept_id`,move_files,move_name_rules,move_folders,cut_move,created_by) VALUES ('" . $mf_id . "', '" . $stagekey . "', '" . $deptid_key . "', '" . $files_val . "'," . $rules_sql . ",'" . $_POST['folders'][$stagekey][$deptid_key] . "'," . (empty($_POST['cutmove'][$stagekey][$deptid_key]) ? 0 : 1) . ",'" . $_COOKIE['user_id'] . "');";
            $db->query($insert_query_mov_files) or die($db->error);
   
  }
    }
	
	//exit;

    // Change-log entry + email for the new record
    log_row_added('Move Files', $mf_id, array_diff_key($_POST, array('action'=>1)), array());
    header("location:".$_COOKIE['set_url']."/managemovefiles?msg=Successfully added");
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
    <?php fu_head('add_move_files'); ?>	

    <!-- Data Table CSS --->
    <style>
    .overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: none;
}

.loader-container {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1001;
    display: none;
}

.loader {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 2s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.ui-widget-content {
  background-color:white !important;
}
.ui-state-active, .ui-widget-content .ui-state-active, .ui-widget-header .ui-state-active, a.ui-button:active, .ui-button:active, .ui-button.ui-state-active:hover {
    border: 1px solid #f26722 !important;
    background: #f26722 !important;
    font-weight: bold !important;
    color: #fff !important;
}
.ui-state-default .ui-icon {
     background-image:none !important; 
}
.ui-state-default, .ui-widget-content .ui-state-default, .ui-widget-header .ui-state-default{
  border: 1px solid #cccccc !important;
    /* background: #fff !important; */
    font-weight: normal ;
    color: #000;
}
.ui-accordion-content{
  font-size: 0.875rem !important
}
.ui-accordion-header{
  font-size: 0.875rem !important

}
.ui-accordion .ui-accordion-header-active{
	padding: 10px 15px;
}
.ui-accordion .ui-accordion-header{
	padding: 10px 15px;
}
      </style>
</head>

<body>
  <div id="loader" class="loader-container">
        <div class="loader"></div>
    </div>
    <div id="overlay" class="overlay"></div>
    <div class="wholeouter">
    <?php include("includes/top-header.php");?>


    <div class="container-fluid" style="justify-content: space-between;">
    <div div class="row pt-4 d-flex align-items-center flex-wrap">
          <div class="col-8 col-lg-10">
            <p class="articleheading ms-2 ms-lg-4">Add Move Files</p>
          </div>

          <div class="col-4 col-lg-2">
            <div class="row pe-2 pe-lg-4" style="display: flex; justify-content: end">
              <div class="col-3 articlebtns d-flex justify-content-end">
                <a href="javascript:history.back();">
                  <div  class="back_icon"><img src="<?php echo $_COOKIE['set_url'];?>/assets/images/backarrow.svg"></div>
                </a>
              </div>
            </div>
          </div>
        </div> 

      </div>
<?php fu_hero(fu_ui_basic('Add Move Files', 'Add Move Files &rsaquo; Add')); ?>
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
          <form action="" method="post" name="add_move_files" id="add_move_files" enctype="multipart/form-data">
            <div class="container mt-4 ">
              <div class="formcont1heading">Add New File</div>
              <div class="row mt-1 g-4 addspacing">
              <!-- <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Cutomer Name</label>
                 <input type="text" class="form-control" name="cust_name" id="cust_name" placeholder="Enter Customer Name" required>
                </input>
                </div> -->
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Customer<span style="color: red">*</span></label>
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="cust_id" id="cust_id" required>
                  <option value="">Select Customer</option>
                   <?php
											$sql_customers = $auth->showCustomerlist();
											foreach($sql_customers as $res_customers)
											{
												
									      	if(isset($_REQUEST['cust_id']))
													{
										    	?>
										       <option <?php if (in_array($res_customers['id'], $ex_cid)){?>selected<?php }?> value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
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
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Journal<span style="color: red">*</span></label>
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_id" id="j_id" required>
                  <option value="">Select Journal</option>

                    <?php
											$sql_journals = $auth->showJournallist('0',2);
											foreach($sql_journals as $res_journals)
											{
												
									      	if(isset($_REQUEST['j_id']))
													{
										    	?>
										       <option <?php if (in_array($res_journals['j_id'], $ex_jid)){?>selected<?php }?> value="<?php echo $res_journals['j_id'] ?>" style="font-weight:500;" data-id="<?php echo $res_journals['j_code'] ?>_0000" data-previous="<?php echo $res_journals['j_code'] ?>_0000"><?php echo $res_journals['j_code'] ?> </option>
													<?php
													}
													else
													{
													?>
													 <option value="<?php echo $res_journals['j_id'] ?>" style="font-weight:500;" data-id="<?php echo $res_journals['j_code'] ?>_0000" data-previous="<?php echo $res_journals['j_code'] ?>_0000"><?php echo $res_journals['j_code'] ?> </option>
													<?php
													}
											}
											?>
                </select>
                </div>
               
                <div class="col-12 col-sm-6 col-lg-4">
              <?php
	$query = "SELECT * FROM `adm_dept_master` ORDER BY `dept_name` ASC";
	$customers = $db->query($query) or ($db->error);		

	?>
                  <label class="mb-2"> Department </label>
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="dept_id" id="dept_id">
                  <option value="">Select</option>

                  <?php while($row = $customers->fetch_assoc()){ ?>
                                  <option value="<?php echo $row['dept_code']; ?>" ><?php echo $row['dept_name']; ?></option>
                  <?php } ?>
                </select>
                </div>
              
    <!-- </head> -->
     <div class="col-sm-12 mb-3 mt-5">
     <div class="formcont1heading"> Manage Department and Stage wise files moving</div>
      
	<div id="accordion" class="accordion-style1 mt-4 ">
	<?php
	$row_cnt = 0;
	foreach ($project_status_types as $id => $val)
	{ 
		$row_cnt++;
	?>
		<h3><?php echo $val; ?></h3>


		<div style="background: #fff" class="p-2 p-md-5" >
			<p>
			<div class="panel-body " >
      <div class="col-lg-12">

				<?php
				$query = "SELECT * FROM `adm_dept_master` WHERE `parent_id` = 0 ORDER BY `dept_name` ASC";
				$customers = $db->query($query) or ( $db->error);
				while ($row = $customers->fetch_assoc()) {
				?>
                    <div class="col-12 d-md-flex gap-3 align-items-center mb-3">
                        <label class="col-sm-3 control-label no-padding-right " style="overflow: auto"><?php echo $row['dept_name']; ?>:</label>
                        <div class="col-6  col-lg-4">
                            Files<input name="files[<?php echo $id; ?>][<?php echo $row['dept_code']; ?>]" placeholder="Ex: tex|pdf|zip" type="text" class="form-control" style="height: 30px">
                           

                        </div>
                        <div class="col-6 col-lg-4">
                            Folder<input name="folders[<?php echo $id; ?>][<?php echo $row['dept_code']; ?>]" id="form-field-icon-1" type="text" class="form-control" style="height: 30px">
                        </div>
                        <div class="col-6 col-lg-4">
                            <label style="font-weight:normal;margin:0;" title="Move instead of copy: the files and folders of this movement are removed from the source folder once they are in the destination"><input type="checkbox" name="cutmove[<?php echo $id; ?>][<?php echo $row['dept_code']; ?>]" value="1"> Cut (move, leave no copy)</label>
                        </div>
                    </div>
				<?php } ?>
      </div> 
			</div>
			</p>
		</div>
            
	<?php 
	} 
	?>
    </div> 

    <div class="meta_nxtbtn container-fluid mt-5 mb-5" >
                <div class="row d-flex gap-3">
                    <div class="col form_btn cancelbtn">
                        <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/managemovefiles" style="text-decoration:none;">Cancel</a></button>
                    </div>
                    <input type="hidden" name="action" value="insert">
                    <div class="col form_btn nxtbtn">
                        <button type="submit" class="btn-next" >Save</button>
                    </div>
                </div>
    </div>
</div>
</div>
</div>
</form>
</div>
</div>
      <?php echo scripts(); ?>	

       <!-- Footer Section -->
       <?php echo footer(); ?>
   
    <!-- Date picker functionality -->
    <script>
      $(function () {
        // var datesForDisable = ["2017-10-14", "2017-10-15", "2017-10-16"];
        var date = new Date();
        date.setDate(date.getDate() - 1);
        var fromdate;
        $(".monthlyinputdate").datepicker({
          uiLibrary: "bootstrap5",
          format: "dd-mm-yyyy",
          autoclose: true,
         //  minDate: date,
              onSelect: function (dateText, inst) {
             fromdate = dateText;

                    $(".monthlyinputdate1").datepicker("option", "minDate", fromdate);
                }
        });

        $(".monthlyinputdate1").datepicker({
          uiLibrary: "bootstrap5",
          format: "dd-mm-yyyy",
          autoclose: true,
          todayHighlight: true,
          // minDate: date, //For disabling date from today
           onSelect: function (dateText, inst) {
             todate = dateText;
                    $(".monthlyinputdate").datepicker("option", "maxDate", todate);
                }
        });
      });

      


      		// Handle changes in the "countries" select element
    $(document).ready(function() {

    $("#cust_id").on("change", function() {
         var cust_id=$.trim($(this).val());

         // Fetch states based on the selected client name and populate the "name" select element
         $.ajax({
             url: "get_journal.php",
             dataType: "json",
             data: { cust_id:cust_id },
             success: function(data) {
                 populateSelecte("j_id",data);

             }
         });
     });



    });

        function populateSelecte(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.j_code).val(item.j_id));
            });
        }

    </script>


<script>
/* link from Start Work ("No file movement configured"): ?pf_stage=JSTAGE opens that stage's panel; on the add page ?pf_cust / pf_j / pf_dept pre-fill the selects */
$(function () {
  var q = new URLSearchParams(location.search), st = q.get('pf_stage');
  function openStage() {
    if (!st) { return; }
    var $in = $('input[name^="files[' + st + ']"]').first(); if (!$in.length) { return; }
    var $panel = $in.closest('#accordion > div'), $h = $panel.prev('h3');
    if ($h.length) { try { $('#accordion').accordion('option', 'active', $('#accordion > h3').index($h)); } catch (e) { $h.click(); } }
    setTimeout(function () { var $f = $panel.find('input').first(); $h[0] && $h[0].scrollIntoView({ behavior: 'smooth', block: 'center' }); $f.focus(); }, 350);
    $h.css('box-shadow', '0 0 0 3px rgba(217,138,6,.45)');
  }
  var c = q.get('pf_cust'), j = q.get('pf_j'), d = q.get('pf_dept');
  function fill() {
    if (j) { $('#j_id').val(j).trigger('change'); }
    if (d) { $('#dept_id').val(d).trigger('change'); }
    openStage();
  }
  if ($('#cust_id').length && (c || j || d)) {
    if (c) {
      /* choosing a customer reloads the journal list (get_journal.php): fill the journal only AFTER that finished, or it is overwritten */
      var done = false, finish = function () { if (!done) { done = true; $(document).off('ajaxComplete.pf'); fill(); } };
      $(document).on('ajaxComplete.pf', function (e, x, s) { if (s && /get_journal\.php/.test(s.url || '')) { setTimeout(finish, 50); } });
      setTimeout(finish, 4000);                                             /* safety: never leave the form half filled */
      $('#cust_id').val(c).trigger('change');
    } else { fill(); }
  } else { openStage(); }
});
</script>
<?php fu_foot('#add_move_files', null, array(), null, fu_ui_basic('Add Move Files', 'Add Move Files &rsaquo; Add')); ?>
  <?php include __DIR__ . '/includes/move-files-stage-depts.php'; ?>
  <?php include __DIR__ . '/includes/move-name-rules-ui.php'; ?>
</body>
</html>
