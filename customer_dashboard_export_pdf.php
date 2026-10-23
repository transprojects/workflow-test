<?php include "includes/includes.php";
// downloads are only for logged-in users (same cookie the rest of the app uses)
if (!isset($_COOKIE['user_id'])) { http_response_code(401); exit('Please log in.'); }
ini_set('display_errors', 0);          // a warning printed here would corrupt the PDF
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
require 'vendor/autoload.php'; // Dompdf autoloader


// Date Range (Replace with POST input or default values)
// Get POST data

// DB Connections
// credentials come from includes/conn.php; only the archive schema name is fixed
$dbPrimary = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
$dbArchive = new mysqli($dbhost, $dbuser, $dbpass, 'workflow_archive');

if ($dbPrimary->connect_error || $dbArchive->connect_error) {
    die("DB Connection failed: " . ($dbPrimary->connect_error ?: $dbArchive->connect_error));
}


// Capture your full HTML content (your entire snippet) as a variable
ob_start();
// Decode JSON POST
// Decode JSON POST
$input = json_decode(file_get_contents('php://input'), true);

$chartBase64 = $input['chartBase64'] ?? '';
$chartType = $input['chartType'] ?? 'png';  // default to png

if ($chartType === 'svg') {
    // Decode SVG from base64
    $svgString = base64_decode($chartBase64);

    // Now, depending on your PDF library:
    // - You might embed SVG directly if supported (like TCPDF 6.2+)
    // - Or convert SVG to raster (PNG) before embedding
    // - Or save as a temp .svg file and add as an image

    // Example (TCPDF): 
    // $pdf->ImageSVG('@' . $svgString, ...);
} else {
    // For PNG (or other raster images)
    $imageData = base64_decode($chartBase64);
    // embed $imageData as PNG in PDF
}


// Inputs (adjust defaults or get from request)
$cust_id = isset($input['cust_id']) ? intval($input['cust_id']) : 33;
$from_date = isset($input['from_date']) ? date('Y-m-d', strtotime($input['from_date'])) : date('Y-m-01');
$to_date = isset($input['to_date']) ? date('Y-m-d', strtotime($input['to_date'])) : date('Y-m-d');

// Get customer name (optional)
$cust_name = '';
$custQuery = "SELECT cust_name FROM adm_customer_master WHERE id = $cust_id";
$custResult = $dbPrimary->query($custQuery);
if ($custResult && $custResult->num_rows > 0) {
    $custRow = $custResult->fetch_assoc();
    $cust_name = $custRow['cust_name'];
}

// Define delivery-related queries
/* wf_id -> department that completes the workflow (last department in wf_code other than 15).
   Built once per database in PHP; the old SQL re-derived it for every workflow with a 100-row
   number generator inside every delivery query. */
function resolvedDeptSql($db) {
    $parts = [];
    $r = $db->query("SELECT wf_id, wf_code FROM adm_workflow_master");
    while ($r && ($w = $r->fetch_assoc())) {
        $d = array_values(array_filter(array_map('trim', explode(',', (string)$w['wf_code'])), function ($x) { return $x !== '' && $x !== '15'; }));
        if (!$d) { continue; }
        $parts[] = "SELECT " . (int)$w['wf_id'] . " AS wf_id, '" . $db->real_escape_string(end($d)) . "' AS resolved_dept";
    }
    return $parts ? implode(' UNION ALL ', $parts) : "SELECT 0 AS wf_id, '' AS resolved_dept";
}

