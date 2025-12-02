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
    WHERE r.id = ? AND r.status = 'Scheduled'
");
$stmt->execute([$reservation_id]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation || ($reservation['learner_user_id'] != $user_id && $reservation['tutor_user_id'] != $user_id)) {
    header("Location: /LearnTogether/login.php");
    exit;
}

$subject = $reservation['subject'];
$tutor_id = $reservation['tutor_id'];

$stmt = $pdo->prepare("SELECT first_name, last_name, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$role = $reservation['learner_user_id'] == $user_id ? 'Learner' : 'Tutor';

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
                'status' => 'Pending'
            ];
        }
    }
}

$totalTopics = count($allTopics);
$doneTopics = 0;
$pendingTopics = 0;
foreach ($allTopics as $topic) {
    if ($topic['status'] === 'Done') $doneTopics++;
    else $pendingTopics++;
}
$progress = $totalTopics > 0 ? round(($doneTopics / $totalTopics) * 100) : 0;
$sessionsCount = $totalTopics;
$pendingCount = $pendingTopics;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Topics for <?= htmlspecialchars($subject) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../CSS/style2.css">
<link rel="stylesheet" href="../CSS/tutor.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
<div class="app">

  <aside id="sidebar">
    <div class="sidebar">
      <div class="profile-dropdown">
        <div class="avatar"><?= strtoupper($user['first_name'][0]) ?></div>
        <div>
          <div style="font-weight:700"><?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?></div>
          <div style="font-size:13px;color:var(--muted)">Active <?= $role ?></div>
        </div>
      </div>

      <nav class="navlinks">
        <?php if ($reservation['learner_user_id'] == $user_id): ?>
          <a href="learnerDashboard.php">🏠 Overview</a>
          <a href="subjects.php">📚 My Subjects</a>
          <a href="searchTutors.php">🔎 Find Tutors</a>
          <a href="schedule.php">📅 My Schedule</a>
          <a href="requests.php">✉️ Requests</a>
          <a href="setting.php">⚙️ Settings</a>
          <a href="../logout.php">🚪 Logout</a>
        <?php else: ?>
          <a href="tutorDashboard.php">🏠 Overview</a>
          <a href="subjects.php">📚 Subjects</a>
          <a href="calendar.php">📅 Schedule</a>
          <a href="requests.php">✉️ Requests</a>
          <a href="setting.php">⚙️ Settings</a>
          <a href="../logout.php">🚪 Logout</a>
        <?php endif; ?>
      </nav>
    </div>
  </aside>

  <div class="overlay" id="overlay"></div>

  <main class="hero" style="margin-top: 10px;">

    <div class="main-container bg-white p-4 rounded shadow-sm">

      <div class="header-section d-flex justify-content-between align-items-start">

        <div class="header-left">
          <div class="hamburger" id="hamburger">
            <span></span><span></span><span></span>
          </div>

          <h1>Topics for <?= htmlspecialchars($subject) ?></h1>

          <p><?= htmlspecialchars($reservation['learner_first'].' '.$reservation['learner_last']) ?></p>

          <small class="text-muted">
            Tutor: <?= htmlspecialchars($reservation['tutor_first'].' '.$reservation['tutor_last']) ?> |
            Learner: <?= htmlspecialchars($reservation['learner_first'].' '.$reservation['learner_last']) ?>
          </small>
        </div>

        <div class="d-flex gap-3">
          <div class="stats-box">
            <div class="fs-4 fw-bold text-primary"><?= $progress ?>%</div>
            <div class="text-muted">Progress</div>
          </div>
          <div class="stats-box">
            <div class="fs-4 fw-bold text-info"><?= $sessionsCount ?></div>
            <div class="text-muted">Sessions</div>
          </div>
          <div class="stats-box">
            <div class="fs-4 fw-bold text-warning"><?= $pendingCount ?></div>
            <div class="text-muted">Pending</div>
          </div>
        </div>

      </div>
      <div class="d-flex justify-content-end gap-2 mt-3">
          <a href="../agoraconvo.php?reservation_id=<?= $reservation_id ?>&tutor_id=<?= $reservation['tutor_id'] ?>&learner_id=<?= $reservation['learner_user_id'] ?>"
             class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center"
             style="width:50px;height:50px;font-size:20px;" target="_blank">💬</a>

          <a href="../meetingPage.php?reservation_id=<?= $reservation_id ?>&tutor_id=<?= $reservation['tutor_id'] ?>&learner_id=<?= $reservation['learner_user_id'] ?>"
             class="btn btn-secondary rounded-circle d-flex align-items-center justify-content-center"
             style="width:50px;height:50px;font-size:20px;" target="_blank">🎥</a>
      </div>


        <?php if (!empty($allTopics)): ?>
          <?php 
          $num = 1;
          foreach ($allTopics as $topic): 
              $badgeClass = $topic['status']==='Done' ? 'bg-success' : 'bg-warning';
          ?>
            <div class="topic-card">
              <div>
                <div class="fw-bold">Topic <?= $num++ ?></div>
                <div><?= htmlspecialchars($topic['title']) ?></div>
              </div>

              <div class="status-badge <?= $badgeClass ?> text-white rounded px-3 py-1">
                <?= htmlspecialchars($topic['status']) ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-muted">No topics available for this subject yet.</p>
        <?php endif; ?>

        <a href="<?= $reservation['learner_user_id'] == $user_id ? 'subjects.php' : 'learnerDashboard.php' ?>" 
           class="btn btn-secondary mt-4">Back</a>
    </div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
