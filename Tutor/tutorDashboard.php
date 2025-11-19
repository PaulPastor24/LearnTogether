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

$stmt = $pdo->prepare("
    SELECT 
        u.id AS learner_id, 
        u.first_name, 
        u.last_name, 
        GROUP_CONCAT(r.subject SEPARATOR ', ') AS subjects
    FROM reservations r
    JOIN learners l ON r.learner_id = l.id
    JOIN users u ON l.user_id = u.id
    WHERE r.tutor_id = ? AND r.status = 'Confirmed'
    GROUP BY u.id, u.first_name, u.last_name
    ORDER BY u.first_name ASC
");
$stmt->execute([$tutor_id]);
$learners = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Tutor Dashboard - LearnTogether</title>
<link rel="stylesheet" href="../CSS/style2.css">
<link rel="stylesheet" href="../CSS/tutor.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="app">
  <aside>
    <div class="sidebar" style="width: 255px; height: 345px;">
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
        <a class="active" href="tutorDashboard.php">🏠 Overview</a>
        <a href="subjects.php">📚 Subjects</a>
        <a href="scheduleTutor.php">📅 Schedule</a>
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
    <h1>Welcome back, <?= htmlspecialchars($tutor['first_name']) ?> 👋</h1>

    <h2 style="margin-top:40px;">Your Learners</h2>
    <?php if (!empty($learners)): ?>
    <div class="learners-grid">
      <?php foreach ($learners as $l): ?>
      <div class="learner-card">
        <div class="learner-avatar"><?= strtoupper($l['first_name'][0] . $l['last_name'][0]) ?></div>
        <div class="learner-info">
          <div class="learner-name"><?= htmlspecialchars($l['first_name'] . ' ' . $l['last_name']) ?></div>
          <div class="learner-subject"><?= htmlspecialchars($l['subjects']) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="no-learners">No learners have reserved you yet.</div>
    <?php endif; ?>
  </main>
</div>
</body>
</html>
