<?php include_once("includes/includes-sw2.php");?>
<?php
$pbkp_python_file = "C:/LAMP/Apache/htdocs/workflow-new/python-scripts/compare.py";
//$dest_pbkp_dept_file = $_POST['work_dir'].$post['volume_folder']."/".$post['pub_id']."/".$post['stage']."/".$post['dept_name']."/"; // destination folder or file 
 $python_tf_article_tme ='C:\\Windows\\py.exe "'.$pbkp_python_file.'" '.$app_res['id'].'';
 system($python_tf_article_tme);
?>
<link rel="stylesheet" href="<?php echo $_COOKIE['set_url'];?>/assets/css/richtexts.min.css" />
<script src="<?php echo $_COOKIE['set_url'];?>/assets/js/jquery.richtexts.min.js"></script>
<div class="container mt-4">
<p style="text-align: center; font-weight: bold;">Updated XML Data</p>
    <?php
	$updated_work_dir = str_replace("E:/New_Workflow_Files/JOURNAL/production/", "E:/New_Workflow_Files/JOURNAL/downloads/", $app_res['work_dir']);

	$fm_html_file_path = $updated_work_dir . $app_res['volume_folder'] . "/" . $app_res['pub_id'] . "/" . $app_res['stage'] . "/" . $pdept_res['dept_name'] . "/compare.xml";

 //   $fm_html_file_path = $app_res['work_dir'] . $app_res['volume_folder'] . "/" . $app_res['pub_id'] . "/" . $app_res['stage'] . "/" . $pdept_res['dept_name'] . "/compare.xml";

    $file_content = ''; // Initialize variable to store the file content

    // Check if the file exists
    if (file_exists($fm_html_file_path)) {
        // Read the content of the file
        $file_content = file_get_contents($fm_html_file_path);
    } else {
        // Optionally handle the case where the file doesn't exist
        $file_content = "<p>The specified HTML file does not exist.</p>";
    }
?>
    <div class="row">
        <textarea name="tme_message" id="tme_message" class="form-control" rows="15" placeholder="Comments"
            style=" min-height: 100px;"><?php echo $file_content; ?></textarea>
    </div>
</div>
<script>
$('#tme_message').richText();
</script>