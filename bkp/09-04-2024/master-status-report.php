<?php include "includes/includes.php";
   include "includes/paginate.php";
   
   session_start();
   ?>
<?php
   extract($_POST);
   $search = "";
   $search1 = "";
   //echo $_REQUEST['mfrom_dt'];
   if (isset($_SESSION["inpt_status_qry"]) || $_SESSION["inpt_status_qry"] == "") {
       $_SESSION["inpt_status_qry"] =
           " AND wd.status NOT IN ('Delivery','Client Review','Completed','Query','Hold') ";
   }
   if ($_REQUEST["cust_id"] != "" && $_REQUEST["cust_id"] != "all") {
       $ccc .= 'and FIND_IN_SET(wd.cust_id,"' . $_REQUEST["cust_id"] . '") ';
   }
   if ($_REQUEST["cust_id"] != "" && $_REQUEST["cust_id"] == "all") {
       $ccc .= "";
   }
   if ($_REQUEST["j_id"] != "" && $_REQUEST["j_id"] != "all") {
       $ccc .= 'and FIND_IN_SET(j.j_id,"' . $_REQUEST["j_id"] . '") ';
   }
   if ($_REQUEST["j_id"] != "" && $_REQUEST["j_id"] == "all") {
       $ccc .= "";
   }
   if ($_REQUEST["stage_id"] != "" && $_REQUEST["stage_id"] == "Pending") {
       $search2 .= "and wd.file_status='Pending' ";
   } elseif (
       $_REQUEST["stage_id"] != "" &&
       $_REQUEST["stage_id"] == "Need Clarification"
   ) {
       $search2 .= "and wd.file_status='Need Clarification' ";
   } elseif ($_REQUEST["stage_id"] != "" && $_REQUEST["stage_id"] != "all") {
       $search2 .= "and wd.stage like '%" . $_REQUEST["stage_id"] . "%' ";
   } elseif ($_REQUEST["stage_id"] != "" && $_REQUEST["stage_id"] == "all") {
       $search2 .= "";
   }
   if ($_REQUEST["dep_id"] != "" && $_REQUEST["dep_id"] != "all") {
       $ccc .= "and department_id= '" . $_REQUEST["dep_id"] . "' ";
   }
   if ($_REQUEST["dep_id"] != "" && $_REQUEST["dep_id"] == "all") {
       $ccc .= "";
   }
   if ($_REQUEST["dp_platform"] != "" && $_REQUEST["dp_platform"] != "all") {
       echo $ccc .= "and j.j_platform='" . $_REQUEST["dp_platform"] . "' ";
   }
   if ($_REQUEST["dp_platform"] != "" && $_REQUEST["dp_platform"] == "all") {
       $ccc .= "";
   }
   $_SESSION["inpt_status_qry"] =
       " AND wd.status NOT IN ('Delivery','Client Review','Completed','Query','Hold') ";
  /* if ($_REQUEST["inpt_status"] != "") {
       $_SESSION["inpt_status"] = $_POST["inpt_status"];
       if ($_SESSION["inpt_status"] != "") {
           if (
               in_array("Query", $_SESSION["inpt_status"]) &&
               in_array("Hold", $_SESSION["inpt_status"])
           ) {
               $_SESSION["inpt_status_qry"] =
                   " AND wd.status NOT IN ('Delivery','Client Review','Completed') ";
           } elseif (in_array("Query", $_SESSION["inpt_status"])) {
               $_SESSION["inpt_status_qry"] =
                   " AND wd.status NOT IN ('Delivery','Client Review','Completed','Hold') ";
           } elseif (in_array("Hold", $_SESSION["inpt_status"])) {
               $_SESSION["inpt_status_qry"] =
                   " AND wd.status NOT IN ('Delivery','Client Review','Completed','Query') ";
           }
       }
   }
   */
   
   if ($_REQUEST["radio"] == "ce") {
       if ($_REQUEST["cfrom_dt"] != "") {
           $frm = date("Y-m-d", strtotime($_REQUEST["cfrom_dt"]));
           $to = date("Y-m-d", strtotime($_REQUEST["cto_dt"]));
           $search .=
               "and DATE_FORMAT(sent_date,'%Y-%m-%d') >='" .
               $frm .
               "' AND DATE_FORMAT(sent_date,'%Y-%m-%d')<='" .
               $to .
               "'";
       }
   }
   if ($_REQUEST["radio"] == "dp" || $_REQUEST["radio"] == "cr") {
       if ($_REQUEST["from_dt"] != "" && $_REQUEST["date_wise"] == "d") {
           $frm = date("Y-m-d", strtotime($_REQUEST["from_dt"]));
           $to = date("Y-m-d", strtotime($_REQUEST["to_dt"]));
           $search .=
               "and DATE_FORMAT(wd.sent_date,'%Y-%m-%d') >='" .
               $frm .
               "' AND DATE_FORMAT(wd.sent_date,'%Y-%m-%d')<='" .
               $to .
               "'";
       }
       if ($_REQUEST["date_wise"] == "m") {
           //echo $_REQUEST['mfrom_dt'];
           $frm = $_REQUEST["mfrom_dt"];
           $search .= "and DATE_FORMAT(wd.sent_date,'%m-%Y')='" . $frm . "'";
       }
       if ($_REQUEST["date_wise"] == "y") {
           $frm = $_REQUEST["yfrom_dt"];
           $to = $_REQUEST["yto_dt"];
           $search .= "and DATE_FORMAT(wd.sent_date,'%Y')='" . $frm . "'";
       }
       if (
           $_REQUEST["date_wise"] != "y" &&
           $_REQUEST["date_wise"] != "m" &&
           $_REQUEST["date_wise"] != "d"
       ) {
           $search3 .= "AND wd.sent_date >='2020-03-01'";
           $search4 .= "AND wd.sent_date >='2020-03-01'";
       }
       if ($_REQUEST["dp_platform"] != "" && $_REQUEST["dp_platform"] != "all") {
           $search .= "and j.j_platform='" . $_REQUEST["dp_platform"] . "' ";
       }
       if ($_REQUEST["dp_platform"] != "" && $_REQUEST["dp_platform"] == "all") {
           $search .= "";
       }
   } elseif ($_REQUEST["radio"] == "sfd") {
       if ($_REQUEST["sdf_dt"] != "") {
           $frm = date("Y-m-d", strtotime($_REQUEST["sdf_dt"]));
           $search .=
               "and DATE_FORMAT(wd.target_due_dt,'%Y-%m-%d')='" . $frm . "'";
       }
       if ($_REQUEST["dp_platform"] != "" && $_REQUEST["dp_platform"] != "all") {
           $search .= "and j.j_platform='" . $_REQUEST["dp_platform"] . "' ";
       }
       if ($_REQUEST["dp_platform"] != "" && $_REQUEST["dp_platform"] == "all") {
           $search .= "";
       }
   } else {
       if ($_REQUEST["from_dt"] != "" && $_REQUEST["date_wise"] == "d") {
           $frm = date("Y-m-d", strtotime($_REQUEST["from_dt"])) . " 00:00:00";
           $to = date("Y-m-d", strtotime($_REQUEST["to_dt"])) . " 23:59:59";
           $search .=
               "and wd.recv_dt >='" . $frm . "' AND wd.recv_dt<='" . $to . "'"; //$search1.="and start_time >='".$frm."' AND start_time<='".$to."'";
           $search3 .=
               "and ((wd.recv_dt >='" .
               $frm .
               "' AND wd.recv_dt<='" .
               $to .
               "') or (r.received_date >='" .
               $frm .
               "' AND r.received_date<='" .
               $to .
               "'))";
           $search4 .=
               "and ((wd.due_dt >='" .
               $frm .
               "' AND wd.due_dt<='" .
               $to .
               "') or (r.due_date >='" .
               $frm .
               "' AND r.due_date<='" .
               $to .
               "'))";
           $search_cd .=
               "and (created_dt >='" . $frm . "' AND created_dt<='" . $to . "')";
       }
       if ($_REQUEST["date_wise"] == "m") {
           //echo $_REQUEST['mfrom_dt'];
           $frm = $_REQUEST["mfrom_dt"];
           $search .= "and DATE_FORMAT(wd.recv_dt,'%m-%Y')='" . $frm . "'"; //$search1.="and DATE_FORMAT(start_time,'%m-%Y')='".$frm."'"; //$search3.="and DATE_FORMAT(r.received_date,'%m-%Y')='".$frm."'";
           $search3 .=
               "and ((DATE_FORMAT(wd.recv_dt,'%m-%Y')='" .
               $frm .
               "') or (DATE_FORMAT(r.received_date,'%m-%Y')='" .
               $frm .
               "'))";
           $search4 .=
               "and ((DATE_FORMAT(wd.recv_dt,'%m-%Y')='" .
               $frm .
               "') or (DATE_FORMAT(r.due_date,'%m-%Y')='" .
               $frm .
               "'))";
           $search_cm .= "and (DATE_FORMAT(created_dt,'%m-%Y')='" . $frm . "')";
       }
       if ($_REQUEST["date_wise"] == "y") {
           $frm = $_REQUEST["yfrom_dt"];
           $to = $_REQUEST["yto_dt"];
           $search .= "and DATE_FORMAT(recv_dt,'%Y')='" . $frm . "'";
           $search_cy .= "and (DATE_FORMAT(created_dt,'%Y')='" . $frm . "')"; //$search1.="and DATE_FORMAT(start_time,'%Y')='".$frm."'"; //$search3.="and DATE_FORMAT(r.received_date,'%Y')='".$frm."'";
           $search3 .=
               "and ((DATE_FORMAT(wd.recv_dt,'%Y')='" .
               $frm .
               "') or (DATE_FORMAT(r.received_date,'%Y')='" .
               $frm .
               "'))";
           $search4 .=
               "and ((DATE_FORMAT(wd.due_dt,'%Y')='" .
               $frm .
               "') or (DATE_FORMAT(r.due_date,'%Y')='" .
               $frm .
               "'))";
       }
       if (
           $_REQUEST["date_wise"] != "y" &&
           $_REQUEST["date_wise"] != "m" &&
           $_REQUEST["date_wise"] != "d"
       ) {
           $search3 .= "AND wd.created_dt >='2020-03-01'";
           $search4 .= "AND wd.created_dt >='2020-03-01'";
       }
   }
   
   /* if($_REQUEST['radio']=='cr')
   {
   if($_REQUEST['date_wise']!= "y" && $_REQUEST['date_wise']!= "m" && $_REQUEST['date_wise']!= "d")
   {
   $searchfp.="AND recv_dt >='2020-03-01'";
   $searchr.="AND received_date >='2020-03-01'";
   }
   } */
   ?>
