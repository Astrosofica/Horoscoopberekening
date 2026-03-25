<?php
session_start();

if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

require_once __DIR__ . '/../src/Database/Connection.php';
require_once __DIR__ . '/../src/Entity/User.php';
require_once __DIR__ . '/../src/Database/UserRepository.php';
require_once __DIR__ . '/../src/Auth/AuthService.php';

use Tijd\Auth\AuthService;

$authService = new AuthService();
$authService->logout();

$_SESSION['flash_success'] = 'Je bent uitgelogd.';
header('Location: login.php');
exit;