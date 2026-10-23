<?php
/* ============================================================================
 * Inflow report API  (Phase 1)             /workflow-new/api/inflow.php
 *
 * Same counting rules as the existing inflow report (inflow-report-all-data.php), fetched in
 * one pass instead of ~10 queries, and with the click-through list served as paged JSON.
 *
 *   ?action=meta                                  signed-in user, customers they may see, defaults
 *   ?action=matrix&cust_id=42&from=2026-06-01&to=2026-06-30      (or &month=2026-06)
 *   ?action=detail&cust_id=42&stage=FP|REV|FIN|JSTAGE|ISS|ALL&date=2026-06-15     one cell
 *                                         or &from=..&to=..                the whole range (TOTAL column)
 *                  [&page=1&per_page=50&sort=due&dir=asc&q=text]
 *   ?action=export  (same filters as detail)      CSV download
 *
 * Counting rules (identical to the existing page):
 *   FP   articles by created date                              live + archive
 *   REV  articles whose current stage is REV*  - by the received date of their LATEST revision
 *   FIN  articles whose current stage is FIN*  - same
 *   JSTAGE  articles whose current stage is JSTAGE*  - same (their latest revision is the JSTAGE round)
 *   ISS  volume issues by received date                         live + archive
 * Difference, on purpose: an article / issue that exists in BOTH live and archive is counted once
 * (live wins).  The old FP row counted it twice.  The response carries the old page's exact
 * numbers in "legacy" and what was left out in "duplicates_excluded", so both can be compared.
 *
 * Access: sign-in is checked the same way as the pages (token cookies, verified in the database).
 * Customers a user may see come from the database (users.cust_id / access_other_cust_id), not from cookies.
 * ========================================================================== */

$ag_json = true;
require __DIR__ . '/../includes/ajax_guard.php';          // 401 JSON when not signed in; also defines $userToken
include_once __DIR__ . '/../dbc.php';                      // $db (mysqli)
include_once __DIR__ . '/../includes/report_stages.php';   // $report_stage_types: which stages the reports show

$t_start = microtime(true);
$q_count = 0;

function api_out($data, $code = 200)
{
    while (ob_get_level() > 0) { ob_end_clean(); }
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: private, no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function api_fail($code, $msg) { api_out(array('ok' => false, 'error' => $msg, 'status' => $code), $code); }

function q($sql)
{
    global $db, $q_count;
    $q_count++;
    $r = $db->query($sql);
    if ($r === false) { api_fail(500, 'Query failed.'); }
    return $r;
}

/* ---------------------------------------------------------------- who is calling */
$username = isset($userToken[0]['username']) ? $userToken[0]['username'] : '';
$ue = $db->real_escape_string($username);
$ur = q("SELECT id, full_name, user_level, cust_id, access_other_cust_id, approved, banned FROM users WHERE user_name = '$ue' LIMIT 1")->fetch_assoc();
if (!$ur || (string)$ur['banned'] === '1' || (string)$ur['approved'] === '0') { api_fail(403, 'This account cannot use the report.'); }

/* customers this user may see: everyone for level 1, otherwise their own + "other" customers (as the report's drop-down) */
$allowed = null;                                           // null = no restriction
if ((string)$ur['user_level'] !== '1') {
    $ids = array();
    foreach (array_merge(array($ur['cust_id']), explode(',', (string)$ur['access_other_cust_id'])) as $x) {
        $x = trim($x);
        if ($x !== '' && ctype_digit($x)) { $ids[(int)$x] = (int)$x; }
    }
    $allowed = array_values($ids);
}

/* ---------------------------------------------------------------- request */
$action = isset($_REQUEST['action']) ? (string)$_REQUEST['action'] : 'matrix';

function req_date($key, $default = null)
{
    if (!isset($_REQUEST[$key]) || $_REQUEST[$key] === '') { return $default; }
    $v = trim((string)$_REQUEST[$key]);
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $v, $m) && checkdate((int)$m[2], (int)$m[3], (int)$m[1])) { return $v; }
    if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $v, $m) && checkdate((int)$m[2], (int)$m[1], (int)$m[3])) { return $m[3] . '-' . $m[2] . '-' . $m[1]; }
    api_fail(400, 'Invalid date: ' . $key);
}

