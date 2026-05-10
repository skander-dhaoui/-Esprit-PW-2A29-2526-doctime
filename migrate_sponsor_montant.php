<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo = Database::getInstance()->getConnection();
    
    echo "Début de la migration pour ajouter la colonne montant à la table sponsors...\n";
    
    // Vérifier si la colonne montant existe déjà
    $checkColumn = $pdo->query("SHOW COLUMNS FROM sponsors LIKE 'montant'")->fetch();
    
    if ($checkColumn) {
        echo "La colonne 'montant' existe déjà dans la table sponsors.\n";
    } else {
        // Ajouter la colonne montant
        $pdo->exec("ALTER TABLE sponsors ADD COLUMN montant DECIMAL(10,2) DEFAULT 0 AFTER niveau");
        echo "Colonne 'montant' ajoutée avec succès.\n";
        
        // Ajouter un index sur la colonne montant
        $pdo->exec("ALTER TABLE sponsors ADD INDEX idx_montant (montant)");
        echo "Index sur la colonne 'montant' ajouté.\n";
    }
    
    // Mettre à jour les sponsors existants avec des montants par défaut selon leur niveau
    $pdo->exec("UPDATE sponsors SET montant = 10000.00 WHERE niveau = 'platinium' AND montant = 0");
    $pdo->exec("UPDATE sponsors SET montant = 7500.00 WHERE niveau = 'gold' AND montant = 0");
    $pdo->exec("UPDATE sponsors SET montant = 5000.00 WHERE niveau = 'silver' AND montant = 0");
    $pdo->exec("UPDATE sponsors SET montant = 2500.00 WHERE niveau = 'bronze' AND montant = 0");
    echo "Montants par défaut appliqués selon les niveaux de sponsors.\n";
    
    echo "Migration terminée avec succès!\n";
    
} catch (Exception $e) {
    echo "Erreur lors de la migration: " . $e->getMessage() . "\n";
}
?>
