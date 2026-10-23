<?php include "includes/includes.php";
include "includes/modern-ui.php";
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- CSS styles -->
    <?php include('includes/title.php'); ?>
    <?php echo styles(); ?>	
    <link href="styles.css" rel="stylesheet" />
    <?php mu_head(); ?>
    <link href="customer-dashboard.css?v=20261002c" rel="stylesheet" />
    <link href="delivery-dashboard-v2.css?v=20261003g" rel="stylesheet" />
    <!-- Data Table CSS- -->
<style>
    .proof-count, .revised-count {
        text-align: center;
        font-weight: bold;
        color: red;
        animation: blinker 1s linear infinite;
    }

    @keyframes blinker {
        50% { opacity: 0; }
    }
	/* Make all rows and cells white background */
#proof-dt tbody tr td,
#revised-dt tbody tr td,
#proof-dt thead tr th,
#revised-dt thead tr th {
    background-color: #ffffff !important;
    color: #000000;
}

 /* Loader */
#progressBarContainer {
  width: 320px;
  background: #e9eef4;
  padding: 4px;
  border-radius: 14px;
  box-shadow: inset 0 1px 3px rgba(20,48,74,.12);
  margin: 0 auto 18px;
}
#progressBar {
  width: 0%;
  height: 22px;
  background: linear-gradient(90deg, #F56500, #f5a35c 60%, #1b6ca8);
  background-size: 200% 100%;
  animation: muShimmer 2.2s linear infinite;
  color: #fff;
  text-align: center;
  border-radius: 10px;
  transition: width 0.3s ease;
  line-height: 22px;
  font-size: 12px;
  font-weight: 700;
  font-family: inherit;
}
.spinner {
  border: 5px solid #e9eef4;
  border-top-color: #F56500;
  border-right-color: #1b6ca8;
  border-radius: 50%;
  width: 56px;
  height: 56px;
  animation: spin .85s linear infinite;
  margin: 10px auto 18px auto;
}
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

/* Show only loading elements while loading */
#dashboardContent.loading > *:not(.spinner):not(#progressBarContainer):not(#loadingText) {
  display: none;
}

#dashboardContent.loading .spinner,
#dashboardContent.loading #progressBarContainer,
#dashboardContent.loading #loadingText {
  display: block;
  margin-left: auto;
  margin-right: auto;
}

/* Center the dashboard and loading content vertically and horizontally */
#dashboardContent.loading {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  height: 100vh;
  background: radial-gradient(circle at 50% 40%, #fff, #f2f6fa);
  font-family: inherit;
}

/* Loading Text styling */
#loadingText {
  text-align: center;
  font-weight: 700;
  color: #14304a;
  letter-spacing: .3px;
  margin-top: 4px;
}


</style>

  </head>
  <body>
      <div class="container-fluid">
    <div class="wholeouter">
      <!-- Header logo section -->
     

     <?php include("includes/top-header.php");?>
	 
<div id="dashboardContent">
  <div class="spinner"></div>
  <div id="progressBarContainer">
    <div id="progressBar">0%</div>
  </div>
  <div id="loadingText">Loading dashboard...</div>
  
  <!-- Your actual dashboard content here (hidden while loading) -->
</div>

	<div id="excelLoader" class="dl-loader" style="display:none;"><div class="dl-loader-card"><div class="spinner"></div><b>Preparing your Excel</b><span>This can take a few seconds&hellip;</span></div></div>
<div id="pdfLoader" class="dl-loader" style="display:none;"><div class="dl-loader-card"><div class="spinner"></div><b>Preparing your PDF</b><span>This can take a few seconds&hellip;</span></div></div>

<!-- Footer Section -->
<?php echo footer(); ?>




<!-- Footer Section -->
<?php echo scripts(); ?>	

<!--<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>-->
<script src="https://code.highcharts.com/highcharts.js"></script>




<script>
/* small non-blocking message (replaces alert()) */
function dlToast(msg, bad) {
  const t = document.createElement('div');
  t.className = 'dl-toast' + (bad ? ' bad' : '');
  t.innerHTML = '<i class="fa ' + (bad ? 'fa-exclamation-circle' : 'fa-check-circle') + '"></i> ';
  t.appendChild(document.createTextNode(msg));
  document.body.appendChild(t);
  requestAnimationFrame(() => t.classList.add('show'));
  setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 400); }, bad ? 5000 : 2500);
}

