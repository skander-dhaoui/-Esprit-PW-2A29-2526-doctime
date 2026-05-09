-- =============================================
-- SAMPLE DATA INSERTION SCRIPT
-- Adds sample data to all tables in doctime_db
-- Run this after recreate_database.sql
-- =============================================

USE doctime_db;

-- =============================================
-- SAMPLE CATÉGORIES
-- =============================================

INSERT INTO categories (nom, slug, description) VALUES
('Médicaments', 'medicaments', 'Médicaments sur ordonnance et en libre accès'),
('Parapharmacie', 'parapharmacie', 'Produits de parapharmacie'),
('Matériel médical', 'materiel-medical', 'Matériel et équipement médical'),
('Hygiène', 'hygiene', 'Produits d\'hygiène et soins'),
('Nutrition', 'nutrition', 'Compléments alimentaires et nutrition');

-- =============================================
-- SAMPLE MÉTIERS
-- =============================================

-- Insertion des métiers
INSERT INTO metiers (nom, categorie, description) VALUES
-- Santé générale
('Médecin généraliste', 'Santé générale', 'Médecin spécialisé en médecine générale et soins primaires'),
('Infirmier(e)', 'Santé générale', 'Professionnel de santé assurant les soins infirmiers'),
('Aide-soignant(e)', 'Santé générale', 'Personnel d\'aide et d\'assistance aux patients'),
('Ambulancier(e)', 'Santé générale', 'Transporteur de patients en ambulance'),
-- Spécialistes médicaux
('Cardiologue', 'Spécialistes médicaux', 'Médecin spécialisé en cardiologie'),
('Dermatologue', 'Spécialistes médicaux', 'Médecin spécialisé en dermatologie'),
('Neurologue', 'Spécialistes médicaux', 'Médecin spécialisé en neurologie'),
('Pédiatre', 'Spécialistes médicaux', 'Médecin spécialisé en pédiatrie'),
('Ophtalmologue', 'Spécialistes médicaux', 'Médecin spécialisé en ophtalmologie'),
('Chirurgien', 'Spécialistes médicaux', 'Médecin spécialisé en chirurgie'),
-- Santé dentaire
('Dentiste', 'Santé dentaire', 'Professionnel spécialisé dans les soins dentaires'),
('Hygiéniste dentaire', 'Santé dentaire', 'Professionnel assistant le dentiste'),
('Prothésiste dentaire', 'Santé dentaire', 'Professionnel fabricant les prothèses dentaires'),
-- Pharmacie
('Pharmacien(ne)', 'Pharmacie', 'Professionnel responsable de la pharmacie'),
('Préparateur en pharmacie', 'Pharmacie', 'Personnel assistant le pharmacien'),
-- Autres professionnels de santé
('Kinésithérapeute', 'Autres professionnels de santé', 'Professionnel en rééducation et physiothérapie'),
('Psychologue', 'Autres professionnels de santé', 'Professionnel en santé mentale et psychologie'),
('Diététicien(ne)', 'Autres professionnels de santé', 'Professionnel en nutrition et diététique'),
('Orthoptiste', 'Autres professionnels de santé', 'Professionnel en rééducation visuelle'),
('Sage-femme', 'Autres professionnels de santé', 'Professionnel spécialisé en obstétrique'),
('Vétérinaire', 'Autres professionnels de santé', 'Médecin spécialisé dans la médecine vétérinaire');

-- =============================================
-- SAMPLE USERS (PATIENTS AND DOCTORS)
-- =============================================

-- Sample patients
INSERT INTO users (nom, prenom, email, password, telephone, adresse, date_naissance, genre, role, statut) VALUES
('Dupont', 'Marie', 'marie.dupont@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0612345678', '123 Rue de la Santé, Paris', '1985-03-15', 'F', 'patient', 'actif'),
('Martin', 'Pierre', 'pierre.martin@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0623456789', '456 Avenue des Médecins, Lyon', '1978-07-22', 'M', 'patient', 'actif'),
('Dubois', 'Sophie', 'sophie.dubois@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0634567890', '789 Boulevard Hospitalier, Marseille', '1990-11-08', 'F', 'patient', 'actif'),
('Garcia', 'Antonio', 'antonio.garcia@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0645678901', '321 Place Médicale, Toulouse', '1982-05-30', 'M', 'patient', 'actif'),
('Lefebvre', 'Isabelle', 'isabelle.lefebvre@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0656789012', '654 Rue du Bien-être, Nice', '1975-09-12', 'F', 'patient', 'actif');

