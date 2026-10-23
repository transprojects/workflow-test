<?php include "includes/includes.php";
include_once "includes/change_log_mail.php";?>

<?php 
	if(isset($_REQUEST['del']))
{	
	// Snapshot before delete (for change-log email)
	$__old = change_log_snapshot('non_workflow_user_percentage_article', 'nwa_id', $_REQUEST['del']);
	$db->query("delete from  non_workflow_user_percentage_article where nwa_id='".$_REQUEST['del']."'");
	log_row_deleted('Non Workflow Percentage (User)', $_REQUEST['del'], $__old);
	header("location:".$_COOKIE['set_url']."/nonworkflowpercentageuser?msg=Successfully deleted");
	die();	
	
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
	$from_dt=date('d-m-Y');
}
if(isset($_REQUEST['user_id']))
{
	  $user_id=$_REQUEST['user_id'];
}
else
{
	$user_id='';
}
if(isset($_REQUEST['to_dt']))
{
	  $to_dt=$_REQUEST['to_dt'];
}
else
{
	$to_dt=date('d-m-Y');
}

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
    
      <!-- Page body starts -->

      
      <div class="container-fluid" style="justify-content: space-between;">
        <div div class="row pt-4 d-flex align-items-center flex-wrap">
          <div class="col-12 col-sm-6 col-md-10">
            <p class="articleheading ms-md-4">Non Workflow Articles</p>
          </div>
          <div class="col-12 col-sm-6 col-md-2 mt-5 mt-sm-0">
            <div class="row d-flex justify-content-sm-end pe-2">
              <div class="col-3 articlebtns me-3">
                <a href="<?php echo $_COOKIE['set_url'];?>/addnonworkflowpercentageusers">
                  <button type="button">+ Add Non Workflow Articles</button>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
<div class="divouter mt-4">
        <form action="" name="articles" class="formouter" method="post">
          <div class="selectdiv row p-4 d-flex gap-2">
           
<?php 
if($_COOKIE['user_level']=='1')
{
?>
             <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                <label class="mb-2">Choose Users</label>
                 <?php
//read data
 $query_user = "SELECT `id`,`user_name`,`full_name` FROM `users` WHERE  approved='1' ORDER BY `full_name` ASC ";
$users_r = $db->query($query_user) or ($db->error);
?><select id="user_id" name="user_id" class="form-control"  data-placeholder="Choose">
   <option value="">-Select-</option>
<?php		
while($row_u = $users_r->fetch_assoc())
{
	?>   
<option value="<?php echo $row_u['id'];?>" <?php if($_REQUEST['user_id'] ==$row_u['id']){?> selected <?php } ?>><?php echo $row_u['full_name']; ?></option>
    <?php
}
?>
    </select>
              </div>
			  <?php 
}
?>			  
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
              <a class="refreshbtn ms-4" href="nonworkflowpercentageuser"><img src="./assets/images/refresh.svg"></a>
             </div>
             </div>
              </form>
              </div>
    
      <!-- Table section -->

       <div class="container table-responsive custom-container p-4 " style="box-shadow: 0px 0px 16px 0px rgba(164, 164, 171, 0.25);border-radius: 24px;">
       <table id="non-workflow" class="table table-striped custtable table-primary" style="text-wrap: nowrap; white-space: nowrap !important">
      
                                          <thead>
											<tr>
												<th>Customer Name</th>
												<th>Pub ID</th>
												 <th><i class="ace-icon fa fa-clock-o bigger-110 hidden-480"></i>  Created On</th>
												 <th>Page Count</th>
												 <th>Time Taken</th>
												 <th>Percentage</th> <!-- New -->
												 <th>Comments</th>
                     						    <th>Action</th>
											</tr>
										</thead>
				</table>
				







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
    if(isset($_REQUEST['user_id']))
      $user_id = $_REQUEST['user_id'];
      else
      $user_id = '';
			
			if(isset($_REQUEST['length']))
				$length = $_REQUEST['length'];
			else
				$length = '10';
		?>
		var table
		var tFlag = true;
		$(document).ready(function() {
			var rid   = 2;
			table = $('#non-workflow').DataTable({
				"pageLength" : <?php echo $length; ?>,
				"lengthMenu" : [[10, 25, 50, 100, 100000], [10, 25, 50, 100, "All"]],
       
				"displayStart":<?php echo $page; ?>,
				"sServerMethod": "POST", 
				"bProcessing": true,
				"bServerSide": true,
				"sAjaxSource": "get/json-non-workflow-users-page.php",
				"sSortDir_0": "desc",
				
                "fnServerParams": function ( aoData ) {
					if(tFlag){
						aoData.push( { "name": "sSearch", "value": '<?php echo $search ?>'} );
						tFlag = false;
					}
				},

       
				"aaSorting": [[5, 'desc']],
								
				"fnServerParams": function ( aoData ) {
					if(tFlag){
						aoData.push(
			   { "name": "article_id", "value": '<?php echo $article_id ?>'},
               { "name": "from_dt", "value": '<?php echo $from_dt; ?>'},
			   { "name": "to_dt", "value": '<?php echo $to_dt; ?>'},               
			   { "name": "user_id", "value": '<?php echo $user_id; ?>'},               
               { "name": "sSearch", "value": '<?php echo $search ?>'}
                );
						tFlag = false;
					}
          else
          {
            aoData.push(
							 { "name": "article_id", "value": '<?php echo $article_id ?>'},
							 { "name": "from_dt", "value": '<?php echo $from_dt; ?>'},
							 { "name": "to_dt", "value": '<?php echo $to_dt; ?>'},
							 { "name": "user_id", "value": '<?php echo $user_id; ?>'}
						);
          }
				},
				"columns": [
					{ "data": "0" },
					{ "data": "1" },
					{ "data": "2" },
					{ "data": "3" },
					{ "data": "4" },
					{ "data": "5" }, // Percentage
					{ "data": "6" }, // Comments
					{ "data": "7", "orderable": false, "searchable": false }  // Delete
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
			$('#non-workflow').on( 'init.dt', function () {
				$('input[type=search]').val('<?php echo $search; ?>');
				table.search( '<?php echo $search; ?>' );
			});
			<?php
			}
			if($length!='')
			{
			?>
			$('#non-workflow').on( 'init.dt', function () {
				table.page.len( <?php echo $length; ?> );
			});
			<?php
			}
			?>	
			$('#non-workflow').on('click', 'button', function () {
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
     window.top.location="nonworkflowpercentageuser?del="+del;

    } else {
      swal("Cancelled", "User Non Workflow percentage not Deleted", "error");
    }
	});
			});

      
		});	
		
/* 		      $(function () {
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
      }); */
	  
$(function () {
    $(".monthlyinputdate, .monthlyinputdate1").datepicker({
        uiLibrary: "bootstrap5",
        dateFormat: "dd-mm-yy",
        autoclose: true,
        todayHighlight: true
    });

    function syncDates(date) {
        $(".monthlyinputdate").val(date);
        $(".monthlyinputdate1").val(date);

        $(".monthlyinputdate, .monthlyinputdate1").datepicker("destroy").datepicker({
            uiLibrary: "bootstrap5",
            dateFormat: "dd-mm-yy",
            autoclose: true,
            todayHighlight: true,
            defaultDate: date
        }).val(date);
    }

    $(".monthlyinputdate").on("change", function () {
        let selectedDate = $(this).val();
        syncDates(selectedDate);
    });

    $(".monthlyinputdate1").on("change", function () {
        let selectedDate = $(this).val();
        syncDates(selectedDate);
    });
});


	</script>
    
   
  
  </body>
</html>
