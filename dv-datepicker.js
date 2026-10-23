/* Modern date-range picker for the filter popup */
function dvInitPicker() {
  const root = document.getElementById('dvCal');
  if (!root) return;
  const fromEl = document.getElementById('fromDate'), toEl = document.getElementById('toDate');
  const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
  const SHORT = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  const today = new Date(); today.setHours(0,0,0,0);
  const parse = v => { const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(v || ''); return m ? new Date(+m[1], +m[2]-1, +m[3]) : null; };
  const iso = d => d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
  const fmt = d => d.getDate() + ' ' + SHORT[d.getMonth()] + ' ' + d.getFullYear();
  const same = (a,b) => a && b && a.getTime() === b.getTime();

  let from = parse(fromEl.value), to = parse(toEl.value), hover = null, picking = false;
  if (!from || !to) { to = to || today; from = from || new Date(today.getFullYear(), today.getMonth(), 1); }
  let view = new Date(to.getFullYear(), to.getMonth(), 1);
  const out = document.getElementById('dvSelTxt'), apply = document.getElementById('dvApply'), disp = document.getElementById('dateDisplay');

  function sync() {
    fromEl.value = from ? iso(from) : ''; toEl.value = to ? iso(to) : '';
    out.innerHTML = from && to ? '<b>' + fmt(from) + '</b><i class="fa fa-long-arrow-right"></i><b>' + fmt(to) + '</b>'
                  : from ? '<b>' + fmt(from) + '</b><i class="fa fa-long-arrow-right"></i><em>pick end date</em>' : '<em>Pick a start date</em>';
    apply.disabled = !(from && to);
  }
  function shown() {
    if (from && to) disp.textContent = same(from, to) ? fmt(from) : fmt(from).replace(/ \d{4}$/, '') + ' – ' + fmt(to);
  }
  function render() {
    const y = view.getFullYear(), m = view.getMonth();
    const lead = (new Date(y, m, 1).getDay() + 6) % 7, days = new Date(y, m + 1, 0).getDate();
    let a = from, b = picking ? hover : to;
    if (a && b && b < a) { const t = a; a = b; b = t; }
    let h = '<div class="dvc-head"><button type="button" data-nav="-1" aria-label="Previous month"><i class="fa fa-angle-left"></i></button>'
          + '<strong>' + MONTHS[m] + ' ' + y + '</strong>'
          + '<button type="button" data-nav="1" aria-label="Next month"' + (new Date(y, m + 1, 1) > today ? ' disabled' : '') + '><i class="fa fa-angle-right"></i></button></div>'
          + '<div class="dvc-grid">' + ['Mo','Tu','We','Th','Fr','Sa','Su'].map(d => '<span class="dvc-dow">' + d + '</span>').join('');
    for (let i = 0; i < lead; i++) h += '<span></span>';
    for (let d = 1; d <= days; d++) {
      const dt = new Date(y, m, d), cls = ['dvc-day'];
      if (dt > today) cls.push('off');
      if (same(dt, today)) cls.push('today');
      if (a && b && dt > a && dt < b) cls.push('in');
      if (same(dt, a)) cls.push('start');
      if (same(dt, b)) cls.push('end');
      if (a && b && !same(a, b) && (same(dt, a) || same(dt, b))) cls.push('cap');
      h += '<button type="button" class="' + cls.join(' ') + '" data-d="' + iso(dt) + '"' + (dt > today ? ' disabled' : '') + '>' + d + '</button>';
    }
    root.innerHTML = h + '</div>';
  }
  function presetRange(p) {
    const t = new Date(today), y = today.getFullYear(), m = today.getMonth();
    const back = n => { const d = new Date(t); d.setDate(d.getDate() - n); return d; };
    if (p === 'today') return [t, t];
    if (p === 'yesterday') return [back(1), back(1)];
    if (p === '7') return [back(6), t];
    if (p === '30') return [back(29), t];
    if (p === 'month') return [new Date(y, m, 1), t];
    if (p === 'lastmonth') return [new Date(y, m - 1, 1), new Date(y, m, 0)];
    return null;
  }
  function markPreset() {
    document.querySelectorAll('.dv-presets button').forEach(btn => {
      const r = presetRange(btn.dataset.p);
      btn.classList.toggle('on', !!(from && to && r && same(r[0], from) && same(r[1], to)));
    });
  }
  function set(a, b) { from = a; to = b; picking = false; hover = null; view = new Date(b.getFullYear(), b.getMonth(), 1); sync(); shown(); render(); markPreset(); }

  root.onclick = function (e) {
    e.stopPropagation(); /* the calendar re-renders, so the target is detached before the outside-click check */
    const nav = e.target.closest('[data-nav]');
    if (nav) { view = new Date(view.getFullYear(), view.getMonth() + +nav.dataset.nav, 1); render(); return; }
    const day = e.target.closest('[data-d]'); if (!day) return;
    const d = parse(day.dataset.d);
    if (!picking) { from = d; to = null; picking = true; hover = d; sync(); render(); markPreset(); }
    else { let a = from, b = d; if (b < a) { const t = a; a = b; b = t; } set(a, b); }
  };
  root.onmouseover = function (e) {
    const day = e.target.closest('[data-d]');
    if (picking && day) { const d = parse(day.dataset.d); if (!same(d, hover)) { hover = d; render(); } }
  };
  document.querySelectorAll('.dv-presets button').forEach(btn => btn.onclick = () => { const r = presetRange(btn.dataset.p); if (r) set(r[0], r[1]); });
  document.getElementById('openDatepicker').onclick = e => { e.stopPropagation(); $('#dateRangeInputs').toggle(); };
  if (!window.__dvPickerDoc) {
    window.__dvPickerDoc = true;
    document.addEventListener('click', ev => { if (!ev.target.closest('.dv-filter')) $('#dateRangeInputs').hide(); });
    document.addEventListener('keydown', ev => { if (ev.key === 'Escape') $('#dateRangeInputs').hide(); });
  }
  if (apply && apply.type === 'button') apply.onclick = () => $('#dateRangeInputs').hide();
  sync(); shown(); render(); markPreset();
}
