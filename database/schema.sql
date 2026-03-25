-- Database: tijd
-- Maak database aan indien niet bestaand
CREATE DATABASE IF NOT EXISTS tijd CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE tijd;

-- Users tabel
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP NULL DEFAULT NULL,
    verification_token VARCHAR(64) DEFAULT NULL,
    verification_token_expires TIMESTAMP NULL DEFAULT NULL,
    remember_token VARCHAR(64) DEFAULT NULL,
    remember_expires TIMESTAMP NULL DEFAULT NULL,
    password_reset_token VARCHAR(64) DEFAULT NULL,
    password_reset_expires TIMESTAMP NULL DEFAULT NULL,
    new_email VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Indexen voor performance
CREATE INDEX IF NOT EXISTS idx_horoscopes_user_id ON horoscopes(user_id);
CREATE INDEX IF NOT EXISTS idx_horoscopes_created_at ON horoscopes(created_at);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE UNIQUE INDEX IF NOT EXISTS idx_horoscopes_slug ON horoscopes(slug);
CREATE INDEX IF NOT EXISTS idx_users_verification_token ON users(verification_token);
CREATE INDEX IF NOT EXISTS idx_users_password_reset_token ON users(password_reset_token);