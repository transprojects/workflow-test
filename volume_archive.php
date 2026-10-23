<?php
set_time_limit(0); 
include ('includes/includes.php');
include_once 'includes/issue_drafts.php';
/* error_reporting(E_ALL);
ini_set('display_errors', 1); */

include "get/json-volume-archive.php";

if (isset($_POST['submit'])) {
	// session_start();
	$_COOKIE['cust_id']=$_POST['v_cust_id'];
$_COOKIE['j_id']=$_POST['v_j_id'];
  $_COOKIE['v_folder'] = $_POST['v_folder']; 
 $_COOKIE['vi_id'] = $_POST['vi_id']; 
 
 
	$v_j_id = $_POST['v_j_id'];
	setcookie("v_j_id", $v_j_id, $cookie_expiration_time);
	
	$v_cust_id = $_POST['v_cust_id'];
	setcookie("v_cust_id", $v_cust_id, $cookie_expiration_time);
	
	
	$v_folder = $_POST['v_folder'];
	setcookie("v_folder", $v_folder, $cookie_expiration_time);
	
	$vi_id = $_POST['vi_id'];
	setcookie("vi_id", $vi_id, $cookie_expiration_time);


header("location:volume_call.php");
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
      .va-bar .form-select{width:100% !important;height:38px;border-radius:9px;border:1px solid #d5dde6;font-size:13.5px;box-shadow:none;}
      .va-bar .af-field{min-width:230px;}
      .va-note{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;border-radius:10px;background:#fff6e5;border:1px solid #f1d9a6;color:#5a3d06;font-weight:600;font-size:13px;}
      .va-step{display:flex;align-items:center;gap:8px;margin:0 0 10px;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#6b7785;}
      .va-step b{display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;background:#e8742a;color:#fff;font-size:11px;}
      .va-top{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;margin-bottom:6px;}
    </style>

    <!-- Data Table CSS- -->
    
    </head>
    
    <body>
    <div class="wholeouter">
    <!-- Header logo section -->
    
    <?php include("includes/top-header.php");?>
    <div class="container-fluid" style="justify-content: space-between;">
      <div class="af-page" style="padding-bottom:0;">
        <div class="af-hero">
          <div><small>Archive</small><h1>Volume Archive</h1></div>
        </div>
      </div>
    </div>

    <?php
    if(isset($_COOKIE["user_message"]))
    {
      echo '<div class="alert alert-info"><button type="button" class="close" aria-label="Close">&times;</button>No data is available</div>';
      unset($_COOKIE["user_message"]);
    }
    if(!empty($_REQUEST['msg']))  {
      echo '<div class="alert alert-success"><button type="button" class="close" aria-label="Close">&times;</button>'. htmlspecialchars($_REQUEST['msg']) .'</div>';
    } ?>

    <?php if($_COOKIE['user_level']==1 || $_COOKIE['department_id'] == 16) { ?>
    <div class="af-page"><div class="af-wrap">
      <form action="volume_archive.php" enctype="multipart/form-data" id="validation-form" method="post" autocomplete="off" class="formouter">
        <div class="va-step"><b>1</b> Select the customer and journal to archive</div>
        <div class="af-bar va-bar">
          <div class="af-field"><label>Customer</label>
            <?php
									$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` ORDER BY `cust_name` ASC ";
									$customers = $db->query($query) or ($db->error);		
									?>
                                <select id="cust_id" name="cust_id" class="form-select " data-placeholder="Choose" required >
                                  <option value="">-Select-</option>
                                  <?php while($row = $customers->fetch_assoc()){ ?>
                                  <option value="<?php echo $row['id']; ?>" <?php if ($_POST['cust_id'] == $row['id']) { echo ' selected="selected"'; } ?>><?php echo $row['cust_name']; ?></option>
                                  <?php } ?>
                                </select>
          </div>
          <div class="af-field"><label>Journal</label>
            <?php
	    $query1 = "SELECT * FROM adm_journals WHERE j_cust_id = '".$_REQUEST['cust_id']."' ORDER BY `j_name` ASC";
		$customers1 = $db->query($query1) or ($db->error);		
		?>
                                <select id="j_id" name="j_id" class="form-select " data-placeholder="Choose" required>
                                  <option value="">-Select-</option>
                                  <?php while($row1 = $customers1->fetch_assoc()){ ?>
                                  <option value="<?php echo $row1['j_id']; ?>" <?php if ($_POST['j_id'] == $row1['j_id']) { echo ' selected="selected"'; } ?>><?php echo $row1['j_code']; ?></option>
                                  <?php } ?>
                                </select>
          </div>
          <div class="af-actions" style="margin-left:0;">
            <button type="submit" value="Get Result" name="getaction" class="af-btn primary"><i class="fa fa-search"></i> Get Result</button>
            <a class="af-btn" href="volume_archive.php" title="Reset"><i class="fa fa-refresh"></i></a>
          </div>
        </div>
      </form>
    </div></div>
    <?php } ?>

    <?php
    if (isset($_POST['getaction'])) {
      $query11 = "SELECT * FROM inw_volume_issues WHERE cust_id = '".$_POST['cust_id']."' AND j_id = '".$_POST['j_id']."' ORDER BY vi_id ASC ";
      $customers11 = $db->query($query11) or ($db->error);
    ?>
    <div class="af-page"><div class="af-wrap">
      <form action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data" id="validation-form" method="post" autocomplete="off" class="formouter">
        <div class="va-step"><b>2</b> Select the volume to proceed</div>
        <div class="af-bar va-bar">
          <div class="af-field"><label>Volume</label>
            <?php
									$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` ORDER BY `cust_name` ASC ";
									$customers = $db->query($query) or ($db->error);		
									?>
                                <select id="v_folder" name="v_folder" class="form-select " data-placeholder="Choose" required >
                                  <option value="">-Select-</option>
                                <?php while($get = $customers11 ->fetch_assoc()){
                                        $newvolume_folder = VOLUME_PREFIX.$get['volume_number'].$get['issue_number'];            
										 ?>
                                  <option value="<?php echo $get['vi_id']; echo " ".$newvolume_folder; ?>" ><?php echo $newvolume_folder; ?></option>
								 <?php
								  }  
								  ?>
                                </select>
          </div>
          <div class="af-actions" style="margin-left:0;">
            <?php
            // Get the current hour in 24-hour format
            $currentHour = date('H');
            if ($currentHour >= 19 || $currentHour < 6) {
            ?>
            <button type="submit" value="Get Result" name="proceed" class="af-btn primary"><i class="fa fa-search"></i> Get Result</button>
            <a class="af-btn" href="volume_archive.php" title="Reset"><i class="fa fa-refresh"></i></a>
            <?php } else { ?>
            <span class="va-note"><i class="fa fa-clock-o"></i> The submit button is available from 7 PM to 6 AM.</span>
            <?php } ?>
          </div>
        </div>
        <input type="hidden" value="<?php echo htmlspecialchars($_POST['cust_id']); ?>" name="v_cust_id">
        <input type="hidden" value="<?php echo htmlspecialchars($_POST['j_id']); ?>" name="v_j_id">
      </form>
    </div></div>
    <?php } ?>

    <?php if (isset($_POST['proceed'])) {
      $v_folder1 = explode(" ",$_POST['v_folder']);
      $v_id = $v_folder1[0];
      $v_folder = $v_folder1[1];
    ?>
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data" id="validation-form" method="post" autocomplete="off">
      <input type="hidden" value="<?php echo htmlspecialchars($_POST['v_cust_id']); ?>" name="v_cust_id">
      <input type="hidden" value="<?php echo htmlspecialchars($_POST['v_j_id']); ?>" name="v_j_id">
      <input type="hidden" value="<?php echo htmlspecialchars($v_id); ?>" name="vi_id">
      <input type="hidden" value="<?php echo htmlspecialchars($v_folder); ?>" name="v_folder">
      <div class="af-page"><div class="af-card">
        <div class="va-top">
          <div class="va-step" style="margin:0;"><b>3</b> Articles in <?php echo htmlspecialchars($v_folder); ?></div>
          <button type="submit" class="af-btn primary" name="submit" id="submit"><i class="fa fa-folder"></i> Archive All</button>
        </div>
        <div class="table-responsive">
          <table id="articles" class="table custtable table-primary idr-compact" style="width: 100% !important; text-wrap: nowrap !important">
            <thead>
              <tr>
                <th data-af-role="client">Customer</th>
                <th data-af-role="journal">Journal</th>
                <th data-af-role="file">Article</th>
                <th class="hidden-480">Group folder</th>
                <th data-af-role="recv">Recvd Date</th>
                <th data-af-role="recv">Due Date</th>
                <th data-af-role="num">Manuscript</th>
                <th data-af-role="num">Fig</th>
                <th data-af-role="num">Table</th>
                <th data-af-role="stage">Stage</th>
                <th data-af-role="none">Status</th>
                <th class="hidden-480">Volume Folder</th>
              </tr>
            </thead>
          </table>
        </div>
      </div></div>
    </form>
    <?php } ?>

     <?php echo scripts(); ?> 
    
    <!-- Footer Section --> 
    <?php echo footer(); ?>  
<?php af_foot(); ?>
<!-- inline scripts related to this page --> 
<script type="text/javascript">


$(document).ready(function() {
    var table = $('#articles').DataTable( {
		dom: "<'row'<'col-sm-4'l><'col-sm-4 text-center'B><'col-sm-4'f>>tp",
       //table.row(':eq(0)', { page: 'current' }).select();
	    pageLength: 10,

		 buttons: [
            {
                extend: 'collection', // Use a collection button for the dropdown
                text: 'Export', // The text for the main button
                buttons: [
                    'copyHtml5',
                    'excelHtml5',
                    'csvHtml5',
                    {
                        extend: 'pdfHtml5',
                        customize: function (doc) {
                            // Customize the PDF content here (optional)
                        }
                    },
                    {
                        extend: 'print',
                        autoPrint: false, // Disable automatic printing
                        customize: function (win) {
                            // Customize the printed content here (optional)
                        }
                    }
                ]
            }
         ],

        "ajax": "json-download/volume-articles.json",
		"ordering" : true,
		"scrollCollapse" : true,
		"columnDefs" : [{"targets":4, "type":"date-eu"},{"targets":5, "type":"date-eu"}],
		"bInfo": true,

        
    } );
	
	
	    $('#articles').on('click', 'a[data-delete]', function () { 
		var del = $(this).attr('data-delete');
					swal({
					title: "Are you sure?",
					text: "You will not be able to recover this record!",
					type: "warning",
					showCancelButton: true,
					confirmButtonColor: "#DD6B55",
					confirmButtonText: "Yes, delete it!",
					cancelButtonText: "No, cancel!",
					closeOnConfirm: false,
					closeOnCancel: true },
				function (isConfirm) {
					if (isConfirm) {
						window.top.location="archive_form.php?delete="+del;
					}
				});
				

	});
	
	
} );

	


</script>
<script type="text/javascript">
			

jQuery(function($) {			
			
				//documentation : http://docs.jquery.com/Plugins/Validation/validate
			
				//datepicker plugin
				//link
				$('.date-picker').datepicker({
					autoclose: true,
					todayHighlight: true,
					format: 'dd-mm-yyyy'
				})
	
				//$('#validation-form').validate({errorClass: 'help-block'});			

			})
		</script>
		
		<script type="text/javascript">
			$(document).ready(function(){
    $('#cust_id').on('change',function(){
        var j_cust_id = $(this).val();
        if(j_cust_id){
            $.ajax({
                type:'POST',
                url:'get_journals.php',
                data:'j_cust_id='+j_cust_id,
                success:function(html){
                    $('#j_id').html(html);                    
                }
            }); 
        }else{
            $('#j_id').html('<option value="">Select customer first</option>');
            
        }
    });

	
	});
	
			
		</script>
		
		
		<!-- inline scripts related to this page --> 
<script type="text/javascript">
			jQuery(function($) {			
			//datepicker plugin
				//link
				$('.date-picker').datepicker({
					autoclose: true,
					todayHighlight: true,
					format: 'dd-mm-yyyy'
				})
	
					

			})
		</script> 
<script type="text/javascript">
			$(document).ready(function(){
  
	
		$('#stage_id').on('change',function()
	{
		//ert("stage_id");
        var stage= $(this).val();
        if(stage=="ISSCOR")
		{
			$('#issue_div').show();
		}
		else
		{
            $('#issue_div').hide();
			$('#issue_no').val("");
            
        }
		
		
    });
	
	});
	
			
		</script> 
</body>
</html>
