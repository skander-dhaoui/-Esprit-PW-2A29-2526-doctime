-- =============================================
-- DOCTIME DATABASE RECREATION SCRIPT
-- Complete SQL script to recreate the entire database
-- Generated from project files
-- =============================================

-- Drop database if exists and create new one
DROP DATABASE IF EXISTS doctime_db;
CREATE DATABASE doctime_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE doctime_db;

-- =============================================
-- TABLE DES UTILISATEURS (CENTRALE)
-- =============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    adresse TEXT,
    date_naissance DATE,
    genre ENUM('M', 'F', 'Autre'),
    role ENUM('admin', 'medecin', 'patient') NOT NULL DEFAULT 'patient',
    statut ENUM('actif', 'inactif', 'en_attente') DEFAULT 'en_attente',
    avatar VARCHAR(255),
    derniere_connexion DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_statut (statut)
);

-- =============================================
-- TABLE DES PATIENTS
-- =============================================
CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    numero_securite_sociale VARCHAR(15),
    mutuelle VARCHAR(100),
    numero_mutuelle VARCHAR(50),
    groupe_sanguin VARCHAR(5),
    allergies TEXT,
    medicaments_actuels TEXT,
    antecedents_medicaux TEXT,
    medecin_traitant_id INT,
    urgence_contact_nom VARCHAR(200),
    urgence_contact_telephone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (medecin_traitant_id) REFERENCES users(id),
    INDEX idx_secu (numero_securite_sociale),
    INDEX idx_groupe_sanguin (groupe_sanguin)
);

-- =============================================
-- TABLE DES MÉDECINS
-- =============================================
CREATE TABLE IF NOT EXISTS medecins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    specialite VARCHAR(100) NOT NULL,
    numero_ordre VARCHAR(20) NOT NULL UNIQUE,
    annee_experience INT,
    diplomes TEXT,
    cabinet_adresse TEXT,
    cabinet_ville VARCHAR(100),
    cabinet_code_postal VARCHAR(10),
    cabinet_telephone VARCHAR(20),
    consultation_prix DECIMAL(10,2),
    consultation_duree INT DEFAULT 30,
    langues_parlees VARCHAR(200),
    description TEXT,
    actif BOOLEAN DEFAULT TRUE,
    certificats TEXT,
    notation_moyenne DECIMAL(3,2) DEFAULT 0,
    nombre_avis INT DEFAULT 0,
    statut_validation ENUM('en_attente', 'valide', 'refusé') DEFAULT 'en_attente',
    commentaire_validation TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_specialite (specialite),
    INDEX idx_ordre (numero_ordre),
    INDEX idx_ville (cabinet_ville),
    INDEX idx_actif (actif),
    INDEX idx_statut_validation (statut_validation)
);

-- =============================================
-- TABLE DES DISPONIBILITÉS
-- =============================================
CREATE TABLE IF NOT EXISTS disponibilites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medecin_id INT NOT NULL,
    jour_semaine ENUM('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'),
    heure_debut TIME,
    heure_fin TIME,
    pause_debut TIME,
    pause_fin TIME,
    actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (medecin_id) REFERENCES users(id),
    INDEX idx_medecin (medecin_id),
    INDEX idx_jour (jour_semaine)
);

-- =============================================
-- TABLE DES RENDEZ-VOUS
-- =============================================
CREATE TABLE IF NOT EXISTS rendez_vous (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    medecin_id INT NOT NULL,
    disponibilite_id INT NULL,
    date_rendezvous DATE NOT NULL,
    heure_rendezvous TIME NOT NULL,
    duree INT DEFAULT 30,
    motif TEXT,
    statut ENUM('en_attente', 'confirmé', 'annulé', 'terminé') DEFAULT 'en_attente',
    notes_medecin TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id),
    FOREIGN KEY (medecin_id) REFERENCES users(id),
    FOREIGN KEY (disponibilite_id) REFERENCES disponibilites(id) ON DELETE SET NULL,
    INDEX idx_date (date_rendezvous),
    INDEX idx_statut (statut),
    INDEX idx_patient (patient_id),
    INDEX idx_medecin (medecin_id),
    INDEX idx_disponibilite (disponibilite_id)
);

