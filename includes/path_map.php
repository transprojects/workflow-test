<?php
/* Folder prefixes that are written one way in the article record (the path the production tools / users' PCs use, e.g. J:/JOURNAL)
 * but live somewhere else on the web server (E:/New_Workflow_Files/JOURNAL).  Apache cannot see the J: drive, so any server-side
 * is_dir() / file scan on such a path fails.  Add more pairs to sw_path_map() when another prefix turns up.
 * Slashes and the case of the drive letter do not matter. */
if (!function_exists('sw_path_map')) {
	function sw_path_map()
	{
		return array(
			'J:/' => 'E:/New_Workflow_Files/',
		);
	}
}

/* the folder as written first, then every mapped alternative */
if (!function_exists('sw_path_candidates')) {
	function sw_path_candidates($path)
	{
		$norm = str_replace('\\', '/', $path);
		$out  = array($path);
		foreach (sw_path_map() as $from => $to) {
			if (strncasecmp($norm, $from, strlen($from)) === 0) { $out[] = $to.substr($norm, strlen($from)); }
		}
		return $out;
	}
}

/* the first of those that exists on this server (the path as written when none does) */
if (!function_exists('wf_existing_dir')) {
	function wf_existing_dir($path)
	{
		foreach (sw_path_candidates($path) as $c) { if (@is_dir($c)) { return $c; } }
		return $path;
	}
}
