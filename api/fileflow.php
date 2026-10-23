<?php
/* ============================================================================
 * Manuscript File Flow API                               /workflow-new/api/fileflow.php
 *
 * "When did each department pick up this manuscript, and where is it now?"
 *
 * A row is one CYCLE of a manuscript received in the chosen period:
 *     FP   a first proof        - the article, by its received date
 *     REV  a revision round      - one row per round (REV1, REV2 ...), by the round's received date
 *     PMC  a PMC round           - same
 *     FIN  a final round         - same
 *     JSTAGE  a JSTAGE round     - same
 * It is listed whatever stage the manuscript is at today.  (The previous report only listed manuscripts that were
 * still sitting in the chosen stage, so a past period looked almost empty; ?current_only=1 gives that view back.)
 *
 * The department columns come from the transactions of THAT cycle only (stage = FP / REV2 / FIN1 ...): the first
 * time the department touched the file, and the last time it finished.  A department group with no transaction is
 * simply empty.  ELX teams are shown in the column of the department they mirror.
 *
 *   ?action=meta
 *   ?action=journals&cust_id=42
 *   ?action=summary&stage=FP&from=2026-06-01&to=2026-06-30&cust_id=42[&j_id=..&platform=LATEX&current_only=1]
 *   ?action=list   (same filters) [&page=1&per_page=50&sort=received&dir=desc&q=text]
 *   ?action=export (same filters)  CSV
 *   ?action=trace&id=45050         the whole journey of one manuscript (every stage, every department step)
 *
 * Live and archive are read together; an article that exists in both is counted once (live wins).
 * ========================================================================== */

require __DIR__ . '/../includes/report_api.php';
include_once __DIR__ . '/../includes/report_stages.php';          // $report_stage_types: which stages the reports show

$action = isset($_REQUEST['action']) ? (string)$_REQUEST['action'] : 'list';

/* department columns: key => [label, department ids].  ELX teams sit with the department they mirror. */
function ff_groups()
{
    return array(
        'PRE'   => array('Pre-Editing / Pre-Clean', array(2, 22, 24, 33)),
        'CE'    => array('CE',                      array(1, 32)),
        'ART'   => array('Art',                     array(9, 35)),
        'PAG'   => array('PAG',                     array(10, 38)),
        'PRQC'  => array('PRQC',                    array(14, 34)),
        'PROOF' => array('Proofing',                array(18)),
        'XML'   => array('XML',                     array(5, 36)),
        'EPUB'  => array('Epub',                    array(17)),
    );
}
function ff_columns($stage)
{
    $map = array('FP' => array('PRE', 'CE', 'ART', 'PAG', 'PRQC', 'PROOF', 'XML'), 'REV' => array('ART', 'PAG', 'PRQC', 'XML'),
                 'PMC' => array('PAG', 'PRQC', 'XML'), 'FIN' => array('ART', 'PAG', 'PRQC', 'XML', 'EPUB'),
                 'JSTAGE' => array('ART', 'PAG', 'PRQC', 'XML', 'EPUB'));
    $g = ff_groups(); $cols = array();
    foreach ($map[$stage] as $k) { $cols[] = array('key' => $k, 'name' => $g[$k][0], 'depts' => $g[$k][1]); }
    return $cols;
}

