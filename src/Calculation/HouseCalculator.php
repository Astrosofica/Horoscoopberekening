<?php

namespace Tijd\Calculation;

use Tijd\Ephemeris\SwissEphemeris;
use Tijd\Helpers\Formatter;

class HouseCalculator
{
    public const HSYS_PLACIDUS = 'P';
    public const HSYS_KOCH = 'K';
    public const HSYS_PORPHYRIUS = 'O';
    public const HSYS_REGIOMONTANUS = 'R';
    public const HSYS_CAMPANUS = 'C';
    public const HSYS_EQUAL = 'E';
    public const HSYS_VEHLOW = 'V';
    public const HSYS_MERIDIAN = 'M';
    public const HSYS_AZIMUTHAL = 'A';

    private const HOUSE_NAMES = [
        1 => 'Cusp 1',
        2 => 'Cusp 2',
        3 => 'Cusp 3',
        4 => 'Cusp 4',
        5 => 'Cusp 5',
        6 => 'Cusp 6',
        7 => 'Cusp 7',
        8 => 'Cusp 8',
        9 => 'Cusp 9',
        10 => 'Cusp 10',
        11 => 'Cusp 11',
        12 => 'Cusp 12'
    ];

    private SwissEphemeris $ephemeris;

    public function __construct(?SwissEphemeris $ephemeris = null)
    {
        $this->ephemeris = $ephemeris ?? new SwissEphemeris();
    }

    public function calculate(
        float $julianDay,
        float $latitude,
        float $longitude,
        string $houseSystem = self::HSYS_KOCH
    ): array {
        $cusps = \FFI::new("double[37]");
        $ascmc = \FFI::new("double[20]");

        $result = SwissEphemeris::getFfi()->swe_houses(
            $julianDay,
            $latitude,
            $longitude,
            ord($houseSystem),
            $cusps,
            $ascmc
        );

        if ($result < 0) {
            return [
                'success' => false,
                'error' => 'Berekening van huizen mislukt'
            ];
        }

        $houses = [];
        for ($i = 1; $i <= 12; $i++) {
            $houses[$i] = [
                'name' => self::HOUSE_NAMES[$i],
                'longitude' => $cusps[$i],
                'position' => Formatter::formatPlanetLongitude($cusps[$i])
            ];
        }

        return [
            'success' => true,
            'system' => $houseSystem,
            'systemName' => $this->getSystemName($houseSystem),
            'houses' => $houses,
            'ascmc' => [
                'ascendant' => [
                    'longitude' => $ascmc[0],
                    'position' => Formatter::formatPlanetLongitude($ascmc[0])
                ],
                'mc' => [
                    'longitude' => $ascmc[1],
                    'position' => Formatter::formatPlanetLongitude($ascmc[1])
                ],
                'armc' => $ascmc[2],
                'vertex' => [
                    'longitude' => $ascmc[3],
                    'position' => Formatter::formatPlanetLongitude($ascmc[3])
                ],
                'equatorial_ascendant' => [
                    'longitude' => $ascmc[4],
                    'position' => Formatter::formatPlanetLongitude($ascmc[4])
                ]
            ]
        ];
    }

    public function calculateByTimestamp(
        int $timestamp,
        float $latitude,
        float $longitude,
        string $houseSystem = self::HSYS_KOCH
    ): array {
        $julianDay = $this->ephemeris->julianDayFromTimestamp($timestamp);
        
        return $this->calculate($julianDay, $latitude, $longitude, $houseSystem);
    }

    public function getSystemName(string $hsys): string
    {
        $names = [
            self::HSYS_PLACIDUS => 'Placidus',
            self::HSYS_KOCH => 'Koch',
            self::HSYS_PORPHYRIUS => 'Porphyrius',
            self::HSYS_REGIOMONTANUS => 'Regiomontanus',
            self::HSYS_CAMPANUS => 'Campanus',
            self::HSYS_EQUAL => 'Equal',
            self::HSYS_VEHLOW => 'Vehlow',
            self::HSYS_MERIDIAN => 'Meridian',
            self::HSYS_AZIMUTHAL => 'Azimuthal'
        ];

        return $names[$hsys] ?? 'Onbekend';
    }

    public function getEphemeris(): SwissEphemeris
    {
        return $this->ephemeris;
    }
}
