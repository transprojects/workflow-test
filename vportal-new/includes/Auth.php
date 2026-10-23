<?php
require "DBController.php";
class Auth {

    function getMemberByUsername($username) {
        $db_handle = new DBController();
        $query = "Select * from  users where user_name = ? and user_level=4";
        $result = $db_handle->runQuery($query, 's', array($username));
        return $result;
    }
	
	    function showUsersArticle($userid) {
        $db_handle = new DBController();
        $query = "Select * from  users where id = ?";
        $result = $db_handle->runQuery($query, 'i', array($userid));
        return $result;
    }
	
	
	
	
	function showUserlist() {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select * from users where user_level in ('2','3','4','5','6','7') and approved=1 order by full_name asc ");
        return $result;
    }
	
		function showUserlistdep($dep_id) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select * from users where user_level in ('2','3','4','5','6','7') and approved=1 and department_id='".$dep_id."' order by full_name asc ");
        return $result;
    }
  
    function getdepartUsername($dept_id) {
        $db_handle = new DBController();
        $query = "Select * from  adm_dept_master where dept_code = ?";
        $result = $db_handle->runQuery($query, 'i', array($dept_id));
        return $result;
    }

    function getAlldepartment() {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select * from adm_dept_master order by dept_code asc");
        return $result;
    }
	
	    function getAlldepartmentTarget() {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select * from adm_dept_master where dept_code in (1,22,10,14) order by dept_code asc");
        return $result;
    }

    function getArticleDepartment($dept_code) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select * from adm_dept_master where dept_code ='".$dept_code."' ");
        return $result;
    }
	
	
    function showUsersTarget($uta_id) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select * FROM  user_target_assign as uta LEFT JOIN users as u ON u.id = uta.user_id LEFT JOIN adm_dept_master as dm ON dm.dept_code = u.department_id where uta.uta_id='".$uta_id."'");
        return $result;
    }
    

    function getAllinwardtype() {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select * from inw_type order by type_name asc");
        return $result;
    }

    function showjournals($jid) {
        $db_handle = new DBController();
        // $result = $db_handle->runBaseQuery("SELECT * from adm_journals WHERE j_cust_id=66  ORDER BY j_code asc");
        $result = $db_handle->runBaseQuery("SELECT * from adm_journals where j_id in (".$jid.") ORDER BY j_code asc");

        return $result;
    }

    function getJournalWorkflow($fp,$rev,$fin) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select * from adm_workflow_master WHERE wf_name IN ('".$fp."','".$rev."','".$fin."') ORDER BY `wf_code` ASC ");
        return $result;
    }

    function getArticleWorkflow($wf_id) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select * from adm_workflow_master WHERE wf_id IN ('".$wf_id."')");
        return $result;
    }
    function getArticleUpdateWorkflow($j_id) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select * from adm_workflow_master WHERE wf_id IN ('".$wf_id."')");
        return $result;
    }


    function getJournalfolder($jid) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select * from inw_journal_folders WHERE j_id IN (".$jid.")  AND is_group_folder = 1 ORDER BY `jf_id` ASC ");
        return $result;
    }

    

        function Selectstaff() {
        $db_handle = new DBController();
        $query = "Select * from users where  id not in (1,9) and staff_status=? ";
        $result = $db_handle->runQuery($query, 's', array('Active'));
        return $result;
    }



    function getMemberBystaff($sid) {
        $db_handle = new DBController();
        $query = "Select * from users where  id = ?";
        $result = $db_handle->runQuery($query, 'i', array($sid));
        return $result;
    }

    function getMemberByAdmin($sid) {
        $db_handle = new DBController();
        $query = "Select * from users where  id = ?";
        $result = $db_handle->runQuery($query, 'i', array($sid));
        return $result;
    }
    
	function getTokenByUsername($username,$expired) {
	    $db_handle = new DBController();
	    $query = "Select * from wf_token_auth where username = ? and is_expired = ?";
	    $result = $db_handle->runQuery($query, 'si', array($username, $expired));
	    return $result;
    }
	
		function getTokenByUsernameandpassword($username,$password,$expired) {
	    $db_handle = new DBController();
	    $query = "Select * from wf_token_auth where username = ? and password_hash=? and is_expired = ?";
	    $result = $db_handle->runQuery($query, 'ssi', array($username,$password,$expired));
	    return $result;
    }

    function getJournalCustomer($jid) {
	    $db_handle = new DBController();
        $query = "Select * from adm_journals,adm_customer_master where adm_journals.j_cust_id = adm_customer_master.id and j_id = ?";
	    $result = $db_handle->runQuery($query, 'i', array($jid));
	    return $result;
    }
	
	function getTokenByUser($username) {
	    $db_handle = new DBController();
        $query = "Select * from users where id = ?";
	    $result = $db_handle->runQuery($query, 's', array($username));
	    return $result;
    }
	
    function markAsExpired($tokenId) {
        $db_handle = new DBController();
        $query = "UPDATE wf_token_auth SET is_expired = ? WHERE id = ?";
        $expired = 1;
        $result = $db_handle->update($query, 'ii', array($expired, $tokenId));
        return $result;
    }
    
    function insertToken($username, $generate_password_hash, $generate_selector_hash, $start_date, $expiry_date) {
        $db_handle = new DBController();
        $query = "INSERT INTO wf_token_auth (username, password_hash, selector_hash, start_date, expiry_date) values (?, ?, ?, ?, ?)";
        $result = $db_handle->insert($query, 'sssss', array($username, $generate_password_hash, $generate_selector_hash, $start_date, $expiry_date));
        return $result;
    }
        function insertLoginhistory($user_id,$user_name) {
        $db_handle = new DBController();
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
             $clientIP = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
             $clientIP = $_SERVER['REMOTE_ADDR'];
                }
                $cdate=date('Y-m-d H:i:s');

        $query = "INSERT INTO login_history (user_id, user_name, login_ip,cdatetime) values (?, ?, ?,?)";
        $result = $db_handle->insert($query, 'ssss', array($user_id, $user_name, $clientIP, $cdate));
        return $result;
    }
    
      function insertLog($inw_id,$filename,$cl_id,$servers,$stage,$remarks,$status) {
        $db_handle = new DBController();
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
             $clientIP = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
             $clientIP = $_SERVER['REMOTE_ADDR'];
                }
                $cdate=date('Y-m-d H:i:s');
                $browserDetails = $_SERVER['HTTP_USER_AGENT'];

                //// insert logs as text file start 
                
         if($status==1)     
         {
            $st='Success';
         }
         else
         {
           $st='Failure';  
         }
                 // Define the log file path
   // $logDirectory =$_COOKIE['set_log_path'];
      $logDirectory ='logs/';

    $logFile =  $logDirectory.$filename.'.txt';

    // Check if the log directory exists, and create it if not
    if (!is_dir($logDirectory)) {
        mkdir($logDirectory, 0755, true);
    }
    // Create the log message with a timestamp
    if($cl_id=='2' || $cl_id=='15' || $cl_id=='28' || $cl_id=='52')
    {
           $logMessage = "[" . date('d-m-Y H:i:s') . "] " . $stage." --> " . $servers." --> ".$clientIP." --> ".$remarks." --> ".$st . PHP_EOL;
  
    }
    else
    {
        $logMessage = "[" . date('d-m-Y H:i:s') . "] " . $stage." --> " . $servers." --> ".$clientIP." --> ".$remarks." --> ".$st . PHP_EOL;
  
    }

    // Write the log message to the file in append mode
    file_put_contents($logFile, $logMessage, FILE_APPEND);
                
               //// insert logs as text file end 
                

        $query = "INSERT INTO logs(inw_id,file_name,cl_id,servers,cdatetime,ip,stage,remarks,status) values (?,?,?,?,?,?,?,?,?)";
        $result = $db_handle->insert($query, 'isisssssi', array($inw_id,$filename, $cl_id, $servers, $cdate, $clientIP, $stage, $remarks,$status));
        return $result;
    }
	
    function update($query) {
        mysqli_query($this->conn,$query);
    }

    function getMenuURL($uri) {
	    $db_handle = new DBController();
	    $query = "select * from wf_menu where menu_status='Active' and menu_url = ?";
	    $result = $db_handle->runQuery($query, 's', array($uri));
	    return $result;
    }

    function getMenu() {
	    $db_handle = new DBController();
	    $query = "select * from wf_menu where menu_show=1 and  menu_status = ?  and menu_type='ADMIN' order by menu_order asc";
	    $result = $db_handle->runQuery($query, 's', array('Active'));
	    return $result;
    }

    function getSMenu($mid) {
	    $db_handle = new DBController();
	    $query = "select * from wf_smenu where smenu_status='Active' and menu_id = ?  and smenu_type='ADMIN' order by smenu_order asc";
	    $result = $db_handle->runQuery($query, 'i', array($mid));
	    return $result;
    }
	
	    function getvMenu() {
	    $db_handle = new DBController();
	    $query = "select * from wf_menu where menu_show=1 and  menu_status = ?  and menu_type='VENDOR' order by menu_order asc";
	    $result = $db_handle->runQuery($query, 's', array('Active'));
	    return $result;
    }

    function getvSMenu($mid) {
	    $db_handle = new DBController();
	    $query = "select * from wf_smenu where smenu_status='Active' and menu_id = ?  and smenu_type='VENDOR' order by smenu_order asc";
	    $result = $db_handle->runQuery($query, 'i', array($mid));
	    return $result;
    }

    function getMenuid($emp_id, $uri) {
	    $db_handle = new DBController();
	    $query = "select * from wf_menu,wf_staff_menu where wf_staff_menu.menu_id=wf_menu.menu_id and menu_status='Active' and wf_staff_menu.st_id = ? and menu_url = ?";
	    $result = $db_handle->runQuery($query, 'is', array($emp_id, $uri));
	    return $result;
    }

    function getSubMenuid($emp_id, $uri) {
	    $db_handle = new DBController();
	    $query = "SELECT * FROM wf_staff_submenu INNER JOIN wf_submenu ON wf_submenu.smenu_id=wf_staff_submenu.smenu_id where wf_staff_submenu.st_id = ? and wf_submenu.smenu_url = ?";
	    $result = $db_handle->runQuery($query, 'is', array($emp_id, $uri));
	    return $result;
    }

	function getPermissionByMenuname($emp_id, $menu) {
	    $db_handle = new DBController();
	    $query = "SELECT * FROM wf_staff_menu INNER JOIN wf_menu ON wf_menu.menu_id=wf_staff_menu.menu_id where wf_staff_menu.st_id = ? and wf_staff_menu.menu_id = ?";
	    $result = $db_handle->runQuery($query, 'is', array($emp_id, $menu));
	    return $result;
    }

	function getPermissionBySubMenuname($emp_id, $smenu) {
	    $db_handle = new DBController();
	    $query = "SELECT * FROM wf_staff_submenu INNER JOIN wf_submenu ON wf_submenu.smenu_id=wf_staff_submenu.smenu_id where wf_staff_submenu.st_id = ? and wf_submenu.smenu_id = ?";
	    $result = $db_handle->runQuery($query, 'is', array($emp_id, $smenu));
	    return $result;
    }

	function SelectStaffMenuURL($staffid, $uri) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select *,count(wf_staff_menu.sm_menuid) as count_menu from wf_menu,wf_staff_menu where wf_staff_menu.sm_menuid = wf_menu.menu_id and menu_status='Active' and wf_staff_menu.sm_staffid='".$staffid."' and menu_link='".$uri."'");
        return $result;
    }
	
	function SelectStaffSubMenuURL($staffid, $uri) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT * FROM wf_staff_submenu INNER JOIN wf_submenu ON wf_submenu.smenu_id = wf_staff_submenu.sbm_menuid where wf_staff_submenu.sbm_staffid =  '".$staffid."' and wf_submenu.smenu_link='".$uri."'");
        return $result;
    }
	
	function showSelectSetting() {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select * from wf_settings where set_id = 1");
        return $result;
    }
    
    	function showEmailTemplates($emid) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select * from wf_email_templates where em_id ='".$emid."'");
        return $result;
    }
    
    
	
	function showStaffMenu($login_id) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select * from wf_menu,wf_staff_menu where wf_staff_menu.menu_id=wf_menu.menu_id and menu_status='0' and wf_staff_menu.st_id='".$_COOKIE['emp_id']."' order by menu_pos");
        return $result;
    }
	
	function showStaffSubMenu($login_id,$menu_id) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select * from wf_submenu,wf_staff_submenu where wf_staff_submenu.smenu_id=wf_submenu.smenu_id and wf_submenu.menu_id='".$menu_id."' and wf_staff_submenu.st_id='".$login_id."' and smenu_status='0' order by smenu_pos asc");
        return $result;
    }
	
	function showStafflist($status, $search) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT *,b_name,lt_title from users,wf_branch,wf_staff_type WHERE st_status IN ($status) and users.b_id=wf_branch.b_id and users.st_id=wf_staff_type.st_id $search ORDER BY st_id ASC");
        return $result;
    }
	
	function showStaffDetails($user_id) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select * from users where st_id='".$user_id."'");
        return $result;
    }

	function checkUsername($username, $search){
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT count(user_id) as cstid FROM users WHERE st_uname = '$username' $search");
        return $result;
    }

	function showLoginTypelist($status, $search) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT * FROM wf_staff_type WHERE st_status='$status' $search ORDER BY st_id ASC ");
        return $result;
    }
	
	
	
	function showMenu() {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select * from wf_menu where menu_status='0'");
        return $result;
    }

	function insertMenu($menu_name, $pos, $menu_url, $menu_status, $crdatetime) {
        $db_handle = new DBController();
        $db_handle->runIUQuery("call insertMenu('".$menu_name."','".$pos."','".$menu_url."','".$menu_status."','".$crdatetime."')");
    }
	
	function updateMenu($menu_id, $menu_name, $pos, $menu_url) {
        $db_handle = new DBController();
        $db_handle->runIUQuery("call updateMenu('".$menu_id."','".$menu_name."','".$pos."','".$menu_url."')");
    }

	function deleteMenu($menu_id, $menu_status) {
        $db_handle = new DBController();
        $db_handle->runIUQuery("call deleteMenu('".$menu_id."','".$menu_status."')");
    }

	function MaxPosMenu() {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select max(menu_pos) as menu_pos from wf_menu");
        return $result;
    }

	function insertSubMenu($menu_id, $smenu_name, $smenu_pos, $smenu_url, $smenu_status, $crdatetime) {
        $db_handle = new DBController();
        $db_handle->runIUQuery("call insertSubMenu('".$menu_id."','".$smenu_name."','".$smenu_pos."','".$smenu_url."','".$smenu_status."','".$crdatetime."')");
    }
	
	function updateSubMenu($smenu_id, $menu_id, $smenu_name, $smenu_pos, $smenu_url) {
        $db_handle = new DBController();
        $db_handle->runIUQuery("call updateSubMenu('".$smenu_id."','".$menu_id."','".$smenu_name."','".$smenu_pos."','".$smenu_url."')");
    }

	function deleteSubMenu($smenu_id, $smenu_status) {
        $db_handle = new DBController();
        $db_handle->runIUQuery("call deleteSubMenu('".$smenu_id."','".$smenu_status."')");
    }

	function MaxPosSubMenu() {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select max(smenu_pos) as smenu_pos from wf_submenu");
        return $result;
    }

	function SelectMenu($menu_id) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select * from wf_menu where menu_id='".$menu_id."'");
        return $result;
    }
    
           function SelectMenus() {
	    $db_handle = new DBController();
	    $query = "select * from wf_menu where menu_status=?";
	    $result = $db_handle->runQuery($query, 's', array('Active'));
	    return $result;
    }

	function SelectSubMenu($smenu_id) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select * from wf_submenu where smenu_id='".$smenu_id."'");
        return $result;
    }

	function showRoleMenu($menu_id, $li_id) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select * from wf_role_menu where menu_id='".$menu_id."' and st_id='".$li_id."'");
        return $result;
    }
	
	function showSubMenu($menu_id) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select * from wf_submenu where menu_id='".$menu_id."' and smenu_status='0'");
        return $result;
    }
	
	function showRoleSubMenu($smenu_id, $li_id) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select * from wf_role_submenu where smenu_id='".$smenu_id."' and st_id='".$li_id."'");
        return $result;
    }
	
	function showSubMenulist($stat, $sSearch, $sort_by, $sort_type, $start, $length) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("call selectSubMenus('".$stat."','".$sSearch."', '`".$sort_by."` ".$sort_type."','', '".$start."', '".$length."')");
        return $result;
    }
	
	function showMenulist($stat, $sSearch, $sort_by, $sort_type, $start, $length) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("call selectMenus('".$stat."','".$sSearch."', '`".$sort_by."` ".$sort_type."','', '".$start."', '".$length."')");
        return $result;
    }

    function countMenulist($stat) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select count(menu_id) as count from wf_menu where menu_status = '".$stat."'");
		return $result;
	}

	


	function countSubMenulist($stat) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select count(smenu_id) as count from wf_submenu where smenu_status = '".$stat."'");
		return $result;
	}

	function InsertRoleMenu($st_id, $eres, $st_status, $crdatetime) {
        $db_handle = new DBController();
        $result = $db_handle->runIUQuery("call insertRoleMenu('".$st_id."','".$eres."','".$st_status."','".$crdatetime."')");
		return $result;
	}
	
	function DeleteRoleMenu($st_id) {
        $db_handle = new DBController();
        $result = $db_handle->runIUQuery("call deleteRoleMenu('".$st_id."')");
		return $result;
	}

	function InsertRoleSubMenu($st_id, $seres, $st_status, $crdatetime) {
        $db_handle = new DBController();
        $result = $db_handle->runIUQuery("call insertRoleSubMenu('".$st_id."','".$seres."','".$st_status."','".$crdatetime."')");
		return $result;
	}
	
	function DeleteRoleSubMenu($st_id) {
        $db_handle = new DBController();
        $result = $db_handle->runIUQuery("call deleteRoleSubMenu('".$st_id."')");
		return $result;
	}

	function InsertStaff($st_name, $st_email, $st_pass,$st_menu) {
        $db_handle = new DBController();
         $db_handle->runIUQuery("call insertStaff('".$st_name."','".$st_email."','".md5($st_pass)."','".$st_email."','0','Active','".date('Y-m-d H:i:s')."','2','".$st_menu."',$st_pass)");
        //$result = $db_handle->runBaseQuery("select LAST_INSERT_ID() as stid");		
		return $result;
	}
	
		function InsertEmailTemplate($em_type, $em_subject, $em_message) {
        $db_handle = new DBController();
        $db_handle->runIUQuery("INSERT INTO wf_email_templates(em_type,em_subject,em_message) VALUES ('".$em_type."','".$em_subject."','".$em_message."')");
	}
		function UpdateEmailTemplate($em_id,$em_type, $em_subject, $em_message) {
        $db_handle = new DBController();
        $db_handle->runIUQuery("UPDATE wf_email_templates set  em_type='".$em_type."', em_subject='".$em_subject."',em_message='".$em_message."' WHERE em_id = '".$em_id."'");
	}
	
	function InsertStaffMenu($user_id, $eres, $menu_add, $menu_edit, $menu_delete, $menu_vall, $menu_vselected, $menu_status, $crdatetime) {
        $db_handle = new DBController();
        $db_handle->runIUQuery("call insertStaffMenu('".$user_id."', '".$eres."', '".$menu_add."', '".$menu_edit."', '".$menu_delete."', '".$menu_vall."', '".$menu_vselected."', '".$menu_status."', '".$crdatetime."')");
	}
	
	function InsertStaffSubMenu($user_id, $seres, $smenu_add, $smenu_edit, $smenu_delete, $smenu_vall, $smenu_vselected, $menu_status, $crdatetime) {
        $db_handle = new DBController();
        $db_handle->runIUQuery("call insertStaffSubMenu('".$user_id."', '".$seres."', '".$smenu_add."', '".$smenu_edit."', '".$smenu_delete."', '".$smenu_vall."', '".$smenu_vselected."', '".$menu_status."', '".$crdatetime."')");
	}
	
	function UpdateStaff($user_id, $st_name, $st_pass,$st_menu) {
        $db_handle = new DBController();
        $db_handle->runIUQuery("call updateStaff('".$user_id."','".$st_name."','".md5($st_pass)."','".$st_pass."','".$st_menu."')");
	}
	
	function DeleteStaff($user_id) {
        $db_handle = new DBController();
        $db_handle->runIUQuery("delete from users where  id='".$user_id."'");
		return $result;
	}
	
	function DeleteStaffMenu($user_id) {
        $db_handle = new DBController();
        $db_handle->runIUQuery("delete from wf_staff_menu where st_id='".$user_id."'");
		return $result;
	}
		function DeleteEmailtemplate($em_id) {
        $db_handle = new DBController();
        $db_handle->runIUQuery("delete from wf_email_templates where em_id='".$em_id."'");
		return $result;
	}
	
	function DeleteStaffSubMenu($user_id) {
        $db_handle = new DBController();
        $db_handle->runIUQuery("delete from wf_staff_submenu where st_id='".$user_id."'");
		return $result;
    }
    
	function ChangePassword($st_id, $st_pass){
        $db_handle = new DBController();
        $db_handle->runIUQuery("call changePassword('".$st_id."','".$st_pass."')");
    }

	
	
	function ShowDivision() {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select * from wf_division where scas_status='Y' order by scas_name asc");
		return $result;
	}
	
	
	
  

	function showPackagelist() {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("call selectPackage(0)");
        return $result;
    }
	
	function showPackageDetails($pack_id) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select * from wf_package where pack_id='".$pack_id."'");
        return $result;
    }

	
    
    function showArticlelist($sSearch, $sort_by, $sort_type, $start, $length, $gSearch) {
        $db_handle = new DBController();
		
        $result = $db_handle->runBaseQuery("call selectArticle('".$sSearch."','".$sort_by."','".$sort_type."','".$start."','".$length."', '".$gSearch."')");
		
	//print_r($result);exit;
		return $result;
    }
	
	    function showallArticlelist($sSearch, $sort_by, $sort_type, $start, $length, $gSearch) {
        $db_handle = new DBController();
		
        $result = $db_handle->runBaseQuery("call selectallArticle('".$sSearch."','".$sort_by."','".$sort_type."','".$start."','".$length."', '".$gSearch."')");
		
	//print_r($result);exit;
		return $result;
    }
	
	
    function showHoldArticlelist($sSearch, $sort_by, $sort_type, $sort_by_d, $sort_type_d, $start, $length, $gSearch) {
        $db_handle = new DBController();
		
        $result = $db_handle->runBaseQuery("call selectHoldArticle('".$sSearch."','".$sort_by."','".$sort_type."','".$sort_by_d."','".$sort_type_d."','".$start."','".$length."', '".$gSearch."')");
		
	//print_r($result);exit;
		return $result;
    }
    function showHighPriorityArticlelist($sSearch, $sort_by, $sort_type, $sort_by_d, $sort_type_d, $start, $length, $gSearch) {
        $db_handle = new DBController();
		
        $result = $db_handle->runBaseQuery("call selectHighpriorityArticle('".$sSearch."','".$sort_by."','".$sort_type."','".$sort_by_d."','".$sort_type_d."','".$start."','".$length."', '".$gSearch."')");
		
	//print_r($result);exit;
		return $result;
    }

    function showClientReviewArticlelist($sSearch, $sort_by, $sort_type, $sort_by_d, $sort_type_d, $start, $length, $gSearch) {
        $db_handle = new DBController();
		
        $result = $db_handle->runBaseQuery("call selectClientReviewArticle('".$sSearch."','".$sort_by."','".$sort_type."','".$sort_by_d."','".$sort_type_d."','".$start."','".$length."', '".$gSearch."')");
		
	//print_r($result);exit;
		return $result;
    }
    

    function showDispatchArticlelist($sSearch, $sort_by, $sort_type, $sort_by_d, $sort_type_d, $start, $length, $gSearch) {
        $db_handle = new DBController();
		
        $result = $db_handle->runBaseQuery("call selectDispatchArticle('".$sSearch."','".$sort_by."','".$sort_type."','".$sort_by_d."','".$sort_type_d."','".$start."','".$length."', '".$gSearch."')");
		
	//print_r($result);exit;
		return $result;
    }



    function SelectASPHistoryArticles($sSearch, $sort_by, $sort_type, $start, $length, $gSearch) {
        $db_handle = new DBController();
       $result = $db_handle->runBaseQuery("call selectASPHistoryArticle('".$sSearch."','".$sort_by."','".$sort_type."','".$start."','".$length."', '".$gSearch."')");
      return $result;
    }
     
    
    	function countArticlelist($sSearch, $gSearch) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT  count(wd.id) as count FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  AND wd.status NOT IN ('Delivery','Client Review','Completed','Query','Hold') and  (pub_id LIKE '%".$sSearch."%' or article_title LIKE '%".$sSearch."%' or c.cust_name LIKE '%".$sSearch."%' or j.j_code LIKE '%".$sSearch."%' or wd.stage LIKE '%".$sSearch."%' or wd.status LIKE '%".$sSearch."%' or wd.manuscript_count LIKE '%".$sSearch."%' or r.correction_pages LIKE '%".$sSearch."%') $gSearch");
		return $result;
    }
	
	    	function countallArticlelist($sSearch, $gSearch) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT  count(wd.id) as count FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id   and  (pub_id LIKE '%".$sSearch."%' or article_title LIKE '%".$sSearch."%' or c.cust_name LIKE '%".$sSearch."%' or j.j_code LIKE '%".$sSearch."%' or wd.stage LIKE '%".$sSearch."%' or wd.status LIKE '%".$sSearch."%' or wd.manuscript_count LIKE '%".$sSearch."%' or r.correction_pages LIKE '%".$sSearch."%') $gSearch");
		return $result;
    }

    function countHoldArticlelist($sSearch, $gSearch) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT  count(wd.id) as count FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  AND wd.status IN ('Hold','Query') and  (pub_id LIKE '%".$sSearch."%' or article_title LIKE '%".$sSearch."%') $gSearch");
		return $result;
    }


    function countHighPriorityArticlelist($sSearch, $gSearch) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT  count(wd.id) as count FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  AND wd.status NOT IN ('Delivery','Client Review','Completed') and wd.highpriority = 1 and  (pub_id LIKE '%".$sSearch."%' or article_title LIKE '%".$sSearch."%') $gSearch");
		return $result;
    }
    function countClientReviewArticlelist($sSearch, $gSearch) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT  count(wd.id) as count FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  AND wd.status IN ('Client Review') and  (pub_id LIKE '%".$sSearch."%' or article_title LIKE '%".$sSearch."%') $gSearch");
		return $result;
    }
    function countDispatchArticlelist($sSearch, $gSearch) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT  count(wd.id) as count FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = (select r_id from inw_revisions_dtl where inw_id = wd.`id` order by r_id DESC limit 1)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  AND wd.status IN ('Delivery') and  (pub_id LIKE '%".$sSearch."%' or article_title LIKE '%".$sSearch."%') $gSearch");
		return $result;
    }

    function CountASPHistoryArticles($sSearch, $gSearch) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT  count(wd.id) as count FROM `asp_articles_history` as wd where 1=1 and  (journal LIKE '%".$sSearch."%' or article_title LIKE '%".$sSearch."%') $gSearch");
		return $result;
    }

    
    

        function countArticle($stat) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT count(id) as count FROM `inw_inward_dtl` WHERE pub_id= '".$stat."'");
		return $result;
	}


   function countArticlestaff($staff) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT count(id) as count FROM `inw_inward_dtl` as wd where  wd.workflow_id!=0 and  user_id= '".$staff."'");
		return $result;
    }

      
    
    function InsertArticle($cust_id,$j_id,$issueorgroup_folder,$pub_id,$article_title,$recv_dt,$due_dt,$test_article,$highpriority,$manuscript_count,$fig,$tab,$stage,$wf_id,$inw_type,$complexity,$corresponding_author,$corresponding_mail,$location_id,$doi,$pub_dt,$accpt_dt,$instructions,$notes,$status) {
        $db_handle = new DBController();
        $db_handle->runIUQuery("call InsertArticle('".$cust_id."','".$j_id."', '".$issueorgroup_folder."', '".$pub_id."', '".$article_title."', '".$recv_dt."', '".$due_dt."', '".$test_article."', '".$highpriority."', '".$manuscript_count."', '".$fig."', '".$tab."', '".$stage."', '".$wf_id."', '".$inw_type."', '".$complexity."', '".$corresponding_author."', '".$corresponding_mail."', '".$location_id."', '".$doi."', '".$pub_dt."', '".$accpt_dt."', '".$instructions."', '".$notes."', '".$status."', @lastID)");
//print_r("call InsertArticle('".$cust_id."','".$j_id."', '".$issueorgroup_folder."', '".$pub_id."', '".$article_title."', '".$recv_dt."', '".$due_dt."', '".$test_article."', '".$highpriority."', '".$manuscript_count."', '".$fig."', '".$tab."', '".$stage."', '".$wf_id."', '".$inw_type."', '".$complexity."', '".$corresponding_author."', '".$corresponding_mail."', '".$location_id."', '".$doi."', '".$pub_dt."', '".$accpt_dt."', '".$instructions."', '".$notes."', '".$status."', @lastID)");
        $result = $db_handle->runBaseQuery("SELECT @lastID AS last_inserted_id");
		
				//print_r($result);exit;


                  return $result;
        }

            function InsertArticleCAuthor($inw_id,$cust_id, $j_id, $journal_title, $pub_id, $author_name, $author_email,$corresponding_author,$user_id,$created_dt) {
            $db_handle = new DBController();
            $db_handle->runIUQuery("call InsertArticleCAuthor('".$inw_id."','".$cust_id."', '".$j_id."', '".$journal_title."', '".$pub_id."', '".$author_name."', '".$author_email."', 'yes','".$_COOKIE['user_id']."','".$created_dt."', @calastID)");

            $result = $db_handle->runBaseQuery("SELECT @calastID AS last_inserted_id");
            return $result;
            }

            function InsertArticleAuthor($inw_id,$cust_id, $j_id, $journal_title, $pub_id, $author_name,$orcid_id,$corresponding_author,$user_id,$created_dt) {
            $db_handle = new DBController();
            $db_handle->runIUQuery("call InsertArticleAuthor('".$inw_id."','".$cust_id."', '".$j_id."', '".$journal_title."', '".$pub_id."', '".$author_name."', '".$orcid_id."', 'no','".$_COOKIE['user_id']."','".$created_dt."', @alastID)");

            $result = $db_handle->runBaseQuery("SELECT @alastID AS last_inserted_id");
            return $result;
            }


         
            
          function selectArticleData($aid) {
                $db_handle = new DBController();
                $result = $db_handle->runBaseQuery("SELECT * FROM `inw_inward_dtl` as wd left join users as  s on s.user_id=wd.user_id left join adm_customer_master as c on  wd.cust_id = c.id  left join adm_journals as j on wd.j_id = j.j_id  WHERE wd.cust_id = c.id and wd.j_id = j.j_id and wd.id='".$aid."'");
                return $result; 
            }
            function selectupdateArticle($aid) {
                $db_handle = new DBController();
                $result = $db_handle->runBaseQuery("SELECT * FROM `inw_inward_dtl` as wd where wd.id='".$aid."'");
                return $result; 
            } 
            
        
            function updateArticle($inw_id,$corrections,$stage_with) {

                $db_handle = new DBController();
                $db_handle->runIUQuery("call updateArticle('".$inw_id."','".$corrections."','".$stage_with."')");


            }

            function UpdateArticlesdata($inw_id,$cust_id,$j_id,$issueorgroup_folder,$pub_id,$article_title,$recv_dt,$due_dt,$test_article,$highpriority,$manuscript_count,$fig,$tab,$stage,$wf_id,$inw_type,$complexity,$corresponding_author,$corresponding_mail,$location_id,$doi,$pub_dt,$accpt_dt,$instructions,$notes,$status) {

                $db_handle = new DBController();
                $db_handle->runIUQuery("call UpdateArticlesdata('".$inw_id."','".$cust_id."','".$j_id."', '".$issueorgroup_folder."', '".$pub_id."', '".$article_title."', '".$recv_dt."', '".$due_dt."', '".$test_article."', '".$highpriority."', '".$manuscript_count."', '".$fig."', '".$tab."', '".$stage."', '".$wf_id."', '".$inw_type."', '".$complexity."', '".$corresponding_author."', '".$corresponding_mail."', '".$location_id."', '".$doi."', '".$pub_dt."', '".$accpt_dt."', '".$instructions."', '".$notes."', '".$status."')");

            }

            
            
           
              function updateArticleauthorstage($inw_id) {

                 $db_handle = new DBController();
                // $query = "update  inw_inward_dtl set stage_with='TRANSFORMA',stage='REV1',corrections='',status='PAG',wf_id=148 where id='".$inw_id."'";
                 $db_handle->runIUQuery("update  inw_inward_dtl set stage_with='TRANSFORMA',stage='REV1',corrections='',status='PAG',wf_id=148 where id='".$inw_id."'");
                // $result = $db_handle->update($query, 'i', array($inw_id));
                // return $result;
            }
            
             function updateArticleapproved($inw_id) {

                 $db_handle = new DBController();
                 $db_handle->runIUQuery("update inw_proof_dtl set is_approved='1' where project_id='".$inw_id."'");
                // return $result;
            }


            function showCAuthorlist($aid) {
                $db_handle = new DBController();
                $result = $db_handle->runBaseQuery("SELECT * from inw_inward_dtl_author WHERE  inw_id ='".$aid."' and corresponding_author='yes'   ORDER BY author_id asc");
                return $result;
            }
    
            function showAuthorlist($aid) {
                $db_handle = new DBController();
                $result = $db_handle->runBaseQuery("SELECT * from inw_inward_dtl_author WHERE  inw_id ='".$aid."' and corresponding_author='no'  ORDER BY author_id asc");
                return $result;
            }

   

            function DeleteAuthorlist($aid) {
                $db_handle = new DBController();
                $db_handle->runIUQuery("call DeleteAuthorlist( '".$aid."')");
            }
            
                  function Selectstaffs($sSearch, $sort_by, $sort_type, $start, $length, $gSearch) {
          $db_handle = new DBController();
         $result = $db_handle->runBaseQuery("call selectStaff('".$sSearch."','".$sort_by."','".$sort_type."','".$start."','".$length."', '".$gSearch."')");
        return $result;
    }

    function SelectCustomers($sSearch, $sort_by, $sort_type, $start, $length, $gSearch) {
        $db_handle = new DBController();
       $result = $db_handle->runBaseQuery("call SelectCustomers('".$sSearch."','".$sort_by."','".$sort_type."','".$start."','".$length."', '".$gSearch."')");
      return $result;
  }

      function SelectJournals($sSearch, $sort_by, $sort_type, $start, $length, $gSearch) {
        $db_handle = new DBController();
       $result = $db_handle->runBaseQuery("call SelectJournals('".$sSearch."','".$sort_by."','".$sort_type."','".$start."','".$length."', '".$gSearch."')");
      return $result;
  }
    
     function Selectemailtemplate($sSearch, $sort_by, $sort_type, $start, $length, $gSearch) {
          $db_handle = new DBController();
         $result = $db_handle->runBaseQuery("call selectemailtemplate('".$sSearch."','".$sort_by."','".$sort_type."','".$start."','".$length."', '".$gSearch."')");
        return $result;
    }
    
    function SelectJournaltype($sSearch, $sort_by, $sort_type, $start, $length, $gSearch) {
        $db_handle = new DBController();
       $result = $db_handle->runBaseQuery("call SelectJournaltype('".$sSearch."','".$sort_by."','".$sort_type."','".$start."','".$length."', '".$gSearch."')");
      return $result;
  }

  

  function SelectFolders($sSearch, $sort_by, $sort_type, $start, $length, $gSearch) {
    $db_handle = new DBController();
   $result = $db_handle->runBaseQuery("call SelectFolders('".$sSearch."','".$sort_by."','".$sort_type."','".$start."','".$length."', '".$gSearch."')");
  return $result;
}



