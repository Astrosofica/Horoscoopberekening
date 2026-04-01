# Progression Events Module Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement progression events calculation with user-selectable planets, aspects, date range, and ingress options.

**Architecture:** New ProgressionEventCalculator class uses existing PlanetCalculator/HouseCalculator wrappers. Tab UI in index.php with POST handler. Session caching for results.

**Tech Stack:** PHP, Swiss Ephemeris via FFI, session storage, inline CSS.

---

## Files Structure

**New files:**
- `src/Calculation/ProgressionEventCalculator.php` - Main calculation logic
- `tests/progression-events-test.php` - Manual verification script

**Modified files:**
- `public/index.php` - New tab UI + POST handler + results display
- `public/includes/sidebar.php` - Add "Progression Events" tab link
- `public/css/style.css` - Form grid layout + results table styling

---

## Task 1: Create ProgressionEventCalculator Skeleton

**Files:**
- Create: `src/Calculation/ProgressionEventCalculator.php`

- [ ] **Step 1: Create class skeleton with method signature**

```php
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

        return $events;
    }
}
```

- [ ] **Step 2: Verify syntax**

Run: `php -l src/Calculation/ProgressionEventCalculator.php`
Expected: "No syntax errors detected"

- [ ] **Step 3: Commit skeleton**

```bash
git add src/Calculation/ProgressionEventCalculator.php
git commit -m "feat: add ProgressionEventCalculator skeleton"
```

---

## Task 2: Implement Progression Time Conversion

**Files:**
- Modify: `src/Calculation/ProgressionEventCalculator.php`

- [ ] **Step 1: Add time conversion methods**

Add after the `calculateEvents` method:

```php
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
```

- [ ] **Step 2: Verify syntax**

Run: `php -l src/Calculation/ProgressionEventCalculator.php`
Expected: "No syntax errors detected"

- [ ] **Step 3: Commit time conversion**

```bash
git add src/Calculation/ProgressionEventCalculator.php
git commit -m "feat: add progression time conversion methods"
```

---

## Task 3: Implement Aspect Point Calculation

**Files:**
- Modify: `src/Calculation/ProgressionEventCalculator.php`

- [ ] **Step 1: Add aspect target calculation**

Add after `getProgressivePlanetPosition`:

```php
    private function normalizeAngle(float $angle): float
    {
        while ($angle < 0) $angle += 360;
        while ($angle >= 360) $angle -= 360;
        return $angle;
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
```

- [ ] **Step 2: Verify syntax**

Run: `php -l src/Calculation/ProgressionEventCalculator.php`
Expected: "No syntax errors detected"

- [ ] **Step 3: Commit aspect targets**

```bash
git add src/Calculation/ProgressionEventCalculator.php
git commit -m "feat: add aspect target calculation methods"
```

---

## Task 4: Implement Time Refinement Algorithm

**Files:**
- Modify: `src/Calculation/ProgressionEventCalculator.php`

- [ ] **Step 1: Add refinement method**

Add after `getTargetName`:

```php
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

    private function normalizeAngleDiff(float $diff): float
    {
        while ($diff < -180) $diff += 360;
        while ($diff > 180) $diff -= 360;
        return $diff;
    }
```

- [ ] **Step 2: Verify syntax**

Run: `php -l src/Calculation/ProgressionEventCalculator.php`
Expected: "No syntax errors detected"

- [ ] **Step 3: Commit refinement**

```bash
git add src/Calculation/ProgressionEventCalculator.php
git commit -m "feat: add time refinement algorithm"
```

---

## Task 5: Implement Main Calculation Loop

**Files:**
- Modify: `src/Calculation/ProgressionEventCalculator.php`

- [ ] **Step 1: Implement calculateEvents body**

Replace the empty `calculateEvents` body with:

```php
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
            $endSpeed = $progEndPos['speed'];

            $isDirectAtStart = $startSpeed >= 0;
            $isDirectAtEnd = $endSpeed >= 0;

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

    private function filterTargetsInRange(array $targets, float $startLon, float $endLon, bool $isDirect): array
    {
        $filtered = [];
        
        foreach ($targets as $target) {
            $targetPos = $target['aspect_position'];
            
            if ($isDirect) {
                if ($endLon > $startLon) {
                    if ($targetPos >= $startLon && $targetPos <= $endLon) {
                        $filtered[] = $target;
                    }
                } else {
                    if ($targetPos >= $startLon || $targetPos <= $endLon) {
                        $filtered[] = $target;
                    }
                }
            } else {
                if ($endLon < $startLon) {
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
```

