<?php 
include ('dbc.php'); 
page_protect();
?>
<?php include "get/json-aws-test-dispatch-articles.php";?>

<?php

if($_REQUEST['delete']!='')
{	
	// $db->query("delete from inw_inward_dtl where id='".$_REQUEST['id']."'");
	header("location:aws_test_dispath_projects.php?msg=Successfully completed");
	die();	
}


if(isset($_REQUEST['getaction']) && $_REQUEST['getaction']!='')
{
	
$_COOKIE['cust_id'] = $_POST['cust_id'];
$_COOKIE['stage_id'] = $_POST['stage_id'];
$_COOKIE['j_id'] = $_POST['j_id'];
$_COOKIE['pub_id'] = $_POST['pub_id'];
$_COOKIE['from_dt'] = $_POST['from_dt'];
$_COOKIE['to_dt'] = $_POST['to_dt'];

$var = '';
if($_COOKIE['cust_id']!=''){
	
     $var.= "and c.id = '".$_COOKIE['cust_id']."' ";
}
if($_COOKIE['stage_id']!=''){
	
      $var.= "and stage LIKE '".$_COOKIE['stage_id']."%' ";
}
if($_COOKIE['j_id']!=''){
	
     $var.= "and j.j_id = '".$_COOKIE['j_id']."' ";
}
if($_COOKIE['pub_id']!=''){
	
     $var.= "and pub_id LIKE '%".$_COOKIE['pub_id']."%' ";
}
if($_COOKIE['from_dt']!='' && $_COOKIE['to_dt']){
	
   $var.="and recv_dt BETWEEN '".date('Y-m-d',strtotime($_COOKIE['from_dt']))."' and '".date('Y-m-d',strtotime($_COOKIE['to_dt']))."'";
}

	//$_COOKIE['varname'] = $var;
}

	
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
<meta charset="utf-8" />
<title>Articles - Workflow</title>
<meta name="description" content="3 styles with inline editable feature" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

<!-- bootstrap & fontawesome -->
<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.5.2/css/buttons.dataTables.min.css" />

<!-- text fonts -->
<link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />

<!-- ace styles -->
<link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />

<!--[if lte IE 9]>
			<link rel="stylesheet" href="assets/css/ace-part2.min.css" class="ace-main-stylesheet" />
		<![endif]-->
<link rel="stylesheet" href="assets/css/ace-skins.min.css" />
<link rel="stylesheet" href="assets/css/ace-rtl.min.css" />


<link href="assets/js/sweetalert/sweetalert.css" rel="stylesheet"/>

<!--[if lte IE 9]>
		  <link rel="stylesheet" href="assets/css/ace-ie.min.css" />
		<![endif]-->

<!-- inline styles related to this page -->

<!-- ace settings handler -->
<script src="assets/js/ace-extra.min.js"></script>

<!-- HTML5shiv and Respond.js for IE8 to support HTML5 elements and media queries -->

<!--[if lte IE 8]>
		<script src="assets/js/html5shiv.min.js"></script>
		<script src="assets/js/respond.min.js"></script>
		<![endif]-->
<style>

.paging_simple_numbers{
	margin-top:20px;
}
.action-buttons a{
	margin:0px 2px !important;
}
</style>		
		
		
</head>

<body class="no-skin">
<!-- .navbar-container -->
<?php include("includes/header.php"); ?>
<!-- /.navbar-container -->

