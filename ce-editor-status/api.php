<?php
include('db.php');

$workflowid=$_REQUEST['id'];

//$workflowid=32230;

$sql="SELECT 
    dept.td_dep_name,
    dept.td_dep_id,
    dept.td_dep_color,
    sdept.td_sdep_name,
    sdept.td_sdep_code,
    sdept.td_sdep_bgcolor,
    fs.td_fs_remark,
    fs.td_fs_start_time,
    fs.td_fs_status,
    td_fs_id,
    CASE 
    WHEN fm.td_fm_dep_active = fs.td_fs_dep_id 
         AND fm.td_fm_subdpt_active = fs.td_fs_sdep_id 
    THEN 'Running' 
    ELSE TIMEDIFF(fs.td_fs_end_time, fs.td_fs_start_time)
END AS time
FROM 
    tbl_td_department AS dept
inner JOIN 
    tbl_td_sdepartment AS sdept 
    ON sdept.td_dep_id = dept.td_dep_id
INNER JOIN 
    tbl_td_file_status AS fs 
    ON fs.td_fs_sdep_id = sdept.td_sdep_code 
    AND fs.td_fs_dep_id = dept.td_dep_code
INNER JOIN 
    tbl_td_file_movement AS fm 
    ON fm.td_fm_id = fs.td_fs_fm_id
WHERE 
    fm.workflow_file_id = ".$workflowid."

UNION

SELECT 
    dept.td_dep_name,
    dept.td_dep_id,
    dept.td_dep_color,
    sdept.td_sdep_name,
    sdept.td_sdep_code,
    sdept.td_sdep_bgcolor,
    NULL as td_fs_remark,
    NULL as td_fs_start_time,
    NULL as td_fs_id,
    NULL as td_fs_status,
    'Running' AS time
FROM 
    tbl_td_department AS dept
inner JOIN 
    tbl_td_sdepartment AS sdept 
    ON sdept.td_dep_id = dept.td_dep_id
INNER JOIN 
    tbl_td_file_movement AS fm 
    ON fm.td_fm_dep_active = dept.td_dep_code 
    AND fm.td_fm_subdpt_active = sdept.td_sdep_code
WHERE 
    fm.workflow_file_id = ".$workflowid." and (fm.td_fm_dep_active!='03' and fm.td_fm_subdpt_active!='01')
    
/*
UNION

SELECT 
    dept.td_dep_name,
    dept.td_dep_id,
    dept.td_dep_color,
    NULL AS td_sdep_name,
    NULL AS td_sdep_code,
    NULL AS td_sdep_bgcolor,
    NULL AS time 
FROM 
    tbl_td_department AS dept
where 
dept.td_dep_status=0 */
ORDER BY 
    td_dep_id,
    td_sdep_code";
$result = $conn->query($sql);
$rows = array();
$outdata = array();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        array_push($rows, $row);
        array_push($outdata, array(
            'dep_name' => $row['td_dep_name'],
            'id' => $row['td_dep_id'],
            'dep_color' => $row['td_dep_color'],
            'child' => array()
        ));
    }
} else {
    echo "0 results";
}

$outdata = array_values(array_map("unserialize", array_unique(array_map("serialize", $outdata))));
foreach ($outdata as $index => $row) {
    $code = $row['id'];
    $sdept_data = array();
    foreach ($rows as $sdept) {
        if ($sdept['td_dep_id'] == $code && $sdept['td_sdep_name']!=NULL) {
            if($sdept['td_fs_status']!=3){
            
            array_push($sdept_data, array(
                's_name' => $sdept['td_sdep_name'],
                'code' => $sdept['td_sdep_code'],
                'S_color' => $sdept['td_sdep_bgcolor'],
                'time'=>$sdept['time'],
                'td_fs_status'=>$sdept['td_fs_status']
            ));
        }else{
            array_push($sdept_data, array(
                's_name' => $sdept['td_sdep_name']."<br>ST=".date('H:i:s', strtotime($sdept['td_fs_start_time']))."<br>Error:".$sdept['td_fs_remark']."<br><a onclick='ajax_reload(".$sdept['td_fs_id'].")'>Reload</a>" ,
                'td_fs_status'=>$sdept['td_fs_status'],
                'S_color' =>'red',
            ));
        }
        }
    }

    $outdata[$index]['child'] = $sdept_data;
}

$outdata = array_values($outdata);

echo json_encode($outdata, JSON_PRETTY_PRINT);


?>