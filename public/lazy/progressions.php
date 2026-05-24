<?php
/**
 * Lazy loader: Progressions tab
 * 
 * Calculates secondary progressions for today (lazy loading)
 * Uses cached result if already calculated
 */

use Tijd\Calculation\ProgressionCalculator;

$currentTab = null;
$progressionsResult = null;

if (isset($_SESSION['horoscope']['core']) && isset($_SESSION['horoscope']['input'])) {
    if (!isset($_SESSION['horoscope']['progressions']) || empty($_SESSION['horoscope']['progressions'])) {
        $progCalculator = new ProgressionCalculator();
        
        $_SESSION['horoscope']['progressions'] = $progCalculator->calculateSecondaryProgressions(
            $_SESSION['horoscope']['core']['houses'],
            $_SESSION['horoscope']['input']['birth_date'],
            $_SESSION['horoscope']['input']['birth_time'],
            $_SESSION['horoscope']['input']['latitude'],
            $_SESSION['horoscope']['input']['longitude'],
            $_SESSION['horoscope']['input']['utc_offset']
        );
    }
    
    $progressionsResult = $_SESSION['horoscope']['progressions'];
    $currentTab = 'progressions';
}

return $progressionsResult;