function getDeliveryQueries($from, $to, $cust_id, $resolved) {
    return [
        'first_proof' => "SELECT 
    i.pub_id AS ARTICLEID,
    t.current_stage,
    d.dept_name,
    i.created_dt,
    t.end_time,
    CASE 
        WHEN TIMESTAMPDIFF(HOUR, i.created_dt, t.end_time) < 48 THEN '<48 hrs to deliver'
        WHEN TIMESTAMPDIFF(HOUR, i.created_dt, t.end_time) BETWEEN 48 AND 72 THEN '48 to 72 hrs to deliver'
        ELSE '>72 hrs to deliver'
    END AS delivery_category
FROM inw_inward_dtl i
JOIN adm_journals j ON j.j_id = i.j_id
JOIN adm_workflow_master w ON w.wf_name = j.j_fp_wf
JOIN inw_transactions t ON t.project_id = i.id

JOIN (".$resolved.") wf_resolved ON wf_resolved.wf_id = w.wf_id

JOIN adm_dept_master d ON d.dept_code = wf_resolved.resolved_dept

WHERE t.current_status = 'Completed'
  AND t.current_stage = 'FP'
  AND t.dept = wf_resolved.resolved_dept
  AND t.end_time >= '$from' AND t.end_time < DATE_ADD('$to', INTERVAL 1 DAY)
  AND i.cust_id = '$cust_id'",

        'revised_proof' => "SELECT 
    i.pub_id AS ARTICLEID,
    t.current_stage,
    r.received_date,
    t.end_time,
    CASE 
        WHEN TIMESTAMPDIFF(HOUR, r.received_date, t.end_time) < 24 THEN '<24 hrs to deliver'
        WHEN TIMESTAMPDIFF(HOUR, r.received_date, t.end_time) BETWEEN 24 AND 48 THEN '24 to 48 hrs to deliver'
        ELSE '>48 hrs to deliver'
    END AS delivery_category,
    d.dept_name
FROM inw_revisions_dtl r
JOIN inw_inward_dtl i ON i.id = r.inw_id

JOIN adm_journals j ON j.j_id = i.j_id
JOIN adm_workflow_master w ON w.wf_name = j.j_rev_wf
JOIN inw_transactions t ON t.project_id = r.inw_id

JOIN (".$resolved.") wf_resolved ON wf_resolved.wf_id = w.wf_id

JOIN adm_dept_master d ON d.dept_code = wf_resolved.resolved_dept

WHERE t.current_status = 'Completed'
  AND t.current_stage = CONCAT('REV', r.revision_count)
  AND t.dept = wf_resolved.resolved_dept
  AND r.received_date >= '$from' AND r.received_date < DATE_ADD('$to', INTERVAL 1 DAY)
  AND i.cust_id = '$cust_id'"
    ];
}

// Fetch only from primary DB (for QMS feedback and CAPA)
function fetchSingleResult($db, $query, $limit = 2500) {
    $res = $db->query($query . " LIMIT $limit");
    if (!$res) {
        echo "Single DB query error: " . $db->error . "<br>";
        return [];
    }
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}

// Summarize delivery categories
function summarize($data, $key) {
    $summary = ['total' => 0, 'lt_48' => 0, 'btw_48_72' => 0, 'gt_72' => 0];
    foreach ($data as $row) {
        $summary['total']++;
        if (strpos($row[$key], '<48') !== false || strpos($row[$key], '<24') !== false) {
            $summary['lt_48']++;
        } elseif (strpos($row[$key], '48 to 72') !== false || strpos($row[$key], '24 to 48') !== false) {
            $summary['btw_48_72']++;
        } elseif (strpos($row[$key], '>72') !== false || strpos($row[$key], '>48') !== false) {
            $summary['gt_72']++;
        }
    }
    return $summary;
}

// --- Main execution ---
$queriesPrimary = getDeliveryQueries($from_date, $to_date, $cust_id, resolvedDeptSql($dbPrimary));
$queriesArchive = getDeliveryQueries($from_date, $to_date, $cust_id, resolvedDeptSql($dbArchive));

$firstProofDataWorkflow   = fetchSingleResult($dbPrimary, $queriesPrimary['first_proof']);
$revisedProofDataWorkflow = fetchSingleResult($dbPrimary, $queriesPrimary['revised_proof']);
$firstProofDataArchive    = fetchSingleResult($dbArchive, $queriesArchive['first_proof']);
$revisedProofDataArchive  = fetchSingleResult($dbArchive, $queriesArchive['revised_proof']);

