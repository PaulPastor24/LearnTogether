<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_data']['email'])) {
    echo json_encode(["message" => "Session expired. Please sign up again."]);
    exit;
}

$email = $_SESSION['user_data']['email'];

// Fetch user phone
$stmt = $pdo->prepare("SELECT phone FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["message" => "User not found."]);
    exit;
}

// Generate new OTP
$newOtp = rand(100000, 999999);
$pdo->prepare("UPDATE users SET otp = ? WHERE email = ?")->execute([$newOtp, $email]);

// Send via Textbelt
$apiKey = "textbelt"; // demo key
$message = "Your new LearnTogether OTP is: $newOtp";

$response = file_get_contents("https://textbelt.com/text", false, stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query([
            'phone' => $user['phone'],
            'message' => $message,
            'key' => $apiKey,
        ]),
    ],
]));

echo json_encode(["message" => "A new OTP has been sent to your phone."]);
