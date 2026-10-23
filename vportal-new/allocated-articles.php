<?php include "includes/includes.php";?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>	
    <?php include_once __DIR__ . '/includes/vp-ui.php'; vp_head(); ?>
    <link rel="stylesheet" href="dv-rangepicker.css?v=1">
    <style>
      .alert{max-width:520px;margin:10px auto;}
      .af-card a.vp-start,.vp-start{display:inline-flex !important;align-items:center;gap:6px;height:34px;padding:0 16px !important;border:0 !important;border-radius:10px !important;font-weight:700 !important;font-size:13px !important;color:#fff !important;background:linear-gradient(135deg,#e8742a,#f08a45) !important;background-color:#e8742a !important;box-shadow:0 4px 12px rgba(232,116,42,.3);transition:transform .15s;}
      .vp-start:hover{transform:translateY(-2px);}
      .af-card .dataTables_length,.af-card .dataTables_info,.af-card .dataTables_paginate{margin-top:8px;}
      .af-total{display:inline-flex;align-items:center;gap:8px;padding:8px 16px;border-radius:999px;background:rgba(255,255,255,.16);font-weight:700;}
      .af-wrap .dvr{min-width:230px;display:block;}

/* small screens: keep the Start button column pinned at the right (no sideways scrolling to reach it) */
@media (max-width:1280px){
  #dynamic-table thead th:last-child,#dynamic-table tbody td:last-child{position:sticky;right:0;z-index:3;}
  #dynamic-table thead th:last-child{background:#f3f6fa !important;}
  #dynamic-table tbody td:last-child{background:#fff !important;box-shadow:-10px 0 12px -8px rgba(20,48,74,.22);}
  #dynamic-table tbody tr.af-row-overdue td:last-child{background:#fff8f1 !important;}
  #dynamic-table tbody tr.af-row-today td:last-child{background:#f3f8ff !important;}
}
    </style>
  </head>

  <body>
    <div class="wholeouter">
     <?php include("includes/top-header.php");?>

      <?php
        if(!empty($_REQUEST['msg']))  {
          echo '<div id="successMessage" class="alert alert-success"><button type="button" class="close" data-dismiss="alert">&times;</button>'. htmlspecialchars($_REQUEST['msg']) .'</div>';
        }
      ?>
      <div class="af-page" style="padding-bottom:0;">
        <div class="af-hero">
          <div><small>Work in progress</small><h1>Allocated Articles</h1></div>
          <span class="af-total"><i class="fa fa-file-text-o"></i> <span id="vp-total">0</span> articles</span>
        </div>
      </div>

      <div class="af-page"><div class="af-wrap">
        <form class="formouter" onsubmit="return false;" autocomplete="off">
          <div class="af-bar">
            <div class="af-field search">
              <label class="mb-2">Article ID / Journal</label>
              <input class="form-control" id="vp-search" placeholder="Search article or journal">
            </div>
            <div class="af-field date dvr-field">
              <label class="mb-2">Due date range</label>
              <div class="dvr" id="vp-dvr" data-from-name="from_dt" data-to-name="to_dt" data-from="" data-to=""></div>
            </div>
            <div class="af-actions">
              <button type="button" class="af-btn" id="vp-reset" title="Reset filters"><i class="fa fa-refresh"></i> Reset</button>
            </div>
          </div>
        </form>
      </div></div>

      <div class="af-page" style="padding-top:0;">
        <div class="af-tabs" id="vp-tabs">
          <button type="button" class="af-tab all active" data-k="all"><i class="fa fa-list"></i> All <b>0</b></button>
          <button type="button" class="af-tab overdue" data-k="late"><i class="fa fa-exclamation-triangle"></i> Overdue <b>0</b></button>
          <button type="button" class="af-tab today" data-k="0"><i class="fa fa-calendar-check-o"></i> Due today <b>0</b></button>
          <button type="button" class="af-tab d1" data-k="1"><i class="fa fa-calendar-o"></i> Due in 1 day <b>0</b></button>
          <button type="button" class="af-tab d2" data-k="2"><i class="fa fa-calendar-o"></i> Due in 2 days <b>0</b></button>
          <button type="button" class="af-tab d3" data-k="3"><i class="fa fa-calendar-o"></i> Due in 3 days <b>0</b></button>
          <button type="button" class="af-tab d4" data-k="4"><i class="fa fa-calendar-plus-o"></i> Due in 4+ days <b>0</b></button>
        </div>
        <div class="af-card">
          <div class="table-responsive">
            <table id="dynamic-table" class="table custtable" style="width:100% !important;">
              <thead>
                <tr>
                  <th>Journal</th>
                  <th>Article</th>
                  <th>Received</th>
                  <th>Due date</th>
                  <th>Manuscript</th>
                  <th>Fig</th>
                  <th>Table</th>
                  <th>Stage</th>
                  <th>Status</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
 <?php
		$order_by = " ORDER BY `id` DESC ";		  
  
			$query = "SELECT c.cust_name,journal_title,pub_id,recv_dt,vendor_due_date,highpriority,wd.created_dt,fig,tab,status,approved_by,wd.id,j.j_code,manuscript_count,stage,work_dir,r.received_date,r.due_date,r.correction_pages,wd.notes,wd.allocate_to,user_working_status FROM `inw_inward_dtl` as wd LEFT JOIN inw_revisions_dtl as r ON (wd.`id` =r.`inw_id` and r.r_id = 
(
    select r_id
    from inw_revisions_dtl
    where inw_id = wd.`id` order by r_id DESC limit 1
)), adm_customer_master as c, adm_journals as j WHERE wd.cust_id = c.id and wd.j_id = j.j_id  AND wd.status NOT IN ('Delivery','Client Review','Completed') and wd.v_issue_id <= 0 and wd.allocate_to = '".$_COOKIE['user_id']."' and department_id in ('1','32')";
			
		
					

		
		$query.= $order_by; 
	//echo $query;
		$customers = $db->query($query) or ($db->error);				
		while($row = $customers->fetch_assoc()){ 
		
		
		
		$trans_sql = "SELECT u.full_name,t.current_status,t.id FROM `inw_transactions` as t,users as u WHERE t.process_user = u.id AND t.`project_id` = '".$row['id']."'  AND t.current_status NOT IN ('Completed','Take Over') AND t.stage = '".$row['stage']."' AND ('Completed' != (select current_status from `inw_transactions` where `project_id` = '".$row['id']."' ORDER BY `id` DESC limit 1) )  ORDER BY t.`id` DESC limit 1;";
		//echo $trans_sql.'<br>';		
		$chk_transaction = $db->query($trans_sql); 
		$transaction_toatl_count=$chk_transaction->num_rows;
		$trans_res = $chk_transaction->fetch_assoc();	

		/* status shown in the list = latest transaction of this article/stage (user_working_status can be stale) */
		$last_tr = $db->query("SELECT current_status FROM `inw_transactions` WHERE `project_id` = '".$row['id']."' AND `stage` = '".$row['stage']."' AND `dept` IN ('1','32') ORDER BY `id` DESC LIMIT 1");
		$last_tr = $last_tr ? $last_tr->fetch_assoc() : null;
		$vp_status_html = (string)$row['user_working_status'];
		/* only vendor-department (1/32) transactions count; a pre-editing (CE) Completed must not show as the vendor's status */
		if (!$last_tr) { $vp_status_html = ''; }
		if ($last_tr && $last_tr['current_status'] !== '') {
			$vp_cur = htmlspecialchars($last_tr['current_status']);
			if (strpos($vp_status_html, 'label-warning') !== false) { $vp_status_html = preg_replace('/(label-warning">)[^<]*(<\/span>)/', '${1}' . $vp_cur . '${2}', $vp_status_html, 1); }
			else { $vp_status_html = ($vp_status_html !== '' ? $vp_status_html . ' | ' : '') . $vp_cur; }
		}
			
		
		?>
<?php $vp_due = strtotime($row['vendor_due_date']); $vp_days = (int)floor(($vp_due - strtotime(date('Y-m-d'))) / 86400); ?>
                  <tr data-due="<?php echo date('Y-m-d', $vp_due); ?>" data-days="<?php echo $vp_days; ?>" class="<?php echo $vp_days < 0 ? 'af-row-overdue' : ($vp_days == 0 ? 'af-row-today' : ''); ?>">
                    <td><span class="af-chip-j"><?php echo $row['j_code']; ?></span></td>
                    <td class="hidden-480"><span class="af-art"><?php echo $row['pub_id']; echo ($row['notes']!='') ? ' <span class="label label-sm label-info">'.substr($row['notes'],0,10).'</span>' : '';?></span></td>
                    <td data-order="<?php echo date('Y-m-d', strtotime($row['recv_dt'])); ?>"><span class="af-date"><b><?php echo date('d-m-Y', strtotime($row['recv_dt'])); ?></b></span></td>
                    <td data-order="<?php echo date('Y-m-d', $vp_due); ?>"><span class="af-due"><span class="af-date"><b><?php echo date('d-m-Y', $vp_due); ?></b></span><span class="af-rel <?php echo $vp_days < 0 ? 'late' : ($vp_days == 0 ? 'today' : ($vp_days <= 2 ? 'soon' : '')); ?>"><?php echo $vp_days < 0 ? abs($vp_days) . ' day' . (abs($vp_days) == 1 ? '' : 's') . ' late' : ($vp_days == 0 ? 'due today' : 'in ' . $vp_days . ' day' . ($vp_days == 1 ? '' : 's')); ?></span></span></td>
                    <td><?php echo $row['manuscript_count']; ?></td>
                    <td><?php echo $row['fig']; ?></td>
                    <td class="hidden-480"><?php echo $row['tab']; ?></td>
                    <td class="hidden-480"><span class="af-stage" style="--c:<?php echo preg_match('/^FP/i', $row['stage']) ? '#1b6ca8' : (preg_match('/^REV/i', $row['stage']) ? '#e8742a' : (preg_match('/^(FIN|PMC)/i', $row['stage']) ? '#1e9e61' : '#6b7785')); ?>"><?php echo $row['stage']; ?></span></td>
                    <td class="hidden-480"><?php if (strpos((string)$vp_status_html, '<') !== false) { echo '<span class="af-stat-wrap">' . $vp_status_html . '</span>'; } else { ?><span class="af-status" style="--c:<?php echo preg_match('/progress|working|take/i', (string)$vp_status_html) ? '#e8742a' : (preg_match('/complete|done/i', (string)$vp_status_html) ? '#1e9e61' : '#6b7785'); ?>"><?php echo $vp_status_html !== '' ? $vp_status_html : 'Not started'; ?></span><?php } ?></td>
                    <td class="hidden-480"><a title="Start work" style="font-size: 0.945em"  class="btn btn-xs btn-primary vp-start" onClick="return check_work_status('<?php echo $row['id']; ?>','<?php echo $trans_res['id']; ?>','<?php echo $_COOKIE['department_id']; ?>');">
				<i class="fa fa-play"></i>&nbsp;Start </a></td>
                   
                  </tr>
                  <?php } ?>
                
				
              </tbody>
            </table>
          </div>
        </div>
      </div>

