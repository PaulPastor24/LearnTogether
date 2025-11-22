<?php
session_start();
require '../db.php';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $reservation_id = (int) $_POST['request_id'];
    $session_date = $_POST['session_date']; // expected YYYY-MM-DD
    $session_time = $_POST['session_time']; // expected HH:MM
    $duration = isset($_POST['duration']) ? (int) $_POST['duration'] : 60;

    // find learner_id and subject from reservation (optional, helpful to store in schedules)
    $r = $pdo->prepare("SELECT learner_id, subject FROM reservations WHERE id = ? AND tutor_id = ?");
    $r->execute([$reservation_id, $tutor_id]);
    $resRow = $r->fetch(PDO::FETCH_ASSOC);

    $learner_id = $resRow['learner_id'] ?? null;
    $subject = $resRow['subject'] ?? null;

    // check if a schedule for this reservation already exists
    $check = $pdo->prepare("SELECT id FROM schedules WHERE reservation_id = ? LIMIT 1");
    $check->execute([$reservation_id]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // update existing schedule
        $up = $pdo->prepare("UPDATE schedules SET date = ?, time = ?, duration = ?, subject = ?, learner_id = ? WHERE id = ? AND tutor_id = ?");
        $up->execute([$session_date, $session_time, $duration, $subject, $learner_id, $existing['id'], $tutor_id]);
    } else {
        // insert new schedule row
        $ins = $pdo->prepare("INSERT INTO schedules (tutor_id, reservation_id, learner_id, subject, date, time, duration) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([$tutor_id, $reservation_id, $learner_id, $subject, $session_date, $session_time, $duration]);
    }

    // Redirect to the calendar so tutor immediately sees it.
    header("Location: calendar.php");
    exit;
}

$pending_stmt = $pdo->prepare("
    SELECT r.id AS reservation_id, r.subject, r.date AS session_date, r.time AS session_time,
           CONCAT(u.first_name, ' ', u.last_name) AS student_name
    FROM reservations r
    JOIN learners l ON r.learner_id = l.id
    JOIN users u ON l.user_id = u.id
    WHERE r.tutor_id = ? AND r.status = 'Confirmed'
    ORDER BY r.id ASC
");
$pending_stmt->execute([$tutor_id]);
$pending_requests = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);

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
  <link rel="stylesheet" href="../CSS/req.css">
  <link rel="stylesheet" href="../CSS/schedule.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="app">
    <aside>
      <div class="sidebar" style="width: 230px; height: 345px;">
        <div class="profile">
          <div class="avatar">
            <?= isset($tutor['first_name'], $tutor['last_name']) ? strtoupper($tutor['first_name'][0]) : 'T' ?>
          </div>
          <div>
            <div style="font-weight:750"><?= htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']) ?></div>
            <div style="font-size:13px;color:var(--muted)">Active Tutor</div>
          </div>
        </div>
        <nav class="navlinks fw-bold" style="margin-top: 12px;">
          <a href="tutorDashboard.php">🏠 Overview</a>
          <a href="subjects.php">📚 Subjects</a>
          <a class="active" href="calendar.php">📅 Schedule</a>
          <a href="requests.php">✉️ Requests</a>
          <a href="../logout.php">🚪 Logout</a>
        </nav>
      </div>
    </aside>

    <div class="nav" style="height: 85px;">
      <div class="logo">
        <div class="mark" style="margin-left: 20px;">LT</div>
        <div>LearnTogether</div>
      </div>
      <div class="search">
        <input type="text" placeholder="Search students, subjects...">
      </div>
      <div class="nav-actions">
        <div style="display:flex;align-items:center;gap:8px;">
          <div class="profile-info">
            <div><?= htmlspecialchars($tutor['first_name'] ?? 'Tutor') ?></div>
            <div>Tutor</div>
          </div>
          <div class="avatar">
            <?= isset($tutor['first_name'], $tutor['last_name']) ? strtoupper($tutor['first_name'][0] . $tutor['last_name'][0]) : 'T' ?>
          </div>
        </div>
      </div>
    </div>

    <main class="p-4" style="margin-left: 400px; margin-top: -10px;">
        <div class="manage-header d-flex align-items-center mb-3">
          <a href="calendar.php" class="back-to-calendar" aria-label="Back to calendar">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M15 18L9 12L15 6" stroke="#0f172a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </a>
          <h1 class="manage-title mb-0" style="margin-left:12px; font-weight:800; font-size:32px;">Manage Schedule</h1>
        </div>

        <h2 class="h5 mb-3">Pending Requests (Approved by Tutor - Awaiting Scheduling)</h2>

        <?php if (count($pending_requests) > 0): ?>
          <div class="container mt-2">
            <div class="row schedule-cards">
              <?php foreach ($pending_requests as $req): ?>
                <div class="col">
                  <div class="card custom-card">
                    <div class="card-header text-center">
                      Set Schedule
                    </div>
                    <div class="card-body">
                      <h5 class="card-title"><?= htmlspecialchars($req['subject']) ?></h5>
                      <p class="card-text">From: <?= htmlspecialchars($req['student_name']) ?></p>
                      <form method="POST">
                        <input type="hidden" name="request_id" value="<?= $req['reservation_id'] ?>">
                        <label>Date</label>
                        <input type="date" name="session_date" class="form-control mb-2" required>
                        <label>Time</label>
                        <input type="time" name="session_time" class="form-control mb-3" required>
                        <input type="hidden" name="duration" value="60">
                        <button type="submit" class="custom-btn btn btn-success">Set Schedule</button>
                      </form>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php else: ?>
          <p style="color:#666;">No pending requests to schedule.</p>
        <?php endif; ?>

    </main>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>