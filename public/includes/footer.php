<?php
if (!defined('BUILD_VERSION')) {
    require_once __DIR__ . '/../../config/app.php';
}

// Detect base URL relative to current script
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$baseUrl = '';
if (basename($scriptDir) === 'horoscope' || basename($scriptDir) === 'public') {
    $baseUrl = basename($scriptDir) === 'horoscope' ? '../' : '';
}
?>
<footer class="card card--footer">
    <p class="footer-text">
        <span class="footer-separator">|</span>
        <a href="<?= $baseUrl ?>index.php?tab=about" class="footer-link">Over</a>
        <span class="footer-separator">|</span>
        <a href="<?= $baseUrl ?>index.php?tab=about#privacy" class="footer-link">Privacy</a>
        <span class="footer-separator">|</span>
        <a href="<?= $baseUrl ?>index.php?tab=about#licenses" class="footer-link">Licenties</a>
        <span class="footer-separator">|</span><br>
         <?= APP_NAME ?> is een programma van <?= APP_AUTHOR ?>.
        <span class="footer-version">Versie <?= BUILD_VERSION ?> (<?= BUILD_DATE ?>)</span>
    </p>
</footer>