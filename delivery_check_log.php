<?php include "includes/includes.php";?>
<?php
/* Pre-delivery checks: every time Start Work v3 showed the "what will go to Delivery" popup - what was in the folder, what would move, what the user decided,
   and how the submit ended.   delivery_check_log.php?aid=123 shows one article.   Same look as the Dispatch Log. */
require_once __DIR__ . '/sw_switch_lib.php';
$db->query("CREATE TABLE IF NOT EXISTS `inw_delivery_confirm_log` (
	`id` int(11) NOT NULL AUTO_INCREMENT, `a_id` int(11) DEFAULT NULL, `pub_id` varchar(100) DEFAULT NULL, `cust_id` int(11) DEFAULT NULL, `j_id` int(11) DEFAULT NULL,
	`stage` varchar(25) DEFAULT NULL, `from_dept` varchar(60) DEFAULT NULL, `to_label` varchar(120) DEFAULT NULL, `kind` varchar(20) DEFAULT NULL,
	`user_id` varchar(20) DEFAULT NULL, `user_name` varchar(100) DEFAULT NULL, `ip` varchar(45) DEFAULT NULL,
	`src_dir` text, `dest_dir` text, `folder` mediumtext, `config` mediumtext, `auto_info` mediumtext, `warnings` mediumtext, `errors` mediumtext,
	`decision` varchar(15) DEFAULT NULL, `shown_at` datetime DEFAULT NULL, `decided_at` datetime DEFAULT NULL,
	`outcome` varchar(15) DEFAULT NULL, `outcome_message` text, `after_folder` mediumtext, `report_mail` varchar(255) DEFAULT NULL, `finished_at` datetime DEFAULT NULL,
	PRIMARY KEY (`id`), KEY `a_id` (`a_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ---- Filters ----
$f_aid     = isset($_GET['aid'])     ? (int) $_GET['aid']     : 0;
$f_dec     = isset($_GET['decision']) ? trim($_GET['decision']) : '';
$f_cust    = isset($_GET['cust'])    ? (int) $_GET['cust']    : 0;
$f_user    = isset($_GET['user'])    ? trim($_GET['user'])    : '';
$f_dept    = isset($_GET['dept'])    ? trim($_GET['dept'])    : '';
$f_article = isset($_GET['article']) ? trim($_GET['article']) : '';
$f_from    = isset($_GET['from'])    ? trim($_GET['from'])    : '';
$f_to      = isset($_GET['to'])      ? trim($_GET['to'])      : '';
$f_mail    = isset($_GET['mail'])    ? trim($_GET['mail'])    : '';
$f_keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
if (!in_array($f_dec, array('confirmed', 'cancelled', 'blocked', 'refused', 'shown'), true)) { $f_dec = ''; }

$where = array(); $params = array(); $types = '';
if ($f_aid > 0)        { $where[] = "l.a_id = ?";            $params[] = $f_aid;  $types .= 'i'; }
if ($f_cust > 0)       { $where[] = "l.cust_id = ?";         $params[] = $f_cust; $types .= 'i'; }
if ($f_user !== '')    { $where[] = "l.user_id = ?";         $params[] = $f_user; $types .= 's'; }
if ($f_dept !== '')    { $where[] = "l.from_dept = ?";       $params[] = $f_dept; $types .= 's'; }
if ($f_article !== '') { $where[] = "l.pub_id LIKE ?";       $params[] = "%$f_article%"; $types .= 's'; }
if ($f_from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_from)) { $where[] = "l.shown_at >= ?"; $params[] = $f_from . ' 00:00:00'; $types .= 's'; }
if ($f_to !== ''   && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_to))   { $where[] = "l.shown_at <= ?"; $params[] = $f_to . ' 23:59:59';   $types .= 's'; }
if ($f_mail === 'sent')   { $where[] = "l.report_mail = 'Sent'"; }
if ($f_mail === 'notsent'){ $where[] = "l.report_mail LIKE 'Not sent%'"; }
if ($f_mail === 'failed') { $where[] = "l.report_mail LIKE 'Failed%'"; }
if ($f_keyword !== '') {
	$where[] = "(l.folder LIKE ? OR l.warnings LIKE ? OR l.errors LIKE ? OR l.outcome_message LIKE ? OR l.after_folder LIKE ?)";
	for ($i = 0; $i < 5; $i++) { $params[] = "%$f_keyword%"; $types .= 's'; }
}
$base_where = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$tab = array('all' => 0, 'confirmed' => 0, 'cancelled' => 0, 'blocked' => 0, 'refused' => 0, 'shown' => 0);
$stmt = $db->prepare("SELECT l.decision, COUNT(*) FROM inw_delivery_confirm_log l" . $base_where . " GROUP BY l.decision");
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute(); $stmt->bind_result($dn, $dc);
while ($stmt->fetch()) { $k = isset($tab[$dn]) ? $dn : 'shown'; $tab[$k] += $dc; $tab['all'] += $dc; }
$stmt->close();

$lw = $where; $lp = $params; $lt = $types;
if ($f_dec !== '') { $lw[] = "l.decision = ?"; $lp[] = $f_dec; $lt .= 's'; }
$where_sql = $lw ? ' WHERE ' . implode(' AND ', $lw) : '';
$total_rows = $f_dec !== '' ? $tab[$f_dec] : $tab['all'];

$per_page = 25;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$total_pages = max(1, (int) ceil($total_rows / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

$rows = array();
$stmt = $db->prepare("SELECT l.*, c.cust_name, j.j_code FROM inw_delivery_confirm_log l
	LEFT JOIN adm_customer_master c ON c.id = l.cust_id LEFT JOIN adm_journals j ON j.j_id = l.j_id" . $where_sql . " ORDER BY l.id DESC LIMIT $per_page OFFSET $offset");
if ($lp) { $stmt->bind_param($lt, ...$lp); }
$stmt->execute(); $res = $stmt->get_result();
while ($r = $res->fetch_assoc()) { $rows[] = $r; }
$stmt->close();

$depts = array(); $res = $db->query("SELECT DISTINCT from_dept FROM inw_delivery_confirm_log WHERE from_dept <> '' ORDER BY from_dept");
while ($res && ($r = $res->fetch_assoc())) { $depts[] = $r['from_dept']; }
$custs = array(); $res = $db->query("SELECT DISTINCT l.cust_id, c.cust_name FROM inw_delivery_confirm_log l LEFT JOIN adm_customer_master c ON c.id = l.cust_id ORDER BY c.cust_name");
while ($res && ($r = $res->fetch_assoc())) { $custs[] = $r; }
$users_dd = array(); $res = $db->query("SELECT DISTINCT user_id, user_name FROM inw_delivery_confirm_log ORDER BY user_name");
while ($res && ($r = $res->fetch_assoc())) { $users_dd[] = $r; }

$qs = $_GET; unset($qs['page']);
$base_qs = http_build_query($qs);
$tab_qs = function ($d) { $q = $_GET; unset($q['page']); if ($d === '') { unset($q['decision']); } else { $q['decision'] = $d; } return '?' . http_build_query($q); };
function h($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
function dc_initials($n) { $p = preg_split('/\s+/', trim((string) $n)); $i = ''; foreach (array_slice($p, 0, 2) as $w) { $i .= strtoupper(substr($w, 0, 1)); } return $i !== '' ? $i : '?'; }
function dc_dec($d) { $m = array('confirmed' => array('Confirmed', '#1e9e61'), 'cancelled' => array('Cancelled', '#6b7785'), 'blocked' => array('Blocked', '#d64545'), 'refused' => array('Refused by server', '#d64545'), 'shown' => array('Shown, no decision', '#d98a06')); return isset($m[$d]) ? $m[$d] : array($d, '#6b7785'); }

$dc_chips = array();
$dc_drop = function ($key) { $q = $_GET; unset($q['page']); unset($q[$key]); return '?' . http_build_query($q); };
$cn = ''; foreach ($custs as $c) { if ((int) $c['cust_id'] === $f_cust) { $cn = $c['cust_name'] !== null && $c['cust_name'] !== '' ? $c['cust_name'] : 'customer ' . $c['cust_id']; } }
$un = ''; foreach ($users_dd as $u) { if ((string) $u['user_id'] === $f_user) { $un = $u['user_name'] !== '' ? $u['user_name'] : 'user_id ' . $u['user_id']; } }
if ($f_cust > 0)       { $dc_chips[] = array('Customer', $cn !== '' ? $cn : 'customer ' . $f_cust, 'cust'); }
if ($f_user !== '')    { $dc_chips[] = array('Checked by', $un !== '' ? $un : 'user_id ' . $f_user, 'user'); }
if ($f_dept !== '')    { $dc_chips[] = array('Department', $f_dept, 'dept'); }
if ($f_mail !== '')    { $dc_chips[] = array('Report email', $f_mail === 'sent' ? 'Sent' : ($f_mail === 'notsent' ? 'Not sent' : 'Failed'), 'mail'); }
if ($f_keyword !== '') { $dc_chips[] = array('Search', $f_keyword, 'keyword'); }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php include_once 'includes/modern-ui.php'; include_once 'includes/article-filter-ui.php'; mu_head(); af_head(); ?>
    <style>
      .af-tab{text-decoration:none !important;} .af-card .table-responsive{overflow-x:auto;} .dl-muted{color:#8a97ab;}
      .dl-ovl{position:fixed;inset:0;background:rgba(15,28,45,.55);z-index:2000;display:none;align-items:center;justify-content:center;padding:18px;}
      .dl-ovl.open{display:flex;animation:muPop .18s ease both;}
      .dl-box{background:#fff;border-radius:18px;width:min(860px,100%);max-height:calc(100vh - 36px);display:flex;flex-direction:column;overflow:hidden;box-shadow:0 24px 70px rgba(15,28,45,.45);}
      .dl-hd{background:linear-gradient(90deg,#e8863a,#de7a2a);color:#fff;padding:16px 24px;display:flex;align-items:center;justify-content:space-between;font-weight:700;font-size:17px;}
      .dl-hd button{background:none;border:0;color:#fff;font-size:26px;line-height:1;cursor:pointer;opacity:.9;} .dl-hd button:hover{opacity:1;}
      .dl-bd{background:#f3f6fb;padding:18px 24px;overflow:auto;flex:1;}
      .dl-ft{background:#fff;padding:14px 24px;text-align:right;}
      .dl-ft button{background:#1d3557;color:#fff;border:0;border-radius:10px;padding:10px 30px;font-weight:700;cursor:pointer;} .dl-ft button:hover{background:#274a78;}
      .dl-hero{background:linear-gradient(120deg,#1b3050,#2c5f9e);color:#fff;border-radius:16px;padding:16px 20px;margin-bottom:6px;text-align:left;}
      .dl-hero-k{font-size:11px;letter-spacing:.08em;opacity:.8;} .dl-hero-t{font-size:22px;font-weight:700;line-height:1.25;word-break:break-all;} .dl-hero-s{font-size:13px;opacity:.9;}
      .dl-pills{margin-top:10px;} .dl-pill{display:inline-block;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.28);border-radius:12px;padding:5px 12px;margin:0 8px 4px 0;font-size:12px;}
      .dl-pill.ok{background:rgba(46,139,87,.55);} .dl-pill.warn{background:rgba(214,137,16,.6);} .dl-pill.bad{background:rgba(192,57,43,.7);}
      .dl-sec{margin:18px 0 8px;font-size:12px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:#1d3557;text-align:left;} .dl-sec .fa{margin-right:4px;color:#2c5f9e;}
      .dl-count{display:inline-block;min-width:20px;text-align:center;background:#dfe8f5;color:#2c5f9e;border-radius:10px;padding:0 7px;font-size:11px;margin-left:4px;}
      .dl-card{background:#fff;border-left:4px solid #2c5f9e;border-radius:12px;padding:11px 14px;margin-bottom:9px;box-shadow:0 1px 4px rgba(20,35,60,.1);text-align:left;font-size:13px;color:#223;}
      .dl-card.ok{border-left-color:#2e8b57;} .dl-card.warn{border-left-color:#e0a21b;color:#8a6200;} .dl-card.bad{border-left-color:#c0392b;color:#c0392b;}
      .dl-kv{width:100%;} .dl-kv td{padding:3px 0;vertical-align:top;word-break:break-word;border:0 !important;background:transparent !important;white-space:normal !important;} .dl-kv td:first-child{width:110px;color:#7a869a;font-weight:600;padding-right:12px;white-space:nowrap !important;}
      .dl-meta{margin-top:2px;color:#667;} .dl-meta span{display:inline-block;margin:0 14px 2px 0;} .dl-meta .fa{margin-right:4px;color:#8a97ab;}
      .dl-row{display:flex;align-items:center;gap:8px;flex-wrap:wrap;} .dl-right{margin-left:auto;color:#5b6b7b;font-size:12px;}
      .dl-zip{margin-top:6px;max-height:170px;overflow:auto;background:#fafbfd;border:1px solid #eef1f6;border-radius:8px;font-size:12px;}
      .dl-zip div{display:flex;justify-content:space-between;gap:10px;padding:4px 10px;border-top:1px solid #f0f3f8;} .dl-zip div:first-child{border-top:0;} .dl-zip em{font-style:normal;color:#8a97ab;white-space:nowrap;}
      .dl-sent{display:inline-block;padding:0 8px;border-radius:10px;background:#2e8b57;color:#fff;font-size:10.5px;font-weight:700;} .dl-sent.grey{background:#9aa6b8;}
      .dl-bad{color:#c0392b;}
    </style>
  </head>
  <body>
    <div class="wholeouter">
      <?php include("includes/top-header.php");?>

      <div class="af-page" style="padding-bottom:0;">
        <div class="af-hero">
          <div><small>Start Work</small><h1>Pre-delivery Checks <span style="font-size:13px;font-weight:600;opacity:.8;">&middot; <?php echo (int) $tab['all']; ?> entr<?php echo $tab['all'] == 1 ? 'y' : 'ies'; ?></span></h1></div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <?php if ($f_aid > 0) { ?><a class="af-add" href="deliverychecklog"><i class="fa fa-list"></i> All articles</a><?php } ?>
            <a class="af-add" href="dispatchlog"><i class="fa fa-paper-plane-o"></i> Dispatch Log</a>
          </div>
        </div>
      </div>

      <div class="af-page"><div class="af-wrap">
        <form action="" method="get" class="formouter">
          <?php if ($f_aid > 0) { ?><input type="hidden" name="aid" value="<?php echo (int) $f_aid; ?>"><?php } ?>
          <?php if ($f_dec !== '') { ?><input type="hidden" name="decision" value="<?php echo h($f_dec); ?>"><?php } ?>
          <div class="af-bar">
            <div class="af-field search">
              <label class="mb-2">Article</label>
              <input class="form-control" type="text" name="article" value="<?php echo h($f_article); ?>" placeholder="Enter Article ID">
            </div>
            <div class="af-field date dvr-field">
              <label class="mb-2">Date range</label>
              <div class="dvr" data-format="ymd" data-from-name="from" data-to-name="to" data-from="<?php echo h($f_from); ?>" data-to="<?php echo h($f_to); ?>"></div>
            </div>
            <link rel="stylesheet" href="dv-rangepicker.css?v=1"><script src="dv-rangepicker.js?v=1"></script>
            <div class="af-actions">
              <button type="button" class="af-btn <?php echo $dc_chips ? 'has' : ''; ?>" id="afToggle"><i class="fa fa-sliders"></i> Filters <span class="af-count"><?php echo count($dc_chips); ?></span> <i class="fa fa-angle-down"></i></button>
              <button type="submit" class="af-btn primary"><i class="fa fa-search"></i> Get Result</button>
              <a class="af-btn" href="<?php echo $f_aid > 0 ? 'deliverychecklog?aid=' . (int) $f_aid : 'deliverychecklog'; ?>" title="Reset filters"><i class="fa fa-refresh"></i></a>
            </div>
          </div>
          <div class="af-panel <?php echo $dc_chips ? 'open' : ''; ?>" id="afPanel">
            <div class="af-grid">
              <div class="af-field"><label class="mb-2">Customer</label>
                <select name="cust" class="form-select"><option value="0">All</option>
                  <?php foreach ($custs as $c) { ?><option value="<?php echo (int) $c['cust_id']; ?>" <?php if ((int) $c['cust_id'] === $f_cust) echo 'selected'; ?>><?php echo h($c['cust_name'] !== null && $c['cust_name'] !== '' ? $c['cust_name'] : 'customer ' . $c['cust_id']); ?></option><?php } ?>
                </select></div>
              <div class="af-field"><label class="mb-2">Department</label>
                <select name="dept" class="form-select"><option value="">All</option>
                  <?php foreach ($depts as $d) { ?><option value="<?php echo h($d); ?>" <?php if ($d === $f_dept) echo 'selected'; ?>><?php echo h($d); ?></option><?php } ?>
                </select></div>
              <div class="af-field"><label class="mb-2">Checked by</label>
                <select name="user" class="form-select"><option value="">All</option>
                  <?php foreach ($users_dd as $u) { ?><option value="<?php echo h($u['user_id']); ?>" <?php if ((string) $u['user_id'] === $f_user) echo 'selected'; ?>><?php echo h($u['user_name'] !== '' ? $u['user_name'] : 'user_id ' . $u['user_id']); ?></option><?php } ?>
                </select></div>
              <div class="af-field"><label class="mb-2">Report email</label>
                <select name="mail" class="form-select"><option value="">All</option>
                  <option value="sent" <?php if ($f_mail === 'sent') echo 'selected'; ?>>Sent</option>
                  <option value="notsent" <?php if ($f_mail === 'notsent') echo 'selected'; ?>>Not sent</option>
                  <option value="failed" <?php if ($f_mail === 'failed') echo 'selected'; ?>>Failed</option>
                </select></div>
              <div class="af-field"><label class="mb-2">Search</label>
                <input type="text" name="keyword" class="form-control" value="<?php echo h($f_keyword); ?>" placeholder="file name, warning, result"></div>
            </div>
          </div>
          <?php if ($dc_chips) { ?>
          <div class="af-chips" id="afChips">
            <?php foreach ($dc_chips as $ch) { ?>
              <span class="af-chip"><?php echo h($ch[0]); ?>: <b><?php echo h($ch[1]); ?></b> <a href="<?php echo h($dc_drop($ch[2])); ?>" title="Remove" style="color:inherit;text-decoration:none;font-size:15px;line-height:1;">&times;</a></span>
            <?php } ?>
            <?php if (count($dc_chips) > 1) { ?><a class="af-chip clear" href="<?php echo $f_aid > 0 ? 'deliverychecklog?aid=' . (int) $f_aid : 'deliverychecklog'; ?>" style="text-decoration:none;">Clear all</a><?php } ?>
          </div>
          <?php } ?>
        </form>
      </div></div>

      <div class="af-page">
        <div class="af-tabs" role="tablist" aria-label="Decision">
          <a class="af-tab <?php echo $f_dec === '' ? 'active' : ''; ?>" href="<?php echo h($tab_qs('')); ?>"><i class="fa fa-th-list"></i> All <b><?php echo (int) $tab['all']; ?></b></a>
          <a class="af-tab d4 <?php echo $f_dec === 'confirmed' ? 'active' : ''; ?>" href="<?php echo h($tab_qs('confirmed')); ?>"><i class="fa fa-check-circle"></i> Confirmed <b><?php echo (int) $tab['confirmed']; ?></b></a>
          <a class="af-tab today <?php echo $f_dec === 'cancelled' ? 'active' : ''; ?>" href="<?php echo h($tab_qs('cancelled')); ?>"><i class="fa fa-undo"></i> Cancelled <b><?php echo (int) $tab['cancelled']; ?></b></a>
          <a class="af-tab high <?php echo $f_dec === 'blocked' ? 'active' : ''; ?>" href="<?php echo h($tab_qs('blocked')); ?>"><i class="fa fa-ban"></i> Blocked <b><?php echo (int) $tab['blocked']; ?></b></a>
          <a class="af-tab high <?php echo $f_dec === 'refused' ? 'active' : ''; ?>" href="<?php echo h($tab_qs('refused')); ?>"><i class="fa fa-shield"></i> Refused by server <b><?php echo (int) $tab['refused']; ?></b></a>
        </div>
      </div>

      <div class="af-page"><div class="af-card">
        <div class="table-responsive">
          <table class="table custtable table-primary dataTable" style="width:100% !important;">
            <thead>
              <tr><th>#</th><th>Date / Time</th><th>Decision</th><th>Article</th><th>Customer / Journal</th><th>Moving</th><th>Checked by</th><th>Outcome</th><th>Report email</th><th>Details</th></tr>
            </thead>
            <tbody>
              <?php if (!$rows) { ?><tr><td class="dataTables_empty" colspan="10">No pre-delivery checks found.</td></tr><?php } ?>
              <?php foreach ($rows as $n => $x) {
                $files = json_decode($x['folder'], true); if (!is_array($files)) { $files = array(); }
                $cfg = json_decode($x['config'], true); $auto = json_decode($x['auto_info'], true);
                $warn = json_decode($x['warnings'], true); if (!is_array($warn)) { $warn = array(); }
                $errs = json_decode($x['errors'], true); if (!is_array($errs)) { $errs = array(); }
                $after = json_decode($x['after_folder'], true); if (!is_array($after)) { $after = array(); }
                $dbf = !empty($x['dest_before']) ? json_decode($x['dest_before'], true) : array(); if (!is_array($dbf)) { $dbf = array(); }
                $coll = 0; foreach ($dbf as $d) { if (!empty($d['collides'])) { $coll++; } }
                $move_n = 0; foreach ($files as $f) { if (!empty($f['will_move'])) { $move_n++; } }
                list($dtxt, $dcol) = dc_dec($x['decision']);
                $ocol = $x['outcome'] === 'completed' ? '#1e9e61' : ($x['outcome'] === 'failed' ? '#d64545' : '#6b7785');
                $ots = $x['outcome'] === 'completed' ? 'Completed' : ($x['outcome'] === 'failed' ? 'Failed' : '-');
                $rowcls = $x['decision'] === 'blocked' ? 'af-row-high' : ($x['outcome'] === 'failed' ? 'af-row-warn' : '');
                $who = $x['user_name'] !== '' ? $x['user_name'] : 'user_id ' . $x['user_id'];
                $ts = strtotime($x['shown_at']);
              ?>
                <tr class="af-in <?php echo $rowcls; ?>" style="--i:<?php echo (int) $n; ?>">
                  <td><span class="dl-muted"><?php echo (int) $x['id']; ?></span></td>
                  <td><span class="af-date"><b><?php echo h($ts ? date('d-M-Y', $ts) : ''); ?></b><small><?php echo h($ts ? date('h:i A', $ts) : ''); ?></small></span></td>
                  <td><span class="af-status" style="--c:<?php echo $dcol; ?>"><?php echo h($dtxt); ?></span></td>
                  <td><a class="af-art" href="?aid=<?php echo (int) $x['a_id']; ?>" style="text-decoration:none;"><?php echo h($x['pub_id']); ?></a><br><span class="af-stage" style="--c:#1b6ca8"><?php echo h($x['stage']); ?></span> <small class="dl-muted"><?php echo h($x['from_dept']); ?> &rarr; <?php echo h($x['to_label']); ?></small></td>
                  <td><span class="af-chip-j"><?php echo h($x['cust_name']); ?> / <?php echo h($x['j_code']); ?></span></td>
                  <td><span class="af-num"><?php echo (int) $move_n; ?></span> file<?php echo $move_n == 1 ? '' : 's'; ?><?php if ($warn) { ?><br><small class="dl-muted"><?php echo count($warn); ?> warning<?php echo count($warn) == 1 ? '' : 's'; ?></small><?php } ?></td>
                  <td><span class="af-person"><span class="af-av"><?php echo h(dc_initials($x['user_name'])); ?></span><?php echo h($who); ?></span><br><small class="dl-muted"><?php echo h($x['ip']); ?></small></td>
                  <td><span class="af-status" style="--c:<?php echo $ocol; ?>"><?php echo $ots; ?></span><?php if ($x['outcome'] === 'failed' && $x['outcome_message'] !== '') { ?><div style="font-size:11.5px;color:#c0392b;max-width:220px;white-space:normal;margin-top:3px;line-height:1.3;"><?php echo h($x['outcome_message']); ?></div><?php } ?></td>
                  <td><span class="af-sub"><?php echo h($x['report_mail'] !== null && $x['report_mail'] !== '' ? $x['report_mail'] : '-'); ?></span></td>
                  <td>
                    <button type="button" class="af-btn dl-toggle"><i class="fa fa-eye"></i> View</button>
                    <div class="dl-pop" style="display:none">
                      <div class="dl-hero">
                        <div class="dl-hero-k">ARTICLE</div><div class="dl-hero-t"><?php echo h($x['pub_id']); ?></div>
                        <div class="dl-hero-s"><?php echo h($x['cust_name']); ?> &middot; <?php echo h($x['j_code']); ?> &middot; stage <?php echo h($x['stage']); ?></div>
                        <div class="dl-hero-s" style="margin-top:6px;"><b><?php echo h($x['from_dept']); ?></b> &rarr; <b><?php echo h($x['to_label']); ?></b></div>
                        <div class="dl-pills">
                          <span class="dl-pill <?php echo $x['decision'] === 'confirmed' ? 'ok' : (($x['decision'] === 'blocked' || $x['decision'] === 'refused') ? 'bad' : 'warn'); ?>"><b><?php echo h($dtxt); ?></b></span>
                          <span class="dl-pill <?php echo $x['outcome'] === 'completed' ? 'ok' : ($x['outcome'] === 'failed' ? 'bad' : ''); ?>">outcome: <b><?php echo $ots; ?></b></span>
                          <span class="dl-pill"><b><?php echo (int) $move_n; ?></b> file<?php echo $move_n == 1 ? '' : 's'; ?> to move</span>
                          <?php if ($warn) { ?><span class="dl-pill warn"><b><?php echo count($warn); ?></b> warning<?php echo count($warn) == 1 ? '' : 's'; ?></span><?php } ?>
                          <?php if ($errs) { ?><span class="dl-pill bad"><b><?php echo count($errs); ?></b> problem<?php echo count($errs) == 1 ? '' : 's'; ?></span><?php } ?>
                          <span class="dl-pill">report mail: <b><?php echo h($x['report_mail'] !== null && $x['report_mail'] !== '' ? $x['report_mail'] : '-'); ?></b></span>
                        </div>
                        <div class="dl-hero-s" style="margin-top:8px;"><?php echo h($who); ?> &middot; <?php echo h($x['ip']); ?> &middot; shown <?php echo h($ts ? date('d M Y h:i:s A', $ts) : ''); ?><?php if ($x['decided_at']) { ?> &middot; decided <?php echo h(date('h:i:s A', strtotime($x['decided_at']))); ?><?php } ?></div>
                      </div>
                      <?php if ($x['outcome'] === 'failed' && $x['outcome_message'] !== '') { ?><div class="dl-card bad"><i class="fa fa-times-circle"></i> <?php echo h($x['outcome_message']); ?></div><?php } ?>

                      <div class="dl-sec"><i class="fa fa-folder-o"></i> FILES IN THE <?php echo strtoupper(h($x['from_dept'])); ?> FOLDER <span class="dl-count"><?php echo count($files); ?></span></div>
                      <?php if (!$files) { ?><div class="dl-card"><span class="dl-muted">The folder was empty or not visible to the server.</span></div><?php } ?>
                      <?php foreach ($files as $f) { ?>
                        <div class="dl-card <?php echo !empty($f['error']) ? 'bad' : (!empty($f['will_move']) ? 'ok' : ''); ?>">
                          <div class="dl-row"><b><?php echo h(isset($f['rel']) ? $f['rel'] : $f['name']); ?></b>
                            <?php if (!empty($f['will_move'])) { ?><span class="dl-sent">will move</span><?php } else { ?><span class="dl-sent grey">stays</span><?php } ?>
                            <span class="dl-right"><?php echo isset($f['size_text']) ? h($f['size_text']) : ''; ?> &middot; <?php echo isset($f['modified']) ? h($f['modified']) : ''; ?></span></div>
                          <?php if (!empty($f['error'])) { ?><div class="dl-bad"><?php echo h($f['error']); ?></div><?php } ?>
                          <?php if (!empty($f['pages'])) { ?><div class="dl-meta"><span><i class="fa fa-file-text-o"></i> <?php echo (int) $f['pages']; ?> pages</span></div><?php } ?>
                          <?php if (!empty($f['entries'])) { ?>
                            <div class="dl-meta"><span><i class="fa fa-folder-open-o"></i> <?php echo (int) (isset($f['entries_total']) ? $f['entries_total'] : count($f['entries'])); ?> file<?php echo (int) (isset($f['entries_total']) ? $f['entries_total'] : count($f['entries'])) == 1 ? '' : 's'; ?> inside the zip</span></div>
                            <div class="dl-zip"><?php foreach ($f['entries'] as $en) { echo '<div><span>' . h($en['name']) . '</span><em>' . h($en['size']) . '</em></div>'; } ?></div>
                          <?php } ?>
                        </div>
                      <?php } ?>

                      <?php if ($dbf || $x['kind'] !== 'auto') { ?>
                        <div class="dl-sec"><i class="fa fa-truck"></i> ALREADY IN THE DELIVERY FOLDER BEFORE THE MOVE <span class="dl-count"><?php echo count($dbf); ?></span><?php if ($coll) { ?> <span class="dl-count" style="background:#fdf3dc;color:#a36b00;"><?php echo (int) $coll; ?> same name</span><?php } ?></div>
                        <?php if (!$dbf) { ?><div class="dl-card ok"><span class="dl-muted">The Delivery folder was empty.</span></div><?php } ?>
                        <?php foreach ($dbf as $d) { ?>
                          <div class="dl-card <?php echo !empty($d['collides']) ? 'warn' : ''; ?>"><div class="dl-row"><b><?php echo h($d['rel']); ?></b>
                            <?php if (!empty($d['collides'])) { ?><span class="dl-sent" style="background:#d68910;"><?php echo !empty($d['same']) ? 'same name, identical content, overwritten' : 'same name, overwritten'; ?></span><?php } ?>
                            <span class="dl-right"><?php echo h($d['size_text']); ?> &middot; <?php echo h($d['modified']); ?></span></div></div>
                        <?php } ?>
                      <?php } ?>

                      <?php if (is_array($cfg) && !empty($cfg['rows'])) { ?>
                        <div class="dl-sec"><i class="fa fa-exchange"></i> MOVE FILES CONFIGURATION &rarr; DELIVERY</div>
                        <?php foreach ($cfg['rows'] as $c) { ?><div class="dl-card <?php echo !empty($c['found']) ? 'ok' : 'warn'; ?>"><b>.<?php echo h($c['ext']); ?></b> &mdash; <?php echo !empty($c['found']) ? h(implode(', ', $c['found'])) : 'not in the folder'; ?></div><?php } ?>
                      <?php } elseif (is_array($cfg) && empty($cfg['configured']) && $x['kind'] !== 'auto') { ?>
                        <div class="dl-sec"><i class="fa fa-exchange"></i> MOVE FILES CONFIGURATION</div><div class="dl-card warn">No file movement was configured for this stage and department.</div>
                      <?php } ?>

                      <?php if (is_array($auto)) { ?>
                        <div class="dl-sec"><i class="fa fa-cloud-upload"></i> <?php echo strtoupper(h($auto['label'])); ?></div>
                        <?php if (empty($auto['package'])) { ?><div class="dl-card bad">No package was found for the automatic upload.</div><?php } ?>
                        <?php foreach ($auto['package'] as $f) { ?>
                          <div class="dl-card <?php echo !empty($f['error']) ? 'bad' : 'ok'; ?>"><div class="dl-row"><b><?php echo h($f['name']); ?></b><span class="dl-right"><?php echo h($f['size_text']); ?> &middot; <?php echo h($f['modified']); ?><?php echo isset($f['where']) ? ' &middot; ' . h($f['where']) : ''; ?></span></div>
                            <?php if (!empty($f['error'])) { ?><div class="dl-bad"><?php echo h($f['error']); ?></div><?php } ?>
                            <?php if (!empty($f['entries'])) { ?><div class="dl-zip"><?php foreach ($f['entries'] as $en) { echo '<div><span>' . h($en['name']) . '</span><em>' . h($en['size']) . '</em></div>'; } ?></div><?php } ?></div>
                        <?php } ?>
                      <?php } ?>

                      <?php if ($errs || $warn) { ?>
                        <div class="dl-sec"><i class="fa fa-exclamation-triangle"></i> CHECKS SHOWN TO THE USER <span class="dl-count"><?php echo count($errs) + count($warn); ?></span></div>
                        <?php foreach ($errs as $e) { ?><div class="dl-card bad"><?php echo h($e); ?></div><?php } ?>
                        <?php foreach ($warn as $w) { ?><div class="dl-card warn"><?php echo h($w); ?></div><?php } ?>
                      <?php } ?>

                      <?php if ($after) { ?>
                        <div class="dl-sec"><i class="fa fa-truck"></i> DELIVERY FOLDER AFTER THE MOVE <span class="dl-count"><?php echo count($after); ?></span></div>
                        <?php foreach ($after as $f) { ?>
                          <div class="dl-card ok"><div class="dl-row"><b><?php echo h(isset($f['rel']) ? $f['rel'] : $f['name']); ?></b><span class="dl-right"><?php echo isset($f['size_text']) ? h($f['size_text']) : ''; ?> &middot; <?php echo isset($f['modified']) ? h($f['modified']) : ''; ?></span></div>
                            <?php if (!empty($f['entries'])) { ?><div class="dl-meta"><span><i class="fa fa-folder-open-o"></i> <?php echo count($f['entries']); ?> file(s) inside</span></div><?php } ?></div>
                        <?php } ?>
                      <?php } ?>
                    </div>
                  </td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
        <?php
        $pg_from = $total_rows ? $offset + 1 : 0; $pg_to = min($offset + $per_page, $total_rows);
        $pg_url = function ($p) use ($base_qs) { return '?' . h($base_qs . ($base_qs !== '' ? '&' : '') . 'page=' . $p); };
        $pg_set = array(1 => 1, $total_pages => $total_pages);
        for ($p = max(1, $page - 2); $p <= min($total_pages, $page + 2); $p++) { $pg_set[$p] = $p; }
        ksort($pg_set);
        ?>
        <div class="dataTables_wrapper" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-top:6px;">
          <div class="dataTables_info">Showing <?php echo (int) $pg_from; ?> to <?php echo (int) $pg_to; ?> of <?php echo (int) $total_rows; ?> entries</div>
          <?php if ($total_pages > 1) { ?>
          <div class="dataTables_paginate paging_simple_numbers">
            <a class="paginate_button previous<?php echo $page <= 1 ? ' disabled' : ''; ?>" <?php if ($page > 1) { ?>href="<?php echo $pg_url($page - 1); ?>"<?php } ?>>Previous</a>
            <span>
              <?php $prev = 0; foreach ($pg_set as $p) {
                if ($prev && $p - $prev > 1) { echo '<span class="ellipsis">&hellip;</span>'; }
                echo '<a class="paginate_button' . ($p == $page ? ' current' : '') . '" href="' . $pg_url($p) . '">' . $p . '</a>';
                $prev = $p;
              } ?>
            </span>
            <a class="paginate_button next<?php echo $page >= $total_pages ? ' disabled' : ''; ?>" <?php if ($page < $total_pages) { ?>href="<?php echo $pg_url($page + 1); ?>"<?php } ?>>Next</a>
          </div>
          <?php } ?>
        </div>
      </div></div>

      <?php echo footer(); ?>
      <?php echo scripts(); ?>
      <script>
        var tg = document.getElementById('afToggle');
        if (tg) { tg.addEventListener('click', function () { tg.classList.toggle('open'); document.getElementById('afPanel').classList.toggle('open'); }); }
        var ovl = document.createElement('div'); ovl.className = 'dl-ovl';
        ovl.innerHTML = '<div class="dl-box"><div class="dl-hd"><span><i class="fa fa-truck"></i> Pre-delivery check</span><button type="button" aria-label="Close" data-close>&times;</button></div><div class="dl-bd"></div><div class="dl-ft"><button type="button" data-close>Close</button></div></div>';
        document.body.appendChild(ovl);
        function dlClose() { ovl.classList.remove('open'); document.body.style.overflow = ''; }
        ovl.addEventListener('click', function (e) { if (e.target === ovl || e.target.hasAttribute('data-close')) { dlClose(); } });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { dlClose(); } });
        document.addEventListener('click', function (e) {
          var b = e.target.closest('.dl-toggle'); if (!b) { return; }
          var pop = b.parentNode.querySelector('.dl-pop'); if (!pop) { return; }
          ovl.querySelector('.dl-bd').innerHTML = pop.innerHTML;
          ovl.querySelector('.dl-bd').scrollTop = 0;
          ovl.classList.add('open'); document.body.style.overflow = 'hidden';
        });
      </script>
    </div>
  </body>
</html>
