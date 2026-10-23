<?php include "includes/includes.php";
include_once 'includes/list_ui.php';
include_once "includes/change_log_mail.php";?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php lu_css(); ?>	
<style>
	.message-content {
    max-width: 300px;         /* or any value based on your table */
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: inline-block;
}
	</style>
    <!-- Data Table CSS- -->

  </head>
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
if(isset($_REQUEST['stage']))
{
	  $stage=implode(",",$_REQUEST['stage']);
 	  $ex_stage = explode(",",$stage);
}
else
{
	$ex_stage='';
}


if(isset($_REQUEST['article_id']))
{
	  $article_id=$_REQUEST['article_id'];
}
else
{
	$article_id='';
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
  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
    <?php 
		if(isset($_REQUEST['del']))
{	
	// Snapshot before delete (for change-log email)
	$__old = change_log_snapshot('inw_internal_feedback', 'f_id', $_REQUEST['del']);
	$db->query("delete from inw_internal_feedback where f_id='".$_REQUEST['del']."'");
	log_row_deleted('Internal Feedback', $_REQUEST['del'], $__old);
	header("location:".$_COOKIE['set_url']."/internalfeedback?msg=Successfully deleted");
	die();	
	
}
?>
      <!-- Page body starts -->

      
      <?php lu_hero('Internal Feedback', 'Internal Feedback &rsaquo; All', $_COOKIE['set_url'].'/addinternalfeedback', '+ Add Feedback'); ?>
<div class="container-fluid" style="justify-content: space-between;">
        <div div class="row pt-4 d-flex align-items-center flex-wrap">
          <div class="col-12 col-sm-6 col-md-10">
		   <?php 
				  if($_COOKIE['user_level']==1 || $_COOKIE['user_level']==14 || $_COOKIE['user_level']==5 || $_COOKIE['user_level']==7)
				  {
				  ?>
				<p class="articleheading ms-md-4">Internal Feedback</p>
				  <?php 
				  }
				  else
				{
				  ?>
				<p class="articleheading ms-md-4">My Feedback</p>
				  <?php 
				  }
				  ?>
          </div>
		   <?php 
				  if($_COOKIE['user_level']==1 || $_COOKIE['user_level']==14 || $_COOKIE['user_level']==5 || $_COOKIE['user_level']==7)
				  {
				  ?>
          <div class="col-12 col-sm-6 col-md-2 mt-5 mt-sm-0">
            <div class="row d-flex justify-content-sm-end pe-2">
              <div class="col-3 articlebtns me-3">
                <a href="<?php echo $_COOKIE['set_url'];?>/addinternalfeedback">
                  <button type="button">+ Add Feedback</button>
                </a>
              </div>
            </div>
			<?php 
				  }
				  ?>
          </div>
        </div>
      </div>
<div class="divouter mt-4">
   <form action="" name="articles" class="formouter" method="post">
          <div class="selectdiv row p-4 d-flex gap-2">
            <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                <label class="mb-2">Choose Customer</label>
                <select class="form-select customer_drop" data-placeholder="Select Customer" name="cust_id[]" id="cust_id"  multiple="multiple">
												    <option value="all">Select All</option>

										  <?php
										  if($_COOKIE['user_level']=='1')
										  {
											$sql_customers = $auth->showCustomerlist();
										  }
										  else
										  {
											$sql_customers = $auth->showCustomerlistbyuser($_COOKIE['cust_id'],$_COOKIE['ocust_id']);
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
                </select>
              </div>
            <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                <label class="mb-2">Choose Journal</label>
                <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_id[]" id="j_id"  multiple="multiple">
												    <option value="all">Select All</option>

                    <?php
										  if($_COOKIE['user_level']=='1')
										  {
											$sql_journals = $auth->showJournallist('0',2);
										  }
										  else
										  {
											$sql_journals = $auth->showJournallistbyuser($_COOKIE['cust_id'],$_COOKIE['ocust_id']);
										  }
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
             <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                <label class="mb-2">Choose Stage</label>
                <select class="form-select stage_drop" data-placeholder="Select Stage" name="stage[]" id="stage" multiple="multiple">
												    <option value="all">Select All</option>

                  <?php foreach($project_status_types as $id=>$val){?>
                          <?php 
                          if(isset($_REQUEST['stage']))
													{
                          ?>
                         <option value="<?php echo $id; ?>" <?php if (in_array($id, $ex_stage)){?>selected<?php }?> ><?php echo $val; ?></option>
                         <?php 
                          }
                          else
                          {
                          ?>
                         <option value="<?php echo $id; ?>" ><?php echo $val; ?></option>

                          <?php 
                          }
                          ?>
                   <?php } ?>
                </select>
              </div>
			    
            
             <div class="col-12 col-sm-6 col-md-3 col-lg-2">
             <label class="mb-2">From Date</label>
                <div class="d-flex">
                  <input type="text"class="form-control monthlyinputdate" name="from_dt" placeholder="From Date" value="<?php echo $from_dt;?>" readonly/>
                </div>
             </div>
             <div class="col-12 col-sm-6 col-md-3 col-lg-2">
               <label class="mb-2">To Date</label>
                <div class="d-flex">
                  <input type="text" class="form-control monthlyinputdate1" name="to_dt" placeholder="To Date" value="<?php echo $to_dt;?>" readonly/>
                </div>
             </div>
             <div class="col-12 col-sm-6 col-md-3 col-lg-2">
              <label class="mb-2">Article ID</label>
              <input class="form-control" name="article_id" id="article_id" placeholder="Enter Article ID" value="<?php echo $_REQUEST['article_id'];?>">
              </input>
             </div> 

             <div class="col-12 mt-5 d-flex justify-content-center align-items-center">
              <button type="submit" name="btn-wip" class="resultbtn">Get Result</button>		  
              <a class="refreshbtn ms-4" href="internalfeedback"><img src="./assets/images/refresh.svg"></a>
             </div>
             </div>
              </form>
</div>
    
    
      <!-- Table section -->

       <?php lu_card_open(); ?>
       <div class="table-responsive custom-container">
       <table id="internal-feedback" class="table table-striped custtable table-primary" style="text-wrap: nowrap;white-space: nowrap">
      
                                          <thead>
											<tr>
                                                <th>Journal Name</th>
												<th>Stage</th>
												<th>Article</th>
												<th>Comments</th>
												<th>Action Plan</th>
												<th>Received Date</th>
												<th>User</th>
                     						    <th>Action</th>
											</tr>
										</thead>
				</table>

      </div>
<?php lu_close(); ?>





     <!-- Footer Section -->
     <?php echo footer(); ?>




<!-- Footer Section -->
<?php echo scripts(); ?>	
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
	
	  <!-- Date picker functionality -->
    <script>
      $(function () {
        // var datesForDisable = ["2017-10-14", "2017-10-15", "2017-10-16"];
        var date = new Date();
        date.setDate(date.getDate() - 1);
        var fromdate;
        $(".monthlyinputdate").datepicker({
          uiLibrary: "bootstrap5",
           dateFormat: "dd-mm-yy",
          autoclose: true,
         //  minDate: date,
              onSelect: function (dateText, inst) {
             fromdate = dateText;

                    $(".monthlyinputdate1").datepicker("option", "minDate", fromdate);
                }
        });

        $(".monthlyinputdate1").datepicker({
          uiLibrary: "bootstrap5",
           dateFormat: "dd-mm-yy",
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
	
		if($(this).val() && $(this).val().indexOf('all') !== -1){
            // Clear all other selections
            $('#cust_id').val([]);
            // Select all options except the "Select All" option
            $('#cust_id option').not(':first').prop('selected', true);
        } else {
            // If any other option is selected, unselect "Select All"
            if ($(this).val() && $(this).val().length === $('option', this).not(':first').length) {
                $('#cust_id option[value=all]').prop('selected', false);
            }
        }

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
	 
	  $("#j_id").on("change", function() {
         var j_id=$.trim($(this).val());
		 
		if($(this).val() && $(this).val().indexOf('all') !== -1){
            // Clear all other selections
            $('#j_id').val([]);
            // Select all options except the "Select All" option
            $('#j_id option').not(':first').prop('selected', true);
        } else {
            // If any other option is selected, unselect "Select All"
            if ($(this).val() && $(this).val().length === $('option', this).not(':first').length) {
                $('#j_id option[value=all]').prop('selected', false);
            }
        }
        
     });
	 
	 	 $("#graphics_status").on("change", function() {
         var graphics_status=$.trim($(this).val());
		 
		if($(this).val() && $(this).val().indexOf('all') !== -1){
            // Clear all other selections
            $('#graphics_status').val([]);
            // Select all options except the "Select All" option
            $('#graphics_status option').not(':first').prop('selected', true);
        } else {
            // If any other option is selected, unselect "Select All"
            if ($(this).val() && $(this).val().length === $('option', this).not(':first').length) {
                $('#graphics_status option[value=all]').prop('selected', false);
            }
        }
        
     });
	 
	 	 	 $("#stage").on("change", function() {
         var stage=$.trim($(this).val());
		 
		if($(this).val() && $(this).val().indexOf('all') !== -1){
            // Clear all other selections
            $('#stage').val([]);
            // Select all options except the "Select All" option
            $('#stage option').not(':first').prop('selected', true);
        } else {
            // If any other option is selected, unselect "Select All"
            if ($(this).val() && $(this).val().length === $('option', this).not(':first').length) {
                $('#stage option[value=all]').prop('selected', false);
            }
        }
        
     });
	 
	 	  $("#dept").on("change", function() {
         var dept=$.trim($(this).val());
		 
		if($(this).val() && $(this).val().indexOf('all') !== -1){
            // Clear all other selections
            $('#dept').val([]);
            // Select all options except the "Select All" option
            $('#dept option').not(':first').prop('selected', true);
        } else {
            // If any other option is selected, unselect "Select All"
            if ($(this).val() && $(this).val().length === $('option', this).not(':first').length) {
                $('#dept option[value=all]').prop('selected', false);
            }
        }
        
     });

});
function populateSelecte(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();
			selectElement.append($("<option>").text("Select All").val("all"));

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.j_code).val(item.j_id));
            });
        }
