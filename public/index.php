<?php
// Debug: log alle requests
file_put_contents(__DIR__ . '/../var/log/debug.log', date('Y-m-d H:i:s') . " REQUEST: " . $_SERVER['REQUEST_METHOD'] . " POST: " . json_encode($_POST) . "\n", FILE_APPEND);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

define('GOOGLE_API_KEY', $_ENV['GOOGLE_API_KEY'] ?? '');
define('ERROR_LOG_PATH', __DIR__ . '/../var/log/error.log');
ini_set('error_log', ERROR_LOG_PATH);

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateLimit = 10;
$ratePeriod = 60;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['save_horoscope'])) {
    if (!isset($_SESSION['requests'])) {
        $_SESSION['requests'] = [];
    }
    
    $_SESSION['requests'][$ip] = array_filter(
        $_SESSION['requests'][$ip] ?? [],
        fn($time) => $time > time() - $ratePeriod
    );
    
    if (count($_SESSION['requests'][$ip] ?? []) >= $rateLimit) {
        $error = "Te veel verzoeken. Wacht " . $ratePeriod . " seconden.";
        error_log("[Tijd] Rate limit exceeded for IP: $ip");
    } else {
        $_SESSION['requests'][$ip][] = time();
    }
}

use Tijd\Auth\AuthService;
use Tijd\Database\HoroscopeRepository;
use Tijd\Geo\GeocodingService;
use Tijd\Time\AstroTime;
use Tijd\Calculation\PlanetCalculator;
use Tijd\Calculation\HouseCalculator;
use Tijd\Calculation\AspectCalculator;
use Tijd\Calculation\HousePlanetMatcher;
use Tijd\Calculation\ParsFortuna;
use Tijd\Calculation\HoroscopeCalculator;
use Tijd\Calculation\ProgressionCalculator;
use Tijd\Calculation\ProgressionEventCalculator;
use Tijd\Helpers\Formatter;
use Tijd\Glyph\SymbolGlyph;

$authService = new AuthService();
$isLoggedIn = $authService->isLoggedIn();
$currentUser = $isLoggedIn ? $authService->getCurrentUser() : null;

$mode = 'new';
$horoscopeSlug = $_GET['h'] ?? null;
$isEdit = isset($_GET['edit']);
$viewHoroscope = null;
$result = null;
$error = null;
$editSlug = null;

if ($horoscopeSlug) {
    if ($isEdit) {
        $mode = 'edit';
        $editSlug = $horoscopeSlug;
    } else {
        $mode = 'view';
    }
    
    if ($isLoggedIn) {
        $horoscopeRepo = new HoroscopeRepository();
        $viewHoroscope = $horoscopeRepo->findBySlugAndUserId($horoscopeSlug, $currentUser->getId());
        
        if (!$viewHoroscope) {
            $_SESSION['flash_error'] = 'Horoscoop niet gevonden.';
            header('Location: dashboard.php');
            exit;
        }
    }
}

// ===========================================================================
// POST HANDLER - New calculation form submission
// ===========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['lastname']) && !isset($_POST['save_horoscope'])) {
    $firstname = trim($_POST['firstname'] ?? '');
    $infix = trim($_POST['infix'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
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
    } elseif (!empty($infix) && !preg_match('/^[\p{L}\s\-]+$/u', $infix)) {
        $error = "Ongeldig tussenvoegsel";
    } elseif (!preg_match('/^[\p{L}]+$/', $lastname)) {
        $error = "Ongeldige achternaam";
    } elseif (!preg_match('/^[\p{L}\s\-\.,]+$/u', $location)) {
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
                $timeResult = [
                    'offset' => 0,
                    'source' => 'manual',
                    'label' => 'UTC'
                ];
                $timezoneId = '';
            } elseif ($isLmt) {
                $lmtOffset = (int) round($lng * 240);
                $utcTimestamp = $timestamp - $lmtOffset;
                $timeResult = [
                    'offset' => $lmtOffset,
                    'source' => 'lmt',
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
                        'address' => $geoResult['address'],
                        'timezone' => $timezoneId,
                        'planets' => $planetResult['planets'],
                        'julian_day' => $planetResult['julian_day'],
                        'houses' => $houseResult,
                        'aspects' => $aspectResult,
                        'local_timestamp' => $timestamp,
                        'utc_timestamp' => $utcTimestamp
                    ];

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
                    
// Behoud progression_events als die al bestaat
                    $existingProgressionEvents = $_SESSION['horoscope']['progression_events'] ?? null;
                    
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
                        ],
                        'core' => $result,
                        'aspects' => null, // Lazy loaded
                    ];
                    
                    // Herstel progression_events indien die bestond
                    if ($existingProgressionEvents !== null) {
                        $_SESSION['horoscope']['progression_events'] = $existingProgressionEvents;
                    }
                    
                    $mode = 'calculate';
                } catch (\Exception $e) {
                    $error = "Berekening mislukt: " . $e->getMessage();
                    error_log("[Tijd] Calculation error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
                }
            }
        }
    }
}

