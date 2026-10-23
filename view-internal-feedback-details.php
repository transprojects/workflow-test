<?php include "includes/includes.php";
require_once __DIR__.'/includes/form_ui.php'; ?>
<?php
/////////////// Update completed ///////////////

if(isset($_REQUEST['id']))
{
$query = "
    SELECT 
        f.*,
        d.dept_name,
        d.dept_code,
        c.cust_name,
        j.j_code,
        u.full_name AS created_by,
        ua.full_name AS approved_by_name
    FROM inw_client_feedback f
    LEFT JOIN adm_dept_master d 
        ON f.dept_id = d.dept_code
    LEFT JOIN adm_customer_master c 
        ON f.cust_id = c.id
    LEFT JOIN adm_journals j 
        ON f.j_id = j.j_id
    LEFT JOIN users u 
        ON f.u_id = u.id
    LEFT JOIN users ua
        ON f.pa_approved_by = ua.id
    WHERE f.f_id = '".intval($_REQUEST['id'])."'";


$result = $db->query($query);
$app_res = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php fu_head('fu_none'); fu_card_css(); ?>
    <style>
  #ea-req{display:none !important;} .fu-card > .container{max-width:none !important;padding:0 !important;margin:0 !important;} .fu-card .row{margin:0;}
  .fu-card .feedback-cards{gap:16px;}
  .fu-card .feedback-card{box-shadow:none !important;border:1px solid #e4e9ef;border-radius:14px !important;background:#fbfcfe !important;padding:18px 20px !important;}
  .fu-card .feedback-card h2{font-size:15px !important;font-weight:700;color:#14304a !important;border-bottom:1px solid #e4e9ef !important;padding:0 0 10px 10px !important;margin-bottom:12px !important;border-left:4px solid #1b6ca8;}
  .fu-card .feedback-card p{color:#33475b !important;}
  .fu-card .feedback-card.rca{border-left:5px solid #d64545;} .fu-card .feedback-card.ca{border-left:5px solid #1f9d6a;} .fu-card .feedback-card.pa{border-left:5px solid #1b6ca8;}
  .fu-card .feedback-card table{width:100%;border-collapse:separate;border-spacing:0;font-size:14px;} .fu-card .feedback-card th{background:#eef3f8;padding:10px 12px;color:#14304a;border:0;} .fu-card .feedback-card td{padding:10px 12px;border:0;border-bottom:1px solid #eef1f5;background:#fff;}
  @media(max-width:800px){.fu-card .feedback-cards{grid-template-columns:1fr !important;} .fu-card .feedback-card{grid-column:auto !important;}}
</style>	

    <!-- Data Table CSS --->
    <style>
    .overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
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
}

.loader {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 2s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.ui-widget-content {
  background-color:white !important;
}
.ui-state-active, .ui-widget-content .ui-state-active, .ui-widget-header .ui-state-active, a.ui-button:active, .ui-button:active, .ui-button.ui-state-active:hover {
    border: 1px solid #003eff !important;
    background: #dd5c1c !important;
    font-weight: normal !important;
    color: #ffffff !important;
}
.ui-state-default .ui-icon {
     background-image:none !important; 
}

.feedback-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
  }

  .feedback-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    opacity: 0;
    transform: translateY(30px);
    animation: fadeInUp 0.6s ease forwards;
  }

  .feedback-card:nth-child(1) { animation-delay: 0.2s; }
  .feedback-card:nth-child(2) { animation-delay: 0.4s; }
  .feedback-card:nth-child(3) { animation-delay: 0.6s; }
  .feedback-card:nth-child(4) { animation-delay: 0.8s; }
  .feedback-card:nth-child(5) { animation-delay: 1s; grid-column: span 2; }

  @keyframes fadeInUp {
    to { opacity: 1; transform: translateY(0); }
  }

  .feedback-card h2 {
    font-size: 18px;
    margin-bottom: 10px;
    color: #333;
    border-bottom: 2px solid #eee;
    padding-bottom: 6px;
  }

  .feedback-card p {
    margin: 5px 0;
    color: #555;
    line-height: 1.5;
  }

  .rca { border-left: 5px solid #e74c3c; }
  .ca  { border-left: 5px solid #27ae60; }
  .pa  { border-left: 5px solid #2980b9; }
      </style>
</head>

<body>
  <div id="loader" class="loader-container">
        <div class="loader"></div>
    </div>
    <div id="overlay" class="overlay"></div>
    <div class="wholeouter">
    <?php include("includes/top-header.php");?>


    <div class="container-fluid" style="justify-content: space-between;">
    <div div class="row pt-4 d-flex align-items-center flex-wrap">
          <div class="col-8 col-lg-10">
			<p class="articleheading ms-4">Internal Feedback View</p>
          </div>
          <div class="col-4 col-lg-2">
            <div class="row d-flex justify-content-end pe-2">
              <div class="col-3 articlebtns ">
				  <a href="<?= $_COOKIE['set_url']; ?>/internalfeedback">
                  <div  class="back_icon"><img src="<?php echo $_COOKIE['set_url'];?>/assets/images/backarrow.svg"></div>
                </a>
              </div>
            </div>
          </div>
        </div> 
  </div>


      <?php fu_hero(fu_ui_basic('Internal Feedback', 'Internal Feedback &rsaquo; View')); ?>
      <div class="fu-wrap">
  <div class="fu-card">
            <div class="container mt-4 ">
              <div class="row mt-1 g-4 ">

    <div class="feedback-cards">
      <!-- Article Details -->
     <div class="feedback-card">
	  <h2>Article Details</h2>
	  <p><strong>Article ID:</strong> <?= htmlspecialchars($app_res['article']) ?></p>
	  <p><strong>Customer:</strong> <?= htmlspecialchars($app_res['cust_name']) ?></p>
	  <p><strong>Journal:</strong> <?= htmlspecialchars($app_res['j_code']) ?></p>
	  <p><strong>Stage:</strong> <?= htmlspecialchars($app_res['stage']) ?></p>
	  <p><strong>Department:</strong> <?= htmlspecialchars($app_res['dept_name']) ?></p>
	  <p><strong>Created By:</strong> <?= htmlspecialchars($app_res['created_by_name']) ?></p>
	  <p><strong>Received Date:</strong> <?= htmlspecialchars($app_res['recv_dt']) ?></p>
	  <p><strong>Created On:</strong> <?= htmlspecialchars($app_res['created_on']) ?></p>
	</div>


      <!-- Client Message -->
      <div class="feedback-card">
		  <h2>Internal Feedback</h2>
		  <div><?= $app_res['message']; ?></div>
	  </div>

      <!-- RCA -->
		<?php if (!empty($app_res['rca'])) { ?>
		<div class="feedback-card rca">
		  <h2>Root Cause (RCA)</h2>
		  <p><?= htmlspecialchars($app_res['rca']); ?></p>
		</div>
		<?php } ?>


    </div>

    <!-- Attachments -->
    <?php
$uploaded_files = json_decode($app_res['uploads'], true) ?: [];

if (!empty($uploaded_files)) {
	?>
	<div class="feedback-card mt-4" style="grid-column: span 2;">
	  <h2>Attachments</h2>
	  <table class="table table-bordered">
		<thead>
		  <tr>
			<th>S.no</th>
			<th>File Name</th>
		  </tr>
		</thead>
		<tbody>
		<?php foreach ($uploaded_files as $key => $file) { ?>
		  <tr>
			<td><?= $key + 1 ?></td>
			<td>
			  <a href="<?= $_COOKIE['set_url'].'/'.htmlspecialchars($file); ?>" target="_blank">
				<?= basename($file); ?>
			  </a>
			</td>
		  </tr>
		<?php } ?>
		</tbody>
	  </table>
	</div>
	<?php } ?>

  </div>
  </div>
  </div>
</div>
      <?php echo scripts(); ?>	
      <?php if (isset($_GET['msg'])){ ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var msg = "<?php echo htmlspecialchars($_GET['msg']); ?>";
        if (msg) {
            swal("Saved", msg, "success");
        }
    });
</script><?php
}?>
       <!-- Footer Section -->
       <?php echo footer(); ?>
   
   

</body>
</html>
