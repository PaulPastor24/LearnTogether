let client, localTracks = { audioTrack: null, videoTrack: null }, remoteUsers = {};

async function joinCall(reservationId, containerId = "videoContainer") {
    const videoContainer = document.getElementById(containerId);

    try {
        const res = await fetch(`../Agora/generate_token.php?reservation_id=${reservationId}`);
        const data = await res.json();
        if (!data.token) return alert("Failed to get token.");

        client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });
        const uid = await client.join(data.appId, data.channelName, data.token, data.uid);

        [localTracks.audioTrack, localTracks.videoTrack] = await AgoraRTC.createMicrophoneAndCameraTracks();

        addVideoBox(localTracks.videoTrack, "You", uid, containerId);
        await client.publish([localTracks.audioTrack, localTracks.videoTrack]);

        client.on("user-published", async (user, mediaType) => {
            await client.subscribe(user, mediaType);
            if (mediaType === "video") addVideoBox(user.videoTrack, `User ${user.uid}`, user.uid, containerId);
            if (mediaType === "audio") user.audioTrack.play();
        });

        client.on("user-unpublished", user => removeVideoBox(user.uid));

        setupControls(containerId);

    } catch (err) {
        console.error(err);
        alert("Failed to join call: " + err.message);
    }
}

function addVideoBox(track, name, uid, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const box = document.createElement("div");
    box.className = "video-box";
    box.id = `video-${uid}`;
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
    const box = document.getElementById(`video-${uid}`);
    if (box) box.remove();
    delete remoteUsers[uid];
}

function setupControls(containerId) {
    const controls = document.getElementById("callControls");
    controls.innerHTML = `
        <button onclick="toggleMic()">🎤 Mute/Unmute</button>
        <button onclick="toggleCam()">📷 Camera On/Off</button>
        <button onclick="leaveCall()">❌ Leave Call</button>
        <button onclick="shareScreen()">🖥️ Share Screen</button>
    `;
}

function toggleMic() { if (localTracks.audioTrack) localTracks.audioTrack.setEnabled(!localTracks.audioTrack.enabled); }
function toggleCam() { if (localTracks.videoTrack) localTracks.videoTrack.setEnabled(!localTracks.videoTrack.enabled); }

async function leaveCall() {
    for (let trackName in localTracks) {
        if (localTracks[trackName]) { localTracks[trackName].stop(); localTracks[trackName].close(); }
    }
    if (client) await client.leave();
    const container = document.getElementById("videoContainer");
    if (container) container.innerHTML = "";
    remoteUsers = {};
}

let screenTrack;
async function shareScreen() {
    if (!client) return;
    try {
        if (!screenTrack) {
            screenTrack = await AgoraRTC.createScreenVideoTrack({}, "auto");
            screenTrack.play("videoContainer");
            await client.publish([screenTrack]);
        } else {
            await screenTrack.close();
            await client.unpublish([screenTrack]);
            screenTrack = null;
        }
    } catch (err) { console.error("Screen share error:", err); }
}
