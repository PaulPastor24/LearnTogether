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
        WHERE r.learner_id = ? AND r.status = 'Confirmed'
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
        WHERE r.tutor_id = ? AND r.status = 'Confirmed'
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
          AND r.status = 'Confirmed'
        ORDER BY r.created_at DESC
        LIMIT 1
    ");
    if ($userRole === 'learner') {
        $resStmt->execute([
            ':learner_user' => $user_id,
            ':tutor_user' => $chat_with
        ]);
    } else {
        $resStmt->execute([
            ':learner_user' => $chat_with,
            ':tutor_user' => $user_id
        ]);
    }
    $reservation_id = $resStmt->fetchColumn();
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
            <div class="sidebar-header p-3 fw-bold">Contacts</div>
            <?php foreach ($contacts as $c): ?>
                <a href="agoraconvo.php?user=<?= $c['user_id'] ?>&reservation_id=<?= $c['reservation_id'] ?>" 
                   class="d-block text-decoration-none p-3 <?= ($chat_with == $c['user_id']) ? 'active' : '' ?>">
                    <?= htmlspecialchars($c['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="col-12 col-md-9 chat-area d-flex flex-column position-relative h-100 p-0">
            <div class="chat-header d-flex justify-content-between align-items-center p-3 border-bottom">
                <div>Chat <?= $contactName ? "with " . htmlspecialchars($contactName) : "" ?></div>
                <?php if ($chat_with && $reservation_id): ?>
                    <button class="video-btn btn btn-sm btn-primary"
                        onclick="window.open('meetingPage2.php?reservation_id=<?= $reservation_id ?>','_blank')">
                        🎥 Video
                    </button>
                <?php endif; ?>
            </div>
            <div class="messages flex-grow-1 overflow-auto p-3" id="messages"></div>
            <div class="input-area d-flex p-3 border-top bg-white gap-2">
                <input id="msgBox" type="text" class="form-control" placeholder="Type a message...">
                <button class="btn btn-primary" onclick="sendMessage()">Send</button>
            </div>
        </div>
    </div>
</div>

<script>
let chatWith = <?= $chat_with ?? 'null' ?>;
let userId = <?= $user_id ?>;

function loadMessages() {
    if (!chatWith) return;
    fetch(`messages.php?receiver_id=${chatWith}`)
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('messages');
            container.innerHTML = '';
            data.forEach(msg => {
                const div = document.createElement('div');
                div.className = 'message ' + (msg.sender_id == userId ? 'me' : 'other');
                div.innerHTML = `${msg.message_text}<br><small class="text-muted">${msg.created_at}</small>`;
                container.appendChild(div);
            });
            container.scrollTop = container.scrollHeight;
        });
}

setInterval(loadMessages, 2000);
loadMessages();

function sendMessage(){
    let msg = document.getElementById("msgBox").value.trim();
    if(msg === '' || !chatWith) return;

    let formData = new FormData();
    formData.append('receiver_id', chatWith);
    formData.append('message', msg);

    fetch('messages.php', { method: 'POST', body: formData })
        .then(res => res.text())
        .then(() => {
            document.getElementById("msgBox").value = '';
            loadMessages();
        });
}
</script>

</body>
</html>
