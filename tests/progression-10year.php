<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/bootstrap.php';

use Tijd\Calculation\ProgressionEventCalculator;
use Tijd\Calculation\PlanetCalculator;
use Tijd\Calculation\HouseCalculator;
use Tijd\Glyph\SymbolGlyph;
use Tijd\Helpers\Formatter;

echo "=== Progression Events - 10 Year Test ===\n\n";

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
foreach (['Sun', 'Moon', 'Mercury', 'Venus', 'Mars', 'Jupiter', 'Saturn'] as $name) {
    if (isset($radixData['planets'][$name]['longitude'])) {
        echo "$name: " . round($radixData['planets'][$name]['longitude'], 2) . "°\n";
    }
}
echo "Asc: " . round($radixData['ascmc']['ascendant']['longitude'], 2) . "°\n\n";

$calculator = new ProgressionEventCalculator();

$startTimestamp = strtotime('2020-01-01');
$endTimestamp = strtotime('2030-01-01');

echo "Testing 10 years: 2020-2030...\n\n";

$progressivePlanets = [0, 1, 2, 3, 4];
$radixTargets = [0, 1, 2, 3, 4, 11];
$aspects = [0, 45, 60, 90, 120, 135, 150, 180];

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
    
    if (count($events) > 0) {
        echo "First 10 events:\n";
        $count = 0;
        foreach ($events as $event) {
            if ($count++ >= 10) break;
            $date = date('d-m-Y', $event['timestamp']);
            $progGlyph = SymbolGlyph::getPlanetGlyphByIndex($event['progressive_index']);
            $aspGlyph = SymbolGlyph::getAspectGlyph($event['aspect']);
            
            echo "$date | {$event['direction']} | $progGlyph $aspGlyph | {$event['radix_target']}\n";
        }
        
        if (count($events) > 10) {
            echo "... and " . (count($events) - 10) . " more events\n";
        }
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " line " . $e->getLine() . "\n";
}