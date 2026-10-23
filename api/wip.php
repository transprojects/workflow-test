<?php
/* ============================================================================
 * WIP report API                                          /workflow-new/api/wip.php
 *
 * Same counting rules as the existing WIP report (wipreport.php), fetched in one query instead of
 * one query per day and stage, with the click-through list served as paged JSON.
 *
 *   ?action=meta                                     signed-in user, customers and departments they can filter by
 *   ?action=matrix&cust_id=42&dept=all&from=2026-06-01&to=2026-06-30       (or &month=2026-06)
 *   ?action=detail&cust_id=42&dept=all&stage=FP|REV|FIN|JSTAGE|ISS|ALL&date=2026-10-08        one day
 *                                                       or &from=..&to=..             a range / the overdue bucket
 *                  [&page=1&per_page=50&sort=due&dir=asc&q=text]
 *   ?action=export  (same filters as detail)         CSV download
 *
 * What is counted - open work only, live tables, by DUE date (same as the existing page):
 *   FP   articles with stage FP, status not "Client Review"                    due = article due date
 *   REV  articles whose stage is REV*, status not Client Review / Completed     due = due date of the LATEST revision
 *   FIN  articles whose stage is FIN*, same rules as REV
 *   PMC  articles whose stage is PMC*, same rules (due = due date of the latest revision, i.e. the PMC round)
 *   JSTAGE  articles whose stage is JSTAGE*, same rules (due = due date of the latest revision, i.e. the JSTAGE round)
 *   ISS  volume issues not Delivery / Client Review / Completed                 due = issue due date   (department filter does not apply, as before)
 * Columns: "overdue" = due from the range start up to yesterday;  one column per day from today to the end of the range;
 *          "total" = due anywhere in the range.
 * ========================================================================== */

require __DIR__ . '/../includes/report_api.php';
include_once __DIR__ . '/../includes/report_stages.php';          // $report_stage_types: which stages the reports show

$action = isset($_REQUEST['action']) ? (string)$_REQUEST['action'] : 'matrix';

function resolve_dept()
{
    $d = isset($_REQUEST['dept']) ? $_REQUEST['dept'] : 'all';
    if (is_array($d)) { api_fail(400, 'Invalid department.'); }
    $d = trim((string)$d);
    if ($d === '' || $d === 'all') { return null; }
    if (!ctype_digit($d)) { api_fail(400, 'Invalid department.'); }
    return (int)$d;
}
function dept_sql($dept) { return $dept === null ? '' : ' AND wd.department_id = ' . (int)$dept; }

