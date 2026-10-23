<?php include "includes/includes.php";
include_once 'includes/list_ui.php';
include_once "includes/change_log_mail.php";
session_start();

if(isset($_REQUEST['del']))
{	
	// Snapshot before delete (for change-log email)
	$__old = change_log_snapshot('artconfig', 'art_id', $_REQUEST['del']);
	$db->query("delete from artconfig where art_id='".$_REQUEST['del']."'");
	log_row_deleted('Art Config', $_REQUEST['del'], $__old);
	header("location:".$_COOKIE['set_url']."/artconfig?msg=Successfully deleted");
	die();	
	
}

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
if(isset($_REQUEST['dept_id']))
{
	  $dept=implode(",",$_REQUEST['dept_id']);
 	  $ex_dept = explode(",",$dept);
}
else
{
	$ex_dept='';
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
    <?php lu_css(); ?>	

<style>
/* Pagination styles */
.pagination {
    display: inline-block;
    padding-left: 0;
    margin: 20px 0;
    border-radius: 4px;
    float: right; /* Align pagination to the right */
}

.pagination > li {
    display: inline;
}

.pagination > li > a,
.pagination > li > span {
    position: relative;
    float: left;
    padding: 6px 12px;
    margin-left: -1px;
    line-height: 1.42857143;
    color: #F26722; /* Orange color */
    text-decoration: none;
    background-color: #fff;
    border: 1px solid #ddd;
}

.pagination > li:first-child > a,
.pagination > li:first-child > span {
    margin-left: 0;
    border-bottom-left-radius: 4px;
    border-top-left-radius: 4px;
}

.pagination > li:last-child > a,
.pagination > li:last-child > span {
    border-bottom-right-radius: 4px;
    border-top-right-radius: 4px;
}

.pagination > li > a:hover,
.pagination > li > span:hover,
.pagination > li > a:focus,
.pagination > li > span:focus {
    background-color: #eee;
}

.pagination > .active > a,
.pagination > .active > span,
.pagination > .active > a:hover,
.pagination > .active > span:hover,
.pagination > .active > a:focus,
.pagination > .active > span:focus {
    z-index: 2;
    color: #fff;
    background-color: #F26722; /* Orange color */
    border-color: #F26722; /* Orange color */
    cursor: default;
}

/* Disabled styles */
.pagination > .disabled > span,
.pagination > .disabled > a,
.pagination > .disabled > a:hover,
.pagination > .disabled > a:focus,

/* 
.datepicker-month-year-only .ui-datepicker-calendar {
    display: none;
}
 */
    #loader {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            text-align: center;
            z-index: 9999;
        }

        #loader img {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
</style>


  </head>

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
	 
      <!-- Header logo section -->
      
      <!-- report section body starts -->
      <div class="containter-fluid">
      <?php lu_hero('Art Configration', 'Art Configration &rsaquo; All', $_COOKIE['set_url'].'/addart', '+ Add Art Config'); ?>
