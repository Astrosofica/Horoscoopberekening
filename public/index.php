<?php

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
use Tijd\Calculation\TransitCalculator;
use Tijd\Calculation\TransitEventCalculator;
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

// Wis horoscoop session data (via clear of new parameter)
if (isset($_GET['clear']) && $_GET['clear'] == '1') {
    unset($_SESSION['horoscope']);
    unset($_SESSION['wheel_data']);
    unset($_SESSION['solaar_prefill']);
    $_SESSION['flash_success'] = 'Horoscoop gewist.';
    header('Location: index.php');
    exit;
}

// Start nieuwe horoscoop (vanaf dashboard) - geen flash message
if (isset($_GET['new']) && $_GET['new'] == '1') {
    unset($_SESSION['horoscope']);
    unset($_SESSION['wheel_data']);
    unset($_SESSION['solaar_prefill']);
    // user_id, user_email, email_verified blijven behouden (gebruiker blijft ingelogd)
    header('Location: index.php');
    exit;
}

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// ===========================================================================
// LAAD OPSGESLAGEN HOROSCOOP (indien ?h=slug parameter)
// ===========================================================================
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

// Cleanup solaar prefill bij laden saved horoscope
if ($viewHoroscope) {
    unset($_SESSION['solaar_prefill']);
}

// Vul $_POST met database data voor edit/view mode (voordat formValues wordt gezet)
// MAAR NIET bij progression/transit form submissions (anders trigger main calculation!)
if (($isEdit || $mode === 'view') && $viewHoroscope && !isset($_POST['lastname']) 
    && !isset($_POST['calculate_progressions']) && !isset($_POST['calculate_transits'])) {
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

// ===========================================================================
// SOLAAR PREFILL - solar return data in formulier
// ===========================================================================
$isSolaarPrefill = isset($_SESSION['solaar_prefill']);

// ===========================================================================
// FORMULIER WAARDEN - gebruik POST, session, of database data
// ===========================================================================
$formValues = [
    'firstname' => '',
    'infix' => '',
    'lastname' => '',
    'location' => '',
    'date' => '',
    'time' => '',
    'utc' => false,
    'lmt' => false,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['lastname'])) {
    $formValues = [
        'firstname' => $_POST['firstname'] ?? '',
        'infix' => $_POST['infix'] ?? '',
        'lastname' => $_POST['lastname'] ?? '',
        'location' => $_POST['location'] ?? '',
        'date' => $_POST['date'] ?? '',
        'time' => $_POST['time'] ?? '',
        'utc' => isset($_POST['time_correction_utc']),
        'lmt' => isset($_POST['time_correction_lmt']),
    ];
} elseif (isset($_SESSION['horoscope']['input']) && !isset($_POST['save_horoscope']) && !$viewHoroscope && !$isSolaarPrefill) {
    // Gebruik session data voor formulier (niet-opgeslagen horoscoop)
    // MAAR NIET als er een opgeslagen horoscoop wordt geladen ($viewHoroscope bestaat)
    $input = $_SESSION['horoscope']['input'];
    $formValues = [
        'firstname' => $input['firstname'] ?? '',
        'infix' => $input['infix'] ?? '',
        'lastname' => $input['lastname'] ?? '',
        'location' => $input['location_name'] ?? '',
        'date' => $input['birth_date'] ?? '',
        'time' => $input['birth_time'] ?? '',
        'utc' => ($input['time_correction'] ?? null) === 'utc',
        'lmt' => ($input['time_correction'] ?? null) === 'lmt',
    ];
} elseif ($isSolaarPrefill) {
    $p = $_SESSION['solaar_prefill'];
    $formValues = [
        'firstname' => $p['firstname'],
        'infix' => $p['infix'],
        'lastname' => $p['lastname'],
        'location' => '',
        'date' => $p['birth_date'],
        'time' => $p['birth_time'],
        'utc' => true,
        'lmt' => false,
    ];
} elseif (!empty($_POST['lastname'])) {
    // Gebruik $_POST data (gevuld met database data voor edit/view mode)
    $formValues = [
        'firstname' => $_POST['firstname'] ?? '',
        'infix' => $_POST['infix'] ?? '',
        'lastname' => $_POST['lastname'] ?? '',
        'location' => $_POST['location'] ?? '',
        'date' => $_POST['date'] ?? '',
        'time' => $_POST['time'] ?? '',
        'utc' => isset($_POST['time_correction_utc']),
        'lmt' => isset($_POST['time_correction_lmt']),
    ];
}

