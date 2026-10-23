<?php
include('../includes/conn.php');
date_default_timezone_set('Asia/Kolkata');

// 🔹 DataTables input parameters
$draw        = intval($_POST['draw'] ?? 1);
$start       = intval($_POST['start'] ?? 0);
$length      = intval($_POST['length'] ?? 10);
$searchValue = trim($_POST['search']['value'] ?? '');
$orderColumnIndex = $_POST['order'][0]['column'] ?? 0;
$orderDir          = $_POST['order'][0]['dir'] ?? 'asc';
$today = $_POST['today'] ?? date('Y-m-d');

// ✅ Define column names exactly as used in DataTables
$columns = [
    'h.cust_name',
    'h.j_code',
    'h.pub_id',
    'h.stage',
    'h.status',
    'h.is_scheduled',
    'h.created_dt'
];

// Sanitize order column
$orderColumn = $columns[$orderColumnIndex] ?? 'h.created_dt';
$orderDir = ($orderDir === 'desc') ? 'DESC' : 'ASC';

// ✅ Base query
$baseQuery = "
    FROM plan_article_to_dispatch_mail_history h
    JOIN plan_article_to_dispatch_mail_daily d ON h.schedule_id = d.id
    WHERE d.schedule_date = ? AND d.status = 'Pending'
";

// ✅ Global search
$params = [$today];
$types  = 's';

if ($searchValue !== '') {
    $baseQuery .= " AND (
        h.cust_name LIKE ? OR 
        h.j_code LIKE ? OR 
        h.pub_id LIKE ? OR 
        h.stage LIKE ? OR
        h.status LIKE ?
    )";
    $like = "%{$searchValue}%";
    $params = array_merge([$today, $like, $like, $like, $like, $like]);
    $types  = 'ssssss';
}

// ✅ Total filtered
$countSql = "SELECT COUNT(*) AS total " . $baseQuery;
$countStmt = $db->prepare($countSql);
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalFiltered = $countStmt->get_result()->fetch_assoc()['total'] ?? 0;

// ✅ Total (without search)
$totalSql = "SELECT COUNT(*) AS total 
             FROM plan_article_to_dispatch_mail_history h
             JOIN plan_article_to_dispatch_mail_daily d ON h.schedule_id = d.id
             WHERE d.schedule_date = ? AND d.status='Pending'";
$totalStmt = $db->prepare($totalSql);
$totalStmt->bind_param('s', $today);
$totalStmt->execute();
$totalRecords = $totalStmt->get_result()->fetch_assoc()['total'] ?? 0;

// ✅ Fetch paginated data
$dataSql = "
    SELECT 
        h.history_id,
        h.cust_name,
        h.j_code,
        h.cust_id,
        h.j_id,
        h.pub_id,
        h.stage,
        h.status,
        h.article_id,
        h.received_date,
        h.due_date,
        h.page_count,
        h.is_scheduled,
        DATE_FORMAT(h.created_dt, '%d-%m-%Y') AS created_dt
    $baseQuery
    ORDER BY $orderColumn $orderDir
    LIMIT ?, ?
";

$stmt = $db->prepare($dataSql);
$params[] = $start;
$params[] = $length;
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// ✅ Format data
$data = [];
while ($row = $result->fetch_assoc()) {
    $btnLabel = ($row['is_scheduled'] == 1) ? "Unschedule Today" : "Schedule Today";
    $btnClass = ($row['is_scheduled'] == 1) ? "btn-warning" : "btn-success";

    $row['action_btn'] = '
      <button 
        class="btn '.$btnClass.' schedule-btn"
        data-article-id="'.htmlspecialchars($row['article_id']).'"
        data-pub-id="'.htmlspecialchars($row['pub_id']).'"
        data-custid="'.htmlspecialchars($row['cust_id']).'"
        data-jid="'.htmlspecialchars($row['j_id']).'"
        data-cust="'.htmlspecialchars($row['cust_name']).'"
        data-jcode="'.htmlspecialchars($row['j_code']).'"
        data-stage="'.htmlspecialchars($row['stage']).'"
        data-status="'.htmlspecialchars($row['status']).'"
        data-received-date="'.htmlspecialchars($row['received_date'] ?? '').'"
        data-due-date="'.htmlspecialchars($row['due_date'] ?? '').'"
        data-page-count="'.(int)($row['page_count'] ?? 0).'">
        '.$btnLabel.'
      </button>';
    $data[] = $row;
}

// ✅ Output in DataTables JSON format
echo json_encode([
    "draw" => $draw,
    "recordsTotal" => intval($totalRecords),
    "recordsFiltered" => intval($totalFiltered),
    "data" => $data
]);
?>
