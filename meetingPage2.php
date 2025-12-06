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
        try {
            if (!localTracks.audioTrack) {
                console.error("Audio track is not available");
                return;
            }
            micEnabled = !micEnabled;
            await localTracks.audioTrack.setEnabled(micEnabled);
            micBtn.className = micEnabled ? "control-btn active" : "control-btn inactive";
            micBtn.innerText = micEnabled ? "🎤" : "🔇";
        } catch (err) {
            console.error("Error toggling microphone:", err);
            micEnabled = !micEnabled;
            alert("Failed to toggle microphone: " + err.message);
        }
    };

    camBtn.onclick = async () => {
        try {
            if (!localTracks.videoTrack) {
                console.error("Video track is not available");
                return;
            }
            camEnabled = !camEnabled;
            await localTracks.videoTrack.setEnabled(camEnabled);
            camBtn.className = camEnabled ? "control-btn active" : "control-btn inactive";
            camBtn.innerText = camEnabled ? "📷" : "📹";
        } catch (err) {
            console.error("Error toggling camera:", err);
            camEnabled = !camEnabled; 
            alert("Failed to toggle camera: " + err.message);
        }
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
        
        if (!isScreenSharing) {
            const localBox = document.querySelector(".video-box.local");
            if (localBox) {
                localBox.style.display = "block";
                localBox.style.position = "absolute";
                localBox.style.bottom = "20px";
                localBox.style.right = "20px";
                localBox.style.transform = "none";
                localBox.style.width = "18%";
                localBox.style.height = "auto";
                localBox.style.maxHeight = "28vh";
                localBox.style.border = "3px solid white";
                localBox.style.borderRadius = "12px";
                localBox.style.zIndex = "20";
                localBox.style.overflow = "hidden";
                localBox.style.aspectRatio = "16/9";
                localBox.style.minHeight = "120px";
                localBox.classList.add("pip-mode");
            }
            
            Object.values(remoteUsers).forEach(box => {
                if (!box.classList.contains('local')) {
                    box.style.position = "";
                    box.style.top = "";
                    box.style.bottom = "";
                    box.style.right = "";
                    box.style.width = "";
                    box.style.height = "";
                    box.style.zIndex = "";
                    box.style.border = "";
                    box.style.borderRadius = "";
                    box.style.overflow = "";
                    box.style.aspectRatio = "";
                    box.style.minHeight = "";
                    box.style.display = "block";
                    box.classList.remove("remote-pip-mode");
                }
            });
        }
    }
}function applyViewerScreenShareLayout() {
    const container = document.getElementById("videoContainer");
    container.classList.add("screen-sharing-active");
    
    const localBox = document.querySelector(".video-box.local");
    if (localBox) {
        localBox.style.display = "block";
        localBox.style.position = "absolute";
        localBox.style.top = "10px";
        localBox.style.right = "20px";
        localBox.style.transform = "none";
        localBox.style.width = "18%";
        localBox.style.height = "auto";
        localBox.style.maxHeight = "28vh";
        localBox.style.border = "3px solid white";
        localBox.style.borderRadius = "12px";
        localBox.style.zIndex = "20";
        localBox.style.overflow = "hidden";
        localBox.style.aspectRatio = "16/9";
        localBox.style.minHeight = "120px";
    }
    
    let cameraIndex = 0;
    Object.values(remoteUsers).forEach(box => {
        if (!box.classList.contains('local')) {
            box.style.display = "block";
            box.style.position = "absolute";
            box.style.top = "auto";
            box.style.bottom = (20 + cameraIndex * 160) + "px";
            box.style.right = "20px";
            box.style.transform = "none";
            box.style.width = "18%";
            box.style.height = "auto";
            box.style.maxHeight = "28vh";
            box.style.border = "3px solid white";
            box.style.borderRadius = "12px";
            box.style.zIndex = "19";
            box.style.overflow = "hidden";
            box.style.aspectRatio = "16/9";
            box.style.minHeight = "120px";
            cameraIndex++;
        }
    });
}

