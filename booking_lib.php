<?php
/* Revise / Final booking (rev_project.php, fin_project.php): shared page layout, file-flow listing and the "article booked" e-mail.
 * Recipients (To / CC / BCC) live in wf_settings (sw_book_*) and are edited on sw_switch_settings.php; every booking is logged in sw_book_log. */

require_once __DIR__.'/sw_switch_lib.php';

function bk_h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

/* folders that stay behind when the previous stage's files are moved (same list rmove() uses) */
function bk_skip_folders() { return array('Delivery', 'CE', 'PRQC', 'DELIVERY-ELX', 'CE-ELX', 'PRQC-ELX'); }

function bk_count_files($dir)
{
	$n = 0;
	try {
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $f) { if ($f->isFile()) { $n++; } }
	} catch (Exception $e) { }
	return $n;
}

/* what is in the previous stage's folder, split into what will move and what stays behind (call it BEFORE the move) */
function bk_file_flow($src)
{
	$out = array('exists' => false, 'files' => array(), 'moved_folders' => array(), 'skipped_folders' => array(), 'moved_files' => 0);
	if (!is_dir($src)) { return $out; }
	$out['exists'] = true;
	$skip = bk_skip_folders();
	try {
		foreach (new DirectoryIterator($src) as $f) {
			if ($f->isDot()) { continue; }
			if ($f->isFile()) { $out['files'][] = array($f->getFilename(), (int)$f->getSize()); }
			elseif ($f->isDir()) {
				$row = array($f->getFilename(), bk_count_files($f->getPathname()));
				if (in_array($f->getFilename(), $skip, true)) { $out['skipped_folders'][] = $row; } else { $out['moved_folders'][] = $row; $out['moved_files'] += $row[1]; }
			}
		}
	} catch (Exception $e) { }
	$out['moved_files'] += count($out['files']);
	return $out;
}

function bk_size($n) { return $n < 1024 ? $n.' B' : ($n < 1048576 ? round($n / 1024).' KB' : round($n / 1048576, 1).' MB'); }

/* wf_id => array('name' => .., 'depts' => [department names in order]) for the workflows offered on the page */
function bk_wf_map($db, $names_csv)
{
	$map = array();
	$dn = array();
	$r = $db->query("SELECT dept_code, dept_name FROM adm_dept_master");
	while ($r && ($x = $r->fetch_assoc())) { $dn[(int)$x['dept_code']] = $x['dept_name']; }
	$r = $db->query("SELECT wf_id, wf_code, wf_name FROM adm_workflow_master WHERE wf_name IN ('".$db->real_escape_string($names_csv)."') ORDER BY wf_code ASC");
	while ($r && ($x = $r->fetch_assoc())) {
		$ds = array();
		foreach (explode(',', $x['wf_code']) as $c) { $c = (int)trim($c); if ($c && isset($dn[$c])) { $ds[] = $dn[$c]; } }
		$map[(string)$x['wf_id']] = array('name' => $x['wf_name'], 'depts' => $ds);
	}
	return $map;
}

/* ------------------------------------------------------------------ the e-mail ------------------------------------------------------------------ */
/* $c: kind (REV|FIN), booked_stage, prev_stage, prev_status, article_id, pub_id, customer, journal, recv, due (Y-m-d), pages, instructions,
       wf_name, depts[], first_dept, flow (bk_file_flow before the move), src, dest, user_id, user_name, upload_url      -> returns the mail status text */
