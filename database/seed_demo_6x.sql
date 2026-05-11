-- =============================================
-- Jeu de données de TEST (≈6 entrées par domaine)
-- Mot de passe pour tous les comptes ci-dessous : doctime123
-- Hash bcrypt généré avec PHP password_hash('doctime123', PASSWORD_BCRYPT)
-- =============================================
USE doctime_db;

SET NAMES utf8mb4;

-- Mot de passe: doctime123
SET @pwd := '$2y$10$G8jQQXF.HvLyaK0EEerGc.4c4iyl2HHKnm/gc/NXqQW9CWigHXbZq';

-- ---------- 6 USERS (2 médecins + 4 patients) ----------
INSERT IGNORE INTO users (nom, prenom, email, password, telephone, role, statut, genre) VALUES
('Alaoui', 'Dr. Sami', 'dr.alaoui@demo.doctime', @pwd, '22123456', 'medecin', 'actif', 'M'),
('Ben Salah', 'Dr. Leila', 'dr.bensalah@demo.doctime', @pwd, '22123457', 'medecin', 'actif', 'F'),
('Trabelsi', 'Amine', 'patient1@demo.doctime', @pwd, '98111111', 'patient', 'actif', 'M'),
('Mansour', 'Sara', 'patient2@demo.doctime', @pwd, '98222222', 'patient', 'actif', 'F'),
('Jlassi', 'Karim', 'patient3@demo.doctime', @pwd, '98333333', 'patient', 'actif', 'M'),
('Gharbi', 'Nour', 'patient4@demo.doctime', @pwd, '98444444', 'patient', 'actif', 'F');

-- ---------- PROFILS MÉDECINS (2) ----------
INSERT IGNORE INTO medecins (user_id, specialite, numero_ordre, annee_experience, cabinet_ville, cabinet_adresse, consultation_prix, consultation_duree, actif, description)
SELECT u.id, 'Cardiologie', 'TN-MED-ORD-20001', 12, 'Tunis', 'Av. Habib Bourguiba, Tunis', 75.00, 30, 1, 'Cardiologue – consultations sur RDV.'
FROM users u WHERE u.email = 'dr.alaoui@demo.doctime' LIMIT 1;

INSERT IGNORE INTO medecins (user_id, specialite, numero_ordre, annee_experience, cabinet_ville, cabinet_adresse, consultation_prix, consultation_duree, actif, description)
SELECT u.id, 'Pédiatrie', 'TN-MED-ORD-20002', 8, 'Sfax', 'Route de l\'Aéroport, Sfax', 65.00, 25, 1, 'Pédiatre – suivi enfants et adolescents.'
FROM users u WHERE u.email = 'dr.bensalah@demo.doctime' LIMIT 1;

-- ---------- PROFILS PATIENTS (4) ----------
INSERT IGNORE INTO patients (user_id, groupe_sanguin, allergies, urgence_contact_nom, urgence_contact_telephone, medecin_traitant_id)
SELECT u.id, 'A+', 'Pénicilline', 'Contact urgence', '90000000', (SELECT id FROM users WHERE email = 'dr.alaoui@demo.doctime' LIMIT 1)
FROM users u WHERE u.email = 'patient1@demo.doctime' LIMIT 1;

INSERT IGNORE INTO patients (user_id, groupe_sanguin, allergies, urgence_contact_nom, urgence_contact_telephone, medecin_traitant_id)
SELECT u.id, 'O+', 'Aucune', 'Contact urgence', '90000001', (SELECT id FROM users WHERE email = 'dr.bensalah@demo.doctime' LIMIT 1)
FROM users u WHERE u.email = 'patient2@demo.doctime' LIMIT 1;

INSERT IGNORE INTO patients (user_id, groupe_sanguin, allergies, urgence_contact_nom, urgence_contact_telephone)
SELECT u.id, 'B+', 'Arachides', 'Contact urgence', '90000002'
FROM users u WHERE u.email = 'patient3@demo.doctime' LIMIT 1;

INSERT IGNORE INTO patients (user_id, groupe_sanguin, allergies, urgence_contact_nom, urgence_contact_telephone)
SELECT u.id, 'AB+', 'Aucune', 'Contact urgence', '90000003'
FROM users u WHERE u.email = 'patient4@demo.doctime' LIMIT 1;

