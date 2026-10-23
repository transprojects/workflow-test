<?php include "includes/includes.php";?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>	
    <?php include_once 'includes/ea-ui.php'; ea_ui_css(); ?>

    <!-- Data Table CSS --->
    <style>
      .overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: none;
}

.loader-container {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 1001;
    display: none;
}

.loader {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 2s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
      </style>
  </head>

  <body>
  <div id="loader" class="loader-container">
        <div class="loader"></div>
    </div>
    <div id="overlay" class="overlay"></div>
    <div class="wholeouter">
    <?php include("includes/top-header.php");?>


      <!-- form section  -->
      <div class="container-fluid mt-4">

      <div div class="row pt-4 d-flex align-items-center flex-wrap">
          <div class="col-8 col-lg-10">
            <p class="articleheading ms-4">Article Information</p>
          </div>

          <div class="col-4 col-lg-2">
            <div class="row d-flex justify-content-end pe-2">
              <div class="col-3 articlebtns ">
                <a href="javascript:history.back();">
                  <div  class="back_icon"><img src="<?php echo $_COOKIE['set_url'];?>/assets/images/backarrow.svg"></div>
                </a>
              </div>
            </div>
          </div>
        </div> 
  </div>
         
       
      <div class="ea-hero">
        <div class="ea-bar">
          <a class="ea-back" href="javascript:history.back();" title="Back"><i class="fa fa-arrow-left"></i></a>
          <div><h1 class="ea-title">Add Article</h1><div class="ea-crumb">Article &rsaquo; Add</div></div>
          <span class="ea-sp"></span>
          <a class="ea-refresh" id="ea-refresh" href="#" title="Refresh - start again with an empty form"><i class="fa fa-refresh"></i></a>
          <span class="ea-req" id="ea-req" title="Required fields filled in"><span id="ea-req-t">Required 0 / 0</span><span class="track"><span class="fill" id="ea-req-f"></span></span></span>
        </div>
        <div class="ea-created" id="aa-created"><i class="fa fa-check-circle"></i><span>Article created. Now upload its files below.</span></div>
        <div class="ea-sum">
          <div><small>New article</small><div class="ea-sum-id" id="aa-pub">#&mdash;</div><div class="ea-sum-sub" id="aa-sub">Choose a customer and journal to begin</div></div>
          <div class="ea-chips">
            <div class="ea-chip"><small>Stage</small><b id="aa-stage">&mdash;</b></div>
            <div class="ea-chip"><small>Workflow</small><b id="aa-wf">&mdash;</b></div>
            <div class="ea-chip hot"><small>Starts in</small><b id="aa-first">&mdash;</b></div>
          </div>
        </div>
      </div>

      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
          <form action="" method="post" name="add_article" id="add_article" enctype="multipart/form-data">
            <div class="container mt-4 separatetitle">
              <div class="formcont1heading">Article Basic Information</div>
              <div class="row mt-1 g-4 addspacing">
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Choose Customer <span style="color: red">*</span></label>
                  <select class="form-select customer_drop" data-placeholder="Select Customer" name="cust_id" id="cust_id" required>
                    <option value="">Select Customer</option>
                   <?php
											$sql_customers = $auth->showCustomerlist();
											foreach($sql_customers as $res_customers)
											{
												
									      	if(isset($_REQUEST['cust_id']))
													{
										    	?>
										       <option <?php if (in_array($res_customers['id'], $ex_cid)){?>selected<?php }?> value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
													<?php
													}
													else
													{
													?>
													 <option value="<?php echo $res_customers['id'] ?>"style="font-weight:500;"><?php echo $res_customers['cust_name'] ?> </option>
													<?php
													}
											}
											?>
                </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Choose Journal <span style="color: red">*</span></label>
                  <select class="form-select journal_drop" data-placeholder="Select Journal" name="j_id" id="j_id" required>
                  <option value="">Select Journal</option>

                    <?php
											$sql_journals = $auth->showJournallist('0',2);
											foreach($sql_journals as $res_journals)
											{
												
									      	if(isset($_REQUEST['j_id']))
													{
										    	?>
										       <option <?php if (in_array($res_journals['j_id'], $ex_jid)){?>selected<?php }?> value="<?php echo $res_journals['j_id'] ?>" style="font-weight:500;" data-id="<?php echo $res_journals['j_code'] ?>_0000" data-previous="<?php echo $res_journals['j_code'] ?>_0000"><?php echo $res_journals['j_code'] ?> </option>
													<?php
													}
													else
													{
													?>
													 <option value="<?php echo $res_journals['j_id'] ?>" style="font-weight:500;" data-id="<?php echo $res_journals['j_code'] ?>_0000" data-previous="<?php echo $res_journals['j_code'] ?>_0000"><?php echo $res_journals['j_code'] ?> </option>
													<?php
													}
											}
											?>
                </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Choose Group Folder </label>
                  <select class="form-select" aria-label="search-input" id="group_folder_name" name="group_folder_name">
                  <option value="">Select Group Folder</option>

                  </select>
                </div>
				
				 <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Choose Article Type</label>
				     <?php
		$query = "SELECT * from inw_article_type ORDER BY `at_title` ASC ";
		$article_type = $db->query($query) or ($db->error);		
		
		?>
                  <select class="form-select journal_drop" data-placeholder="Select Article Type" name="at_id" id="at_id">
                  <option value="">Select Article Type</option>
   <?php while($row_at = $article_type->fetch_assoc()){ ?>
                                  <option value="<?php echo $row_at['at_id']; ?>"><?php echo $row_at['at_title']; ?></option>
                                  <?php } ?>
                    
                </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Article ID</label>
                 <input type="text" class="form-control" name="pub_id" id="pub_id" placeholder="Enter Article ID" required>
                </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Article Title <span style="color: red">*</span></label>
              <input type="text" class="form-control" name="article_title" id="artitle_title" placeholder="Enter Article Title" required>
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Received Date</label>
              <div class="d-flex">
                  <input type="text" class="form-control monthlyinputdate" name="recv_dt" id="recv_dt" required placeholder="Select Received Date" value="<?php echo date('d-m-Y'); ?>" readonly/>
                </div>
              </Input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Due Date</label>
             <div class="d-flex">
                  <input type="text" class="form-control monthlyinputdate1" name="due_dt" id="due_dt" required placeholder="Select Due Date" value="<?php echo date('d-m-Y'); ?>" readonly/>
                </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-4 d-flex align-items-end">
                <div class="col-6 col-lg-6 ">
                    <input type="checkbox" name="test_article" id="test_article" value="1"><label class="ms-2">Is Test Article</label>
                </div>
                 <div class="col-6 col-lg-6 ">
                    <input type="checkbox" name="highpriority" id="highpriority" value="1"><label class="ms-2">Is High Priority</label>
                </div>
				 <div class="col-6 col-lg-6 ">
                    <input type="checkbox" name="is_conversion" id="is_conversion" value="yes"><label class="ms-2">Is Conversion Article</label>
                </div>
                </div>
                </div>
            </div>
            <!-- heading 2 -->
            <div class="container mt-4 separatetitle">
              <div class="formcont1heading">Article Flow</div>
              <div class="row mt-1 g-4 addspacing">
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Manuscript Count </label>
                  <input type="number" class="form-control" name="manuscript_count" id="manuscript_count" required  placeholder="Enter Manuscript Count" value="0">
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Fig Count </label>
                  <input type="number" class="form-control" name="fig" id="fig" placeholder="Enter Fig Count" value="0" required>

              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Table Count </label>
                  <input type="number" class="form-control" name="tab" id="tab" placeholder="Enter Table Count" value="1" required>

                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">Stage <span style="color: red">*</span></label>
                 <select class="form-select" aria-label="search-input" id="stage" name="stage" required>
                 <?php foreach($project_status_types as $id=>$val){?>
                          <?php 
                          if(isset($_REQUEST['stage']))
													{
                          ?>
                         <option value="<?php echo $id; ?>" <?php if (in_array($id, $ex_stage)){?>selected<?php }?> ><?php echo $val; ?></option>
                         <?php 
                          }
                          else
                          {
                          ?>
                         <option value="<?php echo $id; ?>" ><?php echo $val; ?></option>
                          <?php 
                          }
                          ?>
                   <?php } ?>
                  </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Choose Workflow <span style="color: red">*</span></label>
              <select class="form-select" aria-label="search-input" id="wf_id" name="wf_id" required>
                   
                  </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Input Type <span style="color: red">*</span></label>
              <select class="form-select" aria-label="search-input" id="multiple-select-field" name="inw_type" id="inw_type" required>
              <?php
											$sql_inwardtype = $auth->getAllinwardtype();
											foreach($sql_inwardtype as $res_inwardtype)
											{
												
									      	if(isset($_REQUEST['inw_type']))
													{
										    	?>
										       <option <?php if (in_array($res_inwardtype['type_name'], $ex_jid)){?>selected<?php }?> value="<?php echo $res_inwardtype['type_name'] ?>"style="font-weight:500;"><?php echo $res_inwardtype['type_name'] ?> </option>
													<?php
													}
													else
													{
													?>
													 <option value="<?php echo $res_inwardtype['type_name'] ?>"style="font-weight:500;"><?php echo $res_inwardtype['type_name'] ?> </option>
													<?php
													}
											}
											?>
                  </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Complexity <span style="color: red">*</span></label>
               <select class="form-select" aria-label="search-input" id="multiple-select-field" name="complexity" id="complexity" required>
               <?php foreach($complexity_types as $val){?>
                                  <option value="<?php echo $val; ?>" <?php if($val=='Regular'){?> selected <?php }?>><?php echo $val; ?></option>
               <?php } ?>
                  </select>
                </div>
               
                </div>
            </div>
            <!-- heading 3 -->
            <div class="container mt-4 ">
              <div class="formcont1heading">Author Details</div>
              <div class="row mt-2 g-4 addspacing">
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Corresponding Author <span style="color: red">*</span> </label>
                  <input type="text" class="form-control" name="corresponding_author" id="corresponding_author" placeholder="Enter Corresponding Author" required>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">Corresponding Mail <span style="color: red">*</span></label>
                  <input type="text" class="form-control" name="corresponding_mail" id="corresponding_mail" placeholder="Enter Corresponding Author Mail" required>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                  <label class="mb-2">E Location ID</label>
                  <input type="text" class="form-control" name="location_id" id="location_id" placeholder="Enter E Location ID">
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">DOI</label>
                 <input type="text" class="form-control" name="doi" id="doi" placeholder="Enter DOI">
              </input>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
                 <label class="mb-2">PUB Date</label>
                <div class="d-flex">
                <input type="text" class="form-control monthlyinputdate" name="pub_dt" id="pub_dt" placeholder="Select PUB Date" value="<?php echo date('d-m-Y'); ?>" required>
                </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Accepted Date <span style="color: red">*</span></label>
              <div class="d-flex">
              <input type="text" class="form-control monthlyinputdate" name="accpt_dt" id="accpt_dt" placeholder="Select Accepted Date" value="<?php echo date('d-m-Y'); ?>" required>
                </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Instructions </label>
              <textarea type="text" class="form-control" name="instructions" id="instructions"></textarea>

                </div>
                <div class="col-12 col-sm-6 col-lg-4">
              <label class="mb-2">Notes</label>
              <textarea type="text" class="form-control" name="notes" id="notes"></textarea>

                </div>
                </div>
            </div>
            <div class="meta_nxtbtn container-fluid mt-5 mb-5" >
                <div class="row">
                    <div class="col form_btn cancelbtn d-flex justify-content-end">
                        <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/add-article" style="text-decoration:none;">Cancel</a></button>
                        
                    </div>
                    <div class="col form_btn nxtbtn">
                        <button type="submit" class="btn-next" >Next</button>
                    </div>
                </div>
            </div>
      </form>

      <!-- upload section -->
    <div class="content_right" style="display:none;" >
          <div class="row d-flex justify-content-center">
            <div class="col-10 col-md-8 dropboxdiv">
              <div
                class="row justify-content-center align-items-center d-flex flex-column"
                style="height: 100%"
              >
                <div class="col-10">
                  <div id="dropzone" class="col-12 dropboxinner">
                    <div id="dropzonewidget" class="dropzone"></div>
     
                   </div>
                  <div class="droppedfile mt-4">
                    <p style="font-size: 1.25em">Uploaded</p>
                    <div class="col-12 fileinfo mt-2">
                      <p class="">Document Name.pdf</p>
                      <button type="button">
                        <img src="<?php echo $_COOKIE['set_url'];?>/assets/images/trash.svg" />
                      </button>
                    </div>
                    <div class="fileupdtn">
                       <button type="submit" class="btn-next-cancel"><a href="<?php echo $_COOKIE['set_url'];?>/add-article" style="text-decoration:none;">Cancel</a></button>
                      <button type="button" class="filebtn2 btn-save btn-uploadworklfow">Upload File</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
         
            <div class="col-3 col-md-3 col-lg-3 d-none d-md-block uploadinfodiv">
              <div class="row g-4">
                <div class="col-12 uploadinfobox">
                  <div class="col-12 d-flex align-items-center mt-4 ms-2">
                    <img src="<?php echo $_COOKIE['set_url'];?>/assets/images/icon.svg" />
                    <p class="fileinfopara ms-3">Total Files</p>
                  </div>
                  <div class="fileno">
                    <p id="file_count">0</p>
                  </div>
                </div>
                <div class="col-12 uploadinfobox">
                  <div class="col-12 d-flex align-items-center mt-4 ms-2">
                    <img src="<?php echo $_COOKIE['set_url'];?>/assets/images/icon1.svg" />
                    <p class="fileinfopara ms-3">Total Size</p>
                  </div>
                  <div class="fileno">
                    <p style="color: #ff6d02" id="totalFileSize">0 KB</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
      </div>
        </div>
      </div>
      <?php echo scripts(); ?>	

       <!-- Footer Section -->
       <?php echo footer(); ?>
     
<div id="ea-bar"></div>
<div id="ea-overlay"><div class="ring"></div><div class="t">Creating article&hellip;</div><div class="s">Please wait, do not close this tab</div></div>
<div id="ea-dlg"><div class="box"><div class="in"><div class="ic" id="ea-dic">?</div><h3 id="ea-dt"></h3><p id="ea-dsub"></p><ul id="ea-dlist"></ul><div class="btns" id="ea-db"></div></div></div></div>
<script>
/* Add Article: design-layer behaviour only (the page's own validation and the upload_article.php call are untouched) */
(function ($) {
  $(function () {
    var $f = $('#add_article'), $bar = $('#ea-bar'), timer = null, val = 0, confirmed = false, dirty = false, MAP = null, journalAsked = '';
    if (!$f.length) { return; }

    /* Cancel buttons were submit buttons wrapped around a link */
    $('.btn-next-cancel').attr('type', 'button').on('click', function (e) { var a = $(this).find('a').attr('href'); if (a && !$(e.target).is('a')) { location.href = a; } });
    $f.find('input[type=checkbox]').each(function () { var $l = $(this).next('label'); if ($l.length && !$l.attr('for') && this.id) { $l.attr('for', this.id); } });
    $('.content_right').prepend('<div class="formcont1heading">Upload Files</div>');

    function norm(s) { return $.trim(String(s == null ? '' : s).replace(/\s+/g, ' ')); }
    function txt($sel) { return norm($sel.find('option:selected').text()); }

    /* ---------- required counter + live summary ---------- */
    function req() {
      var $r = $f.find('[required]').not('[type=hidden]').not(':disabled'), done = 0, tot = $r.length;
      $r.each(function () { if ($.trim($(this).val()) !== '') { done++; } });
      $('#ea-req-t').text('Required ' + done + ' / ' + tot); $('#ea-req-f').css('width', (tot ? Math.round(done * 100 / tot) : 100) + '%'); $('#ea-req').toggleClass('done', tot > 0 && done === tot);
    }
    function summary() {
      var pub = norm($('#pub_id').val()), cu = $('#cust_id').val() ? txt($('#cust_id')) : '', jo = $('#j_id').val() ? txt($('#j_id')) : '';
      $('#aa-pub').text(pub ? '#' + pub : '#—');
      $('#aa-sub').text(cu || jo ? [cu, jo].filter(Boolean).join(' · ') : 'Choose a customer and journal to begin');
      $('#aa-stage').text($('#stage').val() ? txt($('#stage')) : '—');
      var w = $('#wf_id').val() ? txt($('#wf_id')) : '', info = MAP && MAP.wf[$('#wf_id').val()];
      $('#aa-wf').text(w || '—'); $('#aa-first').text(info && info.first ? info.first : '—');
      /* the departments the workflow goes through, first one highlighted */
      var $c = $('#aa-chain'); if (!$c.length) { $c = $('<div class="ea-chain" id="aa-chain"></div>').insertAfter('#wf_id'); }
      $c.empty();
      if (info && info.chain.length) { $.each(info.chain, function (i, d) { if (i) { $c.append('<span class="ea-arrow"><i class="fa fa-chevron-right"></i></span>'); } $c.append($('<span class="ea-step' + (i === 0 ? ' first' : '') + '"></span>').text(d).css('animation-delay', (i * 0.05) + 's')); }); }
    }
    $f.on('input change keyup', function () { dirty = true; req(); summary(); });
    setTimeout(function () { req(); summary(); dirty = false; }, 500);

    /* ---------- stage -> workflow ---------- */
    function applyStage(tries) {
      var st = $('#stage').val(), id = MAP && MAP.stage[st];
      if (!MAP || !st) { return; }
      if (!id) { $('#aa-wfhint').remove(); $('<div class="ea-hint warn" id="aa-wfhint"><i class="fa fa-info-circle"></i> This journal has no workflow set for this stage &mdash; please choose the workflow yourself.</div>').insertAfter('#aa-chain'); summary(); return; }
      if (!$('#wf_id option[value="' + id + '"]').length) { if ((tries || 0) < 20) { setTimeout(function () { applyStage((tries || 0) + 1); }, 200); } return; }   /* the workflow list is still loading */
      $('#aa-wfhint').remove();
      $('#wf_id').val(String(id)); var $w = $('#wf_id'); $w.removeClass('ea-flash'); void $w[0].offsetWidth; $w.addClass('ea-flash'); setTimeout(function () { $w.removeClass('ea-flash'); }, 1400);
      $('<div class="ea-hint" id="aa-wfhint"><i class="fa fa-magic"></i> Workflow set to <b></b> for the <b></b> stage.</div>').insertAfter('#aa-chain').find('b').eq(0).text(MAP.wf[id].name).end().eq(1).text(txt($('#stage')));
      summary(); req();
    }
    $('#j_id').on('change.aa', function () {
      var j = $(this).val(); MAP = null; $('#aa-wfhint').remove();
      if (!j) { summary(); return; }
      journalAsked = j;
      $.getJSON('get_stage_workflow.php', { j_id: j }, function (d) { if (journalAsked === j) { MAP = d; applyStage(0); } });
    });
    $('#stage').on('change.aa', function () { applyStage(0); });

    /* ---------- dialog ---------- */
    function short(s) { s = String(s); return s.length > 100 ? s.slice(0, 100) + '…' : s; }
    function dlg(o) {
      $('#ea-dlg').toggleClass('info', !!o.info).addClass('on'); $('#ea-dic').text(o.info ? 'i' : '?'); $('#ea-dt').text(o.title); $('#ea-dsub').text(o.sub || '');
      var $l = $('#ea-dlist').empty();
      $.each(o.list || [], function (i, c) { $l.append($('<li></li>').css('animation-delay', Math.min(i, 12) * 0.04 + 's').append($('<b></b>').text(c.label)).append($('<span class="cmp"></span>').append($('<span class="new"></span>').text(short(c.to))))); });
      var $b = $('#ea-db').empty();
      $.each(o.buttons, function (i, b) { $('<button type="button" class="ea-dbtn' + (b.primary ? ' primary' : '') + '"></button>').text(b.text).on('click', function () { $('#ea-dlg').removeClass('on'); if (b.cb) { b.cb(); } }).appendTo($b); });
    }
    function labelOf($el) {
      var t = $el.is(':checkbox') ? $el.next('label').text() : $el.closest('[class*="col-"]').find('label').not('.error').first().text();
      return norm(t.replace(/\*/g, '').replace(/:\s*$/, '')) || $el.attr('name');
    }
    function entered() {
      var out = [];
      $f.find('input,select,textarea').filter(function () { return this.name && this.type !== 'hidden' && this.type !== 'file' && this.type !== 'submit' && this.type !== 'button'; }).each(function () {
        var $e = $(this), v = $e.is(':checkbox') ? ($e.is(':checked') ? 'Yes' : '') : ($e.is('select') ? txt($e) : norm($e.val()));
        if (v !== '' && v !== '-Select-') { out.push({ label: labelOf($e), to: v }); }
      });
      return out;
    }

    /* ---------- refresh ---------- */
    $('#ea-refresh').on('click', function (e) {
      e.preventDefault(); var $i = $(this).find('i'), go = function () { $i.addClass('fa-spin'); location.reload(); };
      if (!dirty) { go(); return; }
      dlg({ title: 'Start again?', sub: 'Refreshing clears everything you have entered on this page.', buttons: [{ text: 'Keep editing' }, { text: 'Clear & refresh', primary: true, cb: go }] });
    });

    /* ---------- submit: validate -> review what will be added -> confirm ---------- */
    $f.on('invalid-form.validate', function (e, v) {
      var el = v && v.errorList && v.errorList[0] && v.errorList[0].element; if (!el) { return; }
      $('html,body').animate({ scrollTop: Math.max(0, $(el).offset().top - 150) }, 300);
    });
    $f.on('submit', function (e) {
      var ok = true; try { ok = $f.valid(); } catch (er) { ok = true; }
      if (!ok) { return; }
      if (!confirmed) {
        e.preventDefault(); e.stopImmediatePropagation();
        var c = entered();
        dlg({ title: 'Review before adding', sub: 'This will create ' + ($('#pub_id').val() ? $('#pub_id').val() : 'the article') + ' with these details:', list: c,
              buttons: [{ text: 'Go back' }, { text: 'Confirm & add', primary: true, cb: function () { confirmed = true; $f.find('.btn-next')[0].click(); } }] });
        return false;
      }
    });

    /* the article is created through upload_article.php (ajax): loader while it runs, a banner when it is done */
    $(document).on('ajaxSend', function (e, x, s) {
      if (!/upload_article\.php/.test(s.url || '')) { return; }
      $f.find('.btn-next').prop('disabled', true); $('#ea-overlay').addClass('on'); clearInterval(timer); $bar.addClass('on'); val = 6; $bar.css('width', val + '%');
      timer = setInterval(function () { val = val + (92 - val) * 0.06; $bar.css('width', val + '%'); }, 200);
    });
    $(document).on('ajaxComplete', function (e, x, s) {
      if (!/upload_article\.php/.test(s.url || '')) { return; }
      clearInterval(timer); $bar.css('width', '100%'); setTimeout(function () { $bar.removeClass('on').css('width', 0); }, 400); $('#ea-overlay').removeClass('on'); $f.find('.btn-next').prop('disabled', false);
    });
    $(document).on('ajaxSuccess', function (e, x, s) {
      if (!/upload_article\.php/.test(s.url || '')) { return; }
      $('#aa-created').addClass('on');
      setTimeout(function () { var $c = $('.content_right:visible'); if ($c.length) { $('html,body').animate({ scrollTop: Math.max(0, $c.offset().top - 120) }, 400); } }, 150);
    });
    $(document).on('ajaxError', function (e, x, s) { if (/upload_article\.php/.test(s.url || '')) { confirmed = false; } });
  });
})(jQuery);
</script>

<script>
/* Add Article - upload step: a short instruction, and the new loader while the article is sent to the workflow */
(function ($) {
  $(function () {
    var $dz = $('.content_right #dropzone');
    if ($dz.length && !$('.aa-help').length) {
      $('<div class="aa-help"><i class="fa fa-info-circle"></i><span>Drop the article\'s files below &mdash; each file uploads as soon as you drop it. Then press <b>Upload File</b> to send the article to the workflow.</span></div>').insertBefore($dz);
    }
    function overlay(on, text) { var $o = $('#ea-overlay'); if (text) { $o.find('.t').text(text); } $o.toggleClass('on', !!on); }
    $(document).on('ajaxSend', function (e, x, s) { if (/upload_article_to_workflow\.php/.test(s.url || '')) { overlay(true, 'Sending to the workflow…'); } });
    $(document).on('ajaxComplete', function (e, x, s) { if (/upload_article_to_workflow\.php/.test(s.url || '')) { overlay(false); setTimeout(function () { $('#ea-overlay .t').text('Creating article…'); }, 400); } });
  });
})(jQuery);
</script>

 <!-- Date picker functionality -->
 <script>
      $(function () {
        // var datesForDisable = ["2017-10-14", "2017-10-15", "2017-10-16"];
        var date = new Date();
        date.setDate(date.getDate() - 1);
        var fromdate;
        $(".monthlyinputdate").datepicker({
          uiLibrary: "bootstrap5",
           dateFormat: "dd-mm-yy",
          autoclose: true,
         //  minDate: date,
              onSelect: function (dateText, inst) {
             fromdate = dateText;

                    $(".monthlyinputdate1").datepicker("option", "minDate", fromdate);
                }
        });

        $(".monthlyinputdate1").datepicker({
          uiLibrary: "bootstrap5",
           dateFormat: "dd-mm-yy",
          autoclose: true,
          todayHighlight: true,
          // minDate: date, //For disabling date from today
           onSelect: function (dateText, inst) {
             todate = dateText;
                    $(".monthlyinputdate").datepicker("option", "maxDate", todate);
                }
        });
      });

      


      		// Handle changes in the "countries" select element
    $(document).ready(function() {

$("#cust_id").on("change", function() {
         var cust_id=$.trim($(this).val());

         // Fetch states based on the selected client name and populate the "name" select element
         $.ajax({
             url: "get_journal.php",
             dataType: "json",
             data: { cust_id:cust_id },
             success: function(data) {
                 populateSelecte("j_id",data);

             }
         });
     });

     $('#j_id').on('change',function(){
        var j_id = $(this).val();
        if(j_id){
            $.ajax({
                url:'get_workflow.php',
                dataType: "json",
                data: { j_id:j_id },
                success:function(data){
                  //  $('#wf_id').html(html);   
                    populateSelecte_wf("wf_id",data);                 
                }
            });  
			
			$.ajax({
                url:'get_group_folder.php',
                dataType: "json",
                data: { j_id:j_id },
                success:function(data){
                   // $('#group_folder_name').html(html);     
                    populateSelecte_gf("group_folder_name",data);                     
                }
            }); 
			
			$.ajax({
                type:'POST',
                url:'get_journal_duration.php',
                data:'j_id='+j_id,
                success:function(html){
                    $('#journal_duration').html(html);   
                   // populateSelecte_jd("journal_duration",data);               
				    $('.date-picker').datepicker({
					autoclose: true,
					todayHighlight: true,
					format: 'dd-mm-yyyy'
				});					
                }
            }); 
			
			
        }else{
            $('#wf_id').html('<option value="">Select customer first</option>');
            
        }
    });

});
function populateSelecte(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();
			 selectElement.append($("<option>").text("-Select-").val(""));

            $.each(data, function(index, item) {
                selectElement.append($("<option data-id="+item.j_code+"_0000  data-previous="+item.j_code+"_0000>").text(item.j_code).val(item.j_id));
            });
        }
        function populateSelecte_wf(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.wf_name).val(item.wf_id));
            });
        }
        function populateSelecte_gf(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.folder_name).val(item.folder_name));
            });
        }
        function populateSelecte_jd(elementId, data) {
            var selectElement = $("#" + elementId);
            selectElement.empty();

            $.each(data, function(index, item) {
                selectElement.append($("<option>").text(item.j_code).val(item.j_id));
            });
        }

    </script>

