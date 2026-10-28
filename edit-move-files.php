<?php include "includes/includes.php";?>
<?php require_once __DIR__.'/includes/form_ui.php'; ?>
<?php
include_once "includes/change_log_mail.php";
require_once __DIR__ . '/includes/move_naming.php';

/**
 * Build the old -> new diff for a Move Files edit and email it via the shared helper.
 */
function send_move_files_change_log($mf_id, $old_header, $old_rows, $post, $stage_names)
{
    $log = "[Header changes]\r\n";
    $changes = 0;
    $header_fields = array('cust_id' => 'Customer', 'j_id' => 'Journal', 'b_id' => 'B ID', 'dept_id' => 'Department');
    foreach ($header_fields as $f => $label) {
        $old = isset($old_header[$f]) ? $old_header[$f] : '';
        $new = isset($post[$f]) ? $post[$f] : '';
        if ((string) $old !== (string) $new) {
            $log .= "  $label ($f): '$old' -> '$new'\r\n";
            $changes++;
        }
    }
    if ($changes == 0) {
        $log .= "  (no change)\r\n";
    }

    $log .= "\r\n[Stage / Department wise changes]\r\n";
    $dept_changes = 0;
    $new_files   = isset($_POST['files']) ? $_POST['files'] : array();
    $new_folders = isset($_POST['folders']) ? $_POST['folders'] : array();
    foreach ($new_files as $stage => $depts) {
        $stage_label = isset($stage_names[$stage]) ? $stage_names[$stage] : $stage;
        foreach ($depts as $dept => $new_f) {
            $new_d = isset($new_folders[$stage][$dept]) ? $new_folders[$stage][$dept] : '';
            $old_f = isset($old_rows[$stage][$dept]['move_files']) ? $old_rows[$stage][$dept]['move_files'] : '';
            $old_d = isset($old_rows[$stage][$dept]['move_folders']) ? $old_rows[$stage][$dept]['move_folders'] : '';
            if ($old_f !== $new_f) {
                $log .= "  Stage: $stage_label | Dept: $dept | Files  : '$old_f' -> '$new_f'\r\n";
                $dept_changes++;
            }
            $old_r = isset($old_rows[$stage][$dept]['move_name_rules']) ? mfn_describe($old_rows[$stage][$dept]['move_name_rules']) : '';
            $new_r = mfn_describe(mfn_rules_from_post($new_f, isset($_POST['rules'][$stage][$dept]) ? $_POST['rules'][$stage][$dept] : array()));
            if ($old_r !== $new_r) {
                $log .= "  Stage: $stage_label | Dept: $dept | Naming rules : '$old_r' -> '$new_r'\r\n";
                $dept_changes++;
            }
            if ($old_d !== $new_d) {
                $log .= "  Stage: $stage_label | Dept: $dept | Folder : '$old_d' -> '$new_d'\r\n";
                $dept_changes++;
            }
        }
    }
    if ($dept_changes == 0) {
        $log .= "  (no change)\r\n";
    }

    send_change_log_mail('Move Files', $mf_id, $log, $changes + $dept_changes);
}

