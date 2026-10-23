<?php
include "includes/includes.php";
require_once __DIR__.'/includes/form_ui.php';
include_once "includes/change_log_mail.php";

// -----------------------------
// INSERT LOGIC
// -----------------------------
if (isset($_POST['action']) && $_POST['action'] == 'insert') {
    foreach ($_POST as $key => $value) {
        $post[$key] = mysqli_real_escape_string($db, $value);
    }

    $cust_id      = $post['cust_id'];
    $cs_id        = $post['cs_id']; // Selected Style ID
    $category     = $post['category'];
    $elements     = $_POST['element_name'];
    $descriptions = $_POST['js_description'];

    // ✅ Step 1: Validate Style belongs to Customer
    $styleCheck = mysqli_query($db, "SELECT cs_id FROM inw_customer_styles WHERE cs_id='$cs_id' AND cust_id='$cust_id'");
    if (mysqli_num_rows($styleCheck) == 0) {
        die("Invalid Style selected for the chosen customer.");
    }

    // ✅ Step 2: Insert specification elements
    for ($i = 0; $i < count($elements); $i++) {
        $element = trim($elements[$i]);
        $desc    = mysqli_real_escape_string($db, $descriptions[$i]);

        if ($element != '') {
            $insert = "INSERT INTO `journal_spec_master`
                       (`cust_id`, `cs_id`, `category`, `element_name`, `js_description`, `status`)
                       VALUES ('$cust_id', '$cs_id', '$category', '$element', '$desc', 'Active')";
            mysqli_query($db, $insert) or die("Error inserting element: " . mysqli_error($db));
        }
    }

    log_row_added('Journal Spec Element', $cs_id . ' / ' . $category, array('cust_id'=>$cust_id,'cs_id'=>$cs_id,'category'=>$category,'element_name'=>$_POST['element_name'],'js_description'=>$_POST['js_description']));
    header("Location: add-spec-elements.php?msg=Successfully added");
    exit;
}


// -----------------------------
// UPDATE LOGIC
// -----------------------------
if (isset($_POST['action']) && $_POST['action'] == 'update') {
    $js_id        = intval($_POST['js_id']);
    $cust_id      = mysqli_real_escape_string($db, $_POST['cust_id']);
    $cs_id        = mysqli_real_escape_string($db, $_POST['cs_id']);
    $category     = mysqli_real_escape_string($db, $_POST['category']);
    $element_name = mysqli_real_escape_string($db, $_POST['element_name']);
    $description  = mysqli_real_escape_string($db, $_POST['js_description']);
    $status       = mysqli_real_escape_string($db, $_POST['status']);

    // ✅ Verify Style
    $styleCheck = mysqli_query($db, "SELECT cs_id FROM inw_customer_styles WHERE cs_id='$cs_id' AND cust_id='$cust_id'");
    if (mysqli_num_rows($styleCheck) == 0) {
        die("Invalid Style selected for the chosen customer.");
    }

    // Snapshot before change (for change-log email)
    $__old = change_log_snapshot('journal_spec_master', 'js_id', $js_id);

    // ✅ Update record
    $update = "UPDATE `journal_spec_master`
               SET `cust_id`='$cust_id',
                   `cs_id`='$cs_id',
                   `category`='$category',
                   `element_name`='$element_name',
                   `js_description`='$description',
                   `status`='$status'
               WHERE `js_id`=$js_id";
    mysqli_query($db, $update) or die("Error updating element: " . mysqli_error($db));

    log_row_change('Journal Spec Element', $js_id, $__old, array('cust_id'=>$_POST['cust_id'],'cs_id'=>$_POST['cs_id'],'category'=>$_POST['category'],'element_name'=>$_POST['element_name'],'js_description'=>$_POST['js_description'],'status'=>$_POST['status']));
    header("Location: add-spec-elements.php?msg=Updated successfully");
    exit;
}