// Summarize each source separately
$firstStatsWorkflow = summarize($firstProofDataWorkflow, 'delivery_category');
$firstStatsWorkflowArchive = summarize($firstProofDataArchive, 'delivery_category');

$revStatsWorkflow = summarize($revisedProofDataWorkflow, 'delivery_category');
$revStatsWorkflowArchive = summarize($revisedProofDataArchive, 'delivery_category');



// If you still want combined stats
$firstStats = [
    'total' => $firstStatsWorkflow['total'] + $firstStatsWorkflowArchive['total'],
    'lt_48' => $firstStatsWorkflow['lt_48'] + $firstStatsWorkflowArchive['lt_48'],
    'btw_48_72' => $firstStatsWorkflow['btw_48_72'] + $firstStatsWorkflowArchive['btw_48_72'],
    'gt_72' => $firstStatsWorkflow['gt_72'] + $firstStatsWorkflowArchive['gt_72'],
];

$revStats = [
    'total' => $revStatsWorkflow['total'] + $revStatsWorkflowArchive['total'],
    'lt_48' => $revStatsWorkflow['lt_48'] + $revStatsWorkflowArchive['lt_48'],
    'btw_48_72' => $revStatsWorkflow['btw_48_72'] + $revStatsWorkflowArchive['btw_48_72'],
    'gt_72' => $revStatsWorkflow['gt_72'] + $revStatsWorkflowArchive['gt_72'],
];

// QMS (Feedback)
$qmsQuery = "SELECT f.f_id, j.j_code, f.article, f.stage, f.message, f.created_on
    FROM inw_client_feedback f
    JOIN adm_journals j ON j.j_id = f.j_id
    WHERE f.message IS NOT NULL 
      AND f.cust_id = $cust_id 
      AND DATE(f.created_on) BETWEEN '$from_date' AND '$to_date'
    ORDER BY f.created_on DESC
";
$qmsResult = $db->query($qmsQuery);

// CAPA (Action Taken)
$capaQuery = "SELECT f.f_id, j.j_code, f.article, f.stage, f.ap_message, f.ap_dt
    FROM inw_client_feedback f
    JOIN adm_journals j ON j.j_id = f.j_id
    WHERE f.ap_message IS NOT NULL 
      AND f.cust_id = $cust_id 
      AND DATE(f.ap_dt) BETWEEN '$from_date' AND '$to_date'
    ORDER BY f.ap_dt DESC
";
$capaResult = $db->query($capaQuery);



?>
<?php 
function fetchMergedResultsa($db1, $db2, $query) {
    $merged = [];

    $res1 = $db1->query($query);
    if ($res1) {
        while ($row = $res1->fetch_assoc()) {
            $merged[] = $row;
        }
    }

    $res2 = $db2->query($query);
    if ($res2) {
        while ($row = $res2->fetch_assoc()) {
            $merged[] = $row;
        }
    }

    return $merged;
}

$queries = [
    'first_proof' => "
        SELECT j.j_code, i.pub_id, d.sent_date
        FROM inw_dispatch_history d
        JOIN inw_inward_dtl i ON d.a_id = i.id
        JOIN adm_journals j ON i.j_id = j.j_id
        WHERE d.sent_date >= '$from_date' AND d.sent_date <= '$to_date'
		and i.created_dt BETWEEN '$from_date' AND '$to_date'
        AND d.stage='FP' AND i.cust_id = $cust_id group by a_id
    ",
    'revised_proof' => "
        SELECT j.j_code, i.pub_id, r.received_date
        FROM inw_revisions_dtl r
        JOIN inw_inward_dtl i ON r.inw_id = i.id
        JOIN adm_journals j ON i.j_id = j.j_id
        WHERE r.received_date >= '$from_date' AND r.received_date <= '$to_date'
        AND i.stage='REV1' AND i.cust_id = $cust_id group by inw_id
    "
];

$proofResult = fetchMergedResultsa($dbPrimary, $dbArchive, $queries['first_proof']);
$revisedResult = fetchMergedResultsa($dbPrimary, $dbArchive, $queries['revised_proof']);

