<?php

namespace Tijd\Helpers;

use Tijd\Glyph\SymbolGlyph;

class Formatter
{
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
     * Formatter::decimalToDMS(52.123456, true)  → "52° 07′ 24″ N"
     * Formatter::decimalToDMS(6.345678, false)  → "006° 20′ 44″ E"
     *
     * Gebruik via wrappers:
     * Formatter::formatLat(52.123456);
     * Formatter::formatLon(6.345678);
     * 
     * Formatter::formatLat(-33.9249) → 33° 55′ 30″ S
     * Formatter::formatLon(-18.4241) → 018° 25′ 27″ W
     */
    public static function decimalToDMS(float $coord, bool $isLat = true): string
    {
        $direction = $isLat
            ? ($coord >= 0 ? 'N' : 'S')
            : ($coord >= 0 ? 'E' : 'W');

        $coord = abs($coord);

        $deg = floor($coord);
        $minFloat = ($coord - $deg) * 60;
        $min = floor($minFloat);
        $sec = round(($minFloat - $min) * 60);

        if ($sec == 60) {
            $sec = 0;
            $min++;
        }

        if ($min == 60) {
            $min = 0;
            $deg++;
        }

        $degFormat = $isLat ? '%02d' : '%03d';

        return sprintf(
            $degFormat . '° %02d′ %02d″ %s',
            $deg,
            $min,
            $sec,
            $direction
        );
    }

    /**
     * Formatteert een latitude in DMS-notatie.
     */
    public static function formatLat(float $lat): string
    {
        return self::decimalToDMS($lat, true);
    }

    /**
     * Formatteert een longitude in DMS-notatie.
     */
    public static function formatLon(float $lon): string
    {
        return self::decimalToDMS($lon, false);
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
     * echo Formatter::formatOffset(7200);     // +2 u
     * echo Formatter::formatOffset(5400);     // +1 u 30 min
     * echo Formatter::formatOffset(-19800);   // -5 u 30 min
     * echo Formatter::formatOffset(45);       // +45 sec
     */
    public static function formatOffset(int $seconds): string
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

    /**
     * Zet een planetaire longitude om naar dierenriemteken met graad.
     *
     * @param float $longitude Longitude in decimale graden (0-360)
     * @return string Formaat: "15° 23′ 45″ Ram"
     */
    public static function formatPlanetLongitude(float $longitude): string
    {
        $zodiacSigns = [
            'Ram',
            'Stier',
            'Tweelingen',
            'Kreeft',
            'Leeuw',
            'Maagd',
            'Weegschaal',
            'Schorpioen',
            'Boogschutter',
            'Steenbok',
            'Waterman',
            'Vissen'
        ];

        $longitude = fmod($longitude, 360);
        if ($longitude < 0) {
            $longitude += 360;
        }

        $signIndex = (int) floor($longitude / 30);
        $signLongitude = fmod($longitude, 30);

        $deg = floor($signLongitude);
        $minFloat = ($signLongitude - $deg) * 60;
        $min = floor($minFloat);
        $sec = round(($minFloat - $min) * 60);

        if ($sec == 60) {
            $sec = 0;
            $min++;
        }

        if ($min == 60) {
            $min = 0;
            $deg++;
        }

        return sprintf(
            '%02d° %02d′ %02d″ %s',
            $deg,
            $min,
            $sec,
            $zodiacSigns[$signIndex]
        );
    }

    /**
     * Zet een planetaire longitude om naar DMS met glyph symbool aan het einde.
     *
     * @param float $longitude Longitude in decimale graden (0-360)
     * @return string Formaat: "15° 23′ 45″ ♍"
     */
    public static function formatLongitudeWithGlyph(float $longitude): string
    {
        $longitude = fmod($longitude, 360);
        if ($longitude < 0) {
            $longitude += 360;
        }

        $signLongitude = fmod($longitude, 30);

        $deg = floor($signLongitude);
        $minFloat = ($signLongitude - $deg) * 60;
        $min = floor($minFloat);
        $sec = round(($minFloat - $min) * 60);

        if ($sec == 60) {
            $sec = 0;
            $min++;
        }

        if ($min == 60) {
            $min = 0;
            $deg++;
        }

        $glyph = SymbolGlyph::getSignGlyph($longitude);

        return sprintf(
            '%02d° %02d′ %02d″ <span class="astro-glyph">%s</span>',
            $deg,
            $min,
            $sec,
            $glyph
        );
    }

    /**
     * Zet een planetaire longitude om naar dierenriemteken met glyph symbool.
     *
     * @param float $longitude Longitude in decimale graden (0-360)
     * @return string Formaat: "<span class='astro-glyph'>P</span> 15° 23′ 45″"
     */
    public static function formatPlanetLongitudeWithGlyph(float $longitude): string
    {
        $signIndex = (int) floor(fmod($longitude, 360) / 30);
        $signIndex = max(0, min(11, $signIndex));
        $signLongitude = fmod($longitude, 30);

        $deg = floor($signLongitude);
        $minFloat = ($signLongitude - $deg) * 60;
        $min = floor($minFloat);
        $sec = round(($minFloat - $min) * 60);

        if ($sec == 60) {
            $sec = 0;
            $min++;
        }

        if ($min == 60) {
            $min = 0;
            $deg++;
        }

        $glyph = SymbolGlyph::getSignGlyph($longitude);

        return sprintf(
            '<span class="astro-glyph">%s</span> %02d° %02d′ %02d″',
            $glyph,
            $deg,
            $min,
            $sec
        );
    }
}