function resolve_range()
{
    $today = date('Y-m-d');
    if (!empty($_REQUEST['month'])) {                                  // 2026-06  or  06-2026
        $mm = trim((string)$_REQUEST['month']);
        if (preg_match('/^(\d{4})-(\d{2})$/', $mm, $m)) { $y = (int)$m[1]; $mo = (int)$m[2]; }
        elseif (preg_match('/^(\d{2})-(\d{4})$/', $mm, $m)) { $y = (int)$m[2]; $mo = (int)$m[1]; }
        else { api_fail(400, 'Invalid month.'); }
        if ($mo < 1 || $mo > 12) { api_fail(400, 'Invalid month.'); }
        $from = sprintf('%04d-%02d-01', $y, $mo);
        $to = date('Y-m-t', strtotime($from));
        if (substr($from, 0, 7) === date('Y-m')) { $to = $today; }      // the running month stops at today
        return array($from, $to);
    }
    $d = req_date('date');
    if ($d) { return array($d, $d); }
    $from = req_date('from', date('Y-m-d', strtotime('-5 days')));
    $to = req_date('to', $today);
    if ($to < $from) { $t = $from; $from = $to; $to = $t; }
    if ((strtotime($to) - strtotime($from)) / 86400 > 400) { api_fail(400, 'Date range too long (max 400 days).'); }
    return array($from, $to);
}

/* customer filter -> array of ints, or null for "everything this user may see" */
function resolve_customers()
{
    global $allowed;
    $c = isset($_REQUEST['cust_id']) ? $_REQUEST['cust_id'] : 'all';
    if (is_array($c)) { api_fail(400, 'Invalid customer.'); }
    $c = trim((string)$c);
    if ($c === '' || $c === 'all') { return $allowed === null ? null : ($allowed ?: array(0)); }
    if (!ctype_digit($c)) { api_fail(400, 'Invalid customer.'); }
    $c = (int)$c;
    if ($allowed !== null && !in_array($c, $allowed, true)) { api_fail(403, 'You do not have access to this customer.'); }
    return array($c);
}

function cust_sql_str($alias, $custs)      // inw_inward_dtl.cust_id is varchar: compare with strings so the index can be used
{
    if ($custs === null) { return ''; }
    return " AND $alias.cust_id IN ('" . implode("','", array_map('intval', $custs)) . "')";
}
function cust_sql_int($alias, $custs)
{
    if ($custs === null) { return ''; }
    return " AND $alias.cust_id IN (" . implode(',', array_map('intval', $custs)) . ")";
}

/* ---------------------------------------------------------------- shared pieces */
const IA_LIVE = 'workflow';
const IA_ARCH = 'workflow_archive';

function dt_bounds($from, $to) { return array($from . ' 00:00:00', date('Y-m-d', strtotime($to . ' +1 day')) . ' 00:00:00'); }

/* "not already in live" for archive rows (live wins) */
function dup_flag_article($alias) { return "EXISTS (SELECT 1 FROM " . IA_LIVE . ".inw_inward_dtl l WHERE l.id = $alias.id)"; }
function dup_flag_issue($alias)   { return "EXISTS (SELECT 1 FROM " . IA_LIVE . ".inw_volume_issues l WHERE l.vi_id = $alias.vi_id)"; }