<div class="main-container " id="main-container"> 
  <!-- .sidebar-shortcuts -->
  <?php include("includes/left_sidebar.php"); ?>
  <!-- /.sidebar-shortcuts -->
  
  <div class="main-content">
    <div class="main-content-inner">
      <div class="breadcrumbs " id="breadcrumbs">
        <ul class="breadcrumb">
          <li> <i class="ace-icon fa fa-home home-icon"></i> <a href="#">Home</a> </li>
          <li> <a href="#">Work</a> </li>
          <li class="active">Dispatch Article</li>
        </ul>
        <!-- /.breadcrumb -->
        
        <div class="nav-search" id="nav-search">
          <form class="form-search">
            <span class="input-icon">
            <input type="text" placeholder="Search ..." class="nav-search-input" id="nav-search-input" autocomplete="off" />
            <i class="ace-icon fa fa-search nav-search-icon"></i> </span>
          </form>
        </div>
        <!-- /.nav-search --> 
      </div>
      <div class="page-content">
        <div class="page-header">
          <h1>AWS TEST - Dispatch Articles
            
          </h1>
        </div>
        <!-- /.page-header -->
		<div class="row">

        <div class="span12">
        <?php
        if(!empty($_REQUEST['msg']))  {
        echo '<div class="alert alert-success">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        '. $_REQUEST['msg'] .'</div>';   

        } ?> 
        </div>
      </div>
	   <?php 
		if($_COOKIE['user_level']==1 || $_COOKIE['user_level']==3  || $_COOKIE['user_level']==5) 
		{
		?>
              <form action="aws_test_dispath_projects.php" id="validation-form" method="post" autocomplete="off">
                   <div class="form-group col-md-2">
                            <label class="control-label">Customer:</label><br>
                            
                            
									<?php
		if($_COOKIE['user_level']== 5) { 
		$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` where id in('46','42','66')  ORDER BY `cust_name` ASC ";
		}
		else{
		$query = "SELECT cust_name,cust_id,id FROM `adm_customer_master` where id in('46','42','66') ORDER BY `cust_name` ASC ";
		}									$customers = $db->query($query) or ($db->error);		
									?>
                                <select id="cust_id" name="cust_id" class="" data-placeholder="Choose">
                                  <option value="">-Select-</option>
                                  <?php while($row = $customers->fetch_assoc()){ ?>
                                  <option value="<?php echo $row['id']; ?>" <?php if ($_POST['cust_id'] == $row['id']) { echo ' selected="selected"'; } ?>><?php echo $row['cust_name']; ?></option>
                                  <?php } ?>
                                </select>
                             
                     </div>

                    <div class="form-group col-md-2">
                            <label class="control-label" style="">Journal:</label> <br>
                           
                                  <?php
	    $query = "SELECT acm.cust_name,acm.cust_id,acm.id,aj.j_id,aj.j_name,aj.j_code FROM `adm_customer_master` as acm INNER JOIN adm_journals as aj WHERE acm.id = aj.j_cust_id and acm.id = '46' ORDER BY `j_name` ASC";
		$customers = $db->query($query) or ($db->error);		
		?>
                                <select id="j_id" name="j_id" class="" data-placeholder="Choose" style="width: 180px;">
                                  <option value="">-Select-</option>
                                  <?php while($row = $customers->fetch_assoc()){ ?>
                                  <option value="<?php echo $row['j_id']; ?>" <?php if ($_POST['j_id'] == $row['j_id']) { echo ' selected="selected"'; } ?>><?php echo $row['j_code']; ?></option>
                                  <?php } ?>
                                </select>
                              </div>
                            
                           <div class="form-group col-md-2">
                            <label class="control-label" style="">Stage:</label>
                           
                                <select id="stage_id" name="stage_id" class="" data-placeholder="Choose" style="width: 180px;">
                                  <option value="">-Select-</option>
                                  <option value="FP" <?php if ($_POST['stage_id'] == 'FP') { echo ' selected="selected"'; } ?>>-First Proof-</option>
								  <option value="REV" <?php if ($_POST['stage_id'] == 'REV') { echo ' selected="selected"'; } ?>>-Revises-</option>
								  <option value="FIN" <?php if ($_POST['stage_id'] == 'FIN') { echo ' selected="selected"'; } ?>>-Final-</option>
								  <option value="ISSCOR" <?php if ($_POST['stage_id'] == 'ISSCOR') { echo ' selected="selected"'; } ?>>-Issue-</option>
								  <option value="ISSCORFINAL" <?php if ($_POST['stage_id'] == 'ISSCORFINAL') { echo ' selected="selected"'; } ?>>-IssueFinal-</option>
								  <option value="Sample" <?php if ($_POST['stage_id'] == 'Sample') { echo ' selected="selected"'; } ?>>-Sample-</option>
                                </select>
                              </div>
                            
						   
                           <div class="form-group col-md-1" style="width:10%;">
                            <label class="control-label" style="">From Date:</label>
                            
                              <div class="input-group">
                                <input type="text" id="from_dt" name="from_dt" style="width: 110px;" class="date-picker"  value="<?php echo $_POST['from_dt'];?>"/>
                              </div>
                         
							 </div>
                      
					   
					   
                          <div class="form-group col-md-1"style="width:10%;">
                            <label class="control-label" style="">To Date:</label>
                            
                              <div class="input-group">
                                <input type="text" id="to_dt" name="to_dt" style="width: 110px;" class="date-picker" value="<?php echo $_POST['to_dt'];?>"/>
                               </div>
                           

                           </div>
						   
						   <div class="form-group col-md-1">
                            <label class="control-label" style="">Article ID:</label>
                            
                              <div class="input-group">
                                <input type="text" id="pub_id" name="pub_id" style="width: 110px;" class="" value="<?php echo $_POST['pub_id'];?>"/>
                               </div>
                           

                           </div>
						   
						   <div class="form-group col-md-2">
						        <input type="submit" value="Get Result"  name="getaction" class="btn btn-info" style="margin-top: 20px;margin-bottom: 20px;margin-left: 26px;">
														       
							<a href="dispath_projects.php"><img src="assets/images/refresh.png" style="width:30px;float:right;margin-top: 26px;"></a>
						   </div>
						    
						   
						   
						    
						   
						   
                          </form>
						  <?php
		}
		?>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- /.widget-main -->
				<div class="container-fluid">
				<p>Total Count of Articles - <b style="color:red;"><?php echo $num_query;?></b></p>
				<div class="table-responsive">
                <table id="dynamic-table" class="table table-striped table-bordered table-hover"  >
                  <thead>
                    <tr>
                     <th>Customer</th>
					<th style="width:95px !important;">Journal</th>
                    <th  style="width:105px;">Article</th>
					<th style="width:125px;">Group folder</th>                   
                    <th style="width:145px;"> <i class="ace-icon fa fa-clock-o bigger-110 hidden-480"></i> Recvd Date</th>
                    <th style="width:125px;"> <i class="ace-icon fa fa-clock-o bigger-110 hidden-480"></i> Due Date</th>
					<th>Proof page count</a></th>
                    <th class="hidden-480">Fig</th>
					<th class="hidden-480">Table</th>
                    <th class="hidden-480">Stage</th>
                    <th class="hidden-480">Status</th>
				   <th></th>
	
                    </tr>
                  </thead>
                
                </table>
			     </div>
			    </div>
             
  <!-- /.main-content -->
  
  <?php include("includes/footer.php"); ?>
  <a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse"> <i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i> </a> </div>
<!-- /.main-container --> 

<!-- basic scripts --> 

<!--[if !IE]> -->
		<script src="assets/js/jquery-2.1.4.min.js"></script>

		<!-- <![endif]--> 

<!--[if IE]>
<script src="assets/js/jquery-1.11.3.min.js"></script>
<![endif]--> 
<script type="text/javascript">
			if('ontouchstart' in document.documentElement) document.write("<script src='assets/js/jquery.mobile.custom.min.js'>"+"<"+"/script>");
		</script> 
<script src="assets/js/bootstrap.min.js"></script> 

<!-- page specific plugin scripts --> 

<!-- page specific plugin scripts --> 
<script src="assets/js/jquery.dataTables.min.js"></script> 
<script src="assets/js/jquery.dataTables.bootstrap.min.js"></script> 
<script src="assets/js/dataTables.buttons.min.js"></script> 
<script src="assets/js/buttons.flash.min.js"></script> 
<script src="assets/js/buttons.html5.min.js"></script> 
<script src="assets/js/buttons.print.min.js"></script> 
<script src="assets/js/buttons.colVis.min.js"></script> 
<script src="assets/js/dataTables.select.min.js"></script> 
<script src="assets/js/bootstrap-datepicker.min.js"></script> 

<!-- ace scripts --> 
<script src="assets/js/ace-elements.min.js"></script> 
<script src="assets/js/ace.min.js"></script> 
<script src="assets/js/bootbox.js"></script>
<script src="//cdn.datatables.net/plug-ins/1.10.11/sorting/date-eu.js"></script>

<!-- inline scripts related to this page --> 
<script type="text/javascript">

$(document).ready(function() {
    var table = $('#dynamic-table').DataTable( {
		dom: "<'row'<'col-sm-4'l><'col-sm-4 text-center'B><'col-sm-4'f>>tp",
       //table.row(':eq(0)', { page: 'current' }).select();
	    pageLength: 10,

		buttons: [
		<?php 
		if($_COOKIE['user_level']== 1) 
		{
		?>
			{extend: 'copy', title: 'Dispatch Articles',className: 'btn-sm',exportOptions: {
                    columns: [ 0, 1, 2, 3, 4, 5 ]
                }},
			{extend: 'csv',title: 'Dispatch Articles', className: 'btn-sm',exportOptions: {
                    columns: [ 0, 1, 2, 3, 4, 5 ]
                }},
			{extend: 'pdf', title: 'Dispatch Articles', className: 'btn-sm',exportOptions: {
                    columns: [ 0, 1, 2, 3, 4, 5 ]
                }},
			{extend: 'print', title: 'Dispatch Articles',className: 'btn-sm',exportOptions: {
                    columns: [ 0, 1, 2, 3, 4, 5 ]
                }}
						<?php 
		}
		?>
		],
        "ajax": "json-download/test-dispatch-articles.json",
		"ordering" : true,
		"scrollCollapse" : true,
		"columnDefs" : [{"targets":4, "type":"date-eu"},{"targets":5, "type":"date-eu"}],
		"bInfo": true,

        
    } );
	
	
	    $('#dynamic-table').on('click', 'a', function () { 
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
						window.top.location="dispatch_projects.php?delete="+del;
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
