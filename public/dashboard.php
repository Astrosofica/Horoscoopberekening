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
require_once __DIR__ . '/../src/Entity/Horoscope.php';
require_once __DIR__ . '/../src/Database/UserRepository.php';
require_once __DIR__ . '/../src/Database/HoroscopeRepository.php';
require_once __DIR__ . '/../src/Auth/AuthService.php';

use Tijd\Auth\AuthService;
use Tijd\Database\HoroscopeRepository;

$authService = new AuthService();

if (!$authService->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = $authService->getCurrentUser();
$horoscopeRepo = new HoroscopeRepository();
$horoscopes = $horoscopeRepo->findByUserId($currentUser->getId());

$success = $_SESSION['flash_success'] ?? null;
$error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Tijd</title>
    <link rel="stylesheet" href="css/astro.css">
</head>
<body>
<div class="container">
    <nav class="nav-header">
        <a href="index.php" class="nav-brand">Tijd</a>
        <div class="nav-links">
            <a href="index.php">Horoscoop berekenen</a>
            <a href="dashboard.php">Dashboard</a>
            <span class="nav-user"><?= htmlspecialchars($currentUser->getEmail()) ?></span>
            <a href="logout.php" class="nav-logout">Uitloggen</a>
        </div>
    </nav>

    <?php if ($success): ?>
        <div class="flash flash--success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="flash flash--error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card card--large">
        <div class="card-header">
            <h2>Mijn Horoscopen</h2>
            <a href="index.php" class="btn btn--primary">+ Nieuwe horoscoop</a>
        </div>

        <?php if (empty($horoscopes)): ?>
            <p class="empty-message">Je hebt nog geen horoscopen opgeslagen.</p>
            <p><a href="index.php">Bereken je eerste horoscoop</a></p>
        <?php else: ?>
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Naam</th>
                        <th>Geboortedatum</th>
                        <th>Geboorteplaats</th>
                        <th>Aangemaakt</th>
                        <th>Acties</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($horoscopes as $h): ?>
                        <tr>
                            <td>
                                <a href="horoscope/view.php?s=<?= $h->getSlug() ?>" class="link--name">
                                    <?= htmlspecialchars($h->getName()) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($h->getBirthDate()) ?> <?= htmlspecialchars(substr($h->getBirthTime(), 0, 5)) ?></td>
                            <td><?= htmlspecialchars($h->getLocationName()) ?></td>
                            <td><?= $h->getCreatedAt()?->format('d-m-Y') ?></td>
                            <td class="actions">
                                <a href="horoscope/view.php?s=<?= $h->getSlug() ?>" class="btn btn--small">Bekijk</a>
                                <a href="index.php?edit=<?= $h->getSlug() ?>" class="btn btn--small btn--secondary">Bewerk</a>
                                <form method="POST" action="horoscope/delete.php" class="form--inline" onsubmit="return confirm('Weet je zeker dat je deze horoscoop wilt verwijderen?');">
                                    <input type="hidden" name="slug" value="<?= $h->getSlug() ?>">
                                    <button type="submit" class="btn btn--small btn--danger">Verwijder</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card card--large">
        <h2>Account instellingen</h2>
        <p>
            <strong>E-mail:</strong> <?= htmlspecialchars($currentUser->getEmail()) ?>
            <?php if ($currentUser->isEmailVerified()): ?>
                <span class="verified-badge">Geverifieerd</span>
            <?php else: ?>
                <a href="verify-email.php" class="btn btn--small btn--secondary">Verifiëren</a>
            <?php endif; ?>
        </p>
        <p>
            <a href="change-password.php" class="btn btn--small btn--secondary">Wachtwoord wijzigen</a>
            <a href="delete-account.php" class="btn btn--small btn--danger">Account verwijderen</a>
        </p>
    </div>
</div>
</body>
</html>