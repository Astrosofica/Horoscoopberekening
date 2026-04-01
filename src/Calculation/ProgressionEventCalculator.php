<?php

namespace Tijd\Calculation;

use Tijd\Calculation\PlanetCalculator;
use Tijd\Calculation\HouseCalculator;
use Tijd\Ephemeris\SwissEphemeris;

class ProgressionEventCalculator
{
    private PlanetCalculator $planetCalculator;
    private HouseCalculator $houseCalculator;
    private SwissEphemeris $ephemeris;

    private const SOLAR_YEAR = 365.24219893;
    private const THRESHOLD_SECONDS = 0.5;
    private const MAX_REFINEMENT_ITERATIONS = 5;

    private const PLANET_NAMES = [
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
        10 => 'NorthNode',
        11 => 'Ascendant',
        12 => 'MC',
    ];

    private const SIGN_NAMES = [
        20 => 'Aries',
        21 => 'Taurus',
        22 => 'Gemini',
        23 => 'Cancer',
        24 => 'Leo',
        25 => 'Virgo',
        26 => 'Libra',
        27 => 'Scorpio',
        28 => 'Sagittarius',
        29 => 'Capricorn',
        30 => 'Aquarius',
        31 => 'Pisces',
    ];

    private const HOUSE_NAMES = [
        40 => 'House 1',
        41 => 'House 2',
        42 => 'House 3',
        43 => 'House 4',
        44 => 'House 5',
        45 => 'House 6',
        46 => 'House 7',
        47 => 'House 8',
        48 => 'House 9',
        49 => 'House 10',
        50 => 'House 11',
        51 => 'House 12',
    ];

    public function __construct()
    {
        $this->planetCalculator = new PlanetCalculator();
        $this->houseCalculator = new HouseCalculator();
        $this->ephemeris = new SwissEphemeris();
    }

    public function calculateEvents(
        array $radixData,
        array $progressivePlanetIndices,
        array $radixTargetIndices,
        array $aspectDegrees,
        int $startTimestamp,
        int $endTimestamp,
        bool $includeHouseIngress,
        bool $includeSignIngress,
        float $latitude,
        float $longitude,
        int $birthUtcTimestamp,
        int $utcOffset
    ): array {
        $events = [];

        $progStartTimestamp = $this->convertRealToProgression($startTimestamp, $birthUtcTimestamp);
        $progEndTimestamp = $this->convertRealToProgression($endTimestamp, $birthUtcTimestamp);

        $aspectTargets = $this->buildAspectTargets($radixData, $radixTargetIndices, $aspectDegrees);

        foreach ($progressivePlanetIndices as $planetIndex) {
            $progStartPos = $this->getProgressivePlanetPosition($planetIndex, $progStartTimestamp);
            $progEndPos = $this->getProgressivePlanetPosition($planetIndex, $progEndTimestamp);

            if (!$progStartPos['success'] || !$progEndPos['success']) {
                continue;
            }

            $startLon = $progStartPos['longitude'];
            $endLon = $progEndPos['longitude'];
            $startSpeed = $progStartPos['speed'];

            $isDirectAtStart = $startSpeed >= 0;
            $isDirectAtEnd = $progEndPos['speed'] >= 0;

            $planetName = self::PLANET_NAMES[$planetIndex] ?? 'Unknown';

            $relevantTargets = $this->filterTargetsInRange($aspectTargets, $startLon, $endLon, $isDirectAtStart);

            foreach ($relevantTargets as $target) {
                $estimatedTimestamp = $this->estimateEventTime(
                    $startTimestamp,
                    $startLon,
                    $target['aspect_position'],
                    $startSpeed
                );

                $refinedTimestamp = $this->refineEventTime(
                    $estimatedTimestamp,
                    $target['aspect_position'],
                    $planetIndex,
                    $birthUtcTimestamp
                );

                if ($refinedTimestamp >= $startTimestamp && $refinedTimestamp <= $endTimestamp) {
                    $progTimestamp = $this->convertRealToProgression($refinedTimestamp, $birthUtcTimestamp);
                    $finalPos = $this->getProgressivePlanetPosition($planetIndex, $progTimestamp);

                    $events[] = [
                        'timestamp' => $refinedTimestamp,
                        'date' => date('Y-m-d', $refinedTimestamp),
                        'progressive_planet' => $planetName,
                        'progressive_index' => $planetIndex,
                        'direction' => $finalPos['speed'] >= 0 ? 'D' : 'R',
                        'aspect' => $target['aspect_degrees'],
                        'radix_target' => $target['target_name'],
                        'radix_index' => $target['target_index'],
                        'radix_position' => $target['target_position'],
                        'progressive_position' => $finalPos['longitude'],
                        'event_type' => 'aspect',
                    ];
                }
            }

            if ($includeSignIngress) {
                $signEvents = $this->calculateSignIngress(
                    $planetIndex,
                    $startLon,
                    $endLon,
                    $startTimestamp,
                    $endTimestamp,
                    $birthUtcTimestamp,
                    $isDirectAtStart
                );
                $events = array_merge($events, $signEvents);
            }

            if ($includeHouseIngress) {
                $houseEvents = $this->calculateHouseIngress(
                    $planetIndex,
                    $startLon,
                    $endLon,
                    $startTimestamp,
                    $endTimestamp,
                    $birthUtcTimestamp,
                    $radixData,
                    $isDirectAtStart
                );
                $events = array_merge($events, $houseEvents);
            }

            if ($isDirectAtStart !== $isDirectAtEnd) {
                $rdEvent = $this->calculateRDTransition(
                    $planetIndex,
                    $startTimestamp,
                    $endTimestamp,
                    $birthUtcTimestamp,
                    $planetName
                );
                if ($rdEvent !== null) {
                    $events[] = $rdEvent;
                }
            }
        }

        usort($events, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);

        return $events;
    }