-- ---------- DISPONIBILITÉS (6 créneaux, médecin Alaoui) ----------
INSERT INTO disponibilites (medecin_id, jour_semaine, heure_debut, heure_fin, actif)
SELECT u.id, 'Lundi', '08:00:00', '12:00:00', 1 FROM users u WHERE u.email = 'dr.alaoui@demo.doctime' LIMIT 1;
INSERT INTO disponibilites (medecin_id, jour_semaine, heure_debut, heure_fin, actif)
SELECT u.id, 'Mardi', '08:00:00', '12:00:00', 1 FROM users u WHERE u.email = 'dr.alaoui@demo.doctime' LIMIT 1;
INSERT INTO disponibilites (medecin_id, jour_semaine, heure_debut, heure_fin, actif)
SELECT u.id, 'Mercredi', '14:00:00', '18:00:00', 1 FROM users u WHERE u.email = 'dr.alaoui@demo.doctime' LIMIT 1;
INSERT INTO disponibilites (medecin_id, jour_semaine, heure_debut, heure_fin, actif)
SELECT u.id, 'Jeudi', '08:00:00', '12:00:00', 1 FROM users u WHERE u.email = 'dr.bensalah@demo.doctime' LIMIT 1;
INSERT INTO disponibilites (medecin_id, jour_semaine, heure_debut, heure_fin, actif)
SELECT u.id, 'Vendredi', '09:00:00', '13:00:00', 1 FROM users u WHERE u.email = 'dr.bensalah@demo.doctime' LIMIT 1;
INSERT INTO disponibilites (medecin_id, jour_semaine, heure_debut, heure_fin, actif)
SELECT u.id, 'Samedi', '09:00:00', '12:00:00', 1 FROM users u WHERE u.email = 'dr.bensalah@demo.doctime' LIMIT 1;

-- ---------- RENDEZ-VOUS (6) ----------
INSERT INTO rendez_vous (patient_id, medecin_id, date_rendezvous, heure_rendezvous, duree, motif, statut)
SELECT pu.id, mu.id, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '09:00:00', 30, 'Bilan cardiologique de contrôle', 'confirmé'
FROM users pu CROSS JOIN users mu WHERE pu.email = 'patient1@demo.doctime' AND mu.email = 'dr.alaoui@demo.doctime' LIMIT 1;

INSERT INTO rendez_vous (patient_id, medecin_id, date_rendezvous, heure_rendezvous, duree, motif, statut)
SELECT pu.id, mu.id, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '10:30:00', 25, 'Consultation pédiatrique', 'en_attente'
FROM users pu CROSS JOIN users mu WHERE pu.email = 'patient2@demo.doctime' AND mu.email = 'dr.bensalah@demo.doctime' LIMIT 1;

INSERT INTO rendez_vous (patient_id, medecin_id, date_rendezvous, heure_rendezvous, motif, statut)
SELECT pu.id, mu.id, DATE_ADD(CURDATE(), INTERVAL 7 DAY), '11:00:00', 'Première consultation', 'en_attente'
FROM users pu CROSS JOIN users mu WHERE pu.email = 'patient3@demo.doctime' AND mu.email = 'dr.alaoui@demo.doctime' LIMIT 1;

INSERT INTO rendez_vous (patient_id, medecin_id, date_rendezvous, heure_rendezvous, motif, statut)
SELECT pu.id, mu.id, DATE_ADD(CURDATE(), INTERVAL 9 DAY), '08:30:00', 'Suivi enfant', 'confirmé'
FROM users pu CROSS JOIN users mu WHERE pu.email = 'patient4@demo.doctime' AND mu.email = 'dr.bensalah@demo.doctime' LIMIT 1;

INSERT INTO rendez_vous (patient_id, medecin_id, date_rendezvous, heure_rendezvous, motif, statut)
SELECT pu.id, mu.id, DATE_ADD(CURDATE(), INTERVAL 12 DAY), '15:00:00', 'ECG', 'en_attente'
FROM users pu CROSS JOIN users mu WHERE pu.email = 'patient1@demo.doctime' AND mu.email = 'dr.bensalah@demo.doctime' LIMIT 1;

