<?php
/**
 * Lazy loader: Midpoints by planet
 * 
 * Calculates all midpoints
 */

use Astro\Calculation\MidpointCalculator;

$currentTab = null;
$midpointsResult = null;

if (isset($_SESSION['horoscope']['core'])) {
    if (!isset($_SESSION['horoscope']['midpoints'])) {
        $midpointCalculator = new MidpointCalculator();
        $_SESSION['horoscope']['midpoints'] = [
            'input' => ['timestamp' => time()],
            'all' => $midpointCalculator->calculateAllMidpoints($_SESSION['horoscope']['core']),
        ];
    }
    
    $currentTab = 'midpoints-planet';
    $midpointsResult = $_SESSION['horoscope']['midpoints']['all']['midpoints'];
}

return $midpointsResult;