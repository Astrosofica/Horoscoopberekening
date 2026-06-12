<?php

namespace Astro\Calculation;

class SolarReturnCalculator
{
    private const THRESHOLD_DEGREES = 0.0000058;
    private const MAX_ITERATIONS = 5;
    private const SECONDS_PER_DAY = 86400;

    public function __construct(
        private PlanetCalculator $planetCalculator
    ) {}

    /**
     * Find the exact UTC moment when the Sun returns to its natal ecliptic position.
     *
     * @param float $natalSunLongitude Natal Sun ecliptic longitude (0-360 degrees)
     * @param int   $targetYear        Year for which to find the solar return
     * @param int   $natalMonth        Birth month (1-12)
     * @param int   $natalDay          Birth day (1-31)
     * @return int  Unix UTC timestamp of the solar return moment
     */
    public function findSolarReturn(
        float $natalSunLongitude,
        int $targetYear,
        int $natalMonth,
        int $natalDay
    ): int {
        $guess = strtotime("{$targetYear}-{$natalMonth}-{$natalDay} 00:00:00");

        for ($i = 0; $i < self::MAX_ITERATIONS; $i++) {
            $result = $this->planetCalculator->calculateForTimestamp($guess);
            $sunLon = $result['planets']['Sun']['longitude'];
            $sunSpeed = $result['planets']['Sun']['speed_longitude'];

            $diff = $this->normalizeAngleDiff($sunLon - $natalSunLongitude);

            if (abs($diff) < self::THRESHOLD_DEGREES) {
                return $guess;
            }

            $speedPerSec = $sunSpeed / self::SECONDS_PER_DAY;
            $correction = (int) round($diff / $speedPerSec);
            $guess -= $correction;
        }

        return $guess;
    }

    private function normalizeAngleDiff(float $diff): float
    {
        while ($diff < -180) {
            $diff += 360;
        }
        while ($diff > 180) {
            $diff -= 360;
        }
        return $diff;
    }
}