-- Sample doctors
INSERT INTO users (nom, prenom, email, password, telephone, adresse, date_naissance, genre, role, statut) VALUES
('Dr. Bernard', 'Jean', 'jean.bernard@doctime.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0667890123', '1 Rue des Docteurs, Paris', '1970-01-15', 'M', 'medecin', 'actif'),
('Dr. Moreau', 'Claire', 'claire.moreau@doctime.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0678901234', '2 Avenue Médicale, Lyon', '1973-04-20', 'F', 'medecin', 'actif'),
('Dr. Petit', 'Michel', 'michel.petit@doctime.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0689012345', '3 Boulevard de la Santé, Marseille', '1968-08-10', 'M', 'medecin', 'actif'),
('Dr. Roux', 'Marie', 'marie.roux@doctime.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0690123456', '4 Place du Médecin, Toulouse', '1975-12-05', 'F', 'medecin', 'actif'),
('Dr. Fournier', 'Pierre', 'pierre.fournier@doctime.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0601234567', '5 Rue Hospitalière, Nice', '1972-06-18', 'M', 'medecin', 'actif');

-- Additional test users for higher IDs
INSERT INTO users (nom, prenom, email, password, telephone, adresse, date_naissance, genre, role, statut) VALUES
('Durand', 'Paul', 'paul.durand@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0711111111', '100 Rue Test 1', '1980-02-14', 'M', 'patient', 'actif'),
('Lefevre', 'Nathalie', 'nathalie.lefevre@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0712222222', '101 Rue Test 2', '1987-05-20', 'F', 'patient', 'actif'),
('Mercier', 'Francois', 'francois.mercier@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0713333333', '102 Rue Test 3', '1992-08-09', 'M', 'patient', 'actif'),
('Bonnet', 'Sylvie', 'sylvie.bonnet@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0714444444', '103 Rue Test 4', '1988-11-03', 'F', 'patient', 'actif'),
('Fontaine', 'Laurent', 'laurent.fontaine@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0715555555', '104 Rue Test 5', '1979-01-25', 'M', 'patient', 'actif'),
('Renard', 'Catherine', 'catherine.renard@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0716666666', '105 Rue Test 6', '1984-07-11', 'F', 'patient', 'actif'),
('Leclerc', 'Thomas', 'thomas.leclerc@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0717777777', '106 Rue Test 7', '1995-04-19', 'M', 'patient', 'actif'),
('Gros', 'Martine', 'martine.gros@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0718888888', '107 Rue Test 8', '1986-10-07', 'F', 'patient', 'actif'),
('Arnould', 'Guillaume', 'guillaume.arnould@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0719999999', '108 Rue Test 9', '1981-09-16', 'M', 'patient', 'actif'),
('Rousseau', 'Valerie', 'valerie.rousseau@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0720000000', '109 Rue Test 10', '1989-03-22', 'F', 'patient', 'actif');

-- =============================================
-- SAMPLE PATIENTS DATA
-- =============================================

INSERT INTO patients (user_id, numero_securite_sociale, mutuelle, numero_mutuelle, groupe_sanguin, allergies, medicaments_actuels, antecedents_medicaux, medecin_traitant_id, urgence_contact_nom, urgence_contact_telephone) VALUES
(2, '2850375123456', 'Mutuelle Générale', 'MG123456', 'A+', 'Pénicilline, Arachides', 'Antihypertenseurs', 'Hypertension artérielle', 7, 'Mme Dupont', '0612345678'),
(3, '1780246987451', 'Harmonie Mutuelle', 'HM789456', 'O-', 'Aucune', 'Antidépresseurs', 'Dépression légère', 8, 'M. Martin', '0623456789'),
(4, '2900512345678', 'Malakoff Médéric', 'MM456789', 'B+', 'Lactose', 'Antidiabétiques', 'Diabète de type 2', 9, 'Mme Dubois', '0634567890'),
(5, '1820678901234', 'MGEN', 'MGEN321654', 'AB+', 'Aucune', 'Anticoagulants', 'Fibrillation auriculaire', 10, 'M. Garcia', '0645678901'),
(6, '1750890123456', 'Mutuelle Bleue', 'MB987654', 'A-', 'Sulfamides', 'Corticoïdes', 'Asthme', 11, 'Mme Lefebvre', '0656789012');

-- =============================================
-- SAMPLE MÉDECINS DATA
-- =============================================

