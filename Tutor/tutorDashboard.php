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

    // Helper function to adjust color brightness
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
+  <link rel="stylesheet" href="../CSS/req.css">
  <link rel="stylesheet" href="../CSS/button.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="app">
    <aside id="sidebar">
      <div class="sidebar" style="width: 230px; height: 420px;">
        <div class="profile" id="sidebarProfile" style="cursor: pointer; position: relative; border-radius: 8px; padding: 10px; transition: all 0.3s ease;" title="View Profile">
          <div class="avatar">
            <?= isset($tutor['first_name'], $tutor['last_name']) ? strtoupper($tutor['first_name'][0]) : 'T' ?>
          </div>
          <div>
            <div style="font-weight:750"><?= htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']) ?></div>
            <div style="font-size:13px;color:var(--muted)">Active Tutor</div>
          </div>
          <div class="view-profile-tooltip" style="position: absolute; bottom: -35px; left: 50%; transform: translateX(-50%); background: #333; color: white; padding: 6px 12px; border-radius: 4px; font-size: 12px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; z-index: 10;">👤 View Profile</div>
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
          <?php foreach ($learners as $l): 
            // Generate color based on subject
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

  const sidebarProfile = document.getElementById('sidebarProfile');
  const tooltip = document.querySelector('.view-profile-tooltip');

  if (sidebarProfile) {
    sidebarProfile.addEventListener('mouseenter', function() {
      tooltip.style.opacity = '1';
    });

    sidebarProfile.addEventListener('mouseleave', function() {
      tooltip.style.opacity = '0';
    });

    sidebarProfile.addEventListener('click', function() {
      window.location.href = 'viewProfile.php';
    });
  }
</script>

<script src="../JS/dashboardSearch.js"></script>
</body>
</html>
