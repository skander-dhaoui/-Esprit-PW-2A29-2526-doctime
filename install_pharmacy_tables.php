<?php
// Script d'installation des tables pharmacie
require_once __DIR__ . '/config/database.php';

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Table categories
    $db->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        image VARCHAR(255),
        statut VARCHAR(20) DEFAULT 'actif',
        parent_id INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_slug (slug),
        INDEX idx_statut (statut)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    echo "Table categories creee\n";
    
    // Table produits
    $db->exec("CREATE TABLE IF NOT EXISTS produits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        categorie_id INT NOT NULL,
        nom VARCHAR(150) NOT NULL,
        slug VARCHAR(150) NOT NULL UNIQUE,
        description TEXT,
        prix DECIMAL(10,2) NOT NULL,
        stock INT DEFAULT 0,
        image VARCHAR(255),
        status VARCHAR(20) DEFAULT 'actif',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE CASCADE,
        INDEX idx_categorie (categorie_id),
        INDEX idx_slug (slug),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    echo "Table produits creee\n";
    
    // Table commandes
    $db->exec("CREATE TABLE IF NOT EXISTS commandes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        numero_commande VARCHAR(50) NOT NULL UNIQUE,
        user_id INT NOT NULL,
        date_commande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        total_ht DECIMAL(10,2) NOT NULL,
        total_ttc DECIMAL(10,2) NOT NULL,
        status VARCHAR(30) DEFAULT 'en_attente',
        adresse_livraison TEXT,
        telephone VARCHAR(20),
        notes TEXT,
        code_promo VARCHAR(20),
        reduction DECIMAL(10,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_status (status),
        INDEX idx_numero (numero_commande)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    echo "Table commandes creee\n";
    
    // Table commande_details
    $db->exec("CREATE TABLE IF NOT EXISTS commande_details (
        id INT AUTO_INCREMENT PRIMARY KEY,
        commande_id INT NOT NULL,
        produit_id INT NOT NULL,
        quantite INT NOT NULL,
        prix_unitaire DECIMAL(10,2) NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
        FOREIGN KEY (produit_id) REFERENCES produits(id),
        INDEX idx_commande (commande_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    echo "Table commande_details creee\n";
    
    // Inserer categories
    $stmt = $db->prepare("INSERT IGNORE INTO categories (nom, slug, description, statut) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Sante', 'sante', 'Produits de sante', 'actif']);
    $stmt->execute(['Beaute', 'beaute', 'Produits de beaute', 'actif']);
    $stmt->execute(['Bebe', 'bebe', 'Produits bebe', 'actif']);
    $stmt->execute(['Hygiene', 'hygiene', 'Produits hygiene', 'actif']);
    
    echo "Categories inserees\n";
    
    // Inserer produits
    $stmt = $db->prepare("INSERT IGNORE INTO produits (categorie_id, nom, slug, description, prix, stock, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([1, 'Vitamine C', 'vitamine-c', 'Vitamine C 1000mg', 25.50, 100, 'actif']);
    $stmt->execute([1, 'Magnesium', 'magnesium', 'Magnesium marin', 35.00, 50, 'actif']);
    $stmt->execute([2, 'Creme', 'creme', 'Creme hydratante', 45.00, 30, 'actif']);
    $stmt->execute([2, 'Serum', 'serum', 'Serum anti-age', 89.90, 20, 'actif']);
    $stmt->execute([3, 'Lait bebe', 'lait-bebe', 'Lait corporel bebe', 18.50, 80, 'actif']);
    $stmt->execute([4, 'Gel douche', 'gel-douche', 'Gel douche naturel', 12.90, 150, 'actif']);
    
    echo "Produits inseres\n";
    
    echo "\nInstallation terminee avec succes !\n";
    echo "Allez a: http://localhost/valorys_Copie/index.php?page=parapharmacie\n";
    
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}
