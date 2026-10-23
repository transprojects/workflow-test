<?php include "includes/includes.php";
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__.'/sw_switch_lib.php';

/* Email Alerts and Logs (Start Work switch + Revise / Final booking alerts).
   Section "switch": who is told when a user goes back from the new Start Work page to the old one.
   Section "book":   who is told when an article is booked as REV<n> / FIN<n> (Under Client Review > Revises / Final).
   Portal Admin only (user_level 1 = Portal Admin in user_role_master). */
$sw_is_admin = isset($_COOKIE['user_level']) && (string)$_COOKIE['user_level'] === '1';
$msg = ''; $msg_type = 'ok'; $msg_kind = '';
$sections = array(
	'switch' => array('title' => 'Start Work: switch back to the old page', 'desc' => 'When a user goes back from the new Start Work page to the old one, they must give a reason. It is e-mailed to these addresses and logged below.', 'saved' => 'Saved. The next "Use old version" switch will be e-mailed to these addresses.'),
	'book'   => array('title' => 'Booking: Revise / Final',               'desc' => 'When an article is booked as REV1, REV2 ... or FIN1, FIN2 ... an alert (who, when, workflow, first department, file flow) is e-mailed to these addresses and logged below.', 'saved' => 'Saved. The next Revise / Final booking will be e-mailed to these addresses.'),
	'filemove' => array('title' => 'Start Work: file movement not configured', 'desc' => 'When a user clicks "Report to admin" on the "No file movement configured" banner of Start Work, the problem (article, stage, department-wise what is set up) is e-mailed to these addresses and logged in the File Movement Log (kind CONFIG). Until this is saved, the Start Work switch addresses above are used.', 'saved' => 'Saved. The next "file movement not configured" report will be e-mailed to these addresses.'),
	'dispatch' => array('title' => 'Project Dispatch: dispatch report', 'desc' => 'Every dispatch from the new Project Dispatch page (sent or failed) e-mails a report to these addresses: who, what, to whom, which files went with their sizes and the files inside zips, and how it ended. The full record is in Reports > Dispatch Log.', 'saved' => 'Saved. The next dispatch will be reported to these addresses.'),
);