INSERT INTO rendez_vous (patient_id, medecin_id, date_rendezvous, heure_rendezvous, motif, statut)
SELECT pu.id, mu.id, DATE_ADD(CURDATE(), INTERVAL 14 DAY), '16:00:00', 'Vaccination préventive', 'terminé'
FROM users pu CROSS JOIN users mu WHERE pu.email = 'patient2@demo.doctime' AND mu.email = 'dr.alaoui@demo.doctime' LIMIT 1;

-- ---------- ARTICLES (6) – auteur = admin ----------
INSERT IGNORE INTO articles (titre, slug, contenu, resume, auteur_id, categorie, tags, status, vues)
SELECT 'Bienvenue sur Doctime', 'bienvenue-doctime', '<p>Plateforme de santé en ligne pour patients et professionnels.</p>', 'Présentation', u.id, 'Santé', 'doctime, santé', 'publié', 42
FROM users u WHERE u.email = 'admin@doctime.com' LIMIT 1;

INSERT IGNORE INTO articles (titre, slug, contenu, resume, auteur_id, categorie, tags, status, vues)
SELECT '5 gestes pour le cœur', '5-gestes-coeur', '<p>Activité physique, alimentation équilibrée, arrêt tabac.</p>', 'Prévention cardio', u.id, 'Prévention', 'cœur', 'publié', 18
FROM users u WHERE u.email = 'admin@doctime.com' LIMIT 1;

INSERT IGNORE INTO articles (titre, slug, contenu, resume, auteur_id, categorie, tags, status, vues)
SELECT 'Vaccination des enfants', 'vaccination-enfants', '<p>Calendrier vaccinal et questions fréquentes.</p>', 'Pédiatrie', u.id, 'Pédiatrie', 'vaccins', 'publié', 25
FROM users u WHERE u.email = 'admin@doctime.com' LIMIT 1;

INSERT IGNORE INTO articles (titre, slug, contenu, resume, auteur_id, categorie, tags, status, vues)
SELECT 'Sommeil et récupération', 'sommeil-recuperation', '<p>Hygiène de sommeil pour adultes actifs.</p>', 'Bien-être', u.id, 'Bien-être', 'sommeil', 'publié', 9
FROM users u WHERE u.email = 'admin@doctime.com' LIMIT 1;

INSERT IGNORE INTO articles (titre, slug, contenu, resume, auteur_id, categorie, tags, status, vues)
SELECT 'Parapharmacie : bien choisir', 'parapharmacie-bien-choisir', '<p>Conseils d\'achat en ligne sécurisé.</p>', 'Pharmacie', u.id, 'Pharmacie', 'achat', 'brouillon', 3
FROM users u WHERE u.email = 'admin@doctime.com' LIMIT 1;

INSERT IGNORE INTO articles (titre, slug, contenu, resume, auteur_id, categorie, tags, status, vues)
SELECT 'Événements santé à Tunis', 'evenements-sante-tunis', '<p>Prochains webinaires et ateliers.</p>', 'Événements', u.id, 'Événements', 'tunis', 'publié', 11
FROM users u WHERE u.email = 'admin@doctime.com' LIMIT 1;

-- ---------- COMMENTAIRES reply (6) ----------
INSERT INTO reply (id_article, user_id, type_reply, contenu_text, auteur)
SELECT a.id, u.id, 'text', 'Très clair, merci pour ces conseils.', 'Amine T.'
FROM articles a CROSS JOIN users u WHERE a.slug = 'bienvenue-doctime' AND u.email = 'patient1@demo.doctime' LIMIT 1;

INSERT INTO reply (id_article, user_id, type_reply, contenu_text, auteur)
SELECT a.id, u.id, 'text', 'Article utile pour toute la famille.', 'Sara M.'
FROM articles a CROSS JOIN users u WHERE a.slug = '5-gestes-coeur' AND u.email = 'patient2@demo.doctime' LIMIT 1;

INSERT INTO reply (id_article, user_id, type_reply, contenu_text, emoji)
SELECT a.id, u.id, 'mixte', 'Super contenu ', '👍'
FROM articles a CROSS JOIN users u WHERE a.slug = 'vaccination-enfants' AND u.email = 'patient3@demo.doctime' LIMIT 1;

INSERT INTO reply (id_article, user_id, type_reply, contenu_text, auteur)
SELECT a.id, NULL, 'text', 'Commentaire invité pour test modération.', 'Visiteur'
FROM articles a WHERE a.slug = 'sommeil-recuperation' LIMIT 1;