- [ ] **Step 2: Verify syntax**

Run: `php -l src/Calculation/ProgressionEventCalculator.php`
Expected: "No syntax errors detected"

- [ ] **Step 3: Commit main calculation**

```bash
git add src/Calculation/ProgressionEventCalculator.php
git commit -m "feat: implement main calculation loop with aspect finding"
```

---

## Task 6: Implement Ingress Detection

**Files:**
- Modify: `src/Calculation/ProgressionEventCalculator.php`

- [ ] **Step 1: Add sign and house ingress methods**

Add after `estimateEventTime`:

```php
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
            $signBoundaries[] = $i * 30;
        }

        $relevantBoundaries = $this->filterTargetsInRange(
            array_map(fn($b) => ['aspect_position' => $b], $signBoundaries),
            $startLon,
            $endLon,
            $isDirect
        );

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
```

- [ ] **Step 2: Verify syntax**

Run: `php -l src/Calculation/ProgressionEventCalculator.php`
Expected: "No syntax errors detected"

- [ ] **Step 3: Commit ingress detection**

```bash
git add src/Calculation/ProgressionEventCalculator.php
git commit -m "feat: add sign and house ingress detection"
```

---

## Task 7: Implement RD Transition Detection

**Files:**
- Modify: `src/Calculation/ProgressionEventCalculator.php`

- [ ] **Step 1: Add RD transition method**

Add after `calculateHouseIngress`:

```php
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
```

- [ ] **Step 2: Verify syntax**

Run: `php -l src/Calculation/ProgressionEventCalculator.php`
Expected: "No syntax errors detected"

- [ ] **Step 3: Commit RD transition**

```bash
git add src/Calculation/ProgressionEventCalculator.php
git commit -m "feat: add retrograde/direct transition detection"
```

---

## Task 8: Add Sidebar Tab Link

**Files:**
- Modify: `public/includes/sidebar.php`

- [ ] **Step 1: Read current sidebar**

Run: `cat public/includes/sidebar.php`

- [ ] **Step 2: Add "Progression Events" tab link**

Find the existing tab links and add after the existing tabs (after line with "Aspecten" if present):

```php
            <li><a href="?tab=progressions-list" data-tab="progressions-list">Progressie Events</a></li>
```

- [ ] **Step 3: Verify syntax**

Run: `php -l public/includes/sidebar.php`
Expected: "No syntax errors detected"

- [ ] **Step 4: Commit sidebar update**

```bash
git add public/includes/sidebar.php
git commit -m "feat: add progression events tab link"
```

---

## Task 9: Add CSS for Progression Form

**Files:**
- Modify: `public/css/style.css`

- [ ] **Step 1: Read current CSS to find insertion point**

Run: `wc -l public/css/style.css`

- [ ] **Step 2: Add progression form styles at end of file**

Add to end of `public/css/style.css`:

```css
.progression-form {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1rem;
}

.progression-column {
    flex: 1;
    min-width: 200px;
    border: 1px solid #ddd;
    border-radius: 0.5rem;
    padding: 0.5rem;
}

.progression-column h4 {
    margin: 0 0 0.5rem 0;
    font-size: 0.9rem;
}

.progression-column label {
    display: block;
    margin: 0.25rem 0;
    font-size: 0.85rem;
}

.progression-column label.astro-glyph {
    font-family: 'AstroGlyph', sans-serif;
}

.progression-datepicker {
    display: flex;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.progression-datepicker input[type="date"] {
    padding: 0.25rem;
}

.progression-options {
    margin-bottom: 0.5rem;
}

.progression-options label {
    display: inline-block;
    margin-right: 1rem;
}

.progression-results {
    margin-top: 1rem;
}

.progression-results table {
    width: 100%;
    border-collapse: collapse;
}

.progression-results th,
.progression-results td {
    padding: 0.5rem;
    border: 1px solid #ddd;
    text-align: center;
}

.progression-results th {
    background-color: #f5f5f5;
}

.progression-results .astro-glyph {
    font-family: 'AstroGlyph', sans-serif;
    font-size: 1.2rem;
}

.progression-results .row--rd {
    background-color: #fff3cd;
}

.progression-results .row--ingress {
    background-color: #d4edda;
}
```