<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="UTF-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <!-- CSS styles -->
      <?php include "includes/title.php"; ?>
      <?php echo styles(); ?>	
      <style>
         /* Pagination styles */
         .pagination {
         display: inline-block;
         padding-left: 0;
         margin: 20px 0;
         border-radius: 4px;
         float: right; /* Align pagination to the right */
         }
         .pagination > li {
         display: inline;
         }
         .pagination > li > a,
         .pagination > li > span {
         position: relative;
         float: left;
         padding: 6px 12px;
         margin-left: -1px;
         line-height: 1.42857143;
         color: #F26722; /* Orange color */
         text-decoration: none;
         background-color: #fff;
         border: 1px solid #ddd;
         }
         .pagination > li:first-child > a,
         .pagination > li:first-child > span {
         margin-left: 0;
         border-bottom-left-radius: 4px;
         border-top-left-radius: 4px;
         }
         .pagination > li:last-child > a,
         .pagination > li:last-child > span {
         border-bottom-right-radius: 4px;
         border-top-right-radius: 4px;
         }
         .pagination > li > a:hover,
         .pagination > li > span:hover,
         .pagination > li > a:focus,
         .pagination > li > span:focus {
         background-color: #eee;
         }
         .pagination > .active > a,
         .pagination > .active > span,
         .pagination > .active > a:hover,
         .pagination > .active > span:hover,
         .pagination > .active > a:focus,
         .pagination > .active > span:focus {
         z-index: 2;
         color: #fff;
         background-color: #F26722; /* Orange color */
         border-color: #F26722; /* Orange color */
         cursor: default;
         }
         /* Disabled styles */
         .pagination > .disabled > span,
         .pagination > .disabled > a,
         .pagination > .disabled > a:hover,
         .pagination > .disabled > a:focus{}
         
         /* Floating date selector */
         .clearfix {
            width: 100%;
            display: flex;
            justify-content: space-evenly;
        }


      </style>
      <!-- Data Table CSS- -->
   </head>
   <body>
      <div class="wholeouter">
         <!-- Header logo section -->
         <?php include "includes/top-header.php"; ?>
         <!-- Header logo section -->
         <!-- report section body starts -->
         <div class="containter-fluid">
            <div class="container-fluid mt-4">
               <p class="reportsectionheading ms-4">Master Status Report</p>
            </div>
            <!-- <div class="container-fluid mt-4">
               <div class="row ms-4" style="height: fit-content">
                 <div class="col-1 reportlink" style="border-radius: 8px 0 0 8px">
                   <a href="" type="button">WIP</a>
                 </div>
                 <div class="col-1 reportlink">
                   <a href="" type="button">Schedules for the day</a>
                 </div>
                 <div class="col-1 reportlink">
                   <a href="" type="button">Delivery Performance</a>
                 </div>
                 <div class="col-1 reportlink">
                   <a href="" type="button">Consolidated-report</a>
                 </div>
                 <div class="col-1 reportlink" style="border-radius: 0 8px 8px 0">
                   <a href="" type="button">CE/PE-Report</a>
                 </div>
               </div>
               </div>-->
            <div class="container-fluid mt-4">
               <form class="form-horizontals"  id="validation-form" method="post" autocomplete="off" >
                  <input type="hidden" id="radio_check" name="radio_check" value="<?php if (
                     $_REQUEST[
                         "radio"
                     ] !=
                     ""
                     ) {
                     echo $_REQUEST[
                         "radio"
                     ];
                     } ?>">
                  <?php if (
                     $_REQUEST[
                         "radio"
                     ] !=
                     ""
                     ) {
                     if (
                         $_REQUEST[
                             "radio"
                         ] ==
                         "wip"
                     ) {
                         $wip_chk =
                             'checked="checked"';
                     } else {
                         $wip_chk =
                             "";
                     }
                     if (
                         $_REQUEST[
                             "radio"
                         ] ==
                         "sfd"
                     ) {
                         $sfd_chk =
                             'checked="checked"';
                     } else {
                         $sfd_chk =
                             "";
                     }
                     if (
                         $_REQUEST[
                             "radio"
                         ] ==
                         "dp"
                     ) {
                         $dp_chk =
                             'checked="checked"';
                     } else {
                         $dp_chk =
                             "";
                     }
                     if (
                         $_REQUEST[
                             "radio"
                         ] ==
                         "dr"
                     ) {
                         $dr_chk =
                             'checked="checked"';
                     } else {
                         $dr_chk =
                             "";
                     }
                     if (
                         $_REQUEST[
                             "radio"
                         ] ==
                         "cr"
                     ) {
                         $cr_chk =
                             'checked="checked"';
                     } else {
                         $cr_chk =
                             "";
                     }
                     if (
                         $_REQUEST[
                             "radio"
                         ] ==
                         "ce"
                     ) {
                         $cr_chk =
                             'checked="checked"';
                     } else {
                         $cr_chk =
                             "";
                     }
                     } ?>
                  <div class="row ms-4" style="height: fit-content">
                     <div id="wip_styl" class="col-1 reportlink  <?php if ($_REQUEST["radio"] == "wip") { ?>reportbackgnd<?php }?>" style="border-radius: 8px 0 0 8px">
                        <label class="radio-container">
                           WIP
                           <input type="radio" class="radio" <?php echo $wip_chk; ?> value="wip" name="radio" id="wip">
                           <div class="checkmark"></div>
                        </label>
                     </div>
                     <div id="sfd_styl" class="col-1 reportlink <?php if ($_REQUEST["radio"] == "sfd") { ?>reportbackgnd<?php }?>">
                        <label class="radio-container">
                           Schedules for the Day
                           <input type="radio" class="radio" name="radio" <?php echo $sfd_chk; ?> id="schedules" value="sfd">
                           <div class="checkmark"></div>
                        </label>
                     </div>
                     <?php if ($_COOKIE["user_level"] == 1 || $_COOKIE["user_level"] == 5) { ?>
                     <div id="dp_styl" class="col-1 reportlink <?php if ($_REQUEST["radio"] == "dp") { ?>reportbackgnd<?php }?>">
                        <label class="radio-container">
                           Delivery Performance
                           <input type="radio" class="radio" name="radio" <?php echo $dp_chk; ?> id="delivery" value="dp">
                           <div class="checkmark"></div>
                        </label>
                     </div>
                     <!-- <label class="radio-container">Detailed Report
                        <input type="radio" class="radio" name="radio" <?php echo $dr_chk; ?> id="detail-report" value="dr">
                        <div class="checkmark"></div>
                        </label>-->
                     <div id="cr_styl" class="col-1 reportlink <?php if ($_REQUEST["radio"] == "cr") { ?>reportbackgnd<?php }?>">
                        <label class="radio-container">
                           Consolidated-report
                           <input type="radio" class="radio" name="radio" <?php echo $cr_chk; ?> id="consolidated-report" value="cr">
                           <div class="checkmark"></div>
                        </label>
                     </div>
                     <div id="cepe_styl" class="col-1 reportlink <?php if ($_REQUEST["radio"] == "cepe") { ?>reportbackgnd<?php }?>">
                        <label class="radio-container">
                           CE/PE -report
                           <input type="radio" class="radio" name="radio" <?php echo $cr_chk; ?> id="cepe" value="ce">
                           <div class="checkmark"></div>
                        </label>
                     </div>
                  </div>
                  <?php } ?>
            
            <div class="mt-4 mb-5 formouter">
            <div class="selectdiv row p-4">
            <div class="col-12 col-sm-6 col-md-3 col-lg-2" id="customer_select">
            <label class="mb-2">Choose Customer</label>
            <select class="form-select customer_drop" data-placeholder="Select Customer" name="cust_id" id="cust_id">
            <option value="all" <?php if (
               $_REQUEST["cust_id"] == "all"
               ) { ?> selected <?php } ?> >Select All</option>
            <?php
               if ($_COOKIE["user_level"] == "3") {
                   $sql_customers = $auth->showCustomerlistbyuser(
                       $_COOKIE["cust_id"],
                       $_COOKIE["ocust_id"]
                   );
               } else {
                   $sql_customers = $auth->showCustomerlist();
               }
               foreach ($sql_customers as $res_customers) {
                   if (isset($_REQUEST["cust_id"])) { ?>
            <option <?php if (
               $_REQUEST["cust_id"] == $res_customers["id"]
               ) { ?> selected <?php } ?> value="<?php echo $res_customers[
               "id"
               ]; ?>"style="font-weight:500;"><?php echo $res_customers[
               "cust_name"
               ]; ?> </option>
            <?php } else { ?>
            <option value="<?php echo $res_customers[
               "id"
               ]; ?>"style="font-weight:500;"><?php echo $res_customers[
               "cust_name"
               ]; ?> </option>
            <?php }
               }
               ?>
            </select>
            </div>
            <div class="col-12 col-sm-6 col-md-3 col-lg-2" id="journal_select">
            <label class="mb-2">Choose Journal</label>
            <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_id" id="j_id" >
            <option value="all">Select All</option>
            <?php
               if ($_COOKIE["user_level"] == "3") {
                   $sql_journals = $auth->showJournallistbyuser(
                       $_COOKIE["cust_id"],
                       $_COOKIE["ocust_id"]
                   );
               } else {
                   $sql_journals = $auth->showJournallist("0", 2);
               }
               foreach ($sql_journals as $res_journals) {
                   if (isset($_REQUEST["j_id"])) { ?>
            <optionvalue="<?php echo $res_journals[
               "j_id"
               ]; ?>"style="font-weight:500;"><?php echo $res_journals["j_code"]; ?> </option>
            <?php } else { ?>
            <option value="<?php echo $res_journals[
               "j_id"
               ]; ?>"style="font-weight:500;"><?php echo $res_journals[
               "j_code"
               ]; ?> </option>
            <?php }
               }
               ?>
            </select>
            </div>
            <div class="col-12 col-sm-6 col-md-3 col-lg-2" id="stage_cons">
            <label class="mb-2">Choose Stage</label>
            <select class="form-select stage_drop" data-placeholder="Select Stage" name="stage_id" id="stage_id">
            <option value="all">Select All</option>
            <option <?php if (
               $_REQUEST["stage_id"] == "all"
               ) { ?> selected <?php } ?> value="all">-All-</option>
            <option value="FP" <?php if (
               $_REQUEST[
                   "stage_id"
               ] ==
               "FP"
               ) { ?> selected <?php } ?> >-First Proof-</option>
            <option value="REV" <?php if (
               $_REQUEST[
                   "stage_id"
               ] ==
               "REV"
               ) { ?> selected <?php } ?> >-All Revises-</option>
            <option value="REV1" <?php if (
               $_REQUEST[
                   "stage_id"
               ] ==
               "REV1"
               ) { ?> selected <?php } ?> >-REV1-</option>
            <option value="REV2" <?php if (
               $_REQUEST[
                   "stage_id"
               ] ==
               "REV2"
               ) { ?> selected <?php } ?> >-REV2-</option>
            <option value="REV3" <?php if (
               $_REQUEST[
                   "stage_id"
               ] ==
               "REV3"
               ) { ?> selected <?php } ?> >-REV3-</option>
            <option value="REV4" <?php if (
               $_REQUEST[
                   "stage_id"
               ] ==
               "REV4"
               ) { ?> selected <?php } ?> >-REV4-</option>
            <option value="REV5" <?php if (
               $_REQUEST[
                   "stage_id"
               ] ==
               "REV5"
               ) { ?> selected <?php } ?> >-REV5-</option>
            <option value="FIN" <?php if (
               $_REQUEST[
                   "stage_id"
               ] ==
               "FIN"
               ) { ?> selected <?php } ?> >-Final-</option>
            <option value="ISSCOR" <?php if (
               $_REQUEST[
                   "stage_id"
               ] ==
               "ISSCOR"
               ) { ?> selected <?php } ?> >-Issue-</option>
            <option value="Sample" <?php if (
               $_REQUEST[
                   "stage_id"
               ] ==
               "Sample"
               ) { ?>selected<?php } ?> >-Sample-</option>
            <option value="Pending" <?php if (
               $_REQUEST["stage_id"] == "Pending"
               ) { ?>selected<?php } ?> >-Pending-</option>
            <option value="Need Clarification" <?php if (
               $_REQUEST[
                   "stage_id"
               ] ==
               "Need Clarification"
               ) { ?>selected<?php } ?> >-Need Clarification-</option>
            </select>
            </div>
            <div class="col-12 col-sm-6 col-md-3 col-lg-2" id="dept_wip">
            <label class="mb-2">Choose Department</label>
            <select class="form-select department_drop" data-placeholder="Select Department" name="dep_id" id="dep_id" >
            <option value="all">Select All</option>
            <?php
               $sql_department = $auth->getAlldepartment("0", 2);
               foreach ($sql_department as $res_department) {
                   if (isset($_REQUEST["dept"])) { ?>
            <option  value="<?php echo $res_department[
               "dept_code"
               ]; ?>"style="font-weight:500;"><?php echo $res_department[
               "dept_name"
               ]; ?> </option>
            <?php } else { ?>
            <option value="<?php echo $res_department[
               "dept_code"
               ]; ?>"style="font-weight:500;"><?php echo $res_department[
               "dept_name"
               ]; ?></option>
            <?php }
               }
               ?>
            </select>
            </div>
            <div class="col-12 col-sm-6 col-md-3 col-lg-2" id="platform_dp" style="display:none">
            <label class="mb-2">Choose Platform</label>
            <select class="form-select platform_drop" aria-label="search-input"  name="dp_platform" id="dp_platform">
            <option value="all">Select All</option>
            <?php foreach ($platform_journal_array as $id => $val) { ?>
            <option <?php if (
               $_REQUEST["dp_platform"] == $val
               ) { ?>selected<?php } ?> value="<?php echo $val; ?>"><?php echo $val; ?></option>
            <?php } ?>
            </select>
            </div>
            <div class="col-12 col-lg-4 form-group" id="date_sdf" style="display:none;">
            <label class="control-label col-xs-12 col-sm-3 no-padding-right mb-2" for="name">Date:</label>
            <div class="col-xs-5  col-sm-6">
            <div class="input-group">
            <input type="text" id="sdf_dt" name="sdf_dt" class="col-xs-12 col-sm-12 date-picker form-control " value="<?php if (
               $_REQUEST[
                   "sdf_dt"
               ] !=
               ""
               ) {
               echo $_REQUEST[
                   "sdf_dt"
               ];
               } else {
               echo date(
                   "d-m-Y"
               );
               } ?>" />
            <span class="input-group-addon"> <i class="ace-icon fa fa-calendar bigger-110"></i> </span> </div>
            </div>
            </div>
            <?php
               $yds = date("d-m-Y", strtotime("-1 days"));
               $tyds = date("Y-m-d");
               $tds = date("d-m-Y", strtotime("+1 days"));
               ?>
            <div class="col-12 col-sm-6 col-md-3 col-lg-4 d-flex gap-4" >
            <div class="form-group" id="cepe_fsdf" style="display:none;">
            <label class="control-label col-xs-12 col-sm-3 no-padding-right" style="width: 100%; color: #222" for="name">From Date:</label>
            <div class="col-12 col-md-12 d-flex">
            <div class="input-group">
            <input type="text" id="cfrom_dt" name="cfrom_dt" class="col-xs-12 col-sm-12 date-picker form-control" value="<?php if (
               $_REQUEST[
                   "cfrom_dt"
               ] !=
               ""
               ) {
               echo $_REQUEST[
                   "cfrom_dt"
               ];
               } else {
               echo $yds;
               } ?>" />
            <span class="input-group-addon"> <i class="ace-icon fa fa-calendar bigger-110"></i> </span> </div>
            </div>
            </div>
            <div class="form-group" id="cepe_tsdf" style="display:none;">
            <label class="control-label col-xs-12 col-sm-3 no-padding-right" style="width: 100%; color: #222" for="name">To Date:</label>
            <div class="col-12 col-md-12 d-flex">
            <div class="input-group">
            <input type="text" id="cto_dt" name="cto_dt" class="col-xs-12 col-sm-12 date-picker form-control" value="<?php if (
               $_REQUEST[
                   "cto_dt"
               ] !=
               ""
               ) {
               echo $_REQUEST[
                   "cto_dt"
               ];
               } else {
               echo $tds;
               } ?>" />
            <span class="input-group-addon"> <i class="ace-icon fa fa-calendar bigger-110"></i> </span> </div>
            </div>
            </div>
            </div>
            <div class="col-12 mt-4" id="status_wip" style="display:none;">
            <div class="row" >
            <div class="col-2" style="width: fit-content">
            <p>Choose Status :</p>
            </div>
            <div class="col-2 d-flex" style="width: fit-content">
            <input type="checkbox" value="Query"  id="inpt_status" name="inpt_status[]"  />
            <p class="ms-3">Query</p>
            </div>
            <div class="col-1 d-flex" style="width: fit-content">
            <input type="checkbox" id="inpt_status" name="inpt_status[]" value="Hold"  />
            <p class="ms-3">Hold</p>
            </div>
            </div>
            </div>
            <!-- </div> -->
            <!-- <div class="col-lg-1 "></div> -->
            <div class="col-lg-4 col-6 floatingselector" id="wip_nodate">
            <?php if ($_COOKIE["user_level"] == 1 || _COOKIE["user_level"] == 5) { ?>
            <div class="form-group floatingselectorinner">
            <label class="control-label1 col-xs-12 col-sm-3 no-padding-right p-4" for="email">Duration</label>
            <div class="col-xs-12 col-sm-9 floatingdate d-flex" style="margin-top: 8px;">
            <div class="clearfix">
            <label class="radio-container">Day</label>
            <input type="radio" class="dateselectorinput"   name="date_wise" <?php if (
               $_REQUEST[
                   "date_wise"
               ] ==
               "d"
               ) { ?> checked <?php } ?> id="daywise" value="d">
            <span class="checkmark"></span>
            
            <label class="radio-container">Month
            <input type="radio"  class="dateselectorinput" name="date_wise" <?php if (
               $_REQUEST[
                   "date_wise"
               ] ==
               "m"
               ) { ?> checked <?php } ?> id="monthwise" value="m">
            <span class="checkmark"></span>
            </label>
            <label class="radio-container">Year
            <input type="radio"  class="dateselectorinput" name="date_wise" <?php if (
               $_REQUEST[
                   "date_wise"
               ] ==
               "y"
               ) { ?> checked <?php } ?> id="yearwise" value="y">
            <span class="checkmark"></span>
            </label>                                                                            
            </div>																
            </div>
            <?php } ?>
            <div class="col-lg-10 mt-4 ms-4" id="day_div" style="display:<?php if (
               $_REQUEST[
                   "date_wise"
               ] == "d"
               ) {
               echo "block";
               } else {
               echo "none";
               } ?>;">
            <div class="d-flex flex-column align-items-center gap-3">
            <!-- <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">From Date:</label> -->
            <div class="col-8 selectortypedate ">
            <div class="dateselector ">
            <input type="text" id="from_dt" name="from_dt" placeholder="From Date" class="col-xs-12 col-sm-12 date-picker form-control monthlyinputdate" value="<?php if (
               $_REQUEST[
                   "from_dt"
               ] !=
               ""
               ) {
               echo $_REQUEST[
                   "from_dt"
               ];
               } else {
               echo date(
                   "d-m-Y"
               );
               } ?>" />
            <!-- <span class="input-group-addon"> <i class="ace-icon fa fa-calendar bigger-110"></i> </span>  -->
            </div>
            </div>
            <!-- <div class="space-2"></div> -->
            <!-- <div class="form-group"> -->
            <!-- <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">To Date:</label> -->
            <div class="col-8 selectortypedate">
            <div class="dateselector ">
            <input type="text" id="to_dt" name="to_dt" placeholder="To Date" class="col-xs-12 col-sm-12 date-picker form-control monthlyinputdate endDate" value="<?php if (
               $_REQUEST[
                   "to_dt"
               ] !=
               ""
               ) {
               echo $_REQUEST[
                   "to_dt"
               ];
               } else {
               echo date(
                   "d-m-Y"
               );
               } ?>"  />
            <!-- <span class="input-group-addon"> <i class="ace-icon fa fa-calendar bigger-110"></i> </span> </div> -->
            </div>
            </div>
            </div>
            </div>
            <div class="col-lg-10 mt-4 ms-4" id="month_div" style="display:<?php if (
               $_REQUEST[
                   "date_wise"
               ] == "m"
               ) {
               echo "block";
               } else {
               echo "none";
               } ?>;">
            <div class="d-flex flex-column align-items-center gap-3">
            <!-- <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Month:</label> -->
            <div class="col-8 selectortypedate">
            <div class="dateselector ">
            <input type="text" id="mfrom_dt" name="mfrom_dt" placeholder="From Date" class="col-xs-12 col-sm-12  date-picker form-control monthlyinputdate mydatepicker" value="<?php if (
               $_REQUEST[
                   "mfrom_dt"
               ] !=
               ""
               ) {
               echo $_REQUEST[
                   "mfrom_dt"
               ];
               } else {
               echo date(
                   "m-Y"
               );
               } ?>">
            <!-- <span class="input-group-addon"> <i class="ace-icon fa fa-calendar bigger-110"></i> </span> </div> -->
            </div>
            </div>
            </div>
            </div>
            <div class="col-lg-10 mt-4 ms-4" id="year_div" style="display:<?php if (
               $_REQUEST[
                   "date_wise"
               ] == "y"
               ) {
               echo "block";
               } else {
               echo "none";
               } ?>;">
            <div class="d-flex flex-column align-items-center gap-3">
            <!-- <label class="control-label col-xs-12 col-sm-3 no-padding-right" for="name">Month:</label> -->
            <div class="col-8 selectortypedate">
            <div class="dateselector ">
            <input type="text" id="yfrom_dt" name="yfrom_dt" placeholder="From year" class="col-xs-12 col-sm-12 date-picker form-control monthlyinputdate fydatepicker" value="<?php if (
               $_REQUEST[
                   "yfrom_dt"
               ] !=
               ""
               ) {
               echo $_REQUEST[
                   "yfrom_dt"
               ];
               } else {
               echo date(
                   "Y"
               );
               } ?>" />
            <!-- <span class="input-group-addon"> <i class="ace-icon fa fa-calendar bigger-110"></i> </span> </div> -->
            </div>
            </div>
            </div>
            </div>
            </div>
            </div>
            <div class="col-12 mt-5 d-flex justify-content-center pb-3">
            <button class="resetdatabtn me-4"><a href="master-status-report.php" >Reset Data</a></button>
            <button type="submit" name="btn_rinvoice" class="resultbtn">Get Result</button>
            </div>
            </div>
            </div>
            </form>
            </div>
         
         <!-- Table section starts-->
         <div class="container table-responsive custom-container p-4 mt-3" style="box-shadow: 0px 0px 16px 0px rgba(164, 164, 171, 0.25);border-radius: 24px;">
            <?php
            //    echo $_REQUEST["radio"];
               if (
                   $_REQUEST["radio"] ==
                   "wip"
               ) {
               
                   if (
                       $_COOKIE[
                           "user_level"
                       ] == 5
                   ) {
                       $query =
                           "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,volume_folder,v_issue_id,highpriority,wd.target_due_dt,wd.assigned_user_id,wd.ce_pe_due_date,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,graphics,fig_complete_status,stage,r.received_date,r.due_date,r.correction_pages,r.revision_count,aq_status,user_working_status FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND (wd.cust_id = '" .
                           $_COOKIE[
                               "cust_id"
                           ] .
                           "' OR FIND_IN_SET(wd.cust_id,'" .
                           $_COOKIE[
                               "ocust_id"
                           ] .
                           "'))  AND wd.status NOT IN ('Client Review','Completed')   $ccc  $search1 $search2 $search3  " .
                           $_SESSION[
                               "inpt_status_qry"
                           ] .
                           " ";
                       $cnt_query =
                           "SELECT COUNT(wd.id) as num,c.cust_name,journal_title,pub_id,graphics,fig_complete_status,recv_dt,due_dt,wd.assigned_user_id,wd.ce_pe_due_date,highpriority,wd.target_due_dt,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,r.received_date,r.due_date,r.correction_pages,r.revision_count,aq_status ,user_working_status FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND (wd.cust_id = '" .
                           $_COOKIE[
                               "cust_id"
                           ] .
                           "' OR FIND_IN_SET(wd.cust_id,'" .
                           $_COOKIE[
                               "ocust_id"
                           ] .
                           "')) AND wd.status NOT IN ('Client Review','Completed')   $ccc  $search1 $search2 $search3  " .
                           $_SESSION[
                               "inpt_status_qry"
                           ] .
                           "  ";
                   } elseif (
                       $_COOKIE[
                           "user_level"
                       ] == 6
                   ) {
                       $query =
                           "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,volume_folder,v_issue_id,highpriority,wd.target_due_dt,wd.assigned_user_id,wd.ce_pe_due_date,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,graphics,fig_complete_status,stage,r.received_date,r.due_date,r.correction_pages,r.revision_count,aq_status ,user_working_status FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND (wd.cust_id = '" .
                           $_COOKIE[
                               "cust_id"
                           ] .
                           "' OR FIND_IN_SET(wd.cust_id,'" .
                           $_COOKIE[
                               "ocust_id"
                           ] .
                           "')) AND (wd.department_id = '" .
                           $_COOKIE[
                               "department_id"
                           ] .
                           "' OR FIND_IN_SET(wd.department_id,'" .
                           $_COOKIE[
                               "odepartment_id"
                           ] .
                           "'))  AND wd.status NOT IN ('Client Review','Completed')   $ccc  $search1 $search2 $search3  " .
                           $_SESSION[
                               "inpt_status_qry"
                           ] .
                           " ";
                       $cnt_query =
                           "SELECT COUNT(wd.id) as num,c.cust_name,journal_title,pub_id,graphics,fig_complete_status,recv_dt,due_dt,wd.assigned_user_id,wd.ce_pe_due_date,highpriority,wd.target_due_dt,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,r.received_date,r.due_date,r.correction_pages,r.revision_count,aq_status ,user_working_status FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND (wd.cust_id = '" .
                           $_COOKIE[
                               "cust_id"
                           ] .
                           "' OR FIND_IN_SET(wd.cust_id,'" .
                           $_COOKIE[
                               "ocust_id"
                           ] .
                           "')) AND (wd.department_id = '" .
                           $_COOKIE[
                               "department_id"
                           ] .
                           "' OR FIND_IN_SET(wd.department_id,'" .
                           $_COOKIE[
                               "odepartment_id"
                           ] .
                           "')) AND wd.status NOT IN ('Client Review','Completed')   $ccc  $search1 $search2 $search3  " .
                           $_SESSION[
                               "inpt_status_qry"
                           ] .
                           "  ";
                   } else {
                       $query =
                           "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,volume_folder,v_issue_id,highpriority,wd.target_due_dt,wd.assigned_user_id,wd.ce_pe_due_date,wd.created_dt,wd.assigned_user_id,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,graphics,fig_complete_status,stage,r.received_date,r.due_date,r.correction_pages,r.revision_count,aq_status ,user_working_status FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  AND wd.status NOT IN ('Client Review','Completed')   $ccc  $search1 $search2 $search3 " .
                           $_SESSION[
                               "inpt_status_qry"
                           ] .
                           "  ";
                       $cnt_query =
                           "SELECT COUNT(wd.id) as num,c.cust_name,journal_title,pub_id,graphics,fig_complete_status,recv_dt,due_dt,wd.assigned_user_id,wd.ce_pe_due_date,highpriority,wd.target_due_dt,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,r.received_date,r.due_date,r.correction_pages,r.revision_count,aq_status ,user_working_status FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND wd.status NOT IN ('Client Review','Completed')   $ccc  $search1 $search2 $search3  " .
                           $_SESSION[
                               "inpt_status_qry"
                           ] .
                           "  ";
                   }
                   ($customers = $db->query(
                       $cnt_query
                   )) or $db->error;
                   $page_count = $customers->fetch_assoc();
                   $total_pages =
                       $page_count["num"];
                   if (
                       $_COOKIE[
                           "user_level"
                       ] == 5
                   ) {
                       $order_by =
                           "ORDER BY FIELD(highpriority, 1) desc,`due_dt` asc";
                   } else {
                       $order_by =
                           "ORDER BY FIELD(highpriority, 1) desc,`target_due_dt` asc";
                   }
                   if (
                       $_REQUEST["start"]
                   ) {
                       $start =
                           $_REQUEST[
                               "start"
                           ];
                   } else {
                       $start = 0;
                   }
                   if (
                       $_REQUEST[
                           "date_wise"
                       ] != "y" &&
                       $_REQUEST[
                           "date_wise"
                       ] != "m" &&
                       $_REQUEST[
                           "date_wise"
                       ] != "d"
                   ) {
                       $filePath =
                           "master-status-report.php?page=1&radio=" .
                           $_REQUEST[
                               "radio"
                           ] .
                           "&cust_id=" .
                           $_REQUEST[
                               "cust_id"
                           ] .
                           "&j_id=" .
                           $_REQUEST[
                               "j_id"
                           ] .
                           "&stage_id=" .
                           $_REQUEST[
                               "stage_id"
                           ] .
                           "&dep_id=" .
                           $_REQUEST[
                               "dep_id"
                           ] .
                           "&dp_platform=" .
                           $_REQUEST[
                               "dp_platform"
                           ] .
                           ""; //your file name  (the name of this file)
                   } else {
                       $filePath =
                           "master-status-report.php?page=1&radio=" .
                           $_REQUEST[
                               "radio"
                           ] .
                           "&cust_id=" .
                           $_REQUEST[
                               "cust_id"
                           ] .
                           "&j_id=" .
                           $_REQUEST[
                               "j_id"
                           ] .
                           "&stage_id=" .
                           $_REQUEST[
                               "stage_id"
                           ] .
                           "&from_dt=" .
                           $_REQUEST[
                               "from_dt"
                           ] .
                           "&to_dt=" .
                           $_REQUEST[
                               "to_dt"
                           ] .
                           "&mfrom_dt=" .
                           $_REQUEST[
                               "mfrom_dt"
                           ] .
                           "&yfrom_dt=" .
                           $_REQUEST[
                               "yfrom_dt"
                           ] .
                           "&date_wise=" .
                           $_REQUEST[
                               "date_wise"
                           ] .
                           "&dep_id=" .
                           $_REQUEST[
                               "dep_id"
                           ] .
                           "&dp_platform=" .
                           $_REQUEST[
                               "dp_platform"
                           ] .
                           ""; //your file name  (the name of this file)
                   }
                   $limit = 10; //how many items to show per page
                   $query =
                       $query .
                       $order_by .
                       " LIMIT $start, $limit"; //echo $query;
                   ($articles = $db->query(
                       $query
                   )) or $db->error;
                   $articles->num_rows;
               
                   //echo "fdgfdg".$total_pages;
                   ?>   
            <div class="container mb-4 ms-4">
               <div class="row">
                  <div
                     class="col-6 col-lg-2 date mb-2"
                     style="font-size: 1.375rem; font-weight: 400"
                     >
                     WIP Report
                  </div>
                  <div class="col-4 col-lg-4 d-flex align-items-center">
                     <div
                        class="totalcount p-2 d-flex align-items-center"
                        style="width: 181px"
                        >
                        <p class="ps-2">Total Count</p>
                     </div>
                     <div class="totalcountval" style="position: relative"><?php echo $total_pages; ?></div>
                  </div>
                  <div class="col-2 col-lg-2 d-flex align-items-center">
                     <a href="download-excel-wip-report.php?cust_id=<?php echo $_REQUEST[
                        "cust_id"
                        ]; ?>&j_id=<?php echo $_REQUEST["j_id"]; ?>&stage_id=<?php echo $_REQUEST[
                        "stage_id"
                        ]; ?>&dp_platform=<?php echo $_REQUEST[
                        "dp_platform"
                        ]; ?>&dep_id=<?php echo $_REQUEST[
                        "dep_id"
                        ]; ?>&inpt_status=<?php echo $_REQUEST[
                        "inpt_status"
                        ]; ?>&radio=<?php echo $_REQUEST[
                        "radio"
                        ]; ?>" class="btn btn-primary">Download Excel </a>
                  </div>
               </div>
            </div>
            <div class="myTable1">
               <table class="table table-striped custtable table-primary wiptable">
                  <thead>
                     <th class="thcolor">Client </th>
                     <th class="thcolor">Journal Name</th>
                     <th class="thcolor">FileName</th>
                     <?php if ($_REQUEST["dep_id"] != 1 && $_REQUEST["dep_id"] != 2) { ?>
                     <th class="thcolor">Stage</th>
                     <th class="thcolor">Vol No/Issue No</th>
                     <?php } ?>
                     <th class="thcolor">Received Date</th>
                     <?php if ($_REQUEST["dep_id"] != 1 && $_REQUEST["dep_id"] != 2) { ?>
                     <th class="thcolor">Due Date</th>
                     <?php } ?>
                     <th class="thcolor">Target Due Date</th>
                     <?php if ($_REQUEST["dep_id"] == "1" || $_REQUEST["dep_id"] == "2") { ?>
                     <th class="thcolor">Alloted to</th>
                     <th class="thcolor">Alloted Due date</th>
                     <?php } ?>
                     <th class="thcolor">Status</th>
                     <th class="thcolor">Graphics</th>
                     <th class="thcolor">Figures Count</th>
                     <th class="thcolor">Manuscript Count</th>
                     <th class="thcolor">Instruction</th>
                     <th class="thcolor">Job Card</th>
                  </thead>
                  <tbody id="tbl1Body">
                     <?php if (
                        $articles->num_rows >
                        0
                        ) { ?>
                     <?php while (
                        $row_history = $articles->fetch_assoc()
                        ) {
                        
                        $stage_r = preg_replace(
                            "/REV([0-9]+)/",
                            "REV",
                            $row_history[
                                "stage"
                            ]
                        );
                        $stage_i = preg_replace(
                            "/ISSCOR([0-9]+)/",
                            "ISSCOR",
                        
                            $row_history[
                                "stage"
                            ]
                        );
                        
                        //print_r($row_history);
                        $trans_sql =
                            "SELECT u.full_name,t.current_status,t.completion_status,t.comments,t.id FROM `inw_transactions` as t,users as u WHERE t.process_user = u.id AND t.`project_id` = '" .
                            $row_history[
                                "id"
                            ] .
                            "' AND t.created_dt > '2020-03-01'  AND t.current_status NOT IN ('Completed','Take Over') AND t.stage = '" .
                            $row_history[
                                "stage"
                            ] .
                            "' AND (t.id=(select id from `inw_transactions` where dept!=9 and `project_id` = '" .
                            $row_history[
                                "id"
                            ] .
                            "' ORDER BY `id` DESC limit 1))  ORDER BY t.`id` DESC limit 1;";
                        //echo $trans_sql.'<br>';
                        $chk_transaction = $db->query(
                            $trans_sql
                        );
                        $transaction_toatl_count =
                            $chk_transaction->num_rows;
                        $trans_res = $chk_transaction->fetch_assoc();
                        $assign_comments = mysqli_query(
                            $db,
                            "select * from inw_transactions where project_id='" .
                                $row_history[
                                    "id"
                                ] .
                                "' and dept='14' and completion_status=6 AND stage = '" .
                                $row_history[
                                    "stage"
                                ] .
                                "' order by id desc limit 1"
                        );
                        $num_acount = mysqli_num_rows(
                            $assign_comments
                        );
                        $res_ac = mysqli_fetch_array(
                            $assign_comments
                        );
                        if (
                            $_REQUEST[
                                "dep_id"
                            ] ==
                                "1" ||
                            $_REQUEST[
                                "dep_id"
                            ] ==
                                "2"
                        ) {
                            $assign_cepe = mysqli_fetch_array(
                                mysqli_query(
                                    $db,
                                    "select * from users where id='" .
                                        $row_history[
                                            "assigned_user_id"
                                        ] .
                                        "'"
                                )
                            );
                        }
                        if (
                            $row_history[
                                "stage"
                            ] ==
                            "FP"
                        ) {
                            $due_dt = date(
                                "d-m-Y",
                                strtotime(
                                    $row_history[
                                        "due_dt"
                                    ]
                                )
                            );
                            $recv_dt = date(
                                "d-m-Y",
                                strtotime(
                                    $row_history[
                                        "recv_dt"
                                    ]
                                )
                            );
                            $manuscript_count =
                                $row_history[
                                    "manuscript_count"
                                ];
                        } else {
                            if (
                                $stage_r ==
                                "REV"
                            ) {
                                $rev_sql =
                                    "SELECT *  FROM `inw_revisions_dtl` WHERE `inw_id` = '" .
                                    $row_history[
                                        "id"
                                    ] .
                                    "' ORDER BY r_id DESC LIMIT 1";
                                $rev_transaction = $db->query(
                                    $rev_sql
                                );
                                $rev_res = $rev_transaction->fetch_assoc();
                                $due_dt = date(
                                    "d-m-Y",
                                    strtotime(
                                        $rev_res[
                                            "due_date"
                                        ]
                                    )
                                );
                                $recv_dt = date(
                                    "d-m-Y",
                                    strtotime(
                                        $rev_res[
                                            "received_date"
                                        ]
                                    )
                                );
                                $manuscript_count =
                                    $rev_res[
                                        "correction_pages"
                                    ];
                            } else {
                                $vol_sql =
                                    "SELECT *  FROM `inw_volume_issues` WHERE `vi_id` = '" .
                                    $row_history[
                                        "v_issue_id"
                                    ] .
                                    "'";
                                $vol_transaction = $db->query(
                                    $vol_sql
                                );
                                $vol_res = $vol_transaction->fetch_assoc();
                                $due_dt = date(
                                    "d-m-Y",
                                    strtotime(
                                        $vol_res[
                                            "due_date"
                                        ]
                                    )
                                );
                                $recv_dt = date(
                                    "d-m-Y",
                                    strtotime(
                                        $vol_res[
                                            "received_date"
                                        ]
                                    )
                                );
                                $manuscript_count =
                                    $vol_res[
                                        "correction_pages"
                                    ];
                            }
                        }
                        $tr_class =
                            "";
                        $current_dateTimestamp1 = strtotime(
                            date(
                                "d-m-Y"
                            )
                        );
                        $Due_dateTimestamp2 = strtotime(
                            $due_dt
                        );
                        if (
                            $row_history[
                                "highpriority"
                            ] ==
                            1
                        ) {
                            $tr_class =
                                "alert alert-info";
                        } elseif (
                            $row_history[
                                "status"
                            ] ==
                                "Query" ||
                            $row_history[
                                "status"
                            ] ==
                                "Hold"
                        ) {
                            $tr_class =
                                "alert alert-dark";
                        } elseif (
                            $current_dateTimestamp1 >
                            $Due_dateTimestamp2
                        ) {
                            $tr_class =
                                "alert alert-danger";
                        }
                        ?>
                     <tr class="<?php echo $tr_class; ?>">
                        <td><?php echo $row_history[
                           "cust_name"
                           ]; ?></td>
                        <td><?php echo $row_history[
                           "j_code"
                           ]; ?></td>
                        <td><?php echo $row_history[
                           "pub_id"
                           ]; ?></td>
                        <?php if (
                           $row_history[
                               "stage"
                           ] ==
                           "FP"
                           ) { ?>
                        <?php if ($_REQUEST["dep_id"] != 1 && $_REQUEST["dep_id"] != 2) { ?>
                        <td><?php echo $row_history["stage"]; ?></td>
                        <td><?php echo $row_history["volume_folder"]; ?></td>
                        <?php } ?>
                        <td><?php echo date(
                           "d M Y",
                           strtotime(
                               $recv_dt
                           )
                           ); ?></td>
                        <?php if ($_REQUEST["dep_id"] != 1 && $_REQUEST["dep_id"] != 2) { ?>
                        <td><?php echo date(
                           "d M Y",
                           strtotime(
                               $due_dt
                           )
                           ); ?></td>
                        <?php } ?>
                        <td><?php echo date(
                           "d M Y",
                           strtotime(
                               $row_history[
                                   "target_due_dt"
                               ]
                           )
                           ); ?></td>
                        <?php } elseif (
                           $stage_r ==
                           "REV"
                           ) { ?>
                        <?php if ($_REQUEST["dep_id"] != 1 && $_REQUEST["dep_id"] != 2) { ?>
                        <td><?php echo "REV" . $row_history["revision_count"]; ?></td>
                        <td><?php echo $row_history["volume_folder"]; ?></td>
                        <?php } ?>
                        <td><?php echo date(
                           "d M Y",
                           
                           strtotime(
                               $recv_dt
                           )
                           ); ?></td>
                        <?php if ($_REQUEST["dep_id"] != 1 && $_REQUEST["dep_id"] != 2) { ?>
                        <td><?php echo date(
                           "d M Y",
                           strtotime(
                               $due_dt
                           )
                           ); ?></td>
                        <?php } ?>
                        <td>-</td>
                        <?php } elseif (
                           $stage_i ==
                           "ISSCOR"
                           ) { ?>
                        <?php if ($_REQUEST["dep_id"] != 1 && $_REQUEST["dep_id"] != 2) { ?>
                        <td><?php echo $row_history["stage"]; ?></td>
                        <td><?php echo $row_history["volume_folder"]; ?></td>
                        <?php } ?>
                        <td><?php echo date(
                           "d M Y",
                           
                           strtotime(
                               $recv_dt
                           )
                           ); ?></td>
                        <?php if ($_REQUEST["dep_id"] != 1 && $_REQUEST["dep_id"] != 2) { ?>
                        <td><?php echo date(
                           "d M Y",
                           strtotime(
                               $due_dt
                           )
                           ); ?></td>
                        <?php } ?>
                        <td>-</td>
                        <?php } else { ?>
                        <?php if ($_REQUEST["dep_id"] != 1 && $_REQUEST["dep_id"] != 2) { ?>
                        <td><?php echo $row_history["stage"]; ?></td>
                        <td><?php echo $row_history["volume_folder"]; ?></td>
                        <?php } ?>
                        <td><?php echo date(
                           "d M Y",
                           strtotime(
                               $row_history[
                                   "received_date"
                               ]
                           )
                           ); ?></td>
                        <?php if ($_REQUEST["dep_id"] != 1 && $_REQUEST["dep_id"] != 2) { ?>
                        <td><?php echo date(
                           "d M Y",
                           strtotime(
                               $row_history[
                                   "due_date"
                               ]
                           )
                           ); ?></td>
                        <?php } ?>
                        <td>-</td>
                        <?php } ?>
                        <?php if ($_REQUEST["dep_id"] == "1" || $_REQUEST["dep_id"] == "2") { ?>
                        <td><?php echo $assign_cepe["full_name"]; ?></td>
                        <td><?php echo date("d M Y", strtotime($row_history["ce_pe_due_date"])); ?></td>
                        <?php } ?>
                        <td>
                           <span class="label label-sm label-success"><?php echo $row_history[
                              "status"
                              ]; ?></span>
                           <?php
                              if ($transaction_toatl_count > 0) {
                                  echo '<span class="label label-sm label-info">' .
                                      $trans_res["full_name"] .
                                      '</span>|<span class="label label-sm label-warning">' .
                                      $trans_res["current_status"] .
                                      "</span>";
                              }
                              if ($num_acount > 0 && $res_ac["comments"] != "") {
                                  echo '|<span class="label label-sm label-danger">' .
                                      $res_ac["comments"] .
                                      "</span>";
                              } elseif ($num_acount > 0 && $res_ac["comments"] == "") {
                                  echo '|<span class="label label-sm label-danger">No Comments</span>';
                              }
                              ?>
                           <?php if (!empty($row_history["aq_status"])) {
                              echo '|<span class="label label-sm label-primary">' .
                                  $row_history["aq_status"] .
                                  "</span>";
                              } ?>
                        </td>
                        <td><?php if (
                           $row_history[
                               "fig_complete_status"
                           ] ==
                           "1"
                           ) {
                           echo "Yes";
                           } else {
                           echo "No";
                           } ?></td>
                        <td><?php echo $row_history[
                           "fig"
                           ]; ?></td>
                        <td><?php echo $row_history[
                           "manuscript_count"
                           ]; ?></td>
                        <td><a href="send_instructions.php?id=<?php echo $row_history[
                           "id"
                           ]; ?>" target="_blank" class="btn btn-info">Send</a></td>
                        <td> <a title="Article history"  href="javascript:void();" data-toggle="modal" data-target="#myModal" onClick="return timepopup(<?php echo $row_history[
                           "id"
                           ]; ?>);">   <button class="btn btn-xs btn-info"><i class="ace-icon fa fa-clock-o bigger-120"></i>
                           </button></a>
                        </td>
                     </tr>
                     <?php //}
                        $i++;
                        }} ?>
                  </tbody>
               </table>
               <?php if ($total_pages > $limit) { ?>
               <!-- <tr> -->
                  <!-- <td colspan="4"> -->
                     <div class="col-xs-12">
                        <div class="dataTables_info" id="dynamic-table_info" role="status" aria-live="polite">Showing <?php echo $start +
                           1; ?> to <?php echo $start + $limit > $total_pages
                           ? $total_pages
                           : $start + $limit; ?> of <?php echo $total_pages; ?> entries</div>
                     </div>
                  </td>
                  <td align="center" colspan="8" class="inactive">
                     <div class="dataTables_paginate paging_simple_numbers" id="datatable_paginate">
                        <ul class="pagination">
                           <?php paginate(
                              $start,
                              $limit,
                              $total_pages,
                              $filePath,
                              $otherParams
                              ); ?>
                        </ul>
                     </div>
                  </td>
               <!-- </tr> -->
               <?php } ?>
            </div>
            <?php
               if ($_REQUEST["cust_id"] != "all" && $_REQUEST["cust_id"] != "") {
                   $sql_query =
                       "SELECT * FROM `adm_customer_master` where id='" .
                       $_REQUEST["cust_id"] .
                       "' ";
               } else {
                   $sql_query = "SELECT * FROM `adm_customer_master`";
               }
               ($run_row = $db->query($sql_query)) or die($db->error);
               while ($row = $run_row->fetch_assoc()) {
                   if ($_REQUEST["j_id"] != "all" && $_REQUEST["j_id"] != "") {
                       $sql_journal =
                           "SELECT `j_id`, `j_cust_id`,`j_code` FROM `adm_journals` WHERE  j_cust_id =" .
                           $row["id"] .
                           " and j_id='" .
                           $_REQUEST["j_id"] .
                           "' ";
                   } else {
                       $sql_journal =
                           "SELECT `j_id`, `j_cust_id`,`j_code` FROM `adm_journals` WHERE  j_cust_id =" .
                           $row["id"] .
                           "";
                   } ?>
            <div class="container mt-5 ms-4">
               <p style="font-size: 24px">Consolidated-Report</p>
            </div>
            <div
               class="container table-responsive mt-4 d-flex justify-content-center"
               >
               <table class="table table-bordered" style="width: 75%">
                  <thead>
                     <tr class="violetclr centercontent">
                        <th rowspan="2">Customer</th>
                        <th rowspan="2">Journal</th>
                        <th colspan="3">Stages</th>
                     </tr>
                     <tr  class="violetclr centercontent">
                        <th class="thcolor">FP</th>
                        <th class="thcolor">REV</th>
                        <th class="thcolor">FIN</th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php
                        ($row_run = $db->query($sql_journal)) or die($db->error);
                        $num_row = $row_run->num_rows;
                        $r = 1;
                        ?>
                     <tr>
                        <td rowspan="<?php echo $num_row; ?>"><?php echo $row[
                           "cust_name"
                           ]; ?> </td>
                        <?php while ($row_journal = $row_run->fetch_assoc()) {
                           // print_r($row_journal);
                           $j_id = $row_journal["j_id"];
                           $j_codes = $row_journal["j_code"];
                           $satge = $row_journal["stage"];
                           $fp_query =
                               "SELECT count(id)as stage_count FROM `inw_inward_dtl` WHERE cust_id =" .
                               $row["id"] .
                               "  AND j_id = $j_id AND stage = 'FP' AND status NOT IN ('Delivery','Client Review','Completed')   $searchfp $search_cy  $search_cm $search_cd ";
                           ($fp_run_query = $db->query($fp_query)) or die($db->error);
                           $fp_row = $fp_run_query->fetch_assoc();
                           $fp_id_count = $fp_row["stage_count"];
                           $rev_query =
                               "SELECT count(pub_id)as rev_count FROM `inw_inward_dtl` WHERE cust_id =" .
                               $row["id"] .
                               " AND j_id = $j_id   AND status NOT IN ('Delivery','Client Review','Completed')AND stage LIKE 'REV%' $searchfp  $search_cy  $search_cm $search_cd"; //$rev_query = "SELECT stage as rev_count FROM `inw_inward_dtl` WHERE cust_id = $cust_ids AND j_id = $j_id Order by stage DESC limit 1 ";
                           ($fev_run_query = $db->query($rev_query)) or die($db->error);
                           $fev_row = $fev_run_query->fetch_assoc();
                           $fev_id_count = $fev_row["rev_count"];
                           $fin_query =
                               "SELECT COUNT(*)as id FROM `inw_inward_dtl` WHERE cust_id =" .
                               $row["id"] .
                               " AND j_id=$j_id  AND status NOT IN ('Delivery','Client Review','Completed') AND  stage LIKE '%FIN%' $searchfp $search_cy  $search_cm $search_cd";
                           ($fin_run_query = $db->query($fin_query)) or die($db->error);
                           $fin_row = $fin_run_query->fetch_assoc();
                           $fin_id_count = $fin_row["id"];
                           if ($r == 1) { ?>
                        <td><?php echo $j_codes; ?></td>
                        <td><?php echo $fp_id_count; ?></td>
                        <td><?php echo $fev_id_count; ?></td>
                        <td><?php echo $fin_id_count; ?></td>
                     </tr>
                     <?php } else { ?>
                     <tr>
                        <td><?php echo $j_codes; ?></td>
                        <td><?php echo $fp_id_count; ?></td>
                        <td><?php echo $fev_id_count; ?></td>
                        <td><?php echo $fin_id_count; ?></td>
                     </tr>
                     <?php }
                        $r++;
                        }
                               }
                        
                                                                          //}
                                                                          ?>
                  </tbody>
               </table>
            </div>
            <?php
               }
               ?>
            <?php //echo $_REQUEST['radio'];
               if ($_REQUEST["radio"] == "sfd") {
               
                   $today_date = date("Y-m-d");
               
                   /// today date
                   if ($_COOKIE["user_level"] == 6) {
                       $query =
                           "SELECT wd.id,wd.cust_id, wd.j_id, wd.status, wd.due_dt,wd.target_due_dt, wd.recv_dt, wd.pub_id, wd.stage, wd.department_id,aq_status ,user_working_status FROM inw_inward_dtl as wd, adm_journals as j  WHERE  wd.j_id = j.j_id AND (wd.cust_id = '" .
                           $_COOKIE["cust_id"] .
                           "' OR FIND_IN_SET(wd.cust_id,'" .
                           $_COOKIE["ocust_id"] .
                           "')) AND (wd.department_id = '" .
                           $_COOKIE["department_id"] .
                           "' OR FIND_IN_SET(wd.department_id,'" .
                           $_COOKIE["odepartment_id"] .
                           "')) and  wd.status != 'Client Review'  AND wd.created_dt > '2020-03-01' AND wd.stage='FP' $ccc  $search $search2 ORDER BY id desc";
                   } else {
                       $query = "SELECT wd.id,wd.cust_id, wd.j_id, wd.status, wd.due_dt,wd.target_due_dt, wd.recv_dt, wd.pub_id, wd.stage, wd.department_id,aq_status ,user_working_status FROM inw_inward_dtl as wd, adm_journals as j  WHERE  wd.j_id = j.j_id and  wd.status != 'Client Review'  AND wd.created_dt > '2020-03-01' AND wd.stage='FP' $ccc  $search $search2 ORDER BY id desc";
                   } //echo $query ;
                   ($articledetails = $db->query($query)) or $db->error;
                   $filecount = 1;
                   if ($articledetails->num_rows > 0) { ?>   
            <div class="row">
               <div
                  class="col-6 col-lg-2 date mb-2"
                  style="font-size: 1.375rem; font-weight: 400"
                  >
                  First Proof Articles
               </div>
               <div class="col-4 col-lg-4 d-flex align-items-center">
                  <div
                     class="totalcount p-2 d-flex align-items-center"
                     style="width: 181px"
                     >
                     <p class="ps-2">Total Count</p>
                  </div>
                  <div class="totalcountval" style="position: relative"><?php echo $articledetails->num_rows; ?></div>
               </div>
            </div>
            <div class="myTable1">
               <div id="t_div"><br/></div>
               <table class="table table-striped custtable table-primary wiptable">
                  <thead>
                     <th class="thcolor">Client </th>
                     <th class="thcolor">Journal Name</th>
                     <th class="thcolor">FileName</th>
                     <th class="thcolor">Stage</th>
                     <th class="thcolor">Received Date</th>
                     <th class="thcolor">Due Date</th>
                     <th class="thcolor">Target Date</th>
                     <th class="thcolor">Status</th>
                  </thead>
                  <tbody id="tbl1Body">
                     <?php while (
                        $article_info = $articledetails->fetch_assoc()
                        ) {
                        
                        /// Get the customers details ////
                        $query =
                            "SELECT cust_name,cust_id,id,email,password FROM `adm_customer_master` WHERE id = '" .
                            $article_info[
                                "cust_id"
                            ] .
                            "' ";
                        ($customers = $db->query(
                            $query
                        )) or
                            $db->error;
                        $crow = $customers->fetch_assoc(); //// Get the Journal Details ///
                        $query =
                            "SELECT j_name,j_code FROM `adm_journals` WHERE j_id = '" .
                            $article_info[
                                "j_id"
                            ] .
                            "';";
                        ($steps = $db->query(
                            $query
                        )) or
                            $db->error;
                        $jninfo = $steps->fetch_assoc(); /////// Get the department name /////
                        $query =
                            "SELECT * FROM adm_dept_master WHERE dept_code = '" .
                            $article_info[
                                "department_id"
                            ] .
                            "'";
                        ($dpts = $db->query(
                            $query
                        )) or
                            $db->error;
                        $dptinfo = $dpts->fetch_assoc(); // $query_disp = mysqli_fetch_array(mysqli_query($db, "SELECT * FROM `inw_dispatch_history` WHERE a_id ='" . $article_info['id'] . "' ORDER BY `idh_id` DESC limit 1 "));
                        $send_dts = date(
                            "d-m-Y"
                        );
                        $due_dts = date(
                            "d-m-Y",
                            strtotime(
                                $article_info[
                                    "due_dt"
                                ]
                            )
                        );
                        $trans_sql =
                            "SELECT u.full_name,t.current_status,t.id FROM `inw_transactions` as t,users as u WHERE t.process_user = u.id AND t.`project_id` = '" .
                            $article_info[
                                "id"
                            ] .
                            "' AND t.created_dt > '2020-03-01'  AND t.current_status NOT IN ('Completed','Take Over') AND t.stage = '" .
                            $article_info[
                                "stage"
                            ] .
                            "' AND ('Completed' != (select current_status from `inw_transactions` where `project_id` = '" .
                            $article_info[
                                "id"
                            ] .
                            "' ORDER BY `id` DESC limit 1) )  ORDER BY t.`id` DESC limit 1;";
                        //echo $trans_sql.'<br>';
                        $chk_transaction = $db->query(
                            $trans_sql
                        );
                        $transaction_toatl_count =
                            $chk_transaction->num_rows;
                        $trans_res = $chk_transaction->fetch_assoc();
                        ?>
                     <tr  <?php if (
                        $send_dts >
                        $due_dts
                        ) {
                        echo "style='color:red;'";
                        } ?>>
                        <td><?php echo $crow[
                           "cust_name"
                           ]; ?></td>
                        <td><?php echo $jninfo[
                           "j_code"
                           ]; ?></td>
                        <td><?php echo $article_info[
                           "pub_id"
                           ]; ?></td>
                        <td><?php echo $article_info[
                           "stage"
                           ]; ?></td>
                        <td><?php echo date(
                           "d M Y",
                           strtotime(
                               $article_info[
                                   "recv_dt"
                               ]
                           )
                           ); ?></td>
                        <td><?php echo date(
                           "d M Y",
                           strtotime(
                               $article_info[
                                   "due_dt"
                               ]
                           )
                           ); ?></td>
                        <td><?php echo date("d M Y", strtotime($article_info["target_due_dt"])); ?></td>
                        <td>
                           <span class="label label-sm label-success"><?php echo $article_info[
                              "status"
                              ]; ?></span>
                           <?php if ($transaction_toatl_count > 0) {
                              echo '<span class="label label-sm label-info">' .
                                  $trans_res["full_name"] .
                                  '</span>|<span class="label label-sm label-warning">' .
                                  $trans_res["current_status"] .
                                  "</span>";
                              } ?>
                        </td>
                     </tr>
                     <?php
                        } ?>
                  </tbody>
               </table>
            </div>
            <?php }
               ?>
            <?php
               if ($_COOKIE["user_level"] == 6) {
                   $querys =
                       "SELECT wd.id,wd.cust_id, wd.j_id, wd.status, r.due_date, r.received_date, wd.pub_id, wd.stage, wd.department_id,aq_status ,user_working_status FROM inw_inward_dtl as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)),adm_journals as j  WHERE  wd.j_id = j.j_id AND (wd.cust_id = '" .
                       $_COOKIE["cust_id"] .
                       "' OR FIND_IN_SET(wd.cust_id,'" .
                       $_COOKIE["ocust_id"] .
                       "')) AND (wd.department_id = '" .
                       $_COOKIE["department_id"] .
                       "' OR FIND_IN_SET(wd.department_id,'" .
                       $_COOKIE["odepartment_id"] .
                       "')) and wd.status != 'Client Review'  AND  DATE_FORMAT(r.due_date,'%Y-%m-%d')<='" .
                       $today_date .
                       "' AND r.created_on > '2020-03-01' AND wd.stage LIKE 'REV%' AND wd.id = r.inw_id $ccc  $search1 $search2 $search3 GROUP BY r.inw_id ORDER BY wd.cust_id ASC";
               } else {
                   $querys =
                       "SELECT wd.id,wd.cust_id, wd.j_id, wd.status, r.due_date, r.received_date, wd.pub_id, wd.stage, wd.department_id,aq_status ,user_working_status FROM inw_inward_dtl as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)),adm_journals as j  WHERE  wd.j_id = j.j_id and wd.status != 'Client Review'  AND  DATE_FORMAT(r.due_date,'%Y-%m-%d')<='" .
                       $today_date .
                       "' AND r.created_on > '2020-03-01' AND wd.stage LIKE 'REV%' AND wd.id = r.inw_id $ccc  $search1 $search2 $search3 GROUP BY r.inw_id ORDER BY wd.cust_id ASC";
               } //echo $querys;
               ($articledetails = $db->query($querys)) or $db->error; //echo  $articledetails->num_rows;
               if ($articledetails->num_rows > 0) { ?>													
            <div class="row">
               <div
                  class="col-6 col-lg-2 date mb-2"
                  style="font-size: 1.375rem; font-weight: 400"
                  >
                  Revises Articles
               </div>
               <div class="col-4 col-lg-4 d-flex align-items-center">
                  <div
                     class="totalcount p-2 d-flex align-items-center"
                     style="width: 181px"
                     >
                     <p class="ps-2">Total Count</p>
                  </div>
                  <div class="totalcountval" style="position: relative"><?php echo $articledetails->num_rows; ?></div>
               </div>
            </div>
            <div class="myTable1">
               <div id="t_div"><br/></div>
               <table class="table table-striped custtable table-primary wiptable">
                  <thead>
                     <th class="thcolor">Client </th>
                     <th class="thcolor">Journal Name</th>
                     <th class="thcolor">FileName</th>
                     <th class="thcolor">Stage</th>
                     <th class="thcolor">Received Date</th>
                     <th class="thcolor">Due Date</th>
                     <th class="thcolor">Status</th>
                  </thead>
                  <tbody id="tbl1Body">
                     <?php while (
                        $article_info = $articledetails->fetch_assoc()
                        ) {
                        
                        /// Get the customers details ////
                        $query =
                            "SELECT cust_name,cust_id,id,email,password FROM `adm_customer_master` WHERE id = '" .
                            $article_info[
                                "cust_id"
                            ] .
                            "' ";
                        ($customers = $db->query(
                            $query
                        )) or
                            $db->error;
                        $crow = $customers->fetch_assoc(); //// Get the Journal Details ///
                        $query =
                            "SELECT j_name,j_code FROM `adm_journals` WHERE j_id = '" .
                            $article_info[
                                "j_id"
                            ] .
                            "';";
                        ($steps = $db->query(
                            $query
                        )) or
                            $db->error;
                        $jninfo = $steps->fetch_assoc(); /////// Get the department name /////
                        $query =
                            "SELECT * FROM adm_dept_master WHERE dept_code = '" .
                            $article_info[
                                "department_id"
                            ] .
                            "'";
                        ($dpts = $db->query(
                            $query
                        )) or
                            $db->error;
                        $dptinfo = $dpts->fetch_assoc();
                        $query =
                            "SELECT * FROM inw_revisions_dtl WHERE inw_id = '" .
                            $article_info[
                                "id"
                            ] .
                            "' order by r_id desc limit 1";
                        ($revin = $db->query(
                            $query
                        )) or
                            $db->error;
                        $revinfo = $revin->fetch_assoc();
                        $send_dts = date(
                            "d-m-Y"
                        );
                        $due_dts = date(
                            "d-m-Y",
                            strtotime(
                                $article_info[
                                    "due_date"
                                ]
                            )
                        );
                        $trans_sql =
                            "SELECT u.full_name,t.current_status,t.id FROM `inw_transactions` as t,users as u WHERE t.process_user = u.id AND t.`project_id` = '" .
                            $article_info[
                                "id"
                            ] .
                            "' AND t.created_dt > '2020-03-01'  AND t.current_status NOT IN ('Completed','Take Over') AND t.stage = '" .
                            $article_info[
                                "stage"
                            ] .
                            "' AND ('Completed' != (select current_status from `inw_transactions` where `project_id` = '" .
                            $article_info[
                                "id"
                            ] .
                            "' ORDER BY `id` DESC limit 1) )  ORDER BY t.`id` DESC limit 1;";
                        //echo $trans_sql.'<br>';
                        $chk_transaction = $db->query(
                            $trans_sql
                        );
                        $transaction_toatl_count =
                            $chk_transaction->num_rows;
                        $trans_res = $chk_transaction->fetch_assoc();
                        ?>
                     <tr  <?php if (
                        $send_dts >
                        $due_dts
                        ) {
                        echo "style='color:red;'";
                        } ?>>
                        <td><?php echo $crow[
                           "cust_name"
                           ]; ?></td>
                        <td><?php echo $jninfo[
                           "j_code"
                           ]; ?></td>
                        <td><?php echo $article_info[
                           "pub_id"
                           ]; ?></td>
                        <td><?php echo $article_info[
                           "stage"
                           ]; ?></td>
                        <td><?php echo date(
                           "d M Y",
                           strtotime(
                               $article_info[
                                   "received_date"
                               ]
                           )
                           ); ?></td>
                        <td><?php echo date(
                           "d M Y",
                           strtotime(
                               $article_info[
                                   "due_date"
                               ]
                           )
                           ); ?></td>
                        <td>
                           <span class="label label-sm label-success"><?php echo $article_info[
                              "status"
                              ]; ?></span>
                           <?php if ($transaction_toatl_count > 0) {
                              echo '<span class="label label-sm label-info">' .
                                  $trans_res["full_name"] .
                                  '</span>|<span class="label label-sm label-warning">' .
                                  $trans_res["current_status"] .
                                  "</span>";
                              } ?>
                        </td>
                     </tr>
                     <?php
                        } ?>
                  </tbody>
               </table>
            </div>
            <?php }
               ?>
            <?php
               if ($_COOKIE["user_level"] == 6) {
                   $querys =
                       "SELECT wd.id,wd.cust_id, wd.j_id, wd.status, r.due_date, r.received_date, wd.pub_id, wd.stage, wd.department_id,aq_status ,user_working_status FROM inw_inward_dtl as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_journals as j  WHERE  wd.j_id = j.j_id  AND (wd.cust_id = '" .
                       $_COOKIE["cust_id"] .
                       "' OR FIND_IN_SET(wd.cust_id,'" .
                       $_COOKIE["ocust_id"] .
                       "')) AND (wd.department_id = '" .
                       $_COOKIE["department_id"] .
                       "' OR FIND_IN_SET(wd.department_id,'" .
                       $_COOKIE["odepartment_id"] .
                       "')) and wd.status != 'Client Review' AND DATE_FORMAT(r.due_date,'%Y-%m-%d')<='" .
                       $today_date .
                       "' AND r.created_on > '2020-03-01' AND wd.stage LIKE 'FIN%' AND wd.id = r.inw_id $ccc  $search1 $search2 $search3 GROUP BY r.inw_id ORDER BY wd.cust_id ASC";
               } else {
                   $querys =
                       "SELECT wd.id,wd.cust_id, wd.j_id, wd.status, r.due_date, r.received_date, wd.pub_id, wd.stage, wd.department_id,aq_status ,user_working_status FROM inw_inward_dtl as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_journals as j  WHERE  wd.j_id = j.j_id and wd.status != 'Client Review' AND DATE_FORMAT(r.due_date,'%Y-%m-%d')<='" .
                       $today_date .
                       "' AND r.created_on > '2020-03-01' AND wd.stage LIKE 'FIN%' AND wd.id = r.inw_id $ccc  $search1 $search2 $search3 GROUP BY r.inw_id ORDER BY wd.cust_id ASC";
               }
               ($articledetails = $db->query($querys)) or $db->error; //echo  $articledetails->num_rows;
               if ($articledetails->num_rows > 0) { ?>													
            <div class="row">
               <div
                  class="col-6 col-lg-2 date mb-2"
                  style="font-size: 1.375rem; font-weight: 400"
                  >
                  Finals Articles
               </div>
               <div class="col-4 col-lg-4 d-flex align-items-center">
                  <div
                     class="totalcount p-2 d-flex align-items-center"
                     style="width: 181px"
                     >
                     <p class="ps-2">Total Count</p>
                  </div>
                  <div class="totalcountval" style="position: relative"><?php echo $articledetails->num_rows; ?></div>
               </div>
            </div>
            <div class="myTable1">
               <div id="t_div"><br/></div>
               <table class="table table-striped custtable table-primary wiptable">
                  <thead>
                     <th class="thcolor">Client </th>
                     <th class="thcolor">Journal Name</th>
                     <th class="thcolor">FileName</th>
                     <th class="thcolor">Stage</th>
                     <th class="thcolor">Received Date</th>
                     <th class="thcolor">Due Date</th>
                     <th class="thcolor">Status</th>
                  </thead>
                  <tbody id="tbl1Body">
                     <?php while (
                        $article_info = $articledetails->fetch_assoc()
                        ) {
                        
                        /// Get the customers details ////
                        $query =
                            "SELECT cust_name,cust_id,id,email,password FROM `adm_customer_master` WHERE id = '" .
                            $article_info[
                                "cust_id"
                            ] .
                            "' ";
                        ($customers = $db->query(
                            $query
                        )) or
                            $db->error;
                        $crow = $customers->fetch_assoc(); //// Get the Journal Details ///
                        $query =
                            "SELECT j_name,j_code FROM `adm_journals` WHERE j_id = '" .
                            $article_info[
                                "j_id"
                            ] .
                            "';";
                        ($steps = $db->query(
                            $query
                        )) or
                            $db->error;
                        $jninfo = $steps->fetch_assoc(); /////// Get the department name /////
                        $query =
                            "SELECT * FROM adm_dept_master WHERE dept_code = '" .
                            $article_info[
                                "department_id"
                            ] .
                            "'";
                        ($dpts = $db->query(
                            $query
                        )) or
                            $db->error;
                        $dptinfo = $dpts->fetch_assoc();
                        $send_dts = date(
                            "d-m-Y"
                        );
                        $due_dts = date(
                            "d-m-Y",
                            strtotime(
                                $article_info[
                                    "due_date"
                                ]
                            )
                        );
                        $trans_sql =
                            "SELECT u.full_name,t.current_status,t.id FROM `inw_transactions` as t,users as u WHERE t.process_user = u.id AND t.`project_id` = '" .
                            $article_info[
                                "id"
                            ] .
                            "' AND t.created_dt > '2020-03-01'  AND t.current_status NOT IN ('Completed','Take Over') AND t.stage = '" .
                            $article_info[
                                "stage"
                            ] .
                            "' AND ('Completed' != (select current_status from `inw_transactions` where `project_id` = '" .
                            $article_info[
                                "id"
                            ] .
                            "' ORDER BY `id` DESC limit 1) )  ORDER BY t.`id` DESC limit 1;";
                        //echo $trans_sql.'<br>';
                        $chk_transaction = $db->query(
                            $trans_sql
                        );
                        $transaction_toatl_count =
                            $chk_transaction->num_rows;
                        $trans_res = $chk_transaction->fetch_assoc();
                        ?>
                     <tr  <?php if (
                        $send_dts >
                        $due_dts
                        ) {
                        echo "style='color:red;'";
                        } ?>>
                        <td><?php echo $crow[
                           "cust_name"
                           ]; ?></td>
                        <td><?php echo $jninfo[
                           "j_code"
                           ]; ?></td>
                        <td><?php echo $article_info[
                           "pub_id"
                           ]; ?></td>
                        <td><?php echo $article_info[
                           "stage"
                           ]; ?></td>
                        <td><?php echo date(
                           "d M Y",
                           strtotime(
                               $article_info[
                                   "received_date"
                               ]
                           )
                           ); ?></td>
                        <td><?php echo date(
                           "d M Y",
                           strtotime(
                               $article_info[
                                   "due_date"
                               ]
                           )
                           ); ?></td>
                        <td>
                           <span class="label label-sm label-success"><?php echo $article_info[
                              "status"
                              ]; ?></span>
                           <?php if ($transaction_toatl_count > 0) {
                              echo '<span class="label label-sm label-info">' .
                                  $trans_res["full_name"] .
                                  '</span>|<span class="label label-sm label-warning">' .
                                  $trans_res["current_status"] .
                                  "</span>";
                              } ?>
                        </td>
                     </tr>
                     <?php
                        } ?>
                  </tbody>
               </table>
            </div>
            <?php }
               ?>
            <?php
               if ($_COOKIE["user_level"] == 6) {
                   $querys =
                       "SELECT wd.id,wd.cust_id, wd.j_id, wd.status, ivi.due_date, ivi.received_date, wd.pub_id, wd.stage, wd.department_id,aq_status ,user_working_status  FROM inw_inward_dtl as wd,inw_volume_issues as ivi , adm_journals as j  WHERE  wd.j_id = j.j_id  AND (wd.cust_id = '" .
                       $_COOKIE["cust_id"] .
                       "' OR FIND_IN_SET(wd.cust_id,'" .
                       $_COOKIE["ocust_id"] .
                       "')) AND (wd.department_id = '" .
                       $_COOKIE["department_id"] .
                       "' OR FIND_IN_SET(wd.department_id,'" .
                       $_COOKIE["odepartment_id"] .
                       "'))  and wd.status != 'Client Review' AND DATE_FORMAT(ivi.due_date,'%Y-%m-%d')<='" .
                       $today_date .
                       "' AND ivi.created_on > '2020-03-01' AND wd.stage LIKE 'ISSCOR%' AND wd.v_issue_id = ivi.vi_id $ccc  $search1 $search2 $search3  ORDER BY wd.cust_id ASC";
               } else {
                   $querys =
                       "SELECT wd.id,wd.cust_id, wd.j_id, wd.status, ivi.due_date, ivi.received_date, wd.pub_id, wd.stage, wd.department_id,aq_status ,user_working_status  FROM inw_inward_dtl as wd,inw_volume_issues as ivi , adm_journals as j  WHERE  wd.j_id = j.j_id and wd.status != 'Client Review' AND DATE_FORMAT(ivi.due_date,'%Y-%m-%d')<='" .
                       $today_date .
                       "' AND ivi.created_on > '2020-03-01' AND wd.stage LIKE 'ISSCOR%' AND wd.v_issue_id = ivi.vi_id $ccc  $search1 $search2 $search3  ORDER BY wd.cust_id ASC";
               }
               ($articledetails = $db->query($querys)) or $db->error; //echo  $articledetails->num_rows;
               if ($articledetails->num_rows > 0) { ?>											
            <div class="row">
               <div
                  class="col-6 col-lg-2 date mb-2"
                  style="font-size: 1.375rem; font-weight: 400"
                  >
                  Issue Corrections Articles
               </div>
               <div class="col-4 col-lg-4 d-flex align-items-center">
                  <div
                     class="totalcount p-2 d-flex align-items-center"
                     style="width: 181px"
                     >
                     <p class="ps-2">Total Count</p>
                  </div>
                  <div class="totalcountval" style="position: relative"><?php echo $articledetails->num_rows; ?></div>
               </div>
            </div>
            <div class="myTable1">
               <div id="t_div"><br/></div>
               <table class="table table-striped custtable table-primary wiptable">
                  <thead>
                     <th class="thcolor">Client </th>
                     <th class="thcolor">Journal Name</th>
                     <th class="thcolor">FileName</th>
                     <th class="thcolor">Stage</th>
                     <th class="thcolor">Received Date</th>
                     <th class="thcolor">Due Date</th>
                     <th class="thcolor">Status</th>
                  </thead>
                  <tbody id="tbl1Body">
                     <?php while (
                        $article_info = $articledetails->fetch_assoc()
                        ) {
                        
                        /// Get the customers details ////
                        $query =
                            "SELECT cust_name,cust_id,id,email,password FROM `adm_customer_master` WHERE id = '" .
                            $article_info[
                                "cust_id"
                            ] .
                            "' ";
                        ($customers = $db->query(
                            $query
                        )) or
                            $db->error;
                        $crow = $customers->fetch_assoc(); //// Get the Journal Details ///
                        $query =
                            "SELECT j_name,j_code FROM `adm_journals` WHERE j_id = '" .
                            $article_info[
                                "j_id"
                            ] .
                            "';";
                        ($steps = $db->query(
                            $query
                        )) or
                            $db->error;
                        $jninfo = $steps->fetch_assoc(); /////// Get the department name /////
                        $query =
                            "SELECT * FROM adm_dept_master WHERE dept_code = '" .
                            $article_info[
                                "department_id"
                            ] .
                            "'";
                        ($dpts = $db->query(
                            $query
                        )) or
                            $db->error;
                        $dptinfo = $dpts->fetch_assoc();
                        $send_dts = date(
                            "d-m-Y"
                        );
                        $due_dts = date(
                            "d-m-Y",
                            strtotime(
                                $article_info[
                                    "due_date"
                                ]
                            )
                        );
                        $trans_sql =
                            "SELECT u.full_name,t.current_status,t.id FROM `inw_transactions` as t,users as u WHERE t.process_user = u.id AND t.`project_id` = '" .
                            $article_info[
                                "id"
                            ] .
                            "' AND t.created_dt > '2020-03-01'  AND t.current_status NOT IN ('Completed','Take Over') AND t.stage = '" .
                            $article_info[
                                "stage"
                            ] .
                            "' AND ('Completed' != (select current_status from `inw_transactions` where `project_id` = '" .
                            $article_info[
                                "id"
                            ] .
                            "' ORDER BY `id` DESC limit 1) )  ORDER BY t.`id` DESC limit 1;";
                        //echo $trans_sql.'<br>';
                        $chk_transaction = $db->query(
                            $trans_sql
                        );
                        $transaction_toatl_count =
                            $chk_transaction->num_rows;
                        $trans_res = $chk_transaction->fetch_assoc();
                        ?>
                     <tr  <?php if (
                        $send_dts >
                        $due_dts
                        ) {
                        echo "style='color:red;'";
                        } ?>>
                        <td><?php echo $crow[
                           "cust_name"
                           ]; ?></td>
                        <td><?php echo $jninfo[
                           "j_code"
                           ]; ?></td>
                        <td><?php echo $article_info[
                           "pub_id"
                           ]; ?></td>
                        <td><?php echo $article_info[
                           "stage"
                           ]; ?></td>
                        <td><?php echo date(
                           "d M Y",
                           strtotime(
                               $article_info[
                                   "received_date"
                               ]
                           )
                           ); ?></td>
                        <td><?php echo date(
                           "d M Y",
                           strtotime(
                               $article_info[
                                   "due_date"
                               ]
                           )
                           ); ?></td>
                        <td>
                           <span class="label label-sm label-success"><?php echo $article_info[
                              "status"
                              ]; ?></span>
                           <?php if ($transaction_toatl_count > 0) {
                              echo '<span class="label label-sm label-info">' .
                                  $trans_res["full_name"] .
                                  '</span>|<span class="label label-sm label-warning">' .
                                  $trans_res["current_status"] .
                                  "</span>";
                              } ?>
                        </td>
                     </tr>
                     <?php
                        } ?>
                  </tbody>
               </table>
            </div>
            <?php }
               ?>		
            <?php
               if ($_COOKIE["user_level"] == 6) {
                   $querys =
                       "SELECT wd.id,wd.cust_id, wd.j_id, wd.status, ivi.due_date, ivi.received_date, wd.pub_id, wd.stage, wd.department_id,aq_status ,user_working_status  FROM inw_inward_dtl as wd,inw_volume_issues as ivi, adm_journals as j  WHERE  wd.j_id = j.j_id  AND (wd.cust_id = '" .
                       $_COOKIE["cust_id"] .
                       "' OR FIND_IN_SET(wd.cust_id,'" .
                       $_COOKIE["ocust_id"] .
                       "')) AND (wd.department_id = '" .
                       $_COOKIE["department_id"] .
                       "' OR FIND_IN_SET(wd.department_id,'" .
                       $_COOKIE["odepartment_id"] .
                       "'))   and wd.status != 'Client Review' AND  DATE_FORMAT(ivi.due_date,'%Y-%m-%d')<='" .
                       $today_date .
                       "' AND ivi.created_on > '2020-03-01' AND wd.stage LIKE 'ISSFIN%' AND wd.v_issue_id = ivi.vi_id $ccc  $search1 $search2 $search3  ORDER BY wd.cust_id ASC";
               } else {
                   $querys =
                       "SELECT wd.id,wd.cust_id, wd.j_id, wd.status, ivi.due_date, ivi.received_date, wd.pub_id, wd.stage, wd.department_id,aq_status ,user_working_status  FROM inw_inward_dtl as wd,inw_volume_issues as ivi, adm_journals as j  WHERE  wd.j_id = j.j_id and wd.status != 'Client Review' AND  DATE_FORMAT(ivi.due_date,'%Y-%m-%d')<='" .
                       $today_date .
                       "' AND ivi.created_on > '2020-03-01' AND wd.stage LIKE 'ISSFIN%' AND wd.v_issue_id = ivi.vi_id $ccc  $search1 $search2 $search3  ORDER BY wd.cust_id ASC";
               } //echo $querys;
               ($articledetails = $db->query($querys)) or $db->error; //echo  $articledetails->num_rows;
               if ($articledetails->num_rows > 0) { ?>													
            <div class="row">
               <div
                  class="col-6 col-lg-2 date mb-2"
                  style="font-size: 1.375rem; font-weight: 400"
                  >
                  Issue Finals Articles
               </div>
               <div class="col-4 col-lg-4 d-flex align-items-center">
                  <div
                     class="totalcount p-2 d-flex align-items-center"
                     style="width: 181px"
                     >
                     <p class="ps-2">Total Count</p>
                  </div>
                  <div class="totalcountval" style="position: relative"><?php echo $articledetails->num_rows; ?></div>
               </div>
            </div>
            <div class="myTable1">
               <div id="t_div"><br/></div>
               <table class="table table-striped custtable table-primary wiptable">
                  <thead>
                     <th class="thcolor">Client </th>
                     <th class="thcolor">Journal Name</th>
                     <th class="thcolor">FileName</th>
                     <th class="thcolor">Stage</th>
                     <th class="thcolor">Received Date</th>
                     <th class="thcolor">Due Date</th>
                     <th class="thcolor">Status</th>
                  </thead>
                  <tbody id="tbl1Body">
                     <?php while (
                        $article_info = $articledetails->fetch_assoc()
                        ) {
                        
                        /// Get the customers details ////
                        $query =
                            "SELECT cust_name,cust_id,id,email,password FROM `adm_customer_master` WHERE id = '" .
                            $article_info[
                                "cust_id"
                            ] .
                            "' ";
                        ($customers = $db->query(
                            $query
                        )) or
                            $db->error;
                        $crow = $customers->fetch_assoc(); //// Get the Journal Details ///
                        $query =
                            "SELECT j_name,j_code FROM `adm_journals` WHERE j_id = '" .
                            $article_info[
                                "j_id"
                            ] .
                            "';";
                        ($steps = $db->query(
                            $query
                        )) or
                            $db->error;
                        $jninfo = $steps->fetch_assoc(); /////// Get the department name /////
                        $query =
                            "SELECT * FROM adm_dept_master WHERE dept_code = '" .
                            $article_info[
                                "department_id"
                            ] .
                            "'";
                        ($dpts = $db->query(
                            $query
                        )) or
                            $db->error;
                        $dptinfo = $dpts->fetch_assoc();
                        $send_dts = date(
                            "d-m-Y"
                        );
                        $due_dts = date(
                            "d-m-Y",
                            strtotime(
                                $article_info[
                                    "due_date"
                                ]
                            )
                        );
                        $trans_sql =
                            "SELECT u.full_name,t.current_status,t.id FROM `inw_transactions` as t,users as u WHERE t.process_user = u.id AND t.`project_id` = '" .
                            $article_info[
                                "id"
                            ] .
                            "' AND t.created_dt > '2020-03-01'  AND t.current_status NOT IN ('Completed','Take Over') AND t.stage = '" .
                            $article_info[
                                "stage"
                            ] .
                            "' AND ('Completed' != (select current_status from `inw_transactions` where `project_id` = '" .
                            $article_info[
                                "id"
                            ] .
                            "' ORDER BY `id` DESC limit 1) )  ORDER BY t.`id` DESC limit 1;";
                        //echo $trans_sql.'<br>';
                        $chk_transaction = $db->query(
                            $trans_sql
                        );
                        $transaction_toatl_count =
                            $chk_transaction->num_rows;
                        $trans_res = $chk_transaction->fetch_assoc();
                        ?>
                     <tr  <?php if (
                        $send_dts >
                        $due_dts
                        ) {
                        echo "style='color:red;'";
                        } ?>>
                        <td><?php echo $crow[
                           "cust_name"
                           ]; ?></td>
                        <td><?php echo $jninfo[
                           "j_code"
                           ]; ?></td>
                        <td><?php echo $article_info[
                           "pub_id"
                           ]; ?></td>
                        <td><?php echo $article_info[
                           "stage"
                           ]; ?></td>
                        <td><?php echo date(
                           "d M Y",
                           strtotime(
                               $article_info[
                                   "received_date"
                               ]
                           )
                           ); ?></td>
                        <td><?php echo date(
                           "d M Y",
                           strtotime(
                               $article_info[
                                   "due_date"
                               ]
                           )
                           ); ?></td>
                        <td>
                           <span class="label label-sm label-success"><?php echo $article_info[
                              "status"
                              ]; ?></span>
                           <?php if ($transaction_toatl_count > 0) {
                              echo '<span class="label label-sm label-info">' .
                                  $trans_res["full_name"] .
                                  '</span>|<span class="label label-sm label-warning">' .
                                  $trans_res["current_status"] .
                                  "</span>";
                              } ?>
                        </td>
                     </tr>
                     <?php
                        } ?>
                  </tbody>
               </table>
            </div>
            <?php }
               ?>																	
            <?php
               } ?>
            <?php if ($_REQUEST["radio"] == "dp") { ?>
            <table class="table table-striped custtable table-primary wiptable">
               <tbody>
                  <tr>
                     <td class="" style="text-align: left">
                        <?php
                           $zero_cust = [];
                           $sql_query = "SELECT * FROM `adm_customer_master`";
                           ($run_row = $db->query($sql_query)) or die($db->error);
                           while ($row = $run_row->fetch_assoc()) {
                               $cnt_query =
                                   "SELECT COUNT(wd.idh_id) as sent_count FROM inw_dispatch_history as wd, `inw_inward_dtl` as wdd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id and wd.a_id = wdd.id  AND wdd.cust_id = " .
                                   $row["id"] .
                                   "  $ccc  $search $search2 $search4";
                               ($fp_run_query = $db->query($cnt_query)) or die($db->error);
                               $fp_row = $fp_run_query->fetch_assoc();
                               $sent_count = $fp_row["sent_count"];
                               if ($sent_count > 0) { ?>
                        <p class="col-md-2" style="color:<?php echo $row[
                           "font_color"
                           ]; ?> !important;"> <?php echo $row[
                           "cust_name"
                           ]; ?> - <?php echo $sent_count; ?></p>
                        <?php } else {$zero_cust[] = $row["id"];}
                           }
                           ?>
                        <?php $implode = implode(",", $zero_cust); ?>
                     </td>
                  </tr>
               </tbody>
            </table>
            <table class="table table-striped custtable table-primary wiptable">
               <tbody>
                  <tr>
                     <td class="pb-3" style="display: flex; flex-wrap: wrap; gap: 10px">
                        <?php
                           $sql_query =
                               "SELECT * FROM `adm_customer_master` where FIND_IN_SET(id,'" .
                               $implode .
                               "')";
                           ($run_row = $db->query($sql_query)) or die($db->error);
                           while ($row = $run_row->fetch_assoc()) {
                           
                               $cnt_query =
                                   "SELECT COUNT(wd.idh_id) as sent_count FROM inw_dispatch_history as wd, `inw_inward_dtl` as wdd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id and wd.a_id = wdd.id  AND wdd.cust_id = " .
                                   $row["id"] .
                                   "  $ccc  $search $search2 $search4";
                               ($fp_run_query = $db->query($cnt_query)) or die($db->error);
                               $fp_row = $fp_run_query->fetch_assoc();
                               $sent_count = $fp_row["sent_count"];
                               if ($sent_count == 0) { ?>
                        <p class="col-md-2" style="color:<?php echo $row[
                           "font_color"
                           ]; ?> !important;flex-grow: 1;text-align: left;border-bottom: 1px solid #d3cdcd59;"> <?php echo $row[
                           "cust_name"
                           ]; ?> - <?php echo $sent_count; ?></p>
                        <?php }
                           ?>
                        <?php
                           }
                           ?>
                     </td>
                  </tr>
               </tbody>
            </table>
            <?php
               $_REQUEST["radio"];
               $query = "SELECT *,c.cust_name,j.j_code,wd.stage from inw_dispatch_history as wd, `inw_inward_dtl` as wdd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id and wd.a_id = wdd.id   $ccc  $search $search2 $search4 ";
               $cnt_query = "SELECT COUNT(wd.a_id) as num from inw_dispatch_history as wd,`inw_inward_dtl` as wdd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id and wd.a_id = wdd.id    $ccc  $search $search2  $search4 ";
               ($customers = $db->query($cnt_query)) or $db->error;
               $page_count = $customers->fetch_assoc();
               $total_pages = $page_count["num"];
               $order_by = "ORDER BY `idh_id` DESC";
               if ($_REQUEST["start"]) {
                   $start = $_REQUEST["start"];
               } else {
                   $start = 0;
               }
               if (
                   $_REQUEST["date_wise"] != "y" &&
                   $_REQUEST["date_wise"] != "m" &&
                   $_REQUEST["date_wise"] != "d"
               ) {
                   $filePath =
                       "master-status-report.php?page=1&radio=" .
                       $_REQUEST["radio"] .
                       "&cust_id=" .
                       $_REQUEST["cust_id"] .
                       "&j_id=" .
                       $_REQUEST["j_id"] .
                       "&stage_id=" .
                       $_REQUEST["stage_id"] .
                       "";
                   //your file name  (the name of this file)
               } else {
                   $filePath =
                       "master-status-report.php?page=1&radio=" .
                       $_REQUEST["radio"] .
                       "&cust_id=" .
                       $_REQUEST["cust_id"] .
                       "&j_id=" .
                       $_REQUEST["j_id"] .
                       "&stage_id=" .
                       $_REQUEST["stage_id"] .
                       "&from_dt=" .
                       $_REQUEST["from_dt"] .
                       "&to_dt=" .
                       $_REQUEST["to_dt"] .
                       "&mfrom_dt=" .
                       $_REQUEST["mfrom_dt"] .
                       "&yfrom_dt=" .
                       $_REQUEST["yfrom_dt"] .
                       "&date_wise=" .
                       $_REQUEST["date_wise"] .
                       "";
                   //your file name  (the name of this file)
               }
               $limit = 10;
               //how many items to show per page
               $query = $query . $order_by . " LIMIT $start, $limit"; //echo $query;
               ($articles = $db->query($query)) or $db->error;
               $articles->num_rows;
               
                      //echo "fdgfdg".$total_pages;
                      ?>   
            <div class="row mb-4" style="display:flex; justify-content: space-between">
               <div
                  class="col-12 col-lg-4 date mb-2"
                  style="font-size: 1.375rem; font-weight: 400"
                  >
                  Delivery Performance Report
               </div>
               <div class="col-12 col-lg-4 d-flex align-items-center mb-2">
                  <div
                     class="totalcount p-2 d-flex align-items-center"
                     style="width: 181px"
                     >
                     <p class="ps-2">Total Count</p>
                  </div>
                  <div class="totalcountval" style="position: relative"><?php echo $total_pages; ?></div>
               </div>
               <div class="col-12 col-lg-2 d-flex align-items-center mb-2">
                  <a href="download-excel-dp-report.php?cust_id=<?php echo $_REQUEST[
                     "cust_id"
                     ]; ?>&j_id=<?php echo $_REQUEST["j_id"]; ?>&stage_id=<?php echo $_REQUEST[
                     "stage_id"
                     ]; ?>&dp_platform=<?php echo $_REQUEST[
                     "dp_platform"
                     ]; ?>&radio=<?php echo $_REQUEST["radio"]; ?>&from_dt=<?php echo $_REQUEST[
                     "from_dt"
                     ]; ?>&to_dt=<?php echo $_REQUEST["to_dt"]; ?>&mfrom_dt=<?php echo $_REQUEST[
                     "mfrom_dt"
                     ]; ?>&yfrom_dt=<?php echo $_REQUEST[
                     "yfrom_dt"
                     ]; ?>&date_wise=<?php echo $_REQUEST[
                     "date_wise"
                     ]; ?>" class="btn btn-primary">Download Excel </a>
               </div>
            </div>
            <div class="myTable1">
               <table class="table table-striped custtable table-primary wiptable">
                  <thead>
                     <th class="thcolor">Client </th>
                     <th class="thcolor">Journal Name</th>
                     <th class="thcolor">FileName</th>
                     <th class="thcolor">Stage</th>
                     <th class="thcolor">Received Date</th>
                     <th class="thcolor">Due Date</th>
                     <th class="thcolor">Dispatched Date</th>
                     <th class="thcolor">Page count</th>
                     <th class="thcolor">Schedule</th>
                     <th class="thcolor">Job Card</th>
                  </thead>
                  <tbody id="tbl1Body">
                     <?php if (
                        $articles->num_rows >
                        0
                        ) { ?>
                     <?php while (
                        $row_history = $articles->fetch_assoc()
                        ) {
                        
                        $stage_r = preg_replace(
                            "/REV([0-9]+)/",
                            "REV",
                            $row_history[
                                "stage"
                            ]
                        );
                        $stage_i = preg_replace(
                            "/ISSCOR([0-9]+)/",
                        
                            "ISSCOR",
                        
                            $row_history[
                                "stage"
                            ]
                        );
                        
                        //print_r($row_history);
                        $query_disp = mysqli_fetch_array(
                            mysqli_query(
                                $db,
                                "SELECT * FROM `inw_inward_dtl` WHERE id ='" .
                                    $row_history[
                                        "a_id"
                                    ] .
                                    "'"
                            )
                        );
                        if (
                            $query_disp[
                                "stage"
                            ] ==
                            "FP"
                        ) {
                            $due_dt = date(
                                "d-m-Y",
                                strtotime(
                                    $query_disp[
                                        "due_dt"
                                    ]
                                )
                            );
                            $recv_dt = date(
                                "d-m-Y",
                                strtotime(
                                    $query_disp[
                                        "recv_dt"
                                    ]
                                )
                            );
                            $manuscript_count =
                                $query_disp[
                                    "manuscript_count"
                                ];
                            $page_count =
                                $query_disp[
                                    "page_count"
                                ];
                        } else {
                            $rev_rep = str_replace(
                                "REV",
                                "",
                                $row_history[
                                    "stage"
                                ]
                            );
                            $query =
                                "SELECT * FROM inw_revisions_dtl WHERE inw_id = '" .
                                $row_history[
                                    "a_id"
                                ] .
                                "' and revision_count='" .
                                $rev_rep .
                                "'";
                            ($revin = $db->query(
                                $query
                            )) or
                                $db->error;
                            $revinfo = $revin->fetch_assoc();
                            $due_dt = date(
                                "d-m-Y",
                                strtotime(
                                    $revinfo[
                                        "due_date"
                                    ]
                                )
                            );
                            $recv_dt = date(
                                "d-m-Y",
                                strtotime(
                                    $revinfo[
                                        "received_date"
                                    ]
                                )
                            );
                            //$manuscript_count = $revinfo['correction_pages'];
                            $page_count =
                                $revinfo[
                                    "correction_pages"
                                ];
                        }
                        //$chk_transaction = $db->query($trans_sql);
                        //$transaction_toatl_count=$chk_transaction->num_rows;
                        //$trans_res = $chk_transaction->fetch_assoc();
                        $send_dt = date(
                            "d-m-Y",
                            strtotime(
                                $row_history[
                                    "sent_date"
                                ]
                            )
                        );
                        $send_dts = date(
                            "d-m-Y",
                            strtotime(
                                $row_history[
                                    "sent_date"
                                ]
                            )
                        );
                        ?>
                     <tr>
                        <td><?php echo $row_history[
                           "cust_name"
                           ]; ?></td>
                        <td><?php echo $row_history[
                           "j_code"
                           ]; ?></td>
                        <td><?php echo $query_disp[
                           "pub_id"
                           ]; ?></td>
                        <?php if (
                           $row_history[
                               "stage"
                           ] ==
                           "FP"
                           ) {
                           $due_dts = date(
                               "d-m-Y",
                               strtotime(
                                   $due_dt
                               )
                           ); ?>
                        <td><?php echo $row_history["stage"]; ?></td>
                        <td><?php echo date(
                           "d M Y",
                           strtotime(
                               $recv_dt
                           )
                           ); ?></td>
                        <td><?php echo date(
                           "d M Y",
                           strtotime(
                               $due_dt
                           )
                           ); ?></td>
                        <?php
                           } elseif (
                               $stage_r ==
                               "REV"
                           ) {
                               $due_dts = date(
                                   "d-m-Y",
                                   strtotime(
                                       $due_dt
                                   )
                               ); ?>
                        <td><?php echo $row_history["stage"]; ?></td>
                        <td><?php echo date(
                           "d M Y",
                           strtotime(
                               $recv_dt
                           )
                           ); ?></td>
                        <td><?php echo date(
                           "d M Y",
                           strtotime(
                               $due_dt
                           )
                           ); ?></td>
                        <?php
                           } elseif (
                               $stage_i ==
                               "ISSCOR"
                           ) {
                               $due_dts = date(
                                   "d-m-Y",
                                   strtotime(
                                       $due_dt
                                   )
                               ); ?>
                        <td><?php echo $row_history["stage"]; ?></td>
                        <td><?php echo date(
                           "d M Y",
                           strtotime(
                               $query_disp[
                                   "created_dt"
                               ]
                           )
                           ); ?></td>
                        <td><?php echo date(
                           "d M Y",
                           strtotime(
                               $query_disp[
                                   "due_dt"
                               ]
                           )
                           ); ?></td>
                        <?php
                           } else {
                               $due_dts = date(
                                   "d-m-Y",
                                   strtotime(
                                       $due_dt
                                   )
                               ); ?>
                        <td><?php echo $row_history["stage"]; ?></td>
                        <td><?php echo date(
                           "d M Y",
                           strtotime(
                               $query_disp[
                                   "created_dt"
                               ]
                           )
                           ); ?></td>
                        <td><?php echo date(
                           "d M Y",
                           strtotime(
                               $query_disp[
                                   "due_dt"
                               ]
                           )
                           ); ?></td>
                        <?php
                           } ?>
                        <td><?php echo $send_dt; ?></td>
                        <td><?php echo $page_count; ?></td>
                        <td>	<?php if (
                           strtotime(
                               $send_dts
                           ) ==
                           strtotime(
                               $due_dts
                           )
                           ) {
                           $l++;
                           echo "On Time";
                           } elseif (
                           strtotime(
                               $send_dts
                           ) >
                           strtotime(
                               $due_dts
                           )
                           ) {
                           $j++;
                           echo "Delay";
                           } elseif (
                           strtotime(
                               $send_dts
                           ) <
                           strtotime(
                               $due_dts
                           )
                           ) {
                           $k++;
                           echo "Ahead";
                           } ?>
                        </td>
                        <td><a title="Job Card" href="job_card.php?id=<?php echo $query_disp[
                           "id"
                           ]; ?>" target="_blank" class="btn btn-xs btn-info"><i class="ace-icon fa fa-clock-o bigger-120"></i></a> </td>
                     </tr>
                     <?php $i++;
                        }} ?>
                     <?php if ($total_pages > $limit) { ?>
                     <tr>
                        <td colspan="4">
                           <div class="col-xs-12">
                              <div class="dataTables_info" id="dynamic-table_info" role="status" aria-live="polite">Showing <?php echo $start +
                                 1; ?> to <?php echo $start + $limit > $total_pages
                                 ? $total_pages
                                 : $start + $limit; ?> of <?php echo $total_pages; ?> entries</div>
                           </div>
                        </td>
                        <td align="center" colspan="8" class="inactive">
                           <div class="dataTables_paginate paging_simple_numbers" id="datatable_paginate">
                              <ul class="pagination">
                                 <?php paginate(
                                    $start,
                                    $limit,
                                    $total_pages,
                                    $filePath,
                                    $otherParams
                                    ); ?>
                              </ul>
                           </div>
                        </td>
                     </tr>
                     <?php } ?>
                  </tbody>
               </table>
               <?php
                  $query_graph = mysqli_query(
                      $db,
                      "SELECT *,c.cust_name,j.j_code from inw_dispatch_history as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  $ccc  $search $search2 $search4 "
                  );
                  
                  while ($res_graph = mysqli_fetch_array($query_graph)) {
                      $stage_gr = preg_replace(
                          "/REV([0-9]+)/",
                          "REV",
                          $res_graph["stage"]
                      );
                      $stage_gi = preg_replace(
                          "/ISSCOR([0-9]+)/",
                          "ISSCOR",
                          $res_graph["stage"]
                      ); //$query_dispgraph = mysqli_fetch_array(mysqli_query($db, "SELECT * FROM `inw_dispatch_history` WHERE a_id ='" . $res_graph['id'] . "' ORDER BY `idh_id` DESC limit 1 "));
                      $query_dispgraph = mysqli_fetch_array(
                          mysqli_query(
                              $db,
                              "SELECT * FROM `inw_inward_dtl` WHERE id ='" .
                                  $res_graph["a_id"] .
                                  "'"
                          )
                      );
                      $send_dts = date("d-m-Y", strtotime($res_graph["sent_date"]));
                      if ($res_graph["stage"] == "FP") {
                          $due_dt = date(
                              "d-m-Y",
                              strtotime($query_dispgraph["due_dt"])
                          );
                      } elseif ($stage_gr == "REV") {
                          $rev_rep = str_replace("REV", "", $res_graph["stage"]);
                          $query =
                              "SELECT * FROM inw_revisions_dtl WHERE inw_id = '" .
                              $res_graph["a_id"] .
                              "' and revision_count='" .
                              $rev_rep .
                              "'";
                          ($revin = $db->query($query)) or $db->error;
                          $revinfo = $revin->fetch_assoc();
                  
                          $due_dt = date("d-m-Y", strtotime($revinfo["due_date"]));
                      } else {
                          $due_dt = date(
                              "d-m-Y",
                              strtotime($query_dispgraph["due_dt"])
                          );
                      }
                      if ($res_graph["stage"] == "FP") {
                          $due_dts = date("d-m-Y", strtotime($due_dt)); ?>
               <?php
                  } elseif ($stage_gr == "REV") {
                      $due_dts = date("d-m-Y", strtotime($due_dt)); ?>
               <?php
                  } elseif ($stage_gi == "ISSCOR") {
                      $due_dts = date("d-m-Y", strtotime($due_dt)); ?>
               <?php
                  } else {
                      $due_dts = date("d-m-Y", strtotime($due_dt)); ?>
               <?php
                  }
                  if (strtotime($send_dts) == strtotime($due_dts)) {
                      $lg++;
                  } elseif (strtotime($send_dts) > strtotime($due_dts)) {
                      $jg++;
                  } elseif (strtotime($send_dts) < strtotime($due_dts)) {
                      $kg++;
                  }
                  }
                  $ahead = $kg;
                  $delay = $jg;
                  
                  $ontime = $lg;
                  ?>
            </div>
         </div>
         <!-- Styles -->
         <style>
            #chartdiv {
            width: 100%;
            height: 500px;
            }
            #chartdiv1 {
            width: 100%;
            height: 500px;
            }#chartdiv2 {
            width: 100%;
            height: 500px;
            }
         </style>
         <!-- Resources -->
         <script src="https://www.amcharts.com/lib/4/core.js"></script>
         <script src="https://www.amcharts.com/lib/4/charts.js"></script>
         <script src="https://www.amcharts.com/lib/4/themes/animated.js"></script>
         <!-- Chart code -->
         <script>
            am4core.ready(function() {
            
            // Themes begin
            am4core.useTheme(am4themes_animated);
            // Themes end
            
            // Create chart instance
            var chart = am4core.create("chartdiv", am4charts.XYChart);
            
            // Add data
            chart.data = [{
              "Performance": "Ahead",
              "Counts": <?php if ($ahead != 0) {
               echo $ahead;
               } else {
               echo "0";
               } ?>
            }, {
              "Performance": "Ontime",
              "Counts": <?php if ($ontime != 0) {
               echo $ontime;
               } else {
               echo "0";
               } ?>
            }, {
              "Performance": "Delay",
              "Counts": <?php if ($delay != 0) {
               echo $delay;
               } else {
               echo "0";
               } ?>
            
            
            
            }];
            
            // Create axes
            
            var categoryAxis = chart.xAxes.push(new am4charts.CategoryAxis());
            categoryAxis.dataFields.category = "Performance";
            categoryAxis.renderer.grid.template.location = 0;
            categoryAxis.renderer.minGridDistance = 15;
            
            categoryAxis.renderer.labels.template.adapter.add("dy", function(dy, target) {
              if (target.dataItem && target.dataItem.index & 2 == 2) {
                return dy + 15;
              }
              return dy;
            });
            
            var valueAxis = chart.yAxes.push(new am4charts.ValueAxis());
            
            // Create series
            var series = chart.series.push(new am4charts.ColumnSeries());
            series.dataFields.valueY = "Counts";
            series.dataFields.categoryX = "Performance";
            series.name = "Counts";
            series.columns.template.tooltipText = "{categoryX}: [bold]{valueY}[/]";
            series.columns.template.fillOpacity = .8;
            
            var columnTemplate = series.columns.template;
            columnTemplate.strokeWidth = 2;
            columnTemplate.strokeOpacity = 1;
            
            }); // end am4core.ready()
            
            
            am4core.ready(function() {
            
            // Themes begin
            am4core.useTheme(am4themes_animated);
            // Themes end
            
            // Create chart instance
            var chart1 = am4core.create("chartdiv1", am4charts.XYChart);
            
            // Add data
            chart1.data = [{
              "Performance": "Ahead",
              "Counts": <?php if ($ahead != 0) {
               echo $ahead;
               } else {
               echo "0";
               } ?>
            }, {
              "Performance": "Ontime",
              "Counts": <?php if ($ontime != 0) {
               echo $ontime;
               } else {
               echo "0";
               } ?>
            }, {
              "Performance": "Delay",
              "Counts": <?php if ($delay != 0) {
               echo $delay;
               } else {
               echo "0";
               } ?>
            
            
            }];
            
            // Create axes
            
            var categoryAxis = chart1.xAxes.push(new am4charts.CategoryAxis());
            categoryAxis.dataFields.category = "Performance";
            categoryAxis.renderer.grid.template.location = 0;
            categoryAxis.renderer.minGridDistance = 15;
            
            categoryAxis.renderer.labels.template.adapter.add("dy", function(dy, target) {
              if (target.dataItem && target.dataItem.index & 2 == 2) {
                return dy + 15;
              }
              return dy;
            });
            
            var valueAxis = chart1.yAxes.push(new am4charts.ValueAxis());
            
            // Create series
            var series = chart1.series.push(new am4charts.ColumnSeries());
            series.dataFields.valueY = "Counts";
            series.dataFields.categoryX = "Performance";
            series.name = "Counts";
            series.columns.template.tooltipText = "{categoryX}: [bold]{valueY}[/]";
            series.columns.template.fillOpacity = .8;
            
            var columnTemplate = series.columns.template;
            columnTemplate.strokeWidth = 2;
            columnTemplate.strokeOpacity = 1;
            
            }); // end am4core.ready()
            
            
            
            am4core.ready(function() {
            
            // Themes begin
            am4core.useTheme(am4themes_animated);
            // Themes end
            
            // Create chart instance
            var chart2 = am4core.create("chartdiv2", am4charts.XYChart);
            
            // Add data
            chart2.data = [{
              "Performance": "Ahead",
              "Counts": <?php if ($ahead != 0) {
               echo $ahead;
               } else {
               echo "0";
               } ?>
            }, {
              "Performance": "Ontime",
              "Counts": <?php if ($ontime != 0) {
               echo $ontime;
               } else {
               echo "0";
               } ?>
            }, {
              "Performance": "Delay",
              "Counts": <?php if ($delay != 0) {
               echo $delay;
               } else {
               echo "0";
               } ?>
            
            
            
            }];
            
            // Create axes
            
            var categoryAxis = chart2.xAxes.push(new am4charts.CategoryAxis());
            categoryAxis.dataFields.category = "Performance";
            categoryAxis.renderer.grid.template.location = 0;
            categoryAxis.renderer.minGridDistance = 15;
            
            categoryAxis.renderer.labels.template.adapter.add("dy", function(dy, target) {
              if (target.dataItem && target.dataItem.index & 2 == 2) {
                return dy + 15;
              }
              return dy;
            });
            
            var valueAxis = chart2.yAxes.push(new am4charts.ValueAxis());
            
            // Create series
            var series = chart2.series.push(new am4charts.ColumnSeries());
            series.dataFields.valueY = "Counts";
            series.dataFields.categoryX = "Performance";
            series.name = "Counts";
            series.columns.template.tooltipText = "{categoryX}: [bold]{valueY}[/]";
            series.columns.template.fillOpacity = .8;
            
            var columnTemplate = series.columns.template;
            columnTemplate.strokeWidth = 2;
            columnTemplate.strokeOpacity = 1;
            
            }); // end am4core.ready()
         </script>
         <!-- HTML -->
         <?php } ?>
         <?php //echo $_REQUEST['radio'];
            if ($_REQUEST["radio"] == "dr") {
            
                $query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,r.received_date,r.due_date,r.correction_pages,r.revision_count FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  AND wd.status IN ('Client Review') and wd.v_issue_id <= 0 $ccc  $search1 $search2 $search4 ";
                $cnt_query = "SELECT COUNT(wd.id) as num,c.cust_name,journal_title,pub_id,recv_dt,due_dt,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,r.received_date,r.due_date,r.correction_pages,r.revision_count FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id AND wd.status IN ('Client Review') and wd.v_issue_id <= 0 $ccc  $search1 $search2 $search4";
                ($customers = $db->query($cnt_query)) or $db->error;
                $page_count = $customers->fetch_assoc();
                $total_pages = $page_count["num"];
                $order_by = "ORDER BY `id` DESC";
                if ($_REQUEST["start"]) {
                    $start = $_REQUEST["start"];
                } else {
                    $start = 0;
                }
                if (
                    $_REQUEST["date_wise"] != "y" &&
                    $_REQUEST["date_wise"] != "m" &&
                    $_REQUEST["date_wise"] != "d"
                ) {
                    $filePath =
                        "master-status-report.php?page=1&radio=" .
                        $_REQUEST["radio"] .
                        "&cust_id=" .
                        $_REQUEST["cust_id"] .
                        "&j_id=" .
                        $_REQUEST["j_id"] .
                        "&stage_id=" .
                        $_REQUEST["stage_id"] .
                        "";
                    //your file name  (the name of this file)
                } else {
                    $filePath =
                        "master-status-report.php?page=1&radio=" .
                        $_REQUEST["radio"] .
                        "&cust_id=" .
                        $_REQUEST["cust_id"] .
                        "&j_id=" .
                        $_REQUEST["j_id"] .
                        "&stage_id=" .
                        $_REQUEST["stage_id"] .
                        "&from_dt=" .
                        $_REQUEST["from_dt"] .
                        "&to_dt=" .
                        $_REQUEST["to_dt"] .
                        "&mfrom_dt=" .
                        $_REQUEST["mfrom_dt"] .
                        "&yfrom_dt=" .
                        $_REQUEST["yfrom_dt"] .
                        "&date_wise=" .
                        $_REQUEST["date_wise"] .
                        "";
                    //your file name  (the name of this file)
                }
                $limit = 10; //how many items to show per page
                $query = $query . $order_by . " LIMIT $start, $limit"; //echo $query;
                ($articles = $db->query($query)) or $db->error;
                $articles->num_rows;
            
                //echo "fdgfdg".$total_pages;
                ?>   
         <div class="row">
            <div
               class="col-6 col-lg-2 date mb-2"
               style="font-size: 1.375rem; font-weight: 400"
               >
               Detailed Report
            </div>
            <div class="col-4 col-lg-4 d-flex align-items-center">
               <div
                  class="totalcount p-2 d-flex align-items-center"
                  style="width: 181px"
                  >
                  <p class="ps-2">Total Count</p>
               </div>
               <div class="totalcountval" style="position: relative"><?php echo $articles->num_rows; ?></div>
            </div>
         </div>
         <div class="myTable1">
            <table class="table table-striped custtable table-primary wiptable">
               <thead>
                  <th class="thcolor">Client </th>
                  <th class="thcolor">Journal Name</th>
                  <th class="thcolor">FileName</th>
                  <th class="thcolor">Stage</th>
                  <th class="thcolor">Received Date</th>
                  <th class="thcolor">Due Date</th>
                  <th class="thcolor">Dispatched Date</th>
                  <th class="thcolor">Schedule</th>
                  <th class="thcolor">Job Card</th>
               </thead>
               <tbody id="tbl1Body">
                  <?php if (
                     $articles->num_rows >
                     0
                     ) { ?>
                  <?php while (
                     $row_history = $articles->fetch_assoc()
                     ) {
                     
                     $stage_r = preg_replace(
                         "/REV([0-9]+)/",
                         "REV",
                         $row_history[
                             "stage"
                         ]
                     );
                     $stage_i = preg_replace(
                         "/ISSCOR([0-9]+)/",
                     
                         "ISSCOR",
                     
                         $row_history[
                             "stage"
                         ]
                     );
                     
                     //print_r($row_history);
                     $trans_sql =
                         "SELECT u.full_name,t.current_status,t.id FROM `inw_transactions` as t,users as u WHERE t.process_user = u.id AND t.`project_id` = '" .
                         $row_history[
                             "id"
                         ] .
                         "'  AND t.current_status NOT IN ('Completed','Take Over') AND t.stage = '" .
                         $row_history[
                             "stage"
                         ] .
                         "' AND ('Completed' != (select current_status from `inw_transactions` where `project_id` = '" .
                         $row_history[
                             "id"
                         ] .
                         "' ORDER BY `id` DESC limit 1) )  ORDER BY t.`id` DESC limit 1;"; //echo $trans_sql.'<br>';
                     $query_disp = mysqli_fetch_array(
                         mysqli_query(
                             $db,
                             "SELECT * FROM `inw_dispatch_history` WHERE a_id ='" .
                                 $row_history[
                                     "id"
                                 ] .
                                 "' ORDER BY `idh_id` DESC limit 1 "
                         )
                     );
                     $chk_transaction = $db->query(
                         $trans_sql
                     );
                     $transaction_toatl_count =
                         $chk_transaction->num_rows;
                     $trans_res = $chk_transaction->fetch_assoc();
                     $send_dt = date(
                         "d-m-Y",
                         strtotime(
                             $query_disp[
                                 "sent_date"
                             ]
                         )
                     );
                     $send_dts = date(
                         "d-m-Y",
                         strtotime(
                             $query_disp[
                                 "sent_date"
                             ]
                         )
                     );
                     ?>
                  <tr>
                     <td><?php echo $row_history[
                        "cust_name"
                        ]; ?></td>
                     <td><?php echo $row_history[
                        "j_code"
                        ]; ?></td>
                     <td><?php echo $row_history[
                        "pub_id"
                        ]; ?></td>
                     <?php if (
                        $row_history[
                            "stage"
                        ] ==
                        "FP"
                        ) {
                        $due_dts = date(
                            "d-m-Y",
                            strtotime(
                                $row_history[
                                    "due_dt"
                                ]
                            )
                        ); ?>
                     <td><?php echo $row_history["stage"]; ?></td>
                     <td><?php echo date(
                        "d M Y",
                        strtotime(
                            $row_history[
                                "recv_dt"
                            ]
                        )
                        ); ?></td>
                     <td><?php echo date(
                        "d M Y",
                        strtotime(
                            $row_history[
                                "due_dt"
                            ]
                        )
                        ); ?></td>
                     <?php
                        } elseif (
                            $stage_r ==
                            "REV"
                        ) {
                            $due_dts = date(
                                "d-m-Y",
                                strtotime(
                                    $row_history[
                                        "due_date"
                                    ]
                                )
                            ); ?>
                     <td><?php echo "REV" . $row_history["revision_count"]; ?></td>
                     <td><?php echo date(
                        "d M Y",
                        strtotime(
                            $row_history[
                                "received_date"
                            ]
                        )
                        ); ?></td>
                     <td><?php echo date(
                        "d M Y",
                        strtotime(
                            $row_history[
                                "due_date"
                            ]
                        )
                        ); ?></td>
                     <?php
                        } elseif (
                            $stage_i ==
                            "ISSCOR"
                        ) {
                            $due_dts = date(
                                "d-m-Y",
                                strtotime(
                                    $row_history[
                                        "due_dt"
                                    ]
                                )
                            ); ?>
                     <td><?php echo $row_history["stage"]; ?></td>
                     <td><?php echo date(
                        "d M Y",
                        strtotime(
                            $row_history[
                                "created_dt"
                            ]
                        )
                        ); ?></td>
                     <td><?php echo date(
                        "d M Y",
                        strtotime(
                            $row_history[
                                "due_dt"
                            ]
                        )
                        ); ?></td>
                     <?php
                        } else {
                            $due_dts = date(
                                "d-m-Y",
                                strtotime(
                                    $row_history[
                                        "due_dt"
                                    ]
                                )
                            ); ?>
                     <td><?php echo $row_history["stage"]; ?></td>
                     <td><?php echo date(
                        "d M Y",
                        strtotime(
                            $row_history[
                                "created_dt"
                            ]
                        )
                        ); ?></td>
                     <td><?php echo date(
                        "d M Y",
                        strtotime(
                            $row_history[
                                "due_dt"
                            ]
                        )
                        ); ?></td>
                     <?php
                        } ?>
                     <td><?php echo $send_dt; ?></td>
                     <td>	<?php if (
                        $send_dts ==
                        $due_dts
                        ) {
                        $l++;
                        echo "On Time";
                        } elseif (
                        $send_dts >
                        $due_dts
                        ) {
                        $j++;
                        echo "Delay";
                        } elseif (
                        $send_dts <
                        $due_dts
                        ) {
                        $k++;
                        echo "Ahead";
                        } ?>
                     </td>
                     <td><a title="Job Card" href="job_card.php?id=<?php echo $row_history[
                        "id"
                        ]; ?>" target="_blank" class="btn btn-xs btn-info"><i class="ace-icon fa fa-clock-o bigger-120"></i></a> </td>
                  </tr>
                  <?php $i++;
                     }} ?>
               </tbody>
            </table>
            <?php if ($total_pages > $limit) { ?>
            <tr>
               <td colspan="4">
                  <div class="col-xs-12">
                     <div class="dataTables_info" id="dynamic-table_info" role="status" aria-live="polite">Showing <?php echo $start +
                        1; ?> to <?php echo $start + $limit > $total_pages
                        ? $total_pages
                        : $start + $limit; ?> of <?php echo $total_pages; ?> entries</div>
                  </div>
               </td>
               <td align="center" colspan="8" class="inactive">
                  <div class="dataTables_paginate paging_simple_numbers" id="datatable_paginate">
                     <ul class="pagination">
                        <?php paginate(
                           $start,
                           $limit,
                           $total_pages,
                           $filePath,
                           $otherParams
                           ); ?>
                     </ul>
                  </div>
               </td>
            </tr>
            <?php } ?>
         </div>
         <?php
            } ?>
         <?php if ($_REQUEST["radio"] == "cr") { ?>											            
         <div id="consolidated-report-tables" >
            <div class="row">
               <div
                  class="col-6 col-lg-4 date mb-2"
                  style="font-size: 1.375rem; font-weight: 400"
                  >
                  Consolidated Report
               </div>
            </div>
            <table id="consolidated-report-table" class="table table-striped custtable table-primary wiptable " style="width: 100%; margin-top: 30px; margin-left: 0;">
               <thead>
                  <tr>
                     <th rowspan="2" style="vertical-align: middle">Customer</th>
                     <th rowspan="2" style="vertical-align: middle">Journal</th>
                     <th colspan="3">Stages</th>
                  </tr>
                  <tr>
                     <th class="thcolor">FP</th>
                     <th class="thcolor">REV</th>
                     <th class="thcolor">FIN</th>
                  </tr>
               </thead>
               <tbody>
                  <?php
                     if ($_REQUEST["cust_id"] != "") {
                         $sql_query =
                             "SELECT * FROM `adm_customer_master` where id='" .
                             $_REQUEST["cust_id"] .
                             "' ";
                     } else {
                         $sql_query = "SELECT * FROM `adm_customer_master`";
                     }
                     ($run_row = $db->query($sql_query)) or die($db->error);
                     while ($row = $run_row->fetch_assoc()) {
                     
                         if ($_REQUEST["j_id"] != "" && $_REQUEST["j_id"] != "all") {
                             $sql_journal =
                                 "SELECT `j_id`, `j_cust_id`,`j_code` FROM `adm_journals` WHERE  j_cust_id =" .
                                 $row["id"] .
                                 " and j_id='" .
                                 $_REQUEST["j_id"] .
                                 "' ";
                         } elseif ($_REQUEST["dp_platform"] != "") {
                             $sql_journal =
                                 "SELECT `j_id`, `j_cust_id`,`j_code` FROM `adm_journals` WHERE  j_cust_id =" .
                                 $row["id"] .
                                 " and j_platform='" .
                                 $_REQUEST["dp_platform"] .
                                 "'";
                         } else {
                             $sql_journal =
                                 "SELECT `j_id`, `j_cust_id`,`j_code` FROM `adm_journals` WHERE  j_cust_id =" .
                                 $row["id"] .
                                 "";
                         }
                         ($row_run = $db->query($sql_journal)) or die($db->error);
                         $num_row = $row_run->num_rows;
                         $r = 1;
                         ?>
                  <tr>
                     <td rowspan="<?php echo $num_row; ?>"><?php echo $row[
                        "cust_name"
                        ]; ?> </td>
                     <?php
                        $fp_id_count = 0;
                        $fev_id_count = 0;
                        $fin_id_count = 0;
                        while ($row_journal = $row_run->fetch_assoc()) {
                            // print_r($row_journal);
                            if ($_REQUEST["j_id"] != "" && $_REQUEST["j_id"] != "all") {
                                $j_id = $_REQUEST["j_id"];
                            } else {
                                $j_id = $row_journal["j_id"];
                            }
                            if ($_REQUEST["cust_id"] != "") {
                                $cust_id = $_REQUEST["cust_id"];
                            } else {
                                $cust_id = $row["cust_id"];
                            }
                            $j_codes = $row_journal["j_code"];
                            $fp_query = "SELECT count(idh_id)as stage_count FROM `inw_dispatch_history` as wd,adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  AND stage = 'FP' AND wd.cust_id = $cust_id    AND wd.j_id = $j_id    $search $search2 $search4 ";
                            ($fp_run_query = $db->query($fp_query)) or die($db->error);
                            $fp_row = $fp_run_query->fetch_assoc();
                            $fp_id_count = $fp_row["stage_count"];
                            $tfp_id_count += $fp_id_count;
                            $rev_query = "SELECT count(idh_id)as rev_count FROM `inw_dispatch_history`as wd,adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  AND  stage LIKE 'REV%' AND wd.cust_id = $cust_id AND wd.j_id = $j_id    $search $search2 $search4";
                            ($fev_run_query = $db->query($rev_query)) or die($db->error);
                            $fev_row = $fev_run_query->fetch_assoc();
                            $fev_id_count = $fev_row["rev_count"];
                            $tfev_id_count += $fev_id_count;
                            $fin_query = "SELECT count(idh_id)as id FROM `inw_dispatch_history` as wd,adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  AND  stage LIKE 'FIN%' AND wd.cust_id = $cust_id and  wd.j_id = $j_id   $search $search2 $search4";
                            ($fin_run_query = $db->query($fin_query)) or die($db->error);
                            $fin_row = $fin_run_query->fetch_assoc();
                            $fin_id_count = $fin_row["id"];
                            $tfin_id_count += $fin_id_count;
                            if ($r == 1) { ?>
                     <td><?php echo $j_codes; ?></td>
                     <td><?php echo $fp_id_count; ?></td>
                     <td><?php echo $fev_id_count; ?></td>
                     <td><?php echo $fin_id_count; ?></td>
                  </tr>
                  <?php } else { ?>
                  <tr>
                     <td><?php echo $j_codes; ?></td>
                     <td><?php echo $fp_id_count; ?></td>
                     <td><?php echo $fev_id_count; ?></td>
                     <td><?php echo $fin_id_count; ?></td>
                  </tr>
                  <?php }
                     $r++;
                     }
                     
                     }
                     
                                 //}
                                 ?>
                  <tr>
                     <td></td>
                     <td><b style="color:red;">Total</b></td>
                     <td><b style="color:red;"><?php echo $tfp_id_count; ?></b></td>
                     <td><b style="color:red;"><?php echo $tfev_id_count; ?></b></td>
                     <td><b style="color:red;"><?php echo $tfin_id_count; ?></b></td>
                  </tr>
               </tbody>
            </table>
         </div>
         <?php } ?>
         <?php if ($_REQUEST["radio"] == "ce") {
            $fds = date("d-m-Y", strtotime("-3 days"));
            $tds = date("d-m-Y");
            if ($_REQUEST["cfrom_dt"] != "") {
                $start_date = date(
                    "Y-m-d",
                    strtotime($_REQUEST["cfrom_dt"])
                );
                $end_date = date(
                    "Y-m-d",
                    strtotime($_REQUEST["cto_dt"])
                );
            } else {
                $start_date = $fds;
                $end_date = $tds;
            }
            ?>											            
         <div id="cepe-report-tables" >
            <div class="page-header">
               <h1>CE/PE-Report</h1>
            </div>
            <div >
               <table class="table table-striped custtable table-primary wiptable " style="width: 100%; margin-top: 30px; margin-left: 0;">
                  <thead>
                     <tr>
                        <th rowspan="2">User</th>
                        <th colspan="18" style="width:90%;color:red;"> FP Stage - <?php echo date(
                           "d-M-Y",
                           strtotime(
                               $start_date
                           )
                           ); ?> TO <?php echo date(
                           "d-M -Y",
                           strtotime($end_date)
                           ); ?></th>
                     </tr>
                     <tr>
                        <?php while (strtotime($start_date) <= strtotime($end_date)) {
                           $yds = date("Y-m-d", strtotime("-1 days"));
                           $tds = date("Y-m-d", strtotime("+1 days"));
                           if ($start_date == $yds) {
                               $today = "Yesterday";
                           } elseif ($start_date == date("Y-m-d")) {
                               $today = "Today";
                           } elseif ($start_date == $tds) {
                               $today = "Tom";
                           } else {
                               $today = date("d M", strtotime($start_date));
                           }
                           ?>
                        <th style="width:200px;color:red;font-size:18px;"> <?php echo $today; ?></th>
                        <?php $start_date = date("Y-m-d", strtotime("+1 days", strtotime($start_date)));
                           } ?>
                     </tr>
                  </thead>
                  <tbody>
                     <?php if ($_REQUEST["cfrom_dt"] != "") {
                        $sql_query =
                            "SELECT user_id FROM `assigned_article_user` as aau, users as u where aau.user_id = u.id and u.approved = 1 and u.department_id IN ('1,2') GROUP BY user_id";
                        ($run_row = $db->query($sql_query)) or die($db->error);
                        while ($row = $run_row->fetch_assoc()) {
                        
                            $sql_users =
                                "SELECT * FROM `users` WHERE approved = 1 AND  id ='" .
                                $row["user_id"] .
                                "'";
                            ($row_run = $db->query($sql_users)) or die($db->error);
                            $num_row = $row_run->num_rows;
                            $r = 1;
                            $row_users = $row_run->fetch_assoc();
                            $user_id = $row_users["id"];
                            $full_name = $row_users["full_name"];
                        
                            // if ($r == 1) {
                            ?> 
                     <tr>
                        <td style="width:25% !important;"><?php echo $full_name; ?></td>
                        <?php
                           if ($_REQUEST["cfrom_dt"] != "") {
                               $sstart_date = date(
                                   "Y-m-d",
                                   strtotime($_REQUEST["cfrom_dt"])
                               );
                               $send_date = date(
                                   "Y-m-d",
                                   strtotime($_REQUEST["cto_dt"])
                               );
                           } else {
                               $sstart_date = $fds;
                               $send_date = $tds;
                           }
                           while (strtotime($sstart_date) <= strtotime($send_date)) {
                           
                               $fp_query =
                                   "SELECT *,count(aau_id) as total_count FROM `assigned_article_user` WHERE DATE_FORMAT(a_date,'%Y-%m-%d')='" .
                                   date("Y-m-d", strtotime($sstart_date)) .
                                   "' and user_id='" .
                                   $row_users["id"] .
                                   "' ";
                               ($fp_run_query = $db->query($fp_query)) or
                                   die($db->error);
                               $fp_row = $fp_run_query->fetch_assoc();
                               if ($sstart_date == $yds) {
                                   if ($fp_row["total_count"] == 0) {
                                       $fp_id_count = "Not Alloted";
                                   } elseif (
                                       $fp_row["article_ids"] == "Null" ||
                                       $fp_row["article_ids"] == ""
                                   ) {
                                       $fp_id_count = "Done";
                                   } else {
                                       $fp_id_count = "Pending";
                                   }
                               } else {
                                   $fp_id_count = $fp_row["total_count"];
                               }
                               ?>
                        <td> <?php if ($sstart_date == $yds) {
                           if ($fp_row["total_count"] == 0) {
                               echo $fp_id_count = "Not Alloted";
                           } elseif (
                               $fp_row["article_ids"] == "Null" ||
                               $fp_row["article_ids"] == ""
                           ) {
                               echo $fp_id_count = "Done";
                           } else {
                               echo $fp_id_count =
                                   '<a href="pending-list.php?uid=' .
                                   $fp_row["user_id"] .
                                   "&pdate=" .
                                   $fp_row["a_date"] .
                                   '" target="_blank">Pending</a>';
                           }
                           } else {
                           $cnt_query =
                               "SELECT COUNT(wd.id) as num FROM `inw_inward_dtl` as wd, adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id and assigned_user_id='" .
                               $fp_row["user_id"] .
                               "' and  DATE_FORMAT(ce_pe_due_date,'%Y-%m-%d')='" .
                               $fp_row["a_date"] .
                               "' ";
                           ($customers = $db->query($cnt_query)) or $db->error;
                           $page_count = $customers->fetch_assoc();
                           $total_pages = $page_count["num"];
                           echo $fp_id_count =
                               '<a href="assigned-work-list.php?uid=' .
                               $fp_row["user_id"] .
                               "&pdate=" .
                               $fp_row["a_date"] .
                               '" target="_blank">' .
                               $page_count["num"] .
                               "</a>";
                           } ?>
                        </td>
                        <?php $sstart_date = date(
                           "Y-m-d",
                           strtotime(
                               "+1 days",
                           
                               strtotime($sstart_date)
                           )
                           );
                                   }
                                   ?>
                     </tr>
                     <?php // }
                        $r++;
                        // }
                        ?>
                     <?php
                        }
                        } ?>
                  </tbody>
               </table>
            </div>
            <?php
               } ?>
         </div>
         <!-- Table section ends -->
         <!-- Footer Section -->
         <?php echo footer(); ?>
         </div>
      </div>
        
      <!-- Footer Section -->
      <?php echo scripts(); ?>	
      <script src="assets/js/select2/select2.min.js"></script>
      <!-- inline scripts related to this page --> 
      <script type="text/javascript">
         jQuery(function ($) {
         
             //documentation : http://docs.jquery.com/Plugins/Validation/validate
         
             //datepicker plugin
             //link
             $('.date-picker').datepicker({
                 autoclose: true,
                 todayHighlight: true,
                 format: 'dd-mm-yyyy'
             })
         
             $('#validation-form').validate({errorClass: 'help-block'});
         
         })
      </script>
      <script>
         $(".customer_drop").select2({
           placeholder: "Select Customer",
         });
         $(".journal_drop").select2({
           placeholder: "Select Journal",
         });
         $(".stage_drop").select2({
           placeholder: "Select Stage",
         });
         $(".department_drop").select2({
           placeholder: "Select Department",
         });
         $(".platform_drop").select2({
           placeholder: "Select Platform",
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
         		// Handle changes in the "countries" select element
         $(document).ready(function() {
         
         $("#cust_id").on("change", function() {
            var cust_id=$.trim($(this).val());
         
         if($(this).val() && $(this).val().indexOf('all') !== -1){
               // Clear all other selections
               $('#cust_id').val([]);
               // Select all options except the "Select All" option
               $('#cust_id option').not(':first').prop('selected', true);
           } else {
               // If any other option is selected, unselect "Select All"
               if ($(this).val() && $(this).val().length === $('option', this).not(':first').length) {
                   $('#cust_id option[value=all]').prop('selected', false);
               }
           }
         
            // Fetch states based on the selected client name and populate the "name" select element
            $.ajax({
                url: "get_journal.php",
                dataType: "json",
                data: { cust_id:cust_id },
                success: function(data) {
                    populateSelecte("j_id",data);
         
                }
            });
         });
         
         
         });
         function populateSelecte(elementId, data) {
               var selectElement = $("#" + elementId);
               selectElement.empty();
         selectElement.append($("<option>").text("Select All").val("all"));
         
               $.each(data, function(index, item) {
                   selectElement.append($("<option>").text(item.j_code).val(item.j_id));
               });
           }
      </script>
      <script>
         $(document).ready(function () {
           var table = $("#table").DataTable({
             dom: "Bfrtip",
             buttons: [
               {
                 extend: "collection", // Use a collection button for the dropdown
                 text: "Export", // The text for the main button
                 buttons: [
                   "copyHtml5",
                   "excelHtml5",
                   "csvHtml5",
                   {
                     extend: "pdfHtml5",
                     customize: function (doc) {
                       // Customize the PDF content here (optional)
                     },
                   },
                   {
                     extend: "print",
                     autoPrint: false, // Disable automatic printing
                     customize: function (win) {
                       // Customize the printed content here (optional)
                     },
                   },
                 ],
               },
             ],
             lengthMenu: [10, 25, 50, 100],
           });
         
           // Add a click event to the main button to trigger the dropdown
           $(".dt-buttons").on("click", function () {
             $(".dt-button-collection").toggleClass("show");
           });
         });
      </script>
      <script type="text/javascript">
         $(document).ready(function () {
             $('#dynamic-table').DataTable({
                 "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
         
                 dom: 'Bfrtip',
                 buttons: [
                     'copy', 'csv', 'excel', 'pdf', 'print'
                 ]
             });
         
         });
      </script>
      <script type="text/javascript">
         $(document).ready(function () {
             $('#dynamic-table1').DataTable({
                 "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
         
                 dom: 'Bfrtip',
                 buttons: [
                     'copy', 'csv', 'excel', 'pdf', 'print'
                 ]
             });
         
         });
      </script>
      <script type="text/javascript">
         $(document).ready(function () {
             $('#dynamic-table2').DataTable({
                 "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
         
                 dom: 'Bfrtip',
                 buttons: [
                     'copy', 'csv', 'excel', 'pdf', 'print'
                 ]
             });
         
         });
      </script>
      <script type="text/javascript">
         $(document).ready(function () {
             $('#dynamic-table3').DataTable({
                 "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
         
                 dom: 'Bfrtip',
                 buttons: [
                     'copy', 'csv', 'excel', 'pdf', 'print'
                 ]
             });
         
         });
      </script>
      <script>
         function checkvalidate() {
             var radio = $("#radio_check").val();
             //var cust_id = $('#cust_id').val();
             //var j_id = $("#j_id").val();
             //var stage_id = $("#stage_id").val();
             var from_dt = $("#from_dt").val();
             var to_dt = $("#to_dt").val();
             var mfrom_dt = $("#mfrom_dt").val();
             var yfrom_dt = $("#yfrom_dt").val();
             if (radio == '') {
                 alert('Please Select Master Status');
                 return false;
             } /* else if (from_dt == '') {
                 alert('Please Select from date');
                 return false;
             } else if (to_dt == '') {
                 alert('Please Select to date');
                 return false;
             } else if (mfrom_dt == '') {
                 alert('Please Select month');
                 return false;
             } else if (yfrom_dt == '') {
                 alert('Please Select year');
                 return false;
             } */
         }
         $(document).ready(function () {
             $("#yearwise").click(function () {
                 $("#year_div").show();
                 $("#yfrom_dt").val(<?php echo date("Y"); ?>);
                 $("#month_div").hide();
                 $("#mfrom_dt").removeAttr('value');
                 $("#day_div").hide();
                 $("#from_dt").removeAttr('value');
                 $("#to_dt").removeAttr('value');				
             });
             $("#monthwise").click(function () {
         $("#month_div").show();
                 $("#year_div").hide();
         $("#day_div").hide();
                 $("#yfrom_dt").removeAttr('value');
                 $("#from_dt").removeAttr('value');				
                 $("#to_dt").removeAttr('value');				
         var vals=(date.getMonth()+1)+"-"+date.getFullYear();
                 $("#mfrom_dt").val(vals);
         
             });
             $("#daywise").click(function () {
                 $("#year_div").hide();
                 $("#yfrom_dt").removeAttr('value');
                 $("#month_div").hide();
                 $("#mfrom_dt").removeAttr('value');
                 $("#day_div").show();
         var vals=date.getDate()+"-"+(date.getMonth()+1)+"-"+date.getFullYear();
         $("#from_dt").attr("value");
         $("#to_dt").attr("value");
                 $("#from_dt").val(vals);
                 $("#to_dt").val(vals);
             });
         });
         $(".fydatepicker").datepicker({
             format: "yyyy",
             viewMode: "years",
             minViewMode: "years"
         });
         $(".mydatepicker").datepicker({
             format: "mm-yyyy",
             viewMode: "months",
             minViewMode: "months"
         });
         
         /*~~~~~~~~~~~~~ WIP add and remove the checked radio ~~~~~~~~~~~~~~~*/
         // Call the function when the document is ready
		 
		  <?php if (!isset($_REQUEST["radio"])) { ?>
    $(document).ready(function(){
         var radio_check = $("#wip").val();
                $("#wip_styl").addClass("reportbackgnd")
                $("#sfd_styl").removeClass("reportbackgnd")
                $("#dp_styl").removeClass("reportbackgnd")
                $("#cr_styl").removeClass("reportbackgnd")
                $("#cepe_styl").removeClass("reportbackgnd")
             var att = $(this).attr("checked");
             if (att === "checked") {
                $(this).removeAttr("checked");
               
                
         $("#day_div").css("display", "none");
             } else {
                 $(this).attr("checked", "checked");
                 $("#radio_check").val(radio_check);
                 $("#day_div").css("display", "none");
         $("#customer_select").css("display", "block");
         $("#journal_select").css("display", "block");		
         $("#wip_nodate").css("display", "none");
         $("#dept_wip").css("display", "block");
         $("#platform_dp").css("display", "block");
         $("#stage_cons").css("display", "block");
         $("#status_wip").css("display", "block");
         $("#date_sdf").css("display", "none");
         $("#cepe_fsdf").css("display", "none");
         $("#cepe_tsdf").css("display", "none");
             }
    });
		     $("#wip").on("click", function () {
         
         });
		  <?php }?>
		 
		 
         $("#wip").on("click", function () {
             var radio_check = $("#wip").val();
                $("#wip_styl").addClass("reportbackgnd")
                $("#sfd_styl").removeClass("reportbackgnd")
                $("#dp_styl").removeClass("reportbackgnd")
                $("#cr_styl").removeClass("reportbackgnd")
                $("#cepe_styl").removeClass("reportbackgnd")
             var att = $(this).attr("checked");
             if (att === "checked") {
                $(this).removeAttr("checked");
         $("#day_div").css("display", "none");
             } else {
                 $(this).attr("checked", "checked");
                 $("#radio_check").val(radio_check);
                 $("#day_div").css("display", "none");
         $("#customer_select").css("display", "block");
         $("#journal_select").css("display", "block");		
         $("#wip_nodate").css("display", "none");
         $("#dept_wip").css("display", "block");
         $("#platform_dp").css("display", "block");
         $("#stage_cons").css("display", "block");
         $("#status_wip").css("display", "block");
         $("#date_sdf").css("display", "none");
         $("#cepe_fsdf").css("display", "none");
         $("#cepe_tsdf").css("display", "none");
             }
         });
         /*~~~~~~~~~~~~~ add and remove the checked radio ~~~~~~~~~~~~~~~*/
         
         
         /*~~~~~~~~~~~~~ Schedules add and remove the checked radio ~~~~~~~~~~~~~~~*/
         $("#schedules").on("click", function () {

                $("#sfd_styl").addClass("reportbackgnd")
                $("#wip_styl").removeClass("reportbackgnd")
                $("#dp_styl").removeClass("reportbackgnd")
                $("#cr_styl").removeClass("reportbackgnd")
                $("#cepe_styl").removeClass("reportbackgnd")


             var radio_check = $("#schedules").val();
         
             var att = $(this).attr("checked");
             if (att === "checked") {
            
                 $(this).removeAttr("checked");
         $("#day_div").css("display", "none");
             } else {
                 $(this).attr("checked", "checked");
                 $("#radio_check").val(radio_check);
         $("#customer_select").css("display", "block");
         $("#journal_select").css("display", "block");		
         $("#day_div").css("display", "none");
         $("#dept_wip").css("display", "none");
         $("#wip_nodate").css("display", "none");
         $("#platform_dp").css("display", "block");
         $("#stage_cons").css("display", "block");
         $("#date_sdf").css("display", "block");
         $("#status_wip").css("display", "none");
         $("#cepe_fsdf").css("display", "none");
         $("#cepe_tsdf").css("display", "none");
         
             }
         
         });
         /*~~~~~~~~~~~~~ Schedules add and remove the checked radio ~~~~~~~~~~~~~~~*/
         
         /*~~~~~~~~~~~~~ delivery add and remove the checked radio ~~~~~~~~~~~~~~~*/
         $("#delivery").on("click", function () {

            $("#dp_styl").addClass("reportbackgnd")
                $("#sfd_styl").removeClass("reportbackgnd")
                $("#wip_styl").removeClass("reportbackgnd")
                $("#cr_styl").removeClass("reportbackgnd")
                $("#cepe_styl").removeClass("reportbackgnd")


             var radio_check = $("#delivery").val();
             var att = $(this).attr("checked");
             if (att === "checked") {
             
                 $(this).removeAttr("checked");
             } else {
                 $(this).attr("checked", "checked");
                 $("#radio_check").val(radio_check);
         $("#customer_select").css("display", "block");
         $("#journal_select").css("display", "block");		
                 // $("#dynamic-tables1").css("display", "block");
         $("#dept_wip").css("display", "none");
         $("#wip_nodate").css("display", "block");
         $("#platform_dp").css("display", "block");
         $("#stage_cons").css("display", "block");
         $("#date_sdf").css("display", "none");
         $("#status_wip").css("display", "none");
         $("#cepe_fsdf").css("display", "none");
         $("#cepe_tsdf").css("display", "none");
         }
         });
         /*~~~~~~~~~~~~~ delivery add and remove the checked radio ~~~~~~~~~~~~~~~*/
         
         /*~~~~~~~~~~~~~ detail-report add and remove the checked radio ~~~~~~~~~~~~~~~*/
         $("#detail-report").on("click", function () {
             var radio_check = $("#detail-report").val();
             var att = $(this).attr("checked");
             if (att === "checked") {
                 $(this).removeAttr("checked");
             } else {
                 $(this).attr("checked", "checked");
                 $("#radio_check").val(radio_check);
                 //$("#dynamic-tables2").css("display", "block");
         $("#dept_wip").css("display", "none");
         $("#wip_nodate").css("display", "block");
         $("#platform_dp").css("display", "none");
         $("#stage_cons").css("display", "block");
         $("#date_sdf").css("display", "none");
         $("#status_wip").css("display", "none");
         $("#cepe_fsdf").css("display", "none");
         $("#cepe_tsdf").css("display", "none");
         }
         });
         /*~~~~~~~~~~~~~ detail-report add and remove the checked radio ~~~~~~~~~~~~~~~*/
         
         /*~~~~~~~~~~~~~consolidated-report add and remove the checked radio ~~~~~~~~~~~~~~~*/
         $("#consolidated-report").on("click", function () {

            $("#cr_styl").addClass("reportbackgnd")
                $("#sfd_styl").removeClass("reportbackgnd")
                $("#dp_styl").removeClass("reportbackgnd")
                $("#wip_styl").removeClass("reportbackgnd")
                $("#cepe_styl").removeClass("reportbackgnd")


             var radio_check = $("#consolidated-report").val();
             var att = $(this).attr("checked");
             if (att === "checked") {
               
                 $(this).removeAttr("checked");
             } else {
                 $(this).attr("checked", "checked");
                 $("#radio_check").val(radio_check);
         $("#customer_select").css("display", "block");
         $("#journal_select").css("display", "block");		
         $("#dept_wip").css("display", "none");
         $("#wip_nodate").css("display", "block");
         $("#platform_dp").css("display", "block");
         $("#stage_cons").css("display", "none");
         $("#date_sdf").css("display", "none");
         $("#status_wip").css("display", "none");
         $("#cepe_fsdf").css("display", "none");
         $("#cepe_tsdf").css("display", "none");
         
         
             }
         });
         /*~~~~~~~~~~~~~ consolidated-report add and remove the checked radio ~~~~~~~~~~~~~~~*/
         
         /*~~~~~~~~~~~~~ CEPE-report add and remove the checked radio ~~~~~~~~~~~~~~~*/
         $("#cepe").on("click", function () {

            $("#cepe_styl").addClass("reportbackgnd")
                 $("#sfd_styl").removeClass("reportbackgnd")
                $("#dp_styl").removeClass("reportbackgnd")
                $("#cr_styl").removeClass("reportbackgnd")
                $("#wip_styl").removeClass("reportbackgnd")


             var radio_check = $("#cepe").val();
             var att = $(this).attr("checked");
             if (att === "checked") {
                 $(this).removeAttr("checked");
              
             } else {
                 $(this).attr("checked", "checked");
                 $("#radio_check").val(radio_check);
                 // $("#dynamic-tables1").css("display", "block");
         $("#dept_wip").css("display", "none");
         $("#wip_nodate").css("display", "none");
         $("#platform_dp").css("display", "none");
         $("#stage_cons").css("display", "none");
         $("#date_sdf").css("display", "none");
         $("#status_wip").css("display", "none");
         $("#cepe_fsdf").css("display", "block");
         $("#cepe_tsdf").css("display", "block");
         $("#customer_select").css("display", "none");
         $("#journal_select").css("display", "none");				
             }
         });
         /*~~~~~~~~~~~~~ CEPE-report add and remove the checked radio ~~~~~~~~~~~~~~~*/
         
         <?php if ($_GET["radio"] == "wip") { ?>
             $("#dynamic-tables").css("display", "block");
             $("#dynamic-tables1").css("display", "none");
             $("#dynamic-tables2").css("display", "none");
             $("#dynamic-tables3").css("display", "none");
             $("#wip_nodate").css("display", "none");
         $("#platform_dp").css("display", "block");
             $("#list1").css("display", "none");
         $("#stage_cons").css("display", "block");
         $("#date_sdf").css("display", "none");
         $("#status_wip").css("display", "block");     
         $("#consolidated-report-tables").css("display", "none");
         $("#cepe_fsdf").css("display", "none");
         $("#cepe_tsdf").css("display", "none");
         
         <?php } elseif (
            isset($radio_check) &&
            ($radio_check == "wip" || $_GET["radio"] == "wip")
            ) { ?>
             $("#dynamic-tables").css("display", "block");
             $("#dynamic-tables1").css("display", "none");
             $("#dynamic-tables2").css("display", "none");
             $("#dynamic-tables3").css("display", "none");
             $("#wip_nodate").css("display", "none");
         $("#platform_dp").css("display", "block");
             $("#list1").css("display", "none");
         $("#stage_cons").css("display", "block");
         $("#date_sdf").css("display", "none");
         $("#status_wip").css("display", "block");  
             $("#consolidated-report-tables").css("display", "none");
         $("#cepe_fsdf").css("display", "none");
         $("#cepe_tsdf").css("display", "none");
         
         <?php } elseif (isset($radio_check) && $radio_check == "sfd") { ?>
             $("#dynamic-tables").css("display", "none");
             $("#dynamic-tables1").css("display", "none");
             $("#dynamic-tables2").css("display", "none");
             $("#dynamic-tables3").css("display", "block");
         $("#wip_nodate").css("display", "none");
         $("#date_sdf").css("display", "block");
         $("#dept_wip").css("display", "none");
         $("#platform_dp").css("display", "block");
             $("#list1").css("display", "none");
         $("#stage_cons").css("display", "block");
         $("#status_wip").css("display", "none");  
             $("#consolidated-report-tables").css("display", "none");
         $("#cepe_fsdf").css("display", "none");
         $("#cepe_tsdf").css("display", "none");
         <?php } elseif (isset($radio_check) && $radio_check == "dp") { ?>
         //alert('hi');
             $("#dynamic-tables").css("display", "none");
             $("#dynamic-tables1").css("display", "block");
             $("#dynamic-tables2").css("display", "none");
             $("#dynamic-tables3").css("display", "none");
         $("#dept_wip").css("display", "none");
         $("#platform_dp").css("display", "block");
             $("#list1").css("display", "block");
         $("#stage_cons").css("display", "block");
         $("#date_sdf").css("display", "none");
         $("#status_wip").css("display", "none");  
             $("#consolidated-report-tables").css("display", "none");
         $("#cepe_fsdf").css("display", "none");
         $("#cepe_tsdf").css("display", "none");
         
         
         <?php } elseif (isset($radio_check) && $radio_check == "dr") { ?>
             $("#dynamic-tables").css("display", "none");
             $("#dynamic-tables1").css("display", "none");
             $("#dynamic-tables2").css("display", "block");
             $("#dynamic-tables3").css("display", "none");
         $("#dept_wip").css("display", "none");
         $("#platform_dp").css("display", "none");
             $("#list1").css("display", "none");
         $("#date_sdf").css("display", "none");
         $("#status_wip").css("display", "none");  
             $("#consolidated-report-tables").css("display", "none");
         $("#cepe_fsdf").css("display", "none");
         $("#cepe_tsdf").css("display", "none");
         
         <?php } elseif (isset($radio_check) && $radio_check == "cr") { ?>
             $("#dynamic-tables").css("display", "none");
             $("#dynamic-tables1").css("display", "none");
             $("#dynamic-tables2").css("display", "none");
             $("#dynamic-tables3").css("display", "none");
         $("#dept_wip").css("display", "none");	
         $("#platform_dp").css("display", "block");
             $("#list1").css("display", "none");
         $("#stage_cons").css("display", "none");
         $("#date_sdf").css("display", "none");
         $("#status_wip").css("display", "none");  
             $("#consolidated-report-tables").css("display", "block");
         $("#cepe_fsdf").css("display", "none");
         $("#cepe_tsdf").css("display", "none");
         
         
         <?php } elseif (isset($radio_check) && $radio_check == "ce") { ?>
         //alert('hi');
             $("#dynamic-tables").css("display", "none");
             $("#dynamic-tables1").css("display", "none");
             $("#dynamic-tables2").css("display", "none");
             $("#dynamic-tables3").css("display", "none");
         $("#dept_wip").css("display", "none");
         $("#customer_select").css("display", "none");
         $("#journal_select").css("display", "none");
         $("#wip_nodate").css("display", "none");
         $("#platform_dp").css("display", "none");
             $("#list1").css("display", "none");
         $("#stage_cons").css("display", "none");
         $("#date_sdf").css("display", "none");
         $("#status_wip").css("display", "none");  
             $("#cepe-report-tables").css("display", "block");
         $("#cepe_fsdf").css("display", "block");
         $("#cepe_tsdf").css("display", "block");
         
         
         <?php } ?>
         
      </script>
   </body>
</html>