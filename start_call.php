<?php
require '../agora_config.php';
require '../vendor/autoload.php';
use Agora\RtcTokenBuilder2;

$channelName = "learnTogether_" . uniqid();
$uid = rand(1,99999);
$expireTimeInSeconds = 3600;
$currentTimestamp = time();
$privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;

$token = RtcTokenBuilder2::buildTokenWithUid($AGORA_APP_ID, $AGORA_APP_CERT, $channelName, $uid, RtcTokenBuilder2::ROLE_PUBLISHER, $privilegeExpiredTs);

echo json_encode(["token" => $token, "channelName" => $channelName]);
?>
