<?php
/**
 * Astrologische Tijdzone Engine
 * Focus op historische accuratesse voor NL/BE en IANA voor de rest van de wereld.
 */

class AstrologicalTimeManager {
    
    // IANA Tijdzone ID (bijv. 'Europe/Amsterdam')
    private $timezoneId;
    private $latitude;
    private $longitude;

    public function __construct(string $timezoneId, float $lat, float $lng) {
        $this->timezoneId = $timezoneId;
        $this->latitude = $lat;
        $this->longitude = $lng;
    }

    /**
     * De hoofdmethode om de offset in seconden te verkrijgen
     */
    public function getOffset(int $timestamp): array {
        // 1. Check voor specifieke Nederlandse data
        if ($this->timezoneId === 'Europe/Amsterdam') {
            $nlOffset = $this->getNetherlandsPrecisionOffset($timestamp);
            if ($nlOffset !== null) return $nlOffset;
        }

        // 2. Check voor specifieke Belgische data (structuur klaargezet)
        if ($this->timezoneId === 'Europe/Brussels') {
            $beOffset = $this->getBelgiumPrecisionOffset($timestamp);
            if ($beOffset !== null) return $beOffset;
        }

        // 3. Fallback naar IANA (voor de rest van de wereld of moderne data)
        return $this->getIanaOffset($timestamp);
    }

    /**
     * Nederland: Handmatige tabel 
     */
    private function getNetherlandsPrecisionOffset(int $timestamp): ?array {
        $dateStr = date("Y-m-d H:i:s", $timestamp);
        $year = (int)date("Y", $timestamp);

        // A. Vóór mei 1909: LMT (Plaatselijke tijd)
        if ($dateStr < "1909-05-01 00:00:00") {
            return [
                'offset' => (int)round($this->longitude * 240),
                'source' => 'Manual LMT',
                'label'  => 'LMT'
            ];
        }

        // B. 1909 - juli 1937: Amsterdamse Tijd (+19m 32s)
        if ($dateStr < "1937-07-01 00:00:00") {
            $isDst = $this->checkManualNLDST($timestamp);
            return [
                'offset' => $isDst ? 1172 + 3600 : 1172,
                'source' => 'Manual High-Precision (Amsterdam Time)',
                'label'  => $isDst ? 'AMST' : 'AMT'
            ];
        }

        // C. 1937 - mei 1940: Loenense Tijd (+20m 00s)
        if ($dateStr < "1940-05-16 00:00:00") {
            $isDst = $this->checkManualNLDST($timestamp);
            return [
                'offset' => $isDst ? 1200 + 3600 : 1200,
                'source' => 'Manual High-Precision (Loenense Time)',
                'label'  => $isDst ? 'NST' : 'NT'
            ];
        }

        // D. 1940 - 1945: Oorlogstijd (MET/MEST)
        if ($year <= 1945) {
            $isDst = $this->checkManualNLDST($timestamp);
            return [
                'offset' => $isDst ? 7200 : 3600,
                'source' => 'Manual High-Precision (War Time)',
                'label'  => $isDst ? 'MEST' : 'MET'
            ];
        }

        // Na 1945: Gebruik IANA
        return null;
    }

    /**
     * BELGIË (Gebaseerd op UTC transities van de Sterrenwacht Brussel)
     */
    private function getBelgiumPrecisionOffset(int $timestamp): ?array {
        $utcStr = gmdate("Y-m-d H:i:s", $timestamp);
        $year = (int)gmdate("Y", $timestamp);
        
        // A. Pre-1880: LMT
        if ($utcStr < "1880-05-01 00:00:00") {
            return ['offset' => (int)round($this->longitude * 240), 'source' => 'Manual BE: LMT', 'label' => 'LMT'];
        }
        // B. 1880 - 1892: Brussel Standaard (+17m 29s)
        if ($utcStr < "1892-05-01 00:00:00") {
            return ['offset' => 1049, 'source' => 'Manual BE: Brussels Meridian', 'label' => 'BMT'];
        }
        // C. 1892 - 1946: De Transitie-tabel
        if ($utcStr < "1947-01-01 00:00:00") {
            $offset = $this->getBelgianTableOffset($utcStr);
            //$label = ($offset >= 7200) ? 'MEST' : (($offset >= 3600) ? 'MET' : 'GMT');
            if ($year < 1940) {
                $label = ($offset > 0) ? 'GMT+DST' : 'GMT';
            } else {
                $label = ($offset >= 7200) ? 'MEST' : 'MET';
            }
            return ['offset' => $offset, 'source' => 'Manual BE: Official Table (KSB)', 'label' => $label];
        }
        return null;
    }

