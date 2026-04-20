-- =============================================
-- AJOUTER DES PRODUITS PARAPHARMACIE AVEC IMAGES
-- =============================================

USE doctime_db;

-- Insérer des catégories supplémentaires
INSERT IGNORE INTO categories (nom, slug, description, created_at, updated_at) VALUES
('Hygiène', 'hygiene', 'Produits de hygiène personnelle', NOW(), NOW()),
('Beauté', 'beaute', 'Produits de beauté et soins de la peau', NOW(), NOW()),
('Bien-être', 'bien-etre', 'Produits pour le bien-être général', NOW(), NOW()),
('Maquillage', 'maquillage', 'Produits de maquillage', NOW(), NOW()),
('Cheveux', 'cheveux', 'Produits pour les cheveux', NOW(), NOW());

-- Insérer les produits parapharmacie avec images
INSERT IGNORE INTO produits (nom, slug, description, categorie_id, prix, stock, image, prescription, status, created_at, updated_at) VALUES

-- Antibiotiques et Antiinflammatoires
('Amoxicilline 500mg', 'amoxicilline-500mg', 'Antibiotique à large spectre - 20 gélules', (SELECT id FROM categories WHERE slug='medicaments'), 8.50, 50, 'https://images.unsplash.com/photo-1587854692152-cbe660dbde0f?w=300&h=300&fit=crop', 1, 'actif', NOW(), NOW()),
('Paracétamol 500mg', 'paracetamol-500mg', 'Analgésique et antipyrétique - 30 comprimés', (SELECT id FROM categories WHERE slug='medicaments'), 3.99, 100, 'https://images.unsplash.com/photo-1557821552-17105176677c?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),
('Ibuprofène 200mg', 'ibuprofen-200mg', 'Anti-inflammatoire non stéroïdien - 20 comprimés', (SELECT id FROM categories WHERE slug='medicaments'), 4.50, 75, 'https://images.unsplash.com/photo-1552062407-2b8f62a3157d?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),
('Loratadine 10mg', 'loratadine-10mg', 'Antihistaminique pour les allergies - 30 comprimés', (SELECT id FROM categories WHERE slug='medicaments'), 6.99, 45, 'https://images.unsplash.com/photo-1610259139161-eba63ac86d47?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),
('Vitamine C 1000mg', 'vitamine-c-1000mg', 'Vitamine C pour l''immunité - 30 comprimés', (SELECT id FROM categories WHERE slug='bien-etre'), 9.99, 60, 'https://images.unsplash.com/photo-1631717816269-0800a6a59c5e?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),

-- Hygiène
('Gel douche Bodix', 'gel-douche-bodix', 'Gel douche doux hypoallergénique - 500ml', (SELECT id FROM categories WHERE slug='hygiene'), 5.99, 80, 'https://images.unsplash.com/photo-1556231439-02f5fb39dd63?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),
('Savon liquide main', 'savon-liquide-main', 'Savon antibactérien - 250ml', (SELECT id FROM categories WHERE slug='hygiene'), 2.99, 120, 'https://images.unsplash.com/photo-1600857065874-30f5b926e5ea?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),
('Déodorant spray 24h', 'deodorant-spray-24h', 'Protection 24 heures - 150ml', (SELECT id FROM categories WHERE slug='hygiene'), 3.50, 95, 'https://images.unsplash.com/photo-1600857062241-98e5dba7214c?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),
('Brossette dentaire électrique', 'brossette-dentaire-electrique', 'Tête de brosse compatible Oral-B - 2 pcs', (SELECT id FROM categories WHERE slug='hygiene'), 7.99, 40, 'https://images.unsplash.com/photo-1610914957529-188f219880a0?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),
('Dentifrice blancheur', 'dentifrice-blancheur', 'Dentifrice blanchissant - 100ml', (SELECT id FROM categories WHERE slug='hygiene'), 4.50, 110, 'https://images.unsplash.com/photo-1608571423902-eed4a5ad8108?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),

-- Beauté et Soins
('Crème hydratante visage', 'creme-hydratante-visage', 'Hydratation profonde sans paraben - 50ml', (SELECT id FROM categories WHERE slug='beaute'), 12.99, 55, 'https://images.unsplash.com/photo-1556228578-8c89e6adf883?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),
('Sérum anti-âge', 'serum-anti-age', 'Sérum concentré à la vitamine C - 30ml', (SELECT id FROM categories WHERE slug='beaute'), 15.99, 35, 'https://images.unsplash.com/photo-1556228578-8c89e6adf883?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),
('Masque facial purifiant', 'masque-facial-purifiant', 'Masque à l''argile - 75ml', (SELECT id FROM categories WHERE slug='beaute'), 8.50, 65, 'https://images.unsplash.com/photo-1556228578-8c89e6adf883?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),
('Huile démaquillante', 'huile-demaquillante', 'Huile nettoyante micellaire - 200ml', (SELECT id FROM categories WHERE slug='beaute'), 9.99, 70, 'https://images.unsplash.com/photo-1556228578-8c89e6adf883?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),
('BB Crème teinte universelle', 'bb-creme-teinte', 'BB Crème SPF30 - 30ml', (SELECT id FROM categories WHERE slug='beaute'), 11.50, 50, 'https://images.unsplash.com/photo-1556228578-8c89e6adf883?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),

-- Maquillage
('Rouge à lèvres mat', 'rouge-levres-mat', 'Nuance Bordeaux - Tenue longue durée', (SELECT id FROM categories WHERE slug='maquillage'), 7.99, 60, 'https://images.unsplash.com/photo-1512033016779-73ffbfe2e928?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),
('Fond de teint liquide', 'fond-teint-liquide', 'Coverage moyen à élevé - 30ml', (SELECT id FROM categories WHERE slug='maquillage'), 10.99, 48, 'https://images.unsplash.com/photo-1512033016779-73ffbfe2e928?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),
('Palette fards à paupières', 'palette-fards-paupieres', 'Palette 12 couleurs - Finition mate et scintillante', (SELECT id FROM categories WHERE slug='maquillage'), 13.99, 38, 'https://images.unsplash.com/photo-1512033016779-73ffbfe2e928?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),
('Mascara volume', 'mascara-volume', 'Mascara noir volumisant - 10ml', (SELECT id FROM categories WHERE slug='maquillage'), 6.50, 75, 'https://images.unsplash.com/photo-1512033016779-73ffbfe2e928?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),
('Poudre compact', 'poudre-compact', 'Poudre matifiante SPF15 - 9g', (SELECT id FROM categories WHERE slug='maquillage'), 8.99, 82, 'https://images.unsplash.com/photo-1512033016779-73ffbfe2e928?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),

-- Cheveux
('Shampoing fortifiant', 'shampoing-fortifiant', 'Shampoing anti-chute - 250ml', (SELECT id FROM categories WHERE slug='cheveux'), 6.99, 70, 'https://images.unsplash.com/photo-1556228014-8c1b11e29c37?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),
('Après-shampoing lissant', 'apres-shampoing-lissant', 'Après-shampoing lissurant à l''argan - 250ml', (SELECT id FROM categories WHERE slug='cheveux'), 7.50, 65, 'https://images.unsplash.com/photo-1556228014-8c1b11e29c37?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),
('Sérum pour cheveux', 'serum-cheveux', 'Sérum protecteur thermique - 50ml', (SELECT id FROM categories WHERE slug='cheveux'), 9.99, 55, 'https://images.unsplash.com/photo-1556228014-8c1b11e29c37?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),
('Coloration cheveux naturelle', 'coloration-cheveux', 'Coloration permanent châtain - 1 kit', (SELECT id FROM categories WHERE slug='cheveux'), 8.50, 40, 'https://images.unsplash.com/photo-1556228014-8c1b11e29c37?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),
('Masque cheveux nourrissant', 'masque-cheveux', 'Masque réparant cheveux secs - 200ml', (SELECT id FROM categories WHERE slug='cheveux'), 6.99, 72, 'https://images.unsplash.com/photo-1556228014-8c1b11e29c37?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),

-- Bien-être supplémentaire
('Spray nasal salin', 'spray-nasal-salin', 'Spray nasal décongestionant - 30ml', (SELECT id FROM categories WHERE slug='bien-etre'), 3.99, 100, 'https://images.unsplash.com/photo-1631717816269-0800a6a59c5e?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),
('Compléments magnésium', 'magnesium-complement', 'Magnésium pour la détente - 60 comprimés', (SELECT id FROM categories WHERE slug='bien-etre'), 7.99, 50, 'https://images.unsplash.com/photo-1631717816269-0800a6a59c5e?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),
('Tisane relaxante', 'tisane-relaxante', 'Tisane camomille-miel - 20 sachets', (SELECT id FROM categories WHERE slug='bien-etre'), 4.50, 85, 'https://images.unsplash.com/photo-1631717816269-0800a6a59c5e?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),
('Huile de ricin pur', 'huile-ricin-pur', 'Huile de ricin bio - 100ml', (SELECT id FROM categories WHERE slug='bien-etre'), 5.99, 60, 'https://images.unsplash.com/photo-1631717816269-0800a6a59c5e?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW()),
('Probiotiques naturels', 'probiotiques-naturels', 'Compléments probiotiques - 30 gélules', (SELECT id FROM categories WHERE slug='bien-etre'), 12.99, 35, 'https://images.unsplash.com/photo-1631717816269-0800a6a59c5e?w=300&h=300&fit=crop', 0, 'actif', NOW(), NOW());
