-- Compte utilisateur demandé : afnengorai@gmail.com / Afnen123
-- Exécuter dans phpMyAdmin ou : mysql -u root doctime_db < database/seed_user_afnengorai.sql

USE doctime_db;

SET NAMES utf8mb4;

-- Hash bcrypt pour le mot de passe : Afnen123 (régénéré via PHP password_hash)
INSERT INTO users (nom, prenom, email, password, telephone, role, statut, genre)
VALUES (
    'Gorai',
    'Afnen',
    'afnengorai@gmail.com',
    '$2y$10$1f76WJihq7N.oD5SIXrK3.SgGehGfqn1b7mCKjSnueeagpBdMf1C.',
    '',
    'patient',
    'actif',
    'M'
)
ON DUPLICATE KEY UPDATE
    password = VALUES(password),
    nom = VALUES(nom),
    prenom = VALUES(prenom),
    statut = 'actif',
    role = IF(role = 'admin', role, 'patient');

-- Ligne patient si absente (pour les RDV / profil patient)
INSERT IGNORE INTO patients (user_id, groupe_sanguin)
SELECT id, NULL FROM users WHERE email = 'afnengorai@gmail.com' LIMIT 1;