    private function getBelgianTableOffset(string $utcStr): int {
        $t = [
            "1892-05-01 00:00:00" => 0,    "1914-08-04 00:00:00" => 3600, "1916-04-30 23:00:00" => 7200,
            "1916-09-30 23:00:00" => 3600, "1917-04-16 01:00:00" => 7200, "1917-09-17 01:00:00" => 3600,
            "1918-04-15 01:00:00" => 7200, "1918-09-16 01:00:00" => 0,    "1919-03-01 23:00:00" => 3600,
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
        krsort($t);
        foreach ($t as $time => $off) { if ($utcStr >= $time) return $off; }
        return 0;
    }

    /**
     * Standaard IANA Fallback
     */
    private function getIanaOffset(int $timestamp): array {
        try {
            $tz = new DateTimeZone($this->timezoneId);
            $transitions = $tz->getTransitions($timestamp, $timestamp);
            $offset = $transitions[0]['offset'] ?? 0;
            return [
                'offset' => $offset,
                'source' => 'IANA Database',
                'label'  => $transitions[0]['abbr'] ?? ''
            ];
        } catch (Exception $e) {
            // Als alles faalt: Astronomisch LMT
            return [
                'offset' => (int)round($this->longitude * 240),
                'source' => 'Fallback LMT',
                'label'  => 'LMT'
            ];
        }
    }

    /**
     * Zomertijd tabel Nederland (1916-1945)
     */
    private function checkManualNLDST(int $timestamp): bool {
        $dateStr = date("Y-m-d H:i:s", $timestamp);
        $year = (int)date("Y", $timestamp);

        $dstTable = [
            1916 => ['1916-05-01 00:00:00', '1916-10-01 00:00:00'],
            1917 => ['1917-04-16 02:00:00', '1917-09-17 03:00:00'],
            1918 => ['1918-04-01 02:00:00', '1918-09-16 03:00:00'],
            1919 => ['1919-04-07 02:00:00', '1919-09-15 03:00:00'],
            1920 => ['1920-04-05 02:00:00', '1920-09-27 03:00:00'],
            1921 => ['1921-04-04 02:00:00', '1921-09-26 03:00:00'],
            1922 => ['1922-03-26 02:00:00', '1922-10-08 03:00:00'],
            1923 => ['1923-04-22 02:00:00', '1923-09-16 03:00:00'],
            1924 => ['1924-03-30 02:00:00', '1924-10-05 03:00:00'],
            1925 => ['1925-04-19 02:00:00', '1925-10-04 03:00:00'],
            1926 => ['1926-05-15 02:00:00', '1926-10-03 03:00:00'],
            1927 => ['1927-05-15 02:00:00', '1927-10-02 03:00:00'],
            1928 => ['1928-05-15 02:00:00', '1928-10-07 03:00:00'],
            1929 => ['1929-05-15 02:00:00', '1929-10-06 03:00:00'],
            1930 => ['1930-05-15 02:00:00', '1930-10-05 03:00:00'],
            1931 => ['1931-05-15 02:00:00', '1931-10-04 03:00:00'],
            1932 => ['1932-05-22 02:00:00', '1932-10-02 03:00:00'],
            1933 => ['1933-05-15 02:00:00', '1933-10-08 03:00:00'],
            1934 => ['1934-05-15 02:00:00', '1934-10-07 03:00:00'],
            1935 => ['1935-05-15 02:00:00', '1935-10-06 03:00:00'],
            1936 => ['1936-05-15 02:00:00', '1936-10-04 03:00:00'],
            1937 => ['1937-05-22 00:00:00', '1937-10-03 00:00:00'],
            1938 => ['1938-05-15 00:00:00', '1938-10-02 00:00:00'],
            1939 => ['1939-05-15 00:00:00', '1939-10-08 00:00:00'],
            1940 => ['1940-05-16 00:00:00', '1940-12-31 23:59:59'],
            1941 => ['1941-01-01 00:00:00', '1941-10-05 03:00:00'],
            1942 => ['1942-11-02 02:00:00', '1942-11-02 03:00:00'],
            1943 => ['1943-03-29 02:00:00', '1943-10-04 03:00:00'],
            1944 => ['1944-04-03 02:00:00', '1944-10-02 03:00:00'],
            1945 => ['1945-04-02 02:00:00', '1945-09-16 03:00:00'],
        ];

        if (!isset($dstTable[$year])) return false;
        return ($dateStr >= $dstTable[$year][0] && $dateStr < $dstTable[$year][1]);
    }
}

// --- GEBRUIKERS VOORBEELD ---
/*
$locatie = "Utrecht"; 
$lat = 52.0907; // Verkregen via Google Geocoding
$lng = 5.1214;
$tzId = "Europe/Amsterdam";
$geboorteTimestamp = strtotime("1938-06-15 14:30:00");

$manager = new AstrologicalTimeManager($tzId, $lat, $lng);
$result = $manager->getOffset($geboorteTimestamp);

echo "Locatie: $locatie\n";
echo "Datum: " . date("Y-m-d H:i:s", $geboorteTimestamp) . "\n";
echo "Offset: " . ($result['offset'] / 3600) . " uur\n";
echo "Bron: " . $result['source'] . " (" . $result['label'] . ")\n";
*/