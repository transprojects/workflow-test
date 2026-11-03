<?php
include 'includes/includes.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>PDF Comparison</title>

    <?php include('includes/title.php'); ?>

    <?php echo styles(); ?>

    <style>

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(15,23,42,0.45);
            backdrop-filter: blur(2px);
            z-index: 1000;
            display: none;
        }

        .loader-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1001;
            display: none;
            text-align: center;
            color: #fff;
        }

        .loader {
            border: 4px solid rgba(255,255,255,.35);
            border-top: 4px solid #f47c20;
            border-radius: 50%;
            width: 46px;
            height: 46px;
            animation: spin 1s linear infinite;
            margin: auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* ===== PDF compare - modern layout ===== */

        .cmp-page {
            --cmp-accent: #f47c20;
            --cmp-accent-dark: #d9650c;
            --cmp-ink: #1b2a41;
            --cmp-muted: #64748b;
            --cmp-line: #e6eaf0;
            --cmp-soft: #f6f8fb;
            --cmp-ok: #1e8449;
            --cmp-bad: #c0392b;
            max-width: 1180px;
            margin: 0 auto;
            padding: 8px 20px 60px;
            color: var(--cmp-ink);
        }

        .cmp-hero {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 24px;
            flex-wrap: wrap;
            padding: 28px 32px;
            border-radius: 20px;
            color: #fff;
            background: linear-gradient(120deg, #1b2a41 0%, #2c4a7a 60%, #f47c20 140%);
            box-shadow: 0 14px 34px rgba(27,42,65,.22);
        }

        .cmp-hero h1 {
            margin: 0 0 6px;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -.2px;
        }

        .cmp-hero p {
            margin: 0;
            max-width: 520px;
            font-size: 14px;
            line-height: 1.55;
            color: rgba(255,255,255,.82);
        }

        .cmp-steps {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .cmp-steps li {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 14px 7px 8px;
            border-radius: 999px;
            background: rgba(255,255,255,.14);
            font-size: 13px;
            white-space: nowrap;
        }

        .cmp-steps b {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #fff;
            color: #1b2a41;
            font-size: 12px;
        }

        .cmp-card {
            margin-top: 22px;
            padding: 28px 32px 30px;
            background: #fff;
            border: 1px solid var(--cmp-line);
            border-radius: 20px;
            box-shadow: 0 8px 28px rgba(27,42,65,.07);
        }

        .cmp-naming {
            margin-top: 22px;
            padding: 20px 24px;
            border-radius: 16px;
            background: #fff8f1;
            border: 1px solid #fbd9bb;
        }

        .cmp-naming h3 {
            margin: 0 0 10px;
            font-size: 15px;
            font-weight: 700;
            color: #9a4a08;
        }

        .cmp-naming-grid {
            display: grid;
            grid-template-columns: 1.1fr 1fr 1fr;
            gap: 18px;
            font-size: 13.5px;
            line-height: 1.6;
        }

        .cmp-naming ul {
            margin: 0;
            padding-left: 18px;
        }

        .cmp-naming .cmp-ttl {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .cmp-naming .good .cmp-ttl { color: var(--cmp-ok); }
        .cmp-naming .bad .cmp-ttl { color: var(--cmp-bad); }

        .cmp-naming code,
        .cmp-hint code {
            padding: 1px 7px;
            border-radius: 6px;
            background: #fff;
            border: 1px solid #f2cfae;
            color: #7a3a05;
            font-size: 12.5px;
            word-break: break-all;
        }

        .cmp-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
        }

        .cmp-filecard {
            padding: 18px;
            border: 1px solid var(--cmp-line);
            border-radius: 16px;
            background: var(--cmp-soft);
        }

        .cmp-label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            font-size: 15px;
            font-weight: 700;
        }

        .cmp-label .cmp-no {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            border-radius: 8px;
            background: var(--cmp-accent);
            color: #fff;
            font-size: 13px;
        }

        .cmp-label .req { color: var(--cmp-bad); }

        .cmp-drop {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 132px;
            padding: 18px;
            text-align: center;
            border: 2px dashed #c3cddb;
            border-radius: 14px;
            background: #fff;
            transition: border-color .15s, background .15s, box-shadow .15s;
        }

        .cmp-drop:hover,
        .cmp-drop.drag {
            border-color: var(--cmp-accent);
            background: #fff8f1;
            box-shadow: 0 0 0 4px rgba(244,124,32,.12);
        }

        .cmp-drop.has-file {
            border-style: solid;
            border-color: var(--cmp-ok);
            background: #f2faf5;
        }

        .cmp-drop input[type=file] {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .cmp-drop svg {
            width: 34px;
            height: 34px;
            color: var(--cmp-accent);
        }

        .cmp-drop.has-file svg { color: var(--cmp-ok); }

        .cmp-drop .cmp-drop-main { font-weight: 600; font-size: 14px; }
        .cmp-drop .cmp-drop-sub { font-size: 12.5px; color: var(--cmp-muted); }

        .selected-file-name {
            margin-top: 10px;
            font-size: 13px;
            font-weight: 600;
            color: var(--cmp-ink);
            word-break: break-all;
        }

        .cmp-status {
            margin-top: 6px;
            font-size: 13px;
            font-weight: 600;
        }

        .cmp-status.ok { color: var(--cmp-ok); }
        .cmp-status.warn { color: #b9770e; }
        .cmp-status.bad { color: var(--cmp-bad); }

        .cmp-hint {
            margin-top: 10px;
            font-size: 12.5px;
            line-height: 1.5;
            color: var(--cmp-muted);
        }

        #pdf-file-details,
        #pdf-article-check {
            margin-top: 22px;
            padding: 16px 18px;
            border: 1px solid var(--cmp-line);
            border-radius: 14px;
            background: #fff;
        }

        #pdf-file-details table,
        #pdf-article-check table {
            width: 100%;
            margin: 0;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13px;
        }

        #pdf-file-details th,
        #pdf-article-check th {
            padding: 9px 12px;
            background: var(--cmp-soft);
            color: var(--cmp-muted);
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
            border: 0;
            border-bottom: 1px solid var(--cmp-line);
        }

        #pdf-file-details td,
        #pdf-article-check td {
            padding: 10px 12px;
            border: 0;
            border-bottom: 1px solid var(--cmp-line);
            vertical-align: top;
        }

        #pdf-file-details .table-responsive,
        #pdf-article-check .table-responsive {
            border: 1px solid var(--cmp-line);
            border-radius: 10px;
            margin-bottom: 8px;
        }

        .cmp-actions {
            display: flex;
            justify-content: center;
            gap: 14px;
            margin-top: 28px;
        }

        .cmp-btn {
            min-width: 150px;
            padding: 13px 26px;
            border-radius: 12px;
            border: 1.5px solid var(--cmp-accent);
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: transform .1s, box-shadow .15s, background .15s;
        }

        .cmp-btn:active { transform: translateY(1px); }

        .cmp-btn-ghost {
            background: #fff;
            color: var(--cmp-accent);
        }

        .cmp-btn-ghost:hover { background: #fff8f1; }

        .cmp-btn-primary {
            background: var(--cmp-accent);
            color: #fff;
            box-shadow: 0 8px 18px rgba(244,124,32,.32);
        }

        .cmp-btn-primary:hover { background: var(--cmp-accent-dark); }

        .cmp-btn:disabled {
            opacity: .5;
            cursor: not-allowed;
            box-shadow: none;
        }

        .cmp-filing { margin-bottom: 16px; padding: 14px 18px; border-radius: 12px; font-size: 13.5px; line-height: 1.55; border: 1px solid; }
        .cmp-filing.ok { background: #f2faf5; border-color: #bfe3cc; color: #17603a; }
        .cmp-filing.bad { background: #fdf1ef; border-color: #f2c6c0; color: #8e2a20; }
        .cmp-filing b { display: block; font-size: 14.5px; margin-bottom: 2px; }
        .cmp-filing-links { margin-top: 8px; }
        .cmp-filing-links a, .cmp-filing a.cmp-lnk { display: inline-block; margin: 2px 8px 2px 0; padding: 5px 12px; border-radius: 8px; border: 1px solid #f2cfae; background: #fff8f1; color: #9a4a08; font-size: 12.5px; font-weight: 600; text-decoration: none; }
        .cmp-filing-links a:hover { background: #ffeedd; }

        /* progress */

        .pdf-progress-wrapper {
            display: none;
            margin-top: 22px;
            padding: 24px 28px;
            background: #fff;
            border-radius: 20px;
            border: 1px solid #e6eaf0;
            box-shadow: 0 8px 28px rgba(27,42,65,.07);
        }

        .pdf-progress-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 14px;
        }

        .pdf-progress-bar {
            width: 100%;
            height: 14px;
            background: #eef1f6;
            border-radius: 20px;
            overflow: hidden;
        }

        .pdf-progress-fill {
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, #f47c20, #ffb066);
            transition: width .35s ease;
            border-radius: 20px;
        }

        .pdf-progress-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-top: 12px;
        }

        .pdf-progress-text { font-size: 14px; color: #64748b; }
        .pdf-progress-percent { font-size: 16px; font-weight: 700; color: #1b2a41; }

        /* result */

        .pdf-comparison-result {
            display: none;
            margin-top: 22px;
            padding: 22px 24px 24px;
            background: #fff;
            border: 1px solid #e6eaf0;
            border-radius: 20px;
            box-shadow: 0 8px 28px rgba(27,42,65,.07);
        }

        .pdf-result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .pdf-result-header h4 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }

        .pdf-result-header .btn {
            border-radius: 10px;
            padding: 9px 18px;
            font-weight: 600;
        }

        .pdf-result-frame {
            width: 100%;
            height: 1000px;
            border: 1px solid #e6eaf0;
            border-radius: 12px;
            background: #fff;
        }

        @media (max-width: 900px) {
            .cmp-grid { grid-template-columns: 1fr; }
            .cmp-naming-grid { grid-template-columns: 1fr; }
            .cmp-card { padding: 20px 16px 24px; }
            .cmp-hero { padding: 22px 20px; }
        }

        @media (max-width: 768px) {
            .pdf-result-frame { height: 700px; }
            .cmp-actions { flex-direction: column-reverse; }
            .cmp-btn { width: 100%; }
        }


    </style>

</head>


<body>

<div id="overlay"
     class="overlay">
</div>


<div id="loader"
     class="loader-container">

    <div class="loader"></div>

    <div style="margin-top:10px;">
        Processing...
    </div>

</div>


<div class="wholeouter">


    <?php include("includes/top-header.php"); ?>


    <div class="container-fluid"
         style="justify-content: space-between;">

        <div class="row pt-4 d-flex align-items-center flex-wrap">

            <div class="col-8 col-lg-10">

                <p class="articleheading ms-4">
                    PDF Comparison
                </p>

            </div>


            <div class="col-4 col-lg-2">

                <div class="row d-flex justify-content-end pe-2">

                    <div class="col-3 articlebtns">

                        <a href="javascript:history.back();">

                            <div class="back_icon">

                                <img
                                    src="<?php echo $_COOKIE['set_url']; ?>/assets/images/backarrow.svg"
                                >

                            </div>

                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>



    <div class="cmp-page">


        <!-- HERO -->

        <div class="cmp-hero">

            <div>
                <h1>Compare two PDFs</h1>
                <p>Upload two versions of a proof. Every page that differs is highlighted, and the report is filed in the article's PRQC folder.</p>
            </div>

            <ul class="cmp-steps">
                <li><b>1</b> Choose files</li>
                <li><b>2</b> Check article</li>
                <li><b>3</b> Compare</li>
                <li><b>4</b> Download report</li>
                <li style="padding-left:14px"><a href="<?php echo $_COOKIE['set_url']; ?>/comparepdflog" style="color:#fff;text-decoration:underline">Filing log</a></li>
            </ul>

        </div>


        <!-- HOW TO NAME THE FILES -->

        <div class="cmp-naming">

            <h3>How to name the files</h3>

            <div class="cmp-naming-grid">

                <div>
                    Name each PDF with the article's <b>Pub ID</b> (the ID shown in the workflow).
                    The report is then saved automatically to that article's PRQC folder.
                    At least <b>one</b> of the two files must match; if both match, they should be the <b>same article</b>.
                </div>

                <div class="good">
                    <div class="cmp-ttl">&#10003; These work</div>
                    <ul>
                        <li><code>TSP_CMC_86406.pdf</code></li>
                        <li><code>TSP_CMC_86406-old.pdf</code></li>
                        <li><code>TSP_CMC_86406 (1).pdf</code></li>
                    </ul>
                </div>

                <div class="bad">
                    <div class="cmp-ttl">&#10007; These are not found</div>
                    <ul>
                        <li><code>final_v2.pdf</code></li>
                        <li><code>scan0012.pdf</code></li>
                        <li><code>TSP-CMC-86406.pdf</code> (wrong separators)</li>
                    </ul>
                </div>

            </div>

        </div>


        <!-- UPLOAD FORM -->

        <form id="pdf-compare"
              class="cmp-card"
              enctype="multipart/form-data">

            <div class="cmp-grid">

                <!-- FILE 1 -->

                <div class="cmp-filecard">

                    <div class="cmp-label">
                        <span class="cmp-no">1</span>
                        Select First PDF <span class="req">*</span>
                    </div>

                    <div class="cmp-drop" id="file1-drop">

                        <input
                            type="file"
                            name="file1"
                            id="file1"
                            accept=".pdf,application/pdf"
                            required
                        >

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M12 17v-6"/><path d="m9.5 13.5 2.5-2.5 2.5 2.5"/></svg>

                        <div class="cmp-drop-main">Click to choose, or drop the PDF here</div>
                        <div class="cmp-drop-sub">PDF only</div>

                    </div>

                    <div id="file1-name" class="selected-file-name"></div>
                    <div id="file1-status" class="cmp-status"></div>

                    <div class="cmp-hint">
                        Name it with the Pub ID, e.g. <code>TSP_CMC_86406.pdf</code>
                    </div>

                </div>


                <!-- FILE 2 -->

                <div class="cmp-filecard">

                    <div class="cmp-label">
                        <span class="cmp-no">2</span>
                        Select Second PDF <span class="req">*</span>
                    </div>

                    <div class="cmp-drop" id="file2-drop">

                        <input
                            type="file"
                            name="file2"
                            id="file2"
                            accept=".pdf,application/pdf"
                            required
                        >

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M12 17v-6"/><path d="m9.5 13.5 2.5-2.5 2.5 2.5"/></svg>

                        <div class="cmp-drop-main">Click to choose, or drop the PDF here</div>
                        <div class="cmp-drop-sub">PDF only</div>

                    </div>

                    <div id="file2-name" class="selected-file-name"></div>
                    <div id="file2-status" class="cmp-status"></div>

                    <div class="cmp-hint">
                        Same Pub ID, with an optional ending, e.g. <code>TSP_CMC_86406-old.pdf</code>
                    </div>

                </div>

            </div>


            <?php
            /* server upload limits, so the page can warn before a 250 MB upload is sent */
            function cmp_ini_bytes($v)
            {
                $v = trim((string)$v);
                $n = (float)$v;
                switch (strtolower(substr($v, -1))) {
                    case 'g': $n *= 1024;
                    case 'm': $n *= 1024;
                    case 'k': $n *= 1024;
                }
                return (int)$n;
            }
            ?>
            <script>
                window.cmpLimits = {
                    file: <?php echo cmp_ini_bytes(ini_get('upload_max_filesize')); ?>,
                    post: <?php echo cmp_ini_bytes(ini_get('post_max_size')); ?>
                };
            </script>

            <div id="pdf-file-details" style="display:none;"></div>
            <div id="pdf-article-check" style="display:none;"></div>


            <div class="cmp-actions">

                <button
                    type="button"
                    class="cmp-btn cmp-btn-ghost"
                    onclick="history.back();">
                    Cancel
                </button>

                <button
                    type="submit"
                    id="compare-button"
                    class="cmp-btn cmp-btn-primary">
                    Compare
                </button>

            </div>

        </form>


        <!-- PROGRESS -->

        <div
            id="pdf-progress"
            class="pdf-progress-wrapper">

            <div class="pdf-progress-title">PDF Comparison Progress</div>

            <div class="pdf-progress-bar">
                <div id="pdf-progress-fill" class="pdf-progress-fill"></div>
            </div>

            <div class="pdf-progress-info">
                <div id="pdf-progress-text" class="pdf-progress-text">Preparing...</div>
                <div id="pdf-progress-percent" class="pdf-progress-percent">0%</div>
            </div>

        </div>


        <!-- RESULT -->

        <div
            id="pdf-comparison-result"
            class="pdf-comparison-result">

            <div class="pdf-result-header">

                <h4>PDF Comparison Result</h4>

                <div class="d-flex" style="gap:10px;">

                    <a
                        id="pdf-open-report"
                        href="#"
                        target="_blank"
                        class="btn btn-primary">
                        Open Full Report
                    </a>

                    <button
                        type="button"
                        id="pdf-download-report"
                        class="btn btn-outline-primary">
                        Download Report
                    </button>

                </div>

            </div>

            <div id="pdf-filing-banner"></div>

            <iframe
                id="pdf-result-frame"
                class="pdf-result-frame">
            </iframe>

        </div>

    </div>



    <?php echo scripts(); ?>

    <?php echo footer(); ?>


</div>



<script>

(function () {


    const form =
        document.getElementById(
            'pdf-compare'
        );


    const file1 =
        document.getElementById(
            'file1'
        );


    const file2 =
        document.getElementById(
            'file2'
        );


    const file1Name =
        document.getElementById(
            'file1-name'
        );


    const file2Name =
        document.getElementById(
            'file2-name'
        );


    const compareButton =
        document.getElementById(
            'compare-button'
        );


    const progressBox =
        document.getElementById(
            'pdf-progress'
        );


    const progressFill =
        document.getElementById(
            'pdf-progress-fill'
        );


    const progressText =
        document.getElementById(
            'pdf-progress-text'
        );


    const progressPercent =
        document.getElementById(
            'pdf-progress-percent'
        );


    const resultBox =
        document.getElementById(
            'pdf-comparison-result'
        );


    const resultFrame =
        document.getElementById(
            'pdf-result-frame'
        );


    const openReport =
        document.getElementById(
            'pdf-open-report'
        );


    const downloadReport =
        document.getElementById(
            'pdf-download-report'
        );


    let currentJobId = null;


    const overlay =
        document.getElementById(
            'overlay'
        );


    const loader =
        document.getElementById(
            'loader'
        );



    /*
    ========================================
    Show selected filenames
    ========================================
    */


    file1.addEventListener(
        'change',
        function () {

            if (this.files.length > 0) {

                file1Name.textContent =
                    this.files[0].name;

            } else {

                file1Name.textContent = '';

            }

        }
    );


    file2.addEventListener(
        'change',
        function () {

            if (this.files.length > 0) {

                file2Name.textContent =
                    this.files[0].name;

            } else {

                file2Name.textContent = '';

            }

        }
    );



    /*
    ========================================
    Progress Function
    ========================================
    */


    /*
    ========================================
    File details (name, size, last modified),
    server-limit warnings and time taken
    ========================================
    */

    const cmpLimits = window.cmpLimits || {};
    const detailsBox = document.getElementById('pdf-file-details');
    let cmpStart = 0;
    let cmpElapsed = '';

    function fmtSize(b) {
        if (b >= 1073741824) { return (b / 1073741824).toFixed(2) + ' GB'; }
        if (b >= 1048576) { return (b / 1048576).toFixed(2) + ' MB'; }
        if (b >= 1024) { return (b / 1024).toFixed(1) + ' KB'; }
        return b + ' bytes';
    }

    function cmpEsc(s) {
        return String(s).replace(/[&<>"]/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
        });
    }

    function fileProblems() {
        const out = [];
        let total = 0;
        [file1, file2].forEach(function (f, i) {
            if (!f.files.length) { return; }
            const x = f.files[0];
            total += x.size;
            if (!/\.pdf$/i.test(x.name)) {
                out.push('File ' + (i + 1) + ' is not a PDF.');
            }
            if (cmpLimits.file > 0 && x.size > cmpLimits.file) {
                out.push('File ' + (i + 1) + ' (' + fmtSize(x.size) + ') is larger than the server limit of ' + fmtSize(cmpLimits.file) + ' per file.');
            }
        });
        if (cmpLimits.post > 0 && total > cmpLimits.post) {
            out.push('Both files together (' + fmtSize(total) + ') are larger than the server limit of ' + fmtSize(cmpLimits.post) + ' per upload.');
        }
        return out;
    }

    function renderDetails() {
        if (!file1.files.length && !file2.files.length) {
            detailsBox.style.display = 'none';
            return;
        }
        let rows = '';
        let total = 0;
        [file1, file2].forEach(function (f, i) {
            if (!f.files.length) {
                rows += '<tr><td>File ' + (i + 1) + '</td><td colspan="3" style="color:#888">Not selected</td></tr>';
                return;
            }
            const x = f.files[0];
            total += x.size;
            rows += '<tr><td>File ' + (i + 1) + '</td><td style="word-break:break-all">' + cmpEsc(x.name) + '</td><td style="white-space:nowrap">' +
                fmtSize(x.size) + '</td><td style="white-space:nowrap">' +
                (x.lastModified ? new Date(x.lastModified).toLocaleString() : '') + '</td></tr>';
        });
        const problems = fileProblems();
        let html = '<div class="table-responsive"><table class="table table-sm table-bordered mb-2" style="background:#fff">' +
            '<thead><tr><th>#</th><th>File name</th><th>Size</th><th>Last modified</th></tr></thead><tbody>' + rows + '</tbody></table></div>' +
            '<div>Total size: <b>' + fmtSize(total) + '</b>';
        if (cmpLimits.file > 0) {
            html += ' &nbsp;|&nbsp; Server limit: ' + fmtSize(cmpLimits.file) + ' per file, ' + (cmpLimits.post > 0 ? fmtSize(cmpLimits.post) : 'no limit') + ' per upload';
        }
        if (cmpElapsed) {
            html += ' &nbsp;|&nbsp; ' + cmpElapsed;
        }
        html += '</div>';
        problems.forEach(function (p) {
            html += '<div style="color:#c0392b;margin-top:4px">' + cmpEsc(p) + '</div>';
        });
        detailsBox.innerHTML = html;
        detailsBox.style.display = 'block';
        compareButton.disabled = problems.length > 0;
    }

    /* look the selected file names up in the workflow (live / archive): status, stage and the PRQC folder the report would go to */
    const checkBox = document.getElementById('pdf-article-check');
    let checkSeq = 0;

    function checkArticles() {
        const names = [];
        [file1, file2].forEach(function (f) {
            if (f.files.length) { names.push(f.files[0].name); }
        });
        if (!names.length) {
            checkBox.style.display = 'none';
            return;
        }
        setFileStatus(1, null); setFileStatus(2, null);
        const seq = ++checkSeq;
        checkBox.style.display = 'block';
        checkBox.innerHTML = '<span style="color:#888">Checking the workflow for these files...</span>';
        const fd = new FormData();
        names.forEach(function (n) { fd.append('names[]', n); });
        fetch('compare-pdf-check.php', { method: 'POST', body: fd, cache: 'no-store' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (seq !== checkSeq) { return; }
                if (!d.ok) { throw new Error(d.error || 'Check failed.'); }
                let pos = 0;
                [file1, file2].forEach(function (f, i) {
                    if (f.files.length) { setFileStatus(i + 1, d.items[pos++]); }
                });
                const colour = { 'In progress': '#1e8449', 'On hold': '#b9770e', 'Delivered': '#2e6da4', 'Archived': '#6c757d', 'Withdrawn': '#c0392b', 'Not found': '#c0392b' };
                let html = '<div style="font-weight:600;margin-bottom:6px">Article check (live / archive)</div>' +
                    '<div class="table-responsive"><table class="table table-sm table-bordered mb-2" style="background:#fff">' +
                    '<thead><tr><th>File</th><th>Article</th><th>Found in</th><th>Current stage</th><th>Workflow status</th><th>Report will be saved to</th></tr></thead><tbody>';
                d.items.forEach(function (it) {
                    const found = it.found_in !== '';
                    html += '<tr><td style="word-break:break-all">' + cmpEsc(it.input) + '</td>' +
                        '<td><b style="color:' + (colour[it.state] || '#333') + '">' + cmpEsc(it.state) + '</b>' + (found ? '<br>' + cmpEsc(it.pub_id) : '') + '</td>' +
                        '<td>' + (found ? cmpEsc(it.found_in) : '-') + '</td>' +
                        '<td>' + (found ? cmpEsc(it.stage || '-') : '-') + '</td>' +
                        '<td>' + (found ? cmpEsc(it.status || '-') : '-') + '</td>' +
                        '<td style="word-break:break-all">' + (it.can_save
                            ? '<span style="color:#1e8449">Ready</span><br>' + cmpEsc(it.prqc_path) + (it.folder_exists ? '' : '<br><small>(folder will be created)</small>') +
                                (it.work_link ? '<br><a href="' + cmpEsc(it.work_link) + '" target="_blank">Open work folder</a>' + (it.source_link ? ' &nbsp;|&nbsp; <a href="' + cmpEsc(it.source_link) + '" target="_blank">Open source folder</a>' : '') : '')
                            : '<span style="color:#c0392b">Will not be filed</span>' + (it.prqc_path ? '<br><small>' + cmpEsc(it.prqc_path) + '</small>' : '')) + '</td></tr>';
                    const extra = it.notes.slice();
                    if (it.suggestions && it.suggestions.length) {
                        extra.push('Did you mean: ' + it.suggestions.join(', ') + '?');
                    }
                    if (extra.length) {
                        html += '<tr><td colspan="6" style="font-size:13px;color:#555">' + extra.map(cmpEsc).join('<br>') + '</td></tr>';
                    }
                });
                checkBox.innerHTML = html + '</tbody></table></div>';
            })
            .catch(function (e) {
                if (seq === checkSeq) {
                    checkBox.innerHTML = '<span style="color:#c0392b">Could not check the files in the workflow: ' + cmpEsc(e.message) + '</span>';
                }
            });
    }

    const filingBanner = document.getElementById('pdf-filing-banner');

    /* result of the "file the report in the work folder" step, with Explorer links like Start Work (Work / Source) */
    function showFiling(saved, message, workLink, sourceLink) {
        let h = '<div class="cmp-filing ' + (saved ? 'ok' : 'bad') + '"><b>' +
            (saved ? 'Report saved to the work folder' : 'Report downloaded, but NOT saved to the work folder') + '</b>' + cmpEsc(message);
        if (workLink || sourceLink) {
            h += '<div class="cmp-filing-links">' +
                (workLink ? '<a href="' + cmpEsc(workLink) + '" target="_blank">Open work folder (PRQC)</a>' : '') +
                (sourceLink ? '<a href="' + cmpEsc(sourceLink) + '" target="_blank">Open source folder</a>' : '') + '</div>';
        }
        h += '<div style="margin-top:6px"><a class="cmp-lnk" href="comparepdflog">View filing log</a></div></div>';
        filingBanner.innerHTML = h;

    }

    /* tick / warning under each file box once the workflow check is back */
    function setFileStatus(idx, item) {
        const el = document.getElementById('file' + idx + '-status');
        if (!el) { return; }
        if (!item) { el.className = 'cmp-status'; el.textContent = ''; return; }
        if (item.found_in === '') {
            el.className = 'cmp-status bad';
            el.textContent = '\u2717 Not matched to an article - rename it with the Pub ID';
        } else if (item.found_in === 'Archive') {
            el.className = 'cmp-status warn';
            el.textContent = '\u2713 ' + item.pub_id + ' - archived (report will not be filed)';
        } else {
            el.className = 'cmp-status ok';
            el.textContent = '\u2713 ' + item.pub_id + ' - ' + item.state + ', stage ' + item.stage;
        }
    }

    [file1, file2].forEach(function (f, i) {
        const box = f.closest('.cmp-drop');
        f.addEventListener('change', function () { box.classList.toggle('has-file', f.files.length > 0); });
        ['dragenter', 'dragover'].forEach(function (ev) { f.addEventListener(ev, function () { box.classList.add('drag'); }); });
        ['dragleave', 'drop'].forEach(function (ev) { f.addEventListener(ev, function () { box.classList.remove('drag'); }); });
    });

    function showDone() {
        const s = Math.round((Date.now() - cmpStart) / 1000);
        cmpElapsed = 'Compared in ' + Math.floor(s / 60) + ' min ' + (s % 60) + ' sec';
        renderDetails();
        return '(' + cmpElapsed + ')';
    }

    file1.addEventListener('change', function () { cmpElapsed = ''; renderDetails(); checkArticles(); });
    file2.addEventListener('change', function () { cmpElapsed = ''; renderDetails(); checkArticles(); });

    function setProgress(
        percent,
        message
    ) {

        percent = Math.max(
            0,
            Math.min(
                100,
                percent
            )
        );


        progressFill.style.width =
            percent + '%';


        progressPercent.textContent =
            percent + '%';


        progressText.textContent =
            message;

    }



    /*
    ========================================
    Sleep
    ========================================
    */


    function sleep(ms) {

        return new Promise(
            function(resolve) {

                setTimeout(
                    resolve,
                    ms
                );

            }
        );

    }



    /*
    ========================================
    AJAX FORM SUBMISSION
    ========================================
    */


    form.addEventListener(
        'submit',
        async function(event) {


            event.preventDefault();



            /*
            ----------------------------------------
            Validate files
            ----------------------------------------
            */


            if (
                !file1.files.length ||
                !file2.files.length
            ) {

                alert(
                    'Please select both PDF files.'
                );

                return;

            }



            /*
            ----------------------------------------
            Reset previous result
            ----------------------------------------
            */


            resultBox.style.display =
                'none';


            resultFrame.src =
                'about:blank';


            openReport.href =
                '#';


            currentJobId =
                null;



            /*
            ----------------------------------------
            Disable button
            ----------------------------------------
            */


            compareButton.disabled =
                true;


            compareButton.dataset.oldText =
                compareButton.innerHTML;


            compareButton.innerHTML =
                'Comparing...';



            /*
            ----------------------------------------
            Show progress
            ----------------------------------------
            */


            progressBox.style.display =
                'block';


            overlay.style.display =
                'block';


            loader.style.display =
                'block';


            setProgress(
                0,
                'Preparing PDF upload...'
            );



            /*
            ========================================
            Create FormData
            ========================================
            */


            const formData =
                new FormData();


            formData.append(
                'file1',
                file1.files[0]
            );


            formData.append(
                'file2',
                file2.files[0]
            );



            try {


                /*
                ====================================
                STEP 1
                Upload files
                ====================================
                */


                const job =
                    await uploadFiles(
                        formData
                    );


                const jobId =
                    job.job_id;


                currentJobId =
                    jobId;



                /*
                ====================================
                STEP 2
                Python processing
                ====================================
                */


                setProgress(
                    40,
                    'PDF uploaded. Starting comparison...'
                );



                /*
                ====================================
                STEP 3
                Poll Python status
                ====================================
                */


                while (true) {


                    await sleep(700);



                    const response =
                        await fetch(
                            'compare-pdf-status.php?job_id=' +
                            encodeURIComponent(
                                jobId
                            ),
                            {
                                method: 'GET',
                                cache: 'no-store'
                            }
                        );


                    if (!response.ok) {

                        throw new Error(
                            'Unable to read comparison status.'
                        );

                    }


                    const status =
                        await response.json();


                    if (!status.success) {

                        throw new Error(
                            status.message ||
                            'Unable to read comparison status.'
                        );

                    }



                    /*
                    --------------------------------
                    Python progress = 0-100

                    Overall:
                    Upload = 0-40
                    Python = 40-100
                    --------------------------------
                    */


                    const pythonProgress =
                        Number(
                            status.progress || 0
                        );


                    const overallProgress =
                        40 +
                        Math.round(
                            pythonProgress * 0.60
                        );


                    setProgress(
                        overallProgress,
                        status.message ||
                        'Comparing PDFs...'
                    );



                    /*
                    ====================================
                    COMPLETED
                    ====================================
                    */


                    if (
                        status.status ===
                        'completed'
                    ) {


                        setProgress(
                            100,
                            'Comparison completed successfully. ' + showDone()
                        );


                        const reportUrl =
                            status.report;


                        if (!reportUrl) {

                            throw new Error(
                                'Comparison completed but HTML report was not generated.'
                            );

                        }



                        /*
                        --------------------------------
                        Load report
                        --------------------------------
                        */


                        resultFrame.src =
                            reportUrl;


                        openReport.href =
                            reportUrl;


                        resultBox.style.display =
                            'block';



                        /*
                        --------------------------------
                        Scroll to result
                        --------------------------------
                        */


                        setTimeout(
                            function() {

                                if (status.filing) {
                            showFiling(!!status.filing.saved, status.filing.message || '', status.filing.work_link || '', status.filing.source_link || '');
                        }

                        resultBox.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'start'
                                });

                            },
                            300
                        );


                        break;

                    }



                    /*
                    ====================================
                    ERROR
                    ====================================
                    */


                    if (
                        status.status ===
                        'error'
                    ) {

                        throw new Error(
                            status.message ||
                            'PDF comparison failed.'
                        );

                    }


                }


            } catch (error) {


                console.error(
                    'PDF comparison error:',
                    error
                );


                setProgress(
                    0,
                    'Comparison failed.'
                );


                alert(
                    'PDF comparison failed:\n\n' +
                    error.message
                );


            } finally {


                /*
                ------------------------------------
                Restore UI
                ------------------------------------
                */


                compareButton.disabled =
                    false;


                compareButton.innerHTML =
                    compareButton.dataset.oldText ||
                    'Compare';


                overlay.style.display =
                    'none';


                loader.style.display =
                    'none';


            }


        }
    );



    /*
    ========================================
    AJAX UPLOAD FUNCTION
    ========================================
    */


    function uploadFiles(
        formData
    ) {


        return new Promise(
            function(resolve, reject) {


                const xhr =
                    new XMLHttpRequest();


                xhr.open(
                    'POST',
                    'compare-pdf-api.php',
                    true
                );



                /*
                ------------------------------------
                Upload progress
                ------------------------------------
                */


                xhr.upload.onprogress =
                    function(event) {


                        if (
                            !event.lengthComputable
                        ) {

                            return;

                        }


                        /*
                        Upload occupies
                        first 40% of overall progress.
                        */


                        const uploadPercent =
                            Math.round(
                                (
                                    event.loaded /
                                    event.total
                                ) * 40
                            );


                        setProgress(
                            uploadPercent,
                            'Uploading PDF files... ' + fmtSize(event.loaded) + ' of ' + fmtSize(event.total)
                        );

                    };



                /*
                ------------------------------------
                Success
                ------------------------------------
                */


                xhr.onload =
                    function() {


                        if (
                            xhr.status < 200 ||
                            xhr.status >= 300
                        ) {

                            reject(
                                new Error(
                                    'PDF upload failed. HTTP status: ' +
                                    xhr.status
                                )
                            );

                            return;

                        }


                        let response;


                        try {

                            response =
                                JSON.parse(
                                    xhr.responseText
                                );

                        } catch (error) {

                            console.error(
                                xhr.responseText
                            );


                            reject(
                                new Error(
                                    'Invalid response received from server.'
                                )
                            );

                            return;

                        }



                        if (
                            !response.success
                        ) {

                            reject(
                                new Error(
                                    response.message || response.error ||
                                    'Unable to start PDF comparison.'
                                )
                            );

                            return;

                        }


                        resolve(
                            response
                        );

                    };



                /*
                ------------------------------------
                Network error
                ------------------------------------
                */


                xhr.onerror =
                    function() {

                        reject(
                            new Error(
                                'Unable to connect to the server.'
                            )
                        );

                    };



                /*
                ------------------------------------
                Send
                ------------------------------------
                */


                cmpStart = Date.now();

                xhr.send(
                    formData
                );

            }
        );

    }



    /*
    ========================================
    DOWNLOAD REPORT
    (also auto-saves it to the article's
    PRQC folder on the server, when the
    source article can be identified)
    ========================================
    */


    downloadReport.addEventListener(
        'click',
        async function() {


            if (!currentJobId) {

                alert(
                    'No comparison report is available to download yet.'
                );

                return;

            }


            const originalText =
                downloadReport.innerHTML;


            downloadReport.disabled =
                true;


            downloadReport.innerHTML =
                'Downloading...';


            try {


                const response =
                    await fetch(
                        'download-compare-pdf-report.php?job_id=' +
                        encodeURIComponent(
                            currentJobId
                        ),
                        {
                            method: 'GET',
                            cache: 'no-store'
                        }
                    );


                if (!response.ok) {

                    throw new Error(
                        'Unable to download the report. HTTP status: ' +
                        response.status
                    );

                }


                const prqcSaved =
                    response.headers.get(
                        'X-Prqc-Saved'
                    ) === '1';


                const prqcMessage =
                    decodeURIComponent(
                        response.headers.get(
                            'X-Prqc-Message'
                        ) || ''
                    );


                const blob =
                    await response.blob();


                const blobUrl =
                    URL.createObjectURL(
                        blob
                    );


                const link =
                    document.createElement(
                        'a'
                    );


                link.href =
                    blobUrl;


                link.download =
                    'PDF_Comparison_Report_' +
                    currentJobId +
                    '.html';


                document.body.appendChild(
                    link
                );


                link.click();


                link.remove();


                URL.revokeObjectURL(
                    blobUrl
                );


                showFiling(prqcSaved, prqcMessage, decodeURIComponent(response.headers.get('X-Prqc-Link') || ''), decodeURIComponent(response.headers.get('X-Source-Link') || ''));


            } catch (error) {


                console.error(
                    'Report download error:',
                    error
                );


                alert(
                    'Failed to download the report:\n\n' +
                    error.message
                );


            } finally {


                downloadReport.disabled =
                    false;


                downloadReport.innerHTML =
                    originalText;


            }


        }
    );


})();

</script>


</body>

</html>