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

<div class="controls" id="callControls"></div>

<script src="https://download.agora.io/sdk/release/AgoraRTC_N.js"></script>
<script>
let client;
let localTracks = { audioTrack: null, videoTrack: null };
let remoteUsers = {};
let micEnabled = true;
let camEnabled = true;
let screenTrack = null;
let isScreenSharing = false;

async function leaveCall() {
    for (let t of Object.values(localTracks)) if (t) { t.stop(); t.close(); }
    if (screenTrack) { await screenTrack.close(); screenTrack = null; }
    remoteUsers = {};
    if (client) await client.leave();
    window.location.href = document.referrer || '/LearnTogether/';
}

function setupControls() {
    const controls = document.getElementById("callControls");
    controls.innerHTML = `
        <button id="toggleMic" class="control-btn active">🎤</button>
        <button id="toggleCam" class="control-btn active">📷</button>
        <button id="shareScreenBtn" class="control-btn">🖥️</button>
        <button id="leaveBtn" class="control-btn end-call">❌</button>
    `;

    const micBtn = document.getElementById("toggleMic");
    const camBtn = document.getElementById("toggleCam");
    const shareBtn = document.getElementById("shareScreenBtn");

    micBtn.onclick = async () => {
        micEnabled = !micEnabled;
        await localTracks.audioTrack.setEnabled(micEnabled);
        micBtn.className = micEnabled ? "control-btn active" : "control-btn inactive";
        micBtn.innerText = micEnabled ? "🎤" : "🔇";
    };

    camBtn.onclick = async () => {
        camEnabled = !camEnabled;
        await localTracks.videoTrack.setEnabled(camEnabled);
        camBtn.className = camEnabled ? "control-btn active" : "control-btn inactive";
        camBtn.innerText = camEnabled ? "📷" : "📹";
    };

    shareBtn.onclick = toggleScreenShare;

    document.getElementById("leaveBtn").onclick = leaveCall;
}

function updateLayout() {
    const container = document.getElementById("videoContainer");
    const remoteCount = Object.keys(remoteUsers).length;

    if (remoteCount === 0) {
        // Only you in the room
        container.classList.add("alone");
    } else {
        // Remote user present → move local to bottom right
        container.classList.remove("alone");
    }
}

function addVideoBox(track, name, uid, isLocal = false) {
    const container = document.getElementById("videoContainer");
    if (!container) return;

    const box = document.createElement("div");
    box.id = `user-${uid}`;
    box.className = isLocal ? "video-box local" : "video-box remote";

    const label = document.createElement("div");
    label.style.position = "absolute";
    label.style.bottom = "5px";
    label.style.left = "5px";
    label.style.color = "white";
    label.style.backgroundColor = "rgba(0,0,0,0.5)";
    label.style.padding = "2px 5px";
    label.style.borderRadius = "4px";
    label.innerText = name;

    box.appendChild(label);
    container.appendChild(box);
    track.play(box);
    remoteUsers[uid] = box;
}

function removeVideoBox(uid) {
    const box = document.getElementById(`user-${uid}`);
    if (box) box.remove();
    delete remoteUsers[uid];
}

async function toggleScreenShare() {
    if (!client) return;
    const shareBtn = document.getElementById("shareScreenBtn");

    try {
        if (!isScreenSharing) {
            // Create screen track
            screenTrack = await AgoraRTC.createScreenVideoTrack({}, "auto");

            // Local preview
            const container = document.createElement("div");
            container.id = `screen-preview`;
            container.className = "video-box local";
            document.getElementById("videoContainer").appendChild(container);

            // Create a video element for local preview
            const videoEl = document.createElement("video");
            videoEl.autoplay = true;
            videoEl.muted = true;
            videoEl.srcObject = screenTrack.mediaStreamTrack ? new MediaStream([screenTrack.mediaStreamTrack]) : null;
            videoEl.style.width = "100%";
            videoEl.style.height = "100%";
            container.appendChild(videoEl);

            await client.publish([screenTrack]);
            shareBtn.innerText = "🖥️"; // icon stays the same
            isScreenSharing = true;
        } else {
            await client.unpublish([screenTrack]);
            screenTrack.close();
            screenTrack = null;

            // Remove local preview
            const container = document.getElementById("screen-preview");
            if (container) container.remove();

            shareBtn.innerText = "🖥️";
            isScreenSharing = false;
        }
    } catch (err) {
        console.error("Screen share error:", err);
        // Do not show any alert for permission denied
    }
}


async function startMeeting() {
    try {
        const res = await fetch(`Agora/generate_token.php?reservation_id=${RESERVATION_ID}`);
        const data = await res.json();
        if (!data.token) return alert("Failed to get token.");

        client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });

        client.on("user-published", async (user, mediaType) => {
            await client.subscribe(user, mediaType);

            if (mediaType === "video") {
                addVideoBox(user.videoTrack, `User ${user.uid}`, user.uid);
                updateLayout();
            }
            if (mediaType === "audio") user.audioTrack.play();
        });

        client.on("user-left", user => {
            removeVideoBox(user.uid);
            updateLayout();
        });

        const uid = await client.join(AGORA_APP_ID, data.channelName, data.token, data.uid);
        [localTracks.audioTrack, localTracks.videoTrack] =
            await AgoraRTC.createMicrophoneAndCameraTracks();

        addVideoBox(localTracks.videoTrack, "You", uid, true);
        updateLayout();
        await client.publish([localTracks.audioTrack, localTracks.videoTrack]);

        setupControls();
    } catch (err) {
        console.error(err);
        alert("Failed to start call: " + err.message);
    }
}

startMeeting();
</script>
</body>
</html>
