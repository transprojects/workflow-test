<?php include "includes/includes.php";
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
    <link href="customer-dashboard.css" rel="stylesheet" />
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

 /* Compact Progress Bar Container */
#progressBarContainer {
  width: 300px;
  background: #eee;
  padding: 6px;
  border-radius: 8px;
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
  margin-bottom: 20px;
  margin-left: auto;
  margin-right: auto;
}

/* Compact Progress Bar */
#progressBar {
  width: 0%;
  height: 24px;
  background: #4caf50;
  color: white;
  text-align: center;
  border-radius: 6px;
  transition: width 0.3s ease;
  line-height: 24px;
  font-weight: bold;
  font-family: Arial, sans-serif;
}

/* Compact Spinner */
.spinner {
  border: 6px solid #f3f3f3;
  border-top: 6px solid #4caf50;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin: 10px auto 20px auto;
}

/* Spinner Animation */
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

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
  background-color: #f9f9f9;
  font-family: Arial, sans-serif;
}

/* Loading Text styling */
#loadingText {
  text-align: center;
  font-weight: 600;
  color: #555;
  margin-top: 10px;
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

	<div id="excelLoader" style="display:none; position:fixed; top:50%; left:50%; 
    transform:translate(-50%, -50%); background:rgba(0,0,0,0.7); color:#fff; 
    padding:20px 40px; border-radius:8px; font-size:18px; z-index:9999; text-align:center;">
    <div class="spinner" style="margin-bottom:10px;">
        <div style="border: 4px solid #fff; border-top: 4px solid transparent; 
            border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite;"></div>
    </div>
    <div>In Progress...</div>
</div>

	<div id="pdfLoader" style="display:none; position:fixed; top:50%; left:50%; 
    transform:translate(-50%, -50%); background:rgba(0,0,0,0.7); color:#fff; 
    padding:20px 40px; border-radius:8px; font-size:18px; z-index:9999; text-align:center;">
    <div class="spinner" style="margin-bottom:10px;">
        <!-- Simple CSS spinner -->
        <div style="border: 4px solid #fff; border-top: 4px solid transparent; 
            border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite;"></div>
    </div>
    <div>In Progress...</div>
</div>
   

<!-- Footer Section -->
<?php echo footer(); ?>




<!-- Footer Section -->
<?php echo scripts(); ?>	

<!--<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>-->
<script src="https://code.highcharts.com/highcharts.js"></script>


<script>
  document.querySelectorAll('.peerj-happenings-content p span').forEach(span => {
    // Generate random color in hex
    const randomColor = '#' + Math.floor(Math.random()*16777215).toString(16).padStart(6, '0');
    span.style.backgroundColor = randomColor;
    span.style.display = 'inline-block';
    span.style.width = '14px';
    span.style.height = '14px';
    span.style.borderRadius = '3px';
    span.style.marginRight = '8px';
    span.style.verticalAlign = 'middle';
  });
</script>

<script>
function handleClick(element) {
    document.querySelectorAll('.delivery-items p').forEach(p => p.classList.remove('peerj1-clicked'));
    element.classList.add('peerj1-clicked');

    const id = element.id;
    document.getElementById('firstProofSection').style.display = id === 'first-proof' ? 'grid' : 'none';
    document.getElementById('revisedSection').style.display = id === 'revised' ? 'grid' : 'none';
	document.getElementById('rfirstproof').style.display = id === 'first-proof' ? 'grid' : 'none';
    document.getElementById('rrevisedproof').style.display = id === 'revised' ? 'grid' : 'none';}
</script>

<script>
function showMainTab(tab) {
  // For QMS and CAPA tabs
  const tabs = ['qms', 'capa'];
  tabs.forEach(t => {
    document.getElementById(t + '-tab').classList.remove('peerj1-clicked');
    document.getElementById(t + '-table').style.display = 'none';
  });
  document.getElementById(tab + '-tab').classList.add('peerj1-clicked');
  document.getElementById(tab + '-table').style.display = 'block';
}

function showProofTab(tab) {
  // For Proof Sent / Revised Received tabs
  const tabs = ['proof', 'revised'];
  tabs.forEach(t => {
    document.getElementById(t + '-tab').classList.remove('peerj1-clicked');
    document.getElementById(t + '-table').style.display = 'none';
  });
  document.getElementById(tab + '-tab').classList.add('peerj1-clicked');
  document.getElementById(tab + '-table').style.display = 'block';
}

</script>


      <script>
        const handleClick = (element) => {
            const paragraphs = document.querySelectorAll(".delivery-items p");

            // If clicked element already has the class, do nothing
            if (element.classList.contains('peerj1-clicked')) {
                return; // keep the class, no change
            }

            // Remove 'peerj1-clicked' class from all <p> tags
            paragraphs.forEach((p) => {
                p.classList.remove("peerj1-clicked");
            });

            // Add the class to the clicked element
            element.classList.add("peerj1-clicked");
        };
        const handleTableClick = (element) => {
            const paragraphs = document.querySelectorAll(".peerj-table-items p");

            // If clicked element already has the class, do nothing
            if (element.classList.contains('peerj1-clicked')) {
                return; // keep the class, no change
            }

            // Remove 'peerj1-clicked' class from all <p> tags
            paragraphs.forEach((p) => {
                p.classList.remove("peerj1-clicked");
            });

            // Add the class to the clicked element
            element.classList.add("peerj1-clicked");
        };
    </script>

    <!-- Review Statics graph -->

