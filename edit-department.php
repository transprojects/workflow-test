<?php include "includes/includes.php";?>
<?php require_once __DIR__.'/includes/form_ui.php'; ?>
<?php include_once "includes/change_log_mail.php";?>
<?php require_once __DIR__.'/sw_switch_lib.php'; sw_dept_ensure($db); ?>
<?php
if(isset($_POST['action']) && $_POST['action']=='update')
{
	foreach($_POST as $key => $value) {
		$post[$key] = $value;
	}	
	/// Snapshot the existing values (before change) for the change-log email ///
	$log_dept_code = (int) $post['id'];
	$res_old_d = $db->query("SELECT * FROM adm_dept_master WHERE dept_code = ".$log_dept_code);
	$old_d = ($res_old_d && $res_old_d->num_rows > 0) ? $res_old_d->fetch_assoc() : array();

	$post['manager_ids'] = (isset($_POST['manager_ids']) && is_array($_POST['manager_ids'])) ? implode(',', array_filter(array_map('intval', $_POST['manager_ids']))) : '';
	$post['dept_email'] = implode(', ', sw_switch_parse_list($post['dept_email'])['valid']);
	$update_query="UPDATE adm_dept_master SET `dept_email` ='".$db->real_escape_string($post['dept_email'])."',`manager_ids` ='".$post['manager_ids']."',`dept_name` ='". $post['dept_name']."',dept_sn ='". $post['dept_sn']."',parent_id ='". $post['parent_id']."',`stage_order` ='". $post['stage_order']."',`accept_files` ='". $post['accept_files']."' WHERE dept_code = ".$post['id']." ";	
	if($db->query($update_query))
	{
		/// Email the change log (text attachment); a mail failure must not block the save ///
		try {
			$dept_fields = array('dept_name', 'dept_sn', 'parent_id', 'stage_order', 'accept_files', 'dept_email', 'manager_ids');
			$d_log = "[Department changes]\r\n";
			$d_changes = 0;
			foreach ($dept_fields as $f) {
				$old = isset($old_d[$f]) ? (string) $old_d[$f] : '';
				$new = isset($post[$f]) ? (string) $post[$f] : '';
				if ($old !== $new) {
					$d_log .= "  $f: '$old' -> '$new'\r\n";
					$d_changes++;
				}
			}
			if ($d_changes == 0) {
				$d_log .= "  (no change)\r\n";
			}
			$d_ref = $log_dept_code . (!empty($old_d['dept_name']) ? " / " . $old_d['dept_name'] : '');
			send_change_log_mail('Department', $d_ref, $d_log, $d_changes);
		} catch (Exception $e) {
			error_log("edit-department change-log mail failed: " . $e->getMessage());
		}
		header("location:".$_COOKIE['set_url']."/managedepartments?msg=Successfully completed"); 
		die();
	}
	else
	{
		$err[] = "Please try again.";
	}
}
/////////////// Update completed ///////////////

if(isset($_REQUEST['id']))
{
	$result = $db->query("SELECT * FROM adm_dept_master WHERE dept_code = ".$_REQUEST['id']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$app_res = $result->fetch_assoc();
	}	
}

/// Get department status if assigned to any workflow or not ////
$wf_status=0;
$sql = "SELECT wf_code FROM adm_workflow_master";
	$result = $db->query($sql);
	if($result->num_rows > 0)
	{
		while($row = $result->fetch_assoc()) 
		{
			$wf_code =   explode(",",$row["wf_code"]);
			if (in_array($_REQUEST['id'], $wf_code))
			{
				$wf_status = 1;
				break;
			}
			else
			{
				$wf_status = 0;
			}
  		}
	}
	else
	{
		$wf_status = 0;
	}
	// echo $status;
	

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php fu_head('edit_department'); ?>	

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
      </style>
  </head>

  <body>
  <div id="loader" class="loader-container">
        <div class="loader"></div>
    </div>
    <div id="overlay" class="overlay"></div>
    <div class="wholeouter">
    <?php include("includes/top-header.php");?>


      <div class="container-fluid mt-4">

      <div div class="row pt-4 d-flex align-items-center flex-wrap">
          <div class="col-8 col-lg-10">
            <p class="articleheading ms-4">Edit Department</p>
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
        <!-- <div class="row">
          <div class="col-5 addarticleinfoicon">
            <div class="arinfoicon">
              <img
                class="articleuploadstatusimg"
                src="<?php echo $_COOKIE['set_url'];?>/assets/images/info-circle.svg"
              />
              <p class="arinfotext">Basic Information</p>
            </div>
          </div>
          <div class="col-2 dotteddiv">
            <span class="dottedline"></span>
          </div>
          <div class="col-5 addarticleinfoicon1">
            <div class="arinfoicon">    
              <img
                class="articleuploadstatusimg"
                src="<?php echo $_COOKIE['set_url'];?>/assets/images/info-upload.svg"
              />
              <p class="arinfotext">Upload Manuscript</p>
            </div>
          </div>
        </div>
      </div> -->

      </div>