<script>  
 //	$.mask.definitions['~']='[+-]';

$(document).ready(function() {
    $("#add_article").validate({
        rules: {

          cust_id: "required",
          j_id: "required",
          pub_id: "required",
          artitle_title: "required",
          due_dt: "required",
          recv_dt: "required",
          manuscript_count: "required",
          fig: "required",
          tab: "required",
          wf_id: "required",
          inw_type: "required",
          complexity: "required",
          corresponding_author: "required",
          corresponding_mail: "required",
          accpt_dt: "required",
          pub_dt: "required"

           
        },
        messages: {

           cust_id: "Please Select Customer",
          j_id: "Please Select Journal",
          pub_id: "Please enter Pub ID",
          artitle_title: "Please enter Article title",
          due_dt: "Please enter Due date",
          recv_dt: "Please enter Received Date",
          manuscript_count: "Please enter Manuscript Count",
          fig: "Please enter Figure Count",
          tab: "Please enter Table Count",
          wf_id: "Please Select Workflow",
          inw_type: "Please Select Inward Type",
          complexity: "Please Select Complexity",
          corresponding_author: "Please enter Corresponding Author",
          corresponding_mail: "Please enter Corresponding Email",
          accpt_dt: "Please enter Accept Date",
          pub_dt: "Please enter Publication Date"
           
        },

        submitHandler: function(form) {
            $.ajax({
                url: "upload_article.php",
                type: "POST",
                data: $(form).serialize(),
                success: function(response) {
                    // Success message
                   // alert("Form submitted successfully!");
                    // Reset the form
                   // $("#articlesubmission")[0].reset();
                      $("#pub_id").prop("readonly", true);
                      $("#cust_id").prop("disabled", true);
                      $("#j_id").prop("disabled", true);
                      $("#recv_dt").prop("disabled", true);
                      $('.btn-next').hide();
                      $('.meta_nxtbtn').remove();
                      $('.content_right').show();
                      $('.btn-save').hide();
                      $(".btn-worklfow-cancel").show();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    // Error message
                    alert("Error submitting form: " + errorThrown);
                    $('.content_right').hide();
                    $('.btn-next').show();
                    $('.btn-save').hide();
                    $(".btn-cancel").hide();
                     $(".btn-worklfow-cancel").hide();

                }
            });
          }
        });
    
});  

  </script> 

     <script>
	$(document).ready(function(){
    $(".pdf").change(function () {
		var astatus = $("input[type='radio']:checked").val();
		//alert(astatus);
		if(astatus=='yes')
		{
			$(".pdf_show").show();
			$(".btn-save").hide();
		    $("#stage").val('REV2');
		}
		else
		{
		$(".pdf_show").hide();
		$(".btn-save").show();
		$("#stage").val('FIN1');
		}
     });


     $('.doi_number').keyup(function(e) {
     var txtVal = $(this).val();
     var split_doi = txtVal.split('.');
     var volume_no = split_doi[3];
     var issue_no = split_doi[4];
      $('#volume_no').val(volume_no);
      $('#issue_no').val(issue_no);
	 pref='https://crossmark.crossref.org/dialog/?doi=';
	 suffix='&domain=pdf';
     $('.crossmark_link').val(pref+txtVal+suffix);
  }); 
  
   $('#volume_no').keyup(function(e) {
     var txtVal = $(this).val();
     var dot='.';
    
     var doi_no = $('.doi_number').val();
     var issue_no = $('#issue_no').val();

     var split_doi = doi_no.split('.');
     $('.doi_number').val(split_doi[0]+dot+split_doi[1]+dot+split_doi[2]+dot+txtVal+dot+issue_no+dot+split_doi[5]);
	 pref='https://crossmark.crossref.org/dialog/?doi=';
	 suffix='&domain=pdf';
     $('.crossmark_link').val(pref+split_doi[0]+dot+split_doi[1]+dot+split_doi[2]+dot+txtVal+dot+issue_no+dot+split_doi[5]+suffix);
  }); 
  
  $('#issue_no').keyup(function(e) {
     var txtVal = $(this).val();
     var dot='.';
    
     var doi_no = $('.doi_number').val();
     var volume_no = $('#volume_no').val();

     var split_doi = doi_no.split('.');
     $('.doi_number').val(split_doi[0]+dot+split_doi[1]+dot+split_doi[2]+dot+volume_no+dot+txtVal+dot+split_doi[5]);
	 pref='https://crossmark.crossref.org/dialog/?doi=';
	 suffix='&domain=pdf';
     $('.crossmark_link').val(pref+split_doi[0]+dot+split_doi[1]+dot+split_doi[2]+dot+volume_no+dot+txtVal+dot+split_doi[5]+suffix);
  }); 

	
  
	// Corresponding Author Inputs//
	
	var counter_c = 2;
	$('.del_cauthor').hide();
	$('#del_cauthor_tag').hide();
	$('.add_cauthor').click(function(){
$('#author_ctools').before('<div class="metaauthorinputs" id="fac'+counter_c+'"><div class="metaauthorname"><label>Author Name </label><input type="text" placeholder="Author Name" name="author_cname[]" aria-label="name" id="author_cname'+counter_c+'" value="" /></div><div class="metaauthormail"><label>Email ID <span style="color: red">*</span></label><input type="email" placeholder="Author Email ID" name="author_cemail[] "aria-label="name" class="article_einput" id="author_cemail'+counter_c+'" value="" /></div></div>');
		$('.del_cauthor').fadeIn(0);
		$('.del_cauthor_tag').fadeIn(0);
		$('#total_show_cauthor').text("Count :"+counter_c);
		$('#total_cauthor').val(counter_c);

	counter_c++;
	



	});
	$('.del_cauthor').click(function(){
		if(counter_c== 3){
			$('.del_cauthor').hide();
			$('#del_cauthor_tag').hide();
		}   

		counter_c--;
		$('#fac'+counter_c).remove();
		$('#total_cauthor').val(counter_c-1);
		$('#total_show_cauthor').text("Count :"+(counter_c-1));



	});


// Corresponding Author Inputs//

//  Author Orcid Inputs//

	var counter_a = 1;
	$('.del_author').hide();
	$('#del_author_tag').hide();
	$('.add_author').click(function(){
$('#author_tools').before('<div class="metaauthorinputs" id="fa'+counter_a+'"><div class="metaauthorname"><label>Author Name </label><input type="text" placeholder="Author Name" name="author_name[]" aria-label="name" id="author_name'+counter_a+'" value="" /></div><div class="metaauthormail"><label>Orcid ID </label><input type="text" placeholder="Orcid ID" name="orcid_id[] "aria-label="name" id="orcid_id'+counter_a+'" value="" /></div></div>');
		$('.del_author').fadeIn(0);
		$('.del_author_tag').fadeIn(0);
		$('#total_show_author').text("Count :"+counter_a);
		$('#total_author').val(counter_a);
		//$('.corr_id_val').val('no');
		$('#orcid_id'+counter_a).mask('****-****-****-****');

	counter_a++;



	});
	$('.del_author').click(function(){
		if(counter_a==2){
			$('.del_author').hide();
			$('#del_author_tag').hide();
		}   

		counter_a--;
		$('#fa'+counter_a).remove();
		$('#total_author').val(counter_a-1);
		$('#total_show_author').text("Count :"+(counter_a-1));
		$('#orcid_id'+counter_a).mask('****-****-****-****');



	});





});
</script>