INSERT INTO reply (id_article, user_id, type_reply, contenu_text, auteur)
SELECT a.id, u.id, 'text', 'Hâte de voir la suite sur la parapharmacie.', 'Nour G.'
FROM articles a CROSS JOIN users u WHERE a.slug = 'parapharmacie-bien-choisir' AND u.email = 'patient4@demo.doctime' LIMIT 1;

INSERT INTO reply (id_article, user_id, type_reply, contenu_text, auteur)
SELECT a.id, u.id, 'text', 'Les événements sont une bonne idée.', 'Amine T.'
FROM articles a CROSS JOIN users u WHERE a.slug = 'evenements-sante-tunis' AND u.email = 'patient1@demo.doctime' LIMIT 1;

-- ---------- ÉVÉNEMENTS (6) ----------
INSERT IGNORE INTO events (titre, slug, description, contenu, date_debut, date_fin, lieu, capacite_max, places_restantes, prix, status) VALUES
('Journée cœur et santé', 'journee-coeur-sante-2026', 'Dépistage et sensibilisation', '<p>Stands et conférences.</p>', '2026-06-10 09:00:00', '2026-06-10 17:00:00', 'Palais des congrès, Tunis', 200, 120, 0, 'à venir'),
('Atelier nutrition', 'atelier-nutrition-2026', 'Cooking santé', '<p>Atelier pratique.</p>', '2026-06-20 14:00:00', '2026-06-20 16:00:00', 'Centre culturel, Sfax', 40, 25, 15.00, 'à venir'),
('Webinaire sommeil', 'webinaire-sommeil-2026', 'En ligne', '<p>Live interactif.</p>', '2026-05-25 20:00:00', '2026-05-25 21:30:00', 'En ligne', 500, 400, 0, 'à venir'),
('Marche solidaire', 'marche-solidaire-2026', 'Collecte fonds', '<p>Parc du Belvédère.</p>', '2026-09-01 08:00:00', '2026-09-01 12:00:00', 'Tunis', 300, 300, 5.00, 'à venir'),
('Sensibilisation diabète', 'sensibilisation-diabete-2026', 'Stand gratuit', '<p>Dépistage HbA1c.</p>', '2026-07-05 10:00:00', '2026-07-05 14:00:00', 'Sousse', 150, 90, 0, 'à venir'),
('Conférence pédiatrie', 'conference-pediatrie-2026', 'Parents bienvenue', '<p>FAQ pédiatre.</p>', '2026-08-12 18:00:00', '2026-08-12 19:30:00', 'En ligne', 200, 180, 0, 'à venir');

-- ---------- SPONSORS (6) – ENUM niveau : platinium, gold, silver, bronze ----------
INSERT IGNORE INTO sponsors (nom, site_web, description, niveau, actif) VALUES
('SantéPlus TN', 'https://santeplus.example', 'Partenaire médias', 'gold', 1),
('LaboBio', 'https://labobio.example', 'Laboratoire d\'analyses', 'silver', 1),
('MedEquip', 'https://medequip.example', 'Matériel médical', 'silver', 1),
('PharmaSud', 'https://pharmasud.example', 'Distribution', 'bronze', 1),
('CardioPro', 'https://cardiopro.example', 'Cardiologie', 'gold', 1),
('NutriLife', 'https://nutrilife.example', 'Compléments', 'bronze', 1);

UPDATE sponsors SET
    email = CASE nom
        WHEN 'SantéPlus TN' THEN 'contact@santeplus.example'
        WHEN 'LaboBio' THEN 'labo@labobio.example'
        WHEN 'MedEquip' THEN 'info@medequip.example'
        WHEN 'PharmaSud' THEN 'pharma@pharmasud.example'
        WHEN 'CardioPro' THEN 'hello@cardiopro.example'
        WHEN 'NutriLife' THEN 'bonjour@nutrilife.example'
        ELSE email
    END,
    telephone = CASE nom
        WHEN 'SantéPlus TN' THEN '71111111'
        WHEN 'LaboBio' THEN '72222222'
        WHEN 'MedEquip' THEN '73333333'
        WHEN 'PharmaSud' THEN '74444444'
        WHEN 'CardioPro' THEN '75555555'
        WHEN 'NutriLife' THEN '76666666'
        ELSE telephone
    END
