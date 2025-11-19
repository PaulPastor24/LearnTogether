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
let micEnabled = true;
let camEnabled = true;

async function leaveCall() {
    for (let t of Object.values(localTracks)) if (t) { t.stop(); t.close(); }
    remoteUsers = {};
    if (client) await client.leave();
    window.location.href = document.referrer || '/LearnTogether/';
}

function setupControls() {
    const micBtn = document.getElementById("toggleMic");
    const camBtn = document.getElementById("toggleCam");

    micBtn.onclick = async () => {
        micEnabled = !micEnabled;
        await localTracks.audioTrack.setEnabled(micEnabled);
        micBtn.innerText = micEnabled ? "🎤 Mute" : "🎤 Unmute";
        micBtn.style.backgroundColor = micEnabled ? "#28a745" : "#f44336";
    };

    camBtn.onclick = async () => {
        camEnabled = !camEnabled;
        await localTracks.videoTrack.setEnabled(camEnabled);
        camBtn.innerText = camEnabled ? "📷 Camera Off" : "📷 Camera On";
        camBtn.style.backgroundColor = camEnabled ? "#28a745" : "#f44336";
    };

    document.getElementById("leaveBtn").onclick = leaveCall;
}

async function startMeeting() {
    try {
        const res = await fetch(`Agora/generate_token.php?reservation_id=${RESERVATION_ID}`);
        const data = await res.json();
        if (!data.token) return alert("Failed to get token.");

        client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });

        client.on("user-published", async (user, mediaType) => {
            await client.subscribe(user, mediaType);
            subscribeToUser(user);
        });

        client.on("user-left", user => {
            delete remoteUsers[user.uid];
            const div = document.getElementById(`user-${user.uid}`);
            if (div) div.remove();
        });

        const uid = await client.join(
            AGORA_APP_ID,
            data.channelName,
            data.token,
            data.uid
        );

        [localTracks.audioTrack, localTracks.videoTrack] =
            await AgoraRTC.createMicrophoneAndCameraTracks();

        const localDiv = document.createElement("div");
        localDiv.className = "video-box";
        localDiv.id = `user-${uid}`;
        document.getElementById("videoContainer").appendChild(localDiv);
        localTracks.videoTrack.play(localDiv);

        await client.publish([localTracks.audioTrack, localTracks.videoTrack]);

        setupControls();
    } catch (err) {
        console.error(err);
        alert("Failed to start call: " + err.message);
    }
}

function subscribeToUser(user) {
    if (!remoteUsers[user.uid]) remoteUsers[user.uid] = user;

    let div = document.getElementById(`user-${user.uid}`);
    if (!div) {
        div = document.createElement("div");
        div.className = "video-box";
        div.id = `user-${user.uid}`;
        document.getElementById("videoContainer").appendChild(div);
    }

    if (user.videoTrack) user.videoTrack.play(div);
    if (user.audioTrack) user.audioTrack.play();
}

startMeeting();
</script>

</body>
</html>