/* =============================================================== MATRIX */
function wip_matrix($custs, $dept, $from, $to)
{
    $today = date('Y-m-d');
    $yest = date('Y-m-d', strtotime('-1 day'));
    $qEnd = $to > $yest ? $to : $yest;                       // "overdue" can reach past the end of the range
    $cA = cust_sql_str('wd', $custs);
    $cI = cust_sql_int('vi', $custs);
    $dA = dept_sql($dept);

    $parts = array();
    if (rs_row_enabled('FP')) $parts[] = "SELECT 'FP' AS stg, wd.due_dt AS d, COUNT(*) AS c
                FROM inw_inward_dtl wd
                WHERE wd.due_dt BETWEEN '$from' AND '$qEnd' AND wd.stage = 'FP' AND wd.status <> 'Client Review' $cA $dA
                GROUP BY wd.due_dt";
    $parts[] = "SELECT IF(LEFT(wd.stage,3) = 'REV', 'REV', 'FIN') AS stg, r.due_date AS d, COUNT(DISTINCT wd.id) AS c
                FROM inw_inward_dtl wd
                JOIN adm_journals j ON j.j_id = wd.j_id
                JOIN inw_revisions_dtl r ON r.inw_id = wd.id
                WHERE " . rs_rev_fin_cond('ALL') . "
                  AND wd.status <> 'Client Review' AND wd.status NOT IN ('Client Review','Completed')
                  AND r.created_on > '2023-01-01' AND r.due_date BETWEEN '$from' AND '$qEnd'
                  AND NOT EXISTS (SELECT 1 FROM inw_revisions_dtl x WHERE x.inw_id = r.inw_id AND x.r_id > r.r_id) $cA $dA
                GROUP BY stg, r.due_date";
    if (rs_row_enabled('PMC')) $parts[] = "SELECT 'PMC' AS stg, r.due_date AS d, COUNT(DISTINCT wd.id) AS c
                FROM inw_inward_dtl wd
                JOIN adm_journals j ON j.j_id = wd.j_id
                JOIN inw_revisions_dtl r ON r.inw_id = wd.id
                WHERE wd.stage LIKE 'PMC%'
                  AND wd.status <> 'Client Review' AND wd.status NOT IN ('Client Review','Completed')
                  AND r.created_on > '2023-01-01' AND r.due_date BETWEEN '$from' AND '$qEnd'
                  AND NOT EXISTS (SELECT 1 FROM inw_revisions_dtl x WHERE x.inw_id = r.inw_id AND x.r_id > r.r_id) $cA $dA
                GROUP BY r.due_date";
    if (rs_row_enabled('JSTAGE')) $parts[] = "SELECT 'JSTAGE' AS stg, r.due_date AS d, COUNT(DISTINCT wd.id) AS c
                FROM inw_inward_dtl wd
                JOIN adm_journals j ON j.j_id = wd.j_id
                JOIN inw_revisions_dtl r ON r.inw_id = wd.id
                WHERE wd.stage LIKE 'JSTAGE%'
                  AND wd.status <> 'Client Review' AND wd.status NOT IN ('Client Review','Completed')
                  AND r.created_on > '2023-01-01' AND r.due_date BETWEEN '$from' AND '$qEnd'
                  AND NOT EXISTS (SELECT 1 FROM inw_revisions_dtl x WHERE x.inw_id = r.inw_id AND x.r_id > r.r_id) $cA $dA
                GROUP BY r.due_date";
    if (rs_row_enabled('ISS')) $parts[] = "SELECT 'ISS' AS stg, vi.due_date AS d, COUNT(DISTINCT vi.vi_id) AS c
                FROM inw_volume_issues vi
                JOIN adm_journals j ON j.j_cust_id = vi.cust_id AND j.j_id = vi.j_id
                WHERE vi.status NOT IN ('Delivery','Client Review','Completed') AND vi.due_date BETWEEN '$from' AND '$qEnd' $cI
                GROUP BY vi.due_date";
    if (!$parts) { $parts[] = "SELECT 'FP' AS stg, NULL AS d, 0 AS c FROM dual WHERE 1=0"; }
    $res = q(implode(' UNION ALL ', $parts));

    $by = array('FP' => array(), 'REV' => array(), 'FIN' => array(), 'PMC' => array(), 'JSTAGE' => array(), 'ISS' => array());
    while ($r = $res->fetch_assoc()) { $by[$r['stg']][$r['d']] = (int)$r['c']; }

    $days = array();
    $d0 = $today > $from ? $today : $from;                   // one column per day from today (or the range start, if later) to the end
    if ($to >= $d0) { for ($c = strtotime($d0); $c <= strtotime($to); $c = strtotime('+1 day', $c)) { $days[] = date('Y-m-d', $c); } }

    $rows = array();
    $tot_prev = 0; $tot_range = 0; $per_day = array_fill(0, count($days), 0);
    foreach ($by as $s => $m) {
        $prev = 0; $range = 0; $counts = array_fill(0, count($days), 0);
        foreach ($m as $d => $c) {
            if ($d >= $from && $d <= $yest) { $prev += $c; }
            if ($d >= $from && $d <= $to) { $range += $c; }
        }
        foreach ($days as $i => $d) { if (isset($m[$d])) { $counts[$i] = $m[$d]; $per_day[$i] += $m[$d]; } }
        $rows[$s] = array('prev' => $prev, 'counts' => $counts, 'total' => $range);
        $tot_prev += $prev; $tot_range += $range;
    }
    return array('today' => $today, 'overdue_range' => array('from' => $from, 'to' => ($from <= $yest ? $yest : null)),
                 'days' => $days, 'rows' => $rows, 'totals' => array('prev' => $tot_prev, 'per_day' => $per_day, 'grand' => $tot_range));
}