function handleClick(element) {
    document.querySelectorAll('.delivery-items button').forEach(b => b.classList.remove('is-active'));
    element.classList.add('is-active');

    const fp = element.id === 'first-proof';
    document.getElementById('firstProofSection').style.display = fp ? 'grid' : 'none';
    document.getElementById('revisedSection').style.display = fp ? 'none' : 'grid';
    document.getElementById('rfirstproof').style.display = fp ? 'flex' : 'none';
    document.getElementById('rrevisedproof').style.display = fp ? 'none' : 'flex';
}
</script>

<script>
function showMainTab(tab) {
  // For QMS and CAPA tabs
  const tabs = ['qms', 'capa'];
  tabs.forEach(t => {
    document.getElementById(t + '-tab').classList.remove('is-active');
    document.getElementById(t + '-table').style.display = 'none';
  });
  document.getElementById(tab + '-tab').classList.add('is-active');
  document.getElementById(tab + '-table').style.display = 'block';
}

function showProofTab(tab) {
  // For Proof Sent / Revised Received tabs
  const tabs = ['proof', 'revised'];
  tabs.forEach(t => {
    document.getElementById(t + '-tab').classList.remove('is-active');
    document.getElementById(t + '-table').style.display = 'none';
  });
  document.getElementById(tab + '-tab').classList.add('is-active');
  document.getElementById(tab + '-table').style.display = 'block';
}

</script>



    <!-- Review Statics graph -->

<script>
// ✅ Chart rendering function (you can define multiple)
function initCharts(data) {
    if (!data) {
        console.error("No chart data provided");
        return;
    }

    // Replace zeros with null to avoid thin bars and data labels
    function fixZeroValues(arr) {
        return arr.map(v => v === 0 ? null : v);
    }

    Highcharts.chart('chart-container', {
        chart: { type: 'column', backgroundColor: 'transparent', style: { fontFamily: 'inherit' }, spacingTop: 10 },
        credits: { enabled: true },
        title: { text: null },
        xAxis: {
            categories: ['Ahead', 'On Time', 'Delay'],
            title: {
                text: 'Delivery time',
                style: { fontSize: '13px', fontWeight: '600', color: '#6b7785' }
            },
            labels: { style: { fontSize: '14px', fontWeight: '600', color: '#14304a' } },
            lineColor: '#e6ebf1', tickLength: 0
        },
        yAxis: {
            min: 0, allowDecimals: false, gridLineColor: '#eef2f6',
            title: {
                text: 'Number of proofs',
                style: { fontSize: '13px', fontWeight: '600', color: '#6b7785' }
            },
            labels: { style: { fontSize: '14px' } }
        },
        legend: {
            align: 'center',
            verticalAlign: 'top',
            layout: 'horizontal',
            itemStyle: { fontSize: '13px', fontWeight: '600', color: '#14304a' }
        },
        plotOptions: {
            column: {
                borderRadius: 6, borderWidth: 0, pointPadding: 0.06, groupPadding: 0.2,
                minPointLength: 0,  // No minimum height for bars
                dataLabels: {
                    enabled: true,
                    formatter: function() {
                        // Hide data label for null or zero values
                        return this.y === null ? '' : this.y;
                    },
                    style: {
                        fontSize: '14px',
                        fontWeight: 'bold',
                        color: '#14304a',
                        textOutline: 'none'
                    }
                }
            }
        },
        series: [{
            name: 'First Proof',
            color: '#1b6ca8',
            data: fixZeroValues([
                data.firstStats.lt_48,
                data.firstStats.btw_48_72,
                data.firstStats.gt_72
            ])
        }, {
            name: 'Revises',
            color: '#F56500',
            data: fixZeroValues([
                data.revStats.lt_48,
                data.revStats.btw_48_72,
                data.revStats.gt_72
            ])
        }]
    });
}
</script>




   
     <script src="dv-datepicker.js?v=1"></script>

<!-- Toogle show hide functionality -->
    <script>
      const showbtn = $(".showmorebtn");
      const report = $(".report");
      var state = "false";

      showbtn.on("click", function () {
        report[0].classList.toggle("hide");
        state = !state;
        if (state) {
          showbtn[0].innerHTML = "Show More";
        } else {
          showbtn[0].innerHTML = "Show Less";
        }
      });
    </script>
	
	
	
    <!-- Date picker functionality -->
    <script>
      $(function () {
        // var datesForDisable = ["2017-10-14", "2017-10-15", "2017-10-16"];
        var date = new Date();
        date.setDate(date.getDate() - 1);
        var fromdate;
        $(".monthlyinputdate").datepicker({
          uiLibrary: "bootstrap5",

          format: "dd-mm-yyyy",
          autoclose: true,
          weekStart: 1,
          todayHighlight: true,
          // minDate: date,
          onSelect: function (dateText, inst) {
            fromdate = dateText;
            console.log(fromdate);
          },
        });

        $(".monthlyinputdate1").datepicker({
          uiLibrary: "bootstrap5",
          iconsLibrary: "materialicons",
          format: "dd-mm-yyyy",
          autoclose: true,
          weekStart: 1,
          todayHighlight: true,
          // minDate: new Date(), //For disabling date from today
        });
      });
    </script>

  


