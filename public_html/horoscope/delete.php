<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Astro\Auth\AuthService;
use Astro\Database\HoroscopeRepository;

$authService = new AuthService();

if (!$authService->isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard.php');
    exit;
}

validateCsrfToken();

$currentUser = $authService->getCurrentUser();
$slug = $_POST['slug'] ?? '';

if (empty($slug)) {
    $_SESSION['flash_error'] = 'Ongeldige horoscoop.';
    header('Location: ../dashboard.php');
    exit;
}

$horoscopeRepo = new HoroscopeRepository();
$deleted = $horoscopeRepo->deleteBySlug($slug, $currentUser->getId());

if ($deleted) {
    $_SESSION['flash_success'] = 'Horoscoop verwijderd.';
} else {
    $_SESSION['flash_error'] = 'Horoscoop kon niet worden verwijderd.';
}

header('Location: ../dashboard.php');
exit;