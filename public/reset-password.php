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
require_once __DIR__ . '/../src/Auth/AuthService.php';

use Tijd\Auth\AuthService;

$authService = new AuthService();

$token = $_GET['token'] ?? '';

if (empty($token)) {
    $_SESSION['flash_error'] = 'Ongeldige reset link.';
    header('Location: forgot-password.php');
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (empty($password) || empty($passwordConfirm)) {
        $error = 'Vul alle velden in.';
    } elseif (strlen($password) < 8) {
        $error = 'Wachtwoord moet minimaal 8 karakters bevatten.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Wachtwoorden komen niet overeen.';
    } else {
        if ($authService->resetPassword($token, $password)) {
            $_SESSION['flash_success'] = 'Je wachtwoord is gewijzigd. Je kunt nu inloggen.';
            header('Location: login.php');
            exit;
        } else {
            $error = 'Deze reset link is ongeldig of verlopen.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wachtwoord resetten - Tijd</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <nav class="nav-header">
        <a href="index.php" class="nav-brand">Tijd</a>
        <div class="nav-links">
            <a href="index.php">Horoscoop berekenen</a>
            <a href="login.php">Inloggen</a>
            <a href="register.php">Registreren</a>
        </div>
    </nav>

    <div class="card card--auth">
        <h2>Nieuw wachtwoord instellen</h2>

        <?php if ($error): ?>
            <div class="flash flash--error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <div class="form-group">
                <label for="password">Nieuw wachtwoord</label>
                <input type="password" id="password" name="password" required minlength="8">
                <small>Minimaal 8 karakters</small>
            </div>

            <div class="form-group">
                <label for="password_confirm">Bevestig wachtwoord</label>
                <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
            </div>

            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <div class="form-submit">
                <button type="submit">Wachtwoord wijzigen</button>
            </div>
        </form>

        <p class="auth-link">
            <a href="login.php">Terug naar inloggen</a>
        </p>
    </div>
</div>
</body>
</html>