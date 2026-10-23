<?php
require_once('dbc.php');
require_once('email-functions.php');
require_once __DIR__ . '/includes/path_map.php';   // wf_existing_dir(): J:/ <-> E:/New_Workflow_Files/

// Bypass notification recipients, per validator tool — each tool has its own "To", but CC/BCC
// follow the same concept (QA cc'd, marimuthu bcc'd), minus whichever address is already the "To".
$style_validator_notify = array(
	'peerj' => array(
		'to'  => 'ananthi@transforma.in',
		'cc'  => 'murali@transforma.in',
		'bcc' => 'marimuthu@transforma.in',
	),
	'iap' => array(
		'to'  => 'murali@transforma.in,dhamodaran@transforma.in,manikandan@transforma.in',
		'cc'  => 'ananthi@transforma.in,jeyakumar@transforma.in,kannan@transforma.in',
		'bcc' => 'marimuthu@transforma.in',
	),
);

// Journal Style Validator (WMS) gate — PeerJ/CE and IAP/XML.
// action=run    -> (re)runs the customer's quality-checker exe on the article's XML and shows the result.
// action=bypass -> records a manual override (mandatory reason) for exceptional cases.
// action=check  -> lightweight gate used by the Complete/Submit handler: echoes 1 if the
//                  most recent pass/bypass is not older than the XML file, else an error string.

$a_id = isset($_REQUEST['a_id']) ? intval($_REQUEST['a_id']) : 0;
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'check';

$result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = " . $a_id);
$row = $result ? $result->fetch_assoc() : null;

if (!$row) {
	echo "Article not found.";
	exit;
}

$dept_result = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = " . intval($row['department_id']));
$dept_row = $dept_result ? $dept_result->fetch_assoc() : null;
$dept_name = $dept_row ? $dept_row['dept_name'] : '';

$cust_result = $db->query("SELECT cust_name FROM adm_customer_master WHERE id = " . intval($row['cust_id']));
$cust_row = $cust_result ? $cust_result->fetch_assoc() : null;
$cust_name = $cust_row ? $cust_row['cust_name'] : '';

$journal_result = $db->query("SELECT j_name, j_code FROM adm_journals WHERE j_id = " . intval($row['j_id']));
$journal_row = $journal_result ? $journal_result->fetch_assoc() : null;
$journal_label = $journal_row ? trim($journal_row['j_code'] . ' - ' . $journal_row['j_name']) : '';

// Map customer + department to the validator exe that applies to this article.
// PeerJ's XMLQualityChecker.exe decides pass/fail itself and writes {pub_id}.pass.txt
// on success or {pub_id}.qc.html on failure. IAP's IAPQualityChecker.exe always writes
// a single report (_{pub_id}-validation-report.html, regardless of outcome) and encodes
// the verdict as data-well-formed/data-base-errors/data-base-warnings on <body> — so for
// IAP we parse that report ourselves and write the pass marker PHP-side.
$tools_dir = 'C:\\LAMP\\Apache\\htdocs\\workflow-tools' . DIRECTORY_SEPARATOR;
if ($cust_name === 'PeerJ' && $dept_name === 'CE') {
	$validator_exe = $tools_dir . 'XMLQualityChecker.exe';
	$validator_kind = 'peerj';
} elseif ($cust_name === 'IAP' && $dept_name === 'XML') {
	$validator_exe = $tools_dir . 'IAPQualityChecker.exe';
	$validator_kind = 'iap';
} else {
	echo "Journal Style Validator is not applicable for this article.";
	exit;
}

$folder = wf_existing_dir($row['work_dir'] . $row['volume_folder'] . "/" . $row['pub_id'] . "/" . $row['stage'] . "/" . $dept_name . "/");

