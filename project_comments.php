<?php include "includes/includes.php";

/* ============================================================================
 * Comments / Instructions of one article - HTML fragment shown in the pop-up
 * (loaded with  $("#ppopup").load("project_comments.php?id=..")  by the article lists and Start Work).
 *
 *   - Customer instructions  (inw_dept_comments)
 *   - Internal comments      (inw_transactions.comments)
 *
 * Users and departments are read once for the whole list instead of once per comment,
 * the id is cast to a number, and comment text is cleaned before it is printed.
 * ========================================================================== */

function pc_e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
/* Comment text for display.  Instructions are often pasted from e-mails / forms (tables, lots of blank lines, inline styles):
 *   - only simple formatting tags (and tables) are kept, every attribute is dropped (links keep a checked href only)
 *   - the whitespace between tags and runs of blank lines / <br> are collapsed, so the text no longer floats in big gaps */
function pc_text($t)
{
	$t = str_replace(array("\r\n", "\r"), "\n", (string)$t);
	$t = strip_tags($t, '<br><b><strong><i><em><u><ul><ol><li><p><a><table><thead><tbody><tr><td><th>');
	$t = preg_replace('/<(\/?)(br|b|strong|i|em|u|ul|ol|li|p|table|thead|tbody|tr|td|th)\b[^>]*?(\/?)>/i', '<$1$2$3>', $t);          // drop attributes
	$t = preg_replace_callback('/<a\b([^>]*)>/i', function ($m) {
		if (preg_match('/href\s*=\s*(["\'])\s*((?:https?:|mailto:)[^"\']*)\1/i', $m[1], $h)) {
			return '<a href="'.htmlspecialchars(html_entity_decode($h[2]), ENT_QUOTES, 'UTF-8').'" target="_blank" rel="noopener noreferrer">';
		}
		return '<a>';
	}, $t);
	$t = str_replace(array('&nbsp;', "\xC2\xA0"), ' ', $t);
	$t = preg_replace('/[ \t]+/', ' ', $t);                                  // runs of spaces
	$t = preg_replace('/>\s+</', '><', $t);                                  // whitespace between tags (breaks tables when turned into <br>)
	$t = preg_replace('/<(p|td|th)>\s*(<br\s*\/?>\s*)*<\/\1>/i', '<$1></$1>', $t);   // cells / paragraphs holding only line breaks
	$t = preg_replace('/<p>\s*<\/p>/i', '', $t);
	$t = preg_replace('/(<br\s*\/?>\s*){2,}/i', '<br>', $t);                 // several blank lines -> one line break
	$t = preg_replace("/\n{2,}/", "\n", trim($t));
	$t = preg_replace('/^(\s*<br\s*\/?>)+|(<br\s*\/?>\s*)+$/i', '', $t);     // no leading / trailing breaks
	return nl2br($t);
}
function pc_initials($n)
{
	$i = '';
	foreach (array_slice(preg_split('/\s+/', trim((string)$n)), 0, 2) as $w) { $i .= strtoupper(substr($w, 0, 1)); }
	return $i !== '' ? $i : '?';
}
function pc_when($v)
{
	$ts = strtotime((string)$v);
	return $ts ? array(date('d M Y', $ts), date('h:i A', $ts)) : array('', '');
}