/* ---------------------------------------------------------------- request */
function ff_filters()
{
    global $db;
    $stage = isset($_REQUEST['stage']) && !is_array($_REQUEST['stage']) ? strtoupper(trim((string)$_REQUEST['stage'])) : 'FP';
    if (!in_array($stage, array('FP', 'REV', 'PMC', 'FIN', 'JSTAGE'), true) || !rs_row_enabled($stage)) { api_fail(400, 'Invalid stage.'); }
    list($from, $to) = resolve_range(date('Y-m-d', strtotime('-29 days')), date('Y-m-d'));
    $custs = resolve_customers();
    $j = isset($_REQUEST['j_id']) && !is_array($_REQUEST['j_id']) ? trim((string)$_REQUEST['j_id']) : 'all';
    if ($j !== '' && $j !== 'all' && !ctype_digit($j)) { api_fail(400, 'Invalid journal.'); }
    $platform = isset($_REQUEST['platform']) && !is_array($_REQUEST['platform']) ? trim((string)$_REQUEST['platform']) : 'all';
    if ($platform !== '' && $platform !== 'all' && !preg_match('/^[A-Za-z0-9 _-]{1,20}$/', $platform)) { api_fail(400, 'Invalid platform.'); }
    $cur = !empty($_REQUEST['current_only']) && (string)$_REQUEST['current_only'] !== '0';
    $focus = isset($_REQUEST['focus']) && !is_array($_REQUEST['focus']) ? trim((string)$_REQUEST['focus']) : '';
    if (!in_array($focus, array('', 'waiting', 'elsewhere', 'progress', 'wait', 'flow'), true)) { api_fail(400, 'Invalid focus.'); }
    return array('focus' => $focus, 'stage' => $stage, 'from' => $from, 'to' => $to, 'custs' => $custs, 'j' => ($j === '' || $j === 'all') ? null : (int)$j,
                 'platform' => ($platform === '' || $platform === 'all') ? null : $db->real_escape_string($platform), 'current_only' => $cur);
}

/* ---------------------------------------------------------------- the cycles in the period */
function ff_cycles_sql($f)
{
    $stage = $f['stage'];
    $cA = cust_sql_str('wd', $f['custs']);
    $jA = $f['j'] === null ? '' : ' AND wd.j_id = ' . (int)$f['j'];
    $pA = $f['platform'] === null ? '' : " AND j.j_platform = '" . $f['platform'] . "'";
    $parts = array();
    foreach (array('Live' => 'workflow', 'Archive' => 'workflow_archive') as $src => $S) {
        $dup = $src === 'Archive' ? ' AND NOT EXISTS (SELECT 1 FROM workflow.inw_inward_dtl l WHERE l.id = wd.id)' : '';
        $cur = $f['current_only'] ? " AND wd.stage LIKE '%" . $stage . "%'" : '';
        $toc = " AND wd.article_title NOT LIKE '%\\_TOC'";
        if ($stage === 'FP') {
            $parts[] = "SELECT '$src' AS src, wd.id AS id, wd.pub_id AS ref, wd.article_title AS title, wd.cust_id AS cust_id, j.j_code AS j_code, j.j_name AS j_name,
                               'FP' AS cyc, 0 AS rc, 'FP' AS stage_key, wd.recv_dt AS received, wd.due_dt AS due, wd.created_dt AS created_ts,
                               wd.stage AS cur_stage, wd.status AS cur_status,
                               COALESCE(NULLIF(wd.page_count,0), NULLIF(CAST(wd.manuscript_count AS UNSIGNED),0)) AS pages
                        FROM $S.inw_inward_dtl wd
                        JOIN $S.adm_journals j ON j.j_id = wd.j_id
                        WHERE wd.recv_dt BETWEEN '{$f['from']}' AND '{$f['to']}' $toc $cA $jA $pA $cur $dup";
        } else {
            $parts[] = "SELECT '$src' AS src, wd.id AS id, wd.pub_id AS ref, wd.article_title AS title, wd.cust_id AS cust_id, j.j_code AS j_code, j.j_name AS j_name,
                               '$stage' AS cyc, r.revision_count AS rc, CONCAT('$stage', r.revision_count) AS stage_key, r.received_date AS received, r.due_date AS due,
                               r.created_on AS created_ts, wd.stage AS cur_stage, wd.status AS cur_status,
                               COALESCE(NULLIF(r.page_count,0), NULLIF(r.correction_pages,0), NULLIF(wd.page_count,0), NULLIF(CAST(wd.manuscript_count AS UNSIGNED),0)) AS pages
                        FROM $S.inw_inward_dtl wd
                        JOIN $S.adm_journals j ON j.j_id = wd.j_id
                        JOIN $S.inw_revisions_dtl r ON r.inw_id = wd.id AND r.type = '$stage'
                        WHERE r.received_date BETWEEN '{$f['from']}' AND '{$f['to']}' $toc $cA $jA $pA $cur $dup";
        }
    }
    return implode(' UNION ALL ', $parts);
}

