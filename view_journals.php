<?php include "includes/includes.php";
include_once 'includes/list_ui.php';
$id = isset($_REQUEST['del']) ? (int)$_REQUEST['del'] : 0;   /* used by the dependency checks below */
lu_delete($db, 'adm_journals', 'j_id', 'Journal', array(array("SELECT COUNT(*) FROM inw_inward_dtl WHERE j_id = $id", 'article(s)'), array("SELECT COUNT(*) FROM workflow_archive.inw_inward_dtl WHERE j_id = $id", 'archived article(s)'), array("SELECT COUNT(*) FROM inw_volume_issues WHERE j_id = $id", 'issue(s)')), 'managejournals');
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
            <p class="articleheading ms-md-4">Journals</p>
          </div>
          <div class="col-12 col-sm-6 col-md-2 mt-5 mt-sm-0">
            <div class="row d-flex justify-content-sm-end pe-2">
              <div class="col-3 articlebtns me-3">
                <a href="<?php echo $_COOKIE['set_url'];?>/addjournal">
                  <button type="button">+ Add Journals</button>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

<div class="divouter mt-4">
</div>
    
      <!-- Table section -->

       <?php lu_open('Journals', 'Journal &rsaquo; All', $_COOKIE['set_url'].'/addjournal', '+ Add Journal'); ?>
       <div class="table-responsive custom-container">
       <table id="journals" class="table table-striped custtable" style="text-wrap: nowrap;white-space: nowrap !important">
		 <thead>
		 <tr>
		 <th>Customer Name</th>
		 <th>Journal</th>
		 <th>Code</th>
		 <th>FP WF</th>
		 <th>REV WF</th>
		 <th>FIN WF</th>
		 <th>ISSUE WF</th>
		 <th>JSTAGE WF</th>
		 <th>PMC WF</th>
		 <th> <i class="ace-icon fa fa-clock-o bigger-110 hidden-480"></i>  Created On</th>
         <th>Action</th>
		 </tr>
		 <?php lu_filter_row(array('Customer','Journal','Code','FP workflow','Revise workflow','Final workflow','Issue workflow','JSTAGE workflow','PMC workflow','yyyy-mm-dd', null)); ?>
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
			table = $('#journals').DataTable({
				"pageLength" : <?php echo $length; ?>,
				"orderCellsTop": true,
				"lengthMenu" : [[10, 25, 50, 100, 100000], [10, 25, 50, 100, "All"]],
       
				"displayStart":<?php echo $page; ?>,
				"sServerMethod": "POST", 
				"bProcessing": true,
				"bServerSide": true,
				"sAjaxSource": "get/json-journals.php",
				"sSortDir_0": "desc",
				
                "fnServerParams": function ( aoData ) {
					luFilterParams('#journals', aoData);
					if(tFlag){
						aoData.push( { "name": "sSearch", "value": '<?php echo $search ?>'} );
						tFlag = false;
					}
				},

       
				"aaSorting": [[9, 'desc']],
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
					{ "orderable": false, "targets": 10 }
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


			
			luBindFilters(table, '#journals');
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
			$('#journals').on( 'init.dt', function () {
				$('input[type=search]').val('<?php echo $search; ?>');
				table.search( '<?php echo $search; ?>' );
			});
			<?php
			}
			if($length!='')
			{
			?>
			$('#journals').on( 'init.dt', function () {
				table.page.len( <?php echo $length; ?> );
			});
			<?php
			}
			?>	
			$('#journals').on('click', 'button[data-delete]', function () {
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
     window.top.location="managejournals?del="+del;

    } else {
      swal("Cancelled", "Journal not Deleted", "error");
    }
	});
			});

      
		});	
		</script>
    
   
  
  </body>
</html>
