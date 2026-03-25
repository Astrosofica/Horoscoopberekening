<?php
session_start();

if (file_exists(__DIR__ . '/../../.env')) {
    $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

define('ERROR_LOG_PATH', __DIR__ . '/../../var/log/error.log');
ini_set('log_errors', true);
ini_set('error_log', ERROR_LOG_PATH);

require_once __DIR__ . '/../../src/Database/Connection.php';
require_once __DIR__ . '/../../src/Entity/User.php';
require_once __DIR__ . '/../../src/Entity/Horoscope.php';
require_once __DIR__ . '/../../src/Database/UserRepository.php';
require_once __DIR__ . '/../../src/Database/HoroscopeRepository.php';
require_once __DIR__ . '/../../src/Auth/AuthService.php';
require_once __DIR__ . '/../../src/Ephemeris/EphemerisConfig.php';
require_once __DIR__ . '/../../src/Ephemeris/SwissEphemeris.php';
require_once __DIR__ . '/../../src/Calculation/PlanetCalculator.php';
require_once __DIR__ . '/../../src/Calculation/HouseCalculator.php';
require_once __DIR__ . '/../../src/Calculation/Aspect.php';
require_once __DIR__ . '/../../src/Calculation/AspectCalculator.php';
require_once __DIR__ . '/../../src/Calculation/HousePlanetMatcher.php';
require_once __DIR__ . '/../../src/Calculation/ParsFortuna.php';
require_once __DIR__ . '/../../src/Calculation/HoroscopeCalculator.php';
require_once __DIR__ . '/../../src/Helpers/Formatter.php';
require_once __DIR__ . '/../../src/Glyph/SymbolGlyph.php';

use Tijd\Auth\AuthService;
use Tijd\Database\HoroscopeRepository;
use Tijd\Calculation\HoroscopeCalculator;
use Tijd\Helpers\Formatter;
use Tijd\Glyph\SymbolGlyph;

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

$localDateTime = Formatter::formatDutchDateTime($result['local_timestamp']);
$utcDateTime = Formatter::formatDutchDateTime($result['utc_timestamp']);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($result['name']) ?> - Tijd</title>
    <link rel="stylesheet" href="../css/astro.css">
</head>
<body>
<div class="container">
    <nav class="nav-header">
        <a href="../index.php" class="nav-brand">Tijd</a>
        <div class="nav-links">
            <a href="../index.php">Horoscoop berekenen</a>
            <a href="../dashboard.php">Dashboard</a>
            <span class="nav-user"><?= htmlspecialchars($currentUser->getEmail()) ?></span>
            <a href="../logout.php" class="nav-logout">Uitloggen</a>
        </div>
    </nav>

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
        <img src="../Wheel/wheel.php?sid=<?= session_id() ?>" alt="Astrologisch Radix">
    </div>

    <div class="houses-planets-container">
        <div class="card">
            <table>
                <tr class="table-header--blue">
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

        <div class="card">
            <table>
                <tr class="table-header--purple">
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

    <div class="card">
        <h4>Aspecten (<?= count($result['aspects']) ?> totaal)</h4>
        <table>
            <tr class="table-header--orange">
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
                    <td class="text-center"><?= round($aspect->orb, 2) ?>°</td>
                    <td class="text-center"><?= Formatter::formatLongitudeWithGlyph($aspect->planet1Longitude) ?></td>
                    <td class="text-center"><?= Formatter::formatLongitudeWithGlyph($aspect->planet2Longitude) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <p class="back-link"><a href="../dashboard.php">&larr; Terug naar dashboard</a></p>
</div>
</body>
</html>