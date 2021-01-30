<?php

// Get Get Request Username an Platfrom
$nameInput = htmlspecialchars($_GET['id']);
$platform = htmlspecialchars($_GET['platform']);
if ($platform != "xbl") {
    $nameInput = str_replace(' ', '', $nameInput);
} else {
    $nameInput = urlencode($nameInput);
}

$url = 'https://' . $_SERVER['HTTP_HOST'] . '/getStats/getStatsPlayer.php?id=' . $nameInput . '&platform=' . $platform . '&region=' . $sesRegion . '&appcode=809965';

$context = stream_context_create(
    array(
        'ssl' => array(
            'verify_peer'      => false,
            'verify_peer_name' => false,
        )
    )
);

echo 'https://' . $_SERVER['HTTP_HOST'] . '/getStats/getStatsPlayer.php?id=' . $nameInput . '&platform=' . $platform . '&region=' . $sesRegion . '&appcode=809965';

// Get Userdata From Ubisoft
$getUser = json_decode(file_get_contents($url, false, $context), true);

if (!empty($getUser) && !array_key_exists("error", $getUser)) {
    // PLAYER DATA
    // Get display platform
    if ($platform == "uplay") {
        $displayPlatform = "PC";
    } elseif ($platform == "xbl") {
        $displayPlatform = "Xbox";
    } elseif ($platform == "psn") {
        $displayPlatform = "PSN";
    }

    // HMS TIME PLAYED
    // Get general pvp time played HMS
    $generalPvpTimePlayedHMS = timeConverterHMS($getUser["generalpvp_timeplayed"]);
    // Get general pve time played
    $generalPveTimePlayedHMS = timeConverterHMS($getUser["generalpve_timeplayed"]);
    // Get overall time Played in seconds
    $overallTimePlayed = $getUser["generalpvp_timeplayed"] + $getUser["generalpve_timeplayed"];
    // Get overall time Played in HMS
    $overallTimePlayedHMS = timeConverterHMS($overallTimePlayed);

    // GENERAL RANKED TIME PLAYED
    $generalRankedTimePlayedHMS = timeConverterHMS($getUser["rankedpvp_timeplayed"]);
    // GENERAL CASUAL TIME PLAYED
    $generalCasualTimePlayedHMS = timeConverterHMS($getUser["casualpvp_timeplayed"]);

    // GENERAL BOMB TIME PLAYED
    $generalBombTimePlayedHMS = timeConverterHMS($getUser["plantbombpvp_timeplayed"]);
    // GENERAL SECURE TIME PLAYED
    $generalSecureTimePlayedHMS = timeConverterHMS($getUser["secureareapvp_timeplayed"]);
    // GENERAL HOSTAGE TIME PLAYED
    $generalHostageTimePlayedHMS = timeConverterHMS($getUser["rescuehostagepvp_timeplayed"]);

    // GENERAL PVE TIME PLAYED
    $generalPveTimePlayedHMS = timeConverterHMS($getUser["generalpve_timeplayed"]);

    // GENERAL R HOSTAGE PVE TIME PLAYED
    $rescuehPveTimePlayedHMS = timeConverterHMS($getUser["rescuehostagepve_timeplayed"]);
    // GENERAL P HOSTAGE PVE TIME PLAYED
    $protecthPveTimePlayedHMS = timeConverterHMS($getUser["protecthostagepve_timeplayed"]);
    // GENERAL BOMB PVE TIME PLAYED
    $plantbombPveTimePlayedHMS = timeConverterHMS($getUser["plantbombpve_timeplayed"]);

    // GENERAL HOSTAGE TIME PLAYED
    $classicPveTimePlayedHMS = timeConverterHMS($getUser["terrohuntclassicpve_timeplayed"]);

    // GENERAL PVP STATS
    // Get General Pvp PenetrationRatio
    if ($getUser["generalpvp_kills"] != 0) {
        $generalPvpPenetrationRatio = round($getUser["generalpvp_penetrationkills"] / $getUser["generalpvp_kills"], 4);
    }
    // Get General Pvp Headshotratio
    if ($getUser["generalpvp_kills"] != 0) {
        $generalPvpHeadshotRatio = round($getUser["generalpvp_headshot"] / $getUser["generalpvp_kills"], 4);
    } else {
        $generalPvpHeadshotRatio = 0;
    }

    // GENERAL RANKED STATS
    // Get General Ranked KD
    if ($getUser["rankedpvp_death"] != 0) {
        $generalRankedKd = round($getUser['rankedpvp_kills'] / $getUser['rankedpvp_death'], 2);
    } else {
        $generalRankedKd = 0;
    }
    // Get General Ranked Winnrate
    if (($getUser['rankedpvp_matchwon'] + $getUser['rankedpvp_matchlost']) != 0) {
        $generalRankedWinloss = round($getUser['rankedpvp_matchwon'] / ($getUser['rankedpvp_matchwon'] + $getUser['rankedpvp_matchlost']) * 100, 2);
    } else {
        $generalRankedWinloss = 0;
    }

    // foreach ($operators as $operator) {
    //     if ($getUser['operators'][$operator]['info']['category'] == 'atk') {
    //         $atkArray[$getUser['operators'][$operator]['info']['id']] = $getUser['operators'][$operator]['operatorpvp_timeplayed'];
    //     } else {
    //         $defArray[$getUser['operators'][$operator]['info']['id']] = $getUser['operators'][$operator]['operatorpvp_timeplayed'];
    //     }
    // }
    // arsort($atkArray);
    // arsort($defArray);
    // $mostUsedAtk = key($atkArray);
    // $mostUsedDef = key($defArray);
}
