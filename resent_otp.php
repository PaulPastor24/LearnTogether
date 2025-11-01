<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /LearnTogether/login.php");
    exit;
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_data']['email'])) {
    echo json_encode(["message" => "Session expired. Please sign up again."]);
    exit;
}

$email = $_SESSION['user_data']['email'];

$stmt = $pdo->prepare("SELECT phone FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["message" => "User not found."]);
    exit;
}

$newOtp = rand(100000, 999999);

$update = $pdo->prepare("UPDATE users SET otp = ? WHERE email = ?");
$update->execute([$newOtp, $email]);

$apiKey = "textbelt";
$message = "Your new LearnTogether OTP is: $newOtp";

$response = file_get_contents(
    "https://textbelt.com/text",
    false,
    stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query([
                'phone'   => $user['phone'],
                'message' => $message,
                'key'     => $apiKey,
            ]),
        ],
    ])
);

echo json_encode(["message" => "A new OTP has been sent to your phone."]);
