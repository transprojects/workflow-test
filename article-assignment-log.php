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

$rows = $db->query("SELECT l.*, d.dept_name, cu.full_name AS cur_name, pu.full_name AS prev_name, bu.full_name AS by_name
	FROM article_assignment_log l
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
			<th class="thcolor">Assigned by</th><th class="thcolor">Date &amp; time</th>
		</tr></thead>
		<tbody>
		<?php $i = 0; while($r = $rows->fetch_assoc()) { $i++; ?>
			<tr><td><?php echo $i; ?></td><td><?php echo $h($r['pub_id']); ?></td><td><?php echo $h($r['stage']); ?></td><td><?php echo $h($r['dept_name']); ?></td>
			<td><?php echo $r['prev_user_id'] ? $h($r['prev_name']) : '&mdash;'; ?></td><td><?php echo $h($r['cur_name']); ?></td>
			<td><?php echo $h(ucfirst($r['action'])); ?></td><td><?php echo $h($r['by_name']); ?></td><td data-order="<?php echo $h($r['assigned_at']); ?>"><?php echo $h(date('d-m-Y H:i', strtotime($r['assigned_at']))); ?></td></tr>
		<?php } ?>
		</tbody>
	</table>
	</div>
</div></div>
<?php echo footer(); ?>
</div>
<?php echo scripts(); ?>
<script>$(document).ready(function(){ $('#alog').DataTable({ pageLength: 25, order: [[8, 'desc']] }); });</script>
</body>
</html>