if ($validator_kind === 'iap') {
	// IAP's XML on disk isn't reliably named after pub_id at all — it can carry a
	// suffix like {pub_id}_1.xml, {pub_id}_2.xml (revision number), differ in case
	// (pub_id "jnet-..." on disk as "JNET-..."), or not resemble pub_id whatsoever.
	// The department folder itself (.../{pub_id}/{stage}/{dept_name}/) already scopes
	// us to the right article, so: prefer a file whose name starts with pub_id
	// (case-insensitive) if one exists, otherwise just take whatever .xml is in
	// there — picking the most recently modified when there's more than one.
	$all_xml = glob($folder . '*.xml');
	$xml_candidates = array();
	foreach ($all_xml as $candidate) {
		if (stripos(basename($candidate), $row['pub_id']) === 0) {
			$xml_candidates[] = $candidate;
		}
	}
	if (empty($xml_candidates)) {
		$xml_candidates = $all_xml; // fall back to any .xml in the article's own folder
	}
	if (!empty($xml_candidates)) {
		usort($xml_candidates, function ($a, $b) { return filemtime($b) - filemtime($a); });
		$xml_file = $xml_candidates[0];
	} else {
		$xml_file = $folder . $row['pub_id'] . ".xml"; // keep a sensible path for the "not found" message below
	}

	// IAPQualityChecker.exe's report naming has varied across builds: older builds always
	// prefixed with the department folder's ACTUAL on-disk name (which can differ in case
	// from $dept_name, the DB value — same class of mismatch as pub_id vs the real filename),
	// i.e. {parent_folder_name}_{input_filename}-validation-report.html. The build introduced
	// 2026-09-01 only adds that prefix to disambiguate a naming collision — which can't happen
	// here since we always invoke it on exactly one file — so it writes the plain
	// {input_filename}-validation-report.html instead. Rather than hard-code one convention,
	// resolve whichever actually exists on disk (checked in the 'run' action below), so this
	// keeps working across either build.
	$actual_folder_name = $dept_name;
	$stage_dir = wf_existing_dir($row['work_dir'] . $row['volume_folder'] . "/" . $row['pub_id'] . "/" . $row['stage'] . "/");
	if (is_dir($stage_dir)) {
		foreach (scandir($stage_dir) as $entry) {
			if ($entry !== '.' && $entry !== '..' && is_dir($stage_dir . $entry) && strcasecmp($entry, $dept_name) === 0) {
				$actual_folder_name = $entry;
				break;
			}
		}
	}
	$xml_basename = pathinfo($xml_file, PATHINFO_FILENAME);
	$pass_file = $folder . $xml_basename . ".pass.txt";
	$bypass_file = $folder . $xml_basename . ".bypass.txt";
	$qc_file_plain = $folder . $xml_basename . '-validation-report.html';
	$qc_file_prefixed = $folder . $actual_folder_name . '_' . $xml_basename . '-validation-report.html';
	if (file_exists($qc_file_prefixed) && (!file_exists($qc_file_plain) || filemtime($qc_file_prefixed) >= filemtime($qc_file_plain))) {
		$qc_file = $qc_file_prefixed;
	} else {
		$qc_file = $qc_file_plain;
	}
} else {
	$xml_file = $folder . $row['pub_id'] . ".xml";
	$pass_file = $folder . $row['pub_id'] . ".pass.txt";
	$qc_file = $folder . $row['pub_id'] . ".qc.html";
	$bypass_file = $folder . $row['pub_id'] . ".bypass.txt";
}

