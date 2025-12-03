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
if (!navigator.mediaDevices) {
    navigator.mediaDevices = {};
}

if (!navigator.mediaDevices.getUserMedia) {
    navigator.mediaDevices.getUserMedia = function(constraints) {
        const getUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia;
        
        if (!getUserMedia) {
            return Promise.reject(new Error('getUserMedia is not supported in this browser'));
        }
        
        return new Promise((resolve, reject) => {
            getUserMedia.call(navigator, constraints, resolve, reject);
        });
    };
}

let client;
let localTracks = { audioTrack: null, videoTrack: null };
let remoteUsers = {};
let micEnabled = true;
let camEnabled = true;
let screenTrack = null;
let isScreenSharing = false;
let remoteScreenSharerUid = null;
let remotePresenterVideoUid = null;

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
        <button id="leaveBtn" class="control-btn end-call">📞</button>
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
        container.classList.add("alone");
    } else {
        container.classList.remove("alone");
    }
}

function applyViewerScreenShareLayout() {
    // When viewing someone's screen share, show both local and remote cameras centered on the right
    const container = document.getElementById("videoContainer");
    container.classList.add("screen-sharing-active");
    
    // Position local camera on the right (centered vertically)
    const localBox = document.querySelector(".video-box.local");
    if (localBox) {
        localBox.style.display = "block";
        localBox.style.position = "absolute";
        localBox.style.top = "50%";
        localBox.style.right = "20px";
        localBox.style.transform = "translateY(-50%)";
        localBox.style.width = "20%";
        localBox.style.height = "auto";
        localBox.style.maxHeight = "35vh";
        localBox.style.border = "3px solid white";
        localBox.style.borderRadius = "12px";
        localBox.style.zIndex = "20";
        localBox.style.overflow = "hidden";
        localBox.style.aspectRatio = "16/9";
        localBox.style.minHeight = "150px";
    }
    
    // Stack remote cameras around center on the right
    let cameraIndex = 0;
    Object.values(remoteUsers).forEach(box => {
        if (!box.classList.contains('local')) {
            box.style.display = "block";
            box.style.position = "absolute";
            box.style.top = `calc(50% + ${(cameraIndex + 1) * 170}px - 50%)`;
            box.style.right = "20px";
            box.style.transform = "none";
            box.style.width = "20%";
            box.style.height = "auto";
            box.style.maxHeight = "35vh";
            box.style.border = "3px solid white";
            box.style.borderRadius = "12px";
            box.style.zIndex = "19";
            box.style.overflow = "hidden";
            box.style.aspectRatio = "16/9";
            box.style.minHeight = "150px";
            cameraIndex++;
        }
    });
}

function applyScreenSharingLayout(screenOwnerUid = null, isRemoteScreen = false) {
    const container = document.getElementById("videoContainer");
    const localBox = document.querySelector(".video-box.local");
    
    container.classList.add("screen-sharing-active");
    
    if (isRemoteScreen) {
        // Remote user is sharing their screen
        const screenBox = document.getElementById(`user-${screenOwnerUid}`);
        if (screenBox) {
            // Screen on the left side
            screenBox.style.position = "absolute";
            screenBox.style.top = "0";
            screenBox.style.left = "0";
            screenBox.style.width = "60%";
            screenBox.style.height = "100%";
            screenBox.style.zIndex = "5";
            screenBox.style.border = "none";
            screenBox.style.borderRadius = "0";
            screenBox.style.backgroundColor = "#000000";
            screenBox.classList.add("screen-share-box");
        }
        
        // Local video on the right
        if (localBox) {
            localBox.classList.add("pip-mode");
            localBox.style.display = "block";
            localBox.style.position = "absolute";
            localBox.style.top = "calc(50% - 35px - 200px)";
            localBox.style.bottom = "auto";
            localBox.style.right = "100px";
            localBox.style.transform = "none";
            localBox.style.width = "23%";
            localBox.style.height = "auto";
            localBox.style.maxHeight = "40vh";
            localBox.style.marginLeft = "0";
            localBox.style.border = "3px solid white";
            localBox.style.borderRadius = "12px";
            localBox.style.zIndex = "20";
            localBox.style.overflow = "hidden";
            localBox.style.aspectRatio = "16/9";
            localBox.style.minHeight = "200px";
        }
        
        // Other remote videos below local video
        Object.entries(remoteUsers).forEach(([uid, box]) => {
            if (uid != screenOwnerUid && !box.classList.contains('local')) {
                box.style.display = "block !important";
                box.classList.add("remote-pip-mode");
                box.style.position = "absolute";
                box.style.bottom = "auto";
                box.style.top = "calc(50% + 35px)";
                box.style.right = "100px";
                box.style.transform = "none";
                box.style.width = "23%";
                box.style.height = "auto";
                box.style.maxHeight = "40vh";
                box.style.marginLeft = "0";
                box.style.border = "3px solid white";
                box.style.borderRadius = "12px";
                box.style.zIndex = "19";
                box.style.overflow = "hidden";
                box.style.aspectRatio = "16/9";
                box.style.minHeight = "200px";
            }
        });
    } else {
        // Local user is sharing their screen (screen is in the container element)
        // Hide local camera video since we're sharing screen
        if (localBox) {
            localBox.style.display = "none";
        }
        
        // Position remote videos on the right
        Object.values(remoteUsers).forEach(box => {
            if (!box.classList.contains('local')) {
                box.style.display = "block !important";
                box.classList.add("remote-pip-mode");
                box.style.position = "absolute";
                box.style.bottom = "auto";
                box.style.top = "calc(50% + 35px)";
                box.style.right = "100px";
                box.style.transform = "none";
                box.style.width = "23%";
                box.style.height = "auto";
                box.style.maxHeight = "40vh";
                box.style.marginLeft = "0";
                box.style.border = "3px solid white";
                box.style.borderRadius = "12px";
                box.style.zIndex = "19";
                box.style.overflow = "hidden";
                box.style.aspectRatio = "16/9";
                box.style.minHeight = "200px";
            }
        });
    }
}

