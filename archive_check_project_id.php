<?php
// Set max execution time and handle aborts
set_time_limit(3600);
ignore_user_abort(false); // Stop if browser is closed

$host = "localhost";
$user = "root";
$pass = "root@123";
$dbArchive = "workflow_archive";
$dbWorkflow = "workflow";
$batchSize = 500;

// Create log directory and file
$logDir = __DIR__ . "/uploads/migrations";
if (!file_exists($logDir)) {
    mkdir($logDir, 0777, true);
}
$logFile = $logDir . "/migration_log_" . date("Ymd_His") . ".log";
$logHandle = fopen($logFile, "a");

// Connect to Archive DB
$connArchive = mysqli_connect($host, $user, $pass, $dbArchive);
if (!$connArchive) {
    die("Connection to archive DB failed: " . mysqli_connect_error());
}

// Connect to Workflow DB
$connWorkflow = mysqli_connect($host, $user, $pass, $dbWorkflow);
if (!$connWorkflow) {
    die("Connection to workflow DB failed: " . mysqli_connect_error());
}

// Count total records
$countQuery = "SELECT COUNT(*) AS total FROM inw_inward_dtl";
$countResult = mysqli_query($connArchive, $countQuery);
$totalRecords = 0;

if ($countResult && $row = mysqli_fetch_assoc($countResult)) {
    $totalRecords = (int)$row['total'];
}

if ($totalRecords === 0) {
    $msg = "No records found in archive.inw_inward_dtl\n";
    echo $msg;
    fwrite($logHandle, $msg);
    fclose($logHandle);
    exit;
}

fwrite($logHandle, "Starting migration at " . date("Y-m-d H:i:s") . "\n");
fwrite($logHandle, "Total records: $totalRecords\n");

for ($offset = 0; $offset < $totalRecords; $offset += $batchSize) {
    $msg = "Processing batch starting at offset $offset\n";
    echo $msg;
    fwrite($logHandle, $msg);

   // $batchQuery = "SELECT id FROM inw_inward_dtl LIMIT $batchSize OFFSET $offset";
	  $batchQuery = "SELECT id FROM inw_inward_dtl ORDER BY id DESC LIMIT $batchSize OFFSET $offset";
    $batchResult = mysqli_query($connArchive, $batchQuery);

    if ($batchResult && mysqli_num_rows($batchResult) > 0) {
        while ($row = mysqli_fetch_assoc($batchResult)) {
            if (connection_aborted()) {
                fwrite($logHandle, "Connection aborted. Stopping process.\n");
                break 2;
            }

            $project_id = intval($row['id']);

            // Check if project_id exists in workflow.inw_transactions
            $checkWorkflow = mysqli_query($connWorkflow,
                "SELECT 1 FROM inw_transactions WHERE project_id = $project_id LIMIT 1");

            if (!$checkWorkflow || mysqli_num_rows($checkWorkflow) == 0) {
                continue;
            }

            // Check if already in workflow_archive.inw_transactions
            $checkArchive = mysqli_query($connWorkflow,
                "SELECT 1 FROM workflow_archive.inw_transactions WHERE project_id = $project_id LIMIT 1");

            if ($checkArchive && mysqli_num_rows($checkArchive) > 0) {
                continue;
            }

            // Check for duplicate in migration_missing_projects
            $checkDup = mysqli_query($connArchive,
                "SELECT 1 FROM migration_missing_projects WHERE project_id = $project_id LIMIT 1");

            if ($checkDup && mysqli_num_rows($checkDup) > 0) {
                $msg = "Duplicate found, skipping project_id: $project_id\n";
                echo $msg;
                fwrite($logHandle, $msg);
                continue;
            }

            // Insert into migration_missing_projects
            $insert = mysqli_query($connArchive,
                "INSERT INTO migration_missing_projects (project_id) VALUES ($project_id)");

            if ($insert) {
                $msg = "Inserted missing project_id: $project_id\n";
                echo $msg;
                fwrite($logHandle, $msg);
            } else {
                $error = mysqli_error($connArchive);
                $msg = "Error inserting project_id $project_id: $error\n";
                echo $msg;
                fwrite($logHandle, $msg);
            }
        }
    } else {
        fwrite($logHandle, "No records found in batch offset $offset\n");
    }
}

fwrite($logHandle, "Migration completed at " . date("Y-m-d H:i:s") . "\n");
fclose($logHandle);
mysqli_close($connArchive);
mysqli_close($connWorkflow);
?>
