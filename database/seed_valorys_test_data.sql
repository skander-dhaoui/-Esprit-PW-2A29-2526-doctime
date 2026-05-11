-- ============================================================================
-- Données de test Valorys / DocTime (MySQL / MariaDB)
-- ----------------------------------------------------------------------------
-- PRÉREQUIS : avoir importé au minimum la structure (doctime_full.sql) et les
--             comptes démo : database/seed_demo_6x.sql
--             Mot de passe démo : doctime123
-- BASE par défaut : doctime_db (adapter si votre .env utilise un autre nom)
-- ============================================================================

USE doctime_db;

SET NAMES utf8mb4;

-- Mot de passe: doctime123 (identique à seed_demo_6x.sql)
SET @pwd := '$2y$10$G8jQQXF.HvLyaK0EEerGc.4c4iyl2HHKnm/gc/NXqQW9CWigHXbZq';

-- Créneau fixe dans le futur (liste d’attente + un RDV déjà pris sur la même plage)
SET @slot_date := DATE_ADD(CURDATE(), INTERVAL 15 DAY);
SET @slot_heure := '14:00:00';

-- Médecin démo : Alaoui si présent, sinon Ben Salah, sinon premier médecin actif
SET @demo_med_id := COALESCE(
    (SELECT id FROM users WHERE email = 'dr.alaoui@demo.doctime' LIMIT 1),
    (SELECT id FROM users WHERE email = 'dr.bensalah@demo.doctime' LIMIT 1),
    (SELECT id FROM users WHERE role = 'medecin' AND IFNULL(statut, 'actif') IN ('actif', 'en_attente') ORDER BY id LIMIT 1)
);

-- ----------------------------------------------------------------------------
-- Table liste d’attente (si créée uniquement par l’app PHP jusqu’ici)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS rendez_vous_waitlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    medecin_id INT NOT NULL,
    date_rendezvous DATE NOT NULL,
    heure_rendezvous TIME NOT NULL,
    motif TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    converted_rdv_id INT NULL,
    converted_at DATETIME NULL,
    notified_patient TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_waitlist_slot (medecin_id, date_rendezvous, heure_rendezvous, status),
    INDEX idx_waitlist_patient (patient_id)
);

-- ----------------------------------------------------------------------------
-- 2 patients supplémentaires (connexion : patient5@ / patient6@ + doctime123)
-- ----------------------------------------------------------------------------
INSERT IGNORE INTO users (nom, prenom, email, password, telephone, role, statut, genre) VALUES
('Hamdani', 'Youssef', 'patient5@demo.doctime', @pwd, '98555555', 'patient', 'actif', 'M'),
('Mezzi', 'Layla', 'patient6@demo.doctime', @pwd, '98666666', 'patient', 'actif', 'F');

INSERT IGNORE INTO patients (user_id, groupe_sanguin, allergies, urgence_contact_nom, urgence_contact_telephone)
SELECT u.id, 'O-', 'Aucune', 'Contact urgence', '90000004'
FROM users u WHERE u.email = 'patient5@demo.doctime' LIMIT 1;

INSERT IGNORE INTO patients (user_id, groupe_sanguin, allergies, urgence_contact_nom, urgence_contact_telephone)
SELECT u.id, 'A+', 'Latex', 'Contact urgence', '90000005'
FROM users u WHERE u.email = 'patient6@demo.doctime' LIMIT 1;

-- ----------------------------------------------------------------------------
-- Scénario liste d’attente : patient1 tient le créneau ; 4 patients en attente
-- (patient3 → patient4 → patient5 → patient6 selon created_at)
-- ----------------------------------------------------------------------------
INSERT INTO rendez_vous (patient_id, medecin_id, date_rendezvous, heure_rendezvous, duree, motif, statut)
SELECT pu.id, @demo_med_id, @slot_date, @slot_heure, 30,
       '[TEST] Créneau occupé — démo liste d''attente', 'confirmé'
FROM users pu
WHERE pu.email = 'patient1@demo.doctime'
  AND @demo_med_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM rendez_vous rv
      WHERE rv.medecin_id = @demo_med_id
        AND rv.date_rendezvous = @slot_date
        AND TIME(rv.heure_rendezvous) = TIME(@slot_heure)
        AND rv.statut NOT IN ('annulé', 'terminé')
  )
