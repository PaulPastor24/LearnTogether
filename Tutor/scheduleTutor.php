<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /LearnTogether/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get tutor_id
$stmt = $pdo->prepare("SELECT id AS tutor_id FROM tutors WHERE user_id = ?");
$stmt->execute([$user_id]);
$tutor_row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tutor_row) {
    header("Location: ../roleSelector.php");
    exit;
}

$tutor_id = $tutor_row['tutor_id'];

// Handle scheduling form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $request_id = $_POST['request_id'];
    $session_date = $_POST['session_date'];
    $session_time_start = $_POST['session_time_start'];
    $session_time_end = $_POST['session_time_end'];

    $update = $pdo->prepare("UPDATE requests SET status='Confirmed', session_date=?, session_time_start=?, session_time_end=? WHERE id=? AND tutor_id=?");
    $update->execute([$session_date, $session_time_start, $session_time_end, $request_id, $tutor_id]);
    header("Location: scheduleTutor.php");
    exit;
}

// Fetch pending requests
$pending_stmt = $pdo->prepare("
    SELECT r.id, r.subject, r.message, CONCAT(u.first_name, ' ', u.last_name) AS student_name
    FROM requests r
    JOIN users u ON r.user_id = u.id
    WHERE r.tutor_id = ? AND r.status='Pending'
    ORDER BY r.id ASC
");
$pending_stmt->execute([$tutor_id]);
$pending_requests = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch confirmed sessions
$confirmed_stmt = $pdo->prepare("
    SELECT r.subject, r.session_date, r.session_time_start, r.session_time_end,
           CONCAT(u.first_name, ' ', u.last_name) AS student_name
    FROM requests r
    JOIN users u ON r.user_id = u.id
    WHERE r.tutor_id = ? AND r.status='Confirmed'
    ORDER BY r.session_date ASC
");
$confirmed_stmt->execute([$tutor_id]);
$confirmed_sessions = $confirmed_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch tutor info
$stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$tutor = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Schedule Management — LearnTogether</title>
<link rel="stylesheet" href="../CSS/style2.css">
<link rel="stylesheet" href="../CSS/navbar.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<div class="app">
  <aside>
    <div class="sidebar">
      <div class="profile">
        <div class="avatar"><?= strtoupper($tutor['first_name'][0]) ?></div>
        <div>
          <div style="font-weight:700"><?= htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']) ?></div>
          <div style="font-size:13px;color:var(--muted)">Active Tutor</div>
        </div>
      </div>
      <nav class="navlinks">
        <a href="tutorDashboard.php">🏠 Overview</a>
        <a href="subjects.php">📚 Subjects</a>
        <a class="active" href="scheduleTutor.php">📅 Schedule</a>
        <a href="requests.php">✉️ Requests</a>
        <a href="../logout.php">🚪 Logout</a>
      </nav>
    </div>
  </aside>

  <div class="nav">
    <div class="logo"><div class="mark">LT</div><div>LearnTogether</div></div>
  </div>

  <main>
    <h1>Schedule Management</h1>

    <!-- Pending Requests -->
    <h2>Pending Requests</h2>
    <?php if (count($pending_requests) > 0): ?>
      <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach ($pending_requests as $req): ?>
          <div class="col">
            <div class="card h-100 shadow-sm">
              <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($req['subject']) ?></h5>
                <p class="card-text">From: <?= htmlspecialchars($req['student_name']) ?></p>
                <p class="card-text"><em><?= htmlspecialchars($req['message']) ?></em></p>
                <form method="POST">
                  <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                  <div class="mb-2">
                    <label class="form-label">Date</label>
                    <input type="date" name="session_date" class="form-control" required>
                  </div>
                  <div class="mb-2">
                    <label class="form-label">Start Time</label>
                    <input type="time" name="session_time_start" class="form-control" required>
                  </div>
                  <div class="mb-2">
                    <label class="form-label">End Time</label>
                    <input type="time" name="session_time_end" class="form-control" required>
                  </div>
                  <button type="submit" class="btn btn-success btn-sm mt-2">Confirm & Schedule</button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p style="color:#666;">No pending requests.</p>
    <?php endif; ?>

    <!-- Confirmed Sessions -->
    <h2 class="mt-5">Confirmed Sessions</h2>
    <?php if (count($confirmed_sessions) > 0): ?>
      <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach ($confirmed_sessions as $s): ?>
          <div class="col">
            <div class="card h-100 shadow-sm">
              <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($s['subject']) ?></h5>
                <p class="card-text">Student: <?= htmlspecialchars($s['student_name']) ?></p>
                <p class="card-text">Date: <?= date("M d, Y", strtotime($s['session_date'])) ?></p>
                <p class="card-text">Time: <?= htmlspecialchars($s['session_time_start']) ?> - <?= htmlspecialchars($s['session_time_end']) ?></p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p style="color:#666;">No scheduled sessions yet.</p>
    <?php endif; ?>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