    private function convertRealToProgression(int $realTimestamp, int $birthTimestamp): int
    {
        $secProgRate = 1 / self::SOLAR_YEAR;
        return (int) round($birthTimestamp + ($realTimestamp - $birthTimestamp) * $secProgRate);
    }

    private function convertProgressionToReal(int $progTimestamp, int $birthTimestamp): int
    {
        $secProgRate = 1 / self::SOLAR_YEAR;
        return (int) round($birthTimestamp + ($progTimestamp - $birthTimestamp) / $secProgRate);
    }

    private function getProgressivePlanetPosition(int $planetIndex, int $progressionTimestamp): array
    {
        $result = $this->planetCalculator->calculateForTimestamp($progressionTimestamp);
        
        $planetName = self::PLANET_NAMES[$planetIndex] ?? null;
        if ($planetName === null || !isset($result['planets'][$planetName])) {
            return ['longitude' => 0, 'speed' => 0, 'success' => false];
        }

        $data = $result['planets'][$planetName];
        return [
            'longitude' => $data['longitude'] ?? 0,
            'speed' => $data['speed_longitude'] ?? 0,
            'success' => $data['success'] ?? false
        ];
    }

    private function normalizeAngle(float $angle): float
    {
        while ($angle < 0) $angle += 360;
        while ($angle >= 360) $angle -= 360;
        return $angle;
    }

    private function normalizeAngleDiff(float $diff): float
    {
        while ($diff < -180) $diff += 360;
        while ($diff > 180) $diff -= 360;
        return $diff;
    }

    private function buildAspectTargets(array $radixData, array $radixTargetIndices, array $aspectDegrees): array
    {
        $targets = [];
        
        foreach ($radixTargetIndices as $targetIndex) {
            $targetPos = $this->getRadixPosition($radixData, $targetIndex);
            $targetName = $this->getTargetName($targetIndex);
            
            foreach ($aspectDegrees as $aspectDeg) {
                $aspectPos = $this->normalizeAngle($targetPos + $aspectDeg);
                $targets[] = [
                    'target_index' => $targetIndex,
                    'target_name' => $targetName,
                    'target_position' => $targetPos,
                    'aspect_degrees' => $aspectDeg,
                    'aspect_position' => $aspectPos,
                ];
                
                if ($aspectDeg > 0 && $aspectDeg < 180) {
                    $aspectPosOpposite = $this->normalizeAngle($targetPos - $aspectDeg);
                    $targets[] = [
                        'target_index' => $targetIndex,
                        'target_name' => $targetName,
                        'target_position' => $targetPos,
                        'aspect_degrees' => $aspectDeg,
                        'aspect_position' => $aspectPosOpposite,
                    ];
                }
            }
        }
        
        usort($targets, fn($a, $b) => $a['aspect_position'] <=> $b['aspect_position']);
        
        return $targets;
    }

