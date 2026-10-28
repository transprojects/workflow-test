<?php
/* IAP "File Package" workbook, sheet J-Stage: what moves into the XML department at the JSTAGE stage.
 *   PAG  -> XML : .xml            named  <pub id>_2
 *   PRQC -> XML : .pdf  .txt      named  <pub id>_2   (the .txt may also be <pub id>_note, the client note)
 * Applied to the IAP (customer 57) journals the workbook lists.  Existing JSTAGE -> XML rows are updated, missing ones (and a
 * missing Move Files record for the journal + source department) are created.  Other stages / destinations are not touched.
 * Not covered (see the workbook): PMC names (journal-vol-issue-id), Graphics / suppl folders, offprint / print PDFs (conditional).
 *     php sql/2026-10-26_seed_iap_jstage_name_rules.php <database> [--dry]
 */
require_once __DIR__ . '/../includes/move_naming.php';
$dry = in_array('--dry', $argv, true);
$args = array_values(array_filter($argv, function ($a) { return $a !== '--dry'; }));
if (count($args) < 2) { fwrite(STDERR, "usage: php seed.php <database> [--dry]\n"); exit(1); }
$db = new mysqli('localhost', 'root', '', $args[1]);
if ($db->connect_errno) { fwrite(STDERR, $db->connect_error . "\n"); exit(1); }

const CUST = 57, STAGE = 'JSTAGE', XML_DEPT = 5;
$journals = array('ACST-SCCJ', 'PTR', 'JTVT', 'ATCS', 'AVD', 'BHI', 'BPB', 'CPB', 'JOWHSR', 'MS', 'SCR', 'JSIAML', 'JNET');
$plan = array(
    10 => array('files' => 'xml',     'rules' => array('xml' => '{pubid}_2')),                                      /* PAG  */
    14 => array('files' => 'pdf|txt', 'rules' => array('pdf' => '{pubid}_2', 'txt' => '{pubid}_2, {pubid}_note')),   /* PRQC */
);
$n = array('records' => 0, 'rows_new' => 0, 'rows_upd' => 0);
foreach ($journals as $code) {
    $r = $db->query("SELECT j_id FROM adm_journals WHERE j_cust_id = " . CUST . " AND j_code = '" . $db->real_escape_string($code) . "'");
    $j = $r ? $r->fetch_assoc() : null;
    if (!$j) { echo "!! journal $code not found\n"; continue; }
    foreach ($plan as $src => $p) {
        $q = $db->query("SELECT mf_id FROM inw_move_files WHERE cust_id = " . CUST . " AND j_id = " . (int) $j['j_id'] . " AND dept_id = $src LIMIT 1");
        $mf = $q ? $q->fetch_assoc() : null;
        if (!$mf) {
            echo "$code: new Move Files record for source dept $src\n"; $n['records']++;
            if (!$dry) { $db->query("INSERT INTO inw_move_files (cust_id, j_id, dept_id, created_by, created_on) VALUES (" . CUST . ", " . (int) $j['j_id'] . ", $src, 0, NOW())"); $mf = array('mf_id' => $db->insert_id); }
            else { $mf = array('mf_id' => 0); }
        }
        $json = $db->real_escape_string(json_encode($p['rules'], JSON_UNESCAPED_SLASHES));
        $q = $db->query("SELECT f_id FROM inw_move_files_dept_wise WHERE mf_id = " . (int) $mf['mf_id'] . " AND stage = '" . STAGE . "' AND dept_id = " . XML_DEPT . " LIMIT 1");
        $row = ($mf['mf_id'] && $q) ? $q->fetch_assoc() : null;
        if ($row) {
            echo "$code: update JSTAGE src $src -> XML: {$p['files']}\n"; $n['rows_upd']++;
            if (!$dry) { $db->query("UPDATE inw_move_files_dept_wise SET move_files = '{$p['files']}', move_name_rules = '$json' WHERE f_id = " . (int) $row['f_id']); }
        } else {
            echo "$code: add    JSTAGE src $src -> XML: {$p['files']}\n"; $n['rows_new']++;
            if (!$dry) { $db->query("INSERT INTO inw_move_files_dept_wise (mf_id, stage, dept_id, move_files, move_name_rules, move_folders, created_by) VALUES (" . (int) $mf['mf_id'] . ", '" . STAGE . "', " . XML_DEPT . ", '{$p['files']}', '$json', '', 0)"); }
        }
    }
}
echo ($dry ? "[dry run] " : "") . "records created: {$n['records']}, rows added: {$n['rows_new']}, rows updated: {$n['rows_upd']}\n";
