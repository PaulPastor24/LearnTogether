<?php
  session_start();
  require '../db.php';

  if (!isset($_SESSION['user_id'])) {
      header("Location: /LearnTogether/login.php");
      exit;
  }

  $user_id = $_SESSION['user_id'];

  $stmt = $pdo->prepare("SELECT first_name, last_name, role FROM users WHERE id = ?");
  $stmt->execute([$user_id]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user) {
      echo "User not found.";
      exit;
  }

  $stmt = $pdo->prepare("SELECT id AS tutor_id FROM tutors WHERE user_id = ?");
  $stmt->execute([$user_id]);
  $tutor_row = $stmt->fetch(PDO::FETCH_ASSOC);

  $tutor = null;
  $tutor_id = null;
  if ($tutor_row) {
      $tutor_id = $tutor_row['tutor_id'];
      $stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
      $stmt->execute([$user_id]);
      $tutor = $stmt->fetch(PDO::FETCH_ASSOC);
  }

  $sessions = [];
  if ($tutor_id) {
      $test_tutor_id = 9; 
      $stmt = $pdo->prepare("
          SELECT 
              s.subject,
              s.day_of_week AS session_day,
              s.start_time AS session_time,
              s.end_time,
              s.duration,
              CONCAT(u.first_name,' ',u.last_name) AS learner_name
          FROM schedules s
          JOIN learners l ON s.learner_id = l.id
          JOIN users u ON l.user_id = u.id
          WHERE s.tutor_id = ?
          ORDER BY FIELD(s.day_of_week, 
              'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'
          ), s.start_time
      ");
      $stmt->execute([$test_tutor_id]);
      $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } else {
      $stmt = $pdo->prepare("SELECT id FROM learners WHERE user_id = ?");
      $stmt->execute([$user_id]);
      $learner = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($learner) {
          $learner_id = $learner['id'];
          $stmt = $pdo->prepare("
              SELECT 
                  s.subject,
                  s.day_of_week AS session_day,
                  s.start_time AS session_time,
                  s.end_time,
                  s.duration,
                  CONCAT(u.first_name,' ',u.last_name) AS tutor_name
              FROM schedules s
              JOIN tutors t ON s.tutor_id = t.id
              JOIN users u ON t.user_id = u.id
              WHERE s.learner_id = ?
              ORDER BY FIELD(s.day_of_week, 
                  'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'
              ), s.start_time
          ");
          $stmt->execute([$learner_id]);
          $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
      }
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && $tutor_id) {
    $reservation_id = (int)$_POST['request_id'];
    $session_day    = $_POST['session_day'];
    $start_time     = $_POST['start_time'];
    $end_time       = $_POST['end_time'];
    $duration       = (strtotime($end_time) - strtotime($start_time)) / 60;

    $res_stmt = $pdo->prepare("SELECT learner_id, subject FROM reservations WHERE id = ? AND tutor_id = ?");
    $res_stmt->execute([$reservation_id, $tutor_id]);
    $res = $res_stmt->fetch(PDO::FETCH_ASSOC);

    if ($res) {
        $learner_id = $res['learner_id'];
        $subject    = $res['subject'];

        $check = $pdo->prepare("SELECT id FROM schedules WHERE reservation_id = ? LIMIT 1");
        $check->execute([$reservation_id]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $update = $pdo->prepare(
                "UPDATE schedules SET day_of_week = ?, start_time = ?, end_time = ?, duration = ?, subject = ?, learner_id = ? WHERE id = ?"
            );
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
            $insert = $pdo->prepare(
                "INSERT INTO schedules (tutor_id, reservation_id, learner_id, subject, day_of_week, start_time, end_time, duration) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
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

        $pdo->prepare("UPDATE reservations SET status = 'Scheduled' WHERE id = ?")->execute([$reservation_id]);
    }

    header("Location: calendar.php");
    exit;
  }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>My Schedule — LearnTogether</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/style2.css">
  <link rel="stylesheet" href="../CSS/schedule.css">

</head>
<body>
  <div class="app">
    <aside id="sidebar">
          <div class="sidebar">
              <div class="profile-dropdown">
                  <div class="avatar"><?= strtoupper($user['first_name'][0]) ?></div>
                  <div>
                      <div style="font-weight:700"><?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?></div>
                      <div style="font-size:13px;color:var(--muted)"><?= $user['role'] == 'tutor' ? 'Active Tutor' : 'Active Learner' ?></div>
                  </div>
              </div>
              <nav class="navlinks">
                  <a class="active" href="learnerDashboard.php">🏠 Overview</a>
                  <a href="subjects.php">📚 My Subjects</a>
                  <a href="searchTutors.php">🔎 Find Tutors</a>
                  <a href="schedule.php">📅 My Schedule</a>
                  <a href="requests.php">✉️ Requests</a>
                  <a href="../logout.php">🚪 Logout</a>
              </nav>
          </div>
      </aside>

      <div class="overlay" id="overlay"></div>

      <div class="nav" role="navigation">
          <div class="hamburger" id="hamburger">
              <span></span>
              <span></span>
              <span></span>
          </div>
          <div class="logo" style="display:flex; align-items:center;">
              <div>
                  <img src="../images/LT.png" alt="LearnTogether Logo" style="width:50px; height:40px;">
              </div>
              <div style="font-weight:700; margin-left:8px;">LearnTogether</div>
          </div>
          <div class="search">
              <input placeholder="Search tutors, subjects or topics" />
          </div>
          <div class="nav-actions">
              <div style="display:flex;align-items:center;gap:8px">
                  <div style="text-align:right;margin-right:6px">
                      <div style="font-weight:700"><?= htmlspecialchars($user['first_name']) ?></div>
                      <div style="font-size:12px;color:var(--muted)"><?= $user['role'] == 'tutor' ? 'Tutor' : 'Learner' ?></div>
                  </div>
                  <div class="avatar" style="width:40px;height:40px;border-radius:10px">
                      <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                  </div>
              </div>
          </div>
      </div>

    <main class="hero">
      <div class="hero-header">
        <h1>Welcome Back, <?= htmlspecialchars($user['first_name']) ?></h1>
        <p>View your upcoming sessions below.</p>
      </div>

      <section class="card-lg">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
          <div style="font-weight:700;font-size:16px;">Upcoming Sessions</div>
          <div style="color:#6b7280;font-size:14px;">Scheduled</div>
        </div>

        <?php if (count($sessions) > 0): ?>
          <div class="session-list">
            <?php foreach ($sessions as $s): 
              $start = date("H:i", strtotime($s['session_time']));
              $end = date("H:i", strtotime($s['end_time']));
              $partner_name = $tutor_id ? $s['learner_name'] : $s['tutor_name'];
            ?>
              <div class="session-item">
                <div class="date">
                  <strong><?= htmlspecialchars($s['session_day']) ?></strong>
                  <span><?= $start ?>–<?= $end ?></span>
                </div>
                <div class="meta">
                  <div style="font-weight:700"><?= htmlspecialchars($s['subject']) ?> — <?= htmlspecialchars($partner_name) ?></div>
                  <div style="font-size:13px;color:#6b7280;margin-top:6px;">Online • <?= $s['duration'] ?> min</div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p style="color:#666;margin:6px 0 0;">You have no scheduled sessions yet.</p>
        <?php endif; ?>
      </section>
    </main>
  </div>

  <script>
    const hamburger = document.getElementById('hamburger');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');

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
  </script>
</body>
</html>
