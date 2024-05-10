<?php
// Function to download and unzip a file
function downloadAndUnzip($url)
{
    global $tempDir;
    // Create a temporary directory
    $zipDir = $tempDir . "/" . uniqid("temp_", true);
    mkdir($zipDir);

    // Download the file
    $fileContents = file_get_contents($url);

    if ($fileContents === false) {
        header("HTTP/1.1 500 Internal Server Error");
        die("Failed to download the file.");
    }

    // Save the downloaded file to the temporary directory
    $zipFile = $zipDir . uniqid("zip_", true) . ".zip";
    file_put_contents($zipFile, $fileContents);

    // Open the zip file with ZipArchive
    $zip = new ZipArchive();
    if ($zip->open($zipFile) !== true) {
        header("HTTP/1.1 500 Internal Server Error");
        die("Failed to open the zip file.");
    }

    // Extract the files
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entryName = $zip->getNameIndex($i);
        $entryDir = dirname($entryName);
        $extractPath = $zipDir . "/" . $entryName;

        // Create the directory if it doesn't exist
        if (!is_dir($zipDir . "/" . $entryDir)) {
            mkdir($zipDir . "/" . $entryDir, 0755, true);
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

function move($sourceDir, $destinationDir)
{
    // Check if the source directory exists
    if (!is_dir($sourceDir)) {
        header("HTTP/1.1 500 Internal Server Error");
        die("Source directory $sourceDir does not exist.");
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
                $sourceFile = $sourceDir . "/" . $entry;
                $destinationFile = $destinationDir . "/" . $entry;

                // If it's a directory, recursively move it
                if (is_dir($sourceFile)) {
                    move($sourceFile, $destinationFile);
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
        header("HTTP/1.1 500 Internal Server Error");
        die("Failed to open source directory $sourceDir.");
    }
}

// To clean up, delete the temporary directory and its contents
function deleteDirectory($dir)
{
    if (!file_exists($dir)) {
        return true;
    }
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    foreach (scandir($dir) as $item) {
        if ($item == "." || $item == "..") {
            continue;
        }
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    return rmdir($dir);
}

function zip($directory, $zipFileName)
{
    // Create a zip archive
    $zip = new ZipArchive();
    if (
        $zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !==
        true
    ) {
        header("HTTP/1.1 500 Internal Server Error");
        die("Failed to create zip archive");
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
    header("Content-Type: application/zip");
    header("Content-disposition: attachment; filename=" . $zipFileName);
    header("Content-Length: " . filesize($zipFileName));
    readfile($zipFileName);

    // Clean up
    unlink($zipFileName);
}


function getLatestReleaseZipUrl($repo) {
    // GitHub API URL for releases
    $url = "https://api.github.com/repos/{$repo}/releases/latest";

    // Initialize cURL session
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-Agent: PHP'));

    // Execute cURL session
    $response = curl_exec($ch);

    // Close cURL session
    curl_close($ch);

    // Decode JSON response
    $releaseData = json_decode($response, true);

    // Check if release data is fetched successfully
    if (isset($releaseData['assets'])) {
      if(isset($releaseData['assets'][0])){
        return $releaseData['assets'][0]['browser_download_url'];
      }else return null;
        return $releaseData['zipball_url'];
    } else {
        return null;
    }
}
