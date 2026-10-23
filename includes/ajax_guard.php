<?php
/* ============================================================================
 * Shared guard for the report AJAX partials (artilce_*.php).
 *
 *   include('includes/ajax_guard.php');           // login check only
 *
 *   $ag_validate = true;                          // + clean up the request values
 *   include('includes/ajax_guard.php');
 *
 * 1. Login: the same token-cookie check the pages use (authCookieSessionValidate.php).
 *    Anyone not signed in gets HTTP 401 and no data.
 * 2. Validation (optional): cust_id / stage / dates / start are checked and rewritten in
 *    $_REQUEST, $_POST and $_GET before the partial builds any SQL from them.  A malformed
 *    value is rejected with HTTP 400 instead of being passed on.
 * ========================================================================== */

if (!function_exists('ag_deny')) {
    function ag_deny($code, $msg)
    {
        while (ob_get_level() > 0) { ob_end_clean(); }
        http_response_code($code);
        if (!empty($GLOBALS['ag_json'])) {                       // JSON callers (api/*.php)
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array('ok' => false, 'error' => $msg, 'status' => $code));
            exit;
        }
        echo '<div class="mu-empty" style="padding:26px;text-align:center;color:#6b7785;">' . htmlspecialchars($msg) . '</div>';
        exit;
    }
}

/* ---- 1. login ---- */
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Util.php';
$auth = new Auth();
$util = new Util();
require __DIR__ . '/authCookieSessionValidate.php';      // sets $isLoggedIn
if (empty($isLoggedIn) || empty($_COOKIE['user_id'])) {
    ag_deny(401, 'Your session has expired. Please sign in again.');
}

/* ---- 2. request validation ---- */
if (!empty($ag_validate)) {
    $ag_fix = function ($key, $fn) {
        foreach (array('_REQUEST', '_POST', '_GET') as $bag) {
            if (isset($GLOBALS[$bag][$key])) {
                $v = $GLOBALS[$bag][$key];
                if (is_array($v)) { ag_deny(400, 'Invalid request.'); }
                $GLOBALS[$bag][$key] = $fn(trim((string)$v));
            }
        }
    };
    $ag_date = function ($v) {
        if ($v === '') { return ''; }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $v, $m) && checkdate((int)$m[2], (int)$m[3], (int)$m[1])) { return $v; }
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $v, $m) && checkdate((int)$m[2], (int)$m[1], (int)$m[3])) { return $m[3] . '-' . $m[2] . '-' . $m[1]; }
        ag_deny(400, 'Invalid date.');
    };
    $ag_fix('cust_id', function ($v) { if ($v === '' || $v === 'all' || ctype_digit($v)) { return $v; } ag_deny(400, 'Invalid customer.'); });
    $ag_fix('stage',   function ($v) { if (preg_match('/^[A-Za-z0-9_]{0,20}$/', $v)) { return $v; } ag_deny(400, 'Invalid stage.'); });
    $ag_fix('dt',      $ag_date);
    $ag_fix('sdt',     $ag_date);
    $ag_fix('start',   function ($v) { return (string)max(0, (int)$v); });
    /* values that are only echoed into links / hidden fields: keep plain characters only */
    foreach (array('radio', 'j_id', 'stage_id', 'dep_id', 'dp_platform', 'date_wise', 'from_dt', 'to_dt', 'mfrom_dt', 'yfrom_dt') as $k) {
        $ag_fix($k, function ($v) { return preg_replace('/[^A-Za-z0-9_\-:., ]/', '', $v); });
    }
}
