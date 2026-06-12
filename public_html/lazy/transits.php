<?php
/**
 * Lazy loader: Current transits
 * 
 * Calculates current transit positions
 */

use Astro\Calculation\TransitCalculator;

$currentTab = null;
$transitsResult = null;

if (isset($_SESSION['horoscope']['core']) && isset($_SESSION['horoscope']['input'])) {
    if (!isset($_SESSION['horoscope']['transits']) || empty($_SESSION['horoscope']['transits'])) {
        $transitCalc = new TransitCalculator();
        $_SESSION['horoscope']['transits'] = $transitCalc->calculateCurrentTransits(
            $_SESSION['horoscope']['core']['houses']
        );
    }
    
    $transitsResult = $_SESSION['horoscope']['transits'];
    $currentTab = 'transits';
}

return $transitsResult;