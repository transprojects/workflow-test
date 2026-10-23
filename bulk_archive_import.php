<?php
ini_set('max_execution_time', 0); // 0 = Unlimited
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);
include("dbc.php");
include("includes/global_variables.php");
include("scan_db.php");
include("simplexlsx-master/src/SimpleXLSX.php");
include("bulk_archive.php");
?>
<?php
// require_once __DIR__.'/simplexlsx-master/src/SimpleXLSX.php';

echo '<h1>Parse books.xslx</h1><pre>';
$var="uploads/peerj-new.xlsx";
if ( $xlsx = SimpleXLSX::parse($var)) {
	foreach( $xlsx->rows() as $row )
	{
		if($row == "")
		{
			continue;
		}
		
		//print_r($row);
		//// Get the journal details ////
	  $query = "SELECT * FROM `adm_journals` WHERE `j_code` = '".$row['2']."';";
	$journaldetails = $db->query($query) or ($db->error);
	$journalinfo = $journaldetails->fetch_assoc();
	echo $art_query = "SELECT * FROM `inw_inward_dtl` WHERE pub_id = '".$row['3']."';";
		$query11=mysqli_query($db, $art_query);

		if (mysqli_num_rows($query11)>0)
		{
			
			bulk_archive($db,$conn,$row['3']);
		}
		//// Here we add the Archive file to create Archive BKP and move to Archive Workflow ///
		
	}
	
} 
else 
{
	echo SimpleXLSX::parseError();
}
echo '<pre>';