// ===========================================================================
// POST HANDLER - Progression Events Form
// ===========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calculate_progressions'])) {
    // Debug naar bestand
    file_put_contents(__DIR__ . '/../var/log/debug.log', date('Y-m-d H:i:s') . " POST received\n", FILE_APPEND);
    file_put_contents(__DIR__ . '/../var/log/debug.log', "POST data: " . json_encode($_POST) . "\n", FILE_APPEND);
    
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
                
                // Debug logging na POST
                error_log("[Tijd] POST Handler - Saved progression_events: " . json_encode([
                    'progressive_planets' => $progressivePlanets,
                    'radix_targets' => $radixTargets,
                    'aspects' => $aspects
                ]));
                
                $progEventsResult = $progEvents;
                $currentTab = 'progressions-list';
                
                // Markeer dat we net een progression submit hebben gedaan
                $_SESSION['just_submitted_progressions'] = true;
                
                // Redirect naar GET om browser resubmit te voorkomen
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            } catch (\Exception $e) {
                $error = "Berekening mislukt: " . $e->getMessage();
                error_log("[Tijd] Progression error: " . $e->getMessage());
            }
        }
    }
}

// ===========================================================================
// TAB SWITCH LOGIC - Lazy Loading
// ===========================================================================
// Check of user een specifieke tab wil bekijken (?tab=aspects)
$requestedTab = $_GET['tab'] ?? null;

if (!isset($currentTab)) {
    $currentTab = 'calculate';
}

// Check of we net een progression submit hebben gedaan (moet VÓÓR viewHoroscope blok!)
if (isset($_SESSION['just_submitted_progressions'])) {
    $currentTab = 'progressions-list';
    // Marker wordt verwijderd in viewHoroscope blok
}

if (($isEdit || $mode === 'view') && $viewHoroscope && !isset($_POST['lastname'])) {
    $_POST['firstname'] = $viewHoroscope->getFirstname();
    $_POST['infix'] = $viewHoroscope->getInfix();
    $_POST['lastname'] = $viewHoroscope->getLastname();
    $_POST['date'] = $viewHoroscope->getBirthDate();
    $_POST['time'] = $viewHoroscope->getBirthTime();
    $_POST['location'] = $viewHoroscope->getLocationName();
    
    $tc = $viewHoroscope->getTimeCorrection();
    if ($tc === 'utc') {
        $_POST['time_correction_utc'] = '1';
    } elseif ($tc === 'lmt') {
        $_POST['time_correction_lmt'] = '1';
    }
}

if ($mode === 'view' && $viewHoroscope && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check of we net een progression submit hebben gedaan
    $justDidProgression = isset($_SESSION['just_submitted_progressions']);
    
    // Reset lazy tabs bij laden opgeslagen horoscoop
    unset($_SESSION['horoscope']['aspects']);
    
    $calculator = new HoroscopeCalculator();
    $result = $calculator->calculate($viewHoroscope);
    $wheelData = $calculator->prepareWheelData($result);
    $_SESSION['wheel_data'] = $wheelData;
    
    // Behoud progression_events als we net een submit hebben gedaan
    $existingProgressionEvents = $justDidProgression ? ($_SESSION['horoscope']['progression_events'] ?? null) : null;
    
    // Session structuur voor lazy loading
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
        ],
        'core' => [
            'planets' => $result['planets'],
            'houses' => $result['houses']['houses'],
            'ascmc' => $result['houses']['ascmc'],
            'julian_day' => $result['julian_day'],
        ],
        'aspects' => null, // Lazy loaded
    ];
    
    // Herstel progression_events als we net een submit hadden
    if ($existingProgressionEvents !== null) {
        $_SESSION['horoscope']['progression_events'] = $existingProgressionEvents;
    } else {
        // Verwijder progression_events bij normaal laden opgeslagen horoscoop
        unset($_SESSION['horoscope']['progression_events']);
    }
    
    // Verwijder marker als die gezet was (tab is al gezet in tab switch logic)
    if ($justDidProgression) {
        unset($_SESSION['just_submitted_progressions']);
    }
}

// ===========================================================================
// TAB SWITCH LOGIC - Lazy Loading (vervolg)
// ===========================================================================
$hasResult = $result !== null;
$formDisabled = ($mode === 'view');

