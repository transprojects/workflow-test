<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
ini_set("display_errors", "0");
include "../includes/Auth.php";
include "../includes/function.php";
include "../includes/conn.php";

$auth = new Auth();
$db_handle = new DBController();

$start  = $_REQUEST['iDisplayStart'];
$length = $_REQUEST['iDisplayLength'];
$sSearch = $_REQUEST['sSearch'];
$col    = $_REQUEST['iSortCol_0'];
$sort_type = $_REQUEST['sSortDir_0'];

$arr = array(
    0 => 'j_code',
    1 => 'full_name',
    2 => 'stage',
    3 => 'article',
    4 => 'recv_dt',
    5 => 'dm.created_on',
    6 => 'f_id'
);

$sort_by = $arr[$col] ?? 'recv_dt';

/**
 * Build WHERE filters for Internal Feedback
 */
function buildInternalFeedbackFilter($db) {
    $gSearch = ''; // default always valid

    // Customer filter
    if (!empty($_REQUEST['cust_id'])) {
        $cust_id = mysqli_real_escape_string($db, $_REQUEST['cust_id']);
        $gSearch .= " AND FIND_IN_SET(dm.cust_id, '$cust_id')";
    }

    // Journal filter
    if (!empty($_REQUEST['j_id'])) {
        $j_id = mysqli_real_escape_string($db, $_REQUEST['j_id']);
        $gSearch .= " AND FIND_IN_SET(dm.j_id, '$j_id')";
    }

    // Stage filter (multiple allowed)
    if (!empty($_REQUEST['stage'])) {
        $ex_stage = explode(",", $_REQUEST['stage']);
        $stageConditions = [];
        foreach ($ex_stage as $st) {
            $stageConditions[] = "stage LIKE '%" . mysqli_real_escape_string($db, $st) . "%'";
        }
        $gSearch .= " AND (" . implode(" OR ", $stageConditions) . ")";
    }

    // Date range filter
    if (!empty($_REQUEST['from_dt']) && !empty($_REQUEST['to_dt'])) {
        $from = date('Y-m-d', strtotime($_REQUEST['from_dt']));
        $to   = date('Y-m-d', strtotime($_REQUEST['to_dt']));
        $gSearch .= " AND DATE(recv_dt) BETWEEN '$from' AND '$to'";
    }

    // Article filter
    if (!empty($_REQUEST['article_id'])) {
        $article = mysqli_real_escape_string($db, $_REQUEST['article_id']);
        $gSearch .= " AND article LIKE '%$article%'";
    }

    // Role-based restriction (only when no filters applied)
    if (
        empty($_REQUEST['cust_id']) &&
        empty($_REQUEST['j_id']) &&
        empty($_REQUEST['stage']) &&
        empty($_REQUEST['article_id']) &&
        empty($_REQUEST['from_dt']) &&
        empty($_REQUEST['to_dt'])
    ) {
        if (
            $_COOKIE['user_level'] != 1 &&
            $_COOKIE['user_level'] != 14 &&
            $_COOKIE['user_level'] != 5 &&
            $_COOKIE['user_level'] != 7
        ) {
            $uid = intval($_COOKIE['user_id']); // enforce integer
            $gSearch .= " AND u_id = $uid";
        }
    }
   
   
    return $gSearch;
}

$gSearch = buildInternalFeedbackFilter($db);

// Fetch data
$res_prof = $auth->SelectInternalFeedback($sSearch, $sort_by, $sort_type, $start, $length, $gSearch);

// Default response
$rec = array(
    'iTotalRecords' => 0,
    'iTotalDisplayRecords' => 0,
    'aaData' => array()
);

if (!empty($res_prof)) {
    $data = $res_prof;

    // Count query
    $qry = $auth->CountInternalFeedback($sSearch, $gSearch);
    $iTotal = 0;
    foreach ($qry as $row) {
        $iTotal = $row['count'];
    }

    $rec = array(
        'iTotalRecords' => $iTotal,
        'iTotalDisplayRecords' => $iTotal,
        'aaData' => array()
    );

    $k = 0;
    foreach ($data as $clientfeedback) {
		
		 $messageContent = '
                <div class="message-content">
                    <strong>RCA:</strong> ' . nl2br($clientfeedback['rca']) . '<br><br>
                </div>';
				
				
        if (
            $_COOKIE['user_level'] == 1 ||
            $_COOKIE['user_level'] == 14 ||
            $_COOKIE['user_level'] == 5 ||
            $_COOKIE['user_level'] == 7
        ) {
           $action = '
				 <div class="hidden-sm hidden-xs action-buttons">

					<a title="Action Plan" 
					   class="btn btn-xs btn-warning" 
					   href="' . $_COOKIE['set_url'] . '/edit-internal-feedback/' . $clientfeedback['f_id'] . '"><i class="fa fa-pencil-square-o"></i></a>

					<a title="View" 
					   class="btn btn-xs btn-info" 
					   href="' . $_COOKIE['set_url'] . '/internal-feedback-detail/' . $clientfeedback['f_id'] . '"><i class="fa fa-eye"></i></a>

					<button data-delete="' . $clientfeedback['f_id'] . '" 
							class="btn btn-xs btn-danger demo4 delete-article" 
							type="button">
						<i class="ace-icon fa fa-trash-o bigger-120"></i>
					</button>

				</div>';

        } else {
            $action = '<div class="hidden-sm hidden-xs action-buttons">
              <a title="Action Plan" class="btn btn-xs btn-warning" href="' . $_COOKIE['set_url'] . '/view-internal-feedback/' . $clientfeedback['f_id'] . '"><i class="fa fa-pencil-square-o"></i></a>
            </div>';
        }

        $rec['aaData'][$k] = array(
            0 => $clientfeedback['j_code'],
            1 => $clientfeedback['stage'],
            2 => $clientfeedback['article'],
            3 => '<div class="message-content">' . $clientfeedback['message'] . '</div>',
            4 => $messageContent,
            5 => date('d-m-Y', strtotime($clientfeedback['recv_dt'])),
            6 => $clientfeedback['full_name'],
            7 => $action
        );
        $k++;
    }
}

echo json_encode($rec);