<script type='text/javascript'>
           // var hiddenValue1 =$("#jid").html();
           // var hiddenValue2 =$("#pubid").html();
           // var hiddenValue3 =$("#j_id").val();
           // console.log('hiddenValue:'+hiddenValue3);
        Dropzone.options.dropzonewidget = {


             maxFiles: 20, // Max number of files
            // acceptedFiles: ".pdf",
             type: 'POST',
             url: 'upload-new-articles-data-file.php',
             paramName: 'file',
             
             params: {
              request:1 // Additional argument 4
                // Add more arguments as needed
            },
            init: function () {

              var myDropzone = this;

                 var totalSize = 0;
                var totalSizeElement = document.getElementById('totalFileSize');

                this.on("addedfile", function (file) {

                  totalSize += file.size;
                  updateTotalSize();

                    var removeButton = Dropzone.createElement('<button class="btn-remove" style="background-color:red;border-radius:15px;cursor:pointer;">Remove</button>');
                    var _this = this;

                    // Event listener for the "Remove" button click
                    removeButton.addEventListener("click", function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        _this.removeFile(file);


                        var name = file.name;    
                
                $.ajax({
                    type: 'POST',
                    url: 'upload-new-articles-data-file.php',
                    data: {name: name,request: 2},
                    sucess: function(data){
                        console.log('success: ' + data);
                    }
                });
                var _ref;
                return (_ref = file.previewElement) != null ? _ref.parentNode.removeChild(file.previewElement) : void 0;
              //  updateTotalSize();

                    });

                    file.previewElement.appendChild(removeButton);

                    updateUploadCount();
                    updateTotalSize();


                });

                this.on("removedfile", function (file) {

                    totalSize -= file.size;
                    updateUploadCount();
                    updateTotalSize();

                });

                this.on("totaluploadprogress", function (progress) {
                    // Use progress.loaded to track the total uploaded size
                });

                this.on("success", function (file, response) {
                    console.log(response);
                });
                
                 this.on("queuecomplete", function () {
                // All files have been processed, display an alert
               // alert("All files have been uploaded.");
                  $(".btn-save").show();
            });

                function updateTotalSize() {
                    totalSizeElement.textContent = formatBytes(totalSize);
                }

                // Function to format bytes into human-readable format
                function formatBytes(bytes) {
                    var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
                    if (bytes === 0) return '0 Byte';
                    var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
                    return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
                }

              

// Add an event listener to the remove button
/* 
document.getElementById('removeButton').addEventListener('click', function () {
    if (myDropzone.files.length > 0) {
        // Remove the first file from the Dropzone instance
        myDropzone.removeFile(myDropzone.files[0]);
    }
});
*/

            }
        };

       


        function updateUploadCount() {
            var dropzoneInstance = Dropzone.forElement("#dropzonewidget");
            var uploadedFilesCount = dropzoneInstance.files.length;
            var countElement = document.getElementById("file_count");

            if (uploadedFilesCount === 0) {
                countElement.textContent = "0";
                	$(".btn-save").hide();
            } else if (uploadedFilesCount === 1) {
                countElement.textContent = "1";
                //	$(".btn-save").show();
            } else {
                countElement.textContent = uploadedFilesCount;
                //$(".btn-save").show();

            }
        }

      /*  $('#j_id').change(function() {
	$('#previous').show();
    $('#pub_id').val($('#j_id option:selected').data('id'));
    $('.previous_aid').text($('#j_id option:selected').data('previous'));
    console.log($('#j_id option:selected').data('id'));
    console.log($('#j_id option:selected').data('previous'));
    var jid=$('#j_id').val();
    console.log(jid);
    //$('#j_id').val(jid);
    $('#j_id').attr('value',jid);



	
});
*/


