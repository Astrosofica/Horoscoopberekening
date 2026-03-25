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
use Tijd\Entity\Horoscope;
use Tijd\Database\HoroscopeRepository;

$authService = new AuthService();

if (!$authService->isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$currentUser = $authService->getCurrentUser();
$userId = $currentUser->getId();

$requiredFields = ['name', 'birth_date', 'birth_time', 'location_name', 'latitude', 'longitude'];
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
    $_POST['name'],
    $_POST['birth_date'],
    $_POST['birth_time'],
    $_POST['location_name'],
    (float) $_POST['latitude'],
    (float) $_POST['longitude'],
    $_POST['timezone_id'] ?? '',
    (int) $_POST['utc_offset']
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
    $horoscope->setFormattedAddress($_POST['formatted_address']);
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