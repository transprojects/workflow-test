<?php include "includes/includes.php";?>
<?php require_once __DIR__.'/includes/form_ui.php'; ?>
<?php include_once "includes/change_log_mail.php";?>
<?php
/// Add New record
if(isset($_POST['action']) && $_POST['action'] == 'insert')
{ 
	foreach($_POST as $key => $value) {
		$post[$key] = $value;
	}	

   $article_details=mysqli_fetch_array(mysqli_query($db,"select id from inw_inward_dtl where pub_id LIKE '%".$post['article']."%'"));
   
	$insert_query = "INSERT INTO `inw_internal_feedback` (`cust_id`, `j_id`, `dept_id`, `u_id`, `stage`,article,inw_id,recv_dt,message,`created_by`, `created_on`) VALUES ('".$post['cust_id']."', '".$post['j_id']."', '".$post['dep_id']."', '".$post['user_id']."', '".$post['stage']."', '".$post['article']."', '".$article_details['id']."', '".date('Y-m-d',strtotime($post['recv_dt']))."','". addslashes($_POST['message'])."','".$_COOKIE['user_id']."', NOW());";	

		$db->query($insert_query) or die($db->error);

		// Change-log entry + email for the new record
		log_row_added('Internal Feedback', change_log_ref(array('article')), array_diff_key($_POST, array('action'=>1)), array());
		header("location:".$_COOKIE['set_url']."/internalfeedback?msg=Successfully added"); 
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
    <?php fu_head('add-internal-feedback'); ?>	

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
    border: 1px solid #003eff !important;
    background: #dd5c1c !important;
    font-weight: normal !important;
    color: #ffffff !important;
}
.ui-state-default .ui-icon {
     background-image:none !important; 
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
            <p class="articleheading ms-4">Add Art Configuration</p>
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

      <?php fu_hero(fu_ui_basic('Add Art Configuration', 'Add Art Configuration &rsaquo; Add')); ?>
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
          <form action="" method="post" name="add-internal-feedback" id="add-internal-feedback" enctype="multipart/form-data">
            <div class="container mt-4">
              <div class="formcont1heading">Add Art Configuration</div>
              <div class="row mt-1 g-4">
              <!-- <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Cutomer Name</label>
                 <input type="text" class="form-control" name="cust_name" id="cust_name" placeholder="Enter Customer Name" required>
                </input>
                </div> -->
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Customer<span style="color: red">*</span></label>
                  <select class="form-select customer_drop" data-placeholder="Select Journal" name="cust_id" id="cust_id" required>
                  <option value="">Select Customer</option>
                   <?php
											$sql_customers = $auth->showCustomerlist();
											foreach($sql_customers as $res_customers)
											{
											?>
													 <option value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
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
													 <option value="<?php echo $res_journals['j_id'] ?>" style="font-weight:500;" data-id="<?php echo $res_journals['j_code'] ?>_0000" data-previous="<?php echo $res_journals['j_code'] ?>_0000"><?php echo $res_journals['j_code'] ?> </option>
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
                  <label class="mb-2"> Stage </label>
                       <select class="form-select stage_drop" data-placeholder="Select Journal" name="stage" id="stage">
                                  <option value="">-Select-</option>
                                  <?php foreach($project_status_types as $id=>$val){?>
                                  <option value="<?php echo $id; ?>" ><?php echo $val; ?></option>
                                  <?php } ?>
                                </select>
                </div>
				
				 <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Department<span style="color: red">*</span></label>
                   <?php
    $query = "SELECT dept_name,dept_code FROM `adm_dept_master`  ORDER BY `dept_name` ASC ";
    $customers = $db->query($query) or ($db->error);		
    
    ?><select id="dep_id" name="dep_id" class="form-control dept_drop"  data-placeholder="Choose" required>
    <option value="">-Select-</option>
    <?php while($row = $customers->fetch_assoc()){ ?>
    <option value="<?php echo $row['dept_code']; ?>" <?php if($_REQUEST['dep_id'] ==$row['dept_code']){?> selected <?php } ?>><?php echo $row['dept_name']; ?></option>
    <?php } ?>
    </select>
                </div>			
    </div>	
                <div class="row mt-1 g-4">


                <div class="col-12 col-sm-6 col-lg-4">
                <label class="mb-2">Image Input Path<span style="color: red">*</span></label>
                <input type="text" class="form-control" name="path" id="path"  value="" placeholder="Eg: /Fig/imgpath" required>
                </input>
                </div>
                 <div class="col-12 col-sm-6 col-lg-4 fp" style="display:none">
                <label class="mb-2">Max Width<span style="color: red">*</span></label>
                <input type="number" class="form-control" name="maxwidth" id="maxwidth"  value="0" placeholder="Enter Max Width" required>
                </input>
                 </div>
                 <div class="col-12 col-sm-6 col-lg-4 fp" style="display:none">
                <label class="mb-2">Min Width<span style="color: red">*</span></label>
                <input type="number" class="form-control" name="minwidth" id="minwidth"  value="0" placeholder="Enter Min Width" required>
                </input>
                 </div>
                 <div class="col-12 col-sm-6 col-lg-4 fp" style="display:none">
                <label class="mb-2">Max Height<span style="color: red">*</span></label>
                <input type="number" class="form-control" name="maxheight" id="maxheight"  value="0" placeholder="Enter Max Height" required>
                </input>
                </div>
                 <div class="col-12 col-sm-6 col-lg-4 rev" style="display:none">
                <label class="mb-2">Padding<span style="color: red">*</span></label>
                <input type="number" class="form-control" name="padding" id="padding"  value="0" placeholder="Enter Padding" required>
                </input>
                 </div>
                 <div class="col-12 col-sm-6 col-lg-4">
                <label class="mb-2">Image Output Path<span style="color: red">*</span></label>
                <input type="text" class="form-control" name="outpath" id="outpath"  value="" placeholder="Eg: /Graphics/Web" required>
                </input>
                 </div>
                


                <div class="meta_nxtbtn container-fluid mt-5 mb-5" >
                <div class="row">
                    <div class="col form_btn cancelbtn d-flex justify-content-end">
                        <button type="reset" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/clientfeedback" style="text-decoration:none;">Cancel</a></button>
                        
                    </div>
                    <input type="hidden" name="id" id="id" value="">

                    <div class="col form_btn nxtbtn">
                        <button type="button" class="btn-next" onclick="save_edit_config()">Save</button>
                    </div>
                </div>
            </div>


                </div>
            </div>
              
    <!-- </head> -->
     
</form>
</div>
</div>
      <?php echo scripts(); ?>	

       <!-- Footer Section -->
       <?php echo footer(); ?>
   
    <!-- Date picker functionality -->
    <script src="assets/js/select2/select2.min.js"></script>
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

      $(document).ready(function() {
       $(".customer_drop").select2({
       placeholder: "Select Customer",
     });
     $(".journal_drop").select2({
       placeholder: "Select Journal",
     });
     $(".stage_drop").select2({
       placeholder: "Select stages",
     });
     $(".dept_drop").select2({
       placeholder: "Select department",
     });
    });

    $("#cust_id").on("change", function() {
         var cust_id=$.trim($(this).val());
         $.ajax({
             url: "get_journal.php",
             dataType: "json",
             data: { cust_id:cust_id },
             success: function(data) {
                 populateSelecte("j_id",data);

             }
         });
     });

$("#stage").on("change",function(){
var stage=$(this).val();
$(".fp").hide();
$(".rev").hide();
if(stage=="FP"){
$(".fp").show();
}else if(stage=="REV"){
$(".rev").show();
}
})
    });

        function populateSelecte(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.j_code).val(item.j_id));
            });
        }
        $('#message').richText();//this will create the default editor will all buttons			

  
	function getdata(){
    var val={
    'id':$("#id").val(),
    'cust_id':$("#cust_id").val(),
    'j_id':$("#j_id").val(),
    'stage':$("#stage").val(),
    'dept_id':$("#dep_id").val(),
    'path':$("#path").val(),
    'padding':$("#padding").val(),
    'maxwidth':$("#maxwidth").val(),
    'minwidth':$("#minwidth").val(),
    'height':$("#maxheight").val(),
    'out_path':$("#outpath").val(),
    }
    return val;
  }	
  function save_edit_config(){
    $.ajax({
                type:'POST',
                url:'saveart.php',
                data:getdata(),
                success:function(html){  
                  alert(html.trim().replace(/^"|"$/g, ''));        
                }
            });
  }
	</script>


<?php fu_foot('#add-internal-feedback', null, array(), null, fu_ui_basic('Add Art Configuration', 'Add Art Configuration &rsaquo; Add')); ?>
  </body>
</html>