function applyScreenSharingLayout(screenOwnerUid = null, isRemoteScreen = false) {
    const container = document.getElementById("videoContainer");
    const localBox = document.querySelector(".video-box.local");
    
    container.classList.add("screen-sharing-active");
    
    if (isRemoteScreen) {

        const screenBox = document.getElementById(`user-${screenOwnerUid}`);
        if (screenBox) {

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
        

        if (localBox) {
            localBox.classList.add("pip-mode");
            localBox.style.display = "block";
            localBox.style.position = "absolute";
            localBox.style.top = "10px";
            localBox.style.bottom = "auto";
            localBox.style.right = "20px";
            localBox.style.transform = "none";
            localBox.style.width = "18%";
            localBox.style.height = "auto";
            localBox.style.maxHeight = "28vh";
            localBox.style.marginLeft = "0";
            localBox.style.border = "3px solid white";
            localBox.style.borderRadius = "12px";
            localBox.style.zIndex = "20";
            localBox.style.overflow = "hidden";
            localBox.style.aspectRatio = "16/9";
            localBox.style.minHeight = "120px";
        }
        

        let remoteCount = 0;
        Object.entries(remoteUsers).forEach(([uid, box]) => {
            if (uid != screenOwnerUid && !box.classList.contains('local')) {
                box.style.display = "block !important";
                box.classList.add("remote-pip-mode");
                box.style.position = "absolute";
                box.style.bottom = (20 + remoteCount * 160) + "px";
                box.style.top = "auto";
                box.style.right = "20px";
                box.style.transform = "none";
                box.style.width = "18%";
                box.style.height = "auto";
                box.style.maxHeight = "28vh";
                box.style.marginLeft = "0";
                box.style.border = "3px solid white";
                box.style.borderRadius = "12px";
                box.style.zIndex = "19";
                box.style.overflow = "hidden";
                box.style.aspectRatio = "16/9";
                box.style.minHeight = "120px";
                remoteCount++;
            }
        });
    } else {


        if (localBox) {
            localBox.style.display = "none";
        }
        

        let remoteCount = 0;
        Object.values(remoteUsers).forEach(box => {
            if (!box.classList.contains('local')) {
                box.style.display = "block !important";
                box.classList.add("remote-pip-mode");
                box.style.position = "absolute";
                box.style.bottom = (20 + remoteCount * 160) + "px";
                box.style.top = "auto";
                box.style.right = "20px";
                box.style.transform = "none";
                box.style.width = "18%";
                box.style.height = "auto";
                box.style.maxHeight = "28vh";
                box.style.marginLeft = "0";
                box.style.border = "3px solid white";
                box.style.borderRadius = "12px";
                box.style.zIndex = "19";
                box.style.overflow = "hidden";
                box.style.aspectRatio = "16/9";
                box.style.minHeight = "120px";
                remoteCount++;
            }
        });
    }
}

function resetLayout() {
    const container = document.getElementById("videoContainer");
    const localBox = document.querySelector(".video-box.local");
    
    container.classList.remove("screen-sharing-active");
    

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

            console.log("Starting screen share...");
            

            console.log("Creating screen track...");
            screenTrack = await AgoraRTC.createScreenVideoTrack({
                encoderConfig: "1080p_1",
                cursorControl: true
            });

            console.log("Screen track object:", screenTrack);
            console.log("Screen track type:", typeof screenTrack);

            const container = document.getElementById("videoContainer");
            
            // Collect all existing video boxes before reorganizing
            const allBoxes = container.querySelectorAll('.video-box');
            const detachedBoxes = [];
            allBoxes.forEach(box => {
                detachedBoxes.push(container.removeChild(box));
            });
            
            const screenBox = document.createElement("div");
            screenBox.id = "screen-share-box";
            screenBox.className = "screen-share-container";
            screenBox.style.position = "relative";
            screenBox.style.width = "65%";
            screenBox.style.height = "100%";
            screenBox.style.backgroundColor = "#000000";
            screenBox.style.display = "flex";
            screenBox.style.alignItems = "center";
            screenBox.style.justifyContent = "center";
            screenBox.style.overflow = "hidden";
            screenBox.style.zIndex = "5";
            screenBox.style.flexShrink = "0";
            container.appendChild(screenBox);
            console.log("Screen share container created");

            const videoPanelContainer = document.createElement("div");
            videoPanelContainer.id = "video-panel-right";
            videoPanelContainer.style.width = "35%";
            videoPanelContainer.style.height = "100%";
            videoPanelContainer.style.backgroundColor = "#000000";
            videoPanelContainer.style.display = "flex";
            videoPanelContainer.style.flexDirection = "column";
            videoPanelContainer.style.overflowY = "auto";
            videoPanelContainer.style.padding = "8px";
            videoPanelContainer.style.gap = "8px";
            videoPanelContainer.style.zIndex = "10";
            videoPanelContainer.style.flexShrink = "0";
            container.appendChild(videoPanelContainer);
            
            // Re-attach detached boxes to the video panel
            detachedBoxes.forEach(box => {
                box.className = box.classList.contains('local') ? 'video-box local remote-pip-mode' : 'video-box remote remote-pip-mode';
                videoPanelContainer.appendChild(box);
            });

            try {
                console.log("Playing screen track...");
                await screenTrack.play(screenBox);
                
                // Ensure video element has proper styling
                const videoElements = screenBox.querySelectorAll('video');
                videoElements.forEach(video => {
                    video.style.width = "100%";
                    video.style.height = "100%";
                    video.style.objectFit = "contain";
                    video.style.backgroundColor = "#000000";
                    video.style.display = "block";
                });
                
                console.log("✓ Screen track is now playing");
            } catch (e) {
                console.error("Error playing screen track:", e);
            }


            console.log("Publishing screen track...");
            try {
                await client.unpublish([localTracks.videoTrack]);
                console.log("✓ Camera unpublished");
                await client.publish(screenTrack);
                console.log("✓ Screen track published");
            } catch (e) {
                console.error("Error publishing screen track:", e);
                throw e;
            }
            
            container.classList.add("screen-sharing-active");
            shareBtn.classList.add("active");
            shareBtn.innerText = "🖥️ Stop";
            isScreenSharing = true;
            
            console.log("✅ Screen sharing started");
        } else {

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

            // Remove the right panel container
            const videoPanelRight = document.getElementById('video-panel-right');
            if (videoPanelRight) {
                const children = Array.from(videoPanelRight.children);
                children.forEach(child => {
                    container.appendChild(child);
                });
                videoPanelRight.remove();
                console.log("✓ Video panel removed");
            }

            console.log("✓ Camera video still active");
            

            Object.values(remoteUsers).forEach(box => {
                if (!box.classList.contains('local')) {
                    box.classList.remove("remote-pip-mode");
                    box.style.display = 'block';
                }
            });
            
            const localBox = document.querySelector(".video-box.local");
            if (localBox) {
                localBox.classList.remove("pip-mode");
                localBox.style.display = "block";
            }
            
            container.classList.remove("screen-sharing-active");
            
            console.log("Republishing camera track...");
            try {
                await client.publish([localTracks.videoTrack]);
                console.log("✓ Camera track republished");
            } catch (e) {
                console.error("Error republishing camera track:", e);
            }
            
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
        

        const screenBox = document.getElementById("screen-share");
        if (screenBox) screenBox.remove();
    }
}