function bk_build_mail($c)
{
	$kindname = $c['kind'] === 'FIN' ? 'Final' : ($c['kind'] === 'JSTAGE' ? 'JSTAGE' : 'Revise');
	$fmt = function ($d) { return ($d && $d !== '0000-00-00' && strtotime($d)) ? date('d-M-Y', strtotime($d)) : '-'; };
	$row = function ($k, $v) { return '<tr><td style="padding:5px 14px 5px 0;color:#5b6b7b;vertical-align:top;white-space:nowrap;"><strong>'.$k.'</strong></td><td style="padding:5px 0;">'.$v.'</td></tr>'; };
	$chain = array();
	foreach ($c['depts'] as $i => $d) { $chain[] = $i === 0 ? '<strong style="color:#c9591a;">'.bk_h($d).'</strong>' : bk_h($d); }
	$f = $c['flow'];
	$files_html = '';
	if (!empty($f['exists'])) {
		$items = array();
		foreach (array_slice($f['files'], 0, 25) as $x) { $items[] = bk_h($x[0]).' <span style="color:#8a95a1;">('.bk_size($x[1]).')</span>'; }
		$more = count($f['files']) - 25;
		$files_html = '<p style="margin:6px 0 4px;"><strong>'.(int)$f['moved_files'].'</strong> file(s) moved from <strong>'.bk_h($c['prev_stage']).'</strong> to <strong>'.bk_h($c['booked_stage']).'</strong>.</p>';
		if ($items) { $files_html .= '<div style="font-size:12.5px;line-height:1.6;">'.implode('<br>', $items).($more > 0 ? '<br><em>and '.$more.' more file(s) in this folder</em>' : '').'</div>'; }
		if ($f['moved_folders']) { $x = array(); foreach ($f['moved_folders'] as $m) { $x[] = bk_h($m[0]).' ('.(int)$m[1].' files)'; } $files_html .= '<p style="margin:8px 0 2px;"><strong>Folders moved:</strong> '.implode(', ', $x).'</p>'; }
		if ($f['skipped_folders']) { $x = array(); foreach ($f['skipped_folders'] as $m) { $x[] = bk_h($m[0]); } $files_html .= '<p style="margin:2px 0;color:#5b6b7b;"><strong>Left in '.bk_h($c['prev_stage']).' (not moved):</strong> '.implode(', ', $x).'</p>'; }
	} else {
		$files_html = '<p style="margin:6px 0;color:#b87800;">The '.bk_h($c['prev_stage']).' folder was not found, so no files were moved.</p>';
	}
	$body = '<div style="font-family:Segoe UI,Arial,sans-serif;font-size:14px;color:#22303f;">'
		. '<p>Hi Team,</p>'
		. '<p><strong>'.bk_h($c['user_name']).'</strong> booked the article <strong>'.bk_h($c['pub_id']).'</strong> as <strong style="color:#c9591a;">'.bk_h($c['booked_stage']).'</strong> ('.$kindname.') on '.date('d-M-Y h:i A').'.</p>'
		. '<table cellpadding="0" cellspacing="0" border="0" style="font-size:14px;">'
		. $row('Booked as', '<strong>'.bk_h($c['booked_stage']).'</strong> ('.$kindname.')')
		. $row('Booked by', bk_h($c['user_name']).($c['user_id'] !== '' ? ' (ID '.bk_h($c['user_id']).')' : ''))
		. $row('Booked on', date('d-M-Y h:i A'))
		. $row('Article', bk_h($c['pub_id']).' (ID '.(int)$c['article_id'].')')
		. $row('Customer / Journal', bk_h($c['customer']).' / '.bk_h($c['journal']))
		. $row('Previous stage', bk_h($c['prev_stage']).($c['prev_status'] !== '' ? ' &middot; '.bk_h($c['prev_status']) : ''))
		. $row('Received date', $fmt($c['recv']))
		. $row('Due date', $fmt($c['due']))
		. $row($c['kind'] === 'REV' ? 'Correction pages' : 'Pages', (int)$c['pages'])
		. ($c['instructions'] !== '' ? $row('Instructions', nl2br(bk_h($c['instructions']))) : '')
		. '</table>'
		. '<h3 style="margin:18px 0 6px;font-size:15px;color:#14304a;">Workflow followed</h3>'
		. '<p style="margin:0 0 4px;"><strong>'.bk_h($c['wf_name']).'</strong></p>'
		. '<p style="margin:0 0 4px;">'.($chain ? implode(' &rarr; ', $chain) : '-').'</p>'
		. '<p style="margin:0;">First department: <strong style="color:#c9591a;">'.bk_h($c['first_dept']).'</strong> &mdash; the article now sits there as <strong>'.bk_h($c['booked_stage']).'</strong>.</p>'
		. '<h3 style="margin:18px 0 6px;font-size:15px;color:#14304a;">File flow</h3>'
		. $files_html
		. '<p style="margin:8px 0 2px;color:#5b6b7b;font-size:12.5px;">From: '.bk_h($c['src']).'<br>To: '.bk_h($c['dest']).'<br>The '.bk_h($c['first_dept']).' folder was created inside the new stage folder for the first department.</p>'
		. '<p style="margin:12px 0 2px;">Next step: the new-stage files are uploaded on the upload page that opens after booking.</p>'
		. '<p style="color:#888;font-size:12px;margin-top:16px;">System-generated notification from the Workflow application. Recipients can be changed in Email Alerts and Logs.</p>'
		. '</div>';
	return array('subject' => 'BOOKED AS '.$c['booked_stage'].': '.$c['pub_id'].' ('.$c['customer'].' / '.$c['journal'].') by '.$c['user_name'], 'body' => $body);
}

