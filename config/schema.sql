-- =============================================
-- Schéma de la base de données : gestion_evenements
-- =============================================

CREATE DATABASE IF NOT EXISTS gestion_evenements
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE gestion_evenements;

-- -----------------------------------------------
-- Table : sponsor
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS sponsor (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(100)    NOT NULL,
    email       VARCHAR(150)    NOT NULL UNIQUE,
    telephone   VARCHAR(20)     NOT NULL,
    site_web    VARCHAR(255)    DEFAULT NULL,
    niveau      ENUM('bronze','argent','or','platine') NOT NULL DEFAULT 'bronze',
    montant     DECIMAL(10,2)   NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------
-- Table : evenement (domaine médical)
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS evenement (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    titre           VARCHAR(200)    NOT NULL,
    description     TEXT            NOT NULL,
    specialite      VARCHAR(100)    NOT NULL,
    lieu            VARCHAR(200)    NOT NULL,
    date_debut      DATE            NOT NULL,
    date_fin        DATE            NOT NULL,
    capacite        INT UNSIGNED    NOT NULL,
    prix            DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    statut          ENUM('planifie','en_cours','termine','annule') NOT NULL DEFAULT 'planifie',
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------
-- Table : evenement_sponsor (relation many-to-many)
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS evenement_sponsor (
    evenement_id    INT UNSIGNED    NOT NULL,
    sponsor_id      INT UNSIGNED    NOT NULL,
    PRIMARY KEY (evenement_id, sponsor_id),
    CONSTRAINT fk_evenement_sponsor_evenement
        FOREIGN KEY (evenement_id) REFERENCES evenement(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_evenement_sponsor_sponsor
        FOREIGN KEY (sponsor_id) REFERENCES sponsor(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------
-- Table : participation
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS participation (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    nom             VARCHAR(100)    NOT NULL,
    prenom          VARCHAR(100)    NOT NULL,
    email           VARCHAR(150)    NOT NULL,
    telephone       VARCHAR(20)     NOT NULL,
    profession      VARCHAR(100)    NOT NULL,
    evenement_id    INT UNSIGNED    NOT NULL,
    statut          ENUM('en_attente','confirme','annule') NOT NULL DEFAULT 'en_attente',
    date_inscription DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_participation_evenement
        FOREIGN KEY (evenement_id) REFERENCES evenement(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT uq_participation UNIQUE (email, evenement_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------
-- Données de test
-- -----------------------------------------------
INSERT INTO sponsor (nom, email, telephone, site_web, niveau, montant) VALUES
('PharmaCorp', 'contact@pharmacorp.com', '71234567', 'https://pharmacorp.com', 'or', 5000.00),
('MedTech Solutions', 'info@medtech.tn', '72345678', 'https://medtech.tn', 'platine', 12000.00),
('BioLab Tunisie', 'biolabtn@gmail.com', '73456789', NULL, 'argent', 2500.00);

-- Dates volontairement dans le futur : la liste front-office n’affiche que les événements
-- non terminés (date_fin >= aujourd’hui) au statut « planifie ».
INSERT INTO evenement (titre, description, specialite, lieu, date_debut, date_fin, capacite, prix, statut) VALUES
('Congrès National de Cardiologie', 'Congrès annuel réunissant les cardiologues tunisiens pour partager les dernières avancées.', 'Cardiologie', 'Hôtel El Mouradi, Tunis', '2027-09-10', '2027-09-12', 200, 150.00, 'planifie'),
('Journée de la Dermatologie', 'Journée scientifique dédiée aux nouvelles thérapies dermatologiques.', 'Dermatologie', 'Faculté de Médecine de Sfax', '2027-07-20', '2027-07-21', 100, 50.00, 'planifie'),
('Symposium Oncologie Pédiatrique', 'Symposium sur les protocoles de traitement du cancer chez l\'enfant.', 'Oncologie', 'Hôpital d\'Enfants, Tunis', '2027-10-05', '2027-10-06', 150, 0.00, 'planifie');

INSERT INTO evenement_sponsor (evenement_id, sponsor_id) VALUES
(1, 2), -- Congrès National de Cardiologie sponsorisé par MedTech Solutions
(2, 1); -- Journée de la Dermatologie sponsorisée par PharmaCorp

-- Migration des anciens sponsors (si sponsor_id existe)
-- INSERT INTO evenement_sponsor (evenement_id, sponsor_id)
-- SELECT id, sponsor_id FROM evenement WHERE sponsor_id IS NOT NULL;

INSERT INTO participation (nom, prenom, email, telephone, profession, evenement_id, statut) VALUES
('Ben Ali', 'Ahmed', 'ahmed.benali@email.com', '20123456', 'Médecin cardiologue', 1, 'confirme'),
('Trabelsi', 'Sarra', 'sarra.trabelsi@email.com', '21234567', 'Interne', 1, 'en_attente'),
('Mansour', 'Khaled', 'khaled.mansour@email.com', '22345678', 'Dermatologue', 2, 'confirme');