/* =============================================================== DETAIL / EXPORT */
function detail_union($custs, $dept, $from, $to, $stage)
{
    $cA = cust_sql_str('wd', $custs);
    $cI = cust_sql_int('vi', $custs);
    $dA = dept_sql($dept);
    $want = function ($s) use ($stage) { return ($stage === 'ALL' || $stage === $s) && rs_row_enabled($s); };   // only the stages in $report_stage_types
    $parts = array();
    $common = "wd.id AS id, 'A' AS kind, wd.pub_id AS ref, c.cust_name AS customer, j.j_code AS j_code, j.j_name AS j_name, wd.volume_folder AS folder,
               wd.status AS status, wd.user_working_status AS working, wd.fig_complete_status AS graphics_done, wd.fig AS fig, wd.highpriority AS priority,
               wd.aq_status AS aq_status, wd.target_due_dt AS target_due, dm.dept_name AS department, NULL AS start_page";
    if ($want('FP')) {
        $parts[] = "SELECT 'FP' AS stg, wd.due_dt AS due, wd.recv_dt AS received, wd.created_dt AS received_at, 'FP' AS stage_label, CAST(wd.manuscript_count AS UNSIGNED) AS pages, $common
                    FROM inw_inward_dtl wd
                    LEFT JOIN adm_customer_master c ON c.id = wd.cust_id
                    LEFT JOIN adm_journals j ON j.j_id = wd.j_id
                    LEFT JOIN adm_dept_master dm ON dm.dept_code = wd.department_id
                    WHERE wd.due_dt BETWEEN '$from' AND '$to' AND wd.stage = 'FP' AND wd.status <> 'Client Review' $cA $dA";
    }
    if ($want('REV') || $want('FIN')) {
        $stageCond = rs_rev_fin_cond($stage);
        $parts[] = "SELECT IF(LEFT(wd.stage,3) = 'REV', 'REV', 'FIN') AS stg, r.due_date AS due, r.received_date AS received, r.created_on AS received_at,
                           CONCAT(IF(LEFT(wd.stage,3) = 'REV', 'REV', 'FIN'), r.revision_count) AS stage_label, r.correction_pages AS pages, $common
                    FROM inw_inward_dtl wd
                    JOIN adm_journals j ON j.j_id = wd.j_id
                    JOIN inw_revisions_dtl r ON r.inw_id = wd.id
                    LEFT JOIN adm_customer_master c ON c.id = wd.cust_id
                    LEFT JOIN adm_dept_master dm ON dm.dept_code = wd.department_id
                    WHERE $stageCond AND wd.status <> 'Client Review' AND wd.status NOT IN ('Client Review','Completed')
                      AND r.created_on > '2023-01-01' AND r.due_date BETWEEN '$from' AND '$to'
                      AND NOT EXISTS (SELECT 1 FROM inw_revisions_dtl x WHERE x.inw_id = r.inw_id AND x.r_id > r.r_id) $cA $dA";
    }
    if ($want('PMC')) {
        $parts[] = "SELECT 'PMC' AS stg, r.due_date AS due, r.received_date AS received, r.created_on AS received_at,
                           CONCAT('PMC', r.revision_count) AS stage_label, r.correction_pages AS pages, $common
                    FROM inw_inward_dtl wd
                    JOIN adm_journals j ON j.j_id = wd.j_id
                    JOIN inw_revisions_dtl r ON r.inw_id = wd.id
                    LEFT JOIN adm_customer_master c ON c.id = wd.cust_id
                    LEFT JOIN adm_dept_master dm ON dm.dept_code = wd.department_id
                    WHERE wd.stage LIKE 'PMC%' AND wd.status <> 'Client Review' AND wd.status NOT IN ('Client Review','Completed')
                      AND r.created_on > '2023-01-01' AND r.due_date BETWEEN '$from' AND '$to'
                      AND NOT EXISTS (SELECT 1 FROM inw_revisions_dtl x WHERE x.inw_id = r.inw_id AND x.r_id > r.r_id) $cA $dA";
    }
    if ($want('JSTAGE')) {
        $parts[] = "SELECT 'JSTAGE' AS stg, r.due_date AS due, r.received_date AS received, r.created_on AS received_at,
                           CONCAT('JSTAGE', r.revision_count) AS stage_label, r.correction_pages AS pages, $common
                    FROM inw_inward_dtl wd
                    JOIN adm_journals j ON j.j_id = wd.j_id
                    JOIN inw_revisions_dtl r ON r.inw_id = wd.id
                    LEFT JOIN adm_customer_master c ON c.id = wd.cust_id
                    LEFT JOIN adm_dept_master dm ON dm.dept_code = wd.department_id
                    WHERE wd.stage LIKE 'JSTAGE%' AND wd.status <> 'Client Review' AND wd.status NOT IN ('Client Review','Completed')
                      AND r.created_on > '2023-01-01' AND r.due_date BETWEEN '$from' AND '$to'
                      AND NOT EXISTS (SELECT 1 FROM inw_revisions_dtl x WHERE x.inw_id = r.inw_id AND x.r_id > r.r_id) $cA $dA";
    }
    if ($want('ISS')) {
        $parts[] = "SELECT 'ISS' AS stg, vi.due_date AS due, vi.received_date AS received, vi.received_date AS received_at, vi.stage AS stage_label, vi.correction_pages AS pages,
                           vi.vi_id AS id, 'I' AS kind,
                           CONCAT_WS(' / ', NULLIF(CONCAT('Vol ', vi.volume_number), 'Vol '), NULLIF(CONCAT('Issue ', vi.issue_number), 'Issue ')) AS ref,
                           c.cust_name AS customer, j.j_code AS j_code, j.j_name AS j_name, '' AS folder, vi.status AS status, NULL AS working,
                           NULL AS graphics_done, NULL AS fig, 0 AS priority, NULL AS aq_status, NULL AS target_due, NULL AS department, vi.starting_page_number AS start_page
                    FROM inw_volume_issues vi
                    JOIN adm_journals j ON j.j_cust_id = vi.cust_id AND j.j_id = vi.j_id
                    LEFT JOIN adm_customer_master c ON c.id = vi.cust_id
                    WHERE vi.status NOT IN ('Delivery','Client Review','Completed') AND vi.due_date BETWEEN '$from' AND '$to' $cI";
    }
    return implode(' UNION ALL ', $parts);
}

