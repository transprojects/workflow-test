<?php
date_default_timezone_set('Asia/Kolkata');

class DBController {
	private $host = "localhost";
	
	private $user = "root";
	private $password = "root@123";
	private $database = "workflow";
	//private $database = "trans_workflow_portal";
	

	private $conn;
	
    function __construct() {
        $this->conn = $this->connectDB();
	}
	
	function connectDB() {
		$conn = mysqli_connect($this->host,$this->user,$this->password,$this->database);
		return $conn;
	}
	
    /* function runBaseQuery($query) {
		$result = mysqli_query($this->conn,$query);
		while($row=mysqli_fetch_assoc($result)) {
		$resultset[] = $row;
		}		
		if(!empty($resultset))
		return $resultset;
    } */
	
	function runBaseQuery($query) {
    $result = mysqli_query($this->conn, $query);
    
    // Check if the query was successful
    if ($result === false) {
        // Handle the error, possibly by logging or displaying an error message
       // error_log("Query failed: " . mysqli_error($this->conn));
        return false; // or return an empty array if you prefer
    }
    
    $resultset = []; // Initialize the result set as an empty array
    while ($row = mysqli_fetch_assoc($result)) {
        $resultset[] = $row;
    }
    
    if (!empty($resultset)) {
        return $resultset;
    }
    
    return null; // or return an empty array if you prefer
}

	
	
	
    function runIUQuery($query) {
		$result = mysqli_query($this->conn,$query);
    }    
    
    function runQuery($query, $param_type, $param_value_array) {
        
        $sql = $this->conn->prepare($query);
        $this->bindQueryParams($sql, $param_type, $param_value_array);
        $sql->execute();
        $result = $sql->get_result();
        
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $resultset[] = $row;
            }
        }
        
        if(!empty($resultset)) {
            return $resultset;
        }
    }
    
    function bindQueryParams($sql, $param_type, $param_value_array) {
        $param_value_reference[] = & $param_type;
        for($i=0; $i<count($param_value_array); $i++) {
            $param_value_reference[] = & $param_value_array[$i];
        }
        call_user_func_array(array(
            $sql,
            'bind_param'
        ), $param_value_reference);
    }
    
    function insert($query, $param_type, $param_value_array) {
        $sql = $this->conn->prepare($query);
        $this->bindQueryParams($sql, $param_type, $param_value_array);
        $sql->execute();
    }
    
    function update($query, $param_type, $param_value_array) {
        $sql = $this->conn->prepare($query);
        $this->bindQueryParams($sql, $param_type, $param_value_array);
        $sql->execute();
    }
}
// Host Name
$dbhost = 'localhost';

// Database Name
$dbname = 'workflow_test';
//$dbname = 'trans_workflow_portal';

// Database Username
$dbuser = 'root';

// Database Password
$dbpass = 'root@123';
$db=mysqli_connect("localhost","$dbuser","$dbpass","$dbname");
$email_setting=mysqli_fetch_array(mysqli_query($db,"select * from email_settings where id=3"));

?>