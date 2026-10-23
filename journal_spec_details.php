<?php include "includes/includes.php"; ?>

<?php 
if(isset($_REQUEST['id'])) {
    // Get Inward details
    $result = $db->query("SELECT * FROM inw_inward_dtl WHERE id = ".$_REQUEST['id']." ");     
    if($result->num_rows > 0) {
        $app_res = $result->fetch_assoc();
    }

    // Get the Client Name
    $cust_q = "SELECT cust_name,cust_id,id FROM `adm_customer_master` WHERE id = ".$app_res['cust_id'].";";
    $customer_info = $db->query($cust_q)->fetch_assoc();

    // Get the Journal Name
    $journal_q = "SELECT `j_id`,`j_name`,j_code,`cs_id` FROM `adm_journals` WHERE j_id = ".$app_res['j_id'].";";
    $journal_info = $db->query($journal_q)->fetch_assoc();
}
?>

<style>
.table {
  border-collapse: collapse;
  width: 100%;
  font-size: 14px;
}
.table th, .table td {
  border: 1px solid #ccc;
  padding: 8px;
  text-align: left;
}
.table th {
  background: #f2f2f2;
}
.rounded-row td {
  background-color: #fff4cc !important;
  font-weight: 600;
}
.table img {
  border-radius: 6px;
  border: 1px solid #ddd;
}
.version-tag {
  color: #d35400;
  font-size: 12px;
  font-weight: bold;
  margin-left: 3px;
}

/* Tab Customization */
.nav-tabs {
    display: flex;
    justify-content: center;
    margin-bottom: 20px;
    border-bottom: none;
}
.nav-tabs li {
    margin: 0 5px;
    border-radius: 5px;
}
.nav-tabs li a {
    color: #fff;
    font-weight: 600;
    display: block;
    padding: 10px 25px;
    border-radius: 5px;
    text-align: center;
    transition: all 0.3s ease;
}

