<?php
$location_id = $_GET['location_id'];

if ($location_id == 'TVL') {
    require("includes/conn.php");
} else {
    require("chndb.php");
}

ob_end_clean();
header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=userlogs_data_" . date('ymd') . ".csv");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel compatibility with Excel

$output = fopen("php://output", "w");

$users = $_REQUEST['user'];

$statement = $pdo->prepare("SELECT * FROM users WHERE worklocation IN ('TVL','CHN') AND FIND_IN_SET(id, ?) AND approved = 1");
$statement->execute([$users]);
$result = $statement->fetchAll();

foreach ($result as $row) {
    $start_date = date('Y-m-d', strtotime($_REQUEST['startdate']));
    $end_date = date('Y-m-d', strtotime($_REQUEST['enddate']));

    // Section title
    fputcsv($output, ["User Logs Overview - {$row['full_name']} ({$location_id})"]);
    fputcsv($output, []); // empty row

    // CSV header row
    fputcsv($output, ['Date', 'Employee ID', 'Employee Name', 'Login In', 'Login Out', 'Count of Log Ins']);

    while (strtotime($start_date) <= strtotime($end_date)) {
        $sql_day_count = mysqli_query($db, "SELECT * FROM wf_token_auth WHERE username='" . $row['user_name'] . "' AND DATE_FORMAT(start_date,'%Y-%m-%d')='" . $start_date . "' ORDER BY id DESC");
        $num_day_login = mysqli_num_rows($sql_day_count);

        $res_login = mysqli_fetch_array(mysqli_query($db, "SELECT MIN(start_date) as intime, MAX(start_date) as outtime FROM wf_token_auth WHERE DATE_FORMAT(start_date,'%Y-%m-%d')='" . $start_date . "' AND username='" . $row['user_name'] . "'"));

        $day_name = strtolower(date("l", strtotime($start_date)));
        $date_display = date('d-m-Y', strtotime($start_date));
        if ($day_name == "saturday") {
            $date_display .= " (Sat)";
        } elseif ($day_name == "sunday") {
            $date_display .= " (Sun)";
        }

        $intime = $outtime = 'No logs';
        if ($num_day_login > 0) {
            $intime = date('H:i:s', strtotime($res_login['intime']));
            $outtime = date('H:i:s', strtotime($res_login['outtime']));
        }

        if ($num_day_login == 0 && $day_name != "sunday") {
            $count = '0 (Leave)';
        } else {
            $count = (string)$num_day_login;
        }

        fputcsv($output, [
            $date_display,
            $row['user_name'],
            $row['full_name'],
            $intime,
            $outtime,
            $count
        ]);

        $start_date = date("Y-m-d", strtotime("+1 days", strtotime($start_date)));
    }

    fputcsv($output, []); // blank line between users
}

fclose($output);
exit;
