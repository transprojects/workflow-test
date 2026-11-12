<?php
/**
 * HTML body of the "freelancer completed the work" notification.
 * Same look as the assignment mail (assign_mail_template.php): table layout, inline styles, Outlook/Gmail/mobile safe.
 * $o: logo_cid (optional), article, freelancer, remarks, completed_at (optional strtotime string)
 */
function wc_mail_h($v)
{
	return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function work_complete_mail_body($o)
{
	$h = 'wc_mail_h';
	$when = !empty($o['completed_at']) && strtotime($o['completed_at']) ? date('d M Y, h:i A', strtotime($o['completed_at'])) : date('d M Y, h:i A');
	$remarks = trim((string)$o['remarks']);

	if (!empty($o['logo_cid'])) {
		$header = '<tr><td style="background:#ffffff;padding:22px 32px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>'
			. '<td align="left"><img src="cid:' . $h($o['logo_cid']) . '" width="200" alt="Transforma" style="display:block;border:0;outline:none;height:auto;width:200px"></td>'
			. '<td align="right" style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#94a3b8;font-weight:700">Workflow</td>'
			. '</tr></table></td></tr>';
	} else {
		$header = '<tr><td style="background:#14304a;padding:22px 32px"><span style="color:#ffffff;font-size:20px;font-weight:700;letter-spacing:.3px">TRANSFORMA</span> <span style="color:#f08a24;font-size:20px;font-weight:700">Workflow</span></td></tr>';
	}

	$cell = 'padding:11px 16px;border-bottom:1px solid #e5e9f0;font-size:14px';
	$remarks_row = '<tr><td style="' . $cell . ';color:#64748b;width:38%">Remarks</td><td style="' . $cell . ';color:#14304a;font-weight:700">' . ($remarks !== '' ? nl2br($h($remarks)) : '-') . '</td></tr>';

	return '<!doctype html><html><body style="margin:0;padding:0;background:#eef2f7;font-family:Segoe UI,Arial,Helvetica,sans-serif">'
	. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f7;padding:24px 12px"><tr><td align="center">'
	. '<table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;width:100%;background:#ffffff;border-radius:10px;overflow:hidden;border:1px solid #e1e6ee">'
	. $header
	. '<tr><td style="background:#f08a24;height:4px;font-size:0;line-height:0">&nbsp;</td></tr>'
	// intro
	. '<tr><td style="padding:28px 32px 8px"><div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#f08a24;font-weight:700">Work completed notification</div>'
	. '<div style="font-size:22px;font-weight:700;color:#14304a;margin:6px 0 12px">Article ' . $h($o['article']) . ' completed</div>'
	. '<div style="font-size:15px;color:#334155;line-height:1.6">Dear Team,<br/><b>' . $h($o['freelancer']) . '</b> has finished editing and submitted this article. Please proceed accordingly.</div></td></tr>'
	// status pill
	. '<tr><td style="padding:14px 32px 18px"><table role="presentation" cellpadding="0" cellspacing="0"><tr>'
	. '<td style="background:#dcfce7;border:1px solid #86efac;border-radius:20px;padding:7px 16px;font-size:14px;color:#166534">Status: <b>Completed</b></td>'
	. '</tr></table></td></tr>'
	// details card
	. '<tr><td style="padding:0 32px 26px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e9f0;border-radius:8px;border-collapse:separate;overflow:hidden">'
	. '<tr><td style="' . $cell . ';color:#64748b;width:38%;background:#f8fafc">Article</td><td style="' . $cell . ';color:#14304a;font-weight:700;background:#f8fafc">' . $h($o['article']) . '</td></tr>'
	. '<tr><td style="' . $cell . ';color:#64748b">Completed by</td><td style="' . $cell . ';color:#14304a;font-weight:700">' . $h($o['freelancer']) . '</td></tr>'
	. '<tr><td style="' . $cell . ';color:#64748b;background:#f8fafc">Completed on</td><td style="' . $cell . ';color:#14304a;font-weight:700;background:#f8fafc">' . $h($when) . '</td></tr>'
	. $remarks_row
	. '</table></td></tr>'
	// footer
	. '<tr><td style="background:#f8fafc;border-top:1px solid #e5e9f0;padding:16px 32px;font-size:12px;color:#64748b;line-height:1.6">Thank you,<br/><b>Inward Team</b>, Transforma<br/>This is an automated notification from Transforma Workflow.</td></tr>'
	. '</table></td></tr></table></body></html>';
}
