<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /LearnTogether/login.php");
    exit;
}

if (!isset($_GET['learner_id'])) {
    header("Location: tutorDashboard.php");
    exit;
}

$learner_id = $_GET['learner_id'];

$stmt = $pdo->prepare("SELECT id FROM tutors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$tutor_row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tutor_row) {
    header("Location: tutorDashboard.php");
    exit;
}

$tutor_id = $tutor_row['id'];

$stmt = $pdo->prepare("
    SELECT u.first_name, u.last_name
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
    SELECT r.subject, r.status
    FROM reservations r
    WHERE r.learner_id = ?
      AND r.tutor_id = ?
      AND r.status = 'Confirmed'
");
$stmt->execute([$learner_id, $tutor_id]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$allTopics = [];
foreach ($reservations as $res) {
    $subject = $res['subject'];
    $stmt = $pdo->prepare("
        SELECT topics
        FROM tutor_subjects
        WHERE tutor_id = ?
          AND subject_name = ?
    ");
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

$totalTopics = count($allTopics);
$doneTopics = 0;
$pendingTopics = 0;
foreach ($allTopics as $topic) {
    if ($topic['status'] === 'Done') {
        $doneTopics++;
    } else {
        $pendingTopics++;
    }
}
$progress = $totalTopics > 0 ? round(($doneTopics / $totalTopics) * 100) : 0;
$sessionsCount = count($allTopics);
$pendingCount = $pendingTopics;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Topics for <?= htmlspecialchars($learner['first_name'] . ' ' . $learner['last_name']) ?></title>
<link rel="stylesheet" href="../CSS/style2.css">
<link rel="stylesheet" href="../CSS/topics.css">
<link rel="stylesheet" href="../CSS/topics2.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="app">
  <div class="nav">
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
          <div><?= htmlspecialchars($learner['first_name'] ?? 'Learner') ?></div>
          <div>Learner</div>
        </div>
        <div class="avatar">
          <?= strtoupper($learner['first_name'][0] . $learner['last_name'][0]) ?>
        </div>
      </div>
    </div>
  </div>
  <div>
    <aside>
      <div class="sidebar">
        <div class="profile">
          <div class="avatar">
            <?= isset($learner['first_name'], $learner['last_name']) ? strtoupper($learner['first_name'][0]) : 'T' ?>
          </div>
          <div>
            <div style="font-weight:750"><?= htmlspecialchars($learner['first_name'] . ' ' . $learner['last_name']) ?></div>
            <div style="font-size:13px;color:var(--muted)">Active Learner</div>
          </div>
        </div>
        <nav class="navlinks fw-bold" style="margin-top: 12px;">
          <a href="tutorDashboard.php">🏠 Overview</a>
          <a href="subjects.php">📚 Subjects</a>
          <a href="scheduleTutor.php">📅 Schedule</a>
          <a href="requests.php">✉️ Requests</a>
          <a href="../logout.php">🚪 Logout</a>
        </nav>
      </div>
    </aside>
    <main class="main-content">
      <div class="main-container bg-white p-4 rounded shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h1 class="fw-bold text-primary"><?= htmlspecialchars($reservations[0]['subject'] ?? 'No Subject') ?></h1>
            <h4 class="text-muted"><?= htmlspecialchars($learner['first_name'] . ' ' . $learner['last_name']) ?></h4>
          </div>
          <div class="d-flex">
            <div class="stats-box me-2 text-center">
              <div class="fs-4 fw-bold text-success"><?= $progress ?>%</div>
              <div class="text-muted">Progress</div>
            </div>
            <div class="stats-box me-2 text-center">
              <div class="fs-4 fw-bold text-info"><?= $sessionsCount ?></div>
              <div class="text-muted">Sessions</div>
            </div>
            <div class="stats-box me-3 text-center">
              <div class="fs-4 fw-bold text-warning"><?= $pendingCount ?></div>
              <div class="text-muted">Pending</div>
            </div>
          </div>
        </div>
        <div class="d-flex justify-content-end mb-4">
          <div class="header-icon-box bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">💬</div>
          <div class="header-icon-box bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">🎥</div>
        </div>
        <div class="lessons-section">
          <h5 class="fw-bold text-secondary">Lessons</h5>
          <?php if (!empty($allTopics)): ?>
            <?php foreach ($reservations as $res): ?>
              <h5 class="fw-semibold mt-4 mb-3 text-primary"><?= htmlspecialchars($res['subject']) ?></h5>
              <?php 
              $num = 1;
              foreach ($allTopics as $topic):
                if ($topic['subject'] !== $res['subject']) continue;
                $status = $topic['status'];
                $badgeClass = $status === 'Done' ? 'status-done' : 'status-pending';
              ?>
              <div class="lesson-card d-flex justify-content-between align-items-center p-3 mb-2 border rounded bg-light">
                <div>
                  <div class="fw-bold">Topic <?= $num++ ?></div>
                  <div><?= htmlspecialchars($topic['title']) ?></div>
                </div>
                <div class="status-badge <?= $badgeClass ?> px-3 py-1 rounded text-white fw-bold">
                  <?= htmlspecialchars($status) ?>
                </div>
              </div>
              <?php endforeach; ?>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="no-topics text-muted">No topics available for this learner yet.</p>
          <?php endif; ?>
        </div>
        <a href="tutorDashboard.php" class="btn btn-secondary back-btn mt-4">Back to Dashboard</a>
      </div>
    </main>
  </div>
</div>
</body>
</html>