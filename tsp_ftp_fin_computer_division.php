<?php 
$current_stage = preg_replace('/[0-9]+/', '', $_POST['stage']);

$host = $_POST['FtpHost'];

$user = $_POST['FtpUser'];

$pass = $_POST['FtpPass'];

//$destDir = $_POST['FtpPath'];
// As per the requirment all Finals is uploading in reguler division///
$destDir = "/From-Transforma/Regular Division";
					
					
					
       

// open connection

        $conn = ftp_connect($host) or die("Cannot initiate connection to host");

// send access parameters

        ftp_login($conn, $user, $pass) or die("Cannot login");

		// Switched to passive mode after logging in
			ftp_pasv($conn, true);




		
		
		
///// Create Month and Year DIR ///

        $destDir .= "/".date('Y') . "/";

// try to create directory $dir
if (ftp_nlist($conn, $destDir) == false) {
        if (ftp_mkdir($conn, $destDir)) {
            echo "Successfully created $destDir";
        } else {
            echo "Error while creating $destDir";
        }
}
		
		
///// Create Final Version DIR ///

        $destDir .= "Final Version/";

// try to create directory $dir
if (ftp_nlist($conn, $destDir) == false) {
        if (ftp_mkdir($conn, $destDir)) {
            echo "Successfully created $destDir";
        } else {
            echo "Error while creating $destDir";
        }
}



///// Create Month  DIR ///

        //$destDir .= date('F'). "/";

// try to create directory $dir
/*if (ftp_nlist($conn, $destDir) == false) {
        if (ftp_mkdir($conn, $destDir)) {
            echo "Successfully created $destDir";
        } else {
            echo "Error while creating $destDir";
        }
}*/
////// END Create DIR ////


///// Create Month and DATE DIR ///

///// Add records in Artile Dispatch history ///
			$tsp_sent_date = date('Y-m-d');
			$ftp_dr_md = date('md');
			
			$get_sent_resutl = $db->query("SELECT id FROM tsp_mail_send_history WHERE type = 'COMP' AND sent_date = '".date('Y-m-d')."';");
			$today_sent_cnt=$get_sent_resutl->num_rows;			
			if($today_sent_cnt>0)
			{
				$tsp_sent_date = date("Y-m-d", strtotime("+1 day")); //Get tomorrows date from date function
				$ftp_dr_md = date("md", strtotime("+1 day")); //Get tomorrows date from date function
			}
			

        $destDir .= $ftp_dr_md;

// try to create directory $dir
if (ftp_nlist($conn, $destDir) == false) {
        if (ftp_mkdir($conn, $destDir)) {
            echo "Successfully created $destDir";
        } else {
            echo "Error while creating $destDir";
        }
}
////// END Create DIR ////
       // $email_ftp_path = '';

			// perform file upload


			//$filename =$post['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$_POST['stage']."/Delivery/TSP_".$post['j_code']."_".$post['pub_id'].".zip";
			$filename =$post['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$_POST['stage']."/Delivery/".$post['pub_id'].".zip";
			$name=$post['pub_id'].".zip";

			$upload = ftp_put($conn, $destDir . "/$name", $filename, FTP_BINARY);

            //$email_ftp_path .= "ftp://" . $user . "@" . $host . $destDir . "/" . $_FILES['fileAttachment']['name'] . "<br>";
            

      

//var_dump($upload);
// check upload status
// display message

        if (!$upload) {

            echo "Cannot upload to FTP";
        } else {

            echo "Upload complete";
        }

//exit;
// close the FTP stream

        ftp_close($conn);
		
		if(file_exists($filename))
	   {
		
			$set_status = "`status` ='Client Review',";
			if ($_POST['stage'] == 'FIN') {
			$set_status = "`status` ='Client Review',";
			}
			
			$prj_update_query = "UPDATE inw_inward_dtl SET " . $set_status . " `department_id` ='0',`assign_department_id` ='0' WHERE id = " . $post['id'] . "";
			
			//echo $prj_update_query; exit;
			$db->query($prj_update_query);
			
			
			
			$dispat_qry = "INSERT INTO `inw_dispatch_history` (`cust_id`, `j_id`, `a_id`, `from_mail`, `to_mail`, `mail_text`, `sent_by`, `stage`, `sent_date`,dispatch_type,query_comments,tsp_sent_date) VALUES ('" . $post['cust_id'] . "', '" . $post['j_id'] . "', '" . $post['id'] . "', '" . $_POST['txtFrom'] . "', '" . $_POST['txtTo'] . "', '" . $body. "', '" . $_COOKIE['user_id'] . "', '" . $_POST['stage'] . "', NOW(),'Only FTP &amp; manual mail','" . $_POST['comments'] . "','".$tsp_sent_date."')";
			$db->query($dispat_qry);
	   }
				
		?>