- [ ] **Step 3: Commit CSS**

```bash
git add public/css/style.css
git commit -m "feat: add progression events form styling"
```

---

## Task 10: Add Form UI and POST Handler in index.php

**Files:**
- Modify: `public/index.php`

This is the main integration task. We need to:
1. Add the form tab section
2. Add POST handler
3. Add results display

- [ ] **Step 1: Add POST handler after existing POST block**

Find the line `// ===========================================================================` after the existing POST handler (around line 267) and add before it:

```php
// ===========================================================================
// POST HANDLER - Progression Events Form
// ===========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calculate_progressions'])) {
    if (!isset($_SESSION['horoscope']['core'])) {
        $error = "Eerst een horoscoop berekenen voordat progressies kunnen worden berekend.";
    } else {
        $startDate = $_POST['prog_start_date'] ?? '';
        $endDate = $_POST['prog_end_date'] ?? '';
        $progressivePlanets = $_POST['progressive_planet'] ?? [];
        $radixTargets = $_POST['radix_target'] ?? [];
        $aspects = $_POST['aspect_type'] ?? [];
        $includeHouseIngress = isset($_POST['include_house_ingress']);
        $includeSignIngress = isset($_POST['include_sign_ingress']);
        
        if (empty($startDate) || empty($endDate)) {
            $error = "Start- en einddatum zijn verplicht.";
        } elseif (strtotime($startDate) > strtotime($endDate)) {
            $error = "Einddatum moet na startdatum liggen.";
        } elseif (empty($progressivePlanets) && empty($radixTargets) && empty($aspects)) {
            $error = "Selecteer minimaal één planeet, radix target of aspect.";
        } else {
            $startTimestamp = strtotime($startDate);
            $endTimestamp = strtotime($endDate) + 86400;
            
            $radixData = [
                'planets' => $_SESSION['horoscope']['core']['planets'],
                'houses' => $_SESSION['horoscope']['core']['houses'],
                'ascmc' => $_SESSION['horoscope']['core']['ascmc'],
            ];
            
            $birthUtcTimestamp = strtotime($_SESSION['horoscope']['input']['birth_date'] . ' ' . $_SESSION['horoscope']['input']['birth_time']) 
                - $_SESSION['horoscope']['input']['utc_offset'];
            
            $progCalculator = new ProgressionEventCalculator();
            
            try {
                $progEvents = $progCalculator->calculateEvents(
                    $radixData,
                    $progressivePlanets,
                    $radixTargets,
                    $aspects,
                    $startTimestamp,
                    $endTimestamp,
                    $includeHouseIngress,
                    $includeSignIngress,
                    $_SESSION['horoscope']['input']['latitude'],
                    $_SESSION['horoscope']['input']['longitude'],
                    $birthUtcTimestamp,
                    $_SESSION['horoscope']['input']['utc_offset']
                );
                
                $_SESSION['horoscope']['progression_events'] = [
                    'input' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'progressive_planets' => $progressivePlanets,
                        'radix_targets' => $radixTargets,
                        'aspects' => $aspects,
                        'include_house_ingress' => $includeHouseIngress,
                        'include_sign_ingress' => $includeSignIngress,
                    ],
                    'results' => $progEvents,
                ];
                
                $currentTab = 'progressions-list';
            } catch (\Exception $e) {
                $error = "Berekening mislukt: " . $e->getMessage();
                error_log("[Tijd] Progression error: " . $e->getMessage());
            }
        }
    }
}
```

- [ ] **Step 2: Add tab switch logic for progressions-list**

Find the existing tab switch block (around line 333-406) and add a new case after the 'progressions' case:

```php
            case 'progressions-list':
                // Show cached progression events if available
                if (isset($_SESSION['horoscope']['progression_events']['results'])) {
                    $progEventsResult = $_SESSION['horoscope']['progression_events']['results'];
                }
                $currentTab = 'progressions-list';
                break;
```

