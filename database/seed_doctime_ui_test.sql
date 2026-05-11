-- =====================================================================
-- Données de test UI DocTime (événements à venir, sponsors avec contacts)
-- Exécution : mysql -u root -p doctime_db < database/seed_doctime_ui_test.sql
-- Les colonnes categorie, sponsor_id, email, telephone sont ajoutées par
-- la page Événements/Sponsors (ensureDocTimeSchema) ou via doctime_full.sql.
-- =====================================================================
SET NAMES utf8mb4;

USE doctime_db;

-- Corriger noms / contacts sponsors (remplace les « ? » si encodage incorrect)
UPDATE sponsors SET nom = 'SantéPlus TN', email = IFNULL(NULLIF(TRIM(email), ''), 'contact@santeplus.tn'), telephone = IFNULL(NULLIF(TRIM(telephone), ''), '+216 71 234 001')
WHERE site_web LIKE '%santeplus%' OR nom LIKE '%Sant%Plus%' OR nom LIKE 'Sant%Plus TN';

UPDATE sponsors SET email = IFNULL(NULLIF(TRIM(email), ''), 'labo@labobio.tn'), telephone = IFNULL(NULLIF(TRIM(telephone), ''), '+216 72 234 002') WHERE nom = 'LaboBio';
UPDATE sponsors SET email = IFNULL(NULLIF(TRIM(email), ''), 'info@medequip.tn'), telephone = IFNULL(NULLIF(TRIM(telephone), ''), '+216 73 234 003') WHERE nom = 'MedEquip';
UPDATE sponsors SET email = IFNULL(NULLIF(TRIM(email), ''), 'pharma@pharmasud.tn'), telephone = IFNULL(NULLIF(TRIM(telephone), ''), '+216 74 234 004') WHERE nom = 'PharmaSud';
UPDATE sponsors SET email = IFNULL(NULLIF(TRIM(email), ''), 'hello@cardiopro.tn'), telephone = IFNULL(NULLIF(TRIM(telephone), ''), '+216 75 555 010') WHERE nom = 'CardioPro';
UPDATE sponsors SET email = IFNULL(NULLIF(TRIM(email), ''), 'bonjour@nutrilife.tn'), telephone = IFNULL(NULLIF(TRIM(telephone), ''), '+216 76 234 006') WHERE nom = 'NutriLife';

INSERT IGNORE INTO sponsors (nom, email, telephone, site_web, description, niveau, actif) VALUES
('MedTech Solutions TN', 'contact@medtech.tn', '+216 70 111 222', 'https://medtech.tn', 'Équipement et solutions médicales', 'platinium', 1);

INSERT IGNORE INTO events (titre, slug, description, contenu, date_debut, date_fin, lieu, categorie, sponsor_id, capacite_max, places_restantes, image, prix, status) VALUES
(
    'Journée de la Dermatologie',
    'doctime-ui-journee-dermatologie',
    'Ateliers cliniques et conférences sur les pathologies dermatologiques courantes.',
    '<p>Programme : matinée conférences, après-midi ateliers.</p>',
    DATE_ADD(NOW(), INTERVAL 40 DAY),
    DATE_ADD(NOW(), INTERVAL 41 DAY),
    'Faculté de Médecine de Sfax',
    'Dermatologie',
    (SELECT id FROM sponsors WHERE nom = 'PharmaSud' LIMIT 1),
    100, 100,
    'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=800&q=80',
    50.00,
    'à venir'
),
(
    'Congrès National de Cardiologie',
    'doctime-ui-congres-cardiologie',
    'Symposium : prévention, imagerie cardiaque et syndromes coronariens.',
    '<p>Deux jours de sessions plénières et posters.</p>',
    DATE_ADD(NOW(), INTERVAL 55 DAY),
    DATE_ADD(NOW(), INTERVAL 56 DAY),
    'Centre des congrès, Tunis',
    'Cardiologie',
    (SELECT id FROM sponsors WHERE nom = 'CardioPro' LIMIT 1),
    250, 240,
    'https://images.unsplash.com/photo-1628348068341-c0527edaf731?w=800&q=80',
    120.00,
    'à venir'
),
(
    'Webinaire — Médecine du sommeil',
    'doctime-ui-webinaire-sommeil',
    'Introduction aux troubles du sommeil en médecine générale. Gratuit, en ligne.',
    '<p>Lien envoyé après inscription.</p>',
    DATE_ADD(NOW(), INTERVAL 15 DAY),
    DATE_ADD(NOW(), INTERVAL 15 DAY),
    'En ligne',
    'Médecine du sommeil',
    (SELECT id FROM sponsors WHERE nom = 'NutriLife' LIMIT 1),
    500, 480,
    NULL,
    0,
    'à venir'
);

SET @demo_user := (SELECT id FROM users WHERE role IN ('patient', 'admin') ORDER BY id ASC LIMIT 1);
SET @demo_user := IFNULL(@demo_user, (SELECT id FROM users ORDER BY id ASC LIMIT 1));

INSERT IGNORE INTO participations (event_id, user_id, statut)
SELECT e.id, @demo_user, 'inscrit' FROM events e WHERE e.slug = 'doctime-ui-journee-dermatologie' LIMIT 1;

INSERT IGNORE INTO participations (event_id, user_id, statut)
SELECT e.id, @demo_user, 'inscrit' FROM events e WHERE e.slug = 'doctime-ui-congres-cardiologie' LIMIT 1;
