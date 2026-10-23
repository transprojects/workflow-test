<?php
/* Shared design layer for the Edit Article / Add Article pages (same look as Start Work). Echo ea_ui_css() inside <head>. */
function ea_ui_css()
{
?>
<style id="ea-theme">
  /* ===== Edit Article - same look as Start Work (design layer only; fields, names and scripts are untouched) ===== */
  body{background:#f3f6fa !important;}
  .container-fluid:has(> .row > .col-8 > .articleheading){display:none;}   /* the old title bar is replaced by the header box below */

  /* header box */
  .ea-hero{width:min(1180px,calc(100% - 28px));margin:14px auto 0;background:#fff;border:1px solid #e0e8f0;border-radius:16px;overflow:hidden;box-shadow:0 6px 22px rgba(20,48,74,.08);animation:eaRise .45s ease;}
  .ea-hero > * + *{border-top:1px solid #e8eef4;}
  .ea-bar{display:flex;align-items:center;gap:14px;padding:11px 20px;}
  .ea-back{flex:0 0 auto;width:36px;height:36px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:#fff;border:1px solid #d9e2ec;color:#14304a !important;text-decoration:none !important;transition:all .2s;cursor:pointer;}
  .ea-back:hover{background:#e8742a;border-color:#e8742a;color:#fff !important;transform:translateX(-2px);box-shadow:0 4px 10px rgba(232,116,42,.35);}
  .ea-title{margin:0;font-size:19px;font-weight:700;color:#14304a;line-height:1.2;} .ea-crumb{font-size:12px;color:#6b7785;}
  .ea-sp{flex:1;}
  .ea-req{display:flex;align-items:center;gap:10px;padding:6px 14px;border-radius:999px;background:#f4f8fc;border:1px solid #d6e4f1;font-size:12.5px;font-weight:700;color:#14304a;white-space:nowrap;}
  .ea-req .track{width:90px;height:7px;border-radius:7px;background:#e3ebf3;overflow:hidden;} .ea-req .fill{display:block;height:100%;width:0;border-radius:7px;background:linear-gradient(90deg,#e8742a,#1b6ca8);transition:width .5s ease;}
  .ea-req.done{background:#e8f7ef;border-color:#bfe6cf;color:#177a49;} .ea-req.done .fill{background:#1e9e61;}
  .ea-sum{padding:12px 20px;background:linear-gradient(120deg,#f4f9fe 0%,#e8f1fa 100%);display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px 18px;}
  .ea-sum small{display:block;font-size:10px;letter-spacing:.7px;text-transform:uppercase;color:#6b7785;}
  .ea-sum-id{font-size:18px;font-weight:700;color:#14304a;line-height:1.25;word-break:break-all;} .ea-sum-sub{font-size:12.5px;color:#5b6b7b;}
  .ea-chips{display:flex;flex-wrap:wrap;gap:8px;}
  .ea-chip{min-width:92px;padding:6px 11px;border-radius:8px;background:#fff;border:1px solid #dbe6f1;animation:eaPop .4s cubic-bezier(.2,.9,.3,1.3) backwards;}
  .ea-chip:nth-child(1){animation-delay:.08s;} .ea-chip:nth-child(2){animation-delay:.14s;} .ea-chip:nth-child(3){animation-delay:.2s;} .ea-chip:nth-child(4){animation-delay:.26s;} .ea-chip:nth-child(5){animation-delay:.32s;} .ea-chip:nth-child(6){animation-delay:.38s;}
  .ea-chip b{display:block;font-size:12.5px;font-weight:700;color:#14304a;}
  .ea-chip.hot{border-color:#f6d3bb;background:#fff7f1;} .ea-chip.hot b{color:#d2631d;} .ea-chip.red{border-color:#f3c4c4;background:#fdeaea;} .ea-chip.red b{color:#c0392b;}

  /* section cards, numbered */
  .addarticleformouter{background:transparent !important;padding:0 !important;box-shadow:none !important;border:0 !important;max-width:none !important;width:100% !important;}
  .addarticleforminner{width:min(1180px,calc(100% - 28px)) !important;max-width:1180px !important;margin:16px auto 30px !important;padding:0 !important;background:transparent !important;border:0 !important;box-shadow:none !important;counter-reset:eastep;}
  #add_article > .container,#add_article > .content_right{background:#fff;border:1px solid #e4e9ef;border-radius:16px;padding:20px 26px 24px !important;margin:0 0 16px !important;max-width:none !important;width:100%;box-shadow:0 4px 18px rgba(20,48,74,.06);animation:eaRise .5s ease backwards;}
  #add_article > .container:nth-of-type(1){animation-delay:.05s;} #add_article > .container:nth-of-type(2){animation-delay:.12s;} #add_article > .container:nth-of-type(3){animation-delay:.19s;} #add_article > .content_right{animation-delay:.26s;}
  #add_article > .container.meta_nxtbtn,#add_article > .meta_nxtbtn{background:transparent;border:0;box-shadow:none;padding:0 !important;}
  .formcont1heading{position:relative;display:flex !important;align-items:center;gap:12px;background:none !important;color:#14304a !important;font-size:16px !important;font-weight:700 !important;padding:2px 0 12px !important;margin:0 0 14px !important;border:0 !important;border-bottom:1px solid #e4e9ef !important;clip-path:none !important;height:auto !important;line-height:1.4 !important;counter-increment:eastep;}
  .formcont1heading:before{content:counter(eastep);flex:0 0 auto;width:28px;height:28px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#e8742a,#1b6ca8);color:#fff;font-size:13px;font-weight:700;box-shadow:0 3px 8px rgba(27,108,168,.3);}
  .formcont1heading:after{display:none !important;}
  .formcont1heading:before{position:static !important;left:auto !important;top:auto !important;margin:0 !important;transform:none !important;}
  /* the four switches sit in one wrapping row */
  .addspacing .d-flex.align-items-end{flex-wrap:wrap;gap:12px 6px;} .addspacing .d-flex.align-items-end > div{width:auto !important;max-width:none !important;flex:0 0 auto !important;display:flex;align-items:center;}

  /* fields */
  .addspacing > [class*="col-"]{animation:eaRise .45s ease backwards;}
  .addspacing > [class*="col-"]:nth-child(1){animation-delay:.1s;} .addspacing > [class*="col-"]:nth-child(2){animation-delay:.14s;} .addspacing > [class*="col-"]:nth-child(3){animation-delay:.18s;} .addspacing > [class*="col-"]:nth-child(4){animation-delay:.22s;}
  .addspacing > [class*="col-"]:nth-child(5){animation-delay:.26s;} .addspacing > [class*="col-"]:nth-child(6){animation-delay:.3s;} .addspacing > [class*="col-"]:nth-child(n+7){animation-delay:.34s;}
  #add_article label{display:block;font-size:12px !important;font-weight:700 !important;letter-spacing:.3px;text-transform:uppercase;color:#5b6b7b !important;margin:0 0 6px !important;transition:color .2s;}
  #add_article label span[style*="red"]{color:#d64545 !important;}
  #add_article .addspacing > [class*="col-"]:focus-within > label{color:#1b6ca8 !important;}
  #add_article input[type=text],#add_article input[type=number],#add_article input[type=email],#add_article select,#add_article .form-select,#add_article .form-control{height:44px;width:100%;border:1px solid #cfd8e3 !important;border-radius:10px !important;background-color:#fbfcfe !important;padding:8px 13px !important;font-size:14px !important;color:#22303f !important;box-shadow:none !important;transition:border-color .2s,box-shadow .2s,background .2s;}
  #add_article textarea{width:100%;min-height:96px;border:1px solid #cfd8e3 !important;border-radius:10px !important;background:#fbfcfe !important;padding:11px 13px !important;font-size:14px !important;color:#22303f !important;box-shadow:none !important;resize:vertical;transition:border-color .2s,box-shadow .2s,background .2s;}
  #add_article input:focus,#add_article select:focus,#add_article textarea:focus{border-color:#1b6ca8 !important;background:#fff !important;box-shadow:0 0 0 4px rgba(27,108,168,.14) !important;outline:0;}
  #add_article input.error,#add_article select.error,#add_article textarea.error{border-color:#d64545 !important;background:#fff8f8 !important;animation:eaShake .35s;}
  #add_article input.valid:not(:focus):not([type=checkbox]),#add_article select.valid:not(:focus){border-color:#b9e0cb !important;}
  #add_article label.error{display:block;color:#d64545 !important;font-size:12px !important;font-weight:600 !important;text-transform:none !important;letter-spacing:0;margin:5px 0 0 !important;animation:eaRise .25s ease;}
  #add_article input[readonly],#add_article input[disabled],#add_article select[disabled]{background:#f1f4f8 !important;color:#7b8a9a !important;}

  /* checkboxes become switches */
  #add_article input[type=checkbox]{appearance:none;-webkit-appearance:none;width:40px !important;height:22px !important;border-radius:22px;border:0 !important;background:#cfd8e3 !important;position:relative;cursor:pointer;vertical-align:middle;transition:background .25s;flex:0 0 auto;padding:0 !important;}
  #add_article input[type=checkbox]:before{content:"";position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.3);transition:transform .25s cubic-bezier(.3,1.4,.5,1);}
  #add_article input[type=checkbox]:checked{background:#1e9e61 !important;} #add_article input[type=checkbox]:checked:before{transform:translateX(18px);}
  #add_article input[type=checkbox]:focus-visible{box-shadow:0 0 0 4px rgba(27,108,168,.2) !important;}
  #add_article input[type=checkbox] + label{display:inline-block !important;text-transform:none !important;font-size:13.5px !important;font-weight:600 !important;color:#33475b !important;letter-spacing:0;margin:0 14px 0 8px !important;cursor:pointer;vertical-align:middle;}

  /* upload */
  .content_right .dropboxdiv{padding:0;} .content_right .dropzone{border:2px dashed #b9c8d8 !important;border-radius:14px !important;background:#fbfcfe !important;min-height:150px;transition:border-color .2s,background .2s,box-shadow .2s;}
  .content_right .dropzone:hover,.content_right .dropzone.dz-drag-hover{border-color:#1b6ca8 !important;background:#eef6fc !important;box-shadow:0 0 0 5px rgba(27,108,168,.1);}
  .content_right .dropzone .dz-message{color:#5b6b7b;font-weight:600;}
  .uploadinfobox{background:#f8fafc;border:1px solid #e1e8ef;border-radius:12px;transition:transform .2s,box-shadow .2s;} .uploadinfobox:hover{transform:translateY(-2px);box-shadow:0 8px 18px rgba(20,48,74,.1);}
  .fileinfopara{font-weight:700;color:#5b6b7b;font-size:12.5px;text-transform:uppercase;letter-spacing:.4px;margin:0;}

  /* buttons */
  .meta_nxtbtn .row{justify-content:flex-end;gap:12px;margin:0;} .meta_nxtbtn .form_btn{flex:0 0 auto;width:auto;padding:0;}
  .btn-next,.btn-next-cancel{height:46px;min-width:140px;padding:0 28px !important;border-radius:10px !important;font-weight:700;font-size:14.5px;cursor:pointer;transition:transform .15s,box-shadow .2s,filter .2s,background .2s;}
  .btn-next{border:0 !important;background:linear-gradient(120deg,#1b6ca8,#14304a) !important;color:#fff !important;box-shadow:0 6px 16px rgba(27,108,168,.32);}
  .btn-next:hover{transform:translateY(-2px);filter:brightness(1.1);box-shadow:0 12px 24px rgba(27,108,168,.4);} .btn-next:active{transform:scale(.98);} .btn-next[disabled]{opacity:.65;cursor:wait;transform:none;}
  .btn-next-cancel{border:1px solid #cfd8e3 !important;background:#fff !important;color:#33475b !important;} .btn-next-cancel:hover{background:#f1f5f9 !important;transform:translateY(-1px);}
  .btn-next-cancel a{color:inherit !important;text-decoration:none !important;display:block;line-height:44px;margin:0 -28px;padding:0 28px;}

  /* loader */
  #ea-overlay{position:fixed;inset:0;z-index:99998;display:none;align-items:center;justify-content:center;flex-direction:column;background:rgba(255,255,255,.86);backdrop-filter:blur(2px);}
  #ea-overlay.on{display:flex;animation:eaFade .2s ease;}
  #ea-overlay .ring{width:58px;height:58px;border-radius:50%;border:5px solid #e9eef4;border-top-color:#e8742a;border-right-color:#1b6ca8;animation:eaSpin .85s linear infinite;margin-bottom:14px;}
  #ea-overlay .t{font-size:17px;font-weight:700;color:#14304a;} #ea-overlay .s{color:#6b7785;margin-top:3px;}
  #ea-bar{position:fixed;top:0;left:0;height:4px;width:0;z-index:99999;opacity:0;background:linear-gradient(90deg,#e8742a,#f5a35c 60%,#1b6ca8);box-shadow:0 0 12px rgba(232,116,42,.65);transition:width .25s ease,opacity .4s ease;} #ea-bar.on{opacity:1;}
  @media(max-width:640px){#add_article > .container,#add_article > .content_right{padding:16px 14px 18px !important;} .ea-req .track{display:none;}}
  @keyframes eaRise{from{opacity:0;transform:translateY(12px);}to{opacity:1;transform:none;}} @keyframes eaPop{from{opacity:0;transform:scale(.8);}to{opacity:1;transform:none;}}
  @keyframes eaFade{from{opacity:0;}to{opacity:1;}} @keyframes eaSpin{to{transform:rotate(360deg);}} @keyframes eaShake{0%,100%{transform:translateX(0);}25%{transform:translateX(-5px);}75%{transform:translateX(5px);}}
  /* stage -> workflow -> department hints, review dialog, refresh */
  .ea-refresh{flex:0 0 auto;width:36px;height:36px;border-radius:50%;border:1px solid #d9e2ec;background:#fff;color:#14304a !important;display:inline-flex;align-items:center;justify-content:center;text-decoration:none !important;cursor:pointer;transition:all .2s;}
  .ea-refresh:hover{background:#14304a;border-color:#14304a;color:#fff !important;box-shadow:0 4px 10px rgba(20,48,74,.3);} .ea-refresh:hover i{transform:rotate(180deg);} .ea-refresh i{transition:transform .4s ease;}
  .ea-hint{margin-top:6px;padding:6px 10px;border-radius:8px;font-size:12px;line-height:1.4;color:#177a49;background:#eaf7f0;border:1px solid #c8e8d6;animation:eaRise .3s ease;} .ea-hint.warn{color:#8a6d1a;background:#fff8e6;border-color:#f1dfa6;} .ea-hint i{margin-right:5px;}
  .ea-flash{animation:eaGlow 1.3s ease !important;} @keyframes eaGlow{0%{box-shadow:0 0 0 0 rgba(232,116,42,.6);border-color:#e8742a;}100%{box-shadow:0 0 0 12px rgba(232,116,42,0);}}
  #ea-dlg{position:fixed;inset:0;z-index:99990;display:none;align-items:center;justify-content:center;background:rgba(15,32,52,.5);backdrop-filter:blur(3px);padding:16px;}
  #ea-dlg.on{display:flex;animation:eaFade .2s ease;}
  #ea-dlg .box{width:min(560px,100%);max-height:90vh;display:flex;flex-direction:column;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 28px 70px rgba(15,35,60,.38);animation:eaPopBox .45s cubic-bezier(.2,1.2,.3,1) backwards;}
  #ea-dlg .box:before{content:"";display:block;height:5px;flex:0 0 auto;background:linear-gradient(90deg,#1b6ca8,#7fb3de);} #ea-dlg.info .box:before{background:linear-gradient(90deg,#e8932a,#f5c98a);}
  #ea-dlg .in{padding:22px 26px 20px;text-align:center;overflow:auto;}
  #ea-dlg .ic{width:56px;height:56px;margin:0 auto 10px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#e6f1fa;color:#1b6ca8;font:700 28px/1 'Open Sans',Arial,sans-serif;animation:eaPop .5s cubic-bezier(.2,1.4,.3,1) .1s backwards;} #ea-dlg.info .ic{background:#fff3e0;color:#e8932a;}
  #ea-dlg h3{margin:0 0 4px;font-size:20px;font-weight:700;color:#1c3550;} #ea-dlg p{margin:0;color:#51606f;font-size:13.5px;line-height:1.5;}
  #ea-dlg ul{list-style:none;margin:14px 0 0;padding:0;text-align:left;max-height:46vh;overflow:auto;}
  #ea-dlg li{margin:0 0 7px;padding:9px 12px 9px 14px;background:#f6f9fc;border:1px solid #e3eaf2;border-left:4px solid #1b6ca8;border-radius:10px;animation:eaRise .35s ease backwards;}
  #ea-dlg li b{display:block;font-size:11.5px;letter-spacing:.4px;text-transform:uppercase;color:#6b7785;margin-bottom:3px;}
  #ea-dlg li .cmp{display:flex;flex-wrap:wrap;align-items:center;gap:8px;font-size:13.5px;} #ea-dlg li .old{color:#9a6a6a;text-decoration:line-through;text-decoration-color:rgba(192,57,43,.5);word-break:break-word;} #ea-dlg li .new{color:#177a49;font-weight:700;word-break:break-word;} #ea-dlg li i{color:#9aa8b8;}
  #ea-dlg .btns{display:flex;justify-content:center;gap:10px;margin-top:18px;}
  .ea-dbtn{min-width:130px;height:44px;padding:0 24px;border-radius:10px;font-weight:700;font-size:14px;cursor:pointer;border:1px solid #cfd8e3;background:#fff;color:#33475b;transition:all .15s;} .ea-dbtn:hover{background:#f1f5f9;transform:translateY(-1px);}
  .ea-dbtn.primary{border:0;color:#fff;background:linear-gradient(120deg,#1b6ca8,#14304a);box-shadow:0 5px 14px rgba(27,108,168,.3);} .ea-dbtn.primary:hover{filter:brightness(1.1);}
  @keyframes eaPopBox{from{opacity:0;transform:translateY(18px) scale(.93);}to{opacity:1;transform:none;}}

  /* Add Article: the upload block sits outside the form, as its own card */
  .addarticleforminner > .content_right{background:#fff;border:1px solid #e4e9ef;border-radius:16px;padding:20px 26px 24px !important;margin:0 0 16px !important;box-shadow:0 4px 18px rgba(20,48,74,.06);animation:eaRise .5s ease backwards;}
  .ea-created{display:none;align-items:center;gap:12px;padding:12px 20px;background:#e8f7ef;color:#177a49;font-weight:600;font-size:13.5px;} .ea-created.on{display:flex;animation:eaRise .35s ease;} .ea-created i{font-size:20px;}
  .ea-chain{display:flex;flex-wrap:wrap;align-items:center;gap:6px;margin-top:8px;} .ea-step{padding:4px 11px;border-radius:999px;background:#fff;border:1px solid #d5dde6;font-size:12px;font-weight:600;color:#33475b;animation:eaPop .35s ease backwards;}
  .ea-step.first{background:#fff7f1;border-color:#f1b98f;color:#c9591a;} .ea-arrow{color:#9aa8b8;font-size:10px;}
  /* ---- Upload step (Add Article): same card look; the drop zone, file chips, buttons and counters ---- */
  #overlay,#loader{display:none !important;}   /* the old dark loader: replaced by the new one */
  .content_right > .row{margin:0;} .content_right .dropboxdiv{background:transparent !important;box-shadow:none !important;border:0 !important;padding:0 8px 0 0 !important;height:auto !important;}
  .content_right .dropboxdiv > .row{height:auto !important;} .content_right .dropboxinner{background:transparent !important;border:0 !important;box-shadow:none !important;padding:0 !important;height:auto !important;min-height:0 !important;display:block !important;position:static !important;}
  .content_right .dropboxdiv .col-10{flex:0 0 100%;max-width:100%;}
  .aa-help{display:flex;gap:10px;align-items:flex-start;margin:0 0 12px;padding:10px 14px;border-radius:10px;background:#eef6fc;border:1px solid #cfe3f3;color:#2f4b66;font-size:13px;line-height:1.5;} .aa-help i{color:#1b6ca8;margin-top:2px;}
  .content_right .dropzone{padding:18px !important;min-height:190px;display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:center;gap:12px;}
  .content_right .dropzone .dz-message{flex:1 0 100%;margin:18px 0 !important;text-align:center;font-size:15px;}
  .content_right .dropzone .dz-preview{margin:0 !important;min-height:0 !important;background:#fff;border:1px solid #dbe6f1;border-radius:12px;padding:12px 14px 10px;width:210px;box-shadow:0 3px 10px rgba(20,48,74,.06);animation:eaPop .35s ease backwards;}
  .content_right .dropzone .dz-preview .dz-image{display:none !important;}
  .content_right .dropzone .dz-preview .dz-details{position:static !important;opacity:1 !important;padding:0 !important;text-align:left;min-width:0 !important;}
  .content_right .dropzone .dz-preview .dz-filename span{font-size:13px;font-weight:700;color:#14304a;word-break:break-all;background:none !important;padding:0 !important;} .content_right .dropzone .dz-preview .dz-size{font-size:11.5px;color:#7b8a9a;margin:2px 0 6px !important;}
  .content_right .dropzone .dz-preview .dz-size span{background:none !important;padding:0 !important;}
  .content_right .dropzone .dz-preview .dz-progress{left:0 !important;top:auto !important;margin-left:0 !important;position:relative !important;width:100% !important;height:6px !important;margin:4px 0 8px !important;border:0 !important;border-radius:6px;background:#e5ecf3 !important;overflow:hidden;transform:none !important;opacity:1 !important;}
  .content_right .dropzone .dz-preview .dz-upload{background:linear-gradient(90deg,#e8742a,#1b6ca8) !important;} .content_right .dropzone .dz-preview.dz-success .dz-upload{background:#1e9e61 !important;}
  .content_right .dropzone .dz-preview .dz-success-mark,.content_right .dropzone .dz-preview .dz-error-mark{display:none;} .content_right .dropzone .dz-preview .dz-error-message{position:static !important;opacity:1 !important;display:none;margin-top:6px;background:#fdeaea !important;color:#b13434;border-radius:8px;} .content_right .dropzone .dz-preview.dz-error .dz-error-message{display:block;}
  .content_right .btn-remove{background:#fdeaea !important;color:#c0392b !important;border:1px solid #f3c4c4 !important;border-radius:999px !important;padding:3px 14px !important;font-size:12px;font-weight:700;transition:all .15s;} .content_right .btn-remove:hover{background:#c0392b !important;color:#fff !important;}
  .content_right .droppedfile{margin-top:16px !important;} .content_right .droppedfile > p,.content_right .droppedfile .fileinfo{display:none !important;}
  .content_right .fileupdtn{display:flex;justify-content:flex-end;gap:12px;margin:0;}
  .content_right .filebtn2.btn-save{height:46px;min-width:170px;padding:0 28px !important;border:0 !important;border-radius:10px !important;font-weight:700;font-size:14.5px;color:#fff !important;background:linear-gradient(120deg,#1e9e61,#14704a) !important;box-shadow:0 6px 16px rgba(30,158,97,.32);cursor:pointer;transition:transform .15s,filter .2s,box-shadow .2s;}
  .content_right .filebtn2.btn-save:hover{transform:translateY(-2px);filter:brightness(1.08);box-shadow:0 12px 24px rgba(30,158,97,.4);}
  .content_right .uploadinfobox{height:auto !important;} .content_right .uploadinfobox:after{display:none !important;}
  .content_right .uploadinfodiv .uploadinfobox{background:#fff;border:1px solid #e1e8ef;border-radius:14px;box-shadow:none;padding:2px 0 12px;overflow:hidden;background-image:none !important;}
  .content_right .uploadinfobox img{width:34px;height:34px;} .content_right .fileno{padding:0 18px;} .content_right .fileno p{margin:0;font-size:30px !important;font-weight:700;color:#14304a;line-height:1.2;}

  /* ---- the old SweetAlert pop-ups on these pages ("Your article is Submitted", "Uploaded!", ...) get the same card look ---- */
  .sweet-overlay{background:rgba(15,32,52,.5) !important;backdrop-filter:blur(3px);-webkit-backdrop-filter:blur(3px);}
  .sweet-alert{font-family:'Open Sans','Segoe UI',Arial,sans-serif !important;border-radius:18px !important;padding:26px 30px 22px !important;box-shadow:0 28px 70px rgba(15,35,60,.38),0 2px 6px rgba(15,35,60,.12) !important;overflow:hidden;border-top:5px solid #1b6ca8;}
  .sweet-alert h2{font-size:21px !important;font-weight:700 !important;color:#1c3550 !important;line-height:1.3 !important;margin:14px 0 6px !important;}
  .sweet-alert p{color:#51606f !important;font-size:14px !important;line-height:1.55;}
  .sweet-alert .sa-button-container{margin-top:18px;}
  .sweet-alert button{border-radius:10px !important;padding:10px 30px !important;font-size:14px !important;font-weight:700 !important;margin:0 5px !important;transition:transform .15s,filter .2s,box-shadow .2s !important;}
  .sweet-alert button.confirm{background:linear-gradient(120deg,#1b6ca8,#14304a) !important;box-shadow:0 5px 14px rgba(27,108,168,.3) !important;color:#fff !important;} .sweet-alert button.confirm:hover{filter:brightness(1.1);transform:translateY(-1px);}
  .sweet-alert button.cancel{background:#fff !important;color:#4b5b6b !important;border:1px solid #cfd8e3 !important;}
</style>
<?php
}