INSERT INTO medecins (user_id, specialite, numero_ordre, annee_experience, diplomes, cabinet_adresse, cabinet_ville, cabinet_code_postal, cabinet_telephone, consultation_prix, consultation_duree, langues_parlees, description, actif, certificats, notation_moyenne, nombre_avis, statut_validation) VALUES
(7, 'Médecine générale', '750000123', 15, 'Médecine générale - Université Paris Descartes', '1 Rue des Docteurs', 'Paris', '75001', '0667890123', 50.00, 30, 'Français, Anglais', 'Médecin généraliste expérimenté avec plus de 15 ans de pratique.', TRUE, 'Certificat de formation continue 2023', 4.5, 25, 'valide'),
(8, 'Cardiologie', '690000456', 12, 'Cardiologie - Université Lyon 1', '2 Avenue Médicale', 'Lyon', '69001', '0678901234', 80.00, 45, 'Français, Italien', 'Spécialiste en cardiologie, traitement des pathologies cardiovasculaires.', TRUE, 'Diplôme européen de cardiologie', 4.7, 32, 'valide'),
(9, 'Pédiatrie', '130000789', 10, 'Pédiatrie - Université Aix-Marseille', '3 Boulevard de la Santé', 'Marseille', '13001', '0689012345', 60.00, 30, 'Français, Espagnol', 'Pédiatre passionnée par la santé des enfants et adolescents.', TRUE, 'Certificat de pédiatrie préventive', 4.8, 28, 'valide'),
(10, 'Dermatologie', '310000321', 8, 'Dermatologie - Université Toulouse 3', '4 Place du Médecin', 'Toulouse', '31000', '0690123456', 70.00, 30, 'Français, Anglais', 'Dermatologue spécialisée dans les pathologies cutanées et esthétiques.', TRUE, 'Diplôme de dermatologie esthétique', 4.3, 19, 'valide'),
(11, 'Ophtalmologie', '060000654', 20, 'Ophtalmologie - Université Nice Sophia Antipolis', '5 Rue Hospitalière', 'Nice', '06000', '0601234567', 75.00, 40, 'Français, Anglais, Italien', 'Ophtalmologiste avec 20 ans d\'expérience en chirurgie réfractive.', TRUE, 'Certificat de chirurgie oculaire', 4.6, 41, 'valide');

-- =============================================
-- SAMPLE DISPONIBILITÉS
-- =============================================

INSERT INTO disponibilites (medecin_id, jour_semaine, heure_debut, heure_fin, pause_debut, pause_fin, actif) VALUES
-- Dr. Bernard (ID 7)
(1, 'Lundi', '09:00:00', '12:00:00', '12:00:00', '14:00:00', TRUE),
(1, 'Lundi', '14:00:00', '17:00:00', NULL, NULL, TRUE),
(1, 'Mardi', '09:00:00', '12:00:00', '12:00:00', '14:00:00', TRUE),
(1, 'Mardi', '14:00:00', '17:00:00', NULL, NULL, TRUE),
(1, 'Mercredi', '09:00:00', '12:00:00', NULL, NULL, TRUE),
(1, 'Jeudi', '09:00:00', '12:00:00', '12:00:00', '14:00:00', TRUE),
(1, 'Jeudi', '14:00:00', '17:00:00', NULL, NULL, TRUE),
(1, 'Vendredi', '09:00:00', '12:00:00', '12:00:00', '14:00:00', TRUE),
(1, 'Vendredi', '14:00:00', '16:00:00', NULL, NULL, TRUE),

-- Dr. Moreau (ID 8)
(2, 'Lundi', '08:30:00', '12:00:00', '12:00:00', '13:30:00', TRUE),
(2, 'Lundi', '13:30:00', '17:30:00', NULL, NULL, TRUE),
(2, 'Mardi', '08:30:00', '12:00:00', '12:00:00', '13:30:00', TRUE),
(2, 'Mardi', '13:30:00', '17:30:00', NULL, NULL, TRUE),
(2, 'Mercredi', '08:30:00', '12:00:00', NULL, NULL, TRUE),
(2, 'Jeudi', '08:30:00', '12:00:00', '12:00:00', '13:30:00', TRUE),
(2, 'Jeudi', '13:30:00', '17:30:00', NULL, NULL, TRUE),
(2, 'Vendredi', '08:30:00', '12:00:00', '12:00:00', '13:30:00', TRUE),
(2, 'Vendredi', '13:30:00', '16:30:00', NULL, NULL, TRUE);

