<?php

namespace Astro\Geo;

class GeocodingService
{
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function geocode(string $location): array
    {
        if (!$this->validateLocation($location)) {
            return ['error' => 'Ongeldige locatie'];
        }

        $geoUrl = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($location) . "&language=nl&key=" . $this->apiKey;
        $geoRes = json_decode(file_get_contents($geoUrl), true);

        if ($geoRes['status'] !== 'OK') {
            return ['error' => 'Locatie niet gevonden: ' . ($geoRes['status'] ?? 'onbekende fout')];
        }

        $result = $geoRes['results'][0];
        
        return [
            'lat' => $result['geometry']['location']['lat'],
            'lng' => $result['geometry']['location']['lng'],
            'address' => $result['formatted_address']
        ];
    }

    public function getTimezoneId(float $lat, float $lng, int $timestamp): array
    {
        $tzUrl = "https://maps.googleapis.com/maps/api/timezone/json?location=$lat,$lng&timestamp=$timestamp&key=" . $this->apiKey;
        $tzRes = json_decode(file_get_contents($tzUrl), true);

        if ($tzRes['status'] !== 'OK') {
            return ['error' => 'Timezone niet gevonden: ' . ($tzRes['status'] ?? 'onbekende fout')];
        }

        return ['timezoneId' => $tzRes['timeZoneId']];
    }

    private function validateLocation(string $location): bool
    {
        return (bool) preg_match('/^[\p{L}\s\-\.,\']+$/u', $location);
    }
}
