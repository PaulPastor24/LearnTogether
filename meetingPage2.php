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
$userRole = $_SESSION['role'] ?? null;
$userName = $_SESSION['first_name'] ?? 'User';
if (isset($_SESSION['last_name']) && $_SESSION['last_name']) {
    $userName .= ' ' . $_SESSION['last_name'];
}
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
const USER_ROLE = "<?= $userRole ?>";
const USER_NAME = "<?= htmlspecialchars($userName) ?>";
const IS_TUTOR = USER_ROLE === 'tutor';
const IS_LEARNER = USER_ROLE === 'learner';
</script>
</head>
<body>

<div id="videoContainer"></div>

<div class="controls" id="callControls"></div>

<div id="chatPanel" class="chat-panel">
    <div class="chat-header">
        <h3>Chat</h3>
        <button id="closeChatBtn" class="close-btn">✕</button>
    </div>
    <div id="chatMessages" class="chat-messages"></div>
    <div class="chat-input-area">
        <input type="text" id="chatInput" placeholder="Type a message..." class="chat-input">
        <button id="sendChatBtn" class="send-btn">Send</button>
    </div>
</div>

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
let myTracks = { audioTrack: null, videoTrack: null };
let participantVideos = {};
let micEnabled = true;
let camEnabled = true;
let screenTrack = null;
let isScreenSharing = false;
let screenSharerUid = null;
let screenSharerPresenterUid = null;

let currentUserUid = null;

let userInfo = {};

async function fetchUserInfo(uid) {
    if (userInfo[uid]) {
        return userInfo[uid];
    }
    
    try {
        const response = await fetch(`getUserInfo.php?uid=${uid}`);
        const data = await response.json();
        
        userInfo[uid] = {
            name: data.name || `User ${uid}`,
            role: data.role || 'participant'
        };
        
        return userInfo[uid];
    } catch (err) {
        console.error("Error fetching user info:", err);
        userInfo[uid] = {
            name: `User ${uid}`,
            role: 'participant'
        };
        return userInfo[uid];
    }
}

async function leaveCall() {
    for (let t of Object.values(myTracks)) if (t) { t.stop(); t.close(); }
    if (screenTrack) { await screenTrack.close(); screenTrack = null; }
    participantVideos = {};
    if (client) await client.leave();
    
    // Redirect based on user role
    if (IS_TUTOR) {
        window.location.href = '/LearnTogether/Tutor/tutorDashboard.php';
    } else if (IS_LEARNER) {
        window.location.href = '/LearnTogether/Learner/learnerDashboard.php';
    } else {
        window.location.href = document.referrer || '/LearnTogether/';
    }
}

let chatMessages = [];

function toggleChatPanel() {
    const chatPanel = document.getElementById("chatPanel");
    chatPanel.classList.toggle("open");
}