function detail_rows($custs, $dept, $from, $to, $stage, $page, $per, $sort, $dir, $search, $no_limit = false)
{
    global $db;
    $union = detail_union($custs, $dept, $from, $to, $stage);
    $where = '';
    if ($search !== '') {
        $s = $db->real_escape_string(addcslashes($search, '%_\\'));
        $where = " WHERE (ref LIKE '%$s%' OR j_code LIKE '%$s%' OR j_name LIKE '%$s%' OR customer LIKE '%$s%' OR department LIKE '%$s%')";
    }
    $cols = array('due' => 'due', 'received' => 'received', 'ref' => 'ref', 'customer' => 'customer', 'journal' => 'j_code', 'stage' => 'stage_label',
                  'pages' => 'pages', 'status' => 'status', 'department' => 'department');
    $dirSql = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';
    $order = isset($cols[$sort]) ? $cols[$sort] . " $dirSql, id ASC" : 'priority DESC, due ASC, id ASC';
    $limit = $no_limit ? '' : ' LIMIT ' . (($page - 1) * $per) . ',' . $per;
    $res = q("SELECT SQL_CALC_FOUND_ROWS * FROM ($union) t $where ORDER BY $order $limit");
    $rows = array();
    while ($r = $res->fetch_assoc()) { $rows[] = $r; }
    $total = (int)q('SELECT FOUND_ROWS() AS n')->fetch_assoc()['n'];
    return array($rows, $total);
}

