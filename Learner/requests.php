<?php
session_start();
require '../db.php';
require '../Agora/agora_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /LearnTogether/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare("SELECT first_name, last_name, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) die("User not found.");

// Fetch learner ID
$stmt = $pdo->prepare("SELECT id FROM learners WHERE user_id = ?");
$stmt->execute([$user_id]);
$learner = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$learner) die("Learner profile not found.");
$learner_id = $learner['id'];

// Fetch learner's session requests
$stmt = $pdo->prepare("
    SELECT 
        r.id AS reservation_id,
        r.subject,
        r.date AS session_date,
        r.time AS session_time,
        r.status,
        t.id AS tutor_id,
        u.first_name AS tutor_first_name,
        u.last_name AS tutor_last_name
    FROM reservations r
    JOIN tutors t ON r.tutor_id = t.id
    JOIN users u ON t.user_id = u.id
    WHERE r.learner_id = ?
    ORDER BY r.date DESC, r.time DESC
");
$stmt->execute([$learner_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>My Requests — LearnTogether</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../CSS/style2.css">
<link rel="stylesheet" href="../CSS/navbar.css">
</head>
<body>
<div class="app">
    <aside>
        <div class="sidebar">
            <div class="profile-dropdown" id="profileDropdown">
                <div class="avatar"><?= strtoupper($user['first_name'][0]) ?></div>
                <div>
                    <div style="font-weight:700"><?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?></div>
                    <div style="font-size:13px;color:var(--muted)">Active <?= htmlspecialchars(ucfirst($user['role'])) ?></div>
                </div>
            </div>

            <nav class="navlinks">
                <a href="learnerDashboard.php">🏠 Overview</a>
                <a href="subjects.php">📚 My Subjects</a>
                <a href="searchTutors.php">🔎 Find Tutors</a>
                <a href="schedule.php">📅 My Schedule</a>
                <a class="active" href="requests.php">✉️ Requests</a>
                <a href="../logout.php">🚪 Logout</a>
            </nav>
        </div>
    </aside>

    <div class="nav">
        <div class="logo">
            <div class="mark">LT</div>
            <div style="font-weight:700">LearnTogether</div>
        </div>
    </div>

    <main>
        <h1>My Session Requests</h1>

        <?php if (empty($requests)): ?>
            <p style="color:#666;">No requests yet.</p>
        <?php else: ?>
            <table style="width:100%;border-collapse:collapse;margin-top:20px;">
                <thead>
                    <tr style="background:#f4f4f4;text-align:left;">
                        <th style="padding:10px;">Tutor</th>
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
                            <td style="padding:10px;"><?= htmlspecialchars($req['tutor_first_name'].' '.$req['tutor_last_name']) ?></td>
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
                                    <button onclick="joinCall(<?= $req['reservation_id'] ?>)"
                                        style="padding:5px 10px;background:#4f46e5;color:white;border:none;border-radius:5px;">
                                        Join Call
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
    </main>
</div>

<script src="https://download.agora.io/sdk/release/AgoraRTC_N.js"></script>
<script>
async function joinCall(reservationId) {
    try {
        const response = await fetch(`../Agora/tutorStartCall.php?reservation_id=${reservationId}`);
        const data = await response.json();

        if (!data.token) return alert("Failed to get token.");

        const client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });
        await client.join("<?= $AGORA_APP_ID ?>", data.channelName, data.token, data.uid);

        const [micTrack, camTrack] = await AgoraRTC.createMicrophoneAndCameraTracks();
        const localDiv = document.createElement("div");
        localDiv.id = "local-player";
        localDiv.style = "width:400px;height:300px;border:2px solid green;margin-top:20px;";
        document.body.appendChild(localDiv);
        camTrack.play(localDiv);

        await client.publish([micTrack, camTrack]);

        client.on("user-published", async (user, mediaType) => {
            await client.subscribe(user, mediaType);
            if (mediaType === "video") {
                const remoteDiv = document.createElement("div");
                remoteDiv.id = `remote-player-${user.uid}`;
                remoteDiv.style = "width:400px;height:300px;border:2px solid orange;margin-top:20px;";
                document.body.appendChild(remoteDiv);
                user.videoTrack.play(remoteDiv);
            }
            if (mediaType === "audio") user.audioTrack.play();
        });

        alert("🎥 Joined call!");
    } catch (err) {
        console.error(err);
        alert("Failed to join call: " + err.message);
    }
}
</script>
</body>
</html>
