<?php

namespace Astro\Database;

use PDO;
use Astro\Entity\User;

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
            'INSERT INTO users (email, password_hash, verification_token, verification_token_expires) VALUES (?, ?, ?, ?)'
        );

        $stmt->execute([
            $user->getEmail(),
            $user->getPasswordHash(),
            $user->getVerificationToken(),
            $user->getVerificationTokenExpires()?->format('Y-m-d H:i:s')
        ]);

        $id = (int) $this->db->lastInsertId();
        $user->setId($id);

        return $id;
    }

    public function findByVerificationToken(string $token): ?User
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM users WHERE verification_token = ? AND verification_token_expires > NOW()'
        );
        $stmt->execute([$token]);
        $data = $stmt->fetch();

        return $data ? User::fromArray($data) : null;
    }

    public function findByPasswordResetToken(string $token): ?User
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW()'
        );
        $stmt->execute([$token]);
        $data = $stmt->fetch();

        return $data ? User::fromArray($data) : null;
    }

    public function verifyEmail(User $user): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET email_verified_at = NOW(), verification_token = NULL, verification_token_expires = NULL WHERE id = ?'
        );

        return $stmt->execute([$user->getId()]);
    }

    public function updateVerificationToken(User $user): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET verification_token = ?, verification_token_expires = ? WHERE id = ?'
        );

        return $stmt->execute([
            $user->getVerificationToken(),
            $user->getVerificationTokenExpires()?->format('Y-m-d H:i:s'),
            $user->getId()
        ]);
    }

    public function updatePasswordResetToken(User $user): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?'
        );

        return $stmt->execute([
            $user->getPasswordResetToken(),
            $user->getPasswordResetExpires()?->format('Y-m-d H:i:s'),
            $user->getId()
        ]);
    }

    public function updatePassword(User $user): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET password_hash = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?'
        );

        return $stmt->execute([
            $user->getPasswordHash(),
            $user->getId()
        ]);
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