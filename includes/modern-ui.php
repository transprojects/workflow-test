<?php
/* ============================================================================
 * Shared "modern" look for the report / dashboard pages.
 *
 *   mu_head()  - echo inside <head>, after styles()
 *   mu_foot()  - echo just before </body>, after scripts()
 *
 * Wrap the page content in <div class="mu-root"> ... </div>.
 * Markup hooks (all optional):
 *   .mu-reveal            fades / slides in when scrolled into view (auto-staggered)
 *   [data-count="123"]    number counts up from 0 (add data-suffix / data-decimals)
 *   .mu-btn               ripple on click
 *   input[data-mu-filter="#tableId"]  live-filters the rows of that table
 * Everything honours prefers-reduced-motion.
 * ========================================================================== */

function mu_head()
{
?>
<style>
.mu-root{--navy:#14304a;--navy2:#1f4b73;--accent:#e8742a;--blue:#1b6ca8;--ink:#22303f;--mute:#6b7785;
  --line:#e4e9ef;--bg:#f5f7fa;--ok:#1e9e61;--warn:#d98a06;--bad:#d64545;--info:#2a7fc1;
  color:var(--ink);font-size:13.5px;max-width:1360px;margin:0 auto;padding:0 18px 30px;}
.mu-root *{box-sizing:border-box;}
.mu-root h1,.mu-root h2,.mu-root h3,.mu-root p{margin:0;}

/* ---- motion ---- */
@keyframes muUp{from{opacity:0;transform:translateY(16px);}to{opacity:1;transform:none;}}
@keyframes muPop{0%{transform:scale(.92);opacity:0;}100%{transform:scale(1);opacity:1;}}
@keyframes muShine{0%{transform:translateX(-120%) skewX(-20deg);}100%{transform:translateX(320%) skewX(-20deg);}}
@keyframes muSpin{to{transform:rotate(360deg);}}
@keyframes muPulse{0%{box-shadow:0 0 0 0 rgba(232,116,42,.45);}100%{box-shadow:0 0 0 12px rgba(232,116,42,0);}}
@keyframes muGrow{from{transform:scaleX(0);}to{transform:scaleX(1);}}
@keyframes muShimmer{from{background-position:200% 0;}to{background-position:-200% 0;}}
.mu-reveal{opacity:0;transform:translateY(16px);transition:opacity .55s ease,transform .55s cubic-bezier(.2,.7,.2,1);}
.mu-reveal.in{opacity:1;transform:none;}

/* ---- page progress ---- */
#mu-progress{position:fixed;top:0;left:0;height:3px;width:0;z-index:3000;border-radius:0 3px 3px 0;
  background:linear-gradient(90deg,var(--accent,#e8742a),#f5a35c 60%,#1b6ca8);box-shadow:0 0 10px rgba(232,116,42,.6);
  transition:width .3s ease,opacity .4s ease;}

/* ---- hero ---- */
.mu-hero{position:relative;overflow:hidden;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;
  gap:14px 24px;padding:22px 26px;margin:8px 0 18px;border-radius:14px;color:#fff;
  background:linear-gradient(120deg,#14304a 0%,#1f4b73 60%,#1b6ca8 100%);box-shadow:0 8px 24px rgba(20,48,74,.22);
  animation:muUp .5s ease both;}
.mu-hero:before{content:"";position:absolute;top:0;bottom:0;width:30%;left:0;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,.14),transparent);animation:muShine 5s ease-in-out infinite;}
.mu-hero:after{content:"";position:absolute;right:-60px;top:-60px;width:200px;height:200px;border-radius:50%;
  background:radial-gradient(circle,rgba(232,116,42,.55),transparent 70%);}
.mu-hero>*{position:relative;z-index:1;}
.mu-hero small{display:block;font-size:11px;letter-spacing:1px;text-transform:uppercase;opacity:.75;}
.mu-hero h1{font-size:24px;font-weight:700;line-height:1.25;margin-top:2px;color:#fff;}
.mu-hero p{opacity:.85;margin-top:3px;}
.mu-hero .mu-hero-right{display:flex;flex-wrap:wrap;gap:10px;align-items:center;}
.mu-chip{padding:8px 14px;border-radius:10px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.22);
  backdrop-filter:blur(4px);}
.mu-chip small{font-size:10.5px;}
.mu-chip b{display:block;font-size:14px;font-weight:600;}

/* ---- buttons ---- */
.mu-btn{position:relative;overflow:hidden;display:inline-flex;align-items:center;gap:7px;border:1px solid var(--line);
  background:#fff;color:var(--navy);border-radius:9px;padding:9px 18px;font-size:13px;font-weight:600;cursor:pointer;
  text-decoration:none;transition:transform .15s,box-shadow .2s,background .2s,color .2s;}
.mu-btn:hover{transform:translateY(-2px);box-shadow:0 6px 14px rgba(20,48,74,.15);color:var(--navy);text-decoration:none;}
.mu-btn:active{transform:translateY(0);}
.mu-btn.primary{background:linear-gradient(135deg,var(--accent),#f08a45);border-color:transparent;color:#fff;}
.mu-btn.primary:hover{color:#fff;box-shadow:0 8px 18px rgba(232,116,42,.4);}
.mu-ripple{position:absolute;border-radius:50%;background:rgba(255,255,255,.55);transform:scale(0);
  animation:muRipple .6s ease-out forwards;pointer-events:none;}
@keyframes muRipple{to{transform:scale(4);opacity:0;}}

/* ---- cards / panels ---- */
.mu-panel{background:#fff;border:1px solid var(--line);border-radius:14px;padding:18px 20px;margin-bottom:16px;
  box-shadow:0 2px 10px rgba(20,48,74,.05);transition:box-shadow .25s,transform .25s;}
.mu-panel:hover{box-shadow:0 8px 22px rgba(20,48,74,.09);}
.mu-panel>h3{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700;text-transform:uppercase;
  letter-spacing:.7px;color:var(--navy);padding-bottom:10px;margin-bottom:12px;border-bottom:1px solid var(--line);}
.mu-panel>h3>i{color:var(--accent);}
.mu-panel>h3 .mu-right{margin-left:auto;font-weight:600;text-transform:none;letter-spacing:0;color:var(--mute);}

/* ---- stat cards ---- */
.mu-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;margin-bottom:18px;}
.mu-stat{position:relative;overflow:hidden;display:flex;align-items:center;gap:14px;padding:16px 18px;background:#fff;
  border:1px solid var(--line);border-radius:14px;box-shadow:0 2px 10px rgba(20,48,74,.05);
  transition:transform .25s,box-shadow .25s;}
.mu-stat:hover{transform:translateY(-5px);box-shadow:0 14px 26px rgba(20,48,74,.14);}
.mu-stat:after{content:"";position:absolute;left:0;bottom:0;height:3px;width:100%;transform-origin:left;
  background:var(--c,var(--blue));animation:muGrow .9s .3s ease both;}
.mu-stat>i{flex:0 0 auto;width:46px;height:46px;line-height:46px;text-align:center;border-radius:12px;font-size:19px;
  background:color-mix(in srgb,var(--c,var(--blue)) 14%,#fff);color:var(--c,var(--blue));transition:transform .3s;}
.mu-stat:hover>i{transform:rotate(-8deg) scale(1.12);}
.mu-stat span{display:block;color:var(--mute);font-size:11.5px;text-transform:uppercase;letter-spacing:.5px;}
.mu-stat b{font-size:22px;color:var(--navy);font-variant-numeric:tabular-nums;line-height:1.2;}
.mu-stat.ok{--c:var(--ok);} .mu-stat.warn{--c:var(--warn);} .mu-stat.bad{--c:var(--bad);}
.mu-stat.info{--c:var(--info);} .mu-stat.accent{--c:var(--accent);}

/* ---- filter form ---- */
.mu-filter{display:flex;flex-wrap:wrap;align-items:flex-end;gap:14px 18px;}
.mu-field{display:flex;flex-direction:column;gap:5px;min-width:160px;}
.mu-field label{font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--mute);margin:0;}
.mu-field input,.mu-field select{height:40px;padding:0 12px;border:1px solid #d5dde6;border-radius:9px;background:#fff;
  color:var(--ink);transition:border-color .2s,box-shadow .2s;}
.mu-field input:focus,.mu-field select:focus{outline:0;border-color:var(--blue);box-shadow:0 0 0 3px rgba(27,108,168,.15);}
.mu-seg{display:inline-flex;padding:4px;border-radius:10px;background:#eef2f6;}
.mu-seg label{margin:0;position:relative;cursor:pointer;}
.mu-seg input{position:absolute;opacity:0;}
.mu-seg span{display:block;padding:8px 18px;border-radius:7px;font-weight:600;color:var(--mute);transition:all .25s;}
.mu-seg input:checked+span{background:#fff;color:var(--blue);box-shadow:0 2px 8px rgba(20,48,74,.15);}

/* ---- tables ---- */
.mu-table-wrap{overflow-x:auto;border:1px solid var(--line);border-radius:12px;}
.mu-table{width:100%;border-collapse:collapse;margin:0;border:0;min-width:640px;}
.mu-table th{position:sticky;top:0;background:#eef3f8;color:var(--navy);border:0;border-bottom:2px solid #d5e1ed;
  padding:12px 14px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;text-align:left;white-space:nowrap;}
.mu-table td{padding:12px 14px;border:0;border-bottom:1px solid #eef1f5;vertical-align:middle;background:#fff;transition:background .2s;}
.mu-table tbody tr{opacity:0;animation:muUp .45s ease forwards;animation-delay:calc(var(--i,0) * 35ms);}
.mu-table tbody tr:nth-child(even) td{background:#f9fbfd;}
.mu-table tbody tr:hover td{background:#eef5fb;}
.mu-table tr.mu-off td{background:#fff5f5 !important;color:var(--bad);}
.mu-table tr.mu-today td{background:#fff9d6 !important;box-shadow:inset 3px 0 0 var(--accent);}
.mu-table tfoot td{background:#eef3f8;font-weight:700;color:var(--navy);border-top:2px solid #d5e1ed;}
.mu-table .c{text-align:center;} .mu-table .nw{white-space:nowrap;}
.mu-table a.mu-num{display:inline-block;min-width:34px;padding:3px 10px;border-radius:20px;text-align:center;font-weight:700;
  background:#e1effa;color:var(--info);text-decoration:none;transition:all .2s;}
.mu-table a.mu-num:hover{background:var(--info);color:#fff;transform:scale(1.1);}
.mu-table a.mu-num.ok{background:#e3f6ec;color:var(--ok);} .mu-table a.mu-num.ok:hover{background:var(--ok);color:#fff;}
.mu-table a.mu-num.wip{background:#fdeee3;color:var(--accent);} .mu-table a.mu-num.wip:hover{background:var(--accent);color:#fff;}
.mu-zero{color:#b3bcc7;}

/* ---- pills / tags / bars ---- */
.mu-pill{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:700;}
.mu-pill.ok{background:#e3f6ec;color:var(--ok);} .mu-pill.warn{background:#fdf1d9;color:var(--warn);}
.mu-pill.bad{background:#fbe5e5;color:var(--bad);} .mu-pill.info{background:#e1effa;color:var(--info);}
.mu-pill.muted{background:#eef1f5;color:var(--mute);}
.mu-split{display:flex;height:8px;width:150px;border-radius:8px;overflow:hidden;background:#eef1f5;margin-top:6px;}
.mu-split i{display:block;height:100%;transform-origin:left;animation:muGrow .8s .2s ease both;}
.mu-legend{display:flex;flex-wrap:wrap;gap:4px 10px;font-size:11.5px;color:var(--mute);margin-top:4px;}
.mu-legend b{font-weight:700;}
.mu-empty{padding:34px;text-align:center;color:var(--mute);background:#f9fbfd;border:1px dashed var(--line);border-radius:12px;}
.mu-empty i{display:block;font-size:30px;margin-bottom:8px;color:#c9d2dc;}
.mu-search{height:38px;padding:0 12px 0 34px;border:1px solid #d5dde6;border-radius:9px;min-width:220px;
  background:#fff url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' stroke='%236b7785' stroke-width='2'><circle cx='7' cy='7' r='5'/><path d='M11 11l4 4'/></svg>") no-repeat 11px center;}
.mu-search:focus{outline:0;border-color:var(--blue);box-shadow:0 0 0 3px rgba(27,108,168,.15);}

/* ---- loader (replaces the old black overlay + gif) ---- */
#loader{display:none;position:fixed;inset:0;z-index:9999;background:rgba(255,255,255,.7);backdrop-filter:blur(3px);text-align:center;}
#loader img{display:none;}
#loader:after{content:"";position:absolute;top:50%;left:50%;width:56px;height:56px;margin:-28px 0 0 -28px;border-radius:50%;
  border:5px solid #e9eef4;border-top-color:var(--accent,#e8742a);border-right-color:var(--blue,#1b6ca8);animation:muSpin .85s linear infinite;}

/* ---- skeleton ---- */
.mu-skel{height:14px;border-radius:6px;margin:12px 0;
  background:linear-gradient(90deg,#eceff3 25%,#f7f9fb 50%,#eceff3 75%);background-size:200% 100%;animation:muShimmer 1.3s linear infinite;}

/* ============ shared controls (popups live outside .mu-root, so these are global) ============ */

/* ---- jQuery UI datepicker / month picker ---- */
.ui-datepicker{width:auto !important;min-width:268px;padding:12px !important;border:1px solid #e4e9ef !important;border-radius:14px !important;background:#fff !important;
  box-shadow:0 22px 48px rgba(20,48,74,.24) !important;font-family:inherit !important;font-size:13px !important;z-index:3100 !important;animation:muPop .18s ease both;}
.ui-datepicker .ui-datepicker-header{padding:4px 0 10px !important;background:transparent !important;border:0 !important;}
.ui-datepicker .ui-datepicker-title{font-weight:700;color:#14304a;line-height:30px;}
.ui-datepicker .ui-datepicker-title select{height:30px;margin:0 2px;padding:0 6px;border:1px solid #d5dde6;border-radius:8px;background:#fff;color:#14304a;font-weight:600;}
.ui-datepicker .ui-datepicker-prev,.ui-datepicker .ui-datepicker-next{top:4px !important;width:30px !important;height:30px !important;border:1px solid #e4e9ef !important;border-radius:8px !important;background:#fff !important;cursor:pointer;transition:all .2s;}
.ui-datepicker .ui-datepicker-prev{left:2px !important;} .ui-datepicker .ui-datepicker-next{right:2px !important;}
.ui-datepicker .ui-datepicker-prev:hover,.ui-datepicker .ui-datepicker-next:hover{background:#14304a !important;border-color:#14304a !important;}
.ui-datepicker .ui-datepicker-prev:hover span,.ui-datepicker .ui-datepicker-next:hover span{filter:invert(1);}
.ui-datepicker table{margin:0 !important;font-size:13px !important;}
.ui-datepicker th{padding:6px 0 !important;color:#6b7785;font-size:11px;font-weight:700;text-transform:uppercase;border:0;}
.ui-datepicker td{padding:1px !important;border:0 !important;}
.ui-datepicker td a,.ui-datepicker td span{display:block;padding:7px 0 !important;text-align:center !important;border:0 !important;border-radius:9px !important;background:transparent !important;color:#22303f !important;font-weight:600;transition:all .15s;}
.ui-datepicker td a:hover{background:#e6f1fa !important;color:#1b6ca8 !important;transform:scale(1.08);}
.ui-datepicker td .ui-state-highlight{background:#fdeee3 !important;color:#e8742a !important;}
.ui-datepicker td .ui-state-active{background:#14304a !important;color:#fff !important;box-shadow:0 6px 14px rgba(20,48,74,.3);}
.ui-datepicker td.ui-state-disabled span{opacity:.35;}
.ui-datepicker .ui-datepicker-buttonpane{margin:10px 0 0 !important;padding:10px 0 0 !important;border:0 !important;border-top:1px solid #e4e9ef !important;background:transparent !important;}
.ui-datepicker .ui-datepicker-buttonpane button{height:34px;padding:0 16px !important;border:1px solid #e4e9ef !important;border-radius:9px !important;background:#fff !important;color:#14304a !important;font-weight:600;opacity:1 !important;cursor:pointer;transition:all .2s;}
.ui-datepicker .ui-datepicker-buttonpane button:hover{border-color:#1b6ca8 !important;color:#1b6ca8 !important;}
.ui-datepicker .ui-datepicker-buttonpane button.ui-priority-primary{background:#14304a !important;border-color:#14304a !important;color:#fff !important;}
.mu-month-only .ui-datepicker-calendar,body.mu-month-open .ui-datepicker-calendar{display:none !important;}
body.mu-month-open .ui-datepicker-current{display:none !important;}
body.mu-month-open .ui-datepicker{min-width:230px;}
.ui-datepicker .ui-datepicker-title{display:flex;align-items:center;justify-content:center;gap:6px;margin:0 38px !important;}
.ui-datepicker .ui-datepicker-title select.ui-datepicker-month,.ui-datepicker .ui-datepicker-title select.ui-datepicker-year{width:auto !important;margin:0 !important;display:inline-block;}
.ui-datepicker .ui-datepicker-prev,.ui-datepicker .ui-datepicker-next{display:flex !important;align-items:center;justify-content:center;}
.ui-datepicker .ui-datepicker-prev .ui-icon,.ui-datepicker .ui-datepicker-next .ui-icon{display:none !important;}
.ui-datepicker .ui-datepicker-prev:after,.ui-datepicker .ui-datepicker-next:after{font-size:20px;line-height:1;color:#14304a;font-weight:700;}
.ui-datepicker .ui-datepicker-prev:after{content:"‹";}
.ui-datepicker .ui-datepicker-next:after{content:"›";}
.ui-datepicker .ui-datepicker-prev:hover:after,.ui-datepicker .ui-datepicker-next:hover:after{color:#fff;}
.ui-datepicker .ui-datepicker-prev.ui-state-disabled,.ui-datepicker .ui-datepicker-next.ui-state-disabled{opacity:.35;cursor:default;}

/* ---- DataTables: search, page length, info, pagination, export buttons ---- */
.mu-root .dataTables_wrapper{font-size:13px;color:#22303f;}
.mu-root .dataTables_wrapper .dataTables_length,.mu-root .dataTables_wrapper .dataTables_filter{margin-bottom:12px;color:#6b7785;font-weight:600;}
.mu-root .dataTables_wrapper .dataTables_filter input{height:38px;margin-left:8px;padding:0 12px 0 34px;border:1px solid #d5dde6;border-radius:9px;min-width:220px;
  background:#fff url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' stroke='%236b7785' stroke-width='2'><circle cx='7' cy='7' r='5'/><path d='M11 11l4 4'/></svg>") no-repeat 11px center;transition:border-color .2s,box-shadow .2s;}
.mu-root .dataTables_wrapper .dataTables_length select{height:38px;margin:0 6px;padding:0 10px;border:1px solid #d5dde6;border-radius:9px;background:#fff;color:#22303f;}
.mu-root .dataTables_wrapper .dataTables_filter input:focus,.mu-root .dataTables_wrapper .dataTables_length select:focus{outline:0;border-color:#1b6ca8;box-shadow:0 0 0 3px rgba(27,108,168,.15);}
.mu-root .dataTables_wrapper .dataTables_info{padding-top:16px;color:#6b7785;font-size:12.5px;}
.mu-root .dataTables_wrapper .dataTables_paginate{padding-top:12px;}
.mu-root .dataTables_wrapper .dataTables_paginate .paginate_button{min-width:36px;margin:0 2px;padding:6px 12px !important;border:1px solid #e4e9ef !important;border-radius:9px !important;background:#fff !important;color:#14304a !important;font-weight:600;box-shadow:none !important;transition:all .2s;}
.mu-root .dataTables_wrapper .dataTables_paginate .paginate_button:hover{background:#eef5fb !important;border-color:#1b6ca8 !important;color:#1b6ca8 !important;transform:translateY(-1px);}
.mu-root .dataTables_wrapper .dataTables_paginate .paginate_button.current,.mu-root .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover{background:linear-gradient(135deg,#e8742a,#f08a45) !important;border-color:transparent !important;color:#fff !important;}
.mu-root .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,.mu-root .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover{opacity:.45;background:#fff !important;color:#6b7785 !important;border-color:#e4e9ef !important;transform:none;cursor:default;}
.mu-root .dataTables_wrapper .dt-buttons{margin-bottom:12px;display:inline-flex;flex-wrap:wrap;gap:8px;}
.mu-root .dt-buttons .dt-button,.mu-root button.dt-button,.mu-root a.dt-button,.mu-root div.dt-button{margin:0 !important;padding:8px 16px !important;border:1px solid #e4e9ef !important;border-radius:9px !important;background:#fff !important;background-image:none !important;
  color:#14304a !important;font-size:13px;font-weight:600;box-shadow:none !important;text-shadow:none !important;transition:transform .15s,box-shadow .2s,border-color .2s;}
.mu-root .dt-buttons .dt-button:hover,.mu-root button.dt-button:hover{background:#fff !important;border-color:#1b6ca8 !important;color:#1b6ca8 !important;transform:translateY(-2px);box-shadow:0 6px 14px rgba(20,48,74,.15) !important;}
.dt-button-collection{margin-top:6px !important;padding:6px !important;border:1px solid #e4e9ef !important;border-radius:12px !important;background:#fff !important;box-shadow:0 18px 40px rgba(20,48,74,.2) !important;}
.dt-button-collection .dt-button{display:block !important;width:100%;margin:2px 0 !important;padding:8px 14px !important;border:0 !important;border-radius:8px !important;background:#fff !important;background-image:none !important;color:#22303f !important;text-align:left;font-weight:600;box-shadow:none !important;}
.dt-button-collection .dt-button:hover{background:#eef5fb !important;color:#1b6ca8 !important;}
.mu-root table.dataTable.mu-table{border-collapse:collapse !important;margin:0 !important;}
.mu-root table.dataTable.mu-table.no-footer{border-bottom:0;}
.mu-root table.dataTable.mu-table thead th{border-bottom:2px solid #d5e1ed !important;}
.mu-root table.dataTable.mu-table thead .sorting,.mu-root table.dataTable.mu-table thead .sorting_asc,.mu-root table.dataTable.mu-table thead .sorting_desc{padding-right:26px;}
.mu-root table.dataTable.mu-table tbody tr{background:transparent;}
.mu-root table.dataTable.mu-table tbody td{border-top:0;}
.mu-root div.dataTables_scrollHead table,.mu-root div.dataTables_scrollBody table{margin:0 !important;}

.mu-root .dataTables_wrapper::after{content:"";display:table;clear:both;}
.mu-root .dataTables_wrapper .dt-buttons{float:left;margin-right:14px;}
.mu-root .dataTables_wrapper .dataTables_length{float:left;display:flex;align-items:center;}
.mu-root .dataTables_wrapper .dataTables_filter{float:right;}
.mu-root .dataTables_wrapper .table-wrapper,.mu-root .dataTables_wrapper .dataTables_scroll{clear:both;overflow-x:auto;border:1px solid #e4e9ef;border-radius:12px;}
.mu-root .dataTables_wrapper .dataTables_info{float:left;}
.mu-root .dataTables_wrapper .dataTables_paginate{float:right;}
@media(max-width:700px){.mu-root .dataTables_wrapper .dt-buttons,.mu-root .dataTables_wrapper .dataTables_length,.mu-root .dataTables_wrapper .dataTables_filter,.mu-root .dataTables_wrapper .dataTables_info,.mu-root .dataTables_wrapper .dataTables_paginate{float:none;}}

/* ---- select2 ---- */
.mu-root .select2-container{max-width:100%;}
.mu-root .select2-container--default .select2-selection--single,.mu-root .select2-container--default .select2-selection--multiple{min-height:40px;border:1px solid #d5dde6;border-radius:9px;background:#fff;transition:border-color .2s,box-shadow .2s;}
.mu-root .select2-container--default .select2-selection--single{display:flex;align-items:center;}
.mu-root .select2-container--default .select2-selection--single .select2-selection__rendered{padding-left:12px;color:#22303f;line-height:38px;}
.mu-root .select2-container--default .select2-selection--single .select2-selection__arrow{height:38px;right:6px;}
.mu-root .select2-container--default.select2-container--focus .select2-selection--multiple,.mu-root .select2-container--default.select2-container--open .select2-selection--single,.mu-root .select2-container--default.select2-container--open .select2-selection--multiple{border-color:#1b6ca8;box-shadow:0 0 0 3px rgba(27,108,168,.15);}
.mu-root .select2-container--default .select2-selection--multiple .select2-selection__choice{margin-top:6px;padding:2px 9px;border:0;border-radius:20px;background:#e1effa;color:#1b6ca8;font-weight:700;font-size:12px;}
.mu-root .select2-container--default .select2-selection--multiple .select2-selection__choice__remove{margin-right:5px;color:#1b6ca8;}
.select2-dropdown{border:1px solid #e4e9ef !important;border-radius:12px !important;box-shadow:0 18px 40px rgba(20,48,74,.2);overflow:hidden;z-index:3100;}
.select2-container--default .select2-search--dropdown .select2-search__field{height:34px;border:1px solid #d5dde6;border-radius:8px;}
.select2-container--default .select2-results__option--highlighted[aria-selected],.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable{background:#14304a !important;color:#fff !important;}
.select2-container--default .select2-results__option[aria-selected=true],.select2-container--default .select2-results__option--selected{background:#eef5fb;color:#1b6ca8;font-weight:600;}

/* ---- legacy server-side pagination (<ul class="pagination">) ---- */
.mu-root .pagination{display:flex;flex-wrap:wrap;gap:6px;float:none !important;justify-content:flex-end;margin:16px 0 0 !important;padding:0;list-style:none;}
.mu-root .pagination>li{display:block;}
.mu-root .pagination>li>a,.mu-root .pagination>li>span{float:none !important;margin:0 !important;min-width:36px;padding:7px 12px !important;border:1px solid #e4e9ef !important;border-radius:9px !important;background:#fff !important;color:#14304a !important;font-weight:600;text-align:center;transition:all .2s;}
.mu-root .pagination>li>a:hover{background:#eef5fb !important;border-color:#1b6ca8 !important;color:#1b6ca8 !important;}
.mu-root .pagination>.active>a,.mu-root .pagination>.active>span{background:linear-gradient(135deg,#e8742a,#f08a45) !important;border-color:transparent !important;color:#fff !important;}
.mu-root .pagination>.disabled>a,.mu-root .pagination>.disabled>span{opacity:.45;}

/* ---- drill-down panel (ajax partials loaded into #article_list) ---- */
.mu-root #article_list:not(:empty){margin-top:16px;padding:18px 20px;border:1px solid var(--line);border-radius:14px;background:#fff;box-shadow:0 2px 10px rgba(20,48,74,.05);overflow-x:auto;animation:muUp .4s ease both;}
.mu-detail-head{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:12px;}
.mu-chip-d{display:inline-flex;align-items:center;gap:7px;padding:6px 12px;border-radius:20px;background:#eef3f8;color:var(--mute);font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;}
.mu-chip-d i{color:var(--accent);} .mu-chip-d b{color:var(--navy);font-size:13px;letter-spacing:0;text-transform:none;}
.mu-detail-actions{margin-bottom:14px;}
.mu-btn.mu-btn-sm{padding:6px 14px;font-size:12px;border-radius:8px;}
.mu-btn.primary.mu-btn-sm{box-shadow:none;}
.mu-icon-btn{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border:0;border-radius:9px;background:#e1effa;color:var(--info);cursor:pointer;transition:all .2s;}
.mu-icon-btn:hover{background:var(--info);color:#fff;transform:scale(1.1) rotate(-6deg);}
.mu-root #article_list .mu-table{min-width:0;}
.mu-root #article_list .mu-table td,.mu-root #article_list .mu-table th{padding:10px 12px;}
.mu-root #article_list .mu-table tbody tr{animation:none;opacity:1;}

/* ---- day-by-day matrix: keep the stage label visible while scrolling ---- */
.mu-matrix tbody th{position:sticky !important;left:0;z-index:2;background:#f4f7fb !important;box-shadow:2px 0 0 #e4e9ef;}
.mu-matrix thead th[rowspan]{position:sticky !important;left:0;z-index:4;box-shadow:2px 0 0 #d5e1ed;}

/* ---- native selects: same chevron everywhere ---- */
.mu-field select:not([multiple]){appearance:none;-webkit-appearance:none;padding-right:34px;
  background:#fff url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='8' fill='none' stroke='%236b7785' stroke-width='2'><path d='M1 1l5 5 5-5'/></svg>") no-repeat right 12px center;}
.mu-field select[multiple]{height:auto;min-height:40px;}

/* ---- leftover bootstrap buttons on report pages ---- */
.mu-root .btn.btn-success,.mu-root .btn.btn-primary{border:0;border-radius:9px;padding:9px 18px;font-weight:600;transition:transform .15s,box-shadow .2s;}
.mu-root .btn.btn-success:hover,.mu-root .btn.btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 14px rgba(20,48,74,.18);}
.mu-field .dvr{width:100%;min-width:230px;}
.mu-field .dvr-trigger{height:40px;border-color:#d5dde6;border-radius:9px;}

@media(max-width:700px){.mu-hero h1{font-size:19px;}.mu-filter{flex-direction:column;align-items:stretch;}}
@media print{#mu-progress,.mu-btn,.mu-filter-panel{display:none !important;}.mu-root *{animation:none !important;opacity:1 !important;transform:none !important;}}
@media(prefers-reduced-motion:reduce){.mu-root *,.mu-hero:before{animation:none !important;transition:none !important;}
  .mu-reveal,.mu-table tbody tr{opacity:1;transform:none;}}

/* ============ table spec = current work page (dashboard .dv-table) ============ */
.mu-table-wrap{border:1px solid #e6ebf1 !important;border-radius:12px;overflow-x:auto;}
.mu-table{font-size:13.5px;border-collapse:separate !important;border-spacing:0;}
.mu-table th{background:#f4f7fa !important;color:#6b7785;font-size:11.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;border:0 !important;padding:10px 12px;}
.mu-table td{padding:10px 12px;border:0 !important;border-top:1px solid #e6ebf1 !important;background:#fff !important;color:#1d2b3a;}
.mu-table tbody tr:first-child td{border-top:0 !important;}
.mu-table tbody tr:nth-child(even) td{background:#fff !important;}
.mu-table tbody tr:hover td{background:#f8fbfd !important;}
.mu-table tfoot td{background:#f4f7fa !important;color:#14304a;font-weight:700;border-top:1px solid #d5e1ed !important;}
.mu-table tr.mu-total td{background:#f4f7fa !important;color:#14304a;font-weight:700;}
.mu-table.mu-idx tbody td:first-child{color:#6b7785;font-weight:700;width:48px;}
.mu-table thead th.sorting,.mu-table thead th.sorting_asc,.mu-table thead th.sorting_desc{position:relative;padding-right:26px !important;cursor:pointer;user-select:none;background-image:none !important;transition:color .2s,background .2s;}
.mu-table thead th.sorting:hover{color:#14304a;background:#e9eef4 !important;}
.mu-table thead th.sorting:after,.mu-table thead th.sorting_asc:after,.mu-table thead th.sorting_desc:after{content:"";position:absolute;right:10px;top:50%;width:0;height:0;margin-top:-3px;border-left:4px solid transparent;border-right:4px solid transparent;border-top:5px solid #b9c5d1;opacity:1;}
.mu-table thead th.sorting:before,.mu-table thead th.sorting_asc:before,.mu-table thead th.sorting_desc:before{content:"";position:absolute;right:10px;top:50%;width:0;height:0;margin-top:-9px;border-left:4px solid transparent;border-right:4px solid transparent;border-bottom:5px solid #b9c5d1;opacity:1;}
.mu-table thead th.sorting_asc:before{border-bottom-color:#F56500;} .mu-table thead th.sorting_desc:after{border-top-color:#F56500;}
.mu-table thead th.sorting_asc,.mu-table thead th.sorting_desc{color:#14304a;}
.mu-matrix tbody th{background:#fff !important;color:#14304a;font-size:13px;font-weight:700;}
.mu-matrix tbody td{background:#fff !important;}

/* DataTables toolbar laid out like the work page: buttons | length | ... search, table, info | pager */
.mu-root .dataTables_wrapper{display:flex;flex-wrap:wrap;align-items:center;gap:10px 14px;}
.mu-root .dataTables_wrapper::after{display:none;}
.mu-root .dataTables_wrapper>.dt-buttons{order:1;float:none;margin:0;}
.mu-root .dataTables_wrapper>.dataTables_length{order:2;float:none;margin:0;}
.mu-root .dataTables_wrapper>.dataTables_filter{order:3;float:none;margin:0 0 0 auto;}
.mu-root .dataTables_wrapper>table,.mu-root .dataTables_wrapper>.table-wrapper,.mu-root .dataTables_wrapper>.dataTables_scroll{order:4;flex:0 0 100%;margin-top:6px !important;border:1px solid #e6ebf1;border-radius:12px;overflow:hidden;}
.mu-root .dataTables_wrapper>.table-wrapper{overflow-x:auto;}
.mu-root .dataTables_wrapper>.table-wrapper table,.mu-root .dataTables_wrapper>.dataTables_scroll table{border:0;}
.mu-root .dataTables_wrapper>.dataTables_info{order:5;float:none;margin:0;padding:0;color:#6b7785;font-size:12.5px;}
.mu-root .dataTables_wrapper>.dataTables_paginate{order:6;float:none;margin:0 0 0 auto;padding:0;}
.mu-root .dataTables_wrapper .dataTables_filter label,.mu-root .dataTables_wrapper .dataTables_length label{display:flex;align-items:center;gap:6px;margin:0;color:#6b7785;font-weight:600;font-size:12.5px;}
.mu-root .dataTables_wrapper .dataTables_filter input{width:190px;height:36px;margin:0;}
.mu-root .dataTables_wrapper .dataTables_length select{height:36px;margin:0;}
.mu-root .dt-buttons .dt-button,.mu-root button.dt-button,.mu-root a.dt-button{padding:6px 12px !important;font-size:12.5px !important;font-weight:700;border-radius:10px !important;}
.mu-root .dataTables_wrapper .dataTables_paginate .paginate_button{min-width:0;padding:4px 11px !important;font-size:12.5px;border-radius:8px !important;}
.mu-root .dataTables_wrapper .dataTables_paginate .paginate_button.current,.mu-root .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover{background:#14304a !important;border-color:#14304a !important;color:#fff !important;}
.mu-root .dataTables_wrapper .dataTables_paginate .paginate_button.disabled{pointer-events:none;}

/* drill-down: one card only (the partial brings its own container) */
.mu-root #article_list .custom-container,.mu-root #article_list .container,.mu-root #article_list .container-fluid{max-width:none !important;width:100% !important;margin:0 !important;padding:0 !important;box-shadow:none !important;border-radius:0 !important;border:0 !important;background:transparent !important;}
.mu-root #article_list .table-responsive{overflow-x:auto;}
.mu-root #article_list .mu-table{width:100%;}

/* ============ status badges = current work page (article-filter-ui .label pills) ============ */
.mu-root .label,.mu-root label.label,.mu-root span.label{display:inline-block !important;width:auto !important;padding:3px 10px !important;border:0 !important;border-radius:20px !important;font-size:11.5px !important;font-weight:700 !important;line-height:1.4 !important;text-shadow:none !important;vertical-align:middle;white-space:nowrap;}
.mu-root .label-success{background:#e3f6ec !important;color:#1e9e61 !important;}
.mu-root .label-default{background:#eef1f5 !important;color:#6b7785 !important;}
.mu-root .label-info{background:#e1effa !important;color:#1b6ca8 !important;}
.mu-root .label-warning{background:#fdf1d9 !important;color:#b87800 !important;}
.mu-root .label-danger{background:#fbe5e5 !important;color:#d64545 !important;}
.mu-root .label-primary{background:#e3eaf2 !important;color:#14304a !important;}
.mu-root .label.bg-secondary{background:#eef1f5 !important;color:#6b7785 !important;}
.mu-root .label.text-white{color:inherit;}

/* old datatable.css forces sort_*.png on the header cell - the work-page arrows above replace it */
.mu-root table.dataTable.mu-table thead th,.mu-root table.dataTable.mu-table thead td,.mu-root table.mu-table thead th.sorting,.mu-root table.mu-table thead th.sorting_asc,.mu-root table.mu-table thead th.sorting_desc{background-image:none !important;}

/* legacy pager wrapper had its own border/background - the buttons carry the styling now */
.mu-root .dataTables_wrapper .dataTables_paginate{border:0 !important;background:transparent !important;box-shadow:none !important;padding:0 !important;display:flex;align-items:center;gap:2px;flex-wrap:wrap;justify-content:flex-end;}
.mu-root .dataTables_wrapper .dataTables_paginate span{display:inline-flex;align-items:center;gap:2px;}
.mu-root .dataTables_wrapper .dataTables_paginate .paginate_button{display:inline-block;margin:0 !important;}
.mu-root .dataTables_wrapper .dataTables_paginate .ellipsis{padding:0 6px;color:#6b7785;}

/* drill-down sits inside the report panel already: divider instead of a second card, one scroller */
.mu-root #article_list:not(:empty){margin-top:18px;padding:18px 0 0;border:0;border-top:1px solid #e6ebf1;border-radius:0;box-shadow:none;background:transparent;overflow:visible;}
.mu-root #article_list .table-responsive{overflow-x:auto;border:1px solid #e6ebf1 !important;border-radius:12px !important;}
.mu-root #article_list .table-responsive .mu-table{border:0;}
.mu-root #article_list .mu-detail-head{margin-bottom:14px;}

/* row action buttons in the drill-down (Schedule Today / Unschedule / ...): compact tinted pills, filled on hover */
.mu-root #article_list .btn{--b:#1b6ca8;--bg:#e8f1fb;--bd:#c5daef;display:inline-flex !important;align-items:center;gap:6px;width:auto !important;margin:0;padding:5px 12px !important;border:1px solid var(--bd) !important;border-radius:20px !important;
  background:var(--bg) !important;background-image:none !important;color:var(--b) !important;font-size:12px !important;font-weight:700 !important;line-height:1.2 !important;white-space:nowrap;box-shadow:none !important;text-shadow:none !important;cursor:pointer;transition:background .2s,color .2s,transform .15s;}
.mu-root #article_list .btn.btn-success{--b:#1e9e61;--bg:#e3f6ec;--bd:#bfe6d0;}
.mu-root #article_list .btn.btn-warning{--b:#b87800;--bg:#fdf1d9;--bd:#f1dcab;}
.mu-root #article_list .btn.btn-danger{--b:#d64545;--bg:#fbe5e5;--bd:#f2c4c4;}
.mu-root #article_list .btn.btn-secondary,.mu-root #article_list .btn.btn-default{--b:#6b7785;--bg:#eef1f5;--bd:#d9dfe6;}
.mu-root #article_list .btn:hover:not(:disabled){background:var(--b) !important;border-color:var(--b) !important;color:#fff !important;transform:translateY(-1px);}
.mu-root #article_list .btn:disabled,.mu-root #article_list .btn.processing{opacity:.55;cursor:not-allowed;transform:none;}

.mu-root #article_list .mu-btn.primary,.mu-root #article_list .mu-btn.mu-btn-sm{padding:5px 14px;font-size:12px;border-radius:20px;box-shadow:none;}
.mu-root #article_list .mu-btn.primary{background:#fdeee3;border:1px solid #f3d2b8;color:#e8742a;}
.mu-root #article_list .mu-btn.primary:hover{background:#e8742a;border-color:#e8742a;color:#fff;box-shadow:none;}

/* select2 tags: room for the absolutely positioned remove button */
.mu-root .select2-container--default .select2-selection--multiple .select2-selection__choice{padding:2px 10px 2px 22px !important;position:relative;}
.mu-root .select2-container--default .select2-selection--multiple .select2-selection__choice__remove{left:2px !important;top:0;bottom:0;padding:0 5px !important;border:0 !important;background:transparent !important;color:#1b6ca8 !important;font-weight:700;line-height:1.6;cursor:pointer;}
.mu-root .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover{color:#d64545 !important;}
.mu-root .select2-container--default .select2-selection--multiple .select2-selection__rendered{padding:0 6px 4px;}

/* tables whose body rows use <th> for the first cells (Customer Monthly, ...): read as normal cells */
.mu-table tbody th{background:#fff !important;color:#14304a !important;font-size:13.5px;font-weight:600;letter-spacing:0;text-transform:none;border:0 !important;border-top:1px solid #e6ebf1 !important;padding:10px 12px;text-align:left;}
.mu-table tbody tr:first-child th{border-top:0 !important;}
.mu-table tbody tr:hover th{background:#f8fbfd !important;}
.mu-matrix tbody th{background:#fff !important;}

/* one border only: DataTables draws its own around the table, so the wrapper must not add a second box */
.mu-table-wrap:has(.dataTables_wrapper),.mu-table-wrap:has(.dataTables_wrapper) .dataTables_wrapper{border:0 !important;border-radius:0 !important;box-shadow:none !important;background:transparent !important;padding:0 !important;overflow:visible !important;}
.mu-root .dataTables_wrapper>.dataTables_scroll,.mu-root .dataTables_wrapper>.table-wrapper{border:1px solid #e6ebf1;border-radius:12px;}
.mu-root .dataTables_wrapper>table.dataTable{border:1px solid #e6ebf1 !important;}
.mu-root .dataTables_wrapper .dataTables_filter input{border:1px solid #d5dde6 !important;outline:0;box-shadow:none;}
.mu-root .dataTables_wrapper .dataTables_filter input:focus{border-color:#1b6ca8 !important;box-shadow:0 0 0 3px rgba(27,108,168,.15);}
.mu-root .dataTables_wrapper .dataTables_length select{border:1px solid #d5dde6 !important;}

/* wide DataTables scroll inside their panel, never the whole page */
.mu-table-wrap:has(.dataTables_wrapper){overflow-x:auto !important;max-width:100%;}
.mu-root .mu-panel{min-width:0;max-width:100%;}
.mu-root .dataTables_wrapper{min-width:0;}

/* narrow summary tables: the border hugs the table instead of a full-width empty box */
.mu-table-wrap:has(>.mu-table[style*="max-width"]){width:fit-content;max-width:100%;}
.mu-table-wrap>.mu-table[style*="max-width"]{width:auto !important;min-width:320px;}
/* wide tables inside a panel scroll in the panel */
#tableWrapper{overflow-x:auto;max-width:100%;border:1px solid #e6ebf1;border-radius:12px;}
#tableWrapper .dataTables_wrapper>table.dataTable{border:0 !important;}
</style>
<link rel="stylesheet" href="dv-rangepicker.css?v=1">
<?php
}

function mu_foot()
{
?>
<div id="mu-progress"></div>
<script>
(function () {
  var $ = window.jQuery;
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* thin progress bar while the page / ajax calls are busy */
  var bar = document.getElementById('mu-progress'), pending = 0, val = 0, t;
  function set(v) { val = v; bar.style.width = v + '%'; bar.style.opacity = 1; }
  function start() { if (pending++ === 0) { set(8); clearInterval(t); t = setInterval(function () { set(val + (92 - val) * .08); }, 200); } }
  function done() { if (--pending > 0) { return; } pending = 0; clearInterval(t); set(100); setTimeout(function () { bar.style.opacity = 0; setTimeout(function () { bar.style.width = 0; }, 400); }, 250); }
  start(); window.addEventListener('load', done);
  if ($) { $(document).ajaxStart(function () {}).ajaxSend(start).ajaxComplete(done).ajaxComplete(function () { if (window.muBoot) { window.muBoot(document); } }); }

  /* count-up numbers */
  function countUp(el) {
    var end = parseFloat(el.getAttribute('data-count')) || 0,
        dec = parseInt(el.getAttribute('data-decimals') || '0', 10),
        suf = el.getAttribute('data-suffix') || '';
    function fmt(n) { return n.toLocaleString(undefined, { minimumFractionDigits: dec, maximumFractionDigits: dec }) + suf; }
    if (reduce || end === 0) { el.textContent = fmt(end); return; }
    var t0 = null, dur = 900;
    (function step(ts) {
      if (t0 === null) { t0 = ts; }
      var p = Math.min(1, (ts - t0) / dur), e = 1 - Math.pow(1 - p, 3);
      el.textContent = fmt(end * e);
      if (p < 1) { requestAnimationFrame(step); }
    })(performance.now());
  }

  function boot(root) {
    var counted = root.querySelectorAll('[data-count]:not([data-done])');
    var items = root.querySelectorAll('.mu-reveal:not(.in)');
    var rows = root.querySelectorAll('.mu-table tbody tr:not([data-ix])');
    root.querySelectorAll('.mu-table').forEach(function (t) {
      var th = t.querySelector('thead th');
      if (th && /^\s*(s\.?\s?no\.?|#)\s*$/i.test(th.textContent)) { t.classList.add('mu-idx'); }
    });
    for (var r = 0; r < rows.length; r++) { rows[r].setAttribute('data-ix', 1); rows[r].style.setProperty('--i', Math.min(r, 25)); }
    items.forEach(function (el, i) { el.style.transitionDelay = Math.min(i, 8) * 70 + 'ms'; });
    if (!('IntersectionObserver' in window)) {
      items.forEach(function (el) { el.classList.add('in'); });
      counted.forEach(function (el) { el.setAttribute('data-done', 1); countUp(el); });
      return;
    }
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (e) {
        if (!e.isIntersecting) { return; }
        var el = e.target;
        el.classList.add('in');
        if (el.hasAttribute('data-count') && !el.hasAttribute('data-done')) { el.setAttribute('data-done', 1); countUp(el); }
        io.unobserve(el);
      });
    }, { threshold: .12 });
    items.forEach(function (el) { io.observe(el); });
    counted.forEach(function (el) { io.observe(el); });
  }
  window.muBoot = boot;
  document.addEventListener('DOMContentLoaded', function () { boot(document); });

  /* month-only pickers: hide the day grid (the popup is shared, so flag it on <body>) */
  document.addEventListener('focusin', function (e) {
    var t = e.target;
    if (!t || !t.classList) { return; }
    if (t.classList.contains('monthlyinputdate2') || t.classList.contains('monthpicker')) { document.body.classList.add('mu-month-open'); }
    else if (t.classList.contains('hasDatepicker')) { document.body.classList.remove('mu-month-open'); }
  });
  document.addEventListener('mousedown', function (e) {
    var t = e.target;
    if (t && t.classList && (t.classList.contains('monthlyinputdate2') || t.classList.contains('monthpicker'))) { document.body.classList.add('mu-month-open'); }
  });

  /* ripple */
  document.addEventListener('click', function (e) {
    var b = e.target.closest ? e.target.closest('.mu-btn') : null;
    if (!b || reduce) { return; }
    var r = b.getBoundingClientRect(), s = Math.max(r.width, r.height), d = document.createElement('span');
    d.className = 'mu-ripple';
    d.style.cssText = 'width:' + s + 'px;height:' + s + 'px;left:' + (e.clientX - r.left - s / 2) + 'px;top:' + (e.clientY - r.top - s / 2) + 'px';
    b.appendChild(d); setTimeout(function () { d.remove(); }, 650);
  });

  /* live table filter */
  document.addEventListener('input', function (e) {
    var i = e.target, sel = i.getAttribute && i.getAttribute('data-mu-filter');
    if (!sel) { return; }
    var q = i.value.toLowerCase();
    document.querySelectorAll(sel + ' tbody tr').forEach(function (tr) {
      tr.style.display = tr.textContent.toLowerCase().indexOf(q) === -1 ? 'none' : '';
    });
  });
})();
</script>
<?php
}
