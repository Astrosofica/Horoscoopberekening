<?php
/**
 * Main calculation POST handler
 * 
 * Triggered when: POST + lastname + NOT save_horoscope + NOT save_edit 
 *                 + NOT calculate_progressions + NOT calculate_transits
 * 
 * CRITICAL EXCLUSION CONDITIONS (from DEEL 2 bug fix):
 * - !isset($_POST['save_horoscope']) - don't trigger on save
 * - !isset($_POST['save_edit']) - don't trigger on edit
 * - !isset($_POST['calculate_progressions']) - don't wipe progression_events
 * - !isset($_POST['calculate_transits']) - don't wipe transit_events
 * 
 * SESSION OVERWRITE WARNING:
 * - This handler OVERWRITES $_SESSION['horoscope'] completely
 * - If progression_events or transit_events exist, they WILL BE WIPED
 * - Exclusion conditions prevent this handler from running during progression/transit submissions
 * 
 * Sets: $error (validation/geocoding errors), $result (calculation data)
 * Session: $_SESSION['horoscope'] = ['input' => ..., 'core' => ..., 'aspects' => null]
 * Redirect: Via index.php (POST-Redirect-GET pattern)
 * 
 * @var string|null $error - set on failure
 * @var array|null $result - set on success (planets, houses, ascmc)
 */

use Astro\Geo\GeocodingService;
use Astro\Time\AstroTime;
use Astro\Calculation\HoroscopeCalculator;
use Astro\Calculation\PlanetCalculator;
use Astro\Calculation\HouseCalculator;
use Astro\Calculation\AspectCalculator;
use Astro\Calculation\HousePlanetMatcher;
use Astro\Calculation\ParsFortuna;

$firstname = trim($_POST['firstname'] ?? '');
$infix = trim($_POST['infix'] ?? '');
$lastname = preg_replace('/\s+/', ' ', trim($_POST['lastname'] ?? ''));
$location = trim($_POST['location'] ?? '');
$date = $_POST['date'] ?? '';
$time = $_POST['time'] ?? '';

// Build full name
$personName = trim($firstname . ' ' . $infix . ' ' . $lastname);
if (empty($personName)) {
    $personName = trim($firstname . ' ' . $lastname);
}

// Validation
if (empty($lastname)) {
    $error = "Achternaam is verplicht";
} elseif (!empty($firstname) && !preg_match('/^[\p{L}\s\-\.\']+$/u', $firstname)) {
    $error = "Ongeldige voornaam";
} elseif (!empty($infix) && !preg_match('/^[\p{L}\s\-\']+/u', $infix)) {
    $error = "Ongeldig tussenvoegsel";
} elseif (!preg_match('/^[\p{L}\s\-\'\(\)\d]+$/u', $lastname)) {
    $error = "Ongeldige achternaam";
} elseif (!preg_match('/^[\p{L}\s\-\.,\']+$/u', $location)) {
    $error = "Ongeldige locatie";
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
    $error = "Ongeldige datum";
} elseif (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
    $error = "Ongeldige tijd";
}

