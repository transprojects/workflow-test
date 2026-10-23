<?php include "includes/includes.php";
/* Book a Revise (REV<n>) for an article under client review - /revarticles/<id>. Page, validation, file move and the booking e-mail: booking_lib.php */
require_once __DIR__.'/booking_lib.php';
$o = bk_run('REV');
include __DIR__.'/booking_page.php';