LIMIT 1;

INSERT INTO rendez_vous_waitlist (patient_id, medecin_id, date_rendezvous, heure_rendezvous, motif, status)
SELECT pu.id, @demo_med_id, @slot_date, @slot_heure, '[TEST] Attente — sera promu si annulation', 'pending'
FROM users pu
WHERE pu.email = 'patient3@demo.doctime'
  AND @demo_med_id IS NOT NULL
  AND EXISTS (
      SELECT 1 FROM rendez_vous rv
      WHERE rv.medecin_id = @demo_med_id
        AND rv.date_rendezvous = @slot_date
        AND TIME(rv.heure_rendezvous) = TIME(@slot_heure)
        AND rv.statut NOT IN ('annulé', 'terminé')
  )
  AND NOT EXISTS (
      SELECT 1 FROM rendez_vous_waitlist w
      WHERE w.patient_id = pu.id AND w.medecin_id = @demo_med_id
        AND w.date_rendezvous = @slot_date
        AND TIME(w.heure_rendezvous) = TIME(@slot_heure)
        AND w.status = 'pending'
  )
LIMIT 1;

INSERT INTO rendez_vous_waitlist (patient_id, medecin_id, date_rendezvous, heure_rendezvous, motif, status)
SELECT pu.id, @demo_med_id, @slot_date, @slot_heure, '[TEST] Attente — 2e position', 'pending'
FROM users pu
WHERE pu.email = 'patient4@demo.doctime'
  AND @demo_med_id IS NOT NULL
  AND EXISTS (
      SELECT 1 FROM rendez_vous rv
      WHERE rv.medecin_id = @demo_med_id
        AND rv.date_rendezvous = @slot_date
        AND TIME(rv.heure_rendezvous) = TIME(@slot_heure)
        AND rv.statut NOT IN ('annulé', 'terminé')
  )
  AND NOT EXISTS (
      SELECT 1 FROM rendez_vous_waitlist w
      WHERE w.patient_id = pu.id AND w.medecin_id = @demo_med_id
        AND w.date_rendezvous = @slot_date
        AND TIME(w.heure_rendezvous) = TIME(@slot_heure)
        AND w.status = 'pending'
  )
LIMIT 1;

INSERT INTO rendez_vous_waitlist (patient_id, medecin_id, date_rendezvous, heure_rendezvous, motif, status)
SELECT pu.id, @demo_med_id, @slot_date, @slot_heure, '[TEST] Attente — patient5', 'pending'
FROM users pu
WHERE pu.email = 'patient5@demo.doctime'
  AND @demo_med_id IS NOT NULL
  AND EXISTS (
      SELECT 1 FROM rendez_vous rv
      WHERE rv.medecin_id = @demo_med_id
        AND rv.date_rendezvous = @slot_date
        AND TIME(rv.heure_rendezvous) = TIME(@slot_heure)
        AND rv.statut NOT IN ('annulé', 'terminé')
  )
  AND NOT EXISTS (
      SELECT 1 FROM rendez_vous_waitlist w
      WHERE w.patient_id = pu.id AND w.medecin_id = @demo_med_id
        AND w.date_rendezvous = @slot_date
        AND TIME(w.heure_rendezvous) = TIME(@slot_heure)
        AND w.status = 'pending'
  )
LIMIT 1;

INSERT INTO rendez_vous_waitlist (patient_id, medecin_id, date_rendezvous, heure_rendezvous, motif, status)
SELECT pu.id, @demo_med_id, @slot_date, @slot_heure, '[TEST] Attente — patient6', 'pending'
FROM users pu
WHERE pu.email = 'patient6@demo.doctime'
  AND @demo_med_id IS NOT NULL
  AND EXISTS (
      SELECT 1 FROM rendez_vous rv
      WHERE rv.medecin_id = @demo_med_id
        AND rv.date_rendezvous = @slot_date
        AND TIME(rv.heure_rendezvous) = TIME(@slot_heure)
        AND rv.statut NOT IN ('annulé', 'terminé')
  )
  AND NOT EXISTS (
      SELECT 1 FROM rendez_vous_waitlist w
      WHERE w.patient_id = pu.id AND w.medecin_id = @demo_med_id
        AND w.date_rendezvous = @slot_date
        AND TIME(w.heure_rendezvous) = TIME(@slot_heure)
        AND w.status = 'pending'
  )
