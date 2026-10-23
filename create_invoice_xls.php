<?php include "includes/includes.php";
include_once 'includes/list_ui.php';
include ('dbc.php'); 
include ('includes/paginate.php');
// page_protect();


function convertNumberToWord($num = false)
{
    $num = str_replace(array(',', ' '), '' , trim($num));
    if(! $num) {
        return false;
    }
    $num = (int) $num;
    $words = array();
    $list1 = array('', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven',
        'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'
    );
    $list2 = array('', 'ten', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety', 'hundred');
    $list3 = array('', 'thousand', 'million', 'billion', 'trillion', 'quadrillion', 'quintillion', 'sextillion', 'septillion',
        'octillion', 'nonillion', 'decillion', 'undecillion', 'duodecillion', 'tredecillion', 'quattuordecillion',
        'quindecillion', 'sexdecillion', 'septendecillion', 'octodecillion', 'novemdecillion', 'vigintillion'
    );
    $num_length = strlen($num);
    $levels = (int) (($num_length + 2) / 3);
    $max_length = $levels * 3;
    $num = substr('00' . $num, -$max_length);
    $num_levels = str_split($num, 3);
    for ($i = 0; $i < count($num_levels); $i++) {
        $levels--;
        $hundreds = (int) ($num_levels[$i] / 100);
        $hundreds = ($hundreds ? ' ' . $list1[$hundreds] . ' hundred' . ' ' : '');
        $tens = (int) ($num_levels[$i] % 100);
        $singles = '';
        if ( $tens < 20 ) {
            $tens = ($tens ? ' ' . $list1[$tens] . ' ' : '' );
        } else {
            $tens = (int)($tens / 10);
            $tens = ' ' . $list2[$tens] . ' ';
            $singles = (int) ($num_levels[$i] % 10);
            $singles = ' ' . $list1[$singles] . ' ';
        }
        $words[] = $hundreds . $tens . $singles . ( ( $levels && ( int ) ( $num_levels[$i] ) ) ? ' ' . $list3[$levels] . ' ' : '' );
    } //end for loop
    $commas = count($words);
    if ($commas > 1) {
        $commas = $commas - 1;
    }
    return implode(' ', $words);
}


if(isset($_REQUEST['getaction']) && $_REQUEST['getaction']!='')
{

	$query = "SELECT  * FROM `customer_invoice_config` where cic_id='".$_POST['cust_id']."' ORDER BY `cic_id` ASC ";
	$customer_invoice = $db->query($query) or ($db->error);
	$customer_invoice_info = $customer_invoice->fetch_assoc();
	
$_SESSION['fcust_id'] = $_POST['cust_id'];
$_SESSION['company_id'] = $_POST['company_id'];		
$_SESSION['stage_id'] = $_POST['stage_id'];
$_SESSION['mfrom_dt'] = $_POST['mfrom_dt'];
$_SESSION['check_page_count'] = $_POST['check_page_count'];
$_SESSION['j_complexity'] = $_POST['j_complexity'];
$_SESSION['division'] = $_POST['division'];
$_SESSION['published']=$customer_invoice_info['cust_id'];

$var = '';
$stage = '';
if($customer_invoice_info['cust_id']!=''){
	
     $var.= "and c.id = '".$customer_invoice_info['cust_id']."' ";
}
if($customer_invoice_info['stage']!=''){
	
	
		 $stage = $customer_invoice_info['stage'];
      // $var.= "and dh.stage = '".$customer_invoice_info['stage']."' ";
	  if($customer_invoice_info['stage']== "REV" && $customer_invoice_info['cust_id']==  "33")
	  {
		  $var.= "and dh.stage LIKE '%REV1%' ";
	  }
	  elseif($customer_invoice_info['stage']== "FP" )
	  {
		  $var.= "and dh.stage LIKE '%".$customer_invoice_info['stage']."%'  ";
	  }
	   else
	  
	  {
		  $var.= "and dh.stage LIKE '%".$customer_invoice_info['stage']."%' ";
	  }
      
	  
}

if(!empty($customer_invoice_info['j_ids']) && !is_null($customer_invoice_info['j_ids']))
{
	$var.= " and j.j_id IN (".$customer_invoice_info['j_ids'].") ";
}

if($_SESSION['mfrom_dt']!=''){
	
   $var.="and DATE_FORMAT(dh.sent_date,'%m-%Y')='" . $_SESSION['mfrom_dt'] . "' ";
   //$var.="and DATE_FORMAT(dh.sent_date,'%m-%Y')>='03-2022' AND  DATE_FORMAT(dh.sent_date,'%m-%Y')<='07-2023'";
}
if($customer_invoice_info['division']!=''){
	$division = array_search ($customer_invoice_info['division'], $division_array);
      $var.= " and j.j_division = '".$division."' ";
}
if($_SESSION['j_complexity']!=''){	
      $var.= " and j.j_complexity = '".$_SESSION['j_complexity']."' ";
}


	 $_SESSION['search_qry'] = $var;
	 $_SESSION['stage'] = $stage;
}

/////////////// satart creating the invoice with Articls data /////
if(isset($_REQUEST['confirm']) && $_REQUEST['confirm']!='')
{
	///// Get the Invoice details ////
	
	$query = "SELECT  * FROM `customer_invoice_config` where cic_id='".$_POST['cust_id']."' ORDER BY `cic_id` ASC ";
	$customer_invoice = $db->query($query) or ($db->error);
	$customer_invoice_info = $customer_invoice->fetch_assoc();
	
	//// Get the Company Details /////
	$query = "Select * From company_details WHERE id = '".$_POST['company_id']."'";
	$company_details = $db->query($query) or ($db->error);
	$company = $company_details->fetch_assoc();
	
	//// Get the Company Details /////
	/// Get the Customer Details /////	
	$query = "SELECT * FROM `adm_customer_master` WHERE id = '".$customer_invoice_info['cust_id']."' ";
	$customers = $db->query($query) or ($db->error);
	$crow = $customers->fetch_assoc();
	
	if($company['address_line_2'] != "")
	{ 
		$company['address_line_2'] = ", ".$company['address_line_2'];
	}
	
	if($crow['address_line_2'] != "")
	{ 
		$crow['address_line_2'] = ", ".$crow['address_line_2'];
	}
	
	
	$inv_supplier_address = $company['company_name']."\n". $company['address_line_1'].$company['address_line_2'].", ".$company['city']." - ".$company['zip'].",".$company['country']. "\n T:". $company['phone'];
	$inv_customer_address = $crow['invoice_company_name']."\n". $crow['address_line_1'].$crow['address_line_2'].", ".$crow['city'].", ".$crow['zip'].",".$crow['country']. "\n Tel.". $crow['phone']. "\n Fax.". $crow['fax']. "\n Email.". $crow['email']. "\n Attn:";
	
	$month_num = substr($_SESSION['mfrom_dt'],0,2) ;
	$year_num = substr($_SESSION['mfrom_dt'],5) ;
	$month = date("F", mktime(0, 0, 0, $month_num, 10));
	$year = substr($_SESSION['mfrom_dt'],3);
	$invoice_file_path = INVOICE_FILE_PATH.$year."_".$month."/";
	
	$inv_invoice_number = $crow['cust_name'] ." - ". $month ." - ". $year_num;
	$inv_invoice_date = date("d M Y");
	$inv_supplier_pan = $company['pan_number'];
	$inv_supplier_cin = $company['cin_number'];
	$inv_type_of_project = $company['inv_type_of_project'];
	$inv_supplier_gstin = $company['gstin_number'];
	$inv_invoice_detail = $company['inv_invoice_detail'];
	$inv_import_export_code_no = $company['inv_import_export_code_no'];
	$inv_satellite_station = $company['inv_satellite_station'];
	$inv_destination = $company['inv_destination'];
	$inv_purchase_order_details = $company['inv_purchase_order_details'];
	$inv_beneficiay_name = $company['beneficiary_name'];
	$inv_beneficiay_account_no = $company['beneficiay_account_no'];
	$inv_hdfc_bank_name = $company['bank_name'];
	$inv_hdfc_bank_ifsc = $company['bank_ifsc'];
	$inv_hdfc_bank_swift_code = $company['bank_swift_code'];
	$inv_correspondance_bank_details = $company['correspondance_bank_details'];
	$inv_correspondance_bank_name = $company['correspondance_bank_name'];
	$inv_correspondance_bank_swift_code = $company['correspondance_bank_swift_code'];
	
	$date = new DateTime('now');
	$date->modify('last day of this month');


	$inv_invoice_vaild_details = "On export sales, IGST is exempted in terms of Indian Government Notification dt. ".$date->format("d/m/Y").". Letter of undertaking executed and reference no.: AD330321028196I - Validity upto ".date("M Y");
	$status = "1";
	
	$insert_query = "INSERT INTO `invoice_details` (inv_supplier_address,`inv_customer_address`, `inv_invoice_number`, `inv_invoice_date`, `inv_supplier_pan`, `inv_supplier_cin`, `inv_type_of_project`, `inv_supplier_gstin`, `inv_invoice_detail`, `inv_import_export_code_no`, `inv_satellite_station`, `inv_destination`, `inv_purchase_order_details`, `inv_beneficiay_name`, `inv_beneficiay_account_no`, `inv_hdfc_bank_name`, `inv_hdfc_bank_swift_code`, `inv_hdfc_bank_ifsc`, `inv_correspondance_bank_details`, `inv_correspondance_bank_name`, `inv_correspondance_bank_swift_code`, `inv_invoice_vaild_details`, `status`, `invoice_year`, `invoice_month`, `invoice_file_path`, `created_on`, `created_by`) VALUES ('".$inv_supplier_address."', '".$inv_customer_address."',  '".$inv_invoice_number."', '".$inv_invoice_date."', '".$inv_supplier_pan."', '".$inv_supplier_cin."', '".$inv_type_of_project."', '".$inv_supplier_gstin."', '".$inv_invoice_detail."', '".$inv_import_export_code_no."', '".$inv_satellite_station."', '".$inv_destination."', '".$inv_purchase_order_details."', '".$inv_beneficiay_name."', '".$inv_beneficiay_account_no."', '".$inv_hdfc_bank_name."', '".$inv_hdfc_bank_swift_code."', '".$inv_hdfc_bank_ifsc."', '".$inv_correspondance_bank_details."', '".$inv_correspondance_bank_name."', '".$inv_correspondance_bank_swift_code."', '". $inv_invoice_vaild_details."', '". $status."', '". $year_num."', '". $month."', '". $invoice_file_path."', NOW(), '".$_SESSION['user_id']."');";	
	$db->query($insert_query) or ($db->error);
	$invoice_id = $db->insert_id;
		
		$var = '';
if($customer_invoice_info['cust_id']!=''){
	
     $var.= "and c.id = '".$customer_invoice_info['cust_id']."' ";
}
if($customer_invoice_info['stage']!=''){
	
      $var.= "and dh.stage LIKE '%".$customer_invoice_info['stage']."%' ";
}

if($_POST['mfrom_dt']!=''){
	
   $var.="and DATE_FORMAT(dh.sent_date,'%m-%Y')='" . $_POST['mfrom_dt'] . "' ";
}
if($customer_invoice_info['division']!=''){
	$division = array_search ($customer_invoice_info['division'], $division_array);
      $var.= " and j.j_division = '".$division."' ";
}


	 $_SESSION['search_qry'] = $var;
	 $_SESSION['stage'] = $customer_invoice_info['stage'];
	 
	if(substr( $customer_invoice_info['stage'], 0, 2 ) === "FP" || substr( $customer_invoice_info['stage'], 0, 3 ) === "REV" || substr( $customer_invoice_info['stage'], 0, 3 ) === "FIN" || substr( $customer_invoice_info['stage'], 0, 3 ) === "ISS" )
    {
		
		if($_SESSION['search_qry']!="")
		{    
			$cnt_query = "";
			$query = "";
			
			if(substr( $customer_invoice_info['stage'], 0, 3 ) === "ISS" )
		{
			$cnt_query = "SELECT COUNT(dh.idh_id) as num FROM inw_issue_dispatch_history as dh, `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE dh.a_id = wd.id AND wd.cust_id = c.id and wd.j_id = j.j_id and wd.pub_id NOT LIKE '%toc%' and wd.id NOT IN (SELECT inw_id FROM invoice_items) ";
			$query = "SELECT wd.auto_pdf_page_count, c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,dh.sent_date,wd.stage  FROM inw_issue_dispatch_history as dh, `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j  WHERE dh.a_id = wd.id AND wd.cust_id = c.id and wd.j_id = j.j_id and wd.pub_id NOT LIKE '%toc%' and wd.id NOT IN (SELECT inw_id FROM invoice_items) ";
		}
		
		else
		{
			$cnt_query = "SELECT COUNT(dh.idh_id) as num FROM inw_dispatch_history as dh, `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE dh.a_id = wd.id AND wd.cust_id = c.id and wd.j_id = j.j_id and wd.pub_id NOT LIKE '%toc%' and wd.id NOT IN (SELECT inw_id FROM invoice_items) ";
			$query = "SELECT wd.auto_pdf_page_count, c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,dh.sent_date,wd.stage  FROM inw_dispatch_history as dh, `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j  WHERE dh.a_id = wd.id AND wd.cust_id = c.id and wd.j_id = j.j_id and wd.pub_id NOT LIKE '%toc%' and wd.id NOT IN (SELECT inw_id FROM invoice_items)"; 
			$published="";
			if($crow['cust_name'] == "EOS")
			{
			  $published=" and published='1' ";
			}
			
		
		}
			
			$cnt_query = $cnt_query.$published. $_SESSION['search_qry'];
			
			// How many adjacent pages should be shown on each side?
							
			$customers = $db->query($cnt_query) or ($db->error);
			$page_count = $customers->fetch_assoc();
			$total_pages = $page_count['num'];	
			
			$order_by = " ORDER BY dh.sent_date asc";	
		
			$query = $query.$published.$_SESSION['search_qry'].$order_by;  
			$customers = $db->query($query) or ($db->error);
			$total_pages_count = 0;
			$sno=1;
			$below_twenty = 0;
			$above_twenty = 0;			
			while($row = $customers->fetch_assoc())
			{
				$total_pages_count += $row['auto_pdf_page_count']; 
				if ($row['auto_pdf_page_count'] <= 20)
				{
					$below_twenty++;  
				}
				if ($row['auto_pdf_page_count'] > 20)
				{
					$above_twenty++;  
				}	  
				$sno++; 
				
				$insert_query = "INSERT INTO `invoice_items` (invoice_id,`inw_id`, `page_count`, `created_by`, `created_on`) VALUES ('".$invoice_id."', '".$row['id']."',  '".$row['auto_pdf_page_count']."', '".$_SESSION['user_id']."',  NOW());";	
				$db->query($insert_query) or ($db->error);
			} 
			
			if($crow['cust_name'] == "TSP")
			{
				$order = 1;
				$amount = 0;
				if($below_twenty > 0)
				{
					$below_twenty_amount = $below_twenty * 30;
					$amount = $amount + $below_twenty_amount;
					$below_twenty_amount = number_format($below_twenty_amount, 2);
					$insert_query = "INSERT INTO `invoice_article_page_count_details` (inv_id,`order`, `article_details`, `page_count`, `rate`, `currency`, `amount`, `created_by`, `created_on`) VALUES ('".$invoice_id."', '".$order."', ' Normal Articles Upto 20 pages ',  '".$below_twenty."', '30.00', 'USD', '".$below_twenty_amount."', '".$_SESSION['user_id']."',  NOW());";	
					$db->query($insert_query) or ($db->error);
					$order++;
				}
				if($above_twenty > 0)
				{
					$above_twenty_amount = $above_twenty * 35;
					$amount = $amount + $above_twenty_amount;
					$above_twenty_amount = number_format($above_twenty_amount, 2);
					$insert_query = "INSERT INTO `invoice_article_page_count_details` (inv_id,`order`, `article_details`, `page_count`, `rate`, `currency`, `amount`,  `created_by`, `created_on`) VALUES ('".$invoice_id."','".$order."', ' Normal Articles more than 20 pages ', '".$above_twenty."', '35.00', 'USD', '".$above_twenty_amount."', '".$_SESSION['user_id']."',  NOW());";	
					$db->query($insert_query) or ($db->error);
					$order++;
				}
				
				//// Updateing the total amount, currency and Amount in words/////
				$amount = number_format($amount, 2);
				$amount_in_words = "USD " . convertNumberToWord($amount). " only";
				$update_query="UPDATE invoice_details SET `total_amount` ='".$amount."',`currency` ='USD', `inv_amount_in_words` ='".$amount_in_words."' WHERE id = ".$invoice_id." "; 
				$db->query($update_query);
				header("location:create_invoice_pdf.php?id=".$invoice_id."&&msg=Invoice Successfully Created");
			}
			elseif($crow['cust_name'] == "EOS")
			{
				$order = 1;
				$amount = 0;
				if($below_twenty > 0)
				{
					$below_twenty_amount = $below_twenty * 30;
					$amount = $amount + $below_twenty_amount;
					$below_twenty_amount = number_format($below_twenty_amount, 2);
					$insert_query = "INSERT INTO `invoice_article_page_count_details` (inv_id,`order`, `article_details`, `page_count`, `rate`, `currency`, `amount`, `created_by`, `created_on`) VALUES ('".$invoice_id."', '".$order."', ' Normal Articles Upto 20 pages ',  '".$below_twenty."', '30.00', 'USD', '".$below_twenty_amount."', '".$_SESSION['user_id']."',  NOW());";	
					$db->query($insert_query) or ($db->error);
					$order++;
				}
				if($above_twenty > 0)
				{
					$above_twenty_amount = $above_twenty * 35;
					$amount = $amount + $above_twenty_amount;
					$above_twenty_amount = number_format($above_twenty_amount, 2);
					$insert_query = "INSERT INTO `invoice_article_page_count_details` (inv_id,`order`, `article_details`, `page_count`, `rate`, `currency`, `amount`,  `created_by`, `created_on`) VALUES ('".$invoice_id."','".$order."', ' Normal Articles more than 20 pages ', '".$above_twenty."', '35.00', 'USD', '".$above_twenty_amount."', '".$_SESSION['user_id']."',  NOW());";	
					$db->query($insert_query) or ($db->error);
					$order++;
				}
				
				//// Updateing the total amount, currency and Amount in words/////
				$amount = number_format($amount, 2);
				$amount_in_words = "USD " . convertNumberToWord($amount). " only";
				$update_query="UPDATE invoice_details SET `total_amount` ='".$amount."',`currency` ='USD', `inv_amount_in_words` ='".$amount_in_words."' WHERE id = ".$invoice_id." "; 
				$db->query($update_query);
				header("location:create_invoice_pdf.php?id=".$invoice_id."&&msg=Invoice Successfully Created");
			}
			
			
		}	
	}	
}?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php lu_css(); ?>
    <style>
  .ci-wrap{width:min(1280px,calc(100% - 28px));margin:16px auto;}
  .ci-card{background:#fff;border:1px solid #e4e9ef;border-radius:16px;box-shadow:0 4px 18px rgba(20,48,74,.06);padding:20px 24px;margin-bottom:18px;}
  .ci-card .container,.ci-card .container-fluid{max-width:none;padding:0;margin:0;} .ci-card .monthly1outer{margin:0 !important;}
  /* one-row filter bar (same look as the Current Articles page) */
  .ci-card:first-of-type{padding:12px 16px;}
  .ci-card .container.d-flex{display:block !important;} .ci-card .monthly1outer{display:flex !important;flex-direction:row !important;flex-wrap:wrap;align-items:flex-end;gap:12px 14px;width:100%;margin:0 !important;}
  .ci-card .row.m-5{margin:0 !important;flex:1 1 auto;display:flex;flex-wrap:wrap;gap:12px 14px;align-items:flex-end;} .ci-card .row.m-5 > [class*="col-"]{flex:1 1 190px;max-width:280px;width:auto;padding:0;display:flex;flex-direction:column;gap:5px;}
  .ci-card label{margin:0 !important;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6b7785;}
  .ci-card .form-select,.ci-card .form-control,.ci-card select{height:42px !important;border:1px solid #d5dde6 !important;border-radius:10px !important;background-color:#fbfcfe !important;font-size:14px;width:100%;}
  .ci-card .select2-container{width:100% !important;}
  .ci-card .input-group{position:relative;display:block;} .ci-card .input-group .input-group-addon{display:none;}
  .ci-card .input-group input.form-control{padding:0 38px 0 12px;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%231b6ca8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='5' width='18' height='16' rx='3'/%3E%3Cpath d='M16 3v4M8 3v4M3 10h18'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;cursor:pointer;}
  .ci-card .row.m-5 > [class*="col-"]:has(input[type=checkbox]){flex:0 0 auto;flex-direction:row;align-items:center;gap:8px;height:42px;padding:0 14px;border:1px solid #d5dde6;border-radius:10px;background:#f9fbfd;align-self:flex-end;}
  .ci-card .row.m-5 > [class*="col-"]:has(input[type=checkbox]) label{display:none;} .ci-card .row.m-5 .lbl{font-size:13.5px;font-weight:600;color:#22303f;text-transform:none;letter-spacing:0;}
  .ci-card .meta_nxtbtn{margin:0 0 0 auto !important;padding:0 !important;width:auto;max-width:none;} .ci-card .meta_nxtbtn .row{display:flex;margin:0;} .ci-card .meta_nxtbtn .col{flex:0 0 auto;width:auto;padding:0;} .ci-card .meta_nxtbtn .nxtbtn{display:none;}
  .ci-card input[type=submit].btn-info{height:42px;padding:0 24px;border:0;border-radius:10px;font-weight:700;color:#fff;background:linear-gradient(135deg,#e8742a,#f08a45);box-shadow:0 4px 12px rgba(232,116,42,.35);}
  .ci-card .pb-3 a.btn-primary{display:inline-flex;align-items:center;gap:8px;height:40px;padding:0 18px;border:1px solid #cfd8e3;border-radius:10px;background:#fff;color:#14304a !important;font-weight:700;text-decoration:none;}
  .ci-card .pb-3 a.btn-primary:hover{background:#14304a;color:#fff !important;}
  .ci-card.lu-card{padding:20px 24px;}
  .ci-card table.custtable th,.ci-card table.custtable td{background:transparent !important;color:#14304a !important;border:0 !important;border-bottom:1px solid #eef1f5 !important;padding:11px 12px !important;font-size:13.5px;white-space:nowrap;}
  .ci-card table.custtable thead th,.ci-card table.custtable thead td{background:#eef3f8 !important;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.3px;border-bottom:1px solid #dbe3ec !important;}
  .ci-card table.custtable thead th i,.ci-card table.custtable thead td i{color:#6b7785 !important;}
  .ci-card table.custtable tbody tr:hover td{background:#f7fafd !important;}
</style>
    <style>
    .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        display: none;
    }

    .loader-container {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 1001;
        display: none;
    }

    .loader {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 2s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
    </style>
    <style>
    .pagination > li > a {
  border-width: 1px;
  border-color: #d4dfe3;
  color: #2283C5;
  background-color: #FAFAFA;
  margin: 0 -1px 0 0;
  position: relative;
  z-index: auto;
}
.pagination > li > a, .pagination > li > span {
  position: relative;
  float: left;
  padding: 6px 12px;
  line-height: 1.42857143;
  text-decoration: none;
  color: #337ab7;
  background-color: #fff;
  border: 1px solid #ddd;
    border-top-width: 1px;
    border-top-color: rgb(221, 221, 221);
    border-right-width: 1px;
    border-right-color: rgb(221, 221, 221);
    border-bottom-width: 1px;
    border-bottom-color: rgb(221, 221, 221);
    border-left-width: 1px;
    border-left-color: rgb(221, 221, 221);
  margin-left: -1px;
}
.pagination > li > span {
  position: relative;
  float: left;
  padding: 6px 12px;
  line-height: 1.42857143;
  text-decoration: none;
  color: #337ab7;
  background-color: #fff;
  border: 1px solid #ddd;
    border-top-width: 1px;
    border-top-color: rgb(221, 221, 221);
    border-right-width: 1px;
    border-right-color: rgb(221, 221, 221);
    border-bottom-width: 1px;
    border-bottom-color: rgb(221, 221, 221);
    border-left-width: 1px;
    border-left-color: rgb(221, 221, 221);
  margin-left: -1px;
}
.pagination > .active > span, .pagination > .active > span:focus, .pagination > .active > span:hover {
  z-index: 3;
  color: #fff;
  background-color: #337ab7;
  border-color: #337ab7;
  cursor: default;
}
    </style>

    <!-- Data Table CSS- -->

</head>

<body>
    <div class="wholeouter">
        <!-- Header logo section -->


        <?php include("includes/top-header.php");?>

        <div class="divouter mt-4">
        </div>

        <div class="containter-fluid">
            <?php lu_hero('Create Invoice XLS', 'Accounts &rsaquo; Create Invoice XLS', '#', ''); ?>
            <div class="ci-wrap"><div class="ci-card">
            <form action="create_invoice_xls.php" id="validation-form" method="post" autocomplete="off">
                <div class="container d-flex justify-content-center">
                    <div class="col-12 col-sm-12 d-flex justify-content-center flex-column mt-4 monthly1outer">
                        <div class="row mt-4 m-5">
                            <div class="col-12 col-sm-6 col-lg-4">
                                <?php
  $query = "SELECT cic_id, cust_id,stage,division FROM `customer_invoice_config` ORDER BY `cic_id` ASC ";
  $customer_invoice = $db->query($query) or ($db->error);
	?>
                                <label class="mb-2">Customer:</label>
                                <select class="form-select journal_drop" data-placeholder="Select Customer"
                                    name="cust_id" id="cust_id">
                                    <option value="">-Select-</option>
                                    <?php 
						$stage = "";
						while($row = $customer_invoice->fetch_assoc()){
						$query = "SELECT cust_name FROM `adm_customer_master` WHERE id='".$row['cust_id'] ."' ORDER BY `cust_name` ASC ";		
						$customers = $db->query($query) or ($db->error);
						$customers_info = $customers->fetch_assoc();
						
						if($row['division']!="")
						{
							$division="_".$row['division'];
						}
						else
						{
							$division="";
						}
				  ?>
                                    <option value="<?php echo $row['cic_id']; ?>"
                                        <?php if ($_SESSION['fcust_id'] == $row['cic_id']) { echo ' selected="selected"'; $stage=$row['stage'];} ?>
                                        data-stage="<?php echo $row['stage'];  ?>">
                                        <?php echo $customers_info['cust_name']."_".$row['stage'].$division; ?>
                                    </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-4">
                                <label class="mb-2">Invoice:</label>

                                <?php
				$query = "SELECT id,invoice_name FROM `company_details` ORDER BY `id` ASC ";
				$company_details = $db->query($query) or ($db->error);
						
		?>
                                <select id="company_id" name="company_id" class="form-select journal_drop"
                                    data-placeholder="Choose">
                                    <option value="">-Select-</option>
                                    <?php while($row = $company_details->fetch_assoc()){ ?>
                                    <option value="<?php echo $row['id']; ?>"
                                        <?php if ($_SESSION['company_id'] == $row['id']) { echo ' selected="selected"'; } ?>>
                                        <?php echo  $row['invoice_name']; ?></option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="col-12 col-sm-6 col-lg-4">
                                <label class="form-label">Month:</label>

                                <div class="input-group">

                                    <input type="text" id="mfrom_dt" name="mfrom_dt" class="form-control mydatepicker"
                                        value="<?php
                                            if ($_SESSION['mfrom_dt'] != '') {
                                                echo $_SESSION['mfrom_dt'];
                                            } else {
                                                echo date('m-Y');
                                            }
                                            ?>" />
                                    <span class="input-group-addon"> <i class="ace-icon fa fa-calendar bigger-110"></i>
                                    </span>
                                </div>


                            </div>

                            <div class="col-12 col-sm-6 col-lg-4">
                                <label for="inputPassword4" class="form-label" style="padding-top:30px;">&nbsp;</label>
                                <input type="checkbox" class="" name="check_page_count" value="1"
                                    <?php if ($_SESSION['check_page_count'] == '1') { echo 'checked'; }?>>
                                <span class="lbl">Enable Page count</span>
                            </div>
                            
                            <div class="col-12 col-sm-6 col-lg-4">
                                <label class="mb-2">Complexity:</label>

                                
                                <select id="j_complexity" name="j_complexity" class="form-select journal_drop"
                                    data-placeholder="Choose">
                                    <option value="">-Select-</option>
                                    <option <?php if ($_SESSION['j_complexity'] == 1) { echo ' selected="selected"'; } ?> value="1">Regular</option>
                                    <option <?php if ($_SESSION['j_complexity'] == 2) { echo ' selected="selected"'; } ?> value="2">Complex</option>
                                   
                                </select>
                            </div>

                        </div>

                        <div class="meta_nxtbtn container-fluid mt-5 mb-5">
                            <div class="row">
                                <div class="col form_btn cancelbtn d-flex justify-content-end">
                                    <input type="submit" value="Get Result" name="getaction" class="btn btn-info">
                                </div>
                                <div class="col form_btn nxtbtn">
                                    <!-- <input type="submit" value="Confirm Invoice" name="confirm" class="btn btn-danger"> -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- upload section -->
            </form>
            </div></div>

        </div>

        <div class="row">
            <div class="col-xs-12">
                <!-- PAGE CONTENT BEGINS -->

                <!-- div.dataTables_borderWrap -->
                <div>
                    <?php 
			//// Start order by script ////
			if($_REQUEST['sortdatafield']!='')
			{
				$otherParams = "&sortdatafield=".$_REQUEST['sortdatafield']."&sortorder=".$_REQUEST['sortorder'];
			}
			?>
                    <div class="ci-wrap"><div class="ci-card lu-card table-responsive">
                        <div class="row">
                            <div class="col-xs-12 pb-3">
                                <a href="inv_articles_data_xlsx.php" target="_blank" class="btn btn-primary">
                                    <i class="ace-icon fa fa-flask align-top bigger-125"></i>
                                    Export to XLSX
                                </a>
                            </div>
                        </div>
                        <table id="dynamic-table" class="table table-striped custtable table-primary"
                            style="text-wrap: nowrap; white-space: nowrap !important">
                            <thead>
                                <tr>
                                    <td>Customer</td>
                                    <th
                                        class="<?php if($_REQUEST['sortdatafield']=="j.j_code"){ echo ($_REQUEST['sortorder']=="ASC") ? 'sorting_asc' : 'sorting_desc'; } else { echo 'sorting'; } ?>">
                                        <a
                                            href="create_invoice_xls.php?sortdatafield=j.j_code&sortorder=<?php echo ($_REQUEST['sortdatafield']=="j.j_code" && $_REQUEST['sortorder']=="ASC")? 'DESC' : 'ASC';?>">Journal</a>
                                    </th>
                                    <th
                                        class="<?php if($_REQUEST['sortdatafield']=="wd.pub_id"){ echo ($_REQUEST['sortorder']=="ASC") ? 'sorting_asc' : 'sorting_desc'; } else { echo 'sorting'; } ?>">
                                        <a
                                            href="create_invoice_xls.php?sortdatafield=wd.pub_id&sortorder=<?php echo ($_REQUEST['sortdatafield']=="wd.pub_id" && $_REQUEST['sortorder']=="ASC")? 'DESC' : 'ASC';?>">Article
                                    </th>
                                    <th
                                        class="<?php if($_REQUEST['sortdatafield']=="wd.recv_dt"){ echo ($_REQUEST['sortorder']=="ASC") ? 'sorting_asc' : 'sorting_desc'; } else { echo 'sorting'; } ?>">
                                        <a
                                            href="create_invoice_xls.php?sortdatafield=wd.recv_dt&sortorder=<?php echo ($_REQUEST['sortdatafield']=="wd.recv_dt" && $_REQUEST['sortorder']=="ASC")? 'DESC' : 'ASC';?>">
                                            <i class="ace-icon fa fa-clock-o bigger-110 hidden-480"></i> Received
                                            Date
                                        </a>
                                    </th>
                                    <th
                                        class="<?php if($_REQUEST['sortdatafield']=="dh.sent_date"){ echo ($_REQUEST['sortorder']=="ASC") ? 'sorting_asc' : 'sorting_desc'; } else { echo 'sorting'; } ?>">
                                        <a
                                            href="create_invoice_xls.php?sortdatafield=dh.sent_date&sortorder=<?php echo ($_REQUEST['sortdatafield']=="dh.sent_date" && $_REQUEST['sortorder']=="ASC")? 'DESC' : 'ASC';?>">
                                            <i class="ace-icon fa fa-clock-o bigger-110 hidden-480"></i> Sent Date
                                        </a>
                                    </th>
                                    <th></i>Pages Count</a></th>
                                    <th
                                        class="<?php if($_REQUEST['sortdatafield']=="wd.stage"){ echo ($_REQUEST['sortorder']=="ASC") ? 'sorting_asc' : 'sorting_desc'; } else { echo 'sorting'; } ?>">
                                        <a
                                            href="create_invoice_xls.php?sortdatafield=wd.stage&sortorder=<?php echo ($_REQUEST['sortdatafield']=="wd.stage" && $_REQUEST['sortorder']=="ASC")? 'DESC' : 'ASC';?>">Stage</a>
                                    </th>
                                    <th
                                        class="<?php if($_REQUEST['sortdatafield']=="wd.status"){ echo ($_REQUEST['sortorder']=="ASC") ? 'sorting_asc' : 'sorting_desc'; } else { echo 'sorting'; } ?>">
                                        <a
                                            href="create_invoice_xls.php?sortdatafield=wd.status&sortorder=<?php echo ($_REQUEST['sortdatafield']=="wd.status" && $_REQUEST['sortorder']=="ASC")? 'DESC' : 'ASC';?>">Status</a>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php

if($_SESSION['search_qry']!="")
	{
		
		$cnt_query = "";
		$query = "";
		
		if(substr( $stage, 0, 3 ) === "ISS" )
		{
			$cnt_query = "SELECT COUNT(dh.idh_id) as num FROM inw_issue_dispatch_history as dh, `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE dh.a_id = wd.id AND wd.cust_id = c.id and wd.j_id = j.j_id and wd.pub_id NOT LIKE '%toc%' and wd.id NOT IN (SELECT inw_id FROM invoice_items) ";
			
			$query = "SELECT wd.auto_pdf_page_count, c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,dh.sent_date,wd.stage,dh.a_id,idh_id FROM inw_issue_dispatch_history as dh, `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE dh.a_id = wd.id AND wd.cust_id = c.id and wd.j_id = j.j_id and wd.pub_id NOT LIKE '%toc%'  and  wd.id NOT IN (SELECT inw_id FROM invoice_items) ";
			
			$cnt_query = $cnt_query . $_SESSION['search_qry'];
		}
		elseif($_SESSION['published'] == "66")
		
		{
			$cnt_query = "SELECT COUNT(DISTINCT dh.a_id) as num FROM inw_dispatch_history as dh, `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE    dh.a_id = wd.id AND wd.cust_id = c.id and wd.j_id = j.j_id and wd.pub_id NOT LIKE '%toc%' and wd.id NOT IN (SELECT inw_id FROM invoice_items) ";
			$query = "SELECT wd.auto_pdf_page_count, c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,dh.sent_date,wd.stage,dh.a_id,idh_id  FROM inw_dispatch_history as dh, `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE  dh.a_id = wd.id AND wd.cust_id = c.id and wd.j_id = j.j_id and wd.pub_id NOT LIKE '%toc%' and wd.id NOT IN (SELECT inw_id FROM invoice_items) ";
			
			$published="";
			//echo $_SESSION['fcust_id'];
			if($_SESSION['published'] == "66")
			{
			  //$published=" and published='1' ";
			  $published="";
			}
			
			  $cnt_query = $cnt_query. $published . $_SESSION['search_qry'];
		
		}
		else
		{
			$published='';
			$cnt_query = "SELECT COUNT(DISTINCT dh.a_id) as num FROM inw_dispatch_history as dh, `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE  dh.a_id = wd.id AND wd.cust_id = c.id and wd.j_id = j.j_id and wd.pub_id NOT LIKE '%toc%' and wd.id NOT IN (SELECT inw_id FROM invoice_items) ";
			$query = "SELECT wd.auto_pdf_page_count, c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,dh.sent_date,dh.stage,dh.a_id,idh_id  FROM inw_dispatch_history as dh, `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE dh.a_id = wd.id AND wd.cust_id = c.id and wd.j_id = j.j_id and wd.pub_id NOT LIKE '%toc%' and wd.id NOT IN (SELECT inw_id FROM invoice_items) ";	
			$cnt_query = $cnt_query. $_SESSION['search_qry'];
		
		}
		 // echo $cnt_query;
		// echo $cnt_query = $cnt_query . $_SESSION['search_qry']. "  GROUP BY wd.pub_id";
		
		// How many adjacent pages should be shown on each side?
        				
		$customers = $db->query($cnt_query) or ($db->error);
		$page_count = $customers->fetch_assoc();
		 $total_pages = $page_count['num'];	
		
		/* Setup vars for query. */
		/// pagination satart here ///////
		if($_REQUEST['start'])
		{
			$start=$_REQUEST['start'];	
		}
		else
		{
			$start=0;
		}
		
		$filePath = "create_invoice_xls.php?page=1&cust_id=".$_SESSION['published'].""; 	//your file name  (the name of this file)
		$limit = 15; //how many items to show per page
		
		///// Adding sorting parameters to the Grid ////
		$sortfield = $_GET['sortdatafield'];
		$sortorder = $_GET['sortorder'];
		$order_by = "GROUP BY dh.a_id ORDER BY dh.idh_id desc";
		if($sortfield != "")
		{
			$order_by = " GROUP BY dh.a_id ORDER BY ".$sortfield." ".$sortorder;
		}
		
	
		    $query = $query . $published .$_SESSION['search_qry'].$order_by." LIMIT $start, $limit"; 
			 
		$customers = $db->query($query) or ($db->error);				
		while($row = $customers->fetch_assoc()){
			 
		?>

                                <tr class="<?php echo $tr_class; ?>">
                                    <td><?php echo $row['cust_name']; ?></td>
                                    <td><?php echo $row['j_code']; ?></td>
                                    <td class="hidden-480"><?php echo $row['pub_id'];?></td>
                                    <td><?php echo (date('d-m-Y',strtotime($row['recv_dt']))); ?></td>
                                    <td><?php echo (date('d-m-Y',strtotime($row['sent_date']))); ?></td>
                                    <td><?php echo $row['auto_pdf_page_count']; ?></td>
                                    <td class="hidden-480"><?php echo $row['stage']; ?></td>
                                    <td class="hidden-480"><span
                                            class="label label-sm label-success"><?php echo $row['status']; ?></span>
                                    </td>
                                </tr>
                                <?php } ?>
                                <?php  if($total_pages > $limit) { ?>
                                <tr>
                                    <td colspan="4">
                                        <div class="col-xs-12">
                                            <div class="dataTables_info" id="dynamic-table_info" role="status"
                                                aria-live="polite">Showing <?php echo ($start+1); ?> to
                                                <?php echo ($start+$limit > $total_pages) ? $total_pages : $start+$limit; ?>
                                                of <?php echo $total_pages; ?> entries</div>
                                        </div>
                                    </td>
                                    <td align="center" colspan="8" class="inactive">
                                        <div class="dataTables_paginate paging_simple_numbers" id="datatable_paginate">
                                            <ul class="pagination">
                                                <?php paginate($start,$limit,$total_pages,$filePath,$otherParams); ?>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php }} ?>
                            </tbody>
                        </table>
                    </div></div>
                </div>

                <!--- PAGE CONTENT ENDS --->
            </div>
            <!-- /.col -->
        </div>



        <!-- Footer Section -->
        <?php echo footer(); ?>




        <!-- Footer Section -->
        <?php echo scripts(); ?>

        
        <script type="text/javascript">
        $(document).ready(function() {
            $('#cust_id').on('change', function() {
                var j_cust_id = $(this).val();
                if (j_cust_id) {
                    $.ajax({
                        type: 'POST',
                        url: 'get_journals.php',
                        data: 'j_cust_id=' + j_cust_id,
                        success: function(html) {
                            $('#j_id').html(html);
                        }
                    });
                } else {
                    $('#j_id').html('<option value="">Select customer first</option>');

                }
            });


        });

        
        </script>



<style>
  .ui-datepicker.ci-month .ui-datepicker-calendar{display:none;}
  .ui-datepicker.ci-month .ui-datepicker-buttonpane{margin-top:10px !important;}
</style>
<script>
/* month picker (mm-yyyy): pick the month and year, the day grid is hidden */
jQuery(function ($) {
  var $m = $('#mfrom_dt'); if (!$m.length || !$.fn.datepicker) { return; }
  function parse(v) { var p = /^(\d{2})-(\d{4})$/.exec(v || ''); return p ? new Date(+p[2], +p[1] - 1, 1) : new Date(); }
  $m.attr('readonly', 'readonly').datepicker({
    changeMonth: true, changeYear: true, showButtonPanel: true, dateFormat: 'mm-yy', yearRange: '-10:+1',
    beforeShow: function (input, inst) { inst.dpDiv.addClass('ci-month'); $(input).datepicker('option', 'defaultDate', parse(input.value)); },
    onClose: function (dateText, inst) {
      inst.dpDiv.removeClass('ci-month');
      var mo = inst.dpDiv.find('.ui-datepicker-month :selected').val(), yr = inst.dpDiv.find('.ui-datepicker-year :selected').val();
      if (mo !== undefined && yr !== undefined) { $(this).datepicker('setDate', new Date(yr, mo, 1)); }
    }
  });
  $m.on('focus', function () { var d = parse(this.value); $(this).datepicker('setDate', d); });
  $m.closest('.input-group').on('click', function () { $m.datepicker('show'); });
});
</script>
</body>

</html>