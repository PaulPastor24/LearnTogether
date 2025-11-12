<?php
require '../db.php';

// Include your Agora credentials
define('APP_ID', 'ba85d26a0db94dec82214e061ceaa39c');
define('APP_CERTIFICATE', '94b38499a3704fccb2b0b91ad28ab8ec');

use \Firebase\JWT\JWT;

$channelName = $_GET['channel'] ?? '';
$uid = $_GET['uid'] ?? rand(1, 99999);

if (!$channelName) {
    http_response_code(400);
    echo json_encode(['error' => 'Channel name is required']);
    exit;
}

// Token expiration: 1 hour
$expireTime = time() + 3600;

// Build the token using Agora AccessToken (simplified using JWT)
$token = [
    "uid" => $uid,
    "appId" => APP_ID,
    "channelName" => $channelName,
    "exp" => $expireTime
];

// Use Firebase JWT to generate token
$agoraToken = JWT::encode($token, APP_CERTIFICATE);

header('Content-Type: application/json');
echo json_encode([
    'token' => $agoraToken,
    'uid' => $uid
]);
