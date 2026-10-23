<?php
include "includes/conn.php";
$token = $_GET['token'] ?? '';
$message = '';
$status_class = '';
$row = null;

// Fetch version based on token
if($token){
    $qry = mysqli_query($db, "SELECT * FROM journal_spec_history WHERE approve_token='".mysqli_real_escape_string($db,$token)."' LIMIT 1");
    if(mysqli_num_rows($qry)){
        $row = mysqli_fetch_assoc($qry);
        $history_id = $row['history_id'];
        $detail_id = $row['journal_spec_details_id'];
    } else {
        $message = "Invalid or expired token!";
        $status_class = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Journal Specification Version Approval</title>
    <?php echo styles(); ?>
    <style>
        body { background: #f4f4f4; font-family: Arial, sans-serif; }
        .approval-container { max-width: 700px; margin: 50px auto; background: #fff; border-radius: 8px; padding: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .logo img { max-height: 60px; margin-bottom: 20px; }
        .message { font-size: 18px; margin: 20px 0; padding: 15px; border-radius: 5px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .version-content { text-align: left; padding: 15px; border:1px solid #ccc; border-radius:5px; background:#f9f9f9; margin-bottom:20px; }
        .version-content img { max-width: 100%; margin-top: 10px; }
        .btn-group button { margin-right: 10px; padding:10px 20px; font-size:16px; border:none; border-radius:5px; cursor:pointer; }
        .btn-approve { background: #28a745; color:#fff; }
        .btn-reject { background: #dc3545; color:#fff; }
        .footer { margin-top: 40px; font-size: 12px; color: #999; text-align:center; }
    </style>
</head>
<body>

<div class="approval-container">
    <div class="logo">
        <img src="<?php echo $_COOKIE['set_url'];?>/assets/images/xpertworkflow.svg" alt="Workflow Logo">
    </div>

    <?php if($message): ?>
        <div class="message <?php echo $status_class; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if($row && $status_class==''): ?>
        <h3>Approve / Reject Journal Specification Element Version</h3>
        <div class="version-content">
            <strong>Version:</strong> <?php echo $row['version']; ?><br>
            <strong>Description:</strong>
            <p><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
            <?php if(!empty($row['image_path'])): ?>
                <img src="<?php echo $row['image_path']; ?>" alt="Element Image">
            <?php endif; ?>
        </div>

        <div class="btn-group">
            <button type="button" id="approveBtn" class="btn-approve">Approve</button>
            <button type="button" id="rejectBtn" class="btn-reject">Reject</button>
        </div>
    <?php endif; ?>

    <div class="footer">
        &copy; <?php echo date('Y'); ?> Workflow System. All rights reserved.
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){
    function sendAction(actionType){
        $.ajax({
            url: 'approve_reject_action.php',
            type: 'POST',
            data: { token: '<?php echo $token; ?>', action: actionType },
            dataType: 'json',
            success: function(res){
                Swal.fire({
                    title: res.title,
                    text: res.message,
                    icon: res.icon,
                    confirmButtonText: 'OK'
                }).then(() => {
                    if(res.icon === 'success') location.reload();
                });
            },
            error: function(){
                Swal.fire('Error','Something went wrong!','error');
            }
        });
    }

    $('#approveBtn').click(function(){ sendAction('approve'); });
    $('#rejectBtn').click(function(){ sendAction('reject'); });
});
</script>

</body>
</html>
