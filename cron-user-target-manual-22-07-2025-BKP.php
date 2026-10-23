<?php
set_time_limit(0); 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Kolkata');
include "includes/includes.php";

$dept_code = $_REQUEST['dept_id'];
$user_condition_user = "AND users.id = " . $_REQUEST['user_id'];
$user_condition_depart = ($dept_code == 1) ? "AND department_id IN ('1','2','22')" : (($dept_code == 10) ? "AND department_id IN ('10','14','24')" : "AND department_id = '$dept_code'");

$start_date = isset($_REQUEST['fdate']) ? date('Y-m-d', strtotime($_REQUEST['fdate'])) : date('Y-m-d');
$end_date = isset($_REQUEST['tdate']) ? date('Y-m-d', strtotime($_REQUEST['tdate'])) : date('Y-m-d');
$daysDifference = (new DateTime($start_date))->diff(new DateTime($end_date))->days + 1;

$user_query = "SELECT full_name, id, user_name, users.department_id, users.access_other_depts, users.cust_id, users.access_other_cust_id FROM users 
               INNER JOIN user_target_customer_assign ON users.id = user_target_customer_assign.user_id 
               WHERE approved='1' $user_condition_depart $user_condition_user 
               GROUP BY user_target_customer_assign.user_id ORDER BY full_name ASC";
$users = $db->query($user_query) or die($db->error);

