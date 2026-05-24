<?php

namespace Tijd\Ephemeris;

class SwissEphemeris
{
    public const SEFLG_SWIEPH    = 2;
    public const SEFLG_SPEED     = 256;
    public const SEFLG_HELCTR    = 8;
    public const SEFLG_TRUEPOS   = 16;
    public const SEFLG_J2000     = 32;
    public const SEFLG_NONUT     = 64;
    public const SEFLG_TOPOCTR   = 1024;

    public const SE_SUN          = 0;
    public const SE_MOON         = 1;
    public const SE_MERCURY      = 2;
    public const SE_VENUS        = 3;
    public const SE_MARS         = 4;
    public const SE_JUPITER      = 5;
    public const SE_SATURN       = 6;
    public const SE_URANUS       = 7;
    public const SE_NEPTUNE      = 8;
    public const SE_PLUTO        = 9;
    public const SE_MEAN_NODE    = 10;
    public const SE_TRUE_NODE    = 11;
    public const SE_CHIRON       = 15;

    private static ?\FFI $ffi = null;

    public static function getFfi(): \FFI
    {
        return self::$ffi;
    }
    private EphemerisConfig $config;

    public function __construct(?EphemerisConfig $config = null)
    {
        $this->config = $config ?? new EphemerisConfig();
        $this->initialize();
    }

    private function initialize(): void
    {
        if (self::$ffi === null) {
            self::$ffi = \FFI::cdef("
                void swe_set_ephe_path(char *path);
                double swe_julday(int year, int month, int day, double hour, int gregflag);
                int swe_calc_ut(double tjd_ut, int ipl, int iflag, double *xx, char *serr);
                int swe_calc(double tjd, int ipl, int iflag, double *xx, char *serr);
                int swe_houses(double tjd_ut, double lat, double lon, int hsys, double *cusps, double *ascmc);
                int swe_houses_ex(double tjd_ut, int iflag, double lat, double lon, int hsys, double *cusps, double *ascmc);
                int swe_houses_armc(double armc, double lat, double ecl, int hsys, double *cusps, double *ascmc);
                void swe_close(void);
                int swe_fixstar_ut(char *star, double tjd_ut, int iflag, double *xx, char *serr);
                char *swe_get_planet_name(int ipl, char *s);
            ", $this->config->getLibraryPath());

            self::$ffi->swe_set_ephe_path($this->config->getEphemerisPath());
        }
    }

    public function julianDay(int $year, int $month, int $day, float $hour = 0.0, bool $gregorian = true): float
    {
        return self::$ffi->swe_julday($year, $month, $day, $hour, $gregorian ? 1 : 0);
    }

    public function julianDayFromTimestamp(int $timestamp): float
    {
        return $this->julianDay(
            (int)date('Y', $timestamp),
            (int)date('n', $timestamp),
            (int)date('j', $timestamp),
            (float)date('G', $timestamp) + (float)date('i', $timestamp)/60 + (float)date('s', $timestamp)/3600
        );
    }

    public function timestampFromJulianDay(float $julianDay): int
    {
        $jd = $julianDay + 0.5;
        $Z = (int)$jd;
        $F = $jd - $Z;

        if ($Z >= 2299161) {
            $alpha = (int)((($Z - 1867216.25) / 36524.25));
            $A = $Z + 1 + $alpha - (int)((($alpha / 4)));
        } else {
            $A = $Z;
        }

        $B = $A + 1524;
        $C = (int)((($B - 122.1) / 365.25));
        $D = (int)((365.25 * $C));
        $E = (int)((($B - $D) / 30.6001));

        $day = $B - $D - (int)((30.6001 * $E));

        if ($E < 14) {
            $month = $E - 1;
        } else {
            $month = $E - 13;
        }

        if ($month > 2) {
            $year = $C - 4716;
        } else {
            $year = $C - 4715;
        }

        $hour = $F * 24;
        $hours = (int)$hour;
        $minutes = (int)((($hour - $hours) * 60));
        $seconds = (int)((((($hour - $hours) * 60) - $minutes) * 60));

        $datetime = new \DateTime();
        $datetime->setDate($year, $month, $day);
        $datetime->setTime($hours, $minutes, $seconds);
        $datetime->setTimezone(new \DateTimeZone('UTC'));

        return $datetime->getTimestamp();
    }

    public function calculatePlanet(
        float $julianDay,
        int $planet,
        int $iflag = self::SEFLG_SPEED
    ): array {
        // Gebruik de instantie (self::$ffi) in plaats van de klasse (\FFI)
        $xx = self::$ffi->new("double[6]");
        $serr = self::$ffi->new("char[256]");

        $result = self::$ffi->swe_calc_ut($julianDay, $planet, $iflag, $xx, $serr);

        if ($result >= 0) {
            return [
                'success' => true,
                'longitude' => $xx[0],
                'latitude' => $xx[1],
                'distance' => $xx[2],
                'speed_longitude' => $xx[3],
                'speed_latitude' => $xx[4],
                'speed_distance' => $xx[5],
            ];
        }

        return [
            'success' => false,
            'error' => \FFI::string($serr)
        ];
    }

    public function calculateAllPlanets(
        float $julianDay,
        int $iflag = self::SEFLG_SPEED,
        array $planets = null
    ): array {
        $planetList = $planets ?? [
            0, 1, 2, 3, 4, 5, 6, 7, 8, 9,
            self::SE_TRUE_NODE,
            self::SE_CHIRON
        ];
        
        $planetNames = [
            0 => 'Sun',
            1 => 'Moon',
            2 => 'Mercury',
            3 => 'Venus',
            4 => 'Mars',
            5 => 'Jupiter',
            6 => 'Saturn',
            7 => 'Uranus',
            8 => 'Neptune',
            9 => 'Pluto',
            self::SE_TRUE_NODE => 'NorthNode',
            self::SE_CHIRON => 'Chiron',
        ];
        
        $results = [];

        foreach ($planetList as $planet) {
            $name = $planetNames[$planet] ?? null;
            if ($name === null) continue;
            
            $results[$name] = $this->calculatePlanet($julianDay, $planet, $iflag);
        }

        return $results;
    }

    public function close(): void
    {
        if (self::$ffi !== null) {
            self::$ffi->swe_close();
            self::$ffi = null;
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}