/* log + send; never throws - a failed mail must not stop a booking */
function bk_notify($db, $c)
{
	$status = '';
	$log_id = 0;
	try {
		sw_switch_ensure($db);
		$db->query("INSERT INTO sw_book_log (article_id, pub_id, kind, booked_stage, prev_stage, workflow, first_dept, user_id, user_name, mail_status, created_at) VALUES ("
			.(int)$c['article_id'].", '".$db->real_escape_string($c['pub_id'])."', '".$db->real_escape_string($c['kind'])."', '".$db->real_escape_string($c['booked_stage'])."', '"
			.$db->real_escape_string($c['prev_stage'])."', '".$db->real_escape_string($c['wf_name'])."', '".$db->real_escape_string($c['first_dept'])."', '"
			.$db->real_escape_string($c['user_id'])."', '".$db->real_escape_string($c['user_name'])."', 'Pending', NOW())");
		$log_id = (int)$db->insert_id;
		$cfg = sw_mail_get($db, 'book');
		$to = sw_switch_parse_list($cfg['to']); $cc = sw_switch_parse_list($cfg['cc']); $bcc = sw_switch_parse_list($cfg['bcc']);
		if (!$to['valid'] && !$cc['valid'] && !$bcc['valid']) {
			$status = 'Not sent - no recipients configured';
		} else {
			require_once __DIR__.'/email-functions.php';
			$es = $db->query("SELECT * FROM email_settings WHERE id = 1"); $es = $es ? $es->fetch_assoc() : null;
			if (!$es) { $status = 'Not sent - email_settings missing'; }
			else {
				$m = bk_build_mail($c);
				$ok = sendMail($es['host'], $es['port'], $es['secure'], $es['from_email'], $es['from_password'], $es['from_email'], $es['from_name'],
					implode(',', $to['valid']), implode(',', $cc['valid']), implode(',', $bcc['valid']), $m['subject'], $m['body']);
				$status = $ok ? 'Sent' : 'Failed - see smtp_mail.log';
			}
		}
	} catch (Throwable $e) { $status = 'Failed - '.substr($e->getMessage(), 0, 150); }
	if ($log_id) { $db->query("UPDATE sw_book_log SET mail_status = '".$db->real_escape_string($status)."' WHERE id = ".$log_id); }
	return $status;
}

