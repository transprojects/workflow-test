<?php include "includes/includes.php"; include_once 'includes/issue_drafts.php';?>
    <?php
	
if(isset($_REQUEST['id']))
{
	$result = $db->query("SELECT * FROM inw_volume_issues WHERE vi_id = ".$_REQUEST['id']." ");	
	
	$toatl_count=$result->num_rows;
	$app_res = array();
	if($toatl_count>0)
	{		
		$app_res = $result->fetch_assoc();
		/// Get the Customer Details /////
		
		$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = '".$app_res['cust_id']."' ";
		$customers = $db->query($query) or ($db->error);
		$crow = $customers->fetch_assoc();
		
		
		//// Journal name ///	
		$query = "SELECT j_name,j_code FROM `adm_journals` WHERE j_id = '".$app_res['j_id']."';";
		$steps = $db->query($query) or ($db->error);
		$jninfo = $steps->fetch_assoc();
		
		$Cn= $crow['cust_name'];
		$J_name = $jninfo['j_code']; 	
				
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
    <?php include_once 'includes/modern-ui.php'; include_once 'includes/article-filter-ui.php'; include_once 'includes/issue_drafts.php'; mu_head(); af_head(); idr_head(); ?>
    <style>
      /* Upload pages: same look as the other issue pages; the drop-zone markup and its script are unchanged */
      .up-page .container,.up-page .container-fluid{max-width:none;padding-left:0;padding-right:0;}
      .up-page .separatetitle:after,.up-page .separatetitle:before{display:none !important;}
      .up-page .row{margin-left:0;margin-right:0;}
      .up-page .content_right>.row{display:flex;flex-wrap:wrap;gap:18px;align-items:flex-start;justify-content:flex-start !important;}
      .up-page .dropboxdiv{flex:1 1 520px;width:auto !important;max-width:none !important;padding:0 !important;}
      .up-page .dropboxdiv{box-shadow:none !important;background:transparent !important;height:auto !important;min-height:0 !important;border:0 !important;}
      .up-page .dropboxdiv>.row{height:auto !important;margin:0;}
      .up-page .dropboxdiv .col-10{width:100% !important;max-width:none !important;flex:1 1 100% !important;padding:0 !important;}
      .up-page .dropboxinner{padding:0 !important;background:transparent !important;border:0 !important;box-shadow:none !important;}
      .up-page .dropzone{min-height:190px;border:2px dashed #b9d0e6 !important;border-radius:14px;background:#f6f9fc !important;padding:18px;transition:border-color .2s,background .2s;}
      .up-page .dropzone:hover,.up-page .dropzone.dz-drag-hover{border-color:#e8742a !important;background:#fff8f2 !important;}
      .up-page .uploadinfodiv{flex:0 0 230px;width:230px !important;max-width:230px !important;padding:0 !important;display:block !important;}
      .up-page .uploadinfodiv .row{margin:0;gap:12px;}
      .up-page .uploadinfobox{border:1px solid #e4e9ef !important;border-radius:14px !important;background:#f9fbfd !important;box-shadow:none !important;padding:0 0 10px !important;width:100% !important;}
      .up-page .uploadinfobox .fileno p{margin:0;font-size:26px;font-weight:700;color:#14304a;text-align:center;}
      .up-page .uploadinfobox .fileinfopara{margin:0;font-weight:600;color:#6b7785;}
      .up-page .droppedfile{padding:0 !important;margin-top:16px !important;}
      .up-page .checkbox label{display:flex;align-items:center;gap:8px;font-weight:600;color:#14304a;}
      .up-page .hr{border-top:1px dashed #e4e9ef;margin:12px 0;}
      .up-link{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:10px;font-weight:700;text-decoration:none !important;border:1px solid #c5daef;background:#e8f1fb;color:#1b6ca8 !important;}
      .up-link:hover{background:#1b6ca8;color:#fff !important;}
      .up-link.green{border-color:#bfe8d2;background:#e3f6ec;color:#1e9e61 !important;}
      .up-link.green:hover{background:#1e9e61;color:#fff !important;}
      .up-page .fileupdtn{margin-top:14px;}
      .up-page .btn-next-cancel{text-decoration:none !important;}
      .up-page .filebtn2{height:38px;padding:0 20px;border-radius:9px;border:1px solid #e4e9ef;background:#fff;color:#14304a;font-weight:600;font-size:13.5px;box-shadow:none;}
      .up-page .filebtn2:hover{background:#eef5fb;}
      .up-info{display:flex;flex-wrap:wrap;gap:10px;align-items:stretch;}
      .up-info>div{padding:8px 14px;border:1px solid #e4e9ef;border-radius:10px;background:#f9fbfd;min-width:140px;}
      .up-info small{display:block;font-size:10.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#6b7785;}
      .up-info b{color:#14304a;font-size:14px;}
      .up-h{display:flex;align-items:center;gap:8px;margin:0 0 4px;font-size:13px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#14304a;}
      .up-h i{color:#e8742a;}
      .up-note{color:#6b7785;margin:0 0 12px;}
    </style>
    
    <!-- Data Table CSS --->
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
    </head>
    
    <body>
    <div id="loader" class="loader-container">
      <div class="loader"></div>
    </div>
    <div id="overlay" class="overlay"></div>
    <div class="wholeouter">
      <?php include("includes/top-header.php");?>

      <div class="container-fluid" style="justify-content: space-between;">
        <div class="af-page" style="padding-bottom:0;">
          <div class="af-hero">
            <div><small>Upload to the issue &middot; <?php echo htmlspecialchars($Cn); ?> &middot; <?php echo htmlspecialchars($J_name); ?></small><h1>Vol<?php echo htmlspecialchars($app_res['volume_number'].$app_res['issue_number']); ?></h1></div>
            <a class="af-add" href="<?php echo $_COOKIE['set_url'];?>/manageissues" style="background:rgba(255,255,255,.16);box-shadow:none;border:1px solid rgba(255,255,255,.35);"><i class="fa fa-arrow-left"></i> Back to Manage Issues</a>
          </div>
        </div>
      </div>

      <div class="af-page"><div class="af-wrap"><div class="formouter">
        <div class="up-info">
          <div><small>Customer</small><b><?php echo htmlspecialchars($Cn); ?></b></div>
          <div><small>Journal</small><b><?php echo htmlspecialchars($J_name); ?></b></div>
          <div><small>Volume / issue</small><b>Vol<?php echo htmlspecialchars($app_res['volume_number'].$app_res['issue_number']); ?></b></div>
          <div><small>Stage</small><b><?php echo htmlspecialchars($app_res['stage']); ?></b></div>
        </div>
      </div></div></div>
      <div class="af-page up-page"><div class="af-card">
          <form action="" method="post" name="add_article" id="add_article" enctype="multipart/form-data">
          <div class="container mt-4 separatetitle">
          <h3 class="up-h"><i class="fa fa-cloud-upload"></i> Upload files</h3>
          <p class="up-note">Drop the files for this issue below. They are saved in the issue&rsquo;s misc folder for the current stage.</p>
          <div class="row mt-1 g-4 addspacing">
          <input type="hidden" name="j_id" id="j_id" value="<?php echo $app_res['j_id'];?>">
          <input type="hidden" name="cust_id" id="cust_id" value="<?php echo $app_res['cust_id'];?>">
          <input type="hidden" name="vi_id" id="vi_id" value="<?php echo $app_res['vi_id'];?>">
          <input type="hidden" name="stage" id="stage" value="<?php echo $app_res['stage'];?>">
		   
          
          <!-- upload section -->
          <div class="content_right">
          <div class="row d-flex justify-content-center">
          <div class="col-10 col-md-8 dropboxdiv">
          <div
                class="row justify-content-center align-items-center d-flex flex-column"
                style="height: 100%"
              >
          <div class="col-10">
          <div id="dropzone" class="col-12 dropboxinner">
            <div id="dropzonewidget" class="dropzone"></div>
          </div>
          <div class="droppedfile mt-4">
          <form class="form-horizontal" id="validation-form" method="post">
            <div class="space-2"></div>
            <div class="form-group">
              <?php
			  $volume_folder = 'Vol'.$app_res['volume_number'].$app_res['issue_number'];
			  $src = WF_DIR."/downloads/JOURNALS/".$Cn."/".$J_name."/".$volume_folder."/".$app_res['stage']."/misc/";  // Upload directory	
								
		if(is_dir($src)) {						
    $i = new DirectoryIterator($src);
    foreach($i as $f) {
        if($f->isFile()) 
		{
            $filename = $f->getFilename();
			?>
              <div class="checkbox col-xs-12 col-sm-9">
                <label>
                  <input name="attach_files[]" class="ace ace-checkbox-2" value="<?php echo  $src.$filename; ?>" type="checkbox" />
                  <span class="lbl"> <?php echo  $filename; ?> </span>                  
                </label>
              </div>
              <?php
			
			
        } 
		
		} }
								?>
            </div>
            <div class="space-2"></div>
            <div class="hr hr-dotted"></div>
            <div class="form-group">
              <div class="col-xs-12 col-sm-9 center">
                <input type="hidden" name="action" value="update">
				 <input type="hidden" name="work_dir" id="work_dir" value="<?php echo $src;?>">		
                <input type="hidden" name="id" value="<?php echo $_REQUEST['id']; ?>" />
                <a class="up-link" href="<?php echo str_replace(WF_DIR,TOOL_DIR,$src); ?>" target="_blank"><i class="ace-icon fa fa-folder align-top bigger-125 pt-1 pe-2"></i> View Source Dir</a> &nbsp;
                
              </div>
            </div>
          </form>
          <div class="fileupdtn "> <a href="<?php echo $_COOKIE['set_url'];?>/manageissues" style="text-decoration:none;" class="btn-next-cancel ">
            <button type="button" class="filebtn2 ">Cancel</button>
            </a> </div>
        </div>
      </div>
    </div>
    </div>
    <div class="col-3 col-md-3 col-lg-3 d-none d-md-block uploadinfodiv">
      <div class="row g-4">
        <div class="col-12 uploadinfobox">
          <div class="col-12 d-flex align-items-center mt-4 ms-2"> <img src="<?php echo $_COOKIE['set_url'];?>/assets/images/icon.svg" />
            <p class="fileinfopara ms-3">Total Files</p>
          </div>
          <div class="fileno">
            <p id="file_count">0</p>
          </div>
        </div>
        <div class="col-12 uploadinfobox">
          <div class="col-12 d-flex align-items-center mt-4 ms-2"> <img src="<?php echo $_COOKIE['set_url'];?>/assets/images/icon1.svg" />
            <p class="fileinfopara ms-3">Total Size</p>
          </div>
          <div class="fileno">
            <p style="color: #ff6d02" id="totalFileSize">0 KB</p>
          </div>
        </div>
      </div>
    </div>
    </div>
    </div>
    </div>
    </div>
    </form>
    </div>
    </div>
    <?php echo scripts(); ?> 
    <?php idr_error_script(); idr_error_popup('Upload failed'); ?>
    
    <!-- Footer Section --> 
    <?php echo footer(); ?>
    </div>
    <!-- Date picker functionality --> 
    
    <script type='text/javascript'>
           // var hiddenValue1 =$("#jid").html();
           // var hiddenValue2 =$("#pubid").html();
           // var hiddenValue3 =$("#j_id").val();
           // console.log('hiddenValue:'+hiddenValue3);
        Dropzone.options.dropzonewidget = {


             maxFiles: 20, // Max number of files
            // acceptedFiles: ".pdf",
             type: 'POST',
             url: '<?php echo $_COOKIE['set_url'];?>/issue_upload_process_files.php',
             paramName: 'file',
             
            
            params: {
              customer_id: $("#cust_id").val(),  // Additional argument 2
              j_id: $("#j_id").val(),
              vi_id: $("#vi_id").val(),
              stage: $("#stage").val(),
              
              request:1 // Additional argument 4
                // Add more arguments as needed
            },
            init: function () {

              var myDropzone = this;

                 var totalSize = 0;
                var totalSizeElement = document.getElementById('totalFileSize');

                this.on("addedfile", function (file) {

                  totalSize += file.size;
                  updateTotalSize();

                    var removeButton = Dropzone.createElement('<button class="btn-remove" style="background-color:red;border-radius:15px;cursor:pointer;">Remove</button>');
                    var _this = this;

                    // Event listener for the "Remove" button click
                    removeButton.addEventListener("click", function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        _this.removeFile(file);


                        var name = file.name;    
                
                $.ajax({
                    type: 'POST',
                    url: '<?php echo $_COOKIE['set_url'];?>/issue_upload_process_files.php',
                    data: {name: name, customer_id: $("#cust_id").val(), j_id: $("#j_id").val(),vi_id: $("#vi_id").val(),stage: $("#stage").val(),request: 2},
                    sucess: function(data){
                        console.log('success: ' + data);
                    }
                });
                var _ref;
                return (_ref = file.previewElement) != null ? _ref.parentNode.removeChild(file.previewElement) : void 0;
              //  updateTotalSize();

                    });

                    file.previewElement.appendChild(removeButton);

                    updateUploadCount();
                    updateTotalSize();


                });

                this.on("removedfile", function (file) {

                    totalSize -= file.size;
                    updateUploadCount();
                    updateTotalSize();

                });

                this.on("totaluploadprogress", function (progress) {
                    // Use progress.loaded to track the total uploaded size
                });

                this.on("success", function (file, response) {
                    console.log(response);
                });
                
                 this.on("queuecomplete", function () {
                // All files have been processed, display an alert
               // alert("All files have been uploaded.");
                  $(".btn-save").show();
            });

                function updateTotalSize() {
                    totalSizeElement.textContent = formatBytes(totalSize);
                }

                // Function to format bytes into human-readable format
                function formatBytes(bytes) {
                    var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
                    if (bytes === 0) return '0 Byte';
                    var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
                    return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
                }

              
            }
        };

       


        function updateUploadCount() {
            var dropzoneInstance = Dropzone.forElement("#dropzonewidget");
            var uploadedFilesCount = dropzoneInstance.files.length;
            var countElement = document.getElementById("file_count");

            if (uploadedFilesCount === 0) {
                countElement.textContent = "0";
                	$(".btn-save").hide();
            } else if (uploadedFilesCount === 1) {
                countElement.textContent = "1";
                //	$(".btn-save").show();
            } else {
                countElement.textContent = uploadedFilesCount;
                //$(".btn-save").show();

            }
        }

        </script>
    </body>
    </html>