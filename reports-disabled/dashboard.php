<?php include "includes/includes.php";
session_start();
function differenceInHours($startdate, $enddate) {

    $difference = abs(strtotime($startdate) - strtotime($enddate));
    $hours = floor(($difference) / (60 * 60));
    $minuts = floor(($difference - $hours * 60 * 60) / 60);
    $seconds = floor(($difference - $hours * 60 * 60 - $minuts * 60));
    echo $hours . ":" . $minuts . ":" . $seconds;
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

    <!-- Data Table CSS- -->

    <!-- Page body starts -->
    <style>
      html {
        scrollbar-width: thin;
      }

      ::-webkit-scrollbar-thumb {
        width: 5px !important;
      }

      ::-webkit-scrollbar-track {
        color: #666;
      }

      body {
        font-family: "Open Sans";
        height: fit-content;
        scrollbar-width: 5px;
      }
      .wholeouter {
        height: fit-content;
      }

      .bar {
        width: 100%;
        height: 12px;
        background-color: #f0edeb;
        border-radius: 100px;
        display: flex;
        align-items: center;
      }

       .progress {
        width: 100%;
        width: 15px;
        height: 15px;
        background-color: rgb(250, 247, 247);
        border-radius: 100px;
        position: relative;
        overflow: visible;
		padding-left:15px;
      }
	  .cprogress {
         width: 100%;
        width: 15px;
        height: 15px;
        background-color: rgb(250, 247, 247);
        border-radius: 100px;
        position: relative;
        overflow: visible;
		padding-left:15px;
      }
	   .rprogress {
        width: 100%;
        width: 15px;
        height: 15px;
        background-color: rgb(250, 247, 247);
        border-radius: 100px;
        position: relative;
        overflow: visible;
		padding-left:15px;
      }
		.fprogress {
        width: 100%;
        width: 15px;
        height: 15px;
        background-color: rgb(250, 247, 247);
        border-radius: 100px;
        position: relative;
        overflow: visible;
		padding-left:15px;
      }

      .footer {
        margin-top: 0;
      }

      input {
        margin-bottom: 30px;
      }

      .showmorebtn {
        height: fit-content;
        width: clamp(60px, 8.333vw, 120px);
        border: 1px solid #ff8801;
        background: transparent;
        border-radius: 8px;
        font-weight: 700;
        font-size: 14px;
        color: #ff8801;
      }

      .searchbtn {
        height: 46px;
        width: clamp(60px, 8.333vw, 120px);
        border: 1px solid #ff8801;
        background: transparent;
        border-radius: 8px;
        font-weight: 700;
        font-size: 14px;
        color: #ff8801;
      }
      .statusnot {
        height: 30px;
        width: 98px;
        background-color: #ffdbdb;
        color: #f22626;
        font-size: 12px;
        font-weight: 600;
        border-radius: 24px;
      }

      .complete {
        height: 30px;
        width: 98px;
        background-color: rgba(87, 246, 103, 0.32);
        color: #058212;
        font-size: 12px;
        font-weight: 600;
        border-radius: 24px;
      }

      .Inprogress {
        height: 30px;
        width: 98px;
        background-color: rgba(255, 187, 86, 0.29);
        color: #ff9900;
        font-size: 12px;
        font-weight: 600;
        border-radius: 24px;
      }

      th,
      td {
        border: 1px solid #cbcbd8;
        padding: 14px;
      }
      .tabclr {
        color: #a8a9bb;
      }

      .analyticdiv {
        height: 132px;
        width: 48%;
        display: flex;
        justify-content: center;
        align-items: center;
        border-radius: 16px;
        border: 1px solid #cbcbd8;
        justify-content: space-around;
      }

	      .percestyl {
        margin-left: 30px;
      }

      @media (max-width: 768px) {
        .analyticdiv {
          width: 100%;
        }

        .percestyl {
          margin-left: 0px;
        }
      }					  
      .dashboardselect {
        padding: 14px 8px;
        border-radius: 8px;
        width: clamp(80px, 10vw, 146px);
        border-color: #cbcbd8;
        background: #fff;
        color: #666;
        font-weight: 600;
        accent-color: #f26722;
      }

      option {
        width: 100px !important;
      }

      .show {
        display: block;
      }

      .hide {
        display: none;
      }

      .dateselection {
        height: 46px;
        width: clamp(130px, 10vw, 162px);
        border-radius: 8px;
        color: #666;
        padding: 5px;
      }

      .analytictitle {
        font-size: 1.5em;
        font-weight: 600;
      }

      .analyticpara {
        font-size: 0.875em;
        color: #a8a9bb;
      }
      .group1 {
        scrollbar-width: thin;
        scrollbar-color: #666;
      }
      .group1::-webkit-scrollbar {
        width: 4px !important;
      }
      .group1::-webkit-scrollbar-thumb {
        background: #666;
      }
      .group2 {
        scrollbar-width: thin;
        scrollbar-color: #666;
      }
      .group2::-webkit-scrollbar {
        width: 4px !important;
      }
      .group2::-webkit-scrollbar-thumb {
        background: #666;
      }
	    

      .percentage {
        height: 28px;
        width: 28px;
        position: absolute;
        font-weight: bold;
		font-size:11px;
        color: aliceblue;
        right: 0px;
        -webkit-box-shadow: 0px 0px 2px 4px rgba(168, 168, 168, 1);
        -moz-box-shadow: 0px 0px 2px 4px rgba(168, 168, 168, 1);
        box-shadow: 0px 0px 2px 4px rgba(168, 168, 168, 1);
        background: #ffffffd0;
        color: #181818;
        z-index: 500;
        border-radius: 50%;
        top: -7px;
        display: flex;
        justify-content: center;
        align-items: center;
      }
	  .cpercentage {
        height: 28px;
        width: 28px;
        position: absolute;
        font-weight: bold;
		font-size:11px;
        color: aliceblue;
        right: 0px;
        -webkit-box-shadow: 0px 0px 2px 4px rgba(168, 168, 168, 1);
        -moz-box-shadow: 0px 0px 2px 4px rgba(168, 168, 168, 1);
        box-shadow: 0px 0px 2px 4px rgba(168, 168, 168, 1);
        background: #ffffffd0;
        color: #181818;
        z-index: 500;
        border-radius: 50%;
        top: -7px;
        display: flex;
        justify-content: center;
        align-items: center;
      }
	   .rpercentage {
        height: 28px;
        width: 28px;
        position: absolute;
        font-weight: bold;
		font-size:11px;
        color: aliceblue;
        right: 0px;
        -webkit-box-shadow: 0px 0px 2px 4px rgba(168, 168, 168, 1);
        -moz-box-shadow: 0px 0px 2px 4px rgba(168, 168, 168, 1);
        box-shadow: 0px 0px 2px 4px rgba(168, 168, 168, 1);
        background: #ffffffd0;
        color: #181818;
        z-index: 500;
        border-radius: 50%;
        top: -7px;
        display: flex;
        justify-content: center;
        align-items: center;
      }
	  .fpercentage {
        height: 28px;
        width: 28px;
        position: absolute;
        font-weight: bold;
		font-size:11px;
        color: aliceblue;
        right: 0px;
        -webkit-box-shadow: 0px 0px 2px 4px rgba(168, 168, 168, 1);
        -moz-box-shadow: 0px 0px 2px 4px rgba(168, 168, 168, 1);
        box-shadow: 0px 0px 2px 4px rgba(168, 168, 168, 1);
        background: #ffffffd0;
        color: #181818;
        z-index: 500;
        border-radius: 50%;
        top: -7px;
        display: flex;
        justify-content: center;
        align-items: center;
      }
	  
	        .chart-text {
        font-size: 0.5em;
        font-weight: 600;
        color: #222;
        -moz-transform: translateY(0.4em);
        -ms-transform: translateY(0.4em);
        -webkit-transform: translateY(0.4em);
        transform: translateY(0.4em);
      }

      .chart-number {
        -moz-transform: translateX(0em);
        -ms-transform: translateX(0em);
        -webkit-transform: translateX(0em);
        transform: translateX(0em);
      }
	  
	  table.dataTable tbody td:not(:last-child):after {
    content: "";
    height:15px;
    width:0.5px;
    border:none !important;
	position: absolute;
    right: 0;
    top:20px;
}
      
    </style>
  </head>
  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
	 
 
   
<!-- body section -->
      <div class="container-fluid">
        <div class="row justify-content-left">
          <div
            class="col-12 col-md-9 group1"
            style="height: 80vh; overflow-y: scroll"
          >
		   <div
              class="container-fluid d-flex align-items-center ps-4 pe-4"
              style="
                margin-top: 32px;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 10px;
              "
            >
              <h2>Dashboard</h2>
             
            </div>
			<form method="post" name="search_target" action="dashboard.php">
			<div class="container-fluid d-flex gap-2 ps-md-4 pe-md-4 gap-4"
              style="margin-top: 32px">
              <input class="dateselection" type="date" name="userfromDate" value="<?php if (isset($_POST['userfromDate'])){ echo $_POST['userfromDate'];}else{ echo date('d-m-Y'); }?>" />
              <input class="dateselection" type="date" name="userendDate" value="<?php if (isset($_POST['userendDate'])){ echo $_POST['userendDate'];}else{ echo date('d-m-Y'); }?>" />
              <button class="searchbtn" type="submit" name="user_but">Search</button>
            </div>
			</form>
           
			
          <div class="justify-content-center flex-column mt-4 monthly1outer p-4" style=" border-radius: 16px; padding: 24px; margin: 20px;height: fit-content;">
		  

            
			
			
              <div class="report mt-3">
              <h5 style="font-weight: 600">Worked Details</h5>

              <table class="table table-striped custtable table-primary" style="width: 100% !important; overflow:auto !important;text-wrap: nowrap !important;white-space: nowrap !important" id="myTable1">
               <thead>
                                                                    <tr>
                                                                        <th data-sortable="true" scope="col" class="thcolor" > Article</th>
                                                                        <th data-sortable="true" scope="col" class="thcolor" > Stage</th>
                                                                        <th data-sortable="true" scope="col" class="thcolor" > Start Date</th>
                                                                        <th data-sortable="true" scope="col" class="thcolor" > End Date</th>
																		<?php 
																		if($_COOKIE['department_id']==9)
																		{
																		?>
                                                                        <th data-sortable="true" scope="col" class="thcolor" > Fig Count</th>
																		<?php 
																		}
																		else
																		{
																		?>
                                                                        <th data-sortable="true" scope="col" class="thcolor" > Page Count's</th>
																		<?php 
																		}
																		?>
                                                                        <th data-sortable="true" scope="col" class="thcolor" > Time Spent in Hrs</th>                          
                                                                        <th data-sortable="true" scope="col" class="thcolor"> Status</th>
                                                                        <th data-sortable="true" scope="col" class="thcolor" > User Name</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php
                                                                    if (isset($_POST['user_but'])) {
                                                                        $user_fromDate = date('Y-m-d',strtotime($_POST['userfromDate']));
                                                                        $user_endDate = date('Y-m-d',strtotime($_POST['userendDate']));
                                                                         $trans_qry = "select t.process_user, t.stage,t.current_status,t.start_time,t.end_time,t.pages_completed, d.pub_id ,d.page_count,d.fig from inw_transactions as t, inw_inward_dtl as d where t.process_user = '" . $_COOKIE['user_id'] . "' AND t.project_id = d.id AND t.current_status NOT IN ('Take Over')AND((date(start_time) BETWEEN  '$user_fromDate' AND  '$user_endDate'))";
                                                                    } else {
                                                                        $user_fromDate = date('Y-m-d');
                                                                        $user_endDate = date('Y-m-d');
                                                                        $trans_qry = "select t.process_user, t.stage,t.current_status,t.start_time,t.end_time,t.pages_completed, d.pub_id ,d.page_count,d.fig from inw_transactions as t, inw_inward_dtl as d where t.process_user = '" . $_COOKIE['user_id'] . "' AND t.project_id = d.id AND t.current_status NOT IN ('Take Over') AND((date(start_time) BETWEEN  '$user_fromDate' AND  '$user_endDate'))";
                                                                    }
                                                                    //$trans_qry = "select t.process_user, t.stage,t.current_status,t.start_time,t.end_time,t.pages_completed, d.pub_id ,d.page_count from inw_transactions as t, inw_inward_dtl as d where t.process_user = '" . $_COOKIE['user_id'] . "' AND t.project_id = d.id AND t.current_status NOT IN ('Take Over')";
                                                                    $transsql = $db->query($trans_qry) or ( $db->error);
                                                                    //print_r($trans_qry);
                                                                    while ($row = $transsql->fetch_array()) {
                                                                        $process_user = $row['process_user'];
                                                                        $sql_user = "SELECT full_name FROM `users` WHERE id= $process_user";
                                                                        $usersql = $db->query($sql_user) or die($db->error);
                                                                        $fetch_row = $usersql->fetch_assoc();
                                                                        $user_name = $fetch_row['full_name'];
                                                                        ?>
                                                                        <tr>
                                                                            <td><?php echo $row['pub_id']; ?></td>
                                                                            <td><?php echo $row['stage']; ?></td>
                                                                            <td>
                                                                                <?php echo date("d-m-Y", strtotime($row['start_time'])); ?>
                                                                            </td>
                                                                            <td>
                                                                                <?php echo date("d-m-Y", strtotime($row['end_time'])); ?>
                                                                            </td>
                                                                            <td><?php  if($_COOKIE['department_id']==9) { echo $row['fig'];}else{ echo $row['page_count'];}  ?></td>
                                                                            <td>
                                                                                <?php if (!is_null($row['end_time'])) { ?>
                                                                                    <i class="ace-icon fa fa-clock-o bigger-110"></i>
                                                                                    <?php echo differenceInHours($row['start_time'], $row['end_time']); ?>
                                                                                <?php } ?>
                                                                            </td>
                                                                            <td class="hidden-480">
                                                                                <?php if (strtolower($row['current_status']) == 'inprogress') { ?>
                                                                                    <span class="label label-warning"><?php echo $row['current_status']; ?></span>
                                                                                <?php } else if (strtolower($row['current_status']) == 'completed') { ?>
                                                                                    <span class="label label-info"><?php echo $row['current_status']; ?></span>
                                                                                <?php } ?>
                                                                            </td>

                                                                            <td><?php echo $user_name; ?></td>

                                                                        </tr>
                                                                    <?php } ?>
                                                                </tbody>
              </table>
            </div>
          </div>
		 
          </div>
		 
         <div
            class="col-3 d-none d-md-flex p-4 group2"
            style="
              flex-direction: column;
              background-color: rgba(255, 245, 235, 0.8);
              height: 80vh;
              overflow-y: auto;
            "
          >
            <h5>Team Article status</h5>
           <div
                class="row d-flex mt-4"
                style="border-bottom: 1px solid #d8d2cb"
              >
              <p class="col-8 pt-2 pb-2">Pub ID</p>
              <p class="col-4 pt-2 pb-2">Status</p>
            </div>
			<?php 
			if($_COOKIE['department_id']==9)
			{
			$sql_allocated=mysqli_query($db,"SELECT wd.department_id,wd.assign_department_id,c.cust_name,journal_title,fig_complete_status,pub_id,recv_dt,file_status,due_dt,ce_pe_due_date,target_due_dt,highpriority,wd.created_dt,wd.issueorgroup_folder,fig,tab,status,approved_by,wd.id,j.j_code,j.jt_id,j.j_division,manuscript_count,stage,work_dir,r.received_date,r.due_date,r.correction_pages,aq_status,wd.id as prj_id FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.approved_by > 0 and wd.cust_id = c.id and wd.j_id = j.j_id AND (wd.cust_id = '".$_COOKIE['cust_id']."' OR FIND_IN_SET(wd.cust_id,'".$_COOKIE['ocust_id']."')) AND (wd.fig_complete_status = 0 OR department_id = '".$_COOKIE['department_id']."') AND (wd.assign_department_id = '9' OR fig > 0) AND wd.status NOT IN ('Delivery','Client Review','Completed','Query','Hold')  and wd.v_issue_id <= 0 and test_article!=1   ORDER BY  `due_dt` asc limit 10");
			}
			else
			{
			$sql_allocated=mysqli_query($db,"SELECT wd.department_id,wd.assign_department_id,c.cust_name,journal_title,fig_complete_status,pub_id,recv_dt,file_status,due_dt,ce_pe_due_date,target_due_dt,highpriority,wd.created_dt,wd.issueorgroup_folder,fig,tab,status,approved_by,wd.id,j.j_code,j.jt_id,j.j_division,manuscript_count,stage,work_dir,r.received_date,r.due_date,r.correction_pages,aq_status,wd.id as prj_id FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.approved_by > 0 and wd.cust_id = c.id and wd.j_id = j.j_id AND (wd.cust_id = '".$_COOKIE['cust_id']."' OR FIND_IN_SET(wd.cust_id,'".$_COOKIE['ocust_id']."')) AND (wd.fig_complete_status = 0 OR department_id = '".$_COOKIE['department_id']."')  AND wd.status NOT IN ('Delivery','Client Review','Completed','Query','Hold')  and wd.v_issue_id <= 0 and test_article!=1   ORDER BY  `due_dt` asc limit 10");	
			}
			while($res_allocated=mysqli_fetch_array($sql_allocated))
			{
			if($_COOKIE['department_id']==9)
			{

			$progress_status=mysqli_fetch_array(mysqli_query($db,"select current_status from inw_transactions where project_id='".$res_allocated['prj_id']."' and dept='".$_COOKIE['department_id']."' order by id desc limit 1"));
			}
			else
			{
			$progress_status=mysqli_fetch_array(mysqli_query($db,"select current_status from inw_transactions where project_id='".$res_allocated['prj_id']."'"));
			}
			?>
            <div
                class="row d-flex pt-2 pb-2 align-items-center"
                style="border-bottom: 1px solid #d8d2cb"
              >
              <p class="col-8 pt-2 pb-2"><?php echo $res_allocated['pub_id'];?></p>
              <p class="col-4 pt-2 pb-2 <?php if($progress_status['current_status']=="Inprogress"){?>Inprogress<?php }elseif($progress_status['current_status']=="Completed"){?>complete<?php }else{?>statusnot<?php }?> d-flex align-items-center justify-content-center">
                <?php if($progress_status['current_status']=="Inprogress"){?>In Progress<?php }elseif($progress_status['current_status']=="Completed"){?>Completed<?php }else{?>Not Started<?php }?>
              </p>
            </div>
          
			<?php
			}
			?>
			
			 <div style="display: flex; margin-top: 15px; justify-content: end">
                <a href="currentwork" target="_blank">Show More</a>
              </div>
          </div>
        </div>
      </div>
     


  
  
    <!-- Footer Section -->
     <?php echo footer(); ?>




<!-- Footer Section -->
<?php echo scripts(); ?>	



   
     <!-- Toogle show hide functionality -->
    <script>
      const showbtn = $(".showmorebtn");
      const report = $(".report");
      var state = "false";

      showbtn.on("click", function () {
        report[0].classList.toggle("hide");
        state = !state;
        if (state) {
          showbtn[0].innerHTML = "Show More";
        } else {
          showbtn[0].innerHTML = "Show Less";
        }
      });
    </script>
	
	
	
    <!-- Date picker functionality -->
    <script>
      $(function () {
        // var datesForDisable = ["2017-10-14", "2017-10-15", "2017-10-16"];
        var date = new Date();
        date.setDate(date.getDate() - 1);
        var fromdate;
        $(".monthlyinputdate").datepicker({
          uiLibrary: "bootstrap5",

          format: "dd-mm-yyyy",
          autoclose: true,
          weekStart: 1,
          todayHighlight: true,
          // minDate: date,
          onSelect: function (dateText, inst) {
            fromdate = dateText;
            console.log(fromdate);
          },
        });

        $(".monthlyinputdate1").datepicker({
          uiLibrary: "bootstrap5",
          iconsLibrary: "materialicons",
          format: "dd-mm-yyyy",
          autoclose: true,
          weekStart: 1,
          todayHighlight: true,
          // minDate: new Date(), //For disabling date from today
        });
      });
	  
	   // DataTable
                var table = $('#myTable1').DataTable({
                    searching: true,
                    pageLength:5,
					lengthMenu : [[5,10, 25, 50, 100, 100000], [5,10, 25, 50, 100, "All"]],  
				});
    </script>

   
  </body>
</html>

