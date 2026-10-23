<?php include "includes/includes-sw2.php";
require_once __DIR__.'/includes/form_ui.php';
include_once "includes/change_log_mail.php";?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php fu_head('add_target_choose'); fu_card_css(); ?>	


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

.highlight-incomplete {
background-color: #fff3cd !important; /* light yellow */
}
.highlight-preferred {
background-color: #d1ecf1 !important; /* light blue */
}
.highlight-preferred-incomplete {
background-color: #e2d6f7 !important; /* light purple */
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

      <?php fu_hero(fu_ui_basic('Add Employee Target', 'Add Employee Target &rsaquo; Add')); ?>
      <style>
  #add_target_choose .form_btn.nxtbtn,#edit_target_choose .form_btn.nxtbtn{justify-content:flex-start !important;margin-top:8px !important;}
  .fu-card .container.mt-4{max-width:none;padding:0;margin-top:0 !important;} .fu-card .row{margin-left:0;margin-right:0;}
  .fu-card .formcont1heading{display:none;}
  .fu-card #totalAveragePerDay,.fu-card #totalAveragePerDayPages{color:#14304a;font-weight:600;font-size:13.5px;padding:0;margin:0;border:0;background:none;}
  .fu-card .d-flex.flex-column.mt-3.gap-3{margin-top:10px !important;gap:4px !important;}
  .fu-card .col-md-12:has(> .addplusicon){display:flex;justify-content:flex-end;}
  .fu-card .addplusicon{width:auto !important;height:40px !important;margin:16px 0 0 auto !important;padding:0 18px;border-radius:10px !important;background:linear-gradient(120deg,#1b6ca8,#14304a) !important;font-size:0 !important;gap:8px;box-shadow:0 6px 16px rgba(27,108,168,.3);transition:transform .15s;}
  .fu-card .addplusicon:before{content:'+ Add customer row';font-size:14px;font-weight:700;color:#fff;}
  .fu-card .addplusicon:hover{transform:translateY(-2px);}
  .fu-card .widget-body{margin:14px 0 6px !important;padding:0 !important;border:1px solid #e4e9ef;border-radius:14px;overflow-x:auto;background:#fff;}
  .fu-card #myTable,.fu-card #omyTable{width:100% !important;margin:0 !important;border-collapse:separate !important;border-spacing:0;font-size:13.5px;}
  .fu-card #myTable th,.fu-card #omyTable th{background:#eef3f8 !important;color:#14304a !important;font-weight:700;text-align:center;padding:11px 10px !important;border:0 !important;border-bottom:1px solid #dbe3ec !important;border-right:1px solid #e4e9ef !important;white-space:nowrap;}
  .fu-card #myTable th.thcolor,.fu-card #omyTable th.thcolor{background:#f6f9fc !important;color:#6b7785 !important;font-size:11.5px;font-weight:600;text-transform:uppercase;letter-spacing:.3px;}
  .fu-card #myTable td,.fu-card #omyTable td{background:#fff !important;color:#14304a !important;padding:8px 8px !important;border:0 !important;border-bottom:1px solid #eef1f5 !important;border-right:1px solid #f1f4f8 !important;vertical-align:middle;}
  .fu-card #myTable tbody tr:hover td,.fu-card #omyTable tbody tr:hover td{background:#f7fafd !important;}
  .fu-card #omyTable tfoot th,.fu-card #omyTable tbody th{background:#f6f9fc !important;text-align:left;}
  .fu-card #myTable input.form-control,.fu-card #omyTable input.form-control{height:38px !important;min-width:64px;text-align:center;border:1px solid #cfd8e3 !important;border-radius:9px !important;background:#fbfcfe !important;color:#14304a !important;padding:0 8px !important;}
  .fu-card #myTable input[readonly],.fu-card #omyTable input[readonly]{background:#f3f6fa !important;color:#5b6b7b !important;border-style:dashed !important;}
  .fu-card .delicon{width:34px !important;height:34px !important;border-radius:10px !important;background:#fdeeee !important;color:#d64545 !important;border:1px solid #f7d4d4;cursor:pointer;transition:all .15s;}
  .fu-card .delicon:hover{background:#d64545 !important;color:#fff !important;} .fu-card .delicon img{width:15px;filter:brightness(0) saturate(100%) invert(36%) sepia(63%) saturate(2200%) hue-rotate(340deg) brightness(92%);transition:filter .15s;} .fu-card .delicon:hover img{filter:none;}
  .fu-card .meta_nxtbtn{margin:18px 0 0 !important;padding:0 !important;max-width:none;} .fu-card .meta_nxtbtn .row{display:flex;justify-content:flex-end;gap:12px;} .fu-card .meta_nxtbtn .col{flex:0 0 auto;width:auto;padding:0;}
  .fu-card .highlight-incomplete td,.fu-card td.highlight-incomplete{background:#fff8e1 !important;} .fu-card .highlight-preferred{background:#e6f4f8 !important;}
</style>
<div class="fu-wrap"><div class="fu-card">
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
        <option value="<?php echo $res_users['id'] ?>"  data-prefer="<?php echo $res_users['preferred_cust_id']; ?>" <?php if ($_REQUEST['user_id'] == $res_users['id']) echo 'selected'; ?>>
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
											<option value="<?php echo $res_department['dept_code'] ?>"style="font-weight:500;"  <?php if ($_REQUEST['dept_id']==$res_department['dept_code']){?>selected<?php }?>><?php echo $res_department['dept_name'] ?> </option>
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
																<td  class=""><input type="text" class="form-control" name="tac" id="tac" value="0" readonly ></td>
																<td class=""><input type="text" class="form-control" name="tpc" id="tpc" value="0" readonly ></td>
																<td  class=""><input type="text" class="form-control" name="tpac" id="tpac" value="0" readonly ></td>
																<td class=""><input type="text" class="form-control" name="tppc" id="tppc" value="0" readonly ></td>
																<td class="hidden-480"></td>
																</tr>
																	<tr>
																<td class="">Per Day Average</td>
																<td  class=""><input type="text" class="form-control" name="aac" id="aac" value="0.00" readonly></td>
																<td class=""><input type="text" class="form-control" name="apc" id="apc" value="0.00" readonly></td>
																<td  class=""><input type="text" class="form-control" name="apac" id="apac" value="0.00" readonly></td>
																<td class=""><input type="text" class="form-control" name="appc" id="appc" value="0.00" readonly></td>
																<td class="hidden-480"></td>
																</tr>
																	<tr>
																		<td>Preferred Cust Avg</td>
																		<td><input type="text" class="form-control" name="paac" id="paac" value="0.00" readonly></td>
																		<td><input type="text" class="form-control" name="papc" id="papc" value="0.00" readonly></td>
																		<td><input type="text" class="form-control" name="papac" id="papac" value="0.00" readonly></td>
																		<td><input type="text" class="form-control" name="pappc" id="pappc" value="0.00" readonly></td>
																		<td></td>
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
            <th>Customer</th>
            <th colspan="1">FP</th>
            <th colspan="1">Corrections</th>
            <th colspan="1">Revises</th>
            <th colspan="1">Finals</th>
            <th colspan="1">Issue</th>
            <th colspan="1">JSTAGE</th>
            <th colspan="1">PMC</th>
            <th colspan="1">Others</th>
            <th>Action</th>
        </tr>
        <tr class="violetclr centercontent">
            <th class="thcolor">Page Count</th>
            <th class="thcolor">Page Count</th>
            <th class="thcolor">Page Count</th>
            <th class="thcolor">Page Count</th>
            <th class="thcolor">Page Count</th>
            <th class="thcolor">Page Count</th>
            <th class="thcolor">Page Count</th>
            <th class="thcolor">Page Count</th>
            <th class="thcolor"></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><select class="form-select" data-placeholder="Select Customer" name="cust_id[]" id="cust_id" style="width:250px;" >
                <option value="all">Select All</option>
                <?php
                if($_COOKIE['user_level']=='3') {
                    $sql_customers = $auth->showCustomerlistbyuser($_COOKIE['cust_id'],$_COOKIE['ocust_id']);
                } else {
                    $sql_customers = $auth->showCustomerlist();
                }
                foreach($sql_customers as $res_customers) {
                    if(isset($_REQUEST['cust_id'])) {
                ?>
                    <option <?php if (in_array($res_customers['id'], $ex_cid)){?>selected<?php }?> value="<?php echo $res_customers['id'] ?>" style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
                <?php
                    } else {
                ?>
                    <option value="<?php echo $res_customers['id'] ?>" style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
                <?php
                    }
                }
                ?>
            </select></td>
            <td class="thcolor"><input type="text" class="form-control" name="fpc[]" id="fpc" placeholder="Page Count" value="0" oninput="ocalculateTotal()"></td>
            <td class="thcolor"><input type="text" class="form-control" name="cpc[]" id="cpc" placeholder="Page Count" value="0" oninput="ocalculateTotal()"></td>
            <td class="thcolor"><input type="text" class="form-control" name="rpc[]" id="rpc" placeholder="Page Count" value="0" oninput="ocalculateTotal()"></td>
            <td class="thcolor"><input type="text" class="form-control" name="fnpc[]" id="fnpc" placeholder="Page Count" value="0" oninput="ocalculateTotal()"></td>
            <td class="thcolor"><input type="text" class="form-control" name="ipc[]" id="ipc" placeholder="Page Count" value="0" oninput="ocalculateTotal()"></td>
            <td class="thcolor"><input type="text" class="form-control" name="jpc[]" id="jpc" placeholder="Page Count" value="0" oninput="ocalculateTotal()"></td>
            <td class="thcolor"><input type="text" class="form-control" name="mpc[]" id="mpc" placeholder="Page Count" value="0" oninput="ocalculateTotal()"></td>
            <td class="thcolor"><input type="text" class="form-control" name="pfpc[]" id="pfpc" placeholder="Page Count" value="0" oninput="ocalculateTotal()"></td>
        </tr>
        <tr class="violetclr centercontent">
            <th>Total</th>
            <td class="thcolor"><input type="text" class="form-control" name="tfpc" id="tfpc" placeholder="Total Page Count" value="0" readonly ></td>
            <td class="thcolor"><input type="text" class="form-control" name="tcpc" id="tcpc" placeholder="Total Page Count" value="0" readonly ></td>
            <td class="thcolor"><input type="text" class="form-control" name="trpc" id="trpc" placeholder="Total Page Count" value="0" readonly ></td>
            <td class="thcolor"><input type="text" class="form-control" name="tfnpc" id="tfnpc" placeholder="Total Page Count" value="0" readonly ></td>
            <td class="thcolor"><input type="text" class="form-control" name="tipc" id="tipc" placeholder="Total Page Count" value="0" readonly ></td>
            <td class="thcolor"><input type="text" class="form-control" name="tjpc" id="tjpc" placeholder="Total Page Count" value="0" readonly ></td>
            <td class="thcolor"><input type="text" class="form-control" name="tmpc" id="tmpc" placeholder="Total Page Count" value="0" readonly ></td>
            <td class="thcolor"><input type="text" class="form-control" name="tpfpc" id="tpfpc" placeholder="Total Page Count" value="0" readonly ></td>
        </tr>
        <tr class="violetclr centercontent">
            <th>Per Day Average</th>
            <td class="thcolor"><input type="text" class="form-control" name="afpc" id="afpc" placeholder="Avg Page Count" value="0" readonly ></td>
            <td class="thcolor"><input type="text" class="form-control" name="acpc" id="acpc" placeholder="Avg Page Count" value="0" readonly ></td>
            <td class="thcolor"><input type="text" class="form-control" name="arpc" id="arpc" placeholder="Avg Page Count" value="0" readonly ></td>
            <td class="thcolor"><input type="text" class="form-control" name="afnpc" id="afnpc" placeholder="Avg Page Count" value="0" readonly ></td>
            <td class="thcolor"><input type="text" class="form-control" name="aipc" id="aipc" placeholder="Avg Page Count" value="0" readonly ></td>
            <td class="thcolor"><input type="text" class="form-control" name="ajpc" id="ajpc" placeholder="Avg Page Count" value="0" readonly ></td>
            <td class="thcolor"><input type="text" class="form-control" name="ampc" id="ampc" placeholder="Avg Page Count" value="0" readonly ></td>
            <td class="thcolor"><input type="text" class="form-control" name="apfpc" id="apfpc" placeholder="Avg Page Count" value="0" readonly ></td>
        </tr>
		<tr class="violetclr centercontent">
    <th>Preferred Cust Avg</th>
    <td class="thcolor"><input type="text" class="form-control" name="pafpc" id="pafpc" placeholder="Preferred Avg" value="0" readonly ></td>
    <td class="thcolor"><input type="text" class="form-control" name="pacpc" id="pacpc" placeholder="Preferred Avg" value="0" readonly ></td>
    <td class="thcolor"><input type="text" class="form-control" name="parpc" id="parpc" placeholder="Preferred Avg" value="0" readonly ></td>
    <td class="thcolor"><input type="text" class="form-control" name="pafnpc" id="pafnpc" placeholder="Preferred Avg" value="0" readonly ></td>
    <td class="thcolor"><input type="text" class="form-control" name="paipc" id="paipc" placeholder="Preferred Avg" value="0" readonly ></td>
    <td class="thcolor"><input type="text" class="form-control" name="pajpc" id="pajpc" placeholder="Preferred Avg" value="0" readonly ></td>
    <td class="thcolor"><input type="text" class="form-control" name="pampc" id="pampc" placeholder="Preferred Avg" value="0" readonly ></td>
    <td class="thcolor"><input type="text" class="form-control" name="papfpc" id="papfpc" placeholder="Preferred Avg" value="0" readonly ></td>
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
let preferredCustIds = [];

document.getElementById('user_id').addEventListener('change', function () {
    const selectedOption = this.options[this.selectedIndex];
    const preferString = selectedOption.getAttribute('data-prefer');

    if (preferString) {
        preferredCustIds = preferString.split(',').map(id => parseInt(id.trim()));
    } else {
        preferredCustIds = [];
    }
 // ✅ Get Department ID from hidden input field
    const deptId = parseInt(document.getElementById('dept_id').value);
    console.log("Dept ID:", deptId);
    console.log("Preferred Customer IDs:", preferredCustIds);

    if (deptId === 1 || deptId === 22) {
        calculateTotal(); // Call your preferred calculation
    } else {
        ocalculateTotal(); // Call regular version if needed
    }
});

window.addEventListener('DOMContentLoaded', function () {
    const event = new Event('change');
    document.getElementById('user_id').dispatchEvent(event);
});


</script>


     <script>
      
// Function to calculate totals and update the Total row
function calculateTotal() {
    var table = document.getElementById("myTable");
    var rows = table.getElementsByTagName('tr');

    var totalAC = 0, totalPC = 0, totalPAC = 0, totalPPC = 0;
    var countAC = 0, countPC = 0, countPAC = 0, countPPC = 0;

    var prefTotalAC = 0, prefTotalPC = 0, prefTotalPAC = 0, prefTotalPPC = 0;
    var prefCountAC = 0, prefCountPC = 0, prefCountPAC = 0, prefCountPPC = 0;

    for (var i = 1; i < rows.length - 3; i++) {
        var row = rows[i];
        row.classList.remove("highlight-incomplete", "highlight-preferred");

        var custSelect = row.querySelector('select[name="cust_id[]"]');
        var custId = custSelect ? parseInt(custSelect.value) : null;

        var acInput = row.querySelector('input[name="ac[]"]');
        var pcInput = row.querySelector('input[name="pc[]"]');
        var pacInput = row.querySelector('input[name="pac[]"]');
        var ppcInput = row.querySelector('input[name="ppc[]"]');

        var ac = acInput ? Number(acInput.value) : 0;
        var pc = pcInput ? Number(pcInput.value) : 0;
        var pac = pacInput ? Number(pacInput.value) : 0;
        var ppc = ppcInput ? Number(ppcInput.value) : 0;

        totalAC += ac;
        totalPC += pc;
        totalPAC += pac;
        totalPPC += ppc;

        if (ac !== 0) countAC++;
        if (pc !== 0) countPC++;
        if (pac !== 0) countPAC++;
        if (ppc !== 0) countPPC++;

        var isPreferred = preferredCustIds.includes(custId);

        if (isPreferred) {
            row.classList.add("highlight-preferred");
        }

        if (ac === 0 || pc === 0 || pac === 0 || ppc === 0) {
            row.classList.add("highlight-incomplete");
        }

        if (isPreferred) {
            if (ac !== 0) {
                prefTotalAC += ac;
                prefCountAC++;
            }
            if (pc !== 0) {
                prefTotalPC += pc;
                prefCountPC++;
            }
            if (pac !== 0) {
                prefTotalPAC += pac;
                prefCountPAC++;
            }
            if (ppc !== 0) {
                prefTotalPPC += ppc;
                prefCountPPC++;
            }
        }
    }

    // Total Row
    var totalRow = rows[rows.length - 3];
    totalRow.querySelector('input[name="tac"]').value = totalAC;
    totalRow.querySelector('input[name="tpc"]').value = totalPC;
    totalRow.querySelector('input[name="tpac"]').value = totalPAC;
    totalRow.querySelector('input[name="tppc"]').value = totalPPC;

    // All Customers Average Row
    var avgRow = rows[rows.length - 2];
    avgRow.querySelector('input[name="aac"]').value = (countAC ? (totalAC / countAC).toFixed(2) : 0);
    avgRow.querySelector('input[name="apc"]').value = (countPC ? (totalPC / countPC).toFixed(2) : 0);
    avgRow.querySelector('input[name="apac"]').value = (countPAC ? (totalPAC / countPAC).toFixed(2) : 0);
    avgRow.querySelector('input[name="appc"]').value = (countPPC ? (totalPPC / countPPC).toFixed(2) : 0);

    // Preferred Customers Average Row
    var prefAvgRow = rows[rows.length - 1];
    prefAvgRow.querySelector('input[name="paac"]').value = (prefCountAC ? (prefTotalAC / prefCountAC).toFixed(2) : 0);
    prefAvgRow.querySelector('input[name="papc"]').value = (prefCountPC ? (prefTotalPC / prefCountPC).toFixed(2) : 0);
    prefAvgRow.querySelector('input[name="papac"]').value = (prefCountPAC ? (prefTotalPAC / prefCountPAC).toFixed(2) : 0);
    prefAvgRow.querySelector('input[name="pappc"]').value = (prefCountPPC ? (prefTotalPPC / prefCountPPC).toFixed(2) : 0);

    // Console output
    console.log("Preferred Customer Averages (non-zero only):");
    console.log("AC:", (prefCountAC ? (prefTotalAC / prefCountAC).toFixed(2) : 0));
    console.log("PC:", (prefCountPC ? (prefTotalPC / prefCountPC).toFixed(2) : 0));
    console.log("PAC:", (prefCountPAC ? (prefTotalPAC / prefCountPAC).toFixed(2) : 0));
    console.log("PPC:", (prefCountPPC ? (prefTotalPPC / prefCountPPC).toFixed(2) : 0));
}



// Function to add a new row
function addRow() {
    var table = document.getElementById("myTable").getElementsByTagName('tbody')[0];
	
	// Insert the new row before the summary rows (Total, Per Day Avg, Preferred Avg)
    var newRow = table.insertRow(table.rows.length - 3); // Insert a new row before the "Total" row

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




    // Function to calculate total and average of Page Counts only
function ocalculateTotal() {
    const table = document.getElementById("omyTable");
    const rows = table.getElementsByTagName('tr');
    const columnNames = ['fpc', 'pfpc', 'cpc', 'rpc', 'fnpc', 'ipc', 'jpc', 'mpc'];

    let columnSums = new Array(columnNames.length).fill(0);
    let columnCounts = new Array(columnNames.length).fill(0);
    let preferSums = new Array(columnNames.length).fill(0);
    let preferCounts = new Array(columnNames.length).fill(0);

    console.log("===== START TOTAL CALCULATION =====");
    console.log("Preferred Customer IDs:", preferredCustIds);

    for (let i = 1; i < rows.length - 3; i++) {
        const row = rows[i];
        row.classList.remove("highlight-incomplete", "highlight-preferred", "highlight-preferred-incomplete");

        var custSelect = row.querySelector('select[name="cust_id[]"]');
        var custId = custSelect ? parseInt(custSelect.value) : null;

        let isPreferred = preferredCustIds.includes(custId);
        let hasZero = false;

        console.log(`Row ${i}: custId = ${custId}, isPreferred = ${isPreferred}`);

        for (let j = 0; j < columnNames.length; j++) {
            const input = row.querySelector(`input[name="${columnNames[j]}[]"]`);
            const value = parseFloat(input?.value) || 0;

            columnSums[j] += value;
            if (value > 0) columnCounts[j]++;
            else if (columnNames[j] !== 'jpc' && columnNames[j] !== 'mpc') hasZero = true;   /* JSTAGE is optional - a 0 does not flag the row */

            if (isPreferred) {
                preferSums[j] += value;
                if (value > 0) preferCounts[j]++;
                console.log(`Row ${i}: ${columnNames[j]} => Prefer Sum: ${preferSums[j]}, Count: ${preferCounts[j]}`);
            }
        }

        // Row highlighting
        if (isPreferred && hasZero) {
            row.classList.add("highlight-preferred-incomplete");
        } else if (isPreferred) {
            row.classList.add("highlight-preferred");
        } else if (hasZero) {
            row.classList.add("highlight-incomplete");
        }
    }

    // Fill Total row (3rd last row)
    const totalRow = rows[rows.length - 3];
    columnNames.forEach((col, j) => {
        const totalInput = totalRow.querySelector(`input[name="t${col}"]`);
        if (totalInput) {
            totalInput.value = columnSums[j];
            console.log(`Total ${col}:`, columnSums[j]);
        }
    });

    // Fill All Customer Averages (2nd last row)
    const allAvgRow = rows[rows.length - 2];
    columnNames.forEach((col, j) => {
        const avgInput = allAvgRow.querySelector(`input[name="a${col}"]`);
        const avg = columnCounts[j] > 0 ? columnSums[j] / columnCounts[j] : 0;
        if (avgInput) {
            avgInput.value = avg.toFixed(2);
            console.log(`All Customer Avg ${col}:`, avg.toFixed(2));
        }
    });

    // Fill Preferred Customer Averages (last row)
    const preferAvgRow = rows[rows.length - 1];
    columnNames.forEach((col, j) => {
        const preferAvgInput = preferAvgRow.querySelector(`input[name="pa${col}"]`);
        const avg = preferCounts[j] > 0 ? preferSums[j] / preferCounts[j] : 0;
        if (preferAvgInput) {
            preferAvgInput.value = avg.toFixed(2);
            console.log(`Preferred Avg ${col}:`, avg.toFixed(2));
        } else {
            console.warn(`Missing input for pa${col}`);
        }
    });

    // Display total preferred average
    const totalPreferredAvg = preferCounts.reduce((sum, count, i) => {
        return sum + (count > 0 ? preferSums[i] / count : 0);
    }, 0) / (columnNames.length - ['jpc', 'mpc'].filter(function (c) { return !(preferCounts[columnNames.indexOf(c)] > 0); }).length);   /* JSTAGE / PMC only count once they have data */

    document.getElementById("totalAveragePerDayPages").textContent =
        "Total Preferred Customer Average Per Day: " + totalPreferredAvg.toFixed(2);

    console.log("Total Preferred Avg Per Day:", totalPreferredAvg.toFixed(2));
    console.log("===== END TOTAL CALCULATION =====");
}



// Function to add a new row dynamically (Only Page Count inputs retained)
function addRowother() {
    var table = document.getElementById("omyTable").getElementsByTagName('tbody')[0];
    var newRow = table.insertRow(table.rows.length - 3); // Insert before Total row

    // Insert cells for Customer and Page Count inputs
    var cell1 = newRow.insertCell(0);
    var cell2 = newRow.insertCell(1);
    var cell3 = newRow.insertCell(2);
    var cell4 = newRow.insertCell(3);
    var cell5 = newRow.insertCell(4);
    var cell6 = newRow.insertCell(5);
    var cell7 = newRow.insertCell(6);
    var cell8 = newRow.insertCell(7);
    var cell9 = newRow.insertCell(8);

    cell1.innerHTML = '<select class="form-select" data-placeholder="Select Customer" name="cust_id[]" style="width:250px;">' + document.getElementById('cust_id').innerHTML + '</select>';
    cell2.innerHTML = '<input type="text" class="form-control fpc" name="fpc[]" placeholder="Page Count" value="0" oninput="ocalculateTotal()">';
    cell3.innerHTML = '<input type="text" class="form-control cpc" name="cpc[]" placeholder="Page Count" value="0" oninput="ocalculateTotal()">';
    cell4.innerHTML = '<input type="text" class="form-control rpc" name="rpc[]" placeholder="Page Count" value="0" oninput="ocalculateTotal()">';
    cell5.innerHTML = '<input type="text" class="form-control fnpc" name="fnpc[]" placeholder="Page Count" value="0" oninput="ocalculateTotal()">';
    cell6.innerHTML = '<input type="text" class="form-control ipc" name="ipc[]" placeholder="Page Count" value="0" oninput="ocalculateTotal()">';
    cell7.innerHTML = '<input type="text" class="form-control jpc" name="jpc[]" placeholder="Page Count" value="0" oninput="ocalculateTotal()">';
    cell8.innerHTML = '<input type="text" class="form-control mpc" name="mpc[]" placeholder="Page Count" value="0" oninput="ocalculateTotal()">';
    cell9.innerHTML = '<input type="text" class="form-control pfpc" name="pfpc[]" placeholder="Page Count" value="0" oninput="ocalculateTotal()">';

    // Action button
    newRow.insertCell(9).innerHTML = '<p onclick="deleteRowother(this)" class="delicon" style="cursor:pointer;"><img src="assets/images/delicon.svg" style="cursor:pointer;"></p>';
}

// Function to delete a row
function deleteRowother(btn) {
    var row = btn.parentNode.parentNode;
    row.parentNode.removeChild(row);
    ocalculateTotal();
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
if (isset($_POST['action']) && $_POST['action'] == 'update') {
    $dept_id = $_REQUEST['dept_id'];
    $user_id = $_REQUEST['user_id'];

    if ($dept_id == 1 || $dept_id == 22) {
        $column_query = ",
            total_ac, total_pc, total_pac, total_ppc,
            average_ac, average_pc, average_pac, average_ppc,
            average_pr_ac, average_pr_pc, average_pr_pac, average_pr_ppc";

        $value_query = ",'" . $_REQUEST['tac'] . "','" . $_REQUEST['tpc'] . "','" . $_REQUEST['tpac'] . "','" . $_REQUEST['tppc'] . "',
                         '" . $_REQUEST['aac'] . "','" . $_REQUEST['apc'] . "','" . $_REQUEST['apac'] . "','" . $_REQUEST['appc'] . "',
                         '" . $_REQUEST['paac'] . "','" . $_REQUEST['papc'] . "','" . $_REQUEST['papac'] . "','" . $_REQUEST['pappc'] . "'";
    } else {
        $column_query = ",
            total_pc, total_cpc, total_rpc, total_fpc, total_ipc, total_opc,
            average_pc, average_cpc, average_rpc, average_fpc, average_ipc, average_opc,average_pr_pc,
            average_pr_cpc, average_pr_rpc, average_pr_fpc, average_pr_ipc, average_pr_opc";

        $value_query = ",'" . $_REQUEST['tfpc'] . "','" . $_REQUEST['tcpc'] . "','" . $_REQUEST['trpc'] . "','" . $_REQUEST['tfnpc'] . "','" . $_REQUEST['tipc'] . "','" . $_REQUEST['tpfpc'] . "',
                         '" . $_REQUEST['afpc'] . "','" . $_REQUEST['acpc'] . "','" . $_REQUEST['arpc'] . "','" . $_REQUEST['afnpc'] . "','" . $_REQUEST['aipc'] . "','" . $_REQUEST['apfpc'] . "','" . $_REQUEST['pafpc'] . "',
                         '" . $_REQUEST['pacpc'] . "','" . $_REQUEST['parpc'] . "','" . $_REQUEST['pafnpc'] . "','" . $_REQUEST['paipc'] . "','" . $_REQUEST['papfpc'] . "'";
    }

$js_ok = ($__q = mysqli_query($db, "SHOW COLUMNS FROM user_target_customer_assign LIKE 'jpc'")) && mysqli_num_rows($__q) > 0
      && ($__q = mysqli_query($db, "SHOW COLUMNS FROM user_target_assign LIKE 'total_jpc'")) && mysqli_num_rows($__q) > 0;   /* JSTAGE columns exist (migration run) - otherwise save as before */
if ($js_ok && !($dept_id == 1 || $dept_id == 22)) {
    $column_query .= ",total_jpc,average_jpc,average_pr_jpc";
    $value_query  .= ",'".(float)($_REQUEST['tjpc'] ?? 0)."','".(float)($_REQUEST['ajpc'] ?? 0)."','".(float)($_REQUEST['pajpc'] ?? 0)."'";
}
$pm_ok = ($__q = mysqli_query($db, "SHOW COLUMNS FROM user_target_customer_assign LIKE 'mpc'")) && mysqli_num_rows($__q) > 0
      && ($__q = mysqli_query($db, "SHOW COLUMNS FROM user_target_assign LIKE 'total_mpc'")) && mysqli_num_rows($__q) > 0;   /* PMC columns exist (pmc migration run) */
if ($pm_ok && !($dept_id == 1 || $dept_id == 22)) {
    $column_query .= ",total_mpc,average_mpc,average_pr_mpc";
    $value_query  .= ",'".(float)($_REQUEST['tmpc'] ?? 0)."','".(float)($_REQUEST['ampc'] ?? 0)."','".(float)($_REQUEST['pampc'] ?? 0)."'";
}
    $user_details = $auth->showUsersArticle($user_id);
    $query = "INSERT INTO user_target_assign (user_id, dept_id $column_query)
              VALUES ('$user_id', '$dept_id' $value_query)";
    $user_target = mysqli_query($db, $query);
    $uta_id = mysqli_insert_id($db);

    $cust_ids = $_POST['cust_id'];

    if ($dept_id == 1 || $dept_id == 22) {
        $pc_values = $_POST['pc'];
        $ac_values = $_POST['ac'];
        $ppc_values = $_POST['ppc'];
        $pac_values = $_POST['pac'];
    } else {
        $fpc_values = $_POST['fpc'];
        $cpc_values = $_POST['cpc'];
        $rpc_values = $_POST['rpc'];
        $fnpc_values = $_POST['fnpc'];
        $ipc_values = $_POST['ipc'];
        $opc_values = $_POST['pfpc'];
    }

    for ($i = 0; $i < count($cust_ids); $i++) {
        $cust_id = $cust_ids[$i];

        if ($dept_id == 1 || $dept_id == 22) {
            $ac_value = $ac_values[$i];
            $pc_value = $pc_values[$i];
            $pac_value = $pac_values[$i];
            $ppc_value = $ppc_values[$i];

            $column_query_c = ", article_count, page_count, particle_count, ppage_count";
            $value_query_c = ",'" . $ac_value . "','" . $pc_value . "','" . $pac_value . "','" . $ppc_value . "'";
        } else {
            $fpc_value = $fpc_values[$i];
            $cpc_value = $cpc_values[$i];
            $rpc_value = $rpc_values[$i];
            $fnpc_value = $fnpc_values[$i];
            $ipc_value = $ipc_values[$i];
            $opc_value = $opc_values[$i];

            $column_query_c = ", page_count, cpc, rpc, fpc, ipc, opc";
            $value_query_c = ",'" . $fpc_value . "','" . $cpc_value . "','" . $rpc_value . "','" . $fnpc_value . "','" . $ipc_value . "','" . $opc_value . "'";
        }

        if ($js_ok && !($dept_id == 1 || $dept_id == 22)) {
            $column_query_c .= ",jpc";
            $value_query_c  .= ",'" . (int)($_POST['jpc'][$i] ?? 0) . "'";
        }
        if ($pm_ok && !($dept_id == 1 || $dept_id == 22)) {
            $column_query_c .= ",mpc";
            $value_query_c  .= ",'" . (int)($_POST['mpc'][$i] ?? 0) . "'";
        }
        $sql_uca = "INSERT INTO user_target_customer_assign (uta_id, user_id, cust_id $column_query_c)
                    VALUES ('$uta_id', '$user_id', '$cust_id' $value_query_c)";
        mysqli_query($db, $sql_uca);
    }

    // Change-log entry + email for the new target
    log_row_added('User Target Assign', $user_id . ' / ' . $user_details[0]['full_name'], change_log_target_values(change_log_target_snapshot($uta_id)));

    echo "<script type='text/javascript'>
        swal({
            title: 'Target Assigned For user " . $user_details[0]['full_name'] . " Successfully',
            type: 'success',
            confirmButtonColor: '#DD6B55',
            confirmButtonText: 'CLOSE',
        }).then(function() {
            window.location.href = '" . $_COOKIE['set_url'] . "/employeetargetassign';
        });
    </script>";
}


					

/////////////// Update completed ///////////////
?>
	
 
  <?php fu_foot('#add_target_choose', null, array(), null, fu_ui_basic('Add Employee Target', 'Add Employee Target &rsaquo; Add')); ?>
  </body>
</html>
