<?php 
// Get Current date, time
$current_time = time();
$current_date = date("Y-m-d H:i:s", $current_time);

// Set Cookie expiration for 10 Hrs
$cookie_expiration_time = $current_time + (10 * 60 * 60);  // for 10 Hrs
//$cookie_expiration_time = $current_time + (60 * 2);  // for 2 Min

$isLoggedIn = false;

// Check if loggedin session exists
if (! empty($_COOKIE["staff_login"]) && ! empty($_COOKIE["generate_password"]) && ! empty($_COOKIE["generate_selector"])) {
    // Initiate auth token verification diirective to false
    $isPasswordVerified = false;
    $isSelectorVerified = false;
    $isExpiryDateVerified = false;
    
    // Get token for username
    $userToken = $auth->getTokenByUsernameandpassword($_COOKIE["staff_login"],$_COOKIE["generate_password_hash"],0);
//echo "cookie :".$_COOKIE["generate_password_hash"];
//echo "<br/>";
//echo "stored :".$userToken[0]["password_hash"];exit;
    // Validate random password cookie with database
    if (password_verify($_COOKIE["generate_password"], $userToken[0]["password_hash"])) {
        $isPasswordVerified = true;
    }
    
    // Validate random selector cookie with database
    if (password_verify($_COOKIE["generate_selector"], $userToken[0]["selector_hash"])) {
        $isSelectorVerified = true;
    }
    
    // check cookie expiration by date
    if($userToken[0]["expiry_date"] >= $current_date) {
        $isExpiryDateVerified = true;
    }
    
    // Redirect if all cookie based validation retuens true
    // Else, mark the token as expired and clear cookies
    if (!empty($userToken[0]["id"]) && $isPasswordVerified && $isSelectorVerified && $isExpiryDateVerified) {
        $isLoggedIn = true;
    } else {
		if(!empty($userToken[0]["id"])) {
			$auth->markAsExpired($userToken[0]["id"]);
		}
		// clear cookies
		$util->clearAuthCookie();
    }
}
?>