<?php
/**
 * Builds the HTML body of the freelancer (re)assignment notification.
 * Table based layout with inline styles so it renders in Outlook, Gmail and mobile clients.
 */
function assign_mail_h($v)
{
	return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function assign_mail_articles($db, $pub_ids)
{
	$rows = array();
	foreach ((array)$pub_ids as $pid) {
		$pid = trim($pid);
		if ($pid === '') {
			continue;
		}
		$res = mysqli_query($db, "SELECT wd.pub_id, wd.recv_dt, wd.due_dt, wd.manuscript_count, wd.fig, wd.tab, j.j_code
			FROM inw_inward_dtl wd LEFT JOIN adm_journals j ON wd.j_id = j.j_id
			WHERE wd.pub_id = '" . mysqli_real_escape_string($db, $pid) . "' LIMIT 1");
		$row = $res ? mysqli_fetch_assoc($res) : null;
		$rows[] = $row ? $row : array('pub_id' => $pid);
	}
	return $rows;
}

function assign_mail_date($d)
{
	$t = strtotime($d);
	return ($d && $t) ? date('d M Y', $t) : '-';
}

/**
 * $o: logo_cid (optional), full_name, user_name, password, message, due_date (any strtotime string), portal_url, reassign (bool), articles (rows from assign_mail_articles)
 */
function assign_mail_body($o)
{
	$h = 'assign_mail_h';
	$n = count($o['articles']);
	$verb = !empty($o['reassign']) ? 'reassigned to you' : 'assigned to you';
	$heading = $n === 1 ? 'New article ' . (!empty($o['reassign']) ? 'reassigned' : 'assigned') : $n . ' articles ' . (!empty($o['reassign']) ? 'reassigned' : 'assigned');
	$due = assign_mail_date($o['due_date']);

	$rows = '';
	foreach ($o['articles'] as $i => $a) {
		$bg = $i % 2 ? '#f8fafc' : '#ffffff';
		$jr = isset($a['j_code']) ? $a['j_code'] : '';
		$rows .= '<tr style="background:' . $bg . '">'
			. '<td style="padding:10px 12px;border-bottom:1px solid #e5e9f0;font-weight:700;color:#14304a">' . $h($a['pub_id']) . '</td>'
			. '<td style="padding:10px 12px;border-bottom:1px solid #e5e9f0;color:#475569">' . $h($jr) . '</td>'
			. '<td style="padding:10px 12px;border-bottom:1px solid #e5e9f0;color:#475569">' . $h(isset($a['recv_dt']) ? assign_mail_date($a['recv_dt']) : '-') . '</td>'
			. '<td style="padding:10px 12px;border-bottom:1px solid #e5e9f0;color:#475569;text-align:center">' . $h(isset($a['manuscript_count']) ? $a['manuscript_count'] : '-') . '</td>'
			. '<td style="padding:10px 12px;border-bottom:1px solid #e5e9f0;color:#475569;text-align:center">' . $h(isset($a['fig']) ? $a['fig'] : '-') . '</td>'
			. '<td style="padding:10px 12px;border-bottom:1px solid #e5e9f0;color:#475569;text-align:center">' . $h(isset($a['tab']) ? $a['tab'] : '-') . '</td>'
			. '</tr>';
	}

	$th = 'padding:9px 12px;background:#14304a;color:#ffffff;font-size:11px;letter-spacing:.06em;text-transform:uppercase;text-align:left';
	$note = trim((string)$o['message']) !== ''
		? '<tr><td style="padding:0 32px 22px"><div style="background:#fff7ed;border-left:4px solid #f08a24;padding:12px 16px;border-radius:4px;color:#7c4a03;font-size:14px;line-height:1.5"><b>Instructions</b><br/>' . nl2br($h($o['message'])) . '</div></td></tr>'
		: '';

	if (!empty($o['logo_cid'])) {
		$header = '<tr><td style="background:#ffffff;padding:22px 32px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>'
			. '<td align="left"><img src="cid:' . $h($o['logo_cid']) . '" width="200" alt="Transforma" style="display:block;border:0;outline:none;height:auto;width:200px"></td>'
			. '<td align="right" style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#94a3b8;font-weight:700">Workflow</td>'
			. '</tr></table></td></tr>';
	} else {
		$header = '<tr><td style="background:#14304a;padding:22px 32px"><span style="color:#ffffff;font-size:20px;font-weight:700;letter-spacing:.3px">TRANSFORMA</span> <span style="color:#f08a24;font-size:20px;font-weight:700">Workflow</span></td></tr>';
	}

	return '<!doctype html><html><body style="margin:0;padding:0;background:#eef2f7;font-family:Segoe UI,Arial,Helvetica,sans-serif">'
	. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f7;padding:24px 12px"><tr><td align="center">'
	. '<table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;width:100%;background:#ffffff;border-radius:10px;overflow:hidden;border:1px solid #e1e6ee">'
	// header (logo is embedded in the mail as cid:<logo_cid>; falls back to text when no logo is given)
	. $header
	. '<tr><td style="background:#f08a24;height:4px;font-size:0;line-height:0">&nbsp;</td></tr>'
	// intro
	. '<tr><td style="padding:28px 32px 8px"><div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#f08a24;font-weight:700">Assignment notification</div>'
	. '<div style="font-size:22px;font-weight:700;color:#14304a;margin:6px 0 12px">' . $h($heading) . '</div>'
	. '<div style="font-size:15px;color:#334155;line-height:1.6">Dear ' . $h($o['full_name']) . ',<br/>The following ' . ($n === 1 ? 'article has' : 'articles have') . ' been ' . $verb . '. Kindly process and complete ' . ($n === 1 ? 'it' : 'them') . ' promptly.</div></td></tr>'
	// due date pill
	. '<tr><td style="padding:14px 32px 18px"><table role="presentation" cellpadding="0" cellspacing="0"><tr>'
	. '<td style="background:#fff1e0;border:1px solid #f6c48b;border-radius:20px;padding:7px 16px;font-size:14px;color:#7c4a03">Your due date: <b>' . $h($due) . '</b></td>'
	. '</tr></table></td></tr>'
	// articles table
	. '<tr><td style="padding:0 32px 22px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e9f0;border-radius:8px;border-collapse:separate;overflow:hidden;font-size:13px">'
	. '<tr><th style="' . $th . '">Article</th><th style="' . $th . '">Journal</th><th style="' . $th . '">Received</th><th style="' . $th . ';text-align:center">Pages</th><th style="' . $th . ';text-align:center">Fig</th><th style="' . $th . ';text-align:center">Table</th></tr>'
	. $rows . '</table></td></tr>'
	. $note
	// credentials card
	. '<tr><td style="padding:0 32px 24px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e5e9f0;border-radius:8px">'
	. '<tr><td style="padding:16px 20px"><div style="font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:#64748b;font-weight:700;margin-bottom:8px">Freelancer portal login</div>'
	. '<div style="font-size:14px;color:#334155;line-height:1.9">Username: <b>' . $h($o['user_name']) . '</b><br/>Password: <b>' . $h($o['password']) . '</b></div></td></tr></table></td></tr>'
	// button
	. '<tr><td align="center" style="padding:0 32px 30px"><a href="' . $h($o['portal_url']) . '" style="display:inline-block;background:#f08a24;color:#ffffff;text-decoration:none;font-weight:700;font-size:15px;padding:13px 34px;border-radius:8px">Open Freelancer Portal</a></td></tr>'
	// footer
	. '<tr><td style="background:#f8fafc;border-top:1px solid #e5e9f0;padding:16px 32px;font-size:12px;color:#64748b;line-height:1.6">Thank you,<br/><b>Inward Team</b>, Transforma<br/>This is an automated notification from Transforma Workflow.</td></tr>'
	. '</table></td></tr></table></body></html>';
}