while ($user_row = $users->fetch_assoc()) {
    $user_id = $user_row['id'];
    $dept_code = $user_row['department_id'];
    $other_dept_code = $user_row['access_other_depts'];
    $primary_cust_id = $user_row['cust_id'];
    $access_other_cust_id = $user_row['access_other_cust_id'];  

    $total_pages_completed = 0;
    $total_target_pages = 0;
    $total_completed = 0;
    $total_percentage = 0;
    $total_production_seconds = 0;

    $stage_totals = ['FP'=>['completed'=>0,'target'=>0,'time'=>0],'REV'=>['completed'=>0,'target'=>0,'time'=>0],'CORR'=>['completed'=>0,'target'=>0,'time'=>0],'FIN'=>['completed'=>0,'target'=>0,'time'=>0],'ISS'=>['completed'=>0,'target'=>0,'time'=>0]];
    $customer_data = [];

    $user_target_customer = $db->query("SELECT * FROM user_target_customer_assign 
                                        INNER JOIN adm_customer_master ON user_target_customer_assign.cust_id = adm_customer_master.id 
                                        WHERE user_id='$user_id'");
    while ($res_user_target = $user_target_customer->fetch_assoc()) {
        $customer_id = $res_user_target['id'];
        $customer_name = $res_user_target['cust_name'];

        if (!isset($customer_data[$customer_id])) {
            $customer_data[$customer_id] = ['name'=>$customer_name, 'stages'=>[]];
        }

        $stages = ($dept_code == 1) ? ['FP'=>'page_count'] : ['FP'=>'page_count','REV'=>'rpc','CORR'=>'cpc','FIN'=>'fpc','ISS'=>'ipc'];

        foreach ($stages as $stage => $target_field) {
            $status_condition = ($stage == 'CORR') ? "inw.current_status = 'Correction Done'" : "current_status in ('Completed','Correction Done','Assign','To Cross Check')";
            $stage_condition = ($stage != 'CORR') ? "AND current_stage LIKE '%$stage%'" : "";

            $query = "
                WITH FirstOccurrences AS (
                    SELECT inw.project_id, inw.pages_completed,
                        ROW_NUMBER() OVER (PARTITION BY inw.project_id ORDER BY inw.created_dt) AS rn
                    FROM inw_transactions AS inw
                    INNER JOIN inw_inward_dtl AS wd ON inw.project_id = wd.id
                    WHERE DATE_FORMAT(inw.created_dt, '%Y-%m-%d') BETWEEN '$start_date' AND '$end_date'
                        AND wd.cust_id = '$customer_id'
                        AND $status_condition
                        $stage_condition
                        AND inw.process_user = '$user_id'
                )
                SELECT SUM(pages_completed) AS pages_completed FROM FirstOccurrences WHERE rn = 1
            ";
            $result = $db->query($query) or die($db->error);
            $pages_completed = (float)($result->fetch_assoc()['pages_completed'] ?? 0);

            $time_query = "
                SELECT SUM(TIMESTAMPDIFF(SECOND, start_time, end_time)) AS total_time 
                FROM inw_transactions AS inw
                INNER JOIN inw_inward_dtl AS wd ON inw.project_id = wd.id
                WHERE DATE_FORMAT(inw.created_dt, '%Y-%m-%d') BETWEEN '$start_date' AND '$end_date'
                    AND wd.cust_id = '$customer_id'
                    AND $status_condition
                    $stage_condition
                    AND inw.process_user = '$user_id'
                    AND start_time IS NOT NULL AND end_time IS NOT NULL
            ";
            $time_result = $db->query($time_query) or die($db->error);
            $time_taken = (int)($time_result->fetch_assoc()['total_time'] ?? 0);

            $target_pages = (float)$res_user_target[$target_field];

            $stage_totals[$stage]['completed'] += $pages_completed;
            $stage_totals[$stage]['target'] += $target_pages;
            $stage_totals[$stage]['time'] += $time_taken;

            $customer_data[$customer_id]['stages'][$stage] = [
                'completed' => $pages_completed,
                'target' => $target_pages,
                'time' => $time_taken
            ];

            $total_pages_completed += $pages_completed;
            $total_target_pages += $target_pages;
            $total_completed += $pages_completed;
            $total_production_seconds += $time_taken;

            if ($target_pages > 0) {
                $total_percentage += round(($pages_completed / $target_pages) * 100, 2);
            }
        }
    }

    $average_percentage = ($daysDifference > 0) ? ($total_percentage / $daysDifference) : 0;
    $total_production_time = gmdate("H:i:s", $total_production_seconds);

    $working_time_query = "
        SELECT MIN(start_time) AS first_start, MAX(end_time) AS last_end 
        FROM inw_transactions AS inw
        INNER JOIN inw_inward_dtl AS wd ON inw.project_id = wd.id
        WHERE DATE_FORMAT(inw.created_dt, '%Y-%m-%d') BETWEEN '$start_date' AND '$end_date'
          AND inw.process_user = '$user_id'
          AND start_time IS NOT NULL AND end_time IS NOT NULL
    ";
    $working_time_result = $db->query($working_time_query) or die($db->error);
    $working_time_data = $working_time_result->fetch_assoc();

    $total_working_time = '00:00:00';
    if ($working_time_data['first_start'] && $working_time_data['last_end']) {
        $total_working_seconds = strtotime($working_time_data['last_end']) - strtotime($working_time_data['first_start']);
        $total_working_time = gmdate("H:i:s", $total_working_seconds);
    }

    // Extract total stage completed values
    $total_fp = $stage_totals['FP']['completed'];
    $total_rev = $stage_totals['REV']['completed'];
    $total_corr = $stage_totals['CORR']['completed'];
    $total_fin = $stage_totals['FIN']['completed'];
    $total_iss = $stage_totals['ISS']['completed'];

    // Check if master record exists
    $check_master = $db->query("SELECT id FROM user_target_analytics 
        WHERE user_id='$user_id' AND from_date='$start_date' AND to_date='$end_date' AND dept_id='$dept_code'");
    
    if ($check_master->num_rows > 0) {
        $row = $check_master->fetch_assoc();
        $master_id = $row['id'];
        $update = $db->prepare("UPDATE user_target_analytics SET 
            primary_cust_id=?, access_other_cust_id=?, other_dept_code=?, days=?, total_worked_pages=?, total_percentage=?, total_production_time=?, total_working_time=?,
            total_fp=?, total_rev=?, total_corr=?, total_finals=?, total_issue=?
            WHERE id=?");
        $update->bind_param("sssiddssdddddi", $primary_cust_id, $access_other_cust_id, $other_dept_code, $daysDifference, $total_pages_completed, $average_percentage, $total_production_time, $total_working_time, $total_fp, $total_rev, $total_corr, $total_fin, $total_iss, $master_id);
        if (!$update->execute()) echo "❌ Master Update Error: " . $update->error . "<br>";
        else echo "✅ Updated master ID = $master_id<br>";
        $update->close();
    } else {
        $insert = $db->prepare("INSERT INTO user_target_analytics 
            (user_id, primary_cust_id, access_other_cust_id, dept_id, other_dept_code, from_date, to_date, days, total_worked_pages, total_percentage, total_production_time, total_working_time, total_fp, total_rev, total_corr, total_finals, total_issue) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insert->bind_param("isssissddsssddddd", 
            $user_id, $primary_cust_id, $access_other_cust_id, $dept_code, $other_dept_code, 
            $start_date, $end_date, $daysDifference, $total_pages_completed, $average_percentage, 
            $total_production_time, $total_working_time,
            $total_fp, $total_rev, $total_corr, $total_fin, $total_iss
        );
        if (!$insert->execute()) echo "❌ Master Insert Error: " . $insert->error . "<br>";
        else echo "✅ Inserted into master: ID = " . $insert->insert_id . "<br>";
        $master_id = $insert->insert_id;
        $insert->close();
    }

    // Update or Insert into details table
    foreach ($customer_data as $cust_id => $cust_info) {
        foreach ($cust_info['stages'] as $stage => $data) {
            $stage_percentage = ($data['target'] > 0) ? round(($data['completed'] / $data['target']) * 100, 2) : 0;
            $time_taken_stage = gmdate("H:i:s", $data['time']);

            $check_detail = $db->query("SELECT id FROM user_target_analytics_details 
                WHERE master_id='$master_id' AND user_id='$user_id' AND cust_id='$cust_id' AND stage='$stage'");
            if ($check_detail->num_rows > 0) {
                $detail_id = $check_detail->fetch_assoc()['id'];
                $update_detail = $db->prepare("UPDATE user_target_analytics_details 
                    SET completed=?, percentage=?, target=?, total_time=? WHERE id=?");
                $update_detail->bind_param("ddssi", $data['completed'], $stage_percentage, $data['target'], $time_taken_stage, $detail_id);
                if (!$update_detail->execute()) echo "❌ Detail Update Error: " . $update_detail->error . "<br>";
                else echo "✅ Updated detail for stage $stage and customer $cust_id<br>";
                $update_detail->close();
            } else {
                $insert_detail = $db->prepare("INSERT INTO user_target_analytics_details 
                    (master_id, user_id, cust_id, stage, completed, percentage, target, total_time) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $insert_detail->bind_param("iiisidds", $master_id, $user_id, $cust_id, $stage, $data['completed'], $stage_percentage, $data['target'], $time_taken_stage);
                if (!$insert_detail->execute()) echo "❌ Detail Insert Error: " . $insert_detail->error . "<br>";
                else echo "✅ Inserted detail for stage $stage and customer $cust_id<br>";
                $insert_detail->close();
            }
        }
    }
}
?>