// Default tab bij resultaat is horoscope, tenzij andere tab gevraagd
if ($hasResult && $mode !== 'edit' && $currentTab !== 'progressions-list') {
    $currentTab = 'horoscope';
    
    // Lazy tabs worden hieronder verwerkt
    if ($requestedTab) {
        switch ($requestedTab) {
            case 'aspects':
                // LAZY: Aspecten tab - alleen berekenen als core data bestaat
                if (isset($_SESSION['horoscope']['core'])) {
                    // Al berekend? Gebruik cached result
                    if (!isset($_SESSION['horoscope']['aspects']) || empty($_SESSION['horoscope']['aspects'])) {
                        // Bereken aspecten
                        $aspectCalculator = new AspectCalculator();
                        
                        // Planets filteren voor aspecten (zonder ParsFortuna)
                        $planetsForAspects = [];
                        foreach ($_SESSION['horoscope']['core']['planets'] as $name => $data) {
                            if ($name === 'ParsFortuna') continue;
                            if (isset($data['success']) && $data['success']) {
                                $planetsForAspects[$name] = ['longitude' => $data['longitude']];
                            }
                        }
                        
                        // Houses structuur herbouwen zoals oorspronkelijk (met houses[1], houses[10], etc.)
                        $housesForAspects = [
                            'houses' => $_SESSION['horoscope']['core']['houses'],
                            'ascmc' => $_SESSION['horoscope']['core']['ascmc'] ?? [],
                        ];
                        
                        $_SESSION['horoscope']['aspects'] = $aspectCalculator->calculate(
                            $planetsForAspects,
                            $housesForAspects
                        );
                    }
                    
                    // Gebruik session data voor result
                    $aspectResult = $_SESSION['horoscope']['aspects'];
                    
                    // Update result array voor template compatibility
                    if ($result !== null) {
                        $result['aspects'] = $aspectResult;
                    }
                    
                    $currentTab = 'aspects';
                }
                break;
                
            case 'progressions':
                // LAZY: Progressies tab - alleen berekenen als core en input data bestaat
                if (isset($_SESSION['horoscope']['core']) && isset($_SESSION['horoscope']['input'])) {
                    // Al berekend? Gebruik cached result
                    if (!isset($_SESSION['horoscope']['progressions']) || empty($_SESSION['horoscope']['progressions'])) {
                        // Bereken secundaire progressies voor vandaag
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
                    
                    // Gebruik session data voor result
                    $progressionsResult = $_SESSION['horoscope']['progressions'];
                    
                    // Update result array voor template compatibility
                    if ($result !== null) {
                        $result['progressions'] = $progressionsResult;
                    }
                    
                    $currentTab = 'progressions';
                }
                break;
                
            case 'progressions-list':
                // Show cached progression events if available
                if (isset($_SESSION['horoscope']['progression_events']['results'])) {
                    $progEventsResult = $_SESSION['horoscope']['progression_events']['results'];
                }
                $currentTab = 'progressions-list';
                break;
                
            case 'antiscia':
                // Spiegelpunten (Jan de Jong) - alleen berekenen als core data bestaat
                if (isset($_SESSION['horoscope']['core'])) {
                    if (!isset($_SESSION['horoscope']['antiscia']) || empty($_SESSION['horoscope']['antiscia'])) {
                        $mirrorCalculator = new \Tijd\Calculation\MirrorPointCalculator();
                        $_SESSION['horoscope']['antiscia'] = $mirrorCalculator->calculate(
                            $_SESSION['horoscope']['core']
                        );
                    }
                    $result['antiscia'] = $_SESSION['horoscope']['antiscia'];
                    $currentTab = 'antiscia';
                }
                break;
        }
    }
}
// ===========================================================================
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $result ? htmlspecialchars($result['name']) . ' - ' : '' ?>Horoscoopberekening</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="app-wrapper">
    <header class="card card--header card--header--app">
        <div class="header-content">
            <h1><a href="index.php" class="header-brand"><?= APP_NAME ?></a></h1>
            <nav class="header-nav">
                <?php if ($isLoggedIn): ?>
                    <a href="dashboard.php">Dashboard</a>
                    <span class="header-user"><?= htmlspecialchars($currentUser->getEmail()) ?></span>
                    <a href="logout.php" class="header-logout">Uitloggen</a>
                <?php else: ?>
                    <a href="login.php">Inloggen</a>
                    <a href="register.php">Registreren</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    
    <div class="app-layout">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <main class="app-content">
            <?php if ($flashSuccess): ?>
                <div class="flash flash--success"><?= htmlspecialchars($flashSuccess) ?></div>
            <?php endif; ?>

            <?php if ($flashError): ?>
                <div class="flash flash--error"><?= htmlspecialchars($flashError) ?></div>
            <?php endif; ?>

            <section id="tab-calculate" class="tab-content<?= $currentTab !== 'calculate' ? ' tab-content--hidden' : '' ?>">
                
                <?php if ($mode === 'edit'): ?>
                    <p class="intro-text">Je bewerkt de horoscoop van <strong><?= htmlspecialchars($viewHoroscope->getName()) ?></strong>.</p>
                <?php endif; ?>
                
                <div class="card card--large card--form">
                    <h2>Geboortegegevens</h2>
                    <form method="POST">
                        <div class="form-row name-row">
                            <div class="form-group form-group--firstname">
                                <label for="firstname">Voornaam</label>
                                <input type="text" id="firstname" name="firstname" placeholder="Jan" value="<?= htmlspecialchars($_POST['firstname'] ?? '') ?>"<?= $formDisabled ? ' disabled' : '' ?>>
                            </div>
                            <div class="form-group form-group--infix">
                                <label for="infix">Tussenv.</label>
                                <input type="text" id="infix" name="infix" placeholder="van" value="<?= htmlspecialchars($_POST['infix'] ?? '') ?>"<?= $formDisabled ? ' disabled' : '' ?>>
                            </div>
                            <div class="form-group form-group--lastname">
                                <label for="lastname">Achternaam <span class="required">*</span></label>
                                <input type="text" id="lastname" name="lastname" placeholder="Berg" value="<?= htmlspecialchars($_POST['lastname'] ?? '') ?>" required<?= $formDisabled ? ' disabled' : '' ?>>
                            </div>
                        </div>

                        <div class="form-row half">
                            <div class="form-group">
                                <label for="date">Datum</label>
                                <input type="date" id="date" name="date" value="<?= htmlspecialchars($_POST['date'] ?? '') ?>" required<?= $formDisabled ? ' disabled' : '' ?>>
                            </div>
                            <div class="form-group">
                                <label for="time">Tijd (lokaal)</label>
                                <input type="time" id="time" name="time" value="<?= htmlspecialchars($_POST['time'] ?? '') ?>" step="1" required<?= $formDisabled ? ' disabled' : '' ?>>
                            </div>
                        </div>

                        <div class="form-row full">
                            <div class="form-group">
                                <label for="location">Geboorteplaats</label>
                                <input type="text" id="location" name="location" placeholder="Bijv. Amsterdam, Nederland" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" required<?= $formDisabled ? ' disabled' : '' ?>>
                            </div>
                        </div>

                        <div class="form-row full">
                            <div class="form-group">
                                <label>Tijdcorrectie</label>
                                <div class="checkbox-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="time_correction_utc" value="1" <?= isset($_POST['time_correction_utc']) ? 'checked' : '' ?> onchange="document.querySelector('input[name=time_correction_lmt]').checked = false;"<?= $formDisabled ? ' disabled' : '' ?>>
                                        Ingevoerde tijd is UTC
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="time_correction_lmt" value="1" <?= isset($_POST['time_correction_lmt']) ? 'checked' : '' ?> onchange="document.querySelector('input[name=time_correction_utc]').checked = false;"<?= $formDisabled ? ' disabled' : '' ?>>
                                        Ingevoerde tijd is LMT/WPT
                                    </label>
                                </div>
                                <small class="form-hint">Vink aan als de ingevoerde tijd al UTC of Lokale Mean Time is.</small>
                            </div>
                        </div>

                        <?php if (!$formDisabled): ?>
                        <div class="form-submit">
                            <button type="submit"><?= $mode === 'edit' ? 'Opnieuw berekenen' : 'Horoscoop berekenen' ?></button>
                        </div>
                        <?php else: ?>
                        <div class="form-submit">
                            <a href="?h=<?= htmlspecialchars($horoscopeSlug) ?>&edit" class="btn btn--primary">Horoscoop bewerken</a>
                        </div>
                        <?php endif; ?>
                    </form>

                    <?php if ($error): ?>
                        <p class="form-error"><?= htmlspecialchars($error) ?></p>
                    <?php endif; ?>
                </div>
            </section>

            <?php if ($result): ?>
                <?php
                $localDateTime = Formatter::formatDutchDateTime($result['local_timestamp']);
                $utcDateTime = Formatter::formatDutchDateTime($result['utc_timestamp']);
                ?>
                
                <section id="tab-horoscope" class="tab-content<?= $currentTab !== 'horoscope' ? ' tab-content--hidden' : '' ?>">
                    <?php if ($isLoggedIn && $mode !== 'view'): ?>
                        <div class="card card--save">
                            <form method="POST" action="horoscope/save.php<?= $editSlug ? '?replace=' . htmlspecialchars($editSlug) : '' ?>">
                                <?= csrfField() ?>
                                <input type="hidden" name="firstname" value="<?= htmlspecialchars($result['firstname'] ?? '') ?>">
                                <input type="hidden" name="infix" value="<?= htmlspecialchars($result['infix'] ?? '') ?>">
                                <input type="hidden" name="lastname" value="<?= htmlspecialchars($result['lastname'] ?? '') ?>">
                                <input type="hidden" name="birth_date" value="<?= htmlspecialchars(date('Y-m-d', $result['local_timestamp'])) ?>">
                                <input type="hidden" name="birth_time" value="<?= htmlspecialchars(date('H:i:s', $result['local_timestamp'])) ?>">
                                <input type="hidden" name="location_name" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                                <input type="hidden" name="latitude" value="<?= $result['coords']['lat'] ?>">
                                <input type="hidden" name="longitude" value="<?= $result['coords']['lng'] ?>">
                                <input type="hidden" name="timezone_id" value="<?= htmlspecialchars($result['timezone']) ?>">
                                <input type="hidden" name="utc_offset" value="<?= $result['offset'] ?>">
                                <input type="hidden" name="time_correction" value="<?= htmlspecialchars($result['time_correction'] ?? '') ?>">
                                <input type="hidden" name="offset_source" value="<?= htmlspecialchars($result['source'] ?? '') ?>">
                                <input type="hidden" name="offset_label" value="<?= htmlspecialchars($result['label'] ?? '') ?>">
                                <input type="hidden" name="formatted_address" value="<?= htmlspecialchars($result['address']) ?>">
                                <input type="hidden" name="house_system" value="K">
                                <button type="submit" name="save_horoscope" class="btn btn--save"><?= $editSlug ? 'Wijzigingen opslaan' : 'Opslaan in mijn horoscopen' ?></button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <div class="card card--large">
                        <h2>Geboortegegevens</h2>
                        <div class="birth-info-row">
                            <span class="birth-info-label">Naam:</span>
                            <span class="birth-info-value"><?= htmlspecialchars($result['name']) ?></span>
                        </div>
                        <div class="birth-info-row">
                            <span class="birth-info-label">Geboortemoment:</span>
                            <span class="birth-info-value"><?= $localDateTime['date'] ?>, <?= $localDateTime['time'] ?> (<?= $result['label'] ?>)</span>
                        </div>
                        <div class="birth-info-row">
                            <span class="birth-info-label">Locatie:</span>
                            <span class="birth-info-value"><?= htmlspecialchars($result['address']) ?> <span class="coordinates">(<?= Formatter::formatLat($result['coords']['lat']) ?>, <?= Formatter::formatLon($result['coords']['lng']) ?>)</span></span>
                        </div>
                        <div class="birth-info-row">
                            <span class="birth-info-label">GMT/UTC:</span>
                            <span class="birth-info-value"><?= $utcDateTime['date'] ?>, <?= $utcDateTime['time'] ?> GMT</span>
                        </div>
                    </div>

                    <div class="wheel-container">
                        <img src="./Wheel/wheel.php" alt="Astrologisch Radix">
                    </div>
                </section>

                <section id="tab-planetshouses" class="tab-content tab-content--hidden">
                    <div class="houses-planets-container">
                        <div class="card card--planets">
                            <table>
                                <tr>
                                    <th colspan="3">Planeetposities</th>
                                </tr>
                                <?php foreach ($result['planets'] as $name => $data): ?>
                                    <tr>
                                        <td class="text-center"><span class="astro-glyph"><?= SymbolGlyph::getPlanetGlyphByName($name) ?></span></td>
                                        <td class="text-center">
                                            <?php if (isset($data['success']) && $data['success']): ?>
                                                <?= Formatter::formatLongitudeWithGlyph($data['longitude']) ?>
                                            <?php else: ?>
                                                <span class="text-error">Fout</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if (isset($data['success']) && $data['success']): ?>
                                                <?php if ($data['speed_longitude'] < 0): ?>
                                                    <span class="astro-glyph"><?= SymbolGlyph::getRetrogradeGlyph() ?></span>
                                                <?php else: ?>
                                                    D
                                                <?php endif; ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                            <?php endforeach; ?>
                        </table>
                        </div>

                        <div class="card card--houses">
                            <table>
                                <tr>
                                    <th colspan="2">Huizensysteem: <?= htmlspecialchars($result['houses']['systemName']) ?></th>
                                </tr>
                                <?php foreach ($result['houses']['houses'] as $houseNum => $house): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($house['name']) ?></td>
                                        <td class="text-center"><?= Formatter::formatLongitudeWithGlyph($house['longitude']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                </section>

                <section id="tab-aspects" class="tab-content tab-content--hidden">
                    <div class="card card--aspects">
                        <h4>Aspecten (<?= count($result['aspects']) ?> totaal)</h4>
                        <table>
                            <tr>
                                <th>Planeet 1</th>
                                <th></th>
                                <th>Planeet 2</th>
                                <th>Orb</th>
                                <th>Positie 1</th>
                                <th>Positie 2</th>
                            </tr>
                            <?php foreach ($result['aspects'] as $aspect): ?>
                                <tr class="<?= $aspect->isDominant ? 'row--dominant' : '' ?>">
                                    <td class="text-center"><span class="astro-glyph"><?= SymbolGlyph::getPlanetGlyphByName($aspect->planet1Name) ?></span></td>
                                    <td class="text-center"><span class="astro-glyph"><?= SymbolGlyph::getAspectGlyph($aspect->aspectDegrees) ?></span></td>
                                    <td class="text-center"><span class="astro-glyph"><?= SymbolGlyph::getPlanetGlyphByName($aspect->planet2Name) ?></span></td>
                                    <td class="text-center"><?= round($aspect->orb, 2) ?>°</td>
                                    <td class="text-center"><?= Formatter::formatLongitudeWithGlyph($aspect->planet1Longitude) ?></td>
                                    <td class="text-center"><?= Formatter::formatLongitudeWithGlyph($aspect->planet2Longitude) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                        <div class="aspecten-toggle-container">
                            <label class="aspecten-toggle-label">
                                <input type="checkbox" id="toggle-dominant-aspects" onchange="toggleDominantAspects()">
                                Toon de dominante aspecten:
                            </label>
                        </div>
                    </div>
                </section>

                <?php if (isset($result['progressions'])): ?>
                <section id="tab-progressions" class="tab-content tab-content--hidden">
                    <div class="card card--progressions">
                        <h4>Secundaire Progressies</h4>
                        <p class="progressions-info">
                            Leeftijd: <?= Formatter::formatProgressAge($result['progressions']['progress_days']) ?>
                            <span class="progressions-decimal">(<?= round($result['progressions']['progress_days'], 2) ?>)</span>
                        </p>
                        <table>
                            <tr>
                                <th>Planeet</th>
                                <th>Positie</th>
                                <th>Huis</th>
                                <th>Richting</th>
                            </tr>
                            <?php foreach ($result['progressions']['planets'] as $name => $data): ?>
                                <?php if ($name === 'Ascendant' || $name === 'MC'): ?>
                                    <tr class="row--axis">
                                        <td class="text-center"><?= htmlspecialchars($name) ?></td>
                                        <td class="text-center"><?= Formatter::formatLongitudeWithGlyph($data['longitude']) ?></td>
                                        <td class="text-center"><?= $data['house'] ?></td>
                                        <td class="text-center"><?= $data['direction'] ?></td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <td class="text-center"><span class="astro-glyph"><?= SymbolGlyph::getPlanetGlyphByName($name) ?></span></td>
                                        <td class="text-center"><?= Formatter::formatLongitudeWithGlyph($data['longitude']) ?></td>
                                        <td class="text-center"><?= $data['house'] ?></td>
                                        <td class="text-center">
                                            <?php if ($data['direction'] === 'R'): ?>
                                                <span class="astro-glyph"><?= SymbolGlyph::getRetrogradeGlyph() ?></span>
                                            <?php else: ?>
                                                <?= $data['direction'] ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </section>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['horoscope']['core'])): ?>
                <section id="tab-progressions-list" class="tab-content<?= $currentTab !== 'progressions-list' ? ' tab-content--hidden' : '' ?>">
                    <div class="card card--large card--progression-events">
                        <h2>Progressie Events</h2>
