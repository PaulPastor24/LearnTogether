<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_GET['uid'])) {
    echo json_encode(['error' => 'UID not provided']);
    exit;
}

$uid = intval($_GET['uid']);

try {
    $stmt = $pdo->prepare("SELECT first_name, last_name, role FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $full_name = $user['first_name'];
        if (!empty($user['last_name'])) {
            $full_name .= ' ' . $user['last_name'];
        }
        
        echo json_encode([
            'success' => true,
            'name' => $full_name,
            'role' => $user['role'] ?? 'user'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'name' => "User $uid",
            'role' => 'participant'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'name' => "User $uid",
        'role' => 'participant'
    ]);
}
?>
