<?php
session_start();
require '../db.php';
require '../security.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: /LearnTogether/login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT id AS tutor_id FROM tutors WHERE user_id = ?");
$stmt->execute([$user_id]);
$tutor_row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tutor_row) {
    header("Location: ../roleSelector.php");
    exit;
}

$tutor_id = $tutor_row['tutor_id'];

$stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$tutor = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Security validation failed. Please try again.";
    } else {
        $subject_name = trim($_POST['subject_name']);
        $description = trim($_POST['description']);
        $topics = isset($_POST['topics']) ? implode(",", array_filter($_POST['topics'])) : "";

        if ($_POST['action'] === 'add') {
            $stmt = $pdo->prepare("INSERT INTO tutor_subjects (tutor_id, subject_name, description, topics) VALUES (?, ?, ?, ?)");
            $stmt->execute([$tutor_id, $subject_name, $description, $topics]);
            header("Location: subjects.php");
            exit;
        }

        if ($_POST['action'] === 'edit') {
            $stmt = $pdo->prepare("UPDATE tutor_subjects SET subject_name=?, description=?, topics=? WHERE id=? AND tutor_id=?");
            $stmt->execute([$subject_name, $description, $topics, $_POST['subject_id'], $tutor_id]);
            header("Location: subjects.php");
            exit;
        }
    }
}

if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM tutor_subjects WHERE id=? AND tutor_id=?");
    $stmt->execute([$_GET['delete_id'], $tutor_id]);
    header("Location: subjects.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT * 
    FROM tutor_subjects 
    WHERE tutor_id = ? 
    ORDER BY id DESC
");
$stmt->execute([$tutor_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tutor Subjects - LearnTogether</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/req.css">
  <link rel="stylesheet" href="../CSS/subjects.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
          <a class="nav-link active" href="subjects.php">
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
        <input type="text" placeholder="Search subjects..." class="search-input">
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

    <main class="lt-main mb-4">
      <h1 class="mb-4" style="font-weight:800">Manage Your Subjects</h1>
      <div class="subjects-grid">
        <?php foreach ($subjects as $sub): ?>
          <div>
              <div class="subject-card shadow-sm">

                  <h4 class="fw-bold"><?= htmlspecialchars($sub['subject_name']) ?></h4>

                  <p class="text-muted"><?= htmlspecialchars($sub['description']) ?></p>

                  <div>
                      <?php foreach (array_filter(explode(",", $sub['topics'])) as $t): ?>
                          <span class="topic-badge"><?= htmlspecialchars($t) ?></span>
                      <?php endforeach; ?>
                  </div>

                  <div class="d-flex justify-content-between mt-3">
                      <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#editModal<?= $sub['id'] ?>">Edit</button>
                      <a href="?delete_id=<?= $sub['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete subject?')">Delete</a>
                  </div>
              </div>
          </div>

          <div class="modal fade" id="editModal<?= $sub['id'] ?>">
              <div class="modal-dialog">
                  <div class="modal-content">
                      <form method="POST">
                          <input type="hidden" name="action" value="edit">
                          <input type="hidden" name="subject_id" value="<?= $sub['id'] ?>">
                          <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                          <div class="modal-header">
                              <h5 class="modal-title">Edit Subject</h5>
                              <button class="btn-close" data-bs-dismiss="modal"></button>
                          </div>

                          <div class="modal-body">
                              <label>Subject Name</label>
                              <input type="text" name="subject_name" class="form-control mb-3" value="<?= htmlspecialchars($sub['subject_name']) ?>">

                              <label>Description</label>
                              <textarea name="description" class="form-control mb-3"><?= htmlspecialchars($sub['description']) ?></textarea>

                              <label>Topics</label>
                              <div id="edit-topics-<?= $sub['id'] ?>">
                                  <?php foreach (array_filter(explode(",", $sub['topics'])) as $t): ?>
                                      <div class="topic-input-row">
                                          <input type="text" name="topics[]" class="form-control topic-field" value="<?= htmlspecialchars($t) ?>">
                                      </div>
                                  <?php endforeach; ?>
                              </div>

                              <button type="button" class="btn btn-success" onclick="addEditTopic(<?= $sub['id'] ?>)">+</button>
                          </div>

                          <div class="modal-footer">
                              <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                              <button class="btn btn-primary">Save Changes</button>
                          </div>

                      </form>
                  </div>
              </div>
          </div>
          <?php endforeach; ?>

          <div>
              <div class="subject-card shadow-sm d-flex align-items-center justify-content-center"
                  style="cursor:pointer;"
                  data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                  <div class="text-center">
                      <div style="font-size:40px; font-weight:bold; color:#0d6efd;">＋</div>
                      <div class="fw-bold mt-2">Add Subjects</div>
                  </div>
              </div>
          </div>
      </div>
    </main>
  </div>

  <div class="modal fade" id="addSubjectModal">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST">
          <input type="hidden" name="action" value="add">

          <div class="modal-header">
            <h5 class="modal-title">Add Subject</h5>
            <button class="btn-close" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <label>Subject Name</label>
            <input type="text" name="subject_name" class="form-control mb-3" required>

            <label>Description</label>
            <textarea name="description" class="form-control mb-3"></textarea>

            <label>Topics</label>
            <div id="topic-list">
              <div class="topic-input-row input-group mb-2">
                <input type="text" name="topics[]" class="form-control topic-field" placeholder="Add topic">
                <button type="button" class="btn btn-success" onclick="addTopicField()">+</button>
              </div>
            </div>

          </div>

          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button class="btn btn-primary">Add Subject</button>
          </div>

        </form>
      </div>
    </div>
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
    function addTopicField() {
        const list = document.getElementById("topic-list");

        const div = document.createElement("div");
        div.className = "topic-input-row input-group mb-2";

        div.innerHTML = `
            <input type="text" name="topics[]" class="form-control topic-field" placeholder="Add topic">
            <button type="button" class="btn btn-success" onclick="addTopicField()">+</button>
        `;

        list.appendChild(div);
    }

    function addEditTopic(id) {
        const list = document.getElementById("edit-topics-" + id);
        const div = document.createElement("div");
        div.className = "topic-input-row";
        div.innerHTML = `<input type="text" name="topics[]" class="form-control topic-field" placeholder="Add topic">`;
        list.appendChild(div);
    }

    window.addEventListener('scroll', function() {
        const h1 = document.querySelector('.lt-main h1');
        if (window.scrollY > 50) { 
            h1.style.opacity = '0';
        } else {
            h1.style.opacity = '1';
        }
    });

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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../JS/dashboardSearch.js"></script>
</body>
</html>
