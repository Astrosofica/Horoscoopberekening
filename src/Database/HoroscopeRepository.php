<?php

namespace Astro\Database;

use PDO;
use Astro\Entity\Horoscope;

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

    public function findBySlug(string $slug): ?Horoscope
    {
        $stmt = $this->db->prepare('SELECT * FROM horoscopes WHERE slug = ?');
        $stmt->execute([$slug]);
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

    public function findBySlugAndUserId(string $slug, int $userId): ?Horoscope
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM horoscopes WHERE slug = ? AND user_id = ?'
        );
        $stmt->execute([$slug, $userId]);
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

    public function findByUserIdPaginated(
        int $userId,
        string $sort = 'newest',
        int $page = 1,
        int $perPage = 10
    ): array {
        $offset = max(0, ($page - 1) * $perPage);
        
        $orderBy = match($sort) {
            'name' => 'lastname ASC, firstname ASC',
            'name_desc' => 'lastname DESC, firstname DESC',
            'oldest' => 'created_at ASC',
            default => 'created_at DESC',
        };
        
        $stmt = $this->db->prepare(
            "SELECT * FROM horoscopes 
             WHERE user_id = ? 
             ORDER BY {$orderBy} 
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$userId, $perPage, $offset]);
        $rows = $stmt->fetchAll();

        $horoscopes = [];
        foreach ($rows as $row) {
            $horoscopes[] = Horoscope::fromArray($row);
        }

        return $horoscopes;
    }

    public function create(Horoscope $horoscope): int
    {
        $slug = $this->generateUniqueSlug();

        $stmt = $this->db->prepare(
            'INSERT INTO horoscopes (
                slug, user_id, firstname, infix, lastname, birth_date, birth_time, location_name,
                latitude, longitude, timezone_id, utc_offset, time_correction,
                offset_source, offset_label, formatted_address, house_system
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $slug,
            $horoscope->getUserId(),
            $horoscope->getFirstname(),
            $horoscope->getInfix(),
            $horoscope->getLastname(),
            $horoscope->getBirthDate(),
            $horoscope->getBirthTime(),
            $horoscope->getLocationName(),
            $horoscope->getLatitude(),
            $horoscope->getLongitude(),
            $horoscope->getTimezoneId(),
            $horoscope->getUtcOffset(),
            $horoscope->getTimeCorrection(),
            $horoscope->getOffsetSource(),
            $horoscope->getOffsetLabel(),
            $horoscope->getFormattedAddress(),
            $horoscope->getHouseSystem()
        ]);

        $id = (int) $this->db->lastInsertId();
        $horoscope->setId($id);
        $horoscope->setSlug($slug);

        return $id;
    }

    private function generateUniqueSlug(): string
    {
        do {
            $slug = substr(bin2hex(random_bytes(8)), 0, 12);
            $stmt = $this->db->prepare('SELECT id FROM horoscopes WHERE slug = ?');
            $stmt->execute([$slug]);
        } while ($stmt->fetch());

        return $slug;
    }

    public function update(Horoscope $horoscope): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE horoscopes SET 
                firstname = ?, infix = ?, lastname = ?, birth_date = ?, birth_time = ?, location_name = ?,
                latitude = ?, longitude = ?, timezone_id = ?, utc_offset = ?, time_correction = ?,
                offset_source = ?, offset_label = ?, formatted_address = ?, house_system = ?
            WHERE id = ? AND user_id = ?'
        );

        return $stmt->execute([
            $horoscope->getFirstname(),
            $horoscope->getInfix(),
            $horoscope->getLastname(),
            $horoscope->getBirthDate(),
            $horoscope->getBirthTime(),
            $horoscope->getLocationName(),
            $horoscope->getLatitude(),
            $horoscope->getLongitude(),
            $horoscope->getTimezoneId(),
            $horoscope->getUtcOffset(),
            $horoscope->getTimeCorrection(),
            $horoscope->getOffsetSource(),
            $horoscope->getOffsetLabel(),
            $horoscope->getFormattedAddress(),
            $horoscope->getHouseSystem(),
            $horoscope->getId(),
            $horoscope->getUserId()
        ]);
    }

    public function deleteBySlug(string $slug, int $userId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM horoscopes WHERE slug = ? AND user_id = ?');
        return $stmt->execute([$slug, $userId]);
    }

    public function searchByNamePaginated(
        int $userId,
        string $query,
        string $sort = 'newest',
        int $page = 1,
        int $perPage = 10
    ): array {
        $offset = max(0, ($page - 1) * $perPage);

        $orderBy = match($sort) {
            'name' => 'lastname ASC, firstname ASC',
            'name_desc' => 'lastname DESC, firstname DESC',
            'oldest' => 'created_at ASC',
            default => 'created_at DESC',
        };

        $likeQuery = '%' . $query . '%';

        $stmt = $this->db->prepare(
            "SELECT * FROM horoscopes
             WHERE user_id = ?
               AND (LOWER(firstname) LIKE LOWER(?)
                 OR LOWER(infix) LIKE LOWER(?)
                 OR LOWER(lastname) LIKE LOWER(?)
                 OR LOWER(CONCAT(lastname, ', ', firstname, ' ', COALESCE(infix, ''))) LIKE LOWER(?))
             ORDER BY {$orderBy}
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$userId, $likeQuery, $likeQuery, $likeQuery, $likeQuery, $perPage, $offset]);
        $rows = $stmt->fetchAll();

        $horoscopes = [];
        foreach ($rows as $row) {
            $horoscopes[] = Horoscope::fromArray($row);
        }

        return $horoscopes;
    }

    public function countSearchResults(int $userId, string $query): int
    {
        $likeQuery = '%' . $query . '%';

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM horoscopes
             WHERE user_id = ?
               AND (LOWER(firstname) LIKE LOWER(?)
                 OR LOWER(infix) LIKE LOWER(?)
                 OR LOWER(lastname) LIKE LOWER(?)
                 OR LOWER(CONCAT(lastname, ', ', firstname, ' ', COALESCE(infix, ''))) LIKE LOWER(?))"
        );
        $stmt->execute([$userId, $likeQuery, $likeQuery, $likeQuery, $likeQuery]);
        return (int) $stmt->fetchColumn();
    }

    public function countByUserId(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM horoscopes WHERE user_id = ?');
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }
}