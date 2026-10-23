<?php
require_once('includes/includes.php');
?>

<?php
//read data
$cust_id = filter($_REQUEST['j_cust_id']);
$j_id = filter($_REQUEST['j_id']);

$query = "SELECT `vi_id`,`volume_number`,issue_number FROM `inw_volume_issues` WHERE cust_id = '".$cust_id."' AND j_id = '".$j_id."'  ORDER BY `volume_number` ASC ";
$volume_issue = $db->query($query) or ($db->error);				

?>
<option value="">-Select-</option>
<?php		
while($row = $volume_issue->fetch_assoc())
{
	?>   
 <option value="<?php echo $row['vi_id']; ?>" ><?php echo VOLUME_PREFIX.$row['volume_number'].$row['issue_number']; ?></option>
    <?php
}
?>