function SelectDepartment($sSearch, $sort_by, $sort_type, $start, $length, $gSearch) {
    $db_handle = new DBController();
   $result = $db_handle->runBaseQuery("call SelectDepartment('".$sSearch."','".$sort_by."','".$sort_type."','".$start."','".$length."', '".$gSearch."')");
  return $result;
}


function SelectWorkflow($sSearch, $sort_by, $sort_type, $start, $length, $gSearch) {
    $db_handle = new DBController();
   $result = $db_handle->runBaseQuery("call SelectWorkflow('".$sSearch."','".$sort_by."','".$sort_type."','".$start."','".$length."', '".$gSearch."')");
  return $result;
}

function SelectMovefiles($sSearch, $sort_by, $sort_type, $start, $length, $gSearch) {
    $db_handle = new DBController();
   $result = $db_handle->runBaseQuery("call SelectMovefiles('".$sSearch."','".$sort_by."','".$sort_type."','".$start."','".$length."', '".$gSearch."')");
  return $result;
}

function SelectChecklist($sSearch, $sort_by, $sort_type, $start, $length, $gSearch) {
    $db_handle = new DBController();
   $result = $db_handle->runBaseQuery("call SelectChecklist('".$sSearch."','".$sort_by."','".$sort_type."','".$start."','".$length."', '".$gSearch."')");
  return $result;
}

