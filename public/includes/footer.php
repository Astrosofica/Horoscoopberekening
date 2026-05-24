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

// Behoud bestaande query parameters (zoals h= slug) voor footer links
$existingQuery = $_GET;
unset($existingQuery['tab']); // Verwijder oude tab parameter
$aboutQuery = array_merge($existingQuery, ['tab' => 'about']);
$aboutUrl = $baseUrl . 'index.php?' . http_build_query($aboutQuery);
?>
<footer class="card card--footer">
    <p class="footer-text">
        <span class="footer-separator">|</span>
        <a href="<?= $aboutUrl ?>" class="footer-link">Over</a>
        <span class="footer-separator">|</span>
        <a href="<?= $aboutUrl ?>#privacy" class="footer-link">Privacy</a>
        <span class="footer-separator">|</span>
        <a href="<?= $aboutUrl ?>#licenses" class="footer-link">Licenties</a>
        <span class="footer-separator">|</span><br>
         <?= APP_NAME ?> is een programma van <?= APP_AUTHOR ?>.
        <span class="footer-version">Versie <?= BUILD_VERSION ?> (<?= BUILD_DATE ?>)</span>
    </p>
</footer>