<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Comments/Instructions</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
      <p id="ppopup"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <!-- <button type="button" class="btn btn-primary">Save changes</button> -->
      </div>
    </div>
  </div>
</div>
     <!-- Footer Section -->
     <?php echo footer(); ?>




<!-- Footer Section -->
<?php echo scripts(); ?>
<script src="dv-rangepicker.js?v=1"></script>	

  <!-- inline scripts related to this page --> 
<script type="text/javascript">
			jQuery(function($) {
				var table = $('#dynamic-table').DataTable({
					bAutoWidth: false,
					dom: '<"af-dt-top"l>rt<"af-dt-bot"ip>',
					"aoColumns": [ null, null, null, null, null, null, null, null, null, { "bSortable": false } ],
					"order": [[3, 'asc']],
					"pageLength": 25,
					"lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]]
				});

				var tab = 'all', from = '', to = '';
				var $rows = $('#dynamic-table tbody tr');
				/* counts on the tabs + the header: follow the search box and the due date range (not the selected tab) */
				function updateCounts() {
					var keep = tab; tab = 'all';
					var nodes = table.rows({ search: 'applied' }).nodes().toArray();
					tab = keep;
					var c = { all: nodes.length, late: 0, 0: 0, 1: 0, 2: 0, 3: 0, 4: 0 };
					nodes.forEach(function (tr) { var d = parseInt(tr.getAttribute('data-days'), 10); if (d < 0) { c.late++; } else if (d >= 4) { c[4]++; } else { c[d]++; } });
					$('#vp-tabs .af-tab').each(function () { var n = c[$(this).data('k')]; $(this).find('b').text(n).toggleClass('zero', !n); });
					$('#vp-total').text(c.all);
				}
				function cat(d) { return d < 0 ? 'late' : (d >= 4 ? '4' : String(d)); }
				$.fn.dataTable.ext.search.push(function (settings, data, i) {
					if (settings.nTable.id !== 'dynamic-table') { return true; }
					var tr = table.row(i).node(), due = tr.getAttribute('data-due'), d = parseInt(tr.getAttribute('data-days'), 10);
					if (tab !== 'all' && cat(d) !== tab) { return false; }
					if (from && due < from) { return false; }
					if (to && due > to) { return false; }
					return true;
				});
				table.on('draw.dt', updateCounts);
				updateCounts();
				$('#vp-search').on('input', function () { table.search(this.value).draw(); });
				$('#vp-tabs').on('click', '.af-tab', function () { $('#vp-tabs .af-tab').removeClass('active'); $(this).addClass('active'); tab = String($(this).data('k')); table.draw(); });
				function iso(v) { var m = /^(\d{2})-(\d{2})-(\d{4})$/.exec(v || ''); return m ? m[3] + '-' + m[2] + '-' + m[1] : ''; }
				$('#vp-dvr').on('dvr:change', function (e) { var d = e.originalEvent && e.originalEvent.detail ? e.originalEvent.detail : (e.detail || {}); from = iso(d.from); to = iso(d.to); table.draw(); });
				$('#vp-reset').on('click', function () {
					$('#vp-search').val(''); tab = 'all'; from = to = ''; $('#vp-tabs .af-tab').removeClass('active').first().addClass('active');
					var x = document.querySelector('#vp-dvr .dvr-x'); if (x) { x.click(); }
					table.search('').draw();
				});
			})

