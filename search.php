<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT first_name, last_name, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$searchType = isset($_GET['type']) ? $_GET['type'] : 'all';

$results = [
    'tutors' => [],
    'learners' => [],
    'subjects' => []
];

if (!empty($searchQuery)) {
    $likeQuery = "%$searchQuery%";
    
    // Search tutors
    if ($searchType === 'all' || $searchType === 'tutors') {
        $stmt = $pdo->prepare("
            SELECT 
                u.id, u.first_name, u.last_name, t.bio, t.expertise,
                t.average_rating, t.total_ratings, t.id as tutor_id,
                GROUP_CONCAT(DISTINCT ts.subject_name SEPARATOR ', ') AS subjects
            FROM users u
            JOIN tutors t ON u.id = t.user_id
            LEFT JOIN tutor_subjects ts ON t.id = ts.tutor_id
            WHERE u.role = 'tutor' 
            AND (u.first_name LIKE ? OR u.last_name LIKE ? OR ts.subject_name LIKE ? OR t.expertise LIKE ?)
            GROUP BY u.id, t.id
            LIMIT 10
        ");
        $stmt->execute([$likeQuery, $likeQuery, $likeQuery, $likeQuery]);
        $results['tutors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Search learners
    if ($searchType === 'all' || $searchType === 'learners') {
        $stmt = $pdo->prepare("
            SELECT 
                u.id, u.first_name, u.last_name, l.learning_goals,
                l.id as learner_id,
                GROUP_CONCAT(DISTINCT ls.subject_name SEPARATOR ', ') AS subjects
            FROM users u
            JOIN learners l ON u.id = l.user_id
            LEFT JOIN learner_subjects ls ON l.id = ls.learner_id
            WHERE u.role = 'learner'
            AND (u.first_name LIKE ? OR u.last_name LIKE ? OR ls.subject_name LIKE ?)
            GROUP BY u.id, l.id
            LIMIT 10
        ");
        $stmt->execute([$likeQuery, $likeQuery, $likeQuery]);
        $results['learners'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Search subjects
    if ($searchType === 'all' || $searchType === 'subjects') {
        $stmt = $pdo->prepare("
            SELECT DISTINCT subject_name
            FROM tutor_subjects
            WHERE subject_name LIKE ?
            LIMIT 10
        ");
        $stmt->execute([$likeQuery]);
        $results['subjects'] = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'subject_name');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - LearnTogether</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/modern-ui.css">
    <style>
        :root {
            --primary: #10b981;
            --secondary: #34d399;
        }
        
        body {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            padding: 40px 20px;
        }
        
        .search-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .search-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .search-header h1 {
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
            margin-bottom: 20px;
        }
        
        .search-box {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 20px 40px rgba(16, 185, 129, 0.15);
            margin-bottom: 30px;
        }
        
        .search-box input {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(52, 211, 153, 0.05) 100%);
            border: 2px solid rgba(16, 185, 129, 0.2);
            border-radius: 8px;
            padding: 15px 20px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .search-box input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        
        .results-section {
            margin-bottom: 40px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(16, 185, 129, 0.1);
        }
        
        .result-card {
            background: white;
            border: 1px solid rgba(16, 185, 129, 0.1);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }
        
        .result-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(16, 185, 129, 0.2);
            border-color: var(--primary);
        }
        
        .result-name {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
        }
        
        .result-info {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .result-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .tag {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(52, 211, 153, 0.1) 100%);
            color: var(--primary);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .rating {
            color: #fbbf24;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="search-container">
        <div class="search-header">
            <h1>🔍 Global Search</h1>
            <p class="text-muted">Find tutors, learners, and subjects across LearnTogether</p>
        </div>
        
        <form method="GET" class="search-box">
            <input type="text" name="q" placeholder="Search tutors, learners, or subjects..." 
                   value="<?= htmlspecialchars($searchQuery) ?>" autofocus required>
            <div style="margin-top: 15px; display: flex; gap: 10px;">
                <select name="type" class="form-select" style="flex: 1; max-width: 200px;">
                    <option value="all" <?= $searchType === 'all' ? 'selected' : '' ?>>All Types</option>
                    <option value="tutors" <?= $searchType === 'tutors' ? 'selected' : '' ?>>Tutors</option>
                    <option value="learners" <?= $searchType === 'learners' ? 'selected' : '' ?>>Learners</option>
                    <option value="subjects" <?= $searchType === 'subjects' ? 'selected' : '' ?>>Subjects</option>
                </select>
                <button type="submit" class="btn" style="background: linear-gradient(135deg, #10b981 0%, #34d399 100%); color: white; border: none; font-weight: 600;">Search</button>
            </div>
        </form>
        
        <?php if (!empty($searchQuery)): ?>
            <!-- Tutors Results -->
            <?php if (!empty($results['tutors'])): ?>
                <div class="results-section">
                    <div class="section-title">👨‍🏫 Tutors (<?= count($results['tutors']) ?>)</div>
                    <?php foreach ($results['tutors'] as $tutor): ?>
                        <div class="result-card">
                            <div class="result-name"><?= htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']) ?></div>
                            <?php if ($tutor['expertise']): ?>
                                <div class="result-info">📚 <?= htmlspecialchars($tutor['expertise']) ?></div>
                            <?php endif; ?>
                            <?php if ($tutor['average_rating']): ?>
                                <div class="rating">⭐ <?= number_format($tutor['average_rating'], 1) ?> (<?= $tutor['total_ratings'] ?> reviews)</div>
                            <?php endif; ?>
                            <?php if ($tutor['subjects']): ?>
                                <div class="result-tags">
                                    <?php foreach (array_slice(explode(',', $tutor['subjects']), 0, 3) as $subject): ?>
                                        <span class="tag"><?= htmlspecialchars(trim($subject)) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Learners Results -->
            <?php if (!empty($results['learners'])): ?>
                <div class="results-section">
                    <div class="section-title">👤 Learners (<?= count($results['learners']) ?>)</div>
                    <?php foreach ($results['learners'] as $learner): ?>
                        <div class="result-card">
                            <div class="result-name"><?= htmlspecialchars($learner['first_name'] . ' ' . $learner['last_name']) ?></div>
                            <?php if ($learner['learning_goals']): ?>
                                <div class="result-info">🎯 <?= htmlspecialchars($learner['learning_goals']) ?></div>
                            <?php endif; ?>
                            <?php if ($learner['subjects']): ?>
                                <div class="result-tags">
                                    <?php foreach (array_slice(explode(',', $learner['subjects']), 0, 3) as $subject): ?>
                                        <span class="tag"><?= htmlspecialchars(trim($subject)) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Subjects Results -->
            <?php if (!empty($results['subjects'])): ?>
                <div class="results-section">
                    <div class="section-title">📖 Subjects (<?= count($results['subjects']) ?>)</div>
                    <div class="result-tags">
                        <?php foreach ($results['subjects'] as $subject): ?>
                            <span class="tag" style="padding: 10px 16px; cursor: pointer;" 
                                  onclick="window.location.href='searchTutors.php?q=<?= urlencode($subject) ?>'">
                                <?= htmlspecialchars($subject) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (empty($results['tutors']) && empty($results['learners']) && empty($results['subjects'])): ?>
                <div class="no-results">
                    <p>No results found for "<?= htmlspecialchars($searchQuery) ?>"</p>
                    <p style="font-size: 14px;">Try searching with different keywords</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