$proofCount = count($proofResult);
$revisedCount = count($revisedResult);
?> 

<!-- Your full HTML here, but add minimal changes: -->

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
</head>
<body>
<!-- PDF Header -->
<!--mpdf

<htmlpageheader name="page-header">
  <div style="width: 100%; font-size: 10pt;">
    <table width="100%" style="border: none; margin-bottom: 5px;">
      <tr>
        <td style="text-align: left; border: none; padding: 0;">
          <img src="<?php echo str_replace(chr(92), '/', __DIR__); ?>/assets/images/transforma-logo.svg" 
               style="width: 80px; height: 40px; object-fit: contain; border: none; outline: none;" 
               alt="Left Logo" />
        </td>
       
        <td style="text-align: center; border: none; padding: 0;">
          <img src="<?php echo str_replace(chr(92), '/', __DIR__); ?>/assets/images/peerj-logo.svg" 
               style="width: 80px; height: 40px; object-fit: contain; border: none; outline: none;" 
               alt="Center Logo" />
        </td>
      </tr>
    </table>
	    <hr style="border: 0; border-top: 1px solid #000; margin: 5px 0 0 0;" />

  </div>
</htmlpageheader>


<htmlpagefooter name="page-footer">
  <div style="text-align: center; font-size: 9pt; border-top: 1px solid #ccc; padding-top: 5px;">
    {DATE j-m-Y H:i:s} | Page {PAGENO} of {nbpg}
  </div>
</htmlpagefooter>


<sethtmlpagefooter name="page-footer" value="on" />
mpdf-->

<div class="container-fluid peerj">
<h4  style="text-align:center; font-weight:bold; margin-bottom:15px;">Delivery Status Report</h4>

  <!-- Section 1 (your peerj-section1) -->
  <div class="peerj-section1">
    <?php if (!empty($from_date) && !empty($to_date)): ?>
      <div style="text-align:center; font-weight:bold; margin-bottom:15px;">
        Showing Data: 
        <span style="color:#2a9d8f;">
          <?php echo date('d-m-Y', strtotime($from_date)); ?>
        </span> 
        to 
        <span style="color:#2a9d8f;">
          <?php echo date('d-m-Y', strtotime($to_date)); ?>
        </span>
      </div>
    <?php endif; ?>

<h4 style="margin-top:20px;">First Proof Summary</h4>
<table width="100%" cellpadding="0" border="0" style="text-align:center; border-collapse: separate; border-spacing: 15px 10px;">
  <tr>
    <td style="background-color:#EBF8FB; border:1px solid #ccc; border-radius:8px; padding:10px;">
      <img src="assets/images/peerj/icon1.svg" width="24" height="24" /><br/>
      <strong><?php echo htmlspecialchars($firstStatsWorkflow['total'] + $firstStatsWorkflowArchive['total']); ?></strong><br/>
      <span>Total Firstproof</span>
    </td>
    <td style="background-color:#F6F4FF; border:1px solid #ccc; border-radius:8px; padding:10px;">
      <img src="assets/images/peerj/icon2.svg" width="24" height="24" /><br/>
      <strong><?php echo htmlspecialchars($firstStats['lt_48']); ?></strong><br/>
      <span>Within 48 h</span>
    </td>
    <td style="background-color:#E3F8ED; border:1px solid #ccc; border-radius:8px; padding:10px;">
      <img src="assets/images/peerj/icon3.svg" width="24" height="24" /><br/>
      <strong><?php echo htmlspecialchars($firstStats['btw_48_72']); ?></strong><br/>
      <span>48 - 72 h</span>
    </td>
    <td style="background-color:#FEEEE9; border:1px solid #ccc; border-radius:8px; padding:10px;">
      <img src="assets/images/peerj/icon4.svg" width="24" height="24" /><br/>
      <strong><?php echo htmlspecialchars($firstStats['gt_72']); ?></strong><br/>
      <span>Above 72 h</span>
    </td>
  </tr>
</table>




   


  </div>

 

