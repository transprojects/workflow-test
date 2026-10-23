<?php
include "includes/includes.php";
include "includes/conn.php";
include 'email-functions.php';

$response = ['title'=>'','message'=>'','icon'=>'error'];

$token = $_POST['token'] ?? '';
$action = $_POST['action'] ?? '';
	$email_settings=mysqli_fetch_array(mysqli_query($db,"select * from email_settings where id=2"));

if($token && $action){
    $qry = mysqli_query($db, "SELECT * FROM journal_spec_history WHERE approve_token='".mysqli_real_escape_string($db,$token)."' LIMIT 1");
    if(mysqli_num_rows($qry)){
        $row = mysqli_fetch_assoc($qry);
        $history_id = $row['history_id'];
        $detail_id = $row['journal_spec_details_id'];

        // Fetch user email
        $user_qry = mysqli_query($db, "SELECT user_email, full_name FROM users WHERE id='".mysqli_real_escape_string($db,$row['edited_by'])."' LIMIT 1");
        $user_info = mysqli_fetch_assoc($user_qry);
        $sendto = $user_info['user_email'];
        $username = $user_info['full_name'];

        if($action==='approve'){
            mysqli_query($db, "UPDATE journal_spec_history SET status='Inactive' WHERE journal_spec_details_id=$detail_id");
            mysqli_query($db, "UPDATE journal_spec_history SET status='Active', approve_token=NULL WHERE history_id=$history_id");
            mysqli_query($db, "UPDATE journal_spec_details 
                SET description='".mysqli_real_escape_string($db,$row['description'])."',
                    image_path='".($row['image_path'] ?? NULL)."',
                    version=".$row['version'].",
                    updated_at=NOW() 
                WHERE id=$detail_id");

            // Send email
            $subject = "Journal Version Approved";
            $body = "Hi $username,<br><br>Your submitted journal version {$row['version']} has been <b>approved</b> by admin.<br><br>Thanks,<br>Workflow Team";
            sendMail(
                $email_settings['host'],
                $email_settings['port'],
                $email_settings['secure'],
                $email_settings['from_email'],
                $email_settings['from_password'],
                $email_settings['from_email'],
                $email_settings['from_name'],
                $sendto,
                $email_settings['mail_cc'],
                $email_settings['mail_bcc'],
                $subject,
                $body
            );

            $response = ['title'=>'Approved','message'=>"Version {$row['version']} approved successfully!",'icon'=>'success'];
        }

        if($action==='reject'){
            mysqli_query($db, "UPDATE journal_spec_history SET status='Rejected', approve_token=NULL WHERE history_id=$history_id");

            // Send email
            $subject = "Journal Version Rejected";
            $body = "Hi $username,<br><br>Your submitted journal version {$row['version']} has been <b>rejected</b> by admin.<br><br>Thanks,<br>Workflow Team";
            sendMail(
                $email_settings['host'],
                $email_settings['port'],
                $email_settings['secure'],
                $email_settings['from_email'],
                $email_settings['from_password'],
                $email_settings['from_email'],
                $email_settings['from_name'],
                $sendto,
                $email_settings['mail_cc'],
                $email_settings['mail_bcc'],
                $subject,
                $body
            );

            $response = ['title'=>'Rejected','message'=>"Version {$row['version']} rejected successfully!",'icon'=>'error'];
        }
    } else {
        $response = ['title'=>'Error','message'=>'Invalid or expired token!','icon'=>'error'];
    }
}

echo json_encode($response);
