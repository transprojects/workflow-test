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
     <?php
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
if(isset($_REQUEST['article_id']))
{
	  $article_id=$_REQUEST['article_id'];
}
else
{
	$article_id='';
}
if(isset($_REQUEST['graphics_status']))
{
	  $graphics_status=implode(",",$_REQUEST['graphics_status']);
 	  $ex_graphics_status = explode(",",$graphics_status);
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
	$from_dt='';
}
if(isset($_REQUEST['to_dt']))
{
	  $to_dt=$_REQUEST['to_dt'];
}
else
{
	$to_dt='';
}

	
?>
      <!-- Page body starts -->
      
      <div class="container-fluid" style="justify-content: space-between;">
        <div class="af-page" style="padding-bottom:0;">
          <div class="af-hero">
            <div><small>Needs attention first</small><h1>High Priority Articles</h1></div>
            <a class="af-add" href="<?php echo $_COOKIE['set_url'];?>/add-article"><i class="fa fa-plus"></i> Add Manual</a>
          </div>
        </div>
      </div>

      <div class="af-page"><div class="af-wrap">
        <form action="" name="articles" class="formouter" method="post">
          <div class="af-bar">
<div class="af-field search">
              <label class="mb-2">Article ID</label>
              <input class="form-control" name="article_id" id="article_id" placeholder="Enter Article ID" value="<?php echo $article_id;?>">
              </input>
             </div>
             <div class="af-field date dvr-field">
              <label class="mb-2">Date range</label>
              <div class="dvr" data-from-name="from_dt" data-to-name="to_dt" data-from="<?php echo htmlspecialchars($from_dt); ?>" data-to="<?php echo htmlspecialchars($to_dt); ?>"></div>
             </div>
             <link rel="stylesheet" href="dv-rangepicker.css?v=1"><script src="dv-rangepicker.js?v=1"></script>

            <div class="af-actions">
              <button type="button" class="af-btn" id="afToggle"><i class="fa fa-sliders"></i> Filters <span class="af-count">0</span> <i class="fa fa-angle-down"></i></button>
              <button type="submit" name="btn-wip" class="af-btn primary"><i class="fa fa-search"></i> Get Result</button>
              <a class="af-btn" href="highpriorityarticles" title="Reset filters"><i class="fa fa-refresh"></i></a>
            </div>
          </div>
          <div class="af-panel" id="afPanel">
            <div class="af-grid">