    private function getRadixPosition(array $radixData, int $targetIndex): float
    {
        if ($targetIndex >= 0 && $targetIndex <= 10) {
            $planetName = self::PLANET_NAMES[$targetIndex];
            return $radixData['planets'][$planetName]['longitude'] ?? 0;
        }
        
        if ($targetIndex === 11) {
            return $radixData['ascmc']['ascendant']['longitude'] ?? 0;
        }
        
        if ($targetIndex === 12) {
            return $radixData['ascmc']['mc']['longitude'] ?? 0;
        }
        
        if ($targetIndex >= 20 && $targetIndex <= 31) {
            return ($targetIndex - 20) * 30;
        }
        
        if ($targetIndex >= 40 && $targetIndex <= 51) {
            $houseNum = $targetIndex - 39;
            return $radixData['houses'][$houseNum]['longitude'] ?? 0;
        }
        
        return 0;
    }

    private function getTargetName(int $targetIndex): string
    {
        return self::PLANET_NAMES[$targetIndex] 
            ?? self::SIGN_NAMES[$targetIndex] 
            ?? self::HOUSE_NAMES[$targetIndex] 
            ?? 'Unknown';
    }

    private function filterTargetsInRange(array $targets, float $startLon, float $endLon, bool $isDirect): array
    {
        $filtered = [];
        
        foreach ($targets as $target) {
            $targetPos = $target['aspect_position'];
            
            if ($isDirect) {
                if ($endLon >= $startLon) {
                    if ($targetPos >= $startLon && $targetPos <= $endLon) {
                        $filtered[] = $target;
                    }
                } else {
                    if ($targetPos >= $startLon || $targetPos <= $endLon) {
                        $filtered[] = $target;
                    }
                }
            } else {
                if ($endLon <= $startLon) {
                    if ($targetPos >= $endLon && $targetPos <= $startLon) {
                        $filtered[] = $target;
                    }
                } else {
                    if ($targetPos >= $endLon || $targetPos <= $startLon) {
                        $filtered[] = $target;
                    }
                }
            }
        }
        
        return $filtered;
    }

    private function estimateEventTime(int $startTimestamp, float $startLon, float $targetLon, float $speed): int
    {
        if (abs($speed) < 0.0001) {
            return $startTimestamp;
        }

        $diff = $this->normalizeAngleDiff($targetLon - $startLon);
        
        if ($speed < 0 && $diff > 0) {
            $diff = $diff - 360;
        } elseif ($speed > 0 && $diff < 0) {
            $diff = $diff + 360;
        }

        $daysToEvent = $diff / $speed;
        
        return (int) round($startTimestamp + $daysToEvent * 86400);
    }

    private function refineEventTime(
        int $estimatedTimestamp,
        float $targetLongitude,
        int $planetIndex,
        int $birthTimestamp
    ): int {
        $thresholdDegrees = self::THRESHOLD_SECONDS / 86400;
        $prevSpeed = null;
        
        for ($i = 0; $i < self::MAX_REFINEMENT_ITERATIONS; $i++) {
            $progTimestamp = $this->convertRealToProgression($estimatedTimestamp, $birthTimestamp);
            $position = $this->getProgressivePlanetPosition($planetIndex, $progTimestamp);
            
            if (!$position['success']) {
                break;
            }
            
            $currentLon = $position['longitude'];
            $currentSpeed = $position['speed'];
            
            $diff = $this->normalizeAngleDiff($currentLon - $targetLongitude);
            
            if (abs($diff) < $thresholdDegrees) {
                break;
            }
            
            if ($prevSpeed !== null && ($prevSpeed * $currentSpeed) < 0) {
                break;
            }
            
            $prevSpeed = $currentSpeed;
            
            if (abs($currentSpeed) > 0.0001) {
                $timeCorrectionSeconds = ($diff / $currentSpeed) * 86400;
                $estimatedTimestamp = (int) round($estimatedTimestamp - $timeCorrectionSeconds);
            }
        }
        
        return $estimatedTimestamp;
    }

