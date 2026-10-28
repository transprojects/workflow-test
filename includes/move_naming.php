<?php
/* Move Files: per-extension file-naming rules.
 *
 * inw_move_files_dept_wise.move_name_rules holds JSON  {"xml":"{pubid}","docx":"{pubid}_*"}  (extension => pattern).
 * An extension without a rule moves every file of that extension (the behaviour before this feature).
 * An extension with a rule moves only the files whose name (without the extension) matches the pattern.
 *
 * Pattern (matched case-insensitively against the whole file name without its extension):
 *   {pubid}   the article's pub id, taken literally ("-", "_" and "." inside it are safe)
 *   *         any characters        ?   one character        [-_]  one of the listed characters
 *   ~regex~   a full regular expression between two tildes, e.g. ~^{pubid}(_rev\d+)?$~  ({pubid} is escaped for you)
 */

/* extensions listed in a Files cell ("xml|docx"), lower-case, no dots, no blanks */
function mfn_ext_list($move_files)
{
    $out = array();
    foreach (explode('|', (string) $move_files) as $e) {
        $e = strtolower(trim($e, " .\t\r\n"));
        if ($e !== '') { $out[$e] = $e; }
    }
    return array_values($out);
}

/* JSON from the database -> array(ext => pattern); never fails */
function mfn_parse_rules($json)
{
    $out = array();
    $a = ($json === null || $json === '') ? null : json_decode($json, true);
    if (is_array($a)) {
        foreach ($a as $ext => $pat) {
            $ext = strtolower(trim((string) $ext, " ."));
            if ($ext !== '' && is_string($pat) && trim($pat) !== '') { $out[$ext] = trim($pat); }
        }
    }
    return $out;
}

/* pattern -> PCRE (anchored, case-insensitive), or null when it cannot be compiled */
function mfn_regex($pattern, $pub_id)
{
    $pattern = trim((string) $pattern);
    if ($pattern === '') { return null; }
    $pid = preg_quote((string) $pub_id, '/');
    if (strlen($pattern) > 2 && $pattern[0] === '~' && substr($pattern, -1) === '~') {
        $re = '/' . str_replace('/', '\/', str_replace('{pubid}', $pid, substr($pattern, 1, -1))) . '/iu';
    } else {
        $re = '';
        $parts = preg_split('/(\{pubid\}|\[[^\]]*\]|\*|\?)/', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $p) {
            if ($p === '') { continue; }
            if ($p === '{pubid}')      { $re .= $pid; }
            elseif ($p === '*')        { $re .= '.*'; }
            elseif ($p === '?')        { $re .= '.'; }
            elseif ($p[0] === '[' && substr($p, -1) === ']' && strlen($p) > 2) { $re .= '[' . preg_quote(substr($p, 1, -1), '/') . ']'; }
            else                       { $re .= preg_quote($p, '/'); }
        }
        $re = '/^' . $re . '$/iu';
    }
    return @preg_match($re, '') === false ? null : $re;
}

/* '' when the pattern is usable, otherwise a message for the person editing the configuration */
function mfn_validate_pattern($pattern)
{
    $pattern = trim((string) $pattern);
    if ($pattern === '')           { return ''; }
    if (strlen($pattern) > 200)    { return 'is longer than 200 characters'; }
    if (!preg_match('//u', $pattern)) { return 'is not valid text'; }
    foreach (mfn_alternatives($pattern) as $alt) {
        if (mfn_regex($alt, 'X-1') === null) { return 'is not a valid pattern (' . $alt . ')'; }
    }
    return '';
}

/* a rule may list several names / patterns separated by commas ("{pubid}, cover, index_*"); a ~regex~ rule is kept whole */
function mfn_alternatives($pattern)
{
    $pattern = trim((string) $pattern);
    if (strlen($pattern) > 2 && $pattern[0] === '~' && substr($pattern, -1) === '~') { return array($pattern); }
    $out = array();
    foreach (explode(',', $pattern) as $a) { $a = trim($a); if ($a !== '') { $out[] = $a; } }
    return $out;
}

