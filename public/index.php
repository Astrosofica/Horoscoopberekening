<?php
session_start();

if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

require_once __DIR__ . '/../config/app.php';
define('GOOGLE_API_KEY', $_ENV['GOOGLE_API_KEY'] ?? '');
define('ERROR_LOG_PATH', __DIR__ . '/../var/log/error.log');

ini_set('log_errors', true);
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

require_once __DIR__ . '/../src/Database/Connection.php';
require_once __DIR__ . '/../src/Entity/User.php';
require_once __DIR__ . '/../src/Entity/Horoscope.php';
require_once __DIR__ . '/../src/Database/UserRepository.php';
require_once __DIR__ . '/../src/Database/HoroscopeRepository.php';
require_once __DIR__ . '/../src/Auth/AuthService.php';
require_once __DIR__ . '/../src/Geo/GeocodingService.php';
require_once __DIR__ . '/../src/Time/AstroTime.php';
require_once __DIR__ . '/../src/Ephemeris/EphemerisConfig.php';
require_once __DIR__ . '/../src/Ephemeris/SwissEphemeris.php';
require_once __DIR__ . '/../src/Calculation/PlanetCalculator.php';
require_once __DIR__ . '/../src/Calculation/HouseCalculator.php';
require_once __DIR__ . '/../src/Calculation/Aspect.php';
require_once __DIR__ . '/../src/Calculation/AspectCalculator.php';
require_once __DIR__ . '/../src/Calculation/HousePlanetMatcher.php';
require_once __DIR__ . '/../src/Calculation/ParsFortuna.php';
require_once __DIR__ . '/../src/Calculation/HoroscopeCalculator.php';
require_once __DIR__ . '/../src/Helpers/Formatter.php';
require_once __DIR__ . '/../src/Glyph/SymbolGlyph.php';

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

if (($isEdit || $mode === 'view') && $viewHoroscope && !isset($_POST['name'])) {
    $_POST['name'] = $viewHoroscope->getName();
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

if ($mode === 'view' && $viewHoroscope) {
    $calculator = new HoroscopeCalculator();
    $result = $calculator->calculate($viewHoroscope);
    $wheelData = $calculator->prepareWheelData($result);
    $_SESSION['wheel_data'] = $wheelData;
}

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['location']) && !isset($_POST['save_horoscope'])) {
    $personName = trim($_POST['name'] ?? '');
    $location = trim($_POST['location']);
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';

    if (empty($personName)) {
        $error = "Naam is verplicht";
    } elseif (!preg_match('/^[\p{L}\s\-\.\']+$/u', $personName)) {
        $error = "Ongeldige naam";
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
                $utcOffset = 0;
                $offsetSource = 'manual';
                $offsetLabel = 'UTC';
                $timezoneId = '';
                $timeResult = [
                    'offset' => 0,
                    'source' => 'manual',
                    'label' => 'UTC'
                ];
            } elseif ($isLmt) {
                $lmtOffset = (int) round($lng * 240);
                $utcTimestamp = $timestamp - $lmtOffset;
                $utcOffset = $lmtOffset;
                $offsetSource = 'lmt';
                $offsetLabel = 'LMT';
                $timezoneId = '';
                $timeResult = [
                    'offset' => $lmtOffset,
                    'source' => 'lmt',
                    'label' => 'LMT'
                ];
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
                    $calculator = new PlanetCalculator();
                    $planetResult = $calculator->calculateForTimestamp($utcTimestamp);
                    
                    $houseCalculator = new HouseCalculator();
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

                    $aspectCalculator = new AspectCalculator();
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
                    
                    $mode = 'calculate';
                } catch (\Exception $e) {
                    $error = "Berekening mislukt: " . $e->getMessage();
                    error_log("[Tijd] Calculation error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
                }
            }
        }
    }
}

$hasResult = $result !== null;
$formDisabled = ($mode === 'view');
$currentTab = 'calculate';
if ($hasResult && $mode !== 'edit') {
    $currentTab = 'horoscope';
}
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
            <a href="index.php" class="header-brand"><?= APP_NAME ?></a>
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
                <?php if ($mode === 'new' || $mode === 'calculate'): ?>
                    <p class="intro-text">Een horoscoop is een symbolische kaart van mogelijkheden, geen voorspelling.</p>
                <?php endif; ?>
                
                <?php if ($mode === 'edit'): ?>
                    <p class="intro-text">Je bewerkt de horoscoop van <strong><?= htmlspecialchars($viewHoroscope->getName()) ?></strong>.</p>
                <?php endif; ?>
                
                <div class="card card--large card--form">
                    <h2>Geboortegegevens</h2>
                    <form method="POST">
                        <div class="form-row full">
                            <div class="form-group">
                                <label for="name">Naam</label>
                                <input type="text" id="name" name="name" placeholder="Volledige naam" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required<?= $formDisabled ? ' disabled' : '' ?>>
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
                                <input type="hidden" name="name" value="<?= htmlspecialchars($result['name']) ?>">
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
                        <img src="./Wheel/wheel.php?sid=<?= session_id() ?>" alt="Astrologisch Radix">
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
                    </div>
                </section>
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
        window.tijdApp.switchTab('horoscope', false);
    }
});
</script>
<?php endif; ?>
</body>
</html>