-- Database: tijd
-- Maak database aan indien niet bestaand
CREATE DATABASE IF NOT EXISTS tijd CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE tijd;

-- Users tabel
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    remember_token VARCHAR(64) DEFAULT NULL,
    remember_expires TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Horoscopes tabel
CREATE TABLE IF NOT EXISTS horoscopes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(16) NOT NULL,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    birth_date DATE NOT NULL,
    birth_time TIME NOT NULL,
    location_name VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 7) NOT NULL,
    longitude DECIMAL(10, 7) NOT NULL,
    timezone_id VARCHAR(64) NOT NULL,
    utc_offset INT NOT NULL,
    offset_source VARCHAR(32),
    offset_label VARCHAR(64),
    formatted_address VARCHAR(255),
    house_system CHAR(1) DEFAULT 'K',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Indexen voor performance
CREATE INDEX IF NOT EXISTS idx_horoscopes_user_id ON horoscopes(user_id);
CREATE INDEX IF NOT EXISTS idx_horoscopes_created_at ON horoscopes(created_at);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE UNIQUE INDEX IF NOT EXISTS idx_horoscopes_slug ON horoscopes(slug);