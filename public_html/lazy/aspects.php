<?php
/**
 * Lazy loader: Aspects tab
 * 
 * Calculates aspects only when tab is requested (lazy loading)
 * Uses cached result if already calculated
 */

use Astro\Calculation\AspectCalculator;

$currentTab = null;
$aspectResult = null;

if (isset($_SESSION['horoscope']['core'])) {
    if (!isset($_SESSION['horoscope']['aspects']) || empty($_SESSION['horoscope']['aspects'])) {
        $aspectCalculator = new AspectCalculator();
        
        $planetsForAspects = [];
        foreach ($_SESSION['horoscope']['core']['planets'] as $name => $data) {
            if ($name === 'ParsFortuna') continue;
            if (isset($data['success']) && $data['success']) {
                $planetsForAspects[$name] = ['longitude' => $data['longitude']];
            }
        }
        
        $housesForAspects = [
            'houses' => $_SESSION['horoscope']['core']['houses'],
            'ascmc' => $_SESSION['horoscope']['core']['ascmc'] ?? [],
        ];
        
        $_SESSION['horoscope']['aspects'] = $aspectCalculator->calculate(
            $planetsForAspects,
            $housesForAspects
        );
    }
    
    $aspectResult = $_SESSION['horoscope']['aspects'];
    $currentTab = 'aspects';
}

return $aspectResult;