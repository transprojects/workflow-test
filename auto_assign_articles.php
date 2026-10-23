<?php
include ('includes/conn.php');

// Function to check if the user has been assigned articles today based on assigned_date
function isAssignedToday($user_id) {
    global $db;

    // Get the assigned_date for the user
    $query = "SELECT assigned_date FROM users WHERE id = $user_id LIMIT 1";
    $result = $db->query($query);
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Check if the assigned_date is today's date
        return $row['assigned_date'] == date('Y-m-d');
    } else {
        return false; // User not found
    }
}

// Function to update the assigned_date to today
function updateAssignedDate($user_id) {
    global $db;
    
    $query = "UPDATE users SET assigned_date = '" . date('Y-m-d') . "' WHERE id = $user_id";
    $db->query($query);
}

// Function to get the remaining daily limit for a user
function getRemainingDailyLimit($user_id, $cust_id) {
    global $db;
    
    // Get the total assigned count for this user today
    $query = "SELECT COUNT(id) as total_assigned FROM articles_assigned_to_user WHERE user_id = $user_id AND DATE(assigned_date) = CURRENT_DATE";
    $result = $db->query($query);
    $assigned = $result->fetch_assoc();
    
    // Retrieve the per_day_limit from user_auto_assign_config for this cust_id
    $query_config = "SELECT per_day_limit FROM user_auto_assign_config WHERE user_id = $user_id AND cust_id = $cust_id ORDER BY assign_order ASC LIMIT 1";
    $config_result = $db->query($query_config);
    $config = $config_result->fetch_assoc();
    
    // Calculate the remaining limit
	//echo $config['per_day_limit']; 
	//echo $assigned['total_assigned'];
    $remaining_limit = $config['per_day_limit'] - $assigned['total_assigned']; //exit;
    return max(0, $remaining_limit);  // Ensure the remaining limit doesn't go negative
}

// Function to assign articles to the user
function assignArticles($user_id) {
    global $db;

    // Get user configuration sorted by assign_order
    $query = "SELECT * FROM user_auto_assign_config WHERE user_id = $user_id ORDER BY assign_order ASC";
    $config_result = $db->query($query);

    if ($config_result->num_rows > 0) {
        while ($config = $config_result->fetch_assoc()) {
            $remaining_limit = getRemainingDailyLimit($user_id, $config['cust_id']); // Get remaining daily limit

            if ($remaining_limit > 0) {
                $department_id = $config['department_id'];

// Determine the appropriate column for assigned user ID based on department_id
if ($department_id == 1 || $department_id == 22) {
    $assigned_user_column = 'assigned_user_id';
} else {
    $assigned_user_column = 'assigned_od_user_id';
}

// Construct the query with the correct column
$query_articles = "SELECT * FROM inw_inward_dtl 
                   WHERE cust_id = " . $config['cust_id'] . " 
                   AND FIND_IN_SET(j_id, '" . $config['j_ids'] . "') > 0  
                   AND stage = '" . $config['stage'] . "' 
                   AND department_id = '" . $department_id . "' 
                   AND $assigned_user_column IS NULL  
                   AND status NOT IN ('Delivery', 'Client Review', 'Completed', 'Hold')  
                   LIMIT $remaining_limit";
                $articles_result = $db->query($query_articles);

                // Assign articles from this customer
                while ($article = $articles_result->fetch_assoc()) {
                    if ($remaining_limit <= 0) break;

                    // Insert the assignment into the new articles_assigned_to_user table
                    $due_date = date('Y-m-d');  // Setting due date to today
                    $assign_query = "INSERT INTO articles_assigned_to_user (user_id, article_id, assigned_date, due_date, status) 
                                     VALUES ($user_id, " . $article['id'] . ", NOW(), '$due_date', 'assigned')";
                    $db->query($assign_query);
					
					
				$assigned_user=mysqli_fetch_array(mysqli_query($db,"select full_name,department_id from users where id='".$user_id."'"));
				$current_user_deptname=mysqli_fetch_array(mysqli_query($db,"select * from adm_dept_master where dept_code='".$assigned_user['department_id']."'"));

				$user_working_status = '<span class="label label-sm label-success">'.$current_user_deptname['dept_name'].'</span>|<span class="label label-sm label-info">'.$assigned_user['full_name'].'</span>|<span class="label label-sm label-warning">Assigned</span>';

				if($config['department_id']=='1' || $config['department_id']=='22')
				{
					$assigned="`assigned_user_id` ='".$user_id."',ce_pe_due_date='".date('Y-m-d')."',user_working_status='".$user_working_status."'";
				}
				else
				{
					
					$assigned="`assigned_od_user_id` ='".$user_id."',od_due_date='".date('Y-m-d')."',user_working_status='".$user_working_status."'";
				}

				$prj_update_query="UPDATE inw_inward_dtl SET $assigned WHERE id = ".$article['id']." "; 
				
				$db->query($prj_update_query);
			
			
                    
                    // Decrease remaining limit
                    $remaining_limit--;
                }

                // If limit is reached, exit loop
                if ($remaining_limit <= 0) break;
            } else {
                echo "Daily limit already reached for this user.\n";
            }
        }

        // Mark the user as assigned today
        updateAssignedDate($user_id);
    } else {
        echo "No configuration found for user.\n";
    }
}

// First-time login: assume you have the user_id from session or other login process
//$user_id = 242;  // Example user ID

// Check if the user has already been assigned articles today
/*if (!isAssignedToday($user_id)) {
    assignArticles($user_id);
} else {
    echo "User has already been assigned articles today, skipping auto-assignment.\n";
}*/

// Close connection
//$db->close();
?>