if (isset($_POST['action']) && $_POST['action'] == 'update') {
  foreach ($_POST as $key => $value) {
      $post[$key] = $value;
  }

  //print_r($_POST);
  /// Snapshot the existing values (before change) for the change-log email ////
  $log_mf_id  = (int) $post['id'];
  $old_header = array();
  $old_rows   = array();
  $res_old_h  = $db->query("SELECT * FROM inw_move_files WHERE mf_id = " . $log_mf_id);
  if ($res_old_h && $res_old_h->num_rows > 0) {
      $old_header = $res_old_h->fetch_assoc();
  }
  $res_old_r = $db->query("SELECT * FROM `inw_move_files_dept_wise` WHERE `mf_id` = " . $log_mf_id);
  if ($res_old_r) {
      while ($o = $res_old_r->fetch_assoc()) {
          $old_rows[$o['stage']][$o['dept_id']] = $o;
      }
  }

  /// Customer / journal / department identify the record and cannot be changed here (a change could duplicate another record) ///
  if (!empty($old_header)) {
      foreach (array('cust_id', 'j_id', 'b_id', 'dept_id') as $hk) { $post[$hk] = $old_header[$hk]; }
  }

  /// Reject unusable naming rules before anything is deleted ///
  $bad_rules = mfn_validate_post(isset($_POST['rules']) ? $_POST['rules'] : array());
  if ($bad_rules) {
      echo "<script>alert(" . json_encode("Some file naming rules are not valid:\n" . implode("\n", $bad_rules)) . "); history.back();</script>";
      die();
  }

  /// Delete the existing files values from DB ////
  $delete_qry = "DELETE FROM `inw_move_files_dept_wise` WHERE `mf_id` = '" . $post['id'] . "' ";
  $db->query($delete_qry) or die($db->error);


  foreach ($_POST['files'] as $stagekey => $sub_files) {

      foreach ($sub_files as $deptid_key => $files_val) {

          $rules_json = mfn_rules_from_post($files_val, isset($_POST['rules'][$stagekey][$deptid_key]) ? $_POST['rules'][$stagekey][$deptid_key] : array());
          $rules_sql  = $rules_json === null ? 'NULL' : "'" . $db->real_escape_string($rules_json) . "'";
          $insert_query_mov_files = "INSERT INTO `inw_move_files_dept_wise` (`mf_id`, `stage`, `dept_id`,move_files,move_name_rules,move_folders,created_by) VALUES ('" . $post['id'] . "', '" . $stagekey . "', '" . $deptid_key . "', '" . $files_val . "'," . $rules_sql . ",'" . $_POST['folders'][$stagekey][$deptid_key] . "','" . $_COOKIE['user_id'] . "');";
          $db->query($insert_query_mov_files) or die($db->error);
      }
  }

  //exit;
  $update_query = "UPDATE inw_move_files SET cust_id ='" . $post['cust_id'] . "',j_id ='".$post['j_id']."',b_id ='".$post['b_id']. "',`dept_id` ='".$post['dept_id']."' WHERE mf_id = '".$post['id']."' ";

  if ($db->query($update_query)) {
      /// Email the change log (text attachment); a mail failure must not block the save ///
      try {
          send_move_files_change_log($log_mf_id, $old_header, $old_rows, $post, $project_status_types);
      } catch (Exception $e) {
          error_log("edit-move-files change-log mail failed: " . $e->getMessage());
      }
      header("location:".$_COOKIE['set_url']."/managemovefiles?msg=Successfully completed");
      die();
  } else {
      $err[] = "Please try again.";
  }
}
/////////////// Update completed ///////////////

