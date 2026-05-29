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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (empty($email) || empty($password) || empty($passwordConfirm)) {
        $error = 'Vul alle velden in.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ongeldig e-mailadres.';
    } elseif (strlen($password) < 8) {
        $error = 'Wachtwoord moet minimaal 8 karakters bevatten.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Wachtwoorden komen niet overeen.';
    } else {
        try {
            $authService->register($email, $password, true);
            $_SESSION['flash_success'] = 'Account aangemaakt! Controleer je e-mail om je adres te verifiëren.';
            header('Location: verify-email.php');
            exit;
        } catch (\InvalidArgumentException $e) {
            $error = $e->getMessage();
        } catch (\Exception $e) {
            $error = 'Er is een fout opgetreden. Probeer het opnieuw.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registreren - Horoscoopberekening</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <?php require_once __DIR__ . '/includes/header.php'; ?>

    <div class="card card--auth">
        <h2>Registreren</h2>

        <?php if ($error): ?>
            <div class="flash flash--error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <?= csrfField() ?>
            <div class="form-group">
                <label for="email">E-mailadres</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Wachtwoord</label>
                <input type="password" id="password" name="password" required minlength="8">
                <small>Minimaal 8 karakters</small>
            </div>

            <div class="form-group">
                <label for="password_confirm">Bevestig wachtwoord</label>
                <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
            </div>

            <div class="form-submit">
                <button type="submit">Registreren</button>
            </div>
        </form>

        <p class="auth-link">
            Heb je al een account? <a href="login.php">Inloggen</a>
        </p>
    </div>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</div>
</body>
</html>