/* ---------------------------------------------------------------- department times for a set of cycles */
function ff_times($rows, $stage)
{
    $times = array();
    if (!$rows) { return $times; }
    $groups = ff_groups(); $dept2key = array();
    foreach (ff_columns($stage) as $c) { foreach ($c['depts'] as $d) { $dept2key[$d] = $c['key']; } }
    $ids = array(); $keys = array();
    foreach ($rows as $r) { $ids[(int)$r['id']] = 1; $keys[$r['stage_key']] = 1; }
    $idList = implode(',', array_keys($ids));
    $keyList = "'" . implode("','", array_map(function ($k) { return preg_replace('/[^A-Za-z0-9]/', '', $k); }, array_keys($keys))) . "'";
    $sql = "SELECT project_id, stage, dept, MIN(start_time) AS s, MAX(end_time) AS e, SUM(end_time IS NULL) AS open_n FROM (
                SELECT project_id, stage, dept, start_time, end_time FROM workflow.inw_transactions WHERE project_id IN ($idList) AND stage IN ($keyList)
                UNION ALL
                SELECT project_id, stage, dept, start_time, end_time FROM workflow_archive.inw_transactions WHERE project_id IN ($idList) AND stage IN ($keyList)
            ) t GROUP BY project_id, stage, dept";
    $res = q($sql);
    while ($x = $res->fetch_assoc()) {
        $d = (int)$x['dept'];
        if (!isset($dept2key[$d])) { continue; }
        $k = $dept2key[$d]; $pid = (int)$x['project_id']; $st = $x['stage'];
        if (!isset($times[$pid][$st][$k])) { $times[$pid][$st][$k] = array('s' => $x['s'], 'e' => $x['e'], 'open' => (int)$x['open_n'] > 0); continue; }
        $c =& $times[$pid][$st][$k];
        if ($x['s'] !== null && ($c['s'] === null || $x['s'] < $c['s'])) { $c['s'] = $x['s']; }
        if ($x['e'] !== null && ($c['e'] === null || $x['e'] > $c['e'])) { $c['e'] = $x['e']; }
        if ((int)$x['open_n'] > 0) { $c['open'] = true; }
        unset($c);
    }
    return $times;
}

function ff_shape($r, $times, $stage, $custNames)
{
    $cols = ff_columns($stage);
    $t = isset($times[(int)$r['id']][$r['stage_key']]) ? $times[(int)$r['id']][$r['stage_key']] : array();
    $depts = array(); $first = null; $last = null; $open = false; $touched = 0;
    foreach ($cols as $c) {
        if (isset($t[$c['key']]) && $t[$c['key']]['s']) {
            $x = $t[$c['key']];
            $depts[$c['key']] = array('start' => $x['s'], 'end' => $x['open'] ? null : $x['e'], 'open' => $x['open']);
            $touched++;
            if ($first === null || $x['s'] < $first) { $first = $x['s']; }
            if ($x['open']) { $open = true; }
            elseif ($x['e'] !== null && ($last === null || $x['e'] > $last)) { $last = $x['e']; }
        }
    }
    $wait = ($first !== null && $r['created_ts'] && $first >= $r['created_ts']) ? (int)round((strtotime($first) - strtotime($r['created_ts'])) / 60) : null;
    $flow = ($first !== null && $last !== null && !$open && $last >= $first) ? (int)round((strtotime($last) - strtotime($first)) / 60) : null;
    $due = ($r['due'] && $r['due'] !== '0000-00-00') ? $r['due'] : null;
    return array(
        'id' => (int)$r['id'], 'source' => $r['src'], 'ref' => tx($r['ref']), 'title' => tx($r['title']), 'customer' => isset($custNames[(int)$r['cust_id']]) ? tx($custNames[(int)$r['cust_id']]) : null,
        'journal_code' => tx($r['j_code']), 'journal_name' => tx($r['j_name']), 'cycle' => $r['cyc'], 'stage' => $r['stage_key'],
        'received' => ($r['received'] && $r['received'] !== '0000-00-00') ? $r['received'] : null, 'due' => $due, 'logged_at' => $r['created_ts'],
        'current_stage' => tx($r['cur_stage']), 'current_status' => tx($r['cur_status']), 'pages' => $r['pages'] === null ? null : (int)$r['pages'],
        'depts' => $depts, 'first_start' => $first, 'last_end' => $last, 'in_progress' => $open, 'picked_up' => $touched > 0,
        'wait_min' => $wait, 'flow_min' => $flow,
    );
}

