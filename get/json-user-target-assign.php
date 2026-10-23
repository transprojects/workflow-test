<?php
include "../includes/Auth.php";
include "../includes/function.php";

$auth = new Auth();
$db_handle = new DBController();

// DataTables params
$start    = $_REQUEST['iDisplayStart'];
$length   = $_REQUEST['iDisplayLength'];
$sSearch  = $_REQUEST['sSearch'];
$col      = $_REQUEST['iSortCol_0'];
$sortType = $_REQUEST['sSortDir_0'];

// Filters
$dept_id = isset($_REQUEST['dep_id']) && $_REQUEST['dep_id'] != '' ? (int)$_REQUEST['dep_id'] : 0;
$user_id = isset($_REQUEST['user_id']) && $_REQUEST['user_id'] != '' ? (int)$_REQUEST['user_id'] : 0;

$arr = array(0 => 'uta_id', 1 => 'full_name', 2 => 'dept_name');
$sort_by = $arr[$col];

$gSearch = "";

// Apply filters
if ($dept_id > 0) {
    $gSearch .= " AND uta.dept_id = $dept_id";
}
if ($user_id > 0 && $user_id != 'All') {
    $gSearch .= " AND uta.user_id = $user_id";
}

// Call model functions
$res_prof = $auth->SelectEmployeesTarget($sSearch, $sort_by, $sortType, $start, $length, $gSearch);

$rec = array(
    'iTotalRecords' => 0,
    'iTotalDisplayRecords' => 0,
    'aaData' => array()
);

if (!empty($res_prof)) {
    $data = is_array($res_prof) ? $res_prof : array();

    // Get total records
    $qry = $auth->CountEmployeesTarget($sSearch, $gSearch);
    $iTotal = isset($qry[0]['count']) ? $qry[0]['count'] : 0;

    $rec = array(
        'iTotalRecords' => $iTotal,
        'iTotalDisplayRecords' => $iTotal,
        'aaData' => array()
    );

    $k = 0;
    foreach ($data as $employees) {
        $rec['aaData'][$k] = array(
            0 => $employees['uta_id'],
            1 => $employees['Employee Name'],
            2 => $employees['Department'],
            3 => $employees['Preferred Collaboration Avg'] ?? '0.00',
            4 => $employees['Other Collaboration Avg'] ?? '0.00',
            5 => '<div class="hidden-sm hidden-xs action-buttons">
                    <a title="Edit Target" class="btn btn-xs btn-warning" href="' . $_COOKIE['set_url'] . '/edit-user-target-assign/' . $employees['uta_id'] . '">
                        <i class="ace-icon fa fa-pencil bigger-120"></i>
                    </a>
                    <button data-delete="' . $employees['uta_id'] . '" class="btn btn-danger demo4 delete-article" type="button">
                        <i class="ace-icon fa fa-trash-o bigger-120"></i>
                    </button>
                </div>'
        );
        $k++;
    }
}

echo json_encode($rec);
?>
