<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status'=>'error','message'=>'Not authenticated']);
    exit;
}

$sender_id = (int)$_SESSION['user_id'];
$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if ($receiver_id <= 0 || $message === '') {
    echo json_encode(['status'=>'error','message'=>'Invalid input']);
    exit;
}

// Optional: you could check receiver exists or is allowed
$stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)");
$stmt->execute([$sender_id, $receiver_id, $message]);

$lastId = $pdo->lastInsertId();
$select = $pdo->prepare("SELECT id, sender_id, receiver_id, message_text, created_at FROM messages WHERE id = ?");
$select->execute([$lastId]);
$inserted = $select->fetch();

echo json_encode(['status'=>'ok', 'message' => $inserted]);
?>