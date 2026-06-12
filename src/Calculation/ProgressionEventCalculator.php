<?php

namespace Astro\Calculation;

use Astro\Calculation\PlanetCalculator;
use Astro\Calculation\HouseCalculator;
use Astro\Ephemeris\SwissEphemeris;

class ProgressionEventCalculator
{
    private PlanetCalculator $planetCalculator;
    private HouseCalculator $houseCalculator;
    private SwissEphemeris $ephemeris;

    private const SOLAR_YEAR = 365.24219893;
    private const THRESHOLD_SECONDS = 0.5;
    private const MAX_REFINEMENT_ITERATIONS = 5;
    private const SECONDS_PER_DAY = 86400;

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

    private int $birthTimestamp;

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
        $this->birthTimestamp = $birthUtcTimestamp;
        $events = [];

        // Converteer echte tijd naar progressieve tijd
        $progStartTimestamp = $this->realToProgression($startTimestamp);
        $progEndTimestamp = $this->realToProgression($endTimestamp);

        // Bouw lijst van aspect targets (posities waar de progressieve planeet moet komen)
        $aspectTargets = $this->buildAspectTargets($radixData, $radixTargetIndices, $aspectDegrees);

        foreach ($progressivePlanetIndices as $planetIndex) {
            // Haal posities en speeds bij start en einde (in progressieve tijd)
            $startPos = $this->getProgressivePlanetPosition($planetIndex, $progStartTimestamp);
            $endPos = $this->getProgressivePlanetPosition($planetIndex, $progEndTimestamp);

            if (!$startPos['success'] || !$endPos['success']) {
                continue;
            }

            $startLon = $startPos['longitude'];
            $endLon = $endPos['longitude'];
            $startSpeed = $startPos['speed'];
            $endSpeed = $endPos['speed'];

            $isDirectAtStart = $startSpeed >= 0;
            $isDirectAtEnd = $endSpeed >= 0;

            $planetName = self::PLANET_NAMES[$planetIndex] ?? 'Unknown';

            // Vind targets in de range van de beweging
            $relevantTargets = $this->filterTargetsInRange($aspectTargets, $startLon, $endLon, $startSpeed, $endSpeed);

            foreach ($relevantTargets as $target) {
                // Bereken progressieve timestamp waarop het aspect exact is
                $progEventTimestamp = $this->findEventTime(
                    $planetIndex,
                    $progStartTimestamp,
                    $progEndTimestamp,
                    $startLon,
                    $startSpeed,
                    $endSpeed,
                    $target['aspect_position']
                );

                if ($progEventTimestamp === null) {
                    continue;
                }

                // Check of event binnen de periode valt
                if ($progEventTimestamp >= $progStartTimestamp && $progEventTimestamp <= $progEndTimestamp) {
                    // Converteer progressieve tijd terug naar echte tijd
                    $realTimestamp = $this->progressionToReal($progEventTimestamp);

                    // Haal finale positie op
                    $finalPos = $this->getProgressivePlanetPosition($planetIndex, $progEventTimestamp);

                    $events[] = [
                        'timestamp' => $realTimestamp,
                        'date' => date('Y-m-d', $realTimestamp),
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

            // Teken ingress
            if ($includeSignIngress) {
                $signEvents = $this->calculateSignIngress(
                    $planetIndex,
                    $startLon,
                    $endLon,
                    $startSpeed,
                    $endSpeed,
                    $progStartTimestamp,
                    $progEndTimestamp
                );
                $events = array_merge($events, $signEvents);
            }

            // Huis ingress
            if ($includeHouseIngress) {
                $houseEvents = $this->calculateHouseIngress(
                    $planetIndex,
                    $startLon,
                    $endLon,
                    $startSpeed,
                    $endSpeed,
                    $progStartTimestamp,
                    $progEndTimestamp,
                    $radixData
                );
                $events = array_merge($events, $houseEvents);
            }

            // RD transitie
            if ($isDirectAtStart !== $isDirectAtEnd) {
                $rdEvent = $this->calculateRDTransition(
                    $planetIndex,
                    $startSpeed,
                    $progStartTimestamp,
                    $progEndTimestamp,
                    $planetName
                );
                if ($rdEvent !== null) {
                    $events[] = $rdEvent;
                }
            }
        }

        // Sorteer op echte tijd
        usort($events, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);

        return $events;
    }

    private function realToProgression(int $realTimestamp): int
    {
        // Echte tijd → progressieve tijd
        // 1 jaar echt = 1 dag progressief
        return (int) round($this->birthTimestamp + ($realTimestamp - $this->birthTimestamp) / self::SOLAR_YEAR);
    }

    private function progressionToReal(int $progTimestamp): int
    {
        // Progressieve tijd → echte tijd
        // 1 dag progressief = 1 jaar echt
        return (int) round($this->birthTimestamp + ($progTimestamp - $this->birthTimestamp) * self::SOLAR_YEAR);
    }

    private function getProgressivePlanetPosition(int $planetIndex, int $progTimestamp): array
    {
        $result = $this->planetCalculator->calculateForTimestamp($progTimestamp);
        
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
                // Vooruit
                $aspectPos = $this->normalizeAngle($targetPos + $aspectDeg);
                $targets[] = [
                    'target_index' => $targetIndex,
                    'target_name' => $targetName,
                    'target_position' => $targetPos,
                    'aspect_degrees' => $aspectDeg,
                    'aspect_position' => $aspectPos,
                ];
                
                // Achteruit (behalve voor 0 en 180)
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
        
        // Sorteer op positie
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

    private function filterTargetsInRange(array $targets, float $startLon, float $endLon, float $startSpeed, float $endSpeed): array
    {
        $filtered = [];
        $isDirect = $startSpeed >= 0;
        
        // Bepaal of planeet over 0°/360° grens gaat
        $crossesZero = $isDirect ? ($endLon < $startLon) : ($endLon > $startLon);
        
        foreach ($targets as $target) {
            $targetPos = $target['aspect_position'];
            $inRange = false;
            
            if ($isDirect) {
                // Direct beweging
                if ($crossesZero) {
                    // Van hoge naar lage positie (over 0°) - bv 350° → 10°
                    if ($targetPos >= $startLon || $targetPos <= $endLon) {
                        $inRange = true;
                    }
                } else {
                    // Normale beweging
                    if ($targetPos >= $startLon && $targetPos <= $endLon) {
                        $inRange = true;
                    }
                }
            } else {
                // Retrograde beweging
                if ($crossesZero) {
                    // Van lage naar hoge positie (over 0°) - bv 10° → 350°
                    if ($targetPos <= $startLon || $targetPos >= $endLon) {
                        $inRange = true;
                    }
                } else {
                    // Normale retrograde
                    if ($targetPos >= $endLon && $targetPos <= $startLon) {
                        $inRange = true;
                    }
                }
            }
            
            if ($inRange) {
                $filtered[] = $target;
            }
        }
        
        return $filtered;
    }

    private function findEventTime(
        int $planetIndex,
        int $progStartTimestamp,
        int $progEndTimestamp,
        float $startLon,
        float $startSpeed,
        float $endSpeed,
        float $targetLon
    ): ?int {
        // Voorkom delen door nul
        if (abs($startSpeed) < 0.0001) {
            return null;
        }
        
        $isDirect = $startSpeed >= 0;
        
        // Bereken positieverschil in bewegingsrichting
        if ($isDirect) {
            // Direct: beweegt van laag naar hoog
            $diff = $targetLon - $startLon;
            if ($diff < 0) {
                $diff += 360;  // Over de 0° grens
            }
        } else {
            // Retrograde: beweegt van hoog naar laag
            $diff = $startLon - $targetLon;
            if ($diff < 0) {
                $diff += 360;  // Over de 0° grens
            }
        }
        
        // Geschatte tijd in progressieve seconden
        // speed is in graden/dag, diff in graden
        // tijd in dagen = diff / |speed|
        $estimatedDays = $diff / abs($startSpeed);
        $estimatedSeconds = $estimatedDays * self::SECONDS_PER_DAY;
        $progEstimatedTimestamp = $progStartTimestamp + (int) round($estimatedSeconds);
        
        // Check of dit binnen onze range valt (met marge voor refinement)
        $margin = self::SECONDS_PER_DAY * 2;
        if ($progEstimatedTimestamp < $progStartTimestamp - $margin || 
            $progEstimatedTimestamp > $progEndTimestamp + $margin) {
            return null;
        }
        
        // Verfijn met Newton-Raphson
        return $this->refineEventTime($planetIndex, $progEstimatedTimestamp, $targetLon);
    }

    private function refineEventTime(int $planetIndex, int $progEstimatedTimestamp, float $targetLon): ?int
    {
        $timestamp = $progEstimatedTimestamp;
        
        for ($i = 0; $i < self::MAX_REFINEMENT_ITERATIONS; $i++) {
            $pos = $this->getProgressivePlanetPosition($planetIndex, $timestamp);
            
            if (!$pos['success'] || abs($pos['speed']) < 0.0001) {
                break;
            }
            
            $diff = $this->normalizeAngleDiff($pos['longitude'] - $targetLon);
            
            // Threshold: 0.5 seconde progressief = 0.5/86400 graden
            $thresholdDegrees = self::THRESHOLD_SECONDS / self::SECONDS_PER_DAY;
            
            if (abs($diff) < $thresholdDegrees) {
                return $timestamp;
            }
            
            // Corrigeer timestamp: diff/speed = dagen, * 86400 = seconden
            $correctionSeconds = ($diff / $pos['speed']) * self::SECONDS_PER_DAY;
            $timestamp = (int) round($timestamp - $correctionSeconds);
        }
        
        // Return zelfs als niet perfect verfijnd
        return $timestamp;
    }

    private function calculateSignIngress(
        int $planetIndex,
        float $startLon,
        float $endLon,
        float $startSpeed,
        float $endSpeed,
        int $progStartTimestamp,
        int $progEndTimestamp
    ): array {
        $events = [];
        $planetName = self::PLANET_NAMES[$planetIndex] ?? 'Unknown';

        // Alle teken grenzen (0°, 30°, 60°, ..., 330°)
        $signBoundaries = [];
        for ($i = 0; $i < 12; $i++) {
            $signBoundaries[] = [
                'position' => $i * 30,
                'sign_index' => $i,
            ];
        }

        $isDirect = $startSpeed >= 0;
        $crossesZero = $isDirect ? ($endLon < $startLon) : ($endLon > $startLon);

        foreach ($signBoundaries as $boundary) {
            $targetPos = $boundary['position'];
            $inRange = false;
            
            if ($isDirect) {
                if ($crossesZero) {
                    if ($targetPos >= $startLon || $targetPos <= $endLon) {
                        $inRange = true;
                    }
                } else {
                    if ($targetPos >= $startLon && $targetPos <= $endLon) {
                        $inRange = true;
                    }
                }
            } else {
                if ($crossesZero) {
                    if ($targetPos <= $startLon || $targetPos >= $endLon) {
                        $inRange = true;
                    }
                } else {
                    if ($targetPos >= $endLon && $targetPos <= $startLon) {
                        $inRange = true;
                    }
                }
            }
            
            if (!$inRange) {
                continue;
            }

            $progEventTimestamp = $this->findEventTime(
                $planetIndex,
                $progStartTimestamp,
                $progEndTimestamp,
                $startLon,
                $startSpeed,
                $endSpeed,
                $targetPos
            );

            if ($progEventTimestamp === null) {
                continue;
            }

            if ($progEventTimestamp >= $progStartTimestamp && $progEventTimestamp <= $progEndTimestamp) {
                $realTimestamp = $this->progressionToReal($progEventTimestamp);
                $finalPos = $this->getProgressivePlanetPosition($planetIndex, $progEventTimestamp);
                $signName = self::SIGN_NAMES[20 + $boundary['sign_index']] ?? 'Unknown';

                $events[] = [
                    'timestamp' => $realTimestamp,
                    'date' => date('Y-m-d', $realTimestamp),
                    'progressive_planet' => $planetName,
                    'progressive_index' => $planetIndex,
                    'direction' => $finalPos['speed'] >= 0 ? 'D' : 'R',
                    'aspect' => 0,
                    'radix_target' => $signName,
                    'radix_index' => 20 + $boundary['sign_index'],
                    'radix_position' => $targetPos,
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
        float $startSpeed,
        float $endSpeed,
        int $progStartTimestamp,
        int $progEndTimestamp,
        array $radixData
    ): array {
        $events = [];
        $planetName = self::PLANET_NAMES[$planetIndex] ?? 'Unknown';

        // Alle huis cusps
        $houseCusps = [];
        for ($i = 1; $i <= 12; $i++) {
            $cuspPos = $radixData['houses'][$i]['longitude'] ?? 0;
            $houseCusps[] = [
                'position' => $cuspPos,
                'house_num' => $i,
            ];
        }

        $isDirect = $startSpeed >= 0;
        $crossesZero = $isDirect ? ($endLon < $startLon) : ($endLon > $startLon);

        foreach ($houseCusps as $cusp) {
            $targetPos = $cusp['position'];
            $inRange = false;
            
            if ($isDirect) {
                if ($crossesZero) {
                    if ($targetPos >= $startLon || $targetPos <= $endLon) {
                        $inRange = true;
                    }
                } else {
                    if ($targetPos >= $startLon && $targetPos <= $endLon) {
                        $inRange = true;
                    }
                }
            } else {
                if ($crossesZero) {
                    if ($targetPos <= $startLon || $targetPos >= $endLon) {
                        $inRange = true;
                    }
                } else {
                    if ($targetPos >= $endLon && $targetPos <= $startLon) {
                        $inRange = true;
                    }
                }
            }
            
            if (!$inRange) {
                continue;
            }

            $progEventTimestamp = $this->findEventTime(
                $planetIndex,
                $progStartTimestamp,
                $progEndTimestamp,
                $startLon,
                $startSpeed,
                $endSpeed,
                $targetPos
            );

            if ($progEventTimestamp === null) {
                continue;
            }

            if ($progEventTimestamp >= $progStartTimestamp && $progEventTimestamp <= $progEndTimestamp) {
                $realTimestamp = $this->progressionToReal($progEventTimestamp);
                $finalPos = $this->getProgressivePlanetPosition($planetIndex, $progEventTimestamp);
                $houseName = self::HOUSE_NAMES[39 + $cusp['house_num']] ?? "House {$cusp['house_num']}";

                $events[] = [
                    'timestamp' => $realTimestamp,
                    'date' => date('Y-m-d', $realTimestamp),
                    'progressive_planet' => $planetName,
                    'progressive_index' => $planetIndex,
                    'direction' => $finalPos['speed'] >= 0 ? 'D' : 'R',
                    'aspect' => 0,
                    'radix_target' => $houseName,
                    'radix_index' => 39 + $cusp['house_num'],
                    'radix_position' => $targetPos,
                    'progressive_position' => $finalPos['longitude'],
                    'event_type' => 'house_ingress',
                ];
            }
        }

        return $events;
    }

    private function calculateRDTransition(
        int $planetIndex,
        float $startSpeed,
        int $progStartTimestamp,
        int $progEndTimestamp,
        string $planetName
    ): ?array {
        // Binary search om het moment te vinden waarop speed = 0
        $searchStart = $progStartTimestamp;
        $searchEnd = $progEndTimestamp;
        $wasDirect = $startSpeed >= 0;
        
        for ($iteration = 0; $iteration < 15; $iteration++) {
            $diff = $searchEnd - $searchStart;
            if ($diff < 60) { // Minder dan 1 minuut verschil
                break;
            }
            
            $midTimestamp = (int) round(($searchStart + $searchEnd) / 2);
            $midPos = $this->getProgressivePlanetPosition($planetIndex, $midTimestamp);
            
            if (!$midPos['success']) {
                break;
            }
            
            $midSpeed = $midPos['speed'];
            
            if (($wasDirect && $midSpeed < 0) || (!$wasDirect && $midSpeed >= 0)) {
                // Transitie in eerste helft
                $searchEnd = $midTimestamp;
            } else {
                // Transitie in tweede helft
                $searchStart = $midTimestamp;
            }
        }
        
        $progTransitionTimestamp = (int) round(($searchStart + $searchEnd) / 2);
        $transitionPos = $this->getProgressivePlanetPosition($planetIndex, $progTransitionTimestamp);
        
        if (!$transitionPos['success']) {
            return null;
        }
        
        $realTimestamp = $this->progressionToReal($progTransitionTimestamp);
        
        return [
            'timestamp' => $realTimestamp,
            'date' => date('Y-m-d', $realTimestamp),
            'progressive_planet' => $planetName,
            'progressive_index' => $planetIndex,
            'direction' => 'S',
            'aspect' => 0,
            'radix_target' => $wasDirect ? 'Gaat Retrograde' : 'Gaat Direct',
            'radix_index' => $wasDirect ? 60 : 61,
            'radix_position' => $transitionPos['longitude'],
            'progressive_position' => $transitionPos['longitude'],
            'event_type' => 'rd_transition',
        ];
    }
}