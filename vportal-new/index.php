<?php
require_once "includes/Auth.php";
require_once "includes/Util.php";
require_once "includes/function.php";

$auth = new Auth();
$db_handle = new DBController();
$util = new Util();

require_once "includes/authCookieSessionValidate.php";
if ($isLoggedIn) {
    $util->redirect($_COOKIE['cpage']);
}





if (isset($_POST["login"])) {



$current_page_url_without_filename = getCurrentPageURLWithoutFilename();
$wordToRemove = "index";
$domain_url = removeWordFromString($current_page_url_without_filename, $wordToRemove);
$domain_url = rtrim($domain_url, '/');

//echo $domain_url;

  $isAuthenticated = false;
     
  $error_message='';
   $username = $_POST["uname"];
   $password = $_POST["pass"];
  
  $user = $auth->getMemberByUsername($username);
  $sel_setting = $auth->showSelectSetting();


  
  $pass=$user[0]["pwd"];


    if ($pass === PwdHash($password,substr($pass,0,9))) { 
      $isAuthenticated = true;
  }
  if ($isAuthenticated) {

     //echo "Authenticated";exit;

  setcookie("staff_login", $username, $cookie_expiration_time);
  setcookie("user_name", $username, $cookie_expiration_time);
  


  $userid = $auth->getTokenByUser($user[0]["id"]);
  $user_dept = $auth->getdepartUsername($userid[0]["department_id"]);

  $role_access=mysqli_fetch_array(mysqli_query($db,"select * from user_role_master where user_role_id='".$userid[0]["user_level"]."'"));
  setcookie("user_id", $userid[0]["id"], $cookie_expiration_time);
  setcookie("full_name", $userid[0]["full_name"], $cookie_expiration_time);
  setcookie("user_email", $userid[0]["user_email"], $cookie_expiration_time);
  setcookie("user_type", $_POST['utype'], $cookie_expiration_time);
  setcookie("sub_user_id", $userid[0]["sub_user_id"], $cookie_expiration_time);
  setcookie("user_level", $userid[0]["user_level"], $cookie_expiration_time);
  setcookie("department_id", $userid[0]["department_id"], $cookie_expiration_time);
  setcookie("odepartment_id", $userid[0]["access_other_depts"], $cookie_expiration_time);
  setcookie("cust_id", $userid[0]["cust_id"], $cookie_expiration_time);
  setcookie("ocust_id", $userid[0]["access_other_cust_id"], $cookie_expiration_time);
  setcookie("stage_access", $userid[0]["stage_access"], $cookie_expiration_time);
  setcookie("HTTP_USER_AGENT", md5($_SERVER['HTTP_USER_AGENT']), $cookie_expiration_time);
  setcookie("approved", md5($userid[0]['approved']), $cookie_expiration_time);
  //setcookie("set_url", $sel_setting[0]["set_url"], $cookie_expiration_time);
  setcookie("set_url", $domain_url, $cookie_expiration_time);
  setcookie("set_title", $sel_setting[0]["formname"], $cookie_expiration_time);
  setcookie("dept_name", $user_dept[0]["dept_name"], $cookie_expiration_time);
  setcookie("menu_access", $role_access["menu_id"], $cookie_expiration_time);
  setcookie("smenu_access", $role_access["smenu_id"], $cookie_expiration_time);
  setcookie("folder_access", $userid[0]["folder_access"], $cookie_expiration_time);

  



  $generate_password = $util->getToken(16);
  setcookie("generate_password", $generate_password, $cookie_expiration_time);

  $generate_selector = $util->getToken(32);
  setcookie("generate_selector", $generate_selector, $cookie_expiration_time);
     //echo "setcookie";exit;

  $generate_password_hash = password_hash($generate_password, PASSWORD_DEFAULT);
  $generate_selector_hash = password_hash($generate_selector, PASSWORD_DEFAULT);
  
  setcookie("generate_password_hash", $generate_password_hash, $cookie_expiration_time);

  $expiry_date = date("Y-m-d H:i:s", $cookie_expiration_time);

  // mark existing token as expired
  $userToken = $auth->getTokenByUsername($username, 0);
  if (! empty($userToken[0]["id"])) {
    $auth->markAsExpired($userToken[0]["id"]);
  }
  // Insert new token
  $auth->insertToken($username, $generate_password_hash, $generate_selector_hash, $crdatetime, $expiry_date);
  
  //insert login history
  $auth->insertLoginhistory($userid[0]["id"], $username);

	$util->redirect("./allocatedarticles");


  } else {
      
       $error_message .= 'Invalid Username and Password<br>';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable = no"/>
    <title>Vendor Portal - Workflow</title>
	  <link rel="shortcut icon" href="assets/images/favicon.svg">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-1.9.1.min.js"></script>
    <link href="styles.css" rel="stylesheet" />
	<style>
	.trans_logo {
    width: 13.958vw;
    position: absolute;
    top: 20px;
    right: 5%;
}
	</style>
  </head>
  <!-- login body starts -->
  <body>
    <div class="container-fluid login_outer">
      <div class="row row-ht">
        <div class="col d-none d-sm-block loginbg d-flex align-items-center">
          <div class="loginlogo d-none d-sm-block">
            <img alt="transforma_logo" src="assets/images/login-bg.png" />
          </div>
		    <div class="trans_logo">
          <img alt="Transforma-logo" src="assets/images/translogo.png">
        </div>
        </div>
        <div class="col-12 col-sm-6 loginwht">
          <!-- Reset Page -->
          <div id="pswdreset" class="loginfieldouter d-none d-flex flex-column justify-content-center align-items-center">
          <form action="" name="admin_forgot_form" id="admin_forgot_form" method="post">
            <div class="form-data m-3 m-lg-4">
              <div class="d-flex justify-content-center pt-2" style="width: 70%">
                <img class=" d-flex justify-content-center" alt="transforma-logo" src="assets/images/xpertworkflow.svg" style="width: 60%"/>
              </div>
              <!--<p class="w-100 d-flex justify-content-center h5">Workflow</p>-->

           
     <?php 
	    if( (isset($error_message)) && ($error_message!='') ):
	        echo '<div class="error">'.$error_message.'</div>';
	    endif;
	    ?>
                <div class="mb-3 input-group forgotinput">
                  <span class="emailicon input-group-addon"> </span>
                  <input type="text" class="form-control" style="height: 52px" placeholder="Email ID"/>
                </div>
                <button type="button" class="btn btn-link backtologinlink" onclick="showlogin()">
                  Back to Login
                </button>
                <div class="loginbtndiv">
                  <button type="submit" class="btn btn-dark loginbtn">
                    Reset Password
                  </button>
                </div>
              </div>
            </form>
          </div>

          <!-- Login Page -->
          <div id="login" class="loginfieldouter d-flex flex-column justify-content-center align-items-center">
            <form action="" name="admin_login_form" id="admin_login_form" method="post" class="d-flex">
            <div class="form-data m-3 m-lg-4">
              <div class="d-flex justify-content-center pt-2" style="width: 70%">
                <img class="d-flex justify-content-center" alt="transforma-logo" src="assets/images/workflow-vendor-logo.png" style="width: 60%"/>
              </div>
              <!--<p class="w-100 d-flex justify-content-center h5">Workflow</p>-->
             <!-- <div class="form-radio w-100 d-flex flex-wrap align-items-center mt-4 mb-4">
                <p class="me-4">User Type:</p>
                <div class="p-1 gap-4 form-check-inline">
                  <input class="form-check-input custom-radio" name="utype" id="utype" value="N" type="radio" checked/>
                  <label class="form-check-label me-3" for="logintype">Normal</label>
                </div>
                <div class="ps-4 gap-4 form-check form-check-inline">
                  <input class="form-check-input custom-radio" name="login-radio"  type="radio" name="utype" id="utype" value="S"/>
                  <label class="form-check-label" for="logintype">Substitute</label>
                </div>
              </div>-->
			  <div class="form-radio w-100 d-flex flex-wrap align-items-center mt-4 mb-4">
			  </div>
              <?php 
	                if( (isset($error_message)) && ($error_message!='') ):
	                echo '<div class="error">'.$error_message.'</div>';
	                endif;
	             ?>
                <div class="mb-3 input-group">
                  <span class="inputicon input-group-addon"> </span>
                  <input type="text" class="form-control" name="uname" style="height:52px" placeholder="Username"/>
                </div>
                <div class="mb-3 input-group">
                  <span class="pswdicon input-group-addon"></span>
                  <input type="password" class="form-control" name="pass" id="passInput" style="height: 52px; padding-right: 48px" placeholder="Password"/>
                  <button type="button" id="togglePass" aria-label="Show password" title="Show password" onclick="togglePassword()" style="position:absolute; right:0; top:0; height:52px; width:46px; z-index:5; border:0; background:transparent; cursor:pointer; padding:0; color:#6c757d;">
                    <svg id="eyeIcon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/><line id="eyeSlash" x1="3" y1="3" x2="21" y2="21" style="display:none"/></svg>
                  </button>
                </div>
                <button type="button" class="btn btn-link forgotlink" onclick="showforgotpswd()">Forgot Password?</button>
                <div class="loginbtndiv">
                  <button type="submit" name="login"  class="btn btn-dark loginbtn">Log in</button>
                </div>
            </div>
          </div>
        </div>
      </div>
      </form>
    </div>
  <script>
  function togglePassword() {
    var i = document.getElementById('passInput'), show = i.type === 'password';
    i.type = show ? 'text' : 'password';
    document.getElementById('eyeSlash').style.display = show ? '' : 'none';
    var b = document.getElementById('togglePass');
    b.title = b.ariaLabel = show ? 'Hide password' : 'Show password';
  }
  </script>
  </body>

  <script>
    function showforgotpswd() {
      let loginpg = document.querySelector("#login");
      let resetpg = document.querySelector("#pswdreset");

      loginpg.classList.add("d-none");
      resetpg.classList.remove("d-none");
    }

    function showlogin() {
      let loginpg = document.querySelector("#login");
      let resetpg = document.querySelector("#pswdreset");

      loginpg.classList.remove("d-none");
      resetpg.classList.add("d-none");
    }
  </script>
</html>
