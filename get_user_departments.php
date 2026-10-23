<?php
 include "includes/includes.php";
if (isset($_REQUEST['user_id'])) {
    $user_id = $_REQUEST['user_id'];

    // Fetch user details
    $user = $auth->getUserById($user_id);

    if ($user) {
        $departments = [];

        if (!empty($user['department_id'])) {
            $departments[] = $user['department_id'];
        }

        if (!empty($user['access_other_depts'])) {
            $access_depts = explode(',', $user['access_other_depts']);
            $departments = array_merge($departments, $access_depts);
        }

        $departments = array_unique($departments);
        $dept_details = $auth->getDepartmentsByCodes($departments);

        echo '<option value="">Select Department</option>';

        if ($user['department_id'] == 1) {
            $combined_depts = [1, 2, 22];
            $combined_names = [];

            foreach ($dept_details as $dept) {
                if (in_array($dept['dept_code'], $combined_depts)) {
                    $combined_names[] = $dept['dept_name'];
                }
            }

            if (!empty($combined_names)) {
                $label = implode(' / ', $combined_names) . ' (Primary Department)';
                echo '<option value="1" style="color: black; background-color:yellow;">' . $label . '</option>';
            }

            foreach ($dept_details as $dept) {
                if (!in_array($dept['dept_code'], $combined_depts)) {
                    echo '<option value="' . $dept['dept_code'] . '">' . $dept['dept_name'] . '</option>';
                }
            }
        } else {
            foreach ($dept_details as $dept) {
                $is_primary = ($dept['dept_code'] == $user['department_id']);
                $style = $is_primary ? 'style="color: black; background-color:yellow;"' : '';
                $label = $dept['dept_name'] . ($is_primary ? ' (Primary Department)' : '');
                echo '<option value="' . $dept['dept_code'] . '" ' . $style . '>' . $label . '</option>';
            }
        }
    } else {
        echo '<option value="">No Department Found</option>';
    }
}


?>
