<?php
/* Which Start Work page does this user get, and why?  Open /sw_route_check.php while logged in (Portal Admin only). Read-only: it reads start_work.php, it changes nothing. */
$uid = isset($_COOKIE['user_id']) ? (string)$_COOKIE['user_id'] : '';
if ($uid === '' || !isset($_COOKIE['user_level']) || (string)$_COOKIE['user_level'] !== '1') { echo 'Please log in as Portal Admin first.'; exit; }
$dir = __DIR__;
$gate = is_file($dir . '/start_work.php') ? file_get_contents($dir . '/start_work.php') : '';
function sw_arr($src, $name) { return preg_match('/\$' . $name . '\s*=\s*array\(([^)]*)\)/', $src, $m) ? preg_split('/[\s,\'"]+/', trim($m[1], " \t\r\n,'\""), -1, PREG_SPLIT_NO_EMPTY) : null; }
function sw_flag($src, $name) { return preg_match('/\$' . $name . '\s*=\s*(true|false)\s*;/', $src, $m) ? $m[1] : null; }
$v3_users = sw_arr($gate, 'SW_V3_PILOT_USERS'); $v3_all = sw_flag($gate, 'SW_V3_ALL');
$v2_users = sw_arr($gate, 'SW_V2_PILOT_USERS'); $v2_all = sw_flag($gate, 'SW_V2_ALL');
$has_gate_v3 = strpos($gate, 'start_work_v3') !== false;
$files = array('start_work.php', 'start_work_v2.php', 'start_work_v3.php', 'start_work_v2.disabled', 'start_work_v3.disabled');
$rows = array();
foreach ($files as $f) { $p = $dir . '/' . $f; $rows[] = array($f, is_file($p) ? 'present, modified ' . date('d-M-Y H:i', filemtime($p)) . ', ' . number_format(filesize($p)) . ' bytes' : 'not present'); }
$in_v3 = $v3_users !== null && in_array($uid, $v3_users, true);
$v3_ok = is_file($dir . '/start_work_v3.php') && !is_file($dir . '/start_work_v3.disabled');
if (!$has_gate_v3) { $target = 'start_work_v2.php'; $why = 'the start_work.php on this server has no v3 routing (it is an older copy - upload the updated start_work.php)'; }
elseif (!$v3_ok) { $target = 'start_work_v2.php'; $why = is_file($dir . '/start_work_v3.disabled') ? 'start_work_v3.disabled exists (the off switch)' : 'start_work_v3.php is not on this server'; }
elseif ($v3_all === 'true' || $in_v3) { $target = 'start_work_v3.php'; $why = $v3_all === 'true' ? 'v3 is on for everybody' : 'your user id is in the v3 pilot list'; }
else { $target = 'start_work_v2.php'; $why = 'your user id (' . $uid . ') is not in the v3 pilot list'; }
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?><!doctype html><meta charset="utf-8"><title>Start Work route check</title>
<body style="font:14px/1.5 Arial,sans-serif;margin:24px;color:#22303f;max-width:760px">
<h2 style="margin:0 0 8px">Start Work route check</h2>
<p>You are user id <b><?php echo h($uid); ?></b> (<?php echo h(isset($_COOKIE['full_name']) ? $_COOKIE['full_name'] : ''); ?>).</p>
<p style="font-size:18px">Start Work opens: <b style="color:<?php echo $target === 'start_work_v3.php' ? '#2e8b57' : '#c0392b'; ?>"><?php echo h($target); ?></b><br><span style="font-size:13px;color:#5b6b7b">because <?php echo h($why); ?></span></p>
<table cellpadding="5" style="border-collapse:collapse;font-size:13px">
<?php foreach ($rows as $r) { ?><tr style="border-top:1px solid #e4e9ef"><td><b><?php echo h($r[0]); ?></b></td><td><?php echo h($r[1]); ?></td></tr><?php } ?>
<tr style="border-top:1px solid #e4e9ef"><td><b>v3 pilot users (start_work.php)</b></td><td><?php echo $v3_users === null ? 'not found in start_work.php' : h(implode(', ', $v3_users)); ?></td></tr>
<tr style="border-top:1px solid #e4e9ef"><td><b>v3 for everybody</b></td><td><?php echo h($v3_all === null ? 'not found in start_work.php' : $v3_all); ?></td></tr>
<tr style="border-top:1px solid #e4e9ef"><td><b>v2 for everybody / pilot</b></td><td><?php echo h(($v2_all === null ? '?' : $v2_all) . ' / ' . ($v2_users === null ? '?' : implode(', ', $v2_users))); ?></td></tr>
</table>
<p style="font-size:12px;color:#8a97ab;margin-top:14px">Also check that you open Start Work from Current Work (link <code>startwork/&lt;id&gt;/&lt;tid&gt;</code>) - that link goes through start_work.php. The badge next to "Article Details" on the page says which version you got.</p>
</body>
