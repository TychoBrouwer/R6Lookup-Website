<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include $_SERVER['DOCUMENT_ROOT'] . '/getStats/config.php';

include $_SERVER['DOCUMENT_ROOT'] . '/getStats/UbiAPI.php';

$uapi = new UbiAPI($config["ubi-email"], $config["ubi-password"]);

$uid = '37d25f56-26bb-432e-8050-896e442763aa';

////////////////////////////////////////////////////////////////////////
$su = $uapi->getStatsTest($uid);

header('Content-type: Application/JSON');
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

print json_encode($su, JSON_PRETTY_PRINT);
