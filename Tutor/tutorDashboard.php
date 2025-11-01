<?php
session_start();
require '../db.php';
require '../agora_config.php';

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

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

  <script src="https://download.agora.io/sdk/release/AgoraRTC_N.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/agora-chat@1.1.1/dist/agora-chat.min.js"></script>

  <style>
    body {
      background-color: #f8fafc;
      font-family: "Inter", "Segoe UI", sans-serif;
    }

    .sidebar {
      min-height: 100vh;
      background: linear-gradient(180deg, #1e3a8a, #2563eb);
      color: #fff;
      padding-top: 1.5rem;
    }

    .sidebar a {
      color: #cbd5e1;
      display: block;
      padding: 0.75rem 1rem;
      border-radius: 10px;
      text-decoration: none;
    }

    .sidebar a:hover,
    .sidebar a.active {
      background: rgba(255, 255, 255, 0.15);
      color: #fff;
    }

    .card-custom {
      border: none;
      border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }
  </style>
</head>

<body>
  <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4 sticky-top">
    <a class="navbar-brand fw-bold text-primary" href="#">
      <i class="bi bi-journal-bookmark me-2"></i>LearnTogether
    </a>

    <div class="ms-auto d-flex align-items-center">
      <i class="bi bi-bell me-3 fs-5"></i>
      <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
          <img src="<?= htmlspecialchars($tutor['profile_image'] ?? 'https://via.placeholder.com/40') ?>"
               alt="profile"
               class="rounded-circle me-2"
               width="40">
          <span class="fw-semibold"><?= htmlspecialchars($tutor_name) ?></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="profile.php">Profile</a></li>
          <li><a class="dropdown-item" href="settings.php">Settings</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container-fluid">
    <div class="row">

      <div class="col-md-2 sidebar d-none d-md-block">
        <h5 class="ps-3">Navigation</h5>
        <a href="tutorDashboard.php" class="active"><i class="bi bi-house me-2"></i> Home</a>
        <a href="subjectsTutor.php"><i class="bi bi-journal-bookmark me-2"></i> Subjects</a>
        <a href="scheduleTutor.php"><i class="bi bi-calendar-check me-2"></i> My Schedule</a>
        <a href="requestsTutor.php"><i class="bi bi-people me-2"></i> Student Requests</a>
      </div>

      <div class="col-md-10 p-4">
        <h2 class="fw-bold mb-4">
          Welcome back, <span class="text-primary"><?= htmlspecialchars($tutor_name) ?></span> 👋
        </h2>

        <div class="row g-4 mb-4">
          <div class="col-md-4">
            <div class="card card-custom p-4 text-center">
              <i class="bi bi-chat-dots fs-1 text-primary mb-3"></i>
              <h5>Messages</h5>
              <p class="text-muted">Chat with your students in real time.</p>
              <button class="btn btn-outline-primary btn-sm" onclick="openChat()">Open Chat</button>
            </div>
          </div>

          <div class="col-md-4">
            <div class="card card-custom p-4 text-center">
              <i class="bi bi-camera-video fs-1 text-success mb-3"></i>
              <h5>Start Call</h5>
              <p class="text-muted">Launch a video call with a student.</p>
              <button class="btn btn-outline-success btn-sm" onclick="startCall()">Start Call</button>
            </div>
          </div>
        </div>

        <div class="card card-custom p-4 mb-4">
          <h5 class="mb-3 fw-bold">
            <i class="bi bi-calendar-event me-2 text-primary"></i> Upcoming Sessions
          </h5>

          <ul class="list-group list-group-flush">
            <?php foreach ($sessions as $s): ?>
              <li class="list-group-item">
                <strong><?= htmlspecialchars($s['subject']) ?></strong> —
                <?= date("M d, h:i A", strtotime($s['session_date'])) ?>
                <span class="badge bg-<?= $s['status'] == 'confirmed'
                  ? 'success'
                  : ($s['status'] == 'pending' ? 'warning text-dark' : 'secondary') ?> float-end">
                  <?= ucfirst($s['status']) ?>
                </span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div class="row g-4">
          <div class="col-md-4">
            <div class="card card-custom text-center p-4">
              <h2 class="text-primary"><?= $total_students ?></h2>
              <p class="text-muted mb-0">Total Students</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card card-custom text-center p-4">
              <h2 class="text-success"><?= $confirmed_sessions ?></h2>
              <p class="text-muted mb-0">Confirmed Sessions</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card card-custom text-center p-4">
              <h2 class="text-warning"><?= $pending_requests ?></h2>
              <p class="text-muted mb-0">Pending Requests</p>
            </div>
          </div>
        </div>
      </div>
    </div>
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
