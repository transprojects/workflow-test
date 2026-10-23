<?php include "includes/includes.php";
include_once 'includes/list_ui.php';
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

    <?php 
	if(isset($_REQUEST['del']))
{	
	$db->query("delete from user_daily_tasks where id='".$_REQUEST['del']."'");
	header("location:".$_COOKIE['set_url']."/todotasks?msg=Successfully deleted");
	die();	
	
}
?>	
     
	  
	  <div class="container-fluid" style="justify-content: space-between;">
        <div div class="row pt-4 d-flex align-items-center flex-wrap">
          <div class="col-12 col-sm-6 col-md-10">
            <p class="articleheading ms-md-4">Manage My Daily Tasks</p>
          </div>
          <div class="col-12 col-sm-6 col-md-2 mt-5 mt-sm-0">
            <div class="row d-flex justify-content-sm-end pe-2">
              <div class="col-3 articlebtns me-3">
                <a href="<?php echo $_COOKIE['set_url'];?>/add-tasks">
                  <button type="button">+ Add  Daily Tasks</button>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
<div class="divouter mt-4">
</div>
    
      <!-- Table section -->

      <!-- Table section -->
<?php lu_open('Manage My Daily Tasks', 'Manage My Daily Tasks &rsaquo; All', $_COOKIE['set_url'].'/add-tasks', '+ Add  Daily Tasks'); ?>
       <div class="table-responsive custom-container">
  <table id="user_daily_tasks_table" class="table table-striped custtable table-primary" style="text-wrap: nowrap; white-space: nowrap">
    <thead>
      <tr>
        <th>#</th>
        <th>Task Description</th>
        <th>Task Date</th>
        <th>Created At</th>
        <th>Action</th>
      </tr>
    </thead>
  </table>
</div>
<?php lu_close(); ?>

<?php echo footer(); ?>
<?php echo scripts(); ?>

<script>
<?php
  $page = isset($_REQUEST['page']) ? $_REQUEST['page'] * 10 : 0;
  $search = $_REQUEST['search'] ?? '';
  $length = $_REQUEST['length'] ?? '10';
?>
$(document).ready(function () {
  var tFlag = true;
  var table = $('#user_daily_tasks_table').DataTable({
    "pageLength": <?php echo $length; ?>,
    "lengthMenu": [[10, 25, 50, 100, 100000], [10, 25, 50, 100, "All"]],
    "displayStart": <?php echo $page; ?>,
    "bProcessing": true,
    "bServerSide": true,
    "sAjaxSource": "get/json-my-todo.php",
    "aaSorting": [[0, 'DESC']],
    "columns": [
      { "data": "0" },  // ID
      { "data": "1" },  // Task Description
      { "data": "2" },  // Task Date
      { "data": "3" },  // Created At
      { "data": "4" }   // Actions
    ],
    "columnDefs": [
      { "orderable": false, "targets": [4] }
    ],
    "fnServerParams": function (aoData) {
      if (tFlag) {
        aoData.push({ "name": "sSearch", "value": '<?php echo $search ?>' });
        tFlag = false;
      }
    },
    dom: 'Blfr<"table-wrapper"t>ip',
    buttons: [
      {
        extend: 'collection',
        text: 'Export',
        buttons: ['copyHtml5', 'excelHtml5', 'csvHtml5', 'pdfHtml5', 'print']
      }
    ]
  });

  // Delete logic
  $('#user_daily_tasks_table').on('click', 'button[data-delete]', function () {
    var del = $(this).attr('data-delete');
    swal({
      title: "Are you sure?",
      text: "This task will be permanently deleted.",
      type: "warning",
      showCancelButton: true,
      confirmButtonColor: '#DD6B55',
      confirmButtonText: 'Yes, delete it!',
      cancelButtonText: "Cancel",
      closeOnConfirm: false,
      closeOnCancel: false
    }, function (isConfirm) {
      if (isConfirm) {
        swal("Deleted!", "Processing...", "success");
        window.location = "todotasks?del=" + del;
      } else {
        swal("Cancelled", "Task not deleted.", "error");
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
