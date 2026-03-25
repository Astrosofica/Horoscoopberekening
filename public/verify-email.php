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
require_once __DIR__ . '/../src/Mail/Mailer.php';
require_once __DIR__ . '/../src/Mail/EmailTemplate.php';
require_once __DIR__ . '/../src/Auth/AuthService.php';

use Tijd\Auth\AuthService;
use Tijd\Mail\Mailer;

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);

$mailer = new Mailer();
$authService = new AuthService(null, $mailer, $baseUrl);

$error = null;
$success = null;

if (!$authService->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = $authService->getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['resend'])) {
        if ($authService->sendVerificationEmail($currentUser)) {
            $success = 'Verificatie e-mail verzonden! Controleer je inbox.';
        } else {
            $error = 'Kon e-mail niet verzenden. Probeer het later opnieuw.';
        }
    }
}

$isVerified = $currentUser->isEmailVerified();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-mail verifiëren - Tijd</title>
    <link rel="stylesheet" href="css/style.css">
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

    <div class="card card--auth">
        <h2>E-mail verificatie</h2>

        <?php if ($error): ?>
            <div class="flash flash--error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="flash flash--success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($isVerified): ?>
            <div class="flash flash--success">
                Je e-mailadres is geverifieerd!
            </div>
            <p><a href="dashboard.php">Ga naar dashboard</a></p>
        <?php else: ?>
            <p>
                Je e-mailadres (<strong><?= htmlspecialchars($currentUser->getEmail()) ?></strong>) is nog niet geverifieerd.
            </p>
            <p>
                Controleer je inbox voor de verificatie e-mail.
            </p>
            <form method="POST">
                <div class="form-submit">
                    <button type="submit" name="resend">Verificatie e-mail opnieuw versturen</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>