/* articles done / pending for the issues on this page: one query, not one per row (department filter applies, as before) */
function issue_progress(&$rows, $dept)
{
    $ids = array();
    foreach ($rows as $r) { if ($r['kind'] === 'I') { $ids[] = (int)$r['id']; } }
    if (!$ids) { return; }
    $res = q("SELECT wd.v_issue_id, SUM(wd.status = 'Delivery') AS done, SUM(wd.status <> 'Delivery') AS pending
              FROM inw_inward_dtl wd WHERE wd.v_issue_id IN (" . implode(',', $ids) . ")" . dept_sql($dept) . " GROUP BY wd.v_issue_id");
    $map = array();
    while ($x = $res->fetch_assoc()) { $map[$x['v_issue_id']] = $x; }
    foreach ($rows as &$r) {
        if ($r['kind'] === 'I') {
            $r['articles_done'] = isset($map[$r['id']]) ? (int)$map[$r['id']]['done'] : 0;
            $r['articles_pending'] = isset($map[$r['id']]) ? (int)$map[$r['id']]['pending'] : 0;
        }
    }
    unset($r);
}

/* time part (HH:MM) of a received timestamp; empty when only a date is known (00:00:00) */
function rcv_time($ts)
{
    if (!$ts || strlen($ts) < 16) { return ''; }
    $t = substr($ts, 11, 5);
    return $t === '00:00' ? '' : $t;
}

function shape_row($r)
{
    $today = date('Y-m-d');
    $due = ($r['due'] && $r['due'] !== '0000-00-00') ? $r['due'] : null;
    $late = ($due && $due < $today) ? (int)round((strtotime($today) - strtotime($due)) / 86400) : 0;
    $parts = working_parts($r['working']);
    $out = array(
        'id' => (int)$r['id'], 'kind' => $r['kind'] === 'I' ? 'issue' : 'article', 'stage_group' => $r['stg'],
        'ref' => tx($r['ref']), 'customer' => tx($r['customer']), 'journal_code' => tx($r['j_code']), 'journal_name' => tx($r['j_name']),
        'stage' => tx($r['stage_label']), 'folder' => tx($r['folder']), 'received' => $r['received'], 'received_time' => rcv_time(isset($r['received_at']) ? $r['received_at'] : null), 'due' => $due, 'target_due' => $r['target_due'],
        'department' => tx($r['department']), 'status' => tx($r['status']), 'working_parts' => $parts,
        'working_status' => implode(' | ', array_map(function ($p) { return $p['t']; }, $parts)),
        'pages' => $r['pages'] === null ? null : (int)$r['pages'], 'priority' => (int)$r['priority'] === 1,
        'overdue' => $late > 0, 'overdue_days' => $late,
    );
    if ($r['kind'] === 'A') {
        $out['graphics'] = (string)$r['graphics_done'] === '1' ? 'Yes' : 'No';
        $out['figures'] = $r['fig'] === null ? null : (int)$r['fig'];
        $out['aq_status'] = tx($r['aq_status']);
        $out['links'] = array('send_instructions' => 'send_instructions.php?id=' . (int)$r['id'], 'job_card' => 'jobcard.php?id=' . (int)$r['id']);
    } else {
        $out['start_page'] = $r['start_page'] === null ? null : (int)$r['start_page'];
        $out['articles_done'] = isset($r['articles_done']) ? $r['articles_done'] : 0;
        $out['articles_pending'] = isset($r['articles_pending']) ? $r['articles_pending'] : 0;
    }
    return $out;
}

/* =============================================================== ROUTER */
if ($action === 'meta') {
    $customers = array();
    $cond = $allowed === null ? '' : ' WHERE id IN (' . implode(',', $allowed ?: array(0)) . ')';
    $res = q("SELECT id, cust_name FROM adm_customer_master $cond ORDER BY cust_name ASC");
    while ($r = $res->fetch_assoc()) { $customers[] = array('id' => (int)$r['id'], 'name' => tx($r['cust_name'])); }
    $departments = array();
    $res = q('SELECT dept_code, dept_name FROM adm_dept_master ORDER BY dept_name ASC');
    while ($r = $res->fetch_assoc()) { $departments[] = array('id' => (int)$r['dept_code'], 'name' => tx($r['dept_name'])); }
    api_out(array('ok' => true, 'action' => 'meta', 'user' => array('id' => (int)$ur['id'], 'name' => $ur['full_name'], 'level' => (int)$ur['user_level']),
                  'customers' => $customers, 'departments' => $departments, 'all_customers_allowed' => $allowed === null, 'today' => date('Y-m-d'),
                  'default_range' => array('from' => date('Y-m-d', strtotime('-5 days')), 'to' => date('Y-m-d', strtotime('+2 days')))));
}

list($from, $to) = resolve_range(date('Y-m-d', strtotime('-5 days')), date('Y-m-d', strtotime('+2 days')));
$custs = resolve_customers();
$dept = resolve_dept();
$params = array('cust_id' => $custs === null ? 'all' : $custs, 'dept' => $dept === null ? 'all' : $dept, 'from' => $from, 'to' => $to);

if ($action === 'matrix') {
    $m = wip_matrix($custs, $dept, $from, $to);
    api_out(array_merge(array('ok' => true, 'action' => 'matrix', 'params' => $params), $m, array('ms' => round((microtime(true) - $t_start) * 1000), 'queries' => $q_count)));
}

if ($action === 'detail' || $action === 'export') {
    $stage = isset($_REQUEST['stage']) ? strtoupper(trim((string)$_REQUEST['stage'])) : 'ALL';
    if (!in_array($stage, array('FP', 'REV', 'FIN', 'PMC', 'JSTAGE', 'ISS', 'ALL'), true) || ($stage !== 'ALL' && !rs_row_enabled($stage))) { api_fail(400, 'Invalid stage.'); }
    $search = isset($_REQUEST['q']) && !is_array($_REQUEST['q']) ? trim((string)$_REQUEST['q']) : '';
    if (strlen($search) > 60) { api_fail(400, 'Search text too long.'); }
    $sort = isset($_REQUEST['sort']) && !is_array($_REQUEST['sort']) ? (string)$_REQUEST['sort'] : '';
    $dir = isset($_REQUEST['dir']) && !is_array($_REQUEST['dir']) ? (string)$_REQUEST['dir'] : 'asc';

    if ($action === 'detail') {
        $page = max(1, (int)(isset($_REQUEST['page']) ? $_REQUEST['page'] : 1));
        $per = min(200, max(1, (int)(isset($_REQUEST['per_page']) ? $_REQUEST['per_page'] : 50)));
        list($rows, $total) = detail_rows($custs, $dept, $from, $to, $stage, $page, $per, $sort, $dir, $search);
        issue_progress($rows, $dept);
        $shaped = array();
        foreach ($rows as $r) { $shaped[] = shape_row($r); }
        api_out(array('ok' => true, 'action' => 'detail', 'params' => array_merge($params, array('stage' => $stage)), 'total' => $total, 'page' => $page, 'per_page' => $per,
                      'pages' => (int)ceil($total / $per), 'rows' => $shaped, 'ms' => round((microtime(true) - $t_start) * 1000), 'queries' => $q_count));
    }

    /* export: CSV, whole result (capped) */
    list($rows, $total) = detail_rows($custs, $dept, $from, $to, $stage, 1, 100000, $sort, $dir, $search, true);
    issue_progress($rows, $dept);
    while (ob_get_level() > 0) { ob_end_clean(); }
    $name = 'wip_' . ($custs === null ? 'all' : implode('-', $custs)) . '_' . $stage . '_' . $from . ($from === $to ? '' : '_to_' . $to) . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Cache-Control: private, no-store');
    $o = fopen('php://output', 'w');
    fwrite($o, "\xEF\xBB\xBF");
    fputcsv($o, array('Type', 'Stage group', 'Customer', 'Journal', 'Article / Issue', 'Stage', 'Department', 'Folder', 'Received', 'Due', 'Days late', 'Target due', 'Status', 'Working status', 'Pages', 'Graphics', 'Figures', 'Priority', 'Articles done', 'Articles pending'));
    foreach ($rows as $r) {
        $s = shape_row($r);
        fputcsv($o, array($s['kind'], $s['stage_group'], $s['customer'], $s['journal_code'], $s['ref'], $s['stage'], $s['department'], $s['folder'], $s['received'], $s['due'], $s['overdue_days'] ?: '',
                          $s['target_due'], $s['status'], $s['working_status'], $s['pages'], isset($s['graphics']) ? $s['graphics'] : '', isset($s['figures']) ? $s['figures'] : '',
                          $s['priority'] ? 'High' : '', isset($s['articles_done']) ? $s['articles_done'] : '', isset($s['articles_pending']) ? $s['articles_pending'] : ''));
    }
    fclose($o);
    exit;
}

api_fail(400, 'Unknown action.');
