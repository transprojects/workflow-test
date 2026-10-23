<?php
$host = "localhost";
$username = "root";
$password = "root@123";
$workflowDb = "workflow";
$archiveDb = "workflow_archive";
$batchSize = 500;

$logDir = "uploads/migrations";
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true); // Creates the directory recursively with full permissions
}

$logFile = "$logDir/migration_log_" . date("Ymd_His") . ".log";

$conn = new mysqli($host, $username, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Logging function
function logMessage($message, $logFile) {
    echo $message . "\n";
    file_put_contents($logFile, "[" . date("Y-m-d H:i:s") . "] $message\n", FILE_APPEND);
}

logMessage("Starting batch migration of missing inw_transactions...", $logFile);

// Step 1: Count total missing rows
echo $countQuery = "
    SELECT COUNT(*) AS total
FROM workflow.inw_transactions wt
JOIN workflow_archive.inw_inward_dtl aid ON aid.id = wt.project_id
LEFT JOIN workflow_archive.inw_transactions at ON at.project_id = wt.project_id
WHERE at.project_id IS NULL";
$result = $conn->query($countQuery);
$row = $result->fetch_assoc();
$totalRows = (int)$row['total'];

if ($totalRows === 0) {
    logMessage("No missing records to migrate.", $logFile);
    exit;
}

logMessage("Total missing records to insert: $totalRows", $logFile);

$offset = 0;
while ($offset < $totalRows) {
    logMessage("Processing batch at offset $offset...", $logFile);

    // Step 2: Get the batch of records
    $selectQuery = "
        SELECT wt.*
        FROM $workflowDb.inw_transactions wt
        JOIN $archiveDb.inw_inward_dtl aid ON aid.id = wt.project_id
        LEFT JOIN $archiveDb.inw_transactions at ON at.project_id = wt.project_id AND at.id = wt.id
        WHERE at.project_id IS NULL
        LIMIT $batchSize OFFSET $offset
    ";

    $batchResult = $conn->query($selectQuery);
    if ($batchResult === false) {
        logMessage("Error retrieving batch: " . $conn->error, $logFile);
        break;
    }

    // Step 3: Insert each row manually to track project_id
    while ($row = $batchResult->fetch_assoc()) {
        $columns = array_keys($row);
        $values = array_map(function($val) use ($conn) {
            return is_null($val) ? "NULL" : "'" . $conn->real_escape_string($val) . "'";
        }, array_values($row));

        $insertQuery = "INSERT INTO $archiveDb.inw_transactions (`" . implode("`,`", $columns) . "`) VALUES (" . implode(",", $values) . ")";
        if ($conn->query($insertQuery)) {
            $projectId = $row['project_id'];

            // Log to table
            $logInsert = "INSERT INTO $archiveDb.migration_log (project_id, action_type) VALUES ($projectId, 'INSERT')";
            $conn->query($logInsert);

            // Log to file
            logMessage("Inserted project_id: $projectId", $logFile);
        } else {
            logMessage("Error inserting project_id " . $row['project_id'] . ": " . $conn->error, $logFile);
        }
    }

    $offset += $batchSize;
}

logMessage("Migration completed.", $logFile);
$conn->close();
?>
