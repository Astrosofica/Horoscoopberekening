<?php
/**
 * Solaar POST handler
 *
 * Triggered when: POST + calculate_solaar
 *
 * Flow:
 * 1. Validate natal chart exists (Sun longitude in core or input)
 * 2. Validate target year
 * 3. Extract natal birth month/day from session input
 * 4. Find solar return UTC timestamp via Newton-Raphson
 * 5. Save prefill data in $_SESSION['solaar_prefill']
 * 6. Preserve natal_sun_longitude in session input for future use
 * 7. Redirect to index.php (no ?tab=)
 *
 * Sets: $error (on failure)
 * Session: $_SESSION['solaar_prefill'] (on success)
 * Redirect: Via index.php (POST-Redirect-GET pattern)
 *
 * @var string|null $error
 */

use Tijd\Calculation\SolarReturnCalculator;
use Tijd\Calculation\PlanetCalculator;

// 1. Guard: natal chart must exist
$natalSunLongitude = $_SESSION['horoscope']['core']['planets']['Sun']['longitude']
    ?? $_SESSION['horoscope']['input']['natal_sun_longitude']
    ?? null;

if ($natalSunLongitude === null) {
    $error = "Eerst een geboortehoroscoop berekenen voordat je een Solaar kunt maken.";
    return;
}

// 2. Validate target year
$solaarYear = (int) ($_POST['solaar_year'] ?? 0);
if ($solaarYear < 1900 || $solaarYear > 2100) {
    $error = "Ongeldig jaar. Kies een jaar tussen 1900 en 2100.";
    return;
}

// 3. Extract natal birth month/day from session input
$natalDate = $_SESSION['horoscope']['input']['birth_date'] ?? '';
if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $natalDate, $m)) {
    $error = "Geen geldige geboortedatum in sessie.";
    return;
}
$natalMonth = (int) $m[2];
$natalDay = (int) $m[3];

// 4. Find solar return UTC timestamp
try {
    $planetCalculator = new PlanetCalculator();
    $solarReturnCalc = new SolarReturnCalculator($planetCalculator);
    $solarReturnUtc = $solarReturnCalc->findSolarReturn(
        $natalSunLongitude,
        $solaarYear,
        $natalMonth,
        $natalDay
    );
} catch (\Exception $e) {
    $error = "Solar Return berekening mislukt: " . $e->getMessage();
    error_log("[Tijd] SolarReturn error: " . $e->getMessage());
    return;
}

// 5. Format UTC date and time
$solaarDate = date('Y-m-d', $solarReturnUtc);
$solaarTime = date('H:i:s', $solarReturnUtc);

// 6. Build prefill data
$natalInput = $_SESSION['horoscope']['input'];
$natalFirstName = $natalInput['firstname'] ?? '';
$natalInfix = $natalInput['infix'] ?? '';
$natalLastName = $natalInput['lastname'] ?? '';

$_SESSION['solaar_prefill'] = [
    'firstname'       => $natalFirstName,
    'infix'           => $natalInfix,
    'lastname'        => trim($natalLastName . ' (Solaar ' . $solaarYear . ')'),
    'birth_date'      => $solaarDate,
    'birth_time'      => $solaarTime,
    'location_name'   => '',
    'latitude'        => '',
    'longitude'       => '',
    'timezone_id'     => '',
    'utc_offset'      => 0,
    'time_correction' => 'utc',
    'offset_source'   => 'geo',
    'offset_label'    => 'UTC',
    'year'            => $solaarYear,
];

// 7. Preserve natal sun longitude for future solaar calculations
$_SESSION['horoscope']['input']['natal_sun_longitude'] = $natalSunLongitude;

error_log("[Tijd] Solar Return found: {$solaarDate} {$solaarTime} UTC for year {$solaarYear}");
