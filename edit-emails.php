<?php include "includes/includes.php";?>
<?php require_once __DIR__.'/includes/form_ui.php'; ?>
<?php include_once "includes/change_log_mail.php";?>
<?php

if(isset($_POST['action']) && $_POST['action']=='update')
{
	foreach($_POST as $key => $value) {
		$post[$key] = $value;
	}

	// Snapshot before change (for change-log email)
	$__old = change_log_snapshot('inw_dispatch_mail_master', 'dm_id', $post['id']);

	
	$sql = "SELECT * FROM inw_dispatch_mail_master WHERE cust_id = '".$post['cust_id']."' AND j_id='".$post['j_id']."' AND stage = '".$post['stage']."' ";
	$cust = $db->query($sql) or ($db->error);

	
	if($cust->num_rows >0)
		{
			$sql2 = "SELECT * FROM inw_dispatch_mail_master WHERE dm_id = '".$_REQUEST['id']."' ";
	        $cust2 = $db->query($sql2) or ($db->error);
	        $row2 = $cust2->fetch_assoc();
			if($row2['cust_id']== $post['cust_id'] && $row2['j_id']== $post['j_id'] && $row2['stage']== $post['stage'])
	        {
				$_POST['message'] = addslashes($_POST['message']);
				
				$update_query="UPDATE inw_dispatch_mail_master SET `stage` ='". $post['stage']."',cust_id ='". $post['cust_id']."',j_id ='". $post['j_id']."',`subject` ='". $post['subject']."',`message` ='". $_POST['message']."' WHERE dm_id = ".$post['id']." ";	
	if($db->query($update_query))
	{
		log_row_change('Dispatch Email', $post['id'], $__old, array('stage'=>$post['stage'],'cust_id'=>$post['cust_id'],'j_id'=>$post['j_id'],'subject'=>$post['subject'],'message'=>$_POST['message']));
		header("location:".$_COOKIE['set_url']."/emails?msg=Successfully completed"); 
		die();
	}
	else
	{
		$err[] = "Please try again.";
	}
}
          else
		  {
			  $error="This Information Already Added, <b>To Modify Go To Edit Page</b>";
		    header("location:".$_COOKIE['set_url']."/emails?msg= This Data Was Already Generated<b>, Try Other Data </b>");
			exit;
		  }
			}
			else
			{
				$update_query="UPDATE inw_dispatch_mail_master SET `stage` ='". $post['stage']."',cust_id ='". $post['cust_id']."',j_id ='". $post['j_id']."',`subject` ='". $post['subject']."',`message` ='". $_POST['message']."' WHERE dm_id = ".$post['id']." ";	
	if($db->query($update_query))
	{
		log_row_change('Dispatch Email', $post['id'], $__old, array('stage'=>$post['stage'],'cust_id'=>$post['cust_id'],'j_id'=>$post['j_id'],'subject'=>$post['subject'],'message'=>$_POST['message']));
		header("location:".$_COOKIE['set_url']."/emails?msg=Successfully completed"); 
		die();
	}
	else
	{
		$err[] = "Please try again.";
	}
}
			}
		

   //print_r($_POST); exit;	
	
/////////////// Update completed ///////////////

if(isset($_REQUEST['id']))
{
	$result = $db->query("SELECT * FROM inw_dispatch_mail_master WHERE dm_id = ".$_REQUEST['id']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$app_res = $result->fetch_assoc();
	}	
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
    <?php fu_head('add_article'); ?>	

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
            <p class="articleheading ms-4">Edit Dispatch Mail Config</p>
          </div>
          <div class="col-4 col-lg-2">
            <div class="row d-flex justify-content-end pe-2">
              <div class="col-3 articlebtns">
                <a href="javascript:history.back();">
                  <div  class="back_icon"><img src="<?php echo $_COOKIE['set_url'];?>/assets/images/backarrow.svg"></div>
                </a>
              </div>
            </div>
          </div>
        </div> 
  </div>

      <?php fu_hero(fu_ui_basic('Edit Dispatch Mail Config', 'Edit Dispatch Mail Config &rsaquo; Edit')); ?>
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
          <form action="" method="post" name="add_article" id="add_article" enctype="multipart/form-data">
            <div class="container mt-4 ">
              <div class="formcont1heading">Edit Dispatch Mail Config</div>
              <div class="row mt-1 g-4 ">
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
													 <option value="<?php echo $res_customers['id'] ?>"style="font-weight:500;" <?php if($app_res['cust_id'] ==$res_customers['id']){?> selected <?php } ?>><?php echo $res_customers['cust_name'] ?> </option>
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
													 <option value="<?php echo $res_journals['j_id'] ?>" style="font-weight:500;" <?php if($app_res['j_id'] ==$res_journals['j_id']){?> selected <?php } ?>><?php echo $res_journals['j_code'] ?> </option>
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
                       <select class="form-select journal_drop" data-placeholder="Select Journal" name="stage" >
                                  <option value="">-Select-</option>
                                  <?php foreach($project_status_types as $id=>$val){?>
                                  <option value="<?php echo $id; ?>" <?php if($app_res['stage'] ==$id){?> selected <?php } ?>><?php echo $val; ?></option>
                                  <?php } ?>
                                </select>
                </div>

                <div class="col-12 col-sm-6 col-lg-6">
              <label class="mb-2">Subject<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="subject" id="subject"   placeholder="Enter Subject" value="<?php echo $app_res['subject'] ?>" required>
              </input>
                </div>

                <div class="col-12  col-lg-12">
              <label class="mb-2">Message<span style="color: red">*</span></label>
              <textarea name="message" id="message" class="form-control" rows="10" placeholder="Comments" style=" min-height: 100px;"><?php echo $app_res['message'] ?></textarea>

              </input>
                </div>
                <div class="meta_nxtbtn container-fluid mt-5 mb-5" >
                <div class="row">
                    <div class="col form_btn cancelbtn d-flex justify-content-end">
                        <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/emails" style="text-decoration:none;">Cancel</a></button>
                        
                    </div>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?php echo $_REQUEST['id']; ?>" />
                    <div class="col form_btn nxtbtn">
                        <button type="submit" class="btn-next" >Next</button>
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
        $('#message').richText();//this will create the default editor will all buttons			

    </script>


<?php fu_foot('#add_article', null, array(), null, fu_ui_basic('Edit Dispatch Mail Config', 'Edit Dispatch Mail Config &rsaquo; Edit')); ?>
  </body>
</html>
