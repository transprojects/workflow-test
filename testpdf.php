<?php
require __DIR__ . '/vendor/autoload.php';

use Mpdf\Mpdf;

$mpdf = new Mpdf();
$mpdf->WriteHTML('<h1>Hello World</h1>');
$mpdf->Output('report.pdf', 'D'); // D = Download

?>