if (isset($_REQUEST['id'])) {
  $result = $db->query("SELECT * FROM inw_move_files WHERE mf_id = " . $_REQUEST['id'] . " ");
  $row_cnt = 1;
  $toatl_count = $result->num_rows;
  if ($toatl_count > 0) {
      $app_res = $result->fetch_assoc();
  }
}
 /// Get the existing DEPT values from ///
 $mf_query = "SELECT * FROM `inw_move_files_dept_wise` WHERE `mf_id` = '" . $_REQUEST['id'] . "' ";
 $mf_info = $db->query($mf_query) or ( $db->error);
 $move_files_folders = array();
 while ($mff = $mf_info->fetch_assoc()) {
     $move_files_folders[$mff['stage']][$mff['dept_id']] = $mff;
 }
 //print_r($move_files_folders); exit;
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php fu_head('edit_move_files'); ?>	

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
            <p class="articleheading ms-4"> Edit Move Files</p>
          </div>
          <div class="col-4 col-lg-2">
            <div class="row d-flex justify-content-end pe-2">
              <div class="col-3 articlebtns ">
                <a href="javascript:history.back();">
                  <div  class="back_icon"><img src="<?php echo $_COOKIE['set_url'];?>/assets/images/backarrow.svg"></div>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php fu_hero(fu_ui_basic('Edit Move Files', 'Edit Move Files &rsaquo; Edit')); ?>
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
          <form action="" method="post" name="edit_move_files" id="edit_move_files" enctype="multipart/form-data">
            <div class="container mt-4 ">
              <div class="formcont1heading"> Edit Move Files</div>
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
												
									      
													?>
													 <option value="<?php echo $res_customers['id'] ?>"style="font-weight:500;" <?php if ($app_res['cust_id'] == $res_customers['id']) { ?> selected <?php } ?>><?php echo $res_customers['cust_name'] ?> </option>
													<?php
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
												
									      
													?>
													 <option value="<?php echo $res_journals['j_id'] ?>" style="font-weight:500;" <?php if ($app_res['j_id'] == $res_journals['j_id']) { ?> selected <?php } ?>><?php echo $res_journals['j_code'] ?> </option>
													<?php
												
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
                                  <option value="<?php echo $row['dept_code']; ?>"   <?php if ($app_res['dept_id'] == $row['dept_code']) { ?> selected <?php } ?>><?php echo $row['dept_name']; ?></option>
                  <?php } ?>
                </select>
                </div>
                      <!-- </head> -->
     <div class="col-sm-12 mb-3">
     <div class="formcont1heading"> Manage Department and Stage wise files moving</div>

	<div id="accordion" class="accordion-style1 mt-4">
	<?php
	$row_cnt = 0;
	foreach ($project_status_types as $id => $val)
	{
		$row_cnt++;
	?>
		<h3><?php echo $val; ?></h3>


		<div style="background: #fff" class="p-2 p-md-5">
			<p>
			<div class="panel-body">
            <div class="col-lg-12">

				<?php
				$query = "SELECT * FROM `adm_dept_master` WHERE `parent_id` = 0 ORDER BY `dept_name` ASC";
				$customers = $db->query($query) or ( $db->error);
				while ($row = $customers->fetch_assoc()) {
				?>
                    <div class="col-12 d-md-flex gap-3 align-items-center mb-3">
                        <label class="col-sm-3 control-label no-padding-right"><?php echo $row['dept_name']; ?>:</label>
                        <div class="col-6  col-lg-4">
                            Files<input name="files[<?php echo $id; ?>][<?php echo $row['dept_code']; ?>]" placeholder="Ex: tex|pdf|zip" type="text" class="form-control" style="height: 30px;" value="<?php echo $move_files_folders[$id][$row['dept_code']]['move_files']; ?>">
                        </div>
                        <div class="col-6 col-lg-4">
                            Folder<input name="folders[<?php echo $id; ?>][<?php echo $row['dept_code']; ?>]" id="form-field-icon-1" type="text" class="form-control" style="height: 30px;" value="<?php echo $move_files_folders[$id][$row['dept_code']]['move_folders']; ?>">
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
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?php echo $_REQUEST['id']; ?>" />

                <div class="meta_nxtbtn container-fluid mt-5 mb-5" >
                <div class="row d-flex gap-3">
                    <div class="col form_btn cancelbtn">
                        <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/managemovefiles" style="text-decoration:none;">Cancel</a></button>
                        
                    </div>

                    <div class="col form_btn nxtbtn">
                        <button type="submit" class="btn-next" >Update</button>
</div>
</div>
</div>
</div>
</div>
</div>
</form>
<?php include __DIR__ . '/includes/move-files-history.php'; ?>
</div>
</div>
      <?php echo scripts(); ?>	

       <!-- Footer Section -->
       <?php echo footer(); ?>
     </div>
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

     $('#j_id').on('change',function(){
        var j_id = $(this).val();
        if(j_id){
            $.ajax({
                url:'get_workflow.php',
                dataType: "json",
                data: { j_id:j_id },
                success:function(data){
                  //  $('#wf_id').html(html);   
                    populateSelecte_wf("wf_id",data);                 
                }
            });  
			
			$.ajax({
                url:'get_group_folder.php',
                dataType: "json",
                data: { j_id:j_id },
                success:function(data){
                   // $('#group_folder_name').html(html);     
                    populateSelecte_gf("group_folder_name",data);                     
                }
            }); 
			
			$.ajax({
                type:'POST',
                url:'get_journal_duration.php',
                data:'j_id='+j_id,
                success:function(html){
                    $('#journal_duration').html(html);   
                   // populateSelecte_jd("journal_duration",data);               
				    $('.date-picker').datepicker({
					autoclose: true,
					todayHighlight: true,
					format: 'dd-mm-yyyy'
				});					
                }
            }); 
			
			
        }else{
            $('#wf_id').html('<option value="">Select customer first</option>');
            
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
        function populateSelecte_wf(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.wf_name).val(item.wf_id));
            });
        }
        function populateSelecte_gf(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.folder_name).val(item.folder_name));
            });
        }
        function populateSelecte_jd(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.j_code).val(item.j_id));
            });
        }

    </script>

