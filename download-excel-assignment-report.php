<?php
include("includes/conn.php");

// ------------------------------------
// Force Excel Download
// ------------------------------------
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Article_Assignment_Report.xls");
header("Pragma: no-cache");
header("Expires: 0");

// ------------------------------------
// Department Mapping
// ------------------------------------
$deptMap = [
    10 => 'PAG',
    14 => 'PRQC'
];

// ------------------------------------
// BASE FILTER (STRICT – SAME AS SCREEN)
// ------------------------------------
$where_base = "
WHERE t.current_status = 'Assign'
  AND t.dept IN (10,14)
  AND t.assign_department IN (10,14)
";

// ------------------------------------
// OPTIONAL FILTERS
// ------------------------------------
$where_extra = "";

// Stage
if (!empty($_REQUEST['stage']) && $_REQUEST['stage'] !== 'all') {
    $stage = $db->real_escape_string($_REQUEST['stage']);
    $where_extra .= " AND t.current_stage LIKE '%$stage%' ";
}

// Customer
if (!empty($_REQUEST['cust_id']) && $_REQUEST['cust_id'] !== 'all') {
    $where_extra .= " AND d.cust_id = '".intval($_REQUEST['cust_id'])."' ";
}

// Journal
if (!empty($_REQUEST['j_id']) && $_REQUEST['j_id'] !== 'all') {
    $where_extra .= " AND d.j_id = '".intval($_REQUEST['j_id'])."' ";
}

// Platform
if (!empty($_REQUEST['dp_platform']) && $_REQUEST['dp_platform'] !== 'all') {
    $where_extra .= " AND j.j_platform = '".$db->real_escape_string($_REQUEST['dp_platform'])."' ";
}

// ------------------------------------
// DATE FILTER (DAY / MONTH – FIXED)
// ------------------------------------
$where_date = "";

if ($_REQUEST['date_wise'] === 'd' && !empty($_REQUEST['from_dt'])) {
    $from = date('Y-m-d', strtotime($_REQUEST['from_dt']));
    $to   = date('Y-m-d', strtotime($_REQUEST['to_dt']));
    $where_date .= " AND DATE(t.start_time) BETWEEN '$from' AND '$to' ";
}
elseif ($_REQUEST['date_wise'] === 'm' && !empty($_REQUEST['mfrom_dt'])) {

    // Expected format: mm-YYYY (example: 01-2026)
    list($month, $year) = explode('-', $_REQUEST['mfrom_dt']);

    $month = str_pad((int)$month, 2, '0', STR_PAD_LEFT);
    $year  = (int)$year;

    $where_date .= "
        AND MONTH(t.start_time) = '$month'
        AND YEAR(t.start_time)  = '$year'
    ";
}

// ------------------------------------
// ASSIGNMENT QUERY (NO LIMIT – GROUPED)
// ------------------------------------
$sql = "
SELECT
    x.project_id AS article_id,
    x.current_stage,
    x.dept AS from_dept,
    x.assign_department AS to_dept,
    x.pub_id,
    u.full_name AS process_user_name,
    SUM(CASE WHEN x.dept = 10 THEN 1 ELSE 0 END) AS pag_count,
    SUM(CASE WHEN x.dept = 14 THEN 1 ELSE 0 END) AS prqc_count,
    x.source
FROM (
    SELECT
        t.project_id,
        t.current_stage,
        t.assign_department,
        t.dept,
        t.process_user,
        d.pub_id,
        'Live' AS source
    FROM workflow.inw_transactions t
    JOIN workflow.inw_inward_dtl d ON d.id = t.project_id
    JOIN workflow.adm_journals j ON j.j_id = d.j_id
    $where_base
    $where_extra
    $where_date

    UNION ALL

    SELECT
        t.project_id,
        t.current_stage,
        t.assign_department,
        t.dept,
        t.process_user,
        d.pub_id,
        'Archive' AS source
    FROM workflow_archive.inw_transactions t
    JOIN workflow_archive.inw_inward_dtl d ON d.id = t.project_id
    JOIN workflow_archive.adm_journals j ON j.j_id = d.j_id
    $where_base
    $where_extra
    $where_date
) x
LEFT JOIN users u ON u.id = x.process_user
GROUP BY
    x.project_id,
    x.current_stage,
    x.assign_department,
    x.dept,
    u.full_name,
    x.source
ORDER BY
    x.project_id,
    x.current_stage
";

$result = $db->query($sql) or die($db->error);
?>

<table border="1">
<thead>
<tr style="background:#d9d9d9;font-weight:bold;">
    <th>S.No</th>
    <th>Article ID</th>
    <th>Stage</th>
    <th>Assigned From</th>
    <th>Assigned To</th>
    <th>PAG Assign Count</th>
    <th>PRQC Assign Count</th>
    <th>Assigned By</th>
    <th>Source</th>
</tr>
</thead>
<tbody>

<?php
$sno = 1;
while ($row = $result->fetch_assoc()) {
?>
<tr>
    <td><?= $sno++ ?></td>
    <td><?= $row['pub_id'] ?></td>
    <td><?= $row['current_stage'] ?></td>
    <td><?= $deptMap[$row['from_dept']] ?? 'Unknown' ?></td>
    <td><?= $deptMap[$row['to_dept']] ?? 'Unknown' ?></td>
    <td><?= $row['pag_count'] ?></td>
    <td><?= $row['prqc_count'] ?></td>
    <td><?= $row['process_user_name'] ?></td>
    <td><?= $row['source'] ?></td>
</tr>
<?php } ?>

</tbody>
</table>
