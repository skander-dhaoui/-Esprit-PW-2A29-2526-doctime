-- Données de test : 6 rendez-vous + 6 ordonnances pour le patient users.id = 2
-- Médecins : users.id 3 (Dr. Sami Alaoui), 4 (Dr. Leila Ben Salah).
--
-- Si les INSERT de statut échouent (enum corrompu), exécuter une fois :
--   database/fix_statuts_rdv_ordonnances.sql
--
SET NAMES utf8mb4;

INSERT INTO rendez_vous (patient_id, medecin_id, date_rendezvous, heure_rendezvous, duree, motif, statut) VALUES
(2, 3, '2026-05-12', '09:00:00', 30, 'Consultation de suivi — tension artérielle', 'terminé'),
(2, 3, '2026-05-14', '11:15:00', 30, 'Prise de sang — bilan lipidique', 'confirmé'),
(2, 4, '2026-05-18', '14:30:00', 30, 'Consultation pédiatrique (accompagnement)', 'en_attente'),
(2, 3, '2026-05-23', '10:00:00', 30, 'Renouvellement ordonnance', 'confirmé'),
(2, 4, '2026-05-27', '16:45:00', 30, 'Bilan allergie printanière', 'terminé'),
(2, 3, '2026-06-05', '08:45:00', 30, 'Contrôle général annuel', 'en_attente');

INSERT INTO ordonnances (numero_ordonnance, patient_id, medecin_id, date_ordonnance, date_expiration, contenu, diagnostic, status)
SELECT 'ORD-TEST-P2-20260510-A', 2, 3, '2026-05-10', '2026-08-10',
       'Amoxicilline 1 g — 1 comprimé matin et soir pendant 7 jours.\nParacétamol 1 g si fièvre ou douleur, espacer 6 h.',
       'Angine bactérienne suspecte', 'active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ordonnances WHERE numero_ordonnance = 'ORD-TEST-P2-20260510-A');

INSERT INTO ordonnances (numero_ordonnance, patient_id, medecin_id, date_ordonnance, date_expiration, contenu, diagnostic, status)
SELECT 'ORD-TEST-P2-20260510-B', 2, 4, '2026-05-11', '2026-11-11',
       'Crème dermocorticoïde — application fine 2 fois par jour sur zones concernées, 7 jours max.\nÉmollient sans parfum.',
       'Dermatite irritative', 'active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ordonnances WHERE numero_ordonnance = 'ORD-TEST-P2-20260510-B');

INSERT INTO ordonnances (numero_ordonnance, patient_id, medecin_id, date_ordonnance, date_expiration, contenu, diagnostic, status)
SELECT 'ORD-TEST-P2-20260510-C', 2, 3, '2026-05-12', '2026-06-12',
       'Vitamine D3 — 1 ampoule 100 000 UI dose mensuelle ou 1000 UI/j selon marque.\nReposer les yeux, lunettes solaires.',
       'Carence vitamine D légère', 'active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ordonnances WHERE numero_ordonnance = 'ORD-TEST-P2-20260510-C');

INSERT INTO ordonnances (numero_ordonnance, patient_id, medecin_id, date_ordonnance, date_expiration, contenu, diagnostic, status)
SELECT 'ORD-TEST-P2-20260510-D', 2, 3, '2026-05-13', '2026-12-31',
       'Ibuprofène 400 mg — 1 comprimé toutes les 8 h si douleur (max 3 jours).\nRéévaluation si persistance.',
       'Tendinite légère poignet', 'active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ordonnances WHERE numero_ordonnance = 'ORD-TEST-P2-20260510-D');

INSERT INTO ordonnances (numero_ordonnance, patient_id, medecin_id, date_ordonnance, date_expiration, contenu, diagnostic, status)
SELECT 'ORD-TEST-P2-20260510-E', 2, 4, '2026-05-14', NULL,
       'Sérum physiologique — lavage nasal 2 à 3 fois par jour.\nHumidificateur dans la chambre.',
       'Rhinite allergique saisonnière', 'active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ordonnances WHERE numero_ordonnance = 'ORD-TEST-P2-20260510-E');

INSERT INTO ordonnances (numero_ordonnance, patient_id, medecin_id, date_ordonnance, date_expiration, contenu, diagnostic, status)
SELECT 'ORD-TEST-P2-20260510-F', 2, 3, '2026-05-15', '2027-05-15',
       'Lévothyroxine — posologie selon dernier bilan TSH (à respecter à jeun).\nContrôle biologique dans 3 mois.',
       'Hypothyroïdie substituée — suivi', 'active'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM ordonnances WHERE numero_ordonnance = 'ORD-TEST-P2-20260510-F');

SET @oid_a := (SELECT id FROM ordonnances WHERE numero_ordonnance = 'ORD-TEST-P2-20260510-A' LIMIT 1);
INSERT INTO ordonnance_medicaments (ordonnance_id, medicament_nom, dosage, duree, posologie, instructions)
SELECT @oid_a, 'Amoxicilline', '1 g', '7 jours', '1 cp matin et soir', 'Finir le traitement même si meilleure'
FROM DUAL WHERE @oid_a IS NOT NULL AND NOT EXISTS (SELECT 1 FROM ordonnance_medicaments om WHERE om.ordonnance_id = @oid_a);

SET @oid_b := (SELECT id FROM ordonnances WHERE numero_ordonnance = 'ORD-TEST-P2-20260510-B' LIMIT 1);
INSERT INTO ordonnance_medicaments (ordonnance_id, medicament_nom, dosage, duree, posologie, instructions)
SELECT @oid_b, 'Crème corticoïde faible', '1 tube', '7 j. max', '2 applications/j', 'Ne pas appliquer sur visage prolongé'
FROM DUAL WHERE @oid_b IS NOT NULL AND NOT EXISTS (SELECT 1 FROM ordonnance_medicaments om WHERE om.ordonnance_id = @oid_b);
