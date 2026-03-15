<?php

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
require_once __DIR__ . '/../src/Helpers/Formatter.php';
require_once __DIR__ . '/../src/Glyph/SymbolGlyph.php';

use Tijd\Geo\GeocodingService;
use Tijd\Time\AstroTime;
use Tijd\Ephemeris\EphemerisConfig;
use Tijd\Ephemeris\SwissEphemeris;
use Tijd\Calculation\PlanetCalculator;
use Tijd\Calculation\HouseCalculator;
use Tijd\Calculation\AspectCalculator;
use Tijd\Helpers\Formatter;
use Tijd\Glyph\SymbolGlyph;

$result = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['location'])) {
    $location = trim($_POST['location']);
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';

    if (!preg_match('/^[\p{L}\s\-\.,]+$/u', $location)) {
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

                $aspectCalculator = new AspectCalculator();
                $planetsForAspects = [];
                foreach ($planetResult['planets'] as $name => $data) {
                    if (isset($data['success']) && $data['success']) {
                        $planetsForAspects[] = [
                            'name' => $name,
                            'longitude' => $data['longitude']
                        ];
                    }
                }
                $aspectResult = $aspectCalculator->calculate($planetsForAspects, $houseResult);

                $result = [
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
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 40px auto; line-height: 1.6; }
        .card { border: 1px solid #ccc; padding: 20px; border-radius: 8px; background: #f9f9f9; }
        .result { margin-top: 20px; padding: 15px; background: #e7f3ff; border-left: 5px solid #2196F3; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box; }
        button { background: #2196F3; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; border-bottom: 1px solid #ddd; }
        .astro-glyph { font-family: 'Astro', sans-serif; font-size: 1.2em; }
    </style>
</head>
<body>

<div class="card">
    <h2>Geboortegegevens</h2>
    <form method="POST">
        <label>Geboorteplaats:</label>
        <input type="text" name="location" placeholder="Bijv. Utrecht" value="<?= htmlspecialchars($_POST['location'] ?? 'Ooltgensplaat') ?>" required>

        <label>Datum:</label>
        <input type="date" name="date" value="<?= $_POST['date'] ?? '1963-07-19' ?>" required>

        <label>Tijd (Lokaal):</label>
        <input type="time" name="time" value="<?= $_POST['time'] ?? '16:51:21' ?>" step="1" required>

        <button type="submit">Bereken Exacte Offset</button>
    </form>

    <?php if ($error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($result): ?>
        <div class="result">
            <h3>Resultaat voor <?= htmlspecialchars($result['address']) ?></h3>
            <p><small>Coördinaten: <?= Formatter::formatLat($result['coords']['lat']) ?>, <?= Formatter::formatLon($result['coords']['lng']) ?></small></p>
            <p><strong>Timezone:</strong> <?= htmlspecialchars($result['timezone']) ?></p>
            <p><strong>Offset met GMT/UTC:</strong> <?= Formatter::formatOffset($result['offset']) ?>
               (<?= $result['offset'] ?> seconden)</p>
            <p><strong>Bron:</strong> <?= $result['source'] ?> (<?= $result['label'] ?>)</p>
            <p><strong>Juliaanse Dag:</strong> <?= number_format($result['julian_day'], 5) ?></p>

            <h4>Huizensysteem: <?= htmlspecialchars($result['houses']['systemName']) ?></h4>
            <table style="width:100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px;">
                <tr style="background: #9C27B0; color: white;">
                    <th style="padding: 8px; text-align: left;">Huis</th>
                    <th style="padding: 8px;">Positie</th>
                </tr>
                <?php foreach ($result['houses']['houses'] as $houseNum => $house): ?>
                    <tr style="border-bottom: 1px solid #ddd;">
                        <td style="padding: 8px;"><?= htmlspecialchars($house['name']) ?></td>
                        <td style="padding: 8px; text-align: center;"><?= Formatter::formatLongitudeWithGlyph($house['longitude']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <h4>Aspecten (<?= count($result['aspects']) ?> totaal)</h4>
            <table style="width:100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px;">
                <tr style="background: #FF9800; color: white;">
                    <th style="padding: 8px;">Planeet 1</th>
                    <th style="padding: 8px;"></th>
                    <th style="padding: 8px;">Planeet 2</th>
                    <th style="padding: 8px;">Orb</th>
                    <th style="padding: 8px;">Positie 1</th>
                    <th style="padding: 8px;">Positie 2</th>
                </tr>
                <?php foreach ($result['aspects'] as $aspect): ?>
                    <tr style="border-bottom: 1px solid #ddd; <?= $aspect->isDominant ? 'background-color: #ffcccb;' : '' ?>">
                        <td style="padding: 8px; text-align: center;"><span class="astro-glyph"><?= SymbolGlyph::getPlanetGlyph($aspect->planet1Index) ?></span></td>
                        <td style="padding: 8px; text-align: center;"><span class="astro-glyph"><?= SymbolGlyph::getAspectGlyph($aspect->aspectDegrees) ?></span></td>
                        <td style="padding: 8px; text-align: center;"><span class="astro-glyph"><?= SymbolGlyph::getPlanetGlyph($aspect->planet2Index) ?></span></td>
                        <td style="padding: 8px; text-align: center;"><?= $aspectCalculator->formatOrb($aspect->orb) ?></td>
                        <td style="padding: 8px; text-align: center;"><?= Formatter::formatLongitudeWithGlyph($aspect->planet1Longitude) ?></td>
                        <td style="padding: 8px; text-align: center;"><?= Formatter::formatLongitudeWithGlyph($aspect->planet2Longitude) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <h4>Planeetposities</h4>
            <table style="width:100%; border-collapse: collapse; margin-top: 10px;">
                <tr style="background: #2196F3; color: white;">
                    <th style="padding: 8px; text-align: center;">Planeet</th>
                    <th style="padding: 8px;">Positie</th>
                    <th style="padding: 8px;">Status</th>
                </tr>
                <?php
                $planetIndex = 0;
                foreach ($result['planets'] as $name => $data): ?>
                    <tr style="border-bottom: 1px solid #ddd;">
                        <td style="padding: 8px; text-align: center;"><span class="astro-glyph"><?= SymbolGlyph::getPlanetGlyph($planetIndex) ?></span></td>
                        <td style="padding: 8px; text-align: center;">
                            <?php if (isset($data['success']) && $data['success']): ?>
                                <?= Formatter::formatLongitudeWithGlyph($data['longitude']) ?>
                            <?php else: ?>
                                <span style="color: red;">Fout</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 8px; text-align: center;">
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
                <?php $planetIndex++; endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