WHERE nom IN ('SantéPlus TN','LaboBio','MedEquip','PharmaSud','CardioPro','NutriLife');

UPDATE events SET categorie = 'Cardiologie', sponsor_id = (SELECT id FROM sponsors WHERE nom = 'CardioPro' LIMIT 1) WHERE slug = 'journee-coeur-sante-2026' LIMIT 1;
UPDATE events SET categorie = 'Nutrition', sponsor_id = (SELECT id FROM sponsors WHERE nom = 'NutriLife' LIMIT 1) WHERE slug = 'atelier-nutrition-2026' LIMIT 1;

-- ---------- PARTICIPATIONS (6) – un user différent par événement quand possible ----------
INSERT IGNORE INTO participations (event_id, user_id, statut)
SELECT e.id, u.id, 'inscrit' FROM events e CROSS JOIN users u WHERE e.slug = 'journee-coeur-sante-2026' AND u.email = 'patient1@demo.doctime' LIMIT 1;

INSERT IGNORE INTO participations (event_id, user_id, statut)
SELECT e.id, u.id, 'inscrit' FROM events e CROSS JOIN users u WHERE e.slug = 'atelier-nutrition-2026' AND u.email = 'patient2@demo.doctime' LIMIT 1;

INSERT IGNORE INTO participations (event_id, user_id, statut)
SELECT e.id, u.id, 'inscrit' FROM events e CROSS JOIN users u WHERE e.slug = 'webinaire-sommeil-2026' AND u.email = 'patient3@demo.doctime' LIMIT 1;

INSERT IGNORE INTO participations (event_id, user_id, statut)
SELECT e.id, u.id, 'inscrit' FROM events e CROSS JOIN users u WHERE e.slug = 'marche-solidaire-2026' AND u.email = 'patient4@demo.doctime' LIMIT 1;

INSERT IGNORE INTO participations (event_id, user_id, statut)
SELECT e.id, u.id, 'présent' FROM events e CROSS JOIN users u WHERE e.slug = 'sensibilisation-diabete-2026' AND u.email = 'patient1@demo.doctime' LIMIT 1;

INSERT IGNORE INTO participations (event_id, user_id, statut)
SELECT e.id, u.id, 'inscrit' FROM events e CROSS JOIN users u WHERE e.slug = 'conference-pediatrie-2026' AND u.email = 'dr.alaoui@demo.doctime' LIMIT 1;

-- ---------- PRODUITS (6) – categorie_id = 1 (Médicaments si présent) ----------
INSERT IGNORE INTO produits (nom, slug, description, prix, stock, categorie_id, prescription, status) VALUES
('Spray nasal isotonique', 'spray-nasal-isotonique', 'Flacon 50 ml', 12.90, 80, 1, 0, 'actif'),
('Sirop toux sèche', 'sirop-toux-seche', 'Flacon 150 ml', 8.50, 45, 1, 0, 'actif'),
('Pansements hydrocolloïdes x10', 'pansements-hydro', 'Boîte 10', 15.00, 60, 1, 0, 'actif'),
('Thermomètre digital', 'thermometre-digital', 'Mesure rapide', 22.00, 30, 1, 0, 'actif'),
('Gel hydroalcoolique 500ml', 'gel-hydro-500', 'Format famille', 9.90, 100, 1, 0, 'actif'),
('Vitamine D3 1000 UI', 'vitamine-d3-1000', 'Gélules 60', 18.50, 55, 1, 1, 'actif');

-- ---------- COMMANDES + DÉTAILS (2 commandes avec lignes, pour ne pas exploser les numéros uniques) ----------
INSERT INTO commandes (numero_commande, user_id, total_ht, total_ttc, status, adresse_livraison, mode_paiement)
SELECT 'CMD-DEMO-10001', u.id, 46.40, 55.00, 'confirmée', '15 Rue de la Santé, Tunis', 'Carte'
FROM users u WHERE u.email = 'patient1@demo.doctime' LIMIT 1;

