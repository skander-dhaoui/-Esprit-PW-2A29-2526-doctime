<?php
require_once __DIR__ . '/config/database.php';
$db = Database::getInstance();

try {
    $db->execute("ALTER TABLE events ADD COLUMN type VARCHAR(100) NULL DEFAULT 'webinaire'");
    echo "Colonne 'type' ajoutée avec succès.\n";
} catch (Exception $e) {
    echo "Erreur type: " . $e->getMessage() . "\n";
}

try {
    $db->execute("ALTER TABLE events ADD COLUMN createur_id INT DEFAULT NULL");
    echo "Colonne 'createur_id' ajoutée.\n";
} catch (Exception $e) {}

try {
    $db->execute("ALTER TABLE events ADD COLUMN createur_type VARCHAR(50) DEFAULT NULL");
    echo "Colonne 'createur_type' ajoutée.\n";
} catch (Exception $e) {}

