<?php

namespace Astro\Auth;

use Astro\Database\UserRepository;
use Astro\Entity\User;
use Astro\Mail\Mailer;
use Astro\Mail\EmailTemplate;

class AuthService
{
    private UserRepository $userRepository;
    private ?Mailer $mailer = null;
    private ?string $baseUrl = null;

    public function __construct(
        ?UserRepository $userRepository = null,
        ?Mailer $mailer = null,
        ?string $baseUrl = null
    ) {
        $this->userRepository = $userRepository ?? new UserRepository();
        $this->mailer = $mailer;
        $this->baseUrl = $baseUrl ?? $this->detectBaseUrl();
    }

    public function login(string $email, string $password, bool $remember = false): bool
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !password_verify($password, $user->getPasswordHash())) {
            return false;
        }

        $this->setSessionUser($user);

        if ($remember) {
            $this->setRememberCookie($user);
        }

        session_regenerate_id(true);

        return true;
    }

    public function logout(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->clearRememberToken($_SESSION['user_id']);
        }

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;

        $_SESSION = [];

        session_regenerate_id(true);

        if ($flashSuccess) $_SESSION['flash_success'] = $flashSuccess;
        if ($flashError) $_SESSION['flash_error'] = $flashError;

        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }

    public function register(string $email, string $password, bool $sendVerification = true): User
    {
        if ($this->userRepository->findByEmail($email)) {
            throw new \InvalidArgumentException('Dit e-mailadres is al geregistreerd.');
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $verificationToken = bin2hex(random_bytes(32));
        $verificationExpires = new \DateTime('+24 hours');

        $user = new User($email, $passwordHash);
        $user->setVerificationToken($verificationToken);
        $user->setVerificationTokenExpires($verificationExpires);

        $this->userRepository->create($user);

        if ($sendVerification && $this->mailer) {
            $this->sendVerificationEmail($user);
        }

        $this->setSessionUser($user);
        session_regenerate_id(true);

        return $user;
    }

    public function sendVerificationEmail(User $user): bool
    {
        if (!$this->mailer) {
            return false;
        }

        $token = $user->getVerificationToken();
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            $user->setVerificationToken($token);
            $user->setVerificationTokenExpires(new \DateTime('+24 hours'));
            $this->userRepository->updateVerificationToken($user);
        }

        $body = EmailTemplate::verifyEmail($token, $this->baseUrl);

        return $this->mailer->send(
            $user->getEmail(),
            'Verifieer je e-mailadres',
            $body
        );
    }

    public function verifyEmail(string $token): bool
    {
        $user = $this->userRepository->findByVerificationToken($token);

        if (!$user) {
            return false;
        }

        $this->userRepository->verifyEmail($user);

        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === $user->getId()) {
            $_SESSION['email_verified'] = true;
        }

        return true;
    }

    public function isEmailVerified(): bool
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $user = $this->getCurrentUser();
        return $user && $user->isEmailVerified();
    }

    public function requestPasswordReset(string $email): bool
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            return true;
        }

        $token = bin2hex(random_bytes(32));
        $expires = new \DateTime('+1 hour');

        $user->setPasswordResetToken($token);
        $user->setPasswordResetExpires($expires);
        $this->userRepository->updatePasswordResetToken($user);

        if ($this->mailer) {
            $body = EmailTemplate::passwordReset($token, $this->baseUrl);
            return $this->mailer->send(
                $user->getEmail(),
                'Wachtwoord resetten',
                $body
            );
        }

        return true;
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $user = $this->userRepository->findByPasswordResetToken($token);

        if (!$user) {
            return false;
        }

        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $user->setPasswordHash($passwordHash);
        $this->userRepository->updatePassword($user);

        return true;
    }

    public function isLoggedIn(): bool
    {
        if (isset($_SESSION['user_id'])) {
            return true;
        }

        return $this->tryLoginFromCookie();
    }

    public function getCurrentUser(): ?User
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return $this->userRepository->findById($_SESSION['user_id']);
    }

    public function getCurrentUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    private function setSessionUser(User $user): void
    {
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['user_email'] = $user->getEmail();
        $_SESSION['email_verified'] = $user->isEmailVerified();
    }

    private function setRememberCookie(User $user): void
    {
        $token = bin2hex(random_bytes(32));
        $expires = new \DateTime('+30 days');

        $user->setRememberToken($token);
        $user->setRememberExpires($expires);
        $this->userRepository->updateRememberToken($user);

        setcookie(
            'remember_token',
            $token,
            $expires->getTimestamp(),
            '/',
            '',
            true,
            true
        );
    }

    private function tryLoginFromCookie(): bool
    {
        if (!isset($_COOKIE['remember_token'])) {
            return false;
        }

        $token = $_COOKIE['remember_token'];
        $user = $this->userRepository->findByRememberToken($token);

        if (!$user) {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
            return false;
        }

        $expires = $user->getRememberExpires();
        if ($expires && $expires < new \DateTime()) {
            $this->clearRememberToken($user->getId());
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
            return false;
        }

        $this->setSessionUser($user);
        return true;
    }

    private function clearRememberToken(int $userId): void
    {
        $user = $this->userRepository->findById($userId);
        if ($user) {
            $user->setRememberToken(null);
            $user->setRememberExpires(null);
            $this->userRepository->updateRememberToken($user);
        }
    }

    private function detectBaseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = dirname($_SERVER['SCRIPT_NAME'] ?? '');

        return "{$protocol}://{$host}{$path}";
    }
}