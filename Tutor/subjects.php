<?php
session_start();
require '../db.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tutor Subjects - LearnTogether</title>
      <link rel="stylesheet" href="../CSS/req.css">
      <link rel="stylesheet" href="../CSS/subjects.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="app">
    <aside id="sidebar">
      <div class="sidebar">
        <div class="profile">
          <div class="avatar">
            <?= strtoupper($tutor['first_name'][0]) ?>
          </div>
          <div>
            <div style="font-weight:750"><?= htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']) ?></div>
            <div style="font-size:13px;color:var(--muted)">Active Tutor</div>
          </div>
        </div>

        <nav class="navlinks fw-bold" style="margin-top:12px;">
          <a href="tutorDashboard.php">🏠 Overview</a>
          <a class="active" href="subjects.php">📚 Subjects</a>
          <a href="calendar.php">📅 Schedule</a>
          <a href="requests.php">✉️ Requests</a>
          <a href="settings.php">⚙️ Settings</a>
          <a href="../logout.php">🚪 Logout</a>
        </nav>
      </div>
    </aside>

    <div class="overlay" id="overlay"></div>

    <div class="nav" style="height:85px;">
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

    // Show hamburger on screens 940px and smaller
    function checkScreenSize() {
        if (window.innerWidth <= 940) {
            hamburger.style.display = 'block';
        } else {
            hamburger.style.display = 'none';
        }
    }
    window.addEventListener('resize', checkScreenSize);
    checkScreenSize(); // Initial check
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../JS/dashboardSearch.js"></script>
</body>
</html>
