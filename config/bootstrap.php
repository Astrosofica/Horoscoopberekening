<?php

// Load composer autoloader FIRST (before session_start for unserialize)
$autoloaderPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloaderPath)) {
    require_once $autoloaderPath;
}

// Session security settings (before session_start)
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Error handling
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Load app config
require_once __DIR__ . '/app.php';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// CSRF validation helper
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

// CSRF field helper
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'] ?? '') . '">';
}

// Rate limiting helper
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