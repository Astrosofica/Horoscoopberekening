<?php

require_once __DIR__ . '/src/Helpers/Formatter.php';
require_once __DIR__ . '/src/Glyph/SymbolGlyph.php';
require_once __DIR__ . '/src/Time/AstroTime.php';
require_once __DIR__ . '/src/Ephemeris/EphemerisConfig.php';
require_once __DIR__ . '/src/Ephemeris/SwissEphemeris.php';
require_once __DIR__ . '/src/Calculation/PlanetCalculator.php';
require_once __DIR__ . '/src/Calculation/HouseCalculator.php';

use Tijd\Time\AstroTime;
use Tijd\Ephemeris\EphemerisConfig;
use Tijd\Ephemeris\SwissEphemeris;
use Tijd\Calculation\PlanetCalculator;
use Tijd\Calculation\HouseCalculator;
use Tijd\Helpers\Formatter;
use Tijd\Glyph\SymbolGlyph;

$location = 'Ooltgensplaat';
$date = '1963-07-19';
$time = '16:51:21';

$lat = 51.7333;
$lng = 4.3833;

$timestamp = strtotime("$date $time");

$astroTime = new AstroTime('Europe/Amsterdam', $lng);
$timeResult = $astroTime->getOffset($timestamp);

$utcTimestamp = $timestamp - $timeResult['offset'];

$calculator = new PlanetCalculator();
$planetResult = $calculator->calculateForTimestamp($utcTimestamp);

$houseCalculator = new HouseCalculator();
$houseResult = $houseCalculator->calculateByTimestamp(
    $utcTimestamp,
    $lat,
    $lng,
    HouseCalculator::HSYS_KOCH
);

echo "HUIZEN ARRAY (met nr en longitude):\n";
echo "====================================\n\n";

$housesArray = [];
foreach ($houseResult['houses'] as $nr => $house) {
    $housesArray[] = [
        'nr' => $nr,
        'longitude' => $house['longitude']
    ];
}
echo "PHP code:\n";
echo "\$houses = " . var_export($housesArray, true) . ";\n\n";

echo "PLANETEN ARRAY (met name, longitude en speed):\n";
echo "===============================================\n\n";

$planetsArray = [];
foreach ($planetResult['planets'] as $name => $data) {
    if (isset($data['success']) && $data['success']) {
        $planetsArray[] = [
            'name' => $name,
            'longitude' => $data['longitude'],
            'speed' => $data['speed_longitude']
        ];
    }
}
echo "PHP code:\n";
echo "\$planets = " . var_export($planetsArray, true) . ";\n";
