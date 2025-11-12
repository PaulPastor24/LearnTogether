<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../Agora/agora_config.php';
use Agora\RtcTokenBuilder;

header('Content-Type: application/json');

$reservation_id = $_GET['reservation_id'] ?? null;
if (!$reservation_id) {
    echo json_encode(['error' => 'Reservation ID required']);
    exit;
}

$channelName = "session_" . $reservation_id;
$uid = rand(1000, 9999);
$role = RtcTokenBuilder::RolePublisher;
$expireTimeInSeconds = 3600; // 1 hour
$currentTimestamp = (new DateTime("now", new DateTimeZone('UTC')))->getTimestamp();
$privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;

try {
    $token = RtcTokenBuilder::buildTokenWithUid(
        $AGORA_APP_ID,
        $AGORA_APP_CERT,
        $channelName,
        $uid,
        $role,
        $privilegeExpiredTs
    );

    echo json_encode([
        "token" => $token,
        "channelName" => $channelName,
        "uid" => $uid
    ]);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
