<?php include "includes/includes-sw2.php";?>

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
.addplusicon {
    height: 32px;
    width: 32px;
    border-radius: 5px;
    background-color: #222;
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
	margin-left:90%;	

}

.delicon {
    height: 32px;
    width: 32px;
    border-radius: 50%;
    background-color: #f11010;
    display: flex;
    justify-content: center;
    align-items: center;
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
            <p class="articleheading ms-4">Add Employee Target</p>
          </div>
          <div class="col-4 col-lg-2">
            <div class="row">
              <div class="col-3 articlebtns d-flex justify-content-end">
                <a href="javascript:history.back();">
                  <div  class="back_icon"><img src="<?php echo $_COOKIE['set_url'];?>/assets/images/backarrow.svg"></div>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
          <form action="" method="post" name="add_target" id="add_target" enctype="multipart/form-data">
            <div class="container mt-4 separatetitle">
				<div class="formcont1heading">Add Target</div>
				<br/>
				  <div class="col-md-12">
				   <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Choose Employee <span style="color: red">*</span></label>
                  <select class="form-select employee_drop" data-placeholder="Select Employee" name="user_id" id="user_id" required>
                    <option value="">Select Employee</option>
											<?php
											$sql_users = $auth->showUserlist();
											foreach($sql_users as $res_users)
											{
											?>
											<option value="<?php echo $res_users['id'] ?>"style="font-weight:500;"><?php echo $res_users['full_name'] ?> </option>
											<?php
											}
											?>
                </select>
                </div>
				   <div class="col-md-12">
						<div class="addplusicon add_cauthor" style="cursor:pointer;" id="add_cauthor" onclick="addRow()">+</div>
					</div>
                      <div class="" id="widget-box-2" >
              <div class="widget-body" style="padding-top: 20px; margin: 20px">
													<div class="widget-main no-padding">
														<table class="table table-hover" id="myTable">
															<thead class="thin-border-bottom">
																<tr>
																	<th>Customer</th>
																	<th>Article Count</th>
																	<th>Page Count</th>
																	<th class="hidden-480">Action</th>
																</tr>
															</thead>

															<tbody>
																<tr>
																<td class=""> <select class="form-select" data-placeholder="Select Customer" name="cust_id[]" id="cust_id" style="width:250px;" >
												    <option value="all">Select All</option>

										  <?php
										  if($_COOKIE['user_level']=='3')
										  {
											$sql_customers = $auth->showCustomerlistbyuser($_COOKIE['cust_id'],$_COOKIE['ocust_id']);
										  }
										  else
										  {
											$sql_customers = $auth->showCustomerlist();
										  }
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
                </select></td>
																<td  class=""><input type="text" class="form-control" name="ac[]" id="ac" value="0" oninput="calculateTotal()"></td>
																<td class=""><input type="text" class="form-control" name="pc[]" id="pc" value="0" oninput="calculateTotal()"></td>
																<td class="hidden-480"></td>
																</tr>


																	<tr>
																<td class="">Total</td>
																<td  class=""><input type="text" class="form-control" name="tac" id="tac" value="0"></td>
																<td class=""><input type="text" class="form-control" name="tpc" id="tpc" value="0"></td>
																<td class="hidden-480"></td>
																</tr>
																	<tr>
																<td class="">Per Day Average</td>
																<td  class=""><input type="text" class="form-control" name="aac" id="aac" value="0.00"></td>
																<td class=""><input type="text" class="form-control" name="apc" id="apc" value="0.00"></td>
																<td class="hidden-480"></td>
																</tr>
															</tbody>

														</table>
															<input type="hidden" name="action" value="update">

													</div>
													</div>
			</div>
			
			 <div class="meta_nxtbtn container-fluid mt-5 mb-5" >
                <div class="row">
                    <div class="col form_btn cancelbtn d-flex justify-content-end">
                        <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/employeetargetassign" style="text-decoration:none;">Cancel</a></button>
                        
                    </div>
                    <div class="col form_btn nxtbtn">
                        <button type="submit" class="btn-next" >Save</button>
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
      
	// Function to calculate totals and update the Total row
function calculateTotal() {
    var table = document.getElementById("myTable");
    var rows = table.getElementsByTagName('tr');
    var totalAC = 0;
    var totalPC = 0;
    
    // Iterate through rows to calculate sum of AC and PC values
    for (var i = 1; i < rows.length - 2; i++) {
        var acInput = parseInt(rows[i].querySelector('input[name="ac[]"]').value) || 0;
        var pcInput = parseInt(rows[i].querySelector('input[name="pc[]"]').value) || 0;
        totalAC += acInput;
        totalPC += pcInput;
    }
    
    // Update Total row with calculated totals
    var totalRow = table.rows[rows.length - 2];
    totalRow.querySelector('input[name="tac"]').value = totalAC;
    totalRow.querySelector('input[name="tpc"]').value = totalPC;
	
	 // Calculate Per Day Average
    var perDayAverageRow = table.rows[rows.length - 1];
    var averageAC = totalAC / (rows.length - 3); // Excluding the header and total rows
    var averagePC = totalPC / (rows.length - 3); // Excluding the header and total rows
    perDayAverageRow.querySelector('input[name="aac"]').value = averageAC.toFixed(2);
    perDayAverageRow.querySelector('input[name="apc"]').value = averagePC.toFixed(2);
}

// Function to add a new row
function addRow() {
    var table = document.getElementById("myTable").getElementsByTagName('tbody')[0];
    var newRow = table.insertRow(table.rows.length - 2); // Insert a new row before the "Total" row
    
    // Insert cells for Customer, AC, PC
    for (var i = 0; i < 3; i++) {
        var newCell = newRow.insertCell(i);
        if (i === 0) {
            // For Customer cell, copy content from the first row
            newCell.innerHTML = table.rows[0].cells[i].innerHTML;
        } else {
            // For AC and PC cells, create input elements
            var input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control';
            input.name = i === 1 ? 'ac[]' : 'pc[]';
            input.value = '0';
            input.oninput = calculateTotal;
            newCell.appendChild(input);
        }
    }
    
    // Insert cell for Action (Delete) button
    newRow.insertCell(3).innerHTML = '<p onclick="deleteRow(this)" class="delicon" style="cursor:pointer;"><img src="assets/images/delicon.svg" style="cursor:pointer;"></p>';
}


// Function to delete a row
function deleteRow(btn) {
    var row = btn.parentNode.parentNode;
    row.parentNode.removeChild(row);
    calculateTotal(); // Recalculate totals after deleting a row
}


        </script>
	      <script src="assets/js/select2/select2.min.js"></script>
	 <script>
         $(".employee_drop").select2({
           placeholder: "Select Employee",
         });
		 $(".customer_drop").select2({
           placeholder: "Select Customers",
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
	<?php 
  if(isset($_POST['action']) && $_POST['action']=='update')
{

	$user_details=$auth->showUsersArticle($_REQUEST['user_id']);
	
	$user_target=mysqli_query($db,"INSERT INTO user_target_assign (user_id,total_ac,total_pc,average_ac,average_pc) values ('".$_REQUEST['user_id']."','".$_REQUEST['tac']."','".$_REQUEST['tpc']."','".$_REQUEST['aac']."','".$_REQUEST['apc']."') ");
	
			//echo "submitted".$_REQUEST['user_id'];exit;

	$uta_id=mysqli_insert_id($db);
// Retrieve the user_id and the corresponding pc[] and ac[] values from the form submission
$user_id = $_REQUEST['user_id']; // Assuming you get the user_id from your session or cookie
$pc_values = $_POST['pc'];
$ac_values = $_POST['ac'];
$cust_ids = $_POST['cust_id'];

// Loop through pc[] and ac[] arrays and insert values into the table
for ($i = 0; $i <count($pc_values); $i++) {
    $ac_value = $ac_values[$i];
    $pc_value = $pc_values[$i];
    $cust_id = $cust_ids[$i];
    
    // Construct and execute the SQL INSERT query
	
    $sql_uca= mysqli_query($db,"INSERT INTO user_target_customer_assign (uta_id,user_id,cust_id,article_count, page_count) VALUES ('$uta_id','$user_id','$cust_id', '$ac_value', '$pc_value')");
    
}

					echo "<script type='text/javascript'>";
 echo "swal({
    title: 'Target Assigned For user ".$user_details[0]['full_name']." Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Redirect the user
window.location.href = '".$_COOKIE['set_url']."/employeetargetassign';
});";
 echo "</script>";
					}
					

/////////////// Update completed ///////////////
?>
	
 
  </body>
</html>
