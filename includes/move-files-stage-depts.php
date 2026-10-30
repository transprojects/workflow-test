<style>
#accordion .mf-hidden,#accordion .d-md-flex.mf-row.mf-hidden,.mf-hidden{display:none !important;}
/* tidy table-like layout for the department rows (Add / Edit Move Files) */
#accordion .panel-body{padding:4px 2px 10px;}
#accordion .panel-body > .col-lg-12{max-width:100%;}
#accordion .mf-head,#accordion .d-md-flex.mf-row:not(.mf-hidden){display:grid !important;grid-template-columns:190px minmax(0,1fr) minmax(0,1fr) 110px;gap:12px;align-items:center;margin-bottom:0 !important;padding:7px 10px;border-bottom:1px solid #eef1f5;}
#accordion .mf-head{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6b7785;border-bottom:2px solid #dfe5ec;padding-bottom:9px;}
#accordion .d-md-flex.mf-row:hover{background:#f7f9fc;}
#accordion .d-md-flex.mf-row > label{width:auto !important;max-width:none;margin:0;font-size:13px !important;font-weight:600;color:#1f2d3d;text-transform:none !important;}
#accordion .d-md-flex.mf-row > div{width:auto !important;max-width:none;flex:none;font-size:0;line-height:0;}   /* hides the repeated "Files" / "Folder" captions, the column header says it once */
#accordion .d-md-flex.mf-row input.form-control{font-size:13px;line-height:1.4;height:34px !important;}
#accordion .d-md-flex.mf-row.mf-filled > label::after{content:"\2713";margin-left:7px;color:#1e9e61;font-weight:700;}
#accordion .d-md-flex.mf-row.mf-filled input.form-control:not(:placeholder-shown){border-color:#9fd8b8;background:#f4fbf7;}
.mf-toggle-bar{display:flex;align-items:center;justify-content:space-between;gap:10px;margin:2px 0 10px !important;}
.mf-toggle-bar .mf-note{font-size:12px;color:#6b7785;}
/* Cut (move) switch: last column, shown once the row has a Files / Folder entry */
#accordion .mf-cutbox label{font-size:12px;line-height:1.3;font-weight:600;text-transform:none;color:#55657a;display:inline-flex;align-items:center;gap:8px;white-space:nowrap;cursor:pointer;}
#accordion .mf-cutbox.mf-cut-off{visibility:hidden;}
@media (max-width:767px){#accordion .mf-head{display:none !important;}#accordion .d-md-flex.mf-row{grid-template-columns:1fr;gap:6px;padding:10px 4px;}}
/* stages as tabs instead of a stacked accordion: one line, pill style */
#accordion > h3.ui-accordion-header{display:none !important;}
.mf-tabs{display:flex;flex-wrap:nowrap;gap:6px;margin:8px 0 16px;padding:6px;background:#f1f4f9;border-radius:14px;overflow-x:auto;scrollbar-width:thin;-webkit-overflow-scrolling:touch;}
.mf-tab{flex:1 1 auto;display:inline-flex;align-items:center;justify-content:center;gap:7px;white-space:nowrap;border:0;background:transparent;padding:9px 14px;font-size:13px;font-weight:600;color:#55657a;border-radius:10px;cursor:pointer;transition:background .15s,color .15s,box-shadow .15s,transform .15s;}
.mf-tab:hover{background:#fff;color:#1f2d3d;box-shadow:0 2px 8px rgba(20,48,74,.08);}
.mf-tab.active{background:linear-gradient(120deg,#e8742a,#f09a4f);color:#fff;box-shadow:0 6px 14px rgba(232,116,42,.35);transform:translateY(-1px);}
.mf-tab .mf-count{display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;padding:0 6px;border-radius:10px;background:#d9f2e3;color:#157a4a;font-size:11px;font-weight:700;line-height:1;}
.mf-tab .mf-count.zero{background:#e3e8ef;color:#8a97a6;}
.mf-tab.active .mf-count{background:rgba(255,255,255,.28);color:#fff;}
.mf-tab.has-data:not(.active){color:#1f2d3d;}
#accordion > .ui-accordion-content{border:1px solid #e3e8ef !important;border-radius:14px !important;box-shadow:0 4px 18px rgba(20,48,74,.06);background:#fff !important;}
@media (max-width:767px){.mf-tab{padding:8px 11px;font-size:12px;}}
/* the "Show other departments" button: clearly visible */
.mf-toggle-bar .mf-toggle{border:0 !important;background:linear-gradient(120deg,#1b6ca8,#14304a) !important;color:#fff !important;font-size:13px !important;font-weight:700 !important;padding:9px 18px !important;border-radius:10px !important;box-shadow:0 6px 14px rgba(20,48,74,.28);cursor:pointer;transition:transform .15s,box-shadow .2s,filter .2s;}
.mf-toggle-bar .mf-toggle::before{content:"\25BE";margin-right:8px;font-size:12px;display:inline-block;transition:transform .2s;}
.mf-toggle-bar .mf-toggle.is-open::before{transform:rotate(180deg);}
.mf-toggle-bar .mf-toggle:hover:not(:disabled){transform:translateY(-1px);box-shadow:0 9px 18px rgba(20,48,74,.36);filter:brightness(1.08);}
.mf-toggle-bar .mf-toggle.is-open{background:linear-gradient(120deg,#e8742a,#f09a4f) !important;box-shadow:0 6px 14px rgba(232,116,42,.35);}
.mf-toggle-bar .mf-toggle:disabled{background:#e3e8ef !important;color:#8a97a6 !important;box-shadow:none;cursor:default;}
.mf-toggle-bar .mf-toggle:disabled::before{content:"";margin:0;}
.mf-toggle-bar .mf-note{font-size:13px;color:#4a5b70;font-weight:500;}
/* placeholders must look like hints, not like typed values */
#accordion input.form-control::placeholder{color:#b4bfcc !important;font-style:italic;font-weight:400;opacity:1;}
#accordion input.form-control::-webkit-input-placeholder{color:#b4bfcc !important;font-style:italic;font-weight:400;}
#accordion input.form-control:focus::placeholder{color:#d3dae3 !important;}
#accordion input.form-control:not(:placeholder-shown){color:#14304a;font-weight:600;}
/* the department chosen above is the SOURCE: files never move from a department to itself */
#accordion .d-md-flex.mf-row.mf-self{opacity:.55;background:repeating-linear-gradient(135deg,#f6f8fb,#f6f8fb 8px,#eef1f5 8px,#eef1f5 16px);}
#accordion .d-md-flex.mf-row.mf-self > label{text-decoration:line-through;color:#8a97a6 !important;}
#accordion .d-md-flex.mf-row.mf-self input.form-control{background:#eef1f5 !important;cursor:not-allowed;}
.mf-self-tag{display:inline-block;margin-left:8px;font-size:10px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#6b7785;background:#e3e8ef;border-radius:8px;padding:1px 7px;text-decoration:none;}
.mf-toggle-bar{align-items:flex-start;}
.mf-note{display:flex;flex-wrap:wrap;align-items:center;gap:8px 10px;min-width:0;}
.mf-wf-label{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#6b7785;}
.mf-wf-name{font-size:13px;color:#14304a;background:#eef3f9;border-radius:8px;padding:2px 10px;}
.mf-flow{display:inline-flex;flex-wrap:wrap;align-items:center;gap:6px;}
.mf-chip{font-size:12px;font-weight:600;color:#1b6ca8;background:#e8f0fa;border-radius:12px;padding:2px 10px;}
.mf-chip-src{background:#fde7d6;color:#c8601a;}
.mf-toggle-bar .mf-toggle{white-space:nowrap;flex:0 0 auto;}
.mf-toggle-bar{gap:12px;}
.mf-wf-none{font-size:12px;color:#8a97a6;font-style:italic;}
.mf-arrow{color:#9aa7b6;font-size:12px;}
.mf-locknote{margin-top:12px;font-size:13px;font-weight:500;color:#6b4a0a;background:linear-gradient(120deg,#fff4dc,#fff9ec);border:1px solid #f3d99b;border-left:4px solid #f0a920;border-radius:10px;padding:10px 14px 10px 40px;position:relative;box-shadow:0 2px 8px rgba(240,169,32,.12);}
.mf-locknote::before{content:"\1F512";position:absolute;left:13px;top:9px;font-size:15px;}
/* Add page: departments that already have a record */
#dept_id option:disabled{background:#fdecec;color:#b42318;}
.mf-existing{margin-top:12px;font-size:13px;color:#7a2a12;background:linear-gradient(120deg,#fdecec,#fff4f0);border:1px solid #f4c3bd;border-left:4px solid #e5533d;border-radius:10px;padding:10px 14px;box-shadow:0 2px 8px rgba(229,83,61,.10);}
.mf-existing b{color:#b42318;}
.mf-chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px;}
.mf-chip-ex{font-size:12px;font-weight:600;color:#b42318;background:#fff;border:1px solid #f1b3ab;border-radius:12px;padding:2px 10px;text-decoration:none;}
.mf-chip-ex:hover{background:#b42318;color:#fff;}
</style>
<script>
/* Add / Edit Move Files: per stage show only the departments of the journal's assigned workflow; a button reveals the others. */
(function ($) {
  if (!$) { return; }
  var map = {};
  function fitAccordion() { try { $('#accordion').accordion('option', 'heightStyle', 'content'); } catch (e) {} }
  function refresh() { try { $('#accordion').accordion('refresh'); } catch (e) {} $('#accordion > .ui-accordion-content').css({ height: 'auto', overflow: 'visible' }); }
  /* ---- stage tabs: one tab per accordion header, they drive the accordion ---- */
  function buildTabs() {
    var $acc = $('#accordion'); if (!$acc.length || $('.mf-tabs').length) { return; }
    var $bar = $('<div class="mf-tabs" role="tablist"></div>');
    $acc.children('h3').each(function (i) {
      $bar.append($('<button type="button" class="mf-tab" role="tab"></button>').attr('data-i', i).html($('<span>').text($(this).text()).prop('outerHTML') + '<span class="mf-count zero">0</span>'));
    });
    $acc.before($bar);
    $bar.on('click', '.mf-tab', function () { try { $acc.accordion('option', 'active', parseInt($(this).attr('data-i'), 10)); } catch (e) {} syncTabs(); setTimeout(refresh, 50); });
    $acc.on('accordionactivate accordioncreate', syncTabs);
    syncTabs();
  }
  function syncTabs() {
    var act = 0; try { act = $('#accordion').accordion('option', 'active'); } catch (e) {}
    $('.mf-tab').each(function () {
      var i = parseInt($(this).attr('data-i'), 10), $p = $('#accordion > div').eq(i);
      var n = $p.find('input[name^="files["]').filter(function () { return $.trim(this.value) !== ''; }).length;
      $(this).toggleClass('active', i === act).toggleClass('has-data', n > 0).find('.mf-count').text(n).toggleClass('zero', !n);
    });
  }
  /* disable the row of the department selected in the header (source -> other departments only) */
  function applySelf() {
    var code = parseInt($('#dept_id').val(), 10);
    $('#accordion input[name^="files["]').each(function () {
      var k = stageOf(this.name); if (!k) { return; }
      var $row = $(this).closest('.d-md-flex'), self = !isNaN(code) && k.dept === code;
      $row.toggleClass('mf-self', self).find('input').prop('disabled', self);
      var $lab = $row.children('label').first(); $lab.find('.mf-self-tag').remove();
      if (self) { $lab.append('<span class="mf-self-tag">Source dept</span>'); }
    });
    syncTabs();
  }
  /* "Workflow: NAME   Pre-Clean > CE > PAG ..." strip shown on each stage tab */
  function esc(t) { return $('<div>').text(t == null ? '' : t).html(); }
  function flowHtml(stage, allowed) {
    var names = (map && map._names) || {}, dn = (map && map._depts) || {}, src = parseInt($('#dept_id').val(), 10), h = '<span class="mf-wf-label">Workflow</span><b class="mf-wf-name">' + esc(names[stage] || '') + '</b><span class="mf-flow">';
    $.each(allowed, function (i, c) {
      h += (i ? '<span class="mf-arrow">&rarr;</span>' : '') + '<span class="mf-chip' + (c === src ? ' mf-chip-src' : '') + '">' + esc(dn[c] || ('Dept ' + c)) + '</span>';
    });
    return h + '</span>';
  }
  /* ---- Edit page: customer / journal / department identify the record -> locked ---- */
  function lockHeader() {
    if (!$('input[name="action"][value="update"]').length || $('.mf-locknote').length) { return; }
    /* not "disabled" (the page's Required n/2 counter and the post both rely on them): just not changeable */
    $('#cust_id, #j_id, #dept_id').each(function () {
      $(this).attr({ tabindex: '-1', 'aria-readonly': 'true' }).css({ background: '#eef1f5', cursor: 'not-allowed', pointerEvents: 'none' })
        .on('mousedown keydown focus', function (e) { e.preventDefault(); this.blur(); });
    });
    $('<div class="mf-locknote col-12">Customer, journal and department identify this record and cannot be changed here. To set up a different combination, add a new Move Files record.</div>').insertAfter($('#dept_id').closest('[class*="col-"]'));
  }
  /* ---- Add page: grey out departments that already have a record for the chosen customer + journal ---- */
  function markExisting() {
    if (!$('input[name="action"][value="insert"]').length) { return; }
    var ex = (map && map._existing) || {}, dn = (map && map._depts) || {};
    $('#dept_id option').each(function () {
      var $o = $(this), v = $o.val(); if (!v) { return; }
      var base = $o.data('base'); if (base === undefined) { base = $o.text(); $o.data('base', base); }
      var dup = ex[v] !== undefined;
      $o.prop('disabled', dup).text(dup ? base + '  (already added)' : base);
    });
    var $sel = $('#dept_id'); if ($sel.find('option:selected').prop('disabled')) { $sel.val(''); applySelf(); }
    /* highlighted notice under the dropdown: which departments are already set up (each links to its record) */
    $('.mf-existing').remove();
    var chips = [];
    $.each(ex, function (code, mf) { chips.push('<a class="mf-chip-ex" href="edit-move-files/' + mf + '" title="Open this record">' + esc(dn[code] || ('Dept ' + code)) + '</a>'); });
    if (chips.length) {
      $('<div class="mf-existing col-12"><b>' + chips.length + ' department' + (chips.length === 1 ? '' : 's') + ' already set up for this journal</b> - choose a different department, or open the record to edit it:<div class="mf-chips">' + chips.join('') + '</div></div>')
        .insertAfter($('#dept_id').closest('[class*="col-"]'));
    }
  }
  /* the Cut switch is offered on a row once something is entered in its Files or Folder box (or it is already on) */
  function applyNext() {
    $('#accordion .mf-cutbox').each(function () {
      var $c = $(this), $row = $c.closest('.d-md-flex');
      var filled = $row.find('input[type=text]').filter(function () { return $.trim(this.value) !== ''; }).length > 0;
      $c.toggleClass('mf-cut-off', !(filled || $c.find('input:checked').length > 0));
    });
  }
  function stageOf(name) { var m = /^files\[([^\]]+)\]\[(\d+)\]$/.exec(name); return m ? { stage: m[1], dept: parseInt(m[2], 10) } : null; }
  function apply() {
    var j = $('#j_id').val();
    var rows = {};
    $('#accordion input[name^="files["]').each(function () {
      var k = stageOf(this.name); if (!k) { return; }
      var $row = $(this).closest('.d-md-flex'); $row.data('mf', k);
      (rows[k.stage] = rows[k.stage] || []).push($row);
    });
    $.each(rows, function (stage, list) {
      var $panel = list[0].closest('.panel-body'), $bar = $panel.find('.mf-toggle-bar');
      if (!$panel.find('.mf-head').length) { $panel.find('.col-lg-12').first().prepend('<div class="mf-head"><span>Department</span><span>Files to move (e.g. tex|pdf|zip)</span><span>Folder</span><span>Cut</span></div>'); }
      $.each(list, function (i, $r) { $r.addClass('mf-row').toggleClass('mf-filled', $r.find('input[type=text]').filter(function () { return $.trim(this.value) !== ''; }).length > 0); });
      var allowed = map[stage];
      if (!allowed || !allowed.length) {
        $.each(list, function (i, $r) { $r.removeClass('mf-extra mf-hidden'); });
        if (!$bar.length) { $bar = $('<div class="mf-toggle-bar"><span class="mf-note"></span></div>'); $panel.prepend($bar); }
        $bar.find('.mf-toggle').remove();
        $bar.find('.mf-note').html(j ? '<span class="mf-wf-label">Workflow</span><span class="mf-wf-none">No workflow assigned to this stage - all departments are listed</span>' : '');
        refresh();
        return;
      }
      /* list the departments in the order of the workflow configuration, the others after them */
      var $box = list[0].parent(), byDept = {}, rest = [];
      $.each(list, function (i, $r) { byDept[$r.data('mf').dept] = $r; });
      $.each(allowed, function (i, code) { if (byDept[code]) { $box.append(byDept[code]); byDept[code] = null; } });
      $.each(list, function (i, $r) { if (byDept[$r.data('mf').dept]) { rest.push($r); } });
      $.each(rest, function (i, $r) { $box.append($r); });
      var hidden = 0;
      $.each(list, function (i, $r) {
        var k = $r.data('mf'), keep = allowed.indexOf(k.dept) !== -1 || $r.find('input[type=text]').filter(function () { return $.trim(this.value) !== ''; }).length > 0;   /* a row that already has a value stays visible */
        $r.toggleClass('mf-extra', !keep);
        if (!keep) { hidden++; }
      });
      if (!$bar.length) {
        $bar = $('<div class="mf-toggle-bar"><span class="mf-note"></span><button type="button" class="btn btn-sm btn-outline-secondary mf-toggle"></button></div>');
        $panel.prepend($bar);
      }
      $bar.data('shown', false);
      $.each(list, function (i, $r) { $r.toggleClass('mf-hidden', $r.hasClass('mf-extra')); });
      var $b = $bar.find('button').data({ hidden: hidden, stage: stage });
      $bar.find('.mf-note').html(flowHtml(stage, allowed));
      refresh();
      $b.text(hidden ? 'Show other departments (' + hidden + ')' : 'All departments are in the workflow').prop('disabled', !hidden);
    });
  }
  $(document).on('input change', '#accordion input[type=text], #accordion .mf-cutbox input', function () { syncTabs(); applyNext(); });
  $(document).on('click', '.mf-toggle', function () {
    var $b = $(this), $bar = $b.closest('.mf-toggle-bar'), show = !$bar.data('shown');
    $bar.data('shown', show);
    $b.closest('.panel-body').find('.mf-extra').toggleClass('mf-hidden', !show);
    refresh();
    $b.toggleClass('is-open', show);
    $b.text(show ? 'Hide other departments' : 'Show other departments (' + $b.data('hidden') + ')');
  });
  function load() {
    var j = $('#j_id').val();
    if (!j) { map = {}; apply(); applySelf(); markExisting(); applyNext(); return; }
    $.getJSON(<?php echo json_encode(rtrim(str_replace(chr(92), '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/get_move_stage_depts.php'); ?>, { j_id: j, cust_id: $('#cust_id').val() }, function (d) { map = d || {}; apply(); applySelf(); markExisting(); applyNext(); }).fail(function () { map = {}; apply(); applySelf(); });
  }
  $(function () { fitAccordion(); buildTabs(); lockHeader(); $('#dept_id').on('change', function () { apply(); applySelf(); applyNext(); }); setTimeout(load, 300);
    /* the department dropdown can be filled after the page script ran (no change event): follow its value so the source row / next department stay right */
    var lastSrc; setInterval(function () { var v = $('#dept_id').val(); if (v !== lastSrc) { lastSrc = v; applySelf(); applyNext(); } }, 400);
    $('#j_id').on('change', function () { setTimeout(load, 300); }); });
})(window.jQuery);
</script>
