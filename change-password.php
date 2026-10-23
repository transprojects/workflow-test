<?php include "includes/includes-sw2.php";?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>	

    <!-- Data Table CSS --->
    <style>
      .overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: none;
}

.loader-container {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1001;
    display: none;
}

.loader {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 2s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@media (min-width : 768px){
    .addarticleformouter{
		width : 50%
	}		
}	
      </style>
  </head>

  <body>
  <div id="loader" class="loader-container">
        <div class="loader"></div>
    </div>
    <div id="overlay" class="overlay"></div>
    <div class="wholeouter">
    <?php include("includes/top-header.php");?>


      <!-- form section  -->
      <div class="container-fluid mt-4">

      <div div class="row pt-4 d-flex align-items-center flex-wrap">
          <div class="col-8 col-lg-10">
            <p class="articleheading ms-4">Change Password</p>
          </div>

          <div class="col-4 col-lg-2">
            <div class="row d-flex justify-content-end pe-2">
              <div class="col-3 articlebtns ">
                <a href="javascript:history.back();">
                  <div  class="back_icon"><img src="<?php echo $_COOKIE['set_url'];?>/assets/images/backarrow.svg"></div>
                </a>
              </div>
            </div>
          </div>
        </div> 
  </div>
         
       
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-2 m-md-5 mt-4">
          <form action="" method="post" name="change_password" id="change_password" enctype="multipart/form-data">
            <div class="container mt-4 ">
              <div class="formcont1heading">Please fill in the fields</div>
              <div class="row mt-1 g-4 addspacing d-flex flex-column align-items-center">
<p>
	  <?php
	  /******************** ERROR MESSAGES*************************************************
	  This code is to show error messages 
	  **************************************************************************/
	  if(!empty($err))  {
	   echo "<div class=\"alert alert-block alert-danger\" style='font-size:12px;'>";
	  foreach ($err as $e) {
	    echo "$e <br>";
	    }
	  echo "</div>";	
	   }
	   
	    if(!empty($msg))  {
	    echo "<div class=\"msg-success\">" . $msg[0] . "</div>";

	   }
	  /******************************* END ********************************/	  
	  ?></p>
                <div class="col-12 col-sm-6 col-lg-6">
                 <label class="mb-2">Enter Old Password <span style="color: red">*</span></label>
                 <input type="text" class="form-control" name="opwd" id="opwd" placeholder="Enter Old Password" required>
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-6">
              <label class="mb-2">Enter New Password <span style="color: red">*</span></label>
              <input type="text" class="form-control" name="npwd" id="npwd" placeholder="Enter New Password" required>
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-6">
              <label class="mb-2">Re-enter New Password <span style="color: red">*</span></label>
              <div class="d-flex">
                  <input type="text" class="form-control" name="cpwd" id="cpwd" required placeholder="Re-enter New Password" value=""/>
                </div>
              </Input>
                </div>
                </div>
            </div>
            
            
            <div class="meta_nxtbtn container-fluid mt-5 mb-5 d-flex justify-content-center" >
                <div class="row ">
                    <div class="col form_btn nxtbtn">
                        <button type="submit" name="btnchange" class="btn-next" >Submit</button>
                    </div>
                </div>
            </div>
      </form>

      
    
        </div>
      </div>
      <?php echo scripts(); ?>	

       <!-- Footer Section -->
       <?php echo footer(); ?>
     </div>
 <script>
    
 $(document).ready(function() {
        $("#change_password").validate({
            rules: {
                "npwd": {
                    required: true,
                    minlength: 6 // Minimum length for the new password
                },
                "cpwd": {
                    required: true,
                    equalTo: "#npwd" // Should match the value of the new password field
                }
            },
            messages: {
                "npwd": {
                    required: "Please enter a new password",
                    minlength: "Password must be at least 6 characters long"
                },
                "cpwd": {
                    required: "Please confirm your password",
                    equalTo: "Passwords do not match"
                }
            },
            submitHandler: function(form) {
                // Your form submission logic here
                form.submit();
            }
        });
    });

</script>
      <?php
      
      if(isset($_REQUEST['btnchange']))
{


$err = array();
$msg = array();

foreach($_POST as $key => $value) {
	$data[$key] = $value;
}


$opwd = $data['opwd'];
$npwd = $data['npwd'];
$cpwd = $data['cpwd'];

//echo "select pwd from users where id='".$_COOKIE['user_id']."'";
$result = $db->query("select pwd from users where id='".$_COOKIE['user_id']."'"); 	
	$row_cnt=1;
	 $toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$app_res = $result->fetch_assoc();
		 $old = $app_res['pwd'];
	}	
	
	//echo $old;
//	echo "<br/>";
	//echo PwdHash($opwd);
	
	//check for old password in md5 format	
	if($old === PwdHash($opwd,substr($old,0,9)))
	{
		//echo "old password ok";
	if($npwd!=$cpwd)
	{
				$err[] = "Error - Sorry your new password and Confirm Password is mismatching.";	

	}
	else
	{	
		$newsha1 = PwdHash($_POST['npwd']);
		$db->query("update users set pwd='$newsha1' where id='$_COOKIE[user_id]'");
		
		$db->query("INSERT INTO  tbl_user_password(user_id,password) values ('".$_COOKIE[user_id]."','".$npwd."')");
		
		
		echo "<script type='text/javascript'>";
 echo "swal({
    title: 'User Password Changed Successfully',
    type: 'success',
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'CLOSE',
  }).then(function() {
// Redirect the user
window.location.href = './logout';
});";
 echo "</script>";
 
	}

	} 
	else
	{
				$err[] = "Your old password is invalid";

	}	

 
 
	//header('location:'.$_COOKIE['set_url'].'/users');
}
?>
  </body>
</html>
