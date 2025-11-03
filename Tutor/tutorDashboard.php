<?php
session_start();
require '../db.php';
require '../agora_config.php';

$user_id = $_SESSION['user_id'];
if (!isset($_SESSION['user_id'])) {
    header("Location: /LearnTogether/login.php");
    exit;
}

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

if (!$tutor) {
    die("Tutor data not found for ID: " . htmlspecialchars($tutor_id));
}

$tutor_name = trim($tutor['first_name'] . ' ' . $tutor['last_name']);

$stmt = $pdo->prepare("
    SELECT subject, session_date, status
    FROM sessions
    WHERE tutor_id = ?
    ORDER BY session_date ASC
    LIMIT 5
");
$stmt->execute([$tutor_id]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_students = $pdo->prepare("SELECT COUNT(DISTINCT learner_id) FROM sessions WHERE tutor_id = ?");
$total_students->execute([$tutor_id]);
$total_students = $total_students->fetchColumn();

$confirmed_sessions = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE tutor_id = ? AND status = 'confirmed'");
$confirmed_sessions->execute([$tutor_id]);
$confirmed_sessions = $confirmed_sessions->fetchColumn();

$pending_requests = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE tutor_id = ? AND status = 'pending'");
$pending_requests->execute([$tutor_id]);
$pending_requests = $pending_requests->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tutor Dashboard - LearnTogether</title>
  <link rel="stylesheet" href="../CSS/style2.css">
  <link rel="stylesheet" href="../CSS/navbar.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body>
  <div class="app">
    <aside>
      <div class="sidebar">
        <div class="profile">
          <img src="<?= htmlspecialchars($tutor['profile_image'] ?? 'https://via.placeholder.com/52') ?>"
               class="avatar" alt="Tutor Image">
          <div>
            <h6><?= htmlspecialchars($tutor_name) ?></h6>
            <small class="text-muted">Tutor</small>
          </div>
        </div>

        <div class="navlinks">
          <a href="tutorDashboard.php" class="active"><i class="bi bi-house"></i> Home</a>
          <a href="subjectsTutor.php"><i class="bi bi-journal-bookmark"></i> Subjects</a>
          <a href="scheduleTutor.php"><i class="bi bi-calendar-check"></i> My Schedule</a>
          <a href="requestsTutor.php"><i class="bi bi-people"></i> Student Requests</a>
        </div>
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
      <h1>Welcome back, <?= htmlspecialchars($tutor['first_name']) ?> 👋</h1>

      <div class="subjects-grid">
        <div class="subject-card">
          <div class="subject-header">
            <div class="icon" style="background:#0f766e;">💬</div>
            <div class="subject-title">Messages</div>
          </div>
          <div class="subject-desc">Chat with your students in real time.</div>
          <button class="btn btn-outline-primary btn-sm" onclick="openChat()">Open Chat</button>
        </div>

        <div class="subject-card">
          <div class="subject-header">
            <div class="icon" style="background:#10b981;">🎥</div>
            <div class="subject-title">Start Call</div>
          </div>
          <div class="subject-desc">Launch a video call with a student.</div>
          <button class="btn btn-outline-success btn-sm" onclick="startCall()">Start Call</button>
        </div>
      </div>

      <h2 style="margin-top:40px;">Upcoming Sessions</h2>
      <ul>
        <?php foreach ($sessions as $s): ?>
          <li>
            <strong><?= htmlspecialchars($s['subject']) ?></strong> —
            <?= date("M d, h:i A", strtotime($s['session_date'])) ?>
            <span style="color:<?= $s['status']=='confirmed'?'green':($s['status']=='pending'?'orange':'gray') ?>;">
              <?= ucfirst($s['status']) ?>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>

      <div class="subjects-grid" style="margin-top:40px;">
        <div class="subject-card text-center">
          <h2><?= $total_students ?></h2>
          <p class="text-muted">Total Students</p>
        </div>
        <div class="subject-card text-center">
          <h2><?= $confirmed_sessions ?></h2>
          <p class="text-muted">Confirmed Sessions</p>
        </div>
        <div class="subject-card text-center">
          <h2><?= $pending_requests ?></h2>
          <p class="text-muted">Pending Requests</p>
        </div>
      </div>
    </main>
  </div>

  <script>
    const AGORA_APP_ID = "<?= $AGORA_APP_ID ?>";

    function openChat() {
      alert("Agora Chat integration placeholder — chat UI coming soon!");
    }

    async function startCall() {
      const response = await fetch('start_call.php');
      const { token, channelName } = await response.json();

      const client = AgoraRTC.createClient({ mode: "rtc", codec: "vp8" });
      await client.join(AGORA_APP_ID, channelName, token, null);

      const [audioTrack, videoTrack] = await AgoraRTC.createMicrophoneAndCameraTracks();
      const playerContainer = document.createElement("div");
      playerContainer.id = "local-player";
      document.body.append(playerContainer);
      videoTrack.play("local-player");
    }
  </script>

  <script src="https://download.agora.io/sdk/release/AgoraRTC_N.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/agora-chat@1.1.1/dist/agora-chat.min.js"></script>
</body>
</html>
