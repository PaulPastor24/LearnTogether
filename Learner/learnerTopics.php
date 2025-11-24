<?php
session_start();
require '../db.php';
require '../Agora/agora_config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['learner_id'])) {
    header("Location: /LearnTogether/login.php");
    exit;
}

$learner_id = $_GET['learner_id'];
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

$stmt = $pdo->prepare("
    SELECT l.id AS learner_id, u.first_name, u.last_name
    FROM learners l
    JOIN users u ON l.user_id = u.id
    WHERE l.id = ?
");
$stmt->execute([$learner_id]);
$learner = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$learner) {
    header("Location: tutorDashboard.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT r.id AS reservation_id, r.subject, r.date, r.time, r.status,
           u.first_name AS tutor_first_name, u.last_name AS tutor_last_name
    FROM reservations r
    JOIN users u ON r.tutor_id = u.id
    WHERE r.learner_id = ? AND r.tutor_id = ? AND r.status = 'Confirmed'
    ORDER BY r.date DESC, r.time DESC
");
$stmt->execute([$learner_id, $tutor_id]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$allTopics = [];
foreach ($reservations as $res) {
    $subject = $res['subject'];
    $stmt = $pdo->prepare("SELECT topics FROM tutor_subjects WHERE tutor_id = ? AND subject_name = ?");
    $stmt->execute([$tutor_id, $subject]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['topics'])) {
        $topics = array_map('trim', explode(',', $row['topics']));
        foreach ($topics as $t) {
            if ($t !== '') {
                $allTopics[] = [
                    'subject' => $subject,
                    'title' => $t,
                    'status' => 'Pending'
                ];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Topics for <?= htmlspecialchars($learner['first_name'].' '.$learner['last_name']) ?></title>
<link rel="stylesheet" href="../CSS/req.css">
<link rel="stylesheet" href="../CSS/tutor.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="app">
  <aside>
    <div class="sidebar" style="width:230px;">
      <div class="profile">
        <div class="avatar"><?= strtoupper($tutor['first_name'][0]) ?></div>
        <div>
          <div style="font-weight:750"><?= htmlspecialchars($tutor['first_name'].' '.$tutor['last_name']) ?></div>
          <div style="font-size:13px;color:var(--muted)">Active Tutor</div>
        </div>
      </div>
      <nav class="navlinks fw-bold" style="margin-top:12px;">
        <a href="tutorDashboard.php">🏠 Overview</a>
        <a href="subjects.php">📚 Subjects</a>
        <a href="calendar.php">📅 Schedule</a>
        <a href="requests.php">✉️ Requests</a>
        <a href="../logout.php">🚪 Logout</a>
      </nav>
    </div>
  </aside>
  <main style="padding: 40px 50px; margin-left: 300px;">
    <h1><?= htmlspecialchars($learner['first_name'].' '.$learner['last_name']) ?>'s Topics</h1>
    <?php if (!empty($allTopics)): ?>
      <?php foreach ($reservations as $res): ?>
        <h5 class="mt-4 mb-3 text-primary">
          <?= htmlspecialchars($res['subject']) ?> 
          (Tutor: <?= htmlspecialchars($res['tutor_first_name'].' '.$res['tutor_last_name']) ?>)
        </h5>
        <?php $num=1; foreach ($allTopics as $topic): 
            if ($topic['subject'] !== $res['subject']) continue;
            $badgeClass = $topic['status']==='Done' ? 'bg-success' : 'bg-warning';
        ?>
          <a href="#" class="lesson-card d-flex justify-content-between align-items-center p-3 mb-2 border rounded bg-light text-decoration-none text-dark">
            <div>
              <div class="fw-bold">Topic <?= $num++ ?></div>
              <div><?= htmlspecialchars($topic['title']) ?></div>
            </div>
            <div class="status-badge <?= $badgeClass ?> px-3 py-1 rounded text-white fw-bold"><?= htmlspecialchars($topic['status']) ?></div>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="text-muted">No topics available for this learner yet.</p>
    <?php endif; ?>
    <a href="tutorDashboard.php" class="btn btn-secondary mt-4">Back to Dashboard</a>
  </main>
</div>
</body>
</html>
