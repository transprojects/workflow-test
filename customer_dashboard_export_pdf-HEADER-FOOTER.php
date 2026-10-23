<?php include "includes/includes.php";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require 'vendor/autoload.php'; // Dompdf autoloader


// Date Range (Replace with POST input or default values)
// Get POST data

// DB Connections
$dbPrimary = new mysqli('localhost', 'root', 'root@123', 'workflow');
$dbArchive = new mysqli('localhost', 'root', 'root@123', 'workflow_archive');

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
function getDeliveryQueries($from, $to, $cust_id) {
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

-- Compute resolved dept only once in derived table
JOIN (
    SELECT w.wf_id, w.wf_code,
        (
          SELECT dept_id FROM (
              SELECT 
                  SUBSTRING_INDEX(SUBSTRING_INDEX(w.wf_code, ',', n.n), ',', -1) AS dept_id
              FROM (
                  SELECT a.N + b.N * 10 + 1 AS n
                  FROM (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 
                        UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 
                        UNION ALL SELECT 8 UNION ALL SELECT 9) a,
                       (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 
                        UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 
                        UNION ALL SELECT 8 UNION ALL SELECT 9) b
              ) n
              WHERE n.n <= 1 + LENGTH(w.wf_code) - LENGTH(REPLACE(w.wf_code, ',', ''))
          ) AS depts
          WHERE dept_id != '15'
          ORDER BY FIND_IN_SET(dept_id, w.wf_code) DESC
          LIMIT 1
        ) AS resolved_dept
    FROM adm_workflow_master w
) wf_resolved ON wf_resolved.wf_id = w.wf_id

JOIN adm_dept_master d ON d.dept_code = wf_resolved.resolved_dept

WHERE t.current_status = 'Completed'
  AND t.current_stage = 'FP'
  AND t.dept = wf_resolved.resolved_dept
  AND i.created_dt BETWEEN '$from' AND '$to'
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

-- Compute resolved department once
JOIN (
    SELECT w.wf_id, w.wf_code,
        (
          SELECT dept_id FROM (
              SELECT 
                  SUBSTRING_INDEX(SUBSTRING_INDEX(w.wf_code, ',', n.n), ',', -1) AS dept_id
              FROM (
                  SELECT a.N + b.N * 10 + 1 AS n
                  FROM (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 
                        UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 
                        UNION ALL SELECT 8 UNION ALL SELECT 9) a,
                       (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 
                        UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 
                        UNION ALL SELECT 8 UNION ALL SELECT 9) b
              ) n
              WHERE n.n <= 1 + LENGTH(w.wf_code) - LENGTH(REPLACE(w.wf_code, ',', ''))
          ) AS depts
          WHERE dept_id != '15'
          ORDER BY FIND_IN_SET(dept_id, w.wf_code) DESC
          LIMIT 1
        ) AS resolved_dept
    FROM adm_workflow_master w
) wf_resolved ON wf_resolved.wf_id = w.wf_id

JOIN adm_dept_master d ON d.dept_code = wf_resolved.resolved_dept

WHERE t.current_status = 'Completed'
  AND t.current_stage = CONCAT('REV', r.revision_count)
  AND t.dept = wf_resolved.resolved_dept
  AND r.received_date BETWEEN '$from' AND '$to'
  AND i.cust_id = '$cust_id'"
    ];
}

// Fetch results with deduplication by ARTICLEID + current_stage (for first and revised proofs)
function fetchMergedResults($db1, $db2, $query, $limit = 500) {
    $dataMap = [];

    $res1 = $db1->query($query . " LIMIT $limit");
    if (!$res1) {
        echo "Primary DB query error: " . $db1->error . "<br>";
    } else {
        while ($row = $res1->fetch_assoc()) {
            $key = ($row['ARTICLEID'] ?? '') . '_' . ($row['current_stage'] ?? '');
            if ($key) {
                $dataMap[$key] = $row;
            }
        }
    }

    $res2 = $db2->query($query . " LIMIT $limit");
    if (!$res2) {
        echo "Archive DB query error: " . $db2->error . "<br>";
    } else {
        while ($row = $res2->fetch_assoc()) {
            $key = ($row['ARTICLEID'] ?? '') . '_' . ($row['current_stage'] ?? '');
            if ($key && !isset($dataMap[$key])) {
                $dataMap[$key] = $row;
            }
        }
    }

    return array_values($dataMap);
}

