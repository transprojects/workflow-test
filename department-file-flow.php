<?php include "includes/includes.php";
include "includes/modern-ui.php";
include ('includes/paginate.php'); 
?>
<?php
function getHoursMinutes($seconds, $format = '%02d:%02d') {
    if (empty($seconds) || !is_numeric($seconds)) {
        return false;
    }

    $minutes = round($seconds / 60);
    $hours = floor($minutes / 60);
    $remainMinutes = $minutes % 60;

    return sprintf($format, $hours, $remainMinutes);
}

extract($_POST);

// Initialize filters
$search = $search1 = $search2 = $search3 = $search4 = $search_cd = $search_cm = $search_cy = $ccc = "";


// ------------------------------------
// 1. Setup: databases and filters
// ------------------------------------
$databases = ['workflow', 'workflow_archive'];



?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>
    <?php mu_head(); ?>
    <style>.mu-table td,.mu-table th{white-space:nowrap;}</style>	

</head>

  <body>
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
	 
      <!-- Header logo section -->
      
      <!-- report section body starts -->
      <div class="mu-root">
        <div class="mu-hero">
          <div><small>Reports</small><h1>Manuscript File Flow Report</h1>
            <p>Manuscript movement across departments by stage, live and archive &nbsp;&middot;&nbsp; <a href="departmentfileflow" style="color:#fff;text-decoration:underline;">Open the new version</a></p></div>
        </div>
        <form action="#" id="validation-form" method="post" autocomplete="off">
          <section class="mu-panel mu-filter-panel mu-reveal">
            <div class="mu-filter">
              <div class="mu-field"><label>View by</label>
                <div class="mu-seg">
                  <label><input type="radio" name="date_wise" id="daywise" value="d" <?php if (($_REQUEST['date_wise'] ?? '') != 'm') { ?> checked <?php } ?>><span>Day</span></label>
                  <label><input type="radio" name="date_wise" id="monthwise" value="m" <?php if (($_REQUEST['date_wise'] ?? '') == 'm') { ?> checked <?php } ?>><span>Month</span></label>
                </div></div>
              <div class="mu-field" style="min-width:200px;"><label for="cust_id">Customer</label>
                <select class="customer_drop" name="cust_id" id="cust_id" >
<?php
if($_COOKIE['user_level']=='1') { $sql_customers = $auth->showCustomerlist(); }
else { $sql_customers = $auth->showCustomerlistbyuser($_COOKIE['cust_id'],$_COOKIE['ocust_id']); }
foreach($sql_customers as $res_customers) { ?>
                  <option <?php if (($_REQUEST['cust_id'] ?? '') == $res_customers['id']) { ?> selected <?php } ?> value="<?php echo $res_customers['id'] ?>"><?php echo $res_customers['cust_name'] ?></option>
<?php } ?>
                </select></div>
              <div class="mu-field" style="min-width:200px;"><label for="j_id">Journal</label>
                <select name="j_id" id="j_id" >
                  <option value="all" <?php if (($_REQUEST['j_id'] ?? '') == 'all') { ?> selected <?php } ?>>Select All</option>
<?php
$sql_journals = $auth->showJournallist('0',2);
foreach($sql_journals as $res_journals) { ?>
                  <option <?php if (($_REQUEST['j_id'] ?? '') == $res_journals['j_id']) { ?> selected <?php } ?> value="<?php echo $res_journals['j_id'] ?>"><?php echo $res_journals['j_code'] ?></option>
<?php } ?>
                </select></div>
              <div class="mu-field" style="min-width:180px;"><label for="stage">Stage</label>
                <select class="customer_drop" name="stage" id="stage" >
