-- Colonnes requises pour la reconnaissance faciale (connexion + enregistrement depuis le profil).
-- À exécuter une fois sur doctime_db si vous avez l'erreur : Unknown column 'face_photo'

USE doctime_db;

ALTER TABLE users
  ADD COLUMN face_photo VARCHAR(255) NULL DEFAULT NULL COMMENT 'Chemin uploads/faces/…' AFTER avatar,
  ADD COLUMN face_encoding VARCHAR(512) NULL DEFAULT NULL COMMENT 'Même chemin ou référence visage' AFTER face_photo,
  ADD COLUMN face_descriptor TEXT NULL DEFAULT NULL COMMENT 'Descripteur / métadonnées visage' AFTER face_encoding;
