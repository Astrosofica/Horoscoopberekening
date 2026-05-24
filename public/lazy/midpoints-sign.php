<?php
/**
 * Lazy loader: Midpoints by sign
 * 
 * Calculates midpoints sorted by sign (with separators)
 */

use Tijd\Calculation\MidpointCalculator;

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
    
    if (!isset($_SESSION['horoscope']['midpoints']['by_sign'])) {
        $midpoints = $_SESSION['horoscope']['midpoints']['all']['midpoints'];
        
        $realMidpoints = array_filter($midpoints, fn($mp) => !isset($mp['separator']));
        
        usort($realMidpoints, fn($a, $b) => $a['normalized'] - $b['normalized']);
        
        $sorted = [];
        $lastSign = -1;
        foreach ($realMidpoints as $mp) {
            $sign = (int) floor($mp['normalized'] / 30);
            if ($lastSign !== -1 && $sign !== $lastSign) {
                $sorted[] = ['separator' => true];
            }
            $sorted[] = $mp;
            $lastSign = $sign;
        }
        
        $_SESSION['horoscope']['midpoints']['by_sign'] = $sorted;
    }
    
    $currentTab = 'midpoints-sign';
    $midpointsResult = $_SESSION['horoscope']['midpoints']['by_sign'];
}

return $midpointsResult;