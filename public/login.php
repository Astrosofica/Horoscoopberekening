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

if ($authService->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = null;
$success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        $error = 'Vul alle velden in.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ongeldig e-mailadres.';
    } else {
        try {
            if ($authService->login($email, $password, $remember)) {
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'E-mailadres of wachtwoord is onjuist.';
            }
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
    <title>Inloggen - Tijd</title>
    <link rel="stylesheet" href="css/astro.css">
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
        <h2>Inloggen</h2>

        <?php if ($error): ?>
            <div class="flash flash--error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="flash flash--success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <div class="form-group">
                <label for="email">E-mailadres</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Wachtwoord</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group form-group--checkbox">
                <label>
                    <input type="checkbox" name="remember">
                    Onthoud mij
                </label>
            </div>

            <div class="form-submit">
                <button type="submit">Inloggen</button>
            </div>
        </form>

        <p class="auth-link">
            Nog geen account? <a href="register.php">Registreren</a>
        </p>
    </div>
</div>
</body>
</html>