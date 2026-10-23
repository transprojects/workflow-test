<?php include "includes/includes.php";
ini_set('max_execution_time', 0); 

if(isset($_REQUEST['id']))
{
	$result = $db->query("SELECT * FROM invoice_details WHERE id = ".(int)$_REQUEST['id']." "); 	
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$invoice_info = $result->fetch_assoc();
		
		$query = "SELECT * FROM `invoice_article_page_count_details` WHERE inv_id = ".$invoice_info['id']." ORDER BY `inv_id` ASC;";
		$page_counts = $db->query($query) or ($db->error);
		
		
	}
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
    <style>
    .dataTables_length {
        padding-bottom: 20px;
    }
    </style>
    <style>
    table,
    th,
    td {
        border: 1px solid black;
        border-collapse: collapse;
        padding: 6px;
        text-align: center;
    }

    .align_left {
        text-align: left;
    }

    input,
    textarea {
        width: 100%;
        color: black !important;
        border: none !important;
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
                    <p class="articleheading ms-md-4">Invoice</p>
                </div>
            </div>
        </div>


        <div class="page-content">
            <!-- /.page-header -->
            <div class="row">
                <div class="span12">
                    <?php
        if(!empty($_REQUEST['msg']))  {
        echo '<div class="alert alert-success">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        '. $_REQUEST['msg'] .'</div>';   

        } ?>
                </div>
            </div>
            <form action="#" id="validation-form" method="post" autocomplete="off">
                <div class="row">
                    <br>
                    <center>

                        <img style="height:50px;" src="assets/images/trans-logo.png" alt=" Intranet" title=" Intranet">
                        <br />
                        <br />
                        <br />

                        <table>
                            <colgroup width="300px">
                                <colgroup width="200px">
                                    <colgroup width="200px">
                                        <colgroup width="150px">
                                            <colgroup width="150px">
                                            </colgroup>
                                        <tr>
                                            <th colspan="5">EXPORT INVOICE</th>

                                        </tr>
                                    <tr>
                                        <th>Name of the Supplier</th>
                                        <th colspan="2">Name of the Customer</th>
                                        <th>Invoice No.</th>
                                        <th>Invoice Date</th>
                                    </tr>
                                <tr>
                                    <td><?php echo $invoice_info['inv_supplier_address']; ?></td>
                                    <td colspan="2"><?php echo $invoice_info['inv_customer_address']; ?></td>
                                    <td rowspan="3"><?php echo $invoice_info['inv_invoice_number']; ?></td>
                                    <td rowspan="3"><?php echo $invoice_info['inv_invoice_date']; ?></td>
                                </tr>
                            <tr>
                                <th>PAN NO. <?php echo $invoice_info['inv_supplier_pan']; ?></th>
                                <th colspan="2">Name of the Project/Work</th>
                            </tr>
                            <tr>
                                <th>CIN: <?php echo $invoice_info['inv_supplier_cin']; ?></th>
                                <td colspan="2"><?php echo $invoice_info['inv_type_of_project']; ?></td>
                            </tr>
                            <tr>
                                <th>GSTIN: <?php echo $invoice_info['inv_supplier_gstin']; ?></th>
                                <td colspan="2"><?php echo $invoice_info['inv_invoice_detail']; ?></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <th>Import Export Code No.</th>
                                <th>Satellite Station</th>
                                <th>Destination</th>
                                <th colspan="2">Purchase Order No./Date</th>
                            </tr>
                            <tr>
                                <td><?php echo $invoice_info['inv_import_export_code_no']; ?></td>
                                <td><?php echo $invoice_info['inv_satellite_station']; ?></td>
                                <td><?php echo $invoice_info['inv_destination']; ?></td>
                                <td colspan="2"><?php echo $invoice_info['inv_purchase_order_details']; ?></td>
                            </tr>

                            <tr>
                                <th>Description</th>
                                <th>No. of Units (Pages / Duration)</th>
                                <th>Rate/article</th>
                                <th>Currency</th>
                                <th>Amount</th>
                            </tr>
                            <?php
				while($page_counts_info = $page_counts->fetch_assoc())
				{
					
			?>
                            <tr>
                                <td><?php echo $page_counts_info['article_details']; ?></td>
                                <td><?php echo $page_counts_info['page_count']; ?></td>
                                <td><?php echo $page_counts_info['rate']; ?></td>
                                <td><?php echo $page_counts_info['currency']; ?></td>
                                <td><?php echo $page_counts_info['amount']; ?></td>
                            </tr>
                            <?php
				}
			?>


                            <tr>
                                <td>&#160;</td>
                                <td>&#160;</td>
                                <td>&#160;</td>
                                <td>&#160;</td>
                                <td>&#160;</td>
                            </tr>

                            <tr>
                                <th></th>
                                <th></th>
                                <th>Total</th>
                                <th><?php echo $invoice_info['currency']; ?></th>
                                <th><?php echo $invoice_info['total_amount']; ?></th>
                            </tr>
                            <tr>
                                <th class="align_left">Amount in Words</th>
                                <td colspan="4" class="align_left"><?php echo $invoice_info['inv_amount_in_words']; ?>
                                </td>
                            </tr>

                            <tr>
                                <th class="align_left">Beneficiay Name</th>
                                <td colspan="4" class="align_left"><?php echo $invoice_info['inv_beneficiay_name']; ?>
                                </td>
                            </tr>

                            <tr>
                                <th class="align_left">Beneficiay Account No.</th>
                                <td colspan="4" class="align_left">
                                    <?php echo $invoice_info['inv_beneficiay_account_no']; ?></td>
                            </tr>

                            <tr>
                                <th class="align_left">Beneficiay Bank Name</th>
                                <td colspan="4" class="align_left"><?php echo $invoice_info['inv_hdfc_bank_name']; ?>
                                </td>
                            </tr>

                            <tr>
                                <th class="align_left">HDFC Bank SWIFT Code</th>
                                <td colspan="4" class="align_left">
                                    <?php echo $invoice_info['inv_hdfc_bank_swift_code']; ?></td>
                            </tr>

                            <tr>
                                <th class="align_left">HDFC Bank IFSC</th>
                                <td colspan="4" class="align_left"><?php echo $invoice_info['inv_hdfc_bank_ifsc']; ?>
                                </td>
                            </tr>

                            <tr>
                                <th class="align_left">Correspondance Bank Details</th>
                                <td colspan="4" class="align_left">
                                    <?php echo $invoice_info['inv_correspondance_bank_details']; ?></td>
                            </tr>

                            <tr>
                                <th class="align_left">Correspondance Bank Name</th>
                                <td colspan="4" class="align_left">
                                    <?php echo $invoice_info['inv_correspondance_bank_name']; ?></td>
                            </tr>

                            <tr>
                                <th class="align_left">Correspondance Bank SWIFT Code</th>
                                <td colspan="4" class="align_left">
                                    <?php echo $invoice_info['inv_correspondance_bank_swift_code']; ?></td>
                            </tr>

                            <tr>
                                <td colspan="5" class="align_left">
                                    <?php echo $invoice_info['inv_invoice_vaild_details']; ?></td>
                            </tr>


                        </table>

                        <div class="space-2"></div>
                        <div class="space-2"></div>
                        <div class="space-2"></div>
                        <div class="form-group">
                            <span style="float:left;margin-left:200px;margin-top:20px;">
                                <span style="float:left;">Date: <?php echo $invoice_info['inv_invoice_date']; ?></span>
                                <br />
                                <span style="float:left;">Chennai - 107</span>
                            </span>
                            <span style="float:right;margin-right:400px;margin-top:20px;">
                                For Transforma Pvt Ltd
                                <br />
                                <img src="<?php echo $_COOKIE['set_url'];?>/assets/images/images.svg" width="60px"
                                    height="60px" />
                                <br />
                                AUTHORISED SIGNATORY
                            </span>
                        </div>

                        <div class="space-2"></div>
                        <div class="space-2"></div>
                        <div class="space-2"></div>
                        <div class="form-group" style="margin-top:150px;">
                            <a href="create_invoice_pdf_list.php" class="btn btn-info">BACK</a>
                        </div>

                </div>
                </center>
            </form>


            <!-- /.row -->
        </div>


    </div>

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