<?php
                        // Haal huidige selecties op
                        $selProg = $_SESSION['horoscope']['progression_events']['input']['progressive_planets'] ?? [];
                        $selAspects = $_SESSION['horoscope']['progression_events']['input']['aspects'] ?? [];
                        $selRadix = $_SESSION['horoscope']['progression_events']['input']['radix_targets'] ?? [];
                        
                        // Haal resultaten uit session (na redirect)
                        $progEventsResult = $_SESSION['horoscope']['progression_events']['results'] ?? null;
                        
                        // Debug logging
                        error_log("[Tijd] Form Render - Reading progression_events: " . json_encode([
                            'progressive_planets' => $selProg,
                            'aspects' => $selAspects,
                            'radix_targets' => $selRadix,
                            'results_count' => count($progEventsResult ?? [])
                        ]));
                        
                        // Bepaal toggle states (afleiden uit selectie)
                        $allProgressive = count($selProg) === 10;
                        $allAspects = count($selAspects) === 8;
                        $allRadix = count($selRadix) === 13;
                        ?>
                        <form method="POST" class="progression-form">
                            <div class="progression-column progression-column--tijdvak">
                                <h4>Tijdvak</h4>
                                <div class="progression-datepicker">
                                    <label>Start:<br><input type="date" name="prog_start_date" id="prog_start_date" value="<?= htmlspecialchars($_SESSION['horoscope']['progression_events']['input']['start_date'] ?? date('Y-01-01')) ?>"></label>
                                    <label>Eind:<br><input type="date" name="prog_end_date" id="prog_end_date" value="<?= htmlspecialchars($_SESSION['horoscope']['progression_events']['input']['end_date'] ?? date('Y-12-31')) ?>"></label>
                                </div>
                                <div class="section-divider"></div>
                                <div class="progression-sectie">
                                    <h5>Selectie</h5>
                                    <div class="progression-quickdates">
                                        <button type="button" id="quick-calyear" onclick="quickCalendarYear()" class="progression-quickbtn">📅 Kalenderjaar</button>
                                        <button type="button" id="quick-twoyear" onclick="quickTwoYears()" class="progression-quickbtn">📅 Twee jaar</button>
                                    </div>
                                </div>
                                <div class="section-divider"></div>
                                <div class="progression-sectie">
                                    <h5>Opties</h5>
                                    <div class="progression-options">
                                        <label class="progression-checkbox-label">
                                            <input type="checkbox" name="include_house_ingress" <?= isset($_SESSION['horoscope']['progression_events']['input']['include_house_ingress']) && $_SESSION['horoscope']['progression_events']['input']['include_house_ingress'] ? 'checked' : '' ?>> Huis ingress
                                        </label>
                                        <label class="progression-checkbox-label">
                                            <input type="checkbox" name="include_sign_ingress" <?= isset($_SESSION['horoscope']['progression_events']['input']['include_sign_ingress']) && $_SESSION['horoscope']['progression_events']['input']['include_sign_ingress'] ? 'checked' : '' ?>> Teken ingress
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="progression-column progression-column--planets">
                                <h4>Progressief</h4>
                                <label class="toggle-all">
                                    <input type="checkbox" id="toggle-progressive" onchange="toggleAllGroup('progressive_planet[]', this)" <?= $allProgressive ? 'checked' : '' ?>>
                                    Alle
                                </label>
                                <?php for ($i = 0; $i <= 9; $i++): ?>
                                    <label>
                                        <input type="checkbox" name="progressive_planet[]" value="<?= $i ?>" onchange="checkToggleState('progressive_planet[]', 'toggle-progressive', 10)" <?= in_array($i, $selProg) ? 'checked' : '' ?>>
                                        <span class="astro-glyph"><?= SymbolGlyph::getPlanetGlyphByIndex($i) ?></span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                            
                            <div class="progression-column progression-column--aspects">
                                <h4>Aspecten</h4>
                                <label class="toggle-all">
                                    <input type="checkbox" id="toggle-aspects" onchange="toggleAllGroup('aspect_type[]', this)" <?= $allAspects ? 'checked' : '' ?>>
                                    Alle
                                </label>
                                <?php 
                                $aspectOptions = [0, 45, 60, 90, 120, 135, 150, 180];
                                foreach ($aspectOptions as $aspDeg): ?>
                                    <label>
                                        <input type="checkbox" name="aspect_type[]" value="<?= $aspDeg ?>" onchange="checkToggleState('aspect_type[]', 'toggle-aspects', 8)" <?= in_array($aspDeg, $selAspects) ? 'checked' : '' ?>>
                                        <span class="astro-glyph"><?= SymbolGlyph::getAspectGlyph($aspDeg) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="progression-column progression-column--radix">
                                <h4>Radix</h4>
                                <label class="toggle-all">
                                    <input type="checkbox" id="toggle-radix" onchange="toggleAllGroup('radix_target[]', this)" <?= $allRadix ? 'checked' : '' ?>>
                                    Alle
                                </label>
                                <?php 
                                $radixPlanetLabels = [
                                    0 => ['glyph' => SymbolGlyph::getPlanetGlyphByIndex(0), 'name' => 'Zon'],
                                    1 => ['glyph' => SymbolGlyph::getPlanetGlyphByIndex(1), 'name' => 'Maan'],
                                    2 => ['glyph' => SymbolGlyph::getPlanetGlyphByIndex(2), 'name' => 'Mercurius'],
                                    3 => ['glyph' => SymbolGlyph::getPlanetGlyphByIndex(3), 'name' => 'Venus'],
                                    4 => ['glyph' => SymbolGlyph::getPlanetGlyphByIndex(4), 'name' => 'Mars'],
                                    5 => ['glyph' => SymbolGlyph::getPlanetGlyphByIndex(5), 'name' => 'Jupiter'],
                                    6 => ['glyph' => SymbolGlyph::getPlanetGlyphByIndex(6), 'name' => 'Saturnus'],
                                    7 => ['glyph' => SymbolGlyph::getPlanetGlyphByIndex(7), 'name' => 'Uranus'],
                                    8 => ['glyph' => SymbolGlyph::getPlanetGlyphByIndex(8), 'name' => 'Neptunus'],
                                    9 => ['glyph' => SymbolGlyph::getPlanetGlyphByIndex(9), 'name' => 'Pluto'],
                                ];
                                foreach ($radixPlanetLabels as $idx => $planet): ?>
                                    <label>
                                        <input type="checkbox" name="radix_target[]" value="<?= $idx ?>" onchange="checkToggleState('radix_target[]', 'toggle-radix', 13)" <?= in_array($idx, $selRadix) ? 'checked' : '' ?>>
                                        <span class="astro-glyph"><?= $planet['glyph'] ?></span>
                                    </label>
                                <?php endforeach; ?>
                                <div class="section-divider"></div>
                                <?php 
                                $radixAxisLabels = [
                                    10 => SymbolGlyph::getPlanetGlyphByIndex(10),
                                    11 => SymbolGlyph::getPlanetGlyphByIndex(11),
                                    12 => SymbolGlyph::getPlanetGlyphByIndex(12),
                                ];
                                foreach ($radixAxisLabels as $idx => $glyph): ?>
                                    <label>
                                        <input type="checkbox" name="radix_target[]" value="<?= $idx ?>" onchange="checkToggleState('radix_target[]', 'toggle-radix', 13)" <?= in_array($idx, $selRadix) ? 'checked' : '' ?>>
                                        <span class="astro-glyph"><?= $glyph ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            
                            <button type="submit" name="calculate_progressions" class="progression-submit">Bereken Progressie Events</button>
                        </form>
                        
                        <?php if (isset($error)): ?>
                            <p class="form-error"><?= htmlspecialchars($error) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (isset($progEventsResult) && count($progEventsResult) > 0): ?>
                    <div class="card card--large progression-results">
                        <h4>Resultaten (<?= count($progEventsResult) ?> events)</h4>
                        <table>
                            <thead>
                                <tr>
                                    <th>Datum</th>
                                    <th>Dir</th>
                                    <th>Progressief</th>
                                    <th>Aspect</th>
                                    <th>Radix</th>
                                    <th>Prog Pos</th>
                                    <th>Radix Pos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($progEventsResult as $event): ?>
                                    <tr class="<?= $event['event_type'] === 'rd_transition' ? 'row--rd' : '' ?><?= ($event['event_type'] === 'house_ingress' || $event['event_type'] === 'sign_ingress') ? 'row--ingress' : '' ?>">
                                        <td><?= date('d-m-Y', $event['timestamp']) ?></td>
                                        <td><?= $event['direction'] ?></td>
                                        <td><span class="astro-glyph"><?= SymbolGlyph::getPlanetGlyphByIndex($event['progressive_index']) ?></span></td>
                                        <td><span class="astro-glyph"><?= SymbolGlyph::getAspectGlyph($event['aspect']) ?></span></td>
                                        <td>
                                            <?php if ($event['event_type'] === 'rd_transition'): ?>
                                                <?= htmlspecialchars($event['radix_target']) ?>
                                            <?php else: ?>
                                                <span class="astro-glyph">
                                                    <?= SymbolGlyph::getGlyphForTarget($event['radix_index']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= Formatter::formatLongitudeWithGlyph($event['progressive_position']) ?></td>
                                        <td><?= Formatter::formatLongitudeWithGlyph($event['radix_position']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php elseif (isset($progEventsResult) && count($progEventsResult) === 0): ?>
                    <div class="card card--large">
                        <p>Geen events gevonden in de opgegeven periode.</p>
                    </div>
                    <?php endif; ?>
                </section>
                <?php endif; ?>
                
                <?php if (isset($result['antiscia'])): ?>
                <section id="tab-antiscia" class="tab-content tab-content--hidden">
                    <div class="card card--large card--antiscia">
                        <h4>Spiegelpunten (Jan de Jong)</h4>
                        
                        <div class="antiscia-container">
                            <div class="antiscia-column antiscia-column--points">
                                <table>
                                    <tr>
                                        <th colspan="2">Spiegelpunten</th>
                                    </tr>
                                    <?php foreach ($result['antiscia']['mirrorPoints'] as $point): ?>
                                        <tr>
                                            <td>
                                                <span class="astro-glyph">
                                                    <?= SymbolGlyph::getPlanetGlyphByIndex($point['name']) ?>
                                                </span> i
                                            </td>
                                            <td>
                                                <?= Formatter::formatLongitudeWithGlyph($point['pos']) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            </div>
                            
                            <div class="antiscia-column antiscia-column--aspects">
                                <table>
                                    <tr>
                                        <th colspan="4">Aspecten spiegelpunten</th>
                                    </tr>
                                    <?php foreach ($result['antiscia']['aspects'] as $aspect): ?>
                                        <tr>
                                            <td>
                                                <span class="astro-glyph">
                                                    <?= SymbolGlyph::getPlanetGlyphByIndex($aspect['name1']) ?>
                                                </span> i
                                            </td>
                                            <td>
                                                <span class="astro-glyph">
                                                    <?= SymbolGlyph::getAspectGlyph($aspect['degree']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="astro-glyph">
                                                    <?= SymbolGlyph::getPlanetGlyphByIndex($aspect['name2']) ?>
                                                </span> r
                                            </td>
                                            <td>
                                                orb: <?= Formatter::formatOrb($aspect['orb']) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
    
    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</div>

<script src="js/app.js"></script>
<?php if ($hasResult && $mode !== 'edit'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.tijdApp) {
        window.tijdApp.enableResultTabs();
        // Gebruik de tab die PHP al heeft bepaald
        window.tijdApp.switchTab('<?= $currentTab ?>', false);
    }
});
</script>
<?php endif; ?>

<?php
// DEBUG: Toon tab info (verwijder na testing)
// echo '<div style="position:fixed;bottom:0;left:0;background:#fff;border:2px solid red;padding:10px;z-index:9999;font-family:monospace;font-size:12px;">';
// echo '<strong>DEBUG TAB INFO:</strong><br>';
// echo 'currentTab: ' . ($currentTab ?? 'UNDEFINED') . '<br>';
// echo 'hasResult: ' . ($hasResult ? 'TRUE' : 'FALSE') . '<br>';
// echo 'mode: ' . ($mode ?? 'UNDEFINED') . '<br>';
// echo 'REQUEST_METHOD: ' . ($_SERVER['REQUEST_METHOD'] ?? 'UNDEFINED') . '<br>';
// echo 'viewHoroscope: ' . ($viewHoroscope ? 'SET' : 'NULL') . '<br>';
// echo 'isLoggedIn: ' . ($isLoggedIn ? 'YES' : 'NO') . '<br>';
// echo 'progression_events exists: ' . (isset($_SESSION['horoscope']['progression_events']) ? 'YES' : 'NO') . '<br>';
// echo 'just_submitted: ' . (isset($_SESSION['just_submitted_progressions']) ? 'YES' : 'NO') . '<br>';
// echo '</div>';
?>

</body>
</html>