function confirmDelete(delUrl) 
{
	if (confirm("Are you sure you want to delete")) 
	{
		document.location = delUrl;
	}
}
	
	
	
		
function check_work_status(id,tid,stage)
			{	
			
				$.ajax(
				{
					type:'POST',
					url:'<?php echo $_COOKIE['set_url'];?>/get_article_work_status.php',
					data:{id:id,stage:stage},
					success:function(result)
					{
						
						if(result == 0)
						{
							window.location = 'start_work_v2.php?id='+id;					
							return true;
						}
						else
						{
							if(result == 'Please complete all the Inprogress Articles before you start this work')
							{
								bootbox.dialog(
								{
									message: '<br/><div class="alert alert-danger"> <strong> In Progress! </strong>'+ result +'</div>',
									buttons:
							{
								"button" :
								{
									"label" : "Close",
									"className" : "btn-sm",
									"callback": function() {
										window.location.reload();				
										return true;
									}
								}							
							} 						
								});					
								return false;
							}
							else
							{
								bootbox.dialog(
								{
									message: '<br/><div class="alert alert-danger"> <strong> In Progress! </strong>'+ result +'</div>',
									buttons:
							{
								
								"danger" :
								{
									"label" : "Take Over!",
									"className" : "btn-sm btn-danger",
									"callback": function() {
										$.ajax({
											type:'POST',
											url:'<?php echo $_COOKIE['set_url'];?>/update_transaction_dtl.php',
											data:'tid='+tid,
											success:function(result){
												
												window.location = '<?php echo $_COOKIE['set_url'];?>/start_work_v2.php?id='+id+'&tid='+tid;					
												return true;
											}
										});  
				
										// window.location = 'project_work.php?id='+id+'&tid='+tid;					
										// return true;
									}
								}, 
								"button" :
								{
									"label" : "Close",
									"className" : "btn-sm",
									"callback": function() {
										window.location.reload();				
										return true;
									}
								}							
							} 						
								});					
								return false;
							}
						}
					}
				});
			}	
			
			function timepopup(id)
			{				
				var url = "project_comments.php?id="+id;
				$("#ppopup").load(url);
			}
			
			
		</script>
  </body>

</html>
