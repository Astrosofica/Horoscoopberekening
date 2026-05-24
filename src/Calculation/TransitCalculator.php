<?php

namespace Tijd\Calculation;

use Tijd\Ephemeris\SwissEphemeris;

class TransitCalculator
{
    private SwissEphemeris $ephemeris;
    private HousePlanetMatcher $houseMatcher;

    public function __construct(
        ?SwissEphemeris $ephemeris = null,
        ?HousePlanetMatcher $houseMatcher = null
    ) {
        $this->ephemeris = $ephemeris ?? new SwissEphemeris();
        $this->houseMatcher = $houseMatcher ?? new HousePlanetMatcher();
    }

    /**
     * Calculate current transit planet positions.
     *
     * @param array $radixHouses Radix house cusps for house determination
     * @param int|null $timestamp Optional timestamp (default: time())
     * @return array{
     *   planets: array<string, array{longitude: float, speed: float, house: int, direction: string}>,
     *   timestamp: int,
     *   date: string
     * }
     */
    public function calculateCurrentTransits(
        array $radixHouses,
        ?int $timestamp = null
    ): array {
        $timestamp = $timestamp ?? time();

        // julianDayFromTimestamp gebruikt date() wat server timezone respecteert.
        // Voor huidige posities moeten we UTC gebruiken: corrigeer voor server offset.
        $utcOffset = (int)date('Z');
        $utcTimestamp = $timestamp - $utcOffset;

        $julianDay = $this->ephemeris->julianDayFromTimestamp($utcTimestamp);

        // Sun t/m Pluto + Noordknoop
        $planets = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, SwissEphemeris::SE_TRUE_NODE];
        $positions = $this->ephemeris->calculateAllPlanets($julianDay, SwissEphemeris::SEFLG_SPEED, $planets);

        $houseCusps = $this->houseMatcher->extractHouseCusps($radixHouses);

        $result = [];
        foreach ($positions as $name => $data) {
            if (!isset($data['success']) || !$data['success']) {
                continue;
            }

            $longitude = $data['longitude'];
            $speed = $data['speed_longitude'] ?? 0;
            $house = $this->houseMatcher->findHouse($longitude, $houseCusps);

            $direction = $this->determineDirection($speed);

            $result[$name] = [
                'longitude' => $longitude,
                'speed' => $speed,
                'house' => $house,
                'direction' => $direction,
            ];
        }

        return [
            'planets' => $result,
            'timestamp' => $timestamp,
            'date' => date('Y-m-d H:i:s', $timestamp),
        ];
    }

    private function determineDirection(float $speed): string
    {
        if ($speed < -0.0001) {
            return 'R';
        }
        if (abs($speed) < 0.0001) {
            return 'S';
        }
        return 'D';
    }
}
