<?php include "includes/includes-sw2.php";?>
<?php 
	$user_details_target=$auth->showUsersTarget($_REQUEST['uid']);


// Retrieve existing data for the user from the database
$sql_select = "SELECT * FROM user_target_customer_assign WHERE uta_id = '".$_REQUEST['uid']."'";
$result = $db->query($sql_select);

// Initialize arrays to hold existing PC and AC values

if($user_details_target[0]['dept_id']==1 || $user_details_target[0]['dept_id']==22 )
{
$existing_pc_values = [];
$existing_ac_values = [];
}
else
{
$existing_pc_values = [];
$existing_ac_values = [];	

$existing_cpc_values = [];
$existing_cac_values = [];	

$existing_rpc_values = [];
$existing_rac_values = [];	

$existing_fnpc_values = [];
$existing_fnac_values = [];	
}

// Populate arrays with existing values, if any
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
		
		if($user_details_target[0]['dept_id']==1 || $user_details_target[0]['dept_id']==22 )
{
        $existing_ac_values[] = $row['article_count'];
        $existing_pc_values[] = $row['page_count'];
}
else
{
		$existing_ac_values[] =$row['article_count'];
		$existing_pc_values[] = $row['page_count'];	

		$existing_cpc_values[] =$row['cpc'];
		$existing_cac_values[] =$row['cac'];	

		$existing_rpc_values[] =$row['rpc'];
		$existing_rac_values[] =$row['rac'];

		$existing_fnpc_values[] =$row['fpc'];
		$existing_fnac_values[] =$row['fac'];
}
		
        $existing_cust_id[] = $row['cust_id'];
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
            <p class="articleheading ms-4">Edit Employee Target</p>
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

      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
            <div class="container mt-4 ">
				<div class="formcont1heading">Add Target</div>
				<br/>
				  <div class="col-md-12">
				<form action="" method="post" name="add_target_choose" id="add_target_choose" enctype="multipart/form-data">
				<div class="row" style="width:100%">
				   <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Choose Department <span style="color: red">*</span></label>
                  <select class="form-select department_drop" data-placeholder="Select Department" name="dept_id" id="dept_id" required>
                    <option value="">Select Department</option>
											<?php
											$sql_department = $auth->getAlldepartmentTarget();
											foreach($sql_department as $res_department)
											{
											?>
											<option value="<?php echo $res_department['dept_code'] ?>"style="font-weight:500;" <?php if ($user_details_target[0]['dept_id']==$res_department['dept_code']){?>selected<?php }?>><?php echo $res_department['dept_name'] ?> </option>
											<?php
											}
											?>
                </select>
                </div>
				  <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Choose Employee <span style="color: red">*</span></label>
                  <select class="form-select employee_drop" data-placeholder="Select Employee" name="user_id" id="user_id" required>
                    <option value="">Select Employee</option>
											<?php
											$sql_users = $auth->showUserlist();
											foreach($sql_users as $res_users)
											{
											?>
											<option value="<?php echo $res_users['id'] ?>"style="font-weight:500;" <?php if ($user_details_target[0]['user_id']==$res_users['id']){?>selected<?php }?>><?php echo $res_users['full_name'] ?> </option>
											<?php
											}
											?>
                </select>
                </div>
				</div>
				<div class="d-flex flex-column mt-3 gap-3">
				<div id="totalAveragePerDay">
				</div>
				<div id="totalAveragePerDayPages"></div>
				</div>
				
				<div id="" class="d-none flex-column mt-3 gap-3">
				<div> Total Average Per Day Count of Article : <span id="" style="color: green; font-weight: 600"></span></div>
				<div> Total Average Per Day Count of Pages &nbsp:  <span id="" style="color: green; font-weight: 600"></span></div>
				</div>
				
			</form>
			
          <form action="" method="post" name="add_target" id="add_target" enctype="multipart/form-data">
					<?php 
			if($user_details_target[0]['dept_id']==1 || $user_details_target[0]['dept_id']==22)
			{
			?>
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
															        <?php for ($i = 0; $i < count($existing_pc_values); $i++) : ?>

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
												
									      
										    	?>
										       <option <?php if ($existing_cust_id[$i]==$res_customers['id']){?>selected<?php }?> value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
													<?php
													
											}
											?>
                </select></td>
																<td  class=""><input type="text" class="form-control" name="ac[]" id="ac" value="<?php echo $existing_ac_values[$i];?>" oninput="calculateTotal()"></td>
																<td class=""><input type="text" class="form-control" name="pc[]" id="pc" value="<?php echo $existing_pc_values[$i];?>" oninput="calculateTotal()"></td>
																<td class="hidden-480"><p onclick="deleteRow(this)" class="delicon" style="cursor:pointer;"><img src="<?php echo $_COOKIE['set_url'];?>/assets/images/delicon.svg" style="cursor:pointer;"></td>
																</tr>
																
											<?php endfor; ?>



																	<tr>
																<td class="">Total</td>
																<td  class=""><input type="text" class="form-control" name="tac" id="tac" value="<?php echo $user_details_target[0]['total_ac'];?>"></td>
																<td class=""><input type="text" class="form-control" name="tpc" id="tpc" value="<?php echo $user_details_target[0]['total_pc'];?>"></td>
																<td class="hidden-480"></td>
																</tr>
																	<tr>
																<td class="">Per Day Average</td>
																<td  class=""><input type="text" class="form-control" name="aac" id="aac" value="<?php echo $user_details_target[0]['average_ac'];?>"></td>
																<td class=""><input type="text" class="form-control" name="apc" id="apc" value="<?php echo $user_details_target[0]['average_pc'];?>"></td>
																<td class="hidden-480"></td>
																</tr>
															</tbody>

														</table>
															<input type="hidden" name="action" value="update">
															<input type="hidden" name="uta_id" value="<?php echo $_REQUEST['uid'];?>">
															<input type="hidden" name="user_id" value="<?php echo $user_details_target[0]['id'];?>">
															<input type="hidden" name="dept_id" value="<?php echo $user_details_target[0]['dept_id'];?>">

													</div>
													</div>
			</div>
			
				<?php 
			}
			?>
			
				<?php 
			if($user_details_target[0]['dept_id']==10 || $user_details_target[0]['dept_id']==14)
			{
			?>
				   <div class="col-md-12">
						<div class="addplusicon" style="cursor:pointer;" id="" onclick="addRowother()">+</div>
					</div>
                      <div class="" id="widget-box-2" >
              <div class="widget-body" style="padding-top: 20px; margin: 20px">
													<div class="widget-main no-padding">
	<table class="table table-bordered" style="width: 100%" id="omyTable">
    <thead>
        <tr class="violetclr centercontent">
            <th rowspan="2">Customer</th>
            <th colspan="2">FP</th>
            <th colspan="2">Corrections</th>
            <th colspan="2">Revises</th>
            <th colspan="2">Finals</th>
            <th rowspan="2">Action</th>
        </tr>
        <tr class="violetclr centercontent">
            <th class="thcolor">Article Count</th>
            <th class="thcolor">Page Count</th>
            <th class="thcolor">Article Count</th>
            <th class="thcolor">Page Count</th>
            <th class="thcolor">Article Count</th>
            <th class="thcolor">Page Count</th>
            <th class="thcolor">Article Count</th>
            <th class="thcolor">Page Count</th>
        </tr>
    </thead>
    <tbody>
	
																        <?php for ($i = 0; $i < count($existing_pc_values); $i++) : ?>

        <tr>
            <td rowspan="1"><select class="form-select" data-placeholder="Select Customer" name="cust_id[]" id="cust_id" style="width:250px;" >
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
												
									      
										    	?>
										       <option <?php if ($existing_cust_id[$i]==$res_customers['id']){?>selected<?php }?> value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
													<?php
													
											}
											?>
                </select></td>
             <td class="thcolor"><input type="text" class="form-control" name="fac[]" id="fac" placeholder="Article Count" value="<?php echo $existing_ac_values[$i];?>" oninput="ocalculateTotal(0)"></td>
             <td class="thcolor"><input type="text" class="form-control" name="fpc[]" id="fpc" placeholder="Page Count" value="<?php echo $existing_pc_values[$i];?>" oninput="ocalculateTotal(1)"></td>
             <td class="thcolor"><input type="text" class="form-control" name="cac[]" id="cac" placeholder="Article Count" value="<?php echo $existing_cac_values[$i];?>" oninput="ocalculateTotal(2)"></td>
             <td class="thcolor"><input type="text" class="form-control" name="cpc[]" id="cpc" placeholder="Page Count" value="<?php echo $existing_cpc_values[$i];?>" oninput="ocalculateTotal(3)"></td>
             <td class="thcolor"><input type="text" class="form-control" name="rac[]" id="rac" placeholder="Article Count" value="<?php echo $existing_rac_values[$i];?>" oninput="ocalculateTotal(4)"></td>
             <td class="thcolor"><input type="text" class="form-control" name="rpc[]" id="rpc" placeholder="Page Count" value="<?php echo $existing_rpc_values[$i];?>" oninput="ocalculateTotal(5)"></td>
             <td class="thcolor"><input type="text" class="form-control" name="fnac[]" id="fnac" placeholder="Article Count" value="<?php echo $existing_fnac_values[$i];?>" oninput="ocalculateTotal(6)"></td>
             <td class="thcolor"><input type="text" class="form-control" name="fnpc[]" id="fnpc" placeholder="Page Count" value="<?php echo $existing_fnpc_values[$i];?>" oninput="ocalculateTotal(7)"></td>
			<td class="hidden-480"><p onclick="deleteRowother(this)" class="delicon" style="cursor:pointer;"><img src="<?php echo $_COOKIE['set_url'];?>/assets/images/delicon.svg" style="cursor:pointer;"></td>
        </tr>
		
													<?php endfor; ?>

        <!-- You can add more rows for other customers if needed -->
		
		
			<tr class="violetclr centercontent">
			  <th rowspan="1">Total</th>
             <td class="thcolor"><input type="text" class="form-control" name="tfac" id="tfac" placeholder="Total Article Count" value="<?php echo $user_details_target[0]['total_ac'];?>" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="tfpc" id="tfpc" placeholder="Total Page Count" value="<?php echo $user_details_target[0]['total_pc'];?>" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="tcac" id="tcac" placeholder="Total Article Count" value="<?php echo $user_details_target[0]['total_cac'];?>" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="tcpc" id="tcpc" placeholder="Total Page Count" value="<?php echo $user_details_target[0]['total_cpc'];?>" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="trac" id="trac" placeholder="Total Article Count" value="<?php echo $user_details_target[0]['total_rac'];?>" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="trpc" id="trpc" placeholder="Total Page Count" value="<?php echo $user_details_target[0]['total_rpc'];?>" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="tfnac" id="tfnac" placeholder="Total Article Count" value="<?php echo $user_details_target[0]['total_fac'];?>" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="tfnpc" id="tfnpc" placeholder="Total Page Count" value="<?php echo $user_details_target[0]['total_fpc'];?>" ></td>
			 <td class="hidden-480"></td>
        </tr>
			<tr class="violetclr centercontent">
			  <th rowspan="2">Per Day Average</th>
             <td class="thcolor"><input type="text" class="form-control" name="afac" id="afac" placeholder="Total Article Count" value="<?php echo $user_details_target[0]['average_ac'];?>" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="afpc" id="afpc" placeholder="Total Page Count" value="<?php echo $user_details_target[0]['average_pc'];?>" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="acac" id="acac" placeholder="Total Article Count" value="<?php echo $user_details_target[0]['average_cac'];?>" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="acpc" id="acpc" placeholder="Total Page Count" value="<?php echo $user_details_target[0]['average_cpc'];?>" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="arac" id="arac" placeholder="Total Article Count" value="<?php echo $user_details_target[0]['average_rac'];?>" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="arpc" id="arpc" placeholder="Total Page Count" value="<?php echo $user_details_target[0]['average_rpc'];?>" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="afnac" id="afnac" placeholder="Total Article Count" value="<?php echo $user_details_target[0]['average_fac'];?>" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="afnpc" id="afnpc" placeholder="Total Page Count" value="<?php echo $user_details_target[0]['average_fpc'];?>" ></td>
			 <td class="hidden-480"></td>
        </tr>
    </tbody>
