-- Données de test pour la page admin ?page=logs
-- À exécuter dans phpMyAdmin sur la base `doctime_db` (ou la vôtre).
-- La table `logs` doit exister (voir database/logs_table.sql ou migration auto).

SET @admin_id := (SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1);
SET @patient_id := (SELECT id FROM users WHERE role = 'patient' ORDER BY id ASC LIMIT 1);
SET @medecin_id := (SELECT id FROM users WHERE role = 'medecin' ORDER BY id ASC LIMIT 1);

INSERT INTO logs (user_id, action, description, ip_address, created_at) VALUES
(@admin_id, 'Connexion', 'Connexion réussie au panneau d''administration.', '192.168.1.10', DATE_SUB(NOW(), INTERVAL 6 DAY) + INTERVAL 9 HOUR),
(@admin_id, 'Vue liste', 'Consultation de la page utilisateurs.', '192.168.1.10', DATE_SUB(NOW(), INTERVAL 5 DAY) + INTERVAL 14 HOUR),
(NULL, 'Système', 'Rotation des fichiers de log applicatif (simulation).', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 5 DAY) + INTERVAL 23 HOUR),
(@patient_id, 'Connexion', 'Connexion patient depuis l''espace public.', '41.225.11.88', DATE_SUB(NOW(), INTERVAL 4 DAY) + INTERVAL 11 HOUR),
(@medecin_id, 'update_profil', 'Mise à jour des informations du cabinet.', '196.203.45.12', DATE_SUB(NOW(), INTERVAL 4 DAY) + INTERVAL 16 HOUR),
(@admin_id, 'create_article', 'Création d''un brouillon d''article blog.', '192.168.1.10', DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 10 HOUR),
(@admin_id, 'delete_commentaire', 'Suppression d''un commentaire signalé.', '192.168.1.10', DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 15 HOUR),
(NULL, 'export_csv', 'Export CSV planifié des rendez-vous (tâche interne).', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 8 HOUR),
(@patient_id, 'prise_rdv', 'Prise de rendez-vous en ligne.', '41.225.11.88', DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 13 HOUR),
(@admin_id, 'Connexion', 'Nouvelle session admin (2FA validée).', '192.168.1.10', DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 9 HOUR),
(@medecin_id, 'Connexion', 'Connexion espace médecin.', '196.203.45.12', DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 17 HOUR),
(@admin_id, 'modif_parametres', 'Modification des paramètres généraux du site.', '192.168.1.10', NOW() - INTERVAL 3 HOUR),
(@patient_id, 'Connexion', 'Connexion rapide depuis mobile.', '41.225.11.88', NOW() - INTERVAL 1 HOUR);
