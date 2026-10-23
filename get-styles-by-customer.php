<?php
include "includes/conn.php";

$cust_id = intval($_POST['cust_id']);
if ($cust_id > 0) {
    $qry = mysqli_query($db, "SELECT cs_id, cs_title FROM inw_customer_styles WHERE cust_id='$cust_id' ORDER BY cs_title ASC");
    if (mysqli_num_rows($qry) > 0) {
        echo '<option value="">Select Style</option>';
        while ($row = mysqli_fetch_assoc($qry)) {
            echo "<option value='{$row['cs_id']}'>{$row['cs_title']}</option>";
        }
    } else {
        echo '<option value="">No styles found</option>';
    }
} else {
    echo '<option value="">Select Style</option>';
}
?>
