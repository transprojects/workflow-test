<?php 
	 require_once('includes/includes.php');

    $dep_id = $_GET['dep_id'];
	
if (isset($_GET['dep_id'])) {
	
	
	
		if($_GET['dep_id']=='0')
		{
    $query = "SELECT id,full_name FROM users WHERE   approved=1 and  (department_id in ('1','2','22') or  access_other_depts in ('1','2','22')) and  worklocation in ('TVL','CHN')";
		}
		else
		{
    $query = "SELECT id,full_name FROM users WHERE  user_level in ('2','3','4','5','6','7') and approved=1 and  department_id = ".$dep_id." and  worklocation in ('TVL','CHN')";
		}
    $result = mysqli_query($db, $query);

    $csource = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $csource[] = $row;
    }

    echo json_encode($csource);
} else {
    echo json_encode(array());
}

mysqli_close($db);
?>