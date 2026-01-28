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
    $dashboard = "Tutor/tutorDashboard.php";
} else {
    $learnerStmt = $pdo->prepare("SELECT id FROM learners WHERE user_id = ?");
    $learnerStmt->execute([$user_id]);
    $learner = $learnerStmt->fetch(PDO::FETCH_ASSOC);
    if (!$learner) die("User role not found");
    $userRole = 'learner';
    $profile_id = $learner['id'];
    $dashboard = "Learner/learnerDashboard.php";
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
        WHERE r.learner_id = ? AND r.status = 'Scheduled'
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
        WHERE r.tutor_id = ? AND r.status = 'Scheduled'
        GROUP BY u.id
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$profile_id]);
}

$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$chat_with = isset($_GET['user']) ? (int)$_GET['user'] : null;
$reservation_id = isset($_GET['reservation_id']) ? (int)$_GET['reservation_id'] : null;

if ($reservation_id && !$chat_with) {
    $resStmt = $pdo->prepare("
        SELECT 
            CASE 
                WHEN l.user_id = ? THEN t.user_id
                ELSE l.user_id
            END AS other_user_id,
            CONCAT(u.first_name, ' ', u.last_name) AS name
        FROM reservations r
        JOIN learners l ON r.learner_id = l.id
        JOIN tutors t ON r.tutor_id = t.id
        JOIN users u ON (
            (l.user_id = ? AND u.id = t.user_id) OR
            (t.user_id = ? AND u.id = l.user_id)
        )
        WHERE r.id = ?
        LIMIT 1
    ");
    $resStmt->execute([$user_id, $user_id, $user_id, $reservation_id]);
    $resData = $resStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resData) {
        $chat_with = $resData['other_user_id'];
    }
}

if ($chat_with && !$reservation_id) {
    $resStmt = $pdo->prepare("
        SELECT r.id 
        FROM reservations r
        INNER JOIN learners l ON r.learner_id = l.id
        INNERJOIN tutors t ON r.tutor_id = t.id
        WHERE (l.user_id = :learner_user AND t.user_id = :tutor_user)
          AND r.status = 'Scheduled'
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
if ($chat_with && !$contactName && $reservation_id) {
    $nameStmt = $pdo->prepare("
        SELECT CONCAT(u.first_name, ' ', u.last_name) AS name
        FROM users u
        WHERE u.id = ?
    ");
    $nameStmt->execute([$chat_with]);
    $nameResult = $nameStmt->fetch(PDO::FETCH_ASSOC);
    if ($nameResult) {
        $contactName = $nameResult['name'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chat - LearnTogether</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="CSS/chat.css">
<style>
:root {
    --primary: #10b981;
    --secondary: #34d399;
    --accent: #6ee7b7;
}

body {
    margin: 0;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    height: 100vh;
}

.container-fluid {
    height: 100vh;
}

.sidebar {
    background: white;
    border-right: 2px solid rgba(16, 185, 129, 0.1);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.08);
}

.sidebar-header {
    background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-weight: 700;
    color: var(--primary);
    border-bottom: 2px solid rgba(16, 185, 129, 0.1);
}

.sidebar a {
    border-bottom: 1px solid rgba(16, 185, 129, 0.05);
    color: #333;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.sidebar a:hover {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(52, 211, 153, 0.05) 100%);
    transform: translateX(4px);
    color: var(--primary);
    font-weight: 600;
}

.sidebar a.active {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(52, 211, 153, 0.1) 100%);
    border-left: 4px solid var(--primary);
    color: var(--primary);
    font-weight: 700;
}

.chat-area {
    background: linear-gradient(135deg, #f0fdf4 0%, rgba(16, 185, 129, 0.02) 100%);
    display: flex;
    flex-direction: column;
}

.chat-header {
    background: white;
    border-bottom: 2px solid rgba(16, 185, 129, 0.1);
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.08);
    padding: 16px 20px;
}

.chat-header .btn {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.chat-header .btn-secondary {
    background: linear-gradient(135deg, rgba(100, 100, 100, 0.8) 0%, rgba(80, 80, 80, 0.8) 100%);
    border: none;
    border-radius: 8px;
    color: white;
    font-weight: 600;
}

.chat-header .btn-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.video-btn {
    background: linear-gradient(135deg, #10b981 0%, #34d399 100%) !important;
    border: none !important;
    color: white !important;
    font-weight: 600 !important;
    border-radius: 8px !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3) !important;
}

.video-btn:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4) !important;
    color: white !important;
}

.messages {
    flex-grow: 1;
    background: transparent;
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 20px;
    overflow-y: auto;
}

.message {
    display: flex;
    animation: slideIn 0.3s ease-out;
    max-width: 70%;
    word-wrap: break-word;
    padding: 12px 16px;
    border-radius: 12px;
    line-height: 1.4;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.message.me {
    align-self: flex-end;
    background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
    color: white;
    border-radius: 12px 12px 0 12px;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2);
}

.message.other {
    align-self: flex-start;
    background: white;
    color: #333;
    border-radius: 12px 12px 12px 0;
    border: 1px solid rgba(16, 185, 129, 0.1);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.message small {
    font-size: 11px;
    opacity: 0.7;
    margin-top: 4px;
}

.input-area {
    background: white;
    border-top: 2px solid rgba(16, 185, 129, 0.1);
    padding: 16px 20px;
    gap: 12px;
}

.input-area .form-control {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(52, 211, 153, 0.05) 100%);
    border: 2px solid rgba(16, 185, 129, 0.2);
    border-radius: 8px;
    padding: 12px 16px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    color: #333;
}

.input-area .form-control:focus {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(52, 211, 153, 0.1) 100%);
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    color: #333;
}

.input-area .form-control::placeholder {
    color: #999;
}

.input-area .btn {
    background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
    border: none;
    color: white;
    font-weight: 600;
    border-radius: 8px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    white-space: nowrap;
    padding: 12px 24px;
}

.input-area .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4);
    color: white;
}