LIMIT 1;

-- ----------------------------------------------------------------------------
-- RDV libre supplémentaire (autre jour / autre heure, même médecin démo)
-- ----------------------------------------------------------------------------
INSERT INTO rendez_vous (patient_id, medecin_id, date_rendezvous, heure_rendezvous, duree, motif, statut)
SELECT pu.id, @demo_med_id, DATE_ADD(CURDATE(), INTERVAL 11 DAY), '10:00:00', 25,
       '[TEST] Consultation créneau libre', 'en_attente'
FROM users pu
WHERE pu.email = 'patient2@demo.doctime'
  AND @demo_med_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM rendez_vous rv
      WHERE rv.patient_id = pu.id AND rv.medecin_id = @demo_med_id
        AND rv.date_rendezvous = DATE_ADD(CURDATE(), INTERVAL 11 DAY)
        AND TIME(rv.heure_rendezvous) = TIME('10:00:00')
        AND rv.statut NOT IN ('annulé', 'terminé')
  )
LIMIT 1;

-- ----------------------------------------------------------------------------
-- Ordonnance de test (idempotente par numéro)
-- ----------------------------------------------------------------------------
INSERT INTO ordonnances (numero_ordonnance, patient_id, medecin_id, date_ordonnance, date_expiration, contenu, diagnostic, status)
SELECT 'ORD-TEST-VALORYS-2026-DEMO', pu.id, @demo_med_id, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 90 DAY),
       'Paracétamol 1 g — si douleur, espacer 6 h.\nHydratation abondante.',
       '[TEST] Syndrome grippal simple', 'active'
FROM users pu
WHERE pu.email = 'patient3@demo.doctime'
  AND @demo_med_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM ordonnances o WHERE o.numero_ordonnance = 'ORD-TEST-VALORYS-2026-DEMO')
LIMIT 1;

SET @oid_demo := (SELECT id FROM ordonnances WHERE numero_ordonnance = 'ORD-TEST-VALORYS-2026-DEMO' LIMIT 1);
INSERT INTO ordonnance_medicaments (ordonnance_id, medicament_nom, dosage, duree, posologie, instructions)
SELECT @oid_demo, 'Paracétamol', '1 g', '5 jours max', '1 comprimé toutes les 6 h si besoin', 'Ne pas dépasser 4 g/j'
FROM DUAL
WHERE @oid_demo IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM ordonnance_medicaments om WHERE om.ordonnance_id = @oid_demo);

-- ----------------------------------------------------------------------------
-- Réclamation de test
-- ----------------------------------------------------------------------------
INSERT INTO reclamations (patient_id, sujet, description, priorite, statut)
SELECT u.id, '[TEST] Délais de rappel',
       'Je souhaite être rappelé(e) 24 h avant le RDV — test données seed.', 'moyenne', 'en_cours'
FROM users u
WHERE u.email = 'patient4@demo.doctime'
  AND NOT EXISTS (
      SELECT 1 FROM reclamations r
      WHERE r.patient_id = u.id AND r.sujet = '[TEST] Délais de rappel'
  )
LIMIT 1;

-- ----------------------------------------------------------------------------
-- Avis médecin (unique patient + médecin)
-- ----------------------------------------------------------------------------
INSERT INTO avis (patient_id, medecin_id, note, replay, status)
SELECT pu.id, @demo_med_id, 5, 'Excellent accueil, explications claires.', 'publié'
FROM users pu
WHERE pu.email = 'patient2@demo.doctime'
  AND @demo_med_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM avis a WHERE a.patient_id = pu.id AND a.medecin_id = @demo_med_id)
LIMIT 1;

-- ----------------------------------------------------------------------------
-- Vérification rapide (affichage console mysql)
-- ----------------------------------------------------------------------------
SELECT 'rendez_vous_waitlist (pending)' AS tbl, COUNT(*) AS n FROM rendez_vous_waitlist WHERE status = 'pending';
SELECT 'rendez_vous [TEST]' AS info, COUNT(*) AS n FROM rendez_vous WHERE motif LIKE '[TEST]%';
