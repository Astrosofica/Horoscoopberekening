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

        // ZELFDE logica als Horoscope entity
        $localTimestamp = strtotime($birthDate . ' ' . $birthTime);
        $birthTimestamp = $localTimestamp - $utcOffset;

        $solaryear = 365.24219893;
        $secProgRate = 1 / $solaryear;
        
        $progressBirthTimestamp = $birthTimestamp + ($progressTimestamp - $birthTimestamp) * $secProgRate;
        $progressBirthTimestamp = (int)round($progressBirthTimestamp);

        $progressBirthDateTime = new \DateTime();
        $progressBirthDateTime->setTimestamp($progressBirthTimestamp);
        $progressBirthDateTime->setTimezone(new \DateTimeZone('UTC'));

        $birthUtcDateTime = new \DateTime();
        $birthUtcDateTime->setTimestamp($birthTimestamp);
        $birthUtcDateTime->setTimezone(new \DateTimeZone('UTC'));

        $nowDateTime = new \DateTime();
        $nowDateTime->setTimestamp($progressTimestamp);
        $nowDateTime->setTimezone(new \DateTimeZone('UTC'));

        $actualAgeInterval = $birthUtcDateTime->diff($nowDateTime);
        $years = $actualAgeInterval->y;

        $progressDays = ($progressBirthTimestamp - $birthTimestamp) / 86400;

        $planetResult = $this->planetCalculator->calculateForTimestamp($progressBirthTimestamp);

        $preTimestamp = $birthTimestamp + ($years * 86400);
        $postTimestamp = $birthTimestamp + (($years + 1) * 86400);

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
        $fractionalDay = $timeDiff / $secondsPerDay;

        $preAsc = $preHouses['ascmc']['ascendant']['longitude'];
        $postAsc = $postHouses['ascmc']['ascendant']['longitude'];
        $ascDiff = $this->normalizeAngleDiff($postAsc - $preAsc);
        $progressAsc = $preAsc + ($ascDiff * $fractionalDay);

        $preMc = $preHouses['ascmc']['mc']['longitude'];
        $postMc = $postHouses['ascmc']['mc']['longitude'];
        $mcDiff = $this->normalizeAngleDiff($postMc - $preMc);
        $progressMc = $preMc + ($mcDiff * $fractionalDay);

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
            'fractional_day' => $fractionalDay,
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