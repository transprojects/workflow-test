<?php include "includes/includes.php";
include_once 'includes/list_ui.php';?>
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
            <p class="articleheading ms-md-4">Manage Complex Article Approval</p>
          </div>
        </div>
      </div>
<div class="divouter mt-4">
</div>
    
    <!-- Table section -->
<?php lu_open('Manage Complex Article Approval', 'Manage Complex Article Approval &rsaquo; All', '#', ''); ?>
       <div class="table-responsive custom-container">
    <table id="complexarticles" class="table table-striped custtable table-primary" style="white-space: nowrap">
        <thead>
            <tr>
                <th>Employee Name</th>
                <th>Customer Name</th>
                <th>Pub ID</th>
                <th>Complexity Type</th>
                <th><i class="ace-icon fa fa-clock-o bigger-110 hidden-480"></i> Created On</th>
                <th>Approved By</th>
                <th>Approval Status</th>
                <th>Approved On</th>
                <?php if ($_COOKIE['user_level'] != 3): ?>
                    <th><input type="checkbox" id="select_all"></th>
                <?php else: ?>
                    <th></th>
                    <th></th>
                <?php endif; ?>
            </tr>
        </thead>
    </table>

    <?php if ($_COOKIE['user_level'] != 3): ?>
        <button id="approve_selected" class="btn btn-success mt-3">Approve Selected</button>
    <?php endif; ?>
</div>
<?php lu_close(); ?>

<?php echo footer(); ?>
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
			table = $('#complexarticles').DataTable({
				"pageLength" : <?php echo $length; ?>,
				"lengthMenu" : [[10, 25, 50, 100, 100000], [10, 25, 50, 100, "All"]],
       
				"displayStart":<?php echo $page; ?>,
				"sServerMethod": "POST", 
				"bProcessing": true,
				"bServerSide": true,
				"sAjaxSource": "get/json-complex-articles-users.php",
				"sSortDir_0": "desc",
				
                "fnServerParams": function ( aoData ) {
					if(tFlag){
						aoData.push( { "name": "sSearch", "value": '<?php echo $search ?>'} );
						tFlag = false;
					}
				},

       
				"aaSorting": [[0, 'DESC']],
				"columns": [
					{ "data": "0" },
					{ "data": "1" },
					{ "data": "2" },
					{ "data": "3" },
					{ "data": "4" },
					{ "data": "5" },
					{ "data": "6" },
					{ "data": "7" },
					{ "data": "8" }
				
				],
        "initComplete": function(settings, json) {
        // Hide the loader once the DataTable is initialized
        $("#articles_processing").css("display", "none");
      },
				dom: "<'row'<'col-sm-2'f><'col-sm-10 voptions no_pad_l text-right'>>" +
					 "<'row'<'col-sm-12'tr>>" +
					 "<'row'<'col-sm-4'l><'col-sm-4 text-center'i><'col-sm-4'p>>",
				 "columnDefs": [
					{ "orderable": false, "targets": 8 }
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
			$('#complexarticles').on( 'init.dt', function () {
				$('input[type=search]').val('<?php echo $search; ?>');
				table.search( '<?php echo $search; ?>' );
			});
			<?php
			}
			if($length!='')
			{
			?>
			$('#complexarticles').on( 'init.dt', function () {
				table.page.len( <?php echo $length; ?> );
			});
			<?php
			}
			?>	

      
		});	
		</script>
    
<script>
document.getElementById('select_all')?.addEventListener('change', function () {
    document.querySelectorAll('.complex_checkbox').forEach(cb => cb.checked = this.checked);
});

// Optional: Bulk approve example
document.getElementById('approve_selected')?.addEventListener('click', function () {
    const selected = [...document.querySelectorAll('.complex_checkbox:checked')].map(cb => cb.value);
    if (!selected.length) return alert('No Article selected.');
    if (!selected.length) return alert('No Article selected.');

    fetch('complex_article_approval.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ complex_ids: selected })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Article approved.');
            location.reload();
        } else {
            alert('Approval failed.');
        }
    });
});
</script>
    
   
  
  </body>
</html>
