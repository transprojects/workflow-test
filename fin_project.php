<?php include "includes/includes.php";
/* Book a Final (FIN<n>) for an article under client review - /finalarticles/<id>. Page, validation, file move and the booking e-mail: booking_lib.php */
require_once __DIR__.'/booking_lib.php';
$o = bk_run('FIN');
include __DIR__.'/booking_page.php';
