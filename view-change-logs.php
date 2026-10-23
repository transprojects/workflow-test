<?php include "includes/includes.php";?>
<?php
include_once "includes/change_log_mail.php";
ensure_change_logs_table($db);
/* Change Logs - same look as Current Work / Dispatch Log: hero, filter bar with a collapsible panel, action tabs, card table, details popup. */

// ---- Filters ----
$f_module  = isset($_GET['module'])  ? trim($_GET['module'])  : '';
$f_user    = isset($_GET['user'])    ? (int) $_GET['user']    : 0;
$f_record  = isset($_GET['record'])  ? trim($_GET['record'])  : '';
$f_from    = isset($_GET['from'])    ? trim($_GET['from'])    : '';
$f_to      = isset($_GET['to'])      ? trim($_GET['to'])      : '';
$f_keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$f_action  = isset($_GET['action']) ? trim($_GET['action']) : '';
$f_mail    = isset($_GET['mail'])    ? trim($_GET['mail'])    : '';
if (!in_array($f_action, array('ADD', 'EDIT', 'DELETE'), true)) { $f_action = ''; }

/* every filter except the action; the action tabs count against this */
$where = array(); $params = array(); $types = '';
if ($f_module !== '')  { $where[] = "module = ?";          $params[] = $f_module;  $types .= 's'; }
if ($f_user > 0)       { $where[] = "changed_by = ?";      $params[] = $f_user;    $types .= 'i'; }
if ($f_record !== '')  { $where[] = "record_id LIKE ?";    $params[] = "%$f_record%"; $types .= 's'; }
if ($f_from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_from)) { $where[] = "created_at >= ?"; $params[] = $f_from . ' 00:00:00'; $types .= 's'; }
if ($f_to !== ''   && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_to))   { $where[] = "created_at <= ?"; $params[] = $f_to . ' 23:59:59';   $types .= 's'; }
if ($f_keyword !== '') { $where[] = "details LIKE ?";      $params[] = "%$f_keyword%"; $types .= 's'; }
if ($f_mail === '1' || $f_mail === '0') { $where[] = "mail_sent = ?"; $params[] = (int) $f_mail; $types .= 'i'; }
$base_where = $where ? ' WHERE ' . implode(' AND ', $where) : '';

// ---- tab counts ----
$tab = array('all' => 0, 'ADD' => 0, 'EDIT' => 0, 'DELETE' => 0);
$stmt = $db->prepare("SELECT action, COUNT(*) FROM change_logs" . $base_where . " GROUP BY action");
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute(); $stmt->bind_result($ac_name, $ac_n);
while ($stmt->fetch()) { if (isset($tab[$ac_name])) { $tab[$ac_name] += $ac_n; } $tab['all'] += $ac_n; }
$stmt->close();

$lw = $where; $lp = $params; $lt = $types;
if ($f_action !== '') { $lw[] = "action = ?"; $lp[] = $f_action; $lt .= 's'; }
$where_sql = $lw ? ' WHERE ' . implode(' AND ', $lw) : '';
$total_rows = $f_action !== '' ? $tab[$f_action] : $tab['all'];

