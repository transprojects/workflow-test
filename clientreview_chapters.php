<?php 
include ('dbc.php'); 
page_protect();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
<meta charset="utf-8" />
<title>Transforma - Workflow</title>
<meta name="description" content="Static &amp; Dynamic Tables" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

<!-- bootstrap & fontawesome -->
<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

<!-- page specific plugin styles -->

<!-- text fonts -->
<link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />

<!-- ace styles -->
<link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />

<!--[if lte IE 9]>
			<link rel="stylesheet" href="assets/css/ace-part2.min.css" class="ace-main-stylesheet" />
		<![endif]-->
<link rel="stylesheet" href="assets/css/ace-skins.min.css" />
<link rel="stylesheet" href="assets/css/ace-rtl.min.css" />

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
          <li class="active">Issues</li>
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
          <h1> Issues</h1>
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
			
			<table id="dynamic-table" class="table table-striped table-bordered table-hover">
                <thead>
                  <tr>
                    <th class="center"> <label class="pos-rel">
                        <input type="checkbox" class="ace" />
                        <span class="lbl"></span> </label>
                    </th>
                    <th>Customer</th>
                    <th>Book</th>
                    <th>Chapters</th>
                    <th>Created Date</th>
                    <th>Due Date</th>
                    <th>Stage</th>
                    
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php
				  $query = "SELECT vi.book_short_name,c.id,c.cust_name,wd.chapter_title,wd.id,wd.recv_dt,wd.due_dt,wd.stage FROM `inw_chapter_dtl` as wd, inw_book_dtl as vi, adm_customer_master as c WHERE vi.id=wd.b_id AND wd.cust_id = c.id AND wd.status = 'Client Review' ORDER BY wd.b_id DESC ";				  
		
		$customers = $db->query($query) or ($db->error);				
		while($row = $customers->fetch_assoc()){ 
		
		
		?>
                  <tr>
                    <td class="center"><label class="pos-rel">
                        <input type="checkbox" class="ace" />
                        <span class="lbl"></span> </label></td>
                    <td><?php echo $row['cust_name']; ?></td>
                    <td><?php echo $row['book_short_name']; ?></td>
                    <td><?php echo $row['chapter_title']; ?></td>
                    <td><?php echo $row['recv_dt']; ?></td>
                    <td><?php echo $row['due_dt']; ?></td>
                    <td><?php echo $row['stage']; ?></td>
                    
                    <td>
					<?php
                   if($_COOKIE['department_id'] == 16 || $_COOKIE['user_level'] == 1){ ?>
                        <?php if($row['stage'] == 'FIN') { ?>
                        <a class="btn btn-xs btn-primary btn-sm" href="#"> <i class="ace-icon fa fa-folder bigger-120"></i> Archive</a>
                        <?php } else { ?>
                        <a class="btn btn-xs btn-primary" href="rev_chapter.php?id=<?php echo $row['id']; ?>"> <i class="ace-icon fa fa-history bigger-120"></i> Revises </a> <a class="btn btn-xs btn-pink btn-sm" href="fin_chapter.php?id=<?php echo $row['id']; ?>"> <i class="ace-icon fa fa-share bigger-120"></i> Final </a> 
						<a class="btn btn-xs btn-warning" href="undo_chapters.php?id=<?php echo $row['id']; ?>"> <i class="ace-icon fa fa-undo bigger-120"></i> Undo</a>
                        <?php } ?>
                        <?php } ?>
                      </div>
                      </td>
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
<script src="assets/js/dataTables.buttons.min.js"></script> 
<script src="assets/js/buttons.flash.min.js"></script> 
<script src="assets/js/buttons.html5.min.js"></script> 
<script src="assets/js/buttons.print.min.js"></script> 
<script src="assets/js/buttons.colVis.min.js"></script> 
<script src="assets/js/dataTables.select.min.js"></script> 

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