<script>
document.addEventListener("DOMContentLoaded", () => {
  console.log("DOM fully loaded, loading dashboard...");
  loadDashboard();

  // Delegated submit binding (works for dynamic content)
  $(document).on("submit", "#filterForm", function (e) {
    e.preventDefault();
    const newFormData = $(this).serialize();
    console.log("Filter form submitted, serialized data:", newFormData);
    loadDashboard(newFormData);
  });
});

function loadDashboard(formData) {
  let percent = 0;
  const dashboard = document.getElementById("dashboardContent");

  if (!dashboard) {
    console.error("Dashboard element (#dashboardContent) not found!");
    return;
  }

  // Insert loader HTML immediately to show spinner and progress bar
  dashboard.classList.add("loading");
  dashboard.innerHTML = `
    <div class="spinner"></div>
    <div id="progressBarContainer">
      <div id="progressBar">0%</div>
    </div>
    <div id="loadingText">Loading dashboard...</div>
  `;

  const progressBar = document.getElementById("progressBar");

  if (progressBar) {
    progressBar.style.width = "0%";
    progressBar.textContent = "0%";
  } else {
    console.warn("Progress bar element (#progressBar) not found. Progress updates won't be visible.");
  }

  // Use passed formData or serialize filter form if available
  if (!formData) {
    if ($("#filterForm").length) {
      formData = $("#filterForm").serialize();
      console.log("No formData passed, serialized filter form:", formData);
    } else {
      formData = "";
      console.log("No filter form found, loading dashboard with empty query.");
    }
  } else {
    console.log("Using passed formData:", formData);
  }

  const fakeProgress = setInterval(() => {
    if (percent < 95) {
      percent += Math.floor(Math.random() * 5) + 1;
      if (percent > 95) percent = 95;
      if (progressBar) {
        progressBar.style.width = percent + "%";
        progressBar.textContent = percent + "%";
      }
      console.log(`Progress: ${percent}%`);
    }
  }, 200);

  fetch("customer-dashboard-all-include.php?" + formData)
    .then(response => {
      console.log("Fetch response status:", response.status);
      if (!response.ok) throw new Error('Network response was not ok: ' + response.status);
      return response.text();
    })
    .then(html => {
      clearInterval(fakeProgress);
      if (progressBar) {
        progressBar.style.width = "100%";
        progressBar.textContent = "100%";
      }
      console.log("Fetch succeeded, HTML content received. Updating dashboard...");
      setTimeout(() => {
        dashboard.classList.remove("loading");
        dashboard.innerHTML = html;
        if (window.muBoot) { window.muBoot(dashboard); }

        // Re-bind filter form submit inside loaded content (if present)
        if ($("#filterForm").length) {
          $("#filterForm").off("submit").on("submit", function (e) {
            e.preventDefault();
            const newFormData = $(this).serialize();
            console.log("Rebound filter form submitted, data:", newFormData);
            loadDashboard(newFormData);
          });
        }

        dvInitPicker();

        // Chart logic
        const scriptTag = document.getElementById('chart-data');
        let chartData = null;
        if (scriptTag) {
          try {
            chartData = JSON.parse(scriptTag.textContent);
            console.log("Chart data parsed:", chartData);
          } catch (e) {
            console.error("Error parsing chart data JSON", e);
          }
        }
        initCharts(chartData);

        // DataTables initialization
        if ($.fn.DataTable.isDataTable('#proof-dt')) {
          $('#proof-dt').DataTable().destroy();
        }
        $('#proof-dt').DataTable({
          pageLength: 10,
          lengthMenu: [5, 10, 25, 50, 100, 150, 200, 500],
          dom: 'Bfltip',
          buttons: [
            {
              extend: 'collection',
              text: 'Export',
              buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
            }
          ],
          stripeClasses: [],
          order: [],
          columnDefs: [{ orderable: false, targets: 0 }],
          language: { search: '', searchPlaceholder: 'Search articles…', lengthMenu: 'Show _MENU_' },
        });

        if ($.fn.DataTable.isDataTable('#revised-dt')) {
          $('#revised-dt').DataTable().destroy();
        }
        $('#revised-dt').DataTable({
          pageLength: 10,
          lengthMenu: [5, 10, 25, 50, 100, 150, 200, 500],
          dom: 'Bfltip',
          buttons: [
            {
              extend: 'collection',
              text: 'Export',
              buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
            }
          ],
          stripeClasses: [],
          order: [],
          columnDefs: [{ orderable: false, targets: 0 }],
          language: { search: '', searchPlaceholder: 'Search articles…', lengthMenu: 'Show _MENU_' },
        });

        // PDF Download Buttons
        $("#downloadBtnpdf").on("click", function () {
          const $btn = $(this).addClass('is-busy').prop('disabled', true);
          const done = () => $btn.removeClass('is-busy').prop('disabled', false);
          const svgElement = document.querySelector('#chart-container svg');
          if (svgElement) {
            $("#pdfLoader").show();
            const serializer = new XMLSerializer();
            let svgString = serializer.serializeToString(svgElement);
            const base64Svg = window.btoa(unescape(encodeURIComponent(svgString)));

            const formData = $("#filterForm").serializeArray();
            const data = { chartBase64: base64Svg, chartType: 'svg' };
            formData.forEach(field => data[field.name] = field.value);

            fetch('customer_dashboard_export_pdf.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(data)
            })
              .then(res => {
                if (!res.ok) throw new Error('PDF generation failed.');
                return res.blob();
              })
              .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'customer_dashboard_report.pdf';
                document.body.appendChild(a);
                a.click();
              dlToast('Download started');
                dlToast('Download started');
                a.remove();
                window.URL.revokeObjectURL(url);
              })
              .catch(error => dlToast(error.message, true))
              .finally(() => { $("#pdfLoader").hide(); done(); });

          } else {
            dlToast('The chart is still loading - try again in a moment.', true); done();
          }
        });

        $("#downloadBtnpdfall").on("click", function () {
          const $btn = $(this).addClass('is-busy').prop('disabled', true);
          const done = () => $btn.removeClass('is-busy').prop('disabled', false);
          const svgElement = document.querySelector('#chart-container svg');
          if (svgElement) {
            $("#pdfLoader").show();
            const serializer = new XMLSerializer();
            let svgString = serializer.serializeToString(svgElement);
            const base64Svg = window.btoa(unescape(encodeURIComponent(svgString)));

            const formData = $("#filterForm").serializeArray();
            const data = { chartBase64: base64Svg, chartType: 'svg' };
            formData.forEach(field => data[field.name] = field.value);

            fetch('customer_dashboard_detail_export_pdf.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(data)
            })
              .then(res => {
                if (!res.ok) throw new Error('Detailed PDF generation failed.');
                return res.blob();
              })
              .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'customer_dashboard_detailed_report.pdf';
                document.body.appendChild(a);
                a.click();
              dlToast('Download started');
                dlToast('Download started');
                a.remove();
                window.URL.revokeObjectURL(url);
              })
              .catch(error => dlToast(error.message, true))
              .finally(() => { $("#pdfLoader").hide(); done(); });

          } else {
            dlToast('The chart is still loading - try again in a moment.', true); done();
          }
        });

        $("#downloadBtnall").on("click", function () {
          const $btn = $(this).addClass('is-busy').prop('disabled', true);
          const done = () => $btn.removeClass('is-busy').prop('disabled', false);
          $("#excelLoader").show();
          const formData = $("#filterForm").serialize();

          fetch("download-report-all.php?" + formData, { method: 'GET' })
            .then(res => {
              if (!res.ok) throw new Error('Excel download failed.');
              return res.blob();
            })
            .then(blob => {
              const url = window.URL.createObjectURL(blob);
              const a = document.createElement('a');
              a.href = url;
              a.download = 'customer_dashboard_report.xlsx';
              document.body.appendChild(a);
              a.click();
              dlToast('Download started');
              a.remove();
              window.URL.revokeObjectURL(url);
            })
            .catch(error => dlToast(error.message, true))
            .finally(() => { $("#excelLoader").hide(); done(); });
        });

      }, 300);
    })
    .catch(error => {
      clearInterval(fakeProgress);
      if (progressBar) {
        progressBar.style.width = "0%";
        progressBar.textContent = "0%";
      }
      dashboard.classList.remove("loading");
      dashboard.innerHTML = "<p>Error loading dashboard.</p>";
      console.error("Error loading dashboard:", error);
    });
}
</script>



  <?php mu_foot(); ?>
  </body>
</html>
