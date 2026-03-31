<?php

namespace Tijd\Calculation;

use Tijd\Calculation\PlanetCalculator;
use Tijd\Calculation\HouseCalculator;
use Tijd\Calculation\HousePlanetMatcher;
use Tijd\Ephemeris\SwissEphemeris;

class ProgressionCalculator
{
    private PlanetCalculator $planetCalculator;
    private HouseCalculator $houseCalculator;
    private HousePlanetMatcher $houseMatcher;
    private SwissEphemeris $ephemeris;

    public function __construct()
    {
        $this->planetCalculator = new PlanetCalculator();
        $this->houseCalculator = new HouseCalculator();
        $this->houseMatcher = new HousePlanetMatcher();
        $this->ephemeris = new SwissEphemeris();
    }

    public function calculateSecondaryProgressions(
        array $radixHouses,
        string $birthDate,
        string $birthTime,
        float $latitude,
        float $longitude,
        int $utcOffset,
        ?int $progressTimestamp = null
    ): array {
        if ($progressTimestamp === null) {
            $progressTimestamp = time();
        }

        $birthDateTime = new \DateTime($birthDate . ' ' . $birthTime, new \DateTimeZone('UTC'));
        $birthTimestamp = $birthDateTime->getTimestamp() + $utcOffset;

        $progressDateTime = new \DateTime();
        $progressDateTime->setTimestamp($progressTimestamp);

        $ageInterval = $birthDateTime->diff($progressDateTime);
        $years = $ageInterval->y;
        $totalDays = $ageInterval->days;
        $daysInCurrentYear = $totalDays % 365;

        $progressDays = $years + ($daysInCurrentYear / 365.24219893);

        $progressBirthDateTime = clone $birthDateTime;
        $progressBirthDateTime->modify("+{$progressDays} days");
        $progressBirthTimestamp = $progressBirthDateTime->getTimestamp();

        $planetResult = $this->planetCalculator->calculateForTimestamp($progressBirthTimestamp);

        $preDateTime = clone $birthDateTime;
        $preDateTime->modify("+{$years} days");
        $preTimestamp = $preDateTime->getTimestamp();

        $postDateTime = clone $birthDateTime;
        $postDateTime->modify("+{$years} days +1 day");
        $postTimestamp = $postDateTime->getTimestamp();

        $preHouses = $this->houseCalculator->calculateByTimestamp(
            $preTimestamp,
            $latitude,
            $longitude,
            HouseCalculator::HSYS_KOCH
        );

        $postHouses = $this->houseCalculator->calculateByTimestamp(
            $postTimestamp,
            $latitude,
            $longitude,
            HouseCalculator::HSYS_KOCH
        );

        $timeDiff = $progressBirthTimestamp - $preTimestamp;
        $secondsPerDay = 86400;
        $fractionalYear = $timeDiff / $secondsPerDay;

        $preAsc = $preHouses['ascmc']['ascendant']['longitude'];
        $postAsc = $postHouses['ascmc']['ascendant']['longitude'];
        $ascDiff = $this->normalizeAngleDiff($postAsc - $preAsc);
        $progressAsc = $preAsc + ($ascDiff * $fractionalYear);

        $preMc = $preHouses['ascmc']['mc']['longitude'];
        $postMc = $postHouses['ascmc']['mc']['longitude'];
        $mcDiff = $this->normalizeAngleDiff($postMc - $preMc);
        $progressMc = $preMc + ($mcDiff * $fractionalYear);

        $progressPlanets = [];
        $radixHouseCusps = $this->extractHouseCusps($radixHouses);

        foreach ($planetResult['planets'] as $name => $data) {
            if (!isset($data['success']) || !$data['success']) {
                continue;
            }

            $longitude = $data['longitude'];
            $speed = $data['speed_longitude'] ?? 0;
            $house = $this->houseMatcher->findHouse($longitude, $radixHouseCusps);

            $direction = 'D';
            if ($speed < -0.0001) {
                $direction = 'R';
            } elseif (abs($speed) < 0.0001) {
                $direction = 'S';
            }

            $progressPlanets[$name] = [
                'longitude' => $longitude,
                'speed' => $speed,
                'house' => $house,
                'direction' => $direction,
                'success' => true
            ];
        }

        $progressAscHouse = $this->houseMatcher->findHouse($progressAsc, $radixHouseCusps);
        $progressMcHouse = $this->houseMatcher->findHouse($progressMc, $radixHouseCusps);

        $progressPlanets['Ascendant'] = [
            'longitude' => $this->normalizeAngle($progressAsc),
            'speed' => 1,
            'house' => $progressAscHouse,
            'direction' => 'D',
            'success' => true
        ];

        $progressPlanets['MC'] = [
            'longitude' => $this->normalizeAngle($progressMc),
            'speed' => 1,
            'house' => $progressMcHouse,
            'direction' => 'D',
            'success' => true
        ];

        return [
            'planets' => $progressPlanets,
            'ascmc' => [
                'ascendant' => [
                    'longitude' => $this->normalizeAngle($progressAsc),
                    'house' => $progressAscHouse
                ],
                'mc' => [
                    'longitude' => $this->normalizeAngle($progressMc),
                    'house' => $progressMcHouse
                ]
            ],
            'progress_days' => $progressDays,
            'years' => $years,
            'fractional_year' => $fractionalYear,
            'progress_date' => $progressBirthDateTime->format('Y-m-d H:i:s')
        ];
    }

    private function extractHouseCusps(array $houses): array
    {
        $cusps = [];
        for ($i = 1; $i <= 12; $i++) {
            $cusps[$i] = $houses[$i]['longitude'] ?? 0;
        }
        return $cusps;
    }

    private function normalizeAngle(float $angle): float
    {
        while ($angle < 0) {
            $angle += 360;
        }
        while ($angle >= 360) {
            $angle -= 360;
        }
        return $angle;
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