<h4 style="margin-top:30px;">Revised Proof Summary</h4>
<table width="100%" cellpadding="0" border="0" 
  style="text-align:center; border-collapse: separate; border-spacing: 15px 10px;">
  <tr>
    <td style="background-color:#EBF8FB; border:1px solid #ccc; border-radius:8px; padding:10px;">
      <img src="assets/images/peerj/icon1.svg" width="24" height="24" /><br/>
      <strong><?php echo htmlspecialchars($revStatsWorkflow['total'] + $revStatsWorkflowArchive['total']); ?></strong><br/>
      <span>Total Revises</span>
    </td>
    <td style="background-color:#F6F4FF; border:1px solid #ccc; border-radius:8px; padding:10px;">
      <img src="assets/images/peerj/icon2.svg" width="24" height="24" /><br/>
      <strong><?php echo htmlspecialchars($revStats['lt_48']); ?></strong><br/>
      <span>Within 24 h</span>
    </td>
    <td style="background-color:#E3F8ED; border:1px solid #ccc; border-radius:8px; padding:10px;">
      <img src="assets/images/peerj/icon3.svg" width="24" height="24" /><br/>
      <strong><?php echo htmlspecialchars($revStats['btw_48_72']); ?></strong><br/>
      <span>24 - 48 h</span>
    </td>
    <td style="background-color:#FEEEE9; border:1px solid #ccc; border-radius:8px; padding:10px;">
      <img src="assets/images/peerj/icon4.svg" width="24" height="24" /><br/>
      <strong><?php echo htmlspecialchars($revStats['gt_72']); ?></strong><br/>
      <span>Above 48 h</span>
    </td>
  </tr>
</table>

<!-- Embed Chart Image Here -->
<h4>Review Statistics</h4>
<?php // Try decoding the base64 input
$svgString = base64_decode($chartBase64, true); // strict mode

if ($svgString === false || trim($svgString) === '') {
    // Decoding failed or empty string
    echo '<div style="color: red;">Error: Invalid or empty base64 chart data.</div>';
} else {
    // Check if it's a valid SVG string
    libxml_use_internal_errors(true); // Suppress libxml errors
    $isValidSvg = false;

    try {
        $xml = simplexml_load_string($svgString);
        if ($xml !== false && $xml->getName() === 'svg') {
            $isValidSvg = true;

            // Ensure SVG has xmlns for rendering
            if (strpos($svgString, 'xmlns=') === false) {
                $svgString = preg_replace('/<svg/', '<svg xmlns="http://www.w3.org/2000/svg"', $svgString, 1);
            }

            // Output the sanitized SVG
            echo $svgString;
        }
    } catch (Exception $e) {
        // Catch parsing exception if any
    }

    if (!$isValidSvg) {
        echo '<div style="color: red;">Error: Invalid SVG format in chart data.</div>';
    }
}
?>
 <!-- Section 2 (your peerj-section2) -->
  <div class="peerj-section2">


    <h4>QMS Feedback</h4>
    <table>
      <thead>
        <tr>
          <th>F.No</th>
          <th>Journal Name</th>
          <th>Article ID</th>
          <th>Stage</th>
          <th>Feedback</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php while($row = $qmsResult->fetch_assoc()): ?>
          <tr>
            <td><?php echo $row['f_id']; ?></td>
            <td><?php echo htmlspecialchars($row['j_code']); ?></td>
            <td><?php echo htmlspecialchars($row['article']); ?></td>
            <td><?php echo htmlspecialchars($row['stage']); ?></td>
            <td><?php echo nl2br(htmlspecialchars($row['message'])); ?></td>
            <td><?php echo  date('d-m-Y',strtotime($row['created_on'])); ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>

    <h4>CAPA Actions</h4>
    <table>
      <thead>
        <tr>
          <th>F.No</th>
          <th>Journal Name</th>
          <th>Article ID</th>
          <th>Stage</th>
          <th>Action Taken</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php while($row = $capaResult->fetch_assoc()): ?>
          <tr>
            <td><?php echo $row['f_id']; ?></td>
            <td><?php echo htmlspecialchars($row['j_code']); ?></td>
            <td><?php echo htmlspecialchars($row['article']); ?></td>
            <td><?php echo htmlspecialchars($row['stage']); ?></td>
            <td><?php echo nl2br(htmlspecialchars($row['ap_message'])); ?></td>
            <td><?php echo  date('d-m-Y',strtotime($row['ap_dt'])); ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
	
	    <!-- Happenings -->
                      <div class="peerj-happenings">
    <h4 class="peerj-common-head">Happenings</h4>
    <div class="peerj-happenings-content">
        <?php
        // Fetch happenings from the database
        $stmt = $db->prepare("SELECT h_title, h_cdate FROM wf_happenings ORDER BY h_cdate DESC LIMIT 10");
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
          //  while ($row = $result->fetch_assoc()) {
               // $formattedDate = date("d-m-Y", strtotime($row['h_cdate']));
               // echo '<p><span></span>' . htmlspecialchars($row['h_title']) . ' ON ' . $formattedDate . '</p>';
             //   echo '<p><span></span>' . htmlspecialchars($row['h_title']) . '</p>';
          //  }
			$colors = ['#3771F2', '#F25C37', '#37F29E', '#F2D237', '#8E37F2']; // define some colors
