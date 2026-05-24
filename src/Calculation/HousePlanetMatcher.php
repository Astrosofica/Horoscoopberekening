<?php

namespace Tijd\Calculation;

class HousePlanetMatcher
{
    public function match(array $planets, array $houses): array
    {
        $houseCusps = $this->extractHouseCusps($houses);
        $result = [];

        foreach ($planets as $name => $data) {
            if (!isset($data['longitude'])) {
                continue;
            }

            $longitude = $data['longitude'];
            $speed = $data['speed_longitude'] ?? 0;
            $house = $this->findHouse($longitude, $houseCusps);

            $result[] = [
                'name' => $name,
                'longitude' => $longitude,
                'house' => $house,
                'speed' => $speed
            ];
        }

        return $result;
    }

    public function extractHouseCusps(array $houses): array
    {
        $cusps = [];
        for ($i = 1; $i <= 12; $i++) {
            $cusps[$i] = $houses[$i]['longitude'] ?? 0;
        }
        return $cusps;
    }

    public function findHouse(float $longitude, array $houseCusps): int
    {
        for ($house = 1; $house <= 12; $house++) {
            $nextHouse = $house < 12 ? $house + 1 : 1;
            $cuspStart = $houseCusps[$house];
            $cuspEnd = $houseCusps[$nextHouse];

            if ($this->isInHouse($longitude, $cuspStart, $cuspEnd)) {
                return $house;
            }
        }

        return 1;
    }

    private function isInHouse(float $longitude, float $cuspStart, float $cuspEnd): bool
    {
        $longitude = $this->normalizeAngle($longitude);
        $cuspStart = $this->normalizeAngle($cuspStart);
        $cuspEnd = $this->normalizeAngle($cuspEnd);

        if ($cuspStart <= $cuspEnd) {
            return $longitude >= $cuspStart && $longitude < $cuspEnd;
        }

        return $longitude >= $cuspStart || $longitude < $cuspEnd;
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
}