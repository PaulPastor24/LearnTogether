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
          WHERE r.learner_id = ? AND r.status = 'Confirmed'
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
  <style>
    .subject-card {
      width: 250px; /* Adjusted to make cards smaller */
    }
    .subject-actions {
      text-align: left; /* Move button to the left */
    }
  </style>
</head>
<body>
  <div class="app">
    <aside id="sidebar">
      <div class="sidebar">

        <div class="profile-dropdown" id="profileDropdown" style="cursor:pointer;">
          <div class="avatar"><?= strtoupper($user['first_name'][0]) ?></div>
          <div>
            <div style="font-weight:700"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
            <div style="font-size:13px;color:var(--muted)">Active <?= htmlspecialchars(ucfirst($user['role'])) ?></div>
          </div>
        </div> 

        <nav class="navlinks">
          <a href="learnerDashboard.php">🏠 Overview</a>
          <a class="active" href="subjects.php">📚 My Subjects</a>
          <a href="searchTutors.php">🔎 Find Tutors</a>
          <a href="schedule.php">📅 My Schedule</a>
          <a href="requests.php">✉️ Requests</a>
          <a href="../logout.php">🚪 Logout</a>
        </nav>

      </div>
    </aside>

    <div class="overlay" id="overlay"></div>

    <div class="nav" role="navigation">
      <div class="hamburger" id="hamburger">
        <span></span>
        <span></span>
        <span></span>
      </div>
      <div class="logo" style="display:flex; align-items:center;">
        <div>
          <img src="../images/LT.png" alt="LearnTogether Logo" style="width:50px; height:40px;">
        </div>
        <div style="font-weight:700; margin-left:8px;">LearnTogether</div>
      </div>
      <div class="search">
        <input id="searchInput" placeholder="Search my subjects..." />
      </div>

      <div class="nav-actions">
        <div style="display:flex;align-items:center;gap:8px">
          <div style="text-align:right;margin-right:6px">
            <div style="font-weight:700"><?= htmlspecialchars($user['first_name']) ?></div>
            <div style="font-size:12px;color:var(--muted)"><?= htmlspecialchars(ucfirst($user['role'])) ?></div>
          </div>
          <div class="avatar" style="width:40px;height:40px;border-radius:10px">
            <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
          </div>
        </div>
      </div>
    </div>

    <main>
      <h1>My Subjects</h1>

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
              <div class="subject-actions" style="margin-top: 10px;">
                <a href="learnerTopics.php?reservation_id=<?= $res['id'] ?>" class="btn" style="display: inline-block; padding: 8px 16px; background-color: #28a745; color: white; text-decoration: none; border-radius: 4px;">View</a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p style="color:gray;">You have no reservations yet.</p>
        <?php endif; ?>
      </div>
    </main>
  </div>

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

    const searchInput = document.getElementById("searchInput");
    const cards = document.querySelectorAll(".subject-card");

    searchInput.addEventListener("input", () => {
      const term = searchInput.value.toLowerCase();
      cards.forEach(card => {
        const title = card.querySelector(".subject-title").textContent.toLowerCase();
        card.style.display = title.includes(term) ? "block" : "none";
      });
    });
  </script>
</body>
</html>