-- =============================================
-- TABLE DES ARTICLES (BLOG)
-- =============================================
CREATE TABLE IF NOT EXISTS articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    contenu LONGTEXT NOT NULL,
    resume TEXT,
    image VARCHAR(255),
    auteur_id INT NOT NULL,
    categorie VARCHAR(100),
    tags VARCHAR(255),
    status ENUM('brouillon', 'publié', 'archive') DEFAULT 'brouillon',
    moderation_status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    moderation_reason TEXT,
    moderated_at DATETIME,
    vues INT DEFAULT 0,
    likes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (auteur_id) REFERENCES users(id),
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_moderation_status (moderation_status),
    INDEX idx_created (created_at),
    INDEX idx_categorie (categorie)
);

-- =============================================
-- TABLE DES REPONSES (COMMENTAIRES DU BLOG)
-- =============================================
CREATE TABLE IF NOT EXISTS replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    user_id INT NOT NULL,
    replay TEXT NOT NULL,
    emoji VARCHAR(255) DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    status ENUM('en_attente', 'approuvé', 'rejeté') DEFAULT 'en_attente',
    moderation_status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    moderation_reason TEXT,
    moderated_at DATETIME,
    parent_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (parent_id) REFERENCES replies(id) ON DELETE CASCADE,
    INDEX idx_article (article_id),
    INDEX idx_status (status),
    INDEX idx_moderation_status (moderation_status),
    INDEX idx_parent_id (parent_id)
);

-- =============================================
-- TABLE DES NOTIFICATIONS ADMIN
-- =============================================
CREATE TABLE IF NOT EXISTS admin_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    reference_type VARCHAR(50) DEFAULT NULL,
    reference_id INT DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_is_read (is_read),
    INDEX idx_reference (reference_type, reference_id)
);

-- =============================================
-- TABLE DES ÉVÉNEMENTS
-- =============================================
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    contenu LONGTEXT,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NOT NULL,
    lieu VARCHAR(255),
    adresse TEXT,
    capacite_max INT,
    places_restantes INT,
    image VARCHAR(255),
    prix DECIMAL(10,2) DEFAULT 0,
    status ENUM('à venir', 'en_cours', 'terminé', 'annulé') DEFAULT 'à venir',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (date_debut),
    INDEX idx_status (status),
    INDEX idx_slug (slug)
);

-- =============================================
-- TABLE DES SPONSORS
-- =============================================
CREATE TABLE IF NOT EXISTS sponsors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    logo VARCHAR(255),
    site_web VARCHAR(255),
    description TEXT,
    niveau ENUM('platinium', 'gold', 'silver', 'bronze') DEFAULT 'bronze',
    actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_niveau (niveau)
);

-- =============================================
-- TABLE DES PARTICIPATIONS (ÉVÉNEMENTS)
-- =============================================
CREATE TABLE IF NOT EXISTS participations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    statut ENUM('inscrit', 'présent', 'absent') DEFAULT 'inscrit',
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    code_qr VARCHAR(255),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_participation (event_id, user_id),
    INDEX idx_event (event_id),
    INDEX idx_statut (statut)
);

-- =============================================
-- TABLE DES CATÉGORIES (PRODUITS)
-- =============================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    image VARCHAR(255),
    parent_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id),
    INDEX idx_slug (slug)
);

-- =============================================
-- TABLE DES MÉTIERS
-- =============================================
CREATE TABLE IF NOT EXISTS metiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    categorie VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nom (nom),
    INDEX idx_categorie (categorie)
);

-- =============================================
-- TABLE DES PRODUITS (PHARMACIE)
-- =============================================
CREATE TABLE IF NOT EXISTS produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    prix DECIMAL(10,2) NOT NULL,
    prix_promo DECIMAL(10,2),
    stock INT DEFAULT 0,
    image VARCHAR(255),
    images TEXT,
    categorie_id INT,
    prescription BOOLEAN DEFAULT FALSE,
    status ENUM('actif', 'inactif', 'rupture') DEFAULT 'actif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES categories(id),
    INDEX idx_categorie (categorie_id),
    INDEX idx_status (status),
    INDEX idx_prix (prix)
);

-- =============================================
-- TABLE DES COMMANDES
-- =============================================
CREATE TABLE IF NOT EXISTS commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_commande VARCHAR(50) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    date_commande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_ht DECIMAL(10,2) NOT NULL,
    total_ttc DECIMAL(10,2) NOT NULL,
    status ENUM('en_attente', 'confirmée', 'expédiée', 'livrée', 'annulée') DEFAULT 'en_attente',
    adresse_livraison TEXT NOT NULL,
    adresse_facturation TEXT,
    mode_paiement VARCHAR(50),
    reference_paiement VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_numero (numero_commande)
);

