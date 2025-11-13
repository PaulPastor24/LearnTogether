<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . '/RtcTokenBuilder2.php';
require_once __DIR__ . '/RtmTokenBuilder.php';

$appID = "ba85d26a0db94dec82214e061ceaa39c";
$appCertificate = "94b38499a3704fccb2b0b91ad28ab8ec";

const ROLE_PUBLISHER = 1;

function generateRTCToken($channelName, $uid, $expireTimeInSeconds = 3600) {
    global $appID, $appCertificate;

    $role = ROLE_PUBLISHER;
    $privilegeExpiredTs = time() + $expireTimeInSeconds;

    return RtcTokenBuilder2::buildTokenWithUid(
        $appID,
        $appCertificate,
        $channelName,
        $uid,
        $role,
        $privilegeExpiredTs
    );
}

function generateRTMToken($userId, $expireTimeInSeconds = 3600) {
    global $appID, $appCertificate;

    $privilegeExpiredTs = time() + $expireTimeInSeconds;
    $role = RtmTokenBuilder::ROLE_RTM_USER;

    return RtmTokenBuilder::buildToken(
        $appID,
        $appCertificate,
        $userId,
        $role,
        $privilegeExpiredTs
    );
}

// Ensure tutor is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => "Not logged in"
    ]);
    exit;
}

$tutorId = $_SESSION['tutor_id'] ?? $_SESSION['user_id'];
$reservationId = $_GET['reservation_id'] ?? null;

// Use reservationId for unique channel
$channelName = $reservationId ? "session_" . $reservationId : "session_" . $tutorId;

try {
    $rtcToken = generateRTCToken($channelName, (int)$tutorId);
    $rtmToken = generateRTMToken((string)$tutorId);

    echo json_encode([
        "status" => "success",
        "channelName" => $channelName,
        "uid" => (int)$tutorId,
        "token" => $rtcToken,      // match frontend
        "rtmToken" => $rtmToken
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