<script>  
 //	$.mask.definitions['~']='[+-]';

$(document).ready(function() {
    $("#add_article").validate({
        rules: {

          cust_id: "required",
          j_id: "required",
          pub_id: "required",
          artitle_title: "required",
          due_dt: "required",
          recv_dt: "required",
          manuscript_count: "required",
          fig: "required",
          tab: "required",
          wf_id: "required",
          inw_type: "required",
          complexity: "required",
          corresponding_author: "required",
          corresponding_mail: "required",
          accpt_dt: "required",
          pub_dt: "required"

           
        },
        messages: {

           cust_id: "Please Select Customer",
          j_id: "Please Select Journal",
          pub_id: "Please enter Pub ID",
          artitle_title: "Please enter Article title",
          due_dt: "Please enter Due date",
          recv_dt: "Please enter Received Date",
          manuscript_count: "Please enter Manuscript Count",
          fig: "Please enter Figure Count",
          tab: "Please enter Table Count",
          wf_id: "Please Select Workflow",
          inw_type: "Please Select Inward Type",
          complexity: "Please Select Complexity",
          corresponding_author: "Please enter Corresponding Author",
          corresponding_mail: "Please enter Corresponding Email",
          accpt_dt: "Please enter Accept Date",
          pub_dt: "Please enter Publication Date"
           
        },

        submitHandler: function(form) {
            $.ajax({
                url: "upload_article.php",
                type: "POST",
                data: $(form).serialize(),
                success: function(response) {
                    // Success message
                   // alert("Form submitted successfully!");
                    // Reset the form
                   // $("#articlesubmission")[0].reset();
                      $("#pub_id").prop("readonly", true);
                      $("#cust_id").prop("disabled", true);
                      $("#j_id").prop("disabled", true);
                      $("#recv_dt").prop("disabled", true);
                      $('.btn-next').hide();
                      $('.meta_nxtbtn').remove();
                      $('.content_right').show();
                      $('.btn-save').hide();
                      $(".btn-worklfow-cancel").show();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    // Error message
                    alert("Error submitting form: " + errorThrown);
                    $('.content_right').hide();
                    $('.btn-next').show();
                    $('.btn-save').hide();
                    $(".btn-cancel").hide();
                     $(".btn-worklfow-cancel").hide();

                }
            });
          }
        });
    
});  

  </script> 

     <script>
	$(document).ready(function(){
    $(".pdf").change(function () {
		var astatus = $("input[type='radio']:checked").val();
		//alert(astatus);
		if(astatus=='yes')
		{
			$(".pdf_show").show();
			$(".btn-save").hide();
		    $("#stage").val('REV2');
		}
		else
		{
		$(".pdf_show").hide();
		$(".btn-save").show();
		$("#stage").val('FIN1');
		}
     });


     $('.doi_number').keyup(function(e) {
     var txtVal = $(this).val();
     var split_doi = txtVal.split('.');
     var volume_no = split_doi[3];
     var issue_no = split_doi[4];
      $('#volume_no').val(volume_no);
      $('#issue_no').val(issue_no);
	 pref='https://crossmark.crossref.org/dialog/?doi=';
	 suffix='&domain=pdf';
     $('.crossmark_link').val(pref+txtVal+suffix);
  }); 
  
   $('#volume_no').keyup(function(e) {
     var txtVal = $(this).val();
     var dot='.';
    
     var doi_no = $('.doi_number').val();
     var issue_no = $('#issue_no').val();

     var split_doi = doi_no.split('.');
     $('.doi_number').val(split_doi[0]+dot+split_doi[1]+dot+split_doi[2]+dot+txtVal+dot+issue_no+dot+split_doi[5]);
	 pref='https://crossmark.crossref.org/dialog/?doi=';
	 suffix='&domain=pdf';
     $('.crossmark_link').val(pref+split_doi[0]+dot+split_doi[1]+dot+split_doi[2]+dot+txtVal+dot+issue_no+dot+split_doi[5]+suffix);
  }); 
  
  $('#issue_no').keyup(function(e) {
     var txtVal = $(this).val();
     var dot='.';
    
     var doi_no = $('.doi_number').val();
     var volume_no = $('#volume_no').val();

     var split_doi = doi_no.split('.');
     $('.doi_number').val(split_doi[0]+dot+split_doi[1]+dot+split_doi[2]+dot+volume_no+dot+txtVal+dot+split_doi[5]);
	 pref='https://crossmark.crossref.org/dialog/?doi=';
	 suffix='&domain=pdf';
     $('.crossmark_link').val(pref+split_doi[0]+dot+split_doi[1]+dot+split_doi[2]+dot+volume_no+dot+txtVal+dot+split_doi[5]+suffix);
  }); 

	
  
	// Corresponding Author Inputs//
	
	var counter_c = 2;
	$('.del_cauthor').hide();
	$('#del_cauthor_tag').hide();
	$('.add_cauthor').click(function(){
$('#author_ctools').before('<div class="metaauthorinputs" id="fac'+counter_c+'"><div class="metaauthorname"><label>Author Name </label><input type="text" placeholder="Author Name" name="author_cname[]" aria-label="name" id="author_cname'+counter_c+'" value="" /></div><div class="metaauthormail"><label>Email ID <span style="color: red">*</span></label><input type="email" placeholder="Author Email ID" name="author_cemail[] "aria-label="name" class="article_einput" id="author_cemail'+counter_c+'" value="" /></div></div>');
		$('.del_cauthor').fadeIn(0);
		$('.del_cauthor_tag').fadeIn(0);
		$('#total_show_cauthor').text("Count :"+counter_c);
		$('#total_cauthor').val(counter_c);

	counter_c++;
	



	});
	$('.del_cauthor').click(function(){
		if(counter_c== 3){
			$('.del_cauthor').hide();
			$('#del_cauthor_tag').hide();
		}   

		counter_c--;
		$('#fac'+counter_c).remove();
		$('#total_cauthor').val(counter_c-1);
		$('#total_show_cauthor').text("Count :"+(counter_c-1));



	});


// Corresponding Author Inputs//

//  Author Orcid Inputs//

	var counter_a = 1;
	$('.del_author').hide();
	$('#del_author_tag').hide();
	$('.add_author').click(function(){
$('#author_tools').before('<div class="metaauthorinputs" id="fa'+counter_a+'"><div class="metaauthorname"><label>Author Name </label><input type="text" placeholder="Author Name" name="author_name[]" aria-label="name" id="author_name'+counter_a+'" value="" /></div><div class="metaauthormail"><label>Orcid ID </label><input type="text" placeholder="Orcid ID" name="orcid_id[] "aria-label="name" id="orcid_id'+counter_a+'" value="" /></div></div>');
		$('.del_author').fadeIn(0);
		$('.del_author_tag').fadeIn(0);
		$('#total_show_author').text("Count :"+counter_a);
		$('#total_author').val(counter_a);
		//$('.corr_id_val').val('no');
		$('#orcid_id'+counter_a).mask('****-****-****-****');

	counter_a++;



	});
	$('.del_author').click(function(){
		if(counter_a==2){
			$('.del_author').hide();
			$('#del_author_tag').hide();
		}   

		counter_a--;
		$('#fa'+counter_a).remove();
		$('#total_author').val(counter_a-1);
		$('#total_show_author').text("Count :"+(counter_a-1));
		$('#orcid_id'+counter_a).mask('****-****-****-****');



	});





});
</script>

