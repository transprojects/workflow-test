

<?php
include ('includes/includes.php'); 
// include ('vendor/dbc.php');
// page_protect();

if(isset($_REQUEST["id"])){
   
	$result = $db->query("SELECT * FROM invoice_details WHERE id = ".(int)$_REQUEST['id']." "); 	
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$invoice_info = $result->fetch_assoc();
        $filepath = $invoice_info['invoice_file_path'] ."/". $invoice_info['inv_invoice_number'].".pdf";

        // Process download
        if(file_exists($filepath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filepath));
            flush(); // Flush system output buffer
            readfile($filepath);
            die();
        } else {
            http_response_code(404);
	        die();
        }
    }
	else {
        die("Invalid file name!");
    }
} else {
            http_response_code(404);
	        die();
        }
?>