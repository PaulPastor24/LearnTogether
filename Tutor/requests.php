<?php
session_start();
require '../db.php';

  if (!isset($_SESSION['user_id'])) {
      header("Location: /LearnTogether/login.php");
      exit;
  }

  $user_id = $_SESSION['user_id'];

  $stmt = $pdo->prepare("
      SELECT t.id AS tutor_id, u.first_name, u.last_name
      FROM tutors t
      JOIN users u ON t.user_id = u.id
      WHERE u.id = ?
  ");

  $stmt->execute([$user_id]);
  $tutor = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$tutor) die("Tutor profile not found.");
  $tutor_id = $tutor['tutor_id'];

  $stmt = $pdo->prepare("
      SELECT 
          r.id AS reservation_id,
          r.subject,
          r.date AS session_date,
          r.time AS session_time,
          r.status,
          l.id AS learner_id,
          u.first_name AS learner_first_name,
          u.last_name AS learner_last_name
      FROM reservations r
      JOIN learners l ON r.learner_id = l.id
      JOIN users u ON l.user_id = u.id
      WHERE r.tutor_id = ?
      ORDER BY r.date DESC, r.time DESC
  ");
  $stmt->execute([$tutor_id]);
  $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tutor Requests — LearnTogether</title>
<link rel="stylesheet" href="../CSS/style2.css">
<link rel="stylesheet" href="../CSS/navbar.css">
<script>
const AGORA_APP_ID = "ba85d26a0db94dec82214e061ceaa39c";
</script>
</head>
<body>
<div class="app">
    <aside>
        <div class="sidebar">
            <div class="profile-dropdown">
                <div class="avatar"><?= strtoupper($tutor['first_name'][0]) ?></div>
                <div>
                    <div style="font-weight:700"><?= htmlspecialchars($tutor['first_name'].' '.$tutor['last_name']) ?></div>
                    <div style="font-size:13px;color:var(--muted)">Active Tutor</div>
                </div>
            </div>
            <nav class="navlinks">
                <a href="tutorDashboard.php">🏠 Overview</a>
                <a href="scheduleTutor.php">📅 My Schedule</a>
                <a class="active" href="requests.php">✉️ Requests</a>
                <a href="../logout.php">🚪 Logout</a>
            </nav>
        </div>
    </aside>

    <div class="nav">
        <div class="logo"><div class="mark">LT</div><div style="font-weight:700">LearnTogether</div></div>
    </div>

    <main>
        <h1>Session Requests</h1>

        <?php if (empty($requests)): ?>
            <p style="color:#666;">No session requests yet.</p>
        <?php else: ?>
            <table style="width:100%;border-collapse:collapse;margin-top:20px;">
                <thead>
                    <tr style="background:#f4f4f4;text-align:left;">
                        <th style="padding:10px;">Learner</th>
                        <th style="padding:10px;">Subject</th>
                        <th style="padding:10px;">Date</th>
                        <th style="padding:10px;">Time</th>
                        <th style="padding:10px;">Status</th>
                        <th style="padding:10px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req): ?>
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:10px;"><?= htmlspecialchars($req['learner_first_name'].' '.$req['learner_last_name']) ?></td>
                            <td style="padding:10px;"><?= htmlspecialchars($req['subject']) ?></td>
                            <td style="padding:10px;"><?= htmlspecialchars($req['session_date']) ?></td>
                            <td style="padding:10px;"><?= htmlspecialchars($req['session_time']) ?></td>
                            <td style="padding:10px;">
                                <?php
                                $statusColor = match($req['status']) {
                                    'Pending' => '#f59e0b',
                                    'Confirmed' => '#10b981',
                                    'Rejected' => '#ef4444',
                                    default => '#6b7280'
                                };
                                ?>
                                <span style="color:<?= $statusColor ?>;font-weight:600;">
                                    <?= htmlspecialchars($req['status']) ?>
                                </span>
                            </td>
                            <td style="padding:10px;">
                                <?php if ($req['status'] === 'Confirmed'): ?>
                                    <button onclick="window.location.href='../meetingPage.php?reservation_id=<?= $req['reservation_id'] ?>'"
                                        style="padding:5px 10px;background:#4f46e5;color:white;border:none;border-radius:5px;">
                                        Start Call
                                    </button>
                                <?php else: ?>
                                    <span style="color:#999;">N/A</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <div id="videoContainer"></div>
        <div id="callControls"></div>
    </main>
</div>

<script src="https://download.agora.io/sdk/release/AgoraRTC_N.js"></script>
<script>
let client, micTrack, camTrack;
const videoContainer = document.getElementById("videoContainer");

async function startCall(reservationId) {
    try {
        const response = await fetch(`../Agora/generate_token.php?reservation_id=${reservationId}`);
        const data = await response.json();
        console.log(data);
        if (!data.token) return alert("Failed to get token.");

        client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });
        await client.join(AGORA_APP_ID, data.channelName, data.token, data.uid);

        [micTrack, camTrack] = await AgoraRTC.createMicrophoneAndCameraTracks();

        addVideoBox(camTrack, "You");
        await client.publish([micTrack, camTrack]);

        client.on("user-published", async (user, mediaType) => {
            await client.subscribe(user, mediaType);
            if (mediaType === "video") addVideoBox(user.videoTrack, `User ${user.uid}`, user.uid);
            if (mediaType === "audio") user.audioTrack.play();
        });

        client.on("user-unpublished", user => removeVideoBox(user.uid));

        createControls();

    } catch (err) {
        console.error(err);
        alert("Failed to start call: " + err.message);
    }
}

function addVideoBox(track, name, uid = "local") {
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
    videoContainer.appendChild(box);
    track.play(box);
}

function removeVideoBox(uid) {
    const box = document.getElementById(`video-${uid}`);
    if (box) box.remove();
}

function createControls() {
    const controls = document.getElementById("callControls");
    controls.innerHTML = `
        <button onclick="toggleMic()">🎤 Mute/Unmute</button>
        <button onclick="toggleCam()">📷 Camera On/Off</button>
        <button onclick="leaveCall()">❌ Leave Call</button>
    `;
}

function toggleMic() { if (micTrack) micTrack.setEnabled(!micTrack.enabled); }
function toggleCam() { if (camTrack) camTrack.setEnabled(!camTrack.enabled); }
async function leaveCall() {
    if (micTrack) micTrack.close();
    if (camTrack) camTrack.close();
    if (client) await client.leave();
    videoContainer.innerHTML = "";
    alert("You left the call.");
}
</script>
</body>
</html>
