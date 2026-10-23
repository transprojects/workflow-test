<?php
require("dbc.php");

/* ============================================================================
 * TVL Workflow - employee live status.
 *
 * Who is logged in today, who is working on which file right now, who is logged in but idle,
 * and what each person has done today.  Same sources as before (users, wf_token_auth,
 * inw_transactions, inw_inward_dtl) but read with a handful of queries for the whole list
 * (the old page ran about nine per employee).  The page shows the first data at once and
 * refreshes itself from  user.php?format=json  - no full reload.
 *
 *   user.php                    -> the page
 *   user.php?dep_id=10          -> one department   (dep_id=all -> everybody)
 *   user.php?format=json&...    -> the same data as JSON
 * ========================================================================== */

function ws_clock($ts) { return $ts ? date('H:i', $ts) : ''; }

/* returns array('employees' => [...], 'summary' => [...], 'generated' => 'H:i:s') */
function ws_collect($db, $dep)
{
	$now      = time();
	$today    = date('Y-m-d');
	$tomorrow = date('Y-m-d', strtotime('+1 day'));
	$out      = array('employees' => array(), 'summary' => array(), 'generated' => date('H:i:s'), 'now' => $now);
	if ($dep === '') { return $out; }

	/* ---- employees ---- */
	$where = ($dep === 'all') ? '' : " AND u.department_id = '".(int)$dep."'";
	$users = array(); $names = array();
	$q = $db->query("SELECT u.id, u.full_name, u.user_name, u.user_email, u.profile_pic, u.department_id, dm.dept_name
	                 FROM users u LEFT JOIN adm_dept_master dm ON dm.dept_code = u.department_id
	                 WHERE u.approved = 1 AND (u.worklocation = 'TVL' OR u.worklocation = 'CHN')".$where."
	                 ORDER BY dm.dept_code, u.full_name ASC");
	while ($q && ($r = $q->fetch_assoc())) { $users[(int)$r['id']] = $r; $names[] = $db->real_escape_string($r['user_name']); }
	if (!$users) { return $out; }
	$ids = implode(',', array_keys($users));
	$inq = "'".implode("','", array_keys($users))."'";

	/* ---- today's logins ---- */
	$login = array();
	if ($names) {
		$q = $db->query("SELECT username, COUNT(*) AS n, MAX(start_date) AS last_in, MIN(start_date) AS first_in
		                 FROM wf_token_auth WHERE start_date >= '".$today."' AND start_date < '".$tomorrow."' AND username IN ('".implode("','", $names)."')
		                 GROUP BY username");
		while ($q && ($r = $q->fetch_assoc())) { $login[$r['username']] = $r; }
	}

	/* ---- today's transactions of all of them, once ---- */
	$tx = array();
	$q = $db->query("SELECT t.id, t.process_user, t.project_id, t.current_status, t.start_time, t.end_time,
	                        CASE WHEN t.pages_completed IS NOT NULL AND t.pages_completed != 0 THEN t.pages_completed
	                             WHEN wd.page_count IS NOT NULL AND wd.page_count != 0 THEN wd.page_count
	                             ELSE wd.manuscript_count END AS pg
	                 FROM inw_transactions t JOIN inw_inward_dtl wd ON wd.id = t.project_id
	                 WHERE t.process_user IN (".$inq.") AND t.created_dt >= '".$today."' AND t.created_dt < '".$tomorrow."'
	                 ORDER BY t.id ASC");
	while ($q && ($r = $q->fetch_assoc())) { $tx[(int)$r['process_user']][] = $r; }

	/* ---- the file each person touched last ---- */
	$cur_pid = array();
	foreach ($tx as $uid => $rows) { $cur_pid[$uid] = (int)$rows[count($rows) - 1]['project_id']; }
	$art = array(); $open = array();
	if ($cur_pid) {
		$pids = implode(',', array_unique($cur_pid));
		$q = $db->query("SELECT wd.id, wd.pub_id, wd.stage, wd.status, wd.highpriority, wd.due_dt, wd.target_due_dt, c.cust_name, j.j_code,
		                        (SELECT rr.due_date FROM inw_revisions_dtl rr WHERE rr.inw_id = wd.id ORDER BY rr.r_id DESC LIMIT 1) AS rev_due
		                 FROM inw_inward_dtl wd
		                 LEFT JOIN adm_customer_master c ON c.id = wd.cust_id
		                 LEFT JOIN adm_journals j ON j.j_id = wd.j_id
		                 WHERE wd.id IN (".$pids.")");
		while ($q && ($r = $q->fetch_assoc())) { $art[(int)$r['id']] = $r; }

		/* is that file still open for the person?  (latest not-completed transaction of the same stage, unless their last one on it is Completed) */
		$q = $db->query("SELECT id, process_user, project_id, current_status, start_time, stage FROM inw_transactions
		                 WHERE project_id IN (".$pids.") AND process_user IN (".$inq.") ORDER BY id ASC");
		$by = array();
		while ($q && ($r = $q->fetch_assoc())) { $by[(int)$r['process_user']][(int)$r['project_id']][] = $r; }
		foreach ($cur_pid as $uid => $pid) {
			if (empty($by[$uid][$pid]) || empty($art[$pid])) { continue; }
			$rows = $by[$uid][$pid];
			if ($rows[count($rows) - 1]['current_status'] === 'Completed') { continue; }
			for ($i = count($rows) - 1; $i >= 0; $i--) {
				if (!in_array($rows[$i]['current_status'], array('Completed', 'Take Over'), true) && $rows[$i]['stage'] === $art[$pid]['stage']) { $open[$uid] = $rows[$i]; break; }
			}
		}
	}

	/* ---- build ---- */
	$sum = array('total' => 0, 'working' => 0, 'idle' => 0, 'break' => 0, 'other' => 0, 'offline' => 0, 'files' => 0, 'completed' => 0, 'pages' => 0);
	foreach ($users as $uid => $u) {
		$lg = isset($login[$u['user_name']]) ? $login[$u['user_name']] : null;
		$rows = isset($tx[$uid]) ? $tx[$uid] : array();
		$files = array(); $done = array(); $pages = 0.0; $worked = 0; $last_act = 0;
		$last_i = count($rows) - 1;
		foreach ($rows as $i => $r) {
			$files[$r['project_id']] = true;
			if ($r['current_status'] === 'Completed' && !isset($done[$r['project_id']])) { $done[$r['project_id']] = true; $pages += (float)$r['pg']; }
			$st = strtotime($r['start_time']); $en = $r['end_time'] ? strtotime($r['end_time']) : 0;
			if ($st && $en && $en >= $st) { $worked += $en - $st; $last_act = max($last_act, $en); }
			elseif ($st && !$en && $r['current_status'] === 'Inprogress' && $i === $last_i) { $worked += min(16 * 3600, max(0, $now - $st)); $last_act = max($last_act, $now); }   // only the file open right now runs; older unfinished rows are stale
			elseif ($st) { $last_act = max($last_act, $st); }
		}
		$last_in = $lg ? strtotime($lg['last_in']) : 0;
		$logged  = $lg && (int)$lg['n'] > 0;

		$cur = null; $since = 0; $state = 'offline'; $state_label = 'Not logged in';
		if ($logged) {
			$op = isset($open[$uid]) ? $open[$uid] : null;
			if ($op) {
				$s = $op['current_status'];
				if ($s === 'Inprogress')   { $state = 'working'; $state_label = 'Working'; $since = strtotime($op['start_time']); }
				elseif ($s === 'Break')    { $state = 'break';   $state_label = 'On break'; $since = strtotime($op['start_time']); }
				else                       { $state = 'other';   $state_label = $s;         $since = strtotime($op['start_time']); }
			} else { $state = 'idle'; $state_label = 'Logged in - no file open'; }
		}
		if (isset($cur_pid[$uid]) && isset($art[$cur_pid[$uid]]) && $logged) {
			$a = $art[$cur_pid[$uid]];
			$raw = ($a['stage'] === 'FP') ? ($a['target_due_dt'] ?: $a['due_dt']) : (preg_match('/^ISSCOR[0-9]+$/', $a['stage']) ? $a['due_dt'] : ($a['rev_due'] ?: $a['due_dt']));
			$dts = ($raw && strpos($raw, '0000-00-00') !== 0) ? strtotime($raw) : false;
			$days = $dts ? (int)round((strtotime(date('Y-m-d', $dts)) - strtotime($today)) / 86400) : null;
			$cur = array('pub_id' => $a['pub_id'], 'stage' => $a['stage'], 'status' => $a['status'], 'cust' => $a['cust_name'], 'journal' => $a['j_code'],
			             'high' => ((int)$a['highpriority'] === 1), 'due_days' => $days);
		}
		$sum['total']++; $sum[$state]++; $sum['files'] += count($files); $sum['completed'] += count($done); $sum['pages'] += $pages;

		$out['employees'][] = array(
			'id' => (int)$uid, 'username' => $u['user_name'], 'name' => $u['full_name'], 'email' => $u['user_email'], 'dept' => $u['dept_name'], 'pic' => $u['profile_pic'],
			'state' => $state, 'state_label' => $state_label,
			'logins' => $logged ? (int)$lg['n'] : 0, 'first_in' => $logged ? ws_clock(strtotime($lg['first_in'])) : '', 'last_in' => $logged ? ws_clock($last_in) : '',
			'files' => count($files), 'completed' => count($done), 'pages' => (int)round($pages), 'worked' => (int)$worked,
			'since' => $since, 'since_clock' => $since ? date('H:i', $since) : '',
			'idle_since' => ($state === 'idle') ? max($last_act, $last_in) : 0,
			'cur' => $cur,
		);
	}
	$sum['pages'] = (int)round($sum['pages']);
	$out['summary'] = $sum;
	return $out;
}

/* ---- request ---- */
$dep = isset($_REQUEST['dep_id']) ? trim((string)$_REQUEST['dep_id']) : '';
$dep = ($dep === 'all') ? 'all' : (ctype_digit($dep) && (int)$dep > 0 ? (string)(int)$dep : '');

if (isset($_REQUEST['format']) && $_REQUEST['format'] === 'json') {
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-store');
	echo json_encode(ws_collect($db, $dep), JSON_HEX_TAG | JSON_HEX_AMP | JSON_PARTIAL_OUTPUT_ON_ERROR);
	exit;
}

$data = ws_collect($db, $dep);
$depts = array();
$dq = $db->query("SELECT dept_name, dept_code FROM adm_dept_master WHERE parent_id = 0 ORDER BY dept_name ASC");
while ($dq && ($dr = $dq->fetch_assoc())) { $depts[] = $dr; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>TVL Workflow - Employee live status</title>
<style>
:root{--navy:#14304a;--navy2:#1f4b73;--accent:#e8742a;--blue:#1b6ca8;--ink:#22303f;--mute:#6b7785;--line:#e4e9ef;--bg:#f3f6fa;--ok:#1e9e61;--warn:#d98a06;--bad:#d64545;--info:#2a7fc1;}
*,*:before,*:after{box-sizing:border-box;}
html,body{margin:0;}
body{font-family:"Open Sans","Segoe UI",system-ui,-apple-system,Roboto,Arial,sans-serif;font-size:14px;color:var(--ink);background:var(--bg);}
button,select,input{font-family:inherit;font-size:inherit;}
.ws{max-width:1500px;margin:0 auto;padding:16px clamp(10px,2vw,22px) 40px;}
@keyframes wsUp{from{opacity:0;transform:translateY(12px);}to{opacity:1;transform:none;}}
@keyframes wsPulse{0%{box-shadow:0 0 0 0 rgba(30,158,97,.55);}100%{box-shadow:0 0 0 10px rgba(30,158,97,0);}}
@keyframes wsShine{0%{transform:translateX(-120%) skewX(-20deg);}100%{transform:translateX(320%) skewX(-20deg);}}
@keyframes wsSpin{to{transform:rotate(360deg);}}

/* ---- header ---- */
.ws-hero{position:relative;overflow:hidden;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px 20px;padding:20px 24px;border-radius:16px;color:#fff;
  background:linear-gradient(120deg,#14304a 0%,#1f4b73 60%,#1b6ca8 100%);box-shadow:0 8px 22px rgba(20,48,74,.22);animation:wsUp .45s ease both;}
.ws-hero:before{content:"";position:absolute;top:0;bottom:0;width:26%;left:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,.13),transparent);animation:wsShine 6s ease-in-out infinite;}
.ws-hero>*{position:relative;}
.ws-hero small{display:block;font-size:11px;letter-spacing:1px;text-transform:uppercase;opacity:.75;}
.ws-hero h1{margin:2px 0 0;font-size:24px;font-weight:700;line-height:1.2;}
.ws-hero p{margin:3px 0 0;opacity:.85;}
.ws-hero-r{display:flex;flex-wrap:wrap;align-items:center;gap:10px;}
.ws-live{display:inline-flex;align-items:center;gap:8px;padding:7px 14px;border-radius:20px;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.28);font-weight:700;font-size:12px;}
.ws-live:before{content:"";width:9px;height:9px;border-radius:50%;background:#4ade80;animation:wsPulse 1.6s infinite;}
.ws-live.off:before{background:#cbd5e1;animation:none;}
.ws-btn{display:inline-flex;align-items:center;gap:7px;height:38px;padding:0 15px;border:1px solid rgba(255,255,255,.3);border-radius:10px;background:rgba(255,255,255,.14);color:#fff;font-weight:600;cursor:pointer;transition:background .2s,transform .15s;}
.ws-btn:hover{background:rgba(255,255,255,.26);transform:translateY(-1px);}
.ws-btn.spin svg{animation:wsSpin .8s linear infinite;}
.ws-btn svg{width:16px;height:16px;}

/* ---- toolbar ---- */
.ws-bar{display:flex;flex-wrap:wrap;align-items:center;gap:10px 12px;margin:14px 0;padding:12px 14px;background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:0 2px 10px rgba(20,48,74,.05);animation:wsUp .45s .05s ease both;}
.ws-field{display:flex;flex-direction:column;gap:3px;}
.ws-field label{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--mute);}
.ws select,.ws input[type=search]{height:38px;padding:0 12px;border:1px solid #d5dde6;border-radius:10px;background:#fff;color:var(--ink);outline:0;transition:border-color .2s,box-shadow .2s;}
.ws select:focus,.ws input[type=search]:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(27,108,168,.15);}
.ws input[type=search]{min-width:230px;padding-left:34px;background:#fff url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' stroke='%239aa6b2' stroke-width='2'><circle cx='7' cy='7' r='5'/><path d='M11 11l4 4'/></svg>") no-repeat 11px center;}
.ws-chips{display:flex;flex-wrap:wrap;gap:6px;margin-left:auto;}
.ws-chip{display:inline-flex;align-items:center;gap:7px;height:36px;padding:0 13px;border:1px solid var(--line);border-radius:20px;background:#fff;color:var(--ink);font-weight:600;cursor:pointer;transition:all .2s;}
.ws-chip i{width:9px;height:9px;border-radius:50%;background:var(--c,#6b7785);}
.ws-chip b{min-width:22px;padding:1px 7px;border-radius:20px;background:#eef1f5;color:var(--mute);font-size:12px;text-align:center;}
.ws-chip:hover{transform:translateY(-1px);box-shadow:0 6px 14px rgba(20,48,74,.1);}
.ws-chip.on{background:var(--c,#14304a);border-color:var(--c,#14304a);color:#fff;} .ws-chip.on i{background:#fff;} .ws-chip.on b{background:rgba(255,255,255,.25);color:#fff;}
.ws-chip.active{--c:#1e9e61;} .ws-chip.working{--c:#1e9e61;} .ws-chip.idle{--c:#d64545;} .ws-chip.break{--c:#d98a06;} .ws-chip.other{--c:#2a7fc1;} .ws-chip.offline{--c:#8795a5;} .ws-chip.all{--c:#14304a;}
.ws-seg{display:inline-flex;padding:3px;border-radius:10px;background:#eef2f6;}
.ws-seg button{border:0;background:transparent;padding:7px 13px;border-radius:8px;color:var(--mute);font-weight:600;cursor:pointer;transition:all .2s;}
.ws-seg button.on{background:#fff;color:var(--blue);box-shadow:0 2px 8px rgba(20,48,74,.15);}
.ws-auto{display:inline-flex;align-items:center;gap:8px;color:var(--mute);font-size:12.5px;font-weight:600;cursor:pointer;user-select:none;}
.ws-auto input{accent-color:var(--accent);width:16px;height:16px;}

/* ---- summary ---- */
.ws-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:14px;}
.ws-stat{position:relative;overflow:hidden;padding:14px 16px;background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:0 2px 10px rgba(20,48,74,.05);transition:transform .25s,box-shadow .25s;animation:wsUp .45s ease both;}
.ws-stat:hover{transform:translateY(-4px);box-shadow:0 12px 24px rgba(20,48,74,.12);}
.ws-stat:after{content:"";position:absolute;left:0;bottom:0;height:3px;width:100%;background:var(--c,#1b6ca8);}
.ws-stat span{display:block;color:var(--mute);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;}
.ws-stat b{display:block;font-size:26px;line-height:1.2;color:var(--navy);font-variant-numeric:tabular-nums;}

/* ---- cards ---- */
.ws-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(330px,1fr));gap:14px;}
.ws-card{position:relative;display:flex;flex-direction:column;gap:12px;padding:16px 16px 14px;background:#fff;border:1px solid var(--line);border-top:4px solid var(--c,#8795a5);border-radius:14px;box-shadow:0 2px 10px rgba(20,48,74,.06);
  transition:transform .25s,box-shadow .25s;animation:wsUp .4s ease both;}
.ws-card:hover{transform:translateY(-4px);box-shadow:0 14px 28px rgba(20,48,74,.14);}
.ws-card.working{--c:#1e9e61;} .ws-card.idle{--c:#d64545;background:linear-gradient(180deg,#fff7f7,#fff 90px);} .ws-card.break{--c:#d98a06;} .ws-card.other{--c:#2a7fc1;} .ws-card.offline{--c:#8795a5;background:#fafbfc;}
.ws-top{display:flex;align-items:center;gap:12px;}
.ws-av{flex:0 0 auto;width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px;background:linear-gradient(135deg,var(--blue),var(--navy));overflow:hidden;}
.ws-av img{width:100%;height:100%;object-fit:cover;}
.ws-who{min-width:0;flex:1;}
.ws-who b{display:block;font-size:15px;color:var(--navy);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.ws-who small{display:block;color:var(--mute);font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.ws-pill{display:inline-flex;align-items:center;gap:7px;flex:0 0 auto;padding:5px 11px;border-radius:20px;font-weight:700;font-size:12px;background:color-mix(in srgb,var(--c,#8795a5) 14%,#fff);color:var(--c,#8795a5);}
.ws-pill:before{content:"";width:8px;height:8px;border-radius:50%;background:var(--c,#8795a5);}
.ws-card.working .ws-pill:before{animation:wsPulse 1.6s infinite;}
.ws-file{padding:10px 12px;border-radius:10px;background:#f6f9fc;border:1px solid #edf1f5;min-height:72px;}
.ws-file .t{display:flex;align-items:center;justify-content:space-between;gap:8px;}
.ws-file .t b{font-size:14px;color:var(--navy);word-break:break-all;}
.ws-file .m{margin-top:4px;color:var(--mute);font-size:12px;}
.ws-file .x{margin-top:6px;display:flex;flex-wrap:wrap;gap:5px;align-items:center;}
.ws-file.none{display:flex;align-items:center;color:#9aa6b2;font-style:italic;}
.ws-tag{display:inline-block;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;background:#eaf2fa;color:var(--blue);}
.ws-tag.high{background:#fde8e8;color:#c0392b;} .ws-tag.late{background:#fff0e3;color:#c4590f;} .ws-tag.today{background:#e1effa;color:#1b6ca8;} .ws-tag.ok{background:#e3f6ec;color:#1e9e61;} .ws-tag.grey{background:#eef1f5;color:#6b7785;}
.ws-time{display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--mute);}
.ws-time b{color:var(--ink);font-variant-numeric:tabular-nums;}
.ws-time.alert b{color:var(--bad);}
.ws-nums{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;padding-top:10px;border-top:1px dashed var(--line);}
.ws-nums div{text-align:center;}
.ws-nums span{display:block;color:var(--mute);font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;}
.ws-nums b{font-size:16px;color:var(--navy);font-variant-numeric:tabular-nums;}
.ws-nums a{color:var(--blue);text-decoration:none;border-bottom:1px dashed var(--blue);cursor:pointer;}
.ws-login{display:flex;justify-content:space-between;gap:8px;color:var(--mute);font-size:12px;}

/* ---- table view ---- */
.ws-tw{overflow-x:auto;background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:0 2px 10px rgba(20,48,74,.05);animation:wsUp .4s ease both;}
.ws-table{width:100%;border-collapse:separate;border-spacing:0;min-width:1100px;}
.ws-table th{position:sticky;top:0;background:#eef3f8;color:var(--navy);padding:11px 12px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.6px;white-space:nowrap;cursor:pointer;border-bottom:2px solid #d5e1ed;}
.ws-table td{padding:10px 12px;border-bottom:1px solid #eef1f5;vertical-align:middle;white-space:nowrap;}
.ws-table tr:hover td{background:#f5f9fd;}
.ws-table tr.offline td{background:#fafbfc;color:#8795a5;} .ws-table tr.idle td:first-child{box-shadow:inset 4px 0 0 var(--bad);} .ws-table tr.working td:first-child{box-shadow:inset 4px 0 0 var(--ok);}
.ws-table .who{display:flex;align-items:center;gap:10px;font-weight:700;color:var(--navy);} .ws-table .who .ws-av{width:30px;height:30px;font-size:11px;}
.ws-table a{color:var(--blue);text-decoration:none;border-bottom:1px dashed var(--blue);cursor:pointer;}

.ws-empty{padding:46px 20px;text-align:center;color:var(--mute);background:#fff;border:1px dashed var(--line);border-radius:14px;}
.ws-empty b{display:block;font-size:16px;color:var(--navy);margin-bottom:4px;}

/* ---- file lists pop-up ---- */
.ws-modal{position:fixed;inset:0;z-index:100;display:none;align-items:center;justify-content:center;padding:18px;background:rgba(20,48,74,.45);backdrop-filter:blur(3px);}
.ws-modal.on{display:flex;}
.ws-box{width:min(920px,100%);height:min(80vh,720px);display:flex;flex-direction:column;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 30px 70px rgba(20,48,74,.35);animation:wsUp .25s ease both;}
.ws-box header{display:flex;align-items:center;justify-content:space-between;padding:12px 18px;color:#fff;background:linear-gradient(120deg,#e8742a,#f08a45);font-weight:700;}
.ws-box header button{border:0;background:rgba(255,255,255,.2);color:#fff;width:32px;height:32px;border-radius:50%;font-size:20px;line-height:1;cursor:pointer;}
.ws-box iframe{flex:1;border:0;width:100%;background:#fff;}

@media (max-width:700px){
  .ws-hero h1{font-size:20px;} .ws-chips{margin-left:0;width:100%;} .ws input[type=search]{min-width:0;width:100%;} .ws-field{flex:1 1 140px;}
  .ws-grid{grid-template-columns:1fr;}
}
@media (prefers-reduced-motion:reduce){*,*:before,*:after{animation:none !important;transition:none !important;}}
</style>
</head>
<body>
<div class="ws">

  <div class="ws-hero">
    <div>
      <small>TVL Workflow</small>
      <h1>Employee live status</h1>
      <p id="wsDate"><?php echo date('l, d M Y'); ?></p>
    </div>
    <div class="ws-hero-r">
      <span class="ws-live" id="wsLive">LIVE &middot; <span id="wsStamp"><?php echo $data['generated']; ?></span></span>
      <button type="button" class="ws-btn" id="wsRefresh"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 3v6h-6"/></svg> Refresh</button>
    </div>
  </div>

  <div class="ws-bar">
    <div class="ws-field"><label for="wsDept">Department</label>
      <select id="wsDept">
        <option value="">-Select-</option>
        <option value="all" <?php echo $dep === 'all' ? 'selected' : ''; ?>>All departments</option>
<?php foreach ($depts as $d) { ?>
        <option value="<?php echo (int)$d['dept_code']; ?>" <?php echo $dep === (string)$d['dept_code'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['dept_name'], ENT_QUOTES, 'UTF-8'); ?></option>
<?php } ?>
      </select></div>
    <div class="ws-field"><label for="wsSearch">Find</label><input type="search" id="wsSearch" placeholder="Name, file, customer..."></div>
    <div class="ws-field"><label for="wsSort">Sort by</label>
      <select id="wsSort">
        <option value="state">Status</option><option value="name">Name</option><option value="worked">Worked today</option>
        <option value="files">Files today</option><option value="pages">Pages today</option><option value="idle">Idle longest</option>
      </select></div>
    <div class="ws-field"><label>View</label><div class="ws-seg"><button type="button" data-view="cards" class="on">Cards</button><button type="button" data-view="table">Table</button></div></div>
    <label class="ws-auto"><input type="checkbox" id="wsAuto" checked> Auto refresh <span id="wsCount"></span></label>
    <div class="ws-chips" id="wsChips"></div>
  </div>

  <div class="ws-stats" id="wsStats"></div>
  <div id="wsBody"></div>
</div>

<div class="ws-modal" id="wsModal"><div class="ws-box"><header><span id="wsModalTitle">Files</span><button type="button" id="wsModalClose" aria-label="Close">&times;</button></header><iframe id="wsFrame" title="Files"></iframe></div></div>

<script>
(function () {
  var DATA = <?php echo json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_PARTIAL_OUTPUT_ON_ERROR); ?>;
  var DEP = <?php echo json_encode($dep); ?>;
  var REFRESH = 60;                                         // seconds
  var st = { filter: 'active', view: 'cards', q: '', sort: 'state', auto: true };
  try { var saved = JSON.parse(localStorage.getItem('wsLive') || '{}'); if (saved.view) { st.view = saved.view; } if (saved.sort) { st.sort = saved.sort; } if (saved.auto === false) { st.auto = false; } } catch (e) {}

  var $ = function (id) { return document.getElementById(id); };
  var ORDER = { idle: 0, working: 1, other: 2, break: 3, offline: 4 };
  var LABEL = { working: 'Working', idle: 'Idle', break: 'On break', other: 'Other', offline: 'Not logged in' };

  function esc(v) { var d = document.createElement('i'); d.textContent = (v === null || v === undefined) ? '' : v; return d.innerHTML; }
  function hms(s) { s = Math.max(0, Math.floor(s)); var h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), x = s % 60; return (h < 10 ? '0' : '') + h + ':' + (m < 10 ? '0' : '') + m + ':' + (x < 10 ? '0' : '') + x; }
  function ago(s) { s = Math.max(0, Math.floor(s)); if (s < 60) { return s + 's'; } var m = Math.floor(s / 60); if (m < 60) { return m + ' min'; } var h = Math.floor(m / 60); return h + 'h ' + (m % 60) + 'm'; }
  function initials(n) { var p = String(n || '?').trim().split(/\s+/); return ((p[0] || '?')[0] + (p[1] ? p[1][0] : '')).toUpperCase(); }
  function now() { return Math.floor(Date.now() / 1000) + (DATA.now - DATA._loaded); }

  function dueTag(c) {
    var out = '';
    if (c.high) { out += '<span class="ws-tag high">High priority</span>'; }
    if (c.due_days === null) { return out; }
    if (c.due_days < 0) { out += '<span class="ws-tag late">Overdue ' + (-c.due_days) + 'd</span>'; }
    else if (c.due_days === 0) { out += '<span class="ws-tag today">Due today</span>'; }
    else { out += '<span class="ws-tag ok">Due in ' + c.due_days + 'd</span>'; }
    return out;
  }
  function avatar(e) { return '<span class="ws-av">' + esc(initials(e.name)) + '</span>'; }
  function timeBlock(e) {
    if (e.state === 'working' || e.state === 'break' || e.state === 'other') {
      return '<div class="ws-time"><span>Started <b>' + esc(e.since_clock) + '</b></span><span>&middot; <b data-since="' + e.since + '">' + ago(now() - e.since) + '</b> ago</span></div>';
    }
    if (e.state === 'idle') {
      return '<div class="ws-time alert"><span>No file open for <b data-idle="' + e.idle_since + '">' + (e.idle_since ? ago(now() - e.idle_since) : '-') + '</b></span></div>';
    }
    return '<div class="ws-time"><span>No login today</span></div>';
  }
  function files(e, kind) {
    var n = kind === 'c' ? e.completed : e.files;
    return n > 0 ? '<a data-pop="' + kind + '" data-id="' + e.id + '" data-name="' + esc(e.name) + '">' + n + '</a>' : '0';
  }

  function card(e, i) {
    var c = e.cur, file = c
      ? '<div class="ws-file"><div class="t"><b>' + esc(c.pub_id) + '</b><span class="ws-tag">' + esc(c.stage) + '</span></div>' +
        '<div class="m">' + esc(c.cust) + (c.journal ? ' &middot; ' + esc(c.journal) : '') + '</div><div class="x">' + dueTag(c) + (c.status ? '<span class="ws-tag grey">' + esc(c.status) + '</span>' : '') + '</div></div>'
      : '<div class="ws-file none">' + (e.state === 'offline' ? 'Not logged in today' : 'No file picked up yet today') + '</div>';
    return '<div class="ws-card ' + e.state + '" style="animation-delay:' + Math.min(i, 14) * 30 + 'ms">' +
      '<div class="ws-top">' + avatar(e) + '<div class="ws-who"><b>' + esc(e.name) + '</b><small title="Login username">' + esc(e.dept) + ' &middot; ' + esc(e.username) + '</small></div>' +
      '<span class="ws-pill">' + esc(e.state === 'other' ? e.state_label : LABEL[e.state]) + '</span></div>' +
      file + timeBlock(e) +
      '<div class="ws-nums"><div><span>Files</span><b>' + files(e, 'f') + '</b></div><div><span>Done</span><b>' + files(e, 'c') + '</b></div><div><span>Pages</span><b>' + e.pages + '</b></div><div><span>Worked</span><b>' + hms(e.worked).slice(0, 5) + '</b></div></div>' +
      '<div class="ws-login"><span>First login <b>' + esc(e.first_in || '-') + '</b></span><span>Last login <b>' + esc(e.last_in || '-') + '</b></span><span>' + e.logins + ' login' + (e.logins === 1 ? '' : 's') + '</span></div></div>';
  }
  function row(e) {
    var c = e.cur;
    return '<tr class="' + e.state + '"><td><div class="who">' + avatar(e) + esc(e.name) + '</div></td><td>' + esc(e.dept) + '</td>' +
      '<td><span class="ws-pill ' + e.state + '" style="--c:var(--' + ({working: 'ok', idle: 'bad', break: 'warn', other: 'info', offline: 'mute'}[e.state]) + ')">' + esc(e.state === 'other' ? e.state_label : LABEL[e.state]) + '</span></td>' +
      '<td>' + (c ? '<b>' + esc(c.pub_id) + '</b> <span class="ws-tag">' + esc(c.stage) + '</span> ' + dueTag(c) + '<div style="color:#6b7785;font-size:12px">' + esc(c.cust) + (c.journal ? ' &middot; ' + esc(c.journal) : '') + '</div>' : '-') + '</td>' +
      '<td>' + (e.since ? esc(e.since_clock) + ' &middot; <b data-since="' + e.since + '">' + ago(now() - e.since) + '</b>' : (e.state === 'idle' ? '<span style="color:#d64545;font-weight:700">idle <b data-idle="' + e.idle_since + '">' + (e.idle_since ? ago(now() - e.idle_since) : '-') + '</b></span>' : '-')) + '</td>' +
      '<td>' + files(e, 'f') + '</td><td>' + files(e, 'c') + '</td><td>' + e.pages + '</td><td>' + hms(e.worked) + '</td><td>' + esc(e.first_in || '-') + ' / ' + esc(e.last_in || '-') + '</td></tr>';
  }

  function visible() {
    var q = st.q.toLowerCase();
    var list = DATA.employees.filter(function (e) {
      if (st.filter === 'active' && e.state === 'offline') { return false; }          // default: only people who are logged in today
      if (st.filter !== 'all' && st.filter !== 'active' && e.state !== st.filter) { return false; }
      if (!q) { return true; }
      var hay = [e.name, e.username, e.dept, e.email, e.cur ? e.cur.pub_id : '', e.cur ? e.cur.cust : '', e.cur ? e.cur.journal : ''].join(' ').toLowerCase();
      return hay.indexOf(q) !== -1;
    });
    var t = now();
    list.sort(function (a, b) {
      switch (st.sort) {
        case 'name':   return a.name.localeCompare(b.name);
        case 'worked': return b.worked - a.worked;
        case 'files':  return b.files - a.files;
        case 'pages':  return b.pages - a.pages;
        case 'idle':   return (b.state === 'idle' ? t - b.idle_since : -1) - (a.state === 'idle' ? t - a.idle_since : -1);
        default:       return (ORDER[a.state] - ORDER[b.state]) || a.name.localeCompare(b.name);
      }
    });
    return list;
  }

  function render() {
    var s = DATA.summary || {}, empty = !DATA.employees.length;
    $('wsStats').innerHTML = empty ? '' :
      [['Employees', s.total, '#1b6ca8'], ['Working now', s.working, '#1e9e61'], ['Idle (logged in)', s.idle, '#d64545'], ['Not logged in', s.offline, '#8795a5'],
       ['Files today', s.files, '#e8742a'], ['Completed', s.completed, '#1e9e61'], ['Pages', s.pages, '#14304a']]
      .map(function (x, i) { return '<div class="ws-stat" style="--c:' + x[2] + ';animation-delay:' + i * 40 + 'ms"><span>' + x[0] + '</span><b>' + (x[1] || 0) + '</b></div>'; }).join('');
    var chips = [['active', 'Active', (s.total || 0) - (s.offline || 0)], ['all', 'All', s.total], ['working', 'Working', s.working], ['idle', 'Idle', s.idle], ['break', 'Break', s.break], ['other', 'Other', s.other], ['offline', 'Offline', s.offline]];
    $('wsChips').innerHTML = empty ? '' : chips.map(function (c) { return '<button type="button" class="ws-chip ' + c[0] + (st.filter === c[0] ? ' on' : '') + '" data-f="' + c[0] + '"><i></i>' + c[1] + ' <b>' + (c[2] || 0) + '</b></button>'; }).join('');

    var list = visible(), body = $('wsBody');
    if (!DEP) { body.innerHTML = '<div class="ws-empty"><b>Choose a department</b>Pick a department above to see who is logged in and what they are working on.</div>'; return; }
    if (empty) { body.innerHTML = '<div class="ws-empty"><b>No employees found</b>No active employee of this department is registered for TVL / CHN.</div>'; return; }
    if (!list.length) {
      body.innerHTML = st.filter === 'active' && !st.q
        ? '<div class="ws-empty"><b>Nobody is logged in right now</b>Only employees who logged in today are shown. <a href="#" id="wsShowAll" style="color:#1b6ca8;font-weight:700;">Show everyone</a></div>'
        : '<div class="ws-empty"><b>Nothing matches</b>Try another status or clear the search.</div>';
      return;
    }
    if (st.view === 'table') {
      body.innerHTML = '<div class="ws-tw"><table class="ws-table"><thead><tr><th>Employee</th><th>Department</th><th>Status</th><th>Current file</th><th>Started / elapsed</th><th>Files</th><th>Done</th><th>Pages</th><th>Worked</th><th>First / last login</th></tr></thead><tbody>' + list.map(row).join('') + '</tbody></table></div>';
    } else {
      body.innerHTML = '<div class="ws-grid">' + list.map(card).join('') + '</div>';
    }
  }

  /* ---- ticking counters (no server call) ---- */
  setInterval(function () {
    var t = now(), i, n;
    n = document.querySelectorAll('[data-since]'); for (i = 0; i < n.length; i++) { n[i].textContent = ago(t - (+n[i].getAttribute('data-since'))); }
    n = document.querySelectorAll('[data-idle]');  for (i = 0; i < n.length; i++) { var v = +n[i].getAttribute('data-idle'); n[i].textContent = v ? ago(t - v) : '-'; }
  }, 1000);

  /* ---- refresh ---- */
  var left = REFRESH, busy = false;
  function load(manual) {
    if (busy || !DEP) { return; }
    busy = true; $('wsRefresh').classList.add('spin');
    fetch('user.php?format=json&dep_id=' + encodeURIComponent(DEP), { cache: 'no-store', credentials: 'same-origin' })
      .then(function (r) { if (!r.ok) { throw new Error(r.status); } return r.json(); })
      .then(function (j) { DATA = j; DATA._loaded = Math.floor(Date.now() / 1000); $('wsStamp').textContent = j.generated; $('wsLive').classList.remove('off'); render(); })
      .catch(function () { $('wsLive').classList.add('off'); })
      .then(function () { busy = false; left = REFRESH; $('wsRefresh').classList.remove('spin'); });
  }
  setInterval(function () {
    if (!st.auto || !DEP) { $('wsCount').textContent = ''; return; }
    if (document.hidden) { return; }
    left--; $('wsCount').textContent = '(' + left + 's)';
    if (left <= 0) { load(false); }
  }, 1000);

  /* ---- events ---- */
  DATA._loaded = Math.floor(Date.now() / 1000);
  $('wsDept').addEventListener('change', function () { window.location.href = 'user.php' + (this.value ? '?dep_id=' + encodeURIComponent(this.value) : ''); });
  $('wsSearch').addEventListener('input', function () { st.q = this.value; render(); });
  $('wsSort').value = st.sort;
  $('wsSort').addEventListener('change', function () { st.sort = this.value; save(); render(); });
  $('wsAuto').checked = st.auto;
  $('wsAuto').addEventListener('change', function () { st.auto = this.checked; left = REFRESH; save(); });
  $('wsRefresh').addEventListener('click', function () { load(true); });
  document.querySelectorAll('.ws-seg button').forEach(function (b) {
    if (b.getAttribute('data-view') === st.view) { b.classList.add('on'); } else { b.classList.remove('on'); }
    b.addEventListener('click', function () { st.view = this.getAttribute('data-view'); document.querySelectorAll('.ws-seg button').forEach(function (x) { x.classList.toggle('on', x === b); }); save(); render(); });
  });
  $('wsBody').addEventListener('click', function (e) { if (e.target.id === 'wsShowAll') { e.preventDefault(); st.filter = 'all'; render(); } });
  $('wsChips').addEventListener('click', function (e) { var b = e.target.closest('[data-f]'); if (b) { st.filter = b.getAttribute('data-f'); render(); } });
  function save() { try { localStorage.setItem('wsLive', JSON.stringify({ view: st.view, sort: st.sort, auto: st.auto })); } catch (e) {} }

  /* today's file lists: the existing pages, in a pop-up */
  document.addEventListener('click', function (e) {
    var a = e.target.closest('[data-pop]'); if (!a) { return; }
    $('wsModalTitle').textContent = (a.getAttribute('data-pop') === 'c' ? 'Completed files today - ' : 'Files worked today - ') + a.getAttribute('data-name');
    $('wsFrame').src = (a.getAttribute('data-pop') === 'c' ? 'project_user_ctime.php?id=' : 'project_user_time.php?id=') + encodeURIComponent(a.getAttribute('data-id'));
    $('wsModal').classList.add('on');
  });
  function closeModal() { $('wsModal').classList.remove('on'); $('wsFrame').src = 'about:blank'; }
  $('wsModalClose').addEventListener('click', closeModal);
  $('wsModal').addEventListener('click', function (e) { if (e.target === this) { closeModal(); } });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { closeModal(); } });

  render();
})();
</script>
</body>
</html>
