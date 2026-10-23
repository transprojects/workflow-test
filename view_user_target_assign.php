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

    <!-- Data Table CSS- -->

  </head>

  <body>
  
  <?php 
  if(isset($_REQUEST['del']))
  {
   
// Snapshot before delete (for change-log email)
$__old_snap = change_log_target_snapshot($_REQUEST['del']);
$delete_user_yarget=mysqli_query($db,"delete from user_target_assign where uta_id='".$_REQUEST['del']."' ");
$delete_user_cu_target=mysqli_query($db,"delete from user_target_customer_assign where uta_id='".$_REQUEST['del']."' ");
log_row_deleted('User Target Assign', $_REQUEST['del'] . (isset($__old_snap['header']['user_id']) ? ' / user ' . $__old_snap['header']['user_id'] : ''), change_log_target_values($__old_snap));   
  }
  ?>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
    
      <!-- Page body starts -->

      
      <?php lu_hero('Employee Target Assign', 'Employee Target Assign &rsaquo; All', $_COOKIE['set_url'].'/addusertargetassign', '+ Add Target'); ?>
<div class="container-fluid" style="justify-content: space-between;">
        <div div class="row pt-4 d-flex align-items-center flex-wrap">
          <div class="col-12 col-sm-6 col-md-10">
            <p class="articleheading ms-md-4">Employee Target Assign</p>
          </div>
          <div class="col-12 col-sm-6 col-md-2 mt-5 mt-sm-0">
            <div class="row d-flex justify-content-sm-end pe-2">
              <div class="col-3 articlebtns me-3">
                <a href="<?php echo $_COOKIE['set_url'];?>/addusertargetassign">
                  <button type="button">+ Add Target </button>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
<div class="divouter mt-4">
        <form action="" name="filterForm" id="filterForm" class="formouter" method="post">
          <div class="selectdiv row p-4 d-flex gap-2">
			      <div class="col-12 col-lg-3">
                <label class="mb-2">Choose Department</label>
                 <?php
    $query = "SELECT dept_name,dept_code FROM `adm_dept_master`  ORDER BY `dept_name` ASC ";
    $customers = $db->query($query) or ($db->error);		
    
    ?><select id="dep_id" name="dep_id" class="form-control"  data-placeholder="Choose" required>
    <option value="">-Select-</option>
    <?php while($row = $customers->fetch_assoc()){ ?>
    <option value="<?php echo $row['dept_code']; ?>" <?php if($_REQUEST['dep_id'] ==$row['dept_code']){?> selected <?php } ?>><?php echo $row['dept_name']; ?></option>
    <?php } ?>
    </select>
              </div>
             <div class="col-12 col-lg-3">
                <label class="mb-2">Choose Employee</label>
                 <select class="form-select employee_drop" data-placeholder="Select Employee" name="user_id" id="user_id">
                    <option value="">Select Employee</option>
                    <option value="all"  <?php if ($_REQUEST['user_id']=="all"){?>selected<?php }?>>-All-</option>
											<?php
											$sql_users = $auth->showdashboardUserlist(0);
											foreach($sql_users as $res_users)
											{
											?>
											<option value="<?php echo $res_users['id'] ?>"style="font-weight:500;" <?php if ($_REQUEST['user_id']==$res_users['id']){?>selected<?php }?>><?php echo $res_users['full_name'] ?> </option>
											<?php
											}
											?>
                </select>
              </div>
			 
			<div class="col-12 col-sm-6 col-md-3 col-lg-2" style="display:flex;align-items:center;padding-top:35px;gap:5px;">
             <div class="col-auto d-flex align-items-center">
					<button type="button" class="btn btn-success" id="downloadBtnall">Download Excel</button>
			</div>
             </div> 
			
             <div class="col-12 mt-5 d-flex justify-content-center align-items-center">
              <button type="submit" name="btn-wip" class="resultbtn">Get Result</button>		  
              <a class="refreshbtn ms-4" href="employeetargetassign"><img src="./assets/images/refresh.svg"></a>
             </div>
             </div>
              </form>
              </div>
    
      <!-- Table section -->

       <?php lu_card_open(); ?>
       <div class="table-responsive custom-container">
	 
    <table id="users" class="table table-striped custtable table-primary" style="text-wrap: nowrap; white-space: nowrap">
    <thead>
        <tr>
            <th style="display:none;"></th> <!-- Hidden uta_id column -->
            <th>Employee Name</th>
            <th>Primary Department</th>
            <th>Collabrative Preferred Customer Average</th>
            <th>Collabrative Average</th>
            <th>Action</th>
        </tr>
    </thead>