/* =============================================================== MATRIX */
function matrix($custs, $from, $to)
{
    list($f0, $t1) = dt_bounds($from, $to);
    $parts = array();
    foreach (array('L' => IA_LIVE, 'A' => IA_ARCH) as $src => $s) {
        $dupA = $src === 'A' ? dup_flag_article('wd') : '0';
        $cA = cust_sql_str('wd', $custs);
        // FP: created date
        if (rs_row_enabled('FP')) $parts[] = "SELECT 'FP' AS stg, DATE(wd.created_dt) AS d, '$src' AS src, wd.id AS k, $dupA AS dup
                    FROM $s.inw_inward_dtl wd
                    WHERE wd.created_dt >= '$f0' AND wd.created_dt < '$t1' $cA";
        // REV / FIN: received date of the latest revision (a newer revision of the same article takes over)
        $parts[] = "SELECT IF(LEFT(wd.stage,3) = 'REV', 'REV', 'FIN') AS stg, r.received_date AS d, '$src' AS src, wd.id AS k, $dupA AS dup
                    FROM $s.inw_inward_dtl wd
                    JOIN $s.adm_journals j ON j.j_id = wd.j_id
                    JOIN $s.inw_revisions_dtl r ON r.inw_id = wd.id
                    WHERE " . rs_rev_fin_cond('ALL') . "
                      AND r.received_date BETWEEN '$from' AND '$to' AND r.created_on > '2025-01-01'
                      AND NOT EXISTS (SELECT 1 FROM $s.inw_revisions_dtl x WHERE x.inw_id = r.inw_id AND x.r_id > r.r_id) $cA";
        // JSTAGE: same rule - articles in a JSTAGE round, by the received date of their latest revision
        if (rs_row_enabled('PMC')) $parts[] = "SELECT 'PMC' AS stg, r.received_date AS d, '$src' AS src, wd.id AS k, $dupA AS dup
                    FROM $s.inw_inward_dtl wd
                    JOIN $s.adm_journals j ON j.j_id = wd.j_id
                    JOIN $s.inw_revisions_dtl r ON r.inw_id = wd.id
                    WHERE wd.stage LIKE 'PMC%'
                      AND r.received_date BETWEEN '$from' AND '$to' AND r.created_on > '2025-01-01'
                      AND NOT EXISTS (SELECT 1 FROM $s.inw_revisions_dtl x WHERE x.inw_id = r.inw_id AND x.r_id > r.r_id) $cA";

        if (rs_row_enabled('JSTAGE')) $parts[] = "SELECT 'JSTAGE' AS stg, r.received_date AS d, '$src' AS src, wd.id AS k, $dupA AS dup
                    FROM $s.inw_inward_dtl wd
                    JOIN $s.adm_journals j ON j.j_id = wd.j_id
                    JOIN $s.inw_revisions_dtl r ON r.inw_id = wd.id
                    WHERE wd.stage LIKE 'JSTAGE%'
                      AND r.received_date BETWEEN '$from' AND '$to' AND r.created_on > '2025-01-01'
                      AND NOT EXISTS (SELECT 1 FROM $s.inw_revisions_dtl x WHERE x.inw_id = r.inw_id AND x.r_id > r.r_id) $cA";
        // ISS: volume issues by received date (journals as in the existing report: live table for both)
        $dupI = $src === 'A' ? dup_flag_issue('vi') : '0';
        $cI = cust_sql_int('vi', $custs);
        if (rs_row_enabled('ISS')) $parts[] = "SELECT 'ISS' AS stg, DATE(vi.received_date) AS d, '$src' AS src, vi.vi_id AS k, $dupI AS dup
                    FROM $s.inw_volume_issues vi
                    JOIN " . IA_LIVE . ".adm_journals j ON j.j_id = vi.j_id AND j.j_cust_id = vi.cust_id
                    WHERE vi.received_date >= '$f0' AND vi.received_date < '$t1' $cI";
    }
    if (!$parts) { $parts[] = "SELECT 'FP' AS stg, NULL AS d, 'L' AS src, 0 AS k, 0 AS dup FROM dual WHERE 1=0"; }
    $sql = "SELECT stg, d,
                   COUNT(DISTINCT CASE WHEN src = 'L' THEN k END)                  AS n_live,
                   COUNT(DISTINCT CASE WHEN src = 'A' AND dup = 0 THEN k END)      AS n_arch_unique,
                   COUNT(DISTINCT CASE WHEN src = 'A' AND dup = 1 THEN k END)      AS n_arch_dup,
                   COUNT(*)                                                        AS rows_all,
                   COUNT(DISTINCT k)                                               AS dist_all
            FROM (" . implode(' UNION ALL ', $parts) . ") t
            GROUP BY stg, d";
    $res = q($sql);

    $days = array();
    for ($c = strtotime($from); $c <= strtotime($to); $c = strtotime('+1 day', $c)) { $days[] = date('Y-m-d', $c); }
    $idx = array_flip($days);
    $n = count($days);
    $zero = array_fill(0, $n, 0);
    $rows = array('FP' => $zero, 'REV' => $zero, 'FIN' => $zero, 'PMC' => $zero, 'JSTAGE' => $zero, 'ISS' => $zero);
    $legacy = $rows;
    $dups = array('FP' => 0, 'REV' => 0, 'FIN' => 0, 'PMC' => 0, 'JSTAGE' => 0, 'ISS' => 0);
    while ($r = $res->fetch_assoc()) {
        if (!isset($idx[$r['d']])) { continue; }
        $i = $idx[$r['d']]; $s = $r['stg'];
        $rows[$s][$i] = (int)$r['n_live'] + (int)$r['n_arch_unique'];
        $dups[$s] += (int)$r['n_arch_dup'];
        // what the existing page shows: FP counts every row of both tables, REV/FIN distinct ids, the ISS row is live only
        $legacy[$s][$i] = $s === 'FP' ? (int)$r['rows_all'] : ($s === 'ISS' ? (int)$r['n_live'] : (int)$r['dist_all']);
    }
    $out = array('days' => $days, 'rows' => array(), 'legacy' => array());
    $per_day = $zero;
    foreach ($rows as $s => $arr) {
        $out['rows'][$s] = array('counts' => $arr, 'total' => array_sum($arr));
        foreach ($arr as $i => $v) { $per_day[$i] += $v; }
    }
    foreach ($legacy as $s => $arr) { $out['legacy'][$s] = array('counts' => $arr, 'total' => array_sum($arr)); }
    $out['totals'] = array('per_day' => $per_day, 'grand' => array_sum($per_day));
    $out['duplicates_excluded'] = $dups;
    return $out;
}

