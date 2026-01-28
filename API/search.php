<?php
session_start();
require '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'tutors';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$results = [];

try {
    if ($type === 'tutors') {
        // Search tutors
        $sql = "SELECT 
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
                WHERE u.role = 'tutor'";
        
        if (!empty($query)) {
            if ($filter === 'name' || $filter === 'all') {
                $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ?)";
            } elseif ($filter === 'subject') {
                $sql .= " AND ts.subject_name LIKE ?";
            }
        }
        
        $sql .= " GROUP BY u.id, t.id, t.expertise, t.bio, t.average_rating, t.total_ratings";
        
        $stmt = $pdo->prepare($sql);
        
        if (!empty($query)) {
            if ($filter === 'name' || $filter === 'all') {
                $likeQuery = "%$query%";
                $stmt->execute([$likeQuery, $likeQuery]);
            } elseif ($filter === 'subject') {
                $stmt->execute(["%$query%"]);
            }
        } else {
            $stmt->execute();
        }
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } elseif ($type === 'learners') {
        // Search learners (for tutors)
        $sql = "SELECT 
                    u.id AS user_id,
                    u.first_name,
                    u.last_name,
                    l.id AS learner_id,
                    l.learning_goals,
                    COALESCE(GROUP_CONCAT(DISTINCT ls.subject_name SEPARATOR ', '), '') AS subjects
                FROM users u
                JOIN learners l ON u.id = l.user_id
                LEFT JOIN learner_subjects ls ON l.id = ls.learner_id
                WHERE u.role = 'learner'";
        
        if (!empty($query)) {
            if ($filter === 'name' || $filter === 'all') {
                $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ?)";
            } elseif ($filter === 'subject') {
                $sql .= " AND ls.subject_name LIKE ?";
            }
        }
        
        $sql .= " GROUP BY u.id, l.id, l.learning_goals";
        
        $stmt = $pdo->prepare($sql);
        
        if (!empty($query)) {
            if ($filter === 'name' || $filter === 'all') {
                $likeQuery = "%$query%";
                $stmt->execute([$likeQuery, $likeQuery]);
            } elseif ($filter === 'subject') {
                $stmt->execute(["%$query%"]);
            }
        } else {
            $stmt->execute();
        }
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } elseif ($type === 'subjects') {
        // Search subjects globally
        $sql = "SELECT DISTINCT subject_name FROM tutor_subjects WHERE 1=1";
        
        if (!empty($query)) {
            $sql .= " AND subject_name LIKE ?";
        }
        
        $sql .= " ORDER BY subject_name LIMIT 20";
        
        $stmt = $pdo->prepare($sql);
        
        if (!empty($query)) {
            $stmt->execute(["%$query%"]);
        } else {
            $stmt->execute();
        }
        
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results = array_map(function($s) { return $s['subject_name']; }, $subjects);
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'count' => count($results),
        'data' => $results,
        'query' => $query,
        'filter' => $filter,
        'type' => $type
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