-- =============================================
-- SAMPLE RENDEZ-VOUS
-- =============================================

INSERT INTO rendez_vous (patient_id, medecin_id, disponibilite_id, date_rendezvous, heure_rendezvous, duree, motif, statut, notes_medecin) VALUES
(2, 7, 1, '2026-05-15', '10:00:00', 30, 'Consultation générale - contrôle annuel', 'confirmé', 'Patient en bonne santé, renouvellement ordonnance'),
(3, 8, 10, '2026-05-16', '09:00:00', 45, 'Consultation cardiologique - douleur thoracique', 'confirmé', 'ECG demandé, suivi nécessaire'),
(4, 9, 15, '2026-05-17', '11:00:00', 30, 'Consultation pédiatrique - vaccination', 'confirmé', 'Vaccins à jour, croissance normale'),
(5, 10, 20, '2026-05-18', '14:30:00', 30, 'Consultation dermatologique - acné', 'en_attente', 'Premier rendez-vous'),
(6, 11, 25, '2026-05-19', '15:00:00', 40, 'Consultation ophtalmologique - contrôle vue', 'confirmé', 'Prescription de lunettes recommandée'),
(2, 7, 2, '2026-05-20', '15:30:00', 30, 'Suivi consultation générale', 'en_attente', NULL),
(3, 8, 11, '2026-05-21', '10:30:00', 45, 'Suivi cardiologique', 'confirmé', 'Résultats d\'examens à analyser');

-- =============================================
-- SAMPLE ARTICLES (BLOG)
-- =============================================

INSERT INTO articles (titre, slug, contenu, resume, image, auteur_id, categorie, tags, status, vues, likes) VALUES
('Les bienfaits du sport sur la santé cardiovasculaire', 'bienfaits-sport-sante-cardiovasculaire', '<p>Le sport régulier est essentiel pour maintenir une bonne santé cardiovasculaire. La pratique d\'une activité physique permet de renforcer le cœur, d\'améliorer la circulation sanguine et de réduire les risques de maladies cardiaques.</p><p>Plusieurs études scientifiques démontrent que 30 minutes d\'exercice modéré par jour peuvent réduire de 30% le risque d\'infarctus et d\'accident vasculaire cérébral.</p>', 'Découvrez comment l\'activité physique peut améliorer votre santé cardiaque et réduire les risques de maladies cardiovasculaires.', 'sport-cardio.jpg', 7, 'Santé', 'sport,coeur,santé,prévention', 'publié', 245, 0),
('Alimentation saine : les clés d\'une nutrition équilibrée', 'alimentation-saine-nutrition-equilibree', '<p>Une alimentation équilibrée est la base d\'une bonne santé. Il est important de consommer tous les groupes alimentaires en quantités appropriées pour apporter à l\'organisme tous les nutriments nécessaires.</p><p>Les fruits et légumes doivent représenter la moitié de l\'assiette, accompagnés de protéines maigres et de glucides complexes.</p>', 'Guide complet pour adopter une alimentation saine et équilibrée au quotidien.', 'alimentation-saine.jpg', 8, 'Nutrition', 'alimentation,nutrition,santé,équilibre', 'publié', 189, 0),
('Prévention des cancers : ce qu\'il faut savoir', 'prevention-cancers-ce-quil-faut-savoir', '<p>La prévention des cancers passe par plusieurs actions simples au quotidien. Arrêter le tabac, limiter la consommation d\'alcool, pratiquer une activité physique régulière et adopter une alimentation saine sont des mesures efficaces.</p><p>Les dépistages précoces permettent également de détecter les cancers à un stade où ils sont plus faciles à traiter.</p>', 'Tout savoir sur la prévention des cancers et les dépistages recommandés.', 'prevention-cancer.jpg', 9, 'Prévention', 'cancer,dépistage,prévention,santé', 'publié', 312, 0),
('Le sommeil : un allié pour la santé mentale', 'sommeil-allie-sante-mentale', '<p>Un sommeil de qualité est essentiel pour le bien-être psychologique. Pendant le sommeil, le cerveau traite les émotions de la journée et consolide les souvenirs.</p><p>Le manque de sommeil chronique peut entraîner de l\'anxiété, de la dépression et des troubles de l\'humeur.</p>', 'Comprendre l\'importance du sommeil pour la santé mentale et les troubles du sommeil.', 'sommeil-sante.jpg', 10, 'Santé mentale', 'sommeil,santé mentale,bien-être,stress', 'publié', 156, 0),
('Vaccination : protéger sa santé et celle des autres', 'vaccination-proteger-sante-autres', '<p>La vaccination est l\'un des moyens les plus efficaces pour prévenir les maladies infectieuses. Les vaccins stimulent le système immunitaire pour qu\'il produise des anticorps contre des agents pathogènes spécifiques.</p><p>Bien que certains vaccins soient obligatoires, d\'autres sont fortement recommandés pour protéger les populations vulnérables.</p>', 'Comprendre le principe de la vaccination et son importance pour la santé publique.', 'vaccination.jpg', 11, 'Prévention', 'vaccination,santé publique,prévention,immunité', 'publié', 278, 0);