/* =============================================================== DETAIL / EXPORT */
function detail_union($custs, $from, $to, $stage)
{
    list($f0, $t1) = dt_bounds($from, $to);
    $parts = array();
    $want = function ($s) use ($stage) { return ($stage === 'ALL' || $stage === $s) && rs_row_enabled($s); };   // only the stages in $report_stage_types
    foreach (array('Live' => IA_LIVE, 'Archive' => IA_ARCH) as $srcName => $s) {
        $arch = $srcName === 'Archive';
        $noDup = $arch ? ' AND NOT ' . dup_flag_article('wd') : '';
        $cA = cust_sql_str('wd', $custs);
        $common = "'$srcName' AS source, wd.id AS id, 'A' AS kind, wd.pub_id AS ref, c.cust_name AS customer, j.j_code AS j_code, j.j_name AS j_name,
                   wd.volume_folder AS folder, wd.status AS status, wd.user_working_status AS working, wd.fig_complete_status AS graphics_done,
                   wd.fig AS fig, wd.highpriority AS priority, wd.aq_status AS aq_status, NULL AS start_page";
        if ($want('FP')) {
            $parts[] = "SELECT 'FP' AS stg, DATE(wd.created_dt) AS ev_date, 'FP' AS stage_label, DATE(wd.created_dt) AS received, wd.created_dt AS received_at, wd.due_dt AS due,
                               CAST(wd.manuscript_count AS UNSIGNED) AS pages, $common
                        FROM $s.inw_inward_dtl wd
                        LEFT JOIN " . IA_LIVE . ".adm_customer_master c ON c.id = wd.cust_id
                        LEFT JOIN $s.adm_journals j ON j.j_id = wd.j_id
                        WHERE wd.created_dt >= '$f0' AND wd.created_dt < '$t1' $cA $noDup";
        }
        if ($want('REV') || $want('FIN')) {
            $stageCond = rs_rev_fin_cond($stage);
            $parts[] = "SELECT IF(LEFT(wd.stage,3) = 'REV', 'REV', 'FIN') AS stg, r.received_date AS ev_date,
                               CONCAT(IF(LEFT(wd.stage,3) = 'REV', 'REV', 'FIN'), r.revision_count) AS stage_label, r.received_date AS received, r.created_on AS received_at, r.due_date AS due,
                               r.correction_pages AS pages, $common
                        FROM $s.inw_inward_dtl wd
                        JOIN $s.adm_journals j ON j.j_id = wd.j_id
                        JOIN $s.inw_revisions_dtl r ON r.inw_id = wd.id
                        LEFT JOIN " . IA_LIVE . ".adm_customer_master c ON c.id = wd.cust_id
                        WHERE $stageCond AND r.received_date BETWEEN '$from' AND '$to' AND r.created_on > '2025-01-01'
                          AND NOT EXISTS (SELECT 1 FROM $s.inw_revisions_dtl x WHERE x.inw_id = r.inw_id AND x.r_id > r.r_id) $cA $noDup";
        }
        if ($want('PMC')) {
            $parts[] = "SELECT 'PMC' AS stg, r.received_date AS ev_date,
                               CONCAT('PMC', r.revision_count) AS stage_label, r.received_date AS received, r.created_on AS received_at, r.due_date AS due,
                               r.correction_pages AS pages, $common
                        FROM $s.inw_inward_dtl wd
                        JOIN $s.adm_journals j ON j.j_id = wd.j_id
                        JOIN $s.inw_revisions_dtl r ON r.inw_id = wd.id
                        LEFT JOIN " . IA_LIVE . ".adm_customer_master c ON c.id = wd.cust_id
                        WHERE wd.stage LIKE 'PMC%' AND r.received_date BETWEEN '$from' AND '$to' AND r.created_on > '2025-01-01'
                          AND NOT EXISTS (SELECT 1 FROM $s.inw_revisions_dtl x WHERE x.inw_id = r.inw_id AND x.r_id > r.r_id) $cA $noDup";
        }
        if ($want('JSTAGE')) {
            $parts[] = "SELECT 'JSTAGE' AS stg, r.received_date AS ev_date,
                               CONCAT('JSTAGE', r.revision_count) AS stage_label, r.received_date AS received, r.created_on AS received_at, r.due_date AS due,
                               r.correction_pages AS pages, $common
                        FROM $s.inw_inward_dtl wd
                        JOIN $s.adm_journals j ON j.j_id = wd.j_id
                        JOIN $s.inw_revisions_dtl r ON r.inw_id = wd.id
                        LEFT JOIN " . IA_LIVE . ".adm_customer_master c ON c.id = wd.cust_id
                        WHERE wd.stage LIKE 'JSTAGE%' AND r.received_date BETWEEN '$from' AND '$to' AND r.created_on > '2025-01-01'
                          AND NOT EXISTS (SELECT 1 FROM $s.inw_revisions_dtl x WHERE x.inw_id = r.inw_id AND x.r_id > r.r_id) $cA $noDup";
        }
        if ($want('ISS')) {
            $noDupI = $arch ? ' AND NOT ' . dup_flag_issue('vi') : '';
            $cI = cust_sql_int('vi', $custs);
            $parts[] = "SELECT 'ISS' AS stg, DATE(vi.received_date) AS ev_date, vi.stage AS stage_label, vi.received_date AS received, vi.received_date AS received_at, vi.due_date AS due,
                               vi.correction_pages AS pages, '$srcName' AS source, vi.vi_id AS id, 'I' AS kind,
                               CONCAT_WS(' / ', NULLIF(CONCAT('Vol ', vi.volume_number), 'Vol '), NULLIF(CONCAT('Issue ', vi.issue_number), 'Issue ')) AS ref,
                               c.cust_name AS customer, j.j_code AS j_code, j.j_name AS j_name, '' AS folder, vi.status AS status, NULL AS working,
                               NULL AS graphics_done, NULL AS fig, 0 AS priority, NULL AS aq_status, vi.starting_page_number AS start_page
                        FROM $s.inw_volume_issues vi
                        JOIN " . IA_LIVE . ".adm_journals j ON j.j_id = vi.j_id AND j.j_cust_id = vi.cust_id
                        LEFT JOIN " . IA_LIVE . ".adm_customer_master c ON c.id = vi.cust_id
                        WHERE vi.received_date >= '$f0' AND vi.received_date < '$t1' $cI $noDupI";
        }
    }
    return implode(' UNION ALL ', $parts);
}

