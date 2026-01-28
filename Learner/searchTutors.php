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
                header("Location: requests.php");
                exit;
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
    
    $tutorSubjectsWithTopics = [];
    foreach ($tutors as $tutor) {
        $subjectStmt = $pdo->prepare("
            SELECT subject_name, topics FROM tutor_subjects WHERE tutor_id = ?
        ");
        $subjectStmt->execute([$tutor['tutor_id']]);
        $tutorSubjectsWithTopics[$tutor['tutor_id']] = $subjectStmt->fetchAll(PDO::FETCH_ASSOC);
    }
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
    <script src="../JS/advancedSearch.js" defer></script>
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
                <input type="text" id="searchInput" placeholder="Search tutors, subjects or topics" class="search-input"
                       style="border: 2px solid rgba(16, 185, 129, 0.2); background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(52, 211, 153, 0.05) 100%); transition: all 0.3s;">
            </div>
            <div class="navbar-user">
                <div class="user-info">
                    <span class="user-name"><?= htmlspecialchars($currentUser['first_name'] ?? 'Learner') ?></span>
                    <span class="user-role"><?= $currentUser['role'] == 'tutor' ? 'Tutor' : 'Learner' ?></span>
                </div>
                <div class="user-avatar">
                    <?= strtoupper(substr($currentUser['first_name'], 0, 1) . substr($currentUser['last_name'], 0, 1)) ?>
                </div>
            </div>
        </nav>

        <aside id="sidebar">
            <div class="sidebar">
                <div class="profile" id="sidebarProfile" title="View Profile">
                    <div class="avatar">
                        <?= strtoupper($currentUser['first_name'][0]) ?>
                    </div>
                    <div class="profile-text">
                        <div class="profile-name"><?= htmlspecialchars($currentUser['first_name'].' '.$currentUser['last_name']) ?></div>
                        <div class="profile-status"><?= $currentUser['role'] == 'tutor' ? 'Active Tutor' : 'Active Learner' ?></div>
                    </div>
                    <div class="view-profile-tooltip">View Profile</div>
                </div>

                <nav class="navlinks">
                    <a class="nav-link" href="learnerDashboard.php">
                        <span class="nav-text">Overview</span>
                    </a>
                    <a class="nav-link" href="subjects.php">
                        <span class="nav-text">My Subjects</span>
                    </a>
                    <a class="nav-link active" href="searchTutors.php">
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
            <h1 class="welcome-title">Find Tutors</h1>
            <p class="welcome-subtitle">Search and connect with qualified tutors</p>

            <?php if (!empty($success)) echo "<p style='color:green;'>$success</p>"; ?>
            <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

            <!-- Search & Filter Section -->
            <div style="margin-bottom: 30px; display: grid; grid-template-columns: 1fr 200px 100px; gap: 15px; align-items: end;">
                <div style="display: flex; gap: 10px;">
                    <input type="text" id="searchInput" placeholder="Search tutors by name, subjects, or topics..." 
                           style="flex: 1; padding: 12px 16px; border: 2px solid rgba(16, 185, 129, 0.2); border-radius: 8px; background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(52, 211, 153, 0.05) 100%); font-size: 14px; transition: all 0.3s;">
                </div>
                <select id="searchFilter" style="padding: 12px 16px; border: 2px solid rgba(16, 185, 129, 0.2); border-radius: 8px; background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(52, 211, 153, 0.05) 100%); font-size: 14px; cursor: pointer;">
                    <option value="all">All</option>
                    <option value="name">Name</option>
                    <option value="subject">Subject</option>
                </select>
                <button id="clearSearch" style="padding: 12px 16px; background: linear-gradient(135deg, #10b981 0%, #34d399 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">Clear</button>
            </div>

            <div class="subjects-grid">
                <?php foreach ($tutors as $t): ?>
                    <?php
                        $subjects_list = $t['subjects'] ? explode(',', $t['subjects']) : [];
                        $subject_name = trim($subjects_list[0] ?? 'Unknown');
                    ?>
                    <div class="subject-card" style="display: flex; flex-direction: column;">
                        <div style="flex-grow: 1;">
                            <div class="subject-header">
                                <div class="icon" style="background: linear-gradient(180deg,#2563eb,#1e40af)">👩‍🏫</div>
                                <div class="subject-title"><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></div>
                            </div>
                            <div class="topics">
                                <?php 
                                    $subjects_array = $t['subjects'] ? array_map('trim', explode(',', $t['subjects'])) : [];
                                    if (!empty($subjects_array)) {
                                        echo "<span class='topic'>" . htmlspecialchars($subjects_array[0]);
                                        if (count($subjects_array) > 1) {
                                            echo "...";
                                        }
                                        echo "</span>";
                                    }
                                ?>
                            </div>
                        </div>

                        <button type="button" class="view-tutor-btn" 
                            data-name="<?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?>"
                            data-bio="<?= htmlspecialchars($t['bio'] ?: 'No description available.') ?>"
                            data-expertise="<?= htmlspecialchars($t['expertise'] ?: 'Not specified') ?>"
                            data-subjects="<?= htmlspecialchars($t['subjects'] ?: 'None') ?>"
                            data-tutor-id="<?= $t['tutor_id'] ?>"
                            data-subject-name="<?= htmlspecialchars($subject_name) ?>"
                            data-rating="<?= $t['average_rating'] ?? 0 ?>"
                            data-reviews="<?= $t['total_ratings'] ?? 0 ?>"
                            data-subjects-json="<?= htmlspecialchars(json_encode($tutorSubjectsWithTopics[$t['tutor_id']] ?? [])) ?>"
                            style="align-self: flex-end; padding:6px 12px;background:#16a34a;color:white;border:none;border-radius:6px;cursor:pointer;margin-top:10px;transition: background 0.3s;">View Tutor</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <p id="noResults" style="display: none;">No tutors found matching your search.</p>
        </main>
    </div>

    <div id="tutorModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px;">
                <div>
                    <h3 style="margin-bottom: 20px; font-size: 16px; font-weight: 600;">Tutor's description :</h3>
                    <div style="background: #f5f5f5; padding: 20px; border-radius: 8px; min-height: 120px;">
                        <p id="modalBio" style="margin: 0; color: #666; line-height: 1.6;"></p>
                    </div>
                    <div style="margin-top: 25px;">
                        <p style="margin: 8px 0; font-size: 14px; font-weight: 600;">Expertise:</p>
                        <p id="modalExpertise" style="margin: 8px 0; color: #666; font-size: 14px;"></p>
                    </div>
                </div>
                
                <div>
                    <h3 style="margin-bottom: 20px; font-size: 16px; font-weight: 600;">Tutor's information :</h3>
                    <div style="background: #f5f5f5; padding: 20px; border-radius: 8px;">
                        <div style="margin-bottom: 20px;">
                            <p style="margin: 8px 0; font-size: 15px; font-weight: 600; color: #333;" id="modalTutorName"></p>
                        </div>
                        <div style="margin-bottom: 20px;">
                            <p style="margin: 8px 0; font-size: 14px; font-weight: 600;">Rating:</p>
                            <div style="margin: 8px 0; font-size: 14px;">
                                <span id="modalStars"></span>
                                <span id="modalRatingValue" style="margin-left: 8px; font-weight: 600;"></span>
                                <span id="modalReviews" style="margin-left: 8px; color: #999;"></span>
                            </div>
                        </div>
                        <div>
                            <p style="margin: 8px 0; font-size: 14px; font-weight: 600;">Subject:</p>
                            <p id="modalSubject" style="margin: 8px 0; color: #4f46e5; font-size: 14px; font-weight: 500;"></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="margin-bottom: 30px;">
                <h3 style="margin-bottom: 25px; font-size: 16px; font-weight: 600;">Offered Subjects & Topics:</h3>
                <div id="modalSubjectsGrid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                </div>
            </div>
            
            <div id="requestSessionDiv" style="display: none; text-align: center; padding: 20px; background: #f0f9ff; border-radius: 8px; border: 2px solid #16a34a;">
                <button type="button" onclick="requestSession()" style="width: 200px; background: #16a34a; color: white; padding: 12px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 15px; transition: background 0.3s;">Request Session</button>
            </div>
            
            <form method="POST" id="requestForm">
                <input type="hidden" name="tutor_id" id="modalTutorId">
                <input type="hidden" name="subject" id="modalSubjectHidden">
                <input type="hidden" name="request_tutor" value="1">
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

    function openModal(name, bio, expertise, subjects, tutorId, subject, rating, reviews, subjectsWithTopics) {
        document.getElementById('modalTutorName').textContent = name;
        document.getElementById('modalBio').textContent = bio;
        document.getElementById('modalExpertise').textContent = expertise;
        document.getElementById('modalSubject').textContent = subject;
        document.getElementById('modalTutorId').value = tutorId;
        
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
        
        const subjectsGrid = document.getElementById('modalSubjectsGrid');
        subjectsGrid.innerHTML = '';
        
        subjectsWithTopics.forEach(subj => {
            const topicsArray = subj.topics ? subj.topics.split(',').map(t => t.trim()) : [];
            const subjectCard = document.createElement('div');
            subjectCard.style.cssText = 'border: 2px solid #d4d4d4; border-radius: 8px; padding: 20px; background: white; display: flex; flex-direction: column; cursor: pointer; transition: all 0.3s;';
            subjectCard.onmouseover = () => subjectCard.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
            subjectCard.onmouseout = () => subjectCard.style.boxShadow = 'none';
            
            const topicsHTML = topicsArray.length > 0 
                ? topicsArray.map(t => `<div style="background: #e8f5e9; padding: 6px 12px; border-radius: 4px; margin-bottom: 8px; font-size: 13px; color: #2d5f3f;">${t}</div>`).join('')
                : '<p style="margin: 0; color: #999; font-size: 13px;">No topics specified</p>';
            
            subjectCard.innerHTML = `
                <div style="background: linear-gradient(135deg, #a8d5ba, #82c2a4); padding: 15px; border-radius: 6px; text-align: center; margin-bottom: 15px;">
                    <p style="margin: 0; font-weight: 600; color: white; font-size: 15px;">${subj.subject_name}</p>
                </div>
                <div style="flex-grow: 1; margin-bottom: 15px;">
                    ${topicsHTML}
                </div>
                <button type="button" onclick="selectSubject(event, '${subj.subject_name}', this.parentElement)" style="width: 100%; background: #16a34a; color: white; padding: 10px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; font-size: 14px; transition: background 0.3s;">Select Subject</button>
            `;
            subjectsGrid.appendChild(subjectCard);
        });
        
        document.getElementById('requestSessionDiv').style.display = 'block';
        modal.style.display = 'flex';
    }

    function selectSubject(event, subject, cardElement) {
        event.preventDefault();
        
        // Remove green border from all subject cards
        document.querySelectorAll('#modalSubjectsGrid > div').forEach(card => {
            card.style.borderColor = '#d4d4d4';
        });
        
        // Add green border to selected card
        if (cardElement) {
            cardElement.style.borderColor = '#16a34a';
            cardElement.style.borderWidth = '3px';
        }
        
        document.getElementById('modalSubjectHidden').value = subject;
        document.getElementById('requestSessionDiv').style.display = 'block';
    }

    function requestSession() {
        document.getElementById('requestForm').submit();
    }

    function closeModal() {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    function debounce(func, delay) {
      let timeout;
      return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), delay);
      };
    }

    document.addEventListener('DOMContentLoaded', () => {
      // Handle View Tutor buttons
      document.querySelectorAll('.view-tutor-btn').forEach(btn => {
        btn.addEventListener('click', function() {
          const subjectsJson = JSON.parse(this.getAttribute('data-subjects-json'));
          openModal(
            this.getAttribute('data-name'),
            this.getAttribute('data-bio'),
            this.getAttribute('data-expertise'),
            this.getAttribute('data-subjects'),
            this.getAttribute('data-tutor-id'),
            this.getAttribute('data-subject-name'),
            this.getAttribute('data-rating'),
            this.getAttribute('data-reviews'),
            subjectsJson
          );
        });
      });

      // Search functionality
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
