-- =============================================
-- CORRIGER LES IMAGES DES PRODUITS
-- =============================================

USE doctime_db;

-- Mettre à jour les images avec des URLs spécifiques pour chaque produit

UPDATE produits SET image = 'https://images.unsplash.com/photo-1587854692152-cbe660dbde0f?w=300&h=300&fit=crop' WHERE slug = 'amoxicilline-500mg';
UPDATE produits SET image = 'https://images.unsplash.com/photo-1557821552-17105176677c?w=300&h=300&fit=crop' WHERE slug = 'paracetamol-500mg';
UPDATE produits SET image = 'https://images.unsplash.com/photo-1552062407-2b8f62a3157d?w=300&h=300&fit=crop' WHERE slug = 'ibuprofen-200mg';
UPDATE produits SET image = 'https://images.unsplash.com/photo-1610259139161-eba63ac86d47?w=300&h=300&fit=crop' WHERE slug = 'loratadine-10mg';
UPDATE produits SET image = 'https://images.unsplash.com/photo-1631717816269-0800a6a59c5e?w=300&h=300&fit=crop' WHERE slug = 'vitamine-c-1000mg';

-- Hygiène
UPDATE produits SET image = 'https://images.unsplash.com/photo-1556231439-02f5fb39dd63?w=300&h=300&fit=crop' WHERE slug = 'gel-douche-bodix';
UPDATE produits SET image = 'https://images.unsplash.com/photo-1600857065874-30f5b926e5ea?w=300&h=300&fit=crop' WHERE slug = 'savon-liquide-main';
UPDATE produits SET image = 'https://images.unsplash.com/photo-1600857062241-98e5dba7214c?w=300&h=300&fit=crop' WHERE slug = 'deodorant-spray-24h';
UPDATE produits SET image = 'https://images.unsplash.com/photo-1610914957529-188f219880a0?w=300&h=300&fit=crop' WHERE slug = 'brossette-dentaire-electrique';
UPDATE produits SET image = 'https://images.unsplash.com/photo-1608571423902-eed4a5ad8108?w=300&h=300&fit=crop' WHERE slug = 'dentifrice-blancheur';

-- Beauté et Soins
UPDATE produits SET image = 'https://images.unsplash.com/photo-1556228578-8c89e6adf883?w=300&h=300&fit=crop' WHERE slug = 'creme-hydratante-visage';
UPDATE produits SET image = 'https://images.unsplash.com/photo-1611083326278-29070daafc2e?w=300&h=300&fit=crop' WHERE slug = 'serum-anti-age';
UPDATE produits SET image = 'https://images.unsplash.com/photo-1556228578-8c89e6adf883?w=300&h=300&fit=crop' WHERE slug = 'masque-facial-purifiant';
UPDATE produits SET image = 'https://images.unsplash.com/photo-1596462502278-af8a5ee7f23e?w=300&h=300&fit=crop' WHERE slug = 'huile-demaquillante';
UPDATE produits SET image = 'https://images.unsplash.com/photo-1503215800140-dd63099e0a91?w=300&h=300&fit=crop' WHERE slug = 'bb-creme-teinte';

-- Maquillage
UPDATE produits SET image = 'https://images.unsplash.com/photo-1542602927-dba85f36457f?w=300&h=300&fit=crop' WHERE slug = 'rouge-levres-mat';
UPDATE produits SET image = 'https://images.unsplash.com/photo-1571875285973-fbf729ef9325?w=300&h=300&fit=crop' WHERE slug = 'fond-teint-liquide';
UPDATE produits SET image = 'https://images.unsplash.com/photo-1608571423902-eed4a5ad8108?w=300&h=300&fit=crop' WHERE slug = 'palette-fards-paupieres';
UPDATE produits SET image = 'https://images.unsplash.com/photo-1546050962-5e3a22a8e1fe?w=300&h=300&fit=crop' WHERE slug = 'mascara-volume';
UPDATE produits SET image = 'https://images.unsplash.com/photo-1599599810990-44b963d9c92f?w=300&h=300&fit=crop' WHERE slug = 'poudre-compact';

-- Cheveux
UPDATE produits SET image = 'https://images.unsplash.com/photo-1556228014-8c1b11e29c37?w=300&h=300&fit=crop' WHERE slug = 'shampoing-fortifiant';
UPDATE produits SET image = 'https://images.unsplash.com/photo-1582338980035-fcd9a30e0d1f?w=300&h=300&fit=crop' WHERE slug = 'apres-shampoing-lissant';
UPDATE produits SET image = 'https://images.unsplash.com/photo-1600228297444-aeb8c5566dc9?w=300&h=300&fit=crop' WHERE slug = 'serum-cheveux';
UPDATE produits SET image = 'https://images.unsplash.com/photo-1609007843269-f79c12f77976?w=300&h=300&fit=crop' WHERE slug = 'coloration-cheveux';
UPDATE produits SET image = 'https://images.unsplash.com/photo-1556228578-8c89e6adf883?w=300&h=300&fit=crop' WHERE slug = 'masque-cheveux';

-- Bien-être supplémentaire
UPDATE produits SET image = 'https://images.unsplash.com/photo-1631717816269-0800a6a59c5e?w=300&h=300&fit=crop' WHERE slug = 'spray-nasal-salin';
UPDATE produits SET image = 'https://images.unsplash.com/photo-1631717816269-0800a6a59c5e?w=300&h=300&fit=crop' WHERE slug = 'magnesium-complement';
UPDATE produits SET image = 'https://images.unsplash.com/photo-1597318972826-00a674c1869d?w=300&h=300&fit=crop' WHERE slug = 'tisane-relaxante';
UPDATE produits SET image = 'https://images.unsplash.com/photo-1612817288484-86e0769266da?w=300&h=300&fit=crop' WHERE slug = 'huile-ricin-pur';
UPDATE produits SET image = 'https://images.unsplash.com/photo-1631717816269-0800a6a59c5e?w=300&h=300&fit=crop' WHERE slug = 'probiotiques-naturels';

-- Vérifier les mises à jour
SELECT id, nom, slug, image FROM produits WHERE image IS NOT NULL OR image = '' LIMIT 30;
