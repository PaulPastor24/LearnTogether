<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /LearnTogether/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$tutor_id = $_GET['tutor_id'] ?? null;

if (!$tutor_id) {
    $stmt = $pdo->prepare("SELECT id FROM tutors WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $tutor_row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($tutor_row) {
        $tutor_id = $tutor_row['id'];
    } else {
        header("Location: tutorDashboard.php");
        exit;
    }
}

$stmt = $pdo->prepare("
    SELECT t.id, t.user_id, t.average_rating, t.total_ratings,
           u.first_name, u.last_name, u.email, u.phone
    FROM tutors t
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ?
");
$stmt->execute([$tutor_id]);
$tutor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tutor) {
    header("Location: tutorDashboard.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT subject_name, topics FROM tutor_subjects 
    WHERE tutor_id = ?
    ORDER BY subject_name ASC
");
$stmt->execute([$tutor_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT rating, COUNT(*) as count 
    FROM tutor_ratings 
    WHERE tutor_id = ?
    GROUP BY rating
    ORDER BY rating DESC
");
$stmt->execute([$tutor_id]);
$ratingBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
$ratingMap = [];
for ($i = 1; $i <= 5; $i++) {
    $ratingMap[$i] = 0;
}
foreach ($ratingBreakdown as $breakdown) {
    $ratingMap[$breakdown['rating']] = $breakdown['count'];
}

$stmt = $pdo->prepare("SELECT first_name, last_name, role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$isOwnProfile = isset($_SESSION['tutor_id']) && $_SESSION['tutor_id'] == $tutor_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($tutor['first_name'].' '.$tutor['last_name']) ?> - Tutor Profile</title>
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
          <div style="font-size:13px;color:var(--muted)"><?= htmlspecialchars($user['role']) ?></div>
        </div>
      </div>

      <nav class="navlinks">
        <?php if ($user['role'] === 'Learner'): ?>
          <a href="../Learner/learnerDashboard.php">🏠 Overview</a>
          <a href="../Learner/subjects.php">📚 My Subjects</a>
          <a href="../Learner/searchTutors.php">🔎 Find Tutors</a>
          <a href="../Learner/schedule.php">📅 My Schedule</a>
          <a href="../Learner/requests.php">✉️ Requests</a>
          <a href="../Learner/settings.php">⚙️ Settings</a>
          <a href="../logout.php">🚪 Logout</a>
        <?php else: ?>
          <a href="tutorDashboard.php">🏠 Overview</a>
          <a href="subjects.php">📚 Subjects</a>
          <a href="calendar.php">📅 Schedule</a>
          <a href="requests.php">✉️ Requests</a>
          <a href="settings.php">⚙️ Settings</a>
          <a href="../logout.php">🚪 Logout</a>
        <?php endif; ?>
      </nav>
    </div>
  </aside>

  <div class="overlay" id="overlay"></div>

  <main class="hero" style="margin-top: 10px;">

    <div class="main-container bg-white p-4 rounded shadow-sm">

      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="hamburger" id="hamburger">
            <span></span><span></span><span></span>
          </div>
          <h1><?= htmlspecialchars($tutor['first_name'].' '.$tutor['last_name']) ?></h1>
          <p class="text-muted mb-0">Tutor Profile</p>
        </div>
        <?php if ($isOwnProfile): ?>
          <a href="setting.php" class="btn btn-primary">Edit Profile</a>
        <?php endif; ?>
      </div>

      <!-- Profile Header -->
      <div class="profile-section mt-5 p-4 border rounded bg-light">
        <div class="row">
          <div class="col-md-6">
            <div class="mb-3">
              <label class="text-muted small">Email</label>
              <p class="fw-bold"><?= htmlspecialchars($tutor['email']) ?></p>
            </div>
            <div class="mb-3">
              <label class="text-muted small">Phone</label>
              <p class="fw-bold"><?= htmlspecialchars($tutor['phone'] ?: 'Not provided') ?></p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <label class="text-muted small">Total Reviews</label>
              <p class="fw-bold"><?= htmlspecialchars($tutor['total_ratings'] ?? 0) ?> reviews</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Ratings Section -->
      <div class="mt-5">
        <h3 class="mb-4">Overall Ratings</h3>
        
        <div class="row">
          <div class="col-md-4">
            <div class="rating-card text-center p-4 border rounded">
              <div class="rating-average" style="font-size: 48px; font-weight: bold; color: #ffc107; margin-bottom: 10px;">
                <?= $tutor['average_rating'] ? number_format($tutor['average_rating'], 1) : 'N/A' ?>
              </div>
              <div class="stars mb-3" style="font-size: 24px; letter-spacing: 5px; color: #ffc107;">
                <?php
                $stars = round($tutor['average_rating'] ?? 0);
                for ($i = 1; $i <= 5; $i++) {
                    echo $i <= $stars ? '★' : '☆';
                }
                ?>
              </div>
              <small class="text-muted">Based on <?= $tutor['total_ratings'] ?? 0 ?> ratings</small>
            </div>
          </div>

          <div class="col-md-8">
            <div class="rating-breakdown">
              <?php for ($star = 5; $star >= 1; $star--): ?>
                <div class="rating-bar mb-3">
                  <div class="d-flex align-items-center gap-3">
                    <div style="min-width: 60px;">
                      <span style="font-weight: 600;"><?= $star ?></span>
                      <span style="color: #ffc107; font-size: 18px;">★</span>
                    </div>
                    <div class="progress" style="flex: 1; height: 25px;">
                      <?php
                      $count = $ratingMap[$star] ?? 0;
                      $percentage = $tutor['total_ratings'] > 0 ? ($count / $tutor['total_ratings']) * 100 : 0;
                      ?>
                      <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $percentage ?>%;" 
                           aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div style="min-width: 40px; text-align: right;">
                      <span class="fw-bold"><?= $count ?></span>
                    </div>
                  </div>
                </div>
              <?php endfor; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Subjects Section -->
      <div class="mt-5">
        <h3 class="mb-4">Subjects & Topics</h3>

        <?php if (!empty($subjects)): ?>
          <div class="row">
            <?php foreach ($subjects as $subject): ?>
              <div class="col-md-6 mb-4">
                <div class="subject-card p-4 border rounded" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);">
                  <h5 class="mb-3 fw-bold"><?= htmlspecialchars($subject['subject_name']) ?></h5>
                  <div class="topics-list">
                    <?php 
                    $topics = array_map('trim', explode(',', $subject['topics']));
                    foreach ($topics as $topic): 
                      if ($topic !== ''):
                    ?>
                      <div class="topic-item mb-2 p-2 bg-white rounded">
                        <small><?= htmlspecialchars($topic) ?></small>
                      </div>
                    <?php 
                      endif;
                    endforeach; 
                    ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-muted">No subjects added yet.</p>
        <?php endif; ?>
      </div>

      <!-- Back Button -->
      <div class="mt-5">
        <a href="<?= $isOwnProfile ? 'tutorDashboard.php' : 'javascript:history.back()' ?>" class="btn btn-secondary">
          ← Back
        </a>
      </div>

    </div>
  </main>
</div>

<style>
.profile-section {
  border-left: 4px solid #007bff !important;
}

.rating-card {
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  transition: transform 0.3s;
}

.rating-card:hover {
  transform: translateY(-5px);
}

.subject-card {
  transition: transform 0.3s, box-shadow 0.3s;
}

.subject-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

.topic-item {
  border-left: 3px solid #007bff;
  padding-left: 12px !important;
}
</style>

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
