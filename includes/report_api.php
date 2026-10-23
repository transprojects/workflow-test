<?php
/* ============================================================================
 * Shared plumbing for the report JSON APIs (api/wip.php).
 * (api/inflow.php keeps its own copy of these helpers; they can be merged later.)
 *
 *   - sign-in check via includes/ajax_guard.php (JSON 401)
 *   - the calling user and the customers they may see come from the database, not from cookies
 *   - small helpers: JSON output, query counter, date / range / customer parsing
 * ========================================================================== */

$ag_json = true;
require_once __DIR__ . '/ajax_guard.php';                 // 401 JSON when not signed in; defines $userToken
include_once __DIR__ . '/../dbc.php';                      // $db (mysqli)

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

/* the tables store some text with HTML entities already encoded ("Sciences &amp; ..."); return plain text */
function tx($v) { return $v === null ? null : html_entity_decode((string)$v, ENT_QUOTES | ENT_HTML5, 'UTF-8'); }

/* user_working_status is stored as ready-made HTML (<span class="label label-sm label-success">PAG</span>|...).
   Return it as plain data [{t:"PAG", k:"success"}, ...]; stored markup is never passed on. */
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

/* ---------------------------------------------------------------- who is calling */
$username = isset($userToken[0]['username']) ? $userToken[0]['username'] : '';
$ue = $db->real_escape_string($username);
$ur = q("SELECT id, full_name, user_level, cust_id, access_other_cust_id, approved, banned FROM users WHERE user_name = '$ue' LIMIT 1")->fetch_assoc();
if (!$ur || (string)$ur['banned'] === '1' || (string)$ur['approved'] === '0') { api_fail(403, 'This account cannot use the report.'); }

/* customers this user may see: everyone for level 1, otherwise their own + "other" customers (as the report drop-downs do) */
$allowed = null;                                           // null = no restriction
if ((string)$ur['user_level'] !== '1') {
    $ids = array();
    foreach (array_merge(array($ur['cust_id']), explode(',', (string)$ur['access_other_cust_id'])) as $x) {
        $x = trim($x);
        if ($x !== '' && ctype_digit($x)) { $ids[(int)$x] = (int)$x; }
    }
    $allowed = array_values($ids);
}

/* ---------------------------------------------------------------- request parsing */
function req_date($key, $default = null)
{
    if (!isset($_REQUEST[$key]) || is_array($_REQUEST[$key]) || $_REQUEST[$key] === '') { return $default; }
    $v = trim((string)$_REQUEST[$key]);
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $v, $m) && checkdate((int)$m[2], (int)$m[3], (int)$m[1])) { return $v; }
    if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $v, $m) && checkdate((int)$m[2], (int)$m[1], (int)$m[3])) { return $m[3] . '-' . $m[2] . '-' . $m[1]; }
    api_fail(400, 'Invalid date: ' . $key);
}

/* month=2026-06 | 06-2026  -> whole month;  date=  -> one day;  from= & to=  -> a range (max 400 days).
   $cap_to_today: the running month stops at today (inflow) - WIP looks ahead, so it keeps the whole month. */
function resolve_range($default_from, $default_to, $cap_to_today = false)
{
    $today = date('Y-m-d');
    if (!empty($_REQUEST['month']) && !is_array($_REQUEST['month'])) {
        $mm = trim((string)$_REQUEST['month']);
        if (preg_match('/^(\d{4})-(\d{2})$/', $mm, $m)) { $y = (int)$m[1]; $mo = (int)$m[2]; }
        elseif (preg_match('/^(\d{2})-(\d{4})$/', $mm, $m)) { $y = (int)$m[2]; $mo = (int)$m[1]; }
        else { api_fail(400, 'Invalid month.'); }
        if ($mo < 1 || $mo > 12) { api_fail(400, 'Invalid month.'); }
        $from = sprintf('%04d-%02d-01', $y, $mo);
        $to = date('Y-m-t', strtotime($from));
        if ($cap_to_today && substr($from, 0, 7) === date('Y-m')) { $to = $today; }
        return array($from, $to);
    }
    $d = req_date('date');
    if ($d) { return array($d, $d); }
    $from = req_date('from', $default_from);
    $to = req_date('to', $default_to);
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

/* inw_inward_dtl.cust_id is varchar: compare with strings so the index can be used */
function cust_sql_str($alias, $custs)
{
    if ($custs === null) { return ''; }
    return " AND $alias.cust_id IN ('" . implode("','", array_map('intval', $custs)) . "')";
}
function cust_sql_int($alias, $custs)
{
    if ($custs === null) { return ''; }
    return " AND $alias.cust_id IN (" . implode(',', array_map('intval', $custs)) . ")";
}