-- =============================================
-- SAMPLE REPLIES (COMMENTAIRES SUR ARTICLES)
-- =============================================

INSERT INTO replies (article_id, user_id, replay, status, moderation_status) VALUES
(1, 2, 'Excellent article ! Je pratique la course à pied 3 fois par semaine et je sens vraiment les bienfaits sur mon cœur.', 'approuvé', 'approved'),
(1, 3, 'Merci pour ces informations. Je vais essayer de marcher 30 minutes par jour comme vous le recommandez.', 'approuvé', 'approved'),
(2, 4, 'Très bon article sur l\'alimentation. Pourriez-vous faire un article sur les régimes végétariens ?', 'approuvé', 'approved'),
(2, 5, 'J\'ai appliqué ces conseils et j\'ai perdu 5kg en 2 mois ! Merci pour les conseils.', 'approuvé', 'approved'),
(3, 6, 'Article très important. Le dépistage du cancer du sein m\'a sauvé la vie.', 'approuvé', 'approved'),
(4, 2, 'Je souffre d\'insomnie depuis des années. Quels conseils donneriez-vous pour améliorer le sommeil ?', 'en_attente', 'pending'),
(5, 3, 'La vaccination est cruciale. Merci de rappeler son importance.', 'approuvé', 'approved');

-- =============================================
-- SAMPLE EVENTS
-- =============================================

INSERT INTO events (titre, slug, contenu, date_debut, date_fin, lieu, adresse, capacite_max, places_restantes, image, prix, status) VALUES
('Journée mondiale de la santé mentale', 'journee-mondiale-sante-mentale', '<p>Célébrons ensemble la Journée mondiale de la santé mentale avec des conférences, ateliers et témoignages de professionnels et patients.</p><p>Programme : Conférences sur les troubles mentaux, ateliers de gestion du stress, témoignages, et stands d\'information.</p>', '2026-10-10 09:00:00', '2026-10-10 18:00:00', 'Centre des Congrès', '123 Boulevard de la Santé, Paris', 200, 45, 'sante-mentale-event.jpg', 0.00, 'à venir'),
('Forum de la prévention cardiovasculaire', 'forum-prevention-cardiovasculaire', '<p>Forum dédié à la prévention des maladies cardiovasculaires avec des experts internationaux.</p><p>Thèmes abordés : Alimentation anti-cholestérol, exercices physiques adaptés, dépistage précoce, et innovations médicales.</p>', '2026-11-15 08:30:00', '2026-11-15 17:00:00', 'Palais des Congrès', '456 Avenue des Médecins, Lyon', 150, 23, 'cardio-forum.jpg', 25.00, 'à venir'),
('Salon de la maternité et de l\'enfance', 'salon-maternite-enfance', '<p>Le rendez-vous incontournable pour les futurs parents et les familles avec des enfants.</p><p>Découvrez les dernières innovations en puériculture, consultez des professionnels de santé, et participez à des ateliers pratiques.</p>', '2026-12-05 10:00:00', '2026-12-05 19:00:00', 'Parc des Expositions', '789 Boulevard Familial, Marseille', 300, 87, 'maternite-salon.jpg', 15.00, 'à venir'),
('Conférence sur le diabète', 'conference-diabete', '<p>Conférence scientifique sur les avancées dans le traitement et la prévention du diabète.</p><p>Interventions d\'endocrinologues, diététiciens et chercheurs sur les nouvelles thérapies et les stratégies de prévention.</p>', '2026-09-20 09:00:00', '2026-09-20 16:00:00', 'Université Médicale', '321 Rue de la Recherche, Toulouse', 100, 12, 'diabete-conf.jpg', 30.00, 'à venir'),
('Semaine de la vaccination', 'semaine-vaccination', '<p>Semaine dédiée à l\'information et à la vaccination pour tous les âges.</p><p>Vaccinations gratuites, conférences sur l\'immunologie, et campagne de sensibilisation auprès du grand public.</p>', '2026-06-15 09:00:00', '2026-06-21 17:00:00', 'Centre de Vaccination', '654 Avenue Préventive, Nice', 500, 156, 'vaccination-semaine.jpg', 0.00, 'en_cours');

