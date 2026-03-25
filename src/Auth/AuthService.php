<?php

namespace Tijd\Auth;

use Tijd\Database\Connection;
use Tijd\Database\UserRepository;
use Tijd\Entity\User;

class AuthService
{
    private UserRepository $userRepository;

    public function __construct(?UserRepository $userRepository = null)
    {
        $this->userRepository = $userRepository ?? new UserRepository();
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

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        setcookie('remember_token', '', time() - 3600, '/', '', true, true);

        session_destroy();
    }

    public function register(string $email, string $password): User
    {
        if ($this->userRepository->findByEmail($email)) {
            throw new \InvalidArgumentException('Dit e-mailadres is al geregistreerd.');
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $user = new User($email, $passwordHash);
        $this->userRepository->create($user);

        $this->setSessionUser($user);

        session_regenerate_id(true);

        return $user;
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
}