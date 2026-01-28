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

$stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$tutor = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {

    $reservation_id = (int)$_POST['request_id'];
    $session_day    = $_POST['session_day'];
    $start_time     = $_POST['start_time'];
    $end_time       = $_POST['end_time'];
    $duration       = (strtotime($end_time) - strtotime($start_time)) / 60;

    $res_stmt = $pdo->prepare("
        SELECT learner_id, subject 
        FROM reservations 
        WHERE id = ? AND tutor_id = ?
    ");
    $res_stmt->execute([$reservation_id, $tutor_id]);
    $res = $res_stmt->fetch(PDO::FETCH_ASSOC);

    if ($res) {
        $learner_id = $res['learner_id'];
        $subject    = $res['subject'];

        $check = $pdo->prepare("
            SELECT id 
            FROM schedules 
            WHERE reservation_id = ? 
            LIMIT 1
        ");
        $check->execute([$reservation_id]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $update = $pdo->prepare("
                UPDATE schedules 
                SET day_of_week = ?, start_time = ?, end_time = ?, duration = ?, subject = ?, learner_id = ?
                WHERE id = ?
            ");
            $update->execute([
                $session_day,
                $start_time,
                $end_time,
                $duration,
                $subject,
                $learner_id,
                $existing['id']
            ]);
        } else {
            $insert = $pdo->prepare("
                INSERT INTO schedules 
                (tutor_id, reservation_id, learner_id, subject, day_of_week, start_time, end_time, duration)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insert->execute([
                $tutor_id,
                $reservation_id,
                $learner_id,
                $subject,
                $session_day,
                $start_time,
                $end_time,
                $duration
            ]);
        }

        $pdo->prepare("
            UPDATE reservations 
            SET status = 'Scheduled'
            WHERE id = ?
        ")->execute([$reservation_id]);
    }

    header("Location: calendar.php");
    exit;
}

$pending_stmt = $pdo->prepare("
    SELECT r.id AS reservation_id,
           r.subject,
           r.date,
           r.status,
           CONCAT(u.first_name, ' ', u.last_name) AS student_name,
           s.id AS schedule_id,
           s.day_of_week,
           s.start_time,
           s.end_time
    FROM reservations r
    JOIN learners l ON r.learner_id = l.id
    JOIN users u ON l.user_id = u.id
    LEFT JOIN schedules s ON r.id = s.reservation_id
    WHERE r.tutor_id = ? 
      AND r.status IN ('Confirmed', 'Scheduled')
    ORDER BY r.status ASC, r.id ASC
");

$pending_stmt->execute([$tutor_id]);
$pending_requests = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Schedule Management — LearnTogether</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/req.css">
  <link rel="stylesheet" href="../CSS/schedule2.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="app">
    <aside id="sidebar">
      <div class="sidebar">
        <div class="profile" id="sidebarProfile" title="View Profile">
          <div class="avatar"><?= strtoupper($tutor['first_name'][0] ?? 'T') ?></div>
          <div class="profile-text">
            <div class="profile-name"><?= htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']) ?></div>
            <div class="profile-status">Active Tutor</div>
          </div>
          <div class="view-profile-tooltip">View Profile</div>
        </div>
        <nav class="navlinks">
          <a class="nav-link" href="tutorDashboard.php">
            <span class="nav-text">Overview</span>
          </a>
          <a class="nav-link" href="subjects.php">
            <span class="nav-text">My Subjects</span>
          </a>
          <a class="nav-link active" href="calendar.php">
            <span class="nav-text">My Schedule</span>
          </a>
          <a class="nav-link" href="requests.php">
            <span class="nav-text">Requests</span>
          </a>
          <a class="nav-link" href="settings.php">
            <span class="nav-text">Settings</span>
          </a>
          <a class="nav-link logout" href="../logout.php">
            <span class="nav-text">Log Out</span>
          </a>
        </nav>
      </div>
    </aside>

    <div class="overlay" id="overlay"></div>

    <nav class="navbar-top">
      <button class="menu-toggle" id="hamburger">☰</button>
      <div class="navbar-brand-section">
        <div class="navbar-logo">
          <img src="../images/LT.png" alt="LearnTogether Logo" class="logo-img">
          <span class="brand-name">LearnTogether</span>
        </div>
      </div>
      <div class="navbar-search">
        <input type="text" placeholder="Search schedules..." class="search-input">
      </div>
      <div class="navbar-user">
        <div class="user-info">
          <span class="user-name"><?= htmlspecialchars($tutor['first_name'] ?? 'Tutor') ?></span>
          <span class="user-role">Tutor</span>
        </div>
        <div class="user-avatar">
          <?= strtoupper(substr($tutor['first_name'], 0, 1) . substr($tutor['last_name'], 0, 1)) ?>
        </div>
      </div>
    </nav>

    <main class="lt-main mb-4">
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
            <?php foreach ($pending_requests as $req): 
              $isScheduled = $req['status'] === 'Scheduled' && !empty($req['schedule_id']);
              $isConfirmed = $req['status'] === 'Confirmed' && empty($req['schedule_id']);
            ?>
              <div class="col">
                <div class="card custom-card">
                  <div class="card-header text-center"><?= $isScheduled ? 'Edit Schedule' : 'Set Schedule' ?></div>
                  <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($req['subject']) ?></h5>
                    <p class="card-text">From: <?= htmlspecialchars($req['student_name']) ?></p>
                    <?php if ($isScheduled): ?>
                      <p class="text-muted" style="font-size: 0.9rem;">Current: <?= htmlspecialchars($req['day_of_week']) ?> • <?= htmlspecialchars($req['start_time']) ?> - <?= htmlspecialchars($req['end_time']) ?></p>
                    <?php endif; ?>
                    <form method="POST">
                      <input type="hidden" name="request_id" value="<?= $req['reservation_id'] ?>">
                      <label>Day of Week</label>
                      <select name="session_day" class="form-control mb-2" required>
                        <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day): 
                          $selected = ($req['day_of_week'] === $day) ? 'selected' : '';
                        ?>
                          <option value="<?= $day ?>" <?= $selected ?>><?= $day ?></option>
                        <?php endforeach; ?>
                      </select>
                      <label>Time</label>
                      <div class="d-flex mb-3 gap-2">
                        <input type="time" name="start_time" class="form-control" value="<?= htmlspecialchars($req['start_time'] ?? '') ?>" required>
                        <span style="align-self:center;">to</span>
                        <input type="time" name="end_time" class="form-control" value="<?= htmlspecialchars($req['end_time'] ?? '') ?>" required>
                      </div>
                      <button type="submit" class="custom-btn btn btn-success w-100"><?= $isScheduled ? 'Update Schedule' : 'Set Schedule' ?></button>
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
  <script>
    document.addEventListener('contextmenu', event => event.preventDefault());
    document.onkeydown = function(e) {
        if (e.keyCode == 123 || 
            (e.ctrlKey && e.shiftKey && ['I','J','C'].includes(e.key.toUpperCase())) ||
            (e.ctrlKey && e.key.toUpperCase() == 'U')) {
            return false;
        }
    };

    const hamburger = document.getElementById('hamburger');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const profile = document.getElementById('profileDropdown');
    const dropdown = document.getElementById('dropdownMenu');

    hamburger.addEventListener('click', () => {
        hamburger.classList.toggle('open');
        sidebar.classList.toggle('open');
        overlay.classList.toggle('show');
    });

    overlay.addEventListener('click', () => {
        hamburger.classList.remove('open');
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    });

    profile.addEventListener('click', () => {
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    });

    document.addEventListener('click', (e) => {
        if (!profile.contains(e.target)) dropdown.style.display = 'none';
    });
  </script>
</body>
</html>
