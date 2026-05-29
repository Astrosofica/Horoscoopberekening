<?php

namespace Astro\Calculation;

use Astro\Calculation\ParsFortuna;

class MidpointCalculator
{
    private const PLANET_COUNT = 10; // Sun t/m Pluto (excl. NorthNode, Chiron)
    private const TOTAL_POINTS = 13; // 10 planeten + index 10 + Asc + MC = indices 0-12

    /**
     * Bereken alle midpunten tussen planeten
     * 
     * @param array $radixData Array met 'planets' en 'ascmc' keys
     * @return array Array met midpoints array
     */
    public function calculateAllMidpoints(array $radixData): array
    {
        $planetPositions = $this->extractPlanetPositions($radixData);
        $midpoints = $this->calculateMidpoints($planetPositions);
        
        return [
            'midpoints' => $midpoints,
        ];
    }
    
    /**
     * Extraheer planeet posities uit radix data
     * 
     * @param array $radixData
     * @return array Array van longitudes (index 0-12)
     */
    private function extractPlanetPositions(array $radixData): array
    {
        $positions = [];
        
        // Planeten 0-9: Sun t/m Pluto (skip NorthNode en Chiron)
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
     * Bereken alle midpunten tussen planeetparen
     * 
     * @param array $planetPositions Array van longitudes
     * @return array Array van midpoint data met lege rijen na elke planeetgroep
     */
    private function calculateMidpoints(array $planetPositions): array
    {
        $midpoints = [];
        
        // TOTAL_POINTS = 13, dus indices 0-12
        for ($p1 = 0; $p1 < self::TOTAL_POINTS; $p1++) {
            for ($p2 = $p1 + 1; $p2 < self::TOTAL_POINTS; $p2++) {
                // Voeg lege rij toe na laatste combinatie van elke planeet (behalve laatste)
                if ($p1 > 0 && $p2 === $p1 + 1) {
                    $midpoints[] = [
                        'separator' => true,
                    ];
                }
                
                $pos1 = $planetPositions[$p1];
                $pos2 = $planetPositions[$p2];
                
                $midpoint = $this->calculateMidpoint($pos1, $pos2);
                
                $midpoints[] = [
                    'planet1_index' => $p1,
                    'planet2_index' => $p2,
                    'longitude' => $midpoint,
                    'normalized' => $this->normalizeLongitude($midpoint),
                ];
            }
        }
        
        return $midpoints;
    }
    
    /**
     * Bereken midpunt tussen twee posities met 360-0 correctie
     * 
     * @param float $pos1 Eerste positie in graden
     * @param float $pos2 Tweede positie in graden
     * @return float Midpunt positie in graden
     */
    private function calculateMidpoint(float $pos1, float $pos2): float
    {
        // Normaliseer naar 0-360
        $pos1 = fmod($pos1, 360);
        $pos2 = fmod($pos2, 360);
        if ($pos1 < 0) {
            $pos1 += 360;
        }
        if ($pos2 < 0) {
            $pos2 += 360;
        }
        
        $diff = abs($pos1 - $pos2);
        
        // Midpunt aan overkant van cirkel (bijv. Ram/Vissen overgang)
        if ($diff > 180) {
            return fmod(($pos1 + $pos2) / 2 + 180, 360);
        }
        
        // Normaal midpunt
        return ($pos1 + $pos2) / 2;
    }
    
    /**
     * Normaliseer longitude naar 0-360 bereik
     * 
     * @param float $lon
     * @return float
     */
    private function normalizeLongitude(float $lon): float
    {
        $lon = fmod($lon, 360);
        if ($lon < 0) {
            $lon += 360;
        }
        return $lon;
    }
}