function resetLayout() {
    const container = document.getElementById("videoContainer");
    const localBox = document.querySelector(".video-box.local");
    
    container.classList.remove("screen-sharing-active");
    
    // Reset local video
    if (localBox) {
        localBox.classList.remove("pip-mode");
        localBox.style.display = "";
        localBox.style.position = "";
        localBox.style.bottom = "";
        localBox.style.right = "";
        localBox.style.top = "";
        localBox.style.transform = "";
        localBox.style.width = "";
        localBox.style.height = "";
        localBox.style.maxHeight = "";
        localBox.style.marginLeft = "";
        localBox.style.border = "";
        localBox.style.borderRadius = "";
        localBox.style.zIndex = "";
        localBox.style.overflow = "";
        localBox.style.aspectRatio = "";
        localBox.style.minHeight = "";
    }
    
    // Reset remote videos
    Object.values(remoteUsers).forEach(box => {
        if (!box.classList.contains('local')) {
            box.classList.remove("remote-pip-mode");
            box.classList.remove("screen-share-box");
            box.style.position = "";
            box.style.top = "";
            box.style.left = "";
            box.style.right = "";
            box.style.bottom = "";
            box.style.width = "";
            box.style.height = "";
            box.style.zIndex = "";
            box.style.border = "";
            box.style.borderRadius = "";
            box.style.maxHeight = "";
            box.style.overflow = "";
            box.style.aspectRatio = "";
            box.style.minHeight = "";
            box.style.transform = "";
            box.style.marginLeft = "";
            box.style.backgroundColor = "";
            box.style.display = "";
        }
    });
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
    if (!client) {
        console.error("Client not initialized");
        return;
    }
    
    const shareBtn = document.getElementById("shareScreenBtn");
    const container = document.getElementById("videoContainer");

    try {
        if (!isScreenSharing) {
            // Start screen sharing
            console.log("Starting screen share...");
            
            // Create screen track with proper configuration
            console.log("Creating screen track...");
            screenTrack = await AgoraRTC.createScreenVideoTrack({
                encoderConfig: "1080p_1",
                cursorControl: true
            });

            console.log("Screen track object:", screenTrack);
            console.log("Screen track type:", typeof screenTrack);

            // Create screen share container (like Google Meet)
            const screenBox = document.createElement("div");
            screenBox.id = "screen-share";
            screenBox.className = "screen-share-container";
            container.appendChild(screenBox);
            console.log("Screen share container created");

            // Play screen track
            try {
                console.log("Playing screen track...");
                await screenTrack.play(screenBox);
                console.log("✓ Screen track is now playing");
            } catch (e) {
                console.error("Error playing screen track:", e);
            }

            // Publish screen track to remote users (don't unpublish camera, publish both)
            console.log("Publishing screen track alongside camera...");
            try {
                await client.publish([screenTrack, localTracks.videoTrack]);
                console.log("✓ Screen and camera tracks published");
            } catch (e) {
                // If we can't publish both, unpublish camera first then publish screen only
                if (e.code === "CAN_NOT_PUBLISH_MULTIPLE_VIDEO_TRACKS") {
                    console.log("Can't publish both tracks, switching to screen only...");
                    await client.unpublish([localTracks.videoTrack]);
                    await client.publish(screenTrack);
                    console.log("✓ Screen track published (camera unpublished)");
                } else {
                    throw e;
                }
            }
            
            // Show local camera in PiP mode on the right (centered vertically)
            const localBox = document.querySelector(".video-box.local");
            if (localBox) {
                // Clear the box and replay the camera
                localBox.innerHTML = '';
                
                // Replay the local camera track
                try {
                    await localTracks.videoTrack.play(localBox);
                    console.log("✓ Local camera playing in PiP mode");
                } catch (e) {
                    console.error("Error playing local camera:", e);
                }
                
                localBox.style.display = "block";
                localBox.style.position = "absolute";
                localBox.style.top = "50%";
                localBox.style.right = "60px";
                localBox.style.transform = "translateY(-50%)";
                localBox.style.width = "20%";
                localBox.style.height = "auto";
                localBox.style.maxHeight = "35vh";
                localBox.style.border = "3px solid white";
                localBox.style.borderRadius = "12px";
                localBox.style.zIndex = "20";
                localBox.style.overflow = "hidden";
                localBox.style.aspectRatio = "16/9";
                localBox.style.minHeight = "150px";
                localBox.classList.add("pip-mode");
            }
            
            // Show remote videos stacked around center on the right
            let remoteIndex = 0;
            Object.values(remoteUsers).forEach((box) => {
                if (!box.classList.contains('local')) {
                    box.style.display = "block";
                    box.style.position = "absolute";
                    box.style.top = `calc(50% + ${(remoteIndex + 1) * 170}px - 50%)`;
                    box.style.right = "20px";
                    box.style.transform = "none";
                    box.style.width = "20%";
                    box.style.height = "auto";
                    box.style.maxHeight = "35vh";
                    box.style.border = "3px solid white";
                    box.style.borderRadius = "12px";
                    box.style.zIndex = "19";
                    box.style.overflow = "hidden";
                    box.style.aspectRatio = "16/9";
                    box.style.minHeight = "150px";
                    box.classList.add("remote-pip-mode");
                    remoteIndex++;
                }
            });
            
            container.classList.add("screen-sharing-active");
            shareBtn.classList.add("active");
            shareBtn.innerText = "🖥️ Stop";
            isScreenSharing = true;
            
            console.log("✅ Screen sharing started");
        } else {
            // Stop screen sharing
            console.log("Stopping screen share...");
            
            if (screenTrack) {
                console.log("Unpublishing screen track...");
                await client.unpublish([screenTrack]);
                console.log("✓ Screen track unpublished");
                
                console.log("Stopping and closing screen track...");
                screenTrack.stop();
                await screenTrack.close();
                console.log("✓ Screen track stopped and closed");
                screenTrack = null;
            }

            const screenBox = document.getElementById('screen-share-box');
            if (screenBox) {
                screenBox.remove();
                console.log("✓ Screen box removed");
            }

            // Camera is still published, no need to republish
            console.log("✓ Camera video still active");
            
            // Restore normal layout
            Object.values(remoteUsers).forEach(box => {
                if (!box.classList.contains('local')) {
                    box.classList.remove("remote-pip-mode");
                    box.style.position = "";
                    box.style.top = "";
                    box.style.bottom = "";
                    box.style.right = "";
                    box.style.transform = "";
                    box.style.width = "";
                    box.style.height = "";
                    box.style.maxHeight = "";
                    box.style.marginLeft = "";
                    box.style.border = "";
                    box.style.borderRadius = "";
                    box.style.zIndex = "";
                    box.style.overflow = "";
                    box.style.aspectRatio = "";
                    box.style.minHeight = "";
                    box.style.display = 'block';
                }
            });
            
            const localBox = document.querySelector(".video-box.local");
            if (localBox) {
                localBox.classList.remove("pip-mode");
                localBox.style.width = "";
                localBox.style.height = "";
                localBox.style.bottom = "";
                localBox.style.right = "";
                localBox.style.position = "";
                localBox.style.borderRadius = "";
                localBox.style.border = "";
                localBox.style.top = "";
                localBox.style.transform = "";
                localBox.style.maxHeight = "";
                localBox.style.marginTop = "";
                localBox.style.marginLeft = "";
                localBox.style.zIndex = "";
                localBox.style.overflow = "";
                localBox.style.aspectRatio = "";
                localBox.style.minHeight = "";
            }
            
            container.classList.remove("screen-sharing-active");
            shareBtn.classList.remove("active");
            shareBtn.innerText = "🖥️";
            isScreenSharing = false;
            updateLayout();
            
            console.log("✅ Screen sharing stopped");
        }
    } catch (err) {
        console.error("❌ Screen share error:", err);
        console.error("Error stack:", err.stack);
        alert("Screen sharing failed: " + err.message + "\n\nCheck browser console for details.");
        
        // Reset state on error
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
        
        // Remove the screen box if it exists
        const screenBox = document.getElementById("screen-share");
        if (screenBox) screenBox.remove();
    }
}


