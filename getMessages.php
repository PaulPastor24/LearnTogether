<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$other = isset($_GET['user']) ? (int)$_GET['user'] : 0;
if ($other <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, sender_id, receiver_id, message_text, created_at
    FROM messages
    WHERE (sender_id = ? AND receiver_id = ?)
       OR (sender_id = ? AND receiver_id = ?)
    ORDER BY created_at ASC
    LIMIT 100
");
$stmt->execute([$user_id, $other, $other, $user_id]);
$msgs = $stmt->fetchAll();

echo json_encode($msgs);
?>