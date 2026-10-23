 <!-- Navbar section  -->
 <?php 
 require_once "includes/Auth.php";
 require_once "includes/Util.php";
 require_once "includes/function.php";

 $auth = new Auth();
 $db_handle = new DBController();
 $util = new Util();
 
 require_once "includes/authCookieSessionValidate.php";

 if ($isLoggedIn==false) {
  $util->redirect(''.$_COOKIE['set_url'].'/index');
}
    $current_url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    setcookie("cpage", $current_url, $cookie_expiration_time);
   ?>
 <nav class="navbar navbar-expand-xl d-flex navbar-dark nav-outer">
        <button class="homebtn" type="button">Home</button>
        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarSupportedContent"
          aria-controls="navbarSupportedContent"
          aria-expanded="false"
          aria-label="Toggle navigation"
        >
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
          <ul class="navbar-nav <?php if($_COOKIE['user_level']!=1){ echo "navtitlelist_level3"; }else{ echo "navtitlelist"; }?> ">

<?php 
// $menuid=array();

// $role_access=mysqli_fetch_array(mysqli_query($db,"select * from user_role_master where user_role_id='".$_COOKIE['user_level']."'"));
// $smenu_access=mysqli_query($db,"select * from wf_smenu where smenu_id in (".$role_access['smenu_id'].") ");
// while($res_access=mysqli_fetch_array($smenu_access))
// {
//   $menuid[]=$res_access['menu_id'];
// }
// $menuarray_unique=array_unique($menuid);
// echo implode(",",$menuarray_unique);
//$smenu_id=explode();
$menu_ex=explode(",",$_COOKIE['menu_access']);
$smenu_ex=explode(",",$_COOKIE['smenu_access']);

$menu = $auth->getMenu();
foreach($menu as $menu_res)
{
 if (in_array($menu_res['menu_id'], $menu_ex))
 {?>
            <li class="dropdown ddlist nav-item">
              <button class="nav-link dropdown-toggle navlinkbtn navlinkbtn<?php echo $menu_res['menu_id'];?> id="navbaraccountsdropdown" role="button" data-bs-toggle="dropdown"  aria-expanded="false"
              ><?php echo $menu_res['menu_title'];?></button>
              <ul class="dropdown-menu custdropdown dropdown-menu-end" aria-labelledby="navbaraccountsdropdown">

              <?php 
              $smenu = $auth->getSMenu($menu_res['menu_id']);
              foreach($smenu as $smenu_res)
              {
                if (in_array($smenu_res['smenu_id'], $smenu_ex)){?>
                <li class="nav-item ">
                  <a class="dropdown-item" href="<?php echo $_COOKIE['set_url'];?>/<?php echo $smenu_res['smenu_link'];?>"><?php echo $smenu_res['smenu_title'];?></a>
                </li>
                <?php 
              }
            }
              ?>
              </ul>
            </li>
            <?php 
}
}
?>
          
             
          </ul>
        </div>
      </nav>