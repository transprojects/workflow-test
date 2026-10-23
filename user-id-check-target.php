<?php
set_time_limit(0);
date_default_timezone_set('Asia/Kolkata');
include "includes/conn.php";

// Input data
$user_ids_str = '165,167,179,182,218,228,234,238,245,256,266,274,276,286,294,296,309,310,311,312,314,315,317,319,321,325,329,331,332,334,336,340,347,348,349,351,352,359,360,366,367,373,374,375,376,377,378,379,380,381,382,383,385,388,391,392';
$report_date = '2025-07-16';

// Get target user info with departments
$user_query = "
    SELECT DISTINCT uta.user_id, uta.uta_id, u.full_name, u.department_id, utca.dept_id, 
                    u.access_other_depts, u.cust_id, u.access_other_cust_id
    FROM user_target_customer_assign uta
    INNER JOIN users u ON uta.user_id = u.id
    INNER JOIN user_target_assign utca ON utca.uta_id = uta.uta_id
    WHERE u.approved = '1' AND uta.user_id IN ($user_ids_str)";
$users = $db->query($user_query) or die($db->error);

// Collect unique (user_id, dept_id) pairs to check
$pairs_to_check = [];
while ($user_row = $users->fetch_assoc()) {
    $user_id = $user_row['user_id'];
    $dept_id = $user_row['dept_id'];
    $pairs_to_check[] = ['user_id' => $user_id, 'dept_id' => $dept_id];
}

// Fetch existing records from user_target_analytics for the given date
// Build dynamic IN clause
$in_conditions = [];
$params = [];
$types = '';

foreach ($pairs_to_check as $pair) {
    $in_conditions[] = "(user_id = ? AND dept_id = ? AND from_date = ?)";
    $params[] = $pair['user_id'];
    $params[] = $pair['dept_id'];
    $params[] = $report_date;
    $types .= 'iis';
}

$existing_pairs = [];
if (!empty($in_conditions)) {
    $sql = "SELECT user_id, dept_id FROM user_target_analytics WHERE " . implode(" OR ", $in_conditions);
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $existing_pairs[] = $row['user_id'] . '-' . $row['dept_id'];
    }
}

// Filter non-existing pairs
$insert_pairs = [];
foreach ($pairs_to_check as $pair) {
    $key = $pair['user_id'] . '-' . $pair['dept_id'];
    if (!in_array($key, $existing_pairs)) {
        $insert_pairs[] = $pair;
    }
}

// Output insertable pairs
foreach ($insert_pairs as $pair) {
    echo "✅ Insert required for User ID: {$pair['user_id']}, Dept ID: {$pair['dept_id']}<br>";
}
?>