while ($row = $result->fetch_assoc()) {
    $randomColor = $colors[array_rand($colors)];
    
    echo '<p style="margin:4px 0; line-height:16px;">';
    echo '<span style="color:' . $randomColor . '; font-size: 14px; margin-right: 12px;">&#9679; </span>&nbsp;&nbsp;'; // ● bullet with more spacing
    echo htmlspecialchars($row['h_title']);
    echo '</p>';
}


        } else {
            echo '<p><span></span>No recent happenings.</p>';
        }

        $stmt->close();
        ?>
    </div>
</div>

	  </div>
	 

</div>
<!-- Footer Section -->


</body>
</html>



<?php
// Capture the buffered HTML (which includes your SVG chart)
$html = ob_get_clean();

// Import mPDF namespace (if not done elsewhere)
use Mpdf\Mpdf;
use Mpdf\HTMLParserMode;



// Define CSS for styling
$css = "
body { font-family: sans-serif; font-size: 11pt; }
table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
th { background-color: #f2f2f2; }
h4 { margin-top: 30px; }
.peerj-dash-card { display: inline-block; width: 23%; margin: 1%; background: #f9f9f9; padding: 10px; border-radius: 6px; }
.peerj-card-head { font-weight: bold; margin-bottom: 10px; }
.graph-image { max-width: 100%; height: auto; margin: 20px 0; }
.peerj-happenings-content span {
    display: inline-block; width: 12px; height: 12px; border-radius: 3px; margin-right: 6px; vertical-align: middle;
}
@page {
    header: page-header;
    footer: page-footer;
	
}
.pdf-header, .pdf-footer {
    width: 100%; text-align: center; font-size: 10pt;
}
.pdf-header img {
    height: 40px; vertical-align: middle;
}
.pdf-footer {
    position: running(page-footer);
}
.pdf-header {
    position: running(page-header);
}
.page-footer-content {
    border-top: 1px solid #ccc; padding-top: 5px; font-size: 9pt; color: #666;
}
";

// Optional CSS for SVG rendering
$svgCss = '
svg {
    width: 100% !important;
    height: auto !important;
    font-family: Arial, sans-serif;
}
svg text {
    font-size: 14px !important;
    fill: #000000 !important;
    font-weight: normal !important;
}
';

$mpdf = new \Mpdf\Mpdf([
    'format' => 'A4',
    'margin_top' => 30,
    'margin_header' => 10,
    'margin_footer' => 10,
]);

// header / footer: defined in the HTML (@page + <sethtmlpagefooter>)


// Write CSS and HTML as before
$mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
$mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

// Output PDF
while (ob_get_level() > 0) { ob_end_clean(); }   // nothing may precede %PDF
$mpdf->Output('customer_dashboard_report.pdf', 'D');
exit;


?>
