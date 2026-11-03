<?php
/* ============================================================================
 * Shared helpers + modern UI for the assign / re-assign pages
 * (assign_articles_vendor, reassign_articles_vendor, assign_articles_users,
 *  rassign_articles_users).
 *
 *   am_request_filters()  - filter values from the query string (no session)
 *   am_status_sql()       - status exclusion for the "Query / Hold" checkboxes
 *   am_where()            - customer / stage / journal / article / date conditions
 *   am_head()             - styles, echo in <head> after af_head()
 *   am_foot()             - script, echo before af_foot()
 * ========================================================================== */

function am_request_filters()
{
	$flt = array();
	foreach (array('cust_id', 'stage_id', 'j_id', 'pub_id', 'from_dt', 'to_dt', 'user_id', 'dept_id', 'dep_id', 'r_user_id') as $k) {
		$flt[$k] = isset($_GET[$k]) && !is_array($_GET[$k]) ? trim((string)$_GET[$k]) : '';
	}
	$flt['inpt_status'] = isset($_GET['inpt_status']) ? (array)$_GET['inpt_status'] : array();
	return $flt;
}

function am_status_sql($status)
{
	$q = in_array('Query', (array)$status);
	$h = in_array('Hold', (array)$status);
	$not = array('Delivery', 'Client Review', 'Completed');
	if (!$q) {
		$not[] = 'Query';
	}
	if (!$h) {
		$not[] = 'Hold';
	}
	return " AND wd.status NOT IN ('" . implode("','", $not) . "') ";
}

function am_where($db, $flt, $stage_col = 'stage')
{
	$e = function ($v) use ($db) {
		return mysqli_real_escape_string($db, $v);
	};
	$w = '';
	if ($flt['cust_id'] !== '' && $flt['cust_id'] !== 'all') {
		$w .= " and c.id = '" . $e($flt['cust_id']) . "' ";
	}
	if ($flt['stage_id'] !== '' && $flt['stage_id'] !== 'all') {
		$w .= " and $stage_col LIKE '" . $e($flt['stage_id']) . "%' ";
	}
	if ($flt['j_id'] !== '') {
		$w .= " and j.j_id = '" . $e($flt['j_id']) . "' ";
	}
	if ($flt['pub_id'] !== '') {
		$w .= " and pub_id LIKE '%" . $e($flt['pub_id']) . "%' ";
	}
	if ($flt['from_dt'] !== '' && $flt['to_dt'] !== '') {
		$w .= " and recv_dt BETWEEN '" . date('Y-m-d', strtotime($flt['from_dt'])) . "' and '" . date('Y-m-d', strtotime($flt['to_dt'])) . "' ";
	}
	return $w;
}

