<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /LearnTogether/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$reservation_id = $_GET['reservation_id'] ?? null;
if (!$reservation_id) die("Reservation ID required.");

// Fetch reservation info
$stmt = $pdo->prepare("SELECT learner_id, tutor_id FROM reservations WHERE id = ?");
$stmt->execute([$reservation_id]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$reservation) die("Reservation not found.");

// Determine user role
$isLearner = ($user_id == $reservation['learner_id']);
$isTutor   = ($user_id == $reservation['tutor_id']);
if (!$isLearner && !$isTutor) die("Access denied.");

// Channel name: unique per reservation
$channelName = "session_" . $reservation_id;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Video Call — LearnTogether</title>
<script src="https://cdn.agora.io/sdk/release/AgoraRTC_N.js"></script>
<style>
#local_video, #remote_video { width: 48%; height: 400px; display:inline-block; background:#000; }
button { padding:8px 12px; margin:5px; }
</style>
</head>
<body>
<h2>Video Call</h2>
<div id="local_video"></div>
<div id="remote_video"></div>
<div>
<button id="endCall">End Call</button>
<button id="muteAudio">Mute/Unmute</button>
<button id="disableVideo">Stop/Start Video</button>
</div>

<script>
const APP_ID = 'YOUR_AGORA_APP_ID';
let localStream;
let client;
let isAudioMuted = false;
let isVideoDisabled = false;

// Fetch token from backend
async function getToken() {
    const res = await fetch(`agora_token.php?channel=<?= $channelName ?>`);
    const data = await res.json();
    return data;
}

async function startCall() {
    const { token, uid } = await getToken();
    client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });

    await client.join(APP_ID, "<?= $channelName ?>", token, uid);

    localStream = AgoraRTC.createStream({ video: true, audio: true });
    await localStream.init();
    localStream.play('local_video');

    client.publish(localStream);

    client.on('stream-added', function(evt) {
        const remoteStream = evt.stream;
        client.subscribe(remoteStream);
    });

    client.on('stream-subscribed', function(evt) {
        const remoteStream = evt.stream;
        remoteStream.play('remote_video');
    });

    client.on('peer-leave', function(evt) {
        console.log("Remote user left:", evt.uid);
        document.getElementById('remote_video').innerHTML = '';
    });
}

// Button actions
document.getElementById('endCall').addEventListener('click', () => {
    client.leave();
    localStream.close();
    alert('Call ended');
    window.location.href = 'learnerDashboard.php';
});

document.getElementById('muteAudio').addEventListener('click', () => {
    isAudioMuted = !isAudioMuted;
    localStream.isAudioOn() ? localStream.disableAudio() : localStream.enableAudio();
});

document.getElementById('disableVideo').addEventListener('click', () => {
    isVideoDisabled = !isVideoDisabled;
    localStream.isVideoOn() ? localStream.disableVideo() : localStream.enableVideo();
});

startCall();
</script>
</body>
</html>
