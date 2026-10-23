<?php include "includes/includes.php";?>


<?php 
$sql_checklist=mysqli_query($db,"SELECT 
    i.inw_id,
    iw.pub_id,
    iw.article_title,
    d.dept_name,
    i.stage,
    c.description AS checklist_item,
    i.submited_value,
    CONCAT(u.user_name, ' (', u.full_name, ')') AS created_by,
    i.created_on,
    r.revision_count
FROM inw_to_checklist i
JOIN jbm_checklist c ON i.cl_id = c.cl_id
JOIN adm_dept_master d ON i.dept_id = d.dept_code
JOIN users u ON i.created_by = u.id
JOIN inw_inward_dtl iw ON i.inw_id = iw.id
LEFT JOIN inw_revisions_dtl r ON i.inw_id = r.inw_id where i.inw_id='".$_REQUEST['aid']."'
ORDER BY  d.dept_name,i.cl_id,i.stage, i.created_on");
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>	

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
      </style>
  </head>

  <body>
  <div id="loader" class="loader-container">
        <div class="loader"></div>
    </div>
    <div id="overlay" class="overlay"></div>
    <div class="wholeouter">
    <?php include("includes/top-header.php");?>


      <!-- form section  -->
    <div class="container-fluid" style="justify-content: space-between;">
    <div div class="row pt-4 d-flex align-items-center flex-wrap">
          <div class="col-8 col-lg-10">
            <p class="articleheading ms-4">Article Checklist By Users</p>
          </div>
          <div class="col-4 col-lg-2">
            <div class="row d-flex justify-content-end pe-2">
              <div class="col-3 articlebtns ">
                <a href="<?php echo $_COOKIE['set_url'];?>/currentwork">
                  <div  class="back_icon"><img src="<?php echo $_COOKIE['set_url'];?>/assets/images/backarrow.svg"></div>
                </a>
              </div>
            </div>
          </div>
        </div> 
  </div>
<?php 
$data = [];
while($row = mysqli_fetch_assoc($sql_checklist)) {
    $data[$row['dept_name']][$row['stage']][$row['created_by']][] = $row;
}
?>
      <div class="container addarticleformouter">
        <div class="container addarticleforminner m-5">
          
		  
<div class="container my-4">

  <?php foreach($data as $dept => $stages): ?>
    <div class="card shadow-lg mb-4 animate__animated animate__fadeIn">
      <div class="card-header bg-primary text-white">
        <h4><?php echo  $dept ?></h4>
      </div>
      <div class="card-body">
        <?php foreach($stages as $stage => $users): ?>
          <div class="mb-3">
            <h5 class="text-info"><?php echo  $stage ?></h5>
            <?php foreach($users as $user => $items): ?>
              <div class="card mb-2 border-info animate__animated animate__zoomIn">
                <div class="card-header bg-light">
                  <strong>User:</strong> <?php echo  $user ?>
                </div>
                <ul class="list-group list-group-flush">
                  <?php foreach($items as $item): ?>
                    <li class="list-group-item d-flex justify-content-between">
                      <?php echo  $item['checklist_item'] ?> 
                      <span class="badge bg-<?php echo  $item['submited_value']=='Yes'?'success':'danger' ?>">
                        <?php echo  $item['submited_value'] ?>
                      </span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Add Animate.css for smooth entry -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
      <!-- upload section -->
    
        </div>
      </div>
      <?php echo scripts(); ?>	

       <!-- Footer Section -->
       <?php echo footer(); ?>
     
 
  </body>
</html>
