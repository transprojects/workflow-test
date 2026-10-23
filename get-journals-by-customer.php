<?php
include "includes/conn.php";

if (isset($_POST['cust_id']) && !empty($_POST['cust_id'])) {
    $cust_id = intval($_POST['cust_id']);
    $cs_id = isset($_POST['cs_id']) ? intval($_POST['cs_id']) : 0; // Optional (for edit mode)

    // ✅ Logic:
    // Show only journals of this customer that are:
    // - Not linked to any style (cs_id IS NULL or 0)
    // - OR already linked to the current style (if editing)
    $condition = ($cs_id > 0)
        ? "AND (cs_id IS NULL OR cs_id = 0 OR cs_id = '$cs_id')"
        : "AND (cs_id IS NULL OR cs_id = 0)";

    $qry = mysqli_query($db, "
        SELECT j_id, j_code 
        FROM adm_journals 
        WHERE j_cust_id = '$cust_id' 
        $condition
        ORDER BY j_code ASC
    ");

    $options = '';
    if (mysqli_num_rows($qry) > 0) {
        while ($row = mysqli_fetch_assoc($qry)) {
            $options .= "<option value='{$row['j_id']}'>{$row['j_code']}</option>";
        }
    } else {
        $options = "<option value=''>No available journals</option>";
    }

    echo $options;
}
?>
