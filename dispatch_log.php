<?php include "includes/includes.php";?>
<?php
/* Dispatch log: every Send from the new Project Dispatch page (send_to_client_v2.php) - who sent what to whom, which files (and what is inside them),
   and how it ended.   dispatch_log.php?aid=123 shows one article.   Same look as Current Work (view_articles_new.php): hero, filter bar, tabs, card. */
$db->query("CREATE TABLE IF NOT EXISTS `inw_dispatch_log` (
	`id` int(11) NOT NULL AUTO_INCREMENT, `a_id` int(11) DEFAULT NULL, `pub_id` varchar(100) DEFAULT NULL, `cust_id` int(11) DEFAULT NULL, `j_id` int(11) DEFAULT NULL,
	`stage` varchar(25) DEFAULT NULL, `method` varchar(60) DEFAULT NULL, `user_id` varchar(20) DEFAULT NULL, `user_name` varchar(100) DEFAULT NULL, `ip` varchar(45) DEFAULT NULL,
	`mail_from` varchar(255) DEFAULT NULL, `mail_to` text, `mail_cc` text, `mail_bcc` text, `subject` text,
	`files` mediumtext, `confirm_info` mediumtext, `steps` mediumtext, `status` varchar(15) DEFAULT NULL, `result_message` text,
	`started_at` datetime DEFAULT NULL, `finished_at` datetime DEFAULT NULL, `duration_ms` int(11) DEFAULT NULL,
	PRIMARY KEY (`id`), KEY `a_id` (`a_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
foreach (array('folder' => 'MEDIUMTEXT NULL', 'report_mail' => 'VARCHAR(255) NULL') as $col => $def) {
	$r = $db->query("SHOW COLUMNS FROM inw_dispatch_log LIKE '" . $col . "'");
	if ($r && $r->num_rows == 0) { $db->query("ALTER TABLE inw_dispatch_log ADD COLUMN `" . $col . "` " . $def); }
}

// ---- Filters ----
$f_aid     = isset($_GET['aid'])     ? (int) $_GET['aid']     : 0;
$f_status  = isset($_GET['status'])  ? trim($_GET['status'])  : '';
$f_method  = isset($_GET['method'])  ? trim($_GET['method'])  : '';
$f_cust    = isset($_GET['cust'])    ? (int) $_GET['cust']    : 0;
$f_user    = isset($_GET['user'])    ? trim($_GET['user'])    : '';
$f_article = isset($_GET['article']) ? trim($_GET['article']) : '';
$f_from    = isset($_GET['from'])    ? trim($_GET['from'])    : '';
$f_to      = isset($_GET['to'])      ? trim($_GET['to'])      : '';
$f_mail    = isset($_GET['mail'])    ? trim($_GET['mail'])    : '';
$f_keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
if (!in_array($f_status, array('completed', 'failed', 'finished'), true)) { $f_status = ''; }

/* every filter except the status; the status tabs count against this */
$where = array(); $params = array(); $types = '';
if ($f_aid > 0)        { $where[] = "l.a_id = ?";            $params[] = $f_aid;  $types .= 'i'; }
if ($f_method !== '')  { $where[] = "l.method = ?";          $params[] = $f_method; $types .= 's'; }
if ($f_cust > 0)       { $where[] = "l.cust_id = ?";         $params[] = $f_cust; $types .= 'i'; }
if ($f_user !== '')    { $where[] = "l.user_id = ?";         $params[] = $f_user; $types .= 's'; }
if ($f_article !== '') { $where[] = "l.pub_id LIKE ?";       $params[] = "%$f_article%"; $types .= 's'; }
if ($f_from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_from)) { $where[] = "l.started_at >= ?"; $params[] = $f_from . ' 00:00:00'; $types .= 's'; }
if ($f_to !== ''   && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_to))   { $where[] = "l.started_at <= ?"; $params[] = $f_to . ' 23:59:59';   $types .= 's'; }
if ($f_mail === 'sent')   { $where[] = "l.report_mail = 'Sent'"; }
if ($f_mail === 'notsent'){ $where[] = "l.report_mail LIKE 'Not sent%'"; }
if ($f_mail === 'failed') { $where[] = "l.report_mail LIKE 'Failed%'"; }
if ($f_keyword !== '') {
	$where[] = "(l.mail_to LIKE ? OR l.mail_cc LIKE ? OR l.subject LIKE ? OR l.files LIKE ? OR l.folder LIKE ? OR l.steps LIKE ?)";
	for ($i = 0; $i < 6; $i++) { $params[] = "%$f_keyword%"; $types .= 's'; }
}
$base_where = $where ? ' WHERE ' . implode(' AND ', $where) : '';

// ---- tab counts (all statuses, same other filters) ----
$tab = array('all' => 0, 'completed' => 0, 'failed' => 0, 'finished' => 0);
$stmt = $db->prepare("SELECT l.status, COUNT(*) FROM inw_dispatch_log l" . $base_where . " GROUP BY l.status");
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute(); $stmt->bind_result($st_name, $st_n);
while ($stmt->fetch()) { $k = isset($tab[$st_name]) ? $st_name : 'finished'; $tab[$k] += $st_n; $tab['all'] += $st_n; }
$stmt->close();

// the list: the status tab on top of the other filters
$lw = $where; $lp = $params; $lt = $types;
if ($f_status !== '') { $lw[] = "l.status = ?"; $lp[] = $f_status; $lt .= 's'; }
$where_sql = $lw ? ' WHERE ' . implode(' AND ', $lw) : '';
$total_rows = $f_status !== '' ? $tab[$f_status] : $tab['all'];

// ---- Paging ----
$per_page = 25;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$total_pages = max(1, (int) ceil($total_rows / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

$rows = array();
$stmt = $db->prepare("SELECT l.*, c.cust_name, j.j_code FROM inw_dispatch_log l
	LEFT JOIN adm_customer_master c ON c.id = l.cust_id LEFT JOIN adm_journals j ON j.j_id = l.j_id" . $where_sql . " ORDER BY l.id DESC LIMIT $per_page OFFSET $offset");
if ($lp) { $stmt->bind_param($lt, ...$lp); }
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) { $rows[] = $r; }
$stmt->close();

// ---- Dropdown values ----
$methods = array(); $res = $db->query("SELECT DISTINCT method FROM inw_dispatch_log WHERE method <> '' ORDER BY method");
while ($res && ($r = $res->fetch_assoc())) { $methods[] = $r['method']; }
$custs = array(); $res = $db->query("SELECT DISTINCT l.cust_id, c.cust_name FROM inw_dispatch_log l LEFT JOIN adm_customer_master c ON c.id = l.cust_id ORDER BY c.cust_name");
while ($res && ($r = $res->fetch_assoc())) { $custs[] = $r; }
$users_dd = array(); $res = $db->query("SELECT DISTINCT user_id, user_name FROM inw_dispatch_log ORDER BY user_name");
while ($res && ($r = $res->fetch_assoc())) { $users_dd[] = $r; }

$qs = $_GET; unset($qs['page']);
$base_qs = http_build_query($qs);
$tab_qs = function ($status) { $q = $_GET; unset($q['page']); if ($status === '') { unset($q['status']); } else { $q['status'] = $status; } return '?' . http_build_query($q); };
function h($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
/* rows saved before the label existed say "option 6" */
function dl_method($m) {
	$map = array(1 => 'FTP upload & mail', 2 => 'Only FTP & manual mail', 3 => 'Attach file & mail', 4 => 'xProof upload & mail', 5 => 'EProofing', 6 => 'Xperttrack');
	return preg_match('/^option (\d+)$/', (string) $m, $x) && isset($map[(int) $x[1]]) ? $map[(int) $x[1]] : (string) $m;
}
function dl_initials($n) { $p = preg_split('/\s+/', trim((string) $n)); $i = ''; foreach (array_slice($p, 0, 2) as $w) { $i .= strtoupper(substr($w, 0, 1)); } return $i !== '' ? $i : '?'; }
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
      .af-tab{text-decoration:none !important;}
      .af-card td.dl-detail{white-space:normal !important;padding:14px 18px !important;background:#fbfcfe !important;border-radius:12px !important;border:1px solid #edf1f5 !important;}
      .af-card tr.dl-detail-row{display:none;box-shadow:none !important;transform:none !important;} .af-card tr.dl-detail-row:hover td{background:#fbfcfe !important;}
      .dl-det{font-size:12.5px;line-height:1.55;color:#22303f;text-align:left;display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:14px 26px;}
      .dl-det h6{margin:0 0 4px;font-size:11px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#14304a;}
      .dl-det table{width:100%;font-size:12.5px;border-collapse:collapse;} .dl-det td{padding:2px 12px 2px 0;vertical-align:top;border:0 !important;background:transparent !important;white-space:normal !important;}
      .dl-det td:first-child{color:#7a869a;white-space:nowrap !important;}
      .dl-det details>summary{cursor:pointer;color:#1b6ca8;} .dl-det .zl{max-height:150px;overflow:auto;font-size:11.5px;background:#fff;border:1px solid #e4e9ef;border-radius:8px;padding:4px 8px;margin:3px 0 5px;}
      .dl-det .steps{white-space:pre-wrap;font-family:Consolas,monospace;font-size:11.5px;background:#fff;border:1px solid #e4e9ef;border-radius:8px;padding:8px 10px;}
      .dl-sent{display:inline-block;padding:0 8px;border-radius:10px;background:#2e8b57;color:#fff;font-size:10.5px;font-weight:700;margin-left:4px;}
      .dl-bad{color:#c0392b;} .dl-warn{color:#b87800;} .dl-muted{color:#8a97ab;}
      .dl-fail{font-size:11.5px;color:#c0392b;max-width:230px;white-space:normal;margin-top:3px;line-height:1.3;}
      .af-card .table-responsive{overflow-x:auto;}
      /* details popup: same look as the Comments/Instructions popup on Current Work */
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
      .dl-kv{width:100%;} .dl-kv td{padding:3px 0;vertical-align:top;word-break:break-word;border:0 !important;background:transparent !important;white-space:normal !important;} .dl-kv td:first-child{width:78px;color:#7a869a;font-weight:600;padding-right:12px;white-space:nowrap !important;}
      .dl-meta{margin-top:2px;color:#667;} .dl-meta span{display:inline-block;margin:0 14px 2px 0;} .dl-meta .fa{margin-right:4px;color:#8a97ab;}
      .dl-row{display:flex;align-items:center;gap:8px;flex-wrap:wrap;} .dl-right{margin-left:auto;color:#5b6b7b;font-size:12px;}
      .dl-zip{margin-top:6px;max-height:170px;overflow:auto;background:#fafbfd;border:1px solid #eef1f6;border-radius:8px;font-size:12px;}
      .dl-zip div{display:flex;justify-content:space-between;gap:10px;padding:4px 10px;border-top:1px solid #f0f3f8;} .dl-zip div:first-child{border-top:0;} .dl-zip em{font-style:normal;color:#8a97ab;white-space:nowrap;}
      .dl-steps{white-space:pre-wrap;font-family:Consolas,monospace;font-size:11.5px;color:#33414f;}
    </style>
  </head>

  <body>
    <div class="wholeouter">
      <?php include("includes/top-header.php");?>

      <div class="af-page" style="padding-bottom:0;">
        <div class="af-hero">
          <div><small>Project Dispatch</small><h1>Dispatch Log <span style="font-size:13px;font-weight:600;opacity:.8;">&middot; <?php echo (int) $tab['all']; ?> entr<?php echo $tab['all'] == 1 ? 'y' : 'ies'; ?></span></h1></div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;"><?php if ($f_aid > 0) { ?><a class="af-add" href="dispatchlog"><i class="fa fa-list"></i> All articles</a><?php } ?><a class="af-add" href="deliverychecklog"><i class="fa fa-truck"></i> Pre-delivery Checks</a></div>
        </div>
      </div>

      <?php
      /* what is set in the collapsible panel (the same idea as the chip row on Current Work); each chip's x removes just that filter */
      $dl_chips = array();
      $dl_drop = function ($key) { $q = $_GET; unset($q['page']); unset($q[$key]); $s = http_build_query($q); return '?' . $s; };
      $cust_name = ''; foreach ($custs as $c) { if ((int) $c['cust_id'] === $f_cust) { $cust_name = $c['cust_name'] !== null && $c['cust_name'] !== '' ? $c['cust_name'] : 'customer ' . $c['cust_id']; } }
      $user_name_f = ''; foreach ($users_dd as $u) { if ((string) $u['user_id'] === $f_user) { $user_name_f = $u['user_name'] !== '' ? $u['user_name'] : 'user_id ' . $u['user_id']; } }
      if ($f_method !== '')  { $dl_chips[] = array('Method', dl_method($f_method), 'method'); }
      if ($f_cust > 0)       { $dl_chips[] = array('Customer', $cust_name !== '' ? $cust_name : 'customer ' . $f_cust, 'cust'); }
      if ($f_user !== '')    { $dl_chips[] = array('Sent by', $user_name_f !== '' ? $user_name_f : 'user_id ' . $f_user, 'user'); }
      if ($f_mail !== '')    { $dl_chips[] = array('Report email', $f_mail === 'sent' ? 'Sent' : ($f_mail === 'notsent' ? 'Not sent' : 'Failed'), 'mail'); }
      if ($f_keyword !== '') { $dl_chips[] = array('Search', $f_keyword, 'keyword'); }
      ?>
      <div class="af-page"><div class="af-wrap">
        <form action="" method="get" class="formouter">
          <?php if ($f_aid > 0) { ?><input type="hidden" name="aid" value="<?php echo (int) $f_aid; ?>"><?php } ?>
          <?php if ($f_status !== '') { ?><input type="hidden" name="status" value="<?php echo h($f_status); ?>"><?php } ?>
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
              <button type="button" class="af-btn <?php echo $dl_chips ? 'has' : ''; ?>" id="afToggle"><i class="fa fa-sliders"></i> Filters <span class="af-count"><?php echo count($dl_chips); ?></span> <i class="fa fa-angle-down"></i></button>
              <button type="submit" class="af-btn primary"><i class="fa fa-search"></i> Get Result</button>
              <a class="af-btn" href="<?php echo $f_aid > 0 ? 'dispatchlog?aid=' . (int) $f_aid : 'dispatchlog'; ?>" title="Reset filters"><i class="fa fa-refresh"></i></a>
            </div>
          </div>
          <div class="af-panel <?php echo $dl_chips ? 'open' : ''; ?>" id="afPanel">
            <div class="af-grid">
              <div class="af-field">
                <label class="mb-2">Method</label>
                <select name="method" class="form-select">
                  <option value="">All</option>
                  <?php foreach ($methods as $m) { ?><option value="<?php echo h($m); ?>" <?php if ($m === $f_method) echo 'selected'; ?>><?php echo h(dl_method($m)); ?></option><?php } ?>
                </select>
              </div>
              <div class="af-field">
                <label class="mb-2">Customer</label>
                <select name="cust" class="form-select">
                  <option value="0">All</option>
                  <?php foreach ($custs as $c) { ?><option value="<?php echo (int) $c['cust_id']; ?>" <?php if ((int) $c['cust_id'] === $f_cust) echo 'selected'; ?>><?php echo h($c['cust_name'] !== null && $c['cust_name'] !== '' ? $c['cust_name'] : 'customer ' . $c['cust_id']); ?></option><?php } ?>
                </select>
              </div>
              <div class="af-field">
                <label class="mb-2">Sent by</label>
                <select name="user" class="form-select">
                  <option value="">All</option>
                  <?php foreach ($users_dd as $u) { ?><option value="<?php echo h($u['user_id']); ?>" <?php if ((string) $u['user_id'] === $f_user) echo 'selected'; ?>><?php echo h($u['user_name'] !== '' ? $u['user_name'] : 'user_id ' . $u['user_id']); ?></option><?php } ?>
                </select>
              </div>
              <div class="af-field">
                <label class="mb-2">Report email</label>
                <select name="mail" class="form-select">
                  <option value="">All</option>
                  <option value="sent" <?php if ($f_mail === 'sent') echo 'selected'; ?>>Sent</option>
                  <option value="notsent" <?php if ($f_mail === 'notsent') echo 'selected'; ?>>Not sent</option>
                  <option value="failed" <?php if ($f_mail === 'failed') echo 'selected'; ?>>Failed</option>
                </select>
              </div>
              <div class="af-field">
                <label class="mb-2">Search</label>
                <input type="text" name="keyword" class="form-control" value="<?php echo h($f_keyword); ?>" placeholder="recipient, subject, file, step">
              </div>
            </div>
          </div>
          <?php if ($dl_chips) { ?>
          <div class="af-chips" id="afChips">
            <?php foreach ($dl_chips as $ch) { ?>
              <span class="af-chip"><?php echo h($ch[0]); ?>: <b><?php echo h($ch[1]); ?></b> <a href="<?php echo h($dl_drop($ch[2])); ?>" title="Remove" style="color:inherit;text-decoration:none;font-size:15px;line-height:1;">&times;</a></span>
            <?php } ?>
            <?php if (count($dl_chips) > 1) { ?><a class="af-chip clear" href="<?php echo $f_aid > 0 ? 'dispatchlog?aid=' . (int) $f_aid : 'dispatchlog'; ?>" style="text-decoration:none;">Clear all</a><?php } ?>
          </div>
          <?php } ?>
        </form>
      </div></div>

      <div class="af-page">
        <div class="af-tabs" role="tablist" aria-label="Status">
          <a class="af-tab <?php echo $f_status === '' ? 'active' : ''; ?>" href="<?php echo h($tab_qs('')); ?>"><i class="fa fa-th-list"></i> All <b><?php echo (int) $tab['all']; ?></b></a>
          <a class="af-tab d4 <?php echo $f_status === 'completed' ? 'active' : ''; ?>" href="<?php echo h($tab_qs('completed')); ?>"><i class="fa fa-check-circle"></i> Completed <b><?php echo (int) $tab['completed']; ?></b></a>
          <a class="af-tab high <?php echo $f_status === 'failed' ? 'active' : ''; ?>" href="<?php echo h($tab_qs('failed')); ?>"><i class="fa fa-times-circle"></i> Failed <b><?php echo (int) $tab['failed']; ?></b></a>
          <a class="af-tab today <?php echo $f_status === 'finished' ? 'active' : ''; ?>" href="<?php echo h($tab_qs('finished')); ?>"><i class="fa fa-question-circle"></i> No clear result <b><?php echo (int) $tab['finished']; ?></b></a>
        </div>
      </div>

      <div class="af-page"><div class="af-card">
        <div class="table-responsive">
          <table class="table custtable table-primary dataTable" style="width:100% !important;">
            <thead>
              <tr>
                <th>#</th>
                <th>Date / Time</th>
                <th>Status</th>
                <th>Article</th>
                <th>Customer / Journal</th>
                <th>Method</th>
                <th>Sent by</th>
                <th>Files</th>
                <th>Report email</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$rows) { ?>
                <tr><td class="dataTables_empty" colspan="10">No dispatches found.</td></tr>
              <?php } ?>
              <?php foreach ($rows as $n => $x) {
                $files = json_decode($x['files'], true); if (!is_array($files)) { $files = array(); }
                $snap  = !empty($x['folder']) ? json_decode($x['folder'], true) : array(); if (!is_array($snap)) { $snap = array(); }
                $steps = json_decode($x['steps'], true); if (!is_array($steps)) { $steps = array(); }
                $cf    = json_decode($x['confirm_info'], true);
                $sent_n = 0; foreach ($snap as $sf) { if (!empty($sf['sent'])) { $sent_n++; } }
                if (!$snap) { $sent_n = count($files); }
                $stc = $x['status'] === 'completed' ? '#1e9e61' : ($x['status'] === 'failed' ? '#d64545' : '#d98a06');
                $stt = $x['status'] === 'completed' ? 'Completed' : ($x['status'] === 'failed' ? 'Failed' : 'No clear result');
                $rowcls = $x['status'] === 'failed' ? 'af-row-high' : ($x['status'] === 'completed' ? '' : 'af-row-warn');
                $who = $x['user_name'] !== '' ? $x['user_name'] : 'user_id ' . $x['user_id'];
                $ts = strtotime($x['started_at']);
              ?>
                <tr class="af-in <?php echo $rowcls; ?>" style="--i:<?php echo (int) $n; ?>">
                  <td><span class="dl-muted"><?php echo (int) $x['id']; ?></span></td>
                  <td><span class="af-date"><b><?php echo h($ts ? date('d-M-Y', $ts) : ''); ?></b><small><?php echo h($ts ? date('h:i A', $ts) : ''); ?> &middot; <?php echo round((int) $x['duration_ms'] / 1000, 1); ?> s</small></span></td>
                  <td><span class="af-status" style="--c:<?php echo $stc; ?>"><?php echo $stt; ?></span><?php if ($x['status'] === 'failed' && $x['result_message'] !== '') { ?><div class="dl-fail"><?php echo h($x['result_message']); ?></div><?php } ?></td>
                  <td><a class="af-art" href="?aid=<?php echo (int) $x['a_id']; ?>" style="text-decoration:none;"><?php echo h($x['pub_id']); ?></a><br><span class="af-stage" style="--c:#1b6ca8"><?php echo h($x['stage']); ?></span></td>
                  <td><span class="af-chip-j"><?php echo h($x['cust_name']); ?> / <?php echo h($x['j_code']); ?></span></td>
                  <td><?php echo h(dl_method($x['method'])); ?><br><small class="dl-muted">to <?php echo h($x['mail_to']); ?></small></td>
                  <td><span class="af-person"><span class="af-av"><?php echo h(dl_initials($x['user_name'])); ?></span><?php echo h($who); ?></span><br><small class="dl-muted"><?php echo h($x['ip']); ?></small></td>
                  <td><span class="af-num"><?php echo (int) $sent_n; ?></span> sent<?php if ($snap) { ?><br><small class="dl-muted"><?php echo count($snap); ?> in folder</small><?php } ?></td>
                  <td><span class="af-sub"><?php echo h(isset($x['report_mail']) && $x['report_mail'] !== '' ? $x['report_mail'] : '-'); ?></span></td>
                  <td>
                    <button type="button" class="af-btn dl-toggle"><i class="fa fa-eye"></i> View</button>
                    <div class="dl-pop" style="display:none">
                      <div class="dl-hero">
                        <div class="dl-hero-k">ARTICLE</div>
                        <div class="dl-hero-t"><?php echo h($x['pub_id']); ?></div>
                        <div class="dl-hero-s"><?php echo h($x['cust_name']); ?> &middot; <?php echo h($x['j_code']); ?> &middot; stage <?php echo h($x['stage']); ?> &middot; <?php echo h(dl_method($x['method'])); ?></div>
                        <div class="dl-pills">
                          <span class="dl-pill <?php echo $x['status'] === 'completed' ? 'ok' : ($x['status'] === 'failed' ? 'bad' : 'warn'); ?>"><b><?php echo $stt; ?></b></span>
                          <span class="dl-pill"><b><?php echo (int) $sent_n; ?></b> file<?php echo $sent_n == 1 ? '' : 's'; ?> sent</span>
                          <span class="dl-pill"><b><?php echo $snap ? count($snap) : 0; ?></b> in folder</span>
                          <span class="dl-pill"><b><?php echo round((int) $x['duration_ms'] / 1000, 1); ?></b> s</span>
                          <span class="dl-pill">report mail: <b><?php echo h(isset($x['report_mail']) && $x['report_mail'] !== '' ? $x['report_mail'] : '-'); ?></b></span>
                        </div>
                        <div class="dl-hero-s" style="margin-top:8px;"><?php echo h($who); ?> &middot; <?php echo h($x['ip']); ?> &middot; <?php echo h($ts ? date('d M Y h:i A', $ts) : ''); ?></div>
                      </div>
                      <?php if ($x['status'] === 'failed' && $x['result_message'] !== '') { ?>
                        <div class="dl-card bad"><i class="fa fa-times-circle"></i> <?php echo h($x['result_message']); ?></div>
                      <?php } ?>

                      <div class="dl-sec"><i class="fa fa-paper-plane-o"></i> MAIL</div>
                      <div class="dl-card">
                        <table class="dl-kv">
                          <tr><td>Subject</td><td><?php echo h($x['subject']); ?></td></tr>
                          <tr><td>From</td><td><?php echo h($x['mail_from']); ?></td></tr>
                          <tr><td>To</td><td><?php echo h($x['mail_to']); ?></td></tr>
                          <?php if ($x['mail_cc'] !== '') { ?><tr><td>Cc</td><td><?php echo h($x['mail_cc']); ?></td></tr><?php } ?>
                          <?php if ($x['mail_bcc'] !== '') { ?><tr><td>Bcc</td><td><?php echo h($x['mail_bcc']); ?></td></tr><?php } ?>
                        </table>
                      </div>

                      <div class="dl-sec"><i class="fa fa-paperclip"></i> FILES SENT <span class="dl-count"><?php echo count($files); ?></span></div>
                      <?php if (!$files) { ?><div class="dl-card"><span class="dl-muted">No file was recorded for this dispatch.</span></div><?php } ?>
                      <?php foreach ($files as $f) { ?>
                        <div class="dl-card ok">
                          <b><?php echo h($f['name']); ?></b>
                          <div class="dl-meta">
                            <?php if (isset($f['size_text'])) { ?><span><i class="fa fa-hdd-o"></i> <?php echo h($f['size_text']); ?></span><span><i class="fa fa-clock-o"></i> Modified <?php echo h($f['modified']); ?></span><?php } ?>
                            <?php if (isset($f['pages'])) { ?><span><i class="fa fa-file-text-o"></i> <?php echo (int) $f['pages']; ?> pages</span><?php } ?>
                            <?php if (isset($f['count'])) { ?><span><i class="fa fa-folder-open-o"></i> <?php echo (int) $f['count']; ?> file<?php echo (int) $f['count'] == 1 ? '' : 's'; ?> inside</span><?php } ?>
                            <?php if (isset($f['note'])) { ?><span><?php echo h($f['note']); ?></span><?php } ?>
                          </div>
                          <?php if (isset($f['md5'])) { ?><div class="dl-muted" style="font-size:11.5px;margin-top:2px;">md5 <?php echo h($f['md5']); ?></div><?php } ?>
                        </div>
                      <?php } ?>

                      <?php if ($snap) { ?>
                        <div class="dl-sec"><i class="fa fa-folder-o"></i> DELIVERY FOLDER AT DISPATCH TIME <span class="dl-count"><?php echo count($snap); ?></span></div>
                        <?php foreach ($snap as $sf) { ?>
                          <div class="dl-card <?php echo !empty($sf['error']) ? 'bad' : (!empty($sf['sent']) ? 'ok' : ''); ?>">
                            <div class="dl-row"><b><?php echo h($sf['name']); ?></b><?php if (!empty($sf['sent'])) { ?> <span class="dl-sent">sent</span><?php } ?>
                              <span class="dl-right"><?php echo isset($sf['size_text']) ? h($sf['size_text']) : ''; ?> &middot; <?php echo isset($sf['modified']) ? h($sf['modified']) : ''; ?></span></div>
                            <?php if (!empty($sf['error'])) { ?><div class="dl-bad"><?php echo h($sf['error']); ?></div><?php } ?>
                            <?php if (!empty($sf['entries'])) { ?>
                              <div class="dl-meta"><span><i class="fa fa-folder-open-o"></i> <?php echo (int) (isset($sf['entries_total']) ? $sf['entries_total'] : count($sf['entries'])); ?> file<?php echo (int) (isset($sf['entries_total']) ? $sf['entries_total'] : count($sf['entries'])) == 1 ? '' : 's'; ?> inside the zip</span></div>
                              <div class="dl-zip"><?php foreach ($sf['entries'] as $en) { echo '<div><span>' . h($en['name']) . '</span><em>' . h($en['size']) . '</em></div>'; } ?></div>
                            <?php } ?>
                          </div>
                        <?php } ?>
                      <?php } ?>

                      <?php if (is_array($cf) && !empty($cf['warnings'])) { ?>
                        <div class="dl-sec"><i class="fa fa-exclamation-triangle"></i> WARNINGS SHOWN TO THE USER <span class="dl-count"><?php echo count($cf['warnings']); ?></span></div>
                        <?php foreach ($cf['warnings'] as $w) { ?><div class="dl-card warn"><?php echo h($w); ?></div><?php } ?>
                        <?php if (!empty($cf['confirmed_at'])) { ?><div class="dl-muted" style="margin:-2px 0 4px 4px;font-size:12px;">Confirmed in the package dialog: <?php echo h($cf['confirmed_at']); ?></div><?php } ?>
                      <?php } ?>

                      <div class="dl-sec"><i class="fa fa-history"></i> WHAT HAPPENED <span class="dl-count"><?php echo count($steps); ?></span></div>
                      <div class="dl-card"><div class="dl-steps"><?php echo $steps ? h(implode("\n", $steps)) : '-'; ?></div></div>
                    </div>
                  </td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>

        <?php
        /* same pager as Current Work (DataTables look): "Showing x to y of N entries", Previous, page numbers with ellipsis, Next */
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
        ovl.innerHTML = '<div class="dl-box"><div class="dl-hd"><span><i class="fa fa-paper-plane-o"></i> Dispatch details</span><button type="button" aria-label="Close" data-close>&times;</button></div><div class="dl-bd"></div><div class="dl-ft"><button type="button" data-close>Close</button></div></div>';
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
