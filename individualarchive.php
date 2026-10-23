<?php 
include ('includes/includes.php'); 
include_once 'includes/issue_drafts.php';
$from_dt = isset($_POST['from_dt']) ? htmlspecialchars($_POST['from_dt']) : '';
$to_dt = isset($_POST['to_dt']) ? htmlspecialchars($_POST['to_dt']) : '';


if(isset($_REQUEST['getaction']))
{
	
$var = '';
if($_POST['cust_id']!=''){
	
     $var.= "and c.id = '".$_POST['cust_id']."' ";
}
if($_POST['stage_id']!=''){
	
      $var.= "and wd.stage LIKE '".$_POST['stage_id']."%' ";
}
if($_POST['j_id']!=''){
	
     $var.= "and j.j_id = '".$_POST['j_id']."' ";
}
if($_POST['pub_id']!=''){
	
     $var.= "and wd.pub_id LIKE '%".$_POST['pub_id']."%' ";
}
if($_POST['from_dt']!='' && $_POST['to_dt']){
	
   $var.="and wd.recv_dt BETWEEN '".date('Y-m-d',strtotime($_POST['from_dt']))."' and '".date('Y-m-d',strtotime($_POST['to_dt']))."'";
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
    <?php include_once 'includes/modern-ui.php'; include_once 'includes/article-filter-ui.php'; mu_head(); af_head(); idr_head(); ?>
    <style>
      .af-bar .select2-container{min-width:220px;width:220px !important;}
      .af-bar .select2-container--default .select2-selection--single{height:38px;border:1px solid #d5dde6;border-radius:9px;}
      .af-bar .select2-container--default .select2-selection--single .select2-selection__rendered{line-height:36px;padding-left:12px;}
      .af-bar .select2-container--default .select2-selection--single .select2-selection__arrow{height:36px;}
      #articles td:first-child,#articles th:first-child{width:auto;}
      .af-card .label{display:inline-block;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;line-height:1.3;color:#fff;background:#0e9aa7;margin-left:6px;}
    </style>

    <!-- Data Table CSS- -->
    
    </head>
    
    <body>
    <div class="wholeouter">
    <!-- Header logo section -->
    
    <?php include("includes/top-header.php");?>
    <div class="container-fluid" style="justify-content: space-between;">
      <div class="af-page" style="padding-bottom:0;">
        <div class="af-hero">
          <div><small>Completed articles</small><h1>Individual Archive</h1></div>
        </div>
      </div>
    </div>

    <?php
    if(!empty($_REQUEST['msg']))  {
      echo '<div class="alert alert-success"><button type="button" class="close" aria-label="Close">&times;</button>'. htmlspecialchars($_REQUEST['msg']) .'</div>';
    } ?>

    <div class="af-page"><div class="af-wrap">
      <form action="" name="articles" class="formouter" method="post">
        <div class="af-bar">
          <div class="af-field"><label>Customer</label>
            <select class="form-select customer_drop" data-placeholder="Select Customer" name="cust_id" id="cust_id">
								<option value="">Select Customer</option>

                   <?php
											$sql_customers = $auth->showCustomerlist();
											foreach($sql_customers as $res_customers)
											{
												
									      	if(isset($_REQUEST['cust_id']))
													{
										    	?>
										       <option <?php if ($res_customers['id']==$_REQUEST['cust_id']){?>selected<?php }?> value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
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
          <div class="af-field"><label>Journal</label>
            <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_id" id="j_id">
				<option value="">Select Journal</option>
                    <?php
											$sql_journals = $auth->showJournallist('0',2);
											foreach($sql_journals as $res_journals)
											{
												
									      	if(isset($_REQUEST['j_id']))
													{
										    	?>
										       <option <?php if ($res_journals['j_id']==$_REQUEST['j_id']){?>selected<?php }?> value="<?php echo $res_journals['j_id'] ?>"style="font-weight:500;"><?php echo $res_journals['j_code'] ?> </option>
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
          <div class="af-field date dvr-field">
            <label>Date range</label>
            <div class="dvr" data-from-name="from_dt" data-to-name="to_dt" data-from="<?php echo $from_dt; ?>" data-to="<?php echo $to_dt; ?>"></div>
          </div>
          <link rel="stylesheet" href="dv-rangepicker.css?v=1"><script src="dv-rangepicker.js?v=1"></script>
          <div class="af-actions">
            <button type="submit" name="getaction" class="af-btn primary"><i class="fa fa-search"></i> Get Result</button>
            <a class="af-btn" href="individualarchive" title="Reset filters"><i class="fa fa-refresh"></i></a>
          </div>
        </div>
      </form>
    </div></div>
    <div class="row">
          <div class="col-xs-12"> 
            <!-- PAGE CONTENT BEGINS --> 
            
            <!-- div.dataTables_borderWrap -->
            <div class="af-page"><div class="af-card">
            <div class="table-responsive">
            <table id="articles" class="table custtable table-primary idr-compact" style="width: 100% !important; text-wrap: nowrap !important">
                <thead>
                  <tr>
                    <th data-af-role="client">Customer</th>
                    <th data-af-role="journal">Journal</th>
                    <th data-af-role="file">Article</th>
                    <th>Group folder</th>
                    <th data-af-role="recv">Received Date</th>
                    <th data-af-role="recv">Created On</th>
                    <th data-af-role="num">Manuscript</th>
                    <th data-af-role="num">Fig</th>
                    <th data-af-role="num">Table</th>
                    <th data-af-role="stage">Stage</th>
                    <th data-af-role="none">Status</th>
                    <th>Archive State</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
				  if(isset($_REQUEST['getaction']))
				  {
						if($_COOKIE['user_level'] != 1) 
						{		  
							if($_COOKIE['department_id'] == 16)
							{
								$query = "SELECT c.cust_name,journal_title,pub_id,issueorgroup_folder,recv_dt,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,j.j_id,manuscript_count,stage,folder FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.approved_by > 0 and wd.cust_id = c.id and wd.j_id = j.j_id and wd.status = 'Client Review' $var ORDER BY `id` DESC ";
							}
							else
							{
								$query = "SELECT c.cust_name,journal_title,pub_id,issueorgroup_folder,recv_dt,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,j.j_id,manuscript_count,stage,folder FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND department_id = '".$_COOKIE['department_id']."' and wd.status = 'Client Review' $var ORDER BY `id` DESC ";
							}
						}
						else
						{
							$query = "SELECT c.cust_name,journal_title,pub_id,issueorgroup_folder,recv_dt,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,j.j_id,manuscript_count,stage,folder FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id and wd.status = 'Client Review' $var ORDER BY `id` DESC ";
							

						
						
						}	
						//echo $query;
						$customers = $db->query($query);
						while($customers && ($row = $customers->fetch_assoc())){   // a failed query shows an empty table instead of stopping the page 
						
						
						?>
								   <tr>
									<td><?php echo $row['cust_name']; ?></td>
									<td><?php echo $row['j_code']; ?></td>
									<td><?php echo $row['pub_id']; ?><?php if(!empty($row['notes'])) { ?><span class="label label-sm label-info"><?php echo htmlspecialchars(substr($row['notes'],0,35)); ?>...</span><?php } ?></td>                   
								<td><?php echo ltrim($row['issueorgroup_folder'],"/"); ?></td>                   
								<td><?php echo $row['recv_dt'] ? date('d-m-Y', strtotime($row['recv_dt'])) : ''; ?></td>
								<td><?php echo (date('d-m-Y',strtotime($row['created_dt']))); ?></td>
								<td><?php echo $row['manuscript_count']; ?></td>
								<td><?php echo $row['fig']; ?></td>
								<td><?php echo $row['tab']; ?></td>
								<td><?php echo $row['stage']; ?></td>
								<td><span class="af-status" style="--c:#1e9e61"><?php echo $row['status']; ?></span></td>
								<td><a class="btn btn-xs btn-primary btn-sm" href="archieve_imp.php?id=<?php echo $row['pub_id'];?>&j_id=<?php echo $row['j_id'];?>"> <i class="ace-icon fa fa-folder bigger-120"></i> Archive</a></td>
							  </tr>
							  <?php 			
							  } 
				  }
				  ?>
                </tbody>
              </table>
            </div></div></div>
            
            <!-- PAGE CONTENT ENDS --> 
          </div>
          <!-- /.col --> 
        </div>
 <!-- /.main-content -->
    <?php echo scripts(); ?> 
    <?php af_foot(); ?>
    
    <!-- Footer Section --> 
    <?php echo footer(); ?>       

<!-- inline scripts related to this page --> 

 <script src="assets/js/select2/select2.min.js"></script>

    <script>
      $(".customer_drop").select2({
        placeholder: "Select Customer",
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
	  $(".graphics_status_drop").select2({
        placeholder: "Select Graphics Stage",
      });
	  
    </script>
<script type="text/javascript">
		
		$(document).ready(function() {
			var table = $('#articles').DataTable({ order: [], dom: 'Blfr<"table-wrapper"t>ip', buttons: [{ extend: 'collection', text: 'Export', buttons: ['copyHtml5', 'excelHtml5', 'csvHtml5', 'pdfHtml5', { extend: 'print', autoPrint: false }] }] });
		});

			$(document).ready(function(){
        $("#cust_id").on("change", function() {
         var cust_id=$.trim($(this).val());

         // Fetch states based on the selected client name and populate the "name" select element
         $.ajax({
             url: "<?php echo $_COOKIE['set_url'];?>/get_journal.php",
             dataType: "json",
             data: { cust_id:cust_id },
             success: function(data) {
                 populateSelecte("j_id",data);

             }
         });
     });
function populateSelecte(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option data-id="+item.j_code+"_0000  data-previous="+item.j_code+"_0000>").text(item.j_code).val(item.j_id));
            });
        }

	
	});
	
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
		</script>
<script type="text/javascript">
			

jQuery(function($) {			
			
				//documentation : http://docs.jquery.com/Plugins/Validation/validate
			
				//datepicker plugin
				//link
				$('.date-picker').datepicker({
					autoclose: true,
					todayHighlight: true,
					format: 'dd-mm-yyyy'
				})
	
				//$('#validation-form').validate({errorClass: 'help-block'});			

			})
		</script>
	

</body>
</html>
