-- Migration: Ajouter colonne statut_validation et commentaire_validation à la table medecins
-- Date: 2026-04-09

ALTER TABLE medecins 
ADD COLUMN statut_validation ENUM('en_attente', 'valide', 'refusé') DEFAULT 'en_attente' AFTER actif,
ADD COLUMN commentaire_validation TEXT NULL AFTER statut_validation;

-- Index pour la recherche par statut de validation
CREATE INDEX idx_statut_validation ON medecins(statut_validation);
// update