async function startMeeting() {
    try {
        // Check if browser supports getUserMedia
        const constraints = { audio: true, video: true };
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            throw new Error("Your browser does not support camera/microphone access (getUserMedia not available)");
        }

        // Check HTTPS or localhost
        if (location.protocol !== "https:" && location.hostname !== "localhost" && location.hostname !== "127.0.0.1") {
            console.warn("⚠️ Warning: Camera/microphone access requires HTTPS (or localhost for development)");
        }

        const res = await fetch(`Agora/generate_token.php?reservation_id=${RESERVATION_ID}`);
        const data = await res.json();
        if (!data.token) return alert("Failed to get token.");

        client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });

        client.on("user-published", async (user, mediaType) => {
            await client.subscribe(user, mediaType);

            if (mediaType === "video") {
                // Check if this is a screen track
                const isScreenTrack = user.videoTrack && (
                    user.videoTrack.source === 'screen' || 
                    user.videoTrack.trackMediaStreamTrack?.getSettings?.()?.displaySurface === 'monitor'
                );
                
                if (isScreenTrack) {
                    // Remote user is sharing their screen
                    console.log("Remote screen track received from user", user.uid);
                    remoteScreenSharerUid = user.uid;
                    
                    // Create screen share container for the viewer
                    const container = document.getElementById("videoContainer");
                    const screenBox = document.createElement("div");
                    screenBox.id = "screen-share-remote";
                    screenBox.className = "screen-share-container";
                    screenBox.style.width = "60%";
                    screenBox.style.height = "100%";
                    screenBox.style.position = "absolute";
                    screenBox.style.top = "0";
                    screenBox.style.left = "0";
                    screenBox.style.zIndex = "5";
                    screenBox.style.backgroundColor = "black";
                    
                    container.appendChild(screenBox);
                    
                    // Play screen track
                    try {
                        await user.videoTrack.play(screenBox);
                        console.log("Remote screen track playing");
                    } catch (e) {
                        console.error("Error playing remote screen track:", e);
                    }
                    
                    // Apply viewer layout: show all cameras on the right
                    applyViewerScreenShareLayout();
                } else {
                    // Regular camera video - could be from presenter or other remote user
                    addVideoBox(user.videoTrack, `User ${user.uid}`, user.uid);
                    
                    // If this user is the one screen sharing, track their video for camera display
                    if (user.uid === remoteScreenSharerUid) {
                        remotePresenterVideoUid = user.uid;
                    }
                    
                    // If remote user is screen sharing, apply pip layout to this camera
                    if (remoteScreenSharerUid) {
                        applyViewerScreenShareLayout();
                    }
                }
                
                updateLayout();
            }
            if (mediaType === "audio") user.audioTrack.play();
        });

        client.on("user-left", user => {
            removeVideoBox(user.uid);
            
            // If the screen sharer left, clean up
            if (user.uid === remoteScreenSharerUid) {
                remoteScreenSharerUid = null;
                remotePresenterVideoUid = null;
                const screenBox = document.getElementById("screen-share-remote");
                if (screenBox) screenBox.remove();
                
                // Reset layout
                const container = document.getElementById("videoContainer");
                container.classList.remove("screen-sharing-active");
                updateLayout();
            } else {
                updateLayout();
            }
        });

        const uid = await client.join(AGORA_APP_ID, data.channelName, data.token, data.uid);
        [localTracks.audioTrack, localTracks.videoTrack] =
            await AgoraRTC.createMicrophoneAndCameraTracks();

        addVideoBox(localTracks.videoTrack, "You", uid, true);
        updateLayout();
        await client.publish([localTracks.audioTrack, localTracks.videoTrack]);

        setupControls();
    } catch (err) {
        console.error("Full error:", err);
        
        // Provide specific error messages
        let userMessage = "Failed to start call: " + err.message;
        
        if (err.message.includes("NOT_SUPPORTED")) {
            userMessage = "Camera/Microphone not supported. Please check:\n1. Browser supports WebRTC\n2. Using HTTPS (or localhost)\n3. Device has camera/microphone\n4. Permissions are granted";
        } else if (err.message.includes("PERMISSION_DENIED")) {
            userMessage = "Camera/Microphone permission denied. Please allow access in browser settings.";
        } else if (err.message.includes("getUserMedia")) {
            userMessage = "Your browser doesn't support camera/microphone access. Try Chrome, Firefox, or Edge.";
        }
        
        alert(userMessage);
    }
}

startMeeting();
</script>
</body>
</html>