function SelectDispatchEmails($sSearch, $sort_by, $sort_type, $start, $length, $gSearch) {
    $db_handle = new DBController();
   $result = $db_handle->runBaseQuery("call SelectDispatchEmails('".$sSearch."','".$sort_by."','".$sort_type."','".$start."','".$length."', '".$gSearch."')");
  return $result;
}


function SelectClientFeedback($sSearch, $sort_by, $sort_type, $start, $length, $gSearch) {
    $db_handle = new DBController();
   $result = $db_handle->runBaseQuery("call SelectClientFeedback('".$sSearch."','".$sort_by."','".$sort_type."','".$start."','".$length."', '".$gSearch."')");
  return $result;
}

function SelectArticlequery($sSearch, $sort_by, $sort_type, $start, $length, $gSearch) {
    $db_handle = new DBController();
   $result = $db_handle->runBaseQuery("call SelectArticlequery('".$sSearch."','".$sort_by."','".$sort_type."','".$start."','".$length."', '".$gSearch."')");
  return $result;
}


function SelectArticletype($sSearch, $sort_by, $sort_type, $start, $length, $gSearch) {
    $db_handle = new DBController();
   $result = $db_handle->runBaseQuery("call SelectArticletype('".$sSearch."','".$sort_by."','".$sort_type."','".$start."','".$length."', '".$gSearch."')");
  return $result;
}

