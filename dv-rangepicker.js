/* Reusable date-range picker (no dependencies).
 *   <div class="dvr" data-from-name="from_dt" data-to-name="to_dt" data-from="03-10-2026" data-to="" data-format="dmy"></div>
 * Builds a trigger button + popup (presets, calendar) and two hidden inputs holding the chosen dates
 * in data-format: "dmy" = dd-mm-yyyy (default), "ymd" = yyyy-mm-dd.  Empty is allowed (Clear). */
(function () {
  var MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
  var SHORT = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  var today = new Date(); today.setHours(0, 0, 0, 0);
  var pad = function (n) { return String(n).padStart(2, '0'); };
  var same = function (a, b) { return !!(a && b && a.getTime() === b.getTime()); };
  var fmtShow = function (d) { return d.getDate() + ' ' + SHORT[d.getMonth()] + ' ' + d.getFullYear(); };

  function parse(v) {
    var m = /^(\d{1,2})-(\d{1,2})-(\d{4})$/.exec(v || '');
    if (m) { return new Date(+m[3], +m[2] - 1, +m[1]); }
    m = /^(\d{4})-(\d{2})-(\d{2})/.exec(v || '');
    return m ? new Date(+m[1], +m[2] - 1, +m[3]) : null;
  }
  function presetRange(p) {
    var t = new Date(today), y = today.getFullYear(), m = today.getMonth();
    var back = function (n) { var d = new Date(t); d.setDate(d.getDate() - n); return d; };
    if (p === 'today') { return [t, t]; }
    if (p === 'yesterday') { return [back(1), back(1)]; }
    if (p === '7') { return [back(6), t]; }
    if (p === '30') { return [back(29), t]; }
    if (p === 'month') { return [new Date(y, m, 1), new Date(y, m + 1, 0)]; }
    if (p === 'lastmonth') { return [new Date(y, m - 1, 1), new Date(y, m, 0)]; }
    return null;
  }

  function init(root) {
    if (root.__dvr) { return; }
    root.__dvr = true;
    var ymd = root.getAttribute('data-format') === 'ymd';
    var iso = function (d) { return ymd ? d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) : pad(d.getDate()) + '-' + pad(d.getMonth() + 1) + '-' + d.getFullYear(); };
    var from = parse(root.getAttribute('data-from')), to = parse(root.getAttribute('data-to'));
    if (from && !to) { to = from; }
    var hover = null, picking = false, view = new Date((to || today).getFullYear(), (to || today).getMonth(), 1);
    var holder = root.getAttribute('data-placeholder') || 'Select dates';

    root.innerHTML =
      '<button type="button" class="dvr-trigger" aria-haspopup="dialog"><i class="fa fa-calendar"></i><span class="dvr-text"></span><i class="fa fa-times dvr-x" title="Clear dates" role="button"></i></button>' +
      '<div class="dvr-pop" role="dialog" aria-label="Choose dates">' +
        '<div class="dvr-presets">' +
          '<button type="button" data-p="today">Today</button><button type="button" data-p="yesterday">Yesterday</button>' +
          '<button type="button" data-p="7">Last 7 days</button><button type="button" data-p="30">Last 30 days</button>' +
          '<button type="button" data-p="month">This month</button><button type="button" data-p="lastmonth">Last month</button>' +
        '</div>' +
        '<div class="dvr-sel"></div><div class="dvr-cal"></div>' +
        '<div class="dvr-foot"><button type="button" class="dvr-clear">Clear</button><button type="button" class="dvr-apply">Apply</button></div>' +
      '</div>' +
      '<input type="hidden" name="' + root.getAttribute('data-from-name') + '"><input type="hidden" name="' + root.getAttribute('data-to-name') + '">';

    var trig = root.querySelector('.dvr-trigger'), txt = root.querySelector('.dvr-text'), pop = root.querySelector('.dvr-pop'),
        sel = root.querySelector('.dvr-sel'), cal = root.querySelector('.dvr-cal'), apply = root.querySelector('.dvr-apply'),
        inF = root.querySelectorAll('input[type=hidden]')[0], inT = root.querySelectorAll('input[type=hidden]')[1];

    var lastKey;
    function commit() {
      inF.value = from ? iso(from) : ''; inT.value = to ? iso(to) : '';
      var key = inF.value + '|' + inT.value;                       /* tell the page when the chosen dates really change */
      if (key !== lastKey) { var first = (lastKey === undefined); lastKey = key; if (!first) { root.dispatchEvent(new CustomEvent('dvr:change', { bubbles: true, detail: { from: inF.value, to: inT.value } })); } }
      if (from && to) { txt.textContent = same(from, to) ? fmtShow(from) : fmtShow(from).replace(/ \d{4}$/, '') + ' – ' + fmtShow(to); root.classList.add('has'); }
      else { txt.textContent = holder; root.classList.remove('has'); }
    }
    function showSel() {
      sel.innerHTML = from && to ? '<b>' + fmtShow(from) + '</b><i class="fa fa-long-arrow-right"></i><b>' + fmtShow(to) + '</b>'
                    : from ? '<b>' + fmtShow(from) + '</b><i class="fa fa-long-arrow-right"></i><em>pick end date</em>' : '<em>Pick a start date</em>';
    }
    function markPresets() {
      [].forEach.call(root.querySelectorAll('.dvr-presets button'), function (b) {
        var r = presetRange(b.getAttribute('data-p'));
        b.classList.toggle('on', !!(from && to && r && same(r[0], from) && same(r[1], to)));
      });
    }
    function render() {
      var y = view.getFullYear(), m = view.getMonth();
      var lead = (new Date(y, m, 1).getDay() + 6) % 7, days = new Date(y, m + 1, 0).getDate();
      var a = from, b = picking ? hover : to;
      if (a && b && b < a) { var t = a; a = b; b = t; }
      var h = '<div class="dvr-head"><button type="button" data-nav="-12" title="Previous year"><i class="fa fa-angle-double-left"></i></button>' +
              '<button type="button" data-nav="-1" title="Previous month"><i class="fa fa-angle-left"></i></button>' +
              '<strong>' + MONTHS[m] + ' ' + y + '</strong>' +
              '<button type="button" data-nav="1" title="Next month"><i class="fa fa-angle-right"></i></button>' +
              '<button type="button" data-nav="12" title="Next year"><i class="fa fa-angle-double-right"></i></button></div>' +
              '<div class="dvr-grid">' + ['Mo','Tu','We','Th','Fr','Sa','Su'].map(function (d) { return '<span class="dvr-dow">' + d + '</span>'; }).join('');
      for (var i = 0; i < lead; i++) { h += '<span></span>'; }
      for (var d = 1; d <= days; d++) {
        var dt = new Date(y, m, d), c = ['dvr-day'];
        if (same(dt, today)) { c.push('today'); }
        if (a && b && dt > a && dt < b) { c.push('in'); }
        if (same(dt, a)) { c.push('start'); }
        if (same(dt, b)) { c.push('end'); }
        if (a && b && !same(a, b) && (same(dt, a) || same(dt, b))) { c.push('cap'); }
        h += '<button type="button" class="' + c.join(' ') + '" data-d="' + iso(dt) + '">' + d + '</button>';
      }
      cal.innerHTML = h + '</div>';
    }
    function setRange(a, b) { from = a; to = b; picking = false; hover = null; if (b) { view = new Date(b.getFullYear(), b.getMonth(), 1); } showSel(); render(); markPresets(); }
    function open() { root.classList.add('open'); }
    function close() { root.classList.remove('open'); picking = false; hover = null; if (from && !to) { to = from; } showSel(); render(); markPresets(); commit(); }

    trig.addEventListener('click', function (e) {
      if (e.target.classList.contains('dvr-x')) { from = to = null; picking = false; commit(); showSel(); render(); markPresets(); root.classList.remove('open'); return; }
      root.classList.contains('open') ? close() : open();
    });
    pop.addEventListener('click', function (e) {
      e.stopPropagation();
      var nav = e.target.closest('[data-nav]');
      if (nav) { view = new Date(view.getFullYear(), view.getMonth() + (+nav.getAttribute('data-nav')), 1); render(); return; }
      var pr = e.target.closest('[data-p]');
      if (pr) { var r = presetRange(pr.getAttribute('data-p')); if (r) { setRange(r[0], r[1]); commit(); } return; }
      var day = e.target.closest('[data-d]');
      if (day) {
        var d = parse(day.getAttribute('data-d'));
        if (!picking) { from = d; to = null; picking = true; hover = d; showSel(); render(); markPresets(); }
        else { var x = from, y = d; if (y < x) { var t = x; x = y; y = t; } setRange(x, y); commit(); }
        return;
      }
      if (e.target.closest('.dvr-clear')) { from = to = null; picking = false; commit(); showSel(); render(); markPresets(); return; }
      if (e.target.closest('.dvr-apply')) { close(); }
    });
    cal.addEventListener('mouseover', function (e) {
      var day = e.target.closest('[data-d]');
      if (picking && day) { var d = parse(day.getAttribute('data-d')); if (!same(d, hover)) { hover = d; render(); } }
    });
    document.addEventListener('click', function (e) { if (root.classList.contains('open') && !root.contains(e.target)) { close(); } });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && root.classList.contains('open')) { close(); } });

    commit(); showSel(); render(); markPresets();
  }

  function boot() { [].forEach.call(document.querySelectorAll('.dvr[data-from-name]'), init); }
  window.dvrInit = boot;
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', boot); } else { boot(); }
})();