$advancedSettingsOpen = $formValues['utc'] || $formValues['lmt'];

// ===========================================================================
// POST HANDLER - Edit mode: direct opslaan
// ===========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_edit']) && $mode === 'edit' && $viewHoroscope) {
    require_once __DIR__ . '/handlers/edit-horoscope.php';
    
    // POST-Redirect-GET pattern
    if (!isset($error) && isset($_SESSION['flash_success'])) {
        // Wis gecachte events — data is na edit niet meer geldig
        unset($_SESSION['horoscope']['progression_events'], $_SESSION['horoscope']['transit_events']);
        header('Location: dashboard.php');
        exit;
    }
}

// ===========================================================================
// POST HANDLER - New calculation form submission
// ===========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['lastname']) && !isset($_POST['save_horoscope']) && !isset($_POST['save_edit']) 
    && !isset($_POST['calculate_progressions']) && !isset($_POST['calculate_transits']) && !isset($_POST['calculate_solaar'])) {
    require_once __DIR__ . '/handlers/calculate-horoscope.php';
    
    // POST-Redirect-GET pattern
    if (!isset($error) && isset($_SESSION['horoscope']['core'])) {
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// ===========================================================================
// POST HANDLER - Progression Events Form
// ===========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calculate_progressions'])) {
    require_once __DIR__ . '/handlers/calculate-progressions.php';
    
    // POST-Redirect-GET pattern
    if (!isset($error) && isset($_SESSION['just_submitted_progressions'])) {
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// ===========================================================================
// POST HANDLER - Transit Events
// ===========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calculate_transits'])) {
    require_once __DIR__ . '/handlers/calculate-transits.php';
    
    // POST-Redirect-GET pattern
    if (!isset($error) && isset($_SESSION['just_submitted_transits'])) {
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// ===========================================================================
// POST HANDLER - Solaar (Solar Return zoeken)
// ===========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calculate_solaar'])) {
    require_once __DIR__ . '/handlers/calculate-solaar.php';

    // POST-Redirect-GET pattern
    if (!isset($error)) {
        header('Location: ' . $_SERVER['SCRIPT_NAME']);
        exit;
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
$justDidProgression = isset($_SESSION['just_submitted_progressions']);
if ($justDidProgression) {
    $currentTab = 'progressions-list';
}

// Check of we net een transit submit hebben gedaan
$justDidTransits = isset($_SESSION['just_submitted_transits']);
if ($justDidTransits) {
    $currentTab = 'transits-list';
}

if ($mode === 'view' && $viewHoroscope && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check of we al dezelfde horoscoop in session hebben
    $currentSlug = $_SESSION['horoscope']['input']['slug'] ?? null;
    
    // Migratie: als session core data heeft maar geen slug, voeg deze toe
    if ($currentSlug === null && isset($_SESSION['horoscope']['core'])) {
        $currentSlug = $viewHoroscope->getSlug();
        $_SESSION['horoscope']['input']['slug'] = $currentSlug;
    }
    
    $sameHoroscope = ($currentSlug === $viewHoroscope->getSlug());
    
    // BEHOUD progression_events en transit_events alleen voor dezelfde horoscoop
    $existingProgressionEvents = $sameHoroscope ? ($_SESSION['horoscope']['progression_events'] ?? null) : null;
    $existingTransitEvents = $sameHoroscope ? ($_SESSION['horoscope']['transit_events'] ?? null) : null;
    
    // Reset lazy tabs bij laden opgeslagen horoscoop
    unset($_SESSION['horoscope']['aspects']);
    
    $calculator = new HoroscopeCalculator();
    $result = $calculator->calculate($viewHoroscope);
    $wheelData = $calculator->prepareWheelData($result);
    $_SESSION['wheel_data'] = $wheelData;
    
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
            'time_correction' => $viewHoroscope->getTimeCorrection(),
            'offset_source' => $viewHoroscope->getOffsetSource(),
            'offset_label' => $viewHoroscope->getOffsetLabel(),
            'slug' => $viewHoroscope->getSlug(),
        ],
        'core' => [
            'planets' => $result['planets'],
            'houses' => $result['houses']['houses'],
            'ascmc' => $result['houses']['ascmc'],
            'julian_day' => $result['julian_day'],
        ],
        'aspects' => null, // Lazy loaded
    ];
    
    // Herstel progression_events indien die bestond (zelfde horoscoop)
    if ($existingProgressionEvents !== null) {
        $_SESSION['horoscope']['progression_events'] = $existingProgressionEvents;
    }
    
    // Herstel transit_events indien die bestond (zelfde horoscoop)
    if ($existingTransitEvents !== null) {
        $_SESSION['horoscope']['transit_events'] = $existingTransitEvents;
    }
    
    // Verwijder eenmalige redirect markers
    unset($_SESSION['just_submitted_progressions'], $_SESSION['just_submitted_transits']);
}

// ===========================================================================
// TAB SWITCH LOGIC - Lazy Loading (vervolg)
// ===========================================================================
$hasResult = !$isSolaarPrefill && (($result !== null) || isset($_SESSION['horoscope']['core']));
$formDisabled = ($mode === 'view');
$hasSessionHoroscope = !$isSolaarPrefill && isset($_SESSION['horoscope']['core']) && !$formDisabled;

// Vul $result vanuit session voor template (alleen als session bestaat en $result null is)
if ($result === null && !$isSolaarPrefill && isset($_SESSION['horoscope']['core']) && isset($_SESSION['horoscope']['input'])) {
    $input = $_SESSION['horoscope']['input'];
    $core = $_SESSION['horoscope']['core'];
    $localTs = strtotime(($input['birth_date'] ?? '') . ' ' . ($input['birth_time'] ?? ''));
    
    // Reconstructeer result array - houses structuur zoals template verwacht
    $result = [
        'name' => trim(($input['firstname'] ?? '') . ' ' . ($input['infix'] ?? '') . ' ' . ($input['lastname'] ?? '')),
        'firstname' => $input['firstname'] ?? '',
        'infix' => $input['infix'] ?? '',
        'lastname' => $input['lastname'] ?? '',
        'address' => $input['location_name'] ?? '',
        'location_name' => $input['location_name'] ?? '',
        'coords' => ['lat' => $input['latitude'] ?? 0, 'lng' => $input['longitude'] ?? 0],
        'timezone' => $input['timezone_id'] ?? '',
        'offset' => $input['utc_offset'] ?? 0,
        'label' => $input['offset_label'] ?? 'UTC+' . round(($input['utc_offset'] ?? 0) / 3600),
        'time_correction' => $input['time_correction'] ?? null,
        'source' => $input['offset_source'] ?? 'session',
        'local_timestamp' => $localTs,
        'utc_timestamp' => $localTs - ($input['utc_offset'] ?? 0),
        'planets' => $core['planets'] ?? [],
        'houses' => [
            'houses' => $core['houses'] ?? [],
            'ascmc' => $core['ascmc'] ?? [],
        ],
        'julian_day' => $core['julian_day'] ?? null,
        // Lazy loaded data (kan null zijn)
        'aspects' => $_SESSION['horoscope']['aspects'] ?? null,
        'progressions' => $_SESSION['horoscope']['progressions'] ?? null,
        'transits' => $_SESSION['horoscope']['transits'] ?? null,
        'antiscia' => $_SESSION['horoscope']['antiscia'] ?? null,
        'midpoints' => $_SESSION['horoscope']['midpoints'] ?? null,
    ];
}

// Default tab bij resultaat is horoscope, tenzij andere tab gevraagd
if ($hasResult && $mode !== 'edit') {
    // Alleen op horoscope zetten als we niet al op een lazy tab zitten
    $lazyTabs = ['progressions-list', 'transits-list', 'aspects', 'progressions', 'antiscia', 'midpoints-planet', 'midpoints-sign', 'midpoints-tree', 'transits', 'solaar'];
    if (!in_array($currentTab, $lazyTabs)) {
        $currentTab = 'horoscope';
    }
    
    // Lazy tabs worden hieronder verwerkt
    if ($requestedTab) {
        switch ($requestedTab) {
            case 'aspects':
                $aspectResult = require_once __DIR__ . '/lazy/aspects.php';
                if ($aspectResult && $result !== null) {
                    $result['aspects'] = $aspectResult;
                }
                break;
            
            case 'progressions':
                $progressionsResult = require_once __DIR__ . '/lazy/progressions.php';
                if ($progressionsResult && $result !== null) {
                    $result['progressions'] = $progressionsResult;
                }
                break;
            
            case 'progressions-list':
                $progEventsResult = require_once __DIR__ . '/lazy/progressions-list.php';
                break;
            
            case 'antiscia':
                $antisciaResult = require_once __DIR__ . '/lazy/antiscia.php';
                if ($antisciaResult && $result !== null) {
                    $result['antiscia'] = $antisciaResult;
                }
                break;
            
            case 'midpoints-planet':
                $midpointsResult = require_once __DIR__ . '/lazy/midpoints-planet.php';
                break;
            
            case 'midpoints-sign':
                $midpointsResult = require_once __DIR__ . '/lazy/midpoints-sign.php';
                break;
            
            case 'midpoints-tree':
                $treeResult = require_once __DIR__ . '/lazy/midpoints-tree.php';
                break;
            
            case 'transits':
                $transitsResult = require_once __DIR__ . '/lazy/transits.php';
                if ($transitsResult && $result !== null) {
                    $result['transits'] = $transitsResult;
                }
                break;
            
            case 'transits-list':
                $transitEventsResult = require_once __DIR__ . '/lazy/transits-list.php';
                break;
            
            case 'solaar':
                require_once __DIR__ . '/lazy/solaar.php';
                break;
        }
    }
}

// About tab - altijd toegankelijk, geen resultaat vereist
if ($requestedTab === 'about') {
    $currentTab = 'about';
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
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
</head>
<body data-has-result="<?= $hasResult ? 'true' : 'false' ?>">
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
        <?php
        $headerName = $result['name'] ?? null;
        if (!$headerName && isset($_SESSION['horoscope']['input'])) {
            $i = $_SESSION['horoscope']['input'];
            $headerName = trim(($i['firstname'] ?? '') . ' ' . ($i['infix'] ?? '') . ' ' . ($i['lastname'] ?? ''));
        }
        ?>
        <?php if ($headerName): ?>
        <div class="header-sub">
            <span class="header-sub__name"><?= htmlspecialchars($headerName) ?></span>
        </div>
        <?php endif; ?>
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
                                <input type="text" id="firstname" name="firstname" placeholder="" value="<?= htmlspecialchars($formValues['firstname']) ?>"<?= $formDisabled ? ' disabled' : '' ?>>
                            </div>
                            <div class="form-group form-group--infix">
                                <label for="infix">Tussenv.</label>
                                <input type="text" id="infix" name="infix" placeholder="" value="<?= htmlspecialchars($formValues['infix']) ?>"<?= $formDisabled ? ' disabled' : '' ?>>
                            </div>
                            <div class="form-group form-group--lastname">
                                <label for="lastname">Achternaam <span class="required">*</span></label>
                                <input type="text" id="lastname" name="lastname" placeholder="" value="<?= htmlspecialchars($formValues['lastname']) ?>" required<?= $formDisabled ? ' disabled' : '' ?>>
                            </div>
                        </div>

                        <div class="form-row half">
                            <div class="form-group">
                                <label for="date_display">Datum<?= $isSolaarPrefill ? ' <small>(UTC)</small>' : '' ?></label>
                                <input type="text" id="date_display" inputmode="numeric" placeholder="DD-MM-JJJJ" required<?= ($formDisabled || $isSolaarPrefill) ? ' disabled' : '' ?>>
                                <input type="hidden" id="date" name="date" value="<?= htmlspecialchars($formValues['date']) ?>">
                            </div>
                            <div class="form-group">
                                <label for="time_display">Tijd<?= $isSolaarPrefill ? ' <small>(UTC)</small>' : ' (lokaal)' ?></label>
                                <input type="text" id="time_display" inputmode="numeric" placeholder="HH:MM:SS" required<?= ($formDisabled || $isSolaarPrefill) ? ' disabled' : '' ?>>
                                <input type="hidden" id="time" name="time" value="<?= htmlspecialchars($formValues['time']) ?>">
                            </div>
                        </div>

                        <?php if ($isSolaarPrefill): ?>
                        <div class="solaar-info">
                            <p class="warning-text">De datum en tijd staan vast (UTC).</p>
                            <p class="warning-text">Vul de huidige locatie in en klik op "Horoscoop berekenen".</p>
                        </div>
                        <?php endif; ?>

                        <div class="form-row full">
                            <div class="form-group">
                                <label for="location">Geboorteplaats</label>
                                <input type="text" id="location" name="location" placeholder="Bijv. Amsterdam, Nederland" value="<?= htmlspecialchars($formValues['location']) ?>" required<?= $formDisabled ? ' disabled' : '' ?>>
                            </div>
                        </div>

                        <div class="advanced-settings">
                            <button type="button" class="advanced-settings__toggle<?= $advancedSettingsOpen ? ' active' : '' ?>" onclick="toggleAdvancedSettings()">
                                <span class="advanced-settings__title">Geavanceerde instellingen</span>
                                <span class="advanced-settings__icon">▼</span>
                            </button>
                            <div class="advanced-settings__content<?= $advancedSettingsOpen ? ' visible' : '' ?>" id="advanced-settings-content">
                                <div class="form-row full">
                                    <div class="form-group">
                                        <label>Tijdcorrectie</label>
                                        <?php if (!$isSolaarPrefill): ?>
                                        <p class="warning-text">⚠ Alleen gebruiken als je handmatig een tijd hebt omgerekend</p>
                                        <?php endif; ?>
                                        <div class="checkbox-group">
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="time_correction_utc" value="1" <?= $formValues['utc'] ? 'checked' : '' ?> onchange="document.querySelector('input[name=time_correction_lmt]').checked = false;"<?= ($formDisabled || $isSolaarPrefill) ? ' disabled' : '' ?>>
                                                Ingevoerde tijd is UTC
                                            </label>
                                            <label class="checkbox-label">
                                                <input type="checkbox" name="time_correction_lmt" value="1" <?= $formValues['lmt'] ? 'checked' : '' ?> onchange="document.querySelector('input[name=time_correction_utc]').checked = false;"<?= ($formDisabled || $isSolaarPrefill) ? ' disabled' : '' ?>>
                                                Ingevoerde tijd is LMT/WPT
                                            </label>
                                        </div>
                                        <?php if ($isSolaarPrefill): ?>
                                        <input type="hidden" name="time_correction_utc" value="1">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (!$formDisabled): ?>
                        <div class="form-submit">
                            <?php if ($mode === 'edit'): ?>
                                <button type="submit" name="save_edit" value="1">Wijzigingen opslaan</button>
                                <a href="dashboard.php" class="btn btn--secondary btn--full-width">Annuleren</a>
                            <?php else: ?>
                                <button type="submit">
                                    <?php if ($hasSessionHoroscope): ?>
                                        Herbereken horoscoop
                                    <?php else: ?>
                                        Horoscoop berekenen
                                    <?php endif; ?>
                                </button>
                                <?php if ($hasSessionHoroscope): ?>
                                    <a href="?clear=1" class="btn btn--danger btn--full-width" 
                                       onclick="return confirm('Nieuwe horoscoop berekenen? Huidige gegevens worden gewis.');">
                                        Bereken nieuwe horoscoop
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
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

            <?php require_once __DIR__ . '/templates/solaar.php'; ?>

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
                                <input type="hidden" name="location_name" value="<?= htmlspecialchars($result['location_name'] ?? $result['address'] ?? '') ?>">
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

                <?php require_once __DIR__ . '/templates/aspects.php'; ?>

                <?php require_once __DIR__ . '/templates/progressions.php'; ?>
                
                <?php if (isset($_SESSION['horoscope']['core'])): ?>
<?php
                        // Haal huidige selecties op
                        $selProg = $_SESSION['horoscope']['progression_events']['input']['progressive_planets'] ?? [];
                        $selAspects = $_SESSION['horoscope']['progression_events']['input']['aspects'] ?? [];
                        $selRadix = $_SESSION['horoscope']['progression_events']['input']['radix_targets'] ?? [];
                        
                        // Haal resultaten uit session (na redirect)
                        $progEventsResult = $_SESSION['horoscope']['progression_events']['results'] ?? null;
                        
                        // Bepaal toggle states (afleiden uit selectie)
                        $allProgressive = count($selProg) === 10;
                        $allAspects = count($selAspects) === 7;
                        $allRadix = count($selRadix) === 13;
                        ?>
                <?php require_once __DIR__ . '/templates/progressions-list.php'; ?>
                
                <?php require_once __DIR__ . '/templates/antiscia.php'; ?>
                
                <?php require_once __DIR__ . '/templates/midpoints-planet.php'; ?>
                
                <?php require_once __DIR__ . '/templates/midpoints-sign.php'; ?>
                
                <?php require_once __DIR__ . '/templates/midpoints-tree.php'; ?>

                <?php require_once __DIR__ . '/templates/transits.php'; ?>

<?php
                        // Haal huidige selecties direct uit session
                        $savedTransitPlanets = $_SESSION['horoscope']['transit_events']['input']['transit_planets'] ?? [];
                        $savedTransitAspects = $_SESSION['horoscope']['transit_events']['input']['aspects'] ?? [];
                        $savedRadixTargets = $_SESSION['horoscope']['transit_events']['input']['radix_targets'] ?? [];
                        
                        // Haal resultaten uit session
                        $transitEventsResult = $_SESSION['horoscope']['transit_events']['results'] ?? null;
                        
                        // Bepaal toggle states
                        $allTransitPlanets = count($savedTransitPlanets) === 5;
                        $allTransitAspects = count($savedTransitAspects) === 7;
                        $allTransitRadix = count($savedRadixTargets) === 13;
                        ?>
                <?php require_once __DIR__ . '/templates/transits-list.php'; ?>
            <?php endif; ?>
            <?php endif; ?>
            
            <section id="tab-about" class="tab-content<?= $currentTab !== 'about' ? ' tab-content--hidden' : '' ?>">
                <div class="card card--large card--about">
                    <?php include 'includes/about-content.php'; ?>
                </div>
            </section>
        </main>
    </div>
    
    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</div>

<script src="js/app.js"></script>
<?php if (($hasResult && $mode !== 'edit') || $currentTab === 'about'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.tijdApp) {
        <?php if ($hasResult && $mode !== 'edit'): ?>
        window.tijdApp.enableResultTabs();
        <?php endif; ?>
        // Gebruik de tab die PHP al heeft bepaald
        window.tijdApp.switchTab('<?= $currentTab ?>', false);
    }
});
</script>
<?php endif; ?>

</body>
</html>