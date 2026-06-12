<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Astro\Auth\AuthService;
use Astro\Database\UserRepository;

$authService = new AuthService();

if (!$authService->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = $authService->getCurrentUser();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();
    
    $password = $_POST['password'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';

    if (empty($password)) {
        $error = 'Vul je wachtwoord in.';
    } elseif (!password_verify($password, $currentUser->getPasswordHash())) {
        $error = 'Het wachtwoord is onjuist.';
    } elseif ($confirmation !== 'VERWIJDEREN') {
        $error = 'Type "VERWIJDEREN" om te bevestigen.';
    } else {
        $userRepository = new UserRepository();
        $userRepository->delete($currentUser->getId());
        
        session_destroy();
        
        session_start();
        $_SESSION['flash_success'] = 'Je account is verwijderd.';
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account verwijderen - Horoscoopberekening</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="card card--auth">
        <h2>Account verwijderen</h2>

        <div class="flash flash--error">
            <strong>Waarschuwing:</strong> Deze actie kan niet ongedaan worden gemaakt. 
            Alle je horoscopen worden permanent verwijderd.
        </div>

        <?php if ($error): ?>
            <div class="flash flash--error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="auth-form" onsubmit="return confirm('Weet je ABSOLUUT zeker dat je je account wilt verwijderen? Dit kan niet ongedaan worden gemaakt.');">
            <?= csrfField() ?>
            <div class="form-group">
                <label for="password">Je wachtwoord</label>
                <input type="password" id="password" name="password" required autofocus>
            </div>

            <div class="form-group">
                <label for="confirmation">Type "VERWIJDEREN" om te bevestigen</label>
                <input type="text" id="confirmation" name="confirmation" placeholder="VERWIJDEREN" required pattern="^VERWIJDEREN$">
            </div>

            <div class="form-submit">
                <button type="submit" class="btn btn--danger" style="width: 100%;">Account definitief verwijderen</button>
            </div>
        </form>

        <p class="auth-link">
            <a href="dashboard.php">&larr; Terug naar dashboard</a>
        </p>
    </div>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</div>
</body>
</html>