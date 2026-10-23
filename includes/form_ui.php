<?php
/* ============================================================================
 * Shared form validation + popup + "Add Article" look for the Customer and Journal pages.
 *
 *  - ONE rule set (fu_rules_*) is used twice: PHP re-checks every save (the server is the
 *    authority, so a bad post can never end in a bare "Missing required field" line), and the
 *    same rules are sent to the browser so the form is checked before it posts.
 *  - Every problem opens a popup that lists ALL of them; clicking one jumps to the field.
 *    Server-side problems (validation or database) re-open the form with the user's entries
 *    kept and the same popup.
 *  - The look is the Add Article page's: header box with a live summary and a required-fields
 *    counter, numbered section cards, same fields / buttons / dialog / saving overlay
 *    (the theme is includes/ea-ui.php, re-scoped to the page's form id).
 *
 * Page usage:
 *     require_once __DIR__.'/includes/form_ui.php';                       // top of the page
 *     $fu_errors = fu_validate($_POST, fu_rules_journal());               // inside the POST handler
 *     <head>: fu_head('edit_journal');   above the form: fu_hero($ui);   before </body>: fu_foot('#edit_journal', $rules, $fu_errors, null, $ui);
 * ========================================================================== */

/* ---------- rules (only what a record cannot be saved without) ---------- */
function fu_rules_journal($is_add = false)
{
	$req = array('j_cust_id' => 'Customer', 'j_name' => 'Journal name', 'j_code' => 'Journal code');
	return array(
		'required' => $req,
		'id'       => array('j_cust_id'),                                   /* a placeholder value of 0 is not a choice */
	);
}

function fu_rules_customer()
{
	return array('required' => array('cust_name' => 'Customer name'), 'id' => array());
}

/* page header + live summary (ids are read by the script in fu_foot) */
function fu_ui_journal($title, $crumb)
{
	return array('title' => $title, 'crumb' => $crumb, 'kind' => 'Journal', 'idField' => 'j_code',
	             'subFields' => array('j_cust_id', 'j_name'), 'sub0' => 'Choose a customer and enter the journal name',
	             'chips' => array(array('First proof', 'j_fp_wf'), array('Revise', 'j_rev_wf'), array('Final', 'j_fin_wf'), array('JSTAGE', 'j_jstage_wf')));
}
/* any other add / edit page: header box + required counter (required fields are taken from the form's own required attributes) */
function fu_ui_basic($title, $crumb)
{
	return array('title' => $title, 'crumb' => $crumb, 'kind' => '', 'idField' => '', 'subFields' => array(), 'sub0' => '', 'chips' => array(), 'auto' => true);
}
function fu_ui_customer($title, $crumb)
{
	return array('title' => $title, 'crumb' => $crumb, 'kind' => 'Customer', 'idField' => 'cust_name',
	             'subFields' => array('parent_cust_id'), 'sub0' => 'Enter the customer name',
	             'chips' => array(array('Category', 'catg_id'), array('Parent', 'parent_cust_id')));
}

/* returns array of array('field' => name, 'label' => text, 'msg' => text); empty = valid */
function fu_validate($src, $rules)
{
	$out = array();
	$ids = isset($rules['id']) ? $rules['id'] : array();
	foreach ($rules['required'] as $f => $label) {
		$v = isset($src[$f]) && !is_array($src[$f]) ? trim((string)$src[$f]) : '';
		if ($v === '' || (in_array($f, $ids, true) && $v === '0')) {
			$out[] = array('field' => $f, 'label' => $label, 'msg' => 'is required - please choose or enter a value.');
		}
	}
	return $out;
}

/* keep what the user typed when the page is shown again after a failed save */
function fu_keep_posted($row, $post, $skip = array('action', 'id'))
{
	if (!is_array($row)) { $row = array(); }
	foreach ($post as $k => $v) {
		if (!in_array($k, $skip, true) && !is_array($v)) { $row[$k] = $v; }
	}
	return $row;
}