<script>
// ✅ Chart rendering function (you can define multiple)
function initCharts(data) {
    if (!data) {
        console.error("No chart data provided");
        return;
    }
    // Save original data for tooltip reference
    const firstProofOriginal = [
        data.firstStats.lt_48,
        data.firstStats.btw_48_72,
        data.firstStats.gt_72
    ];
    const revisesOriginal = [
        data.revStats.lt_48,
        data.revStats.btw_48_72,
        data.revStats.gt_72
    ];
    function fixZeroValues(arr) {
        return arr.map(v => v === 0 ? 1 : v);
    }
    Highcharts.chart('chart-container', {
        chart: { type: 'column' },
        title: { text: null },
        xAxis: {
            categories: ['Ahead', 'On Time', 'Delay'],
            title: {
                text: 'Delivery Time',
                style: { fontSize: '16px', fontWeight: 'bold' }
            },
            labels: { style: { fontSize: '14px' } }
        },
        yAxis: {
            min: 0,
            title: {
                text: 'Number of Proofs',
                style: { fontSize: '16px', fontWeight: 'bold' }
            },
            labels: { style: { fontSize: '14px' } }
        },
        legend: {
            align: 'center',
            verticalAlign: 'top',
            layout: 'horizontal',
            itemStyle: { fontSize: '14px', fontWeight: 'normal' }
        },
        plotOptions: {
            column: {
                minPointLength: 1,
                dataLabels: {
                    enabled: true,
                    style: {
                        fontSize: '14px',
                        fontWeight: 'bold',
                        color: '#000000',
                        textOutline: 'none'
                    }
                }
            }
        },
        tooltip: {
            formatter: function() {
                let seriesIndex = this.series.index;
                let pointIndex = this.point.index;
                let actualValue = seriesIndex === 0
                    ? firstProofOriginal[pointIndex]
                    : revisesOriginal[pointIndex];
                if (actualValue === 0 && this.y === 1) {
                    return `<b>${this.series.name}</b><br/>${this.x}: 0 (shown as 1 for visibility)`;
                }
                return `<b>${this.series.name}</b><br/>${this.x}: ${this.y}`;
            }
        },
        series: [{
            name: 'First Proof',
            color: 'rgba(54, 162, 235, 0.7)',
            data: fixZeroValues(firstProofOriginal)
        }, {
            name: 'Revises',
            color: 'rgba(255, 99, 132, 0.7)',
            data: fixZeroValues(revisesOriginal)
        }]
    });
}
</script>



   
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

        // Re-bind filter form submit inside loaded content (if present)
        if ($("#filterForm").length) {
          $("#filterForm").off("submit").on("submit", function (e) {
            e.preventDefault();
            const newFormData = $(this).serialize();
            console.log("Rebound filter form submitted, data:", newFormData);
            loadDashboard(newFormData);
          });
        }

        // DATE PICKER LOGIC
        const today = new Date();
        $('#dateDisplay').text('Filter').show();

        $('#openDatepicker').on('click', function () {
          $('#dateRangeInputs').toggle();
        });

        $('#fromDate').datepicker({
          format: "yyyy-mm-dd",
          autoclose: true,
          todayHighlight: true,
          endDate: today
        }).on('changeDate', function (e) {
          const selectedFromDate = e.date;
          $('#toDate').datepicker('setStartDate', selectedFromDate);
          $('#toDate').datepicker('setEndDate', today);

          const currentToDate = $('#toDate').val();
          if (currentToDate) {
            const toDate = new Date(currentToDate);
            if (toDate < selectedFromDate || toDate > today) {
              $('#toDate').val('');
            }
          }
          updateDateDisplay();
        });

        $('#toDate').datepicker({
          format: "yyyy-mm-dd",
          autoclose: true,
          todayHighlight: true,
          startDate: today,
          endDate: today
        }).on('changeDate', function () {
          updateDateDisplay();
        });

        function updateDateDisplay() {
          const fromDate = $('#fromDate').val();
          const toDate = $('#toDate').val();
          if (fromDate && toDate) {
            $('#dateRangeInputs').hide();
            const displayText = (fromDate === toDate) ? toDate : `${fromDate} to ${toDate}`;
            $('#dateDisplay').text(displayText).show();
          }
        }

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
        });

        // PDF Download Buttons
        $("#downloadBtnpdf").on("click", function () {
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
                a.remove();
                window.URL.revokeObjectURL(url);
              })
              .catch(error => alert('Error: ' + error.message))
              .finally(() => $("#pdfLoader").hide());

          } else {
            alert('SVG chart not found.');
          }
        });

        $("#downloadBtnpdfall").on("click", function () {
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
                a.remove();
                window.URL.revokeObjectURL(url);
              })
              .catch(error => alert('Error: ' + error.message))
              .finally(() => $("#pdfLoader").hide());

          } else {
            alert('SVG chart not found.');
          }
        });

        $("#downloadBtnall").on("click", function () {
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
              a.remove();
              window.URL.revokeObjectURL(url);
            })
            .catch(error => alert('Error: ' + error.message))
            .finally(() => $("#excelLoader").hide());
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



  </body>
</html>
