<?php

namespace Tijd\Calculation;

use Tijd\Calculation\ParsFortuna;

class MirrorPointCalculator
{
    private const MIRROR_ANGLES = [
        135, 105, 75, 165, 45, 195, 15, 255, 285, 315, 345, 225, 0, 270, 330
    ];
    
    private const ASPECT_DEGREES = [0, 45, 90, 135, 180];
    private const MAX_ORB = 1.5;
    
    private const PLANET_INDEX_MAP = [
        0, 1, 2, 2, 3, 3, 4, 5, 6, 7, 8, 9, 11, 12, 14
    ];

    public function calculate(array $radixData): array
    {
        $planets = $radixData['planets'];
        $ascmc = $radixData['ascmc'];
        
        $planetPositions = $this->extractPlanetPositions($planets);
        $planetPositions[11] = $ascmc['ascendant']['longitude'];
        $planetPositions[12] = $ascmc['mc']['longitude'];
        
        $parsFortuna = ParsFortuna::calculate(
            $planetPositions[11],
            $planetPositions[1],
            $planetPositions[0]
        );
        $planetPositions[14] = $parsFortuna;
        
        $mirrorPoints = $this->calculateMirrorPoints($planetPositions);
        $aspects = $this->calculateAspects($mirrorPoints, $planetPositions);
        
        return [
            'mirrorPoints' => $mirrorPoints,
            'aspects' => $aspects,
        ];
    }
    
    private function extractPlanetPositions(array $planets): array
    {
        $positions = [];
        $planetNames = ['Sun', 'Moon', 'Mercury', 'Venus', 'Mars', 'Jupiter', 'Saturn', 'Uranus', 'Neptune', 'Pluto'];
        
        foreach ($planetNames as $index => $name) {
            if (isset($planets[$name]) && isset($planets[$name]['longitude'])) {
                $positions[$index] = $planets[$name]['longitude'];
            } else {
                $positions[$index] = 0;
            }
        }
        
        return $positions;
    }
    
    private function calculateMirrorPoints(array $planetPositions): array
    {
        $mirrorPoints = [];
        
        foreach (self::MIRROR_ANGLES as $index => $angle) {
            $planetIndex = self::PLANET_INDEX_MAP[$index];
            $planetPos = $planetPositions[$planetIndex] ?? 0;
            
            $mirrorPos = 2 * $angle - $planetPos;
            $mirrorPos = $this->normalizeLongitude($mirrorPos);
            
            $mirrorPoints[] = [
                'name' => $planetIndex,
                'pos' => $mirrorPos,
            ];
        }
        
        return $mirrorPoints;
    }
    
    private function calculateAspects(array $mirrorPoints, array $planetPositions): array
    {
        $aspects = [];
        
        foreach ($mirrorPoints as $mirrorIndex => $mirrorPoint) {
            $mirrorLon = $mirrorPoint['pos'];
            $mirrorPlanetIndex = $mirrorPoint['name'];
            
            foreach ($planetPositions as $natalIndex => $natalLon) {
                if ($natalIndex === 10) {
                    continue;
                }
                
                $distance = abs($mirrorLon - $natalLon);
                
                foreach (self::ASPECT_DEGREES as $aspectIndex => $aspectDegree) {
                    $orb = $distance - $aspectDegree;
                    
                    if (abs($orb) <= self::MAX_ORB) {
                        $aspects[] = [
                            'name1' => $mirrorPlanetIndex,
                            'name2' => $natalIndex,
                            'degree' => $aspectDegree,
                            'orb' => $orb,
                        ];
                    }
                    
                    if ($aspectIndex !== 4) {
                        $orb = $distance - (360 - $aspectDegree);
                        
                        if (abs($orb) <= self::MAX_ORB) {
                            $aspects[] = [
                                'name1' => $mirrorPlanetIndex,
                                'name2' => $natalIndex,
                                'degree' => $aspectDegree,
                                'orb' => $orb,
                            ];
                        }
                    }
                }
            }
        }
        
        return $aspects;
    }
    
    private function normalizeLongitude(float $lon): float
    {
        while ($lon < 0) {
            $lon += 360;
        }
        while ($lon >= 360) {
            $lon -= 360;
        }
        return $lon;
    }
}
