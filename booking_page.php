<?php /* Revise / Final booking page template - included at page level by rev_project.php / fin_project.php (data: $o from bk_run) */
	$bk_kind = $o['kind']; $bk_app = $o['app_res'];
	$bk_isfin = ($bk_kind === 'FIN');
	$bk_title = $bk_kind === 'JSTAGE' ? 'Book JSTAGE Article' : ($bk_isfin ? 'Book Final Article' : 'Book Revise Article');
	$bk_booked = $bk_kind.$o['revision_count'];
	$bk_back = $_COOKIE['set_url'].'/underclientreview';
	$bk_fmt = function ($d) { return ($d && $d !== '0000-00-00' && strtotime($d)) ? date('d-m-Y', strtotime($d)) : '-'; };
	$bk_due = ($bk_app['stage'] === 'FP') ? $bk_app['target_due_dt'] : $bk_app['due_dt'];
	$bk_wf1 = null; foreach ($o['wfmap'] as $bk_id => $bk_w) { $bk_wf1 = $bk_id; break; }
	$bk_sel = $o['selected_wf'] !== '' ? $o['selected_wf'] : $bk_wf1;
	$bk_flow = $o['flow'];
	$bk_prev = array();
	foreach (array_slice($bk_flow['files'], 0, 8) as $bk_x) { $bk_prev[] = $bk_x[0]; }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php bk_css(); ?>
  </head>
  <body>
    <div id="bk-bar"></div>
    <div id="bk-overlay"><div class="ring"></div><div class="t" id="bk-ot">Booking&hellip;</div><div class="s">Moving the files &mdash; please do not close this tab</div></div>
    <div id="bk-dlg"><div class="box"><div class="in"><div class="ic" id="bk-dic">!</div><h3 id="bk-dt"></h3><div id="bk-dm"></div><div class="btns" id="bk-db"></div></div></div></div>
    <div class="wholeouter">
    <?php include("includes/top-header.php"); ?>

      <div class="bk-hero">
        <div class="bk-bar">
          <a class="bk-back" href="<?php echo bk_h($bk_back); ?>" title="Back to Under Client Review"><i class="fa fa-arrow-left"></i></a>
          <div><h1 class="bk-title"><?php echo bk_h($bk_title); ?></h1><div class="bk-crumb"><a href="<?php echo bk_h($bk_back); ?>">Under Client Review</a> &rsaquo; <?php echo ($bk_kind === 'JSTAGE' ? 'JSTAGE' : ($bk_isfin ? 'Final' : 'Revise')); ?></div></div>
          <span class="bk-sp"></span><span class="bk-id" title="<?php echo bk_h($bk_app['pub_id']); ?>">#<?php echo bk_h($bk_app['pub_id']); ?></span>
        </div>
        <div class="bk-sum">
          <div><small>Article ID</small><div class="bk-sum-id">#<?php echo bk_h($bk_app['pub_id']); ?></div><div class="bk-sum-sub"><?php echo bk_h($o['customer_info']['cust_name']); ?> &middot; <?php echo bk_h($o['journal_info']['j_code']); ?></div></div>
          <div class="bk-chips">
            <div class="bk-chip"><small>Stage</small><b><?php echo bk_h($bk_app['stage']); ?></b></div>
            <div class="bk-chip"><small>Department</small><b><?php echo bk_h($bk_app['status']); ?></b></div>
            <div class="bk-chip"><small>Received</small><b><?php echo bk_h($bk_fmt($bk_app['recv_dt'])); ?></b></div>
            <div class="bk-chip"><small>Due date</small><b><?php echo bk_h($bk_fmt($bk_due)); ?></b></div>
            <div class="bk-chip"><small>Pages</small><b><?php echo (int)$bk_app['page_count']; ?></b></div>
            <div class="bk-chip hot"><small>Booking as</small><b><?php echo bk_h($bk_booked); ?></b></div>
          </div>
        </div>
      </div>

      <div class="bk-card">
        <form id="bk-form" method="post" novalidate autocomplete="off">
          <input type="hidden" name="action" value="insert" />
          <input type="hidden" name="revision_count" value="<?php echo (int)$o['revision_count']; ?>" />
          <input type="hidden" name="inw_id" value="<?php echo (int)$bk_app['id']; ?>" />
          <input type="hidden" name="cust_name" value="<?php echo bk_h($o['customer_info']['cust_name']); ?>" />
          <input type="hidden" name="j_code" value="<?php echo bk_h($o['journal_info']['j_code']); ?>" />
          <input type="hidden" name="pub_id" value="<?php echo bk_h($bk_app['pub_id']); ?>" />
          <input type="hidden" name="volume_folder" value="<?php echo bk_h($bk_app['volume_folder']); ?>" />
          <input type="hidden" name="work_dir" value="<?php echo bk_h($bk_app['work_dir']); ?>" />
          <input type="hidden" name="prev_stage" value="<?php echo bk_h($bk_app['stage']); ?>" />
          <input type="hidden" name="stage" value="<?php echo bk_h($bk_booked); ?>" />

          <h2 class="bk-h">Booking details</h2>
          <div class="bk-grid">
            <div class="bk-f" data-f="recv_dt"><label for="recv_dt">Received date<i>*</i></label><input type="text" id="recv_dt" name="recv_dt" class="date-picker" value="<?php echo date('d-m-Y'); ?>" /><div class="bk-msg"></div></div>
            <div class="bk-f" data-f="due_dt"><label for="due_dt">Due date<i>*</i></label><input type="text" id="due_dt" name="due_dt" class="date-picker" value="<?php echo date('d-m-Y'); ?>" /><div class="bk-msg"></div></div>
            <div class="bk-f" data-f="correction_pages"><label for="correction_pages"><?php echo ($bk_kind === 'REV' ? 'Correction pages' : 'Pages'); ?><i>*</i></label><input type="number" min="0" step="1" id="correction_pages" name="correction_pages" value="<?php echo (int)$bk_app['page_count']; ?>" /><div class="bk-msg"></div></div>
            <div class="bk-f"><label>Stage</label><div class="bk-static"><?php echo bk_h($bk_booked); ?> &nbsp;<span style="font-weight:600;font-size:12px;color:#8a6d3b;">(<?php echo ($bk_kind === 'JSTAGE' ? 'JSTAGE' : ($bk_isfin ? 'Final' : 'Revise')); ?>)</span></div></div>
            <div class="bk-f w6" data-f="wf_id"><label for="wf_id">Workflow<i>*</i></label>
              <select id="wf_id" name="wf_id">
                <option value="">- Select -</option>
                <?php foreach ($o['wfmap'] as $bk_id => $bk_w) { ?><option value="<?php echo bk_h($bk_id); ?>"<?php echo ((string)$bk_id === (string)$bk_sel) ? ' selected' : ''; ?>><?php echo bk_h($bk_w['name']); ?></option><?php } ?>
              </select><div class="bk-msg"></div></div>
            <div class="bk-f w12"><label for="instructions">Instructions</label><textarea id="instructions" name="instructions" placeholder="Anything the first department should know about this <?php echo ($bk_kind === 'JSTAGE' ? 'JSTAGE round' : ($bk_isfin ? 'final' : 'revision')); ?> (optional)"></textarea></div>
          </div>

          <h2 class="bk-h">What happens when you book</h2>
          <div class="bk-flow">
            <div><h4>Workflow</h4><div class="bk-steps" id="bk-steps"></div><div class="bk-note" style="margin-top:8px;" id="bk-first"></div></div>
            <div><h4>File flow</h4>
              <div class="bk-note">
              <?php if ($bk_flow['exists']) { ?>
                <b><?php echo (int)$bk_flow['moved_files']; ?></b> file(s) in <b><?php echo bk_h($bk_app['stage']); ?></b> move to <b><?php echo bk_h($bk_booked); ?></b>.
                <?php if ($bk_flow['skipped_folders']) { $bk_sk = array(); foreach ($bk_flow['skipped_folders'] as $bk_m) { $bk_sk[] = $bk_m[0]; } ?><br><span class="muted">Stay behind in <?php echo bk_h($bk_app['stage']); ?>: <?php echo bk_h(implode(', ', $bk_sk)); ?></span><?php } ?>
                <?php if ($bk_prev) { ?><ul class="bk-files"><?php foreach ($bk_prev as $bk_fn) { ?><li><i class="fa fa-file-o"></i><?php echo bk_h($bk_fn); ?></li><?php } if (count($bk_flow['files']) > 8) { ?><li class="muted">&hellip; and <?php echo count($bk_flow['files']) - 8; ?> more</li><?php } ?></ul><?php } ?>
              <?php } else { ?>
                <span style="color:#b87800;">The <b><?php echo bk_h($bk_app['stage']); ?></b> folder was not found on the server, so no files will be moved.</span>
              <?php } ?>
              <br><span class="muted">A folder for the first department is created in <?php echo bk_h($bk_booked); ?>, then the upload page opens for the new files. An e-mail alert goes to the booking recipients.</span>
              </div>
            </div>
          </div>

          <div class="bk-actions">
            <a class="bk-btn" href="<?php echo bk_h($bk_back); ?>" id="bk-cancel">Cancel</a>
            <button type="button" class="bk-btn primary" id="bk-submit"><i class="fa fa-check"></i> Book as <?php echo bk_h($bk_booked); ?></button>
          </div>
        </form>
      </div>

      <?php echo footer(); ?>
      <?php echo scripts(); ?>
