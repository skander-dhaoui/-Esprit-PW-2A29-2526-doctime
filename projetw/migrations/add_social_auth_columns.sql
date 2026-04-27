ALTER TABLE users
    ADD COLUMN social_provider VARCHAR(50) NULL AFTER avatar,
    ADD COLUMN social_provider_id VARCHAR(191) NULL AFTER social_provider,
    ADD COLUMN social_avatar VARCHAR(255) NULL AFTER social_provider_id;

CREATE INDEX idx_users_social_provider
    ON users (social_provider, social_provider_id);
