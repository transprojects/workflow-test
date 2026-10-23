 <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>
 /*navbar clock ----------*/
.nav-clock {
	 display: flex;
    justify-content: center;
    align-items: center;
    font-size: 18px;
    border-radius: 0;
    color: #494c57;
	position: relative;
    right:-28%;
    font-weight: 500;
    margin-left: 15px;
    width: 130px;
    text-align: center;
    background-color: #fff;
    /* border: 1px solid #f1f3fa; */
    border-radius: 8px;
    min-height: 40px;
    border-width: 0 1px;
    line-height: 40px;
    font-family: DS-DIGI;
    letter-spacing: 1px;
    -webkit-box-shadow: 0px 0px 5px 1px rgba(242,103,34,0.62) !important;
	-moz-box-shadow: 0px 0px 5px 1px rgba(242,103,34,0.62) !important;
	box-shadow: 0px 0px 5px 1px rgba(242,103,34,0.62) !important;
}
.nav-clock .time > span:not(:last-child)::after {
    content: ':';
    width: 10px;
    text-align: center;
    display: inline-block;
    position: relative;
    top: -1px;
    right: -2px;
}

@media(max-width:1200px) {
    .nav-clock {
        display: none;
    }
}
 </style>
 <div class="container-fluid d-flex" style="position: sticky;top: 0;padding: 0 !important;background: #fff; z-index: 500">
 <div class="row titlesection" style="margin: 0 !important">
        <div class="col-6 col-md-4 col-lg-4 d-none d-md-block">
          <a href="https://www.transforma.in" target="_blank">
            <img
              class="wkfwlogo"
              alt="transforma-logo"
              src="<?php echo $_COOKIE['set_url'];?>/assets/images/translogo.png"
            />
          </a>
        </div>
        <div class="col-6 col-md-4 col-lg-4 d-flex  justify-content-md-start justify-content-lg-center">
          <img
            class="wkfwlogo1 ms-lg-5"
            alt="Xpert-workflow-logo"
            src="<?php echo $_COOKIE['set_url'];?>/assets/images/workflow-vendor-logo.png" />
	<div class="nav-clock">
        <div class="time">
            <span class="time-hours"></span>
            <span class="time-min"></span>
            <span class="time-sec"></span>
        </div>
    </div>
        </div>
		
        <div class="col-2 col-md-2 col-lg-4 col-xl-3 d-flex justify-content-center align-items-center" >
    <nav class="navbar navbar-expand-lg" style="background: #fff !important">
    <div class="container-fluid p-0">
    <button class="navbar-toggler p-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation" style="border: none !important"> 
    <img
      alt="profileicon"
      src="<?php echo $_COOKIE['set_url'];?>/assets/images/profileicon.svg"
      />
    </button>
    <div class="collapse navbar-collapse collapsestyle" id="navbarNavDropdown">
      <ul class="navbar-nav p-2">
        <li class="nav-item d-flex justify-content-center">
              <div class="notiicon">
                <button class="notibtn"> 
                  <img
                    alt="notification_icon"
                    src="<?php echo $_COOKIE['set_url'];?>/assets/images/notification.svg"
                  />
                </button>
              </div>
        </li>
        <div class="col-1 d-none d-lg-flex justify-content-center align-items-center">
              <div class="vrline1"></div>
        </div>
        <li class="nav-item dropdown d-flex">
             <img
                class="d-none d-lg-block"
                alt="profileicon"
                src="<?php echo $_COOKIE['set_url'];?>/assets/images/profileicon.svg"
              />
            <button class="btn userprofilebtn dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false" style="width: 90% !important">
              <?php echo $_COOKIE['full_name']; ?> (<?php echo $_COOKIE['dept_name']; ?>)
            </button>
            <ul class="dropdown-menu custdropdown" aria-labelledby="dropdownMenuButton1" style="position: absolute; left:15px">
                  <li>
                    <a class="dropdown-item" href="<?php echo $_COOKIE['set_url'];?>/logout">Logout</a>
                  </li>
                </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>
</div>
      </div>
</div>
	  <script>
// Function to update the current time
function updateCurrentTime() {
    var now = new Date();
    var hours = String(now.getHours()).padStart(2, '0');
    var minutes = String(now.getMinutes()).padStart(2, '0');
    var seconds = String(now.getSeconds()).padStart(2, '0');
    
    $(".time-hours").text(hours);
    $(".time-min").text(minutes);
    $(".time-sec").text(seconds);
}

// Update the current time initially when the page loads
updateCurrentTime();

// Update the current time every second (1000 milliseconds)
setInterval(updateCurrentTime, 1000);
</script>
<?php include("includes/header.php");?>