</table>

      </div>
<?php lu_close(); ?>



  
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
			 if(isset($_REQUEST['dep_id']))
				$dep_id = $_REQUEST['dep_id'];
			else
				$dep_id = '';
			 if(isset($_REQUEST['user_id']))
				$user_id = $_REQUEST['user_id'];
			else
				$user_id = '';
		?>
		var table
		var tFlag = true;
		$(document).ready(function() {
			var rid   = 2;
			table = $('#users').DataTable({
				"pageLength" : <?php echo $length; ?>,
				"lengthMenu" : [[10, 25, 50, 100, 100000], [10, 25, 50, 100, "All"]],
       
				"displayStart":<?php echo $page; ?>,
				"sServerMethod": "POST", 
				"bProcessing": true,
				"bServerSide": true,
				"sAjaxSource": "get/json-user-target-assign.php",
				"sSortDir_0": "desc",
				
				"fnServerParams": function ( aoData ) {
					if(tFlag){
						aoData.push(
			   { "name": "dep_id", "value": '<?php echo $dep_id; ?>'},
               { "name": "user_id", "value": '<?php echo $user_id; ?>'},
               { "name": "sSearch", "value": '<?php echo $search ?>'}
                );
						tFlag = false;
					}
          else
          {
            aoData.push(
							  { "name": "dep_id", "value": '<?php echo $dep_id; ?>'},
							  { "name": "user_id", "value": '<?php echo $user_id; ?>'},
							  { "name": "allocated", "value": '<?php echo $allocated; ?>'}
						);
          }
				},
	
       
				"aaSorting": [[0, 'desc']],
				"columns": [
				 { "data": "0", "visible": false }, // uta_id, hidden but used for sorting
				 { "data": "1" }, // full_name
				 { "data": "2" }, // dept_name
				 { "data": "3" },  // ac
				 { "data": "4" },  // ac
				 { "data": "5" }  // ac
					


				],
        "initComplete": function(settings, json) {
        // Hide the loader once the DataTable is initialized
        $("#articles_processing").css("display", "none");
      },
				dom: "<'row'<'col-sm-2'f><'col-sm-10 voptions no_pad_l text-right'>>" +
					 "<'row'<'col-sm-12'tr>>" +
					 "<'row'<'col-sm-4'l><'col-sm-4 text-center'i><'col-sm-4'p>>",
				 "columnDefs": [
					  { "orderable": false, "targets": 5 } // disable sorting on action buttons
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
			$('#users').on( 'init.dt', function () {
				$('input[type=search]').val('<?php echo $search; ?>');
				table.search( '<?php echo $search; ?>' );
			});
			<?php
			}
			if($length!='')
			{
			?>
			$('#users').on( 'init.dt', function () {
				table.page.len( <?php echo $length; ?> );
			});
			<?php
			}
			?>	
			$('#users').on('click', 'button[data-delete]', function () {
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
     // window.top.location="employeetargetassign?del="+del;
     window.top.location="employeetargetassign?del="+del;

    } else {
      swal("Cancelled", "Employee Target not Deleted", "error");
    }
	});
			});

      
		});	
		
		$("#downloadBtnall").on("click", function () {
    const formData = $("#filterForm").serialize();
    const downloadUrl = "export_user_target_assign.php?" + formData;
    window.location.href = downloadUrl; // triggers download
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
	  			$(document).ready(function(){
    $('#dep_id').on('change',function(){
        var dep_id = $(this).val();
        if(dep_id){
            $.ajax({
                type:'POST',
                url:'get_consolidated_users.php',
                data:'dept_id='+dep_id,
                success:function(html){
                    $('#user_id').html(html);                    
                }
            }); 
        }else{
            $('#user_id').html('<option value="">Select User first</option>');
            
        }
    });

	
	});
    </script>

  </body>
</html>
