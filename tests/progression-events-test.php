<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/bootstrap.php';

use Tijd\Calculation\ProgressionEventCalculator;
use Tijd\Calculation\PlanetCalculator;
use Tijd\Calculation\HouseCalculator;
use Tijd\Glyph\SymbolGlyph;
use Tijd\Helpers\Formatter;

echo "=== Progression Events Test ===\n\n";

$birthDate = '1963-07-19';
$birthTime = '16:51:21';
$lat = 51.7333;
$lng = 4.3833;
$utcOffset = -7200;

$localTimestamp = strtotime("$birthDate $birthTime");
$birthUtcTimestamp = $localTimestamp - $utcOffset;

echo "Birth: $birthDate $birthTime (UTC offset: $utcOffset seconds)\n";
echo "Birth UTC timestamp: $birthUtcTimestamp\n";
echo "Birth UTC: " . date('Y-m-d H:i:s', $birthUtcTimestamp) . "\n\n";

$planetCalculator = new PlanetCalculator();
$houseCalculator = new HouseCalculator();

$planetResult = $planetCalculator->calculateForTimestamp($birthUtcTimestamp);
$houseResult = $houseCalculator->calculateByTimestamp(
    $birthUtcTimestamp,
    $lat,
    $lng,
    HouseCalculator::HSYS_KOCH
);

$radixData = [
    'planets' => $planetResult['planets'],
    'houses' => $houseResult['houses'],
    'ascmc' => $houseResult['ascmc'],
];

echo "Radix Sun: " . Formatter::formatLongitudeWithGlyph($radixData['planets']['Sun']['longitude']) . "\n";
echo "Radix Moon: " . Formatter::formatLongitudeWithGlyph($radixData['planets']['Moon']['longitude']) . "\n";
echo "Radix Mars: " . Formatter::formatLongitudeWithGlyph($radixData['planets']['Mars']['longitude']) . "\n";
echo "Radix Asc: " . Formatter::formatLongitudeWithGlyph($radixData['ascmc']['ascendant']['longitude']) . "\n\n";

$calculator = new ProgressionEventCalculator();

$startTimestamp = strtotime('2025-01-01');
$endTimestamp = strtotime('2025-12-31');

echo "Calculating events for 2025...\n";
echo "Range: 2025-01-01 to 2025-12-31\n\n";

$progressivePlanets = [0, 1, 4];
$radixTargets = [0, 1, 4, 11];
$aspects = [0, 90, 180];

echo "Progressive planets: Sun, Moon, Mars\n";
echo "Radix targets: Sun, Moon, Mars, Ascendant\n";
echo "Aspects: 0, 90, 180\n\n";

$startTime = microtime(true);

try {
    $events = $calculator->calculateEvents(
        $radixData,
        $progressivePlanets,
        $radixTargets,
        $aspects,
        $startTimestamp,
        $endTimestamp,
        true,
        true,
        $lat,
        $lng,
        $birthUtcTimestamp,
        $utcOffset
    );
    
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    echo "Found " . count($events) . " events in {$duration}ms:\n\n";
    
    foreach ($events as $event) {
        $eventType = $event['event_type'];
        $date = date('d-m-Y', $event['timestamp']);
        $progGlyph = SymbolGlyph::getPlanetGlyphByIndex($event['progressive_index']);
        $aspGlyph = SymbolGlyph::getAspectGlyph($event['aspect']);
        
        echo "$date | {$event['direction']} | $progGlyph | $aspGlyph | {$event['radix_target']} | $eventType\n";
    }
    
    echo "\n=== Test Complete ===\n";
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " line " . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}