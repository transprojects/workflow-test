 <div class="footer">
            <div class="copyright">
                 <p>Copyright © Designed &amp; Developed by <a href="https://www.transforma.in" target="_blank"> Transforma</a> <?php echo date('Y');?></p>
            </div>
</div>

 <!-- Modal -->
 
	

    <!--**********************************
        Scripts
    ***********************************-->
    <!-- Required vendors -->
    <script src="vendor/global/global.min.js"></script>
	<script src="vendor/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
	<script src="vendor/chart.js/Chart.bundle.min.js"></script>
	<script src="vendor/jquery-nice-select/js/jquery.nice-select.min.js"></script>
	<script src="vendor/lightgallery/js/lightgallery-all.min.js"></script>

	
	<!-- Apex Chart -->
	<script src="vendor/apexchart/apexchart.js"></script>
	
	<script src="vendor/chart.js/Chart.bundle.min.js"></script>
	
	<!-- Chart piety plugin files -->
    <script src="vendor/peity/jquery.peity.min.js"></script>
	
	<!-- Dashboard 1 -->
	<script src="js/dashboard/dashboard-1.js"></script>
	
	<script src="vendor/owl-carousel/owl.carousel.js"></script>
	
    <script src="js/custom.min.js"></script>
	<script src="js/dlabnav-init.js"></script>


	<!-- Datatable -->
	<script src="vendor/datatables/js/jquery.dataTables.min.js"></script>
    <script src="js/plugins-init/datatables.init.js"></script>
	<script src="vendor/bootstrap-datepicker-master/js/bootstrap-datepicker.min.js"></script>

<script>
	$(".form_datetime").datepicker({format: 'yyyy-mm-dd'});
	var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
	var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
	  return new bootstrap.Tooltip(tooltipTriggerEl)
	})
	</script>
	<script src="validation/dist/jquery.validate.js"></script>
	<script src="validation/dist/additional-methods.js"></script>

	
	<script>

	$().ready(function() {
		// validate the comment form when it is submitted

		// validate client upload form on keyup and submit
		$("#client_upload").validate({
			rules: {
				j_id: "required",
				article_type: "required",
				pub_id: "required",
				email: {
					required: true,
					email: true
				}
			},
			messages: {
				j_id: "Please Select Journal",
				article_type: "Please Select Artcile type",
				pub_id: "Enter Artcile ID",
				email: "Please enter a valid email address",
			}
		});
		
		
				$("#add_user").validate({
			rules: {
				staff_name: "required",
				staff_mob: "required",
				staff_email: {
					required: true,
					email: true
				}
			},
			messages: {
				staff_name: "Please Enter User Name",
				staff_mob: "Please Enter Valid Mobile no",
				staff_email: "Please enter a valid email address",
			}
		});
		
		
						$("#edit_user").validate({
			rules: {
				staff_name: "required",
				staff_mob: "required",
				staff_pass: "required",
				staff_email: {
					required: true,
					email: true
				}
			},
			messages: {
				staff_name: "Please Enter User Name",
				staff_mob: "Please Enter Valid Mobile no",
				staff_pass: "Please Enter Password",
				staff_email: "Please enter a valid email address"
			}
		});
		
	});




	</script>
