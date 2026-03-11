<?php
require_once 'config.php'; // Hier moet je API_KEY in staan
require_once 'AstrologicalTimeManager.php'; // De class die we net maakten
require_once 'functions.php'; // Hulpfuncties

$result = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['location'])) {
    $location = trim($_POST['location']);
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';

    if (!preg_match('/^[\p{L}\s\-\.,]+$/u', $location)) {
        $error = "Ongeldige locatie";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
        $error = "Ongeldige datum";
    } elseif (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
        $error = "Ongeldige tijd";
    }

    if (!isset($error)) {
        $timestamp = strtotime("$date $time");

        // Stap 1: Geocoding (Locatie -> Coördinaten & Timezone ID)
        // In een productie-omgeving zou je dit via JS doen of hier in PHP:
        $geoUrl = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($location) . "&language=nl&key=" . GOOGLE_API_KEY;
        $geoRes = json_decode(file_get_contents($geoUrl), true);

        if ($geoRes['status'] === 'OK') {
            $lat = $geoRes['results'][0]['geometry']['location']['lat'];
            $lng = $geoRes['results'][0]['geometry']['location']['lng'];
            
            // Google Time Zone API aanroepen om de juiste IANA ID te krijgen (bv. Europe/Amsterdam)
            $tzUrl = "https://maps.googleapis.com/maps/api/timezone/json?location=$lat,$lng&timestamp=$timestamp&key=" . GOOGLE_API_KEY;
            $tzRes = json_decode(file_get_contents($tzUrl), true);
            
            if ($tzRes['status'] === 'OK') {
                $tzId = $tzRes['timeZoneId'];

                // Stap 2: Onze High-Precision Class gebruiken
                $manager = new AstrologicalTimeManager($tzId, $lat, $lng);
                $result = $manager->getOffset($timestamp);
                $result['coords'] = ['lat' => $lat, 'lng' => $lng];
                $result['address'] = $geoRes['results'][0]['formatted_address'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Astrologische Tijd Calculator</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 40px auto; line-height: 1.6; }
        .card { border: 1px solid #ccc; padding: 20px; border-radius: 8px; background: #f9f9f9; }
        .result { margin-top: 20px; padding: 15px; background: #e7f3ff; border-left: 5px solid #2196F3; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 8px; margin-bottom: 15px; box-sizing: border-box; }
        button { background: #2196F3; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>

<div class="card">
    <h2>Geboortegegevens</h2>
    <form method="POST">
        <label>Geboorteplaats:</label>
        <input type="text" name="location" placeholder="Bijv. Utrecht" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" required>

        <label>Datum:</label>
        <input type="date" name="date" value="<?= $_POST['date'] ?? '1938-06-15' ?>" required>

        <label>Tijd (Lokaal):</label>
        <input type="time" name="time" value="<?= $_POST['time'] ?? '14:30:00' ?>" step="1" required>

        <button type="submit">Bereken Exacte Offset</button>
    </form>

    <?php if ($error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($result): ?>
        <div class="result">
            <h3>Resultaat voor <?= htmlspecialchars($result['address']) ?></h3>
            <p><small>Coördinaten: <?= formatLat($result['coords']['lat']) ?>, <?= formatLon($result['coords']['lng']) ?></small></p>
            <p><strong>Offset met GMT/UTC:</strong> <?= formatOffset($result['offset']) ?> 
               (<?= $result['offset'] ?> seconden)</p>
            <p><strong>Bron:</strong> <?= $result['source'] ?> (<?= $result['label'] ?>)</p>
            
        </div>
    <?php endif; ?>
</div>

</body>
</html>