$(".btn-uploadworklfow").on("click", function() {
// Show overlay and loader
     
        
  var count=$("#file_count").text();
//alert(count);

 // alert('ajax initiated');
	if(count>0)
	{
	$("#overlay").show();
    $("#loader").show();
  var customerid = $('#cust_id').val();
  var jid = $('#j_id').val();
  var pubid = $('#pub_id').val();
// Initiate the AJAX request

// Fetch connectedsource based on the selected connectedsource and populate the "name" select element
$.ajax({
    url: "upload_article_to_workflow.php",
    dataType: "json",
    data: { cust_id: customerid, j_id: jid, pub_id: pubid },
    success: function(data) {
    // Hide the loader
       // Hide overlay and loader
       $("#overlay").hide();
       $("#loader").hide();
     // alert('ajax success');
     resetForm();
	},
  complete:function(response){
 


swal({
  title:"Your article is Submitted",
   type: "success",
		showCancelButton: false,
		confirmButtonColor: '#DD6B55',
		confirmButtonText: 'Ok!',
    confirmButtonText: 'CLOSE',
		closeOnConfirm: false,
		closeOnCancel: false
	},
	function(isConfirm){
    if (isConfirm){

      swal("Uploaded!", "Thank You", "success");
     // window.top.location="view_users.php?del="+del;
     window.location.href = '<?php echo $_COOKIE['set_url'];?>/articles';

    } else {
      swal("Uploaded!", "Thank You", "success");
     // window.top.location="view_users.php?del="+del;
     window.location.href = '<?php echo $_COOKIE['set_url'];?>/articles';
    }
	});

}, 
    error: function() {
                // Hide overlay and loader on error
                $("#overlay").hide();
                $("#loader").hide();
                console.log("An error occurred.");
            }
});
}
else
{
   swal({
   title:"Please Upload Doc or any file to Proceed",
 type: "info",  
confirmButtonColor: '#DD6B55',
confirmButtonText: 'CLOSE',
});

 // Hide overlay and loader on error
                $("#overlay").hide();
                $("#loader").hide();
                console.log("An error occurred.");

}

	});

  function resetForm() 
{
	$("#articlesubmission")[0].reset();

}
        </script>
  </body>
</html>
