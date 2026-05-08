-- =============================================
-- DONNÉES DE TEST POUR PARTICIPATIONS
-- =============================================
USE doctime_db;

-- Insertion de participations de test
INSERT INTO participations (event_id, user_id, statut, date_inscription, code_qr) VALUES
(1, 2, 'inscrit', '2024-01-15 10:30:00', 'QR123456789'),
(1, 4, 'présent', '2024-01-16 14:20:00', 'QR987654321'),
(2, 2, 'inscrit', '2024-01-20 09:15:00', 'QR456789123'),
(2, 4, 'absent', '2024-01-21 11:45:00', 'QR789123456'),
(3, 2, 'inscrit', '2024-01-25 16:30:00', 'QR321654987'),
(3, 4, 'présent', '2024-01-26 13:20:00', 'QR654987321');

-- Vérification des données insérées
SELECT
    p.id,
    u.nom,
    u.prenom,
    u.email,
    e.titre AS evenement_titre,
    p.statut,
    p.date_inscription,
    p.code_qr
FROM participations p
JOIN users u ON p.user_id = u.id
JOIN events e ON p.event_id = e.id
ORDER BY p.date_inscription DESC;