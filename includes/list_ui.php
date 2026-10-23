<?php
/* ============================================================================
 * List pages (Journals, Customers, ...): the same look as the Add Article / Edit Article
 * pages - header box, white card, tidy DataTables controls, round icon buttons.
 * Design layer only: the table, its ajax source and its scripts are untouched.
 *
 *   <head>:                lu_css();
 *   above the table:       lu_open('Journals', 'Journal &rsaquo; All', $set_url.'/addjournal', '+ Add Journal');
 *   below the table:       lu_close();
 *   in the DataTable init: table.on('xhr.dt', function (e, s, json) { if (json) { $('#lu-count').text(json.iTotalDisplayRecords); } });
 * ========================================================================== */

function lu_css()
{
?>
<?php include_once __DIR__.'/ui_filters.php'; ui_filters_css(); ?>
<style id="lu-theme">
  body{background:#f3f6fa !important;}
  .container-fluid:has(> .row > [class*="col-"] > .articleheading){display:none;}    /* the old title bar is replaced by the header box */
  /* filter forms that sit above a table: a card of their own */
  /* modern row action icons (edit / delete / view) */
  table td .btn.btn-sm.btn-primary,table td .btn.btn-sm.btn-danger,table td .btn.btn-warning,table td a.btn-warning,table td .btn.btn-danger.delete-article,table td button[data-delete],table td .btn.delete-article,table td .btn.btn-info,table td a.btn-info{
    display:inline-flex !important;align-items:center;justify-content:center;width:34px !important;height:34px !important;padding:0 !important;margin:0 3px;border:1px solid transparent !important;border-radius:10px !important;font-size:15px;line-height:1;box-shadow:none !important;cursor:pointer;transition:all .15s ease;text-decoration:none !important;}
  table td .btn.btn-warning,table td a.btn-warning,table td .btn.btn-sm.btn-primary{background:#fff4e8 !important;color:#d2631d !important;border-color:#f6d3bb !important;}
  table td .btn.btn-warning:hover,table td a.btn-warning:hover,table td .btn.btn-sm.btn-primary:hover{background:#e8742a !important;color:#fff !important;transform:translateY(-1px);box-shadow:0 6px 14px rgba(232,116,42,.35) !important;}
  table td .btn.btn-sm.btn-danger,table td .btn.btn-danger.delete-article,table td .btn.delete-article,table td button[data-delete]{background:#fdeeee !important;color:#d64545 !important;border-color:#f7d4d4 !important;}
  table td .btn.btn-sm.btn-danger:hover,table td .btn.btn-danger.delete-article:hover,table td button[data-delete]:hover{background:#d64545 !important;color:#fff !important;transform:translateY(-1px);box-shadow:0 6px 14px rgba(214,69,69,.3) !important;}
  table td .btn.btn-info,table td a.btn-info{background:#eaf7f1 !important;color:#1f9d6a !important;border-color:#cdeedd !important;}
  table td .btn.btn-info:hover,table td a.btn-info:hover{background:#1f9d6a !important;color:#fff !important;}
  /* invoice / document action icons + status badges */
  table td a.btn.btn-xs.btn-primary{display:inline-flex !important;align-items:center;justify-content:center;width:34px !important;height:34px !important;padding:0 !important;margin:0 3px;border-radius:10px !important;border:1px solid #d3e4f5 !important;background:#e8f1fb !important;color:#1b6ca8 !important;font-size:15px;box-shadow:none !important;text-decoration:none !important;transition:all .15s;}
  table td a.btn.btn-xs.btn-primary:hover{background:#1b6ca8 !important;color:#fff !important;transform:translateY(-1px);}
  table td a.btn.btn-xs.btn-primary:has(.fa-file-pdf-o){background:#fdeeee !important;color:#d64545 !important;border-color:#f7d4d4 !important;} table td a.btn.btn-xs.btn-primary:has(.fa-file-pdf-o):hover{background:#d64545 !important;color:#fff !important;}
  table td a.btn.btn-xs.btn-primary:has(.fa-file-excel-o){background:#eaf7f1 !important;color:#1f9d6a !important;border-color:#cdeedd !important;} table td a.btn.btn-xs.btn-primary:has(.fa-file-excel-o):hover{background:#1f9d6a !important;color:#fff !important;}
  table td a#blue,table td a.blue[title]{display:inline-flex;align-items:center;height:34px;padding:0 14px;margin-left:6px;border-radius:10px;background:linear-gradient(135deg,#e8742a,#f08a45);color:#fff !important;font-weight:700;font-size:13px;text-decoration:none !important;box-shadow:0 4px 12px rgba(232,116,42,.3);transition:transform .15s;vertical-align:middle;}
  table td a#blue:hover{transform:translateY(-2px);}
  .inv-st{display:inline-block;padding:4px 12px;border-radius:999px;font-size:12px;font-weight:700;white-space:nowrap;} .inv-st.s1{background:#fff4e8;color:#d2631d;} .inv-st.s2{background:#eaf2fa;color:#1b6ca8;} .inv-st.s3{background:#e7f6ee;color:#1f9d6a;}
  .divouter{display:none;} .divouter:has(form){display:block !important;width:min(1280px,calc(100% - 28px));margin:16px auto 0 !important;background:#fff;border:1px solid #e4e9ef;border-radius:16px;box-shadow:0 4px 18px rgba(20,48,74,.06);padding:8px 12px;}
  .divouter:has(form) .resultbtn{height:42px;padding:0 24px;border:0;border-radius:10px;font-weight:700;color:#fff;background:linear-gradient(120deg,#1b6ca8,#14304a);box-shadow:0 6px 16px rgba(27,108,168,.3);}
  .divouter:has(form) label{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#5b6b7b;}
  .divouter:has(form) .form-control,.divouter:has(form) select,.divouter:has(form) input[type=text]{height:42px;border:1px solid #cfd8e3;border-radius:10px;background:#fbfcfe;}

  .lu-hero{width:min(1280px,calc(100% - 28px));margin:14px auto 0;background:#fff;border:1px solid #e0e8f0;border-radius:16px;overflow:hidden;box-shadow:0 6px 22px rgba(20,48,74,.08);animation:luRise .45s ease;}
  .lu-bar{display:flex;align-items:center;gap:14px;padding:12px 20px;flex-wrap:wrap;}
  .lu-title{margin:0;font-size:19px;font-weight:700;color:#14304a;line-height:1.2;} .lu-crumb{font-size:12px;color:#6b7785;}
  .lu-sp{flex:1;}
  .lu-count{display:flex;align-items:center;gap:8px;padding:6px 14px;border-radius:999px;background:#f4f8fc;border:1px solid #d6e4f1;font-size:12.5px;font-weight:700;color:#14304a;white-space:nowrap;}
  .lu-count b{font-size:15px;color:#1b6ca8;font-variant-numeric:tabular-nums;}
  .lu-add{display:inline-flex;align-items:center;gap:8px;height:42px;padding:0 22px;border-radius:10px;border:0;font-weight:700;font-size:14px;color:#fff !important;text-decoration:none !important;
    background:linear-gradient(120deg,#1b6ca8,#14304a);box-shadow:0 6px 16px rgba(27,108,168,.3);transition:transform .15s,box-shadow .2s,filter .2s;}
  .lu-add:hover{transform:translateY(-2px);filter:brightness(1.1);box-shadow:0 12px 24px rgba(27,108,168,.4);}

  .lu-card{width:min(1280px,calc(100% - 28px));margin:16px auto 30px;background:#fff;border:1px solid #e4e9ef;border-radius:16px;padding:18px 22px 20px;box-shadow:0 4px 18px rgba(20,48,74,.06);animation:luRise .5s ease .08s backwards;}
  .lu-card .custom-container,.lu-card .container.table-responsive{box-shadow:none !important;border-radius:0 !important;padding:0 !important;max-width:none !important;width:100% !important;}

  /* DataTables controls */
  .lu-card .dataTables_wrapper{display:flex;flex-wrap:wrap;align-items:center;gap:10px 16px;}
  .lu-card .dataTables_wrapper > .table-wrapper{flex:0 0 100%;order:5;overflow-x:auto;border:1px solid #e4e9ef;border-radius:12px;}
  .lu-card .dt-buttons{order:1;} .lu-card .dataTables_length{order:2;margin:0;} .lu-card .dataTables_filter{order:3;margin-left:auto;}
  .lu-card .dataTables_info{order:6;color:#6b7785;font-size:12.5px;} .lu-card .dataTables_paginate{order:7;margin-left:auto;}
  .lu-card .dataTables_filter label,.lu-card .dataTables_length label{margin:0;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#5b6b7b;display:flex;align-items:center;gap:8px;}
  .lu-card .dataTables_filter input,.lu-card .dataTables_length select{height:40px;border:1px solid #cfd8e3 !important;border-radius:10px !important;background:#fbfcfe !important;padding:0 12px !important;color:#22303f;font-size:14px;margin:0 !important;}
  .lu-card .dataTables_filter input{min-width:240px;}
  .lu-card .dataTables_filter input:focus,.lu-card .dataTables_length select:focus{border-color:#1b6ca8 !important;background:#fff !important;box-shadow:0 0 0 4px rgba(27,108,168,.14);outline:0;}
  .lu-card .dt-buttons button,.lu-card .dt-buttons .dt-button{height:40px;border:1px solid #cfd8e3 !important;border-radius:10px !important;background:#fff !important;color:#33475b !important;font-weight:700;padding:0 18px !important;box-shadow:none !important;transition:background .15s,transform .15s;}
  .lu-card .dt-buttons button:hover{background:#f1f5f9 !important;transform:translateY(-1px);}
  .lu-card .dataTables_paginate .paginate_button{border:1px solid #d9e2ec !important;border-radius:9px !important;background:#fff !important;color:#33475b !important;margin:0 2px;padding:6px 12px !important;font-weight:600;}
  .lu-card .dataTables_paginate .paginate_button:hover{background:#eef5fb !important;color:#14304a !important;}
  .lu-card .dataTables_paginate .paginate_button.current,.lu-card .dataTables_paginate .paginate_button.current:hover{background:linear-gradient(135deg,#e8742a,#f08a45) !important;color:#fff !important;border-color:transparent !important;box-shadow:0 4px 10px rgba(232,116,42,.3);}
  .lu-card .dataTables_paginate,.dataTables_wrapper .dataTables_paginate{border:0 !important;background:transparent !important;box-shadow:none !important;padding:0 !important;}
  .lu-card .dataTables_paginate .paginate_button.disabled{opacity:.45;}
  .lu-card .dataTables_processing{background:rgba(255,255,255,.9) !important;border:1px solid #d6e4f1;border-radius:10px;color:#14304a;font-weight:700;}

  /* table */
  .lu-card table.dataTable,.lu-card table.custtable{width:100% !important;margin:0 !important;border-collapse:collapse !important;border:0 !important;}
  .lu-card table.dataTable thead th{background:#eef3f8 !important;color:#14304a !important;border:0 !important;border-bottom:2px solid #d5e1ed !important;padding:12px 14px !important;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;}
  .lu-card table.dataTable tbody td{padding:11px 14px !important;border:0 !important;border-bottom:1px solid #eef1f5 !important;vertical-align:middle;background:#fff !important;transition:background .15s;color:#22303f;}
  .lu-card table.dataTable tbody tr:nth-child(even) td{background:#f9fbfd !important;}
  .lu-card table.dataTable tbody tr:hover td{background:#eef5fb !important;}


  /* ---- the site's own table classes tinted every row lavender and fixed the height: neutralise ---- */
  .lu-card .dataTables_wrapper > .table-wrapper{height:auto !important;max-height:none !important;overflow:visible !important;}
  .lu-card .table-responsive{overflow-x:auto;}
  .lu-card table.dataTable{--bs-table-bg:transparent;--bs-table-accent-bg:transparent;--bs-table-striped-bg:transparent;--bs-table-hover-bg:transparent;}
  .lu-card .table > :not(caption) > * > *,.lu-card table.dataTable td,.lu-card table.dataTable th{box-shadow:none !important;}
  .lu-card table.dataTable tbody tr,.lu-card table.dataTable tbody td{border-color:#eef1f5 !important;}

  /* ---- sort arrows (the site's DataTables theme has none) ---- */
  .lu-card table.dataTable thead tr:not(.lu-filters) th.sorting,.lu-card table.dataTable thead tr:not(.lu-filters) th.sorting_asc,.lu-card table.dataTable thead tr:not(.lu-filters) th.sorting_desc{cursor:pointer;position:relative;padding-right:30px !important;user-select:none;background-image:none !important;}
  .lu-card table.dataTable thead th.sorting:after,.lu-card table.dataTable thead th.sorting_asc:after,.lu-card table.dataTable thead th.sorting_desc:after{position:absolute;right:11px;top:50%;transform:translateY(-50%);line-height:1;}
  .lu-card table.dataTable thead th.sorting:after{content:"\21C5";color:#9aa8b8;font-size:14px;}
  .lu-card table.dataTable thead th.sorting_asc:after{content:"\25B2";color:#1b6ca8;font-size:11px;}
  .lu-card table.dataTable thead th.sorting_desc:after{content:"\25BC";color:#1b6ca8;font-size:11px;}
  .lu-card table.dataTable thead th.sorting:hover{background:#e3edf7 !important;}
  .lu-card table.dataTable thead th.sorting_disabled:after{content:none;}

  /* ---- column filters (second header row) ---- */
  .lu-card table.dataTable thead tr.lu-filters th{background:#fff !important;padding:8px 10px !important;border-bottom:1px solid #e4e9ef !important;text-transform:none;letter-spacing:0;font-weight:400;}
  .lu-filters input{width:100%;min-width:90px;height:34px;border:1px solid #cfd8e3;border-radius:8px;padding:0 10px;font-size:13px;color:#22303f;background:#fbfcfe;}
  .lu-filters input:focus{outline:0;border-color:#1b6ca8;background:#fff;box-shadow:0 0 0 3px rgba(27,108,168,.14);}
  .lu-filters input.on{border-color:#1b6ca8;background:#eef6fc;}
  .lu-clear{height:34px;padding:0 12px;border-radius:8px;border:1px solid #cfd8e3;background:#fff;color:#33475b;font-weight:700;font-size:12px;cursor:pointer;white-space:nowrap;transition:background .15s;}
  .lu-clear:hover{background:#fdeaea;border-color:#f3c4c4;color:#c0392b;}


  /* result banner after a delete / save */
  .lu-msg{width:min(1280px,calc(100% - 28px));margin:14px auto 0;padding:12px 18px;border-radius:12px;font-weight:600;font-size:13.5px;display:flex;align-items:center;gap:10px;animation:luRise .35s ease;}
  .lu-msg.ok{background:#e8f7ef;border:1px solid #bfe6cf;color:#177a49;} .lu-msg.err{background:#fdeaea;border:1px solid #f3c4c4;color:#b83232;}

  /* row actions: round icon buttons */
  .lu-card .action-buttons{display:flex !important;align-items:center;gap:8px;}
  .lu-card .action-buttons .btn{width:34px;height:34px;padding:0 !important;border-radius:50% !important;display:inline-flex;align-items:center;justify-content:center;border:1px solid transparent !important;transition:transform .15s,box-shadow .2s;}
  .lu-card .action-buttons .btn-warning{background:#fff4e8 !important;color:#d2631d !important;border-color:#f6d3bb !important;}
  .lu-card .action-buttons .btn-warning:hover{background:#e8742a !important;color:#fff !important;box-shadow:0 4px 10px rgba(232,116,42,.35);transform:translateY(-2px);}
  .lu-card .action-buttons .btn-danger{background:#fdeaea !important;color:#c0392b !important;border-color:#f3c4c4 !important;}
  .lu-card .action-buttons .btn-danger:hover{background:#d64545 !important;color:#fff !important;box-shadow:0 4px 10px rgba(214,69,69,.35);transform:translateY(-2px);}
  .lu-card .action-buttons .btn-info,.lu-card .action-buttons .btn-success{background:#e8f1fa !important;color:#1b6ca8 !important;border-color:#cfe3f3 !important;}
  .lu-card .action-buttons .btn-info:hover,.lu-card .action-buttons .btn-success:hover{background:#1b6ca8 !important;color:#fff !important;transform:translateY(-2px);}

  /* the old SweetAlert pop-ups get the same card look */
  .sweet-overlay{background:rgba(15,32,52,.5) !important;backdrop-filter:blur(3px);}
  .sweet-alert{font-family:'Open Sans','Segoe UI',Arial,sans-serif !important;border-radius:18px !important;padding:26px 30px 22px !important;box-shadow:0 28px 70px rgba(15,35,60,.38) !important;}
  .sweet-alert h2{font-size:21px !important;font-weight:700 !important;color:#1c3550 !important;}
  .sweet-alert button{border-radius:10px !important;padding:10px 30px !important;font-weight:700 !important;}
  .sweet-alert button.confirm{background:linear-gradient(120deg,#1b6ca8,#14304a) !important;color:#fff !important;}

  @keyframes luRise{from{opacity:0;transform:translateY(12px);}to{opacity:1;transform:none;}}
  @media(max-width:640px){.lu-card{padding:14px 12px;} .lu-card .dataTables_filter{margin-left:0;width:100%;} .lu-card .dataTables_filter input{min-width:0;width:100%;}}
  @media (prefers-reduced-motion:reduce){.lu-hero,.lu-card{animation:none !important;}}
</style>
<?php
}

/* opens the header box + the white card (the table goes between lu_open and lu_close) */
/* header box only (pages with a filter form above the table put this at the very top and open the card later with lu_card_open()) */
function lu_card_open() { echo '<div class="lu-card">'; }
function lu_open($title, $crumb, $add_url, $add_label)
{
	lu_hero($title, $crumb, $add_url, $add_label);
	lu_card_open();
}
function lu_hero($title, $crumb, $add_url, $add_label)
{
	$e = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES); };
?>
<?php lu_banner(); ?>
      <div class="lu-hero">
        <div class="lu-bar">
          <div><h1 class="lu-title"><?php echo $e($title); ?></h1><div class="lu-crumb"><?php echo $crumb; ?></div></div>
          <span class="lu-sp"></span>
          <span class="lu-count">Total <b id="lu-count">&hellip;</b></span>
<?php if ($add_label !== '') { ?>          <a class="lu-add" href="<?php echo $e($add_url); ?>"><?php echo $e($add_label); ?></a>
<?php } ?>
        </div>
      </div>
<?php
}

/* delete confirmation for static (non-ajax) tables: any [data-delete] button inside the list card -> ?del=ID on $route */
function lu_delete_js($route, $what = 'record')
{
?>
<script>
window.addEventListener('load', function () {
  var $ = window.jQuery; if (!$ || !window.swal) { return; }
  $(document).on('click', '.lu-card [data-delete]', function (e) {
    e.preventDefault();
    var id = $(this).attr('data-delete');
    swal({title: "Are you sure?", text: "You will not be able to recover this <?php echo addslashes($what); ?>!", type: "warning", showCancelButton: true,
          confirmButtonColor: '#DD6B55', confirmButtonText: 'Proceed!', cancelButtonText: "No, cancel please!", closeOnConfirm: false, closeOnCancel: true},
      function (ok) {
        if (!ok) { return; }
        swal({title: "Deleting...", text: "Please wait", type: "info", showConfirmButton: false});
        window.top.location = "<?php echo addslashes($route); ?>?del=" + encodeURIComponent(id);
      });
  });
});
</script>
<?php
}

function lu_close()
{
?>
      </div>
<?php include_once __DIR__.'/ui_filterbar.php'; ui_filterbar(); ?>
<script>
/* header total: any server-side table fills it; otherwise count the rows that are on the page */
window.addEventListener('load', function () {
  var el = document.getElementById('lu-count'); if (!el || !window.jQuery) { return; }
  jQuery(document).on('xhr.dt', function (e, s, json) { if (json) { var n = json.iTotalDisplayRecords != null ? json.iTotalDisplayRecords : json.recordsFiltered; if (n != null) { el.textContent = n; } } });
  setTimeout(function () {
    if (el.textContent.replace(/\W/g, '') !== '') { return; }
    var rows = jQuery('.lu-card table tbody tr').filter(function () { return !jQuery(this).find('td.dataTables_empty').length; }).length;
    el.textContent = rows;
  }, 1500);
});
</script>
<?php
}

/* second header row with one filter box per column: $cols = array of placeholders, null = no filter (the Action column gets "Clear") */
function lu_filter_row($cols)
{
	echo '<tr class="lu-filters">';
	foreach ($cols as $i => $ph) {
		if ($ph === null) { echo '<th><button type="button" class="lu-clear" title="Clear all filters">Clear</button></th>'; }
		else { echo '<th><input type="text" data-col="' . (int)$i . '" placeholder="' . htmlspecialchars($ph, ENT_QUOTES) . '" autocomplete="off"></th>'; }
	}
	echo '</tr>';
}

/* script: luFilterParams(tableSel, aoData) adds cf_<n> to every ajax call; luBindFilters(table, tableSel) wires the boxes (call once, after the DataTable is created).
   Filters are applied by the server, so they work across all pages of results. */
function lu_filter_js()
{
?>
<script>
window.luFilterParams = function (sel, aoData) {
  $(sel + ' thead tr.lu-filters input[data-col]').each(function () { aoData.push({ name: 'cf_' + $(this).data('col'), value: $.trim($(this).val()) }); });
};
window.luBindFilters = function (table, sel) {
  /* sorting / filtering / paging: hold the table's height while the rows load - no collapse, no flash */
  var $w = $(sel).closest('.dataTables_wrapper'), $tw = $w.children('.table-wrapper');
  $(sel).on('preXhr.dt', function () { $tw.css('min-height', $tw.outerHeight() + 'px'); $w.addClass('lu-busy'); });
  $(sel).on('draw.dt', function () { $w.removeClass('lu-busy'); setTimeout(function () { $tw.css('min-height', ''); }, 60); });
  var timer = null, $in = $(sel + ' thead tr.lu-filters input[data-col]');
  function mark() { $in.each(function () { $(this).toggleClass('on', $.trim($(this).val()) !== ''); }); }
  $in.on('keyup input', function () { mark(); clearTimeout(timer); timer = setTimeout(function () { table.draw(); }, 350); });
  $in.on('keydown', function (e) { e.stopPropagation(); });                           /* typing must not trigger sorting */
  $(sel + ' thead tr.lu-filters .lu-clear').on('click', function () { $in.val(''); mark(); table.draw(); });
};
</script>
<?php
}

/* green / red banner from ?msg= (done) or ?err= (refused) - text is escaped */
function lu_banner()
{
	$ok = isset($_GET['msg']) ? trim((string)$_GET['msg']) : '';
	$er = isset($_GET['err']) ? trim((string)$_GET['err']) : '';
	if ($er !== '') { echo '<div class="lu-msg err"><i class="fa fa-exclamation-circle"></i><span>' . htmlspecialchars($er, ENT_QUOTES) . '</span></div>'; }
	elseif ($ok !== '') { echo '<div class="lu-msg ok"><i class="fa fa-check-circle"></i><span>' . htmlspecialchars($ok, ENT_QUOTES) . '</span></div>'; }
}

/* Other tables that point at a record: any table with a column named like $cols holding $id (small or indexed tables only, so it stays fast).
   Skips the record's own table(s), backups / logs / archives. Returns "table (n)" style names. */
function lu_auto_deps($db, $self_tables, $cols, $id)
{
	$used = array();
	$in = "'" . implode("','", array_map(function ($c) use ($db) { return $db->real_escape_string($c); }, $cols)) . "'";
	try {
		$r = $db->query("SELECT c.table_name AS t, c.column_name AS col, t.table_rows AS n
		                 FROM information_schema.columns c
		                 JOIN information_schema.tables t ON t.table_schema = c.table_schema AND t.table_name = c.table_name AND t.table_type = 'BASE TABLE'
		                 WHERE c.table_schema = DATABASE() AND c.column_name IN ($in)");
		while ($r && ($x = $r->fetch_assoc())) {
			$tn = $x['t'];
			if (in_array($tn, $self_tables, true) || preg_match('/(bkp|backup|_old|_log$|^change_log|audit|archive)/i', $tn)) { continue; }
			if ((int)$x['n'] > 300000) {                                  // big table: only when the column is indexed
				$ix = $db->query("SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = '" . $db->real_escape_string($tn) . "' AND column_name = '" . $db->real_escape_string($x['col']) . "' AND seq_in_index = 1 LIMIT 1");
				if (!$ix || !$ix->num_rows) { continue; }
			}
			$q = $db->query("SELECT 1 FROM `" . $tn . "` WHERE `" . $x['col'] . "` = " . (int)$id . " LIMIT 1");
			if ($q && $q->num_rows) { $used[] = $tn; }
		}
	} catch (Throwable $e) { }
	return $used;
}

/* Delete one record from a list page (?del=<id>).
 *   $deps     = array(array('SELECT COUNT(*) ...', 'articles'), ..., 'auto:dept_code,dept_id')   - 'auto:' scans the other tables for those column names.
 *               If anything is still using the record nothing is deleted and the page says why.
 *   $children = array(array('child_table', 'fk_column'), ...)  - rows that belong to the record and go with it.
 *   Every delete is written to the change log (old values) like the other delete screens. Returns only when there is no ?del=. */
function lu_delete($db, $table, $pk, $label, $deps, $back_path, $children = array())
{
	if (!isset($_GET['del']) && !isset($_POST['del'])) { return; }
	$id = (int)(isset($_REQUEST['del']) ? $_REQUEST['del'] : 0);
	$back = $_COOKIE['set_url'] . '/' . $back_path;
	$go = function ($k, $m) use ($back) { header('Location: ' . $back . '?' . $k . '=' . rawurlencode($m)); exit; };
	if ($id <= 0) { $go('err', 'Nothing was deleted - no record was chosen.'); }
	$r = $db->query("SELECT * FROM `$table` WHERE `$pk` = $id");
	$row = $r ? $r->fetch_assoc() : null;
	if (!$row) { $go('err', $label . ' was not found - it may already be deleted.'); }
	$used = array();
	foreach ($deps as $d) {
		if (is_string($d) && strpos($d, 'auto:') === 0) {
			$own = array($table); foreach ($children as $c) { $own[] = $c[0]; }
			foreach (lu_auto_deps($db, $own, explode(',', substr($d, 5)), $id) as $tn) { $used[] = $tn; }
			continue;
		}
		try { $q = $db->query($d[0]); $n = $q ? (int)$q->fetch_row()[0] : 0; } catch (Throwable $e) { $n = 0; }
		if ($n > 0) { $used[] = $n . ' ' . $d[1]; }
	}
	if ($used) { $go('err', $label . ' was NOT deleted - it is still used by ' . implode(', ', $used) . '. Remove or move those first.'); }
	include_once __DIR__ . '/change_log_mail.php';
	try { $old = change_log_snapshot($table, $pk, $id); } catch (Throwable $e) { $old = $row; }
	foreach ($children as $c) { $db->query("DELETE FROM `" . $c[0] . "` WHERE `" . $c[1] . "` = $id"); }
	if (!$db->query("DELETE FROM `$table` WHERE `$pk` = $id")) { $go('err', $label . ' could not be deleted (' . $db->error . ').'); }
	try { log_row_deleted($label, $id, $old); } catch (Throwable $e) { error_log('delete log failed: ' . $e->getMessage()); }
	$go('msg', $label . ' deleted.');
}