// -----------------------------
// FETCH FOR EDIT
// -----------------------------
$editData = [];
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $qry = mysqli_query($db, "
        SELECT j.*, s.cs_title 
        FROM journal_spec_master j 
        LEFT JOIN inw_customer_styles s ON j.cs_id = s.cs_id 
        WHERE j.js_id = $id
    ");
    $editData = mysqli_fetch_assoc($qry);
}


// -----------------------------
// DELETE LOGIC
// -----------------------------
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Snapshot before delete (for change-log email)
    $__old = change_log_snapshot('journal_spec_master', 'js_id', $id);
    mysqli_query($db, "DELETE FROM journal_spec_master WHERE js_id=$id");
    log_row_deleted('Journal Spec Element', $id . (isset($__old['element_name']) ? ' / ' . $__old['element_name'] : ''), $__old);
    header("Location: add-spec-elements.php?msg=Deleted successfully");
    exit;
}


// -----------------------------
// FETCH ALL RECORDS
// -----------------------------
$listQry = mysqli_query($db, "
    SELECT j.*, c.cust_name, s.cs_title 
    FROM journal_spec_master j
    LEFT JOIN adm_customer_master c ON j.cust_id = c.id
    LEFT JOIN inw_customer_styles s ON j.cs_id = s.cs_id
    ORDER BY c.cust_name, s.cs_title, j.category, j.element_name
");
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Journal Specification Master</title>
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php fu_head('fu_form'); fu_card_css(); ?>
    <style>
        .add-btn { background-color:#007bff; color:white; border:none; padding:5px 10px; border-radius:4px; }
        .remove-btn { background-color:#dc3545; color:white; border:none; padding:5px 10px; border-radius:4px; }
        table th, table td { vertical-align: middle; }
    </style>
</head>
<body>
<div class="wholeouter">
    <?php include("includes/top-header.php"); ?>

    <?php fu_hero(fu_ui_basic('Journal Spec Elements', 'Journal Spec Elements &rsaquo; Add')); ?>
      <div class="fu-wrap">
        <div class="fu-card">
            <form id="fu_form" action="" method="post">
                <div class="formcont1heading mb-3">
                    <?php echo isset($editData['js_id']) ? "Edit Element" : "Add New Category Elements"; ?>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>Customer<span style="color:red">*</span></label>
                        <select class="form-select" name="cust_id" id="cust_id" required>
                            <option value="">Select Customer</option>
                            <?php 
                            $customerQry = mysqli_query($db, "SELECT * FROM adm_customer_master ORDER BY cust_name ASC");
                            while($cRow = mysqli_fetch_assoc($customerQry)) { 
                                $selected = (isset($editData['cust_id']) && $editData['cust_id'] == $cRow['id']) ? 'selected' : '';
                                echo "<option value='{$cRow['id']}' $selected>{$cRow['cust_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label>Style<span style="color:red">*</span></label>
                        <select class="form-select" name="cs_id" id="cs_id" required>
                            <option value="">Select Style</option>
                            <?php 
                            if (isset($editData['cust_id'])) {
                                $styleQry = mysqli_query($db, "SELECT * FROM inw_customer_styles WHERE cust_id='{$editData['cust_id']}' ORDER BY cs_title");
                                while($sRow = mysqli_fetch_assoc($styleQry)) {
                                    $sel = ($editData['cs_id'] == $sRow['cs_id']) ? 'selected' : '';
                                    echo "<option value='{$sRow['cs_id']}' $sel>{$sRow['cs_title']}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label>Category<span style="color:red">*</span></label>
                        <select name="category" class="form-select" required>
                            <option value="">Select Category</option>
                            <?php
                            $cats = ["Front Matter", "Body Matter", "Back Matter","Specific Style Points"];
                            foreach ($cats as $cat) {
                                $sel = (isset($editData['category']) && $editData['category'] == $cat) ? 'selected' : '';
                                echo "<option value='$cat' $sel>$cat</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <?php if (!isset($editData['js_id'])) { ?>
                    <div id="element-container">
                        <div class="row mb-3 element-row">
                            <div class="col-md-4">
                                <label>Element Name<span style="color:red">*</span></label>
                                <input type="text" class="form-control" name="element_name[]" placeholder="Enter element name" required>
                            </div>
                            <div class="col-md-6">
                                <label>Description</label>
                                <input type="text" class="form-control" name="js_description[]" placeholder="Optional description">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="button" class="add-btn" onclick="addElement()">+ Add More</button>
                            </div>
                        </div>
                    </div>
                <?php } else { ?>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label>Element Name</label>
                            <input type="text" name="element_name" value="<?php echo $editData['element_name']; ?>" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label>Description</label>
                            <input type="text" name="js_description" value="<?php echo $editData['js_description']; ?>" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="Active" <?php if ($editData['status']=='Active') echo 'selected'; ?>>Active</option>
                                <option value="Inactive" <?php if ($editData['status']=='Inactive') echo 'selected'; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                <?php } ?>

                <input type="hidden" name="action" value="<?php echo isset($editData['js_id']) ? 'update' : 'insert'; ?>">
                <?php if (isset($editData['js_id'])) { ?>
                    <input type="hidden" name="js_id" value="<?php echo $editData['js_id']; ?>">
                <?php } ?>

                <div class="row mt-4">
                    <div class="col d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="fu-card">
        <h5 class="mb-3">Existing Journal Specification Elements</h5>
        <table class="fu-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th>Customer</th>
                    <th>Style</th>
                    <th>Category</th>
                    <th>Element</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th width="15%">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $i = 1;
                while ($row = mysqli_fetch_assoc($listQry)) {
                    echo "<tr>
                        <td>{$i}</td>
                        <td>{$row['cust_name']}</td>
                        <td>{$row['cs_title']}</td>
                        <td>{$row['category']}</td>
                        <td>{$row['element_name']}</td>
                        <td>{$row['js_description']}</td>
                        <td>{$row['status']}</td>
                        <td>
                            <a href='?edit={$row['js_id']}' class='btn btn-sm btn-primary' title='Edit'><i class='fa fa-pencil'></i></a>
                            <a href='?delete={$row['js_id']}' data-confirm='Delete this element permanently? This cannot be undone.' class='btn btn-sm btn-danger' title='Delete'><i class='fa fa-trash-o'></i></a>
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

<script>
function addElement() {
    let container = document.getElementById('element-container');
    let newRow = document.createElement('div');
    newRow.classList.add('row', 'mb-3', 'element-row');
    newRow.innerHTML = `
        <div class="col-md-4">
            <input type="text" class="form-control" name="element_name[]" placeholder="Enter element name" required>
        </div>
        <div class="col-md-6">
            <input type="text" class="form-control" name="js_description[]" placeholder="Optional description">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="button" class="remove-btn" onclick="removeElement(this)">Remove</button>
        </div>
    `;
    container.appendChild(newRow);
}
function removeElement(btn) {
    btn.closest('.element-row').remove();
}

$('#cust_id').on('change', function() {
    var cust_id = $(this).val();
    $('#cs_id').html('<option value="">Loading...</option>');

    if (cust_id != '') {
        $.ajax({
            url: 'get-styles-by-customer.php',
            type: 'POST',
            data: { cust_id: cust_id },
            success: function(data) {
                $('#cs_id').html(data);
            }
        });
    } else {
        $('#cs_id').html('<option value="">Select Style</option>');
    }
});
</script>

<?php echo scripts(); ?>
<?php echo footer(); ?>
<?php fu_confirm_js(); fu_foot('#fu_form', null, array(), null, fu_ui_basic('Journal Spec Elements', 'Journal Spec Elements &rsaquo; Add')); ?>
  </body>
</html>
