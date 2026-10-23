<?php
$vi_id = ''; // Replace with the actual vi_id you want to delete
$dsn = 'mysql:host=localhost;dbname=workflow;charset=utf8mb4'; // Update with your database details
$username = 'root'; // Update with your username
$password = 'root@123'; // Update with your password

try {
    // Create a new PDO instance
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Step 1: Delete from inw_transactions
    $stmt1 = $pdo->prepare("
        DELETE it 
        FROM inw_transactions it
        JOIN inw_inward_dtl ind ON it.project_id = ind.id
        JOIN inw_volume_issues mt ON ind.v_issue_id = mt.vi_id
        WHERE mt.vi_id = :vi_id
    ");
    $stmt1->execute([':vi_id' => $vi_id]);

    // Step 2: Delete from inw_dispatch_history
    $stmt2 = $pdo->prepare("
        DELETE dh 
        FROM inw_dispatch_history dh
        JOIN inw_inward_dtl ind ON dh.a_id = ind.id
        JOIN inw_volume_issues mt ON ind.v_issue_id = mt.vi_id
        WHERE mt.vi_id = :vi_id
    ");
    $stmt2->execute([':vi_id' => $vi_id]);

    // Step 3: Delete from inw_inward_dtl
    $stmt3 = $pdo->prepare("
        DELETE ind 
        FROM inw_inward_dtl ind
        JOIN inw_volume_issues mt ON ind.v_issue_id = mt.vi_id
        WHERE mt.vi_id = :vi_id
    ");
    $stmt3->execute([':vi_id' => $vi_id]);

    // Step 4: Delete from inw_issue_dispatch_history
    $stmt4 = $pdo->prepare("
        DELETE idh 
        FROM inw_issue_dispatch_history idh
        JOIN inw_volume_issues mt ON idh.vi_id = mt.vi_id
        WHERE mt.vi_id = :vi_id
    ");
    $stmt4->execute([':vi_id' => $vi_id]);

    // Step 5: Delete from inw_issue_corrections
    $stmt5 = $pdo->prepare("
        DELETE ic 
        FROM inw_issue_corrections ic
        JOIN inw_volume_issues mt ON ic.vi_id = mt.vi_id
        WHERE mt.vi_id = :vi_id
    ");
    $stmt5->execute([':vi_id' => $vi_id]);

    // Step 6: Finally, delete from inw_volume_issues
    $stmt6 = $pdo->prepare("DELETE FROM inw_volume_issues WHERE vi_id = :vi_id");
    $stmt6->execute([':vi_id' => $vi_id]);

    // Commit the transaction
    $pdo->commit();
    echo "Records deleted successfully.";
} catch (Exception $e) {
    // Rollback the transaction if something fails
    $pdo->rollBack();
    echo "Failed to delete records: " . $e->getMessage();
}
?>
