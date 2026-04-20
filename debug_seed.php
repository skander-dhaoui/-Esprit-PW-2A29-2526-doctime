<?php
require_once __DIR__ . '/config/database.php';
try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->query("SHOW COLUMNS FROM sponsors");
    print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
