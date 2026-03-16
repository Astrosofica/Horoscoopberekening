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

define('GOOGLE_API_KEY', $_ENV['GOOGLE_API_KEY'] ?? '');

require_once __DIR__ . '/../src/Geo/GeocodingService.php';
require_once __DIR__ . '/../src/Time/AstroTime.php';
require_once __DIR__ . '/../src/Ephemeris/EphemerisConfig.php';
require_once __DIR__ . '/../src/Ephemeris/SwissEphemeris.php';
require_once __DIR__ . '/../src/Calculation/PlanetCalculator.php';
require_once __DIR__ . '/../src/Calculation/HouseCalculator.php';
require_once __DIR__ . '/../src/Calculation/Aspect.php';
require_once __DIR__ . '/../src/Calculation/AspectCalculator.php';
require_once __DIR__ . '/../src/Calculation/HousePlanetMatcher.php';
require_once __DIR__ . '/../src/Calculation/ParsFortuna.php';
require_once __DIR__ . '/../src/Helpers/Formatter.php';
require_once __DIR__ . '/../src/Glyph/SymbolGlyph.php';

use Tijd\Geo\GeocodingService;
use Tijd\Time\AstroTime;
use Tijd\Ephemeris\EphemerisConfig;
use Tijd\Ephemeris\SwissEphemeris;
use Tijd\Calculation\PlanetCalculator;
use Tijd\Calculation\HouseCalculator;
use Tijd\Calculation\AspectCalculator;
use Tijd\Calculation\HousePlanetMatcher;
use Tijd\Calculation\ParsFortuna;
use Tijd\Helpers\Formatter;
use Tijd\Glyph\SymbolGlyph;

$result = null;
$error = null;

