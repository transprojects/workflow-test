<?php
set_time_limit(0);

require_once "includes/conn.php";

/*---------------------------------------------------
    FILE DETAILS
---------------------------------------------------*/
$filename = $app_res['work_dir'] .
            $app_res['volume_folder'] . "/" .
            $app_res['issueorgroup_folder'] . "/" .
            $app_res['pub_id'] . "/" .
            $stage . "/DELIVERY-ELX/" .
            $app_res['pub_id'] . ".pdf";

$name = $app_res['pub_id'] . ".pdf";

$plain_stage = preg_replace('/[^a-zA-Z]/', '', $stage);


/*---------------------------------------------------
    JOURNAL DETAILS
---------------------------------------------------*/
$select_jornal_query = "
    SELECT *
    FROM adm_journals
    WHERE j_id = '" . mysqli_real_escape_string($db, $app_res['j_id']) . "'
";

$select_jounal_run = $db->query($select_jornal_query) or die($db->error);
$select_journal_row = $select_jounal_run->fetch_assoc();


/*---------------------------------------------------
    MAIL TEMPLATE DETAILS
---------------------------------------------------*/
$query = "
    SELECT *
    FROM inw_dispatch_mail_master
    WHERE j_id = '" . mysqli_real_escape_string($db, $app_res['j_id']) . "'
    AND cust_id = '" . mysqli_real_escape_string($db, $app_res['cust_id']) . "'
    AND stage = '" . mysqli_real_escape_string($db, $plain_stage) . "'
";

$d_mail = $db->query($query) or die($db->error);
$d_mail_info = $d_mail->fetch_assoc();

$body    = $d_mail_info['message'];
$Subject = $d_mail_info['subject'];

$articleTitle = !empty($app_res['article_title'])
    ? $app_res['article_title']
    : $_POST['pub_id'];


/*---------------------------------------------------
    REPLACE MAIL TAGS
---------------------------------------------------*/
$body = str_replace('##AuthorNames##', "Team", $body);
$body = str_replace('##FileName##', $_POST['pub_id'], $body);
$body = str_replace('##ARTICLEID##', $_POST['pub_id'], $body);
$body = str_replace('##ARTICLETITLE##', $articleTitle, $body);

$Subject = str_replace('##FileName##', $_POST['pub_id'], $Subject);


/*---------------------------------------------------
    CUSTOMER DETAILS
---------------------------------------------------*/
$query = "
    SELECT cust_name, cust_id, id
    FROM adm_customer_master
    WHERE id = '" . mysqli_real_escape_string($db, $app_res['cust_id']) . "'
";

$customers = $db->query($query) or die($db->error);
$customer_info = $customers->fetch_assoc();


/*---------------------------------------------------
    INSERT DISPATCH HISTORY
---------------------------------------------------*/
$dispat_qry = "
    INSERT INTO inw_dispatch_history
    (
        cust_id,
        j_id,
        a_id,
        from_mail,
        to_mail,
        to_cc,
        to_bcc,
        mail_subject,
        mail_text,
        sent_by,
        stage,
        sent_date,
        dispatch_type
    )
    VALUES
    (
        '" . mysqli_real_escape_string($db, $app_res['cust_id']) . "',
        '" . mysqli_real_escape_string($db, $app_res['j_id']) . "',
        '" . mysqli_real_escape_string($db, $app_res['id']) . "',
        '" . mysqli_real_escape_string($db, $mail_from) . "',
        '" . mysqli_real_escape_string($db, $select_journal_row['j_to_email']) . "',
        '" . mysqli_real_escape_string($db, $select_journal_row['j_cc_email']) . "',
        '" . mysqli_real_escape_string($db, $select_journal_row['j_bcc_email']) . "',
        '" . mysqli_real_escape_string($db, $Subject) . "',
        '" . mysqli_real_escape_string($db, $body) . "',
        '" . mysqli_real_escape_string($db, $_COOKIE['user_id']) . "',
        '" . mysqli_real_escape_string($db, $stage) . "',
        NOW(),
        'FTP upload and mail'
    )
";

$db->query($dispat_qry);


/*---------------------------------------------------
    CALL PYTHON SCRIPT
---------------------------------------------------*/
$ftp_python_file = "C:/LAMP/Apache/htdocs/workflow-new/python_ftp_upload/ftp_upload_insert_table_edp.py";

$python_article_delivery =
    'C:\\Windows\\py.exe "' .
    $ftp_python_file . '" "' .
    $app_res['id'] . '"';

system($python_article_delivery);


/*---------------------------------------------------
    CHECK FILE EXISTS
---------------------------------------------------*/
if (file_exists($filename)) {

    // FILE EXISTS -> MOVE TO CLIENT REVIEW
    $user_working_status =
        '<span class="label label-sm label-success">Client Review</span>';

    $prj_update_query = "
        UPDATE inw_inward_dtl
        SET
            status = 'Client Review',
            department_id = '0',
            user_working_status = '" . mysqli_real_escape_string($db, $user_working_status) . "'
        WHERE id = '" . mysqli_real_escape_string($db, $app_res['id']) . "'
    ";

    $db->query($prj_update_query);

} else {

    // FILE MISSING -> REVERT TO PREVIOUS DEPARTMENT
    $current_user_deptname = mysqli_fetch_array(
        mysqli_query(
            $db,
            "SELECT dept_name
             FROM adm_dept_master
             WHERE dept_code = '" . mysqli_real_escape_string($db, $post['department_id']) . "'"
        )
    );

    $user_working_status =
        '<span class="label label-sm label-success">' .
        $current_user_deptname['dept_name'] .
        '</span>';

    $prj_update_query = "
        UPDATE inw_inward_dtl
        SET
            status = '" . mysqli_real_escape_string($db, $current_user_deptname['dept_name']) . "',
            department_id = '" . mysqli_real_escape_string($db, $post['department_id']) . "',
            user_working_status = '" . mysqli_real_escape_string($db, $user_working_status) . "'
        WHERE id = '" . mysqli_real_escape_string($db, $app_res['id']) . "'
    ";

    $db->query($prj_update_query);

    header(
        "Location:" .
        $_COOKIE['set_url'] .
        "/startwork/" .
        $app_res['id'] .
        "/new&msg=Source file path is missing please check and try again"
    );

    exit;
}
?>