function addChatMessage(sender, message) {
    chatMessages.push({ sender, message, timestamp: new Date() });
    const chatMessagesDiv = document.getElementById("chatMessages");
    
    const messageEl = document.createElement("div");
    messageEl.className = "chat-message";
    const isOwnMessage = sender === "You";
    messageEl.style.backgroundColor = isOwnMessage ? "#e3f2fd" : "white";
    messageEl.style.borderLeftColor = isOwnMessage ? "#2196F3" : "#667eea";
    messageEl.innerHTML = `
        <strong>${sender}:</strong> ${escapeHtml(message)}
        <span class="chat-time">${new Date().toLocaleTimeString()}</span>
    `;
    chatMessagesDiv.appendChild(messageEl);
    chatMessagesDiv.scrollTop = chatMessagesDiv.scrollHeight;
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function sendChatMessage(message) {
    console.log("Message sent:", message);
    addChatMessage("You", message);
}

function setupChatListeners() {
    const chatInput = document.getElementById("chatInput");
    const sendBtn = document.getElementById("sendChatBtn");
    const closeBtn = document.getElementById("closeChatBtn");

    sendBtn.onclick = () => {
        const message = chatInput.value.trim();
        if (message) {
            sendChatMessage(message);
            chatInput.value = "";
        }
    };

    chatInput.onkeypress = (e) => {
        if (e.key === "Enter") {
            const message = chatInput.value.trim();
            if (message) {
                sendChatMessage(message);
                chatInput.value = "";
            }
        }
    };

    closeBtn.onclick = toggleChatPanel;

    const chatPanel = document.getElementById("chatPanel");
    const chatHeader = document.querySelector(".chat-header");
    let isDragging = false;
    let offsetX = 0;
    let offsetY = 0;

    chatHeader.style.cursor = "grab";
    chatHeader.onmousedown = (e) => {
        isDragging = true;
        const rect = chatPanel.getBoundingClientRect();
        offsetX = e.clientX - rect.left;
        offsetY = e.clientY - rect.top;
        chatHeader.style.cursor = "grabbing";
    };

    document.onmousemove = (e) => {
        if (isDragging) {
            chatPanel.style.right = "auto";
            chatPanel.style.left = (e.clientX - offsetX) + "px";
            chatPanel.style.top = (e.clientY - offsetY) + "px";
            chatPanel.style.transform = "none";
        }
    };

    document.onmouseup = () => {
        isDragging = false;
        chatHeader.style.cursor = "grab";
    };
}

function setupControls() {
    const controls = document.getElementById("callControls");
    controls.innerHTML = `
        <button id="toggleMic" class="control-btn active">🎤</button>
        <button id="toggleCam" class="control-btn active">📷</button>
        <button id="shareScreenBtn" class="control-btn">🖥️</button>
        <button id="chatBtn" class="control-btn">💬</button>
        <button id="leaveBtn" class="control-btn end-call">📞</button>
    `;

    const micBtn = document.getElementById("toggleMic");
    const camBtn = document.getElementById("toggleCam");
    const shareBtn = document.getElementById("shareScreenBtn");

    micBtn.title = "Mute microphone";
    camBtn.title = "Turn off camera";
    shareBtn.title = "Share screen";

    micBtn.onclick = async () => {
        try {
            if (!myTracks.audioTrack) {
                console.error("Audio track is not available");
                return;
            }
            micEnabled = !micEnabled;
            await myTracks.audioTrack.setEnabled(micEnabled);
            micBtn.className = micEnabled ? "control-btn active" : "control-btn inactive";
            micBtn.title = micEnabled ? "Mute microphone" : "Unmute microphone";
            micBtn.innerText = micEnabled ? "🎤" : "🔇";
        } catch (err) {
            console.error("Error toggling microphone:", err);
            micEnabled = !micEnabled;
            alert("Failed to toggle microphone: " + err.message);
        }
    };

    camBtn.onclick = async () => {
        try {
            if (!myTracks.videoTrack) {
                console.error("Video track is not available");
                return;
            }
            camEnabled = !camEnabled;
            await myTracks.videoTrack.setEnabled(camEnabled);
            camBtn.className = camEnabled ? "control-btn active" : "control-btn inactive";
            camBtn.title = camEnabled ? "Turn off camera" : "Turn on camera";
            const camImg = camBtn.querySelector("img");
            if (camImg) {
                camImg.src = camEnabled ? 
                    "https://cdn-icons-png.flaticon.com/512/3050/3050161.png" : 
                    "https://cdn-icons-png.flaticon.com/512/3050/3050163.png";
                camImg.alt = camEnabled ? "Camera On" : "Camera Off";
            }
        } catch (err) {
            console.error("Error toggling camera:", err);
            camEnabled = !camEnabled; 
            alert("Failed to toggle camera: " + err.message);
        }
    };

    shareBtn.onclick = toggleScreenShare;

    const chatBtn = document.getElementById("chatBtn");
    chatBtn.onclick = toggleChatPanel;

    document.getElementById("leaveBtn").onclick = leaveCall;
}

function updateLayout() {
    const container = document.getElementById("videoContainer");
    const participantCount = Object.keys(participantVideos).length;

    if (participantCount === 0) {
        container.classList.add("alone");
    } else {
        container.classList.remove("alone");
        
        if (!isScreenSharing) {
            const myVideoBox = document.querySelector(".video-box.my-video");
            if (myVideoBox) {
                myVideoBox.style.display = "block";
                myVideoBox.style.position = "absolute";
                myVideoBox.style.bottom = "20px";
                myVideoBox.style.right = "20px";
                myVideoBox.style.transform = "none";
                myVideoBox.style.width = "18%";
                myVideoBox.style.height = "auto";
                myVideoBox.style.maxHeight = "28vh";
                myVideoBox.style.border = "3px solid white";
                myVideoBox.style.borderRadius = "12px";
                myVideoBox.style.zIndex = "20";
                myVideoBox.style.overflow = "hidden";
                myVideoBox.style.aspectRatio = "16/9";
                myVideoBox.style.minHeight = "120px";
                myVideoBox.classList.add("pip-mode");
            }
            
            Object.values(participantVideos).forEach(box => {
                if (!box.classList.contains('my-video')) {
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
                    box.classList.remove("participant-pip-mode");
                }
            });
        }
    }
}function applyViewerScreenShareLayout() {
    const container = document.getElementById("videoContainer");
    const videoPanelRight = document.getElementById("video-panel-right");
    
    container.classList.add("screen-sharing-active");
    
    if (!videoPanelRight) {
        const myVideoBox = document.querySelector(".video-box.my-video");
        if (myVideoBox) {
            myVideoBox.style.display = "block";
            myVideoBox.style.position = "absolute";
            myVideoBox.style.top = "10px";
            myVideoBox.style.right = "20px";
            myVideoBox.style.transform = "none";
            myVideoBox.style.width = "18%";
            myVideoBox.style.height = "auto";
            myVideoBox.style.maxHeight = "28vh";
            myVideoBox.style.border = "3px solid white";
            myVideoBox.style.borderRadius = "12px";
            myVideoBox.style.zIndex = "20";
            myVideoBox.style.overflow = "hidden";
            myVideoBox.style.aspectRatio = "16/9";
            myVideoBox.style.minHeight = "120px";
        }
        
        let cameraIndex = 0;
        Object.values(participantVideos).forEach(box => {
            if (!box.classList.contains('my-video')) {
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
    } else {
        const myVideoBox = document.querySelector(".video-box.my-video");
        if (myVideoBox) {
            myVideoBox.style.position = "relative";
            myVideoBox.style.width = "100%";
            myVideoBox.style.height = "200px";
            myVideoBox.style.maxHeight = "200px";
            myVideoBox.style.border = "1px solid rgba(255, 255, 255, 0.1)";
            myVideoBox.style.borderRadius = "8px";
            myVideoBox.style.overflow = "hidden";
            myVideoBox.style.margin = "0";
            myVideoBox.style.padding = "0";
            myVideoBox.style.display = "block";
            myVideoBox.classList.add("participant-pip-mode");
        }
        
        Object.values(participantVideos).forEach(box => {
            if (!box.classList.contains('my-video')) {
                box.style.position = "relative";
                box.style.width = "100%";
                box.style.height = "200px";
                box.style.maxHeight = "200px";
                box.style.border = "1px solid rgba(255, 255, 255, 0.1)";
                box.style.borderRadius = "8px";
                box.style.overflow = "hidden";
                box.style.margin = "0";
                box.style.padding = "0";
                box.style.display = "block";
                box.classList.add("participant-pip-mode");
            }
        });
    }
}

function applyRoleBasedScreenShareLayout() {
    const container = document.getElementById("videoContainer");
    const videoPanelRight = document.getElementById("video-panel-right");
    
    if (!videoPanelRight) return;
    
    console.log(`Current role: ${USER_ROLE}, IS_TUTOR: ${IS_TUTOR}, IS_LEARNER: ${IS_LEARNER}`);
    
    const myVideoBox = document.querySelector(".video-box.my-video");
    const participantBoxes = Array.from(participantVideos.values()).filter(box => !box.classList.contains('my-video'));
    
    videoPanelRight.innerHTML = '';
    
    if (IS_TUTOR) {
        console.log("Layout: TUTOR at top");
        if (myVideoBox) {
            myVideoBox.style.order = "1";
            videoPanelRight.appendChild(myVideoBox);
        }
        participantBoxes.forEach((box, index) => {
            box.style.order = (index + 2).toString();
            videoPanelRight.appendChild(box);
        });
    } else if (IS_LEARNER) {
        console.log("Layout: LEARNER at top");
        if (myVideoBox) {
            myVideoBox.style.order = "1";
            videoPanelRight.appendChild(myVideoBox);
        }
        participantBoxes.forEach((box, index) => {
            box.style.order = (index + 2).toString();
            videoPanelRight.appendChild(box);
        });
    }
    
    videoPanelRight.style.display = "flex";
    videoPanelRight.style.flexDirection = "column";
}

function applyScreenSharingLayout(screenOwnerUid = null, isRemoteScreen = false) {
    const container = document.getElementById("videoContainer");
    const myVideoBox = document.querySelector(".video-box.my-video");
    
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
        

        if (myVideoBox) {
            myVideoBox.classList.add("pip-mode");
            myVideoBox.style.display = "block";
            myVideoBox.style.position = "absolute";
            myVideoBox.style.top = "10px";
            myVideoBox.style.bottom = "auto";
            myVideoBox.style.right = "20px";
            myVideoBox.style.transform = "none";
            myVideoBox.style.width = "18%";
            myVideoBox.style.height = "auto";
            myVideoBox.style.maxHeight = "28vh";
            myVideoBox.style.marginLeft = "0";
            myVideoBox.style.border = "3px solid white";
            myVideoBox.style.borderRadius = "12px";
            myVideoBox.style.zIndex = "20";
            myVideoBox.style.overflow = "hidden";
            myVideoBox.style.aspectRatio = "16/9";
            myVideoBox.style.minHeight = "120px";
        }
        

        let remoteCount = 0;
        Object.entries(participantVideos).forEach(([uid, box]) => {
            if (uid != screenOwnerUid && !box.classList.contains('my-video')) {
                box.style.display = "block !important";
                box.classList.add("participant-pip-mode");
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


        if (myVideoBox) {
            myVideoBox.style.display = "none";
        }
        

        let remoteCount = 0;
        Object.values(participantVideos).forEach(box => {
            if (!box.classList.contains('my-video')) {
                box.style.display = "block !important";
                box.classList.add("participant-pip-mode");
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
    const myVideoBox = document.querySelector(".video-box.my-video");
    
    container.classList.remove("screen-sharing-active");
    

    if (myVideoBox) {
        myVideoBox.classList.remove("pip-mode");
        myVideoBox.style.display = "";
        myVideoBox.style.position = "";
        myVideoBox.style.bottom = "";
        myVideoBox.style.right = "";
        myVideoBox.style.top = "";
        myVideoBox.style.transform = "";
        myVideoBox.style.width = "";
        myVideoBox.style.height = "";
        myVideoBox.style.maxHeight = "";
        myVideoBox.style.marginLeft = "";
        myVideoBox.style.border = "";
        myVideoBox.style.borderRadius = "";
        myVideoBox.style.zIndex = "";
        myVideoBox.style.overflow = "";
        myVideoBox.style.aspectRatio = "";
        myVideoBox.style.minHeight = "";
    }
    

    Object.values(participantVideos).forEach(box => {
        if (!box.classList.contains('my-video')) {
            box.classList.remove("participant-pip-mode");
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

function addVideoBox(track, name, uid, isMyVideo = false) {
    const container = document.getElementById("videoContainer");
    if (!container) {
        console.error("❌ Container not found!");
        return;
    }

    if (isMyVideo) {
        userInfo[uid] = { name: USER_NAME, role: USER_ROLE };
    } else {
        userInfo[uid] = { name: name, role: 'participant' };
    }

    const box = document.createElement("div");
    box.id = `user-${uid}`;
    box.className = isMyVideo ? "video-box my-video" : "video-box participant-video";
    
    console.log(`📺 Adding ${isMyVideo ? "MY" : "PARTICIPANT"} video box, uid: ${uid}, class: ${box.className}`);

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
    
    if (container.classList.contains("screen-sharing-active") && !isMyVideo) {
        const videoPanelRight = document.getElementById("video-panel-right");
        if (videoPanelRight) {
            box.classList.add("participant-pip-mode");
            videoPanelRight.appendChild(box);
            console.log("✓ Added to video panel");
        } else {
            container.appendChild(box);
            console.log("✓ Added to container (no panel yet)");
        }
    } else {
        container.appendChild(box);
        console.log("✓ Added to main container");
    }
    
    track.play(box);
    participantVideos[uid] = box;
    console.log("✓ Track playing, stored in participantVideos");
}

function removeVideoBox(uid) {
    const box = document.getElementById(`user-${uid}`);
    if (box) box.remove();
    delete participantVideos[uid];
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
            videoPanelContainer.style.boxSizing = "border-box";
            container.appendChild(videoPanelContainer);
            
            detachedBoxes.forEach(box => {
                const isMyVideo = box.classList.contains('my-video');
                box.className = isMyVideo ? 'video-box my-video participant-pip-mode' : 'video-box participant-video participant-pip-mode';
                
                box.style.position = "relative";
                box.style.width = "100%";
                box.style.height = "200px";
                box.style.maxHeight = "200px";
                box.style.border = "1px solid rgba(255, 255, 255, 0.1)";
                box.style.borderRadius = "8px";
                box.style.overflow = "hidden";
                box.style.margin = "0";
                box.style.padding = "0";
                box.style.display = "block";
                box.style.boxSizing = "border-box";
                
                videoPanelContainer.appendChild(box);
            });

            try {
                console.log("Playing screen track...");
                await screenTrack.play(screenBox);
                
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


            try {
                console.log("Publishing screen track...");
                try {
                    await client.unpublish([myTracks.videoTrack]);
                    console.log("✓ Camera unpublished");
                    
                    await client.publish(screenTrack);
                    console.log("✓ Screen track published");
                } catch (e) {
                    console.error("Error publishing screen track:", e);
                    throw e;
                }
                
                container.classList.add("screen-sharing-active");
                shareBtn.classList.add("active");
                shareBtn.title = "Stop sharing screen";
                isScreenSharing = true;
                
                applyRoleBasedScreenShareLayout();
                
                console.log("✅ Screen sharing started");
            } catch (e) {
                console.error("Error in screen share publishing:", e);
            }
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

            const videoPanelRight = document.getElementById('video-panel-right');
            if (videoPanelRight) {
                const children = Array.from(videoPanelRight.children);
                children.forEach(child => {
                    child.className = child.classList.contains('my-video') ? 'video-box my-video' : 'video-box participant-video';
                    child.style.position = "";
                    child.style.width = "";
                    child.style.height = "";
                    child.style.border = "";
                    child.style.borderRadius = "";
                    child.style.overflow = "";
                    child.style.margin = "";
                    child.style.padding = "";
                    child.style.maxHeight = "";
                    child.style.display = "block";
                    container.appendChild(child);
                    const uid = child.id.replace('user-', '');
                    if (participantVideos[uid]) {
                        console.log(`✓ Restored video for uid: ${uid}`);
                    }
                });
                videoPanelRight.remove();
                console.log("✓ Video panel removed");
            }

            console.log("✓ Camera video still active");
            

            Object.values(participantVideos).forEach(box => {
                if (!box.classList.contains('my-video')) {
                    box.classList.remove("participant-pip-mode");
                    box.style.display = 'block';
                }
            });
            
            const myVideoBox = document.querySelector(".video-box.my-video");
            if (myVideoBox) {
                myVideoBox.classList.remove("pip-mode");
                myVideoBox.style.display = "block";
            }
            
            container.classList.remove("screen-sharing-active");
            
            console.log("Republishing camera track...");
            try {
                await client.publish([myTracks.videoTrack]);
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
                    screenSharerUid = user.uid;
                    
                    const container = document.getElementById("videoContainer");
                    
                    const allBoxes = container.querySelectorAll('.video-box');
                    const boxes = {
                        myVideo: null,
                        participants: []
                    };
                    
                    allBoxes.forEach(box => {
                        if (box.classList.contains('my-video')) {
                            boxes.myVideo = box;
                        } else {
                            boxes.participants.push(box);
                        }
                    });
                    
                    const detachedBoxes = [];
                    allBoxes.forEach(box => {
                        detachedBoxes.push(container.removeChild(box));
                    });
                    
                    container.classList.add("screen-sharing-active");
                    
                    const screenBox = document.createElement("div");
                    screenBox.id = "screen-share-remote";
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
                    screenBox.style.minWidth = "0";
                    container.appendChild(screenBox);

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
                    videoPanelContainer.style.boxSizing = "border-box";
                    videoPanelContainer.style.position = "relative";
                    container.appendChild(videoPanelContainer);
                    
                    detachedBoxes.forEach(box => {
                        const isMyVideo = box.classList.contains('my-video');
                        box.className = isMyVideo ? 'video-box my-video participant-pip-mode' : 'video-box participant-video participant-pip-mode';
                        
                        box.style.position = "relative";
                        box.style.width = "100%";
                        box.style.height = "200px";
                        box.style.maxHeight = "200px";
                        box.style.border = "1px solid rgba(255, 255, 255, 0.1)";
                        box.style.borderRadius = "8px";
                        box.style.overflow = "hidden";
                        box.style.margin = "0";
                        box.style.padding = "0";
                        box.style.display = "block";
                        box.style.boxSizing = "border-box";
                        
                        videoPanelContainer.appendChild(box);
                    });
                    
                    try {
                        await user.videoTrack.play(screenBox);
                        
                        const videoElements = screenBox.querySelectorAll('video');
                        videoElements.forEach(video => {
                            video.style.width = "100%";
                            video.style.height = "100%";
                            video.style.objectFit = "contain";
                            video.style.backgroundColor = "#000000";
                            video.style.display = "block";
                        });
                        
                        console.log("Remote screen track playing");
                        
                        applyRoleBasedScreenShareLayout();
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
                    

                    if (user.uid === screenSharerUid) {
                        screenSharerPresenterUid = user.uid;
                    }
                    

                    if (screenSharerUid) {
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
                
                if (user.uid === screenSharerUid) {
                    console.log("Remote screen share stopped by user", user.uid);
                    screenSharerUid = null;
                    screenSharerPresenterUid = null;
                    
                    const container = document.getElementById("videoContainer");
                    const screenBox = document.getElementById("screen-share-remote");
                    if (screenBox) {
                        screenBox.remove();
                        console.log("Remote screen box removed");
                    }
                    
                    const videoPanelRight = document.getElementById('video-panel-right');
                    if (videoPanelRight) {
                        const children = Array.from(videoPanelRight.children);
                        children.forEach(child => {
                            child.className = child.classList.contains('my-video') ? 'video-box my-video' : 'video-box participant-video';
                            child.style.position = "";
                            child.style.width = "";
                            child.style.height = "";
                            child.style.border = "";
                            child.style.borderRadius = "";
                            child.style.overflow = "";
                            child.style.margin = "";
                            child.style.padding = "";
                            child.style.maxHeight = "";
                            child.style.display = "block";
                            container.appendChild(child);
                            const uid = child.id.replace('user-', '');
                            participantVideos[uid] = child;
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
                        label.style.padding = "4px 8px";
                        label.style.borderRadius = "4px";
                        label.style.fontSize = "12px";
                        label.style.fontWeight = "500";
                        label.style.whiteSpace = "nowrap";
                        
                        (async () => {
                            const info = await fetchUserInfo(user.uid);
                            label.innerText = `${info.name} (${info.role})`;
                        })();
                        
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
            
            if (user.uid === screenSharerUid) {
                screenSharerUid = null;
                screenSharerPresenterUid = null;
                const screenBox = document.getElementById("screen-share-remote");
                if (screenBox) screenBox.remove();
                
                const container = document.getElementById("videoContainer");
                const videoPanelRight = document.getElementById('video-panel-right');
                if (videoPanelRight) {
                    const children = Array.from(videoPanelRight.children);
                    children.forEach(child => {
                        child.className = child.classList.contains('my-video') ? 'video-box my-video' : 'video-box participant-video';
                        child.style.position = "";
                        child.style.width = "";
                        child.style.height = "";
                        child.style.border = "";
                        child.style.borderRadius = "";
                        child.style.overflow = "";
                        child.style.margin = "";
                        child.style.padding = "";
                        child.style.maxHeight = "";
                        child.style.display = "block";
                        container.appendChild(child);
                        const uid = child.id.replace('user-', '');
                        participantVideos[uid] = child;
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
        currentUserUid = uid;
        
        [myTracks.audioTrack, myTracks.videoTrack] =
            await AgoraRTC.createMicrophoneAndCameraTracks();

        addVideoBox(myTracks.videoTrack, "You", uid, true);
        console.log("✓ My video added, uid:", uid);
        console.log("✓ participantVideos:", participantVideos);
        updateLayout();
        await client.publish([myTracks.audioTrack, myTracks.videoTrack]);
        console.log("✓ Tracks published");

        setupControls();
        setupChatListeners();
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
