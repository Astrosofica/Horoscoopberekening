<?php
/**
 * Transit Events POST handler
 * 
 * Triggered when: POST + calculate_transits
 * Exclusion: NOT triggered by main calculation (exclusion conditions in main handler)
 * 
 * SESSION OVERWRITE PATTERN (from DEEL 2):
 * - PRESERVE progression_events BEFORE overwrite: $existingProgressionEvents = ...
 * - After overwrite: restore progression_events if they existed
 * 
 * Sets: $error (validation errors), $transitEventsResult
 * Session: $_SESSION['horoscope']['transit_events'] = [...]
 * Redirect: Via index.php (POST-Redirect-GET pattern)
 * 
 * @var Horoscope|null $viewHoroscope - may be null (unsaved horoscope)
 * @var string|null $error - set on failure
 * @var array|null $transitEventsResult - set on success
 */

use Tijd\Calculation\HoroscopeCalculator;
use Tijd\Calculation\TransitEventCalculator;

// EERST: capture existing progression_events VOORDAT we session overwrite
$existingProgressionEvents = $_SESSION['horoscope']['progression_events'] ?? null;

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
    if ($existingProgressionEvents !== null) {
        $_SESSION['horoscope']['progression_events'] = $existingProgressionEvents;
    }
}

if (!isset($_SESSION['horoscope']['core'])) {
    $error = "Bereken eerst een horoscoop voordat je transits kunt bekijken.";
} else {
    $transitStartDate = $_POST['transit_start_date'] ?? '';
    $transitEndDate = $_POST['transit_end_date'] ?? '';
    $transitPlanets = array_map('intval', $_POST['transit_planet'] ?? []);
    $radixTargets = array_map('intval', $_POST['radix_target'] ?? []);
    $transitAspects = array_map('intval', $_POST['transit_aspect'] ?? []);
    $includeHouseIngress = isset($_POST['include_house_ingress']);

    if (empty($transitStartDate) || empty($transitEndDate)) {
        $error = "Start- en einddatum zijn verplicht.";
    } elseif (strtotime($transitStartDate) > strtotime($transitEndDate)) {
        $error = "Einddatum moet na startdatum liggen.";
    } elseif (empty($transitPlanets)) {
        $error = "Selecteer ten minste één transitplaneet.";
    } elseif (!$includeHouseIngress && (empty($radixTargets) || empty($transitAspects))) {
        $error = "Selecteer radixpunten en aspecten, of vink huis ingress aan.";
    } else {
        $radixData = [
            'planets' => $_SESSION['horoscope']['core']['planets'],
            'houses' => $_SESSION['horoscope']['core']['houses'],
            'ascmc' => $_SESSION['horoscope']['core']['ascmc'],
        ];

        $transitEventCalc = new TransitEventCalculator();
        $transitEvents = $transitEventCalc->findTransitEvents(
            $radixData,
            $radixData['houses'],
            $transitPlanets,
            $radixTargets,
            $transitAspects,
            $transitStartDate,
            $transitEndDate,
            $includeHouseIngress
        );

        $_SESSION['horoscope']['transit_events'] = [
            'input' => [
                'start_date' => $transitStartDate,
                'end_date' => $transitEndDate,
                'transit_planets' => $transitPlanets,
                'radix_targets' => $radixTargets,
                'aspects' => $transitAspects,
                'include_house_ingress' => $includeHouseIngress,
            ],
            'results' => $transitEvents,
        ];

        $currentTab = 'transits-list';
        $_SESSION['just_submitted_transits'] = true;
    }
}