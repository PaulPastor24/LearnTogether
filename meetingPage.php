<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /LearnTogether/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$reservationId = $_GET['reservation_id'] ?? null;

if (!$reservationId) die("Reservation ID missing");

$stmt = $pdo->prepare("SELECT role FROM users WHERE id=?");
$stmt->execute([$user_id]);
$userRole = $stmt->fetchColumn();

$AGORA_APP_ID = "ba85d26a0db94dec82214e061ceaa39c";

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Session Meeting</title>
<link rel="stylesheet" href="CSS/meetingPage.css">
<script>
const AGORA_APP_ID = "<?= $AGORA_APP_ID ?>";
const RESERVATION_ID = "<?= $reservationId ?>";
</script>
</head>
<body>
<div id="videoContainer"></div>

<div class="controls">
    <button id="toggleMic">🎤 Mute</button>
    <button id="toggleCam">📷 Camera Off</button>
    <button id="leaveBtn">Leave</button>
</div>

<script src="https://download.agora.io/sdk/release/AgoraRTC_N.js"></script>
<script>
let client;
let localTracks = { audioTrack: null, videoTrack: null };
let remoteUsers = {};

async function startMeeting() {
    try {
        const res = await fetch(`Agora/generate_token.php?reservation_id=${RESERVATION_ID}`);
        const data = await res.json();
        if (!data.token) return alert("Failed to get token.");

        client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });

        const uid = await client.join(AGORA_APP_ID, data.channelName, data.token, data.uid);

        [localTracks.audioTrack, localTracks.videoTrack] = await AgoraRTC.createMicrophoneAndCameraTracks();

        const localDiv = document.createElement("div");
        localDiv.className = "video-box";
        localDiv.id = `user-${uid}`;
        document.getElementById("videoContainer").appendChild(localDiv);
        localTracks.videoTrack.play(localDiv);

        await client.publish([localTracks.audioTrack, localTracks.videoTrack]);
        console.log("Local tracks published successfully");

        client.remoteUsers.forEach(user => {
            subscribeToUser(user, "all");
        });

        client.on("user-published", async (user, mediaType) => {
            await client.subscribe(user, mediaType);
            subscribeToUser(user, mediaType);
        });

        client.on("user-unpublished", user => {
            if (remoteUsers[user.uid]) {
                remoteUsers[user.uid].remove();
                delete remoteUsers[user.uid];
            }
        });

        setupControls();

    } catch (err) {
        console.error(err);
        alert("Failed to start call: " + err.message);
    }
}

function subscribeToUser(user, mediaType) {
    if (!remoteUsers[user.uid]) {
        const remoteDiv = document.createElement("div");
        remoteDiv.className = "video-box";
        remoteDiv.id = `user-${user.uid}`;
        document.getElementById("videoContainer").appendChild(remoteDiv);
        remoteUsers[user.uid] = remoteDiv;
    }

    if ((mediaType === "video" || mediaType === "all") && user.videoTrack) {
        user.videoTrack.play(remoteUsers[user.uid]);
    }
    if ((mediaType === "audio" || mediaType === "all") && user.audioTrack) {
        user.audioTrack.play();
    }
}

function setupControls() {
    const micBtn = document.getElementById("toggleMic");
    const camBtn = document.getElementById("toggleCam");
    const leaveBtn = document.getElementById("leaveBtn");

    micBtn.onclick = () => {
        if (!localTracks.audioTrack) return;
        localTracks.audioTrack.setEnabled(localTracks.audioTrack.isMuted);
        micBtn.innerText = localTracks.audioTrack.isMuted ? "🎤 Mute" : "🎤 Unmute";
    };

    camBtn.onclick = () => {
        if (!localTracks.videoTrack) return;
        localTracks.videoTrack.setEnabled(localTracks.videoTrack.isMuted);
        camBtn.innerText = localTracks.videoTrack.isMuted ? "📷 Camera Off" : "📷 Camera On";
    };

    leaveBtn.onclick = leaveCall;
}

async function leaveCall() {
    for (let trackName in localTracks) {
        const track = localTracks[trackName];
        if (track) {
            track.stop();
            track.close();
        }
    }
    remoteUsers = {};
    if (client) await client.leave();
    window.location.href = document.referrer || '/LearnTogether/';
}

// Start meeting
startMeeting();
</script>
</body>
</html>
