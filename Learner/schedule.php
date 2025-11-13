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

  $stmt = $pdo->prepare("
    SELECT subject, tutor_name, session_date, session_time_start, session_time_end
    FROM requests
    WHERE user_id = ? AND status = 'Confirmed'
    ORDER BY session_date ASC
  ");
  $stmt->execute([$user_id]);
  $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>My Schedule — LearnTogether</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/style2.css">
  <link rel="stylesheet" href="../CSS/navbar.css">
</head>
<body>
  <div class="app">
    <aside>
      <div class="sidebar">
        <div class="profile-dropdown" id="profileDropdown" 
            style="position:relative;cursor:pointer;">
          <div class="avatar"><?= strtoupper($user['first_name'][0]) ?></div>
          <div>
            <div style="font-weight:700">
              <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
            <div>
              <?php
                $displayRole = '';
                if (strtolower($user['role']) === 'learner') {
                  $displayRole = 'Learner';
                } elseif (strtolower($user['role']) === 'tutor') {
                  $displayRole = 'Tutor';
                } else {
                  $displayRole = ucfirst($user['role']);
                }
              ?>
                <div style="font-size:13px;color:var(--muted)">Active <?= htmlspecialchars(ucfirst($user['role'] === 'tutor' ? 'learner' : $user['role'])) ?></div>
            </div>
          </div>  
          
        <nav class="navlinks">
          <a href="learnerDashboard.php">🏠 Overview</a>
          <a href="subjects.php">📚 My Subjects</a>
          <a href="searchTutors.php">🔎 Find Tutors</a>
          <a class="active" href="schedule.php">📅 My Schedule</a>
          <a href="requests.php">✉️ Requests</a>
          <a href="../logout.php">🚪 Logout</a>
        </nav>
      </div>
    </aside>

    <div class="nav" role="navigation">
      <div class="logo"><div class="mark">LT</div><div style="font-weight:700">LearnTogether</div></div>
      <div class="search"><input placeholder="Search tutors, subjects or topics" /></div>
      <div class="nav-actions">
        <button class="icon-btn">🔔</button>
        <button class="icon-btn">💬</button>
        <div style="display:flex;align-items:center;gap:8px">
          <div style="text-align:right;margin-right:6px">
            <div style="font-weight:700"><?= htmlspecialchars($user['first_name']) ?></div>
            <div style="font-size:12px;color:var(--muted)">Student</div>
          </div>
          <div class="avatar" style="width:40px;height:40px;border-radius:10px">
            <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
          </div>
        </div>
      </div>
    </div>

  <main>
    <h1>My Schedule</h1>
    <div class="subjects-grid">
      <?php if (count($sessions) > 0): ?>
        <?php foreach ($sessions as $s): ?>
          <div class="subject-card">
            <div class="subject-header">
              <div class="icon" style="background: linear-gradient(180deg,#4f46e5,#4338ca)">📅</div>
              <div class="subject-title"><?= htmlspecialchars($s['subject']) ?></div>
            </div>
            <div class="subject-desc">Session with Tutor: <?= htmlspecialchars($s['tutor_name']) ?></div>
            <div class="topics">
              <span class="topic">Date: <?= date("M d, Y", strtotime($s['session_date'])) ?></span>
              <span class="topic">
                Time: <?= htmlspecialchars($s['session_time_start']) ?> - <?= htmlspecialchars($s['session_time_end']) ?>
              </span>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="color:#666;">You have no confirmed sessions yet.</p>
      <?php endif; ?>
    </div>
  </main>

  </div>

  <script>
    document.querySelectorAll('.navlinks a').forEach(a => {
      a.addEventListener('click', () => {
        document.querySelectorAll('.navlinks a').forEach(x => x.classList.remove('active'));
        a.classList.add('active');
      });
    });

    const profile = document.getElementById('profileDropdown');
    const dropdown = document.getElementById('dropdownMenu');

    profile.addEventListener('click', () => {
      dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    });

    document.addEventListener('click', (e) => {
      if (!profile.contains(e.target)) {
        dropdown.style.display = 'none';
      }
    });
  </script>
</body>
</html>