    private function calculateSignIngress(
        int $planetIndex,
        float $startLon,
        float $endLon,
        int $startTimestamp,
        int $endTimestamp,
        int $birthTimestamp,
        bool $isDirect
    ): array {
        $events = [];
        $planetName = self::PLANET_NAMES[$planetIndex] ?? 'Unknown';

        $signBoundaries = [];
        for ($i = 0; $i < 12; $i++) {
            $signBoundaries[] = ['aspect_position' => $i * 30];
        }

        $relevantBoundaries = $this->filterTargetsInRange($signBoundaries, $startLon, $endLon, $isDirect);

        foreach ($relevantBoundaries as $boundary) {
            $targetLon = $boundary['aspect_position'];
            $signIndex = (int) ($targetLon / 30);
            $signName = self::SIGN_NAMES[20 + $signIndex] ?? 'Unknown';

            $progStart = $this->convertRealToProgression($startTimestamp, $birthTimestamp);
            $progStartPos = $this->getProgressivePlanetPosition($planetIndex, $progStart);
            $speed = $progStartPos['speed'];

            $estimatedTimestamp = $this->estimateEventTime($startTimestamp, $startLon, $targetLon, $speed);
            $refinedTimestamp = $this->refineEventTime($estimatedTimestamp, $targetLon, $planetIndex, $birthTimestamp);

            if ($refinedTimestamp >= $startTimestamp && $refinedTimestamp <= $endTimestamp) {
                $progTimestamp = $this->convertRealToProgression($refinedTimestamp, $birthTimestamp);
                $finalPos = $this->getProgressivePlanetPosition($planetIndex, $progTimestamp);

                $events[] = [
                    'timestamp' => $refinedTimestamp,
                    'date' => date('Y-m-d', $refinedTimestamp),
                    'progressive_planet' => $planetName,
                    'progressive_index' => $planetIndex,
                    'direction' => $finalPos['speed'] >= 0 ? 'D' : 'R',
                    'aspect' => 0,
                    'radix_target' => $signName,
                    'radix_index' => 20 + $signIndex,
                    'radix_position' => $targetLon,
                    'progressive_position' => $finalPos['longitude'],
                    'event_type' => 'sign_ingress',
                ];
            }
        }

        return $events;
    }

    private function calculateHouseIngress(
        int $planetIndex,
        float $startLon,
        float $endLon,
        int $startTimestamp,
        int $endTimestamp,
        int $birthTimestamp,
        array $radixData,
        bool $isDirect
    ): array {
        $events = [];
        $planetName = self::PLANET_NAMES[$planetIndex] ?? 'Unknown';

        $houseCusps = [];
        for ($i = 1; $i <= 12; $i++) {
            $cuspLon = $radixData['houses'][$i]['longitude'] ?? 0;
            $houseCusps[] = ['aspect_position' => $cuspLon, 'house_num' => $i];
        }

        $relevantCusps = $this->filterTargetsInRange($houseCusps, $startLon, $endLon, $isDirect);

        foreach ($relevantCusps as $cusp) {
            $targetLon = $cusp['aspect_position'];
            $houseNum = $cusp['house_num'];
            $houseName = self::HOUSE_NAMES[39 + $houseNum] ?? "House $houseNum";

            $progStart = $this->convertRealToProgression($startTimestamp, $birthTimestamp);
            $progStartPos = $this->getProgressivePlanetPosition($planetIndex, $progStart);
            $speed = $progStartPos['speed'];

            $estimatedTimestamp = $this->estimateEventTime($startTimestamp, $startLon, $targetLon, $speed);
            $refinedTimestamp = $this->refineEventTime($estimatedTimestamp, $targetLon, $planetIndex, $birthTimestamp);

            if ($refinedTimestamp >= $startTimestamp && $refinedTimestamp <= $endTimestamp) {
                $progTimestamp = $this->convertRealToProgression($refinedTimestamp, $birthTimestamp);
                $finalPos = $this->getProgressivePlanetPosition($planetIndex, $progTimestamp);

                $events[] = [
                    'timestamp' => $refinedTimestamp,
                    'date' => date('Y-m-d', $refinedTimestamp),
                    'progressive_planet' => $planetName,
                    'progressive_index' => $planetIndex,
                    'direction' => $finalPos['speed'] >= 0 ? 'D' : 'R',
                    'aspect' => 0,
                    'radix_target' => $houseName,
                    'radix_index' => 39 + $houseNum,
                    'radix_position' => $targetLon,
                    'progressive_position' => $finalPos['longitude'],
                    'event_type' => 'house_ingress',
                ];
            }
        }

        return $events;
    }

