<?php include "includes/includes.php";
include_once 'includes/list_ui.php';
include_once "includes/change_log_mail.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php lu_css(); ?>	

    <style>
    .message-content {
        max-width: 300px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: inline-block;
    }
    </style>
</head>

<?php 
// ✅ Delete Record Logic
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);

    // Snapshot before delete (for change-log email)
    $__old = change_log_snapshot('journal_spec_details', 'id', $id);

    mysqli_begin_transaction($db);
    try {
        mysqli_query($db, "DELETE FROM journal_spec_history WHERE journal_spec_details_id = $id");
        mysqli_query($db, "DELETE FROM journal_spec_details WHERE id = $id");

        mysqli_commit($db);
        log_row_deleted('Journal Spec Description', $id, $__old);
        header("Location: journalspecdescriptions?msg=Deleted successfully");
        exit;
    } catch (Exception $e) {
        mysqli_rollback($db);
        header("Location: journalspecdescriptions?msg=Error deleting element");
        exit;
    }
}
?>

<body>
<div class="wholeouter">

    <?php include("includes/top-header.php");?>

    <!-- Page header -->
    <div class="container-fluid" style="justify-content: space-between;">
        <div class="row pt-4 d-flex align-items-center flex-wrap">
            <div class="col-12 col-sm-6 col-md-10">
                <p class="articleheading ms-md-4">Journal Specification Details</p>
            </div>
            <div class="col-12 col-sm-6 col-md-2 mt-5 mt-sm-0">
                <div class="row d-flex justify-content-sm-end pe-2">
                    <div class="col-3 articlebtns">
                        <a href="<?php echo $_COOKIE['set_url'];?>/addjournalspecdescriptions">
                            <button type="button">+ Add Description</button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Section -->
    <?php lu_open('Journal Specification Details', 'Journal Specification Details &rsaquo; All', $_COOKIE['set_url'].'/addjournalspecdescriptions', '+ Add Description'); ?>
       <div class="table-responsive custom-container">

        <table id="journal_spec_details" class="table table-striped custtable table-primary"
               style="white-space: nowrap;">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Style</th>
                    <th>Category</th>
                    <th>Element</th>
                    <th>Description</th>
                    <th>Version</th>
                    <th>Updated At</th>
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
    table = $('#journal_spec_details').DataTable({
        "pageLength": <?php echo $length; ?>,
        "lengthMenu": [[10, 25, 50, 100, 100000], [10, 25, 50, 100, "All"]],
        "displayStart": <?php echo $page; ?>,
        "sServerMethod": "POST",
        "bProcessing": true,
        "bServerSide": true,
        "sAjaxSource": "get/json-journal-spec-details.php",  // ✅ Updated AJAX source
        "sSortDir_0": "desc",

        "fnServerParams": function (aoData) {
            if (tFlag) {
                aoData.push({ "name": "sSearch", "value": '<?php echo $search ?>' });
                tFlag = false;
            }
        },

        "aaSorting": [[0, 'asc']],
        "columns": [
            { "data": "customer" },
            { "data": "style" },
            { "data": "category" },
            { "data": "element" },
            { "data": "description" },
            { "data": "version" },
            { "data": "updated_at" },
            { "data": "action" }
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
                    'pdfHtml5',
                    'print'
                ]
            }
        ],

        "columnDefs": [
            { "orderable": false, "targets": [4, 7] }
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

    // Delete Confirmation
    $('#journal_spec_details').on('click', 'button.delete-spec', function () {
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
                window.top.location = "journalspecdescriptions?del=" + del;
            } else {
                swal("Cancelled", "Specification not deleted", "error");
            }
        });
    });
});
</script>

</body>
</html>
