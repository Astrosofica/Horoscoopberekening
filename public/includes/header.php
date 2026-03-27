<?php
require_once __DIR__ . '/../../config/app.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userEmail = $_SESSION['user_email'] ?? null;

$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$baseUrl = '';
if (basename($scriptDir) === 'horoscope' || basename($scriptDir) === 'public') {
    $baseUrl = basename($scriptDir) === 'horoscope' ? '../' : '';
}

$currentScript = basename($_SERVER['SCRIPT_NAME']);
$isDashboard = ($currentScript === 'dashboard.php');
?>
<header class="card card--header">
    <div class="header-content">
        <a href="<?= $baseUrl . ($isLoggedIn ? 'dashboard.php' : 'index.php') ?>" class="header-brand"><?= APP_NAME ?></a>
        <nav class="header-nav">
            <?php if ($isLoggedIn): ?>
                <?php if (!$isDashboard): ?>
                    <a href="<?= $baseUrl ?>index.php">Horoscoop berekenen</a>
                <?php endif; ?>
                <span class="header-user"><?= htmlspecialchars($userEmail) ?></span>
                <a href="<?= $baseUrl ?>logout.php" class="header-logout">Uitloggen</a>
            <?php else: ?>
                <a href="<?= $baseUrl ?>index.php">Horoscoop berekenen</a>
                <a href="<?= $baseUrl ?>login.php">Inloggen</a>
                <a href="<?= $baseUrl ?>register.php">Registreren</a>
            <?php endif; ?>
        </nav>
    </div>
</header>