/* A cycle with no timing in the department columns is one of three different things:
     elsewhere  the file WAS worked on, but under another stage (e.g. went straight to FIN1, so FP has no work)
     norecord   nothing recorded anywhere, and the article is already delivered / at client review / completed
     waiting    nothing recorded anywhere and the article is still open: genuinely waiting for a first pickup */
function ff_finish_states(&$shaped)
{
    $need = array();
    foreach ($shaped as $x) { if (!$x['picked_up']) { $need[$x['id']] = 1; } }
    $others = array();
    foreach (array_chunk(array_keys($need), 1500) as $chunk) {
        $ids = implode(',', array_map('intval', $chunk));
        $res = q("SELECT project_id, stage FROM (SELECT project_id, stage FROM workflow.inw_transactions WHERE project_id IN ($ids)
                  UNION ALL SELECT project_id, stage FROM workflow_archive.inw_transactions WHERE project_id IN ($ids)) t
                  WHERE stage <> '' GROUP BY project_id, stage ORDER BY stage");
        while ($r = $res->fetch_assoc()) { $others[(int)$r['project_id']][] = $r['stage']; }
    }
    foreach ($shaped as $i => $x) {
        $shaped[$i]['other_stages'] = array();
        if ($x['picked_up']) { $shaped[$i]['state'] = 'picked'; continue; }
        $mine = array();
        if (isset($others[$x['id']])) { foreach ($others[$x['id']] as $st) { if ($st !== $x['stage']) { $mine[] = $st; } } }
        if ($mine) { $shaped[$i]['state'] = 'elsewhere'; $shaped[$i]['other_stages'] = $mine; continue; }
        $shaped[$i]['state'] = preg_match('/^(client review|deliver|completed)/i', (string)$x['current_status']) ? 'norecord' : 'waiting';
    }
}

function ff_customer_names()
{
    $m = array();
    $res = q('SELECT id, cust_name FROM adm_customer_master');
    while ($r = $res->fetch_assoc()) { $m[(int)$r['id']] = $r['cust_name']; }
    return $m;
}

/* ---------------------------------------------------------------- list / export */
function ff_list($f, $page, $per, $sort, $dir, $search, $no_limit = false)
{
    global $db;
    $cycles = ff_cycles_sql($f);
    $where = '';
    if ($search !== '') {
        $s = $db->real_escape_string(addcslashes($search, '%_\\'));
        $where = " WHERE (ref LIKE '%$s%' OR title LIKE '%$s%' OR j_code LIKE '%$s%' OR j_name LIKE '%$s%')";
    }
    $cols = array('received' => 'received', 'due' => 'due', 'ref' => 'ref', 'journal' => 'j_code', 'stage' => 'rc', 'pages' => 'pages', 'logged' => 'created_ts');
    $dirSql = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';
    $order = isset($cols[$sort]) ? $cols[$sort] . " $dirSql, id DESC, rc ASC" : 'received DESC, id DESC, rc ASC';
    $limit = $no_limit ? '' : ' LIMIT ' . (($page - 1) * $per) . ',' . $per;
    $res = q("SELECT SQL_CALC_FOUND_ROWS * FROM ($cycles) t $where ORDER BY $order $limit");
    $rows = array();
    while ($r = $res->fetch_assoc()) { $rows[] = $r; }
    $total = (int)q('SELECT FOUND_ROWS() AS n')->fetch_assoc()['n'];
    return array($rows, $total);
}

