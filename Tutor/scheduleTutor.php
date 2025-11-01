<?php
session_start();
require '../db.php';

$tutor_id = $_SESSION['tutor_id'] ?? null;
if (!$tutor_id) {
  header("Location: ../login.php");
  exit;
}

$stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$tutor_id]);
$tutor = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
  SELECT subject, session_date, session_time_start, session_time_end, 
         CONCAT(u.first_name, ' ', u.last_name) AS student_name
  FROM requests r
  JOIN users u ON r.user_id = u.id
  WHERE r.tutor_id = ? AND r.status = 'Confirmed'
  ORDER BY r.session_date ASC
");
$stmt->execute([$tutor_id]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>My Schedule — LearnTogether</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/style2.css">
  <link rel="stylesheet" href="../CSS/navbar.css">
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
          <a href="subjectsTutor.php">📚 My Subjects</a>
          <a class="active" href="scheduleTutor.php">📅 Schedule</a>
          <a href="requestsTutor.php">✉️ Requests</a>
          <a href="settings.php">⚙️ Settings</a>
        </nav>
      </div>
    </aside>

    <div class="nav" role="navigation">
      <div class="logo"><div class="mark">LT</div><div style="font-weight:700">LearnTogether</div></div>
      <div class="search"><input placeholder="Search students or subjects" /></div>
      <div class="nav-actions">
        <button class="icon-btn">🔔</button>
        <button class="icon-btn">💬</button>
        <div style="display:flex;align-items:center;gap:8px">
          <div style="text-align:right;margin-right:6px">
            <div style="font-weight:700"><?= htmlspecialchars($tutor['first_name']) ?></div>
            <div style="font-size:12px;color:var(--muted)">Tutor</div>
          </div>
          <div class="avatar" style="width:40px;height:40px;border-radius:10px">
            <?= strtoupper(substr($tutor['first_name'], 0, 1) . substr($tutor['last_name'], 0, 1)) ?>
          </div>
        </div>
      </div>
    </div>

<main>
  <h1>My Schedule</h1>
  <div class="subjects-grid">
    <?php if (count($sessions) > 0): ?>
      <?php foreach ($sessions as $s): ?>
        <div class="subject-card">
          <div class="subject-header">
            <div class="icon" style="background: linear-gradient(180deg,#4f46e5,#4338ca)">📅</div>
            <div class="subject-title"><?= htmlspecialchars($s['subject']) ?></div>
          </div>
          <div class="subject-desc">Session with Student: <?= htmlspecialchars($s['student_name']) ?></div>
          <div class="topics">
            <span class="topic">Date: <?= date("M d, Y", strtotime($s['session_date'])) ?></span>
            <span class="topic">Time: <?= htmlspecialchars($s['session_time_start']) ?> - <?= htmlspecialchars($s['session_time_end']) ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="color:#666;">No scheduled sessions yet.</p>
    <?php endif; ?>
  </div>
</main>

  </div>

  <script>
    document.querySelectorAll('.navlinks a').forEach(a => {
      a.addEventListener('click', () => {
        document.querySelectorAll('.navlinks a').forEach(x => x.classList.remove('active'));
        a.classList.add('active');
      });
    });
  </script>
</body>
</html>
