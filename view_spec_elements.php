<?php include "includes/includes.php";
include_once 'includes/list_ui.php';
include_once 'includes/change_log_mail.php';
lu_delete($db, 'journal_spec_master', 'js_id', 'Spec element', array('auto:js_id'), 'journalspecelements');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php lu_css(); ?>	
</head>

<body>
<div class="wholeouter">
    <?php include("includes/top-header.php");?>

    <!-- Page Header -->
    <div class="container-fluid" style="justify-content: space-between;">
        <div class="row pt-4 d-flex align-items-center flex-wrap">
            <div class="col-12 col-sm-6 col-md-10">
                <p class="articleheading ms-md-4">Journal Specification Master</p>
            </div>
            <div class="col-12 col-sm-6 col-md-2 mt-5 mt-sm-0">
                <div class="row d-flex justify-content-sm-end pe-2">
                    <div class="col-3 articlebtns">
                        <a href="<?php echo $_COOKIE['set_url'];?>/add-journal-specification">
                            <button type="button">+ Add Specification</button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Section -->
    <?php lu_open('Journal Specification Master', 'Journal Specification Master &rsaquo; All', $_COOKIE['set_url'].'/add-journal-specification', '+ Add Specification'); ?>
       <div class="table-responsive custom-container">

        <table id="journal_spec_master" class="table table-striped custtable table-primary"
               style="text-wrap: nowrap; white-space: nowrap !important">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Style</th>
                    <th>Category</th>
                    <th>Element Name</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Created On</th>
                    <th>Action</th>
                </tr>
            </thead>
        </table>
    </div>
<?php lu_close(); ?>

    <?php echo footer(); ?>
</div>

<?php echo scripts(); ?>

<script>
<?php
    $page = isset($_REQUEST['page']) ? $_REQUEST['page'] * 10 : 0;
    $search = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';
    $length = isset($_REQUEST['length']) ? $_REQUEST['length'] : 10;
?>
var table;
var tFlag = true;

$(document).ready(function() {
    table = $('#journal_spec_master').DataTable({
        "pageLength": <?php echo $length; ?>,
        "lengthMenu": [[10, 25, 50, 100, 100000], [10, 25, 50, 100, "All"]],
        "displayStart": <?php echo $page; ?>,
        "sServerMethod": "POST",
        "bProcessing": true,
        "bServerSide": true,
        "sAjaxSource": "get/json-journal-spec-master.php",
        "sSortDir_0": "desc",

        "fnServerParams": function (aoData) {
            if (tFlag) {
                aoData.push({ "name": "sSearch", "value": '<?php echo $search ?>' });
                tFlag = false;
            }
        },

        "aaSorting": [[0, 'asc']],
        "columns": [
            { "data": "0" },  // Customer Name
            { "data": "1" },  // Style
            { "data": "2" },  // Category
            { "data": "3" },  // Element
            { "data": "4" },  // Description
            { "data": "5" },  // Status
            { "data": "6" },  // Created On
            { "data": "7" }   // Action Buttons
        ],

        dom: 'Blfr<"table-wrapper"t>ip',
        buttons: [
            {
                extend: 'collection',
                text: 'Export',
                buttons: [
                    'copyHtml5',
                    'excelHtml5',
                    'csvHtml5',
                    {
                        extend: 'pdfHtml5',
                        orientation: 'landscape',
                        pageSize: 'A4'
                    },
                    {
                        extend: 'print',
                        autoPrint: false
                    }
                ]
            }
        ],

        "columnDefs": [
            { "orderable": false, "targets": 7 }
        ],

        "initComplete": function(settings, json) {
            $("#articles_processing").css("display", "none");
        }
    });

    table.on('xhr.dt', function (e, s, json) { if (json) { $('#lu-count').text(json.iTotalDisplayRecords != null ? json.iTotalDisplayRecords : json.recordsFiltered); } });
    table.on('page.dt', function() {
        $('html, body').animate({
            scrollTop: $(".dataTables_wrapper").offset().top - 500
        }, '1000');
        $('thead tr th:first-child').focus().blur();
    });

    <?php if($search!=''){ ?>
    $('#journal_spec_master').on('init.dt', function () {
        $('input[type=search]').val('<?php echo $search; ?>');
        table.search('<?php echo $search; ?>');
    });
    <?php } ?>

    <?php if($length!=''){ ?>
    $('#journal_spec_master').on('init.dt', function () {
        table.page.len(<?php echo $length; ?>);
    });
    <?php } ?>

    // Delete Confirmation
    $('#journal_spec_master').on('click', 'button.delete-spec', function () {
        var del = $(this).attr('data-delete');
        swal({
            title: "Are you sure?",
            text: "This record will be permanently deleted!",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: '#DD6B55',
            confirmButtonText: 'Proceed!',
            cancelButtonText: "No, cancel please!",
            closeOnConfirm: false,
            closeOnCancel: false
        }, function(isConfirm) {
            if (isConfirm) {
                swal("Deleted!", "Deleting, please wait...", "success");
                window.top.location = "journalspecelements?del=" + del;
            } else {
                swal("Cancelled", "Specification not deleted", "error");
            }
        });
    });
});
</script>

</body>
</html>
