-- Exécuter une fois sur la base existante (erreurs « duplicate column » = déjà appliqué).
-- Réinitialisation mot de passe + OAuth.

ALTER TABLE users
    ADD COLUMN reset_token VARCHAR(64) NULL DEFAULT NULL,
    ADD COLUMN reset_expires DATETIME NULL DEFAULT NULL;

ALTER TABLE users
    ADD COLUMN social_provider VARCHAR(32) NULL DEFAULT NULL,
    ADD COLUMN social_provider_id VARCHAR(128) NULL DEFAULT NULL,
    ADD COLUMN social_avatar VARCHAR(512) NULL DEFAULT NULL;
