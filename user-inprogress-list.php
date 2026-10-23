<?php 
include ('includes/includes.php');
include_once 'includes/list_ui.php'; 

if(isset($_REQUEST['getaction']))
{
	
$var = '';
if($_POST['dep_id']!=''){
	
     $var.= "and it.dept = '".$_POST['dep_id']."' ";
}
if($_POST['user_id']!=''){
	
      $var.= "and it.process_user = '".$_POST['user_id']."' ";
}

if($_POST['from_dt']!='' && $_POST['to_dt']){
	
   $var.="and it.start_time BETWEEN '".date('Y-m-d',strtotime($_POST['from_dt']))." 00:00:00' and '".date('Y-m-d',strtotime($_POST['to_dt']))."  23:59:59'";
}

}
	if(isset($_REQUEST['del']))
{	
	$db->query("delete from inw_transactions where id='".$_REQUEST['del']."'");
	header("location:".$_COOKIE['set_url']."/userinprogresslist?msg=Successfully deleted");
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
    <?php lu_css(); ?>
    
    <!-- Data Table CSS- -->
    <style>
      td:last-child  a {
        font-size: 0.925em !important;
      }
    </style>
    </head>
    
    <body>
    <div class="wholeouter">
    <!-- Header logo section -->
    
    <?php include("includes/top-header.php");?>
    <?php lu_hero('User WIP Article List', 'User WIP Article List &rsaquo; All', '#', ''); ?>
<div class="container-fluid" style="justify-content: space-between;">
      <div div class="row pt-4 d-flex align-items-center flex-wrap">
        <div class="col-8 col-lg-10">
          <p class="articleheading ms-4">User WIP Article List</p>
        </div>
      </div>
    </div>   
        <!-- /.page-header -->
        <div class="row" style="width: 100%">
          <div class="span12">
            <?php
        if(!empty($_REQUEST['msg']))  {
        echo '<div class="alert alert-success">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        '. $_REQUEST['msg'] .'</div>';   
        } ?>
          </div>
        </div>
		
		<div class="divouter mt-4">
        <form action="" name="articles" class="formouter" method="post">
          <div class="selectdiv row p-4 d-flex gap-2">
              <div class="col-12 col-lg-3">
                <label class="mb-2">Choose Department</label>
                 <?php
    $query = "SELECT dept_name,dept_code FROM `adm_dept_master`  ORDER BY `dept_name` ASC ";
    $customers = $db->query($query) or ($db->error);		
    
    ?><select id="dep_id" name="dep_id" class="form-control"  data-placeholder="Choose" required>
    <option value="">-Select-</option>
    <?php while($row = $customers->fetch_assoc()){ ?>
    <option value="<?php echo $row['dept_code']; ?>" <?php if($_REQUEST['dep_id'] ==$row['dept_code']){?> selected <?php } ?>><?php echo $row['dept_name']; ?></option>
    <?php } ?>
    </select>
              </div>
			   <div class="col-12 col-lg-3">
                <label class="mb-2">Choose Users</label>
                 <?php
//read data
 $query_user = "SELECT `id`,`user_name`,`full_name` FROM `users` WHERE department_id = '".$_REQUEST['dep_id']."' and  approved='1' ORDER BY `full_name` ASC ";
$users_r = $db->query($query_user) or ($db->error);
?><select id="user_id" name="user_id" class="form-control"  data-placeholder="Choose">
   <option value="">-Select-</option>
<?php		
while($row_u = $users_r->fetch_assoc())
{
	?>   
<option value="<?php echo $row_u['id'];?>" <?php if($_REQUEST['user_id'] ==$row_u['id']){?> selected <?php } ?>><?php echo $row_u['full_name']; ?></option>
    <?php
}
?>
    </select>
              </div>
             <div class="col col-lg-2">
             <label class="mb-2">From Date</label>
                <div class="d-flex">
                  <input type="text"class="form-control monthlyinputdate" name="from_dt" placeholder="From Date" value="<?php echo $_POST['from_dt'];?>" readonly/>
                </div>
             </div>
             <div class="col col-lg-2">
               <label class="mb-2">To Date</label>
                <div class="d-flex">
                  <input type="text" class="form-control monthlyinputdate1" name="to_dt" placeholder="To Date" value="<?php echo $_POST['to_dt'];?>" readonly/>
                </div>
             </div>
             <div class="col-12 mt-5 d-flex justify-content-center align-items-center">
              <button type="submit" name="getaction" class="resultbtn">Get Result</button>		  
              <a class="refreshbtn ms-4" href="userinprogresslist"><img src="./assets/images/refresh.svg"></a>
             </div>
             </div>
              </form>
              </div>
			  <?php 
			  if(isset($_REQUEST['user_id']))
			  {
			  ?>
        <div class="row">
          <div class="col-xs-12"> 
            <!-- PAGE CONTENT BEGINS --> 
           
            <!-- div.dataTables_borderWrap -->
            <?php lu_card_open(); ?>
       <div class="table-responsive custom-container">
            <div class="container-fluid table-responsive" style="overflow: auto">
            <table id="dynamic-table" class="table custtable table-primary" style="width: 100% !important; overflow:auto !important;text-wrap: nowrap !important;white-space: nowrap">
                <thead>
                  <tr>
                    <th>Article ID</th>
                    <th>User</th>
                    <th>Created Date Time</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
		                  $query = "SELECT pub_id,start_time,current_status,it.id,full_name from inw_transactions as it inner join inw_inward_dtl on it.project_id=inw_inward_dtl.id inner join users on it.process_user=users.id where it.current_status = 'Inprogress' $var  ORDER BY it.id ASC;";
		              // echo $query;
		                $customers = $db->query($query) or ($db->error);				
		                while($row = $customers->fetch_assoc()){ 		
		              ?>
                  <tr>
                    <td><?php echo $row['pub_id']; ?></td>
                    <td><?php echo $row['full_name']; ?></td>
                    <td><?php echo date('d-m-Y H:i:s',strtotime($row['start_time'])); ?></td>
                    <td><?php echo $row['current_status']; ?></td>
                    <td><button data-delete="<?php echo $row['id']; ?>" class="btn btn-danger demo4   delete-article" type="button"><i class="ace-icon fa fa-trash-o bigger-120"></i></button>
		    </a></td>
                 
                  </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
<?php lu_close(); ?>
                        </div>
            
            <!-- PAGE CONTENT ENDS --> 
          </div>
          <!-- /.col --> 
        </div>
			 <?php 
			  }
			  ?>
          <!-- /.main-content -->
    <?php echo scripts(); ?> 
    
    <!-- Footer Section --> 
    <?php echo footer(); ?> 
<!-- inline scripts related to this page --> 
   


<script type="text/javascript">

	
	  $(function () {
        // var datesForDisable = ["2017-10-14", "2017-10-15", "2017-10-16"];
        var date = new Date();
        date.setDate(date.getDate() - 1);
        var fromdate;
        $(".monthlyinputdate").datepicker({
          uiLibrary: "bootstrap5",
          format: "dd-mm-yyyy",
          autoclose: true,
         //  minDate: date,
              onSelect: function (dateText, inst) {
             fromdate = dateText;

                    $(".monthlyinputdate1").datepicker("option", "minDate", fromdate);
                }
        });

        $(".monthlyinputdate1").datepicker({
          uiLibrary: "bootstrap5",
          format: "dd-mm-yyyy",
          autoclose: true,
          todayHighlight: true,
          // minDate: date, //For disabling date from today
           onSelect: function (dateText, inst) {
             todate = dateText;
                    $(".monthlyinputdate").datepicker("option", "maxDate", todate);
                }
        });
      });
		</script>
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
			$(document).ready(function() {
        $('#dynamic-table').DataTable({
 
 dom: "<'row'<'col-sm-4'l><'col-sm-4 text-center'B><'col-sm-4'f>>tp",
       //table.row(':eq(0)', { page: 'current' }).select();
	    pageLength: 10,

		buttons: [
			<?php 
		if($_COOKIE['user_level']== 1) 
		{
		?>
			{extend: 'copy', title: 'Client Issue Articles',className: 'btn-sm',exportOptions: {
                    columns: [ 0, 1, 2, 3, 4, 5 ]
                }},
			{extend: 'csv',title: 'Client Issue Articles', className: 'btn-sm',exportOptions: {
                    columns: [ 0, 1, 2, 3, 4, 5 ]
                }},
			{extend: 'pdf', title: 'Client Issue Articles', className: 'btn-sm',exportOptions: {
                    columns: [ 0, 1, 2, 3, 4, 5 ]
                }},
			{extend: 'print', title: 'Client Issue Articles',className: 'btn-sm',exportOptions: {
                    columns: [ 0, 1, 2, 3, 4, 5 ]
                }}
		<?php 
		}
		?>
 ],

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

});

	$('#dynamic-table').on('click', 'button[data-delete]', function () {
     //$(".delete-article").on("click", function() {

				var del = $(this).attr('data-delete');
        swal({
		title: "Are you sure?",
	    text: "You will not be able to recover this record!",
         type: "warning",
		showCancelButton: true,
		confirmButtonColor: '#DD6B55',
		confirmButtonText: 'Proceed!',
		cancelButtonText: "No, cancel please!",
		closeOnConfirm: false,
		closeOnCancel: false
	},
	function(isConfirm){
    if (isConfirm){

      swal({title: "Deleting...", text: "Please wait", type: "info", showConfirmButton: false});
     // window.top.location="userinprogresslist?del="+del;
     window.top.location="userinprogresslist?del="+del;

    } else {
      swal("Cancelled", "Journal not Deleted", "error");
    }
	});
			});
});
			
function confirmDelete(delUrl) 
{
	if (confirm("Are you sure you want to delete")) 
	{
		document.location = delUrl;
	}
}

			$(document).ready(function(){
    $('#dep_id').on('change',function(){
        var dep_id = $(this).val();
        if(dep_id){
            $.ajax({
                type:'POST',
                url:'get_ruser.php',
                data:'dept_id='+dep_id,
                success:function(html){
                    $('#user_id').html(html);                    
                }
            }); 
        }else{
            $('#user_id').html('<option value="">Select User first</option>');
            
        }
    });

	
	});		
	


		</script>
</body>
</html>
