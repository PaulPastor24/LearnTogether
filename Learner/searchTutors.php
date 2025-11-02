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
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
  SELECT 
    u.id, 
    u.first_name, 
    u.last_name, 
    t.bio,
    GROUP_CONCAT(ts.subject_name SEPARATOR ', ') AS subjects
  FROM users u
  LEFT JOIN tutors t ON u.id = t.user_id
  LEFT JOIN tutor_subjects ts ON u.id = ts.tutor_id
  WHERE u.role = 'tutor'
  GROUP BY u.id
");
$tutors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Find Tutors — LearnTogether</title>
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
          <div class="avatar">
            <?= strtoupper($currentUser['first_name'][0]) ?>
          </div>
          <div>
            <div style="font-weight:700">
              <?= htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?>
            </div>
            <?php
              $displayRole = '';
              if (strtolower($currentUser['role']) === 'learner') {
                $displayRole = 'Learner';
              } elseif (strtolower($currentUser['role']) === 'tutor') {
                $displayRole = 'Tutor';
              } else {
                $displayRole = ucfirst($currentUser['role']);
              }
            ?>
            <div style="font-size:13px;color:var(--muted)">
              Active <?= htmlspecialchars($displayRole) ?>
            </div>

          </div>

          <div class="dropdown-menu" id="dropdownMenu"
              style="display:none;position:absolute;top:60px;left:0;background:white;
                      border:1px solid #ddd;border-radius:8px;
                      box-shadow:0 4px 10px rgba(0,0,0,0.1);
                      min-width:180px;z-index:999;">
            <a href="profile.php"
              style="display:block;padding:10px 15px;text-decoration:none;
                      color:#333;font-size:14px;">🧑‍💻 View Profile</a>
            <a href="settings.php"
              style="display:block;padding:10px 15px;text-decoration:none;
                      color:#333;font-size:14px;">⚙️ Settings</a>
            <hr style="margin:5px 0;border:none;border-top:1px solid #eee;">
            <a href="../logout.php"
              style="display:block;padding:10px 15px;text-decoration:none;
                      color:#333;font-size:14px;">🚪 Logout</a>
          </div>
        </div>

        <nav class="navlinks">
          <a href="learnerDashboard.php">🏠 Overview</a>
          <a href="subjects.php">📚 My Subjects</a>
          <a class="active" href="searchTutors.php">🔎 Find Tutors</a>
          <a href="schedule.php">📅 My Schedule</a>
          <a href="requests.php">✉️ Requests</a>
          <a href="../logout.php">🚪 Logout</a>
        </nav>
      </div>
    </aside>

    <div class="nav" role="navigation">
      <div class="logo">
        <div class="mark">LT</div>
        <div style="font-weight:700">LearnTogether</div>
      </div>
      <div class="search">
        <input id="searchInput" placeholder="Search tutors, subjects or topics" />
      </div>
      <div class="nav-actions">
        <button class="icon-btn">🔔</button>
        <button class="icon-btn">💬</button>
        <div style="display:flex;align-items:center;gap:8px">
          <div style="text-align:right;margin-right:6px">
            <div style="font-weight:700"><?= htmlspecialchars($currentUser['first_name']) ?></div>
            <div style="font-size:12px;color:var(--muted)"><?= htmlspecialchars(ucfirst($currentUser['role'])) ?></div>
          </div>
          <div class="avatar" style="width:40px;height:40px;border-radius:10px">
            <?= strtoupper(substr($currentUser['first_name'], 0, 1)) . strtoupper(substr($currentUser['last_name'], 0, 1)) ?>
          </div>
        </div>
      </div>
    </div>

    <main>
      <h1>Find Tutors</h1>
      <div class="subjects-grid">
        <?php foreach ($tutors as $t): ?>
          <div class="subject-card">
            <div class="subject-header">
              <div class="icon" style="background: linear-gradient(180deg,#2563eb,#1e40af)">👩‍🏫</div>
              <div class="subject-title">
                <?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?>
              </div>
            </div>
            <div class="subject-desc">
              <?= htmlspecialchars($t['bio'] ?: 'No description available.') ?>
            </div>
            <div class="topics">
              <?php
                $subs = explode(',', $t['subjects'] ?? '');
                foreach ($subs as $s) {
                  $s = trim($s);
                  if ($s) echo "<span class='topic'>" . htmlspecialchars($s) . "</span>";
                }
              ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </main>
  </div>
    <script>
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
