<?php
/* ============================================================================
 * Filter bar for list pages that have a filter form above the table.
 * Same look as the Current Articles page (includes/article-filter-ui.php):
 *   [ search ] [ date range picker ]            [ Filters (n) ] [ Get Result ] [ reset ]
 *   and a collapsible panel with the dropdowns.
 *
 * It restructures the existing form in the browser (the fields are MOVED, not recreated), so the
 * field names, ids, select2 widgets and the page's own scripts keep working.  The two date inputs
 * (from_dt / to_dt) become the range picker (dv-rangepicker.js), which writes the same two names.
 * Called by lu_close().
 * ========================================================================== */
function ui_filterbar()
{
	static $done = false;
	if ($done) { return; }
	$done = true;
	$base = isset($_COOKIE['set_url']) ? $_COOKIE['set_url'] : '';
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($base); ?>/dv-rangepicker.css?v=1">
<style id="ui-filterbar">
.af-wrap{--navy:#14304a;--accent:#e8742a;--blue:#1b6ca8;--ink:#22303f;--mute:#6b7785;--line:#e4e9ef;}
.af-wrap form.formouter,form.formouter.af-wrap{background:transparent !important;border:0 !important;box-shadow:none !important;padding:4px 6px !important;margin:0 !important;}
.af-wrap .af-bar{display:flex;flex-wrap:wrap;align-items:flex-end;gap:10px 14px;}
.af-wrap .af-field{display:flex;flex-direction:column;gap:5px;min-width:150px;margin:0;padding:0;flex:0 1 auto;}
.af-wrap .af-field label{margin:0 !important;font-size:10.5px !important;font-weight:700 !important;text-transform:uppercase;letter-spacing:.5px;color:var(--mute) !important;}
.af-wrap .af-field .form-control,.af-wrap .af-field .form-select{height:42px !important;border-radius:10px !important;border:1px solid #d5dde6 !important;font-size:14px;padding:0 12px !important;box-shadow:none !important;background:#fbfcfe !important;width:100%;}
.af-wrap .af-field .form-control:focus{border-color:var(--blue) !important;box-shadow:0 0 0 3px rgba(27,108,168,.15) !important;}
.af-wrap .af-field.search{flex:1 1 220px;max-width:340px;position:relative;}
.af-wrap .af-field.search .form-control{padding-left:36px !important;}
.af-wrap .af-field.search:before{content:"\f002";font-family:FontAwesome;position:absolute;left:13px;bottom:12px;color:#9aa6b2;pointer-events:none;z-index:1;}
.af-wrap .af-field.sel{min-width:200px;flex:1 1 200px;max-width:320px;}
.af-wrap .af-field.dvr-field .dvr{min-width:230px;display:block;}
.af-wrap .af-actions{display:flex;gap:8px;align-items:center;margin-left:auto;}
.af-wrap .af-btn{display:inline-flex;align-items:center;gap:7px;height:42px;padding:0 18px;border-radius:10px;border:1px solid var(--line);background:#fff;color:var(--navy);font-weight:600;font-size:14px;cursor:pointer;transition:transform .15s,box-shadow .2s;text-decoration:none !important;width:auto;margin:0 !important;}
.af-wrap .af-btn:hover{transform:translateY(-2px);box-shadow:0 6px 14px rgba(20,48,74,.14);color:var(--navy);}
.af-wrap .af-btn.primary{background:linear-gradient(135deg,var(--accent),#f08a45);border-color:transparent;color:#fff !important;box-shadow:0 4px 12px rgba(232,116,42,.35);}
.af-wrap .af-btn.danger{background:#fdeeee;border-color:#f7d4d4;color:#d64545 !important;}
.af-wrap .af-btn.danger:hover{background:#d64545;color:#fff !important;}
.af-wrap .af-btn.dark{background:var(--navy);border-color:var(--navy);color:#fff !important;}
.af-wrap .af-btn.icon{padding:0;width:42px;justify-content:center;}
.af-wrap .af-btn .af-count{display:none;min-width:20px;height:20px;line-height:20px;padding:0 6px;border-radius:20px;background:var(--accent);color:#fff;font-size:11px;text-align:center;}
.af-wrap .af-btn.has .af-count{display:inline-block;}
.af-wrap .af-btn .fa-angle-down{transition:transform .25s;} .af-wrap .af-btn.open .fa-angle-down{transform:rotate(180deg);}
.af-wrap .af-panel{display:none;margin-top:12px;padding-top:12px;border-top:1px dashed var(--line);}
.af-wrap .af-panel.open{display:block;}
.af-wrap .af-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px 14px;}
@media(max-width:1200px){.af-wrap .af-grid{grid-template-columns:repeat(3,minmax(0,1fr));}}
@media(max-width:800px){.af-wrap .af-grid{grid-template-columns:repeat(2,minmax(0,1fr));}}
@media(max-width:640px){.af-wrap .af-grid{grid-template-columns:1fr;}.af-wrap .af-actions{margin-left:0;width:100%;}}
.af-wrap .af-grid .af-field{min-width:0;max-width:none;}
.af-wrap .af-grid .select2-container,.af-wrap .af-field.sel .select2-container{width:100% !important;}
.af-wrap .select2-container--default .select2-selection--multiple,.af-wrap .select2-container--default .select2-selection--single{min-height:42px !important;border:1px solid #d5dde6 !important;border-radius:10px !important;background:#fbfcfe !important;}
.af-wrap .select2-container--default .select2-selection--multiple{max-height:96px;overflow-y:auto;}
.af-wrap .select2-container--default .select2-selection--multiple .select2-selection__choice{display:inline-flex !important;align-items:center;flex-direction:row-reverse;gap:7px;background:#eef1f5 !important;border:0 !important;border-radius:5px !important;padding:2px 9px 2px 6px !important;margin:3px 4px 3px 0 !important;color:#22303f !important;font-size:13px;line-height:1.4;}
.af-wrap .select2-container--default .select2-selection--multiple .select2-selection__choice__remove{position:static !important;float:none !important;border:0 !important;background:transparent !important;color:#8a95a1 !important;font-weight:700;margin:0 !important;padding:0 7px 0 0 !important;height:auto !important;width:auto !important;border-right:1px solid #cfd6de !important;}
.af-wrap .select2-selection--multiple .select2-search__field{height:26px !important;min-height:0 !important;border:0 !important;background:transparent !important;box-shadow:none !important;padding:0 4px !important;margin:3px 0 !important;border-radius:0 !important;line-height:20px !important;resize:none;overflow:hidden !important;}
.af-wrap .af-chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px;} .af-wrap .af-chips:empty{display:none;}
.af-wrap .af-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 6px 4px 11px;border-radius:20px;background:#eaf2fa;color:var(--blue);font-size:12px;font-weight:600;}
.af-wrap .af-chip b{color:var(--navy);} .af-wrap .af-chip button{border:0;background:rgba(27,108,168,.15);color:var(--blue);width:18px;height:18px;line-height:16px;border-radius:50%;padding:0;cursor:pointer;font-size:13px;}
.af-wrap .af-chip button:hover{background:var(--blue);color:#fff;}
</style>
<script>
window.addEventListener('load', function () {
  var $ = window.jQuery; if (!$) { return; }
  var form = document.querySelector('.divouter form.formouter'), sd = form && form.querySelector('.selectdiv');
  if (!form || !sd || form.getAttribute('data-afbar')) { return; }
  form.setAttribute('data-afbar', '1');
  function el(tag, cls, html) { var e = document.createElement(tag); if (cls) { e.className = cls; } if (html != null) { e.innerHTML = html; } return e; }

  var from = form.querySelector('input[name="from_dt"]'), to = form.querySelector('input[name="to_dt"]');
  var cols = [].slice.call(sd.children), bar = el('div', 'af-bar'), grid = el('div', 'af-grid'), actions = el('div', 'af-actions');
  var fieldOf = function (c) { return c.querySelector('label') ? c : c; };
  var selects = [], searches = [], dateCols = [];
  cols.forEach(function (c) {
    if (c.querySelector('input[name="from_dt"], input[name="to_dt"]')) { dateCols.push(c); }
    else if (c.querySelector('select')) { selects.push(c); }
    else if (c.querySelector('input[type=text],input[type=search],input:not([type])')) { searches.push(c); }
  });

  function makeField(c, kind) {                                    /* keep the original nodes (label + control) */
    var f = el('div', 'af-field' + (kind ? ' ' + kind : ''));
    [].slice.call(c.childNodes).forEach(function (n) { f.appendChild(n); });
    return f;
  }
  searches.forEach(function (c) { var f = makeField(c, 'search'); bar.appendChild(f); });

  if (from && to) {                                                 /* two date inputs -> one range picker */
    var fmt = 'dmy', hasDP = false;
    try { var df = $(from).datepicker('option', 'dateFormat'); if (df === 'yy-mm-dd') { fmt = 'ymd'; } hasDP = true; } catch (e) {}
    var f = el('div', 'af-field date dvr-field'), lab = el('label', '', 'Date range'), box = el('div', 'dvr');
    box.setAttribute('data-from-name', 'from_dt'); box.setAttribute('data-to-name', 'to_dt');
    box.setAttribute('data-from', from.value || ''); box.setAttribute('data-to', to.value || ''); box.setAttribute('data-format', fmt);
    f.appendChild(lab); f.appendChild(box); bar.appendChild(f);
    dateCols.forEach(function (c) { c.parentNode && c.parentNode.removeChild(c); });
    if (hasDP) { try { $(from).datepicker('destroy'); $(to).datepicker('destroy'); } catch (e) {} }
    var s = document.createElement('script'); s.src = '<?php echo htmlspecialchars($base); ?>/dv-rangepicker.js?v=1';
    s.onload = function () { if (window.dvrInit) { window.dvrInit(); } };
    document.body.appendChild(s);
  }

  var inBar = selects.length <= 2 && !searches.length && !(from && to);      /* only a couple of dropdowns: keep them on the bar */
  selects.forEach(function (c) {
    var f = makeField(c, inBar ? 'sel' : '');
    (inBar ? bar : grid).appendChild(f);
  });

  /* buttons: whatever the old form had below / beside the fields */
  var seen = [];
  [].slice.call(form.querySelectorAll('button, a.refreshbtn, input[type=submit]')).forEach(function (b) {
    if (b.closest('.select2-container') || seen.indexOf(b) > -1) { return; }
    seen.push(b);
    var txt = (b.textContent || b.value || '').trim(), isRefresh = b.classList.contains('refreshbtn') || b.tagName === 'A';
    var host = b.parentNode;
    var isReset = (b.name === 'btn-reset');
    b.className = 'af-btn' + (isRefresh ? ' icon' : (isReset ? ' danger' : (b.type === 'submit' ? ' primary' : ' dark')));
    b.classList.remove('resultbtn', 'btn', 'ms-4');
    if (isRefresh) { b.innerHTML = '<i class="fa fa-refresh"></i>'; b.title = 'Reset filters'; }
    else if (isReset) { b.removeAttribute('style'); b.innerHTML = '<i class="fa fa-undo"></i> ' + (txt || 'Reset'); }
    else if (b.type === 'submit') { b.innerHTML = '<i class="fa fa-search"></i> ' + (txt || 'Get Result'); }
    else { b.innerHTML = (txt ? txt : '<i class="fa fa-download"></i>'); }
    actions.appendChild(b);
    if (host && host !== form && host !== actions && !host.querySelector('select, input')) { host.className = 'af-gone'; host.style.display = 'none'; }
  });

  var hasPanel = grid.children.length > 0, toggle = null, panel = null, chips = el('div', 'af-chips');
  if (hasPanel) {
    toggle = el('button', 'af-btn', '<i class="fa fa-sliders"></i> Filters <span class="af-count">0</span> <i class="fa fa-angle-down"></i>');
    toggle.type = 'button';
    actions.insertBefore(toggle, actions.firstChild);
    panel = el('div', 'af-panel'); panel.appendChild(grid);
  }
  bar.appendChild(actions);

  var wrap = el('div', 'af-wrap');
  form.insertBefore(wrap, form.firstChild);
  wrap.appendChild(bar); if (panel) { wrap.appendChild(panel); wrap.appendChild(chips); }
  sd.parentNode.removeChild(sd);
  form.parentNode.classList.add('af-host');
  /* the wrapper must also cover the (re-parented) form for the scoped css */
  wrap.className = 'af-wrap'; form.classList.add('af-wrap');

  function selectedOf(s) { var o = []; $(s).find('option:selected').each(function () { if (this.value !== 'all' && this.value !== '') { o.push($.trim($(this).text())); } }); return o; }
  function summary() {
    var n = 0; $(chips).empty();
    $(grid).find('select').each(function () {
      var items = selectedOf(this); if (!items.length) { return; } n++;
      var lab = $.trim($(this).closest('.af-field').find('label').first().text()).replace(/^Choose\s+/i, '');
      var txt = items.length <= 2 ? items.join(', ') : items.length + ' selected', sel = this;
      var chip = $('<span class="af-chip"></span>').text(lab + ': ').append($('<b></b>').text(txt));
      $('<button type="button" title="Remove">&times;</button>').on('click', function () { $(sel).val([]).trigger('change'); summary(); }).appendTo(chip);
      chip.appendTo(chips);
    });
    if (toggle) { $(toggle).toggleClass('has', n > 0).find('.af-count').text(n); }
  }
  if (toggle) {
    $(toggle).on('click', function () { $(this).toggleClass('open'); $(panel).toggleClass('open'); });
    $(grid).on('change', 'select', summary);
    summary();
    if (document.querySelector('.af-chip')) { /* a saved selection: show the panel so it is visible */ }
  }
});
</script>
<?php
}
