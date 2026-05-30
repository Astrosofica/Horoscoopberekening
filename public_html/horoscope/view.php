<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Astro\Auth\AuthService;
use Astro\Database\HoroscopeRepository;
use Astro\Calculation\HoroscopeCalculator;
use Astro\Helpers\Formatter;
use Astro\Glyph\SymbolGlyph;

$authService = new AuthService();

if (!$authService->isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$currentUser = $authService->getCurrentUser();

if (!isset($_GET['s']) || empty($_GET['s'])) {
    $_SESSION['flash_error'] = 'Horoscoop niet gevonden.';
    header('Location: ../dashboard.php');
    exit;
}

$horoscopeRepo = new HoroscopeRepository();
$horoscope = $horoscopeRepo->findBySlugAndUserId($_GET['s'], $currentUser->getId());

if (!$horoscope) {
    $_SESSION['flash_error'] = 'Horoscoop niet gevonden.';
    header('Location: ../dashboard.php');
    exit;
}

$calculator = new HoroscopeCalculator();
$result = $calculator->calculate($horoscope);
$wheelData = $calculator->prepareWheelData($result);

$_SESSION['wheel_data'] = $wheelData;

$locale = $_SESSION['locale'] ?? 'nl_NL';
$localDateTime = Formatter::formatDateTime($result['local_timestamp'], $locale);
$utcDateTime = Formatter::formatDateTime($result['utc_timestamp'], $locale);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($result['name']) ?><?= $result['birth_date'] ? ' (' . date('d-m-Y', strtotime($result['birth_date'])) . ')' : '' ?> - Horoscoopberekening</title>
    <link rel="stylesheet" href="../css/style.css?v=<?= BUILD_VERSION ?>">
</head>
<body>
<div class="container">
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <div class="card card--large">
        <h2>Geboortegegevens</h2>
        <div class="birth-info-row">
            <span class="birth-info-label">Naam:</span>
            <span class="birth-info-value"><?= htmlspecialchars($result['name']) ?></span>
        </div>
        <div class="birth-info-row">
            <span class="birth-info-label">Geboortemoment:</span>
            <span class="birth-info-value"><?= $localDateTime['date'] ?>, <?= $localDateTime['time'] ?> (<?= $result['label'] ?? '' ?>)</span>
        </div>
        <div class="birth-info-row">
            <span class="birth-info-label">Locatie:</span>
            <span class="birth-info-value"><?= htmlspecialchars($result['address']) ?> <span class="coordinates">(<?= Formatter::formatLat($result['coords']['lat']) ?>, <?= Formatter::formatLon($result['coords']['lng']) ?>)</span></span>
        </div>
        <div class="birth-info-row">
            <span class="birth-info-label">GMT/UTC:</span>
            <span class="birth-info-value"><?= $utcDateTime['date'] ?>, <?= $utcDateTime['time'] ?> GMT</span>
        </div>
    </div>

    <div class="wheel-container">
        <img src="../Wheel/wheel.php" alt="Astrologisch Radix" id="wheel-image">
        <div class="wheel-toggle">
            <a href="#" id="wheel-aspect-toggle">Toon aspectlijnen</a>
        </div>
    </div>

    <div class="houses-planets-container">
        <div class="card card--planets">
            <table>
                <tr>
                    <th colspan="3">Planeetposities</th>
                </tr>
                <?php foreach ($result['planets'] as $name => $data): ?>
                    <tr>
                        <td class="text-center"><span class="astro-glyph"><?= SymbolGlyph::getPlanetGlyphByName($name) ?></span></td>
                        <td class="text-center">
                            <?php if (isset($data['success']) && $data['success']): ?>
                                <?= Formatter::formatLongitudeWithGlyph($data['longitude']) ?>
                            <?php else: ?>
                                <span class="text-error">Fout</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if (isset($data['success']) && $data['success']): ?>
                                <?php if ($data['speed_longitude'] < 0): ?>
                                    <span class="astro-glyph"><?= SymbolGlyph::getRetrogradeGlyph() ?></span>
                                <?php else: ?>
                                    D
                                <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="card card--houses">
            <table>
                <tr>
                    <th colspan="2">Huizensysteem: <?= htmlspecialchars($result['houses']['systemName']) ?></th>
                </tr>
                <?php foreach ($result['houses']['houses'] as $houseNum => $house): ?>
                    <tr>
                        <td><?= htmlspecialchars($house['name']) ?></td>
                        <td class="text-center"><?= Formatter::formatLongitudeWithGlyph($house['longitude']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <div class="card card--aspects">
        <h4>Aspecten (<?= count($result['aspects']) ?> totaal)</h4>
        <table>
            <tr>
                <th>Planeet 1</th>
                <th></th>
                <th>Planeet 2</th>
                <th>Orb</th>
                <th>Positie 1</th>
                <th>Positie 2</th>
            </tr>
            <?php foreach ($result['aspects'] as $aspect): ?>
                <tr class="<?= $aspect->isDominant ? 'row--dominant' : '' ?>">
                    <td class="text-center"><span class="astro-glyph"><?= SymbolGlyph::getPlanetGlyphByName($aspect->planet1Name) ?></span></td>
                    <td class="text-center"><span class="astro-glyph"><?= SymbolGlyph::getAspectGlyph($aspect->aspectDegrees) ?></span></td>
                    <td class="text-center"><span class="astro-glyph"><?= SymbolGlyph::getPlanetGlyphByName($aspect->planet2Name) ?></span></td>
                    <td class="<?= $aspect->isOutOfSign ? 'orb--out-of-sign' : '' ?>"><?= number_format($aspect->orb, 2) ?>°<?= $aspect->isOutOfSign ? ' B' : '' ?></td>
                    <td class="text-center"><?= Formatter::formatLongitudeWithGlyph($aspect->planet1Longitude) ?></td>
                    <td class="text-center"><?= Formatter::formatLongitudeWithGlyph($aspect->planet2Longitude) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <p class="text-muted text-small text-right">B = Aspect buiten teken, orb gemaximeerd op 2°</p>
    </div>

    <p class="back-link"><a href="../dashboard.php">&larr; Terug naar dashboard</a></p>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</div>

<script src="../js/app.js?v=<?= BUILD_VERSION ?>"></script>
</body>
</html>