<?php
$location_id = $_GET['location_id'];

if ($location_id == 'TVL') {
    require("includes/conn.php");
} else {
    require("chndb.php");
}

ob_end_clean(); // Clean all output buffers
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=userlogs_data_" . date('ymd') . ".xls");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel compatibility

$users = $_REQUEST['user'];

$statement = $pdo->prepare("SELECT * FROM users WHERE worklocation IN ('TVL','CHN') AND FIND_IN_SET(id, ?) AND approved = 1");
$statement->execute([$users]);
$result = $statement->fetchAll();

foreach ($result as $row) {
    $start_date = date('Y-m-d', strtotime($_REQUEST['startdate']));
    $end_date = date('Y-m-d', strtotime($_REQUEST['enddate']));

    echo "<h3>User Logs Overview - {$row['full_name']} ({$location_id})</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<thead>
            <tr style='background-color:#ccc;'>
                <th>Date</th>
                <th>Employee ID</th>
                <th>Employee Name</th>
                <th>Login In</th>
                <th>Login Out</th>
                <th>Count of Log Ins</th>
            </tr>
        </thead><tbody>";

    while (strtotime($start_date) <= strtotime($end_date)) {
        $sql_day_count = mysqli_query($db, "SELECT * FROM wf_token_auth WHERE username='" . $row['user_name'] . "' AND DATE_FORMAT(start_date,'%Y-%m-%d')='" . $start_date . "' ORDER BY id DESC");
        $res_day_login = mysqli_fetch_array($sql_day_count);
        $num_day_login = mysqli_num_rows($sql_day_count);

        $res_login = mysqli_fetch_array(mysqli_query($db, "SELECT MIN(start_date) as intime, MAX(start_date) as outtime FROM wf_token_auth WHERE DATE_FORMAT(start_date,'%Y-%m-%d')='" . $start_date . "' AND username='" . $row['user_name'] . "'"));

        $givendate = date('Y-m-d', strtotime($start_date));
        $day_name = strtolower(date("l", strtotime($givendate)));

        // Start row
        echo "<tr" . ($num_day_login == 0 && $day_name != "sunday" ? " style='background-color:yellow;'" : "") . ">";

        // Date
        $day_display = ($day_name == "saturday") ? "<span style='color:orange;'>" . date('d-m-Y', strtotime($start_date)) . " (Sat)</span>" :
            (($day_name == "sunday") ? "<span style='color:red;'>" . date('d-m-Y', strtotime($start_date)) . " (Sun)</span>" : date('d-m-Y', strtotime($start_date)));
        echo "<td>{$day_display}</td>";

        // Employee ID, Name
        echo "<td>{$row['user_name']}</td><td>{$row['full_name']}</td>";

        // Login In / Out
        if ($num_day_login > 0) {
            echo "<td>" . date('H:i:s', strtotime($res_login['intime'])) . "</td>";
            echo "<td>" . date('H:i:s', strtotime($res_login['outtime'])) . "</td>";
        } else {
            echo "<td style='color:red;'>No logs</td><td style='color:red;'>No logs</td>";
        }

        // Count
        if ($num_day_login > 0) {
            echo "<td>{$num_day_login}</td>";
        } elseif ($num_day_login == 0 && $day_name != "sunday") {
            echo "<td style='color:red;'>{$num_day_login} (Leave)</td>";
        } else {
            echo "<td style='color:red;'>{$num_day_login}</td>";
        }

        echo "</tr>";
        $start_date = date("Y-m-d", strtotime("+1 days", strtotime($start_date)));
    }

    echo "</tbody></table><br><br>";
}
exit;
?>
