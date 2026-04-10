-- Reset des donnees parapharmacie et insertion d'un catalogue de demo.
-- A coller dans phpMyAdmin.

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM commande_details;
DELETE FROM commandes;
DELETE FROM produits;
DELETE FROM categories;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO categories (id, nom, slug, description, image, parent_id, statut, created_at, updated_at) VALUES
(1, 'Visage', 'visage', 'Soins du visage, hydratation et nettoyage', 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=1200&q=80', NULL, 'actif', NOW(), NOW()),
(2, 'Cheveux', 'cheveux', 'Shampoings, soins et traitements capillaires', 'https://images.unsplash.com/photo-1522337660859-02fbefca4702?auto=format&fit=crop&w=1200&q=80', NULL, 'actif', NOW(), NOW()),
(3, 'Bebe', 'bebe', 'Produits doux pour bebes et enfants', 'https://images.unsplash.com/photo-1519689680058-324335c77eba?auto=format&fit=crop&w=1200&q=80', NULL, 'actif', NOW(), NOW()),
(4, 'Protection solaire', 'protection-solaire', 'Crèmes, sprays et soins anti-UV', 'https://images.unsplash.com/photo-1503341504253-dff4815485f1?auto=format&fit=crop&w=1200&q=80', NULL, 'actif', NOW(), NOW()),
(5, 'Digestion et bien-etre', 'digestion-bien-etre', 'Compléments pour digestion, stress et sommeil', 'https://images.unsplash.com/photo-1576678927484-cc907957088c?auto=format&fit=crop&w=1200&q=80', NULL, 'actif', NOW(), NOW()),
(6, 'Hygiène', 'hygiene', 'Hygiène quotidienne et soins corporels', 'https://images.unsplash.com/photo-1612817288484-6f916006741a?auto=format&fit=crop&w=1200&q=80', NULL, 'actif', NOW(), NOW());

INSERT INTO produits (nom, reference, description, categorie_id, prix_achat, prix_vente, tva, stock, stock_alerte, image, prescription, actif, created_at, updated_at) VALUES
('Crème Hydratante Peau Sèche', 'VIS-001', 'Crème riche pour peau sèche et sensible. Aide à restaurer le confort cutané.', 1, 18.000, 34.900, 19, 25, 5, 'https://images.unsplash.com/photo-1571781926291-c477ebfd024b?auto=format&fit=crop&w=1200&q=80', 0, 1, NOW(), NOW()),
('Gel Nettoyant Purifiant', 'VIS-002', 'Nettoyant visage pour peau grasse et mixte. Idéal pour routines anti-brillance.', 1, 12.500, 24.900, 19, 30, 6, 'https://images.unsplash.com/photo-1556228578-8c89e6adf883?auto=format&fit=crop&w=1200&q=80', 0, 1, NOW(), NOW()),
('Shampoing Réparateur Cheveux Secs', 'CHE-001', 'Shampoing nourrissant pour cheveux secs, abîmés et cassants.', 2, 15.000, 29.900, 19, 18, 4, 'https://images.unsplash.com/photo-1527799820374-dcf8d9d4a6b4?auto=format&fit=crop&w=1200&q=80', 0, 1, NOW(), NOW()),
('Sérum Capillaire Fortifiant', 'CHE-002', 'Sérum sans rinçage pour renforcer la fibre capillaire et réduire les frisottis.', 2, 22.000, 39.900, 19, 14, 3, 'https://images.unsplash.com/photo-1631730359585-38a4935cbec4?auto=format&fit=crop&w=1200&q=80', 0, 1, NOW(), NOW()),
('Lait de Toilette Bébé', 'BEB-001', 'Lait nettoyant doux pour peau de bébé, adapté au visage et au corps.', 3, 10.000, 19.900, 19, 35, 8, 'https://images.unsplash.com/photo-1512374382149-233c42b6a83b?auto=format&fit=crop&w=1200&q=80', 0, 1, NOW(), NOW()),
('Crème Solaire SPF50+', 'SOL-001', 'Protection solaire haute couvrance pour visage et corps.', 4, 21.000, 39.900, 19, 22, 5, 'https://images.unsplash.com/photo-1505842465776-3d89f3dc82da?auto=format&fit=crop&w=1200&q=80', 0, 1, NOW(), NOW()),
('Complément Digestion Confort', 'DIG-001', 'Complément alimentaire pour le confort digestif et le transit.', 5, 16.000, 31.900, 19, 20, 4, 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=1200&q=80', 0, 1, NOW(), NOW()),
('Gel Douche Corps & Cheveux', 'HYG-001', 'Gel douche 2-en-1 pour une routine d’hygiène quotidienne.', 6, 8.000, 16.900, 19, 40, 10, 'https://images.unsplash.com/photo-1584305574647-0cc49c04fece?auto=format&fit=crop&w=1200&q=80', 0, 1, NOW(), NOW()),
('Baume Réparateur Lèvres', 'VIS-003', 'Baume nourrissant pour lèvres sèches et irritées.', 1, 6.000, 12.900, 19, 50, 10, 'https://images.unsplash.com/photo-1596755389378-c31d21fd1273?auto=format&fit=crop&w=1200&q=80', 0, 1, NOW(), NOW()),
('Huile Nourrissante Cheveux', 'CHE-003', 'Huile légère pour cheveux ternes, secs ou bouclés.', 2, 17.000, 32.900, 19, 16, 4, 'https://images.unsplash.com/photo-1556228578-8c89e6adf883?auto=format&fit=crop&w=1200&q=80', 0, 1, NOW(), NOW());

-- Optionnel: promo de test dans le fichier JSON du projet.
-- PROMO10, PROMO15 et BIENVENUE20 sont aussi reconnus par défaut dans le code.
