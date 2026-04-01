<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/bootstrap.php';

use Tijd\Calculation\ProgressionEventCalculator;
use Tijd\Calculation\PlanetCalculator;
use Tijd\Calculation\HouseCalculator;
use Tijd\Glyph\SymbolGlyph;
use Tijd\Helpers\Formatter;

echo "=== Progression Events Debug Test ===\n\n";

$birthDate = '1963-07-19';
$birthTime = '16:51:21';
$lat = 51.7333;
$lng = 4.3833;
$utcOffset = -7200;

$localTimestamp = strtotime("$birthDate $birthTime");
$birthUtcTimestamp = $localTimestamp - $utcOffset;

$planetCalculator = new PlanetCalculator();
$houseCalculator = new HouseCalculator();

$birthUtcResult = $planetCalculator->calculateForTimestamp($birthUtcTimestamp);
$houseResult = $houseCalculator->calculateByTimestamp(
    $birthUtcTimestamp,
    $lat,
    $lng,
    HouseCalculator::HSYS_KOCH
);

$radixData = [
    'planets' => $birthUtcResult['planets'],
    'houses' => $houseResult['houses'],
    'ascmc' => $houseResult['ascmc'],
];

echo "RADIX POSITIONS:\n";
echo "Sun: " . round($radixData['planets']['Sun']['longitude'], 2) . "°\n";
echo "Moon: " . round($radixData['planets']['Moon']['longitude'], 2) . "°\n";
echo "Mars: " . round($radixData['planets']['Mars']['longitude'], 2) . "°\n";
echo "Asc: " . round($radixData['ascmc']['ascendant']['longitude'], 2) . "°\n\n";

$calculator = new ProgressionEventCalculator();

$startTimestamp = strtotime('2025-01-01');
$endTimestamp = strtotime('2025-12-31') + 86400;

echo "Testing full year 2025...\n";
echo "Start: " . date('Y-m-d', $startTimestamp) . "\n";
echo "End: " . date('Y-m-d', $endTimestamp) . "\n\n";

$progressivePlanets = [0, 1];
$radixTargets = [0, 1];
$aspects = [0, 90, 180];

echo "Progressive: Sun, Moon\n";
echo "Radix: Sun, Moon\n";
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
        false,
        false,
        $lat,
        $lng,
        $birthUtcTimestamp,
        $utcOffset
    );
    
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    echo "Found " . count($events) . " events in {$duration}ms:\n\n";
    
    foreach ($events as $event) {
        $date = date('d-m-Y', $event['timestamp']);
        $progGlyph = SymbolGlyph::getPlanetGlyphByIndex($event['progressive_index']);
        $aspGlyph = SymbolGlyph::getAspectGlyph($event['aspect']);
        $radixGlyph = SymbolGlyph::getPlanetGlyphByIndex($event['radix_index']);
        
        $progPos = Formatter::formatLongitudeWithGlyph($event['progressive_position']);
        $radixPos = Formatter::formatLongitudeWithGlyph($event['radix_position']);
        
        echo "$date | {$event['direction']} | $progGlyph | $aspGlyph | $radixGlyph | $eventType\n";
        echo "  Prog pos: $progPos | Radix pos: $radixPos\n\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " line " . $e->getLine() . "\n";
}