- [ ] **Step 3: Add form tab section**

Find the location after the existing `tab-progressions` section (around line 696) and add:

```php
                <?php if (isset($_SESSION['horoscope']['core'])): ?>
                <section id="tab-progressions-list" class="tab-content tab-content--hidden">
                    <div class="card card--large">
                        <h2>Progressie Events</h2>
                        <form method="POST" class="progression-form">
                            <div class="progression-column" style="min-width: 300px;">
                                <h4>Tijdvak</h4>
                                <div class="progression-datepicker">
                                    <label>Start: <input type="date" name="prog_start_date" value="<?= htmlspecialchars($_SESSION['horoscope']['progression_events']['input']['start_date'] ?? date('Y-01-01')) ?>"></label>
                                    <label>Eind: <input type="date" name="prog_end_date" value="<?= htmlspecialchars($_SESSION['horoscope']['progression_events']['input']['end_date'] ?? date('Y-12-31')) ?>"></label>
                                </div>
                                <div class="progression-options">
                                    <label><input type="checkbox" name="include_house_ingress" <?= isset($_SESSION['horoscope']['progression_events']['input']['include_house_ingress']) ? 'checked' : '' ?>> Huis ingress</label>
                                    <label><input type="checkbox" name="include_sign_ingress" <?= isset($_SESSION['horoscope']['progression_events']['input']['include_sign_ingress']) ? 'checked' : '' ?>> Teken ingress</label>
                                </div>
                            </div>
                            
                            <div class="progression-column">
                                <h4>Progressief</h4>
                                <?php for ($i = 0; $i <= 9; $i++): ?>
                                    <label class="astro-glyph">
                                        <input type="checkbox" name="progressive_planet[]" value="<?= $i ?>" <?= in_array($i, $_SESSION['horoscope']['progression_events']['input']['progressive_planets'] ?? []) ? 'checked' : '' ?>>
                                        <?= SymbolGlyph::getPlanetGlyphByIndex($i) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="progression-column">
                                <h4>Aspecten</h4>
                                <?php 
                                $aspectOptions = [0, 45, 60, 90, 120, 135, 150, 180];
                                foreach ($aspectOptions as $aspDeg): ?>
                                    <label class="astro-glyph">
                                        <input type="checkbox" name="aspect_type[]" value="<?= $aspDeg ?>" <?= in_array($aspDeg, $_SESSION['horoscope']['progression_events']['input']['aspects'] ?? []) ? 'checked' : '' ?>>
                                        <?= SymbolGlyph::getAspectGlyph($aspDeg) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="progression-column">
                                <h4>Radix</h4>
                                <?php for ($i = 0; $i <= 12; $i++): ?>
                                    <label class="astro-glyph">
                                        <input type="checkbox" name="radix_target[]" value="<?= $i ?>" <?= in_array($i, $_SESSION['horoscope']['progression_events']['input']['radix_targets'] ?? []) ? 'checked' : '' ?>>
                                        <?= SymbolGlyph::getPlanetGlyphByIndex($i) ?>
                                    </label>
                                <?php endforeach; ?>
                                <hr style="margin: 0.5rem 0; border-color: #ddd;">
                                <?php for ($i = 20; $i <= 31; $i++): ?>
                                    <label class="astro-glyph">
                                        <input type="checkbox" name="radix_target[]" value="<?= $i ?>" <?= in_array($i, $_SESSION['horoscope']['progression_events']['input']['radix_targets'] ?? []) ? 'checked' : '' ?>>
                                        <?= SymbolGlyph::getSignGlyph($i - 20) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            
                            <button type="submit" name="calculate_progressions" class="btn btn--primary" style="width: 100%; margin-top: 0.5rem;">Bereken Progressie Events</button>
                        </form>
                        
                        <?php if ($error): ?>
                            <p class="form-error"><?= htmlspecialchars($error) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (isset($progEventsResult) && count($progEventsResult) > 0): ?>
                    <div class="card card--large progression-results">
                        <h4>Resultaten (<?= count($progEventsResult) ?> events)</h4>
                        <table>
                            <thead>
                                <tr>
                                    <th>Datum</th>
                                    <th>Dir</th>
                                    <th>Progressief</th>
                                    <th>Aspect</th>
                                    <th>Radix</th>
                                    <th>Prog Pos</th>
                                    <th>Radix Pos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($progEventsResult as $event): ?>
                                    <tr class="<?= $event['event_type'] === 'rd_transition' ? 'row--rd' : '' ?><?= $event['event_type'] === 'house_ingress' || $event['event_type'] === 'sign_ingress' ? 'row--ingress' : '' ?>">
                                        <td><?= date('d-m-Y', $event['timestamp']) ?></td>
                                        <td><?= $event['direction'] ?></td>
                                        <td class="astro-glyph"><?= SymbolGlyph::getPlanetGlyphByIndex($event['progressive_index']) ?></td>
                                        <td class="astro-glyph"><?= SymbolGlyph::getAspectGlyph($event['aspect']) ?></td>
                                        <td><?= $event['event_type'] === 'rd_transition' ? htmlspecialchars($event['radix_target']) : '<span class="astro-glyph">' . SymbolGlyph::getGlyphForTarget($event['radix_index']) . '</span>' ?></td>
                                        <td><?= Formatter::formatLongitudeWithGlyph($event['progressive_position']) ?></td>
                                        <td><?= Formatter::formatLongitudeWithGlyph($event['radix_position']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php elseif (isset($progEventsResult) && count($progEventsResult) === 0): ?>
                    <div class="card card--large">
                        <p>Geen events gevonden in de opgegeven periode.</p>
                    </div>
                    <?php endif; ?>
                </section>
                <?php endif; ?>
```

