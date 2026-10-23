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
				<div class="row" style="width:100%">
  <!-- Choose User -->
  <div class="col-12 col-sm-6 col-lg-4">
    <label class="mb-2">Choose Employee <span style="color: red">*</span></label>
    <select class="form-select user_drop" name="user_id" id="user_id" required>
      <option value="">Select Employee</option>
      <?php
      $sql_users = $auth->showUserlist();
      foreach($sql_users as $res_users) {
      ?>
        <option value="<?php echo $res_users['id'] ?>" <?php if ($_REQUEST['user_id'] == $res_users['id']) echo 'selected'; ?>>
          <?php echo $res_users['full_name'] ?>
        </option>
      <?php } ?>
    </select>
  </div>

  <!-- Choose Department -->
  <div class="col-12 col-sm-6 col-lg-4">
    <label class="mb-2">Choose Department <span style="color: red">*</span></label>
    <select class="form-select department_drop" name="dept_id" id="dept_id" required>
      <option value="">Select Department</option>
											<?php
											$sql_department = $auth->getAlldepartmentTarget();
											foreach($sql_department as $res_department)
											{
											?>
											<option value="<?php echo $res_department['dept_code'] ?>"style="font-weight:500;" <?php if ($_REQUEST['dept_id']==$res_department['dept_code']){?>selected<?php }?>><?php echo $res_department['dept_name'] ?> </option>
											<?php
											}
											?>
    </select>
  </div>
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
							<br/>

				
					<div class="col form_btn nxtbtn d-flex justify-content-center mt-4">
                        <button type="submit" class="btn-next" >Next</button>
                    </div>
			</form>
			<div class="mt-3">
          <form action="" method="post" name="add_target" id="add_target" enctype="multipart/form-data">
					<?php 
			if($_REQUEST['dept_id']==1 || $_REQUEST['dept_id']==22)
			{
			?>
				   <div class="col-md-12">
						<div class="addplusicon add_cauthor" style="cursor:pointer;" id="add_cauthor" onclick="addRow()">+</div>
					</div>
                      <div class="" id="widget-box-2" >
              <div class="widget-body" style="padding-top: 20px; margin: 20px; overflow-x: auto">
													<div class="widget-main no-padding">
														<table class="table table-hover" id="myTable">
															<thead class="thin-border-bottom">
																<tr>
																	<th>Customer</th>
																	<th>CE Article Count</th>
																	<th>CE Page Count</th>
																	<th>Pre Clean Article Count</th>
																	<th>Pre Clean Page Count</th>
																	<th rowspan="2" class="hidden-480">Action</th>
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
																<td  class=""><input type="text" class="form-control" name="pac[]" id="pac" value="0" oninput="calculateTotal()"></td>
																<td class=""><input type="text" class="form-control" name="ppc[]" id="ppc" value="0" oninput="calculateTotal()"></td>
																<td class="hidden-480"></td>
																</tr>


																	<tr>
																<td class="">Total</td>
																<td  class=""><input type="text" class="form-control" name="tac" id="tac" value="0"></td>
																<td class=""><input type="text" class="form-control" name="tpc" id="tpc" value="0"></td>
																<td  class=""><input type="text" class="form-control" name="tpac" id="tpac" value="0"></td>
																<td class=""><input type="text" class="form-control" name="tppc" id="tppc" value="0"></td>
																<td class="hidden-480"></td>
																</tr>
																	<tr>
																<td class="">Per Day Average</td>
																<td  class=""><input type="text" class="form-control" name="aac" id="aac" value="0.00"></td>
																<td class=""><input type="text" class="form-control" name="apc" id="apc" value="0.00"></td>
																<td  class=""><input type="text" class="form-control" name="apac" id="apac" value="0.00"></td>
																<td class=""><input type="text" class="form-control" name="appc" id="appc" value="0.00"></td>
																<td class="hidden-480"></td>
																</tr>
															</tbody>

														</table>
															<input type="hidden" name="action" value="update">
															<input type="hidden" name="user_id" value="<?php echo $_REQUEST['user_id'];?>">
															<input type="hidden" name="dept_id" value="<?php echo $_REQUEST['dept_id'];?>">													</div>
													</div>
			</div>
			
				<?php 
			}
			elseif(isset($_REQUEST['dept_id']) && $_REQUEST['dept_id']!=1 && $_REQUEST['dept_id']!=22)
			{
			?>
				   <div class="col-md-12">
						<div class="addplusicon" style="cursor:pointer;" id="" onclick="addRowother()">+</div>
					</div>
                      <div class="" id="widget-box-2" >
              <div class="widget-body" style="padding-top: 20px; margin: 20px;overflow-x: auto">
													<div class="widget-main no-padding">
	<table class="table table-bordered" style="width: 100%" id="omyTable">
    <thead>
        <tr class="violetclr centercontent">
            <th rowspan="2">Customer</th>
            <th colspan="2">FP</th>
            <th colspan="2">Corrections</th>
            <th colspan="2">Revises</th>
            <th colspan="2">Finals</th>
            <th colspan="2">Issue</th>
            <th colspan="2">JSTAGE</th>
            <th colspan="2">PMC</th>
            <th colspan="2">Others</th>
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
             <td class="thcolor"><input type="text" class="form-control" name="fac[]" id="fac" placeholder="Article Count" value="0" oninput="ocalculateTotal()" readonly></td>
             <td class="thcolor"><input type="text" class="form-control" name="fpc[]" id="fpc" placeholder="Page Count" value="0" oninput="ocalculateTotal()"></td>
			
             <td class="thcolor"><input type="text" class="form-control" name="cac[]" id="cac" placeholder="Article Count" value="0" oninput="ocalculateTotal()" readonly></td>
             <td class="thcolor"><input type="text" class="form-control" name="cpc[]" id="cpc" placeholder="Page Count" value="0" oninput="ocalculateTotal()"></td>
             <td class="thcolor"><input type="text" class="form-control" name="rac[]" id="rac" placeholder="Article Count" value="0" oninput="ocalculateTotal()" readonly></td>
             <td class="thcolor"><input type="text" class="form-control" name="rpc[]" id="rpc" placeholder="Page Count" value="0" oninput="ocalculateTotal()"></td>
             <td class="thcolor"><input type="text" class="form-control" name="fnac[]" id="fnac" placeholder="Article Count" value="0" oninput="ocalculateTotal()" readonly></td>
             <td class="thcolor"><input type="text" class="form-control" name="fnpc[]" id="fnpc" placeholder="Page Count" value="0" oninput="ocalculateTotal()"></td>
			 <td class="thcolor"><input type="text" class="form-control" name="iac[]" id="iac" placeholder="Article Count" value="0" oninput="ocalculateTotal()" readonly></td>
             <td class="thcolor"><input type="text" class="form-control" name="ipc[]" id="ipc" placeholder="Page Count" value="0" oninput="ocalculateTotal()"></td>
			 <td class="thcolor"><input type="text" class="form-control" name="jac[]" id="jac" placeholder="Article Count" value="0" oninput="ocalculateTotal()" readonly></td>
             <td class="thcolor"><input type="text" class="form-control" name="jpc[]" id="jpc" placeholder="Page Count" value="0" oninput="ocalculateTotal()"></td>
			 <td class="thcolor"><input type="text" class="form-control" name="mac[]" id="mac" placeholder="Article Count" value="0" oninput="ocalculateTotal()" readonly></td>
             <td class="thcolor"><input type="text" class="form-control" name="mpc[]" id="mpc" placeholder="Page Count" value="0" oninput="ocalculateTotal()"></td>
			 <td class="thcolor"><input type="text" class="form-control" name="pfac[]" id="pfac" placeholder="Article Count" value="0" oninput="ocalculateTotal()" readonly></td>
             <td class="thcolor"><input type="text" class="form-control" name="pfpc[]" id="pfpc" placeholder="Page Count" value="0" oninput="ocalculateTotal()"></td>        </tr>
        <!-- You can add more rows for other customers if needed -->
		
		
			<tr class="violetclr centercontent">
			  <th rowspan="1">Total</th>
             <td class="thcolor"><input type="text" class="form-control" name="tfac" id="tfac" placeholder="Total Article Count" value="0" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="tfpc" id="tfpc" placeholder="Total Page Count" value="0" ></td>
			 <td class="thcolor"><input type="text" class="form-control" name="tcac" id="tcac" placeholder="Total Article Count" value="0" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="tcpc" id="tcpc" placeholder="Total Page Count" value="0" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="trac" id="trac" placeholder="Total Article Count" value="0" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="trpc" id="trpc" placeholder="Total Page Count" value="0" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="tfnac" id="tfnac" placeholder="Total Article Count" value="0" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="tfnpc" id="tfnpc" placeholder="Total Page Count" value="0" ></td>
			 <td class="thcolor"><input type="text" class="form-control" name="tiac" id="tiac" placeholder="Total Article Count" value="0" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="tipc" id="tipc" placeholder="Total Page Count" value="0" ></td>
			 <td class="thcolor"><input type="text" class="form-control" name="tjac" id="tjac" placeholder="Total Article Count" value="0" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="tjpc" id="tjpc" placeholder="Total Page Count" value="0" ></td>
			 <td class="thcolor"><input type="text" class="form-control" name="tmac" id="tmac" placeholder="Total Article Count" value="0" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="tmpc" id="tmpc" placeholder="Total Page Count" value="0" ></td>
			 <td class="thcolor"><input type="text" class="form-control" name="tpfac" id="tpfac" placeholder="Total Article Count" value="0" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="tpfpc" id="tpfpc" placeholder="Total Page Count" value="0" ></td>     
        </tr>
			<tr class="violetclr centercontent">
			  <th rowspan="2">Per Day Average</th>
             <td class="thcolor"><input type="text" class="form-control" name="afac" id="afac" placeholder="Total Article Count" value="0" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="afpc" id="afpc" placeholder="Total Page Count" value="0" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="acac" id="acac" placeholder="Total Article Count" value="0" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="acpc" id="acpc" placeholder="Total Page Count" value="0" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="arac" id="arac" placeholder="Total Article Count" value="0" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="arpc" id="arpc" placeholder="Total Page Count" value="0" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="afnac" id="afnac" placeholder="Total Article Count" value="0" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="afnpc" id="afnpc" placeholder="Total Page Count" value="0" ></td>
			 <td class="thcolor"><input type="text" class="form-control" name="aiac" id="aiac" placeholder="Total Article Count" value="0" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="aipc" id="aipc" placeholder="Total Page Count" value="0" ></td>
			 <td class="thcolor"><input type="text" class="form-control" name="ajac" id="ajac" placeholder="Total Article Count" value="0" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="ajpc" id="ajpc" placeholder="Total Page Count" value="0" ></td>
			 <td class="thcolor"><input type="text" class="form-control" name="amac" id="amac" placeholder="Total Article Count" value="0" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="ampc" id="ampc" placeholder="Total Page Count" value="0" ></td>
			 <td class="thcolor"><input type="text" class="form-control" name="apfac" id="apfac" placeholder="Total Article Count" value="0" ></td>
             <td class="thcolor"><input type="text" class="form-control" name="apfpc" id="apfpc" placeholder="Total Page Count" value="0" ></td>  
 			 </tr>
    </tbody>
</table>

															<input type="hidden" name="action" value="update">
															<input type="hidden" name="user_id" value="<?php echo $_REQUEST['user_id'];?>">
															<input type="hidden" name="dept_id" value="<?php echo $_REQUEST['dept_id'];?>">

													</div>
													</div>
			</div>
			
				<?php 
			}
			?>
			<?php 
			if(isset($_REQUEST['dept_id']))
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
    var totalPAC = 0;
    var totalPPC = 0;

    // Iterate through rows to calculate sum of AC, PC, PAC, and PPC values
    for (var i = 1; i < rows.length - 2; i++) {
        var acInput = Number(rows[i].querySelector('input[name="ac[]"]').value) || 0;
        var pcInput = Number(rows[i].querySelector('input[name="pc[]"]').value) || 0;
        var pacInput = Number(rows[i].querySelector('input[name="pac[]"]').value) || 0;
        var ppcInput = Number(rows[i].querySelector('input[name="ppc[]"]').value) || 0;
        totalAC += acInput;
        totalPC += pcInput;
        totalPAC += pacInput;
        totalPPC += ppcInput;
    }

    // Update Total row with calculated totals
    var totalRow = table.rows[rows.length - 2];
    totalRow.querySelector('input[name="tac"]').value = totalAC;
    totalRow.querySelector('input[name="tpc"]').value = totalPC;
    totalRow.querySelector('input[name="tpac"]').value = totalPAC;
    totalRow.querySelector('input[name="tppc"]').value = totalPPC;

    // Calculate Per Day Average
    var perDayAverageRow = table.rows[rows.length - 1];
    var validRowCount = rows.length - 3; // Exclude header and footer rows
    var averageAC = totalAC / validRowCount;
    var averagePC = totalPC / validRowCount;
    var averagePAC = totalPAC / validRowCount;
    var averagePPC = totalPPC / validRowCount;

    perDayAverageRow.querySelector('input[name="aac"]').value = averageAC.toFixed(2);
    perDayAverageRow.querySelector('input[name="apc"]').value = averagePC.toFixed(2);
    perDayAverageRow.querySelector('input[name="apac"]').value = averagePAC.toFixed(2);
    perDayAverageRow.querySelector('input[name="appc"]').value = averagePPC.toFixed(2);
}

