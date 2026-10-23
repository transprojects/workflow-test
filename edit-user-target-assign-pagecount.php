<?php include "includes/includes-sw2.php";
require_once __DIR__.'/includes/form_ui.php';
include_once("smtpmail/phpmailer/class.phpmailer.php");
include_once "includes/change_log_mail.php";
?>
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

$existing_ppc_values = [];
$existing_pac_values = [];
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

$existing_ipc_values = [];
$existing_jac_values = [];
$existing_jpc_values = [];
$existing_mac_values = [];
$existing_mpc_values = [];
$existing_iac_values = [];	

$existing_opc_values =[];
$existing_oac_values =[];
}

// Populate arrays with existing values, if any
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
		
		if($user_details_target[0]['dept_id']==1 || $user_details_target[0]['dept_id']==22 )
{
        $existing_ac_values[] = $row['article_count'];
        $existing_pc_values[] = $row['page_count'];
		
		$existing_pac_values[] = $row['particle_count'];
        $existing_ppc_values[] = $row['ppage_count'];
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
		
		$existing_ipc_values[] =$row['ipc'];
		$existing_jac_values[] = $row['jac'] ?? 0;
		$existing_jpc_values[] = $row['jpc'] ?? 0;
		$existing_mac_values[] = $row['mac'] ?? 0;
		$existing_mpc_values[] = $row['mpc'] ?? 0;
		$existing_iac_values[] =$row['iac'];
		
		$existing_opc_values[] =$row['opc'];
		$existing_oac_values[] =$row['oac'];
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

      <?php fu_hero(fu_ui_basic('Edit Employee Target', 'Edit Employee Target &rsaquo; Edit')); ?>
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
																	<th>Pre Clean Article Count</th>
																	<th>Pre Clean Page Count</th>
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
																<td  class=""><input type="text" class="form-control" name="pac[]" id="pac" value="<?php echo $existing_pac_values[$i];?>"oninput="calculateTotal()" ></td>
																<td class=""><input type="text" class="form-control" name="ppc[]" id="ppc"  value="<?php echo $existing_ppc_values[$i];?>"oninput="calculateTotal()" ></td>
																<td class="hidden-480"><p onclick="deleteRow(this)" class="delicon" style="cursor:pointer;"><img src="<?php echo $_COOKIE['set_url'];?>/assets/images/delicon.svg" style="cursor:pointer;"></td>
																</tr>
																
											<?php endfor; ?>



																	<tr>
																<td class="">Total</td>
																<td  class=""><input type="text" class="form-control" name="tac" id="tac" value="<?php echo $user_details_target[0]['total_ac'];?>"></td>
																<td class=""><input type="text" class="form-control" name="tpc" id="tpc" value="<?php echo $user_details_target[0]['total_pc'];?>"></td>
																<td  class=""><input type="text" class="form-control" name="tpac" id="tpac" value="<?php echo $user_details_target[0]['total_pac'];?>"></td>
																<td class=""><input type="text" class="form-control" name="tppc" id="tppc" value="<?php echo $user_details_target[0]['total_ppc'];?>"></td>
																<td class="hidden-480"></td>
																</tr>
																	<tr>
																<td class="">Per Day Average</td>
																<td  class=""><input type="text" class="form-control" name="aac" id="aac" value="<?php echo $user_details_target[0]['average_ac'];?>"></td>
																<td class=""><input type="text" class="form-control" name="apc" id="apc" value="<?php echo $user_details_target[0]['average_pc'];?>"></td>
																<td  class=""><input type="text" class="form-control" name="apac" id="apac" value="<?php echo $user_details_target[0]['average_pac'];?>"></td>
																<td class=""><input type="text" class="form-control" name="appc" id="appc" value="<?php echo $user_details_target[0]['average_ppc'];?>"></td>
																<td class="hidden-480"></td>
																</tr>
																<tr>
																		<td>Preferred Cust Avg</td>
																		<td><input type="text" class="form-control" name="paac" id="paac" value="<?php echo $user_details_target[0]['average_pr_ac'];?>"  readonly></td>
																		<td><input type="text" class="form-control" name="papc" id="papc" value="<?php echo $user_details_target[0]['average_pr_pc'];?>" readonly></td>
																		<td><input type="text" class="form-control" name="papac" id="papac" value="<?php echo $user_details_target[0]['average_pr_pac'];?>" readonly></td>
																		<td><input type="text" class="form-control" name="pappc" id="pappc" value="<?php echo $user_details_target[0]['average_pr_ppc'];?>" readonly></td>
																		<td></td>
																	</tr>
															</tbody>

														</table>
															<input type="hidden" name="action" value="update">
															<input type="hidden" name="uta_id" value="<?php echo $_REQUEST['uid'];?>">
															<input type="hidden" name="user_id" value="<?php echo $user_details_target[0]['id'];?>">
															<input type="hidden" name="preferred_cust_id" id="preferred_cust_id" value="<?php echo $user_details_target[0]['preferred_cust_id'];?>">
															<input type="hidden" name="dept_id" value="<?php echo $user_details_target[0]['dept_id'];?>">

													</div>
													</div>
			</div>
			
				<?php 
			}
			else
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
            <th>Customer</th>
            <th>FP</th>
            <th>Corrections</th>
            <th>Revises</th>
            <th>Finals</th>
            <th>Issue</th>
            <th>JSTAGE</th>
            <th>PMC</th>
            <th>Others</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php for ($i = 0; $i < count($existing_pc_values); $i++) : ?>
        <tr>
            <td>
                <select class="form-select" name="cust_id[]" id="cust_id" style="width:250px;">
                    <option value="all">Select All</option>
                    <?php
                    if($_COOKIE['user_level']=='3') {
                        $sql_customers = $auth->showCustomerlistbyuser($_COOKIE['cust_id'],$_COOKIE['ocust_id']);
                    } else {
                        $sql_customers = $auth->showCustomerlist();
                    }
                    foreach($sql_customers as $res_customers) {
                    ?>
                    <option <?php if ($existing_cust_id[$i]==$res_customers['id']) echo 'selected'; ?> value="<?php echo $res_customers['id']; ?>" style="font-weight:500;">
                        <?php echo $res_customers['cust_name']; ?>
                    </option>
                    <?php } ?>
                </select>
            </td>
            <td><input type="text" class="form-control" name="fpc[]" id="fpc" placeholder="Page Count" value="<?php echo $existing_pc_values[$i];?>" oninput="ocalculateTotal(1)"></td>
            <td><input type="text" class="form-control" name="cpc[]" id="cpc" placeholder="Page Count" value="<?php echo $existing_cpc_values[$i];?>" oninput="ocalculateTotal(3)"></td>
            <td><input type="text" class="form-control" name="rpc[]" id="rpc" placeholder="Page Count" value="<?php echo $existing_rpc_values[$i];?>" oninput="ocalculateTotal(5)"></td>
            <td><input type="text" class="form-control" name="fnpc[]" id="fnpc" placeholder="Page Count" value="<?php echo $existing_fnpc_values[$i];?>" oninput="ocalculateTotal(7)"></td>
            <td><input type="text" class="form-control" name="ipc[]" id="ipc" placeholder="Page Count" value="<?php echo $existing_ipc_values[$i];?>" oninput="ocalculateTotal(9)"></td>
            <td><input type="text" class="form-control" name="jpc[]" id="jpc" placeholder="Page Count" value="<?php echo $existing_jpc_values[$i];?>" oninput="ocalculateTotal(9)"></td>
            <td><input type="text" class="form-control" name="mpc[]" id="mpc" placeholder="Page Count" value="<?php echo $existing_mpc_values[$i];?>" oninput="ocalculateTotal(9)"></td>
            <td><input type="text" class="form-control" name="pfpc[]" id="pfpc" placeholder="Page Count" value="<?php echo $existing_opc_values[$i];?>" oninput="ocalculateTotal(11)"></td>
            <td class="hidden-480"><p onclick="deleteRowother(this)" class="delicon" style="cursor:pointer;"><img src="<?php echo $_COOKIE['set_url'];?>/assets/images/delicon.svg" style="cursor:pointer;"></p></td>
        </tr>
        <?php endfor; ?>

        <tr class="violetclr centercontent">
            <th>Total</th>
            <td><input type="text" class="form-control" name="tfpc" id="tfpc" placeholder="Total Page Count" value="<?php echo $user_details_target[0]['total_pc'];?>" ></td>
            <td><input type="text" class="form-control" name="tcpc" id="tcpc" placeholder="Total Page Count" value="<?php echo $user_details_target[0]['total_cpc'];?>" ></td>
            <td><input type="text" class="form-control" name="trpc" id="trpc" placeholder="Total Page Count" value="<?php echo $user_details_target[0]['total_rpc'];?>" ></td>
            <td><input type="text" class="form-control" name="tfnpc" id="tfnpc" placeholder="Total Page Count" value="<?php echo $user_details_target[0]['total_fpc'];?>" ></td>
            <td><input type="text" class="form-control" name="tipc" id="tipc" placeholder="Total Page Count" value="<?php echo $user_details_target[0]['total_ipc'];?>" ></td>
            <td><input type="text" class="form-control" name="tjpc" id="tjpc" placeholder="Total Page Count" value="<?php echo $user_details_target[0]['total_jpc'] ?? 0;?>" ></td>
            <td><input type="text" class="form-control" name="tmpc" id="tmpc" placeholder="Total Page Count" value="<?php echo $user_details_target[0]['total_mpc'] ?? 0;?>" ></td>
            <td><input type="text" class="form-control" name="tpfpc" id="tpfpc" placeholder="Total Page Count" value="<?php echo $user_details_target[0]['total_opc'];?>" ></td>
            <td></td>
        </tr>

        <tr class="violetclr centercontent">
            <th>Per Day Average</th>
            <td><input type="text" class="form-control" name="afpc" id="afpc" placeholder="Avg Page Count" value="<?php echo $user_details_target[0]['average_pc'];?>" ></td>
            <td><input type="text" class="form-control" name="acpc" id="acpc" placeholder="Avg Page Count" value="<?php echo $user_details_target[0]['average_cpc'];?>" ></td>
            <td><input type="text" class="form-control" name="arpc" id="arpc" placeholder="Avg Page Count" value="<?php echo $user_details_target[0]['average_rpc'];?>" ></td>
            <td><input type="text" class="form-control" name="afnpc" id="afnpc" placeholder="Avg Page Count" value="<?php echo $user_details_target[0]['average_fpc'];?>" ></td>
            <td><input type="text" class="form-control" name="aipc" id="aipc" placeholder="Avg Page Count" value="<?php echo $user_details_target[0]['average_ipc'];?>" ></td>
            <td><input type="text" class="form-control" name="ajpc" id="ajpc" placeholder="Avg Page Count" value="<?php echo $user_details_target[0]['average_jpc'] ?? 0;?>" ></td>
            <td><input type="text" class="form-control" name="ampc" id="ampc" placeholder="Avg Page Count" value="<?php echo $user_details_target[0]['average_mpc'] ?? 0;?>" ></td>
            <td><input type="text" class="form-control" name="apfpc" id="apfpc" placeholder="Avg Page Count" value="<?php echo $user_details_target[0]['average_opc'];?>" ></td>
            <td></td>
        </tr>
		<tr class="violetclr centercontent">
    <th>Preferred Cust Avg</th>
    <td class="thcolor"><input type="text" class="form-control" name="pafpc" id="pafpc" placeholder="Preferred Avg" value="<?php echo $user_details_target[0]['average_pr_pc'];?>" readonly ></td>
    <td class="thcolor"><input type="text" class="form-control" name="pacpc" id="pacpc" placeholder="Preferred Avg" value="<?php echo $user_details_target[0]['average_pr_cpc'];?>" readonly ></td>
    <td class="thcolor"><input type="text" class="form-control" name="parpc" id="parpc" placeholder="Preferred Avg" value="<?php echo $user_details_target[0]['average_pr_rpc'];?>" readonly ></td>
    <td class="thcolor"><input type="text" class="form-control" name="pafnpc" id="pafnpc" placeholder="Preferred Avg"value="<?php echo $user_details_target[0]['average_pr_fpc'];?>" readonly ></td>
    <td class="thcolor"><input type="text" class="form-control" name="paipc" id="paipc" placeholder="Preferred Avg" value="<?php echo $user_details_target[0]['average_pr_ipc'];?>" readonly ></td>
    <td class="thcolor"><input type="text" class="form-control" name="pajpc" id="pajpc" placeholder="Preferred Avg" value="<?php echo $user_details_target[0]['average_pr_jpc'] ?? 0;?>" readonly ></td>
    <td class="thcolor"><input type="text" class="form-control" name="pampc" id="pampc" placeholder="Preferred Avg" value="<?php echo $user_details_target[0]['average_pr_mpc'] ?? 0;?>" readonly ></td>
    <td class="thcolor"><input type="text" class="form-control" name="papfpc" id="papfpc" placeholder="Preferred Avg" value="<?php echo $user_details_target[0]['average_pr_opc'];?>" readonly ></td>
