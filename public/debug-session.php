<?php
// Simpele debug script
session_start();

echo "<h1>Debug Progression Events</h1>";

if (isset($_SESSION['horoscope']['progression_events'])) {
    echo "<h2>Progression Events in Session:</h2>";
    echo "<pre>" . print_r($_SESSION['horoscope']['progression_events'], true) . "</pre>";
} else {
    echo "<p>Geen progression_events gevonden in session</p>";
}

echo "<h2>Hele horoscope session:</h2>";
if (isset($_SESSION['horoscope'])) {
    $keys = array_keys($_SESSION['horoscope']);
    echo "Keys: " . implode(', ', $keys) . "<br>";
    if (isset($_SESSION['horoscope']['progression_events'])) {
        echo "progression_events input: ";
        print_r($_SESSION['horoscope']['progression_events']['input'] ?? 'N/A');
    }
} else {
    echo "Geen horoscope in session";
}