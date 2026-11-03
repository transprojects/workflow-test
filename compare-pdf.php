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
            background-color: rgba(0,0,0,0.45);
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
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            animation: spin 1s linear infinite;
            margin: auto;
        }

        @keyframes spin {

            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }

        }

        /*
        ========================================
        Progress Section
        ========================================
        */

        .pdf-progress-wrapper {

            display: none;

            margin-top: 30px;
            margin-bottom: 30px;

            padding: 25px;

            background: #ffffff;

            border-radius: 10px;

            border: 1px solid #e3e6ea;

            box-shadow: 0 3px 12px rgba(0,0,0,0.08);

        }

        .pdf-progress-title {

            font-size: 18px;

            font-weight: 600;

            margin-bottom: 15px;

        }

        .pdf-progress-bar {

            width: 100%;

            height: 22px;

            background: #e9ecef;

            border-radius: 20px;

            overflow: hidden;

        }

        .pdf-progress-fill {

            width: 0%;

            height: 100%;

            background: linear-gradient(
                90deg,
                #007bff,
                #00a8ff
            );

            transition: width .35s ease;

            border-radius: 20px;

        }

        .pdf-progress-info {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-top: 12px;

        }

        .pdf-progress-text {

            font-size: 14px;

            color: #555;

        }

        .pdf-progress-percent {

            font-size: 15px;

            font-weight: 600;

        }

        /*
        ========================================
        Result Section
        ========================================
        */

        .pdf-comparison-result {

            display: none;

            margin-top: 35px;

            margin-bottom: 50px;

            padding: 20px;

            background: #ffffff;

            border: 1px solid #e3e6ea;

            border-radius: 10px;

            box-shadow: 0 3px 15px rgba(0,0,0,0.08);

        }

        .pdf-result-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 20px;

        }

        .pdf-result-header h4 {

            margin: 0;

            font-weight: 600;

        }

        .pdf-result-frame {

            width: 100%;

            height: 1000px;

            border: 1px solid #ddd;

            border-radius: 8px;

            background: #fff;

        }

        /*
        ========================================
        File Information
        ========================================
        */

        .selected-file-name {

            margin-top: 7px;

            font-size: 13px;

            color: #666;

            word-break: break-all;

        }

        /*
        ========================================
        Responsive
        ========================================
        */

        @media(max-width: 768px) {

            .pdf-result-header {

                flex-direction: column;

                align-items: flex-start;

                gap: 15px;

            }

            .pdf-result-frame {

                height: 700px;

            }

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



    <div class="container addarticleformouter">

        <div class="container addarticleforminner m-5">


            <!-- =========================================
                 PDF UPLOAD FORM
            ========================================== -->

            <form id="pdf-compare"
                  enctype="multipart/form-data">


                <div class="container mt-4">

                    <div class="formcont1heading">

                        Upload PDF Files for Comparison

                    </div>


                    <div class="row mt-1 g-4">


                        <!-- FILE 1 -->

                        <div class="col-12 col-sm-6 col-lg-5">

                            <label class="mb-2">

                                Select First PDF

                                <span style="color:red">
                                    *
                                </span>

                            </label>


                            <input
                                type="file"
                                class="form-control"
                                name="file1"
                                id="file1"
                                accept=".pdf,application/pdf"
                                required
                            >


                            <div
                                id="file1-name"
                                class="selected-file-name">
                            </div>

                        </div>



                        <!-- FILE 2 -->

                        <div class="col-12 col-sm-6 col-lg-5">

                            <label class="mb-2">

                                Select Second PDF

                                <span style="color:red">
                                    *
                                </span>

                            </label>


                            <input
                                type="file"
                                class="form-control"
                                name="file2"
                                id="file2"
                                accept=".pdf,application/pdf"
                                required
                            >


                            <div
                                id="file2-name"
                                class="selected-file-name">
                            </div>

                        </div>


                    </div>

                </div>



                <!-- =========================================
                     BUTTONS
                ========================================== -->

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

                <div id="pdf-file-details" class="mt-4" style="display:none; font-size:14px;"></div>

                <div class="meta_nxtbtn container-fluid mt-5 mb-5">

                    <div class="row">

                        <div class="col form_btn cancelbtn d-flex justify-content-end">

                            <button
                                type="button"
                                class="btn-next-cancel"
                                onclick="history.back();">

                                Cancel

                            </button>

                        </div>


                        <div class="col form_btn nxtbtn">

                            <button
                                type="submit"
                                id="compare-button"
                                class="btn-next">

                                Compare

                            </button>

                        </div>

                    </div>

                </div>


            </form>



            <!-- =========================================
                 PROGRESS BAR
            ========================================== -->

            <div
                id="pdf-progress"
                class="pdf-progress-wrapper">


                <div class="pdf-progress-title">

                    PDF Comparison Progress

                </div>


                <div class="pdf-progress-bar">

                    <div
                        id="pdf-progress-fill"
                        class="pdf-progress-fill">
                    </div>

                </div>


                <div class="pdf-progress-info">

                    <div
                        id="pdf-progress-text"
                        class="pdf-progress-text">

                        Preparing...

                    </div>


                    <div
                        id="pdf-progress-percent"
                        class="pdf-progress-percent">

                        0%

                    </div>

                </div>


            </div>



            <!-- =========================================
                 RESULT
            ========================================== -->

            <div
                id="pdf-comparison-result"
                class="pdf-comparison-result">


                <div class="pdf-result-header">


                    <h4>
                        PDF Comparison Result
                    </h4>


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


                <iframe
                    id="pdf-result-frame"
                    class="pdf-result-frame">
                </iframe>


            </div>


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

    function showDone() {
        const s = Math.round((Date.now() - cmpStart) / 1000);
        cmpElapsed = 'Compared in ' + Math.floor(s / 60) + ' min ' + (s % 60) + ' sec';
        renderDetails();
        return '(' + cmpElapsed + ')';
    }

    file1.addEventListener('change', function () { cmpElapsed = ''; renderDetails(); });
    file2.addEventListener('change', function () { cmpElapsed = ''; renderDetails(); });

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
                                    response.message ||
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


                if (prqcMessage) {

                    alert(
                        prqcSaved ?
                        ('Report downloaded.\n\n' + prqcMessage) :
                        ('Report downloaded, but it was NOT auto-saved to a PRQC folder.\n\n' + prqcMessage)
                    );

                }


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