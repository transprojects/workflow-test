<?php include "includes/includes.php";
/* Book a JSTAGE round (JSTAGE<n>) for an article under client review - /jstagearticles/<id>. Page, validation, file move and the booking e-mail: booking_lib.php */
require_once __DIR__.'/booking_lib.php';
$o = bk_run('JSTAGE');
include __DIR__.'/booking_page.php';