INSERT INTO commandes (numero_commande, user_id, total_ht, total_ttc, status, adresse_livraison, mode_paiement)
SELECT 'CMD-DEMO-10002', u.id, 30.60, 36.50, 'en_attente', '22 Av. Liberté, Sfax', 'Virement'
FROM users u WHERE u.email = 'patient2@demo.doctime' LIMIT 1;

INSERT INTO commandes (numero_commande, user_id, total_ht, total_ttc, status, adresse_livraison, mode_paiement)
SELECT 'CMD-DEMO-10003', u.id, 24.00, 28.80, 'expédiée', '8 Rue Habib, Sousse', 'Carte'
FROM users u WHERE u.email = 'patient3@demo.doctime' LIMIT 1;

INSERT INTO commandes (numero_commande, user_id, total_ht, total_ttc, status, adresse_livraison, mode_paiement)
SELECT 'CMD-DEMO-10004', u.id, 18.50, 22.20, 'livrée', '5 Impasse des Oliviers, Tunis', 'Espèces'
FROM users u WHERE u.email = 'patient4@demo.doctime' LIMIT 1;

INSERT INTO commandes (numero_commande, user_id, total_ht, total_ttc, status, adresse_livraison, mode_paiement)
SELECT 'CMD-DEMO-10005', u.id, 50.00, 60.00, 'en_attente', '1 Av. Principale, Bizerte', 'Carte'
FROM users u WHERE u.email = 'patient1@demo.doctime' LIMIT 1;

INSERT INTO commandes (numero_commande, user_id, total_ht, total_ttc, status, adresse_livraison, mode_paiement)
SELECT 'CMD-DEMO-10006', u.id, 12.90, 15.48, 'confirmée', '3 Rue du Port, La Goulette', 'Carte'
FROM users u WHERE u.email = 'patient2@demo.doctime' LIMIT 1;

-- Détails : lier au dernier produit inséré / existant par slug
INSERT INTO commande_details (commande_id, produit_id, quantite, prix_unitaire, total)
SELECT c.id, p.id, 2, p.prix, p.prix * 2 FROM commandes c JOIN produits p ON p.slug = 'spray-nasal-isotonique' WHERE c.numero_commande = 'CMD-DEMO-10001' LIMIT 1;

INSERT INTO commande_details (commande_id, produit_id, quantite, prix_unitaire, total)
SELECT c.id, p.id, 1, p.prix, p.prix FROM commandes c JOIN produits p ON p.slug = 'sirop-toux-seche' WHERE c.numero_commande = 'CMD-DEMO-10002' LIMIT 1;

INSERT INTO commande_details (commande_id, produit_id, quantite, prix_unitaire, total)
SELECT c.id, p.id, 3, p.prix, p.prix * 3 FROM commandes c JOIN produits p ON p.slug = 'gel-hydro-500' WHERE c.numero_commande = 'CMD-DEMO-10003' LIMIT 1;

INSERT INTO commande_details (commande_id, produit_id, quantite, prix_unitaire, total)
SELECT c.id, p.id, 1, p.prix, p.prix FROM commandes c JOIN produits p ON p.slug = 'thermometre-digital' WHERE c.numero_commande = 'CMD-DEMO-10004' LIMIT 1;

INSERT INTO commande_details (commande_id, produit_id, quantite, prix_unitaire, total)
SELECT c.id, p.id, 2, p.prix, p.prix * 2 FROM commandes c JOIN produits p ON p.slug = 'vitamine-d3-1000' WHERE c.numero_commande = 'CMD-DEMO-10005' LIMIT 1;

INSERT INTO commande_details (commande_id, produit_id, quantite, prix_unitaire, total)
SELECT c.id, p.id, 1, p.prix, p.prix FROM commandes c JOIN produits p ON p.slug = 'pansements-hydro' WHERE c.numero_commande = 'CMD-DEMO-10006' LIMIT 1;

-- ---------- ORDONNANCES (6) ----------
INSERT IGNORE INTO ordonnances (numero_ordonnance, patient_id, medecin_id, date_ordonnance, date_expiration, contenu, diagnostic, status)
SELECT 'ORD-DEMO-60001', pu.id, mu.id, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Paracétamol 1g si douleur', 'Syndrome grippal', 'active'
FROM users pu CROSS JOIN users mu WHERE pu.email = 'patient1@demo.doctime' AND mu.email = 'dr.alaoui@demo.doctime' LIMIT 1;