/* =============================================================== ROUTER */
if ($action === 'meta') {
    $customers = array();
    $cond = $allowed === null ? '' : ' WHERE id IN (' . implode(',', $allowed ?: array(0)) . ')';
    $res = q("SELECT id, cust_name FROM adm_customer_master $cond ORDER BY cust_name ASC");
    while ($r = $res->fetch_assoc()) { $customers[] = array('id' => (int)$r['id'], 'name' => tx($r['cust_name'])); }
    $platforms = array();
    $res = q("SELECT DISTINCT j_platform FROM adm_journals WHERE j_platform IS NOT NULL AND j_platform <> '' ORDER BY j_platform");
    while ($r = $res->fetch_assoc()) { $platforms[] = $r['j_platform']; }
    $stages = array();
    foreach (array('FP' => 'First proof', 'REV' => 'Revisions', 'PMC' => 'PMC', 'FIN' => 'Finals', 'JSTAGE' => 'JSTAGE') as $k => $n) {
        if (!rs_row_enabled($k)) { continue; }               // only the stages in $report_stage_types
        $stages[] = array('key' => $k, 'name' => $n, 'columns' => array_map(function ($c) { return array('key' => $c['key'], 'name' => $c['name']); }, ff_columns($k)));
    }
    api_out(array('ok' => true, 'action' => 'meta', 'user' => array('id' => (int)$ur['id'], 'name' => $ur['full_name'], 'level' => (int)$ur['user_level']),
                  'customers' => $customers, 'platforms' => $platforms, 'stages' => $stages, 'all_customers_allowed' => $allowed === null, 'today' => date('Y-m-d'),
                  'default_range' => array('from' => date('Y-m-d', strtotime('-29 days')), 'to' => date('Y-m-d'))));
}

if ($action === 'journals') {
    $custs = resolve_customers();
    $cond = $custs === null ? '' : ' WHERE j_cust_id IN (' . implode(',', array_map('intval', $custs)) . ')';
    $out = array();
    $res = q("SELECT j_id, j_code, j_name FROM adm_journals $cond ORDER BY j_code ASC");
    while ($r = $res->fetch_assoc()) { $out[] = array('id' => (int)$r['j_id'], 'code' => tx($r['j_code']), 'name' => tx($r['j_name'])); }
    api_out(array('ok' => true, 'action' => 'journals', 'journals' => $out));
}

if ($action === 'summary') {
    $f = ff_filters();
    /* every row of the period, shaped exactly like the table rows, then totalled - so the cards can never disagree with the table */
    list($rows, $total) = ff_list($f, 1, 100000, '', 'desc', '', true);
    $names = array();
    $n = 0; $still = 0; $arch = 0; $waiting = 0; $elsewhere = 0; $norecord = 0; $inProg = 0; $waitSum = 0; $waitN = 0; $flowSum = 0; $flowN = 0;
    foreach (array_chunk($rows, 1500) as $chunk) {
        $times = ff_times($chunk, $f['stage']);
        $shaped = array();
        foreach ($chunk as $r) { $shaped[] = ff_shape($r, $times, $f['stage'], $names); }
        ff_finish_states($shaped);
        foreach ($shaped as $k => $x) {
            $r = $chunk[$k]; $n++;
            if (strpos((string)$r['cur_stage'], $f['stage']) !== false) { $still++; }
            if ($r['src'] === 'Archive') { $arch++; }
            if ($x['state'] === 'waiting') { $waiting++; }
            elseif ($x['state'] === 'elsewhere') { $elsewhere++; }
            elseif ($x['state'] === 'norecord') { $norecord++; }
            elseif ($x['in_progress']) { $inProg++; }
            if ($x['wait_min'] !== null) { $waitSum += $x['wait_min']; $waitN++; }
            if ($x['flow_min'] !== null) { $flowSum += $x['flow_min']; $flowN++; }
        }
    }
    api_out(array('ok' => true, 'action' => 'summary', 'params' => array('stage' => $f['stage'], 'from' => $f['from'], 'to' => $f['to'], 'current_only' => $f['current_only']),
                  'total' => $n, 'still_in_stage' => $still, 'archived' => $arch, 'not_picked_up' => $waiting, 'worked_elsewhere' => $elsewhere, 'no_work_recorded' => $norecord, 'in_progress' => $inProg,
                  'avg_wait_min' => $waitN ? (int)round($waitSum / $waitN) : null, 'avg_flow_min' => $flowN ? (int)round($flowSum / $flowN) : null,
                  'ms' => round((microtime(true) - $t_start) * 1000), 'queries' => $q_count));
}

