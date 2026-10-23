<?php include "includes/includes.php";
session_start();

if(isset($_POST['assign']))
{
	//print_r($_POST);
	$post['target_due_dt'] = date('Y-m-d',strtotime($_POST['target_due_dt']));
	foreach($_POST['aids'] as $key => $a_id) {
		if($a_id>0)
		{
			$prj_update_query="UPDATE inw_inward_dtl SET `target_due_dt` ='".$post['target_due_dt']."' WHERE id = ".$a_id." "; 
			//echo $prj_update_query; exit;
			$db->query($prj_update_query);
			
		}		
	}
	
	header("location:".$_COOKIE['set_url']."/assignduedate?msg=Successfully Assigned Due Dates");
}

if(isset($_SESSION['inpt_status_qry']) || $_SESSION['inpt_status_qry'] =='' )
{
	$_SESSION['inpt_status_qry'] = " AND wd.status NOT IN ('Delivery','Client Review','Completed','Query','Hold') ";
}

if(isset($_REQUEST['getaction']) && $_REQUEST['getaction']!='')
{
	
$_SESSION['cust_id'] = $_POST['cust_id'];
$_SESSION['stage_id'] = $_POST['stage_id'];
$_SESSION['j_id'] = $_POST['j_id'];
$_SESSION['pub_id'] = $_POST['pub_id'];
$_SESSION['from_dt'] = $_POST['from_dt'];
$_SESSION['to_dt'] = $_POST['to_dt'];
$_SESSION['inpt_status'] = $_POST['inpt_status'];
$_SESSION['inpt_status_qry'] = " AND wd.status NOT IN ('Delivery','Client Review','Completed','Query','Hold') ";

if($_SESSION['inpt_status']!=''){
	if (in_array("Query", (array)($_SESSION['inpt_status'] ?? [])) && in_array("Hold", (array)($_SESSION['inpt_status'] ?? [])))
	{
		$_SESSION['inpt_status_qry'] = " AND wd.status NOT IN ('Delivery','Client Review','Completed') ";
	}
	else if(in_array("Query", (array)($_SESSION['inpt_status'] ?? [])))
	{
		$_SESSION['inpt_status_qry'] = " AND wd.status NOT IN ('Delivery','Client Review','Completed','Hold') ";
	}
	else if(in_array("Hold", (array)($_SESSION['inpt_status'] ?? [])))
	{
		$_SESSION['inpt_status_qry'] = " AND wd.status NOT IN ('Delivery','Client Review','Completed','Query') ";
	}
     
}
$var = '';
if($_SESSION['cust_id']!=''){
	
     $var.= "and c.id = '".$_SESSION['cust_id']."' ";
}
if($_SESSION['stage_id']!=''){
	
      $var.= "and stage LIKE '".$_SESSION['stage_id']."%' ";
}
if($_SESSION['j_id']!=''){
	
     $var.= "and j.j_id = '".$_SESSION['j_id']."' ";
}
if($_SESSION['pub_id']!=''){
	
     $var.= "and pub_id LIKE '%".$_SESSION['pub_id']."%' ";
}
if($_SESSION['from_dt']!='' && $_SESSION['to_dt']){
	
   $var.="and recv_dt BETWEEN '".date('Y-m-d',strtotime($_SESSION['from_dt']))."' and '".date('Y-m-d',strtotime($_SESSION['to_dt']))."'";
}

	$_SESSION['varname'] = $var;
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
    <?php include_once 'includes/modern-ui.php'; include_once 'includes/article-filter-ui.php'; mu_head(); af_head(); ?>

    <!-- Data Table CSS- -->

  
<style>
.ad-wrap{padding:0;}
.ad-wrap .ad-head,.ad-wrap .ad-chips,.ad-wrap .ad-form{padding-left:18px;padding-right:18px;}
.ad-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding-top:16px;padding-bottom:10px;}
.ad-head h3,.ad-bar h3{margin:0;font-size:15px;font-weight:700;color:#14304a;}
.ad-head p,.ad-bar p{margin:2px 0 0;font-size:12.5px;color:#6b7785;}
.ad-total{display:flex;align-items:baseline;gap:6px;padding:8px 16px;border-radius:12px;background:#fdeee3;color:#e8742a;}
.ad-total b{font-size:22px;line-height:1;} .ad-total span{font-size:11.5px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;}
.ad-chips{display:grid;grid-template-columns:repeat(auto-fill,minmax(168px,1fr));gap:8px;padding-bottom:14px;}
.ad-chip{display:flex;align-items:center;gap:8px;padding:7px 10px;border:1px solid #e4e9ef;border-radius:10px;background:#fff;opacity:.55;transition:transform .15s,box-shadow .2s,opacity .2s;}
.ad-chip i{width:8px;height:8px;border-radius:50%;background:var(--c);flex:0 0 auto;}
.ad-chip em{font-style:normal;font-size:12.5px;font-weight:600;color:#22303f;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.ad-chip b{min-width:24px;text-align:center;padding:1px 7px;border-radius:20px;background:#eef2f6;color:#6b7785;font-size:12px;}
.ad-chip.has{opacity:1;border-left:3px solid var(--c);} .ad-chip.has b{background:var(--c);color:#fff;}
.ad-chip:hover{transform:translateY(-1px);box-shadow:0 6px 14px rgba(20,48,74,.1);opacity:1;}
.ad-form{border-top:1px dashed #e4e9ef;padding-top:14px;padding-bottom:14px;}
.ad-wrap .formouter{border:0;box-shadow:none;background:transparent !important;}
.ad-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px;}
.ad-sel{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6b7785;}
.ad-sel b{min-width:34px;text-align:center;padding:5px 12px;border-radius:20px;background:#e8742a;color:#fff;font-size:14px;}
.ad-st{display:inline-block;padding:3px 11px;border-radius:20px;background:#e6f6ee;color:#1e9e61;font-weight:700;font-size:12px;}
.ad-st.warn{background:#fdecec;color:#d64545;}
.ad-stage{display:inline-block;padding:3px 10px;border-radius:8px;background:#eaf2fa;color:#1b6ca8;font-weight:700;font-size:12px;}
.ad-note{margin-left:6px;padding:2px 8px;border-radius:20px;background:#e8f3fb;color:#1b6ca8;font-size:11px;font-weight:600;}
.ad-assign{position:sticky;bottom:12px;z-index:5;display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-top:12px;padding:12px 16px;border-radius:14px;
  background:linear-gradient(120deg,#14304a,#1f4b73);color:#fff;box-shadow:0 10px 26px rgba(20,48,74,.3);}
.ad-assign-l{display:flex;align-items:center;gap:12px;}
.ad-ico{display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.15);font-size:18px;}
.ad-assign label{display:block;margin:0 0 3px;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;opacity:.8;}
.ad-assign .input-group{display:flex;align-items:center;position:relative;}
.ad-assign .form-control{height:38px;width:170px;border-radius:9px;border:0;padding:0 34px 0 12px;font-size:13.5px;}
.ad-assign .input-group-addon{position:absolute;right:10px;color:#6b7785;pointer-events:none;}
.ad-assign .help-block{position:absolute;top:100%;left:0;color:#ffd0d0;font-size:11px;white-space:nowrap;}
.ad-assign .af-btn{border:0;}
.af-card .dataTables_wrapper .dataTables_length{margin-bottom:6px !important;}
@media(max-width:700px){.ad-assign{position:static;}.ad-assign .af-btn{width:100%;justify-content:center;}.ad-bar,.ad-head{flex-wrap:wrap;}}
</style>
</head>

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
   
      <!-- Page body starts -->
   <style>
  .alert {
    max-width: 300px; /* Adjust the maximum width as needed */
    padding: 10px; /* Add some padding for better spacing */
    margin: 10px auto; /* Center the div horizontally */
  }
  .hide {
  display: none;
}
.close {
    position: absolute;
    top: 5px;
    right: 5px;
    padding: 5px;
    background-color: transparent;
    border: none;
    cursor: pointer;
  }

  .close:hover {
    color: #000;
  }
  
  .dataTables_length{
	  margin-bottom: 15px !important;
  }
</style>   
      <div class="container-fluid" style="justify-content: space-between;">
	  
	   <?php
  if(!empty($_REQUEST['msg']))  {
    echo '<div id="successMessage" class="alert alert-success">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      '. $_REQUEST['msg'] .'</div>';   
  }
  ?> 
        <div class="af-page" style="padding-bottom:0;">
          <div class="af-hero">
            <div><small>Planning</small><h1>Assign Due Date to Articles</h1></div>
          </div>
        </div>
      </div>

      <div class="af-page"><div class="af-wrap ad-wrap">
        <div class="ad-head"><div><h3>Pending due dates by customer</h3><p>First-proof articles that still need a target due date</p></div><div class="ad-total"><b id="adTotal">0</b><span>articles</span></div></div>
        <div class="ad-chips">
<?php
$ad_total = 0; ob_start();
$run_row = $db->query("SELECT * FROM `adm_customer_master`") or die($db->error);
while ($row = $run_row->fetch_assoc()) {
    $fp_query = "SELECT count(id)as stage_count FROM `inw_inward_dtl` WHERE cust_id =".$row['id']." AND  stage = 'FP' AND (target_due_dt IS NULL OR target_due_dt = '1970-01-01') AND status NOT IN ('Delivery','Client Review','Completed','Query','Hold')  $searchfp $search_cy  $search_cm $search_cd ";
    $fp_run_query = $db->query($fp_query) or die($db->error);
    $fp_row = $fp_run_query->fetch_assoc();
    $fp_id_count = (int)$fp_row['stage_count'];
    $ad_total += $fp_id_count;
?>
          <span class="ad-chip<?php echo $fp_id_count ? ' has' : ''; ?>" style="--c:<?php echo htmlspecialchars($row['font_color'] ?: '#14304a'); ?>"><i></i><em><?php echo htmlspecialchars($row['cust_name']); ?></em><b><?php echo $fp_id_count; ?></b></span>
<?php }
$ad_chips_html = ob_get_clean(); echo $ad_chips_html; ?>
        </div>
        <script>document.getElementById('adTotal').textContent = '<?php echo $ad_total; ?>';</script>
        <form action="" name="articles" class="formouter ad-form" method="post">
          <div class="af-bar">
            <div class="af-field" style="min-width:240px;">
              <label>Choose Customer</label>
              <select class="form-select" data-placeholder="Select Customer" name="cust_id" id="cust_id">
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
										       <option <?php if ($_SESSION['cust_id'] == $res_customers['id']) { echo ' selected="selected"'; } ?> value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
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
            <div class="af-field check">
              <input type="checkbox" id="inpt_status_q" name="inpt_status[]" value="Query" <?php if (in_array("Query", (array)($_SESSION['inpt_status'] ?? []))) { echo 'checked'; } ?> />
              <label for="inpt_status_q">Include Query</label>
            </div>
            <div class="af-field check">
              <input type="checkbox" id="inpt_status_h" name="inpt_status[]" value="Hold" <?php if (in_array("Hold", (array)($_SESSION['inpt_status'] ?? []))) { echo 'checked'; } ?> />
              <label for="inpt_status_h">Include Hold</label>
            </div>
            <div class="af-actions">
              <button type="submit" name="getaction" value="Get Articles" class="af-btn primary"><i class="fa fa-search"></i> Get Articles</button>
              <a class="af-btn" href="assignduedate" title="Reset filters"><i class="fa fa-refresh"></i></a>
            </div>
          </div>
        </form>
      </div></div>
              

      <!-- Table section -->

       <div class="af-card">
	   <div class="table-responsive">
					<div class="ad-bar">
            <div><h3>Articles awaiting a due date</h3><p>Tick the articles, pick a date and press Assign</p></div>
            <div class="ad-sel"><span>Selected</span><b class="" id="a_count">0</b></div>
          </div>

		<form action="assign_due_date_articles.php" id="assign_due_date_form" method="post" autocomplete="off">
              <table id="dynamic-table" class="table custtable table-primary" style="width: 100% !important; overflow:auto !important;text-wrap: nowrap !important;white-space: nowrap !important">
                <thead>
                  <tr>
                    <th class="center"> <label class="pos-rel">
                        <input type="checkbox" id="checkAll" name="checkAll" class="ace" />
                        <span class="lbl"></span> </label>
                    </th>                    
                    <th data-sortable="true" scope="col" class="thcolor">Journal</th>
                    <th data-sortable="true" scope="col" class="thcolor">Article</th>
                    <th data-sortable="true" scope="col" class="thcolor"> Received Date </th>
                    <th data-sortable="true" scope="col" class="thcolor">Due Date </th>
                    <th data-sortable="true" scope="col" class="thcolor">Manuscript</th>
                    <th data-sortable="true" scope="col" class="thcolor">Fig</th>
                    <th data-sortable="true" scope="col" class="thcolor">Table</th>
                    <th data-sortable="true" scope="col" class="thcolor">Stage</th>
                    <th data-sortable="true" scope="col" class="thcolor">Status</th>
                    
                  </tr>
                </thead>
                <tbody>
                  <?php

		
	
			   
			   $cnt_query = "SELECT COUNT(wd.id) as num FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND wd.stage = 'FP' AND wd.`target_due_dt` IS NULL  ".$_SESSION['inpt_status_qry']." ";
			   if($_SESSION['cust_id']!="all" && $_SESSION['cust_id']!="")
			   {
			    $query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage, wd.notes FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND wd.stage = 'FP' AND (wd.target_due_dt IS NULL OR wd.target_due_dt = '1970-01-01') ".$_SESSION['inpt_status_qry']." ".$_SESSION['varname']." ";
			   }
			   else
			   {
			   $query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage, wd.notes FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND wd.stage = 'FP' AND (wd.target_due_dt IS NULL OR wd.target_due_dt = '1970-01-01') ".$_SESSION['inpt_status_qry']."";
			   }
	
		$customers = $db->query($query) or ($db->error);				
		while($row = $customers->fetch_assoc()){ 
		?>
                  <tr <?php if($row['status'] == 'Query' || $row['status'] == 'Hold'){ ?> class="af-row-flag" <?php } ?>>
                    <td class="center"><label class="pos-rel">
                        <input type="checkbox" class="ace aids" name="aids[]" value="<?php echo $row['id']; ?>" />
                        <span class="lbl"></span> </label></td>                   
                    <td><span class="af-chip-j"><?php echo htmlspecialchars($row['j_code']); ?></span></td>
                    <td class="hidden-480"><span class="af-art"><?php echo htmlspecialchars($row['pub_id']); ?></span><?php echo ($row['notes']!='') ? ' <span class="ad-note" title="'.htmlspecialchars($row['notes']).'">'.htmlspecialchars(substr($row['notes'],0,10)).'</span>' : '';?></td>
                    <td><?php echo (date('d-m-Y',strtotime($row['recv_dt']))); ?></td>
                    <td><?php echo (date('d-m-Y',strtotime($row['due_dt']))); ?></td>
                    <td><span class="af-num"><?php echo $row['manuscript_count']; ?></span></td>
                    <td><span class="af-num"><?php echo $row['fig']; ?></span></td>
                    <td class="hidden-480"><span class="af-num"><?php echo $row['tab']; ?></span></td>
                    <td class="hidden-480"><span class="ad-stage"><?php echo htmlspecialchars($row['stage']); ?></span></td>
                    <td class="hidden-480"><span class="ad-st <?php echo in_array($row['status'], array('Query','Hold')) ? 'warn' : ''; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                   
                  </tr>
                  <?php } ?>
                
				
                </tbody>
              </table>
			  
			<!-- <table>
			    <tr><td colspan="6"> <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-4 no-padding-right" for="name">Date:</label>
                            <div class="col-xs-12 col-sm-4">
                              <div class="input-group">
                                <input type="text" id="target_due_dt" name="target_due_dt" class="required col-xs-12 col-sm-12 date-picker" value="" />
                                <span class="input-group-addon"> <i class="ace-icon fa fa-calendar bigger-110"></i> </span> </div>
                            </div>
                          </div></td>
						  <td colspan="4">
						  
                            <button type="submit" id="assign" value="Assign" name="assign" class="btn btn-sm btn-warning">Assign <i class="ace-icon fa fa-plus icon-on-right"></i></button>
                          
					</td>
				</tr>
			  </table> -->
			  <div class="ad-assign">
              <div class="ad-assign-l"><span class="ad-ico"><i class="fa fa-calendar-check-o"></i></span>
                <div><label for="target_due_dt">Target due date</label>
                  <div class="input-group">
                    <input type="text" id="target_due_dt" name="target_due_dt" class="required date-picker form-control" value="" placeholder="dd-mm-yyyy" autocomplete="off">
                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                  </div>
                </div>
              </div>
              <button type="submit" id="assign" value="Assign" name="assign" class="af-btn primary"><i class="fa fa-check"></i> Assign due date</button>
            </div>
			  </form>
		
		</div>
      	</div>
</div>

     <!-- Footer Section -->
     <?php echo footer(); ?>




<!-- Footer Section -->
<?php echo scripts(); ?>	

   
     <script type="text/javascript">
	 
	 
	 $(document).ready(function() {
			table = $('#dynamic-table').DataTable();
	 });
$(document).ready(function() {
    $('#assign_due_date_form').submit(function() {
        if ($('input:checked', this).length  > 0) {
            return true;
        } else {
            alert('Please tick at least one Article to assign the due date!');
            return false;
        }
    });
	
	$('.aids').click(function() {
        var checkboxes = $('input[name="aids[]"]:checked').length;
		//alert(checkboxes);
		$("#a_count").html(checkboxes);
        
    })
	
});


$(function () {
    $("#checkAll").click(function () {
        if ($("#checkAll").is(':checked')) {
            $(".aids").prop("checked", true);
			var checkboxes = $('input[name="aids[]"]:checked').length;	
			$("#a_count").html(checkboxes - 1);			
		$("#a_count").html(checkboxes);
        } else {
            $(".aids").prop("checked", false);
			$("#a_count").html(0);
        }
    });
});
			
function confirmDelete(delUrl) 
{
	if (confirm("Are you sure you want to delete")) 
	{
		document.location = delUrl;
	}
}		

jQuery(function($) {			
			
				//documentation : http://docs.jquery.com/Plugins/Validation/validate
			
				//datepicker plugin
				//link
				$('.date-picker').datepicker({
					autoclose: true,
					todayHighlight: true,
					format: 'dd-mm-yyyy'
				})
	
				$('#assign_due_date_form').validate({errorClass: 'help-block'});			

			})
		</script> 
<script type="text/javascript">
			$(document).ready(function(){
    $('#cust_id').on('change',function(){
        var j_cust_id = $(this).val();
        if(j_cust_id){
            $.ajax({
                type:'POST',
                url:'get_journals.php',
                data:'j_cust_id='+j_cust_id,
                success:function(html){
                    $('#j_id').html(html);                    
                }
            }); 
        }else{
            $('#j_id').html('<option value="">Select customer first</option>');
            
        }
    });

	
	});
	
			
		</script>
  <?php af_foot(); ?>
  </body>
</html>