/* does this file pass the naming rule of its extension?  (true when its extension has no rule) */
function mfn_file_allowed($filename, $rules, $pub_id)
{
    $info = pathinfo((string) $filename);
    $ext  = isset($info['extension']) ? strtolower($info['extension']) : '';
    if ($ext === '' || empty($rules[$ext])) { return true; }
    foreach (mfn_alternatives($rules[$ext]) as $alt) {
        if ((string) $pub_id === '' && strpos($alt, '{pubid}') !== false) { continue; }
        $re = mfn_regex($alt, $pub_id);
        if ($re === null) { continue; }
        /* the name without its extension ("main"), or the whole file name ("main.tex") */
        if (preg_match($re, $info['filename']) === 1 || preg_match($re, $info['basename']) === 1) { return true; }
    }
    return false;
}

/* Posted rules for one Files cell -> JSON for the database (only extensions that are in the cell and have a pattern), or null */
function mfn_rules_from_post($move_files, $posted)
{
    $keep = array_flip(mfn_ext_list($move_files));
    $out = array();
    if (is_array($posted)) {
        foreach ($posted as $ext => $pat) {
            $ext = strtolower(trim((string) $ext, " ."));
            $pat = is_string($pat) ? trim($pat) : '';
            if ($pat !== '' && isset($keep[$ext])) { $out[$ext] = $pat; }
        }
    }
    return $out ? json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
}

/* every posted pattern -> list of "stage / dept / .ext: problem" messages (empty when all are fine) */
function mfn_validate_post($rules_post)
{
    $bad = array();
    if (is_array($rules_post)) {
        foreach ($rules_post as $stage => $depts) {
            if (!is_array($depts)) { continue; }
            foreach ($depts as $dept => $exts) {
                if (!is_array($exts)) { continue; }
                foreach ($exts as $ext => $pat) {
                    $m = is_string($pat) ? mfn_validate_pattern($pat) : 'is not valid text';
                    if ($m !== '') { $bad[] = "$stage / dept $dept / .$ext: $m"; }
                }
            }
        }
    }
    return $bad;
}

/* "xml: {pubid}; docx: {pubid}_*" for logs and messages */
function mfn_describe($json)
{
    $r = mfn_parse_rules($json); $o = array();
    foreach ($r as $e => $p) { $o[] = $e . ': ' . $p; }
    return implode('; ', $o);
}

/* ---- several Move Files rows feeding the same destination (one row per source department) ----
   $lists[ext] = array of patterns, '' meaning that row has no rule for the extension.  A file passes when ANY row would move it. */
function mfn_add_to_lists(&$lists, $move_files, $rules_json)
{
    $rules = mfn_parse_rules($rules_json);
    foreach (mfn_ext_list($move_files) as $e) { $lists[$e][] = isset($rules[$e]) ? $rules[$e] : ''; }
}
function mfn_any_allowed($filename, $lists, $pub_id)
{
    $ext = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));
    if ($ext === '' || empty($lists[$ext])) { return false; }
    foreach ($lists[$ext] as $pat) {
        if ($pat === '' || mfn_file_allowed($filename, array($ext => $pat), $pub_id)) { return true; }
    }
    return false;
}

/* " named xml: {pubid}_2; pdf: {pubid}_2, {pubid}_print" for a "needs a .xml file" message ('' when there are no rules) */
function mfn_rule_hint($rules)
{
    $d = array();
    foreach ((array) $rules as $e => $p) { $d[] = $e . ': ' . $p; }
    return $d ? ' with a name matching (' . implode('; ', $d) . ')' : '';
}

/* rules for display: {pubid} replaced by the article's real pub id, e.g. array('xml' => 'ABC-1_2, ABC-1_note') */
function mfn_rules_for_display($rules, $pub_id)
{
    $o = array();
    foreach ((array) $rules as $e => $p) { $o[$e] = str_replace('{pubid}', (string) $pub_id, $p); }
    return $o;
}
