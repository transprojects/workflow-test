<?php
set_time_limit(0);
date_default_timezone_set('Asia/Kolkata');
include "includes/conn.php";

/// Get start and end date
$start_date = isset($_REQUEST['fdate']) && !empty($_REQUEST['fdate']) 
    ? date('Y-m-d', strtotime($_REQUEST['fdate'])) 
    : date('Y-m-d', strtotime('-1 day'));

$end_date = isset($_REQUEST['tdate']) && !empty($_REQUEST['tdate']) 
    ? date('Y-m-d', strtotime($_REQUEST['tdate'])) 
    : date('Y-m-d', strtotime('-1 day'));


// Create array of dates between start and end
$date_range = [];
$current_date = new DateTime($start_date);
$end = new DateTime($end_date);
$user_id='234,296,359,365,336,375,373,385,374,314,309,319,334,256,238,347,310,294,312,331,245,218,348,366,329,228,317,383,332,311,179,367,376,351,360,315,266,326,286,352,167,274,321,377,182,349,380,378,379,381,340,325';
//$user_id=$_REQUEST['user_id'];
while ($current_date <= $end) {
    $date_range[] = $current_date->format('Y-m-d');
    $current_date->modify('+1 day');
}

// User fetch query (modify user_id as needed)
$user_query = "SELECT DISTINCT uta.user_id,uta.uta_id, u.full_name, u.department_id, utca.dept_id,u.access_other_depts, u.cust_id, u.access_other_cust_id
               FROM user_target_customer_assign uta
               INNER JOIN users u ON uta.user_id = u.id
			   INNER JOIN user_target_assign utca  ON utca.uta_id = uta.uta_id
               WHERE u.approved = '1' AND uta.user_id in($user_id)";
$users = $db->query($user_query) or die($db->error);

