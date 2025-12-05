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

    $stmt = $pdo->prepare("SELECT id FROM learners WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $learner = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$learner) {
        die("Learner profile not found.");
    }
    $learner_id = $learner['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_tutor'])) {
        $tutor_id = $_POST['tutor_id'] ?? null;
        $subject = $_POST['subject'] ?? null;

        if ($tutor_id && $subject) {
            $insert = $pdo->prepare("
                INSERT INTO reservations (learner_id, tutor_id, subject, status, date, time)
                VALUES (?, ?, ?, 'Pending', CURDATE(), CURTIME())
            ");
            try {
                $insert->execute([$learner_id, $tutor_id, $subject]);
                $success = "Request sent successfully!";
            } catch (PDOException $e) {
                $error = "Failed to send request. Please try again.";
            }
        } else {
            $error = "Failed to send request.";
        }
    }

    $stmt = $pdo->query("
        SELECT 
            u.id AS user_id,
            u.first_name,
            u.last_name,
            t.id AS tutor_id,
            t.expertise,
            t.bio,
            t.average_rating,
            t.total_ratings,
            COALESCE(GROUP_CONCAT(DISTINCT ts.subject_name SEPARATOR ', '), '') AS subjects
        FROM users u
        JOIN tutors t ON u.id = t.user_id
        LEFT JOIN tutor_subjects ts ON t.id = ts.tutor_id
        WHERE u.role = 'tutor'
        GROUP BY u.id, t.id, t.expertise, t.bio, t.average_rating, t.total_ratings
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
    <link rel="stylesheet" href="../CSS/tutor2.css">
    <link rel="stylesheet" href="../CSS/search.css">
</head>
<body>
    <div class="app">
        <aside id="sidebar">
            <div class="sidebar">
                <div class="profile-dropdown" id="profileDropdown" style="position:relative;cursor:pointer;">
                    <div class="avatar"><?= strtoupper($currentUser['first_name'][0]) ?></div>
                    <div>
                        <div style="font-weight:700"><?= htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?></div>
                        <div style="font-size:13px;color:var(--muted)">Active Learner</div>
                    </div>
                </div>

                <nav class="navlinks">
                    <a href="learnerDashboard.php">🏠 Overview</a>
                    <a href="subjects.php">📚 My Subjects</a>
                    <a class="active" href="searchTutors.php">🔎 Find Tutors</a>
                    <a href="schedule.php">📅 My Schedule</a>
                    <a href="requests.php">✉️ Requests</a>
                    <a href="setting.php">⚙️ Settings</a>
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
                <input id="searchInput" placeholder="Search tutors, subjects or topics" />
                <select id="searchFilter">
                    <option value="all">All</option>
                    <option value="name">Name</option>
                    <option value="subject">Subject</option>
                </select>
                <button id="clearSearch" title="Clear search">✕</button>
            </div>
            <div class="nav-actions">
                <div style="display:flex;align-items:center;gap:8px">
                    <div style="text-align:right;margin-right:6px">
                        <div style="font-weight:700"><?= htmlspecialchars($currentUser['first_name']) ?></div>
                        <div style="font-size:12px;color:var(--muted)">Learner</div>
                    </div>
                    <div class="avatar" style="width:40px;height:40px;border-radius:10px">
                        <?= strtoupper(substr($currentUser['first_name'], 0, 1) . substr($currentUser['last_name'], 0, 1)) ?>
                    </div>
                </div>
            </div>
        </div>

        <main>
            <h1>Find Tutors</h1>

            <?php if (!empty($success)) echo "<p style='color:green;'>$success</p>"; ?>
            <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

            <div class="subjects-grid">
                <?php foreach ($tutors as $t): ?>
                    <?php
                        $subjects_list = $t['subjects'] ? explode(',', $t['subjects']) : [];
                        $subject_name = trim($subjects_list[0] ?? 'Unknown');
                    ?>
                    <div class="subject-card">
                        <div class="subject-header">
                            <div class="icon" style="background: linear-gradient(180deg,#2563eb,#1e40af)">👩‍🏫</div>
                            <div class="subject-title"><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></div>
                        </div>
                        <div class="topics">
                            <?php if ($subject_name) echo "<span class='topic'>" . htmlspecialchars($subject_name) . "</span>"; ?>
                        </div>

                        <button type="button" onclick="openModal('<?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?>', '<?= htmlspecialchars($t['bio'] ?: 'No description available.') ?>', '<?= htmlspecialchars($t['expertise'] ?: 'Not specified') ?>', '<?= htmlspecialchars($t['subjects'] ?: 'None') ?>', '<?= $t['tutor_id'] ?>', '<?= htmlspecialchars($subject_name) ?>', '<?= $t['average_rating'] ?? 0 ?>', '<?= $t['total_ratings'] ?? 0 ?>')" style="padding:6px 12px;background:#4f46e5;color:white;border:none;border-radius:6px;cursor:pointer;margin-top:10px;">View Tutor</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <p id="noResults">No tutors found matching your search.</p>
        </main>
    </div>

    <div id="tutorModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <div class="modal-name" id="modalTutorName"></div>
            <div class="modal-subject" id="modalSubject"></div>
            <div class="modal-description">
                <p><strong></strong> <span id="modalBio"></span></p>
                <p><strong>Expertise:</strong> <span id="modalExpertise"></span></p>
            </div>
            <form method="POST" id="requestForm">
                <input type="hidden" name="tutor_id" id="modalTutorId">
                <input type="hidden" name="subject" id="modalSubjectHidden">
                <button type="submit" name="request_tutor" class="request-btn">Request Tutor</button>
            </form>
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
    const hamburger = document.getElementById('hamburger');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const profile = document.getElementById('profileDropdown');
    const dropdown = document.getElementById('dropdownMenu');
    const modal = document.getElementById('tutorModal');

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

    function openModal(name, bio, expertise, subjects, tutorId, subject, rating, reviews) {
        document.getElementById('modalTutorName').textContent = name;
        document.getElementById('modalBio').textContent = bio;
        document.getElementById('modalExpertise').textContent = expertise;
        document.getElementById('modalSubject').textContent = subject;
        document.getElementById('modalTutorId').value = tutorId;
        document.getElementById('modalSubjectHidden').value = subject;
        
        // Display rating
        const ratingNum = parseFloat(rating) || 0;
        const reviewsNum = parseInt(reviews) || 0;
        const stars = Math.round(ratingNum);
        let starsDisplay = '';
        for (let i = 1; i <= 5; i++) {
            starsDisplay += i <= stars ? '⭐' : '☆';
        }
        document.getElementById('modalStars').textContent = starsDisplay;
        document.getElementById('modalRatingValue').textContent = ratingNum > 0 ? ratingNum.toFixed(1) : 'N/A';
        document.getElementById('modalReviews').textContent = reviewsNum > 0 ? `(${reviewsNum} reviews)` : '(No reviews yet)';
        
        modal.style.display = 'flex';
    }

    function closeModal() {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    // Debounce function for performance
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

      const tutors = document.querySelectorAll('.subject-card');

      const filterTutors = () => {
        const query = searchInput.value.toLowerCase();
        const filter = searchFilter.value;
        let hasVisible = false;

        tutors.forEach(tutor => {
          const name = tutor.querySelector('.subject-title')?.textContent.toLowerCase() || '';
          const subjects = tutor.querySelector('.topics')?.textContent.toLowerCase() || '';

          let show = false;
          if (filter === 'all') {
            show = name.includes(query) || subjects.includes(query);
          } else if (filter === 'name') {
            show = name.includes(query);
          } else if (filter === 'subject') {
            show = subjects.includes(query);
          }

          tutor.style.display = show ? '' : 'none';
          if (show) hasVisible = true;
        });

        noResults.style.display = hasVisible ? 'none' : 'block';
      };

      const debouncedFilter = debounce(filterTutors, 300);
      searchInput.addEventListener('input', debouncedFilter);
      searchFilter.addEventListener('change', filterTutors);

      clearSearch.addEventListener('click', () => {
        searchInput.value = '';
        searchFilter.value = 'all';
        filterTutors();
      });
    });
    </script>
</body>
</html>