</tr>
    </tbody>
</table>


															<input type="hidden" name="action" value="update">
															<input type="hidden" name="user_id" value="<?php echo $user_details_target[0]['id'];?>">
															<input type="hidden" name="preferred_cust_id" id="preferred_cust_id" value="<?php echo $user_details_target[0]['preferred_cust_id'];?>">
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
let preferredCustIds = [];

window.addEventListener('DOMContentLoaded', function () {
    // ✅ Get preferred customer IDs from hidden input
    const preferString = document.getElementById('preferred_cust_id').value;
    if (preferString) {
        preferredCustIds = preferString.split(',').map(id => parseInt(id.trim()));
    } else {
        preferredCustIds = [];
    }

    // ✅ Get Department ID from hidden input field
    const deptId = parseInt(document.getElementById('dept_id').value);
    console.log("Dept ID:", deptId);
    console.log("Preferred Customer IDs:", preferredCustIds);

    // ✅ Call appropriate function based on department
    if (deptId === 1 || deptId === 22) {
        calculateTotal();
    } else {
        ocalculateTotal();
    }
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

    // Total Row (3rd from last)
    var totalRow = rows[rows.length - 3];
    totalRow.querySelector('input[name="tac"]').value = totalAC;
    totalRow.querySelector('input[name="tpc"]').value = totalPC;
    totalRow.querySelector('input[name="tpac"]').value = totalPAC;
    totalRow.querySelector('input[name="tppc"]').value = totalPPC;

    // All Customers Average Row (2nd from last)
    var avgRow = rows[rows.length - 2];
    avgRow.querySelector('input[name="aac"]').value = (countAC ? (totalAC / countAC).toFixed(2) : 0);
    avgRow.querySelector('input[name="apc"]').value = (countPC ? (totalPC / countPC).toFixed(2) : 0);
    avgRow.querySelector('input[name="apac"]').value = (countPAC ? (totalPAC / countPAC).toFixed(2) : 0);
    avgRow.querySelector('input[name="appc"]').value = (countPPC ? (totalPPC / countPPC).toFixed(2) : 0);

    // Preferred Customers Average Row (last row)
    var prefAvgRow = rows[rows.length - 1];
    prefAvgRow.querySelector('input[name="paac"]').value = (prefCountAC ? (prefTotalAC / prefCountAC).toFixed(2) : 0);
    prefAvgRow.querySelector('input[name="papc"]').value = (prefCountPC ? (prefTotalPC / prefCountPC).toFixed(2) : 0);
    prefAvgRow.querySelector('input[name="papac"]').value = (prefCountPAC ? (prefTotalPAC / prefCountPAC).toFixed(2) : 0);
    prefAvgRow.querySelector('input[name="pappc"]').value = (prefCountPPC ? (prefTotalPPC / prefCountPPC).toFixed(2) : 0);

    // Console debug
    console.log("Preferred Customer Averages:");
    console.log("AC:", (prefCountAC ? (prefTotalAC / prefCountAC).toFixed(2) : 0));
    console.log("PC:", (prefCountPC ? (prefTotalPC / prefCountPC).toFixed(2) : 0));
    console.log("PAC:", (prefCountPAC ? (prefTotalPAC / prefCountPAC).toFixed(2) : 0));
    console.log("PPC:", (prefCountPPC ? (prefTotalPPC / prefCountPPC).toFixed(2) : 0));
}



// Function to add a new row
function addRow() {
    var table = document.getElementById("myTable").getElementsByTagName('tbody')[0];
    var newRow = table.insertRow(table.rows.length - 3); // Insert a new row before the "Total" row
    
    // 1. Customer Cell
    var customerCell = newRow.insertCell(0);
    customerCell.innerHTML = table.rows[0].cells[0].innerHTML;

    // 2. Input Fields: AC, PC, PAC, PPC
    var inputNames = ['ac[]', 'pc[]', 'pac[]', 'ppc[]'];
    for (var i = 0; i < inputNames.length; i++) {
        var newCell = newRow.insertCell(i + 1); // Start from column 1
        var input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control';
        input.name = inputNames[i];
        input.value = '0';
        input.oninput = calculateTotal;
        newCell.appendChild(input);
    }

    // 3. Action (Delete) button
    newRow.insertCell(5).innerHTML = '<p onclick="deleteRow(this)" class="delicon" style="cursor:pointer;"><img src="<?php echo $_COOKIE['set_url'];?>/assets/images/delicon.svg" style="cursor:pointer;"></p>';
}



// Function to delete a row
function deleteRow(btn) {
    var row = btn.parentNode.parentNode;
    row.parentNode.removeChild(row);
    calculateTotal(); // Recalculate totals after deleting a row
}





// Function to calculate total AC and PC, Corrections, Revises, Finals, and averages
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

        const custSelect = row.querySelector('select[name="cust_id[]"]');
        const custId = custSelect ? parseInt(custSelect.value) : null;

        const isPreferred = preferredCustIds.includes(custId);
        let hasZero = false;

        for (let j = 0; j < columnNames.length; j++) {
            const input = row.querySelector(`input[name="${columnNames[j]}[]"]`);
            const value = parseFloat(input?.value) || 0;

            columnSums[j] += value;
            if (value > 0) columnCounts[j]++;
            else if (columnNames[j] !== 'jpc' && columnNames[j] !== 'mpc') hasZero = true;   /* JSTAGE is optional - a 0 does not flag the row */

            if (isPreferred) {
                preferSums[j] += value;
                if (value > 0) preferCounts[j]++;
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
        if (totalInput) totalInput.value = columnSums[j];
    });

    // Fill All Customer Averages (2nd last row)
    const allAvgRow = rows[rows.length - 2];
    columnNames.forEach((col, j) => {
        const avgInput = allAvgRow.querySelector(`input[name="a${col}"]`);
        const avg = columnCounts[j] > 0 ? columnSums[j] / columnCounts[j] : 0;
        if (avgInput) avgInput.value = avg.toFixed(2);
    });

    // Fill Preferred Customer Averages (last row)
    const preferAvgRow = rows[rows.length - 1];
    columnNames.forEach((col, j) => {
        const preferAvgInput = preferAvgRow.querySelector(`input[name="pa${col}"]`);
        const avg = preferCounts[j] > 0 ? preferSums[j] / preferCounts[j] : 0;
        if (preferAvgInput) preferAvgInput.value = avg.toFixed(2);
    });

    // Display total preferred average
    const totalPreferredAvg = preferCounts.reduce((sum, count, i) => {
        return sum + (count > 0 ? preferSums[i] / count : 0);
    }, 0) / (columnNames.length - ['jpc', 'mpc'].filter(function (c) { return !(preferCounts[columnNames.indexOf(c)] > 0); }).length);   /* JSTAGE / PMC only count once they have data */

    document.getElementById("totalAveragePerDayPages").textContent =
        "Total Preferred Customer Average Per Day: " + totalPreferredAvg.toFixed(2);

    // Clear unused placeholder if exists
    document.getElementById("totalAveragePerDay").textContent = "";

    console.log("===== END TOTAL CALCULATION =====");
}









   function addRowother() {
    var table = document.getElementById("omyTable").getElementsByTagName('tbody')[0];
    var newRow = table.insertRow(table.rows.length - 3); // Insert before Total row

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

    newRow.insertCell(9).innerHTML = '<p onclick="deleteRowother(this)" class="delicon" style="cursor:pointer;"><img src="<?php echo $_COOKIE['set_url'];?>/assets/images/delicon.svg" style="cursor:pointer;"></p>';
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

if (isset($_POST['action']) && $_POST['action'] == 'update') {
	
	function getCustomerName($db, $cust_id) {
    $cust_name = "Unknown";

    $sql = "SELECT cust_name FROM adm_customer_master WHERE id = '" . intval($cust_id) . "'";
    $res = mysqli_query($db, $sql);

    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        $cust_name = $row['cust_name'];
    }

    return $cust_name;
}

	$old_uta_id = $_REQUEST['uta_id'];
    $dept_id = $_REQUEST['dept_id'];
    $user_id = $_REQUEST['user_id'];

	
    $query_es = "SELECT * FROM `email_settings` WHERE id= '2'";
    $steps_es = $db->query($query_es) or ($db->error);
    $email_st = $steps_es->fetch_assoc();
	
	$query_des = "SELECT * FROM `adm_dept_master` WHERE dept_code= '".$dept_id."'";
    $steps_des = $db->query($query_des) or ($db->error);
    $dept_st = $steps_des->fetch_assoc();
	$dept_name=$dept_st['dept_name'];
   
    // Assign column/value sets
    if ($dept_id == 1 || $dept_id == 22) {
        $column_query = ",
            total_ac, total_pc, total_pac, total_ppc,
            average_ac, average_pc, average_pac, average_ppc,
            average_pr_ac, average_pr_pc, average_pr_pac, average_pr_ppc";

        $value_query = ",'{$_REQUEST['tac']}','{$_REQUEST['tpc']}','{$_REQUEST['tpac']}','{$_REQUEST['tppc']}',
                         '{$_REQUEST['aac']}','{$_REQUEST['apc']}','{$_REQUEST['apac']}','{$_REQUEST['appc']}',
                         '{$_REQUEST['paac']}','{$_REQUEST['papc']}','{$_REQUEST['papac']}','{$_REQUEST['pappc']}'";
    } else {
        $column_query = ",
            total_pc, total_cpc, total_rpc, total_fpc, total_ipc, total_opc,
            average_pc, average_cpc, average_rpc, average_fpc, average_ipc, average_opc, average_pr_pc,
            average_pr_cpc, average_pr_rpc, average_pr_fpc, average_pr_ipc, average_pr_opc";

        $value_query = ",'{$_REQUEST['tfpc']}','{$_REQUEST['tcpc']}','{$_REQUEST['trpc']}','{$_REQUEST['tfnpc']}','{$_REQUEST['tipc']}','{$_REQUEST['tpfpc']}',
                         '{$_REQUEST['afpc']}','{$_REQUEST['acpc']}','{$_REQUEST['arpc']}','{$_REQUEST['afnpc']}','{$_REQUEST['aipc']}','{$_REQUEST['apfpc']}',
                         '{$_REQUEST['pafpc']}','{$_REQUEST['pacpc']}','{$_REQUEST['parpc']}','{$_REQUEST['pafnpc']}','{$_REQUEST['paipc']}','{$_REQUEST['papfpc']}'";
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

    // Snapshot before change (for change-log email)
    $__old_snap = change_log_target_snapshot($old_uta_id);

    // ✅ Insert new target first
    $user_target = mysqli_query($db, "INSERT INTO user_target_assign (user_id, dept_id $column_query) 
                VALUES ('$user_id', '$dept_id' $value_query)");

    if (!$user_target) {
        echo "Error inserting user_target_assign: " . mysqli_error($db);
        exit;
    }

    $uta_id = mysqli_insert_id($db);
    $cust_ids = $_POST['cust_id'] ?? [];

    // Collect inputs
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

    // Insert customer-wise
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
        $sql_uca = mysqli_query($db, "INSERT INTO user_target_customer_assign 
            (uta_id, user_id, cust_id $column_query_c) 
            VALUES ('$uta_id', '$user_id', '$cust_id' $value_query_c)");

        if (!$sql_uca) {
            echo "Error inserting user_target_customer_assign for cust_id $cust_id: " . mysqli_error($db);
            exit;
        }
    }

    // ✅ Now safely delete old records after new insert success
    mysqli_query($db, "DELETE FROM user_target_assign WHERE uta_id='" . $old_uta_id . "' ");
    mysqli_query($db, "DELETE FROM user_target_customer_assign WHERE uta_id='" . $old_uta_id . "'");

    // Change-log email (diff old vs newly inserted rows)
    log_target_assign_change('User Target Assign (Page Count)', $user_id . ' / ' . $user_details[0]['full_name'], $__old_snap, change_log_target_snapshot($uta_id));

  
// ✅ Build email notification
$subject = "User Target Updated - " . $user_details[0]['full_name'];

// Build customer-wise table
$table = "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse: collapse; font-family: Arial; font-size: 13px;'>
<tr style='background:#f2f2f2;'>
    <th>Customer</th>";

if ($dept_id == 1 || $dept_id == 22) {
    $table .= "<th>Article Count</th><th>Page Count</th><th>Particle Count</th><th>PPage Count</th>";
} else {
    $table .= "<th>FPC</th><th>CPC</th><th>RPC</th><th>FNPC</th><th>IPC</th><th>OPC</th>";
}
$table .= "</tr>";

for ($i = 0; $i < count($cust_ids); $i++) {
    $cust_id = $cust_ids[$i];
    $cust_name = getCustomerName($db, $cust_id);

    $table .= "<tr><td>" . htmlspecialchars($cust_name) . "</td>";

    if ($dept_id == 1 || $dept_id == 22) {
        $table .= "<td>" . ($ac_values[$i] ?? 0) . "</td>
                   <td>" . ($pc_values[$i] ?? 0) . "</td>
                   <td>" . ($pac_values[$i] ?? 0) . "</td>
                   <td>" . ($ppc_values[$i] ?? 0) . "</td>";
    } else {
        $table .= "<td>" . ($fpc_values[$i] ?? 0) . "</td>
                   <td>" . ($cpc_values[$i] ?? 0) . "</td>
                   <td>" . ($rpc_values[$i] ?? 0) . "</td>
                   <td>" . ($fnpc_values[$i] ?? 0) . "</td>
                   <td>" . ($ipc_values[$i] ?? 0) . "</td>
                   <td>" . ($opc_values[$i] ?? 0) . "</td>";
    }
    $table .= "</tr>";
}
$table .= "</table>";

$body = "<p>Dear Team,</p>";
$body .= "<p>The target for <strong>" . $user_details[0]['full_name'] . "</strong> has been updated.</p>";
$body .= "<p><strong>New UTA ID:</strong> $uta_id<br>
<strong>Department ID:</strong> $dept_id<br/>
<strong>Department Name:</strong> $dept_name<br/>
<strong>Edited By:</strong> ".$_COOKIE['full_name']."</p>";
$body .= "<p>Below are the updated target details:</p>";
$body .= $table;
$body .= "<p>Best regards,<br>System Notification</p>";

// ✅ PHPMailer setup
$mail = new PHPMailer();
$mail->IsSMTP();
$mail->CharSet = 'UTF-8';
$mail->Host = "outlook.office365.com";
$mail->SMTPAuth = true;
$mail->Port = 587;
$mail->Username = $email_st['from_email'];
$mail->Password = $email_st['from_password'];
$mail->SMTPSecure = 'tls';
$mail->From = $email_st['from_email'];
$mail->FromName = "Journal Workflow";
$mail->isHTML(true);
$mail->Subject = $subject;
$mail->Body = $body;

// ✅ Recipients
$mail->addAddress("marimuthu@transforma.in");
$ccEmails = ["viji@transforma.in", "jeyapriya@transforma.in", "uma@transforma.in", "pc@transforma.in"];
foreach ($ccEmails as $cc) {
    $mail->addCC($cc);
}

// ✅ Send email & SweetAlert
if ($mail->Send()) {
    echo "<script type='text/javascript'>
		 swal({
            title: 'Target Updated For user " . addslashes($user_details[0]['full_name']) . " Successfully and Email notification sent',
            type: 'success',
            confirmButtonColor: '#DD6B55',
            confirmButtonText: 'CLOSE',
        }).then(function() {
            window.location.href = '" . $_COOKIE['set_url'] . "/employeetargetassign';
        });
    </script>";
} else {
    echo "<script type='text/javascript'>
		swal({
            title: 'Target Updated For user " . addslashes($user_details[0]['full_name']) . " Successfully But Email Failed',
            type: 'success',
            confirmButtonColor: '#DD6B55',
            confirmButtonText: 'CLOSE',
        }).then(function() {
            window.location.href = '" . $_COOKIE['set_url'] . "/employeetargetassign';
        });
    </script>";
}
}



					

/////////////// Update completed ///////////////
?>
	
 
  <?php fu_foot('#add_target_choose', null, array(), null, fu_ui_basic('Edit Employee Target', 'Edit Employee Target &rsaquo; Edit')); ?>
  </body>
</html>
