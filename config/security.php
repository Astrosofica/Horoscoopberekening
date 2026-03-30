<?php

// Security headers - must be called before any output
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// HTTPS enforcement (only if HTTPS is active)
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Prevent PHP errors from leaking to users
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');

// Session security settings (call before session_start)
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}

// Generate CSRF token if not exists
if (session_status() === PHP_SESSION_ACTIVE && empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function validateCsrfToken(): void {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        http_response_code(403);
        die('CSRF token missing');
    }
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die('CSRF token invalid');
    }
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'] ?? '') . '">';
}

function checkRateLimit(string $identifier, int $maxAttempts = 5, int $period = 300): bool {
    if (!isset($_SESSION['rate_limits'])) {
        $_SESSION['rate_limits'] = [];
    }
    
    $now = time();
    $_SESSION['rate_limits'][$identifier] = array_filter(
        $_SESSION['rate_limits'][$identifier] ?? [],
        fn($time) => $time > $now - $period
    );
    
    if (count($_SESSION['rate_limits'][$identifier]) >= $maxAttempts) {
        return false;
    }
    
    $_SESSION['rate_limits'][$identifier][] = $now;
    return true;
}