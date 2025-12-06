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
<title>Session Meeting - Improved Screen Share</title>
<link rel="stylesheet" href="CSS/meetingPage.css">
<script>
const AGORA_APP_ID = "<?= $AGORA_APP_ID ?>";
const RESERVATION_ID = "<?= $reservationId ?>";
</script>
</head>
<body>

<div id="videoContainer"></div>
<div class="controls" id="callControls"></div>
<div class="screen-share-status" id="screenShareStatus">
    🖥️ Screen Sharing Active - Press ESC or click Stop to exit
</div>

<script src="https://download.agora.io/sdk/release/AgoraRTC_N.js"></script>
<script>
let client;
let localTracks = { audioTrack: null, videoTrack: null };
let remoteUsers = {};
let micEnabled = true;
let camEnabled = true;
let screenTrack = null;
let isScreenSharing = false;

console.log("Meeting page initialized");

async function leaveCall() {
    console.log("Leaving call...");
    for (let t of Object.values(localTracks)) {
        if (t) { 
            try {
                t.stop(); 
                t.close();
            } catch (e) {
                console.error("Error stopping track:", e);
            }
        }
    }
    if (screenTrack) {
        try {
            screenTrack.stop();
            await screenTrack.close();
            screenTrack = null;
        } catch (e) {
            console.error("Error closing screen track:", e);
        }
    }
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
        <button id="leaveBtn" class="control-btn end-call">📞</button>
    `;

    const micBtn = document.getElementById("toggleMic");
    const camBtn = document.getElementById("toggleCam");
    const shareBtn = document.getElementById("shareScreenBtn");
    const leaveBtn = document.getElementById("leaveBtn");

    micBtn.onclick = async () => {
        try {
            micEnabled = !micEnabled;
            if (localTracks.audioTrack) {
                await localTracks.audioTrack.setEnabled(micEnabled);
                micBtn.className = micEnabled ? "control-btn active" : "control-btn inactive";
                micBtn.innerText = micEnabled ? "🎤" : "🔇";
            }
        } catch (e) {
            console.error("Error toggling mic:", e);
        }
    };

    camBtn.onclick = async () => {
        try {
            camEnabled = !camEnabled;
            if (localTracks.videoTrack) {
                await localTracks.videoTrack.setEnabled(camEnabled);
                camBtn.className = camEnabled ? "control-btn active" : "control-btn inactive";
                camBtn.innerText = camEnabled ? "📷" : "📹";
            }
        } catch (e) {
            console.error("Error toggling camera:", e);
        }
    };

    shareBtn.onclick = toggleScreenShare;
    leaveBtn.onclick = leaveCall;

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && isScreenSharing) {
            toggleScreenShare();
        }
    });

    console.log("Controls setup completed");
}

function updateLayout() {
    const container = document.getElementById("videoContainer");
    const remoteCount = Object.keys(remoteUsers).length;
    if (remoteCount === 0) {
        container.classList.add("alone");
    } else {
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
    
    try {
        track.play(box);
        console.log(`Video box created for ${name} (${uid})`);
    } catch (e) {
        console.error(`Error playing track for ${name}:`, e);
    }
    
    remoteUsers[uid] = box;
}

function removeVideoBox(uid) {
    const box = document.getElementById(`user-${uid}`);
    if (box) box.remove();
    delete remoteUsers[uid];
    console.log(`Video box removed for user ${uid}`);
}

async function toggleScreenShare() {
    if (!client) {
        console.error("Client not initialized");
        return;
    }

    const shareBtn = document.getElementById("shareScreenBtn");
    const container = document.getElementById("videoContainer");
    const statusDiv = document.getElementById("screenShareStatus");

    try {
        if (!isScreenSharing) {
            console.log("Starting screen share...");
            
            // Create screen track
            try {
                screenTrack = await AgoraRTC.createScreenVideoTrack({
                    encoderConfig: "1080p_1",
                    cursorControl: true
                });
                console.log("Screen track created successfully");
            } catch (e) {
                console.error("Failed to create screen track:", e);
                throw new Error("Unable to access screen. Please check permissions.");
            }

            // Create container for screen share
            const screenBox = document.createElement("div");
            screenBox.id = "screen-share";
            screenBox.className = "video-box screen-share";
            screenBox.style.width = "100%";
            screenBox.style.height = "100%";
            screenBox.style.position = "absolute";
            screenBox.style.top = "0";
            screenBox.style.left = "0";
            screenBox.style.zIndex = "5";
            screenBox.style.backgroundColor = "black";
            
            container.appendChild(screenBox);
            console.log("Screen share container created");

            try {
                await screenTrack.play(screenBox);
                console.log("Screen track playing");
            } catch (e) {
                console.error("Error playing screen track:", e);
                throw new Error("Failed to display screen content");
            }

            try {
                await client.unpublish([localTracks.videoTrack]);
                console.log("Camera unpublished");
            } catch (e) {
                console.error("Error unpublishing camera:", e);
            }
            
            try {
                await client.publish(screenTrack);
                console.log("Screen track published to other users");
            } catch (e) {
                console.error("Error publishing screen track:", e);
                throw new Error("Failed to share screen with other users");
            }
            
            container.classList.add("screen-sharing-active");
            const localBox = document.querySelector(".video-box.local");
            if (localBox) localBox.style.display = "none";
            
            shareBtn.classList.add("active");
            shareBtn.innerText = "🖥️ Stop";
            statusDiv.classList.add("active");
            isScreenSharing = true;
            
            console.log("✅ Screen sharing started");
        } else {
            console.log("Stopping screen share...");
            
            if (screenTrack) {
                try {
                    await client.unpublish(screenTrack);
                    console.log("Screen track unpublished");
                } catch (e) {
                    console.error("Error unpublishing screen:", e);
                }
                
                try {
                    screenTrack.stop();
                    await screenTrack.close();
                    console.log("Screen track stopped and closed");
                } catch (e) {
                    console.error("Error closing screen track:", e);
                }
                
                screenTrack = null;
            }

            const screenBox = document.getElementById("screen-share");
            if (screenBox) screenBox.remove();

            try {
                await client.publish([localTracks.videoTrack]);
                console.log("Camera video republished");
            } catch (e) {
                console.error("Error republishing camera:", e);
            }
            
            // Show local video box again
            const localBox = document.querySelector(".video-box.local");
            if (localBox) localBox.style.display = "block";
            
            container.classList.remove("screen-sharing-active");
            shareBtn.classList.remove("active");
            shareBtn.innerText = "🖥️";
            statusDiv.classList.remove("active");
            isScreenSharing = false;
            
            console.log("✅ Screen sharing stopped");
        }
    } catch (err) {
        console.error("❌ Screen share error:", err);
        alert("Screen sharing failed: " + err.message);
        
        if (screenTrack) {
            try {
                screenTrack.stop();
                await screenTrack.close();
            } catch (e) {
                console.error("Error during cleanup:", e);
            }
            screenTrack = null;
        }
        isScreenSharing = false;
        shareBtn.classList.remove("active");
        statusDiv.classList.remove("active");
    }
}

async function startMeeting() {
    try {
        console.log("Starting meeting with reservation ID:", RESERVATION_ID);
        
        const res = await fetch(`Agora/generate_token.php?reservation_id=${RESERVATION_ID}`);
        const data = await res.json();
        
        if (!data.token) {
            console.error("Token response:", data);
            throw new Error("Failed to get token from server");
        }
        
        console.log("Token received, channel:", data.channelName);

        client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });
        console.log("Agora client created");

        client.on("user-published", async (user, mediaType) => {
            console.log(`User ${user.uid} published ${mediaType}`);
            try {
                await client.subscribe(user, mediaType);

                if (mediaType === "video") {
                    addVideoBox(user.videoTrack, `User ${user.uid}`, user.uid);
                    updateLayout();
                }
                if (mediaType === "audio") {
                    user.audioTrack.play();
                }
            } catch (e) {
                console.error("Error subscribing to user:", e);
            }
        });

        client.on("user-left", user => {
            console.log(`User ${user.uid} left`);
            removeVideoBox(user.uid);
            updateLayout();
        });

        const uid = await client.join(AGORA_APP_ID, data.channelName, data.token, data.uid);
        console.log("Joined channel with UID:", uid);

        const tracks = await AgoraRTC.createMicrophoneAndCameraTracks();
        localTracks.audioTrack = tracks[0];
        localTracks.videoTrack = tracks[1];
        console.log("Microphone and camera tracks created");

        addVideoBox(localTracks.videoTrack, "You", uid, true);
        updateLayout();
        
        await client.publish([localTracks.audioTrack, localTracks.videoTrack]);
        console.log("Local tracks published");

        setupControls();
        console.log("✅ Meeting started successfully");
    } catch (err) {
        console.error("❌ Failed to start call:", err);
        alert("Failed to start call: " + err.message);
    }
}

// Start meeting when page loads
startMeeting();
</script>
</body>
</html>