// Function to add a new row
function addRow() {
    var table = document.getElementById("myTable").getElementsByTagName('tbody')[0];
    var newRow = table.insertRow(table.rows.length - 2); // Insert a new row before the "Total" row

    // Insert cells for Customer, AC, PC, PAC, PPC
    for (var i = 0; i < 5; i++) {
        var newCell = newRow.insertCell(i);
        if (i === 0) {
            // For Customer cell, copy content from the first row
            newCell.innerHTML = table.rows[0].cells[i].innerHTML;
        } else {
            // For AC, PC, PAC, and PPC cells, create input elements
            var input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control';
            if (i === 1) input.name = 'ac[]';
            else if (i === 2) input.name = 'pc[]';
            else if (i === 3) input.name = 'pac[]';
            else if (i === 4) input.name = 'ppc[]';
            input.value = '0';
            input.oninput = calculateTotal;
            newCell.appendChild(input);
        }
    }

    // Insert cell for Action (Delete) button
    var deleteCell = newRow.insertCell(5);
    deleteCell.innerHTML = '<p onclick="deleteRow(this)" class="delicon" style="cursor:pointer;"><img src="assets/images/delicon.svg" style="cursor:pointer;"></p>';
}

// Function to delete a row
function deleteRow(element) {
    var row = element.closest('tr');
    row.parentNode.removeChild(row);
    calculateTotal(); // Recalculate totals after deleting a row
}