<script type='text/javascript'>
           // var hiddenValue1 =$("#jid").html();
           // var hiddenValue2 =$("#pubid").html();
           // var hiddenValue3 =$("#j_id").val();
           // console.log('hiddenValue:'+hiddenValue3);
        Dropzone.options.dropzonewidget = {


             maxFiles: 20, // Max number of files
            // acceptedFiles: ".pdf",
             type: 'POST',
             url: 'upload-new-articles-data-file.php',
             paramName: 'file',
             
             params: {
              request:1 // Additional argument 4
                // Add more arguments as needed
            },
            init: function () {

              var myDropzone = this;

                 var totalSize = 0;
                var totalSizeElement = document.getElementById('totalFileSize');

                this.on("addedfile", function (file) {

                  totalSize += file.size;
                  updateTotalSize();

                    var removeButton = Dropzone.createElement('<button class="btn-remove" style="background-color:red;border-radius:15px;cursor:pointer;">Remove</button>');
                    var _this = this;

                    // Event listener for the "Remove" button click
                    removeButton.addEventListener("click", function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        _this.removeFile(file);


                        var name = file.name;    
                
                $.ajax({
                    type: 'POST',
                    url: 'upload-new-articles-data-file.php',
                    data: {name: name,request: 2},
                    sucess: function(data){
                        console.log('success: ' + data);
                    }
                });
                var _ref;
                return (_ref = file.previewElement) != null ? _ref.parentNode.removeChild(file.previewElement) : void 0;
              //  updateTotalSize();

                    });

                    file.previewElement.appendChild(removeButton);

                    updateUploadCount();
                    updateTotalSize();


                });

                this.on("removedfile", function (file) {

                    totalSize -= file.size;
                    updateUploadCount();
                    updateTotalSize();

                });

                this.on("totaluploadprogress", function (progress) {
                    // Use progress.loaded to track the total uploaded size
                });

                this.on("success", function (file, response) {
                    console.log(response);
                });
                
                 this.on("queuecomplete", function () {
                // All files have been processed, display an alert
               // alert("All files have been uploaded.");
                  $(".btn-save").show();
            });

                function updateTotalSize() {
                    totalSizeElement.textContent = formatBytes(totalSize);
                }

                // Function to format bytes into human-readable format
                function formatBytes(bytes) {
                    var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
                    if (bytes === 0) return '0 Byte';
                    var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
                    return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
                }

              

// Add an event listener to the remove button
/* 
document.getElementById('removeButton').addEventListener('click', function () {
    if (myDropzone.files.length > 0) {
        // Remove the first file from the Dropzone instance
        myDropzone.removeFile(myDropzone.files[0]);
    }
});
*/

            }
        };

       


        function updateUploadCount() {
            var dropzoneInstance = Dropzone.forElement("#dropzonewidget");
            var uploadedFilesCount = dropzoneInstance.files.length;
            var countElement = document.getElementById("file_count");

            if (uploadedFilesCount === 0) {
                countElement.textContent = "0";
                	$(".btn-save").hide();
            } else if (uploadedFilesCount === 1) {
                countElement.textContent = "1";
                //	$(".btn-save").show();
            } else {
                countElement.textContent = uploadedFilesCount;
                //$(".btn-save").show();

            }
        }

        $('#j_id').change(function() {
	$('#previous').show();
    $('#pub_id').val($('#j_id option:selected').data('id'));
    $('.previous_aid').text($('#j_id option:selected').data('previous'));
    console.log($('#j_id option:selected').data('id'));
    console.log($('#j_id option:selected').data('previous'));
    var jid=$('#j_id').val();
    console.log(jid);
    //$('#j_id').val(jid);
    $('#j_id').attr('value',jid);



	
});