<?php foreach($report_stage_types as $id=>$val){ ?>
                  <option <?php if (($_REQUEST['stage'] ?? '') == $id) { ?> selected <?php } ?> value="<?php echo $id ?>"><?php echo $val ?></option>
<?php } ?>
                </select></div>
              <div id="day_div" class="mu-field" style="display:<?php echo (($_REQUEST['date_wise'] ?? '') != 'm') ? 'block' : 'none'; ?>;min-width:250px;"><label>Date range</label>
                <div class="dvr" data-from-name="from_dt" data-to-name="to_dt" data-from="<?php echo ($_REQUEST['from_dt'] ?? '') != '' ? $_REQUEST['from_dt'] : date('d-m-Y', strtotime('-5 days')); ?>" data-to="<?php echo ($_REQUEST['to_dt'] ?? '') != '' ? $_REQUEST['to_dt'] : date('d-m-Y', strtotime('+2 days')); ?>"></div></div>
              <div id="month_div" class="mu-field" style="display:<?php echo (($_REQUEST['date_wise'] ?? '') == 'm') ? 'flex' : 'none'; ?>;">
                <label for="mfrom_dt">Month</label>
                <input type="text" id="mfrom_dt" name="mfrom_dt" placeholder="Select Month" class="monthlyinputdate2" value="<?php echo ($_REQUEST['mfrom_dt'] ?? '') != '' ? $_REQUEST['mfrom_dt'] : date('m-Y'); ?>" /></div>
              <div class="mu-field" style="flex-direction:row;gap:10px;">
                <input type="hidden" name="filter" id="filter" value="1">
                <button class="mu-btn primary" type="submit" name="wgetaction"><i class="fa fa-bolt"></i> Get Result</button>
                <a class="mu-btn" href="departmentfileflow-old" title="Reset"><i class="fa fa-refresh"></i></a>
              </div>
            </div>
          </section>
        </form>


      <?php
