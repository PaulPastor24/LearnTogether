<?php
session_start();
require '../db.php';
require '../Agora/agora_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /LearnTogether/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id AS tutor_id FROM tutors WHERE user_id = ?");
$stmt->execute([$user_id]);
$tutor_row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tutor_row) {
    header("Location: ../roleSelector.php");
    exit;
}

$tutor_id = $tutor_row['tutor_id'];

$stmt = $pdo->prepare("
    SELECT u.first_name, u.last_name, t.profile_image
    FROM tutors t
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ?
");
$stmt->execute([$tutor_id]);
$tutor = $stmt->fetch(PDO::FETCH_ASSOC);

if (isset($_POST['action'], $_POST['session_id'])) {
    $session_id = $_POST['session_id'];
    if ($_POST['action'] === 'approve') {
        $pdo->prepare("UPDATE sessions SET status='confirmed' WHERE id=? AND tutor_id=?")
            ->execute([$session_id, $tutor_id]);
    } elseif ($_POST['action'] === 'reject') {
        $pdo->prepare("UPDATE sessions SET status='rejected' WHERE id=? AND tutor_id=?")
            ->execute([$session_id, $tutor_id]);
    }
    header("Location: requests.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT s.id, s.subject, s.session_date, s.status, 
           u.first_name AS learner_first_name, u.last_name AS learner_last_name
    FROM sessions s
    JOIN users u ON s.learner_id = u.id
    WHERE s.tutor_id = ?
    ORDER BY s.session_date DESC
");
$stmt->execute([$tutor_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Session Requests - LearnTogether</title>
<link rel="stylesheet" href="../CSS/style2.css">
<link rel="stylesheet" href="../CSS/navbar.css">
</head>
<body>
<div class="app">
  <aside>
    <div class="sidebar">
      <div class="profile">
        <img src="<?= htmlspecialchars($tutor['profile_image'] ?? 'https://via.placeholder.com/52') ?>" class="avatar" alt="Tutor Image">
        <div>
          <h6><?= htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']) ?></h6>
          <small class="text-muted">Tutor</small>
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
      <div class="logo">
        <div class="mark">LT</div>
        <div>LearnTogether</div>
      </div>
      <div class="search">
        <input type="text" placeholder="Search students, subjects..." />
      </div>
      <div class="nav-actions">
        <button class="icon-btn">🔔</button>
        <button class="icon-btn">💬</button>
        <div style="display:flex;align-items:center;gap:8px;">
          <div class="profile-info">
            <div><?= htmlspecialchars($tutor['first_name']) ?></div>
            <div>Tutor</div>
          </div>
          <div class="avatar">
            <?= strtoupper($tutor['first_name'][0] . $tutor['last_name'][0]) ?>
          </div>
        </div>
      </div>
  </div>

  <main>
    <h1>Session Requests</h1>

    <?php if (empty($requests)): ?>
      <p style="color:#666;margin-top:20px;">No session requests yet.</p>
    <?php else: ?>
      <table style="width:100%;border-collapse:collapse;margin-top:20px;">
        <thead>
          <tr style="background:#f4f4f4;text-align:left;">
            <th style="padding:10px;">Learner</th>
            <th style="padding:10px;">Subject</th>
            <th style="padding:10px;">Date</th>
            <th style="padding:10px;">Status</th>
            <th style="padding:10px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($requests as $req): ?>
            <tr style="border-bottom:1px solid #eee;">
              <td style="padding:10px;"><?= htmlspecialchars($req['learner_first_name'] . ' ' . $req['learner_last_name']) ?></td>
              <td style="padding:10px;"><?= htmlspecialchars($req['subject']) ?></td>
              <td style="padding:10px;"><?= date("M d, h:i A", strtotime($req['session_date'])) ?></td>
              <td style="padding:10px;">
                <?php
                  $statusColor = match (strtolower($req['status'])) {
                    'pending' => '#f59e0b',
                    'confirmed' => '#10b981',
                    'rejected' => '#ef4444',
                    default => '#6b7280'
                  };
                ?>
                <span style="color:<?= $statusColor ?>;font-weight:600;">
                  <?= ucfirst($req['status']) ?>
                </span>
              </td>
              <td style="padding:10px;">
                <?php if (strtolower($req['status']) === 'pending'): ?>
                  <form method="post" style="display:inline;">
                    <input type="hidden" name="session_id" value="<?= $req['id'] ?>">
                    <button name="action" value="approve" style="padding:5px 10px;background:#10b981;color:white;border:none;border-radius:5px;">Approve</button>
                    <button name="action" value="reject" style="padding:5px 10px;background:#ef4444;color:white;border:none;border-radius:5px;">Reject</button>
                  </form>
                <?php elseif (strtolower($req['status']) === 'confirmed'): ?>
                  <button onclick="startCall(<?= $req['id'] ?>)" style="padding:5px 10px;background:#4f46e5;color:white;border:none;border-radius:5px;">Start Call</button>
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
async function startCall(sessionId) {
  try {
    const response = await fetch(`../Agora/tutorStartCall.php?reservation_id=${sessionId}`);
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

    alert("🎥 Video call started!");
  } catch (err) {
    console.error(err);
    alert("Failed to start call: " + err.message);
  }
}
</script>
</body>
</html>
