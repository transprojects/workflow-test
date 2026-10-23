<?php 

include ('includes/includes.php'); 
// include ('vendor/dbc.php');
		// page_protect();
if(isset($_REQUEST['id']))
{
	$result = $db->query("SELECT * FROM invoice_details WHERE id = ".$_REQUEST['id']." "); 	
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$invoice_info = $result->fetch_assoc();
		
		/// Get the invoice article page count details  ////
		$query = "SELECT * FROM `invoice_article_page_count_details` WHERE inv_id = ".$invoice_info['id']." ORDER BY `inv_id` ASC;";
		$page_counts = $db->query($query) or ($db->error);
		
		
		$query = "SELECT * FROM `invoice_items` WHERE invoice_id = ".$invoice_info['id']." ORDER BY `invoice_id` ASC;";
		$invoice_article_counts = $db->query($query) or ($db->error);
		
		
		$file= $invoice_info['inv_invoice_number'].".xls";
		header("Content-type: application/vnd.ms-excel");
		header("Content-Disposition: attachment; filename=$file");
		
		
				
	?>



<table border="1">
    <thead>
        <tr>
            <td>S.No</td>
            <th>Article</th>
            <th>Pages Count</th>
        </tr>
    </thead>
    <tbody>
        <?php
	$sno = 1;
	while($invoice_article_info = $invoice_article_counts->fetch_assoc())
	{ 
		
		
			$query = "SELECT pub_id FROM `inw_inward_dtl` WHERE id = ".$invoice_article_info['inw_id']."";
		$article_details = $db->query($query) or ($db->error);
		$article_info = $article_details->fetch_assoc();
		?>
        <tr>
            <td><?php echo $sno;?></td>
            <td><?php echo $article_info['pub_id'];?></td>
            <td><?php echo $invoice_article_info['page_count'];?></td>
        </tr>
        <?php $sno++; } 
	while($page_info = $page_counts->fetch_assoc()){
	 ?>
        <tr>
            <td></td>
            <td><?php echo $page_info['article_details'];?></td>
            <td><?php echo $page_info['page_count'];?></td>
        </tr>
        <?php } ?>
    </tbody>
</table>
<?php 
}		
	}

?>