while ($user_row = $users->fetch_assoc()) {
    $uta_id = $user_row['uta_id'];
    $user_id = $user_row['user_id'];
    $dept_code = $user_row['dept_id'];
    $other_dept_code = $user_row['access_other_depts'];
    $primary_cust_id = $user_row['cust_id'];
    $access_other_cust_id = $user_row['access_other_cust_id'];

    foreach ($date_range as $report_date) {
        $daysDifference = 1;
        $total_pages_completed = 0;
        $total_target_pages = 0;
        $total_percentage = 0;
        $total_production_seconds = 0;

        $stage_totals = ['FP'=>['completed'=>0,'target'=>0,'time'=>0],'REV'=>['completed'=>0,'target'=>0,'time'=>0],'CORR'=>['completed'=>0,'target'=>0,'time'=>0],'FIN'=>['completed'=>0,'target'=>0,'time'=>0],'ISS'=>['completed'=>0,'target'=>0,'time'=>0],'PRECLEAN'=>['completed'=>0,'target'=>0,'time'=>0]];
        $customer_data = [];

        $user_target_customer = $db->query("SELECT * FROM user_target_customer_assign 
                                            INNER JOIN adm_customer_master ON user_target_customer_assign.cust_id = adm_customer_master.id 
											 INNER JOIN user_target_assign utca  ON utca.uta_id = user_target_customer_assign.uta_id
                                            WHERE user_target_customer_assign.user_id='$user_id' and dept_id='".$user_row['dept_id']."' ");
        while ($res_user_target = $user_target_customer->fetch_assoc()) {
            $customer_id = $res_user_target['id'];
            $customer_name = $res_user_target['cust_name'];

            if (!isset($customer_data[$customer_id])) {
                $customer_data[$customer_id] = ['name'=>$customer_name, 'stages'=>[]];
            }
			if($dept_code==1)
			{
			$stages = ['FP' => 'page_count','PRECLEAN' => 'ppage_count'];
			$dept_code_s="AND inw.dept IN ('1','2','22')";
			}
			else
			{
            $stages = ['FP'=>'page_count','REV'=>'rpc','CORR'=>'cpc','FIN'=>'fpc','ISS'=>'ipc'];
			$dept_code_s="AND inw.dept IN ($dept_code)";
			}

if($dept_code==1)
{
//
            foreach ($stages as $stage => $target_field) {
				
				if ($stage == 'PRECLEAN') {
					
					  $query = "
                    WITH FirstOccurrences AS (
                        SELECT 
						inw.project_id, 
						 CASE 
                WHEN inw.pages_completed IS NOT NULL AND inw.pages_completed != 0 THEN inw.pages_completed
                WHEN wd.page_count IS NOT NULL AND wd.page_count != 0 THEN wd.page_count
                ELSE wd.manuscript_count
            END AS pages_completed,
                            ROW_NUMBER() OVER (PARTITION BY inw.project_id ORDER BY inw.created_dt) AS rn
                        FROM inw_transactions AS inw
                        INNER JOIN inw_inward_dtl AS wd ON inw.project_id = wd.id
                        WHERE DATE_FORMAT(inw.created_dt, '%Y-%m-%d') = '$report_date'
                            AND wd.cust_id = '$customer_id'
                            AND current_stage LIKE '%FP%'
							AND inw.dept='22'
							AND current_status in ('Completed','Assign','To Cross Check','Correction Done')
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
                    WHERE DATE_FORMAT(inw.created_dt, '%Y-%m-%d') = '$report_date'
                        AND wd.cust_id = '$customer_id'
						AND current_status in ('Completed','Assign','To Cross Check','Correction Done')
                        AND inw.process_user = '$user_id'
						AND inw.dept in ('22')
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
               // $total_production_seconds += $time_taken;

               

                if ($target_pages > 0) {
                    $total_percentage += round(($pages_completed / $target_pages) * 100, 2);
                }
				$stage_totals['FP']['completed'] += $pages_completed;

				}
				else
				{
                $status_condition = ($stage == 'CORR') ? "inw.current_status = 'Correction Done'" : "current_status in ('Completed','Assign','To Cross Check')";
                $stage_condition = ($stage != 'CORR') ? "AND current_stage LIKE '%$stage%'" : "";

                $query = "
                    WITH FirstOccurrences AS (
                        SELECT inw.project_id,
						 CASE 
                WHEN inw.pages_completed IS NOT NULL AND inw.pages_completed != 0 THEN inw.pages_completed
                WHEN wd.page_count IS NOT NULL AND wd.page_count != 0 THEN wd.page_count
                ELSE wd.manuscript_count
            END AS pages_completed,
                            ROW_NUMBER() OVER (PARTITION BY inw.project_id ORDER BY inw.created_dt) AS rn
                        FROM inw_transactions AS inw
                        INNER JOIN inw_inward_dtl AS wd ON inw.project_id = wd.id
                        WHERE DATE_FORMAT(inw.created_dt, '%Y-%m-%d') = '$report_date'
                            AND wd.cust_id = '$customer_id'
                            AND current_status in ('Completed','Assign','To Cross Check','Correction Done')
                            AND inw.process_user = '$user_id'
							AND inw.dept in ('1','2')
                    )
                    SELECT SUM(pages_completed) AS pages_completed FROM FirstOccurrences WHERE rn = 1
                ";
                $result = $db->query($query) or die($db->error);
                $pages_completed = (float)($result->fetch_assoc()['pages_completed'] ?? 0);

                $time_query = "
                    SELECT SUM(TIMESTAMPDIFF(SECOND, start_time, end_time)) AS total_time 
                    FROM inw_transactions AS inw
                    INNER JOIN inw_inward_dtl AS wd ON inw.project_id = wd.id
                    WHERE DATE_FORMAT(inw.created_dt, '%Y-%m-%d') = '$report_date'
                        AND wd.cust_id = '$customer_id'
                        AND $status_condition
                        $stage_condition
                        AND inw.process_user = '$user_id'
						AND inw.dept in ('1','2','22')
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
                $total_production_seconds += $time_taken;

                if ($target_pages > 0) {
                    $total_percentage += round(($pages_completed / $target_pages) * 100, 2);
                }
				}
         
            }
			//
}
else
{
	  foreach ($stages as $stage => $target_field) {
				
				
                $status_condition = ($stage == 'CORR') ? "inw.current_status = 'Correction Done'" : "current_status in ('Completed','Assign','To Cross Check')";
                $stage_condition = ($stage != 'CORR') ? "AND current_stage LIKE '%$stage%'" : "";

                $query = "
                    WITH FirstOccurrences AS (
                        SELECT inw.project_id,
						 CASE 
                WHEN inw.pages_completed IS NOT NULL AND inw.pages_completed != 0 THEN inw.pages_completed
                WHEN wd.page_count IS NOT NULL AND wd.page_count != 0 THEN wd.page_count
                ELSE wd.manuscript_count
            END AS pages_completed,
                            ROW_NUMBER() OVER (PARTITION BY inw.project_id ORDER BY inw.created_dt) AS rn
                        FROM inw_transactions AS inw
                        INNER JOIN inw_inward_dtl AS wd ON inw.project_id = wd.id
                        WHERE DATE_FORMAT(inw.created_dt, '%Y-%m-%d') = '$report_date'
                            AND wd.cust_id = '$customer_id'
                            AND $status_condition
                            $stage_condition
                            AND inw.process_user = '$user_id'
							$dept_code_s
                    )
                    SELECT SUM(pages_completed) AS pages_completed FROM FirstOccurrences WHERE rn = 1
                ";
                $result = $db->query($query) or die($db->error);
                $pages_completed = (float)($result->fetch_assoc()['pages_completed'] ?? 0);

                $time_query = "
                    SELECT SUM(TIMESTAMPDIFF(SECOND, start_time, end_time)) AS total_time 
                    FROM inw_transactions AS inw
                    INNER JOIN inw_inward_dtl AS wd ON inw.project_id = wd.id
                    WHERE DATE_FORMAT(inw.created_dt, '%Y-%m-%d') = '$report_date'
                        AND wd.cust_id = '$customer_id'
                        AND $status_condition
                        $stage_condition
                        AND inw.process_user = '$user_id'
						$dept_code_s
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
                $total_production_seconds += $time_taken;

                if ($target_pages > 0) {
                    $total_percentage += round(($pages_completed / $target_pages) * 100, 2);
                }
				
         
            }
}
        }

        $average_percentage = ($daysDifference > 0) ? ($total_percentage / $daysDifference) : 0;
        $total_production_time = gmdate("H:i:s", $total_production_seconds);

        $total_fp = $stage_totals['FP']['completed'];
        $total_rev = $stage_totals['REV']['completed'];
        $total_corr = $stage_totals['CORR']['completed'];
        $total_finals = $stage_totals['FIN']['completed'];
        $total_issue = $stage_totals['ISS']['completed'];

        $working_time_query = "
            SELECT 
        SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND, start_time, end_time))) AS total_working_time
        
    FROM inw_transactions 
    WHERE process_user = '$user_id'
    AND DATE_FORMAT(start_time, '%Y-%m-%d') >= '$report_date'
    AND DATE_FORMAT(end_time, '%Y-%m-%d') <= '$report_date'
        ";
        $working_time_result = $db->query($working_time_query) or die($db->error);
        $working_time_data = $working_time_result->fetch_assoc();
     //   $first_start = $working_time_data['first_start'];
      //  $last_end = $working_time_data['last_end'];
       // $total_working_seconds = ($first_start && $last_end) ? strtotime($last_end) - strtotime($first_start) : 0;
       // $total_working_time = gmdate("H:i:s", $total_working_seconds);
        //$total_working_time = gmdate("H:i:s", $working_time_data['total_working_time']);
        $total_working_time = $working_time_data['total_working_time'];

        // Insert Master
        $insert_master = $db->prepare("INSERT INTO user_target_analytics 
            (uta_id,user_id, primary_cust_id, access_other_cust_id, dept_id, other_dept_code, from_date, to_date, days, total_worked_pages, total_percentage, total_production_time, total_working_time,
             total_fp, total_rev, total_corr, total_finals, total_issue) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insert_master->bind_param(
            "iisssssssssssddddd",
             $uta_id, $user_id, $primary_cust_id, $access_other_cust_id, $dept_code, $other_dept_code, $report_date, $report_date, $daysDifference,
            $total_pages_completed, $average_percentage, $total_production_time, $total_working_time,
            $total_fp, $total_rev, $total_corr, $total_finals, $total_issue
        );

        if ($insert_master->execute()) {
            $master_id = $insert_master->insert_id;
            echo "✅ Inserted master for user $user_id for date $report_date (ID: $master_id)<br>";
        } else {
            echo "❌ Insert Error for user $user_id on $report_date: " . $insert_master->error . "<br>";
            continue;
        }
        $insert_master->close();

        // Insert Details
        foreach ($customer_data as $cust_id => $cust_info) {
            foreach ($cust_info['stages'] as $stage => $data) {
                $stage_percentage = ($data['target'] > 0) ? round(($data['completed'] / $data['target']) * 100, 2) : 0;
                $time_taken_stage = gmdate("H:i:s", $data['time']);

                $insert_detail = $db->prepare("INSERT INTO user_target_analytics_details 
                    (master_id, user_id, cust_id, stage, completed, percentage, target, total_time) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $insert_detail->bind_param("iiisidds", $master_id, $user_id, $cust_id, $stage, $data['completed'], $stage_percentage, $data['target'], $time_taken_stage);

                if (!$insert_detail->execute()) {
                    echo "❌ Detail Insert Error: " . $insert_detail->error . "<br>";
                } else {
                    echo "✅ Inserted detail for user $user_id, date $report_date, customer $cust_id, stage $stage<br>";
                }
                $insert_detail->close();
            }
        }
    }
}
?>
