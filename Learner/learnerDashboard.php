<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /LearnTogether/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch basic user info
$stmt = $pdo->prepare("SELECT first_name, last_name, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User not found.";
    exit;
}

// If the user is a tutor, fetch tutor row and tutor info
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

// Handle schedule set form (same as tutor flow)
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

// For tutor: fetch pending (confirmed) reservations that need scheduling
$pending_requests = [];
if ($tutor_id) {
    $pending_stmt = $pdo->prepare(
        "SELECT r.id AS reservation_id, r.subject, r.date, CONCAT(u.first_name, ' ', u.last_name) AS student_name FROM reservations r JOIN learners l ON r.learner_id = l.id JOIN users u ON l.user_id = u.id WHERE r.tutor_id = ? AND r.status = 'Confirmed' ORDER BY r.id ASC"
    );
    $pending_stmt->execute([$tutor_id]);
    $pending_requests = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// For learner: fetch their requests (keeps original functionality in case user views both roles)
$requests_stmt = $pdo->prepare(
    "SELECT r.id, r.subject, r.date AS session_date, r.time AS session_time, r.status, u.first_name AS tutor_first_name, u.last_name AS tutor_last_name FROM reservations r JOIN users u ON r.tutor_id = u.id WHERE r.learner_id = ? ORDER BY r.date DESC, r.time DESC"
);
$requests_stmt->execute([$user_id]);
$requests = $requests_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Manage Schedule — LearnTogether</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/style2.css">
  <link rel="stylesheet" href="../CSS/schedule.css">
  <style>
    main.hero {
      margin-left: 290px;
      padding: 15px;
    }

    .hero-header {
      background:#f0fdf4; /* pale green */
      padding: 10px 10px;
      border-radius:8px;
      margin-bottom:20px;
    }
    .hero-header h1 { font-size:38px; margin:0; font-weight:800; }
    .hero-header p { margin:8px 0 0; color:#374151 }

    .card-lg {
      background: #ffffff;
      border-radius:10px;
      padding:18px;
      box-shadow: 0 6px 18px rgba(11,22,14,0.03);
    }

    .session-list .session-item { display:flex; align-items:center; gap:18px; padding:16px; border:1px solid #e6e6e6; border-radius:8px; margin-bottom:12px; }
    .session-list .date { width:88px; text-align:center; background:#f8faf8; border-radius:6px; padding:10px 8px; font-weight:700; }
    .session-list .meta { flex:1; }
    .status { font-weight:700; }

    @media (max-width:900px){
      main.hero { margin-left:0; padding:20px; }
    }
  </style>
</head>
<body>
<div class="app">
  <aside>
        <div class="sidebar">
            <div class="profile-dropdown">
                <div class="avatar"><?= strtoupper($user['first_name'][0]) ?></div>
                <div>
                    <div style="font-weight:700"><?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?></div>
                    <div style="font-size:13px;color:var(--muted)">Active Learner</div>
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

    <div class="nav" role="navigation">
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
                    <div style="font-size:12px;color:var(--muted)">Learner</div>
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
      <p>Manage your schedule and upcoming sessions below.</p>
    </div>

    <section class="card-lg">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <div style="font-weight:700;font-size:16px;">Upcoming Sessions</div>
        <div style="color:#6b7280;font-size:14px;">Next 7 days</div>
      </div>

      <?php if (count($pending_requests) > 0): ?>
        <div class="session-list">
          <?php foreach ($pending_requests as $req): ?>
            <div class="session-item">
              <div class="date">
                <?php
                  $d = date_create($req['date']);
                  echo date_format($d, 'M d');
                ?>
              </div>
              <div class="meta">
                <div style="font-weight:700"><?= htmlspecialchars($req['subject']) ?> — <?= htmlspecialchars($req['student_name']) ?></div>
                <div style="font-size:13px;color:#6b7280;margin-top:6px;">Online • 60 min</div>
              </div>
              <div style="text-align:right;min-width:110px;">
                <form method="POST" style="margin-bottom:8px;">
                  <input type="hidden" name="request_id" value="<?= $req['reservation_id'] ?>">
                  <select name="session_day" required style="padding:6px;border-radius:6px;border:1px solid #e5e7eb;margin-bottom:6px;">
                    <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day): ?>
                      <option value="<?= $day ?>"><?= $day ?></option>
                    <?php endforeach; ?>
                  </select>
                  <div style="display:flex;gap:6px;margin-top:6px;">
                    <input type="time" name="start_time" required style="padding:6px;border-radius:6px;border:1px solid #e5e7eb;">
                    <input type="time" name="end_time" required style="padding:6px;border-radius:6px;border:1px solid #e5e7eb;">
                  </div>
                  <button type="submit" style="margin-top:8px;padding:8px 10px;border-radius:8px;border:none;background:#10b981;color:white;cursor:pointer;">Set</button>
                </form>
                <div style="color:#10b981;font-weight:700;margin-top:8px;">Confirmed</div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p style="color:#666;margin:6px 0 0;">No pending requests to schedule.</p>
      <?php endif; ?>
    </section>
  </main>
</div>

<script>
  // simple dropdown toggle if needed
  const profile = document.getElementById('profileDropdown');
  if (profile) {
    profile.addEventListener('click', () => {
      // toggle logic if you add a dropdown
    });
  }
</script>
</body>
</html>
