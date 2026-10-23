<?php include "includes/includes.php";?>
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
    
      <!-- Page body starts -->

    <?php 
	if(isset($_REQUEST['del']))
{	
	$db->query("delete from wf_user_leave where ul_id='".$_REQUEST['del']."'");
	header("location:".$_COOKIE['set_url']."/my-leave?msg=Successfully deleted");
	die();	
	
}
?>	
     
	  
	  <div class="container-fluid" style="justify-content: space-between;">
        <div div class="row pt-4 d-flex align-items-center flex-wrap">
          <div class="col-12 col-sm-6 col-md-10">
            <p class="articleheading ms-md-4">Manage My Leaves</p>
          </div>
          <div class="col-12 col-sm-6 col-md-2 mt-5 mt-sm-0">
            <div class="row d-flex justify-content-sm-end pe-2">
              <div class="col-3 articlebtns me-3">
                <a href="<?php echo $_COOKIE['set_url'];?>/addmyleave">
                  <button type="button">+ Apply  Leaves</button>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
<div class="divouter mt-4">
</div>
    
      <!-- Table section -->

       <div class="container table-responsive custom-container p-4 " style="box-shadow: 0px 0px 16px 0px rgba(164, 164, 171, 0.25);border-radius: 24px;">
      <table id="happenings" class="table table-striped custtable table-primary" style="text-wrap: nowrap; white-space: nowrap">
    <thead>
		<tr>
			<th>Type of Leave</th>
			<th>Employee Name</th>
			<th>Leave on</th>
			<th>Session</th>
			<th>Reasons</th>
			<th>Created By</th>
			<th>Approved By</th>
			<th>Approval Status</th>
			 <?php if ($_COOKIE['user_level'] != 3): ?>
			<th><input type="checkbox" id="select_all"></th>
			<?php else: ?>
			<th></th>  <!-- empty th to keep column alignment -->
			<?php endif; ?>
			<th>Action</th>
		</tr>
    </thead>
</table>
<?php if ($_COOKIE['user_level'] != 3): ?>
<button id="approve_selected" class="btn btn-success mt-3">Approve Selected</button>
<?php endif; ?>


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
			table = $('#happenings').DataTable({
				"pageLength" : <?php echo $length; ?>,
				"lengthMenu" : [[10, 25, 50, 100, 100000], [10, 25, 50, 100, "All"]],
       
				"displayStart":<?php echo $page; ?>,
				"sServerMethod": "POST", 
				"bProcessing": true,
				"bServerSide": true,
				"sAjaxSource": "get/json-my-leave.php",
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
					{ "data": "8" },
					{ "data": "9" }
				
				],
        "initComplete": function(settings, json) {
        // Hide the loader once the DataTable is initialized
        $("#articles_processing").css("display", "none");
      },
				dom: "<'row'<'col-sm-2'f><'col-sm-10 voptions no_pad_l text-right'>>" +
					 "<'row'<'col-sm-12'tr>>" +
					 "<'row'<'col-sm-4'l><'col-sm-4 text-center'i><'col-sm-4'p>>",
				 "columnDefs": [
					{ "orderable": false, "targets": 7 },
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
			$('#happenings').on( 'init.dt', function () {
				$('input[type=search]').val('<?php echo $search; ?>');
				table.search( '<?php echo $search; ?>' );
			});
			<?php
			}
			if($length!='')
			{
			?>
			$('#happenings').on( 'init.dt', function () {
				table.page.len( <?php echo $length; ?> );
			});
			<?php
			}
			?>	
			$('#happenings').on('click', 'button', function () {
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
     window.top.location="my-leave?del="+del;

    } else {
      swal("Cancelled", "My Leave not Deleted", "error");
    }
	});
			});

      
		});	
		</script>
    
<script>
document.getElementById('select_all')?.addEventListener('change', function () {
    document.querySelectorAll('.leave_checkbox').forEach(cb => cb.checked = this.checked);
});

// Optional: Bulk approve example
document.getElementById('approve_selected')?.addEventListener('click', function () {
    const selected = [...document.querySelectorAll('.leave_checkbox:checked')].map(cb => cb.value);
    if (!selected.length) return alert('No leaves selected.');
    if (!selected.length) return alert('No leaves selected.');

    fetch('approval_leaves.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ leave_ids: selected })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Leaves approved.');
            location.reload();
        } else {
            alert('Approval failed.');
        }
    });
});
</script>

    
   
  
  </body>
</html>
