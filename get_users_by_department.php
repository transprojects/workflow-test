<?php 
	include ('dbc.php');

$dept_id = $_POST['dept_id'];
$options = '<option value="">Select Employee</option>';

if ($dept_id != "") {
  $query = "SELECT id, full_name FROM users 
            WHERE department_id = ? 
               OR FIND_IN_SET(?, access_other_depts)";

  $stmt = $conn->prepare($query);
  $stmt->bind_param("is", $dept_id, $dept_id);
  $stmt->execute();
  $result = $stmt->get_result();

  while ($row = $result->fetch_assoc()) {
    $options .= "<option value='{$row['id']}'>{$row['full_name']}</option>";
  }
}

echo $options;
?>