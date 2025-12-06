<?php
/**
 * Security Helper Functions
 * Provides CSRF token generation, validation, and other security utilities
 */

// Generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF token
function validateCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Get CSRF token for forms
function getCSRFTokenInput() {
    $token = generateCSRFToken();
    return "<input type='hidden' name='csrf_token' value='" . htmlspecialchars($token) . "'>";
}

// Regenerate session ID after login (prevent session fixation)
function regenerateSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

// Secure password hashing
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Sanitize output to prevent XSS
function sanitizeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Rate limiting helper (simple implementation)
function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
    $cacheKey = "rate_limit_" . hash('sha256', $identifier);
    
    if (!isset($_SESSION[$cacheKey])) {
        $_SESSION[$cacheKey] = ['attempts' => 0, 'first_attempt' => time()];
    }
    
    $data = $_SESSION[$cacheKey];
    $timeElapsed = time() - $data['first_attempt'];
    
    // Reset if time window has passed
    if ($timeElapsed > $timeWindow) {
        $_SESSION[$cacheKey] = ['attempts' => 0, 'first_attempt' => time()];
        return true;
    }
    
    // Check if max attempts exceeded
    if ($data['attempts'] >= $maxAttempts) {
        return false;
    }
    
    // Increment attempts
    $_SESSION[$cacheKey]['attempts']++;
    return true;
}
?>
