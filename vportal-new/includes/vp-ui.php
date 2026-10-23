<?php
/* ============================================================================
 * Vendor portal: the "Current Work" look (hero, filter bar, tabs, row-card table) - self-contained copy of the main app's
 * includes/article-filter-ui.php styles, plus a few vendor-portal additions.  Used by allocated-articles.php.
 *   vp_head()  - echo in <head>, after styles()
 * ========================================================================== */
function vp_head()
{
?>
<style>
body{background:#f3f6fa !important;}
/* the old portal stylesheet draws a small vertical bar after every table cell (styles.css) - not wanted in the row cards */
table.dataTable tbody td::after,.wiptable.dataTable tbody td::after,.af-card table td::after{content:none !important;display:none !important;}
@keyframes muUp{from{opacity:0;transform:translateY(16px);}to{opacity:1;transform:none;}}
@keyframes muPop{0%{transform:scale(.92);opacity:0;}100%{transform:scale(1);opacity:1;}}
@keyframes muShine{0%{transform:translateX(-120%) skewX(-20deg);}100%{transform:translateX(320%) skewX(-20deg);}}
</style>
<style>
.af-page,.af-card{--navy:#14304a;--accent:#e8742a;--blue:#1b6ca8;--ink:#22303f;--mute:#6b7785;--line:#e4e9ef;max-width:1500px;margin:0 auto;padding:0 18px 30px;color:var(--ink);}
.af-page *{box-sizing:border-box;}

/* ---- slim title bar ---- */
.af-hero{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin:10px 0 12px;padding:14px 22px;border-radius:14px;color:#fff;
  background:linear-gradient(120deg,#14304a 0%,#1f4b73 60%,#1b6ca8 100%);box-shadow:0 6px 18px rgba(20,48,74,.2);position:relative;overflow:hidden;animation:muUp .45s ease both;}
.af-hero:before{content:"";position:absolute;top:0;bottom:0;width:26%;left:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,.13),transparent);animation:muShine 6s ease-in-out infinite;}
.af-hero>*{position:relative;}
.af-hero small{display:block;font-size:10.5px;letter-spacing:1px;text-transform:uppercase;opacity:.75;}
.af-hero h1{margin:0;font-size:21px;font-weight:700;color:#fff;line-height:1.2;}
.af-add{display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border-radius:10px;background:linear-gradient(135deg,var(--accent),#f08a45);color:#fff !important;font-weight:600;
  text-decoration:none !important;box-shadow:0 4px 12px rgba(232,116,42,.4);transition:transform .2s,box-shadow .2s;}
.af-add:hover{transform:translateY(-2px);box-shadow:0 10px 20px rgba(232,116,42,.45);}

/* ---- filter bar (one row) ---- */
.af-wrap{margin-bottom:12px;}
.af-wrap .formouter{background:#fff !important;border:1px solid var(--line);border-radius:14px;padding:12px 14px 10px;box-shadow:0 2px 10px rgba(20,48,74,.05);height:auto;}
.af-bar{display:flex;flex-wrap:wrap;align-items:flex-end;gap:10px 12px;}
.af-field{display:flex;flex-direction:column;gap:4px;min-width:150px;margin:0;}
.af-field label{margin:0 !important;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--mute);}
.af-field .form-control,.af-field .form-select{height:38px;border-radius:9px;border:1px solid #d5dde6;font-size:13.5px;padding:0 12px;box-shadow:none;transition:border-color .2s,box-shadow .2s;}
.af-field .form-control:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(27,108,168,.15);}
.af-field.search{flex:1 1 220px;max-width:340px;position:relative;}
.af-field.search .form-control{padding-left:34px;}
.af-field.search:before{content:"\f002";font-family:FontAwesome;position:absolute;left:12px;bottom:9px;color:#9aa6b2;pointer-events:none;}
.af-field.date{min-width:128px;max-width:150px;}
.af-field.check{flex-direction:row;align-items:center;gap:8px;padding:0 12px;height:38px;border:1px solid #d5dde6;border-radius:9px;background:#f9fbfd;}
.af-field.check label{text-transform:none;letter-spacing:0;font-size:13px;color:var(--ink);font-weight:600;}
.af-field.check input{margin:0 !important;height:auto;}
.af-actions{display:flex;gap:8px;align-items:center;margin-left:auto;}
.af-btn{display:inline-flex;align-items:center;gap:7px;height:38px;padding:0 16px;border-radius:9px;border:1px solid var(--line);background:#fff;color:var(--navy);font-weight:600;font-size:13.5px;cursor:pointer;
  transition:transform .15s,box-shadow .2s,background .2s,color .2s;text-decoration:none !important;width:auto;}
.af-btn:hover{transform:translateY(-2px);box-shadow:0 6px 14px rgba(20,48,74,.14);color:var(--navy);}
.af-btn.primary{background:linear-gradient(135deg,var(--accent),#f08a45);border-color:transparent;color:#fff !important;}
.af-btn.dark{background:var(--navy);border-color:var(--navy);color:#fff !important;}
.af-btn .af-count{display:none;min-width:20px;height:20px;line-height:20px;padding:0 6px;border-radius:20px;background:var(--accent);color:#fff;font-size:11px;text-align:center;}
.af-btn.has .af-count{display:inline-block;}
.af-btn .fa-angle-down{transition:transform .25s;}
.af-btn.open .fa-angle-down{transform:rotate(180deg);}
.af-wrap .resultbtn,.af-wrap .refreshbtn{width:auto;height:38px;margin:0 !important;}

/* ---- collapsible panel with the 5 multi-selects ---- */
.af-panel{display:none;margin-top:12px;padding-top:12px;border-top:1px dashed var(--line);}
.af-panel.open{display:block;animation:muUp .3s ease both;}
.af-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;}
@media(max-width:1200px){.af-grid{grid-template-columns:repeat(3,minmax(0,1fr));}}
@media(max-width:700px){.af-grid{grid-template-columns:1fr;}.af-actions{margin-left:0;width:100%;}.af-field.search,.af-field.date{max-width:none;flex:1 1 140px;}}
.af-grid .af-field{min-width:0;}
.af-grid .select2-container{width:100% !important;}
.af-grid .select2-container--default .select2-selection--multiple{min-height:38px;border-radius:9px;border:1px solid #d5dde6;max-height:92px;overflow-y:auto;}

/* ---- active-filter chips ---- */
.af-chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px;}
.af-chips:empty{display:none;}
.af-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 6px 4px 11px;border-radius:20px;background:#eaf2fa;color:var(--blue);font-size:12px;font-weight:600;animation:muPop .25s ease both;}
.af-chip b{color:var(--navy);font-weight:700;}
.af-chip button{border:0;background:rgba(27,108,168,.15);color:var(--blue);width:18px;height:18px;line-height:16px;border-radius:50%;padding:0;cursor:pointer;font-size:13px;}
.af-chip button:hover{background:var(--blue);color:#fff;}
.af-chip.clear{background:#fdeee3;color:var(--accent);cursor:pointer;padding:4px 11px;}

/* ---- table: floating "row cards" ---- */
.af-card{background:linear-gradient(180deg,#f6f9fc,#fff 120px);border:1px solid var(--line);border-radius:16px;padding:16px 18px 12px;box-shadow:0 4px 18px rgba(20,48,74,.07);animation:muUp .5s .1s ease both;}
.af-card .dataTables_wrapper{padding-top:0;}
.af-card .dataTables_filter input,.af-card .dataTables_length select{height:36px;border:1px solid #d5dde6;border-radius:10px;padding:0 12px;margin-bottom:0;background:#fff;}
.af-card .dataTables_filter input:focus{outline:0;border-color:var(--blue);box-shadow:0 0 0 3px rgba(27,108,168,.15);}
.af-card .dt-buttons .dt-button,.af-card .dt-button{border:1px solid #d5dde6 !important;border-radius:10px !important;background:#fff !important;color:var(--navy) !important;font-weight:600;padding:6px 14px;}
.af-card .dataTables_info{color:var(--mute);font-size:12.5px;}

.af-card table.dataTable,.af-card table.dataTable>thead,.af-card table.dataTable>thead>tr,.af-card table.dataTable>tbody{--bs-table-bg:transparent;--bs-table-accent-bg:transparent;--bs-table-striped-bg:transparent;background:transparent !important;}
.af-card table.dataTable>tbody>tr>*{--bs-table-bg-state:transparent;--bs-table-accent-bg:transparent;}
.af-card table.dataTable{width:100% !important;border-collapse:separate !important;border-spacing:0 8px !important;margin:0 !important;border:0 !important;}
.af-card table.dataTable thead th,.af-card table.dataTable thead th.thcolor{background:transparent !important;background-color:transparent !important;border:0 !important;color:#7b8a9a !important;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:1px;
  padding:6px 14px 2px;white-space:nowrap;box-shadow:none !important;}
.af-card table.dataTable thead th.sorting_asc,.af-card table.dataTable thead th.sorting_desc{color:var(--accent) !important;}
/* sort arrows: the transparent header background above also removes DataTables' own arrow images, so they are drawn here */
.af-card table.dataTable thead th.sorting,.af-card table.dataTable thead th.sorting_asc,.af-card table.dataTable thead th.sorting_desc{position:relative;padding-right:28px !important;cursor:pointer;background-image:none !important;}
.af-card table.dataTable thead th.sorting:after,.af-card table.dataTable thead th.sorting_asc:after,.af-card table.dataTable thead th.sorting_desc:after{font-family:FontAwesome;font-weight:normal;font-size:12px;line-height:14px;position:absolute;right:9px;top:50%;margin-top:-5px;transition:color .15s,transform .15s;}
.af-card table.dataTable thead th.sorting:after{content:"\f0dc";color:#b3bfcc;}
.af-card table.dataTable thead th.sorting_asc:after{content:"\f0de";color:var(--accent,#e8742a);margin-top:-3px;}
.af-card table.dataTable thead th.sorting_desc:after{content:"\f0dd";color:var(--accent,#e8742a);margin-top:-8px;}
.af-card table.dataTable thead th.sorting:hover:after{color:#1b6ca8;transform:scale(1.15);}
.af-card table.dataTable thead th.sorting_disabled{cursor:default;padding-right:14px !important;} .af-card table.dataTable thead th.sorting_disabled:after{display:none;}
.af-card table.dataTable tbody tr{box-shadow:0 1px 3px rgba(20,48,74,.07);border-radius:12px;transition:box-shadow .2s,transform .2s;}
.af-card table.dataTable tbody td{background:#fff !important;border:0 !important;border-top:1px solid #edf1f5 !important;border-bottom:1px solid #edf1f5 !important;vertical-align:middle;padding:12px 14px;color:var(--ink);white-space:nowrap;}
.af-card table.dataTable tbody td:first-child{border-left:1px solid #edf1f5 !important;border-radius:12px 0 0 12px;box-shadow:inset 4px 0 0 var(--stripe,transparent);}
.af-card table.dataTable tbody td:last-child{border-right:1px solid #edf1f5 !important;border-radius:0 12px 12px 0;}
.af-card table.dataTable tbody tr:hover{box-shadow:0 8px 22px rgba(20,48,74,.14);transform:translateY(-1px);}
.af-card table.dataTable tbody tr:hover td{background:#f8fbff !important;}
.af-card table.dataTable tbody tr.af-row-high{--stripe:#d64545;} .af-card table.dataTable tbody tr.af-row-high td{background:#fff5f5 !important;}
.af-card table.dataTable tbody tr.af-row-overdue{--stripe:#e8742a;} .af-card table.dataTable tbody tr.af-row-overdue td{background:#fff8f1 !important;}
.af-card table.dataTable tbody tr.af-row-today{--stripe:#1b6ca8;} .af-card table.dataTable tbody tr.af-row-today td{background:#f3f8ff !important;}
.af-card table.dataTable tbody tr.af-row-flag{--stripe:#d64545;}
.af-card table.dataTable tbody tr.af-row-pri{--stripe:#1b6ca8;}
.af-card table.dataTable tbody tr.af-row-warn{--stripe:#d98a06;}
.af-card table.dataTable tbody tr:not(.odd):not(.even){}
.af-card table.dataTable tbody tr.af-in{animation:muUp .35s ease both;animation-delay:calc(var(--i,0) * 22ms);}
.af-card td.dataTables_empty{text-align:center;color:var(--mute);padding:34px !important;border-radius:12px !important;}

/* cell pieces built by the script below */
.af-art{font-weight:700;color:var(--navy);letter-spacing:.2px;}
.af-art small,.af-art span.label{font-weight:600;}
.af-person{display:inline-flex;align-items:center;gap:9px;font-weight:600;}
.af-av{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;color:#fff;font-size:10.5px;font-weight:700;background:linear-gradient(135deg,var(--blue),#14304a);flex:0 0 auto;}
.af-chip-j{display:inline-block;padding:3px 10px;border-radius:7px;background:#eef3f8;color:var(--navy);font-weight:700;font-size:12px;letter-spacing:.3px;}
.af-stage{display:inline-block;padding:3px 10px;border-radius:20px;font-weight:700;font-size:11.5px;letter-spacing:.3px;background:color-mix(in srgb,var(--c,#6b7785) 13%,#fff);color:var(--c,#6b7785);border:1px solid color-mix(in srgb,var(--c,#6b7785) 25%,#fff);}
.af-date{display:inline-flex;flex-direction:column;line-height:1.25;}
.af-date b{font-weight:600;color:var(--ink);font-size:13px;} .af-date small{color:var(--mute);font-size:11px;}
.af-due{display:inline-flex;flex-direction:column;align-items:flex-start;gap:3px;}
.af-rel{display:inline-block;padding:1px 8px;border-radius:20px;font-size:10.5px;font-weight:700;background:#eef1f5;color:var(--mute);}
.af-rel.late{background:#fde8e8;color:#c0392b;} .af-rel.today{background:#e1effa;color:#1b6ca8;} .af-rel.soon{background:#fdf1d9;color:#b87800;}
.af-status{display:inline-flex;align-items:center;gap:7px;white-space:nowrap;padding:4px 11px 4px 9px;border-radius:20px;font-weight:700;font-size:12px;background:color-mix(in srgb,var(--c,#6b7785) 13%,#fff);color:var(--c,#6b7785);}
.af-status:before{content:"";width:7px;height:7px;border-radius:50%;background:var(--c,#6b7785);}
.af-stat-wrap{display:inline-flex;flex-wrap:wrap;align-items:center;gap:5px;max-width:260px;white-space:normal;}
.af-sub{display:inline-block;padding:2px 9px;border-radius:20px;border:1px solid #dde4ec;background:#f6f9fc;color:var(--ink);font-size:11.5px;font-weight:600;}
.af-num{font-variant-numeric:tabular-nums;font-weight:700;color:var(--navy);display:inline-block;min-width:34px;text-align:right;}

/* action button + menu */
.af-card td:first-child .dropdown>div[data-bs-toggle]{display:inline-flex !important;align-items:center;gap:6px;border:1px solid #d5dde6 !important;background:#fff !important;color:var(--navy) !important;padding:6px 12px !important;border-radius:9px !important;font-weight:600;font-size:12.5px;cursor:pointer;transition:all .2s;}
.af-card td:first-child .dropdown>div[data-bs-toggle]:after{content:"\f107";font-family:FontAwesome;margin-left:2px;}
.af-card td:first-child .dropdown>div[data-bs-toggle]:hover,.af-card td:first-child .dropdown>div[data-bs-toggle][aria-expanded="true"]{background:var(--navy) !important;border-color:var(--navy) !important;color:#fff !important;}
.af-card .dropdown-menu,.dropdown-menu.af-float{border:1px solid #e4e9ef;border-radius:12px;box-shadow:0 14px 34px rgba(20,48,74,.22);padding:6px;animation:muPop .15s ease both;}
.af-card .dropdown-item,.dropdown-menu.af-float .dropdown-item{display:block;width:100%;border-radius:8px;padding:8px 12px;font-weight:500;font-size:13.5px;color:#22303f;text-align:left;border:0;background:transparent;}
.af-card .dropdown-item:hover,.dropdown-menu.af-float .dropdown-item:hover{background:#eef5fb;color:#14304a;}
.dropdown-menu.af-float{z-index:5000 !important;min-width:200px;}

/* footer: info + pagination */
.af-card .dataTables_paginate{padding-top:6px;}
.af-card .dataTables_paginate .paginate_button{border:1px solid var(--line) !important;border-radius:9px !important;background:#fff !important;margin:0 2px;padding:5px 12px !important;color:var(--navy) !important;font-weight:600;}
.af-card .dataTables_paginate .paginate_button:hover{background:#eef5fb !important;}
.af-card .dataTables_paginate .paginate_button.current,.af-card .dataTables_paginate .paginate_button.current:hover{background:linear-gradient(135deg,var(--accent),#f08a45) !important;border-color:transparent !important;color:#fff !important;}
.af-card .dataTables_paginate .paginate_button.disabled{opacity:.45;}
.af-card .dataTables_processing{border-radius:14px;box-shadow:0 10px 28px rgba(20,48,74,.2);border:1px solid var(--line);}

/* ---- DataTables controls: export / show / search / pager - one look, overriding the theme's square boxes ---- */
.af-card .dataTables_length,.af-card .dataTables_filter,.af-card .dt-buttons{margin:0 0 10px;}
.af-card .dataTables_length label,.af-card .dataTables_filter label{display:inline-flex;align-items:center;gap:8px;margin:0;color:var(--mute);font-weight:600;font-size:13px;}
.af-card .dataTables_filter input[type=search],.af-card .dataTables_filter input,.af-card .dataTables_length select{
  height:38px !important;border:1px solid #d5dde6 !important;border-radius:10px !important;background-color:#fff !important;box-shadow:none !important;
  color:var(--ink);font-size:13.5px;font-weight:500;margin:0 !important;outline:0;transition:border-color .2s,box-shadow .2s;}
.af-card .dataTables_filter input[type=search],.af-card .dataTables_filter input{
  width:240px;padding:0 12px 0 36px !important;
  background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' stroke='%239aa6b2' stroke-width='2'><circle cx='7' cy='7' r='5'/><path d='M11 11l4 4'/></svg>") !important;
  background-repeat:no-repeat !important;background-position:12px center !important;}
.af-card .dataTables_length select{padding:0 32px 0 12px !important;min-width:74px;cursor:pointer;-webkit-appearance:none;-moz-appearance:none;appearance:none;
  background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%236b7785' stroke-width='2'><path d='M2 4l4 4 4-4'/></svg>") !important;
  background-repeat:no-repeat !important;background-position:right 11px center !important;}
.af-card .dataTables_filter input:focus,.af-card .dataTables_length select:focus{border-color:var(--blue) !important;box-shadow:0 0 0 3px rgba(27,108,168,.15) !important;}
.af-card .dt-buttons .dt-button,.af-card button.dt-button{height:38px;display:inline-flex;align-items:center;gap:6px;font-size:13.5px !important;padding:0 16px !important;margin:0 !important;box-shadow:none !important;
  transition:transform .15s,box-shadow .2s,background .2s !important;}
.af-card .dt-buttons .dt-button:hover{background:#eef5fb !important;transform:translateY(-1px);box-shadow:0 6px 14px rgba(20,48,74,.12) !important;}
/* pager: loose pill buttons, no outer box */
.af-card .dataTables_paginate{border:0 !important;box-shadow:none !important;background:transparent !important;display:flex !important;align-items:center;justify-content:flex-end;gap:6px;float:right;height:auto !important;padding:4px 0 0 !important;margin:0;}
.af-card .dataTables_paginate>span{display:inline-flex;gap:6px;}
.af-card .dataTables_paginate .paginate_button{display:inline-flex !important;float:none !important;align-items:center;justify-content:center;min-width:38px;height:38px;margin:0 !important;padding:0 14px !important;border:1px solid #d5dde6 !important;border-radius:10px !important;
  background:#fff !important;color:var(--navy) !important;font-weight:600;font-size:13.5px;line-height:1;cursor:pointer;box-shadow:none !important;transition:transform .15s,background .2s,box-shadow .2s;}
.af-card .dataTables_paginate .paginate_button:hover:not(.disabled):not(.current){background:#eef5fb !important;transform:translateY(-1px);box-shadow:0 6px 14px rgba(20,48,74,.12) !important;}
.af-card .dataTables_paginate .paginate_button.current,.af-card .dataTables_paginate .paginate_button.current:hover{background:linear-gradient(135deg,var(--accent),#f08a45) !important;border-color:transparent !important;color:#fff !important;box-shadow:0 6px 14px rgba(232,116,42,.35) !important;}
.af-card .dataTables_paginate .paginate_button.disabled,.af-card .dataTables_paginate .paginate_button.disabled:hover{opacity:.5;cursor:default;background:#f6f9fc !important;color:#9aa6b2 !important;transform:none;box-shadow:none !important;}
.af-card .dataTables_info{padding-top:14px;font-size:13px;color:var(--mute);}
.af-card .dataTables_wrapper .row,.af-card .dataTables_wrapper>div{align-items:center;}

/* ---- comments / instructions pop-up + the Instruction / Start buttons in the table ---- */
#exampleModal .modal-dialog{max-width:860px;}
#exampleModal .modal-content{border:0;border-radius:18px;overflow:hidden;box-shadow:0 30px 70px rgba(20,48,74,.35);}
#exampleModal.fade .modal-dialog{transform:translateY(24px) scale(.97);transition:transform .3s cubic-bezier(.2,.7,.2,1),opacity .25s;}
#exampleModal.show .modal-dialog{transform:none;}
#exampleModal .modal-header{padding:14px 22px;border:0;color:#fff;background:linear-gradient(120deg,#e8742a,#f08a45);}
#exampleModal .modal-title{display:flex;align-items:center;gap:10px;font-size:17px;font-weight:700;color:#fff;}
#exampleModal .modal-title:before{content:"\f0e6";font-family:FontAwesome;font-size:18px;}
#exampleModal .btn-close{filter:invert(1) grayscale(1) brightness(2);opacity:.9;border-radius:50%;padding:8px;transition:transform .2s,background .2s;}
#exampleModal .btn-close:hover{transform:rotate(90deg);background-color:rgba(255,255,255,.2);opacity:1;}
#exampleModal .modal-body{padding:20px 24px;background:#f3f6fa;max-height:68vh;overflow-y:auto;}
#exampleModal .modal-footer{border:0;padding:12px 22px;background:#fff;}
#exampleModal .modal-footer .btn{border-radius:10px;font-weight:600;padding:8px 22px;background:#14304a;border-color:#14304a;color:#fff;transition:transform .15s,box-shadow .2s;}
#exampleModal .modal-footer .btn:hover{transform:translateY(-2px);box-shadow:0 8px 16px rgba(20,48,74,.25);}
#ppopup{margin:0;}
.modal-backdrop.show{backdrop-filter:blur(3px);background:#14304a;opacity:.45;}

.af-card td a[onclick*="timepopup"]{display:inline-flex !important;align-items:center;gap:6px;padding:6px 14px !important;border:1px solid #f3c7a8 !important;border-radius:20px !important;background:#fff6ee !important;
  color:var(--accent) !important;font-weight:700;font-size:12px !important;text-decoration:none !important;transition:all .2s;}
.af-card td a[onclick*="timepopup"]:before{content:"\f075";font-family:FontAwesome;font-size:12px;}
.af-card td a[onclick*="timepopup"]:hover{background:var(--accent) !important;border-color:var(--accent) !important;color:#fff !important;transform:translateY(-1px);box-shadow:0 6px 14px rgba(232,116,42,.35);}
.af-card a[id^="startBtn_"],.af-card .btn.btn-xs.btn-primary{display:inline-flex !important;align-items:center;gap:7px;padding:7px 16px !important;border:0 !important;border-radius:20px !important;
  background:linear-gradient(135deg,#14304a,#1f4b73) !important;color:#fff !important;font-weight:700;font-size:12.5px !important;cursor:pointer;box-shadow:0 3px 8px rgba(20,48,74,.25);transition:transform .15s,box-shadow .2s,filter .2s;}
.af-card a[id^="startBtn_"]:hover{transform:translateY(-2px);box-shadow:0 10px 20px rgba(20,48,74,.35);filter:brightness(1.12);}
.af-card .label{display:inline-block !important;width:auto !important;padding:3px 10px !important;border-radius:20px !important;font-size:11.5px !important;font-weight:700 !important;line-height:1.4 !important;}
.af-card .label-success{background:#e3f6ec !important;color:#1e9e61 !important;} .af-card .label-default{background:#eef1f5 !important;color:#6b7785 !important;}
.af-card .label-info{background:#e1effa !important;color:#1b6ca8 !important;} .af-card .label-warning{background:#fdf1d9 !important;color:#b87800 !important;}

.af-card table.dataTable tbody tr.af-open{position:relative;z-index:90;}
/* ---- row action buttons (Upload / Move / Undo / Hold for Weekend ...): soft tinted pills that fill on hover ---- */
.af-card td .btn.btn-xs.btn-purple,.af-card td .btn.btn-xs.btn-warning,.af-card td .btn.btn-xs.btn-danger,.af-card td .btn.btn-xs.btn-success,.af-card td .btn.btn-xs.btn-info,.af-card td .btn.btn-xs.btn-pink{
  --b:#1b6ca8;--bg:#e8f1fb;--bd:#c5daef;
  display:inline-flex !important;align-items:center;gap:6px;width:auto !important;margin:0 4px 0 0;padding:6px 14px !important;border-radius:20px !important;border:1px solid var(--bd) !important;
  background:var(--bg) !important;color:var(--b) !important;font-size:12px !important;font-weight:700;line-height:1.2;text-decoration:none !important;white-space:nowrap;cursor:pointer;box-shadow:none !important;
  transition:background .2s,color .2s,border-color .2s,transform .15s,box-shadow .2s;}
.af-card td .btn.btn-xs.btn-warning{--b:#b87800;--bg:#fdf1d9;--bd:#f1d9a6;}
.af-card td .btn.btn-xs.btn-danger{--b:#c0392b;--bg:#fdeaea;--bd:#f3c4c4;}
.af-card td .btn.btn-xs.btn-success{--b:#1e9e61;--bg:#e3f6ec;--bd:#bfe8d2;}
.af-card td .btn.btn-xs.btn-info{--b:#0e9aa7;--bg:#e2f6f8;--bd:#b9e6ea;}
.af-card td .btn.btn-xs.btn-pink{--b:#c2185b;--bg:#fde8f1;--bd:#f6c3d9;}
.af-card td .btn.btn-xs.btn-purple:hover,.af-card td .btn.btn-xs.btn-warning:hover,.af-card td .btn.btn-xs.btn-danger:hover,.af-card td .btn.btn-xs.btn-success:hover,.af-card td .btn.btn-xs.btn-info:hover,.af-card td .btn.btn-xs.btn-pink:hover{
  background:var(--b) !important;border-color:var(--b) !important;color:#fff !important;transform:translateY(-1px);box-shadow:0 6px 14px color-mix(in srgb,var(--b) 35%,transparent) !important;}
.af-card td .btn.btn-xs i{font-size:13px;}
.af-card td .action-buttons{display:flex;flex-wrap:nowrap;gap:2px;align-items:center;}
.af-card td .action-buttons .btn{min-height:30px;justify-content:center;}

/* ---- priority tabs ---- */
.af-tabs{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin:0 0 10px;}
.af-tab{--c:#1b6ca8;display:inline-flex;align-items:center;gap:8px;height:38px;padding:0 14px;border-radius:10px;border:1px solid var(--line);background:#fff;color:var(--ink);font-weight:600;font-size:13.5px;
  cursor:pointer;transition:transform .15s,box-shadow .2s,background .2s,color .2s,border-color .2s;box-shadow:0 1px 4px rgba(20,48,74,.05);}
.af-tab i{color:var(--c);transition:transform .25s;}
.af-tab b{min-width:24px;height:22px;line-height:22px;padding:0 7px;border-radius:20px;background:color-mix(in srgb,var(--c) 14%,#fff);color:var(--c);font-size:12px;text-align:center;font-variant-numeric:tabular-nums;}
.af-tab b.zero{background:#eef1f5;color:#9aa6b2;}
.af-tab:hover{transform:translateY(-2px);box-shadow:0 8px 16px rgba(20,48,74,.12);}
.af-tab:hover i{transform:scale(1.2);}
.af-tab.active{background:var(--c);border-color:var(--c);color:#fff;box-shadow:0 8px 18px color-mix(in srgb,var(--c) 40%,transparent);}
.af-tab.active i{color:#fff;} .af-tab.active b{background:rgba(255,255,255,.25);color:#fff;}
.af-tab.high{--c:#d64545;} .af-tab.overdue{--c:#e8742a;} .af-tab.today{--c:#1b6ca8;} .af-tab.others{--c:#6b7785;} .af-tab.all{--c:#14304a;}
.af-tab.d1{--c:#d98a06;} .af-tab.d2{--c:#2f9e8f;} .af-tab.d3{--c:#3a7bd5;} .af-tab.d4{--c:#7a5fc4;} .af-tab.query{--c:#c2399a;}
.af-tab:first-child{--c:#14304a;}
.af-tab-hint{margin-left:auto;font-size:12px;color:var(--mute);}
@media(max-width:900px){.af-tab-hint{display:none;}}

@media print{.af-hero,.af-wrap{display:none !important;}}
@media(prefers-reduced-motion:reduce){.af-page *,.af-hero:before{animation:none !important;transition:none !important;}}
/* ---- page messages (Successfully added ...): slim banner with an icon and a working close button.
   Scoped to the page body, so the Bootbox pop-ups keep their own look. ---- */
.wholeouter div.alert{--ac:#1e9e61;--acbg:#e8f7ef;position:relative;display:flex;align-items:center;gap:12px;width:fit-content;min-width:260px;max-width:min(640px,calc(100% - 36px));margin:14px auto 4px;padding:11px 46px 11px 14px;border:1px solid color-mix(in srgb,var(--ac) 28%,#fff);border-left:4px solid var(--ac);border-radius:12px;background:var(--acbg);color:#1f3a2c;font-size:13.5px;font-weight:600;line-height:1.4;box-shadow:0 8px 22px rgba(20,48,74,.12);animation:muPop .3s ease both;}
.wholeouter div.alert:before{content:"\f058";font-family:FontAwesome;flex:0 0 auto;display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;background:var(--ac);color:#fff;font-size:14px;line-height:1;}
.wholeouter div.alert.alert-danger{--ac:#d64545;--acbg:#fdeeee;color:#5a1f1f;}
.wholeouter div.alert.alert-danger:before{content:"\f057";}
.wholeouter div.alert.alert-warning{--ac:#d98a06;--acbg:#fff6e5;color:#5a3d06;}
.wholeouter div.alert.alert-warning:before{content:"\f071";}
.wholeouter div.alert.alert-info{--ac:#1b6ca8;--acbg:#e8f2fb;color:#17405f;}
.wholeouter div.alert.alert-info:before{content:"\f05a";}
.wholeouter div.alert .close{position:absolute;top:50%;right:10px;transform:translateY(-50%);width:26px;height:26px;padding:0;border:0;border-radius:50%;background:transparent;color:var(--ac);font-size:20px;line-height:1;opacity:.75;cursor:pointer;transition:background .2s,opacity .2s;}
.wholeouter div.alert .close:hover{background:color-mix(in srgb,var(--ac) 16%,transparent);opacity:1;}
.wholeouter div.alert.af-out{opacity:0;transform:translateY(-6px);transition:opacity .35s,transform .35s;}
.wholeouter div.alert.hide{display:none;}

/* ---- Bootbox pop-ups (Work started, "already in progress", confirmations ...): same card look as the Start Work alerts ---- */
.bootbox.modal{--ac:#1b6ca8;--acbg:#e6f1fa;--acring:rgba(27,108,168,.18);}
.bootbox.modal.bb-ok{--ac:#1e9e61;--acbg:#e5f6ed;--acring:rgba(30,158,97,.18);}
.bootbox.modal:has(.alert-danger),.bootbox.modal.bb-bad{--ac:#d64545;--acbg:#fdeaea;--acring:rgba(214,69,69,.18);}
.bootbox.modal:has(.alert-warning),.bootbox.modal.bb-warn{--ac:#e8932a;--acbg:#fff3e0;--acring:rgba(232,147,42,.2);}
.bootbox.modal,.bootbox.modal *{font-family:'Open Sans','Segoe UI',Arial,sans-serif;}
.bootbox.modal .fa{font-family:FontAwesome;}
.bootbox.modal .modal-dialog{max-width:440px;margin:14vh auto 0;transform:translateY(26px) scale(.93);opacity:0;transition:transform .45s cubic-bezier(.2,1.2,.3,1),opacity .25s ease;}
.bootbox.modal.show .modal-dialog,.bootbox.modal.in .modal-dialog{transform:none;opacity:1;}
.bootbox.modal .modal-content{border:0;border-radius:18px;overflow:hidden;box-shadow:0 28px 70px rgba(15,35,60,.38),0 2px 6px rgba(15,35,60,.12);}
.bootbox.modal .modal-content:before{content:"";display:block;height:5px;background:linear-gradient(90deg,var(--ac),color-mix(in srgb,var(--ac) 55%,#fff));}
.bootbox.modal .modal-body{position:relative;padding:26px 28px 6px;text-align:center;}
.bootbox.modal .bootbox-close-button{position:absolute;top:8px;right:14px;z-index:2;float:none;border:0;background:none;color:#9aa8b8;font-size:24px;line-height:1;opacity:1;cursor:pointer;transition:color .15s,transform .2s;}
.bootbox.modal .bootbox-close-button:hover{color:#d64545;transform:rotate(90deg);}
.bootbox.modal .bootbox-body{font-size:14.5px;line-height:1.55;color:#51606f;}
/* the icon badge above the text */
.bootbox.modal .bootbox-body:before{content:"i";display:flex;align-items:center;justify-content:center;width:62px;height:62px;margin:0 auto 14px;border-radius:50%;background:var(--acbg);color:var(--ac);font:700 30px/1 'Open Sans',Arial,sans-serif;font-style:italic;
  animation:bbIcon .55s cubic-bezier(.2,1.4,.3,1) .1s backwards,bbRing 2.2s ease-out .7s infinite;}
.bootbox.modal.bb-ok .bootbox-body:before{content:"\2713";font-style:normal;}
.bootbox.modal:has(.alert-danger) .bootbox-body:before,.bootbox.modal.bb-bad .bootbox-body:before,.bootbox.modal:has(.alert-warning) .bootbox-body:before,.bootbox.modal.bb-warn .bootbox-body:before{content:"!";font-style:normal;}
.bootbox.modal .bb-title{display:block;margin:0 0 6px;font-size:21px;font-weight:700;color:#1c3550;line-height:1.3;animation:bbUp .4s ease .15s backwards;}
.bootbox.modal .bb-sub{display:block;font-size:13.5px;color:#6b7785;animation:bbUp .4s ease .22s backwards;}
.bootbox.modal .bootbox-body strong{font-size:20px;font-weight:700;color:#1c3550;}
.bootbox.modal .bootbox-body .bb-title strong{font-size:inherit;}
.bootbox.modal .bootbox-body .alert{margin:0;padding:0;border:0;background:none;color:#2f4357;font-size:15px;font-weight:600;line-height:1.5;animation:bbUp .4s ease .18s backwards;}
.bootbox.modal .bootbox-body br:first-child{display:none;}
.bootbox.modal .modal-footer{border:0;justify-content:center;gap:10px;padding:16px 28px 24px;animation:bbUp .4s ease .3s backwards;}
.bootbox.modal .modal-footer .btn{min-width:110px;border-radius:10px;padding:9px 24px;font-size:14px;font-weight:600;border:1px solid #cfd8e3;background:#fff;color:#4b5b6b;box-shadow:none;transition:transform .15s,box-shadow .15s,filter .15s,background .15s;margin:0;}
.bootbox.modal .modal-footer .btn:hover{background:#f1f5f9;transform:translateY(-1px);}
.bootbox.modal .modal-footer .btn-primary,.bootbox.modal .modal-footer .bootbox-accept,.bootbox.modal .modal-footer .btn-danger{border:0;color:#fff;background:linear-gradient(120deg,var(--ac),color-mix(in srgb,var(--ac) 70%,#14304a));box-shadow:0 5px 14px color-mix(in srgb,var(--ac) 38%,transparent);}
.bootbox.modal .modal-footer .btn-primary:hover,.bootbox.modal .modal-footer .bootbox-accept:hover,.bootbox.modal .modal-footer .btn-danger:hover{filter:brightness(1.08);background:linear-gradient(120deg,var(--ac),color-mix(in srgb,var(--ac) 70%,#14304a));transform:translateY(-1px);}
.bootbox.modal .modal-footer .btn:active{transform:scale(.97);}
body:has(.bootbox.show) .modal-backdrop.show{background:#0f2034;opacity:.5;backdrop-filter:blur(3px);-webkit-backdrop-filter:blur(3px);}
/* "work already started by ..." card inside the pop-up */
.bootbox.modal .bb-sub{margin:2px auto 0;max-width:340px;}
.bootbox.modal .bb-who{display:flex;align-items:center;gap:12px;max-width:340px;margin:16px auto 0;padding:10px 14px;background:#f6f9fc;border:1px solid #e3eaf2;border-left:4px solid var(--ac);border-radius:12px;text-align:left;animation:bbUp .4s ease .26s backwards;}
.bootbox.modal .bb-av{flex:0 0 auto;width:40px;height:40px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#1b6ca8,#14304a);color:#fff;font-weight:700;font-size:14px;box-shadow:0 3px 8px rgba(20,48,74,.3);}
.bootbox.modal .bb-wt{flex:1;min-width:0;line-height:1.3;} .bootbox.modal .bb-wt small{display:block;font-size:10.5px;letter-spacing:.6px;text-transform:uppercase;color:#7b8a9a;} .bootbox.modal .bb-wt b{display:block;font-size:15px;color:#14304a;word-break:break-word;}
.bootbox.modal .bb-chip{flex:0 0 auto;display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:999px;background:#fff3e0;color:#b86a00;font-size:11.5px;font-weight:700;white-space:nowrap;} .bootbox.modal .bb-chip:before{content:"";width:7px;height:7px;border-radius:50%;background:#e8932a;animation:bbBlink 1.4s ease-in-out infinite;}
@keyframes bbBlink{0%,100%{opacity:1;}50%{opacity:.3;}}
@keyframes bbIcon{0%{transform:scale(0) rotate(-40deg);opacity:0;}100%{transform:none;opacity:1;}}
@keyframes bbRing{0%{box-shadow:0 0 0 0 var(--acring);}70%{box-shadow:0 0 0 14px rgba(255,255,255,0);}100%{box-shadow:0 0 0 0 rgba(255,255,255,0);}}
@keyframes bbUp{0%{opacity:0;transform:translateY(8px);}100%{opacity:1;transform:none;}}
</style>
<?php
}