-- =============================================
-- SAMPLE SPONSORS
-- =============================================

INSERT INTO sponsors (nom, logo, site_web, description, niveau, actif) VALUES
('Laboratoire PharmaPlus', 'pharmaplus-logo.jpg', 'https://www.pharmaplus.com', 'Leader européen en médicaments génériques et innovants', 'platinium', TRUE),
('Mutuelle SantéPlus', 'santeplus-logo.jpg', 'https://www.santeplus.fr', 'Mutuelle santé proposant des garanties complètes et adaptées', 'gold', TRUE),
('Clinique du Bien-être', 'clinique-bienetre-logo.jpg', 'https://www.cliniquebienetre.fr', 'Réseau de cliniques spécialisées dans la médecine préventive', 'gold', TRUE),
('TechMed Solutions', 'techmed-logo.jpg', 'https://www.techmed-solutions.com', 'Éditeur de logiciels de gestion médicale et télémédecine', 'silver', TRUE),
('BioHealth Nutrition', 'biohealth-logo.jpg', 'https://www.biohealth-nutrition.com', 'Spécialiste des compléments alimentaires naturels', 'bronze', TRUE);

-- =============================================
-- SAMPLE PARTICIPATIONS
-- =============================================

INSERT INTO participations (event_id, user_id, statut, code_qr) VALUES
(1, 2, 'inscrit', 'QR_MARIE_DUPONT_001'),
(1, 3, 'inscrit', 'QR_PIERRE_MARTIN_002'),
(2, 4, 'présent', 'QR_SOPHIE_DUBOIS_003'),
(2, 5, 'inscrit', 'QR_ANTONIO_GARCIA_004'),
(3, 6, 'inscrit', 'QR_ISABELLE_LEFEBVRE_005'),
(4, 2, 'inscrit', 'QR_MARIE_DUPONT_006'),
(5, 3, 'présent', 'QR_PIERRE_MARTIN_007');

-- =============================================
-- SAMPLE PRODUITS
-- =============================================

INSERT INTO produits (nom, slug, description, prix, prix_promo, stock, image, categorie_id, prescription, status) VALUES
('Paracétamol 500mg', 'paracetamol-500mg', 'Antalgique et antipyrétique pour soulager la douleur et la fièvre', 2.50, 2.00, 150, 'paracetamol.jpg', 1, FALSE, 'actif'),
('Ibuprofène 200mg', 'ibuprofene-200mg', 'Anti-inflammatoire non stéroïdien pour douleurs et fièvre', 3.20, NULL, 120, 'ibuprofene.jpg', 1, FALSE, 'actif'),
('Amoxicilline 500mg', 'amoxicilline-500mg', 'Antibiotique pour infections bactériennes', 8.50, NULL, 80, 'amoxicilline.jpg', 1, TRUE, 'actif'),
('Crème solaire SPF50', 'creme-solaire-spf50', 'Protection solaire haute protection pour la peau', 15.90, 12.50, 200, 'creme-solaire.jpg', 2, FALSE, 'actif'),
('Bandage élastique', 'bandage-elastique', 'Bandage de contention pour soutien articulaire', 6.80, NULL, 300, 'bandage.jpg', 3, FALSE, 'actif'),
('Thermomètre électronique', 'thermometre-electronique', 'Thermomètre médical digital pour mesure de température', 12.90, 10.50, 100, 'thermometre.jpg', 3, FALSE, 'actif'),
('Savon antiseptique', 'savon-antiseptique', 'Savon désinfectant pour lavage des mains', 4.50, NULL, 250, 'savon-antiseptique.jpg', 4, FALSE, 'actif'),
('Vitamine C 500mg', 'vitamine-c-500mg', 'Complément alimentaire pour renforcer les défenses immunitaires', 9.90, 7.50, 180, 'vitamine-c.jpg', 5, FALSE, 'actif'),
('Crème hydratante', 'creme-hydratante', 'Crème nourrissante pour peau sèche', 18.50, 15.00, 90, 'creme-hydratante.jpg', 2, FALSE, 'actif'),
('Tensiomètre électronique', 'tensiometre-electronique', 'Appareil de mesure de la tension artérielle', 45.00, 38.00, 50, 'tensiometre.jpg', 3, FALSE, 'actif');

