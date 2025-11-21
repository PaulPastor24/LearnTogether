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
      $reservation_id = $_POST['request_id'];
      $session_date = $_POST['session_date'];
      $session_time = $_POST['session_time'];
      $update = $pdo->prepare("UPDATE reservations SET status = 'Scheduled', date = ?, time = ? WHERE id = ? AND tutor_id = ?");
      $update->execute([$session_date, $session_time, $reservation_id, $tutor_id]);
      header("Location: scheduleTutor.php");
      exit;
  }

  $pending_stmt = $pdo->prepare("
      SELECT 
          r.id AS reservation_id,
          r.subject,
          r.date AS session_date,
          r.time AS session_time,
          CONCAT(u.first_name, ' ', u.last_name) AS student_name
      FROM reservations r
      JOIN learners l ON r.learner_id = l.id
      JOIN users u ON l.user_id = u.id
      WHERE r.tutor_id = ? AND r.status = 'Confirmed'
      ORDER BY r.id ASC
  ");

  $pending_stmt->execute([$tutor_id]);
  $pending_requests = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);
  $confirmed_stmt = $pdo->prepare("
      SELECT 
          r.id AS reservation_id,
          r.subject,
          r.date AS session_date,
          r.time AS session_time,
          CONCAT(u.first_name, ' ', u.last_name) AS student_name
      FROM reservations r
      JOIN learners l ON r.learner_id = l.id
      JOIN users u ON l.user_id = u.id
      WHERE r.tutor_id = ? AND r.status = 'Scheduled'
      ORDER BY r.date ASC
  ");

  $confirmed_stmt->execute([$tutor_id]);
  $confirmed_sessions = $confirmed_stmt->fetchAll(PDO::FETCH_ASSOC);
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
  <link rel="stylesheet" href="../CSS/topics2.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
  <div class="app">
    <aside>
    <div class="sidebar" style="width: 255px; height: 345px;">
      <div class="profile">
        <div class="avatar">
          <?= isset($tutor['first_name'], $tutor['last_name']) 
              ? strtoupper($tutor['first_name'][0]) 
              : 'T' ?>
        </div>
        <div>
          <div style="font-weight:750"><?= htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']) ?></div>
          <div style="font-size:13px;color:var(--muted)">Active Tutor</div>
        </div>
      </div>
      <nav class="navlinks fw-bold" style="margin-top: 12px;">
        <a href="tutorDashboard.php">🏠 Overview</a>
        <a href="subjects.php">📚 Subjects</a>
        <a class="active" href="scheduleTutor.php">📅 Schedule</a>
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
        <input type="text" placeholder="Search students, subjects..." />
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

    <main>
      <h1>Schedule Management</h1>
      <h2>Pending Requests (Approved by Tutor - Awaiting Scheduling)</h2>
      <?php if (count($pending_requests) > 0): ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
          <?php foreach ($pending_requests as $req): ?>
            <div class="col">
              <div class="card h-100 shadow-sm">
                <div class="card-body">
                  <h5 class="card-title"><?= htmlspecialchars($req['subject']) ?></h5>
                  <p class="card-text">From: <?= htmlspecialchars($req['student_name']) ?></p>
                  <p class="card-text">Requested date: <?= htmlspecialchars($req['session_date']) ?: '—' ?></p>
                  <p class="card-text">Requested time: <?= htmlspecialchars($req['session_time']) ?: '—' ?></p>
                  <form method="POST">
                    <input type="hidden" name="request_id" value="<?= $req['reservation_id'] ?>">
                    <div class="mb-2">
                      <label class="form-label">Date</label>
                      <input type="date" name="session_date" class="form-control" required>
                    </div>
                    <div class="mb-2">
                      <label class="form-label">Time</label>
                      <input type="time" name="session_time" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm mt-2">Set Schedule</button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p style="color:#666;">No pending requests to schedule.</p>
      <?php endif; ?>
      <h2 class="mt-5">Scheduled Sessions</h2>
      <?php if (count($confirmed_sessions) > 0): ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
          <?php foreach ($confirmed_sessions as $s): ?>
            <div class="col">
              <div class="card h-100 shadow-sm">
                <div class="card-body">
                  <h5 class="card-title"><?= htmlspecialchars($s['subject']) ?></h5>
                  <p class="card-text">Student: <?= htmlspecialchars($s['student_name']) ?></p>
                  <p class="card-text">Date: <?= date("M d, Y", strtotime($s['session_date'])) ?></p>
                  <p class="card-text">Time: <?= htmlspecialchars($s['session_time']) ?></p>
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
