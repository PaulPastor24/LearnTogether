<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /LearnTogether/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$tutorStmt = $pdo->prepare("SELECT id FROM tutors WHERE user_id = ?");
$tutorStmt->execute([$user_id]);
$tutor = $tutorStmt->fetch(PDO::FETCH_ASSOC);

if ($tutor) {
    $userRole = 'tutor';
    $profile_id = $tutor['id'];
} else {
    $learnerStmt = $pdo->prepare("SELECT id FROM learners WHERE user_id = ?");
    $learnerStmt->execute([$user_id]);
    $learner = $learnerStmt->fetch(PDO::FETCH_ASSOC);
    if (!$learner) die("User role not found");
    $userRole = 'learner';
    $profile_id = $learner['id'];
}

if ($userRole === 'learner') {
    $stmt = $pdo->prepare("
        SELECT 
            u.id AS user_id,
            CONCAT(u.first_name, ' ', u.last_name) AS name,
            r.id AS reservation_id
        FROM reservations r
        INNER JOIN tutors t ON r.tutor_id = t.id
        INNER JOIN users u ON t.user_id = u.id
        WHERE r.learner_id = ?
        GROUP BY u.id
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$profile_id]);
} else {
    $stmt = $pdo->prepare("
        SELECT 
            u.id AS user_id,
            CONCAT(u.first_name, ' ', u.last_name) AS name,
            r.id AS reservation_id
        FROM reservations r
        INNER JOIN learners l ON r.learner_id = l.id
        INNER JOIN users u ON l.user_id = u.id
        WHERE r.tutor_id = ?
        GROUP BY u.id
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$profile_id]);
}

$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$chat_with = isset($_GET['user']) ? (int)$_GET['user'] : null;
$reservation_id = isset($_GET['reservation_id']) ? (int)$_GET['reservation_id'] : null;

if ($chat_with && !$reservation_id) {
    $resStmt = $pdo->prepare("
        SELECT r.id 
        FROM reservations r
        INNER JOIN learners l ON r.learner_id = l.id
        INNER JOIN tutors t ON r.tutor_id = t.id
        WHERE (l.user_id = :learner_user AND t.user_id = :tutor_user)
        ORDER BY r.created_at DESC
        LIMIT 1
    ");

    if ($userRole === 'learner') {
        $resStmt->execute([
            ':learner_user' => $user_id,
            ':tutor_user'   => $chat_with
        ]);
    } else {
        $resStmt->execute([
            ':learner_user' => $chat_with,
            ':tutor_user'   => $user_id
        ]);
    }

    $reservation_id = $resStmt->fetchColumn();
}

$messages = [];
if ($chat_with) {
    $msgStmt = $pdo->prepare("
        SELECT * FROM messages
        WHERE (sender_id = ? AND receiver_id = ?)
           OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at ASC
    ");
    $msgStmt->execute([$user_id, $chat_with, $chat_with, $user_id]);
    $messages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);
}

$contactName = "";
foreach ($contacts as $c) {
    if ($c['user_id'] == $chat_with) {
        $contactName = $c['name'];
        break;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Chat</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="CSS/chat.css">
</head>
<body class="vh-100 d-flex flex-column">

<div class="container-fluid h-100">
    <div class="row h-100 g-0">
        <div class="col-12 col-md-3 sidebar p-0 h-100 overflow-auto">
            <div class="sidebar-header">Contacts</div>
            <?php foreach ($contacts as $c): ?>
                <a href="agoraconvo.php?user=<?= $c['user_id'] ?>&reservation_id=<?= $c['reservation_id'] ?>" 
                   class="d-block text-decoration-none p-3 <?= ($chat_with == $c['user_id']) ? 'active' : '' ?>">
                    <?= htmlspecialchars($c['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="col-12 col-md-9 chat-area d-flex flex-column position-relative h-100 p-0">
            <div class="chat-header d-flex justify-content-between align-items-center position-relative">
                <div>Chat <?= $contactName ? "with " . htmlspecialchars($contactName) : "" ?></div>
                <?php if ($chat_with && $reservation_id): ?>
                    <button class="video-btn-small position-absolute top-50 end-0 translate-middle-y me-3"
                        onclick="window.open('meetingPage.php?reservation_id=<?= $reservation_id ?>','_blank')">
                        🎥
                    </button>
                <?php endif; ?>
            </div>
            <div class="messages flex-grow-1 overflow-auto p-3" id="messages">
                <?php if ($chat_with && !empty($messages)): ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="message <?= ($msg['sender_id'] == $user_id) ? 'me' : 'other' ?>">
                            <?= htmlspecialchars($msg['message_text']) ?><br>
                            <small class="text-muted"><?= $msg['created_at'] ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="input-area d-flex p-3 border-top bg-white gap-2">
                <input id="msgBox" type="text" class="form-control" placeholder="Type a message...">
                <label class="file-btn mb-0">
                    📎
                    <input type="file" id="fileInput" hidden>
                </label>
                <label class="image-btn mb-0">
                    🖼️
                    <input type="file" id="imageInput" accept="image/*" hidden>
                </label>
                <button class="btn btn-primary mb-0" onclick="sendMessage()">Send</button>
            </div>
        </div>
    </div>
</div>

<script>
function sendMessage(){
    let msg = document.getElementById("msgBox").value;
    let fileInput = document.getElementById("fileInput").files[0];
    let imageInput = document.getElementById("imageInput").files[0];
    if(msg.trim()==="" && !fileInput && !imageInput) return;

    let formData = new FormData();
    formData.append('receiver_id', <?= $chat_with ?? 'null' ?>);
    formData.append('message', msg);
    if(fileInput) formData.append('file', fileInput);
    if(imageInput) formData.append('image', imageInput);

    fetch("messages.php", { method: "POST", body: formData })
      .then(res => res.text())
      .then(() => location.reload());
}
</script>

</body>
</html>
