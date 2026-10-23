<?php include "includes/includes.php";?>

<?php

	//echo $_COOKIE['rses_inw_id'];
	//echo $_COOKIE['rses_stage'];
if(isset($_REQUEST['id']))
{
	$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$_REQUEST['id']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
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

if(isset($_POST['delete']) && $_POST['delete']=='delete')
{
	foreach($_POST['attach_files'] as $file)
	{
		unlink($file);
	}
	header("location:upload_rfiles.php?id=".$_REQUEST['id']."&issue_id=".$_REQUEST['issue_id']."&msg=Successfully completed"); 
		die();

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

    <!-- Data Table CSS -->

  
   <style>
   
       th  {
  color: #336199 !important;
  background-color: #EDF3F4 !important;
  border-top: 1px solid #F7FBFF !important;
}
 td {
  color: #336199 !important;
  background-color: #EDF3F4 !important;
  border-top: 1px solid #F7FBFF !important;
}
      .tdstyle{
        width: 30%;
      }
      .ftpstyle{
        width: 100%;
      }
      .form-group{
        display: flex;
        width: 90%;
      }
      .form-control{
        width: 50%;
      }
      .form_outer{
        width: 95%;
        padding: 50px 20px;
        display: flex;
        justify-content: center;
        background-color: #fff3ed;

      }
      .form_inner{
        width: 95%;
        background-color: #fff;
        padding: 20px;
      }
     .divouter{
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 40px;
     }
     .radio_btn{
      margin: 30px 0;
      height: 30px;
     }
    </style>
  </head>

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
  


      <!-- Page body starts -->
      
      <div class="container-fluid" style="justify-content: space-between;">
        <div div class="row pt-4 d-flex align-items-center flex-wrap">
          <div class="col-8 col-lg-10">
            <p class="articleheading ms-4">Revise Article</p>
          </div>
        
        </div>
      </div>

            <!-- Table section -->
			<form method="post">
			
			 <input type="hidden" id="cust_id" name="cust_id" value="<?php echo $Cn;?>">
                <input type="hidden" id="j_id" name="j_id" value="<?php echo $J_name;?>">
                <input type="hidden" id="pub_id" name="pub_id" value="<?php echo $app_res['pub_id']; ?>">
                <input type="hidden" id="folder_name" name="folder_name" value="<?php echo $app_res['pub_id']; ?>">
                <input type="hidden" id="volume_folder_name" name="volume_folder_name" value="<?php echo $app_res['volume_folder']; ?>">
				<input type="hidden" id="issueorgroup_folder" name="issueorgroup_folder" value="<?php echo $app_res['issueorgroup_folder']; ?>">

                <input type="hidden" id="stage" name="stage" value="<?php echo $app_res['stage']; ?>">
				
				
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
        <div class="row d-flex flex-column">
        <div class="col-md-12">
                <div class="content_right" >
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
                    <p style="font-size: 1.25em">Uploaded</p>
                    <div class="col-12 fileinfo mt-2">
					
					<?php 
					
							$src = WF_DIR."/downloads/JOURNALS/".$Cn."/".$jn."/".$app_res['volume_folder'].$app_res['issueorgroup_folder']."/".$app_res['pub_id']."/".$stage."/";  // Upload directory

					if(is_dir($src)) {						
    $i = new DirectoryIterator($src);
    foreach($i as $f) {
        if($f->isFile()) 
		{
            $filename = $f->getFilename();
			?>
			
			 <p class=""><?php echo  $filename; ?></p>
              
                <?php
			
			
        } 
		
		} }
		?>
					  
                      <button type="button">
                        <img src="<?php echo $_COOKIE['set_url'];?>/assets/images/trash.svg" />
                      </button>
                    </div>
                    <div class="fileupdtn">
                       <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/underclientreview" style="text-decoration:none;">Cancel</a></button>
                      <button type="button" class="filebtn2"><a href="<?php echo $_COOKIE['set_url'];?>/underclientreview"  >Upload File</a></button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
         
            <div class="col-3 col-md-3 col-lg-3 d-none d-md-block uploadinfodiv">
              <div class="row g-4">
                <div class="col-12 uploadinfobox">
                  <div class="col-12 d-flex align-items-center mt-4 ms-2">
                    <img src="<?php echo $_COOKIE['set_url'];?>/assets/images/icon.svg" />
                    <p class="fileinfopara ms-3">Total Files</p>
                  </div>
                  <div class="fileno">
                    <p id="file_count">0</p>
                  </div>
                </div>
                <div class="col-12 uploadinfobox">
                  <div class="col-12 d-flex align-items-center mt-4 ms-2">
                    <img src="<?php echo $_COOKIE['set_url'];?>/assets/images/icon1.svg" />
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
                    </div>
                </div>
				</form>
        <!-- form section  -->
        
     <!-- Footer Section -->
     <?php echo footer(); ?>




<!-- Footer Section -->
<?php echo scripts(); ?>	

   
     <script type='text/javascript'>
           // var hiddenValue1 =$("#jid").html();
           // var hiddenValue2 =$("#pubid").html();
           // var hiddenValue3 =$("#j_id").val();
           // console.log('hiddenValue:'+hiddenValue3);
        Dropzone.options.dropzonewidget = {


             maxFiles: 20, // Max number of files
            // acceptedFiles: ".pdf",
             type: 'POST',
             url: 'upload-rarticle-data-file.php',
             paramName: 'file',
             
             params: {
              j_id: $("#j_id").val(),  // Additional argument 2
              stage: $("#stage").val(),  // Additional argument 2
              pub_id: $("#pub_id").val(),  // Additional argument 2
              volume_folder_name: $("#volume_folder_name").val(),  // Additional argument 2
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
                    url: 'upload-rarticle-data-file.php',
                    data: {name: name,pub_id:$("#pub_id").val(),stage:$("#stage").val(),request: 2},
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

              

// Add an event listener to the remove button
/* 
document.getElementById('removeButton').addEventListener('click', function () {
    if (myDropzone.files.length > 0) {
        // Remove the first file from the Dropzone instance
        myDropzone.removeFile(myDropzone.files[0]);
    }
});
*/

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

        $('#j_id').change(function() {
	$('#previous').show();
    $('#pub_id').val($('#j_id option:selected').data('id'));
    $('.previous_aid').text($('#j_id option:selected').data('previous'));
    console.log($('#j_id option:selected').data('id'));
    console.log($('#j_id option:selected').data('previous'));
    var jid=$('#j_id').val();
    console.log(jid);
    //$('#j_id').val(jid);
    $('#j_id').attr('value',jid);



	
});

$(".btn-uploadworklfow").on("click", function() {
// Show overlay and loader
     
        
  var count=$("#file_count").text();
//alert(count);

 // alert('ajax initiated');
	if(count>0)
	{
	$("#overlay").show();
    $("#loader").show();
  var customerid = $('#cust_id').val();
  var jid = $('#j_id').val();
  var pubid = $('#pub_id').val();
// Initiate the AJAX request

// Fetch connectedsource based on the selected connectedsource and populate the "name" select element
$.ajax({
    url: "upload_article_to_workflow.php",
    dataType: "json",
    data: { cust_id: customerid, j_id: jid, pub_id: pubid },
    success: function(data) {
    // Hide the loader
       // Hide overlay and loader
       $("#overlay").hide();
       $("#loader").hide();
     // alert('ajax success');
     resetForm();
	},
  complete:function(response){
 


swal({
  title:"Your article is Submitted",
   type: "success",
		showCancelButton: false,
		confirmButtonColor: '#DD6B55',
		confirmButtonText: 'Ok!',
    confirmButtonText: 'CLOSE',
		closeOnConfirm: false,
		closeOnCancel: false
	},
	function(isConfirm){
    if (isConfirm){

      swal("Uploaded!", "Thank You", "success");
     // window.top.location="view_users.php?del="+del;
     window.location.href = '<?php echo $_COOKIE['set_url'];?>/articles';

    } else {
      swal("Uploaded!", "Thank You", "success");
     // window.top.location="view_users.php?del="+del;
     window.location.href = '<?php echo $_COOKIE['set_url'];?>/articles';
    }
	});

}, 
    error: function() {
                // Hide overlay and loader on error
                $("#overlay").hide();
                $("#loader").hide();
                console.log("An error occurred.");
            }
});
}
else
{
   swal({
   title:"Please Upload Doc or any file to Proceed",
 type: "info",  
confirmButtonColor: '#DD6B55',
confirmButtonText: 'CLOSE',
});

 // Hide overlay and loader on error
                $("#overlay").hide();
                $("#loader").hide();
                console.log("An error occurred.");

}

	});

  function resetForm() 
{
	$("#articlesubmission")[0].reset();

}
        </script>
  </body>
</html>
