<?php include "includes/includes.php";?>
<?php 
if (isset($_POST['pub_id']))
{



	unset($_COOKIE['j_id']);
	unset($_COOKIE['pub_id']);


	$j_id = $_POST['j_id'];
	$_COOKIE['j_id']=$j_id;
	$article_id = $_POST['pub_id'];
	$_COOKIE['pub_id']=$article_id;


$recv_dt=date('Y-m-d', strtotime($_REQUEST['recv_dt']));
$due_dt=date('Y-m-d', strtotime($_REQUEST['due_dt']));
$pub_dt=date('Y-m-d', strtotime($_REQUEST['pub_dt']));
$accpt_dt=date('Y-m-d', strtotime($_REQUEST['accpt_dt']));

	$currentDate = new DateTime(); // Get the current date and time
$cdate=date('Y-m-d');
/// Define an array of days to exclude (1 = Monday, 2 = Tuesday, etc.)
$excludedDays = [2, 3]; // Exclude Tuesday (2) and Wednesday (3)

// Define an array of specific dates to exclude
$excludedDates = [$cdate,'2023-10-23', '2023-11-13'];

// Loop until we find a date that is not in the excluded days or the excluded dates
while (
   // in_array($currentDate->format('N'), $excludedDays) ||
    $currentDate->format('N') == 6 || // Saturday
    $currentDate->format('N') == 7 || // Sunday
    in_array($currentDate->format('Y-m-d'), $excludedDates)
) {
    $currentDate->add(new DateInterval('P2D')); // Add 2 day to the current date
}

$nextDate = $currentDate->format('Y-m-d'); // Format the next date as YYYY-MM-DD
//echo "The next date excluding specified days and dates is: " . $nextDate;
	
//	$due_date=date('Y-m-d', strtotime('+1 days'));
        $due_date=$nextDate;
	

	$journaldetails = $auth->getJournalCustomer($_POST['j_id']);

	
	
	$Cn=$journaldetails[0]['cust_name'];
	$cust_id=$journaldetails[0]['j_cust_id'];
	$jn=$journaldetails[0]['j_code'];
	$article_id=$_POST['pub_id'];




		//// Get the worflow Steps ////

		$getArticleWorkflow = $auth->getArticleWorkflow($_POST['wf_id']);

		$dept_info = explode(',',$getArticleWorkflow[0]['wf_code']);
		//print_r($dept_info);
		/////// Get the department name /////

		$getArticleDepartment = $auth->getArticleDepartment($dept_info[0]);

		
			$status=$getArticleDepartment[0]['dept_name'];
			//echo $status;
			
	
	// $target_dir =$_COOKIE['set_fp_path']."/".$Cn."/".$jn."/".$article_id;  
	 $target_dir ="uploads/".$Cn."/".$jn."/".$article_id."/".$cdate;  

if (!(is_dir($target_dir))) 
	{
		if (!mkdir($target_dir, 0755, true)) 
		{
			die('Failed to create folders...$jn');
		}
	}
	//	echo "123";

	
		$article_count = $auth->countArticle($article_id);
		//echo "ghfghfgh".$article_count[0]['count'];

	if($article_count[0]['count']==1)
	{



		if(isset($_POST['test_article'])){ $test_article='1';}else{$test_article='';}
		if(isset($_POST['highpriority'])){ $highpriority='1';}else{$highpriority='';}

		

			$inser_article = $auth->UpdateArticlesdata($_POST['aid'],$_POST['cust_id'],$_POST['j_id'],$_POST['group_folder_name'],$_POST['pub_id'],$_POST['article_title'],$recv_dt,$due_dt,$test_article,$highpriority,$_POST['manuscript_count'],$_POST['fig'],$_POST['tab'],$_POST['stage'],$_POST['wf_id'],$_POST['inw_type'],$_POST['complexity'],$_POST['corresponding_author'],$_POST['corresponding_mail'],$_POST['location_id'],$_POST['doi'],$pub_dt,$accpt_dt,$_POST['instructions'],$_POST['notes'],$status);


			  $articledetails=$target_dir."/readme.txt";
				
				
			   $article_txt = " [ Article Edited ] Journal name :".$jn."\n Article ID :".$article_id."\n Article Type:".$_POST['inw_type']."\n Uploaded By : ".$_COOKIE['user_email']."\n Updated Datetime:".date('d-m-Y h:i:s a')."\n  Corresponding Author Details \n --------------------------------- \n ";
	   
			   //file_put_contents($file, $article_txt, FILE_APPEND | LOCK_EX);
			   //fwrite($articledetails, $article_txt);
			   file_put_contents($articledetails, $article_txt, FILE_APPEND | LOCK_EX);

			



				



		//echo "can insert";exit;
		
	}
	else
	{
		//echo "Duplicate";exit;
	}
	
   // echo $j_id;
   
   	        //insert log 
			$insert_log=$auth->insertLog($_POST['aid'],$article_id,'1','LINUX','FP','basic information updated','1');
}
?>