INSERT IGNORE INTO ordonnances (numero_ordonnance, patient_id, medecin_id, date_ordonnance, date_expiration, contenu, diagnostic, status)
SELECT 'ORD-DEMO-60002', pu.id, mu.id, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'Sérum physiologique, lavage nasal', 'Rhinite', 'active'
FROM users pu CROSS JOIN users mu WHERE pu.email = 'patient2@demo.doctime' AND mu.email = 'dr.bensalah@demo.doctime' LIMIT 1;

INSERT IGNORE INTO ordonnances (numero_ordonnance, patient_id, medecin_id, date_ordonnance, contenu, diagnostic, status)
SELECT 'ORD-DEMO-60003', pu.id, mu.id, CURDATE(), 'Repos + hydratation', 'Fatigue', 'active'
FROM users pu CROSS JOIN users mu WHERE pu.email = 'patient3@demo.doctime' AND mu.email = 'dr.alaoui@demo.doctime' LIMIT 1;

INSERT IGNORE INTO ordonnances (numero_ordonnance, patient_id, medecin_id, date_ordonnance, contenu, diagnostic, status)
SELECT 'ORD-DEMO-60004', pu.id, mu.id, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Antihistaminique si démangeaisons', 'Allergie saisonnière', 'active'
FROM users pu CROSS JOIN users mu WHERE pu.email = 'patient4@demo.doctime' AND mu.email = 'dr.bensalah@demo.doctime' LIMIT 1;

INSERT IGNORE INTO ordonnances (numero_ordonnance, patient_id, medecin_id, date_ordonnance, contenu, diagnostic, status)
SELECT 'ORD-DEMO-60005', pu.id, mu.id, CURDATE(), 'Ibuprofène 400mg après repas', 'Courbatures', 'active'
FROM users pu CROSS JOIN users mu WHERE pu.email = 'patient1@demo.doctime' AND mu.email = 'dr.bensalah@demo.doctime' LIMIT 1;

INSERT IGNORE INTO ordonnances (numero_ordonnance, patient_id, medecin_id, date_ordonnance, contenu, diagnostic, status)
SELECT 'ORD-DEMO-60006', pu.id, mu.id, CURDATE(), 'Suivi tension artérielle à domicile', 'HTA légère', 'active'
FROM users pu CROSS JOIN users mu WHERE pu.email = 'patient2@demo.doctime' AND mu.email = 'dr.alaoui@demo.doctime' LIMIT 1;

-- ---------- LIGNES ORDONNANCE MÉDICAMENTS (6) ----------
INSERT INTO ordonnance_medicaments (ordonnance_id, medicament_nom, dosage, duree, posologie)
SELECT o.id, 'Paracétamol 1g', '1 comp', '3 jours', '1 comp x3/j si douleur' FROM ordonnances o WHERE o.numero_ordonnance = 'ORD-DEMO-60001' LIMIT 1;

INSERT INTO ordonnance_medicaments (ordonnance_id, medicament_nom, dosage, duree, posologie)
SELECT o.id, 'Sérum physiologique', '5ml', '7 jours', '2 lavages/jour' FROM ordonnances o WHERE o.numero_ordonnance = 'ORD-DEMO-60002' LIMIT 1;

INSERT INTO ordonnance_medicaments (ordonnance_id, medicament_nom, dosage, duree, posologie)
SELECT o.id, 'Vitamine C', '500mg', '10 jours', 'Matin' FROM ordonnances o WHERE o.numero_ordonnance = 'ORD-DEMO-60003' LIMIT 1;

INSERT INTO ordonnance_medicaments (ordonnance_id, medicament_nom, dosage, duree, posologie)
SELECT o.id, 'Cétirizine', '10mg', '5 jours', 'Soir' FROM ordonnances o WHERE o.numero_ordonnance = 'ORD-DEMO-60004' LIMIT 1;

INSERT INTO ordonnance_medicaments (ordonnance_id, medicament_nom, dosage, duree, posologie)
SELECT o.id, 'Ibuprofène', '400mg', '3 jours', 'Après repas' FROM ordonnances o WHERE o.numero_ordonnance = 'ORD-DEMO-60005' LIMIT 1;

