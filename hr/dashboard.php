<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="keywords" content="" />
	<meta name="author" content="" />
	<meta name="robots" content="" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Jobick : Job Admin Bootstrap 5 Template" />
	<meta property="og:title" content="Jobick : Job Admin Bootstrap 5 Template" />
	<meta property="og:description" content="Jobick : Job Admin Bootstrap 5 Template" />
	<meta property="og:image" content="https://jobick.dexignlab.com/xhtml/social-image.png" />
	<meta name="format-detection" content="telephone=no">
	
	<!-- PAGE TITLE HERE -->
    <?php include('includes/title.php'); ?>
	
	<!-- FAVICONS ICON -->
	<link rel="shortcut icon" type="image/png" href="images/favicon.png" />
	<link href="vendor/jquery-nice-select/css/nice-select.css" rel="stylesheet">
	<link href="vendor/owl-carousel/owl.carousel.css" rel="stylesheet">
	
	<!-- Style css -->
    <link href="css/style.css" rel="stylesheet">
	
</head>

<body>



    <!--**********************************
        Main wrapper start
    ***********************************-->
    <div id="main-wrapper">

     
		
		<!--**********************************
            Header start
        ***********************************-->
        <?php include('includes/header.php'); ?>
        <!--**********************************
            Header end ti-comment-alt
        ***********************************-->

        <!--**********************************
            Sidebar start
        ***********************************-->
        <?php include('includes/nav.php'); ?>
       
        <!--**********************************
            Sidebar end
        ***********************************-->
		
		<!--**********************************
            Content body start
        ***********************************-->
     


		<div class="content-body">
            <!-- row -->
			<div class="container-fluid">
				<div class="row">
					<div class="col-xl-6">
						<div class="row">
							<div class="col-xl-12">
								<div class="card">
								<div class="card-header border-0 pb-0">
										<h3 class="fs-20 mb-1">Articles Received</h3>
										
									</div>
									<div class="card-body">
										<div class="row separate-row">
											<div class="col-sm-6">
												<div class="job-icon pb-4 d-flex justify-content-between">
													<div>
														<div class="d-flex align-items-center mb-1">
															<h2 class="mb-0">10</h2>
															
														</div>	
														<span class="fs-14 d-block mb-2">Total Received</span>
													</div>	
													
												</div>
											</div>
											<div class="col-sm-6">
												<div class="job-icon pb-4 pt-4 pt-sm-0 d-flex justify-content-between">
													<div>
														<div class="d-flex align-items-center mb-1">
															<h2 class="mb-0">2</h2>
														</div>	
														<span class="fs-14 d-block mb-2">Completed</span>
													</div>	
													
												</div>
											</div>
											<div class="col-sm-6">
												<div class="job-icon pt-4 pb-sm-0 pb-4 pe-3 d-flex justify-content-between">
													<div>
														<div class="d-flex align-items-center mb-1">
															<h2 class="mb-0">5</h2>
														
														</div>	
														<span class="fs-14 d-block mb-2">Yet to submit</span>
													</div>	
												
												</div>
											</div>
											<div class="col-sm-6">
												<div class="job-icon pt-4  d-flex justify-content-between">
													<div>
														<div class="d-flex align-items-center mb-1">
															<h2 class="mb-0">3</h2>
														</div>	
														<span class="fs-14 d-block mb-2">On Hold</span>
													</div>	
													
												</div>
											</div>
											
										</div>
									</div>
								</div>
							</div>

						<div class="col-lg-12">
						<div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Completed Article List</h3>
                            </div>

                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover table-responsive-sm">
                                        <thead>
                                            <tr>
                                               
                                                <th>Job ID</th>
                                                <th>Journal</th>
                                                <th>Created Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>

										
                                            <tr>
                                              
                                                <td>TSP0001</td>
                                                <td>CSSE</td>
                                                <td>12-06-2023</td>   
                                                <td>Approved</td>                                                
                                            </tr>
											<tr>
                                              
											  <td>TSP0002</td>
											  <td>CSSE</td>
											  <td>15-06-2023</td>   
											  <td>Received</td>                                                
										  </tr>
										  <tr>
                                              
											  <td>TSP0003</td>
											  <td>CMES</td>
											  <td>17-06-2023</td>   
											  <td>Yet to Submit</td>                                                
										  </tr>
					
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                           <div class="card-footer border-0 m-auto">
										<a href="view_articles.php" class="btn btn-outline-primary m-auto">View more</a>
							</div> 
                            
                        </div>
                        <!-- /# card -->
                    </div>


							
						</div>
					</div>

				<div class="col-xl-6">
						<div class="row">
						<div class="col-xl-12">
								<div class="card">
								<div class="card-header border-0 pb-0">
										<h3 class="fs-20 mb-1"> Upcoming Due Articles</h3>
										
									</div>
									<div class="card-body">
										<div class="row separate-row">
											<div class="col-sm-6">
												<div class="job-icon pb-4 d-flex justify-content-between">
													<div>
														<div class="d-flex align-items-center mb-1">
															<h2 class="mb-0">10</h2>
															
														</div>	
														<span class="fs-14 d-block mb-2">16-06-2023</span>
													</div>	
													
												</div>
											</div>
											<div class="col-sm-6">
												<div class="job-icon pb-4 pt-4 pt-sm-0 d-flex justify-content-between">
													<div>
														<div class="d-flex align-items-center mb-1">
															<h2 class="mb-0">2</h2>
														</div>	
														<span class="fs-14 d-block mb-2">17-06-2023</span>
													</div>	
													
												</div>
											</div>
											<div class="col-sm-6">
												<div class="job-icon pt-4 pb-sm-0 pb-4 pe-3 d-flex justify-content-between">
													<div>
														<div class="d-flex align-items-center mb-1">
															<h2 class="mb-0">5</h2>
														
														</div>	
														<span class="fs-14 d-block mb-2">18-06-2023</span>
													</div>	
												
												</div>
											</div>
											<div class="col-sm-6">
												<div class="job-icon pt-4  d-flex justify-content-between">
													<div>
														<div class="d-flex align-items-center mb-1">
															<h2 class="mb-0">3</h2>
														</div>	
														<span class="fs-14 d-block mb-2">19-06-2023</span>
													</div>	
													
												</div>
											</div>
											
										</div>
									</div>
								</div>
							</div>

						<div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Pending Article Lists</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover table-responsive-sm">
                                        <thead>
										<tr>
                                               
											   <th>Job ID</th>
											   <th>Journal</th>
											   <th>Created Date</th>
											   <th>Status</th>
										   </tr>
									   </thead>
									   <tbody>

									   
										   <tr>
											 
											   <td>TSP0001</td>
											   <td>CSSE</td>
											   <td>12-06-2023</td>   
											   <td>Approved</td>                                                
										   </tr>
										   <tr>
											 
											 <td>TSP0002</td>
											 <td>CSSE</td>
											 <td>15-06-2023</td>   
											 <td>Received</td>                                                
										 </tr>
										 <tr>
											 
											 <td>TSP0003</td>
											 <td>CMES</td>
											 <td>17-06-2023</td>   
											 <td>Yet to Submit</td>                                                
										 </tr>
					                                           
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                           <div class="card-footer border-0 m-auto">
						   <a href="view_articles.php" class="btn btn-outline-primary m-auto" >View more</a>
							</div> 
                        </div>
                        <!-- /# card -->
                    </div>


					</div>
					</div>
					
					
                                
                                </div>					
					
					

                    <div class="col-xl-12 col-lg-12">
                        <div class="card">
                        
                        <div class="card-header border-0 pb-0">
										<h3 class="fs-20 mb-1">Articles List</h3>
									</div>
                        
						<div class="col-xl-12">
					
					         <div class="card-body pb-0">
                                <div class="widget-media">
									<ul class="timeline">
									<li>
											<div class="timeline-panel">
												<div class="media me-2">
												R												</div>
												<div class="media-body">
													<h5 class="mb-1">TSP0001.</h5>
													<small class="d-block">CSSE</small>
												</div>
												<div class="dropdown">
													<button type="button" class="btn btn-info light sharp" data-bs-toggle="dropdown">
														<svg width="18px" height="18px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><circle fill="#000000" cx="5" cy="12" r="2"/><circle fill="#000000" cx="12" cy="12" r="2"/><circle fill="#000000" cx="19" cy="12" r="2"/></g></svg>
													</button>
													<div class="dropdown-menu dropdown-menu-end">
													<a class="dropdown-item" href="meta-form.php">Metadata Form</a>
														<a class="dropdown-item" href="#">Download PDF</a>
													</div>
												</div>
											</div>
										</li>
										<li>
											<div class="timeline-panel">
												<div class="media me-2">
												R												</div>
												<div class="media-body">
													<h5 class="mb-1">TSP0002.</h5>
													<small class="d-block">CSSE</small>
												</div>
												<div class="dropdown">
													<button type="button" class="btn btn-info light sharp" data-bs-toggle="dropdown">
														<svg width="18px" height="18px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><circle fill="#000000" cx="5" cy="12" r="2"/><circle fill="#000000" cx="12" cy="12" r="2"/><circle fill="#000000" cx="19" cy="12" r="2"/></g></svg>
													</button>
													<div class="dropdown-menu dropdown-menu-end">
														<a class="dropdown-item" href="meta-form.php">Metadata Form</a>
														<a class="dropdown-item" href="#">Download PDF</a>
													</div>
												</div>
											</div>
										</li>
										<li>
											<div class="timeline-panel">
												<div class="media me-2">
												R												</div>
												<div class="media-body">
													<h5 class="mb-1">TSP0003.</h5>
													<small class="d-block">CMES</small>
												</div>
												<div class="dropdown">
													<button type="button" class="btn btn-info light sharp" data-bs-toggle="dropdown">
														<svg width="18px" height="18px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><circle fill="#000000" cx="5" cy="12" r="2"/><circle fill="#000000" cx="12" cy="12" r="2"/><circle fill="#000000" cx="19" cy="12" r="2"/></g></svg>
													</button>
													<div class="dropdown-menu dropdown-menu-end">
													<a class="dropdown-item" href="meta-form.php">Metadata Form</a>
														<a class="dropdown-item" href="#">Download PDF</a>
													</div>
												</div>
											</div>
										</li>
																			
									</ul>	
								</div>
                            </div>
                            
                        </div>  
                           <div class="card-footer border-0 m-auto">
							<a href="view_articles.php" class="btn btn-outline-primary m-auto" >View more</a>
							</div>  
                        
                        
                          
                        </div>
                        </div>                            
					
				</div>	
            </div>
        </div>


        <!--**********************************
            Content body end
        ***********************************-->
		
		
		
        <!--**********************************
            Footer start
        ***********************************-->
		<?php include('includes/footer.php'); ?>

        <!--**********************************
            Footer end
        ***********************************-->

		<!--**********************************
           Support ticket button start
        ***********************************-->
		
        <!--**********************************
           Support ticket button end
        ***********************************-->


	</div>
    <!--**********************************
        Main wrapper end
    ***********************************-->
	

</body>
</html>