function detail_rows($custs, $from, $to, $stage, $page, $per, $sort, $dir, $search, $no_limit = false)
{
    global $db;
    $union = detail_union($custs, $from, $to, $stage);
    $where = '';
    if ($search !== '') {
        $s = $db->real_escape_string(addcslashes($search, '%_\\'));
        $where = " WHERE (ref LIKE '%$s%' OR j_code LIKE '%$s%' OR j_name LIKE '%$s%' OR customer LIKE '%$s%')";
    }
    $cols = array('date' => 'ev_date', 'due' => 'due', 'received' => 'received', 'ref' => 'ref', 'customer' => 'customer', 'journal' => 'j_code',
                  'stage' => 'stage_label', 'pages' => 'pages', 'status' => 'status');
    $dirSql = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';
    $order = isset($cols[$sort]) ? $cols[$sort] . " $dirSql, id ASC" : 'priority DESC, due ASC, id ASC';
    $limit = $no_limit ? '' : ' LIMIT ' . (($page - 1) * $per) . ',' . $per;
    $res = q("SELECT SQL_CALC_FOUND_ROWS * FROM ($union) t $where ORDER BY $order $limit");
    $rows = array();
    while ($r = $res->fetch_assoc()) { $rows[] = $r; }
    $total = (int)q('SELECT FOUND_ROWS() AS n')->fetch_assoc()['n'];
    return array($rows, $total);
}

