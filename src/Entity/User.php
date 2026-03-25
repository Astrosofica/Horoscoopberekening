<?php

namespace Tijd\Entity;

class User
{
    private ?int $id = null;
    private string $email;
    private string $passwordHash;
    private ?string $rememberToken = null;
    private ?\DateTime $rememberExpires = null;
    private ?\DateTime $createdAt = null;

    public function __construct(string $email, string $passwordHash = '')
    {
        $this->email = $email;
        $this->passwordHash = $passwordHash;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $hash): self
    {
        $this->passwordHash = $hash;
        return $this;
    }

    public function getRememberToken(): ?string
    {
        return $this->rememberToken;
    }

    public function setRememberToken(?string $token): self
    {
        $this->rememberToken = $token;
        return $this;
    }

    public function getRememberExpires(): ?\DateTime
    {
        return $this->rememberExpires;
    }

    public function setRememberExpires(?\DateTime $expires): self
    {
        $this->rememberExpires = $expires;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public static function fromArray(array $data): self
    {
        $user = new self($data['email'], $data['password_hash'] ?? '');
        $user->setId((int) $data['id']);

        if (!empty($data['remember_token'])) {
            $user->setRememberToken($data['remember_token']);
        }

        if (!empty($data['remember_expires'])) {
            $user->setRememberExpires(new \DateTime($data['remember_expires']));
        }

        if (!empty($data['created_at'])) {
            $user->setCreatedAt(new \DateTime($data['created_at']));
        }

        return $user;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'password_hash' => $this->passwordHash,
            'remember_token' => $this->rememberToken,
            'remember_expires' => $this->rememberExpires?->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
        ];
    }
}