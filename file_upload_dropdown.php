
    <link href='https://fonts.googleapis.com/css?family=Open Sans' rel='stylesheet'>
    <style>
        body{
            font-family: 'Open Sans';
        }
        /* Modal styles */
        .modalcss {
            display: none;
            position: absolute;
            z-index: 1;
            /* left: 0; */
            top: auto;
            width: auto;
            height: 100%;
            /* overflow: auto; */
            /* background-color: rgba(0, 0, 0, 0.5); */
            padding-top: 60px;
        }
        .contentcss{
            padding:0px 10px;
            height: auto;
            width:auto;
        }
        .modal-contentcss {
            background-color: #fff;
            /* margin: 5% auto; */
            border: 1px solid rgba(187, 194, 199, 0.56);
            width: auto;
            height: auto;
            border-radius: 24px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .close {
            color: #aaa;
            /* float: right; */
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: #000;
        }

        .heading {
            /* margin-top: 0; */
            font-size: 20px;
            color: #333;
			font-weight:bold;
            /* text-align: left; */
            /* margin-bottom: 20px; */
        }
		.formgroup select{
			appearance: none; /* Remove default arrow */
   			-webkit-appearance: none; /* For Safari */
    		-moz-appearance: none; /* For Firefox */
    		background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>') no-repeat right 15px center; 
		}
		.formgroup select option {
		background-color: #F0F0F2; 
		color: black;
		}
        /* Form styles */
        /* .formgroup {
            margin-bottom: 15px;
        } */

        .formgroup1 label {
            display: block;
            /* margin-bottom: 12px; */
            /* font-weight: bold; */
            color: #222;
            font-size: 14px;
            padding: 32px 0px;
        }

        .formgroup label {
            display: block;
            margin-bottom: 12px;
            /* font-weight: bold; */
            color: #222;
            font-size: 14px;
            /* padding-top: 8px; */
        }

        .formgroup select,
        .formgroup input,
        .formgroup textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 12px;
            font-size: 14px;
            box-sizing: border-box;
            font-family: 'Open Sans';
        }

        .formgroup textarea {
            resize: vertical;
        }

        /* Button styles */
        .button-group {
            text-align: right;
            margin-top: 20px;
        }

        .button-group button {
            padding: 7px;
            border: none;
            border-radius:12px;
            width: 109px;
            height:48px;
            font-size: 14px;
            cursor: pointer;
            margin-left: 10px;
        }

        .button-group .cancel {
            background-color: #f44336;
            color: white;
        }

        .button-group .cancel:hover {
            background-color: #d32f2f;
        }

        .button-group .submit {
            background-color: #4CAF50;
            color: white;
        }

        .button-group .submit:hover {
            background-color: #388e3c;
        }

        /* Source and Forum row */
        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .row .formgroup {
            flex: 1;
            margin-right: 10px;
        }

        .row .formgroup:last-child {
            margin-right: 0;
        }

        /* Button styles for Work, Source, Forum */
        .button-row {
            display: flex;
            align-items: center;
            background-color: #f9f9f9;
            padding: 16px 0px;
            justify-content: center;
            gap: 15px;
            /* margin-bottom: 15px; */
        }

        .button-row button,.button-row a {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 12px;
            background-color: #f9f9f9;
            height:32px;
            width:auto;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        .button-row button.active {
            background-color: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }

        /* Radio button styles */
        .radio-group {
            display: flex;
            gap: 10px;
            /* margin-bottom: 15px; */
        }

        .radio-group label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            font-size: 14px;
            cursor: pointer;
        }
        .radio-group input:hover{
        accent-color:#e7530d38 !important;
        }
        input[type="radio"]{
        accent-color:#e7530d38;
        height: 16px;
        width: 16px;
        }
        .divied{
             padding: 20px;
             width:auto
        }



