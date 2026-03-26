-- Adds first-login password reset support to the users table.
-- Existing users remain unchanged; new student accounts are flagged in the application flow.

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active;

UPDATE users
SET must_change_password = 0
WHERE must_change_password IS NULL;
