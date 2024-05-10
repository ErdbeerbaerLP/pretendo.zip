<?php

include "common.php";

$tempDir = sys_get_temp_dir() . "/" . uniqid("3ds_", true);
mkdir($tempDir);

$targetDir = $tempDir . "/target";
mkdir($targetDir, 0777, true);

//Copy pretendo base files
move(
    downloadAndUnzip(getLatestReleaseZipUrl("PretendoNetwork/nimbus")),
    $targetDir
);

zip($targetDir, "[3DS] Pretendo Network.zip");

deleteDirectory($tempDir);