- [ ] **Step 4: Add missing helper method to SymbolGlyph if needed**

Check if `SymbolGlyph::getPlanetGlyphByIndex` and `SymbolGlyph::getGlyphForTarget` exist. If not, add them.

Run: `grep -n "getPlanetGlyphByIndex" src/Glyph/SymbolGlyph.php`

If not found, add to `src/Glyph/SymbolGlyph.php`:

```php
    public static function getPlanetGlyphByIndex(int $index): string
    {
        $mapping = [
            0 => self::SUN,
            1 => self::MOON,
            2 => self::MERCURY,
            3 => self::VENUS,
            4 => self::MARS,
            5 => self::JUPITER,
            6 => self::SATURN,
            7 => self::URANUS,
            8 => self::NEPTUNE,
            9 => self::PLUTO,
            10 => self::NORTHNODE,
            11 => self::ASCENDANT,
            12 => self::MC,
        ];
        return $mapping[$index] ?? '';
    }

    public static function getSignGlyph(int $index): string
    {
        $mapping = [
            0 => self::ARIES,
            1 => self::TAURUS,
            2 => self::GEMINI,
            3 => self::CANCER,
            4 => self::LEO,
            5 => self::VIRGO,
            6 => self::LIBRA,
            7 => self::SCORPIO,
            8 => self::SAGITTARIUS,
            9 => self::CAPRICORN,
            10 => self::AQUARIUS,
            11 => self::PISCES,
        ];
        return $mapping[$index] ?? '';
    }

    public static function getGlyphForTarget(int $targetIndex): string
    {
        if ($targetIndex >= 0 && $targetIndex <= 12) {
            return self::getPlanetGlyphByIndex($targetIndex);
        }
        if ($targetIndex >= 20 && $targetIndex <= 31) {
            return self::getSignGlyph($targetIndex - 20);
        }
        if ($targetIndex >= 40 && $targetIndex <= 51) {
            return 'H' . ($targetIndex - 39);
        }
        return '';
    }
```

- [ ] **Step 5: Verify syntax**

Run: `php -l public/index.php && php -l src/Glyph/SymbolGlyph.php`
Expected: "No syntax errors detected" for both

- [ ] **Step 6: Commit integration**

```bash
git add public/index.php src/Glyph/SymbolGlyph.php
git commit -m "feat: integrate progression events UI with POST handler"
```

---

## Task 11: Create Manual Test Script

**Files:**
- Create: `tests/progression-events-test.php`

