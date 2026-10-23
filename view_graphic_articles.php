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
    <style>
      /* Current Graphics: file name carries customer, journal and group folder; Fig / Table in one cell; other team status under the status */
      .gf-file b{display:block;color:#14304a;font-size:13.5px;}
      .gf-file small{display:block;color:#6b7785;font-size:11.5px;margin-top:1px;font-weight:500;}
      .gf-chip{display:inline-block;margin-right:5px;padding:2px 9px;border-radius:12px;font-size:11.5px;font-weight:700;line-height:1.5;}
      .gf-chip.pend{background:#fff1dc;color:#b86a00;border:1px solid #f6d3a0;}
      .gf-chip.done{background:#e3f6ec;color:#177a49;border:1px solid #b9e4cb;}
      .gf-none{color:#aab4c0;font-size:12px;}
      .gf-yn{display:inline-block;padding:2px 10px;border-radius:12px;font-size:11.5px;font-weight:700;}
      .gf-yn.yes{background:#e3f6ec;color:#177a49;} .gf-yn.no{background:#eef1f5;color:#6b7785;}
      .gf-oth{margin-top:5px;font-size:11px;color:#6b7785;}
      .gf-oth i{font-style:normal;font-weight:700;letter-spacing:.04em;text-transform:uppercase;font-size:10px;color:#7a4fb5;margin-right:6px;}
      /* table polish: even column widths, tidy cells, nothing stretched by the status chips */
      #articles{table-layout:auto;}
      #articles thead th{white-space:nowrap;vertical-align:middle;}
      #articles tbody td{vertical-align:middle !important;}
      #articles th:nth-child(1),#articles td:nth-child(1){width:104px;}
      #articles th:nth-child(2),#articles td:nth-child(2){min-width:230px;max-width:290px;}
      #articles th:nth-child(3),#articles td:nth-child(3),#articles th:nth-child(4),#articles td:nth-child(4){width:118px;}
      #articles th:nth-child(5),#articles td:nth-child(5){width:150px;}
      #articles th:nth-child(6),#articles td:nth-child(6){width:84px;}
      #articles th:nth-child(7),#articles td:nth-child(7){min-width:210px;max-width:300px;white-space:normal !important;}
      #articles th:nth-child(8),#articles td:nth-child(8){width:90px;text-align:center;}
      #articles th:nth-child(9),#articles td:nth-child(9){width:118px;}
      #articles th:nth-child(10),#articles td:nth-child(10){width:100px;}
      .gf-file{max-width:280px;}
      .gf-file b,.gf-file small{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
      #articles td:nth-child(7) .af-stat-wrap{display:flex;flex-wrap:wrap;gap:4px;align-items:center;}
      .gf-oth{display:flex;flex-wrap:wrap;gap:4px;align-items:center;padding-top:5px;margin-top:5px;border-top:1px dashed #e3e8ef;}
      .gf-oth>i{flex:0 0 100%;margin:0 0 1px;}
      .gf-oth .af-status,.gf-oth .af-sub{margin:0;}
      .gf-chip{margin:0 4px 3px 0;}
    </style>

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
   <style>
  .alert {
    max-width: 300px; /* Adjust the maximum width as needed */
    padding: 10px; /* Add some padding for better spacing */
    margin: 10px auto; /* Center the div horizontally */
  }
  .hide {
  display: none;
}
.close {
    position: absolute;
    top: 5px;
    right: 5px;
    padding: 5px;
    background-color: transparent;
    border: none;
    cursor: pointer;
  }

  .close:hover {
    color: #000;
  }
</style>   
      <div class="container-fluid" style="justify-content: space-between;">
	  
	   <?php
  if(!empty($_REQUEST['msg']))  {
    echo '<div id="successMessage" class="alert alert-success">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      '. $_REQUEST['msg'] .'</div>';   
  }
  ?> 
        <div class="af-page" style="padding-bottom:0;">
          <div class="af-hero">
            <div><small>Graphics</small><h1>Current Graphics Articles</h1></div>
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
              <a class="af-btn" href="graphicarticles" title="Reset filters"><i class="fa fa-refresh"></i></a>
            </div>
          </div>
          <div class="af-panel" id="afPanel">
            <div class="af-grid">
<div class="af-field ">
                <label class="mb-2">Choose Customer</label>
 <select class="form-select customer_drop" data-placeholder="Select Customer" name="cust_id[]" id="cust_id"  multiple="multiple">
												    <option value="all">Select All</option>

													<?php
													if($_COOKIE['user_level']=='1')
													{
														$sql_customers = $auth->showCustomerlist();
													}
													else
													{
														$sql_customers = $auth->showCustomerlistbyuser($_COOKIE['cust_id'],$_COOKIE['ocust_id']);
													}
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
					<option value="all">Select All</option>

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
								    <option value="all">Select All</option>

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
								    <option value="all">Select All</option>
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

       <div class="af-page">
         <div class="af-tabs" id="afTabs" role="tablist" aria-label="Due date">
           <button type="button" class="af-tab active" data-tab="all"><i class="fa fa-th-list"></i> All <b>0</b></button>
           <button type="button" class="af-tab high" data-tab="high"><i class="fa fa-flag"></i> High priority <b>0</b></button>
           <button type="button" class="af-tab overdue" data-tab="overdue"><i class="fa fa-exclamation-triangle"></i> Overdue <b>0</b></button>
           <button type="button" class="af-tab today" data-tab="today"><i class="fa fa-clock-o"></i> Due today <b>0</b></button>
           <button type="button" class="af-tab d1" data-tab="d1"><i class="fa fa-calendar-o"></i> Due in 1 day <b>0</b></button>
           <button type="button" class="af-tab d2" data-tab="d2"><i class="fa fa-calendar-o"></i> Due in 2 days <b>0</b></button>
           <button type="button" class="af-tab d3" data-tab="d3"><i class="fa fa-calendar-o"></i> Due in 3 days <b>0</b></button>
           <button type="button" class="af-tab d4" data-tab="d4"><i class="fa fa-calendar-check-o"></i> Due in 4+ days <b>0</b></button>
           <span class="af-tab-hint">Listed by due date, earliest first &middot; High priority = flagged articles</span>
         </div>
       </div>
       <div class="af-page"><div class="af-card">
	   <div class="table-responsive">
       <table id="articles" class="table custtable table-primary" style="width: 100% !important; overflow:auto !important;text-wrap: nowrap !important;white-space: nowrap !important">
		<thead>
        <tr>
              <th scope="col" class="thcolor" >Action</th>
              <th data-sortable="true" scope="col" class="thcolor">File Name</th>
              <th data-sortable="true" scope="col" class="thcolor">Received Date</th>
              <th data-sortable="true" scope="col" class="thcolor">Due Date</th>
              <th data-sortable="true" scope="col" class="thcolor">Fig / Table</th>
              <th data-sortable="true" scope="col" class="thcolor">Stage</th>
              <th data-sortable="true" data-af-role="gfstatus" scope="col" class="thcolor">Status</th>
              <th data-sortable="true" scope="col" class="thcolor">Graphics</th>
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
			 if(isset($_REQUEST['graphics_status']))
			 {
$graphics_status = $_REQUEST['graphics_status'];
$graphics_status = array_filter($graphics_status, function($value) {
    return $value !== "all";
});
$graphics_status = implode(",", $graphics_status);
			 }

			else
			{
				$graphics_status = '';
			}
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
		var afTab = 'all';
		$(document).ready(function() {
			var rid   = 2;
			table = $('#articles').DataTable({
				
				
			
			
				"pageLength" : <?php echo $length; ?>,
				"lengthMenu" : [[10, 25, 50, 100, 100000], [10, 25, 50, 100, "All"]],
       
				"displayStart":<?php echo $page; ?>,
				"sServerMethod": "POST", 
				"bProcessing": true,
				"bServerSide": true,
				"sAjaxSource": "get/json-garticles.php",
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
               { "name": "sSearch", "value": '<?php echo $search ?>'},
               { "name": "ptab", "value": afTab }
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
							 { "name": "to_dt", "value": '<?php echo $to_dt; ?>'},
								 { "name": "ptab", "value": afTab }
						);
          }
				},
				
				

       
				//"aaSorting": [[4, 'asc']],
			"aaSorting": [],   // earliest due date first comes from the server; clicking a header still sorts by that column
			
			"fnSortData": function (aoData, iColumn, bDescending, oSettings) {
                if (iColumn == 0) {
                    // Custom sorting for Column1 based on values
                    return aoData.map(function (data, i) {
                        return { 'value': data[iColumn], 'index': i };
                    });
                }else if (iColumn == 1) {
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
      if (aData[11] == "1") {
        $('td', nRow).css('background-color', '#d9edf7');
		
      }
	  else if (aData[11] == "0") {
        $('td', nRow).css('background-color', '#fff');
		
      }
	  if (aData[12] == "1") {
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
					// 		{
					// 	 "targets": -1,
					// 	"data": null,
					// 	'render': function (data, type, row) {
					// 	return '<button data-delete="' + row[10] +'" class="btn btn-danger demo4   delete-article" type="button"><i class="ace-icon fa fa-trash-o bigger-120"></i></button>'

					// 	}
					// },
				
						{ "orderable": false, "targets": 0 },
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


			
			/* due-date tabs: counts come back with every response, a click reloads the table on that tab */
			$('#articles').on('xhr.dt', function (e, settings, json) {
				if (!json || !json.tabCounts) { return; }
				$('#afTabs .af-tab').each(function () {
					var n = json.tabCounts[$(this).data('tab')] || 0;
					$(this).find('b').text(n.toLocaleString()).toggleClass('zero', n === 0);
				});
			});
			$('#afTabs').on('click', '.af-tab', function () {
				afTab = $(this).data('tab');
				$('#afTabs .af-tab').removeClass('active'); $(this).addClass('active');
				table.page(0).draw(false);
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
		 
		if($(this).val() && $(this).val().indexOf('all') !== -1){
            // Clear all other selections
            $('#cust_id').val([]);
            // Select all options except the "Select All" option
            $('#cust_id option').not(':first').prop('selected', true);
        } else {
            // If any other option is selected, unselect "Select All"
            if ($(this).val() && $(this).val().length === $('option', this).not(':first').length) {
                $('#cust_id option[value=all]').prop('selected', false);
            }
        }

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
	 
	 $("#j_id").on("change", function() {
         var j_id=$.trim($(this).val());
		 
		if($(this).val() && $(this).val().indexOf('all') !== -1){
            // Clear all other selections
            $('#j_id').val([]);
            // Select all options except the "Select All" option
            $('#j_id option').not(':first').prop('selected', true);
        } else {
            // If any other option is selected, unselect "Select All"
            if ($(this).val() && $(this).val().length === $('option', this).not(':first').length) {
                $('#j_id option[value=all]').prop('selected', false);
            }
        }
        
     });
	 
	 	 $("#graphics_status").on("change", function() {
         var graphics_status=$.trim($(this).val());
		 
		if($(this).val() && $(this).val().indexOf('all') !== -1){
            // Clear all other selections
            $('#graphics_status').val([]);
            // Select all options except the "Select All" option
            $('#graphics_status option').not(':first').prop('selected', true);
        } else {
            // If any other option is selected, unselect "Select All"
            if ($(this).val() && $(this).val().length === $('option', this).not(':first').length) {
                $('#graphics_status option[value=all]').prop('selected', false);
            }
        }
        
     });
	 
	 	 	 $("#stage").on("change", function() {
         var stage=$.trim($(this).val());
		 
		if($(this).val() && $(this).val().indexOf('all') !== -1){
            // Clear all other selections
            $('#stage').val([]);
            // Select all options except the "Select All" option
            $('#stage option').not(':first').prop('selected', true);
        } else {
            // If any other option is selected, unselect "Select All"
            if ($(this).val() && $(this).val().length === $('option', this).not(':first').length) {
                $('#stage option[value=all]').prop('selected', false);
            }
        }
        
     });

});
function populateSelecte(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();
			// Prepend "Select All" option
			selectElement.append($("<option>").text("Select All").val("all"));
	$.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.j_code).val(item.j_id));
            });
        }


        function check_graphics_work_status(id,tid,stage)
			{	
			//console.log(id);
			//console.log(tid);
			//console.log(stage);exit;
			
				$.ajax(
				{
					type:'POST',
					url:'<?php echo $_COOKIE['set_url'];?>/get_article_graphics_work_status.php',
					data:{id:id,stage:stage},
					success:function(result)
					{
						
						if(result == 0)
						{
							//window.location = 'project_fig_work.php?id='+id;	
							var wid='new';
							//window.location ='<?php echo $_COOKIE['set_url'];?>/graphicstartwork/'+id+'/'+wid;	
// Get the URL from the cookie
										var url = '<?php echo $_COOKIE['set_url'];?>/graphicstartwork/'+id+'/'+wid;

										// Open the URL in a new tab
										var newTab = window.open(url, '_blank');
										newTab.focus();										
										return true;							
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
										
										//window.location = 'project_fig_work.php?id='+id+'&tid='+tid;		
										//window.location ='<?php echo $_COOKIE['set_url'];?>/graphicstartwork/'+id+'/'+tid;	
									// Get the URL from the cookie
										var url = '<?php echo $_COOKIE['set_url'];?>/graphicstartwork/'+id+'/'+tid;

										// Open the URL in a new tab
										var newTab = window.open(url, '_blank');
										newTab.focus();										
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
					url:'<?php echo $_COOKIE['set_url'];?>/get_article_graphics_work_status.php',
					data:{id:id,stage:stage},
					success:function(result)
					{
						
						if(result == 0)
						{
						//	window.location = 'project_work.php?id='+id;		
              //window.location ='<?php echo $_COOKIE['set_url'];?>/startwork/'+id;		
             var wid='new';
             // window.location ='<?php echo $_COOKIE['set_url'];?>/startwork/'+id+'/'+wid;					
				var url = '<?php echo $_COOKIE['set_url'];?>/startwork/'+id+'/'+wid;

										// Open the URL in a new tab
										var newTab = window.open(url, '_blank');
										newTab.focus();		
			
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
										//window.location ='<?php echo $_COOKIE['set_url'];?>/startwork/'+id+'/'+tid;		

										var url = '<?php echo $_COOKIE['set_url'];?>/startwork/'+id+'/'+tid;

										// Open the URL in a new tab
										var newTab = window.open(url, '_blank');
										newTab.focus();														
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
		<script>
  setTimeout(function() {
    var successMessage = document.getElementById('successMessage');
    if (successMessage) {
      successMessage.classList.add('hide');
    }
  }, 5000); // Hide after 5 seconds (5000 milliseconds)
</script>



  <?php af_foot(); ?>
  </body>
</html>