function am_head()
{
?>
<style>
/* ---------- motion ---------- */
@keyframes am-up{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}
@keyframes am-in{from{opacity:0;transform:scale(.94) translateY(8px)}to{opacity:1;transform:none}}
@keyframes am-flow{0%{background-position:0% 50%}100%{background-position:100% 50%}}
@keyframes am-pop{0%{transform:scale(1)}40%{transform:scale(1.25)}100%{transform:scale(1)}}
@keyframes am-spin{to{transform:rotate(360deg)}}
@keyframes am-toast{from{opacity:0;transform:translateX(30px)}to{opacity:1;transform:none}}
@keyframes am-row{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
@media (prefers-reduced-motion:reduce){*{animation:none !important;transition:none !important}}

/* ---------- page shell ---------- */
.af-hero{background:linear-gradient(120deg,#14304a,#1b5a8a,#f08a24 140%) !important;background-size:220% 220% !important;animation:am-flow 10s ease infinite alternate,am-up .5s ease both !important;border-radius:18px !important;box-shadow:0 10px 30px rgba(20,48,74,.25) !important}
.af-hero h1{letter-spacing:.2px}
.af-wrap{animation:am-up .55s .08s ease both}
.af-card{animation:am-up .55s .16s ease both !important;border-radius:18px !important;box-shadow:0 8px 30px rgba(20,48,74,.08) !important}

/* ---------- filters (auto-apply) ---------- */
.af-bar{gap:14px 16px !important}
.af-bar .af-field select{height:42px !important;border-radius:12px !important;border:1.5px solid #dbe3ec !important;background:#fff !important;transition:border-color .2s,box-shadow .2s,transform .2s}
.af-bar .af-field select:hover{border-color:#b9c7d6 !important}
.af-bar .af-field select:focus{border-color:#f08a24 !important;box-shadow:0 0 0 4px rgba(240,138,36,.16) !important;outline:0}
.af-bar .af-field > label.control-label{font-size:10.5px !important;font-weight:700 !important;text-transform:uppercase;letter-spacing:.5px;color:#6b7785 !important;margin:0 0 4px !important}
.af-bar .af-field .d-flex{margin:0 !important;gap:8px !important;align-items:center}
.af-bar .af-field .inline{position:relative;display:inline-block}
.af-bar .af-field .inline input.ace[type=checkbox]{position:absolute;inset:0;width:100%;height:100%;opacity:0;margin:0 !important;cursor:pointer;z-index:2}
.af-bar .af-field .inline .lbl{display:inline-flex;align-items:center;gap:8px;height:42px;padding:0 16px 0 12px;border:1.5px solid #dbe3ec;border-radius:21px;background:#fff;font-size:13px;font-weight:600;color:#475569;user-select:none;transition:background .2s,border-color .2s,color .2s,box-shadow .2s,transform .15s}
.af-bar .af-field .inline .lbl:before{content:"";width:16px;height:16px;border-radius:50%;border:2px solid #cbd5e1;box-sizing:border-box;transition:background .2s,border-color .2s,box-shadow .2s}
.af-bar .af-field .inline:hover .lbl{border-color:#b9c7d6;transform:translateY(-1px)}
.af-bar .af-field .inline input.ace:checked + .lbl{background:#fff4e5;border-color:#f08a24;color:#b45309}
.af-bar .af-field .inline input.ace:checked + .lbl:before{background:#f08a24;border-color:#f08a24;box-shadow:inset 0 0 0 3px #fff4e5}
.af-bar .af-field .inline input.ace:focus-visible + .lbl{box-shadow:0 0 0 4px rgba(240,138,36,.2)}
.af-bar .af-actions .af-btn{display:none !important}
.af-bar .af-actions{margin:0 !important;align-self:flex-end}
.am-reset{display:none;align-items:center;gap:6px;height:42px;padding:0 16px;border-radius:12px;background:#f1f5f9;color:#475569 !important;font-weight:600;font-size:13px;text-decoration:none !important;transition:background .2s,transform .2s}
.am-reset.show{display:inline-flex;animation:am-in .3s ease both}
.am-reset:hover{background:#e2e8f0;transform:translateY(-1px)}
.af-bar .af-field .af-err{color:#d9534f;font-size:12px;display:none}
.af-bar .af-field.has-err .af-err{display:block}
.af-bar .af-field.has-err select{border-color:#d9534f !important}
.am-busy{position:relative;pointer-events:none}
.am-busy:after{content:"";position:absolute;inset:0;background:rgba(255,255,255,.65);border-radius:18px;z-index:5}
.am-busy:before{content:"";position:absolute;top:50%;left:50%;width:34px;height:34px;margin:-17px;border:3px solid #f08a24;border-top-color:transparent;border-radius:50%;animation:am-spin .7s linear infinite;z-index:6}

/* ---------- selected-count pill ---------- */
.af-card .pull-right.mb-2{float:none !important;display:flex;justify-content:flex-end;align-items:center;margin:0 0 14px !important}
.af-card .pull-right.mb-2 label.control-label{display:inline-flex;align-items:center;gap:10px;margin:0 !important;padding:5px 6px 5px 16px;border:1.5px solid #e3e8ef;border-radius:30px;background:#fff;font-size:0 !important;color:#64748b;box-shadow:0 2px 8px rgba(20,48,74,.05)}
.af-card .pull-right.mb-2 label.control-label b{font-size:12px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#64748b}
#a_count.btn{pointer-events:none;display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 10px;margin:0 !important;border:0 !important;border-radius:20px;background:#e5e9f0 !important;color:#64748b !important;font-size:15px;font-weight:800;box-shadow:none;transition:background .3s,color .3s,box-shadow .3s}
#a_count.btn.on{background:linear-gradient(135deg,#f6a13a,#e8742a) !important;color:#fff !important;box-shadow:0 4px 12px rgba(232,116,42,.4)}
#a_count.btn.pop{animation:am-pop .35s ease}
/* ---------- table ---------- */
.af-card .table-responsive{overflow:visible !important}
.af-card .dataTables_wrapper{overflow-x:auto;overflow-y:hidden;padding-bottom:4px}
#dynamic-table tbody tr{cursor:pointer;transition:background .2s,box-shadow .2s,transform .2s;animation:am-row .45s ease both}
#dynamic-table tbody tr:nth-child(1){animation-delay:.02s}#dynamic-table tbody tr:nth-child(2){animation-delay:.06s}#dynamic-table tbody tr:nth-child(3){animation-delay:.1s}#dynamic-table tbody tr:nth-child(4){animation-delay:.14s}#dynamic-table tbody tr:nth-child(5){animation-delay:.18s}#dynamic-table tbody tr:nth-child(n+6){animation-delay:.22s}
#dynamic-table tbody tr:hover{background:#f7fafd !important;box-shadow:inset 3px 0 0 #cbd5e1}
#dynamic-table tbody tr.row-selected{background:#fff6ea !important;box-shadow:inset 4px 0 0 #f08a24,0 4px 14px rgba(240,138,36,.14)}
#dynamic-table tbody tr.alert-danger{box-shadow:inset 4px 0 0 #d9534f}
#dynamic-table .label-success{background:#e3f6ec !important;color:#15803d !important;border-radius:20px;padding:4px 11px;font-weight:700}
#dynamic-table .label-info{background:#e6f0ff !important;color:#1d4ed8 !important}
#dynamic-table td.dataTables_empty{padding:46px 10px !important;color:#94a3b8;font-size:15px}
#dynamic-table td.dataTables_empty:before{content:"\1F4ED";display:block;font-size:34px;margin-bottom:8px}
.ace-check{accent-color:#f08a24}
input.ace[type=checkbox]{accent-color:#f08a24;width:16px;height:16px;cursor:pointer}

/* ---------- action dock ---------- */
.assign-panel{position:relative;z-index:60;margin-top:22px;border:1px solid #e3e8ef;border-radius:18px;background:rgba(255,255,255,.96);backdrop-filter:blur(8px);box-shadow:0 14px 44px rgba(20,48,74,.2);overflow:hidden;transition:opacity .35s,transform .35s,box-shadow .35s}
.assign-panel.idle{opacity:.6;transform:translateY(6px) scale(.992);box-shadow:0 6px 18px rgba(20,48,74,.08)}
.assign-panel.active{opacity:1;transform:none;position:sticky;bottom:16px}
.assign-panel .ap-head{display:flex;justify-content:space-between;align-items:center;padding:12px 20px;background:linear-gradient(90deg,#f6f9fc,#fff);border-bottom:1px solid #e3e8ef}
.assign-panel .ap-title{font-weight:800;color:#14304a;font-size:15px;display:flex;align-items:center;gap:8px}
.assign-panel .ap-title:before{content:"";width:9px;height:9px;border-radius:50%;background:#cbd5e1;transition:background .3s,box-shadow .3s}
.assign-panel.active .ap-title:before{background:#22c55e;box-shadow:0 0 0 5px rgba(34,197,94,.18)}
.assign-panel .ap-count{background:#e8eefc;color:#1d4ed8;border-radius:20px;padding:4px 14px;font-size:13px;font-weight:700}
.assign-panel .ap-count.zero{background:#f1f3f5;color:#6b7280}
.assign-panel .ap-count.pop{animation:am-pop .35s ease}
.assign-panel .ap-clear{margin-left:10px;border:0;background:none;color:#64748b;font-size:12px;cursor:pointer;text-decoration:underline;display:none}
.assign-panel.active .ap-clear{display:inline}
.assign-panel .ap-body{display:grid;grid-template-columns:1.1fr 1.6fr 1.1fr auto;gap:18px;padding:18px 20px;align-items:start}
.assign-panel.ap-3 .ap-body{grid-template-columns:1.6fr 1fr auto}
.assign-panel label.ap-label{display:block;font-size:11px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:#5b6675;margin:0 0 6px}
.assign-panel .ap-label i{color:#d9534f;font-style:normal}
.assign-panel select,.assign-panel textarea,.assign-panel input.ap-date{width:100%;border:1.5px solid #dbe3ec;border-radius:12px;padding:10px 13px;font-size:14px;background:#fff;box-sizing:border-box;transition:border-color .2s,box-shadow .2s}
.assign-panel textarea{resize:vertical;min-height:42px;height:42px}
.assign-panel select:focus,.assign-panel textarea:focus,.assign-panel input.ap-date:focus{outline:0;border-color:#f08a24;box-shadow:0 0 0 4px rgba(240,138,36,.16)}
.assign-panel .ap-datewrap{position:relative}
.assign-panel .ap-datewrap i{position:absolute;right:13px;top:13px;color:#6b7280;pointer-events:none}
.assign-panel .ap-quick{margin-top:7px;display:flex;gap:6px;flex-wrap:wrap}
.assign-panel .ap-quick button{border:1px solid #dbe3ec;background:#f7f9fc;border-radius:16px;font-size:12px;padding:3px 11px;color:#374151;cursor:pointer;transition:all .2s}
.assign-panel .ap-quick button:hover{background:#fff1e0;border-color:#f08a24;transform:translateY(-1px)}
.assign-panel .ap-go{align-self:end}
.assign-panel .ap-go button{height:44px;padding:0 26px;border:0;border-radius:12px;background:linear-gradient(135deg,#f6a13a,#e8742a);color:#fff;font-weight:800;cursor:pointer;white-space:nowrap;box-shadow:0 6px 16px rgba(232,116,42,.35);transition:transform .2s,box-shadow .2s,filter .2s}
.assign-panel .ap-go button:hover:not(:disabled){transform:translateY(-2px);box-shadow:0 10px 22px rgba(232,116,42,.45)}
.assign-panel .ap-go button:active:not(:disabled){transform:none}
.assign-panel .ap-go button:disabled{background:#d6dae1;box-shadow:none;cursor:not-allowed}
.assign-panel .ap-hint{font-size:12px;color:#6b7280;margin-top:7px;text-align:center}
.assign-panel .ap-err{color:#d9534f;font-size:12px;margin-top:4px;display:none}
.assign-panel .has-err .ap-err{display:block}
.assign-panel .has-err select,.assign-panel .has-err input.ap-date{border-color:#d9534f !important;animation:am-shake .35s}
@keyframes am-shake{20%,60%{transform:translateX(-4px)}40%,80%{transform:translateX(4px)}}
.assign-panel label.help-block{display:none !important}
.assign-panel select.help-block,.assign-panel input.help-block,.assign-panel textarea.help-block{display:block !important}
@media(max-width:992px){.assign-panel .ap-body,.assign-panel.ap-3 .ap-body{grid-template-columns:1fr 1fr}}
@media(max-width:600px){.assign-panel .ap-body,.assign-panel.ap-3 .ap-body{grid-template-columns:1fr}.assign-panel{bottom:8px}}

.assign-panel .ap-warn{display:none;margin-top:7px;padding:7px 11px;border-radius:9px;background:#fff4e5;border:1px solid #f6c48b;color:#9a4b05;font-size:12px;line-height:1.4;animation:am-in .25s ease both}
.assign-panel .ap-warn.show{display:block}
.assign-panel select option:disabled,.assign-panel select option[data-online]:disabled{color:#b6bfcb !important;background:#f4f6f9}
.assign-panel select option[data-online="1"],.af-bar select option[data-online="1"]{color:#15803d}
.assign-panel select option[data-online="0"],.af-bar select option[data-online="0"]{color:#475569}
.assign-panel .ap-legend{margin-top:6px;font-size:11px;color:#64748b}
.assign-panel .ap-legend b{font-weight:700}
.assign-panel .ap-legend .on{color:#15803d}
.assign-panel .ap-note{display:none;margin-top:7px;padding:7px 11px;border-radius:9px;background:#eef4ff;border:1px solid #c7d8fb;color:#1e40af;font-size:12px;line-height:1.4;animation:am-in .25s ease both}
.assign-panel .ap-note.show{display:block}
/* ---------- confirm dialog ---------- */
.cm-back{position:fixed;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(3px);display:none;align-items:center;justify-content:center;z-index:99999;padding:16px}
.cm-back.open{display:flex;animation:am-up .2s ease both}
.cm-box{background:#fff;border-radius:20px;width:580px;max-width:100%;max-height:88vh;display:flex;flex-direction:column;box-shadow:0 30px 70px rgba(0,0,0,.35);overflow:hidden;animation:am-in .28s ease both}
.cm-head{padding:18px 24px;background:linear-gradient(120deg,#14304a,#1b5a8a);color:#fff;font-size:18px;font-weight:800;display:flex;justify-content:space-between;align-items:center}
.cm-head small{font-weight:600;background:rgba(255,255,255,.18);border-radius:14px;padding:3px 12px;font-size:12px}
.cm-body{padding:18px 24px;overflow:auto}
.cm-row{display:flex;gap:12px;padding:9px 0;border-bottom:1px dashed #e5e9f0;font-size:14px}
.cm-row span:first-child{width:120px;flex:none;color:#64748b;font-size:11px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;padding-top:3px}
.cm-row span:last-child{color:#14304a;font-weight:600;word-break:break-word}
.cm-list{margin-top:16px;font-size:11px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#64748b}
.cm-chips{display:flex;flex-wrap:wrap;gap:7px;margin-top:9px;max-height:150px;overflow:auto}
.cm-chips span{background:#eef2ff;color:#1e3a8a;border-radius:16px;padding:4px 12px;font-size:13px;font-weight:700;animation:am-in .3s ease both}
.cm-foot{padding:16px 24px;background:#f8fafc;border-top:1px solid #e5e9f0;display:flex;justify-content:flex-end;gap:10px}
.cm-foot button{border:0;border-radius:12px;padding:11px 24px;font-weight:800;cursor:pointer;font-size:14px;transition:transform .2s,box-shadow .2s}
.cm-foot button:hover{transform:translateY(-1px)}
.cm-cancel{background:#e5e9f0;color:#334155}
.cm-ok{background:linear-gradient(135deg,#f6a13a,#e8742a);color:#fff;box-shadow:0 6px 16px rgba(232,116,42,.35)}

/* ---------- toast (server message) ---------- */
#successMessage.alert{position:fixed !important;top:84px;right:22px;z-index:99998;max-width:360px !important;margin:0 !important;padding:14px 44px 14px 16px !important;border:0 !important;border-left:5px solid #22c55e !important;border-radius:12px !important;background:#fff !important;color:#14304a !important;font-weight:600;box-shadow:0 14px 40px rgba(20,48,74,.25);animation:am-toast .4s ease both}
#successMessage.alert.out{opacity:0;transform:translateX(30px);transition:all .4s}
</style>
<?php
}

function am_foot()
{
?>
<script type="text/javascript">
$(function(){
  var $form  = $('.assign-panel').closest('form'),
      $panel = $('.assign-panel'),
      $btn   = $('.ap-go button'),
      $sel   = $panel.find('select'),
      $date  = $('.ap-date'),
      $msg   = $('#message');

  /* ---- re-assign: the current owner of a selected article cannot be picked again ---- */
  var $newSel = $('#ruser_id,#re_user_id');
  var $warn = $('<div class="ap-warn"></div>');
  if ($newSel.length) { $newSel.after($warn); }
  function syncOwners(){
    if (!$newSel.length) return;
    var owners = {};
    $('.aids:checked').each(function(){
      var id = String($(this).data('owner') || '');
      if (id && id !== '0') { owners[id] = owners[id] || { n: 0, name: $(this).data('owner-name') || 'This user' }; owners[id].n++; }
    });
    var msgs = [];
    $newSel.find('option').each(function(){
      var $o = $(this), base = $o.data('base');
      if (base === undefined) { base = $o.text(); $o.data('base', base); }
      var o = owners[$o.val()];
      if (o && $o.val() !== '') { $o.prop('disabled', true).text(base + '  (already has ' + o.n + ' selected)'); msgs.push(o.name + ' already holds ' + o.n + ' of the selected article(s)'); }
      else { $o.prop('disabled', false).text(base); }
    });
    var cur = $newSel.find('option:selected');
    if (cur.length && cur.prop('disabled')) { $newSel.val(''); }
    $warn.html(msgs.length ? '&#9888; ' + msgs.join('; ') + '. They are disabled in the list - pick someone else.' : '').toggleClass('show', msgs.length > 0);
  }
  /* ---- user lists: logged-in marker legend + note for offline users ---- */
  var $userSel = $panel.find('select').filter(function(){ return $(this).find('option[data-online]').length > 0; }).first();
  if ($userSel.length) {
    var $legend = $('<div class="ap-legend"><span class="on">\u25CF</span> logged in today &nbsp;&nbsp; \u25CB not logged in today</div>');
    var $note = $('<div class="ap-note"></div>');
    $userSel.after($legend);
    $legend.after($note);
    $userSel.on('change', function(){
      var $o = $(this).find('option:selected');
      if ($o.length && $o.val() !== '' && String($o.data('online')) === '0') {
        $note.html('\u25CB ' + $.trim($o.text().replace(/^[\u25CF\u25CB]\s*/, '')) + ' has not logged in today - the assignment is saved, but they may not see it until they log in.').addClass('show');
      } else { $note.removeClass('show'); }
    });
  }
  /* ---- selection state ---- */
  var last = -1;
  function refreshSel(){
    var n = $('input[name="aids[]"]:checked').length;
    $('#ap_count_n').text(n);
    $('#ap_count').toggleClass('zero', n === 0);
    if (n !== last) { $('#ap_count').removeClass('pop'); void $('#ap_count')[0].offsetWidth; $('#ap_count').addClass('pop'); last = n; }
    $btn.prop('disabled', n === 0);
    $('#a_count').text(n).toggleClass('on', n > 0).removeClass('pop'); void $('#a_count')[0].offsetWidth; $('#a_count').addClass('pop');
    $panel.toggleClass('active', n > 0).toggleClass('idle', n === 0);
    $('#ap_hint').text(n === 0 ? 'Tick articles above to enable' : 'Ready for ' + n + ' article(s)');
    $('.aids').each(function(){ $(this).closest('tr').toggleClass('row-selected', this.checked); });
    syncOwners();
  }
  $(document).on('click change', '.aids,#checkAll', function(){ setTimeout(refreshSel, 0); });
  $('#dynamic-table tbody').on('click', 'tr', function(e){
    if ($(e.target).is('input,label,span.lbl,a,button')) return;
    var cb = $(this).find('.aids')[0]; if (cb) cb.click();
  });
  $('<button type="button" class="ap-clear">Clear selection</button>').appendTo('.assign-panel .ap-head').on('click', function(){
    $('.aids,#checkAll').prop('checked', false); $('#a_count,#aa_count').html(0); refreshSel();
  });
  refreshSel();

  /* ---- due date shortcuts ---- */
  $('.ap-quick button').on('click', function(){
    var d = new Date(); d.setDate(d.getDate() + parseInt($(this).data('days'), 10));
    var p = function(x){ return ('0' + x).slice(-2); };
    $date.val(p(d.getDate()) + '-' + p(d.getMonth() + 1) + '-' + d.getFullYear()).trigger('change');
  });

  /* ---- filter bar: apply on change, reset link, required check ---- */
  var $f = $('form[name="articles"]').attr('novalidate', 'novalidate');
  $f.find('select[required]').each(function(){
    $(this).closest('.af-field').append('<div class="af-err">Please select this</div>');
    $(this).on('change', function(){ $(this).closest('.af-field').removeClass('has-err'); });
  });
  function applyFilters(){
    var bad = $f.find('select[required]').filter(function(){ return !$(this).val(); });
    bad.each(function(){ $(this).closest('.af-field').addClass('has-err'); });
    if (bad.length) { bad.first().focus(); return; }
    $('.af-card').addClass('am-busy');
    $f[0].submit();
  }
  $f.on('submit', function(e){ e.preventDefault(); applyFilters(); });
  $f.find('select,input[type=checkbox]').on('change', function(){
    if (this.id === 'dept_id' || this.id === 'dep_id') { $('#r_user_id').val(''); }
    applyFilters();
  });
  var qs = window.location.search.replace(/[?&]msg=[^&]*/, '');
  $('<a class="am-reset' + (qs.length > 1 ? ' show' : '') + '" href="' + window.location.pathname + '">&#8635; Reset filters</a>').appendTo($f.find('.af-actions'));

  /* ---- toast ---- */
  var $t = $('#successMessage');
  if ($t.length) { setTimeout(function(){ $t.addClass('out'); setTimeout(function(){ $t.remove(); }, 450); }, 5200); }

  /* ---- confirmation dialog ---- */
  $sel.closest('div').append('<div class="ap-err">Please select</div>');
  $date.closest('div').parent().append('<div class="ap-err">Please pick a due date</div>');
  $sel.on('change', function(){ $(this).parent().removeClass('has-err'); });
  $date.on('change input', function(){ $(this).closest('div').parent().removeClass('has-err'); });
  function closeDlg(){ $('#cm_back').removeClass('open'); }
  $('#cm_cancel').on('click', closeDlg);
  $('#cm_back').on('click', function(e){ if (e.target === this) closeDlg(); });
  $(document).on('keydown', function(e){ if (e.key === 'Escape') closeDlg(); });
  $form.on('submit', function(e){
    e.preventDefault(); e.stopImmediatePropagation();
    var ids = $('input[name="aids[]"]:checked').map(function(){ return this.value; }).get();
    if (!ids.length) { alert('Please tick at least one article.'); return false; }
    var bad = false;
    if (!$sel.val()) { $sel.parent().addClass('has-err'); bad = true; }
    if (!$.trim($date.val())) { $date.closest('div').parent().addClass('has-err'); bad = true; }
    if (bad) { $('.has-err').first().find('select,input').first().focus(); return false; }
    var who = $.trim($sel.find('option:selected').text().replace(/^[\u25CF\u25CB]\s*/, '')), bt = $btn.text().replace(/\s+/g, ' ').trim();
    $('#cm_title').text($('.ap-title').text());
    $('#cm_who_l').text($sel.closest('div').find('label.ap-label').text().replace('*', '').trim());
    $('#cm_who').text(who); $('#cm_due').text($date.val());
    if ($msg.length) { $('#cm_msg_row').show(); $('#cm_msg').text($.trim($msg.val()) || '-'); } else { $('#cm_msg_row').hide(); }
    $('#cm_n').text(ids.length); $('#cm_sub').text(ids.length + ' selected');
    var $c = $('#cm_chips').empty();
    $.each(ids, function(i, v){ $('<span>').text($('input.aids[value="' + v + '"]').closest('tr').find('td:eq(2)').text().trim().split(/\s+/)[0] || v).css('animation-delay', (i * 30) + 'ms').appendTo($c); });
    $('#cm_ok').prop('disabled', false).text('Confirm & ' + bt);
    try { $date.datepicker('hide'); } catch (x) {}
    $('#cm_back').addClass('open'); $('#cm_ok').focus();
    return false;
  });
  $('#cm_ok').on('click', function(){
    $('<input type="hidden">').attr({ name: $btn.attr('name'), value: $btn.attr('value') }).appendTo($form);
    $(this).prop('disabled', true).text('Assigning...');
    $form[0].submit();
  });
});
</script>
<?php
}
