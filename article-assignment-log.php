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
</head>
<body>
<div class="wholeouter">
<?php include("includes/top-header.php"); ?>
<div class="container-fluid mt-4">
	<p class="reportsectionheading ms-4">Article Assignment Log</p>
	<form method="get" class="row g-2 mx-4 mb-3">
		<div class="col-auto"><label>From</label><input type="date" name="from_dt" class="form-control" value="<?php echo $h($from); ?>"></div>
		<div class="col-auto"><label>To</label><input type="date" name="to_dt" class="form-control" value="<?php echo $h($to); ?>"></div>
		<div class="col-auto"><label>Article ID</label><input type="text" name="article_id" class="form-control" value="<?php echo $h($art); ?>"></div>
		<div class="col-auto"><label>User (previous or current)</label>
			<select name="user_id" class="form-control"><option value="0">All</option>
			<?php while($u = $users->fetch_assoc()) { ?><option value="<?php echo (int)$u['id']; ?>" <?php if($uid==(int)$u['id']) echo 'selected'; ?>><?php echo $h($u['full_name']); ?></option><?php } ?>
			</select></div>
		<div class="col-auto"><label>Action</label>
			<select name="action" class="form-control"><option value="">All</option>
			<?php foreach(array('assign'=>'Assign','reassign'=>'Reassign','auto'=>'Auto assign','handoff'=>'Hand-off') as $k=>$v) { ?><option value="<?php echo $k; ?>" <?php if($action===$k) echo 'selected'; ?>><?php echo $v; ?></option><?php } ?>
			</select></div>
		<div class="col-auto align-self-end"><button type="submit" class="btn btn-primary">Get Result</button></div>
	</form>
	<div class="mx-4">
	<table id="alog" class="table table-striped table-bordered">
		<thead><tr><th>#</th><th>Article</th><th>Stage</th><th>Department</th><th>Previous user</th><th>Current user</th><th>Action</th><th>Assigned by</th><th>Date &amp; time</th></tr></thead>
		<tbody>
		<?php $i = 0; while($r = $rows->fetch_assoc()) { $i++; ?>
			<tr><td><?php echo $i; ?></td><td><?php echo $h($r['pub_id']); ?></td><td><?php echo $h($r['stage']); ?></td><td><?php echo $h($r['dept_name']); ?></td>
			<td><?php echo $r['prev_user_id'] ? $h($r['prev_name']) : '&mdash;'; ?></td><td><?php echo $h($r['cur_name']); ?></td>
			<td><?php echo $h($r['action']); ?></td><td><?php echo $h($r['by_name']); ?></td><td><?php echo $h(date('d-m-Y H:i', strtotime($r['assigned_at']))); ?></td></tr>
		<?php } ?>
		</tbody>
	</table>
	</div>
</div>
<?php echo footer(); ?>
</div>
<?php echo scripts(); ?>
<script>$(document).ready(function(){ $('#alog').DataTable({ pageLength: 25 }); });</script>
</body>
</html>
