<?php

namespace Astro\Calculation;

class MidpointTreeCalculator
{
    private const DOMINANT_ASPECTS = [0, 45, 90, 135, 180];
    private const ASPECT_ORB = 1.25;
    private const TOTAL_PLANETS = 13; // 0-12

    /**
     * Bereken aspecten tussen radix planeten en midpunten
     * 
     * @param array $radixData Radix data met planets en ascmc
     * @param array $midpoints Midpunten array van MidpointCalculator
     * @return array Gegroepeerde aspecten per radix planeet
     */
    public function calculateTree(array $radixData, array $midpoints): array
    {
        $planetPositions = $this->extractPlanetPositions($radixData);
        $midpointData = $this->extractMidpointData($midpoints);
        
        $results = [];
        
        // Voor elke radix planeet
        for ($i2 = 0; $i2 < self::TOTAL_PLANETS; $i2++) {
            $foundAspects = [];
            
            // Check alle midpunten
            foreach ($midpointData as $aa => $midpoint) {
                $afstand = $planetPositions[$i2] - $midpoint['longitude'];
                
                // Check alle dominante aspecten
                foreach (self::DOMINANT_ASPECTS as $iii => $aspectDegree) {
                    // Normaal aspect
                    $orb = ($afstand < 0) ? $afstand + $aspectDegree : $afstand - $aspectDegree;
                    
                    if ($orb <= self::ASPECT_ORB && $orb >= -self::ASPECT_ORB 
                        && $i2 !== $midpoint['planet1_index'] 
                        && $i2 !== $midpoint['planet2_index']) {
                        $foundAspects[] = [
                            'midpoint_index' => $aa,
                            'planet1_index' => $midpoint['planet1_index'],
                            'planet2_index' => $midpoint['planet2_index'],
                            'longitude' => $midpoint['longitude'],
                            'aspect_degrees' => $aspectDegree,
                            'orb' => $orb,
                        ];
                    }
                    
                    // Anti-dominant (alleen voor niet-oppositie)
                    if ($iii < 4) { // Geen double oppositions
                        $orb = ($afstand < 0) ? $afstand + (360 - $aspectDegree) : $afstand - (360 - $aspectDegree);
                        
                        if ($orb <= self::ASPECT_ORB && $orb >= -self::ASPECT_ORB 
                            && $i2 !== $midpoint['planet1_index'] 
                            && $i2 !== $midpoint['planet2_index']) {
                            $foundAspects[] = [
                                'midpoint_index' => $aa,
                                'planet1_index' => $midpoint['planet1_index'],
                                'planet2_index' => $midpoint['planet2_index'],
                                'longitude' => $midpoint['longitude'],
                                'aspect_degrees' => $aspectDegree,
                                'orb' => $orb,
                            ];
                        }
                    }
                }
            }
            
            // Sorteer aspecten van positief naar negatief (signed orb)
            if (!empty($foundAspects)) {
                // Sorteer op signed orb (positief naar negatief)
                usort($foundAspects, function($a, $b) {
                    return $b['orb'] <=> $a['orb'];
                });
                
                // Vind aspect met kleinste absolute orb (meest exacte)
                $minAbsOrb = PHP_FLOAT_MAX;
                $exactIndex = 0;
                foreach ($foundAspects as $key => $aspect) {
                    $absOrb = abs($aspect['orb']);
                    if ($absOrb < $minAbsOrb) {
                        $minAbsOrb = $absOrb;
                        $exactIndex = $key;
                    }
                }
                
                // Markeer meest exacte aspect met **
                foreach ($foundAspects as $key => $aspect) {
                    $foundAspects[$key]['exact'] = ($key === $exactIndex);
                }
                
                $results[$i2] = [
                    'planet_index' => $i2,
                    'longitude' => $planetPositions[$i2],
                    'aspects' => $foundAspects,
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Extraheer planeet posities uit radix data
     */
    private function extractPlanetPositions(array $radixData): array
    {
        $positions = [];
        
        // Planeten 0-9
        $planetNames = ['Sun', 'Moon', 'Mercury', 'Venus', 'Mars', 'Jupiter', 'Saturn', 'Uranus', 'Neptune', 'Pluto'];
        
        foreach ($planetNames as $index => $name) {
            if (isset($radixData['planets'][$name]) && isset($radixData['planets'][$name]['longitude'])) {
                $positions[$index] = (float) $radixData['planets'][$name]['longitude'];
            } else {
                $positions[$index] = 0.0;
            }
        }
        
        // Index 10: NorthNode
        $positions[10] = isset($radixData['planets']['NorthNode']) && isset($radixData['planets']['NorthNode']['longitude'])
            ? (float) $radixData['planets']['NorthNode']['longitude']
            : 0.0;
        
        // Index 11: Ascendant
        $positions[11] = (float) $radixData['ascmc']['ascendant']['longitude'];
        
        // Index 12: MC
        $positions[12] = (float) $radixData['ascmc']['mc']['longitude'];
        
        return $positions;
    }
    
    /**
     * Extraheer midpunt data voor eenvoudige verwerking
     */
    private function extractMidpointData(array $midpoints): array
    {
        $data = [];
        
        foreach ($midpoints as $mp) {
            if (!isset($mp['separator'])) {
                $data[] = [
                    'planet1_index' => $mp['planet1_index'],
                    'planet2_index' => $mp['planet2_index'],
                    'longitude' => $mp['longitude'],
                ];
            }
        }
        
        return $data;
    }
}
