<?php
session_start();
require '../db.php';
require '../Agora/agora_config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['reservation_id'])) {
    header("Location: /LearnTogether/login.php");
    exit;
}

$reservation_id = $_GET['reservation_id'];
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT r.*, l.user_id AS learner_user_id, t.user_id AS tutor_user_id, t.id AS tutor_id, r.subject,
           u_learner.first_name AS learner_first, u_learner.last_name AS learner_last,
           u_tutor.first_name AS tutor_first, u_tutor.last_name AS tutor_last
    FROM reservations r
    JOIN learners l ON r.learner_id = l.id
    JOIN tutors t ON r.tutor_id = t.id
    JOIN users u_learner ON l.user_id = u_learner.id
    JOIN users u_tutor ON t.user_id = u_tutor.id
    WHERE r.id = ? AND r.status = 'Confirmed'
");
$stmt->execute([$reservation_id]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation || ($reservation['learner_user_id'] != $user_id && $reservation['tutor_user_id'] != $user_id)) {
    header("Location: /LearnTogether/login.php");
    exit;
}

$subject = $reservation['subject'];
$tutor_id = $reservation['tutor_id'];

$stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

$allTopics = [];
$stmt = $pdo->prepare("SELECT topics FROM tutor_subjects WHERE tutor_id = ? AND subject_name = ?");
$stmt->execute([$tutor_id, $subject]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row && !empty($row['topics'])) {
    $topics = array_map('trim', explode(',', $row['topics']));
    foreach ($topics as $t) {
        if ($t !== '') {
            $allTopics[] = [
                'title' => $t,
                'status' => 'Pending'  // You can adjust status logic if needed
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Topics for <?= htmlspecialchars($subject) ?></title>
<link rel="stylesheet" href="../CSS/req.css">
<link rel="stylesheet" href="../CSS/tutor.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="app">
  <aside>
    <div class="sidebar" style="width:230px;">
      <div class="profile">
        <div class="avatar"><?= strtoupper($current_user['first_name'][0]) ?></div>
        <div>
          <div style="font-weight:750"><?= htmlspecialchars($current_user['first_name'].' '.$current_user['last_name']) ?></div>
          <div style="font-size:13px;color:var(--muted)">Active <?= $reservation['learner_user_id'] == $user_id ? 'Learner' : 'Tutor' ?></div>
        </div>
      </div>
      <nav class="navlinks fw-bold" style="margin-top:12px;">
        <?php if ($reservation['learner_user_id'] == $user_id): ?>
          <a href="learnerDashboard.php">🏠 Overview</a>
          <a href="subjects.php">📚 My Subjects</a>
          <a href="searchTutors.php">🔎 Find Tutors</a>
          <a href="schedule.php">📅 My Schedule</a>
          <a href="requests.php">✉️ Requests</a>
          <a href="../logout.php">🚪 Logout</a>
        <?php else: ?>
          <a href="tutorDashboard.php">🏠 Overview</a>
          <a href="subjects.php">📚 Subjects</a>
          <a href="calendar.php">📅 Schedule</a>
          <a href="requests.php">✉️ Requests</a>
          <a href="../logout.php">🚪 Logout</a>
        <?php endif; ?>
      </nav>
    </div>
  </aside>
  <main style="padding: 40px 50px; margin-left: 300px;">
    <h1>Topics for <?= htmlspecialchars($subject) ?></h1>
    <p class="mb-4">Tutor: <?= htmlspecialchars($reservation['tutor_first'].' '.$reservation['tutor_last']) ?> | Learner: <?= htmlspecialchars($reservation['learner_first'].' '.$reservation['learner_last']) ?></p>
    <?php if (!empty($allTopics)): ?>
      <?php $num=1; foreach ($allTopics as $topic): 
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
    <?php else: ?>
      <p class="text-muted">No topics available for this subject yet.</p>
    <?php endif; ?>
    <a href="<?= $reservation['learner_user_id'] == $user_id ? 'subjects.php' : 'tutorDashboard.php' ?>" class="btn btn-secondary mt-4">Back</a>
  </main>
</div>
</body>
</html>
