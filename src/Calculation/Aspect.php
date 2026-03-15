<?php

namespace Tijd\Calculation;

class Aspect
{
    public int $planet1Index;
    public int $planet2Index;
    public string $planet1Name;
    public string $planet2Name;
    public float $planet1Longitude;
    public float $planet2Longitude;
    public int $aspectDegrees;
    public float $orb;
    public string $name;
    public bool $isDominant;

    public function __construct(
        int $planet1Index,
        int $planet2Index,
        string $planet1Name,
        string $planet2Name,
        float $planet1Longitude,
        float $planet2Longitude,
        int $aspectDegrees,
        float $orb,
        string $name,
        bool $isDominant = false
    ) {
        $this->planet1Index = $planet1Index;
        $this->planet2Index = $planet2Index;
        $this->planet1Name = $planet1Name;
        $this->planet2Name = $planet2Name;
        $this->planet1Longitude = $planet1Longitude;
        $this->planet2Longitude = $planet2Longitude;
        $this->aspectDegrees = $aspectDegrees;
        $this->orb = $orb;
        $this->name = $name;
        $this->isDominant = $isDominant;
    }

    public function toArray(): array
    {
        return [
            'planet1_index' => $this->planet1Index,
            'planet2_index' => $this->planet2Index,
            'planet1_name' => $this->planet1Name,
            'planet2_name' => $this->planet2Name,
            'planet1_longitude' => $this->planet1Longitude,
            'planet2_longitude' => $this->planet2Longitude,
            'aspect_degrees' => $this->aspectDegrees,
            'orb' => $this->orb,
            'name' => $this->name,
            'is_dominant' => $this->isDominant
        ];
    }
}