/* ------------------------------------------------------------------ pages ------------------------------------------------------------------ */
function bk_css()
{
?>
<style id="bk-theme">
  /* ===== Revise / Final booking - same look as Start Work ===== */
  body{background:#f3f6fa !important;}
  .bk-hero{width:min(1100px,calc(100% - 28px));margin:14px auto 0;background:#fff;border:1px solid #e0e8f0;border-radius:16px;overflow:hidden;box-shadow:0 6px 22px rgba(20,48,74,.08);animation:bkRise .45s ease;}
  .bk-hero > * + *{border-top:1px solid #e8eef4;}
  .bk-bar{display:flex;align-items:center;gap:14px;padding:11px 20px;}
  .bk-back{flex:0 0 auto;width:36px;height:36px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:#fff;border:1px solid #d9e2ec;color:#14304a !important;text-decoration:none !important;transition:all .2s;}
  .bk-back:hover{background:#e8742a;border-color:#e8742a;color:#fff !important;transform:translateX(-2px);box-shadow:0 4px 10px rgba(232,116,42,.35);}
  .bk-title{margin:0;font-size:19px;font-weight:700;color:#14304a;line-height:1.2;}
  .bk-crumb{font-size:12px;color:#6b7785;} .bk-crumb a{color:#1b6ca8;text-decoration:none;}
  .bk-sp{flex:1;}
  .bk-id{padding:5px 12px;border-radius:999px;background:#eaf2fa;border:1px solid #c5daef;color:#14304a;font-weight:700;font-size:12.5px;max-width:46%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
  .bk-sum{padding:12px 20px;background:linear-gradient(120deg,#f4f9fe 0%,#e8f1fa 100%);display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px 18px;}
  .bk-sum small{display:block;font-size:10px;letter-spacing:.7px;text-transform:uppercase;color:#6b7785;}
  .bk-sum-id{font-size:18px;font-weight:700;color:#14304a;line-height:1.25;word-break:break-all;} .bk-sum-sub{font-size:12.5px;color:#5b6b7b;}
  .bk-chips{display:flex;flex-wrap:wrap;gap:8px;}
  .bk-chip{min-width:92px;padding:6px 11px;border-radius:8px;background:#fff;border:1px solid #dbe6f1;animation:bkPop .4s cubic-bezier(.2,.9,.3,1.3) backwards;}
  .bk-chip:nth-child(1){animation-delay:.08s;} .bk-chip:nth-child(2){animation-delay:.14s;} .bk-chip:nth-child(3){animation-delay:.2s;} .bk-chip:nth-child(4){animation-delay:.26s;} .bk-chip:nth-child(5){animation-delay:.32s;} .bk-chip:nth-child(6){animation-delay:.38s;}
  .bk-chip b{display:block;font-size:12.5px;font-weight:700;color:#14304a;}
  .bk-chip.hot{border-color:#f6d3bb;background:#fff7f1;} .bk-chip.hot b{color:#d2631d;}
  .bk-card{width:min(1100px,calc(100% - 28px));margin:16px auto 28px;background:#fff;border:1px solid #e4e9ef;border-radius:16px;padding:22px 26px 26px;box-shadow:0 4px 18px rgba(20,48,74,.07);animation:bkRise .5s ease;}
  .bk-h{position:relative;margin:0 0 14px;padding:2px 0 12px 16px;font-size:16px;font-weight:700;color:#14304a;border-bottom:1px solid #e4e9ef;}
  .bk-h:before{content:"";position:absolute;left:0;top:2px;bottom:14px;width:5px;border-radius:3px;background:linear-gradient(#e8742a,#1b6ca8);}
  .bk-grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:16px 18px;margin-bottom:22px;}
  .bk-f{grid-column:span 3;min-width:0;} .bk-f.w6{grid-column:span 6;} .bk-f.w12{grid-column:1 / -1;}
  @media(max-width:900px){.bk-f{grid-column:span 6;} .bk-f.w6{grid-column:1 / -1;}}
  @media(max-width:560px){.bk-f{grid-column:1 / -1;} .bk-card{padding:18px 14px;}}
  .bk-f label{display:block;margin:0 0 6px;font-size:12px;font-weight:700;letter-spacing:.3px;text-transform:uppercase;color:#5b6b7b;}
  .bk-f label i{color:#d64545;font-style:normal;margin-left:3px;}
  .bk-f input[type=text],.bk-f input[type=number],.bk-f select,.bk-f textarea{width:100%;height:44px;border:1px solid #cfd8e3;border-radius:10px;background:#fbfcfe;padding:8px 13px;font-size:14px;color:#22303f;box-shadow:none;transition:border-color .2s,box-shadow .2s,background .2s;font-family:inherit;}
  .bk-f textarea{height:auto;min-height:110px;padding:11px 13px;resize:vertical;}
  .bk-f input:focus,.bk-f select:focus,.bk-f textarea:focus{outline:0;border-color:#1b6ca8;background:#fff;box-shadow:0 0 0 4px rgba(27,108,168,.14);}
  .bk-f .bk-static{height:44px;display:flex;align-items:center;padding:0 13px;border-radius:10px;background:#fff7f1;border:1px solid #f6d3bb;color:#c9591a;font-weight:700;font-size:15px;}
  .bk-f.err input,.bk-f.err select,.bk-f.err textarea{border-color:#d64545;background:#fff8f8;animation:bkShake .35s;}
  .bk-msg{display:none;margin-top:5px;color:#d64545;font-size:12px;font-weight:600;} .bk-f.err .bk-msg{display:block;animation:bkRise .25s ease;}
  .bk-flow{border:1px solid #e1e8ef;border-radius:12px;background:#f8fafc;padding:14px 16px;margin-bottom:22px;display:grid;grid-template-columns:1fr 1fr;gap:14px 24px;}
  @media(max-width:760px){.bk-flow{grid-template-columns:1fr;}}
  .bk-flow h4{margin:0 0 8px;font-size:12px;letter-spacing:.6px;text-transform:uppercase;color:#6b7785;font-weight:700;}
  .bk-steps{display:flex;flex-wrap:wrap;align-items:center;gap:6px;} .bk-step{padding:5px 11px;border-radius:999px;background:#fff;border:1px solid #d5dde6;font-size:12.5px;font-weight:600;color:#33475b;animation:bkPop .35s ease backwards;}
  .bk-step.first{background:#fff7f1;border-color:#f1b98f;color:#c9591a;} .bk-arrow{color:#9aa8b8;font-size:11px;}
  .bk-note{font-size:13px;line-height:1.55;color:#33475b;} .bk-note b{color:#14304a;} .bk-note .muted{color:#7b8a9a;}
  .bk-files{margin:6px 0 0;padding:0;list-style:none;font-size:12.5px;color:#4a5b6b;max-height:120px;overflow:auto;} .bk-files li{padding:2px 0;} .bk-files li i{color:#1b6ca8;margin-right:6px;}
  .bk-actions{display:flex;justify-content:flex-end;gap:12px;padding-top:6px;border-top:1px solid #eef1f5;margin-top:6px;}
  .bk-btn{display:inline-flex;align-items:center;gap:8px;min-width:130px;justify-content:center;height:44px;padding:0 24px;border-radius:10px;font-weight:700;font-size:14px;cursor:pointer;text-decoration:none !important;transition:all .2s;border:1px solid #cfd8e3;background:#fff;color:#33475b;}
  .bk-btn:hover{background:#f1f5f9;}
  .bk-btn.primary{border:0;background:linear-gradient(120deg,#1b6ca8,#14304a);color:#fff;box-shadow:0 5px 14px rgba(27,108,168,.3);} .bk-btn.primary:hover{filter:brightness(1.1);transform:translateY(-1px);}
  .bk-btn[disabled]{opacity:.6;cursor:wait;transform:none;}
  /* loader + dialog */
  #bk-overlay{position:fixed;inset:0;z-index:99998;display:none;align-items:center;justify-content:center;flex-direction:column;background:rgba(255,255,255,.85);backdrop-filter:blur(2px);}
  #bk-overlay.on{display:flex;animation:bkFade .2s ease;}
  #bk-overlay .ring{width:58px;height:58px;border-radius:50%;border:5px solid #e9eef4;border-top-color:#e8742a;border-right-color:#1b6ca8;animation:bkSpin .85s linear infinite;margin-bottom:14px;}
  #bk-overlay .t{font-size:17px;font-weight:700;color:#14304a;} #bk-overlay .s{color:#6b7785;margin-top:3px;}
  #bk-bar{position:fixed;top:0;left:0;height:4px;width:0;z-index:99999;opacity:0;background:linear-gradient(90deg,#e8742a,#f5a35c 60%,#1b6ca8);box-shadow:0 0 12px rgba(232,116,42,.65);transition:width .25s ease,opacity .4s ease;} #bk-bar.on{opacity:1;}
  #bk-dlg{position:fixed;inset:0;z-index:99990;display:none;align-items:center;justify-content:center;background:rgba(15,32,52,.5);backdrop-filter:blur(3px);padding:16px;}
  #bk-dlg.on{display:flex;animation:bkFade .2s ease;}
  #bk-dlg .box{width:min(470px,100%);background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 28px 70px rgba(15,35,60,.38);animation:bkPopBox .45s cubic-bezier(.2,1.2,.3,1) backwards;}
  #bk-dlg .box:before{content:"";display:block;height:5px;background:linear-gradient(90deg,#e8932a,#f5c98a);}
  #bk-dlg.err .box:before{background:linear-gradient(90deg,#d64545,#f0a0a0);}
  #bk-dlg .in{padding:24px 28px 22px;text-align:center;}
  #bk-dlg .ic{width:60px;height:60px;margin:0 auto 12px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#fff3e0;color:#e8932a;font:700 30px/1 'Open Sans',Arial,sans-serif;animation:bkPop .5s cubic-bezier(.2,1.4,.3,1) .1s backwards;}
  #bk-dlg.err .ic{background:#fdeaea;color:#d64545;}
  #bk-dlg h3{margin:0 0 8px;font-size:20px;font-weight:700;color:#1c3550;} #bk-dlg p{margin:0;color:#51606f;font-size:14px;line-height:1.55;}
  #bk-dlg ul{list-style:none;margin:12px 0 0;padding:0;text-align:left;} #bk-dlg li{padding:9px 12px 9px 14px;margin:0 0 7px;background:#f6f9fc;border:1px solid #e3eaf2;border-left:4px solid #e8932a;border-radius:10px;font-size:13.5px;color:#2f4357;}
  #bk-dlg .btns{display:flex;justify-content:center;gap:10px;margin-top:18px;}
  @keyframes bkRise{from{opacity:0;transform:translateY(12px);}to{opacity:1;transform:none;}} @keyframes bkPop{from{opacity:0;transform:scale(.8);}to{opacity:1;transform:none;}}
  @keyframes bkPopBox{from{opacity:0;transform:translateY(18px) scale(.92);}to{opacity:1;transform:none;}} @keyframes bkFade{from{opacity:0;}to{opacity:1;}} @keyframes bkSpin{to{transform:rotate(360deg);}}
  @keyframes bkShake{0%,100%{transform:translateX(0);}25%{transform:translateX(-5px);}75%{transform:translateX(5px);}}
</style>
<?php
}

/* a booking step failed: a clear page instead of a bare die() */
function bk_fail($title, $message, $back)
{
	if (!headers_sent()) { header('Content-Type: text/html; charset=utf-8'); }
	echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>'.bk_h($title).'</title>'
		.'<style>body{margin:0;background:#f3f6fa;font-family:"Open Sans","Segoe UI",Arial,sans-serif;display:flex;min-height:100vh;align-items:center;justify-content:center;padding:16px;}'
		.'.b{max-width:480px;width:100%;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 20px 60px rgba(15,35,60,.2);text-align:center;}.b:before{content:"";display:block;height:5px;background:linear-gradient(90deg,#d64545,#f0a0a0);}'
		.'.i{padding:28px 30px 26px;}.c{width:60px;height:60px;border-radius:50%;margin:0 auto 12px;background:#fdeaea;color:#d64545;font:700 30px/60px Arial;}h3{margin:0 0 8px;font-size:20px;color:#1c3550;}p{margin:0 0 18px;color:#51606f;font-size:14px;line-height:1.55;}'
		.'a{display:inline-block;padding:11px 26px;border-radius:10px;background:linear-gradient(120deg,#1b6ca8,#14304a);color:#fff;text-decoration:none;font-weight:700;}</style></head><body><div class="b"><div class="i"><div class="c">!</div><h3>'
		.bk_h($title).'</h3><p>'.bk_h($message).'</p><a href="'.bk_h($back).'">Back</a></div></div></body></html>';
	exit;
}

/* dd-mm-yyyy -> yyyy-mm-dd, or null */
function bk_date($s)
{
	if (!preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', trim((string)$s), $m) || !checkdate((int)$m[2], (int)$m[1], (int)$m[3])) { return null; }
	return $m[3].'-'.$m[2].'-'.$m[1];
}

/* moves the previous stage's files (and its sub-folders, except the ones that stay behind) into the new stage folder */
function bk_move_files($src, $dest)
{
	if (!is_dir($src)) { return false; }
	if (!is_dir($dest) && !@mkdir($dest, 0777, true)) { return false; }
	$skip = bk_skip_folders();
	foreach (new DirectoryIterator($src) as $f) {
		if ($f->isDot()) { continue; }
		if ($f->isFile()) { @rename($f->getRealPath(), rtrim($dest, '/\\').'/'.$f->getFilename()); }
		elseif ($f->isDir()) {
			$to = rtrim($dest, '/\\').'/'.$f->getFilename();
			if (in_array($f->getFilename(), $skip, true)) { if (!is_dir($to)) { @mkdir($to, 0777, true); } }	/* skipped folder: files stay behind, empty folder is created */
			else { bk_move_files($f->getRealPath(), $to); }
		}
	}
	return true;
}

/* handles the booking POST (and exits with a redirect), otherwise returns the data for booking_page.php. $kind = 'REV' (rev_project.php, /revarticles/<id>) or 'FIN' (fin_project.php, /finalarticles/<id>) */
function bk_run($kind)
{
	global $db;
	$is_fin = ($kind === 'FIN');
	$back = $_COOKIE['set_url'].'/underclientreview';
	$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : (isset($_POST['inw_id']) ? (int)$_POST['inw_id'] : 0);

	$r = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$id);
	$app = $r ? $r->fetch_assoc() : null;
	if (!$app) { bk_fail('Article not found', 'This article does not exist or was removed.', $back); }
	$app['volume_folder'] = $app['volume_folder'].$app['issueorgroup_folder'];

	$r = $db->query("SELECT cust_name, cust_id, id FROM `adm_customer_master` WHERE id = ".(int)$app['cust_id']);
	$cust = $r ? $r->fetch_assoc() : array('cust_name' => '');
	$r = $db->query("SELECT * FROM `adm_journals` WHERE j_id = ".(int)$app['j_id']);   /* * : j_jstage_wf only exists after the JSTAGE migration */
	$jr = $r ? $r->fetch_assoc() : array('j_code' => '', 'j_name' => '', 'j_fin_wf' => '', 'j_rev_wf' => '', 'j_jstage_wf' => '');
	$r = $db->query("SELECT r_id FROM inw_revisions_dtl WHERE inw_id = ".$id." AND `type` = '".$kind."'");
	$count = ($r ? $r->num_rows : 0) + 1;
	$booked = $kind.$count;
	$wfmap = bk_wf_map($db, $is_fin ? $jr['j_fin_wf'] : ($kind === 'JSTAGE' ? (isset($jr['j_jstage_wf']) ? $jr['j_jstage_wf'] : '') : $jr['j_rev_wf']));
	if ($kind === 'JSTAGE' && !$wfmap) { bk_fail('No JSTAGE workflow', 'This journal has no JSTAGE workflow yet. Choose one in Journals > Edit > JSTAGE Workflow, then book again.', $back); }

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'insert') {
		if ($app['status'] !== 'Client Review') {
			bk_fail('Already booked', 'This article is no longer under client review (it is now '.$app['stage'].' / '.$app['status'].'), so it cannot be booked again.', $back);
		}
		$recv = bk_date(isset($_POST['recv_dt']) ? $_POST['recv_dt'] : ''); $due = bk_date(isset($_POST['due_dt']) ? $_POST['due_dt'] : '');
		if (!$recv || !$due) { bk_fail('Invalid date', 'Please go back and enter the received and due dates as dd-mm-yyyy.', $back); }
		if ($due < $recv) { bk_fail('Invalid date', 'The due date cannot be before the received date.', $back); }
		$pages = max(0, (int)(isset($_POST['correction_pages']) ? $_POST['correction_pages'] : 0));
		$wf_id = (string)(int)(isset($_POST['wf_id']) ? $_POST['wf_id'] : 0);
		if (!isset($wfmap[$wf_id])) { bk_fail('Workflow not allowed', 'The chosen workflow is not available for this journal.', $back); }
		$instructions = trim((string)(isset($_POST['instructions']) ? $_POST['instructions'] : ''));

		$r = $db->query("SELECT wf_id, wf_code, wf_name FROM `adm_workflow_master` WHERE wf_id = ".(int)$wf_id);
		$wf = $r ? $r->fetch_assoc() : null;
		$codes = $wf ? explode(',', $wf['wf_code']) : array();
		$first_code = $codes ? (int)$codes[0] : 0;
		$r = $first_code ? $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".$first_code) : false;
		$dept_res = $r ? $r->fetch_assoc() : null;
		if (!$dept_res) { bk_fail('Workflow has no department', 'The chosen workflow has no first department configured.', $back); }

		$Cn = $cust['cust_name']; $jn = $jr['j_code'];
		$destination_dir = WF_DIR."/downloads/JOURNALS/".$Cn."/".$jn."/".$app['volume_folder']."/".$app['pub_id']."/".$booked."/";
		if (!is_dir($destination_dir) && !@mkdir($destination_dir, 0777, true)) { bk_fail('Could not create the folder', 'The download folder for '.$booked.' could not be created on the server. Nothing was booked.', $back); }

		$base = $app['work_dir'].$app['volume_folder']."/".$app['pub_id']."/";
		$src = $base.$app['stage']."/"; $dst = $base.$booked."/";
		$flow = bk_file_flow($src);                       // what is in the previous stage, before it moves
		bk_move_files($src, $dst);
		if (!is_dir($dst.$dept_res['dept_name']) && !@mkdir($dst.$dept_res['dept_name'], 0777, true)) {
			bk_fail('Could not create the folder', 'The '.$dept_res['dept_name'].' folder for '.$booked.' could not be created. The files may already have moved - please tell support.', $back);
		}

		$uid = isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : '';
		$ok = $db->query("INSERT INTO `inw_revisions_dtl` (`inw_id`,revision_count,`received_date`, `due_date`, `correction_pages`, `comments`, `file_name`, `created_by`, `created_on`, `type`) VALUES ("
			.$id.", ".$count.", '".$recv."', '".$due."', '".$pages."', '".$db->real_escape_string($instructions)."', '".$db->real_escape_string($destination_dir)."', '".$db->real_escape_string($uid)."', NOW(), '".$kind."')");
		if (!$ok) { bk_fail('Could not save the booking', 'The booking record could not be saved ('.$db->error.'). The files may already have moved - please tell support.', $back); }
		$ok = $db->query("UPDATE inw_inward_dtl SET stage = '".$booked."', wf_id = '".(int)$wf_id."', `status` = '".$db->real_escape_string($dept_res['dept_name'])."', `department_id` = '".$first_code."' WHERE id = ".$id);
		if (!$ok) { bk_fail('Could not update the article', 'The article could not be moved to '.$booked.' ('.$db->error.'). Please tell support.', $back); }
		if ($kind === 'REV') {
			$by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $uid;
			$db->query("INSERT INTO inw_instructions_dtl(`inw_id`,`stage`,`instructions`,`created_by`,`created_dt`) VALUES (".$id.", '".$booked."', '".$db->real_escape_string($instructions)."', '".$db->real_escape_string($by)."', NOW())");
			$_SESSION['rses_inw_id'] = $id; $_SESSION['rses_stage'] = $booked;
		}

		/* tell the booking recipients (To / CC / BCC from Email Alerts and Logs) */
		bk_notify($db, array(
			'kind' => $kind, 'booked_stage' => $booked, 'prev_stage' => $app['stage'], 'prev_status' => (string)$app['status'], 'article_id' => $id, 'pub_id' => $app['pub_id'],
			'customer' => $Cn, 'journal' => $jn, 'recv' => $recv, 'due' => $due, 'pages' => $pages, 'instructions' => $instructions,
			'wf_name' => $wfmap[$wf_id]['name'], 'depts' => $wfmap[$wf_id]['depts'], 'first_dept' => $dept_res['dept_name'], 'flow' => $flow,
			'src' => $src, 'dest' => $dst, 'user_id' => $uid, 'user_name' => isset($_COOKIE['full_name']) ? $_COOKIE['full_name'] : '', 'upload_url' => ''));

		header("location:".$_COOKIE['set_url']."/".($kind === 'JSTAGE' ? "upload_jstage_files.php" : ($is_fin ? "upload_fin_files.php" : "upload_rfiles.php"))."?id=".$id);
		exit;
	}

	$flow = bk_file_flow($app['work_dir'].$app['volume_folder']."/".$app['pub_id']."/".$app['stage']."/");
	return array('kind' => $kind, 'app_res' => $app, 'customer_info' => $cust, 'journal_info' => $jr, 'revision_count' => $count, 'wfmap' => $wfmap, 'flow' => $flow, 'selected_wf' => '');
}
