<?php
ob_start();
session_start();

header("Content-Type: application/json");

require_once __DIR__ . '/RtcTokenBuilder2.php';
require_once __DIR__ . '/RtmTokenBuilder.php';

$appID = "ba85d26a0db94dec82214e061ceaa39c";
$appCertificate = "94b38499a3704fccb2b0b91ad28ab8ec";

const ROLE_PUBLISHER = 1;
const ROLE_RTM_USER = 1;

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit;
}

$reservationId = $_GET['reservation_id'] ?? null;
if (!$reservationId) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing reservation_id"]);
    exit;
}

$uid = (int)$_SESSION['user_id'];
$channelName = "session_" . $reservationId;

try {
    $rtcToken = RtcTokenBuilder2::buildTokenWithUid(
        $appID,
        $appCertificate,
        $channelName,
        $uid,
        ROLE_PUBLISHER,
        time() + 3600
    );

    $rtmToken = RtmTokenBuilder::buildToken(
        $appID,
        $appCertificate,
        (string)$uid,
        ROLE_RTM_USER,
        time() + 3600
    );

    ob_clean();
    echo json_encode([
        "status" => "success",
        "channelName" => $channelName,
        "uid" => $uid,
        "token" => $rtcToken,
        "rtmToken" => $rtmToken
    ]);
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

exit;