$(".btn-uploadworklfow").on("click", function() {
// Show overlay and loader
     
        
  var count=$("#file_count").text();
//alert(count);

 // alert('ajax initiated');
	if(count>0)
	{
	$("#overlay").show();
    $("#loader").show();
  var customerid = $('#cust_id').val();
  var jid = $('#j_id').val();
  var pubid = $('#pub_id').val();
// Initiate the AJAX request

// Fetch connectedsource based on the selected connectedsource and populate the "name" select element
$.ajax({
    url: "upload_article_to_workflow.php",
    dataType: "json",
    data: { cust_id: customerid, j_id: jid, pub_id: pubid },
    success: function(data) {
    // Hide the loader
       // Hide overlay and loader
       $("#overlay").hide();
       $("#loader").hide();
     // alert('ajax success');
     resetForm();
	},
  complete:function(response){
 


swal({
  title:"Your article is Submitted",
   type: "success",
		showCancelButton: false,
		confirmButtonColor: '#DD6B55',
		confirmButtonText: 'Ok!',
    confirmButtonText: 'CLOSE',
		closeOnConfirm: false,
		closeOnCancel: false
	},
	function(isConfirm){
    if (isConfirm){

      swal("Uploaded!", "Thank You", "success");
     // window.top.location="view_users.php?del="+del;
     window.location.href = '<?php echo $_COOKIE['set_url'];?>/articles';

    } else {
      swal("Uploaded!", "Thank You", "success");
     // window.top.location="view_users.php?del="+del;
     window.location.href = '<?php echo $_COOKIE['set_url'];?>/articles';
    }
	});

}, 
    error: function() {
                // Hide overlay and loader on error
                $("#overlay").hide();
                $("#loader").hide();
                console.log("An error occurred.");
            }
});
}
else
{
   swal({
   title:"Please Upload Doc or any file to Proceed",
 type: "info",  
confirmButtonColor: '#DD6B55',
confirmButtonText: 'CLOSE',
});

 // Hide overlay and loader on error
                $("#overlay").hide();
                $("#loader").hide();
                console.log("An error occurred.");

}

	});

  function resetForm() 
{
	$("#articlesubmission")[0].reset();

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
  if ($('#cust_id').length && (c || j || d)) {
    if (c) { $('#cust_id').val(c).trigger('change'); }                      /* the journal list reloads for the customer: wait for it */
    var tries = 0, iv = setInterval(function () {
      tries++;
      if (!j || $('#j_id option[value="' + j + '"]').length || tries > 30) {
        clearInterval(iv);
        if (j) { $('#j_id').val(j).trigger('change'); }
        if (d) { $('#dept_id').val(d).trigger('change'); }
        openStage();
      }
    }, 150);
  } else { openStage(); }
});
</script>
<?php fu_foot('#edit_move_files', null, array(), null, fu_ui_basic('Edit Move Files', 'Edit Move Files &rsaquo; Edit')); ?>
  <?php include __DIR__ . '/includes/move-files-stage-depts.php'; ?>
  <?php
  $mfn_existing = array();
  foreach ($move_files_folders as $st => $depts) { foreach ($depts as $dp => $mff) { if (!empty($mff['move_name_rules'])) { $mfn_existing[$st][$dp] = $mff['move_name_rules']; } } }
  include __DIR__ . '/includes/move-name-rules-ui.php';
  ?>
</body>
</html>