// Debug mode: populate form with test data
if (isset($_GET['do']) && $_GET['do'] === 'debug') {
    $_POST['name'] = 'Test Persoon';
    $_POST['date'] = '1985-05-15';
    $_POST['time'] = '14:30:00';
    $_POST['location'] = 'Amsterdam, Nederland';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['location'])) {
    $personName = trim($_POST['name'] ?? '');
    $location = trim($_POST['location']);
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';

    if (empty($personName)) {
        $error = "Naam is verplicht";
    } elseif (!preg_match('/^[\p{L}\s\-\.\']+$/u', $personName)) {
        $error = "Ongeldige naam";
    } elseif (!preg_match('/^[\p{L}\s\-\.,]+$/u', $location)) {
        $error = "Ongeldige locatie";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
        $error = "Ongeldige datum";
    } elseif (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
        $error = "Ongeldige tijd";
    }

    if (!isset($error)) {
        $timestamp = strtotime("$date $time");

        $geoService = new GeocodingService(GOOGLE_API_KEY);
        $geoResult = $geoService->geocode($location);

        if (isset($geoResult['error'])) {
            $error = $geoResult['error'];
        } else {
            $tzResult = $geoService->getTimezoneId($geoResult['lat'], $geoResult['lng'], $timestamp);

            if (isset($tzResult['error'])) {
                $error = $tzResult['error'];
            } else {
                $astroTime = new AstroTime($tzResult['timezoneId'], $geoResult['lng']);
                $timeResult = $astroTime->getOffset($timestamp);

                $utcTimestamp = $timestamp - $timeResult['offset'];

                $calculator = new PlanetCalculator();
                $planetResult = $calculator->calculateForTimestamp($utcTimestamp);

                $houseCalculator = new HouseCalculator();
                $houseResult = $houseCalculator->calculateByTimestamp(
                    $utcTimestamp,
                    $geoResult['lat'],
                    $geoResult['lng'],
                    HouseCalculator::HSYS_KOCH
                );

                // Calculate Pars Fortuna
                $ascendant = $houseResult['ascmc']['ascendant']['longitude'];
                $moon = $planetResult['planets']['Moon']['longitude'] ?? 0;
                $sun = $planetResult['planets']['Sun']['longitude'] ?? 0;
                
                $planetResult['planets']['ParsFortuna'] = ParsFortuna::calculateWithSpeed(
                    $ascendant,
                    $moon,
                    $sun
                );

                $aspectCalculator = new AspectCalculator();
                $planetsForAspects = [];
                foreach ($planetResult['planets'] as $name => $data) {
                    if ($name === 'ParsFortuna') continue;
                    if (isset($data['success']) && $data['success']) {
                        $planetsForAspects[$name] = [
                            'longitude' => $data['longitude']
                        ];
                    }
                }
                $aspectResult = $aspectCalculator->calculate($planetsForAspects, $houseResult);

                $result = [
                    'name' => $personName,
                    'offset' => $timeResult['offset'],
                    'source' => $timeResult['source'],
                    'label' => $timeResult['label'],
                    'coords' => ['lat' => $geoResult['lat'], 'lng' => $geoResult['lng']],
                    'address' => $geoResult['address'],
                    'timezone' => $tzResult['timezoneId'],
                    'planets' => $planetResult['planets'],
                    'julian_day' => $planetResult['julian_day'],
                    'houses' => $houseResult,
                    'aspects' => $aspectResult,
                    'local_timestamp' => $timestamp,
                    'utc_timestamp' => $utcTimestamp
                ];

                $housePlanetMatcher = new HousePlanetMatcher();
                $planetsForWheel = $housePlanetMatcher->match(
                    $result['planets'],
                    $result['houses']['houses']
                );
                $houseCuspsForWheel = $housePlanetMatcher->extractHouseCusps($result['houses']['houses']);

                $_SESSION['wheel_data'] = [
                    'name' => $personName,
                    'house_cusps' => $houseCuspsForWheel,
                    'planets' => $planetsForWheel
                ];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Astrologische Tijd Calculator</title>
    <link rel="stylesheet" href="css/astro.css">
</head>
<body>

<div class="card card--large">
    <h2>Geboortegegevens</h2>
    <form method="POST">
        <div class="form-row full">
            <div class="form-group">
                <label for="name">Naam</label>
                <input type="text" id="name" name="name" placeholder="Volledige naam" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
            </div>
        </div>

        <div class="form-row half">
            <div class="form-group">
                <label for="date">Datum</label>
                <input type="date" id="date" name="date" value="<?= htmlspecialchars($_POST['date'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="time">Tijd (lokaal)</label>
                <input type="time" id="time" name="time" value="<?= htmlspecialchars($_POST['time'] ?? '') ?>" step="1" required>
            </div>
        </div>

        <div class="form-row full">
            <div class="form-group">
                <label for="location">Geboorteplaats</label>
                <input type="text" id="location" name="location" placeholder="Bijv. Amsterdam, Nederland" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" required>
            </div>
        </div>

        <div class="form-submit">
            <button type="submit">Horoscoop berekenen</button>
        </div>
    </form>

    <?php if ($error): ?>
        <p class="form-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    
    <?php if (isset($_GET['do']) && $_GET['do'] === 'debug'): ?>
        <p style="margin-top: 16px; font-size: 0.85em; color: #4CAF50;">
            ✓ Testdata geladen &nbsp;|&nbsp;
            <a href="?" style="color: #2196F3; text-decoration: none;">Wis testdata</a>
        </p>
    <?php else: ?>
        <p style="margin-top: 16px; font-size: 0.85em; color: #888;">
            <a href="?do=debug" style="color: #2196F3; text-decoration: none;">🐛 Testdata laden</a>
        </p>
    <?php endif; ?>
</div>

    <?php if ($result): ?>
        <?php
        $localDateTime = Formatter::formatDutchDateTime($result['local_timestamp']);
        $utcDateTime = Formatter::formatDutchDateTime($result['utc_timestamp']);
        ?>
        
        <div class="card card--large">
            <h2>Geboortegegevens</h2>
            <div class="birth-info-row">
                <span class="birth-info-label">Naam:</span>
                <span class="birth-info-value"><?= htmlspecialchars($result['name']) ?></span>
            </div>
            <div class="birth-info-row">
                <span class="birth-info-label">Geboortemoment:</span>
                <span class="birth-info-value"><?= $localDateTime['date'] ?>, <?= $localDateTime['time'] ?> (<?= $result['label'] ?>)</span>
            </div>
            <div class="birth-info-row">
                <span class="birth-info-label">Locatie:</span>
                <span class="birth-info-value"><?= htmlspecialchars($result['address']) ?> <span class="coordinates">(<?= Formatter::formatLat($result['coords']['lat']) ?>, <?= Formatter::formatLon($result['coords']['lng']) ?>)</span></span>
            </div>
            <div class="birth-info-row">
                <span class="birth-info-label">Referentie (UTC):</span>
                <span class="birth-info-value"><?= $utcDateTime['date'] ?>, <?= $utcDateTime['time'] ?> GMT</span>
            </div>
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

        <div class="wheel-container">
            <img src="../src/Wheel/wheel.php?sid=<?= session_id() ?>" alt="Astrologisch Radix">
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
                        <td class="text-center"><?= $aspectCalculator->formatOrb($aspect->orb) ?></td>
                        <td class="text-center"><?= Formatter::formatLongitudeWithGlyph($aspect->planet1Longitude) ?></td>
                        <td class="text-center"><?= Formatter::formatLongitudeWithGlyph($aspect->planet2Longitude) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
