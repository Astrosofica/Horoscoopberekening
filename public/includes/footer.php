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
        <?= APP_NAME ?> is een programma van <?= APP_AUTHOR ?>.
        <span class="footer-version">Versie <?= BUILD_VERSION ?> (<?= BUILD_DATE ?>)</span>
    </p>
</footer>