<?php

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function getCSRFTokenInput() {
    $token = generateCSRFToken();
    return "<input type='hidden' name='csrf_token' value='" . htmlspecialchars($token) . "'>";
}

function regenerateSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function sanitizeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
    $cacheKey = "rate_limit_" . hash('sha256', $identifier);
    
    if (!isset($_SESSION[$cacheKey])) {
        $_SESSION[$cacheKey] = ['attempts' => 0, 'first_attempt' => time()];
    }
    
    $data = $_SESSION[$cacheKey];
    $timeElapsed = time() - $data['first_attempt'];
    
    if ($timeElapsed > $timeWindow) {
        $_SESSION[$cacheKey] = ['attempts' => 0, 'first_attempt' => time()];
        return true;
    }
    
    if ($data['attempts'] >= $maxAttempts) {
        return false;
    }
    
    $_SESSION[$cacheKey]['attempts']++;
    return true;
}
?>