<?php fu_hero(fu_ui_basic('Edit Department', 'Edit Department &rsaquo; Edit')); ?>
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
          <form action="" method="post" name="edit_department" id="edit_department" enctype="multipart/form-data">
            

              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" id="id" value="<?php echo $_REQUEST['id'];?>">

              <!-- <div class="col-12 col-sm-6 col-lg-4 d-flex align-items-center">
                  <label class="mb-2">Is Grouo Folder:&nbsp<span style="color: red"></span> </label>
                  <input type="checkbox"name="lbl" id="lbl" placeholder="" required></input>
                </div> -->
                <div class="container mt-4">
              <div class="formcont1heading">Edit Department</div>
              <div class="row mt-2 g-4 addspacing">
              <div class="col-12 col-sm-6 col-lg-4">
              <?php
	$query = "SELECT * FROM `adm_dept_master` WHERE `parent_id` = 0 ORDER BY `dept_name` ASC";
	$customers = $db->query($query) or ($db->error);		

	?>
                  <label class="mb-2">Parent Department </label>
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="parent_id" id="parent_id">
                  <option value="">Select</option>

                  <?php while($row = $customers->fetch_assoc()){ ?>
                                  <option value="<?php echo $row['dept_code']; ?>" <?php if($app_res['parent_id'] == $row['dept_code']){?> selected <?php } ?>><?php echo $row['dept_name']; ?></option>
                                  <?php } ?>
                </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Department Name<span style="color: red">*</span></label>
                 <input type="text" class="form-control" name="dept_name" id="dept_name" placeholder="Enter department name" value="<?php echo $app_res['dept_name']; ?>" required>
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Department Shortname<span style="color: red">*</span></label>
                 <input type="text" class="form-control" name="dept_sn" id="dept_sn" placeholder="Enter department shortname" value="<?php echo $app_res['dept_sn']; ?>" required>
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Accepted Files<span style="color: red">*</span></label>
                 <input type="text" class="form-control" name="accept_files" id="accept_files" placeholder="Enter file name" value="<?php echo $app_res['accept_files']; ?>" required>
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Department Order<span style="color: red">*</span></label>
                 <input type="text" class="form-control" name="stage_order" id="stage_order" placeholder="Enter department order" value="<?php echo $app_res['stage_order']; ?>" required>
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Department E-mail</label>
                 <input type="text" class="form-control" name="dept_email" id="dept_email" placeholder="dept@transforma.in (several: separate with comma)" value="<?php echo htmlspecialchars($app_res['dept_email']); ?>">
                </div>
                <div class="col-12 col-sm-6 col-lg-8">
                 <label class="mb-2">Department Managers (reporting)</label>
                 <select class="form-select manager_drop" multiple name="manager_ids[]" id="manager_ids" data-placeholder="Select managers" style="width:100%">
                 <option value="all">Select All</option>
                 <?php echo sw_dept_manager_options($db, (int)$app_res['dept_code'], $app_res['manager_ids']); ?>
                 </select>
                 <small style="color:#6b7a89;">The department e-mail is the To address and the managers are CC'd on department-wise notifications, e.g. "file movement not configured" for an article that is in this department.</small>
                </div>
				</div>
    </div>
                <div class="meta_nxtbtn container-fluid mt-5 mb-5" >
                <div class="row">
                    <div class="col form_btn cancelbtn d-flex justify-content-end">
                        <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/managedepartments" style="text-decoration:none;">Cancel</a></button>
                        
                    </div>
                    <div class="col form_btn nxtbtn">
                        <button type="submit" class="btn-next" >Save</button>
                    </div>
                </div>
            </div>
      </div>
    </div>
      <!-- upload section -->
    
        </div>
      </div>
      <?php echo scripts(); ?>	

       <!-- Footer Section -->
       <?php echo footer(); ?>
     
 <!-- Date picker functionality -->

 <script src="assets/js/select2/select2.min.js"></script>
