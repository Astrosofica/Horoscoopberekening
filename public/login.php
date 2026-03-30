<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

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
    validateCsrfToken();
    
    $email = trim($_POST['email'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    if (!checkRateLimit('login:' . $ip . ':' . $email, 5, 300)) {
        $error = 'Te veel pogingen. Probeer het later opnieuw.';
    } elseif (empty($email) || empty($_POST['password'])) {
        $error = 'Vul alle velden in.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ongeldig e-mailadres.';
    } else {
        try {
            if ($authService->login($email, $_POST['password'], isset($_POST['remember']))) {
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
    <title>Inloggen - Horoscoopberekening</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <?php require_once __DIR__ . '/includes/header.php'; ?>

    <div class="card card--auth">
        <h2>Inloggen</h2>

        <?php if ($error): ?>
            <div class="flash flash--error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="flash flash--success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <?= csrfField() ?>
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
            <a href="forgot-password.php">Wachtwoord vergeten?</a>
        </p>

        <p class="auth-link">
            Nog geen account? <a href="register.php">Registreren</a>
        </p>
    </div>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</div>
</body>
</html>