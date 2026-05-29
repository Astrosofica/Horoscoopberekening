<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Astro\Auth\AuthService;
use Astro\Entity\Horoscope;
use Astro\Database\HoroscopeRepository;

$authService = new AuthService();

if (!$authService->isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

validateCsrfToken();

$currentUser = $authService->getCurrentUser();
$userId = $currentUser->getId();

$requiredFields = ['lastname', 'birth_date', 'birth_time', 'location_name', 'latitude', 'longitude'];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || $_POST[$field] === '') {
        $_SESSION['flash_error'] = 'Ontbrekende gegevens.';
        header('Location: ../index.php');
        exit;
    }
}

if (!isset($_POST['utc_offset'])) {
    $_SESSION['flash_error'] = 'Ontbrekende gegevens.';
    header('Location: ../index.php');
    exit;
}

$horoscope = new Horoscope(
    $userId,
    $_POST['lastname'],
    $_POST['birth_date'],
    $_POST['birth_time'],
    $_POST['location_name'],
    (float) $_POST['latitude'],
    (float) $_POST['longitude'],
    $_POST['timezone_id'] ?? '',
    (int) $_POST['utc_offset'],
    $_POST['firstname'] ?? null,
    $_POST['infix'] ?? null
);

if (!empty($_POST['offset_source'])) {
    $horoscope->setOffsetSource($_POST['offset_source']);
}
if (!empty($_POST['offset_label'])) {
    $horoscope->setOffsetLabel($_POST['offset_label']);
}
if (!empty($_POST['time_correction'])) {
    $horoscope->setTimeCorrection($_POST['time_correction']);
}
if (!empty($_POST['formatted_address'])) {
    $cleanAddress = trim(preg_replace('/\s+/', ' ', preg_replace('/\d{4}\s?[A-Z]{2}/', '', $_POST['formatted_address'])));
    $horoscope->setFormattedAddress($cleanAddress);
}
if (!empty($_POST['house_system'])) {
    $horoscope->setHouseSystem($_POST['house_system']);
}

$horoscopeRepo = new HoroscopeRepository();
$horoscopeRepo->create($horoscope);

$replaceSlug = $_GET['replace'] ?? null;
if ($replaceSlug) {
    $horoscopeRepo->deleteBySlug($replaceSlug, $userId);
    $_SESSION['flash_success'] = 'Horoscoop bijgewerkt.';
} else {
    $_SESSION['flash_success'] = 'Horoscoop opgeslagen.';
}

header('Location: ../dashboard.php');
exit;