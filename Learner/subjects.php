<?php
  session_start();
  require '../db.php';

  if (!isset($_SESSION['user_id'])) {
      header("Location: /LearnTogether/login.php");
      exit;
  }

  $user_id = $_SESSION['user_id'];

  $stmt = $pdo->prepare("SELECT first_name, last_name, role FROM users WHERE id = ?");
  $stmt->execute([$user_id]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  $learner_stmt = $pdo->prepare("SELECT id FROM learners WHERE user_id = ?");
  $learner_stmt->execute([$user_id]);
  $learner = $learner_stmt->fetch(PDO::FETCH_ASSOC);
  $learner_id = $learner['id'] ?? null;

  $reservations = [];
  if ($learner_id) {
      $res_stmt = $pdo->prepare("
          SELECT r.*, u.first_name AS tutor_first, u.last_name AS tutor_last
          FROM reservations r
          JOIN tutors t ON r.tutor_id = t.id
          JOIN users u ON t.user_id = u.id
          WHERE r.learner_id = ? AND r.status = 'Scheduled'
          ORDER BY r.created_at DESC
      ");
      $res_stmt->execute([$learner_id]);
      $reservations = $res_stmt->fetchAll(PDO::FETCH_ASSOC);
  }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>My Subjects — LearnTogether</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/style2.css">
  <link rel="stylesheet" href="../CSS/search.css">
</head>
<body style="overflow-x: hidden;">
  <div class="app">
    <nav class="navbar-top">
      <button class="menu-toggle" id="hamburger">☰</button>
      <div class="navbar-brand-section">
        <div class="navbar-logo">
          <img src="../images/LT.png" alt="LearnTogether Logo" class="logo-img">
          <span class="brand-name">LearnTogether</span>
        </div>
      </div>
      <div class="navbar-search">
        <input type="text" id="searchInput" placeholder="Search my subjects..." class="search-input">
      </div>
      <div class="navbar-user">
        <div class="user-info">
          <span class="user-name"><?= htmlspecialchars($user['first_name'] ?? 'Learner') ?></span>
          <span class="user-role"><?= $user['role'] == 'tutor' ? 'Tutor' : 'Learner' ?></span>
        </div>
        <div class="user-avatar">
          <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
        </div>
      </div>
    </nav>

    <aside id="sidebar">
      <div class="sidebar">
        <div class="profile" id="sidebarProfile" title="View Profile">
          <div class="avatar">
            <?= strtoupper($user['first_name'][0]) ?>
          </div>
          <div class="profile-text">
            <div class="profile-name"><?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?></div>
            <div class="profile-status"><?= $user['role'] == 'tutor' ? 'Active Tutor' : 'Active Learner' ?></div>
          </div>
          <div class="view-profile-tooltip">View Profile</div>
        </div>

        <nav class="navlinks">
          <a class="nav-link" href="learnerDashboard.php">
            <span class="nav-text">Overview</span>
          </a>
          <a class="nav-link active" href="subjects.php">
            <span class="nav-text">My Subjects</span>
          </a>
          <a class="nav-link" href="searchTutors.php">
            <span class="nav-text">Find Tutors</span>
          </a>
          <a class="nav-link" href="schedule.php">
            <span class="nav-text">My Schedule</span>
          </a>
          <a class="nav-link" href="requests.php">
            <span class="nav-text">Requests</span>
          </a>
          <a class="nav-link" href="setting.php">
            <span class="nav-text">Settings</span>
          </a>
          <a class="nav-link logout" href="../logout.php">
            <span class="nav-text">Log Out</span>
          </a>
        </nav>
      </div>
    </aside>

    <div class="overlay" id="overlay"></div>

    <main class="dashboard-main">
      <h1 class="welcome-title">My Subjects</h1>
      <p class="welcome-subtitle">Manage your learning subjects and tutors</p>

      <div class="subjects-grid" id="subjectsGrid">
        <?php if (!empty($reservations)): ?>
          <?php foreach ($reservations as $res): ?>
            <div class="subject-card">
              <div class="subject-header">
                <div class="subject-title"><?= htmlspecialchars($res['subject']) ?></div>
              </div>
              <div class="subject-desc">
                Reserved with Tutor: <?= htmlspecialchars($res['tutor_first'] . ' ' . $res['tutor_last']) ?>
              </div>
              <div class="subject-actions">
                <a href="learnerTopics.php?reservation_id=<?= $res['id'] ?>" class="view-btn">View</a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p style="color:gray; grid-column: 1/-1;">You have no reservations yet.</p>
        <?php endif; ?>
      </div>
      <p id="noResults" style="display: none;">No subjects found matching your search.</p>
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

    function debounce(func, delay) {
      let timeout;
      return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), delay);
      };
    }

    document.addEventListener('DOMContentLoaded', () => {
      const searchInput = document.getElementById('searchInput');
      const searchFilter = document.getElementById('searchFilter');
      const clearSearch = document.getElementById('clearSearch');
      const noResults = document.getElementById('noResults');
      if (!searchInput) return;

      const cards = document.querySelectorAll('.subject-card');

      const filterSubjects = () => {
        const query = searchInput.value.toLowerCase();
        const filter = searchFilter.value;
        let hasVisible = false;

        cards.forEach(card => {
          const subject = card.querySelector('.subject-title')?.textContent.toLowerCase() || '';
          const tutor = card.querySelector('.subject-desc')?.textContent.toLowerCase() || '';

          let show = false;
          if (filter === 'all') {
            show = subject.includes(query) || tutor.includes(query);
          } else if (filter === 'subject') {
            show = subject.includes(query);
          } else if (filter === 'tutor') {
            show = tutor.includes(query);
          }

          card.style.display = show ? '' : 'none';
          if (show) hasVisible = true;
        });

        noResults.style.display = hasVisible ? 'none' : 'block';
      };

      const debouncedFilter = debounce(filterSubjects, 300);
      searchInput.addEventListener('input', debouncedFilter);
      searchFilter.addEventListener('change', filterSubjects);

      clearSearch.addEventListener('click', () => {
        searchInput.value = '';
        searchFilter.value = 'all';
        filterSubjects();
      });
    });
  </script>
</body>
</html>
