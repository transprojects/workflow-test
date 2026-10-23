<?php
/* ============================================================================
 * Issue draft rounds (DRAFT1, DRAFT2 ...) - shared bits for the issue pages.
 * Data comes from inw_issue_article_drafts (see sql/migration_issue_drafts.sql).
 *
 *   idr_head()                          - styles, echo in <head> after af_head()
 *   idr_issue_map($runner)              - per issue: round => [articles, still in production]
 *   idr_issue_cell($map, $vi_id)        - HTML for the "Draft Status" column of an issue row
 *   idr_article_map($runner)            - article id => latest round (number + label)
 *   idr_article_badge($map, $article_id)
 *
 * $runner is any callable that takes an SQL string and returns an array of
 * associative rows, so the same helper works with mysqli ($db) and DBController.
 * Everything returns empty / "-" while the drafts table does not exist yet.
 * ========================================================================== */

function idr_head()
{
?>
<style>
.idr-pills{display:inline-flex;flex-wrap:wrap;gap:5px;align-items:center;}
.idr-pill{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:20px;background:#eaf2fa;border:1px solid #c5daef;color:#1b6ca8;font-size:11.5px;font-weight:700;white-space:nowrap;}
.idr-pill b{color:#14304a;font-weight:700;}
.idr-pill small{font-weight:600;color:#b87800;background:#fdf1d9;border-radius:20px;padding:0 7px;font-size:10.5px;}
.idr-none{color:#b3bcc7;}
.idr-badge{display:inline-block;margin-left:6px;padding:1px 8px;border-radius:20px;background:#1b6ca8;color:#fff;font-size:10.5px;font-weight:700;letter-spacing:.3px;vertical-align:middle;}
/* the kit tints success / warning / info / pink pills; the blue primary pill is added here */
.af-card td .btn.btn-xs.btn-primary{--b:#1b6ca8;--bg:#e8f1fb;--bd:#c5daef;display:inline-flex !important;align-items:center;gap:6px;width:auto !important;margin:0 4px 0 0;padding:6px 14px !important;border-radius:20px !important;border:1px solid var(--bd) !important;background:var(--bg) !important;color:var(--b) !important;font-size:12px !important;font-weight:700;line-height:1.2;text-decoration:none !important;white-space:nowrap;box-shadow:none !important;transition:background .2s,color .2s,border-color .2s,transform .15s,box-shadow .2s;}
/* purple pill (Upload) - the kit's btn-purple uses the blue default, so it is set here; keep the action cell on one line */
.af-card td .btn.btn-xs.btn-purple{--b:#6f42c1;--bg:#f0eafb;--bd:#d9cdf1;}
.af-card td:last-child{white-space:nowrap;vertical-align:middle;}
/* old Bootstrap .row / .col wrappers left around the cards have -12px side margins: they made the page 12px wider than the window (sideways scrollbar) */
.wholeouter>.row{margin-left:0;margin-right:0;}
.wholeouter>.row>[class*=col-]{padding-left:0;padding-right:0;}
/* DataTables' Bootstrap .row has -12px side margins; inside the card it made the wrapper wider than the card and showed a stray scrollbar */
.af-card .dataTables_wrapper>.row{margin-left:0;margin-right:0;}
/* compact tables (many columns): tighter cell padding so the action column stays in view */
.af-card table.idr-compact.dataTable>tbody>tr>td,.af-card table.idr-compact.dataTable>thead>tr>th{padding-left:6px !important;padding-right:6px !important;}
.af-card table.idr-compact .af-person{gap:6px;}
.af-card table.idr-compact .af-av{width:24px;height:24px;font-size:9.5px;}
.af-card table.idr-compact .btn.btn-xs{padding:6px 11px !important;}
.af-card td .action-buttons{flex-wrap:nowrap !important;gap:6px !important;}
.af-card td .action-buttons .btn{margin:0 !important;}
.af-card td .btn.btn-xs.btn-primary:hover{background:var(--b) !important;border-color:var(--b) !important;color:#fff !important;transform:translateY(-1px);box-shadow:0 6px 14px rgba(27,108,168,.35) !important;}
</style>
<?php
}

function idr_table_exists($runner)
{
	static $ok = null;
	if ($ok === null) {
		$r = $runner("SHOW TABLES LIKE 'inw_issue_article_drafts'");
		$ok = is_array($r) && count($r) > 0;
	}
	return $ok;
}

/* true once the dispatch columns of the drafts table exist (migration_issue_drafts.sql) */
function idr_has_sent_cols($runner)
{
	static $ok = null;
	if ($ok === null) {
		$r = $runner("SHOW COLUMNS FROM inw_issue_article_drafts LIKE 'dispatched_on'");
		$ok = idr_table_exists($runner) && is_array($r) && count($r) > 0;
	}
	return $ok;
}

/* Called when articles of an issue go back to the client: stamps the open draft round (no dispatch date yet) of each article.
   $delivery_only = only articles whose status is Delivery (finished, waiting to be sent). Normal issues have no draft rows, so nothing happens. */
function idr_stamp_dispatch($db, $vi_id, $delivery_only)
{
	$runner = idr_runner_mysqli($db);
	if (!idr_has_sent_cols($runner)) { return; }
	$user = $db->real_escape_string(isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : '');
	$db->query("UPDATE inw_issue_article_drafts x
		JOIN (SELECT article_id, MAX(draft_no) AS m FROM inw_issue_article_drafts GROUP BY article_id) l ON l.article_id = x.article_id AND l.m = x.draft_no
		JOIN inw_inward_dtl d ON d.id = x.article_id
		SET x.dispatched_on = NOW(), x.dispatched_by = '".$user."'
		WHERE x.vi_id = ".(int)$vi_id." AND x.dispatched_on IS NULL".($delivery_only ? " AND d.status = 'Delivery'" : ""));
}

/* [vi_id => [ 'DRAFT1' => array('n' => articles booked in this round, 'open' => of those, still in production in this round, 'sent' => last dispatch date or '') ]] */
function idr_issue_map($runner)
{
	$map = array();
	if (!idr_table_exists($runner)) { return $map; }
	$sent = idr_has_sent_cols($runner) ? 'MAX(x.dispatched_on)' : 'NULL';
	$rows = $runner("SELECT x.vi_id, x.sub_stage, COUNT(*) AS n, SUM(l.m = x.draft_no AND d.status NOT IN ('Delivery','Client Review')) AS open_n, ".$sent." AS sent_on
		FROM inw_issue_article_drafts x
		JOIN (SELECT article_id, MAX(draft_no) AS m FROM inw_issue_article_drafts GROUP BY article_id) l ON l.article_id = x.article_id
		JOIN inw_inward_dtl d ON d.id = x.article_id
		GROUP BY x.vi_id, x.sub_stage ORDER BY x.vi_id, x.draft_no");
	if (is_array($rows)) {
		foreach ($rows as $r) { $map[$r['vi_id']][$r['sub_stage']] = array('n' => (int)$r['n'], 'open' => (int)$r['open_n'], 'sent' => $r['sent_on'] ? date('d-m-Y', strtotime($r['sent_on'])) : ''); }
	}
	return $map;
}

function idr_issue_cell($map, $vi_id)
{
	if (empty($map[$vi_id])) { return '<span class="idr-none">&ndash;</span>'; }
	$h = '<span class="idr-pills">';
	foreach ($map[$vi_id] as $round => $c) {
		$h .= '<span class="idr-pill" title="'.$c['n'].' article(s) in '.htmlspecialchars($round).($c['open'] ? ', '.$c['open'].' still in production' : ', all back with the client').'">'
		   .  htmlspecialchars($round).' <b>&times;'.$c['n'].'</b>'.($c['open'] ? ' <small>'.$c['open'].' open</small>' : ($c['sent'] ? ' <small>sent '.$c['sent'].'</small>' : '')).'</span>';
	}
	return $h.'</span>';
}

/* [vi_id => number of articles with the client (Delivery or Client Review)] for issues that ALREADY have a draft round.
   Decides whether the extra "Draft" button is offered on Manage Issues / the issue Start page: an issue that has never been through a
   draft correction is booked from Under Client Issues (where it really is with the client); a plain issue in production whose finished
   articles are only waiting for upload must not get the button. */
function idr_withclient_map($runner)
{
	$map = array();
	if (!idr_table_exists($runner)) { return $map; }
	$rows = $runner("SELECT v_issue_id, COUNT(id) AS n FROM inw_inward_dtl WHERE status IN ('Delivery','Client Review') AND v_issue_id IN (SELECT DISTINCT vi_id FROM inw_issue_article_drafts) GROUP BY v_issue_id");
	if (is_array($rows)) { foreach ($rows as $r) { $map[$r['v_issue_id']] = (int)$r['n']; } }
	return $map;
}

/* [article id => 'DRAFT2'] latest round per article */
function idr_article_map($runner)
{
	$map = array();
	if (!idr_table_exists($runner)) { return $map; }
	$rows = $runner("SELECT x.article_id, x.sub_stage FROM inw_issue_article_drafts x
		JOIN (SELECT article_id, MAX(draft_no) AS m FROM inw_issue_article_drafts GROUP BY article_id) l ON l.article_id = x.article_id AND l.m = x.draft_no");
	if (is_array($rows)) { foreach ($rows as $r) { $map[$r['article_id']] = $r['sub_stage']; } }
	return $map;
}

function idr_article_badge($map, $article_id)
{
	return isset($map[$article_id]) ? '<span class="idr-badge" title="Latest client correction round">'.htmlspecialchars($map[$article_id]).'</span>' : '';
}

/* ---------------------------------------------------------------------------
 * Error pop-ups (same Bootbox card look as the other pages; needs af_head() for the styling and jQuery + Bootbox loaded)
 *
 *   idr_error_script()   - echo AFTER scripts(): defines idrError(message, title) for client-side validation
 *   idr_error_popup()    - echo AFTER idr_error_script(): shows the pop-up when the address has ?ermsg=...
 *   idr_fail($page, $query, $msg) - server side: go back to $page?$query&ermsg=$msg instead of printing a bare die() message
 * ------------------------------------------------------------------------- */
function idr_error_script()
{
	static $done = false;
	if ($done) { return; }
	$done = true;
?>
<script type="text/javascript">
window.idrError = function (msg, title) {
	bootbox.alert({
		message: '<span class="bb-title"></span><span class="bb-sub"></span>',
		className: 'bootbox-sm bb-bad',
		onShown: function () { $('.bootbox.bb-bad .bb-title').text(title || 'Please check'); $('.bootbox.bb-bad .bb-sub').text(msg); }
	});
};
</script>
<?php
}

function idr_error_popup($title = 'Something went wrong')
{
	if (empty($_REQUEST['ermsg'])) { return; }
?>
<script type="text/javascript">
$(function () {
	idrError(<?php echo json_encode((string)$_REQUEST['ermsg']); ?>, <?php echo json_encode($title); ?>);
	if (window.history && history.replaceState) { var u = new URL(location.href); u.searchParams.delete('ermsg'); history.replaceState(null, '', u.pathname + u.search); }   // a refresh must not show it again
});
</script>
<?php
}

function idr_fail($page, $query, $msg)
{
	header('location:'.$page.'?'.($query !== '' ? $query.'&' : '').'ermsg='.urlencode($msg));
	exit();
}

/* runner for the mysqli connection most pages use */
function idr_runner_mysqli($db)
{
	return function ($sql) use ($db) {
		$out = array();
		$r = $db->query($sql);
		while ($r && ($row = $r->fetch_assoc())) { $out[] = $row; }
		return $out;
	};
}
