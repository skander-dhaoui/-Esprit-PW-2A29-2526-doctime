-- Exemples catalogue « soins capillaires » (exécuter une fois si absents).
-- Compatible schéma doctime_full.sql (categories sans statut, produits.status).

INSERT INTO categories (nom, slug, description, parent_id, created_at, updated_at)
SELECT 'Soins capillaires', 'soins-capillaires', 'Shampoings, après-shampoings et soins pour cheveux', NULL, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug = 'soins-capillaires');

INSERT INTO produits (nom, slug, description, prix, stock, categorie_id, prescription, status, created_at, updated_at)
SELECT 'Shampoing usage fréquent cheveux normaux', 'shampoing-usage-frequent',
       'Nettoyage doux au pH neutre pour cheveux et cuir chevelu. Flacon 250 ml.', 18.90, 40,
       (SELECT id FROM categories WHERE slug = 'soins-capillaires' LIMIT 1), 0, 'actif', NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM produits WHERE slug = 'shampoing-usage-frequent');

INSERT INTO produits (nom, slug, description, prix, stock, categorie_id, prescription, status, created_at, updated_at)
SELECT 'Après-shampoing démêlant cheveux secs', 'apres-shampoing-demelant',
       'Soin capillaire nourrissant et démêlant, sans rinçage abusif. 200 ml.', 22.50, 35,
       (SELECT id FROM categories WHERE slug = 'soins-capillaires' LIMIT 1), 0, 'actif', NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM produits WHERE slug = 'apres-shampoing-demelant');

INSERT INTO produits (nom, slug, description, prix, stock, categorie_id, prescription, status, created_at, updated_at)
SELECT 'Huile capillaire réparateur pointes', 'huile-capillaire-pointes',
       'Soin capillaire concentré pour pointes abîmées et cheveux ternes. 50 ml.', 29.00, 25,
       (SELECT id FROM categories WHERE slug = 'soins-capillaires' LIMIT 1), 0, 'actif', NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM produits WHERE slug = 'huile-capillaire-pointes');