if ($action == 'run') {
	if (!file_exists($xml_file)) {
		echo '<div class="alert alert-warning"><strong>Journal Style Validator:</strong> XML file not found at ' . htmlspecialchars($xml_file) . '</div>';
		exit;
	}

	if (file_exists($pass_file)) { unlink($pass_file); }
	if (file_exists($qc_file)) { unlink($qc_file); }
	// Clear both possible IAP report names (see naming note above) so a stale report from
	// a previously-deployed build of the exe can't be mistaken for this run's output.
	if ($validator_kind === 'iap') {
		if (file_exists($qc_file_plain)) { unlink($qc_file_plain); }
		if (file_exists($qc_file_prefixed)) { unlink($qc_file_prefixed); }
	}

	if ($validator_kind === 'iap') {
		// IAPQualityChecker.exe shells out to "xsltproc" by bare name for the PMC
		// style check (JATS 1.3 articles) and relies on it being on PATH. Rather than
		// require a system-wide PATH change + web server restart, look for it dropped
		// alongside the other tools and prepend that folder to THIS process's PATH —
		// child processes (the exe, and its own subprocess.run("xsltproc")) inherit it.
		$xsltproc_dir = $tools_dir . 'xsltproc';
		if (is_dir($xsltproc_dir)) {
			putenv('PATH=' . $xsltproc_dir . ';' . getenv('PATH'));
		}
	}

	$cmd = '"' . $validator_exe . '" "' . $xml_file . '" 2>&1';
	$cmd_output = shell_exec($cmd);

	if ($validator_kind === 'iap') {
		// Re-resolve to whichever name this run's build actually produced.
		if (file_exists($qc_file_plain)) {
			$qc_file = $qc_file_plain;
		} elseif (file_exists($qc_file_prefixed)) {
			$qc_file = $qc_file_prefixed;
		}
	}

	if ($validator_kind === 'iap' && file_exists($qc_file)) {
		$report_html = file_get_contents($qc_file);
		$passed = false;
		if (preg_match('/data-well-formed="(\d)"\s+data-base-errors="(\d+)"\s+data-base-warnings="(\d+)"/', $report_html, $m)) {
			$passed = ($m[1] === '1' && (int)$m[2] === 0 && (int)$m[3] === 0);
		}
		if ($passed) {
			file_put_contents($pass_file, date('Y-m-d H:i:s') . "\n");
		}
	}

	if (file_exists($pass_file)) {
		echo '<div class="alert alert-success"><strong>Journal Style Validator:</strong> Passed. No issues found — you can now complete this stage.</div>';
	} elseif (file_exists($qc_file)) {
		$report = file_get_contents($qc_file);
		echo '<div class="alert alert-danger"><strong>Journal Style Validator:</strong> Failed. Please fix the issues below and run it again before completing this stage.</div>' . $report;
	} else {
		echo '<div class="alert alert-warning"><strong>Journal Style Validator:</strong> Did not produce a report. Please contact support.</div>'
			. '<p class="mt-2 mb-1"><strong>Diagnostics</strong></p>'
			. '<pre style="white-space:pre-wrap;background:#f8f9fa;border:1px solid #dee2e6;padding:10px;border-radius:6px;font-size:12px;">'
			. 'Command run:' . "\n" . htmlspecialchars($cmd) . "\n\n"
			. 'Tool output:' . "\n" . htmlspecialchars($cmd_output === null ? '(empty — shell_exec may be disabled or returned nothing)' : $cmd_output) . "\n\n"
			. 'Expected report at: ' . htmlspecialchars($qc_file) . "\n"
			. 'Expected pass marker at: ' . htmlspecialchars($pass_file)
			. '</pre>';
	}
	exit;
}