/* Gradient colors */
.nav-tabs li:nth-child(1) a {
    background: linear-gradient(45deg, #f39c12, #f1c40f);
}
.nav-tabs li:nth-child(2) a {
    background: linear-gradient(45deg, #27ae60, #2ecc71);
}
.nav-tabs li:nth-child(3) a {
    background: linear-gradient(45deg, #2980b9, #3498db);
}
.nav-tabs li:nth-child(4) a {
    background: linear-gradient(45deg, #e84393, #a29bfe);
}

/* Hover effect */
.nav-tabs li a:hover {
    filter: brightness(1.15);
    text-decoration: none;
}

/* Active tab highlight */
.nav-tabs li.active a {
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

/* Highlight updated versions */
.rounded-row {
  background-color: #fff6e0 !important;
}
.version-tag {
  color: #d9534f;
  font-weight: bold;
}
</style>

<div class="page-content">
  <div class="page-header">
    <h5 style="text-align:center;color:red;"> <?php echo $app_res['pub_id']; ?></h5> 
  </div>
  <div class="row">
    <div class="col-xs-12">
      <div id="timeline-1">
        <div class="row">
          <div class="col-xs-12 col-sm-10 col-sm-offset-1" style="width:100%;margin-left:0;">
            
            <?php 
            $cs_id = $journal_info['cs_id'];
            $categories = ['Front Matter', 'Body Matter', 'Back Matter','Specific Style Points'];
            ?>

            <h4 style="text-align:center;color:#2b5797;">
              Journal Specifications - <?php echo htmlspecialchars($journal_info['j_name']); ?>
            </h4>

            <!-- 🔹 Tab Headers -->
            <ul class="nav nav-tabs" role="tablist">
              <?php foreach ($categories as $index => $category): ?>
                <li role="presentation" class="<?php echo $index === 0 ? 'active' : ''; ?>">
                  <a href="#tab_<?php echo $index; ?>" aria-controls="tab_<?php echo $index; ?>" role="tab" data-toggle="tab">
                    <?php echo htmlspecialchars($category); ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>

            <!-- 🔹 Tab Content -->
            <div class="tab-content">
              <?php foreach ($categories as $index => $category): ?>
                <div role="tabpanel" class="tab-pane fade <?php echo $index === 0 ? 'in active' : ''; ?>" id="tab_<?php echo $index; ?>">
                  <?php 
                  $query = "
                    SELECT jsd.*, je.element_name ,u.full_name
                    FROM journal_spec_details jsd
                    LEFT JOIN journal_spec_master je ON je.js_id = jsd.element_id
					LEFT JOIN users u ON u.id = jsd.updated_by
                    WHERE jsd.cs_id = $cs_id 
                      AND je.category = '".mysqli_real_escape_string($db, $category)."'
                    ORDER BY jsd.element_id ASC
                  ";
                  $result = $db->query($query);
                  ?>

                  <?php if($result && $result->num_rows > 0): ?>
                    <table class="table table-bordered table-striped">
                      <thead>
                        <tr>
                          <th style="width:20%;">Element</th>
                          <th>Description</th>
                          <th style="width:10%;">Image</th>
                          <th style="width:10%;">Version</th>
                          <th style="width:15%;">Updated By</th>
                          <th style="width:15%;">Updated At</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                          <?php 
                            $highlight = ($row['version'] > 1) ? 'rounded-row' : '';
                            $updated_tag = ($row['version'] > 1) ? '<span class="version-tag">(Updated)</span>' : '';
                          ?>
                          <tr class="<?php echo $highlight; ?>">
                            <td><?php echo htmlspecialchars($row['element_name']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($row['description'])); ?></td>
							<td style="text-align:center;">
								<?php if(!empty($row['image_path'])): ?>
									<a href="javascript:void(0);" 
									onclick="showImageModal('<?php echo $_COOKIE['set_url'];?>/<?php echo $row['image_path']; ?>')">
										<i class="fa fa-picture-o" style="font-size:22px;color:#2b5797;cursor:pointer;"></i>
									</a>

								<?php else: ?>
									<span style="color:#999;">No Image</span>
								<?php endif; ?>
							</td>

                            <td style="text-align:center;">
                              v<?php echo $row['version']; ?> <?php echo $updated_tag; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td><?php echo date('d-m-Y H:i', strtotime($row['updated_at'])); ?></td>
                          </tr>
                        <?php endwhile; ?>
                      </tbody>
                    </table>
                  <?php else: ?>
                    <p style="text-align:center;color:#999;">No specifications found under this category.</p>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div><!-- tab-content -->

          </div>
        </div>
      </div>
    </div>
  </div>
</div>


<!-- 🔹 Ensure Bootstrap JS for tabs -->
<script>
$(document).ready(function(){
    $('.nav-tabs a:first').tab('show'); // ✅ Make first tab active on load
    $('.nav-tabs a').click(function (e) {
        e.preventDefault();
        $(this).tab('show');
    });
});
</script>
<script>
function showImageModal(imgUrl) {
    document.getElementById('previewImg').src = imgUrl;
    document.getElementById('imagePreviewOverlay').style.display = 'block';
}

function hideImageOverlay() {
    document.getElementById('imagePreviewOverlay').style.display = 'none';
}
</script>


<style>
#imagePreviewOverlay {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.85);
    z-index: 99999; /* Higher than any modal */
    text-align: center;
    padding-top: 50px;
}
#imagePreviewOverlay img {
    max-width: 90%;
    max-height: 85%;
    border: 3px solid #fff;
    border-radius: 8px;
}
#imagePreviewOverlay span {
    color: #fff;
    font-size: 22px;
    position: absolute;
    top: 20px;
    right: 35px;
    cursor: pointer;
}
</style>

<div id="imagePreviewOverlay">
    <span onclick="hideImageOverlay()">&times;</span>
    <img id="previewImg">
</div>