// Function to delete a row
function deleteRow(btn) {
    var row = btn.parentNode.parentNode;
    row.parentNode.removeChild(row);
    calculateTotal(); // Recalculate totals after deleting a row
}




    // Function to calculate total AC and PC, Corrections, Revises, Finals, and averages
function ocalculateTotal() {
    var table = document.getElementById("omyTable");
    var rows = table.getElementsByTagName('tr');
    var columnNames = ['fac', 'fpc', 'pfac', 'pfpc', 'cac', 'cpc', 'rac', 'rpc', 'fnac', 'fnpc', 'iac', 'ipc', 'jac', 'jpc', 'mac', 'mpc'];
    var columnSums = new Array(16).fill(0);
    var columnCounts = new Array(16).fill(0);

    // Iterate through rows (excluding header, total, and average rows)
    for (var i = 1; i < rows.length - 2; i++) {
        for (var j = 0; j < columnNames.length; j++) {
            var input = rows[i].querySelector(`input[name="${columnNames[j]}[]"]`);
            var value = parseFloat(input?.value) || 0;
            columnSums[j] += value;

            // Optional: only count averages if value > 0
            if (value > 0) columnCounts[j]++;
        }
    }

    // Update totals
    var totalRow = rows[rows.length - 2];
    for (var j = 0; j < columnNames.length; j++) {
        var totalInput = totalRow.querySelector(`input[name="t${columnNames[j]}"]`);
        if (totalInput) totalInput.value = columnSums[j];
    }

    // Update averages
    var avgRow = rows[rows.length - 1];
    for (var j = 0; j < columnNames.length; j++) {
        var avgInput = avgRow.querySelector(`input[name="a${columnNames[j]}"]`);
        var avgValue = columnCounts[j] > 0 ? (columnSums[j] / columnCounts[j]) : 0;
        if (avgInput) avgInput.value = avgValue.toFixed(2);
    }

    // Total Average Per Day Count of Articles (based on 4 article columns)
    var articleAvg = (columnSums[0] + columnSums[2] + columnSums[4] + columnSums[6]) / 4;
    document.getElementById("totalAveragePerDay").textContent =
        "Total Average Per Day Count of Articles: " + articleAvg.toFixed(2);

    // Total Average Per Day Count of Pages (based on 4 page columns)
    var pageAvg = (columnSums[1] + columnSums[3] + columnSums[5] + columnSums[7]) / 4;
    document.getElementById("totalAveragePerDayPages").textContent =
        "Total Average Per Day Count of Pages: " + pageAvg.toFixed(2);
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
		var cell10 = newRow.insertCell(9);
        var cell11 = newRow.insertCell(10);
		var cell12 = newRow.insertCell(11);
        var cell13 = newRow.insertCell(12);
        var cell14 = newRow.insertCell(13);
        var cell15 = newRow.insertCell(14);
        var cell16 = newRow.insertCell(15);
        var cell17 = newRow.insertCell(16);        // Populate cells with HTML content
        cell1.innerHTML = '<select class="form-select" data-placeholder="Select Customer" name="cust_id[]" style="width:250px;">' + document.getElementById('cust_id').innerHTML + '</select>';
        cell2.innerHTML = '<input type="text" class="form-control fac" name="fac[]" placeholder="Article Count" value="0" oninput="ocalculateTotal()" readonly>';
        cell3.innerHTML = '<input type="text" class="form-control fpc" name="fpc[]" placeholder="Page Count" value="0" oninput="ocalculateTotal()">';
		cell4.innerHTML = '<input type="text" class="form-control cac" name="cac[]" placeholder="Article Count" value="0" oninput="ocalculateTotal()" readonly>';
        cell5.innerHTML = '<input type="text" class="form-control cpc" name="cpc[]" placeholder="Page Count" value="0" oninput="ocalculateTotal()">';
        cell6.innerHTML = '<input type="text" class="form-control rac" name="rac[]" placeholder="Article Count" value="0" oninput="ocalculateTotal()" readonly>';
        cell7.innerHTML = '<input type="text" class="form-control rpc" name="rpc[]" placeholder="Page Count" value="0" oninput="ocalculateTotal()" >';
        cell8.innerHTML = '<input type="text" class="form-control fnac" name="fnac[]" placeholder="Article Count" value="0" oninput="ocalculateTotal()" readonly>';
        cell9.innerHTML = '<input type="text" class="form-control fnpc" name="fnpc[]" placeholder="Page Count" value="0" oninput="ocalculateTotal()">';
		cell10.innerHTML = '<input type="text" class="form-control iac" name="iac[]" placeholder="Article Count" value="0" oninput="ocalculateTotal()" readonly>';
        cell11.innerHTML = '<input type="text" class="form-control ipc" name="ipc[]" placeholder="Page Count" value="0" oninput="ocalculateTotal()">';
		cell12.innerHTML = '<input type="text" class="form-control jac" name="jac[]" placeholder="Article Count" value="0" oninput="ocalculateTotal()" readonly>';
        cell13.innerHTML = '<input type="text" class="form-control jpc" name="jpc[]" placeholder="Page Count" value="0" oninput="ocalculateTotal()">';
		cell14.innerHTML = '<input type="text" class="form-control mac" name="mac[]" placeholder="Article Count" value="0" oninput="ocalculateTotal()" readonly>';
        cell15.innerHTML = '<input type="text" class="form-control mpc" name="mpc[]" placeholder="Page Count" value="0" oninput="ocalculateTotal()">';
		cell16.innerHTML = '<input type="text" class="form-control pfac" name="pfac[]" placeholder="Article Count" value="0" oninput="ocalculateTotal()" readonly>';
        cell17.innerHTML = '<input type="text" class="form-control pfpc" name="pfpc[]" placeholder="Page Count" value="0" oninput="ocalculateTotal()">';    
		
		  // Insert cell for Action (Delete) button
		newRow.insertCell(17).innerHTML = '<p onclick="deleteRowother(this)" class="delicon" style="cursor:pointer;"><img src="assets/images/delicon.svg" style="cursor:pointer;"></p>';
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
		
		
		
/* // Handle changes in the "countries" select element
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
            }); */


        </script>
		
		<script>
$(document).ready(function() {
  $('#user_id').on('change', function() {
    var user_id = $(this).val();

    if (user_id != '') {
      $.ajax({
        url: 'get_user_departments.php',
        type: 'POST',
        data: { user_id: user_id },
        success: function(response) {
          $('#dept_id').html(response);
        }
      });
    } else {
      $('#dept_id').html('<option value="">Select Department</option>');
    }
  });
});
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

if($_REQUEST['dept_id']==1 || $_REQUEST['dept_id']==22 )
{
$column_query=",total_ac,total_pc,total_pac,total_ppc,average_ac,average_pc,average_pac,average_ppc";
$value_query=",'".$_REQUEST['tac']."','".$_REQUEST['tpc']."','".$_REQUEST['tpac']."','".$_REQUEST['tppc']."','".$_REQUEST['aac']."','".$_REQUEST['apc']."','".$_REQUEST['apac']."','".$_REQUEST['appc']."'";
}
else
{
$column_query=",total_ac,total_pc,average_ac,average_pc,total_cac,total_cpc,total_rac,total_rpc,total_fac,total_fpc,total_iac,total_ipc,total_oac,total_opc,average_cac,average_cpc,average_rac,average_rpc,average_fac,average_fpc,average_iac,average_ipc,average_oac,average_opc";
$value_query=",'".$_REQUEST['tfac']."','".$_REQUEST['tfpc']."','".$_REQUEST['afac']."','".$_REQUEST['afpc']."','".$_REQUEST['tcac']."','".$_REQUEST['tcpc']."','".$_REQUEST['trac']."','".$_REQUEST['trpc']."','".$_REQUEST['tfnac']."','".$_REQUEST['tfnpc']."','".$_REQUEST['tiac']."','".$_REQUEST['tipc']."','".$_REQUEST['tpfac']."','".$_REQUEST['tpfpc']."','".$_REQUEST['acac']."','".$_REQUEST['acpc']."','".$_REQUEST['arac']."','".$_REQUEST['arpc']."','".$_REQUEST['afnac']."','".$_REQUEST['afnpc']."','".$_REQUEST['aiac']."','".$_REQUEST['aipc']."','".$_REQUEST['apfac']."','".$_REQUEST['apfpc']."'";
}
$js_ok = ($__q = mysqli_query($db, "SHOW COLUMNS FROM user_target_customer_assign LIKE 'jpc'")) && mysqli_num_rows($__q) > 0
      && ($__q = mysqli_query($db, "SHOW COLUMNS FROM user_target_assign LIKE 'total_jpc'")) && mysqli_num_rows($__q) > 0;   /* JSTAGE columns exist (migration run) - otherwise save as before */
if ($js_ok && !($_REQUEST['dept_id']==1 || $_REQUEST['dept_id']==22)) {
    $column_query .= ",total_jac,total_jpc,average_jac,average_jpc";
    $value_query  .= ",'".(float)($_REQUEST['tjac'] ?? 0)."','".(float)($_REQUEST['tjpc'] ?? 0)."','".(float)($_REQUEST['ajac'] ?? 0)."','".(float)($_REQUEST['ajpc'] ?? 0)."'";
}
$pm_ok = ($__q = mysqli_query($db, "SHOW COLUMNS FROM user_target_customer_assign LIKE 'mpc'")) && mysqli_num_rows($__q) > 0
      && ($__q = mysqli_query($db, "SHOW COLUMNS FROM user_target_assign LIKE 'total_mpc'")) && mysqli_num_rows($__q) > 0;   /* PMC columns exist (pmc migration run) */
if ($pm_ok && !($_REQUEST['dept_id']==1 || $_REQUEST['dept_id']==22)) {
    $column_query .= ",total_mac,total_mpc,average_mac,average_mpc";
    $value_query  .= ",'".(float)($_REQUEST['tmac'] ?? 0)."','".(float)($_REQUEST['tmpc'] ?? 0)."','".(float)($_REQUEST['amac'] ?? 0)."','".(float)($_REQUEST['ampc'] ?? 0)."'";
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

$ppc_values = $_POST['ppc'];
$pac_values = $_POST['pac'];


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

$ipc_values = $_POST['ipc'];
$iac_values = $_POST['iac'];

$opc_values = $_POST['pfpc'];
$oac_values = $_POST['pfac'];
}

$cust_ids = $_POST['cust_id'];

// Loop through pc[] and ac[] arrays and insert values into the table
for ($i = 0; $i <count($pc_values); $i++) {
   
    $cust_id = $cust_ids[$i];
    
	
if($_REQUEST['dept_id']==1 || $_REQUEST['dept_id']==22 )
{
$ac_value = $ac_values[$i];
$pc_value = $pc_values[$i];

$pac_value = $pac_values[$i];
$ppc_value = $ppc_values[$i];



$column_query_c=",article_count, page_count,particle_count, ppage_count";
$value_query_c=",'".$ac_value."','".$pc_value."','".$pac_value."','".$ppc_value."'";
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

$ipc_value = $ipc_values[$i];
$iac_value = $iac_values[$i];

$opc_value = $opc_values[$i];
$oac_value = $oac_values[$i];

$column_query_c=",article_count, page_count,cpc,cac,rpc,rac,fpc,fac,ipc,iac,opc,oac";
$value_query_c=",'".$fac_value."','".$fpc_value."','".$cpc_value."','".$cac_value."','".$rpc_value."','".$rac_value."','".$fnpc_value."','".$fnac_value."','".$ipc_value."','".$iac_value."','".$opc_value."','".$oac_value."'";
}
if ($js_ok && !($_REQUEST['dept_id']==1 || $_REQUEST['dept_id']==22)) {
    $column_query_c .= ",jac,jpc";
    $value_query_c  .= ",'".(int)($_POST['jac'][$i] ?? 0)."','".(int)($_POST['jpc'][$i] ?? 0)."'";
}
if ($pm_ok && !($_REQUEST['dept_id']==1 || $_REQUEST['dept_id']==22)) {
    $column_query_c .= ",mac,mpc";
    $value_query_c  .= ",'".(int)($_POST['mac'][$i] ?? 0)."','".(int)($_POST['mpc'][$i] ?? 0)."'";
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