if ($sw_is_admin) {
	sw_switch_ensure($db);

	/* attachment download: ?dl=<log id>&i=<file index> (Portal Admin only; files are not directly reachable by URL) */
	if (isset($_GET['dl'])) {
		$r = $db->query("SELECT files FROM sw_switch_log WHERE id = ".(int)$_GET['dl']);
		$row = $r ? $r->fetch_assoc() : null;
		$list = ($row && $row['files']) ? json_decode($row['files'], true) : array();
		$i = isset($_GET['i']) ? (int)$_GET['i'] : -1;
		if (is_array($list) && isset($list[$i])) {
			$path = realpath(sw_switch_dir().'/'.$list[$i]['s']); $base = realpath(sw_switch_dir());
			if ($path && $base && strpos($path, $base) === 0 && is_file($path)) {
				while (ob_get_level()) { ob_end_clean(); }
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="'.preg_replace('/[^A-Za-z0-9._ -]+/', '_', $list[$i]['n']).'"');
				header('Content-Length: '.filesize($path));
				readfile($path); exit;
			}
		}
		http_response_code(404); echo 'File not found.'; exit;
	}
	if (empty($_SESSION['sw_switch_csrf'])) { $_SESSION['sw_switch_csrf'] = bin2hex(random_bytes(16)); }

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$kind = (isset($_POST['kind']) && isset($sections[$_POST['kind']])) ? $_POST['kind'] : 'switch';
		$msg_kind = $kind;
		if (!isset($_POST['csrf']) || !hash_equals($_SESSION['sw_switch_csrf'], (string)$_POST['csrf'])) {
			$msg = 'The form expired. Please try again.'; $msg_type = 'bad';
		} else {
			$to = sw_switch_parse_list(isset($_POST['to']) ? $_POST['to'] : '');
			$cc = sw_switch_parse_list(isset($_POST['cc']) ? $_POST['cc'] : '');
			$bcc = sw_switch_parse_list(isset($_POST['bcc']) ? $_POST['bcc'] : '');
			$bad = array_merge($to['bad'], $cc['bad'], $bcc['bad']);
			if ($bad) { $msg = 'These are not valid e-mail addresses: '.implode(', ', $bad); $msg_type = 'bad'; }
			elseif (!$to['valid']) { $msg = 'Please give at least one address in "To".'; $msg_type = 'bad'; }
			elseif (sw_mail_save($db, $kind, implode(', ', $to['valid']), implode(', ', $cc['valid']), implode(', ', $bcc['valid']))) { $msg = $sections[$kind]['saved']; }
			else { $msg = 'Could not save the settings.'; $msg_type = 'bad'; }
		}
	}
	$cfg = array('switch' => sw_mail_get($db, 'switch'), 'book' => sw_mail_get($db, 'book'), 'dispatch' => sw_mail_get($db, 'dispatch'), 'filemove' => sw_mail_get($db, 'filemove'));
	if ($msg_type === 'bad' && $_SERVER['REQUEST_METHOD'] === 'POST') {   /* keep what was typed so it can be corrected */
		$cfg[$msg_kind] = array('to' => isset($_POST['to']) ? $_POST['to'] : '', 'cc' => isset($_POST['cc']) ? $_POST['cc'] : '', 'bcc' => isset($_POST['bcc']) ? $_POST['bcc'] : '');
	}
	$log = array();
	$r = $db->query("SELECT * FROM sw_switch_log ORDER BY id DESC LIMIT 50");
	while ($r && ($row = $r->fetch_assoc())) { $log[] = $row; }
	$blog = array();
	$r = $db->query("SELECT * FROM sw_book_log ORDER BY id DESC LIMIT 50");
	while ($r && ($row = $r->fetch_assoc())) { $blog[] = $row; }
}
function swh($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function sw_cls($s) { return (strpos($s, 'Sent') === 0) ? 'st-sent' : ((strpos($s, 'Failed') === 0) ? 'st-fail' : 'st-skip'); }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <style>
      .sws{max-width:980px;margin:26px auto 40px;padding:0 18px;color:#22303f;font-size:14px;}
      .sws h1{font-size:22px;font-weight:700;color:#14304a;margin:0 0 4px;}
      .sws h2{font-size:17px;font-weight:700;color:#14304a;margin:0 0 4px;}
      .sws .sub{color:#6b7785;margin-bottom:14px;}
      .sws .card{background:#fff;border:1px solid #e0e8f0;border-radius:14px;padding:20px 22px;margin-bottom:18px;box-shadow:0 6px 20px rgba(20,48,74,.06);}
      .sws label{display:block;font-weight:600;color:#14304a;margin:0 0 4px;}
      .sws .hint{color:#6b7785;font-size:12.5px;margin:0 0 6px;}
      .sws textarea{width:100%;min-height:70px;border:1px solid #cfd8e3;border-radius:10px;padding:10px 12px;font-family:inherit;font-size:14px;resize:vertical;}
      .sws textarea:focus{outline:0;border-color:#1b6ca8;box-shadow:0 0 0 3px rgba(27,108,168,.15);}
      .sws .row2{margin-bottom:14px;}
      .sws .btn-save{background:#1b6ca8;color:#fff;border:0;border-radius:10px;padding:10px 26px;font-weight:600;cursor:pointer;}
      .sws .btn-save:hover{filter:brightness(1.08);}
      .sws .flash{padding:11px 14px;border-radius:10px;margin-bottom:14px;font-weight:600;}
      .sws .flash.ok{background:#e9f7ef;color:#177a49;border:1px solid #bfe6cf;} .sws .flash.bad{background:#fdecec;color:#b13434;border:1px solid #f3c1c1;}
      .sws table{width:100%;border-collapse:collapse;font-size:13px;}
      .sws th{background:#eef3f8;color:#14304a;text-align:left;padding:8px 10px;border:1px solid #dde5ee;}
      .sws td{padding:8px 10px;border:1px solid #e5ebf2;vertical-align:top;}
      .sws a.swf{color:#1b6ca8;text-decoration:none;font-size:12.5px;word-break:break-all;} .sws a.swf:hover{text-decoration:underline;}
      .sws .st-sent{color:#177a49;font-weight:600;} .sws .st-fail{color:#b13434;font-weight:600;} .sws .st-skip{color:#8a6d1a;font-weight:600;}
      .sws .tag{display:inline-block;padding:2px 9px;border-radius:999px;background:#fff3e0;color:#b86a00;font-weight:700;font-size:12px;}
    </style>
  </head>
  <body>
    <div class="wholeouter">
    <?php include("includes/top-header.php"); ?>
      <div class="sws">
<?php if (!$sw_is_admin) { ?>
        <div class="card"><h1>Email Alerts and Logs</h1><div class="flash bad" style="margin:12px 0 0;">Only a Portal Admin can open this page.</div></div>
<?php } else { ?>
        <h1>Email Alerts and Logs</h1>
        <div class="sub">Who receives each workflow alert, and a log of what was sent. Separate several addresses with a comma, semicolon or new line.</div>
<?php foreach ($sections as $kind => $sec) { ?>
<?php if ($msg && $msg_kind === $kind) { ?><div class="flash <?php echo $msg_type; ?>"><?php echo swh($msg); ?></div><?php } ?>
        <form method="post" class="card" autocomplete="off">
          <input type="hidden" name="csrf" value="<?php echo swh($_SESSION['sw_switch_csrf']); ?>">
          <input type="hidden" name="kind" value="<?php echo swh($kind); ?>">
          <h2><?php echo swh($sec['title']); ?></h2>
          <div class="sub"><?php echo swh($sec['desc']); ?></div>
          <div class="row2"><label for="to_<?php echo $kind; ?>">To <span style="color:#d64545">*</span></label>
            <textarea id="to_<?php echo $kind; ?>" name="to" placeholder="name@transforma.in, other@transforma.in"><?php echo swh($cfg[$kind]['to']); ?></textarea></div>
          <div class="row2"><label for="cc_<?php echo $kind; ?>">CC</label><textarea id="cc_<?php echo $kind; ?>" name="cc"><?php echo swh($cfg[$kind]['cc']); ?></textarea></div>
          <div class="row2"><label for="bcc_<?php echo $kind; ?>">BCC</label><textarea id="bcc_<?php echo $kind; ?>" name="bcc"><?php echo swh($cfg[$kind]['bcc']); ?></textarea></div>
          <button type="submit" class="btn-save">Save</button>
        </form>
<?php } ?>

        <div class="card">
          <h2 style="margin-bottom:10px;">Recent bookings (last 50)</h2>
<?php if (!$blog) { ?>
          <div class="sub" style="margin:0;">No article has been booked as a Revise or Final yet.</div>
<?php } else { ?>
          <table>
            <tr><th style="width:130px;">When</th><th>Booked by</th><th>Article</th><th>Booked as</th><th>Workflow / first dept</th><th style="width:120px;">E-mail</th></tr>
<?php foreach ($blog as $l) { ?>
            <tr><td><?php echo swh(date('d-M-Y h:i A', strtotime($l['created_at']))); ?></td><td><?php echo swh($l['user_name']); ?></td>
                <td><?php echo swh($l['pub_id']); ?></td><td><span class="tag"><?php echo swh($l['booked_stage']); ?></span> <span style="color:#8a95a1;font-size:12px;">from <?php echo swh($l['prev_stage']); ?></span></td>
                <td><?php echo swh($l['workflow']); ?><br><span style="color:#6b7785;font-size:12px;">starts with <?php echo swh($l['first_dept']); ?></span></td>
                <td class="<?php echo sw_cls($l['mail_status']); ?>"><?php echo swh($l['mail_status']); ?></td></tr>
<?php } ?>
          </table>
<?php } ?>
        </div>

        <div class="card">
          <h2 style="margin-bottom:10px;">Recent switches to the old pages (last 50)</h2>
<?php if (!$log) { ?>
          <div class="sub" style="margin:0;">Nobody has switched back to the old page yet.</div>
<?php } else { ?>
          <table>
            <tr><th style="width:130px;">When</th><th>Page</th><th>User</th><th>Article</th><th>Stage / Dept</th><th>Reason</th><th>Files</th><th style="width:120px;">E-mail</th></tr>
<?php foreach ($log as $l) { ?>
            <tr><td><?php echo swh(date('d-M-Y h:i A', strtotime($l['created_at']))); ?></td><td><?php echo (isset($l['page']) && $l['page'] === 'send_to_client') ? 'Project Dispatch' : 'Start Work'; ?></td><td><?php echo swh($l['user_name']); ?></td>
                <td><?php echo swh($l['pub_id']); ?></td><td><?php echo swh($l['stage'].' / '.$l['dept']); ?></td>
                <td><?php echo nl2br(swh($l['reason'])); ?></td>
                <td><?php $fl = !empty($l['files']) ? json_decode($l['files'], true) : array(); if (is_array($fl)) { foreach ($fl as $fi => $f) { ?><a class="swf" href="?dl=<?php echo (int)$l['id']; ?>&amp;i=<?php echo (int)$fi; ?>" title="Download"><i class="fa fa-paperclip"></i> <?php echo swh($f['n']); ?></a><br><?php } } ?></td>
                <td class="<?php echo sw_cls($l['mail_status']); ?>"><?php echo swh($l['mail_status']); ?></td></tr>
<?php } ?>
          </table>
<?php } ?>
        </div>
<?php } ?>
      </div>
      <?php echo scripts(); ?>
      <?php echo footer(); ?>
  </body>
</html>