/* Responsive Styles */
@media screen and (max-width: 768px) {
	.modalcss {
            display: none;
            position: absolute;
            z-index: 1002;
            left: 0;
            top: 0;
            width: auto;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            padding-top: 60px;
        }
	
	.modal-contentcss {
        width: auto;
        max-width: auto;
        height: auto;
    }

    .button-group {
    display:flex;
    gap: 10px;
    }

    .button-group button {
        width: 50%;
    }

    .row {
        flex-direction: column;
        gap: 10px;
    }

    .button-row {
        flex-wrap: wrap;
    }
}

@media screen and (max-width: 480px) {
	.modalcss {
            display: none;
            position: absolute;
            z-index: 1002;
            left: 0;
            top: 0;
            width: auto;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            padding-top: 60px;
        }
    .modal-contentcss {
        width: auto;
        max-width: auto;
        height:auto;
    }

    .heading {
        font-size: 18px;
    }
    .formgroup1{
        padding-top: 32px;
        flex-direction: column;
    }
    .formgroup1 label {
    padding: 0%;
    }
    
    .formgroup label {
        font-size: 12px;
    }

    .formgroup select,
    .formgroup textarea {
        font-size: 12px;
    }

    .button-group {
        display:flex;
        align-content: center;
    }

    .button-group button {
        width: 50%;
    }

    .button-row {
        display:flex;
        flex-wrap: nowrap;
        gap: 10px;
    }

    .radio-group {
      padding-bottom: 20px;
    }
}
</style>


<!-- Button to open the modal -->
<!-- <div class="form_btn cancelbtn">
    <button id="openModal" class="btn btn-sm" style="text-decoration:none;color:gray;cursor:pointer;border: 1px solid gray;font-size:0.85em;width: fit-content; height:25px">File Update Status &#x2B9F;</button></div> -->

