<?php

namespace Astro\Entity;

class User
{
    private ?int $id = null;
    private string $email;
    private string $passwordHash;
    private ?\DateTime $emailVerifiedAt = null;
    private ?string $verificationToken = null;
    private ?\DateTime $verificationTokenExpires = null;
    private ?string $rememberToken = null;
    private ?\DateTime $rememberExpires = null;
    private ?string $passwordResetToken = null;
    private ?\DateTime $passwordResetExpires = null;
    private ?string $newEmail = null;
    private ?\DateTime $createdAt = null;
    private string $locale = 'nl_NL';

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

    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    public function getEmailVerifiedAt(): ?\DateTime
    {
        return $this->emailVerifiedAt;
    }

    public function setEmailVerifiedAt(?\DateTime $verifiedAt): self
    {
        $this->emailVerifiedAt = $verifiedAt;
        return $this;
    }

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function setVerificationToken(?string $token): self
    {
        $this->verificationToken = $token;
        return $this;
    }

    public function getVerificationTokenExpires(): ?\DateTime
    {
        return $this->verificationTokenExpires;
    }

    public function setVerificationTokenExpires(?\DateTime $expires): self
    {
        $this->verificationTokenExpires = $expires;
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

    public function getPasswordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    public function setPasswordResetToken(?string $token): self
    {
        $this->passwordResetToken = $token;
        return $this;
    }

    public function getPasswordResetExpires(): ?\DateTime
    {
        return $this->passwordResetExpires;
    }

    public function setPasswordResetExpires(?\DateTime $expires): self
    {
        $this->passwordResetExpires = $expires;
        return $this;
    }

    public function getNewEmail(): ?string
    {
        return $this->newEmail;
    }

    public function setNewEmail(?string $email): self
    {
        $this->newEmail = $email;
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

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;
        return $this;
    }

    public static function fromArray(array $data): self
    {
        $user = new self($data['email'], $data['password_hash'] ?? '');
        $user->setId((int) $data['id']);

        if (!empty($data['email_verified_at'])) {
            $user->setEmailVerifiedAt(new \DateTime($data['email_verified_at']));
        }

        if (!empty($data['verification_token'])) {
            $user->setVerificationToken($data['verification_token']);
        }

        if (!empty($data['verification_token_expires'])) {
            $user->setVerificationTokenExpires(new \DateTime($data['verification_token_expires']));
        }

        if (!empty($data['remember_token'])) {
            $user->setRememberToken($data['remember_token']);
        }

        if (!empty($data['remember_expires'])) {
            $user->setRememberExpires(new \DateTime($data['remember_expires']));
        }

        if (!empty($data['password_reset_token'])) {
            $user->setPasswordResetToken($data['password_reset_token']);
        }

        if (!empty($data['password_reset_expires'])) {
            $user->setPasswordResetExpires(new \DateTime($data['password_reset_expires']));
        }

        if (!empty($data['new_email'])) {
            $user->setNewEmail($data['new_email']);
        }

        if (!empty($data['created_at'])) {
            $user->setCreatedAt(new \DateTime($data['created_at']));
        }

        if (!empty($data['locale'])) {
            $user->setLocale($data['locale']);
        }

        return $user;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'password_hash' => $this->passwordHash,
            'email_verified_at' => $this->emailVerifiedAt?->format('Y-m-d H:i:s'),
            'verification_token' => $this->verificationToken,
            'verification_token_expires' => $this->verificationTokenExpires?->format('Y-m-d H:i:s'),
            'remember_token' => $this->rememberToken,
            'remember_expires' => $this->rememberExpires?->format('Y-m-d H:i:s'),
            'password_reset_token' => $this->passwordResetToken,
            'password_reset_expires' => $this->passwordResetExpires?->format('Y-m-d H:i:s'),
            'new_email' => $this->newEmail,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'locale' => $this->locale,
        ];
    }
}