<style>
.mgr-dd{position:relative;width:100%}
.mgr-box{min-height:44px;border:1px solid #cfd8e3;border-radius:10px;background:#fbfcfe;padding:4px 30px 4px 8px;cursor:pointer;display:flex;flex-wrap:wrap;gap:4px;align-items:center;position:relative}
.mgr-box:after{content:"";position:absolute;right:12px;top:50%;margin-top:-3px;border:5px solid transparent;border-top-color:#667;}
.mgr-box.open{border-color:#1b6ca8}
.mgr-ph{color:#8793a0;font-size:14px}
.mgr-tag{background:#eaf2fa;border:1px solid #c5d8ea;color:#14304a;border-radius:8px;padding:2px 6px 2px 8px;font-size:13px;display:inline-flex;gap:6px;align-items:center}
.mgr-tag b{cursor:pointer;font-weight:700;color:#5b7a95}
.mgr-panel{display:none;position:absolute;z-index:1000;left:0;right:0;top:100%;margin-top:4px;background:#fff;border:1px solid #cfd8e3;border-radius:10px;box-shadow:0 8px 24px rgba(20,48,74,.15);overflow:hidden}
.mgr-panel.open{display:block}
.mgr-panel input.mgr-search{width:100%;border:0;border-bottom:1px solid #e3e9f0;padding:9px 12px;outline:0;font-size:14px}
.mgr-list{max-height:260px;overflow-y:auto}
.mgr-grp{padding:6px 12px;font-size:11px;font-weight:700;text-transform:uppercase;color:#667b8f;background:#f4f7fa}
.mgr-opt{padding:8px 12px;font-size:14px;cursor:pointer;display:flex;gap:8px;align-items:center}
.mgr-opt:hover{background:#eef4fa}
.mgr-opt.all{background:#1b2f4b;color:#fff;font-weight:700}
.mgr-opt.all:hover{background:#243b5c}
</style>
<script>
(function () {
  var sel = document.getElementById("manager_ids");
  if (!sel) { return; }
  sel.style.display = "none";
  var wrap = document.createElement("div"); wrap.className = "mgr-dd";
  var box = document.createElement("div"); box.className = "mgr-box";
  var panel = document.createElement("div"); panel.className = "mgr-panel";
  var search = document.createElement("input"); search.type = "text"; search.className = "mgr-search"; search.placeholder = "Search";
  var list = document.createElement("div"); list.className = "mgr-list";
  panel.appendChild(search); panel.appendChild(list);
  wrap.appendChild(box); wrap.appendChild(panel);
  sel.parentNode.insertBefore(wrap, sel.nextSibling);
  var opts = [].slice.call(sel.options).filter(function (o) { return o.value !== "all"; });

  function renderBox() {
    box.innerHTML = "";
    var chosen = opts.filter(function (o) { return o.selected; });
    if (!chosen.length) { box.innerHTML = '<span class="mgr-ph">Select managers</span>'; return; }
    chosen.forEach(function (o) {
      var t = document.createElement("span"); t.className = "mgr-tag";
      t.appendChild(document.createTextNode(o.text.replace(/\s*\(.*\)$/, "")));
      var x = document.createElement("b"); x.textContent = "\u00d7";
      x.onclick = function (e) { e.stopPropagation(); o.selected = false; renderBox(); renderList(); };
      t.appendChild(x); box.appendChild(t);
    });
  }
  function row(label, cls, checked, fn) {
    var d = document.createElement("div"); d.className = "mgr-opt " + cls;
    var c = document.createElement("input"); c.type = "checkbox"; c.checked = checked;
    d.appendChild(c); d.appendChild(document.createTextNode(label));
    d.onclick = function (e) { fn(); renderBox(); renderList(); };
    return d;
  }
  function renderList() {
    var q = search.value.toLowerCase(), top = list.scrollTop, lastGrp = null;
    list.innerHTML = "";
    var all = opts.every(function (o) { return o.selected; });
    list.appendChild(row("Select All", "all", all && opts.length > 0, function () { opts.forEach(function (o) { o.selected = !all; }); }));
    opts.forEach(function (o) {
      if (q && o.text.toLowerCase().indexOf(q) === -1) { return; }
      var g = o.parentNode.tagName === "OPTGROUP" ? o.parentNode.label : "";
      if (g !== lastGrp) { lastGrp = g; if (g) { var h = document.createElement("div"); h.className = "mgr-grp"; h.textContent = g; list.appendChild(h); } }
      list.appendChild(row(o.text, "", o.selected, function () { o.selected = !o.selected; }));
    });
    list.scrollTop = top;
  }
  box.onclick = function () { var o = !panel.classList.contains("open"); panel.classList.toggle("open", o); box.classList.toggle("open", o); if (o) { search.value = ""; renderList(); search.focus(); } };
  search.oninput = renderList;
  document.addEventListener("click", function (e) { if (!wrap.contains(e.target)) { panel.classList.remove("open"); box.classList.remove("open"); } });
  renderBox();
})();
</script>
   
   <script>
     $(".customer_drop").select2({
       placeholder: "Select Customer",
     });
     $(".journal_drop").select2({
       placeholder: "Select Journal",
     });
     $(".stage_drop").select2({
       placeholder: "Select Stage",
     });
     $(".department_drop").select2({
       placeholder: "Select Department",
     });
     $(".platform_drop").select2({
       placeholder: "Select Platform",
     });
   </script>
 <script>
    


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
    $("#edit_department").validate({
        rules: {

          j_cust_id: "required",
          j_name: "required",
        

           
        },
        messages: {

          j_cust_id: "Select your customer ID",
          j_name: "Please enter Journal Name",
        
           
        },

      
    
});  
});  

  </script> 
  <?php fu_foot('#edit_department', null, array(), null, fu_ui_basic('Edit Department', 'Edit Department &rsaquo; Edit')); ?>
  </body>
</html>