<!-- Modal -->
<div id="myModal" class="modalcss">
    <div class="contentcss">
    <div class="modal-contentcss">
	<?php 
	if($app_res['cust_id']!='76')
	{
	?>
    <div class="divied" style="padding-bottom:0px;border-bottom: 1px solid rgba(187, 194, 199, 0.56);">
        <div style="display: flex; align-items: center;justify-content: space-between;">
            <h2 class="heading">Update your file status</h2>
            <span class="close" style="padding: 16px 0px;">&times;</span>
        </div>
    </div>
	<?php 
	}
	?>
    <div class="divied" style="padding-top:0px;">
        <!-- <form  class="form-horizontal" id="validation-form" method="post"> -->
        <?php 
			//echo "folder access :".$_COOKIE['folder_access'];
			if($_COOKIE['folder_access']=='DOMAIN' || $_COOKIE['folder_access']=='BOTH' )
			{
				$work_dir = $app_res['work_dir'];
				$work_dir = str_replace(WF_DIR,TOOL_DIR,$work_dir);
				
				// Use preg_replace to remove the "peerj-" prefix
				$updatedFilename = preg_replace('/^peerj-/', '', $app_res['pub_id']);
                        ?>
            <!-- Work, Source, Forum buttons -->
            <div class="button-row">
                <a type="button" id="sourceButton" style="border-color:#1337A2; background-color: #E4EAFC; color: #1337A2; font-weight: 600;" href="file:///<?php echo str_replace('production','downloads',$work_dir).$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']; ?>" target="_blank"><svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M16.254 10.725L15.954 14.475C15.8415 15.6225 15.7515 16.5 13.719 16.5H4.28403C2.25153 16.5 2.16153 15.6225 2.04903 14.475L1.74903 10.725C1.68903 10.1025 1.88403 9.525 2.23653 9.0825C2.24403 9.075 2.24403 9.075 2.25153 9.0675C2.66403 8.565 3.28653 8.25 3.98403 8.25H14.019C14.7165 8.25 15.3315 8.565 15.7365 9.0525C15.744 9.06 15.7515 9.0675 15.7515 9.075C16.119 9.5175 16.3215 10.095 16.254 10.725Z" stroke="#1337A2" stroke-width="1.5" stroke-miterlimit="10"/>
                    <path d="M2.625 8.57148V4.70898C2.625 2.15898 3.2625 1.52148 5.8125 1.52148H6.765C7.7175 1.52148 7.935 1.80648 8.295 2.28648L9.2475 3.56148C9.4875 3.87648 9.63 4.07148 10.2675 4.07148H12.18C14.73 4.07148 15.3675 4.70898 15.3675 7.25898V8.60148" stroke="#1337A2" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M7.07227 12.75H10.9273" stroke="#1337A2" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>Source</a>
                <a type="button" id="workButton" style="border-color:#901D0F; background-color: #FDE5E4; color: #901D0F;  font-weight: 600;" href="file:///<?php echo $work_dir.$app_res['volume_folder']."/".$app_res['pub_id']."/".$app_res['stage']."/".$pdept_res['dept_name']."/"; ?>" target="_blank"><svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M16.5 7.5V11.25C16.5 15 15 16.5 11.25 16.5H6.75C3 16.5 1.5 15 1.5 11.25V6.75C1.5 3 3 1.5 6.75 1.5H10.5" stroke="#901D0F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M16.5 7.5H13.5C11.25 7.5 10.5 6.75 10.5 4.5V1.5L16.5 7.5Z" stroke="#901D0F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M5.25 9.75H9.75" stroke="#901D0F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M5.25 12.75H8.25" stroke="#901D0F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Work</a>
                   <?php  if($app_res['cust_id']=="33"){
				$stage_rev=preg_replace("/REV([0-9]+)/","REV",$app_res['stage']);
                if($stage_rev='REV')
                {
                ?>
                <a type="button" id="forumButton" style="border-color:#4C0F97; background-color: #EDDFFB; color: #4C0F97;  font-weight: 600;" href="<?php echo $journal_info['j_link'];?><?php echo $updatedFilename;?>" target="_blank"><svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 6.1875V13.5C15 15.75 13.6575 16.5 12 16.5H6C4.3425 16.5 3 15.75 3 13.5V6.1875C3 3.75 4.3425 3.1875 6 3.1875C6 3.6525 6.18748 4.0725 6.49498 4.38C6.80248 4.6875 7.2225 4.875 7.6875 4.875H10.3125C11.2425 4.875 12 4.1175 12 3.1875C13.6575 3.1875 15 3.75 15 6.1875Z" stroke="#4C0F97" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M12 3.1875C12 4.1175 11.2425 4.875 10.3125 4.875H7.6875C7.2225 4.875 6.80248 4.6875 6.49498 4.38C6.18748 4.0725 6 3.6525 6 3.1875C6 2.2575 6.7575 1.5 7.6875 1.5H10.3125C10.7775 1.5 11.1975 1.6875 11.505 1.995C11.8125 2.3025 12 2.7225 12 3.1875Z" stroke="#4C0F97" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M6 9.75H9" stroke="#4C0F97" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M6 12.75H12" stroke="#4C0F97" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Forum</a>
                    <?php 
						}
	
                   
                    ?>
                <?php 
                }
                if(($app_res['department_id'] == 1 || $app_res['department_id'] == 2 || $app_res['department_id'] == 5) && ($app_res['cust_id'] == 7 || $app_res['cust_id'] == 33 || $app_res['cust_id'] == 42 || $app_res['cust_id'] == 37 || $app_res['cust_id'] == 68 || $app_res['cust_id'] == 66 || $app_res['cust_id'] == 43 || $app_res['cust_id'] == 76  || $app_res['cust_id'] == 46 || $app_res['cust_id'] == 64 || $app_res['cust_id'] == 82 || $app_res['cust_id'] == 84 || $app_res['cust_id'] == 69))
                    {
					?>
					<a type="button" id="ceButton" style="border-color:#0F9797; background-color: #D4E9E9; color: #0F9797;  font-weight: 600;" href="http://<?php echo $ceditor_ip; ?>/Transforma/ce_editor/home/edit_file_users/<?php echo $app_res['activity']; ?>/<?php echo $app_res['pub_id']; ?>/<?php echo $_COOKIE['user_login_id']; ?>" target="_blank">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
<path d="M8.25 1.5H6.75C3 1.5 1.5 3 1.5 6.75V11.25C1.5 15 3 16.5 6.75 16.5H11.25C15 16.5 16.5 15 16.5 11.25V9.75" stroke="#0F9797" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M12.0304 2.26397L6.1204 8.17397C5.8954 8.39897 5.6704 8.84147 5.6254 9.16397L5.3029 11.4215C5.1829 12.239 5.7604 12.809 6.5779 12.6965L8.8354 12.374C9.1504 12.329 9.5929 12.104 9.8254 11.879L15.7354 5.96897C16.7554 4.94897 17.2354 3.76397 15.7354 2.26397C14.2354 0.763969 13.0504 1.24397 12.0304 2.26397Z" stroke="#0F9797" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M11.1836 3.11328C11.6861 4.90578 13.0886 6.30828 14.8886 6.81828" stroke="#0F9797" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
</svg> CE Editor
					</a>
                    <?php 
					  }
                    }
          
          
					/* Adding this for 94 XML editor 
					Swamy - 09-08-2022 
					Adding the records in DB
					Provide the link to PAG User */
					$copy_files = true;
					$temp_dept = $app_res['status'];
					if(strpos($app_res['status'], "PAG") !== false)
					{
						$app_res['status'] = "PAG";
					}
					if(strpos($app_res['status'], "CE") !== false)
					{
						$app_res['status'] = "CE";
					}
					
					//// Checking in Transactions table to stop the copy files to Edit Latex Server ////
					$transaction_query = "select count(id) cnt from `inw_transactions` where dept='".$app_res['department_id']."' and `project_id` = '".$app_res['id']."' and `stage` = '".$app_res['stage']."' ";
					$transaction_res = $db->query($transaction_query); 					
					$trans_res_row = $transaction_res->fetch_assoc();
					//print_r($trans_res_row);
					if($trans_res_row['cnt'] > 1)
					{
						$copy_files = false;
					}
					  /* if(($customer_info['cust_name'] == "SIF-Editlatex" || $customer_info['cust_name'] == "SIF" || $customer_info['cust_name'] =="Transforma") && $app_res['stage'] != "FIN1" && $app_res['status'] == "PAG" || $app_res['status'] == "Pre-Editing" && ($journal_info['j_code'] =="EPL" || $journal_info['j_code'] =="NCC")){
						include("94_edit_latex_url.php");
					  } 
					  // Added on 19-10-2022 for JSIAM Customer
					  if($customer_info['cust_name'] =="IAP" && $app_res['stage'] != "FIN1" && ($app_res['status'] == "PAG" || $app_res['status'] == "CE" || $app_res['status'] == "Pre-Editing") && $journal_info['j_code'] =="JSIAML")
					  {
						if($app_res['status'] == "PAG"  && $app_res['stage'] == "FP")
						{
							$copy_files = false;
						}
						include("94_edit_latex_url.php");
					  }  */
					  $app_res['status'] = $temp_dept;
