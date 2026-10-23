<?php include "includes/includes.php";

$a_id = intval($_REQUEST['a_id']);
$query = "SELECT * FROM `inw_inward_dtl` WHERE id='".$a_id."'";
	$fileinfo = $db->query($query) or ($db->error);
	$app_res = $fileinfo->fetch_assoc();

	/// Get the Customer details ///
	$query = "SELECT * FROM `adm_customer_master` WHERE id = ".intval($app_res['cust_id']).";";
	$customers = $db->query($query) or ($db->error);
	$customer_info = $customers->fetch_assoc();


	//// Get the journal details ////
	$query = "SELECT j_code FROM `adm_journals` WHERE j_id = '".$db->real_escape_string($app_res['j_id'])."';";
	$journaldetails = $db->query($query) or ($db->error);
	$journalinfo = $journaldetails->fetch_assoc();

	$file_name = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/".$app_res['status']."/".$app_res['pub_id'].".3d";
	$xml_name = $app_res['work_dir'].$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/".$app_res['status']."/".$app_res['pub_id'].".xml";
	echo $xml_name;

	$type = $_REQUEST['type'];

	if (!defined('PERL_EXE_PATH')) {
		define('PERL_EXE_PATH', 'C:\\Perl64\\bin\\perl.exe');
	}

	$perl = escapeshellarg(PERL_EXE_PATH)." ".escapeshellarg(XML_to_HTML_perl_Path)." "
	      . escapeshellarg($file_name)." "
	      . escapeshellarg($customer_info['cust_name'])." "
	      . escapeshellarg($journalinfo['j_code'])." "
	      . escapeshellarg($type);

	if($app_res['cust_id']==71)
	{

	define('OPF_XML_to_HTML_perl_Path','E:/Transforma/Proofing/XML2HTML_JOURNAL/XML2HTML_JOURNAL_RSSDI.pl');
	}
	else
	{
	define('OPF_XML_to_HTML_perl_Path','E:/Transforma/Proofing/XML2HTML_JOURNAL/XML2HTML_JOURNAL_XMT.pl');
	}

	/// This vairable os declared for only Perl script purpose.
	/// Please don not remove this ///
	/// Added on 29-jan-2022 by Swamy
	$opf_xml_type = 1;

	$opf_perl = escapeshellarg(PERL_EXE_PATH)." ".escapeshellarg(OPF_XML_to_HTML_perl_Path)." "
	          . escapeshellarg($xml_name)." "
	          . escapeshellarg($customer_info['cust_name'])." "
	          . escapeshellarg($journalinfo['j_code'])." "
	          . escapeshellarg($opf_xml_type);

	// Run the perl command, capture its real output/exit code, and only
	// report success when it actually exited 0.
	function run_perl_conversion($cmd, $success_msg, $failure_msg)
	{
		$output = array();
		$return_var = 0;
		exec($cmd." 2>&1", $output, $return_var);
		$log = implode("\n", $output);
		error_log("3dtohtml.php CMD: $cmd\nOUTPUT:\n$log\nEXIT CODE: $return_var");

		if ($return_var === 0)
		{
			echo $success_msg;
		}
		else
		{
			echo $failure_msg." (exit code $return_var)<br><pre>".htmlspecialchars($log)."</pre>";
		}
	}

	if($type == "1")
	{
		if (file_exists($file_name))
		{
			run_perl_conversion($perl, "3d to HTML conversion completed", "3d to HTML conversion FAILED");
		}
		else
		{
			echo "3d file not exists completed";
		}
	}
	else if($type == "2")
	{
		if (file_exists($xml_name))
		{
			run_perl_conversion($perl, "XML to HTML conversion completed", "XML to HTML conversion FAILED");
		}
		else
		{
			echo "XML file not exists";
		}

	}
	else if($type == "3")
	{
		if (file_exists($xml_name))
		{
			run_perl_conversion($opf_perl, "OPF XML to HTML conversion completed", "OPF XML to HTML conversion FAILED");
		}
		else
		{
			echo "OPF XML file not exists";
		}

	}


?>
