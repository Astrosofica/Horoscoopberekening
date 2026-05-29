<?php

namespace Astro\Calculation;

use Astro\Entity\Horoscope;
use Astro\Ephemeris\SwissEphemeris;
use Astro\Helpers\Formatter;

class HoroscopeCalculator
{
    private PlanetCalculator $planetCalculator;
    private HouseCalculator $houseCalculator;
    private AspectCalculator $aspectCalculator;
    private HousePlanetMatcher $housePlanetMatcher;

    public function __construct(
        ?PlanetCalculator $planetCalculator = null,
        ?HouseCalculator $houseCalculator = null,
        ?AspectCalculator $aspectCalculator = null
    ) {
        $this->planetCalculator = $planetCalculator ?? new PlanetCalculator();
        $this->houseCalculator = $houseCalculator ?? new HouseCalculator();
        $this->aspectCalculator = $aspectCalculator ?? new AspectCalculator();
        $this->housePlanetMatcher = new HousePlanetMatcher();
    }

    public function calculate(Horoscope $horoscope): array
    {
        $utcTimestamp = $horoscope->getUtcTimestamp();
        $latitude = $horoscope->getLatitude();
        $longitude = $horoscope->getLongitude();
        $houseSystem = $horoscope->getHouseSystem();

        $planetResult = $this->planetCalculator->calculateForTimestamp($utcTimestamp);

        $houseResult = $this->houseCalculator->calculateByTimestamp(
            $utcTimestamp,
            $latitude,
            $longitude,
            $houseSystem
        );

        $ascendant = $houseResult['ascmc']['ascendant']['longitude'];
        $moon = $planetResult['planets']['Moon']['longitude'] ?? 0;
        $sun = $planetResult['planets']['Sun']['longitude'] ?? 0;

        $planetResult['planets']['ParsFortuna'] = ParsFortuna::calculateWithSpeed(
            $ascendant,
            $moon,
            $sun
        );

        $planetsForAspects = [];
        foreach ($planetResult['planets'] as $name => $data) {
            if ($name === 'ParsFortuna') continue;
            if (isset($data['success']) && $data['success']) {
                $planetsForAspects[$name] = [
                    'longitude' => $data['longitude']
                ];
            }
        }
        $aspectResult = $this->aspectCalculator->calculate($planetsForAspects, $houseResult);

        $result = [
            'name' => $horoscope->getFullName(),
            'firstname' => $horoscope->getFirstname(),
            'infix' => $horoscope->getInfix(),
            'lastname' => $horoscope->getLastname(),
            'offset' => $horoscope->getUtcOffset(),
            'source' => $horoscope->getOffsetSource(),
            'label' => $horoscope->getOffsetLabel(),
            'coords' => [
                'lat' => $horoscope->getLatitude(),
                'lng' => $horoscope->getLongitude()
            ],
            'address' => $horoscope->getFormattedAddress() ?? $horoscope->getLocationName(),
            'location_name' => $horoscope->getLocationName(),
            'timezone' => $horoscope->getTimezoneId(),
            'planets' => $planetResult['planets'],
            'julian_day' => $planetResult['julian_day'],
            'houses' => $houseResult,
            'aspects' => $aspectResult,
            'local_timestamp' => $horoscope->getLocalTimestamp(),
            'utc_timestamp' => $utcTimestamp,
            'horoscope_id' => $horoscope->getId()
        ];

        return $result;
    }

    public function prepareWheelData(array $result): array
    {
        $planetsForWheel = $this->housePlanetMatcher->match(
            $result['planets'],
            $result['houses']['houses']
        );
        $houseCuspsForWheel = $this->housePlanetMatcher->extractHouseCusps(
            $result['houses']['houses']
        );

        return [
            'name' => $result['name'],
            'house_cusps' => $houseCuspsForWheel,
            'planets' => $planetsForWheel
        ];
    }
}