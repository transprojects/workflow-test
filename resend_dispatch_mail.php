<?php include "includes/includes.php";
include_once 'includes/list_ui.php';
date_default_timezone_set('Asia/Kolkata');

/* Articles > "Resend Dispatch Mail": type a pub_id, pick one of the dispatch mails already sent for that article, press Send again.
 * To      : inw_dispatch_history.to_mail
 * CC/BCC  : inw_dispatch_history.to_cc / to_bcc  +  adm_journals.j_cc_email / j_bcc_email
 * Subject / text : the history row; when empty the inw_dispatch_mail_master entry (customer, journal, stage), [##FileName##] = pub_id
 * SMTP    : email_settings id = 2
 * Every press is written to inw_dispatch_resend_log (who, when, to/cc/bcc, result). */

$db->query("CREATE TABLE IF NOT EXISTS `inw_dispatch_resend_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT, `idh_id` int(11) NOT NULL, `a_id` int(11) DEFAULT NULL, `pub_id` varchar(150) DEFAULT NULL,
  `stage` varchar(25) DEFAULT NULL, `mail_to` text, `mail_cc` text, `mail_bcc` text, `subject` text,
  `status` varchar(10) NOT NULL, `error` varchar(255) DEFAULT NULL, `sent_by` varchar(30) DEFAULT NULL, `sent_on` datetime NOT NULL,
  PRIMARY KEY (`id`), KEY `idh_id` (`idh_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function rdm_clean_list($s) {
	$out = array();
	foreach (preg_split('/[,;\s]+/', (string)$s) as $x) {
		$x = trim($x);
		if ($x !== '' && $x !== '-' && filter_var($x, FILTER_VALIDATE_EMAIL)) { $out[strtolower($x)] = $x; }
	}
	return $out;
}
function rdm_merge() {
	$o = array();
	foreach (func_get_args() as $l) { foreach (rdm_clean_list($l) as $k => $v) { $o[$k] = $v; } }
	return $o;
}
/* fill the mail-master tags from the article */
function rdm_fill($t, $x) {
	$title = !empty($x['article_title']) ? $x['article_title'] : $x['pub_id'];
	return str_replace(array('[##FileName##]', '##FileName##', '##ARTICLEID##', '##ARTICLETITLE##', '##AuthorNames##'),
		array($x['pub_id'], $x['pub_id'], $x['pub_id'], $title, (string)$x['corresponding_author']), (string)$t);
}
/* the mail that would go out for one dispatch-history row */
function rdm_build($db, $idh) {
	$r = $db->query("SELECT d.idh_id, d.cust_id, d.j_id, d.a_id, d.to_mail, d.to_cc, d.to_bcc, d.mail_subject, d.mail_text, d.stage, d.sent_date,
			i.pub_id, i.article_title, i.corresponding_author, j.j_cc_email, j.j_bcc_email, m.subject AS tpl_subject, m.message AS tpl_message
		FROM inw_dispatch_history d
		JOIN inw_inward_dtl i ON i.id = d.a_id
		LEFT JOIN adm_journals j ON j.j_id = d.j_id AND j.j_cust_id = d.cust_id
		LEFT JOIN inw_dispatch_mail_master m ON m.cust_id = d.cust_id AND m.j_id = d.j_id AND m.stage = d.stage
		WHERE d.idh_id = " . (int)$idh . " LIMIT 1");
	$x = $r ? $r->fetch_assoc() : null;
	if (!$x) { return null; }
	$to = rdm_clean_list($x['to_mail']);
	$cc = rdm_merge($x['to_cc'], $x['j_cc_email']);
	$bcc = rdm_merge($x['to_bcc'], $x['j_bcc_email']);
	foreach ($to as $k => $_) { unset($cc[$k], $bcc[$k]); }
	foreach ($cc as $k => $_) { unset($bcc[$k]); }
	$subj = trim((string)$x['mail_subject']) !== '' ? $x['mail_subject'] : (string)$x['tpl_subject'];
	$body = trim((string)$x['mail_text']) !== '' ? $x['mail_text'] : (string)$x['tpl_message'];
	return array('row' => $x, 'to' => $to, 'cc' => $cc, 'bcc' => $bcc,
		'subject' => rdm_fill($subj, $x), 'body' => rdm_fill($body, $x));
}

/* ---------- preview (AJAX, POST): what would go out, nothing is sent ---------- */
if (isset($_POST['action']) && $_POST['action'] === 'preview') {
	header('Content-Type: application/json; charset=utf-8');
	$m = rdm_build($db, isset($_POST['idh_id']) ? $_POST['idh_id'] : 0);
	if (!$m) { echo json_encode(array('ok' => false, 'message' => 'Dispatch entry not found.')); exit; }
	echo json_encode(array('ok' => true, 'pub_id' => $m['row']['pub_id'], 'stage' => $m['row']['stage'],
		'to' => implode(', ', $m['to']), 'cc' => implode(', ', $m['cc']), 'bcc' => implode(', ', $m['bcc']),
		'subject' => $m['subject'], 'body' => $m['body']));
	exit;
}

/* ---------- send again (AJAX, POST) ---------- */
if (isset($_POST['action']) && $_POST['action'] === 'resend') {
	header('Content-Type: application/json; charset=utf-8');
	$out = function ($ok, $msg) { echo json_encode(array('ok' => $ok, 'message' => $msg)); exit; };
	$m = rdm_build($db, isset($_POST['idh_id']) ? $_POST['idh_id'] : 0);
	if (!$m) { $out(false, 'Dispatch entry not found.'); }
	$err = null;
	if (!$m['to']) { $err = 'No valid To address on this dispatch entry.'; }
	elseif (trim($m['subject']) === '') { $err = 'No subject (dispatch entry and mail master are empty).'; }
	elseif (trim($m['body']) === '') { $err = 'No mail text (dispatch entry and mail master are empty).'; }
	if (!$err) {
		$es = $db->query("SELECT * FROM email_settings WHERE id = 2"); $es = $es ? $es->fetch_assoc() : null;
		if (!$es) { $err = 'email_settings (id 2) not found.'; }
	}
	if (!$err) {
		try {
			require_once __DIR__ . '/email-functions.php';
			$ok = sendMail($es['host'], $es['port'], $es['secure'], $es['from_email'], $es['from_password'], $es['from_email'], $es['from_name'],
				implode(',', $m['to']), implode(',', $m['cc']), implode(',', $m['bcc']), $m['subject'], $m['body']);
			if (!$ok) { $err = 'Mail server refused the mail - see smtp_mail.log.'; }
		} catch (\Throwable $ex) { $err = substr($ex->getMessage(), 0, 200); }
	}
	$st = $err ? 'Failed' : 'Sent';
	$q = function ($v) use ($db) { return $db->real_escape_string((string)$v); };
	$db->query("INSERT INTO inw_dispatch_resend_log (idh_id, a_id, pub_id, stage, mail_to, mail_cc, mail_bcc, subject, status, error, sent_by, sent_on) VALUES ("
		. (int)$m['row']['idh_id'] . ", " . (int)$m['row']['a_id'] . ", '" . $q($m['row']['pub_id']) . "', '" . $q($m['row']['stage']) . "', '"
		. $q(implode(',', $m['to'])) . "', '" . $q(implode(',', $m['cc'])) . "', '" . $q(implode(',', $m['bcc'])) . "', '" . $q($m['subject']) . "', '$st', "
		. ($err ? "'" . $q($err) . "'" : 'NULL') . ", '" . $q(isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : '') . "', NOW())");
	$out(!$err, $err ? $err : 'Mail sent to ' . implode(', ', $m['to']));
}

/* ---------- search ---------- */
$e = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES); };
$pub = isset($_GET['pub']) ? trim($_GET['pub']) : '';
$arts = array();
if ($pub !== '') {
	$r = $db->query("SELECT i.id, i.pub_id, i.status, i.stage, c.cust_name, j.j_name
		FROM inw_inward_dtl i LEFT JOIN adm_customer_master c ON c.id = i.cust_id LEFT JOIN adm_journals j ON j.j_id = i.j_id
		WHERE i.pub_id LIKE '%" . $db->real_escape_string($pub) . "%' ORDER BY (i.pub_id = '" . $db->real_escape_string($pub) . "') DESC, i.id DESC LIMIT 20");
	while ($r && ($x = $r->fetch_assoc())) {
		$x['mails'] = array();
		$h = $db->query("SELECT d.idh_id, d.stage, d.sent_date, d.to_mail, d.mail_subject, d.dispatch_type,
				(SELECT m.subject FROM inw_dispatch_mail_master m WHERE m.cust_id = d.cust_id AND m.j_id = d.j_id AND m.stage = d.stage LIMIT 1) AS tpl_subject,
				(SELECT COUNT(*) FROM inw_dispatch_resend_log l WHERE l.idh_id = d.idh_id AND l.status = 'Sent') AS resent,
				(SELECT MAX(l.sent_on) FROM inw_dispatch_resend_log l WHERE l.idh_id = d.idh_id AND l.status = 'Sent') AS last_resent
			FROM inw_dispatch_history d WHERE d.a_id = " . (int)$x['id'] . " ORDER BY d.sent_date DESC");
		while ($h && ($y = $h->fetch_assoc())) {
			$y['subject_show'] = trim((string)$y['mail_subject']) !== '' ? $y['mail_subject'] : rdm_fill($y['tpl_subject'], $x);
			$x['mails'][] = $y;
		}
		$arts[] = $x;
	}
}
$base = isset($_COOKIE['set_url']) ? $_COOKIE['set_url'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <?php include('includes/title.php'); ?>
  <?php echo styles(); ?>
  <?php include_once 'includes/modern-ui.php'; include_once 'includes/article-filter-ui.php'; mu_head(); af_head(); ?>
  <style>
    .rd-card{margin-bottom:16px;padding-top:12px;}
    .rd-head{display:flex;flex-wrap:wrap;align-items:center;gap:8px 12px;padding:2px 4px 8px;}
    .rd-head .t{font-size:17px;font-weight:700;color:#14304a;letter-spacing:.2px;margin-right:6px;}
    .rd-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:20px;background:#eaf2fa;color:#1b6ca8;font-size:12px;font-weight:600;}
    .rd-chip.o{background:#fdeee3;color:#e8742a;} .rd-chip.g{background:#eef1f5;color:#6b7785;}
    .rd-stage{display:inline-block;min-width:46px;text-align:center;padding:4px 12px;border-radius:20px;font-weight:700;font-size:12px;background:#e1effa;color:#2a7fc1;}
    .rd-stage.fp{background:#fdeee3;color:#e8742a;} .rd-stage.fin{background:#e3f6ec;color:#1e9e61;} .rd-stage.rev{background:#f1e9fb;color:#7a4fc2;}
    .rd-to{white-space:normal !important;word-break:break-all;max-width:300px;min-width:180px;}
    .rd-sub{white-space:normal !important;min-width:240px;}
    .rd-times{display:inline-block;padding:3px 10px;border-radius:20px;background:#e3f6ec;color:#1e9e61;font-weight:700;font-size:12px;}
    .rd-times small{display:block;font-weight:600;color:#6b7785;font-size:10.5px;}
    .rd-btn{display:inline-flex;align-items:center;gap:7px;height:36px;padding:0 16px;border:0;border-radius:10px;font-weight:700;color:#fff;background:linear-gradient(135deg,#e8742a,#f08a45);cursor:pointer;box-shadow:0 4px 12px rgba(232,116,42,.35);transition:transform .15s,box-shadow .2s;}
    .rd-btn:hover{transform:translateY(-2px);box-shadow:0 8px 18px rgba(232,116,42,.45);} .rd-btn:disabled{opacity:.55;cursor:default;transform:none;}
    .rd-empty{padding:46px 20px;text-align:center;color:#6b7785;background:#fff;border:1px dashed #cfd8e3;border-radius:16px;}
    .rd-empty i{display:block;font-size:34px;color:#c3cfdb;margin-bottom:10px;} .rd-empty b{color:#14304a;}
    .rd-ov{position:fixed;inset:0;background:rgba(20,48,74,.55);backdrop-filter:blur(3px);display:none;align-items:center;justify-content:center;z-index:9999;padding:16px;}
    .rd-ov.on{display:flex;} .rd-ov.on .rd-box{animation:muPop .25s ease both;}
    .rd-box{background:#fff;border-radius:16px;width:min(840px,100%);max-height:92vh;display:flex;flex-direction:column;box-shadow:0 24px 70px rgba(0,0,0,.4);overflow:hidden;}
    .rd-box h3{margin:0;padding:15px 20px;color:#fff;font-size:16px;font-weight:700;background:linear-gradient(120deg,#14304a,#1f4b73 60%,#1b6ca8);}
    .rd-meta{padding:14px 20px 4px;font-size:13px;color:#22303f;} .rd-meta div{display:flex;gap:12px;margin-bottom:6px;}
    .rd-meta b{flex:0 0 60px;color:#6b7785;font-size:11px;text-transform:uppercase;letter-spacing:.5px;padding-top:2px;} .rd-meta span{word-break:break-all;}
    .rd-frame{flex:1;min-height:260px;margin:8px 20px;border:1px solid #e4e9ef;border-radius:12px;width:calc(100% - 40px);background:#fff;}
    .rd-foot{padding:12px 20px;display:flex;gap:10px;justify-content:flex-end;align-items:center;border-top:1px solid #eef1f5;background:#f9fbfd;}
    .rd-foot .msg{margin-right:auto;font-size:13px;} .rd-foot .msg.bad{color:#d64545;font-weight:600;} .rd-foot .msg.ok{color:#1e9e61;font-weight:600;}
    .rd-cancel{height:36px;padding:0 18px;border:1px solid #d5dde6;border-radius:10px;background:#fff;color:#14304a;font-weight:700;cursor:pointer;}
  </style>
</head>
<body>
<div class="wholeouter">
  <?php include("includes/top-header.php"); ?>
  <div class="af-page" style="padding-bottom:0;">
    <div class="af-hero"><div><small>Articles</small><h1>Resend Dispatch Mail</h1></div></div>
  </div>
  <div class="af-page"><div class="af-wrap">
    <form class="formouter" method="get" action="resend_dispatch_mail.php">
      <div class="af-bar">
        <div class="af-field search" style="max-width:420px;">
          <label>Article ID (pub_id)</label>
          <input class="form-control" type="text" name="pub" value="<?php echo $e($pub); ?>" placeholder="Enter Article ID" autofocus>
        </div>
        <div class="af-actions" style="margin-left:0;">
          <button type="submit" class="af-btn primary"><i class="fa fa-search"></i> Search</button>
          <a class="af-btn" href="resend_dispatch_mail.php" title="Clear"><i class="fa fa-refresh"></i></a>
        </div>
      </div>
    </form>
  </div></div>
  <div class="af-page" style="padding-top:0;">
<?php if ($pub === '') { ?>
    <div class="rd-empty"><i class="fa fa-envelope-o"></i><b>Enter an article ID and press Search</b><br>The dispatch mails already sent for the article are listed. Choose one, check the preview and send it again.</div>
<?php } elseif (!$arts) { ?>
    <div class="rd-empty"><i class="fa fa-search"></i><b>No article found for &ldquo;<?php echo $e($pub); ?>&rdquo;</b></div>
<?php } else { foreach ($arts as $a) { ?>
    <div class="af-card rd-card">
      <div class="rd-head">
        <span class="t"><?php echo $e($a['pub_id']); ?></span>
        <span class="rd-chip"><i class="fa fa-user-o"></i> <?php echo $e(html_entity_decode($a['cust_name'])); ?></span>
        <span class="rd-chip g"><i class="fa fa-book"></i> <?php echo $e(html_entity_decode($a['j_name'])); ?></span>
        <span class="rd-chip o"><?php echo $e(trim(strip_tags($a['status']))); ?> &middot; <?php echo $e($a['stage']); ?></span>
      </div>
<?php if (!$a['mails']) { ?>
      <div style="padding:14px 6px;color:#6b7785;">No dispatch mail has been recorded for this article yet.</div>
<?php } else { ?>
      <table class="dataTable">
        <thead><tr><th>Stage</th><th>Sent on</th><th>To</th><th>Subject</th><th>Resent</th><th></th></tr></thead>
        <tbody>
<?php foreach ($a['mails'] as $m) { $sg = strtoupper($m['stage']); $sc = strpos($sg, 'FP') === 0 ? 'fp' : (strpos($sg, 'FIN') === 0 ? 'fin' : (strpos($sg, 'REV') === 0 ? 'rev' : '')); ?>
          <tr class="af-in">
            <td><span class="rd-stage <?php echo $sc; ?>"><?php echo $e($m['stage']); ?></span></td>
            <td><?php echo $e(date('d-M-Y', strtotime($m['sent_date']))); ?><div style="font-size:11.5px;color:#6b7785"><?php echo $e(date('H:i', strtotime($m['sent_date']))); ?></div></td>
            <td class="rd-to"><?php echo $e($m['to_mail']); ?></td>
            <td class="rd-sub"><?php echo $e(trim($m['subject_show']) !== '' ? $m['subject_show'] : '(no subject set)'); ?></td>
            <td><?php echo (int)$m['resent'] ? '<span class="rd-times">' . (int)$m['resent'] . '&times;<small>' . $e(date('d-M H:i', strtotime($m['last_resent']))) . '</small></span>' : '<span style="color:#b3bcc7">&ndash;</span>'; ?></td>
            <td style="text-align:right"><button type="button" class="rd-btn" data-idh="<?php echo (int)$m['idh_id']; ?>"><i class="fa fa-eye"></i> Preview &amp; send</button></td>
          </tr>
<?php } ?>
        </tbody>
      </table>
<?php } ?>
    </div>
<?php } } ?>
  </div>
</div>
<div class="rd-ov" id="rd-ov"><div class="rd-box">
  <h3 id="rd-title">Mail preview</h3>
  <div class="rd-meta">
    <div><b>To</b><span id="rd-to"></span></div><div><b>CC</b><span id="rd-cc"></span></div>
    <div><b>BCC</b><span id="rd-bcc"></span></div><div><b>Subject</b><span id="rd-sub" style="font-weight:700"></span></div>
  </div>
  <iframe class="rd-frame" id="rd-frame" sandbox=""></iframe>
  <div class="rd-foot"><span class="msg" id="rd-msg"></span><button type="button" class="rd-cancel" id="rd-close">Close</button><button type="button" class="rd-btn" id="rd-send">Send mail</button></div>
</div></div>
<?php echo scripts(); ?>
<script>
(function () {
  var ov = document.getElementById('rd-ov'), send = document.getElementById('rd-send'), msg = document.getElementById('rd-msg'), cur = 0;
  function post(data) { var fd = new FormData(); for (var k in data) { fd.append(k, data[k]); } return fetch('resend_dispatch_mail.php', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }); }
  function say(t, c) { msg.textContent = t; msg.className = 'msg ' + (c || ''); }
  function close() { ov.classList.remove('on'); document.getElementById('rd-frame').srcdoc = ''; }
  document.getElementById('rd-close').onclick = close;
  ov.addEventListener('click', function (e) { if (e.target === ov) { close(); } });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { close(); } });
  document.addEventListener('click', function (ev) {
    var b = ev.target.closest ? ev.target.closest('.rd-btn[data-idh]') : null; if (!b) { return; }
    cur = b.getAttribute('data-idh'); b.disabled = true; b.innerHTML = 'Loading...';
    post({ action: 'preview', idh_id: cur }).then(function (j) {
      if (!j.ok) { alert(j.message); return; }
      document.getElementById('rd-title').textContent = 'Mail preview - ' + j.pub_id + ' (' + j.stage + ')';
      document.getElementById('rd-to').textContent = j.to || '-'; document.getElementById('rd-cc').textContent = j.cc || '-';
      document.getElementById('rd-bcc').textContent = j.bcc || '-'; document.getElementById('rd-sub').textContent = j.subject || '-';
      document.getElementById('rd-frame').srcdoc = j.body || '<p style="color:#999">(no mail text)</p>';
      send.disabled = !j.to; send.textContent = 'Send mail'; say(j.to ? '' : 'No valid To address - cannot send.', j.to ? '' : 'bad');
      ov.classList.add('on');
    }).catch(function () { alert('Could not reach the server.'); })
      .then(function () { b.disabled = false; b.innerHTML = '<i class="fa fa-eye"></i> Preview &amp; send'; });
  });
  send.onclick = function () {
    if (send.disabled) { return; } send.disabled = true; send.textContent = 'Sending...'; say('');
    post({ action: 'resend', idh_id: cur }).then(function (j) {
      say(j.message, j.ok ? 'ok' : 'bad');
      if (j.ok) { send.textContent = 'Sent'; setTimeout(function () { location.reload(); }, 1800); } else { send.disabled = false; send.textContent = 'Send mail'; }
    }).catch(function () { say('Could not reach the server.', 'bad'); send.disabled = false; send.textContent = 'Send mail'; });
  };
})();
</script>
<?php echo footer(); ?>
</body>
</html>
