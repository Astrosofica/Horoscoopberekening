<?php

namespace Tijd\Calculation;

class AspectCalculator
{
    public const ASPECT_CONJUNCTIE = 0;
    public const ASPECT_OPPOSITIE = 180;
    public const ASPECT_VIERKANT = 90;
    public const ASPECT_DRIEHOEK = 120;
    public const ASPECT_SEXTIEL = 60;
    public const ASPECT_HALFVIERKANT = 45;
    public const ASPECT_ANDERHALFVIERKANT = 135;
    public const ASPECT_INCONJUNCT = 150;

    private const ASPECT_DEFINITIONS = [
        [
            'degrees' => 0,
            'orb' => 6,
            'name' => 'Conjunctie'
        ],
        [
            'degrees' => 180,
            'orb' => 6,
            'name' => 'Oppositie'
        ],
        [
            'degrees' => 90,
            'orb' => 6,
            'name' => 'Vierkant'
        ],
        [
            'degrees' => 45,
            'orb' => 2,
            'name' => 'Halfvierkant'
        ],
        [
            'degrees' => 135,
            'orb' => 2,
            'name' => 'Anderhalfvierkant'
        ],
        [
            'degrees' => 120,
            'orb' => 5,
            'name' => 'Driehoek'
        ],
        [
            'degrees' => 60,
            'orb' => 4,
            'name' => 'Sextiel'
        ],
        [
            'degrees' => 150,
            'orb' => 2,
            'name' => 'Inconjunct'
        ],
    ];

    private const HARD_ASPECTS = [0, 45, 90, 135, 180];
    
    private const PLANET_NAMES = [
        'Sun',
        'Moon',
        'Mercury',
        'Venus',
        'Mars',
        'Jupiter',
        'Saturn',
        'Uranus',
        'Neptune',
        'Pluto'
    ];

    /**
     * Bereken het kortste boog tussen twee planeten
     */
    public function calculateDistance(float $lon1, float $lon2): float
    {
        $distance = abs($lon1 - $lon2);
        
        if ($distance > 180) {
            $distance = 360 - $distance;
        }
        
        return $distance;
    }

    /**
     * Check of een aspect binnen de orb valt
     */
    public function checkAspect(
        float $distance,
        int $aspectDegrees,
        int $orbRange
    ): ?float {
        if ($distance > $aspectDegrees - $orbRange && $distance < $aspectDegrees + $orbRange) {
            return abs($distance - $aspectDegrees);
        }
        
        return null;
    }

    /**
     * Formatteer een orb naar DMS-notatie
     */
    public function formatOrb(float $orb): string
    {
        $degree = (int) floor($orb);
        $remainder = ($orb - $degree) * 60;
        $minute = (int) floor($remainder);
        $second = (int) floor(($remainder - $minute) * 60);
        
        return sprintf("%d°%02d'%02d\"", $degree, $minute, $second);
    }

