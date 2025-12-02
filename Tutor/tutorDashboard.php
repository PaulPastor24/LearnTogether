<?php
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: 0");
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
            l.id AS learner_id,
            u.first_name,
            u.last_name,
            GROUP_CONCAT(DISTINCT r.subject SEPARATOR ', ') AS subjects
        FROM reservations r
        JOIN learners l ON r.learner_id = l.id
        JOIN users u ON l.user_id = u.id
        WHERE r.tutor_id = ?
          AND r.status = 'Scheduled'
        GROUP BY l.id, u.first_name, u.last_name
        ORDER BY u.first_name ASC
    ");
    $stmt->execute([$tutor_id]);

    $learners = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tutor Dashboard - LearnTogether</title>
  <link rel="stylesheet" href="../CSS/style2.css">
  <link rel="stylesheet" href="../CSS/req.css">
  <link rel="stylesheet" href="../CSS/button.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="app">
    <aside id="sidebar">
      <div class="sidebar" style="width: 230px; height: 400px;">
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
          <a href="calendar.php">📅 Schedule</a>
          <a href="requests.php">✉️ Requests</a>
          <a href="settings.php">⚙️ Settings</a>
          <a href="../logout.php">🚪 Logout</a>
        </nav>
      </div>
    </aside>

    <div class="overlay" id="overlay"></div>

    <div class="nav" style="height: 85px;">
      <button class="menu-toggle" id="hamburger">☰</button>
      <div class="logo" style="display:flex; align-items:center;">
          <div>
              <img src="../images/LT.png" alt="LearnTogether Logo" style="width:50px; height:40px; margin-left:20px;">
          </div>
          <div style="font-weight:700; margin-left:8px;">LearnTogether</div>
      </div>

      <div class="nav-actions" style="display: flex; align-items: center; gap: 12px; margin-left: auto;"> 
        <div style="display:flex;align-items:center;gap:8px;">
          <div class="profile-info">
            <div><?= htmlspecialchars($tutor['first_name'] ?? 'Tutor') ?></div>
            <div>Tutor</div>
          </div>
          <div class="avatar">
            <?= strtoupper($tutor['first_name'][0] . $tutor['last_name'][0]) ?>
          </div>
        </div>
      </div>
    </div>

    <main class="lt-main mb-4">
      <h1 style="font-weight:800">Welcome back, <?= htmlspecialchars($tutor['first_name']) ?> 👋</h1>
      <p>Connect, Learn, and grow</p>
      <?php if (!empty($learners)): ?>
        <div class="learners-grid">
          <?php foreach ($learners as $l): ?>
          <div class="learner-card">
              <div class="learner-avatar">
                  <?= strtoupper($l['first_name'][0] . $l['last_name'][0]) ?>
              </div>
              <div class="learner-info">
                  <div class="learner-name">
                      <?= htmlspecialchars($l['first_name'] . ' ' . $l['last_name']) ?>
                  </div>
                  <div class="learner-subject">
                      <?= htmlspecialchars($l['subjects']) ?>
                  </div>
                  <a href="learnerTopics.php?learner_id=<?= $l['learner_id'] ?>" class="view-btn btn btn-success mt-2">View</a>
              </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p>No learners found yet.</p>
      <?php endif; ?>
    </main>

  </div>

<script>
  const hamburger = document.getElementById('hamburger');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('overlay');

  hamburger.addEventListener('click', () => {
      hamburger.classList.toggle('open');
      sidebar.classList.toggle('show');
      overlay.classList.toggle('show');
  });

  overlay.addEventListener('click', () => {
      hamburger.classList.remove('open');
      sidebar.classList.remove('show');
      overlay.classList.remove('show');
  });

  function checkScreenSize() {
      if (window.innerWidth <= 940) {
          hamburger.style.display = 'block';
      } else {
          hamburger.style.display = 'none';
      }
  }
  window.addEventListener('resize', checkScreenSize);
  checkScreenSize();
</script>

<script src="../JS/dashboardSearch.js"></script>
</body>
</html>
