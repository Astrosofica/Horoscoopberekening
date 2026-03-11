<?php 
/**
 * Zet een decimale coördinaat om naar DMS-notatie (Degrees, Minutes, Seconds).
 *
 * De functie accepteert een latitude of longitude in decimale graden
 * (bijv. 52.123456 of -6.345678) en geeft een geformatteerde string terug
 * met graden, minuten en seconden inclusief richting (N/S/E/W).
 *
 * Kenmerken:
 * - seconden worden afgerond
 * - nul-opvulling voor minuten en seconden
 * - graden worden met 2 digits (lat) of 3 digits (lon) weergegeven
 * - overflow wordt gecorrigeerd (bijv. 59.9 seconden → 1 minuut)
 *
 * Parameters:
 * @param float $coord  Decimale coördinaat
 * @param bool  $isLat  true = latitude (N/S), false = longitude (E/W)
 *
 * Return:
 * @return string Geformatteerde DMS-string
 *
 * Voorbeelden:
 * decimalToDMS(52.123456, true)  → "52° 07′ 24″ N"
 * decimalToDMS(6.345678, false)  → "006° 20′ 44″ E"
 *
 * Gebruik via wrappers:
 * formatLat(52.123456);
 * formatLon(6.345678);
 * 
 * formatLat(-33.9249) → 33° 55′ 30″ S
 * formatLon(-18.4241) → 018° 25′ 27″ W
 * 
 */
function decimalToDMS(float $coord, bool $isLat = true): string
{
    $direction = $isLat
        ? ($coord >= 0 ? 'N' : 'S')
        : ($coord >= 0 ? 'E' : 'W');

    $coord = abs($coord);

    $deg = floor($coord);
    $minFloat = ($coord - $deg) * 60;
    $min = floor($minFloat);
    $sec = round(($minFloat - $min) * 60);

    // overflow correctie
    if ($sec == 60) {
        $sec = 0;
        $min++;
    }

    if ($min == 60) {
        $min = 0;
        $deg++;
    }

    // breedte van graden verschilt
    $degFormat = $isLat ? '%02d' : '%03d';

    return sprintf(
        $degFormat . '° %02d′ %02d″ %s',
        $deg,
        $min,
        $sec,
        $direction
    );
}

/*
* Formatteert een latitude in DMS-notatie.
*/
function formatLat(float $lat): string {
    return decimalToDMS($lat, true);
}

/*
* Formatteert een longitude in DMS-notatie.
*/
function formatLon(float $lon): string {
    return decimalToDMS($lon, false);
}


/**
 * Formatteert een UTC/GMT offset (in seconden) naar een leesbare string.
 *
 * Voorbeelden:
 * 3600   → "+1 u"
 * 5400   → "+1 u 30 min"
 * -19800 → "-5 u 30 min"
 * 45     → "+45 sec"
 *
 * Regels:
 * - positief krijgt een '+' prefix
 * - negatief krijgt een '-' prefix
 * - uren/minuten/seconden worden alleen getoond als ze ≠ 0 zijn
 *
 * @param int $seconds Offset t.o.v. UTC in seconden
 * @return string
 * 
 * Gebruik:
 * echo formatOffset(7200);     // +2 u
 * echo formatOffset(5400);     // +1 u 30 min
 * echo formatOffset(-19800);   // -5 u 30 min
 * echo formatOffset(45);       // +45 sec
 * 
 */
function formatOffset(int $seconds): string
{
    $sign = $seconds >= 0 ? '+' : '-';
    $seconds = abs($seconds);

    $hours = intdiv($seconds, 3600);
    $seconds %= 3600;

    $minutes = intdiv($seconds, 60);
    $seconds %= 60;

    $parts = [];

    if ($hours) {
        $parts[] = $hours . ' u';
    }

    if ($minutes) {
        $parts[] = $minutes . ' min';
    }

    if ($seconds) {
        $parts[] = $seconds . ' sec';
    }

    if (empty($parts)) {
        $parts[] = '0 sec';
    }

    return $sign . implode(' ', $parts);
}