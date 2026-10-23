<?php include "includes/includes.php";?>
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

  </head>

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
    
      <!-- Page body starts -->

      
      <div class="af-page" style="padding-bottom:0;">
          <div class="af-hero">
            <div><small>Author proofing</small><h1>ASP Article History</h1></div>
          </div>
        </div>
    
      <!-- Table section -->

       <div class="af-page"><div class="af-card"><div class="table-responsive">
       <table id="workflow" class="table table-striped custtable table-primary" style="text-wrap: nowrap; white-space: nowrap">
      
                                          <thead>
											<tr>
											<th>Loaded Date</th>
											<th>Original article name_number</th>
											<th>Journal</th>
											<th>Article title</th>
											<th>Corresponding Author</th>
											<th>Corresponding Author email</th>
											<th>Article no</th>
											<th>Proof pages</th>
											<th>Proof sent to authors</th>
											<th>AA's received</th>
											<th>Finalised in issue date</th>
											<th>General remarks notes</th>
											</tr>
										</thead>
				</table>

      </div></div></div>





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
			table = $('#workflow').DataTable({
				"pageLength" : <?php echo $length; ?>,
				"lengthMenu" : [[10, 25, 50, 100, 100000], [10, 25, 50, 100, "All"]],
       
				"displayStart":<?php echo $page; ?>,
				"sServerMethod": "POST", 
				"bProcessing": true,
				"bServerSide": true,
				"sAjaxSource": "get/json-asp-history-articles.php",
				"sSortDir_0": "desc",
				
                "fnServerParams": function ( aoData ) {
					if(tFlag){
						aoData.push( { "name": "sSearch", "value": '<?php echo $search ?>'} );
						tFlag = false;
					}
				},

       
				"aaSorting": [[3, 'desc']],
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
					{ "data": "10" },
					{ "data": "11" },

				],
        "initComplete": function(settings, json) {
        // Hide the loader once the DataTable is initialized
        $("#articles_processing").css("display", "none");
      },
				dom: "<'row'<'col-sm-2'f><'col-sm-10 voptions no_pad_l text-right'>>" +
					 "<'row'<'col-sm-12'tr>>" +
					 "<'row'<'col-sm-4'l><'col-sm-4 text-center'i><'col-sm-4'p>>",
				 "columnDefs": [
					//{ "orderable": false, "targets": 4 }
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
			$('#workflow').on( 'init.dt', function () {
				$('input[type=search]').val('<?php echo $search; ?>');
				table.search( '<?php echo $search; ?>' );
			});
			<?php
			}
			if($length!='')
			{
			?>
			$('#workflow').on( 'init.dt', function () {
				table.page.len( <?php echo $length; ?> );
			});
			<?php
			}
			?>	
			$('#workflow').on('click', 'button', function () {
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

      swal("Deleted!", "Loading Please wait", "success");
     // window.top.location="view_users.php?del="+del;
     window.top.location="asparticlehistory?del="+del;

    } else {
      swal("Cancelled", "Asp Article not Deleted", "error");
    }
	});
			});

      
		});	
		</script>
    
   
  
  <?php af_foot(); ?>
  </body>
</html>