function SelectScheduleMail($sSearch, $sort_by, $sort_type, $start, $length, $gSearch) {
    $db_handle = new DBController();
   $result = $db_handle->runBaseQuery("call SelectScheduleMail('".$sSearch."','".$sort_by."','".$sort_type."','".$start."','".$length."', '".$gSearch."')");
  return $result;
}

function SelectArchivemaster($sSearch, $sort_by, $sort_type, $start, $length, $gSearch) {
    $db_handle = new DBController();
   $result = $db_handle->runBaseQuery("call SelectArchivemaster('".$sSearch."','".$sort_by."','".$sort_type."','".$start."','".$length."', '".$gSearch."')");
  return $result;
}

function SelectShiftsettings($sSearch, $sort_by, $sort_type, $start, $length, $gSearch) {
    $db_handle = new DBController();
   $result = $db_handle->runBaseQuery("call SelectShiftsettings('".$sSearch."','".$sort_by."','".$sort_type."','".$start."','".$length."', '".$gSearch."')");
  return $result;
}

function SelectEmployees($sSearch, $sort_by, $sort_type, $start, $length, $gSearch) {
    $db_handle = new DBController();
   $result = $db_handle->runBaseQuery("call SelectEmployees('".$sSearch."','".$sort_by."','".$sort_type."','".$start."','".$length."', '".$gSearch."')");
  return $result;
}

