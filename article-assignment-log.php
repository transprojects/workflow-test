<?php include "includes/includes.php";
/* Article assignment history: who an article was assigned to, who held it before, at which stage, when, by whom.
   Not for employees (level 3) or customers (level 14). */
$lvl = isset($_COOKIE['user_level']) ? (string)$_COOKIE['user_level'] : '';
if ($lvl === '' || $lvl === '3' || $lvl === '14') { header("location:".(isset($_COOKIE['set_url']) ? $_COOKIE['set_url'] : '')."/currentwork?msg=You do not have access to this page"); exit; }

$h = function($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$from   = isset($_GET['from_dt']) && $_GET['from_dt'] !== '' ? date('Y-m-d', strtotime($_GET['from_dt'])) : date('Y-m-d', strtotime('-30 days'));
$to     = isset($_GET['to_dt'])   && $_GET['to_dt']   !== '' ? date('Y-m-d', strtotime($_GET['to_dt']))   : date('Y-m-d');
$art    = isset($_GET['article_id']) ? trim($_GET['article_id']) : '';
$uid    = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

$where = "l.assigned_at >= '".$db->real_escape_string($from)." 00:00:00' AND l.assigned_at <= '".$db->real_escape_string($to)." 23:59:59'";
if ($art !== '')  { $where .= " AND l.pub_id LIKE '%".$db->real_escape_string($art)."%'"; }
if ($uid > 0)     { $where .= " AND (l.assigned_user_id = $uid OR l.prev_user_id = $uid)"; }
if (in_array($action, array('assign','reassign','auto','handoff'), true)) { $where .= " AND l.action = '".$db->real_escape_string($action)."'"; }

$rows = $db->query("SELECT l.*, d.dept_name, cu.full_name AS cur_name, pu.full_name AS prev_name, bu.full_name AS by_name,
	i.stage AS now_stage, i.status AS now_status, i.file_status AS now_file_status, i.department_id AS now_dept_id, i.present_user_id,
	i.assigned_user_id AS now_ce_user, i.assigned_od_user_id AS now_od_user,
	cm.cust_name, jn.j_code, nd.dept_name AS now_dept_name, wu.full_name AS working_name,
	IF(i.stage = 'FP', COALESCE(NULLIF(i.target_due_dt,'0000-00-00'), NULLIF(i.due_dt,'0000-00-00')),
	   (SELECT rv.due_date FROM inw_revisions_dtl rv WHERE rv.inw_id = i.id ORDER BY rv.r_id DESC LIMIT 1)) AS now_due,
	hu.full_name AS holder_name, hu.id AS holder_id
	FROM article_assignment_log l
	LEFT JOIN inw_inward_dtl i ON i.id = l.inw_id
	LEFT JOIN adm_customer_master cm ON cm.id = i.cust_id
	LEFT JOIN adm_journals jn ON jn.j_id = i.j_id
	LEFT JOIN adm_dept_master nd ON nd.dept_code = i.department_id
	LEFT JOIN users wu ON wu.id = i.present_user_id
	LEFT JOIN users hu ON hu.id = IF(i.department_id IN (10,14,34,38), NULLIF(i.assigned_od_user_id,0), NULLIF(i.assigned_user_id,0))
	LEFT JOIN adm_dept_master d ON d.dept_code = l.dept_id
	LEFT JOIN users cu ON cu.id = l.assigned_user_id
	LEFT JOIN users pu ON pu.id = l.prev_user_id
	LEFT JOIN users bu ON bu.id = l.assigned_by
	WHERE $where ORDER BY l.assigned_at DESC, l.id DESC LIMIT 5000");
$users = $db->query("SELECT id, full_name FROM users WHERE approved='1' ORDER BY full_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<?php include('includes/title.php'); ?>
<?php echo styles(); ?>
<?php include_once 'includes/modern-ui.php'; include_once 'includes/article-filter-ui.php'; mu_head(); af_head(); ?>
</head>
<body>
<div class="wholeouter">
<?php include("includes/top-header.php"); ?>
<div class="container-fluid" style="justify-content: space-between;">
	<div class="af-page" style="padding-bottom:0;">
		<div class="af-hero">
			<div><small>Allocation history</small><h1>Article Assignment Log</h1></div>
		</div>
	</div>
</div>

<div class="af-page"><div class="af-wrap">
	<form action="" method="get" class="formouter" autocomplete="off">
		<div class="af-bar">
			<div class="af-field search">
				<label class="mb-2">Article ID</label>
				<input class="form-control" type="text" name="article_id" placeholder="Enter Article ID" value="<?php echo $h($art); ?>">
			</div>
			<div class="af-field">
				<label class="mb-2">From</label>
				<input type="date" name="from_dt" class="form-control" value="<?php echo $h($from); ?>">
			</div>
			<div class="af-field">
				<label class="mb-2">To</label>
				<input type="date" name="to_dt" class="form-control" value="<?php echo $h($to); ?>">
			</div>
			<div class="af-field">
				<label class="mb-2">User (previous or current)</label>
				<select name="user_id" class="form-control"><option value="0">All</option>
				<?php while($u = $users->fetch_assoc()) { ?><option value="<?php echo (int)$u['id']; ?>" <?php if($uid==(int)$u['id']) echo 'selected'; ?>><?php echo $h($u['full_name']); ?></option><?php } ?>
				</select>
			</div>
			<div class="af-field">
				<label class="mb-2">Action</label>
				<select name="action" class="form-control"><option value="">All</option>
				<?php foreach(array('assign'=>'Assign','reassign'=>'Reassign','auto'=>'Auto assign','handoff'=>'Hand-off') as $k=>$v) { ?><option value="<?php echo $k; ?>" <?php if($action===$k) echo 'selected'; ?>><?php echo $v; ?></option><?php } ?>
				</select>
			</div>
			<div class="af-actions">
				<button type="submit" class="af-btn primary"><i class="fa fa-search"></i> Get Result</button>
				<a class="af-btn" href="assignmentlog" title="Reset filters"><i class="fa fa-refresh"></i></a>
			</div>
		</div>
	</form>
</div></div>

<div class="af-page"><div class="af-card">
	<div class="table-responsive">
	<table id="alog" class="table custtable table-primary" style="width: 100% !important; text-wrap: nowrap !important; white-space: nowrap !important">
		<thead><tr>
			<th class="thcolor">#</th><th class="thcolor">Article</th><th class="thcolor">Stage</th><th class="thcolor">Department</th>
			<th class="thcolor">Previous user</th><th class="thcolor">Current user</th><th class="thcolor">Action</th>
			<th class="thcolor">Assigned by</th><th class="thcolor">Date &amp; time</th><th class="thcolor">Details</th>
		</tr></thead>
		<tbody>
		<?php $i = 0; while($r = $rows->fetch_assoc()) { $i++; ?>
			<tr><td><?php echo $i; ?></td><td><?php echo $h($r['pub_id']); ?></td><td><?php echo $h($r['stage']); ?></td><td><?php echo $h($r['dept_name']); ?></td>
			<td><?php echo $r['prev_user_id'] ? $h($r['prev_name']) : '&mdash;'; ?></td><td><?php echo $h($r['cur_name']); ?></td>
			<td><?php echo $h(ucfirst($r['action'])); ?></td><td><?php echo $h($r['by_name']); ?></td><td data-order="<?php echo $h($r['assigned_at']); ?>"><?php echo $h(date('d-m-Y H:i', strtotime($r['assigned_at']))); ?></td>
			<?php $still = ((int)$r['holder_id'] === (int)$r['assigned_user_id']) && (int)$r['assigned_user_id'] > 0;
			$d = array(
				'article' => $r['pub_id'], 'customer' => $r['cust_name'], 'journal' => $r['j_code'],
				'a_stage' => $r['stage'], 'a_dept' => $r['dept_name'], 'a_prev' => $r['prev_user_id'] ? $r['prev_name'] : '', 'a_cur' => $r['cur_name'],
				'a_action' => ucfirst($r['action']), 'a_by' => $r['by_name'], 'a_at' => date('d-m-Y H:i', strtotime($r['assigned_at'])),
				'n_stage' => $r['now_stage'], 'n_status' => $r['now_status'], 'n_file' => $r['now_file_status'], 'n_dept' => $r['now_dept_name'],
				'n_holder' => $r['holder_name'], 'n_working' => $r['working_name'],
				'n_due' => ($r['now_due'] && $r['now_due'] !== '0000-00-00') ? date('d-m-Y', strtotime($r['now_due'])) : '', 'still' => $still ? 1 : 0); ?>
			<td><button type="button" class="af-btn alog-view" data-d="<?php echo $h(json_encode($d)); ?>"><i class="fa fa-eye"></i> View</button></td></tr>
		<?php } ?>
		</tbody>
	</table>
	</div>
</div></div>

<div id="alogModal" class="alog-ov" style="display:none"><div class="alog-box">
	<div class="alog-head"><b id="alogTitle"></b><button type="button" id="alogClose" aria-label="Close">&times;</button></div>
	<div id="alogBody"></div>
</div></div>
<style>
.alog-ov{position:fixed;inset:0;background:rgba(20,48,74,.45);z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px;}
.alog-box{background:#fff;border-radius:14px;max-width:640px;width:100%;max-height:90vh;overflow:auto;box-shadow:0 20px 50px rgba(20,48,74,.35);}
.alog-head{display:flex;justify-content:space-between;align-items:center;padding:14px 20px;background:linear-gradient(120deg,#14304a,#1b6ca8);color:#fff;border-radius:14px 14px 0 0;font-size:16px;}
.alog-head button{background:none;border:0;color:#fff;font-size:26px;line-height:1;cursor:pointer;}
.alog-sec{padding:12px 20px 4px;}
.alog-sec h4{margin:0 0 8px;font-size:11px;letter-spacing:1px;text-transform:uppercase;color:#6b7785;}
.alog-row{display:flex;justify-content:space-between;gap:12px;padding:7px 0;border-bottom:1px solid #eef2f6;font-size:13.5px;}
.alog-row span:first-child{color:#6b7785;}.alog-row span:last-child{color:#22303f;font-weight:600;text-align:right;}
.alog-badge{display:inline-block;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:700;}
.alog-yes{background:#e3f6ec;color:#1e9e61;}.alog-no{background:#eef2f6;color:#6b7785;}.alog-un{background:#fdecec;color:#b04545;}
</style>
<?php echo footer(); ?>
</div>
<?php echo scripts(); ?>
<script>
$(document).ready(function(){
	$('#alog').DataTable({ pageLength: 25, order: [[8, 'desc']] });
	function esc(v){ return $('<div>').text(v == null || v === '' ? '\u2013' : v).html(); }
	function row(k, v){ return '<div class="alog-row"><span>' + k + '</span><span>' + v + '</span></div>'; }
	$(document).on('click', '.alog-view', function(){
		var d = JSON.parse($(this).attr('data-d'));
		var holder = d.n_holder ? esc(d.n_holder) + ' <small>(' + esc(d.n_dept) + ')</small>' : '<span class="alog-badge alog-un">Unallocated</span> <small>(' + esc(d.n_dept) + ')</small>';
		var still = d.still ? '<span class="alog-badge alog-yes">Yes - still holds it</span>' : '<span class="alog-badge alog-no">No - re-assigned or moved on</span>';
		$('#alogTitle').text('#' + d.article);
		$('#alogBody').html(
			'<div class="alog-sec"><h4>Article</h4>' + row('Customer', esc(d.customer)) + row('Journal', esc(d.journal)) + '</div>' +
			'<div class="alog-sec"><h4>This assignment</h4>' + row('Date &amp; time', esc(d.a_at)) + row('Stage', esc(d.a_stage)) + row('Department', esc(d.a_dept)) +
				row('Previous user', esc(d.a_prev)) + row('Assigned to', esc(d.a_cur)) + row('Action', esc(d.a_action)) + row('Assigned by', esc(d.a_by)) + '</div>' +
			'<div class="alog-sec" style="padding-bottom:16px"><h4>Current status</h4>' + row('Current stage', esc(d.n_stage)) + row('Status', esc(d.n_status)) + row('File status', esc(d.n_file)) +
				row('Now with', holder) + row('Working now', esc(d.n_working)) + row('Due date', esc(d.n_due)) + row('Assigned user still holds it?', still) + '</div>');
		$('#alogModal').show();
	});
	$('#alogClose').on('click', function(){ $('#alogModal').hide(); });
	$('#alogModal').on('click', function(e){ if (e.target === this) { $(this).hide(); } });
	$(document).on('keydown', function(e){ if (e.key === 'Escape') { $('#alogModal').hide(); } });
});
</script>
</body>
</html>
