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
                    <p class="articleheading ms-md-4">Invoice List</p>
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

        <?php lu_open('Submit Invoice', 'Accounts &rsaquo; Submit Invoice', '#', ''); ?>
       <div class="table-responsive custom-container">
            <table id="dynamic-table" class="table table-striped custtable table-primary"
                style="text-wrap: nowrap; white-space: nowrap !important">
                <thead>
                    <tr>
                        <th class="center">S.No</th>
                        <th class="hidden-480">Invoice Number</th>
                        <th> <i class="ace-icon fa fa-clock-o bigger-110 hidden-480"></i> Invoice Date</th>
                        <th> Currency</th>
                        <th> Invoice Amount</th>
                        <th> Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
		$query = "SELECT * FROM `invoice_details` WHERE status != '1' ORDER BY `id` DESC ";
		$customers = $db->query($query) or ($db->error);	
				$sno = 1;
		while($row = $customers->fetch_assoc()){ ?>
                    <tr>
                        <td class="center"><label class="pos-rel"><?php echo $sno; ?></td>
                        <td><?php echo $row['inv_invoice_number']; ?></td>
                        <td><?php echo $row['inv_invoice_date']; ?></td>
                        <td><?php echo $row['currency']; ?></td>
                        <td><?php echo $row['total_amount']; ?></td>
                        <td>
                            <?php
							if($row['status'] == 1)
							{
								echo '<span class="inv-st s1">Approval Pending at Account Manager</span>';
							}
							else if($row['status'] == 2)
							{
								echo '<span class="inv-st s2">Approval Pending at MD</span>';
							}							
							else if($row['status'] == 3)
							{
								echo '<span class="inv-st s3">Invoice Submitted</span>';
							}
						?>
                        </td>
                        <td>
                            <div class="hidden-sm hidden-xs action-buttons">

                                <a title="View Invoice Details"
                                    href="create_invoice_pdf_view.php?id=<?php echo $row['id']; ?>"
                                    class="btn btn-xs btn-primary"> <i class="ace-icon fa fa-eye bigger-120"></i></a>
                                <a title="Download PDF File" href="invoice_download.php?id=<?php echo $row['id']; ?>"
                                    class="btn btn-xs btn-primary"> <i
                                        class="ace-icon fa fa-file-pdf-o bigger-120"></i></a>
                                <a title="Download XLSX File"
                                    href="create_invoice_xlsx_download.php?id=<?php echo $row['id']; ?>"
                                    class="btn btn-xs btn-primary"> <i
                                        class="ace-icon fa fa-file-excel-o bigger-120"></i></a>
                                <?php if($row['status'] == 2)
					{
						?>

                                <a class="blue" id="blue" title="Clone the Customer configuration"
                                    href="submit_invoices_pdf.php?id=<?php echo $row['id']; ?>"> Update / Submit </a>
                                <?php 
					}
					
					?>
                            </div>
                        </td>
                    </tr>
                    <?php $sno++;
				  }
				  ?>
                </tbody>
            </table>
        </div>
<?php lu_close(); ?>



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
        </script>



</body>

</html>