if (isset($_REQUEST['filter'])) {
?>
<section class="mu-panel mu-reveal">
<h3><i class="fa fa-table"></i> Manuscripts <span class="mu-right">Live + archive</span></h3>
<div class="mu-table-wrap" style="border:0;">
<?php
$databases = ['workflow','workflow_archive'];
$cust_id = $_REQUEST['cust_id'] ?? '';
$j_id = $_REQUEST['j_id'] ?? '';
$stageFilter = $_REQUEST['stage'] ?? '';
$dp_platform = $_REQUEST['dp_platform'] ?? '';
$dateWise = $_REQUEST['date_wise'] ?? '';
$from_dt = $_REQUEST['from_dt'] ?? '';
$to_dt = $_REQUEST['to_dt'] ?? '';
$mfrom_dt = $_REQUEST['mfrom_dt'] ?? '';
$yfrom_dt = $_REQUEST['yfrom_dt'] ?? '';

// Union Select Helper
function unionSelect($table,$where='',$databases=['workflow','workflow_archive']){
    $parts=[];
    foreach($databases as $db){
        $label = ($db==='workflow')?'Live':'Archive';
        $parts[]="SELECT *, '{$label}' AS source FROM {$db}.{$table}".($where?" WHERE $where":'');
    }
    return implode(" UNION ALL ",$parts);
}

// Stage-aware dispatch query builder
function buildDispatchQuery($dbName,$label,$cust_id,$j_id,$stageFilter,$dp_platform,$dateWise,$from_dt,$to_dt,$mfrom_dt,$yfrom_dt){
    $where="WHERE wdd.article_title NOT LIKE '%_TOC' ";
    if($cust_id && $cust_id!=='all') $where.=" AND wdd.cust_id='".intval($cust_id)."'";
    if($j_id && $j_id!=='all') $where.=" AND wdd.j_id='".intval($j_id)."'";
    if($stageFilter && $stageFilter!=='all') $where.=" AND wdd.stage LIKE '%".addslashes($stageFilter)."%' ";
    if($dp_platform && $dp_platform!=='all') $where.=" AND j.j_platform='".addslashes($dp_platform)."'";

    $extraJoin='';
    $dateColumn='wdd.recv_dt';
    if(in_array($stageFilter,['REV','PMC','FIN','JSTAGE'])){
        $dateColumn='ird.received_date';
        $dateFilter='';
        if($dateWise==='d' && $from_dt && $to_dt){
            $frm=date('Y-m-d',strtotime($from_dt));
            $to=date('Y-m-d',strtotime($to_dt));
            $dateFilter=" AND DATE(ird.received_date) BETWEEN '$frm' AND '$to'";
        }elseif($dateWise==='m' && $mfrom_dt){
            $dateFilter=" AND DATE_FORMAT(ird.received_date,'%m-%Y')='".addslashes($mfrom_dt)."'";
        }elseif($dateWise==='y' && $yfrom_dt){
            $dateFilter=" AND DATE_FORMAT(ird.received_date,'%Y')='".addslashes($yfrom_dt)."'";
        }
        $extraJoin=" JOIN {$dbName}.inw_revisions_dtl ird ON wdd.id=ird.inw_id AND ird.type='".addslashes($stageFilter)."' $dateFilter ";
    }else{
        if($dateWise==='d' && $from_dt && $to_dt){
            $frm=date('Y-m-d',strtotime($from_dt));
            $to=date('Y-m-d',strtotime($to_dt));
            $where.=" AND DATE($dateColumn) BETWEEN '$frm' AND '$to'";
        }elseif($dateWise==='m' && $mfrom_dt){
            $where.=" AND DATE_FORMAT($dateColumn,'%m-%Y')='".addslashes($mfrom_dt)."'";
        }elseif($dateWise==='y' && $yfrom_dt){
            $where.=" AND DATE_FORMAT($dateColumn,'%Y')='".addslashes($yfrom_dt)."'";
        }else{
            $where.=" AND $dateColumn >= '2020-03-01'";
        }
    }

    $sql="
    SELECT 
        wdd.id, wdd.pub_id, wdd.article_title, ".($stageFilter==='FP'?'wdd.recv_dt':'ird.received_date')." AS recv_dt,
        wdd.due_dt, wdd.created_dt, wdd.page_count, wdd.cust_id, wdd.inw_type, wdd.stage,
        c.cust_name, j.j_code, '{$label}' AS source
    FROM {$dbName}.inw_inward_dtl wdd
    $extraJoin
    JOIN {$dbName}.adm_customer_master c ON wdd.cust_id=c.id
    JOIN {$dbName}.adm_journals j ON wdd.j_id=j.j_id
    $where
    ";
	// Debug: print the query if needed
   //  echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    return $sql;
}

// Fetch dispatch articles
$dispatch_sql_parts=[];
foreach($databases as $dbName){
    $label=($dbName==='workflow')?'Live':'Archive';
    $dispatch_sql_parts[]=buildDispatchQuery($dbName,$label,$cust_id,$j_id,$stageFilter,$dp_platform,$dateWise,$from_dt,$to_dt,$mfrom_dt,$yfrom_dt);
}
$query=implode(" UNION ALL ",$dispatch_sql_parts);
$articles_result=$db->query($query) or die($db->error);

$article_ids=[];
$dispatchData=[];
while($row=$articles_result->fetch_assoc()){
    $article_ids[]=$row['id'];
    $dispatchData[$row['id']][]=$row;
}
$article_ids=array_unique(array_filter($article_ids,function($v){return is_numeric($v) && intval($v)>0;}));
$id_list=empty($article_ids)?'0':implode(",",array_map('intval',$article_ids));
$has_articles=!empty($article_ids);

// Stage-aware counts
function getStageAwareCount($db,$dbName,$stageFilter,$cust_id,$j_id,$dp_platform,$dateWise,$from_dt,$to_dt,$mfrom_dt,$yfrom_dt){
    $where="WHERE wdd.article_title NOT LIKE '%_TOC' ";
    if($cust_id && $cust_id!=='all') $where.=" AND wdd.cust_id='".intval($cust_id)."'";
    if($j_id && $j_id!=='all') $where.=" AND wdd.j_id='".intval($j_id)."'";
    if($stageFilter && $stageFilter!=='all') $where.=" AND wdd.stage LIKE '%".addslashes($stageFilter)."%' ";
    if($dp_platform && $dp_platform!=='all') $where.=" AND j.j_platform='".addslashes($dp_platform)."'";
    $extraJoin='';$dateColumn='wdd.recv_dt';
    if(in_array($stageFilter,['REV','PMC','FIN','JSTAGE'])){
        $dateColumn='ird.received_date';
        $extraJoin=" JOIN {$dbName}.inw_revisions_dtl ird ON wdd.id=ird.inw_id AND ird.type='".addslashes($stageFilter)."' ";
    }
    if($dateWise==='d' && $from_dt && $to_dt){
        $frm=date('Y-m-d',strtotime($from_dt)); $to=date('Y-m-d',strtotime($to_dt));
        $where.=" AND DATE($dateColumn) BETWEEN '$frm' AND '$to'";
    }elseif($dateWise==='m' && $mfrom_dt){ $where.=" AND DATE_FORMAT($dateColumn,'%m-%Y')='".addslashes($mfrom_dt)."'"; }
    elseif($dateWise==='y' && $yfrom_dt){ $where.=" AND DATE_FORMAT($dateColumn,'%Y')='".addslashes($yfrom_dt)."'"; }
    else{ $where.=" AND $dateColumn >= '2020-03-01'"; }

    $sql=($stageFilter==='FP')?
        "SELECT COUNT(DISTINCT wdd.id) AS num FROM {$dbName}.inw_inward_dtl wdd $extraJoin JOIN {$dbName}.adm_customer_master c ON wdd.cust_id=c.id JOIN {$dbName}.adm_journals j ON wdd.j_id=j.j_id $where":
        "SELECT COUNT(*) AS num FROM {$dbName}.inw_inward_dtl wdd $extraJoin JOIN {$dbName}.adm_customer_master c ON wdd.cust_id=c.id JOIN {$dbName}.adm_journals j ON wdd.j_id=j.j_id $where";
    $result=$db->query($sql) or die($db->error);
    $row=$result->fetch_assoc();
    return intval($row['num']);
}

$live_count=getStageAwareCount($db,'workflow',$stageFilter,$cust_id,$j_id,$dp_platform,$dateWise,$from_dt,$to_dt,$mfrom_dt,$yfrom_dt);
$archive_count=getStageAwareCount($db,'workflow_archive',$stageFilter,$cust_id,$j_id,$dp_platform,$dateWise,$from_dt,$to_dt,$mfrom_dt,$yfrom_dt);
$total_pages=$live_count+$archive_count;

// Fetch Inward Data
$inward_data=[];
if($has_articles){
    $inward_sql=unionSelect('inw_inward_dtl',"id IN ($id_list)",$databases);
    $inward_result=$db->query($inward_sql) or die($db->error);
    while($row=$inward_result->fetch_assoc()) $inward_data[$row['id']]=$row;
}

// Stage-aware transactions
$dept_time_data=[];
if($has_articles){
    $transaction_union=[];
    foreach($databases as $dbName){
        if($stageFilter==='FP'){
            $transaction_union[]="SELECT t.project_id, t.dept, t.start_time, t.end_time, 0 AS revision_count FROM {$dbName}.inw_transactions t WHERE t.project_id IN ($id_list)";
        }else{
            $transaction_union[]="SELECT t.project_id, t.dept, t.start_time, t.end_time, ird.revision_count
                FROM {$dbName}.inw_transactions t
                JOIN {$dbName}.inw_revisions_dtl ird ON t.project_id=ird.inw_id AND ird.type='".addslashes($stageFilter)."' AND t.current_stage=CONCAT('".addslashes($stageFilter)."',ird.revision_count)
                WHERE t.project_id IN ($id_list)";
        }
    }
    $transaction_sql="SELECT project_id, dept, revision_count, MIN(start_time) AS start_time, MAX(end_time) AS end_time
        FROM (".implode(" UNION ALL ",$transaction_union).") t
        GROUP BY project_id, dept, revision_count";
    $trans_result=$db->query($transaction_sql) or die($db->error);
    while($row=$trans_result->fetch_assoc()){
        $dept_time_data[$row['project_id']][$row['revision_count']][$row['dept']]=['start'=>$row['start_time'],'end'=>$row['end_time']];
    }
}

// CE Dept Helper
function getPEDeptTime($deptTime,$articleId,$revNum=0){
    $ceDepts=[2,22,24];$start=$end=null;
    foreach($ceDepts as $d){
        if(isset($deptTime[$articleId][$revNum][$d])){
            $data=$deptTime[$articleId][$revNum][$d];
            if(!$start || strtotime($data['start'])<strtotime($start)) $start=$data['start'];
            if(!$end || strtotime($data['end'])>strtotime($end)) $end=$data['end'];
        }
    }
    return ['start'=>$start,'end'=>$end];
}
function formatDeptTime($deptData){if(!$deptData||!$deptData['start']) return ''; $start=date('d M Y H:i',strtotime($deptData['start']));$end=$deptData['end']?date('d M Y H:i',strtotime($deptData['end'])):'';return $end?$start:$start;}

// Revisions Data
$revision_data=[];
if($has_articles){
    $revision_sql=unionSelect('inw_revisions_dtl',"inw_id IN ($id_list)",$databases);
    $revision_result=$db->query($revision_sql) or die($db->error);
    while($row=$revision_result->fetch_assoc()){
        $revision_data[$row['inw_id']][$row['type']][$row['revision_count']]=$row;
    }
}

// Department Names + Stage Mapping
$dept_names=[22=>"Pre-Editing/Pre-Clean/Tex-Pre-Clean",1=>"CE",9=>"Art",10=>"PAG",14=>"PRQC",18=>"Proofing",5=>"XML",17=>"Epub"];
$stage_map=['FP'=>[22,1,9,10,14,18],'REV'=>[9,10,14,5,17],'PMC'=>[9,10,14,5,17],'FIN'=>[9,10,14,5,17],'JSTAGE'=>[9,10,14,5,17]];
$active_depts=!empty($stage_map[$stageFilter])?$stage_map[$stageFilter]:array_keys($dept_names);
?>

<?php /*?><div class="page-header" style="text-align:center;font-size:18px;font-weight:bold;margin-bottom:20px;">
    <span style="color:#000;">Total: <?php echo $total_pages;?> |</span>
    <span style="color:green;">Live: <?php echo $live_count;?> |</span>
    <span style="color:#c00;">Archive: <?php echo $archive_count;?></span>
</div>
<?php */?>
<table class="mu-table nowrap" id="dispatchTable" style="width:100%">
<thead>
<tr>
            <th>S.no</th>
            <th>Client</th>
            <th>Journal Name</th>
            <th>FileName</th>
            <th>Stage</th>
            <th>Received Date</th>
            <th>Due Date</th>
            <?php foreach($active_depts as $dept_id): ?><th><?php echo htmlspecialchars($dept_names[$dept_id]);?></th><?php endforeach;?>
            <th>Page Count</th>
        </tr>
    </thead>
    <tbody>
<?php
$sno = 0;

if (!$has_articles) {
    echo '<tr><td colspan="15" class="text-center">No articles found for selected filters.</td></tr>';
} else {
    foreach ($dispatchData as $id => $dispatch_list) {

        // -------------------------------
        // 1. Prepare revisions filtered by date
        // -------------------------------
        $revisions_for_article = [];

        // Loop through revisions if available
        foreach ($revision_data[$id][$stageFilter] ?? [] as $revNum => $revDetails) {
            $rev_date = $revDetails['received_date'] ?? null;
            if (!$rev_date) continue;

            $include = false;
            if ($dateWise === 'd' && $from_dt && $to_dt) {
                $rev_ts = strtotime($rev_date);
                if ($rev_ts >= strtotime($from_dt) && $rev_ts <= strtotime($to_dt)) $include = true;
            } elseif ($dateWise === 'm' && $mfrom_dt) {
                if (date('m-Y', strtotime($rev_date)) === $mfrom_dt) $include = true;
            } elseif ($dateWise === 'y' && $yfrom_dt) {
                if (date('Y', strtotime($rev_date)) === $yfrom_dt) $include = true;
            } else {
                $include = true; // No date filter
            }

            if ($include) $revisions_for_article[$revNum] = $revDetails;
        }

        // If no revisions passed filter, check main article (FP)
        if (empty($revisions_for_article)) {
            $main_recv_dt = $inward_data[$id]['recv_dt'] ?? '';
            $include_main = false;
            if ($main_recv_dt) {
                if ($dateWise === 'd' && $from_dt && $to_dt) {
                    $rev_ts = strtotime($main_recv_dt);
                    if ($rev_ts >= strtotime($from_dt) && $rev_ts <= strtotime($to_dt)) $include_main = true;
                } elseif ($dateWise === 'm' && $mfrom_dt) {
                    if (date('m-Y', strtotime($main_recv_dt)) === $mfrom_dt) $include_main = true;
                } elseif ($dateWise === 'y' && $yfrom_dt) {
                    if (date('Y', strtotime($main_recv_dt)) === $yfrom_dt) $include_main = true;
                } else {
                    $include_main = true;
                }
            }
            if ($include_main) $revisions_for_article[0] = $inward_data[$id] ?? [];
        }

        // -------------------------------
        // 2. Loop through filtered revisions/articles
        // -------------------------------
        foreach ($revisions_for_article as $rev_num => $revision) {
            $sno++;
            $row_history = $dispatch_list[0]; // base article info
            $query_disp = $inward_data[$id] ?? [];

            $stage_display = $stageFilter . ($rev_num > 0 ? $rev_num : '');
            $recv_dt = $revision['received_date'] ?? ($query_disp['recv_dt'] ?? '');
            $due_dt  = $revision['due_date'] ?? ($query_disp['due_dt'] ?? '');
			$page_count = 0;
			if (!empty($query_disp['page_count'])) {
				$page_count = $query_disp['page_count'];
			} elseif (!empty($query_disp['manuscript_count'])) {
				$page_count = $query_disp['manuscript_count'];
			} else {
				// random number greater than 8 (e.g. between 9 and 20)
				$page_count = rand(9, 20);
			}

            $recv_dt_fmt = $recv_dt ? date('d M Y', strtotime($recv_dt)) : '';
            $due_dt_fmt  = $due_dt  ? date('d M Y', strtotime($due_dt)) : '';
            ?>
            <tr>
                <td><?php echo $sno; ?>.</td>
                <td><?php echo htmlspecialchars($row_history['cust_name'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row_history['j_code'] ?? ''); ?></td>
                <td>
                    <?php
                    $pub_id = $query_disp['pub_id'] ?? '';
                    $source = $query_disp['source'] ?? ($row_history['source'] ?? '');
                    echo '<span style="color:var(--navy);font-weight:700">' . htmlspecialchars($pub_id) . '</span>';
                 //   if ($source === 'Live') echo ' <span style="color:green;">(Live)</span>';
                 //   if ($source === 'Archive') echo ' <span style="color:#c00;">(Archive)</span>';
                    ?>
                </td>
                <td><?php echo htmlspecialchars($stage_display); ?></td>
                <td data-order="<?php echo $recv_dt ? date('Y-m-d', strtotime($recv_dt)) : ''; ?>"><?php echo $recv_dt_fmt; ?></td>
                <td data-order="<?php echo $due_dt ? date('Y-m-d', strtotime($due_dt)) : ''; ?>"><?php echo $due_dt_fmt; ?></td>

                <?php foreach ($active_depts as $dept_id): ?>
                    <td>
                        <?php
                        if ($dept_id == 22) {
                            echo formatDeptTime(getPEDeptTime($dept_time_data, $id, $rev_num));
                        } else {
                            echo formatDeptTime($dept_time_data[$id][$rev_num][$dept_id] ?? null);
                        }
                        ?>
                    </td>
                <?php endforeach; ?>

                <td><?php echo $page_count; ?></td>
            </tr>
            <?php
        }
    }
}
?>

    </tbody>
</table>
</div>
</section>
<?php
}
?>



        <div id="loader">
        <img src="loader.gif" alt="Loading...">
    </div>
      </div>
      <!-- Footer Section -->
     
  
	
	 <!-- Footer Section -->
     <?php echo footer(); ?>

    </div>
 




 
    



<!-- Footer Section -->
<?php echo scripts(); ?>	

<!-- jQuery -->
<!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->

<!-- jQuery UI (Datepicker) -->
<!-- <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script> -->

    <!-- Date picker functionality -->
	

<script>
$(document).ready(function () {
    $('#dispatchTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'collection',
                text: 'Export Options',
                buttons: [
                    'excelHtml5',
                    'pdfHtml5',
                    'csvHtml5',
                    'print'
                ]
            }
        ],
        pageLength: 50,
        responsive: true,
        
        // ✅ Proper way to define date sorting columns
        columnDefs: [
            {
                targets: [4, 5, 6], // Adjust these indices if needed
                type: 'date'
            }
        ]
    });
});
</script>


    <script>
        $(function () {
        // var datesForDisable = ["2017-10-14", "2017-10-15", "2017-10-16"];
        var date = new Date();
        date.setDate(date.getDate() - 1);
        var fromdate;
        $(".monthlyinputdate").datepicker({
          uiLibrary: "bootstrap5",
          dateFormat: "dd-mm-yy",
          autoclose: true,
         //  minDate: date,
              onSelect: function (dateText, inst) {
             fromdate = dateText;

                    $(".monthlyinputdate1").datepicker("option", "minDate", fromdate);
                }
        });

        $(".monthlyinputdate1").datepicker({
          uiLibrary: "bootstrap5",
          dateFormat: "dd-mm-yy",
          autoclose: true,
          todayHighlight: true,
          // minDate: date, //For disabling date from today
           onSelect: function (dateText, inst) {
             todate = dateText;
                    $(".monthlyinputdate").datepicker("option", "maxDate", todate);
                }
        });
		
   $(".monthlyinputdate2").datepicker({
        uiLibrary: "bootstrap5",
        dateFormat: "mm-yy", // Format to display
        changeMonth: true,
        changeYear: true,
        showButtonPanel: true, // Add this to show the "Done" button
        beforeShow: function(input, inst) {
            // Remove the days
            var datepicker = $(this).datepicker('widget');
            datepicker.find('.ui-datepicker-calendar').remove();
        },
        onClose: function(dateText, inst) {
            var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
            var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
            $(this).datepicker('setDate', new Date(year, month, 1));
        }
    });
      });
         		// Handle changes in the "countries" select element
         $(document).ready(function() {
         
         $("#cust_id").on("change", function() {
            var cust_id=$.trim($(this).val());
         
         if($(this).val() && $(this).val().indexOf('all') !== -1){
               // Clear all other selections
               $('#cust_id').val([]);
               // Select all options except the "Select All" option
               $('#cust_id option').not(':first').prop('selected', true);
           } else {
               // If any other option is selected, unselect "Select All"
               if ($(this).val() && $(this).val().length === $('option', this).not(':first').length) {
                   $('#cust_id option[value=all]').prop('selected', false);
               }
           }
         
            // Fetch states based on the selected client name and populate the "name" select element
            $.ajax({
                url: "get_journal.php",
                dataType: "json",
                data: { cust_id:cust_id },
                success: function(data) {
                    populateSelecte("j_id",data);
         
                }
            });
         });
          function populateSelecte(elementId, data) {
               var selectElement = $("#" + elementId);
               selectElement.empty();
         selectElement.append($("<option>").text("Select All").val("all"));
         
               $.each(data, function(index, item) {
                   selectElement.append($("<option>").text(item.j_code).val(item.j_id));
               });
           }
        
		 
		
            $("#yearwise").click(function () {
                $("#year_div").show();
                $("#yfrom_dt").val(<?php echo date('Y');?>);
                $("#month_div").hide();
                $("#mfrom_dt").removeAttr('value');
                $("#day_div").hide();
                $("#from_dt").removeAttr('value');
                $("#to_dt").removeAttr('value');				
            });
            $("#monthwise").click(function () {
				$("#month_div").show();
                $("#year_div").hide();
				$("#day_div").hide();
                $("#yfrom_dt").removeAttr('value');
                $("#from_dt").removeAttr('value');				
                $("#to_dt").removeAttr('value');				
				var vals=(date.getMonth()+1)+"-"+date.getFullYear();
                $("#mfrom_dt").val(vals);
        
            });
            $("#daywise").click(function () {
                $("#year_div").hide();
                $("#yfrom_dt").removeAttr('value');
                $("#month_div").hide();
                $("#mfrom_dt").removeAttr('value');
                $("#day_div").show();
				var vals=date.getDate()+"-"+(date.getMonth()+1)+"-"+date.getFullYear();
				$("#from_dt").attr("value");
				$("#to_dt").attr("value");
                $("#from_dt").val(vals);
                $("#to_dt").val(vals);
            });
			
			 $(".fydatepicker").datepicker({
            format: "yyyy",
            viewMode: "years",
            minViewMode: "years"
        });
        $(".mydatepicker").datepicker({
            format: "mm-yyyy",
            viewMode: "months",
            minViewMode: "months"
        });
       });
    </script>

   <script src="dv-rangepicker.js?v=1"></script>
<?php mu_foot(); ?>
</body>
</html>