</table>

															<input type="hidden" name="action" value="update">
															<input type="hidden" name="user_id" value="<?php echo $user_details_target[0]['id'];?>">
															<input type="hidden" name="dept_id" value="<?php echo $user_details_target[0]['dept_id'];?>">
															<input type="hidden" name="uta_id" value="<?php echo $_REQUEST['uid'];?>">

													</div>
													</div>
			</div>
			
				<?php 
			}
			?>
			<?php 
			if(isset($user_details_target[0]['dept_id']))
			{
			?>
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
			<?php 
			}
			?>
		
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
    newRow.insertCell(3).innerHTML = '<p onclick="deleteRow(this)" class="delicon" style="cursor:pointer;"><img src="<?php echo $_COOKIE['set_url'];?>/assets/images/delicon.svg" style="cursor:pointer;"></p>';
}


// Function to delete a row
function deleteRow(btn) {
    var row = btn.parentNode.parentNode;
    row.parentNode.removeChild(row);
    calculateTotal(); // Recalculate totals after deleting a row
}





  function ocalculateTotal() {
    var table = document.getElementById("omyTable");
    var rows = table.getElementsByTagName('tr');
    var columnSums = [0, 0, 0, 0, 0, 0, 0, 0]; // Array to hold column sums
    var columnCounts = [0, 0, 0, 0, 0, 0, 0, 0]; // Array to hold column counts
    var columnAverages = [0, 0, 0, 0, 0, 0, 0, 0]; // Array to hold column averages

    // Iterate through rows to calculate column sums and counts
    for (var i = 1; i < rows.length - 2; i++) {
        for (var j = 0; j < 8; j++) { // Loop through each column index
            var inputValue = parseInt(rows[i].querySelectorAll('input[name*="[]"]')[j]?.value) || 0;
            if (inputValue > 0) {
                columnSums[j] += inputValue;
                columnCounts[j]++;
            }
        }
    }

    // Calculate averages for each column
    for (var k = 0; k < 8; k++) {
        if (columnCounts[k] > 0) {
            columnAverages[k] = columnSums[k] / columnCounts[k];
        }
    }

    // Update Total row with calculated totals
    var totalRow = table.rows[rows.length - 2];
    for (var l = 0; l < 8; l++) {
        totalRow.querySelector('input[name="t' + ['fac', 'fpc', 'cac', 'cpc', 'rac', 'rpc', 'fnac', 'fnpc'][l] + '"]').value = columnSums[l];
    }

    // Update Per Day Average row with calculated averages
    var perDayAverageRow = table.rows[rows.length - 1];
    for (var m = 0; m < 8; m++) {
        perDayAverageRow.querySelector('input[name="a' + ['fac', 'fpc', 'cac', 'cpc', 'rac', 'rpc', 'fnac', 'fnpc'][m] + '"]').value = columnAverages[m].toFixed(2);
    }
	
	// Calculate total average per day count of articles
    var totalAveragePerDay = (columnAverages[0] + columnAverages[2] + columnAverages[4] + columnAverages[6])/4;

    // Update the content of the <div> element with the total average per day count of articles
    var totalAveragePerDayDiv = document.getElementById("totalAveragePerDay");
    totalAveragePerDayDiv.textContent = "Total Average Per Day Count of Articles: " + totalAveragePerDay.toFixed(2);
	
	// Calculate total average per day count of pages
    var totalAveragePerDayPages = (columnAverages[1] + columnAverages[3] + columnAverages[5] + columnAverages[7])/4;

    // Update the content of the <div> element with the total average per day count of pages
    var totalAveragePerDayPagesDiv = document.getElementById("totalAveragePerDayPages");
    totalAveragePerDayPagesDiv.textContent = "Total Average Per Day Count of Pages: " + totalAveragePerDayPages.toFixed(2);
}








    // Function to add a new row dynamically
    function addRowother() {
        var table = document.getElementById("omyTable").getElementsByTagName('tbody')[0];
        var newRow = table.insertRow(table.rows.length - 2); // Insert a new row before the "Total" row

        // Insert cells for Customer, FP, Corrections, Revises, Finals
        var cell1 = newRow.insertCell(0);
        var cell2 = newRow.insertCell(1);
        var cell3 = newRow.insertCell(2);
        var cell4 = newRow.insertCell(3);
        var cell5 = newRow.insertCell(4);
        var cell6 = newRow.insertCell(5);
        var cell7 = newRow.insertCell(6);
        var cell8 = newRow.insertCell(7);
        var cell9 = newRow.insertCell(8);

        // Populate cells with HTML content
        cell1.innerHTML = '<select class="form-select" data-placeholder="Select Customer" name="cust_id[]" style="width:250px;">' + document.getElementById('cust_id').innerHTML + '</select>';
        cell2.innerHTML = '<input type="text" class="form-control fac" name="fac[]" placeholder="Article Count" value="0" oninput="ocalculateTotal()">';
        cell3.innerHTML = '<input type="text" class="form-control fpc" name="fpc[]" placeholder="Page Count" value="0" oninput="ocalculateTotal()">';
        cell4.innerHTML = '<input type="text" class="form-control cac" name="cac[]" placeholder="Article Count" value="0" oninput="ocalculateTotal()">';
        cell5.innerHTML = '<input type="text" class="form-control cpc" name="cpc[]" placeholder="Page Count" value="0" oninput="ocalculateTotal()">';
        cell6.innerHTML = '<input type="text" class="form-control rac" name="rac[]" placeholder="Article Count" value="0" oninput="ocalculateTotal()">';
        cell7.innerHTML = '<input type="text" class="form-control rpc" name="rpc[]" placeholder="Page Count" value="0" oninput="ocalculateTotal()">';
        cell8.innerHTML = '<input type="text" class="form-control fac" name="fnac[]" placeholder="Article Count" value="0" oninput="ocalculateTotal()">';
        cell9.innerHTML = '<input type="text" class="form-control fac" name="fnpc[]" placeholder="Article Count" value="0" oninput="ocalculateTotal()">';
		  // Insert cell for Action (Delete) button
		newRow.insertCell(9).innerHTML = '<p onclick="deleteRowother(this)" class="delicon" style="cursor:pointer;"><img src="assets/images/delicon.svg" style="cursor:pointer;"></p>';
    }

    // Function to delete a row
    function deleteRowother(btn) {
        var row = btn.parentNode.parentNode;
        row.parentNode.removeChild(row);
        ocalculateTotal(); // Recalculate totals after deleting a row
    }