// ---- Paging ----
$per_page = 25;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$total_pages = max(1, (int) ceil($total_rows / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

$rows = array();
$stmt = $db->prepare("SELECT * FROM change_logs" . $where_sql . " ORDER BY id DESC LIMIT $per_page OFFSET $offset");
if ($lp) { $stmt->bind_param($lt, ...$lp); }
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) { $rows[] = $r; }
$stmt->close();

// ---- Dropdown values ----
$modules = array();
$res = $db->query("SELECT DISTINCT module FROM change_logs ORDER BY module");
while ($r = $res->fetch_assoc()) { $modules[] = $r['module']; }
$users_dd = array();
$res = $db->query("SELECT DISTINCT changed_by, changed_by_name FROM change_logs ORDER BY changed_by_name");
while ($r = $res->fetch_assoc()) { $users_dd[] = $r; }

$qs = $_GET; unset($qs['page']);
$base_qs = http_build_query($qs);
$tab_qs = function ($a) { $q = $_GET; unset($q['page']); if ($a === '') { unset($q['action']); } else { $q['action'] = $a; } return '?' . http_build_query($q); };
function h($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
function cl_initials($n) { $p = preg_split('/\s+/', trim((string) $n)); $i = ''; foreach (array_slice($p, 0, 2) as $w) { $i .= strtoupper(substr($w, 0, 1)); } return $i !== '' ? $i : '?'; }

/* chips for what is set in the collapsible panel */
$cl_chips = array();
$cl_drop = function ($key) { $q = $_GET; unset($q['page']); unset($q[$key]); return '?' . http_build_query($q); };
$user_label = ''; foreach ($users_dd as $u) { if ((int) $u['changed_by'] === $f_user) { $user_label = $u['changed_by_name'] !== '' ? $u['changed_by_name'] : 'user_id ' . $u['changed_by']; } }
if ($f_module !== '')  { $cl_chips[] = array('Module', $f_module, 'module'); }
if ($f_user > 0)       { $cl_chips[] = array('Changed by', $user_label !== '' ? $user_label : 'user_id ' . $f_user, 'user'); }
if ($f_mail !== '')    { $cl_chips[] = array('Email', $f_mail === '1' ? 'Sent' : 'Not sent', 'mail'); }
if ($f_keyword !== '') { $cl_chips[] = array('Search', $f_keyword, 'keyword'); }
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
      .af-card .table-responsive{overflow-x:auto;}
      .dl-muted{color:#8a97ab;}
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
      .dl-pill.ok{background:rgba(46,139,87,.55);} .dl-pill.warn{background:rgba(214,137,16,.6);} .dl-pill.bad{background:rgba(192,57,43,.7);} .dl-pill.info{background:rgba(42,127,193,.6);}
      .dl-sec{margin:18px 0 8px;font-size:12px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:#1d3557;text-align:left;} .dl-sec .fa{margin-right:4px;color:#2c5f9e;}
      .dl-card{background:#fff;border-left:4px solid #2c5f9e;border-radius:12px;padding:11px 14px;margin-bottom:9px;box-shadow:0 1px 4px rgba(20,35,60,.1);text-align:left;font-size:13px;color:#223;}
      .dl-kv{width:100%;} .dl-kv td{padding:3px 0;vertical-align:top;word-break:break-word;border:0 !important;background:transparent !important;white-space:normal !important;} .dl-kv td:first-child{width:96px;color:#7a869a;font-weight:600;padding-right:12px;white-space:nowrap !important;}
      .dl-steps{white-space:pre-wrap;font-family:Consolas,monospace;font-size:12px;color:#33414f;}
    </style>
  </head>
  <body>
    <div class="wholeouter">
      <?php include("includes/top-header.php");?>

      <div class="af-page" style="padding-bottom:0;">
        <div class="af-hero">
          <div><small>Audit trail</small><h1>Change Logs <span style="font-size:13px;font-weight:600;opacity:.8;">&middot; <?php echo (int) $tab['all']; ?> entr<?php echo $tab['all'] == 1 ? 'y' : 'ies'; ?></span></h1></div>
        </div>
      </div>

      <div class="af-page"><div class="af-wrap">
        <form action="" method="get" class="formouter">
          <?php if ($f_action !== '') { ?><input type="hidden" name="action" value="<?php echo h($f_action); ?>"><?php } ?>
          <div class="af-bar">
            <div class="af-field search">
              <label class="mb-2">Record ID</label>
              <input class="form-control" type="text" name="record" value="<?php echo h($f_record); ?>" placeholder="e.g. 42 or article name">
            </div>
            <div class="af-field date dvr-field">
              <label class="mb-2">Date range</label>
              <div class="dvr" data-format="ymd" data-from-name="from" data-to-name="to" data-from="<?php echo h($f_from); ?>" data-to="<?php echo h($f_to); ?>"></div>
            </div>
            <link rel="stylesheet" href="dv-rangepicker.css?v=1"><script src="dv-rangepicker.js?v=1"></script>
            <div class="af-actions">
              <button type="button" class="af-btn <?php echo $cl_chips ? 'has' : ''; ?>" id="afToggle"><i class="fa fa-sliders"></i> Filters <span class="af-count"><?php echo count($cl_chips); ?></span> <i class="fa fa-angle-down"></i></button>
              <button type="submit" class="af-btn primary"><i class="fa fa-search"></i> Get Result</button>
              <a class="af-btn" href="changelogs" title="Reset filters"><i class="fa fa-refresh"></i></a>
            </div>
          </div>
          <div class="af-panel <?php echo $cl_chips ? 'open' : ''; ?>" id="afPanel">
            <div class="af-grid">
              <div class="af-field">
                <label class="mb-2">Module</label>
                <select name="module" class="form-select">
                  <option value="">All</option>
                  <?php foreach ($modules as $m) { ?><option value="<?php echo h($m); ?>" <?php if ($m === $f_module) echo 'selected'; ?>><?php echo h($m); ?></option><?php } ?>
                </select>
              </div>
              <div class="af-field">
                <label class="mb-2">Changed by</label>
                <select name="user" class="form-select">
                  <option value="0">All</option>
                  <?php foreach ($users_dd as $u) { ?><option value="<?php echo (int) $u['changed_by']; ?>" <?php if ((int) $u['changed_by'] === $f_user) echo 'selected'; ?>><?php echo h($u['changed_by_name'] !== '' ? $u['changed_by_name'] : 'user_id ' . $u['changed_by']); ?></option><?php } ?>
                </select>
              </div>
              <div class="af-field">
                <label class="mb-2">Email</label>
                <select name="mail" class="form-select">
                  <option value="">All</option>
                  <option value="1" <?php if ($f_mail === '1') echo 'selected'; ?>>Sent</option>
                  <option value="0" <?php if ($f_mail === '0') echo 'selected'; ?>>Not sent</option>
                </select>
              </div>
              <div class="af-field" style="grid-column:span 2;">
                <label class="mb-2">Search in changes (field / value)</label>
                <input type="text" name="keyword" class="form-control" value="<?php echo h($f_keyword); ?>" placeholder="e.g. at_title or a value">
              </div>
            </div>
          </div>
          <?php if ($cl_chips) { ?>
          <div class="af-chips" id="afChips">
            <?php foreach ($cl_chips as $ch) { ?>
              <span class="af-chip"><?php echo h($ch[0]); ?>: <b><?php echo h($ch[1]); ?></b> <a href="<?php echo h($cl_drop($ch[2])); ?>" title="Remove" style="color:inherit;text-decoration:none;font-size:15px;line-height:1;">&times;</a></span>
            <?php } ?>
            <?php if (count($cl_chips) > 1) { ?><a class="af-chip clear" href="changelogs" style="text-decoration:none;">Clear all</a><?php } ?>
          </div>
          <?php } ?>
        </form>
      </div></div>

      <div class="af-page">
        <div class="af-tabs" role="tablist" aria-label="Action">
          <a class="af-tab <?php echo $f_action === '' ? 'active' : ''; ?>" href="<?php echo h($tab_qs('')); ?>"><i class="fa fa-th-list"></i> All <b><?php echo (int) $tab['all']; ?></b></a>
          <a class="af-tab d4 <?php echo $f_action === 'ADD' ? 'active' : ''; ?>" href="<?php echo h($tab_qs('ADD')); ?>"><i class="fa fa-plus-circle"></i> Added <b><?php echo (int) $tab['ADD']; ?></b></a>
          <a class="af-tab today <?php echo $f_action === 'EDIT' ? 'active' : ''; ?>" href="<?php echo h($tab_qs('EDIT')); ?>"><i class="fa fa-pencil"></i> Edited <b><?php echo (int) $tab['EDIT']; ?></b></a>
          <a class="af-tab high <?php echo $f_action === 'DELETE' ? 'active' : ''; ?>" href="<?php echo h($tab_qs('DELETE')); ?>"><i class="fa fa-trash"></i> Deleted <b><?php echo (int) $tab['DELETE']; ?></b></a>
        </div>
      </div>

      <div class="af-page"><div class="af-card">
        <div class="table-responsive">
          <table class="table custtable table-primary dataTable" style="width:100% !important;">
            <thead>
              <tr>
                <th>#</th>
                <th>Date / Time</th>
                <th>Action</th>
                <th>Module</th>
                <th>Record ID</th>
                <th>Changed by</th>
                <th>Changes</th>
                <th>Email</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$rows) { ?>
                <tr><td class="dataTables_empty" colspan="9">No change logs found.</td></tr>
              <?php } ?>
              <?php foreach ($rows as $n => $r) {
                $ac = $r['action'];
                $acc = $ac === 'ADD' ? '#1e9e61' : ($ac === 'DELETE' ? '#d64545' : '#1b6ca8');
                $act = $ac === 'ADD' ? 'Added' : ($ac === 'DELETE' ? 'Deleted' : 'Edited');
                $who = $r['changed_by_name'] !== '' ? $r['changed_by_name'] : 'user_id ' . $r['changed_by'];
                $ts = strtotime($r['created_at']);
                $rowcls = $ac === 'DELETE' ? 'af-row-high' : '';
              ?>
                <tr class="af-in <?php echo $rowcls; ?>" style="--i:<?php echo (int) $n; ?>">
                  <td><span class="dl-muted"><?php echo (int) $r['id']; ?></span></td>
                  <td><span class="af-date"><b><?php echo h($ts ? date('d-M-Y', $ts) : $r['created_at']); ?></b><small><?php echo h($ts ? date('h:i:s A', $ts) : ''); ?></small></span></td>
                  <td><span class="af-status" style="--c:<?php echo $acc; ?>"><?php echo $act; ?></span></td>
                  <td><span class="af-chip-j"><?php echo h($r['module']); ?></span></td>
                  <td><span class="af-art"><?php echo h($r['record_id']); ?></span></td>
                  <td><span class="af-person"><span class="af-av"><?php echo h(cl_initials($r['changed_by_name'])); ?></span><?php echo h($who); ?></span><br><small class="dl-muted"><?php echo h($r['ip_address']); ?></small></td>
                  <td><span class="af-num"><?php echo (int) $r['changes_count']; ?></span></td>
                  <td><span class="af-sub"><?php echo $r['mail_sent'] ? 'Sent' : 'Not sent'; ?></span></td>
                  <td>
                    <button type="button" class="af-btn dl-toggle"><i class="fa fa-eye"></i> View</button>
                    <div class="dl-pop" style="display:none">
                      <div class="dl-hero">
                        <div class="dl-hero-k"><?php echo strtoupper(h($r['module'])); ?></div>
                        <div class="dl-hero-t"><?php echo h($r['record_id']); ?></div>
                        <div class="dl-hero-s"><?php echo h($who); ?> &middot; <?php echo h($r['ip_address']); ?> &middot; <?php echo h($ts ? date('d M Y h:i:s A', $ts) : $r['created_at']); ?></div>
                        <div class="dl-pills">
                          <span class="dl-pill <?php echo $ac === 'ADD' ? 'ok' : ($ac === 'DELETE' ? 'bad' : 'info'); ?>"><b><?php echo $act; ?></b></span>
                          <span class="dl-pill"><b><?php echo (int) $r['changes_count']; ?></b> change<?php echo (int) $r['changes_count'] == 1 ? '' : 's'; ?></span>
                          <span class="dl-pill">email: <b><?php echo $r['mail_sent'] ? 'Sent' : 'Not sent'; ?></b></span>
                        </div>
                      </div>
                      <div class="dl-sec"><i class="fa fa-info-circle"></i> LOG</div>
                      <div class="dl-card">
                        <table class="dl-kv">
                          <tr><td>Log #</td><td><?php echo (int) $r['id']; ?></td></tr>
                          <tr><td>Module</td><td><?php echo h($r['module']); ?></td></tr>
                          <tr><td>Record</td><td><?php echo h($r['record_id']); ?></td></tr>
                          <tr><td>Changed by</td><td><?php echo h($who); ?></td></tr>
                          <tr><td>IP</td><td><?php echo h($r['ip_address']); ?></td></tr>
                        </table>
                      </div>
                      <div class="dl-sec"><i class="fa fa-pencil-square-o"></i> CHANGES <span class="dl-count" style="display:inline-block;min-width:20px;text-align:center;background:#dfe8f5;color:#2c5f9e;border-radius:10px;padding:0 7px;font-size:11px;margin-left:4px;"><?php echo (int) $r['changes_count']; ?></span></div>
                      <div class="dl-card"><div class="dl-steps"><?php echo $r['details'] !== '' ? h($r['details']) : '-'; ?></div></div>
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
        ovl.innerHTML = '<div class="dl-box"><div class="dl-hd"><span><i class="fa fa-history"></i> Change details</span><button type="button" aria-label="Close" data-close>&times;</button></div><div class="dl-bd"></div><div class="dl-ft"><button type="button" data-close>Close</button></div></div>';
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
