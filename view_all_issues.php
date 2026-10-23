<?php 
include ('includes/includes.php');
include_once 'includes/issue_drafts.php';
$idr_map = idr_issue_map(idr_runner_mysqli($db));
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
    
    <!-- Data Table CSS- -->
    
    </head>
    <style>
      .dataTables_wrapper .dataTables_filter{
        margin-bottom : 10px !important;
      }
      td:last-child  a{
        font-size : 0.925em;
      }
    </style>
    <body>
    <div class="wholeouter">
    <!-- Header logo section -->
    
    <?php include("includes/top-header.php");?>
    
    <div class="container-fluid" style="justify-content: space-between;">
      <div class="af-page" style="padding-bottom:0;">
        <div class="af-hero">
          <div><small>All volumes and issues</small><h1>Manage Issues</h1></div>
          <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a class="af-add" href="<?php echo $_COOKIE['set_url'];?>/create_issue.php"><i class="fa fa-plus"></i> Create Issue</a>
            <a class="af-add" style="background:rgba(255,255,255,.16);box-shadow:none;border:1px solid rgba(255,255,255,.35);" href="<?php echo $_COOKIE['set_url'];?>/reject_issue.php"><i class="fa fa-exchange"></i> Reject / Assign Issue</a>
          </div>
        </div>
      </div>
    </div>
        <!-- /.page-header -->
        <div class="row mt-2">
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
            <div class="clearfix">
              <div class="pull-right tableTools-container"></div>
            </div>
            <!-- div.dataTables_borderWrap -->
            <div class="af-page"><div class="af-card">
            <div class="table-responsive">
            <table id="articles" class="table custtable table-primary" style="width: 100% !important; text-wrap: nowrap; white-space: nowrap">
                <thead>
                  <tr>
                    <th class="center"> <label class="pos-rel">
                        <input type="checkbox" class="ace" />
                        <span class="lbl"></span> </label>
                    </th>
                    <th data-af-role="client">Customer</th>
                    <th data-af-role="journal">Journal</th>
                    <th data-af-role="file">Issue Folder Name</th>
                    <th data-af-role="recv">Created Date</th>
                    <th data-af-role="recv">Due Date</th>
                    <th data-af-role="stage">Stage</th>
                    <th data-af-role="num">#Articles Done</th>
                    <th data-af-role="num">#Articles Pending</th>
                    <th>Draft Status</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php
		$query = "SELECT vi.stage,vi.vi_id,vi.received_date,vi.due_date,volume_number,issue_number,cus.cust_name,ju.j_code,(SELECT COUNT(id) FROM inw_inward_dtl WHERE v_issue_id = vi.vi_id AND status IN ('Delivery','Client Review','Completed','Closed')) as ccount,(SELECT COUNT(id) FROM inw_inward_dtl WHERE v_issue_id = vi.vi_id AND status NOT IN ('Delivery','Client Review','Completed','Closed')) as pcount FROM inw_volume_issues as vi LEFT JOIN adm_customer_master as cus ON vi.cust_id = cus.id LEFT JOIN adm_journals as ju ON vi.j_id = ju.j_id  ORDER BY vi.vi_id ASC;";
		$customers = $db->query($query) or ($db->error);				
		while($row = $customers->fetch_assoc()){ 
		
		
		?>
                  <tr>
                    <td class="center"><label class="pos-rel">
                        <input type="checkbox" class="ace" />
                        <span class="lbl"></span> </label></td>
                    <td><?php echo $row['cust_name']; ?></td>
                    <td><?php echo $row['j_code']; ?></td>
                    <td><?php echo VOLUME_PREFIX.$row['volume_number'].$row['issue_number']; ?></td>
                    <td><?php echo $row['received_date'] ? date('d-m-Y', strtotime($row['received_date'])) : ''; ?></td>
                    <td><?php echo $row['due_date'] ? date('d-m-Y', strtotime($row['due_date'])) : ''; ?></td>
                    <td><?php echo $row['stage']; ?></td>
                    <td><?php echo $row['ccount']; ?></td>
                    <td><?php echo $row['pcount']; ?></td>
                    <td><?php echo idr_issue_cell($idr_map, $row['vi_id']); ?></td>
                    <td>
                      <div class="hidden-sm hidden-xs action-buttons"><a class="btn btn-xs btn-primary" href="view_all_issue_articles.php?id=<?php echo $row['vi_id']; ?>"><i class="ace-icon fa fa-eye bigger-120"></i> View </a> </div>
                      
                      </td>
                  </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div></div></div>
            
            <!-- PAGE CONTENT ENDS --> 
          </div>
          <!-- /.col --> 
        </div>
        <!-- /.row --> 
      
    <?php echo scripts(); ?> 
    
    <!-- Footer Section --> 
    <?php echo footer(); ?> 
<!-- inline scripts related to this page --> 
<script type="text/javascript">
			jQuery(function($) {
				//initiate dataTables plugin
				var myTable = 
				$('#articles')
				//.wrap("<div class='dataTables_borderWrap' />")   //if you are applying horizontal scrolling (sScrollX)
				.DataTable( {
					bAutoWidth: false,
					"aoColumns": [
					  { "bSortable": false },
					  null, null,null, null, null,null,null,null,null,
					  { "bSortable": false }
					],
					"aaSorting": [],				
			
					select: {
						style: 'multi'
					},

          dom: 'Blfr<"table-wrapper"t>ip',
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
			    } );
			
				
				
						
			})
			
	
		</script>
<?php af_foot(); ?>
</body>
</html>