function SelectEmployeesTarget($sSearch, $sort_by, $sort_type, $start, $length, $gSearch) {
    $db_handle = new DBController();
   $result = $db_handle->runBaseQuery("call SelectEmployeesTarget('".$sSearch."','".$sort_by."','".$sort_type."','".$start."','".$length."', '".$gSearch."')");
  return $result;
}

function SelectEmployeeRole($sSearch, $sort_by, $sort_type, $start, $length, $gSearch) {
    $db_handle = new DBController();
   $result = $db_handle->runBaseQuery("call SelectEmployeeRole('".$sSearch."','".$sort_by."','".$sort_type."','".$start."','".$length."', '".$gSearch."')");
  return $result;
}



    function Countstaffs($sSearch, $gSearch) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT count(user_id) as count from   `users` where  staff_status='Active' and    (staff_name like '%".$sSearch."%' or staff_email like '%".$sSearch."%')  $gSearch");
		return $result;
    }

    
    function CountCustomers($sSearch, $gSearch) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT count(id) as count from   `adm_customer_master` where   (cust_name like '%".$sSearch."%' or email like '%".$sSearch."%')  $gSearch");
		return $result;
    }

    function CountJournals($sSearch, $gSearch) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT count(j_id) as count from   `adm_journals` where   (j_name like '%".$sSearch."%' or j_code like '%".$sSearch."%')  $gSearch");
		return $result;
    }
    function CountJournaltype($sSearch, $gSearch) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT count(jt_id) as count from   `journal_type` where   (jt_name like '%".$sSearch."%' or jt_name like '%".$sSearch."%')  $gSearch");
		return $result;
    }

    function CountFolders($sSearch, $gSearch) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT count(jf_id) as count FROM inw_journal_folders as dm   LEFT JOIN adm_customer_master as cm ON dm.cust_id = cm.id LEFT JOIN adm_journals as j ON j.j_id = dm.j_id where   (dm.folder_name like '%".$sSearch."%' or cm.cust_name like '%".$sSearch."%')  $gSearch");
		return $result;
    }

    function CountDepartment($sSearch, $gSearch) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT count(dept_code) as count from   `adm_dept_master` where   (dept_name like '%".$sSearch."%' or dept_sn like '%".$sSearch."%')  $gSearch");
		return $result;
    }

    function CountWorkflow($sSearch, $gSearch) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT count(wf_id) as count from   `adm_workflow_master` where   (wf_name like '%".$sSearch."%' or wf_name like '%".$sSearch."%')  $gSearch");
		return $result;
    }

    function CountChecklist($sSearch, $gSearch) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT count(cl_id) as count from   `jbm_checklist` where   (stage like '%".$sSearch."%' or description like '%".$sSearch."%')  $gSearch");
		return $result;
    }

    function CountMovefiles($sSearch, $gSearch) {
        $db_handle = new DBController();

      $result = $db_handle->runBaseQuery("SELECT count(mf_id) as count FROM inw_move_files as dm  LEFT JOIN adm_customer_master as cm ON dm.cust_id = cm.id 
        LEFT JOIN adm_journals as j ON j.j_id = dm.j_id LEFT JOIN adm_dept_master as d ON d.dept_code = dm.dept_id where   (dept_name like '%".$sSearch."%' or j_code like '%".$sSearch."%')   $gSearch");
		return $result;
    }
    function CountDispatchEmails($sSearch, $gSearch) {
        $db_handle = new DBController();

      $result = $db_handle->runBaseQuery("SELECT count(dm_id) as count FROM inw_dispatch_mail_master as dm  LEFT JOIN adm_customer_master as cm ON dm.cust_id = cm.id LEFT JOIN adm_journals as j ON j.j_id = dm.j_id where   (j.j_code like '%".$sSearch."%' or stage like '%".$sSearch."%')   $gSearch");
		return $result;
    }

    function CountClientFeedback($sSearch, $gSearch) {
        $db_handle = new DBController();

      $result = $db_handle->runBaseQuery("SELECT count(f_id) as count FROM inw_client_feedback as dm  LEFT JOIN adm_customer_master as cm ON dm.cust_id = cm.id LEFT JOIN adm_journals as j ON j.j_id = dm.j_id where   (article like '%".$sSearch."%' or stage like '%".$sSearch."%')   $gSearch");
		return $result;
    }

    function CountArticlequery($sSearch, $gSearch) {
        $db_handle = new DBController();

      $result = $db_handle->runBaseQuery("SELECT count(qid) as count FROM tbl_query_client as dm LEFT JOIN adm_customer_master as cm ON dm.cust_id = cm.id LEFT JOIN adm_journals as j ON j.j_id = dm.jid where   (subject like '%".$sSearch."%' or j_code like '%".$sSearch."%')   $gSearch");
		return $result;
    }

    function CountArticletype($sSearch, $gSearch) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT count(at_id) as count FROM  inw_article_type  where   (at_title like '%".$sSearch."%' or at_status like '%".$sSearch."%')  $gSearch");
		return $result;
    }

    
    function CountScheduleMail($sSearch, $gSearch) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT count(id) as count FROM  adm_schedule_mail_details  where   (type like '%".$sSearch."%' or from_email like '%".$sSearch."%')  $gSearch");
		return $result;
    }

    function CountArchivemaster($sSearch, $gSearch) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT count(dma.id) as count FROM  adm_archive_master as dma LEFT JOIN adm_customer_master as cm ON dma.cust_id = cm.id LEFT JOIN adm_journals as j ON j.j_id = dma.j_id  where   (stage like '%".$sSearch."%' or days like '%".$sSearch."%')  $gSearch");
		return $result;
    }
    
    function CountShiftsettings($sSearch, $gSearch) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT count(sm_id) as count FROM  shift_masters  where   (sm_title like '%".$sSearch."%' or sm_from like '%".$sSearch."%')  $gSearch");
		return $result;
    }

    function CountEmployees($sSearch, $gSearch) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT count(id) as count FROM  users as u LEFT JOIN user_role_master as um ON u.user_level = um.user_role_id LEFT JOIN adm_dept_master as dm ON dm.dept_code = u.department_id  where   (full_name like '%".$sSearch."%' or user_email like '%".$sSearch."%')  $gSearch");
		return $result;
    }
	
	    function CountEmployeesTarget($sSearch, $gSearch) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT count(id) as count FROM  user_target_assign as uta LEFT JOIN users as u ON u.id = uta.user_id LEFT JOIN adm_dept_master as dm ON dm.dept_code = u.department_id  where   (u.full_name like '%".$sSearch."%' or u.user_email like '%".$sSearch."%')  $gSearch");
		return $result;
    }

    function CountEmployeeRole($sSearch, $gSearch) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT count(user_role_id) as count FROM  user_role_master where   (user_role_name like '%".$sSearch."%' or user_role_name like '%".$sSearch."%')  $gSearch");
		return $result;
    }
     function Countemailtemplate($sSearch, $gSearch) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT count(em_id) as count from   `wf_email_templates` where  em_status='Active' and    (em_type like '%".$sSearch."%')  $gSearch");
		return $result;
    }
    
    
     function Countstaffemail($staff_email) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT count(user_id) as count from   `users` where  staff_status='Active' and staff_email='".$staff_email."'");
		return $result;
    }
    
    
    	function Updatesaffpassword($staffid, $password) {
        $db_handle = new DBController();
        $db_handle->runIUQuery("UPDATE users set staff_pass='".md5($password)."', staff_apass='".$password."' where  id = '".$staffid."'");
    }
    
    function Updatesettings($formname, $set_url, $set_bpsize, $set_fp_path, $set_rev_path, $set_ftp_server, $set_ftp_port, $set_ftp_user_name, $set_ftp_user_pass, $set_ftp_path, $set_workflow_path, $set_aws_ftp_server, $set_aws_ftp_port, $set_aws_ftp_user_name, $set_aws_ftp_user_pass, $set_aws_ftp_path, $set_email) 
    {
        $db_handle = new DBController();
        
       
        $db_handle->runIUQuery("UPDATE wf_settings set formname='".$formname."',set_url='".$set_url."',set_bpsize='".$set_bpsize."',
        set_fp_path='".$set_fp_path."',set_rev_path='".$set_rev_path."',set_ftp_server='".$set_ftp_server."',set_ftp_port='".$set_ftp_port."',set_ftp_user_name='".$set_ftp_user_name."',
        set_ftp_user_pass='".$set_ftp_user_pass."',set_ftp_path='".$set_ftp_path."',set_workflow_path='".$set_workflow_path."',set_aws_ftp_server='".$set_aws_ftp_server."',set_aws_ftp_port='".$set_aws_ftp_port."',
        set_aws_ftp_user_name='".$set_aws_ftp_user_name."',set_aws_ftp_user_pass='".$set_aws_ftp_user_pass."',set_aws_ftp_path='".$set_aws_ftp_path."',set_email='".$set_email."' WHERE set_id = '1'");
    }
    
    
        
                    function Selectstafflist($user_id) {
        $db_handle = new DBController();
        $query = "Select * from users where  id=? ";
        $result = $db_handle->runQuery($query, 'i', array($user_id));
        return $result;
    }
    
    
    // Call your processing function and pass the last inserted ID
    function processLastInsertedID($lastInsertedID)
    {
        echo $lastInsertedID;
    
	}



    function showCustomerlist() {
        $db_handle = new DBController();
        // $result = $db_handle->runBaseQuery("SELECT * from adm_journals WHERE j_cust_id=66  ORDER BY j_code asc");
        $result = $db_handle->runBaseQuery("SELECT * from adm_customer_master ORDER BY cust_name asc");

        return $result;
    }
	
	    function showCustomerlistbyuser($cust_id,$ocust_id) {
        $db_handle = new DBController();
        // $result = $db_handle->runBaseQuery("SELECT * from adm_journals WHERE j_cust_id=66  ORDER BY j_code asc");
        $result = $db_handle->runBaseQuery("SELECT * from adm_customer_master where id = '".$cust_id."' OR FIND_IN_SET(id,'".$ocust_id."')  ORDER BY cust_name asc");

        return $result;
    }

    
    function showJournallist($status, $search) {
        $db_handle = new DBController();
        // $result = $db_handle->runBaseQuery("SELECT * from adm_journals WHERE j_cust_id=66  ORDER BY j_code asc");
        $result = $db_handle->runBaseQuery("SELECT * from adm_journals ORDER BY j_code asc");

        return $result;
    }
	
	    function showJournallistbyuser($cust_id,$ocust_id) {
        $db_handle = new DBController();
        // $result = $db_handle->runBaseQuery("SELECT * from adm_journals WHERE j_cust_id=66  ORDER BY j_code asc");
        $result = $db_handle->runBaseQuery("SELECT * from adm_journals where j_cust_id = '".$cust_id."' OR FIND_IN_SET(j_cust_id,'".$ocust_id."') ORDER BY j_code asc");

        return $result;
    }

    function showjournalscustomerwise($cust_id) {
        $db_handle = new DBController();
        $cid = str_replace("-", ",", $cust_id);

        // $result = $db_handle->runBaseQuery("SELECT * from adm_journals WHERE j_cust_id=66  ORDER BY j_code asc");
        $result = $db_handle->runBaseQuery("SELECT * from adm_journals where j_cust_id in (".$cid.") ORDER BY j_code asc");

        return $result;
    }
    
    function showArticleTransaction($inw_id, $stage) {
        $db_handle = new DBController();
         $result = $db_handle->runBaseQuery("SELECT u.full_name,t.current_status,t.id FROM `inw_transactions` as t,users as u WHERE t.process_user = u.id AND t.`project_id` = '".$inw_id."'  AND t.current_status NOT IN ('Completed','Take Over','Query','Hold') AND t.stage = '".$stage."' AND (t.id=(select id from `inw_transactions` where dept!=9 and `project_id` = '".$inw_id."' ORDER BY `id` DESC limit 1) )  ORDER BY t.`id` DESC limit 1");

       //echo "SELECT u.full_name,t.current_status,t.id FROM `inw_transactions` as t,users as u WHERE t.process_user = u.id AND t.`project_id` = '".$inw_id."'  AND t.current_status NOT IN ('Completed','Take Over','Query','Hold') AND t.stage = '".$stage."' AND (t.id=(select id from `inw_transactions` where dept!=9 and `project_id` = '".$inw_id."' ORDER BY `id` DESC limit 1) )  ORDER BY t.`id` DESC limit 1";
        return $result;
    }
	
	    function showArticleTransactionlimitr($inw_id, $stage) {
        $db_handle = new DBController();
         $result = $db_handle->runBaseQuery("SELECT t.id FROM `inw_transactions` as t WHERE  t.`project_id` = '".$inw_id."'  AND t.current_status NOT IN ('Completed','Take Over','Query','Hold') AND t.stage = '".$stage."' AND (t.id=(select id from `inw_transactions` where dept!=9 and `project_id` = '".$inw_id."' ORDER BY `id` DESC limit 1) )  ORDER BY t.`id` DESC limit 1");
        return $result;
    }

    	

    
        function Selectalogs($sSearch, $sort_by, $sort_type, $start, $length, $gSearch) {
        $db_handle = new DBController();
    $result = $db_handle->runBaseQuery("call selectalogs('".$sSearch."','".$sort_by."','".$sort_type."','".$start."','".$length."', '".$gSearch."')");

        return $result;
    }
    
            function Selectalogdetails($sSearch, $sort_by, $sort_type, $start, $length, $gSearch) {
        $db_handle = new DBController();
     $result = $db_handle->runBaseQuery("call selectalogdetails('".$sSearch."','".$sort_by."','".$sort_type."','".$start."','".$length."', '".$gSearch."')");

        return $result;
    }

    
    
       	function countaloglist($sSearch, $gSearch) {
        $db_handle = new DBController();
        
        $result = $db_handle->runBaseQuery("SELECT count(wd.id) as count FROM `inw_inward_dtl` as wd left join users as  s on s.user_id=wd.user_id left join adm_customer_master as c on  wd.cust_id = c.id  left join adm_journals as j on wd.j_id = j.j_id
       where  wd.cust_id=66  and    (pub_id like '%".$sSearch."%' or article_title like '%".$sSearch."%')  $gSearch");
		return $result;
    }
    
           	function countalogdetailslist($sSearch, $gSearch) {
        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("SELECT count(lg.id) as count FROM `logs` as lg left join  inw_inward_dtl as wd on wd.pub_id = lg.file_name left join users as  s on s.user_id=wd.user_id left join adm_customer_master as c on  wd.cust_id = c.id  left join adm_journals as j on wd.j_id = j.j_id
        left join  article_log_checklist as lgc on lg.cl_id = lgc.lc_id where  wd.cust_id=66  and    (wd.pub_id like '%".$sSearch."%' or wd.article_title like '%".$sSearch."%')  $gSearch");
		return $result;
    }

   
    
    function getvaloperator($user_id)
{


        $db_handle = new DBController();
        $result = $db_handle->runBaseQuery("select * from users where  id = '".$user_id."'");
		return $result;
}


function valoperator($feat)
{
$staffdetails_access= $auth->getvaloperator($_COOKIE['user_id']);
$menu_accessmenu=explode(",",$staffdetails_access[0]['staff_menu']);

    if(!in_array($feat, $menu_accessmenu))
    {
      echo'<script language=javascript>alert("Access Denied");</script>';
      echo("<script language='javascript'>window.history.back();</script>");
    exit;
    }
}


function SelectManageissues($sSearch, $sort_by, $sort_type, $start, $length, $gSearch) {
    $db_handle = new DBController();
   $result = $db_handle->runBaseQuery("call Manageissues('".$sSearch."','".$sort_by."','".$sort_type."','".$start."','".$length."', '".$gSearch."')");
  return $result;
}


function CountManageissues($sSearch, $gSearch) {
        $db_handle = new DBController();
        
        $result = $db_handle->runBaseQuery("SELECT count(vi.vi_id) as count,(SELECT COUNT(id) FROM inw_inward_dtl WHERE v_issue_id = vi.vi_id AND status='Delivery') as ccount,(SELECT COUNT(id) FROM inw_inward_dtl WHERE v_issue_id = vi.vi_id AND status !='Delivery') as pcount  FROM inw_volume_issues as vi LEFT JOIN inw_issue_corrections as r ON (vi.`vi_id` =r.`vi_id` and r.ic_id = (select ic_id from inw_issue_corrections where vi_id = r.`vi_id` order by ic_id DESC limit 1))  , adm_customer_master as c, adm_journals as j WHERE vi.cust_id = c.id and vi.j_id = j.j_id and vi.status NOT IN ('Delivery','Client Review','Completed') $gSearch");
		return $result;
    }

}
?>