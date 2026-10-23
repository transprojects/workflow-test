<?php include "includes/includes.php";?>
<?php require_once __DIR__.'/includes/form_ui.php'; ?>
<?php include_once "includes/change_log_mail.php";?>
<?php
/// Add New record

if(isset($_POST['action']) && $_POST['action']=='update')
{
	foreach($_POST as $key => $value) {
		$post[$key] = $value;
	}	
	// Snapshot before change (for change-log email)
	$__old = change_log_snapshot('inw_journal_folders', 'jf_id', $post['id']);
	$update_query="UPDATE inw_journal_folders SET `folder_name` ='". $post['folder_name']."',cust_id ='". $post['cust_id']."',j_id ='". $post['j_id']."',is_group_folder ='". $post['is_group_folder']."' WHERE jf_id = ".$post['id']." ";	
	if($db->query($update_query))
	{
		log_row_change('Folder', $post['id'], $__old, array('folder_name'=>$post['folder_name'],'cust_id'=>$post['cust_id'],'j_id'=>$post['j_id'],'is_group_folder'=>$post['is_group_folder']), array());
		
		/// Get the Customer Details /////
	
	$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = '".$_POST['cust_id']."' ";
	$customers = $db->query($query) or ($db->error);
	$crow = $customers->fetch_assoc();
 		
	
	//// Journal name ///	
	$query = "SELECT j_name,j_code FROM `adm_journals` WHERE j_id = '".$post['j_id']."';";
	$steps = $db->query($query) or ($db->error);
	$jninfo = $steps->fetch_assoc();
	
	$Cn= $crow['cust_name'];
	$journal_title = $jninfo['j_code']; 
	
		$dest = WF_DIR."/downloads/JOURNALS/".$Cn."/".$journal_title."/".VOLUME_FOLDER_NAME."/".$post['folder_name']."/misc";   // Create misc folder 
		if(!file_exists($dest))
		{
			mkdir($dest, 0777, true);
		}
		
		$dest = WF_DIR."/production/JOURNALS/".$Cn."/".$journal_title."/".VOLUME_FOLDER_NAME."/".$post['folder_name'];   // Create misc folder 
		
		if(!file_exists($dest))
		{
			mkdir($dest, 0777, true);
		}
			
		header("location:".$_COOKIE['set_url']."/managefolder?msg=Successfully completed"); 
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
	$result = $db->query("SELECT * FROM inw_journal_folders WHERE jf_id = ".$_REQUEST['id']." "); 	
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
      </style>
  </head>

  <body>
  <div id="loader" class="loader-container">
        <div class="loader"></div>
    </div>
    <div id="overlay" class="overlay"></div>
    <div class="wholeouter">
    <?php include("includes/top-header.php");?>


      <!-- form section  -->
      <div class="container-fluid mt-4">

      <div div class="row pt-4 d-flex align-items-center flex-wrap">
          <div class="col-8 col-lg-10">
            <p class="articleheading ms-4">Edit Folder</p>
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
<?php fu_hero(fu_ui_basic('Edit Folder', 'Edit Folder &rsaquo; Edit')); ?>
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
          <form action="" method="post" name="add_article" id="add_article" enctype="multipart/form-data">
            <div class="container mt-4">
              <div class="formcont1heading">Edit Folders  </div>
              <div class="row mt-1 g-4 addspacing">

            
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Customer<span style="color: red">*</span></label>
                  <select class="form-select customer_drop" data-placeholder="Select Customer" name="cust_id" id="cust_id"  required>
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
                  <label class="mb-2">Choose Platform<span style="color: red">*</span></label>
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_id" id="j_id" required >
                    <?php
											$sql_journals = $auth->showJournallist('0',2);
											foreach($sql_journals as $res_journals)
											{
												
									      	if(isset($_REQUEST['j_id']))
													{
										    	?>
										       <option <?php if (in_array($res_journals['j_id'], $ex_jid)){?>selected<?php }?> value="<?php echo $res_journals['j_id'] ?>"style="font-weight:500;"><?php echo $res_journals['j_code'] ?> </option>
													<?php
													}
													else
													{
													?>
													 <option value="<?php echo $res_journals['j_id'] ?>"style="font-weight:500;"><?php echo $res_journals['j_code'] ?> </option>
													<?php
													}
											}
											?>
                </select>
                </div>
               
                <!-- <div class="row mt-1 g-2"> -->
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Folder Name<span style= "color:red">*</span></label>
                 <input type="text" class="form-control" name="folder_name" id="folder_name" placeholder="Enter folder name " value="<?php echo $app_res['folder_name']; ?>">
                </input>
                </div>

                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Is Group Folder?<span style= "color:red">*</span></label>
                 <input type="checkbox" <?php if($app_res['is_group_folder'] ==1){ ?> checked <?php } ?> type="checkbox" id="is_group_folder" name="is_group_folder"  value="1">
                </input>
                </div>
			</div>
      </div>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?php echo $_REQUEST['id']; ?>" />
                <div class="meta_nxtbtn container-fluid mt-5 mb-5" >
                <div class="row">
                    <div class="col form_btn cancelbtn d-flex justify-content-end">
                        <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/managefolder">Cancel</a></button>
                        
                    </div>
                    <div class="col form_btn nxtbtn">
                        <button type="submit" class="btn-next" >Save</button>
                    </div>
                </div>
            </div>
      </form>

      <!-- upload section -->
    
        </div>
      </div>
      <?php echo scripts(); ?>	

       <!-- Footer Section -->
       <?php echo footer(); ?>
     
 <!-- Date picker functionality -->

 <script src="<?php echo $_COOKIE['set_url'];?>/assets/js/select2/select2.min.js"></script>
   
   <script>
     $(".customer_drop").select2({
       placeholder: "Select Customer",
     });
     $(".journal_drop").select2({
       placeholder: "Select Journal",
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
    $("#add_article").validate({
        rules: {

          cust_id: "required",
          j_id: "required",
        

           
        },
        messages: {

          cust_id: "Select your customer ID",
          j_id: "Please enter Journal Name",
        
           
        },

      
    
});  
});  

  </script> 
  <?php fu_foot('#add_article', null, array(), null, fu_ui_basic('Edit Folder', 'Edit Folder &rsaquo; Edit')); ?>
  </body>
</html>
