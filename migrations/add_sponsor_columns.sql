-- Migration: Add missing columns to sponsors table
-- Date: 2026-04-19

-- Add missing columns if they don't exist
ALTER TABLE sponsors 
ADD COLUMN IF NOT EXISTS email VARCHAR(255),
ADD COLUMN IF NOT EXISTS telephone VARCHAR(20),
ADD COLUMN IF NOT EXISTS secteur VARCHAR(100),
ADD COLUMN IF NOT EXISTS budget DECIMAL(15,2),
ADD COLUMN IF NOT EXISTS statut ENUM('actif', 'inactif', 'archive') DEFAULT 'actif',
ADD COLUMN IF NOT EXISTS date_debut DATE,
ADD COLUMN IF NOT EXISTS date_fin DATE,
ADD COLUMN IF NOT EXISTS contact_nom VARCHAR(100),
ADD COLUMN IF NOT EXISTS contact_prenom VARCHAR(100),
ADD COLUMN IF NOT EXISTS contact_email VARCHAR(255),
ADD COLUMN IF NOT EXISTS contact_telephone VARCHAR(20),
ADD COLUMN IF NOT EXISTS notes TEXT;

-- Insert sample sponsors
INSERT INTO sponsors (nom, email, telephone, secteur, budget, description, logo, site_web, statut, created_at, updated_at) 
VALUES 
('BioLab Tunisie', 'biolabtn@gmail.com', '73456789', 'Argent', 5000, 'Laboratoire d\'analyses médicales', NULL, NULL, 'actif', NOW(), NOW()),
('Delice Holding', 'delicetunisie@gmail.com', '71454118', 'Platine', 10000, 'Groupe pharmaceutique tunisien', NULL, NULL, 'actif', NOW(), NOW()),
('MedTech Solutions', 'info@medtech.tn', '72345678', 'Platine', 8000, 'Solutions technologiques médicales', NULL, 'https://medtech.tn', 'actif', NOW(), NOW()),
('PharmaCorp', 'contact@pharmacorp.com', '71234567', 'Or', 3000, 'Distributeur de médicaments', NULL, 'https://pharmacorp.com', 'actif', NOW(), NOW()),
('Validation', 'validation@gmail.com', '73556773', 'Bronze', 1500, 'Cabinet de validation', NULL, NULL, 'actif', NOW(), NOW());
