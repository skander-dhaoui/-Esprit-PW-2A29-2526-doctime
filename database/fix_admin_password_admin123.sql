-- Réinitialise le mot de passe de admin@doctime.com à admin123
-- Adapter USE nom_base_si_différent;
USE doctime_db;

UPDATE users
SET password = '$2y$10$l41tKGrgvz/B4.b8vK0fLe3mGJEUfA8Kp2B3LYtcD3zfYoTf00IOS'
WHERE email = 'admin@doctime.com';
