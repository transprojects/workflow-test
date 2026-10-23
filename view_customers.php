<?php include "includes/includes.php";
include_once 'includes/list_ui.php';
$id = isset($_REQUEST['del']) ? (int)$_REQUEST['del'] : 0;   /* used by the dependency checks below */
lu_delete($db, 'adm_customer_master', 'id', 'Customer', array(array("SELECT COUNT(*) FROM adm_journals WHERE j_cust_id = $id", 'journal(s)'), array("SELECT COUNT(*) FROM inw_inward_dtl WHERE cust_id = $id", 'article(s)')), 'customers');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>	
    <?php lu_css(); ?>

    <!-- Data Table CSS- -->

  </head>

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
    
      <!-- Page body starts -->

      
      <div class="container-fluid" style="justify-content: space-between;">
        <div div class="row pt-4 d-flex align-items-center flex-wrap">
          <div class="col-12 col-sm-6 col-md-10">
            <p class="articleheading ms-md-4">Customer Information</p>
          </div>
          <div class="col-12 col-sm-6 col-md-2 mt-5 mt-sm-0">
            <div class="row d-flex justify-content-sm-end pe-2">
              <div class="col-3 articlebtns me-3">
                <a href="<?php echo $_COOKIE['set_url'];?>/addcustomer">
                  <button type="button">+ Add Customer</button>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
<div class="divouter mt-4">
</div>
    
      <!-- Table section -->

       <?php lu_open('Customers', 'Customer &rsaquo; All', $_COOKIE['set_url'].'/addcustomer', '+ Add Customer'); ?>
       <div class="table-responsive custom-container">
       <table id="customers" class="table table-striped custtable" style="text-wrap: nowrap !important; white-space: nowrap !important">
      
                                          <thead>
											<tr>
												<th>Customer Name</th>
												 <th>Email ID</th>
												 <th>Created On</th>
                                                <th>Action</th>
											</tr>
										<?php lu_filter_row(array('Customer','E-mail','yyyy-mm-dd', null)); ?>
		 </thead>
				</table>

      </div>
<?php lu_close(); lu_filter_js(); ?>



  
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
			if(isset($_REQUEST['page']))
				$page = $_REQUEST['page']*10;
			else
				$page = 0;
			
			if(isset($_REQUEST['search']))
				$search = $_REQUEST['search'];
			else
				$search = '';
			
			if(isset($_REQUEST['length']))
				$length = $_REQUEST['length'];
			else
				$length = '10';
		?>
		var table
		var tFlag = true;
		$(document).ready(function() {
			var rid   = 2;
			table = $('#customers').DataTable({
				"pageLength" : <?php echo $length; ?>,
				"orderCellsTop": true,
				"lengthMenu" : [[10, 25, 50, 100, 100000], [10, 25, 50, 100, "All"]],
       
				"displayStart":<?php echo $page; ?>,
				"sServerMethod": "POST", 
				"bProcessing": true,
				"bServerSide": true,
				"sAjaxSource": "get/json-customers.php",
				"sSortDir_0": "desc",
				
                "fnServerParams": function ( aoData ) {
					luFilterParams('#customers', aoData);
					if(tFlag){
						aoData.push( { "name": "sSearch", "value": '<?php echo $search ?>'} );
						tFlag = false;
					}
				},

       
				"aaSorting": [[0, 'desc']],
				"columns": [
					{ "data": "0" },
					{ "data": "1" },
					{ "data": "2" },
					{ "data": "3" }

				],
        "initComplete": function(settings, json) {
        // Hide the loader once the DataTable is initialized
        $("#articles_processing").css("display", "none");
      },
				dom: "<'row'<'col-sm-2'f><'col-sm-10 voptions no_pad_l text-right'>>" +
					 "<'row'<'col-sm-12'tr>>" +
					 "<'row'<'col-sm-4'l><'col-sm-4 text-center'i><'col-sm-4'p>>",
				 "columnDefs": [
					{ "orderable": false, "targets": 3 }
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


			
			luBindFilters(table, '#customers');
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
			$('#customers').on( 'init.dt', function () {
				$('input[type=search]').val('<?php echo $search; ?>');
				table.search( '<?php echo $search; ?>' );
			});
			<?php
			}
			if($length!='')
			{
			?>
			$('#customers').on( 'init.dt', function () {
				table.page.len( <?php echo $length; ?> );
			});
			<?php
			}
			?>	
			$('#customers').on('click', 'button[data-delete]', function () {
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
     // window.top.location="view_users.php?del="+del;
     window.top.location="customers?del="+del;

    } else {
      swal("Cancelled", "Article not Deleted", "error");
    }
	});
			});

      
		});	
		</script>
    
   
  
  </body>
</html>
