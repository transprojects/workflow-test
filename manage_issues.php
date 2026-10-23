<?php include "includes/includes.php"; include_once 'includes/issue_drafts.php';?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php include_once 'includes/modern-ui.php'; include_once 'includes/article-filter-ui.php'; mu_head(); af_head(); idr_head(); ?>
    
    <!-- Data Table CSS- -->
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
    </head>
    
    <body>
    <div class="wholeouter">
    <!-- Header logo section -->
    
    <?php include("includes/top-header.php");?>
    <?php
if(isset($_REQUEST['cust_id']))
{
$cust_id=implode(",",$_REQUEST['cust_id']);
$ex_cid = explode(",",$cust_id);
}
else
{
$ex_cid='';
}
if(isset($_REQUEST['j_id']))
{
	 $jid=implode(",",$_REQUEST['j_id']);
 	 $ex_jid = explode(",",$jid);
}
else
{
	$ex_jid='';
}
if(isset($_REQUEST['from_dt']))
{
	  $from_dt=$_REQUEST['from_dt'];
}
else
{
	$from_dt='';
}
if(isset($_REQUEST['to_dt']))
{
	  $to_dt=$_REQUEST['to_dt'];
}
else
{
	$to_dt='';
}

	
?>
    
    <!-- Page body starts -->
    
    <div class="container-fluid" style="justify-content: space-between;">
      <div class="af-page" style="padding-bottom:0;">
        <div class="af-hero">
          <div><small>Volumes and issues in production</small><h1>Manage Issues</h1></div>
          <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a class="af-add" href="<?php echo $_COOKIE['set_url'];?>/create_issue.php"><i class="fa fa-plus"></i> Create Issue</a>
            <a class="af-add" style="background:rgba(255,255,255,.16);box-shadow:none;border:1px solid rgba(255,255,255,.35);" href="<?php echo $_COOKIE['set_url'];?>/reject_issue.php"><i class="fa fa-exchange"></i> Reject / Assign Issue</a>
            <a class="af-add" style="background:rgba(255,255,255,.16);box-shadow:none;border:1px solid rgba(255,255,255,.35);" href="<?php echo $_COOKIE['set_url'];?>/issue_change_status.php"><i class="fa fa-random"></i> Change status</a>
          </div>
        </div>
      </div>
    </div>

    <?php
    if(!empty($_REQUEST['msg']))  {
      echo '<div id="successMessage" class="alert alert-success">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        '. $_REQUEST['msg'] .'</div>';
    }
    ?>

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
            <button type="submit" name="btn-wip" class="af-btn primary"><i class="fa fa-search"></i> Get Result</button>
            <a class="af-btn" href="manage_issues.php" title="Reset filters"><i class="fa fa-refresh"></i></a>
          </div>
        </div>
        <div class="af-panel<?php echo (isset($_REQUEST['cust_id']) || isset($_REQUEST['j_id'])) ? ' open' : ''; ?>" id="afPanel">
          <div class="af-grid">
            <div class="af-field">
              <label>Customer</label>
              <select class="form-select customer_drop" data-placeholder="Select Customer" name="cust_id[]" id="cust_id"  multiple="multiple">
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
            <div class="af-field">
              <label>Journal</label>
              <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_id[]" id="j_id"  multiple="multiple">
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
          </div>
        </div>
        <div class="af-chips" id="afChips"></div>
      </form>
    </div></div>

    <!-- Table section -->
    
    <div class="af-page"><div class="af-card">
      <div class="table-responsive">
        <table id="articles" class="table custtable table-primary idr-compact" style="width: 100% !important; overflow:auto !important; text-wrap: nowrap !important; white-space: nowrap">
          <thead>
            <tr>              
              <th data-sortable="true" scope="col" class="thcolor" data-af-role="client">Customer</th>
              <th data-sortable="true" scope="col" class="thcolor" data-af-role="journal">Journal</th>
              <th data-sortable="true" scope="col" class="thcolor" data-af-role="file">Issue Folder Name</th>
              <th data-sortable="true" scope="col" class="thcolor" data-af-role="num">Start Page</th>
              <th data-sortable="true" scope="col" class="thcolor" data-af-role="recv">Created Date</th>
              <th data-sortable="true" scope="col" class="thcolor" data-af-role="recv">Due Date</th>
              <th data-sortable="true" scope="col" class="thcolor" data-af-role="stage">Stage</th>
              <th data-sortable="true" scope="col" class="thcolor" data-af-role="num">#Articles Done</th>
              <th data-sortable="true" scope="col" class="thcolor" data-af-role="num">#Articles Pending</th>
              <th class="thcolor">Draft Status</th>
              <th></th>
            </tr>
          </thead>          
        </table>
      </div>
      
      <!-- PAGE CONTENT ENDS --> 
    </div></div>
    
    <!-- Footer Section --> 
    <?php echo footer(); ?> 
    
    <!-- Footer Section --> 
    <?php echo scripts(); ?> 
    <?php af_foot(); ?>
    <?php idr_error_script(); idr_error_popup('Something went wrong'); ?>
    
    <!-- Initialize DataTables --> 
    <script>
		<?php
    //echo ;exit;
			if(isset($_REQUEST['page']))
				$page = $_REQUEST['page']*10;
			else
				$page = 0;
			
			if(isset($_REQUEST['search']))
				$search = $_REQUEST['search'];
			else
				$search = '';
        if(isset($_REQUEST['cust_id']))
        $cust_id = implode(",",$_REQUEST['cust_id']);
     else
       $cust_id = '';
      if(isset($_REQUEST['j_id']))
				 $j_id = implode(",",$_REQUEST['j_id']);
			else
				$j_id = '';            
      if(isset($_REQUEST['from_dt']))
      $from_dt = $_REQUEST['from_dt'];
			else
				$from_dt = '';
      if(isset($_REQUEST['to_dt']))
      $to_dt = $_REQUEST['to_dt'];
      else
      $to_dt = '';
			
			if(isset($_REQUEST['length']))
				$length = $_REQUEST['length'];
			else
				$length = '10';
		?>
		var table
		var tFlag = true;
		$(document).ready(function() {
			var rid   = 2;
			table = $('#articles').DataTable({
				"pageLength" : <?php echo $length; ?>,
				"lengthMenu" : [[10, 25, 50, 100, 100000], [10, 25, 50, 100, "All"]],
       
				"displayStart":<?php echo $page; ?>,
				"sServerMethod": "POST", 
				"bProcessing": true,
				"bServerSide": true,
				"sAjaxSource": "get/json-issues.php",
				"sSortDir_0": "desc",
				
				"fnServerParams": function ( aoData ) {
					if(tFlag){
						aoData.push(
               { "name": "cust_id", "value": '<?php echo $cust_id; ?>'},
               { "name": "j_id", "value": '<?php echo $j_id; ?>'},
               { "name": "from_dt", "value": '<?php echo $from_dt; ?>'},
			         { "name": "to_dt", "value": '<?php echo $to_dt; ?>'},    
               { "name": "sSearch", "value": '<?php echo $search ?>'}
                );
						tFlag = false;
					}
          else
          {
            aoData.push(
			{ "name": "cust_id", "value": '<?php echo $cust_id; ?>'},
			{ "name": "j_id", "value": '<?php echo $j_id; ?>'},			
			{ "name": "from_dt", "value": '<?php echo $from_dt; ?>'},
			{ "name": "to_dt", "value": '<?php echo $to_dt; ?>'}
						);
          }
				},

       
				"aaSorting": [[0, 'desc']],
				"columns": [
					{ "data": "0" },
					{ "data": "1" },
					{ "data": "2" },
					{ "data": "3" },
					{ "data": "4" },
					{ "data": "5" },
					{ "data": "6" },
					{ "data": "7" },
					{ "data": "8" },
					{ "data": "9" },
					{ "data": "10" }
				],
        "initComplete": function(settings, json) {
        // Hide the loader once the DataTable is initialized
        $("#articles_processing").css("display", "none");
      },
				dom: "<'row'<'col-sm-2'f><'col-sm-10 voptions no_pad_l text-right'>>" +
					 "<'row'<'col-sm-12'tr>>" +
					 "<'row'<'col-sm-4'l><'col-sm-4 text-center'i><'col-sm-4'p>>",
				 "columnDefs": [					
					{ "orderable": false, "targets": [9, 10] }
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


			
			table.on('page.dt', function() {
			  $('html, body').animate({
				scrollTop: $(".dataTables_wrapper").offset().top-500
			  }, '1000');
			  $('thead tr th:first-child').focus().blur();
			});
			<?php
			if($search!='')
			{
			?>
			$('#articles').on( 'init.dt', function () {
				$('input[type=search]').val('<?php echo $search; ?>');
				table.search( '<?php echo $search; ?>' );
			});
			<?php
			}
			if($length!='')
			{
			?>
			$('#articles').on( 'init.dt', function () {
				table.page.len( <?php echo $length; ?> );
			});
			<?php
			}
			?>	
			$('#articles').on('click', 'button', function () {
     

				var del = $(this).attr('data-delete');
        swal({
		title: "Are you sure?",
	    text: "You will not be able to recover this record!",
         type: "warning",
		showCancelButton: true,
		confirmButtonColor: '#DD6B55',
		confirmButtonText: 'Proceed!',
		cancelButtonText: "No, cancel please!",
		closeOnConfirm: false,
		closeOnCancel: false
	},
	function(isConfirm){
    if (isConfirm){

      swal("Deleted!", "Loading Please wait", "success");
     // window.top.location="view_users.php?del="+del;
     window.top.location="articles?del="+del;

    } else {
      swal("Cancelled", "Article not Deleted", "error");
    }
	});
			});

      
		});	
		</script> 
    <script src="assets/js/select2/select2.min.js"></script> 
    <script>
      $(".customer_drop").select2({
        placeholder: "Select Customer",
      });
      $(".journal_drop").select2({
        placeholder: "Select Journal",
      });      
    </script> 
    
    <!-- Date picker functionality --> 
    <script>
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
function populateSelecte(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.j_code).val(item.j_id));
            });
        }

        
			
    </script>
    </body>
    </html>