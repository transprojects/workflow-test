<?php
// Run batch file (on server side)
exec('C:\\LAMP\\Apache\\htdocs\\workflow-new\\integrity_checks\\Integrity_check.bat 2>&1', $output, $return_var);
echo "Output:<br>" . implode("<br>", $output);
echo "<br>Return code: $return_var";?>
