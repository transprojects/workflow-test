<?php
include "includes/includes.php";
include "includes/paginate.php";
if(!isset($_POST['art_id'])){
$json_data = json_encode($_POST,true);
if ($json_data === false) {
    die(json_encode(["error" => "JSON encoding failed", "json_last_error" => json_last_error_msg()]));
}

$sql_query = "CALL add_edit_art('$json_data')";

$stmt = $db->prepare("CALL add_edit_art(?)");
if (!$stmt) {
    die(json_encode(["error" => "Prepare failed: " . $db->error]));
}

if (!$stmt->bind_param("s", $json_data)) {
    die(json_encode(["error" => "Binding failed: " . $stmt->error]));
}

if (!$stmt->execute()) {
    die(json_encode(["error" => "Execute failed: " . $stmt->error]));
}

$stmt->store_result();
$stmt->bind_result($result);
while ($stmt->fetch()) {
    echo json_encode($result);
}


$stmt->close();
$db->close();
}else{
  $sql_query = "update artconfig set del_flag=1 where art_id=".$_POST['art_id'];
  mysqli_query($db, $sql_query);
  echo json_encode("Deleted Successfully");
}
?>