/* ---------------------------------------------------------------- rows behind a summary card
   Uses the very same shaping as the summary, so a card and the table it opens can never disagree.
     waiting   no department has picked the cycle up yet
     progress  a department is working on it right now
     wait      picked up - longest wait before pickup first
     flow      finished  - longest pickup-to-finish first                                              */
function ff_focused($f, $search, $sort, $dir, $names)
{
    list($rows, $total) = ff_list($f, 1, 100000, $sort, $dir, $search, true);
    $out = array();
    foreach (array_chunk($rows, 1500) as $chunk) {
        $times = ff_times($chunk, $f['stage']);
        $shaped = array();
        foreach ($chunk as $r) { $shaped[] = ff_shape($r, $times, $f['stage'], $names); }
        ff_finish_states($shaped);
        foreach ($shaped as $x) {
            $ok = ($f['focus'] === 'waiting' && $x['state'] === 'waiting')
               || ($f['focus'] === 'elsewhere' && $x['state'] === 'elsewhere')
               || ($f['focus'] === 'progress' && $x['state'] === 'picked' && $x['in_progress'])
               || ($f['focus'] === 'wait' && $x['wait_min'] !== null)
               || ($f['focus'] === 'flow' && $x['flow_min'] !== null);
            if ($ok) { $out[] = $x; }
        }
    }
    if ($f['focus'] === 'wait' || $f['focus'] === 'flow') {
        $k = $f['focus'] === 'wait' ? 'wait_min' : 'flow_min';
        usort($out, function ($a, $b) use ($k) { return $b[$k] <=> $a[$k]; });
    }
    return $out;
}

if ($action === 'list' || $action === 'export') {
    $f = ff_filters();
    $search = isset($_REQUEST['q']) && !is_array($_REQUEST['q']) ? trim((string)$_REQUEST['q']) : '';
    if (strlen($search) > 60) { api_fail(400, 'Search text too long.'); }
    $sort = isset($_REQUEST['sort']) && !is_array($_REQUEST['sort']) ? (string)$_REQUEST['sort'] : '';
    $dir = isset($_REQUEST['dir']) && !is_array($_REQUEST['dir']) ? (string)$_REQUEST['dir'] : 'desc';
    $names = ff_customer_names();
    $params = array('stage' => $f['stage'], 'from' => $f['from'], 'to' => $f['to'], 'cust_id' => $f['custs'] === null ? 'all' : $f['custs'], 'current_only' => $f['current_only'], 'focus' => $f['focus']);
    $columns = array_map(function ($c) { return array('key' => $c['key'], 'name' => $c['name']); }, ff_columns($f['stage']));

    if ($action === 'list') {
        $page = max(1, (int)(isset($_REQUEST['page']) ? $_REQUEST['page'] : 1));
        $per = min(200, max(1, (int)(isset($_REQUEST['per_page']) ? $_REQUEST['per_page'] : 50)));
        if ($f['focus'] !== '') {
            $all = ff_focused($f, $search, $sort, $dir, $names);
            $total = count($all);
            $out = array_slice($all, ($page - 1) * $per, $per);
        } else {
            list($rows, $total) = ff_list($f, $page, $per, $sort, $dir, $search);
            $times = ff_times($rows, $f['stage']);
            $out = array();
            foreach ($rows as $r) { $out[] = ff_shape($r, $times, $f['stage'], $names); }
            ff_finish_states($out);
        }
        api_out(array('ok' => true, 'action' => 'list', 'params' => $params, 'columns' => $columns, 'total' => $total, 'page' => $page, 'per_page' => $per,
                      'pages' => (int)ceil($total / $per), 'rows' => $out, 'ms' => round((microtime(true) - $t_start) * 1000), 'queries' => $q_count));
    }

    if ($f['focus'] !== '') { $shapedAll = ff_focused($f, $search, $sort, $dir, $names); $rows = array(); }
    else { list($rows, $total) = ff_list($f, 1, 100000, $sort, $dir, $search, true); $shapedAll = null; }
    while (ob_get_level() > 0) { ob_end_clean(); }
    $name = 'file_flow_' . $f['stage'] . '_' . $f['from'] . '_to_' . $f['to'] . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Cache-Control: private, no-store');
    $o = fopen('php://output', 'w');
    fwrite($o, "\xEF\xBB\xBF");
    $head = array('Source', 'Manuscript', 'Title', 'Customer', 'Journal', 'Stage', 'Received', 'Due', 'Logged at', 'Pages', 'Now at', 'Current status');
    foreach ($columns as $c) { $head[] = $c['name'] . ' - first start'; $head[] = $c['name'] . ' - last end'; }
    $head[] = 'Timing in this stage'; $head[] = 'First pickup'; $head[] = 'Wait before pickup (min)'; $head[] = 'Last finished'; $head[] = 'Pickup to finish (min)';
    fputcsv($o, $head);
    $emit = $shapedAll !== null ? array($shapedAll) : array_chunk($rows, 1500);
    foreach ($emit as $chunk) {
        $times = $shapedAll !== null ? array() : ff_times($chunk, $f['stage']);
        $sc = array();
        foreach ($chunk as $r) { $sc[] = $shapedAll !== null ? $r : ff_shape($r, $times, $f['stage'], $names); }
        if ($shapedAll === null) { ff_finish_states($sc); }
        foreach ($sc as $s) {
            $line = array($s['source'], $s['ref'], $s['title'], $s['customer'], $s['journal_code'], $s['stage'], $s['received'], $s['due'], $s['logged_at'], $s['pages'], $s['current_stage'], $s['current_status']);
            foreach ($columns as $c) { $d = isset($s['depts'][$c['key']]) ? $s['depts'][$c['key']] : null; $line[] = $d ? $d['start'] : ''; $line[] = $d ? ($d['open'] ? 'in progress' : $d['end']) : ''; }
            $lab = array('picked' => 'recorded', 'waiting' => 'waiting for pickup', 'elsewhere' => 'worked in ' . implode('/', $s['other_stages']), 'norecord' => 'no work recorded');
            $line[] = isset($lab[$s['state']]) ? $lab[$s['state']] : '';
            $line[] = $s['first_start']; $line[] = $s['wait_min']; $line[] = $s['last_end']; $line[] = $s['flow_min'];
            fputcsv($o, $line);
        }
    }
    fclose($o);
    exit;
}

