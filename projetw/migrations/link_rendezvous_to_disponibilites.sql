-- Migration : Ajouter la jointure entre rendez_vous et disponibilites
-- Ajoute une colonne disponibilite_id à rendez_vous avec FK

ALTER TABLE rendez_vous 
ADD COLUMN disponibilite_id INT NULL AFTER medecin_id,
ADD FOREIGN KEY (disponibilite_id) REFERENCES disponibilites(id) ON DELETE SET NULL;

-- Index pour améliorer les requêtes
ALTER TABLE rendez_vous 
ADD INDEX idx_disponibilite (disponibilite_id);
