<?php
/**
 * Progression Events POST handler
 * 
 * Triggered when: POST + calculate_progressions
 * Exclusion: NOT triggered by main calculation (exclusion conditions in main handler)
 * 
 * SESSION OVERWRITE PATTERN (from DEEL 2):
 * - PRESERVE transit_events BEFORE overwrite: $existingTransitEvents = ...
 * - After overwrite: restore transit_events if they existed
 * 
 * Sets: $error (validation errors), $progEventsResult
 * Session: $_SESSION['horoscope']['progression_events'] = [...]
 * Redirect: Via index.php (POST-Redirect-GET pattern)
 * 
 * @var Horoscope|null $viewHoroscope - may be null (unsaved horoscope)
 * @var string|null $error - set on failure
 * @var array|null $progEventsResult - set on success
 */

use Astro\Calculation\HoroscopeCalculator;
use Astro\Calculation\ProgressionEventCalculator;

// EERST: capture existing transit_events VOORDAT we session overwrite
$existingTransitEvents = $_SESSION['horoscope']['transit_events'] ?? null;

// Check voor saved horoscope: populate session if missing
if (!isset($_SESSION['horoscope']['core']) && $viewHoroscope) {
    $calculator = new HoroscopeCalculator();
    $calcResult = $calculator->calculate($viewHoroscope);
    $_SESSION['horoscope'] = [
        'input' => [
            'firstname' => $viewHoroscope->getFirstname(),
            'infix' => $viewHoroscope->getInfix(),
            'lastname' => $viewHoroscope->getLastname(),
            'birth_date' => $viewHoroscope->getBirthDate(),
            'birth_time' => $viewHoroscope->getBirthTime(),
            'location_name' => $viewHoroscope->getLocationName(),
            'latitude' => $viewHoroscope->getLatitude(),
            'longitude' => $viewHoroscope->getLongitude(),
            'timezone_id' => $viewHoroscope->getTimezoneId(),
            'utc_offset' => $viewHoroscope->getUtcOffset(),
            'time_correction' => $viewHoroscope->getTimeCorrection(),
            'offset_source' => $viewHoroscope->getOffsetSource(),
            'offset_label' => $viewHoroscope->getOffsetLabel(),
            'slug' => $viewHoroscope->getSlug(),
        ],
        'core' => [
            'planets' => $calcResult['planets'],
            'houses' => $calcResult['houses']['houses'],
            'ascmc' => $calcResult['houses']['ascmc'],
            'julian_day' => $calcResult['julian_day'],
        ],
        'aspects' => null,
    ];
    if ($existingTransitEvents !== null) {
        $_SESSION['horoscope']['transit_events'] = $existingTransitEvents;
    }
}

if (!isset($_SESSION['horoscope']['core'])) {
    $error = "Eerst een horoscoop berekenen voordat progressies kunnen worden berekend.";
} else {
    $startDate = $_POST['prog_start_date'] ?? '';
    $endDate = $_POST['prog_end_date'] ?? '';
    $progressivePlanets = array_map('intval', $_POST['progressive_planet'] ?? []);
    $radixTargets = array_map('intval', $_POST['radix_target'] ?? []);
    $aspects = array_map('intval', $_POST['aspect_type'] ?? []);
    $includeHouseIngress = isset($_POST['include_house_ingress']);
    $includeSignIngress = isset($_POST['include_sign_ingress']);
    
    if (empty($startDate) || empty($endDate)) {
        $error = "Start- en einddatum zijn verplicht.";
    } elseif (strtotime($startDate) > strtotime($endDate)) {
        $error = "Einddatum moet na startdatum liggen.";
    } elseif (empty($progressivePlanets) && empty($radixTargets) && empty($aspects)) {
        $error = "Selecteer minimaal één planeet, radix target of aspect.";
    } else {
        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate) + 86400;
        
        $radixData = [
            'planets' => $_SESSION['horoscope']['core']['planets'],
            'houses' => $_SESSION['horoscope']['core']['houses'],
            'ascmc' => $_SESSION['horoscope']['core']['ascmc'],
        ];
        
        $birthUtcTimestamp = strtotime($_SESSION['horoscope']['input']['birth_date'] . ' ' . $_SESSION['horoscope']['input']['birth_time']) 
            - $_SESSION['horoscope']['input']['utc_offset'];
        
        $progEventCalculator = new ProgressionEventCalculator();
        
        try {
            $progEvents = $progEventCalculator->calculateEvents(
                $radixData,
                $progressivePlanets,
                $radixTargets,
                $aspects,
                $startTimestamp,
                $endTimestamp,
                $includeHouseIngress,
                $includeSignIngress,
                $_SESSION['horoscope']['input']['latitude'],
                $_SESSION['horoscope']['input']['longitude'],
                $birthUtcTimestamp,
                $_SESSION['horoscope']['input']['utc_offset']
            );
            
            $_SESSION['horoscope']['progression_events'] = [
                'input' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'progressive_planets' => $progressivePlanets,
                    'radix_targets' => $radixTargets,
                    'aspects' => $aspects,
                    'include_house_ingress' => $includeHouseIngress,
                    'include_sign_ingress' => $includeSignIngress,
                ],
                'results' => $progEvents,
            ];
            
            $progEventsResult = $progEvents;
            $currentTab = 'progressions-list';
            
            // Markeer dat we net een progression submit hebben gedaan
            $_SESSION['just_submitted_progressions'] = true;
            
        } catch (\Exception $e) {
            $error = "Berekening mislukt: " . $e->getMessage();
            error_log("[Tijd] Progression error: " . $e->getMessage());
        }
    }
}