<?php include "includes/includes.php";
include_once 'includes/list_ui.php';?>
<?php
$id = isset($_REQUEST['del']) ? (int)$_REQUEST['del'] : 0;
lu_delete($db, 'customer_invoice_config', 'cic_id', 'Customer invoice config', array(), 'invoice_details');
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
    .dataTables_length {
        padding-bottom: 20px;
    }
    </style>

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
                    <p class="articleheading ms-md-4">Customer Invoice Details</p>
                </div>
                <div class="col-12 col-sm-6 col-md-2 mt-5 mt-sm-0">
                    <div class="row d-flex justify-content-sm-end pe-2">
                        <div class="col-3 articlebtns me-3">
                            <a href="<?php echo $_COOKIE['set_url'];?>/add_invoice_details">
                                <button type="button">+ Add New</button>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="divouter mt-4">
        </div>

        <div class="row">

            <div class="span12">
                <?php
if(!empty($_REQUEST['msg']))  {
echo '<div class="alert alert-success">
<button type="button" class="close" data-dismiss="alert">&times;</button>
'. $_REQUEST['msg'] .'</div>';   

}

if(!empty($_REQUEST['err']))  {
echo '<div class="alert alert-warning">
<button type="button" class="close" data-dismiss="alert">&times;</button>
'. $_REQUEST['err'] .'</div>';   

}
?>
            </div>
        </div>

        <?php lu_open('Customer Invoice Details', 'Customer Invoice Details &rsaquo; All', $_COOKIE['set_url'].'/add_invoice_details', '+ Add New'); ?>
       <div class="table-responsive custom-container">
            <table id="dynamic-table" class="table table-striped custtable table-primary"
                style="text-wrap: nowrap; white-space: nowrap !important">
                <thead>
                    <tr>
                        <th class="center"> <label class="pos-rel">
                                <input type="checkbox" class="ace" />
                                <span class="lbl"></span> </label>
                        </th>
                        <th class="hidden-480">Customer Name</th>
                        <th class="hidden-480">Journals</th>
                        <th class="hidden-480">Stage</th>
                        <th class="hidden-480">Division</th>
                        <th> <i class="ace-icon fa fa-clock-o bigger-110 hidden-480"></i> Created On</th>

                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
		$query = "SELECT * FROM `customer_invoice_config` ORDER BY `cic_id` ASC ";
		$customers = $db->query($query) or ($db->error);	
				
		while($row = $customers->fetch_assoc()){ 
		
		/// Get the custoer details //// 
		$query = "SELECT * FROM `adm_customer_master` Where id='".$row['cust_id']."'";
		$customer = $db->query($query) or ($db->error);
		$customer_info= $customer->fetch_assoc()
		?>
                    <tr>
                        <td class="center"><label class="pos-rel">
                                <input type="checkbox" class="ace" />
                                <span class="lbl"></span> </label></td>

                        <td><?php echo $customer_info['cust_name']; ?></td>
                        <td><?php
					if(!empty($row['j_ids']) && !is_null($row['j_ids']))
					{
					$jn_query = "SELECT j_code FROM `adm_journals` where j_id IN (".$row['j_ids'].") ORDER BY `j_name` ASC ";
					$jn_qry_obj = $db->query($jn_query) or ($db->error);
					$jCodeArray = array();
						while($jn_row = $jn_qry_obj->fetch_assoc())
						{ 
							$jCodeArray[] = $jn_row['j_code'];
						}
					
					echo implode(",",$jCodeArray); 
					}
					else 
					{
						echo 'All';
					}
		
		?></td>
                        <td><?php echo $row['stage']; ?></td>
                        <td><?php echo $row['division']; ?></td>
                        <td><?php echo $row['created_on']; ?></td>

                        <td>
                            <div class="hidden-sm hidden-xs action-buttons">
                                <a class="btn btn-warning" title="Edit" href="add_invoice_details.php?id=<?php echo $row['cic_id']; ?>"><i class="fa fa-pencil"></i></a>
                                <button type="button" class="btn btn-danger delete-article" title="Delete" data-delete="<?php echo $row['cic_id']; ?>"><i class="fa fa-trash-o"></i></button>

                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
<?php lu_close(); lu_delete_js('invoice_details', 'invoice configuration'); ?>



        <!-- Footer Section -->
        <?php echo footer(); ?>




        <!-- Footer Section -->
        <?php echo scripts(); ?>

        <script>
        jQuery(function($) {
            //initiate dataTables plugin
            var myTable =
                $('#dynamic-table')
                //.wrap("<div class='dataTables_borderWrap' />")   //if you are applying horizontal scrolling (sScrollX)
                .DataTable({
                    bAutoWidth: false,
                dom: 'lfr<"table-wrapper"t>ip',
                    "aoColumns": [{
                            "bSortable": false
                        },
                        null, null, null, null, null,
                        {
                            "bSortable": false
                        }
                    ],
                    "aaSorting": [],


                    //"bProcessing": true,
                    //"bServerSide": true,
                    //"sAjaxSource": "http://127.0.0.1/table.php"	,

                    //,
                    //"sScrollY": "200px",
                    //"bPaginate": false,

                    //"sScrollX": "100%",
                    //"sScrollXInner": "120%",
                    //"bScrollCollapse": true,
                    //Note: if you are applying horizontal scrolling (sScrollX) on a ".table-bordered"
                    //you may want to wrap the table inside a "div.dataTables_borderWrap" element

                    //"iDisplayLength": 50


                    select: {
                        style: 'multi'
                    }
                });



            $.fn.dataTable.Buttons.defaults.dom.container.className =
                'dt-buttons btn-overlap btn-group btn-overlap';

            new $.fn.dataTable.Buttons(myTable, {
                buttons: [{
                        "extend": "colvis",
                        "text": "<i class='fa fa-search bigger-110 blue'></i> <span class='hidden'>Show/hide columns</span>",
                        "className": "btn btn-white btn-primary btn-bold",
                        columns: ':not(:first):not(:last)'
                    },
                    {
                        "extend": "copy",
                        "text": "<i class='fa fa-copy bigger-110 pink'></i> <span class='hidden'>Copy to clipboard</span>",
                        "className": "btn btn-white btn-primary btn-bold"
                    },
                    {
                        "extend": "csv",
                        "text": "<i class='fa fa-database bigger-110 orange'></i> <span class='hidden'>Export to CSV</span>",
                        "className": "btn btn-white btn-primary btn-bold"
                    },
                    {
                        "extend": "excel",
                        "text": "<i class='fa fa-file-excel-o bigger-110 green'></i> <span class='hidden'>Export to Excel</span>",
                        "className": "btn btn-white btn-primary btn-bold"
                    },
                    {
                        "extend": "pdf",
                        "text": "<i class='fa fa-file-pdf-o bigger-110 red'></i> <span class='hidden'>Export to PDF</span>",
                        "className": "btn btn-white btn-primary btn-bold"
                    },
                    {
                        "extend": "print",
                        "text": "<i class='fa fa-print bigger-110 grey'></i> <span class='hidden'>Print</span>",
                        "className": "btn btn-white btn-primary btn-bold",
                        autoPrint: false,
                        message: 'This print was produced using the Print button for DataTables'
                    }
                ]
            });
            myTable.buttons().container().appendTo($('.tableTools-container'));

            //style the message box
            var defaultCopyAction = myTable.button(1).action();
            myTable.button(1).action(function(e, dt, button, config) {
                defaultCopyAction(e, dt, button, config);
                $('.dt-button-info').addClass('gritter-item-wrapper gritter-info gritter-center white');
            });


            var defaultColvisAction = myTable.button(0).action();
            myTable.button(0).action(function(e, dt, button, config) {

                defaultColvisAction(e, dt, button, config);


                if ($('.dt-button-collection > .dropdown-menu').length == 0) {
                    $('.dt-button-collection')
                        .wrapInner(
                            '<ul class="dropdown-menu dropdown-light dropdown-caret dropdown-caret" />')
                        .find('a').attr('href', '#').wrap("<li />")
                }
                $('.dt-button-collection').appendTo('.tableTools-container .dt-buttons')
            });

            ////

            setTimeout(function() {
                $($('.tableTools-container')).find('a.dt-button').each(function() {
                    var div = $(this).find(' > div').first();
                    if (div.length == 1) div.tooltip({
                        container: 'body',
                        title: div.parent().text()
                    });
                    else $(this).tooltip({
                        container: 'body',
                        title: $(this).text()
                    });
                });
            }, 500);





            myTable.on('select', function(e, dt, type, index) {
                if (type === 'row') {
                    $(myTable.row(index).node()).find('input:checkbox').prop('checked', true);
                }
            });
            myTable.on('deselect', function(e, dt, type, index) {
                if (type === 'row') {
                    $(myTable.row(index).node()).find('input:checkbox').prop('checked', false);
                }
            });




            /////////////////////////////////
            //table checkboxes
            $('th input[type=checkbox], td input[type=checkbox]').prop('checked', false);

            //select/deselect all rows according to table header checkbox
            $('#dynamic-table > thead > tr > th input[type=checkbox], #dynamic-table_wrapper input[type=checkbox]')
                .eq(0).on('click', function() {
                    var th_checked = this.checked; //checkbox inside "TH" table header

                    $('#dynamic-table').find('tbody > tr').each(function() {
                        var row = this;
                        if (th_checked) myTable.row(row).select();
                        else myTable.row(row).deselect();
                    });
                });

            //select/deselect a row when the checkbox is checked/unchecked
            $('#dynamic-table').on('click', 'td input[type=checkbox]', function() {
                var row = $(this).closest('tr').get(0);
                if (this.checked) myTable.row(row).deselect();
                else myTable.row(row).select();
            });



            $(document).on('click', '#dynamic-table .dropdown-toggle', function(e) {
                e.stopImmediatePropagation();
                e.stopPropagation();
                e.preventDefault();
            });



            //And for the first simple table, which doesn't have TableTools or dataTables
            //select/deselect all rows according to table header checkbox
            var active_class = 'active';
            $('#simple-table > thead > tr > th input[type=checkbox]').eq(0).on('click', function() {
                var th_checked = this.checked; //checkbox inside "TH" table header

                $(this).closest('table').find('tbody > tr').each(function() {
                    var row = this;
                    if (th_checked) $(row).addClass(active_class).find('input[type=checkbox]')
                        .eq(0).prop('checked', true);
                    else $(row).removeClass(active_class).find('input[type=checkbox]').eq(0)
                        .prop('checked', false);
                });
            });

            //select/deselect a row when the checkbox is checked/unchecked
            $('#simple-table').on('click', 'td input[type=checkbox]', function() {
                var $row = $(this).closest('tr');
                if ($row.is('.detail-row ')) return;
                if (this.checked) $row.addClass(active_class);
                else $row.removeClass(active_class);
            });










        })

        function confirmDelete(ctid) {
            bootbox.confirm("Are you sure you want to delete?", function(res) {
                if (res) {
                    document.location = 'invoice_details.php?action=delete&id=' + ctid;
                    return true;
                }
            });
        }
        </script>



</body>

</html>