<?php
/* Replaces the customer-82 special case that used to be hard-coded in rcopy() (start_work.php): for every Move Files row of
 * customer 82, each listed extension gets the rule "{pubid}" (only <pubid>.<ext> moves).  Rows that already have rules are left alone.
 * Run after 2026-10-26_move_name_rules.sql, BEFORE deploying the new start_work.php:
 *     php sql/2026-10-26_seed_customer82_name_rules.php <database> [user] [password]       (add --dry to only list)
 */
require_once __DIR__ . '/../includes/move_naming.php';
$args = array_values(array_filter($argv, function ($a) { return $a !== '--dry'; }));
$dry  = in_array('--dry', $argv, true);
if (count($args) < 2) { fwrite(STDERR, "usage: php seed.php <database> [user] [password] [--dry]\n"); exit(1); }
$db = new mysqli('localhost', isset($args[2]) ? $args[2] : 'root', isset($args[3]) ? $args[3] : '', $args[1]);
if ($db->connect_errno) { fwrite(STDERR, $db->connect_error . "\n"); exit(1); }

$r = $db->query("SELECT d.f_id, d.move_files FROM inw_move_files_dept_wise d JOIN inw_move_files f ON f.mf_id = d.mf_id
                 WHERE f.cust_id = 82 AND d.move_files <> '' AND d.move_name_rules IS NULL");
$n = 0;
while ($row = $r->fetch_assoc()) {
    $rules = array();
    foreach (mfn_ext_list($row['move_files']) as $e) { $rules[$e] = '{pubid}'; }
    if (!$rules) { continue; }
    $json = json_encode($rules);
    echo ($dry ? 'would set' : 'set') . " f_id {$row['f_id']}: $json\n";
    if (!$dry) { $db->query("UPDATE inw_move_files_dept_wise SET move_name_rules = '" . $db->real_escape_string($json) . "' WHERE f_id = " . (int) $row['f_id']); }
    $n++;
}
echo "$n row(s)\n";