/* ---------- CSS: the Add Article theme, scoped to this page's form ---------- */
/* card layout for single-page "form on top, table below" screens */
function fu_card_css()
{
?>
<style id="fu-card-theme">
  body{background:#f3f6fa !important;}
  .fu-wrap{width:min(1180px,calc(100% - 28px));margin:16px auto 40px;}
  .fu-card{background:#fff;border:1px solid #e4e9ef;border-radius:16px;box-shadow:0 4px 18px rgba(20,48,74,.06);padding:22px 26px;margin-bottom:18px;}
  .fu-card h4,.fu-card h5{font-size:16px;font-weight:700;color:#14304a;margin:0 0 16px;padding-left:10px;border-left:4px solid #1b6ca8;}
  .fu-card label{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#5b6b7b;margin-bottom:6px;}
  .fu-card .form-control,.fu-card .form-select{min-height:42px;border:1px solid #cfd8e3;border-radius:10px;background:#fbfcfe;}
  .fu-card .btn-primary,.fu-card .btn-primary:hover{color:#fff !important;height:42px;padding:0 28px;border:0;border-radius:10px;font-weight:700;background:linear-gradient(120deg,#1b6ca8,#14304a);box-shadow:0 6px 16px rgba(27,108,168,.3);}
  .fu-card .btn-secondary{height:42px;padding:9px 22px;border-radius:10px;background:#fff;color:#14304a;border:1px solid #cfd8e3;}
  .fu-table{width:100%;border-collapse:separate;border-spacing:0;font-size:14px;}
  .fu-table th{background:#eef3f8;color:#14304a;font-weight:700;padding:11px 12px;border-bottom:1px solid #dbe3ec;text-align:left;}
  .fu-table th:first-child{border-radius:10px 0 0 0;} .fu-table th:last-child{border-radius:0 10px 0 0;}
  .fu-table td{padding:11px 12px;border-bottom:1px solid #eef1f5;vertical-align:middle;background:#fff;}
  .fu-table tr:hover td{background:#f7fafd;}
  .fu-table .btn{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;padding:0 !important;margin:0 3px;border:1px solid transparent;border-radius:10px;font-size:15px;line-height:1;transition:all .15s ease;text-decoration:none;}
  .fu-table .btn-primary{background:#fff4e8 !important;color:#d2631d !important;border-color:#f6d3bb;} .fu-table .btn-primary:hover{background:#e8742a !important;color:#fff !important;}
  .fu-table td:last-child,.fu-table th:last-child{white-space:nowrap;width:1%;text-align:center;} .fu-table td:last-child .btn{vertical-align:middle;}
  .fu-table .btn-danger{background:#fdeeee !important;color:#d64545 !important;border-color:#f7d4d4;} .fu-table .btn-danger:hover{background:#d64545 !important;color:#fff !important;}
  .fu-card .alert{border-radius:10px;}
  .fu-card select.select2-hidden-accessible,.fu-wrap select.select2-hidden-accessible{position:absolute !important;width:1px !important;height:1px !important;min-height:0 !important;padding:0 !important;margin:-1px !important;border:0 !important;overflow:hidden !important;clip:rect(0 0 0 0) !important;opacity:0 !important;}
  .fu-card .select2-container{width:100% !important;}
  .fu-card .select2-container--default .select2-selection--multiple,.fu-card .select2-container--default .select2-selection--single{min-height:42px;border:1px solid #cfd8e3;border-radius:10px;background:#fbfcfe;padding:3px 6px;}
  .fu-card .select2-container--default .select2-selection--single{display:flex;align-items:center;}
  .fu-card .select2-container--default .select2-selection--multiple .select2-selection__choice{background:#eaf2fa;border:1px solid #c5d8ea;color:#14304a;border-radius:8px;padding:2px 8px;margin-top:4px;}
  .fu-card .select2-container--default.select2-container--focus .select2-selection--multiple{border-color:#1b6ca8;}
  #fu_form .select2-search--inline .select2-search__field,.fu-card .select2-search--inline .select2-search__field{border-radius:0 !important;height:30px !important;line-height:20px !important;resize:none !important;overflow:hidden !important;min-height:0 !important;margin:4px 0 !important;padding:0 4px !important;border:0 !important;background:transparent !important;font-size:14px;box-shadow:none !important;}
  .fu-card .select2-container--default .select2-selection--multiple{height:auto !important;min-height:44px;max-height:140px;overflow-y:auto;}
</style>
<?php
}

/* jQuery UI dual-list "steps" picker (workflow pages) */
function fu_multiselect_css()
{
?>
<style id="fu-ms-theme">
  #add_workflow h3.lighter,#edit_workflow h3.lighter{font-size:16px;font-weight:700;color:#14304a;margin:26px 0 12px;padding-left:10px;border-left:4px solid #1b6ca8;}
  .ui-multiselect{border:1px solid #dbe3ec !important;border-radius:14px !important;overflow:hidden;background:#fff !important;width:100% !important;display:flex;}
  .ui-multiselect .selected,.ui-multiselect .available{float:none !important;flex:1 1 50%;width:auto !important;min-width:0;border:0 !important;padding:0 !important;}
  .ui-multiselect .selected{border-right:1px solid #e4e9ef !important;background:#f9fbfd;}
  .ui-multiselect .actions,.ui-multiselect .ui-widget-header{background:linear-gradient(120deg,#1b6ca8,#14304a) !important;color:#fff !important;border:0 !important;border-radius:0 !important;padding:10px 14px !important;font-weight:700;font-size:13px;display:flex;align-items:center;justify-content:space-between;}
  .ui-multiselect .actions a,.ui-multiselect .actions span{color:#fff !important;font-size:12px;}
  .ui-multiselect .search{margin:0 !important;} .ui-multiselect .search input,.ui-multiselect input.search{width:calc(100% - 20px) !important;height:36px;margin:10px;padding:0 12px;border:1px solid #cfd8e3 !important;border-radius:10px !important;background:#fbfcfe !important;}
  .ui-multiselect ul.connected-list{height:260px !important;margin:0 !important;padding:6px 10px 10px !important;overflow-y:auto;list-style:none;}
  .ui-multiselect ul.connected-list li{margin:5px 0 !important;padding:9px 12px !important;border:1px solid #dbe3ec !important;border-radius:10px !important;background:#fff !important;color:#14304a !important;font-size:14px;cursor:grab;transition:all .12s;}
  .ui-multiselect ul.connected-list li:hover{border-color:#1b6ca8 !important;background:#eef5fb !important;transform:translateX(2px);}
  .ui-multiselect .selected ul.connected-list li{background:#eaf2fa !important;border-color:#c5d8ea !important;}
  .ui-multiselect ul.connected-list li{display:flex !important;align-items:center;justify-content:space-between;gap:10px;}
  .ui-multiselect ul.connected-list li .ui-helper-hidden{display:none !important;}
  .ui-multiselect ul.connected-list li.ui-helper-hidden-accessible{display:none !important;}
  .ui-multiselect li a.action{flex:0 0 auto;width:26px;height:26px;border-radius:8px;display:inline-flex !important;align-items:center;justify-content:center;text-decoration:none;background:#e8f1fb;color:#1b6ca8;transition:all .12s;}
  .ui-multiselect ul.connected-list li{line-height:26px !important;} .ui-multiselect li a.action{position:static !important;float:none !important;margin:0 !important;top:auto !important;right:auto !important;}
  .ui-multiselect .selected li a.action{margin-left:auto !important;}
  .ui-multiselect li a.action:hover{background:#1b6ca8;color:#fff;}
  .ui-multiselect li a.action .ui-icon{background:none !important;width:auto;height:auto;text-indent:0;overflow:visible;font:700 17px/1 Arial,sans-serif;color:inherit;margin:0;}
  .ui-multiselect li a.action .ui-icon-plus:before{content:'+';}
  .ui-multiselect li a.action .ui-icon-minus:before,.ui-multiselect li a.action .ui-icon-closethick:before{content:'-';font-size:20px;}
  .ui-multiselect .selected li a.action{background:#fdeeee;color:#d64545;} .ui-multiselect .selected li a.action:hover{background:#d64545;color:#fff;}
  .ui-multiselect .ui-icon{opacity:1;}
  .ui-multiselect li > .ui-icon-arrowthick-2-n-s{display:none !important;}
  .ui-multiselect .selected li{justify-content:flex-start;} .ui-multiselect .selected li a.action{margin-left:auto;}
  .ui-multiselect .count{color:#fff !important;font-weight:700;}
</style>
<?php
}

/* modern confirm dialog for links/buttons marked data-confirm="message" (data-confirm-ok = button label); only follows the link when the user presses the red button */
function fu_confirm_js()
{
?>
<style>
  #fu-cf{position:fixed;inset:0;z-index:99999;display:none;align-items:center;justify-content:center;background:rgba(20,48,74,.45);backdrop-filter:blur(2px);}
  #fu-cf.on{display:flex;}
  #fu-cf .b{width:min(420px,calc(100% - 32px));background:#fff;border-radius:18px;box-shadow:0 24px 60px rgba(20,48,74,.35);padding:26px 26px 20px;text-align:center;animation:fuCf .18s ease;}
  @keyframes fuCf{from{transform:translateY(10px) scale(.97);opacity:0}to{transform:none;opacity:1}}
  #fu-cf .i{width:54px;height:54px;border-radius:50%;margin:0 auto 12px;background:#fdeeee;color:#d64545;font-size:24px;display:flex;align-items:center;justify-content:center;}
  #fu-cf h3{margin:0 0 6px;font-size:18px;font-weight:700;color:#14304a;} #fu-cf p{margin:0 0 18px;color:#5b6b7b;font-size:14px;line-height:1.45;}
  #fu-cf .r{display:flex;gap:10px;justify-content:center;} #fu-cf button{height:42px;padding:0 22px;border-radius:10px;font-weight:700;border:1px solid #cfd8e3;background:#fff;color:#14304a;cursor:pointer;}
  #fu-cf button.go{background:#d64545;border-color:#d64545;color:#fff;box-shadow:0 6px 16px rgba(214,69,69,.3);}
</style>
<div id="fu-cf" role="alertdialog" aria-modal="true"><div class="b"><div class="i"><i class="fa fa-trash-o"></i></div><h3>Are you sure?</h3><p id="fu-cf-m"></p><div class="r"><button type="button" id="fu-cf-no">No, cancel</button><button type="button" class="go" id="fu-cf-yes">Yes, delete</button></div></div></div>
<script>
(function () {
  var box = document.getElementById('fu-cf'), msg = document.getElementById('fu-cf-m'), href = null;
  function close() { box.classList.remove('on'); href = null; }
  document.addEventListener('click', function (e) {
    var a = e.target.closest ? e.target.closest('[data-confirm]') : null;
    if (!a) { return; }
    e.preventDefault(); e.stopPropagation();
    href = a.getAttribute('href'); msg.textContent = a.getAttribute('data-confirm'); box.classList.add('on');
    document.getElementById('fu-cf-yes').textContent = a.getAttribute('data-confirm-ok') || 'Yes, delete';
  }, true);
  document.getElementById('fu-cf-no').addEventListener('click', close);
  box.addEventListener('click', function (e) { if (e.target === box) { close(); } });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { close(); } });
  document.getElementById('fu-cf-yes').addEventListener('click', function () { var h = href; close(); if (h) { location.href = h; } });
})();
</script>
<?php
}

/* Bootstrap-3 "form-horizontal" forms (label col-sm-3 + field col-sm-6, one per row) -> a tidy responsive grid inside .fu-card */
function fu_horizontal_css()
{
?>
<style id="fu-horizontal">
  .fu-card .container,.fu-card .container.mt-4{max-width:none;padding:0;margin:0 !important;} .fu-card .row{margin-left:0;margin-right:0;}
  .fu-card .step-pane > .formcont1heading{display:none;}
  .fu-card #fuelux-wizard-container{margin:0;} .fu-card .step-content{border:0;padding:0;}
  .fu-card form.form-horizontal{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px 22px;align-items:start;}
  .fu-card form.form-horizontal .form-group{margin:0 !important;display:block;padding:0;}
  .fu-card form.form-horizontal .form-group > label.control-label{float:none !important;width:100% !important;max-width:none !important;text-align:left !important;padding:0 !important;margin:0 0 6px !important;}
  .fu-card form.form-horizontal .form-group > [class*="col-"]{float:none !important;width:100% !important;max-width:none !important;padding:0 !important;margin:0 !important;}
  .fu-card form.form-horizontal .form-group .clearfix{display:block;}
  .fu-card form.form-horizontal .form-group .form-control,.fu-card form.form-horizontal .form-group textarea{width:100% !important;}
  .fu-card form.form-horizontal .form-group textarea{min-height:84px;height:auto;padding:10px 12px;}
  .fu-card form.form-horizontal .form-group:has(textarea),.fu-card form.form-horizontal .form-group:has(.wysiwyg-editor),.fu-card form.form-horizontal > hr,.fu-card form.form-horizontal > .formcont1heading,.fu-card form.form-horizontal > div:has(> .formcont1heading),.fu-card form.form-horizontal > .hr,.fu-card form.form-horizontal > .form-actions,.fu-card form.form-horizontal > .meta_nxtbtn,.fu-card form.form-horizontal > h3,.fu-card form.form-horizontal > .clearfix,.fu-card form.form-horizontal > div:has(button[type=submit]){grid-column:1 / -1;}
  .fu-card form.form-horizontal > .space-2,.fu-card form.form-horizontal > .space-4,.fu-card form.form-horizontal > .space-6{display:none;}
  .fu-card .chosen-container,.fu-card form.form-horizontal select.form-control,.fu-card form.form-horizontal select{width:100% !important;max-width:none !important;}
  .fu-card .chosen-container .chosen-single{height:42px !important;line-height:40px !important;border-radius:10px !important;border:1px solid #cfd8e3 !important;background:#fbfcfe !important;box-shadow:none !important;padding:0 12px !important;color:#14304a;}
  .fu-card .chosen-container .chosen-single div{top:8px;} .fu-card .chosen-container-multi .chosen-choices{min-height:42px;border-radius:10px !important;border:1px solid #cfd8e3 !important;background:#fbfcfe !important;padding:4px 8px;box-shadow:none !important;}
  .fu-card .chosen-container-multi .chosen-choices li.search-choice{background:#eef1f5 !important;border:0 !important;border-radius:5px !important;box-shadow:none !important;color:#22303f;}
  .fu-card .chosen-container .chosen-drop{border-radius:12px;border:1px solid #e4e9ef;box-shadow:0 18px 40px rgba(20,48,74,.2);overflow:hidden;margin-top:4px;}
  .fu-card .chosen-container .chosen-results li.highlighted{background:#14304a !important;color:#fff;}
  .fu-card form.form-horizontal > [class*="col-"]{float:none !important;width:100% !important;max-width:none !important;flex:none !important;padding:0 !important;margin:0 !important;}
  .fu-card form.form-horizontal > [class*="col-"] > label.control-label{float:none !important;width:100% !important;display:block;text-align:left !important;padding:0 !important;margin:0 0 6px !important;}
  .fu-card form.form-horizontal > [class*="col-"] .select2-container{width:100% !important;}
  .fu-card form.form-horizontal select.select2-hidden-accessible{position:absolute !important;width:1px !important;height:1px !important;opacity:0 !important;}
  .fu-card .select2-container--default .select2-selection--single,.fu-card .select2-container--default .select2-selection--multiple{min-height:42px !important;height:auto !important;border:1px solid #cfd8e3 !important;border-radius:10px !important;background:#fbfcfe !important;display:flex;align-items:center;padding:3px 8px;}
  .fu-card .select2-container--default .select2-selection--single .select2-selection__arrow{height:40px !important;}
  .fu-card .btn-next-cancel a,.fu-card .btn-next-cancel a.btn{border:0 !important;background:transparent !important;box-shadow:none !important;height:auto !important;line-height:inherit !important;padding:0 !important;color:inherit !important;border-radius:0 !important;}
  .fu-card .wizard-actions{grid-column:1 / -1;display:flex;justify-content:flex-end;gap:12px;align-items:center;}
  .fu-card form.form-horizontal hr{border:0;border-top:1px dashed #e4e9ef;margin:4px 0;}
  .fu-card form.form-horizontal .btn-primary,.fu-card form.form-horizontal button[type=submit]{height:42px;padding:0 28px;border:0;border-radius:10px;font-weight:700;color:#fff !important;background:linear-gradient(120deg,#1b6ca8,#14304a);box-shadow:0 6px 16px rgba(27,108,168,.3);}
  .fu-card form.form-horizontal .btn-default,.fu-card form.form-horizontal .btn-secondary,.fu-card form.form-horizontal a.btn:not(.btn-primary){height:42px;line-height:26px;padding:7px 22px;border-radius:10px;background:#fff;color:#14304a !important;border:1px solid #cfd8e3;}
  @media(max-width:900px){.fu-card form.form-horizontal{grid-template-columns:repeat(2,minmax(0,1fr));}} @media(max-width:600px){.fu-card form.form-horizontal{grid-template-columns:1fr;}}
</style>
<?php
}

function fu_head($form_id)
{
	include_once __DIR__.'/ea-ui.php';
	ob_start(); ea_ui_css(); $css = ob_get_clean();
	echo str_replace('#add_article', '#' . $form_id, $css);
	include_once __DIR__.'/ui_filters.php'; ui_filters_css();
?>
<style>
  #<?php echo $form_id; ?> .select2-search--inline .select2-search__field{height:28px !important;min-height:0 !important;width:auto !important;min-width:120px;margin:2px 0 !important;padding:0 4px !important;border:0 !important;border-radius:0 !important;background:transparent !important;box-shadow:none !important;line-height:20px !important;resize:none !important;overflow:hidden !important;}
  #<?php echo $form_id; ?> label.fu-msg{display:block;color:#d64545 !important;font-size:12px !important;font-weight:600 !important;text-transform:none !important;margin:5px 0 0 !important;}
  .fu-bad + .select2-container .select2-selection{border-color:#d64545 !important;background:#fff8f8 !important;}
  #ea-dlg.fu .box:before{background:linear-gradient(90deg,#d64545,#f0a0a0);}
  #ea-dlg.fu .ic{background:#fdeaea;color:#d64545;}
  #ea-dlg.fu li{border-left-color:#d64545;background:#fff8f8;border-color:#f3d4d4;cursor:pointer;}
  #ea-dlg.fu li:hover{background:#ffeeee;}
</style>
<?php
}

/* header box: title, required counter, live summary chips */
function fu_hero($ui)
{
	$e = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES); };
?>
      <div class="ea-hero">
        <div class="ea-bar">
          <a class="ea-back" href="javascript:history.back();" title="Back"><i class="fa fa-arrow-left"></i></a>
          <div><h1 class="ea-title"><?php echo $e($ui['title']); ?></h1><div class="ea-crumb"><?php echo $ui['crumb']; ?></div></div>
          <span class="ea-sp"></span>
          <span class="ea-req" id="ea-req" title="Required fields filled in"><span id="ea-req-t">Required 0 / 0</span><span class="track"><span class="fill" id="ea-req-f"></span></span></span>
        </div>
<?php if (!empty($ui['idField'])) { ?>
        <div class="ea-sum">
          <div><small><?php echo $e($ui['kind']); ?></small><div class="ea-sum-id" id="fu-sum-id">&mdash;</div><div class="ea-sum-sub" id="fu-sum-sub"><?php echo $e($ui['sub0']); ?></div></div>
          <div class="ea-chips">
<?php foreach ($ui['chips'] as $i => $c) { ?>
            <div class="ea-chip"><small><?php echo $e($c[0]); ?></small><b id="fu-chip-<?php echo $i; ?>">&mdash;</b></div>
<?php } ?>
          </div>
        </div>
<?php } ?>
      </div>
<?php
}

/* ---------- popup + script ---------- */
function fu_foot($form_sel, $rules, $server_errors = array(), $posted = null, $ui = null)
{
	if (!is_array($rules)) { $rules = array('required' => array(), 'id' => array()); }
	$cfg = array('form' => $form_sel, 'rules' => $rules, 'server' => array_values($server_errors), 'ui' => $ui, 'auto' => !empty($ui['auto']),
	             'posted' => ($posted && $server_errors) ? $posted : null);   /* add pages: put the user's entries back after a failed save */
?>
<div id="ea-bar"></div>
<div id="ea-overlay"><div class="ring"></div><div class="t">Saving&hellip;</div><div class="s">Please wait, do not close this tab</div></div>
<div id="ea-dlg" class="fu" role="alertdialog" aria-modal="true"><div class="box"><div class="in">
  <div class="ic">!</div><h3 id="fu-t">Please check the form</h3><p id="fu-s"></p><ul id="fu-l"></ul>
  <div class="btns"><button type="button" class="ea-dbtn primary" id="fu-ok">OK, fix it</button></div>
</div></div></div>
<script>
(function () {
  var C = <?php echo json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>;
  var form = document.querySelector(C.form); if (!form) { return; }
  form.setAttribute('novalidate', 'novalidate');                       /* one popup for everything instead of browser bubbles */
  var dlg = document.getElementById('ea-dlg'), list = document.getElementById('fu-l'), firstEl = null, submitting = false, dirty = false;
  var R = C.rules.required || {}, IDS = C.rules.id || [];
  /* basic pages: the required fields are the ones the form itself marks `required` (checkbox / radio groups are left to the page) */
  if (C.auto) {
    R = {};
    [].forEach.call(form.querySelectorAll('[required]'), function (el) {
      if (!el.name || el.disabled || /^(hidden|checkbox|radio|file|submit|button)$/.test(el.type)) { return; }
      var wrap = el.closest('[class*="col-"]'), lb = wrap && wrap.querySelector('label'), txt = lb ? lb.textContent.replace(/[*:]/g, '').replace(/\s+/g, ' ').trim() : '';
      R[el.name] = txt || el.name.replace(/\[\]$/, '').replace(/_/g, ' ');
    });
  }

  function field(n) { return n ? (form.elements[n] || null) : null; }
  function host(el) { return el.nextElementSibling && el.nextElementSibling.classList.contains('select2-container') ? el.nextElementSibling : el; }
  function val(n) { var el = field(n); return el && el.value != null ? String(el.value).trim() : ''; }
  function text(n) { var el = field(n); if (!el) { return ''; } if (el.tagName === 'SELECT') { return el.value ? (el.options[el.selectedIndex].text || '').replace(/\s+/g, ' ').trim() : ''; } return String(el.value || '').trim(); }

  function clearMarks() {
    [].forEach.call(form.querySelectorAll('.fu-bad'), function (e) { e.classList.remove('fu-bad', 'error'); });
    [].forEach.call(form.querySelectorAll('.fu-msg'), function (e) { e.parentNode.removeChild(e); });
  }
  function mark(el, msg) {
    if (!el) { return; }
    el.classList.add('fu-bad', 'error');
    var h = host(el);
    if (!h.parentNode.querySelector('.fu-msg[data-for="' + el.name + '"]')) {
      var m = document.createElement('label'); m.className = 'error fu-msg'; m.setAttribute('data-for', el.name); m.textContent = msg;
      h.parentNode.insertBefore(m, h.nextSibling);
    }
  }
  function clearOne(el) { if (!el || !el.classList) { return; } el.classList.remove('fu-bad', 'error'); var m = form.querySelector('.fu-msg[data-for="' + el.name + '"]'); if (m) { m.parentNode.removeChild(m); } }

  function show(items, title) {
    list.innerHTML = '';
    items.forEach(function (it) {
      var li = document.createElement('li'); li.innerHTML = '<b></b><span></span>';
      li.firstChild.textContent = it.label || 'Error'; li.lastChild.textContent = it.msg;
      li.onclick = function () { hide(); focusField(it.field); };
      list.appendChild(li);
    });
    document.getElementById('fu-t').textContent = title || (items.length === 1 ? 'One thing needs your attention' : items.length + ' things need your attention');
    document.getElementById('fu-s').textContent = 'Nothing was saved. Click an item to jump to it.';
    dlg.classList.add('on'); firstEl = items.length ? items[0].field : null;
    setTimeout(function () { document.getElementById('fu-ok').focus(); }, 30);
  }
  function hide() { dlg.classList.remove('on'); }
  function focusField(n) {
    var el = field(n); if (!el) { return; }
    host(el).scrollIntoView({ behavior: 'smooth', block: 'center' });
    try { el.focus({ preventScroll: true }); } catch (e) {}
  }
  document.getElementById('fu-ok').onclick = function () { hide(); if (firstEl) { focusField(firstEl); } };
  dlg.addEventListener('click', function (e) { if (e.target === dlg) { hide(); } });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && dlg.classList.contains('on')) { hide(); } });

  function check() {
    var out = [];
    Object.keys(R).forEach(function (f) {
      if (!field(f)) { return; }
      var v = val(f);
      if (v === '' || (IDS.indexOf(f) > -1 && v === '0')) { out.push({ field: f, label: R[f], msg: 'is required - please choose or enter a value.' }); }
    });
    return out;
  }
  function flag(items) { clearMarks(); items.forEach(function (it) { mark(field(it.field), (it.label ? it.label + ' ' : '') + it.msg); }); }

  /* red star only on what is really required (the old stars on optional fields go) */
  [].forEach.call(form.querySelectorAll('label'), function (lb) {
    var wrap = lb.closest('[class*="col-"]'), el = wrap && wrap.querySelector('input,select,textarea');
    if (!el || !el.name) { return; }
    [].forEach.call(lb.querySelectorAll('span[style*="red"],.fu-req'), function (s) { s.parentNode.removeChild(s); });
    if (R[el.name]) { var s = document.createElement('span'); s.setAttribute('style', 'color: red'); s.textContent = ' *'; lb.appendChild(s); }
  });

  /* Cancel was a submit button wrapped around a link: make it a plain button that just goes to the link */
  [].forEach.call(form.querySelectorAll('.btn-next-cancel'), function (b) {
    b.setAttribute('type', 'button');
    b.addEventListener('click', function (e) { var a = b.querySelector('a'); if (a && e.target.tagName !== 'A') { dirty = false; location.href = a.href; } });
  });

  /* header: required counter + live summary */
  function hero() {
    var keys = Object.keys(R).filter(function (f) { return field(f); }), done = 0;
    keys.forEach(function (f) { var v = val(f); if (v !== '' && !(IDS.indexOf(f) > -1 && v === '0')) { done++; } });
    var t = document.getElementById('ea-req-t'), fl = document.getElementById('ea-req-f'), rq = document.getElementById('ea-req');
    if (t) { t.textContent = 'Required ' + done + ' / ' + keys.length; fl.style.width = (keys.length ? Math.round(done * 100 / keys.length) : 100) + '%'; rq.classList.toggle('done', keys.length > 0 && done === keys.length); }
    var U = C.ui; if (!U) { return; }
    var id = text(U.idField), idEl = document.getElementById('fu-sum-id'), sub = document.getElementById('fu-sum-sub');
    if (idEl) { idEl.textContent = id || '—'; }
    if (sub) { var parts = (U.subFields || []).map(text).filter(Boolean); sub.textContent = parts.length ? parts.join(' · ') : U.sub0; }
    (U.chips || []).forEach(function (c, i) { var b = document.getElementById('fu-chip-' + i); if (b) { b.textContent = text(c[1]) || '—'; } });
  }
  function touched(e) { dirty = true; clearOne(e.target); hero(); }
  form.addEventListener('input', touched); form.addEventListener('change', touched);
  if (window.jQuery) { window.jQuery(form).on('change', 'select', function (e) { hero(); }); }

  var bar = document.getElementById('ea-bar');
  form.addEventListener('submit', function (e) {
    var errs = check();
    if (errs.length) { e.preventDefault(); e.stopImmediatePropagation(); flag(errs); show(errs); return false; }
    if (submitting) { e.preventDefault(); return false; }                       /* double-click guard */
    submitting = true; dirty = false;
    document.getElementById('ea-overlay').classList.add('on'); bar.style.opacity = 1; bar.style.width = '70%';
    var b = form.querySelector('button.btn-next'); if (b) { b.setAttribute('disabled', 'disabled'); }
  }, true);

  window.addEventListener('beforeunload', function (e) { if (dirty && !submitting) { e.preventDefault(); e.returnValue = ''; } });

  /* add pages: restore the entries after a failed save (the fields were rendered with their defaults) */
  if (C.posted) {
    setTimeout(function () {
      Object.keys(C.posted).forEach(function (n) {
        var el = form.elements[n]; if (!el || !el.tagName || el.type === 'file') { return; }
        if (el.type === 'checkbox') { el.checked = String(C.posted[n]) !== '' && String(C.posted[n]) !== '0'; } else { el.value = C.posted[n]; }
        if (window.jQuery) { window.jQuery(el).trigger('change.select2').trigger('change'); }
      });
      dirty = false; hero();
    }, 60);
  }

  /* problems found by the server (validation or database): same popup, fields marked */
  if (C.server && C.server.length) { flag(C.server); show(C.server, 'The form could not be saved'); }
  setTimeout(function () { hero(); dirty = false; }, 250);
})();
</script>
<?php
}