// Fetch only from primary DB (for QMS feedback and CAPA)
function fetchSingleResult($db, $query, $limit = 500) {
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
$queries = getDeliveryQueries($from_date, $to_date, $cust_id);

// Fetch and merge first proof data from both DBs
$firstProofData = fetchMergedResults($dbPrimary, $dbArchive, $queries['first_proof']);

// Fetch and merge revised proof data from both DBs
$revisedProofData = fetchMergedResults($dbPrimary, $dbArchive, $queries['revised_proof']);

// Summarize delivery categories
$firstStats = summarize($firstProofData, 'delivery_category');
$revStats = summarize($revisedProofData, 'delivery_category');


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
$capaQuery = "SELECT f.f_id, j.j_code, f.article, f.stage, f.ap_message, f.created_on
    FROM inw_client_feedback f
    JOIN adm_journals j ON j.j_id = f.j_id
    WHERE f.ap_message IS NOT NULL 
      AND f.cust_id = $cust_id 
      AND DATE(f.created_on) BETWEEN '$from_date' AND '$to_date'
    ORDER BY f.created_on DESC
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
<title>Delivery Status Report</title>
</head>
<body>
<!-- PDF Header -->
<!--mpdf
<htmlpageheader name="page-header">
  <div style="width: 100%; font-size: 10pt;">
    <table width="100%" style="border: none; margin-bottom: 5px;">
      <tr>
        <td style="text-align: center; border: none; padding: 0;">
          <img src="http://103.66.12.3:8010/workflow-new/assets/images/transforma-logo.svg" style="height: 40px; border:none; outline:none;" alt="Logo" />
        </td>
      </tr>
    </table>
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
      <strong><?php echo htmlspecialchars($firstStats['total']); ?></strong><br/>
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

  <!-- Section 2 (your peerj-section2) -->
  <div class="peerj-section2">
<!-- Embed Chart Image Here -->
<h4>Review Statistics</h4>
<?php if (!empty($chartBase64)): ?>
  <div class="graph-image">
    <?php 
      // Decode base64 SVG string (assumed to be pure SVG XML, no data URI prefix)
      $svgString = base64_decode($chartBase64);

      // Optional: ensure SVG has xmlns attribute for proper rendering
      if (strpos($svgString, '<svg') !== false && strpos($svgString, 'xmlns=') === false) {
          $svgString = preg_replace('/<svg/', '<svg xmlns="http://www.w3.org/2000/svg"', $svgString, 1);
      }

      // Output raw SVG XML to render inline
      echo $svgString;
    ?>
  </div>
<?php else: ?>
  <p><em>Chart not available.</em></p>
<?php endif; ?>


    <h4>QMS Feedback</h4>
    <table>
      <thead>
        <tr>
          <th>F.No</th>
          <th>Journal Name</th>
          <th>Article ID</th>
          <th>Stage</th>
          <th>Feedback</th>
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

<h4 style="margin-top:30px;">Revised Proof Summary</h4>
<table width="100%" cellpadding="0" border="0" 
  style="text-align:center; border-collapse: separate; border-spacing: 15px 10px;">
  <tr>
    <td style="background-color:#EBF8FB; border:1px solid #ccc; border-radius:8px; padding:10px;">
      <img src="assets/images/peerj/icon1.svg" width="24" height="24" /><br/>
      <strong><?php echo htmlspecialchars($revStats['total']); ?></strong><br/>
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


	  <div class="peerj-section2">

    <!-- Proof Sent and Revised Received tables -->
    <h4>Proof Sent</h4>
    <p>Total Proof Sent: <?php echo $proofCount; ?></p>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Journal Name</th>
          <th>Article ID</th>
          <th>Sent Date</th>
        </tr>
      </thead>
      <tbody>
        <?php $i=1; foreach($proofResult as $row): ?>
          <tr>
            <td><?php echo $i++; ?></td>
            <td><?php echo htmlspecialchars($row['j_code']); ?></td>
            <td><?php echo htmlspecialchars($row['pub_id']); ?></td>
            <td><?php echo htmlspecialchars($row['sent_date']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <h4>Revised Received</h4>
    <p>Total Revised Received: <?php echo $revisedCount; ?></p>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Journal Name</th>
          <th>Article ID</th>
          <th>Received Date</th>
        </tr>
      </thead>
      <tbody>
        <?php $i=1; foreach($revisedResult as $row): ?>
          <tr>
            <td><?php echo $i++; ?></td>
            <td><?php echo htmlspecialchars($row['j_code']); ?></td>
            <td><?php echo htmlspecialchars($row['pub_id']); ?></td>
            <td><?php echo htmlspecialchars($row['received_date']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

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
    'margin_top' => 50,
    'margin_header' => 10,
    'margin_footer' => 10,
]);

// Set header for first page
$mpdf->SetHTMLHeaderByName('page-header', 'O'); // First page only
$mpdf->SetHTMLHeaderByName('', 'E'); // No header on other pages

// Set footer for all pages
$mpdf->SetHTMLFooterByName('page-footer');

// Write CSS and HTML as before
$mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
$mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

// Output PDF
$mpdf->Output('customer_dashboard_report.pdf', 'D');
exit;


?>