<div class="container-fluid" style="justify-content: space-between;">
        <div div class="row pt-4 d-flex align-items-center flex-wrap">
          <div class="col-12 col-sm-6 col-md-10">
				<p class="articleheading ms-md-4">Art Configration</p>
          </div>
		   <?php 
				  if($_COOKIE['user_level']==1 || $_COOKIE['user_level']==5 || $_COOKIE['user_level']==7)
				  {
				  ?>
          <div class="col-12 col-sm-6 col-md-2 mt-5 mt-sm-0">
            <div class="row d-flex justify-content-sm-end pe-2">
              <div class="col-3 articlebtns me-3">
                <a href="<?php echo $_COOKIE['set_url'];?>/addart">
                  <button type="button">+ Add Art Config</button>
                </a>
              </div>
            </div>
			<?php 
				  }
				  ?>
          </div>
        </div>
      </div>
		 <div class="divouter mt-4">
        <form action="" name="articles" class="formouter" method="post">
          <div class="selectdiv row p-4 d-flex gap-2">
            <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                  <label class="mb-2">Choose Customer<span style="color: red">*</span></label>
                  <select class="form-select customer_drop" data-placeholder="Select Customer" name="cust_id[]" id="cust_id" aria-label="search-input" multiple="multiple">
                  <option value="">Choose Customer</option>
                   <?php
											$sql_customers = $auth->showCustomerlist();
											foreach($sql_customers as $res_customers)
											{
												
									      	if(isset($_REQUEST['cust_id']))
													{
										    	?>
										       <option <?php if (in_array($res_customers['id'], $ex_cid)){?>selected<?php }?> value="<?php echo $res_customers['id'] ?>" ><?php echo $res_customers['cust_name'] ?> </option>
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
                  <label class="mb-2">Choose Journals<span style="color: red">*</span></label>
                  <select class="form-control journal_drop" data-placeholder="Select Journal" name="j_id[]" id="j_id" multiple="multiple" >
                    <option value="">Choose Journals</option>
                    <?php
											$sql_journals = $auth->showJournallist('0',2);
											foreach($sql_journals as $res_journals)
											{
												
									      	if(isset($_REQUEST['j_id']))
													{
										    	?>
										       <option <?php if (in_array($res_journals['j_id'], $ex_jid)){?>selected<?php }?> value="<?php echo $res_journals['j_id'] ?>"style="font-weight:500; "><?php echo $res_journals['j_code'] ?> </option>
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
                  <label class="mb-2">Choose stages<span style="color: red">*</span></label>
                  <select class="form-control stage_drop" data-placeholder="Select Stages" name="stage[]" id="stage" multiple="multiple" >
                    <option value="">Choose stages</option>
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
                <?php
	$query = "SELECT * FROM `adm_dept_master` WHERE `parent_id` = 0 ORDER BY `dept_name` ASC";
	$customers = $db->query($query) or ($db->error);		

	?>
                  <label class="mb-2">Department<span style="color: red">*</span></label>
                  <select class="form-select dept_drop" data-placeholder="Select Department" name="dept_id[]" id="dept_id" multiple="multiple">
                    <option value="">Select department</option>
                    <?php 
					  	$sql_department = $auth->getAlldepartment('0',2);

					  foreach($sql_department as $res_department)
											{
												
									      	if(isset($_REQUEST['dept_id']))
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

            </div>
            <!-- </div> -->
          <div class="col-12 mt-5 d-flex justify-content-center align-items-center">
              <button type="submit" name="getaction" class="resultbtn">Get Result</button>		  
              <a class="refreshbtn ms-4" href="artconfig"><img src="./assets/images/refresh.svg"></a>
             </div>
          </div>
        </div>
	</form>


        <!-- Table section -->
	
       <?php lu_card_open(); ?>
       <div class="table-responsive custom-container">
				<table id="artconfig" class="table table-striped custtable table-primary" style="text-wrap: nowrap; white-space: nowrap !important">
      
                                          <thead>
											<tr>
												<th>Journal</th>
												 <th>Stage</th>
												 <th>Department</th>
                     						    <th>In Path</th>
												 <th>Out path</th>
                     						    <th>Action</th>
											</tr>
										  </thead>
				</table>

       </div>
<?php lu_close(); ?>

     <?php echo footer(); ?>
     <?php echo scripts(); ?>	
    </div>

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
      $(".dept_drop").select2({
        placeholder: "Select Department",
      });
     
    </script>
<script>
   
    $("#cust_id").on("change", function() {
            var cust_id=$.trim($(this).val());

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
     function populateSelecte(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();
            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.j_code).val(item.j_id));
            });
        }
        
        function deleteart(art_id){
          $.ajax({
                type:'POST',
                url:'saveart.php',
                data:{'art_id':art_id},
                success:function(html){  
                  alert(html.trim().replace(/^"|"$/g, ''));
                  location.reload();        
                }
            });
        }

   <!-- Initialize DataTables -->
		<?php
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
			if(isset($_REQUEST['dept_id']))
			 {
$dept_id = $_REQUEST['dept_id'];
$dept_id = array_filter($dept_id, function($value) {
    return $value !== "all";
});
$dept_id = implode(",", $dept_id);
			 }

			else
			{
				$dept_id = '';
			}
			if(isset($_REQUEST['length']))
				$length = $_REQUEST['length'];
			else
				$length = '10';
		?>
		var table
		var tFlag = true;
		$(document).ready(function() {
			var rid   = 2;
			table = $('#artconfig').DataTable({
				"pageLength" : <?php echo $length; ?>,
				"lengthMenu" : [[10, 25, 50, 100, 100000], [10, 25, 50, 100, "All"]],
       
				"displayStart":<?php echo $page; ?>,
				"sServerMethod": "POST", 
				"bProcessing": true,
				"bServerSide": true,
				"sAjaxSource": "get/json-artconfig.php",
				"sSortDir_0": "desc",
				
          				"fnServerParams": function ( aoData ) {
					if(tFlag){
						aoData.push(
               { "name": "cust_id", "value": '<?php echo $cust_id; ?>'},
               { "name": "j_id", "value": '<?php echo $j_id; ?>'},
			   { "name": "stage", "value": '<?php echo $stage; ?>'},
			   { "name": "dept_id", "value": '<?php echo $dept_id; ?>'},
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
							 { "name": "dept_id", "value": '<?php echo $dept_id; ?>'}
						);
          }
				},
				

       
				"aaSorting": [[4, 'desc']],
				"columns": [
					{ "data": "0" },
					{ "data": "1" },
					{ "data": "2" },
					{ "data": "3" },
					{ "data": "4" },
					{ "data": "5" }
				],
        "initComplete": function(settings, json) {
        // Hide the loader once the DataTable is initialized
        $("#articles_processing").css("display", "none");
      },
				dom: "<'row'<'col-sm-2'f><'col-sm-10 voptions no_pad_l text-right'>>" +
					 "<'row'<'col-sm-12'tr>>" +
					 "<'row'<'col-sm-4'l><'col-sm-4 text-center'i><'col-sm-4'p>>",
				 "columnDefs": [
					{ "orderable": false, "targets": 4 }
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
			$('#artconfig').on( 'init.dt', function () {
				$('input[type=search]').val('<?php echo $search; ?>');
				table.search( '<?php echo $search; ?>' );
			});
			<?php
			}
			if($length!='')
			{
			?>
			$('#artconfig').on( 'init.dt', function () {
				table.page.len( <?php echo $length; ?> );
			});
			<?php
			}
			?>	
			$('#artconfig').on('click', 'button[data-delete]', function () {
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
     // window.top.location="artconfig?del="+del;
     window.top.location="artconfig?del="+del;

    } else {
      swal("Cancelled", "Art configuration was not deleted", "error");
    }
	});
			});

      
		});	
</script>
