<?php

namespace Tijd\Database;

use PDO;
use Tijd\Entity\User;

class UserRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Connection::getInstance();
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $data = $stmt->fetch();

        return $data ? User::fromArray($data) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $data = $stmt->fetch();

        return $data ? User::fromArray($data) : null;
    }

    public function findByRememberToken(string $token): ?User
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM users WHERE remember_token = ? AND remember_expires > NOW()'
        );
        $stmt->execute([$token]);
        $data = $stmt->fetch();

        return $data ? User::fromArray($data) : null;
    }

    public function create(User $user): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (email, password_hash) VALUES (?, ?)'
        );

        $stmt->execute([
            $user->getEmail(),
            $user->getPasswordHash()
        ]);

        $id = (int) $this->db->lastInsertId();
        $user->setId($id);

        return $id;
    }

    public function updateRememberToken(User $user): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET remember_token = ?, remember_expires = ? WHERE id = ?'
        );

        return $stmt->execute([
            $user->getRememberToken(),
            $user->getRememberExpires()?->format('Y-m-d H:i:s'),
            $user->getId()
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id = ?');
        return $stmt->execute([$id]);
    }
}