<script>
(function ($) {
  var WF = <?php echo json_encode($o['wfmap']); ?>, BOOKED = <?php echo json_encode($bk_booked); ?>, PREV = <?php echo json_encode($bk_app['stage']); ?>,
      $bar = $('#bk-bar'), timer = null, val = 0, sending = false;

  $('.date-picker').datepicker({ uiLibrary: 'bootstrap5', dateFormat: 'dd-mm-yy', autoclose: true });

  /* workflow preview */
  function steps() {
    var w = WF[$('#wf_id').val()], $s = $('#bk-steps').empty(), $f = $('#bk-first').empty();
    if (!w || !w.depts.length) { $s.append('<span class="bk-note muted">Choose a workflow to see its departments.</span>'); return; }
    $.each(w.depts, function (i, d) {
      if (i) { $s.append('<span class="bk-arrow"><i class="fa fa-chevron-right"></i></span>'); }
      $s.append($('<span class="bk-step' + (i === 0 ? ' first' : '') + '"></span>').text(d).css('animation-delay', (i * 0.05) + 's'));
    });
    $f.html('First department: <b></b> &mdash; the article goes there as <b></b>.'); $f.find('b').eq(0).text(w.depts[0]); $f.find('b').eq(1).text(BOOKED);
  }
  $('#wf_id').on('change', function () { steps(); setErr('wf_id', ''); }); steps();

  /* validation: inline messages + one summary dialog */
  function setErr(name, msg) { var $f = $('.bk-f[data-f="' + name + '"]'); $f.toggleClass('err', !!msg); $f.find('.bk-msg').text(msg || ''); }
  function parse(s) { var m = /^(\d{2})-(\d{2})-(\d{4})$/.exec($.trim(s)); if (!m) { return null; } var d = new Date(+m[3], +m[2] - 1, +m[1]); return (d.getFullYear() === +m[3] && d.getMonth() === +m[2] - 1 && d.getDate() === +m[1]) ? d : null; }
  function validate() {
    var errs = [], r = parse($('#recv_dt').val()), d = parse($('#due_dt').val()), p = $.trim($('#correction_pages').val());
    ['recv_dt', 'due_dt', 'correction_pages', 'wf_id'].forEach(function (n) { setErr(n, ''); });
    if (!r) { setErr('recv_dt', 'Enter a valid date (dd-mm-yyyy).'); errs.push('Received date – enter a valid date (dd-mm-yyyy).'); }
    if (!d) { setErr('due_dt', 'Enter a valid date (dd-mm-yyyy).'); errs.push('Due date – enter a valid date (dd-mm-yyyy).'); }
    else if (r && d < r) { setErr('due_dt', 'Due date cannot be before the received date.'); errs.push('Due date – cannot be before the received date.'); }
    if (p === '' || !/^\d+$/.test(p)) { setErr('correction_pages', 'Enter the number of pages (0 or more).'); errs.push('<?php echo ($bk_kind === 'REV' ? 'Correction pages' : 'Pages'); ?> – enter a whole number (0 or more).'); }
    if (!$('#wf_id').val()) { setErr('wf_id', 'Choose a workflow.'); errs.push('Workflow – please choose one.'); }
    return errs;
  }

  /* dialog */
  function dlg(o) {
    $('#bk-dlg').toggleClass('err', !!o.error).addClass('on'); $('#bk-dic').text(o.error ? '!' : '?'); $('#bk-dt').text(o.title);
    var $m = $('#bk-dm').empty();
    if (o.list) { $m.append($('<p></p>').text(o.text || '')); var $u = $('<ul></ul>'); $.each(o.list, function (i, t) { $u.append($('<li></li>').text(t)); }); $m.append($u); } else { $m.append($('<p></p>').html(o.html || '')); }
    var $b = $('#bk-db').empty();
    $.each(o.buttons, function (i, b) { $('<button type="button" class="bk-btn' + (b.primary ? ' primary' : '') + '"></button>').text(b.text).on('click', function () { $('#bk-dlg').removeClass('on'); if (b.cb) { b.cb(); } }).appendTo($b); });
  }

  function loaderOn() {
    $('#bk-overlay').addClass('on'); clearInterval(timer); $bar.addClass('on'); val = 6; $bar.css('width', val + '%');
    timer = setInterval(function () { val = val + (92 - val) * 0.05; $bar.css('width', val + '%'); }, 220);
  }

  $('#bk-submit').on('click', function () {
    if (sending) { return; }
    var errs = validate();
    if (errs.length) { dlg({ error: true, title: 'Please complete the required fields', list: errs, buttons: [{ text: 'OK', primary: true, cb: function () { $('.bk-f.err').first().find('input,select').first().focus(); } }] }); return; }
    var w = WF[$('#wf_id').val()] || { name: '', depts: [''] };
    dlg({ title: 'Book as ' + BOOKED + '?',
          html: 'The files in <b></b> will be moved to <b></b> and the article goes to <b></b> (<b></b>). This cannot be undone from this page.',
          buttons: [{ text: 'Cancel' }, { text: 'Book now', primary: true, cb: function () {
            sending = true; $('#bk-submit').prop('disabled', true); loaderOn(); $('#bk-form')[0].submit(); } }] });
    var $b = $('#bk-dm b'); $b.eq(0).text(PREV); $b.eq(1).text(BOOKED); $b.eq(2).text(w.depts[0]); $b.eq(3).text(w.name);
  });
  $('#bk-form').on('keydown', function (e) { if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') { e.preventDefault(); $('#bk-submit').click(); } });
  $('#recv_dt,#due_dt,#correction_pages').on('input change', function () { setErr(this.name, ''); });
})(jQuery);
</script>
    </div>
  </body>
</html>
