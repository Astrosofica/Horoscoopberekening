<?php

namespace Astro\Calculation;

use Astro\Database\EphemerisRepository;

class TransitEventCalculator
{
    private EphemerisRepository $ephemerisRepo;

    public function __construct(?EphemerisRepository $ephemerisRepo = null)
    {
        $this->ephemerisRepo = $ephemerisRepo ?? new EphemerisRepository();
    }

    /**
     * Vind transit events binnen een datumreeks.
     *
     * @param array $radixData Radix planeet posities + ascmc
     * @param array $radixHouses Radix huizen cusps
     * @param array $transitPlanets Planet indices als transit (5-9: Jupiter-Pluto)
     * @param array $radixTargets Radix target indices (0-9 planeten, 10=NorthNode, 11=Asc, 12=MC)
     * @param array $aspects Aspect graden (0, 45, 60, 90, 120, 135, 150, 180)
     * @param string $startDate Start datum (YYYY-MM-DD)
     * @param string $endDate Eind datum (YYYY-MM-DD)
     * @param bool $includeHouseIngress Inclusief huis ingress events
     * @return array Array van transit events
     */
    public function findTransitEvents(
        array $radixData,
        array $radixHouses,
        array $transitPlanets,
        array $radixTargets,
        array $aspects,
        string $startDate,
        string $endDate,
        bool $includeHouseIngress = false
    ): array {
        $radixPositions = $this->buildRadixPositionMap($radixData, $radixHouses);

        $sensitivePoints = $this->buildSensitivePoints($radixPositions, $radixTargets, $aspects);

        if ($includeHouseIngress) {
            $sensitivePoints = array_merge(
                $sensitivePoints,
                $this->buildHouseIngressPoints($radixHouses)
            );
        }

        $events = [];
        foreach ($transitPlanets as $tPlanet) {
            foreach ($sensitivePoints as $point) {
                $crossings = $this->ephemerisRepo->findPositionCrossings(
                    $tPlanet,
                    $point['long'],
                    $startDate,
                    $endDate
                );

                foreach ($crossings as $crossing) {
                    $events[] = [
                        'date' => $crossing['date'],
                        'timestamp' => strtotime($crossing['date']),
                        'speed' => (float) $crossing['speed'],
                        'direction' => ((float) $crossing['speed'] < 0) ? 'R' : 'D',
                        'tplanet' => $tPlanet,
                        'tlong' => $point['long'],
                        'rplanet' => $point['planet'],
                        'rlong' => $radixPositions[$point['planet']] ?? 0,
                        'aspect' => $point['aspect'],
                        'event_type' => $point['event_type'] ?? 'aspect',
                    ];
                }
            }
        }

        usort($events, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);

        return $events;
    }

    /**
     * Bouw radix positie map: index => longitude
     *
     *  0-9: Sun-Pluto
     * 10: NorthNode
     * 11: Ascendant
     * 12: MC
     */
    private function buildRadixPositionMap(array $radixData, array $radixHouses): array
    {
        $positions = [];

        $planetMap = [
            'Sun' => 0, 'Moon' => 1, 'Mercury' => 2, 'Venus' => 3, 'Mars' => 4,
            'Jupiter' => 5, 'Saturn' => 6, 'Uranus' => 7, 'Neptune' => 8, 'Pluto' => 9,
            'NorthNode' => 10,
        ];

        foreach ($planetMap as $name => $index) {
            if (isset($radixData['planets'][$name]['longitude'])) {
                $positions[$index] = (float) $radixData['planets'][$name]['longitude'];
            }
        }

        if (isset($radixData['ascmc']['ascendant']['longitude'])) {
            $positions[11] = (float) $radixData['ascmc']['ascendant']['longitude'];
        } elseif (isset($radixHouses[1]['longitude'])) {
            $positions[11] = (float) $radixHouses[1]['longitude'];
        }

        if (isset($radixData['ascmc']['mc']['longitude'])) {
            $positions[12] = (float) $radixData['ascmc']['mc']['longitude'];
        } elseif (isset($radixHouses[10]['longitude'])) {
            $positions[12] = (float) $radixHouses[10]['longitude'];
        }

        for ($i = 1; $i <= 12; $i++) {
            if (isset($radixHouses[$i]['longitude'])) {
                $positions[39 + $i] = (float) $radixHouses[$i]['longitude'];
            }
        }

        return $positions;
    }

    /**
     * Bouw gevoelige punten van radix targets en aspecten.
     */
    private function buildSensitivePoints(array $radixPositions, array $radixTargets, array $aspects): array
    {
        $points = [];

        foreach ($radixTargets as $rIndex) {
            if (!isset($radixPositions[$rIndex])) {
                continue;
            }
            $rLong = $radixPositions[$rIndex];

            foreach ($aspects as $aspectDeg) {
                $points[] = [
                    'long' => $this->in360($rLong + $aspectDeg),
                    'planet' => $rIndex,
                    'aspect' => $aspectDeg,
                    'event_type' => 'aspect',
                ];

                if ($aspectDeg !== 0 && $aspectDeg !== 180) {
                    $points[] = [
                        'long' => $this->in360($rLong - $aspectDeg),
                        'planet' => $rIndex,
                        'aspect' => $aspectDeg,
                        'event_type' => 'aspect',
                    ];
                }
            }
        }

        return $points;
    }

    /**
     * Bouw huis ingress gevoelige punten.
     */
    private function buildHouseIngressPoints(array $radixHouses): array
    {
        $points = [];

        for ($house = 1; $house <= 12; $house++) {
            if (isset($radixHouses[$house]['longitude'])) {
                $points[] = [
                    'long' => (float) $radixHouses[$house]['longitude'],
                    'planet' => 39 + $house,
                    'aspect' => 0,
                    'event_type' => 'house_ingress',
                ];
            }
        }

        return $points;
    }

    /**
     * Normaliseer hoek naar 0-360.
     */
    private function in360(float $angle): float
    {
        $angle = fmod($angle, 360);
        if ($angle < 0) {
            $angle += 360;
        }
        return $angle;
    }
}
