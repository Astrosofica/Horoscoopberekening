<?php
/**
 * Lazy loader: Progressions list tab
 * 
 * Shows cached progression events (no calculation needed)
 */

$currentTab = 'progressions-list';
$progEventsResult = null;

if (isset($_SESSION['horoscope']['progression_events']['results'])) {
    $progEventsResult = $_SESSION['horoscope']['progression_events']['results'];
}

return $progEventsResult;