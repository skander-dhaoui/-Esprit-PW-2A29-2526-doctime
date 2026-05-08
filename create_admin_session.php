<?php
// Create admin session without 2FA
session_start();

require_once __DIR__ . '/config/database.php';
$pdo = Database::getInstance()->getConnection();

// Get admin user
$admin = $pdo->query("SELECT id, email, role FROM users WHERE role = 'admin' LIMIT 1")->fetch();

if ($admin) {
    $_SESSION['user_id'] = $admin['id'];
    $_SESSION['user_email'] = $admin['email'];
    $_SESSION['user_role'] = $admin['role'];
    $_SESSION['user_name'] = 'System Admin';
    
    echo "✅ Session admin créée<br>";
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    echo "Email: " . $_SESSION['user_email'] . "<br>";
    echo "Role: " . $_SESSION['user_role'] . "<br>";
    echo "<br>";
    echo "<a href='index.php?page=carte'>Accéder à la page Carte</a>";
} else {
    echo "❌ Aucun admin trouvé";
}
?>
