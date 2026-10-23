<?php
/* Now LIVE: calculated from the transactions (same rules as the admin dashboard).
 * The previous version that read the nightly cron table is kept as view_my_consolidated_targetreport_cron.php and is no longer used. */
$cr_self = true;
include __DIR__ . '/view_consolidated_targetreport_live.php';
