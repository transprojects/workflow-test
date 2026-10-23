<?php
/* Shared look for filter forms (customer / journal / stage / dates ...) and the jQuery UI date picker.
 * Echoed by lu_css() (list pages) and fu_head() (add / edit pages). */
function ui_filters_css()
{
	static $done = false;
	if ($done) { return; }
	$done = true;
?>
<style id="ui-filters-theme">
  /* ---- filter form card ---- */
  .divouter:has(form) form.formouter{background:transparent !important;border:0 !important;box-shadow:none !important;padding:0 !important;margin:0 !important;}
  .divouter:has(form) .selectdiv{padding:10px 6px 6px !important;gap:14px 0 !important;align-items:flex-end;}
  .divouter:has(form) .selectdiv > [class*="col-"]{padding:0 8px;}
  .divouter:has(form) label,.formouter label{display:block;font-size:11.5px !important;font-weight:700 !important;text-transform:uppercase;letter-spacing:.35px;color:#5b6b7b !important;margin:0 0 6px !important;}
  .divouter:has(form) input[type=text],.divouter:has(form) input[type=date],.divouter:has(form) input[type=number],.divouter:has(form) select.form-select,.divouter:has(form) .form-control,
  .formouter input.hasDatepicker{height:42px !important;width:100%;border:1px solid #cfd8e3 !important;border-radius:10px !important;background-color:#fbfcfe !important;padding:0 12px !important;font-size:14px;color:#14304a;box-shadow:none !important;transition:border-color .15s,box-shadow .15s;}
  .divouter:has(form) input:focus,.divouter:has(form) select:focus{border-color:#1b6ca8 !important;box-shadow:0 0 0 3px rgba(27,108,168,.15) !important;outline:0;}
  .divouter:has(form) .select2-container{width:100% !important;}
  .divouter:has(form) .select2-container--default .select2-selection--single,.divouter:has(form) .select2-container--default .select2-selection--multiple{min-height:42px !important;height:auto !important;border:1px solid #cfd8e3 !important;border-radius:10px !important;background:#fbfcfe !important;padding:3px 8px !important;display:flex;align-items:center;max-height:96px;overflow-y:auto;}
  .divouter:has(form) .select2-container--default.select2-container--open .select2-selection,.divouter:has(form) .select2-container--default.select2-container--focus .select2-selection{border-color:#1b6ca8 !important;box-shadow:0 0 0 3px rgba(27,108,168,.15);}
  .divouter:has(form) .select2-selection__rendered{padding:0 !important;line-height:1.4 !important;color:#14304a;}
  .divouter:has(form) .select2-selection--single .select2-selection__arrow{height:40px !important;}
  .divouter:has(form) .select2-container--default .select2-selection--multiple .select2-selection__choice{background:#eaf2fa !important;border:1px solid #c5d8ea !important;color:#14304a !important;border-radius:8px !important;padding:1px 8px !important;margin:3px 4px 3px 0 !important;font-size:12.5px;}
  .divouter:has(form) .select2-selection--multiple .select2-search__field{height:28px !important;min-height:0 !important;margin:2px 0 !important;border:0 !important;background:transparent !important;box-shadow:none !important;line-height:20px !important;font-size:13.5px;padding:0 4px !important;resize:none;overflow:hidden !important;}
  .divouter:has(form) input.hasDatepicker,.formouter input.hasDatepicker,input.hasDatepicker.form-control{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%231b6ca8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='5' width='18' height='16' rx='3'/%3E%3Cpath d='M16 3v4M8 3v4M3 10h18'/%3E%3C/svg%3E") !important;background-repeat:no-repeat !important;background-position:right 12px center !important;padding-right:38px !important;cursor:pointer;}
  .divouter:has(form) .resultbtn{height:42px;padding:0 28px;margin-top:6px;}
  .divouter:has(form) .fa-refresh,.divouter:has(form) .fa-sync,.divouter:has(form) .refresh{display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;border-radius:50%;background:#fff4e8;color:#d2631d !important;transition:all .15s;vertical-align:middle;margin-left:10px;}
  .divouter:has(form) .fa-refresh:hover,.divouter:has(form) .fa-sync:hover{background:#e8742a;color:#fff !important;}

  /* ---- select2 boxes inside add / edit forms ---- */
  .addarticleforminner .select2-container,.fu-card .select2-container{width:100% !important;}
  .addarticleforminner select.select2-hidden-accessible,.fu-card select.select2-hidden-accessible{position:absolute !important;width:1px !important;height:1px !important;min-height:0 !important;padding:0 !important;margin:-1px !important;border:0 !important;overflow:hidden !important;clip:rect(0 0 0 0) !important;opacity:0 !important;}
  .addarticleforminner .select2-container--default .select2-selection--multiple,.addarticleforminner .select2-container--default .select2-selection--single{min-height:44px !important;height:auto !important;border:1px solid #cfd8e3 !important;border-radius:10px !important;background:#fbfcfe !important;padding:4px 8px !important;}
  .addarticleforminner .select2-container--default .select2-selection--multiple{max-height:150px;overflow-y:auto;display:block;}
  .addarticleforminner .select2-container--default .select2-selection--single{display:flex;align-items:center;}
  .addarticleforminner .select2-container--default.select2-container--focus .select2-selection,.addarticleforminner .select2-container--default.select2-container--open .select2-selection{border-color:#1b6ca8 !important;box-shadow:0 0 0 3px rgba(27,108,168,.15);}
  .addarticleforminner .select2-selection__rendered{padding:0 !important;display:flex !important;flex-wrap:wrap;align-items:center;gap:4px;}
  .addarticleforminner .select2-container--default .select2-selection--multiple .select2-selection__choice{display:inline-flex;align-items:center;flex-direction:row-reverse;gap:6px;background:#eaf2fa !important;border:1px solid #c5d8ea !important;color:#14304a !important;border-radius:8px !important;padding:3px 10px !important;margin:0 !important;font-size:13px;line-height:1.3;}
  .addarticleforminner .select2-container--default .select2-selection--multiple .select2-selection__choice__remove{position:static !important;border:0 !important;background:transparent !important;color:#d64545 !important;font-weight:700;margin:0 !important;padding:0 !important;height:auto !important;}
  .addarticleforminner .select2-search--inline{margin:0;}
  .addarticleforminner .select2-search--inline .select2-search__field{height:28px !important;min-height:0 !important;width:auto !important;min-width:120px;margin:2px 0 !important;padding:0 4px !important;border:0 !important;border-radius:0 !important;background:transparent !important;box-shadow:none !important;line-height:20px !important;font-size:13.5px;resize:none !important;overflow:hidden !important;}
  .addarticleforminner .select2-selection--single .select2-selection__arrow{height:42px !important;}

  /* ---- select2 dropdown list (same as the Current Articles filters) ---- */
  .select2-container--open{z-index:3100;}
  .select2-dropdown{border:1px solid #e4e9ef !important;border-radius:12px !important;box-shadow:0 18px 40px rgba(20,48,74,.2) !important;overflow:hidden;background:#fff !important;margin-top:4px;}
  .select2-container--default .select2-results>.select2-results__options{max-height:260px;padding:6px;}
  .select2-container--default .select2-results__option{padding:8px 12px !important;border-radius:8px;font-size:13.5px;color:#22303f;margin:1px 0;line-height:1.35;}
  .select2-container--default .select2-results__option--highlighted[aria-selected],.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable{background:#14304a !important;color:#fff !important;}
  .select2-container--default .select2-results__option[aria-selected=true],.select2-container--default .select2-results__option--selected{background:#eef5fb !important;color:#1b6ca8 !important;font-weight:600;}
  .select2-container--default .select2-search--dropdown{padding:8px 8px 4px;}
  .select2-container--default .select2-search--dropdown .select2-search__field{height:36px !important;border:1px solid #d5dde6 !important;border-radius:9px !important;padding:0 10px !important;background:#fbfcfe !important;}
  .select2-container--default .select2-results__message{padding:10px 12px;color:#6b7785;}

  /* ---- date picker ---- */
  #ui-datepicker-div.ui-datepicker,.ui-datepicker{width:300px !important;padding:12px !important;background:#fff !important;border:1px solid #e4e9ef !important;border-radius:16px !important;box-shadow:0 18px 44px rgba(20,48,74,.22) !important;font-family:inherit;z-index:99990 !important;}
  .ui-datepicker .ui-datepicker-header{background:linear-gradient(120deg,#1b6ca8,#14304a) !important;border:0 !important;border-radius:12px !important;padding:10px 0 !important;margin-bottom:8px;color:#fff;position:relative;}
  .ui-datepicker .ui-datepicker-title{color:#fff !important;font-weight:700;font-size:14px;line-height:1.6;margin:0 2.4em;text-align:center;}
  .ui-datepicker .ui-datepicker-title select{height:26px !important;width:auto !important;display:inline-block;border:0 !important;border-radius:6px !important;background:rgba(255,255,255,.18) !important;color:#fff !important;font-size:13px;padding:0 4px !important;margin:0 2px !important;}
  .ui-datepicker .ui-datepicker-title select option{color:#14304a;}
  .ui-datepicker .ui-datepicker-prev,.ui-datepicker .ui-datepicker-next{top:8px !important;width:28px !important;height:28px !important;border:0 !important;border-radius:8px !important;background:rgba(255,255,255,.16) !important;cursor:pointer;}
  .ui-datepicker .ui-datepicker-prev{left:8px !important;} .ui-datepicker .ui-datepicker-next{right:8px !important;}
  .ui-datepicker .ui-datepicker-prev:hover,.ui-datepicker .ui-datepicker-next:hover{background:rgba(255,255,255,.32) !important;}
  .ui-datepicker .ui-datepicker-prev span,.ui-datepicker .ui-datepicker-next span{background-image:none !important;text-indent:0 !important;display:flex;align-items:center;justify-content:center;width:100%;height:100%;margin:0;left:0;top:0;color:#fff;font-size:0;position:static;}
  .ui-datepicker .ui-datepicker-prev span:before{content:'\2039';font-size:20px;line-height:1;} .ui-datepicker .ui-datepicker-next span:before{content:'\203A';font-size:20px;line-height:1;}
  .ui-datepicker table{width:100%;border-collapse:separate;border-spacing:2px;margin:0;font-size:13px;}
  .ui-datepicker th{padding:6px 0;font-weight:700;font-size:11.5px;color:#5b6b7b;text-transform:uppercase;border:0 !important;background:transparent !important;}
  .ui-datepicker td{padding:0;border:0 !important;background:transparent !important;}
  .ui-datepicker td a,.ui-datepicker td span{display:block;height:34px;line-height:34px;text-align:center;border:0 !important;border-radius:10px !important;background:transparent !important;color:#14304a !important;font-weight:600;font-size:13px;}
  .ui-datepicker td a:hover{background:#eaf2fa !important;color:#1b6ca8 !important;}
  .ui-datepicker td.ui-datepicker-today a{background:#fff4e8 !important;color:#d2631d !important;}
  .ui-datepicker td a.ui-state-active,.ui-datepicker td a.ui-state-highlight.ui-state-active{background:linear-gradient(120deg,#1b6ca8,#14304a) !important;color:#fff !important;box-shadow:0 4px 10px rgba(27,108,168,.35);}
  .ui-datepicker td.ui-state-disabled span,.ui-datepicker td.ui-datepicker-other-month span{color:#b9c4d0 !important;}
  .ui-datepicker .ui-datepicker-buttonpane{border:0 !important;margin:6px 0 0 !important;padding:0 !important;display:flex;justify-content:space-between;}
  .ui-datepicker .ui-datepicker-buttonpane button{border:0 !important;border-radius:8px !important;background:#eaf2fa !important;color:#1b6ca8 !important;font-weight:600;padding:6px 12px !important;float:none !important;opacity:1 !important;}
</style>
<?php
}
