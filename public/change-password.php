<?php
session_start();
require_once __DIR__ . '/../config/security.php';

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

if (!$authService->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = $authService->getCurrentUser();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();
    
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Vul alle velden in.';
    } elseif (!password_verify($currentPassword, $currentUser->getPasswordHash())) {
        $error = 'Het huidige wachtwoord is onjuist.';
    } elseif (strlen($newPassword) < 8) {
        $error = 'Het nieuwe wachtwoord moet minimaal 8 karakters bevatten.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'De nieuwe wachtwoorden komen niet overeen.';
    } elseif (password_verify($newPassword, $currentUser->getPasswordHash())) {
        $error = 'Het nieuwe wachtwoord mag niet hetzelfde zijn als het huidige wachtwoord.';
    } else {
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $currentUser->setPasswordHash($newHash);
        
        $userRepository = new \Tijd\Database\UserRepository();
        $userRepository->updatePassword($currentUser);
        
        $success = 'Je wachtwoord is gewijzigd.';
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wachtwoord wijzigen - Horoscoopberekening</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="card card--auth">
        <h2>Wachtwoord wijzigen</h2>

        <?php if ($error): ?>
            <div class="flash flash--error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="flash flash--success"><?= htmlspecialchars($success) ?></div>
        <?php else: ?>
            <form method="POST" class="auth-form">
                <?= csrfField() ?>
                <div class="form-group">
                    <label for="current_password">Huidig wachtwoord</label>
                    <input type="password" id="current_password" name="current_password" required autofocus>
                </div>

                <div class="form-group">
                    <label for="new_password">Nieuw wachtwoord</label>
                    <input type="password" id="new_password" name="new_password" required minlength="8">
                    <small>Minimaal 8 karakters</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Bevestig nieuw wachtwoord</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                </div>

                <div class="form-submit">
                    <button type="submit">Wachtwoord wijzigen</button>
                </div>
            </form>
        <?php endif; ?>

        <p class="auth-link">
            <a href="dashboard.php">&larr; Terug naar dashboard</a>
        </p>
    </div>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</div>
</body>
</html>