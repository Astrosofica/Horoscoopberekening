<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Astro\Auth\AuthService;
use Astro\Mail\Mailer;

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
    validateCsrfToken();
    
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
    <title>E-mail verifiëren - Horoscoopberekening</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <?php require_once __DIR__ . '/includes/header.php'; ?>

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
                <?= csrfField() ?>
                <div class="form-submit">
                    <button type="submit" name="resend">Verificatie e-mail opnieuw versturen</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</div>
</body>
</html>