</script>
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
       if (isset($_REQUEST['cust_id']))
				$cust_id = implode(",", $_REQUEST['cust_id']);
			else if (isset($_COOKIE['user_level']) && ($_COOKIE['user_level'] == 1 || $_COOKIE['user_level'] == 14 ))
				$cust_id = '';
			else
				$cust_id = '';
      if(isset($_REQUEST['j_id']))
				 $j_id = implode(",",$_REQUEST['j_id']);
			else
				$j_id = '';
     if(isset($_REQUEST['stage']))
			 {
$stage = $_REQUEST['stage'];
$stage = array_filter($stage, function($value) {
    return $value !== "all";
});
$stage = implode(",", $stage);
			 }

			else
			{
				$stage = '';
			}
			
			
      if(isset($_REQUEST['article_id']))
				$article_id = $_REQUEST['article_id'];
			else
				$article_id = '';
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
			table = $('#internal-feedback').DataTable({
				"pageLength" : <?php echo $length; ?>,
				"lengthMenu" : [[10, 25, 50, 100, 100000], [10, 25, 50, 100, "All"]],
       
				"displayStart":<?php echo $page; ?>,
				"sServerMethod": "POST", 
				"bProcessing": true,
				"bServerSide": true,
				"sAjaxSource": "get/json-internal-feedback.php",
				"sSortDir_0": "desc",
				
				"fnServerParams": function ( aoData ) {
					if(tFlag){
						aoData.push(
               { "name": "cust_id", "value": '<?php echo $cust_id; ?>'},
               { "name": "j_id", "value": '<?php echo $j_id; ?>'},
			   { "name": "stage", "value": '<?php echo $stage; ?>'},
			   { "name": "article_id", "value": '<?php echo $article_id ?>'},
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
							 { "name": "stage", "value": '<?php echo $stage; ?>'},
							 { "name": "article_id", "value": '<?php echo $article_id ?>'},
							 { "name": "from_dt", "value": '<?php echo $from_dt; ?>'},
							 { "name": "to_dt", "value": '<?php echo $to_dt; ?>'}
						);
          }
				},
				
       
				"aaSorting": [[4, 'desc']],
				"columns": [
					{ "data": "0" },
					{ "data": "1" },
					{ "data": "2" },
					{ "data": "3" },
					{ "data": "4" },
					{ "data": "5" },
					{ "data": "6" },
					{ "data": "7" }
				],
        "initComplete": function(settings, json) {
        // Hide the loader once the DataTable is initialized
        $("#articles_processing").css("display", "none");
      },
				dom: "<'row'<'col-sm-2'f><'col-sm-10 voptions no_pad_l text-right'>>" +
					 "<'row'<'col-sm-12'tr>>" +
					 "<'row'<'col-sm-4'l><'col-sm-4 text-center'i><'col-sm-4'p>>",
				 "columnDefs": [
					{ "orderable": false, "targets": 7 }
					//{ "orderable": false, "targets": 9 }
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


			
			table.on('xhr.dt', function (e, s, json) { if (json) { $('#lu-count').text(json.iTotalDisplayRecords); } });

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
			$('#internal-feedback').on( 'init.dt', function () {
				$('input[type=search]').val('<?php echo $search; ?>');
				table.search( '<?php echo $search; ?>' );
			});
			<?php
			}
			if($length!='')
			{
			?>
			$('#internal-feedback').on( 'init.dt', function () {
				table.page.len( <?php echo $length; ?> );
			});
			<?php
			}
			?>	
			$('#internal-feedback').on('click', 'button[data-delete]', function () {
     //$(".delete-article").on("click", function() {

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

      swal({title: "Deleting...", text: "Please wait", type: "info", showConfirmButton: false});
     // window.top.location="internalfeedback?del="+del;
     window.top.location="internalfeedback?del="+del;

    } else {
      swal("Cancelled", "Internal Feedback not Deleted", "error");
    }
	});
			});

      
		});	
		</script>
    
   
  
  </body>
</html>
