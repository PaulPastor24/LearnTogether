<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /LearnTogether/login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT first_name, last_name, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT subject, tutor_name, status, session_date, requested_date FROM requests WHERE user_id = ? ORDER BY requested_date DESC");
$stmt->execute([$user_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Requests — LearnTogether</title>
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
            </div>
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
                <div style="font-size:13px;color:var(--muted)">
                  Active <?= htmlspecialchars($displayRole) ?>
                </div>
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

        <nav class="navlinks">
          <a href="learnerDashboard.php">🏠 Overview</a>
          <a href="subjects.php">📚 My Subjects</a>
          <a href="searchTutors.php">🔎 Find Tutors</a>
          <a href="schedule.php">📅 My Schedule</a>
          <a class="active" href="requests.php">✉️ Requests</a>
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
        <input placeholder="Search tutors, subjects or topics" />
      </div>
      <div class="nav-actions">
        <button class="icon-btn">🔔</button>
        <button class="icon-btn">💬</button>
        <div style="display:flex;align-items:center;gap:8px">
          <div style="text-align:right;margin-right:6px">
            <div style="font-weight:700"><?php echo htmlspecialchars($user['first_name']); ?></div>
            <div style="font-size:12px;color:var(--muted)">Student</div>
          </div>
          <div class="avatar" style="width:40px;height:40px;border-radius:10px">
            <?php echo strtoupper(substr($user['first_name'], 0, 1)) . strtoupper(substr($user['last_name'], 0, 1)); ?>
          </div>
        </div>
      </div>
    </div>

<main>
  <h1>Requests</h1>
  <div class="subjects-grid">
    <?php if (count($requests) > 0): ?>
      <?php foreach ($requests as $req): ?>
        <?php
          $status = strtolower($req['status']);
          $icon = $status === 'confirmed' || $status === 'accepted' ? '✅' : ($status === 'pending' ? '⏳' : '❌');
          $color = $status === 'confirmed' || $status === 'accepted'
            ? 'linear-gradient(180deg,#22c55e,#15803d)'
            : ($status === 'pending'
              ? 'linear-gradient(180deg,#f59e0b,#d97706)'
              : 'linear-gradient(180deg,#ef4444,#b91c1c)');
        ?>
        <div class="subject-card">
          <div class="subject-header">
            <div class="icon" style="background: <?php echo $color; ?>"><?php echo $icon; ?></div>
            <div class="subject-title"><?php echo htmlspecialchars($req['subject']) . " - " . ucfirst($req['status']); ?></div>
          </div>
          <div class="subject-desc">Tutor: <?php echo htmlspecialchars($req['tutor_name']); ?></div>
          <div class="topics">
            <?php if (!empty($req['session_date'])): ?>
              <span class="topic">Session Date: <?php echo date("M d, Y", strtotime($req['session_date'])); ?></span>
            <?php else: ?>
              <span class="topic">Requested on <?php echo date("M d, Y", strtotime($req['requested_date'])); ?></span>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="color:gray">You have no requests yet.</p>
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