if (!isset($error)) {
    error_log("[Tijd] Starting calculation for: {$personName} at {$location}");
    $timestamp = strtotime("$date $time");
    
    $isUtc = isset($_POST['time_correction_utc']);
    $isLmt = isset($_POST['time_correction_lmt']);
    $timeCorrection = null;
    if ($isUtc) $timeCorrection = 'utc';
    if ($isLmt) $timeCorrection = 'lmt';

    $geoService = new GeocodingService(GOOGLE_API_KEY);
    $geoResult = $geoService->geocode($location);

    if (isset($geoResult['error'])) {
        $error = $geoResult['error'];
        error_log("[Tijd] Geocoding error: {$error} for location: {$location}");
    } else {
        $lat = $geoResult['lat'];
        $lng = $geoResult['lng'];
        
        if ($isUtc) {
            $utcTimestamp = $timestamp;

            // Tijdzone lookup voor lokale weergave (Solaar → normale horoscoop)
            $tzResult = $geoService->getTimezoneId($lat, $lng, $utcTimestamp);
            if (!isset($tzResult['error'])) {
                $astroTime = new AstroTime($tzResult['timezoneId'], $lng);
                $timeResult = $astroTime->getOffset($utcTimestamp);
                $timezoneId = $tzResult['timezoneId'];
                $localTimestamp = $utcTimestamp + $timeResult['offset'];
                $date = date('Y-m-d', $localTimestamp);
                $time = date('H:i:s', $localTimestamp);
                $timeCorrection = null;
            } else {
                // fallback: blijf op UTC als tijdzone lookup faalt
                $timeResult = [
                    'offset' => 0,
                    'source' => 'manual',
                    'label' => 'UTC'
                ];
                $timezoneId = '';
            }
        } elseif ($isLmt) {
            $lmtOffset = (int) round($lng * 240);
            $utcTimestamp = $timestamp - $lmtOffset;
            $timeResult = [
                'offset' => $lmtOffset,
                'source' => 'manual',
                'label' => 'LMT'
            ];
            $timezoneId = '';
        } else {
            $tzResult = $geoService->getTimezoneId($lat, $lng, $timestamp);

            if (isset($tzResult['error'])) {
                $error = $tzResult['error'];
                error_log("[Tijd] Timezone error: {$error} for coords: {$lat},{$lng}");
            } else {
                $astroTime = new AstroTime($tzResult['timezoneId'], $lng);
                $timeResult = $astroTime->getOffset($timestamp);
                $utcTimestamp = $timestamp - $timeResult['offset'];
                $timezoneId = $tzResult['timezoneId'];
            }
        }

        if (!isset($error)) {
            error_log("[Tijd] Timezone offset: {$timeResult['offset']} ({$timeResult['label']}) for {$timezoneId}");

            try {
                $calculator = new HoroscopeCalculator();
                $planetCalculator = new PlanetCalculator();
                $houseCalculator = new HouseCalculator();
                $aspectCalculator = new AspectCalculator();
                
                $planetResult = $planetCalculator->calculateForTimestamp($utcTimestamp);
                
                $houseResult = $houseCalculator->calculateByTimestamp(
                    $utcTimestamp,
                    $lat,
                    $lng,
                    HouseCalculator::HSYS_KOCH
                );

                $ascendant = $houseResult['ascmc']['ascendant']['longitude'];
                $moon = $planetResult['planets']['Moon']['longitude'] ?? 0;
                $sun = $planetResult['planets']['Sun']['longitude'] ?? 0;
                
                $planetResult['planets']['ParsFortuna'] = ParsFortuna::calculateWithSpeed(
                    $ascendant,
                    $moon,
                    $sun
                );

                $planetsForAspects = [];
                foreach ($planetResult['planets'] as $name => $data) {
                    if ($name === 'ParsFortuna') continue;
                    if (isset($data['success']) && $data['success']) {
                        $planetsForAspects[$name] = [
                            'longitude' => $data['longitude']
                        ];
                    }
                }
                $aspectResult = $aspectCalculator->calculate($planetsForAspects, $houseResult);
                
                error_log("[Tijd] Calculation successful: " . count($planetResult['planets']) . " planets, " . count($aspectResult) . " aspects");
                
                $result = [
                    'name' => $personName,
                    'firstname' => $firstname,
                    'infix' => $infix,
                    'lastname' => $lastname,
                    'offset' => $timeResult['offset'],
                    'source' => $timeResult['source'],
                    'label' => $timeResult['label'],
                    'time_correction' => $timeCorrection,
                    'coords' => ['lat' => $lat, 'lng' => $lng],
                    'address' => trim(preg_replace('/\s+/', ' ', preg_replace('/\d{4}\s?[A-Z]{2}|\d{4}/', '', $geoResult['address']))),
                    'location_name' => $location,
                    'timezone' => $timezoneId,
                    'planets' => $planetResult['planets'],
                    'julian_day' => $planetResult['julian_day'],
                    'houses' => $houseResult,
                    'aspects' => $aspectResult,
                    'local_timestamp' => $timestamp,
                    'utc_timestamp' => $utcTimestamp
                ];

                // Solaar: overschrijf local_timestamp met omgerekende lokale tijd
                if ($isUtc && isset($localTimestamp)) {
                    $result['local_timestamp'] = $localTimestamp;
                }

                $housePlanetMatcher = new HousePlanetMatcher();
                $planetsForWheel = $housePlanetMatcher->match(
                    $result['planets'],
                    $result['houses']['houses']
                );
                $houseCuspsForWheel = $housePlanetMatcher->extractHouseCusps($result['houses']['houses']);

                $_SESSION['wheel_data'] = [
                    'name' => $personName,
                    'house_cusps' => $houseCuspsForWheel,
                    'planets' => $planetsForWheel
                ];
                
                // Verwijder oude progression/transit markers bij nieuwe horoscoop
                unset($_SESSION['just_submitted_progressions'], $_SESSION['just_submitted_transits']);
                
                // Preserve natal sun longitude across session overwrites (voor solaar)
                $preservedNatalSunLon = $_SESSION['horoscope']['input']['natal_sun_longitude'] ?? null;

                // Session structuur voor lazy loading
                $_SESSION['horoscope'] = [
                    'input' => [
                        'firstname' => $firstname,
                        'infix' => $infix,
                        'lastname' => $lastname,
                        'birth_date' => $date,
                        'birth_time' => $time,
                        'location_name' => $location,
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'timezone_id' => $timezoneId,
                        'utc_offset' => $timeResult['offset'],
                        'time_correction' => $timeCorrection,
                        'offset_source' => $timeResult['source'],
                        'offset_label' => $timeResult['label'],
                        'natal_sun_longitude' => $preservedNatalSunLon,
                    ],
                    'core' => [
                        'planets' => $result['planets'],
                        'houses' => $result['houses']['houses'],
                        'ascmc' => $result['houses']['ascmc'],
                        'julian_day' => $result['julian_day'],
                    ],
                    'aspects' => null, // Lazy loaded
                ];
                
                // Store natal Sun longitude on first calculation (for future Solaar use)
                if ($_SESSION['horoscope']['input']['natal_sun_longitude'] === null
                    && isset($result['planets']['Sun']['longitude'])) {
                    $_SESSION['horoscope']['input']['natal_sun_longitude'] = $result['planets']['Sun']['longitude'];
                }
                
                // Cleanup solaar prefill na succesvolle solaar berekening
                if (isset($_SESSION['solaar_prefill'])) {
                    unset($_SESSION['solaar_prefill']);
                }

                $mode = 'calculate';
            } catch (\Exception $e) {
                $error = "Berekening mislukt: " . $e->getMessage();
                error_log("[Tijd] Calculation error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
            }
        }
    }
}