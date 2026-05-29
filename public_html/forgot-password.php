<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Astro\Auth\AuthService;
use Astro\Mail\Mailer;

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);

$mailer = new Mailer();
$authService = new AuthService(null, $mailer, $baseUrl);

if ($authService->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();
    
    $email = trim($_POST['email'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    if (!checkRateLimit('forgot:' . $ip, 3, 300)) {
        $error = 'Te veel pogingen. Probeer het later opnieuw.';
    } elseif (empty($email)) {
        $error = 'Vul je e-mailadres in.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ongeldig e-mailadres.';
    } else {
        $authService->requestPasswordReset($email);
        $success = 'Als dit e-mailadres bij ons bekend is, ontvang je een e-mail met instructies om je wachtwoord te resetten.';
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wachtwoord vergeten - Horoscoopberekening</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="card card--auth">
        <h2>Wachtwoord vergeten</h2>

        <?php if ($error): ?>
            <div class="flash flash--error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="flash flash--success"><?= htmlspecialchars($success) ?></div>
        <?php else: ?>
            <p>Vul je e-mailadres in om je wachtwoord te resetten.</p>

            <form method="POST" class="auth-form">
                <?= csrfField() ?>
                <div class="form-group">
                    <label for="email">E-mailadres</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
                </div>

                <div class="form-submit">
                    <button type="submit">Wachtwoord resetten</button>
                </div>
            </form>
        <?php endif; ?>

        <p class="auth-link">
            <a href="login.php">Terug naar inloggen</a>
        </p>
    </div>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</div>
</body>
</html>