<div class="af-field ">
                <label class="mb-2">Choose Customer</label>
                <select class="form-select customer_drop" data-placeholder="Select Customer" name="cust_id[]" id="cust_id"  multiple="multiple">
                   <?php
											$sql_customers = $auth->showCustomerlist();
											foreach($sql_customers as $res_customers)
											{
												
									      	if(isset($_REQUEST['cust_id']))
													{
										    	?>
										       <option <?php if (in_array($res_customers['id'], $ex_cid)){?>selected<?php }?> value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
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
<div class="af-field ">
                <label class="mb-2">Choose Journal</label>
                <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_id[]" id="j_id"  multiple="multiple">
                    <?php
											$sql_journals = $auth->showJournallist('0',2);
											foreach($sql_journals as $res_journals)
											{
												
									      	if(isset($_REQUEST['j_id']))
													{
										    	?>
										       <option <?php if (in_array($res_journals['j_id'], $ex_jid)){?>selected<?php }?> value="<?php echo $res_journals['j_id'] ?>"style="font-weight:500;"><?php echo $res_journals['j_code'] ?> </option>
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
<div class="af-field ">
                <label class="mb-2">Choose Stage</label>
                <select class="form-select stage_drop" data-placeholder="Select Stage" name="stage[]" id="stage" multiple="multiple">
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
<div class="af-field ">
                <label class="mb-2">Choose Graphics Stage</label>
                <select class="form-select graphics_status_drop" data-placeholder="Select Graphics Stage" name="graphics_status[]" id="graphics_status" multiple="multiple">
				 <?php 
                          if(isset($_REQUEST['graphics_status']))
													{
                          ?>
                    <option value="1" <?php if (in_array('1', $ex_graphics_status)){?>selected<?php }?>>-Yes-</option>
                    <option value="0" <?php if (in_array('0', $ex_graphics_status)){?>selected<?php }?>>-No-</option>
					<?php 
                          }
                          else
                          {
                          ?>
                          <option value="1" >-Yes-</option>
						  <option value="0" >-No-</option>

                          <?php 
                          }
                          ?>
                </select>
              </div>
            </div>
          </div>
          <div class="af-chips" id="afChips"></div>
        </form>
      </div></div>

      <!-- Table section -->

       <div class="af-page"><div class="af-card">
	   <div class="table-responsive">
       <table id="articles" class="table custtable table-primary" style="width: 100% !important; overflow:auto !important;text-wrap: nowrap !important; white-space: nowrap !important">
		<thead>
        <tr>
              <th data-sortable="true" scope="col" class="thcolor" >Action</th>
              <th data-sortable="true" scope="col" class="thcolor" >Client</th>
              <th data-sortable="true" scope="col" class="thcolor">Journal Name</th>
              <th data-sortable="true" scope="col" class="thcolor">File Name</th>
              <th data-sortable="true" scope="col" class="thcolor">Stage</th>
              <th data-sortable="true" scope="col" class="thcolor">Received Date</th>
              <th data-sortable="true" scope="col" class="thcolor">Due Date</th>
              <th data-sortable="true" scope="col" class="thcolor">Status</th>
              <th data-sortable="true" scope="col" class="thcolor">Page Count</th>
              <th class="thcolor">Instruction</th>
              <th class="thcolor">Startwork</th>
    	</tr>
		</thead>
		</table>
		</div></div>
      	</div>



  
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
    //echo ;exit;
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
				$stage = implode(",",$_REQUEST['stage']);
			else
				$stage = '';
			 if(isset($_REQUEST['graphics_status']))
				$graphics_status = implode(",",$_REQUEST['graphics_status']);
			else
				$graphics_status = '';
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
			
			if(isset($_REQUEST['length']))
				$length = $_REQUEST['length'];
			else
				$length = '10';
		?>
		var table
		var tFlag = true;
		$(document).ready(function() {
			var rid   = 2;
			table = $('#articles').DataTable({
				
				
			
			
				"pageLength" : <?php echo $length; ?>,
				"lengthMenu" : [[10, 25, 50, 100, 100000], [10, 25, 50, 100, "All"]],
       
				"displayStart":<?php echo $page; ?>,
				"sServerMethod": "POST", 
				"bProcessing": true,
				"bServerSide": true,
				"sAjaxSource": "get/json-high-priority-articles.php",
				"sSortDir_0": "desc",
				
				
				
				
				"fnServerParams": function ( aoData ) {
					if(tFlag){
						aoData.push(
               { "name": "cust_id", "value": '<?php echo $cust_id; ?>'},
               { "name": "j_id", "value": '<?php echo $j_id; ?>'},
			   { "name": "stage", "value": '<?php echo $stage; ?>'},
			   { "name": "graphics_status", "value": '<?php echo $graphics_status; ?>'},
			   { "name": "article_id", "value": '<?php echo $article_id ?>'},
               { "name": "from_dt", "value": '<?php echo $from_dt; ?>'},
			   { "name": "to_dt", "value": '<?php echo $to_dt; ?>'},               
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
							 { "name": "graphics_status", "value": '<?php echo $graphics_status; ?>'},
							 { "name": "article_id", "value": '<?php echo $article_id ?>'},
							 { "name": "from_dt", "value": '<?php echo $from_dt; ?>'},
							 { "name": "to_dt", "value": '<?php echo $to_dt; ?>'}
						);
          }
				},
				
				

       
				//"aaSorting": [[4, 'asc']],
			"aaSorting": [
                // Sort by column1 with custom values
                [4, 'desc'],
                [5, 'asc'],
                //[0, 'asc', 'value3'],
                // Then, sort by column2 in ascending order
               // [1, 'asc']
                // Add more sorting rules if you have more columns
            ],
			
			"fnSortData": function (aoData, iColumn, bDescending, oSettings) {
                if (iColumn == 0) {
                    // Custom sorting for Column1 based on values
                    return aoData.map(function (data, i) {
                        return { 'value': data[iColumn], 'index': i };
                    });
                } else {
                    // Default sorting for other columns
                    return oSettings.oApi.fnSortData(aoData, iColumn, bDescending, oSettings);
                }
            },
			
			 "fnRowCallback": function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {
      if (aData[12] == "1") {
        $('td', nRow).css('background-color', 'rgb(255, 0, 0,.2)');
		
      }
	  else if (aData[12] == "0") {
        $('td', nRow).css('background-color', '#fff');
		
      }
	  if (aData[13] == "1") {
        $('td', nRow).css('background-color', 'rgb(255, 0, 0,.5)');
		
      }
    },
				
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
					{"data": "10"}


				],
        "initComplete": function(settings, json) {
        // Hide the loader once the DataTable is initialized
        $("#articles_processing").css("display", "none");
      },
				dom: "<'row'<'col-sm-2'f><'col-sm-10 voptions no_pad_l text-right'>>" +
					 "<'row'<'col-sm-12'tr>>" +
					 "<'row'<'col-sm-4'l><'col-sm-4 text-center'i><'col-sm-4'p>>",
				 "columnDefs": [
					// 		{
					// 	 "targets": -1,
					// 	"data": null,
					// 	'render': function (data, type, row) {
					// 	return '<button data-delete="' + row[10] +'" class="btn btn-danger demo4   delete-article" type="button"><i class="ace-icon fa fa-trash-o bigger-120"></i></button>'

					// 	}
					// },
				
					{ "orderable": false, "targets": 7 },
					{ "orderable": false, "targets": 8 },
					{ "orderable": false, "targets": 9 }
					//{ "visible": false, "targets": 12 }
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
			$('#articles').on( 'init.dt', function () {
				$('input[type=search]').val('<?php echo $search; ?>');
				table.search( '<?php echo $search; ?>' );
			});
			<?php
			}
			if($length!='')
			{
			?>
			$('#articles').on( 'init.dt', function () {
				table.page.len( <?php echo $length; ?> );
			});
			<?php
			}
			?>	
			$('#articles').on('click', 'button', function () {
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
     window.top.location="articles?del="+del;

    } else {
      swal("Cancelled", "Article not Deleted", "error");
    }
	});
			});

      
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
	  
    </script>

    <!-- Date picker functionality -->
    <script>
      $(function () {
        // var datesForDisable = ["2017-10-14", "2017-10-15", "2017-10-16"];
        var date = new Date();
        date.setDate(date.getDate() - 1);
        var fromdate;
        $(".monthlyinputdate").datepicker({
          uiLibrary: "bootstrap5",
          format: "dd-mm-yyyy",
          autoclose: true,
         //  minDate: date,
              onSelect: function (dateText, inst) {
             fromdate = dateText;

                    $(".monthlyinputdate1").datepicker("option", "minDate", fromdate);
                }
        });

        $(".monthlyinputdate1").datepicker({
          uiLibrary: "bootstrap5",
          format: "dd-mm-yyyy",
          autoclose: true,
          todayHighlight: true,
          // minDate: date, //For disabling date from today
           onSelect: function (dateText, inst) {
             todate = dateText;
                    $(".monthlyinputdate").datepicker("option", "maxDate", todate);
                }
        });
      });

      


      		// Handle changes in the "countries" select element
    $(document).ready(function() {

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

});
function populateSelecte(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.j_code).val(item.j_id));
            });
        }


        function check_graphics_work_status(id,tid,stage)
			{	
			
				$.ajax(
				{
					type:'POST',
					url:'get_article_graphics_work_status.php',
					data:{id:id,stage:stage},
					success:function(result)
					{
						
						if(result == 0)
						{
							window.location = 'project_fig_work.php?id='+id;					
							return true;
						}
						else
						{
							if(result == 'Please complete all the Inprogress Articles before you start this work')
							{
								bootbox.dialog(
								{
									message: '<br/><div class="alert alert-danger"> <strong> In Progress! </strong>'+ result +'</div>',
									buttons:
							{
								"button" :
								{
									"label" : "Close",
									"className" : "btn-sm",
									"callback": function() {
										//window.location.reload();				
										return true;
									}
								}							
							} 						
								});					
								return false;
							}
							else if(result == 'Please complete all the Pending Articles before you start this work')
							{
								bootbox.dialog(
								{
									message: '<br/><div class="alert alert-danger"> <strong> In Progress! </strong>'+ result +'</div>',
									buttons:
							{
								"button" :
								{
									"label" : "Close",
									"className" : "btn-sm",
									"callback": function() {
										//window.location.reload();				
										return true;
									}
								}							
							} 						
								});					
								return false;
							}
							else
							{
								bootbox.dialog(
								{
									message: '<br/><div class="alert alert-danger"> <strong> In Progress! </strong>'+ result +'</div>',
									buttons:
							{
								
								"danger" :
								{
									"label" : "Take Over!",
									"className" : "btn-sm btn-danger",
									"callback": function() {
										
										window.location = 'project_fig_work.php?id='+id+'&tid='+tid;					
										return true;
									}
								}, 
								"button" :
								{
									"label" : "Close",
									"className" : "btn-sm",
									"callback": function() {
									//	window.location.reload();				
										return true;
									}
								}							
							} 						
								});					
								return false;
							}
						}
					}
				});
			}	
			
			function check_work_status(id,tid,stage)
			{	
			
				$.ajax(
				{
					type:'POST',
					url:'get_article_work_status.php',
					data:{id:id,stage:stage},
					success:function(result)
					{
						
						if(result == 0)
						{
						//	window.location = 'project_work.php?id='+id;		
              //window.location ='<?php echo $_COOKIE['set_url'];?>/startwork/'+id;		
             var wid='new';
              window.location ='<?php echo $_COOKIE['set_url'];?>/startwork/'+id+'/'+wid;					
		
			
							return true;
						}
						else
						{
							if(result == 'Please complete all the Inprogress Articles before you start this work')
							{
								bootbox.dialog(
								{
									message: '<br/><div class="alert alert-danger"> <strong> In Progress! </strong>'+ result +'</div>',
									buttons:
							{
								"button" :
								{
									"label" : "Close",
									"className" : "btn-sm",
									"callback": function() {
										//window.location.reload();				
										return true;
									}
								}							
							} 						
								});					
								return false;
							}
							else if(result == 'Please complete all the Pending Articles before you start this work')
							{
								bootbox.dialog(
								{
									message: '<br/><div class="alert alert-danger"> <strong> In Progress! </strong>'+ result +'</div>',
									buttons:
							{
								"button" :
								{
									"label" : "Close",
									"className" : "btn-sm",
									"callback": function() {
									//	window.location.reload();				
										return true;
									}
								}							
							} 						
								});					
								return false;
							}
							else
							{
								bootbox.dialog(
								{
									message: '<br/><div class="alert alert-danger"> <strong> In Progress! </strong>'+ result +'</div>',
									buttons:
							{
								
								"danger" :
								{
									"label" : "Take Over!",
									"className" : "btn-sm btn-danger",
									"callback": function() {
										
                    //$_COOKIE['set_url'].'/startwork/'.$articles['id'].';
										window.location ='<?php echo $_COOKIE['set_url'];?>/startwork/'+id+'/'+tid;					
										return true;
									}
								}, 
								"button" :
								{
									"label" : "Close",
									"className" : "btn-sm",
									"callback": function() {
										//window.location.reload();				
										return true;
									}
								}							
							} 						
								});					
								return false;
							}
						}
					}
				});
			}	
			
			function timepopup(id)
			{		
       // alert('yrdyd');		
				var url = "<?php echo $_COOKIE['set_url'];?>/project_comments.php?id="+id;
				$("#ppopup").load(url);
			}	
    </script>
  <?php af_foot(); ?>
  </body>
</html>