async function startMeeting() {
    try {

        const constraints = { audio: true, video: true };
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            throw new Error("Your browser does not support camera/microphone access (getUserMedia not available)");
        }


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

                const isScreenTrack = user.videoTrack && (
                    user.videoTrack.source === 'screen' || 
                    user.videoTrack.trackMediaStreamTrack?.getSettings?.()?.displaySurface === 'monitor'
                );
                
                if (isScreenTrack) {

                    console.log("Remote screen track received from user", user.uid);
                    remoteScreenSharerUid = user.uid;
                    
                    const container = document.getElementById("videoContainer");
                    
                    // Collect all video boxes and their references
                    const allBoxes = container.querySelectorAll('.video-box');
                    const boxes = {
                        local: null,
                        remote: []
                    };
                    
                    allBoxes.forEach(box => {
                        if (box.classList.contains('local')) {
                            boxes.local = box;
                        } else {
                            boxes.remote.push(box);
                        }
                    });
                    
                    // Detach all existing boxes from DOM (but keep their content)
                    const detachedBoxes = [];
                    allBoxes.forEach(box => {
                        detachedBoxes.push(container.removeChild(box));
                    });
                    
                    container.classList.add("screen-sharing-active");
                    
                    // Create screen share box on the left (65%)
                    const screenBox = document.createElement("div");
                    screenBox.id = "screen-share-remote";
                    screenBox.className = "screen-share-container";
                    container.appendChild(screenBox);

                    // Create video panel on the right (35%)
                    const videoPanelContainer = document.createElement("div");
                    videoPanelContainer.id = "video-panel-right";
                    videoPanelContainer.style.width = "35%";
                    videoPanelContainer.style.height = "100%";
                    videoPanelContainer.style.backgroundColor = "#000000";
                    videoPanelContainer.style.display = "flex";
                    videoPanelContainer.style.flexDirection = "column";
                    videoPanelContainer.style.overflowY = "auto";
                    videoPanelContainer.style.padding = "8px";
                    videoPanelContainer.style.gap = "8px";
                    videoPanelContainer.style.zIndex = "10";
                    container.appendChild(videoPanelContainer);
                    
                    // Re-attach detached boxes to the video panel
                    detachedBoxes.forEach(box => {
                        box.className = box.classList.contains('local') ? 'video-box local remote-pip-mode' : 'video-box remote remote-pip-mode';
                        videoPanelContainer.appendChild(box);
                    });
                    
                    try {
                        await user.videoTrack.play(screenBox);
                        console.log("Remote screen track playing");
                    } catch (e) {
                        console.error("Error playing remote screen track:", e);
                    }
                } else {
                    const existingBox = document.getElementById(`user-${user.uid}`);
                    if (existingBox) {
                        try {
                            await user.videoTrack.play(existingBox);
                            console.log("Remote video track resumed for user", user.uid);
                        } catch (e) {
                            console.error("Error playing remote video track:", e);
                        }
                    } else {
                        addVideoBox(user.videoTrack, `User ${user.uid}`, user.uid);
                    }
                    

                    if (user.uid === remoteScreenSharerUid) {
                        remotePresenterVideoUid = user.uid;
                    }
                    

                    if (remoteScreenSharerUid) {
                        applyViewerScreenShareLayout();
                    }
                }
                
                updateLayout();
            }
            if (mediaType === "audio") user.audioTrack.play();
        });

        client.on("user-unpublished", async (user, mediaType) => {
            if (mediaType === "video") {
                console.log("Remote video unpublished for user", user.uid);
                
                // Check if this is the screen share that was unpublished
                if (user.uid === remoteScreenSharerUid) {
                    console.log("Remote screen share stopped by user", user.uid);
                    remoteScreenSharerUid = null;
                    remotePresenterVideoUid = null;
                    
                    const container = document.getElementById("videoContainer");
                    const screenBox = document.getElementById("screen-share-remote");
                    if (screenBox) {
                        screenBox.remove();
                        console.log("Remote screen box removed");
                    }
                    
                    // Remove the right panel and restore videos to container
                    const videoPanelRight = document.getElementById('video-panel-right');
                    if (videoPanelRight) {
                        const children = Array.from(videoPanelRight.children);
                        children.forEach(child => {
                            container.appendChild(child);
                        });
                        videoPanelRight.remove();
                        console.log("✓ Video panel removed");
                    }
                    
                    container.classList.remove("screen-sharing-active");
                    updateLayout();
                } else {
                    const videoBox = document.getElementById(`user-${user.uid}`);
                    if (videoBox) {
                        videoBox.innerHTML = '';
                        const label = document.createElement("div");
                        label.style.position = "absolute";
                        label.style.bottom = "5px";
                        label.style.left = "5px";
                        label.style.color = "white";
                        label.style.backgroundColor = "rgba(0,0,0,0.5)";
                        label.style.padding = "2px 5px";
                        label.style.borderRadius = "4px";
                        label.innerText = `User ${user.uid} (camera off)`;
                        videoBox.appendChild(label);
                        videoBox.style.backgroundColor = "#1a1a1a";
                    }
                    updateLayout();
                }
            }
            if (mediaType === "audio") {
                if (user.audioTrack) {
                    user.audioTrack.stop();
                }
            }
        });

        client.on("user-left", user => {
            removeVideoBox(user.uid);
            
            if (user.uid === remoteScreenSharerUid) {
                remoteScreenSharerUid = null;
                remotePresenterVideoUid = null;
                const screenBox = document.getElementById("screen-share-remote");
                if (screenBox) screenBox.remove();
                
                // Remove the right panel and restore videos to container
                const container = document.getElementById("videoContainer");
                const videoPanelRight = document.getElementById('video-panel-right');
                if (videoPanelRight) {
                    const children = Array.from(videoPanelRight.children);
                    children.forEach(child => {
                        container.appendChild(child);
                    });
                    videoPanelRight.remove();
                }

                container.classList.remove("screen-sharing-active");
                updateLayout();
            } else {
                updateLayout();
            }
        });

        const uid = await client.join(AGORA_APP_ID, data.channelName, data.token, data.uid);
        [localTracks.audioTrack, localTracks.videoTrack] =
            await AgoraRTC.createMicrophoneAndCameraTracks();

        // Add local video FIRST to ensure it appears at grid-row 1 (top)
        addVideoBox(localTracks.videoTrack, "You", uid, true);
        updateLayout();
        await client.publish([localTracks.audioTrack, localTracks.videoTrack]);

        setupControls();
    } catch (err) {
        console.error("Full error:", err);
        

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