-- =============================================
-- SAMPLE COMMANDES
-- =============================================

INSERT INTO commandes (numero_commande, user_id, total_ht, total_ttc, status, adresse_livraison, mode_paiement) VALUES
('CMD2026001', 2, 25.40, 27.50, 'confirmée', '123 Rue de la Santé, 75001 Paris', 'Carte bancaire'),
('CMD2026002', 3, 15.90, 17.20, 'expédiée', '456 Avenue des Médecins, 69001 Lyon', 'PayPal'),
('CMD2026003', 4, 52.30, 56.50, 'livrée', '789 Boulevard Hospitalier, 13001 Marseille', 'Virement'),
('CMD2026004', 5, 8.50, 9.20, 'en_attente', '321 Place Médicale, 31000 Toulouse', 'Carte bancaire'),
('CMD2026005', 6, 31.80, 34.30, 'confirmée', '654 Rue du Bien-être, 06000 Nice', 'Carte bancaire');

-- =============================================
-- SAMPLE COMMANDE DETAILS
-- =============================================

INSERT INTO commande_details (commande_id, produit_id, quantite, prix_unitaire, total) VALUES
(1, 1, 2, 2.50, 5.00),
(1, 4, 1, 15.90, 15.90),
(1, 7, 1, 4.50, 4.50),
(2, 4, 1, 15.90, 15.90),
(3, 1, 1, 2.50, 2.50),
(3, 2, 1, 3.20, 3.20),
(3, 5, 2, 6.80, 13.60),
(3, 8, 1, 9.90, 9.90),
(4, 3, 1, 8.50, 8.50),
(5, 6, 1, 12.90, 12.90),
(5, 9, 1, 18.50, 18.50);

-- =============================================
-- SAMPLE ORDONNANCES
-- =============================================

INSERT INTO ordonnances (numero_ordonnance, patient_id, medecin_id, date_ordonnance, date_expiration, contenu, diagnostic, status) VALUES
('ORD2026001', 2, 7, '2026-05-01', '2026-08-01', 'Paracétamol 500mg - 1 comprimé toutes les 6 heures en cas de douleur', 'Fièvre et douleur légère', 'active'),
('ORD2026002', 3, 8, '2026-04-15', '2026-07-15', 'Bisoprolol 5mg - 1 comprimé par jour', 'Hypertension artérielle', 'active'),
('ORD2026003', 4, 9, '2026-05-10', '2026-11-10', 'Amoxicilline 500mg - 1 comprimé 3 fois par jour pendant 7 jours', 'Angine bactérienne', 'active'),
('ORD2026004', 5, 10, '2026-03-20', '2026-06-20', 'Crème dermocorticoïde - Appliquer 2 fois par jour', 'Dermatite atopique', 'expirée'),
('ORD2026005', 6, 11, '2026-04-05', '2026-10-05', 'Collyre anti-inflammatoire - 1 goutte 3 fois par jour', 'Conjonctivite allergique', 'active');

-- =============================================
-- SAMPLE ORDONNANCE MÉDICAMENTS
-- =============================================

INSERT INTO ordonnance_medicaments (ordonnance_id, medicament_nom, dosage, duree, posologie, instructions) VALUES
(1, 'Paracétamol', '500mg', '5 jours', '1 comprimé toutes les 6 heures', 'Ne pas dépasser 3g par jour'),
(2, 'Bisoprolol', '5mg', 'Traitement au long cours', '1 comprimé par jour', 'Surveillance tension artérielle'),
(3, 'Amoxicilline', '500mg', '7 jours', '1 comprimé 3 fois par jour', 'Prendre avec ou sans nourriture'),
(4, 'Crème dermocorticoïde', '0.1%', '10 jours', 'Appliquer 2 fois par jour', 'Ne pas appliquer sur le visage'),
(5, 'Collyre anti-inflammatoire', '1mg/ml', '5 jours', '1 goutte 3 fois par jour', 'Bien agiter avant utilisation');

-- =============================================
-- SAMPLE RÉCLAMATIONS
-- =============================================