function populateSelect(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.full_name).val(item.id));
            });
        }
		
		
		
// Handle changes in the "countries" select element
            $("#dept_id").on("change", function() {
                var department_id = $(this).val();

                // Fetch connectedsource based on the selected connectedsource and populate the "name" select element
                $.ajax({
                    url: "get_users.php",
                    dataType: "json",
                    data: { dep_id: department_id },
                    success: function(data) {
                        populateSelect("user_id", data);

                        // Reset the "cities" select element when the country changes
                        //$("#user_id").empty();
                    }
                });
            });


        </script>
	
	<?php 
  if(isset($_POST['action']) && $_POST['action']=='update')
{


$delete_user_yarget=mysqli_query($db,"delete from user_target_assign where user_id='".$_REQUEST['user_id']."' ");
$delete_user_cu_target=mysqli_query($db,"delete from user_target_customer_assign where uta_id='".$_REQUEST['uta_id']."' ");
	
	
if($_REQUEST['dept_id']==1 || $_REQUEST['dept_id']==22 )
{
$column_query=",total_ac,total_pc,average_ac,average_pc";
$value_query=",'".$_REQUEST['tac']."','".$_REQUEST['tpc']."','".$_REQUEST['aac']."','".$_REQUEST['apc']."'";
}
else
{
$column_query=",total_ac,total_pc,average_ac,average_pc,total_cac,total_cpc,total_rac,total_rpc,total_fac,total_fpc,average_cac,average_cpc,average_rac,average_rpc,average_fac,average_fpc";
$value_query=",'".$_REQUEST['tfac']."','".$_REQUEST['tfpc']."','".$_REQUEST['afac']."','".$_REQUEST['afpc']."','".$_REQUEST['tcac']."','".$_REQUEST['tcpc']."','".$_REQUEST['trac']."','".$_REQUEST['trpc']."','".$_REQUEST['tfnac']."','".$_REQUEST['tfnpc']."','".$_REQUEST['acac']."','".$_REQUEST['acpc']."','".$_REQUEST['arac']."','".$_REQUEST['arpc']."','".$_REQUEST['afnac']."','".$_REQUEST['afnpc']."'";
}
$user_details=$auth->showUsersArticle($_REQUEST['user_id']);

//echo "INSERT INTO user_target_assign (user_id$column_query) values ('".$_REQUEST['user_id']."'$value_query) ";exit;
$user_target=mysqli_query($db,"INSERT INTO user_target_assign (user_id,dept_id$column_query) values ('".$_REQUEST['user_id']."','".$_REQUEST['dept_id']."'$value_query) ");
	
$uta_id=mysqli_insert_id($db);
// Retrieve the user_id and the corresponding pc[] and ac[] values from the form submission
$user_id = $_REQUEST['user_id']; // Assuming you get the user_id from your session or cookie

if($_REQUEST['dept_id']==1 || $_REQUEST['dept_id']==22 )
{
$pc_values = $_POST['pc'];
$ac_values = $_POST['ac'];
}
else
{
	
$pc_values = $_POST['fpc'];

$fpc_values = $_POST['fpc'];
$fac_values = $_POST['fac'];

$cpc_values = $_POST['cpc'];
$cac_values = $_POST['cac'];

$rpc_values = $_POST['rpc'];
$rac_values = $_POST['rac'];

$fnpc_values = $_POST['fnpc'];
$fnac_values = $_POST['fnac'];

}

$cust_ids = $_POST['cust_id'];

// Loop through pc[] and ac[] arrays and insert values into the table
for ($i = 0; $i <count($pc_values); $i++) {
   
    $cust_id = $cust_ids[$i];
    
	
if($_REQUEST['dept_id']==1 || $_REQUEST['dept_id']==22 )
{
$ac_value = $ac_values[$i];
$pc_value = $pc_values[$i];

$column_query_c=",article_count, page_count";
$value_query_c=",'".$ac_value."','".$pc_value."'";
}
else
{
	
$fpc_value = $fpc_values[$i];
$fac_value = $fac_values[$i];

$cpc_value = $cpc_values[$i];
$cac_value = $cac_values[$i];

$rpc_value = $rpc_values[$i];
$rac_value = $rac_values[$i];

$fnpc_value = $fnpc_values[$i];
$fnac_value = $fnac_values[$i];

$column_query_c=",article_count, page_count,cpc,cac,rpc,rac,fpc,fac";
$value_query_c=",'".$fac_value."','".$fpc_value."','".$cpc_value."','".$cac_value."','".$rpc_value."','".$rac_value."','".$fnpc_value."','".$fnac_value."'";
}
    // Construct and execute the SQL INSERT query
	
	//echo "INSERT INTO user_target_customer_assign (uta_id,user_id,cust_id$column_query_c) VALUES ('$uta_id','$user_id','$cust_id'$value_query_c)";
    $sql_uca= mysqli_query($db,"INSERT INTO user_target_customer_assign (uta_id,user_id,cust_id$column_query_c) VALUES ('$uta_id','$user_id','$cust_id'$value_query_c)");
    
}
//exit;

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
