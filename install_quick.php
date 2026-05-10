<?php
session_start();
require_once __DIR__ . '/config/database.php';

$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>🔧 Installation RDV Avancée</h2>";
    
    // Migration 1 : event_comments
    echo "<h3>1. Création table event_comments</h3>";
    try {
        $checkStmt = $db->query("SHOW TABLES LIKE 'event_comments'");
        if ($checkStmt->rowCount() == 0) {
            $sql = "
                CREATE TABLE event_comments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    event_id INT NOT NULL,
                    user_id INT NOT NULL,
                    comment LONGTEXT NOT NULL,
                    status ENUM('en_attente', 'approuvé', 'rejeté') DEFAULT 'approuvé',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_event (event_id),
                    INDEX idx_status (status),
                    INDEX idx_user (user_id),
                    INDEX idx_created (created_at)
                )
            ";
            $db->exec($sql);
            echo "✅ Table créée\n";
        } else {
            echo "ℹ️ Table existe déjà\n";
        }
    } catch (Exception $e) {
        echo "❌ Erreur: " . $e->getMessage() . "\n";
    }
    
    // Migration 2 : disponibilite_id
    echo "<h3>2. Ajout colonne disponibilite_id</h3>";
    try {
        $checkStmt = $db->query("SHOW COLUMNS FROM rendez_vous LIKE 'disponibilite_id'");
        if ($checkStmt->rowCount() == 0) {
            $sql = "
                ALTER TABLE rendez_vous 
                ADD COLUMN disponibilite_id INT NULL AFTER medecin_id,
                ADD FOREIGN KEY fk_rdv_dispo (disponibilite_id) REFERENCES disponibilites(id) ON DELETE SET NULL,
                ADD INDEX idx_disponibilite (disponibilite_id)
            ";
            $db->exec($sql);
            echo "✅ Colonne ajoutée\n";
        } else {
            echo "ℹ️ Colonne existe déjà\n";
        }
    } catch (Exception $e) {
        echo "⚠️ Erreur (peut-être déjà créée): " . $e->getMessage() . "\n";
    }
    
    echo "<h3>✅ Installation terminée!</h3>";
    echo "<p><a href='index.php?page=admin_rendezvous'>← Retour aux RDV</a></p>";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage();
}
?>
