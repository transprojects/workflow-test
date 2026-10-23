<?php
require_once('includes/includes.php');
?>

<?php
//read data
$cust_id = filter($_REQUEST['j_cust_id']);
$j_id = filter($_REQUEST['j_id']);

$query = "SELECT vi.starting_page_number, vi.stage,vi.vi_id,vi.received_date,vi.due_date,volume_number,issue_number,cus.cust_name,ju.j_code,(SELECT COUNT(id) FROM inw_inward_dtl WHERE v_issue_id = vi.vi_id AND status='Delivery') as ccount,(SELECT COUNT(id) FROM inw_inward_dtl WHERE v_issue_id = vi.vi_id AND status !='Delivery') as pcount FROM inw_volume_issues as vi LEFT JOIN adm_customer_master as cus ON vi.cust_id = cus.id LEFT JOIN adm_journals as ju ON vi.j_id = ju.j_id WHERE vi.status NOT IN ('Delivery','Client Review','Completed')and  vi.cust_id = '".$cust_id."' AND vi.j_id = '".$j_id."'  ORDER BY vi.volume_number ASC ";
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
