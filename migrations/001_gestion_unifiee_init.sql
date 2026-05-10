-- Migration: Gestion Unifiée (User + Event + Pharmacie)
-- Créé: 2026-05-10

-- Table de liaison entre Participations et Produits
CREATE TABLE IF NOT EXISTS participation_produits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    participation_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantity INT DEFAULT 1,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (participation_id) REFERENCES participations(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE,
    UNIQUE KEY unique_participation_produit (participation_id, produit_id),
    INDEX idx_participation_id (participation_id),
    INDEX idx_produit_id (produit_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajouter des colonnes de liaison si nécessaire
ALTER TABLE events ADD COLUMN IF NOT EXISTS gestion_unifiee_id INT DEFAULT NULL;
ALTER TABLE participations ADD COLUMN IF NOT EXISTS gestion_unifiee_metadata JSON DEFAULT NULL;

-- Table de gestion globale
CREATE TABLE IF NOT EXISTS gestion_unifiee_metadata (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    related_entities JSON,
    metadata JSON,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vue: Participations avec Produits
CREATE OR REPLACE VIEW vw_participations_complete AS
SELECT 
    p.id AS participation_id,
    u.id AS user_id,
    u.nom AS user_nom,
    u.prenom AS user_prenom,
    u.email AS user_email,
    e.id AS event_id,
    e.titre AS event_titre,
    e.date_debut AS event_date_debut,
    e.lieu AS event_lieu,
    p.date_participation,
    p.statut,
    COUNT(DISTINCT pr.id) AS nombre_produits,
    GROUP_CONCAT(DISTINCT pr.nom SEPARATOR ', ') AS produits_noms,
    SUM(pr.prix * COALESCE(pp.quantity, 1)) AS produits_total
FROM participations p
LEFT JOIN users u ON p.user_id = u.id
LEFT JOIN events e ON p.event_id = e.id
LEFT JOIN participation_produits pp ON p.id = pp.participation_id
LEFT JOIN produits pr ON pp.produit_id = pr.id
GROUP BY p.id;

-- Vue: Événements avec Statistiques
CREATE OR REPLACE VIEW vw_events_statistics AS
SELECT 
    e.id,
    e.titre,
    e.date_debut,
    e.date_fin,
    e.lieu,
    e.statut,
    COUNT(DISTINCT p.id) AS nombre_participants,
    COUNT(DISTINCT pr.id) AS nombre_produits_distribues,
    SUM(pr.prix * COALESCE(pp.quantity, 1)) AS valeur_produits_distribues,
    MAX(p.date_participation) AS derniere_participation
FROM events e
LEFT JOIN participations p ON e.id = p.event_id
LEFT JOIN participation_produits pp ON p.id = pp.participation_id
LEFT JOIN produits pr ON pp.produit_id = pr.id
GROUP BY e.id;
