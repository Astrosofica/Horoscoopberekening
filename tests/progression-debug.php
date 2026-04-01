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

echo "Testing progression time conversion:\n";
echo "Birth UTC: " . date('Y-m-d H:i:s', $birthUtcTimestamp) . " ($birthUtcTimestamp)\n\n";

$secProgRate = 1 / 365.24219893;

$startTimestamp = strtotime('2025-01-01');
$endTimestamp = strtotime('2025-12-31');

$progStart = (int) round($birthUtcTimestamp + ($startTimestamp - $birthUtcTimestamp) * $secProgRate);
$progEnd = (int) round($birthUtcTimestamp + ($endTimestamp - $birthUtcTimestamp) * $secProgRate);

echo "Real start: " . date('Y-m-d', $startTimestamp) . " ($startTimestamp)\n";
echo "Progression start: " . date('Y-m-d H:i:s', $progStart) . " ($progStart)\n";
echo "Progression start is " . round(($progStart - $birthUtcTimestamp) / 86400, 2) . " days after birth\n\n";

echo "Real end: " . date('Y-m-d', $endTimestamp) . " ($endTimestamp)\n";
echo "Progression end: " . date('Y-m-d H:i:s', $progEnd) . " ($progEnd)\n";
echo "Progression end is " . round(($progEnd - $birthUtcTimestamp) / 86400, 2) . " days after birth\n\n";

$progStartResult = $planetCalculator->calculateForTimestamp($progStart);
$progEndResult = $planetCalculator->calculateForTimestamp($progEnd);

echo "Sun at progression start: " . round($progStartResult['planets']['Sun']['longitude'], 4) . "°\n";
echo "Sun at progression end: " . round($progEndResult['planets']['Sun']['longitude'], 4) . "°\n";
echo "Sun movement: " . round($progEndResult['planets']['Sun']['longitude'] - $progStartResult['planets']['Sun']['longitude'], 4) . "°\n\n";

echo "Moon at progression start: " . round($progStartResult['planets']['Moon']['longitude'], 4) . "°\n";
echo "Moon at progression end: " . round($progEndResult['planets']['Moon']['longitude'], 4) . "°\n";
echo "Moon movement: " . round($progEndResult['planets']['Moon']['longitude'] - $progStartResult['planets']['Moon']['longitude'], 4) . "°\n\n";

echo "Mars at progression start: " . round($progStartResult['planets']['Mars']['longitude'], 4) . "°\n";
echo "Mars at progression end: " . round($progEndResult['planets']['Mars']['longitude'], 4) . "°\n";
echo "Mars movement: " . round($progEndResult['planets']['Mars']['longitude'] - $progStartResult['planets']['Mars']['longitude'], 4) . "°\n\n";

echo "=== End Debug ===\n";