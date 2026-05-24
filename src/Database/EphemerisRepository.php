<?php

namespace Tijd\Database;

use PDO;

class EphemerisRepository
{
    private PDO $db;

    public function __construct()
    {
        $host = $_ENV['EDB_HOST'] ?? 'localhost';
        $db   = $_ENV['EDB_NAME'] ?? 'ephemeris';
        $user = $_ENV['EDB_USER'] ?? 'ephemeris_user';
        $pass = $_ENV['EDB_PASS'] ?? '';

        $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $this->db = new PDO($dsn, $user, $pass, $options);
    }

    /**
     * Planet index naar tabelnaam.
     */
    private static array $planetTables = [
        0 => 'sun',
        1 => 'moon',
        2 => 'sun',     // Mercury niet in DB, fallback
        3 => 'sun',     // Venus niet in DB
        4 => 'sun',     // Mars niet in DB
        5 => 'jupiter',
        6 => 'saturn',
        7 => 'uranus',
        8 => 'neptune',
        9 => 'pluto',
    ];

    /**
     * Vind crossing points: wanneer een transiterende planeet een gevoelig punt passeert.
     *
     * @param int $planet Planet index (5-9 voor Jupiter-Pluto)
     * @param float $position Target longitude (0-360)
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return array Array van ['date' => string, 'pos' => float, 'speed' => float]
     */
    public function findPositionCrossings(int $planet, float $position, string $startDate, string $endDate): array
    {
        $table = self::$planetTables[$planet] ?? null;
        if ($table === null) {
            return [];
        }

        // Controleer of de tabel bestaat
        if (!$this->tableExists($table)) {
            return [];
        }

        $sql = "
            SELECT
                s1.date AS date,
                s1.pos AS pos,
                s1.speed AS speed,
                s2.pos AS pos_next
            FROM {$table} s1
            JOIN {$table} s2 ON s2.id = s1.id + 1
            WHERE
                (
                    (s1.pos < :pos1 AND s2.pos >= :pos2 AND s1.speed >= 0)
                    OR (s1.pos > :pos3 AND s2.pos <= :pos4 AND s1.speed < 0)
                )
                AND s1.date >= :startDate
                AND s1.date <= :endDate
            ORDER BY s1.date ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'pos1' => $position,
            'pos2' => $position,
            'pos3' => $position,
            'pos4' => $position,
            'startDate' => $startDate . ' 00:00:00',
            'endDate' => $endDate . ' 23:59:59',
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Check of een tabel bestaat in de ephemeris database.
     */
    private function tableExists(string $table): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :db AND table_name = :table"
        );
        $stmt->execute(['db' => $_ENV['EDB_NAME'] ?? 'ephemeris', 'table' => $table]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Haal de beschikbare datumbereik op uit een tabel.
     */
    public function getTableDateRange(string $table): ?array
    {
        if (!$this->tableExists($table)) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT MIN(date) as min_date, MAX(date) as max_date FROM {$table}");
        $stmt->execute();
        $row = $stmt->fetch();

        if ($row['min_date'] === null) {
            return null;
        }

        return [
            'min_date' => $row['min_date'],
            'max_date' => $row['max_date'],
        ];
    }
}
