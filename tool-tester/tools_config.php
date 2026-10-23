<?php
// Registry of QC/validator .exe tools available to the standalone tester UI.
//
// To add a future tool, just append an entry here — no other code changes needed.
// The tester runs "<exe>" "<uploaded_file>" [selected option flags...] and shows
// whatever output files appear in the run folder afterwards (a directory diff),
// so you don't need to know the tool's exact output filename convention up front.
//
// Fields:
//   label         Shown in the tool dropdown.
//   exe           Absolute path to the executable.
//   help          Short description shown under the dropdown.
//   accept_ext    Allowed upload extensions (lowercase, no dot).
//   options       Optional enumerated CLI flags the user can pick from a <select>.
//                 Each option is rendered as "<flag> <chosen_choice>" and only ever
//                 built from these fixed choices — never from free-typed text — so
//                 there is no shell-injection surface here.
//   path_dirs     Optional list of folders to prepend to PATH before running this
//                 tool, for tools that shell out to a helper program by bare name
//                 (e.g. IAPQualityChecker.exe calling "xsltproc"). Missing folders
//                 are silently skipped.

// Folder holding the validator .exe files. Live server uses C:\LAMP\...\workflow-tools;
// on this dev machine it falls back to the workflow-tools git clone.
$tools_dir = 'C:\\LAMP\\Apache\\htdocs\\workflow-tools\\';
if (!is_dir($tools_dir)) {
	$tools_dir = 'F:\\Transforma\\repo\\TRANSFORMA-JOURNAL-WORKFLOW\\workflow-tools\\';
}

return array(

	'peerj_style_validator' => array(
		'label'      => 'PeerJ Style Validator — XMLQualityChecker.exe',
		'exe'        => $tools_dir . 'XMLQualityChecker.exe',
		'help'       => 'PeerJ/CE journal style + DTD validator.',
		'accept_ext' => array('xml'),
		'options'    => array(),
	),

	// TSP XML QC agent (Python tsp_xml_qc packaged as a single exe). Report-only: writes
	// <name>.qc-report.html next to the upload. Exit 0 = no Errors, 1 = Errors present.
	'tsp_style_validator' => array(
		'label'      => 'TSP XML QC Agent - TSPXmlQC.exe',
		'exe'        => $tools_dir . 'TSPXmlQC.exe',
		'help'       => 'TSP XML QC: JATS DTD + PMC style checker + TSP house rules. Export = pagination hand-off gate; Final = XML department output (adds journal-meta / volume / pages checks).',
		'accept_ext' => array('xml'),
		'options'    => array(
			'stage' => array(
				'label'   => 'Stage (--stage)',
				'flag'    => '--stage',
				'choices' => array('export', 'final'),
				'default' => 'export',
			),
		),
	),

	'iap_style_validator' => array(
		'label'      => 'IAP Style Validator — IAPQualityChecker.exe',
		'exe'        => $tools_dir . 'IAPQualityChecker.exe',
		'help'       => 'IAP journal XML validation tool. Force a JATS variant if auto-detection from DOCTYPE is not what you want to test.',
		'accept_ext' => array('xml'),
		'options'    => array(
			'variant' => array(
				'label'   => 'JATS Variant (--variant)',
				'flag'    => '--variant',
				'choices' => array('auto', 'j-stage', 'pmc', 'pe'),
				'default' => 'auto',
			),
		),
		'path_dirs'  => array($tools_dir . 'xsltproc'), // needed for the PMC-variant style check (xsltproc)
	),

	'springer_style_agent' => array(
		'label'      => 'Springer Style Agent - SpringerStyleAgent.exe',
		'exe'        => $tools_dir . 'SpringerStyleAgent.exe',
		'help'       => 'Applies Springer house style to a chapter .docx as Word tracked changes. Outputs <name>_styled.docx plus _report.md/.json.',
		'accept_ext' => array('docx'),
		'options'    => array(),
	),

);
