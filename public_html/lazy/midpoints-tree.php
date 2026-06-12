<?php
/**
 * Lazy loader: Midpoints tree
 * 
 * Calculates midpoint trees (aspects between radix and midpoints)
 */

use Astro\Calculation\MidpointCalculator;
use Astro\Calculation\MidpointTreeCalculator;

$currentTab = null;
$treeResult = null;

if (isset($_SESSION['horoscope']['core'])) {
    if (!isset($_SESSION['horoscope']['midpoints'])) {
        $midpointCalculator = new MidpointCalculator();
        $_SESSION['horoscope']['midpoints'] = [
            'input' => ['timestamp' => time()],
            'all' => $midpointCalculator->calculateAllMidpoints($_SESSION['horoscope']['core']),
        ];
    }
    
    if (!isset($_SESSION['horoscope']['midpoints']['tree'])) {
        $treeCalculator = new MidpointTreeCalculator();
        $_SESSION['horoscope']['midpoints']['tree'] = $treeCalculator->calculateTree(
            $_SESSION['horoscope']['core'],
            $_SESSION['horoscope']['midpoints']['all']['midpoints']
        );
    }
    
    $currentTab = 'midpoints-tree';
    $treeResult = $_SESSION['horoscope']['midpoints']['tree'];
}

return $treeResult;