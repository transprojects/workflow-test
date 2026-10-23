<?php include "includes/includes.php";
session_start();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>	

    <!-- Data Table CSS- -->

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
if(isset($_REQUEST['stage']))
{
	  $stage=implode(",",$_REQUEST['stage']);
 	  $ex_stage = explode(",",$stage);
}
else
{
	$ex_stage='';
}
if(isset($_REQUEST['dept']))
{
	  $dept=implode(",",$_REQUEST['dept']);
 	  $ex_dept = explode(",",$dept);
}
else
{
	$ex_dept='';
}

if(isset($_REQUEST['article_id']))
{
	  $article_id=$_REQUEST['article_id'];
}
else
{
	$article_id='';
}
if(isset($_REQUEST['graphics_status']))
{
	  $graphics_status=implode(",",$_REQUEST['graphics_status']);
 	  $ex_graphics_status = explode(",",$graphics_status);
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
</style>   
      <div class="container-fluid" style="justify-content: space-between;">
	  
	   <?php
  if(!empty($_REQUEST['msg']))  {
    echo '<div id="successMessage" class="alert alert-success">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      '. $_REQUEST['msg'] .'</div>';   
  }
  ?> 
        <div div class="row pt-4 d-flex align-items-center flex-wrap">
          <div class="col-8 col-lg-10">
            <p class="articleheading ms-4">Article</p>
          </div>
          <div class="col-4 col-lg-2">
            <div class="row">
              <div class="col-3 articlebtns d-flex justify-content-end">
                <a href="<?php echo $_COOKIE['set_url'];?>/add-article">
                  <button type="button">+ Add Manual</button>
                </a>
              </div>
              <!--<div class="col-2 articlebtns">
                <a href="<?php echo $_COOKIE['set_url'];?>/add-article">
                  <button type="button">+ Add XML</button>
                </a>
              </div>-->
            </div>
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
                </select>
              </div>
            <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                <label class="mb-2">Choose Journal</label>
                <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_id[]" id="j_id"  multiple="multiple">
												    <option value="all">Select All</option>

                    <?php
											 if($_COOKIE['user_level']=='3')
										  {
											$sql_journals = $auth->showJournallistbyuser($_COOKIE['cust_id'],$_COOKIE['ocust_id']);
										  }
										  else
										  {
											$sql_journals = $auth->showJournallist('0',2);
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
                <label class="mb-2">Choose Department</label>
                <select class="form-select department_drop" data-placeholder="Select Department" name="dept[]" id="dept" multiple="multiple">
					<option value="all">Select All</option>
                      <?php 
					  	$sql_department = $auth->getAlldepartment('0',2);

					  foreach($sql_department as $res_department)
											{
												
									      	if(isset($_REQUEST['dept']))
													{
										    	?>
										       <option <?php if (in_array($res_department['dept_code'], $ex_dept)){?>selected<?php }?> value="<?php echo $res_department['dept_code'] ?>"style="font-weight:500;"><?php echo $res_department['dept_name'] ?> </option>
													<?php
													}
													else
													{
													?>
													 <option value="<?php echo $res_department['dept_code'] ?>"style="font-weight:500;"><?php echo $res_department['dept_name'] ?></option>
													<?php
													}
											}
											?>
                </select>
              </div>
             <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                <label class="mb-2">Choose Graphics Stage</label>
                <select class="form-select graphics_status_drop" data-placeholder="Select Graphics Stage" name="graphics_status[]" id="graphics_status" multiple="multiple">
								    <option value="all">Select All</option>
				 <?php 
                          if(isset($_REQUEST['graphics_status']))
													{
                          ?>
                    <option value="1" <?php if (in_array('1', $ex_graphics_status)){?>selected<?php }?>>-Yes-</option>
                    <option value="0" <?php if (in_array('0', $ex_graphics_status)){?>selected<?php }?>>-No-</option>
					<?php 
                          }
                          else
                          {
                          ?>
                          <option value="1" >-Yes-</option>
						  <option value="0" >-No-</option>

                          <?php 
                          }
                          ?>
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
              <input class="form-control" name="article_id" id="article_id" placeholder="Enter Article ID" value="<?php echo $article_id;?>">
              </input>
             </div> 

             <div class="col-12 mt-5 d-flex justify-content-center align-items-center">
              <button type="submit" name="btn-wip" class="resultbtn">Get Result</button>		  
              <a class="refreshbtn ms-4" href="articles"><img src="./assets/images/refresh.svg"></a>
             </div>
             </div>
              </form>
              </div>

      <!-- Table section -->

       <div class="container-fluid  custom-container p-4" style="box-shadow: 0px 0px 16px 0px rgba(164, 164, 171, 0.25);border-radius: 24px;">
	   <div class="container-fluid table-responsive" style="overflow: auto">
	   
       <table id="articles" class="table custtable table-primary" style="width: 100% !important; overflow:auto !important;text-wrap: nowrap !important;white-space: nowrap !important">
		<thead>
        <tr>
              <th data-sortable="true" scope="col" class="thcolor" >Action</th>
              <th data-sortable="true" scope="col" class="thcolor" >Client</th>
              <th data-sortable="true" scope="col" class="thcolor">Journal Name</th>
              <th data-sortable="true" scope="col" class="thcolor">File Name</th>
              <th data-sortable="true" scope="col" class="thcolor">Stage</th>
              <th data-sortable="true" scope="col" class="thcolor">Received Date</th>
              <th data-sortable="true" scope="col" class="thcolor">Due Date</th>
              <th data-sortable="true" scope="col" class="thcolor">Status</th>
              <th data-sortable="true" scope="col" class="thcolor">Page Count</th>
              <th class="thcolor">Instruction</th>
              <th class="thcolor">Startwork</th>
    	</tr>
		</thead>
		</table>
		
		</div>
      	</div>



  
  <!-- Modal -->
 

  <!-- Button trigger modal -->
<!-- <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
  Launch demo modal
</button> -->

<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Comments/Instructions</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
      <p id="ppopup"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <!-- <button type="button" class="btn btn-primary">Save changes</button> -->
      </div>
    </div>
  </div>
</div>


     <!-- Footer Section -->
     <?php echo footer(); ?>




<!-- Footer Section -->
<?php echo scripts(); ?>	

   
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
			if(isset($_REQUEST['dept']))
			 {
$dept = $_REQUEST['dept'];
$dept = array_filter($dept, function($value) {
    return $value !== "all";
});
$dept = implode(",", $dept);
			 }

			else
			{
				$dept = '';
			}
			 if(isset($_REQUEST['graphics_status']))
			 {
$graphics_status = $_REQUEST['graphics_status'];
$graphics_status = array_filter($graphics_status, function($value) {
    return $value !== "all";
});
$graphics_status = implode(",", $graphics_status);
			 }

			else
			{
				$graphics_status = '';
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
			table = $('#articles').DataTable({
				
				"pageLength" : <?php echo $length; ?>,
				"lengthMenu" : [[10, 25, 50, 100, 100000], [10, 25, 50, 100, "All"]],
       
				"displayStart":<?php echo $page; ?>,
				"sServerMethod": "POST", 
				"bProcessing": true,
				"bServerSide": true,
				"sAjaxSource": "get/json-all-articles.php",
				"sSortDir_0": "desc",
				
				
				
				
				"fnServerParams": function ( aoData ) {
					if(tFlag){
						aoData.push(
               { "name": "cust_id", "value": '<?php echo $cust_id; ?>'},
               { "name": "j_id", "value": '<?php echo $j_id; ?>'},
			   { "name": "stage", "value": '<?php echo $stage; ?>'},
			   { "name": "dept", "value": '<?php echo $dept; ?>'},
			   { "name": "graphics_status", "value": '<?php echo $graphics_status; ?>'},
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
							 { "name": "dept", "value": '<?php echo $dept; ?>'},
							 { "name": "graphics_status", "value": '<?php echo $graphics_status; ?>'},
							 { "name": "article_id", "value": '<?php echo $article_id ?>'},
							 { "name": "from_dt", "value": '<?php echo $from_dt; ?>'},
							 { "name": "to_dt", "value": '<?php echo $to_dt; ?>'}
						);
          }
				},
				
				

       
				//"aaSorting": [[4, 'asc']],
			"aaSorting": [
                // Sort by column1 with custom values
                [10, 'desc', '1'],
                [6, 'asc'],
                //[0, 'asc', 'value3'],
                // Then, sort by column2 in ascending order
               // [1, 'asc']
                // Add more sorting rules if you have more columns
            ],
			
			"fnSortData": function (aoData, iColumn, bDescending, oSettings) {
                if (iColumn == 0) {
                    // Custom sorting for Column1 based on values
                    return aoData.map(function (data, i) {
                        return { 'value': data[iColumn], 'index': i };
                    });
                }else if (iColumn == 1) {
                    // Custom sorting for Column1 based on values
                    return aoData.map(function (data, i) {
                        return { 'value': data[iColumn], 'index': i };
                    });
                } else {
                    // Default sorting for other columns
                    return oSettings.oApi.fnSortData(aoData, iColumn, bDescending, oSettings);
                }
            },
			
			 "fnRowCallback": function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {
      if (aData[12] == "1") {
        $('td', nRow).css('background-color', '#d9edf7');
		
      }
	  else if (aData[12] == "0") {
        $('td', nRow).css('background-color', '#fff');
		
      }
	  if (aData[13] == "1") {
        $('td', nRow).css('background-color', 'rgb(255, 0, 0,.5)');
		
      }
    },
				 
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
					{"data": "10"}
				], 
        "initComplete": function(settings, json) {
        // Hide the loader once the DataTable is initialized
        $("#articles_processing").css("display", "none");
      },
				dom: "<'row'<'col-sm-2'f><'col-sm-10 voptions no_pad_l text-right'>>" +
					 "<'row'<'col-sm-12'tr>>" +
					 "<'row'<'col-sm-4'l><'col-sm-4 text-center'i><'col-sm-4'p>>",
				 "columnDefs": [
					// 		{
					// 	 "targets": -1,
					// 	"data": null,
					// 	'render': function (data, type, row) {
					// 	return '<button data-delete="' + row[10] +'" class="btn btn-danger demo4   delete-article" type="button"><i class="ace-icon fa fa-trash-o bigger-120"></i></button>'

					// 	}
					// },
				
					{ "orderable": false, "targets": 0 },
					{ "orderable": false, "targets": 7 },
					{ "orderable": false, "targets": 9 },
					{ "orderable": false, "targets": 10 }
					//{ "visible": false, "targets": 12 }
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
     //$(".delete-article").on("click", function() {

				var del = $(this).attr('data-delete');
        swal({
		title: "Are you sure?",
	    text: "Your will not be able to recover this!",
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


        function check_graphics_work_status(id,tid,stage)
			{	
			
				$.ajax(
				{
					type:'POST',
					url:'get_article_graphics_work_status.php',
					data:{id:id,stage:stage},
					success:function(result)
					{
						
						if(result == 0)
						{
							window.location = 'project_fig_work.php?id='+id;					
							return true;
						}
						else
						{
							if(result == 'Please complete all the Inprogress Articles before you start this work')
							{
								bootbox.dialog(
								{
									message: '<br/><div class="alert alert-danger"> <strong> In Progress! </strong>'+ result +'</div>',
									buttons:
							{
								"button" :
								{
									"label" : "Close",
									"className" : "btn-sm",
									"callback": function() {
										//window.location.reload();				
										return true;
									}
								}							
							} 						
								});					
								return false;
							}
							else if(result == 'Please complete all the Pending Articles before you start this work')
							{
								bootbox.dialog(
								{
									message: '<br/><div class="alert alert-danger"> <strong> In Progress! </strong>'+ result +'</div>',
									buttons:
							{
								"button" :
								{
									"label" : "Close",
									"className" : "btn-sm",
									"callback": function() {
										//window.location.reload();				
										return true;
									}
								}							
							} 						
								});					
								return false;
							}
							else
							{
								bootbox.dialog(
								{
									message: '<br/><div class="alert alert-danger"> <strong> In Progress! </strong>'+ result +'</div>',
									buttons:
							{
								
								"danger" :
								{
									"label" : "Take Over!",
									"className" : "btn-sm btn-danger",
									"callback": function() {
										
										window.location = 'project_fig_work.php?id='+id+'&tid='+tid;					
										return true;
									}
								}, 
								"button" :
								{
									"label" : "Close",
									"className" : "btn-sm",
									"callback": function() {
									//	window.location.reload();				
										return true;
									}
								}							
							} 						
								});					
								return false;
							}
						}
					}
				});
			}	
			
			function check_work_status(id,tid,stage)
			{	
			
				$.ajax(
				{
					type:'POST',
					url:'get_article_work_status.php',
					data:{id:id,stage:stage},
					success:function(result)
					{
						
						if(result == 0)
						{
						//	window.location = 'project_work.php?id='+id;		
              //window.location ='<?php echo $_COOKIE['set_url'];?>/startwork/'+id;		
             var wid='new';
              window.location ='<?php echo $_COOKIE['set_url'];?>/startwork/'+id+'/'+wid;					
		
			
							return true;
						}
						else
						{
							if(result == 'Please complete all the Inprogress Articles before you start this work')
							{
								bootbox.dialog(
								{
									message: '<br/><div class="alert alert-danger"> <strong> In Progress! </strong>'+ result +'</div>',
									buttons:
							{
								"button" :
								{
									"label" : "Close",
									"className" : "btn-sm",
									"callback": function() {
										//window.location.reload();				
										return true;
									}
								}							
							} 						
								});					
								return false;
							}
							else if(result == 'Please complete all the Pending Articles before you start this work')
							{
								bootbox.dialog(
								{
									message: '<br/><div class="alert alert-danger"> <strong> In Progress! </strong>'+ result +'</div>',
									buttons:
							{
								"button" :
								{
									"label" : "Close",
									"className" : "btn-sm",
									"callback": function() {
									//	window.location.reload();				
										return true;
									}
								}							
							} 						
								});					
								return false;
							}
							else
							{
								bootbox.dialog(
								{
									message: '<br/><div class="alert alert-danger"> <strong> In Progress! </strong>'+ result +'</div>',
									buttons:
							{
								
								"danger" :
								{
									"label" : "Take Over!",
									"className" : "btn-sm btn-danger",
									"callback": function() {
										
                    //$_COOKIE['set_url'].'/startwork/'.$articles['id'].';
										window.location ='<?php echo $_COOKIE['set_url'];?>/startwork/'+id+'/'+tid;					
										return true;
									}
								}, 
								"button" :
								{
									"label" : "Close",
									"className" : "btn-sm",
									"callback": function() {
										//window.location.reload();				
										return true;
									}
								}							
							} 						
								});					
								return false;
							}
						}
					}
				});
			}	
			
			function timepopup(id)
			{		
       // alert('yrdyd');		
				var url = "<?php echo $_COOKIE['set_url'];?>/project_comments.php?id="+id;
				$("#ppopup").load(url);
			}	
    </script>
	
	<script>
  setTimeout(function() {
    var successMessage = document.getElementById('successMessage');
    if (successMessage) {
      successMessage.classList.add('hide');
    }
  }, 5000); // Hide after 5 seconds (5000 milliseconds)
</script>
  </body>
</html>