?>
						  <?php
						  if($app_res['cust_id']=="33" && $app_res['stage']=='FP' && in_array((int)$app_res['department_id'], array(1,5,9,10,14)))
						  {
						  ?>
						  <a type="button" id="metaHtmlButton" style="border-color:#0B5394; background-color: #DCEBFA; color: #0B5394; font-weight: 600; cursor:pointer;">
						  <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
						  <path d="M6 5.25L2.25 9L6 12.75" stroke="#0B5394" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						  <path d="M12 5.25L15.75 9L12 12.75" stroke="#0B5394" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						  <path d="M10.125 3.75L7.875 14.25" stroke="#0B5394" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						  </svg>
						  METADATA HTML
						  </a>
						  <?php
						  }
						  ?>
					  <?php
					  if($style_validator_required)
					  {
					  ?>
					  <a type="button" id="styleValidatorButton" style="border-color:#0F6B3A; background-color: #D9F2E3; color: #0F6B3A; font-weight: 600; cursor:pointer;">
					  <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
					  <path d="M8.25 1.5H6.75C3 1.5 1.5 3 1.5 6.75V11.25C1.5 15 3 16.5 6.75 16.5H11.25C15 16.5 16.5 15 16.5 11.25V9.75" stroke="#0F6B3A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
					  <path d="M6.5625 9.375L8.0625 10.875L11.4375 6.75" stroke="#0F6B3A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
					  </svg>
					  Style Validator
					  </a>
					  <a type="button" id="bypassValidatorButton" style="border-color:#997404; background-color: #FFF3CD; color: #997404; font-weight: 600; cursor:pointer;">
					  <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
					  <path d="M9 6.75V9.75" stroke="#997404" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
					  <path d="M9 12.375C9.31066 12.375 9.5625 12.1232 9.5625 11.8125C9.5625 11.5018 9.31066 11.25 9 11.25C8.68934 11.25 8.4375 11.5018 8.4375 11.8125C8.4375 12.1232 8.68934 12.375 9 12.375Z" fill="#997404"/>
					  <path d="M7.7175 1.995L1.1925 13.3575C0.6825 14.2425 1.32 15.375 2.3625 15.375H15.4125C16.455 15.375 17.0925 14.2425 16.5825 13.3575L10.0575 1.995C9.5325 1.0875 8.2425 1.0875 7.7175 1.995Z" stroke="#997404" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
					  </svg>
					  Bypass Validation
					  </a>
					  <?php
					  }
					  ?>
            </div>
            <div id="result_meta_html" style="display:none; margin:10px 0; padding:10px; border:1px solid #ccc; border-radius:12px; max-height:300px; overflow:auto;"></div>

            <!-- Choose status as radio buttons -->
            <div class="formgroup1" style="display:flex; gap:24px;">
                <!--<label>Choose status <span style="color:red;">*</span></label>-->
                <div class="radio-group">
                    <label>
                        <input type="radio" name="status" class="status" value="1" style="height: 16px; width: 16px;"> Completed
                    </label>
                    <label>
                        <input type="radio" name="status" class="status" value="2"> Pending
                    </label>
                    <label>
                        <input type="radio" name="status" class="status" value="6"> Assigned
                    </label>
					<label>
                        <input type="radio" name="status" class="status" value="0" > Others
                    </label>
                </div>
            </div>

            <!-- Page count and Slip to department row -->
            <div class="row">
                <div class="formgroup">
                    <label for="pageCount">Page count <span style="color:red;">*</span></label>
                    <input  type="number" id="page_count" name="page_count" required placeholder="Page Count" value="<?php echo ($app_res['page_count'] > 0) ? $app_res['page_count'] : '0'; ?>" onkeyup="PreventFirstZero(event);">
                </div>
				
				 <div class="formgroup" id="other_status" style="display:none;">
                        <label class="mb-2" >Select Other Status :<span style="color: red">*</span></label>
                            <select id="ostatus" name="ostatus" class="form-select customer_drop" data-placeholder="Choose">
                              <option value="">-Select-</option>
								<?php foreach($project_status_others_array as $id=>$val){?>
                                     <option value="<?php echo $id; ?>" ><?php echo $val; ?></option>
                                 <?php } ?>
                            </select>
                 </div>
					   
					   
                <?php $workflow_query = "SELECT wf_code FROM `adm_workflow_master` WHERE
    `wf_id` =".$app_res['wf_id']."";
    $workflow_details = $db->query($workflow_query) or
    ($db->error);
    $workflow_info = $workflow_details->fetch_assoc();
    $all_dpt_info = explode(',',$workflow_info['wf_code']);
    $all_dept_array = $all_dpt_info;
    if (!in_array("9", $all_dpt_info))
    {			
        $all_dpt_info[]="9"; 
    } ?>
                <?php if($journal_info['skip_dept'] == '1'){ ?>
                <div class="formgroup">
                    <label for="department">Skip to department <span style="color:red;">*</span></label>
					<?php

	$query = "SELECT * FROM `adm_dept_master` WHERE `parent_id`
		= 0 AND dept_code IN (".implode(",",$all_dept_array).") and dept_code !=
		'".$app_res['department_id']."' ";
	    $customers = $db->query($query) or ($db->error);
	?>
                    <select id="skip_dept" name="skip_dept">
					<option value="">-Select-</option>
                              <?php while($row = $customers->fetch_assoc()){ ?>
                              <option value="<?php echo $row['dept_code']; ?>" <?php if(isset($app_res['parent_id'])?$app_res['parent_id']:0 == $row['dept_code']){?> selected <?php } ?>><?php echo $row['dept_name']; ?></option>
                              <?php } ?>
                    </select>
                </div>
                <?php } ?>
                        </div>
			<div class="formgroup" id="assign_department_div" style="display:none;">
                <?php
                	$query = "SELECT * FROM `adm_dept_master` WHERE `parent_id`
                    = 0 AND dept_code IN (".implode(",",$all_dpt_info) .") and dept_code NOT IN ( ".$app_res['department_id'].",15 ) ORDER BY `dept_name` ASC";
                    $customers = $db->query($query) or ($db->error);
                ?>
                    <label for="assign_department">Assign Department <span style="color:red;">*</span></label>
                    <select id="assign_department" name="assign_department">
					<option value="">-Select-</option>
                              <?php while($row = $customers->fetch_assoc()){ ?>
                              <option value="<?php echo $row['dept_code']; ?>" <?php if(isset($app_res['parent_id'])?$app_res['parent_id']:0 == $row['dept_code']){?> selected <?php } ?>><?php echo $row['dept_name']; ?></option>
                              <?php } ?>
                    </select>
                </div>
                <div class="form-group" id="assign_user" style="display:none;">
                        <label class="mb-2" >Select User :<span style="color: red">*</span></label>

                        
                       <div class="col-12 col-sm-6 col-lg-4">
                          <div class="clearfix">
                            <select id="a_user_id" name="a_user_id" class="form-select customer_drop" data-placeholder="Choose">
                              <option value="">-Select-</option>
                            </select>
                          </div>
                        </div>
                       </div>
					   
					   
            <!-- Remarks -->
            <div class="formgroup" id="tmpa">
                <label for="remarks">Remarks <span style="color:red;">*</span></label>
                <textarea name="vcomments" rows="4" placeholder="Write your Remarks" ></textarea>
            </div>
            <div class="formgroup" id="notmpa" style="display:none;">
                <label for="remarks">Remarks <span style="color:red;">*</span></label>
                <textarea name="comments" rows="4" placeholder="Write your Remarks" ></textarea>
            </div>
            <div class="formgroup"  id="query_div" style="display:none;">
                <label for="remarks">Remarks <span style="color:red;">*</span></label>
                <textarea name="message_query" id="message" rows="4" placeholder="Write your Remarks" ></textarea>
            </div>

            <!-- Buttons -->
            <div class="button-group">
                <button type="button" style="color:black; background-color:rgba(187, 194, 199, 0.16); border: 1px solid rgba(187, 194, 199, 0.56);" class="cancel">Cancel</button>
                <button type="submit" id="submit-btnform" style="color:white; background-color: #010101" class="submit" onclick="submitform()">Submit</button>
            </div>
        <!-- </form> -->
        </div>
    </div>
