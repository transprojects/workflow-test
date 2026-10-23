<?php
include ('includes/includes.php'); include_once 'includes/issue_drafts.php';

/*
 * Re-book selected articles of an issue for client corrections (DRAFT1, DRAFT2 ...).
 * The articles stay in their ISSCOR stage and files stay in the same ISSCOR folders;
 * the draft round is recorded only in inw_issue_article_drafts (see migration_issue_drafts.sql).
 */

if (!(isset($_COOKIE['department_id']) && $_COOKIE['department_id'] == 16) && !(isset($_COOKIE['user_level']) && $_COOKIE['user_level'] == 1))
{
	idr_fail('manage_issues.php', '', 'You are not allowed to open the draft correction page.');
}

$vi_id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
if ($vi_id <= 0) { idr_fail('manage_issues.php', '', 'Invalid issue.'); }

/* create the table on first use */
$chk = $db->query("SHOW TABLES LIKE 'inw_issue_article_drafts'");
if ($chk && $chk->num_rows == 0)
{
	$db->query("CREATE TABLE IF NOT EXISTS `inw_issue_article_drafts` (
	  `draft_id` int(11) NOT NULL AUTO_INCREMENT,
	  `vi_id` int(11) NOT NULL,
	  `article_id` bigint(20) NOT NULL,
	  `draft_no` smallint(6) NOT NULL,
	  `sub_stage` varchar(10) NOT NULL,
	  `stage` varchar(15) DEFAULT NULL,
	  `received_date` date DEFAULT NULL,
	  `due_date` date DEFAULT NULL,
	  `correction_pages` int(11) NOT NULL DEFAULT 0,
	  `wf_id` int(11) DEFAULT NULL,
	  `comments` text,
	  `attachment` varchar(255) DEFAULT NULL,
	  `created_by` varchar(50) DEFAULT NULL,
	  `created_on` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	  PRIMARY KEY (`draft_id`),
	  UNIQUE KEY `uq_article_draft` (`article_id`,`draft_no`),
	  KEY `idx_vi` (`vi_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8") or die($db->error);
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8', false); }

/// Issue, customer, journal
$result = $db->query("SELECT * FROM inw_volume_issues WHERE vi_id = ".$vi_id);
if (!$result || $result->num_rows == 0) { idr_fail('manage_issues.php', '', 'Issue not found.'); }
$app_res = $result->fetch_assoc();
$app_res['volume_folder'] = VOLUME_PREFIX.$app_res['volume_number'].$app_res['issue_number'];

$customer_info = $db->query("SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = ".(int)$app_res['cust_id'])->fetch_assoc();
$journal_info  = $db->query("SELECT `j_id`,`j_name`,j_code FROM `adm_journals` WHERE j_id = ".(int)$app_res['j_id'])->fetch_assoc();

$error = '';

/// Save
if (isset($_POST['action']) && $_POST['action'] == 'insert')
{
	$ids = array();
	if (isset($_POST['article_ids']) && is_array($_POST['article_ids']))
	{
		foreach ($_POST['article_ids'] as $a) { $ids[(int)$a] = (int)$a; }
	}
	$recv_dt = filter($_POST['recv_dt']);
	$due_dt  = filter($_POST['due_dt']);
	$pages   = (int)$_POST['correction_pages'];
	$wf_id   = (int)$_POST['wf_id'];
	$instructions = filter($_POST['instructions']);

	if (count($ids) < 1)                          { $error = 'You must select at least one article.'; }
	elseif ($recv_dt == '' || $due_dt == '')      { $error = 'Received date and due date are required.'; }
	elseif ($wf_id <= 0)                          { $error = 'Please choose a workflow.'; }

	if ($error == '')
	{
		$recv_dt = convert_mdy_ymd($recv_dt);
		$due_dt  = convert_mdy_ymd($due_dt);

		/// Workflow steps and first department
		$deptinfo = $db->query("SELECT `wf_id`,`wf_code`,wf_name FROM `adm_workflow_master` WHERE wf_id = ".$wf_id)->fetch_assoc();
		$dept_info = explode(',', $deptinfo['wf_code']);
		$dept_res = $db->query("SELECT dept_name FROM adm_dept_master WHERE dept_code = ".(int)$dept_info[0])->fetch_assoc();
		if (!$dept_res) { $error = 'Workflow department not found.'; }
	}

	if ($error == '')
	{
		/// Only articles of this issue that are with the client can be re-booked
		$res = $db->query("SELECT * FROM `inw_inward_dtl` WHERE `v_issue_id` = ".$vi_id." AND status IN ('Delivery','Client Review') AND id IN (".implode(',', $ids).")");
		$articles = array();
		while ($r = $res->fetch_assoc()) { $articles[] = $r; }
		if (count($articles) != count($ids)) { $error = 'Some selected articles are not with the client (status Delivery or Client Review).'; }
	}

	if ($error == '')
	{
		/// Optional attachment -> same ISSCOR misc folder style as the revise page, under the issue
		$attachment = '';
		$batch = 'draft_'.date('Ymd_His');   // every booking gets its own dated folder, so earlier files in misc are never overwritten
		if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0)
		{
			$stage_dir = $articles[0]['stage'];
			$misc_dir = WF_DIR."/downloads/JOURNALS/".$customer_info['cust_name']."/".$journal_info['j_code']."/".$app_res['volume_folder']."/".$stage_dir."/misc/".$batch."/";
			if (!is_dir($misc_dir)) { mkdir($misc_dir, 0777, true); }
			$fname = basename($_FILES['attachment']['name']);
			if (strtolower(substr($fname, -4)) == '.zip')
			{
				$zip = new ZipArchive;
				if ($zip->open($_FILES['attachment']['tmp_name']) === TRUE) { $zip->extractTo($misc_dir); $zip->close(); }
			}
			else
			{
				move_uploaded_file($_FILES['attachment']['tmp_name'], $misc_dir.$fname);
			}
			$attachment = $batch.'/'.$fname;
		}

		$user = $db->real_escape_string($_COOKIE['user_id']);
		$ok = true;
		$db->begin_transaction();
		foreach ($articles as $a)
		{
			$r = $db->query("SELECT COALESCE(MAX(draft_no),0) AS m FROM inw_issue_article_drafts WHERE article_id = ".(int)$a['id'])->fetch_assoc();
			$draft_no = (int)$r['m'] + 1;

			$ok = $ok && $db->query("INSERT INTO inw_issue_article_drafts (vi_id,article_id,draft_no,sub_stage,stage,received_date,due_date,correction_pages,wf_id,comments,attachment,created_by,created_on)
				VALUES (".$vi_id.",".(int)$a['id'].",".$draft_no.",'DRAFT".$draft_no."','".$db->real_escape_string($a['stage'])."','".$recv_dt."','".$due_dt."',".$pages.",".$wf_id.",'".$db->real_escape_string($instructions)."','".$db->real_escape_string($attachment)."','".$user."',NOW())");

			/// Same stage, same folder: only the workflow position changes (files are overridden by the workflow as usual)
			$stage_dir = $app_res['volume_folder'];
			$work_dir = WF_DIR."/production/JOURNALS/".$customer_info['cust_name']."/".$journal_info['j_code']."/".$stage_dir."/".$a['pub_id']."/".$a['stage']."/".$dept_res['dept_name'];
			if (!is_dir($work_dir)) { mkdir($work_dir, 0777, true); }

			$ok = $ok && $db->query("UPDATE inw_inward_dtl SET `prev_stage` ='".$db->real_escape_string($a['stage'])."',`prev_workflow` ='".(int)$a['wf_id']."',`prev_dept_id` ='".(int)$a['department_id']."',`prev_status` ='".$db->real_escape_string($a['status'])."',wf_id = ".$wf_id.", `status` ='".$db->real_escape_string($dept_res['dept_name'])."',`department_id` =".(int)$dept_info[0]." WHERE id = ".(int)$a['id']);
		}
		/// Issue goes back to production (same as the revise page)
		$ok = $ok && $db->query("UPDATE inw_volume_issues SET `status` ='' WHERE vi_id = ".$vi_id);

		if ($ok) { $db->commit(); header("location:show_issues.php?id=".$vi_id."&msg=".urlencode("Draft correction booked for ".count($articles)." article(s)")); die(); }
		$db->rollback();
		$error = 'Save failed: '.$db->error;
	}
}

/// Articles of the issue with their latest draft
$ARTICLES = array();
$res = $db->query("SELECT d.id, d.pub_id, d.job_id, d.article_title, d.stage, d.status,
		(SELECT MAX(draft_no) FROM inw_issue_article_drafts x WHERE x.article_id = d.id) AS last_draft
	FROM inw_inward_dtl d WHERE d.v_issue_id = ".$vi_id." ORDER BY d.pub_id ASC");
while ($r = $res->fetch_assoc()) { $ARTICLES[] = $r; }

/// Workflow list (same source as the revise page)
$jrow = $db->query("SELECT j_issue_wf FROM `adm_journals` WHERE j_id = ".(int)$app_res['j_id'])->fetch_assoc();
$wfs = $db->query("SELECT `wf_id`,`wf_code`,wf_name FROM `adm_workflow_master` WHERE wf_name IN ('".$db->real_escape_string($jrow['j_issue_wf'])."') ORDER BY `wf_code` ASC");

/// Draft history of this issue
$DEPTS = array();
$r = $db->query("SELECT dept_code, dept_name FROM adm_dept_master");
while ($d = $r->fetch_assoc()) { $DEPTS[(int)$d['dept_code']] = $d['dept_name']; }

$HIST = array();
$res = $db->query("SELECT dr.*, d.pub_id, u.full_name, u.user_name
	FROM inw_issue_article_drafts dr
	LEFT JOIN inw_inward_dtl d ON d.id = dr.article_id
	LEFT JOIN users u ON u.id = dr.created_by
	WHERE dr.vi_id = ".$vi_id." ORDER BY d.pub_id ASC, dr.draft_no ASC");
while ($r = $res->fetch_assoc()) { $HIST[] = $r; }

/* work done on the article during a draft round: from this round's booking to the next round's booking */
function draft_steps($db, $row, $next_on)
{
	$to = $next_on ? " AND t.created_dt < '".$db->real_escape_string($next_on)."'" : '';
	$q = $db->query("SELECT t.stage, t.dept, t.created_dt, u.full_name, u.user_name, dm.dept_name
		FROM inw_transactions t
		LEFT JOIN users u ON u.id = t.process_user
		LEFT JOIN adm_dept_master dm ON dm.dept_code = t.dept
		WHERE t.project_id = ".(int)$row['article_id']." AND t.created_dt >= '".$db->real_escape_string($row['created_on'])."'".$to."
		ORDER BY t.created_dt ASC, t.id ASC");
	$out = array();
	while ($q && ($t = $q->fetch_assoc())) { $out[] = $t; }
	return $out;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<?php include('includes/title.php'); ?>
<?php echo styles(); ?>
<?php include_once 'includes/modern-ui.php'; include_once 'includes/article-filter-ui.php'; include_once 'includes/issue_drafts.php'; mu_head(); af_head(); idr_head(); ?>
<style>
  .dc-info{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px;}
  .dc-info div{padding:8px 12px;border:1px solid var(--line,#e4e9ef);border-radius:10px;background:#f9fbfd;}
  .dc-info small{display:block;font-size:10.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#6b7785;}
  .dc-info b{color:#14304a;font-size:14px;}
  .dc-h{display:flex;align-items:center;gap:8px;margin:0 0 4px;font-size:13px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#14304a;}
  .dc-h i{color:#e8742a;}
  .dc-note{color:#6b7785;margin:0 0 12px;}
  .dc-tbl{width:100%;border-collapse:separate;border-spacing:0 6px;}
  .dc-tbl th{padding:6px 12px 2px;font-size:10.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#7b8a9a;text-align:left;white-space:nowrap;border:0;background:transparent;}
  .dc-tbl td{padding:10px 12px;background:#fff;border-top:1px solid #e4e9ef;border-bottom:1px solid #e4e9ef;vertical-align:top;}
  .dc-tbl td:first-child{border-left:1px solid #e4e9ef;border-radius:10px 0 0 10px;}
  .dc-tbl td:last-child{border-right:1px solid #e4e9ef;border-radius:0 10px 10px 0;}
  .dc-tbl tr.dc-off td{background:#f6f8fa;color:#98a4b0;}
  .dc-tbl tr.dc-sel td{background:#eef5fb;}
  .dc-form{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px 18px;}
  .dc-form .af-field{min-width:0;}
  .dc-form .af-field.wide{grid-column:1/-1;}
  .dc-form textarea{border:1px solid #d5dde6;border-radius:9px;padding:10px 12px;min-height:90px;width:100%;}
  .dc-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:16px;}
  .dc-sel-count{display:inline-block;min-width:22px;padding:1px 8px;border-radius:20px;background:#e8742a;color:#fff;font-weight:700;text-align:center;}
  .badge-orig{display:inline-block;padding:2px 9px;border-radius:20px;background:#eef1f5;color:#6b7785;font-size:11.5px;font-weight:700;}
  .steps{font-size:12px;color:#6b7785 !important;margin:0;padding-left:16px;}
</style>
</head>
<body>
<div class="wholeouter">
<?php include("includes/top-header.php"); ?>

<div class="container-fluid" style="justify-content: space-between;">
  <div class="af-page" style="padding-bottom:0;">
    <div class="af-hero">
      <div><small><?php echo h($customer_info['cust_name']); ?> &middot; <?php echo h($journal_info['j_code']); ?> &middot; <?php echo h($app_res['volume_folder']); ?></small><h1>Draft Correction</h1></div>
      <a class="af-add" href="clientreview_issues.php" style="background:rgba(255,255,255,.16);box-shadow:none;border:1px solid rgba(255,255,255,.35);"><i class="fa fa-arrow-left"></i> Back to Under Client Issues</a>
    </div>
  </div>
</div>

<div class="af-page"><div class="af-wrap"><div class="formouter">
  <div class="dc-info">
    <div><small>Customer</small><b><?php echo h($customer_info['cust_name']); ?></b></div>
    <div><small>Journal</small><b><?php echo h($journal_info['j_name']); ?></b></div>
    <div><small>Volume</small><b><?php echo h($app_res['volume_folder']); ?></b></div>
    <div><small>Received</small><b><?php echo date('d-m-Y', strtotime($app_res['received_date'])); ?></b></div>
    <div><small>Due</small><b><?php echo date('d-m-Y', strtotime($app_res['due_date'])); ?></b></div>
  </div>
</div></div></div>

<form id="validation-form" method="post" action="" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?php echo $vi_id; ?>" />
<input type="hidden" name="action" value="insert" />

<div class="af-page"><div class="af-card">
  <h3 class="dc-h"><i class="fa fa-check-square-o"></i> Articles the client wants corrected</h3>
  <p class="dc-note">Articles stay in their current stage and folder. The round (DRAFT1, DRAFT2 ...) is only recorded against each article. Only articles that are with the client can be selected.</p>
    <div class="table-responsive">
  <table class="dc-tbl">
    <thead><tr>
      <th style="width:36px"><input type="checkbox" id="chk_all" title="Select all"></th>
      <th>Article</th><th>Job ID</th><th>Title</th><th>Stage</th><th>Current Round</th><th>Next Round</th>
    </tr></thead>
    <tbody>
    <?php foreach ($ARTICLES as $a) {
		$with_client = in_array($a['status'], array('Delivery', 'Client Review'));
		$next = (int)$a['last_draft'] + 1; ?>
      <tr class="<?php echo $with_client ? '' : 'dc-off'; ?>">
        <td><?php if ($with_client) { ?><input type="checkbox" class="chk_art" name="article_ids[]" value="<?php echo (int)$a['id']; ?>" <?php echo (isset($_POST['article_ids']) && in_array($a['id'], $_POST['article_ids'])) ? 'checked' : ''; ?>><?php } ?></td>
        <td><span class="af-art"><?php echo h($a['pub_id']); ?></span></td>
        <td><?php echo h($a['job_id']); ?></td>
        <td><?php echo h($a['article_title']); ?></td>
        <td><span class="af-stage" style="--c:#6f42c1"><?php echo h($a['stage']); ?></span></td>
        <td><?php echo $a['last_draft'] ? '<span class="idr-pill">DRAFT'.(int)$a['last_draft'].'</span>' : '<span class="badge-orig">Original</span>'; ?>
            <?php if (!$with_client) { echo ' <span class="af-sub">'.h($a['status']).'</span>'; } ?></td>
        <td><?php echo $with_client ? '<b>DRAFT'.$next.'</b>' : '&ndash;'; ?></td>
      </tr>
    <?php } ?>
    </tbody>
  </table>
  </div>
  <p style="margin:6px 0 0;">Selected: <span class="dc-sel-count" id="sel_count">0</span></p>
</div></div>

<div class="af-page"><div class="af-card">
  <h3 class="dc-h"><i class="fa fa-pencil-square-o"></i> Correction details</h3>
  <div class="dc-form af-wrap" style="margin-top:12px;">
    <div class="af-field dvr-field">
      <label>Received Date &rarr; Due Date</label>
      <div class="dvr" data-placeholder="Select received and due dates" data-from-name="recv_dt" data-to-name="due_dt" data-from="<?php echo isset($_POST['recv_dt']) ? h($_POST['recv_dt']) : ''; ?>" data-to="<?php echo isset($_POST['due_dt']) ? h($_POST['due_dt']) : ''; ?>"></div>
    </div>
    <link rel="stylesheet" href="dv-rangepicker.css?v=1"><script src="dv-rangepicker.js?v=1"></script>
    <div class="af-field">
      <label>Correction Pages</label>
      <input type="number" min="0" id="correction_pages" name="correction_pages" class="form-control" value="<?php echo isset($_POST['correction_pages']) ? (int)$_POST['correction_pages'] : ''; ?>" />
    </div>
    <div class="af-field">
      <label>Workflow</label>
      <select id="wf_id" name="wf_id" class="form-select">
        <option value="">-Select-</option>
        <?php while ($row = $wfs->fetch_assoc()) { ?>
        <option value="<?php echo (int)$row['wf_id']; ?>" <?php echo (isset($_POST['wf_id']) && $_POST['wf_id'] == $row['wf_id']) ? 'selected' : ''; ?>><?php echo h($row['wf_name']); ?></option>
        <?php } ?>
      </select>
    </div>
    <div class="af-field wide">
      <label>Instructions</label>
      <textarea name="instructions" rows="4"><?php echo isset($_POST['instructions']) ? h($_POST['instructions']) : ''; ?></textarea>
    </div>
    <div class="af-field wide">
      <label>Attachment</label>
      <input class="form-control" type="file" name="attachment" accept=".doc,.docx,.pdf,.zip" />
    </div>
  </div>
  <div class="dc-actions">
    <a class="af-btn" href="clientreview_issues.php">Cancel</a>
    <button type="submit" class="af-btn primary"><i class="fa fa-check"></i> Save Draft Correction</button>
  </div>
</div></div>
</form>

<div class="af-page"><div class="af-card">
  <h3 class="dc-h"><i class="fa fa-history"></i> Draft History of this Issue</h3>
  <?php if (count($HIST) == 0) { ?>
    <p class="dc-note" style="margin:8px 0 0;">No draft rounds have been booked for this issue yet.</p>
  <?php } else {
	  /* next round booking time per article, to bound the work done in each round */
	  $next_on = array();
	  for ($i = 0; $i < count($HIST); $i++)
	  {
		  $next_on[$i] = (isset($HIST[$i+1]) && $HIST[$i+1]['article_id'] == $HIST[$i]['article_id']) ? $HIST[$i+1]['created_on'] : null;
	  } ?>
  <div class="table-responsive">
  <table class="dc-tbl">
    <thead><tr><th>Article</th><th>Round</th><th>Stage</th><th>Received</th><th>Due</th><th>Pages</th><th>Booked By</th><th>Booked On</th><th>Instructions</th><th>Work Done</th></tr></thead>
    <tbody>
    <?php foreach ($HIST as $i => $d) { $steps = draft_steps($db, $d, $next_on[$i]); ?>
      <tr>
        <td><span class="af-art"><?php echo h($d['pub_id']); ?></span></td>
        <td><span class="idr-pill"><?php echo h($d['sub_stage']); ?></span></td>
        <td><span class="af-stage" style="--c:#6f42c1"><?php echo h($d['stage']); ?></span></td>
        <td><?php echo $d['received_date'] ? date('d-m-Y', strtotime($d['received_date'])) : ''; ?></td>
        <td><?php echo $d['due_date'] ? date('d-m-Y', strtotime($d['due_date'])) : ''; ?></td>
        <td><?php echo (int)$d['correction_pages']; ?></td>
        <td><?php echo h($d['full_name'] ? $d['full_name'] : $d['user_name']); ?></td>
        <td><?php echo date('d-m-Y H:i', strtotime($d['created_on'])); ?></td>
        <td><?php echo h($d['comments']); ?><?php if ($d['attachment']) { echo '<br><small>Attachment: '.h($d['attachment']).'</small>'; } ?></td>
        <td><?php if (count($steps) == 0) { echo '&ndash;'; } else { echo '<ul class="steps">'; foreach ($steps as $t) {
			echo '<li>'.h($t['dept_name'] ? $t['dept_name'] : $t['dept']).' - '.h($t['full_name'] ? $t['full_name'] : $t['user_name']).' ('.date('d-m-Y H:i', strtotime($t['created_dt'])).')</li>'; }
			echo '</ul>'; } ?></td>
      </tr>
    <?php } ?>
    </tbody>
  </table>
  </div>
  <?php } ?>
</div></div>

<?php echo scripts(); ?>
<?php idr_error_script(); idr_error_popup('Cannot book the draft correction'); ?>
<?php if ($error != '') { idr_error_script(); ?><script type="text/javascript">$(function () { idrError(<?php echo json_encode($error); ?>, 'Cannot book the draft correction'); });</script><?php } ?>
<?php echo footer(); ?>
</div>
<script type="text/javascript">
$(function () {
	function recount() { $('#sel_count').text($('.chk_art:checked').length); $('.chk_art').each(function () { $(this).closest('tr').toggleClass('dc-sel', this.checked); }); }
	$('#chk_all').on('change', function () { $('.chk_art').prop('checked', this.checked); recount(); });
	$('.chk_art').on('change', recount);
	recount();
	$('#validation-form').on('submit', function (e) {
		if ($('.chk_art:checked').length < 1) { idrError('Please tick at least one article first.', 'No article selected'); e.preventDefault(); return; }
		if ($('input[name=recv_dt]').val() == '' || $('input[name=due_dt]').val() == '' || $('#wf_id').val() == '') { idrError('Please choose the received / due dates and a workflow.', 'Details required'); e.preventDefault(); }
	});
});
</script>
</body>
</html>
