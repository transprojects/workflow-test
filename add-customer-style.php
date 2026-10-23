<?php include "includes/includes.php";
require_once __DIR__.'/includes/form_ui.php'; ?>
<?php include_once "includes/change_log_mail.php"; ?>

<?php
// -----------------------------
// INSERT LOGIC
// -----------------------------
if (isset($_POST['action']) && $_POST['action'] == 'insert') {
    $cust_id    = intval($_POST['cust_id']);
    $cs_title   = trim(mysqli_real_escape_string($db, $_POST['cs_title']));
    $status     = 'Active';
    $journal_ids = isset($_POST['journal_ids']) ? $_POST['journal_ids'] : [];

    if ($cust_id && $cs_title != '') {
        // Check duplicate
        $check = mysqli_query($db, "SELECT * FROM inw_customer_styles WHERE cust_id='$cust_id' AND cs_title='$cs_title'");
        if (mysqli_num_rows($check) == 0) {

            // Insert new style
            mysqli_query($db, "INSERT INTO inw_customer_styles (cust_id, cs_title, cs_status) VALUES ('$cust_id', '$cs_title', '$status')")
                or die("Error inserting style: " . mysqli_error($db));
            $cs_id = mysqli_insert_id($db);
            log_row_added('Customer Style', $cs_id . ' / ' . $_POST['cs_title'], array('cust_id'=>$cust_id,'cs_title'=>$_POST['cs_title'],'cs_status'=>$status,'journal_ids'=>$journal_ids));

            // Link journals
            if (!empty($journal_ids)) {
                $journal_list = implode(',', array_map('intval', $journal_ids));
                mysqli_query($db, "UPDATE adm_journals SET cs_id='$cs_id' WHERE j_id IN ($journal_list)")
                    or die("Error updating journals: " . mysqli_error($db));
            }

            header("Location: add-customer-style.php?msg=Style added successfully");
            exit;
        } else {
            $error = "This style already exists for the selected customer.";
        }
    } else {
        $error = "All fields are required.";
    }
}

// -----------------------------
// UPDATE LOGIC
// -----------------------------
if (isset($_POST['action']) && $_POST['action'] == 'update') {
    $cs_id      = intval($_POST['cs_id']);
    $cust_id    = intval($_POST['cust_id']);
    $cs_title   = trim(mysqli_real_escape_string($db, $_POST['cs_title']));
    $status     = mysqli_real_escape_string($db, $_POST['cs_status']);
    $journal_ids = isset($_POST['journal_ids']) ? $_POST['journal_ids'] : [];

    // Snapshot before change (for change-log email)
    $__old = change_log_snapshot('inw_customer_styles', 'cs_id', $cs_id);

    // Step 1: Update style details
    mysqli_query($db, "UPDATE inw_customer_styles 
                       SET cust_id='$cust_id', cs_title='$cs_title', cs_status='$status' 
                       WHERE cs_id='$cs_id'") or die("Error updating style: " . mysqli_error($db));

    // Step 2: Reset previously linked journals
    mysqli_query($db, "UPDATE adm_journals SET cs_id=NULL WHERE cs_id='$cs_id'");

    // Step 3: Link new selected journals
    if (!empty($journal_ids)) {
        $journal_list = implode(',', array_map('intval', $journal_ids));
        mysqli_query($db, "UPDATE adm_journals SET cs_id='$cs_id' WHERE j_id IN ($journal_list)")
            or die("Error updating new journal links: " . mysqli_error($db));
    }

    log_row_change('Customer Style', $cs_id, $__old, array('cust_id'=>$cust_id,'cs_title'=>$_POST['cs_title'],'cs_status'=>$_POST['cs_status']));
    header("Location: add-customer-style.php?msg=Style updated successfully");
    exit;
}

// -----------------------------
// DELETE LOGIC
// -----------------------------
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Snapshot before delete (for change-log email)
    $__old = change_log_snapshot('inw_customer_styles', 'cs_id', $id);
    mysqli_query($db, "UPDATE adm_journals SET cs_id=NULL WHERE cs_id=$id");
    mysqli_query($db, "DELETE FROM inw_customer_styles WHERE cs_id=$id");
    log_row_deleted('Customer Style', $id . (isset($__old['cs_title']) ? ' / ' . $__old['cs_title'] : ''), $__old);
    header("Location: add-customer-style.php?msg=Deleted successfully");
    exit;
}

