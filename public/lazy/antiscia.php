<?php
/**
 * Lazy loader: Antiscia tab
 * 
 * Calculates mirror points (antiscia) - Jan de Jong method
 */

use Tijd\Calculation\MirrorPointCalculator;

$currentTab = null;

if (isset($_SESSION['horoscope']['core'])) {
    if (!isset($_SESSION['horoscope']['antiscia']) || empty($_SESSION['horoscope']['antiscia'])) {
        $mirrorCalculator = new MirrorPointCalculator();
        $_SESSION['horoscope']['antiscia'] = $mirrorCalculator->calculate(
            $_SESSION['horoscope']['core']
        );
    }
    $currentTab = 'antiscia';
}

return $_SESSION['horoscope']['antiscia'] ?? null;