.input-area .btn:active {
    transform: translateY(0);
}

@media (max-width: 768px) {
    .sidebar {
        max-width: 100%;
    }
    .message {
        max-width: 85%;
    }
}
</style>
</head>
<body class="vh-100 d-flex flex-column">

<div class="container-fluid h-100">
    <div class="row h-100 g-0">
        <div class="col-12 col-md-3 sidebar p-0 h-100 overflow-auto">
            <div class="sidebar-header p-3 fw-bold">
                👥 <?= $userRole === 'learner' ? 'Your Tutor' : 'Your Learner' ?>
            </div>
            <?php if ($contactName): ?>
                <div class="d-block text-decoration-none p-3 active">
                    ✓ <?= htmlspecialchars($contactName) ?>
                </div>
            <?php else: ?>
                <div class="p-3 text-muted">No active conversations</div>
            <?php endif; ?>
            
            <div class="sidebar-header p-3 fw-bold mt-3">📋 All Contacts</div>
            <?php foreach ($contacts as $c): ?>
                <a href="agoraconvo.php?user=<?= $c['user_id'] ?>&reservation_id=<?= $c['reservation_id'] ?>" 
                   class="d-block text-decoration-none p-3 <?= ($chat_with == $c['user_id']) ? 'active' : '' ?>">
                    <?= htmlspecialchars($c['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="col-12 col-md-9 chat-area d-flex flex-column position-relative h-100 p-0">
            
            <div class="chat-header d-flex justify-content-between align-items-center">
                <div style="font-weight: 700; color: #333; font-size: 18px;">
                    💬 Chat <?= $contactName ? "with " . htmlspecialchars($contactName) : "" ?>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-secondary btn-sm" onclick="history.back()" title="Go back">
                        ⬅ Back
                    </button>

                    <?php if ($chat_with && $reservation_id): ?>
                        <button class="video-btn btn btn-sm"
                            onclick="window.location.href='meetingPage.php?reservation_id=<?= $reservation_id ?>'" 
                            title="Start video call">
                            🎥 Video Call
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="messages flex-grow-1 overflow-auto p-3" id="messages"></div>

            <div class="input-area d-flex gap-2">
                <input id="msgBox" type="text" class="form-control flex-grow-1" placeholder="Type a message..." 
                       onkeypress="if(event.key==='Enter') sendMessage()">
                <button class="btn" onclick="sendMessage()" title="Send message (Enter key also works)">
                    📤 Send
                </button>
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
        .then (data => {
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
