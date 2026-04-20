-- =============================================
-- ACTIVER TOUS LES PRODUITS
-- =============================================

USE doctime_db;

-- Mettre à jour le statut de tous les produits à 'actif'
UPDATE produits SET status = 'actif' WHERE status IS NULL OR status = 'inactif';

-- Vérifier les résultats
SELECT COUNT(*) as total, 
       SUM(CASE WHEN status = 'actif' THEN 1 ELSE 0 END) as actifs,
       SUM(CASE WHEN status = 'inactif' THEN 1 ELSE 0 END) as inactifs,
       SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as rupture
FROM produits;