- [ ] **Step 1: Create test script**

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Calculation/ProgressionEventCalculator.php';
require_once __DIR__ . '/../src/Glyph/SymbolGlyph.php';
require_once __DIR__ . '/../src/Helpers/Formatter.php';

use Tijd\Calculation\ProgressionEventCalculator;
use Tijd\Glyph\SymbolGlyph;
use Tijd\Helpers\Formatter;

echo "=== Progression Events Test ===\n\n";

$radixData = [
    'planets' => [
        'Sun' => ['longitude' => 116.5, 'speed' => 0.9856, 'success' => true],
        'Moon' => ['longitude' => 234.2, 'speed' => 13.2, 'success' => true],
        'Mars' => ['longitude' => 45.3, 'speed' => 0.524, 'success' => true],
    ],
    'houses' => [
        1 => ['longitude' => 180],
        10 => ['longitude' => 90],
    ],
    'ascmc' => [
        'ascendant' => ['longitude' => 180],
        'mc' => ['longitude' => 90],
    ],
];

$calculator = new ProgressionEventCalculator();

$birthTimestamp = strtotime('1963-07-19 16:51:21 UTC');
$startTimestamp = strtotime('2025-01-01');
$endTimestamp = strtotime('2025-12-31');

echo "Calculating events for 2025...\n";
echo "Birth: 1963-07-19 UTC\n";
echo "Range: 2025-01-01 to 2025-12-31\n\n";

$events = $calculator->calculateEvents(
    $radixData,
    [0, 1, 4],
    [0, 1, 4],
    [0, 90, 180],
    $startTimestamp,
    $endTimestamp,
    true,
    true,
    51.7333,
    4.3833,
    $birthTimestamp,
    0
);

echo "Found " . count($events) . " events:\n\n";

foreach ($events as $event) {
    $eventType = $event['event_type'];
    $date = date('d-m-Y', $event['timestamp']);
    $progGlyph = SymbolGlyph::getPlanetGlyphByIndex($event['progressive_index']);
    $aspGlyph = SymbolGlyph::getAspectGlyph($event['aspect']);
    
    echo "$date | {$event['direction']} | $progGlyph | $aspGlyph | {$event['radix_target']} | $eventType\n";
}

echo "\n=== Test Complete ===\n";
```

- [ ] **Step 2: Run test**

Run: `php tests/progression-events-test.php`
Expected: Output with events list or error messages to debug

- [ ] **Step 3: Debug and fix if needed**

If errors occur, check:
- Class exists and methods are callable
- PlanetCalculator returns expected structure
- SwissEphemeris FFI is working

- [ ] **Step 4: Commit test script**

```bash
git add tests/progression-events-test.php
git commit -m "feat: add manual test script for progression events"
```

---

## Task 12: Final Integration Test

**Files:**
- None (browser testing)

- [ ] **Step 1: Start/restart PHP server if needed**

- [ ] **Step 2: Calculate a horoscope first**

1. Navigate to index.php
2. Enter birth data (e.g., the test case: 1963-07-19, Ooltgensplaat)
3. Submit and verify horoscope appears

- [ ] **Step 3: Navigate to Progression Events tab**

1. Click "Progressie Events" tab in sidebar
2. Verify form appears with checkboxes

- [ ] **Step 4: Submit progression calculation**

1. Select start/end dates (e.g., 2025-01-01 to 2025-12-31)
2. Check some progressive planets (Sun, Moon)
3. Check some aspects (90, 180)
4. Check ingress options
5. Submit

- [ ] **Step 5: Verify results**

1. Results table should appear
2. Events should be chronologically sorted
3. Glyphs should render correctly
4. Dates should be in dd-mm-yyyy format

- [ ] **Step 6: Fix any issues found**

If issues:
- Check error log: `tail var/log/error.log`
- Debug in test script
- Fix code and re-test

- [ ] **Step 7: Final commit**

```bash
git add -A
git commit -m "feat: complete progression events module implementation"
```

---

## Self-Review Checklist

- [x] Spec coverage: All requirements have corresponding tasks
- [x] Placeholder scan: No TBD/TODO/placeholders in plan
- [x] Type consistency: Method signatures match across tasks
- [x] Threshold value: 0.5 seconds used consistently
- [x] Event types: aspect, house_ingress, sign_ingress, rd_transition all covered