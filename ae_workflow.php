<?php include "includes/includes.php";?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>	

    <!-- Data Table CSS -->
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
.ui-helper-hidden { display: block !important; }
.ui-state-default ui-element { display: block !important; }
.ui-icon { display: block; text-indent: -99999px; overflow: hidden; background-repeat: no-repeat; }

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
            <p class="articleheading ms-4">Add / Edit Workflow</p>
          </div>
        </div> 
  </div>

      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
          <form action="" method="post" name="add_article" id="add_article" enctype="multipart/form-data">
            <div class="container mt-4 separatetitle">
              <div class="formcont1heading">Add New Customer</div>
              <div class="row mt-1 g-4 addspacing">
              <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Work-Type<span style="color: red">*</span></label>
                  <select class="form-select customer_drop" data-placeholder="Select Customer" name="w_cust_id" id="w_cust_id" required>
                    <option value="">Select type</option>
                   <?php
											$sql_customers = $auth->showCustomerlist();
											foreach($sql_customers as $res_customers)
											{
												
									      	if($update_articles[0]['cust_id']!="")
													{
										    	?>
										       <option <?php if ($res_customers['id']== $update_articles[0]['cust_id']){?>selected<?php }?> value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
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
              <label class="mb-2">Workflow Name<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="wf_name" id="wf_name"  value="<?php echo $update_articles[0]['article_title']; ?>" placeholder="Enter workflow name" required>
              </input>
                </div>


                <div class="space-2"></div>
                          <h3 class="lighter block green">Add the Workflow steps</h3>
                             <?php							
	$query = "SELECT * FROM `adm_dept_master` WHERE parent_id = 0 ORDER BY `dept_code` ASC";
	$customers = $db->query($query) or ($db->error);		

	?>
                                <select id="departments" name="departments[]" class="multiselect col-xs-12 col-sm-12" multiple="multiple">
                                  <?php while($row = $customers->fetch_assoc()){ ?>
                                  <option value="<?php echo $row['dept_code']; ?>"><?php echo $row['dept_name']; ?></option>
                                  <?php } ?>
                                </select>
                          <div class="hr hr-dotted"></div>


                </div>
            </div>
            <div class="meta_nxtbtn container-fluid mt-5 mb-5" >
                <div class="row">
                    <div class="col form_btn cancelbtn d-flex justify-content-end">
                        <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/edit-article/<?php echo $_REQUEST['aid'];?>" style="text-decoration:none;">Cancel</a></button>
                        
                    </div>
                    <div class="col form_btn nxtbtn">
                        <button type="submit" class="btn-next" >Save</button>
                    </div>
                </div>
            </div>
      </form>
    
        </div>
      </div>
      <?php echo scripts(); ?>	
<script type="text/javascript">

$(document).ready(function() {
 	$.localise('ui-multiselect', {language: 'en', path: 'assets/js/locale/'});
			$(".multiselect").multiselect();
			//$('#switcher').themeswitcher();
});
	</script>
       <!-- Footer Section -->
       <?php echo footer(); ?>
     
 <!-- Date picker functionality -->
 
  </body>
</html>
