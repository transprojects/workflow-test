<?php
/* ============================================================================
 * Vendor portal "Start Work": the look of the main app's Start Work v2 (hero with the article summary, cards, grid form, buttons).
 * Design layer only: the page's form, fields, upload and scripts are unchanged.
 *   vp_sw_theme(array('id'=>..,'client'=>..,'journal'=>..,'stage'=>..,'dept'=>..,'recv'=>..,'due'=>..,'back'=>..))   before </body>
 * ========================================================================== */
function vp_sw_theme($sum)
{
?>
<style id="sw-theme">
  /* ================= start_work_v2 theme ================= */
  body{background:#f3f6fa !important;}
  .container-fluid:has(.articleheading){background:#fff !important;border-bottom:1px solid #e4e9ef;box-shadow:0 2px 10px rgba(20,48,74,.05);}
  .articleheading{font-size:22px !important;font-weight:700 !important;color:#14304a !important;}

  /* article summary strip */
  .sw-summary{width:min(1180px,calc(100% - 36px));margin:22px auto 0;padding:20px 26px;border-radius:16px;color:#14304a;
    background:linear-gradient(120deg,#f4f9fe 0%,#e8f1fa 100%);border:1px solid #d6e4f1;box-shadow:0 2px 12px rgba(20,48,74,.06);
    display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:14px 26px;animation:swRise .45s ease;}
  .sw-summary small{display:block;font-size:11px;letter-spacing:.8px;text-transform:uppercase;color:#6b7785;}
  .sw-sum-id{font-size:23px;font-weight:700;line-height:1.25;word-break:break-all;}
  .sw-sum-sub{color:#5b6b7b;margin-top:2px;}
  .sw-sum-chips{display:flex;flex-wrap:wrap;gap:10px;}
  .sw-chip{min-width:112px;padding:9px 14px;border-radius:10px;background:#fff;border:1px solid #dbe6f1;}
  .sw-chip b{display:block;font-size:14px;font-weight:700;color:#14304a;}
  .sw-chip.hot{border-color:#f6d3bb;background:#fff7f1;} .sw-chip.hot b{color:#d2631d;}

  /* cards */
  .addarticleformouter{background:transparent !important;padding:0 !important;box-shadow:none !important;border:0 !important;}
  .addarticleforminner{width:min(1180px,calc(100% - 36px)) !important;max-width:1180px !important;margin:18px auto !important;padding:26px 32px 30px !important;
    border-radius:16px !important;border:1px solid #e4e9ef !important;background:#fff !important;box-shadow:0 4px 18px rgba(20,48,74,.07) !important;animation:swRise .45s ease;}
  .addarticleforminner .container.mt-4{margin-top:0 !important;padding:0 !important;}
  @keyframes swRise{from{opacity:0;transform:translateY(12px);}to{opacity:1;transform:none;}}

  /* section titles (was a black arrow tag) */
  .formcont1heading{position:relative;display:block !important;background:none !important;color:#14304a !important;font-size:17px !important;font-weight:700 !important;
    padding:2px 0 14px 18px !important;margin:0 0 4px !important;border:0 !important;border-bottom:1px solid #e4e9ef !important;clip-path:none !important;height:auto !important;
    line-height:1.4 !important;word-break:break-all;}
  .formcont1heading::before{content:"";position:absolute;left:0;top:2px;bottom:16px;width:5px;border-radius:3px;background:linear-gradient(#e8742a,#1b6ca8);}
  .formcont1heading::after{display:none !important;}

  /* top action buttons (queries / instructions / specs ...) */
  #validation-form .d-flex.gap-3.justify-content-center{flex-wrap:wrap;gap:10px !important;margin:6px 0 4px;}
  #validation-form .form_btn .btn:not(.btn-purple):not(.btn-success):not(.btn-orange):not(.btn-danger){background:#fff !important;color:#14304a !important;border:1px solid #cfd8e3 !important;border-radius:22px !important;
    padding:8px 18px !important;font-size:13px !important;font-weight:600 !important;width:auto !important;height:auto !important;box-shadow:none !important;transition:all .2s;}
  #validation-form .form_btn .btn:not(.btn-purple):not(.btn-success):not(.btn-orange):not(.btn-danger):hover{background:#14304a !important;color:#fff !important;border-color:#14304a !important;}
  #validation-form .form_btn a.btn-orange{background:#fff7f1 !important;border:1px solid #f6d3bb !important;color:#d2631d !important;border-radius:22px !important;}
  #validation-form .form_btn a.btn-orange:hover{filter:brightness(.96);transform:translateY(-1px);}
  #validation-form .btn.btn-purple{background:#7c5cbf !important;color:#fff !important;}
  #validation-form .btn.btn-success{background:#1e9e61 !important;color:#fff !important;}
  #validation-form .btn.btn-danger{background:#d64545 !important;color:#fff !important;border:0 !important;border-radius:22px !important;padding:8px 20px !important;font-weight:600 !important;}   /* keeps its inline white text readable */
  #validation-form #openModal{border-radius:12px !important;padding:11px 18px !important;height:auto !important;width:auto !important;min-width:230px;border:1px solid #1b6ca8 !important;}
  #validation-form #openModal span{color:#1b6ca8 !important;font-weight:700;}

  /* conversion tools row */
  #validation-form .row.addspacing{display:flex;flex-wrap:wrap;justify-content:center;gap:12px !important;margin:12px 0 !important;}
  #validation-form .btn.btn-primary.col-2{width:auto !important;flex:0 0 auto;background:#eaf2fa !important;color:#1b6ca8 !important;border:1px solid #c5daef !important;
    border-radius:12px !important;padding:11px 20px !important;font-weight:600 !important;font-size:14px !important;transition:all .2s;}
  #validation-form .btn.btn-primary.col-2:hover{background:#1b6ca8 !important;color:#fff !important;transform:translateY(-1px);box-shadow:0 6px 14px rgba(27,108,168,.25);}

  /* the form body becomes a grid */
  #validation-form > .p-5{padding:20px 0 6px !important;display:grid !important;grid-template-columns:repeat(12,minmax(0,1fr));gap:20px 22px !important;}
  #validation-form > .p-5 > *{grid-column:1 / -1;min-width:0;}
  #validation-form > .p-5 > [class*="col-"]:not(.col-xs-12):not(.sw-wide){grid-column:span 4;max-width:none;width:auto;padding:0;}
  #validation-form > .p-5 > .sw-wide{grid-column:1 / -1 !important;max-width:none;width:auto;}
  #validation-form > .p-5 > .space-2, #validation-form > .p-5 > .space-20{display:none;}
  @media (max-width:991px){ #validation-form > .p-5 > [class*="col-"]:not(.col-xs-12):not(.sw-wide){grid-column:span 6;} }
  @media (max-width:640px){ #validation-form > .p-5 > [class*="col-"]:not(.col-xs-12):not(.sw-wide){grid-column:1 / -1;} .addarticleforminner{padding:20px 16px !important;} }

  /* labels + fields */
  #validation-form label{display:block;font-size:12.5px !important;font-weight:700 !important;letter-spacing:.3px;text-transform:uppercase;color:#5b6b7b !important;margin:0 0 7px !important;}
  #validation-form label span, #validation-form label font, #validation-form label small{text-transform:none;letter-spacing:0;font-weight:600;font-size:12px;}
  #validation-form label span[style*="red"], #validation-form label .red{color:#d64545 !important;}
  #validation-form input[type=text],#validation-form input[type=number],#validation-form input[type=email],#validation-form input[type=password],
  #validation-form select,#validation-form .form-select,#validation-form .form-control{height:46px !important;width:100% !important;border:1px solid #cfd8e3 !important;border-radius:10px !important;
    background-color:#fbfcfe !important;padding:8px 14px !important;font-size:14px !important;color:#22303f !important;box-shadow:none !important;transition:border-color .2s,box-shadow .2s,background .2s;}
  #validation-form textarea{width:100% !important;min-height:120px;border:1px solid #cfd8e3 !important;border-radius:10px !important;background:#fbfcfe !important;padding:12px 14px !important;font-size:14px !important;}
  #validation-form input:focus,#validation-form select:focus,#validation-form textarea:focus{border-color:#1b6ca8 !important;background:#fff !important;box-shadow:0 0 0 4px rgba(27,108,168,.14) !important;outline:0;}
  #validation-form input[type=radio],#validation-form input[type=checkbox]{accent-color:#1b6ca8;width:17px;height:17px;vertical-align:-3px;}
  #validation-form .help-block,#validation-form label.error{display:block;color:#d64545 !important;font-size:12px !important;font-weight:600 !important;text-transform:none !important;margin-top:5px !important;}

  /* tables (checklist, file list) */
  .addarticleforminner table{width:100%;border-collapse:separate !important;border-spacing:0;border:1px solid #e4e9ef !important;border-radius:12px;overflow:hidden;margin:6px 0 !important;}
  .addarticleforminner th{background:#eef3f8 !important;color:#14304a !important;font-size:12px !important;text-transform:uppercase;letter-spacing:.5px;font-weight:700 !important;
    padding:12px 14px !important;border:0 !important;border-bottom:2px solid #d5e1ed !important;text-align:left;}
  .addarticleforminner td{padding:11px 14px !important;border:0 !important;border-bottom:1px solid #eef1f5 !important;background:#fff !important;color:#33475b !important;vertical-align:middle;}
  .addarticleforminner tbody tr:nth-child(even) td{background:#f9fbfd !important;}
  .addarticleforminner tbody tr:hover td{background:#eef5fb !important;}
  .addarticleforminner td[colspan]{text-align:center;color:#8a95a1 !important;padding:22px 14px !important;}

  /* buttons at the bottom of the form */
  #validation-form .btn-purple,#validation-form a.btn-purple,#validation-form .btn-success{border-radius:22px !important;padding:8px 20px !important;font-weight:600 !important;border:0 !important;}
  #validation-form .container-fluid.mt-5{margin:26px 0 0 !important;}
  #validation-form .form_btn.cancelbtn button,#cancel-btn{min-width:130px;}
  #submit-btn,#submit-btnform{min-width:150px;}

  /* file browser card */
  .sw-upload{background:#1b6ca8 !important;color:#fff !important;border:0 !important;border-radius:10px !important;padding:10px 18px !important;font-weight:700 !important;transition:background .2s;}
  .sw-upload:hover{background:#14304a !important;}
  .addarticleforminner input[type=file]{border:1px dashed #b9c8d8;border-radius:10px;padding:8px 10px;background:#fbfcfe;}

  /* "File Update Status" panel */
  .modal-contentcss{border-radius:18px !important;border:1px solid #dbe6f1 !important;box-shadow:0 22px 60px rgba(20,48,74,.28) !important;}
  .modal-contentcss .heading{color:#14304a !important;font-size:19px !important;}
  .modal-contentcss .button-row a{border-radius:20px !important;padding:7px 16px !important;font-size:13px !important;}
  .modal-contentcss .button-group .submit,#submit-btnform{background:linear-gradient(120deg,#1b6ca8,#14304a) !important;color:#fff !important;border:0 !important;border-radius:10px !important;padding:11px 26px !important;font-weight:700 !important;}
  .modal-contentcss .radio-group label{text-transform:none !important;font-size:14px !important;color:#22303f !important;font-weight:600 !important;margin:0 !important;}
  .modal .modal-content{border-radius:16px;border:0;box-shadow:0 22px 60px rgba(20,48,74,.3);} .modal-header{background:#f4f8fc;border-bottom:1px solid #e4e9ef;}
</style>
<style id="sw-compact">
  /* ================= compact sizing + motion (overrides sw-theme) ================= */
  html{scroll-behavior:smooth;}
  #validation-form{font-size:13px;}
  .articleheading{font-size:19px !important;}

  /* summary strip */
  .sw-summary{margin:14px auto 0;padding:12px 18px;border-radius:12px;gap:10px 18px;}
  .sw-sum-id{font-size:18px;} .sw-sum-sub{font-size:12.5px;margin-top:0;} .sw-summary small{font-size:10px;letter-spacing:.7px;}
  .sw-sum-chips{gap:8px;} .sw-chip{min-width:92px;padding:6px 11px;border-radius:8px;} .sw-chip b{font-size:12.5px;}
  .sw-chip{animation:swPop .4s cubic-bezier(.2,.9,.3,1.3) backwards;}
  .sw-chip:nth-child(1){animation-delay:.10s;} .sw-chip:nth-child(2){animation-delay:.16s;} .sw-chip:nth-child(3){animation-delay:.22s;}
  .sw-chip:nth-child(4){animation-delay:.28s;} .sw-chip:nth-child(5){animation-delay:.34s;}
  @keyframes swPop{from{opacity:0;transform:scale(.85) translateY(6px);}to{opacity:1;transform:none;}}

  /* required-files card */
  .sw-files{width:min(640px,calc(100% - 36px));margin:12px auto 0;padding:11px 16px;border-radius:12px;border-left-width:5px;font-size:13px;animation:swRise .45s ease .08s backwards;}
  .sw-files-head{gap:11px;} .sw-files-ico{flex-basis:34px;height:34px;line-height:34px;font-size:18px;}
  .sw-files-title{font-size:14.5px;} .sw-files-sub{font-size:12px;margin-top:0;}
  .sw-files ul{margin-top:9px;} .sw-files li{padding:6px 10px;margin-top:4px;border-radius:8px;gap:6px 8px;}
  .sw-mark{width:19px;height:19px;line-height:19px;font-size:11px;} .sw-ext{padding:2px 9px;font-size:12px;} .sw-state{font-size:10.5px;}
  .sw-files.bad li.bad{animation:swShake .5s ease .5s backwards;}
  @keyframes swShake{0%,100%{transform:none;}20%{transform:translateX(-5px);}40%{transform:translateX(5px);}60%{transform:translateX(-3px);}80%{transform:translateX(3px);}}

  /* cards */
  .addarticleforminner{margin:12px auto !important;padding:16px 22px 20px !important;border-radius:14px !important;animation:swRise .5s ease backwards;}
  .addarticleformouter:nth-of-type(1) .addarticleforminner{animation-delay:.16s;}
  .addarticleformouter + .addarticleformouter .addarticleforminner{animation-delay:.26s;}
  .formcont1heading{font-size:14.5px !important;padding:1px 0 10px 14px !important;}
  .formcont1heading::before{width:4px;bottom:11px;}

  /* buttons */
  #validation-form .d-flex.gap-3.justify-content-center{gap:7px !important;margin:2px 0;}
  #validation-form .form_btn .btn:not(.btn-purple):not(.btn-success):not(.btn-orange):not(.btn-danger),
  #validation-form .form_btn a.btn-orange{padding:5px 13px !important;font-size:12px !important;}
  #validation-form #openModal{padding:7px 14px !important;min-width:190px;font-size:12.5px !important;}
  #validation-form .row.addspacing{gap:8px !important;margin:8px 0 !important;}
  #validation-form .btn.btn-primary.col-2{padding:7px 14px !important;font-size:12.5px !important;border-radius:9px !important;}
  #validation-form .btn.btn-purple,#validation-form .btn.btn-success,#validation-form .btn.btn-danger{padding:5px 15px !important;font-size:12px !important;}
  #validation-form .btn,#submit-btn,#cancel-btn,.sw-upload{transition:transform .15s,box-shadow .2s,background .2s,color .2s,border-color .2s;}
  #validation-form .btn:active,#submit-btn:active,#cancel-btn:active,.sw-upload:active{transform:scale(.96) !important;}
  #submit-btn,#submit-btnform{padding:8px 24px !important;min-width:120px;font-size:13px;background-size:200% 100% !important;background-position:0 0;}
  #submit-btn:hover,#submit-btnform:hover{background-position:100% 0;}
  #cancel-btn{padding:8px 22px !important;min-width:100px;font-size:13px;}
  #validation-form .container-fluid.mt-5{margin:14px 0 0 !important;}

  /* form grid */
  #validation-form > .p-5{padding:12px 0 2px !important;gap:12px 16px !important;}
  #validation-form > .p-5 > *{animation:swRise .4s ease backwards;}
  #validation-form > .p-5 > *:nth-child(1){animation-delay:.20s;} #validation-form > .p-5 > *:nth-child(2){animation-delay:.24s;}
  #validation-form > .p-5 > *:nth-child(3){animation-delay:.28s;} #validation-form > .p-5 > *:nth-child(4){animation-delay:.32s;}
  #validation-form > .p-5 > *:nth-child(5){animation-delay:.36s;} #validation-form > .p-5 > *:nth-child(n+6){animation-delay:.40s;}
  #validation-form label{font-size:11px !important;margin:0 0 4px !important;transition:color .2s;}
  #validation-form label span,#validation-form label font,#validation-form label small{font-size:11px;}
  #validation-form > .p-5 > *:focus-within > label{color:#1b6ca8 !important;}
  #validation-form input[type=text],#validation-form input[type=number],#validation-form input[type=email],#validation-form input[type=password],
  #validation-form select,#validation-form .form-select,#validation-form .form-control{height:36px !important;padding:5px 11px !important;font-size:13px !important;border-radius:8px !important;}
  #validation-form textarea{min-height:76px;padding:8px 11px !important;font-size:13px !important;border-radius:8px !important;}

  /* tables */
  .addarticleforminner table{margin:2px 0 !important;border-radius:10px;}
  .addarticleforminner th{padding:7px 11px !important;font-size:11px !important;}
  .addarticleforminner td{padding:7px 11px !important;font-size:12.5px;}
  .addarticleforminner tbody tr{animation:swRowIn .35s ease backwards;}
  .addarticleforminner tbody tr:nth-child(2){animation-delay:.04s;} .addarticleforminner tbody tr:nth-child(3){animation-delay:.08s;}
  .addarticleforminner tbody tr:nth-child(4){animation-delay:.12s;} .addarticleforminner tbody tr:nth-child(n+5){animation-delay:.16s;}
  .addarticleforminner tbody tr td:first-child{box-shadow:inset 0 0 0 #1b6ca8;transition:box-shadow .2s;}
  .addarticleforminner tbody tr:hover td:first-child{box-shadow:inset 3px 0 0 #1b6ca8;}
  @keyframes swRowIn{from{opacity:0;transform:translateX(-8px);}to{opacity:1;transform:none;}}
  .addarticleforminner td[colspan]{padding:14px 11px !important;}

  /* ===== file browser card ===== */
  .formcont1heading.mb-2[style*="left"]{left:0 !important;padding:0 0 8px 0 !important;border:0 !important;margin:0 0 6px !important;}
  .formcont1heading.mb-2[style*="left"]::before{display:none;}
  .formcont1heading.mb-2 p{display:inline-flex;align-items:center;gap:8px;width:auto !important;max-width:100%;margin:0;padding:6px 12px;border-radius:8px;
    background:#f3f7fb;border:1px solid #dbe6f1;color:#33475b;font:600 12px/1.4 Consolas,'Courier New',monospace;letter-spacing:0;word-break:break-all;}
  .formcont1heading.mb-2 p::before{content:"\f07b";font-family:FontAwesome;color:#e8742a;font-weight:400;font-size:14px;}
  .addarticleforminner .row{display:flex;flex-wrap:wrap;align-items:center;justify-content:flex-end;gap:8px;margin:0 !important;}
  .addarticleforminner .row > .col-12.col-md-6{width:auto !important;flex:0 1 auto;padding:0 !important;}
  .addarticleforminner .row form{display:flex;flex-wrap:nowrap;align-items:center;gap:8px;margin:0;}
  .addarticleforminner thead th::before,.addarticleforminner thead th::after{display:none !important;}
  .addarticleforminner table.dataTable thead th{background-repeat:no-repeat !important;background-position:right 8px center !important;background-size:9px !important;padding-right:24px !important;}
  .addarticleforminner input[type=file]{font-size:12px;padding:4px 8px;max-width:280px;border-radius:8px;transition:border-color .2s,background .2s;}
  .addarticleforminner input[type=file]:hover{border-color:#1b6ca8;background:#f3f8fd;}
  .addarticleforminner input[type=file]::file-selector-button{margin-right:8px;padding:4px 10px;border:0;border-radius:6px;background:#eaf2fa;color:#1b6ca8;font-weight:600;cursor:pointer;transition:background .2s;}
  .addarticleforminner input[type=file]::file-selector-button:hover{background:#1b6ca8;color:#fff;}
  .sw-upload{padding:6px 14px !important;font-size:12.5px !important;margin:0 !important;white-space:nowrap;width:auto !important;}
  .addarticleforminner .main.pb-5{padding-bottom:0 !important;}
  .addarticleforminner .bottom-content{overflow:auto !important;max-height:300px;margin-top:6px;border-radius:10px;}
  .addarticleforminner .bottom-content::-webkit-scrollbar{width:8px;height:8px;} .addarticleforminner .bottom-content::-webkit-scrollbar-thumb{background:#c9d6e3;border-radius:8px;}

  /* panel + modal */
  .modal-contentcss .heading{font-size:16px !important;}
  .modal-contentcss .button-row a{padding:5px 12px !important;font-size:12px !important;}
  .modal-contentcss .button-group .submit,#submit-btnform{padding:8px 20px !important;}


  /* input + button groups (e.g. "Fetch Author Details") stay on one line */
  #validation-form .input-group{display:flex !important;flex-wrap:nowrap !important;align-items:center;gap:8px;width:100%;}
  #validation-form .input-group > input,#validation-form .input-group > .form-control{flex:1 1 auto !important;width:auto !important;min-width:0;}
  #validation-form .input-group > .btn{flex:0 0 auto;margin:0 !important;height:36px;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;
    padding:0 14px !important;font-size:12.5px !important;font-weight:600 !important;border-radius:8px !important;}
  #validation-form .input-group > .btn i{margin:0;font-size:14px;vertical-align:middle;}
  #validation-form > .p-5 > .sw-span6{grid-column:span 6 !important;}
  @media (max-width:991px){ #validation-form > .p-5 > .sw-span6{grid-column:1 / -1 !important;} }
  /* page header: back button + title + breadcrumb in one tidy bar */
  .sw-bar{padding:8px 0 !important;background:#f3f6fa !important;border:0 !important;box-shadow:none !important;}
  .sw-bar > .row{display:none !important;}
  .sw-bar-in{display:flex;align-items:center;gap:14px;width:min(1180px,calc(100% - 36px));margin:0 auto;padding:10px 18px;background:#fff;border:1px solid #e4e9ef;border-radius:14px;
    box-shadow:0 4px 18px rgba(20,48,74,.07);animation:swRise .4s ease backwards;}
  .sw-back{flex:0 0 auto;width:36px;height:36px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:#fff;border:1px solid #d9e2ec;color:#14304a;
    text-decoration:none !important;box-shadow:0 2px 6px rgba(20,48,74,.08);transition:all .2s;}
  .sw-back:hover{background:#e8742a;border-color:#e8742a;color:#fff;transform:translateX(-2px);box-shadow:0 4px 10px rgba(232,116,42,.35);}
  .sw-back i{font-size:15px;}
  .sw-legacy{margin-right:12px;padding:5px 11px;border:1px solid #d9e2ec;border-radius:999px;background:#fff;color:#5b6b7b !important;font-size:12px;font-weight:600;text-decoration:none !important;white-space:nowrap;transition:all .2s;}
  .sw-legacy:hover{background:#fff7f1;border-color:#f1c4a6;color:#c9591a !important;}
  .sw-legacy i{margin-right:5px;}
  .sw-att{margin:12px 0 0;padding:12px 14px;border:1.5px dashed #c9d6e3;border-radius:12px;background:#f8fafc;text-align:left;transition:all .2s;}
  .sw-att.over{border-color:#1b6ca8;background:#eef6fc;}
  .sw-att-btn{display:inline-flex;align-items:center;gap:7px;margin:0;padding:7px 14px;border-radius:9px;background:#fff;border:1px solid #cfd8e3;color:#14304a;font-size:13px;font-weight:600;cursor:pointer;transition:all .15s;}
  .sw-att-btn:hover{border-color:#1b6ca8;color:#1b6ca8;box-shadow:0 3px 8px rgba(27,108,168,.15);}
  .sw-att-btn input{display:none;}
  .sw-att-hint{margin-left:10px;color:#6b7785;font-size:12.5px;}
  .sw-att-count{float:right;color:#1e9e61;font-size:12px;font-weight:700;}
  .sw-att-lim{margin-top:6px;color:#8b97a4;font-size:11.5px;}
  .sw-att-list{list-style:none;margin:8px 0 0;padding:0;}
  .sw-att-list li{display:flex;align-items:center;gap:8px;margin:0 0 6px;padding:6px 8px;background:#fff;border:1px solid #e1e8ef;border-radius:8px;font-size:12.5px;animation:swItem .3s ease backwards;}
  .sw-att-list li i{color:#1b6ca8;} .sw-att-list .n{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#2f4357;} .sw-att-list .z{color:#8b97a4;font-size:11.5px;}
  .sw-att-list .x{border:0;background:none;color:#b13434;font-size:18px;line-height:1;cursor:pointer;padding:0 4px;} .sw-att-list .x:hover{color:#d64545;}
  .sw-legacy.sw-hl{background:#fff7f1;border-color:#e8742a;color:#c9591a !important;animation:swHl 1.3s ease-in-out infinite;}
  @keyframes swHl{0%{box-shadow:0 0 0 0 rgba(232,116,42,.55);transform:scale(1);}50%{box-shadow:0 0 0 9px rgba(232,116,42,0);transform:scale(1.07);}100%{box-shadow:0 0 0 0 rgba(232,116,42,0);transform:scale(1);}}
  .sw-bar-title{font-size:18px;font-weight:700;color:#14304a;line-height:1.2;margin:0;}
  .sw-bar-crumb{font-size:11.5px;color:#7a8898;margin-top:2px;} .sw-bar-crumb a{color:#1b6ca8;text-decoration:none;} .sw-bar-crumb a:hover{text-decoration:underline;}
  .sw-bar-sp{flex:1 1 auto;}
  .sw-bar-id{font-size:12px;font-weight:700;color:#14304a;background:#eef3f8;border:1px solid #dbe6f1;border-radius:20px;padding:4px 12px;}
  /* ONE common box: header + article summary + required-files notice */
  .sw-hero{width:min(1180px,calc(100% - 36px));margin:14px auto 0;background:#fff;border:1px solid #e0e8f0;border-radius:16px;overflow:hidden;
    box-shadow:0 6px 22px rgba(20,48,74,.09);animation:swRise .45s ease backwards;}
  .sw-hero > * + *{border-top:1px solid #e8eef4;}
  .sw-bar{display:none !important;}
  .sw-hero .sw-bar-in{width:auto;margin:0;padding:11px 20px;background:#fff;border:0;border-radius:0;box-shadow:none;animation:none;}
  .sw-hero .sw-summary{width:auto;margin:0;padding:12px 20px;border:0;border-radius:0;box-shadow:none;animation:none;
    background:linear-gradient(120deg,#f7fbff 0%,#edf4fb 100%);}
  .sw-hero .sw-files{width:auto;max-width:none;margin:0;padding:10px 20px;border:0;border-left:5px solid;border-radius:0;box-shadow:none;animation:swSlide .4s ease backwards;}
  .sw-hero .sw-files.ok{background:#f1fbf5;border-left-color:#1e9e61;} .sw-hero .sw-files.bad{background:#fff5f5;border-left-color:#d64545;} .sw-hero .sw-files.warn{background:#fff8e6;border-left-color:#d98a06;}
  .sw-hero .sw-files ul{max-width:760px;}
  @keyframes swSlide{from{opacity:0;max-height:0;}to{opacity:1;max-height:400px;}}
  /* tool buttons (3D to HTML, package creation, ePub ...): one loader for all of them */
  [id^="ajaxLoader"],#overlay{display:none !important;}   /* the old per-button loaders are replaced by the common one below */
  .sw-tool-busy{pointer-events:none;opacity:.8;position:relative;}
  .sw-tool-busy::before{content:"";display:inline-block;width:12px;height:12px;margin-right:7px;border:2px solid currentColor;border-top-color:transparent;
    border-radius:50%;animation:swSpin .7s linear infinite;vertical-align:-2px;}
  /* radio rows must stay on one line (my label rule made them block) */
  .modal-contentcss .radio-group,.modal-contentcss .formgroup1{display:flex;flex-wrap:wrap;align-items:center;gap:6px 18px;}
  .modal-contentcss .radio-group label,#validation-form .radio-group label,#validation-form label.form-check-label{display:inline-flex !important;align-items:center;gap:7px;margin:0 !important;
    text-transform:none !important;letter-spacing:0 !important;font-size:13px !important;color:#22303f !important;font-weight:600 !important;cursor:pointer;}
  .modal-contentcss .radio-group input[type=radio]{margin:0;}
  .modal-contentcss .formgroup1{flex-direction:column !important;align-items:flex-start !important;gap:4px !important;margin:10px 0 4px !important;height:auto !important;min-height:0 !important;}
  .modal-contentcss .sw-status-err{margin:0 !important;flex:0 0 auto;}
  #validation-form .modal .btn-close,.modal .btn-close{width:30px !important;min-width:0 !important;height:30px !important;padding:0 !important;border:0 !important;border-radius:50% !important;
    background-color:#eef3f8 !important;opacity:1 !important;transition:background .2s,transform .2s;}
  .modal .btn-close:hover{background-color:#dfe8f1 !important;transform:rotate(90deg);}
  /* dialogs always above the page content */
  .modal{z-index:2050 !important;} .modal-backdrop{z-index:2040 !important;}
  @media (prefers-reduced-motion:reduce){ *{animation:none !important;transition:none !important;} html{scroll-behavior:auto;} }
</style>
<style id="vp-sw-files">
  .sw-files{width:min(760px,calc(100% - 36px));margin:22px auto 6px;padding:18px 24px;border-radius:14px;border:1px solid;border-left-width:7px;
    box-shadow:0 8px 24px rgba(20,48,74,.12);font-size:15px;animation:swFilesIn .4s ease;}
  .sw-files.ok{background:#effaf4;border-color:#bfe5cf;border-left-color:#1e9e61;}
  .sw-files.bad{background:#fff3f3;border-color:#f3c4c4;border-left-color:#d64545;}
  .sw-files.warn{background:#fffaf0;border-color:#f3dfae;border-left-color:#d98a06;}
  .sw-mf-link{display:inline-flex;align-items:center;gap:7px;margin-top:9px;padding:7px 16px;border-radius:9px;background:#d98a06;color:#fff !important;font-weight:700;font-size:13px;text-decoration:none !important;box-shadow:0 4px 10px rgba(217,138,6,.3);transition:filter .15s,transform .15s;}
  .sw-mf-link:hover{filter:brightness(1.08);transform:translateY(-1px);}
  .sw-files-head{display:flex;align-items:center;gap:16px;}
  .sw-files-ico{flex:0 0 46px;height:46px;line-height:46px;border-radius:50%;text-align:center;font-size:24px;font-weight:700;color:#fff;}
  .sw-files.warn .sw-files-ico{background:#d98a06;} .sw-files.ok .sw-files-ico{background:#1e9e61;} .sw-files.bad .sw-files-ico{background:#d64545;animation:swPulse 1.6s ease-in-out 2;}
  .sw-files-title{font-size:18px;font-weight:700;color:#14304a;} .sw-files-sub{color:#4a5b6b;margin-top:2px;}

  .sw-recheck{align-self:flex-start;flex:none;padding:6px 14px;border-radius:99px;border:1px solid #d64545;color:#b83232;font-weight:700;font-size:12.5px;text-decoration:none !important;background:#fff;}
  .sw-recheck:hover{background:#d64545;color:#fff;} .sw-recheck.busy i{animation:swSpin .7s linear infinite;} @keyframes swSpin{to{transform:rotate(360deg)}}
  .sw-dbg{margin-top:8px;padding:8px 12px;border-radius:8px;background:rgba(255,255,255,.7);border:1px dashed #e6b3b3;font-size:12px;color:#4a5b6b;word-break:break-all;}
  .sw-dbg code{background:#f6e3e3;color:#8a2b2b;padding:1px 6px;border-radius:5px;font-size:11.5px;}
  .sw-files ul{list-style:none;margin:14px 0 0;padding:0;}
  .sw-files li{display:flex;flex-wrap:wrap;align-items:center;gap:8px 10px;padding:10px 12px;margin-top:6px;border-radius:10px;background:#fff;border:1px solid #e4e9ef;}
  .sw-mark{width:24px;height:24px;line-height:24px;border-radius:50%;text-align:center;font-weight:700;font-size:14px;color:#fff;}
  .sw-files li.ok .sw-mark{background:#1e9e61;} .sw-files li.bad .sw-mark{background:#d64545;}
  .sw-ext{padding:3px 11px;border-radius:20px;background:#eaf2fa;color:#1b6ca8;font-weight:700;font-size:13px;}
  .sw-rule{display:inline-block;margin:2px 8px 2px 0;padding:2px 9px;border-radius:12px;background:#fff7e8;border:1px solid #f2dcae;color:#4a3a14;font-size:12px;}
  .sw-state{margin-left:auto;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.5px;}
  .sw-files li.ok .sw-state{color:#1e9e61;} .sw-files li.bad .sw-state{color:#d64545;}
  @keyframes swFilesIn{from{opacity:0;transform:translateY(-10px);}to{opacity:1;transform:none;}}
  @keyframes swPulse{0%,100%{box-shadow:0 0 0 0 rgba(214,69,69,.5);}50%{box-shadow:0 0 0 12px rgba(214,69,69,0);}}
</style>
<style id="vp-sw-extra">
  .vp-for{color:#6b7785;font-size:12.5px;} .sw-files li .sw-state{white-space:nowrap;}
  .vp-up{margin-top:12px;padding:10px 14px;border-radius:10px;background:rgba(255,255,255,.75);border:1px dashed #cfd8e3;} .vp-up-h{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#5b6b7b;margin-bottom:4px;}
  .vp-up ul{margin:0 !important;} .vp-up li{display:flex;align-items:center;gap:10px;padding:5px 8px !important;margin:2px 0 !important;background:transparent !important;border:0 !important;font-size:13px;} .vp-up li i{color:#1b6ca8;} .vp-up li .n{flex:1;word-break:break-all;} .vp-up li .z{color:#8a95a1;font-size:12px;} .vp-up li.more{color:#6b7785;font-style:italic;}
  .addarticleformouter.container,.addarticleformouter{max-width:none !important;width:100% !important;padding:0 !important;margin:0 !important;}
  .addarticleformouter .addarticleforminner{margin-left:auto !important;margin-right:auto !important;}
  .sw-hide-info{display:none !important;}
  #validation-form .container-fluid.d-flex > .row.d-flex{flex-wrap:nowrap !important;gap:12px !important;justify-content:center;width:auto !important;margin:0 !important;}
  #validation-form .form_btn.cancelbtn,#validation-form .form_btn.nxtbtn{flex:0 0 auto !important;width:auto !important;max-width:none !important;padding:0 !important;}
  /* ================= vendor portal additions ================= */
  .container-fluid:has(.articleheading){display:none !important;}                /* the old sticky title bar is replaced by the hero */
  .sw-hero .nav-clock{display:inline-flex !important;align-items:center;gap:10px;margin:0 !important;padding:6px 16px !important;border-radius:999px !important;background:#fff7f1 !important;border:1px solid #f6d3bb !important;box-shadow:none !important;position:static !important;width:auto !important;}
  .sw-hero .nav-clock p{margin:0 !important;font-size:12px !important;font-weight:700;color:#d2631d !important;font-family:inherit !important;}
  .sw-hero .nav-clock .time{display:inline-flex;gap:2px;font-weight:700;color:#14304a;font-variant-numeric:tabular-nums;}
  .sw-hero .nav-clock .time span:not(:last-child):after{content:" :";}
  .col-12.d-flex.d-lg-none:has(.nav-clock){display:none !important;}
  /* cards: the two old "addarticleformouter" blocks */
  .addarticleformouter .widget-body{margin:12px 0 4px !important;padding:0 !important;}
  .addarticleforminner .formcont1heading{left:0 !important;top:0 !important;}
  .addarticleforminner .formcont1heading h5{font-size:16px !important;font-weight:700;color:#14304a;margin:0 !important;}
  .addarticleforminner .formcont1heading h5 i{color:#1b6ca8;margin-right:8px;}
  .addarticleforminner table td{width:auto;} .addarticleforminner table td:first-child{width:220px;font-weight:600;color:#5b6b7b !important;background:#f9fbfd !important;}
  .addarticleforminner a{color:#1b6ca8;font-weight:600;text-decoration:none;} .addarticleforminner a:hover{color:#14304a;text-decoration:underline;}
  /* upload zone */
  .addarticleforminner .dropzone,#dropzone{border:2px dashed #b9c8d8 !important;border-radius:14px !important;background:#fbfcfe !important;padding:44px 0 !important;min-height:0 !important;transition:border-color .2s,background .2s;}
  #dropzone.dz-drag-hover{border-color:#e8742a !important;background:#fff7f1 !important;}
  #dropzone .dz-message{margin:0 !important;color:#5b6b7b;} #dropzone .dz-message .bigger-150{font-size:16px;font-weight:700;color:#14304a;}
  #dropzone .dz-message i.red{color:#e8742a;} #dropzone .upload-icon{display:block;font-size:34px;margin-top:10px;color:#1b6ca8;}
  /* the status form */
  #validation-form > .p-5{display:grid !important;grid-template-columns:repeat(12,minmax(0,1fr));gap:18px 22px !important;padding:16px 0 0 !important;}
  #validation-form > .p-5 > *{grid-column:1 / -1;min-width:0;}
  #validation-form > .p-5 > [class*="col-"]:not(.col-xs-12){grid-column:span 4;max-width:none;width:auto;padding:0;}
  #validation-form > .p-5 > .col-lg-12,#validation-form > .p-5 > #checklist_table{grid-column:1 / -1 !important;}
  #validation-form .space-2{display:none;}
  #validation-form .form_btn button[type=reset],#validation-form .form_btn button:not(#btn_submit){height:46px;min-width:130px;border-radius:10px;background:#fff;color:#33475b;border:1px solid #cfd8e3;font-weight:600;}
  #validation-form .form_btn #btn_submit.btn,#validation-form #btn_submit{height:46px !important;width:auto !important;min-width:150px;padding:0 34px !important;border:0 !important;border-radius:10px !important;font-weight:700 !important;font-size:14px !important;color:#fff !important;background:linear-gradient(120deg,#1b6ca8,#14304a) !important;box-shadow:0 6px 14px rgba(20,48,74,.25) !important;transition:transform .15s,box-shadow .15s;}
  #validation-form .form_btn #btn_submit.btn:hover{transform:translateY(-1px);box-shadow:0 9px 18px rgba(20,48,74,.3) !important;color:#fff !important;background:linear-gradient(120deg,#1b6ca8,#14304a) !important;}
  #validation-form .form_btn #btn_submit.btn:disabled{opacity:.6;cursor:wait;transform:none;}
  #validation-form .form_btn button[type=reset]{height:46px !important;width:auto !important;min-width:130px;padding:0 28px !important;border-radius:10px !important;background:#fff !important;color:#33475b !important;border:1px solid #cfd8e3 !important;font-weight:600 !important;}
  #checklist_table h3.formcont1heading{left:0 !important;}
  .modal .modal-content{border-radius:16px;border:0;box-shadow:0 22px 60px rgba(20,48,74,.3);} .modal-header{background:#f4f8fc;border-bottom:1px solid #e4e9ef;}
</style>
<script>
(function ($) {
  if (!$) { return; }
  var SUM = <?php echo json_encode($sum, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
  function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
  function chip(label, val, hot) { return '<div class="sw-chip' + (hot ? ' hot' : '') + '"><small>' + label + '</small><b>' + esc(val || '-') + '</b></div>'; }
  $(function () {
    var $first = $('.addarticleformouter').first(); if (!$first.length || $('.sw-hero').length) { return; }
    var $clock = $('.nav-clock.startnav-clock').first();
    /* the article summary is in the hero now: hide the old "Article Information" table */
    $('.addarticleforminner h5.widget-title').filter(function () { return /Article Information/i.test($(this).text()); }).closest('.col-md-12').addClass('sw-hide-info');
    var $hero = $('<div class="sw-hero"></div>').insertBefore($first);
    var $bar = $('<div class="sw-bar-in"><a class="sw-back" href="' + esc(SUM.back || 'allocatedarticles') + '" title="Back to Allocated Articles"><i class="fa fa-arrow-left"></i></a>' +
      '<div><h1 class="sw-bar-title">Article Details</h1><div class="sw-bar-crumb">Allocated Articles &rsaquo; Start Work</div></div><span class="sw-bar-sp"></span>' + (SUM.old ? '<a href="' + esc(SUM.old) + '" class="sw-legacy" title="Go back to the old Start Work page"><i class="fa fa-undo"></i> Use old version</a>' : '') + '<span class="sw-bar-id">#' + esc(SUM.id) + '</span></div>');
    $hero.append($bar);
    if ($clock.length) { $clock.detach().insertBefore($bar.find('.sw-legacy, .sw-bar-id').first()); }          /* keep the same element: the page's own script updates it */
    $hero.append('<div class="sw-summary"><div><small>Article ID</small><div class="sw-sum-id">#' + esc(SUM.id) + '</div>' +
      '<div class="sw-sum-sub">' + esc(SUM.client) + ' &nbsp;&middot;&nbsp; ' + esc(SUM.journal) + '</div></div>' +
      '<div class="sw-sum-chips">' + chip('Stage', SUM.stage) + chip('Department', SUM.dept) + chip('Received', SUM.recv) + chip('Due date', SUM.due, true) + '</div></div>');
    /* "Save" shows it is working */
    $('#validation-form').on('submit', function () { var $b = $('#btn_submit'); setTimeout(function () { if ($('#validation-form').valid === undefined || $('#validation-form').valid()) { $b.prop('disabled', true).text('Saving…'); } }, 0); });
  });
})(window.jQuery);
</script>
<style id="vp-sw-dlg">
  #sw-overlay{position:fixed;inset:0;z-index:99998;display:none;align-items:center;justify-content:center;flex-direction:column;background:rgba(255,255,255,.82);backdrop-filter:blur(2px);}
  #sw-overlay.on{display:flex;animation:swFade .2s ease;}
  #sw-overlay .sw-ring{width:60px;height:60px;border-radius:50%;border:5px solid #e9eef4;border-top-color:#e8742a;border-right-color:#1b6ca8;animation:swSpin .85s linear infinite;margin-bottom:16px;}
  #sw-overlay .sw-title{font-size:17px;font-weight:700;color:#14304a;} #sw-overlay .sw-sub{color:#6b7785;margin-top:3px;}
  #sw-bar{position:fixed;top:0;left:0;height:4px;width:0;z-index:99999;opacity:0;background:linear-gradient(90deg,#e8742a,#f5a35c 60%,#1b6ca8);box-shadow:0 0 12px rgba(232,116,42,.65);transition:width .25s ease,opacity .4s ease;} #sw-bar.on{opacity:1;}
  @keyframes swSpin{to{transform:rotate(360deg);}} @keyframes swFade{from{opacity:0;}to{opacity:1;}}
  #vp-dlg{position:fixed;inset:0;z-index:99997;display:none;align-items:center;justify-content:center;background:rgba(15,32,52,.5);backdrop-filter:blur(3px);}
  #vp-dlg.on{display:flex;}
  #vp-dlg .b{width:min(480px,calc(100% - 32px));background:#fff;border-radius:18px;box-shadow:0 28px 70px rgba(15,35,60,.35);overflow:hidden;animation:swFade .2s ease;}
  #vp-dlg .b:before{content:"";display:block;height:6px;background:linear-gradient(90deg,#d64545,#f0a0a0);}
  #vp-dlg.warn .b:before{background:linear-gradient(90deg,#e8932a,#f5c27a);} #vp-dlg.ok .b:before{background:linear-gradient(90deg,#1e9e61,#7bd3a6);}
  #vp-dlg .in{padding:24px 26px 20px;text-align:center;}
  #vp-dlg .ic{width:54px;height:54px;border-radius:50%;margin:0 auto 12px;display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:700;background:#fdeaea;color:#d64545;}
  #vp-dlg.warn .ic{background:#fff3e0;color:#e8932a;} #vp-dlg.ok .ic{background:#e5f6ed;color:#1e9e61;}
  #vp-dlg h3{margin:0 0 6px;font-size:19px;font-weight:700;color:#14304a;} #vp-dlg p{margin:0 0 12px;color:#5b6b7b;font-size:14px;}
  #vp-dlg ul{list-style:none;margin:0 0 14px;padding:0;text-align:left;max-height:240px;overflow:auto;}
  #vp-dlg li{padding:9px 12px;margin:5px 0;border-radius:10px;background:#fff8f8;border:1px solid #f3d4d4;border-left:4px solid #d64545;color:#33475b;font-size:13.5px;cursor:pointer;}
  #vp-dlg.warn li{background:#fffaf0;border-color:#f3dfae;border-left-color:#e8932a;} #vp-dlg li:hover{background:#fff0f0;}
  #vp-dlg button{height:42px;padding:0 30px;border:0;border-radius:10px;font-weight:700;color:#fff;background:linear-gradient(120deg,#1b6ca8,#14304a);cursor:pointer;}
  .vp-bad{border-color:#d64545 !important;background:#fff8f8 !important;}
</style>
<div id="sw-bar"></div>
<div id="sw-overlay"><div class="sw-ring"></div><div class="sw-title" id="sw-title">Saving&hellip;</div><div class="sw-sub">Please wait, do not close this tab</div></div>
<div id="vp-dlg" role="alertdialog" aria-modal="true"><div class="b"><div class="in"><div class="ic">!</div><h3 id="vp-dt"></h3><p id="vp-ds"></p><ul id="vp-dl"></ul><button type="button" id="vp-dok">OK</button></div></div></div>
<script>
/* validation + required-files check + saving loader (same behaviour as the main app's Start Work v2) */
(function ($) {
  if (!$) { return; }
  var SUM2 = <?php echo json_encode($sum, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
  var busy = false, barTimer = null, barVal = 0;
  function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
  function bar(v) { barVal = v; $('#sw-bar').css('width', v + '%'); }
  function loaderOn(text) { $('#sw-title').text(text || 'Saving…'); $('#sw-overlay').addClass('on'); clearInterval(barTimer); $('#sw-bar').addClass('on'); bar(6); barTimer = setInterval(function () { bar(barVal + (92 - barVal) * 0.06); }, 200); }
  function loaderOff() { clearInterval(barTimer); bar(100); $('#sw-overlay').removeClass('on'); setTimeout(function () { $('#sw-bar').removeClass('on'); setTimeout(function () { bar(0); }, 450); }, 250); }
  function dlg(kind, title, sub, items) {
    var $d = $('#vp-dlg').removeClass('warn ok').addClass(kind === 'warn' ? 'warn' : (kind === 'ok' ? 'ok' : '')).addClass('on');
    $d.find('.ic').text(kind === 'ok' ? '✓' : '!'); $('#vp-dt').text(title); $('#vp-ds').text(sub || '');
    var $l = $('#vp-dl').empty();
    $.each(items || [], function (i, it) { $('<li></li>').text(it.text).on('click', function () { $d.removeClass('on'); if (it.el) { it.el.focus(); $('html,body').animate({ scrollTop: $(it.el).offset().top - 140 }, 200); } }).appendTo($l); });
  }
  $('#vp-dok').on('click', function () { $('#vp-dlg').removeClass('on'); });
  $(document).on('keydown', function (e) { if (e.key === 'Escape') { $('#vp-dlg').removeClass('on'); } });

  function isCompleted() { return String($('#status').val()) === '1'; }
  function checkFiles() { return $.ajax({ url: location.href.split('#')[0], type: 'POST', dataType: 'json', cache: false, global: false, data: { sw_check_files: 1 } }); }

  $(function () {
    var $hero = $('.sw-hero').first();

    /* ---- the "files are ready for the next department" banner ---- */
    function sizeTxt(b) { b = +b || 0; return b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB'; }
    function uploadedHtml(r) {
      var up = (r && r.uploaded) || [], tot = (r && r.uploaded_total) || 0;
      if (!up.length) { return '<div class="vp-up"><div class="vp-up-h">No file uploaded yet</div></div>'; }
      var li = $.map(up.slice(0, 8), function (f) { return '<li><i class="fa fa-file-o"></i><span class="n">' + esc(f.name) + '</span><span class="z">' + sizeTxt(f.size) + '</span></li>'; }).join('');
      return '<div class="vp-up"><div class="vp-up-h">Uploaded so far (' + tot + ')</div><ul>' + li + (tot > 8 ? '<li class="more">+ ' + (tot - 8) + ' more</li>' : '') + '</ul></div>';
    }
    /* what the vendor still has to upload, one line per set of file types (no server folders or drive letters are shown) */
    function needs(r) {
      var map = {}, order = [];
      $.each(r.checked || [], function (i, c) {
        var k = c.exts.join('|');
        if (!map[k]) { map[k] = { exts: c.exts, dests: [], found: false, rules: c.rules || {} }; order.push(k); }
        map[k].dests.push(c.dest); if (c.found) { map[k].found = true; }
      });
      return $.map(order, function (k) { return map[k]; });
    }
    function renderFiles(r) {
      $('#sw-files').remove();
      var reqs = needs(r || {});
      if (!r || !reqs.length) { return; }                       /* nothing is required for this stage */
      var miss = (r.problems || []).length, items = '';
      $.each(reqs, function (i, q) {
        var exts = $.map(q.exts, function (e) { return '<span class="sw-ext">.' + esc(e) + '</span>'; }).join('');
        items += '<li class="' + (q.found ? 'ok' : 'bad') + '"><span class="sw-mark">' + (q.found ? '&#10003;' : '&#10007;') + '</span>' +
                 '<span class="sw-what">Upload one of</span> ' + exts + $.map(q.rules || {}, function (p, e) { return '<span class="sw-rule">.' + esc(e) + ' named <b>' + esc(p) + '</b></span>'; }).join('') + '<span class="vp-for">needed for ' + esc(q.dests.join(', ')) + '</span>' +
                 '<span class="sw-state">' + (q.found ? 'uploaded' : 'not uploaded') + '</span></li>';
      });
      $hero.append('<div id="sw-files" class="sw-files ' + (miss ? 'bad' : 'ok') + '" role="alert"><div class="sw-files-head"><span class="sw-files-ico">' + (miss ? '!' : '&#10003;') + '</span><div style="flex:1">' +
        '<div class="sw-files-title">' + (miss ? 'Please upload the required files' : 'Required files are uploaded') + '</div>' +
        '<div class="sw-files-sub">' + (miss ? 'You can submit as <b>Completed</b> only after these files are uploaded with the <b>Drop files</b> box below.' : 'Everything the next department needs is there. You can submit as <b>Completed</b>.') + '</div></div>' +
        '<a href="#dropzone" class="sw-recheck" style="' + (miss ? '' : 'border-color:#1e9e61;color:#1e9e61;') + '"><i class="fa fa-refresh"></i> Re-check</a></div><ul>' + items + '</ul>' + uploadedHtml(r) + '</div>');
      $('#sw-files .sw-recheck').on('click', function (e) { e.preventDefault(); var $a = $(this).addClass('busy'); runCheck().always(function () { $a.removeClass('busy'); }); });
    }
    function runCheck() { return checkFiles().done(renderFiles); }
    $(document).ajaxComplete(function (e, x, s) { if (s && s.url && /do_upload_offline_process/.test(s.url)) { setTimeout(runCheck, 600); } });      /* a file was removed */
    setTimeout(function () { var dz = document.querySelector('#dropzone') && document.querySelector('#dropzone').dropzone; if (dz && dz.on) { dz.on('success', function () { setTimeout(runCheck, 700); }); dz.on('queuecomplete', function () { setTimeout(runCheck, 700); }); } }, 800);
    if ($hero.length) { runCheck(); $(window).on('focus', function () { if ($('#sw-files').hasClass('bad') || $('#sw-files').hasClass('warn')) { runCheck(); } }); }

    /* ---- mandatory fields: ONE popup listing every problem ---- */
    setTimeout(function () {
      var $f = $('#validation-form'), v = $f.data('validator'); if (!v || !$.fn.validate) { return; }
      if ($('#status').length) { $('#status').rules('add', { required: true, messages: { required: 'Please select a status.' } }); }
      var $wc = $('input[name=vendor_wc]'); if ($wc.length) { $wc.rules('add', { required: true, messages: { required: 'Please enter the word count.' } }); }
      var $rm = $('textarea[name=comments]'); if ($rm.length) { $rm.rules('add', { required: true, messages: { required: 'Please enter your remarks.' } }); }
      $f.on('invalid-form.validate', function (e, validator) {
        var lines = [];
        $.each(validator.errorList, function (i, er) {
          var $el = $(er.element), label = $.trim($el.closest('[class*="col-"]').find('label').first().text()).replace(/[:*]+$/, '').replace(/\s+/g, ' ');
          lines.push({ text: (label ? label + ' – ' : '') + er.message, el: er.element });
          $el.addClass('vp-bad');
        });
        $('#btn_submit').prop('disabled', false);
        dlg('warn', 'Please complete the required fields', 'Nothing was saved. Click an item to go to the field.', lines);
      });
      $f.on('input change', 'input,select,textarea', function () { $(this).removeClass('vp-bad'); });

      /* ---- submit: Completed -> check the files first, then the page's own handler (kept), with the loader ---- */
      var orig = v.settings.submitHandler;
      v.settings.submitHandler = function (form) {
        if (busy) { return false; }
        busy = true; $('#btn_submit').prop('disabled', true);
        function go() { loaderOn('Saving work status…'); if (orig) { orig.call(v, form); } else { form.submit(); } }
        if (isCompleted()) {
          loaderOn('Checking required files…');
          checkFiles().done(function (r) {
            if (r && r.problems && r.problems.length) {
              loaderOff(); busy = false; $('#btn_submit').prop('disabled', false); renderFiles(r);
              var q = needs(r), lines = $.map(q, function (x) { return x.found ? null : { text: 'Upload one of: .' + x.exts.join(' / .') + '  (needed for ' + x.dests.join(', ') + ')' }; });
              dlg('error', 'Please upload the required files', 'Use the Drop files box on this page, then submit again.', lines.length ? lines : $.map(r.problems, function (t) { return { text: t }; }));
              return;
            }
            go();
          }).fail(function () { go(); });      /* the server repeats the check, so a failed pre-check is not fatal */
        } else { go(); }
        return false;
      };
    }, 400);
  });
})(window.jQuery);
</script>
<?php
}
