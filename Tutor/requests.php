<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /LearnTogether/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT t.id AS tutor_id, u.first_name, u.last_name
    FROM tutors t
    JOIN users u ON t.user_id = u.id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$tutor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tutor) {
    header("Location: ../roleSelector.php");
    exit;
}

$tutor_id = (int)$tutor['tutor_id'];

if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $stmt = $pdo->prepare("UPDATE reservations SET status = 'Confirmed' WHERE id = ? AND tutor_id = ?");
    $stmt->execute([$id, $tutor_id]);
    header("Location: requests.php");
    exit;
}

if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    $stmt = $pdo->prepare("UPDATE reservations SET status = 'Rejected' WHERE id = ? AND tutor_id = ?");
    $stmt->execute([$id, $tutor_id]);
    header("Location: requests.php");
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = ? AND tutor_id = ?");
    $stmt->execute([$id, $tutor_id]);
    header("Location: requests.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        r.id AS reservation_id,
        r.subject,
        r.date AS session_date,
        r.time AS session_time,
        r.status,
        l.id AS learner_id,
        u.first_name AS learner_first_name,
        u.last_name AS learner_last_name
    FROM reservations r
    LEFT JOIN tutors t ON r.tutor_id = t.id
    LEFT JOIN learners l ON r.learner_id = l.id
    LEFT JOIN users u ON l.user_id = u.id
    WHERE t.user_id = ?
    ORDER BY r.date DESC, r.time DESC
");
$stmt->execute([$user_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$tutor_first = htmlspecialchars($tutor['first_name']);
$tutor_last = htmlspecialchars($tutor['last_name']);
$avatar_initial = strtoupper($tutor['first_name'][0] . $tutor['last_name'][0]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tutor Requests — LearnTogether</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/req.css"> 
  <link rel="stylesheet" href="../CSS/request.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
</head>
<body>
  <div class="app">
    <aside id="sidebar">
      <div class="sidebar">
        <div class="profile" id="sidebarProfile" title="View Profile">
          <div class="avatar"><?= strtoupper($tutor['first_name'][0]) ?></div>
          <div class="profile-text">
            <div class="profile-name"><?= htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']) ?></div>
            <div class="profile-status">Active Tutor</div>
          </div>
          <div class="view-profile-tooltip">View Profile</div>
        </div>
        <nav class="navlinks">
          <a class="nav-link" href="tutorDashboard.php">
            <span class="nav-text">Overview</span>
          </a>
          <a class="nav-link" href="subjects.php">
            <span class="nav-text">My Subjects</span>
          </a>
          <a class="nav-link" href="calendar.php">
            <span class="nav-text">My Schedule</span>
          </a>
          <a class="nav-link active" href="requests.php">
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
        <input type="text" placeholder="Search requests..." class="search-input">
      </div>
      <div class="navbar-user">
        <div class="user-info">
          <span class="user-name"><?= htmlspecialchars($tutor['first_name']) ?></span>
          <span class="user-role">Tutor</span>
        </div>
        <div class="user-avatar">
          <?= strtoupper(substr($tutor['first_name'], 0, 1) . substr($tutor['last_name'], 0, 1)) ?>
        </div>
      </div>
    </nav>

    <main class="lt-main mb-4">
    <div class="content-wrap">
      <h1 class="page-title" style="font-weight:800">Session Requests</h1>

      <div class="card content-card">
        <div class="card-body p-0">
          <?php if (empty($requests)): ?>
            <div class="p-4 text-muted">No session requests yet.</div>
          <?php else: ?>
            <div class="table-responsive p-3">
              <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Learner</th>
                    <th>Subject</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th class="text-center">Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($requests as $req): 
                    $status = $req['status'];
                    $color = $status === 'Pending' ? '#f59e0b'
                          : ($status === 'Confirmed' ? '#10b981'
                          : ($status === 'Rejected' ? '#ef4444' : '#6b7280'));
                  ?>
                  <tr>
                    <td><?= htmlspecialchars($req['learner_first_name'].' '.$req['learner_last_name'] ?? 'Unknown') ?></td>
                    <td><?= htmlspecialchars($req['subject']) ?></td>
                    <td><?= htmlspecialchars($req['session_date']) ?></td>
                    <td><?= htmlspecialchars($req['session_time']) ?></td>
                    <td class="text-center">
                      <span class="fw-semibold" style="color:<?= $color ?>;display:inline-block;min-width:72px;">
                        <?= htmlspecialchars($status) ?>
                      </span>
                    </td>
                    <td>
                      <?php if ($status === 'Pending'): ?>
                        <a href="requests.php?approve=<?= $req['reservation_id'] ?>" class="btn btn-success btn-sm">Approve</a>
                        <a href="requests.php?reject=<?= $req['reservation_id'] ?>" class="btn btn-danger btn-sm ms-1">Reject</a>
                      <?php elseif ($status === 'Confirmed'): ?>
                        <button onclick="window.open('../agoraconvo.php?reservation_id=<?= $req['reservation_id'] ?>', '_blank')" class="btn btn-primary btn-sm">View</button>
                      <?php elseif ($status === 'Rejected'): ?>
                        <a href="requests.php?delete=<?= $req['reservation_id'] ?>"
                          onclick="return confirm('Delete this rejected request?');"
                          class="btn btn-outline-danger btn-sm">
                          Delete
                        </a>
                      <?php else: ?>
                        <span class="text-muted">N/A</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../JS/dashboardSearch.js"></script>
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
</body>
</html>