/* articles done / pending for the issues on this page: one query, not one per row */
function issue_progress(&$rows)
{
    $live = array(); $arch = array();
    foreach ($rows as $r) {
        if ($r['kind'] !== 'I') { continue; }
        if ($r['source'] === 'Live') { $live[] = (int)$r['id']; } else { $arch[] = (int)$r['id']; }
    }
    $map = array();
    foreach (array(IA_LIVE => $live, IA_ARCH => $arch) as $s => $ids) {
        if (!$ids) { continue; }
        $res = q("SELECT v_issue_id, SUM(status = 'Delivery') AS done, SUM(status <> 'Delivery') AS pending
                  FROM $s.inw_inward_dtl WHERE v_issue_id IN (" . implode(',', $ids) . ") GROUP BY v_issue_id");
        while ($x = $res->fetch_assoc()) { $map[($s === IA_LIVE ? 'Live' : 'Archive') . ':' . $x['v_issue_id']] = $x; }
    }
    foreach ($rows as &$r) {
        if ($r['kind'] === 'I') {
            $k = $r['source'] . ':' . $r['id'];
            $r['articles_done'] = isset($map[$k]) ? (int)$map[$k]['done'] : 0;
            $r['articles_pending'] = isset($map[$k]) ? (int)$map[$k]['pending'] : 0;
        }
    }
    unset($r);
}

/* the tables store some text with HTML entities already encoded ("Sciences &amp; ..."); return plain text */
function tx($v) { return $v === null ? null : html_entity_decode((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8'); }

/* user_working_status is stored as ready-made HTML, e.g. <span class="label label-sm label-success">PAG</span>|<span class="label label-sm label-info">Name</span>|...
   Return it as plain data: [{t:"PAG", k:"success"}, ...] so the page draws its own badges and never injects stored markup. */
function working_parts($html)
{
    $html = (string)$html;
    if ($html === '') { return array(); }
    $parts = array();
    if (preg_match_all('/<span[^>]*class="[^"]*label-(success|info|warning|danger|primary|default)[^"]*"[^>]*>(.*?)<\/span>/is', $html, $m, PREG_SET_ORDER)) {
        foreach ($m as $x) {
            $t = trim(html_entity_decode(strip_tags($x[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($t !== '') { $parts[] = array('t' => $t, 'k' => strtolower($x[1])); }
        }
        return $parts;
    }
    $t = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    return $t === '' ? array() : array(array('t' => $t, 'k' => 'default'));
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
    $out = array(
        'id' => (int)$r['id'], 'kind' => $r['kind'] === 'I' ? 'issue' : 'article', 'source' => $r['source'], 'stage_group' => $r['stg'],
        'date' => $r['ev_date'], 'ref' => tx($r['ref']), 'customer' => tx($r['customer']), 'journal_code' => tx($r['j_code']), 'journal_name' => tx($r['j_name']),
        'stage' => tx($r['stage_label']), 'folder' => tx($r['folder']), 'received' => $r['received'], 'received_time' => rcv_time(isset($r['received_at']) ? $r['received_at'] : null), 'due' => $r['due'],
        'status' => tx($r['status']), 'working_parts' => working_parts($r['working']),
        'working_status' => implode(' | ', array_map(function ($p) { return $p['t']; }, working_parts($r['working']))), 'pages' => $r['pages'] === null ? null : (int)$r['pages'],
        'priority' => (int)$r['priority'] === 1, 'overdue' => ($r['due'] && $r['due'] < $today && $r['due'] !== '0000-00-00'),
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
    while ($r = $res->fetch_assoc()) { $customers[] = array('id' => (int)$r['id'], 'name' => $r['cust_name']); }
    api_out(array('ok' => true, 'action' => 'meta', 'user' => array('id' => (int)$ur['id'], 'name' => $ur['full_name'], 'level' => (int)$ur['user_level']),
                  'customers' => $customers, 'all_customers_allowed' => $allowed === null, 'today' => date('Y-m-d'),
                  'default_range' => array('from' => date('Y-m-d', strtotime('-5 days')), 'to' => date('Y-m-d'))));
}

list($from, $to) = resolve_range();
$custs = resolve_customers();

if ($action === 'matrix') {
    $m = matrix($custs, $from, $to);
    api_out(array_merge(array('ok' => true, 'action' => 'matrix', 'params' => array('cust_id' => $custs === null ? 'all' : $custs, 'from' => $from, 'to' => $to)), $m,
                        array('ms' => round((microtime(true) - $t_start) * 1000), 'queries' => $q_count)));
}

if ($action === 'detail' || $action === 'export') {
    $stage = isset($_REQUEST['stage']) ? strtoupper(trim((string)$_REQUEST['stage'])) : 'ALL';
    if (!in_array($stage, array('FP', 'REV', 'FIN', 'PMC', 'JSTAGE', 'ISS', 'ALL'), true) || ($stage !== 'ALL' && !rs_row_enabled($stage))) { api_fail(400, 'Invalid stage.'); }
    $search = isset($_REQUEST['q']) ? trim((string)$_REQUEST['q']) : '';
    if (strlen($search) > 60) { api_fail(400, 'Search text too long.'); }
    $sort = isset($_REQUEST['sort']) ? (string)$_REQUEST['sort'] : '';
    $dir = isset($_REQUEST['dir']) ? (string)$_REQUEST['dir'] : 'asc';

    if ($action === 'detail') {
        $page = max(1, (int)(isset($_REQUEST['page']) ? $_REQUEST['page'] : 1));
        $per = min(200, max(1, (int)(isset($_REQUEST['per_page']) ? $_REQUEST['per_page'] : 50)));
        list($rows, $total) = detail_rows($custs, $from, $to, $stage, $page, $per, $sort, $dir, $search);
        issue_progress($rows);
        $shaped = array();
        foreach ($rows as $r) { $shaped[] = shape_row($r); }
        api_out(array('ok' => true, 'action' => 'detail', 'params' => array('cust_id' => $custs === null ? 'all' : $custs, 'stage' => $stage, 'from' => $from, 'to' => $to),
                      'total' => $total, 'page' => $page, 'per_page' => $per, 'pages' => (int)ceil($total / $per), 'rows' => $shaped,
                      'ms' => round((microtime(true) - $t_start) * 1000), 'queries' => $q_count));
    }

    /* export: CSV, whole result (capped) */
    list($rows, $total) = detail_rows($custs, $from, $to, $stage, 1, 100000, $sort, $dir, $search, true);
    issue_progress($rows);
    while (ob_get_level() > 0) { ob_end_clean(); }
    $name = 'inflow_' . ($custs === null ? 'all' : implode('-', $custs)) . '_' . $stage . '_' . $from . ($from === $to ? '' : '_to_' . $to) . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Cache-Control: private, no-store');
    $o = fopen('php://output', 'w');
    fwrite($o, "\xEF\xBB\xBF");
    fputcsv($o, array('Source', 'Type', 'Stage group', 'Date', 'Customer', 'Journal', 'Article / Issue', 'Stage', 'Folder', 'Received', 'Due', 'Status', 'Pages', 'Graphics', 'Figures', 'Priority', 'Articles done', 'Articles pending'));
    foreach ($rows as $r) {
        $s = shape_row($r);
        fputcsv($o, array($s['source'], $s['kind'], $s['stage_group'], $s['date'], $s['customer'], $s['journal_code'], $s['ref'], $s['stage'], $s['folder'], $s['received'], $s['due'],
                          $s['status'], $s['pages'], isset($s['graphics']) ? $s['graphics'] : '', isset($s['figures']) ? $s['figures'] : '', $s['priority'] ? 'High' : '',
                          isset($s['articles_done']) ? $s['articles_done'] : '', isset($s['articles_pending']) ? $s['articles_pending'] : ''));
    }
    fclose($o);
    exit;
}

api_fail(400, 'Unknown action.');
