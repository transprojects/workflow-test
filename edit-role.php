<?php include "includes/includes.php";?>
<?php require_once __DIR__.'/includes/form_ui.php'; ?>
<?php include_once "includes/change_log_mail.php";?>
<?php 
if(isset($_POST['action']) && $_POST['action']=='update')
{
	foreach($_POST as $key => $value) {
		$post[$key] = $value;
	}	


  //$menu_id=implode(",",$_REQUEST['menu_id']);
  $smenu_id=implode(",",$_REQUEST['smenu_id']);
  $crud_access=implode(",",$_REQUEST['crud_access']);
$menuid=array();

$smenu_access=mysqli_query($db,"select * from wf_smenu where smenu_id in (".$smenu_id.") ");
while($res_access=mysqli_fetch_array($smenu_access))
{
  $menuid[]=$res_access['menu_id'];
}
$menuarray_unique=array_unique($menuid);
$menu_id=implode(",",$menuarray_unique);

	 // Snapshot before change (for change-log email)
	 $__old = change_log_snapshot('user_role_master', 'user_role_id', $post['user_role_id']);
	 $update_query="UPDATE user_role_master SET `user_role_name` ='". $post['user_role_name']."',`user_role_description` ='". $post['user_role_description']."',`menu_id` ='". $menu_id."',`smenu_id` ='". $smenu_id."',`crud_access` ='". $crud_access."' WHERE user_role_id = ".$post['user_role_id']." ";	
	if($db->query($update_query))
	{
		log_row_change('Role', $post['user_role_id'] . ' / ' . $post['user_role_name'], $__old, array('user_role_name'=>$post['user_role_name'],'user_role_description'=>$post['user_role_description'],'menu_id'=>$menu_id,'smenu_id'=>$smenu_id,'crud_access'=>$crud_access), array());
		header("location:".$_COOKIE['set_url']."/roles?msg=Successfully completed"); 
		die();
	}
	else
	{
		$err[] = "Please try again.";
	}
}
/////////////// Update completed ///////////////

