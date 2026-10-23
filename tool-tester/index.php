<?php
require __DIR__ . '/auth.php';
$tools = include __DIR__ . '/tools_config.php';

// Build the "Recent Runs" list from each run folder's _meta.json.
$recent_runs = array();
$runs_root = __DIR__ . '/runs';
if (is_dir($runs_root)) {
	foreach (scandir($runs_root) as $tool_key) {
		if ($tool_key === '.' || $tool_key === '..') { continue; }
		$tool_dir = $runs_root . '/' . $tool_key;
		if (!is_dir($tool_dir)) { continue; }
		foreach (scandir($tool_dir) as $run_id) {
			if ($run_id === '.' || $run_id === '..') { continue; }
			$meta_path = $tool_dir . '/' . $run_id . '/_meta.json';
			if (!file_exists($meta_path)) { continue; }
			$meta = json_decode(file_get_contents($meta_path), true);
			if (!$meta) { continue; }
			$files = array();
			foreach (scandir($tool_dir . '/' . $run_id) as $f) {
				if ($f === '.' || $f === '..' || $f === '_meta.json' || $f === $meta['input_file']) { continue; }
				$files[] = $f;
			}
			$meta['run_id'] = $run_id;
			$meta['tool_key'] = $tool_key;
			$meta['files'] = $files;
			$recent_runs[] = $meta;
		}
	}
	usort($recent_runs, function ($a, $b) { return strcmp($b['created_at'], $a['created_at']); });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>QC Tool Tester</title>
<style>
	body { font-family: -apple-system, "Segoe UI", Arial, sans-serif; background: #f3f4f7; color: #1b1e24; margin: 0; }
	.wrap { max-width: 1100px; margin: 0 auto; padding: 24px; }
	h1 { font-size: 20px; margin: 0 0 4px; }
	.subtitle { color: #5b6270; font-size: 13px; margin-bottom: 20px; }
	.card { background: #fff; border: 1px solid #dde1e7; border-radius: 10px; padding: 18px 20px; margin-bottom: 20px; }
	label { display: block; font-size: 13px; font-weight: 600; margin: 12px 0 4px; }
	select, input[type=file] { width: 100%; padding: 8px; border: 1px solid #dde1e7; border-radius: 6px; font-size: 13px; box-sizing: border-box; }
	.tool-help { font-size: 12px; color: #5b6270; margin-top: 4px; }
	.options-row { display: flex; gap: 16px; flex-wrap: wrap; }
	.options-row > div { flex: 1; min-width: 180px; }
	.drop-zone { border: 2px dashed #dde1e7; border-radius: 8px; padding: 24px; text-align: center; color: #5b6270; font-size: 13px; margin-top: 6px; cursor: pointer; }
	.drop-zone.drag { border-color: #e85a00; background: #fff6ef; }
	.drop-zone .fname { font-weight: 600; color: #1b1e24; margin-top: 6px; }
	button.run-btn { margin-top: 16px; background: #e85a00; color: #fff; border: none; border-radius: 8px; padding: 10px 20px; font-size: 14px; font-weight: 600; cursor: pointer; }
	button.run-btn:disabled { background: #dde1e7; cursor: not-allowed; }
	.result-box { margin-top: 18px; }
	.status-pill { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
	.status-ok { background: #e7f7f0; color: #1e9e6d; }
	.status-err { background: #fdebee; color: #d6304a; }
	pre.cmd, pre.output { background: #14161b; color: #edeef0; padding: 12px; border-radius: 8px; font-size: 12.5px; overflow-x: auto; white-space: pre-wrap; word-break: break-word; }
	.file-list { list-style: none; padding: 0; margin: 10px 0 0; }
	.file-list li { padding: 8px 10px; border: 1px solid #dde1e7; border-radius: 6px; margin-bottom: 6px; display: flex; justify-content: space-between; align-items: center; font-size: 13px; }
	.file-list a { color: #2f6fed; text-decoration: none; font-weight: 600; }
	.muted { color: #5b6270; font-size: 12px; }
	table.runs { width: 100%; border-collapse: collapse; font-size: 13px; }
	table.runs th, table.runs td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #dde1e7; }
	table.runs th { color: #5b6270; font-size: 11.5px; text-transform: uppercase; }
	.del-btn { background: none; border: 1px solid #dde1e7; border-radius: 6px; color: #d6304a; padding: 3px 9px; font-size: 11.5px; cursor: pointer; }
	.run-files a { margin-right: 8px; }
</style>
</head>
<body>
<div style="position:fixed;top:10px;right:16px;font-size:13px;z-index:9"><a href="logout.php">Log out</a></div>
<div class="wrap">
	<h1>QC Tool Tester</h1>
	<div class="subtitle">Drop a file, run a validator .exe in isolation, inspect the output — no article record or production folder needed.</div>

	<div class="card">
		<label for="tool">Tool</label>
		<select id="tool">
			<?php foreach ($tools as $key => $t) { ?>
			<option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($t['label']); ?></option>
			<?php } ?>
		</select>
		<div class="tool-help" id="toolHelp"></div>

		<div id="optionsHost" class="options-row"></div>

		<label>File</label>
		<div class="drop-zone" id="dropZone">
			Drag &amp; drop a file here, or click to choose one
			<div class="fname" id="fname"></div>
		</div>
		<input type="file" id="fileInput" style="display:none;">

		<button class="run-btn" id="runBtn" disabled>Run</button>

		<div class="result-box" id="resultBox" style="display:none;">
			<div id="statusPill"></div>
			<p class="muted">Command</p>
			<pre class="cmd" id="cmdOut"></pre>
			<p class="muted">Tool output</p>
			<pre class="output" id="rawOut"></pre>
			<p class="muted">Result files</p>
			<ul class="file-list" id="fileListOut"></ul>
		</div>
	</div>

	<div class="card">
		<h2 style="font-size:15px;margin:0 0 10px;">Recent Runs</h2>
		<?php if (empty($recent_runs)) { ?>
			<p class="muted">No runs yet.</p>
		<?php } else { ?>
		<table class="runs">
			<thead><tr><th>When</th><th>Tool</th><th>Input</th><th>Output files</th><th></th></tr></thead>
			<tbody id="runsBody">
				<?php foreach ($recent_runs as $r) { ?>
				<tr data-tool="<?php echo htmlspecialchars($r['tool_key']); ?>" data-run="<?php echo htmlspecialchars($r['run_id']); ?>">
					<td><?php echo htmlspecialchars($r['created_at']); ?></td>
					<td><?php echo htmlspecialchars($r['tool_label']); ?></td>
					<td><?php echo htmlspecialchars($r['input_file']); ?></td>
					<td class="run-files">
						<?php foreach ($r['files'] as $f) { ?>
						<a href="view_file.php?tool=<?php echo urlencode($r['tool_key']); ?>&run=<?php echo urlencode($r['run_id']); ?>&file=<?php echo urlencode($f); ?>" target="_blank"><?php echo htmlspecialchars($f); ?></a>
						<?php } if (empty($r['files'])) { echo '<span class="muted">none</span>'; } ?>
					</td>
					<td><button class="del-btn" onclick="deleteRun('<?php echo htmlspecialchars($r['tool_key']); ?>','<?php echo htmlspecialchars($r['run_id']); ?>', this)">Delete</button></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
		<?php } ?>
	</div>
</div>

<script>
var TOOLS = <?php echo json_encode($tools); ?>;
var selectedFile = null;

var toolSelect = document.getElementById('tool');
var optionsHost = document.getElementById('optionsHost');
var toolHelp = document.getElementById('toolHelp');
var dropZone = document.getElementById('dropZone');
var fileInput = document.getElementById('fileInput');
var fnameEl = document.getElementById('fname');
var runBtn = document.getElementById('runBtn');

function renderToolOptions() {
	var tool = TOOLS[toolSelect.value];
	toolHelp.textContent = tool.help || '';
	optionsHost.innerHTML = '';
	var opts = tool.options || {};
	Object.keys(opts).forEach(function (key) {
		var opt = opts[key];
		var div = document.createElement('div');
		var label = document.createElement('label');
		label.textContent = opt.label;
		var sel = document.createElement('select');
		sel.id = 'opt_' + key;
		opt.choices.forEach(function (choice) {
			var o = document.createElement('option');
			o.value = choice;
			o.textContent = choice;
			if (choice === opt.default) o.selected = true;
			sel.appendChild(o);
		});
		div.appendChild(label);
		div.appendChild(sel);
		optionsHost.appendChild(div);
	});
}
toolSelect.addEventListener('change', renderToolOptions);
renderToolOptions();

dropZone.addEventListener('click', function () { fileInput.click(); });
dropZone.addEventListener('dragover', function (e) { e.preventDefault(); dropZone.classList.add('drag'); });
dropZone.addEventListener('dragleave', function () { dropZone.classList.remove('drag'); });
dropZone.addEventListener('drop', function (e) {
	e.preventDefault();
	dropZone.classList.remove('drag');
	if (e.dataTransfer.files.length) setFile(e.dataTransfer.files[0]);
});
fileInput.addEventListener('change', function () {
	if (fileInput.files.length) setFile(fileInput.files[0]);
});
function setFile(f) {
	selectedFile = f;
	fnameEl.textContent = f.name + ' (' + Math.round(f.size / 1024) + ' KB)';
	runBtn.disabled = false;
}

runBtn.addEventListener('click', function () {
	if (!selectedFile) return;
	runBtn.disabled = true;
	runBtn.textContent = 'Running...';

	var fd = new FormData();
	fd.append('tool', toolSelect.value);
	fd.append('upload_file', selectedFile);
	var tool = TOOLS[toolSelect.value];
	Object.keys(tool.options || {}).forEach(function (key) {
		var sel = document.getElementById('opt_' + key);
		if (sel) fd.append('opt_' + key, sel.value);
	});

	fetch('run.php', { method: 'POST', body: fd })
		.then(function (r) { return r.json(); })
		.then(function (data) {
			runBtn.disabled = false;
			runBtn.textContent = 'Run';
			showResult(data);
			if (data.ok) location.reload(); // refresh Recent Runs list
		})
		.catch(function (err) {
			runBtn.disabled = false;
			runBtn.textContent = 'Run';
			alert('Request failed: ' + err);
		});
});

function showResult(data) {
	var box = document.getElementById('resultBox');
	box.style.display = 'block';
	var pill = document.getElementById('statusPill');
	if (!data.ok) {
		pill.innerHTML = '<span class="status-pill status-err">ERROR</span> ' + (data.error || 'Unknown error');
		document.getElementById('cmdOut').textContent = '';
		document.getElementById('rawOut').textContent = '';
		document.getElementById('fileListOut').innerHTML = '';
		return;
	}
	pill.innerHTML = '<span class="status-pill status-ok">DONE</span> Run ' + data.run_id;
	document.getElementById('cmdOut').textContent = data.command;
	document.getElementById('rawOut').textContent = data.output || '(no output on stdout/stderr)';
	var list = document.getElementById('fileListOut');
	list.innerHTML = '';
	if (!data.result_files.length) {
		list.innerHTML = '<li class="muted">No new files were produced.</li>';
	}
	data.result_files.forEach(function (f) {
		var li = document.createElement('li');
		li.innerHTML = '<a href="' + f.url + '" target="_blank">' + f.name + '</a><span class="muted">' + Math.round(f.size / 1024) + ' KB</span>';
		list.appendChild(li);
	});
}

function deleteRun(tool, run, btn) {
	if (!confirm('Delete this run and its files?')) return;
	var fd = new FormData();
	fd.append('tool', tool);
	fd.append('run', run);
	fetch('delete_run.php', { method: 'POST', body: fd })
		.then(function (r) { return r.json(); })
		.then(function (data) {
			if (data.ok) {
				btn.closest('tr').remove();
			} else {
				alert(data.error || 'Delete failed.');
			}
		});
}
</script>
</body>
</html>