    private function calculateRDTransition(
        int $planetIndex,
        int $startTimestamp,
        int $endTimestamp,
        int $birthTimestamp,
        string $planetName
    ): ?array {
        $midTimestamp = (int) round(($startTimestamp + $endTimestamp) / 2);
        
        $progStart = $this->convertRealToProgression($startTimestamp, $birthTimestamp);
        $progEnd = $this->convertRealToProgression($endTimestamp, $birthTimestamp);
        $progMid = $this->convertRealToProgression($midTimestamp, $birthTimestamp);
        
        $posStart = $this->getProgressivePlanetPosition($planetIndex, $progStart);
        $posEnd = $this->getProgressivePlanetPosition($planetIndex, $progEnd);
        $posMid = $this->getProgressivePlanetPosition($planetIndex, $progMid);
        
        $speedStart = $posStart['speed'];
        $speedMid = $posMid['speed'];
        
        $transitionFound = false;
        $searchStart = $startTimestamp;
        $searchEnd = $endTimestamp;
        
        if (($speedStart >= 0 && $speedMid < 0) || ($speedStart < 0 && $speedMid >= 0)) {
            $transitionFound = true;
            $searchEnd = $midTimestamp;
        } elseif (($speedMid >= 0 && $posEnd['speed'] < 0) || ($speedMid < 0 && $posEnd['speed'] >= 0)) {
            $transitionFound = true;
            $searchStart = $midTimestamp;
        }
        
        if (!$transitionFound) {
            return null;
        }
        
        for ($i = 0; $i < 10; $i++) {
            $diff = $searchEnd - $searchStart;
            if ($diff < 86400) {
                break;
            }
            
            $midSearch = (int) round(($searchStart + $searchEnd) / 2);
            $progMidSearch = $this->convertRealToProgression($midSearch, $birthTimestamp);
            $posMidSearch = $this->getProgressivePlanetPosition($planetIndex, $progMidSearch);
            $speedMidSearch = $posMidSearch['speed'];
            
            if (($speedStart >= 0 && $speedMidSearch < 0) || ($speedStart < 0 && $speedMidSearch >= 0)) {
                $searchEnd = $midSearch;
            } else {
                $searchStart = $midSearch;
                $speedStart = $speedMidSearch;
            }
        }
        
        $transitionTimestamp = (int) round(($searchStart + $searchEnd) / 2);
        $progTransition = $this->convertRealToProgression($transitionTimestamp, $birthTimestamp);
        $posTransition = $this->getProgressivePlanetPosition($planetIndex, $progTransition);
        
        $wasDirect = $posStart['speed'] >= 0;
        
        return [
            'timestamp' => $transitionTimestamp,
            'date' => date('Y-m-d', $transitionTimestamp),
            'progressive_planet' => $planetName,
            'progressive_index' => $planetIndex,
            'direction' => 'S',
            'aspect' => 0,
            'radix_target' => $wasDirect ? 'Gaat Retrograde' : 'Gaat Direct',
            'radix_index' => $wasDirect ? 60 : 61,
            'radix_position' => $posTransition['longitude'],
            'progressive_position' => $posTransition['longitude'],
            'event_type' => 'rd_transition',
        ];
    }
}