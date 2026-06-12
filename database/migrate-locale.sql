-- Migration: Add locale column to users table
-- Date: 2026-05-29
-- Description: Adds locale support for internationalization (nl_NL, en_GB, en_US)

ALTER TABLE users ADD COLUMN locale VARCHAR(10) DEFAULT 'nl_NL' AFTER created_at;

-- Existing users will get default 'nl_NL'