if ($action === 'trace') {
    $id = isset($_REQUEST['id']) && !is_array($_REQUEST['id']) ? trim((string)$_REQUEST['id']) : '';
    if (!ctype_digit($id) || $id === '' || strlen($id) > 12) { api_fail(400, 'Invalid manuscript.'); }
    $id = (int)$id;
    $art = null; $src = null;
    foreach (array('Live' => 'workflow', 'Archive' => 'workflow_archive') as $label => $S) {
        $r = q("SELECT wd.id, wd.pub_id, wd.article_title, wd.cust_id, wd.stage, wd.status, wd.recv_dt, wd.due_dt, wd.created_dt, wd.user_working_status, j.j_code, j.j_name
                FROM $S.inw_inward_dtl wd LEFT JOIN $S.adm_journals j ON j.j_id = wd.j_id WHERE wd.id = $id LIMIT 1")->fetch_assoc();
        if ($r) { $art = $r; $src = $label; break; }
    }
    if (!$art) { api_fail(404, 'Manuscript not found.'); }
    if ($allowed !== null && !in_array((int)$art['cust_id'], $allowed, true)) { api_fail(403, 'You do not have access to this customer.'); }

    $depts = array();
    $res = q('SELECT dept_code, dept_name FROM adm_dept_master');
    while ($r = $res->fetch_assoc()) { $depts[(int)$r['dept_code']] = tx($r['dept_name']); }
    $steps = array();
    $res = q("SELECT stage, dept, current_status, process_user, start_time, end_time, pages_completed, comments FROM (
                  SELECT id, stage, dept, current_status, process_user, start_time, end_time, pages_completed, comments FROM workflow.inw_transactions WHERE project_id = $id
                  UNION ALL
                  SELECT id, stage, dept, current_status, process_user, start_time, end_time, pages_completed, comments FROM workflow_archive.inw_transactions WHERE project_id = $id
              ) t ORDER BY start_time ASC, id ASC");
    $uids = array();
    while ($r = $res->fetch_assoc()) { $steps[] = $r; if (ctype_digit((string)$r['process_user'])) { $uids[(int)$r['process_user']] = 1; } }
    $users = array();
    if ($uids) {
        $res = q('SELECT id, full_name FROM users WHERE id IN (' . implode(',', array_keys($uids)) . ')');
        while ($r = $res->fetch_assoc()) { $users[(int)$r['id']] = $r['full_name']; }
    }
    $rounds = array();
    foreach (array('workflow', 'workflow_archive') as $S) {
        $res = q("SELECT type, revision_count, received_date, due_date FROM $S.inw_revisions_dtl WHERE inw_id = $id ORDER BY r_id ASC");
        while ($r = $res->fetch_assoc()) { $k = $r['type'] . $r['revision_count']; if (!isset($rounds[$k])) { $rounds[$k] = array('stage' => $k, 'received' => $r['received_date'], 'due' => $r['due_date']); } }
    }
    $groups = array(); $prevEnd = null; $order = array();
    foreach ($steps as $s) {
        $st = (string)$s['stage'] === '' ? '-' : $s['stage'];
        if (!isset($groups[$st])) { $groups[$st] = array('stage' => $st, 'steps' => array(), 'first' => $s['start_time'], 'last' => null, 'open' => false); $order[] = $st; }
        $gap = null;
        if (!empty($groups[$st]['steps']) && $prevEnd !== null && $s['start_time'] >= $prevEnd) { $gap = (int)round((strtotime($s['start_time']) - strtotime($prevEnd)) / 60); }
        $dur = ($s['end_time'] && $s['end_time'] >= $s['start_time']) ? (int)round((strtotime($s['end_time']) - strtotime($s['start_time'])) / 60) : null;
        $groups[$st]['steps'][] = array(
            'dept' => isset($depts[(int)$s['dept']]) ? $depts[(int)$s['dept']] : 'Dept ' . (int)$s['dept'], 'status' => tx($s['current_status']),
            'user' => ctype_digit((string)$s['process_user']) && isset($users[(int)$s['process_user']]) ? tx($users[(int)$s['process_user']]) : null,
            'start' => $s['start_time'], 'end' => $s['end_time'], 'minutes' => $dur, 'wait_min' => $gap,
            'pages' => ($s['pages_completed'] === null || $s['pages_completed'] === '') ? null : $s['pages_completed'],
            'comment' => $s['comments'] ? mb_substr(trim(strip_tags(tx($s['comments']))), 0, 240) : null);
        if ($s['end_time'] === null) { $groups[$st]['open'] = true; } elseif ($groups[$st]['last'] === null || $s['end_time'] > $groups[$st]['last']) { $groups[$st]['last'] = $s['end_time']; }
        if ($s['end_time'] !== null) { $prevEnd = $s['end_time']; }
    }
    $stages = array();
    foreach ($order as $st) { $g = $groups[$st]; if (isset($rounds[$st])) { $g['received'] = $rounds[$st]['received']; $g['due'] = $rounds[$st]['due']; } $stages[] = $g; }
    $names = ff_customer_names();
    api_out(array('ok' => true, 'action' => 'trace',
        'article' => array('id' => (int)$art['id'], 'source' => $src, 'ref' => tx($art['pub_id']), 'title' => tx($art['article_title']), 'customer' => isset($names[(int)$art['cust_id']]) ? tx($names[(int)$art['cust_id']]) : null,
                           'journal_code' => tx($art['j_code']), 'journal_name' => tx($art['j_name']), 'current_stage' => tx($art['stage']), 'current_status' => tx($art['status']),
                           'received' => $art['recv_dt'], 'due' => $art['due_dt'], 'logged_at' => $art['created_dt'], 'working_parts' => working_parts($art['user_working_status']),
                           'links' => array('job_card' => 'jobcard.php?id=' . (int)$art['id'], 'send_instructions' => 'send_instructions.php?id=' . (int)$art['id'])),
        'rounds' => array_values($rounds), 'stages' => $stages, 'steps_total' => count($steps), 'ms' => round((microtime(true) - $t_start) * 1000), 'queries' => $q_count));
}

api_fail(400, 'Unknown action.');
