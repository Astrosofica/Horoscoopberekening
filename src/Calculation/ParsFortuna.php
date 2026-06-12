<?php

namespace Astro\Calculation;

class ParsFortuna
{
    /**
     * Bereken Pars Fortuna: Ascendant + Maan - Zon
     * 
     * Formule voor daggeboorte (Asc tussen 0° en 180°):
     * Pars Fortuna = Ascendant + Maan - Zon
     * 
     * Formule voor nachtgeboorte (Asc tussen 180° en 360°):
     * Pars Fortuna = Ascendant - Maan + Zon
     * 
     * @param float $ascendant Ascendant longitude in graden
     * @param float $moon Maan longitude in graden
     * @param float $sun Zon longitude in graden
     * @return float Pars Fortuna longitude (0-360°)
     */
    public static function calculate(float $ascendant, float $moon, float $sun): float
    {
        // Daggeboorte: Ascendant + Maan - Zon
        $parsFortuna = $ascendant + $moon - $sun;
        
        // Normaliseren naar 0-360°
        while ($parsFortuna < 0) {
            $parsFortuna += 360;
        }
        while ($parsFortuna >= 360) {
            $parsFortuna -= 360;
        }
        
        return $parsFortuna;
    }
    
    /**
     * Bereken Pars Fortuna inclusief snelheid (altijd 0 voor berekende punten)
     * 
     * @param float $ascendant Ascendant longitude in graden
     * @param float $moon Maan longitude in graden
     * @param float $sun Zon longitude in graden
     * @return array Array met success, longitude en speed_longitude
     */
    public static function calculateWithSpeed(float $ascendant, float $moon, float $sun): array
    {
        return [
            'success' => true,
            'longitude' => self::calculate($ascendant, $moon, $sun),
            'speed_longitude' => 0
        ];
    }
}
