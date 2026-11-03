<?php
include 'includes/includes.php';
require_once __DIR__ . '/includes/compare_pdf_lookup.php';

/* Filing log of the PDF compare tool: did the report reach the article's PRQC (work) folder, and where. */
cmp_ensure_log_table($db);

$rows = array();
$res = $db->query("SELECT l.*, u.full_name FROM compare_pdf_filing_log l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.id DESC LIMIT 500");
while ($res && ($r = $res->fetch_assoc())) {
    $rows[] = $r;
}

function cl_e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function cl_size($b)
{
    $b = (float)$b;
    if ($b >= 1073741824) { return round($b / 1073741824, 2) . ' GB'; }
    if ($b >= 1048576) { return round($b / 1048576, 1) . ' MB'; }
    if ($b > 0) { return round($b / 1024) . ' KB'; }
    return '';
}

$saved = 0;
foreach ($rows as $r) { if ($r['saved']) { $saved++; } }
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>PDF Compare Filing Log</title>

    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>

    <style>
        .cl-page { --ink:#1b2a41; --muted:#64748b; --line:#e6eaf0; --soft:#f6f8fb; --ok:#1e8449; --bad:#c0392b; --accent:#f47c20;
                   max-width: 1320px; margin: 0 auto; padding: 8px 20px 60px; color: var(--ink); }
        .cl-hero { display:flex; justify-content:space-between; align-items:center; gap:20px; flex-wrap:wrap; padding:24px 30px; border-radius:20px; color:#fff;
                   background: linear-gradient(120deg,#1b2a41 0%,#2c4a7a 60%,#f47c20 140%); box-shadow:0 14px 34px rgba(27,42,65,.22); }
        .cl-hero h1 { margin:0 0 4px; font-size:24px; font-weight:700; }
        .cl-hero p { margin:0; font-size:14px; color:rgba(255,255,255,.82); }
        .cl-hero a.cl-back { padding:10px 20px; border-radius:12px; background:#fff; color:#1b2a41; font-weight:700; text-decoration:none; }
        .cl-stats { display:flex; gap:12px; margin-top:20px; flex-wrap:wrap; }
        .cl-stat { padding:14px 20px; border:1px solid var(--line); border-radius:14px; background:#fff; min-width:150px; box-shadow:0 4px 14px rgba(27,42,65,.05); }
        .cl-stat b { display:block; font-size:24px; }
        .cl-stat span { font-size:12.5px; color:var(--muted); }
        .cl-card { margin-top:20px; padding:20px 22px; background:#fff; border:1px solid var(--line); border-radius:20px; box-shadow:0 8px 28px rgba(27,42,65,.07); }
        .cl-tools { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:14px; }
        .cl-tools input, .cl-tools select { padding:10px 14px; border:1px solid #cdd6e2; border-radius:10px; font-size:14px; background:#fff; }
        .cl-tools input { flex:1; min-width:240px; }
        .cl-wrap { overflow-x:auto; border:1px solid var(--line); border-radius:12px; }
        table.cl-table { width:100%; border-collapse:separate; border-spacing:0; font-size:13px; }
        .cl-table th { position:sticky; top:0; padding:11px 12px; background:var(--soft); color:var(--muted); font-size:11.5px; font-weight:700; text-transform:uppercase;
                       letter-spacing:.4px; text-align:left; border-bottom:1px solid var(--line); white-space:nowrap; }
        .cl-table td { padding:11px 12px; border-bottom:1px solid var(--line); vertical-align:top; }
        .cl-table tr:hover td { background:#fffaf5; }
        .cl-badge { display:inline-block; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:700; white-space:nowrap; }
        .cl-badge.ok { background:#e6f5ec; color:var(--ok); }
        .cl-badge.bad { background:#fdecea; color:var(--bad); }
        .cl-muted { color:var(--muted); font-size:12px; }
        .cl-path { word-break:break-all; font-size:12px; color:#334155; }
        .cl-links a { display:inline-block; margin:2px 6px 2px 0; padding:4px 10px; border-radius:8px; border:1px solid #f2cfae; background:#fff8f1; color:#9a4a08;
                      font-size:12px; font-weight:600; text-decoration:none; white-space:nowrap; }
        .cl-links a:hover { background:#ffeedd; }
        .cl-empty { padding:40px; text-align:center; color:var(--muted); }
    </style>

</head>

<body>

<div class="wholeouter">

    <?php include("includes/top-header.php"); ?>

    <div class="cl-page">

        <div class="cl-hero">
            <div>
                <h1>PDF compare - report filing log</h1>
                <p>Every time a comparison report is downloaded, this shows whether it reached the article's PRQC (work) folder, and where.</p>
            </div>
            <a class="cl-back" href="<?php echo cl_e($_COOKIE['set_url']); ?>/comparepdf">&larr; Back to PDF Compare</a>
        </div>

        <div class="cl-stats">
            <div class="cl-stat"><b><?php echo count($rows); ?></b><span>Latest entries</span></div>
            <div class="cl-stat"><b style="color:var(--ok)"><?php echo $saved; ?></b><span>Saved to the work folder</span></div>
            <div class="cl-stat"><b style="color:var(--bad)"><?php echo count($rows) - $saved; ?></b><span>Not saved</span></div>
        </div>

        <div class="cl-card">

            <div class="cl-tools">
                <input type="search" id="cl-q" placeholder="Search Pub ID, file name, user, message...">
                <select id="cl-f">
                    <option value="">All results</option>
                    <option value="1">Saved</option>
                    <option value="0">Not saved</option>
                </select>
            </div>

            <?php if (!$rows) { ?>
                <div class="cl-empty">Nothing logged yet. A line is added each time a comparison report is downloaded.</div>
            <?php } else { ?>
            <div class="cl-wrap">
                <table class="cl-table" id="cl-table">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>User</th>
                            <th>Files compared</th>
                            <th>Article</th>
                            <th>Result</th>
                            <th>Where it went / why not</th>
                            <th>Open in Explorer</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $r) { ?>
                        <tr data-saved="<?php echo (int)$r['saved']; ?>">
                            <td style="white-space:nowrap"><?php echo cl_e(date('d-m-Y H:i', strtotime($r['logged_at']))); ?></td>
                            <td><?php echo cl_e($r['full_name'] !== null && $r['full_name'] !== '' ? $r['full_name'] : $r['user_id']); ?></td>
                            <td style="word-break:break-all">
                                <?php echo cl_e($r['file1_name']); ?> <span class="cl-muted"><?php echo cl_e(cl_size($r['file1_size'])); ?></span><br>
                                <?php echo cl_e($r['file2_name']); ?> <span class="cl-muted"><?php echo cl_e(cl_size($r['file2_size'])); ?></span>
                            </td>
                            <td>
                                <?php if ($r['pub_id'] !== '') { ?>
                                    <b><?php echo cl_e($r['pub_id']); ?></b><br>
                                    <span class="cl-muted"><?php echo cl_e($r['article_state']); ?> &middot; <?php echo cl_e($r['found_in']); ?> &middot; <?php echo cl_e($r['stage']); ?> / <?php echo cl_e($r['article_status']); ?></span>
                                <?php } else { ?>
                                    <span class="cl-muted">Not found</span>
                                <?php } ?>
                            </td>
                            <td><span class="cl-badge <?php echo $r['saved'] ? 'ok' : 'bad'; ?>"><?php echo $r['saved'] ? 'Saved' : 'Not saved'; ?></span></td>
                            <td>
                                <?php if ($r['saved']) { ?>
                                    <div class="cl-path"><?php echo cl_e($r['saved_file']); ?></div>
                                <?php } else { ?>
                                    <div style="font-size:12.5px"><?php echo cl_e($r['message']); ?></div>
                                <?php } ?>
                            </td>
                            <td class="cl-links">
                                <?php if ($r['work_link'] !== '') { ?>
                                    <a href="<?php echo cl_e($r['work_link']); ?>" target="_blank" title="<?php echo cl_e($r['prqc_path']); ?>">Work (PRQC)</a>
                                <?php } ?>
                                <?php if ($r['source_link'] !== '') { ?>
                                    <a href="<?php echo cl_e($r['source_link']); ?>" target="_blank">Source</a>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
            <?php } ?>

        </div>

    </div>

    <?php echo scripts(); ?>
    <?php echo footer(); ?>

</div>

<script>
(function () {
    var q = document.getElementById('cl-q'), f = document.getElementById('cl-f'), t = document.getElementById('cl-table');
    if (!t) { return; }
    function apply() {
        var s = q.value.toLowerCase(), v = f.value;
        [].forEach.call(t.tBodies[0].rows, function (r) {
            var ok = (!s || r.textContent.toLowerCase().indexOf(s) > -1) && (v === '' || r.getAttribute('data-saved') === v);
            r.style.display = ok ? '' : 'none';
        });
    }
    q.addEventListener('input', apply);
    f.addEventListener('change', apply);
})();
</script>

</body>
</html>
