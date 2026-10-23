<?php 
include "includes/includes.php";
include_once 'includes/issue_drafts.php';
include("simplexlsx-master/src/SimpleXLSX.php");

if(isset($_REQUEST['assign_sequence_issue'])) 
{
	
	foreach($_POST as $key => $value) {
		$post[$key] = $value;
		}
		 
$cust_id=$_REQUEST['cust_id'];
$j_id=$_REQUEST['j_id'];
$upload ="uploads/";
if(!file_exists($upload)){
mkdir($upload);
}

$excel_file=$_FILES['excel_file']['name'];

$ext = pathinfo($excel_file, PATHINFO_EXTENSION);

if($ext == "xlsx" || $ext == "XLSX")
{
	if($excel_file!="")
	{
		/// Get the Customer Details ///
		$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = ".$cust_id.";";
		$customers = $db->query($query) or ($db->error);
		$customer_info = $customers->fetch_assoc();
		
		/// Get the journal Details ///
		$query = "SELECT `j_id`,`j_name`,j_code FROM `adm_journals` WHERE j_id = ".$j_id.";";
		$journal = $db->query($query) or ($db->error);
		$journal_info = $journal->fetch_assoc();
		
		$excel_file=$customer_info['cust_name']."_".$journal_info['j_code']."_".$_REQUEST['Volume_and_Issue']."_".date("Y_m_d_H_i_s").'.'.$ext;
		
		move_uploaded_file($_FILES['excel_file']['tmp_name'],$upload.$excel_file);
		
		$inputFileName="uploads/".$excel_file.""; 
		
		$article_details;
		if ( $xlsx = SimpleXLSX::parse($inputFileName)) 
		{
			$i=0;
			$c=0;
			$e=0;
			foreach( $xlsx->rows() as $aid )
			{
				if($aid == "")
				{
					continue;
				}
				
				if($aid['1']!="" && $aid['1']!='Article ID')
				{
					 $article_details[ $aid['0'] ]=trim($aid['1']);
				}
			}			
		}
		/// Get the all aricles  Details available in a given volume issue ///
		 $query = "SELECT *  FROM `inw_inward_dtl` WHERE (`volume_folder` = '".$_REQUEST['Volume_and_Issue']."' AND `cust_id` = '".$_REQUEST['cust_id']."'  AND `j_id` = '".$_REQUEST['j_id']."'  AND `pub_id` NOT LIKE '%-FM%' AND `pub_id` NOT LIKE '%-BM%' AND `pub_id` NOT LIKE '%-Hyperlink%');";
		$varticles = $db->query($query) or ($db->error);
		// $varticles_info = $varticles->fetch_assoc();
		$varticles_count=$varticles->num_rows;
		if($varticles_count >=  sizeof($article_details))
		{
			$ar_error = "";
			while($varticles_info = $varticles->fetch_assoc()){
				if (!in_array($varticles_info['pub_id'], $article_details)) 
				{ 
					$ar_error .= $varticles_info['pub_id'].", "; 
				} 
			}
			//echo	$ar_error;
			//$ar_error = "";
			$varticles = $db->query($query) or ($db->error);
			$inw_volume_issues="";
			while($varticles_info = $varticles->fetch_assoc())
			{
				// echo $varticles_info['pub_id'];
				$key = array_search ($varticles_info['pub_id'], $article_details);
				if(!empty($key))
				{
					$uquery = "UPDATE `inw_inward_dtl` SET `v_issue_sequence_id` = '".$key."',`automation_status` = 'Squence number assigned' WHERE `inw_inward_dtl`.`id` = '".$varticles_info['id']."'";
					$db->query($uquery);
					$inw_volume_issues=$varticles_info['v_issue_id'];
				}
				
			}
			$uquery = "UPDATE `inw_volume_issues` SET `automation_process` ='5', `automation_status` = 'Sequence number updated. waiting for automation approval', automation_run_from='1' WHERE `vi_id` = '".$inw_volume_issues."'";
			$db->query($uquery);
				
				
				
			if($ar_error=="")
			{
				header("location:manageissues?msg= Sequence number added Successfully"); 
				exit();				
				
			}
			else
			{
				$ar_error .= " articles not found in xlsx file please check it";
				header("location:assignsequencenumberstoissue?ermsg=".urlencode(trim($ar_error))); 
				exit();
			}
		}
		else
		{
			header("location:assignsequencenumberstoissue?ermsg=".urlencode("Please check the uploaded xlsx file: the article count or a sequence number does not match.")); 
			exit();
		}
	}
}
else
{
	header("location:assignsequencenumberstoissue?ermsg=".urlencode("Please upload a valid .xlsx file.")); 
	exit();
}
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>	

    <?php include_once 'includes/modern-ui.php'; include_once 'includes/article-filter-ui.php'; mu_head(); af_head(); idr_head(); ?>
    <style>
      .sq-note{display:flex;gap:12px;align-items:flex-start;margin:0 0 14px;padding:12px 14px;border-radius:12px;background:#e8f2fb;border:1px solid #c5daef;color:#17405f;font-size:13px;}
      .sq-note i{margin-top:2px;color:#1b6ca8;font-size:16px;}
      .sq-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px 18px;}
      .sq-grid .af-field{min-width:0;}
      .sq-grid .form-select,.sq-grid .form-control{width:100%;height:38px;border-radius:9px;border:1px solid #d5dde6;font-size:13.5px;box-shadow:none;}
      .sq-grid input[type=file].form-control{height:auto;padding:6px 10px;}
      .sq-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:18px;}
      .sq-req{color:#d64545;}
    </style>
  </head>

  <body>
  <div class="wholeouter">
    <?php include("includes/top-header.php");?>
    <div class="container-fluid" style="justify-content: space-between;">
      <div class="af-page" style="padding-bottom:0;">
        <div class="af-hero">
          <div><small>Issues</small><h1>Assign Sequence Numbers</h1></div>
          <a class="af-add" href="manageissues" style="background:rgba(255,255,255,.16);box-shadow:none;border:1px solid rgba(255,255,255,.35);"><i class="fa fa-arrow-left"></i> Back to Manage Issues</a>
        </div>
      </div>
    </div>

    <?php
    if(!empty($_REQUEST['msg']))  {
      echo '<div class="alert alert-success"><button type="button" class="close" aria-label="Close">&times;</button>'. htmlspecialchars($_REQUEST['msg']) .'</div>';
    } ?>

    <div class="af-page"><div class="af-card">
      <div class="sq-note"><i class="fa fa-info-circle"></i>
        <div>Choose the customer, journal and volume / issue, then upload the <b>.xlsx</b> file that lists the articles and their sequence numbers. The file must match the articles of the issue.</div>
      </div>
      <form action="" method="post" name="add_article" id="add_article" enctype="multipart/form-data">
        <div class="sq-grid">
          <div class="af-field"><label>Customer <span class="sq-req">*</span></label>
            <select class="form-select customer_drop" data-placeholder="Select Customer" name="cust_id" id="cust_id" required>				  
                    
					<?php
		$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` ORDER BY `cust_name` ASC ";
		$customers = $db->query($query) or ($db->error);		
		?>
                                
                                  <option value="">Select Customer</option>
                                  <?php while($row = $customers->fetch_assoc()){ ?>
                                  <option value="<?php echo $row['id']; ?>" ><?php echo $row['cust_name']; ?></option>
                                  <?php } ?>
                </select>
          </div>
          <div class="af-field"><label>Journal <span class="sq-req">*</span></label>
            <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_id" id="j_id" required>
              <option value="">Select Journal</option>
            </select>
          </div>
          <div class="af-field"><label>Volume &amp; Issue <span class="sq-req">*</span></label>
            <select class="form-select journal_drop" data-placeholder="Select Volume and Issue" name="Volume_and_Issue" id="Volume_and_Issue" required>
              <option value="">Select</option>
            </select>
          </div>
          <div class="af-field"><label>Upload Excel <span class="sq-req">*</span></label>
            <input type="file" class="form-control" name="excel_file" id="excel_file" accept=".xlsx" />
          </div>
        </div>
        <div class="sq-actions">
          <a class="af-btn" href="<?php echo $_COOKIE['set_url'];?>/add-article">Cancel</a>
          <button type="submit" id="assign_sequence_issue" value="assign_sequence_issue" name="assign_sequence_issue" class="af-btn primary"><i class="fa fa-check"></i> Assign</button>
        </div>
      </form>
    </div></div>
      <?php echo scripts(); ?>	
      <?php idr_error_script(); idr_error_popup('Cannot assign sequence numbers'); ?>
      <script type="text/javascript">
      $(function () {
        $('#add_article').on('submit', function (e) {
          var f = $('#excel_file').val() || '';
          if (!f) { idrError('Please choose the .xlsx file to upload.', 'File required'); e.preventDefault(); return; }
          if (!/\.xlsx$/i.test(f)) { idrError('Only .xlsx files can be uploaded.', 'Invalid file'); e.preventDefault(); }
        });
      });
      </script>

       <!-- Footer Section -->
       <?php echo footer(); ?>
     
 <!-- Date picker functionality -->
 <script>

      		// Handle changes in the "countries" select element
    $(document).ready(function() {

$("#cust_id").on("change", function() {
         var cust_id=$.trim($(this).val());

         // Fetch states based on the selected client name and populate the "name" select element
         $.ajax({
             url: "get_journal.php",
             dataType: "json",
             data: { cust_id:cust_id },
             success: function(data) {
                 populateSelecte("j_id",data);

             }
         });
     });

     $('#j_id').on('change',function(){
        var j_id = $(this).val();
        if(j_id){
            $.ajax({
                type:'POST',
                url:'get_issue.php',
                data:'j_id='+j_id,
                success:function(html){
                    $('#Volume_and_Issue').html(html);                    
                }
            }); 
        }else{
            $('#Volume_and_Issue').html('<option value="">Select customer first</option>');
            
        }
    });

});
function populateSelecte(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option data-id="+item.j_code+"_0000  data-previous="+item.j_code+"_0000>").text(item.j_code).val(item.j_id));
            });
        }
        function populateSelecte_wf(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.wf_name).val(item.wf_id));
            });
        }
        function populateSelecte_gf(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.folder_name).val(item.folder_name));
            });
        }
        function populateSelecte_jd(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.j_code).val(item.j_id));
            });
        }

    </script>


  </body>
</html>
