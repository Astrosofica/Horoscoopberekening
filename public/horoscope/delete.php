<?php
session_start();

if (file_exists(__DIR__ . '/../../.env')) {
    $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

require_once __DIR__ . '/../../src/Database/Connection.php';
require_once __DIR__ . '/../../src/Entity/User.php';
require_once __DIR__ . '/../../src/Entity/Horoscope.php';
require_once __DIR__ . '/../../src/Database/UserRepository.php';
require_once __DIR__ . '/../../src/Database/HoroscopeRepository.php';
require_once __DIR__ . '/../../src/Auth/AuthService.php';

use Tijd\Auth\AuthService;
use Tijd\Database\HoroscopeRepository;

$authService = new AuthService();

if (!$authService->isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard.php');
    exit;
}

$currentUser = $authService->getCurrentUser();
$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['flash_error'] = 'Ongeldige horoscoop.';
    header('Location: ../dashboard.php');
    exit;
}

$horoscopeRepo = new HoroscopeRepository();
$deleted = $horoscopeRepo->delete($id, $currentUser->getId());

if ($deleted) {
    $_SESSION['flash_success'] = 'Horoscoop verwijderd.';
} else {
    $_SESSION['flash_error'] = 'Horoscoop kon niet worden verwijderd.';
}

header('Location: ../dashboard.php');
exit;