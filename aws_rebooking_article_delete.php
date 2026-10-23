<?php 
include ('dbc.php'); 
page_protect();
?>
<?php
if(isset($_REQUEST['id']) && $_REQUEST['id']!='' && $_REQUEST['action']=='delete')
{	
	//$db->query("delete from inw_inward_dtl where id='".$_REQUEST['id']."'");
	header("location:projects.php?msg=Successfully completed");
	die();	
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
<meta charset="utf-8" />
<title>AWS FP PROOFING FILES DELETE (REBOOKING) - Workflow</title>
<meta name="description" content="Static &amp; Dynamic Tables" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

<!-- bootstrap & fontawesome -->
<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

<!-- page specific plugin styles -->

<!-- ace styles -->
<link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />

<!--[if lte IE 9]>
			<link rel="stylesheet" href="assets/css/ace-part2.min.css" class="ace-main-stylesheet" />
		<![endif]-->


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
          <li class="active">AWS REBOOKING ARTICLES</li>
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
          <h1> AWS FP PROOFING FILES DELETE (REBOOKING)
            
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
        
        <div class="row">
          <div class="col-xs-12"> 
            <!-- PAGE CONTENT BEGINS --> 
            
            <!-- div.dataTables_borderWrap -->
            <div>
              <table id="dynamic-table" class="table table-bordered table-hover">
                <thead>
                  <tr>
                    <th class="center"> <label class="pos-rel">
                        <input type="checkbox" class="ace" />
                        <span class="lbl"></span> </label>
                    </th>
                    <th>Customer</th>
					<th>Journal</th>
                    <th>Article</th>                   
                    <th> <i class="ace-icon fa fa-clock-o bigger-110 hidden-480"></i> Received On </th>
                    <th> <i class="ace-icon fa fa-clock-o bigger-110 hidden-480"></i> Due Date </th>
					<th class="hidden-480">Manuscript</th>
                    <th class="hidden-480">Fig</th>
					<th class="hidden-480">Table</th>
                    <th class="hidden-480">Stage</th>
                    <th class="hidden-480">Status</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php
				  
			
			$query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,work_dir,r.received_date,r.due_date,r.correction_pages FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON wd.`id` =r.`inw_id`, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  AND wd.stage IN ('FP') and wd.cust_id=42 and wd.v_issue_id <= 0 and wd.rebooking='1'  ORDER BY `id` DESC ";
		
		//echo $query;
		$customers = $db->query($query) or ($db->error);				
		while($row = $customers->fetch_assoc()){ 
		
		if($row['stage'] == "FP")
		{
			$due_dt = (date('d-m-Y',strtotime($row['due_dt'])));
			$recv_dt = (date('d-m-Y',strtotime($row['recv_dt'])));
			$manuscript_count = $row['manuscript_count'];
		}
		else
		{
			$due_dt = (date('d-m-Y',strtotime($row['due_date'])));
			$recv_dt = (date('d-m-Y',strtotime($row['received_date'])));
			$manuscript_count = $row['correction_pages'];
		}
		?>
                  <tr class="alert alert-danger">
                    <td class="center"><label class="pos-rel">
                        <input type="checkbox" class="ace" />
                        <span class="lbl"></span> </label></td>
                    <td><?php echo $row['cust_name']; ?></td>
					<td><?php echo $row['j_code']; ?></td>
                    <td class="hidden-480"><?php echo $row['pub_id']; ?></td>                   
                    <td><?php echo $recv_dt; ?></td>
					<td><?php echo $due_dt; ?></td>
					 <td><?php echo $manuscript_count; ?></td>
                     <td><?php echo $row['fig']; ?></td>
                    <td class="hidden-480"><?php echo $row['tab']; ?></td>
                    <td class="hidden-480"><?php echo $row['stage']; ?></td>
                    <td class="hidden-480"><span class="label label-sm label-success"><?php echo $row['status']; ?></span></td>
                     <td><div class="hidden-sm hidden-xs action-buttons">
					<a class="light-red" title="Delete Authour Proof articles" href="?id=<?php echo $proofsinfo['id']; ?>&&pub_id=<?php echo $proofsinfo['pub_id']; ?>&&proof_id=<?php echo $proofsinfo['id']; ?>&&ac=IAPA"> <i class="ace-icon fa fa-cloud-download bigger-180"></i> </a>
					</div></td>
                  </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
            
            <!-- PAGE CONTENT ENDS --> 
          </div>
          <!-- /.col --> 
        </div>
        <!-- /.row --> 
      </div>
      <!-- /.page-content --> 
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


<!-- ace scripts --> 
<script src="assets/js/ace-elements.min.js"></script> 
<script src="assets/js/ace.min.js"></script> 

<!-- inline scripts related to this page --> 
<script type="text/javascript">
			jQuery(function($) {
				//initiate dataTables plugin
				var myTable = 
				$('#dynamic-table')
				//.wrap("<div class='dataTables_borderWrap' />")   //if you are applying horizontal scrolling (sScrollX)
				.DataTable( {
					bAutoWidth: false,
					"aoColumns": [
					  { "bSortable": false },
					  null, null,null, null, null,null,null,null,null,null,
					  { "bSortable": false }
					],
					"aaSorting": [],
					select: {
						style: 'multi'
					}
			    } );
			
				
				
						
			})
			
function confirmDelete(delUrl) 
{
	if (confirm("Are you sure you want to delete")) 
	{
		document.location = delUrl;
	}
}		
		</script>
</body>
</html>
