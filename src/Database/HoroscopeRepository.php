<?php

namespace Tijd\Database;

use PDO;
use Tijd\Entity\Horoscope;

class HoroscopeRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Connection::getInstance();
    }

    public function findById(int $id): ?Horoscope
    {
        $stmt = $this->db->prepare('SELECT * FROM horoscopes WHERE id = ?');
        $stmt->execute([$id]);
        $data = $stmt->fetch();

        return $data ? Horoscope::fromArray($data) : null;
    }

    public function findByIdAndUserId(int $id, int $userId): ?Horoscope
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM horoscopes WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$id, $userId]);
        $data = $stmt->fetch();

        return $data ? Horoscope::fromArray($data) : null;
    }

    public function findByUserId(int $userId, int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM horoscopes WHERE user_id = ? ORDER BY created_at DESC LIMIT ?'
        );
        $stmt->execute([$userId, $limit]);
        $rows = $stmt->fetchAll();

        $horoscopes = [];
        foreach ($rows as $row) {
            $horoscopes[] = Horoscope::fromArray($row);
        }

        return $horoscopes;
    }

    public function create(Horoscope $horoscope): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO horoscopes (
                user_id, name, birth_date, birth_time, location_name,
                latitude, longitude, timezone_id, utc_offset,
                offset_source, offset_label, formatted_address, house_system
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $horoscope->getUserId(),
            $horoscope->getName(),
            $horoscope->getBirthDate(),
            $horoscope->getBirthTime(),
            $horoscope->getLocationName(),
            $horoscope->getLatitude(),
            $horoscope->getLongitude(),
            $horoscope->getTimezoneId(),
            $horoscope->getUtcOffset(),
            $horoscope->getOffsetSource(),
            $horoscope->getOffsetLabel(),
            $horoscope->getFormattedAddress(),
            $horoscope->getHouseSystem()
        ]);

        $id = (int) $this->db->lastInsertId();
        $horoscope->setId($id);

        return $id;
    }

    public function update(Horoscope $horoscope): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE horoscopes SET 
                name = ?, birth_date = ?, birth_time = ?, location_name = ?,
                latitude = ?, longitude = ?, timezone_id = ?, utc_offset = ?,
                offset_source = ?, offset_label = ?, formatted_address = ?, house_system = ?
            WHERE id = ? AND user_id = ?'
        );

        return $stmt->execute([
            $horoscope->getName(),
            $horoscope->getBirthDate(),
            $horoscope->getBirthTime(),
            $horoscope->getLocationName(),
            $horoscope->getLatitude(),
            $horoscope->getLongitude(),
            $horoscope->getTimezoneId(),
            $horoscope->getUtcOffset(),
            $horoscope->getOffsetSource(),
            $horoscope->getOffsetLabel(),
            $horoscope->getFormattedAddress(),
            $horoscope->getHouseSystem(),
            $horoscope->getId(),
            $horoscope->getUserId()
        ]);
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM horoscopes WHERE id = ? AND user_id = ?');
        return $stmt->execute([$id, $userId]);
    }

    public function countByUserId(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM horoscopes WHERE user_id = ?');
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }
}