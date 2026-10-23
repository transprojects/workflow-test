<?php
include "includes/includes.php";
require_once __DIR__.'/includes/form_ui.php';
include 'email-functions.php';
include_once "includes/change_log_mail.php";

$customers = $auth->showCustomerlist();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$mode = $id > 0 ? 'update' : 'insert';
$data = [
    'customer_id' => '',
    'cs_id' => '',
    'element_id' => '',
    'description' => '',
    'image_path' => ''
];

// Load existing data if update mode
if ($mode == 'update') {
    $qry = mysqli_query($db, "SELECT * FROM journal_spec_details WHERE id=$id");
    if ($qry && mysqli_num_rows($qry) > 0) {
        $data = mysqli_fetch_assoc($qry);
    }
}

if (isset($_POST['save'])) {
    $cust_id = intval($_POST['cust_id']);
    $cs_id = intval($_POST['cs_id']);
    $element_id = intval($_POST['element_id']);
    $description = mysqli_real_escape_string($db, $_POST['description']);
    $user = $_COOKIE['user_id'];
    $user_name = $_COOKIE['full_name'];

    // Upload image
    $image_path = $data['image_path'];
    if (!empty($_FILES['image']['name'])) {
        $tmp = $_FILES['image']['tmp_name'];
        $name = time() . '_' . basename($_FILES['image']['name']);
        move_uploaded_file($tmp, "uploads/" . $name);
        $image_path = "uploads/" . $name;
    }

    $email_settings = mysqli_fetch_array(mysqli_query($db, "SELECT * FROM email_settings WHERE id=2"));

    if ($mode == 'insert') {
        // ✅ Version check for same customer + style + element
        $check = mysqli_query($db, "
            SELECT * FROM journal_spec_details 
            WHERE customer_id=$cust_id 
              AND cs_id=$cs_id 
              AND element_id=$element_id
        ");

        $qry_elements = mysqli_fetch_array(mysqli_query($db, "
            SELECT js_id, element_name 
            FROM journal_spec_master 
            WHERE js_id='$element_id' AND status='Active'
        "));
        $element_name = $qry_elements['element_name'];

        if (mysqli_num_rows($check) > 0) {
            // Existing → create new version awaiting approval
            $row = mysqli_fetch_assoc($check);
            $new_version = $row['version'] + 1;

            mysqli_query($db, "
                INSERT INTO journal_spec_history 
                    (journal_spec_details_id, description, image_path, version, edited_by, edited_at, status)
                VALUES 
                    (".$row['id'].", '$description', '$image_path', $new_version, '$user', NOW(), 'Inactive')
            ");

            $history_id = mysqli_insert_id($db);
            $token = md5($history_id.time());
            mysqli_query($db, "UPDATE journal_spec_history SET approve_token='$token' WHERE history_id=$history_id");

            // Change-log entry + email (new version submitted, awaiting approval)
            log_row_added('Journal Spec Description (New Version)', $row['id'] . ' / v' . $new_version, array('customer_id'=>$cust_id,'cs_id'=>$cs_id,'element_id'=>$element_id,'description'=>$_POST['description'],'image_path'=>$image_path,'version'=>$new_version,'status'=>'Inactive (awaiting approval)'));

            // Email approval link
            $subject = "Journal Specification Update Approval Needed";
            $approval_link = "http://103.66.12.3:8010/workflow-new/journal_approve.php?token=$token";
            $body = "
            <html><body>
            <p>Hello Admin,</p>
            <p>User <strong>$user_name</strong> submitted an update for 
            Element <strong>$element_name</strong> (Version $new_version).</p>
            <p>Description: $description</p>
            <p><a href='$approval_link'>Approve Version</a></p>
            </body></html>";

            sendMail(
                $email_settings['host'],
                $email_settings['port'],
                $email_settings['secure'],
                $email_settings['from_email'],
                $email_settings['from_password'],
                $email_settings['from_email'],
                $email_settings['from_name'],
                $email_settings['mail_to'],
                $email_settings['mail_cc'],
                $email_settings['mail_bcc'],
                $subject,
                $body
            );
        } else {
            // First entry → insert directly
			
            mysqli_query($db, "
                INSERT INTO journal_spec_details 
                    (customer_id, cs_id, element_id, description, image_path, version, updated_by, updated_at)
                VALUES 
                    ($cust_id, $cs_id, $element_id, '$description', '$image_path', 1, '$user', NOW())
            ");

            $detail_id = mysqli_insert_id($db);

            mysqli_query($db, "
                INSERT INTO journal_spec_history 
                    (journal_spec_details_id, description, image_path, version, edited_by, edited_at, status)
                VALUES 
                    ($detail_id, '$description', '$image_path', 1, '$user', NOW(), 'Active')
            ");

            // Change-log entry + email for the new record
            log_row_added('Journal Spec Description', $detail_id . ' / ' . $element_name, array('customer_id'=>$cust_id,'cs_id'=>$cs_id,'element_id'=>$element_id,'description'=>$_POST['description'],'image_path'=>$image_path,'version'=>1));
        }
    } else {
        // Direct update (no version control)
        mysqli_query($db, "
            UPDATE journal_spec_details 
            SET customer_id=$cust_id, cs_id=$cs_id, element_id=$element_id, 
                description='$description', image_path='$image_path', 
                updated_by='$user', updated_at=NOW()
            WHERE id=$id
        ");

        // Change-log entry + email ($data holds the values loaded before the edit)
        log_row_change('Journal Spec Description', $id, $data, array('customer_id'=>$cust_id,'cs_id'=>$cs_id,'element_id'=>$element_id,'description'=>$_POST['description'],'image_path'=>$image_path));
    }

    header("Location: journalspecdescriptions?msg=Saved successfully");
    exit;
}
if ($mode == 'update') {
	
	
	    // Fetch style name
    $styleResult = mysqli_query($db, "SELECT cs_id, cs_title FROM inw_customer_styles WHERE cust_id = '{$data['customer_id']}' AND cs_status='Active'");
    $styles = mysqli_fetch_all($styleResult, MYSQLI_ASSOC);
	
	
    $elementResult = mysqli_query($db, "
        SELECT js_id, element_name, element_name, category 
        FROM journal_spec_master 
        WHERE cs_id = '{$data['cs_id']}' and  js_id = '{$data['element_id']}' 
        AND status='Active'
        ORDER BY element_name
    ");
    $elements = mysqli_fetch_all($elementResult, MYSQLI_ASSOC);

    // ✅ Extract the category from DB or data
     $selectedCategory = $elements[0]['category'];
       
} else {
    $styles = $elements = [];
    $selectedCategory = '';
}

$categories = ['Front Matter', 'Body Matter', 'Back Matter','Specific Style Points'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php fu_head('fu_form'); fu_card_css(); ?>
</head>
<body>
<div class="wholeouter">
    <?php include("includes/top-header.php");?>

    <div class="container-fluid pt-4">
        <div class="row align-items-center">
            <div class="col-8 col-lg-10">
                <p class="articleheading ms-4">
                    <?php echo $mode == 'update' ? 'Edit' : 'Add' ?> Journal Specification Description
                </p>
            </div>
            <div class="col-4 col-lg-2 text-end pe-3">
                <a href="javascript:history.back();" class="back_icon">
                    <img src="<?php echo $_COOKIE['set_url'];?>/assets/images/backarrow.svg">
                </a>
            </div>
        </div>
    </div>

   <?php fu_hero(fu_ui_basic('Journal Spec Description', 'Journal Spec Description &rsaquo; Add')); ?>
      <div class="fu-wrap">
    <div class="fu-card">
        <form id="fu_form" action="" method="post" enctype="multipart/form-data">
            <h4>Element Description</h4>

            <div class="row g-4">

                <!-- 🔹 Customer -->
                <div class="col-md-4">
                    <label>Customer<span style="color:red">*</span></label>
                    <select class="form-select" name="cust_id" id="cust_id" required>
                        <option value="">Select Customer</option>
                        <?php foreach ($customers as $cust) { ?>
                            <option value="<?php echo $cust['id']; ?>" 
                                <?php echo ($cust['id'] == $data['customer_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cust['cust_name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <!-- 🔹 Style -->
                <div class="col-md-4">
                    <label>Style<span style="color:red">*</span></label>
                    <select class="form-select" name="cs_id" id="cs_id" required>
                        <option value="">Select Style</option>
                        <?php foreach ($styles as $style) { ?>
                            <option value="<?php echo $style['id']; ?>" 
                                <?php echo ($style['cs_id'] == $data['cs_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($style['cs_title']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <!-- 🔹 Category -->
				<div class="col-md-4">
					<label>Category<span style="color:red">*</span></label>
						<select class="form-select" name="category" id="category" required>
							<option value="">Select Category</option>
							<?php foreach ($categories as $cat) { ?>
							<option value="<?php echo $cat; ?>" 
							<?php echo ($cat == $selectedCategory) ? 'selected' : ''; ?>>
							<?php echo htmlspecialchars($cat); ?>
							</option>
							<?php } ?>
						</select>
				</div>



                <!-- 🔹 Element -->
                <div class="col-md-4">
                    <label>Element<span style="color:red">*</span></label>
                    <select class="form-select" name="element_id" id="element" required>
                        <option value="">Select Element</option>
                        <?php foreach ($elements as $el) { ?>
                            <option value="<?php echo $el['js_id']; ?>" 
                                <?php echo ($el['js_id'] == $data['element_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($el['element_name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <!-- 🔹 Description -->
                <div class="col-md-4">
                    <label>Description<span style="color:red">*</span></label>
                    <textarea name="description" class="form-control" rows="10" style="height:180px !important" required><?php echo htmlspecialchars(trim($data['description'])); ?></textarea>
                </div>

                <!-- 🔹 Image -->
                <div class="col-md-4">
                    <label>Image</label>
                    <input type="file" class="form-control" name="image" id="image">
                    <?php if (!empty($data['image_path'])) { ?>
                        <div class="mt-2">
                            <a href="<?php echo $data['image_path']; ?>" target="_blank">View Current Image</a>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="mt-5 text-end">
                <button type="button" class="btn btn-secondary me-3" onclick="history.back()">Cancel</button>
                <button type="submit" name="save" class="btn btn-primary"><?php echo ucfirst($mode); ?></button>
            </div>
        </form>
    </div>
</div>

</div>

<?php echo scripts(); ?>
<?php echo footer(); ?>

<script>
$(document).ready(function() {

    // Detect mode
    var isUpdateMode = <?php echo ($mode == 'update') ? 'true' : 'false'; ?>;
    var initializing = isUpdateMode; // prevent triggers during update preload

    // ✅ Fetch Styles by Customer
    $('#cust_id').on('change', function() {
        if (initializing) return; // skip during preload
        var cust_id = $(this).val();
        $('#cs_id').html('<option>Loading...</option>');
        $('#category').html('<option>Select Category</option>');
        $('#element').html('<option>Select Element</option>');

        if (cust_id) {
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

    // ✅ When Style changes → load categories dynamically
    $('#cs_id').on('change', function() {
        if (initializing) return;
        var cs_id = $(this).val();
        $('#category').html('<option value="">Loading...</option>');
        $('#element').html('<option value="">Select Element</option>');

        if (cs_id) {
            $.ajax({
                url: 'get_elements_by_style.php',
                type: 'GET',
                dataType: 'json',
                data: { cs_id: cs_id },
                success: function(data) {
                    var catSelect = $('#category');
                    catSelect.html('<option value="">Select Category</option>');
                    $.each(data.categories, function(i, cat) {
                        catSelect.append('<option value="'+cat+'">'+cat+'</option>');
                    });
                }
            });
        } else {
            $('#category').html('<option value="">Select Category</option>');
        }
    });

    // ✅ When Category changes → load elements (filtered by Style + Category)
    $('#category').on('change', function() {
        if (initializing) return;
        var cs_id = $('#cs_id').val();
        var category = $(this).val();
        $('#element').html('<option>Loading...</option>');

        if (cs_id && category) {
            $.ajax({
                url: 'get_elements_by_style.php',
                type: 'GET',
                dataType: 'json',
                data: { cs_id: cs_id, category: category },
                success: function(data) {
                    var elSelect = $('#element');
                    elSelect.html('<option value="">Select Element</option>');
                    $.each(data.elements, function(i, e) {
                        elSelect.append('<option value="'+e.js_id+'">'+e.element_name+'</option>');
                    });
                }
            });
        } else {
            $('#element').html('<option value="">Select Element</option>');
        }
    });


    // ✅ UPDATE MODE — Auto populate chain without overwriting selections
<?php if ($mode == 'update' && !empty($data['customer_id'])) { ?>
    var cust_id = <?php echo (int)$data['customer_id']; ?>;
    var cs_id = <?php echo (int)$data['cs_id']; ?>;
    var category = '<?php echo addslashes($selectedCategory); ?>';
    var element_id = <?php echo (int)$data['element_id']; ?>;

    // Step 1 → Load styles for the selected customer
    $.ajax({
        url: 'get-styles-by-customer.php',
        type: 'POST',
        data: { cust_id: cust_id }
    }).done(function(data) {
        $('#cs_id').html(data);
        $('#cust_id').val(cust_id);
        $('#cs_id').val(cs_id);

        // Step 2 → Load categories for this style
        $.ajax({
            url: 'get_elements_by_style.php',
            type: 'GET',
            dataType: 'json',
            data: { cs_id: cs_id }
        }).done(function(resp) {
            var catSelect = $('#category');
            catSelect.html('<option value="">Select Category</option>');
            $.each(resp.categories, function(i, cat) {
                var sel = (cat === category) ? 'selected' : '';
                catSelect.append('<option value="'+cat+'" '+sel+'>'+cat+'</option>');
            });

            // Step 3 → Load elements for this style + selected category
            $.ajax({
                url: 'get_elements_by_style.php',
                type: 'GET',
                dataType: 'json',
                data: { cs_id: cs_id, category: category }
            }).done(function(resp2) {
                var elSelect = $('#element');
                elSelect.html('<option value="">Select Element</option>');
                $.each(resp2.elements, function(i, e) {
                    var sel = (e.js_id == element_id) ? 'selected' : '';
                    elSelect.append('<option value="'+e.js_id+'" '+sel+'>'+e.element_name+'</option>');
                });

                // ✅ Mark initialization complete — now events can trigger normally
                initializing = false;
            });
        });
    });
<?php } else { ?>
    initializing = false; // not update mode, enable change handlers immediately
<?php } ?>

});
</script>



<?php fu_foot('#fu_form', null, array(), null, fu_ui_basic('Journal Spec Description', 'Journal Spec Description &rsaquo; Add')); ?>
  </body>
</html>