    /**
     * Bereken alle aspecten tussen planeten
     * 
     * @param array $planets Array van planeten met 'longitude' en 'name' keys
     * @param array|null $houses Optionele huizen array met 'longitude' key
     * @return Aspect[]
     */
    public function calculate(array $planets, ?array $houses = null): array
    {
        $aspects = [];
        
        // Reindex planets array to numeric indices, skip NorthNode and Chiron
        $planetsWithIndices = [];
        $index = 0;
        foreach ($planets as $name => $data) {
            if ($name === 'NorthNode' || $name === 'Chiron') {
                continue;
            }
            $planetsWithIndices[$index] = [
                'longitude' => $data['longitude'] ?? 0,
                'name' => $name
            ];
            $index++;
        }
        
        $ascIndex = null;
        $mcIndex = null;
        
        // Add Ascendant and MC
        if ($houses !== null && isset($houses['houses'][1], $houses['houses'][10])) {
            $ascIndex = $index;
            $planetsWithIndices[$index] = [
                'longitude' => $houses['houses'][1]['longitude'],
                'name' => 'Ascendant'
            ];
            $index++;
            
            $mcIndex = $index;
            $planetsWithIndices[$index] = [
                'longitude' => $houses['houses'][10]['longitude'],
                'name' => 'Midhemel'
            ];
            $index++;
        }
        
        $planetIndices = array_keys($planetsWithIndices);
        
        foreach ($planetIndices as $i => $p1) {
            for ($j = $i + 1; $j < count($planetIndices); $j++) {
                $p2 = $planetIndices[$j];
                
                // Skip Asc-MC aspect
                if (($p1 === $ascIndex && $p2 === $mcIndex) ||
                    ($p1 === $mcIndex && $p2 === $ascIndex)) {
                    continue;
                }
                
                $distance = $this->calculateDistance(
                    $planetsWithIndices[$p1]['longitude'],
                    $planetsWithIndices[$p2]['longitude']
                );
                
                foreach (self::ASPECT_DEFINITIONS as $definition) {
                    $orb = $this->checkAspect(
                        $distance,
                        $definition['degrees'],
                        $definition['orb']
                    );
                    
                    if ($orb !== null) {
                        $isDominant = $this->isDominantAspect(
                            $planetsWithIndices[$p2]['name'],
                            $definition['degrees'],
                            $orb
                        );
                        
                        $aspect = new Aspect(
                            $p1,
                            $p2,
                            $planetsWithIndices[$p1]['name'],
                            $planetsWithIndices[$p2]['name'],
                            $planetsWithIndices[$p1]['longitude'],
                            $planetsWithIndices[$p2]['longitude'],
                            $definition['degrees'],
                            $orb,
                            $definition['name'],
                            $isDominant
                        );
                        
                        $aspects[] = $aspect;
                    }
                }
            }
        }
        
        return $aspects;
    }

    /**
     * Check of een aspect dominant is (harde aspecten naar Ascendant/MC met orb < 2°)
     */
    public function isDominantAspect(string $planet2Name, int $aspectDegrees, float $orb): bool
    {
        if ($planet2Name === 'Ascendant' || $planet2Name === 'Midhemel') {
            if (in_array($aspectDegrees, self::HARD_ASPECTS, true) && $orb < 2) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Haal de definitie van een aspect op
     */
    public function getAspectDefinition(int $degrees): ?array
    {
        foreach (self::ASPECT_DEFINITIONS as $definition) {
            if ($definition['degrees'] === $degrees) {
                return $definition;
            }
        }
        
        return null;
    }

    /**
     * Get alle aspect definities
     */
    public function getAllAspectDefinitions(): array
    {
        return self::ASPECT_DEFINITIONS;
    }

    /**
     * Formatteer aspecten voor weergave
     */
    public function formatAspects(array $aspects, bool $onlyDominant = false): array
    {
        $formatted = [];
        
        foreach ($aspects as $aspect) {
            if ($onlyDominant && !$aspect->isDominant) {
                continue;
            }
            
            $formatted[] = [
                'planet1' => $aspect->planet1Name,
                'planet2' => $aspect->planet2Name,
                'aspect' => $aspect->name,
                'orb' => $this->formatOrb($aspect->orb),
                'orb_decimal' => $aspect->orb,
                'is_dominant' => $aspect->isDominant,
                'planet1_longitude' => $aspect->planet1Longitude,
                'planet2_longitude' => $aspect->planet2Longitude,
                'aspect_glyph' => $this->getAspectGlyph($aspect->aspectDegrees)
            ];
        }
        
        return $formatted;
    }

    /**
     * Get glyph voor een aspect
     */
    public function getAspectGlyph(int $degrees): string
    {
        $mapping = [
            0 => ']',
            45 => 'b',
            60 => 'a',
            90 => '`',
            120 => '_',
            135 => 'c',
            150 => 'd',
            180 => '^'
        ];
        return $mapping[$degrees] ?? '?';
    }
}