if(isset($_REQUEST['id']))
{
	$result = $db->query("SELECT * FROM user_role_master WHERE user_role_id = ".$_REQUEST['id']." "); 	
	$row_cnt=1;
	$toatl_count=$result->num_rows;
	if($toatl_count>0)
	{
		$app_res = $result->fetch_assoc();
	}	
}
if($app_res['smenu_id']!="")
{
 // echo "1";
$menu_id=explode(",",$app_res['menu_id']);
$smenu_id=explode(",",$app_res['smenu_id']);
}
else
{
 // echo "2";
$menu_id="";
$smenu_id="";
}
if($app_res['crud_access']!="")
{
  //echo "cr-yes";
$crud_access=explode(",",$app_res['crud_access']);
}
else
{
  //echo "cr-no";
$crud_access="";
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
    <?php fu_head('edit-role'); ?>	

    <!-- Data Table CSS -->
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
      </style>
  </head>

  <body>
  <div id="loader" class="loader-container">
        <div class="loader"></div>
    </div>
    <div id="overlay" class="overlay"></div>
    <div class="wholeouter">
    <?php include("includes/top-header.php");?>
  <div class="container-fluid" style="justify-content: space-between;">
        <div div class="row pt-4 d-flex align-items-center flex-wrap">
          <div class="col-8 col-lg-10">
            <p class="articleheading ms-4">Edit User Roles</p>
          </div>

          <div class="col-4 col-lg-2">
            <div class="row  d-flex justify-content-end pe-2">
              <div class="col-3 articlebtns">
                <a href="javascript:history.back();">
                  <div  class="back_icon"><img src="<?php echo $_COOKIE['set_url'];?>/assets/images/backarrow.svg"></div>
                </a>
              </div>
            </div>
          </div>
        </div> 
  </div>

      <?php fu_hero(fu_ui_basic('Edit User Roles', 'Edit User Roles &rsaquo; Edit')); ?>
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
          <form action="" method="post" name="edit-role" id="edit-role" enctype="multipart/form-data">
          <div class="container mt-4 separatetitle">
              <div class="formcont1heading">Edit User Roles</div>
              <div class="row mt-1 g-4 addspacing">
              <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Role<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="user_role_name" id="user_role_name"  value="<?php echo $app_res['user_role_name']; ?>" placeholder="Enter role" required>
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Description<span style="color: red">*</span></label>
              <input type="text" class="form-control" name="user_role_description" id="user_role_description"  value="<?php echo $app_res['user_role_description']; ?>" placeholder="Enter dscription" required>
              </input>
                </div>
                </div>
            </div>
              <!-- heading 2 -->
              <div class="container mt-4 separatetitle">
               <div class="formcont1heading">Menu Access Permission</div>
                <div class="row mt-2 g-4 addspacing">
                <?php 
if($_REQUEST['id']==4)
{
$menu = $auth->getvMenu();
}
else
{
$menu = $auth->getMenu();
}
$i=1;

foreach($menu as $menu_res)
{
?>
                <div class="col-12 col-sm-6 col-lg-3">
                  <label class="mb-2">Menu<span style="color: red"></span> </label>
                  <select class="form-select menu_drop" data-placeholder="Select Menu" aria-label="search-input" id="menu_id" name="menu_id[]">
                  <!-- <option value=" "style="font-weight:500;">Select Menu</option> -->

									      
				<option value="<?php echo $menu_res['menu_id'] ?>"style="font-weight:500;"><?php echo $menu_res['menu_title'] ?> </option>
											
                  </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-9">
                  <label class="mb-2"><?php echo $menu_res['menu_title'] ?> Submenu<span style="color: red"></span></label>
                  <select class="form-select smenu_drop" data-placeholder="Select <?php echo $menu_res['menu_title'] ?> Submenu" name="smenu_id[]" id="smenu_id<?php echo $i;?>"  multiple="multiple">
                  <?php 
if($_REQUEST['id']==4)
{
              $smenu = $auth->getvSMenu($menu_res['menu_id']);
}
else
{
              $smenu = $auth->getSMenu($menu_res['menu_id']);
}



              foreach($smenu as $smenu_res)
              {
              
												
              
                if($app_res['smenu_id']!="")
                {
                ?>
                   <option <?php if (in_array($smenu_res['smenu_id'], $smenu_id)){?>selected<?php }?> value="<?php echo $smenu_res['smenu_id'] ?>"style="font-weight:500;"><?php echo $smenu_res['smenu_title'] ?> </option>
                   <?php
                }
                else
                {
                ?>
                <option value="<?php echo $smenu_res['smenu_id'] ?>"style="font-weight:500;"><?php echo $smenu_res['smenu_title'] ?> </option>
                <?php 
                }
              
                                                  
				}
				?>
                </select>
                </div>

<?php 
  $i++;
}
?>
</div>
</div>
               
                
             <div class="container mt-4">
               <div class="formcont1heading">CRUD Access Permission</div>
                <div class="row mt-2 g-4 addspacing">
             <div class="col-12 col-sm-6 col-lg-4">
                <label class="mb-2">CRUD Access</label>
                <select class="form-select crud_access" data-placeholder="Select CRUD Access" name="crud_access[]" id="crud_access" multiple="multiple">
                  <?php 
                  if($app_res['crud_access']!="")
                  {
                  ?>
                         <option value="add" <?php if (in_array("add",$crud_access)){?>selected<?php }?> >Add</option>
                         <option value="edit" <?php if (in_array("edit",$crud_access)){?>selected<?php }?> >Edit</option>
                         <option value="delete" <?php if (in_array("delete",$crud_access)){?>selected<?php }?> >Delete</option>
                         <?php 
                  }
                  else
                  {
                  ?>
                    <option value="add">Add</option>
                   <option value="edit">Edit</option>
                   <option value="delete">Delete</option>
                  <?php 
                  }
                  ?>
                </select>
              </div>
                      </div>
                 </div>
            <div class="meta_nxtbtn container-fluid mt-5 mb-5" >
                <div class="row">
                    <div class="col form_btn cancelbtn d-flex justify-content-end">
                        <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/roles" style="text-decoration:none;">Cancel</a></button>
                        
                    </div>
                      <input type="hidden" name="action" value="update">
                      <input type="hidden" name="user_role_id" value="<?php echo $_REQUEST['id']; ?>" />
                    <div class="col form_btn nxtbtn">
                        <button type="submit" class="btn-next" >Update</button>
                    </div>
                </div>
            </div>
      </form>
    
        </div>
      </div>
      <?php echo scripts(); ?>	

       <!-- Footer Section -->
       <?php echo footer(); ?>
     
       <script src="<?php echo $_COOKIE['set_url'];?>/assets/js/select2/select2.min.js"></script>
   
   <script>

     $(".smenu_drop").select2({
       placeholder: "Select Submenu",
     });

     $(".crud_access").select2({
       placeholder: "Select Crud Access",
     });

   
   </script>
  <?php fu_foot('#edit-role', null, array(), null, fu_ui_basic('Edit User Roles', 'Edit User Roles &rsaquo; Edit')); ?>
  </body>
</html>