-- =============================================
-- TABLE DES DÉTAILS DE COMMANDE
-- =============================================
CREATE TABLE IF NOT EXISTS commande_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantite INT NOT NULL,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id),
    INDEX idx_commande (commande_id)
);

-- =============================================
-- TABLE DES ORDONNANCES (CORRIGÉE)
-- =============================================
CREATE TABLE IF NOT EXISTS ordonnances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_ordonnance VARCHAR(50) NOT NULL UNIQUE,
    patient_id INT NOT NULL,
    medecin_id INT NOT NULL,
    date_ordonnance DATE NOT NULL,
    date_expiration DATE,
    contenu TEXT NOT NULL,
    diagnostic TEXT,
    fichier VARCHAR(255),
    status ENUM('active', 'expirée', 'annulée') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id),
    FOREIGN KEY (medecin_id) REFERENCES users(id),
    INDEX idx_patient (patient_id),
    INDEX idx_numero (numero_ordonnance),
    INDEX idx_date (date_ordonnance)
);

-- =============================================
-- TABLE DES MÉDICAMENTS (DANS ORDONNANCE)
-- =============================================
CREATE TABLE IF NOT EXISTS ordonnance_medicaments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ordonnance_id INT NOT NULL,
    medicament_nom VARCHAR(255) NOT NULL,
    dosage VARCHAR(100),
    duree VARCHAR(100),
    posologie TEXT,
    instructions TEXT,
    FOREIGN KEY (ordonnance_id) REFERENCES ordonnances(id) ON DELETE CASCADE,
    INDEX idx_ordonnance (ordonnance_id)
);

-- =============================================
-- TABLE DES RÉCLAMATIONS
-- =============================================
CREATE TABLE IF NOT EXISTS reclamations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    sujet VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    priorite ENUM('basse', 'moyenne', 'haute') DEFAULT 'moyenne',
    statut ENUM('en_cours', 'traité', 'fermé') DEFAULT 'en_cours',
    reponse TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id),
    INDEX idx_patient (patient_id),
    INDEX idx_statut (statut)
);

-- =============================================
-- TABLE DES AVIS (MÉDECINS)
-- =============================================
CREATE TABLE IF NOT EXISTS avis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    medecin_id INT NOT NULL,
    note INT NOT NULL CHECK (note >= 1 AND note <= 5),
    replay TEXT,
    status ENUM('en_attente', 'publié', 'signalé') DEFAULT 'en_attente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id),
    FOREIGN KEY (medecin_id) REFERENCES users(id),
    UNIQUE KEY unique_avis (patient_id, medecin_id),
    INDEX idx_medecin (medecin_id),
    INDEX idx_note (note)
);

-- =============================================
-- TABLE DES COMMENTAIRES D'ÉVÉNEMENTS
-- =============================================
CREATE TABLE IF NOT EXISTS event_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    comment LONGTEXT NOT NULL,
    status ENUM('en_attente', 'approuvé', 'rejeté') DEFAULT 'approuvé',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_event (event_id),
    INDEX idx_status (status),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
);

-- =============================================
-- TABLE DES LIKES D'ARTICLES
-- =============================================
CREATE TABLE IF NOT EXISTS article_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    user_id INT NOT NULL,
    type ENUM('like', 'dislike') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_article_user (article_id, user_id),
    INDEX idx_article (article_id),
    INDEX idx_user (user_id),
    INDEX idx_type (type)
);

CREATE TABLE IF NOT EXISTS reply_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reply_id INT NOT NULL,
    user_id INT NOT NULL,
    type ENUM('like', 'dislike') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reply_id) REFERENCES replies(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_reply_user (reply_id, user_id),
    INDEX idx_reply (reply_id),
    INDEX idx_user (user_id),
    INDEX idx_type (type)
);

-- =============================================
-- AJOUT DES COLONNES SOCIAL AUTH
-- =============================================

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS social_provider VARCHAR(50) NULL AFTER avatar,
    ADD COLUMN IF NOT EXISTS social_provider_id VARCHAR(191) NULL AFTER social_provider,
    ADD COLUMN IF NOT EXISTS social_avatar VARCHAR(255) NULL AFTER social_provider_id;

CREATE INDEX IF NOT EXISTS idx_users_social_provider
    ON users (social_provider, social_provider_id);

-- =============================================
-- DONNÉES INITIALES
-- =============================================

-- Insertion d'un admin par défaut (mot de passe: admin123)
INSERT INTO users (nom, prenom, email, password, role, statut)
VALUES ('Admin', 'System', 'admin@doctime.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'actif');

-- =============================================
-- FIN DU SCRIPT
-- =============================================