INSERT INTO reclamations (patient_id, sujet, description, priorite, statut, reponse) VALUES
(2, 'Délai de rendez-vous trop long', 'J\'ai attendu plus d\'une heure pour ma consultation malgré un rendez-vous fixé', 'moyenne', 'traité', 'Nous nous excusons pour ce désagrément. Nous avons renforcé notre système de gestion des rendez-vous.'),
(3, 'Erreur dans l\'ordonnance', 'Le dosage prescrit ne correspond pas à mes besoins habituels', 'haute', 'en_cours', 'Nous examinons votre dossier médical et vous contacterons dans les 24h.'),
(4, 'Problème de facturation', 'J\'ai été facturé deux fois pour la même consultation', 'haute', 'fermé', 'Erreur corrigée. Le remboursement a été effectué sur votre compte.'),
(5, 'Médicament indisponible', 'Le médicament prescrit n\'est pas disponible en pharmacie', 'moyenne', 'en_cours', 'Nous avons contacté votre pharmacien pour trouver une alternative.'),
(6, 'Comportement du personnel', 'Le personnel d\'accueil a été désagréable lors de mon appel', 'basse', 'traité', 'Formation du personnel effectuée. Nous veillons à l\'amabilité de notre équipe.');

-- =============================================
-- SAMPLE AVIS (REVIEWS)
-- =============================================

INSERT INTO avis (patient_id, medecin_id, note, replay, status) VALUES
(2, 7, 5, 'Excellent médecin, très à l\'écoute et professionnel. Consultation parfaite.', 'publié'),
(3, 8, 4, 'Bon cardiologue, explications claires. Un peu d\'attente mais justifié.', 'publié'),
(4, 9, 5, 'Dr Moreau est formidable avec les enfants. Ma fille l\'adore !', 'publié'),
(5, 10, 4, 'Traitement efficace pour mon acné. Résultats visibles rapidement.', 'publié'),
(6, 11, 5, 'Ophtalmologiste très compétent. Chirurgie réussie, merci !', 'publié'),
(2, 8, 3, 'Consultation correcte mais j\'aurais aimé plus de temps pour poser mes questions.', 'en_attente'),
(3, 9, 5, 'Superbe pédiatre, très douce avec les enfants. Recommande vivement.', 'publié');

-- =============================================
-- SAMPLE EVENT COMMENTS
-- =============================================

INSERT INTO event_comments (event_id, user_id, comment, status) VALUES
(1, 2, 'Très intéressée par cette journée. Pourriez-vous donner plus de détails sur le programme ?', 'approuvé'),
(1, 3, 'Excellente initiative ! La santé mentale est un sujet trop souvent négligé.', 'approuvé'),
(2, 4, 'En tant que patient cardiaque, je suis impatient d\'assister à ce forum.', 'approuvé'),
(3, 5, 'Parfait pour les jeunes parents ! Merci pour cette organisation.', 'approuvé'),
(4, 6, 'Conférence très attendue. Les avancées dans le traitement du diabète sont cruciales.', 'approuvé'),
(5, 2, 'La vaccination sauve des vies. Bravo pour cette campagne de sensibilisation.', 'approuvé');

-- =============================================
-- SAMPLE ARTICLE LIKES
-- =============================================

INSERT INTO article_likes (article_id, user_id, type) VALUES
(1, 2, 'like'),
(1, 3, 'like'),
(1, 4, 'like'),
(2, 2, 'like'),
(2, 5, 'like'),
(2, 6, 'dislike'),
(3, 3, 'like'),
(3, 4, 'like'),
(3, 5, 'like'),
(4, 2, 'like'),
(4, 6, 'like'),
(5, 3, 'like'),
(5, 4, 'like'),
(5, 5, 'like'),
(5, 6, 'like');

-- =============================================
-- SAMPLE REPLY LIKES
-- =============================================

INSERT INTO reply_likes (reply_id, user_id, type) VALUES
(1, 2, 'like'),
(1, 4, 'like'),
(2, 3, 'like'),
(2, 5, 'like'),
(3, 2, 'like'),
(3, 6, 'dislike'),
(4, 2, 'like'),
(4, 3, 'like'),
(5, 4, 'like'),
(5, 5, 'like'),
(6, 2, 'like'),
(7, 3, 'like'),
(7, 5, 'like'),
(7, 6, 'like');

-- =============================================
-- FIN DU SCRIPT DE DONNÉES D'EXEMPLE
-- =============================================
