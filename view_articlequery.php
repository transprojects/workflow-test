<?php include "includes/includes.php";
include_once 'includes/list_ui.php';
$id = isset($_REQUEST['del']) ? (int)$_REQUEST['del'] : 0;   /* used by the dependency checks */
lu_delete($db, 'tbl_query_client', 'qid', 'Article query', array(), 'articlequery');
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

    <style>
      /* clear sort arrows (the stock arrow images are tiny, pale and overlap the header text) */
      #article-query thead th.sorting,#article-query thead th.sorting_asc,#article-query thead th.sorting_desc{position:relative;padding-right:28px !important;cursor:pointer;background-image:none !important;}
      #article-query thead th.sorting:after,#article-query thead th.sorting_asc:after,#article-query thead th.sorting_desc:after{font-family:FontAwesome;font-weight:normal;font-size:12px;line-height:14px;position:absolute;right:9px;top:50%;margin-top:-5px;transition:color .15s,transform .15s;}
      #article-query thead th.sorting:after{content:"\f0dc";color:#9aa8b8;}
      #article-query thead th.sorting_asc:after{content:"\f0de";color:#e8742a;margin-top:-3px;}
      #article-query thead th.sorting_desc:after{content:"\f0dd";color:#e8742a;margin-top:-8px;}
      #article-query thead th.sorting:hover:after{color:#1b6ca8;transform:scale(1.15);}
      #article-query thead th.sorting_disabled{cursor:default;padding-right:10px !important;background-image:none !important;} #article-query thead th.sorting_disabled:after{display:none !important;}
    </style>
  </head>

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
    
      <!-- Page body starts -->

      
      <div class="container-fluid" style="justify-content: space-between;">
        <div div class="row pt-4 d-flex align-items-center flex-wrap">
          <div class="col-8 col-lg-10">
            <p class="articleheading ms-4">Article Query</p>
          </div>
          <!--<div class="col-4 col-lg-2">
            <div class="row">
              <div class="col-3 articlebtns d-flex justify-content-end">
                <a href="<?php echo $_COOKIE['set_url'];?>/addarticlequery">
                  <button type="button">+ Add Feedback</button>
                </a>
              </div>
            </div>
          </div>-->
        </div>
      </div>
<div class="divouter mt-4">
</div>
    
      <!-- Table section -->

       <?php lu_open('Article Query', 'Article Query &rsaquo; All', $_COOKIE['set_url'].'/addarticlequery', '+ Add Feedback'); ?>
       <div class="table-responsive custom-container">
       <table id="article-query" class="table table-striped custtable table-primary" style="text-wrap: nowrap;white-space: nowrap">
      
                                          <thead>
											<tr>
                                                <th>Customer Name</th>
                                                <th>Journal Name</th>
												<th>Stage</th>
												<th>Article</th>
												<th>Status</th>
												 <th> <i class="ace-icon fa fa-clock-o bigger-110 hidden-480"></i>  Created On</th>
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
			table = $('#article-query').DataTable({
				"pageLength" : <?php echo $length; ?>,
				"lengthMenu" : [[10, 25, 50, 100, 100000], [10, 25, 50, 100, "All"]],
       
				"displayStart":<?php echo $page; ?>,
				"sServerMethod": "POST", 
				"bProcessing": true,
				"bServerSide": true,
				"sAjaxSource": "get/json-article-query.php",
				"sSortDir_0": "desc",
				
                "fnServerParams": function ( aoData ) {
					if(tFlag){
						aoData.push( { "name": "sSearch", "value": '<?php echo $search ?>'} );
						tFlag = false;
					}
				},

       
				"aaSorting": [[6, 'desc']],
				"columns": [
					{ "data": "0" },
					{ "data": "1" },
					{ "data": "2" },
					{ "data": "3" },
					{ "data": "4" },
					{ "data": "5" },
					{ "data": "6" }
				],
        "initComplete": function(settings, json) {
        // Hide the loader once the DataTable is initialized
        $("#articles_processing").css("display", "none");
      },
				dom: "<'row'<'col-sm-2'f><'col-sm-10 voptions no_pad_l text-right'>>" +
					 "<'row'<'col-sm-12'tr>>" +
					 "<'row'<'col-sm-4'l><'col-sm-4 text-center'i><'col-sm-4'p>>",
				 "columnDefs": [
					{ "orderable": false, "targets": 6 }
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
			$('#article-query').on( 'init.dt', function () {
				$('input[type=search]').val('<?php echo $search; ?>');
				table.search( '<?php echo $search; ?>' );
			});
			<?php
			}
			if($length!='')
			{
			?>
			$('#article-query').on( 'init.dt', function () {
				table.page.len( <?php echo $length; ?> );
			});
			<?php
			}
			?>	
			$('#article-query').on('click', 'button[data-delete]', function () {
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
     // window.top.location="articlequery?del="+del;
     window.top.location="articlequery?del="+del;

    } else {
      swal("Cancelled", "Article Query not Deleted", "error");
    }
	});
			});

      
		});	
		</script>
    
   
  
  </body>
</html>