if ($action == 'bypass') {
	$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
	if ($reason === '') {
		echo '<div class="alert alert-warning"><strong>Journal Style Validator:</strong> A reason is required to bypass validation.</div>';
		exit;
	}

	$bypassed_by = isset($_COOKIE['full_name']) ? $_COOKIE['full_name'] : ('user #' . $_COOKIE['user_id']);
	$bypass_note = date('Y-m-d H:i:s') . ' | Bypassed by: ' . $bypassed_by . ' | Reason: ' . $reason . "\n";

	$folder_created = false;
	if (!is_dir($folder)) {
		$folder_created = @mkdir($folder, 0777, true);   // an exceptional bypass must still work when nothing has been copied into the folder yet
		clearstatcache();
	}
	if (!is_dir($folder)) {
		echo '<div class="alert alert-warning"><strong>Journal Style Validator:</strong> Work folder not found and could not be created, cannot record bypass: ' . htmlspecialchars($folder) . '</div>';
		exit;
	}
	file_put_contents($bypass_file, $bypass_note, FILE_APPEND);

	$escaped_reason = $db->real_escape_string($reason);
	$escaped_by = $db->real_escape_string($bypassed_by);
	$article_transactions_sql = "INSERT INTO article_transactions(article_id, action, department, status, assigned_to, notes)
		VALUES ('" . intval($a_id) . "', 'Journal Style Validator bypassed by " . $escaped_by . "', '" . $dept_name . "', 'Bypassed', '', '" . $escaped_reason . "')";
	$db->query($article_transactions_sql);

	// Email the bypass remarks + report + XML to this tool's notify list, best-effort.
	$email_settings = $db->query("SELECT * FROM email_settings WHERE id = 2");
	$email_settings = $email_settings ? $email_settings->fetch_assoc() : null;
	$notify = isset($style_validator_notify[$validator_kind]) ? $style_validator_notify[$validator_kind] : null;
	if ($email_settings && $notify) {
		$had_failing_report = file_exists($qc_file);
		$validator_state = $had_failing_report
			? 'The validator had reported errors/warnings on this article, and the user chose to override that result instead of fixing them.'
			: 'The validator had not been run (or produced no report) for this article, and the user chose to skip it entirely.';

		$subject = 'ACTION BYPASSED: Journal Style Validator skipped for ' . $row['pub_id'] . ' (' . $cust_name . '/' . $dept_name . ')';
		$body = '<p>Hi Team,</p>'
			. '<p>This is to inform you that the <strong>Journal Style Validator</strong> check was <strong>bypassed</strong> '
			. 'for the article below, meaning the user completed this stage <u>without a passing validation report</u>. '
			. $validator_state . '</p>'
			. '<table cellpadding="4" cellspacing="0" border="0">'
			. '<tr><td><strong>Article ID</strong></td><td>' . htmlspecialchars($a_id) . '</td></tr>'
			. '<tr><td><strong>Pub ID</strong></td><td>' . htmlspecialchars($row['pub_id']) . '</td></tr>'
			. '<tr><td><strong>Customer</strong></td><td>' . htmlspecialchars($cust_name) . '</td></tr>'
			. ($journal_label ? '<tr><td><strong>Journal</strong></td><td>' . htmlspecialchars($journal_label) . '</td></tr>' : '')
			. '<tr><td><strong>Department</strong></td><td>' . htmlspecialchars($dept_name) . '</td></tr>'
			. '<tr><td><strong>Stage</strong></td><td>' . htmlspecialchars($row['stage']) . '</td></tr>'
			. '<tr><td><strong>Bypassed By</strong></td><td>' . htmlspecialchars($bypassed_by) . '</td></tr>'
			. '<tr><td><strong>Bypassed On</strong></td><td>' . date('d-M-Y h:i A') . '</td></tr>'
			. '<tr><td style="vertical-align:top;"><strong>Reason Given</strong></td><td>' . nl2br(htmlspecialchars($reason)) . '</td></tr>'
			. '</table>'
			. '<p>' . ($had_failing_report ? 'The validator\'s failure report and the article\'s current XML are attached for review.' : 'The article\'s current XML is attached for reference.') . '</p>'
			. '<p>Please review and follow up with the team if this bypass needs to be corrected before the article proceeds further.</p>'
			. '<p style="color:#888;font-size:12px;">This is a system-generated notification from the Workflow application. Please do not reply directly to this email.</p>';

		$attachments = array();
		if (file_exists($qc_file)) { $attachments[] = $qc_file; }
		if (file_exists($xml_file)) { $attachments[] = $xml_file; }

		sendMail(
			$email_settings['host'],
			$email_settings['port'],
			$email_settings['secure'],
			$email_settings['from_email'],
			$email_settings['from_password'],
			$email_settings['from_email'],
			$email_settings['from_name'],
			$notify['to'],
			$notify['cc'],
			$notify['bcc'],
			$subject,
			$body,
			$attachments
		);
	}

	echo '<div class="alert alert-warning"><strong>Journal Style Validator:</strong> Bypassed by ' . htmlspecialchars($bypassed_by) . '. This has been recorded in the article history and reported by email — you can now complete this stage.' . ($folder_created ? ' <em>(The work folder did not exist yet, so it was created.)</em>' : '') . '</div>';
	exit;
}

if ($action == 'check') {
	$xml_mtime = file_exists($xml_file) ? filemtime($xml_file) : 0;
	if (file_exists($pass_file) && filemtime($pass_file) >= $xml_mtime) {
		echo 1;
	} elseif (file_exists($bypass_file) && filemtime($bypass_file) >= $xml_mtime) {
		echo 1;
	} elseif (file_exists($qc_file) && (!file_exists($pass_file) || filemtime($qc_file) >= filemtime($pass_file))) {
		echo "Journal Style Validator has not passed for this article. Please click \"Style Validator\" and fix all issues, or use \"Bypass Validation\" for exceptional cases, before completing this stage.";
	} else {
		echo "Journal Style Validator has not been run for this article yet. Please click \"Style Validator\" and get a passing report, or use \"Bypass Validation\" for exceptional cases, before completing this stage.";
	}
	exit;
}

echo "Invalid request.";
