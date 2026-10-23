<?php include "includes/includes.php"; include_once 'includes/issue_drafts.php';?>
<?php
/**
 * Recursively move files from one directory to another
 * 
 * @param String $src - Source of files being moved
 * @param String $dest - Destination of files being moved
 */



if(isset($_POST['get_records_btn']))
{
				/// draft rounds: stamp the open round of each draft article as sent to the client
				idr_stamp_dispatch($db, (int)$_POST['volume_number'], false);
				$prj_update_query="UPDATE inw_inward_dtl SET status='Client Review',department_id='15'  WHERE v_issue_id= '".$_POST['volume_number']."' and j_id='".$_POST['j_id']."' and cust_id='".$_POST['cust_id']."' ";
				$db->query($prj_update_query);
				
				$update_vol_issue=mysqli_query($db,"update inw_volume_issues set status='Client Review' where vi_id='".$_POST['volume_number']."'");
				
	header("location:manage_issues.php?msg=Successfully added"); 
	 exit();
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
    <?php include_once 'includes/modern-ui.php'; include_once 'includes/article-filter-ui.php'; mu_head(); af_head(); ?>
    <style>
      .cs-note{display:flex;gap:12px;align-items:flex-start;margin:0 0 14px;padding:12px 14px;border-radius:12px;background:#fff6e5;border:1px solid #f1d9a6;color:#5a3d06;font-size:13px;}
      .cs-note i{margin-top:2px;color:#d98a06;font-size:16px;}
      .cs-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px 16px;}
      .cs-grid .af-field{min-width:0;}
      .cs-grid .form-select{height:38px;border-radius:9px;border:1px solid #d5dde6;font-size:13.5px;box-shadow:none;width:100%;}
      .cs-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:16px;}
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
          <div><small>Manage issues</small><h1>Change Issue Status</h1></div>
          <a class="af-add" href="manage_issues.php" style="background:rgba(255,255,255,.16);box-shadow:none;border:1px solid rgba(255,255,255,.35);"><i class="fa fa-arrow-left"></i> Back to Manage Issues</a>
        </div>
      </div>
    </div>

    <?php
    if(!empty($_REQUEST['msg']))  {
      echo '<div class="alert alert-success"><button type="button" class="close" aria-label="Close">&times;</button>'. htmlspecialchars($_REQUEST['msg']) .'</div>';
    } ?>

    <div class="af-page"><div class="af-card">
      <div class="cs-note"><i class="fa fa-exclamation-triangle"></i>
        <div><b>This moves the whole issue to Client Review.</b> Every article of the chosen issue is set to Client Review (department 15) and the issue itself is marked Client Review. Choose the customer, journal and issue, then confirm.</div>
      </div>
      <form class="form-horizontal" id="get_records" method="post">
        <div class="cs-grid">
          <div class="af-field">
            <label>Customer</label>
            <?php
		                                $query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` ORDER BY `cust_name` ASC ";
		                                $customers = $db->query($query) or ($db->error);		
		                            ?>
                                <select id="cust_id" name="cust_id" class="col-xs-12 col-sm-4 required form-select" data-placeholder="Choose">
                                  <option value="">-Select-</option>
                                  <?php while($row = $customers->fetch_assoc()){ ?>
                                  <option value="<?php echo $row['id']; ?>" <?php if($_POST['cust_id'] ==$row['id'] ){?> selected <?php } ?>><?php echo $row['cust_name']; ?></option>
                                  <?php } ?>
                                </select>
          </div>
          <div class="af-field">
            <label>Journal</label>
            <?php
		                              $query = "SELECT `j_id`,`j_name`,j_code FROM `adm_journals` where j_cust_id ='".$_POST['cust_id']."' ORDER BY `j_name` ASC ";
		                              $customers = $db->query($query) or ($db->error);		
		                            ?>
                                <select id="j_id" name="j_id" class="col-xs-12 col-sm-4 required form-select" data-placeholder="Choose">
                                  <option value="">-Select-</option>
                                  <?php while($row = $customers->fetch_assoc()){ ?>
                                  <option value="<?php echo $row['j_id']; ?>" <?php if($_POST['j_id'] == $row['j_id'] ){?> selected <?php } ?>><?php echo $row['j_code']; ?></option>
                                  <?php } ?>
                                </select>
          </div>
          <div class="af-field">
            <label>Volume / Issue</label>
            <?php
		                              $query = "SELECT `vi_id`,`volume_number`,issue_number FROM `inw_volume_issues` ORDER BY `volume_number` ASC ";
		                              $volume_issue = $db->query($query) or ($db->error);		
		                            ?>
                                <select id="volume_number" name="volume_number" class="col-xs-12 col-sm-4 required form-select" data-placeholder="Choose">
                                  <option value="">-Select-</option>
                                  <?php while($row = $volume_issue->fetch_assoc()){ ?>
                                  <option value="<?php echo $row['vi_id']; ?>" <?php if($_POST['volume_number'] ==$row['vi_id'] ){?> selected <?php } ?>><?php echo VOLUME_PREFIX.$row['volume_number'].$row['issue_number']; ?></option>
                                  <?php } ?>
                                </select>
          </div>
        </div>
        <div class="cs-actions">
          <a class="af-btn" href="manage_issues.php">Cancel</a>
          <button type="submit" value="Get Records" name="get_records_btn" id="get_records_btn" class="af-btn primary"><i class="fa fa-check"></i> Move to Client Review</button>
        </div>
      </form>
    </div></div>

   <!-- /.main-content -->
    <?php echo scripts(); ?> 
    <?php idr_error_script(); idr_error_popup('Cannot change the status'); ?>
    
    <!-- Footer Section --> 
    <?php echo footer(); ?> 
<?php af_foot(); ?>
<!-- inline scripts related to this page --> 
<script type="text/javascript">
$(document).ready(function(){
    $('#get_records').on('submit', function (e) {
        if (!$('#cust_id').val() || !$('#j_id').val() || !$('#volume_number').val()) { idrError('Please choose the customer, the journal and the volume / issue.', 'Selection required'); e.preventDefault(); return; }
        var v = $.trim($('#volume_number option:selected').text());
        if (!confirm('Move ' + v + ' and all its articles to Client Review?')) { e.preventDefault(); }
    });
    $("#cust_id").on("change", function() {
         var cust_id=$.trim($(this).val());

         // Fetch states based on the selected client name and populate the "name" select element
         $.ajax({
             url: "<?php echo $_COOKIE['set_url'];?>/get_journal.php",
             dataType: "json",
             data: { cust_id:cust_id },
             success: function(data) {
                 populateSelecte("j_id",data);

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
	
	///////////// Get workflow from Journal //////////
	
	$('#j_id').on('change',function(){
        var j_id = $(this).val();
		var j_cust_id = $('#cust_id').val();
        if(j_id){
            $.ajax({
                type:'POST',
                url:'get_vol_cs_issue.php',
                data:'j_id='+j_id+'&j_cust_id='+j_cust_id,
                success:function(html){
                    $('#volume_number').html(html);                    
                }
            }); 
        }else{
            $('#volume_number').html('<option value="">Select Customer and Journal first</option>');
            
        }
    });
	
	
	});
		
		</script>
</body>
</html>