</div>
</div>
<div class="modal fade" id="tableModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Checklist Form</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="    height: 10px;
    width: 10px;
    margin-right: 10px;
    font-size: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 7px;
"> &times;</button>
      </div>
      <div class="modal-body">
							<div class="row" id="checklist_table" style="display:none;">
                            <div class="col-xs-12"> 
                              <!-- PAGE CONTENT BEGINS -->
                              <div class="row">
                                <div class="col-xs-12 pt-3">
                                  <table  id="simple-table" class="table  table-bordered table-hover">
                                    <thead>
                                      <tr>
                                        <th id="focus" tabindex="0">Details</th>
                                        <th width="10%">Yes</th>
                                        <th width="10%">NA</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                    <?php
		$query = "SELECT * FROM `jbm_checklist` WHERE (`stage` = '".$app_res['stage']."' OR `stage` = 'BOTH') AND `dept_id` = '".$app_res['department_id']."' ORDER BY `display_order` ASC ";
		$qry_result = $db->query($query) or ($db->error);		
		while($row = $qry_result->fetch_assoc()){ ?>
		
                                      <tr>
                                        <td><?php echo $row['description']; ?></td>
                                        
<td>
                                            <input class="required" type="radio" name="checklist[<?php echo $row['cl_id']; ?>]" value="Yes" checked="checked"/>
                                            </td><td>
                                            <input class="required" type="radio" name="checklist[<?php echo $row['cl_id']; ?>]" value="NA"/>
                                            </td>
                                      </tr>
                                      <?php } ?>
                                    </tbody>
                                  </table>
                                </div>
                              </div>
                            </div>
                          </div>
						  </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<script>
    	
    // JavaScript to handle modal open/close
    var modal = document.getElementById("myModal");
    var btn = document.getElementById("openModal");
    var span = document.getElementsByClassName("close")[0];
    var cancelBtn = document.querySelector(".cancel");

    // btn.onclick = function() {
	function buton(){
		if(modal.style.display=="none" || modal.style.display==""){
			$('.down').hide();
			$('.up').show();
			modal.style.display = "block";
		}else{
			$('.up').hide();
			$('.down').show();
			modal.style.display = "none";
		}
    }

    span.onclick = function() {
		$('.up').hide();
		$('.down').show();
        modal.style.display = "none";
    }

    cancelBtn.onclick = function() {
		$('.up').hide();
		$('.down').show();
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == modal) {
			$('.up').hide();
			$('.down').show();
            modal.style.display = "none";
        }
    }

    // // JavaScript to handle button active state
    // const workButton = document.getElementById('workButton');
    // const sourceButton = document.getElementById('sourceButton');
    // const forumButton = document.getElementById('forumButton');

    // [workButton, sourceButton, forumButton].forEach(button => {
    //     button.addEventListener('click', () => {
    //         // Remove active class from all buttons
    //         [workButton, sourceButton, forumButton].forEach(btn => btn.classList.remove('active'));
    //         // Add active class to the clicked button
    //         button.classList.add('active');
    //     });
    // });
    $('.status').on('click',function()
{
	var status = $(this).val();
	if(status==2 || status == 6)
	{
	$('#tmpa').show();
	$('#notmpa').hide();
	}
	else
	{
	$('#notmpa').show();
	$('#tmpa').hide();
	}
	if(status == 6)
	{
	$('#assign_department_div').show();
	$('#assign_department').focus();
	}
	else
	{
	$('#assign_department_div').hide();
	}
	if(status ==1)
	{
	$('#checklist_table').show();
	var myModal = new bootstrap.Modal(document.getElementById('tableModal'));
	myModal.show();
	}
	else
	{
	$('#checklist_table').hide();      
	}
	if(status == 0)
	{
	$('#other_status').show();
	}
	else
	{
	$('#other_status').hide();
	}
  });
 function submitform(){
	$('#submit-btn').trigger('click');
};
</script>
