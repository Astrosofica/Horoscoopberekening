<?php

namespace Astro\Time;

class AstroTime
{
    private const SECONDS_PER_DEGREE = 240;
    private const MIN_TIMESTAMP = -5364662400;
    private const MAX_TIMESTAMP = 13569465599;

    private string $timezoneId;
    private float $longitude;
    private ?array $nlTableCache = null;
    private ?array $beTableCache = null;

    public function __construct(string $timezoneId, float $lng)
    {
        if (!in_array($timezoneId, \DateTimeZone::listIdentifiers(), true)) {
            throw new \InvalidArgumentException(
                "Ongeldige timezone ID: '{$timezoneId}'. Gebruik een geldige IANA timezone (bijv. 'Europe/Amsterdam')."
            );
        }

        if ($lng < -180 || $lng > 180) {
            throw new \InvalidArgumentException(
                "Longitude moet tussen -180 en 180 graden liggen. Ontvangen: {$lng}"
            );
        }

        $this->timezoneId = $timezoneId;
        $this->longitude = $lng;
    }

    public function getTimezoneId(): string
    {
        return $this->timezoneId;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function getOffset(int $timestamp): array
    {
        if ($timestamp < self::MIN_TIMESTAMP) {
            return [
                'offset' => 0,
                'source' => 'Error',
                'label'  => 'OUT_OF_RANGE',
                'error'  => 'Datum te oud. Ondersteunde range: 1800-2399 CE. Gebruik een timestamp na ' . gmdate('Y-m-d', self::MIN_TIMESTAMP) . '.'
            ];
        }

        if ($timestamp > self::MAX_TIMESTAMP) {
            return [
                'offset' => 0,
                'source' => 'Error',
                'label'  => 'OUT_OF_RANGE',
                'error'  => 'Datum te ver in de toekomst. Ondersteunde range: 1800-2399 CE. Gebruik een timestamp voor ' . gmdate('Y-m-d', self::MAX_TIMESTAMP) . '.'
            ];
        }

        if ($this->timezoneId === 'Europe/Amsterdam') {
            $nlOffset = $this->getNetherlandsPrecisionOffset($timestamp);
            if ($nlOffset !== null) return $nlOffset;
        }

        if ($this->timezoneId === 'Europe/Brussels') {
            $beOffset = $this->getBelgiumPrecisionOffset($timestamp);
            if ($beOffset !== null) return $beOffset;
        }

        return $this->getIanaOffset($timestamp);
    }

    private function getNetherlandsPrecisionOffset(int $timestamp): ?array
    {
        $utcStr = gmdate("Y-m-d H:i:s", $timestamp);
        $year = (int)gmdate("Y", $timestamp);

        if ($utcStr < "1909-04-30 23:40:28") {
            return [
                'offset' => (int)round($this->longitude * self::SECONDS_PER_DEGREE),
                'source' => 'Manual NL: LMT (Plaatselijke tijd)',
                'label'  => 'LMT'
            ];
        }

        if ($year <= 1945) {
            $offset = $this->getNetherlandsTableOffset($utcStr);

            if ($year < 1937 || ($year === 1937 && $utcStr < "1937-06-30 22:40:28")) {
                $label = ($offset > 1200) ? 'AMST' : 'AMT';
            } elseif ($year < 1940 || ($year === 1940 && $utcStr < "1940-05-15 23:40:00")) {
                $label = ($offset > 1200) ? 'NST' : 'NT';
            } else {
                $label = ($offset >= 7200) ? 'MEST' : 'MET';
            }

            return [
                'offset' => $offset,
                'source' => 'Manual NL: High-Precision UTC Table',
                'label'  => $label
            ];
        }

        return null;
    }

    protected function getNetherlandsTableOffset(string $utcStr): int
    {
        if ($this->nlTableCache === null) {
            $this->nlTableCache = [
                "1909-04-30 23:40:28" => 1172,
                "1916-04-30 23:40:28" => 4772, "1916-09-30 22:40:28" => 1172,
                "1917-04-16 01:40:28" => 4772, "1917-09-16 01:40:28" => 1172,
                "1918-04-01 01:40:28" => 4772, "1918-09-29 01:40:28" => 1172,
                "1919-04-07 01:40:28" => 4772, "1919-09-28 01:40:28" => 1172,
                "1920-04-05 01:40:28" => 4772, "1920-09-26 01:40:28" => 1172,
                "1921-04-04 01:40:28" => 4772, "1921-10-02 01:40:28" => 1172,
                "1922-03-26 01:40:28" => 4772, "1922-10-08 01:40:28" => 1172,
                "1923-04-22 01:40:28" => 4772, "1923-10-07 01:40:28" => 1172,
                "1924-03-30 01:40:28" => 4772, "1924-10-05 01:40:28" => 1172,
                "1925-04-19 01:40:28" => 4772, "1925-10-04 01:40:28" => 1172,
                "1926-05-15 01:40:28" => 4772, "1926-10-03 01:40:28" => 1172,
                "1927-05-15 01:40:28" => 4772, "1927-10-02 01:40:28" => 1172,
                "1928-05-15 01:40:28" => 4772, "1928-10-07 01:40:28" => 1172,
                "1929-05-15 01:40:28" => 4772, "1929-10-06 01:40:28" => 1172,
                "1930-05-15 01:40:28" => 4772, "1930-10-05 01:40:28" => 1172,
                "1931-05-15 01:40:28" => 4772, "1931-10-04 01:40:28" => 1172,
                "1932-05-22 01:40:28" => 4772, "1932-10-02 01:40:28" => 1172,
                "1933-05-15 01:40:28" => 4772, "1933-10-08 01:40:28" => 1172,
                "1934-05-15 01:40:28" => 4772, "1934-10-07 01:40:28" => 1172,
                "1935-05-15 01:40:28" => 4772, "1935-10-06 01:40:28" => 1172,
                "1936-05-15 01:40:28" => 4772, "1936-10-04 01:40:28" => 1172,
                "1937-05-22 01:40:28" => 4772,
                "1937-06-30 22:40:28" => 4800,
                "1937-10-03 01:40:00" => 1200,
                "1938-05-15 01:40:00" => 4800,
                "1938-10-02 01:40:00" => 1200,
                "1939-05-15 01:40:00" => 4800,
                "1939-10-08 01:40:00" => 1200,
                "1940-05-15 23:40:00" => 7200,
                "1941-10-05 01:00:00" => 3600,
                "1942-03-09 01:00:00" => 7200, "1942-11-02 01:00:00" => 3600,
                "1943-03-29 01:00:00" => 7200, "1943-10-04 01:00:00" => 3600,
                "1944-04-03 01:00:00" => 7200, "1944-10-02 01:00:00" => 3600,
                "1945-04-02 01:00:00" => 7200, "1945-09-16 01:00:00" => 3600,
            ];
            krsort($this->nlTableCache);
        }

        foreach ($this->nlTableCache as $time => $off) {
            if ($utcStr >= $time) return $off;
        }
        return 1172;
    }

    private function getBelgiumPrecisionOffset(int $timestamp): ?array
    {
        $utcStr = gmdate("Y-m-d H:i:s", $timestamp);
        $year = (int)gmdate("Y", $timestamp);

        if ($utcStr < "1880-05-01 00:00:00") {
            return ['offset' => (int)round($this->longitude * self::SECONDS_PER_DEGREE), 'source' => 'Manual BE: LMT', 'label' => 'LMT'];
        }
        if ($utcStr < "1892-05-01 00:00:00") {
            return ['offset' => 1049, 'source' => 'Manual BE: Brussels Meridian', 'label' => 'BMT'];
        }
        if ($utcStr < "1947-01-01 00:00:00") {
            $offset = $this->getBelgianTableOffset($utcStr);
            if ($year < 1940) {
                $label = ($offset > 0) ? 'GMT+DST' : 'GMT';
            } else {
                $label = ($offset >= 7200) ? 'MEST' : 'MET';
            }
            return ['offset' => $offset, 'source' => 'Manual BE: Official Table (KSB)', 'label' => $label];
        }
        return null;
    }

    protected function getBelgianTableOffset(string $utcStr): int
    {
        if ($this->beTableCache === null) {
            $this->beTableCache = [
                "1892-05-01 00:00:00" => 0,    "1914-08-04 00:00:00" => 3600, "1916-04-30 23:00:00" => 7200,
                "1916-09-30 23:00:00" => 3600, "1917-04-16 01:00:00" => 7200, "1917-09-17 01:00:00" => 3600,
                "1918-04-15 01:00:00" => 7200, "1918-09-16 01:00:00" => 3600, "1918-11-11 00:00:00" => 0,
                "1919-03-01 23:00:00" => 3600,
                "1919-10-04 23:00:00" => 0,    "1920-02-14 23:00:00" => 3600, "1920-10-23 23:00:00" => 0,
                "1921-03-14 23:00:00" => 3600, "1921-10-25 23:00:00" => 0,    "1922-03-25 23:00:00" => 3600,
                "1922-10-07 23:00:00" => 0,    "1923-04-21 23:00:00" => 3600, "1923-10-06 23:00:00" => 0,
                "1924-03-29 23:00:00" => 3600, "1924-10-04 23:00:00" => 0,    "1925-04-04 23:00:00" => 3600,
                "1925-10-03 23:00:00" => 0,    "1926-04-17 23:00:00" => 3600, "1926-10-02 23:00:00" => 0,
                "1927-04-09 23:00:00" => 3600, "1927-10-01 23:00:00" => 0,    "1928-04-14 23:00:00" => 3600,
                "1928-10-07 02:00:00" => 0,    "1929-04-21 02:00:00" => 3600, "1929-10-06 02:00:00" => 0,
                "1930-04-13 02:00:00" => 3600, "1930-10-05 02:00:00" => 0,    "1931-04-19 02:00:00" => 3600,
                "1931-10-04 02:00:00" => 0,    "1932-04-03 02:00:00" => 3600, "1932-10-02 02:00:00" => 0,
                "1933-03-26 02:00:00" => 3600, "1933-10-08 02:00:00" => 0,    "1934-04-08 02:00:00" => 3600,
                "1934-10-07 02:00:00" => 0,    "1935-03-31 02:00:00" => 3600, "1935-10-06 02:00:00" => 0,
                "1936-04-19 02:00:00" => 3600, "1936-10-04 02:00:00" => 0,    "1937-04-04 02:00:00" => 3600,
                "1937-10-03 02:00:00" => 0,    "1938-03-27 02:00:00" => 3600, "1938-10-02 02:00:00" => 0,
                "1939-04-16 02:00:00" => 3600, "1939-11-19 02:00:00" => 0,    "1940-02-25 02:00:00" => 3600,
                "1940-05-20 02:00:00" => 7200, "1942-11-02 01:00:00" => 3600, "1943-03-29 01:00:00" => 7200,
                "1943-10-04 01:00:00" => 3600, "1944-04-03 01:00:00" => 7200, "1944-09-17 01:00:00" => 3600,
                "1945-04-02 01:00:00" => 7200, "1945-09-16 01:00:00" => 3600, "1946-05-19 01:00:00" => 7200,
                "1946-10-07 01:00:00" => 3600,
            ];
            krsort($this->beTableCache);
        }

        foreach ($this->beTableCache as $time => $off) {
            if ($utcStr >= $time) return $off;
        }
        return 0;
    }

    private function getIanaOffset(int $timestamp): array
    {
        try {
            $tz = new \DateTimeZone($this->timezoneId);
            $transitions = $tz->getTransitions($timestamp, $timestamp);

            if (!isset($transitions[0]) || !isset($transitions[0]['offset'])) {
                throw new \RuntimeException(
                    "Geen timezone transitie gevonden voor {$this->timezoneId} op timestamp {$timestamp}"
                );
            }

            $abbr = $transitions[0]['abbr'] ?? 'UNKNOWN';

            // When the IANA database returns "LMT" as abbreviation, it reflects the
            // timezone's reference meridian LMT (e.g., Berlin for Europe/Berlin).
            // Actual LMT for the birth location depends on its specific longitude.
            if ($abbr === 'LMT') {
                return [
                    'offset' => (int)round($this->longitude * self::SECONDS_PER_DEGREE),
                    'source' => 'Auto LMT (location-based)',
                    'label'  => 'LMT'
                ];
            }

            return [
                'offset' => $transitions[0]['offset'],
                'source' => 'IANA Database',
                'label'  => $abbr
            ];
        } catch (\Exception $e) {
            error_log(
                sprintf(
                    "[AstroTime] IANA fallback faalde voor timezone='%s', timestamp=%d: %s",
                    $this->timezoneId,
                    $timestamp,
                    $e->getMessage()
                )
            );

            return [
                'offset' => (int)round($this->longitude * self::SECONDS_PER_DEGREE),
                'source' => 'Fallback LMT (IANA error)',
                'label'  => 'LMT'
            ];
        }
    }
}
