<?php
$tempDir = sys_get_temp_dir() . '/' . uniqid('wiiu_', true);
mkdir($tempDir);
// Function to download and unzip a file
function downloadAndUnzip($url) {
    global $tempDir;
    // Create a temporary directory
    $zipDir = $tempDir . '/' . uniqid('temp_', true);
    mkdir($zipDir);

    // Download the file
    $fileContents = file_get_contents($url);

    if ($fileContents === false) {
        die('Failed to download the file.');
    }

    // Save the downloaded file to the temporary directory
    $zipFile = $zipDir .uniqid('zip_', true). '.zip';
    file_put_contents($zipFile, $fileContents);

    // Open the zip file with ZipArchive
    $zip = new ZipArchive;
    if ($zip->open($zipFile) !== true) {
        die('Failed to open the zip file.');
    }

    // Extract the files
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entryName = $zip->getNameIndex($i);
        $entryDir = dirname($entryName);
        $extractPath = $zipDir . '/' . $entryName;

        // Create the directory if it doesn't exist
        if (!is_dir($zipDir . '/' . $entryDir)) {
            mkdir($zipDir . '/' . $entryDir, 0755, true);
        }

        // Extract the entry
        if ($zip->extractTo($zipDir, $entryName)) {
//            echo "Extracted $entryName\n";
        } else {
//            echo "Failed to extract $entryName\n";
        }
    }

    // Close the zip file
    $zip->close();

    return $zipDir;
}

function moveDirectoryWithOverwrite($sourceDir, $destinationDir) {
    // Check if the source directory exists
    if (!is_dir($sourceDir)) {
        echo "Source directory $sourceDir does not exist.";
        return;
    }

    // Create the destination directory if it doesn't exist
    if (!is_dir($destinationDir)) {
        mkdir($destinationDir, 0755, true);
    }

    // Open the source directory
    if ($handle = opendir($sourceDir)) {
        // Loop through the directory
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                $sourceFile = $sourceDir . '/' . $entry;
                $destinationFile = $destinationDir . '/' . $entry;

                // If it's a directory, recursively move it
                if (is_dir($sourceFile)) {
                    moveDirectoryWithOverwrite($sourceFile, $destinationFile);
                } else {
                    // Copy the file and overwrite if it exists
                    copy($sourceFile, $destinationFile);
                    unlink($sourceFile);
                }
            }
        }

        // Close the directory handle
        closedir($handle);
    } else {
        echo "Failed to open source directory $sourceDir.";
    }
}


// To clean up, delete the temporary directory and its contents
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    return rmdir($dir);
}

function zipAndServe($directory) {
    // Create a zip archive
    $zip = new ZipArchive();
    $zipFileName = '[Wii U] Pretendo Network.zip';
    if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        die('Failed to create zip archive');
    }

    // Add files from the directory to the zip archive
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($directory) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }

    // Close the zip archive
    $zip->close();

    // Set headers to force download the zip file
    header('Content-Type: application/zip');
    header('Content-disposition: attachment; filename=' . $zipFileName);
    header('Content-Length: ' . filesize($zipFileName));
    readfile($zipFileName);

    // Clean up
    unlink($zipFileName);
}


$targetDir = $tempDir . '/target';
mkdir($targetDir, 0777, true);

if(isset($_GET['aroma'])) {
    $addonUrl="https://aroma.foryour.cafe/api/download?packages=environmentloader,".$_GET['aroma'];
    $addonDir = downloadAndUnzip($addonUrl);
    $aromaDir = downloadAndUnzip("https://github.com/wiiu-env/Aroma/releases/download/beta-17/aroma-beta-17.zip");
    moveDirectoryWithOverwrite($aromaDir."/wiiu", $targetDir."/wiiu");
    moveDirectoryWithOverwrite($addonDir."/wiiu", $targetDir."/wiiu");
// Open the directory
}else{
mkdir($targetDir."/wiiu/environments/aroma/plugins/test", 0777, true);
mkdir($targetDir."/wiiu/environments/aroma/modules/setup/test", 0777, true);
}

if(isset($_GET['hbappstore'])) {
   $appstore = downloadAndUnzip("https://github.com/fortheusers/hb-appstore/releases/latest/download/wiiu-extracttosd.zip");
   moveDirectoryWithOverwrite($appstore."/wiiu", $targetDir."/wiiu");
}

if(isset($_GET['sigpatches'])) {
   copy("https://github.com/marco-calautti/SigpatchesModuleWiiU/releases/latest/download/01_sigpatches.rpx", $targetDir."/wiiu/environments/aroma/modules/setup/01_sigpatches.rpx");
}

copy("https://github.com/PretendoNetwork/Inkay/releases/latest/download/Inkay-pretendo.wps", $targetDir."/wiiu/environments/aroma/plugins/Inkay-pretendo.wps");
copy("https://github.com/PretendoNetwork/Nimble/releases/latest/download/30_nimble.rpx", $targetDir."/wiiu/environments/aroma/modules/setup/30_nimble.rpx");

function listFilesRecursive($directory) {
    if (!is_dir($directory)) {
        echo "Directory $directory does not exist.";
        return;
    }

    if ($handle = opendir($directory)) {
        echo "Files in $directory:<br>";

        // Loop through the directory
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                echo "$entry<br>";
                // If it's a directory, recursively list its files
                if (is_dir($directory . '/' . $entry)) {
                    listFilesRecursive($directory . '/' . $entry);
                }
            }
        }

        // Close the directory handle
        closedir($handle);
    } else {
        echo "Failed to open directory $directory.";
    }
}
zipAndServe($targetDir);
//listFilesRecursive($targetDir);



deleteDirectory($tempDir);
