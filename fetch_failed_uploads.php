<?php include "includes/includes.php";?>
<?php
$request = $_POST;

$where = " WHERE (f.mail_send IN ('FAILED','PENDING')) ";

// Filters
if (!empty($request['article_id'])) {
    $where .= " AND f.article_id LIKE '%".$request['article_id']."%' ";
}

if (!empty($request['stage'])) {
    $stage = implode("','", $request['stage']);
    $where .= " AND f.stage IN ('$stage')";
}

if (!empty($request['cust_id'])) {
    $cust = implode(",", $request['cust_id']);
    $where .= " AND f.cust_id IN ($cust)";
}

if (!empty($request['j_id'])) {
    $jid = implode(",", $request['j_id']);
    $where .= " AND f.jour_id IN ($jid)";
}

if (!empty($request['from_dt']) && !empty($request['to_dt'])) {
    $where .= " AND DATE(i.created_at) BETWEEN '".$request['from_dt']."' AND '".$request['to_dt']."'";
}

// COUNT
$sql = "
SELECT COUNT(*) as total
FROM xperttrack_ftp_upload_history f

LEFT JOIN inw_inward_dtl i 
    ON i.pub_id = f.article_id 
    AND i.status = 'Client Review'
    AND i.stage = f.stage

$where
AND i.pub_id IS NOT NULL
";

$res = mysqli_fetch_assoc(mysqli_query($db, $sql));
$totalData = $res['total'];

// MAIN QUERY
$sql = "
SELECT 
    f.id,
    f.article_id,
    f.stage,
    f.status,
    f.mail_send,

    c.cust_name,
    j.j_name

FROM  xperttrack_ftp_upload_history f

LEFT JOIN adm_customer_master c 
    ON c.id = f.cust_id

LEFT JOIN adm_journals j 
    ON j.j_id = f.jour_id

LEFT JOIN inw_inward_dtl i 
    ON i.pub_id = f.article_id 
    AND i.status = 'Client Review'
    AND i.stage = f.stage

$where
AND i.job_id IS NOT NULL

ORDER BY f.id DESC
LIMIT ".$request['start']." , ".$request['length'];

$query = mysqli_query($db, $sql);

$data = [];

while ($row = mysqli_fetch_assoc($query)) {

    // Status logic
if ($row['status'] == 'FAILED' || $row['mail_send'] == 'FAILED') {
    $status = '<span class="badge bg-danger">FAILED (PRESS RETRY)</span>';
    $buttonLabel = 'Retry Upload';
    $buttonClass = 'btn-danger';
    $disabled = '';
} elseif ($row['status'] == 'YET_TO_UPLOAD') {
    $status = '<span class="badge bg-warning">AUTO UPLOAD IN PROGRESS</span>';
    $buttonLabel = 'Auto Uploading...';
    $buttonClass = 'btn-warning';
    $disabled = 'disabled';
} else {
    $status = '<span class="badge bg-success">SUCCESS</span>';
    $buttonLabel = 'Uploaded';
    $buttonClass = 'btn-success';
    $disabled = 'disabled';
}

    $nestedData = [];

   $nestedData['action'] = '
    <button class="btn btn-sm '.$buttonClass.' retry-upload" data-id="'.$row['id'].'" '.$disabled.'>
        '.$buttonLabel.'
    </button>
';
    /* the upload itself is fine / done but the client mail failed: offer to send just the mail again */
    if ($row['mail_send'] == 'FAILED' && $row['status'] != 'YET_TO_UPLOAD') {
        $nestedData['action'] .= ' <button class="btn btn-sm btn-warning send-mail" data-id="'.$row['id'].'" title="Send the client mail again"><i class="fa fa-envelope"></i> Send Mail</button>';
        if ($row['status'] != 'FAILED') { $status = '<span class="badge bg-danger">MAIL FAILED</span>'; $nestedData['action'] = str_replace('btn-success retry-upload', 'btn-secondary retry-upload', $nestedData['action']); }
    }

    $nestedData['customer'] = $row['cust_name'] ?? '-';
    $nestedData['journal'] = $row['j_name'] ?? '-';
    $nestedData['article_id'] = $row['article_id'];
    $nestedData['stage'] = $row['stage'];
    $nestedData['status_html'] = $status;

    $data[] = $nestedData;
}

$json_data = [
    "draw" => intval($request['draw']),
    "recordsTotal" => intval($totalData),
    "recordsFiltered" => intval($totalData),
    "data" => $data
];

echo json_encode($json_data);
?>