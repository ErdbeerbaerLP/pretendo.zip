<?php

include "common.php";

$tempDir = sys_get_temp_dir() . "/" . uniqid("wiiu_", true);
mkdir($tempDir);

$targetDir = $tempDir . "/target";
mkdir($targetDir, 0777, true);

if (isset($_GET["aroma"])) {
    //Download Aroma if requested
    $addonUrl =
        "https://aroma.foryour.cafe/api/download?packages=environmentloader," .
        $_GET["aroma"];
    $addonDir = downloadAndUnzip($addonUrl);
    $aromaDir = downloadAndUnzip(getLatestReleaseZipUrl("wiiu-env/Aroma"));
    move($aromaDir . "/wiiu", $targetDir . "/wiiu");
    move($addonDir . "/wiiu", $targetDir . "/wiiu");
} else {
    //
    mkdir($targetDir . "/wiiu/environments/aroma/plugins/test", 0777, true);
    mkdir(
        $targetDir . "/wiiu/environments/aroma/modules/setup/test",
        0777,
        true
    );
}

//Copy HBAppStore if requested
if (isset($_GET["hbappstore"])) {
    $appstore = downloadAndUnzip(
        "https://github.com/fortheusers/hb-appstore/releases/latest/download/wiiu-extracttosd.zip"
    );
    move($appstore . "/wiiu", $targetDir . "/wiiu");
}

//Copy sigpatches if requested
if (isset($_GET["sigpatches"])) {
    copy(
        "https://github.com/marco-calautti/SigpatchesModuleWiiU/releases/latest/download/01_sigpatches.rpx",
        $targetDir . "/wiiu/environments/aroma/modules/setup/01_sigpatches.rpx"
    );
}

//Copy pretendo base files
$inkay = downloadAndUnzip(
        "https://github.com/PretendoNetwork/Inkay/releases/latest/download/Inkay-pretendo.zip"
    );

move($inkay, $targetDir . "/wiiu/environments/aroma/plugins/");
copy(
    "https://github.com/PretendoNetwork/Nimble/releases/latest/download/30_nimble.rpx",
    $targetDir . "/wiiu/environments/aroma/modules/setup/30_nimble.rpx"
);

zip($targetDir, "[Wii U] Pretendo Network.zip");

deleteDirectory($tempDir);
