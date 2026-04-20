<?php
require 'config/database.php';
try {
    $db = Database::getInstance()->getConnection();
    
    // Check for admin users
    $stmt = $db->query('SELECT id, nom, prenom, email, role, statut FROM users WHERE role="admin" LIMIT 5');
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== ADMIN USERS ===\n";
    echo "Admins found: " . count($admins) . "\n";
    foreach ($admins as $a) {
        echo "- " . $a['email'] . " (" . $a['role'] . ") - " . $a['statut'] . "\n";
    }
    
    if (empty($admins)) {
        echo "❌ No admin users found in database!\n";
        echo "Running debug_login.php to create a test admin account...\n";
    } else {
        echo "✅ Admin users exist\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
