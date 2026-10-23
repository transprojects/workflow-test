<?php 
/*
// Specify the folder containing the files you want to zip
 $GraphicsPath = 'D:\New_Workflow_Files\JOURNAL\production\JOURNALS\SIF\EPL\Vol00000\EPL_6767\FP\Graphics';
 $GraphicsPath_Delivery ='D:\New_Workflow_Files\JOURNAL\production\JOURNALS\SIF\EPL\Vol00000\EPL_6767\FP\Delivery';

// Name for the output ZIP file
$GraphicszipFileNames ='EPL_6767_graphics.zip';
$GraphicszipFileName ='EPL_6767_graphics.zip';



$zip = new ZipArchive();

if ($zip->open($GraphicsPath_Delivery . '/' . $GraphicszipFileName, ZipArchive::CREATE) === TRUE) {
    // Create a recursive iterator to iterate through all files in the source folder
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($GraphicsPath),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {

        // Skip directories named "int"
        if ($file->isDir() && basename($file->getPath()) === "int") {
            continue;
        }

        
        // Skip directories
        if (!$file->isDir()) {
            // Get the file path
            $filePath = $file->getRealPath();

            // Calculate the relative path within the source folder
            $relativePath = substr($filePath, strlen($GraphicsPath) + 1);

            // Add the file to the zip archive
            $zip->addFile($filePath, $relativePath);
        }
    }

    // Close the zip archive
    $zip->close();

    echo "Zip archive created successfully: " . $GraphicsPath_Delivery . '/' . $GraphicszipFileName;
} else {
    echo "Failed to create zip archive";
}
*/


 $GraphicsPath = 'D:/New_Workflow_Files/JOURNAL/production/JOURNALS/SIF/EPL/Vol00000/EPL_6767/FP/Graphics/Print';
 $GraphicsPath_Delivery ='D:/New_Workflow_Files/JOURNAL/production/JOURNALS/SIF/EPL/Vol00000/EPL_6767/FP/Delivery/';

// Name for the output ZIP file
$GraphicszipFileNames ='EPL_6767_graphics.zip';
$GraphicszipFileName ='EPL_6767_graphics.zip';
$zip = new ZipArchive();

// Open the zip archive for writing
if ($zip->open($GraphicsPath_Delivery.$GraphicszipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
    // Get all files in the folder
	//echo $target_dirs=$target_dir;
       // Open the source folder
	   $dir = opendir($GraphicsPath);

	   // Iterate through the files in the folder
	   while (($file = readdir($dir)) !== false) {
		   if ($file != '.' && $file != '..') {
			   $filePath = $GraphicsPath . '/' . $file;
   
			   // Add the file to the zip archive
			   $zip->addFile($filePath, $file);
		   }
	   }
   
	   // Close the source folder
	   closedir($dir);
   
	   // Close the zip archive
	   $zip->close();
}
?>