<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Tijd\Auth\AuthService;

$authService = new AuthService();

$token = $_GET['token'] ?? '';

if (empty($token)) {
    $_SESSION['flash_error'] = 'Ongeldige verificatie link.';
    header('Location: login.php');
    exit;
}

if ($authService->verifyEmail($token)) {
    $_SESSION['flash_success'] = 'Je e-mailadres is geverifieerd!';
} else {
    $_SESSION['flash_error'] = 'Deze verificatie link is ongeldig of verlopen.';
}

if ($authService->isLoggedIn()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;