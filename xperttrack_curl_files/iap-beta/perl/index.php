<?php 

$ce_perl_file = "C:/LAMP/Apache/htdocs/workflow/xperttrack_curl_files/IAP/perl/IAPBOOKINGMAIL.pl";
$start=$_REQUEST['start'];
$end=$_REQUEST['end'];
/// IAPBOOKINGMAIL.pl "1" "2024-01-30 09:00:51" "2024-01-30 17:05:51"
$perl_update_article = 'C:\\Perl64\\bin\\perl.exe "'.$ce_perl_file.'" "1" "'.$start.'" "'.$end.'"';
system($perl_update_article);	
?>

