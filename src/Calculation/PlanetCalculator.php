<?php

namespace Astro\Calculation;

use Astro\Ephemeris\SwissEphemeris;

class PlanetCalculator
{
    private SwissEphemeris $ephemeris;

    public function __construct(?SwissEphemeris $ephemeris = null)
    {
        $this->ephemeris = $ephemeris ?? new SwissEphemeris();
    }

    public function calculateForTimestamp(int $timestamp, array $options = []): array
    {
        $julianDay = $this->ephemeris->julianDayFromTimestamp($timestamp);
        
        $iflag = $options['flags'] ?? SwissEphemeris::SEFLG_SPEED;
        $planets = $options['planets'] ?? null;

        return [
            'timestamp' => $timestamp,
            'julian_day' => $julianDay,
            'date' => date('Y-m-d H:i:s', $timestamp),
            'planets' => $this->ephemeris->calculateAllPlanets($julianDay, $iflag, $planets)
        ];
    }

    public function getPlanetPositions(
        int $timestamp,
        string $timezoneId,
        float $longitude,
        float $latitude = 52.0,
        string $houseSystem = 'K'
    ): array {
        $julianDay = $this->ephemeris->julianDayFromTimestamp($timestamp);
        
        $positions = $this->ephemeris->calculateAllPlanets($julianDay);
        
        $houseCalculator = new HouseCalculator($this->ephemeris);
        $houses = $houseCalculator->calculate($julianDay, $latitude, $longitude, $houseSystem);

        return [
            'timestamp' => $timestamp,
            'timezone' => $timezoneId,
            'coordinates' => [
                'latitude' => $latitude,
                'longitude' => $longitude
            ],
            'julian_day' => $julianDay,
            'planets' => $positions,
            'houses' => $houses
        ];
    }

    public function getEphemeris(): SwissEphemeris
    {
        return $this->ephemeris;
    }
}
