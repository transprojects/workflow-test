<?php include "../includes/includes-sw2.php";?>
<?php 
include_once("../smtpmail/phpmailer/class.phpmailer.php");
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('../includes/title.php'); ?>
    <?php echo styles(); ?>	

    <!-- Data Table CSS -->
    <style>
th  {
  color: #336199 !important;
  background-color: #fff1f1 !important;
  border-top: 1px solid #dedef3 !important;
  border-left: 1px solid #dedef3 !important;
  border-right: 1px solid #dedef3 !important;
}
 td {
  color: #336199 !important;
  background-color: #fff !important;
  border-top: 1px solid #dedef3 !important;
  border-right: 1px solid #dedef3 !important;
  border-left: 1px solid #dedef3 !important;
}
/* td:last-child{
	border-right:  1px solid #dedef3 !important;
} */
table.dataTable tbody td:not(:last-child):after {
    content: "";
    height: 15px;
    width: 0.5px;
    border: none !important;
    position: absolute;
    right: 0;
    top: 20px;
}
.startnav-clock{
	right: 0 !important;
	-webkit-box-shadow: 0px 0px 5px 1px rgba(242,103,34,0.62) !important;
	-moz-box-shadow: 0px 0px 5px 1px rgba(242,103,34,0.62) !important;
	box-shadow: 0px 0px 5px 1px rgba(242,103,34,0.62) !important;
	background-color: #ec5f19db !important;
	color: #fff !important;
	border-radius: 22px !important;
	font-weight: 600 !important;

}

/* Fullscreen Transparent Overlay */
#overlay {
    display: none; /* Hidden by default */
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5); /* Slight opacity for the background */
    z-index: 9998; /* Below the loader */
}

/* Loader Container */
#ajaxLoader {
    display: none; /* Hidden by default */
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 9999; /* Above everything else */
    text-align: center;
}

/* Loader Text */
#ajaxLoader span {
    display: block;
    margin-top: 10px;
    font-size: 18px;
    font-family: Arial, sans-serif;
    color: white; /* Ensure visibility over the overlay */
    font-weight: bold;
}

/* Loader Image */
#ajaxLoader img {
    width: 50px; /* Adjust size of the spinner */
    height: 50px;
}


/* Loader Container */

#ajaxLoader_Editlatex{
    display: none; /* Hidden by default */
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 9999; /* Above everything else */
    text-align: center;
}

/* Loader Text */
#ajaxLoader_Editlatex span {
    display: block;
    margin-top: 10px;
    font-size: 18px;
    font-family: Arial, sans-serif;
    color: white; /* Ensure visibility over the overlay */
    font-weight: bold;
}

#ajaxLoader_Editlatex img {
    width: 50px; /* Adjust size of the spinner */
    height: 50px;
}

#ajaxLoader_QC {
    display: none; /* Hidden by default */
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 9999; /* Above everything else */
    text-align: center;
}


/* Loader Text */
#ajaxLoader_QC span {
    display: block;
    margin-top: 10px;
    font-size: 18px;
    font-family: Arial, sans-serif;
    color: white; /* Ensure visibility over the overlay */
    font-weight: bold;
}

/* Loader Image */
#ajaxLoader_QC img {
    width: 50px; /* Adjust size of the spinner */
    height: 50px;
}

/* Loader Container */
#ajaxLoader_e {
    display: none; /* Hidden by default */
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 9999; /* Above everything else */
    text-align: center;
}

/* Loader Text */
#ajaxLoader_e span {
    display: block;
    margin-top: 10px;
    font-size: 18px;
    font-family: Arial, sans-serif;
    color: white; /* Ensure visibility over the overlay */
    font-weight: bold;
}

/* Loader Image */
#ajaxLoader_e img {
    width: 50px; /* Adjust size of the spinner */
    height: 50px;
}

/* Loader Container */
#ajaxLoader_html {
    display: none; /* Hidden by default */
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 9999; /* Above everything else */
    text-align: center;
}

/* Loader Text */
#ajaxLoader_html span {
    display: block;
    margin-top: 10px;
    font-size: 18px;
    font-family: Arial, sans-serif;
    color: white; /* Ensure visibility over the overlay */
    font-weight: bold;
}

/* Loader Image */
#ajaxLoader_html img {
    width: 50px; /* Adjust size of the spinner */
    height: 50px;
}

/* Loader Container */
#ajaxLoader_tp {
    display: none; /* Hidden by default */
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 9999; /* Above everything else */
    text-align: center;
}

/* Loader Text */
#ajaxLoader_tp span {
    display: block;
    margin-top: 10px;
    font-size: 18px;
    font-family: Arial, sans-serif;
    color: white; /* Ensure visibility over the overlay */
    font-weight: bold;
}

/* Loader Image */
#ajaxLoader_tp img {
    width: 50px; /* Adjust size of the spinner */
    height: 50px;
}
/* Loader Container */
#ajaxLoader_algorithm {
    display: none; /* Hidden by default */
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 9999; /* Above everything else */
    text-align: center;
}

/* Loader Text */
#ajaxLoader_algorithm span {
    display: block;
    margin-top: 10px;
    font-size: 18px;
    font-family: Arial, sans-serif;
    color: white; /* Ensure visibility over the overlay */
    font-weight: bold;
}

/* Loader Image */
#ajaxLoader_algorithm img {
    width: 50px; /* Adjust size of the spinner */
    height: 50px;
}

#ajaxLoader_AV {
    display: none; /* Hidden by default */
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 9999; /* Above everything else */
    text-align: center;
}


/* Loader Text */
#ajaxLoader_AV span {
    display: block;
    margin-top: 10px;
    font-size: 18px;
    font-family: Arial, sans-serif;
    color: white; /* Ensure visibility over the overlay */
    font-weight: bold;
}

/* Loader Image */
#ajaxLoader_AV img {
    width: 50px; /* Adjust size of the spinner */
    height: 50px;
}
  </style>
  </head>	

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("../includes/top-header.php");?>
	 
<?php
require_once "fetch_data.php";
require_once "helpers.php";

$aid          = intval($_GET['aid']);
$app_res      = getArticleDetails($db, $aid);
$customer     = getCustomerDetails($db, $app_res['cust_id']);
$workflow     = getWorkflowDeptCodes($db, $app_res['wf_id']);
$ceditor_ip   = $_COOKIE['preediting_server'] ?? '10.10.10.109';
?>

<?php include "components/modals.php"; ?>
<?php include "components/loaders.php"; ?>

<div class="container workstatus-container">

    <h3 class="section-title">Work Status & Comments</h3>

    <?php include "components/buttons.php"; ?>

    <?php include "components/customer_tools.php"; ?>

    <?php include "components/file_iframes.php"; ?>

    <form method="post" id="workstatusForm">

        <?php include "components/status_selector.php"; ?>

        <?php include "components/page_count.php"; ?>

        <?php include "components/validation_forms.php"; ?>

        <?php include "components/comments_block.php"; ?>

        <?php include "components/hidden_fields.php"; ?>

        <div class="submit-row">
            <button type="submit" class="btn btn-secondary">Cancel</button>
            <button type="submit" class="btn btn-primary" id="submit-btn">Submit</button>
        </div>

    </form>

</div>

<script src="js/workstatus.js"></script>
<link rel="stylesheet" href="css/workstatus.css">
   <!-- Footer Section -->
     <?php echo footer(); ?>
	 </div>



<!-- Footer Section -->
<?php echo scripts(); ?>	
