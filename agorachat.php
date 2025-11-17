<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /LearnTogether/login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Get contacts (everyone except current user)
$stmt = $pdo->prepare("
    SELECT id, CONCAT(first_name, ' ', last_name) AS name
    FROM users
    WHERE id != ?
    ORDER BY first_name ASC, last_name ASC
");
$stmt->execute([$user_id]);
$contacts = $stmt->fetchAll();

// Determine chat partner
$chat_with = isset($_GET['user']) ? (int)$_GET['user'] : (count($contacts) ? (int)$contacts[0]['id'] : null);

// If no contacts, show message
if ($chat_with === null) {
    $contacts = [];
}

// Create a deterministic channel name for Agora (same for both users)
function makeChannelName($a, $b) {
    $a = (int)$a; $b = (int)$b;
    if ($a === 0 || $b === 0) return 'chat_anon';
    return 'chat_' . min($a,$b) . '_' . max($a,$b);
}
$agoraChannel = ($chat_with !== null) ? makeChannelName($user_id, $chat_with) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Messenger Chat + Agora Video</title>
<meta name="viewport" content="width=device-width,initial-scale=1" />
<style>
    body { margin: 0; font-family: Arial, sans-serif; background:#f0f2f5; }
    .container { display:flex; height:100vh; }
    .sidebar { width:280px; background:#fff; border-right:1px solid #ddd; overflow-y:auto; }
    .brand { padding:16px; font-weight:bold; border-bottom:1px solid #eee; }
    .contact { padding:12px 16px; border-bottom:1px solid #f1f1f1; cursor:pointer; display:flex; justify-content:space-between; align-items:center; }
    .contact.active { background:#f5f7fb; }
    .contact:hover { background:#f7f7f7; }
    .chat-area { flex:1; display:flex; flex-direction:column; background:#e5ddd5; }
    .chat-header { background:#fff; padding:12px 16px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #ddd; }
    .video-btn { padding:8px 12px; background:#28a745; color:#fff; border-radius:6px; text-decoration:none; }
    .messages { flex:1; padding:20px; overflow-y:auto; }
    .message { margin-bottom:12px; max-width:70%; padding:10px 14px; border-radius:14px; background:#fff; }
    .message.me { margin-left:auto; background:#0084ff; color:#fff; }
    .meta { font-size:11px; color:#555; margin-top:6px; }
    .input-area { display:flex; padding:12px; background:#fff; border-top:1px solid #ddd; }
    .input-area input { flex:1; padding:12px; border-radius:20px; border:1px solid #ccc; }
    .input-area button { margin-left:10px; padding:10px 16px; border:none; background:#0084ff; color:#fff; border-radius:20px; cursor:pointer; }
    /* small screens */
    @media (max-width:700px) {
        .sidebar { display:none; }
    }
</style>
</head>
<body>

<div class="container">
    <div class="sidebar">
        <div class="brand">LearnTogether — Messages</div>
        <?php if (count($contacts) === 0): ?>
            <div style="padding:16px;color:#666">No contacts found.</div>
        <?php endif; ?>
        <?php foreach ($contacts as $c): ?>
            <a href="?user=<?= $c['id'] ?>" style="display:block;text-decoration:none;color:inherit;">
                <div class="contact <?= ($chat_with == $c['id']) ? 'active' : '' ?>">
                    <div><?= htmlspecialchars($c['name']) ?></div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="chat-area">
        <div class="chat-header">
            <div>
                <?php if ($chat_with !== null): ?>
                    <strong>Chat with <?= htmlspecialchars((function($id){ global $pdo; $s=$pdo->prepare("SELECT CONCAT(first_name,' ',last_name) AS name FROM users WHERE id=?"); $s->execute([$id]); $r=$s->fetch(); return $r ? $r['name'] : "User $id"; })($chat_with)) ?></strong>
                    <div style="font-size:12px;color:#666">User ID: <?= $chat_with ?></div>
                <?php else: ?>
                    <strong>No user selected</strong>
                <?php endif; ?>
            </div>

            <div>
                <?php if ($chat_with !== null): ?>
                    <!-- Video call opens your meeting page or video_call.php. It must accept channel & uid. -->
                    <a class="video-btn" href="video_call.php?channel=<?= urlencode($agoraChannel) ?>&uid=<?= $user_id ?>">🎥 Video Call</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="messages" id="messages" aria-live="polite">
            <!-- Messages are loaded by JS polling -->
            <div style="font-size:13px;color:#666">Loading messages...</div>
        </div>

        <div class="input-area" id="inputArea">
            <input id="msgBox" type="text" placeholder="<?= $chat_with ? 'Type a message...' : 'Select a contact to start chat' ?>" <?= $chat_with ? '' : 'disabled' ?> />
            <button id="sendBtn" <?= $chat_with ? '' : 'disabled' ?>>Send</button>
        </div>
    </div>
</div>

<script>
const CURRENT_USER = <?= json_encode($user_id) ?>;
const CHAT_WITH = <?= json_encode($chat_with) ?>;
const POLL_MS = 2000;
let lastMessageId = 0; // will help with minimal updates

function escapeHTML(s){
    if (!s) return '';
    return s.replace(/[&<>"']/g, function(m){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]); });
}

function renderMessages(msgs, replaceAll = false){
    const container = document.getElementById('messages');
    if (replaceAll) container.innerHTML = '';
    for (const m of msgs){
        // skip already-rendered messages by id
        if (m.id <= lastMessageId) continue;
        const div = document.createElement('div');
        div.className = 'message' + (m.sender_id == CURRENT_USER ? ' me' : '');
        div.innerHTML = '<div>' + escapeHTML(m.message_text) + '</div>' +
                        '<div class="meta">' + escapeHTML(m.created_at) + '</div>';
        container.appendChild(div);
        lastMessageId = Math.max(lastMessageId, parseInt(m.id));
    }
    // scroll to bottom
    container.scrollTop = container.scrollHeight;
}

async function fetchMessages(replaceAll = false){
    if (!CHAT_WITH) return;
    try {
        const res = await fetch('get_messages.php?user=' + encodeURIComponent(CHAT_WITH));
        if (!res.ok) throw new Error('Network error');
        const data = await res.json();
        if (Array.isArray(data)) {
            renderMessages(data, replaceAll);
        }
    } catch (err) {
        console.error('fetchMessages error', err);
    }
}

async function sendMessage(){
    const input = document.getElementById('msgBox');
    const text = input.value.trim();
    if (!text) return;
    // disable while sending
    document.getElementById('sendBtn').disabled = true;
    try {
        const body = new URLSearchParams();
        body.append('receiver_id', CHAT_WITH);
        body.append('message', text);

        const res = await fetch('messages.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: body.toString()
        });
        const j = await res.json();
        if (j && j.status === 'ok') {
            input.value = '';
            // append new message immediately
            renderMessages([j.message], false);
        } else {
            alert('Failed to send message');
        }
    } catch (err) {
        console.error(err);
        alert('Failed to send message (network)');
    } finally {
        document.getElementById('sendBtn').disabled = false;
    }
}

document.getElementById('sendBtn').addEventListener('click', sendMessage);
document.getElementById('msgBox').addEventListener('keydown', function(e){
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});

// initial load
fetchMessages(true);
// polling
setInterval(fetchMessages, POLL_MS);
</script>
</body>
</html>