$id = (int)(isset($_REQUEST['id']) ? $_REQUEST['id'] : 0);
$art = null;
$r = $db->query("SELECT a.id, a.pub_id, c.cust_name, j.j_code, j.j_name
                 FROM inw_inward_dtl a
                 LEFT JOIN adm_customer_master c ON c.id = a.cust_id
                 LEFT JOIN adm_journals j ON j.j_id = a.j_id
                 WHERE a.id = ".$id." LIMIT 1");
if ($r) { $art = $r->fetch_assoc(); }
if (!$art) { echo '<div class="pc-empty">Article not found.</div>'; return; }

$inst = array(); $comm = array(); $uids = array(); $dept_name = array();
$q = $db->query("SELECT created_by, created_dept, created_on, comments FROM inw_dept_comments WHERE inw_id = ".$id." ORDER BY id ASC");
while ($q && ($x = $q->fetch_assoc())) { $inst[] = $x; $uids[(int)$x['created_by']] = true; }
$q = $db->query("SELECT created_by, created_dt, comments FROM inw_transactions WHERE project_id = ".$id." AND comments != '' ORDER BY id ASC");
while ($q && ($x = $q->fetch_assoc())) { $comm[] = $x; $uids[(int)$x['created_by']] = true; }

$users = array();
if ($uids) {
	$q = $db->query("SELECT id, full_name, user_name, profile_pic, department_id FROM users WHERE id IN (".implode(',', array_keys($uids)).")");
	while ($q && ($x = $q->fetch_assoc())) { $users[(int)$x['id']] = $x; }
}
$q = $db->query("SELECT dept_code, dept_name FROM adm_dept_master");
while ($q && ($x = $q->fetch_assoc())) { $dept_name[(int)$x['dept_code']] = $x['dept_name']; }

/* one comment card */
function pc_card($n, $tone, $u, $dept, $when, $body, $to = '')
{
	$name = $u ? ($u['full_name'] !== '' ? $u['full_name'] : $u['user_name']) : 'Unknown user';
	$pic  = ($u && $u['profile_pic'] !== '' && $u['profile_pic'] !== null) ? $u['profile_pic'] : '';
	$av   = ($pic !== '' && isset($_COOKIE['set_url']))
	      ? '<img class="pc-avatar" alt="" src="'.pc_e($_COOKIE['set_url']).'/assets/images/avatars/'.pc_e($pic).'" onerror="this.outerHTML=\'<span class=&quot;pc-avatar pc-ini&quot;>'.pc_e(pc_initials($name)).'</span>\'">'
	      : '<span class="pc-avatar pc-ini">'.pc_e(pc_initials($name)).'</span>';
	$h  = '<li class="pc-item '.$tone.'"><span class="pc-node">'.$av.'</span><div class="pc-card">';
	$h .= '<div class="pc-head"><b>'.pc_e(ucwords($name)).'</b>';
	if ($dept !== '') { $h .= '<span class="pc-dept">'.pc_e($dept).'</span>'; }
	if ($to !== '')   { $h .= '<span class="pc-to"><i class="fa fa-arrow-right"></i> '.pc_e($to).'</span>'; }
	$h .= '<span class="pc-when"><i class="fa fa-calendar-o"></i> '.pc_e($when[0]).' <small>'.pc_e($when[1]).'</small></span></div>';
	$h .= $body !== '' ? '<div class="pc-body">'.pc_text($body).'</div>' : '<div class="pc-body pc-none">No comment text.</div>';
	return $h.'<span class="pc-n">#'.(int)$n.'</span></div></li>';
}
?>
<style>
.pc{--navy:#14304a;--accent:#e8742a;--blue:#1b6ca8;--ink:#22303f;--mute:#6b7785;--line:#e4e9ef;color:var(--ink);font-size:13.5px;}
.pc *{box-sizing:border-box;}
.pc-top{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px 16px;padding:14px 18px;margin-bottom:18px;border-radius:14px;color:#fff;
  background:linear-gradient(120deg,#14304a 0%,#1f4b73 60%,#1b6ca8 100%);box-shadow:0 6px 16px rgba(20,48,74,.18);animation:muUp .35s ease both;}
.pc-top small{display:block;font-size:10.5px;letter-spacing:1px;text-transform:uppercase;opacity:.75;}
.pc-top h4{margin:0;font-size:20px;font-weight:700;color:#fff;word-break:break-all;}
.pc-top p{margin:2px 0 0;opacity:.85;font-size:12.5px;}
.pc-counts{display:flex;gap:8px;}
.pc-counts span{padding:6px 12px;border-radius:10px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.22);font-weight:600;font-size:12px;}
.pc-counts b{font-size:16px;margin-right:4px;}
.pc-sec{margin:0 0 20px;}
.pc-sec>h5{display:flex;align-items:center;gap:8px;margin:0 0 12px;font-size:12.5px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--navy);}
.pc-sec>h5 i{color:var(--c);} .pc-sec>h5 em{font-style:normal;min-width:22px;height:20px;line-height:20px;padding:0 7px;border-radius:20px;background:color-mix(in srgb,var(--c) 14%,#fff);color:var(--c);text-align:center;font-size:11.5px;}
.pc-sec.ci{--c:#e8742a;} .pc-sec.ic{--c:#1b6ca8;}
.pc-list{list-style:none;margin:0;padding:0 0 0 22px;position:relative;}
.pc-list:before{content:"";position:absolute;left:17px;top:6px;bottom:6px;width:2px;background:linear-gradient(180deg,var(--c),#e4e9ef);border-radius:2px;}
.pc-item{position:relative;margin:0 0 14px;padding-left:34px;animation:muUp .4s ease both;}
.pc-item:nth-child(2){animation-delay:.05s;} .pc-item:nth-child(3){animation-delay:.1s;} .pc-item:nth-child(n+4){animation-delay:.15s;}
.pc-node{position:absolute;left:-14px;top:0;width:36px;height:36px;border-radius:50%;background:#fff;border:2px solid var(--c);display:flex;align-items:center;justify-content:center;overflow:hidden;box-shadow:0 2px 8px rgba(20,48,74,.15);z-index:1;}
.pc-avatar{width:100%;height:100%;object-fit:cover;border-radius:50%;}
.pc-ini{display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;background:linear-gradient(135deg,var(--c),#14304a);}
.pc-card{position:relative;background:#fff;border:1px solid var(--line);border-left:4px solid var(--c);border-radius:12px;padding:12px 16px 12px;box-shadow:0 2px 8px rgba(20,48,74,.06);transition:box-shadow .2s,transform .2s;}
.pc-card:hover{box-shadow:0 10px 22px rgba(20,48,74,.12);transform:translateY(-2px);}
.pc-head{display:flex;flex-wrap:wrap;align-items:center;gap:6px 10px;margin-bottom:8px;padding-right:34px;}
.pc-head b{font-size:14px;color:var(--navy);}
.pc-dept{padding:2px 9px;border-radius:20px;background:color-mix(in srgb,var(--c) 13%,#fff);color:var(--c);font-weight:700;font-size:11px;}
.pc-to{color:var(--mute);font-size:11.5px;font-weight:600;}
.pc-when{margin-left:auto;color:var(--mute);font-size:12px;white-space:nowrap;} .pc-when small{color:#9aa6b2;}
.pc-body{padding:10px 12px;border-radius:9px;background:#f6f9fc;line-height:1.55;word-break:break-word;}
.pc-body table{width:100%;border-collapse:collapse;margin:6px 0;font-size:13px;background:#fff;border:1px solid var(--line);border-radius:8px;overflow:hidden;}
.pc-body th,.pc-body td{padding:6px 10px;border:1px solid var(--line);vertical-align:top;text-align:left;}
.pc-body th{background:#eef3f8;color:#14304a;font-weight:700;}
.pc-body td:empty{padding:0;border:0;}
.pc-body p{margin:0 0 6px;} .pc-body ul,.pc-body ol{margin:4px 0 6px 18px;padding:0;} .pc-body a{color:#1b6ca8;text-decoration:underline;}
.pc-body.pc-none{color:#9aa6b2;font-style:italic;}
.pc-n{position:absolute;right:12px;top:10px;font-size:11px;font-weight:700;color:#b3bcc7;}
.pc-empty{padding:26px;text-align:center;color:var(--mute);background:#f9fbfd;border:1px dashed var(--line);border-radius:12px;}
.pc-empty i{display:block;font-size:26px;margin-bottom:6px;color:#c9d2dc;}
@media(max-width:600px){.pc-when{margin-left:0;}}
</style>

<div class="pc">
	<div class="pc-top">
		<div>
			<small>Article</small>
			<h4><?php echo pc_e($art['pub_id']); ?></h4>
			<p><?php echo pc_e($art['cust_name']); ?><?php echo $art['j_code'] ? ' &nbsp;&middot;&nbsp; '.pc_e($art['j_code']).($art['j_name'] ? ' &ndash; '.pc_e($art['j_name']) : '') : ''; ?></p>
		</div>
		<div class="pc-counts">
			<span><b><?php echo count($inst); ?></b>Instructions</span>
			<span><b><?php echo count($comm); ?></b>Comments</span>
		</div>
	</div>

	<?php if ($inst) { ?>
	<section class="pc-sec ci">
		<h5><i class="fa fa-bullhorn"></i> Customer instructions <em><?php echo count($inst); ?></em></h5>
		<ul class="pc-list">
<?php foreach ($inst as $i => $x) {
	$u = isset($users[(int)$x['created_by']]) ? $users[(int)$x['created_by']] : null;
	echo pc_card($i + 1, 'ci', $u, isset($dept_name[(int)$x['created_dept']]) ? $dept_name[(int)$x['created_dept']] : '', pc_when($x['created_on']), $x['comments'], 'All departments');
} ?>
		</ul>
	</section>
	<?php } ?>

	<section class="pc-sec ic">
		<h5><i class="fa fa-comments-o"></i> Internal comments <em><?php echo count($comm); ?></em></h5>
		<?php if (!$comm) { ?>
		<div class="pc-empty"><i class="fa fa-comment-o"></i>No internal comments yet.</div>
		<?php } else { ?>
		<ul class="pc-list">
<?php foreach ($comm as $i => $x) {
	$u = isset($users[(int)$x['created_by']]) ? $users[(int)$x['created_by']] : null;
	$d = ($u && isset($dept_name[(int)$u['department_id']])) ? $dept_name[(int)$u['department_id']] : '';
	echo pc_card($i + 1, 'ic', $u, $d, pc_when($x['created_dt']), $x['comments']);
} ?>
		</ul>
		<?php } ?>
	</section>
</div>
