<?php 
include ('includes/includes.php'); 
include_once 'includes/issue_drafts.php';

if(isset($_REQUEST['getaction']))
{
	
$var = '';
if($_POST['cust_id']!=''){
	
     $var.= "and cus.id = '".$_POST['cust_id']."' ";
}
if($_POST['stage_id']!=''){
	
      $var.= "and stage LIKE '".$_POST['stage_id']."%' ";
}
if($_POST['j_id']!=''){
	
     $var.= "and ju.j_id = '".$_POST['j_id']."' ";
}
if($_POST['pub_id']!=''){
	
     $var.= "and pub_id LIKE '%".$_POST['pub_id']."%' ";
}
if($_POST['from_dt']!='' && $_POST['to_dt']){
	
   $var.="and received_date BETWEEN '".date('Y-m-d',strtotime($_POST['from_dt']))."' and '".date('Y-m-d',strtotime($_POST['to_dt']))."'";
}
}

$from_dt = isset($_POST['from_dt']) ? htmlspecialchars($_POST['from_dt']) : '';
$to_dt = isset($_POST['to_dt']) ? htmlspecialchars($_POST['to_dt']) : '';
$idr_map = idr_issue_map(idr_runner_mysqli($db));
?>
<!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php include_once 'includes/modern-ui.php'; include_once 'includes/article-filter-ui.php'; include_once 'includes/issue_drafts.php'; mu_head(); af_head(); idr_head(); ?>
    
    <!-- Data Table CSS- -->
    <style>
      td:last-child  a {
        font-size: 0.925em !important;
      }
    </style>
    </head>
    
    <body>
    <div class="wholeouter">
    <!-- Header logo section -->
    
    <?php include("includes/top-header.php");?>
    <div class="container-fluid" style="justify-content: space-between;">
      <div class="af-page" style="padding-bottom:0;">
        <div class="af-hero">
          <div><small>With the client</small><h1>Under Client Issues</h1></div>
        </div>
      </div>
    </div>
        <!-- /.page-header -->
        <div class="row" style="width: 100%">
          <div class="span12">
            <?php
        if(!empty($_REQUEST['msg']))  {
        echo '<div class="alert alert-success">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        '. $_REQUEST['msg'] .'</div>';   
        } ?>
          </div>
        </div>
		
		<div class="af-page"><div class="af-wrap">
        <form action="" name="articles" class="formouter" method="post">
          <div class="af-bar">
            <div class="af-field date dvr-field">
              <label>Date range</label>
              <div class="dvr" data-from-name="from_dt" data-to-name="to_dt" data-from="<?php echo htmlspecialchars($from_dt); ?>" data-to="<?php echo htmlspecialchars($to_dt); ?>"></div>
            </div>
            <link rel="stylesheet" href="dv-rangepicker.css?v=1"><script src="dv-rangepicker.js?v=1"></script>
            <div class="af-actions">
              <button type="button" class="af-btn" id="afToggle"><i class="fa fa-sliders"></i> Filters <span class="af-count">0</span> <i class="fa fa-angle-down"></i></button>
              <button type="submit" name="getaction" class="af-btn primary"><i class="fa fa-search"></i> Get Result</button>
              <a class="af-btn" href="underclientissues" title="Reset filters"><i class="fa fa-refresh"></i></a>
            </div>
          </div>
          <div class="af-panel<?php echo (isset($_REQUEST['cust_id']) && $_REQUEST['cust_id']!='') || (isset($_REQUEST['j_id']) && $_REQUEST['j_id']!='') ? ' open' : ''; ?>" id="afPanel">
            <div class="af-grid">
              <div class="af-field">
                <label>Customer</label>
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
              <div class="af-field">
                <label>Journal</label>
                <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_id" id="j_id">
												<option value="">Select Journal</option>

                    <?php
											$sql_journals = $auth->showJournallist('0',2);
											foreach($sql_journals as $res_journals)
											{
												
									      	if(isset($_REQUEST['j_id']))
													{
										    	?>
										       <option  <?php if ($res_journals['j_id']==$_REQUEST['j_id']){?>selected<?php }?> value="<?php echo $res_journals['j_id'] ?>"style="font-weight:500;"><?php echo $res_journals['j_code'] ?> </option>
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
            </div>
          </div>
          <div class="af-chips" id="afChips"></div>
        </form>
      </div></div>
        <div class="row">
          <div class="col-xs-12"> 
            <!-- PAGE CONTENT BEGINS --> 
            
            <!-- div.dataTables_borderWrap -->
            <div class="af-page"><div class="af-card">
            <div class="table-responsive">
            <table id="articles" class="table custtable table-primary" style="width: 100% !important; overflow:auto !important;text-wrap: nowrap !important;white-space: nowrap">
                <thead>
                  <tr>
                    <th data-af-role="client">Customer</th>
                    <th data-af-role="journal">Journal</th>
                    <th data-af-role="file">Issue Folder Name</th>
                    <th data-af-role="recv">Created Date</th>
                    <th data-af-role="recv">Due Date</th>
                    <th data-af-role="num">#Articles</th>
                    <th>Draft Status</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php
		                $query = "SELECT vi.vi_id,vi.received_date,vi.due_date,volume_number,issue_number,cus.cust_name,ju.j_code,(SELECT COUNT(id) FROM inw_inward_dtl WHERE v_issue_id = vi.vi_id AND status='Delivery') as ccount FROM inw_volume_issues as vi LEFT JOIN adm_customer_master as cus ON vi.cust_id = cus.id LEFT JOIN adm_journals as ju ON vi.j_id = ju.j_id WHERE vi.status = 'Client Review' $var  ORDER BY vi.vi_id ASC;";
		              // echo $query;
		                $customers = $db->query($query) or ($db->error);				
		                while($row = $customers->fetch_assoc()){ 		
		              ?>
                  <tr>
                    <td><?php echo $row['cust_name']; ?></td>
                    <td><?php echo $row['j_code']; ?></td>
                    <td><?php echo VOLUME_PREFIX.$row['volume_number'].$row['issue_number']; ?></td>
                    <td><?php echo $row['received_date'] ? date('d-m-Y', strtotime($row['received_date'])) : ''; ?></td>
                    <td><?php echo $row['due_date'] ? date('d-m-Y', strtotime($row['due_date'])) : ''; ?></td>
                    <td><?php echo $row['ccount']; ?></td>
                    <td><?php echo idr_issue_cell($idr_map, $row['vi_id']); ?></td>
                    <td><div class="hidden-sm hidden-xs action-buttons">
                        <?php
					              if($_COOKIE['department_id'] == 16 || $_COOKIE['user_level'] == 1){ ?>
                        <?php if($row['stage'] == 'FIN') { ?>
                        <a class="btn btn-xs btn-primary btn-sm" href="#"> <i class="ace-icon fa fa-folder bigger-120"></i> Archive</a>
                        <?php } else { ?>
                        <a class="btn btn-xs btn-primary" href="rev_issue.php?id=<?php echo $row['vi_id']; ?>"> <i class="ace-icon fa fa-history bigger-120"></i> Revises </a> <a class="btn btn-xs btn-success btn-sm" title="Book selected articles for client corrections (DRAFT1, DRAFT2 ...)" href="issue_draft_rebook.php?id=<?php echo $row['vi_id']; ?>"> <i class="ace-icon fa fa-pencil bigger-120"></i> Draft Correction </a> <a class="btn btn-xs btn-pink btn-sm" href="fin_issue.php?id=<?php echo $row['vi_id']; ?>"> <i class="ace-icon fa fa-share bigger-120"></i> Final </a> <a class="btn btn-xs btn-warning btn-sm" href="undo_issue.php?id=<?php echo $row['vi_id']; ?>"> <i class="ace-icon fa fa-share bigger-120"></i> Undo </a> 
                        <?php } ?>
                        <?php } ?>
                      </div></td>
                  </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
                        </div></div>
            
            <!-- PAGE CONTENT ENDS --> 
          </div>
          <!-- /.col --> 
        </div>
          <!-- /.main-content -->
    <?php echo scripts(); ?> 
    
    <!-- Footer Section --> 
    <?php echo footer(); ?> 
<!-- inline scripts related to this page --> 
<script src="assets/js/select2/select2.min.js"></script>
    <?php af_foot(); ?>
   
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
			//datepicker plugin
				//link
				$('.date-picker').datepicker({
					autoclose: true,
					todayHighlight: true,
					format: 'dd-mm-yyyy'
				})
	
					

			})
		</script> 
		
			
<script type="text/javascript">
			$(document).ready(function() {
        $('#articles').DataTable({
 
 dom: "<'row'<'col-sm-4'l><'col-sm-4 text-center'B><'col-sm-4'f>>tp",
       //table.row(':eq(0)', { page: 'current' }).select();
	    pageLength: 10,

		buttons: [
			<?php 
		if($_COOKIE['user_level']== 1) 
		{
		?>
			{extend: 'copy', title: 'Client Issue Articles',className: 'btn-sm',exportOptions: {
                    columns: [ 0, 1, 2, 3, 4, 5 ]
                }},
			{extend: 'csv',title: 'Client Issue Articles', className: 'btn-sm',exportOptions: {
                    columns: [ 0, 1, 2, 3, 4, 5 ]
                }},
			{extend: 'pdf', title: 'Client Issue Articles', className: 'btn-sm',exportOptions: {
                    columns: [ 0, 1, 2, 3, 4, 5 ]
                }},
			{extend: 'print', title: 'Client Issue Articles',className: 'btn-sm',exportOptions: {
                    columns: [ 0, 1, 2, 3, 4, 5 ]
                }}
		<?php 
		}
		?>
 ],

 dom: 'Blfr<"table-wrapper"t>ip',
         buttons: [
            {
                extend: 'collection', // Use a collection button for the dropdown
                text: 'Export', // The text for the main button
                buttons: [
                    'copyHtml5',
                    'excelHtml5',
                    'csvHtml5',
                    {
                        extend: 'pdfHtml5',
                        customize: function (doc) {
                            // Customize the PDF content here (optional)
                        }
                    },
                    {
                        extend: 'print',
                        autoPrint: false, // Disable automatic printing
                        customize: function (win) {
                            // Customize the printed content here (optional)
                        }
                    }
                ]
            }
         ],

});
});
			
function confirmDelete(delUrl) 
{
	if (confirm("Are you sure you want to delete")) 
	{
		document.location = delUrl;
	}
}		
		</script>
</body>
</html>
