-- Répare les ENUM corrompus (accents perdus en import Windows/latin1) pour
-- rendez_vous.statut et ordonnances.status — à exécuter une fois si besoin.

SET NAMES utf8mb4;

ALTER TABLE rendez_vous MODIFY COLUMN statut VARCHAR(32) NOT NULL DEFAULT 'en_attente';
UPDATE rendez_vous SET statut = 'confirmé' WHERE statut IN ('confirm?', 'confirme', 'confirm');
UPDATE rendez_vous SET statut = 'terminé' WHERE statut IN ('termin?', 'termine', 'termin');
UPDATE rendez_vous SET statut = 'annulé' WHERE statut IN ('annul?', 'annule', 'annul');
ALTER TABLE rendez_vous MODIFY COLUMN statut ENUM('en_attente','confirmé','annulé','terminé') NOT NULL DEFAULT 'en_attente';

ALTER TABLE ordonnances MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active';
UPDATE ordonnances SET status = 'expirée' WHERE status IN ('expir?e', 'expiree', 'expire');
UPDATE ordonnances SET status = 'annulée' WHERE status IN ('annul?e', 'annulee', 'annule');
ALTER TABLE ordonnances MODIFY COLUMN status ENUM('active','expirée','annulée') NOT NULL DEFAULT 'active';
