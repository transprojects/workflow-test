<?php
set_time_limit(3600);
ignore_user_abort(false); // Stop if browser is closed

$host = "localhost";
$user = "root";
$pass = "root@123";
$dbWorkflow = "workflow";
$dbArchive = "workflow_archive";
$batchSize = 500;

// Log setup
$logDir = __DIR__ . "/uploads/migrations";
if (!file_exists($logDir)) {
    mkdir($logDir, 0777, true);
}
$logFile = "$logDir/migration_log_" . date("Ymd_His") . ".log";

function logMessage($msg, $logFile) {
    echo $msg . "\n";
    file_put_contents($logFile, "[" . date("Y-m-d H:i:s") . "] $msg\n", FILE_APPEND);
}

// Database connection
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

logMessage("Starting unified migration process...", $logFile);

// Step 1: Count total missing records
$countQuery = "
    SELECT COUNT(*) AS total
    FROM $dbArchive.inw_inward_dtl aid
    JOIN $dbWorkflow.inw_transactions wt ON wt.project_id = aid.id
    LEFT JOIN $dbArchive.inw_transactions at ON at.project_id = aid.id
    LEFT JOIN $dbArchive.migration_missing_projects mp ON mp.project_id = aid.id
    WHERE at.project_id IS NULL AND mp.project_id IS NULL
";
$countResult = $conn->query($countQuery);
$totalRecords = ($countResult && $row = $countResult->fetch_assoc()) ? (int)$row['total'] : 0;

if ($totalRecords === 0) {
    logMessage("No missing records to migrate.", $logFile);
    exit;
}

logMessage("Total missing records to insert: $totalRecords", $logFile);

$offset = 0;
while ($offset < $totalRecords) {
    logMessage("Processing batch at offset $offset", $logFile);

    $selectQuery = "
        SELECT wt.*
        FROM $dbArchive.inw_inward_dtl aid
        JOIN $dbWorkflow.inw_transactions wt ON wt.project_id = aid.id
        LEFT JOIN $dbArchive.inw_transactions at ON at.project_id = aid.id
        LEFT JOIN $dbArchive.migration_missing_projects mp ON mp.project_id = aid.id
        WHERE at.project_id IS NULL AND mp.project_id IS NULL
        ORDER BY aid.id DESC
        LIMIT $batchSize OFFSET $offset
    ";

    $batchResult = $conn->query($selectQuery);
    if (!$batchResult) {
        logMessage("Error selecting batch: " . $conn->error, $logFile);
        break;
    }

    while ($row = $batchResult->fetch_assoc()) {
        $projectId = intval($row['project_id']);

        // Prepare insert query for inw_transactions
        $columns = array_keys($row);
        $values = array_map(function ($val) use ($conn) {
            return is_null($val) ? "NULL" : "'" . $conn->real_escape_string($val) . "'";
        }, array_values($row));

        $insertQuery = "INSERT INTO $dbArchive.inw_transactions (`" . implode("`,`", $columns) . "`) VALUES (" . implode(",", $values) . ")";

        if ($conn->query($insertQuery)) {
            logMessage("Inserted project_id: $projectId", $logFile);

            // Log to migration_log table
            $logInsert = "INSERT INTO $dbArchive.migration_log (project_id, action_type) VALUES ($projectId, 'INSERT')";
            $conn->query($logInsert);

            // Log to migration_missing_projects
            $missInsert = "INSERT INTO $dbArchive.migration_missing_projects (project_id) VALUES ($projectId)";
            $conn->query($missInsert);

        } else {
            logMessage("Error inserting project_id $projectId: " . $conn->error, $logFile);
        }

        if (connection_aborted()) {
            logMessage("Connection aborted. Stopping...", $logFile);
            break 2;
        }
    }

    $offset += $batchSize;
}

logMessage("Migration process completed.", $logFile);
$conn->close();
?>
