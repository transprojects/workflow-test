<?php
/// Checking the PDF page count

// --- Input ID ---
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

$auto_pdf_page_count_python_file = "C:/LAMP/Apache/htdocs/workflow-new/python-scripts/auto_pdf_page_count.py";
		
		$python_tf_article_appc ='C:\\Windows\\py.exe "'.$auto_pdf_page_count_python_file.'" '.$id.'';
		$output = system($python_tf_article_appc);
// --- Output from Python ---
echo "<pre>";
echo "Python Output:\n";
print_r($output);
echo "</pre>";
exit; die();

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
  setcookie("user_name", $userid[0]["user_name"], $cookie_expiration_time);
  setcookie("user_email", $userid[0]["user_email"], $cookie_expiration_time);
  //setcookie("user_type", $_POST['utype'], $cookie_expiration_time);
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
  setcookie("show_todo_popup", 1, $cookie_expiration_time); // 👈 ADD THIS

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
  $today = date('Y-m-d');

  //insert login history
  $auth->insertLoginhistory($userid[0]["id"], $username);
 // loadDefaultTasksFromTemplate($userid[0]["id"], $userid[0]["user_level"], $today);

    // Step 1: Get templates for the role
    $stmt = mysqli_prepare($db, "SELECT task_description FROM todo_templates WHERE role = ?");
    mysqli_stmt_bind_param($stmt, "i", $userid[0]["user_level"]);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) return false;

    while ($row = mysqli_fetch_assoc($result)) {
        $task_desc = $row['task_description'];

        // Step 2: Check if task already exists
        $check = mysqli_prepare($db, "SELECT COUNT(*) as count FROM user_todo_tasks WHERE user_id = ? AND task_description = ? AND task_date = ?");
        mysqli_stmt_bind_param($check, "iss", $userid[0]["id"], $task_desc, $today);
        mysqli_stmt_execute($check);
        $check_result = mysqli_stmt_get_result($check);
        $check_row = mysqli_fetch_assoc($check_result);

        if (intval($check_row['count']) === 0) {
            // Step 3: Insert task
            $insert = mysqli_prepare($db, "INSERT INTO user_todo_tasks (user_id, task_description, task_date, is_custom) VALUES (?, ?, ?, 0)");
            mysqli_stmt_bind_param($insert, "iss", $userid[0]["id"], $task_desc, $today);
            mysqli_stmt_execute($insert);
        }
    }
  /// Auto Assign starts here 
  // First-time login: assume you have the user_id from session or other login process 
	include("auto_assign_articles.php");
	//$user_id = 242;  // Example user ID
	$user_id = $userid[0]["id"];  // Example user ID
	// Check if the user has already been assigned articles today
	if (!isAssignedToday($user_id)) {
		assignArticles($user_id);
	}
	/// Auto Assign ends here

  if($userid[0]["department_id"]==9)
  {
  $util->redirect("./graphicarticles");
  }
  elseif($userid[0]["user_level"]==5)
  {
  $util->redirect("./assignduedate");
  }
  else
  {
	$util->redirect("./articles");
  }
  } else {
      
       $error_message .= 'Password does not match<br>';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable = no"/>
    <title>Workflow</title>
	  <link rel="shortcut icon" href="assets/images/favicon.svg">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <script src="assets/js/jquery-1.9.1.min.js"></script>
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
                <img class="d-flex justify-content-center" alt="transforma-logo" src="assets/images/xpertworkflow.svg" style="width: 60%"/>
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
                  <input type="password" class="form-control" name="pass" style="height: 52px" placeholder="Password"/>
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