INSERT INTO ordonnance_medicaments (ordonnance_id, medicament_nom, dosage, duree, posologie)
SELECT o.id, 'Amlodipine', '5mg', '30 jours', 'Matin' FROM ordonnances o WHERE o.numero_ordonnance = 'ORD-DEMO-60006' LIMIT 1;

-- ---------- AVIS (6) – couples (patient_id, medecin_id) uniques ----------
INSERT IGNORE INTO avis (patient_id, medecin_id, note, replay, status)
SELECT pu.id, mu.id, 5, 'Excellent suivi.', 'publié' FROM users pu CROSS JOIN users mu WHERE pu.email = 'patient1@demo.doctime' AND mu.email = 'dr.alaoui@demo.doctime' LIMIT 1;

INSERT IGNORE INTO avis (patient_id, medecin_id, note, replay, status)
SELECT pu.id, mu.id, 4, 'Très pro.', 'publié' FROM users pu CROSS JOIN users mu WHERE pu.email = 'patient2@demo.doctime' AND mu.email = 'dr.bensalah@demo.doctime' LIMIT 1;

INSERT IGNORE INTO avis (patient_id, medecin_id, note, replay, status)
SELECT pu.id, mu.id, 5, NULL, 'en_attente' FROM users pu CROSS JOIN users mu WHERE pu.email = 'patient3@demo.doctime' AND mu.email = 'dr.alaoui@demo.doctime' LIMIT 1;

INSERT IGNORE INTO avis (patient_id, medecin_id, note, replay, status)
SELECT pu.id, mu.id, 4, 'Pédagogue.', 'publié' FROM users pu CROSS JOIN users mu WHERE pu.email = 'patient4@demo.doctime' AND mu.email = 'dr.bensalah@demo.doctime' LIMIT 1;

INSERT IGNORE INTO avis (patient_id, medecin_id, note, replay, status)
SELECT pu.id, mu.id, 5, 'Bonne écoute.', 'publié' FROM users pu CROSS JOIN users mu WHERE pu.email = 'patient1@demo.doctime' AND mu.email = 'dr.bensalah@demo.doctime' LIMIT 1;

INSERT IGNORE INTO avis (patient_id, medecin_id, note, replay, status)
SELECT pu.id, mu.id, 4, 'RDV ponctuel.', 'publié' FROM users pu CROSS JOIN users mu WHERE pu.email = 'patient2@demo.doctime' AND mu.email = 'dr.alaoui@demo.doctime' LIMIT 1;

-- ---------- RÉCLAMATIONS (6) ----------
INSERT INTO reclamations (patient_id, sujet, description, priorite, statut)
SELECT u.id, 'Délai de livraison', 'Commande reçue avec 4 jours de retard.', 'moyenne', 'en_cours' FROM users u WHERE u.email = 'patient1@demo.doctime' LIMIT 1;

INSERT INTO reclamations (patient_id, sujet, description, priorite, statut)
SELECT u.id, 'Application mobile', 'Bug à la connexion 2FA.', 'haute', 'en_cours' FROM users u WHERE u.email = 'patient2@demo.doctime' LIMIT 1;

INSERT INTO reclamations (patient_id, sujet, description, priorite, statut)
SELECT u.id, 'Remboursement', 'Demande de justificatif.', 'basse', 'traité' FROM users u WHERE u.email = 'patient3@demo.doctime' LIMIT 1;

INSERT INTO reclamations (patient_id, sujet, description, priorite, statut)
SELECT u.id, 'Rendez-vous annulé', 'Souhaite reprogrammer.', 'moyenne', 'en_cours' FROM users u WHERE u.email = 'patient4@demo.doctime' LIMIT 1;

INSERT INTO reclamations (patient_id, sujet, description, priorite, statut)
SELECT u.id, 'Facturation', 'Montant TTC incorrect sur PDF.', 'haute', 'fermé' FROM users u WHERE u.email = 'patient1@demo.doctime' LIMIT 1;

INSERT INTO reclamations (patient_id, sujet, description, priorite, statut)
SELECT u.id, 'Données personnelles', 'Demande de correction adresse.', 'basse', 'traité' FROM users u WHERE u.email = 'patient3@demo.doctime' LIMIT 1;

-- Fin du jeu de démo
SELECT 'Seed demo OK – comptes test mot de passe: doctime123 (admin existant: voir doctime_full.sql)' AS message;
