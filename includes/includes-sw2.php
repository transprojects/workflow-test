<?php
include 'includes/check.php';
include 'includes/global_variables.php';

function filter($data) {
	$data = trim(htmlentities(strip_tags($data)));
	return $data;
}

//////////////////// Common Dat Format //////////////

function convert_mdy_ymd($date)
{
	$darr = explode("-",$date);
	$dmdy =  $darr[2].'-'.$darr[1].'-'.$darr[0];
	return $dmdy;
}

function convert_ymd_dmy($date)
{
	$darr = explode("-",$date);
	$dmdy =  $darr[2].'-'.$darr[1].'-'.$darr[0];
	return $dmdy ;
}

function pageLength() {
?>
	pageLength : 10,
	lengthMenu : [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
<?php  }
function styles() {

$timestamp=date('ymdhis');
?>

    <link rel="shortcut icon" href="<?php echo $_COOKIE['set_url'];?>/assets/images/favicon.svg">
    <link href="<?php echo $_COOKIE['set_url'];?>/css/main.min.css" rel="stylesheet" />
    <link href="<?php echo $_COOKIE['set_url'];?>/styles.css" rel="stylesheet" />
    <link href="<?php echo $_COOKIE['set_url'];?>/assets/css/datepicker.min.css" rel="stylesheet" type="text/css"/>
    <link href="<?php echo $_COOKIE['set_url'];?>/assets/css/select2.min.css" rel="stylesheet" />
    <link href="<?php echo $_COOKIE['set_url'];?>/assets/fonts/font-awesome.min.css" rel="stylesheet" />
   <!-- <link href="<?php echo $_COOKIE['set_url'];?>/assets/css/sweetalert-3/sweet-alert3.css" rel="stylesheet" />-->
	<link href="<?php echo $_COOKIE['set_url'];?>/vendor/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="<?php echo $_COOKIE['set_url'];?>/assets/css/dropzone.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="<?php echo $_COOKIE['set_url'];?>/assets/css/richtext.min.css" />
    <link rel="stylesheet" href="<?php echo $_COOKIE['set_url'];?>/assets/css/ace.min.css" />
    <!--<link rel="stylesheet" href="<?php echo $_COOKIE['set_url'];?>/assets/css/jquery-ui.css">-->
    <link type="text/css" href="<?php echo $_COOKIE['set_url'];?>/assets/css/ui.multiselect.css" rel="stylesheet" />
	<link type="text/css" rel="stylesheet" href="<?php echo $_COOKIE['set_url'];?>/assets/css/jquery-ui.css" />

<?php  } 
function scripts() {
?>	
    <script src="<?php echo $_COOKIE['set_url'];?>/assets/js/bootstrap.js"></script>

<!-- Include jQuery -->

<script src="<?php echo $_COOKIE['set_url'];?>/assets/js/jquery-3.6.0.js"></script>


<script src="<?php echo $_COOKIE['set_url'];?>/assets/js/jquery-3.6.0.min.js"></script>
<script src="<?php echo $_COOKIE['set_url'];?>/assets/js/gijgo.min.js" type="text/javascript" ></script>

<!-- Include DataTables CSS -->
<link rel="stylesheet" type="text/css" href="<?php echo $_COOKIE['set_url'];?>/assets/css/datatable.css">


<!-- Include DataTables JavaScript -->
<script type="text/javascript" charset="utf8" src="<?php echo $_COOKIE['set_url'];?>/assets/js/jquery.dataTables.min.js"></script>

<script src="<?php echo $_COOKIE['set_url'];?>/assets/js/bootbox.js"></script>


<!-- Include DataTables Buttons CSS and JS -->
<link rel="stylesheet" type="text/css" href="<?php echo $_COOKIE['set_url'];?>/assets/css/buttons.dataTables.min.css">
<script type="text/javascript" charset="utf8" src="<?php echo $_COOKIE['set_url'];?>/assets/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" charset="utf8" src="<?php echo $_COOKIE['set_url'];?>/assets/js/jszip.min.js"></script>
<script type="text/javascript" charset="utf8" src="<?php echo $_COOKIE['set_url'];?>/assets/js/buttons.html5.min.js"></script>
<script type="text/javascript" charset="utf8" src="<?php echo $_COOKIE['set_url'];?>/assets/js/buttons.print.min.js"></script>
<script type="text/javascript" charset="utf8" src="<?php echo $_COOKIE['set_url'];?>/assets/js/pdfmake.min.js"></script>
<script type="text/javascript" charset="utf8" src="<?php echo $_COOKIE['set_url'];?>/assets/js/vfs_fonts.js"></script>
<!--<script type="text/javascript" charset="utf8" src="<?php echo $_COOKIE['set_url'];?>/assets/js/sweetalert-3/sweet-alert.min.js"></script>-->
<script type="text/javascript" charset="utf8" src="<?php echo $_COOKIE['set_url'];?>/vendor/sweetalert2/dist/sweetalert2.min.js"></script>

<script src="<?php echo $_COOKIE['set_url'];?>/assets/js/dropzone.js" type="text/javascript"></script>
<script src="<?php echo $_COOKIE['set_url'];?>/assets/js/jquery.validate.min.js"></script>
<script src="<?php echo $_COOKIE['set_url'];?>/assets/js/parsleyjs/parsley.min.js"></script>
<script src="<?php echo $_COOKIE['set_url'];?>/assets/js/jquery.richtext.min.js"></script>
<script src="<?php echo $_COOKIE['set_url'];?>/assets/js/jquery-ui.js"></script>
<script src="<?php echo $_COOKIE['set_url'];?>/assets/js/ui.multiselect.js"></script>


	<script type="text/javascript" src="<?php echo $_COOKIE['set_url'];?>/assets/js/plugins/localisation/jquery.localisation-min.js"></script>
	<script type="text/javascript" src="<?php echo $_COOKIE['set_url'];?>/assets/js/plugins/scrollTo/jquery.scrollTo-min.js"></script>

<!-- <script src="<?php echo $_COOKIE['set_url'];?>/assets/js/select2.min.js"></script> -->
<script>
  $( function() {
    $( "#accordion" ).accordion();
  } );
  </script>
<?php } 
function footer() {
?>
<div class="container-fluid footer">
        <div class="row footerrow align-items-center">
          <div class="col d-none d-md-block"></div>
          <div class="col d-flex justify-content-center">
            <span style="font-family: Open Sans; color: #f26722">Transforma Pvt Ltd</span>
            - Workflow © <?php echo date('Y');?>
          </div>
          <div class="col d-flex justify-content-end socialicons">
            <a href="" target="_blank">
              <img src="<?php echo $_COOKIE['set_url'];?>/assets/images/twitter.svg" alt="twitter_logo">
            </a>
            <a href="" target="_blank">
              <img src="<?php echo $_COOKIE['set_url'];?>/assets/images/facebook.svg" alt="twitter_logo">
            </a>
            <a href="" target="_blank">
              <img src="<?php echo $_COOKIE['set_url'];?>/assets/images/youtube.svg" alt="twitter_logo">
            </a>
            <a href="" target="_blank">
              <img src="<?php echo $_COOKIE['set_url'];?>/assets/images/linkedin.svg" alt="twitter_logo">
            </a>
          </div>
        </div>
      </div>
<?php
}
error_reporting(0);

?>


