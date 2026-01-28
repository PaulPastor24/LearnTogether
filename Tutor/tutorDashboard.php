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

    function adjustBrightness($color, $percent) {
        $color = str_replace('#', '', $color);
        $rgb = [
            'r' => hexdec(substr($color, 0, 2)),
            'g' => hexdec(substr($color, 2, 2)),
            'b' => hexdec(substr($color, 4, 2))
        ];
        
        foreach ($rgb as $key => $value) {
            $rgb[$key] = max(0, min(255, $value + ($value * $percent / 100)));
        }
        
        return '#' . dechex($rgb['r']) . dechex($rgb['g']) . dechex($rgb['b']);
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
            r.subject
        FROM reservations r
        JOIN learners l ON r.learner_id = l.id
        JOIN users u ON l.user_id = u.id
        WHERE r.tutor_id = ?
          AND r.status = 'Scheduled'
        ORDER BY u.first_name ASC, r.subject ASC
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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/req.css">
  <link rel="stylesheet" href="../CSS/button.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="app">
    <aside id="sidebar">
      <div class="sidebar">
        <div class="profile" id="sidebarProfile" title="View Profile">
          <div class="avatar"><?= strtoupper($tutor['first_name'][0] ?? 'T') ?></div>
          <div class="profile-text">
            <div class="profile-name"><?= htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']) ?></div>
            <div class="profile-status">Active Tutor</div>
          </div>
          <div class="view-profile-tooltip">View Profile</div>
        </div>
        <nav class="navlinks">
          <a class="nav-link active" href="tutorDashboard.php">
            <span class="nav-text">Overview</span>
          </a>
          <a class="nav-link" href="subjects.php">
            <span class="nav-text">My Subjects</span>
          </a>
          <a class="nav-link" href="calendar.php">
            <span class="nav-text">My Schedule</span>
          </a>
          <a class="nav-link" href="requests.php">
            <span class="nav-text">Requests</span>
          </a>
          <a class="nav-link" href="settings.php">
            <span class="nav-text">Settings</span>
          </a>
          <a class="nav-link logout" href="../logout.php">
            <span class="nav-text">Log Out</span>
          </a>
        </nav>
      </div>
    </aside>

    <div class="overlay" id="overlay"></div>

    <nav class="navbar-top">
      <button class="menu-toggle" id="hamburger">☰</button>
      <div class="navbar-brand-section">
        <div class="navbar-logo">
          <img src="../images/LT.png" alt="LearnTogether Logo" class="logo-img">
          <span class="brand-name">LearnTogether</span>
        </div>
      </div>
      <div class="navbar-search">
        <input type="text" id="searchInput" placeholder="Search learners or subjects..." class="search-input" 
               style="border: 2px solid rgba(16, 185, 129, 0.2); background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(52, 211, 153, 0.05) 100%); transition: all 0.3s;">
      </div>
      <div class="navbar-user">
        <div class="user-info">
          <span class="user-name"><?= htmlspecialchars($tutor['first_name'] ?? 'Tutor') ?></span>
          <span class="user-role">Tutor</span>
        </div>
        <div class="user-avatar">
          <?= strtoupper(substr($tutor['first_name'], 0, 1) . substr($tutor['last_name'], 0, 1)) ?>
        </div>
      </div>
    </nav>

    <main class="dashboard-main">
      <h1 class="welcome-title">Welcome back, <?= htmlspecialchars($tutor['first_name']) ?> 👋</h1>
      <p class="welcome-subtitle">Connect, Learn, and grow</p>
      <?php if (!empty($learners)): ?>
        <div class="learners-grid">
          <?php foreach ($learners as $l): 
            $colors = [
              'Mathematics' => '#10B981',
              'Science' => '#3B82F6',
              'English' => '#8B5CF6',
              'History' => '#F59E0B',
              'Art' => '#EC4899',
              'Music' => '#06B6D4',
              'Physics' => '#14B8A6',
              'Chemistry' => '#F97316',
              'Biology' => '#84CC16',
              'Literature' => '#6366F1',
            ];
            $subject = $l['subject'];
            $bgColor = $colors[$subject] ?? '#0F766E';
          ?>
          <div class="learner-card">
              <div class="learner-avatar" style="background: linear-gradient(135deg, <?= $bgColor ?>, <?= adjustBrightness($bgColor, -20) ?>);">
                  <?= strtoupper($l['first_name'][0] . $l['last_name'][0]) ?>
              </div>
              <div class="learner-info">
                  <div class="learner-name">
                      <?= htmlspecialchars($l['first_name'] . ' ' . $l['last_name']) ?>
                  </div>
                  <div class="learner-subject" style="color: <?= $bgColor ?>; font-weight: 600;">
                      <?= htmlspecialchars($subject) ?>
                  </div>
                  <a href="learnerTopics.php?learner_id=<?= $l['learner_id'] ?>&subject=<?= urlencode($subject) ?>" class="view-btn btn btn-success mt-2">View</a>
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
    document.addEventListener('contextmenu', event => event.preventDefault());
    document.onkeydown = function(e) {
        if (e.keyCode == 123 || 
            (e.ctrlKey && e.shiftKey && ['I','J','C'].includes(e.key.toUpperCase())) ||
            (e.ctrlKey && e.key.toUpperCase() == 'U')) {
            return false;
        }
    };
  </script>
  <script>
    const hamburger = document.getElementById('hamburger');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const profile = document.getElementById('profileDropdown');
    const dropdown = document.getElementById('dropdownMenu');

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

    profile.addEventListener('click', () => {
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    });

    document.addEventListener('click', (e) => {
        if (!profile.contains(e.target)) dropdown.style.display = 'none';
    });
  </script>

<script src="../JS/dashboardSearch.js"></script>
</body>
</html>
