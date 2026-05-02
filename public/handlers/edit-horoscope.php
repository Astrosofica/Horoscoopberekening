<?php
/**
 * Edit mode POST handler
 * 
 * Triggered when: POST + save_edit + mode=edit + viewHoroscope exists
 * Exclusion conditions: NOT save_horoscope, NOT calculate_progressions, NOT calculate_transits
 * 
 * Sets: $error (validation errors), updates $viewHoroscope entity
 * Session: Sets flash_success on success
 * Redirect: dashboard.php (via index.php for POST-Redirect-GET)
 * 
 * @var Horoscope|null $viewHoroscope - passed from index.php (must exist for edit mode)
 * @var string|null $error - set on validation/geocoding failure
 */

use Tijd\Geo\GeocodingService;
use Tijd\Time\AstroTime;
use Tijd\Database\HoroscopeRepository;

$firstname = trim($_POST['firstname'] ?? '');
$infix = trim($_POST['infix'] ?? '');
$lastname = preg_replace('/\s+/', ' ', trim($_POST['lastname'] ?? ''));
$location = trim($_POST['location'] ?? '');
$date = $_POST['date'] ?? '';
$time = $_POST['time'] ?? '';

// Validation
if (empty($lastname)) {
    $error = "Achternaam is verplicht";
} elseif (!empty($firstname) && !preg_match('/^[\p{L}\s\-\.\']+$/u', $firstname)) {
    $error = "Ongeldige voornaam";
} elseif (!empty($infix) && !preg_match('/^[\p{L}\s\-\']+/u', $infix)) {
    $error = "Ongeldig tussenvoegsel";
    } elseif (!preg_match('/^[\p{L}\s\-\'\(\)]+$/u', $lastname)) {
    $error = "Ongeldige achternaam";
} elseif (!preg_match('/^[\p{L}\s\-\.,\']+$/u', $location)) {
    $error = "Ongeldige locatie";
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
    $error = "Ongeldige datum";
} elseif (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
    $error = "Ongeldige tijd";
}

if (!isset($error)) {
    // Geocoding
    $geoService = new GeocodingService(GOOGLE_API_KEY);
    $geoResult = $geoService->geocode($location);
    
    if (isset($geoResult['error'])) {
        $error = $geoResult['error'];
    } else {
        $lat = $geoResult['lat'];
        $lng = $geoResult['lng'];
        $timestamp = strtotime("$date $time");
        
        // Timezone lookup
        $isUtc = isset($_POST['time_correction_utc']);
        $isLmt = isset($_POST['time_correction_lmt']);
        $timeCorrection = null;
        if ($isUtc) $timeCorrection = 'utc';
        if ($isLmt) $timeCorrection = 'lmt';
        
        if ($isUtc) {
            $utcOffset = 0;
            $timezoneId = '';
            $offsetSource = 'manual';
            $offsetLabel = 'UTC';
        } elseif ($isLmt) {
            $utcOffset = (int) round($lng * 240);
            $timezoneId = '';
            $offsetSource = 'manual';
            $offsetLabel = 'LMT';
        } else {
            $tzResult = $geoService->getTimezoneId($lat, $lng, $timestamp);
            
            if (isset($tzResult['error'])) {
                $error = $tzResult['error'];
            } else {
                $astroTime = new AstroTime($tzResult['timezoneId'], $lng);
                $timeResult = $astroTime->getOffset($timestamp);
                $utcOffset = $timeResult['offset'];
                $timezoneId = $tzResult['timezoneId'];
                $offsetSource = $timeResult['source'];
                $offsetLabel = $timeResult['label'];
            }
        }
        
        if (!isset($error)) {
            // Update horoscope entity
            $viewHoroscope->setFirstname($firstname ?: null);
            $viewHoroscope->setInfix($infix ?: null);
            $viewHoroscope->setLastname($lastname);
            $viewHoroscope->setBirthDate($date);
            $viewHoroscope->setBirthTime($time);
            $viewHoroscope->setLocationName($location);
            $viewHoroscope->setLatitude($lat);
            $viewHoroscope->setLongitude($lng);
            $viewHoroscope->setTimezoneId($timezoneId);
            $viewHoroscope->setUtcOffset($utcOffset);
            $viewHoroscope->setTimeCorrection($timeCorrection);
            $viewHoroscope->setOffsetSource($offsetSource);
            $viewHoroscope->setOffsetLabel($offsetLabel);
            $viewHoroscope->setFormattedAddress($geoResult['address']);
            
            // Save to database
            $horoscopeRepo = new HoroscopeRepository();
            $horoscopeRepo->update($viewHoroscope);
            
            // Set flash message (redirect happens in index.php)
            $_SESSION['flash_success'] = 'Horoscoop bijgewerkt.';
        }
    }
}

// Note: On error, $error is set and form stays visible with posted data
// FormValues are set below in index.php