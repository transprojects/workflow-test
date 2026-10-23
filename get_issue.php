<?php
require_once('includes/includes.php');
?>

<?php
//read data
$j_id = filter($_REQUEST['j_id']);
$query = "SELECT * FROM `inw_inward_dtl` WHERE j_id='".$j_id."' group by volume_folder  ORDER BY `volume_folder` ASC ";
$customers = $db->query($query) or ($db->error);
?>
<select id="issue_no" name="issue_no" required class="col-xs-12 col-sm-4" data-placeholder="Choose">
<option value="">-Select Issue-</option>
<?php		
while($row = $customers->fetch_assoc())
{
?>
<option value="<?php echo $row['volume_folder'];?>"><?php echo $row['volume_folder'];?></option>
    <?php
}
?>
</select>