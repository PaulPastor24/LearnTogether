<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status'=>'error','message'=>'Not authenticated']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    if ($receiver_id <= 0 || $message === '') {
        echo json_encode(['status'=>'error','message'=>'Invalid input']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $receiver_id, $message]);

    $lastId = $pdo->lastInsertId();
    $select = $pdo->prepare("SELECT id, sender_id, receiver_id, message_text, created_at FROM messages WHERE id = ?");
    $select->execute([$lastId]);
    $inserted = $select->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['status'=>'ok', 'message' => $inserted]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $receiver_id = isset($_GET['receiver_id']) ? (int)$_GET['receiver_id'] : 0;
    if ($receiver_id <= 0) {
        echo json_encode([]);
        exit;
    }

    $msgStmt = $pdo->prepare("
        SELECT id, sender_id, receiver_id, message_text, created_at
        FROM messages
        WHERE (sender_id = ? AND receiver_id = ?)
           OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at ASC
    ");
    $msgStmt->execute([$user_id, $receiver_id, $receiver_id, $user_id]);
    $messages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($messages);
    exit;
}

echo json_encode(['status'=>'error','message'=>'Invalid request']);