// -----------------------------
// FETCH DATA FOR EDIT
// -----------------------------
$editData = [];
if (isset($_GET['edit'])) {
    $cs_id = intval($_GET['edit']);
    $qry = mysqli_query($db, "
        SELECT s.*, c.cust_name 
        FROM inw_customer_styles s
        LEFT JOIN adm_customer_master c ON s.cust_id = c.id
        WHERE s.cs_id='$cs_id'
    ");
    $editData = mysqli_fetch_assoc($qry);
}

// -----------------------------
// FETCH CUSTOMER + STYLES
// -----------------------------
$customers = mysqli_query($db, "SELECT * FROM adm_customer_master ORDER BY cust_name ASC");
$styles = mysqli_query($db, "
    SELECT s.*, c.cust_name 
    FROM inw_customer_styles s 
    LEFT JOIN adm_customer_master c ON s.cust_id = c.id 
    ORDER BY c.cust_name, s.cs_title
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Journal Styles</title>
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php fu_head('fu_form'); fu_card_css(); ?>
    <link href="assets/js/select2/select2.min.css" rel="stylesheet" />
</head>
<body>
<div class="wholeouter">
    <?php include("includes/top-header.php"); ?>

    <?php fu_hero(fu_ui_basic('Journal Styles', 'Journal Styles &rsaquo; Add')); ?>
      <div class="fu-wrap">
        <div class="fu-card">
            <h4 class="mb-3">
                <?php echo isset($editData['cs_id']) ? "Edit Customer Journal Style" : "Add Customer Journal Style"; ?>
            </h4>

            <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
            <?php if (isset($_GET['msg'])) echo "<div class='alert alert-success'>".$_GET['msg']."</div>"; ?>

            <form id="fu_form" action="" method="post">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Customer<span style="color:red">*</span></label>
                        <select name="cust_id" id="cust_id" class="form-control" required <?php echo isset($editData['cs_id']) ? 'readonly' : ''; ?>>
                            <option value="">Select Customer</option>
                            <?php 
                            mysqli_data_seek($customers, 0);
                            while($row = mysqli_fetch_assoc($customers)) { 
                                $sel = (isset($editData['cust_id']) && $editData['cust_id'] == $row['id']) ? 'selected' : '';
                                echo "<option value='{$row['id']}' $sel>{$row['cust_name']}</option>";
                            } 
                            ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label>Style Title<span style="color:red">*</span></label>
                        <input type="text" name="cs_title" class="form-control" 
                               value="<?php echo isset($editData['cs_title']) ? htmlspecialchars($editData['cs_title']) : ''; ?>" 
                               placeholder="Enter style name" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label>Journal(s)<span style="color:red">*</span></label>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small>
                                <a href="javascript:void(0)" id="select_all_journals">Select All</a> /
                                <a href="javascript:void(0)" id="deselect_all_journals">Deselect All</a>
                            </small>
                        </div>
                <select class="form-select journal_drop" name="journal_ids[]" id="journal_ids" multiple required>
<?php
$current_cs_id = isset($editData['cs_id']) ? $editData['cs_id'] : 0;

if (isset($editData['cust_id'])) {
    $cust_id = $editData['cust_id'];
} elseif (isset($_POST['cust_id'])) {
    $cust_id = $_POST['cust_id']; // if form posted
} else {
    $cust_id = ''; // default
}

if (!empty($cust_id)) {
    // ✅ If editing, allow journals linked to this style as well
    if ($current_cs_id > 0) {
        $jQry = mysqli_query($db, "
            SELECT j_id, j_code 
            FROM adm_journals 
            WHERE j_cust_id = '$cust_id' 
              AND (cs_id IS NULL OR cs_id = 0 OR cs_id = '$current_cs_id')
            ORDER BY j_code ASC
        ");
    } else {
        // ✅ Add mode: show only unlinked journals
        $jQry = mysqli_query($db, "
            SELECT j_id, j_code 
            FROM adm_journals 
            WHERE j_cust_id = '$cust_id' 
              AND (cs_id IS NULL OR cs_id = 0)
            ORDER BY j_code ASC
        ");
    }

    // Fetch linked journals for pre-selection (only if edit)
    $linkedJournals = [];
    if ($current_cs_id > 0) {
        $getLinked = mysqli_query($db, "SELECT j_id FROM adm_journals WHERE cs_id='$current_cs_id'");
        while ($l = mysqli_fetch_assoc($getLinked)) {
            $linkedJournals[] = $l['j_id'];
        }
    }

    // Display available journals
    if (mysqli_num_rows($jQry) > 0) {
        while ($jr = mysqli_fetch_assoc($jQry)) {
            $sel = in_array($jr['j_id'], $linkedJournals) ? 'selected' : '';
            echo "<option value='{$jr['j_id']}' $sel>{$jr['j_code']}</option>";
        }
    } else {
        echo "<option value=''>No available journals</option>";
    }
} else {
    echo "<option value=''>Select Customer First</option>";
}
?>
</select>


                    </div>
                </div>

                <?php if (isset($editData['cs_id'])) { ?>
                    <div class="col-md-4 mb-3">
                        <label>Status</label>
                        <select name="cs_status" class="form-control">
                            <option value="Active" <?php if ($editData['cs_status']=='Active') echo 'selected'; ?>>Active</option>
                            <option value="Inactive" <?php if ($editData['cs_status']=='Inactive') echo 'selected'; ?>>Inactive</option>
                        </select>
                    </div>
                    <input type="hidden" name="cs_id" value="<?php echo $editData['cs_id']; ?>">
                    <input type="hidden" name="action" value="update">
                <?php } else { ?>
                    <input type="hidden" name="action" value="insert">
                <?php } ?>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <?php if (isset($editData['cs_id'])) { ?>
                        <a href="add-customer-style.php" class="btn btn-secondary">Cancel</a>
                    <?php } ?>
                </div>
            </form>
        </div>

        <div class="fu-card">
        <h5>Existing Customer Journal Styles</h5>
        <table class="fu-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Style</th>
                    <th>Status</th>
                    <th>Linked Journals (J_CODE)</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $i = 1;
                while ($row = mysqli_fetch_assoc($styles)) {
                    $jCodes = [];
                    $journalQry = mysqli_query($db, "SELECT j_code FROM adm_journals WHERE cs_id='{$row['cs_id']}' ORDER BY j_code ASC");
                    while ($jr = mysqli_fetch_assoc($journalQry)) {
                        $jCodes[] = $jr['j_code'];
                    }
                    $journalList = !empty($jCodes) ? implode(', ', $jCodes) : '<span class="text-muted">No Journals Linked</span>';

                    echo "<tr>
                        <td>{$i}</td>
                        <td>{$row['cust_name']}</td>
                        <td>{$row['cs_title']}</td>
                        <td>{$row['cs_status']}</td>
                        <td>{$journalList}</td>
                        <td>
                            <a href='?edit={$row['cs_id']}' class='btn btn-sm btn-primary' title='Edit'><i class='fa fa-pencil'></i></a>
                            <a href='?delete={$row['cs_id']}' data-confirm='Delete this style permanently? This cannot be undone.' class='btn btn-sm btn-danger' title='Delete'><i class='fa fa-trash-o'></i></a>
                        </td>
                    </tr>";
                    $i++;
                }
                ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<?php echo scripts(); ?>
<script src="assets/js/select2/select2.min.js"></script>

<script>
$(".journal_drop").select2({
    placeholder: "Select Journals",
    width: '100%'
});

// --- Fetch Journals Dynamically based on Customer ---
$('#cust_id').on('change', function() {
    var cust_id = $(this).val();
    var cs_id = $('#cs_id').val() || 0; // ✅ Include style id if editing
    $('#journal_ids').html('<option>Loading...</option>');

    if (cust_id != '') {
        $.ajax({
            url: 'get-journals-by-customer.php',
            type: 'POST',
            data: { 
                cust_id: cust_id,
                cs_id: cs_id // ✅ Pass style ID to backend
            },
            success: function(data) {
                $('#journal_ids').html(data);
                $('#journal_ids').trigger('change');
            },
            error: function() {
                $('#journal_ids').html('<option value="">Error loading journals</option>');
            }
        });
    } else {
        $('#journal_ids').html('<option value="">Select Customer First</option>');
    }
});

// --- Select All / Deselect All for Journals ---
$('#select_all_journals').on('click', function() {
    $('#journal_ids option').prop('selected', true);
    $('#journal_ids').trigger('change');
});

$('#deselect_all_journals').on('click', function() {
    $('#journal_ids option').prop('selected', false);
    $('#journal_ids').trigger('change');
});
</script>

<?php echo footer(); ?>
<?php fu_confirm_js(); fu_foot('#fu_form', null, array(), null, fu_ui_basic('Journal Styles', 'Journal Styles &rsaquo; Add')); ?>
  </body>
</html>
