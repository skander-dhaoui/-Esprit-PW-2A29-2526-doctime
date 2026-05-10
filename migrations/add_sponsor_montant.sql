-- Migration pour ajouter la colonne montant à la table sponsors
-- Date: 2026-05-08

-- Ajouter la colonne montant à la table sponsors
ALTER TABLE sponsors ADD COLUMN montant DECIMAL(10,2) DEFAULT 0 AFTER niveau;

-- Ajouter un index sur la colonne montant pour optimiser les performances
ALTER TABLE sponsors ADD INDEX idx_montant (montant);

-- Mettre à jour les sponsors existants avec des montants par défaut selon leur niveau
UPDATE sponsors SET montant = 10000.00 WHERE niveau = 'platinium';
UPDATE sponsors SET montant = 7500.00 WHERE niveau = 'gold';
UPDATE sponsors SET montant = 5000.00 WHERE niveau = 'silver';
UPDATE sponsors SET montant = 2500.00 WHERE niveau = 'bronze';
